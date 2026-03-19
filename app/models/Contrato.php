<?php
/**
 * Model: Contrato
 * Gestão de fornecedores, contratos de manutenção e garantias
 */

require_once __DIR__ . '/../../config/app.php';

class Contrato {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ===================== FORNECEDORES =====================

    public function listarFornecedores($filtros = []) {
        $where = ['f.ativo = 1'];
        $params = [];
        if (!empty($filtros['busca'])) {
            $where[] = '(f.nome LIKE ? OR f.cnpj LIKE ? OR f.contato_email LIKE ?)';
            $params[] = "%{$filtros['busca']}%";
            $params[] = "%{$filtros['busca']}%";
            $params[] = "%{$filtros['busca']}%";
        }
        return $this->db->fetchAll(
            "SELECT f.*, 
                    (SELECT COUNT(*) FROM contratos c WHERE c.fornecedor_id = f.id AND c.status = 'ativo') as contratos_ativos,
                    (SELECT SUM(c.valor) FROM contratos c WHERE c.fornecedor_id = f.id AND c.status = 'ativo') as valor_total
             FROM fornecedores f
             WHERE " . implode(' AND ', $where) . "
             ORDER BY f.nome ASC",
            $params
        );
    }

    public function getFornecedor($id) {
        return $this->db->fetch("SELECT * FROM fornecedores WHERE id = ?", [$id]);
    }

    public function criarFornecedor($dados) {
        return $this->db->insert('fornecedores', $dados);
    }

    public function atualizarFornecedor($id, $dados) {
        return $this->db->update('fornecedores', $dados, 'id = ?', [$id]);
    }

    public function excluirFornecedor($id) {
        return $this->db->update('fornecedores', ['ativo' => 0], 'id = ?', [$id]);
    }

    // ===================== CONTRATOS =====================

