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

        <!-- Tarefas -->
        <div class="pv-card">
            <div class="pv-card-header">
                <h3><i class="fas fa-tasks"></i> Tarefas <span class="pv-count-badge"><?= $tarefasTotal ?></span></h3>
                <a href="<?= BASE_URL ?>/index.php?page=kanban&projeto_id=<?= $projeto['id'] ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-plus"></i> Gerenciar no Kanban
                </a>
            </div>
            <div class="pv-card-body p-0">
                <?php if (empty($tarefas)): ?>
                <div class="pv-empty-mini">
                    <i class="fas fa-clipboard-list"></i>
                    <p>Nenhuma tarefa criada. Use o Kanban para adicionar tarefas.</p>
                </div>
                <?php else: ?>
                <div class="pv-tasks-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Tarefa</th>
                                <th>Status</th>
                                <th>Responsável</th>
                                <th>Prazo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarefas as $t): ?>
                            <?php
                                $colunas = KANBAN_COLUNAS;
                                $colInfo = $colunas[$t['coluna']] ?? ['label' => $t['coluna'], 'cor' => '#6b7280'];
                                $tarefaAtrasada = ($t['prazo'] && strtotime($t['prazo']) < time() && $t['coluna'] !== 'concluido');
                            ?>
                            <tr class="<?= $t['coluna'] === 'concluido' ? 'pv-task-done' : '' ?> <?= $tarefaAtrasada ? 'pv-task-late' : '' ?>">
                                <td>
                                    <div class="pv-task-title">
                                        <?php if ($t['coluna'] === 'concluido'): ?>
                                        <i class="fas fa-check-circle" style="color: #059669;"></i>
                                        <?php else: ?>
                                        <i class="far fa-circle" style="color: var(--gray-400);"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8', false) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="pv-task-col-badge" style="background:<?= $colInfo['cor'] ?>14;color:<?= $colInfo['cor'] ?>;border:1px solid <?= $colInfo['cor'] ?>30">
                                        <?= $colInfo['label'] ?>
                                    </span>
                                </td>
                                <td class="pv-task-responsavel"><?= htmlspecialchars($t['responsavel_nome'] ?? '-', ENT_QUOTES, 'UTF-8', false) ?></td>
                                <td class="pv-task-prazo <?= $tarefaAtrasada ? 'pv-late-text' : '' ?>">
                                    <?php if ($t['prazo']): ?>
                                    <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($t['prazo'])) ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                <div class="pv-info-row pv-info-editable" onclick="toggleResponsavelEdit()" title="Clique para alterar o responsável">
                    <span class="pv-info-label"><i class="fas fa-user-shield"></i> Dono / Responsável</span>
                    <span class="pv-info-value" id="pvResponsavelDisplay">
                        <?= htmlspecialchars($projeto['responsavel_nome'] ?? 'Não definido', ENT_QUOTES, 'UTF-8', false) ?>
                        <i class="fas fa-pen pv-edit-icon"></i>
                    </span>
                </div>
                <div class="pv-info-row pv-edit-row" id="pvResponsavelEdit" style="display:none">
                    <select id="pvResponsavelSelect" class="form-select form-select-sm">
                        <option value="">Sem responsável</option>
                        <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($projeto['responsavel_id'] == $t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nome'], ENT_QUOTES, 'UTF-8', false) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary btn-xs" onclick="salvarResponsavel(<?= $projeto['id'] ?>)"><i class="fas fa-check"></i></button>
                    <button class="btn btn-secondary btn-xs" onclick="toggleResponsavelEdit()"><i class="fas fa-times"></i></button>
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
function toggleResponsavelEdit() {
    const display = document.getElementById('pvResponsavelDisplay').parentElement;
    const edit = document.getElementById('pvResponsavelEdit');
    if (edit.style.display === 'none') {
        display.style.display = 'none';
        edit.style.display = 'flex';
    } else {
        display.style.display = '';
        edit.style.display = 'none';
    }
}

function salvarResponsavel(projetoId) {
    const select = document.getElementById('pvResponsavelSelect');
    const novoId = select.value || null;
    const novoNome = select.options[select.selectedIndex].text;
    HelpDesk.api('POST', '/api/projetos.php', {
        action: 'atualizar', id: projetoId, responsavel_id: novoId
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Responsável atualizado!', 'success');
            setTimeout(() => location.reload(), 600);
        }
    });
}

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
</script>
