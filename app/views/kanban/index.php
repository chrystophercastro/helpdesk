<?php
/**
 * View: Kanban Board
 */
$user = currentUser();
$prioridadeList = PRIORIDADES;
$colunas = KANBAN_COLUNAS;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quadro Kanban</h1>
        <p class="page-subtitle">Gerencie tarefas com drag and drop<?php if (!isAdmin()) echo ' — Projetos do seu departamento'; ?></p>
    </div>
    <div class="page-actions">
        <select class="form-select" id="kanbanProjetoFilter" onchange="filtrarProjeto(this.value)">
            <option value="">Todos os Projetos</option>
            <?php foreach ($projetosLista as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($projetoId ?? '') == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('kanban_assignment', {projeto_id: document.getElementById('kanbanProjetoFilter')?.value || ''})">
            <i class="fas fa-robot"></i> Análise IA
        </button>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novaTarefa')">
            <i class="fas fa-plus"></i> Nova Tarefa
        </button>
    </div>
</div>

<div class="kanban-board" id="kanbanBoard">
    <?php foreach ($colunas as $key => $col): ?>
    <div class="kanban-column" data-column="<?= $key ?>">
        <div class="kanban-column-header" style="border-top: 3px solid <?= $col['cor'] ?>">
            <div class="kanban-column-title">
                <i class="<?= $col['icone'] ?>"></i>
                <span><?= $col['label'] ?></span>
                <span class="kanban-count"><?= count($kanban[$key]['tarefas']) ?></span>
            </div>
        </div>
        <div class="kanban-column-body" id="column-<?= $key ?>" data-column="<?= $key ?>">
            <?php foreach ($kanban[$key]['tarefas'] as $t): ?>
            <div class="kanban-card" data-id="<?= $t['id'] ?>" onclick="verTarefa(<?= $t['id'] ?>)">
                <?php if ($t['projeto_nome']): ?>
                <div class="kanban-card-project"><?= htmlspecialchars($t['projeto_nome']) ?></div>
                <?php endif; ?>
                <div class="kanban-card-title"><?= htmlspecialchars($t['titulo']) ?></div>
                <div class="kanban-card-footer">
                    <div class="kanban-card-meta">
                        <?php $pri = $prioridadeList[$t['prioridade']]; ?>
                        <span class="priority-dot" style="background:<?= $pri['cor'] ?>" title="<?= $pri['label'] ?>"></span>
                        <?php if ($t['pontos']): ?>
                        <span class="kanban-points"><?= $t['pontos'] ?>pts</span>
                        <?php endif; ?>
                        <?php if ($t['prazo']): ?>
                        <span class="kanban-deadline <?= (strtotime($t['prazo']) < time() && $key !== 'concluido') ? 'overdue' : '' ?>">
                            <i class="fas fa-calendar"></i> <?= date('d/m', strtotime($t['prazo'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($t['responsavel_nome']): ?>
                    <div class="kanban-card-avatar" title="<?= htmlspecialchars($t['responsavel_nome']) ?>">
                        <?= strtoupper(substr($t['responsavel_nome'], 0, 2)) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
// Dados para o modal de nova tarefa
const projetosLista = <?= json_encode($projetosLista) ?>;
const tecnicosLista = <?= json_encode(array_map(fn($t) => ['id' => $t['id'], 'nome' => $t['nome']], $tecnicos)) ?>;

function filtrarProjeto(projetoId) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'kanban');
    if (projetoId) {
        url.searchParams.set('projeto_id', projetoId);
    } else {
        url.searchParams.delete('projeto_id');
    }
    window.location = url;
}

function verTarefa(id) {
    HelpDesk.api('GET', '/api/tarefas.php?action=ver&id=' + id)
        .then(tarefa => {
            if (!tarefa || tarefa.error) return;
            const html = `
                <div class="detail-info">
                    <div class="info-row"><span class="info-label">Projeto</span><span class="info-value">${tarefa.projeto_nome || '-'}</span></div>
                    <div class="info-row"><span class="info-label">Responsável</span><span class="info-value">${tarefa.responsavel_nome || 'Não atribuído'}</span></div>
                    <div class="info-row"><span class="info-label">Prioridade</span><span class="info-value">${tarefa.prioridade}</span></div>
                    <div class="info-row"><span class="info-label">Prazo</span><span class="info-value">${tarefa.prazo || '-'}</span></div>
                    <div class="info-row"><span class="info-label">Pontos</span><span class="info-value">${tarefa.pontos || '-'}</span></div>
                    <div class="info-row"><span class="info-label">Horas Trab.</span><span class="info-value">${tarefa.horas_trabalhadas || 0}h</span></div>
                </div>
                <hr>
                <div class="description-content">${(tarefa.descricao || 'Sem descrição').replace(/\\n/g, '<br>')}</div>
            `;
            HelpDesk.showModal(tarefa.titulo, html, `
                <button class="btn btn-danger btn-sm" onclick="deletarTarefa(${tarefa.id})"><i class="fas fa-trash"></i> Excluir</button>
                <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>
            `);
        });
}

function deletarTarefa(id) {
    if (!confirm('Tem certeza que deseja excluir esta tarefa?')) return;
    HelpDesk.api('POST', '/api/tarefas.php', { action: 'deletar', id: id })
        .then(() => {
            HelpDesk.toast('Tarefa excluída!', 'success');
            HelpDesk.closeModal();
            setTimeout(() => location.reload(), 500);
        });
}

// Modal nova tarefa
HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novaTarefa = function() {
    let projetoOpts = '<option value="">Nenhum</option>';
    projetosLista.forEach(p => projetoOpts += `<option value="${p.id}">${p.nome}</option>`);
    let tecOpts = '<option value="">Não atribuído</option>';
    tecnicosLista.forEach(t => tecOpts += `<option value="${t.id}">${t.nome}</option>`);

    const html = `
    <form id="formNovaTarefa" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Título *</label>
            <input type="text" name="titulo" class="form-input" required>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-textarea" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Projeto</label>
            <select name="projeto_id" class="form-select">${projetoOpts}</select>
        </div>
        <div class="form-group">
            <label class="form-label">Responsável</label>
            <select name="responsavel_id" class="form-select">${tecOpts}</select>
        </div>
        <div class="form-group">
            <label class="form-label">Coluna</label>
            <select name="coluna" class="form-select">
                <option value="backlog">Backlog</option>
                <option value="a_fazer">A Fazer</option>
                <option value="em_andamento">Em Andamento</option>
                <option value="em_revisao">Em Revisão</option>
            </select>
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
            <label class="form-label">Prazo</label>
            <input type="date" name="prazo" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Story Points</label>
            <input type="number" name="pontos" class="form-input" min="1" max="21">
        </div>
    </form>`;

    HelpDesk.showModal('Nova Tarefa', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovaTarefa()"><i class="fas fa-save"></i> Criar</button>
    `);
};

function submitNovaTarefa() {
    const form = document.getElementById('formNovaTarefa');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';

    HelpDesk.api('POST', '/api/tarefas.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Tarefa criada!', 'success');
                HelpDesk.closeModal();
                setTimeout(() => location.reload(), 500);
            }
        });
}
</script>
