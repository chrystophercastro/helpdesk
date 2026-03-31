<?php
/**
 * View: Dashboard — Layout Profissional
 */
$chamadosAbertos   = $stats['chamados']['abertos'] ?? 0;
$chamadosAndamento = $stats['chamados']['em_andamento'] ?? 0;
$chamadosResolvidos = $stats['chamados']['resolvidos'] ?? 0;
$chamadosFechados  = $stats['chamados']['fechados'] ?? 0;
$chamadosTotal     = $stats['chamados']['total'] ?? 0;
$projetosAtivos    = $stats['projetos']['ativos'] ?? 0;
$tarefasAtrasadas  = $stats['tarefas']['atrasadas'] ?? 0;
$tarefasTotal      = $stats['tarefas']['total'] ?? 0;
$tempoMedio        = $stats['chamados']['tempo_medio']['horas'] ?? 0;
$slaEstourados     = $stats['chamados']['sla_estourados'] ?? 0;
$slaTaxa           = $stats['chamados']['sla_taxa'] ?? 100;
$abertosHoje       = $stats['chamados']['abertos_hoje'] ?? 0;
$resolvidosHoje    = $stats['chamados']['resolvidos_hoje'] ?? 0;
$comprasPendentes  = $stats['compras']['pendentes'] ?? 0;
$comprasValor      = $stats['compras']['valor_total'] ?? 0;
$satisfacao        = $stats['chamados']['satisfacao'] ?? ['media' => 0, 'total' => 0];
$satisfMedia       = round($satisfacao['media'] ?? 0, 1);
$satisfTotal       = $satisfacao['total'] ?? 0;
$porPrioridade     = $stats['chamados']['por_prioridade'] ?? [];
$porCanal          = $stats['chamados']['por_canal'] ?? [];
$recentes          = $stats['chamados']['recentes'] ?? [];
$tarefasCols       = $stats['tarefas']['colunas'] ?? [];
$statusList        = CHAMADO_STATUS;
$prioridadeList    = PRIORIDADES;

$priMap = [];
foreach ($porPrioridade as $pp) $priMap[$pp['prioridade']] = $pp['total'];

$canalMap = [];
foreach ($porCanal as $pc) $canalMap[$pc['canal']] = $pc['total'];
?>

<!-- Dashboard Header -->
<div class="dash-header">
    <div class="dash-header-left">
        <h1 class="dash-title">Dashboard</h1>
        <p class="dash-subtitle">
            <i class="far fa-calendar-alt"></i>
            <?= date('d/m/Y') ?> — 
            <?php if (!isAdmin() && getDeptFilter()): ?>
                Visão do seu departamento
            <?php else: ?>
                Visão geral do sistema
            <?php endif; ?>
        </p>
    </div>
    <div class="dash-header-right">
        <div class="dash-today-badge">
            <div class="dash-today-item">
                <span class="dash-today-num"><?= $abertosHoje ?></span>
                <span class="dash-today-label">Abertos hoje</span>
            </div>
            <div class="dash-today-sep"></div>
            <div class="dash-today-item">
                <span class="dash-today-num dash-today-green"><?= $resolvidosHoje ?></span>
                <span class="dash-today-label">Resolvidos hoje</span>
            </div>
        </div>
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('dashboard_briefing')">
            <i class="fas fa-robot"></i> Briefing IA
        </button>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoChamado')">
            <i class="fas fa-plus"></i> Novo Chamado
        </button>
    </div>
</div>

<!-- KPI Cards Row 1 -->
<div class="dash-kpi-grid">
    <div class="dash-kpi dash-kpi-blue">
        <div class="dash-kpi-icon"><i class="fas fa-folder-open"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $chamadosAbertos ?></span>
            <span class="dash-kpi-label">Abertos</span>
        </div>
        <div class="dash-kpi-spark"><i class="fas fa-inbox"></i></div>
    </div>
    <div class="dash-kpi dash-kpi-purple">
        <div class="dash-kpi-icon"><i class="fas fa-tools"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $chamadosAndamento ?></span>
            <span class="dash-kpi-label">Em Atendimento</span>
        </div>
        <div class="dash-kpi-spark"><i class="fas fa-cogs"></i></div>
    </div>
    <div class="dash-kpi dash-kpi-green">
        <div class="dash-kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $chamadosResolvidos ?></span>
            <span class="dash-kpi-label">Resolvidos</span>
        </div>
        <div class="dash-kpi-spark"><i class="fas fa-trophy"></i></div>
    </div>
    <div class="dash-kpi dash-kpi-amber">
        <div class="dash-kpi-icon"><i class="fas fa-stopwatch"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= round($tempoMedio, 1) ?>h</span>
            <span class="dash-kpi-label">Tempo Médio</span>
        </div>
        <div class="dash-kpi-spark"><i class="fas fa-hourglass-half"></i></div>
    </div>
