<?php
/**
 * View: Detalhe do Projeto — Layout Profissional
 */
if (!$projeto) {
    echo '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Projeto não encontrado</h3></div>';
    return;
}
$statusList = PROJETO_STATUS;
$prioridadeList = PRIORIDADES;
$st = $statusList[$projeto['status']];
$pri = $prioridadeList[$projeto['prioridade']];
$progresso = (int)($projeto['progresso'] ?? 0);
$progressClass = $progresso >= 80 ? 'proj-prog-high' : ($progresso >= 40 ? 'proj-prog-mid' : 'proj-prog-low');
$atrasado = ($projeto['prazo'] && strtotime($projeto['prazo']) < time() && !in_array($projeto['status'], ['concluido', 'cancelado']));

require_once __DIR__ . '/../../models/Tarefa.php';
$tarefaModel = new Tarefa();
$tarefas = $tarefaModel->listarPorProjeto($projeto['id']);

$tarefasConcluidas = 0;
$tarefasTotal = count($tarefas);
foreach ($tarefas as $t) {
    if ($t['coluna'] === 'concluido') $tarefasConcluidas++;
}
?>

<!-- Header do Projeto -->
<div class="pv-header">
    <div class="pv-header-top">
        <a href="<?= BASE_URL ?>/index.php?page=projetos" class="btn-back">
            <i class="fas fa-arrow-left"></i> Voltar aos projetos
        </a>
        <div class="pv-header-actions">
            <a href="<?= BASE_URL ?>/index.php?page=kanban&projeto_id=<?= $projeto['id'] ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-columns"></i> Ver Kanban
            </a>
        </div>
    </div>

    <div class="pv-title-block">
        <div class="pv-badges">
            <span class="pv-badge-status" style="background:<?= $st['cor'] ?>12;color:<?= $st['cor'] ?>;border:1px solid <?= $st['cor'] ?>35">
                <?= $st['label'] ?>
            </span>
            <span class="pv-badge-priority" style="background:<?= $pri['cor'] ?>12;color:<?= $pri['cor'] ?>;border:1px solid <?= $pri['cor'] ?>35">
                <i class="<?= $pri['icone'] ?>"></i> <?= $pri['label'] ?>
            </span>
            <?php if ($atrasado): ?>
            <span class="pv-badge-late"><i class="fas fa-clock"></i> Atrasado</span>
            <?php endif; ?>
        </div>
        <h1 class="pv-title"><?= htmlspecialchars($projeto['nome'], ENT_QUOTES, 'UTF-8', false) ?></h1>
        <div class="pv-meta">
            <span><i class="far fa-calendar-alt"></i> Criado em <?= formatarData($projeto['criado_em'] ?? $projeto['data_inicio']) ?></span>
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($projeto['responsavel_nome'] ?? 'Sem responsável', ENT_QUOTES, 'UTF-8', false) ?></span>
            <span><i class="fas fa-tasks"></i> <?= $tarefasConcluidas ?>/<?= $tarefasTotal ?> tarefas</span>
        </div>
    </div>

    <!-- Barra de Progresso -->
    <div class="pv-progress-section">
        <div class="pv-progress-header">
            <span class="pv-progress-label">Progresso Geral</span>
            <span class="pv-progress-pct <?= $progressClass ?>"><?= $progresso ?>%</span>
        </div>
        <div class="pv-progress-track">
            <div class="pv-progress-fill <?= $progressClass ?>" style="width:<?= $progresso ?>%"></div>
        </div>
    </div>
</div>

