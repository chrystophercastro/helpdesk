<?php
/**
 * View: Timesheet - Registro de Horas
 */
$isGestor = in_array($_SESSION['usuario_tipo'], ['admin', 'gestor']);
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-clock"></i> Timesheet</h1>
        <p>Registro e acompanhamento de horas trabalhadas</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('timesheet_productivity')">
            <i class="fas fa-robot"></i> Produtividade IA
        </button>
        <button class="btn btn-success" id="tsTimerBtn" onclick="tsToggleTimer()">
            <i class="fas fa-play"></i> Iniciar Timer
        </button>
        <button class="btn btn-primary" onclick="tsAbrirModalRegistro()">
            <i class="fas fa-plus"></i> Registro Manual
        </button>
    </div>
</div>

<!-- Timer Ativo -->
<div class="ts-timer-bar" id="tsTimerBar" style="display:none">
    <div class="ts-timer-info">
        <i class="fas fa-circle ts-timer-pulse"></i>
        <span id="tsTimerDescricao">Timer ativo</span>
    </div>
    <div class="ts-timer-clock" id="tsTimerClock">00:00:00</div>
    <button class="btn btn-danger btn-sm" onclick="tsParar()"><i class="fas fa-stop"></i> Parar</button>
</div>

<!-- KPIs -->
<div class="ts-kpis" id="tsKpis"></div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="ts-semana" onclick="tsTab(this)">
        <i class="fas fa-calendar-week"></i> Semana
    </button>
    <button class="ad-tab" data-tab="ts-registros" onclick="tsTab(this)">
        <i class="fas fa-list"></i> Registros
    </button>
    <button class="ad-tab" data-tab="ts-relatorios" onclick="tsTab(this)">
        <i class="fas fa-chart-bar"></i> Relatórios
    </button>
    <?php if ($isGestor): ?>
    <button class="ad-tab" data-tab="ts-aprovacao" onclick="tsTab(this)">
        <i class="fas fa-check-double"></i> Aprovação
    </button>
    <?php endif; ?>
</div>

<!-- Tab: Semana -->
<div class="ad-tab-content active" id="ts-semana">
    <div class="ts-week-nav">
        <button class="btn btn-sm" onclick="tsNavSemana(-1)"><i class="fas fa-chevron-left"></i></button>
        <span id="tsWeekLabel">Semana Atual</span>
        <button class="btn btn-sm" onclick="tsNavSemana(1)"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="ts-week-grid" id="tsWeekGrid"></div>
    <div class="ts-week-total" id="tsWeekTotal"></div>
</div>

<!-- Tab: Registros -->
<div class="ad-tab-content" id="ts-registros">
    <div class="cmdb-toolbar">
        <input type="date" id="tsDataInicio" class="ct-select" onchange="tsCarregarRegistros()">
        <input type="date" id="tsDataFim" class="ct-select" onchange="tsCarregarRegistros()">
        <select id="tsFiltroTipo" class="ct-select" onchange="tsCarregarRegistros()">
            <option value="">Todos os Tipos</option>
            <option value="chamado">Chamado</option>
            <option value="projeto">Projeto</option>
            <option value="interno">Interno</option>
            <option value="reuniao">Reunião</option>
            <option value="treinamento">Treinamento</option>
            <option value="outro">Outro</option>
        </select>
        <select id="tsFiltroStatus" class="ct-select" onchange="tsCarregarRegistros()">
            <option value="">Todos Status</option>
            <option value="em_andamento">Em Andamento</option>
            <option value="concluido">Concluído</option>
            <option value="aprovado">Aprovado</option>
            <option value="rejeitado">Rejeitado</option>
        </select>
    </div>
    <div class="ct-table-wrap">
        <table class="ct-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Duração</th>
                    <th>Tipo</th>
                    <th>Referência</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tsRegistrosBody"></tbody>
        </table>
    </div>
</div>

