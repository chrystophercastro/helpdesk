<?php
/**
 * Model: Usuario
 */
require_once __DIR__ . '/Database.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch("SELECT * FROM usuarios WHERE id = ?", [$id]);
    }

    public function findByEmail($email) {
        return $this->db->fetch("SELECT * FROM usuarios WHERE email = ?", [$email]);
    }

    public function findByTelefone($telefone) {
        return $this->db->fetch("SELECT * FROM usuarios WHERE telefone = ?", [$telefone]);
    }

    public function listar($filtros = []) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['tipo'])) {
            $where .= " AND tipo = ?";
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['ativo'])) {
            $where .= " AND ativo = ?";
            $params[] = $filtros['ativo'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }

        return $this->db->fetchAll("SELECT * FROM usuarios WHERE {$where} ORDER BY nome ASC", $params);
    }

    public function listarTecnicos() {
        return $this->db->fetchAll("SELECT * FROM usuarios WHERE tipo IN ('tecnico','gestor','admin') AND ativo = 1 ORDER BY nome");
    }

    public function criar($dados) {
        $dados['senha'] = password_hash($dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->db->insert('usuarios', $dados);
    }

    public function atualizar($id, $dados) {
        if (isset($dados['senha']) && !empty($dados['senha'])) {
            $dados['senha'] = password_hash($dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            unset($dados['senha']);
        }
        return $this->db->update('usuarios', $dados, 'id = ?', [$id]);
    }

    public function deletar($id) {
        return $this->db->update('usuarios', ['ativo' => 0], 'id = ?', [$id]);
    }

    public function autenticar($email, $senha) {
        $usuario = $this->findByEmail($email);
        if ($usuario && $usuario['ativo'] && password_verify($senha, $usuario['senha'])) {
            $this->db->update('usuarios', ['ultimo_login' => date('Y-m-d H:i:s')], 'id = ?', [$usuario['id']]);
            return $usuario;
        }
        return false;
    }

    public function contarPorTipo() {
        return $this->db->fetchAll("SELECT tipo, COUNT(*) as total FROM usuarios WHERE ativo = 1 GROUP BY tipo");
    }
}
