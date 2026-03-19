<?php
/**
 * API: Projetos
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/controllers/ProjetoController.php';
$controller = new ProjetoController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            $result = $controller->criar($data);
            jsonResponse($result);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            $result = $controller->atualizar($id, $data);
            jsonResponse($result);
            break;

        case 'comentar':
            $projetoId = (int)($data['projeto_id'] ?? 0);
            $conteudo = $data['conteudo'] ?? '';
            $result = $controller->comentar($projetoId, $conteudo);
            jsonResponse($result);
            break;

        case 'adicionar_membro':
            $projetoId = (int)($data['projeto_id'] ?? 0);
            $usuarioId = (int)($data['usuario_id'] ?? 0);
            $funcao = sanitizar($data['funcao'] ?? 'membro');
            require_once __DIR__ . '/../app/models/Projeto.php';
            $projetoModel = new Projeto();
            $projetoModel->adicionarMembro($projetoId, $usuarioId, $funcao);
            jsonResponse(['success' => true]);
            break;

        case 'remover_membro':
            $projetoId = (int)($data['projeto_id'] ?? 0);
            $usuarioId = (int)($data['usuario_id'] ?? 0);
            require_once __DIR__ . '/../app/models/Projeto.php';
            $projetoModel = new Projeto();
            $projetoModel->removerMembro($projetoId, $usuarioId);
            jsonResponse(['success' => true]);
            break;

        case 'alterar_papel':
            $projetoId = (int)($data['projeto_id'] ?? 0);
            $usuarioId = (int)($data['usuario_id'] ?? 0);
            $papel = sanitizar($data['papel'] ?? 'membro');
            $papeisValidos = ['membro', 'lider', 'desenvolvedor', 'analista', 'testador'];
            if (!in_array($papel, $papeisValidos)) {
                jsonResponse(['error' => 'Papel inválido'], 400);
            }
            require_once __DIR__ . '/../app/models/Projeto.php';
            $projetoModel = new Projeto();
            $projetoModel->alterarPapel($projetoId, $usuarioId, $papel);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'ver':
            $id = (int)($_GET['id'] ?? 0);
            $projeto = $controller->ver($id);
            jsonResponse($projeto ?: ['error' => 'Projeto não encontrado']);
            break;

        case 'listar':
            $projetos = $controller->listar();
            jsonResponse($projetos);
            break;

        default:
            $projetos = $controller->listar();
            jsonResponse($projetos);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
