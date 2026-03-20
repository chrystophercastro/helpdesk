<?php
/**
 * Model: Departamento
 * Gerenciamento de departamentos do sistema multi-departamentos
 */
require_once __DIR__ . '/Database.php';

class Departamento {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Buscar departamento por ID
     */
    public function findById($id) {
        return $this->db->fetch(
            "SELECT d.*, u.nome as responsavel_nome 
             FROM departamentos d
             LEFT JOIN usuarios u ON d.responsavel_id = u.id
             WHERE d.id = ?", [$id]
        );
    }

    /**
     * Buscar por sigla
     */
    public function findBySigla($sigla) {
        return $this->db->fetch(
            "SELECT * FROM departamentos WHERE sigla = ?", [strtoupper($sigla)]
        );
    }

    /**
     * Listar todos (com filtros)
     */
    public function listar($filtros = []) {
        $where = "1=1";
        $params = [];

        if (isset($filtros['ativo'])) {
            $where .= " AND d.ativo = ?";
            $params[] = (int)$filtros['ativo'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (d.nome LIKE ? OR d.sigla LIKE ? OR d.descricao LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }

        return $this->db->fetchAll(
            "SELECT d.*, u.nome as responsavel_nome,
                    (SELECT COUNT(*) FROM categorias WHERE departamento_id = d.id AND ativo = 1) as total_categorias,
                    (SELECT COUNT(*) FROM chamados WHERE departamento_id = d.id) as total_chamados,
                    (SELECT COUNT(*) FROM chamados WHERE departamento_id = d.id AND status NOT IN ('resolvido','fechado')) as chamados_abertos,
                    (SELECT COUNT(*) FROM usuarios WHERE departamento_id = d.id AND ativo = 1) as total_usuarios
             FROM departamentos d
             LEFT JOIN usuarios u ON d.responsavel_id = u.id
             WHERE {$where}
             ORDER BY d.ordem ASC, d.nome ASC", $params
        );
    }

    /**
     * Listar apenas ativos (para portal, selects, etc.)
     */
    public function listarAtivos() {
        return $this->db->fetchAll(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM categorias WHERE departamento_id = d.id AND tipo = 'chamado' AND ativo = 1) as total_categorias,
                    (SELECT COUNT(*) FROM chamados WHERE departamento_id = d.id AND status NOT IN ('resolvido','fechado')) as chamados_abertos
             FROM departamentos d
             WHERE d.ativo = 1
             ORDER BY d.ordem ASC, d.nome ASC"
        );
    }

    /**
     * Criar departamento
     */
    public function criar($dados) {
        $sigla = strtoupper(trim($dados['sigla']));
        
        // Verificar sigla única
        $existe = $this->db->fetch("SELECT id FROM departamentos WHERE sigla = ?", [$sigla]);
        if ($existe) {
            return ['error' => 'Já existe um departamento com a sigla ' . $sigla];
        }

        $this->db->query(
            "INSERT INTO departamentos (nome, sigla, descricao, icone, cor, email, responsavel_id, ativo, ordem)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)",
            [
                trim($dados['nome']),
                $sigla,
                trim($dados['descricao'] ?? ''),
                trim($dados['icone'] ?? 'fas fa-building'),
                trim($dados['cor'] ?? '#6366F1'),
                trim($dados['email'] ?? '') ?: null,
                !empty($dados['responsavel_id']) ? (int)$dados['responsavel_id'] : null,
                (int)($dados['ordem'] ?? 0)
            ]
        );

        $id = $this->db->getConnection()->lastInsertId();
        return ['success' => true, 'id' => $id];
    }

    /**
     * Atualizar departamento
     */
    public function atualizar($id, $dados) {
        $sigla = strtoupper(trim($dados['sigla']));
        
        // Verificar sigla única (exceto o próprio)
        $existe = $this->db->fetch("SELECT id FROM departamentos WHERE sigla = ? AND id != ?", [$sigla, $id]);
        if ($existe) {
            return ['error' => 'Já existe outro departamento com a sigla ' . $sigla];
        }

        $this->db->query(
            "UPDATE departamentos SET nome = ?, sigla = ?, descricao = ?, icone = ?, cor = ?, email = ?, responsavel_id = ?, ordem = ?, atualizado_em = NOW()
             WHERE id = ?",
            [
                trim($dados['nome']),
                $sigla,
                trim($dados['descricao'] ?? ''),
                trim($dados['icone'] ?? 'fas fa-building'),
                trim($dados['cor'] ?? '#6366F1'),
                trim($dados['email'] ?? '') ?: null,
                !empty($dados['responsavel_id']) ? (int)$dados['responsavel_id'] : null,
                (int)($dados['ordem'] ?? 0),
                $id
            ]
        );

        return ['success' => true];
    }

    /**
     * Ativar/Desativar
     */
    public function toggleAtivo($id) {
        $dept = $this->findById($id);
        if (!$dept) return ['error' => 'Departamento não encontrado'];
        
        $novoStatus = $dept['ativo'] ? 0 : 1;
        $this->db->query("UPDATE departamentos SET ativo = ? WHERE id = ?", [$novoStatus, $id]);
        return ['success' => true, 'ativo' => $novoStatus];
    }

    /**
     * Excluir (soft: desativa; hard: só se não tem chamados)
     */
    public function excluir($id) {
        $chamados = $this->db->fetchColumn("SELECT COUNT(*) FROM chamados WHERE departamento_id = ?", [$id]);
        if ($chamados > 0) {
            // Soft delete - apenas desativa
            $this->db->query("UPDATE departamentos SET ativo = 0 WHERE id = ?", [$id]);
            return ['success' => true, 'modo' => 'desativado', 'msg' => 'Departamento desativado (possui chamados vinculados)'];
        }
        
        // Hard delete
        $this->db->query("UPDATE categorias SET departamento_id = NULL WHERE departamento_id = ?", [$id]);
        $this->db->query("UPDATE usuarios SET departamento_id = NULL WHERE departamento_id = ?", [$id]);
        $this->db->query("DELETE FROM departamentos WHERE id = ?", [$id]);
        return ['success' => true, 'modo' => 'excluido'];
    }

    /**
     * Obter categorias de um departamento
     */
    public function getCategorias($departamentoId) {
        return $this->db->fetchAll(
            "SELECT * FROM categorias WHERE departamento_id = ? AND tipo = 'chamado' AND ativo = 1 ORDER BY nome",
            [$departamentoId]
        );
    }

    /**
     * Estatísticas do departamento
     */
    public function getStats($departamentoId) {
        $stats = [];
        
        $stats['total_chamados'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE departamento_id = ?", [$departamentoId]
        );
        $stats['chamados_abertos'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE departamento_id = ? AND status NOT IN ('resolvido','fechado')", [$departamentoId]
        );
        $stats['chamados_resolvidos'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE departamento_id = ? AND status IN ('resolvido','fechado')", [$departamentoId]
        );
        $stats['por_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as total FROM chamados WHERE departamento_id = ? GROUP BY status", [$departamentoId]
        );
        $stats['por_prioridade'] = $this->db->fetchAll(
            "SELECT prioridade, COUNT(*) as total FROM chamados WHERE departamento_id = ? AND status NOT IN ('resolvido','fechado') GROUP BY prioridade", [$departamentoId]
        );
        $stats['total_categorias'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM categorias WHERE departamento_id = ? AND ativo = 1", [$departamentoId]
        );
        $stats['total_tecnicos'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM usuarios WHERE departamento_id = ? AND ativo = 1", [$departamentoId]
        );

        return $stats;
    }
}
