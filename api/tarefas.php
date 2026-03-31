<?php
/**
 * API: Tarefas
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/controllers/TarefaController.php';
$controller = new TarefaController();
$deptFilter = getDeptFilter();

/**
 * Verifica se o usuário tem acesso ao projeto da tarefa.
 */
function verificarAcessoProjeto($projetoId, $deptFilter) {
    if (!$deptFilter) return true;
    $db = Database::getInstance();
    $projDept = $db->fetchColumn("SELECT departamento_id FROM projetos WHERE id = ?", [$projetoId]);
    return (int)$projDept === (int)$deptFilter;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            if ($deptFilter && !verificarAcessoProjeto((int)($data['projeto_id'] ?? 0), $deptFilter)) {
                jsonResponse(['error' => 'Acesso negado: projeto de outro departamento'], 403);
            }
            $result = $controller->criar($data);
            jsonResponse($result);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            if ($deptFilter) {
                require_once __DIR__ . '/../app/models/Tarefa.php';
                $tModel = new Tarefa();
                $tarefa = $tModel->findById($id);
                if ($tarefa && !verificarAcessoProjeto((int)($tarefa['projeto_id'] ?? 0), $deptFilter)) {
                    jsonResponse(['error' => 'Acesso negado'], 403);
                }
            }
            $result = $controller->atualizar($id, $data);
            jsonResponse($result);
            break;

        case 'mover':
            $id = (int)($data['tarefa_id'] ?? 0);
            if ($deptFilter) {
                require_once __DIR__ . '/../app/models/Tarefa.php';
                $tModel = new Tarefa();
                $tarefa = $tModel->findById($id);
                if ($tarefa && !verificarAcessoProjeto((int)($tarefa['projeto_id'] ?? 0), $deptFilter)) {
                    jsonResponse(['error' => 'Acesso negado'], 403);
                }
            }
            $coluna = sanitizar($data['coluna'] ?? '');
            $posicao = (int)($data['posicao'] ?? 0);
            $result = $controller->mover($id, $coluna, $posicao);
            jsonResponse($result);
            break;

        case 'deletar':
            $id = (int)($data['id'] ?? 0);
            if ($deptFilter) {
                require_once __DIR__ . '/../app/models/Tarefa.php';
                $tModel = new Tarefa();
                $tarefa = $tModel->findById($id);
                if ($tarefa && !verificarAcessoProjeto((int)($tarefa['projeto_id'] ?? 0), $deptFilter)) {
                    jsonResponse(['error' => 'Acesso negado'], 403);
                }
            }
            $result = $controller->deletar($id);
            jsonResponse($result);
            break;

        case 'comentar':
            $tarefaId = (int)($data['tarefa_id'] ?? 0);
            $conteudo = trim($data['conteudo'] ?? '');
            if (!$tarefaId || !$conteudo) {
                jsonResponse(['error' => 'Tarefa e conteúdo são obrigatórios'], 400);
            }
            if ($deptFilter) {
                require_once __DIR__ . '/../app/models/Tarefa.php';
                $tModel = new Tarefa();
                $tarefa = $tModel->findById($tarefaId);
                if ($tarefa && !verificarAcessoProjeto((int)($tarefa['projeto_id'] ?? 0), $deptFilter)) {
                    jsonResponse(['error' => 'Acesso negado'], 403);
                }
            }
            $result = $controller->comentar($tarefaId, $conteudo);
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
            require_once __DIR__ . '/../app/models/Tarefa.php';
            $tarefaModel = new Tarefa();
            $tarefa = $tarefaModel->findById($id);
            if ($tarefa) {
                $tarefa['comentarios'] = $tarefaModel->getComentarios($id);
                $tarefa['tags'] = $tarefaModel->getTags($id);
            }
            jsonResponse($tarefa ?: ['error' => 'Tarefa não encontrada']);
            break;

        case 'kanban':
            $projetoId = $_GET['projeto_id'] ?? null;
            $kanban = $controller->listarKanban($projetoId, $deptFilter);
            jsonResponse($kanban);
            break;

        default:
            jsonResponse(['error' => 'Ação não especificada'], 400);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
