<?php
/**
 * Portal API - Abertura e Consulta de Chamados (AJAX)
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Método não permitido'], 405);
}

$data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
$action = $data['acao'] ?? '';

try {

switch ($action) {
    case 'abrir':
        $nome = sanitizar($data['nome'] ?? '');
        $email = sanitizar($data['email'] ?? '');
        $telefone = formatarTelefone(sanitizar($data['telefone'] ?? ''));
        $titulo = sanitizar($data['titulo'] ?? '');
        $descricao = sanitizar($data['descricao'] ?? '');
        $categoria_id = (int)($data['categoria_id'] ?? 0);
        $urgencia = sanitizar($data['urgencia'] ?? 'media');
        $impacto = sanitizar($data['impacto'] ?? 'medio');

        // Validações
        if (empty($nome) || empty($email) || empty($telefone) || empty($titulo) || empty($descricao)) {
            jsonResponse(['error' => 'Preencha todos os campos obrigatórios.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Email inválido.'], 400);
        }

        if (strlen($telefone) < 12 || strlen($telefone) > 15) {
            jsonResponse(['error' => 'Telefone inválido. Use o formato: 5562999999999'], 400);
        }

        require_once __DIR__ . '/../app/controllers/ChamadoController.php';
        $controller = new ChamadoController();
        $result = $controller->criar([
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'categoria_id' => $categoria_id ?: null,
            'urgencia' => $urgencia,
            'impacto' => $impacto
        ], true);

        if (isset($result['success'])) {
            jsonResponse([
                'success' => true,
                'codigo' => $result['codigo'],
                'id' => $result['id'],
                'telefone' => $telefone
            ]);
        } else {
            jsonResponse(['error' => $result['error'] ?? 'Erro ao abrir chamado.'], 400);
        }
        break;

    case 'consultar':
        $telefone = formatarTelefone(sanitizar($data['telefone'] ?? ''));
        $codigo = strtoupper(sanitizar($data['codigo'] ?? ''));

        if (empty($telefone) || empty($codigo)) {
            jsonResponse(['error' => 'Informe o telefone e o código do chamado.'], 400);
        }

        require_once __DIR__ . '/../app/models/Chamado.php';
        $chamadoModel = new Chamado();
        $chamado = $chamadoModel->buscarPorTelefoneECodigo($telefone, $codigo);

        if (!$chamado) {
            jsonResponse(['error' => 'Chamado não encontrado. Verifique o telefone e o código.'], 404);
        }

        $chamado['comentarios'] = $chamadoModel->getComentarios($chamado['id']);

        // Filtrar comentários internos
        $comentariosPublicos = [];
        foreach ($chamado['comentarios'] as $com) {
            if (($com['tipo'] ?? '') !== 'interno') {
                $comentariosPublicos[] = [
                    'autor' => htmlspecialchars($com['autor_nome'] ?? $com['usuario_nome'] ?? 'Equipe', ENT_QUOTES, 'UTF-8', false),
                    'conteudo' => nl2br(htmlspecialchars($com['conteudo'], ENT_QUOTES, 'UTF-8', false)),
                    'data' => formatarData($com['criado_em'])
                ];
            }
        }

        $statusList = CHAMADO_STATUS;
        $st = $statusList[$chamado['status']];

        jsonResponse([
            'success' => true,
            'chamado' => [
                'codigo' => $chamado['codigo'],
                'titulo' => htmlspecialchars($chamado['titulo'], ENT_QUOTES, 'UTF-8', false),
                'descricao' => nl2br(htmlspecialchars($chamado['descricao'], ENT_QUOTES, 'UTF-8', false)),
                'status_label' => $st['label'],
                'status_cor' => $st['cor'],
                'status_icone' => $st['icone'],
                'prioridade' => ucfirst($chamado['prioridade']),
                'categoria' => $chamado['categoria_nome'] ?? '-',
                'tecnico' => $chamado['tecnico_nome'] ?? 'Aguardando atribuição',
                'data_abertura' => formatarData($chamado['data_abertura']),
                'comentarios' => $comentariosPublicos
            ]
        ]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida'], 400);
}
} catch (Exception $e) {
    error_log('Portal API Error: ' . $e->getMessage());
    jsonResponse(['error' => 'Erro interno no servidor. Tente novamente.'], 500);
}