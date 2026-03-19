<?php
/**
 * API: Deploy / Publicação em Produção
 * Acesso restrito a admin
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Deploy.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || $_SESSION['usuario_tipo'] !== 'admin') {
    jsonResponse(['erro' => 'Acesso restrito a administradores'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
if ($method === 'POST' && empty($action)) {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $body['action'] ?? '';
} else {
    $body = [];
}

try {
    $deploy = new Deploy();

    switch ($action) {
        // ===== Visão geral =====
        case 'overview':
            $stats = $deploy->getStats();
            $config = $deploy->getConfig();
            $version = trim(@file_get_contents(BASE_PATH . '/VERSION') ?: '?');

            // Mascarar senhas
            foreach ($config as $k => &$v) {
                if (str_contains($k, 'pass') && !empty($v['valor'])) {
                    $v['valor'] = '********';
                }
            }

            jsonResponse([
                'stats' => $stats,
                'config' => $config,
                'version' => $version,
                'overrides' => $deploy->getConfigOverrides(),
            ]);
            break;

        // ===== Salvar configurações =====
        case 'save_config':
            $data = $body['config'] ?? [];
            if (empty($data)) {
                jsonResponse(['erro' => 'Nenhum dado recebido'], 400);
            }
            $deploy->saveConfig($data);
            jsonResponse(['success' => true, 'message' => 'Configurações salvas']);
            break;

        // ===== Salvar overrides de produção =====
        case 'save_overrides':
            $overrides = $body['overrides'] ?? [];
            $deploy->saveConfigOverrides($overrides);
            jsonResponse(['success' => true, 'message' => 'Overrides de produção salvos']);
            break;

        // ===== Testar conexão =====
        case 'test_connection':
            $result = $deploy->testConnection();
            jsonResponse($result);
            break;

        // ===== Preview (listar arquivos) =====
        case 'preview':
            $preview = $deploy->preview();
            jsonResponse($preview);
            break;

        // ===== Executar deploy =====
        case 'deploy':
            set_time_limit(600); // 10 minutos máximo
            $tipo = $body['tipo'] ?? 'full';
            $selectedFiles = $body['files'] ?? [];
            $userId = (int)$_SESSION['usuario_id'];
            $result = $deploy->executeDeploy($userId, $tipo, $selectedFiles);
            jsonResponse($result);
            break;

        // ===== Histórico =====
        case 'historico':
            $limit = (int)($_GET['limit'] ?? 20);
            $historico = $deploy->getHistorico($limit);
            jsonResponse(['historico' => $historico]);
            break;

        // ===== Detalhes de um deploy =====
        case 'deploy_detail':
            $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
            if (!$id) jsonResponse(['erro' => 'ID inválido'], 400);
            $detail = $deploy->getDeployById($id);
            if (!$detail) jsonResponse(['erro' => 'Deploy não encontrado'], 404);
            jsonResponse($detail);
            break;

        default:
            jsonResponse(['erro' => 'Ação inválida: ' . $action], 400);
    }

} catch (Exception $e) {
    jsonResponse(['erro' => $e->getMessage()], 500);
}
