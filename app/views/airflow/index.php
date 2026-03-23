<?php
/**
 * View: Airflow — Apache Airflow Management
 * Módulo exclusivo TI — Oracle X
 */
?>

<style>
.af-kpi-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.af-kpi-card {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 16px 12px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}
.af-kpi-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}
.af-kpi-value {
    font-size: 22px;
    font-weight: 700;
    line-height: 1.2;
}
.af-kpi-label {
    font-size: 12px;
    color: var(--gray-500);
    margin-top: 4px;
}
.af-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    background: var(--gray-50);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.af-section-header h3 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--gray-700);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.af-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}
.af-badge-success { background: #F0FDF4; color: #15803D; }
.af-badge-danger  { background: #FEF2F2; color: #991B1B; }
.af-badge-warning { background: #FFFBEB; color: #92400E; }
.af-badge-info    { background: #EFF6FF; color: #1E40AF; }
.af-badge-primary { background: #E0F2FE; color: #0369A1; }
.af-badge-secondary { background: #F1F5F9; color: #64748B; }
.af-badge-light   { background: #F8FAFC; color: #475569; border: 1px solid var(--gray-200); }
.af-btn-group {
    display: inline-flex;
    gap: 4px;
}
.af-btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 1px solid var(--gray-200);
    background: #fff;
    cursor: pointer;
    color: var(--gray-600);
    font-size: 13px;
    transition: var(--transition);
}
.af-btn-action:hover {
    background: var(--gray-100);
    color: var(--gray-800);
    border-color: var(--gray-300);
}
.af-btn-action.success { color: var(--success); }
.af-btn-action.success:hover { background: #F0FDF4; border-color: var(--success); }
.af-btn-action.warning { color: #D97706; }
.af-btn-action.warning:hover { background: #FFFBEB; border-color: #D97706; }
.af-btn-action.info { color: #017CEE; }
.af-btn-action.info:hover { background: #E0F2FE; border-color: #017CEE; }
.af-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    font-size: 12px;
    border-top: 1px solid var(--gray-100);
    color: var(--gray-500);
}
@media (max-width: 1200px) {
    .af-kpi-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 768px) {
    .af-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-wind" style="margin-right:8px;color:#017CEE"></i> Apache Airflow</h1>
        <p class="page-subtitle">Gerenciamento de DAGs, execuções e pipelines de dados</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-sm" onclick="afRefresh()" id="btnRefresh">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
        <button class="btn btn-sm" onclick="afOpenWebUI()" style="background:#017CEE;color:#fff;border-color:#017CEE">
            <i class="fas fa-external-link-alt"></i> Abrir Airflow UI
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="af-kpi-grid" id="afKpis">
    <div class="af-kpi-card">
        <div class="af-kpi-value" style="color:#017CEE" id="kpiDagsTotal">-</div>
        <div class="af-kpi-label">DAGs Total</div>
    </div>
    <div class="af-kpi-card">
        <div class="af-kpi-value" style="color:var(--success)" id="kpiDagsActive">-</div>
        <div class="af-kpi-label">DAGs Ativas</div>
    </div>
    <div class="af-kpi-card">
        <div class="af-kpi-value" style="color:#D97706" id="kpiDagsPaused">-</div>
        <div class="af-kpi-label">DAGs Pausadas</div>
    </div>
    <div class="af-kpi-card">
        <div class="af-kpi-value" style="color:#3B82F6" id="kpiRunsRunning">-</div>
        <div class="af-kpi-label">Executando</div>
    </div>
    <div class="af-kpi-card">
        <div class="af-kpi-value" style="color:var(--danger)" id="kpiFailed24h">-</div>
        <div class="af-kpi-label">Falhas 24h</div>
    </div>
    <div class="af-kpi-card">
        <div class="af-kpi-value" style="color:var(--danger)" id="kpiImportErrors">-</div>
        <div class="af-kpi-label">Import Errors</div>
    </div>
    <div class="af-kpi-card">
        <div class="af-kpi-value" id="kpiHealth" style="font-size:14px">-</div>
        <div class="af-kpi-label">Status</div>
    </div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="af-dags" onclick="afTab(this);afLoadDags()">
        <i class="fas fa-project-diagram"></i> DAGs
    </button>
    <button class="ad-tab" data-tab="af-runs" onclick="afTab(this);afLoadRecentRuns()">
        <i class="fas fa-play-circle"></i> Execuções
    </button>
    <button class="ad-tab" data-tab="af-errors" onclick="afTab(this);afLoadImportErrors()">
        <i class="fas fa-exclamation-triangle"></i> Import Errors
        <span class="af-badge af-badge-danger" id="badgeImportErrors" style="display:none;font-size:9px;margin-left:4px">0</span>
    </button>
    <button class="ad-tab" data-tab="af-connections" onclick="afTab(this);afLoadConnections()">
        <i class="fas fa-plug"></i> Conexões
    </button>
    <button class="ad-tab" data-tab="af-variables" onclick="afTab(this);afLoadVariables()">
        <i class="fas fa-key"></i> Variáveis
    </button>
    <button class="ad-tab" data-tab="af-pools" onclick="afTab(this);afLoadPools()">
        <i class="fas fa-water"></i> Pools
    </button>
    <button class="ad-tab" data-tab="af-log" onclick="afTab(this);afLoadActionLog()">
        <i class="fas fa-history"></i> Histórico
    </button>
    <button class="ad-tab" data-tab="af-config" onclick="afTab(this)">
        <i class="fas fa-cog"></i> Configuração
    </button>
</div>

<!-- ============================================ -->
<!--  TAB: DAGs                                    -->
<!-- ============================================ -->
<div class="ad-tab-content active" id="af-dags">
    <div class="card">
        <div class="af-section-header">
            <h3><i class="fas fa-project-diagram"></i> DAGs</h3>
            <div style="display:flex;gap:8px;align-items:center">
                <input type="text" class="form-control" placeholder="Buscar DAG..." 
                       id="afDagSearch" onkeyup="afDagSearchDebounce()" style="width:220px;padding:6px 10px;font-size:13px">
                <select class="form-select" id="afDagFilter" onchange="afLoadDags()" style="width:auto;padding:6px 10px;font-size:13px">
                    <option value="">Todas</option>
                    <option value="active">Ativas</option>
                    <option value="paused">Pausadas</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:30px"></th>
                        <th>DAG ID</th>
                        <th>Tags</th>
                        <th style="width:100px">Schedule</th>
                        <th style="width:80px">Status</th>
                        <th style="width:140px">Última Execução</th>
                        <th style="width:80px">Último Estado</th>
                        <th style="width:130px">Ações</th>
                    </tr>
                </thead>
                <tbody id="afDagsTable">
                    <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--gray-400)">
                        <i class="fas fa-spinner fa-spin"></i> Carregando DAGs...
                    </td></tr>
                </tbody>
            </table>
        </div>
        <div class="af-pagination">
            <span id="afDagsPagInfo">-</span>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm btn-ghost" id="afDagsPrev" onclick="afDagsPaginate(-1)" disabled>← Anterior</button>
                <button class="btn btn-sm btn-ghost" id="afDagsNext" onclick="afDagsPaginate(1)" disabled>Próximo →</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: EXECUÇÕES (RUNS)                        -->
<!-- ============================================ -->
<div class="ad-tab-content" id="af-runs">
    <div class="card">
        <div class="af-section-header">
            <h3><i class="fas fa-play-circle"></i> Execuções Recentes</h3>
            <div style="display:flex;gap:8px">
                <select class="form-select" id="afRunDagSelect" onchange="afLoadDagRuns()" style="width:250px;padding:6px 10px;font-size:13px">
                    <option value="">Selecione uma DAG...</option>
                </select>
                <select class="form-select" id="afRunStateFilter" onchange="afLoadDagRuns()" style="width:auto;padding:6px 10px;font-size:13px">
                    <option value="">Todos estados</option>
                    <option value="success">✅ Success</option>
                    <option value="failed">❌ Failed</option>
                    <option value="running">🔵 Running</option>
                    <option value="queued">⏳ Queued</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th>DAG ID</th>
                        <th>Run ID</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Duração</th>
                        <th style="width:100px">Ações</th>
                    </tr>
                </thead>
                <tbody id="afRunsTable">
                    <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--gray-400)">Selecione uma DAG para ver as execuções</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: IMPORT ERRORS                           -->
<!-- ============================================ -->
<div class="ad-tab-content" id="af-errors">
    <div class="card">
        <div class="af-section-header">
            <h3><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> Import Errors</h3>
        </div>
        <div class="table-responsive">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Erro</th>
                        <th style="width:160px">Data</th>
                    </tr>
                </thead>
                <tbody id="afErrorsTable">
                    <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray-400)">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: CONEXÕES                                -->
<!-- ============================================ -->
<div class="ad-tab-content" id="af-connections">
    <div class="card">
        <div class="af-section-header">
            <h3><i class="fas fa-plug"></i> Conexões do Airflow</h3>
        </div>
        <div class="table-responsive">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th>Connection ID</th>
                        <th>Tipo</th>
                        <th>Host</th>
                        <th>Porta</th>
                        <th>Schema</th>
                        <th>Login</th>
                    </tr>
                </thead>
                <tbody id="afConnsTable">
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: VARIÁVEIS                               -->
<!-- ============================================ -->
<div class="ad-tab-content" id="af-variables">
    <div class="card">
        <div class="af-section-header">
            <h3><i class="fas fa-key"></i> Variáveis do Airflow</h3>
        </div>
        <div class="table-responsive">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th>Chave</th>
                        <th>Valor</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody id="afVarsTable">
                    <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray-400)">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: POOLS                                   -->
<!-- ============================================ -->
<div class="ad-tab-content" id="af-pools">
    <div class="card">
        <div class="af-section-header">
            <h3><i class="fas fa-water"></i> Pools</h3>
        </div>
        <div class="table-responsive">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th>Pool</th>
                        <th>Slots</th>
                        <th>Usados</th>
                        <th>Em Fila</th>
                        <th>Livres</th>
                        <th>Ocupação</th>
                    </tr>
                </thead>
                <tbody id="afPoolsTable">
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: HISTÓRICO DE AÇÕES                      -->
<!-- ============================================ -->
<div class="ad-tab-content" id="af-log">
    <div class="card">
        <div class="af-section-header">
            <h3><i class="fas fa-history"></i> Histórico de Ações (Oracle X)</h3>
        </div>
        <div class="table-responsive">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:160px">Data</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>DAG</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody id="afActionLogTable">
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-400)">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: CONFIGURAÇÃO                            -->
<!-- ============================================ -->
<div class="ad-tab-content" id="af-config">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="card">
            <div class="af-section-header">
                <h3><i class="fas fa-cog"></i> Conexão com o Airflow</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">URL do Airflow Webserver</label>
                    <input type="text" class="form-control" id="afCfgUrl" placeholder="http://172.16.0.180:8080">
                    <small style="color:var(--gray-400);font-size:12px">URL completa incluindo porta (ex: http://airflow.local:8080)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="afCfgUser" placeholder="airflow">
                </div>
                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <input type="password" class="form-control" id="afCfgPass" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label class="form-label">Timeout (segundos)</label>
                    <input type="number" class="form-control" id="afCfgTimeout" value="30" min="5" max="120">
                </div>
                <div style="display:flex;gap:8px;margin-top:16px">
                    <button class="btn btn-primary" onclick="afSaveConfig()">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                    <button class="btn btn-outline" onclick="afTestConnection()" style="color:var(--success);border-color:var(--success)">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                </div>
                <div id="afConfigTestResult" style="margin-top:12px;display:none"></div>
            </div>
        </div>
        <div class="card">
            <div class="af-section-header">
                <h3><i class="fas fa-info-circle"></i> Sobre este módulo</h3>
            </div>
            <div class="card-body">
                <p>Este módulo conecta ao <strong>Apache Airflow</strong> via REST API (v1) e permite:</p>
                <ul style="font-size:14px;line-height:2">
                    <li>📊 Monitorar status de DAGs e execuções em tempo real</li>
                    <li>▶️ Disparar DAGs manualmente com parâmetros customizados</li>
                    <li>⏸️ Pausar/reativar DAGs</li>
                    <li>🔍 Visualizar logs de tasks</li>
                    <li>🔗 Listar conexões, variáveis e pools</li>
                    <li>⚠️ Monitorar erros de importação</li>
                    <li>📝 Histórico de todas as ações realizadas pelo Oracle X</li>
                </ul>
                <hr style="border-color:var(--gray-100)">
                <p style="margin:4px 0"><small style="color:var(--gray-400)">API utilizada: <code>Airflow REST API v1</code></small></p>
                <p style="margin:0"><small style="color:var(--gray-400)">Documentação: <a href="https://airflow.apache.org/docs/apache-airflow/stable/stable-rest-api-ref.html" target="_blank">airflow.apache.org/docs</a></small></p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  MODAL: Detalhes da DAG                       -->
<!-- ============================================ -->
<div class="modal-overlay" id="modalDagDetail" style="display:none">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h2 id="modalDagTitle"><i class="fas fa-project-diagram" style="color:#017CEE"></i> DAG</h2>
            <button class="modal-close" onclick="afCloseModal('modalDagDetail')">&times;</button>
        </div>
        <div class="modal-body" id="modalDagBody">
            <div style="text-align:center;padding:40px"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--gray-300)"></i></div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  MODAL: Task Log                              -->
<!-- ============================================ -->
<div class="modal-overlay" id="modalTaskLog" style="display:none">
    <div class="modal modal-xl" style="max-height:85vh">
        <div class="modal-header">
            <h2 id="modalTaskLogTitle"><i class="fas fa-file-alt"></i> Log da Task</h2>
            <button class="modal-close" onclick="afCloseModal('modalTaskLog')">&times;</button>
        </div>
        <div class="modal-body" style="padding:0;flex:1;overflow:hidden">
            <pre id="modalTaskLogContent" style="max-height:600px;overflow:auto;padding:16px;margin:0;background:#0d1117;color:#c9d1d9;font-size:12px;line-height:1.6;white-space:pre-wrap;word-break:break-all"></pre>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  MODAL: Trigger DAG                           -->
<!-- ============================================ -->
<div class="modal-overlay" id="modalTriggerDag" style="display:none">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h2><i class="fas fa-play" style="color:var(--success)"></i> Disparar DAG</h2>
            <button class="modal-close" onclick="afCloseModal('modalTriggerDag')">&times;</button>
        </div>
        <div class="modal-body">
            <p>DAG: <strong id="triggerDagId"></strong></p>
            <div class="form-group">
                <label class="form-label">Configuração (JSON)</label>
                <textarea class="form-control" id="triggerDagConf" rows="5" placeholder='{"key": "value"}'>{}</textarea>
                <small style="color:var(--gray-400);font-size:12px">Parâmetros de configuração para esta execução (conf)</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="afCloseModal('modalTriggerDag')">Cancelar</button>
            <button class="btn btn-success" onclick="afTriggerDagConfirm()">
                <i class="fas fa-play"></i> Disparar
            </button>
        </div>
    </div>
</div>

<script>
const AF_API = '<?= BASE_URL ?>/api/airflow.php';
let afDagsPage = 0;
const afDagsPerPage = 25;
let afDagsTotal = 0;
let afFavoritos = [];
let afDagSearchTimer = null;
let afAirflowUrl = '';

// ==========================================
//  MODAL HELPERS
// ==========================================
function afOpenModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function afCloseModal(id) {
    document.getElementById(id).style.display = 'none';
}

// ==========================================
//  INIT
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    afLoadOverview();
    afLoadDags();
    afLoadConfig();
    afLoadFavoritos();

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    });
});

// ==========================================
//  TABS
// ==========================================
function afTab(btn) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    const target = document.getElementById(btn.dataset.tab);
    if (target) target.classList.add('active');
}

// ==========================================
//  OVERVIEW / KPIs
// ==========================================
async function afLoadOverview() {
    try {
        const resp = await HelpDesk.api('GET', AF_API + '?action=overview');
        if (!resp.success) return;
        const d = resp.data;

        document.getElementById('kpiDagsTotal').textContent = d.dags_total;
        document.getElementById('kpiDagsActive').textContent = d.dags_active;
        document.getElementById('kpiDagsPaused').textContent = d.dags_paused;
        document.getElementById('kpiRunsRunning').textContent = d.runs_running;
        document.getElementById('kpiFailed24h').textContent = d.runs_failed_24h;
        document.getElementById('kpiImportErrors').textContent = d.import_errors;

        const healthEl = document.getElementById('kpiHealth');
        if (d.health.online) {
            healthEl.innerHTML = '<span style="color:var(--success)">● Online</span>';
            afAirflowUrl = d.health.url || '';
        } else {
            healthEl.innerHTML = '<span style="color:var(--danger)">● Offline</span>';
        }

        // Badge import errors
        const badge = document.getElementById('badgeImportErrors');
        if (d.import_errors > 0) {
            badge.textContent = d.import_errors;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }

        // Highlight failed
        const failedEl = document.getElementById('kpiFailed24h');
        failedEl.style.color = d.runs_failed_24h > 0 ? 'var(--danger)' : 'var(--success)';
    } catch (e) {
        document.getElementById('kpiHealth').innerHTML = '<span style="color:var(--danger)">● Erro</span>';
    }
}

function afRefresh() {
    const btn = document.getElementById('btnRefresh');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
    afLoadOverview().then(() => {
        const activeTab = document.querySelector('.ad-tab.active');
        if (activeTab) activeTab.click();
    }).finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Atualizar';
    });
}

