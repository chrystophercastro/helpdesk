<?php
/**
 * View: Calendário
 * Calendário interativo com eventos, reuniões, lembretes e integrações
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-calendar-alt" style="margin-right:8px;color:var(--primary)"></i> Calendário</h1>
        <p class="page-subtitle">Eventos, reuniões, lembretes e prazos</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-sm" onclick="calImportarPrazos()">
            <i class="fas fa-download"></i> Importar Prazos
        </button>
        <button class="btn btn-primary btn-sm" onclick="calAbrirModal()">
            <i class="fas fa-plus"></i> Novo Evento
        </button>
    </div>
</div>

<!-- Top Bar: Navigation + View Switch -->
<div class="cal-topbar">
    <div class="cal-nav">
        <button class="btn btn-sm" onclick="calNav(-1)"><i class="fas fa-chevron-left"></i></button>
        <button class="btn btn-sm" onclick="calHoje()">Hoje</button>
        <button class="btn btn-sm" onclick="calNav(1)"><i class="fas fa-chevron-right"></i></button>
        <h2 class="cal-title" id="calTitle">...</h2>
    </div>
    <div class="cal-views">
        <button class="cal-view-btn active" data-view="month" onclick="calSetView('month')">Mês</button>
        <button class="cal-view-btn" data-view="week" onclick="calSetView('week')">Semana</button>
        <button class="cal-view-btn" data-view="day" onclick="calSetView('day')">Dia</button>
        <button class="cal-view-btn" data-view="list" onclick="calSetView('list')">Lista</button>
    </div>
</div>

<!-- Calendar Container -->
<div class="cal-container" id="calContainer">
    <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
</div>

<!-- Sidebar: Próximos Eventos -->
<div class="cal-sidebar" id="calSidebar" style="display:none">
    <h3><i class="fas fa-clock"></i> Próximos Eventos</h3>
    <div id="calProximos"></div>
</div>

<!-- Modal: Evento -->
<div class="modal-overlay" id="calModal" style="display:none">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h2 id="calModalTitle"><i class="fas fa-calendar-plus"></i> Novo Evento</h2>
            <button class="modal-close" onclick="document.getElementById('calModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="calEditId">
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" id="calTitulo" class="form-control" placeholder="Título do evento">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select id="calTipo" class="form-control">
                        <option value="evento">📅 Evento</option>
                        <option value="reuniao">🤝 Reunião</option>
                        <option value="lembrete">⏰ Lembrete</option>
                        <option value="manutencao">🔧 Manutenção</option>
                        <option value="deploy">🚀 Deploy</option>
                        <option value="plantao">🏥 Plantão</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Cor</label>
                    <input type="color" id="calCor" class="form-control" value="#3B82F6" style="height:38px;padding:4px">
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer">
                <input type="checkbox" id="calDiaInteiro" onchange="calToggleDiaInteiro()"> Dia inteiro
            </label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Início *</label>
                    <input type="datetime-local" id="calInicio" class="form-control">
                </div>
                <div class="form-group" id="calFimGroup">
                    <label class="form-label">Fim</label>
                    <input type="datetime-local" id="calFim" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Local</label>
                <input type="text" id="calLocal" class="form-control" placeholder="Sala, link ou endereço">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea id="calDescricao" class="form-control" rows="3" placeholder="Detalhes do evento"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Recorrência</label>
                    <select id="calRecorrencia" class="form-control">
                        <option value="nenhuma">Nenhuma</option>
                        <option value="diaria">Diária</option>
                        <option value="semanal">Semanal</option>
                        <option value="mensal">Mensal</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Lembrete (min antes)</label>
                    <select id="calNotificar" class="form-control">
                        <option value="0">Sem lembrete</option>
                        <option value="5">5 minutos</option>
                        <option value="15" selected>15 minutos</option>
                        <option value="30">30 minutos</option>
                        <option value="60">1 hora</option>
                        <option value="1440">1 dia</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button class="btn btn-primary" onclick="calSalvar()" style="flex:1">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button class="btn btn-sm" id="calBtnExcluir" onclick="calExcluir()" style="display:none;color:var(--danger)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CAL_API = '<?= BASE_URL ?>/api/calendario.php';

let calView = 'month';
let calDate = new Date();
let calEventos = [];

const CAL_TIPOS = {
    evento: {icon:'📅', color:'#3B82F6'},
    reuniao: {icon:'🤝', color:'#8B5CF6'},
    lembrete: {icon:'⏰', color:'#F59E0B'},
    manutencao: {icon:'🔧', color:'#6B7280'},
    deploy: {icon:'🚀', color:'#10B981'},
    plantao: {icon:'🏥', color:'#EF4444'}
};

document.addEventListener('DOMContentLoaded', () => calRender());

function calSetView(view) {
    calView = view;
    document.querySelectorAll('.cal-view-btn').forEach(b => b.classList.toggle('active', b.dataset.view === view));
    calRender();
}

function calNav(dir) {
    if (calView === 'month') calDate.setMonth(calDate.getMonth() + dir);
    else if (calView === 'week') calDate.setDate(calDate.getDate() + (7 * dir));
    else calDate.setDate(calDate.getDate() + dir);
    calRender();
}

function calHoje() {
    calDate = new Date();
    calRender();
}

async function calRender() {
    const container = document.getElementById('calContainer');
    await calFetchEventos();
    calUpdateTitle();
    if (calView === 'month') calRenderMonth(container);
    else if (calView === 'week') calRenderWeek(container);
    else if (calView === 'day') calRenderDay(container);
    else calRenderList(container);
}

async function calFetchEventos() {
    let inicio, fim;
    if (calView === 'month') {
        const y = calDate.getFullYear(), m = calDate.getMonth();
        const first = new Date(y, m, 1);
        const last = new Date(y, m + 1, 0);
        const startDay = first.getDay() || 7;
        inicio = new Date(first); inicio.setDate(inicio.getDate() - startDay + 1);
        fim = new Date(last); fim.setDate(fim.getDate() + (7 - (last.getDay() || 7)));
    } else if (calView === 'week') {
        inicio = new Date(calDate);
        const day = inicio.getDay() || 7;
        inicio.setDate(inicio.getDate() - day + 1);
        fim = new Date(inicio); fim.setDate(fim.getDate() + 6);
    } else {
        inicio = new Date(calDate);
        fim = new Date(calDate);
    }
    inicio.setHours(0,0,0,0);
    fim.setHours(23,59,59,999);

    try {
        const r = await fetch(`${CAL_API}?action=eventos&inicio=${calFormatDT(inicio)}&fim=${calFormatDT(fim)}`);
        const data = await r.json();
        calEventos = data.success ? data.data : [];
    } catch(e) { calEventos = []; }
}

function calUpdateTitle() {
    const meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    const dias = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
    const el = document.getElementById('calTitle');
    if (calView === 'month') el.textContent = meses[calDate.getMonth()] + ' ' + calDate.getFullYear();
    else if (calView === 'week') {
        const start = new Date(calDate);
        const day = start.getDay() || 7;
        start.setDate(start.getDate() - day + 1);
        const end = new Date(start); end.setDate(end.getDate() + 6);
        el.textContent = `${start.getDate()} - ${end.getDate()} ${meses[end.getMonth()]} ${end.getFullYear()}`;
    }
    else if (calView === 'day') el.textContent = `${dias[calDate.getDay()]}, ${calDate.getDate()} de ${meses[calDate.getMonth()]}`;
    else el.textContent = meses[calDate.getMonth()] + ' ' + calDate.getFullYear();
}

// ==================== MONTH VIEW ====================
function calRenderMonth(container) {
    const y = calDate.getFullYear(), m = calDate.getMonth();
    const first = new Date(y, m, 1);
    const last = new Date(y, m + 1, 0);
    const startDay = (first.getDay() || 7) - 1;
    const today = new Date(); today.setHours(0,0,0,0);

    let html = '<div class="cal-month-grid">';
    html += '<div class="cal-dow">Seg</div><div class="cal-dow">Ter</div><div class="cal-dow">Qua</div><div class="cal-dow">Qui</div><div class="cal-dow">Sex</div><div class="cal-dow">Sáb</div><div class="cal-dow">Dom</div>';

    const start = new Date(first); start.setDate(start.getDate() - startDay);
    for (let i = 0; i < 42; i++) {
        const d = new Date(start); d.setDate(d.getDate() + i);
        const isMonth = d.getMonth() === m;
        const isToday = d.getTime() === today.getTime();
        const dateStr = calFormatDate(d);
        const dayEvents = calEventos.filter(e => calFormatDate(new Date(e.data_inicio)) === dateStr);

        html += `<div class="cal-day${isMonth ? '' : ' other'}${isToday ? ' today' : ''}" onclick="calClickDay('${dateStr}')">`;
        html += `<span class="cal-day-num${isToday ? ' today' : ''}">${d.getDate()}</span>`;
        dayEvents.slice(0, 3).forEach(e => {
            const t = CAL_TIPOS[e.tipo] || CAL_TIPOS.evento;
            html += `<div class="cal-event" style="background:${e.cor || t.color}20;color:${e.cor || t.color};border-left:3px solid ${e.cor || t.color}" onclick="event.stopPropagation();calVerEvento(${e.id})">${calEscape(e.titulo)}</div>`;
        });
        if (dayEvents.length > 3) html += `<span class="cal-more">+${dayEvents.length - 3} mais</span>`;
        html += '</div>';
    }
    html += '</div>';
    container.innerHTML = html;
}

// ==================== WEEK VIEW ====================
function calRenderWeek(container) {
    const start = new Date(calDate);
    const day = start.getDay() || 7;
    start.setDate(start.getDate() - day + 1);
    const today = new Date(); today.setHours(0,0,0,0);
    const diasSemana = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];

    let html = '<div class="cal-week">';
    // Header
    html += '<div class="cal-week-header"><div class="cal-week-time-col"></div>';
    for (let i = 0; i < 7; i++) {
        const d = new Date(start); d.setDate(d.getDate() + i);
        const isToday = d.getTime() === today.getTime();
        html += `<div class="cal-week-day-header${isToday ? ' today' : ''}">${diasSemana[i]}<br><span class="cal-week-day-num">${d.getDate()}</span></div>`;
    }
    html += '</div>';

    // Hours grid
    html += '<div class="cal-week-body">';
    for (let h = 7; h < 22; h++) {
        html += `<div class="cal-week-row"><div class="cal-week-time">${String(h).padStart(2,'0')}:00</div>`;
        for (let i = 0; i < 7; i++) {
            const d = new Date(start); d.setDate(d.getDate() + i);
            const dateStr = calFormatDate(d);
            const hourEvents = calEventos.filter(e => {
                const eDate = new Date(e.data_inicio);
                return calFormatDate(eDate) === dateStr && eDate.getHours() === h;
            });
            html += `<div class="cal-week-cell" onclick="calClickDay('${dateStr}', ${h})">`;
            hourEvents.forEach(e => {
                const t = CAL_TIPOS[e.tipo] || CAL_TIPOS.evento;
                html += `<div class="cal-week-event" style="background:${e.cor || t.color}" onclick="event.stopPropagation();calVerEvento(${e.id})">${calEscape(e.titulo)}</div>`;
            });
            html += '</div>';
        }
        html += '</div>';
    }
    html += '</div></div>';
    container.innerHTML = html;
}

// ==================== DAY VIEW ====================
function calRenderDay(container) {
    const dateStr = calFormatDate(calDate);
    const dayEvents = calEventos.filter(e => calFormatDate(new Date(e.data_inicio)) === dateStr);

    let html = '<div class="cal-day-view">';
    for (let h = 7; h < 22; h++) {
        const hourEvents = dayEvents.filter(e => new Date(e.data_inicio).getHours() === h);
        html += `<div class="cal-day-row" onclick="calClickDay('${dateStr}', ${h})">`;
        html += `<div class="cal-day-time">${String(h).padStart(2,'0')}:00</div>`;
        html += '<div class="cal-day-content">';
        hourEvents.forEach(e => {
            const t = CAL_TIPOS[e.tipo] || CAL_TIPOS.evento;
            html += `<div class="cal-day-event" style="background:${e.cor || t.color}15;border-left:4px solid ${e.cor || t.color}" onclick="event.stopPropagation();calVerEvento(${e.id})">
                <strong>${calEscape(e.titulo)}</strong>
                ${e.local ? '<br><small><i class="fas fa-map-marker-alt"></i> '+calEscape(e.local)+'</small>' : ''}
            </div>`;
        });
        html += '</div></div>';
    }
    // All-day events
    const allDay = dayEvents.filter(e => parseInt(e.dia_inteiro));
    if (allDay.length) {
        html = `<div class="cal-allday"><strong>Dia Inteiro:</strong> ${allDay.map(e => `<span class="cal-allday-tag" style="background:${e.cor || '#3B82F6'}" onclick="calVerEvento(${e.id})">${calEscape(e.titulo)}</span>`).join('')}</div>` + html;
    }
    html += '</div>';
    container.innerHTML = html;
}

// ==================== LIST VIEW ====================
function calRenderList(container) {
    if (!calEventos.length) {
        container.innerHTML = '<div class="gh-empty-state"><i class="fas fa-calendar-times" style="font-size:48px;color:var(--gray-400)"></i><h3>Nenhum evento</h3><p>Nenhum evento neste período</p></div>';
        return;
    }
    // Group by date
    const grouped = {};
    calEventos.forEach(e => {
        const d = calFormatDate(new Date(e.data_inicio));
        if (!grouped[d]) grouped[d] = [];
        grouped[d].push(e);
    });

    let html = '<div class="cal-list">';
    for (const [date, events] of Object.entries(grouped)) {
        const dt = new Date(date + 'T00:00:00');
        const dias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
        html += `<div class="cal-list-date"><span>${dias[dt.getDay()]}, ${dt.getDate()}/${dt.getMonth()+1}</span></div>`;
        events.forEach(e => {
            const t = CAL_TIPOS[e.tipo] || CAL_TIPOS.evento;
            const hora = e.dia_inteiro == 1 ? 'Dia inteiro' : new Date(e.data_inicio).toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
            html += `<div class="cal-list-item" onclick="calVerEvento(${e.id})">
                <div class="cal-list-color" style="background:${e.cor || t.color}"></div>
                <div class="cal-list-info">
                    <strong>${calEscape(e.titulo)}</strong>
                    <span>${hora}${e.local ? ' · '+calEscape(e.local) : ''}</span>
                </div>
                <span class="cal-list-type">${t.icon} ${e.tipo}</span>
            </div>`;
        });
    }
    html += '</div>';
    container.innerHTML = html;
}

// ==================== CRUD ====================
function calClickDay(dateStr, hour) {
    calAbrirModal(null, dateStr, hour);
}

function calAbrirModal(evento = null, dateStr = null, hour = null) {
    document.getElementById('calModal').style.display = 'flex';
    document.getElementById('calEditId').value = evento?.id || '';
    document.getElementById('calModalTitle').innerHTML = evento ? '<i class="fas fa-edit"></i> Editar Evento' : '<i class="fas fa-calendar-plus"></i> Novo Evento';
    document.getElementById('calTitulo').value = evento?.titulo || '';
    document.getElementById('calTipo').value = evento?.tipo || 'evento';
    document.getElementById('calCor').value = evento?.cor || '#3B82F6';
    document.getElementById('calDiaInteiro').checked = evento ? !!parseInt(evento.dia_inteiro) : false;
    document.getElementById('calLocal').value = evento?.local || '';
    document.getElementById('calDescricao').value = evento?.descricao || '';
    document.getElementById('calRecorrencia').value = evento?.recorrencia || 'nenhuma';
    document.getElementById('calNotificar').value = evento?.notificar_antes ?? 15;
    document.getElementById('calBtnExcluir').style.display = evento ? '' : 'none';

    if (evento) {
        document.getElementById('calInicio').value = calToInputDT(evento.data_inicio);
        document.getElementById('calFim').value = evento.data_fim ? calToInputDT(evento.data_fim) : '';
    } else if (dateStr) {
        const h = hour || 9;
        document.getElementById('calInicio').value = `${dateStr}T${String(h).padStart(2,'0')}:00`;
        document.getElementById('calFim').value = `${dateStr}T${String(h+1).padStart(2,'0')}:00`;
    } else {
        const now = new Date();
        now.setMinutes(0,0,0); now.setHours(now.getHours() + 1);
        document.getElementById('calInicio').value = calToInputDT(now.toISOString());
        const end = new Date(now); end.setHours(end.getHours() + 1);
        document.getElementById('calFim').value = calToInputDT(end.toISOString());
    }
    calToggleDiaInteiro();
}

function calToggleDiaInteiro() {
    const isAll = document.getElementById('calDiaInteiro').checked;
    document.getElementById('calFimGroup').style.display = isAll ? 'none' : '';
    if (isAll) {
        const v = document.getElementById('calInicio').value;
        if (v && v.includes('T')) document.getElementById('calInicio').value = v.split('T')[0] + 'T00:00';
    }
}

async function calVerEvento(id) {
    try {
        const r = await fetch(`${CAL_API}?action=evento&id=${id}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        calAbrirModal(data.data);
    } catch(e) { calToast('Erro: ' + e.message, 'danger'); }
}

async function calSalvar() {
    const id = document.getElementById('calEditId').value;
    const dados = {
        action: id ? 'atualizar' : 'criar',
        id: id || undefined,
        titulo: document.getElementById('calTitulo').value.trim(),
        tipo: document.getElementById('calTipo').value,
        cor: document.getElementById('calCor').value,
        data_inicio: document.getElementById('calInicio').value.replace('T', ' ') + ':00',
        data_fim: document.getElementById('calDiaInteiro').checked ? null : (document.getElementById('calFim').value ? document.getElementById('calFim').value.replace('T', ' ') + ':00' : null),
        dia_inteiro: document.getElementById('calDiaInteiro').checked ? 1 : 0,
        local: document.getElementById('calLocal').value.trim() || null,
        descricao: document.getElementById('calDescricao').value.trim() || null,
        recorrencia: document.getElementById('calRecorrencia').value,
        notificar_antes: parseInt(document.getElementById('calNotificar').value)
    };
    if (!dados.titulo || !dados.data_inicio) return calToast('Título e data obrigatórios', 'warning');
    try {
        const r = await fetch(CAL_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(dados)
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        calToast(data.message, 'success');
        document.getElementById('calModal').style.display = 'none';
        calRender();
    } catch(e) { calToast('Erro: ' + e.message, 'danger'); }
}

async function calExcluir() {
    const id = document.getElementById('calEditId').value;
    if (!id || !confirm('Excluir este evento?')) return;
    try {
        const r = await fetch(CAL_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action: 'excluir', id: parseInt(id)})
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        calToast('Evento excluído', 'success');
        document.getElementById('calModal').style.display = 'none';
        calRender();
    } catch(e) { calToast('Erro: ' + e.message, 'danger'); }
}

async function calImportarPrazos() {
    if (!confirm('Importar prazos de chamados e sprints para o calendário?')) return;
    try {
        const r = await fetch(CAL_API, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action: 'importar_prazos'})
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        calToast(data.message, 'success');
        calRender();
    } catch(e) { calToast('Erro: ' + e.message, 'danger'); }
}

// ==================== UTILS ====================
function calEscape(s) { if(!s) return ''; const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function calFormatDate(d) { return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function calFormatDT(d) {
    if (d instanceof Date) return d.toISOString().slice(0,19).replace('T',' ');
    return d;
}
function calToInputDT(str) {
    if (!str) return '';
    return str.replace(' ', 'T').substring(0, 16);
}
function calToast(msg, type='info') {
    const container = document.getElementById('calToastContainer') || (() => {
        const c = document.createElement('div'); c.id = 'calToastContainer'; c.className = 'gh-toast-container'; document.body.appendChild(c); return c;
    })();
    const icons = {success:'check-circle',danger:'exclamation-circle',warning:'exclamation-triangle',info:'info-circle'};
    const t = document.createElement('div'); t.className = `gh-toast ${type}`;
    t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}"></i><span>${msg}</span>`;
    container.appendChild(t);
    setTimeout(() => { t.classList.add('fade-out'); setTimeout(() => t.remove(), 300); }, 3500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none'); });
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.style.display = 'none'; }));
</script>
