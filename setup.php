<?php
/**
 * Oracle X — Assistente de Instalação (Setup Wizard)
 * Executa configuração inicial: testa BD, cria tabelas, configura admin e empresa.
 */
session_start();

// ── Segurança: bloquear se já instalado ──
$lockFile = __DIR__ . '/config/.installed';
if (file_exists($lockFile)) {
    header('Location: /helpdesk/login.php');
    exit;
}

// ── Variáveis ──
$step     = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$errors   = [];
$success  = [];
$totalSteps = 4;

// ── Processar cada passo ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ▸ STEP 1 — Requisitos do servidor
    if ($step === 1) {
        $step = 2; // apenas avança
    }

    // ▸ STEP 2 — Conexão com banco de dados
    elseif ($step === 2) {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? 'helpdesk_ti');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = $_POST['db_pass'] ?? '';

        // Testar conexão
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Criar database se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Executar database.sql
            $sqlFile = __DIR__ . '/database.sql';
            if (!file_exists($sqlFile)) {
                $errors[] = 'Arquivo database.sql não encontrado na raiz do projeto.';
            } else {
                $sql = file_get_contents($sqlFile);

                // Remover comandos CREATE DATABASE e USE (já fizemos acima)
                $sql = preg_replace('/CREATE DATABASE.*?;/si', '', $sql);
                $sql = preg_replace('/USE\s+`.*?`;/si', '', $sql);

                // Separar por statements
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

                // Executar tudo de uma vez via multi-statement
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

                // Quebrar em statements individuais (mais seguro)
                $statements = array_filter(
                    array_map('trim', preg_split('/;\s*\n/', $sql)),
                    fn($s) => !empty($s) && !preg_match('/^\s*(--)/', $s) && strlen($s) > 5
                );

                $tablesCreated = 0;
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (empty($stmt) || $stmt === 'SET FOREIGN_KEY_CHECKS = 0' || $stmt === 'SET FOREIGN_KEY_CHECKS = 1') continue;
                    try {
                        $pdo->exec($stmt);
                        if (stripos($stmt, 'CREATE TABLE') !== false) $tablesCreated++;
                    } catch (PDOException $e) {
                        // Ignorar erros de tabela já existente
                        if (strpos($e->getMessage(), 'already exists') === false &&
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            $errors[] = 'SQL Error: ' . mb_substr($e->getMessage(), 0, 200);
                        }
                    }
                }

                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

                if (empty($errors)) {
                    // Salvar configuração do banco
                    $configContent = "<?php\n";
                    $configContent .= "/**\n * Configuração do Banco de Dados\n * Oracle X\n */\n\n";
                    $configContent .= "define('DB_HOST', " . var_export($dbHost, true) . ");\n";
                    $configContent .= "define('DB_NAME', " . var_export($dbName, true) . ");\n";
                    $configContent .= "define('DB_USER', " . var_export($dbUser, true) . ");\n";
                    $configContent .= "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
                    $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
                    $configContent .= "define('DB_PORT', {$dbPort});\n";

                    file_put_contents(__DIR__ . '/config/database.php', $configContent);

                    $_SESSION['setup_db'] = [
                        'host' => $dbHost,
                        'port' => $dbPort,
                        'name' => $dbName,
                        'user' => $dbUser,
                        'pass' => $dbPass,
                        'tables' => $tablesCreated
                    ];
                    $success[] = "Banco de dados configurado! {$tablesCreated} tabelas criadas.";
                    $step = 3;
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Falha na conexão: ' . $e->getMessage();
            $step = 2;
        }
    }

    // ▸ STEP 3 — Configurar administrador + empresa
    elseif ($step === 3) {
        $adminNome     = trim($_POST['admin_nome'] ?? '');
        $adminEmail    = trim($_POST['admin_email'] ?? '');
        $adminTelefone = trim($_POST['admin_telefone'] ?? '');
        $adminSenha    = $_POST['admin_senha'] ?? '';
        $adminSenha2   = $_POST['admin_senha2'] ?? '';
        $empresaNome   = trim($_POST['empresa_nome'] ?? 'Oracle X');

        // Validações
        if (empty($adminNome))  $errors[] = 'Nome do administrador é obrigatório.';
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (empty($adminTelefone)) $errors[] = 'Telefone é obrigatório.';
        if (strlen($adminSenha) < 6) $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
        if ($adminSenha !== $adminSenha2) $errors[] = 'As senhas não coincidem.';

        if (empty($errors)) {
            try {
                $db = $_SESSION['setup_db'];
                $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                $senhaHash = password_hash($adminSenha, PASSWORD_BCRYPT, ['cost' => 12]);

                // Remover admin padrão do seed e inserir o novo
                $pdo->exec("DELETE FROM usuarios WHERE email = 'admin@helpdesk.com'");

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, telefone, senha, tipo, cargo, departamento)
                    VALUES (?, ?, ?, ?, 'admin', 'Administrador', 'TI')
                    ON DUPLICATE KEY UPDATE nome = VALUES(nome), senha = VALUES(senha), telefone = VALUES(telefone)
                ");
                $stmt->execute([$adminNome, $adminEmail, $adminTelefone, $senhaHash]);

                // Atualizar nome da empresa
                $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'empresa_nome'")
                    ->execute([$empresaNome]);

                $_SESSION['setup_admin_email'] = $adminEmail;
                $step = 4;
            } catch (PDOException $e) {
                $errors[] = 'Erro ao criar administrador: ' . $e->getMessage();
                $step = 3;
            }
        } else {
            $step = 3;
        }
    }

    // ▸ STEP 4 — Finalizar
    elseif ($step === 4) {
        // Criar arquivo de lock
        file_put_contents($lockFile, date('Y-m-d H:i:s') . ' - Instalação concluída');

        // Limpar sessão do setup
        unset($_SESSION['setup_db']);

        header('Location: /helpdesk/login.php');
        exit;
    }
}

