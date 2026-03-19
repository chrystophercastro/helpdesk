<?php
/**
 * API: Chamados
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/controllers/ChamadoController.php';
$controller = new ChamadoController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  try {
    // Para FormData (multipart), dados vêm em $_POST. Para JSON, vem em php://input
    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        $jsonBody = file_get_contents('php://input');
        $data = $jsonBody ? json_decode($jsonBody, true) : [];
    }
    // Remover token CSRF dos dados para não ir ao banco
    unset($data['_csrf']);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            // Check if it's FormData (has files)
            $isFormData = !empty($_FILES);
            $result = $controller->criar($data);

            // Handle file upload
            if ($result['success'] && $isFormData && !empty($_FILES['anexo'])) {
                $chamadoId = $result['id'];
                $uploadDir = __DIR__ . '/../uploads/chamados/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $file = $_FILES['anexo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','txt','zip','rar'];

                if (in_array($ext, $allowedExts) && $file['size'] <= 10 * 1024 * 1024) {
                    $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                        $db = Database::getInstance();
                        $db->insert('anexos', [
                            'entidade_tipo' => 'chamado',
                            'entidade_id' => $chamadoId,
                            'nome_original' => $file['name'],
                            'nome_arquivo' => $fileName,
                            'tipo_mime' => $file['type'],
                            'tamanho' => $file['size'],
                            'usuario_id' => currentUser()['id']
                        ]);
                    }
                }
            }

            jsonResponse($result);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            $result = $controller->atualizar($id, $data);
            jsonResponse($result);
            break;

        case 'comentar':
            $chamadoId = (int)($data['chamado_id'] ?? 0);
            $result = $controller->comentar($chamadoId, $data);

            // Upload de arquivos do comentário
            if ($result['success'] && !empty($_FILES['arquivos'])) {
                $comentarioId = $result['id'];
                $uploadDir = UPLOAD_PATH . '/comentarios/' . $comentarioId . '/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $files = $_FILES['arquivos'];
                $fileCount = is_array($files['name']) ? count($files['name']) : 1;
                $uploadedFiles = [];

                for ($i = 0; $i < $fileCount; $i++) {
                    $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                    $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                    $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                    $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];

                    if ($error !== UPLOAD_ERR_OK || empty($name)) continue;
                    if ($size > UPLOAD_MAX_SIZE) continue;

                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','txt','zip','rar'];
                    if (!in_array($ext, $allowed)) continue;

                    $nomeArquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
                    $destino = $uploadDir . $nomeArquivo;

                    if (move_uploaded_file($tmp, $destino)) {
                        $db = Database::getInstance();
                        $db->insert('anexos', [
                            'entidade_tipo' => 'comentario',
                            'entidade_id' => $comentarioId,
                            'nome_original' => $name,
                            'nome_arquivo' => $nomeArquivo,
                            'tamanho' => $size,
                            'tipo_mime' => $type
                        ]);
                        $uploadedFiles[] = [
                            'path' => $destino,
                            'name' => $name,
                            'type' => $type,
                            'url' => UPLOAD_URL . '/comentarios/' . $comentarioId . '/' . $nomeArquivo
                        ];
                    }
                }

                // Enviar arquivos via WhatsApp (se não for nota interna)
                $tipo = $data['tipo'] ?? 'comentario';
                if ($tipo !== 'interno' && !empty($uploadedFiles)) {
                    require_once __DIR__ . '/../app/models/Notificacao.php';
                    $notificacao = new Notificacao();
                    $chamadoData = $controller->ver($chamadoId);
                    if ($chamadoData && !empty($chamadoData['telefone_solicitante'])) {
                        foreach ($uploadedFiles as $uf) {
                            $notificacao->sendWhatsAppMedia(
                                $chamadoData['telefone_solicitante'],
                                $uf['path'],
                                $uf['name'],
                                $uf['type']
                            );
                        }
                    }
                }

                $result['arquivos'] = count($uploadedFiles);
            }

            jsonResponse($result);
            break;

        case 'upload_anexo':
            $chamadoId = (int)($data['chamado_id'] ?? 0);
            if (!$chamadoId || empty($_FILES['anexos'])) {
                jsonResponse(['error' => 'Dados inválidos'], 400);
            }

            $uploadDir = UPLOAD_PATH . '/chamado/' . $chamadoId . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $files = $_FILES['anexos'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
            $uploaded = 0;

            for ($i = 0; $i < $fileCount; $i++) {
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];

                if ($error !== UPLOAD_ERR_OK || empty($name)) continue;
                if ($size > UPLOAD_MAX_SIZE) continue;

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS)) continue;

                $nomeArquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
                $destino = $uploadDir . $nomeArquivo;

                if (move_uploaded_file($tmp, $destino)) {
                    $db = Database::getInstance();
                    $db->insert('anexos', [
                        'entidade_tipo' => 'chamado',
                        'entidade_id' => $chamadoId,
                        'nome_original' => $name,
                        'nome_arquivo' => $nomeArquivo,
                        'tamanho' => $size,
                        'tipo_mime' => $type
                    ]);
                    $uploaded++;
                }
            }

            jsonResponse(['success' => true, 'uploaded' => $uploaded]);
            break;

        case 'atribuir':
            $chamadoId = (int)($data['chamado_id'] ?? 0);
            $tecnicoId = (int)($data['tecnico_id'] ?? 0);
            require_once __DIR__ . '/../app/models/Chamado.php';
            $chamadoModel = new Chamado();
            $chamadoModel->atualizar($chamadoId, ['tecnico_id' => $tecnicoId, 'status' => 'em_atendimento']);
            $chamadoModel->registrarHistorico($chamadoId, currentUser()['id'], 'atribuicao', '', 'Chamado atribuído ao técnico');
            jsonResponse(['success' => true]);
            break;

        // ========== TRANSFORMAÇÕES ==========

        case 'transformar_projeto':
            $chamadoId = (int)($data['chamado_id'] ?? 0);
            $chamadoData = $controller->ver($chamadoId);
            if (!$chamadoData) { jsonResponse(['error' => 'Chamado não encontrado'], 404); break; }

            require_once __DIR__ . '/../app/models/Projeto.php';
            $projetoModel = new Projeto();
            $projetoData = [
                'nome' => sanitizar($data['nome'] ?? $chamadoData['titulo']),
                'descricao' => sanitizar($data['descricao'] ?? $chamadoData['descricao']),
                'responsavel_id' => $data['responsavel_id'] ?? $chamadoData['tecnico_id'],
                'prioridade' => $chamadoData['prioridade'],
                'status' => 'planejamento',
                'data_inicio' => $data['data_inicio'] ?? date('Y-m-d'),
                'prazo' => $data['prazo'] ?? null,
                'chamado_origem_id' => $chamadoId
            ];
            $projetoId = $projetoModel->criar($projetoData);

            // Registrar no histórico do chamado
            $chamadoModel2 = new Chamado();
            $chamadoModel2->registrarHistorico($chamadoId, currentUser()['id'], 'transformacao', '', 'Transformado em Projeto #' . $projetoId);

            // Notificar solicitante via WhatsApp
            require_once __DIR__ . '/../app/models/Notificacao.php';
            $notificacao = new Notificacao();
            $notificacao->notificarTransformacao($chamadoData, 'projeto', $projetoId, $projetoData['nome']);

            // Log
            $db = Database::getInstance();
            $db->insert('logs', [
                'usuario_id' => $_SESSION['usuario_id'] ?? null,
                'acao' => 'chamado_transformado_projeto',
                'entidade_tipo' => 'chamado',
                'entidade_id' => $chamadoId,
                'detalhes' => "Chamado {$chamadoData['codigo']} transformado em Projeto #{$projetoId}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            jsonResponse(['success' => true, 'projeto_id' => $projetoId, 'message' => 'Chamado transformado em projeto com sucesso!']);
            break;

        case 'transformar_sprint':
            $chamadoId = (int)($data['chamado_id'] ?? 0);
            $projetoId2 = (int)($data['projeto_id'] ?? 0);
            $chamadoData = $controller->ver($chamadoId);
            if (!$chamadoData) { jsonResponse(['error' => 'Chamado não encontrado'], 404); break; }
            if (!$projetoId2) { jsonResponse(['error' => 'Selecione um projeto'], 400); break; }

            require_once __DIR__ . '/../app/models/Sprint.php';
            require_once __DIR__ . '/../app/models/Tarefa.php';
            $sprintModel = new Sprint();
            $tarefaModel = new Tarefa();

            // Criar Sprint
            $sprintData = [
                'nome' => sanitizar($data['nome'] ?? 'Sprint - ' . $chamadoData['titulo']),
                'projeto_id' => $projetoId2,
                'data_inicio' => $data['data_inicio'] ?? date('Y-m-d'),
                'data_fim' => $data['data_fim'] ?? date('Y-m-d', strtotime('+14 days')),
                'status' => 'planejamento',
                'meta' => sanitizar($data['meta'] ?? 'Originado do chamado ' . $chamadoData['codigo'])
            ];
            $sprintId = $sprintModel->criar($sprintData);

            // Criar tarefa automaticamente dentro da sprint
            $tarefaModel->criar([
                'titulo' => $chamadoData['titulo'],
                'descricao' => $chamadoData['descricao'],
                'projeto_id' => $projetoId2,
                'sprint_id' => $sprintId,
                'responsavel_id' => $chamadoData['tecnico_id'],
                'prioridade' => $chamadoData['prioridade'],
                'coluna' => 'a_fazer',
                'chamado_origem_id' => $chamadoId
            ]);

            // Registrar no histórico do chamado
            $chamadoModel3 = new Chamado();
            $chamadoModel3->registrarHistorico($chamadoId, currentUser()['id'], 'transformacao', '', 'Transformado em Sprint #' . $sprintId);

            // Notificar solicitante via WhatsApp
            require_once __DIR__ . '/../app/models/Notificacao.php';
            $notificacao = new Notificacao();
            $notificacao->notificarTransformacao($chamadoData, 'sprint', $sprintId, $sprintData['nome']);

            // Log
            $db = Database::getInstance();
            $db->insert('logs', [
                'usuario_id' => $_SESSION['usuario_id'] ?? null,
                'acao' => 'chamado_transformado_sprint',
                'entidade_tipo' => 'chamado',
                'entidade_id' => $chamadoId,
                'detalhes' => "Chamado {$chamadoData['codigo']} transformado em Sprint #{$sprintId}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            jsonResponse(['success' => true, 'sprint_id' => $sprintId, 'message' => 'Chamado transformado em sprint com sucesso!']);
            break;

        case 'transformar_compra':
            $chamadoId = (int)($data['chamado_id'] ?? 0);
            $chamadoData = $controller->ver($chamadoId);
            if (!$chamadoData) { jsonResponse(['error' => 'Chamado não encontrado'], 404); break; }

            require_once __DIR__ . '/../app/models/Compra.php';
            $compraModel = new Compra();

            $codigoCompra = gerarCodigoCompra();
            $compraData = [
                'codigo' => $codigoCompra,
                'solicitante_usuario_id' => $_SESSION['usuario_id'] ?? null,
                'item' => sanitizar($data['item'] ?? $chamadoData['titulo']),
                'descricao' => sanitizar($data['descricao'] ?? $chamadoData['descricao']),
                'quantidade' => (int)($data['quantidade'] ?? 1),
                'justificativa' => sanitizar($data['justificativa'] ?? 'Originado do chamado ' . $chamadoData['codigo'] . ': ' . $chamadoData['titulo']),
                'prioridade' => $chamadoData['prioridade'],
                'valor_estimado' => (float)($data['valor_estimado'] ?? 0),
                'status' => 'solicitado',
                'chamado_origem_id' => $chamadoId
            ];
            $compraId = $compraModel->criar($compraData);

            // Registrar no histórico do chamado
            $chamadoModel4 = new Chamado();
            $chamadoModel4->registrarHistorico($chamadoId, currentUser()['id'], 'transformacao', '', 'Transformado em Compra ' . $codigoCompra);

            // Notificar solicitante via WhatsApp
            require_once __DIR__ . '/../app/models/Notificacao.php';
            $notificacao = new Notificacao();
            $notificacao->notificarTransformacao($chamadoData, 'compra', $compraId, $codigoCompra);

            // Log
            $db = Database::getInstance();
            $db->insert('logs', [
                'usuario_id' => $_SESSION['usuario_id'] ?? null,
                'acao' => 'chamado_transformado_compra',
                'entidade_tipo' => 'chamado',
                'entidade_id' => $chamadoId,
                'detalhes' => "Chamado {$chamadoData['codigo']} transformado em Compra {$codigoCompra}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            jsonResponse(['success' => true, 'compra_id' => $compraId, 'codigo' => $codigoCompra, 'message' => 'Chamado transformado em requisição de compra com sucesso!']);
            break;

        case 'reenviar_avaliacao':
            $chamadoId = (int)($data['chamado_id'] ?? 0);
            if (!$chamadoId) {
                jsonResponse(['error' => 'ID do chamado inválido'], 400);
            }

            $chamado = $controller->ver($chamadoId);
            if (!$chamado) {
                jsonResponse(['error' => 'Chamado não encontrado'], 404);
            }

            if (!in_array($chamado['status'], ['resolvido', 'fechado'])) {
                jsonResponse(['error' => 'O chamado precisa estar resolvido ou fechado para enviar avaliação'], 400);
            }

            require_once __DIR__ . '/../app/models/Notificacao.php';
            $notificacao = new Notificacao();
            $notificacao->notificarAvaliacao($chamado, $chamado['solicitante_nome'] ?? '');

            jsonResponse(['success' => true, 'message' => 'Link de avaliação reenviado via WhatsApp']);
            break;

        case 'excluir':
            // Somente admin pode excluir
            if ($_SESSION['usuario_tipo'] !== 'admin') {
                jsonResponse(['error' => 'Sem permissão para excluir chamados'], 403);
            }
            $chamadoId = (int)($data['id'] ?? 0);
            if (!$chamadoId) {
                jsonResponse(['error' => 'ID do chamado inválido'], 400);
            }
            $chamado = $controller->ver($chamadoId);
            if (!$chamado) {
                jsonResponse(['error' => 'Chamado não encontrado'], 404);
            }

            $db = Database::getInstance();

            // 1) Excluir arquivos físicos dos anexos
            try {
                $anexos = $db->fetchAll("SELECT nome_arquivo FROM anexos WHERE entidade_tipo = 'chamado' AND entidade_id = ?", [$chamadoId]);
                foreach ($anexos as $a) {
                    $filePath = UPLOAD_PATH . '/chamados/' . $a['nome_arquivo'];
                    if (file_exists($filePath)) unlink($filePath);
                }
                // Anexos de comentários do chamado
                $comentarioIds = $db->fetchAll("SELECT id FROM chamados_comentarios WHERE chamado_id = ?", [$chamadoId]);
                foreach ($comentarioIds as $c) {
                    $anexosComent = $db->fetchAll("SELECT nome_arquivo FROM anexos WHERE entidade_tipo = 'comentario' AND entidade_id = ?", [$c['id']]);
                    foreach ($anexosComent as $ac) {
                        $filePath = UPLOAD_PATH . '/comentarios/' . $ac['nome_arquivo'];
                        if (file_exists($filePath)) unlink($filePath);
                    }
                    $db->delete('anexos', "entidade_tipo = 'comentario' AND entidade_id = ?", [$c['id']]);
                }
            } catch (Exception $e) { /* continua */ }

            // 2) Limpar registros sem FK cascade
            try { $db->delete('anexos', "entidade_tipo = 'chamado' AND entidade_id = ?", [$chamadoId]); } catch (Exception $e) {}
            try { $db->delete('logs', "entidade_tipo = 'chamado' AND entidade_id = ?", [$chamadoId]); } catch (Exception $e) {}
            try { $db->delete('notificacoes', "referencia_tipo = 'chamado' AND referencia_id = ?", [$chamadoId]); } catch (Exception $e) {}

            // 3) Excluir chamado (CASCADE cuida de comentarios, historico, tags)
            $db->delete('chamados', 'id = ?', [$chamadoId]);

            jsonResponse(['success' => true, 'message' => 'Chamado excluído com sucesso']);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
  } catch (Exception $e) {
    jsonResponse(['error' => 'Erro interno: ' . $e->getMessage()], 500);
  }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'ver':
            $id = (int)($_GET['id'] ?? 0);
            $chamado = $controller->ver($id);
            jsonResponse($chamado ?: ['error' => 'Chamado não encontrado']);
            break;

        case 'listar':
            $chamados = $controller->listar();
            jsonResponse($chamados);
            break;

        default:
            $chamados = $controller->listar();
            jsonResponse($chamados);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
