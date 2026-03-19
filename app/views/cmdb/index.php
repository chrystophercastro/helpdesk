<?php
/**
 * View: CMDB - Configuration Management Database
 */
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-sitemap"></i> CMDB</h1>
        <p>Configuration Management Database — Gerenciamento de Itens de Configuração</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="cmdbAbrirModalItem()"><i class="fas fa-plus"></i> Novo CI</button>
        <button class="btn btn-secondary" onclick="cmdbAbrirModalRelacao()"><i class="fas fa-link"></i> Novo Relacionamento</button>
    </div>
</div>

<!-- KPI Cards -->
<div class="cmdb-kpis" id="cmdbKpis"></div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="cmdb-overview" onclick="cmdbTab(this)">
        <i class="fas fa-tachometer-alt"></i> Visão Geral
    </button>
    <button class="ad-tab" data-tab="cmdb-itens" onclick="cmdbTab(this)">
        <i class="fas fa-cube"></i> Itens (CI)
    </button>
    <button class="ad-tab" data-tab="cmdb-mapa" onclick="cmdbTab(this)">
        <i class="fas fa-project-diagram"></i> Mapa de Dependências
    </button>
    <button class="ad-tab" data-tab="cmdb-impacto" onclick="cmdbTab(this)">
        <i class="fas fa-bolt"></i> Análise de Impacto
    </button>
</div>

<!-- Tab: Overview -->
<div class="ad-tab-content active" id="cmdb-overview">
    <div class="cmdb-ov-grid">
        <div class="cmdb-ov-card">
            <h3><i class="fas fa-layer-group"></i> Por Categoria</h3>
            <div id="cmdbCatChart"></div>
        </div>
        <div class="cmdb-ov-card">
            <h3><i class="fas fa-signal"></i> Por Criticidade</h3>
            <div id="cmdbCritChart"></div>
        </div>
        <div class="cmdb-ov-card">
            <h3><i class="fas fa-globe"></i> Por Ambiente</h3>
            <div id="cmdbAmbChart"></div>
        </div>
        <div class="cmdb-ov-card cmdb-ov-full">
            <h3><i class="fas fa-history"></i> Últimas Mudanças</h3>
            <div id="cmdbUltimasMudancas" class="cmdb-timeline"></div>
        </div>
    </div>
</div>

<!-- Tab: Itens -->
<div class="ad-tab-content" id="cmdb-itens">
    <div class="cmdb-toolbar">
        <input type="text" id="cmdbBusca" placeholder="Buscar CIs..." class="ct-search" oninput="cmdbBuscarItens()">
        <select id="cmdbFiltroCat" class="ct-select" onchange="cmdbBuscarItens()">
            <option value="">Todas Categorias</option>
        </select>
        <select id="cmdbFiltroStatus" class="ct-select" onchange="cmdbBuscarItens()">
            <option value="">Todos os Status</option>
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
            <option value="planejado">Planejado</option>
            <option value="em_manutencao">Em Manutenção</option>
            <option value="aposentado">Aposentado</option>
        </select>
        <select id="cmdbFiltroCrit" class="ct-select" onchange="cmdbBuscarItens()">
            <option value="">Todas Criticidades</option>
            <option value="critica">Crítica</option>
            <option value="alta">Alta</option>
            <option value="media">Média</option>
            <option value="baixa">Baixa</option>
        </select>
        <select id="cmdbFiltroAmb" class="ct-select" onchange="cmdbBuscarItens()">
            <option value="">Todos Ambientes</option>
            <option value="producao">Produção</option>
            <option value="homologacao">Homologação</option>
            <option value="desenvolvimento">Desenvolvimento</option>
            <option value="teste">Teste</option>
        </select>
    </div>
    <div class="cmdb-cards-grid" id="cmdbItensGrid"></div>
</div>

<!-- Tab: Mapa -->
<div class="ad-tab-content" id="cmdb-mapa">
    <div class="cmdb-mapa-container">
        <div class="cmdb-mapa-legenda" id="cmdbMapaLegenda"></div>
        <div class="cmdb-mapa-canvas" id="cmdbMapaCanvas">
            <svg id="cmdbSvg" width="100%" height="600"></svg>
        </div>
    </div>
</div>

<!-- Tab: Análise de Impacto -->
<div class="ad-tab-content" id="cmdb-impacto">
    <div class="cmdb-impacto-header">
        <label>Selecione um CI para analisar impacto:</label>
        <select id="cmdbImpactoSelect" class="ct-select" style="min-width:300px" onchange="cmdbAnalisarImpacto()">
            <option value="">Selecione...</option>
        </select>
    </div>
    <div id="cmdbImpactoResult" class="cmdb-impacto-result"></div>
