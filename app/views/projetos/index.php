<?php
/**
 * View: Projetos — Layout Profissional
 */
$statusList = PROJETO_STATUS;
$prioridadeList = PRIORIDADES;
$filtroStatus = $_GET['status'] ?? '';
$filtroPrioridade = $_GET['prioridade'] ?? '';
$filtroBusca = $_GET['busca'] ?? '';

// Contadores para summary cards
$totalProjetos = count($projetos);
$projetosAtivos = 0;
$projetosConcluidos = 0;
$projetosAtrasados = 0;
$projetosCancelados = 0;
foreach ($projetos as $p) {
    if (in_array($p['status'], ['em_desenvolvimento', 'em_testes', 'planejamento'])) $projetosAtivos++;
    if ($p['status'] === 'concluido') $projetosConcluidos++;
    if ($p['status'] === 'cancelado') $projetosCancelados++;
    if ($p['prazo'] && strtotime($p['prazo']) < time() && !in_array($p['status'], ['concluido', 'cancelado'])) $projetosAtrasados++;
}
?>

<!-- Summary Cards -->
<div class="proj-summary">
    <div class="proj-summary-card">
        <div class="proj-summary-icon" style="background: var(--primary-bg); color: var(--primary);"><i class="fas fa-project-diagram"></i></div>
        <div class="proj-summary-info"><span class="proj-summary-value"><?= $totalProjetos ?></span><span class="proj-summary-label">Total</span></div>
    </div>
    <div class="proj-summary-card">
        <div class="proj-summary-icon" style="background: #dbeafe; color: #2563eb;"><i class="fas fa-spinner"></i></div>
        <div class="proj-summary-info"><span class="proj-summary-value"><?= $projetosAtivos ?></span><span class="proj-summary-label">Ativos</span></div>
    </div>
    <div class="proj-summary-card">
        <div class="proj-summary-icon" style="background: #d1fae5; color: #059669;"><i class="fas fa-check-circle"></i></div>
        <div class="proj-summary-info"><span class="proj-summary-value"><?= $projetosConcluidos ?></span><span class="proj-summary-label">Concluídos</span></div>
    </div>
    <div class="proj-summary-card">
        <div class="proj-summary-icon" style="background: #fee2e2; color: #dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="proj-summary-info"><span class="proj-summary-value"><?= $projetosAtrasados ?></span><span class="proj-summary-label">Atrasados</span></div>
    </div>
    <div class="proj-summary-card">
        <div class="proj-summary-icon" style="background: #f1f5f9; color: #64748b;"><i class="fas fa-ban"></i></div>
        <div class="proj-summary-info"><span class="proj-summary-value"><?= $projetosCancelados ?></span><span class="proj-summary-label">Cancelados</span></div>
    </div>
</div>

<!-- Tabs: Ativos / Concluídos / Cancelados -->
<div class="proj-tabs">
    <button class="proj-tab active" data-tab="ativos" onclick="projSwitchTab('ativos')">
        <i class="fas fa-rocket"></i> Ativos <span class="proj-tab-count"><?= $projetosAtivos ?></span>
    </button>
    <button class="proj-tab" data-tab="concluidos" onclick="projSwitchTab('concluidos')">
        <i class="fas fa-check-circle"></i> Concluídos <span class="proj-tab-count"><?= $projetosConcluidos ?></span>
    </button>
    <button class="proj-tab" data-tab="cancelados" onclick="projSwitchTab('cancelados')">
        <i class="fas fa-ban"></i> Cancelados <span class="proj-tab-count"><?= $projetosCancelados ?></span>
    </button>
</div>

