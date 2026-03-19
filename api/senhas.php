<?php
/**
 * API: Senhas (Cofre de Senhas)
 * Acesso restrito a admin e técnicos
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

// Apenas admin e técnicos podem acessar o cofre
if (!in_array($_SESSION['usuario_tipo'], ['admin', 'tecnico'])) {
    jsonResponse(['error' => 'Sem permissão para acessar o cofre de senhas'], 403);
}

require_once __DIR__ . '/../app/models/Senha.php';
$model = new Senha();
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['usuario_id'];

try {
    if ($method === 'POST') {
        if (!empty($_POST)) {
            $data = $_POST;
        } else {
            $jsonBody = file_get_contents('php://input');
            $data = $jsonBody ? json_decode($jsonBody, true) : [];
        }
        unset($data['_csrf']);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'criar':
                $titulo = trim($data['titulo'] ?? '');
                $senha = $data['senha'] ?? '';
                $categoria = $data['categoria'] ?? 'outro';

                if (empty($titulo)) {
                    jsonResponse(['error' => 'Título é obrigatório'], 400);
                }
                if (empty($senha)) {
                    jsonResponse(['error' => 'Senha é obrigatória'], 400);
                }

                $id = $model->criar([
                    'titulo' => sanitizar($titulo),
                    'categoria' => $categoria,
                    'url' => sanitizar($data['url'] ?? ''),
                    'usuario' => $data['usuario'] ?? '',
                    'senha' => $senha,
                    'notas' => $data['notas'] ?? '',
                    'ip_host' => sanitizar($data['ip_host'] ?? ''),
                    'porta' => sanitizar($data['porta'] ?? ''),
                    'criado_por' => $userId
                ]);

                $model->registrarLog($id, $userId, 'criar');

                jsonResponse(['success' => true, 'id' => $id]);
                break;

            case 'atualizar':
                $id = (int)($data['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

                $existing = $model->findById($id);
                if (!$existing) jsonResponse(['error' => 'Senha não encontrada'], 404);

                $titulo = trim($data['titulo'] ?? '');
                if (empty($titulo)) {
                    jsonResponse(['error' => 'Título é obrigatório'], 400);
                }

                $model->atualizar($id, [
                    'titulo' => sanitizar($titulo),
                    'categoria' => $data['categoria'] ?? 'outro',
                    'url' => sanitizar($data['url'] ?? ''),
                    'usuario' => $data['usuario'] ?? '',
                    'senha' => $data['senha'] ?? '',
                    'notas' => $data['notas'] ?? '',
                    'ip_host' => sanitizar($data['ip_host'] ?? ''),
                    'porta' => sanitizar($data['porta'] ?? ''),
                    'atualizado_por' => $userId
                ]);

                $model->registrarLog($id, $userId, 'editar');

                jsonResponse(['success' => true]);
                break;

            case 'excluir':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas administradores podem excluir senhas'], 403);
                }

                $id = (int)($data['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

                $existing = $model->findById($id);
                if (!$existing) jsonResponse(['error' => 'Senha não encontrada'], 404);

                $model->registrarLog($id, $userId, 'excluir');
                $model->deletar($id);

                jsonResponse(['success' => true]);
                break;

            case 'revelar':
                $id = (int)($data['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

                $senhaData = $model->findById($id);
                if (!$senhaData) jsonResponse(['error' => 'Senha não encontrada'], 404);

                $model->registrarLog($id, $userId, 'visualizar');

                jsonResponse([
                    'success' => true,
                    'usuario' => $senhaData['usuario_dec'],
                    'senha' => $senhaData['senha_dec'],
                    'notas' => $senhaData['notas_dec']
                ]);
                break;

            case 'copiar_log':
                $id = (int)($data['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID inválido'], 400);
                $model->registrarLog($id, $userId, 'copiar');
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
                if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

                $senhaData = $model->findById($id);
                if (!$senhaData) jsonResponse(['error' => 'Senha não encontrada'], 404);

                // Não enviar senha descriptografada no GET - usar ação 'revelar'
                unset($senhaData['senha'], $senhaData['senha_dec']);
                unset($senhaData['usuario'], $senhaData['notas']);
                $senhaData['usuario_dec'] = $senhaData['usuario_dec'] ?? '';
                $senhaData['notas_dec'] = $senhaData['notas_dec'] ?? '';

                jsonResponse(['success' => true, 'data' => $senhaData]);
                break;

            case 'listar':
                $filtros = [
                    'categoria' => $_GET['categoria'] ?? '',
                    'busca' => $_GET['busca'] ?? ''
                ];
                $senhas = $model->listar($filtros);

                // Descriptografar usuários para exibição na listagem
                foreach ($senhas as &$s) {
                    $s['usuario_dec'] = $model->decryptUsuario($s['usuario']);
                    unset($s['usuario']); // Não enviar o valor criptografado
                }

                jsonResponse(['success' => true, 'data' => $senhas]);
                break;

            case 'logs':
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID inválido'], 400);
                $logs = $model->getLogs($id);
                jsonResponse(['success' => true, 'data' => $logs]);
                break;

            case 'categorias':
                $categorias = $model->contarPorCategoria();
                jsonResponse(['success' => true, 'data' => $categorias]);
                break;

            default:
                jsonResponse(['error' => 'Ação não especificada'], 400);
        }
    } else {
        jsonResponse(['error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Erro interno: ' . $e->getMessage()], 500);
}
