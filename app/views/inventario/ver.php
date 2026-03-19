<?php
/**
 * View: Detalhes do Ativo (Inventário)
 */
if (!$ativo) {
    echo '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Ativo não encontrado</h3></div>';
    return;
}

$tipoLabels = [
    'computador' => 'Computador', 'servidor' => 'Servidor', 'switch' => 'Switch',
    'roteador' => 'Roteador', 'impressora' => 'Impressora', 'software' => 'Software',
    'monitor' => 'Monitor', 'telefone' => 'Telefone', 'outro' => 'Outro'
];
$statusColors = ['ativo' => '#10B981', 'manutencao' => '#F59E0B', 'inativo' => '#6B7280', 'descartado' => '#EF4444'];
$statusLabels = ['ativo' => 'Ativo', 'manutencao' => 'Manutenção', 'inativo' => 'Inativo', 'descartado' => 'Descartado'];
$cor = $statusColors[$ativo['status']] ?? '#6B7280';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/index.php?page=inventario" class="btn btn-ghost btn-sm mb-2">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 class="page-title">
            <span class="code-badge mr-2"><?= htmlspecialchars($ativo['numero_patrimonio']) ?></span>
            <?= htmlspecialchars($ativo['nome']) ?>
        </h1>
    </div>
    <div class="page-actions">
        <span class="status-badge lg" style="background:<?= $cor ?>20;color:<?= $cor ?>">
            <?= $statusLabels[$ativo['status']] ?? $ativo['status'] ?>
        </span>
        <button class="btn btn-outline" onclick="editarAtivo()">
            <i class="fas fa-edit"></i> Editar
        </button>
    </div>
</div>

<div class="grid-detail">
    <div class="detail-main">
        <!-- Informações do Ativo -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Informações do Ativo</h3>
            </div>
            <div class="card-body">
                <div class="detail-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-box"></i> Tipo</span>
                        <span class="info-value"><?= $tipoLabels[$ativo['tipo']] ?? ucfirst($ativo['tipo']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-laptop"></i> Modelo</span>
                        <span class="info-value"><?= htmlspecialchars($ativo['modelo'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-industry"></i> Fabricante</span>
                        <span class="info-value"><?= htmlspecialchars($ativo['fabricante'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-barcode"></i> Número de Série</span>
                        <span class="info-value"><?= htmlspecialchars($ativo['numero_serie'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Localização</span>
                        <span class="info-value"><?= htmlspecialchars($ativo['localizacao'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user"></i> Responsável</span>
                        <span class="info-value"><?= htmlspecialchars($ativo['responsavel_nome'] ?? 'Não atribuído') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Especificações -->
        <?php if (!empty($ativo['especificacoes'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs"></i> Especificações</h3>
            </div>
            <div class="card-body">
                <pre style="white-space:pre-wrap;font-size:0.9rem;background:#f8fafc;padding:16px;border-radius:8px;margin:0"><?= htmlspecialchars($ativo['especificacoes']) ?></pre>
            </div>
        </div>
        <?php endif; ?>

        <!-- Chamados Vinculados -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ticket-alt"></i> Chamados Vinculados (<?= count($chamadosVinculados) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($chamadosVinculados)): ?>
                <div class="empty-state-sm" style="padding:24px">
                    <i class="fas fa-ticket-alt"></i>
                    <p>Nenhum chamado vinculado a este ativo</p>
                </div>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chamadosVinculados as $ch): ?>
                        <?php $chSt = CHAMADO_STATUS[$ch['status']] ?? ['label' => $ch['status'], 'cor' => '#6B7280']; ?>
                        <tr class="clickable-row" onclick="window.location='<?= BASE_URL ?>/index.php?page=chamados&action=ver&id=<?= $ch['id'] ?>'">
                            <td><span class="code-badge"><?= $ch['codigo'] ?></span></td>
                            <td><?= htmlspecialchars($ch['titulo']) ?></td>
                            <td>
                                <span class="status-badge" style="background:<?= $chSt['cor'] ?>20;color:<?= $chSt['cor'] ?>">
                                    <?= $chSt['label'] ?>
                                </span>
                            </td>
                            <td><span class="text-sm text-muted"><?= formatarData($ch['data_abertura'] ?? $ch['criado_em'] ?? '', 'd/m/Y') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="detail-sidebar">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Resumo</h3></div>
            <div class="card-body">
                <div class="detail-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-hashtag"></i> Patrimônio</span>
                        <span class="info-value"><strong><?= htmlspecialchars($ativo['numero_patrimonio']) ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-ticket-alt"></i> Chamados</span>
                        <span class="info-value"><?= count($chamadosVinculados) ?></span>
                    </div>
                </div>

                <hr>

                <!-- Alterar Status -->
                <h4 class="mb-2">Alterar Status</h4>
                <select class="form-select" onchange="atualizarAtivo('status', this.value)">
                    <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $ativo['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>

                <hr>

                <!-- Alterar Responsável -->
                <h4 class="mb-2">Responsável</h4>
                <select class="form-select" onchange="atualizarAtivo('responsavel_id', this.value)">
                    <option value="">Não atribuído</option>
                    <?php foreach ($tecnicos as $tec): ?>
                    <option value="<?= $tec['id'] ?>" <?= ($ativo['responsavel_id'] ?? '') == $tec['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tec['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<script>
function atualizarAtivo(campo, valor) {
    const data = { action: 'atualizar', id: <?= $ativo['id'] ?> };
    data[campo] = valor;
    HelpDesk.api('POST', '/api/inventario.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Ativo atualizado!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao atualizar', 'danger');
            }
        });
}

function editarAtivo() {
    const tecOpts = '<option value="">Não atribuído</option>' +
        <?php
        $optsJs = '';
        foreach ($tecnicos as $t) {
            $sel = ($ativo['responsavel_id'] ?? '') == $t['id'] ? 'selected' : '';
            $optsJs .= '<option value="' . $t['id'] . '" ' . $sel . '>' . addslashes($t['nome']) . '</option>';
        }
        echo "'" . addslashes($optsJs) . "'";
        ?>;

    const html = `
    <form id="formEditarAtivo" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" class="form-input" required value="<?= htmlspecialchars($ativo['nome']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-input" value="<?= htmlspecialchars($ativo['modelo'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Fabricante</label>
            <input type="text" name="fabricante" class="form-input" value="<?= htmlspecialchars($ativo['fabricante'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Nº Série</label>
            <input type="text" name="numero_serie" class="form-input" value="<?= htmlspecialchars($ativo['numero_serie'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Localização</label>
            <input type="text" name="localizacao" class="form-input" value="<?= htmlspecialchars($ativo['localizacao'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Responsável</label>
            <select name="responsavel_id" class="form-select">${tecOpts}</select>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Especificações</label>
            <textarea name="especificacoes" class="form-textarea" rows="3"><?= htmlspecialchars($ativo['especificacoes'] ?? '') ?></textarea>
        </div>
    </form>`;

    HelpDesk.showModal('Editar Ativo', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarAtivo()"><i class="fas fa-save"></i> Salvar</button>
    `);
}

function salvarAtivo() {
    const form = document.getElementById('formEditarAtivo');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'atualizar';
    data.id = <?= $ativo['id'] ?>;

    HelpDesk.api('POST', '/api/inventario.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Ativo atualizado!', 'success');
                HelpDesk.closeModal();
                setTimeout(() => location.reload(), 800);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao atualizar', 'danger');
            }
        });
}
</script>