<!-- Tab: Relatórios -->
<div class="ad-tab-content" id="ts-relatorios">
    <div class="cmdb-toolbar">
        <input type="date" id="tsRelInicio" class="ct-select">
        <input type="date" id="tsRelFim" class="ct-select">
        <button class="btn btn-primary" onclick="tsCarregarRelatorios()"><i class="fas fa-search"></i> Gerar</button>
    </div>
    <div class="ts-rel-grid">
        <div class="cmdb-ov-card">
            <h3><i class="fas fa-tags"></i> Horas por Tipo</h3>
            <div id="tsRelTipo"></div>
        </div>
        <?php if ($isGestor): ?>
        <div class="cmdb-ov-card">
            <h3><i class="fas fa-users"></i> Horas por Técnico</h3>
            <div id="tsRelUsuario"></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tab: Aprovação -->
<?php if ($isGestor): ?>
<div class="ad-tab-content" id="ts-aprovacao">
    <div class="ct-table-wrap">
        <table class="ct-table">
            <thead>
                <tr><th>Técnico</th><th>Data</th><th>Hora</th><th>Duração</th><th>Tipo</th><th>Descrição</th><th>Ações</th></tr>
            </thead>
            <tbody id="tsAprovacaoBody"></tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal Registro -->
<div class="modal-overlay" id="tsModalRegistro">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h2 id="tsModalTitulo">Novo Registro</h2>
            <button class="modal-close" onclick="tsFecharModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="tsRegId">
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Data</label>
                    <input type="date" id="tsRegData" class="form-control">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Tipo</label>
                    <select id="tsRegTipo" class="form-control" onchange="tsToggleRef()">
                        <option value="chamado">Chamado</option>
                        <option value="projeto">Projeto</option>
                        <option value="interno">Interno</option>
                        <option value="reuniao">Reunião</option>
                        <option value="treinamento">Treinamento</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Hora Início *</label>
                    <input type="time" id="tsRegInicio" class="form-control">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Hora Fim</label>
                    <input type="time" id="tsRegFim" class="form-control">
                </div>
            </div>
            <div class="form-group" id="tsRefChamado">
                <label>Chamado</label>
                <select id="tsRegChamado" class="form-control"><option value="">Nenhum</option></select>
            </div>
            <div class="form-group" id="tsRefProjeto" style="display:none">
                <label>Projeto</label>
                <select id="tsRegProjeto" class="form-control"><option value="">Nenhum</option></select>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea id="tsRegDescricao" class="form-control" rows="2" placeholder="O que foi feito..."></textarea>
            </div>
            <div class="form-group">
                <label>Custo/Hora (R$)</label>
                <input type="number" step="0.01" id="tsRegCusto" class="form-control" value="0">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="tsFecharModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="tsSalvarRegistro()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Timer -->
<div class="modal-overlay" id="tsModalTimer">
    <div class="modal" style="max-width:450px">
        <div class="modal-header">
            <h2><i class="fas fa-play-circle"></i> Iniciar Timer</h2>
            <button class="modal-close" onclick="tsFecharModal('tsModalTimer')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Tipo</label>
                <select id="tsTimerTipo" class="form-control" onchange="tsToggleTimerRef()">
                    <option value="chamado">Chamado</option>
                    <option value="projeto">Projeto</option>
                    <option value="interno">Interno</option>
                    <option value="reuniao">Reunião</option>
                    <option value="treinamento">Treinamento</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="form-group" id="tsTimerRefChamado">
                <label>Chamado</label>
                <select id="tsTimerChamado" class="form-control"><option value="">Nenhum</option></select>
            </div>
            <div class="form-group" id="tsTimerRefProjeto" style="display:none">
                <label>Projeto</label>
                <select id="tsTimerProjeto" class="form-control"><option value="">Nenhum</option></select>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <input type="text" id="tsTimerDescricaoInput" class="form-control" placeholder="O que está fazendo...">
            </div>
            <div class="form-group">
                <label>Custo/Hora (R$)</label>
                <input type="number" step="0.01" id="tsTimerCusto" class="form-control" value="0">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="tsFecharModal('tsModalTimer')">Cancelar</button>
            <button class="btn btn-success" onclick="tsIniciarTimer()"><i class="fas fa-play"></i> Iniciar</button>
        </div>
    </div>
