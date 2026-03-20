<?php
/**
 * API: Módulos / Permissões
 * Admin-only: gerenciar acesso de usuários aos módulos
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/ModuloPermissao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

if (!isAdmin()) {
    jsonResponse(['error' => 'Acesso restrito a administradores'], 403);
}

$perm = new ModuloPermissao();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

try {

if ($method === 'GET') {
    switch ($action) {
        case 'modulos':
            jsonResponse(['success' => true, 'data' => ModuloPermissao::getModulosDisponiveis()]);
            break;

        case 'usuarios_modulo':
            $modulo = $_GET['modulo'] ?? '';
            if (empty($modulo)) jsonResponse(['error' => 'Módulo obrigatório'], 400);
            jsonResponse(['success' => true, 'data' => $perm->getUsuariosModulo($modulo)]);
            break;

        case 'listar_usuarios':
            $modulo = $_GET['modulo'] ?? '';
            if (empty($modulo)) jsonResponse(['error' => 'Módulo obrigatório'], 400);
            jsonResponse(['success' => true, 'data' => $perm->listarUsuariosComFlag($modulo)]);
            break;

        case 'modulos_usuario':
            $uid = (int)($_GET['usuario_id'] ?? 0);
            if (!$uid) jsonResponse(['error' => 'Usuário obrigatório'], 400);
            jsonResponse(['success' => true, 'data' => $perm->getModulosUsuario($uid)]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
}

if ($method === 'POST') {
    switch ($action) {
        case 'conceder':
            $uid = (int)($input['usuario_id'] ?? 0);
            $modulo = $input['modulo'] ?? '';
            $nivel = $input['nivel'] ?? 'leitura';
            if (!$uid || empty($modulo)) jsonResponse(['error' => 'Usuário e módulo obrigatórios'], 400);
            $perm->concederAcesso($uid, $modulo, $nivel);
            jsonResponse(['success' => true]);
            break;

        case 'revogar':
            $uid = (int)($input['usuario_id'] ?? 0);
            $modulo = $input['modulo'] ?? '';
            if (!$uid || empty($modulo)) jsonResponse(['error' => 'Usuário e módulo obrigatórios'], 400);
            $perm->revogarAcesso($uid, $modulo);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
}

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
