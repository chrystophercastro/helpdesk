<?php
/**
 * HelpDesk Remote — WebSocket Relay Server
 * 
 * Funciona como ponte entre o Client (Python) e o Viewer (Browser).
 * Execute: php remote/server.php [porta]
 * 
 * Protocolo:
 *   Text frames  = JSON (controle, mouse, teclado, clipboard, file info)
 *   Binary frames = Screen data (raw JPEG do client → viewer)
 */

// Permitir execução indefinida
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carregar config do HelpDesk para acesso ao DB
require_once __DIR__ . '/../config/app.php';

$PORT = (int)($argv[1] ?? 8089);
$HOST = '0.0.0.0';

// ==========================================
//  Classes
// ==========================================

class WsClient {
    public $stream;
    public string $id;
    public string $ip;
    public bool $handshakeDone = false;
    public string $buffer = '';
    public string $role = '';        // 'client' ou 'viewer'
    public string $code = '';        // Código de sessão
    public ?WsClient $pair = null;   // Cliente/Viewer pareado
    public array $info = [];         // hostname, username, etc.
    public float $lastActivity;
    public bool $alive = true;

    public function __construct($stream) {
        $this->stream = $stream;
        $this->id = uniqid('ws_', true);
        $this->ip = stream_socket_get_name($stream, true) ?: 'unknown';
        $this->lastActivity = microtime(true);
    }
}

// ==========================================
//  Estado Global
// ==========================================

$server = null;
$clients = [];       // id => WsClient
$codeMap = [];       // code => client_id (clientes que aguardam viewer)
$db = null;

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    logMsg("WARN", "DB não disponível: " . $e->getMessage());
}

// ==========================================
//  Funções WebSocket (RFC 6455)
// ==========================================

function wsHandshake(WsClient $client, string $data): bool {
    if (!preg_match("/Sec-WebSocket-Key:\s*(.+?)\r\n/i", $data, $m)) {
        return false;
    }
    $key = trim($m[1]);
    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

    $origin = '';
    if (preg_match("/Origin:\s*(.+?)\r\n/i", $data, $om)) {
        $origin = "Access-Control-Allow-Origin: " . trim($om[1]) . "\r\n";
    }

    $response = "HTTP/1.1 101 Switching Protocols\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Accept: $accept\r\n" .
        $origin .
        "\r\n";

    @fwrite($client->stream, $response);
    $client->handshakeDone = true;
    return true;
}

function wsDecode(string &$buffer): ?array {
    $len = strlen($buffer);
    if ($len < 2) return null;

    $firstByte = ord($buffer[0]);
    $secondByte = ord($buffer[1]);

    $fin = ($firstByte >> 7) & 1;
    $opcode = $firstByte & 0x0F;
    $masked = ($secondByte >> 7) & 1;
    $payloadLen = $secondByte & 0x7F;

    $offset = 2;

    if ($payloadLen === 126) {
        if ($len < 4) return null;
        $payloadLen = unpack('n', substr($buffer, 2, 2))[1];
        $offset = 4;
    } elseif ($payloadLen === 127) {
        if ($len < 10) return null;
        $payloadLen = unpack('J', substr($buffer, 2, 8))[1];
        $offset = 10;
    }

    if ($masked) {
        if ($len < $offset + 4) return null;
        $maskKey = substr($buffer, $offset, 4);
        $offset += 4;
    }

    if ($len < $offset + $payloadLen) return null;

    $payload = substr($buffer, $offset, $payloadLen);

    if ($masked) {
        for ($i = 0; $i < $payloadLen; $i++) {
            $payload[$i] = chr(ord($payload[$i]) ^ ord($maskKey[$i % 4]));
        }
    }

    // Remover frame processado do buffer
    $buffer = substr($buffer, $offset + $payloadLen);

    return ['opcode' => $opcode, 'payload' => $payload, 'fin' => $fin];
}

function wsEncode(string $payload, int $opcode = 0x01): string {
    $len = strlen($payload);
    $frame = chr(0x80 | $opcode); // FIN + opcode

    if ($len < 126) {
        $frame .= chr($len);
    } elseif ($len < 65536) {
        $frame .= chr(126) . pack('n', $len);
    } else {
        $frame .= chr(127) . pack('J', $len);
    }

    return $frame . $payload;
}

