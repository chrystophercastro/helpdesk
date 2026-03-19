<?php
/**
 * View: Atualização do Sistema
 * Gerencia migrations, saúde e informações do sistema
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-cloud-upload-alt" style="color:#F59E0B"></i> Atualização do Sistema</h1>
        <p>Gerencie migrations, verifique a saúde e mantenha o sistema atualizado</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <span id="updVersion" class="upd-version-badge"></span>
        <span id="updAmbiente" class="upd-env-badge"></span>
    </div>
</div>

<!-- KPIs -->
<div class="upd-kpis">
    <div class="upd-kpi">
        <div class="upd-kpi-icon" style="background:#3B82F6"><i class="fas fa-layer-group"></i></div>
        <div class="upd-kpi-info">
            <span class="upd-kpi-val" id="updKpiTotal">-</span>
            <span class="upd-kpi-label">Total Migrations</span>
        </div>
    </div>
    <div class="upd-kpi">
        <div class="upd-kpi-icon" style="background:#10B981"><i class="fas fa-check-circle"></i></div>
        <div class="upd-kpi-info">
            <span class="upd-kpi-val" id="updKpiExec">-</span>
            <span class="upd-kpi-label">Executadas</span>
        </div>
    </div>
    <div class="upd-kpi">
        <div class="upd-kpi-icon" style="background:#F59E0B"><i class="fas fa-clock"></i></div>
        <div class="upd-kpi-info">
            <span class="upd-kpi-val" id="updKpiPend">-</span>
            <span class="upd-kpi-label">Pendentes</span>
        </div>
    </div>
    <div class="upd-kpi" id="updKpiSaudeCard">
        <div class="upd-kpi-icon" style="background:#10B981"><i class="fas fa-heartbeat"></i></div>
        <div class="upd-kpi-info">
            <span class="upd-kpi-val" id="updKpiSaude">-</span>
            <span class="upd-kpi-label">Saúde do Sistema</span>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" onclick="updTab('painel')"><i class="fas fa-tachometer-alt"></i> Visão Geral</button>
    <button class="ad-tab" onclick="updTab('migrations')"><i class="fas fa-database"></i> Migrations</button>
    <button class="ad-tab" onclick="updTab('historico')"><i class="fas fa-history"></i> Histórico</button>
    <button class="ad-tab" onclick="updTab('sistema')"><i class="fas fa-server"></i> Sistema</button>
</div>

<!-- Tab: Visão Geral -->
<div class="ad-tab-content active" id="tab-painel">
    <div class="upd-panel-grid">
        <!-- Health Checks -->
        <div class="upd-panel-card upd-panel-full">
            <h3><i class="fas fa-stethoscope"></i> Verificação de Saúde</h3>
            <div class="upd-health-grid" id="updHealthGrid">
                <div class="upd-loading"><i class="fas fa-spinner fa-spin"></i> Verificando...</div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="upd-panel-card">
            <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
            <div class="upd-actions-list">
                <button class="btn btn-primary" onclick="updExecutarTodas()" id="btnExecTodas" style="width:100%;margin-bottom:10px">
                    <i class="fas fa-play"></i> Executar Todas Pendentes
                </button>
                <button class="btn btn-secondary" onclick="updMarcarTodas()" style="width:100%;margin-bottom:10px">
                    <i class="fas fa-check-double"></i> Marcar Todas como Executadas
                </button>
                <button class="btn btn-secondary" onclick="updRecarregar()" style="width:100%">
                    <i class="fas fa-sync-alt"></i> Recarregar Status
                </button>
            </div>
            <div id="updQuickResult" class="upd-quick-result" style="display:none"></div>
        </div>

        <!-- Migrations Pendentes -->
        <div class="upd-panel-card">
            <h3><i class="fas fa-exclamation-triangle" style="color:#F59E0B"></i> Pendentes</h3>
            <div id="updPendingList" class="upd-pending-list">
                <div class="upd-loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Migrations -->
<div class="ad-tab-content" id="tab-migrations">
    <div class="upd-toolbar">
        <input type="text" class="form-control" placeholder="Buscar migration..." id="updBusca" oninput="updFiltrar()" style="max-width:300px">
        <select class="form-control" id="updFiltroStatus" onchange="updFiltrar()" style="max-width:200px">
            <option value="">Todas</option>
            <option value="pendente">Pendentes</option>
            <option value="executada">Executadas</option>
        </select>
        <div style="flex:1"></div>
        <button class="btn btn-primary" onclick="updExecutarTodas()" id="btnExecTodas2">
            <i class="fas fa-play"></i> Executar Pendentes
        </button>
    </div>
    <div class="upd-migrations-table" id="updMigrationsTable">
        <div class="upd-loading"><i class="fas fa-spinner fa-spin"></i> Carregando migrations...</div>
    </div>
</div>

<!-- Tab: Histórico -->
<div class="ad-tab-content" id="tab-historico">
    <div class="table-responsive">
        <table class="data-table" id="updHistTable">
            <thead>
                <tr>
                    <th>Migration</th>
                    <th>Status</th>
                    <th>Batch</th>
                    <th>Duração</th>
                    <th>Executada em</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody id="updHistBody">
                <tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Tab: Sistema -->
<div class="ad-tab-content" id="tab-sistema">
    <div class="upd-sys-grid" id="updSysGrid">
        <div class="upd-loading"><i class="fas fa-spinner fa-spin"></i> Carregando informações...</div>
    </div>
</div>

<!-- Modal: Output da Migration -->
<div class="modal-overlay" id="updModalOutput">
    <div class="modal-content" style="max-width:700px">
        <div class="modal-header">
            <h2 id="updModalOutputTitle"><i class="fas fa-terminal"></i> Output</h2>
            <button class="btn-close" onclick="updFecharModal('updModalOutput')">&times;</button>
        </div>
        <div class="modal-body">
            <pre id="updModalOutputContent" class="upd-output-pre"></pre>
        </div>
    </div>
</div>

<script>
const UPD_API = '<?= BASE_URL ?>/api/atualizacao.php';
let updData = null;

/* ─── Init ─── */
document.addEventListener('DOMContentLoaded', updInit);