function afOpenWebUI() {
    if (afAirflowUrl) {
        window.open(afAirflowUrl, '_blank');
    } else {
        HelpDesk.toast('⚠️ URL do Airflow não configurada. Vá na aba Configuração.', 'warning');
    }
}

// ==========================================
//  DAGs
// ==========================================
async function afLoadDags() {
    const tbody = document.getElementById('afDagsTable');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

    const search = document.getElementById('afDagSearch').value.trim();
    const filter = document.getElementById('afDagFilter').value;
    const activeParam = filter === 'active' ? 'true' : (filter === 'paused' ? 'false' : '');

    let url = `${AF_API}?action=dags&limit=${afDagsPerPage}&offset=${afDagsPage * afDagsPerPage}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (activeParam) url += `&active=${activeParam}`;

    try {
        const resp = await HelpDesk.api('GET', url);
        if (!resp.success) throw new Error(resp.error || 'Erro');

        const dags = resp.data.dags || [];
        afDagsTotal = resp.data.total || 0;

        if (dags.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--gray-400)">Nenhuma DAG encontrada</td></tr>';
            afUpdateDagsPag();
            return;
        }

        // Populate dag select for Runs tab
        const dagSelect = document.getElementById('afRunDagSelect');
        const currentVal = dagSelect.value;
        dagSelect.innerHTML = '<option value="">Selecione uma DAG...</option>';
        dags.forEach(d => {
            dagSelect.innerHTML += `<option value="${esc(d.dag_id)}">${esc(d.dag_id)}</option>`;
        });
        if (currentVal) dagSelect.value = currentVal;

        tbody.innerHTML = dags.map(dag => {
            const isFav = afFavoritos.includes(dag.dag_id);
            const favIcon = isFav ? 'fas fa-star' : 'far fa-star';
            const favColor = isFav ? 'color:#F59E0B' : 'color:var(--gray-300)';
            const isPaused = dag.is_paused;
            const statusBadge = isPaused 
                ? '<span class="af-badge af-badge-warning">Pausada</span>'
                : '<span class="af-badge af-badge-success">Ativa</span>';
            const tags = (dag.tags || []).map(t => `<span class="af-badge af-badge-light" style="font-size:10px">${esc(t.name)}</span>`).join(' ');
            const schedule = dag.timetable_description || dag.schedule_interval || '-';

            let lastRun = '-';
            let lastState = '-';
            if (dag.last_parsed_time) {
                lastRun = formatDate(dag.last_parsed_time);
            }

            return `<tr>
                <td style="text-align:center">
                    <i class="${favIcon}" style="cursor:pointer;${favColor}" onclick="afToggleFav('${esc(dag.dag_id)}', this)" title="Favorito"></i>
                </td>
                <td>
                    <a href="#" onclick="afShowDag('${esc(dag.dag_id)}');return false" style="color:#017CEE;font-weight:600">${esc(dag.dag_id)}</a>
                    ${dag.description ? `<br><small style="color:var(--gray-400)">${esc(dag.description).substring(0, 80)}</small>` : ''}
                </td>
                <td>${tags || '<span style="color:var(--gray-300)">-</span>'}</td>
                <td><code style="font-size:11px">${esc(schedule)}</code></td>
                <td>${statusBadge}</td>
                <td style="font-size:12px">${lastRun}</td>
                <td>${lastState}</td>
                <td>
                    <div class="af-btn-group">
                        <button class="af-btn-action success" onclick="afShowTrigger('${esc(dag.dag_id)}')" title="Disparar">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="af-btn-action warning" onclick="afToggleDag('${esc(dag.dag_id)}', ${!isPaused})" title="${isPaused ? 'Ativar' : 'Pausar'}">
                            <i class="fas fa-${isPaused ? 'play-circle' : 'pause-circle'}"></i>
                        </button>
                        <button class="af-btn-action info" onclick="afShowDag('${esc(dag.dag_id)}')" title="Detalhes">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        afUpdateDagsPag();
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--danger)"><i class="fas fa-exclamation-triangle"></i> ${esc(e.message)}</td></tr>`;
    }
}