function wsSendText(WsClient $client, string $data): bool {
    return wsSendRaw($client, wsEncode($data, 0x01));
}

function wsSendBinary(WsClient $client, string $data): bool {
    return wsSendRaw($client, wsEncode($data, 0x02));
}

function wsSendJson(WsClient $client, array $data): bool {
    return wsSendText($client, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function wsSendRaw(WsClient $client, string $frame): bool {
    if (!$client->alive || !$client->stream) return false;
    $len = strlen($frame);
    $sent = 0;
    $retries = 0;
    while ($sent < $len) {
        $chunk = ($sent === 0) ? $frame : substr($frame, $sent);
        $written = @fwrite($client->stream, $chunk);
        if ($written === false) {
            $client->alive = false;
            return false;
        }
        if ($written === 0) {
            // Buffer cheio — esperar um pouco
            $retries++;
            if ($retries > 100) {
                // Desistir após muitas tentativas (cliente lento demais)
                logMsg("WARN", "Send timeout para {$client->ip} — frame dropped");
                return false;
            }
            usleep(500); // 0.5ms
            continue;
        }
        $retries = 0;
        $sent += $written;
    }
    return true;
}

function wsSendClose(WsClient $client, int $code = 1000, string $reason = ''): void {
    $payload = pack('n', $code) . $reason;
    wsSendRaw($client, wsEncode($payload, 0x08));
}

function wsSendPing(WsClient $client): bool {
    return wsSendRaw($client, wsEncode('ping', 0x09));
}

// ==========================================
//  Lógica de Mensagens
// ==========================================

function generateCode(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function handleMessage(WsClient $client, string $payload, int $opcode): void {
    global $clients, $codeMap, $db;

    $client->lastActivity = microtime(true);

    // Binary frame = screen data → relay to paired viewer
    if ($opcode === 0x02) {
        if ($client->role === 'client' && $client->pair) {
            wsSendBinary($client->pair, $payload);
        }
        return;
    }

    // Text frame = JSON
    $msg = json_decode($payload, true);
    if (!$msg || !isset($msg['type'])) return;

    switch ($msg['type']) {
        // ===== Client registra-se =====
        case 'register':
            $client->role = 'client';
            $client->info = [
                'hostname' => $msg['hostname'] ?? php_uname('n'),
                'username' => $msg['username'] ?? '',
                'ip' => $msg['ip'] ?? $client->ip,
                'ip_local' => $msg['ip_local'] ?? '',
                'os' => $msg['os'] ?? '',
                'resolution' => $msg['resolution'] ?? [0, 0],
            ];

            // Gerar código único
            do {
                $code = generateCode();
            } while (isset($codeMap[$code]));

            $client->code = $code;
            $codeMap[$code] = $client->id;

            // Registrar no DB
            if ($db) {
                try {
                    $clientId = substr($client->id, 0, 64);
                    $db->query("DELETE FROM remote_sessoes WHERE client_id = ?", [$clientId]);
                    $db->insert('remote_sessoes', [
                        'codigo' => $code,
                        'client_id' => $clientId,
                        'hostname' => $client->info['hostname'],
                        'username' => $client->info['username'],
                        'ip_address' => $client->info['ip'],
                        'ip_local' => $client->info['ip_local'],
                        'os_info' => $client->info['os'],
                        'resolucao' => implode('x', $client->info['resolution']),
                        'status' => 'online',
                    ]);

                    // Tentar vincular ao inventário por hostname
                    $inv = $db->fetch("SELECT id FROM inventario WHERE nome = ? AND status = 'ativo' LIMIT 1", [$client->info['hostname']]);
                    if ($inv) {
                        $db->query("UPDATE remote_sessoes SET inventario_id = ? WHERE codigo = ?", [$inv['id'], $code]);
                    }
                } catch (Exception $e) {
                    logMsg("WARN", "DB insert error: " . $e->getMessage());
                }
            }

            wsSendJson($client, [
                'type' => 'registered',
                'code' => $code,
                'message' => 'Registrado com sucesso. Código: ' . $code,
            ]);

            logMsg("INFO", "Client registrado: {$client->info['hostname']} ({$client->info['ip']}) → Código: $code");
            break;

        // ===== Viewer conecta a um client pelo código =====
        case 'connect':
            $code = strtoupper(trim($msg['code'] ?? ''));
            if (!$code || !isset($codeMap[$code])) {
                wsSendJson($client, ['type' => 'error', 'message' => 'Código inválido ou expirado']);
                return;
            }

            $targetId = $codeMap[$code];
            $target = $clients[$targetId] ?? null;
            if (!$target || !$target->alive) {
                wsSendJson($client, ['type' => 'error', 'message' => 'Cliente não está mais online']);
                unset($codeMap[$code]);
                return;
            }

            $client->role = 'viewer';
            $client->code = $code;
            $client->pair = $target;
            $target->pair = $client;

            $tecnicoId = (int)($msg['tecnico_id'] ?? 0);
            $tecnicoNome = $msg['tecnico_nome'] ?? '';
            $chamadoId = !empty($msg['chamado_id']) ? (int)$msg['chamado_id'] : null;

            // Notificar o client que um viewer conectou
            wsSendJson($target, [
                'type' => 'viewer_connected',
                'tecnico' => $tecnicoNome,
                'tecnico_id' => $tecnicoId,
            ]);

            // Confirmar para o viewer
            wsSendJson($client, [
                'type' => 'connected',
                'hostname' => $target->info['hostname'],
                'username' => $target->info['username'],
                'ip' => $target->info['ip'],
                'os' => $target->info['os'],
                'resolution' => $target->info['resolution'],
            ]);

            // Atualizar DB
            if ($db) {
                try {
                    $db->query(
                        "UPDATE remote_sessoes SET status = 'conectado', tecnico_id = ?, chamado_id = ?, conectado_em = NOW() WHERE codigo = ?",
                        [$tecnicoId ?: null, $chamadoId, $code]
                    );
                    $db->insert('remote_historico', [
                        'sessao_codigo' => $code,
                        'tecnico_id' => $tecnicoId ?: 1,
                        'tecnico_nome' => $tecnicoNome,
                        'hostname' => $target->info['hostname'],
                        'username' => $target->info['username'],
                        'ip_address' => $target->info['ip'],
                        'chamado_id' => $chamadoId,
                        'inventario_id' => null,
                        'inicio' => date('Y-m-d H:i:s'),
                    ]);
                } catch (Exception $e) {
                    logMsg("WARN", "DB update error: " . $e->getMessage());
                }
            }

            logMsg("INFO", "Viewer conectou ao client {$target->info['hostname']} (código: $code) — Técnico: $tecnicoNome");
            break;

        // ===== Aprovação do client =====
        case 'approval':
            if ($client->role === 'client' && $client->pair) {
                wsSendJson($client->pair, [
                    'type' => 'approval',
                    'approved' => (bool)($msg['approved'] ?? false),
                ]);
                if (!($msg['approved'] ?? false)) {
                    disconnectPair($client);
                }
            }
            break;

        // ===== Controle (viewer → client): mouse, teclado, settings =====
        case 'mouse':
        case 'key':
        case 'settings':
        case 'request_frame':
            if ($client->role === 'viewer' && $client->pair) {
                wsSendJson($client->pair, $msg);
            }
            break;

        // ===== Clipboard (bidirecional) =====
        case 'clipboard':
            if ($client->pair) {
                wsSendJson($client->pair, $msg);
            }
            break;

        // ===== Transferência de arquivo (bidirecional) =====
        case 'file_info':
        case 'file_chunk':
        case 'file_complete':
            if ($client->pair) {
                wsSendJson($client->pair, $msg);
            }
            break;

        // ===== Desconectar =====
        case 'disconnect':
            disconnectPair($client);
            break;

        // ===== Keepalive =====
        case 'ping':
            wsSendJson($client, ['type' => 'pong']);
            break;

        // ===== Status (API queries via WS) =====
        case 'list_clients':
            $list = [];
            foreach ($clients as $c) {
                if ($c->role === 'client' && $c->alive) {
                    $list[] = [
                        'code' => $c->code,
                        'hostname' => $c->info['hostname'] ?? '',
                        'username' => $c->info['username'] ?? '',
                        'ip' => $c->info['ip'] ?? '',
                        'os' => $c->info['os'] ?? '',
                        'resolution' => $c->info['resolution'] ?? [],
                        'paired' => $c->pair !== null,
                    ];
                }
            }
            wsSendJson($client, ['type' => 'client_list', 'clients' => $list]);
            break;
    }
}

function disconnectPair(WsClient $client): void {
    global $codeMap, $db;

    $pair = $client->pair;
    $code = $client->code;

    if ($pair) {
        wsSendJson($pair, ['type' => 'disconnected', 'reason' => 'A outra parte se desconectou']);

        // v4.0 FIX: Identificar quem é o client real (pode ser $client ou $pair)
        // Se o viewer desconectou, o pair é o client — manter no codeMap
        $realClient = null;
        $realCode = null;

        if ($client->role === 'client' && $client->alive) {
            $realClient = $client;
            $realCode = $code;
        } elseif ($pair->role === 'client' && $pair->alive) {
            $realClient = $pair;
            $realCode = $pair->code;
        }

        // Limpar pareamento de ambos
        $pair->pair = null;
        $client->pair = null;

        // Re-registrar o client no codeMap para continuar online
        if ($realClient && $realCode) {
            $codeMap[$realCode] = $realClient->id;
            logMsg("INFO", "Client {$realClient->info['hostname']} (código: $realCode) voltou ao modo aguardando");
        }
    } else {
        // Sem par — só limpar
        $client->pair = null;

        // Se é um client sozinho, garantir que continua no codeMap
        if ($client->role === 'client' && $client->alive && $code) {
            $codeMap[$code] = $client->id;
        }
    }

    // Atualizar DB — sessão volta para 'online'
    $dbCode = $code ?: ($pair ? $pair->code : null);
    if ($db && $dbCode) {
        try {
            $db->query("UPDATE remote_sessoes SET status = 'online', tecnico_id = NULL, conectado_em = NULL WHERE codigo = ? AND status = 'conectado'", [$dbCode]);
            $db->query("UPDATE remote_historico SET fim = NOW(), duracao_segundos = TIMESTAMPDIFF(SECOND, inicio, NOW()) WHERE sessao_codigo = ? AND fim IS NULL", [$dbCode]);
        } catch (Exception $e) {}
    }
}

function removeClient(WsClient $client): void {
    global $clients, $codeMap, $db;

    $client->alive = false;

    // Desparear
    disconnectPair($client);

    // Remover do mapa de códigos
    if ($client->code && isset($codeMap[$client->code])) {
        unset($codeMap[$client->code]);
    }

    // Atualizar DB
    if ($db && $client->code) {
        try {
            $db->query("UPDATE remote_sessoes SET status = 'desconectado', desconectado_em = NOW() WHERE codigo = ?", [$client->code]);
            $db->query("UPDATE remote_historico SET fim = NOW(), duracao_segundos = TIMESTAMPDIFF(SECOND, inicio, NOW()) WHERE sessao_codigo = ? AND fim IS NULL", [$client->code]);
        } catch (Exception $e) {}
    }

    @fclose($client->stream);
    unset($clients[$client->id]);

    $role = $client->role ?: 'unknown';
    $info = $client->info['hostname'] ?? $client->ip;
    logMsg("INFO", ucfirst($role) . " desconectou: $info");
}

// ==========================================
//  Logging
// ==========================================

function logMsg(string $level, string $msg): void {
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] [$level] $msg";
    echo $line . "\n";

    // Também gravar em arquivo
    $logFile = __DIR__ . '/server.log';
    @file_put_contents($logFile, $line . "\n", FILE_APPEND);
}

// ==========================================
//  Main Server Loop
// ==========================================

$context = stream_context_create([
    'socket' => [
        'backlog' => 64,
    ],
]);

$server = @stream_socket_server("tcp://$HOST:$PORT", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

if (!$server) {
    logMsg("FATAL", "Não foi possível iniciar o servidor em $HOST:$PORT — $errstr ($errno)");
    exit(1);
}

stream_set_blocking($server, false);

// Gravar PID para controle
$pidFile = __DIR__ . '/server.pid';
@file_put_contents($pidFile, getmypid());
register_shutdown_function(function() use ($pidFile) {
    @unlink($pidFile);
});

logMsg("INFO", "══════════════════════════════════════════════");
logMsg("INFO", "  HelpDesk Remote — WebSocket Relay Server");
logMsg("INFO", "  Escutando em ws://$HOST:$PORT");
logMsg("INFO", "  PID: " . getmypid());
logMsg("INFO", "══════════════════════════════════════════════");

// Limpar sessões antigas
if ($db) {
    try {
        $db->query("UPDATE remote_sessoes SET status = 'desconectado', desconectado_em = NOW() WHERE status IN ('online', 'conectado')");
        $db->query("UPDATE remote_historico SET fim = NOW(), duracao_segundos = TIMESTAMPDIFF(SECOND, inicio, NOW()) WHERE fim IS NULL");
    } catch (Exception $e) {}
}

$lastCleanup = time();
$lastPing = time();

while (true) {
    $readStreams = [$server];
    foreach ($clients as $c) {
        if ($c->alive && $c->stream) {
            $readStreams[] = $c->stream;
        }
    }

    $write = null;
    $except = null;
    $changed = @stream_select($readStreams, $write, $except, 0, 5000); // 5ms timeout (v3.0: mais responsivo)

    if ($changed === false) {
        usleep(10000);
        continue;
    }

    // Aceitar novas conexões
    if (in_array($server, $readStreams)) {
        $newStream = @stream_socket_accept($server, 0);
        if ($newStream) {
            stream_set_blocking($newStream, false);
            stream_set_timeout($newStream, 30);
            // Buffer grande para frames binários (JPEG ~50-150KB)
            @stream_set_write_buffer($newStream, 262144); // 256KB
            @stream_set_read_buffer($newStream, 262144);
            $wsClient = new WsClient($newStream);
            $clients[$wsClient->id] = $wsClient;
            logMsg("DEBUG", "Nova conexão de: {$wsClient->ip}");
        }
    }

    // Ler dados dos clientes
    foreach ($clients as $client) {
        if (!$client->alive || !in_array($client->stream, $readStreams)) continue;

        $data = @fread($client->stream, 4194304); // 4MB max read (v3.0: suportar frames maiores)

        if ($data === false || $data === '') {
            // Verificar se realmente desconectou
            if (feof($client->stream)) {
                removeClient($client);
            }
            continue;
        }

        $client->lastActivity = microtime(true);

        if (!$client->handshakeDone) {
            // WebSocket handshake
            if (wsHandshake($client, $data)) {
                logMsg("DEBUG", "Handshake OK: {$client->ip}");
            } else {
                logMsg("WARN", "Handshake falhou: {$client->ip}");
                removeClient($client);
            }
            continue;
        }

        // Adicionar ao buffer e processar frames
        $client->buffer .= $data;

        while (strlen($client->buffer) > 0) {
            $frame = wsDecode($client->buffer);
            if ($frame === null) break; // Dados incompletos

            switch ($frame['opcode']) {
                case 0x01: // Text
                case 0x02: // Binary
                    handleMessage($client, $frame['payload'], $frame['opcode']);
                    break;
                case 0x08: // Close
                    removeClient($client);
                    break 2;
                case 0x09: // Ping
                    wsSendRaw($client, wsEncode($frame['payload'], 0x0A)); // Pong
                    break;
                case 0x0A: // Pong
                    break;
            }
        }
    }

    // Limpeza periódica (a cada 30s)
    $now = time();
    if ($now - $lastCleanup > 30) {
        $lastCleanup = $now;
        $nowFloat = microtime(true);
        foreach ($clients as $client) {
            // Timeout: 120s sem atividade
            if ($nowFloat - $client->lastActivity > 120) {
                logMsg("INFO", "Timeout: {$client->ip}");
                removeClient($client);
            }
        }
    }

    // Ping periódico (a cada 20s)
    if ($now - $lastPing > 20) {
        $lastPing = $now;
        foreach ($clients as $client) {
            if ($client->alive && $client->handshakeDone) {
                wsSendPing($client);
            }
        }
    }
}