</div>

<!-- Modal Item CI -->
<div class="modal-overlay" id="cmdbModalItem">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h2 id="cmdbModalItemTitulo">Novo Item de Configuração</h2>
            <button class="modal-close" onclick="cmdbFecharModal('cmdbModalItem')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cmdbItemId">
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Nome *</label>
                    <input type="text" id="cmdbItemNome" class="form-control" placeholder="Ex: Servidor Web Principal">
                </div>
                <div class="form-group" style="flex:0 0 180px">
                    <label>Identificador</label>
                    <input type="text" id="cmdbItemIdentificador" class="form-control" placeholder="SRV-WEB-01">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Categoria</label>
                    <select id="cmdbItemCategoria" class="form-control"></select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Criticidade</label>
                    <select id="cmdbItemCriticidade" class="form-control">
                        <option value="baixa">Baixa</option>
                        <option value="media" selected>Média</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Status</label>
                    <select id="cmdbItemStatus" class="form-control">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="planejado">Planejado</option>
                        <option value="em_manutencao">Em Manutenção</option>
                        <option value="aposentado">Aposentado</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Ambiente</label>
                    <select id="cmdbItemAmbiente" class="form-control">
                        <option value="producao">Produção</option>
                        <option value="homologacao">Homologação</option>
                        <option value="desenvolvimento">Desenvolvimento</option>
                        <option value="teste">Teste</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea id="cmdbItemDescricao" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Versão</label>
                    <input type="text" id="cmdbItemVersao" class="form-control" placeholder="v2.1.0">
                </div>
                <div class="form-group" style="flex:1">
                    <label>IP / Endereço</label>
                    <input type="text" id="cmdbItemIp" class="form-control" placeholder="192.168.1.100">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Localização</label>
                    <input type="text" id="cmdbItemLocal" class="form-control" placeholder="Rack A3, Sala Servidores">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Responsável</label>
                    <select id="cmdbItemResponsavel" class="form-control"><option value="">Nenhum</option></select>
                </div>
            </div>
            <div class="form-group">
                <label>Fornecedor</label>
                <select id="cmdbItemFornecedor" class="form-control"><option value="">Nenhum</option></select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cmdbFecharModal('cmdbModalItem')">Cancelar</button>
            <button class="btn btn-primary" onclick="cmdbSalvarItem()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Relacionamento -->
<div class="modal-overlay" id="cmdbModalRelacao">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h2>Novo Relacionamento</h2>
            <button class="modal-close" onclick="cmdbFecharModal('cmdbModalRelacao')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>CI de Origem *</label>
                <select id="cmdbRelOrigem" class="form-control"></select>
            </div>
            <div class="form-group">
                <label>Tipo de Relação *</label>
                <select id="cmdbRelTipo" class="form-control">
                    <option value="depende_de">Depende de</option>
                    <option value="componente_de">Componente de</option>
                    <option value="conecta_com">Conecta com</option>
                    <option value="backup_de">Backup de</option>
                    <option value="executa_em">Executa em</option>
                    <option value="hospedado_em">Hospedado em</option>
                    <option value="monitora">Monitora</option>
                </select>
            </div>
            <div class="form-group">
                <label>CI de Destino *</label>
                <select id="cmdbRelDestino" class="form-control"></select>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <input type="text" id="cmdbRelDescricao" class="form-control" placeholder="Descrição opcional">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cmdbFecharModal('cmdbModalRelacao')">Cancelar</button>
            <button class="btn btn-primary" onclick="cmdbSalvarRelacao()"><i class="fas fa-link"></i> Criar</button>
        </div>
    </div>
</div>

<!-- Modal Detalhes CI -->
<div class="modal-overlay" id="cmdbModalDetalhes">
    <div class="modal" style="max-width:750px">
        <div class="modal-header">
            <h2>Detalhes do CI</h2>
            <button class="modal-close" onclick="cmdbFecharModal('cmdbModalDetalhes')">&times;</button>
        </div>
        <div class="modal-body" id="cmdbDetalhesBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cmdbFecharModal('cmdbModalDetalhes')">Fechar</button>
        </div>
    </div>
</div>

<script>
const CMDB_API = '<?= BASE_URL ?>/api/cmdb.php';
let cmdbCategoriasCache = [];
let cmdbItensCache = [];

document.addEventListener('DOMContentLoaded', () => {
    cmdbCarregarOverview();
    cmdbCarregarCategorias();
    cmdbCarregarItens();
    cmdbCarregarSelects();
});

