/**
 * Oracle X - Kanban Board JavaScript
 * Handles drag-and-drop via SortableJS
 */
document.addEventListener('DOMContentLoaded', function() {
    initKanbanBoard();
});

function initKanbanBoard() {
    const columns = document.querySelectorAll('.kanban-column-body');
    
    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 200,
            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            handle: '.kanban-card',
            delay: 50,
            delayOnTouchOnly: true,

            onEnd: function(evt) {
                const taskId = evt.item.dataset.id;
                const newColumn = evt.to.dataset.column;
                const newIndex = evt.newIndex;

                if (!taskId || !newColumn) return;

                // Update card count badges
                updateColumnCounts();

                // Send to server
                HelpDesk.api('POST', '/api/tarefas.php', {
                    action: 'mover',
                    tarefa_id: parseInt(taskId),
                    coluna: newColumn,
                    posicao: newIndex
                }).then(resp => {
                    if (resp.success) {
                        // Optional: subtle visual feedback
                        evt.item.style.borderColor = '#10B981';
                        setTimeout(() => { evt.item.style.borderColor = ''; }, 1000);
                    } else {
                        HelpDesk.toast(resp.error || 'Erro ao mover tarefa', 'danger');
                        // Revert position
                        evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                        updateColumnCounts();
                    }
                }).catch(() => {
                    HelpDesk.toast('Erro de conexão', 'danger');
                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                    updateColumnCounts();
                });
            }
        });
    });
}

/**
 * Update the count badges in each column header
 */
function updateColumnCounts() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const body = col.querySelector('.kanban-column-body');
        const count = col.querySelector('.kanban-column-header .kanban-count');
        if (body && count) {
            count.textContent = body.querySelectorAll('.kanban-card').length;
        }
    });
}

/**
 * Filter kanban cards by project
 */
function filterKanbanByProject(projectId) {
    const currentUrl = new URL(window.location);
    if (projectId) {
        currentUrl.searchParams.set('projeto_id', projectId);
    } else {
        currentUrl.searchParams.delete('projeto_id');
    }
    window.location.href = currentUrl.toString();
}
