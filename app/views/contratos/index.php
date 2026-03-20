<?php
/**
 * View: Contratos e Fornecedores
 */
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-file-contract"></i> Contratos e Fornecedores</h1>
        <p>Gestão de contratos, fornecedores e garantias</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('contratos_intelligence')">
            <i class="fas fa-robot"></i> Inteligência IA
        </button>
        <button class="btn btn-primary" onclick="ctAbrirModalContrato()"><i class="fas fa-plus"></i> Novo Contrato</button>
        <button class="btn btn-secondary" onclick="ctAbrirModalFornecedor()"><i class="fas fa-plus"></i> Novo Fornecedor</button>
    </div>
</div>

<!-- KPI Cards -->
<div class="ct-kpis" id="ctKpis"></div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="ct-overview" onclick="ctTab(this)">
        <i class="fas fa-tachometer-alt"></i> Visão Geral
    </button>
    <button class="ad-tab" data-tab="ct-contratos" onclick="ctTab(this)">
        <i class="fas fa-file-contract"></i> Contratos
    </button>
    <button class="ad-tab" data-tab="ct-fornecedores" onclick="ctTab(this)">
        <i class="fas fa-building"></i> Fornecedores
    </button>
    <button class="ad-tab" data-tab="ct-alertas" onclick="ctTab(this)">
        <i class="fas fa-bell"></i> Alertas
    </button>
</div>

<!-- Tab: Visão Geral -->
<div class="ad-tab-content active" id="ct-overview">
    <div class="ct-overview-grid">
        <div class="ct-chart-card">
            <h3><i class="fas fa-chart-pie"></i> Contratos por Tipo</h3>
            <div id="ctTipoChart" class="ct-chart-body"></div>
        </div>
        <div class="ct-chart-card">
            <h3><i class="fas fa-exclamation-triangle"></i> Vencendo em 30 dias</h3>
            <div id="ctVencendoList" class="ct-alert-list"></div>
        </div>
        <div class="ct-chart-card">
            <h3><i class="fas fa-times-circle"></i> Contratos Vencidos</h3>
            <div id="ctVencidosList" class="ct-alert-list"></div>
        </div>
    </div>
</div>

<!-- Tab: Contratos -->
<div class="ad-tab-content" id="ct-contratos">
    <div class="ct-toolbar">
        <input type="text" id="ctBuscaContrato" placeholder="Buscar contratos..." class="ct-search" oninput="ctBuscarContratos()">
        <select id="ctFiltroStatus" class="ct-select" onchange="ctBuscarContratos()">
            <option value="">Todos os Status</option>
            <option value="ativo">Ativo</option>
            <option value="vencido">Vencido</option>
            <option value="cancelado">Cancelado</option>
            <option value="renovado">Renovado</option>
        </select>
        <select id="ctFiltroTipo" class="ct-select" onchange="ctBuscarContratos()">
            <option value="">Todos os Tipos</option>
            <option value="manutencao">Manutenção</option>
            <option value="garantia">Garantia</option>
            <option value="licenca">Licença</option>
            <option value="suporte">Suporte</option>
            <option value="servico">Serviço</option>
            <option value="aluguel">Aluguel</option>
            <option value="outro">Outro</option>
        </select>
        <select id="ctFiltroFornecedor" class="ct-select" onchange="ctBuscarContratos()">
            <option value="">Todos os Fornecedores</option>
        </select>
    </div>
    <div class="ct-table-wrap">
        <table class="ct-table" id="ctTabelaContratos">
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Título</th>
                    <th>Fornecedor</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Vigência</th>
                    <th>Dias Rest.</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="ctContratosBody"></tbody>
        </table>
    </div>
</div>

<!-- Tab: Fornecedores -->
<div class="ad-tab-content" id="ct-fornecedores">
    <div class="ct-toolbar">
        <input type="text" id="ctBuscaFornecedor" placeholder="Buscar fornecedores..." class="ct-search" oninput="ctBuscarFornecedores()">
    </div>
    <div class="ct-cards-grid" id="ctFornecedoresGrid"></div>
</div>

