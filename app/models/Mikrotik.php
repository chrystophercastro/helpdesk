<?php
/**
 * Model: MikroTik
 * Gerenciamento de dispositivos MikroTik RouterOS via API
 * Suporta: System, Interfaces, NAT, Firewall, DHCP, Queues, ARP, Logs
 */
class Mikrotik {

    private $db;
    private $socket = null;
    private const CIPHER_METHOD = 'aes-256-cbc';
    private const ENC_SALT = 'helpdesk_mikrotik_enc_2024!';

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
        $decoded = base64_decode($data);
        $parts = explode('::', $decoded, 2);
        if (count($parts) !== 2) return '';
        $iv = $parts[0];
        $encrypted = $parts[1];
        $key = hash('sha256', self::ENC_SALT, true);
        return openssl_decrypt($encrypted, self::CIPHER_METHOD, $key, 0, $iv);
    }

    // ==========================================
    //  CRUD DE DISPOSITIVOS
    // ==========================================
    public function listarDispositivos() {
        return $this->db->fetchAll("SELECT id, nome, descricao, host, porta, usuario, use_ssl, ativo, ultimo_acesso, created_at FROM mikrotik_devices ORDER BY nome");
    }

    public function getDispositivo($id) {
        return $this->db->fetch("SELECT * FROM mikrotik_devices WHERE id = ?", [$id]);
    }

    public function criarDispositivo($dados) {
        $this->db->insert('mikrotik_devices', [
            'nome'      => $dados['nome'],
            'descricao' => $dados['descricao'] ?? '',
            'host'      => $dados['host'],
            'porta'     => $dados['porta'] ?? 8728,
            'usuario'   => $dados['usuario'],
            'senha'     => $this->encrypt($dados['senha']),
            'use_ssl'   => $dados['use_ssl'] ?? 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function atualizarDispositivo($id, $dados) {
        $update = [
            'nome'      => $dados['nome'],
            'descricao' => $dados['descricao'] ?? '',
            'host'      => $dados['host'],
            'porta'     => $dados['porta'] ?? 8728,
            'usuario'   => $dados['usuario'],
            'use_ssl'   => $dados['use_ssl'] ?? 0,
        ];
        if (!empty($dados['senha'])) {
            $update['senha'] = $this->encrypt($dados['senha']);
        }
        $this->db->update('mikrotik_devices', $update, 'id = ?', [$id]);
    }

    public function excluirDispositivo($id) {
        $this->db->delete('mikrotik_devices', 'id = ?', [$id]);
    }

    // ==========================================
    //  CONEXÃO API RouterOS
    // ==========================================

    /**
     * Conectar ao dispositivo via API RouterOS (porta 8728/8729)
     */
    private function connect($device) {
        $host = $device['host'];
        $port = (int)$device['porta'];
        $timeout = 5;

        if ($device['use_ssl']) {
            $context = stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
            $this->socket = @stream_socket_client(
                "ssl://{$host}:{$port}", $errno, $errstr, $timeout,
                STREAM_CLIENT_CONNECT, $context
            );
        } else {
            $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        }

        if (!$this->socket) {
            // Windows retorna errstr em encoding local (CP1252/ISO-8859-1), converter para UTF-8
            if ($errstr && !mb_check_encoding($errstr, 'UTF-8')) {
                $errstr = mb_convert_encoding($errstr, 'UTF-8', 'Windows-1252');
            }
            throw new \Exception("Não foi possível conectar ao MikroTik {$host}:{$port} — {$errstr}");
        }

        stream_set_timeout($this->socket, 10);
    }

    /**
     * Desconectar
     */
    private function disconnect() {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Login via API RouterOS (suporta método novo post-6.43 e legado)
     */
    private function login($username, $password) {
        // Tentar login novo (post-6.43) primeiro
        $this->write('/login', false);
        $this->write('=name=' . $username, false);
        $this->write('=password=' . $password);
        $response = $this->read();

        if (isset($response[0]) && $response[0] === '!done') {
            return true;
        }

        // Login legado (pre-6.43 com challenge)
        if (isset($response[1]) && strpos($response[1], '=ret=') === 0) {
            $challenge = substr($response[1], 5);
            $challengeBin = hex2bin($challenge);
            $md5 = md5(chr(0) . $password . $challengeBin);

            $this->write('/login', false);
            $this->write('=name=' . $username, false);
            $this->write('=response=00' . $md5);
            $response = $this->read();

            if (isset($response[0]) && $response[0] === '!done') {
                return true;
            }
        }

        throw new \Exception('Autenticação falhou no MikroTik. Verifique usuário e senha.');
    }

    /**
     * Escrever uma "word" no protocolo da API RouterOS
     */
    private function write($word, $isLast = true) {
        $len = strlen($word);

        if ($len < 0x80) {
            fwrite($this->socket, chr($len));
        } elseif ($len < 0x4000) {
            $len |= 0x8000;
            fwrite($this->socket, chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            $len |= 0xC00000;
            fwrite($this->socket, chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            $len |= 0xE0000000;
            fwrite($this->socket, chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } else {
            fwrite($this->socket, chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }

        fwrite($this->socket, $word);

        if ($isLast) {
            fwrite($this->socket, chr(0)); // end of sentence
        }
    }

    /**
     * Ler resposta completa da API RouterOS
     */
    private function read() {
        $response = [];
        $receivingDone = false;

        while (!$receivingDone) {
            $word = $this->readWord();

            if ($word === false || $word === '') {
                // End of sentence - se já temos dados, verificar se terminou
                if (!empty($response)) {
                    $last = end($response);
                    if ($last === '!done' || $last === '!fatal' || $last === '!trap') {
                        $receivingDone = true;
                    }
                }
                continue;
            }

            $response[] = $word;

            if ($word === '!done' || $word === '!fatal') {
                // Ler até o fim da sentence
                while (true) {
                    $extra = $this->readWord();
                    if ($extra === '' || $extra === false) break;
                    $response[] = $extra;
                }
                $receivingDone = true;
            }
        }

        return $response;
    }

    /**
     * Ler uma word individual
     */
    private function readWord() {
        $byte = @fread($this->socket, 1);
        if ($byte === false || $byte === '') return '';

        $len = ord($byte);

        if ($len === 0) return '';

        if (($len & 0x80) === 0) {
            // 1 byte length
        } elseif (($len & 0xC0) === 0x80) {
            $len = (($len & ~0x80) << 8) + ord(fread($this->socket, 1));
        } elseif (($len & 0xE0) === 0xC0) {
            $len = (($len & ~0xC0) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif (($len & 0xF0) === 0xE0) {
            $len = (($len & ~0xE0) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif ($len === 0xF0) {
            $len = (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }

        if ($len === 0) return '';

        $word = '';
        $remaining = $len;
        while ($remaining > 0) {
            $chunk = fread($this->socket, $remaining);
            if ($chunk === false || $chunk === '') break;
            $word .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $word;
    }

    /**
     * Enviar comando e receber resposta parseada
     */
    private function command($deviceId, $cmd, $params = []) {
        $device = $this->getDispositivo($deviceId);
        if (!$device) throw new \Exception('Dispositivo não encontrado');

        $senha = $this->decrypt($device['senha']);

        $this->connect($device);

        try {
            $this->login($device['usuario'], $senha);

            // Enviar comando
            $this->write($cmd, empty($params));

            foreach ($params as $i => $param) {
                $isLast = ($i === count($params) - 1);
                $this->write($param, $isLast);
            }

            $raw = $this->read();
            $this->disconnect();

            // Atualizar último acesso
            $this->db->query("UPDATE mikrotik_devices SET ultimo_acesso = NOW() WHERE id = ?", [$deviceId]);

            return $this->parseResponse($raw);

        } catch (\Exception $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Parse da resposta RouterOS para array associativo
     */
    private function parseResponse($raw) {
        $result = [];
        $current = [];
        $error = null;

        foreach ($raw as $word) {
            if ($word === '!re') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                $current = [];
            } elseif ($word === '!done') {
                if (!empty($current)) {
                    $result[] = $current;
                }
            } elseif ($word === '!trap' || $word === '!fatal') {
                // Próximas words podem ter a mensagem de erro
                continue;
            } elseif (strpos($word, '=') === 0) {
                $word = substr($word, 1); // remove leading =
                $eqPos = strpos($word, '=');
                if ($eqPos !== false) {
                    $key = substr($word, 0, $eqPos);
                    $val = substr($word, $eqPos + 1);
                    if ($key === 'message' && $error === null) {
                        $error = $val;
                    } else {
                        $current[$key] = $val;
                    }
                }
            }
        }

        if ($error) {
            throw new \Exception('MikroTik: ' . $error);
        }

        return $result;
    }

    /**
     * Testar conexão com dispositivo
     */
    public function testarConexao($dados) {
        $device = [
            'host'    => $dados['host'],
            'porta'   => $dados['porta'] ?? 8728,
            'use_ssl' => $dados['use_ssl'] ?? 0,
        ];
        $senha = $dados['senha'];

        // Se editando sem senha nova, buscar a salva
        if (empty($senha) && !empty($dados['id'])) {
            $existing = $this->getDispositivo($dados['id']);
            if ($existing) $senha = $this->decrypt($existing['senha']);
        }

        $this->connect($device);
        try {
            $this->login($dados['usuario'], $senha);
            $this->disconnect();
            return ['status' => 'ok', 'message' => 'Conexão e autenticação bem-sucedidas!'];
        } catch (\Exception $e) {
            $this->disconnect();
            throw $e;
        }
    }

    // ==========================================
    //  SYSTEM INFO
    // ==========================================
    public function getSystemResource($deviceId) {
        return $this->command($deviceId, '/system/resource/print');
    }

    public function getSystemIdentity($deviceId) {
        return $this->command($deviceId, '/system/identity/print');
    }

    public function getSystemRouterboard($deviceId) {
        try {
            return $this->command($deviceId, '/system/routerboard/print');
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSystemClock($deviceId) {
        return $this->command($deviceId, '/system/clock/print');
    }

    // ==========================================
    //  INTERFACES
    // ==========================================
    public function getInterfaces($deviceId) {
        return $this->command($deviceId, '/interface/print');
    }

    public function getInterfaceTraffic($deviceId, $interface) {
        return $this->command($deviceId, '/interface/monitor-traffic', [
            '=interface=' . $interface,
            '=once='
        ]);
    }

    public function enableInterface($deviceId, $id) {
        return $this->command($deviceId, '/interface/enable', ['=.id=' . $id]);
    }

    public function disableInterface($deviceId, $id) {
        return $this->command($deviceId, '/interface/disable', ['=.id=' . $id]);
    }

    public function getIPAddresses($deviceId) {
        return $this->command($deviceId, '/ip/address/print');
    }

    // ==========================================
    //  NAT (srcnat / dstnat)
    // ==========================================
    public function getNatRules($deviceId) {
        return $this->command($deviceId, '/ip/firewall/nat/print');
    }

    public function addNatRule($deviceId, $dados) {
        $params = [];
        $fields = ['chain', 'action', 'protocol', 'src-address', 'dst-address',
                    'src-port', 'dst-port', 'to-addresses', 'to-ports',
                    'in-interface', 'out-interface', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f]) && $dados[$f] !== '') {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/ip/firewall/nat/add', $params);
    }

    public function updateNatRule($deviceId, $id, $dados) {
        $params = ['=.id=' . $id];
        $fields = ['chain', 'action', 'protocol', 'src-address', 'dst-address',
                    'src-port', 'dst-port', 'to-addresses', 'to-ports',
                    'in-interface', 'out-interface', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f])) {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/ip/firewall/nat/set', $params);
    }

    public function removeNatRule($deviceId, $id) {
        return $this->command($deviceId, '/ip/firewall/nat/remove', ['=.id=' . $id]);
    }

    public function enableNatRule($deviceId, $id) {
        return $this->command($deviceId, '/ip/firewall/nat/enable', ['=.id=' . $id]);
    }

    public function disableNatRule($deviceId, $id) {
        return $this->command($deviceId, '/ip/firewall/nat/disable', ['=.id=' . $id]);
    }

    public function moveNatRule($deviceId, $id, $destination) {
        return $this->command($deviceId, '/ip/firewall/nat/move', [
            '=numbers=' . $id,
            '=destination=' . $destination
        ]);
    }

    // ==========================================
    //  FIREWALL FILTER
    // ==========================================
    public function getFirewallRules($deviceId) {
        return $this->command($deviceId, '/ip/firewall/filter/print');
    }

    public function addFirewallRule($deviceId, $dados) {
        $params = [];
        $fields = ['chain', 'action', 'protocol', 'src-address', 'dst-address',
                    'src-port', 'dst-port', 'in-interface', 'out-interface',
                    'connection-state', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f]) && $dados[$f] !== '') {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/ip/firewall/filter/add', $params);
    }

    public function updateFirewallRule($deviceId, $id, $dados) {
        $params = ['=.id=' . $id];
        $fields = ['chain', 'action', 'protocol', 'src-address', 'dst-address',
                    'src-port', 'dst-port', 'in-interface', 'out-interface',
                    'connection-state', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f])) {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/ip/firewall/filter/set', $params);
    }

    public function removeFirewallRule($deviceId, $id) {
        return $this->command($deviceId, '/ip/firewall/filter/remove', ['=.id=' . $id]);
    }

    public function enableFirewallRule($deviceId, $id) {
        return $this->command($deviceId, '/ip/firewall/filter/enable', ['=.id=' . $id]);
    }

    public function disableFirewallRule($deviceId, $id) {
        return $this->command($deviceId, '/ip/firewall/filter/disable', ['=.id=' . $id]);
    }

    // ==========================================
    //  DHCP
    // ==========================================
    public function getDHCPLeases($deviceId) {
        return $this->command($deviceId, '/ip/dhcp-server/lease/print');
    }

    public function addDHCPLease($deviceId, $dados) {
        $params = [];
        $fields = ['address', 'mac-address', 'server', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f]) && $dados[$f] !== '') {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/ip/dhcp-server/lease/add', $params);
    }

    public function removeDHCPLease($deviceId, $id) {
        return $this->command($deviceId, '/ip/dhcp-server/lease/remove', ['=.id=' . $id]);
    }

    public function makeDHCPStatic($deviceId, $id) {
        return $this->command($deviceId, '/ip/dhcp-server/lease/make-static', ['=.id=' . $id]);
    }

    public function getDHCPServers($deviceId) {
        return $this->command($deviceId, '/ip/dhcp-server/print');
    }

    public function getDHCPNetworks($deviceId) {
        return $this->command($deviceId, '/ip/dhcp-server/network/print');
    }

    // ==========================================
    //  ROUTES
    // ==========================================
    public function getRoutes($deviceId) {
        return $this->command($deviceId, '/ip/route/print');
    }

    public function addRoute($deviceId, $dados) {
        $params = [];
        $fields = ['dst-address', 'gateway', 'distance', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f]) && $dados[$f] !== '') {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/ip/route/add', $params);
    }

    public function removeRoute($deviceId, $id) {
        return $this->command($deviceId, '/ip/route/remove', ['=.id=' . $id]);
    }

    // ==========================================
    //  QUEUES (Controle de Banda)
    // ==========================================
    public function getQueues($deviceId) {
        return $this->command($deviceId, '/queue/simple/print');
    }

    public function addQueue($deviceId, $dados) {
        $params = [];
        $fields = ['name', 'target', 'max-limit', 'burst-limit', 'burst-threshold',
                    'burst-time', 'limit-at', 'priority', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f]) && $dados[$f] !== '') {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/queue/simple/add', $params);
    }

    public function updateQueue($deviceId, $id, $dados) {
        $params = ['=.id=' . $id];
        $fields = ['name', 'target', 'max-limit', 'burst-limit', 'burst-threshold',
                    'burst-time', 'limit-at', 'priority', 'comment', 'disabled'];
        foreach ($fields as $f) {
            if (isset($dados[$f])) {
                $params[] = '=' . $f . '=' . $dados[$f];
            }
        }
        return $this->command($deviceId, '/queue/simple/set', $params);
    }

    public function removeQueue($deviceId, $id) {
        return $this->command($deviceId, '/queue/simple/remove', ['=.id=' . $id]);
    }

    public function enableQueue($deviceId, $id) {
        return $this->command($deviceId, '/queue/simple/enable', ['=.id=' . $id]);
    }

    public function disableQueue($deviceId, $id) {
        return $this->command($deviceId, '/queue/simple/disable', ['=.id=' . $id]);
    }

    // ==========================================
    //  ARP
    // ==========================================
    public function getArpList($deviceId) {
        return $this->command($deviceId, '/ip/arp/print');
    }

    // ==========================================
    //  DNS
    // ==========================================
    public function getDnsSettings($deviceId) {
        return $this->command($deviceId, '/ip/dns/print');
    }

    public function getDnsCache($deviceId) {
        return $this->command($deviceId, '/ip/dns/cache/print');
    }

    public function flushDnsCache($deviceId) {
        return $this->command($deviceId, '/ip/dns/cache/flush');
    }

    public function getDnsStatic($deviceId) {
        return $this->command($deviceId, '/ip/dns/static/print');
    }

    // ==========================================
    //  LOGS
    // ==========================================
    public function getLogs($deviceId, $limit = 100) {
        $result = $this->command($deviceId, '/log/print');
        // Retornar apenas os últimos N
        if (count($result) > $limit) {
            $result = array_slice($result, -$limit);
        }
        return $result;
    }

    // ==========================================
    //  TOOLS
    // ==========================================
    public function ping($deviceId, $address, $count = 4) {
        return $this->command($deviceId, '/ping', [
            '=address=' . $address,
            '=count=' . $count,
        ]);
    }

    public function getActiveConnections($deviceId) {
        return $this->command($deviceId, '/ip/firewall/connection/print');
    }

    // ==========================================
    //  SISTEMA
    // ==========================================
    public function reboot($deviceId) {
        try {
            return $this->command($deviceId, '/system/reboot');
        } catch (\Exception $e) {
            // Reboot fecha a conexão, isso é normal
            if (strpos($e->getMessage(), 'stream') !== false || strpos($e->getMessage(), 'closed') !== false) {
                return [['status' => 'rebooting']];
            }
            throw $e;
        }
    }

    public function getUsers($deviceId) {
        return $this->command($deviceId, '/user/print');
    }

    public function getActiveUsers($deviceId) {
        return $this->command($deviceId, '/user/active/print');
    }

    // ==========================================
    //  HELPERS
    // ==========================================
    public static function formatBytes($bytes) {
        $bytes = (float)$bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public static function formatUptime($uptime) {
        // RouterOS format: "2w3d14h22m33s" or "14h22m33s"
        $result = '';
        if (preg_match('/(\d+)w/', $uptime, $m)) $result .= $m[1] . 'sem ';
        if (preg_match('/(\d+)d/', $uptime, $m)) $result .= $m[1] . 'd ';
        if (preg_match('/(\d+)h/', $uptime, $m)) $result .= $m[1] . 'h ';
        if (preg_match('/(\d+)m(?!s)/', $uptime, $m)) $result .= $m[1] . 'm ';
        if (preg_match('/(\d+)s/', $uptime, $m)) $result .= $m[1] . 's';
        return trim($result) ?: $uptime;
    }

    public static function formatBitrate($bitsPerSec) {
        $bps = (float)$bitsPerSec;
        if ($bps >= 1000000000) return round($bps / 1000000000, 2) . ' Gbps';
        if ($bps >= 1000000) return round($bps / 1000000, 2) . ' Mbps';
        if ($bps >= 1000) return round($bps / 1000, 2) . ' Kbps';
        return $bps . ' bps';
    }

    /**
     * Overview completo do dispositivo (chamada única com múltiplos dados)
     */
    public function getOverview($deviceId) {
        $device = $this->getDispositivo($deviceId);
        if (!$device) throw new \Exception('Dispositivo não encontrado');

        $senha = $this->decrypt($device['senha']);

        $this->connect($device);

        try {
            $this->login($device['usuario'], $senha);

            // Identity
            $this->write('/system/identity/print');
            $identityRaw = $this->read();
            $identity = $this->parseResponse($identityRaw);

            // Resource
            $this->write('/system/resource/print');
            $resourceRaw = $this->read();
            $resource = $this->parseResponse($resourceRaw);

            // Interfaces count
            $this->write('/interface/print');
            $ifRaw = $this->read();
            $interfaces = $this->parseResponse($ifRaw);

            // DHCP leases count
            $this->write('/ip/dhcp-server/lease/print');
            $dhcpRaw = $this->read();
            $dhcpLeases = $this->parseResponse($dhcpRaw);

            // IP addresses
            $this->write('/ip/address/print');
            $ipRaw = $this->read();
            $ips = $this->parseResponse($ipRaw);

            // Firewall rules count
            $this->write('/ip/firewall/filter/print');
            $fwRaw = $this->read();
            $fwRules = $this->parseResponse($fwRaw);

            // NAT rules count
            $this->write('/ip/firewall/nat/print');
            $natRaw = $this->read();
            $natRules = $this->parseResponse($natRaw);

            // Queues count
            $this->write('/queue/simple/print');
            $qRaw = $this->read();
            $queues = $this->parseResponse($qRaw);

            $this->disconnect();

            // Atualizar último acesso
            $this->db->query("UPDATE mikrotik_devices SET ultimo_acesso = NOW() WHERE id = ?", [$deviceId]);

            $res = $resource[0] ?? [];

            // Calcular uso de memória
            $totalMem = (float)($res['total-memory'] ?? 0);
            $freeMem  = (float)($res['free-memory'] ?? 0);
            $usedMem  = $totalMem - $freeMem;
            $memPct   = $totalMem > 0 ? round(($usedMem / $totalMem) * 100, 1) : 0;

            // Calcular uso de CPU
            $cpuLoad = (int)($res['cpu-load'] ?? 0);

            // Calcular uso de HDD
            $totalHdd = (float)($res['total-hdd-space'] ?? 0);
            $freeHdd  = (float)($res['free-hdd-space'] ?? 0);
            $usedHdd  = $totalHdd - $freeHdd;
            $hddPct   = $totalHdd > 0 ? round(($usedHdd / $totalHdd) * 100, 1) : 0;

            $ifUp = count(array_filter($interfaces, fn($i) => ($i['running'] ?? 'false') === 'true'));

            return [
                'identity'         => $identity[0]['name'] ?? 'MikroTik',
                'version'          => $res['version'] ?? 'N/A',
                'board_name'       => $res['board-name'] ?? 'N/A',
                'architecture'     => $res['architecture-name'] ?? 'N/A',
                'uptime'           => $res['uptime'] ?? 'N/A',
                'cpu_count'        => $res['cpu-count'] ?? 1,
                'cpu_load'         => $cpuLoad,
                'cpu_model'        => $res['cpu'] ?? 'N/A',
                'total_memory'     => $totalMem,
                'used_memory'      => $usedMem,
                'memory_pct'       => $memPct,
                'total_hdd'        => $totalHdd,
                'used_hdd'         => $usedHdd,
                'hdd_pct'          => $hddPct,
                'interfaces_total' => count($interfaces),
                'interfaces_up'    => $ifUp,
                'ip_addresses'     => $ips,
                'dhcp_leases'      => count($dhcpLeases),
                'firewall_rules'   => count($fwRules),
                'nat_rules'        => count($natRules),
                'queues'           => count($queues),
            ];

        } catch (\Exception $e) {
            $this->disconnect();
            throw $e;
        }
    }
}
