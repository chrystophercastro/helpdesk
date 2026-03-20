<?php
/**
 * Portal Público Multi-Departamentos
 * Abertura e consulta de chamados por departamento
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Departamento.php';

$db = Database::getInstance();
$deptModel = new Departamento();
$departamentos = $deptModel->listarAtivos();

$configs = [];
$confs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($confs as $c) $configs[$c['chave']] = $c['valor'];

$empresaNome = $configs['empresa_nome'] ?? 'HelpDesk';

// Se um departamento foi selecionado via GET
$deptSelecionado = null;
$categoriasDept = [];
if (!empty($_GET['dept'])) {
    $deptId = (int)$_GET['dept'];
    $deptSelecionado = $deptModel->findById($deptId);
    if ($deptSelecionado && $deptSelecionado['ativo']) {
        $categoriasDept = $deptModel->getCategorias($deptId);
    } else {
        $deptSelecionado = null;
    }
}

$statusList = CHAMADO_STATUS;
$prioridadeList = PRIORIDADES;

// Carregar últimos posts para o mural público
require_once __DIR__ . '/../app/models/Post.php';
$postModel = new Post();
$postsPublicos = $postModel->listar(1, 5); // 5 posts mais recentes
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Suporte - <?= htmlspecialchars($empresaNome) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #3730A3;
            --bg: #F1F5F9;
            --card: #FFFFFF;
            --text: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --radius: 16px;
            --shadow: 0 4px 24px rgba(0,0,0,0.06);
            --shadow-lg: 0 12px 40px rgba(0,0,0,0.12);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ========== Header ========== */
        .portal-header {
            background: linear-gradient(135deg, #1E1B4B 0%, #4338CA 50%, #6366F1 100%);
            color: white;
            padding: 48px 24px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .portal-header::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 60%);
            animation: headerPulse 15s ease-in-out infinite;
        }
        @keyframes headerPulse {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(5%, 5%); }
        }
        .portal-header > * { position: relative; z-index: 1; }
        .portal-header .logo-icon {
            font-size: 56px;
            margin-bottom: 16px;
            display: block;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .portal-header h1 {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .portal-header p {
            opacity: 0.85;
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ========== Container ========== */
        .portal-container {
            max-width: 960px;
            margin: -48px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        /* ========== Department Grid ========== */
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .dept-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 28px 24px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            box-shadow: var(--shadow);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .dept-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--dept-color, var(--primary));
        }
        .dept-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--dept-color, var(--primary));
            border-radius: var(--radius) var(--radius) 0 0;
        }
        .dept-card-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
            color: white;
        }
        .dept-card-title { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
        .dept-card-desc { font-size: 13px; color: var(--text-muted); line-height: 1.5; flex-grow: 1; }
        .dept-card-meta {
            display: flex;
            gap: 16px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
        }
        .dept-card-meta span i { margin-right: 4px; }

        /* ========== Consultar Section ========== */
        .consultar-section {
            background: var(--card);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            margin-bottom: 32px;
        }
        .consultar-section h2 {
            font-size: 20px; font-weight: 700;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .consultar-section h2 i { color: var(--primary); }

        /* ========== Portal Mural ========== */
        .portal-mural { margin-bottom: 32px; }
        .portal-mural h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: var(--text); }
        .portal-mural h2 i { color: #8B5CF6; }
        .pm-post { background: var(--card); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow); margin-bottom: 16px; border-left: 4px solid transparent; transition: transform 0.2s; }
        .pm-post:hover { transform: translateY(-2px); }
        .pm-post.pm-comunicado { border-left-color: #EF4444; background: linear-gradient(135deg, #FFF 0%, #FEF2F2 100%); }
        .pm-post.pm-fixado { border-left-color: #F59E0B; }
        .pm-post-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .pm-avatar { width: 40px; height: 40px; border-radius: 50%; background: #6366F1; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
        .pm-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .pm-info { flex: 1; }
        .pm-author { font-weight: 600; font-size: 14px; color: var(--text); }
        .pm-meta { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-top: 2px; }
        .pm-dept { display: inline-flex; padding: 1px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .pm-badges { display: flex; gap: 6px; }
        .pm-badge-pin { font-size: 11px; color: #F59E0B; font-weight: 600; display: flex; align-items: center; gap: 3px; }
        .pm-badge-com { font-size: 11px; color: #EF4444; font-weight: 600; display: flex; align-items: center; gap: 3px; }
        .pm-content { font-size: 15px; line-height: 1.7; color: var(--text); word-break: break-word; }
        .pm-content a { color: var(--primary); }
        .pm-media { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .pm-media img { max-width: 100%; max-height: 400px; border-radius: 12px; object-fit: cover; cursor: pointer; }
        .pm-media video { max-width: 100%; max-height: 400px; border-radius: 12px; }
        .pm-footer { display: flex; align-items: center; gap: 16px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); font-size: 13px; color: var(--text-muted); }
        .pm-footer i { margin-right: 4px; }
        .pm-empty { text-align: center; padding: 40px; color: var(--text-muted); }
        .pm-empty i { font-size: 36px; margin-bottom: 12px; opacity: 0.3; display: block; }
        .pm-more { text-align: center; margin-top: 8px; }
        .pm-more a { color: var(--primary); font-weight: 500; text-decoration: none; font-size: 14px; }
        .pm-more a:hover { text-decoration: underline; }

        /* ========== Form Card ========== */
        .form-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        .form-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }
        .dept-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 10px;
            font-weight: 600; font-size: 14px; color: white;
        }
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; background: var(--bg);
            border: 2px solid var(--border); border-radius: 10px;
            font-size: 13px; font-weight: 600; color: var(--text-muted);
            cursor: pointer; text-decoration: none; transition: all 0.2s;
            font-family: inherit;
        }
        .back-btn:hover { border-color: var(--primary); color: var(--primary); }

        /* ========== Form ========== */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-input, .form-select, .form-textarea {
            width: 100%; padding: 11px 14px;
            border: 2px solid var(--border); border-radius: 10px;
            font-family: inherit; font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s; background: white;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        }
        .form-textarea { resize: vertical; min-height: 120px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: all 0.3s; font-family: inherit;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(79,70,229,0.35); }

        /* ========== Alerts ========== */
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }
        .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
        .alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }

        /* ========== Result ========== */
        .chamado-result { background: #F8FAFC; border-radius: 12px; padding: 24px; }
        .chamado-result h3 { font-size: 18px; margin-bottom: 16px; color: #1E1B4B; }
        .result-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #E5E7EB; }
        .result-row:last-child { border-bottom: none; }
        .result-label { font-weight: 600; color: #6B7280; font-size: 13px; }
        .result-value { font-weight: 500; }
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .comment-list { margin-top: 20px; }
        .comment-item { background: white; border-radius: 10px; padding: 14px; margin-bottom: 10px; border: 1px solid #E5E7EB; }
        .comment-meta { font-size: 12px; color: #6B7280; margin-bottom: 6px; }
        .comment-meta strong { color: #1E293B; }

        /* ========== Footer ========== */
        .portal-footer { text-align: center; padding: 24px; color: var(--text-muted); font-size: 13px; }
        .portal-footer a { color: var(--primary); text-decoration: none; font-weight: 500; }

        /* ========== Modals ========== */
        .modal-overlay {
            display: none; position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px); z-index: 9999;
            justify-content: center; align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-card {
            background: white; border-radius: 20px; padding: 48px;
            text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: modalIn 0.3s ease; max-width: 440px; width: 90%;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-spinner {
            width: 56px; height: 56px;
            border: 4px solid #E5E7EB; border-top-color: var(--primary);
            border-radius: 50%; animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .modal-title { font-size: 18px; font-weight: 700; color: #1E293B; margin-bottom: 6px; }
        .modal-subtitle { font-size: 14px; color: var(--text-muted); }
        .success-icon { width: 64px; height: 64px; border-radius: 50%; background: #ECFDF5; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .success-icon i { font-size: 28px; color: #059669; }
        .success-code {
            display: inline-block; background: #F1F5F9; border: 2px solid #E2E8F0;
            border-radius: 10px; padding: 10px 24px; font-size: 20px;
            font-weight: 800; color: var(--primary); letter-spacing: 1px;
            margin: 12px 0 20px; font-family: 'SF Mono', 'Consolas', monospace;
        }
        .modal-btn {
            display: inline-block; padding: 12px 28px;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white; border: none; border-radius: 12px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            transition: all 0.3s; font-family: inherit;
        }
        .modal-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(79,70,229,0.3); }

        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .portal-header { padding: 32px 16px 64px; }
            .portal-header h1 { font-size: 24px; }
            .dept-grid { grid-template-columns: 1fr; }
            .form-card, .consultar-section { padding: 20px; }
            .form-card-header { flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <i class="fas fa-headset logo-icon"></i>
        <h1><?= htmlspecialchars($empresaNome) ?></h1>
        <p>Portal de Suporte — Selecione o departamento para abrir ou consultar sua demanda</p>
    </div>

    <div class="portal-container">

    <?php if (!$deptSelecionado): ?>
    <!-- =================================================================
         VIEW 1: Seleção de Departamento
         ================================================================= -->
        <div class="dept-grid">
            <?php foreach ($departamentos as $dept): ?>
            <a href="?dept=<?= $dept['id'] ?>" class="dept-card" style="--dept-color: <?= htmlspecialchars($dept['cor']) ?>">
                <div class="dept-card-icon" style="background: <?= htmlspecialchars($dept['cor']) ?>">
                    <i class="<?= htmlspecialchars($dept['icone']) ?>"></i>
                </div>
                <div class="dept-card-title"><?= htmlspecialchars($dept['nome']) ?></div>
                <div class="dept-card-desc"><?= htmlspecialchars($dept['descricao'] ?: 'Abrir demanda para ' . $dept['nome']) ?></div>
                <div class="dept-card-meta">
                    <span><i class="fas fa-tags"></i> <?= (int)$dept['total_categorias'] ?> categorias</span>
                    <?php if ($dept['chamados_abertos'] > 0): ?>
                    <span><i class="fas fa-clock"></i> <?= (int)$dept['chamados_abertos'] ?> em aberto</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($departamentos)): ?>
        <div class="form-card" style="text-align:center;padding:60px 20px;">
            <i class="fas fa-building" style="font-size:48px;color:#CBD5E1;margin-bottom:16px;display:block;"></i>
            <h3 style="color:#64748B;margin-bottom:8px;">Nenhum departamento configurado</h3>
            <p style="color:#94A3B8;">Entre em contato com o administrador do sistema.</p>
        </div>
        <?php endif; ?>

        <!-- Mural da Empresa -->
        <?php if (!empty($postsPublicos['posts'])): ?>
        <div class="portal-mural">
            <h2><i class="fas fa-stream"></i> Mural da Empresa</h2>
            <?php foreach ($postsPublicos['posts'] as $post):
                $initials = strtoupper(substr($post['autor_nome'] ?? 'U', 0, 2));
                $isComunicado = ($post['tipo'] === 'comunicado');
                $isFixado = ($post['fixado'] == 1);
                $classes = 'pm-post';
                if ($isComunicado) $classes .= ' pm-comunicado';
                elseif ($isFixado) $classes .= ' pm-fixado';
            ?>
            <div class="<?= $classes ?>">
                <div class="pm-post-header">
                    <div class="pm-avatar" style="background:<?= htmlspecialchars($post['departamento_cor'] ?? '#6366F1') ?>">
                        <?php if (!empty($post['autor_avatar'])): ?>
                            <img src="<?= UPLOAD_URL ?>/avatars/<?= htmlspecialchars($post['autor_avatar']) ?>" alt="">
                        <?php else: ?>
                            <?= $initials ?>
                        <?php endif; ?>
                    </div>
                    <div class="pm-info">
                        <div class="pm-author"><?= htmlspecialchars($post['autor_nome']) ?></div>
                        <div class="pm-meta">
                            <?php if ($post['departamento_sigla']): ?>
                            <span class="pm-dept" style="background:<?= htmlspecialchars($post['departamento_cor']) ?>18;color:<?= htmlspecialchars($post['departamento_cor']) ?>">
                                <?= htmlspecialchars($post['departamento_sigla']) ?>
                            </span>
                            <?php endif; ?>
                            <span><?= date('d/m/Y H:i', strtotime($post['criado_em'])) ?></span>
                        </div>
                    </div>
                    <div class="pm-badges">
                        <?php if ($isFixado): ?><span class="pm-badge-pin"><i class="fas fa-thumbtack"></i> Fixado</span><?php endif; ?>
                        <?php if ($isComunicado): ?><span class="pm-badge-com"><i class="fas fa-bullhorn"></i> Comunicado</span><?php endif; ?>
                    </div>
                </div>
                <div class="pm-content"><?= $post['conteudo'] ?></div>
                <?php
                $midia = is_string($post['midia']) ? json_decode($post['midia'], true) : $post['midia'];
                if (!empty($midia)):
                ?>
                <div class="pm-media">
                    <?php foreach (array_slice($midia, 0, 4) as $m): ?>
                        <?php if (($m['tipo'] ?? '') === 'video'): ?>
                            <video controls preload="metadata"><source src="<?= htmlspecialchars($m['url']) ?>"></video>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($m['url']) ?>" alt="" onclick="window.open(this.src)">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="pm-footer">
                    <span><i class="fas fa-heart"></i> <?= (int)$post['likes_count'] ?> curtidas</span>
                    <span><i class="fas fa-comment"></i> <?= (int)$post['comentarios_count'] ?> comentários</span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if ($postsPublicos['total_paginas'] > 1): ?>
            <div class="pm-more"><a href="<?= BASE_URL ?>/index.php?page=posts">Ver todos os posts <i class="fas fa-arrow-right"></i></a></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Consultar Chamado global -->
        <div class="consultar-section">
            <h2><i class="fas fa-search"></i> Consultar Chamado</h2>
            <form id="formConsultarChamado">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Telefone (WhatsApp)</label>
                        <input type="text" name="telefone" class="form-input" required placeholder="5562999999999" value="55" id="consultaTelefone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Código do Chamado</label>
                        <input type="text" name="codigo" class="form-input" required placeholder="TI-2026-00001" id="consultaCodigo" style="text-transform:uppercase;">
                    </div>
                </div>
                <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#0F172A,#334155);">
                    <i class="fas fa-search"></i> Consultar
                </button>
            </form>
            <div id="resultadoConsulta" style="display:none;margin-top:24px;"></div>
        </div>

    <?php else: ?>
    <!-- =================================================================
         VIEW 2: Formulário do Departamento Selecionado
         ================================================================= -->
        <div class="alert" id="portalAlert" style="display:none">
            <i class="fas fa-info-circle" id="portalAlertIcon"></i>
            <span id="portalAlertText"></span>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <div class="dept-badge" style="background:<?= htmlspecialchars($deptSelecionado['cor']) ?>">
                    <i class="<?= htmlspecialchars($deptSelecionado['icone']) ?>"></i>
                    <?= htmlspecialchars($deptSelecionado['nome']) ?>
                </div>
                <a href="<?= BASE_URL ?>/portal/" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

            <div style="display:flex;gap:8px;margin-bottom:24px;">
                <button class="back-btn tab-btn" onclick="switchTab('abrir')" id="tabAbrir"
                        style="flex:1;justify-content:center;border-color:var(--primary);color:var(--primary);">
                    <i class="fas fa-plus-circle"></i> Abrir Demanda
                </button>
                <button class="back-btn tab-btn" onclick="switchTab('consultar')" id="tabConsultar"
                        style="flex:1;justify-content:center;">
                    <i class="fas fa-search"></i> Consultar
                </button>
            </div>

            <!-- Panel: Abrir -->
            <div id="panelAbrir">
                <form id="formAbrirChamado">
                    <input type="hidden" name="departamento_id" value="<?= $deptSelecionado['id'] ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome" class="form-input" required placeholder="Seu nome completo">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" required placeholder="seu@email.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telefone (WhatsApp) *</label>
                            <input type="text" name="telefone" class="form-input" required placeholder="5562999999999" value="55">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoria *</label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($categoriasDept as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Impacto</label>
                            <select name="impacto" class="form-select">
                                <option value="baixo">Baixo — Apenas eu sou afetado</option>
                                <option value="medio" selected>Médio — Minha equipe é afetada</option>
                                <option value="alto">Alto — Toda a empresa é afetada</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Urgência</label>
                            <select name="urgencia" class="form-select">
                                <option value="baixa">Baixa — Pode aguardar</option>
                                <option value="media" selected>Média — Preciso em breve</option>
                                <option value="alta">Alta — Preciso imediatamente</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Título da Demanda *</label>
                        <input type="text" name="titulo" class="form-input" required placeholder="Descreva brevemente o que precisa">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descrição Detalhada *</label>
                        <textarea name="descricao" class="form-textarea" rows="5" required
                                  placeholder="Descreva com o máximo de detalhes possível"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Anexos (opcional)</label>
                        <input type="file" name="anexos[]" class="form-input" multiple>
                    </div>

                    <button type="submit" class="btn-submit"
                            style="background:linear-gradient(135deg,<?= htmlspecialchars($deptSelecionado['cor']) ?>,<?= htmlspecialchars($deptSelecionado['cor']) ?>cc);">
                        <i class="fas fa-paper-plane"></i> Enviar Demanda para <?= htmlspecialchars($deptSelecionado['sigla']) ?>
                    </button>
                </form>
            </div>

            <!-- Panel: Consultar -->
            <div id="panelConsultar" style="display:none;">
                <form id="formConsultarDept">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telefone (WhatsApp)</label>
                            <input type="text" name="telefone" class="form-input" required placeholder="5562999999999" value="55" id="consultaTelDept">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Código do Chamado</label>
                            <input type="text" name="codigo" class="form-input" required placeholder="<?= $deptSelecionado['sigla'] ?>-2026-00001" id="consultaCodDept" style="text-transform:uppercase;">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#0F172A,#334155);">
                        <i class="fas fa-search"></i> Consultar
                    </button>
                </form>
                <div id="resultadoConsultaDept" style="display:none;margin-top:24px;"></div>
            </div>
        </div>
    <?php endif; ?>

        <div class="portal-footer">
            <p>Powered by <a href="<?= BASE_URL ?>">Oracle X</a> | <a href="<?= BASE_URL ?>/login.php">Área Administrativa</a></p>
        </div>
    </div>

    <!-- Loading -->
    <div class="modal-overlay" id="loadingOverlay">
        <div class="modal-card">
            <div class="modal-spinner"></div>
            <div class="modal-title" id="loadingTitle">Processando...</div>
            <div class="modal-subtitle" id="loadingSubtitle">Aguarde um momento</div>
        </div>
    </div>

    <!-- Success -->
    <div class="modal-overlay" id="successOverlay">
        <div class="modal-card">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <div class="modal-title" style="font-size:20px;font-weight:800;">Demanda Registrada!</div>
            <div class="modal-subtitle" style="margin-bottom:12px;">Seu chamado foi aberto com sucesso. Guarde o código abaixo:</div>
            <div class="success-code" id="successCodigo"></div>
            <br>
            <button class="modal-btn" id="btnAcompanhar"><i class="fas fa-search"></i> Acompanhar Demanda</button>
        </div>
    </div>

    <script>
    const PORTAL_API = '<?= BASE_URL ?>/portal/api.php';

    function showLoading(t, s) {
        document.getElementById('loadingTitle').textContent = t || 'Processando...';
        document.getElementById('loadingSubtitle').textContent = s || 'Aguarde um momento';
        document.getElementById('loadingOverlay').classList.add('active');
    }
    function hideLoading() { document.getElementById('loadingOverlay').classList.remove('active'); }

    function showAlert(text, type) {
        const el = document.getElementById('portalAlert');
        if (!el) return alert(text);
        el.className = 'alert alert-' + type;
        document.getElementById('portalAlertIcon').className = 'fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle');
        document.getElementById('portalAlertText').innerHTML = text;
        el.style.display = 'flex';
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function hideAlert() { const el = document.getElementById('portalAlert'); if (el) el.style.display = 'none'; }

    async function apiJson(data) { return (await fetch(PORTAL_API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })).json(); }
    async function apiForm(fd) { return (await fetch(PORTAL_API, { method:'POST', body:fd })).json(); }

    /* Tab switch */
    function switchTab(tab) {
        hideAlert();
        const abrir = document.getElementById('panelAbrir');
        const consultar = document.getElementById('panelConsultar');
        const tA = document.getElementById('tabAbrir');
        const tC = document.getElementById('tabConsultar');
        if (!abrir) return;
        if (tab === 'abrir') {
            abrir.style.display = 'block'; consultar.style.display = 'none';
            tA.style.borderColor = 'var(--primary)'; tA.style.color = 'var(--primary)';
            tC.style.borderColor = 'var(--border)'; tC.style.color = 'var(--text-muted)';
        } else {
            abrir.style.display = 'none'; consultar.style.display = 'block';
            tA.style.borderColor = 'var(--border)'; tA.style.color = 'var(--text-muted)';
            tC.style.borderColor = 'var(--primary)'; tC.style.color = 'var(--primary)';
        }
    }

    /* Abrir chamado */
    const formAbrir = document.getElementById('formAbrirChamado');
    if (formAbrir) formAbrir.addEventListener('submit', async function(e) {
        e.preventDefault(); hideAlert();
        const fd = new FormData(this);
        fd.append('acao', 'abrir');
        if (!fd.get('nome') || !fd.get('email') || !fd.get('telefone') || !fd.get('titulo') || !fd.get('descricao')) {
            showAlert('Preencha todos os campos obrigatórios.', 'error'); return;
        }
        showLoading('Registrando sua demanda...', 'Aguarde um momento');
        try {
            const r = await apiForm(fd); hideLoading();
            if (r.success) {
                formAbrir.reset();
                formAbrir.querySelector('[name="telefone"]').value = '55';
                document.getElementById('successCodigo').textContent = r.codigo;
                document.getElementById('successOverlay').classList.add('active');
                document.getElementById('btnAcompanhar').onclick = function() {
                    document.getElementById('successOverlay').classList.remove('active');
                    (document.getElementById('consultaTelDept') || document.getElementById('consultaTelefone')).value = r.telefone;
                    (document.getElementById('consultaCodDept') || document.getElementById('consultaCodigo')).value = r.codigo;
                    switchTab('consultar');
                    consultarChamado(r.telefone, r.codigo, 'resultadoConsultaDept');
                };
            } else {
                showAlert(r.error || 'Erro ao registrar demanda.', 'error');
            }
        } catch(ex) { hideLoading(); showAlert('Erro de conexão.', 'error'); }
    });

    /* Consultar */
    async function consultarChamado(tel, cod, cid) {
        showLoading('Consultando...', 'Buscando informações');
        try {
            const r = await apiJson({ acao:'consultar', telefone:tel, codigo:cod }); hideLoading();
            const box = document.getElementById(cid || 'resultadoConsulta');
            if (r.success) { box.innerHTML = renderResultado(r.chamado); box.style.display = 'block'; }
            else { box.style.display = 'none'; showAlert(r.error || 'Chamado não encontrado.', 'error'); }
        } catch(ex) { hideLoading(); showAlert('Erro de conexão.', 'error'); }
    }

    const fCH = document.getElementById('formConsultarChamado');
    if (fCH) fCH.addEventListener('submit', function(e) {
        e.preventDefault();
        consultarChamado(document.getElementById('consultaTelefone').value.trim(), document.getElementById('consultaCodigo').value.trim().toUpperCase(), 'resultadoConsulta');
    });
    const fCD = document.getElementById('formConsultarDept');
    if (fCD) fCD.addEventListener('submit', function(e) {
        e.preventDefault();
        consultarChamado(document.getElementById('consultaTelDept').value.trim(), document.getElementById('consultaCodDept').value.trim().toUpperCase(), 'resultadoConsultaDept');
    });

    function renderResultado(ch) {
        let h = `<div class="chamado-result"><h3><i class="fas fa-ticket-alt"></i> ${ch.codigo} — ${ch.titulo}</h3>`;
        if (ch.departamento) h += `<div style="margin-bottom:12px"><span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:600;background:${ch.departamento_cor||'#6366F1'}20;color:${ch.departamento_cor||'#6366F1'}"><i class="${ch.departamento_icone||'fas fa-building'}"></i> ${ch.departamento}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Status</span><span class="status-badge" style="background:${ch.status_cor}20;color:${ch.status_cor}"><i class="${ch.status_icone}"></i> ${ch.status_label}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Prioridade</span><span class="result-value">${ch.prioridade}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Categoria</span><span class="result-value">${ch.categoria}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Responsável</span><span class="result-value">${ch.tecnico}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Aberto em</span><span class="result-value">${ch.data_abertura}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Descrição</span><span class="result-value">${ch.descricao}</span></div>`;
        if (ch.comentarios && ch.comentarios.length) {
            h += `<div class="comment-list"><h4 style="margin-bottom:12px"><i class="fas fa-comments"></i> Comentários</h4>`;
            ch.comentarios.forEach(c => h += `<div class="comment-item"><div class="comment-meta"><strong>${c.autor}</strong> — ${c.data}</div><div>${c.conteudo}</div></div>`);
            h += `</div>`;
        }
        return h + `</div>`;
    }
    </script>
</body>
</html>
