<?php
/**
 * View: Detalhes do Artigo - Base de Conhecimento
 */
if (!$artigo) {
    echo '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Artigo não encontrado</h3></div>';
    return;
}
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/index.php?page=base-conhecimento" class="btn btn-ghost btn-sm mb-2">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 class="page-title"><?= htmlspecialchars($artigo['titulo']) ?></h1>
        <p class="page-subtitle">
            <?php if (!empty($artigo['categoria_nome'])): ?>
            <span class="tag" style="background:#6366F120;color:#6366F1"><?= htmlspecialchars($artigo['categoria_nome']) ?></span>
            <?php endif; ?>
            <span style="color:#64748b;font-size:0.85rem">
                <i class="fas fa-user"></i> <?= htmlspecialchars($artigo['autor_nome'] ?? 'Anônimo') ?> &bull;
                <i class="fas fa-calendar"></i> <?= formatarData($artigo['criado_em'] ?? '', 'd/m/Y') ?> &bull;
                <i class="fas fa-eye"></i> <?= $artigo['visualizacoes'] ?? 0 ?> visualizações
            </span>
        </p>
    </div>
    <div class="page-actions">
        <button class="btn btn-outline" onclick="editarArtigo()">
            <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-success btn-sm" onclick="marcarUtil()">
            <i class="fas fa-thumbs-up"></i> Útil (<?= $artigo['util'] ?? 0 ?>)
        </button>
    </div>
</div>

<div class="grid-detail">
    <div class="detail-main">
        <!-- Problema -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle" style="color:#EF4444"></i> Problema / Pergunta</h3>
            </div>
            <div class="card-body">
                <div class="description-content" style="font-size:0.95rem;line-height:1.8">
                    <?= nl2br(htmlspecialchars($artigo['problema'] ?? 'Não informado')) ?>
                </div>
            </div>
        </div>

        <!-- Solução -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-check-circle" style="color:#10B981"></i> Solução / Resposta</h3>
            </div>
            <div class="card-body">
                <div class="description-content" style="font-size:0.95rem;line-height:1.8">
                    <?= nl2br(htmlspecialchars($artigo['solucao'] ?? 'Não informada')) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="detail-sidebar">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Informações</h3></div>
            <div class="card-body">
                <div class="detail-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-tag"></i> Categoria</span>
                        <span class="info-value"><?= htmlspecialchars($artigo['categoria_nome'] ?? 'Sem categoria') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user"></i> Autor</span>
                        <span class="info-value"><?= htmlspecialchars($artigo['autor_nome'] ?? 'Anônimo') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-eye"></i> Visualizações</span>
                        <span class="info-value"><?= $artigo['visualizacoes'] ?? 0 ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-thumbs-up"></i> Útil</span>
                        <span class="info-value"><?= $artigo['util'] ?? 0 ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-globe"></i> Publicado</span>
                        <span class="info-value"><?= ($artigo['publicado'] ?? 0) ? 'Sim' : 'Não' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar"></i> Criado em</span>
                        <span class="info-value"><?= formatarData($artigo['criado_em'] ?? '', 'd/m/Y H:i') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function marcarUtil() {
    HelpDesk.api('POST', '/api/base-conhecimento.php', {
        action: 'util', id: <?= $artigo['id'] ?>
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Obrigado pelo feedback!', 'success');
            setTimeout(() => location.reload(), 800);
        }
    });
}

function editarArtigo() {
    let catOpts = '<option value="">Sem categoria</option>';
    <?php foreach ($categorias as $cat): ?>
    catOpts += '<option value="<?= $cat['id'] ?>" <?= ($artigo['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= addslashes(htmlspecialchars($cat['nome'])) ?></option>';
    <?php endforeach; ?>

    const html = `
    <form id="formEditarArtigo" class="form-grid">
        <div class="form-group col-span-2">
            <label class="form-label">Título *</label>
            <input type="text" name="titulo" class="form-input" required value="<?= htmlspecialchars($artigo['titulo']) ?>">
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Categoria</label>
            <select name="categoria_id" class="form-select">${catOpts}</select>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Problema / Pergunta</label>
            <textarea name="problema" class="form-textarea" rows="5"><?= htmlspecialchars($artigo['problema'] ?? '') ?></textarea>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Solução / Resposta</label>
            <textarea name="solucao" class="form-textarea" rows="5"><?= htmlspecialchars($artigo['solucao'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Publicado</label>
            <select name="publicado" class="form-select">
                <option value="1" <?= ($artigo['publicado'] ?? 0) ? 'selected' : '' ?>>Sim</option>
                <option value="0" <?= ($artigo['publicado'] ?? 0) ? '' : 'selected' ?>>Não</option>
            </select>
        </div>
    </form>`;

    HelpDesk.showModal('Editar Artigo', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarArtigo()"><i class="fas fa-save"></i> Salvar</button>
    `, 'modal-lg');
}

function salvarArtigo() {
    const form = document.getElementById('formEditarArtigo');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'atualizar';
    data.id = <?= $artigo['id'] ?>;

    HelpDesk.api('POST', '/api/base-conhecimento.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Artigo atualizado!', 'success');
                HelpDesk.closeModal();
                setTimeout(() => location.reload(), 800);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao atualizar', 'danger');
            }
        });
}
</script>
