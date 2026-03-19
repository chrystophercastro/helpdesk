<?php
/**
 * Model: GitHub
 * Cliente para API GitHub v3 (REST) — repositórios, commits, branches, PRs, issues, webhooks
 */

class GitHub {
    private $db;
    private $apiBase = 'https://api.github.com';

    // Criptografia para tokens
    private const CIPHER_METHOD = 'aes-256-cbc';
    private const ENC_SALT = 'helpdesk_github_enc_2024!';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ==========================================
    //  CRIPTOGRAFIA
    // ==========================================
    private function encrypt($data) {
        if (empty($data)) return '';
        $key = hash('sha256', self::ENC_SALT, true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, self::CIPHER_METHOD, $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    private function decrypt($data) {
        if (empty($data)) return '';
        $data = base64_decode($data);
        $parts = explode('::', $data, 2);
        if (count($parts) !== 2) return '';
        $key = hash('sha256', self::ENC_SALT, true);
        return openssl_decrypt($parts[1], self::CIPHER_METHOD, $key, 0, $parts[0]);
    }

    // ==========================================
    //  CONFIG / TOKEN
    // ==========================================
    public function getConfig($usuarioId = null) {
        if ($usuarioId) {
            $config = $this->db->fetch(
                "SELECT * FROM github_config WHERE usuario_id = ? AND ativo = 1",
                [$usuarioId]
            );
            if ($config) return $config;
        }
        // Fallback: configuração global (usuario_id IS NULL)
        return $this->db->fetch(
            "SELECT * FROM github_config WHERE usuario_id IS NULL AND ativo = 1"
        );
    }

    public function getToken($usuarioId = null) {
        $config = $this->getConfig($usuarioId);
        if (!$config) return null;
        return $this->decrypt($config['token']);
    }

    public function salvarConfig($dados) {
        $usuarioId = $dados['usuario_id'] ?? null;
        $token = $this->encrypt($dados['token']);

        // Validar token fazendo uma chamada à API
        $userInfo = $this->apiRequest('/user', $dados['token']);
        if (!$userInfo || isset($userInfo['message'])) {
            throw new \Exception('Token inválido: ' . ($userInfo['message'] ?? 'Erro desconhecido'));
        }

        $record = [
            'token' => $token,
            'username' => $userInfo['login'] ?? null,
            'avatar_url' => $userInfo['avatar_url'] ?? null,
            'ativo' => 1,
            'ultimo_uso' => date('Y-m-d H:i:s'),
        ];

        $existing = $this->db->fetch(
            "SELECT id FROM github_config WHERE " . ($usuarioId ? "usuario_id = ?" : "usuario_id IS NULL"),
            $usuarioId ? [$usuarioId] : []
        );

        if ($existing) {
            $this->db->update('github_config', $record, 'id = ?', [$existing['id']]);
            return $existing['id'];
        } else {
            $record['usuario_id'] = $usuarioId;
            $this->db->insert('github_config', $record);
            return $this->db->lastInsertId();
        }
    }

    public function removerConfig($usuarioId = null) {
        if ($usuarioId) {
            $this->db->delete('github_config', 'usuario_id = ?', [$usuarioId]);
        } else {
            $this->db->delete('github_config', 'usuario_id IS NULL');
        }
    }

    // ==========================================
    //  API GITHUB (HTTP Client)
    // ==========================================
    private function apiRequest($endpoint, $tokenOverride = null, $method = 'GET', $body = null) {
        $token = $tokenOverride ?: $this->getToken($_SESSION['usuario_id'] ?? null);
        if (!$token) {
            throw new \Exception('Token GitHub não configurado');
        }

        $url = (strpos($endpoint, 'https://') === 0) ? $endpoint : $this->apiBase . $endpoint;

        $headers = [
            'Accept: application/vnd.github.v3+json',
            'Authorization: Bearer ' . $token,
            'User-Agent: HelpDesk-TI/1.0',
        ];

        if ($body) {
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erro de conexão com GitHub: $error");
        }

        $data = json_decode($response, true);

        if ($httpCode === 401) {
            throw new \Exception('Token GitHub expirado ou inválido');
        }
        if ($httpCode === 403) {
            throw new \Exception('Limite de requisições GitHub atingido ou sem permissão');
        }
        if ($httpCode === 404) {
            throw new \Exception('Recurso não encontrado no GitHub');
        }
        if ($httpCode >= 400) {
            $msg = $data['message'] ?? "Erro HTTP $httpCode";
            throw new \Exception("GitHub API: $msg");
        }

        // Atualizar último uso
        $uid = $_SESSION['usuario_id'] ?? null;
        if ($uid && !$tokenOverride) {
            $this->db->query(
                "UPDATE github_config SET ultimo_uso = NOW() WHERE usuario_id = ? OR (usuario_id IS NULL)",
                [$uid]
            );
        }

        return $data;
    }

    // Paginação automática
    private function apiRequestPaginated($endpoint, $perPage = 30, $maxPages = 5) {
        $results = [];
        $separator = strpos($endpoint, '?') !== false ? '&' : '?';

        for ($page = 1; $page <= $maxPages; $page++) {
            $data = $this->apiRequest($endpoint . $separator . "per_page=$perPage&page=$page");
            if (!is_array($data) || empty($data)) break;
            $results = array_merge($results, $data);
            if (count($data) < $perPage) break;
        }

        return $results;
    }

    // ==========================================
    //  USUÁRIO AUTENTICADO
    // ==========================================
    public function getUser() {
        return $this->apiRequest('/user');
    }

    public function getUserOrgs() {
        return $this->apiRequest('/user/orgs');
    }

    // ==========================================
    //  REPOSITÓRIOS
    // ==========================================
    public function listarReposRemoto($tipo = 'all', $sort = 'updated') {
        return $this->apiRequestPaginated("/user/repos?type=$tipo&sort=$sort&direction=desc", 30, 10);
    }

    public function getRepoRemoto($owner, $repo) {
        return $this->apiRequest("/repos/$owner/$repo");
    }

    public function listarReposVinculados($projetoId = null) {
        $sql = "SELECT gr.*, p.nome as projeto_nome
                FROM github_repositorios gr
                LEFT JOIN projetos p ON gr.projeto_id = p.id
                WHERE gr.ativo = 1";
        $params = [];

        if ($projetoId) {
            $sql .= " AND gr.projeto_id = ?";
            $params[] = $projetoId;
        }

        $sql .= " ORDER BY gr.updated_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function vincularRepo($dados) {
        $owner = $dados['owner'];
        $repo = $dados['repo'];

        // Buscar info do repo na API
        $repoInfo = $this->getRepoRemoto($owner, $repo);

        $record = [
            'projeto_id' => $dados['projeto_id'] ?: null,
            'owner' => $owner,
            'repo' => $repo,
            'full_name' => $repoInfo['full_name'],
            'description' => $repoInfo['description'] ?? null,
            'default_branch' => $repoInfo['default_branch'] ?? 'main',
            'private' => $repoInfo['private'] ? 1 : 0,
            'language' => $repoInfo['language'] ?? null,
            'stars' => $repoInfo['stargazers_count'] ?? 0,
            'forks' => $repoInfo['forks_count'] ?? 0,
            'open_issues' => $repoInfo['open_issues_count'] ?? 0,
            'html_url' => $repoInfo['html_url'] ?? null,
            'ultimo_sync' => date('Y-m-d H:i:s'),
            'ativo' => 1,
        ];

        // Upsert
        $existing = $this->db->fetch(
            "SELECT id FROM github_repositorios WHERE owner = ? AND repo = ?",
            [$owner, $repo]
        );

        if ($existing) {
            $this->db->update('github_repositorios', $record, 'id = ?', [$existing['id']]);
            return $existing['id'];
        } else {
            $this->db->insert('github_repositorios', $record);
            return $this->db->lastInsertId();
        }
    }

    public function desvincularRepo($id) {
        $this->db->update('github_repositorios', ['ativo' => 0], 'id = ?', [$id]);
    }

    public function atualizarVinculoProjeto($repoId, $projetoId) {
        $this->db->update('github_repositorios', [
            'projeto_id' => $projetoId ?: null
        ], 'id = ?', [$repoId]);
    }

    public function syncRepo($repoId) {
        $repoLocal = $this->db->fetch("SELECT * FROM github_repositorios WHERE id = ?", [$repoId]);
        if (!$repoLocal) throw new \Exception('Repositório não encontrado');

        $repoInfo = $this->getRepoRemoto($repoLocal['owner'], $repoLocal['repo']);

        $this->db->update('github_repositorios', [
            'description' => $repoInfo['description'] ?? null,
            'default_branch' => $repoInfo['default_branch'] ?? 'main',
            'private' => $repoInfo['private'] ? 1 : 0,
            'language' => $repoInfo['language'] ?? null,
            'stars' => $repoInfo['stargazers_count'] ?? 0,
            'forks' => $repoInfo['forks_count'] ?? 0,
            'open_issues' => $repoInfo['open_issues_count'] ?? 0,
            'ultimo_sync' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$repoId]);

        // Sincronizar commits recentes
        $this->syncCommits($repoId);

        return true;
    }

    // ==========================================
    //  COMMITS
    // ==========================================
    public function getCommits($owner, $repo, $branch = null, $perPage = 30) {
        $endpoint = "/repos/$owner/$repo/commits?per_page=$perPage";
        if ($branch) $endpoint .= "&sha=$branch";
        return $this->apiRequest($endpoint);
    }

    public function syncCommits($repoId) {
        $repoLocal = $this->db->fetch("SELECT * FROM github_repositorios WHERE id = ?", [$repoId]);
        if (!$repoLocal) return;

        $commits = $this->getCommits($repoLocal['owner'], $repoLocal['repo'], $repoLocal['default_branch'], 50);

        foreach ($commits as $c) {
            $sha = $c['sha'];
            $exists = $this->db->fetch(
                "SELECT id FROM github_commits WHERE repositorio_id = ? AND sha = ?",
                [$repoId, $sha]
            );
            if ($exists) continue;

            $this->db->insert('github_commits', [
                'repositorio_id' => $repoId,
                'sha' => $sha,
                'message' => $c['commit']['message'] ?? '',
                'author_name' => $c['commit']['author']['name'] ?? null,
                'author_email' => $c['commit']['author']['email'] ?? null,
                'author_avatar' => $c['author']['avatar_url'] ?? null,
                'author_login' => $c['author']['login'] ?? null,
                'commit_date' => date('Y-m-d H:i:s', strtotime($c['commit']['author']['date'] ?? 'now')),
                'additions' => $c['stats']['additions'] ?? 0,
                'deletions' => $c['stats']['deletions'] ?? 0,
                'url' => $c['html_url'] ?? null,
            ]);
        }
    }

    public function getCommitsLocal($repoId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM github_commits WHERE repositorio_id = ? ORDER BY commit_date DESC LIMIT ?",
            [$repoId, $limit]
        );
    }

    // ==========================================
    //  BRANCHES
    // ==========================================
    public function getBranches($owner, $repo) {
        return $this->apiRequestPaginated("/repos/$owner/$repo/branches", 30, 3);
    }

    // ==========================================
    //  PULL REQUESTS
    // ==========================================
    public function getPullRequests($owner, $repo, $state = 'open') {
        return $this->apiRequestPaginated("/repos/$owner/$repo/pulls?state=$state&sort=updated&direction=desc", 30, 3);
    }

    public function getPullRequest($owner, $repo, $number) {
        return $this->apiRequest("/repos/$owner/$repo/pulls/$number");
    }

    public function getPullRequestFiles($owner, $repo, $number) {
        return $this->apiRequest("/repos/$owner/$repo/pulls/$number/files");
    }

    // ==========================================
    //  ISSUES
    // ==========================================
    public function getIssues($owner, $repo, $state = 'open') {
        return $this->apiRequestPaginated("/repos/$owner/$repo/issues?state=$state&sort=updated&direction=desc", 30, 3);
    }

    public function getIssue($owner, $repo, $number) {
        return $this->apiRequest("/repos/$owner/$repo/issues/$number");
    }

    public function criarIssue($owner, $repo, $dados) {
        return $this->apiRequest("/repos/$owner/$repo/issues", null, 'POST', [
            'title' => $dados['title'],
            'body' => $dados['body'] ?? '',
            'labels' => $dados['labels'] ?? [],
            'assignees' => $dados['assignees'] ?? [],
        ]);
    }

    // ==========================================
    //  ACTIONS / WORKFLOWS
    // ==========================================
    public function getWorkflowRuns($owner, $repo, $perPage = 10) {
        $data = $this->apiRequest("/repos/$owner/$repo/actions/runs?per_page=$perPage");
        return $data['workflow_runs'] ?? [];
    }

    // ==========================================
    //  RELEASES
    // ==========================================
    public function getReleases($owner, $repo, $perPage = 10) {
        return $this->apiRequest("/repos/$owner/$repo/releases?per_page=$perPage");
    }

    // ==========================================
    //  CONTRIBUTORS
    // ==========================================
    public function getContributors($owner, $repo) {
        return $this->apiRequestPaginated("/repos/$owner/$repo/contributors", 30, 2);
    }

    // ==========================================
    //  LANGUAGES
    // ==========================================
    public function getLanguages($owner, $repo) {
        return $this->apiRequest("/repos/$owner/$repo/languages");
    }

    // ==========================================
    //  README
    // ==========================================
    public function getReadme($owner, $repo) {
        try {
            $data = $this->apiRequest("/repos/$owner/$repo/readme");
            if (isset($data['content'])) {
                $data['decoded_content'] = base64_decode($data['content']);
            }
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ==========================================
    //  WEBHOOKS
    // ==========================================
    public function registrarWebhook($payload) {
        $repoFullName = $payload['repository']['full_name'] ?? null;
        $eventType = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown';

        $repoLocal = null;
        if ($repoFullName) {
            $repoLocal = $this->db->fetch(
                "SELECT id FROM github_repositorios WHERE full_name = ? AND ativo = 1",
                [$repoFullName]
            );
        }

        $this->db->insert('github_webhooks', [
            'repositorio_id' => $repoLocal['id'] ?? null,
            'event_type' => $eventType,
            'action' => $payload['action'] ?? null,
            'payload' => json_encode($payload),
            'processed' => 0,
        ]);

        return $this->db->lastInsertId();
    }

    public function processarWebhook($webhookId) {
        $webhook = $this->db->fetch("SELECT * FROM github_webhooks WHERE id = ?", [$webhookId]);
        if (!$webhook || $webhook['processed']) return;

        $payload = json_decode($webhook['payload'], true);
        $event = $webhook['event_type'];
        $resultado = '';

        try {
            switch ($event) {
                case 'push':
                    // Sincronizar commits quando receber push
                    if ($webhook['repositorio_id']) {
                        $this->syncCommits($webhook['repositorio_id']);
                        $resultado = 'Commits sincronizados';
                    }
                    break;

                case 'pull_request':
                    $resultado = 'PR ' . ($payload['action'] ?? '') . ': ' . ($payload['pull_request']['title'] ?? '');
                    break;

                case 'issues':
                    $resultado = 'Issue ' . ($payload['action'] ?? '') . ': ' . ($payload['issue']['title'] ?? '');
                    break;

                default:
                    $resultado = "Evento $event recebido";
            }

            $this->db->update('github_webhooks', [
                'processed' => 1,
                'resultado' => $resultado,
            ], 'id = ?', [$webhookId]);

        } catch (\Exception $e) {
            $this->db->update('github_webhooks', [
                'processed' => 1,
                'resultado' => 'Erro: ' . $e->getMessage(),
            ], 'id = ?', [$webhookId]);
        }
    }

    // ==========================================
    //  OVERVIEW / DASHBOARD
    // ==========================================
    public function getRepoOverview($repoId) {
        $repoLocal = $this->db->fetch("SELECT * FROM github_repositorios WHERE id = ? AND ativo = 1", [$repoId]);
        if (!$repoLocal) throw new \Exception('Repositório não encontrado');

        $owner = $repoLocal['owner'];
        $repo = $repoLocal['repo'];

        $repoInfo = $this->getRepoRemoto($owner, $repo);

        // Buscar dados em paralelo (sequencial no PHP)
        $branches = $this->getBranches($owner, $repo);
        $prsOpen = $this->getPullRequests($owner, $repo, 'open');
        $issuesOpen = $this->getIssues($owner, $repo, 'open');
        $commits = $this->getCommitsLocal($repoId, 20);
        $languages = $this->getLanguages($owner, $repo);
        $contributors = $this->getContributors($owner, $repo);

        // CI/CD status
        $workflows = [];
        try {
            $workflows = $this->getWorkflowRuns($owner, $repo, 5);
        } catch (\Exception $e) {
            // Actions pode não estar habilitado
        }

        return [
            'repo' => $repoInfo,
            'local' => $repoLocal,
            'branches' => $branches,
            'branches_count' => count($branches),
            'pull_requests' => $prsOpen,
            'prs_count' => count($prsOpen),
            'issues' => $issuesOpen,
            'issues_count' => count($issuesOpen),
            'commits' => $commits,
            'languages' => $languages,
            'contributors' => array_slice($contributors, 0, 20),
            'contributors_count' => count($contributors),
            'workflows' => $workflows,
            'last_sync' => $repoLocal['ultimo_sync'],
        ];
    }

    // ==========================================
    //  SEARCH
    // ==========================================
    public function searchRepos($query, $perPage = 20) {
        $data = $this->apiRequest("/search/repositories?q=" . urlencode($query) . "&per_page=$perPage&sort=updated");
        return $data['items'] ?? [];
    }

    // ==========================================
    //  HELPERS
    // ==========================================
    public function listarProjetos() {
        return $this->db->fetchAll(
            "SELECT id, nome, status FROM projetos ORDER BY nome ASC"
        );
    }

    public function getStats() {
        $totalRepos = $this->db->fetch("SELECT COUNT(*) as total FROM github_repositorios WHERE ativo = 1");
        $totalCommits = $this->db->fetch("SELECT COUNT(*) as total FROM github_commits");
        $reposVinculados = $this->db->fetch("SELECT COUNT(*) as total FROM github_repositorios WHERE projeto_id IS NOT NULL AND ativo = 1");
        $webhooksHoje = $this->db->fetch("SELECT COUNT(*) as total FROM github_webhooks WHERE DATE(received_at) = CURDATE()");

        return [
            'total_repos' => $totalRepos['total'] ?? 0,
            'total_commits' => $totalCommits['total'] ?? 0,
            'repos_vinculados' => $reposVinculados['total'] ?? 0,
            'webhooks_hoje' => $webhooksHoje['total'] ?? 0,
        ];
    }

    public function formatTimeAgo($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) return 'agora mesmo';
        if ($diff < 3600) return floor($diff / 60) . 'min atrás';
        if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
        if ($diff < 2592000) return floor($diff / 86400) . 'd atrás';
        return date('d/m/Y', $time);
    }
}
