<?php
/**
 * View: Acesso Remoto
 * Central de Conexão Remota — WebSocket Viewer + Gerenciamento
 */
$user = currentUser();
$isTecnico = in_array($user['tipo'], ['admin', 'tecnico']);

require_once __DIR__ . '/../../models/RemoteDesktop.php';
$remoteModel = new RemoteDesktop();
$config = $remoteModel->getAllConfig();
$serverPort = (int)($config['server_port'] ?? 8089);
$serverRunning = $remoteModel->isServerRunning();
$stats = $remoteModel->estatisticas();
?>

<style>
/* ===== Remote Desktop Styles ===== */
.remote-page { position: relative; }
.remote-tabs { display: flex; gap: 4px; margin-bottom: 20px; background: var(--gray-100); border-radius: var(--radius-lg); padding: 4px; }
.remote-tab { padding: 10px 20px; border-radius: var(--radius); cursor: pointer; font-weight: 500; font-size: 14px; border: none; background: transparent; color: var(--gray-500); transition: var(--transition); display: flex; align-items: center; gap: 8px; }
.remote-tab:hover { color: var(--gray-700); }
.remote-tab.active { background: white; color: var(--primary); box-shadow: var(--shadow-sm); }
.remote-tab .badge { background: var(--primary); color: white; font-size: 11px; padding: 2px 8px; border-radius: 10px; }

.remote-panel { display: none; }
.remote-panel.active { display: block; }

/* Stats cards */
.remote-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.remote-stat-card { background: white; border-radius: var(--radius-lg); padding: 20px; border: 1px solid var(--gray-200); }
.remote-stat-card .stat-value { font-size: 28px; font-weight: 700; color: var(--gray-800); }
.remote-stat-card .stat-label { font-size: 12px; color: var(--gray-500); margin-top: 4px; }
.remote-stat-card .stat-icon { width: 40px; height: 40px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; font-size: 18px; }

