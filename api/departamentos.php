<?php
/**
 * API: Departamentos
 * CRUD completo para gerenciamento de departamentos
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Departamento.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$model = new Departamento();

// ==========================================
//  GET — Listar / Detalhe
// ==========================================
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'listar';

    switch ($action) {
        case 'listar':
            $filtros = [
                'ativo' => $_GET['ativo'] ?? null,
                'busca'  => $_GET['busca'] ?? '',
            ];
            if ($filtros['ativo'] === null || $filtros['ativo'] === '') unset($filtros['ativo']);
            jsonResponse(['departamentos' => $model->listar($filtros)]);
            break;

        case 'ativos':
            jsonResponse(['departamentos' => $model->listarAtivos()]);
            break;

        case 'detalhe':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $dept = $model->findById($id);
            if (!$dept) jsonResponse(['error' => 'Departamento não encontrado'], 404);
            $dept['categorias'] = $model->getCategorias($id);
            $dept['stats'] = $model->getStats($id);
            jsonResponse(['departamento' => $dept]);
            break;

        case 'categorias':
            $id = (int)($_GET['departamento_id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'departamento_id obrigatório'], 400);
            jsonResponse(['categorias' => $model->getCategorias($id)]);
            break;

        default:
            jsonResponse(['error' => 'Ação GET desconhecida'], 400);
    }
    exit;
}

// ==========================================
//  POST — Criar / Atualizar / Excluir
// ==========================================
if ($method === 'POST') {
    requireLogin();
    requireRole(['admin']);

    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            if (empty($data['nome']) || empty($data['sigla'])) {
                jsonResponse(['error' => 'Nome e sigla são obrigatórios'], 400);
            }
            $result = $model->criar($data);
            jsonResponse($result, isset($result['error']) ? 400 : 200);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            if (empty($data['nome']) || empty($data['sigla'])) {
                jsonResponse(['error' => 'Nome e sigla são obrigatórios'], 400);
            }
            $result = $model->atualizar($id, $data);
            jsonResponse($result, isset($result['error']) ? 400 : 200);
            break;

        case 'toggle':
            $id = (int)($data['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $result = $model->toggleAtivo($id);
            jsonResponse($result, isset($result['error']) ? 400 : 200);
            break;

        case 'excluir':
            $id = (int)($data['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $result = $model->excluir($id);
            jsonResponse($result, isset($result['error']) ? 400 : 200);
            break;

        default:
            jsonResponse(['error' => 'Ação POST desconhecida'], 400);
    }
    exit;
}

jsonResponse(['error' => 'Método não suportado'], 405);
