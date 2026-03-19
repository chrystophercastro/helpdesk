<?php
/**
 * Model: Monitor
 * Monitoramento de serviços, uptime, health checks e alertas NOC
 */
class Monitor {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ========================================
    // SERVIÇOS — CRUD
    // ========================================

    public function listarServicos($grupo = null, $status = null) {
        $sql = "SELECT ms.*, u.nome AS criado_por_nome FROM monitor_servicos ms 
                LEFT JOIN usuarios u ON ms.criado_por = u.id WHERE 1=1";
        $params = [];
        if ($grupo) { $sql .= " AND ms.grupo = ?"; $params[] = $grupo; }
        if ($status) { $sql .= " AND ms.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY ms.grupo, ms.nome";
        return $this->db->fetchAll($sql, $params);
    }

    public function getServico($id) {
        return $this->db->fetch(
            "SELECT ms.*, u.nome AS criado_por_nome FROM monitor_servicos ms 
             LEFT JOIN usuarios u ON ms.criado_por = u.id WHERE ms.id = ?",
            [$id]
        );
    }

    public function criarServico($dados) {
        return $this->db->insert('monitor_servicos', [
            'nome'          => $dados['nome'],
            'tipo'          => $dados['tipo'] ?? 'http',
            'host'          => $dados['host'],
            'porta'         => $dados['porta'] ?? null,
            'caminho'       => $dados['caminho'] ?? '/',
            'esperado'      => $dados['esperado'] ?? null,
            'intervalo_seg' => $dados['intervalo_seg'] ?? 60,
            'timeout_seg'   => $dados['timeout_seg'] ?? 10,
            'grupo'         => $dados['grupo'] ?? 'Geral',
            'notificar'     => $dados['notificar'] ?? 1,
            'criado_por'    => $dados['criado_por'] ?? null,
        ]);
    }

    public function atualizarServico($id, $dados) {
        $campos = ['nome','tipo','host','porta','caminho','esperado','intervalo_seg','timeout_seg','grupo','notificar','ativo'];
        $update = [];
        foreach ($campos as $c) {
            if (isset($dados[$c])) $update[$c] = $dados[$c];
        }
        return $this->db->update('monitor_servicos', $update, 'id = ?', [$id]);
    }

    public function excluirServico($id) {
        return $this->db->delete('monitor_servicos', 'id = ?', [$id]);
    }

    public function listarGrupos() {
        return $this->db->fetchAll("SELECT DISTINCT grupo FROM monitor_servicos ORDER BY grupo");
    }

    // ========================================
    // HEALTH CHECK — executar verificação
    // ========================================

    public function verificarServico($id) {
        $servico = $this->getServico($id);
        if (!$servico || !$servico['ativo']) return null;

        $resultado = $this->executarCheck($servico);

        // Registrar no histórico
        $this->db->insert('monitor_historico', [
            'servico_id'  => $id,
            'status'      => $resultado['status'],
            'resposta_ms' => $resultado['resposta_ms'],
            'codigo_http' => $resultado['codigo_http'] ?? null,
            'mensagem'    => $resultado['mensagem'] ?? null,
        ]);

        // Atualizar serviço
        $statusAnterior = $servico['status'];
        $totalChecks = $servico['total_checks'] + 1;
        $totalFalhas = $servico['total_falhas'] + ($resultado['status'] !== 'online' ? 1 : 0);
        $uptime = round((($totalChecks - $totalFalhas) / $totalChecks) * 100, 2);

        $updateData = [
            'status'         => $resultado['status'],
            'ultimo_check'   => date('Y-m-d H:i:s'),
            'resposta_ms'    => $resultado['resposta_ms'],
            'total_checks'   => $totalChecks,
            'total_falhas'   => $totalFalhas,
            'uptime_percent' => $uptime,
        ];

        if ($resultado['status'] === 'online') {
            $updateData['ultimo_online'] = date('Y-m-d H:i:s');
        }

        $this->db->update('monitor_servicos', $updateData, 'id = ?', [$id]);

        // Detectar mudança de status → criar incidente
        if ($statusAnterior !== $resultado['status']) {
            if ($resultado['status'] === 'offline') {
                $this->criarIncidente($id, 'outage', 'Serviço ficou offline: ' . ($resultado['mensagem'] ?? ''));
            } elseif ($resultado['status'] === 'degradado') {
                $this->criarIncidente($id, 'degraded', 'Resposta degradada: ' . ($resultado['resposta_ms'] ?? '?') . 'ms');
            } elseif ($statusAnterior === 'offline' || $statusAnterior === 'degradado') {
                // Recuperação
                $this->resolverIncidenteAberto($id);
                $this->criarIncidente($id, 'recovery', 'Serviço recuperado');
            }
        }

        $resultado['servico'] = $servico['nome'];
        return $resultado;
    }

    /**
     * Verificar todos os serviços ativos
     */
    public function verificarTodos() {
        $servicos = $this->db->fetchAll("SELECT id FROM monitor_servicos WHERE ativo = 1");
        $resultados = [];
        foreach ($servicos as $s) {
            $resultados[] = $this->verificarServico($s['id']);
        }
        return $resultados;
    }

    /**
     * Executar o check real baseado no tipo
     */
    private function executarCheck($servico) {
        $inicio = microtime(true);
        $resultado = ['status' => 'offline', 'resposta_ms' => null, 'codigo_http' => null, 'mensagem' => null];

        try {
            switch ($servico['tipo']) {
                case 'http':
                case 'https':
                    $resultado = $this->checkHTTP($servico);
                    break;
                case 'ping':
                    $resultado = $this->checkPing($servico);
                    break;
                case 'tcp':
                    $resultado = $this->checkTCP($servico);
                    break;
                case 'dns':
                    $resultado = $this->checkDNS($servico);
                    break;
            }
        } catch (\Exception $e) {
            $resultado['status'] = 'offline';
            $resultado['mensagem'] = $e->getMessage();
        }

        if ($resultado['resposta_ms'] === null) {
            $resultado['resposta_ms'] = round((microtime(true) - $inicio) * 1000);
        }

        // Detectar degradação (resposta > 3s)
        if ($resultado['status'] === 'online' && $resultado['resposta_ms'] > 3000) {
            $resultado['status'] = 'degradado';
        }

        return $resultado;
    }

    private function checkHTTP($servico) {
        $proto = $servico['tipo'] === 'https' ? 'https' : 'http';
        $porta = $servico['porta'] ?: ($proto === 'https' ? 443 : 80);
        $url = "{$proto}://{$servico['host']}:{$porta}" . ($servico['caminho'] ?: '/');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $servico['timeout_seg'],
            CURLOPT_CONNECTTIMEOUT => $servico['timeout_seg'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_NOBODY         => false,
            CURLOPT_USERAGENT      => 'HelpDesk-Monitor/1.0',
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => 'offline', 'resposta_ms' => $totalTime, 'codigo_http' => 0, 'mensagem' => $error];
        }

        $esperado = $servico['esperado'] ?: '200';
        $online = false;

        if (is_numeric($esperado)) {
            $online = (int)$httpCode === (int)$esperado;
        } else {
            $online = $httpCode >= 200 && $httpCode < 400 && stripos($body, $esperado) !== false;
        }

        return [
            'status' => $online ? 'online' : 'offline',
            'resposta_ms' => $totalTime,
            'codigo_http' => $httpCode,
            'mensagem' => $online ? "HTTP {$httpCode}" : "HTTP {$httpCode} (esperado: {$esperado})",
        ];
    }

