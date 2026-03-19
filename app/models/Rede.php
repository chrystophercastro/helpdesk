<?php
/**
 * Model: Rede
 * Gestão de dispositivos de rede, ping e scanner
 */
require_once __DIR__ . '/Database.php';

class Rede {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ==========================================
    //  CRUD DISPOSITIVOS
    // ==========================================

    public function listar($filtros = []) {
        $where = '1=1';
        $params = [];

        if (!empty($filtros['busca'])) {
            $where .= " AND (d.nome LIKE ? OR d.ip LIKE ? OR d.localizacao LIKE ?)";
            $b = '%' . $filtros['busca'] . '%';
            $params = array_merge($params, [$b, $b, $b]);
        }
        if (!empty($filtros['tipo'])) {
            $where .= " AND d.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        if (isset($filtros['status']) && $filtros['status'] !== '') {
            $where .= " AND d.ultimo_status = ?";
            $params[] = $filtros['status'];
        }

        return $this->db->fetchAll(
            "SELECT d.*, u.nome AS criado_por_nome
             FROM dispositivos_rede d
             LEFT JOIN usuarios u ON d.criado_por = u.id
             WHERE {$where}
             ORDER BY d.nome ASC",
            $params
        );
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT d.*, u.nome AS criado_por_nome
             FROM dispositivos_rede d
             LEFT JOIN usuarios u ON d.criado_por = u.id
             WHERE d.id = ?",
            [$id]
        );
    }

    public function findByIp($ip) {
        return $this->db->fetch("SELECT * FROM dispositivos_rede WHERE ip = ?", [$ip]);
    }

    public function criar($dados) {
        return $this->db->insert('dispositivos_rede', [
            'nome' => $dados['nome'],
            'ip' => $dados['ip'],
            'tipo' => $dados['tipo'] ?? 'outro',
            'localizacao' => $dados['localizacao'] ?? null,
            'mac_address' => $dados['mac_address'] ?? null,
            'observacoes' => $dados['observacoes'] ?? null,
            'intervalo_ping' => (int)($dados['intervalo_ping'] ?? 60),
            'notificar_offline' => isset($dados['notificar_offline']) ? 1 : 0,
            'criado_por' => $dados['criado_por'],
        ]);
    }

    public function atualizar($id, $dados) {
        $campos = [
            'nome' => $dados['nome'],
            'ip' => $dados['ip'],
            'tipo' => $dados['tipo'] ?? 'outro',
            'localizacao' => $dados['localizacao'] ?? null,
            'mac_address' => $dados['mac_address'] ?? null,
            'observacoes' => $dados['observacoes'] ?? null,
            'intervalo_ping' => (int)($dados['intervalo_ping'] ?? 60),
            'notificar_offline' => !empty($dados['notificar_offline']) ? 1 : 0,
        ];

        return $this->db->update('dispositivos_rede', $campos, 'id = ?', [$id]);
    }

    public function excluir($id) {
        return $this->db->delete('dispositivos_rede', 'id = ?', [$id]);
    }

    // ==========================================
    //  PING
    // ==========================================

    /**
     * Pingar um IP e retornar status + latência
     */
    public function ping($ip, $timeout = 2) {
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        if (!$ip) {
            return ['online' => false, 'latencia' => null, 'erro' => 'IP inválido'];
        }

        $startTime = microtime(true);

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: ping -n 1 -w timeout_ms
            $timeoutMs = $timeout * 1000;
            $output = [];
            $returnCode = 0;
            exec("ping -n 1 -w {$timeoutMs} {$ip} 2>&1", $output, $returnCode);
            $outputStr = implode("\n", $output);

            $online = (strpos($outputStr, 'TTL=') !== false || strpos($outputStr, 'ttl=') !== false);

            $latencia = null;
            if ($online && preg_match('/(?:tempo|time)[=<](\d+)\s*ms/i', $outputStr, $m)) {
                $latencia = max(1, (float)$m[1]); // <1ms = 1ms
            } elseif ($online) {
                $latencia = round((microtime(true) - $startTime) * 1000, 2);
            }
        } else {
            // Linux/Mac: ping -c 1 -W timeout
            $output = [];
            $returnCode = 0;
            exec("ping -c 1 -W {$timeout} {$ip} 2>&1", $output, $returnCode);
            $outputStr = implode("\n", $output);

            $online = ($returnCode === 0);

            $latencia = null;
            if ($online && preg_match('/time[=]?([\d.]+)\s*ms/i', $outputStr, $m)) {
                $latencia = (float)$m[1];
            }
        }

        return [
            'online' => $online,
            'latencia' => $latencia,
            'output' => $outputStr ?? '',
        ];
    }

    /**
     * Pingar um dispositivo e salvar resultado
     */
    public function pingarDispositivo($id) {
        $dispositivo = $this->findById($id);
        if (!$dispositivo) return null;

        $resultado = $this->ping($dispositivo['ip']);

        $status = $resultado['online'] ? 'online' : 'offline';

        // Atualizar status do dispositivo
        $this->db->query(
            "UPDATE dispositivos_rede SET ultimo_status = ?, ultimo_ping = NOW(), latencia_ms = ? WHERE id = ?",
            [$status, $resultado['latencia'], $id]
        );

        // Registrar no log
        $this->db->insert('dispositivos_rede_log', [
            'dispositivo_id' => $id,
            'status' => $status,
            'latencia_ms' => $resultado['latencia'],
        ]);

        return [
            'id' => $id,
            'ip' => $dispositivo['ip'],
            'nome' => $dispositivo['nome'],
            'status' => $status,
            'latencia' => $resultado['latencia'],
        ];
    }

    /**
     * Pingar todos os dispositivos ativos
     */
    public function pingarTodos() {
        $dispositivos = $this->db->fetchAll(
            "SELECT id FROM dispositivos_rede WHERE ativo = 1"
        );

        $resultados = [];
        foreach ($dispositivos as $d) {
            $resultados[] = $this->pingarDispositivo($d['id']);
        }

        return $resultados;
    }

    // ==========================================
    //  SCANNER DE REDE
    // ==========================================

    /**
     * Detectar rede do servidor automaticamente
     */
    public function getServerNetwork() {
        // Método 1: SERVER_ADDR (funciona quando acessado via web)
        $ip = $_SERVER['SERVER_ADDR'] ?? null;

        // Método 2: gethostbyname
        if (!$ip || $ip === '::1' || $ip === '127.0.0.1') {
            $hostname = gethostname();
            if ($hostname) {
                $resolved = gethostbyname($hostname);
                if ($resolved !== $hostname && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
                    $ip = $resolved;
                }
            }
        }

        // Método 3: Windows - ipconfig (pegar todas as interfaces e preferir rede real)
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec('ipconfig 2>&1');
                if ($output) {
                    preg_match_all('/IPv4[.\s:]+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $output, $matches);
                    if (!empty($matches[1])) {
                        // Filtrar IPs válidos (não loopback, não APIPA)
                        $candidatos = array_filter($matches[1], function($candidate) {
                            return $candidate !== '127.0.0.1' && !str_starts_with($candidate, '169.254.');
                        });
                        // Preferir IPs de redes corporativas (10.x, 172.16-31.x) sobre virtuais (192.168.56.x, 192.168.224.x)
                        $preferido = null;
                        foreach ($candidatos as $candidate) {
                            if (str_starts_with($candidate, '10.') || preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $candidate)) {
                                $preferido = $candidate;
                                break;
                            }
                        }
                        $ip = $preferido ?: reset($candidatos) ?: null;
                    }
                }
            } else {
                $output = shell_exec("hostname -I 2>/dev/null");
                if ($output) {
                    $ips = explode(' ', trim($output));
                    if (!empty($ips[0]) && filter_var($ips[0], FILTER_VALIDATE_IP)) {
                        $ip = $ips[0];
                    }
                }
            }
        }

        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            $parts = explode('.', $ip);
            return [
                'ip' => $ip,
                'rede' => $parts[0] . '.' . $parts[1] . '.' . $parts[2],
            ];
        }

        return ['ip' => '127.0.0.1', 'rede' => '192.168.1'];
    }

    /**
     * Scan de rede com pings em paralelo (rápido)
     * Usa popen para disparar múltiplos pings simultaneamente
     */
    public function scanRedeStream($rede, $inicio, $fim, callable $onResult) {
        $rede = trim($rede);
        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $rede)) {
            throw new \Exception('Formato de rede inválido. Use: 192.168.1');
        }

        $inicio = max(1, min(254, (int)$inicio));
        $fim = max($inicio, min(254, (int)$fim));
        $batchSize = 30;
        $timeoutMs = 500;
        $total = $fim - $inicio + 1;
        $scanned = 0;

        for ($i = $inicio; $i <= $fim; $i += $batchSize) {
            $batchEnd = min($i + $batchSize - 1, $fim);
            $procs = [];

            // Disparar batch de pings em paralelo
            for ($j = $i; $j <= $batchEnd; $j++) {
                $ip = $rede . '.' . $j;
                if (PHP_OS_FAMILY === 'Windows') {
                    $cmd = "ping -n 1 -w {$timeoutMs} {$ip}";
                } else {
                    $cmd = "ping -c 1 -W 1 {$ip}";
                }
                $procs[$ip] = popen($cmd . ' 2>&1', 'r');
            }

            // Coletar resultados do batch
            foreach ($procs as $ip => $proc) {
                $output = stream_get_contents($proc);
                pclose($proc);
                $scanned++;

                $online = (stripos($output, 'TTL=') !== false);
                if ($online) {
                    $latencia = null;
                    if (preg_match('/(?:tempo|time)[=<](\d+)\s*ms/i', $output, $m)) {
                        $latencia = max(1, (float)$m[1]);
                    }

                    $mac = $this->getMacAddress($ip);
                    $dispositivo = $this->findByIp($ip);

                    $hostname = '';
                    $hn = @gethostbyaddr($ip);
                    if ($hn !== false && $hn !== $ip) $hostname = $hn;

                    $onResult([
                        'type' => 'result',
                        'ip' => $ip,
                        'hostname' => $hostname,
                        'mac' => $mac,
                        'latencia' => $latencia,
                        'cadastrado' => !!$dispositivo,
                        'dispositivo_id' => $dispositivo['id'] ?? null,
                        'dispositivo_nome' => $dispositivo['nome'] ?? null,
                    ]);
                }
            }

            // Enviar progresso após cada batch
            $onResult([
                'type' => 'progress',
                'scanned' => min($scanned, $total),
                'total' => $total,
                'current_ip' => $rede . '.' . $batchEnd,
            ]);
        }
    }

    /**
     * Scan legado (síncrono) - mantido para compatibilidade
     */
    public function scanRede($rede, $inicio = 1, $fim = 254, $timeout = 1) {
        $resultados = [];
        $this->scanRedeStream($rede, $inicio, $fim, function($data) use (&$resultados) {
            if (($data['type'] ?? '') === 'result') {
                $resultados[] = $data;
            }
        });
        return $resultados;
    }

    /**
     * Tentar obter MAC address via ARP table
     */
    private function getMacAddress($ip) {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec("arp -a {$ip} 2>&1", $output);
            $outputStr = implode("\n", $output);
            if (preg_match('/([0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2}[-:][0-9a-f]{2})/i', $outputStr, $m)) {
                return strtoupper(str_replace('-', ':', $m[1]));
            }
        } else {
            $output = [];
            exec("arp -n {$ip} 2>&1", $output);
            $outputStr = implode("\n", $output);
            if (preg_match('/([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/i', $outputStr, $m)) {
                return strtoupper($m[1]);
            }
        }
        return '';
    }

    // ==========================================
    //  ESTATÍSTICAS
    // ==========================================

    public function getEstatisticas() {
        $total = $this->db->fetch("SELECT COUNT(*) as c FROM dispositivos_rede WHERE ativo = 1")['c'] ?? 0;
        $online = $this->db->fetch("SELECT COUNT(*) as c FROM dispositivos_rede WHERE ativo = 1 AND ultimo_status = 'online'")['c'] ?? 0;
        $offline = $this->db->fetch("SELECT COUNT(*) as c FROM dispositivos_rede WHERE ativo = 1 AND ultimo_status = 'offline'")['c'] ?? 0;
        $desconhecido = $total - $online - $offline;

        return [
            'total' => (int)$total,
            'online' => (int)$online,
            'offline' => (int)$offline,
            'desconhecido' => (int)$desconhecido,
        ];
    }

    /**
     * Histórico de pings de um dispositivo
     */
    public function getHistorico($dispositivoId, $limite = 100) {
        return $this->db->fetchAll(
            "SELECT status, latencia_ms, criado_em 
             FROM dispositivos_rede_log 
             WHERE dispositivo_id = ? 
             ORDER BY criado_em DESC 
             LIMIT ?",
            [$dispositivoId, $limite]
        );
    }

    /**
     * Uptime % das últimas 24h
     */
    public function getUptime24h($dispositivoId) {
        $total = $this->db->fetch(
            "SELECT COUNT(*) as c FROM dispositivos_rede_log WHERE dispositivo_id = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$dispositivoId]
        )['c'] ?? 0;

        if ($total == 0) return null;

        $online = $this->db->fetch(
            "SELECT COUNT(*) as c FROM dispositivos_rede_log WHERE dispositivo_id = ? AND status = 'online' AND criado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$dispositivoId]
        )['c'] ?? 0;

        return round(($online / $total) * 100, 1);
    }
}
