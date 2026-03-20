<?php
/**
 * Portal Público — Oracle X
 * Design moderno com Mural interativo, departamentos e consulta
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Departamento.php';
require_once __DIR__ . '/../app/models/Post.php';

$db = Database::getInstance();
$deptModel = new Departamento();
$postModel = new Post();

// Carregar configurações
$configs = [];
$confs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($confs as $c) $configs[$c['chave']] = $c['valor'];

$empresaNome = $configs['empresa_nome'] ?? 'Oracle X';

// Portal configs com defaults
$portal = [
    'titulo'           => $configs['portal_titulo'] ?: $empresaNome,
    'subtitulo'        => $configs['portal_subtitulo'] ?? 'Portal de Suporte — Selecione o departamento para abrir ou consultar sua demanda',
    'icone'            => $configs['portal_icone'] ?? 'fas fa-headset',
    'logo'             => $configs['portal_logo'] ?? '',
    'cor_header_1'     => $configs['portal_cor_header_1'] ?? '#1E1B4B',
    'cor_header_2'     => $configs['portal_cor_header_2'] ?? '#4338CA',
    'cor_header_3'     => $configs['portal_cor_header_3'] ?? '#6366F1',
    'cor_primaria'     => $configs['portal_cor_primaria'] ?? '#4F46E5',
    'cor_primaria_dark'=> $configs['portal_cor_primaria_dark'] ?? '#3730A3',
    'mostrar_mural'    => ($configs['portal_mostrar_mural'] ?? '1') === '1',
    'mostrar_consulta' => ($configs['portal_mostrar_consulta'] ?? '1') === '1',
    'mostrar_depts'    => ($configs['portal_mostrar_depts'] ?? '1') === '1',
    'posts_qtd'        => (int)($configs['portal_posts_quantidade'] ?? 5),
    'footer_texto'     => $configs['portal_footer_texto'] ?? '',
    'css_custom'       => $configs['portal_css_custom'] ?? '',
    'banner_url'       => $configs['portal_banner_url'] ?? '',
    'mural_titulo'     => $configs['portal_mural_titulo'] ?? 'Mural da Empresa',
    'consulta_titulo'  => $configs['portal_consulta_titulo'] ?? 'Consultar Chamado',
];

// Departamentos ativos
$departamentos = $deptModel->listarAtivos();

// Departamento selecionado (View 2)
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

// Posts para o mural
$postsPublicos = $postModel->listar(1, $portal['posts_qtd']);
$postsRecentes = $postModel->listar(1, 8);

// Verificar login (para likes/comentários)
$isLogged = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0;
$userId = $isLogged ? (int)$_SESSION['usuario_id'] : 0;
$userName = '';
$userAvatar = '';

if ($isLogged) {
    $user = $db->fetch("SELECT nome, avatar FROM usuarios WHERE id = ?", [$userId]);
    $userName = $user['nome'] ?? '';
    $userAvatar = $user['avatar'] ?? '';
    $postIds = array_column($postsPublicos['posts'], 'id');
    $likesMap = !empty($postIds) ? $postModel->getLikesMap($postIds, $userId) : [];
} else {
    $likesMap = [];
}

$statusList = CHAMADO_STATUS;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Suporte — <?= htmlspecialchars($portal['titulo']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?= htmlspecialchars($portal['cor_primaria']) ?>;
            --primary-dark: <?= htmlspecialchars($portal['cor_primaria_dark']) ?>;
            --header-1: <?= htmlspecialchars($portal['cor_header_1']) ?>;
            --header-2: <?= htmlspecialchars($portal['cor_header_2']) ?>;
            --header-3: <?= htmlspecialchars($portal['cor_header_3']) ?>;
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
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* ===== HEADER ===== */
        .portal-header {
            background: linear-gradient(135deg, var(--header-1) 0%, var(--header-2) 50%, var(--header-3) 100%);
            color: white; padding: 48px 24px 80px; text-align: center;
            position: relative; overflow: hidden;
        }
        .portal-header::before {
            content: ''; position: absolute; top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 60%);
            animation: headerPulse 15s ease-in-out infinite;
        }
        @keyframes headerPulse { 0%,100%{transform:translate(0,0)} 50%{transform:translate(5%,5%)} }
        .portal-header > * { position: relative; z-index: 1; }
        .portal-header .logo-icon { font-size: 56px; margin-bottom: 16px; display: block; text-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .portal-header .logo-img { max-height: 72px; margin-bottom: 16px; filter: drop-shadow(0 4px 20px rgba(0,0,0,0.3)); }
        .portal-header h1 { font-size: 36px; font-weight: 900; margin-bottom: 8px; letter-spacing: -0.5px; }
        .portal-header p { opacity: 0.85; font-size: 16px; max-width: 500px; margin: 0 auto; line-height: 1.6; }
        .user-badge {
            position: absolute; top: 20px; right: 24px; z-index: 10;
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
            padding: 8px 16px; border-radius: 50px; font-size: 13px;
            color: white; text-decoration: none; transition: background 0.2s;
        }
        .user-badge:hover { background: rgba(255,255,255,0.25); }
        .ub-avatar {
            width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.3);
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;
        }
        .ub-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }

        /* ===== CONTAINER ===== */
        .portal-container { max-width: 1200px; margin: -48px auto 40px; padding: 0 20px; position: relative; z-index: 2; }

        /* ===== MAIN GRID ===== */
        .main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 24px; margin-bottom: 32px; }

        /* ===== DEPT GRID ===== */
        .dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .dept-card {
            background: var(--card); border-radius: var(--radius); padding: 24px 20px;
            cursor: pointer; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            border: 2px solid transparent; box-shadow: var(--shadow);
            text-decoration: none; color: inherit; display: flex; flex-direction: column;
            position: relative; overflow: hidden;
        }
        .dept-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--dept-color, var(--primary)); }
        .dept-card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: var(--dept-color, var(--primary)); border-radius: var(--radius) var(--radius) 0 0;
        }
        .dept-card-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 12px; color: white;
        }
        .dept-card-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .dept-card-desc { font-size: 12px; color: var(--text-muted); line-height: 1.5; flex-grow: 1; }
        .dept-card-meta {
            display: flex; gap: 12px; margin-top: 12px; padding-top: 12px;
            border-top: 1px solid var(--border); font-size: 11px; color: var(--text-muted);
        }
        .dept-card-meta span i { margin-right: 3px; }

        /* ===== MURAL WIDGET ===== */
        .mural-widget {
            background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow);
            overflow: hidden; display: flex; flex-direction: column;
        }
        .mural-widget-header {
            padding: 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .mural-widget-header h3 { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .mural-widget-header h3 i { color: #8B5CF6; }
        .btn-new-post {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; background: linear-gradient(135deg, var(--primary), #7C3AED);
            color: white; border: none; border-radius: 10px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: all 0.2s; font-family: inherit; text-decoration: none;
        }
        .btn-new-post:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(79,70,229,0.3); }
        .mural-widget-search { padding: 12px 20px; border-bottom: 1px solid var(--border); }
        .mural-search-input {
            width: 100%; padding: 10px 14px 10px 36px; border: 2px solid var(--border);
            border-radius: 10px; font-size: 13px; font-family: inherit;
            background: #F8FAFC url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' fill='%2394A3B8'%3E%3Cpath d='M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z'/%3E%3C/svg%3E") no-repeat 12px center;
            background-size: 14px; transition: border-color 0.2s;
        }
        .mural-search-input:focus { outline: none; border-color: var(--primary); }
        .mural-widget-banner {
            flex: 1; min-height: 160px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative; overflow: hidden;
        }
        .mural-widget-banner img { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; }
        .banner-overlay { position: relative; z-index: 1; text-align: center; color: white; padding: 20px; }
        .banner-overlay i { font-size: 40px; opacity: 0.7; margin-bottom: 8px; display: block; }
        .banner-overlay p { font-size: 13px; opacity: 0.8; }
        .mural-widget-stats {
            padding: 16px 20px; display: flex; justify-content: space-around;
            border-top: 1px solid var(--border); font-size: 12px; color: var(--text-muted);
        }
        .mural-widget-stats span { display: flex; flex-direction: column; align-items: center; gap: 2px; }
        .mural-widget-stats strong { font-size: 18px; font-weight: 800; color: var(--text); }

        /* ===== CONTENT GRID ===== */
        .content-grid { display: grid; grid-template-columns: 1fr 360px; gap: 24px; margin-bottom: 32px; }

        /* ===== MURAL FEED ===== */
        .mural-feed h2 { font-size: 20px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .mural-feed h2 i { color: #8B5CF6; }
        .post-card {
            background: var(--card); border-radius: var(--radius); padding: 24px;
            box-shadow: var(--shadow); margin-bottom: 16px;
            border-left: 4px solid transparent; transition: transform 0.2s;
        }
        .post-card:hover { transform: translateY(-2px); }
        .post-card.post-comunicado { border-left-color: #EF4444; background: linear-gradient(135deg, #FFF 0%, #FEF2F2 100%); }
        .post-card.post-fixado { border-left-color: #F59E0B; }
        .post-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .post-avatar {
            width: 44px; height: 44px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; font-weight: 700;
            font-size: 14px; color: white; flex-shrink: 0;
        }
        .post-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .post-info { flex: 1; }
        .post-author { font-weight: 600; font-size: 14px; }
        .post-meta { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-top: 2px; }
        .post-dept { display: inline-flex; padding: 1px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .post-badges { display: flex; gap: 6px; }
        .post-badge { font-size: 11px; font-weight: 600; display: flex; align-items: center; gap: 3px; }
        .post-badge.pin { color: #F59E0B; }
        .post-badge.comunicado { color: #EF4444; }
        .post-content { font-size: 15px; line-height: 1.7; word-break: break-word; }
        .post-content a { color: var(--primary); }
        .post-media { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .post-media img { max-width: 100%; max-height: 400px; border-radius: 12px; object-fit: cover; cursor: pointer; }
        .post-media video { max-width: 100%; max-height: 400px; border-radius: 12px; }
        .post-actions {
            display: flex; align-items: center; gap: 4px; margin-top: 14px;
            padding-top: 14px; border-top: 1px solid var(--border);
        }
        .post-action-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border: none; border-radius: 10px;
            background: transparent; color: var(--text-muted); font-size: 13px;
            font-weight: 500; cursor: pointer; transition: all 0.2s; font-family: inherit;
        }
        .post-action-btn:hover { background: #F1F5F9; color: var(--text); }
        .post-action-btn.liked { color: #EF4444; }
        .post-action-btn.liked i { animation: likeAnim 0.3s ease; }
        @keyframes likeAnim { 0%{transform:scale(1)} 50%{transform:scale(1.3)} 100%{transform:scale(1)} }
        .post-action-btn .count { font-weight: 600; }
        .post-action-spacer { flex: 1; }
        .post-action-btn.ver-mais { color: var(--primary); font-weight: 600; }
        .post-action-btn.ver-mais:hover { background: rgba(79,70,229,0.08); }

        /* Comments */
        .post-comments { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); }
        .cmt-item { display: flex; gap: 10px; margin-bottom: 12px; }
        .cmt-avatar {
            width: 32px; height: 32px; border-radius: 50%; background: #E2E8F0;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #64748B; flex-shrink: 0;
        }
        .cmt-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .cmt-body { flex: 1; background: #F8FAFC; border-radius: 12px; padding: 10px 14px; }
        .cmt-author { font-size: 12px; font-weight: 600; margin-bottom: 2px; }
        .cmt-text { font-size: 13px; line-height: 1.5; color: var(--text); }
        .cmt-time { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
        .cmt-input-wrap { display: flex; gap: 10px; align-items: flex-start; margin-top: 12px; }
        .cmt-input-wrap input {
            flex: 1; padding: 10px 14px; border: 2px solid var(--border); border-radius: 10px;
            font-size: 13px; font-family: inherit; transition: border-color 0.2s;
        }
        .cmt-input-wrap input:focus { outline: none; border-color: var(--primary); }
        .cmt-input-wrap button {
            padding: 10px 16px; background: var(--primary); color: white; border: none;
            border-radius: 10px; font-size: 13px; cursor: pointer; transition: all 0.2s;
        }
        .cmt-input-wrap button:hover { background: var(--primary-dark); }
        .login-prompt {
            text-align: center; padding: 12px; background: #F8FAFC; border-radius: 10px;
            margin-top: 10px; font-size: 13px; color: var(--text-muted);
        }
        .login-prompt a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .login-prompt a:hover { text-decoration: underline; }
        .post-empty { text-align: center; padding: 48px 20px; color: var(--text-muted); }
        .post-empty i { font-size: 40px; opacity: 0.3; display: block; margin-bottom: 12px; }
        .post-load-more { text-align: center; margin-top: 8px; }
        .post-load-more a {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 24px; background: var(--card); border: 2px solid var(--border);
            border-radius: 12px; color: var(--primary); font-weight: 600;
            font-size: 14px; text-decoration: none; transition: all 0.2s;
        }
        .post-load-more a:hover { border-color: var(--primary); background: rgba(79,70,229,0.04); }

        /* ===== SIDEBAR ===== */
        .sidebar-section { margin-bottom: 24px; }
        .sidebar-card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
        .sidebar-card-header {
            padding: 16px 20px; border-bottom: 1px solid var(--border);
            font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;
        }
        .sidebar-card-header i { color: var(--primary); }
        .sidebar-card-body { padding: 16px 20px; }

        /* Consultar */
        .consultar-form .form-group { margin-bottom: 14px; }
        .consultar-form .form-label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .consultar-form .form-input {
            width: 100%; padding: 10px 12px; border: 2px solid var(--border); border-radius: 10px;
            font-family: inherit; font-size: 13px; transition: border-color 0.2s; background: white;
        }
        .consultar-form .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        .btn-consultar {
            width: 100%; padding: 12px; background: linear-gradient(135deg, #0F172A, #334155);
            color: white; border: none; border-radius: 10px; font-size: 14px;
            font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
        }
        .btn-consultar:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(15,23,42,0.3); }

        .chamado-result { background: #F8FAFC; border-radius: 12px; padding: 20px; margin-top: 16px; }
        .chamado-result h3 { font-size: 16px; margin-bottom: 12px; color: #1E1B4B; }
        .result-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #E5E7EB; font-size: 13px; }
        .result-row:last-child { border-bottom: none; }
        .result-label { font-weight: 600; color: #6B7280; }
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .cmt-list-result { margin-top: 16px; }
        .cmt-item-result { background: white; border-radius: 10px; padding: 12px; margin-bottom: 8px; border: 1px solid #E5E7EB; font-size: 13px; }
        .cmt-meta-result { font-size: 11px; color: #6B7280; margin-bottom: 4px; }
        .cmt-meta-result strong { color: #1E293B; }

        /* Recent Posts */
        .rp-item { display: flex; gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .rp-item:last-child { border-bottom: none; }
        .rp-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 11px; color: white; flex-shrink: 0;
        }
        .rp-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .rp-info { flex: 1; min-width: 0; }
        .rp-author { font-size: 12px; font-weight: 600; }
        .rp-text { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
        .rp-stats { font-size: 11px; color: var(--text-muted); display: flex; gap: 10px; margin-top: 3px; }

        /* ===== FORM VIEW 2 ===== */
        .form-card { background: var(--card); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow); margin-bottom: 24px; }
        .form-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid var(--border); }
        .dept-badge-lg { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 14px; color: white; }
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
            background: var(--bg); border: 2px solid var(--border); border-radius: 10px;
            font-size: 13px; font-weight: 600; color: var(--text-muted);
            cursor: pointer; text-decoration: none; transition: all 0.2s; font-family: inherit;
        }
        .back-btn:hover { border-color: var(--primary); color: var(--primary); }
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-input, .form-select, .form-textarea {
            width: 100%; padding: 11px 14px; border: 2px solid var(--border); border-radius: 10px;
            font-family: inherit; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; background: white;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79,70,229,0.1); }
        .form-textarea { resize: vertical; min-height: 120px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary), #7C3AED);
            color: white; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s; font-family: inherit;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(79,70,229,0.35); }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }
        .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
        .alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }

        /* ===== FOOTER ===== */
        .portal-footer { text-align: center; padding: 24px; color: var(--text-muted); font-size: 13px; }
        .portal-footer a { color: var(--primary); text-decoration: none; font-weight: 500; }

        /* ===== MODALS ===== */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);
            z-index: 9999; justify-content: center; align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-card {
            background: white; border-radius: 20px; padding: 48px;
            text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: modalIn 0.3s ease; max-width: 440px; width: 90%;
        }
        @keyframes modalIn { from{opacity:0;transform:translateY(20px) scale(0.95)} to{opacity:1;transform:translateY(0) scale(1)} }
        .modal-spinner {
            width: 56px; height: 56px; border: 4px solid #E5E7EB; border-top-color: var(--primary);
            border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 20px;
        }
        @keyframes spin { to{transform:rotate(360deg)} }
        .modal-title { font-size: 18px; font-weight: 700; color: #1E293B; margin-bottom: 6px; }
        .modal-subtitle { font-size: 14px; color: var(--text-muted); }
        .success-icon { width: 64px; height: 64px; border-radius: 50%; background: #ECFDF5; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .success-icon i { font-size: 28px; color: #059669; }
        .success-code {
            display: inline-block; background: #F1F5F9; border: 2px solid #E2E8F0;
            border-radius: 10px; padding: 10px 24px; font-size: 20px;
            font-weight: 800; color: var(--primary); letter-spacing: 1px;
            margin: 12px 0 20px; font-family: 'SF Mono','Consolas',monospace;
        }
        .modal-btn {
            display: inline-block; padding: 12px 28px;
            background: linear-gradient(135deg, var(--primary), #7C3AED);
            color: white; border: none; border-radius: 12px;
            font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-family: inherit;
        }
        .modal-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(79,70,229,0.3); }

        /* Lightbox */
        .lb-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.9); z-index: 99999; justify-content: center; align-items: center; cursor: zoom-out;
        }
        .lb-overlay.active { display: flex; }
        .lb-overlay img { max-width: 90vw; max-height: 90vh; border-radius: 8px; }

        @media (max-width: 960px) {
            .main-grid { grid-template-columns: 1fr; }
            .content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .portal-header { padding: 32px 16px 64px; }
            .portal-header h1 { font-size: 24px; }
            .dept-grid { grid-template-columns: 1fr; }
            .form-card { padding: 20px; }
            .form-card-header { flex-direction: column; gap: 12px; }
            .user-badge { position: static; margin: 0 auto 16px; width: fit-content; }
        }

        <?= $portal['css_custom'] ?>
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="portal-header">
        <?php if ($isLogged): ?>
        <a href="<?= BASE_URL ?>/" class="user-badge">
            <div class="ub-avatar">
                <?php if (!empty($userAvatar)): ?>
                    <img src="<?= UPLOAD_URL ?>/avatars/<?= htmlspecialchars($userAvatar) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($userName, 0, 2)) ?>
                <?php endif; ?>
            </div>
            <span><?= htmlspecialchars($userName) ?></span>
            <i class="fas fa-external-link-alt" style="font-size:10px;opacity:0.6"></i>
        </a>
        <?php endif; ?>

        <?php if (!empty($portal['logo'])): ?>
            <img src="<?= htmlspecialchars($portal['logo']) ?>" alt="Logo" class="logo-img">
        <?php else: ?>
            <i class="<?= htmlspecialchars($portal['icone']) ?> logo-icon"></i>
        <?php endif; ?>
        <h1><?= htmlspecialchars($portal['titulo']) ?></h1>
        <p><?= htmlspecialchars($portal['subtitulo']) ?></p>
    </div>

    <div class="portal-container">

    <?php if (!$deptSelecionado): ?>
    <!-- ======================== VIEW 1: PORTAL PRINCIPAL ======================== -->

        <!-- TOP: Departamentos + Mural Widget -->
        <div class="main-grid">
            <!-- Departamentos -->
            <div>
                <?php if ($portal['mostrar_depts'] && !empty($departamentos)): ?>
                <div class="dept-grid">
                    <?php foreach ($departamentos as $dept): ?>
                    <a href="?dept=<?= $dept['id'] ?>" class="dept-card" style="--dept-color:<?= htmlspecialchars($dept['cor']) ?>">
                        <div class="dept-card-icon" style="background:<?= htmlspecialchars($dept['cor']) ?>">
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
                <?php elseif ($portal['mostrar_depts']): ?>
                <div style="text-align:center;padding:40px;background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);">
                    <i class="fas fa-building" style="font-size:40px;color:#CBD5E1;margin-bottom:12px;display:block;"></i>
                    <h3 style="color:#64748B;margin-bottom:6px;">Nenhum departamento configurado</h3>
                    <p style="color:#94A3B8;font-size:13px;">Entre em contato com o administrador.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Mural Widget Card -->
            <?php if ($portal['mostrar_mural']): ?>
            <div class="mural-widget">
                <div class="mural-widget-header">
                    <h3><i class="fas fa-stream"></i> <?= htmlspecialchars($portal['mural_titulo']) ?></h3>
                    <?php if ($isLogged): ?>
                    <a href="<?= BASE_URL ?>/?page=posts" class="btn-new-post"><i class="fas fa-plus"></i> Nova Postagem</a>
                    <?php endif; ?>
                </div>
                <div class="mural-widget-search">
                    <input type="text" class="mural-search-input" id="searchPosts" placeholder="Buscar no mural..." oninput="filterPosts(this.value)">
                </div>
                <div class="mural-widget-banner">
                    <?php if (!empty($portal['banner_url'])): ?>
                        <img src="<?= htmlspecialchars($portal['banner_url']) ?>" alt="Banner">
                    <?php else: ?>
                        <div class="banner-overlay">
                            <i class="fas fa-newspaper"></i>
                            <p>Fique por dentro das novidades</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mural-widget-stats">
                    <span><strong><?= (int)$postsPublicos['total'] ?></strong> Posts</span>
                    <span><strong><?= count($departamentos) ?></strong> Deptos</span>
                    <span><strong><?= array_sum(array_column($postsPublicos['posts'], 'likes_count')) ?></strong> Curtidas</span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- CONTENT: Feed + Sidebar -->
        <div class="content-grid">
            <!-- Feed -->
            <?php if ($portal['mostrar_mural']): ?>
            <div class="mural-feed">
                <h2><i class="fas fa-stream"></i> <?= htmlspecialchars($portal['mural_titulo']) ?></h2>

                <?php if (!empty($postsPublicos['posts'])): ?>
                <div id="postsContainer">
                <?php foreach ($postsPublicos['posts'] as $post):
                    $initials = strtoupper(substr($post['autor_nome'] ?? 'U', 0, 2));
                    $isComunicado = ($post['tipo'] === 'comunicado');
                    $isFixado = ($post['fixado'] == 1);
                    $classes = 'post-card';
                    if ($isComunicado) $classes .= ' post-comunicado';
                    elseif ($isFixado) $classes .= ' post-fixado';
                    $isLiked = isset($likesMap[$post['id']]);
                ?>
                <div class="<?= $classes ?>" data-post-id="<?= $post['id'] ?>" data-searchable="<?= htmlspecialchars(strtolower(($post['autor_nome'] ?? '') . ' ' . strip_tags($post['conteudo'] ?? ''))) ?>">
                    <div class="post-header">
                        <div class="post-avatar" style="background:<?= htmlspecialchars($post['departamento_cor'] ?? '#6366F1') ?>">
                            <?php if (!empty($post['autor_avatar'])): ?>
                                <img src="<?= UPLOAD_URL ?>/avatars/<?= htmlspecialchars($post['autor_avatar']) ?>" alt="">
                            <?php else: ?>
                                <?= $initials ?>
                            <?php endif; ?>
                        </div>
                        <div class="post-info">
                            <div class="post-author"><?= htmlspecialchars($post['autor_nome'] ?? 'Usuário') ?></div>
                            <div class="post-meta">
                                <?php if (!empty($post['departamento_sigla'])): ?>
                                <span class="post-dept" style="background:<?= htmlspecialchars($post['departamento_cor']) ?>18;color:<?= htmlspecialchars($post['departamento_cor']) ?>">
                                    <?= htmlspecialchars($post['departamento_sigla']) ?>
                                </span>
                                <?php endif; ?>
                                <span><?= timeAgo($post['criado_em']) ?></span>
                            </div>
                        </div>
                        <div class="post-badges">
                            <?php if ($isFixado): ?><span class="post-badge pin"><i class="fas fa-thumbtack"></i> Fixado</span><?php endif; ?>
                            <?php if ($isComunicado): ?><span class="post-badge comunicado"><i class="fas fa-bullhorn"></i> Comunicado</span><?php endif; ?>
                        </div>
                    </div>

                    <div class="post-content"><?= nl2br(htmlspecialchars(mb_strimwidth(strip_tags($post['conteudo'] ?? ''), 0, 500, '...'))) ?></div>

                    <?php
                    $midia = is_string($post['midia'] ?? null) ? json_decode($post['midia'], true) : ($post['midia'] ?? null);
                    if (!empty($midia)):
                    ?>
                    <div class="post-media">
                        <?php foreach (array_slice($midia, 0, 4) as $m): ?>
                            <?php if (($m['tipo'] ?? '') === 'video'): ?>
                                <video controls preload="metadata"><source src="<?= htmlspecialchars($m['url']) ?>"></video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($m['url']) ?>" alt="" onclick="openLightbox(this.src)">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="post-actions">
                        <?php if ($isLogged): ?>
                        <button class="post-action-btn <?= $isLiked ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>, this)">
                            <i class="<?= $isLiked ? 'fas' : 'far' ?> fa-heart"></i>
                            <span class="count"><?= (int)$post['likes_count'] ?></span>
                        </button>
                        <button class="post-action-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                            <i class="far fa-comment"></i>
                            <span class="count" id="cmtCount-<?= $post['id'] ?>"><?= (int)$post['comentarios_count'] ?></span>
                        </button>
                        <?php else: ?>
                        <span class="post-action-btn" style="cursor:default"><i class="far fa-heart"></i> <span class="count"><?= (int)$post['likes_count'] ?></span></span>
                        <span class="post-action-btn" style="cursor:default"><i class="far fa-comment"></i> <span class="count"><?= (int)$post['comentarios_count'] ?></span></span>
                        <?php endif; ?>
                        <div class="post-action-spacer"></div>
                        <button class="post-action-btn ver-mais" onclick="toggleComments(<?= $post['id'] ?>)">
                            Ver mais <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>

                    <!-- Comentários -->
                    <div class="post-comments" id="cmts-<?= $post['id'] ?>" style="display:none;">
                        <div id="cmtsList-<?= $post['id'] ?>">
                            <div style="text-align:center;padding:12px;color:var(--text-muted);font-size:13px;">
                                <i class="fas fa-spinner fa-spin"></i> Carregando...
                            </div>
                        </div>
                        <?php if ($isLogged): ?>
                        <div class="cmt-input-wrap">
                            <input type="text" id="cmtInput-<?= $post['id'] ?>" placeholder="Escreva um comentário..." onkeypress="if(event.key==='Enter')submitComment(<?= $post['id'] ?>)">
                            <button onclick="submitComment(<?= $post['id'] ?>)"><i class="fas fa-paper-plane"></i></button>
                        </div>
                        <?php else: ?>
                        <div class="login-prompt">
                            <i class="fas fa-lock"></i> <a href="<?= BASE_URL ?>/login.php">Faça login</a> para comentar
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <?php if ($postsPublicos['total_paginas'] > 1): ?>
                <div class="post-load-more">
                    <a href="<?= BASE_URL ?>/?page=posts"><i class="fas fa-stream"></i> Ver todos os posts</a>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="post-empty">
                    <i class="fas fa-newspaper"></i>
                    <p>Nenhuma publicação no mural ainda.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Sidebar -->
            <div>
                <!-- Consultar Chamado -->
                <?php if ($portal['mostrar_consulta']): ?>
                <div class="sidebar-section">
                    <div class="sidebar-card">
                        <div class="sidebar-card-header"><i class="fas fa-search"></i> <?= htmlspecialchars($portal['consulta_titulo']) ?></div>
                        <div class="sidebar-card-body">
                            <form id="formConsultarChamado" class="consultar-form">
                                <div class="form-group">
                                    <label class="form-label">Código do Chamado</label>
                                    <input type="text" name="codigo" class="form-input" required placeholder="TI-2026-00001" id="consultaCodigo" style="text-transform:uppercase;">
                                </div>
                                <button type="submit" class="btn-consultar"><i class="fas fa-search"></i> Consultar</button>
                            </form>
                            <div id="resultadoConsulta" style="display:none;"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Posts Recentes -->
                <?php if ($portal['mostrar_mural'] && !empty($postsRecentes['posts'])): ?>
                <div class="sidebar-section">
                    <div class="sidebar-card">
                        <div class="sidebar-card-header"><i class="fas fa-clock"></i> Posts Recentes</div>
                        <div class="sidebar-card-body" style="padding:8px 20px;">
                            <?php foreach (array_slice($postsRecentes['posts'], 0, 6) as $rp):
                                $rpI = strtoupper(substr($rp['autor_nome'] ?? 'U', 0, 2));
                            ?>
                            <div class="rp-item">
                                <div class="rp-avatar" style="background:<?= htmlspecialchars($rp['departamento_cor'] ?? '#6366F1') ?>">
                                    <?php if (!empty($rp['autor_avatar'])): ?>
                                        <img src="<?= UPLOAD_URL ?>/avatars/<?= htmlspecialchars($rp['autor_avatar']) ?>" alt="">
                                    <?php else: ?>
                                        <?= $rpI ?>
                                    <?php endif; ?>
                                </div>
                                <div class="rp-info">
                                    <div class="rp-author"><?= htmlspecialchars($rp['autor_nome'] ?? 'Usuário') ?></div>
                                    <div class="rp-text"><?= htmlspecialchars(mb_strimwidth(strip_tags($rp['conteudo'] ?? ''), 0, 60, '...')) ?></div>
                                    <div class="rp-stats">
                                        <span><i class="fas fa-heart"></i> <?= (int)$rp['likes_count'] ?></span>
                                        <span><i class="fas fa-comment"></i> <?= (int)$rp['comentarios_count'] ?></span>
                                        <span><?= timeAgo($rp['criado_em']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$isLogged): ?>
                <div class="sidebar-section">
                    <div class="sidebar-card">
                        <div class="sidebar-card-body" style="text-align:center;padding:24px;">
                            <i class="fas fa-user-circle" style="font-size:36px;color:#CBD5E1;margin-bottom:10px;display:block;"></i>
                            <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Faça login para curtir e comentar nos posts</p>
                            <a href="<?= BASE_URL ?>/login.php" class="btn-new-post" style="text-decoration:none;">
                                <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
    <!-- ======================== VIEW 2: FORMULÁRIO ======================== -->

        <div class="alert" id="portalAlert" style="display:none">
            <i class="fas fa-info-circle" id="portalAlertIcon"></i>
            <span id="portalAlertText"></span>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <div class="dept-badge-lg" style="background:<?= htmlspecialchars($deptSelecionado['cor']) ?>">
                    <i class="<?= htmlspecialchars($deptSelecionado['icone']) ?>"></i>
                    <?= htmlspecialchars($deptSelecionado['nome']) ?>
                </div>
                <a href="<?= BASE_URL ?>/portal/" class="back-btn"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>

            <div style="display:flex;gap:8px;margin-bottom:24px;">
                <button class="back-btn tab-btn" onclick="switchTab('abrir')" id="tabAbrir" style="flex:1;justify-content:center;border-color:var(--primary);color:var(--primary);">
                    <i class="fas fa-plus-circle"></i> Abrir Demanda
                </button>
                <button class="back-btn tab-btn" onclick="switchTab('consultar')" id="tabConsultar" style="flex:1;justify-content:center;">
                    <i class="fas fa-search"></i> Consultar
                </button>
            </div>

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
                        <textarea name="descricao" class="form-textarea" rows="5" required placeholder="Descreva com o máximo de detalhes possível"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Anexos (opcional)</label>
                        <input type="file" name="anexos[]" class="form-input" multiple>
                    </div>
                    <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,<?= htmlspecialchars($deptSelecionado['cor']) ?>,<?= htmlspecialchars($deptSelecionado['cor']) ?>cc);">
                        <i class="fas fa-paper-plane"></i> Enviar Demanda para <?= htmlspecialchars($deptSelecionado['sigla']) ?>
                    </button>
                </form>
            </div>

            <div id="panelConsultar" style="display:none;">
                <form id="formConsultarDept">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Código do Chamado</label>
                            <input type="text" name="codigo" class="form-input" required placeholder="<?= $deptSelecionado['sigla'] ?>-2026-00001" id="consultaCodDept" style="text-transform:uppercase;">
                        </div>
                    </div>
                    <button type="submit" class="btn-consultar" style="width:100%;"><i class="fas fa-search"></i> Consultar</button>
                </form>
                <div id="resultadoConsultaDept" style="display:none;margin-top:16px;"></div>
            </div>
        </div>
    <?php endif; ?>

        <div class="portal-footer">
            <?php if (!empty($portal['footer_texto'])): ?>
                <p><?= htmlspecialchars($portal['footer_texto']) ?></p>
            <?php endif; ?>
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
    <!-- Lightbox -->
    <div class="lb-overlay" id="lbOverlay" onclick="closeLightbox()">
        <img id="lbImage" src="" alt="">
    </div>

    <script>
    const PORTAL_API = '<?= BASE_URL ?>/portal/api.php';
    const POSTS_API  = '<?= BASE_URL ?>/api/posts.php';
    const UPLOAD_URL = '<?= UPLOAD_URL ?>';
    const IS_LOGGED  = <?= $isLogged ? 'true' : 'false' ?>;

    /* Helpers */
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
    async function apiJson(url, data) { return (await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })).json(); }
    async function apiForm(url, fd) { return (await fetch(url, { method:'POST', body:fd })).json(); }
    function escHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    function timeAgoJs(ds) {
        const diff = Math.floor((Date.now() - new Date(ds).getTime()) / 1000);
        if (diff < 60) return 'Agora';
        if (diff < 3600) return Math.floor(diff/60) + 'min atrás';
        if (diff < 86400) return Math.floor(diff/3600) + 'h atrás';
        if (diff < 604800) return Math.floor(diff/86400) + 'd atrás';
        return new Date(ds).toLocaleDateString('pt-BR');
    }

    /* Lightbox */
    function openLightbox(src) { document.getElementById('lbImage').src = src; document.getElementById('lbOverlay').classList.add('active'); }
    function closeLightbox() { document.getElementById('lbOverlay').classList.remove('active'); }

    /* Search */
    function filterPosts(q) {
        q = q.toLowerCase().trim();
        document.querySelectorAll('.post-card').forEach(c => {
            c.style.display = (!q || (c.getAttribute('data-searchable')||'').includes(q)) ? '' : 'none';
        });
    }

    /* Like */
    async function toggleLike(pid, btn) {
        if (!IS_LOGGED) return;
        try {
            const r = await apiJson(POSTS_API, { action:'like', post_id: pid });
            if (r.liked !== undefined) {
                const i = btn.querySelector('i'), c = btn.querySelector('.count');
                btn.classList.toggle('liked', r.liked);
                i.className = r.liked ? 'fas fa-heart' : 'far fa-heart';
                c.textContent = r.likes_count ?? parseInt(c.textContent) + (r.liked ? 1 : -1);
            }
        } catch(e) { console.error(e); }
    }

    /* Comments */
    async function toggleComments(pid) {
        const box = document.getElementById('cmts-' + pid);
        if (!box) return;
        if (box.style.display !== 'none') { box.style.display = 'none'; return; }
        box.style.display = 'block';
        await loadComments(pid);
    }
    async function loadComments(pid) {
        const el = document.getElementById('cmtsList-' + pid);
        try {
            const r = await fetch(POSTS_API + '?action=comentarios&post_id=' + pid);
            const data = await r.json();
            if (Array.isArray(data) && data.length) {
                el.innerHTML = data.map(c => {
                    const ini = (c.autor_nome||'U').substring(0,2).toUpperCase();
                    const av = c.autor_avatar ? `<img src="${UPLOAD_URL}/avatars/${c.autor_avatar}" alt="">` : ini;
                    return `<div class="cmt-item">
                        <div class="cmt-avatar">${av}</div>
                        <div class="cmt-body">
                            <div class="cmt-author">${escHtml(c.autor_nome||'Usuário')}</div>
                            <div class="cmt-text">${escHtml(c.conteudo)}</div>
                            <div class="cmt-time">${timeAgoJs(c.criado_em)}</div>
                        </div>
                    </div>`;
                }).join('');
            } else {
                el.innerHTML = '<p style="text-align:center;color:var(--text-muted);font-size:13px;padding:12px;">Nenhum comentário. Seja o primeiro!</p>';
            }
        } catch(e) {
            el.innerHTML = '<p style="text-align:center;color:#EF4444;font-size:13px;padding:12px;">Erro ao carregar comentários</p>';
        }
    }
    async function submitComment(pid) {
        if (!IS_LOGGED) return;
        const inp = document.getElementById('cmtInput-' + pid);
        const txt = inp.value.trim();
        if (!txt) return;
        inp.disabled = true;
        try {
            const r = await apiJson(POSTS_API, { action:'comentar', post_id: pid, conteudo: txt });
            if (r.success) {
                inp.value = '';
                const ce = document.getElementById('cmtCount-' + pid);
                if (ce) ce.textContent = r.comentarios_count;
                await loadComments(pid);
            }
        } catch(e) { console.error(e); }
        inp.disabled = false; inp.focus();
    }

    /* Tabs (View 2) */
    function switchTab(tab) {
        hideAlert();
        const a = document.getElementById('panelAbrir'), c = document.getElementById('panelConsultar');
        const tA = document.getElementById('tabAbrir'), tC = document.getElementById('tabConsultar');
        if (!a) return;
        if (tab === 'abrir') {
            a.style.display = 'block'; c.style.display = 'none';
            tA.style.borderColor = 'var(--primary)'; tA.style.color = 'var(--primary)';
            tC.style.borderColor = 'var(--border)'; tC.style.color = 'var(--text-muted)';
        } else {
            a.style.display = 'none'; c.style.display = 'block';
            tA.style.borderColor = 'var(--border)'; tA.style.color = 'var(--text-muted)';
            tC.style.borderColor = 'var(--primary)'; tC.style.color = 'var(--primary)';
        }
    }

    /* Abrir chamado */
    const fA = document.getElementById('formAbrirChamado');
    if (fA) fA.addEventListener('submit', async function(e) {
        e.preventDefault(); hideAlert();
        const fd = new FormData(this);
        fd.append('acao', 'abrir');
        if (!fd.get('nome')||!fd.get('email')||!fd.get('telefone')||!fd.get('titulo')||!fd.get('descricao')) {
            showAlert('Preencha todos os campos obrigatórios.', 'error'); return;
        }
        showLoading('Registrando sua demanda...', 'Aguarde um momento');
        try {
            const r = await apiForm(PORTAL_API, fd); hideLoading();
            if (r.success) {
                fA.reset(); fA.querySelector('[name="telefone"]').value = '55';
                document.getElementById('successCodigo').textContent = r.codigo;
                document.getElementById('successOverlay').classList.add('active');
                document.getElementById('btnAcompanhar').onclick = function() {
                    document.getElementById('successOverlay').classList.remove('active');
                    (document.getElementById('consultaCodDept')||document.getElementById('consultaCodigo')).value = r.codigo;
                    switchTab('consultar');
                    consultarChamado(r.codigo, 'resultadoConsultaDept');
                };
            } else { showAlert(r.error || 'Erro ao registrar.', 'error'); }
        } catch(ex) { hideLoading(); showAlert('Erro de conexão.', 'error'); }
    });

    /* Consultar */
    async function consultarChamado(cod, cid) {
        showLoading('Consultando...', 'Buscando informações');
        try {
            const r = await apiJson(PORTAL_API, { acao:'consultar', codigo:cod }); hideLoading();
            const box = document.getElementById(cid || 'resultadoConsulta');
            if (r.success) { box.innerHTML = renderResultado(r.chamado); box.style.display = 'block'; }
            else { box.style.display = 'none'; showAlert ? showAlert(r.error||'Chamado não encontrado.','error') : alert(r.error); }
        } catch(ex) { hideLoading(); alert('Erro de conexão.'); }
    }
    const fCH = document.getElementById('formConsultarChamado');
    if (fCH) fCH.addEventListener('submit', function(e) {
        e.preventDefault();
        consultarChamado(document.getElementById('consultaCodigo').value.trim().toUpperCase(), 'resultadoConsulta');
    });
    const fCD = document.getElementById('formConsultarDept');
    if (fCD) fCD.addEventListener('submit', function(e) {
        e.preventDefault();
        consultarChamado(document.getElementById('consultaCodDept').value.trim().toUpperCase(), 'resultadoConsultaDept');
    });

    function renderResultado(ch) {
        let h = `<div class="chamado-result"><h3><i class="fas fa-ticket-alt"></i> ${ch.codigo} — ${ch.titulo}</h3>`;
        if (ch.departamento) h += `<div style="margin-bottom:12px"><span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:600;background:${ch.departamento_cor||'#6366F1'}20;color:${ch.departamento_cor||'#6366F1'}"><i class="${ch.departamento_icone||'fas fa-building'}"></i> ${ch.departamento}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Status</span><span class="status-badge" style="background:${ch.status_cor}20;color:${ch.status_cor}"><i class="${ch.status_icone}"></i> ${ch.status_label}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Prioridade</span><span>${ch.prioridade}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Categoria</span><span>${ch.categoria}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Responsável</span><span>${ch.tecnico}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Aberto em</span><span>${ch.data_abertura}</span></div>`;
        h += `<div class="result-row"><span class="result-label">Descrição</span><span>${ch.descricao}</span></div>`;
        if (ch.comentarios && ch.comentarios.length) {
            h += `<div class="cmt-list-result"><h4 style="margin-bottom:10px;font-size:14px;"><i class="fas fa-comments"></i> Comentários</h4>`;
            ch.comentarios.forEach(c => h += `<div class="cmt-item-result"><div class="cmt-meta-result"><strong>${c.autor}</strong> — ${c.data}</div><div>${c.conteudo}</div></div>`);
            h += `</div>`;
        }
        return h + `</div>`;
    }
    </script>
</body>
</html>
