<?php
/**
 * Model: Financeiro
 * Notas fiscais, contas a pagar, boletos, fornecedores + integração SEFAZ
 */
require_once __DIR__ . '/Database.php';

class Financeiro {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ========================================
    // FORNECEDORES
    // ========================================

    public function listarFornecedores($filtros = []) {
        $where = ['1=1'];
        $params = [];
        if (!empty($filtros['busca'])) {
            $where[] = '(razao_social LIKE ? OR nome_fantasia LIKE ? OR cnpj LIKE ? OR cpf LIKE ?)';
            $b = '%' . $filtros['busca'] . '%';
            array_push($params, $b, $b, $b, $b);
        }
        if (!empty($filtros['status'])) {
            $where[] = 'status = ?';
            $params[] = $filtros['status'];
        }
        return $this->db->fetchAll(
            "SELECT * FROM fornecedores WHERE " . implode(' AND ', $where) . " ORDER BY razao_social",
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

    public function getFornecedorPorCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return $this->db->fetch(
            "SELECT * FROM fornecedores WHERE REPLACE(REPLACE(REPLACE(cnpj,'.',''),'/',''),'-','') = ?",
            [$cnpj]
        );
    }

    // ========================================
    // NOTAS FISCAIS
    // ========================================

    public function listarNotas($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['tipo'])) { $where[] = 'nf.tipo = ?'; $params[] = $filtros['tipo']; }
        if (!empty($filtros['natureza'])) { $where[] = 'nf.natureza = ?'; $params[] = $filtros['natureza']; }
        if (!empty($filtros['status'])) { $where[] = 'nf.status = ?'; $params[] = $filtros['status']; }
        if (!empty($filtros['manifesto_status'])) { $where[] = 'nf.manifesto_status = ?'; $params[] = $filtros['manifesto_status']; }
        if (!empty($filtros['fornecedor_id'])) { $where[] = 'nf.fornecedor_id = ?'; $params[] = (int)$filtros['fornecedor_id']; }
        if (!empty($filtros['data_inicio'])) { $where[] = 'nf.data_emissao >= ?'; $params[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim'])) { $where[] = 'nf.data_emissao <= ?'; $params[] = $filtros['data_fim']; }
        if (!empty($filtros['busca'])) {
            $where[] = '(nf.numero LIKE ? OR nf.chave_acesso LIKE ? OR nf.emitente_razao LIKE ? OR nf.emitente_cnpj LIKE ?)';
            $b = '%' . $filtros['busca'] . '%';
            array_push($params, $b, $b, $b, $b);
        }

        return $this->db->fetchAll(
            "SELECT nf.*, f.razao_social as fornecedor_nome, f.nome_fantasia as fornecedor_fantasia,
                    d.sigla as departamento_sigla, d.cor as departamento_cor,
                    u.nome as criado_por_nome
             FROM notas_fiscais nf
             LEFT JOIN fornecedores f ON f.id = nf.fornecedor_id
             LEFT JOIN departamentos d ON d.id = nf.departamento_id
             LEFT JOIN usuarios u ON u.id = nf.criado_por
             WHERE " . implode(' AND ', $where) . "
             ORDER BY nf.data_emissao DESC, nf.id DESC",
            $params
        );
    }

    public function getNota($id) {
        $nota = $this->db->fetch(
            "SELECT nf.*, f.razao_social as fornecedor_nome, f.nome_fantasia as fornecedor_fantasia,
                    f.cnpj as fornecedor_cnpj,
                    d.nome as departamento_nome, d.sigla as departamento_sigla,
                    u.nome as criado_por_nome
             FROM notas_fiscais nf
             LEFT JOIN fornecedores f ON f.id = nf.fornecedor_id
             LEFT JOIN departamentos d ON d.id = nf.departamento_id
             LEFT JOIN usuarios u ON u.id = nf.criado_por
             WHERE nf.id = ?",
            [$id]
        );
        if ($nota) {
            $nota['itens'] = $this->db->fetchAll(
                "SELECT * FROM notas_fiscais_itens WHERE nota_fiscal_id = ? ORDER BY numero_item",
                [$id]
            );
        }
        return $nota;
    }

    public function criarNota($dados) {
        $itens = $dados['itens'] ?? [];
        unset($dados['itens']);
        $id = $this->db->insert('notas_fiscais', $dados);
        foreach ($itens as $item) {
            $item['nota_fiscal_id'] = $id;
            $this->db->insert('notas_fiscais_itens', $item);
        }
        return $id;
    }

    public function atualizarNota($id, $dados) {
        $itens = $dados['itens'] ?? null;
        unset($dados['itens']);
        $this->db->update('notas_fiscais', $dados, 'id = ?', [$id]);
        if ($itens !== null) {
            $this->db->delete('notas_fiscais_itens', 'nota_fiscal_id = ?', [$id]);
            foreach ($itens as $item) {
                $item['nota_fiscal_id'] = $id;
                $this->db->insert('notas_fiscais_itens', $item);
            }
        }
        return true;
    }

    public function getNotaPorChave($chaveAcesso) {
        return $this->db->fetch(
            "SELECT * FROM notas_fiscais WHERE chave_acesso = ?",
            [$chaveAcesso]
        );
    }

    // ========================================
    // CONTAS A PAGAR
    // ========================================

    public function listarContas($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['status'])) { $where[] = 'cp.status = ?'; $params[] = $filtros['status']; }
        if (!empty($filtros['categoria'])) { $where[] = 'cp.categoria = ?'; $params[] = $filtros['categoria']; }
        if (!empty($filtros['fornecedor_id'])) { $where[] = 'cp.fornecedor_id = ?'; $params[] = (int)$filtros['fornecedor_id']; }
        if (!empty($filtros['vencimento_inicio'])) { $where[] = 'cp.data_vencimento >= ?'; $params[] = $filtros['vencimento_inicio']; }
        if (!empty($filtros['vencimento_fim'])) { $where[] = 'cp.data_vencimento <= ?'; $params[] = $filtros['vencimento_fim']; }
        if (!empty($filtros['busca'])) {
            $where[] = '(cp.descricao LIKE ? OR cp.codigo LIKE ? OR cp.codigo_barras LIKE ?)';
            $b = '%' . $filtros['busca'] . '%';
            array_push($params, $b, $b, $b);
        }

