<?php
/**
 * API: Airflow
 * Endpoints para integração com Apache Airflow
 * Restrito a admin/tecnico
 */
session_start();
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

if (!in_array($_SESSION['usuario_tipo'], ['admin', 'tecnico'])) {
    jsonResponse(['error' => 'Acesso restrito à equipe de TI'], 403);
}

require_once __DIR__ . '/../app/models/Airflow.php';
$airflow = new Airflow();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

try {
    if ($method === 'GET') {
        switch ($action) {

            // ---- OVERVIEW / STATUS ----
            case 'overview':
                $data = $airflow->getOverview();
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'config':
                jsonResponse(['success' => true, 'data' => $airflow->getConfig()]);
                break;

            case 'test_connection':
                jsonResponse(['success' => true, 'data' => $airflow->testConnection()]);
                break;

            // ---- DAGs ----
            case 'dags':
                $limit  = (int) ($_GET['limit'] ?? 100);
                $offset = (int) ($_GET['offset'] ?? 0);
                $active = isset($_GET['active']) ? ($_GET['active'] === 'true') : null;
                $search = $_GET['search'] ?? null;
                $tags   = isset($_GET['tags']) ? explode(',', $_GET['tags']) : null;
                $data   = $airflow->getDags($limit, $offset, $active, $search, $tags);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'dag':
                $dagId = $_GET['dag_id'] ?? '';
                if (!$dagId) jsonResponse(['error' => 'dag_id obrigatório'], 400);
                $data = $airflow->getDagDetails($dagId);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            // ---- DAG RUNS ----
            case 'dag_runs':
                $dagId = $_GET['dag_id'] ?? '';
                if (!$dagId) jsonResponse(['error' => 'dag_id obrigatório'], 400);
                $limit  = (int) ($_GET['limit'] ?? 25);
                $offset = (int) ($_GET['offset'] ?? 0);
                $state  = $_GET['state'] ?? null;
                $data   = $airflow->getDagRuns($dagId, $limit, $offset, $state);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            case 'dag_run_detail':
                $dagId    = $_GET['dag_id'] ?? '';
                $dagRunId = $_GET['dag_run_id'] ?? '';
                if (!$dagId || !$dagRunId) jsonResponse(['error' => 'dag_id e dag_run_id obrigatórios'], 400);
                $run   = $airflow->getDagRunDetail($dagId, $dagRunId);
                $tasks = $airflow->getTaskInstances($dagId, $dagRunId);
                jsonResponse(['success' => true, 'data' => ['run' => $run, 'tasks' => $tasks]]);
                break;

            // ---- TASK LOG ----
            case 'task_log':
                $dagId    = $_GET['dag_id'] ?? '';
                $dagRunId = $_GET['dag_run_id'] ?? '';
                $taskId   = $_GET['task_id'] ?? '';
                $try      = (int) ($_GET['try_number'] ?? 1);
                if (!$dagId || !$dagRunId || !$taskId) jsonResponse(['error' => 'Parâmetros obrigatórios: dag_id, dag_run_id, task_id'], 400);
                $log = $airflow->getTaskLog($dagId, $dagRunId, $taskId, $try);
                jsonResponse(['success' => true, 'data' => ['log' => $log]]);
                break;

            // ---- IMPORT ERRORS ----
            case 'import_errors':
                $data = $airflow->getImportErrors();
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            // ---- CONNECTIONS ----
            case 'connections':
                $data = $airflow->getConnections();
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            // ---- VARIABLES ----
            case 'variables':
                $data = $airflow->getVariables();
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            // ---- POOLS ----
            case 'pools':
                $data = $airflow->getPools();
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            // ---- HISTÓRICO LOCAL ----
            case 'action_log':
                $limit = (int) ($_GET['limit'] ?? 50);
                $data  = $airflow->getActionLog($limit);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            // ---- FAVORITOS ----
            case 'favoritos':
                $data = $airflow->getFavoritos($_SESSION['usuario_id']);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            // ---- SNAPSHOTS ----
            case 'snapshots':
                $horas = (int) ($_GET['horas'] ?? 24);
                $data  = $airflow->getSnapshots($horas);
                jsonResponse(['success' => true, 'data' => $data]);
                break;

            default:
                jsonResponse(['error' => 'Ação GET não reconhecida: ' . $action], 400);
        }

    } elseif ($method === 'POST') {
        switch ($action) {

            // ---- CONFIGURAÇÃO ----
            case 'save_config':
                requireRole(['admin']);
                $airflow->saveConfig($input);
                jsonResponse(['success' => true, 'message' => 'Configuração salva com sucesso!']);
                break;

            // ---- TRIGGER DAG ----
            case 'trigger_dag':
                $dagId = $input['dag_id'] ?? '';
                if (!$dagId) jsonResponse(['error' => 'dag_id obrigatório'], 400);
                $conf = $input['conf'] ?? [];
                $data = $airflow->triggerDag($dagId, $conf);
                jsonResponse(['success' => true, 'data' => $data, 'message' => "DAG '{$dagId}' disparada com sucesso!"]);
                break;

            // ---- TOGGLE DAG (pause/unpause) ----
            case 'toggle_dag':
                $dagId    = $input['dag_id'] ?? '';
                $isPaused = $input['is_paused'] ?? true;
                if (!$dagId) jsonResponse(['error' => 'dag_id obrigatório'], 400);
                $data = $airflow->toggleDag($dagId, $isPaused);
                $status = $isPaused ? 'pausada' : 'ativada';
                $airflow->logAction('toggle_dag', $dagId, ['is_paused' => $isPaused]);
                jsonResponse(['success' => true, 'data' => $data, 'message' => "DAG '{$dagId}' {$status}!"]);
                break;

            // ---- CLEAR DAG RUN ----
            case 'clear_dag_run':
                $dagId    = $input['dag_id'] ?? '';
                $dagRunId = $input['dag_run_id'] ?? '';
                if (!$dagId || !$dagRunId) jsonResponse(['error' => 'dag_id e dag_run_id obrigatórios'], 400);
                $data = $airflow->clearDagRun($dagId, $dagRunId);
                jsonResponse(['success' => true, 'data' => $data, 'message' => 'DAG Run limpa e reagendada!']);
                break;

            // ---- FAVORITO ----
            case 'toggle_favorito':
                $dagId = $input['dag_id'] ?? '';
                if (!$dagId) jsonResponse(['error' => 'dag_id obrigatório'], 400);
                $added = $airflow->toggleFavorito($_SESSION['usuario_id'], $dagId);
                jsonResponse(['success' => true, 'data' => ['favorited' => $added], 
                    'message' => $added ? 'DAG adicionada aos favoritos ⭐' : 'DAG removida dos favoritos']);
                break;

            default:
                jsonResponse(['error' => 'Ação POST não reconhecida: ' . $action], 400);
        }
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