/* Server control */
.server-control { background: white; border-radius: var(--radius-lg); padding: 24px; border: 1px solid var(--gray-200); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.server-status { display: flex; align-items: center; gap: 12px; }
.server-dot { width: 12px; height: 12px; border-radius: 50%; }
.server-dot.online { background: var(--success); box-shadow: 0 0 8px rgba(16,185,129,0.4); animation: pulse-green 2s infinite; }
.server-dot.offline { background: var(--gray-400); }
@keyframes pulse-green { 0%, 100% { box-shadow: 0 0 8px rgba(16,185,129,0.4); } 50% { box-shadow: 0 0 16px rgba(16,185,129,0.6); } }

/* Connection panel */
.connect-panel { background: white; border-radius: var(--radius-lg); padding: 24px; border: 1px solid var(--gray-200); margin-bottom: 24px; }
.connect-row { display: flex; gap: 12px; align-items: end; }
.connect-row .form-group { flex: 1; }
.connect-row input { font-size: 20px; letter-spacing: 4px; text-transform: uppercase; font-weight: 700; text-align: center; font-family: 'Consolas', monospace; }

/* Client list */
.client-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.client-card { background: white; border-radius: var(--radius-lg); padding: 20px; border: 1px solid var(--gray-200); transition: var(--transition); }
.client-card:hover { border-color: var(--primary-light); box-shadow: var(--shadow-md); }
.client-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.client-card-icon { width: 44px; height: 44px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 20px; }
.client-card-icon.windows { background: #E3F2FD; color: #1565C0; }
.client-card-icon.linux { background: #FFF3E0; color: #E65100; }
.client-card-icon.mac { background: #F3E5F5; color: #6A1B9A; }
.client-card-name { font-weight: 600; font-size: 15px; color: var(--gray-800); }
.client-card-user { font-size: 12px; color: var(--gray-500); }
.client-card-details { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.client-card-detail { font-size: 12px; color: var(--gray-500); display: flex; align-items: center; gap: 4px; background: var(--gray-50); padding: 4px 8px; border-radius: 4px; }
.client-card-actions { display: flex; gap: 8px; }
.client-status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.client-status-badge.online { background: var(--success-bg); color: #059669; }
.client-status-badge.conectado { background: var(--primary-bg); color: var(--primary-dark); }

/* Viewer */
.remote-viewer { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: #0a0a0a; z-index: 10000; display: none; flex-direction: column; }
.remote-viewer.active { display: flex; }
.viewer-toolbar { background: rgba(30,41,59,0.95); backdrop-filter: blur(10px); padding: 8px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); z-index: 10001; min-height: 48px; }
.viewer-toolbar-group { display: flex; align-items: center; gap: 8px; }
.viewer-toolbar .toolbar-btn { background: rgba(255,255,255,0.1); border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 6px; transition: 0.15s; }
.viewer-toolbar .toolbar-btn:hover { background: rgba(255,255,255,0.2); }
.viewer-toolbar .toolbar-btn.active { background: var(--primary); }
.viewer-toolbar .toolbar-btn.danger { background: var(--danger); }
.viewer-toolbar .toolbar-info { color: #94A3B8; font-size: 12px; }
.viewer-screen { flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; cursor: default; }
.viewer-screen canvas { max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; -webkit-user-drag: none; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; will-change: contents; transform: translateZ(0); }
.viewer-screen .no-signal { color: #475569; text-align: center; }
.viewer-screen .no-signal i { font-size: 64px; margin-bottom: 16px; display: block; }
.viewer-screen .no-signal p { font-size: 14px; }

/* File transfer modal */
.file-transfer-panel { position: fixed; bottom: 80px; right: 24px; width: 360px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); z-index: 10002; display: none; }
.file-transfer-panel.active { display: block; }
.file-transfer-header { padding: 16px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; }
.file-transfer-body { padding: 16px; max-height: 300px; overflow-y: auto; }
.file-transfer-dropzone { border: 2px dashed var(--gray-300); border-radius: var(--radius); padding: 32px; text-align: center; color: var(--gray-500); cursor: pointer; transition: var(--transition); }
.file-transfer-dropzone:hover, .file-transfer-dropzone.dragover { border-color: var(--primary); color: var(--primary); background: var(--primary-bg); }
.file-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--gray-100); }
.file-item:last-child { border-bottom: none; }
.file-item-icon { font-size: 20px; color: var(--gray-400); }
.file-item-info { flex: 1; }
.file-item-name { font-size: 13px; font-weight: 500; }
.file-item-size { font-size: 11px; color: var(--gray-500); }
.file-progress { height: 4px; background: var(--gray-200); border-radius: 2px; overflow: hidden; margin-top: 4px; }
.file-progress-bar { height: 100%; background: var(--primary); transition: width 0.3s; }

/* History table */
.history-table-wrapper { background: white; border-radius: var(--radius-lg); border: 1px solid var(--gray-200); overflow: hidden; }
.history-table-wrapper table { width: 100%; }

/* Settings panel */
.remote-settings { max-width: 600px; }
.remote-settings .form-group { margin-bottom: 20px; }

/* Responsive */
@media (max-width: 768px) {
    .remote-tabs { overflow-x: auto; }
    .connect-row { flex-direction: column; }
    .client-grid { grid-template-columns: 1fr; }
    .remote-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="remote-page">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px;font-weight:700;color:var(--gray-800);margin:0;">
                <i class="fas fa-desktop" style="color:var(--primary);margin-right:8px;"></i>
                Acesso Remoto
            </h1>
            <p style="color:var(--gray-500);font-size:14px;margin-top:4px;">Central de Conexão Remota</p>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-outline" onclick="remoteRefresh()" title="Atualizar">
                <i class="fas fa-sync-alt"></i>
            </button>
            <a href="<?= BASE_URL ?>/api/remoto.php?action=download_client" class="btn btn-primary" title="Baixar instalador com servidor pré-configurado">
                <i class="fas fa-download"></i> Baixar Instalador
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="remote-tabs">
        <button class="remote-tab active" data-tab="conexao">
            <i class="fas fa-plug"></i> Conexão
        </button>
        <button class="remote-tab" data-tab="clientes">
            <i class="fas fa-laptop"></i> Clientes Online
            <span class="badge" id="badgeOnline"><?= $stats['online'] + $stats['conectados'] ?></span>
        </button>
        <button class="remote-tab" data-tab="historico">
            <i class="fas fa-history"></i> Histórico
        </button>
        <?php if ($user['tipo'] === 'admin'): ?>
        <button class="remote-tab" data-tab="config">
            <i class="fas fa-cog"></i> Configurações
        </button>
        <?php endif; ?>
    </div>

    <!-- ==================== PANEL: Conexão ==================== -->
    <div class="remote-panel active" id="panel-conexao">
        <!-- Server Status -->
        <div class="server-control">
            <div class="server-status">
                <div class="server-dot <?= $serverRunning ? 'online' : 'offline' ?>" id="serverDot"></div>
                <div>
                    <div style="font-weight:600;color:var(--gray-800);">Servidor WebSocket</div>
                    <div style="font-size:12px;color:var(--gray-500);">
                        Porta <strong><?= $serverPort ?></strong> — 
                        <span id="serverStatusText"><?= $serverRunning ? 'Online' : 'Offline' ?></span>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <?php if ($isTecnico): ?>
                <button class="btn <?= $serverRunning ? 'btn-danger' : 'btn-success' ?>" id="btnServerToggle" onclick="toggleServer()">
                    <i class="fas fa-<?= $serverRunning ? 'stop' : 'play' ?>"></i>
                    <span><?= $serverRunning ? 'Parar' : 'Iniciar' ?></span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats -->
        <div class="remote-stats">
            <div class="remote-stat-card">
                <div class="stat-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-wifi"></i></div>
                <div class="stat-value" id="statOnline"><?= $stats['online'] ?></div>
                <div class="stat-label">Online</div>
            </div>
            <div class="remote-stat-card">
                <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary);"><i class="fas fa-link"></i></div>
                <div class="stat-value" id="statConectados"><?= $stats['conectados'] ?></div>
                <div class="stat-label">Conectados</div>
            </div>
            <div class="remote-stat-card">
                <div class="stat-icon" style="background:var(--purple-bg);color:var(--purple);"><i class="fas fa-clock"></i></div>
                <div class="stat-value" id="statHoje"><?= $stats['total_hoje'] ?></div>
                <div class="stat-label">Sessões Hoje</div>
            </div>
            <div class="remote-stat-card">
                <div class="stat-icon" style="background:var(--warning-bg);color:var(--warning);"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-value" id="statTempo"><?= $stats['tempo_medio'] ? gmdate('i:s', $stats['tempo_medio']) : '--' ?></div>
                <div class="stat-label">Tempo Médio</div>
            </div>
        </div>

        <!-- Conexão por Código -->
        <div class="connect-panel">
            <h3 style="margin-bottom:16px;font-size:16px;font-weight:600;color:var(--gray-800);">
                <i class="fas fa-key" style="color:var(--primary);margin-right:8px;"></i>
                Conectar por Código
            </h3>
            <div class="connect-row">
                <div class="form-group">
                    <label class="form-label">Código de Acesso</label>
                    <input type="text" id="inputCode" class="form-control" placeholder="EX: ABC123" maxlength="6"
                           style="font-size:24px;letter-spacing:6px;text-transform:uppercase;font-weight:700;text-align:center;font-family:Consolas,monospace;">
                </div>
                <button class="btn btn-primary btn-lg" onclick="connectByCode()" id="btnConnect" style="white-space:nowrap;height:52px;padding:0 32px;margin-bottom:0;">
                    <i class="fas fa-desktop"></i> Conectar
                </button>
            </div>
            <p style="margin-top:12px;font-size:12px;color:var(--gray-500);">
                <i class="fas fa-info-circle"></i>
                Peça ao usuário para abrir o <strong>HelpDesk Remote</strong> e informar o código exibido.
            </p>
        </div>
    </div>

    <!-- ==================== PANEL: Clientes Online ==================== -->
    <div class="remote-panel" id="panel-clientes">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:16px;font-weight:600;color:var(--gray-800);">Clientes Disponíveis</h3>
            <button class="btn btn-outline btn-sm" onclick="loadClients()">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
        <div class="client-grid" id="clientGrid">
            <div class="empty-state" style="grid-column:1/-1;padding:60px;text-align:center;">
                <i class="fas fa-laptop" style="font-size:48px;color:var(--gray-300);margin-bottom:16px;display:block;"></i>
                <p style="color:var(--gray-500);">Nenhum cliente online no momento.</p>
                <p style="color:var(--gray-400);font-size:12px;margin-top:8px;">Os clientes aparecerão aqui quando abrirem o HelpDesk Remote.</p>
            </div>
        </div>
    </div>

    <!-- ==================== PANEL: Histórico ==================== -->
    <div class="remote-panel" id="panel-historico">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:16px;font-weight:600;color:var(--gray-800);">Histórico de Conexões</h3>
        </div>
        <div class="history-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Técnico</th>
                        <th>Computador</th>
                        <th>IP</th>
                        <th>Início</th>
                        <th>Duração</th>
                        <th>Chamado</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400);">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== PANEL: Configurações ==================== -->
    <?php if ($user['tipo'] === 'admin'): ?>
    <div class="remote-panel" id="panel-config">
        <div class="remote-settings">
            <div class="card" style="padding:24px;">
                <h3 style="margin-bottom:20px;font-size:16px;font-weight:600;">Configurações do Servidor</h3>
                <div class="form-group">
                    <label class="form-label">IP / Host do Servidor</label>
                    <input type="text" id="cfgHost" class="form-control" value="<?= htmlspecialchars($config['server_host'] ?? $_SERVER['SERVER_ADDR'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost') ?>" placeholder="Ex: 192.168.1.100 ou meuserver.local">
                    <small class="text-muted">IP ou hostname que os clientes remotos usarão para conectar. Será embutido no instalador.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Porta WebSocket</label>
                    <input type="number" id="cfgPort" class="form-control" value="<?= $config['server_port'] ?? 8089 ?>" min="1024" max="65535">
                    <small class="text-muted">Porta do servidor WebSocket relay (padrão: 8089)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">FPS Máximo</label>
                    <input type="number" id="cfgFps" class="form-control" value="<?= $config['max_fps'] ?? 15 ?>" min="1" max="30">
                </div>
                <div class="form-group">
                    <label class="form-label">Qualidade Padrão (JPEG)</label>
                    <input type="number" id="cfgQuality" class="form-control" value="<?= $config['default_quality'] ?? 50 ?>" min="10" max="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Escala Padrão</label>
                    <select id="cfgScale" class="form-control">
                        <?php $defScale = $config['default_scale'] ?? '0.6'; ?>
                        <option value="0.3" <?= $defScale == '0.3' ? 'selected' : '' ?>>30%</option>
                        <option value="0.4" <?= $defScale == '0.4' ? 'selected' : '' ?>>40%</option>
                        <option value="0.5" <?= $defScale == '0.5' ? 'selected' : '' ?>>50%</option>
                        <option value="0.6" <?= $defScale == '0.6' ? 'selected' : '' ?>>60%</option>
                        <option value="0.7" <?= $defScale == '0.7' ? 'selected' : '' ?>>70%</option>
                        <option value="0.8" <?= $defScale == '0.8' ? 'selected' : '' ?>>80%</option>
                        <option value="0.9" <?= $defScale == '0.9' ? 'selected' : '' ?>>90%</option>
                        <option value="1.0" <?= $defScale == '1.0' ? 'selected' : '' ?>>100%</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Timeout da Sessão (segundos)</label>
                    <input type="number" id="cfgTimeout" class="form-control" value="<?= $config['session_timeout'] ?? 300 ?>" min="60" max="3600">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" id="cfgApproval" <?= ($config['require_approval'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span>Exigir aprovação do usuário antes de conectar</span>
                    </label>
                </div>
                <button class="btn btn-primary" onclick="saveConfig()">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
            </div>

            <!-- Card Proxy WSS -->
            <div class="card" style="padding:24px;margin-top:16px;">
                <h3 style="margin-bottom:16px;font-size:16px;font-weight:600;">
                    <i class="fas fa-shield-alt" style="color:var(--primary);margin-right:6px;"></i>
                    Proxy WSS (HTTPS → WebSocket)
                </h3>
                <div id="proxyStatusArea" style="margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:8px;padding:12px;background:var(--gray-50);border-radius:8px;">
                        <i class="fas fa-spinner fa-spin" style="color:var(--gray-400);"></i>
                        <span style="color:var(--gray-500);">Verificando status...</span>
                    </div>
                </div>
                <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px;">
                    Necessário para acessar o WebSocket quando o sistema usa <strong>HTTPS</strong>.
                    Configura automaticamente os módulos <code>proxy_wstunnel</code> e regras de proxy no Apache.
                </p>
                <button class="btn btn-outline" onclick="configureProxy()" id="btnProxy">
                    <i class="fas fa-cogs"></i> Configurar Proxy Automaticamente
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ==================== VIEWER FULLSCREEN ==================== -->
<div class="remote-viewer" id="remoteViewer">
    <div class="viewer-toolbar">
        <div class="viewer-toolbar-group">
            <span style="color:white;font-weight:600;font-size:14px;" id="viewerTitle">
                <i class="fas fa-desktop" style="margin-right:6px;"></i>
                <span id="viewerHostname">---</span>
            </span>
            <span class="viewer-toolbar .toolbar-info" style="color:#94A3B8;font-size:12px;" id="viewerInfo">---</span>
            <span style="color:#10B981;font-size:11px;font-weight:600;margin-left:8px;background:rgba(16,185,129,0.15);padding:2px 8px;border-radius:10px;" id="viewerFps">-- FPS</span>
        </div>
        <div class="viewer-toolbar-group">
            <button class="toolbar-btn" onclick="viewerToggleInput()" id="btnInput" title="Controle Remoto">
                <i class="fas fa-mouse-pointer"></i> Controle
            </button>
            <button class="toolbar-btn" onclick="viewerClipboard()" title="Área de Transferência">
                <i class="fas fa-clipboard"></i>
            </button>
            <button class="toolbar-btn" onclick="viewerToggleFiles()" title="Transferir Arquivo">
                <i class="fas fa-file-upload"></i>
            </button>
            <button class="toolbar-btn" onclick="viewerSettings()" title="Qualidade">
                <i class="fas fa-sliders-h"></i>
            </button>
            <button class="toolbar-btn" onclick="viewerFullscreen()" title="Tela Cheia">
                <i class="fas fa-expand"></i>
            </button>
            <button class="toolbar-btn danger" onclick="viewerDisconnect()" title="Desconectar">
                <i class="fas fa-times"></i> Sair
            </button>
        </div>
    </div>

    <div class="viewer-screen" id="viewerScreen">
        <div class="no-signal" id="viewerNoSignal">
            <i class="fas fa-satellite-dish"></i>
            <p>Aguardando conexão...</p>
        </div>
        <canvas id="viewerCanvas" style="display:none;pointer-events:none;user-select:none;" width="1" height="1"></canvas>
    </div>

    <!-- File Transfer Panel -->
    <div class="file-transfer-panel" id="filePanel">
        <div class="file-transfer-header">
            <strong><i class="fas fa-file-upload"></i> Transferir Arquivos</strong>
            <button onclick="viewerToggleFiles()" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--gray-400);">&times;</button>
        </div>
        <div class="file-transfer-body">
            <div class="file-transfer-dropzone" id="fileDropzone">
                <i class="fas fa-cloud-upload-alt" style="font-size:32px;margin-bottom:8px;display:block;"></i>
                <p>Arraste arquivos aqui ou clique para selecionar</p>
                <input type="file" id="fileInput" multiple style="display:none;">
            </div>
            <div id="fileList" style="margin-top:12px;"></div>
        </div>
    </div>
</div>

<script>
// ==========================================
//  Estado Global
// ==========================================
const BASE_URL = <?= json_encode(BASE_URL) ?>;
const API = BASE_URL + '/api/remoto.php';
const USER_ID = <?= (int)$user['id'] ?>;
const USER_NOME = <?= json_encode($user['nome']) ?>;
let ws = null;
let serverRunning = <?= $serverRunning ? 'true' : 'false' ?>;
let viewerActive = false;
let inputEnabled = true;
let remoteResolution = [1920, 1080];
let connectedCode = '';
let frameCount = 0;
let fpsInterval = null;

// ==========================================
//  Tabs
// ==========================================
document.querySelectorAll('.remote-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.remote-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.remote-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        const panel = document.getElementById('panel-' + tab.dataset.tab);
        if (panel) panel.classList.add('active');

        if (tab.dataset.tab === 'clientes') loadClients();
        if (tab.dataset.tab === 'historico') loadHistory();
    });
});

