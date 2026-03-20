<?php
/**
 * API: Compras
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

// Restrito ao departamento de TI
if (!isTIDept()) {
    jsonResponse(['error' => 'Acesso restrito ao departamento de TI'], 403);
}

require_once __DIR__ . '/../app/controllers/CompraController.php';
$controller = new CompraController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            $result = $controller->criar($data);
            jsonResponse($result);
            break;

        case 'alterar_status':
            $id = (int)($data['id'] ?? 0);
            $status = sanitizar($data['status'] ?? '');
            $observacao = sanitizar($data['observacao'] ?? '');
            $result = $controller->alterarStatus($id, $status, $observacao);
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
            $compra = $controller->ver($id);
            jsonResponse($compra ?: ['error' => 'Compra não encontrada']);
            break;

        case 'listar':
            $compras = $controller->listar();
            jsonResponse($compras);
            break;

        default:
            $compras = $controller->listar();
            jsonResponse($compras);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