</div>

<script>
const TS_API = '<?= BASE_URL ?>/api/timesheet.php';
const TS_IS_GESTOR = <?= $isGestor ? 'true' : 'false' ?>;
let tsTimerInterval = null;
let tsTimerData = null;
let tsWeekOffset = 0;

document.addEventListener('DOMContentLoaded', () => {
    // Set default dates
    const hoje = new Date().toISOString().split('T')[0];
    const mesInicio = hoje.substring(0,8) + '01';
    document.getElementById('tsDataInicio').value = mesInicio;
    document.getElementById('tsDataFim').value = hoje;
    document.getElementById('tsRelInicio').value = mesInicio;
    document.getElementById('tsRelFim').value = hoje;

    tsCarregarOverview();
    tsCarregarSemana();
    tsCarregarRegistros();
    tsCarregarSelectsRef();
    tsCheckTimer();
    if (TS_IS_GESTOR) tsCarregarAprovacao();
});

function tsTab(btn) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
}

function tsFecharModal(id) {
    if (id) { document.getElementById(id).classList.remove('active'); }
    else { ['tsModalRegistro','tsModalTimer'].forEach(mid => document.getElementById(mid)?.classList.remove('active')); }
    document.body.style.overflow = '';
}
// Click overlay to close
['tsModalRegistro','tsModalTimer'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) tsFecharModal(id);
    });
});
// ESC key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tsFecharModal();
});
function tsFormatMin(m) { if (!m) return '0h'; const h = Math.floor(m/60); const min = m%60; return h > 0 ? h+'h'+String(min).padStart(2,'0')+'m' : min+'m'; }
function tsFormatData(d) { if (!d) return '-'; const p = d.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; }
function tsTipoLabel(t) {
    const m = {chamado:'Chamado',projeto:'Projeto',interno:'Interno',reuniao:'Reunião',treinamento:'Treinamento',outro:'Outro'};
    return m[t]||t;
}
function tsTipoCor(t) {
    const m = {chamado:'#3B82F6',projeto:'#8B5CF6',interno:'#6B7280',reuniao:'#F59E0B',treinamento:'#10B981',outro:'#EC4899'};
    return m[t]||'#6B7280';
}
function tsStatusBadge(s) {
    const m = {em_andamento:'ts-st-and',concluido:'ts-st-conc',aprovado:'ts-st-aprov',rejeitado:'ts-st-rej'};
    const l = {em_andamento:'Em Andamento',concluido:'Concluído',aprovado:'Aprovado',rejeitado:'Rejeitado'};
    return `<span class="cmdb-badge ${m[s]||''}">${l[s]||s}</span>`;
}

// === OVERVIEW ===
async function tsCarregarOverview() {
    try {
        const r = await fetch(TS_API + '?action=overview');
        const j = await r.json();
        if (!j.success) return;
        const d = j.data;
        document.getElementById('tsKpis').innerHTML = `
            <div class="ts-kpi"><div class="ts-kpi-icon" style="background:var(--primary)"><i class="fas fa-clock"></i></div>
                <div class="ts-kpi-info"><span class="ts-kpi-val">${tsFormatMin(d.horas_hoje)}</span><span class="ts-kpi-label">Horas Hoje</span></div></div>
            <div class="ts-kpi"><div class="ts-kpi-icon" style="background:var(--success)"><i class="fas fa-calendar-check"></i></div>
                <div class="ts-kpi-info"><span class="ts-kpi-val">${tsFormatMin(d.horas_mes)}</span><span class="ts-kpi-label">Horas no Mês</span></div></div>
            <div class="ts-kpi"><div class="ts-kpi-icon" style="background:var(--warning)"><i class="fas fa-hourglass-half"></i></div>
                <div class="ts-kpi-info"><span class="ts-kpi-val">${d.pendentes_aprovacao}</span><span class="ts-kpi-label">Pendentes</span></div></div>
            <div class="ts-kpi"><div class="ts-kpi-icon" style="background:var(--purple)"><i class="fas fa-dollar-sign"></i></div>
                <div class="ts-kpi-info"><span class="ts-kpi-val">R$ ${parseFloat(d.custo_mes||0).toFixed(2)}</span><span class="ts-kpi-label">Custo Mensal</span></div></div>
        `;
    } catch(e) { console.error(e); }
}