// ==========================================
//  Server Control
// ==========================================
async function toggleServer() {
    const btn = document.getElementById('btnServerToggle');
    const action = serverRunning ? 'stop_server' : 'start_server';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aguarde...';

    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action})
        });
        const data = await r.json();

        if (data.success) {
            serverRunning = data.running;
            updateServerUI();
            showToast(data.message, 'success');
        } else {
            let msg = data.error || data.message || 'Erro ao iniciar';
            if (data.debug) {
                console.error('Server debug:', data.debug);
                if (data.debug.log_hint) msg += '\n' + data.debug.log_hint;
            }
            showToast(msg, 'error');
        }
    } catch (e) {
        showToast('Erro de comunicação: ' + e.message, 'error');
    }
    btn.disabled = false;
    updateServerUI();
}

function updateServerUI() {
    const dot = document.getElementById('serverDot');
    const text = document.getElementById('serverStatusText');
    const btn = document.getElementById('btnServerToggle');

    if (serverRunning) {
        dot.className = 'server-dot online';
        text.textContent = 'Online';
        if (btn) {
            btn.className = 'btn btn-danger';
            btn.innerHTML = '<i class="fas fa-stop"></i> <span>Parar</span>';
        }
    } else {
        dot.className = 'server-dot offline';
        text.textContent = 'Offline';
        if (btn) {
            btn.className = 'btn btn-success';
            btn.innerHTML = '<i class="fas fa-play"></i> <span>Iniciar</span>';
        }
    }
}

