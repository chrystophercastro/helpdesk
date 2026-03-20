<?php
/**
 * View: Posts / Timeline — Feed tipo Instagram
 * Todos os departamentos veem todos os posts.
 * Rich editor com suporte a fotos, vídeos e texto formatado.
 */
$user = currentUser();
?>

<!-- ========== TIMELINE CSS ========== -->
<style>
/* Timeline Container */
.tl-container { max-width: 680px; margin: 0 auto; padding: 0 16px; }
.tl-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.tl-header h1 { font-size: 24px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
.tl-header h1 i { color: var(--primary); }

/* Composer */
.tl-composer { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: box-shadow 0.2s; }
.tl-composer:focus-within { box-shadow: 0 4px 20px rgba(99,102,241,0.12); border-color: var(--primary); }
.tl-composer-top { display: flex; gap: 12px; align-items: flex-start; }
.tl-composer-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; }
.tl-composer-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.tl-composer-input { flex: 1; }
.tl-composer-input .tl-editor { min-height: 60px; max-height: 300px; overflow-y: auto; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 12px; font-size: 15px; line-height: 1.6; outline: none; color: var(--text-primary); background: var(--bg-secondary, #f9fafb); transition: border-color 0.2s; }
.tl-editor:focus { border-color: var(--primary); background: var(--card-bg); }
.tl-editor:empty:before { content: attr(data-placeholder); color: var(--text-muted, #9ca3af); pointer-events: none; }
.tl-editor img { max-width: 100%; border-radius: 8px; margin: 8px 0; }

/* Toolbar */
.tl-toolbar { display: flex; align-items: center; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); }
.tl-toolbar-actions { display: flex; gap: 4px; }
.tl-toolbar-btn { background: none; border: none; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); font-size: 17px; transition: all 0.15s; }
.tl-toolbar-btn:hover { background: var(--primary-bg, #eef2ff); color: var(--primary); }
.tl-toolbar-btn.active { background: var(--primary-bg, #eef2ff); color: var(--primary); }
.tl-toolbar-right { display: flex; align-items: center; gap: 10px; }
.tl-char-count { font-size: 12px; color: var(--text-muted); }
.tl-btn-post { background: var(--primary); color: #fff; border: none; padding: 8px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s; }
.tl-btn-post:hover { background: var(--primary-hover, #4f46e5); transform: translateY(-1px); }
.tl-btn-post:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* Media Preview */
.tl-media-preview { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.tl-media-thumb { position: relative; width: 100px; height: 100px; border-radius: 10px; overflow: hidden; border: 2px solid var(--border-color); }
.tl-media-thumb img, .tl-media-thumb video { width: 100%; height: 100%; object-fit: cover; }
.tl-media-thumb .tl-media-remove { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; background: rgba(0,0,0,0.7); color: #fff; border: none; border-radius: 50%; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.tl-media-thumb .tl-media-video-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); color: #fff; font-size: 24px; text-shadow: 0 2px 6px rgba(0,0,0,0.5); pointer-events: none; }

/* Options row */
.tl-options { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
.tl-option-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; background: #fef3c7; color: #92400e; cursor: pointer; }
.tl-option-badge input { margin: 0; }

/* === FEED === */
.tl-feed { display: flex; flex-direction: column; gap: 20px; }
.tl-post { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; transition: box-shadow 0.2s; }
.tl-post:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.tl-post.pinned { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b22; }
.tl-post.pinned::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.tl-post { position: relative; }

/* Post Header */
.tl-post-header { display: flex; align-items: center; padding: 16px 18px 0; gap: 12px; }
.tl-post-avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
.tl-post-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.tl-post-userinfo { flex: 1; }
.tl-post-username { font-weight: 600; font-size: 14px; color: var(--text-primary); }
.tl-post-usermeta { font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; margin-top: 2px; }
.tl-dept-badge { display: inline-flex; align-items: center; gap: 3px; padding: 1px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.tl-post-time { font-size: 12px; color: var(--text-muted); }
.tl-post-menu { background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px 8px; border-radius: 8px; font-size: 16px; }
.tl-post-menu:hover { background: var(--bg-secondary); color: var(--text-primary); }
.tl-pinned-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #f59e0b; font-weight: 600; }

/* Post Content */
.tl-post-body { padding: 12px 18px 0; }
.tl-post-text { font-size: 15px; line-height: 1.7; color: var(--text-primary); word-break: break-word; }
.tl-post-text a { color: var(--primary); text-decoration: none; }
.tl-post-text a:hover { text-decoration: underline; }

/* Post Media */
.tl-post-media { margin-top: 12px; }
.tl-post-media-single img { width: 100%; max-height: 520px; object-fit: cover; cursor: pointer; transition: filter 0.2s; }
.tl-post-media-single img:hover { filter: brightness(0.95); }
.tl-post-media-single video { width: 100%; max-height: 520px; border-radius: 0; }
.tl-post-media-grid { display: grid; gap: 3px; }
.tl-post-media-grid.grid-2 { grid-template-columns: 1fr 1fr; }
.tl-post-media-grid.grid-3 { grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
.tl-post-media-grid.grid-3 .tl-grid-item:first-child { grid-row: 1 / 3; }
.tl-post-media-grid.grid-4 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
.tl-grid-item { position: relative; overflow: hidden; min-height: 200px; cursor: pointer; }
.tl-grid-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
.tl-grid-item:hover img { transform: scale(1.03); }
.tl-grid-extra { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 28px; font-weight: 700; }

/* Post Actions */
.tl-post-actions { display: flex; align-items: center; padding: 8px 18px; border-top: 1px solid var(--border-color); margin-top: 12px; }
.tl-action-btn { display: flex; align-items: center; gap: 6px; background: none; border: none; padding: 8px 16px; border-radius: 10px; font-size: 14px; color: var(--text-secondary); cursor: pointer; font-weight: 500; transition: all 0.15s; flex: 1; justify-content: center; }
.tl-action-btn:hover { background: var(--bg-secondary); color: var(--primary); }
.tl-action-btn.liked { color: #ef4444; }
.tl-action-btn.liked i { animation: likeAnim 0.4s ease; }
@keyframes likeAnim { 0%,100%{transform:scale(1)} 50%{transform:scale(1.3)} }

/* Comments Section */
.tl-comments { padding: 0 18px 14px; }
.tl-comments-toggle { background: none; border: none; color: var(--text-secondary); font-size: 13px; font-weight: 500; cursor: pointer; padding: 6px 0; }
.tl-comments-toggle:hover { color: var(--primary); }
.tl-comments-list { margin-top: 8px; }
.tl-comment { display: flex; gap: 10px; padding: 8px 0; }
.tl-comment-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--bg-secondary); color: var(--text-secondary); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.tl-comment-body { flex: 1; background: var(--bg-secondary, #f3f4f6); padding: 8px 12px; border-radius: 12px; }
.tl-comment-author { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.tl-comment-text { font-size: 13px; color: var(--text-secondary); margin-top: 2px; line-height: 1.5; }
.tl-comment-time { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
.tl-comment-input { display: flex; gap: 8px; margin-top: 8px; align-items: center; }
.tl-comment-input input { flex: 1; border: 1px solid var(--border-color); border-radius: 20px; padding: 8px 16px; font-size: 13px; outline: none; background: var(--bg-secondary); transition: border-color 0.2s; }
.tl-comment-input input:focus { border-color: var(--primary); background: var(--card-bg); }
.tl-comment-input button { background: var(--primary); color: #fff; border: none; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; transition: background 0.2s; flex-shrink: 0; }
.tl-comment-input button:hover { background: var(--primary-hover); }

/* Post Dropdown Menu */
.tl-dropdown { position: relative; display: inline-block; }
.tl-dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); min-width: 180px; z-index: 100; overflow: hidden; }
.tl-dropdown-menu.show { display: block; }
.tl-dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 14px; color: var(--text-primary); cursor: pointer; transition: background 0.1s; border: none; background: none; width: 100%; text-align: left; }
.tl-dropdown-item:hover { background: var(--bg-secondary); }
.tl-dropdown-item.danger { color: #ef4444; }

/* Lightbox */
.tl-lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.92); z-index: 9999; align-items: center; justify-content: center; }
.tl-lightbox.active { display: flex; }
.tl-lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 8px; }
.tl-lightbox-close { position: absolute; top: 20px; right: 24px; color: #fff; font-size: 28px; cursor: pointer; background: rgba(255,255,255,0.15); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: none; }

/* Loading spinner */
.tl-loading { text-align: center; padding: 40px; color: var(--text-muted); }
.tl-loading i { font-size: 28px; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty state */
.tl-empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.tl-empty i { font-size: 48px; margin-bottom: 16px; opacity: 0.3; }
.tl-empty h3 { font-size: 18px; color: var(--text-secondary); margin-bottom: 8px; }
.tl-empty p { font-size: 14px; }

/* Load More */
.tl-load-more { text-align: center; padding: 20px; }
.tl-load-more button { background: var(--bg-secondary); border: 1px solid var(--border-color); padding: 10px 32px; border-radius: 12px; font-size: 14px; font-weight: 500; color: var(--text-secondary); cursor: pointer; transition: all 0.2s; }
.tl-load-more button:hover { background: var(--primary-bg); color: var(--primary); border-color: var(--primary); }

/* Edit modal */
.tl-edit-area { width: 100%; min-height: 120px; border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; font-size: 15px; font-family: inherit; resize: vertical; outline: none; }
.tl-edit-area:focus { border-color: var(--primary); }

/* Responsive */
@media (max-width: 768px) {
    .tl-container { padding: 0 8px; }
    .tl-composer { padding: 14px; }
    .tl-post-header { padding: 12px 14px 0; }
    .tl-post-body { padding: 10px 14px 0; }
    .tl-post-actions { padding: 6px 14px; }
    .tl-comments { padding: 0 14px 10px; }
}
</style>

<!-- ========== TIMELINE HTML ========== -->
<div class="tl-container">
    <div class="tl-header">
        <h1><i class="fas fa-stream"></i> Mural</h1>
    </div>

    <!-- Composer -->
    <div class="tl-composer" id="tlComposer">
        <div class="tl-composer-top">
            <div class="tl-composer-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= UPLOAD_URL ?>/avatars/<?= $user['avatar'] ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($user['nome'] ?? 'U', 0, 2)) ?>
                <?php endif; ?>
            </div>
            <div class="tl-composer-input">
                <div class="tl-editor" id="tlEditor" contenteditable="true" data-placeholder="O que está acontecendo no seu departamento?" oninput="tlUpdateCharCount()"></div>
            </div>
        </div>

        <div class="tl-media-preview" id="tlMediaPreview"></div>

        <?php if (isAdmin()): ?>
        <div class="tl-options">
            <label class="tl-option-badge"><input type="checkbox" id="tlFixado"> <i class="fas fa-thumbtack"></i> Fixar no topo</label>
            <label class="tl-option-badge" style="background:#fee2e2;color:#991b1b"><input type="checkbox" id="tlComunicado"> <i class="fas fa-bullhorn"></i> Comunicado</label>
        </div>
        <?php endif; ?>

        <div class="tl-toolbar">
            <div class="tl-toolbar-actions">
                <button class="tl-toolbar-btn" onclick="tlTriggerUpload('imagem')" title="Foto"><i class="fas fa-image"></i></button>
                <button class="tl-toolbar-btn" onclick="tlTriggerUpload('video')" title="Vídeo"><i class="fas fa-video"></i></button>
                <button class="tl-toolbar-btn" onclick="document.execCommand('bold')" title="Negrito"><i class="fas fa-bold"></i></button>
                <button class="tl-toolbar-btn" onclick="document.execCommand('italic')" title="Itálico"><i class="fas fa-italic"></i></button>
                <button class="tl-toolbar-btn" onclick="document.execCommand('createLink', false, prompt('URL:'))" title="Link"><i class="fas fa-link"></i></button>
            </div>
            <div class="tl-toolbar-right">
                <span class="tl-char-count" id="tlCharCount"></span>
                <button class="tl-btn-post" id="tlBtnPost" onclick="tlSubmitPost()">
                    <i class="fas fa-paper-plane"></i> Publicar
                </button>
            </div>
        </div>
        <input type="file" id="tlFileInput" multiple accept="image/*,video/*" style="display:none" onchange="tlHandleFiles(this.files)">
    </div>

    <!-- Feed -->
    <div class="tl-feed" id="tlFeed">
        <div class="tl-loading" id="tlLoading"><i class="fas fa-spinner"></i><p>Carregando posts...</p></div>
    </div>

    <!-- Load More -->
    <div class="tl-load-more" id="tlLoadMore" style="display:none">
        <button onclick="tlLoadMore()">Carregar mais posts</button>
    </div>
</div>

<!-- Lightbox -->
<div class="tl-lightbox" id="tlLightbox" onclick="tlCloseLightbox()">
    <button class="tl-lightbox-close"><i class="fas fa-times"></i></button>
    <img id="tlLightboxImg" src="" alt="">
</div>

<!-- ========== TIMELINE JAVASCRIPT ========== -->
<script>
const TL = {
    currentPage: 1,
    totalPages: 1,
    loading: false,
    pendingFiles: [],
    userId: <?= $user['id'] ?? 0 ?>,
    isAdmin: <?= isAdmin() ? 'true' : 'false' ?>
};

/* ---- LOAD POSTS ---- */
function tlLoadPosts(page = 1, append = false) {
    if (TL.loading) return;
    TL.loading = true;

    if (!append) {
        document.getElementById('tlFeed').innerHTML = '<div class="tl-loading"><i class="fas fa-spinner"></i><p>Carregando...</p></div>';
    }

    HelpDesk.api('GET', '/api/posts.php', { action: 'listar', pagina: page })
        .then(data => {
            if (!data || data.error) {
                document.getElementById('tlFeed').innerHTML = `
                    <div class="tl-empty">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Erro ao carregar posts</h3>
                        <p>${data?.error || 'Tente recarregar a página.'}</p>
                    </div>`;
                TL.loading = false;
                return;
            }

            TL.currentPage = data.pagina;
            TL.totalPages = data.total_paginas;

            const feed = document.getElementById('tlFeed');
            if (!append) feed.innerHTML = '';

            if (data.posts.length === 0 && !append) {
                feed.innerHTML = `
                    <div class="tl-empty">
                        <i class="fas fa-stream"></i>
                        <h3>Nenhuma publicação ainda</h3>
                        <p>Seja o primeiro a compartilhar algo com a empresa!</p>
                    </div>`;
            } else {
                data.posts.forEach(post => {
                    feed.insertAdjacentHTML('beforeend', tlRenderPost(post));
                });
            }

            document.getElementById('tlLoadMore').style.display =
                TL.currentPage < TL.totalPages ? '' : 'none';
            TL.loading = false;
        })
        .catch(() => { TL.loading = false; });
}

function tlLoadMore() {
    tlLoadPosts(TL.currentPage + 1, true);
}

/* ---- RENDER POST ---- */
function tlRenderPost(p) {
    const initials = (p.autor_nome || 'U').substring(0, 2).toUpperCase();
    const avatarHtml = p.autor_avatar
        ? `<img src="<?= UPLOAD_URL ?>/avatars/${p.autor_avatar}" alt="">`
        : initials;
    const deptBadge = p.departamento_sigla
        ? `<span class="tl-dept-badge" style="background:${p.departamento_cor}18;color:${p.departamento_cor}">${p.departamento_sigla}</span>`
        : '';
    const pinBadge = p.fixado == 1 ? '<span class="tl-pinned-badge"><i class="fas fa-thumbtack"></i> Fixado</span>' : '';
    const timeAgo = tlTimeAgo(p.criado_em);
    const isOwner = (p.usuario_id == TL.userId) || TL.isAdmin;
    const likedClass = p.liked ? 'liked' : '';
    const heartIcon = p.liked ? 'fas fa-heart' : 'far fa-heart';

    // Media
    let mediaHtml = '';
    if (p.midia && p.midia.length > 0) {
        if (p.midia.length === 1) {
            const m = p.midia[0];
            if (m.tipo === 'video') {
                mediaHtml = `<div class="tl-post-media"><div class="tl-post-media-single"><video controls preload="metadata"><source src="${m.url}"></video></div></div>`;
            } else {
                mediaHtml = `<div class="tl-post-media"><div class="tl-post-media-single"><img src="${m.url}" alt="" onclick="tlOpenLightbox('${m.url}')"></div></div>`;
            }
        } else {
            const gridClass = p.midia.length === 2 ? 'grid-2' : p.midia.length === 3 ? 'grid-3' : 'grid-4';
            let items = '';
            const showMax = Math.min(p.midia.length, 4);
            for (let i = 0; i < showMax; i++) {
                const m = p.midia[i];
                const extra = (i === 3 && p.midia.length > 4) ? `<div class="tl-grid-extra">+${p.midia.length - 4}</div>` : '';
                items += `<div class="tl-grid-item" onclick="tlOpenLightbox('${m.url}')"><img src="${m.url}" alt="">${extra}</div>`;
            }
            mediaHtml = `<div class="tl-post-media"><div class="tl-post-media-grid ${gridClass}">${items}</div></div>`;
        }
    }

    // Menu
    const menuHtml = isOwner ? `
        <div class="tl-dropdown">
            <button class="tl-post-menu" onclick="tlToggleMenu(event, ${p.id})"><i class="fas fa-ellipsis-h"></i></button>
            <div class="tl-dropdown-menu" id="tlMenu-${p.id}">
                <button class="tl-dropdown-item" onclick="tlEditPost(${p.id})"><i class="fas fa-pen"></i> Editar</button>
                ${TL.isAdmin ? `<button class="tl-dropdown-item" onclick="tlTogglePin(${p.id}, ${p.fixado})"><i class="fas fa-thumbtack"></i> ${p.fixado == 1 ? 'Desafixar' : 'Fixar'}</button>` : ''}
                <button class="tl-dropdown-item danger" onclick="tlDeletePost(${p.id})"><i class="fas fa-trash"></i> Excluir</button>
            </div>
        </div>` : '';

    return `
    <div class="tl-post ${p.fixado == 1 ? 'pinned' : ''}" id="tlPost-${p.id}" data-id="${p.id}">
        <div class="tl-post-header">
            <div class="tl-post-avatar">${avatarHtml}</div>
            <div class="tl-post-userinfo">
                <div class="tl-post-username">${tlEscape(p.autor_nome)}</div>
                <div class="tl-post-usermeta">
                    ${deptBadge}
                    <span>${p.autor_cargo || ''}</span>
                    ${pinBadge}
                </div>
            </div>
            <span class="tl-post-time" title="${p.criado_em}">${timeAgo}</span>
            ${menuHtml}
        </div>
        <div class="tl-post-body">
            <div class="tl-post-text">${tlFormatContent(p.conteudo)}</div>
            ${mediaHtml}
        </div>
        <div class="tl-post-actions">
            <button class="tl-action-btn ${likedClass}" id="tlLike-${p.id}" onclick="tlToggleLike(${p.id})">
                <i class="${heartIcon}"></i> <span>${p.likes_count || 0}</span>
            </button>
            <button class="tl-action-btn" onclick="tlToggleComments(${p.id})">
                <i class="far fa-comment"></i> <span>${p.comentarios_count || 0}</span>
            </button>
            <button class="tl-action-btn" onclick="tlSharePost(${p.id})">
                <i class="far fa-share-square"></i> Compartilhar
            </button>
        </div>
        <div class="tl-comments" id="tlComments-${p.id}" style="display:none">
            <div class="tl-comments-list" id="tlCommentsList-${p.id}"></div>
            <div class="tl-comment-input">
                <input type="text" id="tlCommentInput-${p.id}" placeholder="Escreva um comentário..." onkeydown="if(event.key==='Enter')tlSubmitComment(${p.id})">
                <button onclick="tlSubmitComment(${p.id})"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>`;
}

/* ---- CREATE POST ---- */
function tlSubmitPost() {
    const editor = document.getElementById('tlEditor');
    const content = editor.innerHTML.trim();
    const textOnly = editor.innerText.trim();

    if (!textOnly && TL.pendingFiles.length === 0) {
        HelpDesk.toast('Escreva algo ou adicione uma mídia', 'warning');
        return;
    }

    const btn = document.getElementById('tlBtnPost');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';

    const formData = new FormData();
    formData.append('action', 'criar');
    formData.append('conteudo', content);

    if (TL.isAdmin) {
        if (document.getElementById('tlFixado')?.checked) formData.append('fixado', '1');
        if (document.getElementById('tlComunicado')?.checked) formData.append('tipo', 'comunicado');
    }

    TL.pendingFiles.forEach(f => formData.append('midia[]', f));

    fetch('<?= BASE_URL ?>/api/posts.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            HelpDesk.toast('Post publicado!', 'success');
            editor.innerHTML = '';
            TL.pendingFiles = [];
            document.getElementById('tlMediaPreview').innerHTML = '';
            if (document.getElementById('tlFixado')) document.getElementById('tlFixado').checked = false;
            if (document.getElementById('tlComunicado')) document.getElementById('tlComunicado').checked = false;
            tlLoadPosts(1);
        } else {
            HelpDesk.toast(data.error || 'Erro ao publicar', 'danger');
        }
    })
    .catch(() => HelpDesk.toast('Erro de conexão', 'danger'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Publicar';
    });
}

/* ---- MEDIA UPLOAD ---- */
function tlTriggerUpload(tipo) {
    const input = document.getElementById('tlFileInput');
    input.accept = tipo === 'video' ? 'video/*' : 'image/*';
    input.click();
}

function tlHandleFiles(files) {
    const preview = document.getElementById('tlMediaPreview');
    Array.from(files).forEach(file => {
        if (file.size > 50 * 1024 * 1024) {
            HelpDesk.toast(`${file.name} é muito grande (máx 50MB)`, 'warning');
            return;
        }
        TL.pendingFiles.push(file);
        const idx = TL.pendingFiles.length - 1;
        const isVideo = file.type.startsWith('video/');
        const url = URL.createObjectURL(file);
        preview.insertAdjacentHTML('beforeend', `
            <div class="tl-media-thumb" id="tlThumb-${idx}">
                ${isVideo ? `<video src="${url}" muted></video><div class="tl-media-video-icon"><i class="fas fa-play"></i></div>` : `<img src="${url}" alt="">`}
                <button class="tl-media-remove" onclick="tlRemoveFile(${idx})"><i class="fas fa-times"></i></button>
            </div>
        `);
    });
    document.getElementById('tlFileInput').value = '';
}

function tlRemoveFile(idx) {
    TL.pendingFiles[idx] = null;
    const thumb = document.getElementById('tlThumb-' + idx);
    if (thumb) thumb.remove();
    TL.pendingFiles = TL.pendingFiles.filter(f => f !== null);
}

/* ---- LIKE ---- */
function tlToggleLike(postId) {
    HelpDesk.api('POST', '/api/posts.php', { action: 'like', post_id: postId })
        .then(data => {
            const btn = document.getElementById('tlLike-' + postId);
            if (!btn) return;
            btn.className = 'tl-action-btn ' + (data.liked ? 'liked' : '');
            btn.innerHTML = `<i class="${data.liked ? 'fas' : 'far'} fa-heart"></i> <span>${data.likes_count}</span>`;
        });
}

/* ---- COMMENTS ---- */
function tlToggleComments(postId) {
    const section = document.getElementById('tlComments-' + postId);
    if (!section) return;
    const showing = section.style.display !== 'none';
    section.style.display = showing ? 'none' : '';
    if (!showing) tlLoadComments(postId);
}

function tlLoadComments(postId) {
    const list = document.getElementById('tlCommentsList-' + postId);
    list.innerHTML = '<div class="tl-loading" style="padding:10px"><i class="fas fa-spinner"></i></div>';

    HelpDesk.api('GET', '/api/posts.php', { action: 'comentarios', post_id: postId })
        .then(comments => {
            if (!comments.length) {
                list.innerHTML = '<p style="color:var(--text-muted);font-size:13px;padding:8px 0">Nenhum comentário ainda.</p>';
                return;
            }
            list.innerHTML = comments.map(c => {
                const ci = (c.autor_nome || 'U').substring(0, 2).toUpperCase();
                const ca = c.autor_avatar
                    ? `<img src="<?= UPLOAD_URL ?>/avatars/${c.autor_avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover">`
                    : ci;
                return `
                    <div class="tl-comment">
                        <div class="tl-comment-avatar">${ca}</div>
                        <div>
                            <div class="tl-comment-body">
                                <span class="tl-comment-author">${tlEscape(c.autor_nome)}</span>
                                <div class="tl-comment-text">${tlEscape(c.conteudo)}</div>
                            </div>
                            <div class="tl-comment-time">${tlTimeAgo(c.criado_em)}</div>
                        </div>
                    </div>`;
            }).join('');
        });
}

function tlSubmitComment(postId) {
    const input = document.getElementById('tlCommentInput-' + postId);
    const conteudo = input.value.trim();
    if (!conteudo) return;

    input.disabled = true;
    HelpDesk.api('POST', '/api/posts.php', { action: 'comentar', post_id: postId, conteudo })
        .then(data => {
            if (data.success) {
                input.value = '';
                tlLoadComments(postId);
                // Update comment count in the button
                const btn = document.querySelector(`#tlPost-${postId} .tl-action-btn:nth-child(2) span`);
                if (btn) btn.textContent = data.comentarios_count;
            }
        })
        .finally(() => { input.disabled = false; input.focus(); });
}

/* ---- EDIT / DELETE / PIN ---- */
function tlEditPost(postId) {
    tlCloseAllMenus();
    HelpDesk.api('GET', '/api/posts.php', { action: 'ver', id: postId })
        .then(post => {
            if (post.error) return;
            const html = `<textarea class="tl-edit-area" id="tlEditArea">${post.conteudo.replace(/<[^>]*>/g, tag => tag)}</textarea>`;
            HelpDesk.showModal('Editar Post', html, `
                <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="tlSaveEdit(${postId})"><i class="fas fa-save"></i> Salvar</button>
            `);
        });
}

function tlSaveEdit(postId) {
    const area = document.getElementById('tlEditArea');
    if (!area) return;
    HelpDesk.api('POST', '/api/posts.php', { action: 'atualizar', id: postId, conteudo: area.value })
        .then(data => {
            if (data.success) {
                HelpDesk.toast('Post atualizado!', 'success');
                HelpDesk.closeModal();
                tlLoadPosts(1);
            }
        });
}

function tlDeletePost(postId) {
    tlCloseAllMenus();
    if (!confirm('Tem certeza que deseja excluir este post?')) return;
    HelpDesk.api('POST', '/api/posts.php', { action: 'deletar', id: postId })
        .then(data => {
            if (data.success) {
                HelpDesk.toast('Post excluído', 'success');
                const el = document.getElementById('tlPost-' + postId);
                if (el) el.style.display = 'none';
            }
        });
}

function tlTogglePin(postId, current) {
    tlCloseAllMenus();
    HelpDesk.api('POST', '/api/posts.php', { action: 'atualizar', id: postId, fixado: current == 1 ? 0 : 1 })
        .then(data => {
            if (data.success) {
                HelpDesk.toast(current == 1 ? 'Post desafixado' : 'Post fixado no topo!', 'success');
                tlLoadPosts(1);
            }
        });
}

/* ---- MENU ---- */
function tlToggleMenu(e, postId) {
    e.stopPropagation();
    tlCloseAllMenus();
    const menu = document.getElementById('tlMenu-' + postId);
    if (menu) menu.classList.toggle('show');
}

function tlCloseAllMenus() {
    document.querySelectorAll('.tl-dropdown-menu.show').forEach(m => m.classList.remove('show'));
}
document.addEventListener('click', tlCloseAllMenus);

/* ---- LIGHTBOX ---- */
function tlOpenLightbox(url) {
    document.getElementById('tlLightboxImg').src = url;
    document.getElementById('tlLightbox').classList.add('active');
}
function tlCloseLightbox() {
    document.getElementById('tlLightbox').classList.remove('active');
}

/* ---- SHARE ---- */
function tlSharePost(postId) {
    const url = window.location.origin + '<?= BASE_URL ?>/index.php?page=posts&id=' + postId;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => HelpDesk.toast('Link copiado!', 'success'));
    } else {
        prompt('Copie o link:', url);
    }
}

/* ---- HELPERS ---- */
function tlEscape(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function tlFormatContent(html) {
    if (!html) return '';
    // Make URLs clickable
    html = html.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    // Hashtags
    html = html.replace(/#(\w+)/g, '<a href="#" class="tl-hashtag">#$1</a>');
    return html;
}

function tlTimeAgo(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr.replace(' ', 'T'));
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'agora';
    if (diff < 3600) return Math.floor(diff / 60) + 'min';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd';
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
}

function tlUpdateCharCount() {
    const editor = document.getElementById('tlEditor');
    const count = editor.innerText.length;
    document.getElementById('tlCharCount').textContent = count > 0 ? count + ' caracteres' : '';
}

/* ---- INIT ---- */
document.addEventListener('DOMContentLoaded', () => tlLoadPosts(1));
</script>