<!-- Tab: Alertas -->
<div class="ad-tab-content" id="ct-alertas">
    <div class="ct-alertas-section">
        <h3><i class="fas fa-clock" style="color:var(--warning)"></i> Vencendo nos Próximos 30 Dias</h3>
        <div class="ct-table-wrap">
            <table class="ct-table">
                <thead>
                    <tr><th>Contrato</th><th>Fornecedor</th><th>Vencimento</th><th>Dias</th><th>Valor</th><th>Ações</th></tr>
                </thead>
                <tbody id="ctAlertaVencendoBody"></tbody>
            </table>
        </div>
    </div>
    <div class="ct-alertas-section" style="margin-top:24px">
        <h3><i class="fas fa-exclamation-circle" style="color:var(--danger)"></i> Contratos Vencidos</h3>
        <div class="ct-table-wrap">
            <table class="ct-table">
                <thead>
                    <tr><th>Contrato</th><th>Fornecedor</th><th>Vencimento</th><th>Dias Atrás</th><th>Valor</th><th>Ações</th></tr>
                </thead>
                <tbody id="ctAlertaVencidoBody"></tbody>
            </table>
        </div>
    </div>
    <div style="margin-top:16px">
        <button class="btn btn-warning" onclick="ctAtualizarVencidos()"><i class="fas fa-sync"></i> Atualizar Status de Vencidos</button>
    </div>
</div>

<!-- Modal Contrato -->
<div class="modal-overlay" id="ctModalContrato">
    <div class="modal" style="max-width:720px">
        <div class="modal-header">
            <h2 id="ctModalContratoTitulo">Novo Contrato</h2>
            <button class="modal-close" onclick="ctFecharModal('ctModalContrato')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="ctContratoId">
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Título *</label>
                    <input type="text" id="ctContratoTitulo" class="form-control">
                </div>
                <div class="form-group" style="flex:0 0 180px">
                    <label>Número</label>
                    <input type="text" id="ctContratoNumero" class="form-control" placeholder="CT-001">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Fornecedor *</label>
                    <select id="ctContratoFornecedor" class="form-control"></select>
                </div>
                <div class="form-group" style="flex:0 0 180px">
                    <label>Tipo</label>
                    <select id="ctContratoTipo" class="form-control">
                        <option value="manutencao">Manutenção</option>
                        <option value="garantia">Garantia</option>
                        <option value="licenca">Licença</option>
                        <option value="suporte">Suporte</option>
                        <option value="servico" selected>Serviço</option>
                        <option value="aluguel">Aluguel</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea id="ctContratoDescricao" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Valor (R$)</label>
                    <input type="number" step="0.01" id="ctContratoValor" class="form-control" value="0">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Recorrência</label>
                    <select id="ctContratoRecorrencia" class="form-control">
                        <option value="mensal">Mensal</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="semestral">Semestral</option>
                        <option value="anual">Anual</option>
                        <option value="unico">Único</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Status</label>
                    <select id="ctContratoStatus" class="form-control">
                        <option value="ativo">Ativo</option>
                        <option value="vencido">Vencido</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="renovado">Renovado</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Data Início</label>
                    <input type="date" id="ctContratoInicio" class="form-control">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Data Fim</label>
                    <input type="date" id="ctContratoFim" class="form-control">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Data Renovação</label>
                    <input type="date" id="ctContratoRenovacao" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:0 0 120px">
                    <label>Alerta (dias)</label>
                    <input type="number" id="ctContratoAlerta" class="form-control" value="30">
                </div>
                <div class="form-group" style="flex:0 0 160px; display:flex; align-items:flex-end; gap:8px;">
                    <input type="checkbox" id="ctContratoAutoRenovar">
                    <label for="ctContratoAutoRenovar" style="margin:0;cursor:pointer">Auto-renovar</label>
                </div>
            </div>
            <div class="form-group">
                <label>Observações</label>
                <textarea id="ctContratoObs" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Ativos Vinculados</label>
                <div class="ct-ativos-select" id="ctAtivosSelect"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="ctFecharModal('ctModalContrato')">Cancelar</button>
            <button class="btn btn-primary" onclick="ctSalvarContrato()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Fornecedor -->
