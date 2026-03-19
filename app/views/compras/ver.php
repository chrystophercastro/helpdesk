<?php
/**
 * View: Detalhes da Compra
 */
if (!$compra) {
    echo '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Requisição não encontrada</h3></div>';
    return;
}
$statusList = COMPRA_STATUS;
$prioridadeList = PRIORIDADES;
$st = $statusList[$compra['status']] ?? ['label' => $compra['status'], 'cor' => '#6B7280'];
$pri = $prioridadeList[$compra['prioridade']] ?? ['label' => $compra['prioridade'], 'cor' => '#6B7280', 'icone' => 'fas fa-minus'];
$user = currentUser();
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/index.php?page=compras" class="btn btn-ghost btn-sm mb-2">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 class="page-title">
            <span class="code-badge mr-2"><?= $compra['codigo'] ?></span>
            <?= htmlspecialchars($compra['item']) ?>
        </h1>
    </div>
    <div class="page-actions">
        <span class="status-badge lg" style="background:<?= $st['cor'] ?>20;color:<?= $st['cor'] ?>">
            <?= $st['label'] ?>
        </span>
    </div>
</div>

<div class="grid-detail">
    <div class="detail-main">
        <!-- Descrição -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt"></i> Descrição</h3>
            </div>
            <div class="card-body">
                <p><?= nl2br(htmlspecialchars($compra['descricao'] ?? 'Sem descrição')) ?></p>
            </div>
        </div>

        <!-- Justificativa -->
        <?php if (!empty($compra['justificativa'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard"></i> Justificativa</h3>
            </div>
            <div class="card-body">
                <p><?= nl2br(htmlspecialchars($compra['justificativa'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Alterar Status -->
        <?php if (in_array($user['tipo'], ['admin', 'gestor'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Alterar Status</h3>
            </div>
            <div class="card-body">
                <form id="formAlterarStatus">
                    <div class="form-group">
                        <label class="form-label">Novo Status</label>
                        <select name="status" class="form-select" id="novoStatus">
                            <?php foreach ($statusList as $key => $s): ?>
                            <option value="<?= $key ?>" <?= $compra['status'] === $key ? 'selected' : '' ?>><?= $s['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observação</label>
                        <textarea name="observacao" class="form-textarea" rows="3" id="obsStatus" placeholder="Motivo da alteração..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Atualizar Status
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Histórico -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history"></i> Histórico</h3>
            </div>
            <div class="card-body p-0">
                <div class="timeline">
                    <?php if (!empty($compra['historico'])): ?>
                    <?php foreach ($compra['historico'] as $h): ?>
                    <?php $hst = $statusList[$h['status']] ?? ['label' => $h['status'], 'cor' => '#6B7280']; ?>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background:<?= $hst['cor'] ?>"></div>
                        <div class="timeline-content">
                            <strong><?= htmlspecialchars($h['usuario_nome'] ?? 'Sistema') ?></strong>
                            alterou status para
                            <span class="status-badge" style="background:<?= $hst['cor'] ?>20;color:<?= $hst['cor'] ?>;font-size:0.8rem"><?= $hst['label'] ?></span>
                            <?php if (!empty($h['observacao'])): ?>
                            <p style="margin:4px 0 0;color:#64748b;font-size:0.85rem"><?= htmlspecialchars($h['observacao']) ?></p>
                            <?php endif; ?>
                            <small class="text-muted d-block"><?= formatarData($h['criado_em']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="timeline-item">
                        <div class="timeline-dot dot-green"></div>
                        <div class="timeline-content">
                            Requisição criada
                            <small class="text-muted d-block"><?= formatarData($compra['criado_em']) ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="detail-sidebar">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Detalhes</h3></div>
            <div class="card-body">
                <div class="detail-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-tag"></i> Prioridade</span>
                        <span class="info-value">
                            <span class="priority-badge" style="background:<?= $pri['cor'] ?>20;color:<?= $pri['cor'] ?>">
                                <i class="<?= $pri['icone'] ?>"></i> <?= $pri['label'] ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user"></i> Solicitante</span>
                        <span class="info-value"><?= htmlspecialchars($compra['solicitante_nome'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-sort-numeric-up"></i> Quantidade</span>
                        <span class="info-value"><?= $compra['quantidade'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-money-bill-wave"></i> Valor Estimado</span>
                        <span class="info-value">R$ <?= number_format($compra['valor_estimado'] ?? 0, 2, ',', '.') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-money-bill-wave"></i> Valor Total</span>
                        <span class="info-value">R$ <?= number_format(($compra['valor_estimado'] ?? 0) * ($compra['quantidade'] ?? 1), 2, ',', '.') ?></span>
                    </div>
                    <?php if (!empty($compra['aprovador_nome'])): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user-check"></i> Aprovador</span>
                        <span class="info-value"><?= htmlspecialchars($compra['aprovador_nome']) ?></span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar-plus"></i> Criação</span>
                        <span class="info-value"><?= formatarData($compra['criado_em']) ?></span>
                    </div>
                    <?php if (!empty($compra['data_aprovacao'])): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-check-circle"></i> Aprovação</span>
                        <span class="info-value"><?= formatarData($compra['data_aprovacao']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($compra['data_compra'])): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-shopping-cart"></i> Compra</span>
                        <span class="info-value"><?= formatarData($compra['data_compra']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($compra['data_entrega'])): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-truck"></i> Entrega</span>
                        <span class="info-value"><?= formatarData($compra['data_entrega']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
<?php if (in_array($user['tipo'], ['admin', 'gestor'])): ?>
document.getElementById('formAlterarStatus').addEventListener('submit', function(e) {
    e.preventDefault();
    const status = document.getElementById('novoStatus').value;
    const observacao = document.getElementById('obsStatus').value;

    HelpDesk.api('POST', '/api/compras.php', {
        action: 'alterar_status',
        id: <?= $compra['id'] ?>,
        status: status,
        observacao: observacao
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Status atualizado!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            HelpDesk.toast(resp.error || 'Erro ao atualizar', 'danger');
        }
    });
});
<?php endif; ?>
</script>