async function updInit() {
    await updCarregarDados();
}

async function updCarregarDados() {
    try {
        const r = await fetch(`${UPD_API}?action=overview`);
        updData = await r.json();
        if (updData.erro) { HelpDesk.toast(updData.erro, 'danger'); return; }
        updRenderKPIs();
        updRenderHealth();
        updRenderPending();
        updRenderMigrations();
        updRenderHistorico();
        updRenderSistema();
    } catch (e) {
        HelpDesk.toast('Erro ao carregar dados: ' + e.message, 'danger');
    }
}

function updRecarregar() { updCarregarDados(); HelpDesk.toast('Dados recarregados', 'success'); }

/* ─── Tabs ─── */
function updTab(tab) {
    document.querySelectorAll('.ad-tab').forEach((b, i) => {
        b.classList.toggle('active', b.textContent.trim().includes(
            {painel:'Visão',migrations:'Migrations',historico:'Histórico',sistema:'Sistema'}[tab]
        ));
    });
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
}

/* ─── KPIs ─── */
function updRenderKPIs() {
    document.getElementById('updKpiTotal').textContent = updData.total;
    document.getElementById('updKpiExec').textContent = updData.executadas;
    document.getElementById('updKpiPend').textContent = updData.pendentes;

    const checks = updData.health_checks || [];
    const erros = checks.filter(c => c.status === 'erro').length;
    const warns = checks.filter(c => c.status === 'warning').length;
    const el = document.getElementById('updKpiSaude');

    if (erros > 0) {
        el.textContent = 'Crítico';
        el.parentElement.previousElementSibling.style.background = '#EF4444';
    } else if (warns > 0) {
        el.textContent = 'Atenção';
        el.parentElement.previousElementSibling.style.background = '#F59E0B';
    } else {
        el.textContent = 'Saudável';
    }

    // Pendentes highlight
    if (updData.pendentes > 0) {
        document.getElementById('updKpiPend').closest('.upd-kpi').classList.add('upd-kpi-warn');
    }

    // Version / Environment badges
    const si = updData.system_info?.helpdesk || {};
    document.getElementById('updVersion').textContent = 'v' + (si.versao || '?');
    const envEl = document.getElementById('updAmbiente');
    envEl.textContent = si.ambiente || 'N/A';
    envEl.className = 'upd-env-badge ' + (si.ambiente === 'Produção' ? 'upd-env-prod' : 'upd-env-dev');

    // Toggle buttons
    const hasP = updData.pendentes > 0;
    document.getElementById('btnExecTodas').disabled = !hasP;
    document.getElementById('btnExecTodas2').disabled = !hasP;
}

