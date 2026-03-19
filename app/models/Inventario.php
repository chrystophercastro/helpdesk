<?php
/**
 * Model: Inventario
 */
require_once __DIR__ . '/Database.php';

class Inventario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT i.*, u.nome as responsavel_nome 
             FROM inventario i 
             LEFT JOIN usuarios u ON i.responsavel_id = u.id 
             WHERE i.id = ?", [$id]
        );
    }

    public function listar($filtros = []) {
        $where = "1=1";
        $params = [];
        if (!empty($filtros['tipo'])) {
            $where .= " AND i.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['status'])) {
            $where .= " AND i.status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (i.nome LIKE ? OR i.numero_patrimonio LIKE ? OR i.modelo LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        return $this->db->fetchAll(
            "SELECT i.*, u.nome as responsavel_nome 
             FROM inventario i 
             LEFT JOIN usuarios u ON i.responsavel_id = u.id 
             WHERE {$where} ORDER BY i.nome ASC", $params
        );
    }

    public function criar($dados) {
        return $this->db->insert('inventario', $dados);
    }

    public function atualizar($id, $dados) {
        return $this->db->update('inventario', $dados, 'id = ?', [$id]);
    }

    public function deletar($id) {
        return $this->db->delete('inventario', 'id = ?', [$id]);
    }

    public function contarPorTipo() {
        return $this->db->fetchAll("SELECT tipo, COUNT(*) as total FROM inventario GROUP BY tipo ORDER BY total DESC");
    }

    public function contarPorStatus() {
        return $this->db->fetchAll("SELECT status, COUNT(*) as total FROM inventario GROUP BY status");
    }

    public function getChamadosVinculados($ativoId) {
        return $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.status, c.data_abertura 
             FROM chamados c WHERE c.ativo_id = ? ORDER BY c.data_abertura DESC", [$ativoId]
        );
    }
}