<!-- Grid Principal -->
<div class="pv-grid">
    <!-- ===================== MAIN COLUMN ===================== -->
    <div class="pv-main">

        <!-- Status Controls -->
        <div class="pv-card">
            <div class="pv-card-header">
                <h3><i class="fas fa-cog"></i> Alterar Status</h3>
            </div>
            <div class="pv-card-body">
                <div class="pv-status-options">
                    <?php foreach ($statusList as $key => $s): ?>
                    <button class="pv-status-btn <?= $projeto['status'] === $key ? 'active' : '' ?>"
                            style="--btn-color:<?= $s['cor'] ?>"
                            onclick="atualizarProjeto(<?= $projeto['id'] ?>, 'status', '<?= $key ?>')">
                        <?= $s['label'] ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Descrição -->
        <div class="pv-card">
            <div class="pv-card-header">
                <h3><i class="fas fa-file-alt"></i> Descrição</h3>
            </div>
            <div class="pv-card-body">
                <div class="pv-description">
                    <?= nl2br(htmlspecialchars($projeto['descricao'] ?? 'Sem descrição adicionada.', ENT_QUOTES, 'UTF-8', false)) ?>
                </div>
            </div>
        </div>

        <!-- Tarefas — Notion Style -->
        <div class="pv-card" id="pvTarefasCard">
            <div class="pv-card-header">
                <h3><i class="fas fa-tasks"></i> Tarefas <span class="pv-count-badge"><?= $tarefasTotal ?></span></h3>
                <div style="display:flex;gap:8px;align-items:center">
                    <div class="nt-filter-pills">
                        <button class="nt-pill active" data-filter="all" onclick="ntFilterTasks('all',this)">Todas</button>
                        <button class="nt-pill" data-filter="pending" onclick="ntFilterTasks('pending',this)">Pendentes</button>
                        <button class="nt-pill" data-filter="done" onclick="ntFilterTasks('done',this)">Concluídas</button>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="ntToggleNewTask()">
                        <i class="fas fa-plus"></i> Nova Tarefa
                    </button>
                </div>
            </div>
            <div class="pv-card-body p-0">
                <!-- Inline new task form (hidden) -->
                <div class="nt-new-task" id="ntNewTask" style="display:none">
                    <div class="nt-new-task-row">
                        <input type="text" id="ntNewTitle" class="nt-new-input" placeholder="Nome da tarefa..." autofocus>
                        <select id="ntNewColuna" class="nt-new-select">
                            <?php foreach (KANBAN_COLUNAS as $k => $c): ?>
                            <option value="<?= $k ?>"><?= $c['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="ntNewPrioridade" class="nt-new-select">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Média</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </select>
                        <input type="hidden" id="ntNewResponsavel" value="">
                        <button type="button" class="nt-user-pick nt-new-select" id="ntNewResponsavelBtn"
                                onclick="UserPicker.open(TECNICOS, '', function(id,nome){ document.getElementById('ntNewResponsavel').value=id; var btn=document.getElementById('ntNewResponsavelBtn'); btn.querySelector('.nt-user-name').textContent=nome||'Responsável'; var av=btn.querySelector('.nt-user-avatar-sm'); av.innerHTML=nome?nome.substring(0,2).toUpperCase():'<i class=\'fas fa-user-plus\' style=\'font-size:9px\'></i>'; })">
                            <span class="nt-user-avatar-sm"><i class="fas fa-user-plus" style="font-size:9px"></i></span>
                            <span class="nt-user-name">Responsável</span>
                        </button>
                        <input type="date" id="ntNewPrazo" class="nt-new-select">
                        <button class="btn btn-primary btn-sm" onclick="ntCreateTask()"><i class="fas fa-check"></i></button>
                        <button class="btn btn-secondary btn-sm" onclick="ntToggleNewTask()"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <?php if (empty($tarefas)): ?>
                <div class="pv-empty-mini">
                    <i class="fas fa-clipboard-list"></i>
                    <p>Nenhuma tarefa criada. Clique em "Nova Tarefa" para começar.</p>
                </div>
                <?php else: ?>
                <!-- Notion-style task list -->
                <div class="nt-task-list" id="ntTaskList">
                    <div class="nt-task-header">
                        <div class="nt-th-check"></div>
                        <div class="nt-th-title">Tarefa</div>
                        <div class="nt-th-status">Status</div>
                        <div class="nt-th-priority">Prioridade</div>
                        <div class="nt-th-assignee">Responsável</div>
                        <div class="nt-th-date">Prazo</div>
                    </div>
                    <?php foreach ($tarefas as $t): ?>
                    <?php
                        $colunas = KANBAN_COLUNAS;
                        $colInfo = $colunas[$t['coluna']] ?? ['label' => $t['coluna'], 'cor' => '#6b7280'];
                        $tarefaAtrasada = ($t['prazo'] && strtotime($t['prazo']) < time() && $t['coluna'] !== 'concluido');
                        $tPri = $prioridadeList[$t['prioridade']] ?? ['label' => $t['prioridade'], 'cor' => '#6b7280', 'icone' => 'fas fa-flag'];
                        $isDone = $t['coluna'] === 'concluido';
                    ?>
                    <div class="nt-task-row <?= $isDone ? 'nt-done' : '' ?> <?= $tarefaAtrasada ? 'nt-late' : '' ?>"
                         data-id="<?= $t['id'] ?>" data-status="<?= $isDone ? 'done' : 'pending' ?>">
                        <div class="nt-td-check" onclick="event.stopPropagation();ntToggleDone(<?= $t['id'] ?>, '<?= $isDone ? 'a_fazer' : 'concluido' ?>')">
                            <?php if ($isDone): ?>
                            <div class="nt-check checked"><i class="fas fa-check"></i></div>
                            <?php else: ?>
                            <div class="nt-check"></div>
                            <?php endif; ?>
                        </div>
                        <div class="nt-td-title" onclick="ntOpenTask(<?= $t['id'] ?>)">
                            <span class="nt-title-text"><?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8', false) ?></span>
                            <?php if (!empty($t['descricao'])): ?>
                            <i class="fas fa-align-left nt-has-desc" title="Tem descrição"></i>
                            <?php endif; ?>
                        </div>
                        <div class="nt-td-status" onclick="event.stopPropagation()">
                            <select class="nt-inline-select nt-status-sel" data-id="<?= $t['id'] ?>"
                                    onchange="ntUpdateField(<?= $t['id'] ?>,'coluna',this.value)"
                                    style="color:<?= $colInfo['cor'] ?>">
                                <?php foreach ($colunas as $ck => $cv): ?>
                                <option value="<?= $ck ?>" <?= $t['coluna'] === $ck ? 'selected' : '' ?>><?= $cv['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="nt-td-priority" onclick="event.stopPropagation()">
                            <select class="nt-inline-select nt-priority-sel" data-id="<?= $t['id'] ?>"
                                    onchange="ntUpdateField(<?= $t['id'] ?>,'prioridade',this.value)"
                                    style="color:<?= $tPri['cor'] ?>">
                                <?php foreach ($prioridadeList as $pk => $pv): ?>
                                <option value="<?= $pk ?>" <?= $t['prioridade'] === $pk ? 'selected' : '' ?>><?= $pv['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="nt-td-assignee" onclick="event.stopPropagation(); UserPicker.open(TECNICOS, '<?= (int)($t['responsavel_id'] ?? 0) ?>', function(id,nome){ ntUpdateField(<?= $t['id'] ?>, 'responsavel_id', id); var el=document.querySelector('.nt-task-row[data-id=\'<?= $t['id'] ?>\'] .nt-user-name'); if(el) el.textContent=nome||'—'; var av=document.querySelector('.nt-task-row[data-id=\'<?= $t['id'] ?>\'] .nt-user-avatar-sm'); if(av) av.textContent=nome?nome.substring(0,2).toUpperCase():''; })">
                            <div class="nt-user-pick">
                                <?php if (!empty($t['responsavel_nome'])): ?>
                                <span class="nt-user-avatar-sm"><?= strtoupper(mb_substr($t['responsavel_nome'], 0, 2)) ?></span>
                                <span class="nt-user-name"><?= htmlspecialchars($t['responsavel_nome'], ENT_QUOTES, 'UTF-8', false) ?></span>
                                <?php else: ?>
                                <span class="nt-user-avatar-sm"></span>
                                <span class="nt-user-name">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="nt-td-date <?= $tarefaAtrasada ? 'nt-late-text' : '' ?>" onclick="event.stopPropagation()">
                            <input type="date" class="nt-inline-date" value="<?= $t['prazo'] ?? '' ?>"
                                   data-id="<?= $t['id'] ?>"
                                   onchange="ntUpdateField(<?= $t['id'] ?>,'prazo',this.value)">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comentários -->
        <div class="pv-card">
            <div class="pv-card-header">
                <h3><i class="fas fa-comments"></i> Comentários <span class="pv-count-badge"><?= count($projeto['comentarios'] ?? []) ?></span></h3>
            </div>
            <div class="pv-card-body">
                <div class="pv-comments-list">
                    <?php if (empty($projeto['comentarios'])): ?>
                    <div class="pv-empty-mini">
                        <i class="fas fa-comment-slash"></i>
                        <p>Nenhum comentário ainda.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($projeto['comentarios'] as $com): ?>
                    <div class="pv-comment">
                        <div class="pv-comment-avatar">
                            <span><?= strtoupper(mb_substr($com['usuario_nome'] ?? 'S', 0, 2)) ?></span>
                        </div>
                        <div class="pv-comment-content">
                            <div class="pv-comment-header">
                                <strong><?= htmlspecialchars($com['usuario_nome'] ?? 'Sistema', ENT_QUOTES, 'UTF-8', false) ?></strong>
                                <time><?= tempoRelativo($com['criado_em']) ?></time>
                            </div>
                            <div class="pv-comment-text">
                                <?= nl2br(htmlspecialchars($com['conteudo'], ENT_QUOTES, 'UTF-8', false)) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form id="formComProjeto" class="pv-comment-form">
                    <div class="pv-comment-input-group">
                        <textarea id="comProjetoTexto" class="pv-comment-input" placeholder="Escreva um comentário..." rows="2"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i> Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================== SIDEBAR ===================== -->
    <div class="pv-sidebar">

        <!-- Informações -->
        <div class="pv-card">
            <div class="pv-card-header">
                <h3><i class="fas fa-info-circle"></i> Informações</h3>
            </div>
            <div class="pv-card-body p-0">
                <div class="pv-info-row pv-info-editable" onclick="UserPicker.open(TECNICOS, '<?= (int)($projeto['responsavel_id'] ?? 0) ?>', function(id,nome){ atualizarProjeto(<?= $projeto['id'] ?>, 'responsavel_id', id); })" title="Clique para alterar o responsável">
                    <span class="pv-info-label"><i class="fas fa-user-shield"></i> Dono / Responsável</span>
                    <span class="pv-info-value" id="pvResponsavelDisplay">
                        <?= htmlspecialchars($projeto['responsavel_nome'] ?? 'Não definido', ENT_QUOTES, 'UTF-8', false) ?>
                        <i class="fas fa-pen pv-edit-icon"></i>
                    </span>
                </div>
                <div class="pv-info-row">
                    <span class="pv-info-label"><i class="fas fa-flag"></i> Prioridade</span>
                    <span class="pv-info-value">
                        <span class="pv-badge-priority-sm" style="background:<?= $pri['cor'] ?>14;color:<?= $pri['cor'] ?>">
                            <i class="<?= $pri['icone'] ?>"></i> <?= $pri['label'] ?>
                        </span>
                    </span>
                </div>
                <div class="pv-info-row">
                    <span class="pv-info-label"><i class="fas fa-play-circle"></i> Início</span>
                    <span class="pv-info-value"><?= $projeto['data_inicio'] ? date('d/m/Y', strtotime($projeto['data_inicio'])) : 'Não definido' ?></span>
                </div>
                <div class="pv-info-row">
                    <span class="pv-info-label"><i class="fas fa-calendar-check"></i> Prazo</span>
                    <span class="pv-info-value <?= $atrasado ? 'pv-late-text' : '' ?>">
                        <?= $projeto['prazo'] ? date('d/m/Y', strtotime($projeto['prazo'])) : 'Não definido' ?>
                        <?php if ($atrasado): ?> <i class="fas fa-exclamation-circle"></i><?php endif; ?>
                    </span>
                </div>
                <div class="pv-info-row">
                    <span class="pv-info-label"><i class="fas fa-chart-pie"></i> Progresso</span>
                    <span class="pv-info-value"><strong><?= $progresso ?>%</strong></span>
                </div>
                <div class="pv-info-row">
                    <span class="pv-info-label"><i class="fas fa-tasks"></i> Tarefas</span>
                    <span class="pv-info-value"><?= $tarefasConcluidas ?> de <?= $tarefasTotal ?> concluídas</span>
                </div>
                <div class="pv-info-row">
                    <span class="pv-info-label"><i class="fas fa-users"></i> Equipe</span>
                    <span class="pv-info-value"><?= count($projeto['equipe']) ?> membro(s)</span>
                </div>
            </div>
        </div>

        <!-- Equipe -->
        <div class="pv-card">
            <div class="pv-card-header">
                <h3><i class="fas fa-users"></i> Equipe</h3>
                <button class="btn btn-outline btn-xs" onclick="abrirModalEquipe()" title="Gerenciar equipe">
                    <i class="fas fa-user-plus"></i> Adicionar
                </button>
            </div>
            <div class="pv-card-body" id="pvEquipeBody">
                <?php if (empty($projeto['equipe'])): ?>
                <div class="pv-empty-mini" id="pvEquipeEmpty">
                    <i class="fas fa-user-plus"></i>
                    <p>Nenhum membro na equipe.</p>
                </div>
                <?php else: ?>
                <div class="pv-team-list" id="pvTeamList">
                    <?php foreach ($projeto['equipe'] as $m): ?>
                    <div class="pv-team-member" id="pvMembro-<?= $m['usuario_id'] ?>">
                        <div class="pv-team-avatar">
                            <span><?= strtoupper(mb_substr($m['nome'], 0, 2)) ?></span>
                        </div>
                        <div class="pv-team-info">
                            <strong><?= htmlspecialchars($m['nome'], ENT_QUOTES, 'UTF-8', false) ?></strong>
                            <select class="pv-papel-select" onchange="alterarPapel(<?= $projeto['id'] ?>, <?= $m['usuario_id'] ?>, this.value)">
                                <option value="membro" <?= ($m['papel'] ?? 'membro') === 'membro' ? 'selected' : '' ?>>Membro</option>
                                <option value="lider" <?= ($m['papel'] ?? '') === 'lider' ? 'selected' : '' ?>>Líder</option>
                                <option value="desenvolvedor" <?= ($m['papel'] ?? '') === 'desenvolvedor' ? 'selected' : '' ?>>Desenvolvedor</option>
                                <option value="analista" <?= ($m['papel'] ?? '') === 'analista' ? 'selected' : '' ?>>Analista</option>
                                <option value="testador" <?= ($m['papel'] ?? '') === 'testador' ? 'selected' : '' ?>>Testador</option>
                            </select>
                        </div>
                        <button class="pv-team-remove" onclick="removerMembro(<?= $projeto['id'] ?>, <?= $m['usuario_id'] ?>, this)" title="Remover da equipe">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="pv-card">
            <div class="pv-card-header">
                <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
            </div>
            <div class="pv-card-body">
                <div class="pv-quick-actions">
                    <a href="<?= BASE_URL ?>/index.php?page=kanban&projeto_id=<?= $projeto['id'] ?>" class="pv-action-btn">
                        <i class="fas fa-columns"></i> Abrir Kanban
                    </a>
                    <?php if ($projeto['status'] !== 'concluido'): ?>
                    <button class="pv-action-btn pv-action-success" onclick="atualizarProjeto(<?= $projeto['id'] ?>, 'status', 'concluido')">
                        <i class="fas fa-check-circle"></i> Marcar Concluído
                    </button>
                    <?php endif; ?>
                    <?php if ($projeto['status'] !== 'cancelado'): ?>
                    <button class="pv-action-btn pv-action-danger" onclick="if(confirm('Cancelar este projeto?')) atualizarProjeto(<?= $projeto['id'] ?>, 'status', 'cancelado')">
                        <i class="fas fa-times-circle"></i> Cancelar Projeto
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const PROJETO_ID = <?= $projeto['id'] ?>;

// ===== ATUALIZAR CAMPO GENÉRICO =====
function atualizarProjeto(id, campo, valor) {
    const data = { action: 'atualizar', id: id };
    data[campo] = valor;
    HelpDesk.api('POST', '/api/projetos.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Projeto atualizado!', 'success');
                setTimeout(() => location.reload(), 800);
            }
        });
}

