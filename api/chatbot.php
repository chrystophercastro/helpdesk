<?php
/**
 * API: Chatbot
 * 
 * Gerencia configurações, números autorizados, sessões, 
 * integração com IA, N8N e base de dados externa.
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

requireRole(['admin', 'tecnico']);

require_once __DIR__ . '/../app/models/ChatbotModel.php';
$chatbot = new ChatbotModel();
$method = $_SERVER['REQUEST_METHOD'];
$user = currentUser();

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            // ---- STATUS GERAL ----
            case 'status':
                $stats = $chatbot->getStats();
                $ollama = $chatbot->checkOllamaStatus();
                $evolution = $chatbot->checkEvolutionStatus();
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'chatbot_ativo' => $chatbot->isAtivo(),
                        'stats' => $stats,
                        'ollama' => $ollama,
                        'evolution' => $evolution,
                        'n8n_ativo' => $chatbot->getConfig('chatbot_n8n_ativo') === '1',
                        'db_ativo' => $chatbot->getConfig('chatbot_db_ativo') === '1',
                    ],
                ]);
                break;

            // ---- CONFIGURAÇÕES ----
            case 'config':
                jsonResponse(['success' => true, 'data' => $chatbot->getAllConfig()]);
                break;

            // ---- NÚMEROS AUTORIZADOS ----
            case 'numeros':
                jsonResponse(['success' => true, 'data' => $chatbot->listarNumeros()]);
                break;

            // ---- SESSÕES ----
            case 'sessoes':
                $limite = (int) ($_GET['limite'] ?? 50);
                jsonResponse(['success' => true, 'data' => $chatbot->listarSessoes($limite)]);
                break;

            // ---- HISTÓRICO DE SESSÃO ----
            case 'historico':
                $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
                if (!$sessaoId) jsonResponse(['error' => 'sessao_id obrigatório'], 400);
                $session = $chatbot->getSession($sessaoId);
                if (!$session) jsonResponse(['error' => 'Sessão não encontrada'], 404);
                $msgs = $chatbot->getHistorico($sessaoId, (int) ($_GET['limite'] ?? 100));
                jsonResponse(['success' => true, 'data' => ['sessao' => $session, 'mensagens' => $msgs]]);
                break;

            // ---- CHECK OLLAMA ----
            case 'check_ollama':
                jsonResponse(['success' => true, 'data' => $chatbot->checkOllamaStatus()]);
                break;

            // ---- CHECK EVOLUTION ----
            case 'check_evolution':
                jsonResponse(['success' => true, 'data' => $chatbot->checkEvolutionStatus()]);
                break;

            // ---- TESTAR CONEXÃO DB EXTERNO ----
            case 'db_test':
                jsonResponse(['success' => true, 'data' => $chatbot->testarConexaoDB()]);
                break;

            // ---- SCHEMA DO DB EXTERNO ----
            case 'db_schema':
                try {
                    $schema = $chatbot->getExternalDBSchema();
                    jsonResponse(['success' => true, 'data' => $schema]);
                } catch (Exception $e) {
                    jsonResponse(['error' => $e->getMessage()], 400);
                }
                break;

            // ---- RELACIONAMENTOS DO DB ----
            case 'get_relationships':
                $rels = $chatbot->getConfig('chatbot_db_relacionamentos', '[]');
                $parsed = json_decode($rels, true);
                jsonResponse(['success' => true, 'data' => is_array($parsed) ? $parsed : []]);
                break;

            // ---- LOGS N8N ----
            case 'n8n_logs':
                $limite = (int) ($_GET['limite'] ?? 50);
                jsonResponse(['success' => true, 'data' => $chatbot->getN8NLogs($limite)]);
                break;

            // ---- LOGS DO WEBHOOK ----
            case 'logs':
                $limite = (int) ($_GET['limite'] ?? 200);
                $filtro = $_GET['filtro'] ?? 'all';
                jsonResponse(['success' => true, 'data' => $chatbot->getLogs($limite, $filtro)]);
                break;

            // ---- ERROS RECENTES ----
            case 'erros':
                $limite = (int) ($_GET['limite'] ?? 50);
                jsonResponse(['success' => true, 'data' => $chatbot->getErrosRecentes($limite)]);
                break;

            // ---- LOGS DATASYSTEM ----
            case 'datasystem_logs':
                $limite = (int) ($_GET['limite'] ?? 100);
                $filtro = $_GET['filtro'] ?? 'all';
                $logs = $chatbot->getDataSystemLogs($limite, $filtro);
                $stats = $chatbot->getDataSystemLogStats();
                jsonResponse(['success' => true, 'data' => $logs, 'stats' => $stats]);
                break;

            // ---- FONTES DE DADOS (MULTI-FONTE) ----
            case 'fontes':
                $fontes = $chatbot->listarFontesDados();
                jsonResponse(['success' => true, 'data' => $fontes]);
                break;

            case 'fonte':
                $fonteId = (int) ($_GET['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                $fonte = $chatbot->getFonteDados($fonteId);
                if (!$fonte) jsonResponse(['error' => 'Fonte não encontrada'], 404);
                // Não expor senha
                $fonte['db_pass'] = !empty($fonte['db_pass']) ? '********' : '';
                $fonte['api_key'] = !empty($fonte['api_key']) ? '********' : '';
                $fonte['api_auth_pass'] = !empty($fonte['api_auth_pass']) ? '********' : '';
                jsonResponse(['success' => true, 'data' => $fonte]);
                break;

            case 'fonte_schema':
                $fonteId = (int) ($_GET['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                try {
                    $schema = $chatbot->getFonteSchema($fonteId);
                    jsonResponse(['success' => true, 'data' => $schema]);
                } catch (Exception $e) {
                    jsonResponse(['error' => $e->getMessage()], 400);
                }
                break;

            case 'fonte_relacionamentos':
                $fonteId = (int) ($_GET['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                $rels = $chatbot->getFonteRelacionamentos($fonteId);
                jsonResponse(['success' => true, 'data' => $rels]);
                break;

            default:
                jsonResponse(['error' => 'Ação desconhecida: ' . $action], 400);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $data['action'] ?? '';

        switch ($action) {
            // ---- SALVAR CONFIGURAÇÕES ----
            case 'save_config':
                $configs = $data['configs'] ?? [];
                if (empty($configs)) jsonResponse(['error' => 'Nenhuma configuração enviada'], 400);
                foreach ($configs as $key => $value) {
                    $chatbot->setConfig($key, $value);
                }
                jsonResponse(['success' => true, 'message' => 'Configurações salvas com sucesso']);
                break;

            // ---- ADICIONAR NÚMERO ----
            case 'add_numero':
                $numero = $data['numero'] ?? '';
                if (empty($numero)) jsonResponse(['error' => 'Número obrigatório'], 400);
                $chatbot->addNumero($numero, $data['nome'] ?? null, $data['notas'] ?? null);
                jsonResponse(['success' => true, 'message' => 'Número adicionado']);
                break;

            // ---- REMOVER NÚMERO ----
            case 'remove_numero':
                $id = (int) ($data['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
                $chatbot->removeNumero($id);
                jsonResponse(['success' => true, 'message' => 'Número removido']);
                break;

            // ---- TOGGLE NÚMERO ----
            case 'toggle_numero':
                $id = (int) ($data['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID obrigatório'], 400);
                $novoStatus = $chatbot->toggleNumero($id);
                jsonResponse(['success' => true, 'ativo' => $novoStatus]);
                break;

            // ---- TESTAR IA (via painel admin) ----
            case 'test_ia':
                $mensagem = $data['mensagem'] ?? '';
                if (empty($mensagem)) jsonResponse(['error' => 'Mensagem obrigatória'], 400);
                $sessaoId = $data['sessao_id'] ?? null;
                $result = $chatbot->testarIA($mensagem, $sessaoId);
                if (isset($result['error'])) {
                    jsonResponse(['error' => $result['error']], 500);
                }
                jsonResponse(['success' => true, 'data' => $result]);
                break;

            // ---- TESTAR N8N ----
            case 'test_n8n':
                $result = $chatbot->notificarN8N('teste', [
                    'mensagem' => 'Teste de conexão do Oracle X Chatbot',
                    'usuario' => $user['nome'] ?? 'Admin',
                    'timestamp' => date('c'),
                ]);
                if ($result && ($result['http_code'] ?? 0) >= 200 && ($result['http_code'] ?? 0) < 300) {
                    jsonResponse(['success' => true, 'message' => 'Webhook N8N enviado com sucesso', 'data' => $result]);
                } else {
                    jsonResponse(['error' => 'Falha ao enviar webhook N8N', 'data' => $result], 400);
                }
                break;

            // ---- ENVIAR MENSAGEM WHATSAPP (manual do admin) ----
            case 'send_message':
                $numero = $data['numero'] ?? '';
                $mensagem = $data['mensagem'] ?? '';
                if (empty($numero) || empty($mensagem)) {
                    jsonResponse(['error' => 'Número e mensagem obrigatórios'], 400);
                }
                $result = $chatbot->enviarWhatsApp($numero, $mensagem);
                jsonResponse(['success' => true, 'message' => 'Mensagem enviada', 'data' => $result]);
                break;

            // ---- LIMPAR SESSÃO ----
            case 'limpar_sessao':
                $sessaoId = (int) ($data['sessao_id'] ?? 0);
                if (!$sessaoId) jsonResponse(['error' => 'sessao_id obrigatório'], 400);
                $chatbot->limparSessao($sessaoId);
                jsonResponse(['success' => true, 'message' => 'Sessão limpa com sucesso']);
                break;

            // ---- LIMPAR CACHE DO CONTEXTO DB ----
            case 'limpar_cache':
                $chatbot->limparCacheContextoDB();
                jsonResponse(['success' => true, 'message' => 'Cache do contexto de banco de dados limpo']);
                break;

            // ---- LIMPAR LOGS ----
            case 'limpar_logs':
                $chatbot->clearLogs();
                jsonResponse(['success' => true, 'message' => 'Logs limpos']);
                break;

            // ---- LIMPAR LOGS DATASYSTEM ----
            case 'limpar_datasystem_logs':
                $chatbot->clearDataSystemLogs();
                jsonResponse(['success' => true, 'message' => 'Logs DataSystem limpos']);
                break;

            // ---- SALVAR RELACIONAMENTOS DO DB ----
            case 'save_relationships':
                $relationships = $data['relationships'] ?? [];
                $chatbot->setConfig('chatbot_db_relacionamentos', json_encode($relationships, JSON_UNESCAPED_UNICODE));
                $chatbot->limparCacheContextoDB();
                jsonResponse(['success' => true, 'message' => 'Relacionamentos salvos']);
                break;

            // ---- WEBHOOK N8N (recebimento) ----
            case 'n8n_webhook':
                $result = $chatbot->processarWebhookN8N($data);
                jsonResponse(['success' => true, 'data' => $result]);
                break;

            // ---- FONTES DE DADOS: CRIAR ----
            case 'criar_fonte':
                $campos = ['nome', 'alias', 'tipo'];
                foreach ($campos as $c) {
                    if (empty($data[$c])) jsonResponse(['error' => "Campo '{$c}' é obrigatório"], 400);
                }
                try {
                    $fonteData = [
                        'nome' => $data['nome'],
                        'alias' => $data['alias'],
                        'tipo' => $data['tipo'],
                        'ativo' => (int)($data['ativo'] ?? 1),
                        'db_host' => $data['db_host'] ?? null,
                        'db_port' => $data['db_port'] ?? null,
                        'db_name' => $data['db_name'] ?? null,
                        'db_user' => $data['db_user'] ?? null,
                        'db_pass' => $data['db_pass'] ?? null,
                        'descricao' => $data['descricao'] ?? null,
                        'tabelas_permitidas' => $data['tabelas_permitidas'] ?? null,
                        'max_rows' => (int)($data['max_rows'] ?? 50),
                        'api_url' => $data['api_url'] ?? null,
                        'api_auth_tipo' => $data['api_auth_tipo'] ?? 'none',
                        'api_key' => $data['api_key'] ?? null,
                        'api_auth_header' => $data['api_auth_header'] ?? 'Authorization',
                        'api_auth_user' => $data['api_auth_user'] ?? null,
                        'api_auth_pass' => $data['api_auth_pass'] ?? null,
                        'api_endpoints' => $data['api_endpoints'] ?? null,
                        'api_descricao' => $data['api_descricao'] ?? null,
                        'api_template' => $data['api_template'] ?? null,
                    ];
                    // Tratar endpoints JSON string
                    if (is_string($fonteData['api_endpoints'])) {
                        $parsed = json_decode($fonteData['api_endpoints'], true);
                        if (is_array($parsed)) $fonteData['api_endpoints'] = json_encode($parsed, JSON_UNESCAPED_UNICODE);
                    }
                    $id = $chatbot->criarFonteDados($fonteData);
                    jsonResponse(['success' => true, 'message' => 'Fonte criada', 'id' => $id]);
                } catch (Exception $e) {
                    jsonResponse(['error' => $e->getMessage()], 400);
                }
                break;

            // ---- FONTES DE DADOS: ATUALIZAR ----
            case 'atualizar_fonte':
                $fonteId = (int)($data['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                try {
                    $fonteData = [];
                    $camposPermitidos = ['nome','alias','tipo','ativo','db_host','db_port','db_name','db_user','db_pass',
                        'descricao','tabelas_permitidas','max_rows','api_url','api_auth_tipo','api_key',
                        'api_auth_header','api_auth_user','api_auth_pass','api_endpoints','api_descricao','api_template','ordem'];
                    foreach ($camposPermitidos as $c) {
                        if (array_key_exists($c, $data)) {
                            // Não sobrescrever senha se vier mascarada
                            if (in_array($c, ['db_pass','api_key','api_auth_pass']) && $data[$c] === '********') continue;
                            $fonteData[$c] = $data[$c];
                        }
                    }
                    if (isset($fonteData['max_rows'])) $fonteData['max_rows'] = (int)$fonteData['max_rows'];
                    if (isset($fonteData['ativo'])) $fonteData['ativo'] = (int)$fonteData['ativo'];
                    if (isset($fonteData['api_endpoints']) && is_string($fonteData['api_endpoints'])) {
                        $parsed = json_decode($fonteData['api_endpoints'], true);
                        if (is_array($parsed)) $fonteData['api_endpoints'] = json_encode($parsed, JSON_UNESCAPED_UNICODE);
                    }
                    $chatbot->atualizarFonteDados($fonteId, $fonteData);
                    jsonResponse(['success' => true, 'message' => 'Fonte atualizada']);
                } catch (Exception $e) {
                    jsonResponse(['error' => $e->getMessage()], 400);
                }
                break;

            // ---- FONTES DE DADOS: REMOVER ----
            case 'remover_fonte':
                $fonteId = (int)($data['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                $chatbot->removerFonteDados($fonteId);
                jsonResponse(['success' => true, 'message' => 'Fonte removida']);
                break;

            // ---- FONTES DE DADOS: TOGGLE ----
            case 'toggle_fonte':
                $fonteId = (int)($data['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                $novoStatus = $chatbot->toggleFonteDados($fonteId);
                jsonResponse(['success' => true, 'ativo' => $novoStatus]);
                break;

            // ---- FONTES DE DADOS: TESTAR CONEXÃO ----
            case 'testar_fonte':
                $fonteId = (int)($data['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                $resultado = $chatbot->testarFonteDadosConexao($fonteId);
                jsonResponse(['success' => true, 'data' => $resultado]);
                break;

            // ---- FONTES DE DADOS: SALVAR RELACIONAMENTOS ----
            case 'salvar_fonte_relacionamentos':
                $fonteId = (int)($data['id'] ?? 0);
                if (!$fonteId) jsonResponse(['error' => 'ID obrigatório'], 400);
                $rels = $data['relacionamentos'] ?? [];
                $chatbot->salvarFonteRelacionamentos($fonteId, $rels);
                jsonResponse(['success' => true, 'message' => 'Relacionamentos salvos']);
                break;

            default:
                jsonResponse(['error' => 'Ação inválida: ' . $action], 400);
        }

    } else {
        jsonResponse(['error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}