<?php
/**
 * API: Folha de Pagamento
 * Endpoints para colaboradores e lançamentos de folha
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/ModuloPermissao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

requireModulo('folha_pagamento');

require_once __DIR__ . '/../app/models/FolhaPagamento.php';
$model = new FolhaPagamento();
$userId = $_SESSION['usuario_id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

try {

if ($method === 'GET') {
    switch ($action) {
        case 'colaboradores':
            $filtros = [
                'tipo_contrato' => $_GET['tipo_contrato'] ?? '',
                'status' => $_GET['status'] ?? 'ativo',
                'departamento_id' => $_GET['departamento_id'] ?? '',
                'busca' => $_GET['busca'] ?? ''
            ];
            jsonResponse(['success' => true, 'data' => $model->listarColaboradores($filtros)]);
            break;

        case 'colaborador':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $c = $model->getColaborador($id);
            if (!$c) jsonResponse(['error' => 'Colaborador não encontrado'], 404);
            jsonResponse(['success' => true, 'data' => $c]);
            break;

        case 'lancamentos':
            $filtros = [
                'competencia' => $_GET['competencia'] ?? date('Y-m'),
                'tipo_contrato' => $_GET['tipo_contrato'] ?? '',
                'status' => $_GET['status'] ?? '',
                'colaborador_id' => $_GET['colaborador_id'] ?? ''
            ];
            jsonResponse(['success' => true, 'data' => $model->listarLancamentos($filtros)]);
            break;

        case 'lancamento':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            $l = $model->getLancamento($id);
            if (!$l) jsonResponse(['error' => 'Lançamento não encontrado'], 404);
            jsonResponse(['success' => true, 'data' => $l]);
            break;

        case 'resumo':
            $competencia = $_GET['competencia'] ?? date('Y-m');
            jsonResponse(['success' => true, 'data' => $model->getResumoCompetencia($competencia)]);
            break;

        case 'stats':
            jsonResponse(['success' => true, 'data' => $model->getStatsGeral()]);
            break;

        case 'calcular_inss':
            $salario = (float)($_GET['salario'] ?? 0);
            jsonResponse(['success' => true, 'data' => ['inss' => $model->calcularINSS($salario)]]);
            break;

        case 'calcular_irrf':
            $salario = (float)($_GET['salario'] ?? 0);
            $inss = (float)($_GET['inss'] ?? 0);
            $dep = (int)($_GET['dependentes'] ?? 0);
            jsonResponse(['success' => true, 'data' => ['irrf' => $model->calcularIRRF($salario, $inss, $dep)]]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
}

if ($method === 'POST') {
    // Escrita requer nível 'escrita'
    requireModulo('folha_pagamento', 'escrita');

    switch ($action) {
        case 'criar_colaborador':
            $dados = [
                'nome_completo' => sanitizar($input['nome_completo'] ?? ''),
                'cpf' => sanitizar($input['cpf'] ?? ''),
                'rg' => sanitizar($input['rg'] ?? ''),
                'data_nascimento' => $input['data_nascimento'] ?? null,
                'email' => sanitizar($input['email'] ?? ''),
                'telefone' => sanitizar($input['telefone'] ?? ''),
                'endereco' => sanitizar($input['endereco'] ?? ''),
                'tipo_contrato' => $input['tipo_contrato'] ?? 'clt',
                'status' => 'ativo',
            ];

            if ($dados['tipo_contrato'] === 'clt') {
                $dados['ctps'] = sanitizar($input['ctps'] ?? '');
                $dados['pis_pasep'] = sanitizar($input['pis_pasep'] ?? '');
                $dados['cargo'] = sanitizar($input['cargo'] ?? '');
                $dados['departamento_id'] = !empty($input['departamento_id']) ? (int)$input['departamento_id'] : null;
                $dados['data_admissao'] = $input['data_admissao'] ?? null;
                $dados['salario_base'] = (float)($input['salario_base'] ?? 0);
                $dados['jornada_semanal'] = (int)($input['jornada_semanal'] ?? 44);
            } else {
                $dados['cnpj'] = sanitizar($input['cnpj'] ?? '');
                $dados['razao_social'] = sanitizar($input['razao_social'] ?? '');
                $dados['inscricao_municipal'] = sanitizar($input['inscricao_municipal'] ?? '');
                $dados['banco'] = sanitizar($input['banco'] ?? '');
                $dados['agencia'] = sanitizar($input['agencia'] ?? '');
                $dados['conta'] = sanitizar($input['conta'] ?? '');
                $dados['pix'] = sanitizar($input['pix'] ?? '');
                $dados['valor_hora'] = !empty($input['valor_hora']) ? (float)$input['valor_hora'] : null;
                $dados['valor_mensal'] = !empty($input['valor_mensal']) ? (float)$input['valor_mensal'] : null;
                $dados['departamento_id'] = !empty($input['departamento_id']) ? (int)$input['departamento_id'] : null;
            }

            if (empty($dados['nome_completo'])) jsonResponse(['error' => 'Nome obrigatório'], 400);
            $id = $model->criarColaborador($dados);
            jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            break;

        case 'atualizar_colaborador':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            unset($input['id'], $input['action']);
            // Sanitizar campos string
            foreach (['nome_completo','cpf','rg','email','telefone','endereco','ctps','pis_pasep','cargo',
                       'cnpj','razao_social','inscricao_municipal','banco','agencia','conta','pix','observacoes'] as $campo) {
                if (isset($input[$campo])) $input[$campo] = sanitizar($input[$campo]);
            }
            foreach (['salario_base','valor_hora','valor_mensal'] as $campo) {
                if (isset($input[$campo])) $input[$campo] = (float)$input[$campo];
            }
            $model->atualizarColaborador($id, $input);
            jsonResponse(['success' => true]);
            break;

        case 'criar_lancamento':
            $dados = [
                'colaborador_id' => (int)($input['colaborador_id'] ?? 0),
                'competencia' => $input['competencia'] ?? date('Y-m'),
                'tipo' => $input['tipo'] ?? 'mensal',
                'salario_bruto' => (float)($input['salario_bruto'] ?? 0),
                'criado_por' => $userId,
                'status' => 'rascunho'
            ];

            if (!$dados['colaborador_id']) jsonResponse(['error' => 'Colaborador obrigatório'], 400);

            // Campos numéricos opcionais
            foreach (['inss','irrf','vale_transporte','vale_alimentacao','plano_saude','faltas_desconto',
                       'outros_descontos','horas_extras','adicional_noturno','comissao','bonus',
                       'outros_adicionais','valor_nota','horas_trabalhadas','fgts'] as $campo) {
                if (isset($input[$campo])) $dados[$campo] = (float)$input[$campo];
            }
            foreach (['descricao_outros_desc','descricao_outros_adic','observacoes'] as $campo) {
                if (isset($input[$campo])) $dados[$campo] = sanitizar($input[$campo]);
            }

            $id = $model->criarLancamento($dados);
            jsonResponse(['success' => true, 'data' => ['id' => $id]]);
            break;

        case 'atualizar_lancamento':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
            unset($input['id'], $input['action']);
            foreach (['salario_bruto','inss','irrf','vale_transporte','vale_alimentacao','plano_saude',
                       'faltas_desconto','outros_descontos','horas_extras','adicional_noturno','comissao',
                       'bonus','outros_adicionais','valor_nota','horas_trabalhadas','fgts'] as $campo) {
                if (isset($input[$campo])) $input[$campo] = (float)$input[$campo];
            }
            $model->atualizarLancamento($id, $input);
            jsonResponse(['success' => true]);
            break;

        case 'gerar_folha':
            requireModulo('folha_pagamento', 'admin');
            $competencia = $input['competencia'] ?? date('Y-m');
            $tipo = $input['tipo_contrato'] ?? 'clt';
            $gerados = $model->gerarFolhaCompetencia($competencia, $tipo, $userId);
            jsonResponse(['success' => true, 'data' => ['gerados' => $gerados]]);
            break;

        case 'aprovar':
            requireModulo('folha_pagamento', 'admin');
            $id = (int)($input['id'] ?? 0);
            $model->aprovar($id, $userId);
            jsonResponse(['success' => true]);
            break;

        case 'marcar_pago':
            requireModulo('folha_pagamento', 'admin');
            $id = (int)($input['id'] ?? 0);
            $model->marcarPago(
                $id,
                $input['data_pagamento'] ?? date('Y-m-d'),
                $input['forma_pagamento'] ?? 'pix'
            );
            jsonResponse(['success' => true]);
            break;

        case 'deletar_lancamento':
            $id = (int)($input['id'] ?? 0);
            $model->deletarLancamento($id);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
}

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
