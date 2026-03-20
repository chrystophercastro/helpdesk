<?php
/**
 * API: Financeiro
 * Endpoints para fornecedores, NF, contas a pagar e SEFAZ
 */
ob_start(); // Capturar qualquer saída PHP (warnings/notices) para não corromper JSON
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/ModuloPermissao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

requireModulo('financeiro');

require_once __DIR__ . '/../app/models/Financeiro.php';
require_once __DIR__ . '/../app/models/Sefaz.php';
$model = new Financeiro();
$sefaz = new Sefaz();
$userId = $_SESSION['usuario_id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'multipart/form-data') !== false) {
        $input = $_POST;
    } else {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    }
    $action = $input['action'] ?? $action;
}

try {

if ($method === 'GET') {
    switch ($action) {
        // ── Fornecedores ──
        case 'fornecedores':
            $filtros = [
                'busca' => $_GET['busca'] ?? '',
                'tipo' => $_GET['tipo'] ?? ''
            ];
            jsonResponse(['success' => true, 'data' => $model->listarFornecedores($filtros)]);
            break;

        case 'fornecedor':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $f = $model->getFornecedor($id);
            if (!$f) jsonResponse(['error' => 'Fornecedor não encontrado'], 404);
            jsonResponse(['success' => true, 'data' => $f]);
            break;

        // ── Notas Fiscais ──
        case 'notas':
            $filtros = [
                'busca' => $_GET['busca'] ?? '',
                'status_manifesto' => $_GET['status_manifesto'] ?? '',
                'data_inicio' => $_GET['data_inicio'] ?? '',
                'data_fim' => $_GET['data_fim'] ?? '',
                'fornecedor_id' => $_GET['fornecedor_id'] ?? ''
            ];
            jsonResponse(['success' => true, 'data' => $model->listarNotas($filtros)]);
            break;

        case 'nota':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $n = $model->getNota($id);
            if (!$n) jsonResponse(['error' => 'Nota não encontrada'], 404);
            jsonResponse(['success' => true, 'data' => $n]);
            break;

        // ── Contas a Pagar ──
        case 'contas':
            $filtros = [
                'status' => $_GET['status'] ?? '',
                'busca' => $_GET['busca'] ?? '',
                'data_inicio' => $_GET['data_inicio'] ?? '',
                'data_fim' => $_GET['data_fim'] ?? '',
                'fornecedor_id' => $_GET['fornecedor_id'] ?? '',
                'categoria' => $_GET['categoria'] ?? ''
            ];
            jsonResponse(['success' => true, 'data' => $model->listarContas($filtros)]);
            break;

        case 'conta':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $c = $model->getConta($id);
            if (!$c) jsonResponse(['error' => 'Conta não encontrada'], 404);
            jsonResponse(['success' => true, 'data' => $c]);
            break;

        // ── Stats / Histórico ──
        case 'stats':
            jsonResponse(['success' => true, 'data' => $model->getStats()]);
            break;

        case 'historico':
            $tipo = $_GET['tipo'] ?? '';
            $refId = (int)($_GET['ref_id'] ?? 0);
            jsonResponse(['success' => true, 'data' => $model->getHistorico($tipo, $refId)]);
            break;

        // ── SEFAZ ──
        case 'sefaz_config':
            requireModulo('financeiro', 'admin');
            jsonResponse(['success' => true, 'data' => $sefaz->getConfig()]);
            break;

        case 'sefaz_status':
            jsonResponse(['success' => true, 'data' => ['ready' => $sefaz->isReady()]]);
            break;

        case 'sefaz_logs':
            $limite = (int)($_GET['limite'] ?? 50);
            jsonResponse(['success' => true, 'data' => $sefaz->getLogs($limite)]);
            break;

        case 'sefaz_testar':
            requireModulo('financeiro', 'admin');
            jsonResponse(['success' => true, 'data' => $sefaz->testarConexao()]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
}

if ($method === 'POST') {
    requireModulo('financeiro', 'escrita');

    switch ($action) {
        // ── Fornecedores ──
        case 'criar_fornecedor':
            $dados = [
                'cnpj_cpf' => sanitizar($input['cnpj_cpf'] ?? ''),
                'razao_social' => sanitizar($input['razao_social'] ?? ''),
                'nome_fantasia' => sanitizar($input['nome_fantasia'] ?? ''),
                'tipo' => $input['tipo'] ?? 'pj',
                'inscricao_estadual' => sanitizar($input['inscricao_estadual'] ?? ''),
                'email' => sanitizar($input['email'] ?? ''),
                'telefone' => sanitizar($input['telefone'] ?? ''),
                'endereco' => sanitizar($input['endereco'] ?? ''),
                'cidade' => sanitizar($input['cidade'] ?? ''),
                'uf' => sanitizar($input['uf'] ?? ''),
                'cep' => sanitizar($input['cep'] ?? ''),
                'contato' => sanitizar($input['contato'] ?? ''),
                'observacoes' => sanitizar($input['observacoes'] ?? '')
            ];
            if (empty($dados['razao_social'])) jsonResponse(['error' => 'Razão social obrigatória'], 400);
            $id = $model->criarFornecedor($dados);
            $model->registrarHistorico('fornecedor', $id, 'criado', null, null, 'Fornecedor cadastrado', $userId);
            jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            break;

        case 'atualizar_fornecedor':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            unset($input['id'], $input['action']);
            foreach (['cnpj_cpf','razao_social','nome_fantasia','inscricao_estadual','email','telefone',
                       'endereco','cidade','uf','cep','contato','observacoes'] as $campo) {
                if (isset($input[$campo])) $input[$campo] = sanitizar($input[$campo]);
            }
            $model->atualizarFornecedor($id, $input);
            $model->registrarHistorico('fornecedor', $id, 'atualizado', null, null, 'Fornecedor atualizado', $userId);
            jsonResponse(['success' => true]);
            break;

        // ── Notas Fiscais ──
        case 'criar_nota':
            $dados = [
                'chave_acesso' => sanitizar($input['chave_acesso'] ?? ''),
                'numero' => sanitizar($input['numero'] ?? ''),
                'serie' => sanitizar($input['serie'] ?? ''),
                'fornecedor_id' => (int)($input['fornecedor_id'] ?? 0),
                'data_emissao' => $input['data_emissao'] ?? null,
                'valor_total' => (float)($input['valor_total'] ?? 0),
                'valor_icms' => (float)($input['valor_icms'] ?? 0),
                'valor_ipi' => (float)($input['valor_ipi'] ?? 0),
                'valor_pis' => (float)($input['valor_pis'] ?? 0),
                'valor_cofins' => (float)($input['valor_cofins'] ?? 0),
                'natureza_operacao' => sanitizar($input['natureza_operacao'] ?? ''),
                'status_manifesto' => $input['status_manifesto'] ?? 'pendente',
                'importado_por' => $userId
            ];
            $itens = $input['itens'] ?? [];
            $dados['itens'] = $itens;
            $id = $model->criarNota($dados);
            $model->registrarHistorico('nota_fiscal', $id, 'criada', null, null, 'NF cadastrada manualmente', $userId);
            jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            break;

        case 'atualizar_nota':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            unset($input['id'], $input['action']);
            $model->atualizarNota($id, $input);
            $model->registrarHistorico('nota_fiscal', $id, 'atualizada', null, null, 'NF atualizada', $userId);
            jsonResponse(['success' => true]);
            break;

        // ── Contas a Pagar ──
        case 'criar_conta':
            $dados = [
                'nota_fiscal_id' => !empty($input['nota_fiscal_id']) ? (int)$input['nota_fiscal_id'] : null,
                'fornecedor_id' => !empty($input['fornecedor_id']) ? (int)$input['fornecedor_id'] : null,
                'descricao' => sanitizar($input['descricao'] ?? ''),
                'categoria' => sanitizar($input['categoria'] ?? ''),
                'valor' => (float)($input['valor'] ?? 0),
                'data_vencimento' => $input['data_vencimento'] ?? null,
                'codigo_barras' => sanitizar($input['codigo_barras'] ?? ''),
                'linha_digitavel' => sanitizar($input['linha_digitavel'] ?? ''),
                'nosso_numero' => sanitizar($input['nosso_numero'] ?? ''),
                'banco' => sanitizar($input['banco'] ?? ''),
                'juros' => (float)($input['juros'] ?? 0),
                'multa' => (float)($input['multa'] ?? 0),
                'desconto' => (float)($input['desconto'] ?? 0),
                'observacoes' => sanitizar($input['observacoes'] ?? ''),
                'parcela_atual' => (int)($input['parcela_atual'] ?? 1),
                'parcela_total' => (int)($input['parcela_total'] ?? 1),
                'recorrente' => (int)($input['recorrente'] ?? 0),
                'criado_por' => $userId
            ];
            if (!$dados['data_vencimento']) jsonResponse(['error' => 'Data de vencimento obrigatória'], 400);
            if (!$dados['valor']) jsonResponse(['error' => 'Valor obrigatório'], 400);

            $id = $model->criarConta($dados);
            $model->registrarHistorico('conta_pagar', $id, 'criada', null, null, 'Conta cadastrada', $userId);
            jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            break;

        case 'atualizar_conta':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            unset($input['id'], $input['action']);
            foreach (['descricao','categoria','codigo_barras','linha_digitavel','nosso_numero','banco','observacoes'] as $campo) {
                if (isset($input[$campo])) $input[$campo] = sanitizar($input[$campo]);
            }
            foreach (['valor','juros','multa','desconto'] as $campo) {
                if (isset($input[$campo])) $input[$campo] = (float)$input[$campo];
            }
            $model->atualizarConta($id, $input);
            $model->registrarHistorico('conta_pagar', $id, 'atualizada', null, null, 'Conta atualizada', $userId);
            jsonResponse(['success' => true]);
            break;

        case 'aprovar_conta':
            requireModulo('financeiro', 'admin');
            $id = (int)($input['id'] ?? 0);
            $model->aprovarConta($id, $userId);
            $model->registrarHistorico('conta_pagar', $id, 'aprovada', null, null, 'Conta aprovada para pagamento', $userId);
            jsonResponse(['success' => true]);
            break;

        case 'pagar_conta':
            requireModulo('financeiro', 'admin');
            $id = (int)($input['id'] ?? 0);
            $pagamento = [
                'data_pagamento' => $input['data_pagamento'] ?? date('Y-m-d'),
                'forma_pagamento' => $input['forma_pagamento'] ?? 'boleto',
                'comprovante' => sanitizar($input['comprovante'] ?? ''),
                'valor_pago' => isset($input['valor_pago']) ? (float)$input['valor_pago'] : null
            ];
            $model->pagarConta($id, $pagamento);
            $model->registrarHistorico('conta_pagar', $id, 'paga', null, null,
                'Pago em ' . $pagamento['data_pagamento'] . ' via ' . $pagamento['forma_pagamento'], $userId);
            jsonResponse(['success' => true]);
            break;

        // ── SEFAZ ──
        case 'sefaz_salvar_config':
            requireModulo('financeiro', 'admin');
            // Mapear nomes do frontend → colunas reais da tabela sefaz_config
            $mapa = [
                'cnpj'         => 'cnpj_empresa',
                'razao_social' => 'razao_social',
                'uf'           => 'uf',
                'ambiente'     => 'ambiente',
                'ativo'        => 'ativo'
            ];
            $cfg = [];
            foreach ($mapa as $front => $col) {
                if (isset($input[$front])) $cfg[$col] = sanitizar($input[$front]);
            }
            // Normalizar ambiente: frontend pode enviar "1"/"2" ou "producao"/"homologacao"
            if (isset($cfg['ambiente'])) {
                if ($cfg['ambiente'] === '1') $cfg['ambiente'] = 'producao';
                elseif ($cfg['ambiente'] === '2') $cfg['ambiente'] = 'homologacao';
            }
            $sefaz->salvarConfig($cfg);
            jsonResponse(['success' => true]);
            break;

        case 'sefaz_upload_certificado':
            requireModulo('financeiro', 'admin');
            if (!isset($_FILES['certificado'])) jsonResponse(['error' => 'Arquivo do certificado obrigatório'], 400);
            $senha = $input['senha_certificado'] ?? '';
            if (empty($senha)) jsonResponse(['error' => 'Senha do certificado obrigatória'], 400);
            $pfxContent = file_get_contents($_FILES['certificado']['tmp_name']);
            $result = $sefaz->uploadCertificado($pfxContent, $senha);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'sefaz_consultar':
            $nsu = $input['nsu'] ?? null;
            $result = $sefaz->consultarDFe($nsu);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'sefaz_manifestar':
            $chave = sanitizar($input['chave_acesso'] ?? '');
            $tipo = $input['tipo_manifesto'] ?? '';
            $justificativa = sanitizar($input['justificativa'] ?? '');
            if (empty($chave) || empty($tipo)) jsonResponse(['error' => 'Chave e tipo são obrigatórios'], 400);
            $tipos_validos = ['ciencia','confirmada','desconhecida','nao_realizada'];
            if (!in_array($tipo, $tipos_validos)) jsonResponse(['error' => 'Tipo de manifesto inválido'], 400);
            $result = $sefaz->manifestar($chave, $tipo, $justificativa);
            if ($result['sucesso']) {
                $model->registrarHistorico('nota_fiscal', 0, 'manifesto_' . $tipo, null, null,
                    'Manifesto ' . $tipo . ' para chave ' . $chave, $userId);
            }
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'sefaz_importar_xml':
            if (!isset($_FILES['xml'])) jsonResponse(['error' => 'Arquivo XML obrigatório'], 400);
            $xml = file_get_contents($_FILES['xml']['tmp_name']);
            $result = $sefaz->importarXML($xml);
            if ($result) {
                $model->registrarHistorico('nota_fiscal', $result, 'importada_xml', null, null, 'NF importada via XML', $userId);
            }
            jsonResponse(['success' => true, 'data' => ['id' => $result]]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
}

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
