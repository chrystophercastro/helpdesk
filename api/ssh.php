<?php
/**
 * API: Terminal SSH
 * Gerenciamento de servidores SSH e execução remota de comandos
 * Acesso restrito a admin e técnico
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

if (!in_array($_SESSION['usuario_tipo'], ['admin', 'tecnico'])) {
    jsonResponse(['error' => 'Sem permissão'], 403);
}

require_once __DIR__ . '/../app/models/SSH.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['usuario_id'];
$db = Database::getInstance();
$ssh = new SSH();

try {
    if ($method === 'POST') {
        if (!empty($_POST)) {
            $data = $_POST;
        } else {
            $jsonBody = file_get_contents('php://input');
            $data = $jsonBody ? json_decode($jsonBody, true) : [];
        }
        unset($data['_csrf']);
        $action = $data['action'] ?? '';

        switch ($action) {
            // ===== CRUD SERVIDORES =====
            case 'criar':
                if (empty(trim($data['nome'] ?? '')) || empty(trim($data['host'] ?? '')) || empty(trim($data['usuario'] ?? ''))) {
                    jsonResponse(['error' => 'Nome, host e usuário são obrigatórios'], 400);
                }

                $host = trim($data['host']);
                if (!filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    jsonResponse(['error' => 'Host inválido (IP ou hostname)'], 400);
                }

                $data['criado_por'] = $userId;
                $id = $ssh->criar($data);

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'servidor_ssh_criado',
                    'entidade_tipo' => 'servidor_ssh',
                    'entidade_id' => $id,
                    'detalhes' => "Servidor SSH cadastrado: {$data['nome']} ({$host})",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                jsonResponse(['success' => true, 'message' => 'Servidor cadastrado com sucesso!', 'id' => $id]);
                break;

            case 'atualizar':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }

                $ssh->atualizar($data['id'], $data);
                jsonResponse(['success' => true, 'message' => 'Servidor atualizado!']);
                break;

            case 'excluir':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }

                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin pode excluir servidores'], 403);
                }

                $servidor = $ssh->findById($data['id']);
                $ssh->excluir($data['id']);

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'servidor_ssh_excluido',
                    'entidade_tipo' => 'servidor_ssh',
                    'entidade_id' => $data['id'],
                    'detalhes' => "Servidor SSH excluído: " . ($servidor['nome'] ?? ''),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                jsonResponse(['success' => true, 'message' => 'Servidor excluído!']);
                break;

            // ===== CONEXÃO =====
            case 'testar':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }

                $resultado = $ssh->testarConexao($data['id']);
                jsonResponse(['success' => true, 'data' => $resultado]);
                break;

            case 'testar_todos':
                $resultados = $ssh->testarTodos();
                jsonResponse(['success' => true, 'data' => $resultados]);
                break;

            // ===== EXECUÇÃO DE COMANDOS =====
            case 'executar':
                if (empty($data['servidor_id'])) {
                    jsonResponse(['error' => 'Selecione um servidor'], 400);
                }
                if (empty(trim($data['comando'] ?? ''))) {
                    jsonResponse(['error' => 'Informe um comando'], 400);
                }

                $comando = trim($data['comando']);
                $servidorId = (int)$data['servidor_id'];
                $timeout = min(120, max(5, (int)($data['timeout'] ?? 30)));
                $useSudo = !empty($data['use_sudo']);
                $sudoPassword = $data['sudo_password'] ?? null;

                $start = microtime(true);
                $resultado = $ssh->executarComando($servidorId, $comando, $timeout, $useSudo, $sudoPassword);
                $duracao = round((microtime(true) - $start) * 1000);

                // Logar comando
                $ssh->logComando(
                    $servidorId,
                    $userId,
                    $comando,
                    ($resultado['output'] ?? '') . ($resultado['error'] ?? ''),
                    $resultado['exit_code'] ?? 0,
                    $duracao
                );

                jsonResponse([
                    'success' => true,
                    'data' => [
                        'output' => $resultado['output'] ?? '',
                        'error' => $resultado['error'] ?? '',
                        'exit_code' => $resultado['exit_code'] ?? 0,
                        'duracao_ms' => $duracao,
                    ]
                ]);
                break;

            // ===== INFO DO SISTEMA =====
            case 'info_sistema':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }

                $info = $ssh->getInfoSistema($data['id']);
                jsonResponse(['success' => true, 'data' => $info]);
                break;

            // ===== COMANDOS SALVOS =====
            case 'salvar_comando':
                if (empty(trim($data['nome'] ?? '')) || empty(trim($data['comando'] ?? ''))) {
                    jsonResponse(['error' => 'Nome e comando são obrigatórios'], 400);
                }

                $data['criado_por'] = $userId;
                $id = $ssh->salvarComando($data);
                jsonResponse(['success' => true, 'message' => 'Comando salvo!', 'id' => $id]);
                break;

            case 'excluir_comando':
                if (empty($data['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }
                $ssh->excluirComando($data['id']);
                jsonResponse(['success' => true, 'message' => 'Comando excluído!']);
                break;

            // ===== INSTALAÇÃO SSH2 =====
            case 'install_ssh2':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin pode instalar extensões'], 403);
                }

                set_time_limit(120); // Download pode demorar
                $resultado = SSH::autoInstallSSH2();

                if ($resultado['success']) {
                    $db->insert('logs', [
                        'usuario_id' => $userId,
                        'acao' => 'ssh2_instalado',
                        'entidade_tipo' => 'sistema',
                        'entidade_id' => 0,
                        'detalhes' => 'Extensão SSH2 instalada automaticamente',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                }

                jsonResponse($resultado);
                break;

            case 'restart_apache':
                if ($_SESSION['usuario_tipo'] !== 'admin') {
                    jsonResponse(['error' => 'Apenas admin pode reiniciar serviços'], 403);
                }

                $resultado = SSH::restartApache();

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'apache_restart',
                    'entidade_tipo' => 'sistema',
                    'entidade_id' => 0,
                    'detalhes' => 'Tentativa de reiniciar Apache: ' . ($resultado['success'] ? 'OK' : 'Falha'),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                jsonResponse($resultado);
                break;

            default:
                jsonResponse(['error' => 'Ação inválida'], 400);
        }

    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'listar':
                $filtros = [
                    'busca' => $_GET['busca'] ?? '',
                    'grupo' => $_GET['grupo'] ?? '',
                    'status' => $_GET['status'] ?? '',
                ];
                $servidores = $ssh->listar($filtros);

                // Remover credenciais da resposta
                foreach ($servidores as &$s) {
                    unset($s['credencial'], $s['passphrase']);
                }

                jsonResponse(['success' => true, 'data' => $servidores]);
                break;

            case 'servidor':
                if (empty($_GET['id'])) {
                    jsonResponse(['error' => 'ID é obrigatório'], 400);
                }
                $s = $ssh->findById($_GET['id']);
                if (!$s) jsonResponse(['error' => 'Não encontrado'], 404);

                // Remover credenciais
                unset($s['credencial'], $s['passphrase']);

                jsonResponse(['success' => true, 'data' => $s]);
                break;

            case 'estatisticas':
                $stats = $ssh->getEstatisticas();
                jsonResponse(['success' => true, 'data' => $stats]);
                break;

            case 'grupos':
                $grupos = $ssh->getGrupos();
                jsonResponse(['success' => true, 'data' => $grupos]);
                break;

            case 'historico':
                if (empty($_GET['servidor_id'])) {
                    jsonResponse(['error' => 'servidor_id é obrigatório'], 400);
                }
                $historico = $ssh->getHistoricoComandos($_GET['servidor_id'], (int)($_GET['limite'] ?? 50));
                jsonResponse(['success' => true, 'data' => $historico]);
                break;

            case 'comandos_salvos':
                $comandos = $ssh->getComandosSalvos();
                jsonResponse(['success' => true, 'data' => $comandos]);
                break;

            case 'check_ssh2':
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'available' => SSH::isSSH2Available(),
                        'instructions' => SSH::getSSH2Instructions(),
                        'php_version' => PHP_VERSION,
                        'os' => PHP_OS_FAMILY,
                    ]
                ]);
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