function afDagSearchDebounce() {
    clearTimeout(afDagSearchTimer);
    afDagSearchTimer = setTimeout(() => { afDagsPage = 0; afLoadDags(); }, 400);
}

function afUpdateDagsPag() {
    const start = afDagsPage * afDagsPerPage + 1;
    const end = Math.min(start + afDagsPerPage - 1, afDagsTotal);
    document.getElementById('afDagsPagInfo').textContent = afDagsTotal > 0 ? `${start}-${end} de ${afDagsTotal}` : 'Sem resultados';
    document.getElementById('afDagsPrev').disabled = afDagsPage === 0;
    document.getElementById('afDagsNext').disabled = end >= afDagsTotal;
}

function afDagsPaginate(dir) {
    afDagsPage += dir;
    if (afDagsPage < 0) afDagsPage = 0;
    afLoadDags();
}

// ==========================================
//  DAG DETAIL MODAL
// ==========================================
async function afShowDag(dagId) {
    document.getElementById('modalDagTitle').innerHTML = `<i class="fas fa-project-diagram" style="color:#017CEE"></i> ${esc(dagId)}`;
    document.getElementById('modalDagBody').innerHTML = '<div style="text-align:center;padding:40px"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--gray-300)"></i></div>';
    afOpenModal('modalDagDetail');

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=dag&dag_id=${encodeURIComponent(dagId)}`);
        if (!resp.success) throw new Error(resp.error);
        const d = resp.data;
        const dag = d.dag;
        const tasks = d.tasks || [];
        const runs = d.runs || [];

        let html = '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">';
        html += `<div><strong>Schedule:</strong> ${esc(dag.timetable_description || dag.schedule_interval || 'Nenhum')}</div>`;
        html += `<div><strong>Status:</strong> ${dag.is_paused ? '<span class="af-badge af-badge-warning">Pausada</span>' : '<span class="af-badge af-badge-success">Ativa</span>'}</div>`;
        html += `<div><strong>Owner:</strong> ${esc(dag.owners?.join(', ') || '-')}</div>`;
        html += '</div>';

        if (dag.description) {
            html += `<p style="color:var(--gray-500)">${esc(dag.description)}</p>`;
        }

        // Tasks
        html += '<h3 style="font-size:14px;font-weight:600;margin-top:16px"><i class="fas fa-tasks"></i> Tasks (' + tasks.length + ')</h3>';
        if (tasks.length > 0) {
            html += '<div class="table-responsive"><table class="table"><thead><tr><th>Task ID</th><th>Operator</th><th>Retries</th><th>Pool</th></tr></thead><tbody>';
            tasks.forEach(t => {
                html += `<tr><td><code>${esc(t.task_id)}</code></td><td>${esc(t.operator_name || t.class_ref?.class_name || '-')}</td><td>${t.retries ?? 0}</td><td>${esc(t.pool || 'default')}</td></tr>`;
            });
            html += '</tbody></table></div>';
        }

        // Recent runs
        html += '<h3 style="font-size:14px;font-weight:600;margin-top:16px"><i class="fas fa-play-circle"></i> Últimas Execuções (' + runs.length + ')</h3>';
        if (runs.length > 0) {
            html += '<div class="table-responsive"><table class="table"><thead><tr><th>Run ID</th><th>Estado</th><th>Início</th><th>Duração</th><th>Ações</th></tr></thead><tbody>';
            runs.forEach(r => {
                const stateBadge = afStateBadge(r.state);
                const duration = r.start_date && r.end_date ? afDuration(r.start_date, r.end_date) : '-';
                html += `<tr>
                    <td style="font-size:11px"><code>${esc(r.dag_run_id)}</code></td>
                    <td>${stateBadge}</td>
                    <td style="font-size:12px">${formatDate(r.start_date)}</td>
                    <td>${duration}</td>
                    <td>
                        <div class="af-btn-group">
                            <button class="af-btn-action info" onclick="afShowRunTasks('${esc(dagId)}','${esc(r.dag_run_id)}')" title="Ver Tasks">
                                <i class="fas fa-tasks"></i>
                            </button>
                            <button class="af-btn-action warning" onclick="afClearRun('${esc(dagId)}','${esc(r.dag_run_id)}')" title="Clear & Retry">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        } else {
            html += '<p style="color:var(--gray-400)">Nenhuma execução registrada</p>';
        }

        document.getElementById('modalDagBody').innerHTML = html;
    } catch (e) {
        document.getElementById('modalDagBody').innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${esc(e.message)}</div>`;
    }
}

// ==========================================
//  DAG RUNS TAB
// ==========================================
async function afLoadRecentRuns() {
    if (document.getElementById('afRunDagSelect').options.length <= 1) {
        try {
            const resp = await HelpDesk.api('GET', `${AF_API}?action=dags&limit=200`);
            if (resp.success) {
                const sel = document.getElementById('afRunDagSelect');
                (resp.data.dags || []).forEach(d => {
                    if (!sel.querySelector(`option[value="${d.dag_id}"]`)) {
                        sel.innerHTML += `<option value="${esc(d.dag_id)}">${esc(d.dag_id)}</option>`;
                    }
                });
            }
        } catch(e) {}
    }
}

async function afLoadDagRuns() {
    const dagId = document.getElementById('afRunDagSelect').value;
    const tbody = document.getElementById('afRunsTable');
    if (!dagId) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--gray-400)">Selecione uma DAG para ver as execuções</td></tr>';
        return;
    }

    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';
    const state = document.getElementById('afRunStateFilter').value;

    try {
        let url = `${AF_API}?action=dag_runs&dag_id=${encodeURIComponent(dagId)}&limit=30`;
        if (state) url += `&state=${state}`;
        const resp = await HelpDesk.api('GET', url);
        if (!resp.success) throw new Error(resp.error);

        const runs = resp.data.dag_runs || [];
        if (runs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--gray-400)">Nenhuma execução encontrada</td></tr>';
            return;
        }

        tbody.innerHTML = runs.map(r => {
            const duration = r.start_date && r.end_date ? afDuration(r.start_date, r.end_date) : (r.state === 'running' ? '<i class="fas fa-spinner fa-spin"></i>' : '-');
            return `<tr>
                <td><code style="font-size:11px">${esc(r.dag_id)}</code></td>
                <td style="font-size:11px">${esc(r.dag_run_id)}</td>
                <td>${afStateBadge(r.state)}</td>
                <td><span class="af-badge af-badge-light">${esc(r.run_type || '-')}</span></td>
                <td style="font-size:12px">${formatDate(r.start_date)}</td>
                <td style="font-size:12px">${r.end_date ? formatDate(r.end_date) : '-'}</td>
                <td>${duration}</td>
                <td>
                    <div class="af-btn-group">
                        <button class="af-btn-action info" onclick="afShowRunTasks('${esc(dagId)}','${esc(r.dag_run_id)}')" title="Ver Tasks">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button class="af-btn-action warning" onclick="afClearRun('${esc(dagId)}','${esc(r.dag_run_id)}')" title="Clear & Retry">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--danger)">${esc(e.message)}</td></tr>`;
    }
}

