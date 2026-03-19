<?php
/**
 * View: Deploy — Publicação em Produção
 * Envia arquivos, aplica configurações, histórico de deploys
 */
$user = currentUser();
?>

<style>
/* ===== Deploy Styles ===== */
.deploy-page { max-width: 1400px; }

.deploy-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.deploy-kpi { background: white; border-radius: var(--radius-lg); padding: 20px; border: 1px solid var(--gray-200); display: flex; align-items: center; gap: 16px; }
.deploy-kpi-icon { width: 48px; height: 48px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; }
.deploy-kpi-val { font-size: 24px; font-weight: 700; color: var(--gray-800); }
.deploy-kpi-label { font-size: 12px; color: var(--gray-500); margin-top: 2px; }

/* Tabs */
.deploy-tabs { display: flex; gap: 4px; margin-bottom: 20px; background: var(--gray-100); border-radius: var(--radius-lg); padding: 4px; flex-wrap: wrap; }
.deploy-tab { padding: 10px 20px; border-radius: var(--radius); cursor: pointer; font-weight: 500; font-size: 14px; border: none; background: transparent; color: var(--gray-500); transition: var(--transition); display: flex; align-items: center; gap: 8px; }
.deploy-tab:hover { color: var(--gray-700); }
.deploy-tab.active { background: white; color: var(--primary); box-shadow: var(--shadow-sm); }
.deploy-tab-content { display: none; }
.deploy-tab-content.active { display: block; }

/* Config form */
.deploy-card { background: white; border-radius: var(--radius-lg); padding: 24px; border: 1px solid var(--gray-200); margin-bottom: 20px; }
.deploy-card h3 { font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; color: var(--gray-800); }
.deploy-card h3 i { color: var(--primary); }

