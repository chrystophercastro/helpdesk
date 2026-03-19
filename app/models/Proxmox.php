<?php
/**
 * Model: Proxmox
 * Gestão de servidores Proxmox VE — VMs, Containers, Nodes, Storage
 * Comunica via API REST do Proxmox (porta 8006)
 */

class Proxmox {
    private $db;
    private const CIPHER_METHOD = 'aes-256-cbc';
    private const ENC_SALT = 'helpdesk_proxmox_enc_2024!';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ==========================================
    //  CRIPTOGRAFIA DE CREDENCIAIS
    // ==========================================

    private function getEncryptionKey() {
        return hash('sha256', self::ENC_SALT . __DIR__, true);
    }

    public function encrypt($plaintext) {
        if (empty($plaintext)) return '';
        $key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt($ciphertext) {
        if (empty($ciphertext)) return '';
        $raw = base64_decode($ciphertext);
        if ($raw === false || strlen($raw) < 17) return '';
        $key = $this->getEncryptionKey();
        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        return openssl_decrypt($encrypted, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv) ?: '';
    }

    // ==========================================
    //  CRUD SERVIDORES PROXMOX
    // ==========================================

    public function listarServidores() {
        return $this->db->fetchAll(
            "SELECT ps.*, u.nome AS criado_por_nome 
             FROM proxmox_servers ps 
             LEFT JOIN usuarios u ON ps.criado_por = u.id 
             ORDER BY ps.nome"
        );
    }

    public function getServidor($id) {
        $server = $this->db->fetch(
            "SELECT * FROM proxmox_servers WHERE id = ?", [$id]
        );
        if ($server) {
            $server['senha_decrypted'] = $this->decrypt($server['senha']);
            if ($server['token_secret']) {
                $server['token_secret_decrypted'] = $this->decrypt($server['token_secret']);
            }
        }
        return $server;
    }

    public function criarServidor($dados) {
        return $this->db->insert('proxmox_servers', [
            'nome' => $dados['nome'],
            'host' => $dados['host'],
            'porta' => $dados['porta'] ?? 8006,
            'usuario' => $dados['usuario'],
            'senha' => $this->encrypt($dados['senha'] ?? ''),
            'realm' => $dados['realm'] ?? 'pam',
            'token_id' => $dados['token_id'] ?? null,
            'token_secret' => !empty($dados['token_secret']) ? $this->encrypt($dados['token_secret']) : null,
            'auth_type' => $dados['auth_type'] ?? 'password',
            'verificar_ssl' => $dados['verificar_ssl'] ?? 0,
            'ativo' => 1,
            'criado_por' => $dados['criado_por'] ?? null
        ]);
    }

    public function atualizarServidor($id, $dados) {
        $update = [
            'nome' => $dados['nome'],
            'host' => $dados['host'],
            'porta' => $dados['porta'] ?? 8006,
            'usuario' => $dados['usuario'],
            'realm' => $dados['realm'] ?? 'pam',
            'auth_type' => $dados['auth_type'] ?? 'password',
            'verificar_ssl' => $dados['verificar_ssl'] ?? 0,
            'ativo' => $dados['ativo'] ?? 1
        ];

        if (!empty($dados['senha'])) {
            $update['senha'] = $this->encrypt($dados['senha']);
        }
        if (!empty($dados['token_id'])) {
            $update['token_id'] = $dados['token_id'];
        }
        if (!empty($dados['token_secret'])) {
            $update['token_secret'] = $this->encrypt($dados['token_secret']);
        }

        return $this->db->update('proxmox_servers', $update, 'id = ?', [$id]);
    }

    public function excluirServidor($id) {
        return $this->db->delete('proxmox_servers', 'id = ?', [$id]);
    }

    // ==========================================
    //  LOG DE AÇÕES
    // ==========================================

    public function registrarLog($serverId, $usuarioId, $acao, $dados = []) {
        return $this->db->insert('proxmox_logs', [
            'server_id' => $serverId,
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'vmid' => $dados['vmid'] ?? null,
            'vm_nome' => $dados['vm_nome'] ?? null,
            'node' => $dados['node'] ?? null,
            'detalhes' => $dados['detalhes'] ?? null,
            'resultado' => $dados['resultado'] ?? 'sucesso'
        ]);
    }

    public function listarLogs($serverId = null, $limit = 50) {
        $sql = "SELECT pl.*, ps.nome AS server_nome, u.nome AS usuario_nome 
                FROM proxmox_logs pl 
                LEFT JOIN proxmox_servers ps ON pl.server_id = ps.id 
                LEFT JOIN usuarios u ON pl.usuario_id = u.id";
        $params = [];
        if ($serverId) {
            $sql .= " WHERE pl.server_id = ?";
            $params[] = $serverId;
        }
        $sql .= " ORDER BY pl.criado_em DESC LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql, $params);
    }

    // ==========================================
    //  COMUNICAÇÃO COM API PROXMOX
    // ==========================================

    /**
     * Autenticar no Proxmox e obter ticket + CSRFPreventionToken
     */
    public function autenticar($serverId) {
        $server = $this->getServidor($serverId);
        if (!$server) throw new Exception('Servidor não encontrado');

        if ($server['auth_type'] === 'apitoken') {
            // API Token: não precisa autenticar, retorna direto
            return [
                'server' => $server,
                'auth_type' => 'apitoken',
                'token_id' => $server['token_id'],
                'token_secret' => $server['token_secret_decrypted'] ?? ''
            ];
        }

        // Autenticação por senha
        $url = $this->getBaseUrl($server) . '/access/ticket';
        $response = $this->apiRequest('POST', $url, [
            'username' => $server['usuario'] . '@' . $server['realm'],
            'password' => $server['senha_decrypted']
        ], null, null, $server['verificar_ssl']);

        if (!isset($response['data']['ticket'])) {
            throw new Exception('Falha na autenticação com o Proxmox: ' . json_encode($response));
        }

        return [
            'server' => $server,
            'auth_type' => 'password',
            'ticket' => $response['data']['ticket'],
            'csrf' => $response['data']['CSRFPreventionToken']
        ];
    }

    /**
     * Montar URL base do Proxmox
     */
    private function getBaseUrl($server) {
        $host = $server['host'];
        $porta = $server['porta'] ?? 8006;
        // Se não tem scheme, adicionar https
        if (strpos($host, '://') === false) {
            $host = 'https://' . $host;
        }
        return rtrim($host, '/') . ':' . $porta . '/api2/json';
    }

    /**
     * Fazer requisição à API do Proxmox
     */
    private function apiRequest($method, $url, $data = null, $ticket = null, $csrf = null, $verifySSL = false, $tokenAuth = null) {
        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        if ($tokenAuth) {
            // API Token authentication
            $headers[] = 'Authorization: PVEAPIToken=' . $tokenAuth['token_id'] . '=' . $tokenAuth['token_secret'];
        } elseif ($ticket) {
            // Cookie ticket authentication
            curl_setopt($ch, CURLOPT_COOKIE, 'PVEAuthCookie=' . $ticket);
            if ($csrf && in_array(strtoupper($method), ['POST', 'PUT', 'DELETE'])) {
                $headers[] = 'CSRFPreventionToken: ' . $csrf;
            }
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => (bool)$verifySSL,
            CURLOPT_SSL_VERIFYHOST => $verifySSL ? 2 : 0,
            CURLOPT_HTTPHEADER => $headers
        ]);

        // Build POST body (always set for POST/PUT to avoid chunked encoding)
        $postBody = $data ? http_build_query($data) : '';

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        } elseif (strtoupper($method) === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        } elseif (strtoupper($method) === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($data && strtoupper($method) === 'GET') {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Erro de conexão com Proxmox: ' . $error);
        }

        if ($httpCode >= 400) {
            $decoded = json_decode($response, true);
            $msg = $decoded['errors'] ?? $decoded['data'] ?? $response;
            throw new Exception('Proxmox API erro (' . $httpCode . '): ' . (is_array($msg) ? json_encode($msg) : $msg));
        }

        return json_decode($response, true) ?: [];
    }

