<?php
/**
 * API: E-mail
 * Gerenciamento de contas IMAP/SMTP, leitura e envio de e-mails
 */
session_start();
require_once __DIR__ . '/../config/app.php';

// Capturar qualquer saída indesejada (warnings IMAP, notices PHP, etc.)
ob_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/Email.php';

/**
 * Resposta JSON limpa - descarta qualquer output PHP/IMAP residual
 */
function emailJsonResponse($data, $statusCode = 200) {
    // Descartar warnings/notices capturados pelo buffer
    while (ob_get_level()) ob_end_clean();
    // Limpar fila de erros/alertas do IMAP
    if (function_exists('imap_errors'))  @imap_errors();
    if (function_exists('imap_alerts'))  @imap_alerts();
    jsonResponse($data, $statusCode);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['usuario_id'];
$email = new Email();

try {
    // ==========================================
    //  POST
    // ==========================================
    if ($method === 'POST') {
        $jsonBody = file_get_contents('php://input');
        $data = $jsonBody ? json_decode($jsonBody, true) : $_POST;
        if (empty($data)) $data = $_POST;
        $action = $data['action'] ?? '';

        switch ($action) {

            // ===== CONTAS =====
            case 'salvar_conta':
                if (empty($data['email']) || empty($data['imap_host']) || empty($data['smtp_host']) || empty($data['usuario_email'])) {
                    emailJsonResponse(['error' => 'Preencha todos os campos obrigatórios'], 400);
                }
                // Se é nova conta, senha é obrigatória
                if (empty($data['id']) && empty($data['senha_email'])) {
                    emailJsonResponse(['error' => 'Senha é obrigatória para nova conta'], 400);
                }
                $contaId = $email->salvarConta($data, $userId);
                emailJsonResponse(['success' => true, 'conta_id' => $contaId, 'message' => 'Conta salva com sucesso!']);
                break;

            case 'excluir_conta':
                if (empty($data['conta_id'])) emailJsonResponse(['error' => 'ID da conta é obrigatório'], 400);
                $email->excluirConta($data['conta_id'], $userId);
                emailJsonResponse(['success' => true, 'message' => 'Conta excluída']);
                break;

            case 'testar_conexao':
                if (empty($data['imap_host']) || empty($data['usuario_email'])) {
                    emailJsonResponse(['error' => 'Preencha host e usuário'], 400);
                }
                // Se não tem senha, buscar da conta existente
                if (empty($data['senha_email']) && !empty($data['id'])) {
                    $data['usuario_id'] = $userId;
                }
                $result = $email->testarConexao($data);
                emailJsonResponse(['success' => true, 'data' => $result]);
                break;

            case 'autodiscover':
                if (empty($data['email'])) {
                    emailJsonResponse(['error' => 'Endereço de e-mail é obrigatório'], 400);
                }
                $result = $email->autodiscover($data['email']);
                emailJsonResponse(['success' => true, 'data' => $result]);
                break;

            // ===== AÇÕES =====
            case 'marcar_lido':
                if (empty($data['conta_id']) || !isset($data['uid'])) {
                    emailJsonResponse(['error' => 'Parâmetros inválidos'], 400);
                }
                $email->marcarLido($data['conta_id'], $userId, $data['uid'], (bool)($data['lido'] ?? true), $data['folder'] ?? 'INBOX');
                emailJsonResponse(['success' => true]);
                break;

            case 'marcar_importante':
                if (empty($data['conta_id']) || !isset($data['uid'])) {
                    emailJsonResponse(['error' => 'Parâmetros inválidos'], 400);
                }
                $email->marcarImportante($data['conta_id'], $userId, $data['uid'], (bool)($data['flagged'] ?? true), $data['folder'] ?? 'INBOX');
                emailJsonResponse(['success' => true]);
                break;

            case 'mover_email':
                if (empty($data['conta_id']) || !isset($data['uid']) || empty($data['dest_folder'])) {
                    emailJsonResponse(['error' => 'Parâmetros inválidos'], 400);
                }
                $email->moverEmail($data['conta_id'], $userId, $data['uid'], $data['dest_folder'], $data['folder'] ?? 'INBOX');
                emailJsonResponse(['success' => true, 'message' => 'E-mail movido']);
                break;

            case 'excluir_email':
                if (empty($data['conta_id']) || !isset($data['uid'])) {
                    emailJsonResponse(['error' => 'Parâmetros inválidos'], 400);
                }
                $email->excluirEmail($data['conta_id'], $userId, $data['uid'], $data['folder'] ?? 'INBOX');
                emailJsonResponse(['success' => true, 'message' => 'E-mail excluído']);
                break;

            // ===== ENVIAR =====
            case 'enviar_email':
                if (empty($data['conta_id']) || empty($data['to']) || empty($data['subject'])) {
                    emailJsonResponse(['error' => 'Destinatário e assunto são obrigatórios'], 400);
                }
                $email->enviarEmail($data['conta_id'], $userId, $data);
                emailJsonResponse(['success' => true, 'message' => 'E-mail enviado com sucesso!']);
                break;

            // ===== IA RESUMO (SSE STREAMING) =====
            case 'ia_resumo':
                if (empty($data['conta_id']) || !isset($data['uid'])) {
                    emailJsonResponse(['error' => 'Parâmetros inválidos'], 400);
                }

                // Buscar e-mail completo
                $emailData = $email->lerEmail($data['conta_id'], $userId, $data['uid'], $data['folder'] ?? 'INBOX');

                // Preparar conteúdo para IA
                $bodyText = !empty($emailData['body_text']) ? $emailData['body_text'] :
                            strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $emailData['body_html']));
                $bodyText = html_entity_decode($bodyText, ENT_QUOTES, 'UTF-8');
                $bodyText = preg_replace('/\n{3,}/', "\n\n", trim($bodyText));

                // Limitar conteúdo a ~4000 chars para não sobrecarregar
                if (mb_strlen($bodyText) > 4000) {
                    $bodyText = mb_substr($bodyText, 0, 4000) . "\n\n[...conteúdo truncado...]";
                }

                $prompt = "Analise o seguinte e-mail e forneça:\n\n" .
                          "1. **Resumo**: Um resumo conciso do conteúdo\n" .
                          "2. **Ações Necessárias**: Liste as ações que o destinatário precisa tomar (se houver)\n" .
                          "3. **Prioridade**: Classifique como Alta, Média ou Baixa\n" .
                          "4. **Tipo**: Categorize (Ex: Solicitação, Informativo, Cobrança, Reunião, etc.)\n\n" .
                          "---\n" .
                          "**De:** {$emailData['from']} <{$emailData['from_email']}>\n" .
                          "**Para:** {$emailData['to']}\n" .
                          "**Assunto:** {$emailData['subject']}\n" .
                          "**Data:** {$emailData['date']}\n\n" .
                          "**Conteúdo:**\n{$bodyText}\n---\n\n" .
                          "Responda em português brasileiro de forma clara e objetiva.";

                // Carregar modelo IA
                require_once __DIR__ . '/../app/models/IA.php';
                $ia = new IA();

                if (!$ia->isHabilitado()) {
                    emailJsonResponse(['error' => 'IA está desabilitada'], 403);
                }

                // Limpar buffer antes de SSE
                while (ob_get_level()) ob_end_clean();
                // Limpar erros IMAP acumulados
                if (function_exists('imap_errors'))  @imap_errors();
                if (function_exists('imap_alerts'))  @imap_alerts();

                // Configurar SSE
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');
                session_write_close();

                set_time_limit(0);
                ini_set('max_execution_time', '0');
                @ini_set('output_buffering', 'off');
                @ini_set('zlib.output_compression', false);
                if (function_exists('apache_setenv')) {
                    @apache_setenv('no-gzip', '1');
                }

                $messages = [
                    ['role' => 'system', 'content' => 'Você é um assistente especializado em analisar e-mails corporativos. Seja conciso, objetivo e identifique claramente as ações necessárias. Responda sempre em português brasileiro. Use formatação Markdown para organizar sua resposta.'],
                    ['role' => 'user', 'content' => $prompt],
                ];

                // Usar modelo configurado centralmente para e-mails
                $modeloEscolhido = trim($data['modelo'] ?? '') ?: $ia->getConfig('modelo_email', null);

                $lastHeartbeat = microtime(true);

                // Helper para flush seguro
                $sseFlush = function() {
                    if (ob_get_level() > 0) @ob_flush();
                    @flush();
                };

                // Evento inicial
                echo "data: " . json_encode(['status' => 'connected']) . "\n\n";
                $sseFlush();

                try {
                    $ia->chatStream($messages, function($chunk) use ($sseFlush, &$lastHeartbeat) {
                        // Heartbeat durante carregamento do modelo
                        if (!empty($chunk['heartbeat'])) {
                            $now = microtime(true);
                            if ($now - $lastHeartbeat >= 2) {
                                echo "data: " . json_encode(['loading' => true, 'status' => 'loading_model']) . "\n\n";
                                $sseFlush();
                                $lastHeartbeat = $now;
                            }
                            return;
                        }
                        if (isset($chunk['message']['content'])) {
                            echo "data: " . json_encode(['content' => $chunk['message']['content'], 'done' => false]) . "\n\n";
                            $sseFlush();
                        }
                    }, $modeloEscolhido);

                    echo "data: " . json_encode(['done' => true]) . "\n\n";
                    $sseFlush();

                } catch (\Exception $e) {
                    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                    $sseFlush();
                }
                exit;

            default:
                emailJsonResponse(['error' => 'Ação inválida: ' . $action], 400);
        }
    }

    // ==========================================
    //  GET
    // ==========================================
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {

            case 'listar_contas':
                $contas = $email->listarContas($userId);
                emailJsonResponse(['success' => true, 'data' => $contas]);
                break;

            case 'get_conta':
                if (empty($_GET['conta_id'])) emailJsonResponse(['error' => 'ID obrigatório'], 400);
                $conta = $email->getConta($_GET['conta_id'], $userId);
                if (!$conta) emailJsonResponse(['error' => 'Conta não encontrada'], 404);
                // Não retornar senha
                unset($conta['senha_email']);
                emailJsonResponse(['success' => true, 'data' => $conta]);
                break;

            case 'listar_pastas':
                if (empty($_GET['conta_id'])) emailJsonResponse(['error' => 'ID obrigatório'], 400);
                $pastas = $email->listarPastas($_GET['conta_id'], $userId);
                emailJsonResponse(['success' => true, 'data' => $pastas]);
                break;

            case 'listar_emails':
                if (empty($_GET['conta_id'])) emailJsonResponse(['error' => 'ID obrigatório'], 400);
                $result = $email->listarEmails(
                    $_GET['conta_id'],
                    $userId,
                    $_GET['folder'] ?? 'INBOX',
                    (int)($_GET['page'] ?? 1),
                    (int)($_GET['per_page'] ?? 25),
                    $_GET['busca'] ?? ''
                );
                emailJsonResponse(['success' => true, 'data' => $result]);
                break;

            case 'ler_email':
                if (empty($_GET['conta_id']) || !isset($_GET['uid'])) {
                    emailJsonResponse(['error' => 'Parâmetros inválidos'], 400);
                }
                $emailData = $email->lerEmail($_GET['conta_id'], $userId, $_GET['uid'], $_GET['folder'] ?? 'INBOX');
                emailJsonResponse(['success' => true, 'data' => $emailData]);
                break;

            case 'contar_nao_lidos':
                if (empty($_GET['conta_id'])) emailJsonResponse(['error' => 'ID obrigatório'], 400);
                $count = $email->contarNaoLidos($_GET['conta_id'], $userId, $_GET['folder'] ?? 'INBOX');
                emailJsonResponse(['success' => true, 'data' => ['count' => $count]]);
                break;

            case 'baixar_anexo':
                if (empty($_GET['conta_id']) || !isset($_GET['uid']) || !isset($_GET['part'])) {
                    emailJsonResponse(['error' => 'Parâmetros inválidos'], 400);
                }
                $anexo = $email->baixarAnexo(
                    $_GET['conta_id'], $userId,
                    $_GET['uid'], $_GET['part'],
                    $_GET['folder'] ?? 'INBOX'
                );
                // Enviar como download
                while (ob_get_level()) ob_end_clean();
                header('Content-Type: ' . $anexo['mime']);
                header('Content-Disposition: attachment; filename="' . $anexo['filename'] . '"');
                header('Content-Length: ' . strlen($anexo['content']));
                echo $anexo['content'];
                exit;

            default:
                emailJsonResponse(['error' => 'Ação inválida: ' . $action], 400);
        }
    }

} catch (\Exception $e) {
    emailJsonResponse(['error' => $e->getMessage()], 500);
}
