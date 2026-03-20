<?php
/**
 * Model: Projeto
 */
require_once __DIR__ . '/Database.php';

class Projeto {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT p.*, u.nome as responsavel_nome 
             FROM projetos p 
             LEFT JOIN usuarios u ON p.responsavel_id = u.id 
             WHERE p.id = ?", [$id]
        );
    }

    public function listar($filtros = [], $deptId = null) {
        $where = "1=1";
        $params = [];
        if ($deptId) {
            $where .= " AND p.departamento_id = ?";
            $params[] = $deptId;
        }
        if (!empty($filtros['status'])) {
            $where .= " AND p.status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
        }
        return $this->db->fetchAll(
            "SELECT p.*, u.nome as responsavel_nome, d.sigla as departamento_sigla, d.cor as departamento_cor,
                    (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id) as total_tarefas,
                    (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id AND t.coluna = 'concluido') as tarefas_concluidas
             FROM projetos p 
             LEFT JOIN usuarios u ON p.responsavel_id = u.id 
             LEFT JOIN departamentos d ON p.departamento_id = d.id
             WHERE {$where} ORDER BY p.criado_em DESC", $params
        );
    }

    public function criar($dados) {
        return $this->db->insert('projetos', $dados);
    }

    public function atualizar($id, $dados) {
        return $this->db->update('projetos', $dados, 'id = ?', [$id]);
    }

    public function deletar($id) {
        return $this->db->delete('projetos', 'id = ?', [$id]);
    }

    public function getEquipe($projetoId) {
        return $this->db->fetchAll(
            "SELECT pe.*, u.nome, u.email, u.avatar, u.cargo 
             FROM projetos_equipe pe 
             INNER JOIN usuarios u ON pe.usuario_id = u.id 
             WHERE pe.projeto_id = ?", [$projetoId]
        );
    }

    public function adicionarMembro($projetoId, $usuarioId, $papel = 'membro') {
        return $this->db->insert('projetos_equipe', [
            'projeto_id' => $projetoId,
            'usuario_id' => $usuarioId,
            'papel' => $papel
        ]);
    }

    public function removerMembro($projetoId, $usuarioId) {
        return $this->db->delete('projetos_equipe', 'projeto_id = ? AND usuario_id = ?', [$projetoId, $usuarioId]);
    }

    public function alterarPapel($projetoId, $usuarioId, $papel) {
        return $this->db->update('projetos_equipe', ['papel' => $papel], 'projeto_id = ? AND usuario_id = ?', [$projetoId, $usuarioId]);
    }

    public function getComentarios($projetoId) {
        return $this->db->fetchAll(
            "SELECT pc.*, u.nome as usuario_nome, u.avatar 
             FROM projetos_comentarios pc 
             LEFT JOIN usuarios u ON pc.usuario_id = u.id 
             WHERE pc.projeto_id = ? ORDER BY pc.criado_em ASC", [$projetoId]
        );
    }

    public function adicionarComentario($projetoId, $usuarioId, $conteudo) {
        return $this->db->insert('projetos_comentarios', [
            'projeto_id' => $projetoId,
            'usuario_id' => $usuarioId,
            'conteudo' => $conteudo
        ]);
    }

    public function atualizarProgresso($projetoId) {
        $stats = $this->db->fetch(
            "SELECT COUNT(*) as total, SUM(CASE WHEN coluna = 'concluido' THEN 1 ELSE 0 END) as concluidas 
             FROM tarefas WHERE projeto_id = ?", [$projetoId]
        );
        $progresso = $stats['total'] > 0 ? round(($stats['concluidas'] / $stats['total']) * 100) : 0;
        $this->db->update('projetos', ['progresso' => $progresso], 'id = ?', [$projetoId]);
        return $progresso;
    }

    public function contarPorStatus($deptId = null) {
        $where = $deptId ? "WHERE departamento_id = ?" : "";
        $params = $deptId ? [$deptId] : [];
        return $this->db->fetchAll("SELECT status, COUNT(*) as total FROM projetos {$where} GROUP BY status", $params);
    }
}
