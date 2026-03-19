<?php
/**
 * API: CMDB (Configuration Management Database)
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/CMDB.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

requireRole(['admin', 'gestor', 'tecnico']);

$cmdb = new CMDB();
$usuarioId = $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'overview':
            jsonResponse(['success' => true, 'data' => $cmdb->getOverview()]);
            break;

        case 'categorias':
            jsonResponse(['success' => true, 'data' => $cmdb->listarCategorias()]);
            break;

        case 'itens':
            $filtros = [];
            if (!empty($_GET['categoria_id'])) $filtros['categoria_id'] = $_GET['categoria_id'];
            if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
            if (!empty($_GET['criticidade'])) $filtros['criticidade'] = $_GET['criticidade'];
            if (!empty($_GET['ambiente'])) $filtros['ambiente'] = $_GET['ambiente'];
            if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
            jsonResponse(['success' => true, 'data' => $cmdb->listarItens($filtros)]);
            break;

        case 'item':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $item = $cmdb->getItem($id);
            if (!$item) jsonResponse(['success' => false, 'error' => 'Item não encontrado'], 404);
            jsonResponse(['success' => true, 'data' => $item]);
            break;

        case 'mapa':
            jsonResponse(['success' => true, 'data' => $cmdb->getMapaRelacionamentos()]);
            break;

        case 'impacto':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $item = $cmdb->getItem($id);
            $impacto = $cmdb->analisarImpacto($id);
            jsonResponse(['success' => true, 'data' => ['item' => $item, 'impacto' => $impacto]]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        // Categorias
        case 'criar_categoria':
            requireRole(['admin', 'gestor']);
            $dados = [
                'nome' => trim($input['nome'] ?? ''),
                'icone' => trim($input['icone'] ?? 'fas fa-cube'),
                'cor' => trim($input['cor'] ?? '#3B82F6'),
                'descricao' => trim($input['descricao'] ?? ''),
                'pai_id' => !empty($input['pai_id']) ? (int)$input['pai_id'] : null,
                'ordem' => (int)($input['ordem'] ?? 0)
            ];
            if (empty($dados['nome'])) jsonResponse(['success' => false, 'error' => 'Nome obrigatório'], 400);
            $cmdb->criarCategoria($dados);
            jsonResponse(['success' => true, 'message' => 'Categoria criada!']);
            break;

        case 'atualizar_categoria':
            requireRole(['admin', 'gestor']);
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $campos = ['nome','icone','cor','descricao','pai_id','ordem'];
            $dados = [];
            foreach ($campos as $c) { if (isset($input[$c])) $dados[$c] = $input[$c]; }
            $cmdb->atualizarCategoria($id, $dados);
            jsonResponse(['success' => true, 'message' => 'Categoria atualizada!']);
            break;

        case 'excluir_categoria':
            requireRole(['admin']);
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $cmdb->excluirCategoria($id);
            jsonResponse(['success' => true, 'message' => 'Categoria excluída!']);
            break;

        // Itens
        case 'criar_item':
            $dados = [
                'nome' => trim($input['nome'] ?? ''),
                'identificador' => trim($input['identificador'] ?? ''),
                'categoria_id' => !empty($input['categoria_id']) ? (int)$input['categoria_id'] : null,
                'inventario_id' => !empty($input['inventario_id']) ? (int)$input['inventario_id'] : null,
                'descricao' => trim($input['descricao'] ?? ''),
                'status' => $input['status'] ?? 'ativo',
                'criticidade' => $input['criticidade'] ?? 'media',
                'ambiente' => $input['ambiente'] ?? 'producao',
                'versao' => trim($input['versao'] ?? ''),
                'ip_endereco' => trim($input['ip_endereco'] ?? ''),
                'localizacao' => trim($input['localizacao'] ?? ''),
                'responsavel_id' => !empty($input['responsavel_id']) ? (int)$input['responsavel_id'] : null,
                'fornecedor_id' => !empty($input['fornecedor_id']) ? (int)$input['fornecedor_id'] : null,
                'dados_extras' => $input['dados_extras'] ?? null
            ];
            if (empty($dados['nome'])) jsonResponse(['success' => false, 'error' => 'Nome obrigatório'], 400);
            $id = $cmdb->criarItem($dados, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Item criado!', 'id' => $id]);
            break;

        case 'atualizar_item':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $campos = ['nome','identificador','categoria_id','inventario_id','descricao','status',
                        'criticidade','ambiente','versao','ip_endereco','localizacao','responsavel_id','fornecedor_id','dados_extras'];
            $dados = [];
            foreach ($campos as $c) { if (isset($input[$c])) $dados[$c] = $input[$c]; }
            $cmdb->atualizarItem($id, $dados, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Item atualizado!']);
            break;

        case 'excluir_item':
            requireRole(['admin', 'gestor']);
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $cmdb->excluirItem($id, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Item excluído!']);
            break;

        // Relacionamentos
        case 'criar_relacionamento':
            $dados = [
                'ci_origem_id' => (int)($input['ci_origem_id'] ?? 0),
                'ci_destino_id' => (int)($input['ci_destino_id'] ?? 0),
                'tipo' => $input['tipo'] ?? 'depende_de',
                'descricao' => trim($input['descricao'] ?? '')
            ];
            if (!$dados['ci_origem_id'] || !$dados['ci_destino_id']) {
                jsonResponse(['success' => false, 'error' => 'Origem e destino obrigatórios'], 400);
            }
            if ($dados['ci_origem_id'] === $dados['ci_destino_id']) {
                jsonResponse(['success' => false, 'error' => 'Não pode relacionar consigo mesmo'], 400);
            }
            $cmdb->criarRelacionamento($dados, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Relacionamento criado!']);
            break;

        case 'excluir_relacionamento':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $cmdb->excluirRelacionamento($id, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Relacionamento removido!']);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Método não suportado'], 405);
}