function cmdbTab(btn) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    const tabId = btn.dataset.tab;
    document.getElementById(tabId).classList.add('active');
    if (tabId === 'cmdb-mapa') cmdbCarregarMapa();
}

function cmdbFecharModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
function cmdbFecharTodosModais() {
    ['cmdbModalItem','cmdbModalRelacao','cmdbModalDetalhes'].forEach(id => {
        document.getElementById(id)?.classList.remove('active');
    });
    document.body.style.overflow = '';
}
// Click overlay to close
['cmdbModalItem','cmdbModalRelacao','cmdbModalDetalhes'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) cmdbFecharModal(id);
    });
});
// ESC key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cmdbFecharTodosModais();
});

// === Criticidade/Status helpers ===
function cmdbCritBadge(c) {
    const map = {critica:'cmdb-crit-critica',alta:'cmdb-crit-alta',media:'cmdb-crit-media',baixa:'cmdb-crit-baixa'};
    const labels = {critica:'Crítica',alta:'Alta',media:'Média',baixa:'Baixa'};
    return `<span class="cmdb-badge ${map[c]||''}">${labels[c]||c}</span>`;
}
function cmdbStatusBadge(s) {
    const map = {ativo:'cmdb-st-ativo',inativo:'cmdb-st-inativo',planejado:'cmdb-st-plan',em_manutencao:'cmdb-st-manut',aposentado:'cmdb-st-apo'};
    const labels = {ativo:'Ativo',inativo:'Inativo',planejado:'Planejado',em_manutencao:'Manutenção',aposentado:'Aposentado'};
    return `<span class="cmdb-badge ${map[s]||''}">${labels[s]||s}</span>`;
}
function cmdbRelTipoLabel(t) {
    const map = {depende_de:'Depende de',componente_de:'Componente de',conecta_com:'Conecta com',backup_de:'Backup de',executa_em:'Executa em',hospedado_em:'Hospedado em',monitora:'Monitora'};
    return map[t]||t;
}