// ===== RESPONSÁVEL / DONO =====
// (handled by UserPicker modal)

// ===== EQUIPE: MODAL ADICIONAR MEMBRO =====
function abrirModalEquipe() {
    // Listar membros atuais para filtrar
    const membrosAtuais = [];
    document.querySelectorAll('[id^="pvMembro-"]').forEach(el => {
        membrosAtuais.push(parseInt(el.id.replace('pvMembro-', '')));
    });

    let optsHtml = '';
    <?php foreach ($tecnicos as $t): ?>
    if (!membrosAtuais.includes(<?= $t['id'] ?>)) {
        optsHtml += '<option value="<?= $t['id'] ?>"><?= addslashes(htmlspecialchars($t['nome'], ENT_QUOTES, 'UTF-8', false)) ?></option>';
    }
    <?php endforeach; ?>

    if (!optsHtml) {
        HelpDesk.toast('Todos os usuários já estão na equipe.', 'info');
        return;
    }

    const html = `
    <form id="formAddMembro" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Selecione o usuário</label>
            <select name="usuario_id" class="form-select" required>${optsHtml}</select>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Papel na equipe</label>
            <select name="funcao" class="form-select">
                <option value="membro">Membro</option>
                <option value="lider">Líder</option>
                <option value="desenvolvedor">Desenvolvedor</option>
                <option value="analista">Analista</option>
                <option value="testador">Testador</option>
            </select>
        </div>
    </form>`;

    HelpDesk.showModal('Adicionar à Equipe', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitAddMembro()"><i class="fas fa-user-plus"></i> Adicionar</button>
    `);
}

function submitAddMembro() {
    const form = document.getElementById('formAddMembro');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'adicionar_membro';
    data.projeto_id = PROJETO_ID;

    HelpDesk.api('POST', '/api/projetos.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Membro adicionado à equipe!', 'success');
                HelpDesk.closeModal();
                setTimeout(() => location.reload(), 500);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao adicionar membro', 'error');
            }
        });
}

// ===== EQUIPE: REMOVER MEMBRO =====
function removerMembro(projetoId, usuarioId, btn) {
    if (!confirm('Remover este membro da equipe?')) return;
    HelpDesk.api('POST', '/api/projetos.php', {
        action: 'remover_membro', projeto_id: projetoId, usuario_id: usuarioId
    }).then(resp => {
        if (resp.success) {
            const el = document.getElementById('pvMembro-' + usuarioId);
            if (el) {
                el.style.transition = 'opacity 0.3s, transform 0.3s';
                el.style.opacity = '0';
                el.style.transform = 'translateX(20px)';
                setTimeout(() => el.remove(), 300);
            }
            HelpDesk.toast('Membro removido da equipe', 'success');
        }
    });
}

// ===== EQUIPE: ALTERAR PAPEL =====
function alterarPapel(projetoId, usuarioId, novoPapel) {
    HelpDesk.api('POST', '/api/projetos.php', {
        action: 'alterar_papel', projeto_id: projetoId, usuario_id: usuarioId, papel: novoPapel
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Papel atualizado!', 'success');
        } else {
            HelpDesk.toast(resp.error || 'Erro ao alterar papel', 'error');
        }
    });
}

// ===== COMENTÁRIOS =====
document.getElementById('formComProjeto').addEventListener('submit', function(e) {
    e.preventDefault();
    const texto = document.getElementById('comProjetoTexto').value.trim();
    if (!texto) return;
    HelpDesk.api('POST', '/api/projetos.php', {
        action: 'comentar', projeto_id: PROJETO_ID, conteudo: texto
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Comentário adicionado!', 'success');
            setTimeout(() => location.reload(), 500);
        }
    });
});

// ============================================================
//  NOTION-STYLE TASK MANAGEMENT
// ============================================================

const KANBAN_COLS = <?= json_encode(KANBAN_COLUNAS) ?>;
const PRIORIDADES = <?= json_encode(PRIORIDADES) ?>;
const TECNICOS = <?= json_encode(array_map(fn($t) => ['id' => $t['id'], 'nome' => $t['nome'], 'email' => $t['email'] ?? ''], $tecnicos)) ?>;

// ============================================================
//  GLOBAL USER PICKER MODAL
// ============================================================
const UserPicker = {
    _overlay: null,
    _callback: null,
    _currentValue: null,
    _users: [],

    open(users, currentValue, callback) {
        this._users = users || TECNICOS;
        this._callback = callback;
        this._currentValue = String(currentValue || '');
        this._render();
    },

    _render() {
        // Remove existing
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
            listHtml += `
            <div class="user-picker-item ${sel}" data-id="${u.id}" data-nome="${(u.nome||'').replace(/"/g,'&quot;')}" data-search="${(u.nome||'').toLowerCase()} ${(u.email||'').toLowerCase()}">
                <div class="user-picker-avatar">${initials}</div>
                <div class="user-picker-info">
                    <div class="user-picker-name">${u.nome||'—'}</div>
                    ${u.email ? '<div class="user-picker-email">'+u.email+'</div>' : ''}
                </div>
                <div class="user-picker-check"><i class="fas fa-check"></i></div>
            </div>`;
        });

        ov.innerHTML = `
        <div class="user-picker-modal">
            <div class="user-picker-search">
                <input type="text" id="userPickerSearch" placeholder="Buscar usuário..." autocomplete="off">
            </div>
            <div class="user-picker-list" id="userPickerList">
                <div class="user-picker-none" data-id="" data-nome="">
                    <i class="fas fa-user-slash"></i> Sem responsável
                </div>
                ${listHtml}
            </div>
        </div>`;

        // Animate open
        requestAnimationFrame(() => ov.classList.add('open'));

        // Focus search
        setTimeout(() => document.getElementById('userPickerSearch')?.focus(), 100);

        // Search
        document.getElementById('userPickerSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#userPickerList .user-picker-item').forEach(item => {
                const match = !q || item.dataset.search.includes(q);
                item.style.display = match ? '' : 'none';
            });
        });

        // Click items
        document.querySelectorAll('#userPickerList .user-picker-item, #userPickerList .user-picker-none').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id || '';
                const nome = item.dataset.nome || '';
                if (self._callback) self._callback(id, nome);
                self.close();
            });
        });

        // Escape
        ov._keyHandler = (e) => { if (e.key === 'Escape') self.close(); };
        document.addEventListener('keydown', ov._keyHandler);
    },

    close() {
        const ov = document.getElementById('userPickerOverlay');
        if (ov) {
            document.removeEventListener('keydown', ov._keyHandler);
            ov.classList.remove('open');
            setTimeout(() => ov.remove(), 250);
        }
    }
};

// -- Toggle new task inline form --
function ntToggleNewTask() {
    const el = document.getElementById('ntNewTask');
    const visible = el.style.display !== 'none';
    el.style.display = visible ? 'none' : '';
    if (!visible) document.getElementById('ntNewTitle').focus();
}

// -- Create task inline --
function ntCreateTask() {
    const titulo = document.getElementById('ntNewTitle').value.trim();
    if (!titulo) { HelpDesk.toast('Título é obrigatório', 'warning'); return; }
    const data = {
        action: 'criar',
        titulo: titulo,
        projeto_id: PROJETO_ID,
        coluna: document.getElementById('ntNewColuna').value,
        prioridade: document.getElementById('ntNewPrioridade').value,
        responsavel_id: document.getElementById('ntNewResponsavel').value || null,
        prazo: document.getElementById('ntNewPrazo').value || null
    };
    HelpDesk.api('POST', '/api/tarefas.php', data).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Tarefa criada!', 'success');
            setTimeout(() => location.reload(), 400);
        }
    });
}

// -- Enter key on title input creates task --
document.getElementById('ntNewTitle')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') ntCreateTask();
    if (e.key === 'Escape') ntToggleNewTask();
});

// -- Update single field (inline editing) --
function ntUpdateField(id, field, value) {
    const data = { action: 'atualizar', id: id };
    data[field] = value;
    HelpDesk.api('POST', '/api/tarefas.php', data).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Atualizado!', 'success');
            // If status changed to concluido, update visual
            if (field === 'coluna') {
                const row = document.querySelector(`.nt-task-row[data-id="${id}"]`);
                if (row) {
                    row.classList.toggle('nt-done', value === 'concluido');
                    row.dataset.status = value === 'concluido' ? 'done' : 'pending';
                    const chk = row.querySelector('.nt-check');
                    if (chk) {
                        chk.classList.toggle('checked', value === 'concluido');
                        chk.innerHTML = value === 'concluido' ? '<i class="fas fa-check"></i>' : '';
                    }
                }
            }
        }
    });
}

// -- Toggle done/undone --
function ntToggleDone(id, newCol) {
    ntUpdateField(id, 'coluna', newCol);
}

// -- Filter tasks (Todas/Pendentes/Concluídas) --
function ntFilterTasks(filter, btn) {
    document.querySelectorAll('.nt-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.nt-task-row').forEach(row => {
        if (filter === 'all') row.style.display = '';
        else if (filter === 'done') row.style.display = row.dataset.status === 'done' ? '' : 'none';
        else row.style.display = row.dataset.status === 'pending' ? '' : 'none';
    });
}

// ============================================================
//  TASK DETAIL SLIDE-OUT PANEL
// ============================================================
function ntOpenTask(id) {
    // Create panel if not exists
    let panel = document.getElementById('ntTaskPanel');
    if (!panel) {
        panel = document.createElement('div');
        panel.id = 'ntTaskPanel';
        panel.className = 'nt-panel-overlay';
        document.body.appendChild(panel);
    }
    panel.innerHTML = `<div class="nt-panel"><div class="nt-panel-loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</div></div>`;
    panel.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Close on overlay click
    panel.onclick = (e) => { if (e.target === panel) ntClosePanel(); };

    HelpDesk.api('GET', '/api/tarefas.php?action=ver&id=' + id).then(t => {
        if (!t || t.error) { ntClosePanel(); HelpDesk.toast('Erro ao carregar tarefa', 'error'); return; }

        const colInfo = KANBAN_COLS[t.coluna] || {label:t.coluna, cor:'#6b7280'};
        const priInfo = PRIORIDADES[t.prioridade] || {label:t.prioridade, cor:'#6b7280', icone:'fas fa-flag'};
        const isDone = t.coluna === 'concluido';

        const respNome = t.responsavel_nome || '';
        const respInitials = respNome ? respNome.substring(0,2).toUpperCase() : '';

        let colOpts = '';
        for (const [k,v] of Object.entries(KANBAN_COLS)) {
            colOpts += `<option value="${k}" ${k===t.coluna?'selected':''}>${v.label}</option>`;
        }

        let priOpts = '';
        for (const [k,v] of Object.entries(PRIORIDADES)) {
            priOpts += `<option value="${k}" ${k===t.prioridade?'selected':''}>${v.label}</option>`;
        }

        // Comments HTML
        let comHtml = '';
        if (t.comentarios && t.comentarios.length) {
            t.comentarios.forEach(c => {
                const initials = (c.usuario_nome||'S').substring(0,2).toUpperCase();
                const time = c.criado_em ? new Date(c.criado_em).toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '';
                comHtml += `
                <div class="nt-comment">
                    <div class="nt-comment-avatar">${initials}</div>
                    <div class="nt-comment-body">
                        <div class="nt-comment-meta"><strong>${c.usuario_nome||'Sistema'}</strong><span>${time}</span></div>
                        <div class="nt-comment-text">${(c.conteudo||'').replace(/\n/g,'<br>')}</div>
                    </div>
                </div>`;
            });
        } else {
            comHtml = '<div class="nt-comment-empty"><i class="far fa-comment-dots"></i> Nenhum comentário ainda</div>';
        }

        const panelEl = panel.querySelector('.nt-panel');
        panelEl.innerHTML = `
        <div class="nt-panel-header">
            <div class="nt-panel-header-left">
                <div class="nt-check-lg ${isDone?'checked':''}" onclick="ntPanelToggleDone(${t.id},'${isDone?'a_fazer':'concluido'}')">
                    ${isDone?'<i class="fas fa-check"></i>':''}
                </div>
                <div class="nt-panel-title-wrap">
                    <input type="text" class="nt-panel-title" id="ntPanelTitle" value="${t.titulo.replace(/"/g,'&quot;')}"
                           onblur="ntSaveTitle(${t.id})">
                    <div class="nt-panel-subtitle">
                        ${t.projeto_nome ? '<span><i class="fas fa-project-diagram"></i> '+t.projeto_nome+'</span>' : ''}
                        <span><i class="far fa-clock"></i> Criado em ${t.criado_em ? new Date(t.criado_em).toLocaleDateString('pt-BR') : '-'}</span>
                    </div>
                </div>
            </div>
            <div class="nt-panel-header-right">
                <button class="nt-panel-btn danger" onclick="ntDeleteTask(${t.id})" title="Excluir tarefa">
                    <i class="fas fa-trash"></i>
                </button>
                <button class="nt-panel-btn" onclick="ntClosePanel()" title="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="nt-panel-body">
            <!-- Properties Grid -->
            <div class="nt-props">
                <div class="nt-prop">
                    <label><i class="fas fa-columns"></i> Status</label>
                    <select class="nt-prop-select" onchange="ntPanelUpdate(${t.id},'coluna',this.value)">${colOpts}</select>
                </div>
                <div class="nt-prop">
                    <label><i class="fas fa-flag"></i> Prioridade</label>
                    <select class="nt-prop-select" onchange="ntPanelUpdate(${t.id},'prioridade',this.value)">${priOpts}</select>
                </div>
                <div class="nt-prop">
                    <label><i class="fas fa-user"></i> Responsável</label>
                    <div class="nt-user-pick nt-prop-user-pick" id="ntPanelResponsavel"
                         onclick="UserPicker.open(TECNICOS, '${t.responsavel_id||''}', function(id,nome){ ntPanelUpdate(${t.id},'responsavel_id',id); var el=document.getElementById('ntPanelResponsavel'); el.querySelector('.nt-user-name').textContent=nome||'Sem responsável'; var av=el.querySelector('.nt-user-avatar-sm'); av.innerHTML=nome?nome.substring(0,2).toUpperCase():'<i class=\\'fas fa-user-plus\\' style=\\'font-size:9px\\'></i>'; })">
                        <span class="nt-user-avatar-sm">${respInitials || '<i class="fas fa-user-plus" style="font-size:9px"></i>'}</span>
                        <span class="nt-user-name">${respNome || 'Sem responsável'}</span>
                    </div>
                </div>
                <div class="nt-prop">
                    <label><i class="far fa-calendar-alt"></i> Prazo</label>
                    <input type="date" class="nt-prop-date" value="${t.prazo||''}" onchange="ntPanelUpdate(${t.id},'prazo',this.value)">
                </div>
                <div class="nt-prop">
                    <label><i class="fas fa-star"></i> Pontos</label>
                    <input type="number" class="nt-prop-input" value="${t.pontos||''}" min="1" max="21" placeholder="—"
                           onchange="ntPanelUpdate(${t.id},'pontos',this.value)">
                </div>
                <div class="nt-prop">
                    <label><i class="fas fa-clock"></i> Horas</label>
                    <div class="nt-prop-hours">
                        <input type="number" class="nt-prop-input" value="${t.horas_estimadas||''}" step="0.5" placeholder="Est." title="Horas estimadas"
                               onchange="ntPanelUpdate(${t.id},'horas_estimadas',this.value)">
                        <span>/</span>
                        <input type="number" class="nt-prop-input" value="${t.horas_trabalhadas||0}" step="0.5" title="Horas trabalhadas"
                               onchange="ntPanelUpdate(${t.id},'horas_trabalhadas',this.value)">
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="nt-section">
                <div class="nt-section-label"><i class="fas fa-align-left"></i> Descrição</div>
                <textarea class="nt-desc-editor" id="ntPanelDesc" placeholder="Adicione uma descrição detalhada..."
                          onblur="ntSaveDesc(${t.id})">${(t.descricao||'').replace(/</g,'&lt;')}</textarea>
            </div>

            <!-- Comments -->
            <div class="nt-section">
                <div class="nt-section-label"><i class="fas fa-comments"></i> Comentários</div>
                <div class="nt-comments-list" id="ntCommentsList">${comHtml}</div>
                <div class="nt-comment-form">
                    <textarea id="ntCommentInput" class="nt-comment-input" placeholder="Escreva um comentário..." rows="2"></textarea>
                    <button class="btn btn-primary btn-sm" onclick="ntAddComment(${t.id})"><i class="fas fa-paper-plane"></i> Enviar</button>
                </div>
            </div>
        </div>`;

        // Auto-resize description
        const desc = document.getElementById('ntPanelDesc');
        if (desc) {
            desc.style.height = 'auto';
            desc.style.height = Math.max(80, desc.scrollHeight) + 'px';
            desc.addEventListener('input', () => {
                desc.style.height = 'auto';
                desc.style.height = desc.scrollHeight + 'px';
            });
        }

        // Enter on comment sends
        document.getElementById('ntCommentInput')?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ntAddComment(t.id); }
        });
    });
}

