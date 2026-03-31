<?php
/**
 * API: Usuários
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/Usuario.php';
$model = new Usuario();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            requireRole(['admin', 'gestor']);
            // Validate
            if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
                jsonResponse(['error' => 'Nome, email e senha são obrigatórios'], 400);
            }
            $db = Database::getInstance();
            $exists = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
            if ($exists) {
                jsonResponse(['error' => 'Email já cadastrado'], 400);
            }
            // Gestor: forçar departamento_id do seu departamento
            $deptFilter = getDeptFilter();
            if ($deptFilter) {
                $data['departamento_id'] = $deptFilter;
                // Gestor não pode criar admin
                if (($data['tipo'] ?? '') === 'admin') {
                    $data['tipo'] = 'tecnico';
                }
            }
            $campos = [
                'nome' => sanitizar($data['nome']),
                'email' => sanitizar($data['email']),
                'senha' => password_hash($data['senha'], PASSWORD_BCRYPT, ['cost' => 12]),
                'tipo' => sanitizar($data['tipo'] ?? 'tecnico'),
                'departamento_id' => !empty($data['departamento_id']) ? (int)$data['departamento_id'] : null,
                'telefone' => sanitizar($data['telefone'] ?? ''),
                'ativo' => 1
            ];
            $id = $db->insert('usuarios', $campos);
            jsonResponse(['success' => true, 'id' => $id]);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            // Users can update their own profile; admin/gestor can update anyone
            if ($id !== (int)$_SESSION['usuario_id']) {
                requireRole(['admin', 'gestor']);
            }
            // Gestor: verificar que o usuário pertence ao seu departamento
            $deptFilter = getDeptFilter();
            if ($deptFilter && $id !== (int)$_SESSION['usuario_id']) {
                $db = Database::getInstance();
                $targetUser = $db->fetch("SELECT departamento_id FROM usuarios WHERE id = ?", [$id]);
                if (!$targetUser || (int)($targetUser['departamento_id'] ?? 0) !== (int)$deptFilter) {
                    jsonResponse(['error' => 'Sem permissão para editar este usuário'], 403);
                }
            }
            $update = [
                'nome' => sanitizar($data['nome'] ?? ''),
                'email' => sanitizar($data['email'] ?? ''),
                'telefone' => sanitizar($data['telefone'] ?? '')
            ];
            // Only admin/gestor can change tipo/departamento
            if (in_array($_SESSION['usuario_tipo'], ['admin', 'gestor'])) {
                if (isset($data['tipo'])) {
                    // Gestor não pode promover a admin
                    if ($deptFilter && $data['tipo'] === 'admin') {
                        $data['tipo'] = 'tecnico';
                    }
                    $update['tipo'] = sanitizar($data['tipo']);
                }
                if (isset($data['departamento_id'])) {
                    // Gestor: forçar seu próprio departamento
                    if ($deptFilter) {
                        $update['departamento_id'] = (int)$deptFilter;
                    } else {
                        $update['departamento_id'] = !empty($data['departamento_id']) ? (int)$data['departamento_id'] : null;
                    }
                }
            }
            if (!empty($data['senha'])) {
                $update['senha'] = password_hash($data['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
            }
            $db = Database::getInstance();
            $db->update('usuarios', $update, 'id = ?', [$id]);
            // Update session if own profile
            if ($id === (int)$_SESSION['usuario_id']) {
                $_SESSION['usuario_nome'] = $update['nome'];
                $_SESSION['usuario_email'] = $update['email'];
                if (!empty($update['telefone'])) $_SESSION['usuario_telefone'] = $update['telefone'];
            }
            jsonResponse(['success' => true]);
            break;

        case 'alterar_senha':
            $id = (int)($data['id'] ?? 0);
            // Only own password or admin
            if ($id !== (int)$_SESSION['usuario_id']) {
                requireRole(['admin']);
            }
            $db = Database::getInstance();
            $user = $db->fetch("SELECT senha FROM usuarios WHERE id = ?", [$id]);
            if (!$user) {
                jsonResponse(['error' => 'Usuário não encontrado'], 404);
            }
            // Verify current password (skip for admin changing others)
            if ($id === (int)$_SESSION['usuario_id']) {
                if (empty($data['senha_atual']) || !password_verify($data['senha_atual'], $user['senha'])) {
                    jsonResponse(['error' => 'Senha atual incorreta'], 400);
                }
            }
            if (empty($data['nova_senha']) || strlen($data['nova_senha']) < 6) {
                jsonResponse(['error' => 'Nova senha deve ter no mínimo 6 caracteres'], 400);
            }
            $novaSenha = password_hash($data['nova_senha'], PASSWORD_BCRYPT, ['cost' => 12]);
            $db->update('usuarios', ['senha' => $novaSenha], 'id = ?', [$id]);
            jsonResponse(['success' => true]);
            break;

        case 'salvar_tema':
            $tema = in_array($data['tema'] ?? '', ['light', 'dark']) ? $data['tema'] : 'light';
            $userId = $_SESSION['usuario_id'];
            $db = Database::getInstance();
            $db->update('usuarios', ['tema' => $tema], 'id = ?', [$userId]);
            $_SESSION['usuario_tema'] = $tema;
            jsonResponse(['success' => true, 'tema' => $tema]);
            break;

        case 'toggle':
            requireRole(['admin', 'gestor']);
            $id = (int)($data['id'] ?? 0);
            $db = Database::getInstance();
            // Gestor: verificar que o usuário pertence ao seu departamento
            $deptFilter = getDeptFilter();
            if ($deptFilter) {
                $targetUser = $db->fetch("SELECT departamento_id, ativo FROM usuarios WHERE id = ?", [$id]);
                if (!$targetUser || (int)($targetUser['departamento_id'] ?? 0) !== (int)$deptFilter) {
                    jsonResponse(['error' => 'Sem permissão'], 403);
                }
                $db->update('usuarios', ['ativo' => $targetUser['ativo'] ? 0 : 1], 'id = ?', [$id]);
                jsonResponse(['success' => true]);
            } else {
                $user = $db->fetch("SELECT ativo FROM usuarios WHERE id = ?", [$id]);
                if ($user) {
                    $db->update('usuarios', ['ativo' => $user['ativo'] ? 0 : 1], 'id = ?', [$id]);
                    jsonResponse(['success' => true]);
                } else {
                    jsonResponse(['error' => 'Usuário não encontrado'], 404);
                }
            }
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'ver':
            $id = (int)($_GET['id'] ?? 0);
            $db = Database::getInstance();
            $user = $db->fetch(
                "SELECT u.id, u.nome, u.email, u.tipo, u.departamento_id, u.telefone, u.ativo, u.ultimo_login,
                        d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor
                 FROM usuarios u
                 LEFT JOIN departamentos d ON d.id = u.departamento_id
                 WHERE u.id = ?", [$id]
            );
            // Gestor: só pode ver usuários do seu departamento
            $deptFilter = getDeptFilter();
            if ($deptFilter && $user && (int)($user['departamento_id'] ?? 0) !== (int)$deptFilter) {
                jsonResponse(['error' => 'Sem permissão'], 403);
            }
            jsonResponse($user ?: ['error' => 'Usuário não encontrado']);
            break;

        case 'listar':
            requireRole(['admin', 'gestor']);
            $filtros = ['busca' => $_GET['busca'] ?? ''];
            // Gestor: filtrar por seu departamento
            $deptFilter = getDeptFilter();
            if ($deptFilter) {
                $filtros['departamento_id'] = $deptFilter;
            }
            $usuarios = $model->listar($filtros);
            jsonResponse($usuarios);
            break;

        default:
            jsonResponse(['error' => 'Ação não especificada'], 400);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
