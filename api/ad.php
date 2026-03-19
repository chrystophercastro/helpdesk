<?php
/**
 * API: Active Directory
 * Gerenciamento de usuários, grupos e OUs do AD
 * Acesso restrito a administradores
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

// Apenas admin pode gerenciar AD
if ($_SESSION['usuario_tipo'] !== 'admin') {
    jsonResponse(['error' => 'Sem permissão. Apenas administradores podem gerenciar o Active Directory.'], 403);
}

require_once __DIR__ . '/../app/models/ActiveDirectory.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['usuario_id'];
$db = Database::getInstance();

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
            // ===== CONFIGURAÇÃO =====
            case 'salvar_config':
                $campos = ['ad_server', 'ad_porta', 'ad_base_dn', 'ad_admin_user', 'ad_dominio', 'ad_ssl'];

                foreach ($campos as $campo) {
                    $valor = trim($data[$campo] ?? '');
                    $existe = $db->fetch("SELECT id FROM configuracoes WHERE chave = ?", [$campo]);
                    if ($existe) {
                        $db->update('configuracoes', ['valor' => $valor], 'chave = ?', [$campo]);
                    } else {
                        $db->insert('configuracoes', ['chave' => $campo, 'valor' => $valor]);
                    }
                }

                // Senha separada - só salvar se preenchida
                if (!empty($data['ad_admin_pass'])) {
                    $existe = $db->fetch("SELECT id FROM configuracoes WHERE chave = 'ad_admin_pass'", []);
                    if ($existe) {
                        $db->update('configuracoes', ['valor' => $data['ad_admin_pass']], "chave = 'ad_admin_pass'", []);
                    } else {
                        $db->insert('configuracoes', ['chave' => 'ad_admin_pass', 'valor' => $data['ad_admin_pass']]);
                    }
                }

                // Log
                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'ad_config_salva',
                    'entidade_tipo' => 'ad',
                    'entidade_id' => 0,
                    'detalhes' => 'Configuração do AD atualizada',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                jsonResponse(['success' => true, 'message' => 'Configuração salva com sucesso!']);
                break;

            case 'testar_conexao':
                $ad = new ActiveDirectory();
                $result = $ad->testarConexao();
                jsonResponse($result);
                break;

            // ===== USUÁRIOS =====
            case 'criar_usuario':
                $campos_obrigatorios = ['login', 'primeiro_nome', 'sobrenome', 'senha'];
                foreach ($campos_obrigatorios as $c) {
                    if (empty(trim($data[$c] ?? ''))) {
                        jsonResponse(['error' => "Campo obrigatório não preenchido: {$c}"], 400);
                    }
                }

                $ad = new ActiveDirectory();
                $ad->connect();

                $dn = $ad->criarUsuario([
                    'login' => trim($data['login']),
                    'primeiro_nome' => trim($data['primeiro_nome']),
                    'sobrenome' => trim($data['sobrenome']),
                    'email' => trim($data['email'] ?? ''),
                    'cargo' => trim($data['cargo'] ?? ''),
                    'departamento' => trim($data['departamento'] ?? ''),
                    'telefone' => trim($data['telefone'] ?? ''),
                    'descricao' => trim($data['descricao'] ?? ''),
                    'empresa' => trim($data['empresa'] ?? ''),
                    'senha' => $data['senha'],
                    'ou_dn' => $data['ou_dn'] ?? '',
                    'trocar_senha_proximo_login' => !empty($data['trocar_senha']),
                ]);

                // Adicionar a grupos selecionados
                if (!empty($data['grupos']) && is_array($data['grupos'])) {
                    foreach ($data['grupos'] as $grupoDn) {
                        try {
                            $ad->adicionarAoGrupo($dn, $grupoDn);
                        } catch (Exception $e) {
                            // Continuar mesmo se falhar um grupo
                        }
                    }
                }

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'ad_usuario_criado',
                    'entidade_tipo' => 'ad',
                    'entidade_id' => 0,
                    'detalhes' => "Usuário AD criado: {$data['login']}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                $ad->disconnect();
                jsonResponse(['success' => true, 'message' => 'Usuário criado com sucesso!', 'dn' => $dn]);
                break;

            case 'resetar_senha':
                if (empty($data['dn']) || empty($data['nova_senha'])) {
                    jsonResponse(['error' => 'DN e nova senha são obrigatórios'], 400);
                }

                $ad = new ActiveDirectory();
                $ad->connect();

                $forcarTroca = !empty($data['forcar_troca']);
                $ad->resetarSenha($data['dn'], $data['nova_senha'], $forcarTroca);

                // Extrair login do DN
                $login = '';
                if (preg_match('/CN=([^,]+)/i', $data['dn'], $m)) $login = $m[1];

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => 'ad_senha_resetada',
                    'entidade_tipo' => 'ad',
                    'entidade_id' => 0,
                    'detalhes' => "Senha resetada para: {$login}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                $ad->disconnect();
                jsonResponse(['success' => true, 'message' => 'Senha resetada com sucesso!']);
                break;

            case 'toggle_conta':
                if (empty($data['dn'])) {
                    jsonResponse(['error' => 'DN é obrigatório'], 400);
                }

                $ad = new ActiveDirectory();
                $ad->connect();

                $habilitar = !empty($data['habilitar']);
                $ad->toggleConta($data['dn'], $habilitar);

                $login = '';
                if (preg_match('/CN=([^,]+)/i', $data['dn'], $m)) $login = $m[1];

                $db->insert('logs', [
                    'usuario_id' => $userId,
                    'acao' => $habilitar ? 'ad_conta_habilitada' : 'ad_conta_desabilitada',
                    'entidade_tipo' => 'ad',
                    'entidade_id' => 0,
                    'detalhes' => ($habilitar ? 'Conta habilitada' : 'Conta desabilitada') . ": {$login}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                $ad->disconnect();
                jsonResponse(['success' => true, 'message' => 'Conta ' . ($habilitar ? 'habilitada' : 'desabilitada') . ' com sucesso!']);
                break;

            case 'desbloquear_conta':
                if (empty($data['dn'])) {
                    jsonResponse(['error' => 'DN é obrigatório'], 400);
                }

                $ad = new ActiveDirectory();
                $ad->connect();
                $ad->desbloquearConta($data['dn']);
                $ad->disconnect();

                jsonResponse(['success' => true, 'message' => 'Conta desbloqueada com sucesso!']);
                break;

            // ===== GRUPOS =====
            case 'adicionar_grupo':
                if (empty($data['user_dn']) || empty($data['grupo_dn'])) {
                    jsonResponse(['error' => 'Usuário e grupo são obrigatórios'], 400);
                }

                $ad = new ActiveDirectory();
                $ad->connect();
                $ad->adicionarAoGrupo($data['user_dn'], $data['grupo_dn']);
                $ad->disconnect();

                jsonResponse(['success' => true, 'message' => 'Usuário adicionado ao grupo!']);
                break;

            case 'remover_grupo':
                if (empty($data['user_dn']) || empty($data['grupo_dn'])) {
                    jsonResponse(['error' => 'Usuário e grupo são obrigatórios'], 400);
                }

                $ad = new ActiveDirectory();
                $ad->connect();
                $ad->removerDoGrupo($data['user_dn'], $data['grupo_dn']);
                $ad->disconnect();

                jsonResponse(['success' => true, 'message' => 'Usuário removido do grupo!']);
                break;

            case 'membros_grupo':
                if (empty($data['grupo_dn'])) {
                    jsonResponse(['error' => 'DN do grupo é obrigatório'], 400);
                }

                $ad = new ActiveDirectory();
                $ad->connect();
                $membros = $ad->listarMembrosGrupo($data['grupo_dn']);
                $ad->disconnect();

                jsonResponse(['success' => true, 'data' => $membros]);
                break;

            case 'gerar_senha':
                $senha = ActiveDirectory::gerarSenha(14);
                jsonResponse(['success' => true, 'senha' => $senha]);
                break;

            default:
                jsonResponse(['error' => 'Ação inválida'], 400);
        }

    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'config':
                $ad = new ActiveDirectory();
                jsonResponse(['success' => true, 'data' => $ad->getConfig()]);
                break;

            case 'usuarios':
                $ad = new ActiveDirectory();
                $ad->connect();
                $usuarios = $ad->listarUsuarios(
                    $_GET['ou_dn'] ?? null,
                    $_GET['busca'] ?? ''
                );
                $ad->disconnect();
                jsonResponse(['success' => true, 'data' => $usuarios]);
                break;

            case 'usuario':
                if (empty($_GET['dn'])) {
                    jsonResponse(['error' => 'DN é obrigatório'], 400);
                }
                $ad = new ActiveDirectory();
                $ad->connect();
                $usuario = $ad->getUsuario($_GET['dn']);
                $ad->disconnect();
                jsonResponse(['success' => true, 'data' => $usuario]);
                break;

            case 'grupos':
                $ad = new ActiveDirectory();
                $ad->connect();
                $grupos = $ad->listarGrupos($_GET['busca'] ?? '');
                $ad->disconnect();
                jsonResponse(['success' => true, 'data' => $grupos]);
                break;

            case 'ous':
                $ad = new ActiveDirectory();
                $ad->connect();
                $ous = $ad->listarOUs();
                $ad->disconnect();
                jsonResponse(['success' => true, 'data' => $ous]);
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