// ==========================================
//  TASK INSTANCES / LOGS
// ==========================================
async function afShowRunTasks(dagId, dagRunId) {
    const body = document.getElementById('modalDagBody');
    body.innerHTML = '<div style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--gray-300)"></i></div>';
    document.getElementById('modalDagTitle').innerHTML = `<i class="fas fa-tasks"></i> Tasks: ${esc(dagRunId)}`;

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=dag_run_detail&dag_id=${encodeURIComponent(dagId)}&dag_run_id=${encodeURIComponent(dagRunId)}`);
        if (!resp.success) throw new Error(resp.error);

        const tasks = resp.data.tasks || [];
        let html = `<p><strong>DAG:</strong> ${esc(dagId)} | <strong>Run:</strong> ${esc(dagRunId)} | <strong>Estado:</strong> ${afStateBadge(resp.data.run?.state)}</p>`;

        if (tasks.length === 0) {
            html += '<p style="color:var(--gray-400)">Nenhuma task instance encontrada</p>';
        } else {
            html += '<div class="table-responsive"><table class="table"><thead><tr><th>Task ID</th><th>Estado</th><th>Tentativa</th><th>Início</th><th>Duração</th><th>Log</th></tr></thead><tbody>';
            tasks.forEach(t => {
                const duration = t.start_date && t.end_date ? afDuration(t.start_date, t.end_date) : '-';
                html += `<tr>
                    <td><code>${esc(t.task_id)}</code></td>
                    <td>${afStateBadge(t.state)}</td>
                    <td>${t.try_number || 1}</td>
                    <td style="font-size:12px">${formatDate(t.start_date)}</td>
                    <td>${duration}</td>
                    <td><button class="af-btn-action" onclick="afShowTaskLog('${esc(dagId)}','${esc(dagRunId)}','${esc(t.task_id)}',${t.try_number || 1})" title="Ver Log"><i class="fas fa-file-alt"></i></button></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        }

        body.innerHTML = html;
        afOpenModal('modalDagDetail');
    } catch (e) {
        body.innerHTML = `<div class="alert alert-danger">${esc(e.message)}</div>`;
    }
}