// === TIMER ===
async function tsCheckTimer() {
    try {
        const r = await fetch(TS_API + '?action=timer_ativo');
        const j = await r.json();
        if (j.data) {
            tsTimerData = j.data;
            tsShowTimer();
        }
    } catch(e) {}
}

function tsShowTimer() {
    if (!tsTimerData) return;
    document.getElementById('tsTimerBar').style.display = 'flex';
    document.getElementById('tsTimerDescricao').textContent = tsTimerData.descricao || tsTipoLabel(tsTimerData.tipo);
    document.getElementById('tsTimerBtn').innerHTML = '<i class="fas fa-stop"></i> Parar Timer';
    document.getElementById('tsTimerBtn').className = 'btn btn-danger';

    if (tsTimerInterval) clearInterval(tsTimerInterval);
    const startTime = new Date(tsTimerData.data + 'T' + tsTimerData.hora_inicio);
    tsTimerInterval = setInterval(() => {
        const diff = Math.floor((Date.now() - startTime.getTime()) / 1000);
        const h = Math.floor(diff/3600), m = Math.floor((diff%3600)/60), s = diff%60;
        document.getElementById('tsTimerClock').textContent =
            String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }, 1000);
}

function tsHideTimer() {
    document.getElementById('tsTimerBar').style.display = 'none';
    document.getElementById('tsTimerBtn').innerHTML = '<i class="fas fa-play"></i> Iniciar Timer';
    document.getElementById('tsTimerBtn').className = 'btn btn-success';
    if (tsTimerInterval) { clearInterval(tsTimerInterval); tsTimerInterval = null; }
    tsTimerData = null;
}

