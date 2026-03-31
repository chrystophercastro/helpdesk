<?php
/**
 * Webhook: Chatbot - Evolution API
 * 
 * Endpoint público para receber mensagens do WhatsApp via Evolution API.
 * NÃO requer autenticação de sessão (é chamado pelo Evolution).
 * 
 * Otimizações v2:
 * - Responde 200 imediatamente ao Evolution (evita timeout e reenvio)
 * - Deduplicação por message ID (evita processar mesma mensagem várias vezes)
 * - Descarta eventos não-mensagem antes de carregar o modelo
 * - Cache do contexto DB (5 min) para não refazer schema a cada msg
 */
require_once __DIR__ . '/../config/app.php';

// Iniciar output buffering para controle de resposta
ob_start();
header('Content-Type: application/json');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Ler body
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

// Preparar log
$logFile = __DIR__ . '/../storage/logs/webhook_chatbot.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Verificar tipo de evento ANTES de carregar modelo pesado
$event = $data['event'] ?? '';

// Descartar eventos que não são mensagens (presença, status, contacts, etc.)
if ($event !== 'messages.upsert' && !isset($data['data']['message'])) {
    echo json_encode(['success' => true, 'message' => 'Evento ignorado: ' . $event]);
    exit;
}

// Log apenas mensagens reais (não logar presença/status que sobrecarregam)
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "[MSG] " . substr($rawBody, 0, 500) . "\n", FILE_APPEND);

// Carregar modelo (apenas se for mensagem real)
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/ChatbotModel.php';

/**
 * Gera uma mensagem de "estou consultando" mais humana e contextual.
 */
function gerarMensagemBuscandoHumana($nomeContato, $textoPergunta) {
    $nome = trim((string) $nomeContato);
    if ($nome === '') {
        $nome = 'você';
    } else {
        // Usa só o primeiro nome para soar natural
        $partesNome = preg_split('/\s+/', $nome);
        $nome = $partesNome[0] ?? $nome;
    }

    $texto = mb_strtolower((string) $textoPergunta, 'UTF-8');
    $tema = 'isso';

    if (preg_match('/\b(estoque|saldo|dispon[ií]vel|invent[aá]rio)\b/u', $texto)) {
        $tema = 'estoque';
    } elseif (preg_match('/\b(venda|vendas|faturamento|receita)\b/u', $texto)) {
        $tema = 'vendas';
    } elseif (preg_match('/\b(pedido|pedidos|compra|compras)\b/u', $texto)) {
        $tema = 'pedidos';
    } elseif (preg_match('/\b(cliente|clientes)\b/u', $texto)) {
        $tema = 'clientes';
    } elseif (preg_match('/\b(produto|produtos|marca|marcas|sku)\b/u', $texto)) {
        $tema = 'produtos';
    } elseif (preg_match('/\b(financeiro|lucro|preju[ií]zo|custo|margem)\b/u', $texto)) {
        $tema = 'financeiro';
    }

    $mensagens = [
        "Perfeito, {$nome}! Ja estou consultando {$tema} para te responder com dados reais.",
        "Boa, {$nome}! Vou puxar {$tema} no sistema agora e ja te trago certinho.",
        "{$nome}, deixa comigo: estou cruzando os dados de {$tema} e ja volto com a resposta.",
    ];

    return $mensagens[array_rand($mensagens)];
}

try {
    $chatbot = new ChatbotModel();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao inicializar chatbot: ' . $e->getMessage()]);
    exit;
}

// Validar API Key (opcional, via header)
$apiKeyHeader = $_SERVER['HTTP_APIKEY'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$evolutionApiKey = $chatbot->getEvolutionConfig('evolution_api_key');
if (!empty($evolutionApiKey) && !empty($apiKeyHeader) && $apiKeyHeader !== $evolutionApiKey) {
    http_response_code(401);
    echo json_encode(['error' => 'API Key inválida']);
    exit;
}

// Processar mensagem recebida
$messageData = $data['data'] ?? $data;
$key = $messageData['key'] ?? [];
$messageContent = $messageData['message'] ?? [];

// Ignorar mensagens enviadas por nós mesmos
if (($key['fromMe'] ?? false) === true) {
    echo json_encode(['success' => true, 'message' => 'Mensagem própria ignorada']);
    exit;
}

// Extrair número do remetente
$remoteJid = $key['remoteJid'] ?? '';
if (empty($remoteJid)) {
    echo json_encode(['success' => true, 'message' => 'Sem remetente']);
    exit;
}

// Ignorar grupos (contêm @g.us)
if (str_contains($remoteJid, '@g.us')) {
    echo json_encode(['success' => true, 'message' => 'Grupos ignorados']);
    exit;
}

// Extrair número real do remetente
// WhatsApp LID: remoteJid pode ser "84005986775080@lid" (ID interno, não é telefone)
// Nesse caso, o telefone real vem em "senderPn" (ex: "556293372731@s.whatsapp.net")
if (str_contains($remoteJid, '@lid')) {
    $senderPn = $key['senderPn'] ?? '';
    if (empty($senderPn)) {
        $senderPn = $key['participant'] ?? '';
    }
    if (empty($senderPn)) {
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERRO: remoteJid é LID mas senderPn vazio\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'LID sem senderPn']);
        exit;
    }
    $numero = preg_replace('/@.*$/', '', $senderPn);
} else {
    $numero = preg_replace('/@.*$/', '', $remoteJid);
}

