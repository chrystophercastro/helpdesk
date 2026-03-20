<?php
/**
 * View: Lista de Chamados — Layout Avançado com Filtros e Paginação
 */
$user = currentUser();
$statusList = CHAMADO_STATUS;
$prioridadeList = PRIORIDADES;

// Dados de paginação
$pag = $paginacao ?? ['total' => count($chamados ?? []), 'pagina' => 1, 'por_pagina' => 25, 'total_paginas' => 1];
$filtros = $filtrosAtivos ?? [];

// Contar chamados por status para os cards de resumo
$totalChamados = $pag['total'];
$countAberto = 0; $countAndamento = 0; $countResolvido = 0; $countAguardando = 0;
foreach ($chamados as $c) {
    if ($c['status'] === 'aberto') $countAberto++;
    elseif (in_array($c['status'], ['em_analise','em_atendimento'])) $countAndamento++;
    elseif (in_array($c['status'], ['resolvido','fechado'])) $countResolvido++;
    elseif ($c['status'] === 'aguardando_usuario') $countAguardando++;
}

// Verificar se há filtros ativos
$temFiltros = false;
foreach ($filtros as $k => $v) {
    if (!empty($v) && $k !== 'ordem') { $temFiltros = true; break; }
}

$canaisDisponiveis = [
    'portal' => 'Portal',
    'interno' => 'Interno',
    'whatsapp' => 'WhatsApp',
    'email' => 'E-mail',
];