// ==========================================
//  Conectar por Código
// ==========================================
function connectByCode() {
    const code = document.getElementById('inputCode').value.trim().toUpperCase();
    if (!code || code.length < 4) {
        showToast('Informe um código válido', 'warning');
        return;
    }
    if (!serverRunning) {
        showToast('O servidor WebSocket não está rodando. Inicie-o primeiro.', 'warning');
        return;
    }
    startViewer(code);
}

document.getElementById('inputCode').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') connectByCode();
});

// ==========================================
//  Viewer (Tela Remota)
// ==========================================
function startViewer(code, chamadoId) {
    const serverHost = window.location.hostname;
    const serverPort = window.location.port;
    const isSecure = window.location.protocol === 'https:';
    // Sempre usar proxy via Apache (/ws-remote/) — evita bloqueio de firewall na porta direta
    const wsProto = isSecure ? 'wss' : 'ws';
    const portPart = serverPort ? ':' + serverPort : '';
    const wsUrl = `${wsProto}://${serverHost}${portPart}/ws-remote/`;

    console.log('[Remote] Connecting to:', wsUrl, 'Code:', code);

    viewerActive = true;
    connectedCode = code;
    document.getElementById('remoteViewer').classList.add('active');
    document.getElementById('viewerNoSignal').style.display = 'flex';
    document.getElementById('viewerCanvas').style.display = 'none';
    document.getElementById('viewerHostname').textContent = 'Conectando a ' + code + '...';
    document.body.style.overflow = 'hidden';

    // Conectar WebSocket
    try {
        ws = new WebSocket(wsUrl);
        ws.binaryType = 'blob';

        ws.onopen = () => {
            console.log('[Remote] WebSocket connected OK');
            ws.send(JSON.stringify({
                type: 'connect',
                code: code,
                tecnico_id: USER_ID,
                tecnico_nome: USER_NOME,
                chamado_id: chamadoId || null,
            }));
        };

        ws.onmessage = (e) => {
            if (e.data instanceof Blob) {
                // Screen frame (binary JPEG)
                handleFrame(e.data);
                return;
            }
            try {
                const msg = JSON.parse(e.data);
                handleViewerMessage(msg);
            } catch (err) {}
        };

        ws.onerror = (e) => {
            console.error('[Remote] WebSocket error:', e);
            showToast('Erro na conexão WebSocket (' + wsUrl + ')', 'error');
        };

        ws.onclose = (e) => {
            console.log('[Remote] WebSocket closed: code=' + e.code + ' reason=' + e.reason);
            if (viewerActive) {
                showToast('Conexão encerrada (código: ' + e.code + ')', 'info');
                viewerDisconnect();
            }
        };
    } catch (e) {
        showToast('Falha ao conectar: ' + e.message, 'error');
        viewerDisconnect();
    }
}

function handleViewerMessage(msg) {
    switch (msg.type) {
        case 'connected':
            document.getElementById('viewerNoSignal').style.display = 'none';
            document.getElementById('viewerCanvas').style.display = 'block';
            document.getElementById('viewerHostname').textContent = msg.hostname || 'Remoto';
            document.getElementById('viewerInfo').textContent = 
                `${msg.username || ''} • ${msg.ip || ''} • ${msg.os || ''} • ${(msg.resolution||[]).join('x')}`;
            remoteResolution = msg.resolution || [1920, 1080];
            startFpsCounter();
            showToast('Conectado a ' + msg.hostname, 'success');
            break;

        case 'approval':
            if (!msg.approved) {
                showToast('O usuário recusou a conexão', 'warning');
                viewerDisconnect();
            }
            break;

        case 'clipboard':
            if (msg.text) {
                navigator.clipboard.writeText(msg.text).then(() => {
                    // silently sync clipboard
                }).catch(() => {});
            }
            break;

        case 'file_info':
            handleFileReceiveStart(msg);
            break;

        case 'file_chunk':
            handleFileReceiveChunk(msg);
            break;

        case 'file_complete':
            handleFileReceiveComplete(msg);
            break;

        case 'disconnected':
            showToast('Remoto desconectou: ' + (msg.reason || ''), 'info');
            viewerDisconnect();
            break;

        case 'error':
            showToast(msg.message || 'Erro', 'error');
            break;

        case 'pong':
            break;
    }
}

let currentImgUrl = null;
let _pendingFrame = null;
let _frameProcessing = false;
let _canvasW = 0;
let _canvasH = 0;
const _viewerCanvas = document.getElementById('viewerCanvas');
const _viewerCtx = _viewerCanvas ? _viewerCanvas.getContext('2d') : null;

function handleFrame(blob) {
    // v4.0: Canvas-based rendering com suporte a Delta Encoding
    // Protocolo: Full JPEG (0xFF 0xD8) ou Delta (0x44 0x45 + header + JPEG região)
    _pendingFrame = blob;
    if (_frameProcessing) return;
    _processNextFrame();
}