    /**
     * Fazer chamada autenticada ao Proxmox
     */
    public function call($serverId, $method, $endpoint, $data = null) {
        $auth = $this->autenticar($serverId);
        $server = $auth['server'];
        $url = $this->getBaseUrl($server) . '/' . ltrim($endpoint, '/');

        $tokenAuth = null;
        $ticket = null;
        $csrf = null;

        if ($auth['auth_type'] === 'apitoken') {
            $tokenAuth = [
                'token_id' => $auth['token_id'],
                'token_secret' => $auth['token_secret']
            ];
        } else {
            $ticket = $auth['ticket'];
            $csrf = $auth['csrf'];
        }

        return $this->apiRequest($method, $url, $data, $ticket, $csrf, $server['verificar_ssl'], $tokenAuth);
    }

    // ==========================================
    //  NODES
    // ==========================================

    public function getNodes($serverId) {
        $response = $this->call($serverId, 'GET', '/nodes');
        $nodes = $response['data'] ?? [];
        // Ordenar por nome
        usort($nodes, fn($a, $b) => strcmp($a['node'] ?? '', $b['node'] ?? ''));
        return $nodes;
    }

    public function getNodeStatus($serverId, $node) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/status");
        return $response['data'] ?? [];
    }

    // ==========================================
    //  VMs (QEMU)
    // ==========================================

    public function getVMs($serverId, $node) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/qemu");
        $vms = $response['data'] ?? [];
        usort($vms, fn($a, $b) => ($a['vmid'] ?? 0) - ($b['vmid'] ?? 0));
        return $vms;
    }

    public function getVMStatus($serverId, $node, $vmid) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/qemu/{$vmid}/status/current");
        return $response['data'] ?? [];
    }

    public function getVMConfig($serverId, $node, $vmid) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/qemu/{$vmid}/config");
        return $response['data'] ?? [];
    }

    public function getVMRRDData($serverId, $node, $vmid, $timeframe = 'hour') {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/qemu/{$vmid}/rrddata", ['timeframe' => $timeframe]);
        return $response['data'] ?? [];
    }

    // Ações de VM
    public function startVM($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu/{$vmid}/status/start");
    }

    public function stopVM($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu/{$vmid}/status/stop");
    }

    public function shutdownVM($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu/{$vmid}/status/shutdown");
    }

    public function resetVM($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu/{$vmid}/status/reset");
    }

    public function suspendVM($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu/{$vmid}/status/suspend");
    }

    public function resumeVM($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu/{$vmid}/status/resume");
    }

    public function rebootVM($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu/{$vmid}/status/reboot");
    }

    // ==========================================
    //  CONTAINERS (LXC)
    // ==========================================

    public function getContainers($serverId, $node) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/lxc");
        $cts = $response['data'] ?? [];
        usort($cts, fn($a, $b) => ($a['vmid'] ?? 0) - ($b['vmid'] ?? 0));
        return $cts;
    }

    public function getContainerStatus($serverId, $node, $vmid) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/lxc/{$vmid}/status/current");
        return $response['data'] ?? [];
    }

    public function getContainerConfig($serverId, $node, $vmid) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/lxc/{$vmid}/config");
        return $response['data'] ?? [];
    }

    public function startContainer($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/lxc/{$vmid}/status/start");
    }

    public function stopContainer($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/lxc/{$vmid}/status/stop");
    }

    public function shutdownContainer($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/lxc/{$vmid}/status/shutdown");
    }

    public function rebootContainer($serverId, $node, $vmid) {
        return $this->call($serverId, 'POST', "/nodes/{$node}/lxc/{$vmid}/status/reboot");
    }

    // ==========================================
    //  SNAPSHOTS
    // ==========================================

    public function getSnapshots($serverId, $node, $vmid, $type = 'qemu') {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/{$type}/{$vmid}/snapshot");
        return $response['data'] ?? [];
    }

    public function createSnapshot($serverId, $node, $vmid, $type = 'qemu', $snapname = '', $description = '') {
        $data = [];
        if ($snapname) $data['snapname'] = $snapname;
        if ($description) $data['description'] = $description;
        if ($type === 'qemu') $data['vmstate'] = 0; // Não incluir RAM por padrão
        return $this->call($serverId, 'POST', "/nodes/{$node}/{$type}/{$vmid}/snapshot", $data);
    }

    public function deleteSnapshot($serverId, $node, $vmid, $snapname, $type = 'qemu') {
        return $this->call($serverId, 'DELETE', "/nodes/{$node}/{$type}/{$vmid}/snapshot/{$snapname}");
    }

    public function rollbackSnapshot($serverId, $node, $vmid, $snapname, $type = 'qemu') {
        return $this->call($serverId, 'POST', "/nodes/{$node}/{$type}/{$vmid}/snapshot/{$snapname}/rollback");
    }

    // ==========================================
    //  STORAGE
    // ==========================================

    public function getStorage($serverId, $node) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/storage");
        return $response['data'] ?? [];
    }

    public function getStorageContent($serverId, $node, $storage) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/storage/{$storage}/content");
        return $response['data'] ?? [];
    }

    // ==========================================
    //  TASKS
    // ==========================================

    public function getTasks($serverId, $node, $limit = 20) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/tasks", ['limit' => $limit]);
        return $response['data'] ?? [];
    }

    public function getTaskStatus($serverId, $node, $upid) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/tasks/{$upid}/status");
        return $response['data'] ?? [];
    }

    // ==========================================
    //  CONSOLE (VNC Proxy)
    // ==========================================

    public function getVNCProxy($serverId, $node, $vmid, $type = 'qemu') {
        $response = $this->call($serverId, 'POST', "/nodes/{$node}/{$type}/{$vmid}/vncproxy", [
            'websocket' => 1
        ]);
        return $response['data'] ?? [];
    }

    // ==========================================
    //  CLUSTER / OVERVIEW
    // ==========================================

    public function getClusterResources($serverId, $resourceType = null) {
        $data = $resourceType ? ['type' => $resourceType] : [];
        $response = $this->call($serverId, 'GET', '/cluster/resources', $data);
        return $response['data'] ?? [];
    }

    public function getClusterStatus($serverId) {
        $response = $this->call($serverId, 'GET', '/cluster/status');
        return $response['data'] ?? [];
    }

    // ==========================================
    //  BACKUP
    // ==========================================

    public function backupVM($serverId, $node, $vmid, $type = 'qemu', $storage = 'local', $mode = 'snapshot', $compress = 'zstd') {
        return $this->call($serverId, 'POST', "/nodes/{$node}/vzdump", [
            'vmid' => $vmid,
            'storage' => $storage,
            'mode' => $mode,
            'compress' => $compress
        ]);
    }

    // ==========================================
    //  NETWORK
    // ==========================================

    public function getNodeNetworks($serverId, $node) {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/network");
        return $response['data'] ?? [];
    }

    // ==========================================
    //  HELPERS
    // ==========================================

    /**
     * Formatar bytes para unidade legível
     */
    public static function formatBytes($bytes, $precision = 2) {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pow = floor(log($bytes) / log(1024));
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Formatar uptime em texto legível
     */
    public static function formatUptime($seconds) {
        if ($seconds <= 0) return '—';
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $mins = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = $days . 'd';
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($mins > 0) $parts[] = $mins . 'm';
        return implode(' ', $parts) ?: '< 1m';
    }

    /**
     * Obter resumo geral de um servidor (todos os recursos)
     */
    public function getServerOverview($serverId) {
        try {
            $resources = $this->getClusterResources($serverId);

            $overview = [
                'nodes' => [],
                'vms' => ['running' => 0, 'stopped' => 0, 'paused' => 0, 'total' => 0],
                'containers' => ['running' => 0, 'stopped' => 0, 'total' => 0],
                'storage' => [],
                'total_cpu' => 0,
                'total_mem' => 0,
                'total_mem_used' => 0,
                'total_disk' => 0,
                'total_disk_used' => 0
            ];

            foreach ($resources as $res) {
                switch ($res['type'] ?? '') {
                    case 'node':
                        $overview['nodes'][] = $res;
                        $overview['total_cpu'] += ($res['maxcpu'] ?? 0);
                        $overview['total_mem'] += ($res['maxmem'] ?? 0);
                        $overview['total_mem_used'] += ($res['mem'] ?? 0);
                        break;

                    case 'qemu':
                        $overview['vms']['total']++;
                        $status = $res['status'] ?? 'stopped';
                        if ($status === 'running') $overview['vms']['running']++;
                        elseif ($status === 'paused') $overview['vms']['paused']++;
                        else $overview['vms']['stopped']++;
                        break;

                    case 'lxc':
                        $overview['containers']['total']++;
                        if (($res['status'] ?? '') === 'running') $overview['containers']['running']++;
                        else $overview['containers']['stopped']++;
                        break;

                    case 'storage':
                        $overview['storage'][] = $res;
                        $overview['total_disk'] += ($res['maxdisk'] ?? 0);
                        $overview['total_disk_used'] += ($res['disk'] ?? 0);
                        break;
                }
            }

            return $overview;
        } catch (Exception $e) {
            throw new Exception('Erro ao obter overview: ' . $e->getMessage());
        }
    }

    /**
     * Testar conexão com servidor Proxmox
     */
    public function testarConexao($serverId) {
        try {
            $auth = $this->autenticar($serverId);
            $version = $this->call($serverId, 'GET', '/version');
            return [
                'conectado' => true,
                'versao' => $version['data']['version'] ?? 'Desconhecida',
                'release' => $version['data']['release'] ?? ''
            ];
        } catch (Exception $e) {
            return [
                'conectado' => false,
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Alterar configurações de uma VM (CPU, Memória, etc)
     */
    public function updateVMConfig($serverId, $node, $vmid, $config, $type = 'qemu') {
        return $this->call($serverId, 'PUT', "/nodes/{$node}/{$type}/{$vmid}/config", $config);
    }

    /**
     * Redimensionar disco de uma VM
     */
    public function resizeDisk($serverId, $node, $vmid, $disk, $size, $type = 'qemu') {
        return $this->call($serverId, 'PUT', "/nodes/{$node}/{$type}/{$vmid}/resize", [
            'disk' => $disk,
            'size' => $size
        ]);
    }

    /**
     * Clonar VM
     */
    public function cloneVM($serverId, $node, $vmid, $newid, $name = '', $full = true, $type = 'qemu') {
        $data = ['newid' => $newid, 'full' => $full ? 1 : 0];
        if ($name) $data['name'] = $name;
        return $this->call($serverId, 'POST', "/nodes/{$node}/{$type}/{$vmid}/clone", $data);
    }

    /**
     * Migrar VM para outro node
     */
    public function migrateVM($serverId, $node, $vmid, $targetNode, $online = true, $type = 'qemu') {
        return $this->call($serverId, 'POST', "/nodes/{$node}/{$type}/{$vmid}/migrate", [
            'target' => $targetNode,
            'online' => $online ? 1 : 0
        ]);
    }

    /**
     * Deletar VM
     */
    public function deleteVM($serverId, $node, $vmid, $type = 'qemu') {
        return $this->call($serverId, 'DELETE', "/nodes/{$node}/{$type}/{$vmid}");
    }

    /**
     * Obter próximo VMID disponível
     */
    public function getNextVMID($serverId) {
        $response = $this->call($serverId, 'GET', '/cluster/nextid');
        return $response['data'] ?? null;
    }

    /**
     * Criar VM
     */
    public function createVM($serverId, $node, $vmid, $config) {
        $config['vmid'] = $vmid;
        return $this->call($serverId, 'POST', "/nodes/{$node}/qemu", $config);
    }

    /**
     * Criar Container LXC
     */
    public function createContainer($serverId, $node, $vmid, $config) {
        $config['vmid'] = $vmid;
        return $this->call($serverId, 'POST', "/nodes/{$node}/lxc", $config);
    }

    /**
     * Obter templates disponíveis
     */
    public function getTemplates($serverId, $node, $storage = 'local') {
        $response = $this->call($serverId, 'GET', "/nodes/{$node}/storage/{$storage}/content", ['content' => 'vztmpl,iso']);
        return $response['data'] ?? [];
    }

    /**
     * Console noVNC URL com autenticação via vncticket
     */
    public function getConsoleUrl($serverId, $node, $vmid, $type = 'qemu') {
        $server = $this->getServidor($serverId);
        if (!$server) throw new Exception('Servidor não encontrado');

        $host = $server['host'];
        $porta = $server['porta'] ?? 8006;
        if (strpos($host, '://') !== false) {
            $parsed = parse_url($host);
            $host = $parsed['host'];
        }

        // Obter ticket de autenticação e VNC proxy
        $auth = $this->autenticar($serverId);
        $vncData = $this->getVNCProxy($serverId, $node, $vmid, $type);

        if (empty($vncData['ticket']) || empty($vncData['port'])) {
            throw new Exception('Não foi possível obter ticket VNC');
        }

        $vncTicket = urlencode($vncData['ticket']);
        $vncPort = $vncData['port'];
        $consoleType = ($type === 'qemu') ? 'kvm' : 'lxc';

        // Para autenticação por senha, usamos o PVEAuthCookie
        if ($auth['auth_type'] === 'password') {
            $pveTicket = urlencode($auth['ticket']);
            return "https://{$host}:{$porta}/" .
                "?console={$consoleType}&novnc=1&vmid={$vmid}&node={$node}" .
                "&resize=off&PVEAuthCookie={$pveTicket}";
        }

        // Para API token, abrimos noVNC direto via websocket
        return "https://{$host}:{$porta}/" .
            "?console={$consoleType}&novnc=1&vmid={$vmid}&node={$node}&resize=off";
    }
}
