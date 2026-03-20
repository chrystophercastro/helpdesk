<?php
/**
 * Model: Suprimento (Estoque de TI + Requisições de Compra)
 */
require_once __DIR__ . '/Database.php';

class Suprimento {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ==========================================
    //  CATEGORIAS
    // ==========================================

    public function listarCategorias($apenasAtivas = true) {
        $where = $apenasAtivas ? "WHERE ativo = 1" : "";
        return $this->db->fetchAll("SELECT * FROM suprimento_categorias {$where} ORDER BY nome");
    }

    public function criarCategoria($dados) {
        return $this->db->insert('suprimento_categorias', [
            'nome'      => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'cor'       => $dados['cor'] ?? '#6B7280',
            'icone'     => $dados['icone'] ?? 'fa-box'
        ]);
    }

    // ==========================================
    //  PRODUTOS (SUPRIMENTOS)
    // ==========================================

    public function findById($id) {
        return $this->db->fetch(
            "SELECT s.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                    u.nome as criado_por_nome
             FROM suprimentos s
             LEFT JOIN suprimento_categorias c ON s.categoria_id = c.id
             LEFT JOIN usuarios u ON s.criado_por = u.id
             WHERE s.id = ?", [$id]
        );
    }

    public function listar($filtros = []) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['busca'])) {
            $where .= " AND (s.nome LIKE ? OR s.codigo LIKE ? OR s.marca LIKE ? OR s.modelo LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params = array_merge($params, [$busca, $busca, $busca, $busca]);
        }
        if (!empty($filtros['categoria_id'])) {
            $where .= " AND s.categoria_id = ?";
            $params[] = (int)$filtros['categoria_id'];
        }
        if (isset($filtros['ativo'])) {
            $where .= " AND s.ativo = ?";
            $params[] = (int)$filtros['ativo'];
        }
        if (!empty($filtros['estoque_baixo'])) {
            $where .= " AND s.estoque_atual <= s.estoque_minimo AND s.estoque_minimo > 0";
        }

        $orderBy = $filtros['order'] ?? 's.nome ASC';

        $limit = '';
        if (!empty($filtros['limite'])) {
            $offset = (int)($filtros['offset'] ?? 0);
            $limit = " LIMIT {$offset}, " . (int)$filtros['limite'];
        }

        return $this->db->fetchAll(
            "SELECT s.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone
             FROM suprimentos s
             LEFT JOIN suprimento_categorias c ON s.categoria_id = c.id
             WHERE {$where}
             ORDER BY {$orderBy}{$limit}", $params
        );
    }

    public function contar($filtros = []) {
        $where = "1=1";
        $params = [];
        if (!empty($filtros['busca'])) {
            $where .= " AND (s.nome LIKE ? OR s.codigo LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
        }
        if (!empty($filtros['categoria_id'])) {
            $where .= " AND s.categoria_id = ?";
            $params[] = (int)$filtros['categoria_id'];
        }
        if (isset($filtros['ativo'])) {
            $where .= " AND s.ativo = ?";
            $params[] = (int)$filtros['ativo'];
        }
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM suprimentos s WHERE {$where}", $params
        );
    }

    public function criar($dados) {
        $codigo = $dados['codigo'] ?? $this->gerarCodigo();
        return $this->db->insert('suprimentos', [
            'codigo'            => $codigo,
            'nome'              => $dados['nome'],
            'descricao'         => $dados['descricao'] ?? null,
            'categoria_id'      => !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null,
            'unidade'           => $dados['unidade'] ?? 'un',
            'marca'             => $dados['marca'] ?? null,
            'modelo'            => $dados['modelo'] ?? null,
            'localizacao'       => $dados['localizacao'] ?? null,
            'estoque_atual'     => (int)($dados['estoque_atual'] ?? 0),
            'estoque_minimo'    => (int)($dados['estoque_minimo'] ?? 0),
            'estoque_maximo'    => !empty($dados['estoque_maximo']) ? (int)$dados['estoque_maximo'] : null,
            'preco_unitario'    => !empty($dados['preco_unitario']) ? (float)$dados['preco_unitario'] : null,
            'fornecedor_padrao' => $dados['fornecedor_padrao'] ?? null,
            'codigo_fornecedor' => $dados['codigo_fornecedor'] ?? null,
            'ncm'               => $dados['ncm'] ?? null,
            'observacoes'       => $dados['observacoes'] ?? null,
            'ativo'             => 1,
            'criado_por'        => $dados['criado_por'] ?? null
        ]);
    }

    public function atualizar($id, $dados) {
        $campos = [];
        $permitidos = ['nome','descricao','categoria_id','unidade','marca','modelo','localizacao',
                       'estoque_minimo','estoque_maximo','preco_unitario','fornecedor_padrao',
                       'codigo_fornecedor','ncm','observacoes','ativo','codigo'];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[$campo] = $dados[$campo] === '' ? null : $dados[$campo];
            }
        }
        if (empty($campos)) return false;
        return $this->db->update('suprimentos', $campos, 'id = ?', [$id]);
    }

    private function gerarCodigo() {
        $ultimo = $this->db->fetchColumn("SELECT MAX(id) FROM suprimentos");
        $prox = ($ultimo ?? 0) + 1;
        return 'SUP-' . str_pad($prox, 5, '0', STR_PAD_LEFT);
    }

    // ==========================================
    //  MOVIMENTAÇÕES DE ESTOQUE
    // ==========================================

    public function registrarMovimentacao($suprimentoId, $tipo, $quantidade, $dados = []) {
        $produto = $this->findById($suprimentoId);
        if (!$produto) throw new \Exception('Produto não encontrado');

        $estoqueAnterior = (int)$produto['estoque_atual'];

        switch ($tipo) {
            case 'entrada':
            case 'devolucao':
                $estoquePosterior = $estoqueAnterior + $quantidade;
                break;
            case 'saida':
                $estoquePosterior = $estoqueAnterior - $quantidade;
                if ($estoquePosterior < 0) $estoquePosterior = 0;
                break;
            case 'ajuste':
                $estoquePosterior = $quantidade; // valor absoluto
                $quantidade = $estoquePosterior - $estoqueAnterior;
                break;
            default:
                throw new \Exception('Tipo de movimentação inválido');
        }

        // Inserir movimentação
        $movId = $this->db->insert('suprimento_movimentacoes', [
            'suprimento_id'     => $suprimentoId,
            'tipo'              => $tipo,
            'quantidade'        => $quantidade,
            'estoque_anterior'  => $estoqueAnterior,
            'estoque_posterior' => $estoquePosterior,
            'motivo'            => $dados['motivo'] ?? null,
            'documento'         => $dados['documento'] ?? null,
            'requisicao_id'     => $dados['requisicao_id'] ?? null,
            'usuario_id'        => $dados['usuario_id'] ?? null
        ]);

        // Atualizar estoque do produto
        $this->db->update('suprimentos', ['estoque_atual' => $estoquePosterior], 'id = ?', [$suprimentoId]);

        return $movId;
    }

    public function listarMovimentacoes($filtros = []) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['suprimento_id'])) {
            $where .= " AND m.suprimento_id = ?";
            $params[] = (int)$filtros['suprimento_id'];
        }
        if (!empty($filtros['tipo'])) {
            $where .= " AND m.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND m.criado_em >= ?";
            $params[] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND m.criado_em <= ?";
            $params[] = $filtros['data_fim'] . ' 23:59:59';
        }

        $limit = '';
        if (!empty($filtros['limite'])) {
            $offset = (int)($filtros['offset'] ?? 0);
            $limit = " LIMIT {$offset}, " . (int)$filtros['limite'];
        }

        return $this->db->fetchAll(
            "SELECT m.*, s.nome as produto_nome, s.codigo as produto_codigo, u.nome as usuario_nome
             FROM suprimento_movimentacoes m
             LEFT JOIN suprimentos s ON m.suprimento_id = s.id
             LEFT JOIN usuarios u ON m.usuario_id = u.id
             WHERE {$where}
             ORDER BY m.criado_em DESC{$limit}", $params
        );
    }

    // ==========================================
    //  REQUISIÇÕES DE COMPRA
    // ==========================================

    public function findRequisicao($id) {
        return $this->db->fetch(
            "SELECT r.*, u.nome as solicitante_nome, a.nome as aprovador_nome
             FROM suprimento_requisicoes r
             LEFT JOIN usuarios u ON r.solicitante_id = u.id
             LEFT JOIN usuarios a ON r.aprovador_id = a.id
             WHERE r.id = ?", [$id]
        );
    }

    public function listarRequisicoes($filtros = []) {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['status'])) {
            $where .= " AND r.status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['solicitante_id'])) {
            $where .= " AND r.solicitante_id = ?";
            $params[] = (int)$filtros['solicitante_id'];
        }
        if (!empty($filtros['busca'])) {
            $where .= " AND (r.titulo LIKE ? OR r.codigo LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
        }

        return $this->db->fetchAll(
            "SELECT r.*, u.nome as solicitante_nome, a.nome as aprovador_nome
             FROM suprimento_requisicoes r
             LEFT JOIN usuarios u ON r.solicitante_id = u.id
             LEFT JOIN usuarios a ON r.aprovador_id = a.id
             WHERE {$where}
             ORDER BY r.criado_em DESC", $params
        );
    }

    public function criarRequisicao($dados) {
        $codigo = $this->gerarCodigoRequisicao();
        $reqId = $this->db->insert('suprimento_requisicoes', [
            'codigo'         => $codigo,
            'titulo'         => $dados['titulo'],
            'justificativa'  => $dados['justificativa'] ?? null,
            'prioridade'     => $dados['prioridade'] ?? 'media',
            'status'         => 'pendente',
            'solicitante_id' => (int)$dados['solicitante_id'],
            'email_compras'  => $dados['email_compras'] ?? null,
            'observacoes'    => $dados['observacoes'] ?? null,
            'valor_total'    => 0
        ]);

        // Inserir itens
        $valorTotal = 0;
        if (!empty($dados['itens'])) {
            foreach ($dados['itens'] as $item) {
                $preco = (float)($item['preco_estimado'] ?? 0);
                $qtd = (int)($item['quantidade'] ?? 1);
                $this->db->insert('suprimento_requisicao_itens', [
                    'requisicao_id'  => $reqId,
                    'suprimento_id'  => !empty($item['suprimento_id']) ? (int)$item['suprimento_id'] : null,
                    'nome_item'      => $item['nome_item'] ?? $item['nome'] ?? 'Item',
                    'descricao'      => $item['descricao'] ?? null,
                    'quantidade'     => $qtd,
                    'unidade'        => $item['unidade'] ?? 'un',
                    'preco_estimado' => $preco > 0 ? $preco : null
                ]);
                $valorTotal += ($preco * $qtd);
            }
        }
        $this->db->update('suprimento_requisicoes', ['valor_total' => $valorTotal], 'id = ?', [$reqId]);

        // Histórico
        $this->registrarHistoricoRequisicao($reqId, 'pendente', $dados['solicitante_id'], 'Requisição criada');

        return $reqId;
    }

    public function getItensRequisicao($requisicaoId) {
        return $this->db->fetchAll(
            "SELECT ri.*, s.nome as produto_nome, s.codigo as produto_codigo
             FROM suprimento_requisicao_itens ri
             LEFT JOIN suprimentos s ON ri.suprimento_id = s.id
             WHERE ri.requisicao_id = ?
             ORDER BY ri.id ASC", [$requisicaoId]
        );
    }

    public function alterarStatusRequisicao($id, $status, $usuarioId, $observacao = '') {
        $this->db->update('suprimento_requisicoes', ['status' => $status], 'id = ?', [$id]);

        if ($status === 'aprovada') {
            $this->db->update('suprimento_requisicoes', [
                'data_aprovacao' => date('Y-m-d H:i:s'),
                'aprovador_id' => $usuarioId
            ], 'id = ?', [$id]);
        } elseif ($status === 'comprada') {
            $this->db->update('suprimento_requisicoes', [
                'data_compra' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
        } elseif ($status === 'entregue') {
            $this->db->update('suprimento_requisicoes', [
                'data_entrega' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            // Dar entrada automática no estoque
            $itens = $this->getItensRequisicao($id);
            foreach ($itens as $item) {
                if ($item['suprimento_id']) {
                    $this->registrarMovimentacao($item['suprimento_id'], 'entrada', $item['quantidade'], [
                        'motivo'       => "Entrega da requisição #{$id}",
                        'requisicao_id' => $id,
                        'usuario_id'   => $usuarioId
                    ]);
                }
            }
        }

        $this->registrarHistoricoRequisicao($id, $status, $usuarioId, $observacao);
    }

    public function registrarHistoricoRequisicao($reqId, $status, $usuarioId, $observacao = '') {
        return $this->db->insert('suprimento_requisicao_historico', [
            'requisicao_id' => $reqId,
            'status'        => $status,
            'usuario_id'    => $usuarioId,
            'observacao'    => $observacao
        ]);
    }

    public function getHistoricoRequisicao($reqId) {
        return $this->db->fetchAll(
            "SELECT h.*, u.nome as usuario_nome
             FROM suprimento_requisicao_historico h
             LEFT JOIN usuarios u ON h.usuario_id = u.id
             WHERE h.requisicao_id = ?
             ORDER BY h.criado_em ASC", [$reqId]
        );
    }

    private function gerarCodigoRequisicao() {
        $ano = date('Y');
        $ultimo = $this->db->fetchColumn(
            "SELECT MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) FROM suprimento_requisicoes WHERE codigo LIKE ?",
            ["RC-{$ano}-%"]
        );
        $prox = ($ultimo ?? 0) + 1;
        return "RC-{$ano}-" . str_pad($prox, 4, '0', STR_PAD_LEFT);
    }

    // ==========================================
    //  ESTATÍSTICAS / DASHBOARD
    // ==========================================

    public function getEstatisticas() {
        $stats = [];
        $stats['total_produtos'] = $this->db->fetchColumn("SELECT COUNT(*) FROM suprimentos WHERE ativo = 1");
        $stats['estoque_baixo'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM suprimentos WHERE ativo = 1 AND estoque_atual <= estoque_minimo AND estoque_minimo > 0"
        );
        $stats['sem_estoque'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM suprimentos WHERE ativo = 1 AND estoque_atual = 0"
        );
        $stats['valor_estoque'] = $this->db->fetchColumn(
            "SELECT COALESCE(SUM(estoque_atual * COALESCE(preco_unitario, 0)), 0) FROM suprimentos WHERE ativo = 1"
        );
        $stats['requisicoes_pendentes'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM suprimento_requisicoes WHERE status IN ('pendente','em_analise')"
        );
        $stats['movimentacoes_mes'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM suprimento_movimentacoes WHERE criado_em >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $stats['categorias'] = $this->db->fetchAll(
            "SELECT c.id, c.nome, c.cor, c.icone, COUNT(s.id) as total_produtos,
                    COALESCE(SUM(s.estoque_atual), 0) as total_estoque
             FROM suprimento_categorias c
             LEFT JOIN suprimentos s ON s.categoria_id = c.id AND s.ativo = 1
             WHERE c.ativo = 1
             GROUP BY c.id ORDER BY c.nome"
        );
        $stats['requisicoes_por_status'] = $this->db->fetchAll(
            "SELECT status, COUNT(*) as total FROM suprimento_requisicoes GROUP BY status"
        );
        return $stats;
    }

    // ==========================================
    //  IMPORTAÇÃO DE PLANILHA (CSV)
    // ==========================================

    public function importarCSV($arquivo, $usuarioId) {
        $resultados = ['sucesso' => 0, 'erros' => [], 'total' => 0];

        // Detectar delimitador
        $handle = fopen($arquivo, 'r');
        if (!$handle) throw new \Exception('Não foi possível abrir o arquivo');

        // Ler primeira linha para detectar delimitador
        $primeiraLinha = fgets($handle);
        rewind($handle);

        $delimitador = ',';
        if (substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',')) {
            $delimitador = ';';
        } elseif (substr_count($primeiraLinha, "\t") > substr_count($primeiraLinha, ',')) {
            $delimitador = "\t";
        }

        // Ler cabeçalho
        $cabecalho = fgetcsv($handle, 0, $delimitador);
        if (!$cabecalho) {
            fclose($handle);
            throw new \Exception('Arquivo vazio ou formato inválido');
        }

        // Normalizar cabeçalho
        $cabecalho = array_map(function($col) {
            return mb_strtolower(trim(preg_replace('/[\x{FEFF}]/u', '', $col)));
        }, $cabecalho);

        // Mapear colunas
        $mapa = $this->mapearColunas($cabecalho);

        // Carregar categorias
        $categorias = [];
        foreach ($this->listarCategorias() as $cat) {
            $categorias[mb_strtolower($cat['nome'])] = $cat['id'];
        }

        $linha = 1;
        while (($row = fgetcsv($handle, 0, $delimitador)) !== false) {
            $linha++;
            $resultados['total']++;

            try {
                $dados = $this->extrairDadosLinha($row, $mapa, $categorias);
                $dados['criado_por'] = $usuarioId;

                if (empty($dados['nome'])) {
                    $resultados['erros'][] = "Linha {$linha}: Nome do produto é obrigatório";
                    continue;
                }

                $this->criar($dados);
                $resultados['sucesso']++;
            } catch (\Exception $e) {
                $resultados['erros'][] = "Linha {$linha}: " . $e->getMessage();
            }
        }

        fclose($handle);
        return $resultados;
    }

    private function mapearColunas($cabecalho) {
        $mapaAliases = [
            'nome'              => ['nome','produto','item','descricao_produto','name','product'],
            'codigo'            => ['codigo','código','cod','sku','code','part_number'],
            'descricao'         => ['descricao','descrição','description','detalhes','obs'],
            'categoria'         => ['categoria','tipo','type','category','grupo','group'],
            'unidade'           => ['unidade','un','unit','medida','unid'],
            'marca'             => ['marca','brand','fabricante','manufacturer'],
            'modelo'            => ['modelo','model','referencia','ref'],
            'localizacao'       => ['localizacao','localização','local','location','almoxarifado'],
            'estoque_atual'     => ['estoque','estoque_atual','quantidade','qtd','qty','stock','saldo'],
            'estoque_minimo'    => ['estoque_minimo','estoque_mínimo','minimo','min','min_stock'],
            'preco_unitario'    => ['preco','preço','preco_unitario','valor','price','custo','cost','valor_unitario'],
            'fornecedor_padrao' => ['fornecedor','supplier','vendor','fornecedor_padrao'],
            'codigo_fornecedor' => ['codigo_fornecedor','cod_fornecedor','supplier_code'],
            'ncm'               => ['ncm','ncm_code']
        ];

        $mapa = [];
        foreach ($mapaAliases as $campo => $aliases) {
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $cabecalho);
                if ($idx !== false) {
                    $mapa[$campo] = $idx;
                    break;
                }
            }
        }
        return $mapa;
    }

    private function extrairDadosLinha($row, $mapa, $categorias) {
        $dados = [];

        foreach ($mapa as $campo => $idx) {
            if (isset($row[$idx])) {
                $dados[$campo] = trim($row[$idx]);
            }
        }

        // Converter categoria texto para ID
        if (isset($dados['categoria']) && !empty($dados['categoria'])) {
            $catNome = mb_strtolower($dados['categoria']);
            $dados['categoria_id'] = $categorias[$catNome] ?? null;
            unset($dados['categoria']);
        }

        // Limpar valores numéricos
        if (isset($dados['estoque_atual'])) {
            $dados['estoque_atual'] = (int)preg_replace('/[^0-9]/', '', $dados['estoque_atual']);
        }
        if (isset($dados['estoque_minimo'])) {
            $dados['estoque_minimo'] = (int)preg_replace('/[^0-9]/', '', $dados['estoque_minimo']);
        }
        if (isset($dados['preco_unitario'])) {
            $valor = str_replace(['R$', ' ', '.'], '', $dados['preco_unitario']);
            $valor = str_replace(',', '.', $valor);
            $dados['preco_unitario'] = (float)$valor;
        }

        return $dados;
    }

    // ==========================================
    //  GERAR HTML DO EMAIL DA REQUISIÇÃO
    // ==========================================

    public function gerarEmailRequisicao($requisicaoId) {
        $req = $this->findRequisicao($requisicaoId);
        if (!$req) throw new \Exception('Requisição não encontrada');

        $itens = $this->getItensRequisicao($requisicaoId);

        $prioridadeCores = [
            'baixa'   => '#10B981',
            'media'   => '#F59E0B',
            'alta'    => '#EF4444',
            'urgente' => '#DC2626'
        ];
        $cor = $prioridadeCores[$req['prioridade']] ?? '#6B7280';

        $html = '
        <div style="font-family:Arial,sans-serif;max-width:700px;margin:0 auto;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden">
            <div style="background:#1E293B;color:#fff;padding:20px 30px">
                <h2 style="margin:0;font-size:20px">ðŸ“‹ Requisição de Compra - ' . htmlspecialchars($req['codigo']) . '</h2>
                <p style="margin:5px 0 0;opacity:0.8;font-size:14px">Oracle X - Suprimentos</p>
            </div>
            <div style="padding:25px 30px">
                <table style="width:100%;border-collapse:collapse;margin-bottom:20px">
                    <tr>
                        <td style="padding:8px 0;color:#6B7280;width:150px">Título:</td>
                        <td style="padding:8px 0;font-weight:bold">' . htmlspecialchars($req['titulo']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6B7280">Solicitante:</td>
                        <td style="padding:8px 0">' . htmlspecialchars($req['solicitante_nome']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6B7280">Prioridade:</td>
                        <td style="padding:8px 0"><span style="background:' . $cor . '20;color:' . $cor . ';padding:3px 10px;border-radius:12px;font-size:13px">' . ucfirst($req['prioridade']) . '</span></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6B7280">Data:</td>
                        <td style="padding:8px 0">' . date('d/m/Y H:i', strtotime($req['criado_em'])) . '</td>
                    </tr>
                </table>';

        if ($req['justificativa']) {
            $html .= '<div style="background:#F8FAFC;padding:15px;border-radius:6px;margin-bottom:20px">
                <strong style="color:#374151">Justificativa:</strong>
                <p style="margin:5px 0 0;color:#4B5563">' . nl2br(htmlspecialchars($req['justificativa'])) . '</p>
            </div>';
        }

        $html .= '<h3 style="color:#374151;margin-bottom:10px;border-bottom:2px solid #E5E7EB;padding-bottom:8px">Itens Solicitados</h3>
            <table style="width:100%;border-collapse:collapse;font-size:14px">
                <tr style="background:#F1F5F9">
                    <th style="padding:10px;text-align:left;border-bottom:1px solid #E5E7EB">#</th>
                    <th style="padding:10px;text-align:left;border-bottom:1px solid #E5E7EB">Item</th>
                    <th style="padding:10px;text-align:center;border-bottom:1px solid #E5E7EB">Qtd</th>
                    <th style="padding:10px;text-align:right;border-bottom:1px solid #E5E7EB">Valor Un.</th>
                    <th style="padding:10px;text-align:right;border-bottom:1px solid #E5E7EB">Subtotal</th>
                </tr>';

        foreach ($itens as $i => $item) {
            $sub = $item['subtotal'] ?? ($item['quantidade'] * ($item['preco_estimado'] ?? 0));
            $html .= '<tr>
                <td style="padding:10px;border-bottom:1px solid #F1F5F9">' . ($i+1) . '</td>
                <td style="padding:10px;border-bottom:1px solid #F1F5F9"><strong>' . htmlspecialchars($item['nome_item']) . '</strong>' .
                ($item['descricao'] ? '<br><small style="color:#6B7280">' . htmlspecialchars($item['descricao']) . '</small>' : '') . '</td>
                <td style="padding:10px;text-align:center;border-bottom:1px solid #F1F5F9">' . $item['quantidade'] . ' ' . $item['unidade'] . '</td>
                <td style="padding:10px;text-align:right;border-bottom:1px solid #F1F5F9">R$ ' . number_format($item['preco_estimado'] ?? 0, 2, ',', '.') . '</td>
                <td style="padding:10px;text-align:right;border-bottom:1px solid #F1F5F9">R$ ' . number_format($sub, 2, ',', '.') . '</td>
            </tr>';
        }

        $html .= '<tr style="background:#F8FAFC;font-weight:bold">
                <td colspan="4" style="padding:12px;text-align:right;border-top:2px solid #E5E7EB">TOTAL ESTIMADO:</td>
                <td style="padding:12px;text-align:right;border-top:2px solid #E5E7EB;color:#1E293B;font-size:16px">R$ ' . number_format($req['valor_total'], 2, ',', '.') . '</td>
            </tr></table>';

        if ($req['observacoes']) {
            $html .= '<div style="margin-top:15px;padding:12px;background:#FEF3C7;border-radius:6px;font-size:13px">
                <strong>ðŸ“ Observações:</strong> ' . nl2br(htmlspecialchars($req['observacoes'])) . '</div>';
        }

        $html .= '</div>
            <div style="background:#F8FAFC;padding:15px 30px;text-align:center;font-size:12px;color:#9CA3AF;border-top:1px solid #E5E7EB">
                Enviado automaticamente pelo Oracle X — ' . date('d/m/Y H:i') . '
            </div>
        </div>';

        return [
            'subject' => "Requisição de Compra {$req['codigo']} - {$req['titulo']}",
            'body' => $html,
            'requisicao' => $req
        ];
    }

    // ==========================================
    //  BUSCA PARA SELECT / AUTOCOMPLETE
    // ==========================================

    public function buscarProdutos($termo) {
        return $this->db->fetchAll(
            "SELECT id, codigo, nome, unidade, preco_unitario, estoque_atual
             FROM suprimentos
             WHERE ativo = 1 AND (nome LIKE ? OR codigo LIKE ?)
             ORDER BY nome LIMIT 20",
            ['%'.$termo.'%', '%'.$termo.'%']
        );
    }
}
