<?php
/**
 * API: SLA Dashboard
 * Endpoints para métricas avançadas de SLA
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/SLADashboard.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

requireRole(['admin', 'gestor']);

$sla = new SLADashboard();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'overview':
            jsonResponse(['success' => true, 'data' => $sla->getOverview()]);
            break;

        case 'semaforo':
            jsonResponse(['success' => true, 'data' => $sla->getSemaforo()]);
            break;

        case 'em_risco':
            $limite = (int)($_GET['limite'] ?? 20);
            jsonResponse(['success' => true, 'data' => $sla->getChamadosEmRisco($limite)]);
            break;

        case 'mttr':
            $dimensao = $_GET['dimensao'] ?? 'prioridade';
            $dias = (int)($_GET['dias'] ?? 30);
            jsonResponse(['success' => true, 'data' => $sla->getMTTR($dimensao, $dias)]);
            break;

        case 'tendencia':
            $meses = (int)($_GET['meses'] ?? 6);
            jsonResponse(['success' => true, 'data' => $sla->getTendencia($meses)]);
            break;

        case 'violacoes':
            $limite = (int)($_GET['limite'] ?? 10);
            jsonResponse(['success' => true, 'data' => $sla->getTopViolacoes($limite)]);
            break;

        case 'compliance_tecnico':
            jsonResponse(['success' => true, 'data' => $sla->getCompliancePorTecnico()]);
            break;

        case 'regras':
            jsonResponse(['success' => true, 'data' => $sla->getRegras()]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Método não suportado'], 405);
}
