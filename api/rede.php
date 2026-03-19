<?php
/**
 * API: Gestão de Rede
 * Monitoramento de dispositivos, ping e scanner
 * Acesso restrito a admin e técnico
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

if (!in_array($_SESSION['usuario_tipo'], ['admin', 'tecnico'])) {
    jsonResponse(['error' => 'Sem permissão'], 403);
}

require_once __DIR__ . '/../app/models/Rede.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['usuario_id'];
$db = Database::getInstance();
$rede = new Rede();

try {
    if ($method === 'POST') {
        if (!empty($_POST)) {
            $data = $_POST;
        } else {
            $jsonBody = file_get_contents('php://input');
            $data = $jsonBody ? json_decode($jsonBody, true) : [];
        }
        unset($data['_csrf']);
        $action = $data['action'] ?? '';

        switch ($action) {
            // ===== DISPOSITIVOS =====
            case 'criar':
                if (empty(trim($data['nome'] ?? '')) || empty(trim($data['ip'] ?? ''))) {
                    jsonResponse(['error' => 'Nome e IP são obrigatórios'], 400);
                }

                $ip = trim($data['ip']);
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    jsonResponse(['error' => 'Endereço IP inválido'], 400);
                }

                // Verificar IP duplicado
                $existente = $rede->findByIp($ip);
                if ($existente) {
                    jsonResponse(['error' => 'Este IP já está cadastrado como "' . $existente['nome'] . '"'], 400);
                }

                $data['criado_por'] = $userId;
                $id = $rede->criar($data);

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'dispositivo_criado',
                    'entidade_tipo' => 'dispositivo_rede',
                    'entidade_id' => $id,
                    'detalhes' => "Dispositivo cadastrado: {$data['nome']} ({$ip})",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                jsonResponse(['success' => true, 'message' => 'Dispositivo cadastrado com sucesso!', 'id' => $id]);
                break;

            case 'atualizar':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }

                $ip = trim($data['ip'] ?? '');
                if ($ip && !filter_var($ip, FILTER_VALIDATE_IP)) {
                    jsonResponse(['error' => 'Endereço IP inválido'], 400);
                }

                // Verificar IP duplicado (excluindo o próprio)
                if ($ip) {
                    $existente = $rede->findByIp($ip);
                    if ($existente && $existente['id'] != $data['id']) {
                        jsonResponse(['error' => 'Este IP já está cadastrado como "' . $existente['nome'] . '"'], 400);
                    }
                }

                $rede->atualizar($data['id'], $data);

                jsonResponse(['success' => true, 'message' => 'Dispositivo atualizado!']);
                break;

            case 'excluir':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }

                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin pode excluir dispositivos'], 403);
                }

                $dispositivo = $rede->findById($data['id']);
                $rede->excluir($data['id']);

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'dispositivo_excluido',
                    'entidade_tipo' => 'dispositivo_rede',
                    'entidade_id' => $data['id'],
                    'detalhes' => "Dispositivo excluído: " . ($dispositivo['nome'] ?? ''),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                jsonResponse(['success' => true, 'message' => 'Dispositivo excluído!']);
                break;

            // ===== PING =====
            case 'ping':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }

                $resultado = $rede->pingarDispositivo($data['id']);
                if (!$resultado) {
                    jsonResponse(['error' => 'Dispositivo não encontrado'], 404);
                }

                jsonResponse(['success' => true, 'data' => $resultado]);
                break;

            case 'ping_ip':
                $ip = trim($data['ip'] ?? '');
                if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
                    jsonResponse(['error' => 'IP inválido'], 400);
                }

                $resultado = $rede->ping($ip);
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'ip' => $ip,
                        'status' => $resultado['online'] ? 'online' : 'offline',
                        'latencia' => $resultado['latencia'],
                    ]
                ]);
                break;

            case 'ping_todos':
                $resultados = $rede->pingarTodos();
                jsonResponse(['success' => true, 'data' => $resultados]);
                break;

            // ===== SCANNER =====
            case 'scan':
                $redeBase = trim($data['rede'] ?? '');
                $inicio = (int)($data['inicio'] ?? 1);
                $fim = (int)($data['fim'] ?? 254);

                if (!$redeBase) {
                    jsonResponse(['error' => 'Informe a rede base (ex: 192.168.1)'], 400);
                }

                set_time_limit(300);
                $resultados = $rede->scanRede($redeBase, $inicio, $fim);

                jsonResponse([
                    'success' => true,
                    'data' => $resultados,
                    'faixa' => "{$redeBase}.{$inicio} - {$redeBase}.{$fim}",
                    'encontrados' => count($resultados),
                ]);
                break;

            // ===== CADASTRAR DO SCAN =====
            case 'cadastrar_scan':
                $ip = trim($data['ip'] ?? '');
                $nome = trim($data['nome'] ?? '');

                if (!$ip || !$nome) {
                    jsonResponse(['error' => 'IP e nome são obrigatórios'], 400);
                }

                $existente = $rede->findByIp($ip);
                if ($existente) {
                    jsonResponse(['error' => 'IP já cadastrado'], 400);
                }

                $data['criado_por'] = $userId;
                $id = $rede->criar($data);

                jsonResponse(['success' => true, 'message' => 'Dispositivo cadastrado!', 'id' => $id]);
                break;

            default:
                jsonResponse(['error' => 'Ação inválida'], 400);
        }

    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'listar':
                $filtros = [
                    'busca' => $_GET['busca'] ?? '',
                    'tipo' => $_GET['tipo'] ?? '',
                    'status' => $_GET['status'] ?? '',
                ];
                $dispositivos = $rede->listar($filtros);
                jsonResponse(['success' => true, 'data' => $dispositivos]);
                break;

            case 'dispositivo':
                if (empty($_GET['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }
                $d = $rede->findById($_GET['id']);
                if (!$d) jsonResponse(['error' => 'Não encontrado'], 404);
                jsonResponse(['success' => true, 'data' => $d]);
                break;

            case 'estatisticas':
                $stats = $rede->getEstatisticas();
                jsonResponse(['success' => true, 'data' => $stats]);
                break;

            case 'historico':
                if (empty($_GET['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }
                $historico = $rede->getHistorico($_GET['id'], (int)($_GET['limite'] ?? 50));
                $uptime = $rede->getUptime24h($_GET['id']);
                jsonResponse(['success' => true, 'data' => $historico, 'uptime_24h' => $uptime]);
                break;

            // ===== INFO DA REDE DO SERVIDOR =====
            case 'rede_info':
                $info = $rede->getServerNetwork();
                jsonResponse(['success' => true, 'data' => $info]);
                break;

            // ===== SCANNER STREAM (SSE) =====
            case 'scan_stream':
                // Fechar sessão para não bloquear outras requisições
                session_write_close();

                // Mudar headers para Server-Sent Events
                header_remove();
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache, no-store');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');

                // Desabilitar todo output buffering
                @ini_set('zlib.output_compression', '0');
                while (ob_get_level()) ob_end_clean();
                ob_implicit_flush(true);

                set_time_limit(300);

                $redeBase = $_GET['rede'] ?? '';
                $inicio = (int)($_GET['inicio'] ?? 1);
                $fim = (int)($_GET['fim'] ?? 254);

                if (!$redeBase || !preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $redeBase)) {
                    echo "data: " . json_encode(['type' => 'error', 'message' => 'Rede base inválida']) . "\n\n";
                    flush();
                    exit;
                }

                $encontrados = 0;

                $rede->scanRedeStream($redeBase, $inicio, $fim, function($data) use (&$encontrados) {
                    if (($data['type'] ?? '') === 'result') $encontrados++;
                    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
                    flush();
                });

                // Sinalizar conclusão
                echo "data: " . json_encode([
                    'type' => 'done',
                    'encontrados' => $encontrados
                ]) . "\n\n";
                flush();
                exit;

            default:
                jsonResponse(['error' => 'Ação não especificada'], 400);
        }

    } else {
        jsonResponse(['error' => 'Método não permitido'], 405);
    }

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
