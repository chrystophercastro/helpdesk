<?php
/**
 * View: SLA Dashboard Avançado
 * Painel visual de SLA em tempo real, semáforo, MTTR e compliance
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-tachometer-alt" style="margin-right:8px;color:var(--warning)"></i> SLA Dashboard</h1>
        <p class="page-subtitle">
            <?php if (!isAdmin()): ?>
                <i class="fas fa-filter" style="margin-right:4px;color:var(--primary)"></i> Métricas de SLA do seu departamento
            <?php else: ?>
                Métricas de SLA, compliance e tempos de resposta em tempo real
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('sla_predictive')">
            <i class="fas fa-robot"></i> Análise Preditiva
        </button>
        <button class="btn btn-sm" onclick="slaRefresh()">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="sla-kpi-grid" id="slaKpiGrid">
    <div class="sla-kpi">
        <div class="sla-kpi-icon resolucao"><i class="fas fa-clipboard-check"></i></div>
        <div class="sla-kpi-info">
            <span class="sla-kpi-value" id="slaTaxaResolucao">--</span>
            <span class="sla-kpi-label">SLA Resolução</span>
        </div>
        <div class="sla-kpi-bar"><div class="sla-kpi-bar-fill" id="slaBarResolucao"></div></div>
    </div>
    <div class="sla-kpi">
        <div class="sla-kpi-icon resposta"><i class="fas fa-reply"></i></div>
        <div class="sla-kpi-info">
            <span class="sla-kpi-value" id="slaTaxaResposta">--</span>
            <span class="sla-kpi-label">SLA Resposta</span>
        </div>
        <div class="sla-kpi-bar"><div class="sla-kpi-bar-fill" id="slaBarResposta"></div></div>
    </div>
    <div class="sla-kpi">
        <div class="sla-kpi-icon mttr"><i class="fas fa-clock"></i></div>
        <div class="sla-kpi-info">
            <span class="sla-kpi-value" id="slaMTTR">--</span>
            <span class="sla-kpi-label">MTTR Geral</span>
        </div>
        <div class="sla-kpi-sub" id="slaMTTRMes"></div>
    </div>
    <div class="sla-kpi">
        <div class="sla-kpi-icon risco"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="sla-kpi-info">
            <span class="sla-kpi-value" id="slaEmRisco">--</span>
            <span class="sla-kpi-label">Em Risco / Estourados</span>
        </div>
        <div class="sla-kpi-sub" id="slaRiscoDetalhe"></div>
    </div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="sla-semaforo" onclick="slaSwitchTab('sla-semaforo')">
        <i class="fas fa-traffic-light"></i> Semáforo
    </button>
    <button class="ad-tab" data-tab="sla-risco" onclick="slaSwitchTab('sla-risco')">
        <i class="fas fa-exclamation-circle"></i> Em Risco
    </button>
    <button class="ad-tab" data-tab="sla-mttr" onclick="slaSwitchTab('sla-mttr')">
        <i class="fas fa-stopwatch"></i> MTTR
    </button>
    <button class="ad-tab" data-tab="sla-tendencia" onclick="slaSwitchTab('sla-tendencia')">
        <i class="fas fa-chart-line"></i> Tendência
    </button>
    <button class="ad-tab" data-tab="sla-tecnicos" onclick="slaSwitchTab('sla-tecnicos')">
        <i class="fas fa-users"></i> Por Técnico
    </button>
</div>

<!-- Tab: Semáforo -->
<div class="ad-tab-content active" id="tab-sla-semaforo">
    <div class="sla-semaforo-grid" id="slaSemaforoGrid">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Tab: Em Risco -->
<div class="ad-tab-content" id="tab-sla-risco">
    <div id="slaRiscoList">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Tab: MTTR -->
<div class="ad-tab-content" id="tab-sla-mttr">
    <div class="sla-mttr-controls">
        <select id="slaMttrDimensao" class="form-control" style="width:auto" onchange="slaCarregarMTTR()">
            <option value="prioridade">Por Prioridade</option>
            <option value="categoria">Por Categoria</option>
            <option value="tecnico">Por Técnico</option>
        </select>
        <select id="slaMttrDias" class="form-control" style="width:auto" onchange="slaCarregarMTTR()">
            <option value="7">Últimos 7 dias</option>
            <option value="30" selected>Últimos 30 dias</option>
            <option value="90">Últimos 90 dias</option>
            <option value="365">Último ano</option>
        </select>
    </div>
    <div id="slaMttrContent">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Tab: Tendência -->
<div class="ad-tab-content" id="tab-sla-tendencia">
    <div id="slaTendenciaContent">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Tab: Por Técnico -->
<div class="ad-tab-content" id="tab-sla-tecnicos">
    <div id="slaTecnicosContent">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<script>
const SLA_API = '<?= BASE_URL ?>/api/sla.php';

document.addEventListener('DOMContentLoaded', () => slaInit());

async function slaInit() {
    await slaCarregarOverview();
    slaCarregarSemaforo();
    slaCarregarEmRisco();
    slaCarregarMTTR();
    slaCarregarTendencia();
    slaCarregarTecnicos();
}

function slaRefresh() { slaInit(); }

function slaSwitchTab(tabId) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');
}

// ==================== OVERVIEW ====================
async function slaCarregarOverview() {
    try {
        const r = await fetch(`${SLA_API}?action=overview`);
        const data = await r.json();
        if (!data.success) return;
        const d = data.data;

        // Taxa Resolução
        document.getElementById('slaTaxaResolucao').textContent = d.taxa_resolucao + '%';
        const barRes = document.getElementById('slaBarResolucao');
        barRes.style.width = d.taxa_resolucao + '%';
        barRes.style.background = slaCorTaxa(d.taxa_resolucao);

        // Taxa Resposta  
        document.getElementById('slaTaxaResposta').textContent = d.taxa_resposta + '%';
        const barResp = document.getElementById('slaBarResposta');
        barResp.style.width = d.taxa_resposta + '%';
        barResp.style.background = slaCorTaxa(d.taxa_resposta);

        // MTTR
        document.getElementById('slaMTTR').textContent = slaFormatMin(d.mttr);
        document.getElementById('slaMTTRMes').innerHTML = `<i class="fas fa-calendar"></i> Último mês: ${slaFormatMin(d.mttr_mes)}`;

        // Em risco
        const totalProblema = d.em_risco + d.estourados;
        document.getElementById('slaEmRisco').textContent = totalProblema;
        document.getElementById('slaRiscoDetalhe').innerHTML = 
            `<span style="color:var(--warning)"><i class="fas fa-exclamation-triangle"></i> ${d.em_risco} em risco</span> · ` +
            `<span style="color:var(--danger)"><i class="fas fa-times-circle"></i> ${d.estourados} estourados</span>`;
    } catch(e) { console.error('SLA Overview:', e); }
}

// ==================== SEMÁFORO ====================
async function slaCarregarSemaforo() {
    const el = document.getElementById('slaSemaforoGrid');
    try {
        const r = await fetch(`${SLA_API}?action=semaforo`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        const prioLabels = {critica:'Crítica',alta:'Alta',media:'Média',baixa:'Baixa'};
        const prioColors = {critica:'#EF4444',alta:'#F59E0B',media:'#3B82F6',baixa:'#6B7280'};

        el.innerHTML = data.data.map(s => {
            const total = s.vermelho + s.amarelo + s.verde;
            const statusGeral = s.vermelho > 0 ? 'vermelho' : (s.amarelo > 0 ? 'amarelo' : 'verde');
            return `
            <div class="sla-semaforo-card">
                <div class="sla-semaforo-header">
                    <span class="sla-prio-badge" style="background:${prioColors[s.prioridade]}">${prioLabels[s.prioridade]}</span>
                    <div class="sla-semaforo-light ${statusGeral}">
                        <div class="sla-light vermelho ${statusGeral === 'vermelho' ? 'active' : ''}"></div>
                        <div class="sla-light amarelo ${statusGeral === 'amarelo' ? 'active' : ''}"></div>
                        <div class="sla-light verde ${statusGeral === 'verde' ? 'active' : ''}"></div>
                    </div>
                </div>
                <div class="sla-semaforo-stats">
                    <div class="sla-sem-stat">
                        <span class="sla-sem-count verde">${s.verde}</span>
                        <span class="sla-sem-label">No prazo</span>
                    </div>
                    <div class="sla-sem-stat">
                        <span class="sla-sem-count amarelo">${s.amarelo}</span>
                        <span class="sla-sem-label">Em risco</span>
                    </div>
                    <div class="sla-sem-stat">
                        <span class="sla-sem-count vermelho">${s.vermelho}</span>
                        <span class="sla-sem-label">Estourado</span>
                    </div>
                </div>
                <div class="sla-semaforo-meta">
                    <div class="sla-sem-meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Resposta: ${slaFormatMin(s.sla_resposta)}</span>
                    </div>
                    <div class="sla-sem-meta-item">
                        <i class="fas fa-check-double"></i>
                        <span>Resolução: ${slaFormatMin(s.sla_resolucao)}</span>
                    </div>
                    <div class="sla-sem-meta-item">
                        <i class="fas fa-stopwatch"></i>
                        <span>MTTR: ${slaFormatMin(s.mttr)}</span>
                    </div>
                    <div class="sla-sem-meta-item">
                        <i class="fas fa-percentage"></i>
                        <span>Compliance: ${s.compliance}%</span>
                    </div>
                </div>
                ${total > 0 ? `<div class="sla-semaforo-bar">
                    <div style="width:${s.verde/total*100}%;background:var(--success)"></div>
                    <div style="width:${s.amarelo/total*100}%;background:var(--warning)"></div>
                    <div style="width:${s.vermelho/total*100}%;background:var(--danger)"></div>
                </div>` : '<div class="sla-semaforo-empty">Nenhum chamado aberto</div>'}
            </div>`;
        }).join('');
    } catch(e) { el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`; }
}

// ==================== EM RISCO ====================
async function slaCarregarEmRisco() {
    const el = document.getElementById('slaRiscoList');
    try {
        const r = await fetch(`${SLA_API}?action=em_risco`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-check-circle" style="color:var(--success);font-size:48px"></i><h3>Tudo no prazo!</h3><p>Nenhum chamado em risco de estourar o SLA</p></div>';
            return;
        }

        el.innerHTML = `<div class="sla-risco-table">
            <table>
                <thead>
                    <tr>
                        <th>Chamado</th>
                        <th>Prioridade</th>
                        <th>Técnico</th>
                        <th>SLA</th>
                        <th>Decorrido</th>
                        <th>Status SLA</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.data.map(c => {
                        const pct = parseFloat(c.percentual_sla);
                        const statusClass = pct > 100 ? 'estourado' : (pct >= 80 ? 'risco' : 'ok');
                        const statusLabel = pct > 100 ? 'ESTOURADO' : (pct >= 80 ? 'EM RISCO' : 'OK');
                        return `<tr class="sla-risco-row ${statusClass}" onclick="window.location.href='<?= BASE_URL ?>/index.php?page=chamados&action=ver&id=${c.id}'">
                            <td>
                                <strong>#${slaEscape(c.codigo)}</strong><br>
                                <small>${slaEscape(c.titulo)}</small>
                            </td>
                            <td><span class="sla-prio-tag ${c.prioridade}">${c.prioridade}</span></td>
                            <td>${slaEscape(c.tecnico_nome || 'Sem técnico')}</td>
                            <td>${slaFormatMin(c.tempo_resolucao)}</td>
                            <td>${slaFormatMin(c.minutos_decorridos)}</td>
                            <td>
                                <div class="sla-gauge-mini">
                                    <div class="sla-gauge-fill ${statusClass}" style="width:${Math.min(pct, 100)}%"></div>
                                </div>
                                <span class="sla-status-tag ${statusClass}">${pct.toFixed(0)}% — ${statusLabel}</span>
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>`;
    } catch(e) { el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`; }
}

// ==================== MTTR ====================
async function slaCarregarMTTR() {
    const el = document.getElementById('slaMttrContent');
    const dimensao = document.getElementById('slaMttrDimensao').value;
    const dias = document.getElementById('slaMttrDias').value;

    try {
        const r = await fetch(`${SLA_API}?action=mttr&dimensao=${dimensao}&dias=${dias}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-chart-bar" style="font-size:48px;color:var(--gray-400)"></i><h3>Sem dados</h3><p>Nenhum chamado resolvido no período selecionado</p></div>';
            return;
        }

        const maxMttr = Math.max(...data.data.map(d => d.mttr || 0), 1);

        el.innerHTML = `<div class="sla-mttr-chart">
            ${data.data.map(d => `
                <div class="sla-mttr-row">
                    <div class="sla-mttr-label">${slaEscape(d.dimensao)}</div>
                    <div class="sla-mttr-bar-wrap">
                        <div class="sla-mttr-bar" style="width:${(d.mttr/maxMttr*100).toFixed(1)}%;background:${slaCorMttr(d.mttr)}">
                            <span>${slaFormatMin(d.mttr)}</span>
                        </div>
                    </div>
                    <div class="sla-mttr-detail">
                        <small>Min: ${slaFormatMin(d.min_resolucao)} · Max: ${slaFormatMin(d.max_resolucao)} · ${d.total} chamados</small>
                    </div>
                </div>
            `).join('')}
        </div>`;
    } catch(e) { el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`; }
}

// ==================== TENDÊNCIA ====================
async function slaCarregarTendencia() {
    const el = document.getElementById('slaTendenciaContent');
    try {
        const r = await fetch(`${SLA_API}?action=tendencia&meses=6`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-chart-line" style="font-size:48px;color:var(--gray-400)"></i><h3>Sem dados históricos</h3></div>';
            return;
        }

        const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        const maxTotal = Math.max(...data.data.map(d => d.total), 1);

        el.innerHTML = `
        <div class="sla-trend-grid">
            <div class="sla-trend-chart">
                <h4><i class="fas fa-chart-bar"></i> Compliance Mensal</h4>
                <div class="sla-trend-bars">
                    ${data.data.map(d => {
                        const taxa = d.total > 0 ? Math.round(d.dentro_sla / d.total * 100) : 0;
                        const mesNum = parseInt(d.mes.split('-')[1]) - 1;
                        return `<div class="sla-trend-col">
                            <div class="sla-trend-value" style="color:${slaCorTaxa(taxa)}">${taxa}%</div>
                            <div class="sla-trend-bar-outer">
                                <div class="sla-trend-bar-inner" style="height:${taxa}%;background:${slaCorTaxa(taxa)}"></div>
                            </div>
                            <div class="sla-trend-month">${meses[mesNum]}</div>
                        </div>`;
                    }).join('')}
                </div>
            </div>
            <div class="sla-trend-chart">
                <h4><i class="fas fa-stopwatch"></i> MTTR Mensal</h4>
                <div class="sla-trend-bars">
                    ${data.data.map(d => {
                        const maxMttr = Math.max(...data.data.map(x => x.mttr_medio || 0), 1);
                        const pct = ((d.mttr_medio || 0) / maxMttr * 100).toFixed(1);
                        const mesNum = parseInt(d.mes.split('-')[1]) - 1;
                        return `<div class="sla-trend-col">
                            <div class="sla-trend-value">${slaFormatMin(d.mttr_medio || 0)}</div>
                            <div class="sla-trend-bar-outer">
                                <div class="sla-trend-bar-inner" style="height:${pct}%;background:${slaCorMttr(d.mttr_medio || 0)}"></div>
                            </div>
                            <div class="sla-trend-month">${meses[mesNum]}</div>
                        </div>`;
                    }).join('')}
                </div>
            </div>
        </div>`;
    } catch(e) { el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`; }
}

// ==================== POR TÉCNICO ====================
async function slaCarregarTecnicos() {
    const el = document.getElementById('slaTecnicosContent');
    try {
        const r = await fetch(`${SLA_API}?action=compliance_tecnico`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-users" style="font-size:48px;color:var(--gray-400)"></i><h3>Sem dados</h3></div>';
            return;
        }

        el.innerHTML = `<div class="sla-tecnico-grid">
            ${data.data.map(t => `
                <div class="sla-tecnico-card">
                    <div class="sla-tecnico-avatar">${slaEscape(t.tecnico).charAt(0).toUpperCase()}</div>
                    <div class="sla-tecnico-info">
                        <h4>${slaEscape(t.tecnico)}</h4>
                        <div class="sla-tecnico-stats">
                            <span><i class="fas fa-ticket-alt"></i> ${t.total} chamados</span>
                            <span><i class="fas fa-check"></i> ${t.dentro_sla} no prazo</span>
                            <span><i class="fas fa-stopwatch"></i> MTTR: ${slaFormatMin(t.mttr)}</span>
                        </div>
                    </div>
                    <div class="sla-tecnico-gauge">
                        <svg viewBox="0 0 36 36" class="sla-circular-chart">
                            <path class="sla-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="sla-circle" stroke="${slaCorTaxa(t.taxa)}" stroke-dasharray="${t.taxa}, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <text x="18" y="20.35" class="sla-circle-text">${t.taxa}%</text>
                        </svg>
                    </div>
                </div>
            `).join('')}
        </div>`;
    } catch(e) { el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`; }
}

// ==================== UTILS ====================
function slaEscape(s) { if(!s) return ''; const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function slaFormatMin(min) {
    min = parseInt(min) || 0;
    if (min < 60) return min + 'min';
    if (min < 1440) return Math.floor(min/60) + 'h ' + (min%60 > 0 ? (min%60)+'min' : '');
    return Math.floor(min/1440) + 'd ' + Math.floor((min%1440)/60) + 'h';
}
function slaCorTaxa(taxa) {
    if (taxa >= 90) return 'var(--success)';
    if (taxa >= 70) return 'var(--warning)';
    return 'var(--danger)';
}
function slaCorMttr(min) {
    if (min <= 60) return 'var(--success)';
    if (min <= 240) return '#3B82F6';
    if (min <= 480) return 'var(--warning)';
    return 'var(--danger)';
}

// Auto-refresh every 2 minutes
setInterval(slaInit, 120000);
</script>