    private function checkPing($servico) {
        $host = escapeshellarg($servico['host']);
        $timeout = $servico['timeout_seg'];

        // Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("ping -n 1 -w " . ($timeout * 1000) . " {$host} 2>&1", $output, $retval);
        } else {
            exec("ping -c 1 -W {$timeout} {$host} 2>&1", $output, $retval);
        }

        $outputStr = implode("\n", $output);
        $ms = null;
        if (preg_match('/[=<](\d+)ms/i', $outputStr, $m)) {
            $ms = (int)$m[1];
        }

        return [
            'status' => $retval === 0 ? 'online' : 'offline',
            'resposta_ms' => $ms,
            'codigo_http' => null,
            'mensagem' => $retval === 0 ? "Ping OK ({$ms}ms)" : 'Ping falhou',
        ];
    }

    private function checkTCP($servico) {
        $porta = $servico['porta'] ?: 80;
        $timeout = $servico['timeout_seg'];
        $inicio = microtime(true);

        $conn = @fsockopen($servico['host'], $porta, $errno, $errstr, $timeout);
        $ms = round((microtime(true) - $inicio) * 1000);

        if ($conn) {
            fclose($conn);
            return ['status' => 'online', 'resposta_ms' => $ms, 'codigo_http' => null, 'mensagem' => "TCP:{$porta} aberta"];
        }

        return ['status' => 'offline', 'resposta_ms' => $ms, 'codigo_http' => null, 'mensagem' => "TCP:{$porta} - {$errstr}"];
    }

    private function checkDNS($servico) {
        $inicio = microtime(true);
        $result = @dns_get_record($servico['host'], DNS_A);
        $ms = round((microtime(true) - $inicio) * 1000);

        if ($result && count($result) > 0) {
            $ip = $result[0]['ip'] ?? 'OK';
            return ['status' => 'online', 'resposta_ms' => $ms, 'codigo_http' => null, 'mensagem' => "DNS: {$ip}"];
        }

        return ['status' => 'offline', 'resposta_ms' => $ms, 'codigo_http' => null, 'mensagem' => 'DNS: resolução falhou'];
    }

    // ========================================
    // INCIDENTES
    // ========================================

    public function criarIncidente($servicoId, $tipo, $mensagem = '') {
        return $this->db->insert('monitor_incidentes', [
            'servico_id' => $servicoId,
            'tipo' => $tipo,
            'inicio' => date('Y-m-d H:i:s'),
            'mensagem' => $mensagem,
        ]);
    }

    public function resolverIncidenteAberto($servicoId) {
        $aberto = $this->db->fetch(
            "SELECT id, inicio FROM monitor_incidentes WHERE servico_id = ? AND resolvido = 0 AND tipo IN ('outage','degraded') ORDER BY inicio DESC LIMIT 1",
            [$servicoId]
        );
        if ($aberto) {
            $duracao = time() - strtotime($aberto['inicio']);
            $this->db->update('monitor_incidentes', [
                'fim' => date('Y-m-d H:i:s'),
                'duracao_seg' => $duracao,
                'resolvido' => 1,
            ], 'id = ?', [$aberto['id']]);
        }
    }

    public function listarIncidentes($servicoId = null, $limite = 50) {
        $sql = "SELECT mi.*, ms.nome AS servico_nome FROM monitor_incidentes mi 
                JOIN monitor_servicos ms ON mi.servico_id = ms.id";
        $params = [];
        if ($servicoId) {
            $sql .= " WHERE mi.servico_id = ?";
            $params[] = $servicoId;
        }
        $sql .= " ORDER BY mi.inicio DESC LIMIT " . (int)$limite;
        return $this->db->fetchAll($sql, $params);
    }

    // ========================================
    // HISTÓRICO
    // ========================================

    public function getHistorico($servicoId, $limite = 100) {
        return $this->db->fetchAll(
            "SELECT * FROM monitor_historico WHERE servico_id = ? ORDER BY checado_em DESC LIMIT " . (int)$limite,
            [$servicoId]
        );
    }

    /**
     * Histórico agrupado por hora (últimas 24h) para gráfico
     */
    public function getHistorico24h($servicoId) {
        return $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(checado_em, '%Y-%m-%d %H:00') AS hora,
                AVG(resposta_ms) AS avg_ms,
                MIN(resposta_ms) AS min_ms,
                MAX(resposta_ms) AS max_ms,
                SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) AS online_count,
                SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) AS offline_count,
                COUNT(*) AS total
             FROM monitor_historico 
             WHERE servico_id = ? AND checado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY hora ORDER BY hora",
            [$servicoId]
        );
    }

    /**
     * Uptime dos últimos N dias
     */
    public function getUptime($servicoId, $dias = 30) {
        return $this->db->fetchAll(
            "SELECT 
                DATE(checado_em) AS dia,
                SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) AS online,
                COUNT(*) AS total,
                ROUND(SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) AS percent
             FROM monitor_historico 
             WHERE servico_id = ? AND checado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY dia ORDER BY dia",
            [$servicoId, $dias]
        );
    }

    // ========================================
    // DASHBOARD / STATS
    // ========================================

    public function getOverview() {
        $servicos = $this->listarServicos();
        $total = count($servicos);
        $online = 0; $offline = 0; $degradado = 0;

        foreach ($servicos as $s) {
            if ($s['status'] === 'online') $online++;
            elseif ($s['status'] === 'offline') $offline++;
            elseif ($s['status'] === 'degradado') $degradado++;
        }

        $incidentesAtivos = (int)$this->db->count('monitor_incidentes', 'resolvido = 0 AND tipo IN ("outage","degraded")');
        $avgUptime = $this->db->fetch("SELECT AVG(uptime_percent) as avg FROM monitor_servicos WHERE ativo = 1");

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'degradado' => $degradado,
            'incidentes_ativos' => $incidentesAtivos,
            'uptime_medio' => round($avgUptime['avg'] ?? 100, 2),
            'servicos' => $servicos,
        ];
    }

    /**
     * Status page pública simplificada
     */
    public function getStatusPage() {
        $grupos = [];
        $servicos = $this->db->fetchAll(
            "SELECT id, nome, tipo, host, status, uptime_percent, resposta_ms, ultimo_check, grupo 
             FROM monitor_servicos WHERE ativo = 1 ORDER BY grupo, nome"
        );
        foreach ($servicos as $s) {
            $grupos[$s['grupo']][] = $s;
        }
        return $grupos;
    }
}
