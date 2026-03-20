<?php
/**
 * API: Sprints
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/controllers/SprintController.php';
$controller = new SprintController();
$deptFilter = getDeptFilter();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            // Verificar se gestor tem acesso ao projeto da sprint
            if ($deptFilter) {
                $db = Database::getInstance();
                $projDept = $db->fetchColumn("SELECT departamento_id FROM projetos WHERE id = ?", [(int)($data['projeto_id'] ?? 0)]);
                if ((int)$projDept !== (int)$deptFilter) {
                    jsonResponse(['error' => 'Acesso negado: projeto de outro departamento'], 403);
                }
            }
            $result = $controller->criar($data);
            jsonResponse($result);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            // Verificar acesso via projeto
            if ($deptFilter) {
                $sprint = $controller->ver($id);
                if ($sprint) {
                    $db = Database::getInstance();
                    $projDept = $db->fetchColumn("SELECT departamento_id FROM projetos WHERE id = ?", [$sprint['projeto_id'] ?? 0]);
                    if ((int)$projDept !== (int)$deptFilter) {
                        jsonResponse(['error' => 'Acesso negado'], 403);
                    }
                }
            }
            $result = $controller->atualizar($id, $data);
            jsonResponse($result);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'ver':
            $id = (int)($_GET['id'] ?? 0);
            $sprint = $controller->ver($id);
            jsonResponse($sprint ?: ['error' => 'Sprint não encontrado']);
            break;

        case 'listar':
            $projetoId = $_GET['projeto_id'] ?? null;
            $sprints = $controller->listar($projetoId, $deptFilter);
            jsonResponse($sprints);
            break;

        default:
            jsonResponse(['error' => 'Ação não especificada'], 400);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
