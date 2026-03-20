<?php
/**
 * API: Posts / Timeline
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// GET requests (listar, ver, comentarios) são públicos para o portal
// POST requests (criar, like, comentar) requerem autenticação
if ($method === 'POST' && !isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}
if ($method === 'GET' && !isLoggedIn()) {
    // Permitir apenas ações de leitura pública
    $allowedPublic = ['listar', 'ver', 'comentarios'];
    $action = $_GET['action'] ?? 'listar';
    if (!in_array($action, $allowedPublic)) {
        jsonResponse(['error' => 'Não autenticado'], 401);
    }
}

require_once __DIR__ . '/../app/models/Post.php';
$postModel = new Post();
$usuarioId = $_SESSION['usuario_id'] ?? 0;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // Se é multipart (upload de mídia), usar $_POST
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    }

    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            $conteudo = trim($data['conteudo'] ?? '');
            if (empty($conteudo) && empty($_FILES['midia'])) {
                jsonResponse(['error' => 'O post precisa ter conteúdo ou mídia'], 400);
            }

            // Processar upload de mídia
            $midiaArray = [];
            if (!empty($_FILES['midia'])) {
                $uploadDir = BASE_PATH . '/uploads/posts/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $files = $_FILES['midia'];
                $fileCount = is_array($files['name']) ? count($files['name']) : 1;

                for ($i = 0; $i < $fileCount; $i++) {
                    $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                    $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                    $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

                    if ($error !== UPLOAD_ERR_OK) continue;
                    if ($size > 50 * 1024 * 1024) continue; // Max 50MB por arquivo

                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif','webp','mp4','webm','mov','avi','pdf'];
                    if (!in_array($ext, $allowed)) continue;

                    $novoNome = uniqid('post_') . '_' . time() . '.' . $ext;
                    $destino = $uploadDir . $novoNome;

                    if (move_uploaded_file($tmpName, $destino)) {
                        $tipo = 'imagem';
                        if (in_array($ext, ['mp4','webm','mov','avi'])) $tipo = 'video';
                        if ($ext === 'pdf') $tipo = 'documento';

                        $midiaArray[] = [
                            'tipo' => $tipo,
                            'url' => '/helpdesk/uploads/posts/' . $novoNome,
                            'nome' => $name,
                            'tamanho' => $size
                        ];
                    }
                }
            }

            // Determinar tipo do post
            $tipo = 'texto';
            if (!empty($midiaArray)) {
                $hasVideo = false;
                $hasImage = false;
                foreach ($midiaArray as $m) {
                    if ($m['tipo'] === 'video') $hasVideo = true;
                    if ($m['tipo'] === 'imagem') $hasImage = true;
                }
                if ($hasVideo) $tipo = 'video';
                elseif (count($midiaArray) > 1) $tipo = 'galeria';
                elseif ($hasImage) $tipo = 'foto';
            }
            if (!empty($data['tipo']) && $data['tipo'] === 'comunicado') {
                $tipo = 'comunicado';
            }

            $postId = $postModel->criar([
                'usuario_id' => $usuarioId,
                'departamento_id' => getUserDeptId(),
                'conteudo' => $conteudo,
                'midia' => !empty($midiaArray) ? json_encode($midiaArray) : null,
                'tipo' => $tipo,
                'fixado' => (isAdmin() && !empty($data['fixado'])) ? 1 : 0
            ]);

            $post = $postModel->findById($postId);
            jsonResponse(['success' => true, 'id' => $postId, 'post' => $post]);
            break;

        case 'atualizar':
            $postId = (int)($data['id'] ?? 0);
            $post = $postModel->findById($postId);
            if (!$post) jsonResponse(['error' => 'Post não encontrado'], 404);

            // Só autor ou admin pode editar
            if ((int)$post['usuario_id'] !== $usuarioId && !isAdmin()) {
                jsonResponse(['error' => 'Sem permissão'], 403);
            }

            $update = [];
            if (isset($data['conteudo'])) $update['conteudo'] = $data['conteudo'];
            if (isAdmin() && isset($data['fixado'])) $update['fixado'] = (int)$data['fixado'];

            $postModel->atualizar($postId, $update);
            jsonResponse(['success' => true]);
            break;

        case 'deletar':
            $postId = (int)($data['id'] ?? 0);
            $post = $postModel->findById($postId);
            if (!$post) jsonResponse(['error' => 'Post não encontrado'], 404);

            // Só autor ou admin pode deletar
            if ((int)$post['usuario_id'] !== $usuarioId && !isAdmin()) {
                jsonResponse(['error' => 'Sem permissão'], 403);
            }

            $postModel->deletar($postId);
            jsonResponse(['success' => true]);
            break;

        case 'like':
            $postId = (int)($data['post_id'] ?? 0);
            $result = $postModel->toggleLike($postId, $usuarioId);
            $post = $postModel->findById($postId);
            $result['likes_count'] = $post['likes_count'] ?? 0;
            jsonResponse($result);
            break;

        case 'comentar':
            $postId = (int)($data['post_id'] ?? 0);
            $conteudo = trim($data['conteudo'] ?? '');
            if (empty($conteudo)) jsonResponse(['error' => 'Comentário vazio'], 400);

            $commentId = $postModel->comentar($postId, $usuarioId, sanitizar($conteudo));

            // Buscar o comentário recém-criado
            $db = Database::getInstance();
            $comment = $db->fetch(
                "SELECT c.*, u.nome as autor_nome, u.avatar as autor_avatar
                 FROM posts_comentarios c
                 LEFT JOIN usuarios u ON c.usuario_id = u.id
                 WHERE c.id = ?", [$commentId]
            );

            $post = $postModel->findById($postId);
            jsonResponse([
                'success' => true,
                'comentario' => $comment,
                'comentarios_count' => $post['comentarios_count'] ?? 0
            ]);
            break;

        case 'remover_comentario':
            $comentarioId = (int)($data['comentario_id'] ?? 0);
            $result = $postModel->removerComentario($comentarioId, $usuarioId);
            jsonResponse(['success' => $result]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }

} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? 'listar';

    switch ($action) {
        case 'listar':
            $pagina = max(1, (int)($_GET['pagina'] ?? 1));
            $resultado = $postModel->listar($pagina, 20);

            // Adicionar info de like do usuário atual
            $postIds = array_column($resultado['posts'], 'id');
            $likesMap = $postModel->getLikesMap($postIds, $usuarioId);

            foreach ($resultado['posts'] as &$p) {
                $p['liked'] = isset($likesMap[$p['id']]);
                $p['midia'] = $p['midia'] ? json_decode($p['midia'], true) : [];
            }

            jsonResponse($resultado);
            break;

        case 'ver':
            $id = (int)($_GET['id'] ?? 0);
            $post = $postModel->findById($id);
            if (!$post) jsonResponse(['error' => 'Post não encontrado'], 404);

            $post['liked'] = $postModel->isLiked($id, $usuarioId);
            $post['midia'] = $post['midia'] ? json_decode($post['midia'], true) : [];
            $post['comentarios'] = $postModel->getComentarios($id);

            jsonResponse($post);
            break;

        case 'comentarios':
            $postId = (int)($_GET['post_id'] ?? 0);
            $comentarios = $postModel->getComentarios($postId);
            jsonResponse($comentarios);
            break;

        default:
            jsonResponse(['error' => 'Ação não especificada'], 400);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