async function afShowTaskLog(dagId, dagRunId, taskId, tryNum) {
    const content = document.getElementById('modalTaskLogContent');
    content.textContent = 'Carregando log...';
    document.getElementById('modalTaskLogTitle').innerHTML = `<i class="fas fa-file-alt"></i> ${esc(taskId)} (tentativa ${tryNum})`;
    
    // Close dag modal, open log modal
    afCloseModal('modalDagDetail');
    afOpenModal('modalTaskLog');

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=task_log&dag_id=${encodeURIComponent(dagId)}&dag_run_id=${encodeURIComponent(dagRunId)}&task_id=${encodeURIComponent(taskId)}&try_number=${tryNum}`);
        if (!resp.success) throw new Error(resp.error);
        content.textContent = resp.data.log || '(log vazio)';
    } catch (e) {
        content.textContent = 'ERRO: ' + e.message;
    }
}

// ==========================================
//  TRIGGER DAG
// ==========================================
function afShowTrigger(dagId) {
    document.getElementById('triggerDagId').textContent = dagId;
    document.getElementById('triggerDagConf').value = '{}';
    afOpenModal('modalTriggerDag');
}

async function afTriggerDagConfirm() {
    const dagId = document.getElementById('triggerDagId').textContent;
    let conf = {};
    try { conf = JSON.parse(document.getElementById('triggerDagConf').value); } catch(e) {
        HelpDesk.toast('⚠️ JSON inválido na configuração', 'warning');
        return;
    }

    try {
        const resp = await HelpDesk.api('POST', AF_API, { action: 'trigger_dag', dag_id: dagId, conf });
        afCloseModal('modalTriggerDag');
        if (resp.success) {
            HelpDesk.toast(resp.message || '✅ DAG disparada!', 'success');
            setTimeout(() => afLoadDags(), 2000);
        } else {
            HelpDesk.toast('❌ ' + (resp.error || 'Erro'), 'error');
        }
    } catch (e) {
        HelpDesk.toast('❌ ' + e.message, 'error');
    }
}

// ==========================================
//  TOGGLE DAG (pause/unpause)
// ==========================================
async function afToggleDag(dagId, isPaused) {
    const action = isPaused ? 'pausar' : 'ativar';
    if (!confirm(`Deseja ${action} a DAG "${dagId}"?`)) return;

    try {
        const resp = await HelpDesk.api('POST', AF_API, { action: 'toggle_dag', dag_id: dagId, is_paused: isPaused });
        if (resp.success) {
            HelpDesk.toast(resp.message, 'success');
            afLoadDags();
            afLoadOverview();
        }
    } catch (e) {
        HelpDesk.toast('❌ ' + e.message, 'error');
    }
}

// ==========================================
//  CLEAR DAG RUN
// ==========================================
async function afClearRun(dagId, dagRunId) {
    if (!confirm(`Limpar e reagendar a execução "${dagRunId}"?`)) return;

    try {
        const resp = await HelpDesk.api('POST', AF_API, { action: 'clear_dag_run', dag_id: dagId, dag_run_id: dagRunId });
        if (resp.success) {
            HelpDesk.toast(resp.message, 'success');
        }
    } catch (e) {
        HelpDesk.toast('❌ ' + e.message, 'error');
    }
}

// ==========================================
//  IMPORT ERRORS
// ==========================================
async function afLoadImportErrors() {
    const tbody = document.getElementById('afErrorsTable');
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=import_errors`);
        if (!resp.success) throw new Error(resp.error);

        const errors = resp.data.errors || [];
        if (errors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--success)"><i class="fas fa-check-circle"></i> Nenhum erro de importação</td></tr>';
            return;
        }

        tbody.innerHTML = errors.map(e => `<tr>
            <td><code style="font-size:11px">${esc(e.filename || '-')}</code></td>
            <td><pre style="font-size:11px;max-height:200px;overflow:auto;white-space:pre-wrap;margin:0;background:#FEF2F2;padding:8px;border-radius:4px;color:#991B1B">${esc(e.stack_trace || '-')}</pre></td>
            <td style="font-size:12px;white-space:nowrap">${formatDate(e.timestamp)}</td>
        </tr>`).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--danger)">${esc(e.message)}</td></tr>`;
    }
}

