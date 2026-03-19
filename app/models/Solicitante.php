<?php
/**
 * Model: Solicitante
 */
require_once __DIR__ . '/Database.php';

class Solicitante {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch("SELECT * FROM solicitantes WHERE id = ?", [$id]);
    }

    public function findByTelefone($telefone) {
        return $this->db->fetch("SELECT * FROM solicitantes WHERE telefone = ?", [$telefone]);
    }

    public function findByEmail($email) {
        return $this->db->fetch("SELECT * FROM solicitantes WHERE email = ?", [$email]);
    }

    public function listar($filtros = []) {
        $where = "1=1";
        $params = [];
        if (!empty($filtros['busca'])) {
            $where .= " AND (nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params = [$busca, $busca, $busca];
        }
        return $this->db->fetchAll("SELECT * FROM solicitantes WHERE {$where} ORDER BY nome ASC", $params);
    }

    /**
     * Buscar ou criar solicitante pelo telefone
     */
    public function buscarOuCriar($dados) {
        $telefone = $dados['telefone'];
        $existente = $this->findByTelefone($telefone);
        
        if ($existente) {
            // Atualizar nome e email se necessário
            $this->db->update('solicitantes', [
                'nome' => $dados['nome'],
                'email' => $dados['email']
            ], 'id = ?', [$existente['id']]);
            return $existente['id'];
        }

        return $this->db->insert('solicitantes', [
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'telefone' => $telefone,
            'empresa' => $dados['empresa'] ?? null,
            'departamento' => $dados['departamento'] ?? null
        ]);
    }

    public function incrementarChamados($id) {
        $this->db->query("UPDATE solicitantes SET total_chamados = total_chamados + 1 WHERE id = ?", [$id]);
    }

    public function contarChamadosAbertos($telefone) {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE telefone_solicitante = ? AND status NOT IN ('resolvido','fechado')",
            [$telefone]
        );
    }
}
