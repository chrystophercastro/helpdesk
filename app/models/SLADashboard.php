<?php
/**
 * Model: SLADashboard
 * Métricas avançadas de SLA, MTTR, compliance e alertas
 * Suporta filtro por departamento (gestor vê só sua área)
 */

require_once __DIR__ . '/../../config/app.php';

class SLADashboard {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Monta cláusula WHERE para departamento
     */
    private function deptClause($alias = 'c', $deptId = null) {
        if (!$deptId) return ['', []];
        return [" AND {$alias}.departamento_id = ?", [(int)$deptId]];
    }

    // ===================== OVERVIEW GERAL =====================

    public function getOverview($deptId = null) {
        [$dw, $dp] = $this->deptClause('c', $deptId);

        // Taxa de compliance geral (resolução)
        $compliance = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_resolucao, c.data_fechamento)) <= s.tempo_resolucao THEN 1 ELSE 0 END) as dentro_sla
             FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             WHERE c.status IN ('resolvido','fechado')
             AND c.data_resolucao IS NOT NULL{$dw}",
            $dp
        );
        $taxaResolucao = $compliance['total'] > 0 ? round(($compliance['dentro_sla'] / $compliance['total']) * 100, 1) : 100;

        // Taxa de compliance resposta
        $compResp = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_primeira_resposta) <= s.tempo_resposta THEN 1 ELSE 0 END) as dentro_sla
             FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             WHERE c.data_primeira_resposta IS NOT NULL{$dw}",
            $dp
        );
        $taxaResposta = $compResp['total'] > 0 ? round(($compResp['dentro_sla'] / $compResp['total']) * 100, 1) : 100;

        // MTTR geral (em minutos) — sem alias, tabela direta
        [$dwNoAlias, $dpNoAlias] = $this->deptClause('chamados', $deptId);
        $mttr = $this->db->fetch(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, data_abertura, data_resolucao)) as mttr
             FROM chamados 
             WHERE status IN ('resolvido','fechado') AND data_resolucao IS NOT NULL" .
             str_replace('chamados.departamento_id', 'departamento_id', $dwNoAlias),
            $dpNoAlias
        );

        // MTTR último mês
        $mttrMes = $this->db->fetch(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, data_abertura, data_resolucao)) as mttr
             FROM chamados 
             WHERE status IN ('resolvido','fechado') AND data_resolucao IS NOT NULL
             AND data_resolucao >= DATE_SUB(NOW(), INTERVAL 30 DAY)" .
             str_replace('chamados.departamento_id', 'departamento_id', $dwNoAlias),
            $dpNoAlias
        );

        // Chamados abertos com SLA
        $abertos = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > s.tempo_resolucao THEN 1 ELSE 0 END) as estourados,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > (s.tempo_resolucao * 0.8) AND TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) <= s.tempo_resolucao THEN 1 ELSE 0 END) as em_risco
             FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             WHERE c.status NOT IN ('resolvido','fechado','cancelado'){$dw}",
            $dp
        );

        return [
            'taxa_resolucao' => $taxaResolucao,
            'taxa_resposta' => $taxaResposta,
            'mttr' => round($mttr['mttr'] ?? 0),
            'mttr_mes' => round($mttrMes['mttr'] ?? 0),
            'abertos_total' => (int)($abertos['total'] ?? 0),
            'estourados' => (int)($abertos['estourados'] ?? 0),
            'em_risco' => (int)($abertos['em_risco'] ?? 0),
            'dentro_prazo' => (int)(($abertos['total'] ?? 0) - ($abertos['estourados'] ?? 0) - ($abertos['em_risco'] ?? 0))
        ];
    }

    // ===================== SEMÁFORO POR PRIORIDADE =====================

    public function getSemaforo($deptId = null) {
        [$dw, $dp] = $this->deptClause('c', $deptId);
        // Para queries sem alias
        $deptSimple = $deptId ? " AND departamento_id = ?" : "";
        $dpSimple = $deptId ? [(int)$deptId] : [];

        $prioridades = ['critica', 'alta', 'media', 'baixa'];
        $resultado = [];

        foreach ($prioridades as $p) {
            // SLA config
            $sla = $this->db->fetch(
                "SELECT tempo_resposta, tempo_resolucao FROM sla WHERE prioridade = ? AND categoria_id IS NULL AND ativo = 1",
                [$p]
            );

            // Chamados abertos desta prioridade
            $stats = $this->db->fetch(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > s.tempo_resolucao THEN 1 ELSE 0 END) as vermelho,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > (s.tempo_resolucao * 0.8) AND TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) <= s.tempo_resolucao THEN 1 ELSE 0 END) as amarelo,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) <= (s.tempo_resolucao * 0.8) THEN 1 ELSE 0 END) as verde
                 FROM chamados c
                 INNER JOIN sla s ON c.sla_id = s.id
                 WHERE c.prioridade = ? AND c.status NOT IN ('resolvido','fechado','cancelado'){$dw}",
                array_merge([$p], $dp)
            );

            // MTTR desta prioridade
            $mttr = $this->db->fetch(
                "SELECT AVG(TIMESTAMPDIFF(MINUTE, data_abertura, data_resolucao)) as mttr
                 FROM chamados 
                 WHERE prioridade = ? AND status IN ('resolvido','fechado') AND data_resolucao IS NOT NULL{$deptSimple}",
                array_merge([$p], $dpSimple)
            );

            // Compliance desta prioridade
            $comp = $this->db->fetch(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_resolucao, c.data_fechamento)) <= s.tempo_resolucao THEN 1 ELSE 0 END) as ok
                 FROM chamados c
                 INNER JOIN sla s ON c.sla_id = s.id
                 WHERE c.prioridade = ? AND c.status IN ('resolvido','fechado') AND c.data_resolucao IS NOT NULL{$dw}",
                array_merge([$p], $dp)
            );

            $resultado[] = [
                'prioridade' => $p,
                'sla_resposta' => (int)($sla['tempo_resposta'] ?? 0),
                'sla_resolucao' => (int)($sla['tempo_resolucao'] ?? 0),
                'abertos' => (int)($stats['total'] ?? 0),
                'vermelho' => (int)($stats['vermelho'] ?? 0),
                'amarelo' => (int)($stats['amarelo'] ?? 0),
                'verde' => (int)($stats['verde'] ?? 0),
                'mttr' => round($mttr['mttr'] ?? 0),
                'compliance' => $comp['total'] > 0 ? round(($comp['ok'] / $comp['total']) * 100, 1) : 100
            ];
        }
        return $resultado;
    }

    // ===================== CHAMADOS EM RISCO =====================

    public function getChamadosEmRisco($limite = 20, $deptId = null) {
        [$dw, $dp] = $this->deptClause('c', $deptId);
        return $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.prioridade, c.status, c.data_abertura,
                    c.data_primeira_resposta, c.tecnico_id,
                    s.tempo_resposta, s.tempo_resolucao, s.nome as sla_nome,
                    u.nome as tecnico_nome, cat.nome as categoria_nome,
                    TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) as minutos_decorridos,
                    ROUND(TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) / s.tempo_resolucao * 100, 1) as percentual_sla
             FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             LEFT JOIN categorias cat ON c.categoria_id = cat.id
             WHERE c.status NOT IN ('resolvido','fechado','cancelado'){$dw}
             HAVING percentual_sla >= 60
             ORDER BY percentual_sla DESC
             LIMIT ?",
            array_merge($dp, [$limite])
        );
    }

    // ===================== MTTR POR DIMENSÃO =====================

    public function getMTTR($dimensao = 'prioridade', $dias = 30, $deptId = null) {
        [$dw, $dp] = $this->deptClause('c', $deptId);

        $groupBy = match($dimensao) {
            'prioridade' => 'c.prioridade',
            'categoria' => 'cat.nome',
            'tecnico' => 'u.nome',
            default => 'c.prioridade'
        };

        $select = match($dimensao) {
            'prioridade' => 'c.prioridade as dimensao',
            'categoria' => 'COALESCE(cat.nome, "Sem categoria") as dimensao',
            'tecnico' => 'COALESCE(u.nome, "Sem técnico") as dimensao',
            default => 'c.prioridade as dimensao'
        };

        return $this->db->fetchAll(
            "SELECT $select,
                    COUNT(*) as total,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao))) as mttr,
                    ROUND(MIN(TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao))) as min_resolucao,
                    ROUND(MAX(TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao))) as max_resolucao,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_primeira_resposta, c.data_resolucao)))) as mttr_resposta
             FROM chamados c
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             LEFT JOIN categorias cat ON c.categoria_id = cat.id
             WHERE c.status IN ('resolvido','fechado') AND c.data_resolucao IS NOT NULL
             AND c.data_resolucao >= DATE_SUB(NOW(), INTERVAL ? DAY){$dw}
             GROUP BY $groupBy
             ORDER BY mttr ASC",
            array_merge([$dias], $dp)
        );
    }

    // ===================== TENDÊNCIA SLA (últimos N meses) =====================

    public function getTendencia($meses = 6, $deptId = null) {
        [$dw, $dp] = $this->deptClause('c', $deptId);
        return $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(c.data_resolucao, '%Y-%m') as mes,
                COUNT(*) as total,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao) <= s.tempo_resolucao THEN 1 ELSE 0 END) as dentro_sla,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao))) as mttr_medio
             FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             WHERE c.status IN ('resolvido','fechado') AND c.data_resolucao IS NOT NULL
             AND c.data_resolucao >= DATE_SUB(NOW(), INTERVAL ? MONTH){$dw}
             GROUP BY DATE_FORMAT(c.data_resolucao, '%Y-%m')
             ORDER BY mes ASC",
            array_merge([$meses], $dp)
        );
    }

    // ===================== TOP VIOLAÇÕES =====================

    public function getTopViolacoes($limite = 10, $deptId = null) {
        [$dw, $dp] = $this->deptClause('c', $deptId);
        return $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.prioridade, c.status,
                    c.data_abertura, c.data_resolucao,
                    s.tempo_resolucao, s.nome as sla_nome,
                    u.nome as tecnico_nome, cat.nome as categoria_nome,
                    TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_resolucao, NOW())) as minutos_total,
                    TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_resolucao, NOW())) - s.tempo_resolucao as minutos_excedido
             FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             LEFT JOIN categorias cat ON c.categoria_id = cat.id
             WHERE TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_resolucao, NOW())) > s.tempo_resolucao{$dw}
             ORDER BY minutos_excedido DESC
             LIMIT ?",
            array_merge($dp, [$limite])
        );
    }

    // ===================== SLA POR TÉCNICO =====================

    public function getCompliancePorTecnico($deptId = null) {
        [$dw, $dp] = $this->deptClause('c', $deptId);
        return $this->db->fetchAll(
            "SELECT u.nome as tecnico,
                    COUNT(*) as total,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao) <= s.tempo_resolucao THEN 1 ELSE 0 END) as dentro_sla,
                    ROUND(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao) <= s.tempo_resolucao THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as taxa,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, c.data_abertura, c.data_resolucao))) as mttr
             FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             INNER JOIN usuarios u ON c.tecnico_id = u.id
             WHERE c.status IN ('resolvido','fechado') AND c.data_resolucao IS NOT NULL{$dw}
             GROUP BY u.id, u.nome
             ORDER BY taxa DESC",
            $dp
        );
    }

    // ===================== REGRAS SLA ATIVAS =====================

    public function getRegras() {
        return $this->db->fetchAll(
            "SELECT s.*, c.nome as categoria_nome
             FROM sla s
             LEFT JOIN categorias c ON s.categoria_id = c.id
             WHERE s.ativo = 1
             ORDER BY s.categoria_id IS NULL DESC, c.nome, FIELD(s.prioridade,'critica','alta','media','baixa')"
        );
    }
}