$ordensDisponiveis = [
    'recentes' => 'Mais Recentes',
    'antigos' => 'Mais Antigos',
    'prioridade' => 'Prioridade',
    'status' => 'Status',
    'titulo' => 'Título A-Z',
    'atualizados' => 'Última Atualização',
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Chamados</h1>
        <p class="page-subtitle">Gerenciamento de chamados de suporte</p>
    </div>
    <div class="page-actions" style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-outline btn-sm" onclick="exportarChamados()" title="Exportar">
            <i class="fas fa-download"></i> Exportar
        </button>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoChamado')">
            <i class="fas fa-plus"></i> Novo Chamado
        </button>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="chamados-summary">
    <div class="summary-card" onclick="aplicarFiltroRapido('status','')" style="cursor:pointer" title="Ver todos">
        <div class="summary-icon" style="background:#EFF6FF;color:#3B82F6"><i class="fas fa-ticket-alt"></i></div>
        <div class="summary-data">
            <span class="summary-number"><?= $totalChamados ?></span>
            <span class="summary-label">Total</span>
        </div>
    </div>
    <div class="summary-card" onclick="aplicarFiltroRapido('status','aberto')" style="cursor:pointer" title="Filtrar abertos">
        <div class="summary-icon" style="background:#FEF3C7;color:#D97706"><i class="fas fa-exclamation-circle"></i></div>
        <div class="summary-data">
            <span class="summary-number"><?= $countAberto ?></span>
            <span class="summary-label">Abertos</span>
        </div>
    </div>
    <div class="summary-card" onclick="aplicarFiltroRapido('status','em_atendimento')" style="cursor:pointer" title="Filtrar em andamento">
        <div class="summary-icon" style="background:#DBEAFE;color:#2563EB"><i class="fas fa-spinner"></i></div>
        <div class="summary-data">
            <span class="summary-number"><?= $countAndamento ?></span>
            <span class="summary-label">Em Andamento</span>
        </div>
    </div>
    <div class="summary-card" onclick="aplicarFiltroRapido('status','aguardando_usuario')" style="cursor:pointer" title="Filtrar aguardando">
        <div class="summary-icon" style="background:#FEF9C3;color:#CA8A04"><i class="fas fa-clock"></i></div>
        <div class="summary-data">
            <span class="summary-number"><?= $countAguardando ?></span>
            <span class="summary-label">Aguardando</span>
        </div>
    </div>
    <div class="summary-card" onclick="aplicarFiltroRapido('status','resolvido')" style="cursor:pointer" title="Filtrar resolvidos">
        <div class="summary-icon" style="background:#D1FAE5;color:#059669"><i class="fas fa-check-circle"></i></div>
        <div class="summary-data">
            <span class="summary-number"><?= $countResolvido ?></span>
            <span class="summary-label">Resolvidos</span>
        </div>
    </div>
</div>

<!-- Filtros Avançados -->
<div class="card mb-4">
    <div class="card-body" style="padding:16px 20px">
        <form class="chamados-filters" id="filterForm" method="GET">
            <input type="hidden" name="page" value="chamados">
            <input type="hidden" name="ordem" id="inputOrdem" value="<?= htmlspecialchars($filtros['ordem'] ?? 'recentes') ?>">
            
            <!-- Linha principal de filtros -->
            <div class="chamados-filters-row">
                <div class="chamados-filter-item">
                    <?php if (!isAdmin() && getDeptFilter()): ?>
                    <select name="departamento_id" class="form-select" disabled style="opacity:0.7">
                        <?php foreach (($departamentosLista ?? []) as $dep): ?>
                        <option value="<?= $dep['id'] ?>" selected><?= htmlspecialchars($dep['sigla'] . ' - ' . $dep['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="departamento_id" value="<?= getDeptFilter() ?>">
                    <?php else: ?>
                    <select name="departamento_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos Departamentos</option>
                        <?php foreach (($departamentosLista ?? []) as $dep): ?>
                        <option value="<?= $dep['id'] ?>" <?= ($filtros['departamento_id'] ?? '') == $dep['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dep['sigla'] . ' - ' . $dep['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="chamados-filter-item">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos os Status</option>
                        <?php foreach ($statusList as $key => $s): ?>
                        <option value="<?= $key ?>" <?= ($filtros['status'] ?? '') === $key ? 'selected' : '' ?>><?= $s['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chamados-filter-item">
                    <select name="prioridade" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas Prioridades</option>
                        <?php foreach ($prioridadeList as $key => $p): ?>
                        <option value="<?= $key ?>" <?= ($filtros['prioridade'] ?? '') === $key ? 'selected' : '' ?>><?= $p['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chamados-filter-item">
                    <select name="categoria_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas Categorias</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filtros['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= $cat['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chamados-filter-item">
                    <select name="tecnico_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos Técnicos</option>
                        <?php foreach ($tecnicos as $tec): ?>
                        <option value="<?= $tec['id'] ?>" <?= ($filtros['tecnico_id'] ?? '') == $tec['id'] ? 'selected' : '' ?>><?= $tec['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chamados-filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="busca" class="form-input" placeholder="Buscar por código, título, descrição, solicitante..." value="<?= htmlspecialchars($filtros['busca'] ?? '') ?>">
                </div>
            </div>

            <!-- Linha secundária: filtros avançados -->
            <div class="chamados-filters-row-advanced" id="advancedFilters" style="<?= $temFiltros ? '' : 'display:none' ?>">
                <div class="chamados-filter-item">
                    <select name="canal" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos Canais</option>
                        <?php foreach ($canaisDisponiveis as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($filtros['canal'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chamados-filter-item">
                    <select name="urgencia" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas Urgências</option>
                        <option value="alta" <?= ($filtros['urgencia'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="media" <?= ($filtros['urgencia'] ?? '') === 'media' ? 'selected' : '' ?>>Média</option>
                        <option value="baixa" <?= ($filtros['urgencia'] ?? '') === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                    </select>
                </div>
                <div class="chamados-filter-item">
                    <select name="impacto" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos Impactos</option>
                        <option value="alto" <?= ($filtros['impacto'] ?? '') === 'alto' ? 'selected' : '' ?>>Alto</option>
                        <option value="medio" <?= ($filtros['impacto'] ?? '') === 'medio' ? 'selected' : '' ?>>Médio</option>
                        <option value="baixo" <?= ($filtros['impacto'] ?? '') === 'baixo' ? 'selected' : '' ?>>Baixo</option>
                    </select>
                </div>
                <div class="chamados-filter-item">
                    <select name="sla_vencido" class="form-select" onchange="this.form.submit()">
                        <option value="">SLA: Todos</option>
                        <option value="1" <?= ($filtros['sla_vencido'] ?? '') === '1' ? 'selected' : '' ?>>SLA Vencido</option>
                    </select>
                </div>
                <div class="chamados-filter-item">
                    <input type="date" name="data_inicio" class="form-input" style="font-size:0.85rem;padding:8px 12px" value="<?= htmlspecialchars($filtros['data_inicio'] ?? '') ?>" onchange="this.form.submit()" title="Data Início">
                </div>
                <div class="chamados-filter-item">
                    <input type="date" name="data_fim" class="form-input" style="font-size:0.85rem;padding:8px 12px" value="<?= htmlspecialchars($filtros['data_fim'] ?? '') ?>" onchange="this.form.submit()" title="Data Fim">
                </div>
            </div>
        </form>

        <!-- Barra de ações -->
        <div class="chamados-toolbar">
            <div class="chamados-toolbar-left">
                <button type="button" class="btn btn-sm btn-ghost" onclick="toggleAdvancedFilters()" id="btnToggleFilters">
                    <i class="fas fa-sliders-h"></i> <span id="toggleFiltersLabel"><?= $temFiltros ? 'Ocultar filtros' : 'Mais filtros' ?></span>
                </button>
                <?php if ($temFiltros): ?>
                <a href="?page=chamados" class="btn btn-sm btn-ghost" style="color:var(--danger)">
                    <i class="fas fa-times"></i> Limpar filtros
                </a>
                <?php endif; ?>
            </div>
            <div class="chamados-toolbar-right">
                <div class="chamados-sort-group">
                    <label style="font-size:0.8rem;color:var(--gray-500);margin-right:6px"><i class="fas fa-sort"></i> Ordenar:</label>
                    <select class="form-select form-select-sm" onchange="document.getElementById('inputOrdem').value=this.value;document.getElementById('filterForm').submit()" style="width:auto;font-size:0.8rem;padding:4px 8px">
                        <?php foreach ($ordensDisponiveis as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($filtros['ordem'] ?? 'recentes') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chamados-perpage-group">
                    <label style="font-size:0.8rem;color:var(--gray-500);margin-right:6px">Exibir:</label>
                    <select class="form-select form-select-sm" onchange="window.location.href=updateUrlParam('por_pagina',this.value)" style="width:auto;font-size:0.8rem;padding:4px 8px">
                        <?php foreach ([10, 25, 50, 100] as $pp): ?>
                        <option value="<?= $pp ?>" <?= $pag['por_pagina'] == $pp ? 'selected' : '' ?>><?= $pp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filtros ativos (chips) -->
        <?php if ($temFiltros): ?>
        <div class="chamados-active-filters">
            <?php if (!empty($filtros['departamento_id'])): ?>
                <?php $depNome = ''; foreach (($departamentosLista ?? []) as $dep) { if ($dep['id'] == $filtros['departamento_id']) { $depNome = $dep['sigla'] . ' - ' . $dep['nome']; break; } } ?>
                <span class="filter-chip" style="background:#EDE9FE;color:#6366F1"><?= $depNome ?: 'Depto #'.$filtros['departamento_id'] ?> <a href="javascript:void(0)" onclick="removerFiltro('departamento_id')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['status'])): ?>
                <span class="filter-chip"><?= $statusList[$filtros['status']]['label'] ?? $filtros['status'] ?> <a href="javascript:void(0)" onclick="removerFiltro('status')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['prioridade'])): ?>
                <span class="filter-chip"><?= $prioridadeList[$filtros['prioridade']]['label'] ?? $filtros['prioridade'] ?> <a href="javascript:void(0)" onclick="removerFiltro('prioridade')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['categoria_id'])): ?>
                <?php $catNome = ''; foreach ($categorias as $cat) { if ($cat['id'] == $filtros['categoria_id']) { $catNome = $cat['nome']; break; } } ?>
                <span class="filter-chip"><?= $catNome ?: 'Categoria #'.$filtros['categoria_id'] ?> <a href="javascript:void(0)" onclick="removerFiltro('categoria_id')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['tecnico_id'])): ?>
                <?php $tecNome = ''; foreach ($tecnicos as $tec) { if ($tec['id'] == $filtros['tecnico_id']) { $tecNome = $tec['nome']; break; } } ?>
                <span class="filter-chip"><?= $tecNome ?: 'Técnico #'.$filtros['tecnico_id'] ?> <a href="javascript:void(0)" onclick="removerFiltro('tecnico_id')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['canal'])): ?>
                <span class="filter-chip"><?= $canaisDisponiveis[$filtros['canal']] ?? $filtros['canal'] ?> <a href="javascript:void(0)" onclick="removerFiltro('canal')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['urgencia'])): ?>
                <span class="filter-chip">Urgência: <?= ucfirst($filtros['urgencia']) ?> <a href="javascript:void(0)" onclick="removerFiltro('urgencia')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['impacto'])): ?>
                <span class="filter-chip">Impacto: <?= ucfirst($filtros['impacto']) ?> <a href="javascript:void(0)" onclick="removerFiltro('impacto')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['sla_vencido'])): ?>
                <span class="filter-chip" style="background:#FEE2E2;color:#DC2626">SLA Vencido <a href="javascript:void(0)" onclick="removerFiltro('sla_vencido')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['data_inicio'])): ?>
                <span class="filter-chip">De: <?= date('d/m/Y', strtotime($filtros['data_inicio'])) ?> <a href="javascript:void(0)" onclick="removerFiltro('data_inicio')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['data_fim'])): ?>
                <span class="filter-chip">Até: <?= date('d/m/Y', strtotime($filtros['data_fim'])) ?> <a href="javascript:void(0)" onclick="removerFiltro('data_fim')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
            <?php if (!empty($filtros['busca'])): ?>
                <span class="filter-chip"><i class="fas fa-search"></i> "<?= htmlspecialchars($filtros['busca']) ?>" <a href="javascript:void(0)" onclick="removerFiltro('busca')"><i class="fas fa-times"></i></a></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Info de resultado -->
<div class="chamados-result-info">
    <span>
        <?php 
        $inicio = (($pag['pagina'] - 1) * $pag['por_pagina']) + 1;
        $fim = min($pag['pagina'] * $pag['por_pagina'], $pag['total']);
        if ($pag['total'] > 0):
        ?>
            Exibindo <strong><?= $inicio ?>–<?= $fim ?></strong> de <strong><?= $pag['total'] ?></strong> chamados
        <?php else: ?>
            Nenhum chamado encontrado
        <?php endif; ?>
    </span>
</div>

<!-- Tabela de Chamados -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Depto</th>
                    <th>Título</th>
                    <th>Solicitante</th>
                    <th>Categoria</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Técnico</th>
                    <th>Canal</th>
                    <th>Data</th>
                    <th width="60">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($chamados)): ?>
                <tr>
                    <td colspan="11" class="text-center py-4">
                        <div class="empty-state-sm">
                            <i class="fas fa-ticket-alt"></i>
                            <p>Nenhum chamado encontrado</p>
                            <?php if ($temFiltros): ?>
                            <a href="?page=chamados" class="btn btn-sm btn-ghost" style="margin-top:8px">Limpar filtros</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($chamados as $c):
                    $pri = $prioridadeList[$c['prioridade']] ?? ['cor' => '#94A3B8', 'icone' => 'fas fa-minus', 'label' => $c['prioridade']];
                    $st = $statusList[$c['status']] ?? ['cor' => '#94A3B8', 'icone' => 'fas fa-circle', 'label' => $c['status']];
                    $canalIcones = ['portal' => 'fas fa-globe', 'interno' => 'fas fa-building', 'whatsapp' => 'fab fa-whatsapp', 'email' => 'fas fa-envelope'];
                    $canalIcone = $canalIcones[$c['canal'] ?? ''] ?? 'fas fa-circle';
                    $canalLabel = $canaisDisponiveis[$c['canal'] ?? ''] ?? '-';
                    
                    // SLA vencido?
                    $slaVencido = !empty($c['sla_resposta_vencido']) || !empty($c['sla_resolucao_vencido']);
                ?>
                <tr class="table-row-clickable <?= $slaVencido ? 'sla-vencido-row' : '' ?>" onclick="window.location='<?= BASE_URL ?>/index.php?page=chamados&action=ver&id=<?= $c['id'] ?>'">
                    <td>
                        <span class="code-badge"><?= $c['codigo'] ?></span>
                        <?php if ($slaVencido): ?>
                            <span class="sla-badge-sm" title="SLA vencido"><i class="fas fa-exclamation-triangle"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($c['departamento_sigla'])): ?>
                        <span class="tag" style="background:<?= $c['departamento_cor'] ?? '#6366F1' ?>15;color:<?= $c['departamento_cor'] ?? '#6366F1' ?>;border:1px solid <?= $c['departamento_cor'] ?? '#6366F1' ?>30;font-weight:700;font-size:11px">
                            <?= htmlspecialchars($c['departamento_sigla']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="chamado-cell-title"><?= htmlspecialchars($c['titulo']) ?></div>
                    </td>
                    <td>
                        <span class="chamado-cell-solicitante"><?= htmlspecialchars($c['solicitante_nome'] ?? '-') ?></span>
                    </td>
                    <td>
                        <?php if (!empty($c['categoria_nome'])): ?>
                        <span class="tag" style="background:<?= $c['categoria_cor'] ?? '#3B82F6' ?>15;color:<?= $c['categoria_cor'] ?? '#3B82F6' ?>;border:1px solid <?= $c['categoria_cor'] ?? '#3B82F6' ?>30">
                            <?= $c['categoria_nome'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge" style="background:<?= $pri['cor'] ?>15;color:<?= $pri['cor'] ?>">
                            <i class="<?= $pri['icone'] ?>"></i> <?= $pri['label'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge" style="background:<?= $st['cor'] ?>15;color:<?= $st['cor'] ?>">
                            <i class="<?= $st['icone'] ?>"></i> <?= $st['label'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="chamado-cell-tecnico">
                            <?php if (!empty($c['tecnico_nome'])): ?>
                                <?= htmlspecialchars($c['tecnico_nome']) ?>
                            <?php else: ?>
                                <span class="text-muted" style="font-style:italic">Não atribuído</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <span class="chamado-canal-badge" title="<?= $canalLabel ?>">
                            <i class="<?= $canalIcone ?>"></i>
                        </span>
                    </td>
                    <td>
                        <span class="chamado-cell-date" title="<?= formatarData($c['data_abertura'], 'd/m/Y H:i:s') ?>">
                            <?= formatarData($c['data_abertura'], 'd/m/Y H:i') ?>
                        </span>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <a href="<?= BASE_URL ?>/index.php?page=chamados&action=ver&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<?php if ($pag['total_paginas'] > 1): ?>
<div class="chamados-pagination">
    <div class="pagination-info">
        Página <strong><?= $pag['pagina'] ?></strong> de <strong><?= $pag['total_paginas'] ?></strong>
    </div>
    <div class="pagination-controls">
        <?php if ($pag['pagina'] > 1): ?>
            <a href="<?= buildPaginationUrl(1) ?>" class="pagination-btn" title="Primeira página"><i class="fas fa-angle-double-left"></i></a>
            <a href="<?= buildPaginationUrl($pag['pagina'] - 1) ?>" class="pagination-btn" title="Anterior"><i class="fas fa-angle-left"></i></a>
        <?php else: ?>
            <span class="pagination-btn disabled"><i class="fas fa-angle-double-left"></i></span>
            <span class="pagination-btn disabled"><i class="fas fa-angle-left"></i></span>
        <?php endif; ?>

        <?php
        $inicio_pag = max(1, $pag['pagina'] - 2);
        $fim_pag = min($pag['total_paginas'], $pag['pagina'] + 2);
        if ($inicio_pag > 1): ?>
            <a href="<?= buildPaginationUrl(1) ?>" class="pagination-btn">1</a>
            <?php if ($inicio_pag > 2): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $inicio_pag; $p <= $fim_pag; $p++): ?>
            <?php if ($p == $pag['pagina']): ?>
                <span class="pagination-btn active"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= buildPaginationUrl($p) ?>" class="pagination-btn"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($fim_pag < $pag['total_paginas']): ?>
            <?php if ($fim_pag < $pag['total_paginas'] - 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
            <a href="<?= buildPaginationUrl($pag['total_paginas']) ?>" class="pagination-btn"><?= $pag['total_paginas'] ?></a>
        <?php endif; ?>

        <?php if ($pag['pagina'] < $pag['total_paginas']): ?>
            <a href="<?= buildPaginationUrl($pag['pagina'] + 1) ?>" class="pagination-btn" title="Próxima"><i class="fas fa-angle-right"></i></a>
            <a href="<?= buildPaginationUrl($pag['total_paginas']) ?>" class="pagination-btn" title="Última página"><i class="fas fa-angle-double-right"></i></a>
        <?php else: ?>
            <span class="pagination-btn disabled"><i class="fas fa-angle-right"></i></span>
            <span class="pagination-btn disabled"><i class="fas fa-angle-double-right"></i></span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
function buildPaginationUrl($pagina) {
    $params = $_GET;
    $params['pg'] = $pagina;
    return '?' . http_build_query($params);
}
?>

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
            <input type="text" name="telefone" class="form-input" required placeholder="5562999999999" value="55">
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
        <div class="form-group col-span-2">
            <label class="form-label">Anexos</label>
            <input type="file" name="anexos[]" class="form-input" multiple>
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

// === Funções de filtros ===
function toggleAdvancedFilters() {
    const el = document.getElementById('advancedFilters');
    const lbl = document.getElementById('toggleFiltersLabel');
    if (el.style.display === 'none') {
        el.style.display = '';
        lbl.textContent = 'Ocultar filtros';
    } else {
        el.style.display = 'none';
        lbl.textContent = 'Mais filtros';
    }
}

function aplicarFiltroRapido(campo, valor) {
    const url = new URL(window.location.href);
    if (campo === 'status') {
        url.searchParams.delete('status');
        url.searchParams.delete('pg');
    }
    if (valor) {
        url.searchParams.set(campo, valor);
    } else {
        url.searchParams.delete(campo);
    }
    url.searchParams.set('page', 'chamados');
    window.location.href = url.toString();
}

function removerFiltro(campo) {
    const url = new URL(window.location.href);
    url.searchParams.delete(campo);
    url.searchParams.delete('pg');
    window.location.href = url.toString();
}

function updateUrlParam(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    url.searchParams.delete('pg');
    return url.toString();
}

function exportarChamados() {
    // Exportar CSV dos dados visíveis na tabela
    const rows = document.querySelectorAll('.table tbody tr');
    let csv = 'Código;Título;Solicitante;Categoria;Prioridade;Status;Técnico;Canal;Data\n';
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 10) {
            const vals = [
                cells[0]?.textContent?.trim(),
                cells[1]?.textContent?.trim(),
                cells[2]?.textContent?.trim(),
                cells[3]?.textContent?.trim(),
                cells[4]?.textContent?.trim(),
                cells[5]?.textContent?.trim(),
                cells[6]?.textContent?.trim(),
                cells[7]?.textContent?.trim(),
                cells[8]?.textContent?.trim(),
            ];
            csv += vals.map(v => '"' + (v||'').replace(/"/g,'""') + '"').join(';') + '\n';
        }
    });
    if (csv.split('\n').length <= 2) {
        HelpDesk.toast('Nenhum dado para exportar', 'warning');
        return;
    }
    const blob = new Blob(['\ufeff' + csv], {type: 'text/csv;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'chamados_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
    HelpDesk.toast('Exportação concluída!', 'success');
}

// Submeter busca com Enter
document.querySelector('input[name="busca"]')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('filterForm').submit();
    }
});

// Atalho: Ctrl+K para focar na busca
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('input[name="busca"]')?.focus();
    }
});

// Registrar modal
HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novoChamado = initNovoChamadoModal;
</script>
