<?php
/**
 * Model: IA (Inteligência Artificial)
 * Integração com Ollama - Multi-modelo (Llama 3, Phi-3, Gemma 2, Qwen 2.5, DeepSeek, Mistral, TinyLlama)
 */

class IA {
    private $db;
    private $config = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfig();
    }

    // ==========================================
    //  CONFIGURAÇÃO
    // ==========================================

    private function loadConfig() {
        $rows = $this->db->fetchAll("SELECT chave, valor FROM ia_config");
        foreach ($rows as $row) {
            $this->config[$row['chave']] = $row['valor'];
        }
    }

    public function getConfig($key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function setConfig($key, $value) {
        $this->db->query(
            "INSERT INTO ia_config (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?",
            [$key, $value, $value]
        );
        $this->config[$key] = $value;
    }

    public function getAllConfig() {
        return $this->db->fetchAll("SELECT * FROM ia_config ORDER BY chave");
    }

    public function isHabilitado() {
        return (int)$this->getConfig('habilitado', '1') === 1;
    }

    // ==========================================
    //  OLLAMA API
    // ==========================================

    /**
     * Enviar mensagem para Ollama e obter resposta
     */
    public function chat($messages, $modelo = null, $options = []) {
        $url = rtrim($this->getConfig('ollama_url', 'http://localhost:11434'), '/') . '/api/chat';
        $modelo = $modelo ?: $this->getConfig('modelo_padrao', 'llama3');

        // Limitar geração para evitar resposta infinita (512 tokens ~= 60s em CPU)
        $numPredict = isset($options['max_tokens']) ? (int)$options['max_tokens'] : 512;
        $numCtx = (int)($options['num_ctx'] ?? $this->getConfig('num_ctx', '2048'));

        $ollamaOpts = [
            'temperature' => (float)($options['temperature'] ?? $this->getConfig('temperatura', '0.7')),
            'num_predict' => $numPredict,
            'num_ctx' => $numCtx,
        ];
        if (isset($options['repeat_penalty'])) {
            $ollamaOpts['repeat_penalty'] = (float)$options['repeat_penalty'];
        }
        if (isset($options['stop'])) {
            $ollamaOpts['stop'] = $options['stop'];
        }

        $payload = [
            'model' => $modelo,
            'messages' => $messages,
            'stream' => false,
            'keep_alive' => '30m',
            'options' => $ollamaOpts,
        ];

        $start = microtime(true);
        $response = $this->httpPost($url, $payload);
        $duracao = round((microtime(true) - $start) * 1000);

        if (!$response || !isset($response['message'])) {
            throw new \Exception('Falha na comunicação com a IA. Verifique se o Ollama está rodando.');
        }

        return [
            'content' => $response['message']['content'] ?? '',
            'model' => $response['model'] ?? $modelo,
            'tokens' => ($response['prompt_eval_count'] ?? 0) + ($response['eval_count'] ?? 0),
            'duracao_ms' => $duracao,
        ];
    }

    /**
     * Enviar mensagem para Ollama com streaming (SSE)
     */
    public function chatStream($messages, $callback, $modelo = null, $options = []) {
        $url = rtrim($this->getConfig('ollama_url', 'http://localhost:11434'), '/') . '/api/chat';
        $modelo = $modelo ?: $this->getConfig('modelo_padrao', 'llama3');

        // Limitar geração para evitar resposta infinita (512 tokens ~= 60s em CPU)
        $numPredict = isset($options['max_tokens']) ? (int)$options['max_tokens'] : 512;
        $numCtx = (int)($options['num_ctx'] ?? $this->getConfig('num_ctx', '2048'));

        $ollamaStreamOpts = [
            'temperature' => (float)($options['temperature'] ?? $this->getConfig('temperatura', '0.7')),
            'num_predict' => $numPredict,
            'num_ctx' => $numCtx,
        ];
        if (isset($options['repeat_penalty'])) {
            $ollamaStreamOpts['repeat_penalty'] = (float)$options['repeat_penalty'];
        }
        if (isset($options['stop'])) {
            $ollamaStreamOpts['stop'] = $options['stop'];
        }

        $payload = json_encode([
            'model' => $modelo,
            'messages' => $messages,
            'stream' => true,
            'keep_alive' => '30m',
            'options' => $ollamaStreamOpts,
        ]);

        // Verificar se o modelo está carregado (para enviar heartbeat enquanto carrega)
        $receivedData = false;
        $heartbeatCallback = $callback; // referência ao callback original

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_LOW_SPEED_LIMIT => 0,
            CURLOPT_LOW_SPEED_TIME => 0,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_PROGRESSFUNCTION => function($ch, $dlTotal, $dlNow, $ulTotal, $ulNow) use (&$receivedData, $heartbeatCallback) {
                // Enquanto não recebeu dados, enviar heartbeat para manter conexão viva
                if (!$receivedData && $dlNow == 0) {
                    $heartbeatCallback(['heartbeat' => true]);
                }
                return 0; // 0 = continuar, 1 = abortar
            },
            CURLOPT_NOPROGRESS => false,
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($callback, &$receivedData) {
                $receivedData = true;
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    $json = json_decode($line, true);
                    if ($json) {
                        $callback($json);
                    }
                }
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erro na conexão com Ollama: {$error}");
        }
        if ($httpCode !== 200 && $httpCode !== 0) {
            throw new \Exception("Ollama retornou HTTP {$httpCode}");
        }
    }

    /**
     * Catálogo de modelos com metadados (nome amigável, descrição, tier, etc.)
     */
    private static $modelCatalog = [
        'llama3' => [
            'label' => 'Llama 3 8B', 'familia' => 'Meta Llama', 'parametros' => '8B',
            'tier' => 'medio', 'cor' => '#3B82F6', 'icone' => 'fa-meta',
            'descricao' => 'Modelo versátil da Meta, bom equilíbrio qualidade/velocidade',
            'recomendado_para' => ['chat', 'chamados', 'geral'],
            'ram_estimada' => '~5 GB',
        ],
        'llama3.1' => [
            'label' => 'Llama 3.1 8B', 'familia' => 'Meta Llama', 'parametros' => '8B',
            'tier' => 'medio', 'cor' => '#3B82F6', 'icone' => 'fa-meta',
            'descricao' => 'Versão atualizada do Llama 3 com melhor raciocínio',
            'recomendado_para' => ['chat', 'chamados', 'geral', 'analise'],
            'ram_estimada' => '~5 GB',
        ],
        'llama3.3' => [
            'label' => 'Llama 3.3 70B', 'familia' => 'Meta Llama', 'parametros' => '70B',
            'tier' => 'avancado', 'cor' => '#1D4ED8', 'icone' => 'fa-meta',
            'descricao' => 'Modelo grande da Meta, qualidade premium para tarefas complexas',
            'recomendado_para' => ['analise', 'diagnostico', 'relatorios', 'problemas-complexos'],
            'ram_estimada' => '~40 GB',
        ],
        'llama3.3:70b' => [
            'label' => 'Llama 3.3 70B', 'familia' => 'Meta Llama', 'parametros' => '70B',
            'tier' => 'avancado', 'cor' => '#1D4ED8', 'icone' => 'fa-meta',
            'descricao' => 'Modelo grande da Meta, qualidade premium para tarefas complexas',
            'recomendado_para' => ['analise', 'diagnostico', 'relatorios', 'problemas-complexos'],
            'ram_estimada' => '~40 GB',
        ],
        'phi3' => [
            'label' => 'Phi-3 Mini', 'familia' => 'Microsoft Phi', 'parametros' => '3.8B',
            'tier' => 'leve', 'cor' => '#00BCF2', 'icone' => 'fa-microsoft',
            'descricao' => 'Modelo compacto da Microsoft, excelente custo-benefício',
            'recomendado_para' => ['chat', 'classificacao', 'resumos'],
            'ram_estimada' => '~3 GB',
        ],
        'phi3:mini' => [
            'label' => 'Phi-3 Mini', 'familia' => 'Microsoft Phi', 'parametros' => '3.8B',
            'tier' => 'leve', 'cor' => '#00BCF2', 'icone' => 'fa-microsoft',
            'descricao' => 'Modelo compacto da Microsoft, excelente custo-benefício',
            'recomendado_para' => ['chat', 'classificacao', 'resumos'],
            'ram_estimada' => '~3 GB',
        ],
        'gemma2' => [
            'label' => 'Gemma 2', 'familia' => 'Google Gemma', 'parametros' => '9B',
            'tier' => 'medio', 'cor' => '#EA4335', 'icone' => 'fa-google',
            'descricao' => 'Modelo do Google, top em qualidade de texto',
            'recomendado_para' => ['chat', 'analise', 'relatorios'],
            'ram_estimada' => '~6 GB',
        ],
        'gemma2:2b' => [
            'label' => 'Gemma 2 2B', 'familia' => 'Google Gemma', 'parametros' => '2B',
            'tier' => 'ultra-leve', 'cor' => '#EA4335', 'icone' => 'fa-google',
            'descricao' => 'Ultra-leve do Google, respostas instantâneas',
            'recomendado_para' => ['classificacao', 'tags', 'respostas-curtas'],
            'ram_estimada' => '~2 GB',
        ],
        'gemma2:9b' => [
            'label' => 'Gemma 2 9B', 'familia' => 'Google Gemma', 'parametros' => '9B',
            'tier' => 'medio', 'cor' => '#EA4335', 'icone' => 'fa-google',
            'descricao' => 'Versão completa do Gemma 2, alta qualidade',
            'recomendado_para' => ['chat', 'analise', 'relatorios'],
            'ram_estimada' => '~6 GB',
        ],
        'qwen2.5' => [
            'label' => 'Qwen 2.5', 'familia' => 'Alibaba Qwen', 'parametros' => '7B',
            'tier' => 'medio', 'cor' => '#FF6A00', 'icone' => 'fa-bolt',
            'descricao' => 'Excelente em código, raciocínio e análise técnica',
            'recomendado_para' => ['codigo', 'ssh', 'analise-tecnica'],
            'ram_estimada' => '~5 GB',
        ],
        'qwen2.5:3b' => [
            'label' => 'Qwen 2.5 3B', 'familia' => 'Alibaba Qwen', 'parametros' => '3B',
            'tier' => 'leve', 'cor' => '#FF6A00', 'icone' => 'fa-bolt',
            'descricao' => 'Versão leve do Qwen, ótimo para análise de código',
            'recomendado_para' => ['codigo', 'ssh', 'classificacao'],
            'ram_estimada' => '~3 GB',
        ],
        'qwen2.5:7b' => [
            'label' => 'Qwen 2.5 7B', 'familia' => 'Alibaba Qwen', 'parametros' => '7B',
            'tier' => 'medio', 'cor' => '#FF6A00', 'icone' => 'fa-bolt',
            'descricao' => 'Versão completa, excelente em tarefas técnicas',
            'recomendado_para' => ['codigo', 'ssh', 'analise-tecnica', 'chat'],
            'ram_estimada' => '~5 GB',
        ],
        'mistral' => [
            'label' => 'Mistral 7B', 'familia' => 'Mistral AI', 'parametros' => '7B',
            'tier' => 'medio', 'cor' => '#FF7000', 'icone' => 'fa-wind',
            'descricao' => 'O clássico, muito equilibrado e confiável',
            'recomendado_para' => ['chat', 'geral', 'chamados'],
            'ram_estimada' => '~5 GB',
        ],
        'tinyllama' => [
            'label' => 'TinyLlama 1.1B', 'familia' => 'TinyLlama', 'parametros' => '1.1B',
            'tier' => 'ultra-leve', 'cor' => '#22C55E', 'icone' => 'fa-feather',
            'descricao' => 'O mais leve de todos, respostas instantâneas',
            'recomendado_para' => ['classificacao', 'tags', 'respostas-curtas'],
            'ram_estimada' => '~1 GB',
        ],
        'deepseek-r1' => [
            'label' => 'DeepSeek R1', 'familia' => 'DeepSeek', 'parametros' => '7B',
            'tier' => 'avancado', 'cor' => '#8B5CF6', 'icone' => 'fa-brain',
            'descricao' => 'Raciocínio avançado chain-of-thought, ideal para problemas complexos',
            'recomendado_para' => ['analise', 'diagnostico', 'relatorios', 'problemas-complexos'],
            'ram_estimada' => '~5 GB',
        ],
        'deepseek-r1:8b' => [
            'label' => 'DeepSeek R1 8B', 'familia' => 'DeepSeek', 'parametros' => '8B',
            'tier' => 'avancado', 'cor' => '#8B5CF6', 'icone' => 'fa-brain',
            'descricao' => 'Raciocínio avançado chain-of-thought, versão 8B',
            'recomendado_para' => ['analise', 'diagnostico', 'relatorios', 'problemas-complexos'],
            'ram_estimada' => '~5 GB',
        ],
    ];

    /**
     * Obter metadados de um modelo pelo nome
     */
    public function getModelMeta(string $name): array {
        // Tentar match exato
        if (isset(self::$modelCatalog[$name])) {
            return self::$modelCatalog[$name];
        }
        // Tentar match parcial (ex: 'phi3:3.8b-mini...' → 'phi3')
        $baseName = explode(':', $name)[0];
        if (isset(self::$modelCatalog[$baseName])) {
            return self::$modelCatalog[$baseName];
        }
        // Tentar com versão simplificada
        foreach (self::$modelCatalog as $key => $meta) {
            if (str_starts_with($name, $key)) return $meta;
        }
        // Modelo desconhecido
        return [
            'label' => ucfirst($baseName), 'familia' => 'Outro', 'parametros' => '?',
            'tier' => 'desconhecido', 'cor' => '#6B7280', 'icone' => 'fa-cube',
            'descricao' => 'Modelo personalizado', 'recomendado_para' => ['geral'],
            'ram_estimada' => 'N/A',
        ];
    }

    /**
     * Verificar se o Ollama está acessível
     */
    public function checkConnection() {
        $url = rtrim($this->getConfig('ollama_url', 'http://localhost:11434'), '/') . '/api/tags';

        $ctx = stream_context_create([
            'http' => ['timeout' => 5, 'method' => 'GET'],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return ['online' => false, 'models' => [], 'error' => 'Não foi possível conectar ao Ollama'];
        }

        $data = json_decode($response, true);
        $models = [];
        if (isset($data['models'])) {
            foreach ($data['models'] as $m) {
                $meta = $this->getModelMeta($m['name']);
                $models[] = array_merge($meta, [
                    'name' => $m['name'],
                    'size' => round(($m['size'] ?? 0) / (1024 * 1024 * 1024), 1) . ' GB',
                    'size_bytes' => $m['size'] ?? 0,
                    'modified' => $m['modified_at'] ?? '',
                    'digest' => substr($m['digest'] ?? '', 0, 12),
                    'quantization' => $m['details']['quantization_level'] ?? 'N/A',
                    'format' => $m['details']['format'] ?? 'N/A',
                    'family_detail' => $m['details']['family'] ?? '',
                    'parameter_size' => $m['details']['parameter_size'] ?? '',
                ]);
            }
        }

        return ['online' => true, 'models' => $models, 'url' => $this->getConfig('ollama_url')];
    }

    /**
     * Listar modelos disponíveis no Ollama (com metadados)
     */
    public function listarModelos() {
        $check = $this->checkConnection();
        return $check['models'] ?? [];
    }

    /**
     * Informações detalhadas de um modelo específico
     */
    public function getModelInfo(string $name): array {
        $url = rtrim($this->getConfig('ollama_url', 'http://localhost:11434'), '/') . '/api/show';
        try {
            $response = $this->httpPost($url, ['name' => $name]);
            $meta = $this->getModelMeta($name);
            return array_merge($meta, [
                'name' => $name,
                'modelfile' => $response['modelfile'] ?? '',
                'parameters' => $response['parameters'] ?? '',
                'template' => $response['template'] ?? '',
                'details' => $response['details'] ?? [],
                'model_info' => $response['model_info'] ?? [],
            ]);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Baixar/Pull um modelo do Ollama
     */
    public function pullModel(string $name): array {
        $url = rtrim($this->getConfig('ollama_url', 'http://localhost:11434'), '/') . '/api/pull';

        // Pull é demorado, usar timeout longo
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['name' => $name, 'stream' => false]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0, // sem timeout
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['success' => false, 'error' => $error];
        if ($httpCode !== 200) return ['success' => false, 'error' => "HTTP $httpCode"];

        return ['success' => true, 'status' => 'downloaded'];
    }

    /**
     * Deletar um modelo do Ollama
     */
    public function deleteModel(string $name): array {
        $url = rtrim($this->getConfig('ollama_url', 'http://localhost:11434'), '/') . '/api/delete';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => json_encode(['name' => $name]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['success' => false, 'error' => $error];
        if ($httpCode !== 200) return ['success' => false, 'error' => "HTTP $httpCode"];

        return ['success' => true];
    }

    /**
     * Obter modelo configurado para cada tarefa
     */
    public function getModelosPorTarefa(): array {
        return [
            'chat' => [
                'modelo' => $this->getConfig('modelo_padrao', 'llama3'),
                'label' => 'Chat / Conversa',
                'descricao' => 'Modelo principal para conversas e assistência geral',
                'icone' => 'fa-comments',
            ],
            'rapido' => [
                'modelo' => $this->getConfig('modelo_rapido', 'llama3'),
                'label' => 'Tarefas Rápidas',
                'descricao' => 'Classificação de chamados, respostas curtas, tags',
                'icone' => 'fa-bolt',
            ],
            'codigo' => [
                'modelo' => $this->getConfig('modelo_codigo', $this->getConfig('modelo_padrao', 'llama3')),
                'label' => 'Código / SSH',
                'descricao' => 'Análise de código, comandos SSH, troubleshooting',
                'icone' => 'fa-terminal',
            ],
            'analise' => [
                'modelo' => $this->getConfig('modelo_analise', $this->getConfig('modelo_padrao', 'llama3')),
                'label' => 'Análise / Relatórios',
                'descricao' => 'Relatórios semanais, diagnósticos complexos',
                'icone' => 'fa-chart-bar',
            ],
        ];
    }

    /**
     * Obter catálogo estático de modelos (para UI)
     */
    public static function getCatalog(): array {
        return self::$modelCatalog;
    }

    // ==========================================
    //  CONVERSAS
    // ==========================================

    public function criarConversa($userId, $titulo = 'Nova Conversa', $contexto = 'geral', $contextoId = null, $modelo = null) {
        return $this->db->insert('ia_conversas', [
            'usuario_id' => $userId,
            'titulo' => $titulo,
            'contexto' => $contexto,
            'contexto_id' => $contextoId,
            'modelo' => $modelo ?: $this->getConfig('modelo_padrao', 'llama3'),
        ]);
    }

    public function getConversa($id) {
        return $this->db->fetch("SELECT * FROM ia_conversas WHERE id = ?", [$id]);
    }

    public function listarConversas($userId, $contexto = null, $limite = 50) {
        $where = "usuario_id = ?";
        $params = [$userId];

        if ($contexto) {
            $where .= " AND contexto = ?";
            $params[] = $contexto;
        }

        return $this->db->fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM ia_mensagens WHERE conversa_id = c.id) as total_mensagens,
                    (SELECT conteudo FROM ia_mensagens WHERE conversa_id = c.id AND role = 'user' ORDER BY id ASC LIMIT 1) as primeira_mensagem
             FROM ia_conversas c 
             WHERE {$where} AND c.ativa = 1
             ORDER BY c.atualizado_em DESC 
             LIMIT " . (int)$limite,
            $params
        );
    }

    public function renomearConversa($id, $titulo) {
        $this->db->update('ia_conversas', ['titulo' => $titulo], 'id = ?', [$id]);
    }

    public function excluirConversa($id) {
        $this->db->update('ia_conversas', ['ativa' => 0], 'id = ?', [$id]);
    }

    public function getMensagens($conversaId) {
        return $this->db->fetchAll(
            "SELECT * FROM ia_mensagens WHERE conversa_id = ? ORDER BY id ASC",
            [$conversaId]
        );
    }

    public function salvarMensagem($conversaId, $role, $conteudo, $tokens = 0, $duracaoMs = 0, $modelo = null) {
        $id = $this->db->insert('ia_mensagens', [
            'conversa_id' => $conversaId,
            'role' => $role,
            'conteudo' => $conteudo,
            'tokens' => $tokens,
            'duracao_ms' => $duracaoMs,
            'modelo' => $modelo,
        ]);

        // Atualizar tokens totais da conversa
        $this->db->query(
            "UPDATE ia_conversas SET tokens_total = tokens_total + ? WHERE id = ?",
            [$tokens, $conversaId]
        );

        return $id;
    }

    // ==========================================
    //  ENVIAR MENSAGEM (COMPLETO)
    // ==========================================

    /**
     * Enviar mensagem numa conversa e obter resposta da IA
     */
    public function enviarMensagem($conversaId, $userId, $mensagem, $contextoExtra = '') {
        $conversa = $this->getConversa($conversaId);
        if (!$conversa) throw new \Exception('Conversa não encontrada');
        if ($conversa['usuario_id'] != $userId) throw new \Exception('Sem permissão');

        // Salvar mensagem do usuário
        $this->salvarMensagem($conversaId, 'user', $mensagem);

        // Montar contexto de mensagens
        $messages = $this->montarContexto($conversaId, $conversa['contexto'], $contextoExtra);

        // Chamar IA
        $resposta = $this->chat($messages, $conversa['modelo']);

        // Processar ações da IA (criar projetos, tarefas, etc.)
        require_once __DIR__ . '/IAActions.php';
        $actions = new IAActions($userId);
        $processed = $actions->processResponse($resposta['content']);

        // Usar texto processado (com resultados das ações) se houve ações
        $finalContent = $processed['text'];

        // Salvar resposta
        $this->salvarMensagem(
            $conversaId,
            'assistant',
            $finalContent,
            $resposta['tokens'],
            $resposta['duracao_ms'],
            $resposta['model']
        );

        // Auto-título se for primeira mensagem
        $totalMsgs = $this->db->fetch(
            "SELECT COUNT(*) as total FROM ia_mensagens WHERE conversa_id = ? AND role = 'user'",
            [$conversaId]
        );
        if (($totalMsgs['total'] ?? 0) <= 1) {
            $this->autoTitulo($conversaId, $mensagem);
        }

        return [
            'content' => $finalContent,
            'tokens' => $resposta['tokens'],
            'duracao_ms' => $resposta['duracao_ms'],
            'model' => $resposta['model'],
            'actions' => $processed['actions'],
            'hasActions' => $processed['hasActions'],
        ];
    }

    /**
     * Montar array de mensagens com system prompt e histórico
     */
    private function montarContexto($conversaId, $contexto, $extra = '', $includeTools = true) {
        // System prompt baseado no contexto
        $systemPrompts = [
            'geral' => $this->getConfig('system_prompt'),
            'ssh' => $this->getConfig('system_prompt_ssh'),
            'chamado' => $this->getConfig('system_prompt_chamados'),
        ];

        $systemPrompt = $systemPrompts[$contexto] ?? $systemPrompts['geral'];

        // Injetar ferramentas/ações disponíveis no prompt
        if ($includeTools) {
            require_once __DIR__ . '/IAActions.php';
            $systemPrompt .= "\n\n" . IAActions::getToolsPrompt();
        }

        if ($extra) {
            $systemPrompt .= "\n\nContexto adicional:\n" . $extra;
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Histórico da conversa (limitar para não estourar contexto)
        // Pegar apenas últimas 20 mensagens para não carregar tudo do banco
        $historico = $this->db->fetchAll(
            "SELECT * FROM ia_mensagens WHERE conversa_id = ? ORDER BY id DESC LIMIT 20",
            [$conversaId]
        );
        $historico = array_reverse($historico);
        $maxTokens = (int)$this->getConfig('contexto_max', '4096');
        $tokenCount = 0;
        $relevantMsgs = [];

        // Percorrer do mais recente para trás
        foreach (array_reverse($historico) as $msg) {
            $msgTokens = max($msg['tokens'], (int)(strlen($msg['conteudo']) / 4));
            if ($tokenCount + $msgTokens > $maxTokens * 0.7) break;
            $tokenCount += $msgTokens;
            array_unshift($relevantMsgs, $msg);
        }

        foreach ($relevantMsgs as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['conteudo'],
            ];
        }

        return $messages;
    }

    /**
     * Gerar título automático para a conversa
     */
    private function autoTitulo($conversaId, $mensagem) {
        // Gerar título localmente (sem chamar LLM novamente, economiza ~10-15s)
        $titulo = mb_substr(trim($mensagem), 0, 60);
        // Cortar na última palavra completa
        if (mb_strlen($mensagem) > 60) {
            $lastSpace = mb_strrpos($titulo, ' ');
            if ($lastSpace > 20) $titulo = mb_substr($titulo, 0, $lastSpace);
            $titulo .= '...';
        }
        // Remover quebras de linha
        $titulo = preg_replace('/[\r\n]+/', ' ', $titulo);
        if (mb_strlen($titulo) > 3) {
            $this->renomearConversa($conversaId, $titulo);
        }
    }

    // ==========================================
    //  ASSISTENTE SSH
    // ==========================================

    /**
     * Explicar um comando SSH
     */
    public function explicarComando($comando) {
        $modelo = $this->getConfig('modelo_codigo', $this->getConfig('modelo_rapido', null));
        $resp = $this->chat([
            ['role' => 'system', 'content' => 'Especialista Linux. Explique o comando de forma breve em PT-BR: o que faz, parâmetros, riscos. Máx 5 linhas.'],
            ['role' => 'user', 'content' => "Explique: {$comando}"],
        ], $modelo, ['max_tokens' => 300, 'temperature' => 0.3, 'num_ctx' => 2048]);

        return $resp['content'];
    }

    /**
     * Interpretar saída/erro de um comando SSH
     */
    public function interpretarSaida($comando, $saida, $exitCode = 0) {
        $tipo = $exitCode !== 0 ? 'ERRO' : 'saída';
        $saidaLimitada = mb_substr($saida, 0, 2000);
        if (mb_strlen($saida) > 2000) $saidaLimitada .= "\n... (saída truncada)";
        $modelo = $this->getConfig('modelo_codigo', $this->getConfig('modelo_rapido', null));
        $resp = $this->chat([
            ['role' => 'system', 'content' => 'Especialista Linux. Interprete a saída do comando, identifique problemas, sugira soluções. PT-BR, conciso.'],
            ['role' => 'user', 'content' => "Comando: {$comando}\n{$tipo} (exit: {$exitCode}):\n{$saidaLimitada}\n\nInterprete e sugira ações."],
        ], $modelo, ['max_tokens' => 512, 'temperature' => 0.3, 'num_ctx' => 2048]);

        return $resp['content'];
    }

    /**
     * Sugerir comando baseado na descrição
     */
    public function sugerirComando($descricao) {
        $modelo = $this->getConfig('modelo_codigo', $this->getConfig('modelo_rapido', null));
        $resp = $this->chat([
            ['role' => 'system', 'content' => 'Sugira o comando Linux exato. Responda APENAS: o comando em bloco de código + 1 linha de explicação.'],
            ['role' => 'user', 'content' => $descricao],
        ], $modelo, ['max_tokens' => 150, 'temperature' => 0.3, 'num_ctx' => 2048]);

        return $resp['content'];
    }

    // ==========================================
    //  ASSISTENTE DE CHAMADOS
    // ==========================================

    /**
     * Classificar um chamado automaticamente
     */
    public function classificarChamado($titulo, $descricao) {
        $categorias = ['hardware', 'software', 'rede', 'email', 'impressora', 'acesso', 'telefonia', 'outro'];
        $prioridades = ['baixa', 'media', 'alta', 'critica'];

        $resp = $this->chat([
            ['role' => 'system', 'content' => "Você é um classificador de chamados de TI. Analise o chamado e retorne APENAS um JSON com: {\"categoria\": \"...\", \"prioridade\": \"...\", \"resumo\": \"...\"}.\nCategorias: " . implode(', ', $categorias) . "\nPrioridades: " . implode(', ', $prioridades) . "\nO resumo deve ter no máximo 1 frase."],
            ['role' => 'user', 'content' => "Título: {$titulo}\nDescrição: {$descricao}"],
        ], null, ['max_tokens' => 200, 'temperature' => 0.1]);

        $json = json_decode($resp['content'], true);
        if (!$json) {
            // Tentar extrair JSON de dentro do texto
            preg_match('/\{[^}]+\}/', $resp['content'], $matches);
            $json = json_decode($matches[0] ?? '{}', true);
        }

        return $json ?: ['categoria' => 'outro', 'prioridade' => 'media', 'resumo' => ''];
    }

    /**
     * Sugerir resposta para um chamado
     */
    public function sugerirResposta($chamado, $interacoes = []) {
        $contexto = "Chamado #{$chamado['codigo']}\nTítulo: {$chamado['titulo']}\nDescrição: {$chamado['descricao']}\nCategoria: {$chamado['categoria_nome']}\nPrioridade: {$chamado['prioridade']}\n";

        if (!empty($interacoes)) {
            $contexto .= "\nHistórico de interações:\n";
            foreach (array_slice($interacoes, -5) as $i) {
                $autor = $i['usuario_nome'] ?? $i['autor_nome'] ?? 'Usuário';
                $contexto .= "- {$autor}: {$i['conteudo']}\n";
            }
        }

        $resp = $this->chat([
            ['role' => 'system', 'content' => 'Você é um técnico de suporte de TI. Sugira uma resposta profissional e empática para este chamado. A resposta deve ser direta, oferecer uma solução ou próximo passo claro. Responda em português.'],
            ['role' => 'user', 'content' => $contexto],
        ], null, ['max_tokens' => 512, 'temperature' => 0.5]);

        return $resp['content'];
    }

    // ==========================================
    //  ANÁLISES E RELATÓRIOS
    // ==========================================

    /**
     * Gerar resumo semanal do dashboard
     */
    public function gerarResumoSemanal($dados) {
        $modelo = $this->getConfig('modelo_analise', $this->getConfig('modelo_padrao', 'llama3'));
        $resp = $this->chat([
            ['role' => 'system', 'content' => 'Você é um analista de TI. Gere um relatório semanal executivo e conciso baseado nos dados fornecidos. Inclua: principais destaques, problemas recorrentes, sugestões de melhoria. Use markdown com tópicos. Responda em português.'],
            ['role' => 'user', 'content' => "Dados da semana:\n" . json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        ], $modelo, ['max_tokens' => 1024, 'temperature' => 0.5]);

        return $resp['content'];
    }

    // ==========================================
    //  ESTATÍSTICAS
    // ==========================================

    public function getEstatisticas($userId = null) {
        $where = $userId ? "WHERE c.usuario_id = ?" : "";
        $params = $userId ? [$userId] : [];

        $stats = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT c.id) as total_conversas,
                (SELECT COUNT(*) FROM ia_mensagens m JOIN ia_conversas c2 ON m.conversa_id = c2.id WHERE m.role = 'user' " . ($userId ? "AND c2.usuario_id = ?" : "") . ") as total_perguntas,
                (SELECT COALESCE(SUM(tokens_total), 0) FROM ia_conversas " . ($userId ? "WHERE usuario_id = ?" : "") . ") as total_tokens,
                (SELECT AVG(duracao_ms) FROM ia_mensagens WHERE role = 'assistant' AND duracao_ms > 0) as tempo_medio_ms
             FROM ia_conversas c {$where}",
            $userId ? [$userId, $userId, $userId] : []
        );

        return $stats;
    }

    // ==========================================
    //  PERGUNTAS RÁPIDAS (sem conversa)
    // ==========================================

    /**
     * Pergunta rápida sem salvar em conversa
     */
    public function perguntaRapida($pergunta, $contexto = 'geral') {
        $systemPrompts = [
            'geral' => $this->getConfig('system_prompt'),
            'ssh' => $this->getConfig('system_prompt_ssh'),
            'chamado' => $this->getConfig('system_prompt_chamados'),
        ];

        // Usar resposta curta e modelo rápido para perguntas rápidas
        $resp = $this->chat([
            ['role' => 'system', 'content' => ($systemPrompts[$contexto] ?? $systemPrompts['geral']) . "\n\nIMPORTANTE: Responda de forma CONCISA e DIRETA, máximo 3-4 parágrafos."],
            ['role' => 'user', 'content' => $pergunta],
        ], $this->getConfig('modelo_rapido', null), [
            'max_tokens' => 512,
            'num_ctx' => 2048,
        ]);

        return $resp;
    }

    // ==========================================
    //  HTTP HELPER
    // ==========================================

    private function httpPost($url, $payload) {
        $json = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($json)],
            CURLOPT_TIMEOUT => 600,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erro de conexão com Ollama: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \Exception("Ollama retornou HTTP {$httpCode}: " . substr($response, 0, 200));
        }

        return json_decode($response, true);
    }
}