/* ─── Health ─── */
function updRenderHealth() {
    const checks = updData.health_checks || [];
    const grid = document.getElementById('updHealthGrid');
    grid.innerHTML = checks.map(c => `
        <div class="upd-health-item upd-health-${c.status}">
            <div class="upd-health-icon"><i class="${c.icone}"></i></div>
            <div class="upd-health-info">
                <strong>${c.nome}</strong>
                <span>${c.valor}</span>
            </div>
            <div class="upd-health-badge upd-st-${c.status}">
                ${c.status === 'ok' ? '<i class="fas fa-check"></i>' : c.status === 'warning' ? '<i class="fas fa-exclamation"></i>' : '<i class="fas fa-times"></i>'}
            </div>
        </div>
    `).join('');
}

/* ─── Pending List ─── */
function updRenderPending() {
    const el = document.getElementById('updPendingList');
    const pending = updData.lista_pendentes || [];
    if (!pending.length) {
        el.innerHTML = '<div class="upd-empty"><i class="fas fa-check-circle" style="color:#10B981;font-size:32px"></i><p>Sistema atualizado!</p></div>';
        return;
    }
    el.innerHTML = pending.map(p => `
        <div class="upd-pending-item">
            <div class="upd-pending-info">
                <i class="fas fa-file-code" style="color:#F59E0B"></i>
                <span>${p}</span>
            </div>
            <button class="btn btn-sm btn-primary" onclick="updExecutar('${p}')">
                <i class="fas fa-play"></i>
            </button>
        </div>
    `).join('');
}

