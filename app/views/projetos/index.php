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
foreach ($projetos as $p) {
    if (in_array($p['status'], ['em_desenvolvimento', 'em_testes'])) $projetosAtivos++;
    if ($p['status'] === 'concluido') $projetosConcluidos++;
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
</div>

<!-- Filtros -->
<div class="proj-filters-bar">
    <div class="proj-filters-left">
        <h1 class="proj-page-title"><i class="fas fa-project-diagram"></i> Projetos</h1>
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
    ?>
    <div class="proj-card <?= $atrasado ? 'proj-card-late' : '' ?>"
         data-status="<?= $p['status'] ?>" data-prioridade="<?= $p['prioridade'] ?>"
         data-nome="<?= htmlspecialchars(strtolower($p['nome']), ENT_QUOTES, 'UTF-8', false) ?>"
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
HelpDesk.modals.novoProjeto = function() {
    let tecOpts = '<option value="">Selecione</option>';
    <?php foreach ($tecnicos as $t): ?>
    tecOpts += '<option value="<?= $t['id'] ?>"><?= addslashes($t['nome']) ?></option>';
    <?php endforeach; ?>

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
            <select name="responsavel_id" class="form-select">${tecOpts}</select>
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

// Filtro JS client-side
function filtrarProjetos() {
    const status = document.getElementById('projStatusFilter').value.toLowerCase();
    const prioridade = document.getElementById('projPrioridadeFilter').value.toLowerCase();
    const busca = document.getElementById('projBuscaInput').value.toLowerCase();
    document.querySelectorAll('.proj-card').forEach(card => {
        const matchStatus = !status || card.dataset.status === status;
        const matchPri = !prioridade || card.dataset.prioridade === prioridade;
        const matchBusca = !busca || card.dataset.nome.includes(busca);
        card.style.display = (matchStatus && matchPri && matchBusca) ? '' : 'none';
    });
}

document.getElementById('projBuscaInput')?.addEventListener('input', filtrarProjetos);
</script>
