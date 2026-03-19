<?php
/**
 * API: Configurações
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

requireRole(['admin']);

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'salvar':
            if (!empty($data['config']) && is_array($data['config'])) {
                foreach ($data['config'] as $chave => $valor) {
                    $exists = $db->fetch("SELECT id FROM configuracoes WHERE chave = ?", [$chave]);
                    if ($exists) {
                        $db->update('configuracoes', ['valor' => sanitizar($valor)], 'chave = ?', [$chave]);
                    } else {
                        $db->insert('configuracoes', ['chave' => sanitizar($chave), 'valor' => sanitizar($valor)]);
                    }
                }
                jsonResponse(['success' => true]);
            } else {
                jsonResponse(['error' => 'Nenhuma configuração fornecida'], 400);
            }
            break;

        case 'criar_categoria':
            $nome = trim($data['nome'] ?? '');
            if (empty($nome)) {
                jsonResponse(['error' => 'Nome é obrigatório'], 400);
            }
            $icone = trim($data['icone'] ?? '');
            $campos = [
                'nome' => sanitizar($nome),
                'tipo' => sanitizar($data['tipo'] ?? 'chamado'),
                'icone' => !empty($icone) ? sanitizar($icone) : 'fas fa-tag',
                'ativo' => 1
            ];
            $id = $db->insert('categorias', $campos);
            jsonResponse(['success' => true, 'id' => $id]);
            break;

        case 'criar_sla':
            $prioridade = sanitizar($data['prioridade'] ?? '');
            $categoriaId = !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null;
            $tempoResp = (int)($data['tempo_resposta'] ?? 0);
            $tempoResol = (int)($data['tempo_resolucao'] ?? 0);

            if (empty($prioridade) || $tempoResp <= 0 || $tempoResol <= 0) {
                jsonResponse(['error' => 'Preencha todos os campos obrigatórios'], 400);
            }

            // Verificar duplicata
            $existe = $db->fetch(
                "SELECT id FROM sla WHERE categoria_id <=> ? AND prioridade = ?",
                [$categoriaId, $prioridade]
            );
            if ($existe) {
                $catNome = $categoriaId ? 'esta categoria' : 'Padrão';
                jsonResponse(['error' => "Já existe SLA para {$catNome} com prioridade {$prioridade}"], 400);
            }

            $catNomeSla = 'Padrão';
            if ($categoriaId) {
                $cat = $db->fetch("SELECT nome FROM categorias WHERE id = ?", [$categoriaId]);
                $catNomeSla = $cat['nome'] ?? 'Categoria';
            }
            $priLabels = ['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'];

            $campos = [
                'categoria_id' => $categoriaId,
                'nome' => 'SLA ' . $catNomeSla . ' - ' . ($priLabels[$prioridade] ?? $prioridade),
                'prioridade' => $prioridade,
                'tempo_resposta' => $tempoResp,
                'tempo_resolucao' => $tempoResol
            ];
            $id = $db->insert('sla', $campos);
            jsonResponse(['success' => true, 'id' => $id]);
            break;

        case 'atualizar_sla':
            $slaId = (int)($data['id'] ?? 0);
            if (!$slaId) jsonResponse(['error' => 'ID inválido'], 400);

            $prioridade = sanitizar($data['prioridade'] ?? '');
            $categoriaId = !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null;
            $tempoResp = (int)($data['tempo_resposta'] ?? 0);
            $tempoResol = (int)($data['tempo_resolucao'] ?? 0);

            // Verificar duplicata (excluindo o próprio registro)
            $existe = $db->fetch(
                "SELECT id FROM sla WHERE categoria_id <=> ? AND prioridade = ? AND id != ?",
                [$categoriaId, $prioridade, $slaId]
            );
            if ($existe) {
                jsonResponse(['error' => 'Já existe outra regra SLA para esta combinação'], 400);
            }

            $catNomeSla = 'Padrão';
            if ($categoriaId) {
                $cat = $db->fetch("SELECT nome FROM categorias WHERE id = ?", [$categoriaId]);
                $catNomeSla = $cat['nome'] ?? 'Categoria';
            }
            $priLabels = ['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'];

            $db->update('sla', [
                'categoria_id' => $categoriaId,
                'nome' => 'SLA ' . $catNomeSla . ' - ' . ($priLabels[$prioridade] ?? $prioridade),
                'prioridade' => $prioridade,
                'tempo_resposta' => $tempoResp,
                'tempo_resolucao' => $tempoResol
            ], 'id = ?', [$slaId]);
            jsonResponse(['success' => true]);
            break;

        case 'excluir_sla':
            $slaId = (int)($data['id'] ?? 0);
            if (!$slaId) jsonResponse(['error' => 'ID inválido'], 400);
            // Desvincular chamados que usam este SLA
            $db->query("UPDATE chamados SET sla_id = NULL WHERE sla_id = ?", [$slaId]);
            $db->delete('sla', 'id = ?', [$slaId]);
            jsonResponse(['success' => true]);
            break;

        case 'testar_whatsapp':
            // Buscar configurações do banco
            $configs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
            $cfg = [];
            foreach ($configs as $c) {
                $cfg[$c['chave']] = $c['valor'];
            }

            $apiUrl = $cfg['evolution_api_url'] ?? '';
            $apiKey = $cfg['evolution_api_key'] ?? '';
            $instance = $cfg['evolution_instance'] ?? '';

            if (empty($apiUrl) || empty($apiKey) || empty($instance)) {
                jsonResponse(['error' => 'Configure URL, API Key e Instância antes de testar'], 400);
                break;
            }

            // Verificar status da instância via Evolution API
            $url = rtrim($apiUrl, '/') . '/instance/connectionState/' . rawurlencode($instance);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'apikey: ' . $apiKey
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                jsonResponse(['error' => 'Erro de conexão: ' . $error], 500);
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                $body = json_decode($response, true);
                $state = $body['instance']['state'] ?? ($body['state'] ?? 'unknown');
                jsonResponse(['success' => true, 'message' => 'Instância: ' . $state, 'state' => $state]);
            } else {
                jsonResponse(['error' => 'Não foi possível conectar à API. HTTP: ' . $httpCode], $httpCode);
            }
            break;

        default:
            jsonResponse(['error' => 'Ação inválida'], 400);
    }
} elseif ($method === 'GET') {
    $configs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
    $result = [];
    foreach ($configs as $c) {
        $result[$c['chave']] = $c['valor'];
    }
    jsonResponse($result);
} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
