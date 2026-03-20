<?php
/**
 * View: Monitor / NOC
 * Dashboard de monitoramento de serviços, uptime e incidentes
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-heartbeat" style="margin-right:8px;color:var(--danger)"></i> Monitor / NOC</h1>
        <p class="page-subtitle">Monitoramento de serviços, uptime e alertas em tempo real</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('monitor_correlation')">
            <i class="fas fa-robot"></i> Correlacionar IA
        </button>
        <button class="btn btn-sm" onclick="monVerificarTodos()" id="monBtnCheckAll">
            <i class="fas fa-sync-alt"></i> Verificar Todos
        </button>
        <button class="btn btn-primary btn-sm" onclick="monAbrirModal()">
            <i class="fas fa-plus"></i> Novo Serviço
        </button>
    </div>
</div>

<!-- Status Cards -->
<div class="mon-stats-grid" id="monStatsGrid">
    <div class="mon-stat-card online">
        <div class="mon-stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="mon-stat-info">
            <span class="mon-stat-value" id="monOnline">-</span>
            <span class="mon-stat-label">Online</span>
        </div>
    </div>
    <div class="mon-stat-card offline">
        <div class="mon-stat-icon"><i class="fas fa-times-circle"></i></div>
        <div class="mon-stat-info">
            <span class="mon-stat-value" id="monOffline">-</span>
            <span class="mon-stat-label">Offline</span>
        </div>
    </div>
    <div class="mon-stat-card degraded">
        <div class="mon-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="mon-stat-info">
            <span class="mon-stat-value" id="monDegraded">-</span>
            <span class="mon-stat-label">Degradado</span>
        </div>
    </div>
    <div class="mon-stat-card uptime">
        <div class="mon-stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="mon-stat-info">
            <span class="mon-stat-value" id="monUptime">-</span>
            <span class="mon-stat-label">Uptime Médio</span>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="mon-dashboard" onclick="monSwitchTab('mon-dashboard')">
        <i class="fas fa-th-large"></i> Dashboard
    </button>
    <button class="ad-tab" data-tab="mon-incidentes" onclick="monSwitchTab('mon-incidentes')">
        <i class="fas fa-exclamation-circle"></i> Incidentes
    </button>
</div>

<!-- Tab: Dashboard -->
<div class="ad-tab-content active" id="tab-mon-dashboard">
    <div class="mon-services-grid" id="monServicesGrid">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Tab: Incidentes -->
<div class="ad-tab-content" id="tab-mon-incidentes">
    <div class="mon-incidents-list" id="monIncidentsList">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Modal: Novo/Editar Serviço -->
<div class="modal-overlay" id="monModal" style="display:none">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h2 id="monModalTitle"><i class="fas fa-plus-circle"></i> Novo Serviço</h2>
            <button class="modal-close" onclick="document.getElementById('monModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="monEditId">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" id="monNome" class="form-control" placeholder="Ex: API Principal">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select id="monTipo" class="form-control" onchange="monTipoChange()">
                        <option value="http">HTTP</option>
                        <option value="https">HTTPS</option>
                        <option value="ping">Ping (ICMP)</option>
                        <option value="tcp">TCP Port</option>
                        <option value="dns">DNS</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Host *</label>
                <input type="text" id="monHost" class="form-control" placeholder="Ex: google.com ou 192.168.1.1">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group" id="monPortaGroup">
                    <label class="form-label">Porta</label>
                    <input type="number" id="monPorta" class="form-control" placeholder="80">
                </div>
                <div class="form-group">
                    <label class="form-label">Intervalo (seg)</label>
                    <input type="number" id="monIntervalo" class="form-control" value="60" min="10">
                </div>
                <div class="form-group">
                    <label class="form-label">Timeout (seg)</label>
                    <input type="number" id="monTimeout" class="form-control" value="10" min="1">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" id="monCaminhoGroup">
                    <label class="form-label">Caminho</label>
                    <input type="text" id="monCaminho" class="form-control" placeholder="/" value="/">
                </div>
                <div class="form-group">
                    <label class="form-label">Grupo</label>
                    <input type="text" id="monGrupo" class="form-control" placeholder="Geral" value="Geral">
                </div>
            </div>
            <div class="form-group" id="monEsperadoGroup">
                <label class="form-label">Resposta Esperada</label>
                <input type="text" id="monEsperado" class="form-control" placeholder="200 (código HTTP) ou texto esperado">
            </div>
            <label style="display:flex;align-items:center;gap:8px;margin-top:8px;cursor:pointer">
                <input type="checkbox" id="monNotificar" checked> Enviar alertas quando offline
            </label>
            <button class="btn btn-primary" onclick="monSalvar()" style="width:100%;margin-top:16px">
                <i class="fas fa-save"></i> Salvar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Detalhes -->
<div class="modal-overlay" id="monDetailModal" style="display:none">
    <div class="modal" style="max-width:800px;max-height:85vh;overflow-y:auto">
        <div class="modal-header">
            <h2 id="monDetailTitle"><i class="fas fa-chart-area"></i> Detalhes</h2>
            <button class="modal-close" onclick="document.getElementById('monDetailModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body" id="monDetailBody">
            <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>
</div>

<script>
const MON_API = '<?= BASE_URL ?>/api/monitor.php';

document.addEventListener('DOMContentLoaded', () => monInit());

async function monInit() {
    try {
        const r = await fetch(`${MON_API}?action=overview`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        monRenderOverview(data.data);
    } catch(e) {
        document.getElementById('monServicesGrid').innerHTML = `<p style="color:var(--danger)">${e.message}</p>`;
    }
    monCarregarIncidentes();
}

function monSwitchTab(tabId) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');
}

function monRenderOverview(data) {
    document.getElementById('monOnline').textContent = data.online;
    document.getElementById('monOffline').textContent = data.offline;
    document.getElementById('monDegraded').textContent = data.degradado;
    document.getElementById('monUptime').textContent = data.uptime_medio + '%';

    const grid = document.getElementById('monServicesGrid');
    if (!data.servicos.length) {
        grid.innerHTML = `
            <div class="gh-empty-state" style="grid-column:1/-1;padding:60px 0">
                <i class="fas fa-heartbeat" style="font-size:48px;color:var(--gray-400)"></i>
                <h3>Nenhum serviço monitorado</h3>
                <p>Adicione serviços para monitorar a disponibilidade</p>
                <button class="btn btn-primary" onclick="monAbrirModal()" style="margin-top:12px">
                    <i class="fas fa-plus"></i> Novo Serviço
                </button>
            </div>`;
        return;
    }

    // Agrupar
    const grupos = {};
    data.servicos.forEach(s => {
        if (!grupos[s.grupo]) grupos[s.grupo] = [];
        grupos[s.grupo].push(s);
    });

    let html = '';
    for (const [grupo, servicos] of Object.entries(grupos)) {
        html += `<div class="mon-group"><h3 class="mon-group-title"><i class="fas fa-folder"></i> ${monEscape(grupo)}</h3>`;
        html += `<div class="mon-group-cards">`;
        servicos.forEach(s => {
            const statusClass = s.status === 'online' ? 'online' : (s.status === 'offline' ? 'offline' : 'degraded');
            const statusIcon = s.status === 'online' ? 'fa-check-circle' : (s.status === 'offline' ? 'fa-times-circle' : 'fa-exclamation-triangle');
            const tipoIcon = {http:'fa-globe',https:'fa-lock',ping:'fa-satellite-dish',tcp:'fa-plug',dns:'fa-server'}[s.tipo] || 'fa-server';
            html += `
                <div class="mon-card ${statusClass}" onclick="monVerDetalhes(${s.id})">
                    <div class="mon-card-header">
                        <div class="mon-card-status ${statusClass}"><i class="fas ${statusIcon}"></i></div>
                        <div class="mon-card-actions">
                            <button class="btn-icon" title="Verificar agora" onclick="event.stopPropagation();monVerificar(${s.id})"><i class="fas fa-sync-alt"></i></button>
                            <button class="btn-icon" title="Editar" onclick="event.stopPropagation();monEditar(${s.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon text-danger" title="Excluir" onclick="event.stopPropagation();monExcluir(${s.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <h4 class="mon-card-name">${monEscape(s.nome)}</h4>
                    <p class="mon-card-host"><i class="fas ${tipoIcon}"></i> ${monEscape(s.host)}${s.porta ? ':'+s.porta : ''}</p>
                    <div class="mon-card-footer">
                        <span class="mon-card-ms">${s.resposta_ms !== null ? s.resposta_ms + 'ms' : '--'}</span>
                        <span class="mon-card-uptime">${s.uptime_percent}%</span>
                        <span class="mon-card-time">${s.ultimo_check ? monTimeAgo(s.ultimo_check) : 'Nunca'}</span>
                    </div>
                </div>`;
        });
        html += `</div></div>`;
    }
    grid.innerHTML = html;
}

// Verificar um serviço
async function monVerificar(id) {
    try {
        monToast('Verificando...', 'info');
        const r = await fetch(MON_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action: 'verificar', id})
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        const res = data.data;
        monToast(`${res.servico}: ${res.status} (${res.resposta_ms}ms)`, res.status === 'online' ? 'success' : 'danger');
        monInit();
    } catch(e) { monToast('Erro: ' + e.message, 'danger'); }
}

// Verificar todos
async function monVerificarTodos() {
    const btn = document.getElementById('monBtnCheckAll');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
    try {
        const r = await fetch(MON_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action: 'verificar_todos'})
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        monToast('Verificação concluída!', 'success');
        monInit();
    } catch(e) { monToast('Erro: ' + e.message, 'danger'); }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync-alt"></i> Verificar Todos';
}

// Modal
function monAbrirModal(servico = null) {
    document.getElementById('monModal').style.display = 'flex';
    document.getElementById('monEditId').value = servico ? servico.id : '';
    document.getElementById('monModalTitle').innerHTML = servico ? '<i class="fas fa-edit"></i> Editar Serviço' : '<i class="fas fa-plus-circle"></i> Novo Serviço';
    document.getElementById('monNome').value = servico?.nome || '';
    document.getElementById('monTipo').value = servico?.tipo || 'http';
    document.getElementById('monHost').value = servico?.host || '';
    document.getElementById('monPorta').value = servico?.porta || '';
    document.getElementById('monIntervalo').value = servico?.intervalo_seg || 60;
    document.getElementById('monTimeout').value = servico?.timeout_seg || 10;
    document.getElementById('monCaminho').value = servico?.caminho || '/';
    document.getElementById('monGrupo').value = servico?.grupo || 'Geral';
    document.getElementById('monEsperado').value = servico?.esperado || '';
    document.getElementById('monNotificar').checked = servico ? !!servico.notificar : true;
    monTipoChange();
}

async function monEditar(id) {
    try {
        const r = await fetch(`${MON_API}?action=servico&id=${id}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        monAbrirModal(data.data);
    } catch(e) { monToast('Erro: ' + e.message, 'danger'); }
}

function monTipoChange() {
    const tipo = document.getElementById('monTipo').value;
    document.getElementById('monCaminhoGroup').style.display = ['http','https'].includes(tipo) ? '' : 'none';
    document.getElementById('monEsperadoGroup').style.display = ['http','https'].includes(tipo) ? '' : 'none';
    document.getElementById('monPortaGroup').style.display = tipo !== 'ping' && tipo !== 'dns' ? '' : 'none';
}

async function monSalvar() {
    const id = document.getElementById('monEditId').value;
    const dados = {
        action: id ? 'atualizar' : 'criar',
        id: id || undefined,
        nome: document.getElementById('monNome').value.trim(),
        tipo: document.getElementById('monTipo').value,
        host: document.getElementById('monHost').value.trim(),
        porta: document.getElementById('monPorta').value || null,
        caminho: document.getElementById('monCaminho').value || '/',
        esperado: document.getElementById('monEsperado').value || null,
        intervalo_seg: parseInt(document.getElementById('monIntervalo').value) || 60,
        timeout_seg: parseInt(document.getElementById('monTimeout').value) || 10,
        grupo: document.getElementById('monGrupo').value || 'Geral',
        notificar: document.getElementById('monNotificar').checked ? 1 : 0,
    };
    if (!dados.nome || !dados.host) return monToast('Nome e host obrigatórios', 'warning');
    try {
        const r = await fetch(MON_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(dados)
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        monToast(data.message, 'success');
        document.getElementById('monModal').style.display = 'none';
        monInit();
    } catch(e) { monToast('Erro: ' + e.message, 'danger'); }
}

async function monExcluir(id) {
    if (!confirm('Excluir este serviço e todo seu histórico?')) return;
    try {
        const r = await fetch(MON_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action: 'excluir', id})
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        monToast('Serviço excluído', 'success');
        monInit();
    } catch(e) { monToast('Erro: ' + e.message, 'danger'); }
}

// Detalhes
async function monVerDetalhes(id) {
    document.getElementById('monDetailModal').style.display = 'flex';
    const body = document.getElementById('monDetailBody');
    body.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>';

    try {
        const [sRes, hRes, uRes, iRes] = await Promise.all([
            fetch(`${MON_API}?action=servico&id=${id}`),
            fetch(`${MON_API}?action=historico_24h&id=${id}`),
            fetch(`${MON_API}?action=uptime&id=${id}&dias=30`),
            fetch(`${MON_API}?action=incidentes&servico_id=${id}`)
        ]);
        const [sData, hData, uData, iData] = await Promise.all([sRes.json(), hRes.json(), uRes.json(), iRes.json()]);

        const s = sData.data;
        const hist = hData.data || [];
        const uptime = uData.data || [];
        const incidents = iData.data || [];
        const statusClass = s.status === 'online' ? 'online' : (s.status === 'offline' ? 'offline' : 'degraded');

        document.getElementById('monDetailTitle').innerHTML = `<i class="fas fa-chart-area"></i> ${monEscape(s.nome)}`;

        // Uptime bars (last 30 days)
        let uptimeBars = '';
        if (uptime.length) {
            uptimeBars = uptime.map(u => {
                const pct = parseFloat(u.percent) || 0;
                const color = pct >= 99 ? 'var(--success)' : (pct >= 90 ? 'var(--warning)' : 'var(--danger)');
                return `<div class="mon-uptime-bar" style="background:${color}" title="${u.dia}: ${pct}%"></div>`;
            }).join('');
        }

        // Response time chart (simple bars)
        let chartBars = '';
        if (hist.length) {
            const maxMs = Math.max(...hist.map(h => h.avg_ms || 0), 1);
            chartBars = hist.map(h => {
                const pct = ((h.avg_ms || 0) / maxMs * 100).toFixed(1);
                const color = (h.offline_count || 0) > 0 ? 'var(--danger)' : 'var(--success)';
                const hora = (h.hora || '').split(' ')[1] || '';
                return `<div class="mon-chart-bar-wrap" title="${hora}: ${Math.round(h.avg_ms || 0)}ms avg">
                    <div class="mon-chart-bar" style="height:${pct}%;background:${color}"></div>
                    <span class="mon-chart-label">${hora.substring(0,2)}h</span>
                </div>`;
            }).join('');
        }

        body.innerHTML = `
            <div class="mon-detail-status ${statusClass}">
                <i class="fas ${s.status === 'online' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                <span>${s.status.toUpperCase()}</span>
                <span style="margin-left:auto;font-size:13px;opacity:0.7">Último check: ${s.ultimo_check ? monTimeAgo(s.ultimo_check) : 'Nunca'}</span>
            </div>

            <div class="mon-detail-info-grid">
                <div class="mon-detail-info-item">
                    <span class="label">Tipo</span>
                    <span class="value">${s.tipo.toUpperCase()}</span>
                </div>
                <div class="mon-detail-info-item">
                    <span class="label">Host</span>
                    <span class="value">${monEscape(s.host)}${s.porta ? ':'+s.porta : ''}</span>
                </div>
                <div class="mon-detail-info-item">
                    <span class="label">Resposta</span>
                    <span class="value">${s.resposta_ms !== null ? s.resposta_ms + 'ms' : '--'}</span>
                </div>
                <div class="mon-detail-info-item">
                    <span class="label">Uptime</span>
                    <span class="value">${s.uptime_percent}%</span>
                </div>
                <div class="mon-detail-info-item">
                    <span class="label">Checks</span>
                    <span class="value">${s.total_checks}</span>
                </div>
                <div class="mon-detail-info-item">
                    <span class="label">Falhas</span>
                    <span class="value" style="color:var(--danger)">${s.total_falhas}</span>
                </div>
            </div>

            ${uptimeBars ? `
            <div class="mon-detail-section">
                <h4><i class="fas fa-calendar-check"></i> Uptime Últimos 30 Dias</h4>
                <div class="mon-uptime-bars">${uptimeBars}</div>
            </div>` : ''}

            ${chartBars ? `
            <div class="mon-detail-section">
                <h4><i class="fas fa-chart-bar"></i> Tempo de Resposta (24h)</h4>
                <div class="mon-chart">${chartBars}</div>
            </div>` : ''}

            ${incidents.length ? `
            <div class="mon-detail-section">
                <h4><i class="fas fa-exclamation-circle"></i> Incidentes Recentes</h4>
                ${incidents.slice(0, 10).map(inc => {
                    const tipoCls = inc.tipo === 'outage' ? 'offline' : (inc.tipo === 'recovery' ? 'online' : 'degraded');
                    const tipoLabel = {outage: 'Outage', degraded: 'Degradado', recovery: 'Recuperação'}[inc.tipo] || inc.tipo;
                    return `
                    <div class="mon-incident-item">
                        <span class="mon-incident-badge ${tipoCls}">${tipoLabel}</span>
                        <span>${monEscape(inc.mensagem || '')}</span>
                        <span class="mon-incident-time">${monTimeAgo(inc.inicio)}${inc.duracao_seg ? ' · ' + monDuration(inc.duracao_seg) : ''}</span>
                    </div>`;
                }).join('')}
            </div>` : ''}

            <div style="display:flex;gap:8px;margin-top:16px">
                <button class="btn btn-primary btn-sm" onclick="monVerificar(${s.id});document.getElementById('monDetailModal').style.display='none'">
                    <i class="fas fa-sync-alt"></i> Verificar Agora
                </button>
                <button class="btn btn-sm" onclick="document.getElementById('monDetailModal').style.display='none';monEditar(${s.id})">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        `;
    } catch(e) { body.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`; }
}

// Incidentes
async function monCarregarIncidentes() {
    const el = document.getElementById('monIncidentsList');
    try {
        const r = await fetch(`${MON_API}?action=incidentes`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-check-circle" style="color:var(--success)"></i><h3>Sem incidentes</h3><p>Todos os serviços operando normalmente</p></div>';
            return;
        }
        el.innerHTML = data.data.map(inc => {
            const tipoCls = inc.tipo === 'outage' ? 'offline' : (inc.tipo === 'recovery' ? 'online' : 'degraded');
            const tipoLabel = {outage: 'Outage', degraded: 'Degradado', recovery: 'Recuperação'}[inc.tipo] || inc.tipo;
            return `
            <div class="gh-list-item" style="cursor:default">
                <div class="gh-list-icon ${tipoCls === 'online' ? 'open' : (tipoCls === 'offline' ? 'closed' : 'merged')}">
                    <i class="fas ${inc.tipo === 'recovery' ? 'fa-check' : 'fa-exclamation'}"></i>
                </div>
                <div class="gh-list-content">
                    <div class="gh-list-title"><strong>${monEscape(inc.servico_nome)}</strong></div>
                    <div class="gh-list-meta">
                        <span class="mon-incident-badge ${tipoCls}">${tipoLabel}</span>
                        <span>${monEscape(inc.mensagem || '')}</span>
                        <span>·</span>
                        <span>${monTimeAgo(inc.inicio)}</span>
                        ${inc.duracao_seg ? `<span>· Duração: ${monDuration(inc.duracao_seg)}</span>` : ''}
                    </div>
                </div>
                <div class="gh-list-stats">
                    <span class="mon-incident-badge ${inc.resolvido ? 'online' : 'offline'}">${inc.resolvido ? 'Resolvido' : 'Ativo'}</span>
                </div>
            </div>`;
        }).join('');
    } catch(e) { el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`; }
}

// Utils
function monEscape(s) { if(!s) return ''; const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function monTimeAgo(dt) {
    if(!dt) return '';
    const d=new Date(dt), now=new Date(), diff=Math.floor((now-d)/1000);
    if(diff<60) return 'agora';
    if(diff<3600) return Math.floor(diff/60)+'min atrás';
    if(diff<86400) return Math.floor(diff/3600)+'h atrás';
    if(diff<2592000) return Math.floor(diff/86400)+'d atrás';
    return d.toLocaleDateString('pt-BR');
}
function monDuration(seg) {
    if(seg < 60) return seg + 's';
    if(seg < 3600) return Math.floor(seg/60) + 'min';
    if(seg < 86400) return Math.floor(seg/3600) + 'h ' + Math.floor((seg%3600)/60) + 'min';
    return Math.floor(seg/86400) + 'd ' + Math.floor((seg%86400)/3600) + 'h';
}
function monToast(msg, type='info') {
    const container = document.getElementById('monToastContainer') || (() => {
        const c = document.createElement('div'); c.id = 'monToastContainer'; c.className = 'gh-toast-container'; document.body.appendChild(c); return c;
    })();
    const icons = {success:'check-circle',danger:'exclamation-circle',warning:'exclamation-triangle',info:'info-circle'};
    const t = document.createElement('div'); t.className = `gh-toast ${type}`;
    t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}"></i><span>${msg}</span>`;
    container.appendChild(t);
    setTimeout(() => { t.classList.add('fade-out'); setTimeout(() => t.remove(), 300); }, 3500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none'); });
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.style.display = 'none'; }));

// Auto-refresh every 60s
setInterval(monInit, 60000);
</script>
