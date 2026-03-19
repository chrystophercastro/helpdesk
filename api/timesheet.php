<?php
/**
 * API: Timesheet
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Timesheet.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$ts = new Timesheet();
$usuarioId = $_SESSION['usuario_id'];
$isGestor = in_array($_SESSION['usuario_tipo'], ['admin', 'gestor']);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'overview':
            jsonResponse(['success' => true, 'data' => $ts->getOverview($usuarioId, $isGestor)]);
            break;

        case 'registros':
            $filtros = [];
            if (!$isGestor) $filtros['usuario_id'] = $usuarioId;
            elseif (!empty($_GET['usuario_id'])) $filtros['usuario_id'] = $_GET['usuario_id'];
            if (!empty($_GET['data_inicio'])) $filtros['data_inicio'] = $_GET['data_inicio'];
            if (!empty($_GET['data_fim'])) $filtros['data_fim'] = $_GET['data_fim'];
            if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
            if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
            if (!empty($_GET['chamado_id'])) $filtros['chamado_id'] = $_GET['chamado_id'];
            if (!empty($_GET['projeto_id'])) $filtros['projeto_id'] = $_GET['projeto_id'];
            jsonResponse(['success' => true, 'data' => $ts->listarRegistros($filtros)]);
            break;

        case 'registro':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            jsonResponse(['success' => true, 'data' => $ts->getRegistro($id)]);
            break;

        case 'timer_ativo':
            jsonResponse(['success' => true, 'data' => $ts->getTimerAtivo($usuarioId)]);
            break;

        case 'resumo_semanal':
            $uid = $isGestor && !empty($_GET['usuario_id']) ? $_GET['usuario_id'] : $usuarioId;
            $dataRef = $_GET['data'] ?? null;
            jsonResponse(['success' => true, 'data' => $ts->getResumoSemanal($uid, $dataRef)]);
            break;

        case 'relatorio_tipo':
            $filtros = [];
            if (!$isGestor) $filtros['usuario_id'] = $usuarioId;
            elseif (!empty($_GET['usuario_id'])) $filtros['usuario_id'] = $_GET['usuario_id'];
            if (!empty($_GET['data_inicio'])) $filtros['data_inicio'] = $_GET['data_inicio'];
            if (!empty($_GET['data_fim'])) $filtros['data_fim'] = $_GET['data_fim'];
            jsonResponse(['success' => true, 'data' => $ts->getRelatorioPorTipo($filtros)]);
            break;

        case 'relatorio_usuario':
            requireRole(['admin', 'gestor']);
            $filtros = [];
            if (!empty($_GET['data_inicio'])) $filtros['data_inicio'] = $_GET['data_inicio'];
            if (!empty($_GET['data_fim'])) $filtros['data_fim'] = $_GET['data_fim'];
            jsonResponse(['success' => true, 'data' => $ts->getRelatorioPorUsuario($filtros)]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'iniciar_timer':
            $dados = [
                'usuario_id' => $usuarioId,
                'data' => date('Y-m-d'),
                'hora_inicio' => date('H:i:s'),
                'tipo' => $input['tipo'] ?? 'chamado',
                'chamado_id' => !empty($input['chamado_id']) ? (int)$input['chamado_id'] : null,
                'projeto_id' => !empty($input['projeto_id']) ? (int)$input['projeto_id'] : null,
                'descricao' => trim($input['descricao'] ?? ''),
                'custo_hora' => floatval($input['custo_hora'] ?? 0)
            ];
            // Check for existing active timer
            $ativo = $ts->getTimerAtivo($usuarioId);
            if ($ativo) jsonResponse(['success' => false, 'error' => 'Já existe um timer ativo. Pare-o primeiro.'], 400);
            $id = $ts->criarRegistro($dados);
            jsonResponse(['success' => true, 'message' => 'Timer iniciado!', 'id' => $id]);
            break;

        case 'parar_timer':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $ts->pararTimer($id);
            jsonResponse(['success' => true, 'message' => 'Timer parado!']);
            break;

        case 'criar_registro':
            $dados = [
                'usuario_id' => $usuarioId,
                'data' => $input['data'] ?? date('Y-m-d'),
                'hora_inicio' => $input['hora_inicio'] ?? '09:00',
                'hora_fim' => $input['hora_fim'] ?? null,
                'tipo' => $input['tipo'] ?? 'chamado',
                'chamado_id' => !empty($input['chamado_id']) ? (int)$input['chamado_id'] : null,
                'projeto_id' => !empty($input['projeto_id']) ? (int)$input['projeto_id'] : null,
                'descricao' => trim($input['descricao'] ?? ''),
                'custo_hora' => floatval($input['custo_hora'] ?? 0)
            ];
            if (empty($dados['hora_inicio'])) jsonResponse(['success' => false, 'error' => 'Hora início obrigatória'], 400);
            $id = $ts->criarRegistro($dados);
            jsonResponse(['success' => true, 'message' => 'Registro criado!', 'id' => $id]);
            break;

        case 'atualizar_registro':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $campos = ['chamado_id','projeto_id','data','hora_inicio','hora_fim','descricao','tipo','custo_hora'];
            $dados = [];
            foreach ($campos as $c) { if (isset($input[$c])) $dados[$c] = $input[$c]; }
            $ts->atualizarRegistro($id, $dados);
            jsonResponse(['success' => true, 'message' => 'Registro atualizado!']);
            break;

        case 'excluir_registro':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $reg = $ts->getRegistro($id);
            if (!$reg) jsonResponse(['success' => false, 'error' => 'Registro não encontrado'], 404);
            if ($reg['usuario_id'] != $usuarioId && !$isGestor) jsonResponse(['success' => false, 'error' => 'Sem permissão'], 403);
            $ts->excluirRegistro($id);
            jsonResponse(['success' => true, 'message' => 'Registro excluído!']);
            break;

        case 'aprovar':
            requireRole(['admin', 'gestor']);
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $ts->aprovarRegistro($id, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Registro aprovado!']);
            break;

        case 'rejeitar':
            requireRole(['admin', 'gestor']);
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $ts->rejeitarRegistro($id, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Registro rejeitado!']);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Método não suportado'], 405);
}
