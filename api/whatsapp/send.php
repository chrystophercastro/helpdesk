<?php
/**
 * API: WhatsApp (Evolution API)
 */
session_start();
require_once __DIR__ . '/../../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../../app/models/Notificacao.php';
$notificacao = new Notificacao();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'test':
            // Test connection to Evolution API
            $db = Database::getInstance();
            $apiUrl = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'evolution_api_url'");
            $apiKey = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'evolution_api_key'");
            $instance = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'evolution_instance'");

            if (!$apiUrl || !$apiKey || !$instance) {
                jsonResponse(['success' => false, 'error' => 'Configurações da Evolution API incompletas']);
            }

            // Try to check instance status
            $url = rtrim($apiUrl, '/') . '/instance/connectionState/' . $instance;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . $apiKey,
                    'Content-Type: application/json'
                ]
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                jsonResponse(['success' => true, 'state' => $result['state'] ?? 'unknown']);
            } else {
                jsonResponse(['success' => false, 'error' => 'Não foi possível conectar à API. HTTP: ' . $httpCode]);
            }
            break;

        case 'send':
            $telefone = $data['telefone'] ?? '';
            $mensagem = $data['mensagem'] ?? '';
            if (empty($telefone) || empty($mensagem)) {
                jsonResponse(['error' => 'Telefone e mensagem são obrigatórios'], 400);
            }
            $result = $notificacao->sendWhatsApp($telefone, $mensagem);
            jsonResponse($result ? ['success' => true] : ['success' => false, 'error' => 'Falha ao enviar']);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
