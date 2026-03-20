<?php
/**
 * API: Chat Interno
 * Endpoints para conversas, mensagens, presença, reações
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/Chat.php';
$chat = new Chat();
$usuarioId = $_SESSION['usuario_id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? $action;
}

try {

if ($method === 'GET') {
    switch ($action) {

        // Listar conversas do usuário
        case 'conversas':
            $conversas = $chat->listarConversas($usuarioId);
            // Para conversas diretas, preencher nome do outro usuário
            foreach ($conversas as &$c) {
                if ($c['tipo'] === 'direta') {
                    $outro = $chat->getInfoConversaDireta($c['id'], $usuarioId);
                    $c['nome'] = $outro['nome'] ?? 'Usuário';
                    $c['outro_usuario'] = $outro;
                }
            }
            jsonResponse(['success' => true, 'data' => $conversas]);
            break;

        // Mensagens de uma conversa
        case 'mensagens':
            $conversaId = (int)($_GET['conversa_id'] ?? 0);
            if (!$conversaId) jsonResponse(['error' => 'conversa_id obrigatório'], 400);
            
            // Verificar acesso
            $conv = $chat->getConversa($conversaId, $usuarioId);
            if (!$conv) jsonResponse(['error' => 'Sem acesso a esta conversa'], 403);

            $antesDeId = !empty($_GET['antes_de']) ? (int)$_GET['antes_de'] : null;
            $limite = (int)($_GET['limite'] ?? 50);
            $msgs = $chat->getMensagens($conversaId, $limite, $antesDeId);
            
            // Marcar como lida
            $chat->marcarLida($conversaId, $usuarioId);

            jsonResponse(['success' => true, 'data' => $msgs]);
            break;

        // Mensagens novas (polling)
        case 'novas':
            $conversaId = (int)($_GET['conversa_id'] ?? 0);
            $depoisDeId = (int)($_GET['depois_de'] ?? 0);
            if (!$conversaId) jsonResponse(['error' => 'conversa_id obrigatório'], 400);

            $conv = $chat->getConversa($conversaId, $usuarioId);
            if (!$conv) jsonResponse(['error' => 'Sem acesso'], 403);

            $novas = $chat->getMensagensNovas($conversaId, $depoisDeId);
            if (!empty($novas)) {
                $chat->marcarLida($conversaId, $usuarioId);
            }

            // Quem está digitando
            $participantes = $chat->getParticipantes($conversaId);
            $digitando = [];
            foreach ($participantes as $p) {
                if ($p['id'] != $usuarioId && $p['digitando_em'] == $conversaId) {
                    $digitando[] = $p['nome'];
                }
            }

            jsonResponse(['success' => true, 'data' => [
                'mensagens' => $novas,
                'digitando' => $digitando
            ]]);
            break;

        // Participantes de uma conversa
        case 'participantes':
            $conversaId = (int)($_GET['conversa_id'] ?? 0);
            if (!$conversaId) jsonResponse(['error' => 'conversa_id obrigatório'], 400);
            $participantes = $chat->getParticipantes($conversaId);
            jsonResponse(['success' => true, 'data' => $participantes]);
            break;

        // Usuários disponíveis para nova conversa
        case 'usuarios':
            $usuarios = $chat->listarUsuariosDisponiveis($usuarioId);
            jsonResponse(['success' => true, 'data' => $usuarios]);
            break;

        // Contar total de não lidas (para badge)
        case 'nao_lidas':
            $total = $chat->contarNaoLidasTotal($usuarioId);
            jsonResponse(['success' => true, 'data' => ['total' => $total]]);
            break;

        // Buscar mensagens
        case 'buscar':
            $termo = $_GET['q'] ?? '';
            if (strlen($termo) < 2) jsonResponse(['error' => 'Termo muito curto'], 400);
            $resultados = $chat->buscarMensagens($usuarioId, $termo);
            jsonResponse(['success' => true, 'data' => $resultados]);
            break;

        // Heartbeat — atualizar presença
        case 'heartbeat':
            $chat->atualizarPresenca($usuarioId, 'online');
            $chat->marcarOfflineInativos(2);
            $total = $chat->contarNaoLidasTotal($usuarioId);
            jsonResponse(['success' => true, 'data' => ['nao_lidas' => $total]]);
            break;

        default:
            jsonResponse(['error' => 'Ação GET não reconhecida'], 400);
    }

} elseif ($method === 'POST') {
    switch ($action) {

        // Enviar mensagem
        case 'enviar':
            $conversaId = (int)($input['conversa_id'] ?? 0);
            $conteudo = trim($input['conteudo'] ?? '');
            $tipo = $input['tipo'] ?? 'texto';
            $respostaA = !empty($input['resposta_a']) ? (int)$input['resposta_a'] : null;

            if (!$conversaId || empty($conteudo)) {
                jsonResponse(['error' => 'conversa_id e conteudo obrigatórios'], 400);
            }

            $conv = $chat->getConversa($conversaId, $usuarioId);
            if (!$conv) jsonResponse(['error' => 'Sem acesso'], 403);

            $msgId = $chat->enviarMensagem($conversaId, $usuarioId, $conteudo, $tipo, $respostaA);
            $chat->setDigitando($usuarioId, null);
            
            jsonResponse(['success' => true, 'data' => ['id' => $msgId]]);
            break;

        // Iniciar conversa direta
        case 'conversa_direta':
            $outroId = (int)($input['usuario_id'] ?? 0);
            if (!$outroId) jsonResponse(['error' => 'usuario_id obrigatório'], 400);
            $conversaId = $chat->getOuCriarConversaDireta($usuarioId, $outroId);
            jsonResponse(['success' => true, 'data' => ['conversa_id' => $conversaId]]);
            break;

        // Criar grupo
        case 'criar_grupo':
            $nome = trim($input['nome'] ?? '');
            if (empty($nome)) jsonResponse(['error' => 'Nome obrigatório'], 400);
            $conversaId = $chat->criarGrupo($input, $usuarioId);
            jsonResponse(['success' => true, 'data' => ['conversa_id' => $conversaId]]);
            break;

        // Editar mensagem
        case 'editar':
            $msgId = (int)($input['mensagem_id'] ?? 0);
            $conteudo = trim($input['conteudo'] ?? '');
            if (!$msgId || empty($conteudo)) jsonResponse(['error' => 'Dados inválidos'], 400);
            $ok = $chat->editarMensagem($msgId, $usuarioId, $conteudo);
            jsonResponse($ok ? ['success' => true] : ['error' => 'Não autorizado'], $ok ? 200 : 403);
            break;

        // Deletar mensagem
        case 'deletar':
            $msgId = (int)($input['mensagem_id'] ?? 0);
            if (!$msgId) jsonResponse(['error' => 'mensagem_id obrigatório'], 400);
            $ok = $chat->deletarMensagem($msgId, $usuarioId, isAdmin());
            jsonResponse($ok ? ['success' => true] : ['error' => 'Não autorizado'], $ok ? 200 : 403);
            break;

        // Toggle reação
        case 'reagir':
            $msgId = (int)($input['mensagem_id'] ?? 0);
            $emoji = $input['emoji'] ?? '';
            if (!$msgId || empty($emoji)) jsonResponse(['error' => 'Dados inválidos'], 400);
            $result = $chat->toggleReacao($msgId, $usuarioId, $emoji);
            jsonResponse(['success' => true, 'data' => ['action' => $result]]);
            break;

        // Digitando
        case 'digitando':
            $conversaId = !empty($input['conversa_id']) ? (int)$input['conversa_id'] : null;
            $chat->setDigitando($usuarioId, $conversaId);
            $chat->atualizarPresenca($usuarioId, 'online');
            jsonResponse(['success' => true]);
            break;

        // Adicionar participante
        case 'adicionar_participante':
            $conversaId = (int)($input['conversa_id'] ?? 0);
            $novoId = (int)($input['usuario_id'] ?? 0);
            if (!$conversaId || !$novoId) jsonResponse(['error' => 'Dados inválidos'], 400);
            $chat->adicionarParticipante($conversaId, $novoId, $usuarioId);
            jsonResponse(['success' => true]);
            break;

        // Remover participante
        case 'remover_participante':
            $conversaId = (int)($input['conversa_id'] ?? 0);
            $remId = (int)($input['usuario_id'] ?? 0);
            if (!$conversaId || !$remId) jsonResponse(['error' => 'Dados inválidos'], 400);
            $chat->removerParticipante($conversaId, $remId, $usuarioId);
            jsonResponse(['success' => true]);
            break;

        // Sair do grupo
        case 'sair':
            $conversaId = (int)($input['conversa_id'] ?? 0);
            if (!$conversaId) jsonResponse(['error' => 'conversa_id obrigatório'], 400);
            $chat->sairDoGrupo($conversaId, $usuarioId);
            jsonResponse(['success' => true]);
            break;

        // Mutar/desmutar conversa
        case 'toggle_mudo':
            $conversaId = (int)($input['conversa_id'] ?? 0);
            if (!$conversaId) jsonResponse(['error' => 'conversa_id obrigatório'], 400);
            $db = Database::getInstance();
            $atual = $db->fetch(
                "SELECT notificacao_mudo FROM chat_participantes WHERE conversa_id = ? AND usuario_id = ?",
                [$conversaId, $usuarioId]
            );
            $novo = ($atual && $atual['notificacao_mudo']) ? 0 : 1;
            $db->query(
                "UPDATE chat_participantes SET notificacao_mudo = ? WHERE conversa_id = ? AND usuario_id = ?",
                [$novo, $conversaId, $usuarioId]
            );
            jsonResponse(['success' => true, 'data' => ['mudo' => $novo]]);
            break;

        // Upload de arquivo no chat
        case 'upload':
            $conversaId = (int)($_POST['conversa_id'] ?? 0);
            if (!$conversaId) jsonResponse(['error' => 'conversa_id obrigatório'], 400);
            
            $conv = $chat->getConversa($conversaId, $usuarioId);
            if (!$conv) jsonResponse(['error' => 'Sem acesso'], 403);

            if (empty($_FILES['arquivo'])) jsonResponse(['error' => 'Nenhum arquivo'], 400);

            $file = $_FILES['arquivo'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) jsonResponse(['error' => 'Arquivo muito grande (max 10MB)'], 400);

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar','txt','csv','mp4','mp3'];
            if (!in_array($ext, $allowedExts)) jsonResponse(['error' => 'Tipo de arquivo não permitido'], 400);

            $uploadDir = __DIR__ . '/../uploads/chat/' . $conversaId . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $nomeArquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $destino = $uploadDir . $nomeArquivo;

            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                jsonResponse(['error' => 'Falha no upload'], 500);
            }

            $tipoMime = mime_content_type($destino);
            $isImage = strpos($tipoMime, 'image/') === 0;

            // Criar mensagem
            $conteudo = $isImage
                ? BASE_URL . '/uploads/chat/' . $conversaId . '/' . $nomeArquivo
                : $file['name'];
            $tipoMsg = $isImage ? 'imagem' : 'arquivo';

            $msgId = $chat->enviarMensagem($conversaId, $usuarioId, $conteudo, $tipoMsg);

            // Registrar anexo
            $db = Database::getInstance();
            $db->insert('chat_anexos', [
                'mensagem_id' => $msgId,
                'nome_original' => $file['name'],
                'nome_arquivo' => $nomeArquivo,
                'tamanho' => $file['size'],
                'tipo_mime' => $tipoMime
            ]);

            jsonResponse(['success' => true, 'data' => [
                'id' => $msgId,
                'tipo' => $tipoMsg,
                'url' => BASE_URL . '/uploads/chat/' . $conversaId . '/' . $nomeArquivo,
                'nome' => $file['name']
            ]]);
            break;

        default:
            jsonResponse(['error' => 'Ação POST não reconhecida'], 400);
    }
} else {
    jsonResponse(['error' => 'Método não suportado'], 405);
}

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