// ==========================================
//  CONNECTIONS
// ==========================================
async function afLoadConnections() {
    const tbody = document.getElementById('afConnsTable');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=connections`);
        if (!resp.success) throw new Error(resp.error);

        const conns = resp.data.connections || [];
        if (conns.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">Nenhuma conexão</td></tr>';
            return;
        }

        tbody.innerHTML = conns.map(c => `<tr>
            <td><code>${esc(c.connection_id)}</code></td>
            <td><span class="af-badge af-badge-light">${esc(c.conn_type || '-')}</span></td>
            <td>${esc(c.host || '-')}</td>
            <td>${c.port || '-'}</td>
            <td>${esc(c.schema || '-')}</td>
            <td>${esc(c.login || '-')}</td>
        </tr>`).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--danger)">${esc(e.message)}</td></tr>`;
    }
}

// ==========================================
//  VARIABLES
// ==========================================
async function afLoadVariables() {
    const tbody = document.getElementById('afVarsTable');
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=variables`);
        if (!resp.success) throw new Error(resp.error);

        const vars = resp.data.variables || [];
        if (vars.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray-400)">Nenhuma variável</td></tr>';
            return;
        }

        tbody.innerHTML = vars.map(v => `<tr>
            <td><code>${esc(v.key)}</code></td>
            <td style="font-size:12px;max-width:400px;word-break:break-all">${esc(v.value || '')}</td>
            <td style="color:var(--gray-400)">${esc(v.description || '-')}</td>
        </tr>`).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--danger)">${esc(e.message)}</td></tr>`;
    }
}

