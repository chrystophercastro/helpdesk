<?php
/**
 * Portal Público: Assinatura Digital de Termo de Responsabilidade
 * Acessado via link com token único enviado por WhatsApp
 * Modo ?print=1 renderiza versão para impressão
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Database.php';

$db = Database::getInstance();

// ===== MODO IMPRESSÃO (requer autenticação) =====
if (isset($_GET['print']) && $_GET['print'] == '1' && isset($_GET['termo_id'])) {
    session_start();
    if (empty($_SESSION['usuario_id'])) {
        die('Acesso não autorizado.');
    }
    $termoId = (int)$_GET['termo_id'];
    $termo = $db->fetch(
        "SELECT t.*, i.nome AS ativo_nome, i.numero_patrimonio, i.tipo AS ativo_tipo, 
                i.modelo AS ativo_modelo, i.fabricante AS ativo_fabricante, i.numero_serie AS ativo_serie,
                u.nome AS tecnico_nome 
         FROM termos_responsabilidade t 
         LEFT JOIN inventario i ON t.ativo_id = i.id 
         LEFT JOIN usuarios u ON t.tecnico_id = u.id 
         WHERE t.id = ?", [$termoId]);
    if (!$termo) die('Termo não encontrado.');
    // Buscar fotos do termo
    try {
        $fotos = $db->fetchAll("SELECT * FROM termos_fotos WHERE termo_id = ? ORDER BY criado_em ASC", [$termoId]);
        $termo['fotos'] = $fotos ?: [];
    } catch (Exception $e) { $termo['fotos'] = []; }
    renderPrint($termo);
    exit;
}

// ===== MODO ASSINATURA PÚBLICA (via token) =====
$token = $_GET['token'] ?? '';
if (!$token || strlen($token) < 32) {
    die('Link inválido.');
}

$termo = $db->fetch(
    "SELECT t.*, i.nome AS ativo_nome, i.numero_patrimonio, i.tipo AS ativo_tipo,
            i.modelo AS ativo_modelo, i.fabricante AS ativo_fabricante, i.numero_serie AS ativo_serie,
            u.nome AS tecnico_nome 
     FROM termos_responsabilidade t 
     LEFT JOIN inventario i ON t.ativo_id = i.id 
     LEFT JOIN usuarios u ON t.tecnico_id = u.id 
     WHERE t.token = ?", [$token]);

if (!$termo) {
    die('Termo não encontrado ou link inválido.');
}

// Buscar fotos do termo
try {
    $fotos = $db->fetchAll("SELECT * FROM termos_fotos WHERE termo_id = ? ORDER BY criado_em ASC", [$termo['id']]);
    $termo['fotos'] = $fotos ?: [];
} catch (Exception $e) { $termo['fotos'] = []; }

$erro = '';
$sucesso = false;

// ===== PROCESSAR ASSINATURA =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $termo['status'] === 'pendente') {
    // Handle both regular POST and JSON body
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $postData = json_decode(file_get_contents('php://input'), true);
    } else {
        $postData = $_POST;
    }

    $assinatura = $postData['assinatura'] ?? '';
    if (!$assinatura || !str_starts_with($assinatura, 'data:image/png;base64,')) {
        if (stripos($contentType, 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Por favor, assine antes de confirmar.']);
            exit;
        }
        $erro = 'Por favor, assine no quadro abaixo antes de confirmar.';
    } else {
        $db->update('termos_responsabilidade', [
            'usuario_assinatura' => $assinatura,
            'data_assinatura_usuario' => date('Y-m-d H:i:s'),
            'ip_assinatura_usuario' => $_SERVER['REMOTE_ADDR'] ?? '',
            'status' => 'assinado'
        ], 'id = ?', [$termo['id']]);

        // Notificar equipe técnica
        try {
            require_once __DIR__ . '/../app/models/Notificacao.php';
            $notificacao = new Notificacao();
            $tecnicos = $db->fetchAll("SELECT telefone FROM usuarios WHERE tipo IN ('admin','tecnico') AND ativo = 1 AND telefone IS NOT NULL");
            $msg = "✅ *TERMO ASSINADO*\n\n"
                 . "O termo *{$termo['codigo']}* foi assinado digitalmente.\n"
                 . "👤 Usuário: *{$termo['usuario_nome']}*\n"
                 . "📦 Ativo: *{$termo['ativo_nome']}*\n"
                 . "🏷️ Patrimônio: *{$termo['numero_patrimonio']}*\n"
                 . "📍 IP: {$_SERVER['REMOTE_ADDR']}\n"
                 . "📅 " . date('d/m/Y H:i');
            foreach ($tecnicos as $tec) {
                if (!empty($tec['telefone'])) {
                    $notificacao->sendWhatsApp($tec['telefone'], $msg);
                }
            }
        } catch (Exception $e) {
            // Ignora erro de notificação
        }

        // Se veio via AJAX, retorna JSON
        if (stripos($contentType, 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        $sucesso = true;
        $termo['status'] = 'assinado';
    }
}

$tiposAtivo = ['computador'=>'Computador','servidor'=>'Servidor','switch'=>'Switch','roteador'=>'Roteador','impressora'=>'Impressora','software'=>'Software','monitor'=>'Monitor','telefone'=>'Telefone','outro'=>'Outro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Termo - <?= htmlspecialchars($termo['codigo']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F1F5F9; color: #1E293B; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 680px; width: 100%; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1E40AF, #3B82F6); color: white; padding: 32px; text-align: center; }
        .header h1 { font-size: 1.4rem; margin-bottom: 4px; }
        .header p { opacity: .85; font-size: .9rem; }
        .header .code-badge { display: inline-block; background: rgba(255,255,255,.2); padding: 4px 14px; border-radius: 20px; font-size: .85rem; margin-top: 8px; font-weight: 600; }
        .content { padding: 32px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .info-item { background: #F8FAFC; padding: 14px; border-radius: 10px; }
        .info-item label { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #64748B; display: block; margin-bottom: 2px; font-weight: 600; }
        .info-item span { font-size: .92rem; font-weight: 500; }
        .section-title { font-size: 1rem; font-weight: 600; margin: 24px 0 12px; display: flex; align-items: center; gap: 8px; color: #1E40AF; }
        .section-title i { font-size: .9rem; }
        .conditions { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; padding: 16px; margin-bottom: 24px; font-size: .88rem; color: #92400E; }
        .sig-existing { text-align: center; margin: 16px 0; }
        .sig-existing img { max-width: 300px; border: 1px solid #E2E8F0; border-radius: 8px; background: #fff; padding: 8px; }
        .sig-existing small { display: block; margin-top: 4px; color: #64748B; font-size: .78rem; }
        .sig-wrapper { position: relative; border: 2px dashed #CBD5E1; border-radius: 12px; overflow: hidden; margin: 12px 0; background: #FAFAFA; }
        .sig-wrapper canvas { width: 100%; height: 150px; display: block; cursor: crosshair; touch-action: none; }
        .sig-clear { position: absolute; top: 8px; right: 8px; background: #EF4444; color: #fff; border: none; border-radius: 6px; padding: 4px 10px; font-size: .75rem; cursor: pointer; }
        .sig-hint { text-align: center; color: #94A3B8; font-size: .78rem; margin-bottom: 12px; }
        .sig-tabs { display: flex; gap: 4px; background: #F1F5F9; padding: 4px; border-radius: 10px; margin-bottom: 16px; }
        .sig-tab { flex: 1; padding: 10px; text-align: center; border: none; background: transparent; border-radius: 8px; cursor: pointer; font-size: .85rem; font-weight: 500; color: #64748B; transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .sig-tab.active { background: #fff; color: #1E40AF; box-shadow: 0 1px 4px rgba(0,0,0,.08); font-weight: 600; }
        .sig-tab:hover:not(.active) { background: #E2E8F0; }
        .sig-panel { display: none; }
        .sig-panel.active { display: block; }
        .rubrica-preview { text-align: center; padding: 20px; }
        .rubrica-canvas-wrap { display: inline-block; border: 1px solid #E2E8F0; border-radius: 12px; background: #fff; padding: 10px; margin-bottom: 12px; }
        .rubrica-canvas-wrap canvas { display: block; }
        .rubrica-styles { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 12px; }
        .rubrica-style-btn { padding: 8px 16px; border: 2px solid #E2E8F0; background: #fff; border-radius: 8px; cursor: pointer; font-size: .82rem; transition: all .2s; color: #475569; }
        .rubrica-style-btn.active { border-color: #3B82F6; background: #EFF6FF; color: #1E40AF; }
        .rubrica-style-btn:hover { border-color: #93C5FD; }
        .btn-submit { width: 100%; padding: 14px; background: #10B981; color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background .2s; }
        .btn-submit:hover { background: #059669; }
        .btn-submit:disabled { background: #94A3B8; cursor: not-allowed; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: .88rem; }
        .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5; }
        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7; text-align: center; }
        .success-icon { font-size: 3rem; color: #10B981; margin-bottom: 12px; }
        .signed-badge { display: inline-flex; align-items: center; gap: 6px; background: #ECFDF5; color: #065F46; padding: 6px 14px; border-radius: 20px; font-size: .85rem; font-weight: 600; margin-top: 8px; }
        .cancelled-badge { display: inline-flex; align-items: center; gap: 6px; background: #FEF2F2; color: #991B1B; padding: 6px 14px; border-radius: 20px; font-size: .85rem; font-weight: 600; margin-top: 8px; }
        .ip-info { text-align: center; color: #94A3B8; font-size: .72rem; margin-top: 16px; }
        .fotos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-bottom: 20px; }
        .foto-grid-item { border-radius: 10px; overflow: hidden; border: 1px solid #E2E8F0; cursor: pointer; aspect-ratio: 1; }
        .foto-grid-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .2s; display: block; }
        .foto-grid-item:hover img { transform: scale(1.05); }
        @media(max-width:600px){
            .info-grid{grid-template-columns:1fr;}
            .header{padding:24px 16px;}
            .content{padding:24px 16px;}
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-file-signature"></i> Termo de Responsabilidade</h1>
        <p>Assinatura Digital de Equipamento</p>
        <div class="code-badge"><?= $termo['codigo'] ?></div>
        <?php if ($termo['status'] === 'assinado'): ?>
            <div class="signed-badge"><i class="fas fa-check-circle"></i> Assinado</div>
        <?php elseif ($termo['status'] === 'cancelado'): ?>
            <div class="cancelled-badge"><i class="fas fa-times-circle"></i> Cancelado</div>
        <?php endif; ?>
    </div>
    <div class="content">
        <?php if ($erro): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= $erro ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                <h3>Termo assinado com sucesso!</h3>
                <p style="margin-top:8px">Sua assinatura digital foi registrada. A equipe de TI foi notificada.</p>
            </div>
        <?php endif; ?>

        <!-- Informações do Equipamento -->
        <h3 class="section-title"><i class="fas fa-laptop"></i> Equipamento</h3>
        <div class="info-grid">
            <div class="info-item"><label>Nome</label><span><?= htmlspecialchars($termo['ativo_nome'] ?? '-') ?></span></div>
            <div class="info-item"><label>Patrimônio</label><span><?= htmlspecialchars($termo['numero_patrimonio'] ?? '-') ?></span></div>
            <div class="info-item"><label>Tipo</label><span><?= $tiposAtivo[$termo['ativo_tipo'] ?? ''] ?? ($termo['ativo_tipo'] ?? '-') ?></span></div>
            <div class="info-item"><label>Modelo</label><span><?= htmlspecialchars($termo['ativo_modelo'] ?? '-') ?></span></div>
            <?php if (!empty($termo['ativo_fabricante'])): ?>
            <div class="info-item"><label>Fabricante</label><span><?= htmlspecialchars($termo['ativo_fabricante']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($termo['ativo_serie'])): ?>
            <div class="info-item"><label>Nº Série</label><span><?= htmlspecialchars($termo['ativo_serie']) ?></span></div>
            <?php endif; ?>
        </div>

        <!-- Informações do Usuário -->
        <h3 class="section-title"><i class="fas fa-user"></i> Responsável</h3>
        <div class="info-grid">
            <div class="info-item"><label>Nome</label><span><?= htmlspecialchars($termo['usuario_nome']) ?></span></div>
            <?php if (!empty($termo['usuario_cargo'])): ?>
            <div class="info-item"><label>Cargo</label><span><?= htmlspecialchars($termo['usuario_cargo']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($termo['usuario_departamento'])): ?>
            <div class="info-item"><label>Departamento</label><span><?= htmlspecialchars($termo['usuario_departamento']) ?></span></div>
            <?php endif; ?>
            <div class="info-item"><label>Técnico</label><span><?= htmlspecialchars($termo['tecnico_nome'] ?? '-') ?></span></div>
            <div class="info-item"><label>Data Entrega</label><span><?= date('d/m/Y', strtotime($termo['data_entrega'])) ?></span></div>
        </div>

        <?php if (!empty($termo['condicoes'])): ?>
        <h3 class="section-title"><i class="fas fa-clipboard-list"></i> Condições</h3>
        <div class="conditions"><?= nl2br(htmlspecialchars($termo['condicoes'])) ?></div>
        <?php endif; ?>

        <?php if (!empty($termo['fotos'])): ?>
        <h3 class="section-title"><i class="fas fa-camera"></i> Fotos do Equipamento</h3>
        <div class="fotos-grid">
            <?php foreach ($termo['fotos'] as $foto): ?>
            <div class="foto-grid-item" onclick="this.querySelector('img').requestFullscreen ? this.querySelector('img').requestFullscreen() : null">
                <img src="<?= BASE_URL ?>/uploads/termos/<?= htmlspecialchars($foto['nome_arquivo']) ?>" alt="<?= htmlspecialchars($foto['nome_original']) ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Declaração -->
        <div class="conditions" style="background:#EFF6FF;border-color:#93C5FD;color:#1E40AF;">
            <strong><i class="fas fa-info-circle"></i> Declaração de Responsabilidade</strong><br><br>
            Declaro que recebi o equipamento acima descrito em perfeito estado de funcionamento, 
            comprometendo-me a utilizá-lo exclusivamente para fins profissionais, zelando pela sua 
            conservação e integridade. Responsabilizo-me por quaisquer danos causados por mau uso, 
            negligência ou imprudência, comprometendo-me a comunicar imediatamente à equipe de TI 
            qualquer problema, defeito ou extravio do mesmo.
        </div>

        <!-- Assinatura do Técnico (já assinada) -->
        <h3 class="section-title"><i class="fas fa-user-shield"></i> Assinatura do Técnico</h3>
        <?php if (!empty($termo['tecnico_assinatura'])): ?>
        <div class="sig-existing">
            <img src="<?= $termo['tecnico_assinatura'] ?>" alt="Assinatura do Técnico">
            <small><?= htmlspecialchars($termo['tecnico_nome'] ?? '') ?> — <?= date('d/m/Y H:i', strtotime($termo['data_assinatura_tecnico'])) ?></small>
        </div>
        <?php endif; ?>

        <!-- Assinatura do Usuário -->
        <h3 class="section-title"><i class="fas fa-pen-fancy"></i> Sua Assinatura</h3>
        <?php if ($termo['status'] === 'assinado' && !empty($termo['usuario_assinatura'])): ?>
            <div class="sig-existing">
                <img src="<?= $termo['usuario_assinatura'] ?>" alt="Assinatura do Usuário">
                <small><?= htmlspecialchars($termo['usuario_nome']) ?> — <?= date('d/m/Y H:i', strtotime($termo['data_assinatura_usuario'])) ?></small>
            </div>
        <?php elseif ($termo['status'] === 'pendente'): ?>
            <!-- Tabs: Desenhar / Rubrica -->
            <div class="sig-tabs">
                <button type="button" class="sig-tab active" onclick="switchSigTab('desenhar')">
                    <i class="fas fa-pen-fancy"></i> Desenhar Assinatura
                </button>
                <button type="button" class="sig-tab" onclick="switchSigTab('rubrica')">
                    <i class="fas fa-font"></i> Usar Rubrica
                </button>
            </div>

            <!-- Painel: Desenhar -->
            <div id="panelDesenhar" class="sig-panel active">
                <p class="sig-hint">Desenhe sua assinatura no quadro abaixo usando o dedo ou mouse</p>
                <div class="sig-wrapper">
                    <canvas id="canvasUsuario"></canvas>
                    <button type="button" class="sig-clear" onclick="limparCanvas()"><i class="fas fa-eraser"></i> Limpar</button>
                </div>
            </div>

            <!-- Painel: Rubrica -->
            <div id="panelRubrica" class="sig-panel">
                <div class="rubrica-preview">
                    <p class="sig-hint">Rubrica gerada automaticamente com seu nome</p>
                    <div class="rubrica-canvas-wrap">
                        <canvas id="canvasRubrica" width="400" height="120"></canvas>
                    </div>
                    <div class="rubrica-styles">
                        <button type="button" class="rubrica-style-btn active" onclick="changeRubricaStyle(0)">Cursiva</button>
                        <button type="button" class="rubrica-style-btn" onclick="changeRubricaStyle(1)">Elegante</button>
                        <button type="button" class="rubrica-style-btn" onclick="changeRubricaStyle(2)">Iniciais</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-submit" id="btnAssinar" onclick="enviarAssinatura()">
                <i class="fas fa-check-circle"></i> Assinar Digitalmente
            </button>

            <div id="msgErro" class="alert alert-error" style="display:none;margin-top:12px"></div>

            <div class="ip-info">
                <i class="fas fa-shield-alt"></i> Sua assinatura será registrada com IP <?= $_SERVER['REMOTE_ADDR'] ?? '' ?> e data/hora atuais.
            </div>
        <?php elseif ($termo['status'] === 'cancelado'): ?>
            <div class="alert alert-error" style="text-align:center">
                <i class="fas fa-ban"></i> Este termo foi cancelado e não pode mais ser assinado.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($termo['status'] === 'pendente' && !$sucesso): ?>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Great+Vibes&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">
<script>
// ===== CONFIG =====
const nomeCompleto = <?= json_encode($termo['usuario_nome']) ?>;
let modoAtivo = 'desenhar'; // 'desenhar' ou 'rubrica'
let rubricaStyleIdx = 0;

// ===== NOME HELPERS =====
function getIniciais(nome) {
    const partes = nome.trim().split(/\s+/);
    if (partes.length === 1) return partes[0].charAt(0).toUpperCase();
    return (partes[0].charAt(0) + partes[partes.length - 1].charAt(0)).toUpperCase();
}

function getNomeAbreviado(nome) {
    const partes = nome.trim().split(/\s+/);
    if (partes.length === 1) return partes[0];
    return partes[0] + ' ' + partes[partes.length - 1];
}

// ===== TABS =====
function switchSigTab(modo) {
    modoAtivo = modo;
    document.querySelectorAll('.sig-tab').forEach((t, i) => {
        t.classList.toggle('active', (i === 0 && modo === 'desenhar') || (i === 1 && modo === 'rubrica'));
    });
    document.getElementById('panelDesenhar').classList.toggle('active', modo === 'desenhar');
    document.getElementById('panelRubrica').classList.toggle('active', modo === 'rubrica');
    if (modo === 'rubrica') renderRubrica();
}

// ===== CANVAS DESENHO =====
const canvas = document.getElementById('canvasUsuario');
const ctx = canvas.getContext('2d');
let drawing = false, lastX = 0, lastY = 0;

function setupCanvas() {
    const r = canvas.getBoundingClientRect();
    const w = r.width, h = r.height;
    canvas.width = w * 2;
    canvas.height = h * 2;
    ctx.scale(2, 2);
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.strokeStyle = '#1E293B';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
}

function getPos(e) {
    const r = canvas.getBoundingClientRect();
    const t = e.touches ? e.touches[0] : e;
    return { x: t.clientX - r.left, y: t.clientY - r.top };
}
function startDraw(e) { e.preventDefault(); drawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; }
function draw(e) { if (!drawing) return; e.preventDefault(); const p = getPos(e); ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke(); lastX = p.x; lastY = p.y; }
function endDraw() { drawing = false; }

canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', endDraw);
canvas.addEventListener('mouseleave', endDraw);
canvas.addEventListener('touchstart', startDraw, { passive: false });
canvas.addEventListener('touchmove', draw, { passive: false });
canvas.addEventListener('touchend', endDraw);

function limparCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function isCanvasBlank(c) {
    const d = c.getContext('2d').getImageData(0, 0, c.width, c.height).data;
    for (let i = 3; i < d.length; i += 4) { if (d[i] !== 0) return false; }
    return true;
}

// ===== RUBRICA =====
const rubricaStyles = [
    { font: '48px "Dancing Script"', color: '#1E293B', label: 'Cursiva', getText: getNomeAbreviado },
    { font: '44px "Great Vibes"', color: '#1E3A5F', label: 'Elegante', getText: getNomeAbreviado },
    { font: 'italic bold 56px "Playfair Display"', color: '#1E293B', label: 'Iniciais', getText: getIniciais }
];

function changeRubricaStyle(idx) {
    rubricaStyleIdx = idx;
    document.querySelectorAll('.rubrica-style-btn').forEach((b, i) => b.classList.toggle('active', i === idx));
    renderRubrica();
}

function renderRubrica() {
    const rc = document.getElementById('canvasRubrica');
    const rctx = rc.getContext('2d');
    const dpr = 2;
    rc.width = 400 * dpr;
    rc.height = 120 * dpr;
    rc.style.width = '400px';
    rc.style.height = '120px';
    rctx.scale(dpr, dpr);
    rctx.clearRect(0, 0, 400, 120);

    const style = rubricaStyles[rubricaStyleIdx];
    const texto = style.getText(nomeCompleto);

    rctx.font = style.font;
    rctx.fillStyle = style.color;
    rctx.textAlign = 'center';
    rctx.textBaseline = 'middle';
    rctx.fillText(texto, 200, 55);

    // Linha decorativa embaixo
    const m = rctx.measureText(texto);
    const lw = Math.min(m.width + 20, 350);
    rctx.strokeStyle = style.color;
    rctx.lineWidth = 1.5;
    rctx.globalAlpha = 0.4;
    rctx.beginPath();
    const sx = 200 - lw / 2;
    rctx.moveTo(sx, 82);
    // Curva suave
    rctx.quadraticCurveTo(200, 76, 200 + lw / 2, 82);
    rctx.stroke();
    rctx.globalAlpha = 1;
}

// ===== ENVIO VIA AJAX =====
function enviarAssinatura() {
    const btn = document.getElementById('btnAssinar');
    const errDiv = document.getElementById('msgErro');
    errDiv.style.display = 'none';

    let dataUrl;
    if (modoAtivo === 'desenhar') {
        if (isCanvasBlank(canvas)) {
            errDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Desenhe sua assinatura no quadro antes de confirmar.';
            errDiv.style.display = 'block';
            return;
        }
        dataUrl = canvas.toDataURL('image/png');
    } else {
        dataUrl = document.getElementById('canvasRubrica').toDataURL('image/png');
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assinatura: dataUrl })
    })
    .then(r => {
        if (!r.ok) throw new Error('Erro HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success) {
            // Substituir toda a área de assinatura por mensagem de sucesso
            document.querySelector('.content').innerHTML = `
                <div class="alert alert-success" style="margin-top:0">
                    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Termo assinado com sucesso!</h3>
                    <p style="margin-top:8px">Sua assinatura digital foi registrada.<br>A equipe de TI foi notificada.</p>
                </div>
            `;
            // Atualizar header
            const codeBadge = document.querySelector('.code-badge');
            if (codeBadge) {
                codeBadge.insertAdjacentHTML('afterend', '<div class="signed-badge"><i class="fas fa-check-circle"></i> Assinado</div>');
            }
        } else {
            throw new Error(data.error || 'Erro ao processar assinatura.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Assinar Digitalmente';
        errDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + err.message + ' Tente novamente.';
        errDiv.style.display = 'block';
    });
}

// ===== INIT =====
setupCanvas();
window.addEventListener('resize', setupCanvas);

// Pré-carregar fontes e renderizar rubrica após carregamento
document.fonts.ready.then(() => {
    renderRubrica();
});
</script>
<?php endif; ?>
</body>
</html>

<?php
// ===== FUNÇÃO: VERSÃO PARA IMPRESSÃO =====
function renderPrint($termo) {
    $tiposAtivo = ['computador'=>'Computador','servidor'=>'Servidor','switch'=>'Switch','roteador'=>'Roteador','impressora'=>'Impressora','software'=>'Software','monitor'=>'Monitor','telefone'=>'Telefone','outro'=>'Outro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Termo <?= $termo['codigo'] ?> - Impressão</title>
    <style>
        @media print { @page { margin: 1.5cm; } body { -webkit-print-color-adjust: exact; } .no-print { display: none !important; } }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; padding: 20px; font-size: 12pt; line-height: 1.5; }
        .print-header { text-align: center; border-bottom: 3px solid #1E40AF; padding-bottom: 16px; margin-bottom: 24px; }
        .print-header h1 { font-size: 16pt; color: #1E40AF; }
        .print-header .code { font-size: 11pt; color: #64748B; margin-top: 4px; }
        .section { margin-bottom: 20px; }
        .section h3 { font-size: 11pt; color: #1E40AF; border-bottom: 1px solid #CBD5E1; padding-bottom: 4px; margin-bottom: 10px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.info td { padding: 6px 10px; border: 1px solid #E2E8F0; font-size: 10pt; }
        table.info td.label { background: #F1F5F9; font-weight: 600; width: 30%; color: #475569; }
        .declaration { background: #F8FAFC; border: 1px solid #CBD5E1; padding: 14px; border-radius: 4px; font-size: 10pt; margin: 20px 0; text-align: justify; }
        .signatures { display: flex; gap: 40px; margin-top: 40px; }
        .sig-block { flex: 1; text-align: center; }
        .sig-block img { max-width: 250px; max-height: 100px; display: block; margin: 0 auto 8px; }
        .sig-block .sig-line { border-top: 1px solid #333; padding-top: 6px; margin-top: 8px; font-size: 9pt; }
        .sig-block .sig-name { font-weight: 600; font-size: 10pt; }
        .sig-block .sig-date { font-size: 8pt; color: #64748B; }
        .footer { text-align: center; margin-top: 40px; padding-top: 16px; border-top: 1px solid #E2E8F0; font-size: 8pt; color: #94A3B8; }
        .btn-print { position: fixed; bottom: 20px; right: 20px; padding: 12px 24px; background: #1E40AF; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 11pt; z-index: 999; }
        .fotos-print-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .fotos-print-grid img { width: 100%; height: auto; max-height: 180px; object-fit: cover; border: 1px solid #CBD5E1; border-radius: 4px; }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir</button>

    <div class="print-header">
        <h1>TERMO DE RESPONSABILIDADE DE EQUIPAMENTO</h1>
        <div class="code"><?= $termo['codigo'] ?> | Emitido em <?= date('d/m/Y', strtotime($termo['data_entrega'])) ?></div>
    </div>

    <div class="section">
        <h3>Dados do Equipamento</h3>
        <table class="info">
            <tr><td class="label">Nome</td><td><?= htmlspecialchars($termo['ativo_nome'] ?? '-') ?></td><td class="label">Patrimônio</td><td><?= htmlspecialchars($termo['numero_patrimonio'] ?? '-') ?></td></tr>
            <tr><td class="label">Tipo</td><td><?= $tiposAtivo[$termo['ativo_tipo'] ?? ''] ?? '-' ?></td><td class="label">Modelo</td><td><?= htmlspecialchars($termo['ativo_modelo'] ?? '-') ?></td></tr>
            <tr><td class="label">Fabricante</td><td><?= htmlspecialchars($termo['ativo_fabricante'] ?? '-') ?></td><td class="label">Nº Série</td><td><?= htmlspecialchars($termo['ativo_serie'] ?? '-') ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Dados do Responsável</h3>
        <table class="info">
            <tr><td class="label">Nome</td><td colspan="3"><?= htmlspecialchars($termo['usuario_nome']) ?></td></tr>
            <tr><td class="label">Cargo</td><td><?= htmlspecialchars($termo['usuario_cargo'] ?? '-') ?></td><td class="label">Departamento</td><td><?= htmlspecialchars($termo['usuario_departamento'] ?? '-') ?></td></tr>
            <tr><td class="label">Email</td><td><?= htmlspecialchars($termo['usuario_email'] ?? '-') ?></td><td class="label">Telefone</td><td><?= htmlspecialchars($termo['usuario_telefone'] ?? '-') ?></td></tr>
        </table>
    </div>

    <?php if (!empty($termo['condicoes'])): ?>
    <div class="section">
        <h3>Condições / Observações</h3>
        <p style="font-size:10pt"><?= nl2br(htmlspecialchars($termo['condicoes'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($termo['fotos'])): ?>
    <div class="section">
        <h3>Fotos do Equipamento</h3>
        <div class="fotos-print-grid">
            <?php foreach ($termo['fotos'] as $foto): ?>
            <img src="<?= BASE_URL ?>/uploads/termos/<?= htmlspecialchars($foto['nome_arquivo']) ?>" alt="<?= htmlspecialchars($foto['nome_original']) ?>">
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="declaration">
        <strong>DECLARAÇÃO DE RESPONSABILIDADE:</strong> Declaro que recebi o equipamento acima descrito em perfeito 
        estado de funcionamento, comprometendo-me a utilizá-lo exclusivamente para fins profissionais, zelando pela 
        sua conservação e integridade. Responsabilizo-me por quaisquer danos causados por mau uso, negligência ou 
        imprudência, comprometendo-me a comunicar imediatamente à equipe de TI qualquer problema, defeito ou extravio do mesmo.
    </div>

    <div class="signatures">
        <div class="sig-block">
            <?php if (!empty($termo['tecnico_assinatura'])): ?>
            <img src="<?= $termo['tecnico_assinatura'] ?>" alt="Assinatura Técnico">
            <?php endif; ?>
            <div class="sig-line">
                <div class="sig-name"><?= htmlspecialchars($termo['tecnico_nome'] ?? '-') ?></div>
                <div>Técnico Responsável</div>
                <?php if ($termo['data_assinatura_tecnico']): ?>
                <div class="sig-date"><?= date('d/m/Y H:i', strtotime($termo['data_assinatura_tecnico'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="sig-block">
            <?php if (!empty($termo['usuario_assinatura'])): ?>
            <img src="<?= $termo['usuario_assinatura'] ?>" alt="Assinatura Usuário">
            <?php endif; ?>
            <div class="sig-line">
                <div class="sig-name"><?= htmlspecialchars($termo['usuario_nome']) ?></div>
                <div>Usuário Responsável</div>
                <?php if ($termo['data_assinatura_usuario']): ?>
                <div class="sig-date"><?= date('d/m/Y H:i', strtotime($termo['data_assinatura_usuario'])) ?> | IP: <?= $termo['ip_assinatura_usuario'] ?? '' ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="footer">
        Documento gerado pelo sistema HelpDesk TI — <?= $termo['codigo'] ?> — <?= date('d/m/Y H:i') ?><br>
        Este documento possui validade com assinaturas digitais registradas no sistema.
    </div>

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>
<?php } ?>
