<?php
/**
 * View: Compras (Requisições)
 */
$compraStatusList = COMPRA_STATUS;
$user = currentUser();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Requisições de Compra</h1>
        <p class="page-subtitle">Gerenciamento de compras de TI</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novaCompra')">
            <i class="fas fa-plus"></i> Nova Requisição
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Item</th>
                    <th>Solicitante</th>
                    <th>Qtd</th>
                    <th>Valor Est.</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($compras)): ?>
                <tr><td colspan="9" class="text-center py-4"><div class="empty-state-sm"><i class="fas fa-shopping-cart"></i><p>Nenhuma requisição</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($compras as $c): ?>
                <?php $st = $compraStatusList[$c['status']] ?? ['label' => $c['status'], 'cor' => '#6B7280']; ?>
                <tr>
                    <td><span class="code-badge"><?= $c['codigo'] ?></span></td>
                    <td><strong><?= htmlspecialchars($c['item']) ?></strong></td>
                    <td><?= htmlspecialchars($c['solicitante_nome'] ?? '-') ?></td>
                    <td><?= $c['quantidade'] ?></td>
                    <td>R$ <?= number_format($c['valor_estimado'], 2, ',', '.') ?></td>
                    <td>
                        <?php $pri = PRIORIDADES[$c['prioridade']] ?? null; ?>
                        <span class="priority-badge" style="background:<?= $pri['cor'] ?>20;color:<?= $pri['cor'] ?>"><?= $pri['label'] ?></span>
                    </td>
                    <td><span class="status-badge" style="background:<?= $st['cor'] ?>20;color:<?= $st['cor'] ?>"><?= $st['label'] ?></span></td>
                    <td><?= formatarData($c['criado_em'], 'd/m/Y') ?></td>
                    <td>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-ghost" onclick="verCompra(<?= $c['id'] ?>)" title="Ver"><i class="fas fa-eye"></i></button>
                            <?php if (in_array($user['tipo'], ['admin', 'gestor'])): ?>
                            <button class="btn btn-sm btn-ghost" onclick="alterarStatusCompra(<?= $c['id'] ?>)" title="Alterar Status"><i class="fas fa-edit"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novaCompra = function() {
    const html = `
    <form id="formNovaCompra" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Item *</label>
            <input type="text" name="item" class="form-input" required placeholder="Ex: Notebook Dell Latitude">
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-textarea" rows="3" placeholder="Especificações detalhadas"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Quantidade *</label>
            <input type="number" name="quantidade" class="form-input" min="1" value="1" required>
        </div>
        <div class="form-group">
            <label class="form-label">Valor Estimado (R$)</label>
            <input type="number" name="valor_estimado" class="form-input" step="0.01" min="0" placeholder="0,00">
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Justificativa *</label>
            <textarea name="justificativa" class="form-textarea" rows="3" required placeholder="Justifique a necessidade"></textarea>
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
    </form>`;

    HelpDesk.showModal('Nova Requisição de Compra', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovaCompra()"><i class="fas fa-save"></i> Criar</button>
    `);
};

function submitNovaCompra() {
    const form = document.getElementById('formNovaCompra');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';
    HelpDesk.api('POST', '/api/compras.php', data).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Requisição criada! Código: ' + resp.codigo, 'success');
            HelpDesk.closeModal();
            setTimeout(() => location.reload(), 1000);
        }
    });
}

function verCompra(id) {
    HelpDesk.api('GET', '/api/compras.php?action=ver&id=' + id).then(compra => {
        if (!compra) return;
        const html = `
        <div class="detail-info">
            <div class="info-row"><span class="info-label">Item</span><span class="info-value">${compra.item}</span></div>
            <div class="info-row"><span class="info-label">Quantidade</span><span class="info-value">${compra.quantidade}</span></div>
            <div class="info-row"><span class="info-label">Valor</span><span class="info-value">R$ ${parseFloat(compra.valor_estimado || 0).toFixed(2)}</span></div>
            <div class="info-row"><span class="info-label">Justificativa</span><span class="info-value">${compra.justificativa}</span></div>
            <div class="info-row"><span class="info-label">Solicitante</span><span class="info-value">${compra.solicitante_nome || '-'}</span></div>
        </div>
        ${compra.historico ? '<hr><h4>Histórico</h4>' + compra.historico.map(h => 
            '<div class="timeline-item-sm"><strong>' + h.status + '</strong> - ' + (h.usuario_nome || 'Sistema') + '<br><small>' + h.criado_em + '</small>' + (h.observacao ? '<br>' + h.observacao : '') + '</div>'
        ).join('') : ''}`;
        HelpDesk.showModal('Requisição ' + compra.codigo, html, '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>');
    });
}

function alterarStatusCompra(id) {
    const html = `
    <form id="formStatusCompra">
        <div class="form-group">
            <label class="form-label">Novo Status</label>
            <select name="status" class="form-select">
                <option value="em_analise">Em Análise</option>
                <option value="aprovado">Aprovado</option>
                <option value="reprovado">Reprovado</option>
                <option value="comprado">Comprado</option>
                <option value="entregue">Entregue</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Observação</label>
            <textarea name="observacao" class="form-textarea" rows="3"></textarea>
        </div>
    </form>`;
    HelpDesk.showModal('Alterar Status', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitStatusCompra(${id})"><i class="fas fa-save"></i> Salvar</button>
    `);
}

function submitStatusCompra(id) {
    const form = document.getElementById('formStatusCompra');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'alterar_status';
    data.id = id;
    HelpDesk.api('POST', '/api/compras.php', data).then(resp => {
        if (resp.success) { HelpDesk.toast('Status atualizado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
    });
}
</script>