function ntClosePanel() {
    const panel = document.getElementById('ntTaskPanel');
    if (panel) {
        panel.classList.remove('open');
        document.body.style.overflow = '';
        setTimeout(() => panel.remove(), 300);
    }
}

function ntSaveTitle(id) {
    const val = document.getElementById('ntPanelTitle').value.trim();
    if (val) ntPanelUpdate(id, 'titulo', val);
}

function ntSaveDesc(id) {
    const val = document.getElementById('ntPanelDesc').value;
    ntPanelUpdate(id, 'descricao', val);
}

function ntPanelUpdate(id, field, value) {
    const data = { action: 'atualizar', id: id };
    data[field] = value;
    HelpDesk.api('POST', '/api/tarefas.php', data).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Salvo!', 'success');
            // Also update the table row if visible
            const row = document.querySelector(`.nt-task-row[data-id="${id}"]`);
            if (row && field === 'titulo') {
                const txt = row.querySelector('.nt-title-text');
                if (txt) txt.textContent = value;
            }
            if (row && field === 'coluna') {
                row.classList.toggle('nt-done', value === 'concluido');
                row.dataset.status = value === 'concluido' ? 'done' : 'pending';
                const sel = row.querySelector('.nt-status-sel');
                if (sel) sel.value = value;
                const chk = row.querySelector('.nt-check');
                if (chk) {
                    chk.classList.toggle('checked', value === 'concluido');
                    chk.innerHTML = value === 'concluido' ? '<i class="fas fa-check"></i>' : '';
                }
            }
            if (row && field === 'responsavel_id') {
                const usr = TECNICOS.find(t => String(t.id) === String(value));
                const nameEl = row.querySelector('.nt-user-name');
                const avEl = row.querySelector('.nt-user-avatar-sm');
                if (nameEl) nameEl.textContent = usr ? usr.nome : '—';
                if (avEl) avEl.textContent = usr ? usr.nome.substring(0,2).toUpperCase() : '';
            }
        }
    });
}

