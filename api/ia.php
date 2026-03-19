<?php
/**
 * API: Inteligência Artificial
 * Integração com Ollama (Llama 3) - Chat, Análise, Assistência
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/IA.php';
require_once __DIR__ . '/../app/models/IAActions.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['usuario_id'];
$ia = new IA();

try {
    if ($method === 'POST') {
        $jsonBody = file_get_contents('php://input');
        $data = $jsonBody ? json_decode($jsonBody, true) : $_POST;
        if (empty($data)) $data = $_POST;
        $action = $data['action'] ?? '';

        switch ($action) {
            // ===== CHAT =====
            case 'enviar':
                if (!$ia->isHabilitado()) {
                    jsonResponse(['error' => 'IA está desabilitada'], 403);
                }
                if (empty(trim($data['mensagem'] ?? ''))) {
                    jsonResponse(['error' => 'Mensagem é obrigatória'], 400);
                }

                $mensagem = trim($data['mensagem']);
                $conversaId = $data['conversa_id'] ?? null;
                $contexto = $data['contexto'] ?? 'geral';
                $contextoExtra = $data['contexto_extra'] ?? '';

                // Criar conversa se não existir
                if (!$conversaId) {
                    $conversaId = $ia->criarConversa($userId, 'Nova Conversa', $contexto, $data['contexto_id'] ?? null);
                }

                $resposta = $ia->enviarMensagem($conversaId, $userId, $mensagem, $contextoExtra);

                jsonResponse([
                    'success' => true,
                    'data' => [
                        'conversa_id' => (int)$conversaId,
                        'content' => $resposta['content'],
                        'tokens' => $resposta['tokens'],
                        'duracao_ms' => $resposta['duracao_ms'],
                        'model' => $resposta['model'],
                    ]
                ]);
                break;

            // ===== CHAT STREAMING (SSE) =====
            case 'enviar_stream':
                if (!$ia->isHabilitado()) {
                    jsonResponse(['error' => 'IA está desabilitada'], 403);
                }
                if (empty(trim($data['mensagem'] ?? ''))) {
                    jsonResponse(['error' => 'Mensagem é obrigatória'], 400);
                }

                $mensagem = trim($data['mensagem']);
                $conversaId = $data['conversa_id'] ?? null;
                $contexto = $data['contexto'] ?? 'geral';
                $modeloEscolhido = trim($data['modelo'] ?? '') ?: null;

                if (!$conversaId) {
                    $conversaId = $ia->criarConversa($userId, 'Nova Conversa', $contexto, null, $modeloEscolhido);
                }

                // Salvar mensagem do usuário
                $ia->salvarMensagem($conversaId, 'user', $mensagem);

                // Configurar SSE
                header('Content-Type: text/event-stream; charset=utf-8');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');
                header('Content-Encoding: none');
                header('Pragma: no-cache');
                header('Expires: 0');
                session_write_close();

                // Garantir que PHP não morra durante execução longa
                set_time_limit(0);
                ini_set('max_execution_time', '0');
                @ini_set('output_buffering', 'off');
                @ini_set('zlib.output_compression', false);
                @ini_set('display_errors', '0');
                error_reporting(0);
                if (function_exists('apache_setenv')) {
                    @apache_setenv('no-gzip', '1');
                }
                while (ob_get_level()) ob_end_clean();

                $fullContent = '';
                $totalTokens = 0;
                $start = microtime(true);
                $lastHeartbeat = $start;

                // Montar contexto
                $mensagens = $ia->getMensagens($conversaId);
                $contexto = $data['contexto'] ?? 'geral';
                $systemPromptKey = 'system_prompt' . ($contexto !== 'geral' ? '_' . $contexto : '');
                $systemPrompt = $ia->getConfig($systemPromptKey, $ia->getConfig('system_prompt'));

                // ============================================================
                // ROTEAMENTO INTELIGENTE DE MODELOS
                // ============================================================
                $needsTools = preg_match('/\b(cri[aeo]|crie|fa[çz]a|gere|list[aeo]|mostr[aeo]|abr[aeiou]|fech[aeo]|atualiz|exclui|delet|edit[aeo]|tarefa|chamado|ticket|projeto|sprint|inventar|compra|conhecimento|rede|ssh|servidor|equipamento|patrimoni|artigo|base de conhecimento|monitor|ping|backup|status|estat[ií]stica)\b/iu', $mensagem);

                // Detectar tarefa complexa (projeto completo com sprints+tarefas)
                $isComplex = $needsTools && (
                    preg_match('/\b(fa[çz]a tudo|crie tudo|completo|todas|todos)\b/iu', $mensagem)
                    || preg_match_all('/\b(projeto|tarefa|sprint)s?\b/iu', $mensagem) >= 2
                    // "crie/criar/monte/faça/gere/estruture um projeto" = sempre complexo
                    || preg_match('/\b(cri[ae]r?|fa[çz](?:a|er)|gere|mont[ae]r?|estrutur[ae]r?|inici[ae]r?|organiz[ae]r?|planej[ae]r?|quero|preciso)\b.{0,30}\bprojeto\b/iu', $mensagem)
                );

                // Modelo para usar
                $modeloParaUsar = $modeloEscolhido ?: $ia->getConfig('modelo_padrao', 'phi3:mini');
                $maxTokens = 512;

                if ($needsTools && !$isComplex) {
                    $systemPrompt .= "\n\n" . IAActions::getToolsPrompt();
                    $maxTokens = 1024;
                }

                // Helper para flush seguro
                $sseFlush = function() {
                    if (ob_get_level() > 0) @ob_flush();
                    @flush();
                };

                // Enviar evento inicial
                $modelInfo = $ia->getModelMeta($modeloParaUsar);
                echo "data: " . json_encode([
                    'status' => 'connected',
                    'model' => $modeloParaUsar,
                    'model_label' => $modelInfo['label'] ?? $modeloParaUsar,
                    'is_complex' => $isComplex,
                    'needs_tools' => (bool)$needsTools,
                ]) . "\n\n";
                $sseFlush();

                try {
                    // ============================================================
                    // MODO 1: TAREFA COMPLEXA (2 fases - planner + executor)
                    // Modelo gera lista de tarefas (streaming com heartbeat) → PHP executa tudo
                    // ============================================================
                    if ($isComplex) {
                        // Fase 1: Solicitar plano ao modelo maior (melhor em seguir instruções)
                        $plannerModel = $ia->getConfig('modelo_analise', 'llama3:latest');
                        $plannerInfo = $ia->getModelMeta($plannerModel);
                        $plannerLabel = $plannerInfo['label'] ?? $plannerModel;

                        echo "data: " . json_encode(['content' => "🧠 **Planejando projeto com {$plannerLabel}...**\n\n", 'done' => false]) . "\n\n";
                        $sseFlush();
                        $fullContent = "🧠 **Planejando projeto com {$plannerLabel}...**\n\n";

                        $planMessages = [
                            ['role' => 'system', 'content' => IAActions::getPlannerPrompt()],
                            ['role' => 'user', 'content' => $mensagem],
                        ];

                        // Usar chatStream com acumulação (mantém heartbeat durante loading)
                        $planText = '';
                        $totalTokens = 0;
                        $lastHeartbeat = time();

                        $ia->chatStream($planMessages, function($chunk) use ($sseFlush, &$planText, &$totalTokens, &$lastHeartbeat) {
                            if (isset($chunk['heartbeat'])) {
                                $now = time();
                                if ($now - $lastHeartbeat >= 2) {
                                    echo "data: " . json_encode(['loading' => true, 'status' => 'planning']) . "\n\n";
                                    $sseFlush();
                                    $lastHeartbeat = $now;
                                }
                                return;
                            }
                            if (isset($chunk['message']['content'])) {
                                $planText .= $chunk['message']['content'];
                            }
                            if (isset($chunk['done']) && $chunk['done']) {
                                $totalTokens = ($chunk['prompt_eval_count'] ?? 0) + ($chunk['eval_count'] ?? 0);
                            }
                        }, $plannerModel, [
                            'max_tokens' => 200,
                            'temperature' => 0.3,
                            'num_ctx' => 2048,
                            'repeat_penalty' => 1.5,
                        ]);

                        // Parsear plano (formato texto simples)
                        $plan = IAActions::parsePlanText($planText);

                        // Fallback: tentar JSON se o formato texto falhou
                        if (!$plan) {
                            $cleanText = preg_replace('/```(?:json)?\s*/i', '', $planText);
                            if (preg_match('/\{[\s\S]*\}/u', $cleanText, $jsonMatch)) {
                                $plan = json_decode($jsonMatch[0], true);
                            }
                        }

                        if (!$plan || empty($plan['projeto'])) {
                            // Plano inválido - informar o usuário
                            $fullContent .= "❌ Não consegui gerar o plano. Tente reformular o pedido.\n";
                            $fullContent .= "\nResposta bruta da IA:\n> " . str_replace("\n", "\n> ", $planText);
                            echo "data: " . json_encode(['content' => "❌ Não consegui gerar o plano. Tente reformular o pedido.\n", 'done' => false]) . "\n\n";
                            $sseFlush();
                        } else {
                            // Fase 2: Executar o plano
                            $nSprints = count($plan['sprints'] ?? []);
                            $nTarefas = count($plan['tarefas'] ?? []);
                            $totalSteps = 1 + $nSprints + $nTarefas; // projeto + sprints + tarefas

                            $planSummary = "📋 **Plano:** 1 projeto, {$nSprints} sprint(s), {$nTarefas} tarefa(s)\n\n";
                            echo "data: " . json_encode(['content' => $planSummary, 'done' => false]) . "\n\n";
                            $sseFlush();
                            $fullContent .= $planSummary;

                            // Criar lista de ações para o painel de execução
                            $actionsList = [['name' => 'criar_projeto', 'label' => 'Criar projeto: ' . ($plan['projeto']['nome'] ?? '')]];
                            foreach ($plan['sprints'] ?? [] as $s) {
                                $actionsList[] = ['name' => 'criar_sprint', 'label' => 'Sprint: ' . ($s['nome'] ?? '')];
                            }
                            foreach ($plan['tarefas'] ?? [] as $t) {
                                $actionsList[] = ['name' => 'criar_tarefa', 'label' => 'Tarefa: ' . ($t['titulo'] ?? '')];
                            }

                            echo "data: " . json_encode([
                                'actions_phase' => true,
                                'total' => count($actionsList),
                                'actions_list' => $actionsList,
                            ]) . "\n\n";
                            $sseFlush();

                            // Executar plano com progress callback
                            $stepIndex = 0;
                            $actionsEngine = new IAActions($userId);
                            $planResult = $actionsEngine->executePlan($plan, function($action, $status, $num, $msg) use ($sseFlush, &$stepIndex) {
                                if ($status === 'executing') {
                                    echo "data: " . json_encode([
                                        'action_executing' => true,
                                        'index' => $stepIndex,
                                        'name' => $action,
                                        'label' => ucfirst(str_replace('_', ' ', $action)) . ': ' . $msg,
                                    ]) . "\n\n";
                                    $sseFlush();
                                } elseif ($status === 'done' || $status === 'error') {
                                    echo "data: " . json_encode([
                                        'action_done' => true,
                                        'index' => $stepIndex,
                                        'name' => $action,
                                        'label' => ucfirst(str_replace('_', ' ', $action)),
                                        'success' => $status === 'done',
                                        'message' => $msg,
                                    ]) . "\n\n";
                                    $sseFlush();
                                    $stepIndex++;
                                }
                            });

                            // Enviar resumo como conteúdo
                            $resumo = $planResult['message'] ?? 'Execução concluída.';
                            echo "data: " . json_encode(['content' => "\n\n" . $resumo, 'done' => false]) . "\n\n";
                            $sseFlush();
                            $fullContent .= "\n\n" . $resumo;
                        }

                        $duracao = round((microtime(true) - $start) * 1000);

                        // Salvar mensagem completa
                        $ia->salvarMensagem($conversaId, 'assistant', $fullContent, $totalTokens, $duracao);

                        echo "data: " . json_encode([
                            'done' => true,
                            'conversa_id' => (int)$conversaId,
                            'tokens' => $totalTokens,
                            'duracao_ms' => $duracao,
                            'final_content' => $fullContent,
                        ]) . "\n\n";
                        $sseFlush();

                    // ============================================================
                    // MODO 2: CHAT NORMAL / AÇÕES SIMPLES (streaming)
                    // ============================================================
                    } else {
                        $messages = [
                            ['role' => 'system', 'content' => $systemPrompt],
                        ];
                        $recentMsgs = array_slice($mensagens, -6);
                        foreach ($recentMsgs as $m) {
                            $messages[] = ['role' => $m['role'], 'content' => $m['conteudo']];
                        }

                        $ia->chatStream($messages, function($chunk) use (&$fullContent, &$totalTokens, &$lastHeartbeat, $sseFlush, $modeloParaUsar) {
                            if (!empty($chunk['heartbeat'])) {
                                $now = microtime(true);
                                if ($now - $lastHeartbeat >= 2) {
                                    echo "data: " . json_encode(['loading' => true, 'status' => 'loading_model', 'model' => $modeloParaUsar]) . "\n\n";
                                    $sseFlush();
                                    $lastHeartbeat = $now;
                                }
                                return;
                            }
                            if (isset($chunk['message']['content'])) {
                                $content = $chunk['message']['content'];
                                $fullContent .= $content;
                                echo "data: " . json_encode(['content' => $content, 'done' => false]) . "\n\n";
                                $sseFlush();
                            }
                            if (!empty($chunk['done'])) {
                                $totalTokens = ($chunk['prompt_eval_count'] ?? 0) + ($chunk['eval_count'] ?? 0);
                            }
                        }, $modeloParaUsar, ['max_tokens' => $maxTokens]);

                        $duracao = round((microtime(true) - $start) * 1000);

                        // Processar ações da IA
                        $actionsEngine = new IAActions($userId);
                        $parsedActions = $actionsEngine->parseActions($fullContent);
                        $savedContent = $fullContent;

                        if (!empty($parsedActions)) {
                            // Detectar dependências de IDs entre ações (projeto → sprint → tarefa)
                            // O modelo pode chutar IDs (ex: projeto_id:1) que precisam ser corrigidos
                            $idMap = ['projeto_id' => null, 'sprint_id' => null];

                            $actionsList = array_map(function($a) {
                                return [
                                    'name' => $a['name'],
                                    'label' => ucfirst(str_replace('_', ' ', $a['name'])),
                                ];
                            }, $parsedActions);

                            echo "data: " . json_encode([
                                'actions_phase' => true,
                                'total' => count($parsedActions),
                                'actions_list' => $actionsList,
                            ]) . "\n\n";
                            $sseFlush();

                            $processedText = $fullContent;
                            foreach ($parsedActions as $i => $action) {
                                echo "data: " . json_encode([
                                    'action_executing' => true,
                                    'index' => $i,
                                    'name' => $action['name'],
                                    'label' => ucfirst(str_replace('_', ' ', $action['name'])),
                                ]) . "\n\n";
                                $sseFlush();

                                // Corrigir IDs dependentes (modelo chuta IDs, trocar pelos reais)
                                $params = $action['params'];
                                if ($idMap['projeto_id'] && isset($params['projeto_id'])) {
                                    $params['projeto_id'] = $idMap['projeto_id'];
                                }
                                if ($idMap['sprint_id'] && isset($params['sprint_id'])) {
                                    $params['sprint_id'] = $idMap['sprint_id'];
                                }

                                $result = $actionsEngine->executeAction($action['name'], $params);

                                // Capturar IDs criados para uso nas ações seguintes
                                if ($result['success'] ?? false) {
                                    if ($action['name'] === 'criar_projeto' && isset($result['data']['id'])) {
                                        $idMap['projeto_id'] = (int)$result['data']['id'];
                                    }
                                    if ($action['name'] === 'criar_sprint' && isset($result['data']['id'])) {
                                        $idMap['sprint_id'] = (int)$result['data']['id'];
                                    }
                                }

                                $resultBlock = $actionsEngine->formatActionResult($action['name'], $result);
                                $processedText = str_replace($action['raw'], $resultBlock, $processedText);

                                echo "data: " . json_encode([
                                    'action_done' => true,
                                    'index' => $i,
                                    'name' => $action['name'],
                                    'label' => ucfirst(str_replace('_', ' ', $action['name'])),
                                    'success' => $result['success'] ?? false,
                                    'message' => $result['message'] ?? ($result['error'] ?? ''),
                                    'data' => $result['data'] ?? null,
                                ]) . "\n\n";
                                $sseFlush();
                            }
                            $savedContent = $processedText;
                        }

                        $ia->salvarMensagem($conversaId, 'assistant', $savedContent, $totalTokens, $duracao);

                        echo "data: " . json_encode([
                            'done' => true,
                            'conversa_id' => (int)$conversaId,
                            'tokens' => $totalTokens,
                            'duracao_ms' => $duracao,
                            'final_content' => $savedContent,
                        ]) . "\n\n";
                        $sseFlush();
                    }

                } catch (\Exception $e) {
                    echo "data: " . json_encode(['error' => $e->getMessage(), 'done' => true]) . "\n\n";
                    $sseFlush();
                }
                exit;

            // ===== CONVERSAS =====
            case 'nova_conversa':
                $contexto = $data['contexto'] ?? 'geral';
                $titulo = $data['titulo'] ?? 'Nova Conversa';
                $id = $ia->criarConversa($userId, $titulo, $contexto, $data['contexto_id'] ?? null);
                jsonResponse(['success' => true, 'data' => ['id' => $id]]);
                break;

            case 'renomear':
                if (empty($data['id']) || empty(trim($data['titulo'] ?? ''))) {
                    jsonResponse(['error' => 'ID e título são obrigatórios'], 400);
                }
                $ia->renomearConversa($data['id'], trim($data['titulo']));
                jsonResponse(['success' => true, 'message' => 'Conversa renomeada']);
                break;

            case 'excluir_conversa':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }
                $ia->excluirConversa($data['id']);
                jsonResponse(['success' => true, 'message' => 'Conversa excluída']);
                break;

            // ===== ASSISTENTE SSH =====
            case 'explicar_comando':
                if (empty(trim($data['comando'] ?? ''))) {
                    jsonResponse(['error' => 'Comando é obrigatório'], 400);
                }
                $explicacao = $ia->explicarComando(trim($data['comando']));
                jsonResponse(['success' => true, 'data' => ['explicacao' => $explicacao]]);
                break;

            case 'interpretar_saida':
                $saida = ($data['output'] ?? '') . ($data['error'] ?? '');
                if (empty($saida)) {
                    jsonResponse(['error' => 'Saída é obrigatória'], 400);
                }
                $interpretacao = $ia->interpretarSaida(
                    $data['comando'] ?? '',
                    $saida,
                    (int)($data['exit_code'] ?? 0)
                );
                jsonResponse(['success' => true, 'data' => ['interpretacao' => $interpretacao]]);
                break;

            case 'sugerir_comando':
                if (empty(trim($data['descricao'] ?? ''))) {
                    jsonResponse(['error' => 'Descrição é obrigatória'], 400);
                }
                $sugestao = $ia->sugerirComando(trim($data['descricao']));
                jsonResponse(['success' => true, 'data' => ['sugestao' => $sugestao]]);
                break;

            // ===== ASSISTENTE CHAMADOS =====
            case 'classificar_chamado':
                if (empty($data['titulo']) && empty($data['descricao'])) {
                    jsonResponse(['error' => 'Título ou descrição é obrigatório'], 400);
                }
                $classificacao = $ia->classificarChamado($data['titulo'] ?? '', $data['descricao'] ?? '');
                jsonResponse(['success' => true, 'data' => $classificacao]);
                break;

            case 'sugerir_resposta':
                if (empty($data['chamado_id'])) {
                    jsonResponse(['error' => 'ID do chamado é obrigatório'], 400);
                }
                require_once __DIR__ . '/../app/models/Chamado.php';
                $chamadoModel = new Chamado();
                $chamado = $chamadoModel->findById($data['chamado_id']);
                if (!$chamado) jsonResponse(['error' => 'Chamado não encontrado'], 404);

                $interacoes = $chamadoModel->getComentarios($data['chamado_id']);
                $sugestao = $ia->sugerirResposta($chamado, $interacoes);
                jsonResponse(['success' => true, 'data' => ['sugestao' => $sugestao]]);
                break;

            // ===== PERGUNTA RÁPIDA =====
            case 'pergunta_rapida':
                if (empty(trim($data['pergunta'] ?? ''))) {
                    jsonResponse(['error' => 'Pergunta é obrigatória'], 400);
                }
                $resp = $ia->perguntaRapida(trim($data['pergunta']), $data['contexto'] ?? 'geral');
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'content' => $resp['content'],
                        'tokens' => $resp['tokens'],
                        'duracao_ms' => $resp['duracao_ms'],
                    ]
                ]);
                break;

            // ===== CONFIGURAÇÃO (admin) =====
            case 'salvar_config':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin pode alterar configurações'], 403);
                }
                if (empty($data['configs']) || !is_array($data['configs'])) {
                    jsonResponse(['error' => 'Configurações inválidas'], 400);
                }
                foreach ($data['configs'] as $key => $value) {
                    $ia->setConfig($key, $value);
                }
                jsonResponse(['success' => true, 'message' => 'Configurações salvas!']);
                break;

            // ===== GERENCIAMENTO DE MODELOS =====
            case 'definir_modelo_tarefa':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin'], 403);
                }
                $tarefa = $data['tarefa'] ?? '';
                $modelo = $data['modelo'] ?? '';
                if (!$tarefa || !$modelo) {
                    jsonResponse(['error' => 'Tarefa e modelo são obrigatórios'], 400);
                }
                $configMap = [
                    'chat' => 'modelo_padrao',
                    'rapido' => 'modelo_rapido',
                    'codigo' => 'modelo_codigo',
                    'analise' => 'modelo_analise',
                ];
                if (!isset($configMap[$tarefa])) {
                    jsonResponse(['error' => 'Tarefa inválida'], 400);
                }
                $ia->setConfig($configMap[$tarefa], $modelo);
                jsonResponse(['success' => true, 'message' => "Modelo '$modelo' definido para '$tarefa'"]);
                break;

            case 'pull_modelo':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin'], 403);
                }
                $nome = trim($data['nome'] ?? '');
                if (!$nome) jsonResponse(['error' => 'Nome do modelo é obrigatório'], 400);
                $result = $ia->pullModel($nome);
                jsonResponse($result);
                break;

            case 'deletar_modelo':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin'], 403);
                }
                $nome = trim($data['nome'] ?? '');
                if (!$nome) jsonResponse(['error' => 'Nome do modelo é obrigatório'], 400);
                $result = $ia->deleteModel($nome);
                jsonResponse($result);
                break;

            default:
                jsonResponse(['error' => 'Ação inválida'], 400);
        }

    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'conversas':
                $conversas = $ia->listarConversas($userId, $_GET['contexto'] ?? null);
                jsonResponse(['success' => true, 'data' => $conversas]);
                break;

            case 'mensagens':
                if (empty($_GET['conversa_id'])) {
                    jsonResponse(['error' => 'conversa_id é obrigatório'], 400);
                }
                $conversa = $ia->getConversa($_GET['conversa_id']);
                if (!$conversa || $conversa['usuario_id'] != $userId) {
                    jsonResponse(['error' => 'Conversa não encontrada'], 404);
                }
                $mensagens = $ia->getMensagens($_GET['conversa_id']);
                jsonResponse(['success' => true, 'data' => [
                    'conversa' => $conversa,
                    'mensagens' => $mensagens
                ]]);
                break;

            case 'status':
                $check = $ia->checkConnection();
                $stats = $ia->getEstatisticas($userId);
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'online' => $check['online'],
                        'models' => $check['models'],
                        'url' => $ia->getConfig('ollama_url'),
                        'modelo_padrao' => $ia->getConfig('modelo_padrao'),
                        'habilitado' => $ia->isHabilitado(),
                        'stats' => $stats,
                        'modelos_tarefa' => $ia->getModelosPorTarefa(),
                    ]
                ]);
                break;

            case 'config':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Sem permissão'], 403);
                }
                $config = $ia->getAllConfig();
                jsonResponse(['success' => true, 'data' => $config]);
                break;

            case 'modelos':
                $modelos = $ia->listarModelos();
                jsonResponse(['success' => true, 'data' => $modelos]);
                break;

            case 'modelo_info':
                $nome = $_GET['nome'] ?? '';
                if (!$nome) jsonResponse(['error' => 'Nome obrigatório'], 400);
                $info = $ia->getModelInfo($nome);
                jsonResponse(['success' => true, 'data' => $info]);
                break;

            case 'modelos_tarefa':
                jsonResponse(['success' => true, 'data' => $ia->getModelosPorTarefa()]);
                break;

            default:
                jsonResponse(['error' => 'Ação não especificada'], 400);
        }

    } else {
        jsonResponse(['error' => 'Método não permitido'], 405);
    }

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
