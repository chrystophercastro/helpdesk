<?php
/**
 * API: Monitor / NOC
 * Endpoints para monitoramento de serviços, health checks, uptime
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/Monitor.php';
$monitor = new Monitor();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'overview':
                $data = $monitor->getOverview();
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'servicos':
                $grupo = $_GET['grupo'] ?? null;
                $status = $_GET['status'] ?? null;
                $servicos = $monitor->listarServicos($grupo, $status);
                jsonResponse(['success' => true, 'data' => $servicos]);
                break;

            case 'servico':
                if (empty($_GET['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $servico = $monitor->getServico($_GET['id']);
                if (!$servico) jsonResponse(['error' => 'Serviço não encontrado'], 404);
                jsonResponse(['success' => true, 'data' => $servico]);
                break;

            case 'historico':
                if (empty($_GET['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $historico = $monitor->getHistorico($_GET['id'], (int)($_GET['limite'] ?? 100));
                jsonResponse(['success' => true, 'data' => $historico]);
                break;

            case 'historico_24h':
                if (empty($_GET['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $historico = $monitor->getHistorico24h($_GET['id']);
                jsonResponse(['success' => true, 'data' => $historico]);
                break;

            case 'uptime':
                if (empty($_GET['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $dias = (int)($_GET['dias'] ?? 30);
                $uptime = $monitor->getUptime($_GET['id'], $dias);
                jsonResponse(['success' => true, 'data' => $uptime]);
                break;

            case 'incidentes':
                $servicoId = $_GET['servico_id'] ?? null;
                $incidentes = $monitor->listarIncidentes($servicoId);
                jsonResponse(['success' => true, 'data' => $incidentes]);
                break;

            case 'grupos':
                $grupos = $monitor->listarGrupos();
                jsonResponse(['success' => true, 'data' => $grupos]);
                break;

            case 'status_page':
                $statusPage = $monitor->getStatusPage();
                jsonResponse(['success' => true, 'data' => $statusPage]);
                break;

            default:
                jsonResponse(['error' => 'Ação GET não reconhecida'], 400);
        }
    } elseif ($method === 'POST') {
        switch ($action) {
            case 'criar':
                requireRole(['admin', 'tecnico']);
                if (empty($input['nome']) || empty($input['host'])) {
                    jsonResponse(['error' => 'Nome e host obrigatórios'], 400);
                }
                $input['criado_por'] = $_SESSION['usuario_id'];
                $id = $monitor->criarServico($input);
                jsonResponse(['success' => true, 'message' => 'Serviço criado!', 'data' => ['id' => $id]]);
                break;

            case 'atualizar':
                requireRole(['admin', 'tecnico']);
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $monitor->atualizarServico($input['id'], $input);
                jsonResponse(['success' => true, 'message' => 'Serviço atualizado!']);
                break;

            case 'excluir':
                requireRole(['admin']);
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $monitor->excluirServico($input['id']);
                jsonResponse(['success' => true, 'message' => 'Serviço excluído']);
                break;

            case 'verificar':
                requireRole(['admin', 'tecnico']);
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $resultado = $monitor->verificarServico($input['id']);
                jsonResponse(['success' => true, 'data' => $resultado]);
                break;

            case 'verificar_todos':
                requireRole(['admin', 'tecnico']);
                $resultados = $monitor->verificarTodos();
                jsonResponse(['success' => true, 'data' => $resultados, 'message' => 'Verificação concluída']);
                break;

            default:
                jsonResponse(['error' => 'Ação POST não reconhecida'], 400);
        }
    } else {
        jsonResponse(['error' => 'Método não suportado'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