function _processNextFrame() {
    if (!_pendingFrame) {
        _frameProcessing = false;
        return;
    }
    _frameProcessing = true;
    const blob = _pendingFrame;
    _pendingFrame = null;

    blob.arrayBuffer().then(buf => {
        const view = new Uint8Array(buf);
        if (view.length < 2) {
            _frameProcessing = false;
            return;
        }

        // Check: Delta frame starts with "DE" (0x44 0x45)
        if (view[0] === 0x44 && view[1] === 0x45 && view.length > 14) {
            // ====== DELTA FRAME ======
            const dv = new DataView(buf);
            const fw = dv.getUint16(2, true);  // full width
            const fh = dv.getUint16(4, true);  // full height
            const rx = dv.getUint16(6, true);  // region x
            const ry = dv.getUint16(8, true);  // region y
            const rw = dv.getUint16(10, true); // region width
            const rh = dv.getUint16(12, true); // region height

            // Ensure canvas size (only resize when resolution changes)
            if (_canvasW !== fw || _canvasH !== fh) {
                // Save existing content before resize
                let savedImage = null;
                if (_canvasW > 0 && _canvasH > 0 && _viewerCtx) {
                    try { savedImage = _viewerCtx.getImageData(0, 0, _canvasW, _canvasH); } catch(e) {}
                }
                _viewerCanvas.width = fw;
                _viewerCanvas.height = fh;
                _canvasW = fw;
                _canvasH = fh;
                if (savedImage && _viewerCtx) {
                    try { _viewerCtx.putImageData(savedImage, 0, 0); } catch(e) {}
                }
            }

            // Decode only the region JPEG and paint at position
            const jpegSlice = buf.slice(14);
            const jpegBlob = new Blob([jpegSlice], {type: 'image/jpeg'});
            createImageBitmap(jpegBlob).then(bmp => {
                _viewerCtx.drawImage(bmp, rx, ry);
                bmp.close();
                frameCount++;
                if (_pendingFrame) {
                    requestAnimationFrame(_processNextFrame);
                } else {
                    _frameProcessing = false;
                }
            }).catch(() => {
                _frameProcessing = false;
            });

        } else {
            // ====== FULL FRAME (standard JPEG, starts with 0xFF 0xD8) ======
            const jpegBlob = new Blob([view], {type: 'image/jpeg'});
            createImageBitmap(jpegBlob).then(bmp => {
                if (_canvasW !== bmp.width || _canvasH !== bmp.height) {
                    _viewerCanvas.width = bmp.width;
                    _viewerCanvas.height = bmp.height;
                    _canvasW = bmp.width;
                    _canvasH = bmp.height;
                }
                _viewerCtx.drawImage(bmp, 0, 0);
                bmp.close();
                frameCount++;
                if (_pendingFrame) {
                    requestAnimationFrame(_processNextFrame);
                } else {
                    _frameProcessing = false;
                }
            }).catch(() => {
                // Fallback: try as img src
                if (currentImgUrl) URL.revokeObjectURL(currentImgUrl);
                currentImgUrl = URL.createObjectURL(blob);
                const tmpImg = new window.Image();
                tmpImg.onload = () => {
                    if (_canvasW !== tmpImg.width || _canvasH !== tmpImg.height) {
                        _viewerCanvas.width = tmpImg.width;
                        _viewerCanvas.height = tmpImg.height;
                        _canvasW = tmpImg.width;
                        _canvasH = tmpImg.height;
                    }
                    _viewerCtx.drawImage(tmpImg, 0, 0);
                    frameCount++;
                    _frameProcessing = false;
                };
                tmpImg.onerror = () => { _frameProcessing = false; };
                tmpImg.src = currentImgUrl;
            });
        }
    }).catch(() => {
        _frameProcessing = false;
    });
}

function startFpsCounter() {
    frameCount = 0;
    if (fpsInterval) clearInterval(fpsInterval);
    fpsInterval = setInterval(() => {
        // Mostrar FPS no toolbar
        const fpsEl = document.getElementById('viewerFps');
        if (fpsEl) fpsEl.textContent = frameCount + ' FPS';
        frameCount = 0;
    }, 1000);
}

function viewerDisconnect() {
    viewerActive = false;
    if (ws) {
        try { ws.send(JSON.stringify({type: 'disconnect'})); } catch(e){}
        try { ws.close(); } catch(e){}
        ws = null;
    }
    if (fpsInterval) { clearInterval(fpsInterval); fpsInterval = null; }
    if (currentImgUrl) { URL.revokeObjectURL(currentImgUrl); currentImgUrl = null; }
    // Clear canvas
    _canvasW = 0; _canvasH = 0;
    if (_viewerCtx && _viewerCanvas) {
        _viewerCtx.clearRect(0, 0, _viewerCanvas.width, _viewerCanvas.height);
        _viewerCanvas.width = 1; _viewerCanvas.height = 1;
    }
    _pendingFrame = null;
    _frameProcessing = false;
    document.getElementById('remoteViewer').classList.remove('active');
    document.body.style.overflow = '';
    connectedCode = '';
    remoteRefresh();
}

// ==========================================
//  Input (Mouse / Teclado)
// ==========================================

function viewerToggleInput() {
    inputEnabled = !inputEnabled;
    const btn = document.getElementById('btnInput');
    btn.classList.toggle('active', inputEnabled);
    document.getElementById('viewerScreen').style.cursor = inputEnabled ? 'default' : 'not-allowed';
}

// Mouse events no viewer
const viewerScreen = document.getElementById('viewerScreen');
const viewerCanvas = document.getElementById('viewerCanvas');

let inputDebugCount = 0;
function getRelativeCoords(e) {
    const el = viewerCanvas;
    const rect = el.getBoundingClientRect();
    // Clamp coords to canvas display bounds
    const cx = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
    const cy = Math.max(0, Math.min(e.clientY - rect.top, rect.height));
    // Map from display coordinates to full remote resolution
    const x = cx / rect.width * remoteResolution[0];
    const y = cy / rect.height * remoteResolution[1];
    return { x: Math.round(x), y: Math.round(y) };
}

// Throttle mouse move to avoid flooding
let lastMouseSend = 0;
viewerScreen.addEventListener('mousemove', (e) => {
    if (!inputEnabled || !ws || !viewerActive) return;
    const now = Date.now();
    if (now - lastMouseSend < 25) return; // ~40fps max para mouse move (v3.0: reduz tráfego)
    lastMouseSend = now;
    const {x, y} = getRelativeCoords(e);
    ws.send(JSON.stringify({type: 'mouse', x, y, action: 'move'}));
});