function tsToggleTimer() {
    if (tsTimerData) {
        tsParar();
    } else {
        tsFecharModal();
        document.getElementById('tsModalTimer').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

async function tsIniciarTimer() {
    const payload = {
        action: 'iniciar_timer',
        tipo: document.getElementById('tsTimerTipo').value,
        chamado_id: document.getElementById('tsTimerChamado')?.value || null,
        projeto_id: document.getElementById('tsTimerProjeto')?.value || null,
        descricao: document.getElementById('tsTimerDescricaoInput').value,
        custo_hora: parseFloat(document.getElementById('tsTimerCusto').value || 0)
    };
    try {
        const r = await fetch(TS_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        const j = await r.json();
        if (j.success) {
            document.getElementById('tsModalTimer').classList.remove('active');
            tsCheckTimer();
            tsCarregarOverview();
            showToast('Timer iniciado!', 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

async function tsParar() {
    if (!tsTimerData) return;
    try {
        const r = await fetch(TS_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'parar_timer', id: tsTimerData.id})});
        const j = await r.json();
        if (j.success) {
            tsHideTimer();
            tsCarregarOverview();
            tsCarregarRegistros();
            tsCarregarSemana();
            showToast('Timer parado!', 'success');
        }
    } catch(e) { showToast('Erro', 'error'); }
}

// === SEMANA ===
async function tsCarregarSemana() {
    const ref = new Date();
    ref.setDate(ref.getDate() + tsWeekOffset * 7);
    const dataRef = ref.toISOString().split('T')[0];

    try {
        const r = await fetch(TS_API + '?action=resumo_semanal&data=' + dataRef);
        const j = await r.json();
        if (!j.success) return;
        const d = j.data;

        document.getElementById('tsWeekLabel').textContent =
            tsFormatData(d.inicio) + ' — ' + tsFormatData(d.fim);

        const diasSemana = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
        const maxMin = Math.max(...d.dias.map(dia => dia.total_minutos), 480);
        document.getElementById('tsWeekGrid').innerHTML = d.dias.map((dia, i) => {
            const pct = maxMin ? ((dia.total_minutos / maxMin) * 100) : 0;
            const horas = (dia.total_minutos / 60).toFixed(1);
            const isHoje = dia.data === new Date().toISOString().split('T')[0];
            return `<div class="ts-day ${isHoje ? 'ts-day-hoje' : ''} ${dia.total_minutos === 0 ? 'ts-day-vazio' : ''}">
                <div class="ts-day-label">${diasSemana[i]}<br><small>${tsFormatData(dia.data)}</small></div>
                <div class="ts-day-bar-wrap"><div class="ts-day-bar" style="height:${pct}%"></div></div>
                <div class="ts-day-val">${horas}h</div>
                <small class="ts-day-reg">${dia.registros} reg.</small>
            </div>`;
        }).join('');

        document.getElementById('tsWeekTotal').innerHTML = `
            <strong>Total da Semana:</strong> ${tsFormatMin(d.total_minutos)}
            <span style="margin-left:16px;color:var(--text-secondary)">(${(d.total_minutos/60).toFixed(1)} horas)</span>
        `;
    } catch(e) { console.error(e); }
}

function tsNavSemana(dir) { tsWeekOffset += dir; tsCarregarSemana(); }

// === REGISTROS ===
async function tsCarregarRegistros() {
    const inicio = document.getElementById('tsDataInicio').value;
    const fim = document.getElementById('tsDataFim').value;
    const tipo = document.getElementById('tsFiltroTipo').value;
    const status = document.getElementById('tsFiltroStatus').value;

    let url = TS_API + '?action=registros';
    if (inicio) url += '&data_inicio=' + inicio;
    if (fim) url += '&data_fim=' + fim;
    if (tipo) url += '&tipo=' + tipo;
    if (status) url += '&status=' + status;

    try {
        const r = await fetch(url);
        const j = await r.json();
        const body = document.getElementById('tsRegistrosBody');
        if (!j.data || !j.data.length) {
            body.innerHTML = '<tr><td colspan="8" class="ct-empty">Nenhum registro encontrado</td></tr>';
            return;
        }
        body.innerHTML = j.data.map(reg => {
            const ref = reg.chamado_titulo ? '🎫 ' + reg.chamado_titulo :
                        reg.projeto_nome ? '📁 ' + reg.projeto_nome : '-';
            return `<tr>
                <td>${tsFormatData(reg.data)}</td>
                <td>${reg.hora_inicio?.substring(0,5) || ''} — ${reg.hora_fim?.substring(0,5) || '...'}</td>
                <td><strong>${tsFormatMin(reg.duracao_minutos)}</strong></td>
                <td><span class="ct-tipo-tag" style="background:${tsTipoCor(reg.tipo)}">${tsTipoLabel(reg.tipo)}</span></td>
                <td>${ref}</td>
                <td>${reg.descricao ? reg.descricao.substring(0,60) : '-'}</td>
                <td>${tsStatusBadge(reg.status)}</td>
                <td class="ct-actions">
                    <button class="btn btn-sm" onclick="tsEditarRegistro(${reg.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="tsExcluirRegistro(${reg.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

// === SELECTS ===
async function tsCarregarSelectsRef() {
    // Chamados abertos
    try {
        const selIds = ['tsRegChamado','tsTimerChamado'];
        // Simple approach - fetch from chamados table
        selIds.forEach(id => {
            const sel = document.getElementById(id);
            if (sel) sel.innerHTML = '<option value="">Nenhum</option>';
        });

        const selProjIds = ['tsRegProjeto','tsTimerProjeto'];
        selProjIds.forEach(id => {
            const sel = document.getElementById(id);
            if (sel) sel.innerHTML = '<option value="">Nenhum</option>';
        });
    } catch(e) {}
}

function tsToggleRef() {
    const tipo = document.getElementById('tsRegTipo').value;
    document.getElementById('tsRefChamado').style.display = tipo === 'chamado' ? '' : 'none';
    document.getElementById('tsRefProjeto').style.display = tipo === 'projeto' ? '' : 'none';
}
function tsToggleTimerRef() {
    const tipo = document.getElementById('tsTimerTipo').value;
    document.getElementById('tsTimerRefChamado').style.display = tipo === 'chamado' ? '' : 'none';
    document.getElementById('tsTimerRefProjeto').style.display = tipo === 'projeto' ? '' : 'none';
}

// === MODAL ===
function tsAbrirModalRegistro(dados = null) {
    document.getElementById('tsModalTitulo').textContent = dados ? 'Editar Registro' : 'Novo Registro';
    document.getElementById('tsRegId').value = dados?.id || '';
    document.getElementById('tsRegData').value = dados?.data || new Date().toISOString().split('T')[0];
    document.getElementById('tsRegTipo').value = dados?.tipo || 'chamado';
    document.getElementById('tsRegInicio').value = dados?.hora_inicio?.substring(0,5) || '';
    document.getElementById('tsRegFim').value = dados?.hora_fim?.substring(0,5) || '';
    document.getElementById('tsRegChamado').value = dados?.chamado_id || '';
    document.getElementById('tsRegProjeto').value = dados?.projeto_id || '';
    document.getElementById('tsRegDescricao').value = dados?.descricao || '';
    document.getElementById('tsRegCusto').value = dados?.custo_hora || 0;
    tsToggleRef();
    tsFecharModal();
    document.getElementById('tsModalRegistro').classList.add('active');
    document.body.style.overflow = 'hidden';
}

async function tsSalvarRegistro() {
    const id = document.getElementById('tsRegId').value;
    const tipo = document.getElementById('tsRegTipo').value;
    const payload = {
        action: id ? 'atualizar_registro' : 'criar_registro',
        id: id ? parseInt(id) : undefined,
        data: document.getElementById('tsRegData').value,
        hora_inicio: document.getElementById('tsRegInicio').value,
        hora_fim: document.getElementById('tsRegFim').value || null,
        tipo: tipo,
        chamado_id: tipo === 'chamado' ? (document.getElementById('tsRegChamado').value || null) : null,
        projeto_id: tipo === 'projeto' ? (document.getElementById('tsRegProjeto').value || null) : null,
        descricao: document.getElementById('tsRegDescricao').value,
        custo_hora: parseFloat(document.getElementById('tsRegCusto').value || 0)
    };
    try {
        const r = await fetch(TS_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        const j = await r.json();
        if (j.success) {
            tsFecharModal();
            tsCarregarRegistros();
            tsCarregarSemana();
            tsCarregarOverview();
            showToast(j.message, 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao salvar', 'error'); }
}

async function tsEditarRegistro(id) {
    try {
        const r = await fetch(TS_API + '?action=registro&id=' + id);
        const j = await r.json();
        if (j.success) tsAbrirModalRegistro(j.data);
    } catch(e) { showToast('Erro', 'error'); }
}

async function tsExcluirRegistro(id) {
    if (!confirm('Excluir este registro?')) return;
    try {
        const r = await fetch(TS_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'excluir_registro', id})});
        const j = await r.json();
        if (j.success) { tsCarregarRegistros(); tsCarregarSemana(); tsCarregarOverview(); showToast(j.message, 'success'); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

// === RELATÓRIOS ===
async function tsCarregarRelatorios() {
    const inicio = document.getElementById('tsRelInicio').value;
    const fim = document.getElementById('tsRelFim').value;

    // Por tipo
    try {
        const r = await fetch(TS_API + '?action=relatorio_tipo&data_inicio=' + inicio + '&data_fim=' + fim);
        const j = await r.json();
        const el = document.getElementById('tsRelTipo');
        if (j.data && j.data.length) {
            const total = j.data.reduce((s,d) => s + parseInt(d.total_minutos||0), 0);
            el.innerHTML = '<div class="cmdb-bar-list">' + j.data.map(d => {
                const pct = total ? ((d.total_minutos/total)*100).toFixed(1) : 0;
                return `<div class="cmdb-bar-row">
                    <span class="cmdb-bar-label">${tsTipoLabel(d.tipo)}</span>
                    <div class="cmdb-bar-wrap"><div class="cmdb-bar" style="width:${pct}%;background:${tsTipoCor(d.tipo)}"></div></div>
                    <span class="cmdb-bar-val">${tsFormatMin(d.total_minutos)}</span>
                </div>`;
            }).join('') + '</div>';
        } else el.innerHTML = '<p class="ct-empty">Sem dados</p>';
    } catch(e) {}

    // Por usuário (gestor)
    if (TS_IS_GESTOR) {
        try {
            const r = await fetch(TS_API + '?action=relatorio_usuario&data_inicio=' + inicio + '&data_fim=' + fim);
            const j = await r.json();
            const el = document.getElementById('tsRelUsuario');
            if (j.data && j.data.length) {
                const maxMin = Math.max(...j.data.map(d => parseInt(d.total_minutos||0)));
                el.innerHTML = '<div class="cmdb-bar-list">' + j.data.map(d => {
                    const pct = maxMin ? ((d.total_minutos/maxMin)*100) : 0;
                    return `<div class="cmdb-bar-row">
                        <span class="cmdb-bar-label">${d.usuario}</span>
                        <div class="cmdb-bar-wrap"><div class="cmdb-bar" style="width:${pct}%;background:var(--primary)"></div></div>
                        <span class="cmdb-bar-val">${tsFormatMin(d.total_minutos)}</span>
                    </div>`;
                }).join('') + '</div>';
            } else el.innerHTML = '<p class="ct-empty">Sem dados</p>';
        } catch(e) {}
    }
}

// === APROVAÇÃO ===
async function tsCarregarAprovacao() {
    try {
        const r = await fetch(TS_API + '?action=registros&status=concluido');
        const j = await r.json();
        const body = document.getElementById('tsAprovacaoBody');
        if (!body) return;
        if (!j.data || !j.data.length) {
            body.innerHTML = '<tr><td colspan="7" class="ct-empty">Nenhum registro pendente de aprovação</td></tr>';
            return;
        }
        body.innerHTML = j.data.map(reg => `<tr>
            <td><strong>${reg.usuario_nome}</strong></td>
            <td>${tsFormatData(reg.data)}</td>
            <td>${reg.hora_inicio?.substring(0,5)||''} — ${reg.hora_fim?.substring(0,5)||'...'}</td>
            <td>${tsFormatMin(reg.duracao_minutos)}</td>
            <td><span class="ct-tipo-tag" style="background:${tsTipoCor(reg.tipo)}">${tsTipoLabel(reg.tipo)}</span></td>
            <td>${reg.descricao||'-'}</td>
            <td class="ct-actions">
                <button class="btn btn-sm btn-success" onclick="tsAprovar(${reg.id})"><i class="fas fa-check"></i></button>
                <button class="btn btn-sm btn-danger" onclick="tsRejeitar(${reg.id})"><i class="fas fa-times"></i></button>
            </td>
        </tr>`).join('');
    } catch(e) {}
}

async function tsAprovar(id) {
    try {
        const r = await fetch(TS_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'aprovar', id})});
        const j = await r.json();
        if (j.success) { tsCarregarAprovacao(); tsCarregarOverview(); showToast('Aprovado!', 'success'); }
    } catch(e) { showToast('Erro', 'error'); }
}

async function tsRejeitar(id) {
    if (!confirm('Rejeitar este registro?')) return;
    try {
        const r = await fetch(TS_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'rejeitar', id})});
        const j = await r.json();
        if (j.success) { tsCarregarAprovacao(); tsCarregarOverview(); showToast('Rejeitado!', 'success'); }
    } catch(e) { showToast('Erro', 'error'); }
}
</script>
