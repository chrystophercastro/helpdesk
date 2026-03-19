<?php
/**
 * View: Dashboard
 */
$chamadosAbertos = $stats['chamados']['abertos'] ?? 0;
$chamadosAndamento = $stats['chamados']['em_andamento'] ?? 0;
$chamadosResolvidos = $stats['chamados']['resolvidos'] ?? 0;
$projetosAtivos = $stats['projetos']['ativos'] ?? 0;
$tarefasAtrasadas = $stats['tarefas']['atrasadas'] ?? 0;
$tempoMedio = $stats['chamados']['tempo_medio']['horas'] ?? 0;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visão geral do sistema</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoChamado')">
            <i class="fas fa-plus"></i> Novo Chamado
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $chamadosAbertos ?></span>
            <span class="stat-label">Chamados Abertos</span>
        </div>
        <div class="stat-trend"><i class="fas fa-ticket-alt"></i></div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="fas fa-tools"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $chamadosAndamento ?></span>
            <span class="stat-label">Em Atendimento</span>
        </div>
        <div class="stat-trend"><i class="fas fa-spinner"></i></div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $chamadosResolvidos ?></span>
            <span class="stat-label">Resolvidos</span>
        </div>
        <div class="stat-trend"><i class="fas fa-arrow-up"></i></div>
    </div>
    <div class="stat-card stat-indigo">
        <div class="stat-icon"><i class="fas fa-project-diagram"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $projetosAtivos ?></span>
            <span class="stat-label">Projetos Ativos</span>
        </div>
        <div class="stat-trend"><i class="fas fa-rocket"></i></div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $tarefasAtrasadas ?></span>
            <span class="stat-label">Tarefas Atrasadas</span>
        </div>
        <div class="stat-trend"><i class="fas fa-clock"></i></div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= round($tempoMedio, 1) ?>h</span>
            <span class="stat-label">Tempo Médio Resolução</span>
        </div>
        <div class="stat-trend"><i class="fas fa-stopwatch"></i></div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Chamados por Categoria</h3>
        </div>
        <div class="card-body">
            <canvas id="chartCategoria" height="260"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar"></i> Chamados por Técnico</h3>
        </div>
        <div class="card-body">
            <canvas id="chartTecnico" height="260"></canvas>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line"></i> Chamados por Mês</h3>
        </div>
        <div class="card-body">
            <canvas id="chartMensal" height="260"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-doughnut"></i> Status dos Chamados</h3>
        </div>
        <div class="card-body">
            <canvas id="chartStatus" height="260"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados dos gráficos
    const porCategoria = <?= json_encode($stats['chamados']['por_categoria'] ?? []) ?>;
    const porTecnico = <?= json_encode($stats['chamados']['por_tecnico'] ?? []) ?>;
    const porMes = <?= json_encode($stats['chamados']['por_mes'] ?? []) ?>;
    const porStatus = <?= json_encode($stats['chamados']['por_status'] ?? []) ?>;

    const statusLabels = <?= json_encode(array_map(fn($s) => $s['label'], CHAMADO_STATUS)) ?>;
    const statusCores = <?= json_encode(array_map(fn($s) => $s['cor'], CHAMADO_STATUS)) ?>;

    // Chart: Categoria
    if (porCategoria.length > 0) {
        new Chart(document.getElementById('chartCategoria'), {
            type: 'doughnut',
            data: {
                labels: porCategoria.map(c => c.nome || 'Sem categoria'),
                datasets: [{
                    data: porCategoria.map(c => c.total),
                    backgroundColor: porCategoria.map(c => c.cor || '#6366F1'),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } }
            }
        });
    }

    // Chart: Técnico
    if (porTecnico.length > 0) {
        new Chart(document.getElementById('chartTecnico'), {
            type: 'bar',
            data: {
                labels: porTecnico.map(t => t.nome),
                datasets: [
                    {
                        label: 'Total',
                        data: porTecnico.map(t => t.total),
                        backgroundColor: '#6366F1',
                        borderRadius: 6
                    },
                    {
                        label: 'Resolvidos',
                        data: porTecnico.map(t => t.resolvidos),
                        backgroundColor: '#10B981',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Chart: Mensal
    const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    const dadosMensal = new Array(12).fill(0);
    porMes.forEach(m => { dadosMensal[m.mes - 1] = m.total; });

    new Chart(document.getElementById('chartMensal'), {
        type: 'line',
        data: {
            labels: meses,
            datasets: [{
                label: 'Chamados',
                data: dadosMensal,
                borderColor: '#6366F1',
                backgroundColor: 'rgba(99,102,241,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#6366F1'
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
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
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } }
        }
    });
});
</script>

<!-- Modal: Novo Chamado (Dashboard) -->
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
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';

    HelpDesk.api('POST', '/api/chamados.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Chamado criado com sucesso! Código: ' + resp.codigo, 'success');
                HelpDesk.closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao criar chamado', 'danger');
            }
        });
}

HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novoChamado = initNovoChamadoModal;
</script>