// === OVERVIEW ===
async function cmdbCarregarOverview() {
    try {
        const r = await fetch(CMDB_API + '?action=overview');
        const j = await r.json();
        if (!j.success) return;
        const d = j.data;

        document.getElementById('cmdbKpis').innerHTML = `
            <div class="cmdb-kpi"><div class="cmdb-kpi-icon" style="background:var(--primary)"><i class="fas fa-cubes"></i></div>
                <div class="cmdb-kpi-info"><span class="cmdb-kpi-val">${d.total}</span><span class="cmdb-kpi-label">Total CIs</span></div></div>
            <div class="cmdb-kpi"><div class="cmdb-kpi-icon" style="background:var(--success)"><i class="fas fa-check-circle"></i></div>
                <div class="cmdb-kpi-info"><span class="cmdb-kpi-val">${d.ativos}</span><span class="cmdb-kpi-label">Ativos</span></div></div>
            <div class="cmdb-kpi"><div class="cmdb-kpi-icon" style="background:var(--danger)"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="cmdb-kpi-info"><span class="cmdb-kpi-val">${d.criticos}</span><span class="cmdb-kpi-label">Críticos</span></div></div>
            <div class="cmdb-kpi"><div class="cmdb-kpi-icon" style="background:var(--purple)"><i class="fas fa-link"></i></div>
                <div class="cmdb-kpi-info"><span class="cmdb-kpi-val">${d.relacoes}</span><span class="cmdb-kpi-label">Relações</span></div></div>
            <div class="cmdb-kpi ${d.sem_relacao > 0 ? 'cmdb-kpi-warn' : ''}"><div class="cmdb-kpi-icon" style="background:var(--warning)"><i class="fas fa-unlink"></i></div>
                <div class="cmdb-kpi-info"><span class="cmdb-kpi-val">${d.sem_relacao}</span><span class="cmdb-kpi-label">Sem Relações</span></div></div>
        `;

        // Categoria chart
        const catEl = document.getElementById('cmdbCatChart');
        if (d.porCategoria.length) {
            const maxCat = Math.max(...d.porCategoria.map(c=>parseInt(c.total)));
            catEl.innerHTML = '<div class="cmdb-bar-list">' + d.porCategoria.map(c => {
                const pct = maxCat ? ((c.total/maxCat)*100) : 0;
                return `<div class="cmdb-bar-row"><span class="cmdb-bar-label"><i class="${c.icone}" style="color:${c.cor}"></i> ${c.nome}</span>
                    <div class="cmdb-bar-wrap"><div class="cmdb-bar" style="width:${pct}%;background:${c.cor}"></div></div>
                    <span class="cmdb-bar-val">${c.total}</span></div>`;
            }).join('') + '</div>';
        } else { catEl.innerHTML = '<p class="ct-empty">Sem dados</p>'; }

        // Criticidade chart
        const critEl = document.getElementById('cmdbCritChart');
        const critCores = {critica:'#EF4444',alta:'#F59E0B',media:'#3B82F6',baixa:'#10B981'};
        const critLabels = {critica:'Crítica',alta:'Alta',media:'Média',baixa:'Baixa'};
        if (d.porCriticidade.length) {
            const totalCrit = d.porCriticidade.reduce((s,c) => s+parseInt(c.total), 0);
            critEl.innerHTML = '<div class="cmdb-donut-grid">' + d.porCriticidade.map(c => {
                const pct = totalCrit ? ((c.total/totalCrit)*100).toFixed(0) : 0;
                return `<div class="cmdb-donut-item"><div class="cmdb-donut-color" style="background:${critCores[c.criticidade]}"></div>
                    <span>${critLabels[c.criticidade]||c.criticidade}</span><strong>${c.total} (${pct}%)</strong></div>`;
            }).join('') + '</div>';
        } else { critEl.innerHTML = '<p class="ct-empty">Sem dados</p>'; }

        // Ambiente chart
        const ambEl = document.getElementById('cmdbAmbChart');
        const ambCores = {producao:'#EF4444',homologacao:'#F59E0B',desenvolvimento:'#3B82F6',teste:'#10B981'};
        const ambLabels = {producao:'Produção',homologacao:'Homologação',desenvolvimento:'Desenvolvimento',teste:'Teste'};
        if (d.porAmbiente.length) {
            const totalAmb = d.porAmbiente.reduce((s,c) => s+parseInt(c.total), 0);
            ambEl.innerHTML = '<div class="cmdb-donut-grid">' + d.porAmbiente.map(c => {
                const pct = totalAmb ? ((c.total/totalAmb)*100).toFixed(0) : 0;
                return `<div class="cmdb-donut-item"><div class="cmdb-donut-color" style="background:${ambCores[c.ambiente]}"></div>
                    <span>${ambLabels[c.ambiente]||c.ambiente}</span><strong>${c.total} (${pct}%)</strong></div>`;
            }).join('') + '</div>';
        } else { ambEl.innerHTML = '<p class="ct-empty">Sem dados</p>'; }

        // Últimas mudanças
        const mudEl = document.getElementById('cmdbUltimasMudancas');
        if (d.ultimasMudancas.length) {
            mudEl.innerHTML = d.ultimasMudancas.map(m => {
                const dt = new Date(m.criado_em);
                const tipoIcon = {criacao:'fa-plus-circle',atualizacao:'fa-edit',status:'fa-exchange-alt',relacionamento:'fa-link',exclusao:'fa-trash'}[m.tipo_mudanca]||'fa-info-circle';
                return `<div class="cmdb-tl-item">
                    <div class="cmdb-tl-icon"><i class="fas ${tipoIcon}"></i></div>
                    <div class="cmdb-tl-content">
                        <strong>${m.ci_nome}</strong> — ${m.descricao||m.tipo_mudanca}
                        <small>${m.usuario_nome} • ${dt.toLocaleDateString('pt-BR')} ${dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</small>
                    </div>
                </div>`;
            }).join('');
        } else { mudEl.innerHTML = '<p class="ct-empty">Nenhuma mudança registrada</p>'; }

    } catch(e) { console.error('Overview error', e); }
}

// === CATEGORIAS ===
async function cmdbCarregarCategorias() {
    try {
        const r = await fetch(CMDB_API + '?action=categorias');
        const j = await r.json();
        cmdbCategoriasCache = j.data || [];
        const sel = document.getElementById('cmdbFiltroCat');
        if (sel) {
            const current = sel.value;
            sel.innerHTML = '<option value="">Todas Categorias</option>' +
                cmdbCategoriasCache.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
            sel.value = current;
        }
        const catSel = document.getElementById('cmdbItemCategoria');
        if (catSel) {
            catSel.innerHTML = '<option value="">Sem categoria</option>' +
                cmdbCategoriasCache.map(c => `<option value="${c.id}">${c.nome}</option>`).join('');
        }
    } catch(e) { console.error(e); }
}

// === ITENS ===
async function cmdbCarregarItens() { await cmdbBuscarItens(); }

