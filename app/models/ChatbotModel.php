<?php
/**
 * Model: ChatbotModel
 * 
 * Motor do chatbot inteligente Oracle X
 * - Integração com IA (Ollama via config existente do sistema)
 * - Integração com Evolution API (WhatsApp)
 * - Integração com N8N (automações via webhook)
 * - Consulta a base de dados externa
 * - Controle de números autorizados
 */
require_once __DIR__ . '/Database.php';

class ChatbotModel {
    private $db;
    private $config = [];
    private $iaConfig = [];
    private $evolutionConfig = [];
    private $fontesConns = []; // Cache de conexões por fonte_id

    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfig();
        $this->loadIAConfig();
        $this->loadEvolutionConfig();
    }

    // ==========================================
    //  CONFIGURAÇÃO
    // ==========================================

    private function loadConfig() {
        $rows = $this->db->fetchAll("SELECT chave, valor FROM chatbot_config");
        foreach ($rows as $r) {
            $this->config[$r['chave']] = $r['valor'];
        }
    }

    private function loadIAConfig() {
        try {
            $rows = $this->db->fetchAll("SELECT chave, valor FROM ia_config");
            foreach ($rows as $r) {
                $this->iaConfig[$r['chave']] = $r['valor'];
            }
        } catch (\Exception $e) {
            $this->iaConfig = [];
        }
    }

    private function loadEvolutionConfig() {
        try {
            $rows = $this->db->fetchAll("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'evolution_%' OR chave = 'whatsapp_ativo'");
            foreach ($rows as $r) {
                $this->evolutionConfig[$r['chave']] = $r['valor'];
            }
        } catch (\Exception $e) {
            $this->evolutionConfig = [];
        }
    }

    public function getConfig($key, $default = '') {
        return $this->config[$key] ?? $default;
    }

    public function setConfig($key, $value) {
        $this->db->query(
            "INSERT INTO chatbot_config (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)",
            [$key, $value]
        );
        $this->config[$key] = $value;

        // Invalidar cache do contexto DB quando configs relevantes mudam
        if (strpos($key, 'chatbot_db_') === 0 || strpos($key, 'chatbot_api_') === 0) {
            $this->limparCacheContextoDB();
        }
    }

    /**
     * Limpar cache do contexto de banco de dados
     * Útil quando as configurações são alteradas
     */
    public function limparCacheContextoDB() {
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '/db_context_*.cache') as $f) {
                @unlink($f);
            }
        }
    }

    public function getAllConfig() {
        return $this->config;
    }

    public function getIAConfig($key, $default = '') {
        return $this->iaConfig[$key] ?? $default;
    }

    public function getEvolutionConfig($key, $default = '') {
        return $this->evolutionConfig[$key] ?? $default;
    }

    // ==========================================
    //  FONTES DE DADOS MÚLTIPLAS
    // ==========================================

    /**
     * Listar todas as fontes de dados
     */
    public function listarFontesDados($apenasAtivas = false) {
        $sql = "SELECT * FROM chatbot_fontes_dados";
        if ($apenasAtivas) {
            $sql .= " WHERE ativo = 1";
        }
        $sql .= " ORDER BY ordem ASC, nome ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Obter uma fonte de dados por ID
     */
    public function getFonteDados($id) {
        return $this->db->fetch("SELECT * FROM chatbot_fontes_dados WHERE id = ?", [$id]);
    }

    /**
     * Obter uma fonte de dados por alias
     */
    public function getFonteDadosByAlias($alias) {
        return $this->db->fetch("SELECT * FROM chatbot_fontes_dados WHERE alias = ?", [$alias]);
    }

    /**
     * Criar uma nova fonte de dados
     */
    public function criarFonteDados($dados) {
        // Sanitizar alias
        $dados['alias'] = preg_replace('/[^a-z0-9_]/', '', strtolower($dados['alias'] ?? ''));
        if (empty($dados['alias'])) {
            throw new \Exception('Alias é obrigatório e deve conter apenas letras minúsculas, números e _');
        }

        // Verificar duplicata de alias
        $existe = $this->db->fetch("SELECT id FROM chatbot_fontes_dados WHERE alias = ?", [$dados['alias']]);
        if ($existe) {
            throw new \Exception('Já existe uma fonte com o alias "' . $dados['alias'] . '"');
        }

        // Definir ordem automática
        if (!isset($dados['ordem'])) {
            $maxOrdem = $this->db->fetchColumn("SELECT MAX(ordem) FROM chatbot_fontes_dados");
            $dados['ordem'] = ($maxOrdem ?? 0) + 1;
        }

        // Tratar relacionamentos e endpoints como JSON
        if (isset($dados['relacionamentos']) && is_array($dados['relacionamentos'])) {
            $dados['relacionamentos'] = json_encode($dados['relacionamentos'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($dados['api_endpoints']) && is_array($dados['api_endpoints'])) {
            $dados['api_endpoints'] = json_encode($dados['api_endpoints'], JSON_UNESCAPED_UNICODE);
        }

        $id = $this->db->insert('chatbot_fontes_dados', $dados);
        $this->limparCacheContextoDB();
        return $id;
    }

    /**
     * Atualizar uma fonte de dados existente
     */
    public function atualizarFonteDados($id, $dados) {
        // Sanitizar alias se presente
        if (isset($dados['alias'])) {
            $dados['alias'] = preg_replace('/[^a-z0-9_]/', '', strtolower($dados['alias']));
            if (empty($dados['alias'])) {
                throw new \Exception('Alias é obrigatório');
            }
            // Verificar duplicata
            $existe = $this->db->fetch("SELECT id FROM chatbot_fontes_dados WHERE alias = ? AND id != ?", [$dados['alias'], $id]);
            if ($existe) {
                throw new \Exception('Já existe uma fonte com o alias "' . $dados['alias'] . '"');
            }
        }

        // Tratar relacionamentos e endpoints como JSON
        if (isset($dados['relacionamentos']) && is_array($dados['relacionamentos'])) {
            $dados['relacionamentos'] = json_encode($dados['relacionamentos'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($dados['api_endpoints']) && is_array($dados['api_endpoints'])) {
            $dados['api_endpoints'] = json_encode($dados['api_endpoints'], JSON_UNESCAPED_UNICODE);
        }

        $this->db->update('chatbot_fontes_dados', $dados, 'id = ?', [$id]);
        
        // Limpar conexão cacheada
        unset($this->fontesConns[$id]);
        $this->limparCacheContextoDB();
        return true;
    }

    /**
     * Remover uma fonte de dados
     */
    public function removerFonteDados($id) {
        $this->db->delete('chatbot_fontes_dados', 'id = ?', [$id]);
        unset($this->fontesConns[$id]);
        $this->limparCacheContextoDB();
        return true;
    }

    /**
     * Ativar/desativar uma fonte de dados
     */
    public function toggleFonteDados($id) {
        $fonte = $this->db->fetch("SELECT ativo FROM chatbot_fontes_dados WHERE id = ?", [$id]);
        if (!$fonte) return false;
        $novoStatus = $fonte['ativo'] ? 0 : 1;
        $this->db->update('chatbot_fontes_dados', ['ativo' => $novoStatus], 'id = ?', [$id]);
        $this->limparCacheContextoDB();
        return $novoStatus;
    }

    /**
     * Testar conexão de uma fonte de dados específica
     */
    public function testarFonteDadosConexao($id) {
        $fonte = $this->getFonteDados($id);
        if (!$fonte) {
            return ['success' => false, 'message' => 'Fonte não encontrada'];
        }

        $resultado = $this->testarConexaoFonte($fonte);

        // Atualizar resultado do teste
        $this->db->update('chatbot_fontes_dados', [
            'ultimo_teste' => date('Y-m-d H:i:s'),
            'ultimo_teste_ok' => $resultado['success'] ? 1 : 0,
        ], 'id = ?', [$id]);

        return $resultado;
    }

    /**
     * Testar conexão de uma fonte (interna)
     */
    private function testarConexaoFonte($fonte) {
        if ($fonte['tipo'] === 'api') {
            if (($fonte['api_template'] ?? '') === 'datasystem') {
                return $this->testarConexaoDataSystem($fonte);
            }
            return $this->testarConexaoFonteAPI($fonte);
        }

        try {
            $db = $this->getExternalDBForFonte($fonte);
            $tables = $this->listarTabelasExternas($db, $fonte['tipo']);
            return [
                'success' => true,
                'tipo' => $fonte['tipo'],
                'tables' => $tables,
                'message' => count($tables) . ' tabelas encontradas (' . strtoupper($fonte['tipo']) . ')',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'tipo' => $fonte['tipo'],
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Testar conexão com API REST de uma fonte
     */
    private function testarConexaoFonteAPI($fonte) {
        $apiUrl = $fonte['api_url'] ?? '';
        if (empty($apiUrl)) {
            return ['success' => false, 'tipo' => 'api', 'message' => 'URL da API não configurada'];
        }

        try {
            $headers = $this->buildFonteAPIHeaders($fonte);
            $ch = curl_init(rtrim($apiUrl, '/'));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_NOBODY => false,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'tipo' => 'api', 'message' => 'cURL: ' . $error];
            }

            return [
                'success' => $httpCode >= 200 && $httpCode < 400,
                'tipo' => 'api',
                'http_code' => $httpCode,
                'message' => $httpCode >= 200 && $httpCode < 400
                    ? "API acessível (HTTP {$httpCode})"
                    : "API retornou HTTP {$httpCode}",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'tipo' => 'api', 'message' => $e->getMessage()];
        }
    }

    /**
     * Obter schema de uma fonte de dados específica
     */
    public function getFonteSchema($id) {
        $fonte = $this->getFonteDados($id);
        if (!$fonte) throw new \Exception('Fonte não encontrada');

        if ($fonte['tipo'] === 'api') {
            if (($fonte['api_template'] ?? '') === 'datasystem') {
                return $this->getDataSystemSchemaInfo($fonte);
            }
            return $this->getFonteAPIEndpointsInfo($fonte);
        }

        $db = $this->getExternalDBForFonte($fonte);
        $tables = $this->listarTabelasExternas($db, $fonte['tipo']);
        $tabelasPermitidas = $fonte['tabelas_permitidas'] ?? '';
        $permitidas = !empty($tabelasPermitidas)
            ? array_map('trim', explode(',', $tabelasPermitidas))
            : $tables;

        $schema = [];
        foreach ($tables as $table) {
            $cols = $this->listarColunasExterna($db, $fonte['tipo'], $table);
            $count = $this->contarRegistrosExterna($db, $fonte['tipo'], $table);
            $schema[] = [
                'table' => $table,
                'columns' => $cols,
                'row_count' => $count,
                'permitted' => in_array($table, $permitidas),
            ];
        }
        return $schema;
    }

    /**
     * Info de endpoints de uma fonte API
     */
    private function getFonteAPIEndpointsInfo($fonte) {
        $endpoints = $fonte['api_endpoints'] ?? '';
        if (empty($endpoints)) return [];
        $parsed = json_decode($endpoints, true);
        if (!is_array($parsed)) return [];

        $result = [];
        foreach ($parsed as $ep) {
            $result[] = [
                'table' => ($ep['method'] ?? 'GET') . ' ' . ($ep['path'] ?? ''),
                'columns' => array_map(function($p) {
                    return ['Field' => $p['name'] ?? $p, 'Type' => $p['type'] ?? 'string'];
                }, $ep['params'] ?? []),
                'row_count' => $ep['description'] ?? '',
                'permitted' => true,
            ];
        }
        return $result;
    }

    /**
     * Salvar relacionamentos de uma fonte de dados
     */
    public function salvarFonteRelacionamentos($id, $relacionamentos) {
        $json = json_encode($relacionamentos, JSON_UNESCAPED_UNICODE);
        $this->db->update('chatbot_fontes_dados', ['relacionamentos' => $json], 'id = ?', [$id]);
        $this->limparCacheContextoDB();
        return true;
    }

    /**
     * Obter relacionamentos de uma fonte de dados
     */
    public function getFonteRelacionamentos($id) {
        $fonte = $this->getFonteDados($id);
        if (!$fonte) return [];
        $rels = json_decode($fonte['relacionamentos'] ?? '[]', true);
        return is_array($rels) ? $rels : [];
    }

    /**
     * Conexão PDO para uma fonte de dados específica
     */
    private function getExternalDBForFonte($fonte) {
        $id = $fonte['id'] ?? 0;
        if (isset($this->fontesConns[$id]) && $this->fontesConns[$id]) {
            return $this->fontesConns[$id];
        }

        $tipo = $fonte['tipo'];
        if ($tipo === 'api') {
            throw new \Exception('Fonte de dados é API REST, não banco de dados');
        }

        $host = $fonte['db_host'] ?? '';
        $port = $fonte['db_port'] ?? '';
        $name = $fonte['db_name'] ?? '';
        $user = $fonte['db_user'] ?? '';
        $pass = $fonte['db_pass'] ?? '';

        switch ($tipo) {
            case 'mysql':
                $port = $port ?: '3306';
                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
                break;
            case 'pgsql':
                $port = $port ?: '5432';
                $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
                break;
            case 'sqlserver':
                $port = $port ?: '1433';
                $dsn = "sqlsrv:Server={$host},{$port};Database={$name}";
                break;
            case 'sqlite':
                $dsn = "sqlite:{$host}";
                $user = null;
                $pass = null;
                break;
            default:
                throw new \Exception("Driver não suportado: {$tipo}");
        }

        if ($tipo !== 'sqlite' && (empty($host) || empty($name))) {
            throw new \Exception('Banco de dados não configurado na fonte "' . ($fonte['nome'] ?? '') . '"');
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 10,
        ];

        $conn = new \PDO($dsn, $user, $pass, $options);
        if ($tipo === 'pgsql') {
            $conn->exec("SET client_encoding TO 'UTF8'");
        }

        $this->fontesConns[$id] = $conn;
        return $conn;
    }

    /**
     * Headers de autenticação para uma fonte API
     */
    private function buildFonteAPIHeaders($fonte) {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $authTipo = $fonte['api_auth_tipo'] ?? 'none';
        $apiKey = $fonte['api_key'] ?? '';

        switch ($authTipo) {
            case 'bearer':
                if ($apiKey) $headers[] = 'Authorization: Bearer ' . $apiKey;
                break;
            case 'apikey':
                $headerName = $fonte['api_auth_header'] ?? 'Authorization';
                if ($apiKey) $headers[] = $headerName . ': ' . $apiKey;
                break;
            case 'basic':
                $user = $fonte['api_auth_user'] ?? '';
                $pass = $fonte['api_auth_pass'] ?? '';
                if ($user) $headers[] = 'Authorization: Basic ' . base64_encode($user . ':' . $pass);
                break;
        }
        return $headers;
    }

    /**
     * Chamar API REST de uma fonte específica
     */
    public function callFonteAPI($fonte, $method, $path, $body = null) {
        $baseUrl = rtrim($fonte['api_url'] ?? '', '/');
        if (empty($baseUrl)) {
            throw new \Exception('URL da API não configurada na fonte "' . ($fonte['nome'] ?? '') . '"');
        }

        $url = $baseUrl . '/' . ltrim($path, '/');
        $headers = $this->buildFonteAPIHeaders($fonte);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) throw new \Exception('cURL: ' . $error);

        return [
            'http_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true),
        ];
    }

    // ==========================================
    //  DATASYSTEM ERP (template: datasystem)
    // ==========================================

    /**
     * Definição dos endpoints padrão do DataSystem ERP
     * Usado para popular o contexto da IA e para o schema visual
     */
    public static function getDataSystemEndpoints() {
        return [
            [
                'path' => '/api/v1/vendas',
                'method' => 'GET',
                'description' => 'Buscar vendas por período (máx 365 dias)',
                'params' => [
                    ['name' => 'dataVendaInicio', 'type' => 'string', 'required' => true, 'description' => 'Data início (yyyy-mm-dd)'],
                    ['name' => 'dataVendaFim', 'type' => 'string', 'required' => true, 'description' => 'Data fim (yyyy-mm-dd)'],
                    ['name' => 'campoOrdem', 'type' => 'string', 'required' => true, 'description' => 'Campo para ordenar (ex: emissao)'],
                    ['name' => 'ordem', 'type' => 'string', 'required' => true, 'description' => 'ASC ou DESC'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                    ['name' => 'loja', 'type' => 'integer', 'required' => false, 'description' => 'Filtrar por loja'],
                    ['name' => 'excluidas', 'type' => 'integer', 'required' => false, 'description' => '1=sim, 2=não, 0=todas'],
                ],
                'response_fields' => 'loja, codigo, hora, emissao, valorTotal, difTroca, desconto, acrescimo, creditoLoja, creditoTroca, bonusValor, nota, serieNf, tipoVenda, tipoVendaDescricao, cliente{loja,codigo,nome,cpf}, itens[{referencia,cor,tamanho,quantidade,valorUnitario,desconto}], parcelas[{valor,plano,cartao}], usuario{codigo,nome}',
            ],
            [
                'path' => '/api/v1/clientes',
                'method' => 'GET',
                'description' => 'Buscar clientes cadastrados',
                'params' => [
                    ['name' => 'campoOrdem', 'type' => 'string', 'required' => true, 'description' => 'Campo para ordenar (ex: nome)'],
                    ['name' => 'ordem', 'type' => 'string', 'required' => true, 'description' => 'ASC ou DESC'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                ],
                'response_fields' => 'loja, codigo, tipoPessoa, nome, sobrenome, cpf, rg, email, telefone1, telefone2, cep, endereco, numero, bairro, cidade, estado, nascimento, sexo, cadastro, limite, bloqueado, ultCompra, datUltAlt',
            ],
            [
                'path' => '/api/v1/clientes/cpfCnpj/{cpfCnpj}',
                'method' => 'GET',
                'description' => 'Buscar cliente por CPF ou CNPJ',
                'params' => [
                    ['name' => 'cpfCnpj', 'type' => 'string', 'required' => true, 'description' => 'CPF ou CNPJ (somente números)'],
                ],
                'response_fields' => 'loja, codigo, nome, sobrenome, cpf, email, telefone1, cidade, estado, limite, bloqueado',
            ],
            [
                'path' => '/api/v1/produtos',
                'method' => 'GET',
                'description' => 'Buscar produtos por data de última compra',
                'params' => [
                    ['name' => 'dataUltimaCompraInicio', 'type' => 'string', 'required' => true, 'description' => 'Data início (yyyy-mm-dd)'],
                    ['name' => 'dataUltimaCompraFim', 'type' => 'string', 'required' => true, 'description' => 'Data fim (yyyy-mm-dd)'],
                    ['name' => 'campoOrdem', 'type' => 'string', 'required' => true, 'description' => 'Campo para ordenar'],
                    ['name' => 'ordem', 'type' => 'string', 'required' => true, 'description' => 'ASC ou DESC'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                ],
                'response_fields' => 'referencia, descricao, marca, departamento, classe, tipo, cor, tamanho, preco, precoPromocao, custoMedio, colecao, status, codBarra, dataUltimaCompra',
            ],
            [
                'path' => '/api/v1/lojas',
                'method' => 'GET',
                'description' => 'Buscar lojas (filiais)',
                'params' => [
                    ['name' => 'codigo', 'type' => 'integer', 'required' => false, 'description' => 'Filtrar por código da loja'],
                    ['name' => 'cnpj', 'type' => 'string', 'required' => false, 'description' => 'Filtrar por CNPJ'],
                ],
                'response_fields' => 'codigo, descricao, filial, razaoSocial, nome, estado, endereco, cep, numero, bairro, cidade, telefone1, telefone2, email, url, cnpj, insc',
            ],
            [
                'path' => '/api/v1/vendedores',
                'method' => 'GET',
                'description' => 'Buscar vendedores',
                'params' => [
                    ['name' => 'datUltAlt', 'type' => 'string', 'required' => true, 'description' => 'Data última alteração (yyyy-mm-dd)'],
                    ['name' => 'loja', 'type' => 'integer', 'required' => true, 'description' => 'Código da loja'],
                    ['name' => 'status', 'type' => 'integer', 'required' => true, 'description' => '0=todos, 1=ativos, 2=inativos'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                ],
                'response_fields' => 'loja, codigo, nome, cpf',
            ],
            [
                'path' => '/api/v1/saldos',
                'method' => 'GET',
                'description' => 'Buscar saldos de estoque por loja',
                'params' => [
                    ['name' => 'loja', 'type' => 'integer', 'required' => true, 'description' => 'Código da loja'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                    ['name' => 'referencia', 'type' => 'string', 'required' => false, 'description' => 'Filtrar por referência'],
                    ['name' => 'todosProdutos', 'type' => 'integer', 'required' => false, 'description' => 'Incluir todos os produtos'],
                ],
                'response_fields' => 'referencia, descricao, cor, tamanho, saldo, custoMedio, precoVarejo',
            ],
            [
                'path' => '/api/v1/contas-a-pagar',
                'method' => 'GET',
                'description' => 'Buscar registros de Contas a Pagar',
                'params' => [
                    ['name' => 'dataHoraInicio', 'type' => 'string', 'required' => true, 'description' => 'Data início (yyyy-mm-dd)'],
                    ['name' => 'dataHoraFim', 'type' => 'string', 'required' => true, 'description' => 'Data fim (yyyy-mm-dd)'],
                    ['name' => 'tipoData', 'type' => 'integer', 'required' => true, 'description' => 'Tipo de data para filtro'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'codigoLoja', 'type' => 'integer', 'required' => false, 'description' => 'Filtrar por loja'],
                ],
                'response_fields' => 'codigo, loja, fornecedor, duplicata, valor, valorPago, dataVencimento, dataPagamento, status',
            ],
            [
                'path' => '/api/v1/contas-a-receber',
                'method' => 'GET',
                'description' => 'Buscar contas a receber',
                'params' => [
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                    ['name' => 'loja', 'type' => 'integer', 'required' => false, 'description' => 'Filtrar por loja'],
                    ['name' => 'dataVencimentoInicio', 'type' => 'string', 'required' => false, 'description' => 'Data vencimento início'],
                    ['name' => 'dataVencimentoFim', 'type' => 'string', 'required' => false, 'description' => 'Data vencimento fim'],
                ],
                'response_fields' => 'loja, cliente, nome, parcela, valor, valorPago, dataVencimento, dataPagamento, status',
            ],
            [
                'path' => '/api/v1/entradas-estoque',
                'method' => 'GET',
                'description' => 'Buscar itens de entrada de estoque',
                'params' => [
                    ['name' => 'dataInicio', 'type' => 'string', 'required' => true, 'description' => 'Data início (yyyy-mm-dd)'],
                    ['name' => 'dataFim', 'type' => 'string', 'required' => true, 'description' => 'Data fim (yyyy-mm-dd)'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                    ['name' => 'lojaDestino', 'type' => 'integer', 'required' => false, 'description' => 'Filtrar por loja destino'],
                    ['name' => 'fornecedor', 'type' => 'integer', 'required' => false, 'description' => 'Filtrar por fornecedor'],
                ],
                'response_fields' => 'loja, numero, fornecedor, dataEntrada, referencia, cor, tamanho, quantidade, custoUnitario, custoTotal',
            ],
            [
                'path' => '/api/v1/fornecedor',
                'method' => 'GET',
                'description' => 'Buscar fornecedores',
                'params' => [],
                'response_fields' => 'codigo, nome, cnpj, telefone, email, endereco, cidade, estado',
            ],
            [
                'path' => '/api/v1/departamentos',
                'method' => 'GET',
                'description' => 'Buscar departamentos de produtos',
                'params' => [],
                'response_fields' => 'codigo, descricao',
            ],
            [
                'path' => '/api/v1/marcas',
                'method' => 'GET',
                'description' => 'Buscar marcas de produtos',
                'params' => [],
                'response_fields' => 'codigo, descricao',
            ],
            [
                'path' => '/api/v1/cores',
                'method' => 'GET',
                'description' => 'Buscar cores de produtos',
                'params' => [],
                'response_fields' => 'codigo, descricao',
            ],
            [
                'path' => '/api/v1/pedidos-compras',
                'method' => 'GET',
                'description' => 'Buscar pedidos de compra (máx 365 dias)',
                'params' => [
                    ['name' => 'dataInicio', 'type' => 'string', 'required' => true, 'description' => 'Data início (yyyy-mm-dd)'],
                    ['name' => 'dataFim', 'type' => 'string', 'required' => true, 'description' => 'Data fim (yyyy-mm-dd)'],
                    ['name' => 'itensPorPagina', 'type' => 'integer', 'required' => true, 'description' => 'Qtd itens por página'],
                    ['name' => 'pagina', 'type' => 'integer', 'required' => true, 'description' => 'Número da página'],
                    ['name' => 'lojaDestino', 'type' => 'integer', 'required' => false, 'description' => 'Filtrar por loja destino'],
                ],
                'response_fields' => 'numero, lojaDestino, fornecedor, dataEmissao, status, valorTotal, itens',
            ],
            [
                'path' => '/api/v1/planos-pagamento',
                'method' => 'GET',
                'description' => 'Buscar planos de pagamento',
                'params' => [
                    ['name' => 'datUltAlt', 'type' => 'string', 'required' => false, 'description' => 'Filtrar por data última alteração'],
                ],
                'response_fields' => 'codigo, descricao, parcelas, taxaJuros',
            ],
            [
                'path' => '/api/v1/colecoes',
                'method' => 'GET',
                'description' => 'Buscar coleções de produtos',
                'params' => [],
                'response_fields' => 'codigo, descricao',
            ],
            [
                'path' => '/api/v1/modelos',
                'method' => 'GET',
                'description' => 'Buscar modelos de produtos',
                'params' => [],
                'response_fields' => 'codigo, descricao',
            ],
            [
                'path' => '/api/v1/classes',
                'method' => 'GET',
                'description' => 'Buscar classes de produtos',
                'params' => [],
                'response_fields' => 'codigo, descricao',
            ],
            [
                'path' => '/api/v1/tipos',
                'method' => 'GET',
                'description' => 'Buscar tipos de produtos',
                'params' => [],
                'response_fields' => 'codigo, descricao',
            ],
        ];
    }

    /**
     * Autenticar na API DataSystem e obter token JWT
     * Retorna o token JWT ou lança exceção em caso de falha
     */
    private function authenticateDataSystem($fonte) {
        $baseUrl = rtrim($fonte['api_url'] ?? '', '/');
        $cnpj = $fonte['api_auth_user'] ?? '';
        $hash = $fonte['api_key'] ?? '';

        if (empty($baseUrl) || empty($cnpj) || empty($hash)) {
            throw new \Exception('DataSystem: URL, CNPJ e Hash são obrigatórios');
        }

        // Verificar cache de token
        $tokenCache = $fonte['api_token_cache'] ?? '';
        $tokenExpires = $fonte['api_token_expires'] ?? '';
        if (!empty($tokenCache) && !empty($tokenExpires) && strtotime($tokenExpires) > time()) {
            return $tokenCache;
        }

        // Autenticar
        $ch = curl_init($baseUrl . '/api/v1/autenticar');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['cnpj' => $cnpj, 'hash' => $hash]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) throw new \Exception('DataSystem Auth cURL: ' . $error);
        if ($httpCode !== 200) throw new \Exception('DataSystem Auth falhou (HTTP ' . $httpCode . '): ' . substr($response, 0, 200));

        $data = json_decode($response, true);
        $token = $data['token'] ?? '';
        if (empty($token)) throw new \Exception('DataSystem: token não retornado na autenticação');

        // Salvar token em cache (válido por 23h - margem de segurança)
        $expiresAt = date('Y-m-d H:i:s', time() + 82800);
        $this->db->update('chatbot_fontes_dados', [
            'api_token_cache' => $token,
            'api_token_expires' => $expiresAt,
        ], 'id = ?', [$fonte['id']]);

        return $token;
    }

    /**
     * Chamar um endpoint da API DataSystem com autenticação automática
     */
    public function callDataSystemAPI($fonte, $method, $path, $queryParams = [], $body = null, $sessaoId = null) {
        $startTime = microtime(true);
        $token = $this->authenticateDataSystem($fonte);
        $baseUrl = rtrim($fonte['api_url'] ?? '', '/');

        $url = $baseUrl . '/' . ltrim($path, '/');
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $endpointLog = $path . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logDataSystemCall($fonte, $method, $endpointLog, $queryParams, $body, 0, null, $error, false, $startTime, $sessaoId);
            throw new \Exception('DataSystem cURL: ' . $error);
        }

        // Se 401, invalidar token e tentar novamente (uma vez)
        if ($httpCode === 401) {
            $this->db->update('chatbot_fontes_dados', [
                'api_token_cache' => null,
                'api_token_expires' => null,
            ], 'id = ?', [$fonte['id']]);
            $fonte['api_token_cache'] = '';
            $fonte['api_token_expires'] = '';

            $token = $this->authenticateDataSystem($fonte);
            $headers[2] = 'Authorization: Bearer ' . $token;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            if (strtoupper($method) === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
            }
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        if ($httpCode >= 400) {
            $this->logDataSystemCall($fonte, $method, $endpointLog, $queryParams, $body, $httpCode, $response, 'HTTP ' . $httpCode, false, $startTime, $sessaoId);
            throw new \Exception('DataSystem HTTP ' . $httpCode . ': ' . substr($response, 0, 300));
        }

        // Log sucesso
        $this->logDataSystemCall($fonte, $method, $endpointLog, $queryParams, $body, $httpCode, $response, null, true, $startTime, $sessaoId);

        $data = json_decode($response, true);
        return [
            'http_code' => $httpCode,
            'body' => $response,
            'data' => $data,
            'itens' => $data['itens'] ?? (is_array($data) && isset($data[0]) ? $data : []),
            'totalItens' => $data['totalItens'] ?? count($data['itens'] ?? []),
        ];
    }

    /**
     * Gravar log de chamada DataSystem no banco
     */
    private function logDataSystemCall($fonte, $method, $endpoint, $queryParams, $body, $httpCode, $response, $erro, $sucesso, $startTime, $sessaoId = null) {
        try {
            $duracaoMs = (int)((microtime(true) - $startTime) * 1000);
            $payloadData = [];
            if (!empty($queryParams)) $payloadData['query'] = $queryParams;
            if (!empty($body)) $payloadData['body'] = $body;

            $this->db->insert('chatbot_datasystem_logs', [
                'fonte_id'      => $fonte['id'] ?? null,
                'fonte_nome'    => $fonte['nome'] ?? $fonte['alias'] ?? 'DataSystem',
                'method'        => strtoupper($method),
                'endpoint'      => mb_substr($endpoint, 0, 500),
                'payload'       => !empty($payloadData) ? json_encode($payloadData, JSON_UNESCAPED_UNICODE) : null,
                'http_code'     => $httpCode ?: null,
                'response_body' => $response,
                'response_size' => $response ? strlen($response) : 0,
                'duracao_ms'    => $duracaoMs,
                'sucesso'       => $sucesso ? 1 : 0,
                'erro'          => $erro ? mb_substr($erro, 0, 500) : null,
                'sessao_id'     => $sessaoId,
            ]);
        } catch (\Exception $e) {
            // Silenciar erros de log para não atrapalhar o fluxo principal
            error_log('DataSystem log error: ' . $e->getMessage());
        }
    }

    /**
     * Listar logs de chamadas DataSystem
     */
    public function getDataSystemLogs($limite = 100, $filtro = 'all') {
        $where = '1=1';
        $params = [];

        if ($filtro === 'errors') {
            $where .= ' AND sucesso = 0';
        } elseif ($filtro === 'success') {
            $where .= ' AND sucesso = 1';
        }

        return $this->db->fetchAll(
            "SELECT id, fonte_id, fonte_nome, method, endpoint, payload, http_code,
                    response_body, response_size, duracao_ms, sucesso, erro, sessao_id, criado_em
             FROM chatbot_datasystem_logs
             WHERE {$where}
             ORDER BY id DESC
             LIMIT ?",
            array_merge($params, [$limite])
        );
    }

    /**
     * Limpar logs DataSystem
     */
    public function clearDataSystemLogs() {
        $this->db->query("DELETE FROM chatbot_datasystem_logs");
    }

    /**
     * Estatísticas dos logs DataSystem
     */
    public function getDataSystemLogStats() {
        $total = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_datasystem_logs");
        $erros = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_datasystem_logs WHERE sucesso = 0");
        $hoje = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_datasystem_logs WHERE DATE(criado_em) = CURDATE()");
        $avgMs = (int)$this->db->fetchColumn("SELECT COALESCE(AVG(duracao_ms), 0) FROM chatbot_datasystem_logs WHERE sucesso = 1");

        return [
            'total' => $total,
            'erros' => $erros,
            'sucesso' => $total - $erros,
            'hoje' => $hoje,
            'avg_ms' => $avgMs,
        ];
    }

    /**
     * Testar conexão com DataSystem (autentica e testa 1 endpoint)
     */
    private function testarConexaoDataSystem($fonte) {
        try {
            $token = $this->authenticateDataSystem($fonte);

            // Testar /api/v1/lojas (simples, sem params obrigatórios)
            $result = $this->callDataSystemAPI($fonte, 'GET', '/api/v1/lojas');
            $lojas = $result['itens'] ?? $result['data'] ?? [];
            $qtd = is_array($lojas) ? count($lojas) : 0;

            return [
                'success' => true,
                'tipo' => 'api',
                'message' => "✅ DataSystem autenticado com sucesso! {$qtd} loja(s) encontrada(s).",
                'tables' => self::getDataSystemEndpoints(),
                'details' => [
                    'token' => substr($token, 0, 20) . '...',
                    'lojas' => $qtd,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'tipo' => 'api',
                'message' => 'DataSystem: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gera o schema visual dos endpoints DataSystem para o diagrama
     */
    private function getDataSystemSchemaInfo($fonte) {
        $endpoints = self::getDataSystemEndpoints();
        $result = [];
        foreach ($endpoints as $ep) {
            $cols = [];
            // Params como "colunas" do endpoint
            foreach ($ep['params'] as $p) {
                $cols[] = [
                    'Field' => $p['name'],
                    'Type' => ($p['required'] ? '* ' : '') . $p['type'],
                    'Key' => $p['required'] ? 'REQ' : '',
                ];
            }
            // Response fields como colunas adicionais
            if (!empty($ep['response_fields'])) {
                $respFields = array_map('trim', explode(',', preg_replace('/\{[^}]+\}|\[[^\]]+\]/', '', $ep['response_fields'])));
                foreach ($respFields as $rf) {
                    if (empty($rf)) continue;
                    $cols[] = [
                        'Field' => '→ ' . $rf,
                        'Type' => 'response',
                        'Key' => '',
                    ];
                }
            }

            $result[] = [
                'table' => $ep['method'] . ' ' . $ep['path'],
                'columns' => $cols,
                'row_count' => $ep['description'],
                'permitted' => true,
            ];
        }
        return $result;
    }

    /**
     * Contexto DataSystem para o prompt da IA (rico e detalhado)
     */
    private function getContextoDataSystem($fonte) {
        $alias = $fonte['alias'];
        $nome = $fonte['nome'];
        $descricao = $fonte['descricao'] ?? $fonte['api_descricao'] ?? '';
        $baseUrl = $fonte['api_url'] ?? '';

        $prompt = "### FONTE: {$nome} (alias: `{$alias}`, tipo: API DataSystem ERP)\n";
        if ($descricao) $prompt .= "Descrição: {$descricao}\n";
        $prompt .= "URL Base: {$baseUrl}\n\n";

        $prompt .= "**IMPORTANTE: Esta é uma API REST do ERP DataSystem (sistema de gestão de varejo/moda).**\n";
        $prompt .= "A autenticação é automática (JWT). Você NÃO precisa se preocupar com tokens.\n\n";

        $prompt .= "**Formato de chamada API:**\n";
        $prompt .= "```api:{$alias}\nGET /api/v1/endpoint?param1=valor1&param2=valor2\n```\n\n";

        $prompt .= "**REGRAS IMPORTANTES:**\n";
        $prompt .= "- Datas SEMPRE no formato yyyy-mm-dd (ex: 2026-03-27)\n";
        $prompt .= "- Use itensPorPagina=50 e pagina=1 como padrão\n";
        $prompt .= "- A resposta vem como { totalItens: N, itens: [...] }\n";
        $prompt .= "- Use campoOrdem=emissao e ordem=DESC para vendas recentes\n";
        $prompt .= "- Para 'vendas de hoje', use dataVendaInicio e dataVendaFim com a data atual\n";
        $prompt .= "- Para 'vendas do mês', use o primeiro e último dia do mês\n";
        $prompt .= "- Use APENAS GET para consultas de dados\n\n";

        $endpoints = self::getDataSystemEndpoints();
        $prompt .= "**Endpoints Disponíveis:**\n\n";

        foreach ($endpoints as $ep) {
            $prompt .= "**{$ep['method']} {$ep['path']}** - {$ep['description']}\n";

            if (!empty($ep['params'])) {
                $required = array_filter($ep['params'], fn($p) => $p['required']);
                $optional = array_filter($ep['params'], fn($p) => !$p['required']);

                if (!empty($required)) {
                    $prompt .= "  Obrigatórios: " . implode(', ', array_map(fn($p) => "`{$p['name']}` ({$p['type']} - {$p['description']})", $required)) . "\n";
                }
                if (!empty($optional)) {
                    $prompt .= "  Opcionais: " . implode(', ', array_map(fn($p) => "`{$p['name']}` ({$p['description']})", $optional)) . "\n";
                }
            }

            if (!empty($ep['response_fields'])) {
                $prompt .= "  Retorna: {$ep['response_fields']}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "**Exemplos de uso:**\n";
        $prompt .= "- Vendas de hoje: `GET /api/v1/vendas?dataVendaInicio=2026-03-27&dataVendaFim=2026-03-27&campoOrdem=emissao&ordem=DESC&itensPorPagina=50&pagina=1`\n";
        $prompt .= "- Buscar cliente por CPF: `GET /api/v1/clientes/cpfCnpj/12345678900`\n";
        $prompt .= "- Estoque da loja 1: `GET /api/v1/saldos?loja=1&itensPorPagina=50&pagina=1`\n";
        $prompt .= "- Listar lojas: `GET /api/v1/lojas`\n";

        return $prompt;
    }

    /**
     * Executar chamada API DataSystem a partir do bloco da IA
     * Entende o formato: GET /api/v1/endpoint?param=value
     */
    private function executarConsultaDataSystem($fonte, $apiCall, $sessaoId) {
        $lines = explode("\n", trim($apiCall));
        $firstLine = trim($lines[0]);

        // Parse: GET /api/v1/endpoint?param1=val&param2=val
        if (!preg_match('/^(GET|POST)\s+(\/\S+)/i', $firstLine, $parts)) {
            return null;
        }

        $method = strtoupper($parts[1]);
        $fullPath = $parts[2];

        // Separar path de query string
        $queryParams = [];
        if (strpos($fullPath, '?') !== false) {
            list($path, $qs) = explode('?', $fullPath, 2);
            parse_str($qs, $queryParams);
        } else {
            $path = $fullPath;
        }

        if ($method !== 'GET') return null; // Segurança: só GET

        try {
            $result = $this->callDataSystemAPI($fonte, $method, $path, $queryParams, null, $sessaoId);
            $itens = $result['itens'] ?? [];
            $totalItens = $result['totalItens'] ?? 0;

            $textoResultado = '';
            if (!empty($itens) && is_array($itens)) {
                // Formatar como tabela/lista
                if (isset($itens[0]) && is_array($itens[0])) {
                    $textoResultado = "Total: {$totalItens} registros (mostrando " . count($itens) . ")\n\n";
                    $textoResultado .= $this->formatarResultadoSQL($itens);
                } else {
                    $partes = [];
                    foreach ($itens as $k => $v) {
                        $partes[] = "*{$k}:* " . (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v);
                    }
                    $textoResultado = implode("\n", $partes);
                }
            } elseif (is_array($result['data'])) {
                // Resposta pode ser array direto (ex: lojas)
                $dados = $result['data'];
                if (isset($dados[0]) && is_array($dados[0])) {
                    $textoResultado = $this->formatarResultadoSQL($dados);
                } elseif (!empty($dados)) {
                    $partes = [];
                    foreach ($dados as $k => $v) {
                        $partes[] = "*{$k}:* " . (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v);
                    }
                    $textoResultado = implode("\n", $partes);
                }
            }

            return [
                'sql' => $method . ' ' . $fullPath,
                'dados' => $itens ?: $result['data'],
                'resposta_final' => !empty($textoResultado) ? $textoResultado : 'API DataSystem não retornou dados para essa consulta. 🤔',
            ];
        } catch (\Exception $e) {
            $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "DATASYSTEM ERRO [{$fonte['alias']}]: {$fullPath} | {$e->getMessage()}\n", FILE_APPEND);

            return [
                'sql' => $method . ' ' . $fullPath,
                'dados' => null,
                'resposta_final' => "Erro ao consultar DataSystem: " . $e->getMessage() . " 😅",
            ];
        }
    }

    // ==========================================
    //  CONTEXTO MULTI-FONTE PARA A IA
    // ==========================================

    /**
     * Gera o contexto combinado de TODAS as fontes de dados ativas
     * para inclusão no system prompt da IA
     */
    private function getContextoMultiFontes() {
        $fontes = $this->listarFontesDados(true); // apenas ativas
        if (empty($fontes)) return null;

        // Cache em arquivo
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

        $cacheKey = md5('multifontes_' . json_encode(array_map(function($f) {
            return $f['id'] . '_' . $f['atualizado_em'];
        }, $fontes)));
        $cacheFile = $cacheDir . '/db_context_multi_' . $cacheKey . '.cache';
        $cacheTTL = 300;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            $cached = file_get_contents($cacheFile);
            if (!empty($cached)) return $cached;
        }

        $contextoTotal = "## FONTES DE DADOS DISPONÍVEIS\n";
        $contextoTotal .= "Você tem acesso a " . count($fontes) . " fonte(s) de dados. ";
        $contextoTotal .= "Cada fonte tem um **alias** único que você DEVE usar para identificar de qual fonte buscar dados.\n\n";

        $fontesSQL = [];
        $fontesAPI = [];

        foreach ($fontes as $fonte) {
            $alias = $fonte['alias'];
            $nome = $fonte['nome'];

            if ($fonte['tipo'] === 'api') {
                $ctx = $this->getContextoFonteAPI($fonte);
                if ($ctx) {
                    $fontesAPI[] = $alias;
                    $contextoTotal .= $ctx . "\n\n";
                }
            } else {
                $ctx = $this->getContextoFonteSQL($fonte);
                if ($ctx) {
                    $fontesSQL[] = $alias;
                    $contextoTotal .= $ctx . "\n\n";
                }
            }
        }

        // Regras gerais
        $contextoTotal .= "### REGRAS GERAIS PARA CONSULTAS:\n";
        
        if (count($fontes) > 1) {
            $contextoTotal .= "- Quando gerar uma query SQL ou chamada API, SEMPRE indique a fonte com o formato:\n";
            $contextoTotal .= "  ```sql:ALIAS\n  SELECT ...\n  ```\n";
            $contextoTotal .= "  ou ```api:ALIAS\n  GET /endpoint\n  ```\n";
            $contextoTotal .= "- Se a pergunta pode envolver mais de uma fonte, gere MÚLTIPLAS consultas (uma para cada fonte)\n";
        } else {
            $alias = $fontes[0]['alias'] ?? 'principal';
            if ($fontes[0]['tipo'] === 'api') {
                $contextoTotal .= "- Envolva chamadas API entre: ```api:{$alias}\nGET /endpoint\n```\n";
            } else {
                $contextoTotal .= "- Envolva queries SQL entre: ```sql:{$alias}\nSELECT ...\n```\n";
            }
        }

        $contextoTotal .= "- Use APENAS SELECT (nunca INSERT, UPDATE, DELETE, DROP, ALTER)\n";
        $contextoTotal .= "- NÃO explique a query SQL na resposta\n";
        $contextoTotal .= "- Após gerar o SQL, AGUARDE os resultados\n";
        $contextoTotal .= "- NUNCA invente dados. Se não encontrar, diga que não há registros\n";
        $contextoTotal .= "- NUNCA termine a query SQL com ponto e vírgula (;)\n";
        $contextoTotal .= "- Se a pergunta não requer dados, responda normalmente sem SQL/API\n";

        // Salvar cache
        if (!empty($contextoTotal)) {
            file_put_contents($cacheFile, $contextoTotal);
        }

        return $contextoTotal;
    }

    /**
     * Gera contexto para uma fonte SQL individual
     */
    private function getContextoFonteSQL($fonte) {
        $tipo = $fonte['tipo'];
        $alias = $fonte['alias'];
        $nome = $fonte['nome'];
        $host = $fonte['db_host'] ?? '';

        if (empty($host) && $tipo !== 'sqlite') return null;

        try {
            $extDb = $this->getExternalDBForFonte($fonte);
            $dbName = $fonte['db_name'] ?? '';
            $descricao = $fonte['descricao'] ?? '';
            $tabelasPermitidas = $fonte['tabelas_permitidas'] ?? '';
            $maxRows = $fonte['max_rows'] ?? 50;

            $tablesRaw = $this->listarTabelasExternas($extDb, $tipo);
            $permitidas = !empty($tabelasPermitidas)
                ? array_map('trim', explode(',', $tabelasPermitidas))
                : $tablesRaw;

            $tables = [];
            foreach ($tablesRaw as $table) {
                if (!in_array($table, $permitidas)) continue;
                $cols = $this->listarColunasExterna($extDb, $tipo, $table);
                $colDefs = [];
                foreach ($cols as $col) {
                    $colDefs[] = $col['Field'] . ' (' . $col['Type'] . ')';
                }
                $tables[$table] = $colDefs;
            }

            // Relacionamentos
            $relacionamentos = json_decode($fonte['relacionamentos'] ?? '[]', true);
            if (!is_array($relacionamentos)) $relacionamentos = [];

            $driverLabel = strtoupper($tipo);
            $prompt = "### FONTE: {$nome} (alias: `{$alias}`, driver: {$driverLabel})\n";
            if ($descricao) $prompt .= "Descrição: {$descricao}\n";
            $prompt .= "Banco: `{$dbName}` | Máximo linhas: {$maxRows}\n\n";
            $prompt .= "**Tabelas e Colunas:**\n";
            foreach ($tables as $table => $cols) {
                $prompt .= "- **{$table}**: " . implode(', ', $cols) . "\n";
            }

            if (!empty($relacionamentos)) {
                $prompt .= "\n**Relacionamentos (JOINs):**\n";
                foreach ($relacionamentos as $rel) {
                    $joinStr = "JOIN {$rel['ref_tabela']} ON {$rel['ref_tabela']}.{$rel['ref_coluna']} = {$rel['tabela']}.{$rel['coluna']}";
                    $descCol = !empty($rel['coluna_descricao']) ? " → use {$rel['ref_tabela']}.{$rel['coluna_descricao']} para nome" : '';
                    $prompt .= "- `{$rel['tabela']}.{$rel['coluna']}` → `{$rel['ref_tabela']}.{$rel['ref_coluna']}` ({$joinStr}{$descCol})\n";
                }
                $prompt .= "- **SEMPRE use os JOINs acima** para trazer NOMES ao invés de IDs\n";
            }

            // Regras específicas por driver
            $prompt .= "\n**Regras SQL ({$driverLabel}):**\n";
            if ($tipo === 'mysql') {
                $prompt .= "- Use LIMIT {$maxRows} | Use crase (`) para nomes\n";
            } elseif ($tipo === 'pgsql') {
                $prompt .= "- Use LIMIT {$maxRows} | Aspas duplas para nomes com maiúsculas\n";
            } elseif ($tipo === 'sqlserver') {
                $prompt .= "- Use TOP {$maxRows} | Use colchetes [] para nomes\n";
            } elseif ($tipo === 'sqlite') {
                $prompt .= "- Use LIMIT {$maxRows}\n";
            }

            return $prompt;
        } catch (\Exception $e) {
            return "### FONTE: {$nome} (alias: `{$alias}`)\nErro ao conectar: " . $e->getMessage();
        }
    }

    /**
     * Gera contexto para uma fonte API individual
     */
    private function getContextoFonteAPI($fonte) {
        // DataSystem tem contexto especializado
        if (($fonte['api_template'] ?? '') === 'datasystem') {
            return $this->getContextoDataSystem($fonte);
        }

        $apiUrl = $fonte['api_url'] ?? '';
        if (empty($apiUrl)) return null;

        $alias = $fonte['alias'];
        $nome = $fonte['nome'];
        $descricao = $fonte['api_descricao'] ?? $fonte['descricao'] ?? '';

        $prompt = "### FONTE: {$nome} (alias: `{$alias}`, tipo: API REST)\n";
        if ($descricao) $prompt .= "Descrição: {$descricao}\n";
        $prompt .= "URL Base: {$apiUrl}\n\n";

        $endpointsJson = $fonte['api_endpoints'] ?? '';
        if (!empty($endpointsJson)) {
            $endpoints = json_decode($endpointsJson, true);
            if (is_array($endpoints)) {
                $prompt .= "**Endpoints Disponíveis:**\n";
                foreach ($endpoints as $ep) {
                    $method = strtoupper($ep['method'] ?? 'GET');
                    $path = $ep['path'] ?? '';
                    $desc = $ep['description'] ?? '';
                    $prompt .= "- **{$method} {$path}**";
                    if ($desc) $prompt .= " - {$desc}";
                    $prompt .= "\n";
                    if (!empty($ep['params'])) {
                        foreach ($ep['params'] as $p) {
                            $pName = is_array($p) ? ($p['name'] ?? '') : $p;
                            $pType = is_array($p) ? ($p['type'] ?? 'string') : 'string';
                            $pDesc = is_array($p) ? ($p['description'] ?? '') : '';
                            $prompt .= "  - `{$pName}` ({$pType})";
                            if ($pDesc) $prompt .= " - {$pDesc}";
                            $prompt .= "\n";
                        }
                    }
                }
            }
        }

        $prompt .= "\n**Regras API:** Use APENAS GET para leitura\n";
        return $prompt;
    }

    /**
     * Processa SQL/API extraído da resposta da IA, roteando para a fonte correta
     * Suporta formato: ```sql:alias\nSELECT...\n``` ou ```sql\nSELECT...\n```
     */
    private function processarConsultaMultiFonte($content, $sessaoId) {
        $resultados = [];
        $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';

        // 1. Extrair blocos com alias: ```sql:alias ... ``` ou ```api:alias ... ```
        $pattern = '/```(?:(sql|api|mysql|pgsql|postgresql|sqlite|sqlserver|tsql)(?::([a-z0-9_]+))?)\s*\R([\s\S]*?)```/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            // Fallback: tentar formato antigo sem alias
            return $this->processarConsultaMultiFonteFallback($content, $sessaoId);
        }

        $fontes = $this->listarFontesDados(true);
        $fontesMap = [];
        foreach ($fontes as $f) {
            $fontesMap[$f['alias']] = $f;
        }

        // Se só tem uma fonte, usar ela como default
        $defaultFonte = count($fontes) === 1 ? $fontes[0] : null;

        foreach ($matches as $match) {
            $tipoBloco = strtolower($match[1]);
            $alias = strtolower($match[2] ?? '');
            $queryBody = trim($match[3]);

            if (empty($queryBody)) continue;

            // Resolver fonte
            $fonte = null;
            if (!empty($alias) && isset($fontesMap[$alias])) {
                $fonte = $fontesMap[$alias];
            } elseif ($defaultFonte) {
                $fonte = $defaultFonte;
            } else {
                // Tentar adivinhar pela primeira fonte do tipo correspondente
                foreach ($fontes as $f) {
                    if ($tipoBloco === 'api' && $f['tipo'] === 'api') { $fonte = $f; break; }
                    if ($tipoBloco !== 'api' && $f['tipo'] !== 'api') { $fonte = $f; break; }
                }
            }

            if (!$fonte) continue;

            if ($fonte['tipo'] === 'api') {
                $resultado = $this->executarConsultaAPIFonte($fonte, $queryBody, $sessaoId);
            } else {
                $resultado = $this->executarConsultaSQLFonte($fonte, $queryBody, $sessaoId);
            }

            if ($resultado) {
                $resultado['fonte_alias'] = $fonte['alias'];
                $resultado['fonte_nome'] = $fonte['nome'];
                $resultados[] = $resultado;
            }
        }

        if (empty($resultados)) return null;

        // Combinar resultados
        return $this->combinarResultadosFontes($resultados, $content);
    }

    /**
     * Fallback: processar consulta sem alias (compatibilidade com formato antigo)
     */
    private function processarConsultaMultiFonteFallback($content, $sessaoId) {
        $fontes = $this->listarFontesDados(true);
        if (empty($fontes)) return null;

        // Usar a primeira fonte ativa como default
        $fonte = $fontes[0];

        if ($fonte['tipo'] === 'api') {
            // Tentar extrair bloco ```api ... ```
            if (!preg_match('/```(?:api)\s*\R([\s\S]*?)```/i', $content, $match)) {
                return null;
            }
            $resultado = $this->executarConsultaAPIFonte($fonte, trim($match[1]), $sessaoId);
        } else {
            // Tentar extrair SQL
            $sql = $this->extrairSQLDoTexto($content);
            if (empty($sql)) return null;
            $resultado = $this->executarConsultaSQLFonte($fonte, $sql, $sessaoId);
        }

        if (!$resultado) return null;

        $resultado['fonte_alias'] = $fonte['alias'];
        $resultado['fonte_nome'] = $fonte['nome'];

        return $this->combinarResultadosFontes([$resultado], $content);
    }

    /**
     * Executar SQL em uma fonte específica
     */
    private function executarConsultaSQLFonte($fonte, $sql, $sessaoId) {
        $sql = rtrim($sql, '; \t\n\r');
        $sqlUpper = strtoupper(trim($sql));

        // Segurança
        if (!str_starts_with($sqlUpper, 'SELECT') && !str_starts_with($sqlUpper, 'WITH')) return null;
        if (!preg_match('/\bSELECT\b/i', $sql)) return null;

        $blocked = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'EXEC', 'GRANT', 'REVOKE'];
        foreach ($blocked as $cmd) {
            if (preg_match('/\b' . $cmd . '\b/i', $sql)) return null;
        }

        $tipo = $fonte['tipo'];
        $maxRows = (int)($fonte['max_rows'] ?? 50);

        if ($tipo === 'sqlserver') {
            if (!preg_match('/\bTOP\b/i', $sql)) {
                $sql = preg_replace('/^SELECT\b/i', "SELECT TOP {$maxRows}", $sql);
            }
        } else {
            if (!preg_match('/\bLIMIT\b/i', $sql)) {
                $sql .= " LIMIT {$maxRows}";
            }
        }

        try {
            $extDb = $this->getExternalDBForFonte($fonte);
            $stmt = $extDb->query($sql);
            $dados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'sql' => $sql,
                'dados' => $dados,
                'resposta_final' => !empty($dados) ? $this->formatarRespostaRapida('', $dados) : "Não encontrei dados para essa consulta. 🤔",
            ];
        } catch (\Exception $e) {
            $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "SQL ERRO [{$fonte['alias']}]: {$sql} | {$e->getMessage()}\n", FILE_APPEND);

            return [
                'sql' => $sql,
                'dados' => null,
                'resposta_final' => "Não consegui buscar informação na fonte \"{$fonte['nome']}\" agora. 😅",
            ];
        }
    }

    /**
     * Executar chamada API em uma fonte específica
     */
    private function executarConsultaAPIFonte($fonte, $apiCall, $sessaoId) {
        // DataSystem tem executor especializado com auth JWT
        if (($fonte['api_template'] ?? '') === 'datasystem') {
            return $this->executarConsultaDataSystem($fonte, $apiCall, $sessaoId);
        }

        $lines = explode("\n", $apiCall, 2);
        $firstLine = trim($lines[0]);
        $body = isset($lines[1]) ? trim($lines[1]) : null;

        if (!preg_match('/^(GET|POST|PUT|PATCH)\s+(\/\S*)$/i', $firstLine, $parts)) {
            return null;
        }

        $method = strtoupper($parts[1]);
        $path = $parts[2];

        if (!in_array($method, ['GET', 'POST'])) return null;

        try {
            $result = $this->callFonteAPI($fonte, $method, $path, $body ? json_decode($body, true) : null);
            $dados = $result['data'];

            $textoResultado = '';
            if (is_array($dados)) {
                if (isset($dados[0]) && is_array($dados[0])) {
                    $textoResultado = $this->formatarResultadoSQL($dados);
                } else {
                    $parts = [];
                    foreach ($dados as $k => $v) {
                        $parts[] = "*{$k}:* " . (is_array($v) ? json_encode($v) : $v);
                    }
                    $textoResultado = implode("\n", $parts);
                }
            }

            return [
                'sql' => $method . ' ' . $path,
                'dados' => $dados,
                'resposta_final' => !empty($textoResultado) ? $textoResultado : 'API não retornou dados.',
            ];
        } catch (\Exception $e) {
            return [
                'sql' => $method . ' ' . $path,
                'dados' => null,
                'resposta_final' => "Erro na API \"{$fonte['nome']}\": " . $e->getMessage(),
            ];
        }
    }

    /**
     * Combinar resultados de múltiplas fontes em uma resposta unificada
     */
    private function combinarResultadosFontes($resultados, $contentOriginal) {
        if (count($resultados) === 1) {
            return $resultados[0];
        }

        // Múltiplos resultados: combinar
        $sqlCombinado = [];
        $dadosCombinados = [];
        $respostasCombinadas = [];

        foreach ($resultados as $r) {
            $sqlCombinado[] = "[{$r['fonte_nome']}] {$r['sql']}";
            if (!empty($r['dados'])) {
                $dadosCombinados = array_merge($dadosCombinados, is_array($r['dados']) ? $r['dados'] : []);
            }
            if (!empty($r['resposta_final'])) {
                $label = count($resultados) > 1 ? "📊 *{$r['fonte_nome']}:*\n" : '';
                $respostasCombinadas[] = $label . $r['resposta_final'];
            }
        }

        return [
            'sql' => implode("\n", $sqlCombinado),
            'dados' => $dadosCombinados,
            'resposta_final' => implode("\n\n", $respostasCombinadas),
            'fonte_alias' => 'multi',
            'fonte_nome' => 'Múltiplas fontes',
        ];
    }

    public function isAtivo() {
        return $this->getConfig('chatbot_ativo', '0') === '1';
    }

    // ==========================================
    //  NÚMEROS AUTORIZADOS
    // ==========================================

    public function listarNumeros() {
        return $this->db->fetchAll("SELECT * FROM chatbot_numeros_autorizados ORDER BY nome ASC, numero ASC");
    }

    public function isNumeroAutorizado($numero) {
        $numero = $this->normalizarNumero($numero);
        return (bool) $this->db->fetch(
            "SELECT id FROM chatbot_numeros_autorizados WHERE numero = ? AND ativo = 1",
            [$numero]
        );
    }

    public function addNumero($numero, $nome = null, $notas = null) {
        $numero = $this->normalizarNumero($numero);
        return $this->db->query(
            "INSERT INTO chatbot_numeros_autorizados (numero, nome, notas) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE nome = VALUES(nome), notas = VALUES(notas), ativo = 1",
            [$numero, $nome, $notas]
        );
    }

    public function removeNumero($id) {
        return $this->db->delete('chatbot_numeros_autorizados', 'id = ?', [$id]);
    }

    public function toggleNumero($id) {
        $n = $this->db->fetch("SELECT ativo FROM chatbot_numeros_autorizados WHERE id = ?", [$id]);
        if (!$n) return false;
        $novoStatus = $n['ativo'] ? 0 : 1;
        $this->db->update('chatbot_numeros_autorizados', ['ativo' => $novoStatus], 'id = ?', [$id]);
        return $novoStatus;
    }

    private function normalizarNumero($numero) {
        // Remover tudo exceto dígitos
        $numero = preg_replace('/\D/', '', $numero);
        // Se começa com 0, remover
        $numero = ltrim($numero, '0');
        // Se não tem código de país, adicionar 55 (Brasil)
        if (strlen($numero) <= 11) {
            $numero = '55' . $numero;
        }
        // Brasil: se 12 dígitos (55+DD+8 dígitos), adicionar o 9° dígito
        // WhatsApp às vezes envia sem o 9 (formato antigo)
        if (strlen($numero) === 12 && str_starts_with($numero, '55')) {
            $ddd = substr($numero, 2, 2);
            $fone = substr($numero, 4); // 8 dígitos
            // Celulares brasileiros começam com 9,8,7,6
            if (in_array($fone[0], ['6','7','8','9'])) {
                $numero = '55' . $ddd . '9' . $fone;
            }
        }
        return $numero;
    }

    // ==========================================
    //  SESSÕES
    // ==========================================

    public function listarSessoes($limite = 50) {
        return $this->db->fetchAll(
            "SELECT s.*, 
                    (SELECT COUNT(*) FROM chatbot_mensagens WHERE sessao_id = s.id) as total_msgs,
                    (SELECT mensagem FROM chatbot_mensagens WHERE sessao_id = s.id ORDER BY criado_em DESC LIMIT 1) as ultima_msg
             FROM chatbot_sessoes s 
             ORDER BY s.ultimo_acesso DESC 
             LIMIT ?",
            [$limite]
        );
    }

    public function getOrCreateSession($numero, $nomeContato = '') {
        $numero = $this->normalizarNumero($numero);
        $session = $this->db->fetch("SELECT * FROM chatbot_sessoes WHERE numero_whatsapp = ?", [$numero]);

        if (!$session) {
            $id = $this->db->insert('chatbot_sessoes', [
                'numero_whatsapp' => $numero,
                'nome_contato' => $nomeContato ?: $numero,
            ]);
            $session = $this->db->fetch("SELECT * FROM chatbot_sessoes WHERE id = ?", [$id]);
        } else {
            $this->db->update('chatbot_sessoes', [
                'ultimo_acesso' => date('Y-m-d H:i:s'),
                'nome_contato' => $nomeContato ?: $session['nome_contato'],
            ], 'id = ?', [$session['id']]);
        }

        return $session;
    }

    public function getSession($id) {
        return $this->db->fetch("SELECT * FROM chatbot_sessoes WHERE id = ?", [$id]);
    }

    public function limparSessao($id) {
        $this->db->delete('chatbot_mensagens', 'sessao_id = ?', [$id]);
        $this->db->update('chatbot_sessoes', [
            'contexto_resumo' => null,
            'total_mensagens' => 0,
        ], 'id = ?', [$id]);
        return true;
    }

    // ==========================================
    //  MENSAGENS
    // ==========================================

    public function getHistorico($sessaoId, $limite = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM chatbot_mensagens WHERE sessao_id = ? ORDER BY criado_em ASC LIMIT ?",
            [$sessaoId, $limite]
        );
    }

    public function salvarMensagem($sessaoId, $remetente, $mensagem, $tipo = 'text', $extra = []) {
        $id = $this->db->insert('chatbot_mensagens', [
            'sessao_id' => $sessaoId,
            'remetente' => $remetente,
            'mensagem' => $mensagem,
            'tipo' => $tipo,
            'sql_executado' => $extra['sql'] ?? null,
            'dados_consulta' => isset($extra['dados']) ? json_encode($extra['dados']) : null,
            'tokens_usados' => $extra['tokens'] ?? 0,
            'duracao_ms' => $extra['duracao_ms'] ?? 0,
        ]);

        // Atualizar contador da sessão
        $this->db->query(
            "UPDATE chatbot_sessoes SET total_mensagens = total_mensagens + 1, ultimo_acesso = NOW() WHERE id = ?",
            [$sessaoId]
        );

        return $id;
    }

    // ==========================================
    //  PROCESSAMENTO DE MENSAGEM (CORE)
    // ==========================================

    /**
     * Processa uma mensagem recebida do WhatsApp
     * Retorna a resposta do bot
     */
    public function processarMensagem($numero, $mensagem, $nomeContato = '') {
        // 1. Verificar se chatbot está ativo
        if (!$this->isAtivo()) {
            return null; // Silenciosamente ignora
        }

        // 2. Verificar horário de atendimento
        if ($this->getConfig('chatbot_horario_ativo') === '1' && !$this->isDentroHorario()) {
            return $this->getConfig('chatbot_msg_fora_horario');
        }

        // 3. Verificar se número é autorizado
        if (!$this->isNumeroAutorizado($numero)) {
            return $this->getConfig('chatbot_msg_nao_autorizado');
        }

        // 4. Obter ou criar sessão
        $session = $this->getOrCreateSession($numero, $nomeContato);

        // 5. Salvar mensagem do usuário
        $this->salvarMensagem($session['id'], 'user', $mensagem);

        // 6. Notificar N8N (se ativo) - antes de processar
        $this->notificarN8N('mensagem_recebida', [
            'numero' => $numero,
            'nome' => $nomeContato,
            'mensagem' => $mensagem,
            'sessao_id' => $session['id'],
        ]);

        // 7. Gerar resposta com IA
        try {
            $resposta = $this->gerarRespostaIA($session, $mensagem, $numero);
        } catch (\Exception $e) {
            $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERRO processarMensagem: " . $e->getMessage() . "\n", FILE_APPEND);
            $resposta = $this->getConfig('chatbot_msg_erro');
            $this->salvarMensagem($session['id'], 'bot', $resposta, 'error', [
                'erro' => $e->getMessage(),
            ]);
            return $resposta;
        }

        // 8. Notificar N8N (se ativo) - depois de processar
        $this->notificarN8N('resposta_gerada', [
            'numero' => $numero,
            'nome' => $nomeContato,
            'pergunta' => $mensagem,
            'resposta' => $resposta,
            'sessao_id' => $session['id'],
        ]);

        return $resposta;
    }

    /**
     * Verifica se está dentro do horário de atendimento
     */
    private function isDentroHorario() {
        $diasAtivos = explode(',', $this->getConfig('chatbot_dias_semana', '1,2,3,4,5'));
        $diaAtual = (int) date('w'); // 0=Dom, 1=Seg...6=Sab

        if (!in_array($diaAtual, $diasAtivos)) return false;

        $agora = date('H:i');
        $inicio = $this->getConfig('chatbot_horario_inicio', '08:00');
        $fim = $this->getConfig('chatbot_horario_fim', '18:00');

        return ($agora >= $inicio && $agora <= $fim);
    }

    // ==========================================
    //  INTELIGÊNCIA ARTIFICIAL (Ollama)
    // ==========================================

    /**
     * Gera resposta da IA com contexto do histórico + base de dados
     * Otimizado: UMA ÚNICA chamada à IA (SQL + resposta amigável no mesmo prompt)
     */
    private function gerarRespostaIA($session, $mensagem, $numero = null) {
        $start = microtime(true);
        $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';

        // Montar mensagens de contexto
        $messages = $this->montarContextoIA($session, $mensagem);

        // Chamar Ollama
        $ollamaUrl = rtrim($this->getIAConfig('ollama_url', 'http://localhost:11434'), '/');
        $modelo = $this->getConfig('chatbot_ia_modelo');
        if (empty($modelo)) {
            $modelo = $this->getIAConfig('modelo_padrao', 'llama3');
        }

        $payload = [
            'model' => $modelo,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => (float) $this->getConfig('chatbot_ia_temperatura', '0.5'),
                'num_predict' => (int) $this->getConfig('chatbot_ia_max_tokens', '1024'),
            ],
        ];

        try {
            $response = $this->httpPost($ollamaUrl . '/api/chat', $payload);
        } catch (\Exception $e) {
            $duracao = round((microtime(true) - $start) * 1000);
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERRO IA [{$duracao}ms]: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
        }

        $duraIa1 = round((microtime(true) - $start) * 1000);
        $content = $response['message']['content'] ?? '';
        $tokens = ($response['prompt_eval_count'] ?? 0) + ($response['eval_count'] ?? 0);

        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "IA OK [{$duraIa1}ms, {$tokens}tok]: " . mb_substr($content, 0, 120) . "\n", FILE_APPEND);

        // Verificar se a IA gerou uma query SQL ou chamada API
        $sqlResult = null;
        if ($this->getConfig('chatbot_db_ativo') === '1') {
            $hasQuery = preg_match('/```(?:sql|api|mysql|pgsql|postgresql|sqlite|sqlserver|tsql)(?::[a-z0-9_]+)?\s*\R/i', $content)
                || preg_match('/(?:^|\R)\s*sql\s*:?\s*\R/i', $content)
                || preg_match('/(?:^|\R)\s*(WITH|SELECT)\b/i', $content);

            // Tentar multi-fonte primeiro
            $fontesAtivas = $this->listarFontesDados(true);
            if (!empty($fontesAtivas)) {
                $sqlResult = $this->processarConsultaMultiFonte($content, $session['id']);
            } else {
                // Fallback: fonte única antiga
                $sqlResult = $this->processarSQLDaResposta($content, $session['id']);
            }

            // Se a IA indicou consulta, mas não conseguimos executar/parsing,
            // não enviar texto técnico/enrolado para o usuário.
            if ($hasQuery && !$sqlResult) {
                $content = "Tive dificuldade para interpretar a consulta agora. Pode repetir a pergunta em uma frase curta? Ex.: \"Qual marca campeã de vendas e o estoque dela?\"";
            }

            // Se teve resultado de consulta COM dados, formatar direto em PHP (SEM segunda chamada à IA!)
            if ($sqlResult && !empty($sqlResult['dados'])) {
                $startFmt = microtime(true);
                $respostaAmigavel = $this->formatarRespostaRapida($mensagem, $sqlResult['dados']);
                if ($this->getConfig('chatbot_timeline_raciocinio', '1') === '1') {
                    $timeline = $this->montarTimelineRaciocinio($mensagem, $sqlResult);
                    if ($timeline !== '') {
                        $respostaAmigavel = $timeline . "\n\n" . $respostaAmigavel;
                    }
                }
                $duraFmt = round((microtime(true) - $startFmt) * 1000);
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "FMT PHP [{$duraFmt}ms]: " . mb_substr($respostaAmigavel, 0, 100) . "\n", FILE_APPEND);
                
                if (!empty($respostaAmigavel)) {
                    $sqlResult['resposta_final'] = $respostaAmigavel;
                }
            }
        }

        $duracao = round((microtime(true) - $start) * 1000);

        if ($sqlResult) {
            // Garantir que resposta_final nunca fique vazia
            $content = $sqlResult['resposta_final'] ?? $content;
            if (empty($content)) {
                $content = "Desculpe, não consegui formatar a resposta. Pode tentar novamente? 😅";
            }
            // Salvar mensagem do bot com dados da query
            $this->salvarMensagem($session['id'], 'bot', $content, 'query', [
                'sql' => $sqlResult['sql'],
                'dados' => $sqlResult['dados'],
                'tokens' => $tokens,
                'duracao_ms' => $duracao,
            ]);
        } else {
            // Salvar mensagem normal do bot
            $this->salvarMensagem($session['id'], 'bot', $content, 'text', [
                'tokens' => $tokens,
                'duracao_ms' => $duracao,
            ]);
        }

        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "TOTAL [{$duracao}ms, {$tokens}tok]\n", FILE_APPEND);
        return $content;
    }

    /**
     * Linha do tempo curta do raciocínio (transparência sem vazar SQL).
     */
    private function montarTimelineRaciocinio($pergunta, array $sqlResult) {
        $p = mb_strtolower((string) $pergunta, 'UTF-8');
        $nReg = isset($sqlResult['dados']) && is_array($sqlResult['dados']) ? count($sqlResult['dados']) : 0;

        $foco = 'sua pergunta';
        if (preg_match('/\b(marca|marcas|ranking|top|campe|lider)\b/u', $p)) {
            $foco = 'ranking de marcas (vendas e estoque)';
        } elseif (preg_match('/\b(estoque|saldo|dispon[ií]vel)\b/u', $p)) {
            $foco = 'posicoes de estoque';
        } elseif (preg_match('/\b(venda|vendas|faturamento)\b/u', $p)) {
            $foco = 'volume de vendas';
        } elseif (preg_match('/\b(pedido|pedidos)\b/u', $p)) {
            $foco = 'pedidos';
        } elseif (preg_match('/\b(cliente|clientes)\b/u', $p)) {
            $foco = 'dados de clientes';
        }

        $linhas = [
            '🧠 *Como cheguei aqui*',
            '1️⃣ Li sua mensagem e identifiquei que voce queria saber sobre *' . $foco . '*.',
            '2️⃣ Consultei os dados reais no seu banco (sem chutar numeros).',
        ];
        if ($nReg > 0) {
            $linhas[] = '3️⃣ Organizei o resultado em ' . $nReg . ' registro' . ($nReg > 1 ? 's' : '') . ' para ficar facil de ler.';
        } else {
            $linhas[] = '3️⃣ Cruzei as informacoes e montei a resposta abaixo.';
        }

        return implode("\n", $linhas);
    }

    /**
     * Normaliza nome de coluna para comparacao (acentos, espacos).
     */
    private function normalizarNomeColunaParaMatch($nome) {
        $n = mb_strtolower(trim((string) $nome), 'UTF-8');
        $n = preg_replace('/\s+/', '_', $n);
        return $n;
    }

    /**
     * Detecta colunas de marca + vendas + estoque com nomes variados (SQL/alias).
     *
     * @return array{marca: string, vendas: string, estoque: string}|null
     */
    private function detectarColunasRankingMarcaVendasEstoque(array $colunas) {
        $marcaKey = null;
        $vendasKey = null;
        $estoqueKey = null;

        foreach ($colunas as $c) {
            $nm = $this->normalizarNomeColunaParaMatch($c);
            if ($estoqueKey === null && preg_match('/(estoque|stock|saldo|invent|dispon)/u', $nm)) {
                $estoqueKey = $c;
            }
        }
        foreach ($colunas as $c) {
            if ($c === $estoqueKey) {
                continue;
            }
            $nm = $this->normalizarNomeColunaParaMatch($c);
            if ($marcaKey === null && preg_match('/(marca|brand|fabricante)/u', $nm) && !preg_match('/(vend|estoque|qtd|quant)/u', $nm)) {
                $marcaKey = $c;
            }
        }
        foreach ($colunas as $c) {
            if ($c === $estoqueKey || $c === $marcaKey) {
                continue;
            }
            $nm = $this->normalizarNomeColunaParaMatch($c);
            if ($vendasKey === null && preg_match('/(vend|unidad|qtd|quant|fatur|sold|total)/u', $nm)) {
                $vendasKey = $c;
            }
        }

        if ($marcaKey && $vendasKey && $estoqueKey) {
            return ['marca' => $marcaKey, 'vendas' => $vendasKey, 'estoque' => $estoqueKey];
        }

        return null;
    }

    private function formatarNumeroBr($val) {
        if ($val === null || $val === '') {
            return '-';
        }
        if (is_numeric($val)) {
            $f = (float) $val;
            return number_format($f, (floor($f) == $f ? 0 : 2), ',', '.');
        }
        return (string) $val;
    }

    /**
     * Ranking estilo "Top N" com medalhas (ex.: marcas).
     */
    private function formatarRankingMarcasTopN(array $dados, array $keys, $pergunta) {
        $n = count($dados);
        $p = mb_strtolower((string) $pergunta, 'UTF-8');
        $titulo = '📊 Top ' . $n . ' Marcas Mais Vendidas';
        if (preg_match('/\b(campe|s[oó]\s*marca|uma\s*marca|qual\s*marca)\b/u', $p) && $n === 1) {
            $titulo = '📊 Marca em destaque';
        }

        $linhas = [$titulo, ''];
        $medalhas = ['🥇', '🥈', '🥉'];

        foreach ($dados as $i => $row) {
            $pos = $i + 1;
            $marca = $row[$keys['marca']] ?? '-';
            $v = $this->formatarNumeroBr($row[$keys['vendas']] ?? null);
            $e = $this->formatarNumeroBr($row[$keys['estoque']] ?? null);

            $estoqueNum = is_numeric($row[$keys['estoque']] ?? null) ? (float) $row[$keys['estoque']] : null;
            $avisoEstoque = ($estoqueNum !== null && $estoqueNum <= 0) ? ' ⚠️' : '';

            if ($i < 3) {
                $prefixo = $medalhas[$i] . ' ' . $pos . 'º — *' . $marca . '*';
            } else {
                $prefixo = $pos . 'º — *' . $marca . '*';
            }

            $linhas[] = $prefixo;
            $linhas[] = 'Vendas: ' . $v;
            $linhas[] = 'Estoque: ' . $e . $avisoEstoque;
            $linhas[] = '';
        }

        return rtrim(implode("\n", $linhas));
    }

    /**
     * Formata o resultado SQL de forma amigável SEM chamar a IA (resposta instantânea)
     */
    private function formatarRespostaRapida($pergunta, $dados) {
        if (empty($dados)) return "Não encontrei dados para essa consulta. 🤔";

        $totalRows = count($dados);
        $colunas = array_keys($dados[0]);
        $numColunas = count($colunas);

        // Ranking de marcas: detecta colunas variadas (marca, Total vendido, Estoque total, etc.)
        $rankingKeys = $this->detectarColunasRankingMarcaVendasEstoque($colunas);
        if ($rankingKeys !== null && $totalRows >= 1) {
            return $this->formatarRankingMarcasTopN($dados, $rankingKeys, $pergunta);
        }

        // Caso 1: Resultado único (COUNT, SUM, etc.) — ex: [{"count": 1234}]
        if ($totalRows === 1 && $numColunas === 1) {
            $valor = array_values($dados[0])[0];
            $nomeCol = strtolower($colunas[0]);
            $valorFmt = is_numeric($valor) ? number_format((float)$valor, 0, ',', '.') : $valor;
            
            // Detectar tipo de métrica pelo nome da coluna
            if (str_contains($nomeCol, 'count') || str_contains($nomeCol, 'total') || str_contains($nomeCol, 'qtd') || str_contains($nomeCol, 'quantidade')) {
                return "📊 O total é *{$valorFmt}*! 😊";
            } elseif (str_contains($nomeCol, 'sum') || str_contains($nomeCol, 'valor') || str_contains($nomeCol, 'preco') || str_contains($nomeCol, 'custo')) {
                return "💰 O valor total é *R$ {$valorFmt}*! 😊";
            } elseif (str_contains($nomeCol, 'avg') || str_contains($nomeCol, 'media') || str_contains($nomeCol, 'média')) {
                return "📈 A média é *{$valorFmt}*! 😊";
            }
            return "📊 Resultado: *{$valorFmt}* 😊";
        }

        // Caso 2: Resultado único com múltiplas colunas (1 registro detalhado)
        if ($totalRows === 1 && $numColunas > 1) {
            $linhas = ["📋 Aqui está o resultado:\n"];
            foreach ($dados[0] as $col => $val) {
                $label = ucfirst(str_replace('_', ' ', $col));
                $valFmt = is_numeric($val) ? number_format((float)$val, (floor($val) == $val ? 0 : 2), ',', '.') : ($val ?? '-');
                $linhas[] = "• *{$label}*: {$valFmt}";
            }
            return implode("\n", $linhas);
        }

        // Caso 3: Tabela com agrupamento (ex: departamento + count) — lista organizada
        if ($numColunas === 2) {
            $colNome = $colunas[0];
            $colValor = $colunas[1];
            $maxShow = min($totalRows, 20);
            
            $linhas = ["📊 Aqui estão os resultados:\n"];
            $total = 0;
            for ($i = 0; $i < $maxShow; $i++) {
                $nome = $dados[$i][$colNome] ?? '-';
                $val = $dados[$i][$colValor] ?? 0;
                $total += is_numeric($val) ? (float)$val : 0;
                $valFmt = is_numeric($val) ? number_format((float)$val, (floor((float)$val) == (float)$val ? 0 : 2), ',', '.') : $val;
                $linhas[] = "• *{$nome}*: {$valFmt}";
            }
            if ($totalRows > $maxShow) {
                $linhas[] = "\n_...e mais " . ($totalRows - $maxShow) . " itens_";
            }
            // Se parece ser numérico, mostrar total
            if ($total > 0 && is_numeric($dados[0][$colValor])) {
                $totalFmt = number_format($total, (floor($total) == $total ? 0 : 2), ',', '.');
                $linhas[] = "\n*Total geral: {$totalFmt}* ✅";
            }
            return implode("\n", $linhas);
        }

        // Caso 4: Tabela genérica com múltiplas colunas (resposta enxuta)
        $maxShow = min($totalRows, 15);
        $linhas = ["Encontrei esses dados para voce:\n"];
        
        for ($i = 0; $i < $maxShow; $i++) {
            $row = $dados[$i];
            $partes = [];
            foreach ($row as $col => $val) {
                $label = ucfirst(str_replace('_', ' ', $col));
                $valFmt = is_numeric($val) ? number_format((float)$val, (floor((float)$val) == (float)$val ? 0 : 2), ',', '.') : ($val ?? '-');
                $partes[] = "*{$label}*: {$valFmt}";
            }
            $linhas[] = ($i + 1) . ". " . implode(' | ', $partes);
        }
        if ($totalRows > $maxShow) {
            $linhas[] = "\n_...e mais " . ($totalRows - $maxShow) . " registros_";
        }
        $linhas[] = "\nSe quiser, eu refino por periodo, marca ou loja para ficar mais direto. 😉";
        return implode("\n", $linhas);
    }

    /**
     * Gera resposta amigável com os dados da consulta usando a IA
     */
    private function gerarRespostaAmigavel($messages, $perguntaOriginal, $sqlResult, $ollamaUrl, $modelo, $options) {
        // Formatar dados para a IA interpretar
        $dadosTexto = $this->formatarResultadoSQL($sqlResult['dados']);

        $promptDados = "O usuário perguntou: \"{$perguntaOriginal}\"\n";
        $promptDados .= "Eu fiz uma consulta e obtive estes resultados:\n{$dadosTexto}\n\n";
        $promptDados .= "Agora responda ao usuário de forma AMIGÁVEL e CONVERSACIONAL, como se fosse uma pessoa.\n";
        $promptDados .= "- Use emojis para ficar mais descontraído\n";
        $promptDados .= "- NÃO mencione SQL, queries, tabelas ou banco de dados\n";
        $promptDados .= "- NÃO repita os dados em formato técnico (campo: valor)\n";
        $promptDados .= "- Responda como se você SOUBESSE a informação de cabeça\n";
        $promptDados .= "- Se for um número grande, use separador de milhares (ex: 12.751)\n";
        $promptDados .= "- Seja breve e direto, mas simpático\n";

        $messagesAmigavel = [
            ['role' => 'system', 'content' => 'Você é um assistente simpático e conversacional. Responda de forma natural como uma pessoa, sem termos técnicos.'],
            ['role' => 'user', 'content' => $promptDados],
        ];

        try {
            $resp = $this->httpPost($ollamaUrl . '/api/chat', [
                'model' => $modelo,
                'messages' => $messagesAmigavel,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => (int) ($options['num_predict'] ?? 512),
                ],
            ]);
            return $resp['message']['content'] ?? $sqlResult['resposta_final'];
        } catch (\Exception $e) {
            return $sqlResult['resposta_final'];
        }
    }

    /**
     * Monta o contexto de mensagens para a IA
     */
    private function montarContextoIA($session, $mensagem) {
        $messages = [];

        // System prompt
        $systemPrompt = $this->getConfig('chatbot_ia_system_prompt');

        // Adicionar contexto das fontes de dados (multi-fonte)
        if ($this->getConfig('chatbot_db_ativo') === '1') {
            $dbContexto = $this->getContextoMultiFontes();
            if (!$dbContexto) {
                // Fallback para fonte única (compatibilidade)
                $dbContexto = $this->getContextoBancoDados();
            }
            if ($dbContexto) {
                $systemPrompt .= "\n\n" . $dbContexto;
            }
        }

        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        // Histórico recente
        $maxContexto = (int) $this->getConfig('chatbot_ia_contexto_max', '10');
        $historico = $this->db->fetchAll(
            "SELECT remetente, mensagem FROM chatbot_mensagens 
             WHERE sessao_id = ? ORDER BY criado_em DESC LIMIT ?",
            [$session['id'], $maxContexto]
        );

        // Reverter para ordem cronológica
        $historico = array_reverse($historico);
        foreach ($historico as $msg) {
            $messages[] = [
                'role' => $msg['remetente'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['mensagem'],
            ];
        }

        // Mensagem atual
        $messages[] = ['role' => 'user', 'content' => $mensagem];

        return $messages;
    }

    /**
     * Gera o contexto da fonte de dados para o system prompt
     * Suporta: banco de dados (multi-driver) ou API REST
     * Usa cache em arquivo (5 min) para evitar queries pesadas a cada mensagem
     */
    private function getContextoBancoDados() {
        $tipo = $this->getConfig('chatbot_db_tipo', 'mysql');

        if ($tipo === 'api') {
            return $this->getContextoAPI();
        }

        // Cache em arquivo para evitar refazer schema/FK/amostragem a cada mensagem
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $cacheKey = md5($tipo . '_' . $this->getConfig('chatbot_db_host') . '_' . $this->getConfig('chatbot_db_name') . '_' . $this->getConfig('chatbot_db_tabelas_permitidas'));
        $cacheFile = $cacheDir . '/db_context_' . $cacheKey . '.cache';
        $cacheTTL = 300; // 5 minutos

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            $cached = file_get_contents($cacheFile);
            if (!empty($cached)) {
                return $cached;
            }
        }

        $contexto = $this->getContextoBancoSQL($tipo);

        // Salvar no cache
        if (!empty($contexto)) {
            file_put_contents($cacheFile, $contexto);
        }

        return $contexto;
    }

    /**
     * Contexto para bancos SQL (MySQL, PostgreSQL, SQL Server, SQLite)
     * Usa relacionamentos manuais definidos pelo usuário (rápido e preciso)
     */
    private function getContextoBancoSQL($tipo) {
        $host = $this->getConfig('chatbot_db_host');
        if (empty($host) && $tipo !== 'sqlite') return null;

        $descricao = $this->getConfig('chatbot_db_descricao');
        $tabelasPermitidas = $this->getConfig('chatbot_db_tabelas_permitidas');
        $maxRows = $this->getConfig('chatbot_db_max_rows', '50');

        try {
            $extDb = $this->getExternalDB();
            $dbName = $this->getConfig('chatbot_db_name');

            $tablesRaw = $this->listarTabelasExternas($extDb, $tipo);
            $permitidas = !empty($tabelasPermitidas) 
                ? array_map('trim', explode(',', $tabelasPermitidas)) 
                : $tablesRaw;

            $tables = [];
            foreach ($tablesRaw as $table) {
                if (!in_array($table, $permitidas)) continue;
                $cols = $this->listarColunasExterna($extDb, $tipo, $table);
                $colDefs = [];
                foreach ($cols as $col) {
                    $colDefs[] = $col['Field'] . ' (' . $col['Type'] . ')';
                }
                $tables[$table] = $colDefs;
            }

            // Carregar relacionamentos manuais (definidos pelo usuário no painel)
            $relacionamentosJson = $this->getConfig('chatbot_db_relacionamentos', '[]');
            $relacionamentos = json_decode($relacionamentosJson, true);
            if (!is_array($relacionamentos)) $relacionamentos = [];

            $driverLabel = strtoupper($tipo);
            $prompt = "## BASE DE DADOS DISPONÍVEL ({$driverLabel})\n";
            if ($descricao) $prompt .= "Descrição: {$descricao}\n\n";
            $prompt .= "Banco: `{$dbName}` | Driver: {$driverLabel}\n";
            $prompt .= "Máximo de linhas por consulta: {$maxRows}\n\n";
            $prompt .= "### Tabelas e Colunas:\n";
            foreach ($tables as $table => $cols) {
                $prompt .= "- **{$table}**: " . implode(', ', $cols) . "\n";
            }

            // Adicionar relacionamentos manuais
            if (!empty($relacionamentos)) {
                $prompt .= "\n### Relacionamentos entre Tabelas (JOINs obrigatórios):\n";
                foreach ($relacionamentos as $rel) {
                    $joinStr = "JOIN {$rel['ref_tabela']} ON {$rel['ref_tabela']}.{$rel['ref_coluna']} = {$rel['tabela']}.{$rel['coluna']}";
                    $descCol = !empty($rel['coluna_descricao']) ? " → use {$rel['ref_tabela']}.{$rel['coluna_descricao']} para exibir o nome" : '';
                    $prompt .= "- `{$rel['tabela']}.{$rel['coluna']}` → `{$rel['ref_tabela']}.{$rel['ref_coluna']}` ({$joinStr}{$descCol})\n";
                }
            }

            $prompt .= "\n### REGRAS DE CONSULTA SQL ({$driverLabel}):\n";
            $prompt .= "- Quando o usuário fizer uma pergunta que requer dados, gere uma query SQL\n";
            $prompt .= "- Envolva a query SQL entre marcadores: ```sql\nSELECT...\n```\n";
            $prompt .= "- Use APENAS SELECT (nunca INSERT, UPDATE, DELETE, DROP, ALTER)\n";

            if (!empty($relacionamentos)) {
                $prompt .= "- **SEMPRE use os JOINs definidos acima** para trazer NOMES ao invés de IDs/códigos\n";
                $prompt .= "- NUNCA retorne IDs ou códigos numéricos sozinhos. Sempre traga o nome/descrição legível via JOIN\n";
            }

            $prompt .= "- Use aliases claros (ex: c.nome AS cliente, p.nome AS produto)\n";

            // Regras específicas por driver
            if ($tipo === 'mysql') {
                $prompt .= "- Limite os resultados com LIMIT {$maxRows}\n";
                $prompt .= "- Use crase (`) para nomes de tabelas e colunas\n";
            } elseif ($tipo === 'pgsql') {
                $prompt .= "- Limite os resultados com LIMIT {$maxRows}\n";
                $prompt .= "- Use aspas duplas (\") para nomes com maiúsculas/espaços\n";
                $prompt .= "- Strings usam aspas simples (')\n";
            } elseif ($tipo === 'sqlserver') {
                $prompt .= "- Limite os resultados com TOP {$maxRows} (ex: SELECT TOP {$maxRows} ...)\n";
                $prompt .= "- Use colchetes [] para nomes de tabelas e colunas\n";
            } elseif ($tipo === 'sqlite') {
                $prompt .= "- Limite os resultados com LIMIT {$maxRows}\n";
            }

            $prompt .= "- NÃO explique a query SQL na resposta. NÃO mostre detalhes técnicos ao usuário\n";
            $prompt .= "- Após gerar o SQL, AGUARDE os resultados. Diga algo breve antes do SQL como 'Vou consultar!'\n";
            $prompt .= "- Se a pergunta não requer dados do banco, responda normalmente sem SQL\n";
            $prompt .= "- NUNCA invente dados. Se não encontrar, diga que não há registros\n";
            $prompt .= "- NUNCA termine a query SQL com ponto e vírgula (;)\n";
            $prompt .= "- Gere queries SIMPLES e EFICIENTES. Use COUNT, SUM, GROUP BY quando apropriado\n";
            $prompt .= "- Para perguntas sobre quantidade, use COUNT(*) ou SUM()\n";

            return $prompt;
        } catch (\Exception $e) {
            return "## BASE DE DADOS\nErro ao conectar: " . $e->getMessage();
        }
    }

    /**
     * Contexto para API REST como fonte de dados
     */
    private function getContextoAPI() {
        $apiUrl = $this->getConfig('chatbot_api_url');
        if (empty($apiUrl)) return null;

        $descricao = $this->getConfig('chatbot_api_descricao');
        $endpointsJson = $this->getConfig('chatbot_api_endpoints');

        $prompt = "## API REST DISPONÍVEL\n";
        if ($descricao) $prompt .= "Descrição: {$descricao}\n\n";
        $prompt .= "URL Base: {$apiUrl}\n\n";

        if (!empty($endpointsJson)) {
            $endpoints = json_decode($endpointsJson, true);
            if (is_array($endpoints)) {
                $prompt .= "### Endpoints Disponíveis:\n";
                foreach ($endpoints as $ep) {
                    $method = strtoupper($ep['method'] ?? 'GET');
                    $path = $ep['path'] ?? '';
                    $desc = $ep['description'] ?? '';
                    $prompt .= "- **{$method} {$path}**";
                    if ($desc) $prompt .= " - {$desc}";
                    $prompt .= "\n";
                    if (!empty($ep['params'])) {
                        foreach ($ep['params'] as $p) {
                            $pName = is_array($p) ? ($p['name'] ?? '') : $p;
                            $pType = is_array($p) ? ($p['type'] ?? 'string') : 'string';
                            $pDesc = is_array($p) ? ($p['description'] ?? '') : '';
                            $prompt .= "  - `{$pName}` ({$pType})";
                            if ($pDesc) $prompt .= " - {$pDesc}";
                            $prompt .= "\n";
                        }
                    }
                    if (!empty($ep['response_example'])) {
                        $prompt .= "  Exemplo de resposta: `" . json_encode($ep['response_example']) . "`\n";
                    }
                }
            }
        }

        $prompt .= "\n### REGRAS DE CONSULTA API:\n";
        $prompt .= "- Quando o usuário fizer uma pergunta que requer dados, gere uma chamada API\n";
        $prompt .= "- Envolva a chamada entre marcadores: ```api\nMETHOD /endpoint?param=value\n```\n";
        $prompt .= "- Para POST com body JSON: ```api\nPOST /endpoint\n{\"key\": \"value\"}\n```\n";
        $prompt .= "- Use APENAS endpoints de leitura (GET). NUNCA use POST/PUT/DELETE para modificar dados\n";
        $prompt .= "- Após receber os dados, explique os resultados de forma clara e amigável\n";
        $prompt .= "- NUNCA invente dados. Se a API retornar erro, informe ao usuário\n";

        return $prompt;
    }

    // ==========================================
    //  API REST - Chamadas externas
    // ==========================================

    /**
     * Monta os headers de autenticação para a API externa
     */
    private function buildAPIHeaders() {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $authTipo = $this->getConfig('chatbot_api_auth_tipo', 'none');
        $apiKey = $this->getConfig('chatbot_api_key');

        switch ($authTipo) {
            case 'bearer':
                if ($apiKey) $headers[] = 'Authorization: Bearer ' . $apiKey;
                break;
            case 'apikey':
                $headerName = $this->getConfig('chatbot_api_auth_header', 'Authorization');
                if ($apiKey) $headers[] = $headerName . ': ' . $apiKey;
                break;
            case 'basic':
                $user = $this->getConfig('chatbot_api_auth_user');
                $pass = $this->getConfig('chatbot_api_auth_pass');
                if ($user) $headers[] = 'Authorization: Basic ' . base64_encode($user . ':' . $pass);
                break;
        }

        return $headers;
    }

    /**
     * Chama a API REST externa
     */
    public function callExternalAPI($method, $path, $body = null) {
        $baseUrl = rtrim($this->getConfig('chatbot_api_url'), '/');
        if (empty($baseUrl)) {
            throw new \Exception('URL da API não configurada');
        }

        $url = $baseUrl . '/' . ltrim($path, '/');
        $headers = $this->buildAPIHeaders();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL: ' . $error);
        }

        return [
            'http_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true),
        ];
    }

    /**
     * Detecta e executa SQL ou chamada API gerada pela IA na resposta
     */
    private function processarSQLDaResposta($content, $sessaoId) {
        $tipo = $this->getConfig('chatbot_db_tipo', 'mysql');

        // Tentar processar bloco ```api ...``` (para fonte API)
        if ($tipo === 'api') {
            return $this->processarAPIDaResposta($content, $sessaoId);
        }

        // Extrair SQL (aceita bloco markdown ou formato cru: "sql\nWITH..." / "SELECT ...")
        $sql = $this->extrairSQLDoTexto($content);
        if (empty($sql)) {
            return null;
        }

        // Remover ponto e vírgula final (a IA às vezes gera)
        $sql = rtrim($sql, '; \t\n\r');

        // SEGURANÇA: permitir SELECT e CTE (WITH ... SELECT)
        $sqlUpper = strtoupper(trim($sql));
        if (!str_starts_with($sqlUpper, 'SELECT') && !str_starts_with($sqlUpper, 'WITH')) {
            return null;
        }
        // Mesmo com WITH, precisa conter SELECT em algum ponto
        if (!preg_match('/\bSELECT\b/i', $sql)) {
            return null;
        }

        // Bloquear qualquer tentativa de injeção
        $blocked = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'EXEC', 'GRANT', 'REVOKE'];
        foreach ($blocked as $cmd) {
            if (preg_match('/\b' . $cmd . '\b/i', $sql)) {
                return null;
            }
        }

        // Garantir limitação de linhas
        $maxRows = (int) $this->getConfig('chatbot_db_max_rows', '50');
        if ($tipo === 'sqlserver') {
            // SQL Server usa TOP ao invés de LIMIT
            if (!preg_match('/\bTOP\b/i', $sql)) {
                $sql = preg_replace('/^SELECT\b/i', "SELECT TOP {$maxRows}", $sql);
            }
        } else {
            if (!preg_match('/\bLIMIT\b/i', $sql)) {
                $sql .= " LIMIT {$maxRows}";
            }
        }

        // Executar no banco externo
        try {
            $extDb = $this->getExternalDB();
            $stmt = $extDb->query($sql);
            $dados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Nunca vazar SQL/prompt técnico para o usuário final
            if (!empty($dados)) {
                $respostaFinal = $this->formatarRespostaRapida('', $dados);
            } else {
                $respostaFinal = "Não encontrei dados para essa consulta. 🤔";
            }

            return [
                'sql' => $sql,
                'dados' => $dados,
                'resposta_final' => $respostaFinal,
            ];
        } catch (\Exception $e) {
            // Log técnico detalhado para debug
            $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';
            $errMsg = $e->getMessage();
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "SQL ERRO: {$sql} | {$errMsg}\n", FILE_APPEND);

            // Salvar erro na tabela de mensagens para visualização
            try {
                $this->salvarMensagem($sessaoId, 'bot', '', 'error', [
                    'sql' => $sql,
                    'erro' => $errMsg,
                    'duracao_ms' => 0,
                ]);
            } catch (\Exception $e2) { /* silenciar */ }

            return [
                'sql' => $sql,
                'dados' => null,
                'resposta_final' => "Não consegui buscar essa informação agora. Pode tentar de outra forma? 😅",
            ];
        }
    }

    /**
     * Extrai uma query SQL de uma resposta da IA.
     * Aceita:
     * - Bloco markdown: ```sql ... ```
     * - Formato cru: "sql\nWITH/SELECT ..."
     * - SQL direto iniciando com WITH/SELECT
     */
    private function extrairSQLDoTexto($content) {
        // 1) Bloco markdown com linguagem SQL
        if (preg_match('/```(?:sql|mysql|pgsql|postgresql|sqlite|sqlserver|tsql)\s*\R([\s\S]*?)```/i', $content, $m)) {
            return trim($m[1]);
        }

        // 2) Prefixo "sql" em linha própria, sem crases
        if (preg_match('/(?:^|\R)\s*sql\s*:?\s*\R([\s\S]*)$/i', $content, $m)) {
            $raw = trim($m[1]);
            // Pega apenas o primeiro bloco antes de parágrafo explicativo
            $raw = preg_split('/\R{2,}/', $raw)[0] ?? $raw;
            return trim($raw);
        }

        // 3) SQL direto (WITH/SELECT) até o primeiro parágrafo separado
        if (preg_match('/(?:^|\R)\s*((?:WITH|SELECT)\b[\s\S]*)$/i', $content, $m)) {
            $raw = trim($m[1]);
            $raw = preg_split('/\R{2,}/', $raw)[0] ?? $raw;
            return trim($raw);
        }

        return null;
    }

    /**
     * Processa chamada API gerada pela IA na resposta
     * Formato: ```api\nGET /endpoint?param=value\n```
     * ou: ```api\nPOST /endpoint\n{"key":"value"}\n```
     */
    private function processarAPIDaResposta($content, $sessaoId) {
        if (!preg_match('/```(?:api)\s*\R([\s\S]*?)```/i', $content, $match)) {
            return null;
        }

        $apiCall = trim($match[1]);
        $lines = explode("\n", $apiCall, 2);
        $firstLine = trim($lines[0]);
        $body = isset($lines[1]) ? trim($lines[1]) : null;

        // Parse "METHOD /path?query"
        if (!preg_match('/^(GET|POST|PUT|PATCH)\s+(\/\S*)$/i', $firstLine, $parts)) {
            return null;
        }

        $method = strtoupper($parts[1]);
        $path = $parts[2];

        // SEGURANÇA: Apenas GET e POST permitidos para leitura
        if (!in_array($method, ['GET', 'POST'])) {
            return null;
        }

        try {
            $result = $this->callExternalAPI($method, $path, $body ? json_decode($body, true) : null);
            $dados = $result['data'];

            $textoResultado = '';
            if (is_array($dados)) {
                // Se é array de objetos, formatar como tabela
                if (isset($dados[0]) && is_array($dados[0])) {
                    $textoResultado = $this->formatarResultadoSQL($dados);
                } else {
                    // Objeto único ou resposta simples
                    $parts = [];
                    foreach ($dados as $k => $v) {
                        $parts[] = "*{$k}:* " . (is_array($v) ? json_encode($v) : $v);
                    }
                    $textoResultado = implode("\n", $parts);
                }
            } else {
                $textoResultado = $result['body'] ?? '';
            }

            $respostaFinal = preg_replace('/```(?:api)\s*\R[\s\S]*?```/i', '', $content);
            $respostaFinal = trim($respostaFinal);
            if (!empty($textoResultado)) {
                $respostaFinal .= "\n\n📊 *Resultado da API:*\n" . $textoResultado;
            } else {
                $respostaFinal .= "\n\n📊 API não retornou dados.";
            }

            return [
                'sql' => $method . ' ' . $path,
                'dados' => $dados,
                'resposta_final' => $respostaFinal,
            ];
        } catch (\Exception $e) {
            return [
                'sql' => $method . ' ' . $path,
                'dados' => null,
                'resposta_final' => trim(preg_replace('/```(?:api)\s*\R[\s\S]*?```/i', '', $content))
                    . "\n\n⚠️ Erro na chamada API: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Formata resultado SQL para texto legível no WhatsApp
     */
    private function formatarResultadoSQL($dados) {
        if (empty($dados)) return '';

        $lines = [];
        
        // Se é uma única linha com poucas colunas (agregação), formato simples
        if (count($dados) === 1 && count($dados[0]) <= 2) {
            foreach ($dados[0] as $col => $val) {
                $lines[] = "*{$col}:* {$val}";
            }
            return implode("\n", $lines);
        }

        // Múltiplas linhas: formato tabular
        foreach ($dados as $i => $row) {
            $parts = [];
            foreach ($row as $col => $val) {
                $parts[] = "*{$col}:* " . ($val ?? 'N/A');
            }
            $lines[] = ($i + 1) . ". " . implode(' | ', $parts);
        }

        return implode("\n", $lines);
    }

    /**
     * Conexão com banco de dados externo (multi-driver)
     * Suporta: mysql, pgsql, sqlserver, sqlite
     */
    private $extDbConn = null;

    private function getExternalDB() {
        if ($this->extDbConn) return $this->extDbConn;

        $tipo = $this->getConfig('chatbot_db_tipo', 'mysql');

        if ($tipo === 'api') {
            throw new \Exception('Fonte de dados é API REST, não banco de dados');
        }

        $host = $this->getConfig('chatbot_db_host');
        $port = $this->getConfig('chatbot_db_port');
        $name = $this->getConfig('chatbot_db_name');
        $user = $this->getConfig('chatbot_db_user');
        $pass = $this->getConfig('chatbot_db_pass');

        switch ($tipo) {
            case 'mysql':
                $port = $port ?: '3306';
                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
                break;
            case 'pgsql':
                $port = $port ?: '5432';
                $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
                break;
            case 'sqlserver':
                $port = $port ?: '1433';
                $dsn = "sqlsrv:Server={$host},{$port};Database={$name}";
                break;
            case 'sqlite':
                // Para SQLite, 'host' contém o caminho do arquivo
                $dsn = "sqlite:{$host}";
                $user = null;
                $pass = null;
                break;
            default:
                throw new \Exception("Driver de banco não suportado: {$tipo}");
        }

        if ($tipo !== 'sqlite' && (empty($host) || empty($name))) {
            throw new \Exception('Banco de dados externo não configurado');
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 10,
        ];

        $this->extDbConn = new \PDO($dsn, $user, $pass, $options);

        // Charset para PostgreSQL
        if ($tipo === 'pgsql') {
            $this->extDbConn->exec("SET client_encoding TO 'UTF8'");
        }

        return $this->extDbConn;
    }

    /**
     * Retorna o tipo da fonte de dados ativa
     */
    public function getFonteDadosTipo() {
        return $this->getConfig('chatbot_db_tipo', 'mysql');
    }

    /**
     * Verifica se a fonte de dados é uma API REST
     */
    public function isFonteAPI() {
        return $this->getConfig('chatbot_db_tipo', 'mysql') === 'api';
    }

    /**
     * Testar conexão com fonte de dados (banco ou API)
     */
    public function testarConexaoDB() {
        $tipo = $this->getConfig('chatbot_db_tipo', 'mysql');

        if ($tipo === 'api') {
            return $this->testarConexaoAPI();
        }

        try {
            $db = $this->getExternalDB();
            $tables = $this->listarTabelasExternas($db, $tipo);
            return [
                'success' => true,
                'tipo' => $tipo,
                'tables' => $tables,
                'message' => count($tables) . ' tabelas encontradas (' . strtoupper($tipo) . ')',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'tipo' => $tipo,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Testar conexão com API REST externa
     */
    public function testarConexaoAPI() {
        $apiUrl = $this->getConfig('chatbot_api_url');
        if (empty($apiUrl)) {
            return ['success' => false, 'tipo' => 'api', 'message' => 'URL da API não configurada'];
        }

        try {
            $headers = $this->buildAPIHeaders();
            $ch = curl_init(rtrim($apiUrl, '/'));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_NOBODY => false,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'tipo' => 'api', 'message' => 'cURL: ' . $error];
            }

            return [
                'success' => $httpCode >= 200 && $httpCode < 400,
                'tipo' => 'api',
                'http_code' => $httpCode,
                'message' => $httpCode >= 200 && $httpCode < 400
                    ? "API acessível (HTTP {$httpCode})"
                    : "API retornou HTTP {$httpCode}",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'tipo' => 'api', 'message' => $e->getMessage()];
        }
    }

    /**
     * Listar tabelas do banco externo (multi-driver)
     */
    private function listarTabelasExternas($db, $tipo) {
        switch ($tipo) {
            case 'mysql':
                return $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            case 'pgsql':
                return $db->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename")->fetchAll(\PDO::FETCH_COLUMN);
            case 'sqlserver':
                return $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(\PDO::FETCH_COLUMN);
            case 'sqlite':
                return $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
            default:
                return [];
        }
    }

    /**
     * Listar colunas de uma tabela (multi-driver)
     */
    private function listarColunasExterna($db, $tipo, $table) {
        switch ($tipo) {
            case 'mysql':
                return $db->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            case 'pgsql':
                $cols = $db->query("SELECT column_name AS \"Field\", data_type AS \"Type\", is_nullable AS \"Null\", column_default AS \"Default\" FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{$table}' ORDER BY ordinal_position")->fetchAll(\PDO::FETCH_ASSOC);
                return $cols;
            case 'sqlserver':
                $cols = $db->query("SELECT COLUMN_NAME AS [Field], DATA_TYPE AS [Type], IS_NULLABLE AS [Null], COLUMN_DEFAULT AS [Default] FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$table}' ORDER BY ORDINAL_POSITION")->fetchAll(\PDO::FETCH_ASSOC);
                return $cols;
            case 'sqlite':
                $cols = $db->query("PRAGMA table_info('{$table}')")->fetchAll(\PDO::FETCH_ASSOC);
                // Normalizar para formato {Field, Type}
                return array_map(function($c) {
                    return ['Field' => $c['name'], 'Type' => $c['type'], 'Null' => $c['notnull'] ? 'NO' : 'YES', 'Default' => $c['dflt_value']];
                }, $cols);
            default:
                return [];
        }
    }

    /**
     * Contar registros em tabela (multi-driver)
     */
    private function contarRegistrosExterna($db, $tipo, $table) {
        $quote = ($tipo === 'mysql') ? '`' : '"';
        if ($tipo === 'sqlserver') {
            return $db->query("SELECT COUNT(*) FROM [{$table}]")->fetchColumn();
        }
        return $db->query("SELECT COUNT(*) FROM {$quote}{$table}{$quote}")->fetchColumn();
    }

    /**
     * Detecta relacionamentos entre tabelas (FK real + convenção de nomes)
     * Retorna array de: tabela, coluna, ref_tabela, ref_coluna, descricao
     */
    private function detectarRelacionamentos($db, $tipo, $dbName, $allCols, $permitidas) {
        $rels = [];

        // 1. Tentar FK reais do banco
        try {
            $fks = $this->obterForeignKeys($db, $tipo, $dbName, $permitidas);
            foreach ($fks as $fk) {
                $rels[] = $fk;
            }
        } catch (\Exception $e) { /* silenciar */ }

        // 2. Detectar por convenção de nomes (_id, _cod, id_)
        $tabelasConhecidas = array_keys($allCols);
        foreach ($allCols as $tabela => $colunas) {
            foreach ($colunas as $col) {
                // Pular colunas que já têm FK real
                $jaTemFK = false;
                foreach ($rels as $r) {
                    if ($r['tabela'] === $tabela && $r['coluna'] === $col) {
                        $jaTemFK = true;
                        break;
                    }
                }
                if ($jaTemFK) continue;

                // Detectar padrão: coluna_id, id_coluna, coluna_cod, cod_coluna
                $refTabela = null;
                if (preg_match('/^(.+?)_id$/i', $col, $m)) {
                    $refTabela = $m[1];
                } elseif (preg_match('/^id_(.+)$/i', $col, $m)) {
                    $refTabela = $m[1];
                } elseif (preg_match('/^(.+?)_cod$/i', $col, $m)) {
                    $refTabela = $m[1];
                } elseif (preg_match('/^cod_(.+)$/i', $col, $m)) {
                    $refTabela = $m[1];
                }

                if (!$refTabela) continue;

                // Tentar encontrar a tabela referenciada (singular/plural/variações)
                $candidatos = [
                    $refTabela,
                    $refTabela . 's',
                    $refTabela . 'es',
                    rtrim($refTabela, 's'),
                    'tb_' . $refTabela,
                    'tb_' . $refTabela . 's',
                    $refTabela . '_cadastro',
                ];

                foreach ($candidatos as $cand) {
                    // Case-insensitive match
                    foreach ($tabelasConhecidas as $t) {
                        if (strtolower($t) === strtolower($cand) && $t !== $tabela) {
                            // Encontrar coluna de referência (id, cod, codigo, nome)
                            $refCol = 'id';
                            if (in_array('id', $allCols[$t])) $refCol = 'id';
                            elseif (in_array('cod', $allCols[$t])) $refCol = 'cod';
                            elseif (in_array('codigo', $allCols[$t])) $refCol = 'codigo';

                            // Detectar coluna de nome/descrição na tabela referenciada
                            $nomeCol = $this->detectarColunaNome($allCols[$t]);

                            $descExtra = $nomeCol ? " (use {$t}.{$nomeCol} para obter o nome)" : '';

                            $rels[] = [
                                'tabela' => $tabela,
                                'coluna' => $col,
                                'ref_tabela' => $t,
                                'ref_coluna' => $refCol,
                                'descricao' => "JOIN {$t} ON {$t}.{$refCol} = {$tabela}.{$col}{$descExtra}",
                            ];
                            break 2;
                        }
                    }
                }
            }
        }

        return $rels;
    }

    /**
     * Obter foreign keys reais do banco
     */
    private function obterForeignKeys($db, $tipo, $dbName, $permitidas) {
        $fks = [];

        switch ($tipo) {
            case 'mysql':
                $rows = $db->query("
                    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = '{$dbName}' AND REFERENCED_TABLE_NAME IS NOT NULL
                ")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    if (!in_array($r['TABLE_NAME'], $permitidas) || !in_array($r['REFERENCED_TABLE_NAME'], $permitidas)) continue;
                    $fks[] = [
                        'tabela' => $r['TABLE_NAME'],
                        'coluna' => $r['COLUMN_NAME'],
                        'ref_tabela' => $r['REFERENCED_TABLE_NAME'],
                        'ref_coluna' => $r['REFERENCED_COLUMN_NAME'],
                        'descricao' => "FK real: JOIN {$r['REFERENCED_TABLE_NAME']} ON {$r['REFERENCED_TABLE_NAME']}.{$r['REFERENCED_COLUMN_NAME']} = {$r['TABLE_NAME']}.{$r['COLUMN_NAME']}",
                    ];
                }
                break;
            case 'pgsql':
                $rows = $db->query("
                    SELECT tc.table_name, kcu.column_name, ccu.table_name AS ref_table, ccu.column_name AS ref_column
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_schema = 'public'
                ")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    if (!in_array($r['table_name'], $permitidas) || !in_array($r['ref_table'], $permitidas)) continue;
                    $fks[] = [
                        'tabela' => $r['table_name'],
                        'coluna' => $r['column_name'],
                        'ref_tabela' => $r['ref_table'],
                        'ref_coluna' => $r['ref_column'],
                        'descricao' => "FK real: JOIN {$r['ref_table']} ON {$r['ref_table']}.{$r['ref_column']} = {$r['table_name']}.{$r['column_name']}",
                    ];
                }
                break;
            case 'sqlserver':
                $rows = $db->query("
                    SELECT fk.name, tp.name AS tabela, cp.name AS coluna, tr.name AS ref_tabela, cr.name AS ref_coluna
                    FROM sys.foreign_keys fk
                    JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
                    JOIN sys.tables tp ON fkc.parent_object_id = tp.object_id
                    JOIN sys.columns cp ON fkc.parent_object_id = cp.object_id AND fkc.parent_column_id = cp.column_id
                    JOIN sys.tables tr ON fkc.referenced_object_id = tr.object_id
                    JOIN sys.columns cr ON fkc.referenced_object_id = cr.object_id AND fkc.referenced_column_id = cr.column_id
                ")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    if (!in_array($r['tabela'], $permitidas) || !in_array($r['ref_tabela'], $permitidas)) continue;
                    $fks[] = [
                        'tabela' => $r['tabela'],
                        'coluna' => $r['coluna'],
                        'ref_tabela' => $r['ref_tabela'],
                        'ref_coluna' => $r['ref_coluna'],
                        'descricao' => "FK real: JOIN {$r['ref_tabela']} ON {$r['ref_tabela']}.{$r['ref_coluna']} = {$r['tabela']}.{$r['coluna']}",
                    ];
                }
                break;
            // SQLite: não tem FK no information_schema facilmente
        }

        return $fks;
    }

    /**
     * Detecta qual coluna contém o "nome" ou "descrição" de uma tabela
     */
    private function detectarColunaNome($colunas) {
        $prioridade = ['nome', 'name', 'descricao', 'description', 'titulo', 'title', 'razao_social', 'nome_fantasia', 'label', 'denominacao'];
        foreach ($prioridade as $p) {
            foreach ($colunas as $col) {
                if (strtolower($col) === $p) return $col;
            }
        }
        // Fallback: primeira coluna que contenha 'nome' ou 'desc'
        foreach ($colunas as $col) {
            if (stripos($col, 'nome') !== false || stripos($col, 'name') !== false || stripos($col, 'desc') !== false) {
                return $col;
            }
        }
        return null;
    }

    /**
     * Amostra dados de tabelas pequenas de referência (categorias, status, tipos)
     * Para que a IA conheça os valores possíveis
     */
    private function amostrarTabelasReferencia($db, $tipo, $allCols, $permitidas) {
        $amostras = [];
        $maxAmostras = 30; // Máx registros por tabela de referência

        foreach ($allCols as $tabela => $colunas) {
            if (!in_array($tabela, $permitidas)) continue;

            // Heurística: tabelas de referência são pequenas e têm colunas id + nome
            $temId = false;
            $temNome = false;
            foreach ($colunas as $col) {
                if (strtolower($col) === 'id' || strtolower($col) === 'cod' || strtolower($col) === 'codigo') $temId = true;
                if ($this->detectarColunaNome([$col])) $temNome = true;
            }
            if (!$temId || !$temNome) continue;

            // Verificar se é tabela pequena (lookup)
            try {
                $count = $this->contarRegistrosExterna($db, $tipo, $tabela);
                if ($count > $maxAmostras || $count === 0) continue;

                // É uma tabela de referência! Buscar os dados
                $idCol = null;
                $nomeCol = $this->detectarColunaNome($colunas);
                foreach ($colunas as $col) {
                    if (in_array(strtolower($col), ['id', 'cod', 'codigo'])) { $idCol = $col; break; }
                }
                if (!$idCol || !$nomeCol) continue;

                $quote = ($tipo === 'mysql') ? '`' : '"';
                if ($tipo === 'sqlserver') {
                    $rows = $db->query("SELECT TOP {$maxAmostras} [{$idCol}], [{$nomeCol}] FROM [{$tabela}]")->fetchAll(\PDO::FETCH_ASSOC);
                } else {
                    $rows = $db->query("SELECT {$quote}{$idCol}{$quote}, {$quote}{$nomeCol}{$quote} FROM {$quote}{$tabela}{$quote} LIMIT {$maxAmostras}")->fetchAll(\PDO::FETCH_ASSOC);
                }

                if (!empty($rows)) {
                    $amostras[$tabela] = $rows;
                }
            } catch (\Exception $e) { /* silenciar */ }
        }

        return $amostras;
    }

    /**
     * Obter schema do banco externo (multi-driver) ou info da API
     */
    public function getExternalDBSchema() {
        $tipo = $this->getConfig('chatbot_db_tipo', 'mysql');

        if ($tipo === 'api') {
            return $this->getAPIEndpointsInfo();
        }

        $db = $this->getExternalDB();
        $tables = $this->listarTabelasExternas($db, $tipo);
        $tabelasPermitidas = $this->getConfig('chatbot_db_tabelas_permitidas');
        $permitidas = !empty($tabelasPermitidas) 
            ? array_map('trim', explode(',', $tabelasPermitidas)) 
            : $tables;

        $schema = [];
        foreach ($tables as $table) {
            $cols = $this->listarColunasExterna($db, $tipo, $table);
            $count = $this->contarRegistrosExterna($db, $tipo, $table);
            $schema[] = [
                'table' => $table,
                'columns' => $cols,
                'row_count' => $count,
                'permitted' => in_array($table, $permitidas),
            ];
        }

        return $schema;
    }

    /**
     * Retorna info dos endpoints da API REST (para o painel)
     */
    private function getAPIEndpointsInfo() {
        $endpoints = $this->getConfig('chatbot_api_endpoints');
        if (empty($endpoints)) {
            return [];
        }
        $parsed = json_decode($endpoints, true);
        if (!is_array($parsed)) return [];

        // Normalizar formato para exibição
        $result = [];
        foreach ($parsed as $ep) {
            $result[] = [
                'table' => ($ep['method'] ?? 'GET') . ' ' . ($ep['path'] ?? ''),
                'columns' => array_map(function($p) {
                    return ['Field' => $p['name'] ?? $p, 'Type' => $p['type'] ?? 'string'];
                }, $ep['params'] ?? []),
                'row_count' => $ep['description'] ?? '',
                'permitted' => true,
            ];
        }
        return $result;
    }

    // ==========================================
    //  EVOLUTION API (WhatsApp)
    // ==========================================

    /**
     * Enviar mensagem via Evolution API
     */
    public function enviarWhatsApp($numero, $mensagem) {
        $apiUrl = $this->getEvolutionConfig('evolution_api_url');
        $apiKey = $this->getEvolutionConfig('evolution_api_key');
        $instance = $this->getEvolutionConfig('evolution_instance', 'helpdesk');

        if (empty($apiUrl) || empty($apiKey)) {
            throw new \Exception('Evolution API não configurada');
        }

        $url = rtrim($apiUrl, '/') . '/message/sendText/' . rawurlencode($instance);
        
        $payload = json_encode([
            'number' => $this->normalizarNumero($numero),
            'text' => $mensagem,
        ]);

        return $this->httpPostRaw($url, $payload, [
            'Content-Type: application/json',
            'apikey: ' . $apiKey,
        ]);
    }

    /**
     * Envia uma resposta longa em múltiplas mensagens, para ficar mais fluido.
     * Quebra por parágrafos e tamanho máximo aproximado.
     */
    public function enviarWhatsAppQuebrado($numero, $mensagem) {
        $chunks = $this->quebrarMensagemWhatsApp($mensagem);
        foreach ($chunks as $parte) {
            if (trim($parte) === '') continue;
            try {
                $this->enviarWhatsApp($numero, $parte);
            } catch (\Exception $e) {
                // Se uma parte falhar, não interrompe as demais
            }
            // Pequeno intervalo para não parecer spam
            usleep(200000); // 200ms
        }
    }

    /**
     * Quebra texto em partes menores, priorizando parágrafos.
     */
    private function quebrarMensagemWhatsApp($mensagem, $maxLen = 900) {
        $mensagem = trim($mensagem);
        if (mb_strlen($mensagem) <= $maxLen) {
            return [$mensagem];
        }

        $partes = [];
        $paragrafos = preg_split("/\n{2,}/u", $mensagem);
        $buffer = '';

        foreach ($paragrafos as $p) {
            $p = trim($p);
            if ($p === '') continue;

            // Se o parágrafo sozinho já é muito grande, quebra “no bruto”
            if (mb_strlen($p) > $maxLen) {
                if ($buffer !== '') {
                    $partes[] = trim($buffer);
                    $buffer = '';
                }
                $offset = 0;
                $len = mb_strlen($p);
                while ($offset < $len) {
                    $partes[] = trim(mb_substr($p, $offset, $maxLen));
                    $offset += $maxLen;
                }
                continue;
            }

            if (mb_strlen($buffer) + mb_strlen("\n\n") + mb_strlen($p) <= $maxLen) {
                $buffer = $buffer === '' ? $p : $buffer . "\n\n" . $p;
            } else {
                if ($buffer !== '') {
                    $partes[] = trim($buffer);
                }
                $buffer = $p;
            }
        }

        if ($buffer !== '') {
            $partes[] = trim($buffer);
        }

        return $partes;
    }

    /**
     * Verificar status da conexão Evolution
     */
    public function checkEvolutionStatus() {
        $apiUrl = $this->getEvolutionConfig('evolution_api_url');
        $apiKey = $this->getEvolutionConfig('evolution_api_key');
        $instance = $this->getEvolutionConfig('evolution_instance', 'helpdesk');

        if (empty($apiUrl) || empty($apiKey)) {
            return ['connected' => false, 'error' => 'Evolution API não configurada'];
        }

        $url = rtrim($apiUrl, '/') . '/instance/connectionState/' . rawurlencode($instance);

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . $apiKey,
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);
            $state = $data['instance']['state'] ?? $data['state'] ?? 'unknown';

            return [
                'connected' => ($state === 'open'),
                'state' => $state,
                'instance' => $instance,
            ];
        } catch (\Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    //  N8N INTEGRATION
    // ==========================================

    /**
     * Envia evento para webhook N8N
     */
    public function notificarN8N($evento, $dados = []) {
        if ($this->getConfig('chatbot_n8n_ativo') !== '1') return null;

        $webhookUrl = $this->getConfig('chatbot_n8n_webhook_url');
        if (empty($webhookUrl)) return null;

        $payload = json_encode([
            'evento' => $evento,
            'timestamp' => date('c'),
            'dados' => $dados,
        ]);

        $headers = ['Content-Type: application/json'];
        $apiKey = $this->getConfig('chatbot_n8n_api_key');
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        try {
            $result = $this->httpPostRaw($webhookUrl, $payload, $headers);

            // Log
            $this->db->insert('chatbot_n8n_logs', [
                'direcao' => 'saida',
                'evento' => $evento,
                'payload' => $payload,
                'resposta' => json_encode($result),
                'http_code' => $result['http_code'] ?? 0,
                'sucesso' => ($result['http_code'] ?? 0) >= 200 && ($result['http_code'] ?? 0) < 300 ? 1 : 0,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->db->insert('chatbot_n8n_logs', [
                'direcao' => 'saida',
                'evento' => $evento,
                'payload' => $payload,
                'resposta' => json_encode(['error' => $e->getMessage()]),
                'http_code' => 0,
                'sucesso' => 0,
            ]);
            return null;
        }
    }

    /**
     * Processa webhook recebido do N8N
     */
    public function processarWebhookN8N($dados) {
        // Log de entrada
        $this->db->insert('chatbot_n8n_logs', [
            'direcao' => 'entrada',
            'evento' => $dados['evento'] ?? 'webhook',
            'payload' => json_encode($dados),
            'sucesso' => 1,
        ]);

        $evento = $dados['evento'] ?? '';
        $result = ['success' => true, 'evento' => $evento];

        switch ($evento) {
            case 'enviar_mensagem':
                // N8N pede para enviar mensagem via WhatsApp
                $numero = $dados['numero'] ?? '';
                $mensagem = $dados['mensagem'] ?? '';
                if ($numero && $mensagem) {
                    $this->enviarWhatsApp($numero, $mensagem);
                    $result['message'] = 'Mensagem enviada';
                }
                break;

            case 'consultar_db':
                // N8N pede para consultar banco
                $sql = $dados['sql'] ?? '';
                if ($sql && str_starts_with(strtoupper(trim($sql)), 'SELECT')) {
                    try {
                        $extDb = $this->getExternalDB();
                        $maxRows = (int) $this->getConfig('chatbot_db_max_rows', '50');
                        if (!preg_match('/\bLIMIT\b/i', $sql)) {
                            $sql .= " LIMIT {$maxRows}";
                        }
                        $stmt = $extDb->query($sql);
                        $result['data'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    } catch (\Exception $e) {
                        $result['success'] = false;
                        $result['error'] = $e->getMessage();
                    }
                }
                break;

            default:
                $result['message'] = 'Evento recebido';
        }

        return $result;
    }

    public function getN8NLogs($limite = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM chatbot_n8n_logs ORDER BY criado_em DESC LIMIT ?",
            [$limite]
        );
    }

    // ==========================================
    //  OLLAMA CONNECTION CHECK
    // ==========================================

    /**
     * Verificar conexão com Ollama e listar modelos
     */
    public function checkOllamaStatus() {
        $ollamaUrl = rtrim($this->getIAConfig('ollama_url', 'http://localhost:11434'), '/');

        try {
            $ch = curl_init($ollamaUrl . '/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return ['online' => false, 'error' => "HTTP {$httpCode}"];
            }

            $data = json_decode($response, true);
            $models = [];
            foreach ($data['models'] ?? [] as $m) {
                $models[] = $m['name'];
            }

            // Modelo ativo do chatbot
            $modeloAtivo = $this->getConfig('chatbot_ia_modelo');
            if (empty($modeloAtivo)) {
                $modeloAtivo = $this->getIAConfig('modelo_padrao', 'llama3');
            }

            return [
                'online' => true,
                'url' => $ollamaUrl,
                'models' => $models,
                'modelo_ativo' => $modeloAtivo,
                'modelo_disponivel' => in_array($modeloAtivo, $models),
            ];
        } catch (\Exception $e) {
            return ['online' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    //  ESTATÍSTICAS
    // ==========================================

    public function getStats() {
        return [
            'total_sessoes' => $this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_sessoes"),
            'sessoes_ativas' => $this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_sessoes WHERE ativo = 1 AND ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"),
            'total_mensagens' => $this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_mensagens"),
            'mensagens_hoje' => $this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_mensagens WHERE DATE(criado_em) = CURDATE()"),
            'numeros_autorizados' => $this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_numeros_autorizados WHERE ativo = 1"),
            'queries_executadas' => $this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_mensagens WHERE tipo = 'query'"),
            'erros_hoje' => $this->db->fetchColumn("SELECT COUNT(*) FROM chatbot_mensagens WHERE tipo = 'error' AND DATE(criado_em) = CURDATE()"),
        ];
    }

    /**
     * Retorna os logs do webhook para visualização no painel admin
     */
    public function getLogs($limite = 200, $filtro = 'all') {
        $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';
        if (!file_exists($logFile)) return [];

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_slice($lines, -$limite); // últimas N linhas
        $lines = array_reverse($lines); // mais recente primeiro

        $result = [];
        foreach ($lines as $line) {
            // Parse: [2026-03-23 12:51:59] MSG/PROCESSANDO/OK/ERRO...
            $entry = ['raw' => $line, 'level' => 'info', 'time' => '', 'message' => $line];

            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*(.*)$/', $line, $m)) {
                $entry['time'] = $m[1];
                $entry['message'] = $m[2];
            }

            // Classificar nível
            $msg = $entry['message'];
            if (str_contains($msg, 'ERRO') || str_contains($msg, 'ERROR') || str_contains($msg, 'SQL ERRO')) {
                $entry['level'] = 'error';
            } elseif (str_contains($msg, 'OK:') || str_contains($msg, 'IA OK') || str_contains($msg, 'FMT OK') || str_contains($msg, 'FMT PHP')) {
                $entry['level'] = 'success';
            } elseif (str_contains($msg, 'PROCESSANDO') || str_contains($msg, 'TOTAL')) {
                $entry['level'] = 'processing';
            } elseif (str_contains($msg, '[MSG]')) {
                $entry['level'] = 'webhook';
                // Truncar payload JSON muito longo
                if (mb_strlen($msg) > 200) {
                    // Extrair info útil: conversation/pushName
                    $resumo = '[MSG] ';
                    if (preg_match('/"pushName"\s*:\s*"([^"]+)"/', $msg, $pn)) {
                        $resumo .= $pn[1] . ': ';
                    }
                    if (preg_match('/"conversation"\s*:\s*"([^"]{1,100})"/', $msg, $cv)) {
                        $resumo .= '"' . $cv[1] . '"';
                    } else {
                        $resumo .= '(mídia/outro)';
                    }
                    $entry['message'] = $resumo;
                }
            } elseif (str_contains($msg, 'SEM RESPOSTA') || str_contains($msg, 'DUPLICADA')) {
                $entry['level'] = 'warning';
            }

            // Filtrar
            if ($filtro !== 'all' && $entry['level'] !== $filtro) continue;

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Limpa o arquivo de logs
     */
    public function clearLogs() {
        $logFile = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../..') . '/storage/logs/webhook_chatbot.log';
        if (file_exists($logFile)) {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "=== LOGS LIMPOS ===\n");
        }
    }

    /**
     * Retorna os erros recentes (mensagens tipo 'error') para o painel
     */
    public function getErrosRecentes($limite = 50) {
        return $this->db->fetchAll(
            "SELECT m.*, s.numero, s.nome_contato 
             FROM chatbot_mensagens m 
             LEFT JOIN chatbot_sessoes s ON s.id = m.sessao_id 
             WHERE m.tipo = 'error' 
             ORDER BY m.criado_em DESC 
             LIMIT ?",
            [$limite]
        );
    }

    // ==========================================
    //  TEST IA - Teste direto via painel admin
    // ==========================================

    /**
     * Envia uma mensagem de teste para a IA (sem WhatsApp)
     */
    public function testarIA($mensagem, $sessaoId = null) {
        $start = microtime(true);

        // Se não tem sessão, criar uma de teste
        if (!$sessaoId) {
            $session = $this->getOrCreateSession('0000000000', 'Admin Teste');
        } else {
            $session = $this->getSession($sessaoId);
        }

        if (!$session) {
            return ['error' => 'Sessão não encontrada'];
        }

        // Salvar pergunta
        $this->salvarMensagem($session['id'], 'user', $mensagem);

        // Montar contexto e chamar IA
        $messages = $this->montarContextoIA($session, $mensagem);

        $ollamaUrl = rtrim($this->getIAConfig('ollama_url', 'http://localhost:11434'), '/');
        $modelo = $this->getConfig('chatbot_ia_modelo');
        if (empty($modelo)) {
            $modelo = $this->getIAConfig('modelo_padrao', 'llama3');
        }

        $payload = [
            'model' => $modelo,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => (float) $this->getConfig('chatbot_ia_temperatura', '0.5'),
                'num_predict' => (int) $this->getConfig('chatbot_ia_max_tokens', '1024'),
            ],
        ];

        try {
            $response = $this->httpPost($ollamaUrl . '/api/chat', $payload);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        $duracao = round((microtime(true) - $start) * 1000);
        $content = $response['message']['content'] ?? '';
        $tokens = ($response['prompt_eval_count'] ?? 0) + ($response['eval_count'] ?? 0);

        // Processar SQL se houver
        $sqlResult = null;
        if ($this->getConfig('chatbot_db_ativo') === '1') {
            // Tentar multi-fonte primeiro
            $fontesAtivas = $this->listarFontesDados(true);
            if (!empty($fontesAtivas)) {
                $sqlResult = $this->processarConsultaMultiFonte($content, $session['id']);
            } else {
                $sqlResult = $this->processarSQLDaResposta($content, $session['id']);
            }

            // Se teve resultado, gerar resposta amigável
            if ($sqlResult && !empty($sqlResult['dados'])) {
                $content = $this->gerarRespostaAmigavel($messages, $mensagem, $sqlResult, $ollamaUrl, $modelo, $payload['options']);
                $sqlResult['resposta_final'] = $content;
            }
        }

        if ($sqlResult) {
            $content = $sqlResult['resposta_final'];
            $this->salvarMensagem($session['id'], 'bot', $content, 'query', [
                'sql' => $sqlResult['sql'],
                'dados' => $sqlResult['dados'],
                'tokens' => $tokens,
                'duracao_ms' => $duracao,
            ]);
        } else {
            $this->salvarMensagem($session['id'], 'bot', $content, 'text', [
                'tokens' => $tokens,
                'duracao_ms' => $duracao,
            ]);
        }

        return [
            'resposta' => $content,
            'modelo' => $modelo,
            'tokens' => $tokens,
            'duracao_ms' => $duracao,
            'sql' => $sqlResult['sql'] ?? null,
            'dados' => $sqlResult['dados'] ?? null,
            'sessao_id' => $session['id'],
        ];
    }

    // ==========================================
    //  HTTP HELPERS
    // ==========================================

    private function httpPost($url, $data) {
        $payload = json_encode($data);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) throw new \Exception("cURL error: {$error}");
        if ($httpCode !== 200) throw new \Exception("HTTP {$httpCode}: " . substr($response, 0, 500));

        return json_decode($response, true) ?: [];
    }

    private function httpPostRaw($url, $payload, $headers = []) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'body' => $response,
            'http_code' => $httpCode,
            'error' => $error,
            'data' => json_decode($response, true),
        ];
    }
}