// Guardar o remoteJid original para enviar a resposta ao destino correto
$replyJid = $remoteJid;

// Extrair texto da mensagem
$texto = '';
if (isset($messageContent['conversation'])) {
    $texto = $messageContent['conversation'];
} elseif (isset($messageContent['extendedTextMessage']['text'])) {
    $texto = $messageContent['extendedTextMessage']['text'];
} elseif (isset($messageContent['imageMessage']['caption'])) {
    $texto = $messageContent['imageMessage']['caption'];
} elseif (isset($messageContent['documentMessage'])) {
    $texto = '[Documento recebido]';
} elseif (isset($messageContent['audioMessage'])) {
    $texto = '[Áudio recebido]';
} elseif (isset($messageContent['videoMessage'])) {
    $texto = '[Vídeo recebido]';
} elseif (isset($messageContent['stickerMessage'])) {
    $texto = '[Sticker recebido]';
} elseif (isset($messageContent['contactMessage'])) {
    $texto = '[Contato recebido]';
} elseif (isset($messageContent['locationMessage'])) {
    $texto = '[Localização recebida]';
}

// Se não há texto para processar, ignorar
if (empty($texto) || str_starts_with($texto, '[')) {
    echo json_encode(['success' => true, 'message' => 'Tipo de mensagem não suportado']);
    exit;
}

// DEDUPLICAÇÃO: Ignorar mensagens já processadas
// O Evolution reenvia a mesma mensagem quando o webhook demora para responder
$messageId = $key['id'] ?? '';
if (!empty($messageId)) {
    $dedupDir = __DIR__ . '/../storage/cache/webhook_dedup';
    if (!is_dir($dedupDir)) {
        mkdir($dedupDir, 0755, true);
    }
    $dedupFile = $dedupDir . '/' . md5($messageId) . '.lock';
    if (file_exists($dedupFile)) {
        // Já processamos essa mensagem, ignorar
        echo json_encode(['success' => true, 'message' => 'Mensagem duplicada ignorada']);
        exit;
    }
    // Marcar como sendo processada
    file_put_contents($dedupFile, time());

    // Limpeza de locks antigos (> 10 min) - a cada ~50 requests
    if (rand(1, 50) === 1) {
        foreach (glob($dedupDir . '/*.lock') as $f) {
            if (filemtime($f) < time() - 600) {
                @unlink($f);
            }
        }
    }
}

// Extrair nome do contato
$nomeContato = $messageData['pushName'] ?? $numero;

// ============================================================
// RESPONDER 200 AO EVOLUTION RAPIDAMENTE
// Envia resposta HTTP e libera o Evolution, mas mantém o PHP rodando
// A deduplicação acima evita que retransmissões sejam processadas
// ============================================================
set_time_limit(300);
ignore_user_abort(true);

// Enviar 200 OK para o Evolution o mais rápido possível
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Processando']);

// Tentar fechar a conexão HTTP mantendo o PHP rodando
header('Connection: close');
header('Content-Length: ' . ob_get_length());
ob_end_flush();
flush();

// Para servidores PHP-FPM, liberar a conexão completamente
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Log do início do processamento
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "PROCESSANDO: {$numero} -> \"{$texto}\"\n", FILE_APPEND);

// Mensagem instantânea de feedback ao usuário (não bloquear esperando IA)
try {
    $msgHumana = gerarMensagemBuscandoHumana($nomeContato, $texto);
    $chatbot->enviarWhatsApp($numero, $msgHumana);
} catch (Exception $e) {
    // Se falhar, segue o processamento normalmente
}

// PROCESSAR A MENSAGEM
try {
    $resposta = $chatbot->processarMensagem($numero, $texto, $nomeContato);
} catch (Exception $e) {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERRO processarMensagem: " . $e->getMessage() . "\n", FILE_APPEND);
    $resposta = null;
}

// Enviar resposta via WhatsApp
if (!empty($resposta)) {
    try {
        // Enviar de forma “quebrada” para ficar mais fluido no WhatsApp
        if (method_exists($chatbot, 'enviarWhatsAppQuebrado')) {
            $chatbot->enviarWhatsAppQuebrado($numero, $resposta);
        } else {
            $chatbot->enviarWhatsApp($numero, $resposta);
        }
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "OK: Resposta enviada para {$numero} (" . strlen($resposta) . " chars)\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERRO envio WhatsApp: " . $e->getMessage() . "\n", FILE_APPEND);
    }
} else {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "SEM RESPOSTA para {$numero} (chatbot inativo ou não autorizado)\n", FILE_APPEND);
}



