<?php
/**
 * View: Inventário de TI
 */
$tiposAtivo = ['computador'=>'Computador','servidor'=>'Servidor','switch'=>'Switch','roteador'=>'Roteador','impressora'=>'Impressora','software'=>'Software','monitor'=>'Monitor','telefone'=>'Telefone','outro'=>'Outro'];
$statusAtivo = ['ativo'=>'Ativo','manutencao'=>'Manutenção','inativo'=>'Inativo','descartado'=>'Descartado'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Inventário de TI</h1>
        <p class="page-subtitle">Gestão de ativos de tecnologia</p>
    </div>
    <div class="page-actions">
        <select class="form-select" onchange="location.href='<?= BASE_URL ?>/index.php?page=inventario&tipo='+this.value">
            <option value="">Todos os Tipos</option>
            <?php foreach ($tiposAtivo as $k => $v): ?>
            <option value="<?= $k ?>" <?= ($_GET['tipo'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoAtivo')">
            <i class="fas fa-plus"></i> Novo Ativo
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Patrimônio</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Modelo</th>
                    <th>Localização</th>
                    <th>Responsável</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventarioItens)): ?>
                <tr><td colspan="8" class="text-center py-4"><div class="empty-state-sm"><i class="fas fa-boxes"></i><p>Nenhum ativo encontrado</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($inventarioItens as $item): ?>
                <tr>
                    <td><span class="code-badge"><?= $item['numero_patrimonio'] ?? '-' ?></span></td>
                    <td><strong><?= htmlspecialchars($item['nome']) ?></strong></td>
                    <td><?= $tiposAtivo[$item['tipo']] ?? $item['tipo'] ?></td>
                    <td><?= htmlspecialchars($item['modelo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['localizacao'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['responsavel_nome'] ?? '-') ?></td>
                    <td>
                        <?php
                        $cores = ['ativo'=>'#10B981','manutencao'=>'#F59E0B','inativo'=>'#6B7280','descartado'=>'#EF4444'];
                        $cor = $cores[$item['status']] ?? '#6B7280';
                        ?>
                        <span class="status-badge" style="background:<?= $cor ?>20;color:<?= $cor ?>"><?= $statusAtivo[$item['status']] ?? $item['status'] ?></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-ghost" onclick="editarAtivo(<?= $item['id'] ?>)"><i class="fas fa-edit"></i></button>
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
HelpDesk.modals.novoAtivo = function() {
    let tipoOpts = '';
    <?php foreach ($tiposAtivo as $k => $v): ?>
    tipoOpts += '<option value="<?= $k ?>"><?= $v ?></option>';
    <?php endforeach; ?>
    let tecOpts = '<option value="">Nenhum</option>';
    <?php foreach ($tecnicos as $t): ?>
    tecOpts += '<option value="<?= $t['id'] ?>"><?= addslashes($t['nome']) ?></option>';
    <?php endforeach; ?>

    const html = `
    <form id="formNovoAtivo" class="form-grid">
        <div class="form-group">
            <label class="form-label">Tipo *</label>
            <select name="tipo" class="form-select" required>${tipoOpts}</select>
        </div>
        <div class="form-group">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Nº Patrimônio</label>
            <input type="text" name="numero_patrimonio" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Fabricante</label>
            <input type="text" name="fabricante" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Nº Série</label>
            <input type="text" name="numero_serie" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Localização</label>
            <input type="text" name="localizacao" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Responsável</label>
            <select name="responsavel_id" class="form-select">${tecOpts}</select>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Especificações</label>
            <textarea name="especificacoes" class="form-textarea" rows="3"></textarea>
        </div>
    </form>`;

    HelpDesk.showModal('Novo Ativo', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovoAtivo()"><i class="fas fa-save"></i> Cadastrar</button>
    `);
};

function submitNovoAtivo() {
    const form = document.getElementById('formNovoAtivo');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';
    HelpDesk.api('POST', '/api/inventario.php', data).then(resp => {
        if (resp.success) { HelpDesk.toast('Ativo cadastrado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}

function editarAtivo(id) {
    HelpDesk.api('GET', '/api/inventario.php?action=ver&id=' + id).then(item => {
        if (!item) return;
        HelpDesk.showModal(item.nome, `
        <div class="detail-info">
            <div class="info-row"><span class="info-label">Patrimônio</span><span class="info-value">${item.numero_patrimonio || '-'}</span></div>
            <div class="info-row"><span class="info-label">Tipo</span><span class="info-value">${item.tipo}</span></div>
            <div class="info-row"><span class="info-label">Modelo</span><span class="info-value">${item.modelo || '-'}</span></div>
            <div class="info-row"><span class="info-label">Fabricante</span><span class="info-value">${item.fabricante || '-'}</span></div>
            <div class="info-row"><span class="info-label">Nº Série</span><span class="info-value">${item.numero_serie || '-'}</span></div>
            <div class="info-row"><span class="info-label">Localização</span><span class="info-value">${item.localizacao || '-'}</span></div>
            <div class="info-row"><span class="info-label">Responsável</span><span class="info-value">${item.responsavel_nome || '-'}</span></div>
            <div class="info-row"><span class="info-label">Especificações</span><span class="info-value">${item.especificacoes || '-'}</span></div>
        </div>`, '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>');
    });
}
</script>