// ==========================================
//  POOLS
// ==========================================
async function afLoadPools() {
    const tbody = document.getElementById('afPoolsTable');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=pools`);
        if (!resp.success) throw new Error(resp.error);

        const pools = resp.data || [];
        if (pools.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">Nenhum pool</td></tr>';
            return;
        }

        tbody.innerHTML = pools.map(p => {
            const total = p.slots || 0;
            const used = p.occupied_slots || p.running_slots || 0;
            const queued = p.queued_slots || 0;
            const free = p.open_slots || (total - used - queued);
            const pct = total > 0 ? Math.round((used / total) * 100) : 0;
            const barColor = pct > 80 ? '#EF4444' : pct > 50 ? '#F59E0B' : '#10B981';
            return `<tr>
                <td><code>${esc(p.name || p.pool)}</code></td>
                <td>${total}</td>
                <td>${used}</td>
                <td>${queued}</td>
                <td>${free}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="flex:1;height:8px;background:var(--gray-100);border-radius:4px;overflow:hidden">
                            <div style="width:${pct}%;height:100%;background:${barColor};border-radius:4px"></div>
                        </div>
                        <small>${pct}%</small>
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--danger)">${esc(e.message)}</td></tr>`;
    }
}

// ==========================================
//  ACTION LOG
// ==========================================
async function afLoadActionLog() {
    const tbody = document.getElementById('afActionLogTable');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=action_log`);
        if (!resp.success) throw new Error(resp.error);

        const logs = resp.data || [];
        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-400)">Nenhuma ação registrada</td></tr>';
            return;
        }

        const actionLabels = {
            'trigger_dag': '<span class="af-badge af-badge-success">▶ Trigger</span>',
            'toggle_dag': '<span class="af-badge af-badge-warning">⏸ Toggle</span>',
            'clear_dag_run': '<span class="af-badge af-badge-info">🔄 Clear</span>',
        };

        tbody.innerHTML = logs.map(l => {
            let detalhes = '-';
            try {
                const det = typeof l.detalhes === 'string' ? JSON.parse(l.detalhes) : l.detalhes;
                detalhes = Object.entries(det || {}).map(([k,v]) => `${k}: ${v}`).join(', ');
            } catch(e) {}

            return `<tr>
                <td style="font-size:12px;white-space:nowrap">${formatDate(l.criado_em)}</td>
                <td>${esc(l.usuario_nome || '-')}</td>
                <td>${actionLabels[l.acao] || esc(l.acao)}</td>
                <td><code style="font-size:11px">${esc(l.dag_id || '-')}</code></td>
                <td style="font-size:11px">${esc(detalhes)}</td>
            </tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--danger)">${esc(e.message)}</td></tr>`;
    }
}

