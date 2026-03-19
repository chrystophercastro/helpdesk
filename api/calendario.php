<?php
/**
 * API: Calendário
 * Endpoints para gestão de eventos
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Calendario.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$calendario = new Calendario();
$usuarioId = $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'eventos':
            $filtros = [];
            if (!empty($_GET['inicio'])) $filtros['inicio'] = $_GET['inicio'];
            if (!empty($_GET['fim'])) $filtros['fim'] = $_GET['fim'];
            if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
            $filtros['usuario_id'] = $usuarioId;
            jsonResponse(['success' => true, 'data' => $calendario->listarEventos($filtros)]);
            break;

        case 'evento':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $evento = $calendario->getEvento($id);
            if (!$evento) jsonResponse(['success' => false, 'error' => 'Evento não encontrado'], 404);
            jsonResponse(['success' => true, 'data' => $evento]);
            break;

        case 'mes':
            $ano = (int)($_GET['ano'] ?? date('Y'));
            $mes = str_pad((int)($_GET['mes'] ?? date('m')), 2, '0', STR_PAD_LEFT);
            jsonResponse(['success' => true, 'data' => $calendario->getEventosMes($ano, $mes, $usuarioId)]);
            break;

        case 'semana':
            $data = $_GET['data'] ?? date('Y-m-d');
            jsonResponse(['success' => true, 'data' => $calendario->getEventosSemana($data, $usuarioId)]);
            break;

        case 'dia':
            $data = $_GET['data'] ?? date('Y-m-d');
            jsonResponse(['success' => true, 'data' => $calendario->getEventosDia($data, $usuarioId)]);
            break;

        case 'proximos':
            $limite = (int)($_GET['limite'] ?? 5);
            jsonResponse(['success' => true, 'data' => $calendario->getProximos($limite, $usuarioId)]);
            break;

        case 'estatisticas':
            jsonResponse(['success' => true, 'data' => $calendario->getEstatisticas($usuarioId)]);
            break;

        case 'buscar':
            $termo = $_GET['q'] ?? '';
            if (!$termo) jsonResponse(['success' => false, 'error' => 'Termo necessário'], 400);
            jsonResponse(['success' => true, 'data' => $calendario->buscar($termo, $usuarioId)]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'criar':
            $dados = [
                'titulo' => $input['titulo'] ?? '',
                'descricao' => $input['descricao'] ?? null,
                'tipo' => $input['tipo'] ?? 'evento',
                'data_inicio' => $input['data_inicio'] ?? null,
                'data_fim' => $input['data_fim'] ?? null,
                'dia_inteiro' => $input['dia_inteiro'] ?? 0,
                'cor' => $input['cor'] ?? '#3B82F6',
                'local' => $input['local'] ?? null,
                'recorrencia' => $input['recorrencia'] ?? 'nenhuma',
                'recorrencia_fim' => $input['recorrencia_fim'] ?? null,
                'participantes' => $input['participantes'] ?? null,
                'notificar_antes' => $input['notificar_antes'] ?? 15,
                'criado_por' => $usuarioId
            ];
            if (empty($dados['titulo']) || empty($dados['data_inicio'])) {
                jsonResponse(['success' => false, 'error' => 'Título e data obrigatórios'], 400);
            }
            if (is_array($dados['participantes'])) {
                $dados['participantes'] = json_encode($dados['participantes']);
            }
            $calendario->criarEvento($dados);
            $id = Database::getInstance()->lastInsertId();
            jsonResponse(['success' => true, 'message' => 'Evento criado!', 'id' => $id]);
            break;

        case 'atualizar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);

            $dados = [];
            $campos = ['titulo','descricao','tipo','data_inicio','data_fim','dia_inteiro','cor','local','recorrencia','recorrencia_fim','participantes','notificar_antes'];
            foreach ($campos as $c) {
                if (isset($input[$c])) $dados[$c] = $input[$c];
            }
            if (isset($dados['participantes']) && is_array($dados['participantes'])) {
                $dados['participantes'] = json_encode($dados['participantes']);
            }
            $calendario->atualizarEvento($id, $dados);
            jsonResponse(['success' => true, 'message' => 'Evento atualizado!']);
            break;

        case 'excluir':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $calendario->excluirEvento($id);
            jsonResponse(['success' => true, 'message' => 'Evento excluído!']);
            break;

        case 'importar_prazos':
            $importados = $calendario->importarPrazos($usuarioId);
            jsonResponse(['success' => true, 'message' => "$importados prazos importados!", 'importados' => $importados]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Método não suportado'], 405);
}