.deploy-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.deploy-form-grid .full-width { grid-column: 1 / -1; }
.deploy-form-group { display: flex; flex-direction: column; gap: 4px; }
.deploy-form-group label { font-size: 13px; font-weight: 500; color: var(--gray-600); }
.deploy-form-group input, .deploy-form-group select, .deploy-form-group textarea {
    padding: 8px 12px; border: 1px solid var(--gray-300); border-radius: var(--radius); font-size: 14px;
    transition: var(--transition); font-family: inherit;
}
.deploy-form-group input:focus, .deploy-form-group select:focus, .deploy-form-group textarea:focus {
    border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.deploy-form-group .hint { font-size: 11px; color: var(--gray-400); }

/* Connection test */
.deploy-test-result { padding: 12px 16px; border-radius: var(--radius); margin-top: 12px; font-size: 13px; display: none; }
.deploy-test-result.success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
.deploy-test-result.error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

/* Deploy button area */
.deploy-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
.deploy-btn { padding: 12px 24px; border-radius: var(--radius); cursor: pointer; font-weight: 600; font-size: 14px; border: none; display: flex; align-items: center; gap: 8px; transition: var(--transition); }
.deploy-btn-primary { background: var(--primary); color: white; }
.deploy-btn-primary:hover { background: var(--primary-dark); }
.deploy-btn-warning { background: #F59E0B; color: white; }
.deploy-btn-warning:hover { background: #D97706; }
.deploy-btn-success { background: #10B981; color: white; }
.deploy-btn-success:hover { background: #059669; }
.deploy-btn-danger { background: #EF4444; color: white; }
.deploy-btn-secondary { background: var(--gray-200); color: var(--gray-700); }
.deploy-btn-secondary:hover { background: var(--gray-300); }
.deploy-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* File preview */
.deploy-file-stats { display: flex; gap: 24px; margin-bottom: 16px; flex-wrap: wrap; }
.deploy-file-stat { font-size: 13px; color: var(--gray-600); }
.deploy-file-stat strong { color: var(--gray-800); }

.deploy-file-filter { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; align-items: center; }
.deploy-file-filter input { flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid var(--gray-300); border-radius: var(--radius); font-size: 13px; }
.deploy-file-filter .filter-chip { padding: 4px 12px; border-radius: 20px; font-size: 12px; border: 1px solid var(--gray-300); background: white; cursor: pointer; transition: var(--transition); }
.deploy-file-filter .filter-chip.active { background: var(--primary); color: white; border-color: var(--primary); }

.deploy-file-list { max-height: 500px; overflow-y: auto; border: 1px solid var(--gray-200); border-radius: var(--radius); }
.deploy-file-item { display: flex; align-items: center; padding: 6px 12px; border-bottom: 1px solid var(--gray-100); font-size: 13px; gap: 8px; }
.deploy-file-item:last-child { border-bottom: none; }
.deploy-file-item:hover { background: var(--gray-50); }
.deploy-file-item input[type=checkbox] { flex-shrink: 0; }
.deploy-file-path { flex: 1; font-family: 'Consolas', monospace; font-size: 12px; word-break: break-all; }
.deploy-file-size { color: var(--gray-400); font-size: 11px; min-width: 60px; text-align: right; }
.deploy-file-type { font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-transform: uppercase; }
.deploy-file-type.php { background: #EDE9FE; color: #6D28D9; }
.deploy-file-type.js { background: #FEF3C7; color: #92400E; }
.deploy-file-type.css { background: #DBEAFE; color: #1E40AF; }
.deploy-file-type.config { background: #FEE2E2; color: #991B1B; }
.deploy-file-type.image { background: #D1FAE5; color: #065F46; }
.deploy-file-type.sql { background: #FCE7F3; color: #9D174D; }

/* Log/progress */
.deploy-progress { background: white; border-radius: var(--radius-lg); padding: 24px; border: 1px solid var(--gray-200); margin-bottom: 20px; display: none; }
.deploy-progress.active { display: block; }
.deploy-progress-bar { height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden; margin-bottom: 16px; }
.deploy-progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--primary), #818CF8); border-radius: 4px; transition: width 0.3s; width: 0%; }
.deploy-progress-status { font-size: 14px; font-weight: 500; color: var(--gray-700); margin-bottom: 8px; }
.deploy-log { background: #1E293B; color: #CBD5E1; border-radius: var(--radius); padding: 16px; font-family: 'Consolas', monospace; font-size: 12px; max-height: 400px; overflow-y: auto; line-height: 1.6; white-space: pre-wrap; }
.deploy-log .log-ok { color: #34D399; }
.deploy-log .log-err { color: #F87171; }
.deploy-log .log-warn { color: #FBBF24; }
.deploy-log .log-info { color: #60A5FA; }

/* Overrides table */
.override-table { width: 100%; border-collapse: collapse; }
.override-table th { text-align: left; font-size: 12px; font-weight: 600; color: var(--gray-500); padding: 8px 12px; border-bottom: 2px solid var(--gray-200); }
.override-table td { padding: 8px 12px; border-bottom: 1px solid var(--gray-100); }
.override-table input { width: 100%; padding: 6px 10px; border: 1px solid var(--gray-300); border-radius: var(--radius); font-size: 13px; font-family: 'Consolas', monospace; }
.override-table .btn-remove { background: none; border: none; color: var(--gray-400); cursor: pointer; font-size: 16px; }
.override-table .btn-remove:hover { color: #EF4444; }

/* History */
.deploy-history-item { background: white; border-radius: var(--radius-lg); padding: 16px 20px; border: 1px solid var(--gray-200); margin-bottom: 12px; display: flex; align-items: center; gap: 16px; cursor: pointer; transition: var(--transition); }
.deploy-history-item:hover { border-color: var(--primary-light); box-shadow: var(--shadow-sm); }
.deploy-history-icon { width: 40px; height: 40px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.deploy-history-icon.concluido { background: #D1FAE5; color: #059669; }
.deploy-history-icon.erro { background: #FEE2E2; color: #DC2626; }
.deploy-history-icon.em_progresso { background: #DBEAFE; color: #2563EB; }
.deploy-history-icon.iniciado { background: #FEF3C7; color: #D97706; }
.deploy-history-info { flex: 1; }
.deploy-history-title { font-weight: 600; font-size: 14px; color: var(--gray-800); }
.deploy-history-meta { font-size: 12px; color: var(--gray-500); margin-top: 2px; }
.deploy-history-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.deploy-history-badge.concluido { background: #D1FAE5; color: #059669; }
.deploy-history-badge.erro { background: #FEE2E2; color: #DC2626; }
.deploy-history-badge.em_progresso { background: #DBEAFE; color: #2563EB; }

@media (max-width: 768px) {
    .deploy-form-grid { grid-template-columns: 1fr; }
    .deploy-kpis { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="deploy-page">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-rocket" style="color:#6366F1"></i> Deploy — Publicação em Produção</h1>
            <p>Configure e publique o sistema no servidor de produção com um clique</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <span id="deployVersion" class="upd-version-badge" style="background:#6366F1;color:white;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600;"></span>
        </div>
    </div>

    <!-- KPIs -->
    <div class="deploy-kpis">
        <div class="deploy-kpi">
            <div class="deploy-kpi-icon" style="background:#6366F1"><i class="fas fa-rocket"></i></div>
            <div>
                <div class="deploy-kpi-val" id="kpiTotal">-</div>
                <div class="deploy-kpi-label">Total de Deploys</div>
            </div>
        </div>
        <div class="deploy-kpi">
            <div class="deploy-kpi-icon" style="background:#10B981"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="deploy-kpi-val" id="kpiSucesso">-</div>
                <div class="deploy-kpi-label">Com Sucesso</div>
            </div>
        </div>
        <div class="deploy-kpi">
            <div class="deploy-kpi-icon" style="background:#EF4444"><i class="fas fa-times-circle"></i></div>
            <div>
                <div class="deploy-kpi-val" id="kpiErro">-</div>
                <div class="deploy-kpi-label">Com Erro</div>
            </div>
        </div>
        <div class="deploy-kpi">
            <div class="deploy-kpi-icon" style="background:#F59E0B"><i class="fas fa-clock"></i></div>
            <div>
                <div class="deploy-kpi-val" id="kpiUltimo">-</div>
                <div class="deploy-kpi-label">Último Deploy</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="deploy-tabs">
        <button class="deploy-tab active" onclick="deployTab('publicar')"><i class="fas fa-rocket"></i> Publicar</button>
        <button class="deploy-tab" onclick="deployTab('config')"><i class="fas fa-cog"></i> Conexão</button>
        <button class="deploy-tab" onclick="deployTab('overrides')"><i class="fas fa-sliders-h"></i> Config Produção</button>
        <button class="deploy-tab" onclick="deployTab('historico')"><i class="fas fa-history"></i> Histórico</button>
    </div>

    <!-- ==================== TAB: Publicar ==================== -->
    <div class="deploy-tab-content active" id="tab-publicar">

        <!-- Ações -->
        <div class="deploy-actions">
            <button class="deploy-btn deploy-btn-primary" onclick="deployFull()" id="btnDeployFull">
                <i class="fas fa-rocket"></i> Deploy Completo
            </button>
            <button class="deploy-btn deploy-btn-warning" onclick="deploySelected()" id="btnDeploySelected" disabled>
                <i class="fas fa-paper-plane"></i> Enviar Selecionados (<span id="selectedCount">0</span>)
            </button>
            <button class="deploy-btn deploy-btn-secondary" onclick="loadPreview()">
                <i class="fas fa-sync-alt"></i> Atualizar Lista
            </button>
        </div>

        <!-- Progress -->
        <div class="deploy-progress" id="deployProgress">
            <div class="deploy-progress-status" id="deployStatus">Preparando deploy...</div>
            <div class="deploy-progress-bar"><div class="deploy-progress-bar-fill" id="deployProgressBar"></div></div>
            <div class="deploy-log" id="deployLog"></div>
        </div>

        <!-- Preview -->
        <div class="deploy-card">
            <h3><i class="fas fa-file-alt"></i> Arquivos para Deploy</h3>

            <div class="deploy-file-stats" id="fileStats">
                <div class="deploy-file-stat">Total: <strong id="statTotal">-</strong> arquivos</div>
                <div class="deploy-file-stat">Tamanho: <strong id="statSize">-</strong></div>
            </div>

            <div class="deploy-file-filter">
                <input type="text" id="fileFilter" placeholder="Filtrar arquivos..." oninput="filterFiles()">
                <label style="font-size:13px;cursor:pointer;display:flex;align-items:center;gap:4px;">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"> Selecionar Todos
                </label>
                <span class="filter-chip active" data-type="all" onclick="filterByType('all')">Todos</span>
                <span class="filter-chip" data-type="php" onclick="filterByType('php')">PHP</span>
                <span class="filter-chip" data-type="js" onclick="filterByType('js')">JS</span>
                <span class="filter-chip" data-type="css" onclick="filterByType('css')">CSS</span>
                <span class="filter-chip" data-type="config" onclick="filterByType('config')">Config</span>
                <span class="filter-chip" data-type="sql" onclick="filterByType('sql')">SQL</span>
                <span class="filter-chip" data-type="image" onclick="filterByType('image')">Imagens</span>
            </div>

            <div class="deploy-file-list" id="fileList">
                <div style="padding:40px;text-align:center;color:var(--gray-400);">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px;margin-bottom:8px;display:block;"></i>
                    Carregando arquivos...
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: Conexão ==================== -->
    <div class="deploy-tab-content" id="tab-config">
        <div class="deploy-card">
            <h3><i class="fas fa-server"></i> Servidor de Produção</h3>
            <div class="deploy-form-grid">
                <div class="deploy-form-group">
                    <label>Método de Deploy</label>
                    <select id="cfgMethod" onchange="toggleMethodFields()">
                        <option value="ftp">FTP</option>
                        <option value="sftp">SFTP (SSH)</option>
                        <option value="local_copy">Cópia Local / Rede</option>
                    </select>
                </div>
                <div class="deploy-form-group" id="grpHost">
                    <label>Host / IP</label>
                    <input type="text" id="cfgHost" placeholder="172.16.0.100 ou ti.texascenter.com.br">
                </div>
                <div class="deploy-form-group" id="grpPort">
                    <label>Porta</label>
                    <input type="number" id="cfgPort" placeholder="21">
                </div>
                <div class="deploy-form-group" id="grpUser">
                    <label>Usuário</label>
                    <input type="text" id="cfgUser" placeholder="ftpuser">
                </div>
                <div class="deploy-form-group" id="grpPass">
                    <label>Senha</label>
                    <input type="password" id="cfgPass" placeholder="••••••••">
                </div>
                <div class="deploy-form-group">
                    <label>Caminho Remoto</label>
                    <input type="text" id="cfgPath" placeholder="/helpdesk ou C:\xampp\htdocs\helpdesk">
                    <span class="hint">Caminho absoluto no servidor de produção</span>
                </div>
                <div class="deploy-form-group full-width">
                    <label>URL da Produção</label>
                    <input type="text" id="cfgUrl" placeholder="https://ti.texascenter.com.br/helpdesk">
                    <span class="hint">URL para acesso ao sistema em produção</span>
                </div>
                <div class="deploy-form-group full-width">
                    <label>Padrões de Exclusão</label>
                    <textarea id="cfgExcludes" rows="3" style="font-family:Consolas,monospace;font-size:12px;" placeholder=".git,*.log,node_modules,..."></textarea>
                    <span class="hint">Arquivos/pastas ignorados no deploy (separados por vírgula)</span>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button class="deploy-btn deploy-btn-primary" onclick="saveConfig()">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
                <button class="deploy-btn deploy-btn-success" onclick="testConnection()" id="btnTestConn">
                    <i class="fas fa-plug"></i> Testar Conexão
                </button>
            </div>
            <div class="deploy-test-result" id="testResult"></div>
        </div>
    </div>

    <!-- ==================== TAB: Config Produção ==================== -->
    <div class="deploy-tab-content" id="tab-overrides">
        <div class="deploy-card">
            <h3><i class="fas fa-sliders-h"></i> Configurações de Produção</h3>
            <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px;">
                Defina os valores que serão diferentes na produção. Após o deploy, os arquivos <code>config/database.php</code> e <code>config/app.php</code> serão sobrescritos automaticamente com estes valores.
            </p>

            <table class="override-table" id="overrideTable">
                <thead>
                    <tr>
                        <th style="width:200px;">Variável</th>
                        <th>Valor na Produção</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="overrideBody">
                    <!-- Populated by JS -->
                </tbody>
            </table>

            <div style="display:flex;gap:12px;margin-top:16px;">
                <button class="deploy-btn deploy-btn-secondary" onclick="addOverride()">
                    <i class="fas fa-plus"></i> Adicionar Variável
                </button>
                <button class="deploy-btn deploy-btn-primary" onclick="saveOverrides()">
                    <i class="fas fa-save"></i> Salvar Overrides
                </button>
            </div>

            <div style="margin-top:20px;padding:16px;background:var(--gray-50);border-radius:var(--radius);border:1px solid var(--gray-200);">
                <strong style="font-size:13px;color:var(--gray-600);"><i class="fas fa-lightbulb" style="color:#F59E0B"></i> Variáveis comuns:</strong>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
                    <span class="filter-chip" style="cursor:pointer;font-size:11px;" onclick="addOverride('DB_HOST','')">DB_HOST</span>
                    <span class="filter-chip" style="cursor:pointer;font-size:11px;" onclick="addOverride('DB_NAME','')">DB_NAME</span>
                    <span class="filter-chip" style="cursor:pointer;font-size:11px;" onclick="addOverride('DB_USER','')">DB_USER</span>
                    <span class="filter-chip" style="cursor:pointer;font-size:11px;" onclick="addOverride('DB_PASS','')">DB_PASS</span>
                    <span class="filter-chip" style="cursor:pointer;font-size:11px;" onclick="addOverride('DB_PORT','3306')">DB_PORT</span>
                    <span class="filter-chip" style="cursor:pointer;font-size:11px;" onclick="addOverride('BASE_URL','/helpdesk')">BASE_URL</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: Histórico ==================== -->
    <div class="deploy-tab-content" id="tab-historico">
        <div id="historicoList">
            <div style="text-align:center;padding:40px;color:var(--gray-400);">
                <i class="fas fa-spinner fa-spin"></i> Carregando...
            </div>
        </div>
    </div>

    <!-- Modal: Deploy Log Detail -->
    <div id="deployLogModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:none;align-items:center;justify-content:center;">
        <div style="background:white;border-radius:var(--radius-lg);width:90%;max-width:800px;max-height:80vh;display:flex;flex-direction:column;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;">
                <strong id="modalTitle">Log do Deploy</strong>
                <button onclick="closeLogModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray-400);">&times;</button>
            </div>
            <div style="padding:20px;overflow-y:auto;flex:1;">
                <div class="deploy-log" id="modalLog" style="max-height:none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
const DEPLOY_API = <?= json_encode(BASE_URL) ?> + '/api/deploy.php';
let allFiles = [];
let currentFilter = 'all';
let overrides = {};

// ==========================================
//  Tabs
// ==========================================
function deployTab(tab) {
    document.querySelectorAll('.deploy-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.deploy-tab-content').forEach(c => c.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');

    if (tab === 'historico') loadHistorico();
    if (tab === 'overrides') loadOverrides();
}

// ==========================================
//  Overview (KPIs + Config)
// ==========================================
async function loadOverview() {
    try {
        const r = await fetch(DEPLOY_API + '?action=overview');
        const data = await r.json();

        document.getElementById('deployVersion').textContent = 'v' + data.version;

        const s = data.stats;
        document.getElementById('kpiTotal').textContent = s.total_deploys;
        document.getElementById('kpiSucesso').textContent = s.deploys_sucesso;
        document.getElementById('kpiErro').textContent = s.deploys_erro;

        if (s.ultimo_deploy) {
            const d = new Date(s.ultimo_deploy.iniciado_em);
            document.getElementById('kpiUltimo').textContent = d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
        } else {
            document.getElementById('kpiUltimo').textContent = 'Nunca';
        }

        // Preencher configs
        const cfg = data.config;
        if (cfg.deploy_method) document.getElementById('cfgMethod').value = cfg.deploy_method.valor;
        if (cfg.prod_host) document.getElementById('cfgHost').value = cfg.prod_host.valor;
        if (cfg.prod_port) document.getElementById('cfgPort').value = cfg.prod_port.valor;
        if (cfg.prod_user) document.getElementById('cfgUser').value = cfg.prod_user.valor;
        if (cfg.prod_pass && cfg.prod_pass.valor) document.getElementById('cfgPass').value = cfg.prod_pass.valor;
        if (cfg.prod_path) document.getElementById('cfgPath').value = cfg.prod_path.valor;
        if (cfg.prod_url) document.getElementById('cfgUrl').value = cfg.prod_url.valor;
        if (cfg.exclude_patterns) document.getElementById('cfgExcludes').value = cfg.exclude_patterns.valor;

        overrides = data.overrides || {};
        toggleMethodFields();

    } catch (e) {
        console.error('loadOverview:', e);
    }
}

function toggleMethodFields() {
    const m = document.getElementById('cfgMethod').value;
    const show = m !== 'local_copy';
    ['grpHost', 'grpPort', 'grpUser', 'grpPass'].forEach(id => {
        document.getElementById(id).style.display = show ? '' : 'none';
    });
    if (m === 'sftp' && document.getElementById('cfgPort').value === '21') {
        document.getElementById('cfgPort').value = '22';
    } else if (m === 'ftp' && document.getElementById('cfgPort').value === '22') {
        document.getElementById('cfgPort').value = '21';
    }
}

// ==========================================
//  Save Config
// ==========================================
async function saveConfig() {
    const config = {
        deploy_method: document.getElementById('cfgMethod').value,
        prod_host: document.getElementById('cfgHost').value,
        prod_port: document.getElementById('cfgPort').value,
        prod_user: document.getElementById('cfgUser').value,
        prod_path: document.getElementById('cfgPath').value,
        prod_url: document.getElementById('cfgUrl').value,
        exclude_patterns: document.getElementById('cfgExcludes').value,
    };
    const pass = document.getElementById('cfgPass').value;
    if (pass && pass !== '********') {
        config.prod_pass = pass;
    }

    try {
        const r = await fetch(DEPLOY_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'save_config', config})
        });
        const data = await r.json();
        if (data.success) {
            HelpDesk.toast('Configurações salvas!', 'success');
        } else {
            HelpDesk.toast(data.erro || 'Erro ao salvar', 'danger');
        }
    } catch (e) {
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }
}

// ==========================================
//  Test Connection
// ==========================================
async function testConnection() {
    const btn = document.getElementById('btnTestConn');
    const result = document.getElementById('testResult');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
    result.style.display = 'none';

    try {
        const r = await fetch(DEPLOY_API + '?action=test_connection');
        const data = await r.json();

        result.style.display = 'block';
        result.className = 'deploy-test-result ' + (data.success ? 'success' : 'error');
        result.innerHTML = `<strong>${data.method || ''}</strong> — ${data.message || data.error || 'Erro desconhecido'}`;
    } catch (e) {
        result.style.display = 'block';
        result.className = 'deploy-test-result error';
        result.textContent = '❌ Erro: ' + e.message;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plug"></i> Testar Conexão';
}

// ==========================================
//  File Preview
// ==========================================
async function loadPreview() {
    const list = document.getElementById('fileList');
    list.innerHTML = '<div style="padding:40px;text-align:center;color:var(--gray-400);"><i class="fas fa-spinner fa-spin" style="font-size:24px;margin-bottom:8px;display:block;"></i>Escaneando arquivos...</div>';

    try {
        const r = await fetch(DEPLOY_API + '?action=preview');
        const data = await r.json();
        allFiles = data.files || [];

        document.getElementById('statTotal').textContent = data.total_files;
        document.getElementById('statSize').textContent = formatBytes(data.total_size);

        renderFiles();
    } catch (e) {
        list.innerHTML = '<div style="padding:20px;color:#EF4444;">Erro ao carregar: ' + e.message + '</div>';
    }
}

function renderFiles() {
    const list = document.getElementById('fileList');
    const query = document.getElementById('fileFilter').value.toLowerCase();
    const typeFilter = currentFilter;

    let filtered = allFiles.filter(f => {
        if (typeFilter !== 'all' && f.type !== typeFilter) return false;
        if (query && !f.path.toLowerCase().includes(query)) return false;
        return true;
    });

    if (filtered.length === 0) {
        list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--gray-400);">Nenhum arquivo encontrado</div>';
        return;
    }

    let html = '';
    filtered.forEach(f => {
        const checked = f._selected ? 'checked' : '';
        html += `<div class="deploy-file-item">
            <input type="checkbox" ${checked} onchange="toggleFile('${f.path.replace(/'/g, "\\'")}', this.checked)">
            <span class="deploy-file-type ${f.type}">${f.type}</span>
            <span class="deploy-file-path">${f.path}</span>
            <span class="deploy-file-size">${formatBytes(f.size)}</span>
        </div>`;
    });

    list.innerHTML = html;
    updateSelectedCount();
}

function filterFiles() { renderFiles(); }

function filterByType(type) {
    currentFilter = type;
    document.querySelectorAll('.filter-chip[data-type]').forEach(c => c.classList.remove('active'));
    document.querySelector(`.filter-chip[data-type="${type}"]`)?.classList.add('active');
    renderFiles();
}

function toggleFile(path, checked) {
    const file = allFiles.find(f => f.path === path);
    if (file) file._selected = checked;
    updateSelectedCount();
}

function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    const query = document.getElementById('fileFilter').value.toLowerCase();
    allFiles.forEach(f => {
        if (currentFilter !== 'all' && f.type !== currentFilter) return;
        if (query && !f.path.toLowerCase().includes(query)) return;
        f._selected = checked;
    });
    renderFiles();
}

function updateSelectedCount() {
    const count = allFiles.filter(f => f._selected).length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('btnDeploySelected').disabled = count === 0;
}

// ==========================================
//  Deploy
// ==========================================
async function deployFull() {
    if (!confirm('🚀 Publicar TODO o sistema em produção?\n\nIsso enviará todos os arquivos e aplicará as configurações de produção.\n\nContinuar?')) return;
    await executeDeploy('full', []);
}

async function deploySelected() {
    const selected = allFiles.filter(f => f._selected).map(f => f.path);
    if (selected.length === 0) {
        HelpDesk.toast('Nenhum arquivo selecionado', 'warning');
        return;
    }
    if (!confirm(`📦 Enviar ${selected.length} arquivo(s) selecionados para produção?\n\nContinuar?`)) return;
    await executeDeploy('parcial', selected);
}

async function executeDeploy(tipo, files) {
    const progress = document.getElementById('deployProgress');
    const status = document.getElementById('deployStatus');
    const bar = document.getElementById('deployProgressBar');
    const logEl = document.getElementById('deployLog');

    progress.classList.add('active');
    status.textContent = '🚀 Iniciando deploy...';
    bar.style.width = '5%';
    logEl.textContent = '';

    document.getElementById('btnDeployFull').disabled = true;
    document.getElementById('btnDeploySelected').disabled = true;

    try {
        const r = await fetch(DEPLOY_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'deploy', tipo, files})
        });
        const data = await r.json();

        if (data.success) {
            bar.style.width = '100%';
            status.textContent = `✅ Deploy concluído! ${data.files_sent}/${data.files_total} enviados (${data.duration}s)`;
            bar.style.background = 'linear-gradient(90deg, #10B981, #34D399)';
            HelpDesk.toast(`Deploy concluído: ${data.files_sent} arquivos enviados`, 'success');
        } else {
            bar.style.width = '100%';
            bar.style.background = '#EF4444';
            status.textContent = '❌ Erro no deploy: ' + (data.error || 'Erro desconhecido');
            HelpDesk.toast('Erro no deploy', 'danger');
        }

        // Exibir log
        if (data.log && data.log.length) {
            logEl.innerHTML = data.log.map(line => {
                if (line.includes('✅')) return `<span class="log-ok">${escHtml(line)}</span>`;
                if (line.includes('❌')) return `<span class="log-err">${escHtml(line)}</span>`;
                if (line.includes('⚠️')) return `<span class="log-warn">${escHtml(line)}</span>`;
                return `<span class="log-info">${escHtml(line)}</span>`;
            }).join('\n');
        }

        loadOverview();

    } catch (e) {
        status.textContent = '❌ Erro: ' + e.message;
        bar.style.background = '#EF4444';
        bar.style.width = '100%';
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }

    document.getElementById('btnDeployFull').disabled = false;
    document.getElementById('btnDeploySelected').disabled = false;
}

// ==========================================
//  Overrides
// ==========================================
function loadOverrides() {
    const body = document.getElementById('overrideBody');
    body.innerHTML = '';

    // Sempre mostrar os campos DB + BASE_URL se vazio
    const defaults = {'DB_HOST': '', 'DB_NAME': '', 'DB_USER': '', 'DB_PASS': '', 'BASE_URL': ''};
    const merged = {...defaults, ...overrides};

    Object.entries(merged).forEach(([key, val]) => {
        addOverrideRow(key, val);
    });
}

function addOverride(key = '', val = '') {
    if (key) {
        // Não duplicar
        const existing = document.querySelector(`#overrideBody input[data-key="${key}"]`);
        if (existing) {
            existing.focus();
            return;
        }
    }
    addOverrideRow(key, val);
}

function addOverrideRow(key, val) {
    const body = document.getElementById('overrideBody');
    const tr = document.createElement('tr');
    const isPass = key.includes('PASS');
    tr.innerHTML = `
        <td><input type="text" value="${escHtml(key)}" placeholder="VARIAVEL" data-key="${escHtml(key)}" class="override-key" style="font-weight:600;"></td>
        <td><input type="${isPass ? 'password' : 'text'}" value="${escHtml(val)}" placeholder="valor na produção" class="override-val"></td>
        <td><button class="btn-remove" onclick="this.closest('tr').remove()" title="Remover">&times;</button></td>
    `;
    body.appendChild(tr);
}

async function saveOverrides() {
    const rows = document.querySelectorAll('#overrideBody tr');
    const data = {};
    rows.forEach(row => {
        const key = row.querySelector('.override-key').value.trim();
        const val = row.querySelector('.override-val').value;
        if (key) data[key] = val;
    });

    try {
        const r = await fetch(DEPLOY_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'save_overrides', overrides: data})
        });
        const result = await r.json();
        if (result.success) {
            overrides = data;
            HelpDesk.toast('Overrides de produção salvos!', 'success');
        } else {
            HelpDesk.toast(result.erro || 'Erro', 'danger');
        }
    } catch (e) {
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }
}

// ==========================================
//  Histórico
// ==========================================
async function loadHistorico() {
    const el = document.getElementById('historicoList');
    try {
        const r = await fetch(DEPLOY_API + '?action=historico&limit=30');
        const data = await r.json();
        const list = data.historico || [];

        if (list.length === 0) {
            el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--gray-400);"><i class="fas fa-inbox" style="font-size:48px;display:block;margin-bottom:12px;"></i>Nenhum deploy realizado ainda</div>';
            return;
        }

        el.innerHTML = list.map(h => {
            const d = new Date(h.iniciado_em);
            const dateStr = d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
            const icon = h.status === 'concluido' ? 'fa-check' : h.status === 'erro' ? 'fa-times' : 'fa-spinner';
            const dur = h.duracao_segundos ? h.duracao_segundos + 's' : '-';
            return `<div class="deploy-history-item" onclick="showDeployLog(${h.id})">
                <div class="deploy-history-icon ${h.status}"><i class="fas ${icon}"></i></div>
                <div class="deploy-history-info">
                    <div class="deploy-history-title">Deploy ${h.tipo} — v${h.versao_origem || '?'}</div>
                    <div class="deploy-history-meta">${dateStr} • ${h.usuario_nome || 'Admin'} • ${h.arquivos_enviados || 0}/${h.arquivos_total || 0} arquivos • ${dur}</div>
                    ${h.log_resumo ? `<div style="font-size:12px;color:var(--gray-500);margin-top:4px;">${escHtml(h.log_resumo)}</div>` : ''}
                </div>
                <span class="deploy-history-badge ${h.status}">${h.status}</span>
            </div>`;
        }).join('');

    } catch (e) {
        el.innerHTML = '<div style="color:#EF4444;padding:20px;">Erro: ' + e.message + '</div>';
    }
}

async function showDeployLog(id) {
    const modal = document.getElementById('deployLogModal');
    const log = document.getElementById('modalLog');
    const title = document.getElementById('modalTitle');

    modal.style.display = 'flex';
    log.textContent = 'Carregando...';

    try {
        const r = await fetch(DEPLOY_API + '?action=deploy_detail&id=' + id);
        const data = await r.json();

        title.textContent = `Deploy #${data.id} — ${data.tipo} (${data.status})`;

        const logText = data.log_detalhado || data.log_resumo || 'Sem log disponível';
        log.innerHTML = logText.split('\n').map(line => {
            if (line.includes('✅')) return `<span class="log-ok">${escHtml(line)}</span>`;
            if (line.includes('❌')) return `<span class="log-err">${escHtml(line)}</span>`;
            if (line.includes('⚠️')) return `<span class="log-warn">${escHtml(line)}</span>`;
            return `<span class="log-info">${escHtml(line)}</span>`;
        }).join('\n');

    } catch (e) {
        log.textContent = 'Erro: ' + e.message;
    }
}

function closeLogModal() {
    document.getElementById('deployLogModal').style.display = 'none';
}

// ==========================================
//  Utils
// ==========================================
function formatBytes(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function escHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

// ==========================================
//  Init
// ==========================================
loadOverview();
loadPreview();
</script>
