<?php
/**
 * Model: Compra
 */
require_once __DIR__ . '/Database.php';

class Compra {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT c.*, u.nome as solicitante_nome, u.telefone as solicitante_telefone,
                    a.nome as aprovador_nome
             FROM compras c
             LEFT JOIN usuarios u ON c.solicitante_usuario_id = u.id
             LEFT JOIN usuarios a ON c.aprovador_id = a.id
             WHERE c.id = ?", [$id]
        );
    }

    public function listar($filtros = []) {
        $where = "1=1";
        $params = [];
        if (!empty($filtros['status'])) {
            $where .= " AND c.status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (c.item LIKE ? OR c.codigo LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
        }
        return $this->db->fetchAll(
            "SELECT c.*, u.nome as solicitante_nome, a.nome as aprovador_nome
             FROM compras c
             LEFT JOIN usuarios u ON c.solicitante_usuario_id = u.id
             LEFT JOIN usuarios a ON c.aprovador_id = a.id
             WHERE {$where} ORDER BY c.criado_em DESC", $params
        );
    }

    public function criar($dados) {
        $id = $this->db->insert('compras', $dados);
        $this->registrarHistorico($id, null, 'solicitado', 'Requisição criada');
        return $id;
    }

    public function atualizar($id, $dados) {
        return $this->db->update('compras', $dados, 'id = ?', [$id]);
    }

    public function alterarStatus($id, $status, $usuarioId, $observacao = '') {
        $this->db->update('compras', ['status' => $status], 'id = ?', [$id]);
        $this->registrarHistorico($id, $usuarioId, $status, $observacao);

        // Datas específicas
        if ($status === 'aprovado') {
            $this->db->update('compras', ['data_aprovacao' => date('Y-m-d H:i:s'), 'aprovador_id' => $usuarioId], 'id = ?', [$id]);
        } elseif ($status === 'comprado') {
            $this->db->update('compras', ['data_compra' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        } elseif ($status === 'entregue') {
            $this->db->update('compras', ['data_entrega' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        }
    }

    public function registrarHistorico($compraId, $usuarioId, $status, $observacao) {
        return $this->db->insert('compras_historico', [
            'compra_id' => $compraId,
            'status' => $status,
            'usuario_id' => $usuarioId,
            'observacao' => $observacao
        ]);
    }

    public function getHistorico($compraId) {
        return $this->db->fetchAll(
            "SELECT ch.*, u.nome as usuario_nome 
             FROM compras_historico ch 
             LEFT JOIN usuarios u ON ch.usuario_id = u.id 
             WHERE ch.compra_id = ? ORDER BY ch.criado_em ASC", [$compraId]
        );
    }

    public function contarPorStatus() {
        return $this->db->fetchAll("SELECT status, COUNT(*) as total FROM compras GROUP BY status");
    }

    public function valorTotal($status = null) {
        $where = $status ? "WHERE status = ?" : "";
        $params = $status ? [$status] : [];
        return $this->db->fetchColumn("SELECT COALESCE(SUM(valor_estimado),0) FROM compras {$where}", $params);
    }
}
