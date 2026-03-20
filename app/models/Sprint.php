<?php
/**
 * Model: Sprint
 */
require_once __DIR__ . '/Database.php';

class Sprint {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT s.*, p.nome as projeto_nome 
             FROM sprints s 
             LEFT JOIN projetos p ON s.projeto_id = p.id 
             WHERE s.id = ?", [$id]
        );
    }

    public function listar($projetoId = null, $deptId = null) {
        $where = "1=1";
        $params = [];
        if ($projetoId) {
            $where .= " AND s.projeto_id = ?";
            $params[] = $projetoId;
        }
        if ($deptId) {
            $where .= " AND p.departamento_id = ?";
            $params[] = $deptId;
        }
        return $this->db->fetchAll(
            "SELECT s.*, p.nome as projeto_nome,
                    (SELECT COUNT(*) FROM tarefas t WHERE t.sprint_id = s.id) as total_tarefas,
                    (SELECT COUNT(*) FROM tarefas t WHERE t.sprint_id = s.id AND t.coluna = 'concluido') as tarefas_concluidas,
                    (SELECT COALESCE(SUM(t.pontos),0) FROM tarefas t WHERE t.sprint_id = s.id) as total_pontos,
                    (SELECT COALESCE(SUM(t.pontos),0) FROM tarefas t WHERE t.sprint_id = s.id AND t.coluna = 'concluido') as pontos_concluidos
             FROM sprints s 
             LEFT JOIN projetos p ON s.projeto_id = p.id 
             WHERE {$where} ORDER BY s.data_inicio DESC", $params
        );
    }

    public function criar($dados) {
        return $this->db->insert('sprints', $dados);
    }

    public function atualizar($id, $dados) {
        return $this->db->update('sprints', $dados, 'id = ?', [$id]);
    }

    public function deletar($id) {
        // Remove sprint_id das tarefas
        $this->db->update('tarefas', ['sprint_id' => null], 'sprint_id = ?', [$id]);
        return $this->db->delete('sprints', 'id = ?', [$id]);
    }

    public function sprintAtiva($projetoId) {
        return $this->db->fetch(
            "SELECT * FROM sprints WHERE projeto_id = ? AND status = 'ativa' LIMIT 1", [$projetoId]
        );
    }

    public function getBurndownData($sprintId) {
        $sprint = $this->findById($sprintId);
        if (!$sprint) return [];

        $inicio = new DateTime($sprint['data_inicio']);
        $fim = new DateTime($sprint['data_fim']);
        $totalPontos = $this->db->fetchColumn(
            "SELECT COALESCE(SUM(pontos),0) FROM tarefas WHERE sprint_id = ?", [$sprintId]
        );

        $dados = [];
        $periodo = new DatePeriod($inicio, new DateInterval('P1D'), $fim->modify('+1 day'));
        
        foreach ($periodo as $dia) {
            $dataStr = $dia->format('Y-m-d');
            $pontosFeitos = $this->db->fetchColumn(
                "SELECT COALESCE(SUM(pontos),0) FROM tarefas 
                 WHERE sprint_id = ? AND coluna = 'concluido' AND DATE(atualizado_em) <= ?",
                [$sprintId, $dataStr]
            );
            $dados[] = [
                'data' => $dia->format('d/m'),
                'ideal' => $totalPontos,
                'real' => $totalPontos - $pontosFeitos
            ];
        }

        // Calcular linha ideal
        $totalDias = count($dados);
        foreach ($dados as $i => &$d) {
            $d['ideal'] = round($totalPontos - ($totalPontos / max($totalDias - 1, 1)) * $i, 1);
        }

        return $dados;
    }

    public function getVelocidade($projetoId, $ultimasSprints = 5) {
        return $this->db->fetchAll(
            "SELECT s.nome,
                    COALESCE(SUM(CASE WHEN t.coluna = 'concluido' THEN t.pontos ELSE 0 END), 0) as pontos
             FROM sprints s
             LEFT JOIN tarefas t ON t.sprint_id = s.id
             WHERE s.projeto_id = ? AND s.status = 'concluida'
             GROUP BY s.id ORDER BY s.data_fim DESC LIMIT ?",
            [$projetoId, $ultimasSprints]
        );
    }
}