    public function listarContratos($filtros = []) {
        $where = ['1=1'];
        $params = [];
        if (!empty($filtros['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = 'c.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['fornecedor_id'])) {
            $where[] = 'c.fornecedor_id = ?';
            $params[] = $filtros['fornecedor_id'];
        }
        if (!empty($filtros['busca'])) {
            $where[] = '(c.titulo LIKE ? OR c.numero LIKE ? OR f.nome LIKE ?)';
            $params[] = "%{$filtros['busca']}%";
            $params[] = "%{$filtros['busca']}%";
            $params[] = "%{$filtros['busca']}%";
        }
        return $this->db->fetchAll(
            "SELECT c.*, f.nome as fornecedor_nome, f.contato_email as fornecedor_email,
                    DATEDIFF(c.data_fim, CURDATE()) as dias_restantes,
                    (SELECT COUNT(*) FROM contrato_ativos ca WHERE ca.contrato_id = c.id) as total_ativos
             FROM contratos c
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.data_fim ASC",
            $params
        );
    }

    public function getContrato($id) {
        $contrato = $this->db->fetch(
            "SELECT c.*, f.nome as fornecedor_nome, f.cnpj as fornecedor_cnpj,
                    f.contato_nome, f.contato_email, f.contato_telefone,
                    DATEDIFF(c.data_fim, CURDATE()) as dias_restantes
             FROM contratos c
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
             WHERE c.id = ?", [$id]
        );
        if ($contrato) {
            $contrato['ativos'] = $this->db->fetchAll(
                "SELECT i.id, i.nome, i.tipo, i.numero_patrimonio, i.numero_serie, i.status
                 FROM contrato_ativos ca
                 INNER JOIN inventario i ON ca.ativo_id = i.id
                 WHERE ca.contrato_id = ?", [$id]
            );
        }
        return $contrato;
    }

    public function criarContrato($dados) {
        $ativos = $dados['ativos'] ?? [];
        unset($dados['ativos']);
        $this->db->insert('contratos', $dados);
        $contratoId = $this->db->lastInsertId();
        foreach ($ativos as $ativoId) {
            $this->db->insert('contrato_ativos', ['contrato_id' => $contratoId, 'ativo_id' => (int)$ativoId]);
        }
        return $contratoId;
    }

    public function atualizarContrato($id, $dados) {
        $ativos = $dados['ativos'] ?? null;
        unset($dados['ativos']);
        $this->db->update('contratos', $dados, 'id = ?', [$id]);
        if ($ativos !== null) {
            $this->db->query("DELETE FROM contrato_ativos WHERE contrato_id = ?", [$id]);
            foreach ($ativos as $ativoId) {
                $this->db->insert('contrato_ativos', ['contrato_id' => $id, 'ativo_id' => (int)$ativoId]);
            }
        }
        return true;
    }

    public function excluirContrato($id) {
        $this->db->query("DELETE FROM contrato_ativos WHERE contrato_id = ?", [$id]);
        return $this->db->query("DELETE FROM contratos WHERE id = ?", [$id]);
    }

    // ===================== ALERTAS DE VENCIMENTO =====================

    public function getContratosVencendo($dias = 30) {
        return $this->db->fetchAll(
            "SELECT c.*, f.nome as fornecedor_nome, f.contato_email,
                    DATEDIFF(c.data_fim, CURDATE()) as dias_restantes
             FROM contratos c
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
             WHERE c.status = 'ativo'
             AND c.data_fim IS NOT NULL
             AND c.data_fim <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND c.data_fim >= CURDATE()
             ORDER BY c.data_fim ASC",
            [$dias]
        );
    }

    public function getContratosVencidos() {
        return $this->db->fetchAll(
            "SELECT c.*, f.nome as fornecedor_nome,
                    DATEDIFF(CURDATE(), c.data_fim) as dias_vencido
             FROM contratos c
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
             WHERE c.status = 'ativo' AND c.data_fim < CURDATE()
             ORDER BY c.data_fim ASC"
        );
    }

    public function atualizarStatusVencidos() {
        return $this->db->query(
            "UPDATE contratos SET status = 'vencido' 
             WHERE status = 'ativo' AND data_fim < CURDATE()"
        );
    }

    // ===================== EQUIPAMENTOS COM GARANTIA =====================

    public function getAtivosSemContrato() {
        return $this->db->fetchAll(
            "SELECT i.id, i.nome, i.tipo, i.numero_patrimonio, i.numero_serie, i.status
             FROM inventario i
             WHERE i.status = 'ativo'
             AND i.id NOT IN (
                SELECT ca.ativo_id FROM contrato_ativos ca
                INNER JOIN contratos c ON ca.contrato_id = c.id
                WHERE c.status = 'ativo'
             )
             ORDER BY i.nome"
        );
    }

    public function getGarantiaAtivo($ativoId) {
        return $this->db->fetchAll(
            "SELECT c.*, f.nome as fornecedor_nome,
                    DATEDIFF(c.data_fim, CURDATE()) as dias_restantes
             FROM contrato_ativos ca
             INNER JOIN contratos c ON ca.contrato_id = c.id
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
             WHERE ca.ativo_id = ? AND c.tipo IN ('garantia','manutencao')
             ORDER BY c.data_fim DESC", [$ativoId]
        );
    }

    // ===================== DASHBOARD =====================

    public function getOverview() {
        $totalFornecedores = $this->db->count('fornecedores', 'ativo = 1');
        $totalContratos = $this->db->count('contratos', 'status = ?', ['ativo']);

        $valorTotal = $this->db->fetch(
            "SELECT SUM(valor) as total FROM contratos WHERE status = 'ativo'"
        );

        $vencendo30 = count($this->getContratosVencendo(30));
        $vencidos = count($this->getContratosVencidos());

        $porTipo = $this->db->fetchAll(
            "SELECT tipo, COUNT(*) as total, SUM(valor) as valor
             FROM contratos WHERE status = 'ativo'
             GROUP BY tipo ORDER BY total DESC"
        );

        $valorMensal = $this->db->fetch(
            "SELECT SUM(CASE recorrencia
                WHEN 'mensal' THEN valor
                WHEN 'trimestral' THEN valor / 3
                WHEN 'semestral' THEN valor / 6
                WHEN 'anual' THEN valor / 12
                ELSE 0
             END) as total
             FROM contratos WHERE status = 'ativo' AND recorrencia != 'unico'"
        );

        return [
            'fornecedores' => $totalFornecedores,
            'contratos_ativos' => $totalContratos,
            'valor_total' => round($valorTotal['total'] ?? 0, 2),
            'valor_mensal' => round($valorMensal['total'] ?? 0, 2),
            'vencendo_30' => $vencendo30,
            'vencidos' => $vencidos,
            'por_tipo' => $porTipo
        ];
    }
}
