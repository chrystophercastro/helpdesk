<?php
/**
 * View: Sprint Detalhes com Burndown Chart
 */
if (!$sprint) { echo '<div class="alert alert-danger">Sprint não encontrado.</div>'; return; }
$statusLabels = ['planejamento'=>'Planejamento','ativa'=>'Ativa','concluida'=>'Concluída','cancelada'=>'Cancelada'];
$statusColors = ['planejamento'=>'#6B7280','ativa'=>'#3B82F6','concluida'=>'#10B981','cancelada'=>'#EF4444'];
$cor = $statusColors[$sprint['status']] ?? '#6B7280';
// Compute tarefas_em_andamento from tarefas list
$emAndamento = 0;
foreach ($tarefasSprint as $ts) {
    if (in_array($ts['coluna'], ['em_andamento', 'em_revisao', 'a_fazer'])) $emAndamento++;
}
$sprint['tarefas_em_andamento'] = $emAndamento;
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/index.php?page=sprints" class="btn btn-ghost btn-sm" style="margin-bottom:8px"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 class="page-title"><?= htmlspecialchars($sprint['nome']) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars($sprint['projeto_nome'] ?? '') ?> &bull; <?= formatarData($sprint['data_inicio'], 'd/m/Y') ?> - <?= formatarData($sprint['data_fim'], 'd/m/Y') ?></p>
    </div>
    <div class="page-actions">
        <span class="status-badge" style="background:<?= $cor ?>20;color:<?= $cor ?>;font-size:0.95rem;padding:6px 16px"><?= $statusLabels[$sprint['status']] ?></span>
        <?php if ($sprint['status'] === 'planejamento'): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="iniciar"><input type="hidden" name="sprint_id" value="<?= $sprint['id'] ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-play"></i> Iniciar Sprint</button></form>
        <?php elseif ($sprint['status'] === 'ativa'): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="concluir"><input type="hidden" name="sprint_id" value="<?= $sprint['id'] ?>">
        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Concluir Sprint</button></form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($sprint['meta'])): ?>
<div class="card" style="margin-bottom:24px">
    <div class="card-body">
        <h4 style="margin-bottom:8px"><i class="fas fa-bullseye" style="color:#F59E0B"></i> Meta do Sprint</h4>
        <p><?= nl2br(htmlspecialchars($sprint['meta'])) ?></p>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid cols-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#3B82F620;color:#3B82F6"><i class="fas fa-tasks"></i></div>
        <div class="stat-info"><div class="stat-value"><?= $sprint['total_tarefas'] ?? 0 ?></div><div class="stat-label">Total Tarefas</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#10B98120;color:#10B981"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><div class="stat-value"><?= $sprint['tarefas_concluidas'] ?? 0 ?></div><div class="stat-label">Concluídas</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#F59E0B20;color:#F59E0B"><i class="fas fa-spinner"></i></div>
        <div class="stat-info"><div class="stat-value"><?= $sprint['tarefas_em_andamento'] ?? 0 ?></div><div class="stat-label">Em Andamento</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#8B5CF620;color:#8B5CF6"><i class="fas fa-tachometer-alt"></i></div>
        <div class="stat-info">
            <?php
            $total = $sprint['total_tarefas'] ?? 0;
            $done = $sprint['tarefas_concluidas'] ?? 0;
            $pct = $total > 0 ? round(($done/$total)*100) : 0;
            ?>
            <div class="stat-value"><?= $pct ?>%</div><div class="stat-label">Progresso</div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-top:24px">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-line"></i> Burndown Chart</h3></div>
        <div class="card-body">
            <canvas id="burndownChart" height="250"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-list"></i> Tarefas do Sprint</h3></div>
        <div class="card-body" style="max-height:400px;overflow-y:auto">
            <?php if (empty($tarefasSprint)): ?>
            <div class="empty-state-sm"><i class="fas fa-clipboard-list"></i><p>Nenhuma tarefa neste sprint</p></div>
            <?php else: ?>
            <?php
            $statusTarefa = ['backlog'=>'#6B7280','a_fazer'=>'#3B82F6','em_andamento'=>'#F59E0B','em_revisao'=>'#8B5CF6','concluido'=>'#10B981'];
            $statusLabelT = ['backlog'=>'Backlog','a_fazer'=>'A Fazer','em_andamento'=>'Em Progresso','em_revisao'=>'Revisão','concluido'=>'Concluído'];
            ?>
            <?php foreach ($tarefasSprint as $t): ?>
            <div class="sprint-task-item">
                <div class="sprint-task-info">
                    <span class="status-dot" style="background:<?= $statusTarefa[$t['coluna']] ?? '#6B7280' ?>"></span>
                    <span class="sprint-task-title"><?= htmlspecialchars($t['titulo']) ?></span>
                </div>
                <span class="status-badge" style="background:<?= ($statusTarefa[$t['coluna']] ?? '#6B7280') ?>20;color:<?= $statusTarefa[$t['coluna']] ?? '#6B7280' ?>;font-size:0.75rem">
                    <?= $statusLabelT[$t['coluna']] ?? $t['coluna'] ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const burndownData = <?= json_encode($burndownData ?? []) ?>;
    if (burndownData.length > 0 && document.getElementById('burndownChart')) {
        const labels = burndownData.map(d => d.data);
        const ideal = burndownData.map(d => d.ideal);
        const real = burndownData.map(d => d.real);
        new Chart(document.getElementById('burndownChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Ideal',
                        data: ideal,
                        borderColor: '#94A3B8',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        pointRadius: 0,
                        tension: 0
                    },
                    {
                        label: 'Real',
                        data: real,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#3B82F6',
                        tension: 0.2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Tarefas Restantes' }, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
