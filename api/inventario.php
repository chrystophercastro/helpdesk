<?php
/**
 * API: Inventário + Termos de Responsabilidade
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/Inventario.php';
$model = new Inventario();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];

try {

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'criar':
            $campos = [
                'tipo' => sanitizar($data['tipo'] ?? ''),
                'nome' => sanitizar($data['nome'] ?? ''),
                'numero_patrimonio' => sanitizar($data['numero_patrimonio'] ?? ''),
                'modelo' => sanitizar($data['modelo'] ?? ''),
                'fabricante' => sanitizar($data['fabricante'] ?? ''),
                'numero_serie' => sanitizar($data['numero_serie'] ?? ''),
                'localizacao' => sanitizar($data['localizacao'] ?? ''),
                'responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
                'especificacoes' => sanitizar($data['especificacoes'] ?? ''),
                'status' => sanitizar($data['status'] ?? 'ativo')
            ];
            // Campos opcionais — só incluir se existirem na tabela
            try {
                $colCheck = $db->fetchAll("SHOW COLUMNS FROM inventario LIKE 'data_aquisicao'");
                if (!empty($colCheck)) {
                    $campos['data_aquisicao'] = !empty($data['data_aquisicao']) ? $data['data_aquisicao'] : null;
                    $campos['valor_aquisicao'] = !empty($data['valor_aquisicao']) ? (float)$data['valor_aquisicao'] : null;
                    $campos['garantia_ate'] = !empty($data['garantia_ate']) ? $data['garantia_ate'] : null;
                    $campos['observacoes'] = sanitizar($data['observacoes'] ?? '');
                }
            } catch (Exception $e) { /* ignora */ }

            $id = $model->criar($campos);
            jsonResponse(['success' => true, 'id' => $id]);
            break;

        case 'atualizar':
            $id = (int)($data['id'] ?? 0);
            if (!$id) { jsonResponse(['error' => 'ID inválido'], 400); }

            // Whitelist de campos permitidos
            $allowed = ['tipo','nome','numero_patrimonio','modelo','fabricante','numero_serie',
                        'localizacao','responsavel_id','especificacoes','status',
                        'data_aquisicao','valor_aquisicao','garantia_ate','observacoes'];
            $campos = [];
            foreach ($allowed as $field) {
                if (!array_key_exists($field, $data)) continue;
                $v = $data[$field];
                if ($field === 'responsavel_id') {
                    $campos[$field] = !empty($v) ? (int)$v : null;
                } elseif ($field === 'valor_aquisicao') {
                    $campos[$field] = !empty($v) ? (float)$v : null;
                } elseif (in_array($field, ['data_aquisicao', 'garantia_ate'])) {
                    $campos[$field] = !empty($v) ? $v : null;
                } else {
                    $campos[$field] = sanitizar($v ?? '');
                }
            }
            if (empty($campos)) { jsonResponse(['error' => 'Nenhum campo para atualizar'], 400); }

            // Verificar se colunas extras existem na tabela
            try {
                $colCheck = $db->fetchAll("SHOW COLUMNS FROM inventario LIKE 'data_aquisicao'");
                if (empty($colCheck)) {
                    unset($campos['data_aquisicao'], $campos['valor_aquisicao'], $campos['garantia_ate'], $campos['observacoes']);
                }
            } catch (Exception $e) { /* ignora */ }

            $model->atualizar($id, $campos);
            jsonResponse(['success' => true]);
            break;

        case 'deletar':
            $id = (int)($data['id'] ?? 0);
            // Verificar se tem chamados vinculados
            $chamados = $model->getChamadosVinculados($id);
            if (!empty($chamados)) {
                jsonResponse(['error' => 'Este ativo possui ' . count($chamados) . ' chamado(s) vinculado(s). Remova os vínculos antes de excluir.'], 400);
            }
            // Verificar se tem termos pendentes
            $termosPendentes = $db->fetch("SELECT COUNT(*) as total FROM termos_responsabilidade WHERE ativo_id = ? AND status = 'pendente'", [$id]);
            if ($termosPendentes && $termosPendentes['total'] > 0) {
                jsonResponse(['error' => 'Este ativo possui termos pendentes. Cancele-os antes de excluir.'], 400);
            }
            $model->deletar($id);
            jsonResponse(['success' => true]);
            break;

        case 'gerar_termo':
            require_once __DIR__ . '/../app/models/Notificacao.php';
            $ativoId = (int)($data['ativo_id'] ?? 0);
            $ativo = $model->findById($ativoId);
            if (!$ativo) { jsonResponse(['error' => 'Ativo não encontrado'], 404); }

            $usuarioNome = sanitizar($data['usuario_nome'] ?? '');
            $telefone = sanitizar($data['usuario_telefone'] ?? '');
            $tecnicoId = (int)($data['tecnico_id'] ?? 0);
            if (!$usuarioNome || !$telefone || !$tecnicoId) {
                jsonResponse(['error' => 'Preencha todos os campos obrigatórios'], 400);
            }

            // Gerar código do termo TR-YYYY-NNNNN
            $ano = date('Y');
            $ultimo = $db->fetch("SELECT codigo FROM termos_responsabilidade WHERE codigo LIKE ? ORDER BY id DESC LIMIT 1", ["TR-$ano-%"]);
            if ($ultimo) {
                $seq = (int)substr($ultimo['codigo'], -5) + 1;
            } else {
                $seq = 1;
            }
            $codigo = 'TR-' . $ano . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);

            // Token público para assinatura
            $token = bin2hex(random_bytes(32));

            $termoId = $db->insert('termos_responsabilidade', [
                'codigo' => $codigo,
                'ativo_id' => $ativoId,
                'usuario_nome' => $usuarioNome,
                'usuario_cargo' => sanitizar($data['usuario_cargo'] ?? ''),
                'usuario_departamento' => sanitizar($data['usuario_departamento'] ?? ''),
                'usuario_telefone' => $telefone,
                'usuario_email' => sanitizar($data['usuario_email'] ?? ''),
                'tecnico_id' => $tecnicoId,
                'tecnico_assinatura' => $data['tecnico_assinatura'] ?? '',
                'data_assinatura_tecnico' => date('Y-m-d H:i:s'),
                'token' => $token,
                'condicoes' => sanitizar($data['condicoes'] ?? ''),
                'status' => 'pendente',
                'data_entrega' => date('Y-m-d H:i:s')
            ]);

            // Enviar WhatsApp com link para assinatura
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                       . '://' . $_SERVER['HTTP_HOST'] . BASE_URL;
            $link = $baseUrl . '/portal/assinar.php?token=' . $token;

            $mensagem = "📋 *TERMO DE RESPONSABILIDADE*\n\n"
                . "Olá *{$usuarioNome}*!\n\n"
                . "Um termo de responsabilidade foi gerado para o equipamento:\n"
                . "📦 *{$ativo['nome']}*\n"
                . "🏷️ Patrimônio: *{$ativo['numero_patrimonio']}*\n"
                . "📄 Código: *{$codigo}*\n\n"
                . "Para assinar digitalmente, acesse o link abaixo:\n"
                . "👉 {$link}\n\n"
                . "⚠️ Este link é pessoal e intransferível.\n"
                . "_TI - Texas Center_";

            $notificacao = new Notificacao();
            $notificacao->sendWhatsApp($telefone, $mensagem);

            // Processar fotos do equipamento (se enviadas)
            if (!empty($_FILES['fotos'])) {
                $uploadDir = __DIR__ . '/../uploads/termos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fotosFiles = $_FILES['fotos'];
                $count = is_array($fotosFiles['name']) ? count($fotosFiles['name']) : 1;
                for ($i = 0; $i < $count; $i++) {
                    $fname = is_array($fotosFiles['name']) ? $fotosFiles['name'][$i] : $fotosFiles['name'];
                    $ftmp = is_array($fotosFiles['tmp_name']) ? $fotosFiles['tmp_name'][$i] : $fotosFiles['tmp_name'];
                    $fsize = is_array($fotosFiles['size']) ? $fotosFiles['size'][$i] : $fotosFiles['size'];
                    $ferror = is_array($fotosFiles['error']) ? $fotosFiles['error'][$i] : $fotosFiles['error'];
                    if ($ferror !== UPLOAD_ERR_OK || empty($fname)) continue;
                    if ($fsize > UPLOAD_MAX_SIZE) continue;
                    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
                    $nomeArquivo = 'termo_' . $termoId . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($ftmp, $uploadDir . $nomeArquivo)) {
                        $db->insert('termos_fotos', [
                            'termo_id' => $termoId,
                            'nome_original' => $fname,
                            'nome_arquivo' => $nomeArquivo,
                            'tamanho' => $fsize,
                            'tipo_mime' => mime_content_type($uploadDir . $nomeArquivo)
                        ]);
                    }
                }
            }

            jsonResponse(['success' => true, 'id' => $termoId, 'codigo' => $codigo]);
            break;

        case 'enviar_termo_whatsapp':
            require_once __DIR__ . '/../app/models/Notificacao.php';
            $id = (int)($data['id'] ?? 0);
            $termo = $db->fetch("SELECT t.*, i.nome AS ativo_nome, i.numero_patrimonio 
                                 FROM termos_responsabilidade t 
                                 LEFT JOIN inventario i ON t.ativo_id = i.id 
                                 WHERE t.id = ?", [$id]);
            if (!$termo) { jsonResponse(['error' => 'Termo não encontrado'], 404); }
            if ($termo['status'] !== 'pendente') { jsonResponse(['error' => 'Apenas termos pendentes podem ser reenviados'], 400); }

            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                       . '://' . $_SERVER['HTTP_HOST'] . BASE_URL;
            $link = $baseUrl . '/portal/assinar.php?token=' . $termo['token'];

            $mensagem = "📋 *LEMBRETE - TERMO DE RESPONSABILIDADE*\n\n"
                . "Olá *{$termo['usuario_nome']}*!\n\n"
                . "Você ainda não assinou o termo de responsabilidade:\n"
                . "📦 *{$termo['ativo_nome']}*\n"
                . "🏷️ Patrimônio: *{$termo['numero_patrimonio']}*\n"
                . "📄 Código: *{$termo['codigo']}*\n\n"
                . "Acesse o link para assinar:\n"
                . "👉 {$link}\n\n"
                . "_TI - Texas Center_";

            $notificacao = new Notificacao();
            $notificacao->sendWhatsApp($termo['usuario_telefone'], $mensagem);

            jsonResponse(['success' => true]);
            break;

        case 'cancelar_termo':
            $id = (int)($data['id'] ?? 0);
            $termo = $db->fetch("SELECT * FROM termos_responsabilidade WHERE id = ?", [$id]);
            if (!$termo) { jsonResponse(['error' => 'Termo não encontrado'], 404); }
            if ($termo['status'] !== 'pendente') { jsonResponse(['error' => 'Apenas termos pendentes podem ser cancelados'], 400); }
            $db->update('termos_responsabilidade', ['status' => 'cancelado'], 'id = ?', [$id]);
            jsonResponse(['success' => true]);
            break;

        case 'upload_fotos_termo':
            $termoId = (int)($data['termo_id'] ?? 0);
            $termo = $db->fetch("SELECT * FROM termos_responsabilidade WHERE id = ?", [$termoId]);
            if (!$termo) { jsonResponse(['error' => 'Termo não encontrado'], 404); }
            if (empty($_FILES['fotos'])) { jsonResponse(['error' => 'Nenhuma foto enviada'], 400); }

            $uploadDir = __DIR__ . '/../uploads/termos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fotosFiles = $_FILES['fotos'];
            $count = is_array($fotosFiles['name']) ? count($fotosFiles['name']) : 1;
            $uploaded = 0;
            for ($i = 0; $i < $count; $i++) {
                $fname = is_array($fotosFiles['name']) ? $fotosFiles['name'][$i] : $fotosFiles['name'];
                $ftmp = is_array($fotosFiles['tmp_name']) ? $fotosFiles['tmp_name'][$i] : $fotosFiles['tmp_name'];
                $fsize = is_array($fotosFiles['size']) ? $fotosFiles['size'][$i] : $fotosFiles['size'];
                $ferror = is_array($fotosFiles['error']) ? $fotosFiles['error'][$i] : $fotosFiles['error'];
                if ($ferror !== UPLOAD_ERR_OK || empty($fname)) continue;
                if ($fsize > UPLOAD_MAX_SIZE) continue;
                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
                $nomeArquivo = 'termo_' . $termoId . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($ftmp, $uploadDir . $nomeArquivo)) {
                    $db->insert('termos_fotos', [
                        'termo_id' => $termoId,
                        'nome_original' => $fname,
                        'nome_arquivo' => $nomeArquivo,
                        'tamanho' => $fsize,
                        'tipo_mime' => mime_content_type($uploadDir . $nomeArquivo)
                    ]);
                    $uploaded++;
                }
            }
            jsonResponse(['success' => true, 'uploaded' => $uploaded]);
            break;

        case 'excluir_foto_termo':
            $fotoId = (int)($data['foto_id'] ?? 0);
            $foto = $db->fetch("SELECT * FROM termos_fotos WHERE id = ?", [$fotoId]);
            if (!$foto) { jsonResponse(['error' => 'Foto não encontrada'], 404); }
            $filePath = __DIR__ . '/../uploads/termos/' . $foto['nome_arquivo'];
            if (file_exists($filePath)) unlink($filePath);
            $db->delete('termos_fotos', 'id = ?', [$fotoId]);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'ver':
            $id = (int)($_GET['id'] ?? 0);
            $item = $model->findById($id);
            if ($item && $item['responsavel_id']) {
                $user = $db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$item['responsavel_id']]);
                $item['responsavel_nome'] = $user['nome'] ?? '';
            }
            jsonResponse($item ?: ['error' => 'Ativo não encontrado']);
            break;

        case 'ver_termo':
            $id = (int)($_GET['id'] ?? 0);
            $termo = $db->fetch(
                "SELECT t.*, i.nome AS ativo_nome, i.numero_patrimonio, i.tipo AS ativo_tipo, 
                        i.modelo AS ativo_modelo, i.numero_serie AS ativo_serie,
                        u.nome AS tecnico_nome 
                 FROM termos_responsabilidade t 
                 LEFT JOIN inventario i ON t.ativo_id = i.id 
                 LEFT JOIN usuarios u ON t.tecnico_id = u.id 
                 WHERE t.id = ?", [$id]);
            if ($termo) {
                // Buscar fotos do termo
                try {
                    $fotos = $db->fetchAll("SELECT id, nome_original, nome_arquivo, tamanho, tipo_mime, criado_em FROM termos_fotos WHERE termo_id = ? ORDER BY criado_em ASC", [$id]);
                    $termo['fotos'] = $fotos ?: [];
                } catch (Exception $e) {
                    $termo['fotos'] = [];
                }
            }
            jsonResponse($termo ?: ['error' => 'Termo não encontrado']);
            break;

        case 'listar':
            $filtros = [
                'tipo' => $_GET['tipo'] ?? '',
                'status' => $_GET['status'] ?? '',
                'busca' => $_GET['busca'] ?? ''
            ];
            $itens = $model->listar($filtros);
            jsonResponse($itens);
            break;

        default:
            jsonResponse(['error' => 'Ação não especificada'], 400);
    }
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}

} catch (Exception $e) {
    jsonResponse(['error' => 'Erro interno: ' . $e->getMessage()], 500);
}