</div>

<!-- KPI Cards Row 2 -->
<div class="dash-kpi-grid dash-kpi-grid-5">
    <div class="dash-kpi dash-kpi-<?= $slaTaxa >= 80 ? 'green' : ($slaTaxa >= 50 ? 'amber' : 'red') ?>">
        <div class="dash-kpi-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $slaTaxa ?>%</span>
            <span class="dash-kpi-label">SLA Cumprido</span>
        </div>
        <div class="dash-kpi-mini-bar">
            <div class="dash-kpi-mini-fill" style="width:<?= $slaTaxa ?>%"></div>
        </div>
    </div>
    <div class="dash-kpi dash-kpi-<?= $slaEstourados > 0 ? 'red' : 'gray' ?>">
        <div class="dash-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $slaEstourados ?></span>
            <span class="dash-kpi-label">SLA Estourado</span>
        </div>
        <?php if ($slaEstourados > 0): ?>
        <div class="dash-kpi-spark dash-kpi-pulse"><i class="fas fa-bell"></i></div>
        <?php endif; ?>
    </div>
    <div class="dash-kpi dash-kpi-indigo">
        <div class="dash-kpi-icon"><i class="fas fa-project-diagram"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $projetosAtivos ?></span>
            <span class="dash-kpi-label">Projetos Ativos</span>
        </div>
        <div class="dash-kpi-spark"><i class="fas fa-rocket"></i></div>
    </div>
    <div class="dash-kpi dash-kpi-<?= $tarefasAtrasadas > 0 ? 'red' : 'green' ?>">
        <div class="dash-kpi-icon"><i class="fas fa-tasks"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $tarefasAtrasadas ?></span>
            <span class="dash-kpi-label">Tarefas Atrasadas</span>
        </div>
        <div class="dash-kpi-spark"><i class="fas fa-clock"></i></div>
    </div>
    <div class="dash-kpi dash-kpi-teal">
        <div class="dash-kpi-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="dash-kpi-body">
            <span class="dash-kpi-value"><?= $comprasPendentes ?></span>
            <span class="dash-kpi-label">Compras Pendentes</span>
        </div>
        <div class="dash-kpi-spark"><i class="fas fa-receipt"></i></div>
    </div>
</div>

<!-- Row: Departamentos -->
<?php $porDept = $stats['chamados']['por_departamento'] ?? []; ?>
<?php if (!empty($porDept)): ?>
<div class="dash-card" style="margin-bottom:24px">
    <div class="dash-card-header">
        <h3><i class="fas fa-building"></i> Chamados por Departamento</h3>
        <a href="<?= BASE_URL ?>/index.php?page=departamentos" class="dash-card-link">Gerenciar <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="dash-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
            <?php foreach ($porDept as $dp): ?>
            <a href="<?= BASE_URL ?>/index.php?page=chamados&departamento_id=<?= $dp['id'] ?>" class="dash-dept-card" style="text-decoration:none;display:block;padding:16px;border-radius:12px;background:<?= $dp['cor'] ?>08;border:1px solid <?= $dp['cor'] ?>20;transition:all 0.2s;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:<?= $dp['cor'] ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:14px;">
                        <i class="<?= htmlspecialchars($dp['icone']) ?>"></i>
                    </div>
                    <div>
                        <span style="font-size:13px;font-weight:700;color:var(--gray-800);display:block;"><?= htmlspecialchars($dp['sigla']) ?></span>
                        <span style="font-size:11px;color:var(--gray-500);"><?= htmlspecialchars($dp['nome']) ?></span>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;text-align:center;">
                    <div>
                        <span style="font-size:18px;font-weight:700;color:#F59E0B;display:block;"><?= (int)$dp['abertos'] ?></span>
                        <span style="font-size:10px;color:var(--gray-500);font-weight:600;">Abertos</span>
                    </div>
                    <div>
                        <span style="font-size:18px;font-weight:700;color:#3B82F6;display:block;"><?= (int)$dp['em_andamento'] ?></span>
                        <span style="font-size:10px;color:var(--gray-500);font-weight:600;">Andamento</span>
                    </div>
                    <div>
                        <span style="font-size:18px;font-weight:700;color:#10B981;display:block;"><?= (int)$dp['resolvidos'] ?></span>
                        <span style="font-size:10px;color:var(--gray-500);font-weight:600;">Resolvidos</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Row: Evolução + Prioridades/Canais -->
