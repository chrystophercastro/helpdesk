<?php
/**
 * Model: Folha de Pagamento
 * Gestão de colaboradores CLT/PJ + lançamentos de folha
 */
require_once __DIR__ . '/Database.php';

class FolhaPagamento {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ========================================
    // COLABORADORES
    // ========================================

    public function listarColaboradores($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['tipo_contrato'])) {
            $where[] = 'c.tipo_contrato = ?';
            $params[] = $filtros['tipo_contrato'];
        }
        if (!empty($filtros['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['departamento_id'])) {
            $where[] = 'c.departamento_id = ?';
            $params[] = (int)$filtros['departamento_id'];
        }
        if (!empty($filtros['busca'])) {
            $where[] = '(c.nome_completo LIKE ? OR c.cpf LIKE ? OR c.cnpj LIKE ? OR c.razao_social LIKE ?)';
            $b = '%' . $filtros['busca'] . '%';
            array_push($params, $b, $b, $b, $b);
        }

        return $this->db->fetchAll(
            "SELECT c.*, d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor
             FROM colaboradores c
             LEFT JOIN departamentos d ON d.id = c.departamento_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.nome_completo",
            $params
        );
    }

    public function getColaborador($id) {
        return $this->db->fetch(
            "SELECT c.*, d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor
             FROM colaboradores c
             LEFT JOIN departamentos d ON d.id = c.departamento_id
             WHERE c.id = ?",
            [$id]
        );
    }

    public function criarColaborador($dados) {
        return $this->db->insert('colaboradores', $dados);
    }

    public function atualizarColaborador($id, $dados) {
        return $this->db->update('colaboradores', $dados, 'id = ?', [$id]);
    }

    public function getColaboradoresAtivos($tipoContrato = null) {
        $sql = "SELECT id, nome_completo, cpf, cnpj, tipo_contrato, cargo, salario_base, valor_mensal, valor_hora, departamento_id
                FROM colaboradores WHERE status = 'ativo'";
        $params = [];
        if ($tipoContrato) {
            $sql .= " AND tipo_contrato = ?";
            $params[] = $tipoContrato;
        }
        $sql .= " ORDER BY nome_completo";
        return $this->db->fetchAll($sql, $params);
    }

    // ========================================
    // LANÇAMENTOS FOLHA
    // ========================================

    public function listarLancamentos($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['competencia'])) {
            $where[] = 'fp.competencia = ?';
            $params[] = $filtros['competencia'];
        }
        if (!empty($filtros['tipo_contrato'])) {
            $where[] = 'c.tipo_contrato = ?';
            $params[] = $filtros['tipo_contrato'];
        }
        if (!empty($filtros['status'])) {
            $where[] = 'fp.status = ?';
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['colaborador_id'])) {
            $where[] = 'fp.colaborador_id = ?';
            $params[] = (int)$filtros['colaborador_id'];
        }

        return $this->db->fetchAll(
            "SELECT fp.*, c.nome_completo, c.cpf, c.cnpj, c.tipo_contrato, c.cargo, c.razao_social,
                    d.sigla as departamento_sigla, d.cor as departamento_cor,
                    ua.nome as aprovado_por_nome
             FROM folha_pagamento fp
             INNER JOIN colaboradores c ON c.id = fp.colaborador_id
             LEFT JOIN departamentos d ON d.id = c.departamento_id
             LEFT JOIN usuarios ua ON ua.id = fp.aprovado_por
             WHERE " . implode(' AND ', $where) . "
             ORDER BY fp.competencia DESC, c.nome_completo",
            $params
        );
    }

    public function getLancamento($id) {
        return $this->db->fetch(
            "SELECT fp.*, c.nome_completo, c.cpf, c.cnpj, c.tipo_contrato, c.cargo, c.razao_social,
                    c.salario_base, c.valor_mensal, c.valor_hora, c.banco, c.agencia, c.conta, c.pix,
                    d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor,
                    uc.nome as criado_por_nome, ua.nome as aprovado_por_nome
             FROM folha_pagamento fp
             INNER JOIN colaboradores c ON c.id = fp.colaborador_id
             LEFT JOIN departamentos d ON d.id = c.departamento_id
             LEFT JOIN usuarios uc ON uc.id = fp.criado_por
             LEFT JOIN usuarios ua ON ua.id = fp.aprovado_por
             WHERE fp.id = ?",
            [$id]
        );
    }

    public function criarLancamento($dados) {
        $this->calcularTotais($dados);
        return $this->db->insert('folha_pagamento', $dados);
    }

    public function atualizarLancamento($id, $dados) {
        $this->calcularTotais($dados);
        return $this->db->update('folha_pagamento', $dados, 'id = ?', [$id]);
    }

    public function deletarLancamento($id) {
        return $this->db->delete('folha_pagamento', 'id = ? AND status IN ("rascunho","calculado")', [$id]);
    }

    /**
     * Gerar folha para todos os colaboradores ativos de um tipo
     */
    public function gerarFolhaCompetencia($competencia, $tipoContrato, $criadoPor) {
        $colaboradores = $this->getColaboradoresAtivos($tipoContrato);
        $gerados = 0;

        foreach ($colaboradores as $c) {
            // Verificar se já existe
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM folha_pagamento WHERE colaborador_id = ? AND competencia = ? AND tipo = 'mensal'",
                [$c['id'], $competencia]
            );
            if ($existe) continue;

            $dados = [
                'colaborador_id' => $c['id'],
                'competencia' => $competencia,
                'tipo' => 'mensal',
                'criado_por' => $criadoPor,
                'status' => 'rascunho'
            ];

            if ($c['tipo_contrato'] === 'clt') {
                $dados['salario_bruto'] = $c['salario_base'] ?? 0;
                // Calcular INSS
                $dados['inss'] = $this->calcularINSS($dados['salario_bruto']);
                $dados['fgts'] = round($dados['salario_bruto'] * 0.08, 2);
            } else {
                // PJ
                $dados['salario_bruto'] = $c['valor_mensal'] ?? 0;
                $dados['valor_nota'] = $c['valor_mensal'] ?? 0;
            }

            $this->criarLancamento($dados);
            $gerados++;
        }

        return $gerados;
    }

    /**
     * Aprovar lançamento
     */
    public function aprovar($id, $aprovadoPor) {
        return $this->db->update('folha_pagamento', [
            'status' => 'aprovado',
            'aprovado_por' => $aprovadoPor,
            'aprovado_em' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    /**
     * Marcar como pago
     */
    public function marcarPago($id, $dataPagamento, $formaPagamento = 'pix', $comprovante = null) {
        $dados = [
            'status' => 'pago',
            'data_pagamento' => $dataPagamento,
            'forma_pagamento' => $formaPagamento
        ];
        if ($comprovante) $dados['comprovante_url'] = $comprovante;
        return $this->db->update('folha_pagamento', $dados, 'id = ?', [$id]);
    }

    // ========================================
    // CÁLCULOS
    // ========================================

    private function calcularTotais(&$dados) {
        $descontos = ($dados['inss'] ?? 0) + ($dados['irrf'] ?? 0) + ($dados['vale_transporte'] ?? 0)
            + ($dados['vale_alimentacao'] ?? 0) + ($dados['plano_saude'] ?? 0)
            + ($dados['faltas_desconto'] ?? 0) + ($dados['outros_descontos'] ?? 0);

        $adicionais = ($dados['horas_extras'] ?? 0) + ($dados['adicional_noturno'] ?? 0)
            + ($dados['comissao'] ?? 0) + ($dados['bonus'] ?? 0) + ($dados['outros_adicionais'] ?? 0);

        $dados['total_descontos'] = round($descontos, 2);
        $dados['total_adicionais'] = round($adicionais, 2);
        $dados['salario_liquido'] = round(($dados['salario_bruto'] ?? 0) + $adicionais - $descontos, 2);
    }

    /**
     * Cálculo INSS 2024/2025 (faixas progressivas)
     */
    public function calcularINSS($salarioBruto) {
        // Faixas INSS 2025
        $faixas = [
            ['limite' => 1518.00, 'aliquota' => 0.075],
            ['limite' => 2793.88, 'aliquota' => 0.09],
            ['limite' => 4190.83, 'aliquota' => 0.12],
            ['limite' => 8157.41, 'aliquota' => 0.14],
        ];

        $inss = 0;
        $salarioRestante = $salarioBruto;
        $faixaAnterior = 0;

        foreach ($faixas as $f) {
            if ($salarioRestante <= 0) break;
            $base = min($salarioRestante, $f['limite'] - $faixaAnterior);
            $inss += $base * $f['aliquota'];
            $salarioRestante -= $base;
            $faixaAnterior = $f['limite'];
        }

        return round(min($inss, 908.86), 2); // Teto INSS
    }

    /**
     * Cálculo IRRF
     */
    public function calcularIRRF($salarioBruto, $inss, $dependentes = 0) {
        $base = $salarioBruto - $inss - ($dependentes * 189.59);
        if ($base <= 0) return 0;

        // Tabela IRRF 2025
        if ($base <= 2259.20) return 0;
        if ($base <= 2826.65) return round($base * 0.075 - 169.44, 2);
        if ($base <= 3751.05) return round($base * 0.15 - 381.44, 2);
        if ($base <= 4664.68) return round($base * 0.225 - 662.77, 2);
        return round($base * 0.275 - 896.00, 2);
    }

    // ========================================
    // ESTATÍSTICAS
    // ========================================

    public function getResumoCompetencia($competencia) {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as total_lancamentos,
                SUM(CASE WHEN c.tipo_contrato='clt' THEN 1 ELSE 0 END) as total_clt,
                SUM(CASE WHEN c.tipo_contrato='pj' THEN 1 ELSE 0 END) as total_pj,
                SUM(salario_bruto) as total_bruto,
                SUM(total_descontos) as total_descontos,
                SUM(salario_liquido) as total_liquido,
                SUM(fgts) as total_fgts,
                SUM(CASE WHEN fp.status='pago' THEN 1 ELSE 0 END) as pagos,
                SUM(CASE WHEN fp.status='aprovado' THEN 1 ELSE 0 END) as aprovados,
                SUM(CASE WHEN fp.status IN ('rascunho','calculado') THEN 1 ELSE 0 END) as pendentes
             FROM folha_pagamento fp
             INNER JOIN colaboradores c ON c.id = fp.colaborador_id
             WHERE fp.competencia = ?",
            [$competencia]
        );
    }

    public function getStatsGeral() {
        return [
            'colaboradores_ativos' => $this->db->fetchColumn("SELECT COUNT(*) FROM colaboradores WHERE status = 'ativo'") ?: 0,
            'clt_ativos' => $this->db->fetchColumn("SELECT COUNT(*) FROM colaboradores WHERE status = 'ativo' AND tipo_contrato = 'clt'") ?: 0,
            'pj_ativos' => $this->db->fetchColumn("SELECT COUNT(*) FROM colaboradores WHERE status = 'ativo' AND tipo_contrato = 'pj'") ?: 0,
            'folha_mes_atual' => $this->db->fetchColumn(
                "SELECT SUM(salario_liquido) FROM folha_pagamento WHERE competencia = ? AND status != 'cancelado'",
                [date('Y-m')]
            ) ?: 0,
        ];
    }
}