<div class="modal-overlay" id="ctModalFornecedor">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <h2 id="ctModalFornecedorTitulo">Novo Fornecedor</h2>
            <button class="modal-close" onclick="ctFecharModal('ctModalFornecedor')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="ctFornecedorId">
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Nome *</label>
                    <input type="text" id="ctFornecedorNome" class="form-control">
                </div>
                <div class="form-group" style="flex:0 0 200px">
                    <label>CNPJ</label>
                    <input type="text" id="ctFornecedorCnpj" class="form-control" placeholder="00.000.000/0000-00">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Contato - Nome</label>
                    <input type="text" id="ctFornecedorContatoNome" class="form-control">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Contato - E-mail</label>
                    <input type="email" id="ctFornecedorContatoEmail" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Contato - Telefone</label>
                    <input type="text" id="ctFornecedorContatoTelefone" class="form-control">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Website</label>
                    <input type="url" id="ctFornecedorWebsite" class="form-control" placeholder="https://">
                </div>
            </div>
            <div class="form-group">
                <label>Endereço</label>
                <input type="text" id="ctFornecedorEndereco" class="form-control">
            </div>
            <div class="form-group">
                <label>Observações</label>
                <textarea id="ctFornecedorObs" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="ctFecharModal('ctModalFornecedor')">Cancelar</button>
            <button class="btn btn-primary" onclick="ctSalvarFornecedor()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Detalhes Contrato -->
<div class="modal-overlay" id="ctModalDetalhes">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h2>Detalhes do Contrato</h2>
            <button class="modal-close" onclick="ctFecharModal('ctModalDetalhes')">&times;</button>
        </div>
        <div class="modal-body" id="ctDetalhesBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="ctFecharModal('ctModalDetalhes')">Fechar</button>
        </div>
    </div>
</div>

<script>
const CT_API = '<?= BASE_URL ?>/api/contratos.php';
let ctFornecedoresCache = [];

// === INIT ===
document.addEventListener('DOMContentLoaded', () => {
    ctCarregarOverview();
    ctCarregarFornecedores();
    ctCarregarContratos();
    ctCarregarAlertas();
});

// === TABS ===
function ctTab(btn) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
}

// === UTILS ===
function ctFormatMoeda(v) { return 'R$ ' + parseFloat(v||0).toLocaleString('pt-BR', {minimumFractionDigits: 2}); }
function ctFormatData(d) { if (!d) return '-'; const p = d.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; }
function ctTipoLabel(t) {
    const map = {manutencao:'Manutenção',garantia:'Garantia',licenca:'Licença',suporte:'Suporte',servico:'Serviço',aluguel:'Aluguel',outro:'Outro'};
    return map[t] || t;
}
function ctTipoCor(t) {
    const map = {manutencao:'#3B82F6',garantia:'#10B981',licenca:'#8B5CF6',suporte:'#F59E0B',servico:'#06B6D4',aluguel:'#EC4899',outro:'#6B7280'};
    return map[t] || '#6B7280';
}
function ctStatusBadge(s) {
    const map = {ativo:'ct-badge-ativo',vencido:'ct-badge-vencido',cancelado:'ct-badge-cancelado',renovado:'ct-badge-renovado'};
    return `<span class="ct-badge ${map[s]||''}">${s.charAt(0).toUpperCase()+s.slice(1)}</span>`;
}
function ctFecharModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
function ctFecharTodosModais() {
    ['ctModalContrato','ctModalFornecedor','ctModalDetalhes'].forEach(id => {
        document.getElementById(id)?.classList.remove('active');
    });
    document.body.style.overflow = '';
}
// Click overlay to close
['ctModalContrato','ctModalFornecedor','ctModalDetalhes'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) ctFecharModal(id);
    });
});
// ESC key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') ctFecharTodosModais();
});