viewerScreen.addEventListener('mousedown', (e) => {
    if (!inputEnabled || !ws || !viewerActive) return;
    e.preventDefault();
    const {x, y} = getRelativeCoords(e);
    const button = e.button === 2 ? 'right' : e.button === 1 ? 'middle' : 'left';
    ws.send(JSON.stringify({type: 'mouse', x, y, action: 'mousedown', button}));
});

viewerScreen.addEventListener('mouseup', (e) => {
    if (!inputEnabled || !ws || !viewerActive) return;
    e.preventDefault();
    const {x, y} = getRelativeCoords(e);
    const button = e.button === 2 ? 'right' : e.button === 1 ? 'middle' : 'left';
    ws.send(JSON.stringify({type: 'mouse', x, y, action: 'mouseup', button}));
});

viewerScreen.addEventListener('click', (e) => {
    if (!inputEnabled || !ws || !viewerActive) return;
    const {x, y} = getRelativeCoords(e);
    ws.send(JSON.stringify({type: 'mouse', x, y, action: 'click', button: 'left'}));
});

viewerScreen.addEventListener('dblclick', (e) => {
    if (!inputEnabled || !ws || !viewerActive) return;
    e.preventDefault();
    const {x, y} = getRelativeCoords(e);
    ws.send(JSON.stringify({type: 'mouse', x, y, action: 'dblclick', button: 'left'}));
});

viewerScreen.addEventListener('contextmenu', (e) => {
    if (!inputEnabled || !ws || !viewerActive) return;
    e.preventDefault();
    const {x, y} = getRelativeCoords(e);
    ws.send(JSON.stringify({type: 'mouse', x, y, action: 'rightclick'}));
});

viewerScreen.addEventListener('wheel', (e) => {
    if (!inputEnabled || !ws || !viewerActive) return;
    e.preventDefault();
    const {x, y} = getRelativeCoords(e);
    const delta = e.deltaY > 0 ? -3 : 3;
    ws.send(JSON.stringify({type: 'mouse', x, y, action: 'scroll', delta}));
}, {passive: false});

// Keyboard
document.addEventListener('keydown', (e) => {
    if (!viewerActive || !inputEnabled || !ws) return;
    // Não capturar se estiver em input/textarea
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

    e.preventDefault();

    // Mapear teclas
    const key = mapKey(e);
    if (!key) return;

    // Hotkeys com modifiers
    const modifiers = [];
    if (e.ctrlKey) modifiers.push('ctrl');
    if (e.altKey) modifiers.push('alt');
    if (e.shiftKey) modifiers.push('shift');

    if (modifiers.length > 0) {
        ws.send(JSON.stringify({type: 'key', key: [...modifiers, key].join('+'), action: 'hotkey'}));
    } else {
        ws.send(JSON.stringify({type: 'key', key, action: 'press'}));
    }
});

function mapKey(e) {
    const keyMap = {
        'Backspace': 'backspace', 'Tab': 'tab', 'Enter': 'enter', 'Escape': 'escape',
        'Delete': 'delete', 'Home': 'home', 'End': 'end', 'PageUp': 'pageup', 'PageDown': 'pagedown',
        'ArrowUp': 'up', 'ArrowDown': 'down', 'ArrowLeft': 'left', 'ArrowRight': 'right',
        'F1': 'f1', 'F2': 'f2', 'F3': 'f3', 'F4': 'f4', 'F5': 'f5', 'F6': 'f6',
        'F7': 'f7', 'F8': 'f8', 'F9': 'f9', 'F10': 'f10', 'F11': 'f11', 'F12': 'f12',
        'Insert': 'insert', 'PrintScreen': 'printscreen', ' ': 'space',
        'Control': null, 'Alt': null, 'Shift': null, 'Meta': null,
    };
    if (e.key in keyMap) return keyMap[e.key];
    if (e.key.length === 1) return e.key.toLowerCase();
    return null;
}

// ==========================================
//  Clipboard
// ==========================================
async function viewerClipboard() {
    if (!ws || !viewerActive) return;
    try {
        const text = await navigator.clipboard.readText();
        if (text) {
            ws.send(JSON.stringify({type: 'clipboard', text}));
            showToast('Clipboard enviado', 'success');
        }
    } catch (e) {
        // Fallback: prompt
        const text = prompt('Texto para enviar à área de transferência remota:');
        if (text) {
            ws.send(JSON.stringify({type: 'clipboard', text}));
        }
    }
}

// ==========================================
//  Transferência de Arquivo
// ==========================================
function viewerToggleFiles() {
    document.getElementById('filePanel').classList.toggle('active');
}

const fileDropzone = document.getElementById('fileDropzone');
const fileInput = document.getElementById('fileInput');

fileDropzone.addEventListener('click', () => fileInput.click());
fileDropzone.addEventListener('dragover', (e) => { e.preventDefault(); fileDropzone.classList.add('dragover'); });
fileDropzone.addEventListener('dragleave', () => fileDropzone.classList.remove('dragover'));
fileDropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    fileDropzone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener('change', (e) => {
    handleFiles(e.target.files);
    fileInput.value = '';
});

function handleFiles(files) {
    if (!ws || !viewerActive) {
        showToast('Conecte-se primeiro', 'warning');
        return;
    }
    for (const file of files) {
        sendFile(file);
    }
}

function sendFile(file) {
    const fileId = Math.random().toString(36).substr(2, 12);
    const chunkSize = 64 * 1024;
    const totalChunks = Math.ceil(file.size / chunkSize);

    // Add to UI
    const listEl = document.getElementById('fileList');
    const item = document.createElement('div');
    item.className = 'file-item';
    item.id = 'file-' + fileId;
    item.innerHTML = `
        <div class="file-item-icon"><i class="fas fa-file"></i></div>
        <div class="file-item-info">
            <div class="file-item-name">${file.name}</div>
            <div class="file-item-size">${formatFileSize(file.size)}</div>
            <div class="file-progress"><div class="file-progress-bar" id="progress-${fileId}" style="width:0%"></div></div>
        </div>
    `;
    listEl.appendChild(item);

    // Send file info
    ws.send(JSON.stringify({
        type: 'file_info', id: fileId, name: file.name, size: file.size, total_chunks: totalChunks,
    }));

    // Send chunks
    let index = 0;
    const reader = new FileReader();

    function readNextChunk() {
        const start = index * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        reader.readAsArrayBuffer(file.slice(start, end));
    }

    reader.onload = () => {
        const chunk = btoa(String.fromCharCode(...new Uint8Array(reader.result)));
        ws.send(JSON.stringify({
            type: 'file_chunk', id: fileId, index, data: chunk, total: totalChunks,
        }));

        const progress = Math.round(((index + 1) / totalChunks) * 100);
        const progressBar = document.getElementById('progress-' + fileId);
        if (progressBar) progressBar.style.width = progress + '%';

        index++;
        if (index < totalChunks) {
            setTimeout(readNextChunk, 10);
        } else {
            ws.send(JSON.stringify({type: 'file_complete', id: fileId, name: file.name}));
            showToast('Arquivo enviado: ' + file.name, 'success');
        }
    };

    readNextChunk();
}