<!-- Filtros -->
<div class="proj-filters-bar">
    <div class="proj-filters-left">
        <h1 class="proj-page-title"><i class="fas fa-project-diagram"></i> Projetos</h1>
        <?php if (!isAdmin()): ?>
        <small style="color: var(--text-secondary); margin-left: 10px;">— Exibindo projetos do seu departamento</small>
        <?php endif; ?>
    </div>
    <div class="proj-filters-right">
        <div class="proj-filter-group">
            <input type="text" class="form-input" placeholder="Buscar projeto..." id="projBuscaInput" value="<?= htmlspecialchars($filtroBusca, ENT_QUOTES, 'UTF-8', false) ?>">
        </div>
        <div class="proj-filter-group">
            <select class="form-select" id="projStatusFilter" onchange="filtrarProjetos()">
                <option value="">Todos os Status</option>
                <?php foreach ($statusList as $key => $s): ?>
                <option value="<?= $key ?>" <?= $filtroStatus === $key ? 'selected' : '' ?>><?= $s['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="proj-filter-group">
            <select class="form-select" id="projPrioridadeFilter" onchange="filtrarProjetos()">
                <option value="">Todas Prioridades</option>
                <?php foreach ($prioridadeList as $key => $pri): ?>
                <option value="<?= $key ?>" <?= $filtroPrioridade === $key ? 'selected' : '' ?>><?= $pri['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('projeto_risk')">
            <i class="fas fa-robot"></i> Risco IA
        </button>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoProjeto')">
            <i class="fas fa-plus"></i> Novo Projeto
        </button>
    </div>
</div>

<!-- Grid de Projetos -->
<?php if (empty($projetos)): ?>
<div class="empty-state">
    <i class="fas fa-project-diagram"></i>
    <h3>Nenhum projeto encontrado</h3>
    <p>Crie seu primeiro projeto para começar</p>
</div>
<?php else: ?>
<div class="proj-grid">
    <?php foreach ($projetos as $p): ?>
    <?php
        $st = $statusList[$p['status']];
        $pri = $prioridadeList[$p['prioridade']];
        $atrasado = ($p['prazo'] && strtotime($p['prazo']) < time() && !in_array($p['status'], ['concluido', 'cancelado']));
        $progresso = (int)($p['progresso'] ?? 0);
        $progressClass = $progresso >= 80 ? 'proj-prog-high' : ($progresso >= 40 ? 'proj-prog-mid' : 'proj-prog-low');
        // Tab group
        $tabGroup = 'ativos';
        if ($p['status'] === 'concluido') $tabGroup = 'concluidos';
        elseif ($p['status'] === 'cancelado') $tabGroup = 'cancelados';
    ?>
    <div class="proj-card <?= $atrasado ? 'proj-card-late' : '' ?>"
         data-status="<?= $p['status'] ?>" data-prioridade="<?= $p['prioridade'] ?>"
         data-nome="<?= htmlspecialchars(strtolower($p['nome']), ENT_QUOTES, 'UTF-8', false) ?>"
         data-tab-group="<?= $tabGroup ?>"
         onclick="window.location='<?= BASE_URL ?>/index.php?page=projetos&action=ver&id=<?= $p['id'] ?>'">

        <div class="proj-card-top">
            <div class="proj-card-badges">
                <span class="proj-badge-status" style="background:<?= $st['cor'] ?>14;color:<?= $st['cor'] ?>;border:1px solid <?= $st['cor'] ?>30">
                    <?= $st['label'] ?>
                </span>
                <span class="proj-badge-priority" style="background:<?= $pri['cor'] ?>14;color:<?= $pri['cor'] ?>;border:1px solid <?= $pri['cor'] ?>30">
                    <i class="<?= $pri['icone'] ?>"></i> <?= $pri['label'] ?>
                </span>
            </div>
            <?php if ($atrasado): ?>
            <span class="proj-badge-late"><i class="fas fa-clock"></i> Atrasado</span>
            <?php endif; ?>
        </div>

        <h3 class="proj-card-title"><?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8', false) ?></h3>

        <?php if (!empty($p['descricao'])): ?>
        <p class="proj-card-desc"><?= htmlspecialchars(mb_strimwidth($p['descricao'] ?? '', 0, 120, '...'), ENT_QUOTES, 'UTF-8', false) ?></p>
        <?php endif; ?>

        <div class="proj-card-progress">
            <div class="proj-card-progress-header">
                <span>Progresso</span>
                <span class="proj-card-progress-pct"><?= $progresso ?>%</span>
            </div>
            <div class="proj-progress-track">
                <div class="proj-progress-fill <?= $progressClass ?>" style="width:<?= $progresso ?>%"></div>
            </div>
        </div>

        <div class="proj-card-footer">
            <div class="proj-card-meta">
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($p['responsavel_nome'] ?? 'Sem responsável', ENT_QUOTES, 'UTF-8', false) ?></span>
            </div>
            <div class="proj-card-meta">
                <span><i class="fas fa-tasks"></i> <?= $p['tarefas_concluidas'] ?? 0 ?>/<?= $p['total_tarefas'] ?? 0 ?> tarefas</span>
                <?php if ($p['prazo']): ?>
                <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($p['prazo'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
HelpDesk.modals = HelpDesk.modals || {};

// Build TECNICOS for user picker on index page
const TECNICOS_INDEX = [
    <?php foreach ($tecnicos as $t): ?>
    {id:<?= $t['id'] ?>, nome:'<?= addslashes($t['nome']) ?>', email:'<?= addslashes($t['email'] ?? '') ?>'},
    <?php endforeach; ?>
];

// Global User Picker (shared)
if (typeof UserPicker === 'undefined') {
    var UserPicker = {
        _overlay: null, _callback: null, _currentValue: null, _users: [],
        open(users, currentValue, callback) {
            this._users = users || TECNICOS_INDEX;
            this._callback = callback;
            this._currentValue = String(currentValue || '');
            this._render();
        },
        _render() {
            let ov = document.getElementById('userPickerOverlay');
            if (ov) ov.remove();
            ov = document.createElement('div');
            ov.id = 'userPickerOverlay';
            ov.className = 'user-picker-overlay';
            document.body.appendChild(ov);
            const self = this;
            ov.onclick = (e) => { if (e.target === ov) self.close(); };
            let listHtml = '';
            this._users.forEach(u => {
                const sel = String(u.id) === self._currentValue ? 'selected' : '';
                const initials = (u.nome || '?').substring(0,2).toUpperCase();
                listHtml += `<div class="user-picker-item ${sel}" data-id="${u.id}" data-nome="${(u.nome||'').replace(/"/g,'&quot;')}" data-search="${(u.nome||'').toLowerCase()} ${(u.email||'').toLowerCase()}">
                    <div class="user-picker-avatar">${initials}</div>
                    <div class="user-picker-info"><div class="user-picker-name">${u.nome||'—'}</div>${u.email ? '<div class="user-picker-email">'+u.email+'</div>' : ''}</div>
                    <div class="user-picker-check"><i class="fas fa-check"></i></div>
                </div>`;
            });
            ov.innerHTML = `<div class="user-picker-modal">
                <div class="user-picker-search"><input type="text" id="userPickerSearch" placeholder="Buscar usuário..." autocomplete="off"></div>
                <div class="user-picker-list" id="userPickerList">
                    <div class="user-picker-none" data-id="" data-nome=""><i class="fas fa-user-slash"></i> Sem responsável</div>
                    ${listHtml}
                </div>
            </div>`;
            requestAnimationFrame(() => ov.classList.add('open'));
            setTimeout(() => document.getElementById('userPickerSearch')?.focus(), 100);
            document.getElementById('userPickerSearch').addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('#userPickerList .user-picker-item').forEach(item => {
                    item.style.display = (!q || item.dataset.search.includes(q)) ? '' : 'none';
                });
            });
            document.querySelectorAll('#userPickerList .user-picker-item, #userPickerList .user-picker-none').forEach(item => {
                item.addEventListener('click', () => {
                    if (self._callback) self._callback(item.dataset.id || '', item.dataset.nome || '');
                    self.close();
                });
            });
            ov._keyHandler = (e) => { if (e.key === 'Escape') self.close(); };
            document.addEventListener('keydown', ov._keyHandler);
        },
        close() {
            const ov = document.getElementById('userPickerOverlay');
            if (ov) { document.removeEventListener('keydown', ov._keyHandler); ov.classList.remove('open'); setTimeout(() => ov.remove(), 250); }
        }
    };
}

HelpDesk.modals.novoProjeto = function() {
    const html = `
    <form id="formNovoProjeto" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Nome do Projeto *</label>
            <input type="text" name="nome" class="form-input" required>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-textarea" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Responsável</label>
            <input type="hidden" name="responsavel_id" id="novoProjetoResponsavelId" value="">
            <div class="nt-user-pick" id="novoProjetoResponsavelBtn" style="padding:8px 12px; border:1px solid var(--gray-200); border-radius:var(--radius); cursor:pointer;"
                 onclick="UserPicker.open(TECNICOS_INDEX, document.getElementById('novoProjetoResponsavelId').value, function(id,nome){ document.getElementById('novoProjetoResponsavelId').value=id; var btn=document.getElementById('novoProjetoResponsavelBtn'); btn.querySelector('.nt-user-name').textContent=nome||'Selecione...'; var av=btn.querySelector('.nt-user-avatar-sm'); av.innerHTML=nome?nome.substring(0,2).toUpperCase():'<i class=\\'fas fa-user-plus\\' style=\\'font-size:10px\\'></i>'; })">
                <span class="nt-user-avatar-sm"><i class="fas fa-user-plus" style="font-size:10px"></i></span>
                <span class="nt-user-name">Selecione...</span>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Prioridade</label>
            <select name="prioridade" class="form-select">
                <option value="baixa">Baixa</option>
                <option value="media" selected>Média</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Data Início</label>
            <input type="date" name="data_inicio" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Prazo</label>
            <input type="date" name="prazo" class="form-input">
        </div>
    </form>`;

    HelpDesk.showModal('Novo Projeto', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovoProjeto()"><i class="fas fa-save"></i> Criar</button>
    `);
};

function submitNovoProjeto() {
    const form = document.getElementById('formNovoProjeto');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';

    HelpDesk.api('POST', '/api/projetos.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Projeto criado!', 'success');
                HelpDesk.closeModal();
                setTimeout(() => location.reload(), 500);
            }
        });
}

// ===== TABS: Ativos / Concluídos / Cancelados =====
let currentProjTab = 'ativos';
function projSwitchTab(tab) {
    currentProjTab = tab;
    document.querySelectorAll('.proj-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    filtrarProjetos();
}

// Filtro JS client-side (with tabs)
function filtrarProjetos() {
    const status = document.getElementById('projStatusFilter').value.toLowerCase();
    const prioridade = document.getElementById('projPrioridadeFilter').value.toLowerCase();
    const busca = document.getElementById('projBuscaInput').value.toLowerCase();
    let count = 0;
    document.querySelectorAll('.proj-card').forEach(card => {
        const matchTab = card.dataset.tabGroup === currentProjTab;
        const matchStatus = !status || card.dataset.status === status;
        const matchPri = !prioridade || card.dataset.prioridade === prioridade;
        const matchBusca = !busca || card.dataset.nome.includes(busca);
        const show = matchTab && matchStatus && matchPri && matchBusca;
        card.style.display = show ? '' : 'none';
        if (show) count++;
    });
    // Show empty state
    let empty = document.getElementById('projTabEmpty');
    if (count === 0) {
        if (!empty) {
            empty = document.createElement('div');
            empty.id = 'projTabEmpty';
            empty.className = 'empty-state';
            const grid = document.querySelector('.proj-grid');
            if (grid) grid.parentNode.insertBefore(empty, grid.nextSibling);
        }
        const labels = {ativos:'ativos',concluidos:'concluídos',cancelados:'cancelados'};
        empty.innerHTML = `<i class="fas fa-folder-open"></i><h3>Nenhum projeto ${labels[currentProjTab]}</h3><p>Não há projetos nesta categoria.</p>`;
        empty.style.display = '';
    } else if (empty) {
        empty.style.display = 'none';
    }
}

// Apply initial tab filter on load
document.addEventListener('DOMContentLoaded', () => filtrarProjetos());

document.getElementById('projBuscaInput')?.addEventListener('input', filtrarProjetos);
</script>
