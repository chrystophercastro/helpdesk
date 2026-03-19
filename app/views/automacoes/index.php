<?php
/**
 * View: Automações & Rotinas
 */
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-robot"></i> Automações & Rotinas</h1>
        <p>Motor de regras automáticas para otimizar sua rotina de TI</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="autoExecutarTodas()" title="Executar agora todas as automações ativas">
            <i class="fas fa-play-circle"></i> Executar Todas
        </button>
        <button class="btn btn-primary" onclick="autoAbrirModal()">
            <i class="fas fa-plus"></i> Nova Automação
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="auto-kpis" id="autoKpis"></div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="auto-painel" onclick="autoTab(this)">
        <i class="fas fa-tachometer-alt"></i> Painel
    </button>
    <button class="ad-tab" data-tab="auto-regras" onclick="autoTab(this)">
        <i class="fas fa-cogs"></i> Regras
    </button>
    <button class="ad-tab" data-tab="auto-historico" onclick="autoTab(this)">
        <i class="fas fa-history"></i> Histórico
    </button>
    <button class="ad-tab" data-tab="auto-cron" onclick="autoTab(this)">
        <i class="fas fa-clock"></i> Agendamento
    </button>
</div>

<!-- Tab: Painel -->
<div class="ad-tab-content active" id="auto-painel">
    <div class="auto-panel-grid">
        <div class="auto-panel-card">
            <h3><i class="fas fa-chart-bar"></i> Top Automações</h3>
            <div id="autoTopList"></div>
        </div>
        <div class="auto-panel-card">
            <h3><i class="fas fa-bolt"></i> Por Tipo de Trigger</h3>
            <div id="autoTriggerChart"></div>
        </div>
        <div class="auto-panel-card auto-panel-full">
            <h3><i class="fas fa-stream"></i> Últimas Execuções</h3>
            <div id="autoRecentLogs"></div>
        </div>
    </div>
</div>

<!-- Tab: Regras -->
<div class="ad-tab-content" id="auto-regras">
    <div class="auto-toolbar">
        <input type="text" id="autoBusca" placeholder="Buscar automações..." class="ct-search" oninput="autoCarregarRegras()">
        <select id="autoFiltroAtivo" class="ct-select" onchange="autoCarregarRegras()">
            <option value="">Todas</option>
            <option value="1">Ativas</option>
            <option value="0">Inativas</option>
        </select>
        <select id="autoFiltroTrigger" class="ct-select" onchange="autoCarregarRegras()">
            <option value="">Todos os triggers</option>
        </select>
    </div>
    <div class="auto-regras-grid" id="autoRegrasGrid"></div>
</div>

<!-- Tab: Histórico -->
<div class="ad-tab-content" id="auto-historico">
    <div class="auto-hist-list" id="autoHistList"></div>
</div>

<!-- Tab: Cron/Agendamento -->
<div class="ad-tab-content" id="auto-cron">
    <!-- Status Banner -->
    <div class="cron-status-banner" id="cronStatusBanner">
        <div class="cron-status-loading">
            <i class="fas fa-spinner fa-spin"></i> Verificando status do agendamento...
        </div>
    </div>

    <!-- Grid Principal -->
    <div class="cron-installer-grid">
        <!-- Card: Instalador -->
        <div class="cron-card cron-card-main">
            <div class="cron-card-header">
                <div class="cron-card-icon" style="background: linear-gradient(135deg, var(--primary), #6366F1)">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h3>Agendador Automático</h3>
                    <p>Instale o cron no Agendador de Tarefas do Windows para executar automaticamente</p>
                </div>
            </div>

            <div class="cron-config-form">
                <div class="cron-config-row">
                    <label><i class="fas fa-clock"></i> Intervalo de execução</label>
                    <div class="cron-interval-picker">
                        <button class="cron-intv-btn" data-min="1" onclick="cronSetIntervalo(1)">1 min</button>
                        <button class="cron-intv-btn active" data-min="5" onclick="cronSetIntervalo(5)">5 min</button>
                        <button class="cron-intv-btn" data-min="10" onclick="cronSetIntervalo(10)">10 min</button>
                        <button class="cron-intv-btn" data-min="15" onclick="cronSetIntervalo(15)">15 min</button>
                        <button class="cron-intv-btn" data-min="30" onclick="cronSetIntervalo(30)">30 min</button>
                        <button class="cron-intv-btn" data-min="60" onclick="cronSetIntervalo(60)">1 hora</button>
                    </div>
                    <div class="cron-interval-custom">
                        <input type="number" id="cronIntervalo" value="5" min="1" max="1440" class="form-control" style="width:80px">
                        <span>minutos</span>
                    </div>
                </div>
            </div>

            <div class="cron-actions">
                <button class="btn btn-success cron-action-btn" id="cronBtnInstalar" onclick="cronInstalar()">
                    <i class="fas fa-download"></i> Instalar Cron
                </button>
                <button class="btn btn-danger cron-action-btn" id="cronBtnDesinstalar" onclick="cronDesinstalar()" style="display:none">
                    <i class="fas fa-trash-alt"></i> Desinstalar Cron
                </button>
                <button class="btn btn-warning cron-action-btn" id="cronBtnReinstalar" onclick="cronInstalar()" style="display:none">
                    <i class="fas fa-sync-alt"></i> Reinstalar com novo intervalo
                </button>
                <button class="btn btn-secondary cron-action-btn" onclick="cronTestar()">
                    <i class="fas fa-play-circle"></i> Testar Execução
                </button>
            </div>

            <div id="cronActionResult" class="cron-action-result" style="display:none"></div>
        </div>

        <!-- Card: Detalhes da Tarefa -->
        <div class="cron-card">
            <div class="cron-card-header-sm">
                <h4><i class="fas fa-info-circle"></i> Detalhes da Tarefa</h4>
            </div>
            <div class="cron-details" id="cronDetalhes">
                <div class="cron-detail-item">
                    <span class="cron-detail-label">Status</span>
                    <span class="cron-detail-value" id="cronDetStatus">—</span>
                </div>
                <div class="cron-detail-item">
                    <span class="cron-detail-label">Nome da Tarefa</span>
                    <span class="cron-detail-value" id="cronDetNome">HelpDesk_Cron</span>
                </div>
                <div class="cron-detail-item">
                    <span class="cron-detail-label">Intervalo</span>
                    <span class="cron-detail-value" id="cronDetIntervalo">—</span>
                </div>
                <div class="cron-detail-item">
                    <span class="cron-detail-label">Próxima Execução</span>
                    <span class="cron-detail-value" id="cronDetProxima">—</span>
                </div>
                <div class="cron-detail-item">
                    <span class="cron-detail-label">Última Execução</span>
                    <span class="cron-detail-value" id="cronDetUltima">—</span>
                </div>
                <div class="cron-detail-item">
                    <span class="cron-detail-label">Último Resultado</span>
                    <span class="cron-detail-value" id="cronDetResultado">—</span>
                </div>
                <div class="cron-detail-item">
                    <span class="cron-detail-label">PHP</span>
                    <span class="cron-detail-value cron-detail-path" id="cronDetPhp">—</span>
                </div>
                <div class="cron-detail-item">
                    <span class="cron-detail-label">Script</span>
                    <span class="cron-detail-value cron-detail-path" id="cronDetScript">—</span>
                </div>
            </div>
        </div>

        <!-- Card: Log de Execuções -->
        <div class="cron-card cron-card-full">
            <div class="cron-card-header-sm" style="display:flex;justify-content:space-between;align-items:center">
                <h4><i class="fas fa-file-alt"></i> Log do Cron (últimas execuções)</h4>
                <button class="btn btn-sm btn-secondary" onclick="cronCarregarLogs()">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
            </div>
            <div class="cron-log-viewer" id="cronLogViewer">
                <p class="ct-empty">Clique em "Atualizar" para ver os logs</p>
            </div>
        </div>

        <!-- Card: Alternativas Manuais -->
        <div class="cron-card cron-card-full">
            <div class="cron-card-header-sm">
                <h4><i class="fas fa-tools"></i> Instalação Manual (Alternativa)</h4>
                <p style="font-size:12px;color:var(--text-secondary);margin-top:4px">
                    Se o instalador automático não funcionar (permissões), use estas instruções:
                </p>
            </div>
            <div class="cron-manual-grid">
                <div class="cron-manual-item">
                    <h5><i class="fas fa-terminal"></i> Windows Task Scheduler (schtasks)</h5>
                    <div class="auto-cron-code">
                        <code id="cronCmdManual">schtasks /Create /TN "HelpDesk_Cron" /TR "\"<span id="cronManualPhp">C:\xampp\php\php.exe</span>\" \"<span id="cronManualScript">C:\xampp\htdocs\helpdesk\cron.php</span>\"" /SC MINUTE /MO 5 /F</code>
                    </div>
                    <button class="btn btn-sm btn-secondary" onclick="autoCopiarCmd(document.getElementById('cronCmdManual').textContent)">
                        <i class="fas fa-copy"></i> Copiar Comando
                    </button>
                </div>
                <div class="cron-manual-item">
                    <h5><i class="fas fa-globe"></i> Via URL (HTTP)</h5>
                    <div class="auto-cron-code">
                        <code id="autoCronUrl"><?= rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/') . BASE_URL ?>/cron.php?key=helpdesk_cron_2026_secret</code>
                    </div>
                    <button class="btn btn-sm btn-secondary" onclick="autoCopiarCmd(document.getElementById('autoCronUrl').textContent)">
                        <i class="fas fa-copy"></i> Copiar URL
                    </button>
                </div>
                <div class="cron-manual-item">
                    <h5><i class="fas fa-mouse-pointer"></i> Execução Manual</h5>
                    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:8px">Execute todas as automações ativas elegíveis agora:</p>
                    <button class="btn btn-success btn-sm" onclick="autoExecutarTodas()">
                        <i class="fas fa-rocket"></i> Executar Agora
                    </button>
                    <div id="autoCronResult" class="auto-cron-result" style="display:none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Automação -->