/* ─── Migrations Table ─── */
function updRenderMigrations() {
    const migs = updData.migrations || [];
    const container = document.getElementById('updMigrationsTable');

    container.innerHTML = `<table class="data-table">
        <thead><tr>
            <th>Status</th>
            <th>Migration</th>
            <th>Descrição</th>
            <th>Tabelas</th>
            <th>Batch</th>
            <th>Duração</th>
            <th>Executada em</th>
            <th>Ações</th>
        </tr></thead>
        <tbody>${migs.map(m => {
            const isPend = m.status === 'pendente';
            const tables = (m.tabelas || []).map(t => `<span class="upd-chip">${t}</span>`).join(' ');
            return `<tr class="${isPend ? 'upd-row-pending' : ''}" data-nome="${m.nome}" data-status="${m.status}">
                <td>
                    <span class="upd-mig-status ${isPend ? 'upd-mig-pending' : 'upd-mig-done'}">
                        <i class="fas ${isPend ? 'fa-clock' : 'fa-check-circle'}"></i>
                        ${isPend ? 'Pendente' : 'Executada'}
                    </span>
                </td>
                <td><strong>${m.nome}</strong></td>
                <td class="upd-desc-cell">${m.descricao || '-'}</td>
                <td>${tables || '-'}</td>
                <td>${m.batch || '-'}</td>
                <td>${m.duracao_ms != null ? m.duracao_ms + 'ms' : '-'}</td>
                <td>${m.executado_em ? updFormatDate(m.executado_em) : '-'}</td>
                <td>
                    <div class="upd-mig-actions">
                        ${isPend ? `<button class="btn btn-sm btn-primary" onclick="updExecutar('${m.nome}')" title="Executar"><i class="fas fa-play"></i></button>` : ''}
                        ${!isPend && m.output ? `<button class="btn btn-sm btn-secondary" onclick="updVerOutput('${m.nome}')" title="Ver output"><i class="fas fa-terminal"></i></button>` : ''}
                        ${!isPend ? `<button class="btn btn-sm btn-secondary" onclick="updResetar('${m.nome}')" title="Resetar"><i class="fas fa-undo"></i></button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('')}</tbody>
    </table>`;
}

function updFiltrar() {
    const busca = document.getElementById('updBusca').value.toLowerCase();
    const status = document.getElementById('updFiltroStatus').value;
    document.querySelectorAll('#updMigrationsTable tbody tr').forEach(tr => {
        const nome = tr.dataset.nome || '';
        const st = tr.dataset.status || '';
        const matchB = !busca || nome.toLowerCase().includes(busca);
        const matchS = !status || st === status;
        tr.style.display = matchB && matchS ? '' : 'none';
    });
}

/* ─── Histórico ─── */
function updRenderHistorico() {
    const hist = updData.health_checks ? [] : [];
    // We need to fetch history separately or use overview data
    fetch(`${UPD_API}?action=historico`)
        .then(r => r.json())
        .then(data => {
            const list = data.historico || [];
            const body = document.getElementById('updHistBody');
            if (!list.length) {
                body.innerHTML = '<tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-secondary)">Nenhum registro de execução</td></tr>';
                return;
            }
            body.innerHTML = list.map(h => `
                <tr>
                    <td><strong>${h.migration}</strong></td>
                    <td>
                        <span class="upd-st-badge upd-st-${h.status}">
                            <i class="fas ${h.status==='sucesso'?'fa-check':h.status==='erro'?'fa-times':'fa-minus'}"></i>
                            ${h.status}
                        </span>
                    </td>
                    <td>${h.batch}</td>
                    <td>${h.duracao_ms}ms</td>
                    <td>${updFormatDate(h.executado_em)}</td>
                    <td>
                        ${h.output ? `<button class="btn btn-sm btn-secondary" onclick="updShowOutput('${updEsc(h.migration)}', \`${updEsc(h.output||'')}\`, \`${updEsc(h.erro||'')}\`)"><i class="fas fa-eye"></i></button>` : '-'}
                    </td>
                </tr>
            `).join('');
        });
}

/* ─── Sistema ─── */
function updRenderSistema() {
    const si = updData.system_info;
    if (!si) return;
    const grid = document.getElementById('updSysGrid');

    const phpExts = (si.php.extensoes || []).map(e => `<span class="upd-chip">${e}</span>`).join(' ');

    grid.innerHTML = `
        <!-- PHP -->
        <div class="upd-sys-card">
            <div class="upd-sys-header"><i class="fab fa-php" style="color:#777BB4;font-size:28px"></i><h3>PHP</h3></div>
            <div class="upd-sys-rows">
                <div class="upd-sys-row"><span>Versão</span><strong>${si.php.versao}</strong></div>
                <div class="upd-sys-row"><span>Memory Limit</span><strong>${si.php.memory_limit}</strong></div>
                <div class="upd-sys-row"><span>Max Execution</span><strong>${si.php.max_execution_time}s</strong></div>
                <div class="upd-sys-row"><span>Upload Max</span><strong>${si.php.upload_max}</strong></div>
                <div class="upd-sys-row"><span>Post Max</span><strong>${si.php.post_max}</strong></div>
                <div class="upd-sys-row"><span>Timezone</span><strong>${si.php.timezone}</strong></div>
            </div>
            <details style="margin-top:12px"><summary style="cursor:pointer;font-size:13px;color:var(--text-secondary)">
                <i class="fas fa-puzzle-piece"></i> ${si.php.extensoes?.length || 0} extensões</summary>
                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px">${phpExts}</div>
            </details>
        </div>

        <!-- MySQL -->
        <div class="upd-sys-card">
            <div class="upd-sys-header"><i class="fas fa-database" style="color:#00758F;font-size:24px"></i><h3>MySQL</h3></div>
            <div class="upd-sys-rows">
                <div class="upd-sys-row"><span>Versão</span><strong>${si.mysql.versao}</strong></div>
                <div class="upd-sys-row"><span>Database</span><strong>${si.mysql.database}</strong></div>
                <div class="upd-sys-row"><span>Tamanho</span><strong>${si.mysql.tamanho_mb} MB</strong></div>
                <div class="upd-sys-row"><span>Tabelas</span><strong>${si.mysql.total_tabelas}</strong></div>
            </div>
        </div>

        <!-- Servidor -->
        <div class="upd-sys-card">
            <div class="upd-sys-header"><i class="fas fa-server" style="color:#3B82F6;font-size:24px"></i><h3>Servidor</h3></div>
            <div class="upd-sys-rows">
                <div class="upd-sys-row"><span>Software</span><strong>${si.servidor.software}</strong></div>
                <div class="upd-sys-row"><span>OS</span><strong>${si.servidor.os}</strong></div>
                <div class="upd-sys-row"><span>Hostname</span><strong>${si.servidor.hostname}</strong></div>
                <div class="upd-sys-row"><span>Disco Livre</span><strong>${si.servidor.disco_livre_gb} GB</strong></div>
                <div class="upd-sys-row"><span>Disco Total</span><strong>${si.servidor.disco_total_gb} GB</strong></div>
                <div class="upd-sys-row">
                    <span>Uso</span>
                    <div style="display:flex;align-items:center;gap:8px;flex:1;justify-content:flex-end">
                        <div class="upd-disk-bar"><div class="upd-disk-fill" style="width:${si.servidor.disco_uso_pct}%;background:${si.servidor.disco_uso_pct > 90 ? '#EF4444' : si.servidor.disco_uso_pct > 75 ? '#F59E0B' : '#10B981'}"></div></div>
                        <strong>${si.servidor.disco_uso_pct}%</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- HelpDesk -->
        <div class="upd-sys-card">
            <div class="upd-sys-header"><i class="fas fa-headset" style="color:#F59E0B;font-size:24px"></i><h3>HelpDesk</h3></div>
            <div class="upd-sys-rows">
                <div class="upd-sys-row"><span>Versão</span><strong>${si.helpdesk.versao}</strong></div>
                <div class="upd-sys-row"><span>Ambiente</span><strong>${si.helpdesk.ambiente}</strong></div>
                <div class="upd-sys-row"><span>Base URL</span><strong>${si.helpdesk.base_url}</strong></div>
                <div class="upd-sys-row"><span>Path</span><strong style="font-size:11px;word-break:break-all">${si.helpdesk.base_path}</strong></div>
            </div>
        </div>

        <!-- Database Tables -->
        <div class="upd-sys-card upd-sys-full">
            <div class="upd-sys-header"><i class="fas fa-table" style="color:#8B5CF6;font-size:24px"></i><h3>Tabelas do Banco (${si.mysql.total_tabelas})</h3></div>
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                <table class="data-table">
                    <thead><tr><th>Tabela</th><th>Registros</th><th>Tamanho</th><th>Engine</th><th>Criada em</th></tr></thead>
                    <tbody>
                        ${(si.mysql.tabelas||[]).map(t => `
                            <tr>
                                <td><strong>${t.tabela}</strong></td>
                                <td>${Number(t.registros || 0).toLocaleString()}</td>
                                <td>${t.tamanho_kb} KB</td>
                                <td>${t.engine || '-'}</td>
                                <td>${t.criado_em ? updFormatDate(t.criado_em) : '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

/* ─── Actions ─── */
async function updExecutar(name) {
    if (!confirm(`Executar migration "${name}"?\n\n⚠️ Recomendação: Faça backup do banco antes de continuar.`)) return;

    HelpDesk.toast('Executando migration...', 'info');
    try {
        const r = await fetch(`${UPD_API}?action=executar`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({migration: name})
        });
        const data = await r.json();
        if (data.status === 'sucesso') {
            HelpDesk.toast(`Migration ${name} executada com sucesso (${data.duracao_ms}ms)`, 'success');
            if (data.output) updMostrarOutput(name, data.output);
        } else if (data.status === 'skip') {
            HelpDesk.toast(data.mensagem || 'Já executada', 'warning');
        } else {
            HelpDesk.toast(`Erro: ${data.erro || 'Falha desconhecida'}`, 'danger');
            if (data.output || data.erro) updMostrarOutput(name, (data.output||'') + '\n\nERRO: ' + (data.erro||''));
        }
        await updCarregarDados();
    } catch (e) {
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }
}

async function updExecutarTodas() {
    if (!updData || updData.pendentes === 0) {
        HelpDesk.toast('Nenhuma migration pendente', 'warning');
        return;
    }
    if (!confirm(`Executar ${updData.pendentes} migration(s) pendente(s)?\n\n⚠️ Recomendação: Faça backup do banco de dados antes de prosseguir.`)) return;

    HelpDesk.toast('Executando migrations...', 'info');
    try {
        const r = await fetch(`${UPD_API}?action=executar_todas`, {method:'POST'});
        const data = await r.json();

        if (data.erro) {
            HelpDesk.toast(data.erro, 'danger');
        } else if (data.erros > 0) {
            HelpDesk.toast(`${data.sucesso} sucesso, ${data.erros} erro(s)`, 'warning', 8000);
        } else {
            HelpDesk.toast(`${data.sucesso} migration(s) executadas com sucesso!`, 'success', 6000);
        }
        await updCarregarDados();
    } catch (e) {
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }
}

async function updMarcarTodas() {
    if (!confirm('Marcar todas as migrations como executadas SEM rodá-las?\n\nUse isso apenas se o banco já está com todas as tabelas criadas (ex: primeiro deploy com banco existente).')) return;

    try {
        const r = await fetch(`${UPD_API}?action=marcar_todas`, {method:'POST'});
        const data = await r.json();
        HelpDesk.toast(data.mensagem || `${data.marcadas} marcadas`, 'success');
        await updCarregarDados();
    } catch (e) {
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }
}

async function updResetar(name) {
    if (!confirm(`Resetar migration "${name}"?\n\nEla será removida do log e poderá ser executada novamente.`)) return;

    try {
        const r = await fetch(`${UPD_API}?action=resetar`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({migration: name})
        });
        const data = await r.json();
        HelpDesk.toast(data.mensagem || 'Migration resetada', 'success');
        await updCarregarDados();
    } catch (e) {
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }
}

/* ─── Modal ─── */
function updMostrarOutput(title, content) {
    document.getElementById('updModalOutputTitle').innerHTML = `<i class="fas fa-terminal"></i> ${title}`;
    document.getElementById('updModalOutputContent').textContent = content;
    document.getElementById('updModalOutput').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function updVerOutput(name) {
    const mig = (updData.migrations || []).find(m => m.nome === name);
    if (mig && mig.output) updMostrarOutput(name, mig.output);
}

function updShowOutput(name, output, erro) {
    let content = output || '';
    if (erro) content += '\n\n❌ ERRO:\n' + erro;
    updMostrarOutput(name, content);
}

function updFecharModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Overlay click & ESC
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('active')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

/* ─── Helpers ─── */
function updFormatDate(str) {
    if (!str) return '-';
    const d = new Date(str);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
}

function updEsc(str) {
    if (!str) return '';
    return str.replace(/`/g, '\\`').replace(/\$/g, '\\$').replace(/</g, '&lt;');
}
</script>
