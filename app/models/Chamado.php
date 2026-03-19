<?php
/**
 * Model: Chamado
 */
require_once __DIR__ . '/Database.php';

class Chamado {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT c.*, 
                    s.nome as solicitante_nome, s.email as solicitante_email,
                    u.nome as tecnico_nome,
                    cat.nome as categoria_nome, cat.cor as categoria_cor,
                    sl.nome as sla_nome, sl.tempo_resposta, sl.tempo_resolucao,
                    inv.nome as ativo_nome, inv.numero_patrimonio
             FROM chamados c
             LEFT JOIN solicitantes s ON c.solicitante_id = s.id
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             LEFT JOIN categorias cat ON c.categoria_id = cat.id
             LEFT JOIN sla sl ON c.sla_id = sl.id
             LEFT JOIN inventario inv ON c.ativo_id = inv.id
             WHERE c.id = ?", [$id]
        );
    }

    public function findByCodigo($codigo) {
        return $this->db->fetch(
            "SELECT c.*, s.nome as solicitante_nome, s.email as solicitante_email,
                    u.nome as tecnico_nome, cat.nome as categoria_nome
             FROM chamados c
             LEFT JOIN solicitantes s ON c.solicitante_id = s.id
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             LEFT JOIN categorias cat ON c.categoria_id = cat.id
             WHERE c.codigo = ?", [$codigo]
        );
    }

    public function listar($filtros = [], $limite = 50, $offset = 0) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['status'])) {
            $where .= " AND c.status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['prioridade'])) {
            $where .= " AND c.prioridade = ?";
            $params[] = $filtros['prioridade'];
        }
        if (!empty($filtros['tecnico_id'])) {
            $where .= " AND c.tecnico_id = ?";
            $params[] = $filtros['tecnico_id'];
        }
        if (!empty($filtros['categoria_id'])) {
            $where .= " AND c.categoria_id = ?";
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['canal'])) {
            $where .= " AND c.canal = ?";
            $params[] = $filtros['canal'];
        }
        if (!empty($filtros['impacto'])) {
            $where .= " AND c.impacto = ?";
            $params[] = $filtros['impacto'];
        }
        if (!empty($filtros['urgencia'])) {
            $where .= " AND c.urgencia = ?";
            $params[] = $filtros['urgencia'];
        }
        if (!empty($filtros['sla_vencido'])) {
            $where .= " AND (c.sla_resposta_vencido = 1 OR c.sla_resolucao_vencido = 1)";
        }
        if (!empty($filtros['telefone'])) {
            $where .= " AND c.telefone_solicitante = ?";
            $params[] = $filtros['telefone'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (c.titulo LIKE ? OR c.codigo LIKE ? OR c.descricao LIKE ? OR s.nome LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND c.data_abertura >= ?";
            $params[] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND c.data_abertura <= ?";
            $params[] = $filtros['data_fim'] . ' 23:59:59';
        }

        // Ordenação configurável
        $ordenacoes = [
            'recentes' => 'c.data_abertura DESC',
            'antigos'  => 'c.data_abertura ASC',
            'prioridade' => "FIELD(c.prioridade, 'critica', 'alta', 'media', 'baixa'), c.data_abertura DESC",
            'status' => "FIELD(c.status, 'aberto', 'em_analise', 'em_atendimento', 'aguardando_usuario', 'resolvido', 'fechado'), c.data_abertura DESC",
            'titulo' => 'c.titulo ASC',
            'atualizados' => 'c.atualizado_em DESC',
        ];
        $ordem = $ordenacoes[$filtros['ordem'] ?? 'recentes'] ?? $ordenacoes['recentes'];

        $sql = "SELECT c.*, 
                    s.nome as solicitante_nome,
                    u.nome as tecnico_nome,
                    cat.nome as categoria_nome, cat.cor as categoria_cor
                FROM chamados c
                LEFT JOIN solicitantes s ON c.solicitante_id = s.id
                LEFT JOIN usuarios u ON c.tecnico_id = u.id
                LEFT JOIN categorias cat ON c.categoria_id = cat.id
                WHERE {$where}
                ORDER BY {$ordem}
                LIMIT {$limite} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function contar($filtros = []) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['status'])) {
            $where .= " AND status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['prioridade'])) {
            $where .= " AND prioridade = ?";
            $params[] = $filtros['prioridade'];
        }
        if (!empty($filtros['tecnico_id'])) {
            $where .= " AND tecnico_id = ?";
            $params[] = $filtros['tecnico_id'];
        }
        if (!empty($filtros['categoria_id'])) {
            $where .= " AND categoria_id = ?";
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['canal'])) {
            $where .= " AND canal = ?";
            $params[] = $filtros['canal'];
        }
        if (!empty($filtros['impacto'])) {
            $where .= " AND impacto = ?";
            $params[] = $filtros['impacto'];
        }
        if (!empty($filtros['urgencia'])) {
            $where .= " AND urgencia = ?";
            $params[] = $filtros['urgencia'];
        }
        if (!empty($filtros['sla_vencido'])) {
            $where .= " AND (sla_resposta_vencido = 1 OR sla_resolucao_vencido = 1)";
        }
        if (!empty($filtros['telefone'])) {
            $where .= " AND telefone_solicitante = ?";
            $params[] = $filtros['telefone'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (titulo LIKE ? OR codigo LIKE ? OR descricao LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND data_abertura >= ?";
            $params[] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND data_abertura <= ?";
            $params[] = $filtros['data_fim'] . ' 23:59:59';
        }

        return $this->db->count('chamados', $where, $params);
    }

    public function criar($dados) {
        return $this->db->insert('chamados', $dados);
    }

    public function atualizar($id, $dados) {
        return $this->db->update('chamados', $dados, 'id = ?', [$id]);
    }

    public function registrarHistorico($chamadoId, $usuarioId, $campo, $valorAnterior, $valorNovo) {
        return $this->db->insert('chamados_historico', [
            'chamado_id' => $chamadoId,
            'usuario_id' => $usuarioId,
            'campo' => $campo,
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo
        ]);
    }

    public function getHistorico($chamadoId) {
        return $this->db->fetchAll(
            "SELECT h.*, u.nome as usuario_nome 
             FROM chamados_historico h 
             LEFT JOIN usuarios u ON h.usuario_id = u.id 
             WHERE h.chamado_id = ? ORDER BY h.criado_em DESC", [$chamadoId]
        );
    }

    public function getComentarios($chamadoId) {
        return $this->db->fetchAll(
            "SELECT cc.*, u.nome as usuario_nome, u.avatar as usuario_avatar,
                    s.nome as solicitante_nome_ref
             FROM chamados_comentarios cc
             LEFT JOIN usuarios u ON cc.usuario_id = u.id
             LEFT JOIN solicitantes s ON cc.solicitante_id = s.id
             WHERE cc.chamado_id = ? ORDER BY cc.criado_em ASC", [$chamadoId]
        );
    }

    public function adicionarComentario($dados) {
        return $this->db->insert('chamados_comentarios', $dados);
    }

    public function getTags($chamadoId) {
        return $this->db->fetchAll("SELECT * FROM chamados_tags WHERE chamado_id = ?", [$chamadoId]);
    }

    public function adicionarTag($chamadoId, $tag) {
        return $this->db->insert('chamados_tags', ['chamado_id' => $chamadoId, 'tag' => $tag]);
    }

    public function removerTag($chamadoId, $tag) {
        return $this->db->delete('chamados_tags', 'chamado_id = ? AND tag = ?', [$chamadoId, $tag]);
    }

    // Estatísticas
    public function contarPorStatus() {
        return $this->db->fetchAll("SELECT status, COUNT(*) as total FROM chamados GROUP BY status");
    }

    public function contarPorCategoria() {
        return $this->db->fetchAll(
            "SELECT cat.nome, cat.cor, COUNT(*) as total 
             FROM chamados c 
             LEFT JOIN categorias cat ON c.categoria_id = cat.id 
             GROUP BY c.categoria_id ORDER BY total DESC"
        );
    }

    public function contarPorTecnico() {
        return $this->db->fetchAll(
            "SELECT u.nome, COUNT(*) as total, 
                    SUM(CASE WHEN c.status = 'resolvido' OR c.status = 'fechado' THEN 1 ELSE 0 END) as resolvidos
             FROM chamados c 
             INNER JOIN usuarios u ON c.tecnico_id = u.id 
             GROUP BY c.tecnico_id ORDER BY total DESC"
        );
    }

    public function tempoMedioResolucao() {
        return $this->db->fetch(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, data_abertura, data_resolucao)) as horas
             FROM chamados WHERE data_resolucao IS NOT NULL AND status IN ('resolvido','fechado')"
        );
    }

    public function chamadosPorMes($ano = null) {
        $ano = $ano ?: date('Y');
        return $this->db->fetchAll(
            "SELECT MONTH(data_abertura) as mes, COUNT(*) as total 
             FROM chamados WHERE YEAR(data_abertura) = ? GROUP BY MONTH(data_abertura) ORDER BY mes",
            [$ano]
        );
    }

    public function buscarPorTelefoneECodigo($telefone, $codigo) {
        return $this->db->fetch(
            "SELECT c.*, s.nome as solicitante_nome, u.nome as tecnico_nome, cat.nome as categoria_nome
             FROM chamados c
             LEFT JOIN solicitantes s ON c.solicitante_id = s.id
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             LEFT JOIN categorias cat ON c.categoria_id = cat.id
             WHERE c.telefone_solicitante = ? AND c.codigo = ?",
            [$telefone, $codigo]
        );
    }

    public function verificarSLA() {
        return $this->db->fetchAll(
            "SELECT c.*, sl.tempo_resposta, sl.tempo_resolucao,
                    TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) as minutos_aberto,
                    s.nome as solicitante_nome, s.telefone as solicitante_telefone
             FROM chamados c
             INNER JOIN sla sl ON c.sla_id = sl.id
             LEFT JOIN solicitantes s ON c.solicitante_id = s.id
             WHERE c.status NOT IN ('resolvido','fechado')
             HAVING minutos_aberto > sl.tempo_resposta * 0.8 OR minutos_aberto > sl.tempo_resolucao * 0.8"
        );
    }
}
