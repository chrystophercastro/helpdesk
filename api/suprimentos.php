<?php
/**
 * API: Suprimentos (Estoque + Requisições de Compra)
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/Suprimento.php';
$model = new Suprimento();

$method = $_SERVER['REQUEST_METHOD'];
$user = currentUser();

// ==========================================
//  POST ACTIONS
// ==========================================
if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // Upload de arquivo (importação)
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $action = $_POST['action'] ?? '';
    } else {
        $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
    }

    switch ($action) {

        // ------ PRODUTOS ------
        case 'criar_produto':
            try {
                $data['criado_por'] = $user['id'];
                $id = $model->criar($data);
                $produto = $model->findById($id);
                jsonResponse(['success' => true, 'message' => 'Produto cadastrado com sucesso!', 'produto' => $produto]);
            } catch (\Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;

        case 'editar_produto':
            try {
                $id = (int)($data['id'] ?? 0);
                if (!$id) jsonResponse(['error' => 'ID não informado'], 400);
                $model->atualizar($id, $data);
                $produto = $model->findById($id);
                jsonResponse(['success' => true, 'message' => 'Produto atualizado!', 'produto' => $produto]);
            } catch (\Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;

        case 'toggle_ativo':
            try {
                $id = (int)($data['id'] ?? 0);
                $ativo = (int)($data['ativo'] ?? 0);
                $model->atualizar($id, ['ativo' => $ativo]);
                jsonResponse(['success' => true, 'message' => $ativo ? 'Produto reativado!' : 'Produto desativado!']);
            } catch (\Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;

        // ------ MOVIMENTAÇÕES ------
        case 'movimentar':
            try {
                $suprimentoId = (int)($data['suprimento_id'] ?? 0);
                $tipo = sanitizar($data['tipo'] ?? '');
                $quantidade = (int)($data['quantidade'] ?? 0);

                if (!$suprimentoId || !$tipo || $quantidade <= 0) {
                    jsonResponse(['error' => 'Dados incompletos'], 400);
                }

                $movId = $model->registrarMovimentacao($suprimentoId, $tipo, $quantidade, [
                    'motivo'     => sanitizar($data['motivo'] ?? ''),
                    'documento'  => sanitizar($data['documento'] ?? ''),
                    'usuario_id' => $user['id']
                ]);

                $produto = $model->findById($suprimentoId);
                jsonResponse([
                    'success' => true,
                    'message' => 'Movimentação registrada!',
                    'estoque_atual' => $produto['estoque_atual'],
                    'mov_id' => $movId
                ]);
            } catch (\Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;

        // ------ REQUISIÇÕES ------
        case 'criar_requisicao':
            try {
                $data['solicitante_id'] = $user['id'];
                // Tratar itens JSON
                if (isset($data['itens']) && is_string($data['itens'])) {
                    $data['itens'] = json_decode($data['itens'], true);
                }
                $reqId = $model->criarRequisicao($data);
                $req = $model->findRequisicao($reqId);
                jsonResponse(['success' => true, 'message' => 'Requisição criada com sucesso!', 'requisicao' => $req]);
            } catch (\Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;

        case 'alterar_status_requisicao':
            try {
                $id = (int)($data['id'] ?? 0);
                $status = sanitizar($data['status'] ?? '');
                $observacao = sanitizar($data['observacao'] ?? '');

                if (!$id || !$status) {
                    jsonResponse(['error' => 'Dados incompletos'], 400);
                }

                $model->alterarStatusRequisicao($id, $status, $user['id'], $observacao);
                jsonResponse(['success' => true, 'message' => 'Status atualizado!']);
            } catch (\Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;

        // ------ E-MAIL ------
        case 'enviar_email_requisicao':
            try {
                $reqId = (int)($data['requisicao_id'] ?? 0);
                $emailDestino = sanitizar($data['email_destino'] ?? '');
                $contaId = (int)($data['conta_email_id'] ?? 0);

                if (!$reqId || !$emailDestino || !$contaId) {
                    jsonResponse(['error' => 'Informe a requisição, e-mail destino e conta de envio'], 400);
                }

                $emailData = $model->gerarEmailRequisicao($reqId);

                require_once __DIR__ . '/../app/models/Email.php';
                $emailModel = new Email();
                $emailModel->enviarEmail($contaId, $user['id'], [
                    'to'      => $emailDestino,
                    'subject' => $emailData['subject'],
                    'body'    => $emailData['body']
                ]);

                // Marcar como enviado
                $db = Database::getInstance();
                $db->update('suprimento_requisicoes', [
                    'email_enviado' => 1,
                    'email_compras' => $emailDestino
                ], 'id = ?', [$reqId]);

                jsonResponse(['success' => true, 'message' => 'E-mail enviado com sucesso para ' . $emailDestino]);
            } catch (\Exception $e) {
                jsonResponse(['error' => 'Falha ao enviar e-mail: ' . $e->getMessage()], 500);
            }
            break;

        // ------ IMPORTAÇÃO ------
        case 'importar':
            try {
                if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                    jsonResponse(['error' => 'Nenhum arquivo enviado ou erro no upload'], 400);
                }

                $file = $_FILES['arquivo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['csv', 'txt'])) {
                    jsonResponse(['error' => 'Formato não suportado. Use CSV ou TXT'], 400);
                }

                $resultado = $model->importarCSV($file['tmp_name'], $user['id']);
                jsonResponse([
                    'success' => true,
                    'message' => "Importação concluída: {$resultado['sucesso']} de {$resultado['total']} produtos importados",
                    'resultado' => $resultado
                ]);
            } catch (\Exception $e) {
                jsonResponse(['error' => 'Erro na importação: ' . $e->getMessage()], 500);
            }
            break;

        // ------ CATEGORIAS ------
        case 'criar_categoria':
            try {
                $id = $model->criarCategoria($data);
                jsonResponse(['success' => true, 'message' => 'Categoria criada!', 'id' => $id]);
            } catch (\Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 400);
            }
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }

// ==========================================
//  GET ACTIONS
// ==========================================
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? 'listar_produtos';

    switch ($action) {
        case 'listar_produtos':
            $filtros = [
                'busca'        => $_GET['busca'] ?? '',
                'categoria_id' => $_GET['categoria_id'] ?? '',
                'ativo'        => isset($_GET['ativo']) ? $_GET['ativo'] : 1,
                'estoque_baixo'=> $_GET['estoque_baixo'] ?? '',
                'limite'       => $_GET['limite'] ?? '',
                'offset'       => $_GET['offset'] ?? 0
            ];
            $produtos = $model->listar($filtros);
            $total = $model->contar($filtros);
            jsonResponse(['produtos' => $produtos, 'total' => $total]);
            break;

        case 'ver_produto':
            $id = (int)($_GET['id'] ?? 0);
            $produto = $model->findById($id);
            if (!$produto) jsonResponse(['error' => 'Produto não encontrado'], 404);
            $movimentacoes = $model->listarMovimentacoes(['suprimento_id' => $id, 'limite' => 20]);
            jsonResponse(['produto' => $produto, 'movimentacoes' => $movimentacoes]);
            break;

        case 'buscar_produtos':
            $termo = $_GET['termo'] ?? '';
            $produtos = $model->buscarProdutos($termo);
            jsonResponse(['produtos' => $produtos]);
            break;

        case 'listar_movimentacoes':
            $filtros = [
                'suprimento_id' => $_GET['suprimento_id'] ?? '',
                'tipo'          => $_GET['tipo'] ?? '',
                'data_inicio'   => $_GET['data_inicio'] ?? '',
                'data_fim'      => $_GET['data_fim'] ?? '',
                'limite'        => $_GET['limite'] ?? 50,
                'offset'        => $_GET['offset'] ?? 0
            ];
            $movs = $model->listarMovimentacoes($filtros);
            jsonResponse(['movimentacoes' => $movs]);
            break;

        case 'listar_requisicoes':
            $filtros = [
                'status'  => $_GET['status'] ?? '',
                'busca'   => $_GET['busca'] ?? ''
            ];
            $reqs = $model->listarRequisicoes($filtros);
            jsonResponse(['requisicoes' => $reqs]);
            break;

        case 'ver_requisicao':
            $id = (int)($_GET['id'] ?? 0);
            $req = $model->findRequisicao($id);
            if (!$req) jsonResponse(['error' => 'Requisição não encontrada'], 404);
            $itens = $model->getItensRequisicao($id);
            $historico = $model->getHistoricoRequisicao($id);
            jsonResponse(['requisicao' => $req, 'itens' => $itens, 'historico' => $historico]);
            break;

        case 'estatisticas':
            $stats = $model->getEstatisticas();
            jsonResponse($stats);
            break;

        case 'categorias':
            $categorias = $model->listarCategorias();
            jsonResponse(['categorias' => $categorias]);
            break;

        case 'contas_email':
            $db = Database::getInstance();
            $contas = $db->fetchAll(
                "SELECT id, nome_conta, email FROM email_contas WHERE usuario_id = ? AND ativo = 1",
                [$user['id']]
            );
            jsonResponse(['contas' => $contas]);
            break;

        default:
            $produtos = $model->listar(['ativo' => 1]);
            jsonResponse(['produtos' => $produtos]);
    }

} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
