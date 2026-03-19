<?php
/**
 * API: Notificações Internas
 * Endpoints para o sistema de notificações em tempo real (bell icon)
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/NotificacaoInterna.php';
$notif = new NotificacaoInterna();
$usuarioId = $_SESSION['usuario_id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'count':
                $count = $notif->contarNaoLidas($usuarioId);
                jsonResponse(['success' => true, 'data' => ['count' => $count]]);
                break;

            case 'listar':
                $limite = (int)($_GET['limite'] ?? 30);
                $offset = (int)($_GET['offset'] ?? 0);
                $naoLidas = isset($_GET['nao_lidas']) && $_GET['nao_lidas'] === '1';
                $lista = $notif->listar($usuarioId, $limite, $offset, $naoLidas);
                $count = $notif->contarNaoLidas($usuarioId);
                jsonResponse(['success' => true, 'data' => ['notificacoes' => $lista, 'nao_lidas' => $count]]);
                break;

            case 'preferencias':
                $prefs = $notif->getPreferencias($usuarioId);
                jsonResponse(['success' => true, 'data' => $prefs]);
                break;

            case 'estatisticas':
                $stats = $notif->getEstatisticas($usuarioId);
                jsonResponse(['success' => true, 'data' => $stats]);
                break;

            default:
                jsonResponse(['error' => 'Ação GET não reconhecida'], 400);
        }
    } elseif ($method === 'POST') {
        switch ($action) {
            case 'marcar_lida':
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $notif->marcarLida($input['id'], $usuarioId);
                jsonResponse(['success' => true]);
                break;

            case 'marcar_todas_lidas':
                $notif->marcarTodasLidas($usuarioId);
                jsonResponse(['success' => true, 'message' => 'Todas marcadas como lidas']);
                break;

            case 'excluir':
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $notif->excluir($input['id'], $usuarioId);
                jsonResponse(['success' => true]);
                break;

            case 'salvar_preferencias':
                $notif->salvarPreferencias($usuarioId, $input);
                jsonResponse(['success' => true, 'message' => 'Preferências salvas']);
                break;

            case 'limpar_antigas':
                $dias = (int)($input['dias'] ?? 30);
                $notif->limparAntigas($dias);
                jsonResponse(['success' => true, 'message' => 'Notificações antigas removidas']);
                break;

            // Admin: enviar notificação para equipe
            case 'enviar_sistema':
                requireRole(['admin']);
                if (empty($input['titulo'])) jsonResponse(['error' => 'Título obrigatório'], 400);
                $destino = $input['destino'] ?? 'equipe';
                if ($destino === 'equipe') {
                    $notif->criarParaEquipe([
                        'tipo' => 'sistema',
                        'titulo' => $input['titulo'],
                        'mensagem' => $input['mensagem'] ?? '',
                        'icone' => 'fa-bullhorn',
                    ]);
                } elseif ($destino === 'todos') {
                    $usuarios = Database::getInstance()->fetchAll("SELECT id FROM usuarios WHERE ativo = 1");
                    $notif->criarParaMultiplos(array_column($usuarios, 'id'), [
                        'tipo' => 'sistema',
                        'titulo' => $input['titulo'],
                        'mensagem' => $input['mensagem'] ?? '',
                        'icone' => 'fa-bullhorn',
                    ]);
                } elseif (!empty($input['usuario_id'])) {
                    $notif->notificarSistema($input['usuario_id'], $input['titulo'], $input['mensagem'] ?? '');
                }
                jsonResponse(['success' => true, 'message' => 'Notificação enviada']);
                break;

            default:
                jsonResponse(['error' => 'Ação POST não reconhecida'], 400);
        }
    } else {
        jsonResponse(['error' => 'Método não suportado'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
