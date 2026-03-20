<?php
/**
 * View: Chat Interno
 * Interface completa tipo Slack/Teams
 */
$user = currentUser();
$userId = $user['id'];
?>

<style>
/* =====================================================
   CHAT — Full-page Slack-like Layout
   ===================================================== */
.chat-container {
    display: flex;
    height: calc(100vh - var(--topbar-height) - 32px);
    background: #fff;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

/* ---- Sidebar ---- */
.chat-sidebar {
    width: 320px;
    min-width: 320px;
    background: var(--gray-50);
    border-right: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
}
.chat-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 8px;
}
.chat-sidebar-header h2 {
    font-size: 16px;
    font-weight: 700;
    flex: 1;
    color: var(--gray-800);
}
.chat-sidebar-header .btn-icon {
    width: 34px; height: 34px;
    border-radius: var(--radius);
    border: none;
    background: transparent;
    color: var(--gray-500);
    cursor: pointer;
    font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition);
}
.chat-sidebar-header .btn-icon:hover { background: var(--gray-200); color: var(--gray-800); }
.chat-search {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
}
.chat-search input {
    width: 100%;
    padding: 8px 12px 8px 34px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 13px;
    background: #fff;
    outline: none;
    transition: var(--transition);
}
.chat-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-bg); }
.chat-search { position: relative; }
.chat-search i { position: absolute; left: 28px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 13px; }
.chat-section-title {
    padding: 10px 16px 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400);
}
.chat-list {
    flex: 1;
    overflow-y: auto;
}
.chat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    cursor: pointer;
    transition: var(--transition);
    border-left: 3px solid transparent;
    position: relative;
}
.chat-item:hover { background: var(--gray-100); }
.chat-item.active { background: var(--primary-bg); border-left-color: var(--primary); }
.chat-item-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px;
    color: #fff;
    flex-shrink: 0;
    position: relative;
}
.chat-item-avatar .presence-dot {
    position: absolute;
    bottom: 0; right: 0;
    width: 12px; height: 12px;
    border-radius: 50%;
    border: 2px solid var(--gray-50);
}
.presence-online { background: var(--success); }
.presence-ausente { background: var(--warning); }
.presence-ocupado { background: var(--danger); }
.presence-offline { background: var(--gray-300); }
.chat-item-info { flex: 1; min-width: 0; }
.chat-item-name {
    font-size: 13px; font-weight: 600;
    color: var(--gray-800);
    display: flex; align-items: center; gap: 6px;
}
.chat-item-name .time {
    font-size: 11px; font-weight: 400;
    color: var(--gray-400); margin-left: auto;
}
.chat-item-preview {
    font-size: 12px; color: var(--gray-500);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-top: 2px;
}
.chat-item-badge {
    background: var(--primary);
    color: #fff;
    font-size: 11px; font-weight: 700;
    min-width: 20px; height: 20px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 6px;
    flex-shrink: 0;
}
.chat-item-muted { opacity: 0.5; }

/* ---- Main Area ---- */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.chat-main-header {
    padding: 12px 20px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
}
.chat-main-header-info { flex: 1; }
.chat-main-header-info h3 {
    font-size: 15px; font-weight: 700; color: var(--gray-800);
    display: flex; align-items: center; gap: 8px;
}
.chat-main-header-info .subtitle {
    font-size: 12px; color: var(--gray-500); margin-top: 1px;
}
.chat-header-actions {
    display: flex; gap: 4px;
}
.chat-header-actions .btn-icon {
    width: 34px; height: 34px;
    border-radius: var(--radius);
    border: none;
    background: transparent;
    color: var(--gray-500);
    cursor: pointer;
    font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition);
}
.chat-header-actions .btn-icon:hover { background: var(--gray-100); color: var(--gray-800); }

/* Messages */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    background: var(--gray-50);
}
.chat-msg {
    display: flex;
    gap: 10px;
    padding: 6px 12px;
    border-radius: var(--radius);
    transition: background 0.15s;
    position: relative;
    max-width: 100%;
}
.chat-msg:hover { background: rgba(0,0,0,0.03); }
.chat-msg:hover .msg-actions { opacity: 1; }
.chat-msg-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
    color: #fff;
    flex-shrink: 0;
    margin-top: 2px;
}
.chat-msg-body { flex: 1; min-width: 0; }
.chat-msg-header {
    display: flex; align-items: baseline; gap: 8px;
}
.chat-msg-author {
    font-size: 13px; font-weight: 700;
    color: var(--gray-800);
}
.chat-msg-dept {
    font-size: 10px; font-weight: 600;
    padding: 1px 6px; border-radius: 4px;
}
.chat-msg-time {
    font-size: 11px; color: var(--gray-400);
}
.chat-msg-edited { font-size: 10px; color: var(--gray-400); font-style: italic; }
.chat-msg-content {
    font-size: 13.5px; color: var(--gray-700);
    line-height: 1.55;
    word-break: break-word;
    margin-top: 2px;
}
.chat-msg-content a { color: var(--primary); }
.chat-msg-content img {
    max-width: 320px; max-height: 240px;
    border-radius: var(--radius);
    margin-top: 6px;
    cursor: pointer;
}
.chat-msg-content .file-attachment {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 14px;
    background: #fff; border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 13px; color: var(--gray-700);
    text-decoration: none;
    margin-top: 6px;
    transition: var(--transition);
}
.chat-msg-content .file-attachment:hover { border-color: var(--primary); color: var(--primary); }
.chat-msg-content .file-attachment i { color: var(--gray-400); }
.chat-msg-reply-preview {
    background: var(--gray-100);
    border-left: 3px solid var(--primary);
    padding: 4px 10px;
    border-radius: 0 var(--radius) var(--radius) 0;
    margin-bottom: 4px;
    font-size: 12px;
    color: var(--gray-500);
    cursor: pointer;
}
.chat-msg-reply-preview strong { color: var(--gray-700); }
.chat-msg-reacoes {
    display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap;
}
.chat-msg-reacao {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    cursor: pointer;
    transition: var(--transition);
}
.chat-msg-reacao:hover { border-color: var(--primary); }
.chat-msg-reacao.minha { background: var(--primary-bg); border-color: var(--primary-light); }
.chat-msg-reacao span { font-size: 13px; }
.chat-msg-reacao small { color: var(--gray-500); }