async function cmdbBuscarItens() {
    const busca = document.getElementById('cmdbBusca')?.value || '';
    const cat = document.getElementById('cmdbFiltroCat')?.value || '';
    const status = document.getElementById('cmdbFiltroStatus')?.value || '';
    const crit = document.getElementById('cmdbFiltroCrit')?.value || '';
    const amb = document.getElementById('cmdbFiltroAmb')?.value || '';

    let url = CMDB_API + '?action=itens';
    if (busca) url += '&busca=' + encodeURIComponent(busca);
    if (cat) url += '&categoria_id=' + cat;
    if (status) url += '&status=' + status;
    if (crit) url += '&criticidade=' + crit;
    if (amb) url += '&ambiente=' + amb;

    try {
        const r = await fetch(url);
        const j = await r.json();
        cmdbItensCache = j.data || [];
        cmdbPopularSelectItens();

        const grid = document.getElementById('cmdbItensGrid');
        if (!cmdbItensCache.length) {
            grid.innerHTML = '<p class="ct-empty" style="grid-column:1/-1">Nenhum item de configuração encontrado</p>';
            return;
        }
        grid.innerHTML = cmdbItensCache.map(ci => `
            <div class="cmdb-ci-card cmdb-ci-${ci.criticidade}" onclick="cmdbVerDetalhes(${ci.id})">
                <div class="cmdb-ci-header">
                    <div class="cmdb-ci-icon" style="background:${ci.categoria_cor||'var(--primary)'}">
                        <i class="${ci.categoria_icone||'fas fa-cube'}"></i>
                    </div>
                    <div class="cmdb-ci-title">
                        <h4>${ci.nome}</h4>
                        <small>${ci.identificador || 'Sem ID'} ${ci.ip_endereco ? '• '+ci.ip_endereco : ''}</small>
                    </div>
                    ${cmdbCritBadge(ci.criticidade)}
                </div>
                <div class="cmdb-ci-body">
                    <div class="cmdb-ci-meta">
                        <span>${cmdbStatusBadge(ci.status)}</span>
                        <span class="cmdb-ci-cat"><i class="${ci.categoria_icone||'fas fa-cube'}" style="color:${ci.categoria_cor}"></i> ${ci.categoria_nome||'Sem categoria'}</span>
                    </div>
                    ${ci.descricao ? `<p class="cmdb-ci-desc">${ci.descricao.substring(0,100)}${ci.descricao.length>100?'...':''}</p>` : ''}
                </div>
                <div class="cmdb-ci-footer">
                    <span title="Relacionamentos"><i class="fas fa-link"></i> ${ci.total_relacoes||0}</span>
                    ${ci.responsavel_nome ? `<span><i class="fas fa-user"></i> ${ci.responsavel_nome}</span>` : ''}
                    <div class="cmdb-ci-actions">
                        <button class="btn btn-sm" onclick="event.stopPropagation();cmdbEditarItem(${ci.id})" title="Editar"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="event.stopPropagation();cmdbExcluirItem(${ci.id})" title="Excluir"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `).join('');
    } catch(e) { console.error('Itens error', e); }
}

function cmdbPopularSelectItens() {
    ['cmdbRelOrigem','cmdbRelDestino','cmdbImpactoSelect'].forEach(id => {
        const sel = document.getElementById(id);
        if (!sel) return;
        const current = sel.value;
        sel.innerHTML = '<option value="">Selecione...</option>' +
            cmdbItensCache.map(ci => `<option value="${ci.id}">[${ci.identificador||ci.id}] ${ci.nome}</option>`).join('');
        sel.value = current;
    });
}

async function cmdbCarregarSelects() {
    try {
        // Responsáveis
        const resp = document.getElementById('cmdbItemResponsavel');
        // Simple fetch usuarios list from existing infrastructure - use a generic approach
        resp.innerHTML = '<option value="">Nenhum</option>';

        // Fornecedores
        const forn = document.getElementById('cmdbItemFornecedor');
        forn.innerHTML = '<option value="">Nenhum</option>';
        try {
            const rf = await fetch('<?= BASE_URL ?>/api/contratos.php?action=fornecedores');
            const jf = await rf.json();
            if (jf.data) jf.data.forEach(f => forn.add(new Option(f.nome, f.id)));
        } catch(e) {}
    } catch(e) { console.error(e); }
}