// Recebendo arquivo do client
let receivingFiles = {};

function handleFileReceiveStart(msg) {
    receivingFiles[msg.id] = { name: msg.name, size: msg.size, total: msg.total_chunks, chunks: {} };
}

function handleFileReceiveChunk(msg) {
    if (receivingFiles[msg.id]) {
        receivingFiles[msg.id].chunks[msg.index] = msg.data;
    }
}

function handleFileReceiveComplete(msg) {
    const finfo = receivingFiles[msg.id];
    if (!finfo) return;
    delete receivingFiles[msg.id];

    // Reassemble
    const chunks = [];
    for (let i = 0; i < finfo.total; i++) {
        if (finfo.chunks[i]) {
            const binary = atob(finfo.chunks[i]);
            const bytes = new Uint8Array(binary.length);
            for (let j = 0; j < binary.length; j++) bytes[j] = binary.charCodeAt(j);
            chunks.push(bytes);
        }
    }

    const blob = new Blob(chunks);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = finfo.name;
    a.click();
    URL.revokeObjectURL(url);
    showToast('Arquivo recebido: ' + finfo.name, 'success');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// ==========================================
//  Viewer Extras
// ==========================================
function viewerSettings() {
    if (!ws || !viewerActive) return;
    const quality = prompt('Qualidade JPEG (10-100):', '50');
    const fps = prompt('FPS (1-30):', '10');
    const scale = prompt('Escala (0.3-1.0):', '0.6');
    if (quality || fps || scale) {
        ws.send(JSON.stringify({
            type: 'settings',
            quality: parseInt(quality) || 50,
            fps: parseInt(fps) || 10,
            scale: parseFloat(scale) || 0.6,
        }));
    }
}

function viewerFullscreen() {
    const el = document.getElementById('remoteViewer');
    if (!document.fullscreenElement) {
        el.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
}

// ==========================================
//  Carregar Clientes Online
// ==========================================
async function loadClients() {
    try {
        const r = await fetch(API + '?action=sessoes&status=online');
        const data = await r.json();
        const grid = document.getElementById('clientGrid');

        if (!data.sessoes || data.sessoes.length === 0) {
            grid.innerHTML = `
                <div class="empty-state" style="grid-column:1/-1;padding:60px;text-align:center;">
                    <i class="fas fa-laptop" style="font-size:48px;color:var(--gray-300);margin-bottom:16px;display:block;"></i>
                    <p style="color:var(--gray-500);">Nenhum cliente online no momento.</p>
                </div>`;
            return;
        }

        grid.innerHTML = data.sessoes.map(s => {
            const osIcon = (s.os_info || '').toLowerCase().includes('windows') ? 'windows' :
                           (s.os_info || '').toLowerCase().includes('linux') ? 'linux' : 'mac';
            const osIconClass = osIcon === 'windows' ? 'fab fa-windows' :
                                osIcon === 'linux' ? 'fab fa-linux' : 'fab fa-apple';
            return `
                <div class="client-card">
                    <div class="client-card-header">
                        <div class="client-card-icon ${osIcon}">
                            <i class="${osIconClass}"></i>
                        </div>
                        <div style="flex:1;">
                            <div class="client-card-name">${esc(s.hostname || 'Desconhecido')}</div>
                            <div class="client-card-user">${esc(s.username || '')} ${s.inventario_nome ? '• ' + esc(s.inventario_nome) : ''}</div>
                        </div>
                        <span class="client-status-badge ${s.status}">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                            ${s.status === 'conectado' ? 'Em uso' : 'Online'}
                        </span>
                    </div>
                    <div class="client-card-details">
                        <span class="client-card-detail"><i class="fas fa-code"></i> ${esc(s.codigo)}</span>
                        <span class="client-card-detail"><i class="fas fa-network-wired"></i> ${esc(s.ip_address || s.ip_local || '')}</span>
                        <span class="client-card-detail"><i class="fas fa-desktop"></i> ${esc(s.resolucao || '')}</span>
                        <span class="client-card-detail"><i class="fas fa-info-circle"></i> ${esc(s.os_info || '')}</span>
                    </div>
                    <div class="client-card-actions">
                        ${s.status !== 'conectado' ? `
                            <button class="btn btn-primary btn-sm" onclick="startViewer('${esc(s.codigo)}')">
                                <i class="fas fa-desktop"></i> Conectar
                            </button>
                        ` : `
                            <button class="btn btn-outline btn-sm" disabled>
                                <i class="fas fa-lock"></i> Em uso por ${esc(s.tecnico_nome || 'outro')}
                            </button>
                        `}
                    </div>
                </div>`;
        }).join('');

        document.getElementById('badgeOnline').textContent = data.sessoes.length;
    } catch (e) {
        console.error('Erro ao carregar clientes:', e);
    }
}

// ==========================================
//  Histórico
// ==========================================
async function loadHistory() {
    try {
        const r = await fetch(API + '?action=historico&limit=50');
        const data = await r.json();
        const tbody = document.getElementById('historyBody');

        if (!data.historico || data.historico.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400);">Nenhum registro encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = data.historico.map(h => `
            <tr>
                <td><strong>${esc(h.tecnico_nome || h.tecnico_nome_rel || '---')}</strong></td>
                <td>
                    <div>${esc(h.hostname || '---')}</div>
                    <small style="color:var(--gray-400);">${esc(h.username || '')}</small>
                </td>
                <td><code style="font-size:12px;">${esc(h.ip_address || '---')}</code></td>
                <td>${formatDateTime(h.inicio)}</td>
                <td>${h.duracao_segundos > 0 ? formatDuration(h.duracao_segundos) : '<span style="color:var(--gray-400);">---</span>'}</td>
                <td>${h.chamado_id ? `<a href="${BASE_URL}/index.php?page=chamados&acao=ver&id=${h.chamado_id}">#${h.chamado_id}</a>` : '<span style="color:var(--gray-400);">---</span>'}</td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('Erro ao carregar histórico:', e);
    }
}

// ==========================================
//  Configurações
// ==========================================
async function saveConfig() {
    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'save_config',
                server_host: document.getElementById('cfgHost').value,
                server_port: document.getElementById('cfgPort').value,
                max_fps: document.getElementById('cfgFps').value,
                default_quality: document.getElementById('cfgQuality').value,
                default_scale: document.getElementById('cfgScale').value,
                session_timeout: document.getElementById('cfgTimeout').value,
                require_approval: document.getElementById('cfgApproval').checked ? '1' : '0',
            })
        });
        const data = await r.json();
        showToast(data.message || 'Salvo', data.success ? 'success' : 'error');
    } catch (e) {
        showToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  Proxy WSS
// ==========================================
async function checkProxy() {
    const area = document.getElementById('proxyStatusArea');
    if (!area) return;
    area.innerHTML = `<div style="display:flex;align-items:center;gap:8px;padding:12px;background:var(--gray-50);border-radius:8px;">
        <i class="fas fa-spinner fa-spin" style="color:var(--gray-400);"></i>
        <span style="color:var(--gray-500);">Verificando status do proxy...</span>
    </div>`;
    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'check_proxy' })
        });
        const d = await r.json();

        const icon = (ok) => ok
            ? '<i class="fas fa-check-circle" style="color:#10B981;"></i>'
            : '<i class="fas fa-times-circle" style="color:#EF4444;"></i>';
        const badge = (ok, labelOk, labelNo) => `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:500;background:${ok ? '#D1FAE5' : '#FEE2E2'};color:${ok ? '#065F46' : '#991B1B'};">${icon(ok)} ${ok ? labelOk : labelNo}</span>`;

        let html = `<div style="padding:14px;background:var(--gray-50);border-radius:8px;">`;

        // Status geral
        if (d.configured) {
            html += `<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding:10px 14px;background:#D1FAE5;border-radius:8px;">
                <i class="fas fa-shield-alt" style="color:#059669;font-size:18px;"></i>
                <div><strong style="color:#065F46;">Proxy WSS Configurado</strong><br><span style="font-size:12px;color:#047857;">WebSocket está acessível via HTTPS</span></div>
            </div>`;
        } else {
            html += `<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding:10px 14px;background:#FEF3C7;border-radius:8px;">
                <i class="fas fa-exclamation-triangle" style="color:#D97706;font-size:18px;"></i>
                <div><strong style="color:#92400E;">Proxy WSS Não Configurado</strong><br><span style="font-size:12px;color:#B45309;">Clique em "Configurar" para habilitar automaticamente</span></div>
            </div>`;
        }

        // Detalhes
        html += `<div style="display:flex;flex-wrap:wrap;gap:8px;">`;
        html += badge(d.proxy_http, 'proxy_http', 'proxy_http');
        html += badge(d.proxy_wstunnel, 'proxy_wstunnel', 'proxy_wstunnel');
        html += badge(d.rewrite, 'rewrite', 'rewrite');
        html += badge(d.proxy_rules, 'Regras proxy', 'Sem regras');
        html += badge(d.ssl_active, 'SSL ativo', 'Sem SSL');
        html += `</div>`;
        html += `<div style="margin-top:8px;font-size:11px;color:var(--gray-400);">SO: ${esc(d.os || '?')}</div>`;
        html += `</div>`;

        area.innerHTML = html;
    } catch (e) {
        area.innerHTML = `<div style="padding:12px;background:#FEE2E2;border-radius:8px;color:#991B1B;font-size:13px;">
            <i class="fas fa-exclamation-triangle"></i> Erro ao verificar: ${esc(e.message)}
        </div>`;
    }
}