function ntPanelToggleDone(id, newCol) {
    ntPanelUpdate(id, 'coluna', newCol);
    setTimeout(() => ntOpenTask(id), 500);
}

function ntDeleteTask(id) {
    if (!confirm('Excluir esta tarefa permanentemente?')) return;
    HelpDesk.api('POST', '/api/tarefas.php', { action: 'deletar', id: id }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Tarefa excluída!', 'success');
            ntClosePanel();
            const row = document.querySelector(`.nt-task-row[data-id="${id}"]`);
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => row.remove(), 300);
            }
        }
    });
}

function ntAddComment(tarefaId) {
    const input = document.getElementById('ntCommentInput');
    const text = input.value.trim();
    if (!text) return;
    input.disabled = true;
    HelpDesk.api('POST', '/api/tarefas.php', {
        action: 'comentar', tarefa_id: tarefaId, conteudo: text
    }).then(resp => {
        input.disabled = false;
        if (resp.success) {
            input.value = '';
            // Add comment to UI
            const list = document.getElementById('ntCommentsList');
            const empty = list.querySelector('.nt-comment-empty');
            if (empty) empty.remove();
            const user = <?= json_encode(currentUser()['nome'] ?? 'Eu') ?>;
            const initials = user.substring(0,2).toUpperCase();
            const now = new Date().toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
            list.insertAdjacentHTML('beforeend', `
                <div class="nt-comment" style="animation:ntSlideIn .3s ease">
                    <div class="nt-comment-avatar">${initials}</div>
                    <div class="nt-comment-body">
                        <div class="nt-comment-meta"><strong>${user}</strong><span>${now}</span></div>
                        <div class="nt-comment-text">${text.replace(/\n/g,'<br>')}</div>
                    </div>
                </div>`);
            list.scrollTop = list.scrollHeight;
        }
    });
}
</script>

<!-- CSS is in assets/css/style.css -->
