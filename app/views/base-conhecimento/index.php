<?php
/**
 * View: Base de Conhecimento
 */
$categoriasBC = [];
foreach ($artigos ?? [] as $a) {
    $cat = $a['categoria_nome'] ?? 'Geral';
    if (!isset($categoriasBC[$cat])) $categoriasBC[$cat] = [];
    $categoriasBC[$cat][] = $a;
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Base de Conhecimento</h1>
        <p class="page-subtitle">Documentação e soluções técnicas</p>
    </div>
    <div class="page-actions">
        <div class="search-box" style="width:300px">
            <i class="fas fa-search"></i>
            <input type="text" id="searchKB" class="form-input" placeholder="Buscar artigos..." value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>" onkeydown="if(event.key==='Enter')buscarKB()">
        </div>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoArtigo')">
            <i class="fas fa-plus"></i> Novo Artigo
        </button>
    </div>
</div>

<?php if (empty($artigos)): ?>
<div class="empty-state">
    <i class="fas fa-book-open"></i>
    <h3>Nenhum artigo encontrado</h3>
    <p>Comece a criar artigos para construir sua base de conhecimento</p>
</div>
<?php else: ?>
<div class="kb-grid">
    <?php foreach ($artigos as $artigo): ?>
    <div class="card kb-card" onclick="verArtigo(<?= $artigo['id'] ?>)">
        <div class="card-body">
            <div class="kb-card-header">
                <span class="kb-categoria"><?= htmlspecialchars($artigo['categoria_nome'] ?? 'Geral') ?></span>
                <span class="kb-views"><i class="fas fa-eye"></i> <?= $artigo['visualizacoes'] ?? 0 ?></span>
            </div>
            <h3 class="kb-title"><?= htmlspecialchars($artigo['titulo']) ?></h3>
            <p class="kb-excerpt"><?= htmlspecialchars(mb_substr(strip_tags($artigo['problema'] ?? ''), 0, 150)) ?>...</p>
            <div class="kb-card-footer">
                <span class="kb-author"><i class="fas fa-user"></i> <?= htmlspecialchars($artigo['autor_nome'] ?? 'Sistema') ?></span>
                <span class="kb-date"><?= tempoRelativo($artigo['criado_em']) ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function buscarKB() {
    const q = document.getElementById('searchKB').value;
    location.href = '<?= BASE_URL ?>/index.php?page=base-conhecimento&busca=' + encodeURIComponent(q);
}

HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novoArtigo = function() {
    let catOpts = '<option value="Geral">Geral</option>';
    <?php foreach ($categorias as $c): ?>
    catOpts += '<option value="<?= addslashes(htmlspecialchars($c['nome'])) ?>"><?= addslashes(htmlspecialchars($c['nome'])) ?></option>';
    <?php endforeach; ?>

    const html = `
    <form id="formNovoArtigo">
        <div class="form-group">
            <label class="form-label">Título *</label>
            <input type="text" name="titulo" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Categoria</label>
            <select name="categoria" class="form-select">${catOpts}</select>
        </div>
        <div class="form-group">
            <label class="form-label">Problema / Pergunta *</label>
            <textarea name="problema" class="form-textarea" rows="5" required placeholder="Descreva o problema ou pergunta"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Solução / Resposta *</label>
            <textarea name="solucao" class="form-textarea" rows="5" required placeholder="Descreva a solução ou resposta"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Tags (separadas por vírgula)</label>
            <input type="text" name="tags" class="form-input" placeholder="Ex: rede, wifi, configuração">
        </div>
    </form>`;

    HelpDesk.showModal('Novo Artigo', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovoArtigo()"><i class="fas fa-save"></i> Publicar</button>
    `, 'modal-lg');
};

function submitNovoArtigo() {
    const form = document.getElementById('formNovoArtigo');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';
    data.autor_id = '<?= currentUser()['id'] ?? '' ?>';
    HelpDesk.api('POST', '/api/base-conhecimento.php', data).then(resp => {
        if (resp.success) { HelpDesk.toast('Artigo publicado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}

function verArtigo(id) {
    HelpDesk.api('GET', '/api/base-conhecimento.php?action=ver&id=' + id).then(artigo => {
        if (!artigo) return;
        const conteudo = artigo.conteudo.replace(/\n/g, '<br>');
        HelpDesk.showModal(artigo.titulo, `
            <div class="kb-article-view">
                <div class="kb-article-meta">
                    <span><i class="fas fa-folder"></i> ${artigo.categoria || 'Geral'}</span>
                    <span><i class="fas fa-user"></i> ${artigo.autor_nome || 'Sistema'}</span>
                    <span><i class="fas fa-eye"></i> ${artigo.visualizacoes || 0} visualizações</span>
                    <span><i class="fas fa-clock"></i> ${artigo.created_at}</span>
                </div>
                <div class="kb-article-content">${conteudo}</div>
                ${artigo.tags ? '<div class="kb-article-tags">' + artigo.tags.split(',').map(t => '<span class="tag">' + t.trim() + '</span>').join('') + '</div>' : ''}
            </div>
        `, '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>', 'modal-lg');
    });
}
</script>