        // Status vencido automático
        $this->db->query(
            "UPDATE contas_pagar SET status = 'vencido' WHERE status = 'pendente' AND data_vencimento < CURDATE()"
        );

        return $this->db->fetchAll(
            "SELECT cp.*, f.razao_social as fornecedor_nome, f.nome_fantasia as fornecedor_fantasia,
                    nf.numero as nf_numero, nf.chave_acesso as nf_chave,
                    d.sigla as departamento_sigla, d.cor as departamento_cor,
                    u.nome as criado_por_nome
             FROM contas_pagar cp
             LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
             LEFT JOIN notas_fiscais nf ON nf.id = cp.nota_fiscal_id
             LEFT JOIN departamentos d ON d.id = cp.departamento_id
             LEFT JOIN usuarios u ON u.id = cp.criado_por
             WHERE " . implode(' AND ', $where) . "
             ORDER BY cp.data_vencimento ASC, cp.id DESC",
            $params
        );
    }

    public function getConta($id) {
        return $this->db->fetch(
            "SELECT cp.*, f.razao_social as fornecedor_nome, f.nome_fantasia as fornecedor_fantasia,
                    f.cnpj as fornecedor_cnpj, f.pix as fornecedor_pix,
                    nf.numero as nf_numero, nf.chave_acesso as nf_chave, nf.valor_total as nf_valor,
                    d.nome as departamento_nome,
                    uc.nome as criado_por_nome, ua.nome as aprovado_por_nome, up.nome as pago_por_nome
             FROM contas_pagar cp
             LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
             LEFT JOIN notas_fiscais nf ON nf.id = cp.nota_fiscal_id
             LEFT JOIN departamentos d ON d.id = cp.departamento_id
             LEFT JOIN usuarios uc ON uc.id = cp.criado_por
             LEFT JOIN usuarios ua ON ua.id = cp.aprovado_por
             LEFT JOIN usuarios up ON up.id = cp.pago_por
             WHERE cp.id = ?",
            [$id]
        );
    }

    public function criarConta($dados) {
        $dados['codigo'] = $this->gerarCodigoConta();
        return $this->db->insert('contas_pagar', $dados);
    }

    public function atualizarConta($id, $dados) {
        return $this->db->update('contas_pagar', $dados, 'id = ?', [$id]);
    }

    public function aprovarConta($id, $aprovadoPor) {
        return $this->db->update('contas_pagar', [
            'aprovado_por' => $aprovadoPor
        ], 'id = ?', [$id]);
    }

    public function pagarConta($id, $dados) {
        $dados['status'] = 'pago';
        $dados['data_pagamento'] = $dados['data_pagamento'] ?? date('Y-m-d');
        return $this->db->update('contas_pagar', $dados, 'id = ?', [$id]);
    }

    private function gerarCodigoConta() {
        return 'CP-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    // ========================================
    // ESTATÍSTICAS
    // ========================================

    public function getStats() {
        $hoje = date('Y-m-d');
        $mesAtual = date('Y-m');
        return [
            'notas' => [
                'total' => $this->db->fetchColumn("SELECT COUNT(*) FROM notas_fiscais") ?: 0,
                'pendentes_manifesto' => $this->db->fetchColumn("SELECT COUNT(*) FROM notas_fiscais WHERE manifesto_status = 'pendente'") ?: 0,
                'valor_mes' => $this->db->fetchColumn("SELECT COALESCE(SUM(valor_total),0) FROM notas_fiscais WHERE DATE_FORMAT(data_emissao,'%Y-%m') = ?", [$mesAtual]) ?: 0,
            ],
            'contas' => [
                'pendentes' => $this->db->fetchColumn("SELECT COUNT(*) FROM contas_pagar WHERE status = 'pendente'") ?: 0,
                'vencidas' => $this->db->fetchColumn("SELECT COUNT(*) FROM contas_pagar WHERE status = 'vencido'") ?: 0,
                'vencendo_7dias' => $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM contas_pagar WHERE status = 'pendente' AND data_vencimento BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY)",
                    [$hoje, $hoje]
                ) ?: 0,
                'total_pendente' => $this->db->fetchColumn("SELECT COALESCE(SUM(valor_original),0) FROM contas_pagar WHERE status IN ('pendente','vencido')") ?: 0,
                'pago_mes' => $this->db->fetchColumn(
                    "SELECT COALESCE(SUM(valor_pago),0) FROM contas_pagar WHERE status = 'pago' AND DATE_FORMAT(data_pagamento,'%Y-%m') = ?",
                    [$mesAtual]
                ) ?: 0,
            ],
            'fornecedores' => [
                'total' => $this->db->fetchColumn("SELECT COUNT(*) FROM fornecedores WHERE status = 'ativo'") ?: 0,
            ]
        ];
    }

    // ========================================
    // HISTÓRICO
    // ========================================

    public function registrarHistorico($tipo, $id, $acao, $campo = null, $anterior = null, $novo = null, $usuarioId = null) {
        $this->db->insert('financeiro_historico', [
            'entidade_tipo' => $tipo,
            'entidade_id' => $id,
            'acao' => $acao,
            'campo_alterado' => $campo,
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
            'usuario_id' => $usuarioId ?? ($_SESSION['usuario_id'] ?? null)
        ]);
    }

    public function getHistorico($tipo, $id) {
        return $this->db->fetchAll(
            "SELECT fh.*, u.nome as usuario_nome 
             FROM financeiro_historico fh
             LEFT JOIN usuarios u ON u.id = fh.usuario_id
             WHERE fh.entidade_tipo = ? AND fh.entidade_id = ?
             ORDER BY fh.criado_em DESC",
            [$tipo, $id]
        );
    }
}