<div class="modal-overlay" id="autoModal">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h2 id="autoModalTitulo">Nova Automação</h2>
            <button class="modal-close" onclick="autoFecharModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="autoId">

            <div class="form-group">
                <label>Nome da Automação *</label>
                <input type="text" id="autoNome" class="form-control" placeholder="Ex: Fechar chamados resolvidos há 7 dias">
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea id="autoDescricao" class="form-control" rows="2" placeholder="O que esta automação faz..."></textarea>
            </div>

            <div class="auto-form-section">
                <h4><i class="fas fa-crosshairs"></i> Gatilho (Quando executar?)</h4>
                <div class="form-row">
                    <div class="form-group" style="flex:1">
                        <label>Tipo de Trigger *</label>
                        <select id="autoTriggerTipo" class="form-control" onchange="autoRenderTriggerConfig()"></select>
                    </div>
                </div>
                <div id="autoTriggerConfigArea"></div>
            </div>

            <div class="auto-form-section">
                <h4><i class="fas fa-bolt"></i> Ação (O que fazer?)</h4>
                <div class="form-row">
                    <div class="form-group" style="flex:1">
                        <label>Tipo de Ação *</label>
                        <select id="autoAcaoTipo" class="form-control" onchange="autoRenderAcaoConfig()"></select>
                    </div>
                </div>
                <div id="autoAcaoConfigArea"></div>
            </div>

            <div class="form-group">
                <label class="auto-switch-label">
                    <input type="checkbox" id="autoAtivo">
                    <span class="auto-switch-slider"></span>
                    Ativar automação
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="autoFecharModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="autoSalvar()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Detalhes -->
<div class="modal-overlay" id="autoModalDetalhe">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h2>Detalhes da Automação</h2>
            <button class="modal-close" onclick="autoFecharModal()">&times;</button>
        </div>
        <div class="modal-body" id="autoDetalheBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="autoFecharModal()">Fechar</button>
        </div>
    </div>
</div>

<script>
const AUTO_API = '<?= BASE_URL ?>/api/automacoes.php';
let autoLabels = { triggers: {}, acoes: {} };
let autoTecnicos = [];
let autoCategorias = [];

// Toast helper (delegates to HelpDesk.toast)
function showToast(msg, type = 'info') {
    const typeMap = { error: 'danger', success: 'success', warning: 'warning', info: 'info' };
    if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) {
        HelpDesk.toast(msg, typeMap[type] || type);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    // Carregar labels primeiro
    try {
        const [lbl, tec, cat] = await Promise.all([
            fetch(AUTO_API + '?action=labels').then(r => { if (!r.ok) throw new Error('labels HTTP ' + r.status); return r.json(); }),
            fetch(AUTO_API + '?action=tecnicos').then(r => { if (!r.ok) throw new Error('tecnicos HTTP ' + r.status); return r.json(); }),
            fetch(AUTO_API + '?action=categorias').then(r => { if (!r.ok) throw new Error('categorias HTTP ' + r.status); return r.json(); })
        ]);
        autoLabels = lbl.data || { triggers: {}, acoes: {} };
        autoTecnicos = tec.data || [];
        autoCategorias = cat.data || [];

        // Popular selects
        autoPopularSelects();
    } catch(e) { console.error('Automações init error:', e); }

    autoCarregarOverview();
    autoCarregarRegras();
});

function autoTab(btn) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');

    if (btn.dataset.tab === 'auto-historico') autoCarregarHistorico();
}

function autoFecharModal() {
    ['autoModal', 'autoModalDetalhe'].forEach(id => document.getElementById(id)?.classList.remove('active'));
    document.body.style.overflow = '';
}
['autoModal', 'autoModalDetalhe'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) autoFecharModal();
    });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') autoFecharModal(); });

function autoPopularSelects() {
    // Trigger select
    const trigSel = document.getElementById('autoTriggerTipo');
    trigSel.innerHTML = '<option value="">Selecione o gatilho...</option>';
    for (const [key, val] of Object.entries(autoLabels.triggers)) {
        trigSel.innerHTML += `<option value="${key}">${val.label}</option>`;
    }

    // Acao select
    const acaoSel = document.getElementById('autoAcaoTipo');
    acaoSel.innerHTML = '<option value="">Selecione a ação...</option>';
    for (const [key, val] of Object.entries(autoLabels.acoes)) {
        acaoSel.innerHTML += `<option value="${key}">${val.label}</option>`;
    }

    // Filtro trigger
    const filtroTrig = document.getElementById('autoFiltroTrigger');
    filtroTrig.innerHTML = '<option value="">Todos os triggers</option>';
    for (const [key, val] of Object.entries(autoLabels.triggers)) {
        filtroTrig.innerHTML += `<option value="${key}">${val.label}</option>`;
    }
}

