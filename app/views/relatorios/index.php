<?php
/**
 * View: Relatórios
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Relatórios</h1>
        <p class="page-subtitle">Análises e métricas do HelpDesk</p>
    </div>
    <div class="page-actions">
        <form class="inline-form" method="GET">
            <input type="hidden" name="page" value="relatorios">
            <input type="date" name="data_inicio" class="form-input" value="<?= $_GET['data_inicio'] ?? date('Y-m-01') ?>">
            <span style="color:#64748b">até</span>
            <input type="date" name="data_fim" class="form-input" value="<?= $_GET['data_fim'] ?? date('Y-m-t') ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid cols-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#3B82F620;color:#3B82F6"><i class="fas fa-ticket-alt"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $relatorio['total_chamados'] ?? 0 ?></div>
            <div class="stat-label">Chamados no Período</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#10B98120;color:#10B981"><i class="fas fa-check-double"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $relatorio['chamados_resolvidos'] ?? 0 ?></div>
            <div class="stat-label">Resolvidos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#F59E0B20;color:#F59E0B"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $relatorio['tempo_medio'] ?? '0h' ?></div>
            <div class="stat-label">Tempo Médio Resolução</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#EF444420;color:#EF4444"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $relatorio['sla_estourados'] ?? 0 ?></div>
            <div class="stat-label">SLA Estourados</div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-top:24px">
    <!-- Chamados por Categoria -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-pie"></i> Por Categoria</h3></div>
        <div class="card-body"><canvas id="relCategoriaChart" height="250"></canvas></div>
    </div>

    <!-- Chamados por Mês -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Evolução Mensal</h3></div>
        <div class="card-body"><canvas id="relMensalChart" height="250"></canvas></div>
    </div>

    <!-- Desempenho por Técnico -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-users"></i> Desempenho por Técnico</h3></div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Técnico</th>
                        <th>Atribuídos</th>
                        <th>Resolvidos</th>
                        <th>Taxa</th>
                        <th>Tempo Médio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relatorio['por_tecnico'] ?? [] as $tec): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($tec['nome']) ?></strong></td>
                        <td><?= $tec['total'] ?></td>
                        <td><?= $tec['resolvidos'] ?></td>
                        <td>
                            <?php $taxa = $tec['total'] > 0 ? round(($tec['resolvidos']/$tec['total'])*100) : 0; ?>
                            <div class="progress-bar-container" style="width:80px;display:inline-flex">
                                <div class="progress-bar-fill" style="width:<?= $taxa ?>%;background:<?= $taxa >= 80 ? '#10B981' : ($taxa >= 50 ? '#F59E0B' : '#EF4444') ?>"></div>
                            </div>
                            <span style="font-size:0.8rem;margin-left:4px"><?= $taxa ?>%</span>
                        </td>
                        <td><?= $tec['tempo_medio'] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($relatorio['por_tecnico'])): ?>
                    <tr><td colspan="5" class="text-center text-muted">Sem dados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Por Prioridade -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-pie"></i> Por Prioridade</h3></div>
        <div class="card-body"><canvas id="relPrioridadeChart" height="250"></canvas></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Por Categoria
    const catData = <?= json_encode($relatorio['por_categoria'] ?? []) ?>;
    if (catData.length > 0) {
        new Chart(document.getElementById('relCategoriaChart'), {
            type: 'doughnut',
            data: {
                labels: catData.map(c => c.nome),
                datasets: [{
                    data: catData.map(c => c.total),
                    backgroundColor: ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4','#F97316','#6366F1','#14B8A6']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
        });
    }

    // Evolução Mensal
    const mesData = <?= json_encode($relatorio['por_mes'] ?? []) ?>;
    if (mesData.length > 0) {
        new Chart(document.getElementById('relMensalChart'), {
            type: 'bar',
            data: {
                labels: mesData.map(m => m.mes),
                datasets: [
                    { label: 'Abertos', data: mesData.map(m => m.abertos), backgroundColor: '#3B82F6' },
                    { label: 'Resolvidos', data: mesData.map(m => m.resolvidos), backgroundColor: '#10B981' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
        });
    }

    // Por Prioridade
    const priData = <?= json_encode($relatorio['por_prioridade'] ?? []) ?>;
    if (priData.length > 0) {
        const priCores = { critica: '#EF4444', alta: '#F97316', media: '#F59E0B', baixa: '#10B981' };
        new Chart(document.getElementById('relPrioridadeChart'), {
            type: 'doughnut',
            data: {
                labels: priData.map(p => p.prioridade.charAt(0).toUpperCase() + p.prioridade.slice(1)),
                datasets: [{ data: priData.map(p => p.total), backgroundColor: priData.map(p => priCores[p.prioridade] || '#6B7280') }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
        });
    }
});
</script>
