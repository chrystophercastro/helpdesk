<?php
/**
 * Model: RemoteDesktop
 * Gerencia sessões de acesso remoto, histórico e configurações
 */
require_once __DIR__ . '/Database.php';

class RemoteDesktop {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ==========================================
    //  SESSÕES (Clientes Online)
    // ==========================================

    public function listarSessoes($status = null) {
        $where = "1=1";
        $params = [];

        if ($status) {
            $where .= " AND rs.status = ?";
            $params[] = $status;
        }

        return $this->db->fetchAll(
            "SELECT rs.*, 
                    u.nome AS tecnico_nome,
                    c.titulo AS chamado_titulo,
                    i.nome AS inventario_nome, i.tipo AS inventario_tipo
             FROM remote_sessoes rs
             LEFT JOIN usuarios u ON rs.tecnico_id = u.id
             LEFT JOIN chamados c ON rs.chamado_id = c.id
             LEFT JOIN inventario i ON rs.inventario_id = i.id
             WHERE $where
             ORDER BY rs.status = 'conectado' DESC, rs.status = 'online' DESC, rs.atualizado_em DESC",
            $params
        );
    }

    public function listarOnline() {
        return $this->listarSessoes('online');
    }

    public function listarConectados() {
        return $this->listarSessoes('conectado');
    }

    public function buscarSessaoPorCodigo($codigo) {
        return $this->db->fetch(
            "SELECT rs.*, 
                    u.nome AS tecnico_nome,
                    i.nome AS inventario_nome
             FROM remote_sessoes rs
             LEFT JOIN usuarios u ON rs.tecnico_id = u.id
             LEFT JOIN inventario i ON rs.inventario_id = i.id
             WHERE rs.codigo = ?",
            [$codigo]
        );
    }

    public function buscarSessaoPorInventario($inventarioId) {
        return $this->db->fetch(
            "SELECT * FROM remote_sessoes WHERE inventario_id = ? AND status IN ('online', 'conectado') ORDER BY atualizado_em DESC LIMIT 1",
            [$inventarioId]
        );
    }

    public function buscarSessaoPorHostname($hostname) {
        return $this->db->fetch(
            "SELECT * FROM remote_sessoes WHERE hostname = ? AND status IN ('online', 'conectado') ORDER BY atualizado_em DESC LIMIT 1",
            [$hostname]
        );
    }

    public function contarOnline() {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM remote_sessoes WHERE status = 'online'");
    }

    public function contarConectados() {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM remote_sessoes WHERE status = 'conectado'");
    }

    // ==========================================
    //  HISTÓRICO
    // ==========================================

    public function listarHistorico($filtros = []) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['tecnico_id'])) {
            $where .= " AND rh.tecnico_id = ?";
            $params[] = $filtros['tecnico_id'];
        }
        if (!empty($filtros['hostname'])) {
            $where .= " AND rh.hostname LIKE ?";
            $params[] = '%' . $filtros['hostname'] . '%';
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND rh.inicio >= ?";
            $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND rh.inicio <= ?";
            $params[] = $filtros['data_fim'] . ' 23:59:59';
        }
        if (!empty($filtros['chamado_id'])) {
            $where .= " AND rh.chamado_id = ?";
            $params[] = $filtros['chamado_id'];
        }

        $limit = (int)($filtros['limit'] ?? 50);
        $offset = (int)($filtros['offset'] ?? 0);

        return $this->db->fetchAll(
            "SELECT rh.*, 
                    u.nome AS tecnico_nome_rel,
                    c.titulo AS chamado_titulo
             FROM remote_historico rh
             LEFT JOIN usuarios u ON rh.tecnico_id = u.id
             LEFT JOIN chamados c ON rh.chamado_id = c.id
             WHERE $where
             ORDER BY rh.inicio DESC
             LIMIT $limit OFFSET $offset",
            $params
        );
    }

    public function contarHistorico($filtros = []) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['tecnico_id'])) {
            $where .= " AND tecnico_id = ?";
            $params[] = $filtros['tecnico_id'];
        }
        if (!empty($filtros['hostname'])) {
            $where .= " AND hostname LIKE ?";
            $params[] = '%' . $filtros['hostname'] . '%';
        }

        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM remote_historico WHERE $where", $params);
    }

    // ==========================================
    //  CONFIGURAÇÕES
    // ==========================================

    public function getConfig($chave, $default = null) {
        $val = $this->db->fetchColumn("SELECT valor FROM remote_config WHERE chave = ?", [$chave]);
        return $val !== false ? $val : $default;
    }

    public function setConfig($chave, $valor) {
        $exists = $this->db->fetchColumn("SELECT COUNT(*) FROM remote_config WHERE chave = ?", [$chave]);
        if ($exists) {
            $this->db->query("UPDATE remote_config SET valor = ? WHERE chave = ?", [$valor, $chave]);
        } else {
            $this->db->insert('remote_config', ['chave' => $chave, 'valor' => $valor]);
        }
    }

    public function getAllConfig() {
        $rows = $this->db->fetchAll("SELECT chave, valor FROM remote_config ORDER BY chave");
        $config = [];
        foreach ($rows as $row) {
            $config[$row['chave']] = $row['valor'];
        }
        return $config;
    }

    // ==========================================
    //  SERVIDOR
    // ==========================================

    public function isServerRunning() {
        $port = (int)$this->getConfig('server_port', 8089);
        $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }

    public function getServerPort() {
        return (int)$this->getConfig('server_port', 8089);
    }

    // ==========================================
    //  INTEGRAÇÃO CHAMADOS
    // ==========================================

    public function buscarSessaoParaChamado($chamadoId) {
        // 1. Buscar pelo chamado direto (sessão vinculada ao chamado)
        $sessao = $this->db->fetch(
            "SELECT rs.* FROM remote_sessoes rs WHERE rs.chamado_id = ? AND rs.status IN ('online','conectado') LIMIT 1",
            [$chamadoId]
        );
        if ($sessao) return $sessao;

        // 2. Buscar pelo ativo do chamado (inventário_id na sessão)
        $chamado = $this->db->fetch("SELECT ativo_id FROM chamados WHERE id = ?", [$chamadoId]);
        if ($chamado && $chamado['ativo_id']) {
            $sessao = $this->buscarSessaoPorInventario($chamado['ativo_id']);
            if ($sessao) return $sessao;

            // 3. Fallback: comparar nome do ativo (inventário) com hostname da sessão remota
            $ativo = $this->db->fetch("SELECT nome FROM inventario WHERE id = ?", [$chamado['ativo_id']]);
            if ($ativo && !empty($ativo['nome'])) {
                $sessao = $this->buscarSessaoPorHostname($ativo['nome']);
                if ($sessao) return $sessao;
            }
        }

        return null;
    }

    // ==========================================
    //  ESTATÍSTICAS
    // ==========================================

    public function estatisticas() {
        return [
            'online' => $this->contarOnline(),
            'conectados' => $this->contarConectados(),
            'total_sessoes' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM remote_historico"),
            'total_hoje' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM remote_historico WHERE DATE(inicio) = CURDATE()"),
            'tempo_medio' => (int)$this->db->fetchColumn("SELECT AVG(duracao_segundos) FROM remote_historico WHERE duracao_segundos > 0"),
            'tecnicos_ativos' => (int)$this->db->fetchColumn("SELECT COUNT(DISTINCT tecnico_id) FROM remote_sessoes WHERE status = 'conectado'"),
        ];
    }
}