function autoTriggerLabel(t) { return autoLabels.triggers[t]?.label || t; }
function autoTriggerIcon(t) { return autoLabels.triggers[t]?.icon || 'fa-cog'; }
function autoTriggerCor(t) { return autoLabels.triggers[t]?.cor || '#6B7280'; }
function autoAcaoLabel(t) { return autoLabels.acoes[t]?.label || t; }
function autoAcaoIcon(t) { return autoLabels.acoes[t]?.icon || 'fa-cog'; }

// === OVERVIEW ===
async function autoCarregarOverview() {
    try {
        const r = await fetch(AUTO_API + '?action=overview');
        if (!r.ok) { console.error('Overview HTTP', r.status); return; }
        const j = await r.json();
        if (!j.success) { console.error('Overview fail:', j.error); return; }
        const d = j.data;

        document.getElementById('autoKpis').innerHTML = `
            <div class="auto-kpi"><div class="auto-kpi-icon" style="background:var(--primary)"><i class="fas fa-cogs"></i></div>
                <div class="auto-kpi-info"><span class="auto-kpi-val">${d.ativas}/${d.total}</span><span class="auto-kpi-label">Automações Ativas</span></div></div>
            <div class="auto-kpi"><div class="auto-kpi-icon" style="background:var(--success)"><i class="fas fa-check-circle"></i></div>
                <div class="auto-kpi-info"><span class="auto-kpi-val">${d.execucoes_hoje}</span><span class="auto-kpi-label">Execuções Hoje</span></div></div>
            <div class="auto-kpi"><div class="auto-kpi-icon" style="background:var(--purple)"><i class="fas fa-bullseye"></i></div>
                <div class="auto-kpi-info"><span class="auto-kpi-val">${d.itens_afetados_hoje}</span><span class="auto-kpi-label">Itens Processados</span></div></div>
            <div class="auto-kpi ${d.erros_hoje > 0 ? 'auto-kpi-warn' : ''}"><div class="auto-kpi-icon" style="background:${d.erros_hoje > 0 ? 'var(--danger)' : 'var(--success)'}"><i class="fas fa-${d.erros_hoje > 0 ? 'times-circle' : 'shield-alt'}"></i></div>
                <div class="auto-kpi-info"><span class="auto-kpi-val">${d.erros_hoje}</span><span class="auto-kpi-label">Erros Hoje</span></div></div>
        `;

        // Top automações
        const topEl = document.getElementById('autoTopList');
        if (d.top_automacoes.length) {
            topEl.innerHTML = d.top_automacoes.map((a, i) => `
                <div class="auto-top-item">
                    <span class="auto-top-rank">#${i+1}</span>
                    <div class="auto-top-info">
                        <strong>${a.nome}</strong>
                        <small><i class="fas ${autoTriggerIcon(a.trigger_tipo)}"></i> ${autoTriggerLabel(a.trigger_tipo)} → <i class="fas ${autoAcaoIcon(a.acao_tipo)}"></i> ${autoAcaoLabel(a.acao_tipo)}</small>
                    </div>
                    <div class="auto-top-stats">
                        <span>${a.total_execucoes} exec</span>
                        <span>${a.total_itens_afetados} itens</span>
                    </div>
                </div>
            `).join('');
        } else {
            topEl.innerHTML = '<p class="ct-empty">Nenhuma automação executada ainda</p>';
        }

        // Trigger chart
        const trigEl = document.getElementById('autoTriggerChart');
        if (d.por_trigger.length) {
            const maxT = Math.max(...d.por_trigger.map(t => parseInt(t.total)));
            trigEl.innerHTML = '<div class="auto-bar-list">' + d.por_trigger.map(t => {
                const pct = maxT ? (t.total / maxT * 100) : 0;
                const cor = autoTriggerCor(t.trigger_tipo);
                return `<div class="auto-bar-row">
                    <span class="auto-bar-label"><i class="fas ${autoTriggerIcon(t.trigger_tipo)}" style="color:${cor}"></i> ${autoTriggerLabel(t.trigger_tipo)}</span>
                    <div class="auto-bar-wrap"><div class="auto-bar" style="width:${pct}%;background:${cor}"></div></div>
                    <span class="auto-bar-val">${t.total} <small>(${t.ativas} ativas)</small></span>
                </div>`;
            }).join('') + '</div>';
        } else {
            trigEl.innerHTML = '<p class="ct-empty">Sem dados</p>';
        }

        // Recent logs
        const logEl = document.getElementById('autoRecentLogs');
        if (d.ultimas_execucoes.length) {
            logEl.innerHTML = '<div class="auto-log-list">' + d.ultimas_execucoes.map(l => {
                const dt = new Date(l.executado_em);
                const statusIcon = l.status === 'sucesso' ? 'fa-check-circle' : l.status === 'erro' ? 'fa-times-circle' : 'fa-exclamation-circle';
                const statusCor = l.status === 'sucesso' ? 'var(--success)' : l.status === 'erro' ? 'var(--danger)' : 'var(--warning)';
                const detalhes = l.detalhes ? JSON.parse(l.detalhes) : {};
                return `<div class="auto-log-item">
                    <i class="fas ${statusIcon}" style="color:${statusCor}"></i>
                    <div class="auto-log-info">
                        <strong>${l.automacao_nome}</strong>
                        <small>${detalhes.msg || ''} • ${l.itens_afetados} item(ns) • ${l.duracao_ms}ms</small>
                    </div>
                    <span class="auto-log-time">${dt.toLocaleDateString('pt-BR')} ${dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</span>
                </div>`;
            }).join('') + '</div>';
        } else {
            logEl.innerHTML = '<p class="ct-empty">Nenhuma execução registrada</p>';
        }

    } catch(e) { console.error('Overview error', e); }
}

