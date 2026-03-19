<?php
/**
 * View: Sprints - Listagem
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sprints</h1>
        <p class="page-subtitle">Gestão de sprints ágeis</p>
    </div>
    <div class="page-actions">
        <select class="form-select" onchange="location.href='<?= BASE_URL ?>/index.php?page=sprints&projeto='+this.value">
            <option value="">Todos os Projetos</option>
            <?php foreach ($projetos as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($_GET['projeto'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoSprint')">
            <i class="fas fa-plus"></i> Novo Sprint
        </button>
    </div>
</div>

<?php if (!empty($sprintAtivo)): ?>
<div class="card sprint-ativo-card">
    <div class="card-header">
        <h3><i class="fas fa-bolt" style="color:#F59E0B"></i> Sprint Ativo: <?= htmlspecialchars($sprintAtivo['nome']) ?></h3>
        <a href="<?= BASE_URL ?>/index.php?page=sprints&acao=ver&id=<?= $sprintAtivo['id'] ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-chart-line"></i> Burndown
        </a>
    </div>
    <div class="card-body">
        <div class="sprint-meta">
            <span><i class="far fa-calendar"></i> <?= formatarData($sprintAtivo['data_inicio'], 'd/m/Y') ?> - <?= formatarData($sprintAtivo['data_fim'], 'd/m/Y') ?></span>
            <span><i class="fas fa-tasks"></i> <?= $sprintAtivo['total_tarefas'] ?? 0 ?> tarefas</span>
        </div>
        <?php
        $total = $sprintAtivo['total_tarefas'] ?? 0;
        $concluidas = $sprintAtivo['tarefas_concluidas'] ?? 0;
        $pct = $total > 0 ? round(($concluidas / $total) * 100) : 0;
        ?>
        <div class="progress-bar-container" style="margin-top:12px">
            <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <div class="progress-label"><?= $pct ?>% concluído (<?= $concluidas ?>/<?= $total ?>)</div>
    </div>
</div>
<?php endif; ?>

<div class="sprints-grid">
    <?php if (empty($sprints)): ?>
    <div class="empty-state">
        <i class="fas fa-running"></i>
        <h3>Nenhum sprint encontrado</h3>
        <p>Crie seu primeiro sprint para organizar o trabalho em iterações</p>
    </div>
    <?php else: ?>
    <?php foreach ($sprints as $sprint): ?>
    <?php
    $statusColors = ['planejamento'=>'#6B7280','ativa'=>'#3B82F6','concluida'=>'#10B981','cancelada'=>'#EF4444'];
    $statusLabels = ['planejamento'=>'Planejamento','ativa'=>'Ativa','concluida'=>'Concluída','cancelada'=>'Cancelada'];
    $cor = $statusColors[$sprint['status']] ?? '#6B7280';
    $totalT = $sprint['total_tarefas'] ?? 0;
    $conclT = $sprint['tarefas_concluidas'] ?? 0;
    $pctS = $totalT > 0 ? round(($conclT / $totalT) * 100) : 0;
    ?>
    <div class="card sprint-card">
        <div class="card-body">
            <div class="sprint-card-header">
                <h3>
                    <a href="<?= BASE_URL ?>/index.php?page=sprints&acao=ver&id=<?= $sprint['id'] ?>"><?= htmlspecialchars($sprint['nome']) ?></a>
                </h3>
                <span class="status-badge" style="background:<?= $cor ?>20;color:<?= $cor ?>"><?= $statusLabels[$sprint['status']] ?></span>
            </div>
            <p class="text-muted" style="font-size:0.85rem"><?= htmlspecialchars($sprint['projeto_nome'] ?? '') ?></p>
            <div class="sprint-meta" style="margin-top:8px">
                <span><i class="far fa-calendar"></i> <?= formatarData($sprint['data_inicio'], 'd/m') ?> - <?= formatarData($sprint['data_fim'], 'd/m') ?></span>
            </div>
            <div class="progress-bar-container" style="margin-top:12px">
                <div class="progress-bar-fill" style="width:<?= $pctS ?>%"></div>
            </div>
            <div class="sprint-card-footer">
                <span><?= $conclT ?>/<?= $totalT ?> tarefas</span>
                <span><?= $pctS ?>%</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novoSprint = function() {
    let projOpts = '';
    <?php foreach ($projetos as $p): ?>
    projOpts += '<option value="<?= $p['id'] ?>"><?= addslashes(htmlspecialchars($p['nome'])) ?></option>';
    <?php endforeach; ?>

    const html = `
    <form id="formNovoSprint" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Nome do Sprint *</label>
            <input type="text" name="nome" class="form-input" required placeholder="Ex: Sprint 1">
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Projeto *</label>
            <select name="projeto_id" class="form-select" required>${projOpts}</select>
        </div>
        <div class="form-group">
            <label class="form-label">Data Início *</label>
            <input type="date" name="data_inicio" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Data Fim *</label>
            <input type="date" name="data_fim" class="form-input" required>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Meta</label>
            <textarea name="meta" class="form-textarea" rows="3" placeholder="Objetivo deste sprint"></textarea>
        </div>
    </form>`;

    HelpDesk.showModal('Novo Sprint', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovoSprint()"><i class="fas fa-save"></i> Criar Sprint</button>
    `);
};

function submitNovoSprint() {
    const form = document.getElementById('formNovoSprint');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';
    HelpDesk.api('POST', '/api/sprints.php', data).then(resp => {
        if (resp.success) { HelpDesk.toast('Sprint criado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}
</script>
