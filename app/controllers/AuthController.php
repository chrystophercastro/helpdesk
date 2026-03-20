<?php
/**
 * Controller: Auth
 */
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $usuario;

    public function __construct() {
        $this->usuario = new Usuario();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitizar($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';

            if (empty($email) || empty($senha)) {
                return ['error' => 'Preencha todos os campos.'];
            }

            $user = $this->usuario->autenticar($email, $senha);
            if ($user) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['usuario_telefone'] = $user['telefone'];
                $_SESSION['usuario_tipo'] = $user['tipo'];
                $_SESSION['usuario_avatar'] = $user['avatar'];
                $_SESSION['usuario_departamento_id'] = $user['departamento_id'] ?? null;

                // Salvar sigla do departamento na sessão
                $db = Database::getInstance();
                $dept = $db->fetch("SELECT sigla FROM departamentos WHERE id = ?", [$user['departamento_id'] ?? 0]);
                $_SESSION['usuario_departamento_sigla'] = $dept['sigla'] ?? null;

                // Log
                $db = Database::getInstance();
                $db->insert('logs', [
                    'usuario_id' => $user['id'],
                    'acao' => 'login',
                    'detalhes' => 'Login realizado com sucesso',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                return ['success' => true];
            }

            return ['error' => 'Email ou senha inválidos.'];
        }
        return null;
    }

    public function logout() {
        if (isset($_SESSION['usuario_id'])) {
            $db = Database::getInstance();
            $db->insert('logs', [
                'usuario_id' => $_SESSION['usuario_id'],
                'acao' => 'logout',
                'detalhes' => 'Logout realizado',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