// === REGRAS ===
async function autoCarregarRegras() {
    const busca = document.getElementById('autoBusca')?.value || '';
    const ativo = document.getElementById('autoFiltroAtivo')?.value ?? '';
    const trigger = document.getElementById('autoFiltroTrigger')?.value || '';

    let url = AUTO_API + '?action=listar';
    if (busca) url += '&busca=' + encodeURIComponent(busca);
    if (ativo !== '') url += '&ativo=' + ativo;
    if (trigger) url += '&trigger_tipo=' + trigger;

    try {
        const r = await fetch(url);
        const j = await r.json();
        const grid = document.getElementById('autoRegrasGrid');

        if (!j.data || !j.data.length) {
            grid.innerHTML = '<p class="ct-empty" style="grid-column:1/-1">Nenhuma automação encontrada</p>';
            return;
        }

        grid.innerHTML = j.data.map(a => {
            const trigConfig = JSON.parse(a.trigger_config || '{}');
            const acaoConfig = JSON.parse(a.acao_config || '{}');
            const cor = autoTriggerCor(a.trigger_tipo);
            const ultimaExec = a.ultima_execucao ? new Date(a.ultima_execucao).toLocaleDateString('pt-BR') + ' ' + new Date(a.ultima_execucao).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}) : 'Nunca';

            return `
            <div class="auto-regra-card ${a.ativo ? '' : 'auto-regra-inativa'}">
                <div class="auto-regra-header">
                    <div class="auto-regra-icon" style="background:${cor}20;color:${cor}">
                        <i class="fas ${autoTriggerIcon(a.trigger_tipo)}"></i>
                    </div>
                    <div class="auto-regra-title">
                        <h4>${a.nome}</h4>
                        <small>${a.descricao ? a.descricao.substring(0, 80) + (a.descricao.length > 80 ? '...' : '') : ''}</small>
                    </div>
                    <label class="auto-toggle" title="${a.ativo ? 'Desativar' : 'Ativar'}">
                        <input type="checkbox" ${a.ativo ? 'checked' : ''} onchange="autoToggle(${a.id})">
                        <span class="auto-toggle-slider"></span>
                    </label>
                </div>
                <div class="auto-regra-flow">
                    <span class="auto-regra-chip" style="background:${cor}15;color:${cor};border-color:${cor}30">
                        <i class="fas ${autoTriggerIcon(a.trigger_tipo)}"></i> ${autoTriggerLabel(a.trigger_tipo)}
                    </span>
                    <i class="fas fa-arrow-right" style="color:var(--text-secondary);font-size:12px"></i>
                    <span class="auto-regra-chip" style="background:rgba(59,130,246,.1);color:#3B82F6;border-color:rgba(59,130,246,.2)">
                        <i class="fas ${autoAcaoIcon(a.acao_tipo)}"></i> ${autoAcaoLabel(a.acao_tipo)}
                    </span>
                </div>
                <div class="auto-regra-footer">
                    <span title="Última execução"><i class="fas fa-clock"></i> ${ultimaExec}</span>
                    <span title="Total de execuções"><i class="fas fa-play"></i> ${a.total_execucoes}x</span>
                    <span title="Itens afetados"><i class="fas fa-bullseye"></i> ${a.total_itens_afetados}</span>
                    <div class="auto-regra-actions">
                        <button class="btn btn-sm" onclick="autoExecutar(${a.id})" title="Executar agora"><i class="fas fa-play-circle"></i></button>
                        <button class="btn btn-sm" onclick="autoVerDetalhes(${a.id})" title="Detalhes"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm" onclick="autoEditar(${a.id})" title="Editar"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="autoExcluir(${a.id})" title="Excluir"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>`;
        }).join('');

    } catch(e) { console.error('Regras error', e); }
}

