<?php
/**
 * API: Base de Conhecimento
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/BaseConhecimento.php';
$model = new BaseConhecimento();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            // Buscar categoria_id pelo nome se necessário
            $categoriaId = null;
            if (!empty($data['categoria'])) {
                $db = Database::getInstance();
                $catRow = $db->fetch("SELECT id FROM categorias WHERE nome = ? AND tipo = 'conhecimento'", [sanitizar($data['categoria'])]);
                $categoriaId = $catRow ? $catRow['id'] : null;
            }
            if (!empty($data['categoria_id'])) {
                $categoriaId = (int)$data['categoria_id'];
            }
            // Suporta tanto 'conteudo' (campo único) quanto 'problema'+'solucao'
            $problema = $data['problema'] ?? $data['conteudo'] ?? '';
            $solucao = $data['solucao'] ?? '';
            $campos = [
                'titulo' => sanitizar($data['titulo'] ?? ''),
                'problema' => $problema,
                'solucao' => $solucao,
                'categoria_id' => $categoriaId,
                'autor_id' => (int)($data['autor_id'] ?? currentUser()['id']),
                'publicado' => 1
            ];
            $id = $model->criar($campos);
            jsonResponse(['success' => true, 'id' => $id]);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            unset($data['action'], $data['id']);
            $model->atualizar($id, $data);
            jsonResponse(['success' => true]);
            break;

        case 'deletar':
            $id = (int)($data['id'] ?? 0);
            $model->deletar($id);
            jsonResponse(['success' => true]);
            break;

        case 'util':
            $id = (int)($data['id'] ?? 0);
            $model->marcarUtil($id);
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
            $artigo = $model->findById($id);
            if ($artigo) {
                $model->incrementarVisualizacao($id);
                // Get author name
                $db = Database::getInstance();
                $autor = $db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$artigo['autor_id'] ?? 0]);
                $artigo['autor_nome'] = $autor['nome'] ?? 'Sistema';
                // Backward compat: merge problema+solucao into conteudo
                $artigo['conteudo'] = trim(($artigo['problema'] ?? '') . "\n\n" . ($artigo['solucao'] ?? ''));
                $artigo['categoria'] = $artigo['categoria_nome'] ?? 'Geral';
                $artigo['created_at'] = $artigo['criado_em'] ?? '';
            }
            jsonResponse($artigo ?: ['error' => 'Artigo não encontrado']);
            break;

        case 'buscar':
            $termo = $_GET['q'] ?? '';
            $resultados = $model->buscar($termo);
            jsonResponse($resultados);
            break;

        default:
            $filtros = [
                'busca' => $_GET['busca'] ?? '',
                'publicado' => 1
            ];
            $artigos = $model->listar($filtros);
            jsonResponse($artigos);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
