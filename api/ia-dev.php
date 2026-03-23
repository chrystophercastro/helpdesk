<?php
/**
 * API: IA Dev - Desenvolvimento assistido por IA
 * 
 * Gerencia projetos, arquivos gerados, workflow de aprovação
 * e integração com o Chat IA para modo desenvolvimento.
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/IADev.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['usuario_id'];
$user = currentUser();
$dev = new IADev($userId);

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            // ===== PROJETOS =====
            case 'projetos':
                $projetos = $dev->listarProjetos($userId);
                jsonResponse(['success' => true, 'data' => $projetos]);
                break;

            case 'projeto':
                $id = (int)($_GET['id'] ?? 0);
                $projeto = $dev->findProjeto($id);
                if (!$projeto) jsonResponse(['error' => 'Projeto não encontrado'], 404);
                jsonResponse(['success' => true, 'data' => $projeto]);
                break;

            // ===== ARQUIVOS =====
            case 'arquivos':
                $projetoId = (int)($_GET['projeto_id'] ?? 0);
                $status = $_GET['status'] ?? null;
                $arquivos = $dev->listarArquivos($projetoId, $status);
                jsonResponse(['success' => true, 'data' => $arquivos]);
                break;

            case 'arquivo':
                $id = (int)($_GET['id'] ?? 0);
                $arq = $dev->findArquivo($id);
                if (!$arq) jsonResponse(['error' => 'Arquivo não encontrado'], 404);
                jsonResponse(['success' => true, 'data' => $arq]);
                break;

            // ===== ESTRUTURA ORACLE X =====
            case 'estrutura':
                $path = $_GET['path'] ?? '';
                $estrutura = $dev->getEstruturaOracleX($path);
                jsonResponse(['success' => true, 'data' => $estrutura]);
                break;

            case 'ler_arquivo':
                $caminho = $_GET['caminho'] ?? '';
                $projetoId = (int)($_GET['projeto_id'] ?? 0);
                
                if ($projetoId) {
                    $projeto = $dev->findProjeto($projetoId);
                    if ($projeto && $projeto['tipo'] === 'externo') {
                        $arq = $dev->lerArquivoExterno($projetoId, $caminho);
                    } else {
                        $arq = $dev->lerArquivoOracleX($caminho);
                    }
                } else {
                    $arq = $dev->lerArquivoOracleX($caminho);
                }
                jsonResponse(['success' => true, 'data' => $arq]);
                break;

            case 'arquivos_disco':
                $projetoId = (int)($_GET['projeto_id'] ?? 0);
                $arquivos = $dev->listarArquivosDisco($projetoId);
                jsonResponse(['success' => true, 'data' => $arquivos]);
                break;

            // ===== ALTERAÇÕES =====
            case 'alteracoes':
                $projetoId = $_GET['projeto_id'] ? (int)$_GET['projeto_id'] : null;
                $status = $_GET['status'] ?? null;
                $alteracoes = $dev->listarAlteracoes($projetoId, $status);
                jsonResponse(['success' => true, 'data' => $alteracoes]);
                break;

            case 'alteracao':
                $id = (int)($_GET['id'] ?? 0);
                $alt = $dev->findAlteracao($id);
                if (!$alt) jsonResponse(['error' => 'Alteração não encontrada'], 404);
                jsonResponse(['success' => true, 'data' => $alt]);
                break;

            // ===== STATS =====
            case 'stats':
                $stats = $dev->getStats($userId);
                jsonResponse(['success' => true, 'data' => $stats]);
                break;

            // ===== PROTECTED PATHS =====
            case 'protected_paths':
                jsonResponse(['success' => true, 'data' => IADev::getProtectedPatterns()]);
                break;

            // ===== CHECK PATH PROTECTION =====
            case 'check_path':
                $path = $_GET['path'] ?? '';
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'path' => $path,
                        'protected' => IADev::isProtectedPath($path),
                    ]
                ]);
                break;

            // ===== CONTEXTO (para system prompt) =====
            case 'contexto':
                $projetoId = (int)($_GET['projeto_id'] ?? 0);
                $projeto = $dev->findProjeto($projetoId);
                if (!$projeto) jsonResponse(['error' => 'Projeto não encontrado'], 404);
                $systemPrompt = $dev->getDevSystemPrompt($projeto);
                jsonResponse(['success' => true, 'data' => ['system_prompt' => $systemPrompt]]);
                break;

            default:
                jsonResponse(['error' => 'Ação desconhecida'], 400);
        }

    } elseif ($method === 'POST') {
        $jsonBody = file_get_contents('php://input');
        $data = $jsonBody ? json_decode($jsonBody, true) : $_POST;
        $action = $data['action'] ?? '';

        switch ($action) {
            // ===== CRIAR PROJETO =====
            case 'novo_projeto':
                if (empty($data['nome'])) jsonResponse(['error' => 'Nome é obrigatório'], 400);
                $id = $dev->criarProjeto([
                    'nome' => $data['nome'],
                    'descricao' => $data['descricao'] ?? null,
                    'tipo' => $data['tipo'] ?? 'externo',
                    'stack' => $data['stack'] ?? null,
                ]);
                jsonResponse(['success' => true, 'data' => ['id' => $id, 'message' => 'Projeto criado']]);
                break;

            // ===== SALVAR ARQUIVOS DA IA =====
            case 'salvar_arquivos':
                $projetoId = (int)($data['projeto_id'] ?? 0);
                $arquivos = $data['arquivos'] ?? [];
                $conversaId = $data['conversa_id'] ?? null;
                $titulo = $data['titulo'] ?? 'Gerado pela IA';
                // Admin pode forçar override de caminhos protegidos
                $forceOverride = !empty($data['force_override']) && $user['tipo'] === 'admin';

                if (!$projetoId || empty($arquivos)) {
                    jsonResponse(['error' => 'Projeto e arquivos são obrigatórios'], 400);
                }

                $result = $dev->salvarArquivosLote($projetoId, $arquivos, $conversaId, $titulo, $forceOverride);
                jsonResponse(['success' => true, 'data' => $result]);
                break;

            // ===== TESTAR ALTERAÇÃO =====
            case 'testar':
                if (!in_array($user['tipo'], ['admin', 'tecnico'])) {
                    jsonResponse(['error' => 'Sem permissão'], 403);
                }
                $id = (int)($data['id'] ?? 0);
                $forceOverride = !empty($data['force_override']) && $user['tipo'] === 'admin';
                $resultados = $dev->testarAlteracao($id, $forceOverride);
                jsonResponse(['success' => true, 'data' => $resultados, 'message' => 'Alteração deployada para teste']);
                break;

            // ===== CANCELAR TESTE =====
            case 'cancelar_teste':
                if (!in_array($user['tipo'], ['admin', 'tecnico'])) {
                    jsonResponse(['error' => 'Sem permissão'], 403);
                }
                $id = (int)($data['id'] ?? 0);
                $dev->cancelarTeste($id);
                jsonResponse(['success' => true, 'message' => 'Teste cancelado, arquivos revertidos']);
                break;

            // ===== APROVAR ALTERAÇÃO =====
            case 'aprovar':
                if (!in_array($user['tipo'], ['admin', 'tecnico'])) {
                    jsonResponse(['error' => 'Sem permissão'], 403);
                }
                $id = (int)($data['id'] ?? 0);
                $notas = $data['notas'] ?? null;
                $result = $dev->aprovarAlteracao($id, $userId, $notas);
                $msg = ($result['from_test'] ?? false) ? 'Alteração aprovada e aplicada!' : 'Alteração aprovada';
                jsonResponse(['success' => true, 'data' => $result, 'message' => $msg]);
                break;

            // ===== APLICAR ALTERAÇÃO =====
            case 'aplicar':
                if (!in_array($user['tipo'], ['admin', 'tecnico'])) {
                    jsonResponse(['error' => 'Sem permissão'], 403);
                }
                $id = (int)($data['id'] ?? 0);
                $forceOverride = !empty($data['force_override']) && $user['tipo'] === 'admin';
                $resultados = $dev->aplicarAlteracao($id, $forceOverride);
                jsonResponse(['success' => true, 'data' => $resultados, 'message' => 'Alteração aplicada ao codebase']);
                break;

            // ===== REJEITAR =====
            case 'rejeitar':
                $id = (int)($data['id'] ?? 0);
                $notas = $data['notas'] ?? null;
                $dev->rejeitarAlteracao($id, $notas);
                jsonResponse(['success' => true, 'message' => 'Alteração rejeitada']);
                break;

            // ===== REVERTER =====
            case 'reverter':
                if (!in_array($user['tipo'], ['admin', 'tecnico'])) {
                    jsonResponse(['error' => 'Sem permissão'], 403);
                }
                $id = (int)($data['id'] ?? 0);
                $dev->reverterAlteracao($id);
                jsonResponse(['success' => true, 'message' => 'Alteração revertida']);
                break;

            // ===== DOWNLOAD ZIP =====
            case 'download':
                $projetoId = (int)($data['projeto_id'] ?? 0);
                $zipPath = $dev->exportarProjetoZip($projetoId);
                $projeto = $dev->findProjeto($projetoId);
                $nome = preg_replace('/[^a-zA-Z0-9_-]/', '_', $projeto['nome'] ?? 'projeto');
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $nome . '.zip"');
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);
                unlink($zipPath);
                exit;

            // ===== ARQUIVAR PROJETO =====
            case 'arquivar_projeto':
                $id = (int)($data['id'] ?? 0);
                $projeto = $dev->findProjeto($id);
                if (!$projeto) jsonResponse(['error' => 'Projeto não encontrado'], 404);
                if ($projeto['tipo'] === 'interno') jsonResponse(['error' => 'Não é possível arquivar o projeto interno'], 400);
                
                $db = Database::getInstance();
                $db->update('ia_dev_projetos', ['status' => 'arquivado'], 'id = ?', [$id]);
                jsonResponse(['success' => true, 'message' => 'Projeto arquivado']);
                break;

            default:
                jsonResponse(['error' => 'Ação desconhecida'], 400);
        }
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
