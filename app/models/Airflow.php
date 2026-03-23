<?php
/**
 * Model: Airflow
 * Integração com Apache Airflow — gerenciamento de DAGs, execuções, logs e conexões
 * Oracle X — Módulo exclusivo TI
 */
class Airflow {
    private $db;
    private $baseUrl;
    private $username;
    private $password;
    private $timeout;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfig();
    }

    // ==========================================
    //  CONFIGURAÇÃO
    // ==========================================

    private function loadConfig() {
        $config = $this->db->fetch("SELECT * FROM configuracoes_sistema WHERE chave = 'airflow_config'");
        if ($config) {
            $data = json_decode($config['valor'], true);
            $this->baseUrl  = rtrim($data['url'] ?? '', '/');
            $this->username = $data['username'] ?? 'airflow';
            $this->password = $data['password'] ?? '';
            $this->timeout  = (int) ($data['timeout'] ?? 30);
        } else {
            $this->baseUrl  = '';
            $this->username = 'airflow';
            $this->password = '';
            $this->timeout  = 30;
        }
    }

    public function getConfig() {
        return [
            'url'      => $this->baseUrl,
            'username' => $this->username,
            'password' => str_repeat('*', min(strlen($this->password), 8)),
            'timeout'  => $this->timeout,
            'configured' => !empty($this->baseUrl),
        ];
    }

    public function saveConfig($data) {
        $config = json_encode([
            'url'      => rtrim($data['url'] ?? '', '/'),
            'username' => $data['username'] ?? 'airflow',
            'password' => $data['password'] ?? '',
            'timeout'  => (int) ($data['timeout'] ?? 30),
        ]);

        $exists = $this->db->fetch("SELECT id FROM configuracoes_sistema WHERE chave = 'airflow_config'");
        if ($exists) {
            $this->db->update('configuracoes_sistema', ['valor' => $config], 'chave = ?', ['airflow_config']);
        } else {
            $this->db->insert('configuracoes_sistema', [
                'chave'     => 'airflow_config',
                'valor'     => $config,
                'descricao' => 'Configuração do Apache Airflow',
            ]);
        }

        $this->loadConfig();
        return true;
    }

    public function testConnection() {
        if (empty($this->baseUrl)) {
            return ['online' => false, 'error' => 'URL do Airflow não configurada'];
        }
        try {
            $resp = $this->apiGet('/api/v1/health');
            $metadataDb = $resp['metadatabase']['status'] ?? 'unknown';
            $scheduler  = $resp['scheduler']['status'] ?? 'unknown';
            return [
                'online'     => ($metadataDb === 'healthy' && $scheduler === 'healthy'),
                'metadb'     => $metadataDb,
                'scheduler'  => $scheduler,
                'url'        => $this->baseUrl,
            ];
        } catch (\Exception $e) {
            return ['online' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    //  DAGs
    // ==========================================

    public function getDags($limit = 100, $offset = 0, $onlyActive = null, $search = null, $tags = null) {
        $params = "limit={$limit}&offset={$offset}&order_by=-last_parsed_time";
        if ($onlyActive !== null) {
            $params .= '&only_active=' . ($onlyActive ? 'true' : 'false');
        }
        if ($search) {
            $params .= '&dag_id_pattern=' . urlencode("%{$search}%");
        }
        if ($tags) {
            foreach ((array) $tags as $tag) {
                $params .= '&tags=' . urlencode($tag);
            }
        }

        $resp = $this->apiGet("/api/v1/dags?{$params}");
        return [
            'dags'       => $resp['dags'] ?? [],
            'total'      => $resp['total_entries'] ?? 0,
        ];
    }

    public function getDag($dagId) {
        return $this->apiGet("/api/v1/dags/" . urlencode($dagId));
    }

    public function getDagDetails($dagId) {
        $dag = $this->getDag($dagId);
        $tasks = $this->apiGet("/api/v1/dags/" . urlencode($dagId) . "/tasks");
        $runs = $this->getDagRuns($dagId, 10);

        return [
            'dag'   => $dag,
            'tasks' => $tasks['tasks'] ?? [],
            'runs'  => $runs['dag_runs'] ?? [],
            'total_runs' => $runs['total'] ?? 0,
        ];
    }

    public function toggleDag($dagId, $isPaused) {
        return $this->apiPatch("/api/v1/dags/" . urlencode($dagId), [
            'is_paused' => $isPaused,
        ]);
    }

    public function triggerDag($dagId, $conf = [], $logicalDate = null) {
        $body = ['conf' => (object) $conf];
        if ($logicalDate) {
            $body['logical_date'] = $logicalDate;
        }
        $result = $this->apiPost("/api/v1/dags/" . urlencode($dagId) . "/dagRuns", $body);

        // Registrar no histórico local
        $this->logAction('trigger_dag', $dagId, [
            'dag_run_id' => $result['dag_run_id'] ?? null,
            'conf' => $conf,
        ]);

        return $result;
    }

    // ==========================================
    //  DAG RUNS
    // ==========================================

    public function getDagRuns($dagId, $limit = 25, $offset = 0, $state = null) {
        $params = "limit={$limit}&offset={$offset}&order_by=-execution_date";
        if ($state) {
            $params .= '&state=' . urlencode($state);
        }
        $resp = $this->apiGet("/api/v1/dags/" . urlencode($dagId) . "/dagRuns?{$params}");
        return [
            'dag_runs' => $resp['dag_runs'] ?? [],
            'total'    => $resp['total_entries'] ?? 0,
        ];
    }

    public function getDagRunDetail($dagId, $dagRunId) {
        return $this->apiGet("/api/v1/dags/" . urlencode($dagId) . "/dagRuns/" . urlencode($dagRunId));
    }

    public function getTaskInstances($dagId, $dagRunId) {
        $resp = $this->apiGet("/api/v1/dags/" . urlencode($dagId) . "/dagRuns/" . urlencode($dagRunId) . "/taskInstances");
        return $resp['task_instances'] ?? [];
    }

    public function clearDagRun($dagId, $dagRunId) {
        $result = $this->apiPost("/api/v1/dags/" . urlencode($dagId) . "/dagRuns/" . urlencode($dagRunId) . "/clear", [
            'dry_run' => false,
        ]);
        $this->logAction('clear_dag_run', $dagId, ['dag_run_id' => $dagRunId]);
        return $result;
    }

    // ==========================================
    //  TASK LOGS
    // ==========================================

    public function getTaskLog($dagId, $dagRunId, $taskId, $tryNumber = 1) {
        $url = "/api/v1/dags/" . urlencode($dagId) 
             . "/dagRuns/" . urlencode($dagRunId) 
             . "/taskInstances/" . urlencode($taskId) 
             . "/logs/" . (int) $tryNumber;
        return $this->apiGet($url, true); // retorna texto
    }

    // ==========================================
    //  IMPORT ERRORS
    // ==========================================

    public function getImportErrors($limit = 50) {
        $resp = $this->apiGet("/api/v1/importErrors?limit={$limit}&order_by=-timestamp");
        return [
            'errors' => $resp['import_errors'] ?? [],
            'total'  => $resp['total_entries'] ?? 0,
        ];
    }

    // ==========================================
    //  CONNECTIONS
    // ==========================================

    public function getConnections($limit = 100) {
        $resp = $this->apiGet("/api/v1/connections?limit={$limit}&order_by=connection_id");
        return [
            'connections' => $resp['connections'] ?? [],
            'total'       => $resp['total_entries'] ?? 0,
        ];
    }

    public function getConnection($connId) {
        return $this->apiGet("/api/v1/connections/" . urlencode($connId));
    }

    // ==========================================
    //  VARIABLES
    // ==========================================

    public function getVariables($limit = 100) {
        $resp = $this->apiGet("/api/v1/variables?limit={$limit}&order_by=key");
        return [
            'variables' => $resp['variables'] ?? [],
            'total'     => $resp['total_entries'] ?? 0,
        ];
    }

    // ==========================================
    //  POOLS
    // ==========================================

    public function getPools() {
        $resp = $this->apiGet("/api/v1/pools?limit=100");
        return $resp['pools'] ?? [];
    }

    // ==========================================
    //  OVERVIEW / STATS
    // ==========================================

    public function getOverview() {
        $health = $this->testConnection();

        $stats = [
            'health'     => $health,
            'dags_total' => 0,
            'dags_active' => 0,
            'dags_paused' => 0,
            'runs_running' => 0,
            'runs_failed_24h' => 0,
            'import_errors' => 0,
        ];

        if (!$health['online']) return $stats;

        try {
            // Total DAGs
            $dagsResp = $this->apiGet("/api/v1/dags?limit=1&only_active=false");
            $stats['dags_total'] = $dagsResp['total_entries'] ?? 0;

            // Active DAGs
            $activeResp = $this->apiGet("/api/v1/dags?limit=1&only_active=true");
            $stats['dags_active'] = $activeResp['total_entries'] ?? 0;
            $stats['dags_paused'] = $stats['dags_total'] - $stats['dags_active'];

            // Recent failed runs (last 24h)
            $since = gmdate('Y-m-d\TH:i:s\Z', time() - 86400);
            $failedResp = $this->apiGet("/api/v1/dags/~/dagRuns?limit=1&state=failed&start_date_gte=" . urlencode($since));
            $stats['runs_failed_24h'] = $failedResp['total_entries'] ?? 0;

            // Running runs
            $runningResp = $this->apiGet("/api/v1/dags/~/dagRuns?limit=1&state=running");
            $stats['runs_running'] = $runningResp['total_entries'] ?? 0;

            // Import errors
            $errResp = $this->apiGet("/api/v1/importErrors?limit=1");
            $stats['import_errors'] = $errResp['total_entries'] ?? 0;

        } catch (\Exception $e) {
            // Partial stats are OK
        }

        // Salvar snapshot para histórico local
        $this->saveSnapshot($stats);

        return $stats;
    }

    // ==========================================
    //  HISTÓRICO LOCAL (DB)
    // ==========================================

    public function logAction($acao, $dagId = null, $detalhes = []) {
        $this->db->insert('airflow_log', [
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'acao'        => $acao,
            'dag_id'      => $dagId,
            'detalhes'    => json_encode($detalhes),
            'criado_em'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function getActionLog($limit = 50) {
        return $this->db->fetchAll(
            "SELECT al.*, u.nome AS usuario_nome 
             FROM airflow_log al 
             LEFT JOIN usuarios u ON al.usuario_id = u.id 
             ORDER BY al.criado_em DESC 
             LIMIT ?",
            [$limit]
        );
    }

    private function saveSnapshot($stats) {
        $this->db->insert('airflow_snapshots', [
            'dags_total'      => $stats['dags_total'],
            'dags_active'     => $stats['dags_active'],
            'runs_running'    => $stats['runs_running'],
            'runs_failed_24h' => $stats['runs_failed_24h'],
            'import_errors'   => $stats['import_errors'],
            'criado_em'       => date('Y-m-d H:i:s'),
        ]);

        // Manter apenas últimas 24h de snapshots
        $this->db->query("DELETE FROM airflow_snapshots WHERE criado_em < NOW() - INTERVAL 24 HOUR");
    }

    public function getSnapshots($horas = 24) {
        return $this->db->fetchAll(
            "SELECT * FROM airflow_snapshots WHERE criado_em >= NOW() - INTERVAL ? HOUR ORDER BY criado_em",
            [$horas]
        );
    }

    // ==========================================
    //  FAVORITOS / DAGs fixados
    // ==========================================

    public function getFavoritos($userId) {
        return $this->db->fetchAll(
            "SELECT dag_id FROM airflow_favoritos WHERE usuario_id = ? ORDER BY criado_em",
            [$userId]
        );
    }

    public function toggleFavorito($userId, $dagId) {
        $exists = $this->db->fetch(
            "SELECT id FROM airflow_favoritos WHERE usuario_id = ? AND dag_id = ?",
            [$userId, $dagId]
        );
        if ($exists) {
            $this->db->delete('airflow_favoritos', 'id = ?', [$exists['id']]);
            return false; // removed
        }
        $this->db->insert('airflow_favoritos', [
            'usuario_id' => $userId,
            'dag_id'     => $dagId,
            'criado_em'  => date('Y-m-d H:i:s'),
        ]);
        return true; // added
    }

    // ==========================================
    //  HTTP HELPERS (Airflow REST API)
    // ==========================================

    private function apiGet($endpoint, $rawText = false) {
        return $this->apiRequest('GET', $endpoint, null, $rawText);
    }

    private function apiPost($endpoint, $data) {
        return $this->apiRequest('POST', $endpoint, $data);
    }

    private function apiPatch($endpoint, $data) {
        return $this->apiRequest('PATCH', $endpoint, $data);
    }

    private function apiDelete($endpoint) {
        return $this->apiRequest('DELETE', $endpoint);
    }

    private function apiRequest($method, $endpoint, $data = null, $rawText = false) {
        if (empty($this->baseUrl)) {
            throw new \Exception('Airflow não configurado. Configure a URL nas configurações.');
        }

        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erro de conexão com Airflow: {$error}");
        }

        if ($httpCode >= 400) {
            $errBody = json_decode($response, true);
            $errMsg  = $errBody['detail'] ?? $errBody['title'] ?? "HTTP {$httpCode}";
            throw new \Exception("Airflow API erro ({$httpCode}): {$errMsg}");
        }

        if ($rawText) {
            return $response;
        }

        return json_decode($response, true) ?: [];
    }
}
