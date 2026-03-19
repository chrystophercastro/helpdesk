<?php
/**
 * API: GitHub
 * Endpoints para integração GitHub — repositórios, commits, PRs, issues, config
 */
session_start();
require_once __DIR__ . '/../config/app.php';

// Webhook endpoint (não requer autenticação)
if (isset($_GET['webhook'])) {
    header('Content-Type: application/json; charset=utf-8');
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload) {
        jsonResponse(['error' => 'Payload inválido'], 400);
    }
    require_once __DIR__ . '/../app/models/GitHub.php';
    $gh = new GitHub();
    $id = $gh->registrarWebhook($payload);
    $gh->processarWebhook($id);
    jsonResponse(['success' => true, 'webhook_id' => $id]);
}

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/GitHub.php';
$gh = new GitHub();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

try {
    // ==========================================
    //  GET
    // ==========================================
    if ($method === 'GET') {
        switch ($action) {

            case 'status':
                $config = $gh->getConfig($_SESSION['usuario_id']);
                $connected = false;
                $user = null;
                if ($config) {
                    try {
                        $user = $gh->getUser();
                        $connected = true;
                    } catch (\Exception $e) {
                        $connected = false;
                    }
                }
                $stats = $gh->getStats();
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'connected' => $connected,
                        'user' => $user,
                        'config' => $config ? [
                            'id' => $config['id'],
                            'username' => $config['username'],
                            'avatar_url' => $config['avatar_url'],
                            'ultimo_uso' => $config['ultimo_uso'],
                        ] : null,
                        'stats' => $stats,
                    ]
                ]);
                break;

            case 'repos_remoto':
                $repos = $gh->listarReposRemoto(
                    $_GET['tipo'] ?? 'all',
                    $_GET['sort'] ?? 'updated'
                );
                jsonResponse(['success' => true, 'data' => $repos]);
                break;

            case 'repos_vinculados':
                $projetoId = $_GET['projeto_id'] ?? null;
                $repos = $gh->listarReposVinculados($projetoId);
                jsonResponse(['success' => true, 'data' => $repos]);
                break;

            case 'repo_overview':
                if (empty($_GET['repo_id'])) jsonResponse(['error' => 'repo_id obrigatório'], 400);
                $overview = $gh->getRepoOverview($_GET['repo_id']);
                jsonResponse(['success' => true, 'data' => $overview]);
                break;

            case 'commits':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $branch = $_GET['branch'] ?? null;
                $commits = $gh->getCommits($_GET['owner'], $_GET['repo'], $branch, (int)($_GET['limit'] ?? 30));
                jsonResponse(['success' => true, 'data' => $commits]);
                break;

            case 'commits_local':
                if (empty($_GET['repo_id'])) jsonResponse(['error' => 'repo_id obrigatório'], 400);
                $commits = $gh->getCommitsLocal($_GET['repo_id'], (int)($_GET['limit'] ?? 50));
                jsonResponse(['success' => true, 'data' => $commits]);
                break;

            case 'branches':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $branches = $gh->getBranches($_GET['owner'], $_GET['repo']);
                jsonResponse(['success' => true, 'data' => $branches]);
                break;

            case 'pull_requests':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $state = $_GET['state'] ?? 'open';
                $prs = $gh->getPullRequests($_GET['owner'], $_GET['repo'], $state);
                jsonResponse(['success' => true, 'data' => $prs]);
                break;

            case 'pull_request':
                if (empty($_GET['owner']) || empty($_GET['repo']) || empty($_GET['number'])) {
                    jsonResponse(['error' => 'owner, repo e number obrigatórios'], 400);
                }
                $pr = $gh->getPullRequest($_GET['owner'], $_GET['repo'], $_GET['number']);
                $files = $gh->getPullRequestFiles($_GET['owner'], $_GET['repo'], $_GET['number']);
                jsonResponse(['success' => true, 'data' => ['pr' => $pr, 'files' => $files]]);
                break;

            case 'issues':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $state = $_GET['state'] ?? 'open';
                $issues = $gh->getIssues($_GET['owner'], $_GET['repo'], $state);
                jsonResponse(['success' => true, 'data' => $issues]);
                break;

            case 'releases':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $releases = $gh->getReleases($_GET['owner'], $_GET['repo']);
                jsonResponse(['success' => true, 'data' => $releases]);
                break;

            case 'workflows':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $runs = $gh->getWorkflowRuns($_GET['owner'], $_GET['repo']);
                jsonResponse(['success' => true, 'data' => $runs]);
                break;

            case 'readme':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $readme = $gh->getReadme($_GET['owner'], $_GET['repo']);
                jsonResponse(['success' => true, 'data' => $readme]);
                break;

            case 'contributors':
                if (empty($_GET['owner']) || empty($_GET['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $contributors = $gh->getContributors($_GET['owner'], $_GET['repo']);
                jsonResponse(['success' => true, 'data' => $contributors]);
                break;

            case 'search':
                if (empty($_GET['q'])) jsonResponse(['error' => 'Query obrigatória'], 400);
                $repos = $gh->searchRepos($_GET['q']);
                jsonResponse(['success' => true, 'data' => $repos]);
                break;

            case 'projetos':
                $projetos = $gh->listarProjetos();
                jsonResponse(['success' => true, 'data' => $projetos]);
                break;

            default:
                jsonResponse(['error' => 'Ação GET não reconhecida: ' . $action], 400);
        }
    }

    // ==========================================
    //  POST
    // ==========================================
    elseif ($method === 'POST') {
        switch ($action) {

            case 'salvar_config':
                if (empty($input['token'])) {
                    jsonResponse(['error' => 'Token obrigatório'], 400);
                }
                $id = $gh->salvarConfig([
                    'usuario_id' => $_SESSION['usuario_id'],
                    'token' => $input['token'],
                ]);
                $user = $gh->getUser();
                jsonResponse([
                    'success' => true,
                    'message' => 'Token salvo e validado com sucesso!',
                    'data' => ['user' => $user]
                ]);
                break;

            case 'remover_config':
                $gh->removerConfig($_SESSION['usuario_id']);
                jsonResponse(['success' => true, 'message' => 'Configuração removida']);
                break;

            case 'vincular_repo':
                if (empty($input['owner']) || empty($input['repo'])) {
                    jsonResponse(['error' => 'owner e repo obrigatórios'], 400);
                }
                $id = $gh->vincularRepo([
                    'owner' => $input['owner'],
                    'repo' => $input['repo'],
                    'projeto_id' => $input['projeto_id'] ?? null,
                ]);
                // Sincronizar commits
                $gh->syncCommits($id);
                jsonResponse(['success' => true, 'message' => 'Repositório vinculado!', 'data' => ['id' => $id]]);
                break;

            case 'desvincular_repo':
                if (empty($input['id'])) jsonResponse(['error' => 'ID obrigatório'], 400);
                $gh->desvincularRepo($input['id']);
                jsonResponse(['success' => true, 'message' => 'Repositório desvinculado']);
                break;

            case 'atualizar_projeto':
                if (empty($input['repo_id'])) jsonResponse(['error' => 'repo_id obrigatório'], 400);
                $gh->atualizarVinculoProjeto($input['repo_id'], $input['projeto_id'] ?? null);
                jsonResponse(['success' => true, 'message' => 'Vínculo atualizado']);
                break;

            case 'sync_repo':
                if (empty($input['repo_id'])) jsonResponse(['error' => 'repo_id obrigatório'], 400);
                $gh->syncRepo($input['repo_id']);
                jsonResponse(['success' => true, 'message' => 'Repositório sincronizado!']);
                break;

            case 'criar_issue':
                if (empty($input['owner']) || empty($input['repo']) || empty($input['title'])) {
                    jsonResponse(['error' => 'owner, repo e title obrigatórios'], 400);
                }
                $issue = $gh->criarIssue($input['owner'], $input['repo'], [
                    'title' => $input['title'],
                    'body' => $input['body'] ?? '',
                    'labels' => $input['labels'] ?? [],
                ]);
                jsonResponse(['success' => true, 'message' => 'Issue criada!', 'data' => $issue]);
                break;

            default:
                jsonResponse(['error' => 'Ação POST não reconhecida: ' . $action], 400);
        }
    }
    else {
        jsonResponse(['error' => 'Método não suportado'], 405);
    }

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