// ── Check de requisitos (para step 1) ──
$requirements = [
    ['label' => 'PHP 8.0+',             'ok' => version_compare(PHP_VERSION, '8.0.0', '>='), 'value' => PHP_VERSION],
    ['label' => 'Extensão PDO',          'ok' => extension_loaded('pdo'),       'value' => extension_loaded('pdo') ? 'Instalada' : 'Ausente'],
    ['label' => 'PDO MySQL',             'ok' => extension_loaded('pdo_mysql'), 'value' => extension_loaded('pdo_mysql') ? 'Instalada' : 'Ausente'],
    ['label' => 'Extensão cURL',         'ok' => extension_loaded('curl'),      'value' => extension_loaded('curl') ? 'Instalada' : 'Ausente'],
    ['label' => 'Extensão mbstring',     'ok' => extension_loaded('mbstring'),  'value' => extension_loaded('mbstring') ? 'Instalada' : 'Ausente'],
    ['label' => 'Extensão JSON',         'ok' => extension_loaded('json'),      'value' => extension_loaded('json') ? 'Instalada' : 'Ausente'],
    ['label' => 'Extensão fileinfo',     'ok' => extension_loaded('fileinfo'),  'value' => extension_loaded('fileinfo') ? 'Instalada' : 'Ausente'],
    ['label' => 'config/ gravável',      'ok' => is_writable(__DIR__ . '/config'), 'value' => is_writable(__DIR__ . '/config') ? 'OK' : 'Sem permissão'],
    ['label' => 'uploads/ gravável',     'ok' => is_dir(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads'), 'value' => (is_dir(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads')) ? 'OK' : 'Sem permissão'],
    ['label' => 'database.sql presente', 'ok' => file_exists(__DIR__ . '/database.sql'), 'value' => file_exists(__DIR__ . '/database.sql') ? 'Encontrado' : 'Não encontrado'],
];
$allReqOk = !in_array(false, array_column($requirements, 'ok'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação — Oracle X</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #818CF8;
            --primary-bg: #EEF2FF;
            --success: #10B981;
            --success-bg: #ECFDF5;
            --danger: #EF4444;
            --danger-bg: #FEF2F2;
            --warning: #F59E0B;
            --warning-bg: #FFFBEB;
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-400: #94A3B8;
            --gray-500: #64748B;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1E293B;
            --gray-900: #0F172A;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 20px 50px rgba(0,0,0,0.15);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 30%, #4338CA 60%, #6366F1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--gray-800);
            -webkit-font-smoothing: antialiased;
        }
        .setup-wrapper {
            width: 100%;
            max-width: 680px;
        }

        /* ── Header ── */
        .setup-header {
            text-align: center;
            margin-bottom: 32px;
            color: #fff;
        }
        .setup-header i.logo {
            font-size: 44px;
            color: var(--primary-light);
            margin-bottom: 12px;
            display: block;
            filter: drop-shadow(0 4px 12px rgba(99,102,241,0.5));
        }
        .setup-header h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .setup-header p {
            font-size: 14px;
            color: rgba(255,255,255,0.65);
        }

        /* ── Stepper ── */
        .stepper {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-bottom: 28px;
            position: relative;
        }
        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }
        .stepper-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: calc(50% + 18px);
            width: calc(100% - 36px);
            height: 3px;
            background: rgba(255,255,255,0.15);
            z-index: 0;
        }
        .stepper-item.done:not(:last-child)::after {
            background: var(--success);
        }
        .stepper-item.active:not(:last-child)::after {
            background: linear-gradient(90deg, var(--primary-light), rgba(255,255,255,0.15));
        }
        .stepper-dot {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            border: 2px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: rgba(255,255,255,0.4);
            transition: all 0.3s;
            position: relative;
            z-index: 2;
        }
        .stepper-item.active .stepper-dot {
            background: var(--primary-light);
            border-color: #fff;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(129,140,248,0.3);
        }
        .stepper-item.done .stepper-dot {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }
        .stepper-label {
            font-size: 11px;
            font-weight: 500;
            color: rgba(255,255,255,0.35);
            margin-top: 8px;
            white-space: nowrap;
        }
        .stepper-item.active .stepper-label { color: rgba(255,255,255,0.85); }
        .stepper-item.done .stepper-label { color: rgba(255,255,255,0.6); }

        /* ── Card ── */
        .setup-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: cardIn 0.4s ease;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-top {
            padding: 28px 36px 20px;
            border-bottom: 1px solid var(--gray-200);
        }
        .card-top h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-top h2 i { color: var(--primary); font-size: 20px; }
        .card-top p {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 4px;
        }
        .card-body { padding: 28px 36px; }
        .card-footer {
            padding: 18px 36px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ── Form ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-grid .col-span-2 { grid-column: span 2; }
        .form-group { margin-bottom: 0; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            color: var(--gray-800);
            background: #fff;
            transition: all 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79,70,229,0.08);
        }
        .form-hint {
            font-size: 11px;
            color: var(--gray-400);
            margin-top: 4px;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: #fff;
        }
        .btn-primary:hover { box-shadow: 0 6px 20px rgba(79,70,229,0.4); }
        .btn-primary:disabled {
            opacity: 0.5; cursor: not-allowed; transform: none;
            box-shadow: none;
        }
        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }
        .btn-secondary:hover { background: var(--gray-300); }
        .btn-success {
            background: linear-gradient(135deg, #059669, #10B981);
            color: #fff;
        }
        .btn-success:hover { box-shadow: 0 6px 20px rgba(16,185,129,0.4); }

        /* ── Requirements Table ── */
        .req-table { width: 100%; border-collapse: collapse; }
        .req-table td {
            padding: 11px 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
        }
        .req-table tr:last-child td { border-bottom: none; }
        .req-table .req-label { font-weight: 500; color: var(--gray-700); }
        .req-table .req-value { color: var(--gray-500); text-align: center; font-size: 13px; }
        .req-table .req-status { text-align: right; width: 36px; }
        .req-icon {
            width: 24px; height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        .req-icon.ok { background: var(--success-bg); color: var(--success); }
        .req-icon.fail { background: var(--danger-bg); color: var(--danger); }

        /* ── Alerts ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
            line-height: 1.5;
        }
        .alert i { margin-top: 2px; flex-shrink: 0; }
        .alert-danger { background: var(--danger-bg); color: #991B1B; border: 1px solid #FECACA; }
        .alert-success { background: var(--success-bg); color: #065F46; border: 1px solid #A7F3D0; }
        .alert-warning { background: var(--warning-bg); color: #92400E; border: 1px solid #FDE68A; }

        /* ── Finish Step ── */
        .finish-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #059669, #10B981);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: #fff;
            box-shadow: 0 8px 30px rgba(16,185,129,0.3);
            animation: bounceIn 0.6s ease;
        }
        @keyframes bounceIn {
            0% { transform: scale(0); }
            60% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .finish-text { text-align: center; }
        .finish-text h3 { font-size: 22px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
        .finish-text p { font-size: 14px; color: var(--gray-500); margin-bottom: 6px; }
        .finish-credentials {
            margin: 24px auto 0;
            max-width: 340px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 18px 22px;
            text-align: left;
        }
        .finish-credentials .cred-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }
        .finish-credentials .cred-label { color: var(--gray-500); font-weight: 500; }
        .finish-credentials .cred-value { color: var(--gray-800); font-weight: 600; }

        /* ── DB test animation ── */
        .db-testing {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 14px;
            background: var(--primary-bg);
            border-radius: 10px;
            color: var(--primary);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 18px;
        }
        .db-testing.visible { display: flex; }
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .setup-wrapper { max-width: 100%; }
            .card-top, .card-body, .card-footer { padding-left: 20px; padding-right: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .col-span-2 { grid-column: span 1; }
            .stepper-label { font-size: 10px; }
            .stepper-dot { width: 28px; height: 28px; font-size: 11px; }
        }
    </style>
</head>
<body>
<div class="setup-wrapper">

    <!-- Header -->
    <div class="setup-header">
        <i class="fas fa-headset logo"></i>
        <h1>Oracle X</h1>
        <p>Assistente de Instalação</p>
    </div>

    <!-- Stepper -->
    <div class="stepper">
        <?php
        $stepLabels = ['Requisitos', 'Banco de Dados', 'Administrador', 'Concluído'];
        foreach ($stepLabels as $i => $label):
            $n = $i + 1;
            $class = $n < $step ? 'done' : ($n === $step ? 'active' : '');
        ?>
        <div class="stepper-item <?= $class ?>">
            <div class="stepper-dot">
                <?= $n < $step ? '<i class="fas fa-check"></i>' : $n ?>
            </div>
            <span class="stepper-label"><?= $label ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Card -->
    <div class="setup-card">
        <form method="POST" autocomplete="off">
            <input type="hidden" name="step" value="<?= $step ?>">

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                <div style="padding: 18px 36px 0;">
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($err) ?></span></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <?php foreach ($success as $msg): ?>
                <div style="padding: 18px 36px 0;">
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= htmlspecialchars($msg) ?></span></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- ═══════════ STEP 1: Requisitos ═══════════ -->
            <?php if ($step === 1): ?>
            <div class="card-top">
                <h2><i class="fas fa-clipboard-check"></i> Verificação de Requisitos</h2>
                <p>Verificando se o servidor atende aos requisitos mínimos do sistema.</p>
            </div>
            <div class="card-body">
                <table class="req-table">
                    <?php foreach ($requirements as $req): ?>
                    <tr>
                        <td class="req-label"><?= $req['label'] ?></td>
                        <td class="req-value"><?= htmlspecialchars($req['value']) ?></td>
                        <td class="req-status">
                            <span class="req-icon <?= $req['ok'] ? 'ok' : 'fail' ?>">
                                <i class="fas fa-<?= $req['ok'] ? 'check' : 'times' ?>"></i>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <?php if (!$allReqOk): ?>
                <div class="alert alert-warning" style="margin-top:18px">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Alguns requisitos não foram atendidos. Corrija-os antes de continuar.</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <span style="font-size:12px;color:var(--gray-400)">Passo 1 de <?= $totalSteps ?></span>
                <button type="submit" class="btn btn-primary" <?= !$allReqOk ? 'disabled' : '' ?>>
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <!-- ═══════════ STEP 2: Banco de Dados ═══════════ -->
            <?php elseif ($step === 2): ?>
            <div class="card-top">
                <h2><i class="fas fa-database"></i> Configuração do Banco de Dados</h2>
                <p>Informe os dados de acesso ao MySQL/MariaDB. O banco será criado automaticamente.</p>
            </div>
            <div class="card-body">
                <div id="dbTesting" class="db-testing">
                    <i class="fas fa-circle-notch spinner"></i>
                    <span>Conectando ao banco de dados e criando tabelas...</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Host do Servidor</label>
                        <input type="text" name="db_host" class="form-input" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Porta</label>
                        <input type="number" name="db_port" class="form-input" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Nome do Banco de Dados</label>
                        <input type="text" name="db_name" class="form-input" value="<?= htmlspecialchars($_POST['db_name'] ?? 'helpdesk_ti') ?>" required>
                        <span class="form-hint">Será criado automaticamente se não existir.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Usuário MySQL</label>
                        <input type="text" name="db_user" class="form-input" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Senha MySQL</label>
                        <input type="password" name="db_pass" class="form-input" value="" placeholder="Deixe vazio se não houver">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <span style="font-size:12px;color:var(--gray-400)">Passo 2 de <?= $totalSteps ?></span>
                <button type="submit" class="btn btn-primary" id="btnStep2" onclick="document.getElementById('dbTesting').classList.add('visible')">
                    <i class="fas fa-database"></i> Criar Banco &amp; Avançar
                </button>
            </div>

            <!-- ═══════════ STEP 3: Administrador ═══════════ -->
            <?php elseif ($step === 3): ?>
            <div class="card-top">
                <h2><i class="fas fa-user-shield"></i> Criar Administrador</h2>
                <p>Configure a conta do administrador principal e os dados da empresa.</p>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label">Nome da Empresa / Sistema</label>
                        <input type="text" name="empresa_nome" class="form-input" value="<?= htmlspecialchars($_POST['empresa_nome'] ?? 'Oracle X') ?>" required>
                    </div>

                    <div class="form-group col-span-2" style="border-top:1px solid var(--gray-200);padding-top:18px;margin-top:6px">
                        <label class="form-label" style="color:var(--primary);font-size:12px;text-transform:uppercase;letter-spacing:0.05em">
                            <i class="fas fa-user-shield"></i> Conta do Administrador
                        </label>
                    </div>

                    <div class="form-group col-span-2">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="admin_nome" class="form-input" value="<?= htmlspecialchars($_POST['admin_nome'] ?? '') ?>" required placeholder="Ex: João da Silva">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="admin_email" class="form-input" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required placeholder="admin@suaempresa.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone (WhatsApp)</label>
                        <input type="text" name="admin_telefone" class="form-input" value="<?= htmlspecialchars($_POST['admin_telefone'] ?? '') ?>" required placeholder="5562999999999">
                        <span class="form-hint">Formato: DDI + DDD + número (sem espaços)</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <input type="password" name="admin_senha" class="form-input" required placeholder="Mínimo 6 caracteres" minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="admin_senha2" class="form-input" required placeholder="Repita a senha" minlength="6">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <span style="font-size:12px;color:var(--gray-400)">Passo 3 de <?= $totalSteps ?></span>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Criar &amp; Finalizar
                </button>
            </div>

            <!-- ═══════════ STEP 4: Concluído ═══════════ -->
            <?php elseif ($step === 4): ?>
            <div class="card-top" style="border:none; padding-bottom:0;">
                <h2 style="justify-content:center"><i class="fas fa-trophy" style="color:#F59E0B"></i> Instalação Concluída!</h2>
            </div>
            <div class="card-body" style="text-align:center">
                <div class="finish-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="finish-text">
                    <h3>Tudo pronto! ðŸŽ‰</h3>
                    <p>O Oracle X foi instalado e configurado com sucesso.</p>
                    <p style="font-size:13px">Todas as tabelas foram criadas e seu administrador está cadastrado.</p>
                </div>

                <div class="finish-credentials">
                    <div style="font-size:12px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px">
                        <i class="fas fa-key"></i> Dados de Acesso
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">URL:</span>
                        <span class="cred-value">/helpdesk/login.php</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Email:</span>
                        <span class="cred-value"><?= htmlspecialchars($_SESSION['setup_admin_email'] ?? '-') ?></span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Senha:</span>
                        <span class="cred-value">a que você definiu</span>
                    </div>
                </div>

                <div class="alert alert-warning" style="margin-top:24px;text-align:left;max-width:400px;margin-left:auto;margin-right:auto">
                    <i class="fas fa-shield-alt"></i>
                    <span>Por segurança, o acesso a esta página será bloqueado após clicar no botão abaixo.</span>
                </div>
            </div>
            <div class="card-footer" style="justify-content:center">
                <button type="submit" class="btn btn-success" style="padding:14px 40px;font-size:15px">
                    <i class="fas fa-sign-in-alt"></i> Acessar o Sistema
                </button>
            </div>
            <?php endif; ?>

        </form>
    </div>

    <!-- Footer -->
    <div style="text-align:center;margin-top:24px;font-size:12px;color:rgba(255,255,255,0.3)">
        Oracle X &copy; <?= date('Y') ?> — Assistente de Instalação
    </div>
</div>

<script>
// Auto-disable submit on click to prevent double submission
document.querySelector('form')?.addEventListener('submit', function() {
    const btn = this.querySelector('button[type="submit"]');
    if (btn && !btn.disabled) {
        setTimeout(() => { btn.disabled = true; btn.style.opacity = '0.6'; }, 50);
    }
});

// Password match validation
const s1 = document.querySelector('input[name="admin_senha"]');
const s2 = document.querySelector('input[name="admin_senha2"]');
if (s1 && s2) {
    s2.addEventListener('input', function() {
        if (this.value && this.value !== s1.value) {
            this.style.borderColor = '#EF4444';
        } else {
            this.style.borderColor = '';
        }
    });
}
</script>
</body>
</html>
