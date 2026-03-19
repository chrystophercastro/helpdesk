<?php
/**
 * Model: CMDB (Configuration Management Database)
 */
require_once __DIR__ . '/../../config/app.php';

class CMDB {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ============ CATEGORIAS ============

    public function listarCategorias() {
        return $this->db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM cmdb_itens WHERE categoria_id = c.id) as total_itens
             FROM cmdb_categorias c ORDER BY c.ordem, c.nome"
        );
    }

    public function getCategoria($id) {
        return $this->db->fetch("SELECT * FROM cmdb_categorias WHERE id = ?", [$id]);
    }

    public function criarCategoria($dados) {
        return $this->db->insert('cmdb_categorias', $dados);
    }

    public function atualizarCategoria($id, $dados) {
        return $this->db->update('cmdb_categorias', $dados, 'id = ?', [$id]);
    }

    public function excluirCategoria($id) {
        return $this->db->delete('cmdb_categorias', 'id = ?', [$id]);
    }

    // ============ ITENS DE CONFIGURAÇÃO ============

    public function listarItens($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['categoria_id'])) {
            $where[] = 'ci.categoria_id = ?';
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['status'])) {
            $where[] = 'ci.status = ?';
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['criticidade'])) {
            $where[] = 'ci.criticidade = ?';
            $params[] = $filtros['criticidade'];
        }
        if (!empty($filtros['ambiente'])) {
            $where[] = 'ci.ambiente = ?';
            $params[] = $filtros['ambiente'];
        }
        if (!empty($filtros['busca'])) {
            $where[] = '(ci.nome LIKE ? OR ci.identificador LIKE ? OR ci.ip_endereco LIKE ? OR ci.descricao LIKE ?)';
            $busca = '%' . $filtros['busca'] . '%';
            $params = array_merge($params, [$busca, $busca, $busca, $busca]);
        }

        $sql = "SELECT ci.*, 
                    cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor,
                    u.nome as responsavel_nome,
                    f.nome as fornecedor_nome,
                    (SELECT COUNT(*) FROM cmdb_relacionamentos WHERE ci_origem_id = ci.id OR ci_destino_id = ci.id) as total_relacoes
                FROM cmdb_itens ci
                LEFT JOIN cmdb_categorias cat ON ci.categoria_id = cat.id
                LEFT JOIN usuarios u ON ci.responsavel_id = u.id
                LEFT JOIN fornecedores f ON ci.fornecedor_id = f.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ci.criticidade = 'critica' DESC, ci.nome ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getItem($id) {
        $item = $this->db->fetch(
            "SELECT ci.*, 
                cat.nome as categoria_nome, cat.icone as categoria_icone, cat.cor as categoria_cor,
                u.nome as responsavel_nome,
                f.nome as fornecedor_nome,
                inv.nome as inventario_nome, inv.numero_patrimonio
             FROM cmdb_itens ci
             LEFT JOIN cmdb_categorias cat ON ci.categoria_id = cat.id
             LEFT JOIN usuarios u ON ci.responsavel_id = u.id
             LEFT JOIN fornecedores f ON ci.fornecedor_id = f.id
             LEFT JOIN inventario inv ON ci.inventario_id = inv.id
             WHERE ci.id = ?", [$id]
        );

        if ($item) {
            $item['relacionamentos'] = $this->getRelacionamentosDoItem($id);
            $item['historico'] = $this->getHistoricoDoItem($id, 20);
        }

        return $item;
    }

    public function criarItem($dados, $usuarioId) {
        $insert = [
            'nome' => $dados['nome'],
            'identificador' => $dados['identificador'] ?? null,
            'categoria_id' => $dados['categoria_id'] ?? null,
            'inventario_id' => $dados['inventario_id'] ?? null,
            'descricao' => $dados['descricao'] ?? null,
            'status' => $dados['status'] ?? 'ativo',
            'criticidade' => $dados['criticidade'] ?? 'media',
            'ambiente' => $dados['ambiente'] ?? 'producao',
            'versao' => $dados['versao'] ?? null,
            'ip_endereco' => $dados['ip_endereco'] ?? null,
            'localizacao' => $dados['localizacao'] ?? null,
            'responsavel_id' => $dados['responsavel_id'] ?? null,
            'fornecedor_id' => $dados['fornecedor_id'] ?? null,
            'dados_extras' => !empty($dados['dados_extras']) ? json_encode($dados['dados_extras']) : null,
            'criado_por' => $usuarioId
        ];

        $this->db->insert('cmdb_itens', $insert);
        $id = $this->db->lastInsertId();

        $this->registrarHistorico($id, 'criacao', null, null, $insert['nome'], 'Item de configuração criado', $usuarioId);

        return $id;
    }

    public function atualizarItem($id, $dados, $usuarioId) {
        $atual = $this->db->fetch("SELECT * FROM cmdb_itens WHERE id = ?", [$id]);
        if (!$atual) return false;

        $campos = ['nome','identificador','categoria_id','inventario_id','descricao','status',
                    'criticidade','ambiente','versao','ip_endereco','localizacao','responsavel_id','fornecedor_id'];
        $update = [];
        foreach ($campos as $c) {
            if (isset($dados[$c])) {
                $update[$c] = $dados[$c];
                if ($atual[$c] != $dados[$c]) {
                    $tipo = ($c === 'status') ? 'status' : 'atualizacao';
                    $this->registrarHistorico($id, $tipo, $c, $atual[$c], $dados[$c], "Campo '$c' alterado", $usuarioId);
                }
            }
        }

        if (!empty($dados['dados_extras'])) {
            $update['dados_extras'] = json_encode($dados['dados_extras']);
        }

        if (!empty($update)) {
            $this->db->update('cmdb_itens', $update, 'id = ?', [$id]);
        }

        return true;
    }

    public function excluirItem($id, $usuarioId) {
        $item = $this->db->fetch("SELECT nome FROM cmdb_itens WHERE id = ?", [$id]);
        if ($item) {
            $this->registrarHistorico($id, 'exclusao', null, $item['nome'], null, 'Item excluído', $usuarioId);
        }
        return $this->db->delete('cmdb_itens', 'id = ?', [$id]);
    }

    // ============ RELACIONAMENTOS ============

    public function getRelacionamentosDoItem($ciId) {
        return $this->db->fetchAll(
            "SELECT r.*,
                co.nome as origem_nome, co.identificador as origem_id_label, co.status as origem_status,
                cato.icone as origem_icone, cato.cor as origem_cor,
                cd.nome as destino_nome, cd.identificador as destino_id_label, cd.status as destino_status,
                catd.icone as destino_icone, catd.cor as destino_cor
             FROM cmdb_relacionamentos r
             JOIN cmdb_itens co ON r.ci_origem_id = co.id
             JOIN cmdb_itens cd ON r.ci_destino_id = cd.id
             LEFT JOIN cmdb_categorias cato ON co.categoria_id = cato.id
             LEFT JOIN cmdb_categorias catd ON cd.categoria_id = catd.id
             WHERE r.ci_origem_id = ? OR r.ci_destino_id = ?
             ORDER BY r.criado_em DESC",
            [$ciId, $ciId]
        );
    }

    public function criarRelacionamento($dados, $usuarioId) {
        $insert = [
            'ci_origem_id' => $dados['ci_origem_id'],
            'ci_destino_id' => $dados['ci_destino_id'],
            'tipo' => $dados['tipo'],
            'descricao' => $dados['descricao'] ?? null,
            'criado_por' => $usuarioId
        ];
        $this->db->insert('cmdb_relacionamentos', $insert);

        $this->registrarHistorico($dados['ci_origem_id'], 'relacionamento', null, null,
            $dados['tipo'] . ' -> CI#' . $dados['ci_destino_id'], 'Relacionamento criado', $usuarioId);

        return $this->db->lastInsertId();
    }

    public function excluirRelacionamento($id, $usuarioId) {
        $rel = $this->db->fetch("SELECT * FROM cmdb_relacionamentos WHERE id = ?", [$id]);
        if ($rel) {
            $this->registrarHistorico($rel['ci_origem_id'], 'relacionamento', null,
                $rel['tipo'] . ' -> CI#' . $rel['ci_destino_id'], null, 'Relacionamento removido', $usuarioId);
        }
        return $this->db->delete('cmdb_relacionamentos', 'id = ?', [$id]);
    }

    // ============ ANÁLISE DE IMPACTO ============

    public function analisarImpacto($ciId, $visitados = []) {
        if (in_array($ciId, $visitados)) return [];
        $visitados[] = $ciId;

        $dependentes = $this->db->fetchAll(
            "SELECT r.ci_origem_id as ci_id, r.tipo, ci.nome, ci.criticidade, ci.status,
                    cat.icone, cat.cor, cat.nome as categoria
             FROM cmdb_relacionamentos r
             JOIN cmdb_itens ci ON r.ci_origem_id = ci.id
             LEFT JOIN cmdb_categorias cat ON ci.categoria_id = cat.id
             WHERE r.ci_destino_id = ? AND r.tipo IN ('depende_de','componente_de','executa_em','hospedado_em')",
            [$ciId]
        );

        $resultado = [];
        foreach ($dependentes as $dep) {
            $dep['nivel'] = count($visitados);
            $dep['impacto_indireto'] = $this->analisarImpacto($dep['ci_id'], $visitados);
            $resultado[] = $dep;
        }

        return $resultado;
    }

    public function getMapaRelacionamentos() {
        $nodes = $this->db->fetchAll(
            "SELECT ci.id, ci.nome, ci.identificador, ci.status, ci.criticidade,
                    cat.nome as categoria, cat.icone, cat.cor
             FROM cmdb_itens ci
             LEFT JOIN cmdb_categorias cat ON ci.categoria_id = cat.id
             WHERE ci.status != 'aposentado'
             ORDER BY ci.nome"
        );

        $edges = $this->db->fetchAll(
            "SELECT id, ci_origem_id as source, ci_destino_id as target, tipo
             FROM cmdb_relacionamentos"
        );

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    // ============ HISTÓRICO ============

    public function getHistoricoDoItem($ciId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT h.*, u.nome as usuario_nome
             FROM cmdb_historico h
             JOIN usuarios u ON h.usuario_id = u.id
             WHERE h.ci_id = ?
             ORDER BY h.criado_em DESC
             LIMIT ?",
            [$ciId, $limit]
        );
    }

    private function registrarHistorico($ciId, $tipo, $campo, $anterior, $novo, $descricao, $usuarioId) {
        $this->db->insert('cmdb_historico', [
            'ci_id' => $ciId,
            'tipo_mudanca' => $tipo,
            'campo' => $campo,
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
            'descricao' => $descricao,
            'usuario_id' => $usuarioId
        ]);
    }

    // ============ DASHBOARD ============

    public function getOverview() {
        $total = $this->db->fetch("SELECT COUNT(*) as t FROM cmdb_itens")['t'];
        $ativos = $this->db->fetch("SELECT COUNT(*) as t FROM cmdb_itens WHERE status = 'ativo'")['t'];
        $criticos = $this->db->fetch("SELECT COUNT(*) as t FROM cmdb_itens WHERE criticidade = 'critica' AND status = 'ativo'")['t'];
        $relacoes = $this->db->fetch("SELECT COUNT(*) as t FROM cmdb_relacionamentos")['t'];
        $categorias = $this->db->fetch("SELECT COUNT(*) as t FROM cmdb_categorias")['t'];
        $semRelacao = $this->db->fetch(
            "SELECT COUNT(*) as t FROM cmdb_itens ci
             WHERE ci.status = 'ativo'
             AND NOT EXISTS (SELECT 1 FROM cmdb_relacionamentos r WHERE r.ci_origem_id = ci.id OR r.ci_destino_id = ci.id)"
        )['t'];

        $porCategoria = $this->db->fetchAll(
            "SELECT cat.nome, cat.icone, cat.cor, COUNT(ci.id) as total
             FROM cmdb_categorias cat
             LEFT JOIN cmdb_itens ci ON ci.categoria_id = cat.id AND ci.status != 'aposentado'
             GROUP BY cat.id ORDER BY total DESC"
        );

        $porStatus = $this->db->fetchAll(
            "SELECT status, COUNT(*) as total FROM cmdb_itens GROUP BY status"
        );

        $porCriticidade = $this->db->fetchAll(
            "SELECT criticidade, COUNT(*) as total FROM cmdb_itens WHERE status = 'ativo' GROUP BY criticidade"
        );

        $porAmbiente = $this->db->fetchAll(
            "SELECT ambiente, COUNT(*) as total FROM cmdb_itens WHERE status = 'ativo' GROUP BY ambiente"
        );

        $ultimasMudancas = $this->db->fetchAll(
            "SELECT h.*, ci.nome as ci_nome, u.nome as usuario_nome
             FROM cmdb_historico h
             JOIN cmdb_itens ci ON h.ci_id = ci.id
             JOIN usuarios u ON h.usuario_id = u.id
             ORDER BY h.criado_em DESC LIMIT 10"
        );

        return [
            'total' => $total,
            'ativos' => $ativos,
            'criticos' => $criticos,
            'relacoes' => $relacoes,
            'categorias' => $categorias,
            'sem_relacao' => $semRelacao,
            'porCategoria' => $porCategoria,
            'porStatus' => $porStatus,
            'porCriticidade' => $porCriticidade,
            'porAmbiente' => $porAmbiente,
            'ultimasMudancas' => $ultimasMudancas
        ];
    }
}
