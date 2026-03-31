<?php
/**
 * Oracle X - Login
 */
session_start();

// Redirecionar para setup se não estiver instalado
if (!file_exists(__DIR__ . '/config/.installed')) {
    header('Location: /helpdesk/setup.php');
    exit;
}

require_once __DIR__ . '/config/app.php';

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $auth = new AuthController();
    $auth->logout();
}

// Se já logado, redirecionar
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $auth = new AuthController();
    $result = $auth->login();
    
    if (isset($result['success'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    $error = $result['error'] ?? 'Erro ao fazer login.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= $_SESSION['usuario_tema'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Oracle X</title>
    <script>!function(){var t=localStorage.getItem('tema');if(t)document.documentElement.setAttribute('data-theme',t)}()</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dark.css">
    <style>
        body {
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 30%, #4338CA 60%, #6366F1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 36px;
        }
        .login-logo i {
            font-size: 48px;
            color: #4F46E5;
            margin-bottom: 12px;
            display: block;
        }
        .login-logo h1 {
            font-size: 26px;
            font-weight: 800;
            color: #1E1B4B;
            margin: 0;
        }
        .login-logo p {
            color: #6B7280;
            font-size: 14px;
            margin-top: 4px;
        }
        .login-form .form-group { margin-bottom: 20px; }
        .login-form label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .login-form .input-wrapper {
            position: relative;
        }
        .login-form .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 15px;
        }
        .login-form input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .login-form input:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }
        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.4);
        }
        .login-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #DC2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .login-footer {
            text-align: center;
            margin-top: 24px;
        }
        .login-footer a {
            color: #4F46E5;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        .login-demo {
            text-align: center;
            margin-top: 20px;
            padding: 16px;
            background: #F0F0FF;
            border-radius: 12px;
            font-size: 12px;
            color: #6B7280;
        }
        .login-demo strong { color: #4F46E5; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="fas fa-bolt"></i>
                <h1>Oracle X</h1>
                <p>Sistema de Gerenciamento Corporativo</p>
            </div>

            <?php if ($error): ?>
            <div class="login-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required placeholder="seu@email.com" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="senha" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>

            <div class="login-footer">
                <a href="<?= BASE_URL ?>/portal/">Abrir chamado sem login →</a>
            </div>

        </div>
    </div>
</body>
</html>
