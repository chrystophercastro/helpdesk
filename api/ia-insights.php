<?php
/**
 * API: IA Insights — Análises inteligentes para todos os módulos
 * Streaming SSE com prompts contextualizados
 */
session_start();
require_once __DIR__ . '/../config/app.php';

// Verificar autenticação antes de qualquer coisa
if (!isLoggedIn()) {
    header('Content-Type: text/event-stream; charset=utf-8');
    echo "data: " . json_encode(['error' => 'Não autenticado']) . "\n\n";
    exit;
}

require_once __DIR__ . '/../app/models/IA.php';
require_once __DIR__ . '/../app/models/IAInsights.php';

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$type = trim($data['type'] ?? '');

if (empty($type)) {
    header('Content-Type: text/event-stream; charset=utf-8');
    echo "data: " . json_encode(['error' => 'Tipo de insight não informado']) . "\n\n";
    exit;
}

$ia = new IA();
$insights = new IAInsights();

// Verificar se IA está habilitada
if (!$ia->isHabilitado()) {
    header('Content-Type: text/event-stream; charset=utf-8');
    echo "data: " . json_encode(['error' => 'IA está desabilitada. Ative nas configurações.']) . "\n\n";
    exit;
}

// Construir prompts para o tipo de insight
try {
    $prompts = $insights->getPrompts($type, $data);
} catch (\Exception $e) {
    header('Content-Type: text/event-stream; charset=utf-8');
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    exit;
}

// ============================================================
// Setup SSE (idêntico ao padrão de api/ia.php)
// ============================================================
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Content-Encoding: none');
header('Pragma: no-cache');
header('Expires: 0');
session_write_close();

set_time_limit(0);
ini_set('max_execution_time', '0');
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('display_errors', '0');
error_reporting(0);
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
while (ob_get_level()) ob_end_clean();

$sseFlush = function() {
    if (ob_get_level() > 0) @ob_flush();
    @flush();
};

// ============================================================
// Modelo: preferir modelo_analise para insights
// ============================================================
$modelo = $ia->getConfig('modelo_analise') ?: $ia->getConfig('modelo_padrao', 'llama3');
$isCloud = (stripos($modelo, ':cloud') !== false);

$messages = [
    ['role' => 'system', 'content' => $prompts['system']],
    ['role' => 'user',   'content' => $prompts['user']],
];

// Evento inicial
$modelInfo = $ia->getModelMeta($modelo);
echo "data: " . json_encode([
    'status' => 'connected',
    'model' => $modelo,
    'model_label' => $modelInfo['label'] ?? $modelo,
    'type' => $type,
]) . "\n\n";
$sseFlush();

// ============================================================
// Stream da resposta
// ============================================================
$fullContent = '';
$lastHeartbeat = time();

try {
    $ia->chatStream($messages, function($chunk) use ($sseFlush, &$fullContent, &$lastHeartbeat) {
        // Heartbeat (enquanto modelo carrega)
        if (isset($chunk['heartbeat'])) {
            $now = time();
            if ($now - $lastHeartbeat >= 2) {
                echo "data: " . json_encode(['loading' => true, 'status' => 'analyzing']) . "\n\n";
                $sseFlush();
                $lastHeartbeat = $now;
            }
            return;
        }
        // Conteúdo
        if (isset($chunk['message']['content'])) {
            $content = $chunk['message']['content'];
            $fullContent .= $content;
            echo "data: " . json_encode(['content' => $content]) . "\n\n";
            $sseFlush();
        }
    }, $modelo, [
        'max_tokens'     => $isCloud ? 4096 : 2048,
        'temperature'    => 0.5,
        'num_ctx'        => $isCloud ? 8192 : 4096,
        'think'          => false,
        'repeat_penalty' => 1.1,
    ]);
} catch (\Exception $e) {
    echo "data: " . json_encode(['error' => 'Erro na IA: ' . $e->getMessage()]) . "\n\n";
    $sseFlush();
}

echo "data: [DONE]\n\n";
$sseFlush();