/* Message hover actions */
.msg-actions {
    position: absolute;
    top: -8px; right: 12px;
    display: flex; gap: 2px;
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    padding: 2px;
    opacity: 0;
    transition: opacity 0.15s;
    z-index: 10;
}
.msg-actions button {
    width: 28px; height: 28px;
    border: none;
    background: transparent;
    color: var(--gray-500);
    cursor: pointer;
    border-radius: 4px;
    font-size: 12px;
    display: flex; align-items: center; justify-content: center;
}
.msg-actions button:hover { background: var(--gray-100); color: var(--gray-800); }

/* System message */
.chat-msg-sistema {
    text-align: center;
    padding: 8px 16px;
    font-size: 12px;
    color: var(--gray-400);
    font-style: italic;
}
.chat-msg-sistema i { margin-right: 4px; }

/* Date separator */
.chat-date-sep {
    display: flex; align-items: center; gap: 16px;
    padding: 12px 0;
    color: var(--gray-400);
    font-size: 12px; font-weight: 600;
}
.chat-date-sep::before, .chat-date-sep::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--gray-200);
}

/* Typing indicator */
.chat-typing {
    padding: 4px 20px 8px;
    font-size: 12px;
    color: var(--gray-500);
    min-height: 24px;
}
.chat-typing i { margin-right: 4px; }

/* Input area */
.chat-input-area {
    padding: 12px 20px 16px;
    background: #fff;
    border-top: 1px solid var(--gray-200);
}
.chat-reply-bar {
    display: none;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: var(--radius) var(--radius) 0 0;
    border: 1px solid var(--gray-200);
    border-bottom: none;
    font-size: 12px;
    color: var(--gray-500);
    align-items: center;
    gap: 8px;
    margin-bottom: -1px;
}
.chat-reply-bar.show { display: flex; }
.chat-reply-bar strong { color: var(--gray-700); }
.chat-reply-bar .close-reply {
    margin-left: auto; cursor: pointer;
    color: var(--gray-400);
    background: none; border: none; font-size: 14px;
}
.chat-input-wrap {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 8px 12px;
    transition: var(--transition);
}
.chat-input-wrap:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-bg); }
.chat-input-wrap textarea {
    flex: 1;
    border: none;
    background: transparent;
    resize: none;
    font-size: 13.5px;
    font-family: var(--font);
    line-height: 1.5;
    max-height: 120px;
    outline: none;
    color: var(--gray-800);
}
.chat-input-wrap textarea::placeholder { color: var(--gray-400); }
.chat-input-btns { display: flex; gap: 4px; align-items: center; }
.chat-input-btns button {
    width: 32px; height: 32px;
    border: none;
    background: transparent;
    color: var(--gray-400);
    cursor: pointer;
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    transition: var(--transition);
}
.chat-input-btns button:hover { color: var(--gray-700); background: var(--gray-100); }
.chat-input-btns .btn-send {
    background: var(--primary);
    color: #fff;
    border-radius: 50%;
    width: 34px; height: 34px;
}
.chat-input-btns .btn-send:hover { background: var(--primary-dark); color: #fff; }
.chat-input-btns .btn-send:disabled { opacity: 0.4; cursor: not-allowed; }

/* Emoji picker dropdown */
.emoji-picker-dropdown {
    position: absolute;
    bottom: 100%;
    right: 0;
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    padding: 12px;
    display: none;
    z-index: 100;
    width: 280px;
}
.emoji-picker-dropdown.show { display: block; }
.emoji-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
}
.emoji-grid button {
    width: 32px; height: 32px;
    border: none;
    background: transparent;
    font-size: 18px;
    cursor: pointer;
    border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
}
.emoji-grid button:hover { background: var(--gray-100); }

/* Empty state */
.chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--gray-400);
    gap: 12px;
}
.chat-empty i { font-size: 48px; }
.chat-empty h3 { font-size: 18px; font-weight: 600; color: var(--gray-600); }
.chat-empty p { font-size: 13px; }

/* Panel (info/members) */
.chat-panel {
    width: 0;
    overflow: hidden;
    border-left: 1px solid var(--gray-200);
    background: #fff;
    transition: width 0.3s ease;
    display: flex;
    flex-direction: column;
}
.chat-panel.open { width: 300px; min-width: 300px; }
.chat-panel-header {
    padding: 16px;
    border-bottom: 1px solid var(--gray-200);
    display: flex; align-items: center; gap: 8px;
}
.chat-panel-header h3 { font-size: 14px; font-weight: 700; flex: 1; color: var(--gray-800); }
.chat-panel-header .btn-close-panel {
    width: 30px; height: 30px;
    border: none; background: transparent;
    cursor: pointer; border-radius: var(--radius);
    color: var(--gray-500); font-size: 14px;
    display: flex; align-items: center; justify-content: center;
}
.chat-panel-header .btn-close-panel:hover { background: var(--gray-100); }
.chat-panel-body { flex: 1; overflow-y: auto; padding: 16px; }
.chat-member-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px; border-radius: var(--radius);
    transition: var(--transition);
}
.chat-member-item:hover { background: var(--gray-50); }
.chat-member-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 11px;
    color: #fff;
    position: relative;
}
.chat-member-avatar .presence-dot-sm {
    position: absolute; bottom: -1px; right: -1px;
    width: 10px; height: 10px;
    border-radius: 50%; border: 2px solid #fff;
}
.chat-member-info { flex: 1; min-width: 0; }
.chat-member-name { font-size: 13px; font-weight: 600; color: var(--gray-800); }
.chat-member-role { font-size: 11px; color: var(--gray-400); }

