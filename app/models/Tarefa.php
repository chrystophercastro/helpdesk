<?php
/**
 * Model: Tarefa
 */
require_once __DIR__ . '/Database.php';

class Tarefa {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT t.*, u.nome as responsavel_nome, p.nome as projeto_nome, sp.nome as sprint_nome
             FROM tarefas t
             LEFT JOIN usuarios u ON t.responsavel_id = u.id
             LEFT JOIN projetos p ON t.projeto_id = p.id
             LEFT JOIN sprints sp ON t.sprint_id = sp.id
             WHERE t.id = ?", [$id]
        );
    }

    public function listarPorProjeto($projetoId) {
        return $this->db->fetchAll(
            "SELECT t.*, u.nome as responsavel_nome
             FROM tarefas t
             LEFT JOIN usuarios u ON t.responsavel_id = u.id
             WHERE t.projeto_id = ? ORDER BY t.ordem ASC", [$projetoId]
        );
    }

    public function listarPorColuna($projetoId = null, $deptId = null) {
        $where = "1=1";
        $params = [];
        if ($projetoId) {
            $where .= " AND t.projeto_id = ?";
            $params[] = $projetoId;
        }
        if ($deptId) {
            $where .= " AND p.departamento_id = ?";
            $params[] = $deptId;
        }
        return $this->db->fetchAll(
            "SELECT t.*, u.nome as responsavel_nome, u.avatar as responsavel_avatar, p.nome as projeto_nome
             FROM tarefas t
             LEFT JOIN usuarios u ON t.responsavel_id = u.id
             LEFT JOIN projetos p ON t.projeto_id = p.id
             WHERE {$where} ORDER BY t.coluna, t.ordem ASC", $params
        );
    }

    public function listarPorSprint($sprintId) {
        return $this->db->fetchAll(
            "SELECT t.*, u.nome as responsavel_nome
             FROM tarefas t
             LEFT JOIN usuarios u ON t.responsavel_id = u.id
             WHERE t.sprint_id = ? ORDER BY t.ordem ASC", [$sprintId]
        );
    }

    public function criar($dados) {
        $maxOrdem = $this->db->fetchColumn(
            "SELECT COALESCE(MAX(ordem), 0) FROM tarefas WHERE coluna = ?", 
            [$dados['coluna'] ?? 'backlog']
        );
        $dados['ordem'] = $maxOrdem + 1;
        return $this->db->insert('tarefas', $dados);
    }

    public function atualizar($id, $dados) {
        return $this->db->update('tarefas', $dados, 'id = ?', [$id]);
    }

    public function moverColuna($id, $coluna, $ordem) {
        return $this->db->update('tarefas', [
            'coluna' => $coluna,
            'ordem' => $ordem
        ], 'id = ?', [$id]);
    }

    public function deletar($id) {
        return $this->db->delete('tarefas', 'id = ?', [$id]);
    }

    public function getComentarios($tarefaId) {
        return $this->db->fetchAll(
            "SELECT tc.*, u.nome as usuario_nome, u.avatar 
             FROM tarefas_comentarios tc
             LEFT JOIN usuarios u ON tc.usuario_id = u.id
             WHERE tc.tarefa_id = ? ORDER BY tc.criado_em ASC", [$tarefaId]
        );
    }

    public function adicionarComentario($tarefaId, $usuarioId, $conteudo) {
        return $this->db->insert('tarefas_comentarios', [
            'tarefa_id' => $tarefaId,
            'usuario_id' => $usuarioId,
            'conteudo' => $conteudo
        ]);
    }

    public function getTags($tarefaId) {
        return $this->db->fetchAll("SELECT * FROM tarefas_tags WHERE tarefa_id = ?", [$tarefaId]);
    }

    public function contarPorColuna($projetoId = null) {
        $where = $projetoId ? "WHERE projeto_id = ?" : "";
        $params = $projetoId ? [$projetoId] : [];
        return $this->db->fetchAll("SELECT coluna, COUNT(*) as total FROM tarefas {$where} GROUP BY coluna", $params);
    }

    public function tarefasAtrasadas() {
        return $this->db->fetchAll(
            "SELECT t.*, u.nome as responsavel_nome, p.nome as projeto_nome
             FROM tarefas t
             LEFT JOIN usuarios u ON t.responsavel_id = u.id
             LEFT JOIN projetos p ON t.projeto_id = p.id
             WHERE t.prazo < CURDATE() AND t.coluna != 'concluido'
             ORDER BY t.prazo ASC"
        );
    }
}