// === OVERVIEW / KPIs ===
async function ctCarregarOverview() {
    try {
        const r = await fetch(CT_API + '?action=overview');
        const j = await r.json();
        if (!j.success) return;
        const d = j.data;
        document.getElementById('ctKpis').innerHTML = `
            <div class="ct-kpi"><div class="ct-kpi-icon" style="background:var(--primary)"><i class="fas fa-building"></i></div>
                <div class="ct-kpi-info"><span class="ct-kpi-val">${d.fornecedores}</span><span class="ct-kpi-label">Fornecedores</span></div></div>
            <div class="ct-kpi"><div class="ct-kpi-icon" style="background:var(--success)"><i class="fas fa-file-contract"></i></div>
                <div class="ct-kpi-info"><span class="ct-kpi-val">${d.contratos_ativos}</span><span class="ct-kpi-label">Contratos Ativos</span></div></div>
            <div class="ct-kpi"><div class="ct-kpi-icon" style="background:var(--purple)"><i class="fas fa-dollar-sign"></i></div>
                <div class="ct-kpi-info"><span class="ct-kpi-val">${ctFormatMoeda(d.valor_mensal)}</span><span class="ct-kpi-label">Custo Mensal</span></div></div>
            <div class="ct-kpi"><div class="ct-kpi-icon" style="background:var(--warning)"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="ct-kpi-info"><span class="ct-kpi-val">${d.vencendo_30}</span><span class="ct-kpi-label">Vencendo (30d)</span></div></div>
            <div class="ct-kpi ct-kpi-danger"><div class="ct-kpi-icon" style="background:var(--danger)"><i class="fas fa-times-circle"></i></div>
                <div class="ct-kpi-info"><span class="ct-kpi-val">${d.vencidos}</span><span class="ct-kpi-label">Vencidos</span></div></div>
        `;
        // Tipo chart
        ctRenderTipoChart(d.porTipo || []);
    } catch(e) { console.error('Overview error', e); }
}

function ctRenderTipoChart(dados) {
    const el = document.getElementById('ctTipoChart');
    if (!dados.length) { el.innerHTML = '<p class="ct-empty">Nenhum contrato cadastrado</p>'; return; }
    const total = dados.reduce((s, d) => s + parseInt(d.total), 0);
    let html = '<div class="ct-tipo-bars">';
    dados.forEach(d => {
        const pct = ((d.total / total) * 100).toFixed(1);
        html += `<div class="ct-tipo-row">
            <span class="ct-tipo-label">${ctTipoLabel(d.tipo)}</span>
            <div class="ct-tipo-bar-wrap"><div class="ct-tipo-bar" style="width:${pct}%;background:${ctTipoCor(d.tipo)}"></div></div>
            <span class="ct-tipo-val">${d.total} (${pct}%)</span>
        </div>`;
    });
    html += '</div>';
    el.innerHTML = html;
}

// === ALERTAS ===
async function ctCarregarAlertas() {
    try {
        const [rv, rd] = await Promise.all([
            fetch(CT_API + '?action=vencendo&dias=30').then(r=>r.json()),
            fetch(CT_API + '?action=vencidos').then(r=>r.json())
        ]);
        // Vencendo
        const vencendoHtml = (rv.data||[]).map(c => `<tr>
            <td><strong>${c.titulo}</strong><br><small>${c.numero||''}</small></td>
            <td>${c.fornecedor_nome||'-'}</td>
            <td>${ctFormatData(c.data_fim)}</td>
            <td><span class="ct-dias-badge ct-dias-warning">${c.dias_restantes}d</span></td>
            <td>${ctFormatMoeda(c.valor)}</td>
            <td><button class="btn btn-sm" onclick="ctVerDetalhes(${c.id})"><i class="fas fa-eye"></i></button></td>
        </tr>`).join('') || '<tr><td colspan="6" class="ct-empty">Nenhum contrato vencendo</td></tr>';
        document.getElementById('ctAlertaVencendoBody').innerHTML = vencendoHtml;

        // Vencidos
        const vencidosHtml = (rd.data||[]).map(c => {
            const diasAtras = Math.abs(c.dias_restantes||0);
            return `<tr>
                <td><strong>${c.titulo}</strong><br><small>${c.numero||''}</small></td>
                <td>${c.fornecedor_nome||'-'}</td>
                <td>${ctFormatData(c.data_fim)}</td>
                <td><span class="ct-dias-badge ct-dias-danger">${diasAtras}d</span></td>
                <td>${ctFormatMoeda(c.valor)}</td>
                <td><button class="btn btn-sm" onclick="ctVerDetalhes(${c.id})"><i class="fas fa-eye"></i></button></td>
            </tr>`;
        }).join('') || '<tr><td colspan="6" class="ct-empty">Nenhum contrato vencido</td></tr>';
        document.getElementById('ctAlertaVencidoBody').innerHTML = vencidosHtml;

        // Overview lists
        document.getElementById('ctVencendoList').innerHTML = (rv.data||[]).slice(0,5).map(c =>
            `<div class="ct-alert-item ct-alert-warning" onclick="ctVerDetalhes(${c.id})">
                <div><strong>${c.titulo}</strong><br><small>${c.fornecedor_nome||''}</small></div>
                <div class="ct-alert-right"><span class="ct-dias-badge ct-dias-warning">${c.dias_restantes}d</span><br><small>${ctFormatMoeda(c.valor)}</small></div>
            </div>`
        ).join('') || '<p class="ct-empty">Nenhum alerta</p>';

        document.getElementById('ctVencidosList').innerHTML = (rd.data||[]).slice(0,5).map(c =>
            `<div class="ct-alert-item ct-alert-danger" onclick="ctVerDetalhes(${c.id})">
                <div><strong>${c.titulo}</strong><br><small>${c.fornecedor_nome||''}</small></div>
                <div class="ct-alert-right"><span class="ct-dias-badge ct-dias-danger">${Math.abs(c.dias_restantes||0)}d</span><br><small>${ctFormatMoeda(c.valor)}</small></div>
            </div>`
        ).join('') || '<p class="ct-empty">Nenhum contrato vencido</p>';
    } catch(e) { console.error('Alertas error', e); }
}