/* Responsive */
@media (max-width: 768px) {
    .chat-sidebar { width: 100%; min-width: 100%; }
    .chat-main { display: none; }
    .chat-container.conv-open .chat-sidebar { display: none; }
    .chat-container.conv-open .chat-main { display: flex; }
    .chat-panel.open { width: 100%; min-width: 100%; position: absolute; z-index: 10; }
}
</style>

<div class="chat-container" id="chatContainer">
    <!-- Sidebar -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h2><i class="fas fa-comments" style="color:var(--primary);margin-right:6px"></i> Chat</h2>
            <button class="btn-icon" onclick="chatNovaConversa()" title="Nova conversa"><i class="fas fa-edit"></i></button>
            <button class="btn-icon" onclick="chatNovoGrupo()" title="Novo grupo"><i class="fas fa-users-cog"></i></button>
        </div>
        <div class="chat-search">
            <i class="fas fa-search"></i>
            <input type="text" id="chatSearchInput" placeholder="Buscar conversas..." oninput="chatFiltrar(this.value)">
        </div>
        <div class="chat-list" id="chatList">
            <div style="text-align:center;padding:40px;color:var(--gray-400)">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-main" id="chatMain">
        <div class="chat-empty" id="chatEmpty">
            <i class="fas fa-comments"></i>
            <h3>Chat Interno</h3>
            <p>Selecione uma conversa ou inicie uma nova</p>
        </div>

        <div id="chatActive" style="display:none;flex:1;display:none;flex-direction:column;height:100%;">
            <!-- Header -->
            <div class="chat-main-header">
                <button class="btn-icon" onclick="chatVoltarLista()" style="display:none" id="chatBtnVoltar">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-item-avatar" id="chatHeaderAvatar" style="width:38px;height:38px;font-size:13px;"></div>
                <div class="chat-main-header-info">
                    <h3 id="chatHeaderNome"></h3>
                    <div class="subtitle" id="chatHeaderSub"></div>
                </div>
                <div class="chat-header-actions">
                    <button class="btn-icon" onclick="chatToggleMudo()" title="Silenciar" id="btnMudo">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="btn-icon" onclick="chatTogglePanel()" title="Membros">
                        <i class="fas fa-users"></i>
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chatMessages"></div>

            <!-- Typing indicator -->
            <div class="chat-typing" id="chatTyping"></div>

            <!-- Input -->
            <div class="chat-input-area">
                <div class="chat-reply-bar" id="chatReplyBar">
                    <i class="fas fa-reply" style="color:var(--primary)"></i>
                    <span>Respondendo a <strong id="replyAuthor"></strong></span>
                    <button class="close-reply" onclick="chatCancelReply()"><i class="fas fa-times"></i></button>
                </div>
                <div class="chat-input-wrap">
                    <textarea id="chatInput" rows="1" placeholder="Digite uma mensagem..."
                        onkeydown="chatInputKeydown(event)" oninput="chatAutoResize(this); chatEmitDigitando();"></textarea>
                    <div class="chat-input-btns" style="position:relative;">
                        <input type="file" id="chatFileInput" style="display:none" onchange="chatUploadFile(this)">
                        <button onclick="document.getElementById('chatFileInput').click()" title="Anexar arquivo">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <button onclick="chatToggleEmoji()" title="Emoji" id="btnEmoji">
                            <i class="fas fa-smile"></i>
                        </button>
                        <div class="emoji-picker-dropdown" id="emojiPicker">
                            <div class="emoji-grid" id="emojiGrid"></div>
                        </div>
                        <button onclick="iaInsight('chat_ia', {canal_id: window._chatCurrentCanal || ''}, {input:true, inputLabel:'Pergunte à IA sobre esta conversa:', inputPlaceholder:'Ex: qual o procedimento para resetar VPN?', inputKey:'pergunta'})" title="Perguntar à IA" style="color:#8B5CF6">
                            <i class="fas fa-robot"></i>
                        </button>
                        <button class="btn-send" onclick="chatEnviar()" id="btnSend" disabled title="Enviar (Enter)">
                            <i class="fas fa-paper-plane" style="font-size:13px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="chat-panel" id="chatPanel">
        <div class="chat-panel-header">
            <h3 id="panelTitle">Detalhes</h3>
            <button class="btn-close-panel" onclick="chatTogglePanel()"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-panel-body" id="panelBody"></div>
    </div>
</div>

