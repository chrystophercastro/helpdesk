<?php
/**
 * IAActions - Motor de execução de ações da IA no sistema
 * 
 * A IA pode chamar funções do sistema usando o formato:
 * [ACTION:nome_funcao]{"param1":"valor1","param2":"valor2"}[/ACTION]
 * 
 * Cada ação é mapeada para métodos reais dos modelos do sistema.
 */

class IAActions {
    private $db;
    private $userId;
    private $userInfo;

    public function __construct($userId) {
        $this->db = Database::getInstance();
        $this->userId = $userId;
        $this->userInfo = $this->db->fetch("SELECT * FROM usuarios WHERE id = ?", [$userId]);
    }

    /**
     * Definição de todas as ações disponíveis para a IA
     * Usado tanto para o system prompt quanto para validação
     */
    public static function getToolDefinitions() {
        return [
            // ===== PROJETOS =====
            'criar_projeto' => [
                'descricao' => 'Criar um novo projeto',
                'params' => [
                    'nome' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Nome do projeto'],
                    'descricao' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Descrição detalhada'],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'baixa|media|alta|critica', 'padrao' => 'media'],
                    'data_inicio' => ['tipo' => 'date', 'obrigatorio' => false, 'desc' => 'Data início YYYY-MM-DD', 'padrao' => 'hoje'],
                    'prazo' => ['tipo' => 'date', 'obrigatorio' => false, 'desc' => 'Prazo final YYYY-MM-DD'],
                ],
                'exemplo' => '[ACTION:criar_projeto]{"nome":"Migração de Servidores","descricao":"Migrar servidores para nova infra","prioridade":"alta","prazo":"2026-06-30"}[/ACTION]',
                'retorno' => 'ID do projeto criado',
            ],
            'listar_projetos' => [
                'descricao' => 'Listar projetos existentes',
                'params' => [
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Filtrar: planejamento|em_desenvolvimento|em_testes|concluido|cancelado'],
                    'busca' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Buscar por nome'],
                ],
                'retorno' => 'Lista de projetos',
            ],
            'atualizar_projeto' => [
                'descricao' => 'Atualizar dados de um projeto',
                'params' => [
                    'id' => ['tipo' => 'int', 'obrigatorio' => true, 'desc' => 'ID do projeto'],
                    'nome' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Novo nome'],
                    'descricao' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Nova descrição'],
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Novo status'],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Nova prioridade'],
                ],
                'retorno' => 'Confirmação',
            ],

            // ===== TAREFAS =====
            'criar_tarefa' => [
                'descricao' => 'Criar uma tarefa em um projeto',
                'params' => [
                    'titulo' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Título da tarefa'],
                    'descricao' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Descrição da tarefa'],
                    'projeto_id' => ['tipo' => 'int', 'obrigatorio' => true, 'desc' => 'ID do projeto'],
                    'sprint_id' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'ID do sprint'],
                    'responsavel_id' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'ID do responsável'],
                    'coluna' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'backlog|a_fazer|em_andamento|em_revisao|concluido', 'padrao' => 'backlog'],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'baixa|media|alta|critica', 'padrao' => 'media'],
                    'prazo' => ['tipo' => 'date', 'obrigatorio' => false, 'desc' => 'Prazo YYYY-MM-DD'],
                    'pontos' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'Story points'],
                    'horas_estimadas' => ['tipo' => 'float', 'obrigatorio' => false, 'desc' => 'Horas estimadas'],
                ],
                'exemplo' => '[ACTION:criar_tarefa]{"titulo":"Configurar firewall","projeto_id":5,"coluna":"a_fazer","prioridade":"alta"}[/ACTION]',
                'retorno' => 'ID da tarefa criada',
            ],
            'listar_tarefas' => [
                'descricao' => 'Listar tarefas de um projeto ou sprint',
                'params' => [
                    'projeto_id' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'ID do projeto'],
                    'sprint_id' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'ID do sprint'],
                ],
                'retorno' => 'Lista de tarefas',
            ],
            'atualizar_tarefa' => [
                'descricao' => 'Atualizar uma tarefa existente',
                'params' => [
                    'id' => ['tipo' => 'int', 'obrigatorio' => true, 'desc' => 'ID da tarefa'],
                    'titulo' => ['tipo' => 'string', 'obrigatorio' => false],
                    'descricao' => ['tipo' => 'string', 'obrigatorio' => false],
                    'coluna' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Mover para coluna'],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false],
                    'responsavel_id' => ['tipo' => 'int', 'obrigatorio' => false],
                    'sprint_id' => ['tipo' => 'int', 'obrigatorio' => false],
                ],
                'retorno' => 'Confirmação',
            ],

            // ===== SPRINTS =====
            'criar_sprint' => [
                'descricao' => 'Criar um sprint em um projeto',
                'params' => [
                    'nome' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Nome do sprint'],
                    'projeto_id' => ['tipo' => 'int', 'obrigatorio' => true, 'desc' => 'ID do projeto'],
                    'data_inicio' => ['tipo' => 'date', 'obrigatorio' => true, 'desc' => 'Data início YYYY-MM-DD'],
                    'data_fim' => ['tipo' => 'date', 'obrigatorio' => true, 'desc' => 'Data fim YYYY-MM-DD'],
                    'meta' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Meta/objetivo do sprint'],
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'planejamento|ativa', 'padrao' => 'planejamento'],
                ],
                'exemplo' => '[ACTION:criar_sprint]{"nome":"Sprint 1","projeto_id":5,"data_inicio":"2026-03-16","data_fim":"2026-03-30","meta":"Setup inicial"}[/ACTION]',
                'retorno' => 'ID do sprint criado',
            ],
            'listar_sprints' => [
                'descricao' => 'Listar sprints de um projeto',
                'params' => [
                    'projeto_id' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'ID do projeto'],
                ],
                'retorno' => 'Lista de sprints',
            ],
            'atualizar_sprint' => [
                'descricao' => 'Atualizar um sprint',
                'params' => [
                    'id' => ['tipo' => 'int', 'obrigatorio' => true],
                    'nome' => ['tipo' => 'string', 'obrigatorio' => false],
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'planejamento|ativa|concluida|cancelada'],
                    'meta' => ['tipo' => 'string', 'obrigatorio' => false],
                    'data_inicio' => ['tipo' => 'date', 'obrigatorio' => false],
                    'data_fim' => ['tipo' => 'date', 'obrigatorio' => false],
                ],
                'retorno' => 'Confirmação',
            ],

            // ===== CHAMADOS =====
            'criar_chamado' => [
                'descricao' => 'Criar um chamado/ticket de suporte',
                'params' => [
                    'titulo' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Título do chamado'],
                    'descricao' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Descrição do problema'],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'baixa|media|alta|critica', 'padrao' => 'media'],
                    'categoria_id' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'ID da categoria'],
                    'tecnico_id' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'ID do técnico responsável'],
                ],
                'retorno' => 'Código do chamado criado',
            ],
            'listar_chamados' => [
                'descricao' => 'Listar chamados/tickets',
                'params' => [
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'aberto|em_analise|em_atendimento|aguardando_usuario|resolvido|fechado'],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false],
                    'tecnico_id' => ['tipo' => 'int', 'obrigatorio' => false],
                    'busca' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Buscar por texto'],
                    'limite' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => 'Máximo de resultados', 'padrao' => 20],
                ],
                'retorno' => 'Lista de chamados',
            ],
            'atualizar_chamado' => [
                'descricao' => 'Atualizar um chamado existente',
                'params' => [
                    'id' => ['tipo' => 'int', 'obrigatorio' => true, 'desc' => 'ID do chamado'],
                    'status' => ['tipo' => 'string', 'obrigatorio' => false],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false],
                    'tecnico_id' => ['tipo' => 'int', 'obrigatorio' => false],
                ],
                'retorno' => 'Confirmação',
            ],
            'comentar_chamado' => [
                'descricao' => 'Adicionar comentário/resposta a um chamado',
                'params' => [
                    'chamado_id' => ['tipo' => 'int', 'obrigatorio' => true],
                    'conteudo' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Texto do comentário'],
                    'tipo' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'resposta|nota_interna', 'padrao' => 'resposta'],
                ],
                'retorno' => 'Confirmação',
            ],

            // ===== INVENTÁRIO =====
            'criar_ativo' => [
                'descricao' => 'Cadastrar um ativo de TI no inventário',
                'params' => [
                    'tipo' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'computador|servidor|switch|roteador|impressora|software|monitor|telefone|outro'],
                    'nome' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Nome/identificação do ativo'],
                    'numero_patrimonio' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Número de patrimônio'],
                    'modelo' => ['tipo' => 'string', 'obrigatorio' => false],
                    'fabricante' => ['tipo' => 'string', 'obrigatorio' => false],
                    'numero_serie' => ['tipo' => 'string', 'obrigatorio' => false],
                    'localizacao' => ['tipo' => 'string', 'obrigatorio' => false],
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'ativo|manutencao|inativo|descartado', 'padrao' => 'ativo'],
                ],
                'retorno' => 'ID do ativo criado',
            ],
            'listar_inventario' => [
                'descricao' => 'Listar ativos do inventário',
                'params' => [
                    'tipo' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Filtrar por tipo'],
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Filtrar por status'],
                    'busca' => ['tipo' => 'string', 'obrigatorio' => false],
                ],
                'retorno' => 'Lista de ativos',
            ],

            // ===== COMPRAS =====
            'criar_compra' => [
                'descricao' => 'Criar uma requisição de compra',
                'params' => [
                    'item' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Nome do item'],
                    'descricao' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'Descrição detalhada'],
                    'quantidade' => ['tipo' => 'int', 'obrigatorio' => false, 'padrao' => 1],
                    'justificativa' => ['tipo' => 'string', 'obrigatorio' => false],
                    'prioridade' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'baixa|media|alta|critica', 'padrao' => 'media'],
                    'valor_estimado' => ['tipo' => 'float', 'obrigatorio' => false],
                ],
                'retorno' => 'ID e código da compra',
            ],
            'listar_compras' => [
                'descricao' => 'Listar requisições de compra',
                'params' => [
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'solicitado|em_analise|aprovado|reprovado|comprado|entregue|cancelado'],
                    'busca' => ['tipo' => 'string', 'obrigatorio' => false],
                ],
                'retorno' => 'Lista de compras',
            ],

            // ===== BASE DE CONHECIMENTO =====
            'criar_artigo' => [
                'descricao' => 'Criar artigo na base de conhecimento',
                'params' => [
                    'titulo' => ['tipo' => 'string', 'obrigatorio' => true],
                    'problema' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Descrição do problema'],
                    'solucao' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Solução detalhada'],
                    'publicado' => ['tipo' => 'int', 'obrigatorio' => false, 'desc' => '0 ou 1', 'padrao' => 1],
                ],
                'retorno' => 'ID do artigo criado',
            ],
            'buscar_conhecimento' => [
                'descricao' => 'Buscar artigos na base de conhecimento',
                'params' => [
                    'busca' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Termo de busca'],
                ],
                'retorno' => 'Lista de artigos relevantes',
            ],

            // ===== REDE =====
            'ping_host' => [
                'descricao' => 'Fazer ping em um host/IP',
                'params' => [
                    'ip' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'IP ou hostname'],
                ],
                'retorno' => 'Resultado do ping (online/offline, latência)',
            ],
            'listar_dispositivos_rede' => [
                'descricao' => 'Listar dispositivos de rede monitorados',
                'params' => [
                    'tipo' => ['tipo' => 'string', 'obrigatorio' => false],
                    'status' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'online|offline'],
                ],
                'retorno' => 'Lista de dispositivos',
            ],

            // ===== USUÁRIOS (leitura) =====
            'listar_usuarios' => [
                'descricao' => 'Listar usuários do sistema',
                'params' => [
                    'tipo' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'admin|gestor|tecnico'],
                    'busca' => ['tipo' => 'string', 'obrigatorio' => false],
                ],
                'retorno' => 'Lista de usuários com ID, nome, email, tipo, cargo',
            ],
            'listar_categorias' => [
                'descricao' => 'Listar categorias do sistema (chamados, conhecimento, etc)',
                'params' => [
                    'tipo_categoria' => ['tipo' => 'string', 'obrigatorio' => false, 'desc' => 'chamado|conhecimento'],
                ],
                'retorno' => 'Lista de categorias com ID e nome',
            ],

            // ===== DASHBOARD / ESTATÍSTICAS =====
            'obter_estatisticas' => [
                'descricao' => 'Obter estatísticas gerais do sistema (dashboard)',
                'params' => [],
                'retorno' => 'Estatísticas: chamados abertos/resolvidos, projetos, tarefas, etc.',
            ],

            // ===== SSH =====
            'executar_comando_ssh' => [
                'descricao' => 'Executar um comando em um servidor SSH cadastrado',
                'params' => [
                    'servidor_id' => ['tipo' => 'int', 'obrigatorio' => true, 'desc' => 'ID do servidor'],
                    'comando' => ['tipo' => 'string', 'obrigatorio' => true, 'desc' => 'Comando a executar'],
                ],
                'retorno' => 'Saída do comando, erros, exit code',
            ],
            'listar_servidores' => [
                'descricao' => 'Listar servidores SSH cadastrados',
                'params' => [],
                'retorno' => 'Lista de servidores com ID, nome, host, status',
            ],
        ];
    }

    /**
     * Gerar descrição das ferramentas para o system prompt
     */
    public static function getToolsPrompt() {
        return self::getToolsPromptCompact();
    }

    /**
     * Prompt compacto de ferramentas (~500 tokens vs ~2000 do verbose)
     * Otimizado para modelos menores rodando em CPU
     */
    public static function getToolsPromptCompact() {
        $tools = self::getToolDefinitions();

        $prompt = "## AÇÕES DISPONÍVEIS\n";
        $prompt .= "Formato EXATO: [ACTION:funcao]{\"param\":\"valor\"}[/ACTION]\n";
        $prompt .= "IMPORTANTE: Use JSON válido com aspas duplas. Pode usar múltiplas ações na mesma resposta.\n";
        $prompt .= "Data hoje: " . date('Y-m-d') . "\n\n";

        // Agrupar por categoria para melhor compreensão
        $categories = [
            'Projetos' => ['criar_projeto', 'listar_projetos', 'atualizar_projeto'],
            'Tarefas' => ['criar_tarefa', 'listar_tarefas', 'atualizar_tarefa'],
            'Sprints' => ['criar_sprint', 'listar_sprints', 'atualizar_sprint'],
            'Chamados' => ['criar_chamado', 'listar_chamados', 'atualizar_chamado', 'comentar_chamado'],
            'Outros' => ['criar_ativo', 'listar_inventario', 'criar_compra', 'listar_compras',
                         'criar_artigo', 'buscar_conhecimento', 'ping_host', 'listar_dispositivos_rede',
                         'listar_usuarios', 'listar_categorias', 'obter_estatisticas',
                         'executar_comando_ssh', 'listar_servidores'],
        ];

        foreach ($categories as $cat => $names) {
            $prompt .= "**{$cat}:**\n";
            foreach ($names as $name) {
                if (!isset($tools[$name])) continue;
                $tool = $tools[$name];
                $reqParams = [];
                $optParams = [];
                foreach (($tool['params'] ?? []) as $pname => $pdef) {
                    if ($pdef['obrigatorio'] ?? false) {
                        $reqParams[] = $pname;
                    } else {
                        $optParams[] = $pname;
                    }
                }
                $paramsStr = implode(',', $reqParams);
                $optStr = $optParams ? ' [' . implode(',', array_slice($optParams, 0, 3)) . ']' : '';
                $prompt .= "- {$name}({$paramsStr}){$optStr}: {$tool['descricao']}\n";
            }
        }

        $prompt .= "\nRegras:\n";
        $prompt .= "- Ao criar projeto+sprint+tarefa: primeiro crie o projeto, depois use o ID retornado nos sprints e tarefas\n";
        $prompt .= "- Sempre gere TODAS as ações necessárias de uma vez na resposta\n";
        $prompt .= "- Prioridades: baixa|media|alta|critica. Status projeto: planejamento|em_desenvolvimento\n";
        $prompt .= "- Cada bloco ACTION deve ter JSON completo e válido\n";

        return $prompt;
    }

    /**
     * Prompt verbose completo (para modelos maiores com mais contexto)
     */
    public static function getToolsPromptVerbose() {
        $tools = self::getToolDefinitions();
        $prompt = "## FERRAMENTAS DISPONÍVEIS\n\n";
        $prompt .= "Formato: `[ACTION:nome_funcao]{\"parametro\":\"valor\"}[/ACTION]`\n";
        $prompt .= "Pode usar múltiplas ações. Use JSON válido.\n\n";

        foreach ($tools as $name => $tool) {
            $prompt .= "### `{$name}` — {$tool['descricao']}\n";
            if (!empty($tool['params'])) {
                foreach ($tool['params'] as $pname => $pdef) {
                    $req = ($pdef['obrigatorio'] ?? false) ? '*' : '';
                    $desc = $pdef['desc'] ?? $pdef['tipo'];
                    $prompt .= "- {$pname}{$req}: {$desc}\n";
                }
            }
            if (isset($tool['exemplo'])) {
                $prompt .= "Ex: `{$tool['exemplo']}`\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Regras: Use listar_* para encontrar IDs. Data: " . date('Y-m-d') . "\n";
        return $prompt;
    }

    /**
     * Extrair ações da resposta da IA
     */
    public function parseActions($responseText) {
        $actions = [];
        preg_match_all('/\[ACTION:(\w+)\](.*?)\[\/ACTION\]/s', $responseText, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $actionName = $match[1];
            $paramsJson = trim($match[2]);
            $params = json_decode($paramsJson, true);

            if ($params === null && !empty($paramsJson)) {
                // Tentar limpar JSON malformado
                $paramsJson = preg_replace('/,\s*}/', '}', $paramsJson);
                $paramsJson = preg_replace('/,\s*]/', ']', $paramsJson);
                $params = json_decode($paramsJson, true);
            }

            $actions[] = [
                'name' => $actionName,
                'params' => $params ?: [],
                'raw' => $match[0],
            ];
        }

        return $actions;
    }

    /**
     * Executar uma ação específica
     */
    public function executeAction($actionName, $params) {
        $tools = self::getToolDefinitions();
        if (!isset($tools[$actionName])) {
            return ['success' => false, 'error' => "Ação '{$actionName}' não existe"];
        }

        // Validar parâmetros obrigatórios
        foreach ($tools[$actionName]['params'] as $pname => $pdef) {
            if (($pdef['obrigatorio'] ?? false) && empty($params[$pname])) {
                return ['success' => false, 'error' => "Parâmetro obrigatório '{$pname}' não informado"];
            }
        }

        try {
            $method = 'action_' . $actionName;
            if (method_exists($this, $method)) {
                return $this->$method($params);
            }
            return ['success' => false, 'error' => "Ação '{$actionName}' não implementada"];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Executar todas as ações encontradas na resposta e substituir pelos resultados
     */
    public function processResponse($responseText) {
        $actions = $this->parseActions($responseText);

        if (empty($actions)) {
            return ['text' => $responseText, 'actions' => [], 'hasActions' => false];
        }

        $results = [];
        $processedText = $responseText;

        foreach ($actions as $action) {
            $result = $this->executeAction($action['name'], $action['params']);
            $results[] = [
                'name' => $action['name'],
                'params' => $action['params'],
                'result' => $result,
            ];

            // Substituir a ação pelo resultado formatado
            $resultBlock = $this->formatActionResult($action['name'], $result);
            $processedText = str_replace($action['raw'], $resultBlock, $processedText);
        }

        return ['text' => $processedText, 'actions' => $results, 'hasActions' => true];
    }

    /**
     * Formatar resultado da ação para exibição
     */
    public function formatActionResult($actionName, $result) {
        if ($result['success']) {
            $emoji = '✅';
            $data = $result['data'] ?? '';
            if (is_array($data)) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
            return "\n\n> {$emoji} **{$actionName}** executado com sucesso\n> {$result['message']}\n\n";
        } else {
            return "\n\n> ❌ **{$actionName}** falhou: {$result['error']}\n\n";
        }
    }

    // =========================================================================
    //  EXECUÇÃO DE PLANO ESTRUTURADO (2 fases)
    //  Recebe JSON plan do AI e cria tudo automaticamente com IDs encadeados
    // =========================================================================

    /**
     * Gerar o prompt de planejamento para a IA
     * Formato ultra-simples: nome do projeto + lista de tarefas (bullet list)
     * PHP monta toda a estrutura (sprints, prioridades, pontos)
     */
    public static function getPlannerPrompt() {
        return "List 5 to 8 tasks for the user's project. Reply ONLY with this format:\nNAME: short project name\n- task 1\n- task 2\n- task 3\nNothing else. No explanations. Task titles in the user's language.";
    }

    /**
     * Parsear resposta simples da IA (NOME: + lista de tarefas)
     * e construir plano completo com sprints e tarefas distribuídas
     */
    public static function parsePlanText($text) {
        $lines = array_filter(array_map('trim', explode("\n", $text)), fn($l) => $l !== '');

        $projectName = '';
        $taskTitles = [];

        // Padrões a ignorar (headers, labels, etc.)
        $skipPatterns = '/^(sprint\s*\d*\s*:|tarefas?:|goal:|objetivo:|nota:|obs:|fase\s*\d*\s*:)/iu';

        foreach ($lines as $line) {
            // NOME: ou PROJETO: na primeira linha com nome
            if (preg_match('/^(?:NOME|PROJETO|NAME)\s*:\s*(.+)/iu', $line, $m) && !$projectName) {
                $projectName = trim($m[1]);
                if (mb_strlen($projectName) > 100) $projectName = mb_substr($projectName, 0, 100);
            }
            // Linhas com - ou * ou + ou • ou número. (bullet list, qualquer nível de indentação)
            elseif (preg_match('/^[-*•+]\s+(.+)/u', $line, $m) || preg_match('/^\d+[.)]\s+(.+)/u', $line, $m)) {
                $title = trim($m[1]);
                // Ignorar títulos curtos, headers e duplicatas
                if (mb_strlen($title) < 8) continue;
                if (preg_match($skipPatterns, $title)) continue;
                if (mb_strlen($title) > 120) $title = mb_substr($title, 0, 120);
                // Deduplicar por título normalizado
                $normalized = mb_strtolower(preg_replace('/\s+/', ' ', $title));
                if (in_array($normalized, array_map(fn($t) => mb_strtolower(preg_replace('/\s+/', ' ', $t)), $taskTitles))) continue;
                if (count($taskTitles) < 10) {
                    $taskTitles[] = $title;
                }
            }
        }

        // Se não encontrou nome, usar primeira linha não-vazia
        if (!$projectName && !empty($lines)) {
            $first = reset($lines);
            if (mb_strlen($first) < 100) $projectName = $first;
            else $projectName = mb_substr($first, 0, 100);
        }

        if (!$projectName || empty($taskTitles)) {
            return null;
        }

        // Construir plano estruturado com 2 sprints
        $mid = (int)ceil(count($taskTitles) / 2);
        $plan = [
            'projeto' => [
                'nome' => $projectName,
                'descricao' => "Projeto gerado por IA com " . count($taskTitles) . " tarefas",
                'prioridade' => 'media',
            ],
            'sprints' => [
                ['nome' => 'Sprint 1 - Planejamento e Setup', 'duracao_semanas' => 2, 'meta' => 'Preparação e fundações'],
                ['nome' => 'Sprint 2 - Desenvolvimento e Entrega', 'duracao_semanas' => 2, 'meta' => 'Implementação e finalização'],
            ],
            'tarefas' => [],
        ];

        foreach ($taskTitles as $i => $title) {
            $plan['tarefas'][] = [
                'titulo' => $title,
                'sprint_index' => $i < $mid ? 0 : 1,
                'prioridade' => 'media',
                'pontos' => 3,
            ];
        }

        return $plan;
    }

    /**
     * Executar um plano estruturado: cria projeto, sprints e tarefas encadeados
     * @param array $plan O plano parseado do JSON
     * @param callable $onProgress Callback para notificar progresso
     * @return array Resultado com resumo de tudo criado
     */
    public function executePlan($plan, $onProgress = null) {
        $results = [];
        $errors = [];

        // === 1. CRIAR PROJETO ===
        $projetoData = $plan['projeto'] ?? null;
        if (!$projetoData || empty($projetoData['nome'])) {
            return ['success' => false, 'error' => 'Plano sem dados de projeto'];
        }

        if ($onProgress) $onProgress('criar_projeto', 'executing', 0, $projetoData['nome']);

        $projetoResult = $this->executeAction('criar_projeto', [
            'nome' => $projetoData['nome'],
            'descricao' => $projetoData['descricao'] ?? '',
            'prioridade' => self::sanitizePrioridade($projetoData['prioridade'] ?? 'media'),
            'data_inicio' => date('Y-m-d'),
            'prazo' => $projetoData['prazo'] ?? null,
        ]);

        if (!$projetoResult['success']) {
            if ($onProgress) $onProgress('criar_projeto', 'error', 0, $projetoResult['error']);
            return ['success' => false, 'error' => 'Falha ao criar projeto: ' . $projetoResult['error']];
        }

        $projetoId = (int)$projetoResult['data']['id'];
        $results[] = $projetoResult;
        if ($onProgress) $onProgress('criar_projeto', 'done', 0, $projetoResult['message']);

        // === 2. CRIAR SPRINTS ===
        $sprints = $plan['sprints'] ?? [];
        $sprintIds = [];
        $sprintStartDate = new \DateTime();

        foreach ($sprints as $i => $sprint) {
            $sprintName = $sprint['nome'] ?? 'Sprint ' . ($i + 1);
            $duracao = (int)($sprint['duracao_semanas'] ?? 2);

            $dataInicio = $sprintStartDate->format('Y-m-d');
            $dataFim = (clone $sprintStartDate)->modify("+{$duracao} weeks")->format('Y-m-d');
            $sprintStartDate->modify("+{$duracao} weeks"); // próximo sprint começa depois

            if ($onProgress) $onProgress('criar_sprint', 'executing', $i + 1, $sprintName);

            $sprintResult = $this->executeAction('criar_sprint', [
                'nome' => $sprintName,
                'projeto_id' => $projetoId,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'meta' => $sprint['meta'] ?? '',
                'status' => $i === 0 ? 'ativa' : 'planejamento',
            ]);

            if ($sprintResult['success']) {
                $sprintIds[$i] = (int)$sprintResult['data']['id'];
                $results[] = $sprintResult;
                if ($onProgress) $onProgress('criar_sprint', 'done', $i + 1, $sprintResult['message']);
            } else {
                $errors[] = "Sprint '{$sprintName}': " . $sprintResult['error'];
                if ($onProgress) $onProgress('criar_sprint', 'error', $i + 1, $sprintResult['error']);
            }
        }

        // === 3. CRIAR TAREFAS ===
        $tarefas = $plan['tarefas'] ?? [];
        $tarefasCriadas = 0;

        foreach ($tarefas as $j => $tarefa) {
            $tituloTarefa = $tarefa['titulo'] ?? 'Tarefa ' . ($j + 1);
            $sprintIndex = (int)($tarefa['sprint_index'] ?? 0);
            $sprintId = $sprintIds[$sprintIndex] ?? ($sprintIds[0] ?? null);

            if ($onProgress) $onProgress('criar_tarefa', 'executing', $j + 1, $tituloTarefa);

            // Sanitizar prioridade (IA pode gerar 'médio','grande','alto', etc)
            $prio = self::sanitizePrioridade($tarefa['prioridade'] ?? 'media');

            $tarefaResult = $this->executeAction('criar_tarefa', [
                'titulo' => $tituloTarefa,
                'descricao' => $tarefa['descricao'] ?? '',
                'projeto_id' => $projetoId,
                'sprint_id' => $sprintId,
                'coluna' => $tarefa['coluna'] ?? 'backlog',
                'prioridade' => $prio,
                'pontos' => $tarefa['pontos'] ?? null,
            ]);

            if ($tarefaResult['success']) {
                $tarefasCriadas++;
                $results[] = $tarefaResult;
                if ($onProgress) $onProgress('criar_tarefa', 'done', $j + 1, $tarefaResult['message']);
            } else {
                $errors[] = "Tarefa '{$tituloTarefa}': " . $tarefaResult['error'];
                if ($onProgress) $onProgress('criar_tarefa', 'error', $j + 1, $tarefaResult['error']);
            }
        }

        // === RESUMO ===
        $resumo = "## ✅ Projeto criado com sucesso!\n\n";
        $resumo .= "**Projeto:** {$projetoData['nome']} (ID #{$projetoId})\n";
        $resumo .= "**Sprints:** " . count($sprintIds) . " criado(s)\n";
        $resumo .= "**Tarefas:** {$tarefasCriadas} criada(s)\n\n";

        if (!empty($sprints)) {
            $resumo .= "### 📋 Sprints\n";
            foreach ($sprints as $i => $s) {
                $sid = $sprintIds[$i] ?? '?';
                $icon = isset($sprintIds[$i]) ? '✅' : '❌';
                $resumo .= "{$icon} **{$s['nome']}** (ID #{$sid})";
                if (!empty($s['meta'])) $resumo .= " — {$s['meta']}";
                $resumo .= "\n";
            }
            $resumo .= "\n";
        }

        if (!empty($tarefas)) {
            $resumo .= "### 📌 Tarefas\n";
            foreach ($results as $r) {
                if (isset($r['data']['titulo'])) {
                    $resumo .= "✅ {$r['data']['titulo']} (ID #{$r['data']['id']})\n";
                }
            }
            $resumo .= "\n";
        }

        if (!empty($errors)) {
            $resumo .= "### ⚠️ Erros\n";
            foreach ($errors as $e) $resumo .= "- {$e}\n";
        }

        $resumo .= "\n> 💡 Acesse o projeto em **Projetos → {$projetoData['nome']}** para gerenciar o board Kanban.";

        return [
            'success' => true,
            'message' => $resumo,
            'data' => [
                'projeto_id' => $projetoId,
                'sprints_criados' => count($sprintIds),
                'tarefas_criadas' => $tarefasCriadas,
                'erros' => $errors,
            ],
            'results' => $results,
        ];
    }

    /**
     * Normalizar prioridade gerada pela IA para valores válidos do banco
     */
    private static function sanitizePrioridade($prio) {
        $prio = mb_strtolower(trim($prio));
        $map = [
            'baixa' => 'baixa', 'low' => 'baixa', 'baixo' => 'baixa',
            'media' => 'media', 'média' => 'media', 'medio' => 'media', 'médio' => 'media', 'medium' => 'media', 'normal' => 'media',
            'alta' => 'alta', 'alto' => 'alta', 'high' => 'alta', 'grande' => 'alta',
            'critica' => 'critica', 'crítica' => 'critica', 'critical' => 'critica', 'urgente' => 'critica',
        ];
        return $map[$prio] ?? 'media';
    }

    // =========================================================================
    //  IMPLEMENTAÇÃO DAS AÇÕES
    // =========================================================================

    // ----- PROJETOS -----
    private function action_criar_projeto($params) {
        require_once __DIR__ . '/Projeto.php';
        $model = new Projeto();

        $dados = [
            'nome' => $params['nome'],
            'descricao' => $params['descricao'] ?? '',
            'responsavel_id' => $this->userId,
            'prioridade' => $params['prioridade'] ?? 'media',
            'status' => 'planejamento',
            'data_inicio' => $params['data_inicio'] ?? date('Y-m-d'),
            'prazo' => $params['prazo'] ?? null,
        ];

        $id = $model->criar($dados);
        // Adicionar criador à equipe
        $model->adicionarMembro($id, $this->userId, 'lider');

        return [
            'success' => true,
            'message' => "Projeto **\"{$params['nome']}\"** criado com ID #{$id}",
            'data' => ['id' => $id, 'nome' => $params['nome']],
        ];
    }

    private function action_listar_projetos($params) {
        require_once __DIR__ . '/Projeto.php';
        $model = new Projeto();

        $filtros = [];
        if (!empty($params['status'])) $filtros['status'] = $params['status'];
        if (!empty($params['busca'])) $filtros['busca'] = $params['busca'];

        $projetos = $model->listar($filtros);
        $resumo = array_map(function($p) {
            return [
                'id' => $p['id'],
                'nome' => $p['nome'],
                'status' => $p['status'],
                'prioridade' => $p['prioridade'],
                'progresso' => ($p['progresso'] ?? 0) . '%',
                'prazo' => $p['prazo'] ?? 'Sem prazo',
            ];
        }, $projetos);

        return [
            'success' => true,
            'message' => count($resumo) . " projeto(s) encontrado(s)",
            'data' => $resumo,
        ];
    }

    private function action_atualizar_projeto($params) {
        require_once __DIR__ . '/Projeto.php';
        $model = new Projeto();

        $id = (int)$params['id'];
        $projeto = $model->findById($id);
        if (!$projeto) return ['success' => false, 'error' => "Projeto #{$id} não encontrado"];

        $dados = [];
        foreach (['nome', 'descricao', 'status', 'prioridade', 'prazo'] as $campo) {
            if (isset($params[$campo])) $dados[$campo] = $params[$campo];
        }

        $model->atualizar($id, $dados);
        return [
            'success' => true,
            'message' => "Projeto #{$id} **\"{$projeto['nome']}\"** atualizado",
            'data' => ['id' => $id],
        ];
    }

    // ----- TAREFAS -----
    private function action_criar_tarefa($params) {
        require_once __DIR__ . '/Tarefa.php';
        $model = new Tarefa();

        $dados = [
            'titulo' => $params['titulo'],
            'descricao' => $params['descricao'] ?? '',
            'projeto_id' => (int)$params['projeto_id'],
            'sprint_id' => !empty($params['sprint_id']) ? (int)$params['sprint_id'] : null,
            'responsavel_id' => !empty($params['responsavel_id']) ? (int)$params['responsavel_id'] : $this->userId,
            'coluna' => $params['coluna'] ?? 'backlog',
            'prioridade' => $params['prioridade'] ?? 'media',
            'prazo' => $params['prazo'] ?? null,
            'pontos' => $params['pontos'] ?? null,
            'horas_estimadas' => $params['horas_estimadas'] ?? null,
        ];

        $id = $model->criar($dados);

        return [
            'success' => true,
            'message' => "Tarefa **\"{$params['titulo']}\"** criada (ID #{$id}) no projeto #{$params['projeto_id']}",
            'data' => ['id' => $id, 'titulo' => $params['titulo']],
        ];
    }

    private function action_listar_tarefas($params) {
        require_once __DIR__ . '/Tarefa.php';
        $model = new Tarefa();

        if (!empty($params['sprint_id'])) {
            $tarefas = $model->listarPorSprint((int)$params['sprint_id']);
        } elseif (!empty($params['projeto_id'])) {
            $tarefas = $model->listarPorProjeto((int)$params['projeto_id']);
        } else {
            $tarefas = $model->listarPorColuna();
        }

        $resumo = array_map(function($t) {
            return [
                'id' => $t['id'],
                'titulo' => $t['titulo'],
                'coluna' => $t['coluna'],
                'prioridade' => $t['prioridade'],
                'responsavel' => $t['responsavel_nome'] ?? 'Sem responsável',
                'prazo' => $t['prazo'] ?? '-',
            ];
        }, $tarefas);

        return [
            'success' => true,
            'message' => count($resumo) . " tarefa(s) encontrada(s)",
            'data' => $resumo,
        ];
    }

    private function action_atualizar_tarefa($params) {
        require_once __DIR__ . '/Tarefa.php';
        $model = new Tarefa();

        $id = (int)$params['id'];
        $tarefa = $model->findById($id);
        if (!$tarefa) return ['success' => false, 'error' => "Tarefa #{$id} não encontrada"];

        $dados = [];
        foreach (['titulo', 'descricao', 'coluna', 'prioridade', 'responsavel_id', 'sprint_id', 'prazo', 'pontos'] as $campo) {
            if (isset($params[$campo])) $dados[$campo] = $params[$campo];
        }

        $model->atualizar($id, $dados);
        return [
            'success' => true,
            'message' => "Tarefa #{$id} **\"{$tarefa['titulo']}\"** atualizada",
            'data' => ['id' => $id],
        ];
    }

    // ----- SPRINTS -----
    private function action_criar_sprint($params) {
        require_once __DIR__ . '/Sprint.php';
        $model = new Sprint();

        $dados = [
            'nome' => $params['nome'],
            'projeto_id' => (int)$params['projeto_id'],
            'data_inicio' => $params['data_inicio'],
            'data_fim' => $params['data_fim'],
            'meta' => $params['meta'] ?? '',
            'status' => $params['status'] ?? 'planejamento',
        ];

        $id = $model->criar($dados);
        return [
            'success' => true,
            'message' => "Sprint **\"{$params['nome']}\"** criado (ID #{$id}) no projeto #{$params['projeto_id']}",
            'data' => ['id' => $id, 'nome' => $params['nome']],
        ];
    }

    private function action_listar_sprints($params) {
        require_once __DIR__ . '/Sprint.php';
        $model = new Sprint();

        $projetoId = !empty($params['projeto_id']) ? (int)$params['projeto_id'] : null;
        $sprints = $model->listar($projetoId);

        $resumo = array_map(function($s) {
            return [
                'id' => $s['id'],
                'nome' => $s['nome'],
                'projeto_id' => $s['projeto_id'],
                'status' => $s['status'],
                'data_inicio' => $s['data_inicio'],
                'data_fim' => $s['data_fim'],
                'total_tarefas' => $s['total_tarefas'] ?? 0,
            ];
        }, $sprints);

        return [
            'success' => true,
            'message' => count($resumo) . " sprint(s) encontrado(s)",
            'data' => $resumo,
        ];
    }

    private function action_atualizar_sprint($params) {
        require_once __DIR__ . '/Sprint.php';
        $model = new Sprint();

        $id = (int)$params['id'];
        $sprint = $model->findById($id);
        if (!$sprint) return ['success' => false, 'error' => "Sprint #{$id} não encontrado"];

        $dados = [];
        foreach (['nome', 'status', 'meta', 'data_inicio', 'data_fim'] as $campo) {
            if (isset($params[$campo])) $dados[$campo] = $params[$campo];
        }

        $model->atualizar($id, $dados);
        return [
            'success' => true,
            'message' => "Sprint #{$id} **\"{$sprint['nome']}\"** atualizado",
            'data' => ['id' => $id],
        ];
    }

    // ----- CHAMADOS -----
    private function action_criar_chamado($params) {
        require_once __DIR__ . '/Chamado.php';
        $model = new Chamado();

        $codigo = 'CHM-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        $dados = [
            'codigo' => $codigo,
            'titulo' => $params['titulo'],
            'descricao' => $params['descricao'],
            'prioridade' => $params['prioridade'] ?? 'media',
            'categoria_id' => $params['categoria_id'] ?? null,
            'solicitante_id' => $this->userId,
            'tecnico_id' => $params['tecnico_id'] ?? null,
            'status' => 'aberto',
            'canal' => 'interno',
        ];

        $id = $model->criar($dados);
        return [
            'success' => true,
            'message' => "Chamado **{$codigo}** criado — \"{$params['titulo']}\"",
            'data' => ['id' => $id, 'codigo' => $codigo],
        ];
    }

    private function action_listar_chamados($params) {
        require_once __DIR__ . '/Chamado.php';
        $model = new Chamado();

        $filtros = [];
        foreach (['status', 'prioridade', 'tecnico_id', 'busca'] as $k) {
            if (!empty($params[$k])) $filtros[$k] = $params[$k];
        }
        $limite = (int)($params['limite'] ?? 20);

        $chamados = $model->listar($filtros, $limite);
        $resumo = array_map(function($c) {
            return [
                'id' => $c['id'],
                'codigo' => $c['codigo'],
                'titulo' => $c['titulo'],
                'status' => $c['status'],
                'prioridade' => $c['prioridade'],
                'tecnico' => $c['tecnico_nome'] ?? 'Não atribuído',
                'data_abertura' => $c['data_abertura'],
            ];
        }, $chamados);

        return [
            'success' => true,
            'message' => count($resumo) . " chamado(s) encontrado(s)",
            'data' => $resumo,
        ];
    }

    private function action_atualizar_chamado($params) {
        require_once __DIR__ . '/Chamado.php';
        $model = new Chamado();

        $id = (int)$params['id'];
        $chamado = $model->findById($id);
        if (!$chamado) return ['success' => false, 'error' => "Chamado #{$id} não encontrado"];

        $dados = [];
        foreach (['status', 'prioridade', 'tecnico_id'] as $campo) {
            if (isset($params[$campo])) {
                $valorAnterior = $chamado[$campo] ?? '';
                $dados[$campo] = $params[$campo];
                $model->registrarHistorico($id, $this->userId, $campo, $valorAnterior, $params[$campo]);
            }
        }

        $model->atualizar($id, $dados);
        return [
            'success' => true,
            'message' => "Chamado #{$id} **\"{$chamado['titulo']}\"** atualizado",
            'data' => ['id' => $id, 'codigo' => $chamado['codigo']],
        ];
    }

    private function action_comentar_chamado($params) {
        require_once __DIR__ . '/Chamado.php';
        $model = new Chamado();

        $chamadoId = (int)$params['chamado_id'];
        $chamado = $model->findById($chamadoId);
        if (!$chamado) return ['success' => false, 'error' => "Chamado #{$chamadoId} não encontrado"];

        $model->adicionarComentario([
            'chamado_id' => $chamadoId,
            'conteudo' => $params['conteudo'],
            'tipo' => $params['tipo'] ?? 'resposta',
            'usuario_id' => $this->userId,
        ]);

        return [
            'success' => true,
            'message' => "Comentário adicionado ao chamado **{$chamado['codigo']}**",
            'data' => ['chamado_id' => $chamadoId],
        ];
    }

    // ----- INVENTÁRIO -----
    private function action_criar_ativo($params) {
        require_once __DIR__ . '/Inventario.php';
        $model = new Inventario();

        $dados = [
            'tipo' => $params['tipo'],
            'nome' => $params['nome'],
            'numero_patrimonio' => $params['numero_patrimonio'] ?? null,
            'modelo' => $params['modelo'] ?? null,
            'fabricante' => $params['fabricante'] ?? null,
            'numero_serie' => $params['numero_serie'] ?? null,
            'localizacao' => $params['localizacao'] ?? null,
            'status' => $params['status'] ?? 'ativo',
        ];

        $id = $model->criar($dados);
        return [
            'success' => true,
            'message' => "Ativo **\"{$params['nome']}\"** cadastrado (ID #{$id})",
            'data' => ['id' => $id],
        ];
    }

    private function action_listar_inventario($params) {
        require_once __DIR__ . '/Inventario.php';
        $model = new Inventario();

        $filtros = [];
        foreach (['tipo', 'status', 'busca'] as $k) {
            if (!empty($params[$k])) $filtros[$k] = $params[$k];
        }

        $itens = $model->listar($filtros);
        $resumo = array_map(function($i) {
            return [
                'id' => $i['id'],
                'nome' => $i['nome'],
                'tipo' => $i['tipo'],
                'patrimonio' => $i['numero_patrimonio'] ?? '-',
                'status' => $i['status'],
                'localizacao' => $i['localizacao'] ?? '-',
            ];
        }, array_slice($itens, 0, 30));

        return [
            'success' => true,
            'message' => count($itens) . " ativo(s) encontrado(s)" . (count($itens) > 30 ? ' (mostrando 30)' : ''),
            'data' => $resumo,
        ];
    }

    // ----- COMPRAS -----
    private function action_criar_compra($params) {
        require_once __DIR__ . '/Compra.php';
        $model = new Compra();

        $codigo = 'CMP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        $dados = [
            'codigo' => $codigo,
            'solicitante_usuario_id' => $this->userId,
            'item' => $params['item'],
            'descricao' => $params['descricao'] ?? '',
            'quantidade' => $params['quantidade'] ?? 1,
            'justificativa' => $params['justificativa'] ?? '',
            'prioridade' => $params['prioridade'] ?? 'media',
            'valor_estimado' => $params['valor_estimado'] ?? null,
            'status' => 'solicitado',
        ];

        $id = $model->criar($dados);
        return [
            'success' => true,
            'message' => "Requisição de compra **{$codigo}** criada — \"{$params['item']}\"",
            'data' => ['id' => $id, 'codigo' => $codigo],
        ];
    }

    private function action_listar_compras($params) {
        require_once __DIR__ . '/Compra.php';
        $model = new Compra();

        $filtros = [];
        if (!empty($params['status'])) $filtros['status'] = $params['status'];
        if (!empty($params['busca'])) $filtros['busca'] = $params['busca'];

        $compras = $model->listar($filtros);
        $resumo = array_map(function($c) {
            return [
                'id' => $c['id'],
                'codigo' => $c['codigo'],
                'item' => $c['item'],
                'status' => $c['status'],
                'prioridade' => $c['prioridade'],
                'valor' => $c['valor_estimado'] ? 'R$ ' . number_format($c['valor_estimado'], 2, ',', '.') : '-',
            ];
        }, $compras);

        return [
            'success' => true,
            'message' => count($resumo) . " requisição(ões) encontrada(s)",
            'data' => $resumo,
        ];
    }

    // ----- BASE DE CONHECIMENTO -----
    private function action_criar_artigo($params) {
        require_once __DIR__ . '/BaseConhecimento.php';
        $model = new BaseConhecimento();

        $dados = [
            'titulo' => $params['titulo'],
            'problema' => $params['problema'],
            'solucao' => $params['solucao'],
            'autor_id' => $this->userId,
            'publicado' => $params['publicado'] ?? 1,
        ];

        $id = $model->criar($dados);
        return [
            'success' => true,
            'message' => "Artigo **\"{$params['titulo']}\"** publicado (ID #{$id})",
            'data' => ['id' => $id],
        ];
    }

    private function action_buscar_conhecimento($params) {
        require_once __DIR__ . '/BaseConhecimento.php';
        $model = new BaseConhecimento();

        $artigos = $model->listar(['busca' => $params['busca'], 'publicado' => 1]);
        $resumo = array_map(function($a) {
            return [
                'id' => $a['id'],
                'titulo' => $a['titulo'],
                'visualizacoes' => $a['visualizacoes'] ?? 0,
            ];
        }, array_slice($artigos, 0, 10));

        return [
            'success' => true,
            'message' => count($artigos) . " artigo(s) encontrado(s)",
            'data' => $resumo,
        ];
    }

    // ----- REDE -----
    private function action_ping_host($params) {
        require_once __DIR__ . '/Rede.php';
        $model = new Rede();

        $result = $model->ping($params['ip']);
        $status = $result['online'] ? '🟢 Online' : '🔴 Offline';
        $latencia = $result['latencia'] ? "{$result['latencia']}ms" : '-';

        return [
            'success' => true,
            'message' => "Ping {$params['ip']}: {$status} (latência: {$latencia})",
            'data' => $result,
        ];
    }

    private function action_listar_dispositivos_rede($params) {
        require_once __DIR__ . '/Rede.php';
        $model = new Rede();

        $filtros = [];
        if (!empty($params['tipo'])) $filtros['tipo'] = $params['tipo'];
        if (!empty($params['status'])) $filtros['status'] = $params['status'];

        $dispositivos = $model->listar($filtros);
        $resumo = array_map(function($d) {
            return [
                'id' => $d['id'],
                'nome' => $d['nome'],
                'ip' => $d['ip'],
                'tipo' => $d['tipo'],
                'status' => $d['status'],
            ];
        }, $dispositivos);

        return [
            'success' => true,
            'message' => count($resumo) . " dispositivo(s) encontrado(s)",
            'data' => $resumo,
        ];
    }

    // ----- USUÁRIOS -----
    private function action_listar_usuarios($params) {
        require_once __DIR__ . '/Usuario.php';
        $model = new Usuario();

        $filtros = [];
        if (!empty($params['tipo'])) $filtros['tipo'] = $params['tipo'];
        if (!empty($params['busca'])) $filtros['busca'] = $params['busca'];

        $usuarios = $model->listar($filtros);
        $resumo = array_map(function($u) {
            return [
                'id' => $u['id'],
                'nome' => $u['nome'],
                'email' => $u['email'],
                'tipo' => $u['tipo'],
                'cargo' => $u['cargo'] ?? '-',
                'departamento' => $u['departamento'] ?? '-',
            ];
        }, $usuarios);

        return [
            'success' => true,
            'message' => count($resumo) . " usuário(s) encontrado(s)",
            'data' => $resumo,
        ];
    }

    private function action_listar_categorias($params) {
        $tipo = $params['tipo_categoria'] ?? null;
        $where = $tipo ? "WHERE tipo = ?" : "WHERE 1=1";
        $p = $tipo ? [$tipo] : [];

        $categorias = $this->db->fetchAll(
            "SELECT id, nome, tipo FROM categorias {$where} AND ativo = 1 ORDER BY tipo, nome",
            $p
        );

        return [
            'success' => true,
            'message' => count($categorias) . " categoria(s) encontrada(s)",
            'data' => $categorias,
        ];
    }

    // ----- DASHBOARD -----
    private function action_obter_estatisticas($params) {
        $stats = [];

        // Chamados
        $stats['chamados'] = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aberto' THEN 1 ELSE 0 END) as abertos,
                SUM(CASE WHEN status IN ('em_analise','em_atendimento') THEN 1 ELSE 0 END) as em_andamento,
                SUM(CASE WHEN status IN ('resolvido','fechado') THEN 1 ELSE 0 END) as resolvidos,
                SUM(CASE WHEN prioridade = 'critica' AND status NOT IN ('resolvido','fechado') THEN 1 ELSE 0 END) as criticos
             FROM chamados"
        );

        // Projetos
        $stats['projetos'] = $this->db->fetch(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN status = 'em_desenvolvimento' THEN 1 ELSE 0 END) as ativos
             FROM projetos WHERE status != 'cancelado'"
        );

        // Tarefas
        $stats['tarefas'] = $this->db->fetch(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN coluna = 'concluido' THEN 1 ELSE 0 END) as concluidas,
                    SUM(CASE WHEN prazo < CURDATE() AND coluna != 'concluido' THEN 1 ELSE 0 END) as atrasadas
             FROM tarefas"
        );

        // Inventário
        $stats['inventario'] = $this->db->fetch(
            "SELECT COUNT(*) as total FROM inventario WHERE status = 'ativo'"
        );

        return [
            'success' => true,
            'message' => "Estatísticas do sistema carregadas",
            'data' => $stats,
        ];
    }

    // ----- SSH -----
    private function action_executar_comando_ssh($params) {
        // Apenas admin/tecnico pode executar comandos SSH
        if (!in_array($this->userInfo['tipo'], ['admin', 'tecnico'])) {
            return ['success' => false, 'error' => 'Sem permissão para executar comandos SSH'];
        }

        require_once __DIR__ . '/SSH.php';
        $model = new SSH();

        $servidorId = (int)$params['servidor_id'];
        $servidor = $model->findById($servidorId);
        if (!$servidor) return ['success' => false, 'error' => "Servidor #{$servidorId} não encontrado"];

        $result = $model->executarComando($servidorId, $params['comando'], 30);

        $status = $result['exit_code'] === 0 ? '✅' : '❌';
        return [
            'success' => true,
            'message' => "{$status} Comando executado em **{$servidor['nome']}** (exit: {$result['exit_code']})",
            'data' => [
                'output' => mb_substr($result['output'] ?? '', 0, 3000),
                'error' => $result['error'] ?? '',
                'exit_code' => $result['exit_code'],
            ],
        ];
    }

    private function action_listar_servidores($params) {
        require_once __DIR__ . '/SSH.php';
        $model = new SSH();

        $servidores = $model->listar();
        $resumo = array_map(function($s) {
            return [
                'id' => $s['id'],
                'nome' => $s['nome'],
                'host' => $s['host'],
                'porta' => $s['porta'],
                'usuario' => $s['usuario'],
                'grupo' => $s['grupo'] ?? '-',
                'status' => $s['status'] ?? 'desconhecido',
            ];
        }, $servidores);

        return [
            'success' => true,
            'message' => count($resumo) . " servidor(es) cadastrado(s)",
            'data' => $resumo,
        ];
    }
}