// ==========================================
//  CONFIGURAÇÃO
// ==========================================
async function afLoadConfig() {
    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=config`);
        if (!resp.success) return;
        const c = resp.data;
        document.getElementById('afCfgUrl').value = c.url || '';
        document.getElementById('afCfgUser').value = c.username || '';
        document.getElementById('afCfgTimeout').value = c.timeout || 30;
        if (c.url) afAirflowUrl = c.url;
    } catch (e) {}
}

async function afSaveConfig() {
    try {
        const resp = await HelpDesk.api('POST', AF_API, {
            action: 'save_config',
            url: document.getElementById('afCfgUrl').value,
            username: document.getElementById('afCfgUser').value,
            password: document.getElementById('afCfgPass').value,
            timeout: parseInt(document.getElementById('afCfgTimeout').value) || 30,
        });
        if (resp.success) {
            HelpDesk.toast('✅ Configuração salva!', 'success');
            afAirflowUrl = document.getElementById('afCfgUrl').value;
            afLoadOverview();
        } else {
            HelpDesk.toast('❌ ' + (resp.error || 'Erro'), 'error');
        }
    } catch (e) {
        HelpDesk.toast('❌ ' + e.message, 'error');
    }
}

async function afTestConnection() {
    const resultEl = document.getElementById('afConfigTestResult');
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testando conexão...</div>';

    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=test_connection`);
        if (!resp.success) throw new Error(resp.error);
        const d = resp.data;
        if (d.online) {
            resultEl.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <strong>Conectado!</strong><br>
                <small>MetaDB: ${d.metadb} | Scheduler: ${d.scheduler} | URL: ${esc(d.url)}</small>
            </div>`;
        } else {
            resultEl.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> <strong>Falha na conexão</strong><br>
                <small>${esc(d.error || 'Erro desconhecido')}</small>
            </div>`;
        }
    } catch (e) {
        resultEl.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ${esc(e.message)}</div>`;
    }
}

// ==========================================
//  FAVORITOS
// ==========================================
async function afLoadFavoritos() {
    try {
        const resp = await HelpDesk.api('GET', `${AF_API}?action=favoritos`);
        if (resp.success) {
            afFavoritos = (resp.data || []).map(f => f.dag_id);
        }
    } catch(e) {}
}

async function afToggleFav(dagId, iconEl) {
    try {
        const resp = await HelpDesk.api('POST', AF_API, { action: 'toggle_favorito', dag_id: dagId });
        if (resp.success) {
            if (resp.data.favorited) {
                iconEl.className = 'fas fa-star';
                iconEl.style.color = '#F59E0B';
                afFavoritos.push(dagId);
            } else {
                iconEl.className = 'far fa-star';
                iconEl.style.color = 'var(--gray-300)';
                afFavoritos = afFavoritos.filter(f => f !== dagId);
            }
        }
    } catch(e) {}
}

// ==========================================
//  HELPERS
// ==========================================
function esc(str) {
    if (str === null || str === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' });
    } catch(e) { return dateStr; }
}

function afDuration(start, end) {
    if (!start || !end) return '-';
    const ms = new Date(end) - new Date(start);
    if (ms < 0) return '-';
    const secs = Math.floor(ms / 1000);
    if (secs < 60) return secs + 's';
    const mins = Math.floor(secs / 60);
    const remSecs = secs % 60;
    if (mins < 60) return `${mins}m ${remSecs}s`;
    const hours = Math.floor(mins / 60);
    const remMins = mins % 60;
    return `${hours}h ${remMins}m`;
}

function afStateBadge(state) {
    const badges = {
        'success':  '<span class="af-badge af-badge-success">✅ Success</span>',
        'failed':   '<span class="af-badge af-badge-danger">❌ Failed</span>',
        'running':  '<span class="af-badge af-badge-primary">🔵 Running</span>',
        'queued':   '<span class="af-badge af-badge-info">⏳ Queued</span>',
        'up_for_retry': '<span class="af-badge af-badge-warning">🔄 Retry</span>',
        'up_for_reschedule': '<span class="af-badge af-badge-secondary">📅 Reschedule</span>',
        'upstream_failed': '<span class="af-badge af-badge-danger">⬆️ Upstream Failed</span>',
        'skipped':  '<span class="af-badge af-badge-secondary">⏭ Skipped</span>',
        'no_status': '<span class="af-badge af-badge-light">— No Status</span>',
    };
    return badges[state] || `<span class="af-badge af-badge-secondary">${esc(state || '-')}</span>`;
}
</script>