<script>
(function() {
    const CHAT_API = '<?= BASE_URL ?>/api/chat.php';
    const MEU_ID = <?= $userId ?>;
    const MEU_NOME = '<?= addslashes($user['nome']) ?>';

    let conversas = [];
    let conversaAtual = null;
    let ultimaMsgId = 0;
    let replyTo = null;
    let pollInterval = null;
    let digitandoTimeout = null;
    let panelAberto = false;

    const EMOJIS = ['😀','😂','😍','🥰','😎','🤔','😢','😡','👍','👎','❤️','🔥','🎉','👏','💪','🙏','✅','❌','⭐','💡','📌','🚀','⚡','🎯','👀','💬','📎','🔔'];

    // ========================================
    // INIT
    // ========================================
    document.addEventListener('DOMContentLoaded', () => {
        chatCarregarConversas();
        chatBuildEmojiPicker();
        chatStartHeartbeat();

        // Auto-resize textarea
        const ta = document.getElementById('chatInput');
        ta.addEventListener('input', () => {
            document.getElementById('btnSend').disabled = ta.value.trim().length === 0;
        });
    });

    // ========================================
    // CONVERSAS
    // ========================================
    async function chatCarregarConversas() {
        try {
            const r = await fetch(`${CHAT_API}?action=conversas`);
            const data = await r.json();
            if (!data.success) throw new Error(data.error);
            conversas = data.data;
            chatRenderLista();
        } catch(e) {
            document.getElementById('chatList').innerHTML = `<div style="padding:20px;text-align:center;color:var(--danger)">${e.message}</div>`;
        }
    }

    function chatRenderLista(filtro = '') {
        const el = document.getElementById('chatList');
        let filtered = conversas;
        if (filtro) {
            const f = filtro.toLowerCase();
            filtered = conversas.filter(c => (c.nome || '').toLowerCase().includes(f));
        }

        const canais = filtered.filter(c => c.tipo === 'canal');
        const grupos = filtered.filter(c => c.tipo === 'grupo');
        const diretas = filtered.filter(c => c.tipo === 'direta');

        let html = '';

        if (canais.length) {
            html += '<div class="chat-section-title">Canais</div>';
            canais.forEach(c => html += chatItemHTML(c));
        }
        if (grupos.length) {
            html += '<div class="chat-section-title">Grupos</div>';
            grupos.forEach(c => html += chatItemHTML(c));
        }
        if (diretas.length) {
            html += '<div class="chat-section-title">Mensagens Diretas</div>';
            diretas.forEach(c => html += chatItemHTML(c));
        }

        if (!html) {
            html = '<div style="padding:40px;text-align:center;color:var(--gray-400)"><i class="fas fa-inbox" style="font-size:32px;margin-bottom:8px;display:block"></i>Nenhuma conversa</div>';
        }

        el.innerHTML = html;
    }

    function chatItemHTML(c) {
        const isActive = conversaAtual && conversaAtual.id === c.id;
        const isMuted = c.notificacao_mudo == 1;
        const avatarBg = c.cor || getCor(c.nome || 'C');
        const initials = c.tipo === 'canal' ? '#' : c.tipo === 'grupo' ? (c.nome || 'G').substring(0, 2).toUpperCase() : getInitials(c.nome || 'U');
        const icon = c.tipo === 'canal' ? (c.icone || 'fa-hashtag') : null;

        let preview = c.ultima_msg || '';
        if (preview.length > 50) preview = preview.substring(0, 50) + '...';
        if (c.ultima_msg_autor && c.tipo !== 'direta') {
            preview = c.ultima_msg_autor.split(' ')[0] + ': ' + preview;
        }

        let presenceDot = '';
        if (c.tipo === 'direta' && c.outro_usuario) {
            const st = c.outro_usuario.presenca_status || 'offline';
            presenceDot = `<div class="presence-dot presence-${st}"></div>`;
        }

        const timeStr = c.ultima_msg_data ? chatTimeAgo(c.ultima_msg_data) : '';

        return `
        <div class="chat-item ${isActive ? 'active' : ''} ${isMuted ? 'chat-item-muted' : ''}" onclick="window._chatAbrir(${c.id})">
            <div class="chat-item-avatar" style="background:${esc(avatarBg)}">
                ${icon ? `<i class="fas ${esc(icon)}" style="font-size:16px"></i>` : esc(initials)}
                ${presenceDot}
            </div>
            <div class="chat-item-info">
                <div class="chat-item-name">
                    ${esc(c.nome || 'Conversa')}
                    ${isMuted ? '<i class="fas fa-bell-slash" style="font-size:10px;color:var(--gray-400)"></i>' : ''}
                    <span class="time">${timeStr}</span>
                </div>
                <div class="chat-item-preview">${esc(preview)}</div>
            </div>
            ${c.nao_lidas > 0 && !isMuted ? `<div class="chat-item-badge">${c.nao_lidas}</div>` : ''}
        </div>`;
    }

    window._chatAbrir = async function(conversaId) {
        const c = conversas.find(x => x.id === conversaId);
        if (!c) return;
        conversaAtual = c;
        window._chatCurrentCanal = c.id;

        document.getElementById('chatEmpty').style.display = 'none';
        const active = document.getElementById('chatActive');
        active.style.display = 'flex';

        // Header
        const avatarBg = c.cor || getCor(c.nome || 'C');
        const initials = c.tipo === 'canal' ? '#' : c.tipo === 'grupo' ? (c.nome || 'G').substring(0, 2).toUpperCase() : getInitials(c.nome || 'U');
        const icon = c.tipo === 'canal' ? (c.icone || 'fa-hashtag') : null;
        document.getElementById('chatHeaderAvatar').style.background = avatarBg;
        document.getElementById('chatHeaderAvatar').innerHTML = icon ? `<i class="fas ${icon}"></i>` : esc(initials);
        document.getElementById('chatHeaderNome').textContent = c.nome || 'Conversa';

        let subText = '';
        if (c.tipo === 'direta' && c.outro_usuario) {
            const st = c.outro_usuario.presenca_status || 'offline';
            const stLabels = { online: '🟢 Online', ausente: '🟡 Ausente', ocupado: '🔴 Ocupado', offline: '⚫ Offline' };
            subText = (c.outro_usuario.departamento_sigla || '') + ' · ' + (stLabels[st] || 'Offline');
        } else {
            subText = c.descricao || (c.tipo === 'canal' ? 'Canal' : 'Grupo');
        }
        document.getElementById('chatHeaderSub').textContent = subText;

        // Mute icon
        document.getElementById('btnMudo').innerHTML = c.notificacao_mudo == 1 ? '<i class="fas fa-bell-slash"></i>' : '<i class="fas fa-bell"></i>';

        // Load messages
        await chatCarregarMensagens(conversaId);

        // Responsive
        document.getElementById('chatContainer').classList.add('conv-open');

        // Mark as read + update badge in sidebar
        c.nao_lidas = 0;
        chatRenderLista(document.getElementById('chatSearchInput').value);

        // Start polling
        chatStartPolling(conversaId);
    };

    async function chatCarregarMensagens(conversaId) {
        const el = document.getElementById('chatMessages');
        el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--gray-400)"><i class="fas fa-spinner fa-spin"></i></div>';
        try {
            const r = await fetch(`${CHAT_API}?action=mensagens&conversa_id=${conversaId}`);
            const data = await r.json();
            if (!data.success) throw new Error(data.error);
            chatRenderMensagens(data.data);
        } catch(e) {
            el.innerHTML = `<div style="padding:20px;text-align:center;color:var(--danger)">${e.message}</div>`;
        }
    }

    function chatRenderMensagens(msgs) {
        const el = document.getElementById('chatMessages');
        if (!msgs.length) {
            el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--gray-400)"><i class="fas fa-comment-dots" style="font-size:32px;display:block;margin-bottom:8px"></i>Nenhuma mensagem ainda.<br>Diga oi! 👋</div>';
            ultimaMsgId = 0;
            return;
        }

        let html = '';
        let lastDate = '';
        let lastUserId = null;

        msgs.forEach(m => {
            const msgDate = m.criado_em.substring(0, 10);
            if (msgDate !== lastDate) {
                lastDate = msgDate;
                html += `<div class="chat-date-sep">${formatarData(msgDate)}</div>`;
                lastUserId = null;
            }

            if (m.tipo === 'sistema') {
                html += `<div class="chat-msg-sistema"><i class="fas fa-info-circle"></i> <strong>${esc(m.autor_nome)}</strong> ${esc(m.conteudo)}</div>`;
                lastUserId = null;
            } else {
                const sameUser = m.usuario_id === lastUserId;
                html += chatMsgHTML(m, sameUser);
                lastUserId = m.usuario_id;
            }
        });

        el.innerHTML = html;
        ultimaMsgId = msgs.length ? msgs[msgs.length - 1].id : 0;
        el.scrollTop = el.scrollHeight;
    }

    function chatMsgHTML(m, compact = false) {
        const isMine = m.usuario_id == MEU_ID;
        const bg = m.autor_dept_cor || getCor(m.autor_nome || 'U');
        const ini = getInitials(m.autor_nome || 'U');
        const time = m.criado_em.substring(11, 16);

        let contentHtml = '';
        if (m.deletado) {
            contentHtml = '<em style="color:var(--gray-400)"><i class="fas fa-ban"></i> Mensagem apagada</em>';
        } else if (m.tipo === 'imagem') {
            contentHtml = `<img src="${esc(m.conteudo)}" onclick="window.open(this.src)" alt="Imagem" loading="lazy">`;
        } else if (m.tipo === 'arquivo') {
            contentHtml = `<a href="#" class="file-attachment"><i class="fas fa-file"></i> ${esc(m.conteudo)}</a>`;
        } else {
            contentHtml = chatFormatText(m.conteudo);
        }

        // Reply preview
        let replyHtml = '';
        if (m.resposta_a && m.resposta_conteudo) {
            replyHtml = `<div class="chat-msg-reply-preview"><strong>${esc(m.resposta_autor_nome || '?')}</strong> ${esc(truncate(m.resposta_conteudo, 60))}</div>`;
        }

        // Reactions
        let reacoesHtml = '';
        if (m.reacoes && m.reacoes.length) {
            const grouped = {};
            m.reacoes.forEach(r => {
                if (!grouped[r.emoji]) grouped[r.emoji] = [];
                grouped[r.emoji].push(r);
            });
            reacoesHtml = '<div class="chat-msg-reacoes">';
            for (const [emoji, users] of Object.entries(grouped)) {
                const isMinha = users.some(u => u.usuario_id == MEU_ID);
                const names = users.map(u => u.usuario_nome).join(', ');
                reacoesHtml += `<div class="chat-msg-reacao ${isMinha ? 'minha' : ''}" onclick="window._chatReagir(${m.id},'${emoji}')" title="${esc(names)}"><span>${emoji}</span><small>${users.length}</small></div>`;
            }
            reacoesHtml += '</div>';
        }

        // Hover actions
        let actions = `<div class="msg-actions">
            <button onclick="window._chatReply(${m.id},'${esc(m.autor_nome)}','${esc(truncate(m.conteudo,40))}')" title="Responder"><i class="fas fa-reply"></i></button>
            <button onclick="window._chatEmojiReacao(${m.id})" title="Reagir"><i class="fas fa-smile"></i></button>`;
        if (isMine && !m.deletado) {
            actions += `<button onclick="window._chatEditar(${m.id},'${escJs(m.conteudo)}')" title="Editar"><i class="fas fa-pen"></i></button>`;
            actions += `<button onclick="window._chatDeletar(${m.id})" title="Apagar"><i class="fas fa-trash"></i></button>`;
        }
        actions += '</div>';

        if (compact) {
            return `<div class="chat-msg" id="msg-${m.id}" data-id="${m.id}">
                <div style="width:36px;flex-shrink:0;text-align:center;padding-top:4px;">
                    <span class="chat-msg-time" style="font-size:10px;visibility:hidden;">${time}</span>
                </div>
                <div class="chat-msg-body">
                    ${replyHtml}
                    <div class="chat-msg-content">${contentHtml}</div>
                    ${m.editado ? '<span class="chat-msg-edited">(editado)</span>' : ''}
                    ${reacoesHtml}
                </div>
                ${m.deletado ? '' : actions}
            </div>`;
        }

        let deptBadge = '';
        if (m.autor_dept_sigla) {
            deptBadge = `<span class="chat-msg-dept" style="background:${m.autor_dept_cor || '#6366F1'}18;color:${m.autor_dept_cor || '#6366F1'}">${esc(m.autor_dept_sigla)}</span>`;
        }

        return `<div class="chat-msg" id="msg-${m.id}" data-id="${m.id}">
            <div class="chat-msg-avatar" style="background:${bg}">${esc(ini)}</div>
            <div class="chat-msg-body">
                <div class="chat-msg-header">
                    <span class="chat-msg-author">${esc(m.autor_nome)}</span>
                    ${deptBadge}
                    <span class="chat-msg-time">${time}</span>
                </div>
                ${replyHtml}
                <div class="chat-msg-content">${contentHtml}</div>
                ${m.editado ? '<span class="chat-msg-edited">(editado)</span>' : ''}
                ${reacoesHtml}
            </div>
            ${m.deletado ? '' : actions}
        </div>`;
    }

    // ========================================
    // ENVIAR
    // ========================================
    window.chatEnviar = async function() {
        if (!conversaAtual) return;
        const ta = document.getElementById('chatInput');
        const conteudo = ta.value.trim();
        if (!conteudo) return;

        ta.value = '';
        ta.style.height = 'auto';
        document.getElementById('btnSend').disabled = true;
        chatCancelReply();

        try {
            await fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'enviar',
                    conversa_id: conversaAtual.id,
                    conteudo,
                    resposta_a: replyTo
                })
            });
            replyTo = null;
            chatPoll(); // Immediate fetch
        } catch(e) { console.error(e); }
    };

    window.chatInputKeydown = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatEnviar();
        }
    };

    window.chatAutoResize = function(ta) {
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
    };

    // ========================================
    // POLLING
    // ========================================
    function chatStartPolling(conversaId) {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => chatPoll(), 2000);
    }

    async function chatPoll() {
        if (!conversaAtual) return;
        try {
            const r = await fetch(`${CHAT_API}?action=novas&conversa_id=${conversaAtual.id}&depois_de=${ultimaMsgId}`);
            const data = await r.json();
            if (!data.success) return;

            const novas = data.data.mensagens;
            if (novas.length) {
                const el = document.getElementById('chatMessages');
                const wasAtBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 80;

                novas.forEach(m => {
                    if (m.tipo === 'sistema') {
                        el.innerHTML += `<div class="chat-msg-sistema"><i class="fas fa-info-circle"></i> <strong>${esc(m.autor_nome)}</strong> ${esc(m.conteudo)}</div>`;
                    } else {
                        el.innerHTML += chatMsgHTML(m, false);
                    }
                    ultimaMsgId = Math.max(ultimaMsgId, m.id);
                });

                if (wasAtBottom) el.scrollTop = el.scrollHeight;
            }

            // Typing
            const typing = data.data.digitando;
            const typingEl = document.getElementById('chatTyping');
            if (typing.length) {
                typingEl.innerHTML = `<i class="fas fa-ellipsis-h fa-beat-fade"></i> ${typing.join(', ')} ${typing.length === 1 ? 'está' : 'estão'} digitando...`;
            } else {
                typingEl.innerHTML = '';
            }
        } catch(e) {}

        // Also refresh sidebar counts
        chatRefreshBadges();
    }

    async function chatRefreshBadges() {
        try {
            const r = await fetch(`${CHAT_API}?action=conversas`);
            const data = await r.json();
            if (!data.success) return;
            conversas = data.data;
            chatRenderLista(document.getElementById('chatSearchInput').value);
        } catch(e) {}
    }

    // ========================================
    // HEARTBEAT
    // ========================================
    function chatStartHeartbeat() {
        fetch(`${CHAT_API}?action=heartbeat`).catch(() => {});
        setInterval(() => {
            fetch(`${CHAT_API}?action=heartbeat`).catch(() => {});
        }, 30000);
    }

    // ========================================
    // DIGITANDO
    // ========================================
    window.chatEmitDigitando = function() {
        if (!conversaAtual) return;
        if (digitandoTimeout) clearTimeout(digitandoTimeout);
        fetch(CHAT_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'digitando', conversa_id: conversaAtual.id })
        }).catch(() => {});
        digitandoTimeout = setTimeout(() => {
            fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'digitando', conversa_id: null })
            }).catch(() => {});
        }, 3000);
    };

    // ========================================
    // REPLY / EDIT / DELETE / REACT
    // ========================================
    window._chatReply = function(id, author, preview) {
        replyTo = id;
        document.getElementById('replyAuthor').textContent = author;
        document.getElementById('chatReplyBar').classList.add('show');
        document.getElementById('chatInput').focus();
    };

    window.chatCancelReply = function() {
        replyTo = null;
        document.getElementById('chatReplyBar').classList.remove('show');
    };

    window._chatEditar = function(id, conteudo) {
        const novo = prompt('Editar mensagem:', conteudo);
        if (novo === null || novo.trim() === '' || novo === conteudo) return;
        fetch(CHAT_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'editar', mensagem_id: id, conteudo: novo.trim() })
        }).then(() => chatCarregarMensagens(conversaAtual.id));
    };

    window._chatDeletar = function(id) {
        if (!confirm('Apagar esta mensagem?')) return;
        fetch(CHAT_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'deletar', mensagem_id: id })
        }).then(() => chatCarregarMensagens(conversaAtual.id));
    };

    window._chatReagir = function(msgId, emoji) {
        fetch(CHAT_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reagir', mensagem_id: msgId, emoji })
        }).then(() => chatCarregarMensagens(conversaAtual.id));
    };

    window._chatEmojiReacao = function(msgId) {
        const emoji = prompt('Emoji para reagir (ex: 👍 ❤️ 😂 🔥):', '👍');
        if (!emoji) return;
        window._chatReagir(msgId, emoji.trim());
    };

    // ========================================
    // UPLOAD
    // ========================================
    window.chatUploadFile = async function(input) {
        if (!input.files.length || !conversaAtual) return;
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('conversa_id', conversaAtual.id);
        fd.append('arquivo', input.files[0]);
        try {
            const r = await fetch(CHAT_API, { method: 'POST', body: fd });
            const data = await r.json();
            if (!data.success) throw new Error(data.error);
            chatPoll();
        } catch(e) { alert('Erro no upload: ' + e.message); }
        input.value = '';
    };

    // ========================================
    // EMOJI PICKER
    // ========================================
    function chatBuildEmojiPicker() {
        const grid = document.getElementById('emojiGrid');
        grid.innerHTML = EMOJIS.map(e => `<button onclick="chatInsertEmoji('${e}')">${e}</button>`).join('');
    }

    window.chatToggleEmoji = function() {
        document.getElementById('emojiPicker').classList.toggle('show');
    };

    window.chatInsertEmoji = function(emoji) {
        const ta = document.getElementById('chatInput');
        ta.value += emoji;
        ta.focus();
        document.getElementById('emojiPicker').classList.remove('show');
        document.getElementById('btnSend').disabled = false;
    };

    document.addEventListener('click', function(e) {
        const picker = document.getElementById('emojiPicker');
        const btn = document.getElementById('btnEmoji');
        if (picker && !picker.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            picker.classList.remove('show');
        }
    });

    // ========================================
    // NOVA CONVERSA / GRUPO
    // ========================================
    window.chatNovaConversa = async function() {
        try {
            const r = await fetch(`${CHAT_API}?action=usuarios`);
            const data = await r.json();
            if (!data.success) throw new Error(data.error);

            let html = '<div style="max-height:400px;overflow-y:auto">';
            data.data.forEach(u => {
                const bg = u.departamento_cor || getCor(u.nome);
                const ini = getInitials(u.nome);
                const st = u.presenca_status || 'offline';
                html += `<div class="chat-member-item" style="cursor:pointer" onclick="window._chatIniciarDireta(${u.id})">
                    <div class="chat-member-avatar" style="background:${bg}">${esc(ini)}
                        <div class="presence-dot-sm presence-${st}"></div>
                    </div>
                    <div class="chat-member-info">
                        <div class="chat-member-name">${esc(u.nome)}</div>
                        <div class="chat-member-role">${esc(u.departamento_sigla || u.tipo)}</div>
                    </div>
                </div>`;
            });
            html += '</div>';

            HelpDesk.showModal('Nova Conversa', html);
        } catch(e) { HelpDesk.toast(e.message, 'error'); }
    };

    window._chatIniciarDireta = async function(outroId) {
        HelpDesk.closeModal();
        try {
            const r = await fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'conversa_direta', usuario_id: outroId })
            });
            const data = await r.json();
            if (!data.success) throw new Error(data.error);
            await chatCarregarConversas();
            window._chatAbrir(data.data.conversa_id);
        } catch(e) { HelpDesk.toast(e.message, 'error'); }
    };

    window.chatNovoGrupo = async function() {
        try {
            const r = await fetch(`${CHAT_API}?action=usuarios`);
            const data = await r.json();
            if (!data.success) throw new Error(data.error);

            let html = `<div class="form-group"><label class="form-label">Nome do Grupo *</label>
                <input type="text" id="grupoNome" class="form-control" placeholder="Ex: Equipe de Projetos"></div>
                <div class="form-group"><label class="form-label">Descrição</label>
                <input type="text" id="grupoDesc" class="form-control" placeholder="Opcional"></div>
                <div class="form-group"><label class="form-label">Membros</label>
                <div style="max-height:250px;overflow-y:auto;border:1px solid var(--gray-200);border-radius:var(--radius);padding:8px">`;
            data.data.forEach(u => {
                html += `<label style="display:flex;align-items:center;gap:8px;padding:6px 8px;cursor:pointer;border-radius:var(--radius);" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                    <input type="checkbox" value="${u.id}" class="grupo-membro-cb">
                    <span style="font-size:13px">${esc(u.nome)}</span>
                    <span style="font-size:11px;color:var(--gray-400);margin-left:auto">${esc(u.departamento_sigla || '')}</span>
                </label>`;
            });
            html += '</div></div>';
            html += '<button class="btn btn-primary" style="width:100%;margin-top:12px" onclick="window._chatCriarGrupo()"><i class="fas fa-users"></i> Criar Grupo</button>';

            HelpDesk.showModal('Novo Grupo', html);
        } catch(e) { HelpDesk.toast(e.message, 'error'); }
    };

    window._chatCriarGrupo = async function() {
        const nome = document.getElementById('grupoNome').value.trim();
        if (!nome) return alert('Nome obrigatório');
        const desc = document.getElementById('grupoDesc').value.trim();
        const membros = Array.from(document.querySelectorAll('.grupo-membro-cb:checked')).map(cb => parseInt(cb.value));

        HelpDesk.closeModal();
        try {
            const r = await fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'criar_grupo', nome, descricao: desc, membros })
            });
            const data = await r.json();
            if (!data.success) throw new Error(data.error);
            await chatCarregarConversas();
            window._chatAbrir(data.data.conversa_id);
            HelpDesk.toast('Grupo criado!', 'success');
        } catch(e) { HelpDesk.toast(e.message, 'error'); }
    };

    // ========================================
    // PANEL (Membros/Info)
    // ========================================
    window.chatTogglePanel = async function() {
        const panel = document.getElementById('chatPanel');
        panelAberto = !panelAberto;
        panel.classList.toggle('open', panelAberto);
        if (panelAberto && conversaAtual) {
            chatLoadPanel();
        }
    };

    async function chatLoadPanel() {
        const body = document.getElementById('panelBody');
        body.innerHTML = '<div style="text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i></div>';

        try {
            const r = await fetch(`${CHAT_API}?action=participantes&conversa_id=${conversaAtual.id}`);
            const data = await r.json();
            if (!data.success) throw new Error(data.error);

            let html = `<div style="margin-bottom:16px">
                <div style="font-size:12px;color:var(--gray-400);font-weight:600;margin-bottom:8px">MEMBROS (${data.data.length})</div>`;

            data.data.forEach(p => {
                const bg = p.departamento_cor || getCor(p.nome);
                const ini = getInitials(p.nome);
                const st = p.presenca_status || 'offline';
                html += `<div class="chat-member-item">
                    <div class="chat-member-avatar" style="background:${bg}">${esc(ini)}
                        <div class="presence-dot-sm presence-${st}"></div>
                    </div>
                    <div class="chat-member-info">
                        <div class="chat-member-name">${esc(p.nome)} ${p.id == MEU_ID ? '(você)' : ''}</div>
                        <div class="chat-member-role">${esc(p.departamento_sigla || '')} · ${esc(p.papel)}</div>
                    </div>
                </div>`;
            });

            html += '</div>';

            // Actions
            if (conversaAtual.tipo !== 'direta') {
                html += `<div style="border-top:1px solid var(--gray-200);padding-top:12px;display:flex;flex-direction:column;gap:8px">`;
                html += `<button class="btn btn-sm" onclick="chatNovaConversa()" style="justify-content:flex-start"><i class="fas fa-user-plus"></i> Adicionar membro</button>`;
                html += `<button class="btn btn-sm" style="justify-content:flex-start;color:var(--danger)" onclick="window._chatSair()"><i class="fas fa-sign-out-alt"></i> Sair do grupo</button>`;
                html += '</div>';
            }

            body.innerHTML = html;
        } catch(e) {
            body.innerHTML = `<p style="color:var(--danger);padding:20px">${e.message}</p>`;
        }
    }

    window._chatSair = async function() {
        if (!confirm('Sair deste grupo?')) return;
        try {
            await fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'sair', conversa_id: conversaAtual.id })
            });
            conversaAtual = null;
            document.getElementById('chatActive').style.display = 'none';
            document.getElementById('chatEmpty').style.display = '';
            chatTogglePanel();
            chatCarregarConversas();
        } catch(e) { HelpDesk.toast(e.message, 'error'); }
    };

    // ========================================
    // MUTE / FILTRO / RESPONSIVOS
    // ========================================
    window.chatToggleMudo = async function() {
        if (!conversaAtual) return;
        try {
            const r = await fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_mudo', conversa_id: conversaAtual.id })
            });
            const data = await r.json();
            if (data.success) {
                conversaAtual.notificacao_mudo = data.data.mudo;
                document.getElementById('btnMudo').innerHTML = data.data.mudo ? '<i class="fas fa-bell-slash"></i>' : '<i class="fas fa-bell"></i>';
                HelpDesk.toast(data.data.mudo ? 'Conversa silenciada' : 'Notificações ativadas', 'success');
                chatCarregarConversas();
            }
        } catch(e) {}
    };

    window.chatFiltrar = function(v) {
        chatRenderLista(v);
    };

    window.chatVoltarLista = function() {
        document.getElementById('chatContainer').classList.remove('conv-open');
    };

    // ========================================
    // HELPERS
    // ========================================
    function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function escJs(s) { return (s || '').replace(/'/g, "\\'").replace(/\n/g, '\\n'); }
    function truncate(s, n) { return (s || '').length > n ? s.substring(0, n) + '...' : (s || ''); }
    function getInitials(name) { return (name || 'U').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase(); }
    function getCor(name) {
        const colors = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#EC4899','#06B6D4','#6366F1'];
        let h = 0; for (let i = 0; i < (name||'').length; i++) h = (name.charCodeAt(i) + ((h << 5) - h)); 
        return colors[Math.abs(h) % colors.length];
    }
    function formatarData(d) {
        const hoje = new Date().toISOString().substring(0,10);
        const ontem = new Date(Date.now()-86400000).toISOString().substring(0,10);
        if (d === hoje) return 'Hoje';
        if (d === ontem) return 'Ontem';
        const [y,m,dd] = d.split('-');
        return `${dd}/${m}/${y}`;
    }
    function chatTimeAgo(dt) {
        if (!dt) return '';
        const d = new Date(dt), now = new Date(), diff = Math.floor((now-d)/1000);
        if (diff < 60) return 'agora';
        if (diff < 3600) return Math.floor(diff/60) + 'min';
        if (diff < 86400) return Math.floor(diff/3600) + 'h';
        if (diff < 172800) return 'ontem';
        return d.toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit'});
    }
    function chatFormatText(text) {
        let t = esc(text);
        // Bold **text**
        t = t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Italic *text*
        t = t.replace(/\*(.+?)\*/g, '<em>$1</em>');
        // Code `text`
        t = t.replace(/`(.+?)`/g, '<code style="background:var(--gray-100);padding:1px 4px;border-radius:3px;font-size:12px">$1</code>');
        // URLs
        t = t.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
        // Newlines
        t = t.replace(/\n/g, '<br>');
        return t;
    }

})();
</script>