// === CONTRATOS ===
async function ctCarregarContratos() {
    await ctBuscarContratos();
}

async function ctBuscarContratos() {
    const busca = document.getElementById('ctBuscaContrato')?.value || '';
    const status = document.getElementById('ctFiltroStatus')?.value || '';
    const tipo = document.getElementById('ctFiltroTipo')?.value || '';
    const fornecedor = document.getElementById('ctFiltroFornecedor')?.value || '';
    let url = CT_API + '?action=contratos';
    if (busca) url += '&busca=' + encodeURIComponent(busca);
    if (status) url += '&status=' + status;
    if (tipo) url += '&tipo=' + tipo;
    if (fornecedor) url += '&fornecedor_id=' + fornecedor;

    try {
        const r = await fetch(url);
        const j = await r.json();
        const body = document.getElementById('ctContratosBody');
        if (!j.data || !j.data.length) {
            body.innerHTML = '<tr><td colspan="9" class="ct-empty">Nenhum contrato encontrado</td></tr>';
            return;
        }
        body.innerHTML = j.data.map(c => {
            const diasClass = c.dias_restantes !== null ?
                (c.dias_restantes < 0 ? 'ct-dias-danger' : c.dias_restantes <= 30 ? 'ct-dias-warning' : 'ct-dias-ok') : '';
            const diasTxt = c.dias_restantes !== null ? c.dias_restantes + 'd' : '-';
            return `<tr>
                <td>${c.numero || '-'}</td>
                <td><a href="#" onclick="ctVerDetalhes(${c.id});return false">${c.titulo}</a></td>
                <td>${c.fornecedor_nome || '-'}</td>
                <td><span class="ct-tipo-tag" style="background:${ctTipoCor(c.tipo)}">${ctTipoLabel(c.tipo)}</span></td>
                <td>${ctFormatMoeda(c.valor)}</td>
                <td>${ctFormatData(c.data_inicio)} — ${ctFormatData(c.data_fim)}</td>
                <td><span class="ct-dias-badge ${diasClass}">${diasTxt}</span></td>
                <td>${ctStatusBadge(c.status)}</td>
                <td class="ct-actions">
                    <button class="btn btn-sm" onclick="ctEditarContrato(${c.id})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="ctExcluirContrato(${c.id})" title="Excluir"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error('Contratos error', e); }
}

// === FORNECEDORES ===
async function ctCarregarFornecedores() {
    await ctBuscarFornecedores();
}

async function ctBuscarFornecedores() {
    const busca = document.getElementById('ctBuscaFornecedor')?.value || '';
    let url = CT_API + '?action=fornecedores';
    if (busca) url += '&busca=' + encodeURIComponent(busca);

    try {
        const r = await fetch(url);
        const j = await r.json();
        ctFornecedoresCache = j.data || [];
        // Update filter dropdown
        const sel = document.getElementById('ctFiltroFornecedor');
        if (sel && sel.options.length <= 1) {
            ctFornecedoresCache.forEach(f => {
                sel.add(new Option(f.nome, f.id));
            });
        }
        // Update modals selects
        ctPopularFornecedorSelect();

        const grid = document.getElementById('ctFornecedoresGrid');
        if (!ctFornecedoresCache.length) {
            grid.innerHTML = '<p class="ct-empty">Nenhum fornecedor cadastrado</p>';
            return;
        }
        grid.innerHTML = ctFornecedoresCache.map(f => `
            <div class="ct-forn-card">
                <div class="ct-forn-header">
                    <div class="ct-forn-avatar"><i class="fas fa-building"></i></div>
                    <div>
                        <h4>${f.nome}</h4>
                        <small>${f.cnpj || 'Sem CNPJ'}</small>
                    </div>
                </div>
                <div class="ct-forn-body">
                    ${f.contato_nome ? `<div class="ct-forn-row"><i class="fas fa-user"></i> ${f.contato_nome}</div>` : ''}
                    ${f.contato_email ? `<div class="ct-forn-row"><i class="fas fa-envelope"></i> ${f.contato_email}</div>` : ''}
                    ${f.contato_telefone ? `<div class="ct-forn-row"><i class="fas fa-phone"></i> ${f.contato_telefone}</div>` : ''}
                    ${f.website ? `<div class="ct-forn-row"><i class="fas fa-globe"></i> <a href="${f.website}" target="_blank">${f.website}</a></div>` : ''}
                </div>
                <div class="ct-forn-footer">
                    <span class="ct-forn-stat"><i class="fas fa-file-contract"></i> ${f.contratos_ativos || 0} contratos</span>
                    <span class="ct-forn-stat"><i class="fas fa-dollar-sign"></i> ${ctFormatMoeda(f.valor_total)}</span>
                    <div class="ct-forn-actions">
                        <button class="btn btn-sm" onclick="ctEditarFornecedor(${f.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="ctExcluirFornecedor(${f.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch(e) { console.error('Fornecedores error', e); }
}

function ctPopularFornecedorSelect() {
    const sel = document.getElementById('ctContratoFornecedor');
    if (!sel) return;
    sel.innerHTML = '<option value="">Selecione...</option>' +
        ctFornecedoresCache.map(f => `<option value="${f.id}">${f.nome}</option>`).join('');
}

// === MODALS ===
function ctAbrirModalContrato(dados = null) {
    document.getElementById('ctModalContratoTitulo').textContent = dados ? 'Editar Contrato' : 'Novo Contrato';
    document.getElementById('ctContratoId').value = dados?.id || '';
    document.getElementById('ctContratoTitulo').value = dados?.titulo || '';
    document.getElementById('ctContratoNumero').value = dados?.numero || '';
    document.getElementById('ctContratoFornecedor').value = dados?.fornecedor_id || '';
    document.getElementById('ctContratoTipo').value = dados?.tipo || 'servico';
    document.getElementById('ctContratoDescricao').value = dados?.descricao || '';
    document.getElementById('ctContratoValor').value = dados?.valor || 0;
    document.getElementById('ctContratoRecorrencia').value = dados?.recorrencia || 'mensal';
    document.getElementById('ctContratoStatus').value = dados?.status || 'ativo';
    document.getElementById('ctContratoInicio').value = dados?.data_inicio || new Date().toISOString().split('T')[0];
    document.getElementById('ctContratoFim').value = dados?.data_fim || '';
    document.getElementById('ctContratoRenovacao').value = dados?.data_renovacao || '';
    document.getElementById('ctContratoAlerta').value = dados?.alerta_dias || 30;
    document.getElementById('ctContratoAutoRenovar').checked = !!dados?.auto_renovar;
    document.getElementById('ctContratoObs').value = dados?.observacoes || '';
    ctCarregarAtivosSelect(dados?.ativos || []);
    ctFecharTodosModais();
    document.getElementById('ctModalContrato').classList.add('active');
    document.body.style.overflow = 'hidden';
}

async function ctCarregarAtivosSelect(ativosSelecionados = []) {
    const el = document.getElementById('ctAtivosSelect');
    try {
        const r = await fetch(CT_API + '?action=ativos_sem_contrato');
        const j = await r.json();
        const livres = j.data || [];
        const todos = [...ativosSelecionados.map(a => ({...a, checked: true})), ...livres.map(a => ({...a, checked: false}))];
        if (!todos.length) { el.innerHTML = '<p class="ct-empty">Nenhum ativo disponível</p>'; return; }
        el.innerHTML = '<div class="ct-ativos-list">' + todos.map(a =>
            `<label class="ct-ativo-item">
                <input type="checkbox" value="${a.id}" ${a.checked ? 'checked' : ''}>
                <span>${a.hostname || a.nome || 'Ativo #'+a.id}</span>
                <small>${a.tipo || ''} ${a.marca || ''}</small>
            </label>`
        ).join('') + '</div>';
    } catch(e) { el.innerHTML = '<p class="ct-empty">Erro ao carregar ativos</p>'; }
}

function ctAbrirModalFornecedor(dados = null) {
    document.getElementById('ctModalFornecedorTitulo').textContent = dados ? 'Editar Fornecedor' : 'Novo Fornecedor';
    document.getElementById('ctFornecedorId').value = dados?.id || '';
    document.getElementById('ctFornecedorNome').value = dados?.nome || '';
    document.getElementById('ctFornecedorCnpj').value = dados?.cnpj || '';
    document.getElementById('ctFornecedorContatoNome').value = dados?.contato_nome || '';
    document.getElementById('ctFornecedorContatoEmail').value = dados?.contato_email || '';
    document.getElementById('ctFornecedorContatoTelefone').value = dados?.contato_telefone || '';
    document.getElementById('ctFornecedorWebsite').value = dados?.website || '';
    document.getElementById('ctFornecedorEndereco').value = dados?.endereco || '';
    document.getElementById('ctFornecedorObs').value = dados?.observacoes || '';
    document.getElementById('ctModalFornecedor').classList.add('active');
}

// === SALVAR ===
async function ctSalvarContrato() {
    const id = document.getElementById('ctContratoId').value;
    const ativos = [...document.querySelectorAll('#ctAtivosSelect input:checked')].map(i => parseInt(i.value));
    const payload = {
        action: id ? 'atualizar_contrato' : 'criar_contrato',
        id: id ? parseInt(id) : undefined,
        fornecedor_id: parseInt(document.getElementById('ctContratoFornecedor').value),
        numero: document.getElementById('ctContratoNumero').value,
        titulo: document.getElementById('ctContratoTitulo').value,
        descricao: document.getElementById('ctContratoDescricao').value,
        tipo: document.getElementById('ctContratoTipo').value,
        valor: parseFloat(document.getElementById('ctContratoValor').value),
        recorrencia: document.getElementById('ctContratoRecorrencia').value,
        status: document.getElementById('ctContratoStatus').value,
        data_inicio: document.getElementById('ctContratoInicio').value,
        data_fim: document.getElementById('ctContratoFim').value || null,
        data_renovacao: document.getElementById('ctContratoRenovacao').value || null,
        alerta_dias: parseInt(document.getElementById('ctContratoAlerta').value),
        auto_renovar: document.getElementById('ctContratoAutoRenovar').checked ? 1 : 0,
        observacoes: document.getElementById('ctContratoObs').value,
        ativos: ativos
    };
    try {
        const r = await fetch(CT_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        const j = await r.json();
        if (j.success) {
            ctFecharModal('ctModalContrato');
            ctCarregarContratos();
            ctCarregarOverview();
            ctCarregarAlertas();
            showToast(j.message, 'success');
        } else { showToast(j.error, 'error'); }
    } catch(e) { showToast('Erro ao salvar contrato', 'error'); }
}

async function ctSalvarFornecedor() {
    const id = document.getElementById('ctFornecedorId').value;
    const payload = {
        action: id ? 'atualizar_fornecedor' : 'criar_fornecedor',
        id: id ? parseInt(id) : undefined,
        nome: document.getElementById('ctFornecedorNome').value,
        cnpj: document.getElementById('ctFornecedorCnpj').value,
        contato_nome: document.getElementById('ctFornecedorContatoNome').value,
        contato_email: document.getElementById('ctFornecedorContatoEmail').value,
        contato_telefone: document.getElementById('ctFornecedorContatoTelefone').value,
        website: document.getElementById('ctFornecedorWebsite').value,
        endereco: document.getElementById('ctFornecedorEndereco').value,
        observacoes: document.getElementById('ctFornecedorObs').value
    };
    try {
        const r = await fetch(CT_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        const j = await r.json();
        if (j.success) {
            ctFecharModal('ctModalFornecedor');
            ctCarregarFornecedores();
            ctCarregarOverview();
            showToast(j.message, 'success');
        } else { showToast(j.error, 'error'); }
    } catch(e) { showToast('Erro ao salvar fornecedor', 'error'); }
}

// === EDITAR / EXCLUIR ===
async function ctEditarContrato(id) {
    try {
        const r = await fetch(CT_API + '?action=contrato&id=' + id);
        const j = await r.json();
        if (j.success) ctAbrirModalContrato(j.data);
    } catch(e) { showToast('Erro ao carregar contrato', 'error'); }
}

async function ctEditarFornecedor(id) {
    try {
        const r = await fetch(CT_API + '?action=fornecedor&id=' + id);
        const j = await r.json();
        if (j.success) ctAbrirModalFornecedor(j.data);
    } catch(e) { showToast('Erro ao carregar fornecedor', 'error'); }
}

async function ctExcluirContrato(id) {
    if (!confirm('Excluir este contrato permanentemente?')) return;
    try {
        const r = await fetch(CT_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'excluir_contrato', id})});
        const j = await r.json();
        if (j.success) { ctCarregarContratos(); ctCarregarOverview(); ctCarregarAlertas(); showToast(j.message, 'success'); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao excluir', 'error'); }
}

async function ctExcluirFornecedor(id) {
    if (!confirm('Desativar este fornecedor?')) return;
    try {
        const r = await fetch(CT_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'excluir_fornecedor', id})});
        const j = await r.json();
        if (j.success) { ctCarregarFornecedores(); ctCarregarOverview(); showToast(j.message, 'success'); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao excluir', 'error'); }
}

// === DETALHES ===
async function ctVerDetalhes(id) {
    try {
        const r = await fetch(CT_API + '?action=contrato&id=' + id);
        const j = await r.json();
        if (!j.success) return;
        const c = j.data;
        document.getElementById('ctDetalhesBody').innerHTML = `
            <div class="ct-det-header">
                <h3>${c.titulo}</h3>
                <div>${ctStatusBadge(c.status)} <span class="ct-tipo-tag" style="background:${ctTipoCor(c.tipo)}">${ctTipoLabel(c.tipo)}</span></div>
            </div>
            <div class="ct-det-grid">
                <div class="ct-det-item"><label>Número</label><span>${c.numero || '-'}</span></div>
                <div class="ct-det-item"><label>Fornecedor</label><span>${c.fornecedor_nome || '-'}</span></div>
                <div class="ct-det-item"><label>Valor</label><span>${ctFormatMoeda(c.valor)}</span></div>
                <div class="ct-det-item"><label>Recorrência</label><span>${c.recorrencia}</span></div>
                <div class="ct-det-item"><label>Data Início</label><span>${ctFormatData(c.data_inicio)}</span></div>
                <div class="ct-det-item"><label>Data Fim</label><span>${ctFormatData(c.data_fim)}</span></div>
                <div class="ct-det-item"><label>Renovação</label><span>${ctFormatData(c.data_renovacao)}</span></div>
                <div class="ct-det-item"><label>Auto-renovar</label><span>${c.auto_renovar ? 'Sim' : 'Não'}</span></div>
                <div class="ct-det-item"><label>Alerta</label><span>${c.alerta_dias} dias antes</span></div>
            </div>
            ${c.descricao ? `<div class="ct-det-section"><h4>Descrição</h4><p>${c.descricao}</p></div>` : ''}
            ${c.observacoes ? `<div class="ct-det-section"><h4>Observações</h4><p>${c.observacoes}</p></div>` : ''}
            ${c.ativos && c.ativos.length ? `<div class="ct-det-section"><h4>Ativos Vinculados (${c.ativos.length})</h4>
                <div class="ct-det-ativos">${c.ativos.map(a => `<span class="ct-det-ativo"><i class="fas fa-desktop"></i> ${a.hostname || a.nome || 'Ativo #'+a.id}</span>`).join('')}</div>
            </div>` : ''}
        `;
        ctFecharTodosModais();
        document.getElementById('ctModalDetalhes').classList.add('active');
        document.body.style.overflow = 'hidden';
    } catch(e) { showToast('Erro ao carregar detalhes', 'error'); }
}

async function ctAtualizarVencidos() {
    try {
        const r = await fetch(CT_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'atualizar_vencidos'})});
        const j = await r.json();
        if (j.success) { ctCarregarContratos(); ctCarregarAlertas(); ctCarregarOverview(); showToast('Status atualizado!', 'success'); }
    } catch(e) { showToast('Erro ao atualizar', 'error'); }
}
</script>