// === HISTÓRICO ===
async function autoCarregarHistorico() {
    try {
        const r = await fetch(AUTO_API + '?action=overview');
        const j = await r.json();
        if (!j.success) return;

        const el = document.getElementById('autoHistList');
        const logs = j.data.ultimas_execucoes;

        if (!logs.length) {
            el.innerHTML = '<p class="ct-empty">Nenhuma execução no histórico</p>';
            return;
        }

        el.innerHTML = `<table class="ct-table">
            <thead><tr>
                <th>Data/Hora</th><th>Automação</th><th>Trigger</th><th>Ação</th><th>Status</th><th>Itens</th><th>Duração</th><th>Detalhes</th>
            </tr></thead>
            <tbody>${logs.map(l => {
                const dt = new Date(l.executado_em);
                const statusCls = l.status === 'sucesso' ? 'auto-st-ok' : l.status === 'erro' ? 'auto-st-err' : 'auto-st-parc';
                const statusLbl = l.status === 'sucesso' ? 'Sucesso' : l.status === 'erro' ? 'Erro' : 'Parcial';
                const detalhes = l.detalhes ? JSON.parse(l.detalhes) : {};
                return `<tr>
                    <td>${dt.toLocaleDateString('pt-BR')} ${dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit',second:'2-digit'})}</td>
                    <td><strong>${l.automacao_nome}</strong></td>
                    <td><span class="auto-chip-sm"><i class="fas ${autoTriggerIcon(l.trigger_tipo)}"></i> ${autoTriggerLabel(l.trigger_tipo)}</span></td>
                    <td><span class="auto-chip-sm"><i class="fas ${autoAcaoIcon(l.acao_tipo)}"></i> ${autoAcaoLabel(l.acao_tipo)}</span></td>
                    <td><span class="cmdb-badge ${statusCls}">${statusLbl}</span></td>
                    <td>${l.itens_afetados}</td>
                    <td>${l.duracao_ms}ms</td>
                    <td class="auto-hist-det">${detalhes.msg || l.erro_mensagem || '-'}</td>
                </tr>`;
            }).join('')}</tbody>
        </table>`;
    } catch(e) { console.error('Historico error', e); }
}

// === MODAL ===
function autoAbrirModal(dados = null) {
    document.getElementById('autoModalTitulo').textContent = dados ? 'Editar Automação' : 'Nova Automação';
    document.getElementById('autoId').value = dados?.id || '';
    document.getElementById('autoNome').value = dados?.nome || '';
    document.getElementById('autoDescricao').value = dados?.descricao || '';
    document.getElementById('autoTriggerTipo').value = dados?.trigger_tipo || '';
    document.getElementById('autoAcaoTipo').value = dados?.acao_tipo || '';
    document.getElementById('autoAtivo').checked = dados?.ativo ? true : false;

    autoRenderTriggerConfig(dados?.trigger_config || {});
    autoRenderAcaoConfig(dados?.acao_config || {});

    autoFecharModal();
    document.getElementById('autoModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// === TRIGGER CONFIG DINÂMICO ===
function autoRenderTriggerConfig(savedConfig = {}) {
    const tipo = document.getElementById('autoTriggerTipo').value;
    const area = document.getElementById('autoTriggerConfigArea');
    let html = '';

    switch (tipo) {
        case 'chamado_resolvido_x_dias':
            html = `<div class="form-group"><label>Dias sem interação</label>
                <input type="number" id="trigDias" class="form-control" value="${savedConfig.dias || 7}" min="1" max="90"></div>`;
            break;
        case 'chamado_sem_resposta':
            html = `<div class="form-group"><label>Horas sem resposta</label>
                <input type="number" id="trigHoras" class="form-control" value="${savedConfig.horas || 24}" min="1" max="720"></div>`;
            break;
        case 'sla_prestes_vencer':
            html = `<div class="form-group"><label>Minutos restantes para SLA</label>
                <input type="number" id="trigMinutos" class="form-control" value="${savedConfig.minutos_restantes || 30}" min="5" max="480"></div>`;
            break;
        case 'contrato_vencendo':
            html = `<div class="form-group"><label>Dias para vencimento</label>
                <input type="number" id="trigDias" class="form-control" value="${savedConfig.dias || 30}" min="1" max="365"></div>`;
            break;
        case 'monitor_offline':
            html = `<div class="form-group"><label>Minutos offline</label>
                <input type="number" id="trigMinutos" class="form-control" value="${savedConfig.minutos_offline || 5}" min="1" max="60">
                <small style="color:var(--text-secondary)">Serviços monitorados (NOC) offline há mais de X minutos</small></div>`;
            break;
        case 'monitor_lento':
            html = `<div class="form-group"><label>Limite de resposta (ms)</label>
                <input type="number" id="trigLimiteMs" class="form-control" value="${savedConfig.limite_ms || 3000}" min="100" max="30000" step="100">
                <small style="color:var(--text-secondary)">Notificar quando o tempo de resposta exceder este valor</small></div>`;
            break;
        case 'dispositivo_offline':
            html = `<div class="form-group"><label>Minutos offline</label>
                <input type="number" id="trigMinutos" class="form-control" value="${savedConfig.minutos_offline || 5}" min="1" max="60">
                <small style="color:var(--text-secondary)">Dispositivos de rede (switches, APs, roteadores) offline há mais de X minutos</small></div>`;
            break;
        case 'novo_chamado':
            html = `<div class="form-row">
                <div class="form-group" style="flex:1"><label>Categoria (opcional)</label>
                    <select id="trigCategoria" class="form-control"><option value="">Qualquer</option>${autoCategorias.map(c => `<option value="${c.nome}" ${savedConfig.categoria_nome === c.nome ? 'selected' : ''}>${c.nome}</option>`).join('')}</select></div>
                <div class="form-group" style="flex:1"><label>Prioridade (opcional)</label>
                    <select id="trigPrioridade" class="form-control">
                        <option value="">Qualquer</option>
                        <option value="baixa" ${savedConfig.prioridade === 'baixa' ? 'selected' : ''}>Baixa</option>
                        <option value="media" ${savedConfig.prioridade === 'media' ? 'selected' : ''}>Média</option>
                        <option value="alta" ${savedConfig.prioridade === 'alta' ? 'selected' : ''}>Alta</option>
                        <option value="urgente" ${savedConfig.prioridade === 'urgente' ? 'selected' : ''}>Urgente</option>
                    </select></div></div>`;
            break;
        case 'agendado_diario':
            html = `<div class="form-group"><label>Hora de execução</label>
                <input type="time" id="trigHora" class="form-control" value="${savedConfig.hora_execucao || '08:00'}"></div>`;
            break;
        case 'agendado_semanal':
            html = `<div class="form-row">
                <div class="form-group" style="flex:1"><label>Dia da semana</label>
                    <select id="trigDiaSemana" class="form-control">
                        ${['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'].map((d, i) => `<option value="${i}" ${(savedConfig.dia_semana ?? 1) == i ? 'selected' : ''}>${d}</option>`).join('')}
                    </select></div>
                <div class="form-group" style="flex:1"><label>Hora</label>
                    <input type="time" id="trigHora" class="form-control" value="${savedConfig.hora_execucao || '08:00'}"></div></div>`;
            break;
        case 'agendado_mensal':
            html = `<div class="form-row">
                <div class="form-group" style="flex:1"><label>Dia do mês</label>
                    <input type="number" id="trigDiaMes" class="form-control" value="${savedConfig.dia_mes || 1}" min="1" max="28"></div>
                <div class="form-group" style="flex:1"><label>Hora</label>
                    <input type="time" id="trigHora" class="form-control" value="${savedConfig.hora_execucao || '08:00'}"></div></div>`;
            break;
    }

    area.innerHTML = html;
}

// === ACAO CONFIG DINÂMICO ===
function autoRenderAcaoConfig(savedConfig = {}) {
    const tipo = document.getElementById('autoAcaoTipo').value;
    const area = document.getElementById('autoAcaoConfigArea');
    let html = '';

    switch (tipo) {
        case 'atribuir_chamado':
            html = `<div class="form-group"><label>Técnico (vazio = round-robin automático)</label>
                <select id="acaoUsuario" class="form-control"><option value="">Automático (menos chamados)</option>
                ${autoTecnicos.map(t => `<option value="${t.id}" ${savedConfig.usuario_id == t.id ? 'selected' : ''}>${t.nome}</option>`).join('')}</select></div>`;
            break;
        case 'mudar_status_chamado':
            html = `<div class="form-group"><label>Novo Status</label>
                <select id="acaoStatus" class="form-control">
                    <option value="em_atendimento" ${savedConfig.status === 'em_atendimento' ? 'selected' : ''}>Em Atendimento</option>
                    <option value="resolvido" ${savedConfig.status === 'resolvido' ? 'selected' : ''}>Resolvido</option>
                    <option value="fechado" ${savedConfig.status === 'fechado' ? 'selected' : ''}>Fechado</option>
                </select></div>`;
            break;
        case 'mudar_prioridade_chamado':
            html = `<div class="form-group"><label>Nova Prioridade</label>
                <select id="acaoPrioridade" class="form-control">
                    <option value="alta" ${savedConfig.prioridade === 'alta' ? 'selected' : ''}>Alta</option>
                    <option value="urgente" ${savedConfig.prioridade === 'urgente' ? 'selected' : ''}>Urgente</option>
                </select></div>`;
            break;
        case 'escalar_chamado':
            html = `<div class="form-group"><label><input type="checkbox" id="acaoNotifGestor" ${savedConfig.notificar_gestor !== false ? 'checked' : ''}> Notificar gestores</label></div>
                <div class="form-group"><label>Mudar prioridade para</label>
                    <select id="acaoMudarPrio" class="form-control">
                        <option value="">Não mudar</option>
                        <option value="alta" ${savedConfig.mudar_prioridade === 'alta' ? 'selected' : ''}>Alta</option>
                        <option value="urgente" ${savedConfig.mudar_prioridade === 'urgente' ? 'selected' : ''}>Urgente</option>
                    </select></div>`;
            break;
        case 'enviar_notificacao':
            html = `<div class="form-group"><label>Título da notificação</label>
                <input type="text" id="acaoTitulo" class="form-control" value="${savedConfig.titulo || ''}" placeholder="Título da notificação"></div>
                <div class="form-group"><label>Destinatários</label>
                    <select id="acaoDestinatarios" class="form-control">
                        <option value="gestores" ${savedConfig.destinatarios === 'gestores' ? 'selected' : ''}>Gestores</option>
                        <option value="tecnicos" ${savedConfig.destinatarios === 'tecnicos' ? 'selected' : ''}>Técnicos</option>
                        <option value="todos" ${savedConfig.destinatarios === 'todos' ? 'selected' : ''}>Todos</option>
                    </select></div>`;
            break;
        case 'fechar_chamado':
            html = `<div class="form-group"><label>Mensagem de fechamento</label>
                <input type="text" id="acaoMensagem" class="form-control" value="${savedConfig.mensagem || 'Fechado automaticamente por automação.'}" placeholder="Mensagem..."></div>`;
            break;
        case 'gerar_relatorio':
            html = `<div class="form-group"><label>Tipo de relatório</label>
                <select id="acaoRelTipo" class="form-control">
                    <option value="chamados_pendentes" ${savedConfig.tipo === 'chamados_pendentes' ? 'selected' : ''}>Chamados Pendentes</option>
                </select></div>
                <div class="form-group"><label><input type="checkbox" id="acaoNotificar" ${savedConfig.notificar !== false ? 'checked' : ''}> Notificar gestores</label></div>`;
            break;
        case 'limpar_notificacoes_antigas':
            html = `<div class="form-group"><label>Remover lidas com mais de X dias</label>
                <input type="number" id="acaoDias" class="form-control" value="${savedConfig.dias || 90}" min="7" max="365"></div>`;
            break;
        case 'criar_tarefa':
            html = `<div class="form-group"><label>ID do Projeto</label>
                <input type="number" id="acaoProjetoId" class="form-control" value="${savedConfig.projeto_id || ''}" placeholder="ID do projeto para criar tarefas">
                <small style="color:var(--text-secondary)">As tarefas serão criadas neste projeto</small></div>
                <div class="form-group"><label>Responsável (opcional)</label>
                <select id="acaoResponsavelId" class="form-control"><option value="">Criador da automação</option>
                ${autoTecnicos.map(t => `<option value="${t.id}" ${savedConfig.responsavel_id == t.id ? 'selected' : ''}>${t.nome}</option>`).join('')}</select></div>`;
            break;
        case 'executar_webhook':
            html = `<div class="form-group"><label>URL do Webhook *</label>
                <input type="url" id="acaoWebhookUrl" class="form-control" value="${savedConfig.url || ''}" placeholder="https://exemplo.com/webhook"></div>
                <div class="form-group"><label>Método HTTP</label>
                <select id="acaoWebhookMetodo" class="form-control">
                    <option value="POST" ${savedConfig.metodo === 'POST' || !savedConfig.metodo ? 'selected' : ''}>POST</option>
                    <option value="GET" ${savedConfig.metodo === 'GET' ? 'selected' : ''}>GET</option>
                    <option value="PUT" ${savedConfig.metodo === 'PUT' ? 'selected' : ''}>PUT</option>
                </select></div>
                <small style="color:var(--text-secondary)">Cada item será enviado como JSON no corpo da requisição</small>`;
            break;
    }

    area.innerHTML = html;
}

// === COLETA CONFIG ===
function autoColetarTriggerConfig() {
    const tipo = document.getElementById('autoTriggerTipo').value;
    const config = {};

    switch (tipo) {
        case 'chamado_resolvido_x_dias':
        case 'contrato_vencendo':
            config.dias = parseInt(document.getElementById('trigDias')?.value || 7);
            break;
        case 'chamado_sem_resposta':
            config.horas = parseInt(document.getElementById('trigHoras')?.value || 24);
            break;
        case 'sla_prestes_vencer':
            config.minutos_restantes = parseInt(document.getElementById('trigMinutos')?.value || 30);
            break;
        case 'monitor_offline':
        case 'dispositivo_offline':
            config.minutos_offline = parseInt(document.getElementById('trigMinutos')?.value || 5);
            break;
        case 'monitor_lento':
            config.limite_ms = parseInt(document.getElementById('trigLimiteMs')?.value || 3000);
            break;
        case 'novo_chamado':
            if (document.getElementById('trigCategoria')?.value) config.categoria_nome = document.getElementById('trigCategoria').value;
            if (document.getElementById('trigPrioridade')?.value) config.prioridade = document.getElementById('trigPrioridade').value;
            break;
        case 'agendado_diario':
            config.hora_execucao = document.getElementById('trigHora')?.value || '08:00';
            break;
        case 'agendado_semanal':
            config.dia_semana = parseInt(document.getElementById('trigDiaSemana')?.value || 1);
            config.hora_execucao = document.getElementById('trigHora')?.value || '08:00';
            break;
        case 'agendado_mensal':
            config.dia_mes = parseInt(document.getElementById('trigDiaMes')?.value || 1);
            config.hora_execucao = document.getElementById('trigHora')?.value || '08:00';
            break;
    }
    return config;
}

function autoColetarAcaoConfig() {
    const tipo = document.getElementById('autoAcaoTipo').value;
    const config = {};

    switch (tipo) {
        case 'atribuir_chamado':
            const uid = document.getElementById('acaoUsuario')?.value;
            if (uid) config.usuario_id = parseInt(uid);
            break;
        case 'mudar_status_chamado':
            config.status = document.getElementById('acaoStatus')?.value || 'em_atendimento';
            break;
        case 'mudar_prioridade_chamado':
            config.prioridade = document.getElementById('acaoPrioridade')?.value || 'alta';
            break;
        case 'escalar_chamado':
            config.notificar_gestor = document.getElementById('acaoNotifGestor')?.checked ?? true;
            config.mudar_prioridade = document.getElementById('acaoMudarPrio')?.value || null;
            break;
        case 'enviar_notificacao':
            config.titulo = document.getElementById('acaoTitulo')?.value || 'Notificação automática';
            config.destinatarios = document.getElementById('acaoDestinatarios')?.value || 'gestores';
            break;
        case 'fechar_chamado':
            config.mensagem = document.getElementById('acaoMensagem')?.value || 'Fechado automaticamente.';
            break;
        case 'gerar_relatorio':
            config.tipo = document.getElementById('acaoRelTipo')?.value || 'chamados_pendentes';
            config.notificar = document.getElementById('acaoNotificar')?.checked ?? true;
            break;
        case 'limpar_notificacoes_antigas':
            config.dias = parseInt(document.getElementById('acaoDias')?.value || 90);
            break;
        case 'criar_tarefa':
            const projId = document.getElementById('acaoProjetoId')?.value;
            if (projId) config.projeto_id = parseInt(projId);
            const respId = document.getElementById('acaoResponsavelId')?.value;
            if (respId) config.responsavel_id = parseInt(respId);
            break;
        case 'executar_webhook':
            config.url = document.getElementById('acaoWebhookUrl')?.value || '';
            config.metodo = document.getElementById('acaoWebhookMetodo')?.value || 'POST';
            break;
    }
    return config;
}

// === SALVAR ===
async function autoSalvar() {
    const id = document.getElementById('autoId').value;
    const payload = {
        action: id ? 'atualizar' : 'criar',
        id: id ? parseInt(id) : undefined,
        nome: document.getElementById('autoNome').value,
        descricao: document.getElementById('autoDescricao').value,
        trigger_tipo: document.getElementById('autoTriggerTipo').value,
        trigger_config: autoColetarTriggerConfig(),
        acao_tipo: document.getElementById('autoAcaoTipo').value,
        acao_config: autoColetarAcaoConfig(),
        ativo: document.getElementById('autoAtivo').checked ? 1 : 0
    };

    if (!payload.nome || !payload.trigger_tipo || !payload.acao_tipo) {
        showToast('Preencha nome, trigger e ação', 'error');
        return;
    }

    try {
        const r = await fetch(AUTO_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const j = await r.json();
        if (j.success) {
            autoFecharModal();
            autoCarregarOverview();
            autoCarregarRegras();
            showToast(j.message, 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao salvar', 'error'); }
}

// === AÇÕES ===
async function autoToggle(id) {
    try {
        const r = await fetch(AUTO_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'toggle', id }) });
        const j = await r.json();
        if (j.success) { autoCarregarOverview(); showToast(j.message, 'success'); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

async function autoExecutar(id) {
    if (!confirm('Executar esta automação agora?')) return;
    try {
        const r = await fetch(AUTO_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'executar', id }) });
        const j = await r.json();
        if (j.success) {
            autoCarregarOverview();
            autoCarregarRegras();
            const res = j.resultado;
            showToast(`${res.automacao}: ${res.itens_afetados} item(ns) processado(s) em ${res.duracao_ms}ms`, res.success ? 'success' : 'error');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao executar', 'error'); }
}

async function autoExecutarTodas() {
    if (!confirm('Executar TODAS as automações ativas agora?')) return;
    try {
        const r = await fetch(AUTO_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'executar_todas' }) });
        const j = await r.json();
        if (j.success) {
            autoCarregarOverview();
            autoCarregarRegras();
            showToast(j.message, 'success');

            // Mostrar resultado no tab cron
            const resEl = document.getElementById('autoCronResult');
            if (resEl && j.resultados) {
                resEl.style.display = 'block';
                resEl.innerHTML = j.resultados.map(r => {
                    const icon = r.success ? '✅' : '❌';
                    return `<div>${icon} <strong>${r.automacao}</strong> — ${r.detalhes || r.erro || 'OK'} (${r.duracao_ms}ms)</div>`;
                }).join('') || '<div>Nenhuma automação elegível.</div>';
            }
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao executar', 'error'); }
}

async function autoEditar(id) {
    try {
        const r = await fetch(AUTO_API + '?action=detalhe&id=' + id);
        const j = await r.json();
        if (j.success) autoAbrirModal(j.data);
    } catch(e) { showToast('Erro ao carregar', 'error'); }
}

async function autoExcluir(id) {
    if (!confirm('Excluir esta automação e todo o histórico?')) return;
    try {
        const r = await fetch(AUTO_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'excluir', id }) });
        const j = await r.json();
        if (j.success) { autoCarregarOverview(); autoCarregarRegras(); showToast(j.message, 'success'); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

async function autoVerDetalhes(id) {
    try {
        const r = await fetch(AUTO_API + '?action=detalhe&id=' + id);
        const j = await r.json();
        if (!j.success) return;
        const a = j.data;
        const tc = a.trigger_config || {};
        const ac = a.acao_config || {};
        const cor = autoTriggerCor(a.trigger_tipo);

        let logsHtml = '';
        if (a.logs && a.logs.length) {
            logsHtml = '<h4 style="margin-top:16px"><i class="fas fa-history"></i> Últimas Execuções</h4><div class="auto-det-logs">' +
                a.logs.map(l => {
                    const dt = new Date(l.executado_em);
                    const det = l.detalhes ? JSON.parse(l.detalhes) : {};
                    const icon = l.status === 'sucesso' ? 'fa-check-circle' : 'fa-times-circle';
                    const cor2 = l.status === 'sucesso' ? 'var(--success)' : 'var(--danger)';
                    return `<div class="auto-det-log"><i class="fas ${icon}" style="color:${cor2}"></i>
                        <span>${dt.toLocaleDateString('pt-BR')} ${dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</span>
                        <span>${l.itens_afetados} itens • ${l.duracao_ms}ms</span>
                        <small>${det.msg || l.erro_mensagem || ''}</small></div>`;
                }).join('') + '</div>';
        }

        document.getElementById('autoDetalheBody').innerHTML = `
            <div class="auto-det-header">
                <div class="auto-regra-icon" style="background:${cor}20;color:${cor};width:56px;height:56px;font-size:24px">
                    <i class="fas ${autoTriggerIcon(a.trigger_tipo)}"></i>
                </div>
                <div>
                    <h3>${a.nome}</h3>
                    <span class="cmdb-badge ${a.ativo ? 'auto-st-ok' : 'auto-st-err'}">${a.ativo ? 'Ativa' : 'Inativa'}</span>
                </div>
            </div>
            ${a.descricao ? '<p style="margin:12px 0;color:var(--text-secondary)">' + a.descricao + '</p>' : ''}
            <div class="ct-det-grid">
                <div class="ct-det-item"><label>Gatilho</label><span><i class="fas ${autoTriggerIcon(a.trigger_tipo)}"></i> ${autoTriggerLabel(a.trigger_tipo)}</span></div>
                <div class="ct-det-item"><label>Ação</label><span><i class="fas ${autoAcaoIcon(a.acao_tipo)}"></i> ${autoAcaoLabel(a.acao_tipo)}</span></div>
                <div class="ct-det-item"><label>Configuração Trigger</label><span>${Object.entries(tc).map(([k,v]) => k+': '+v).join(', ') || '-'}</span></div>
                <div class="ct-det-item"><label>Configuração Ação</label><span>${Object.entries(ac).map(([k,v]) => k+': '+v).join(', ') || '-'}</span></div>
                <div class="ct-det-item"><label>Total Execuções</label><span>${a.total_execucoes}</span></div>
                <div class="ct-det-item"><label>Itens Afetados</label><span>${a.total_itens_afetados}</span></div>
                <div class="ct-det-item"><label>Última Execução</label><span>${a.ultima_execucao ? new Date(a.ultima_execucao).toLocaleString('pt-BR') : 'Nunca'}</span></div>
                <div class="ct-det-item"><label>Criado por</label><span>${a.criado_por_nome || '-'}</span></div>
            </div>
            ${logsHtml}
        `;
        autoFecharModal();
        document.getElementById('autoModalDetalhe').classList.add('active');
        document.body.style.overflow = 'hidden';
    } catch(e) { showToast('Erro ao carregar detalhes', 'error'); }
}

function autoCopiarCmd(text) {
    navigator.clipboard.writeText(text).then(() => showToast('Copiado!', 'success'));
}

// ============ CRON INSTALLER ============

let cronStatusAtual = null;

async function cronCarregarStatus() {
    const banner = document.getElementById('cronStatusBanner');
    try {
        const r = await fetch(AUTO_API + '?action=cron_status');
        const j = await r.json();
        if (!j.success) {
            banner.innerHTML = `<div class="cron-status-error"><i class="fas fa-exclamation-triangle"></i> Erro ao verificar status: ${j.error || 'Desconhecido'}</div>`;
            return;
        }

        cronStatusAtual = j.data;
        const d = j.data;

        // Atualizar banner
        if (d.instalado) {
            banner.innerHTML = `
                <div class="cron-status-ok">
                    <div class="cron-status-indicator cron-pulse"></div>
                    <div class="cron-status-text">
                        <strong><i class="fas fa-check-circle"></i> Cron Instalado e Ativo</strong>
                        <span>Executando a cada ${d.intervalo || '?'} minuto(s) via Windows Task Scheduler</span>
                    </div>
                    <div class="cron-status-meta">
                        ${d.proximo_execucao ? `<span><i class="fas fa-forward"></i> Próxima: ${d.proximo_execucao}</span>` : ''}
                        ${d.ultimo_execucao ? `<span><i class="fas fa-history"></i> Última: ${d.ultimo_execucao}</span>` : ''}
                    </div>
                </div>`;
            document.getElementById('cronBtnInstalar').style.display = 'none';
            document.getElementById('cronBtnDesinstalar').style.display = '';
            document.getElementById('cronBtnReinstalar').style.display = '';
            // Highlight intervalo atual
            if (d.intervalo) {
                document.getElementById('cronIntervalo').value = d.intervalo;
                cronSetIntervalo(d.intervalo, false);
            }
        } else {
            banner.innerHTML = `
                <div class="cron-status-off">
                    <div class="cron-status-indicator"></div>
                    <div class="cron-status-text">
                        <strong><i class="fas fa-moon"></i> Cron Não Instalado</strong>
                        <span>Configure abaixo para executar automações automaticamente</span>
                    </div>
                </div>`;
            document.getElementById('cronBtnInstalar').style.display = '';
            document.getElementById('cronBtnDesinstalar').style.display = 'none';
            document.getElementById('cronBtnReinstalar').style.display = 'none';
        }

        // Atualizar detalhes
        document.getElementById('cronDetStatus').innerHTML = d.instalado
            ? '<span class="cmdb-badge auto-st-ok">Instalado</span>'
            : '<span class="cmdb-badge auto-st-err">Não instalado</span>';
        document.getElementById('cronDetIntervalo').textContent = d.intervalo ? `${d.intervalo} minuto(s)` : '—';
        document.getElementById('cronDetProxima').textContent = d.proximo_execucao || '—';
        document.getElementById('cronDetUltima').textContent = d.ultimo_execucao || '—';
        document.getElementById('cronDetResultado').textContent = d.ultimo_resultado || '—';
        document.getElementById('cronDetPhp').textContent = d.php_path || '—';
        document.getElementById('cronDetScript').textContent = d.cron_path || '—';

        // Atualizar caminhos manuais
        if (d.php_path) document.getElementById('cronManualPhp').textContent = d.php_path;
        if (d.cron_path) document.getElementById('cronManualScript').textContent = d.cron_path;

        // Lock warning
        if (d.lock_exists) {
            banner.innerHTML += `<div class="cron-lock-warn"><i class="fas fa-lock"></i> Existe um lock file ativo — o cron pode estar executando neste momento.</div>`;
        }

    } catch (e) {
        console.error('Cron status error', e);
        banner.innerHTML = `<div class="cron-status-error"><i class="fas fa-exclamation-triangle"></i> Erro ao verificar status do cron</div>`;
    }
}

function cronSetIntervalo(min, updateInput = true) {
    document.querySelectorAll('.cron-intv-btn').forEach(b => {
        b.classList.toggle('active', parseInt(b.dataset.min) === min);
    });
    if (updateInput) {
        document.getElementById('cronIntervalo').value = min;
    }
}

// Sync input com botões
document.getElementById('cronIntervalo')?.addEventListener('change', function() {
    const val = parseInt(this.value) || 5;
    document.querySelectorAll('.cron-intv-btn').forEach(b => {
        b.classList.toggle('active', parseInt(b.dataset.min) === val);
    });
});

async function cronInstalar() {
    const intervalo = parseInt(document.getElementById('cronIntervalo').value) || 5;
    if (!confirm(`Instalar cron para executar a cada ${intervalo} minuto(s)?`)) return;

    const resultEl = document.getElementById('cronActionResult');
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<div class="cron-loading"><i class="fas fa-spinner fa-spin"></i> Instalando tarefa agendada...</div>';

    try {
        const r = await fetch(AUTO_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cron_instalar', intervalo })
        });
        const j = await r.json();

        if (j.success) {
            resultEl.innerHTML = `
                <div class="cron-result-ok">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>${j.message}</strong>
                        ${j.detalhes ? `<small>Tarefa: ${j.detalhes.task} | PHP: ${j.detalhes.php}</small>` : ''}
                    </div>
                </div>`;
            showToast(j.message, 'success');
            setTimeout(() => cronCarregarStatus(), 1000);
        } else {
            resultEl.innerHTML = `
                <div class="cron-result-err">
                    <i class="fas fa-times-circle"></i>
                    <div>
                        <strong>${j.error}</strong>
                        ${j.dica ? `<p style="margin:6px 0 0;font-size:12px;opacity:.8">${j.dica}</p>` : ''}
                        ${j.comando_manual ? `<div class="auto-cron-code" style="margin-top:8px"><code>${j.comando_manual}</code></div>
                        <button class="btn btn-sm btn-secondary" onclick="autoCopiarCmd('${j.comando_manual.replace(/'/g, "\\'")}')"><i class="fas fa-copy"></i> Copiar comando para executar manualmente</button>` : ''}
                    </div>
                </div>`;
            showToast(j.error, 'error');
        }
    } catch (e) {
        resultEl.innerHTML = '<div class="cron-result-err"><i class="fas fa-times-circle"></i> Erro de conexão</div>';
        showToast('Erro ao instalar cron', 'error');
    }
}

async function cronDesinstalar() {
    if (!confirm('Desinstalar o cron? As automações deixarão de executar automaticamente.')) return;

    const resultEl = document.getElementById('cronActionResult');
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<div class="cron-loading"><i class="fas fa-spinner fa-spin"></i> Desinstalando...</div>';

    try {
        const r = await fetch(AUTO_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cron_desinstalar' })
        });
        const j = await r.json();

        if (j.success) {
            resultEl.innerHTML = `<div class="cron-result-ok"><i class="fas fa-check-circle"></i> <strong>${j.message}</strong></div>`;
            showToast(j.message, 'success');
            setTimeout(() => cronCarregarStatus(), 1000);
        } else {
            resultEl.innerHTML = `<div class="cron-result-err"><i class="fas fa-times-circle"></i> <strong>${j.error}</strong></div>`;
            showToast(j.error, 'error');
        }
    } catch (e) {
        resultEl.innerHTML = '<div class="cron-result-err"><i class="fas fa-times-circle"></i> Erro de conexão</div>';
        showToast('Erro ao desinstalar', 'error');
    }
}

async function cronTestar() {
    const resultEl = document.getElementById('cronActionResult');
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<div class="cron-loading"><i class="fas fa-spinner fa-spin"></i> Executando cron via CLI...</div>';

    try {
        const r = await fetch(AUTO_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cron_testar' })
        });
        const j = await r.json();

        const icon = j.success ? 'fa-check-circle' : 'fa-times-circle';
        const cls = j.success ? 'cron-result-ok' : 'cron-result-err';
        resultEl.innerHTML = `
            <div class="${cls}">
                <i class="fas ${icon}"></i>
                <div>
                    <strong>${j.message}</strong>
                    ${j.output ? `<pre class="cron-test-output">${j.output}</pre>` : ''}
                </div>
            </div>`;

        showToast(j.message, j.success ? 'success' : 'error');
        if (j.success) {
            autoCarregarOverview();
            cronCarregarLogs();
        }
    } catch (e) {
        resultEl.innerHTML = '<div class="cron-result-err"><i class="fas fa-times-circle"></i> Erro de conexão</div>';
        showToast('Erro ao testar cron', 'error');
    }
}

async function cronCarregarLogs() {
    const viewer = document.getElementById('cronLogViewer');
    viewer.innerHTML = '<div class="cron-loading"><i class="fas fa-spinner fa-spin"></i> Carregando logs...</div>';

    try {
        const r = await fetch(AUTO_API + '?action=cron_logs');
        const j = await r.json();

        if (!j.data || !j.data.length) {
            viewer.innerHTML = '<p class="ct-empty">Nenhum log de cron encontrado neste mês</p>';
            return;
        }

        viewer.innerHTML = '<div class="cron-log-lines">' + j.data.map(line => {
            const isError = line.toLowerCase().includes('erro') || line.toLowerCase().includes('error');
            const icon = isError ? 'fa-times-circle' : 'fa-check-circle';
            const cls = isError ? 'cron-log-err' : 'cron-log-ok';
            return `<div class="cron-log-line ${cls}"><i class="fas ${icon}"></i> <span>${line}</span></div>`;
        }).join('') + '</div>';
    } catch (e) {
        viewer.innerHTML = '<div class="cron-result-err"><i class="fas fa-times-circle"></i> Erro ao carregar logs</div>';
    }
}

// Carregar status do cron quando a aba é aberta
const originalAutoTab = autoTab;
autoTab = function(btn) {
    originalAutoTab(btn);
    if (btn.dataset.tab === 'auto-cron') {
        cronCarregarStatus();
        cronCarregarLogs();
    }
};
</script>