// === MODALS ===
function cmdbAbrirModalItem(dados = null) {
    document.getElementById('cmdbModalItemTitulo').textContent = dados ? 'Editar CI' : 'Novo Item de Configuração';
    document.getElementById('cmdbItemId').value = dados?.id || '';
    document.getElementById('cmdbItemNome').value = dados?.nome || '';
    document.getElementById('cmdbItemIdentificador').value = dados?.identificador || '';
    document.getElementById('cmdbItemCategoria').value = dados?.categoria_id || '';
    document.getElementById('cmdbItemCriticidade').value = dados?.criticidade || 'media';
    document.getElementById('cmdbItemStatus').value = dados?.status || 'ativo';
    document.getElementById('cmdbItemAmbiente').value = dados?.ambiente || 'producao';
    document.getElementById('cmdbItemDescricao').value = dados?.descricao || '';
    document.getElementById('cmdbItemVersao').value = dados?.versao || '';
    document.getElementById('cmdbItemIp').value = dados?.ip_endereco || '';
    document.getElementById('cmdbItemLocal').value = dados?.localizacao || '';
    document.getElementById('cmdbItemResponsavel').value = dados?.responsavel_id || '';
    document.getElementById('cmdbItemFornecedor').value = dados?.fornecedor_id || '';
    cmdbFecharTodosModais();
    document.getElementById('cmdbModalItem').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function cmdbAbrirModalRelacao() {
    document.getElementById('cmdbRelOrigem').value = '';
    document.getElementById('cmdbRelDestino').value = '';
    document.getElementById('cmdbRelTipo').value = 'depende_de';
    document.getElementById('cmdbRelDescricao').value = '';
    cmdbFecharTodosModais();
    document.getElementById('cmdbModalRelacao').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// === SAVE ===
async function cmdbSalvarItem() {
    const id = document.getElementById('cmdbItemId').value;
    const payload = {
        action: id ? 'atualizar_item' : 'criar_item',
        id: id ? parseInt(id) : undefined,
        nome: document.getElementById('cmdbItemNome').value,
        identificador: document.getElementById('cmdbItemIdentificador').value || null,
        categoria_id: document.getElementById('cmdbItemCategoria').value || null,
        criticidade: document.getElementById('cmdbItemCriticidade').value,
        status: document.getElementById('cmdbItemStatus').value,
        ambiente: document.getElementById('cmdbItemAmbiente').value,
        descricao: document.getElementById('cmdbItemDescricao').value,
        versao: document.getElementById('cmdbItemVersao').value,
        ip_endereco: document.getElementById('cmdbItemIp').value,
        localizacao: document.getElementById('cmdbItemLocal').value,
        responsavel_id: document.getElementById('cmdbItemResponsavel').value || null,
        fornecedor_id: document.getElementById('cmdbItemFornecedor').value || null
    };
    try {
        const r = await fetch(CMDB_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        const j = await r.json();
        if (j.success) {
            cmdbFecharModal('cmdbModalItem');
            cmdbCarregarItens();
            cmdbCarregarOverview();
            showToast(j.message, 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao salvar', 'error'); }
}

async function cmdbSalvarRelacao() {
    const payload = {
        action: 'criar_relacionamento',
        ci_origem_id: parseInt(document.getElementById('cmdbRelOrigem').value),
        ci_destino_id: parseInt(document.getElementById('cmdbRelDestino').value),
        tipo: document.getElementById('cmdbRelTipo').value,
        descricao: document.getElementById('cmdbRelDescricao').value
    };
    if (!payload.ci_origem_id || !payload.ci_destino_id) { showToast('Selecione origem e destino', 'error'); return; }
    try {
        const r = await fetch(CMDB_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        const j = await r.json();
        if (j.success) {
            cmdbFecharModal('cmdbModalRelacao');
            cmdbCarregarItens();
            cmdbCarregarOverview();
            showToast(j.message, 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao criar relacionamento', 'error'); }
}

// === EDIT / DELETE ===
async function cmdbEditarItem(id) {
    try {
        const r = await fetch(CMDB_API + '?action=item&id=' + id);
        const j = await r.json();
        if (j.success) cmdbAbrirModalItem(j.data);
    } catch(e) { showToast('Erro ao carregar', 'error'); }
}

async function cmdbExcluirItem(id) {
    if (!confirm('Excluir este item e todos os seus relacionamentos?')) return;
    try {
        const r = await fetch(CMDB_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'excluir_item', id})});
        const j = await r.json();
        if (j.success) { cmdbCarregarItens(); cmdbCarregarOverview(); showToast(j.message, 'success'); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

// === DETALHES ===
async function cmdbVerDetalhes(id) {
    try {
        const r = await fetch(CMDB_API + '?action=item&id=' + id);
        const j = await r.json();
        if (!j.success) return;
        const ci = j.data;
        let relHtml = '';
        if (ci.relacionamentos && ci.relacionamentos.length) {
            relHtml = '<h4><i class="fas fa-link"></i> Relacionamentos</h4><div class="cmdb-det-rels">' +
                ci.relacionamentos.map(r => {
                    const isOrigem = r.ci_origem_id == id;
                    const outroNome = isOrigem ? r.destino_nome : r.origem_nome;
                    const outroIcon = isOrigem ? r.destino_icone : r.origem_icone;
                    const outroCor = isOrigem ? r.destino_cor : r.origem_cor;
                    const direcao = isOrigem ? '→' : '←';
                    return `<div class="cmdb-det-rel">
                        <i class="${outroIcon||'fas fa-cube'}" style="color:${outroCor}"></i>
                        <span>${direcao} <strong>${cmdbRelTipoLabel(r.tipo)}</strong> ${outroNome}</span>
                        <button class="btn btn-sm btn-danger" onclick="cmdbExcluirRelacao(${r.id})" title="Remover"><i class="fas fa-times"></i></button>
                    </div>`;
                }).join('') + '</div>';
        }
        let histHtml = '';
        if (ci.historico && ci.historico.length) {
            histHtml = '<h4><i class="fas fa-history"></i> Histórico</h4><div class="cmdb-det-hist">' +
                ci.historico.slice(0,10).map(h => {
                    const dt = new Date(h.criado_em);
                    return `<div class="cmdb-det-hist-item">
                        <small>${dt.toLocaleDateString('pt-BR')} ${dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</small>
                        <span>${h.descricao||h.tipo_mudanca}</span>
                        <small class="cmdb-det-hist-user">${h.usuario_nome}</small>
                    </div>`;
                }).join('') + '</div>';
        }

        document.getElementById('cmdbDetalhesBody').innerHTML = `
            <div class="cmdb-det-header">
                <div class="cmdb-ci-icon" style="background:${ci.categoria_cor||'var(--primary)'}; width:56px; height:56px; font-size:24px">
                    <i class="${ci.categoria_icone||'fas fa-cube'}"></i>
                </div>
                <div>
                    <h3>${ci.nome}</h3>
                    <span>${ci.identificador||''} ${ci.ip_endereco?'• '+ci.ip_endereco:''}</span>
                </div>
                <div>${cmdbCritBadge(ci.criticidade)} ${cmdbStatusBadge(ci.status)}</div>
            </div>
            <div class="ct-det-grid">
                <div class="ct-det-item"><label>Categoria</label><span>${ci.categoria_nome||'-'}</span></div>
                <div class="ct-det-item"><label>Ambiente</label><span>${ci.ambiente||'-'}</span></div>
                <div class="ct-det-item"><label>Versão</label><span>${ci.versao||'-'}</span></div>
                <div class="ct-det-item"><label>Localização</label><span>${ci.localizacao||'-'}</span></div>
                <div class="ct-det-item"><label>Responsável</label><span>${ci.responsavel_nome||'-'}</span></div>
                <div class="ct-det-item"><label>Fornecedor</label><span>${ci.fornecedor_nome||'-'}</span></div>
            </div>
            ${ci.descricao ? '<div class="ct-det-section"><h4>Descrição</h4><p>'+ci.descricao+'</p></div>' : ''}
            ${relHtml}
            ${histHtml}
        `;
        cmdbFecharTodosModais();
        document.getElementById('cmdbModalDetalhes').classList.add('active');
        document.body.style.overflow = 'hidden';
    } catch(e) { showToast('Erro ao carregar detalhes', 'error'); }
}

async function cmdbExcluirRelacao(id) {
    if (!confirm('Remover este relacionamento?')) return;
    try {
        const r = await fetch(CMDB_API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'excluir_relacionamento', id})});
        const j = await r.json();
        if (j.success) { cmdbFecharModal('cmdbModalDetalhes'); cmdbCarregarItens(); cmdbCarregarOverview(); showToast(j.message, 'success'); }
    } catch(e) { showToast('Erro', 'error'); }
}

// === MAPA DE DEPENDÊNCIAS ===
async function cmdbCarregarMapa() {
    try {
        const r = await fetch(CMDB_API + '?action=mapa');
        const j = await r.json();
        if (!j.success || !j.data.nodes.length) {
            document.getElementById('cmdbMapaCanvas').innerHTML = '<p class="ct-empty" style="padding:80px">Nenhum CI cadastrado para exibir o mapa</p>';
            return;
        }
        cmdbRenderMapa(j.data);
    } catch(e) { console.error('Mapa error', e); }
}

function cmdbRenderMapa(data) {
    const svg = document.getElementById('cmdbSvg');
    const W = svg.parentElement.clientWidth;
    const H = 600;
    svg.setAttribute('width', W);
    svg.setAttribute('height', H);

    // Simple force-directed layout (basic positioning)
    const nodes = data.nodes.map((n, i) => {
        const angle = (2 * Math.PI * i) / data.nodes.length;
        const radius = Math.min(W, H) * 0.35;
        return { ...n, x: W/2 + radius * Math.cos(angle), y: H/2 + radius * Math.sin(angle) };
    });
    const nodeMap = {};
    nodes.forEach(n => nodeMap[n.id] = n);

    let svgHtml = '<defs><marker id="arrowhead" viewBox="0 0 10 10" refX="25" refY="5" markerWidth="6" markerHeight="6" orient="auto"><path d="M 0 0 L 10 5 L 0 10 z" fill="var(--text-secondary)"/></marker></defs>';

    // Edges
    data.edges.forEach(e => {
        const s = nodeMap[e.source], t = nodeMap[e.target];
        if (s && t) {
            svgHtml += `<line x1="${s.x}" y1="${s.y}" x2="${t.x}" y2="${t.y}" stroke="var(--border)" stroke-width="1.5" marker-end="url(#arrowhead)"/>`;
        }
    });

    // Nodes
    nodes.forEach(n => {
        const cor = n.cor || '#3B82F6';
        const critStroke = {critica:'#EF4444',alta:'#F59E0B',media:'#3B82F6',baixa:'#10B981'}[n.criticidade] || '#3B82F6';
        svgHtml += `<g class="cmdb-map-node" onclick="cmdbVerDetalhes(${n.id})" style="cursor:pointer">
            <circle cx="${n.x}" cy="${n.y}" r="20" fill="${cor}" stroke="${critStroke}" stroke-width="3" opacity="0.9"/>
            <text x="${n.x}" y="${n.y+30}" text-anchor="middle" fill="var(--text-primary)" font-size="11" font-weight="500">${n.nome.substring(0,20)}</text>
        </g>`;
    });

    svg.innerHTML = svgHtml;

    // Legend
    const cats = [...new Set(nodes.map(n => JSON.stringify({nome:n.categoria||'Outro',cor:n.cor||'#6B7280'})))].map(s=>JSON.parse(s));
    document.getElementById('cmdbMapaLegenda').innerHTML = cats.map(c =>
        `<span class="cmdb-leg-item"><span class="cmdb-leg-color" style="background:${c.cor}"></span>${c.nome}</span>`
    ).join('');
}

// === ANÁLISE DE IMPACTO ===
async function cmdbAnalisarImpacto() {
    const id = document.getElementById('cmdbImpactoSelect').value;
    const el = document.getElementById('cmdbImpactoResult');
    if (!id) { el.innerHTML = ''; return; }

    try {
        const r = await fetch(CMDB_API + '?action=impacto&id=' + id);
        const j = await r.json();
        if (!j.success) return;

        const item = j.data.item;
        const impacto = j.data.impacto;

        let html = `<div class="cmdb-imp-header">
            <div class="cmdb-ci-icon" style="background:${item.categoria_cor||'var(--primary)'}"><i class="${item.categoria_icone||'fas fa-cube'}"></i></div>
            <div><h3>${item.nome}</h3><small>${item.identificador||''} — ${cmdbCritBadge(item.criticidade)}</small></div>
        </div>`;

        if (!impacto.length) {
            html += '<div class="cmdb-imp-empty"><i class="fas fa-check-circle"></i> Nenhum item depende diretamente deste CI.</div>';
        } else {
            html += `<h4 style="margin:16px 0 8px"><i class="fas fa-bolt" style="color:var(--warning)"></i> ${impacto.length} item(ns) impactado(s)</h4>`;
            html += '<div class="cmdb-imp-tree">';
            html += cmdbRenderImpactoTree(impacto, 0);
            html += '</div>';
        }

        el.innerHTML = html;
    } catch(e) { console.error('Impacto error', e); }
}

function cmdbRenderImpactoTree(items, level) {
    return items.map(item => {
        const critCor = {critica:'#EF4444',alta:'#F59E0B',media:'#3B82F6',baixa:'#10B981'}[item.criticidade]||'#6B7280';
        let html = `<div class="cmdb-imp-item" style="margin-left:${level*28}px">
            <i class="${item.icone||'fas fa-cube'}" style="color:${item.cor||'var(--primary)'}"></i>
            <span class="cmdb-imp-name">${item.nome}</span>
            ${cmdbCritBadge(item.criticidade)}
            <small class="cmdb-imp-tipo">${cmdbRelTipoLabel(item.tipo)}</small>
        </div>`;
        if (item.impacto_indireto && item.impacto_indireto.length) {
            html += cmdbRenderImpactoTree(item.impacto_indireto, level + 1);
        }
        return html;
    }).join('');
}
</script>