<div class="dash-grid-2-1">
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-chart-area"></i> Evolução de Chamados</h3>
            <span class="dash-card-badge"><?= date('Y') ?></span>
        </div>
        <div class="dash-card-body" style="height:300px">
            <canvas id="chartMensal"></canvas>
        </div>
    </div>

    <div class="dash-sidebar-stack">
        <div class="dash-card">
            <div class="dash-card-header">
                <h3><i class="fas fa-flag"></i> Por Prioridade</h3>
                <span class="dash-card-badge"><?= array_sum($priMap) ?> abertos</span>
            </div>
            <div class="dash-card-body dash-pri-list">
                <?php
                $priOrder = ['critica','alta','media','baixa'];
                $priColors = ['critica'=>'#EF4444','alta'=>'#F97316','media'=>'#F59E0B','baixa'=>'#10B981'];
                $priIcons = ['critica'=>'fa-fire','alta'=>'fa-arrow-up','media'=>'fa-minus','baixa'=>'fa-arrow-down'];
                $totalPri = max(1, array_sum($priMap));
                foreach ($priOrder as $pk):
                    $pv = $priMap[$pk] ?? 0;
                    $pPct = round(($pv / $totalPri) * 100);
                ?>
                <div class="dash-pri-row">
                    <div class="dash-pri-label">
                        <i class="fas <?= $priIcons[$pk] ?>" style="color:<?= $priColors[$pk] ?>"></i>
                        <span><?= $prioridadeList[$pk]['label'] ?? ucfirst($pk) ?></span>
                    </div>
                    <div class="dash-pri-bar-wrap">
                        <div class="dash-pri-bar" style="width:<?= $pPct ?>%;background:<?= $priColors[$pk] ?>"></div>
                    </div>
                    <span class="dash-pri-val"><?= $pv ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-header">
                <h3><i class="fas fa-satellite-dish"></i> Canais de Entrada</h3>
            </div>
            <div class="dash-card-body dash-canal-grid">
                <?php
                $canalIcons = ['portal'=>'fa-globe','interno'=>'fa-desktop','whatsapp'=>'fa-whatsapp','email'=>'fa-envelope'];
                $canalLabels = ['portal'=>'Portal','interno'=>'Interno','whatsapp'=>'WhatsApp','email'=>'Email'];
                $canalColors = ['portal'=>'#3B82F6','interno'=>'#6366F1','whatsapp'=>'#25D366','email'=>'#F59E0B'];
                foreach (['portal','interno','whatsapp','email'] as $ck):
                    $cv = $canalMap[$ck] ?? 0;
                ?>
                <div class="dash-canal-item">
                    <div class="dash-canal-icon" style="background:<?= $canalColors[$ck] ?>15;color:<?= $canalColors[$ck] ?>">
                        <i class="<?= $ck === 'whatsapp' ? 'fab' : 'fas' ?> <?= $canalIcons[$ck] ?>"></i>
                    </div>
                    <span class="dash-canal-num"><?= $cv ?></span>
                    <span class="dash-canal-label"><?= $canalLabels[$ck] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row: 3 Charts -->
<div class="dash-grid-3">
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-chart-pie"></i> Status</h3>
        </div>
        <div class="dash-card-body" style="height:260px">
            <canvas id="chartStatus"></canvas>
        </div>
    </div>
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-tags"></i> Categorias</h3>
        </div>
        <div class="dash-card-body" style="height:260px">
            <canvas id="chartCategoria"></canvas>
        </div>
    </div>
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-user-tie"></i> Técnicos</h3>
        </div>
        <div class="dash-card-body" style="height:260px">
            <canvas id="chartTecnico"></canvas>
        </div>
    </div>
