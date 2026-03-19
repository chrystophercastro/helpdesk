<?php
/**
 * Model: BaseConhecimento
 */
require_once __DIR__ . '/Database.php';

class BaseConhecimento {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT bc.*, u.nome as autor_nome, cat.nome as categoria_nome 
             FROM base_conhecimento bc 
             LEFT JOIN usuarios u ON bc.autor_id = u.id 
             LEFT JOIN categorias cat ON bc.categoria_id = cat.id 
             WHERE bc.id = ?", [$id]
        );
    }

    public function listar($filtros = []) {
        $where = "1=1";
        $params = [];
        if (!empty($filtros['categoria_id'])) {
            $where .= " AND bc.categoria_id = ?";
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (bc.titulo LIKE ? OR bc.problema LIKE ? OR bc.solucao LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        if (isset($filtros['publicado'])) {
            $where .= " AND bc.publicado = ?";
            $params[] = $filtros['publicado'];
        }
        return $this->db->fetchAll(
            "SELECT bc.*, u.nome as autor_nome, cat.nome as categoria_nome 
             FROM base_conhecimento bc 
             LEFT JOIN usuarios u ON bc.autor_id = u.id 
             LEFT JOIN categorias cat ON bc.categoria_id = cat.id 
             WHERE {$where} ORDER BY bc.criado_em DESC", $params
        );
    }

    public function criar($dados) {
        return $this->db->insert('base_conhecimento', $dados);
    }

    public function atualizar($id, $dados) {
        return $this->db->update('base_conhecimento', $dados, 'id = ?', [$id]);
    }

    public function deletar($id) {
        return $this->db->delete('base_conhecimento', 'id = ?', [$id]);
    }

    public function incrementarVisualizacao($id) {
        $this->db->query("UPDATE base_conhecimento SET visualizacoes = visualizacoes + 1 WHERE id = ?", [$id]);
    }

    public function marcarUtil($id) {
        $this->db->query("UPDATE base_conhecimento SET util = util + 1 WHERE id = ?", [$id]);
    }

    public function buscar($termo) {
        return $this->db->fetchAll(
            "SELECT bc.*, u.nome as autor_nome, cat.nome as categoria_nome,
                    MATCH(titulo, problema, solucao) AGAINST(? IN BOOLEAN MODE) as relevancia
             FROM base_conhecimento bc 
             LEFT JOIN usuarios u ON bc.autor_id = u.id 
             LEFT JOIN categorias cat ON bc.categoria_id = cat.id 
             WHERE bc.publicado = 1 AND MATCH(titulo, problema, solucao) AGAINST(? IN BOOLEAN MODE)
             ORDER BY relevancia DESC",
            [$termo, $termo]
        );
    }
}