async function configureProxy() {
    const btn = document.getElementById('btnProxy');
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Configurando...';

    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'configure_proxy' })
        });
        const d = await r.json();

        if (d.success) {
            showToast(d.message || 'Proxy configurado com sucesso!', 'success');
        } else {
            showToast(d.message || d.error || 'Erro ao configurar', 'error');
        }

        // Mostrar log de passos
        const area = document.getElementById('proxyStatusArea');
        if (area && (d.steps?.length || d.errors?.length)) {
            let html = '<div style="padding:14px;background:var(--gray-50);border-radius:8px;">';

            if (d.steps?.length) {
                html += '<div style="margin-bottom:8px;font-weight:600;font-size:13px;color:var(--gray-600);">Passos executados:</div>';
                d.steps.forEach(s => {
                    html += `<div style="display:flex;align-items:flex-start;gap:6px;margin-bottom:4px;font-size:13px;">
                        <i class="fas fa-check" style="color:#10B981;margin-top:2px;"></i>
                        <span style="color:var(--gray-600);">${esc(s)}</span>
                    </div>`;
                });
            }

            if (d.errors?.length) {
                html += '<div style="margin-top:8px;margin-bottom:8px;font-weight:600;font-size:13px;color:#DC2626;">Erros:</div>';
                d.errors.forEach(e => {
                    html += `<div style="display:flex;align-items:flex-start;gap:6px;margin-bottom:4px;font-size:13px;">
                        <i class="fas fa-times" style="color:#EF4444;margin-top:2px;"></i>
                        <span style="color:#991B1B;">${esc(e)}</span>
                    </div>`;
                });
            }

            html += '</div>';
            area.innerHTML = html;
        }

        // Re-check após 2s
        setTimeout(checkProxy, 2000);
    } catch (e) {
        showToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

// Verificar proxy ao carregar a aba config
setTimeout(checkProxy, 1500);

// ==========================================
//  Refresh
// ==========================================
async function remoteRefresh() {
    try {
        const r = await fetch(API + '?action=status');
        const data = await r.json();
        serverRunning = data.server_running;
        updateServerUI();
        document.getElementById('statOnline').textContent = data.online || 0;
        document.getElementById('statConectados').textContent = data.conectados || 0;
        document.getElementById('statHoje').textContent = data.total_hoje || 0;
        if (data.tempo_medio > 0) {
            const mins = Math.floor(data.tempo_medio / 60);
            const secs = data.tempo_medio % 60;
            document.getElementById('statTempo').textContent = `${String(mins).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;
        }
    } catch (e) {}
}

// Auto-refresh a cada 15s
setInterval(remoteRefresh, 15000);

// ==========================================
//  Utils
// ==========================================
function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDateTime(dt) {
    if (!dt) return '---';
    const d = new Date(dt);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'});
}

function formatDuration(seconds) {
    if (!seconds) return '---';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) return `${h}h ${m}m`;
    if (m > 0) return `${m}m ${s}s`;
    return `${s}s`;
}

function showToast(msg, type) {
    if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) {
        HelpDesk.toast(msg, type);
    } else {
        // Fallback
        const container = document.getElementById('toastContainer') || document.body;
        const toast = document.createElement('div');
        toast.className = `toast toast-${type || 'info'}`;
        toast.innerHTML = `<span>${msg}</span>`;
        toast.style.cssText = 'padding:12px 20px;margin:8px;background:#1E293B;color:white;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,0.15);animation:slideIn 0.3s;';
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }
}

// Carregar clientes ao abrir a aba
// Check URL params for direct connect
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('code')) {
    setTimeout(() => {
        document.getElementById('inputCode').value = urlParams.get('code');
        connectByCode();
    }, 500);
}
if (urlParams.get('chamado_id') && urlParams.get('code')) {
    setTimeout(() => startViewer(urlParams.get('code'), urlParams.get('chamado_id')), 600);
}
</script>