</div>

<!-- Row: Recentes + Sidebar -->
<div class="dash-grid-2-1">
    <div class="dash-card">
        <div class="dash-card-header">
            <h3><i class="fas fa-bolt"></i> Chamados Recentes</h3>
            <a href="<?= BASE_URL ?>/index.php?page=chamados" class="dash-card-link">Ver todos <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="dash-card-body dash-card-body-flush">
            <div class="dash-recent-list">
                <?php if (empty($recentes)): ?>
                <div style="text-align:center;padding:40px;color:var(--gray-400)">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                    Nenhum chamado registrado
                </div>
                <?php endif; ?>
                <?php foreach ($recentes as $r):
                    $rSt = $statusList[$r['status']] ?? ['label'=>$r['status'],'cor'=>'#6B7280','icone'=>'fas fa-circle'];
                    $rPri = $prioridadeList[$r['prioridade']] ?? ['label'=>$r['prioridade'],'cor'=>'#6B7280'];
                ?>
                <a href="<?= BASE_URL ?>/index.php?page=chamados&action=ver&id=<?= $r['id'] ?>" class="dash-recent-item">
                    <div class="dash-recent-pri" style="background:<?= $rPri['cor'] ?>"></div>
                    <div class="dash-recent-body">
                        <div class="dash-recent-top">
                            <span class="dash-recent-code"><?= $r['codigo'] ?></span>
                            <span class="dash-recent-status" style="background:<?= $rSt['cor'] ?>15;color:<?= $rSt['cor'] ?>">
                                <i class="<?= $rSt['icone'] ?>"></i> <?= $rSt['label'] ?>
                            </span>
                        </div>
                        <p class="dash-recent-title"><?= htmlspecialchars($r['titulo']) ?></p>
                        <div class="dash-recent-meta">
                            <span><i class="far fa-user"></i> <?= htmlspecialchars($r['solicitante_nome'] ?? 'N/A') ?></span>
                            <span><i class="far fa-clock"></i> <?= tempoRelativo($r['data_abertura']) ?></span>
                            <?php if (!empty($r['tecnico_nome'])): ?>
                            <span><i class="fas fa-user-cog"></i> <?= htmlspecialchars($r['tecnico_nome']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="dash-sidebar-stack">
        <!-- Mini Kanban -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3><i class="fas fa-columns"></i> Kanban</h3>
                <a href="<?= BASE_URL ?>/index.php?page=kanban" class="dash-card-link">Abrir <i class="fas fa-external-link-alt"></i></a>
            </div>
            <div class="dash-card-body">
                <?php
                $kanbanCols = KANBAN_COLUNAS ?? [];
                $kanbanColors = ['backlog'=>'#94A3B8','a_fazer'=>'#3B82F6','em_progresso'=>'#F59E0B','em_revisao'=>'#8B5CF6','concluido'=>'#10B981'];
                $totalTarefas = max(1, $tarefasTotal);
                ?>
                <div class="dash-kanban-mini">
                    <?php foreach ($kanbanCols as $kk => $kv):
                        $kt = $tarefasCols[$kk] ?? 0;
                        $kPct = round(($kt / $totalTarefas) * 100);
                    ?>
                    <div class="dash-kanban-row">
                        <div class="dash-kanban-label">
                            <span class="dash-kanban-dot" style="background:<?= $kanbanColors[$kk] ?? '#6B7280' ?>"></span>
                            <span><?= $kv['label'] ?? ucfirst(str_replace('_', ' ', $kk)) ?></span>
                        </div>
                        <div class="dash-kanban-bar-wrap">
                            <div class="dash-kanban-bar" style="width:<?= $kPct ?>%;background:<?= $kanbanColors[$kk] ?? '#6B7280' ?>"></div>
                        </div>
                        <span class="dash-kanban-val"><?= $kt ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Satisfação -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3><i class="fas fa-star"></i> Satisfação</h3>
            </div>
            <div class="dash-card-body dash-satisfacao">
                <div class="dash-satisfacao-score">
                    <span class="dash-satisfacao-num"><?= $satisfMedia ?: '—' ?></span>
                    <div class="dash-satisfacao-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="<?= $i <= round($satisfMedia) ? 'fas' : 'far' ?> fa-star" style="color:<?= $i <= round($satisfMedia) ? '#F59E0B' : '#E5E7EB' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="dash-satisfacao-total"><?= $satisfTotal ?> avaliações</span>
                </div>
            </div>
        </div>

        <!-- Compras -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h3><i class="fas fa-dollar-sign"></i> Compras</h3>
                <a href="<?= BASE_URL ?>/index.php?page=compras" class="dash-card-link">Ver <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="dash-card-body" style="text-align:center;padding:20px 16px">
                <span style="font-size:1.5rem;font-weight:700;color:var(--gray-900)">
                    R$ <?= number_format($comprasValor ?? 0, 2, ',', '.') ?>
                </span>
                <p style="font-size:0.8rem;color:var(--gray-500);margin-top:4px">Valor total estimado</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const porCategoria = <?= json_encode($stats['chamados']['por_categoria'] ?? []) ?>;
    const porTecnico = <?= json_encode($stats['chamados']['por_tecnico'] ?? []) ?>;
    const porMes = <?= json_encode($stats['chamados']['por_mes'] ?? []) ?>;
    const porStatus = <?= json_encode($stats['chamados']['por_status'] ?? []) ?>;
    const statusLabels = <?= json_encode(array_map(fn($s) => $s['label'], CHAMADO_STATUS)) ?>;
    const statusCores = <?= json_encode(array_map(fn($s) => $s['cor'], CHAMADO_STATUS)) ?>;

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(51,65,85,0.5)' : '#F3F4F6';
    const tickColor = isDark ? '#94A3B8' : undefined;
    const pointBorder = isDark ? '#111827' : '#fff';

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 14, usePointStyle: true, pointStyleWidth: 10, font: { size: 11, family: 'Inter' }, color: isDark ? '#CBD5E1' : undefined }
            }
        }
    };

    // Chart: Evolução Mensal
    const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    const dadosMensal = new Array(12).fill(0);
    porMes.forEach(m => { dadosMensal[m.mes - 1] = m.total; });
    const mesAtual = new Date().getMonth();

    new Chart(document.getElementById('chartMensal'), {
        type: 'line',
        data: {
            labels: meses,
            datasets: [{
                label: 'Chamados',
                data: dadosMensal,
                borderColor: '#6366F1',
                backgroundColor: (ctx) => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
                    g.addColorStop(0, 'rgba(99,102,241,0.2)');
                    g.addColorStop(1, 'rgba(99,102,241,0.01)');
                    return g;
                },
                fill: true,
                tension: 0.4,
                pointRadius: dadosMensal.map((_, i) => i === mesAtual ? 6 : 3),
                pointBackgroundColor: dadosMensal.map((_, i) => i === mesAtual ? '#4F46E5' : '#6366F1'),
                pointBorderColor: pointBorder,
                pointBorderWidth: 2,
                borderWidth: 3
            }]
        },
        options: {
            ...chartDefaults,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: { size: 11 }, color: tickColor } },
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: tickColor } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });

    // Chart: Status
    const statusData = {};
    porStatus.forEach(s => { statusData[s.status] = s.total; });
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: Object.values(statusLabels),
            datasets: [{
                data: Object.keys(statusLabels).map(k => statusData[k] || 0),
                backgroundColor: Object.values(statusCores),
                borderWidth: 0, hoverOffset: 6
            }]
        },
        options: { ...chartDefaults, cutout: '65%' }
    });

    // Chart: Categoria
    if (porCategoria.length > 0) {
        const palette = ['#6366F1','#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4','#F97316','#14B8A6'];
        new Chart(document.getElementById('chartCategoria'), {
            type: 'doughnut',
            data: {
                labels: porCategoria.map(c => c.nome || 'Sem categoria'),
                datasets: [{
                    data: porCategoria.map(c => c.total),
                    backgroundColor: porCategoria.map((c, i) => c.cor || palette[i % palette.length]),
                    borderWidth: 0, hoverOffset: 6
                }]
            },
            options: { ...chartDefaults, cutout: '65%' }
        });
    } else {
        document.getElementById('chartCategoria').parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--gray-400)"><div style="text-align:center"><i class="fas fa-chart-pie" style="font-size:2rem;display:block;margin-bottom:8px"></i>Sem dados</div></div>';
    }

    // Chart: Técnico
    if (porTecnico.length > 0) {
        new Chart(document.getElementById('chartTecnico'), {
            type: 'bar',
            data: {
                labels: porTecnico.map(t => t.nome.split(' ')[0]),
                datasets: [
                    { label: 'Total', data: porTecnico.map(t => t.total), backgroundColor: '#6366F1', borderRadius: 6, maxBarThickness: 28 },
                    { label: 'Resolvidos', data: porTecnico.map(t => t.resolvidos), backgroundColor: '#10B981', borderRadius: 6, maxBarThickness: 28 }
                ]
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { stepSize: 1, font: { size: 11 }, color: tickColor } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: tickColor } }
                }
            }
        });
    } else {
        document.getElementById('chartTecnico').parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--gray-400)"><div style="text-align:center"><i class="fas fa-chart-bar" style="font-size:2rem;display:block;margin-bottom:8px"></i>Sem dados</div></div>';
    }
});
</script>

