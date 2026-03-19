<?php
/**
 * API: Contratos e Fornecedores
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Contrato.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

requireRole(['admin', 'gestor']);

$contrato = new Contrato();
$usuarioId = $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'overview':
            jsonResponse(['success' => true, 'data' => $contrato->getOverview()]);
            break;

        case 'fornecedores':
            $filtros = ['busca' => $_GET['busca'] ?? ''];
            jsonResponse(['success' => true, 'data' => $contrato->listarFornecedores($filtros)]);
            break;

        case 'fornecedor':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            jsonResponse(['success' => true, 'data' => $contrato->getFornecedor($id)]);
            break;

        case 'contratos':
            $filtros = [];
            if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
            if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
            if (!empty($_GET['fornecedor_id'])) $filtros['fornecedor_id'] = $_GET['fornecedor_id'];
            if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
            jsonResponse(['success' => true, 'data' => $contrato->listarContratos($filtros)]);
            break;

        case 'contrato':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            jsonResponse(['success' => true, 'data' => $contrato->getContrato($id)]);
            break;

        case 'vencendo':
            $dias = (int)($_GET['dias'] ?? 30);
            jsonResponse(['success' => true, 'data' => $contrato->getContratosVencendo($dias)]);
            break;

        case 'vencidos':
            jsonResponse(['success' => true, 'data' => $contrato->getContratosVencidos()]);
            break;

        case 'ativos_sem_contrato':
            jsonResponse(['success' => true, 'data' => $contrato->getAtivosSemContrato()]);
            break;

        case 'garantia_ativo':
            $id = (int)($_GET['ativo_id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID do ativo necessário'], 400);
            jsonResponse(['success' => true, 'data' => $contrato->getGarantiaAtivo($id)]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        // === FORNECEDORES ===
        case 'criar_fornecedor':
            $dados = [
                'nome' => trim($input['nome'] ?? ''),
                'cnpj' => trim($input['cnpj'] ?? ''),
                'contato_nome' => trim($input['contato_nome'] ?? ''),
                'contato_email' => trim($input['contato_email'] ?? ''),
                'contato_telefone' => trim($input['contato_telefone'] ?? ''),
                'endereco' => trim($input['endereco'] ?? ''),
                'website' => trim($input['website'] ?? ''),
                'observacoes' => trim($input['observacoes'] ?? ''),
                'criado_por' => $usuarioId
            ];
            if (empty($dados['nome'])) jsonResponse(['success' => false, 'error' => 'Nome obrigatório'], 400);
            $contrato->criarFornecedor($dados);
            jsonResponse(['success' => true, 'message' => 'Fornecedor criado!']);
            break;

        case 'atualizar_fornecedor':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $campos = ['nome','cnpj','contato_nome','contato_email','contato_telefone','endereco','website','observacoes'];
            $dados = [];
            foreach ($campos as $c) { if (isset($input[$c])) $dados[$c] = trim($input[$c]); }
            $contrato->atualizarFornecedor($id, $dados);
            jsonResponse(['success' => true, 'message' => 'Fornecedor atualizado!']);
            break;

        case 'excluir_fornecedor':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $contrato->excluirFornecedor($id);
            jsonResponse(['success' => true, 'message' => 'Fornecedor excluído!']);
            break;

        // === CONTRATOS ===
        case 'criar_contrato':
            $dados = [
                'fornecedor_id' => (int)($input['fornecedor_id'] ?? 0),
                'numero' => trim($input['numero'] ?? ''),
                'titulo' => trim($input['titulo'] ?? ''),
                'descricao' => trim($input['descricao'] ?? ''),
                'tipo' => $input['tipo'] ?? 'servico',
                'valor' => floatval($input['valor'] ?? 0),
                'recorrencia' => $input['recorrencia'] ?? 'mensal',
                'data_inicio' => $input['data_inicio'] ?? date('Y-m-d'),
                'data_fim' => $input['data_fim'] ?? null,
                'data_renovacao' => $input['data_renovacao'] ?? null,
                'auto_renovar' => (int)($input['auto_renovar'] ?? 0),
                'alerta_dias' => (int)($input['alerta_dias'] ?? 30),
                'observacoes' => trim($input['observacoes'] ?? ''),
                'ativos' => $input['ativos'] ?? [],
                'criado_por' => $usuarioId
            ];
            if (empty($dados['titulo']) || !$dados['fornecedor_id']) {
                jsonResponse(['success' => false, 'error' => 'Título e fornecedor obrigatórios'], 400);
            }
            $id = $contrato->criarContrato($dados);
            jsonResponse(['success' => true, 'message' => 'Contrato criado!', 'id' => $id]);
            break;

        case 'atualizar_contrato':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $campos = ['fornecedor_id','numero','titulo','descricao','tipo','valor','recorrencia','data_inicio','data_fim','data_renovacao','auto_renovar','alerta_dias','status','observacoes','ativos'];
            $dados = [];
            foreach ($campos as $c) { if (isset($input[$c])) $dados[$c] = $input[$c]; }
            $contrato->atualizarContrato($id, $dados);
            jsonResponse(['success' => true, 'message' => 'Contrato atualizado!']);
            break;

        case 'excluir_contrato':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $contrato->excluirContrato($id);
            jsonResponse(['success' => true, 'message' => 'Contrato excluído!']);
            break;

        case 'atualizar_vencidos':
            $contrato->atualizarStatusVencidos();
            jsonResponse(['success' => true, 'message' => 'Status atualizado!']);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Método não suportado'], 405);
}