<!-- Modal: Novo Chamado -->
<script>
function initNovoChamadoModal() {
    const html = `
    <form id="formNovoChamado" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Título *</label>
            <input type="text" name="titulo" class="form-input" required placeholder="Descreva brevemente o problema">
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Descrição *</label>
            <textarea name="descricao" class="form-textarea" rows="4" required placeholder="Detalhe o problema"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Nome do Solicitante *</label>
            <input type="text" name="nome" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Telefone (WhatsApp) *</label>
            <input type="text" name="telefone" class="form-input" required placeholder="5562999999999">
        </div>
        <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Categoria</label>
            <select name="categoria_id" class="form-select">
                <option value="">Selecione</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Impacto</label>
            <select name="impacto" class="form-select">
                <option value="baixo">Baixo</option>
                <option value="medio" selected>Médio</option>
                <option value="alto">Alto</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Urgência</label>
            <select name="urgencia" class="form-select">
                <option value="baixa">Baixa</option>
                <option value="media" selected>Média</option>
                <option value="alta">Alta</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Técnico Responsável</label>
            <select name="tecnico_id" class="form-select">
                <option value="">Não atribuído</option>
                <?php foreach ($tecnicos as $tec): ?>
                <option value="<?= $tec['id'] ?>"><?= $tec['nome'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Ativo (Inventário)</label>
            <select name="ativo_id" class="form-select">
                <option value="">Nenhum</option>
                <?php foreach ($ativos as $a): ?>
                <option value="<?= $a['id'] ?>"><?= $a['nome'] ?> (<?= $a['numero_patrimonio'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Tags</label>
            <input type="text" name="tags" class="form-input" placeholder="Separe por vírgula">
        </div>
    </form>`;
    
    HelpDesk.showModal('Novo Chamado', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovoChamado()"><i class="fas fa-save"></i> Criar Chamado</button>
    `);
}

function submitNovoChamado() {
    const form = document.getElementById('formNovoChamado');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    const formData = new FormData(form);
    formData.append('action', 'criar');
    
    // Converter campos vazios de ID para não enviar
    ['categoria_id', 'tecnico_id', 'ativo_id'].forEach(f => {
        if (formData.get(f) === '') formData.delete(f);
    });
    
    const btn = event?.target || document.querySelector('.modal-footer .btn-primary');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...'; }
    
    HelpDesk.api('POST', '/api/chamados.php', formData, true)
        .then(data => {
            if (data && data.success) {
                HelpDesk.toast('Chamado criado com sucesso! Código: ' + data.codigo, 'success');
                HelpDesk.closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                HelpDesk.toast(data?.error || 'Erro ao criar chamado', 'danger');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Criar Chamado'; }
            }
        }).catch(err => {
            HelpDesk.toast('Erro ao criar chamado: ' + (err.message || err), 'danger');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Criar Chamado'; }
        });
}

HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novoChamado = initNovoChamadoModal;
</script>
