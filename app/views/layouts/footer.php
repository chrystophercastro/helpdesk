    </div><!-- /.page-content -->
</main><!-- /.main-content -->
</div><!-- /.app-container -->

<!-- Notification System Script -->
<script>
(function() {
    const NOTIF_API = '<?= BASE_URL ?>/api/notificacoes.php';
    const NOTIF_BASE = '<?= BASE_URL ?>';
    let notifOpen = false;

    // Poll for notification count every 30s
    function notifPollCount() {
        fetch(`${NOTIF_API}?action=count`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('notifBadge');
                    const count = data.data.count;
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(() => {});
    }

    // Toggle dropdown
    window.notifToggle = function() {
        const dd = document.getElementById('notifDropdown');
        notifOpen = !notifOpen;
        dd.style.display = notifOpen ? 'block' : 'none';
        if (notifOpen) notifCarregar();
    };

    // Load notifications
    window.notifCarregar = function() {
        const body = document.getElementById('notifDropdownBody');
        body.innerHTML = '<div class="notif-loading"><i class="fas fa-spinner fa-spin"></i></div>';
        fetch(`${NOTIF_API}?action=listar&limite=15`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error);
                const list = data.data.notificacoes;
                if (!list.length) {
                    body.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>Nenhuma notificação</p></div>';
                    return;
                }
                body.innerHTML = list.map(n => {
                    const icons = {
                        'chamado': 'fa-ticket-alt', 'tarefa': 'fa-tasks', 'success': 'fa-check-circle',
                        'warning': 'fa-exclamation-triangle', 'danger': 'fa-exclamation-circle',
                        'sistema': 'fa-cog', 'info': 'fa-info-circle'
                    };
                    const colors = {
                        'chamado': 'var(--primary)', 'tarefa': 'var(--purple)', 'success': 'var(--success)',
                        'warning': 'var(--warning)', 'danger': 'var(--danger)',
                        'sistema': 'var(--gray-500)', 'info': 'var(--info)'
                    };
                    const icon = icons[n.tipo] || n.icone || 'fa-bell';
                    const color = colors[n.tipo] || 'var(--gray-500)';
                    return `
                        <div class="notif-item ${n.lida ? '' : 'unread'}" onclick="notifClick(${n.id}, '${(n.link||'').replace(/'/g,"\\'")}')">
                            <div class="notif-item-icon" style="color:${color}"><i class="fas ${icon}"></i></div>
                            <div class="notif-item-content">
                                <span class="notif-item-title">${notifEscape(n.titulo)}</span>
                                ${n.mensagem ? `<span class="notif-item-msg">${notifEscape(n.mensagem)}</span>` : ''}
                                <span class="notif-item-time">${notifTimeAgo(n.criado_em)}</span>
                            </div>
                            ${!n.lida ? '<div class="notif-item-dot"></div>' : ''}
                        </div>
                    `;
                }).join('');
            })
            .catch(e => {
                body.innerHTML = `<div class="notif-empty"><p style="color:var(--danger)">${e.message}</p></div>`;
            });
    };

    // Click notification
    window.notifClick = function(id, link) {
        fetch(NOTIF_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'marcar_lida', id})
        }).then(() => notifPollCount());
        if (link) window.location.href = NOTIF_BASE + link;
        else { notifCarregar(); }
    };

    // Mark all read
    window.notifMarcarTodasLidas = function() {
        fetch(NOTIF_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'marcar_todas_lidas'})
        }).then(() => { notifPollCount(); notifCarregar(); });
    };

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('notifWrapper');
        if (wrapper && !wrapper.contains(e.target) && notifOpen) {
            notifOpen = false;
            document.getElementById('notifDropdown').style.display = 'none';
        }
    });

    // Helpers
    function notifEscape(s) {
        if (!s) return '';
        const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }
    function notifTimeAgo(dt) {
        if (!dt) return '';
        const d = new Date(dt), now = new Date(), diff = Math.floor((now-d)/1000);
        if (diff < 60) return 'agora';
        if (diff < 3600) return Math.floor(diff/60) + 'min';
        if (diff < 86400) return Math.floor(diff/3600) + 'h';
        if (diff < 2592000) return Math.floor(diff/86400) + 'd';
        return d.toLocaleDateString('pt-BR');
    }

    // Init
    notifPollCount();
    setInterval(notifPollCount, 30000);
})();
</script>

<!-- Modal Container -->
<div class="modal-overlay" id="modalOverlay" style="display:none">
    <div class="modal" id="modalContainer">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle"></h3>
            <button class="modal-close" id="modalClose">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-footer" id="modalFooter"></div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Floating AI Button (global) -->
<?php if (isset($_GET['page']) && $_GET['page'] !== 'ia'): ?>
<button class="ia-floating-btn" onclick="toggleFloatingAI()" title="Assistente IA" id="iaFloatingBtn">
    <i class="fas fa-robot"></i>
</button>
<div class="ia-floating-panel" id="iaFloatingPanel">
    <div class="ia-floating-header">
        <i class="fas fa-robot"></i>
        <span>Assistente IA — Pergunta Rápida</span>
        <button class="ia-floating-close" onclick="toggleFloatingAI()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="ia-floating-messages" id="iaFloatingMessages">
        <div class="ia-message ia-assistant" style="max-width:100%">
            <div class="ia-avatar ia-avatar-ai"><i class="fas fa-robot"></i></div>
            <div class="ia-bubble" style="font-size:13px">
                <div class="ia-content">Olá! Faça uma pergunta rápida sobre TI, rede, servidores ou chamados.</div>
            </div>
        </div>
    </div>
    <div class="ia-floating-input-wrap">
        <input type="text" id="iaFloatingInput" placeholder="Pergunte algo..."
               onkeydown="if(event.key==='Enter')enviarPerguntaRapida()">
        <button onclick="enviarPerguntaRapida()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function toggleFloatingAI() {
    const panel = document.getElementById('iaFloatingPanel');
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
        document.getElementById('iaFloatingInput').focus();
    }
}

function floatingAiRender(text) {
    if (typeof marked !== 'undefined') {
        try { return marked.parse(text); } catch(e) {}
    }
    return escapeHtmlFloat(text).replace(/\n/g, '<br>');
}

function enviarPerguntaRapida() {
    const input = document.getElementById('iaFloatingInput');
    const msg = input.value.trim();
    if (!msg) return;
    input.value = '';

    const messagesDiv = document.getElementById('iaFloatingMessages');

    // User message
    messagesDiv.innerHTML += `
        <div class="ia-message ia-user" style="max-width:100%;align-self:flex-end">
            <div class="ia-bubble" style="font-size:13px"><div class="ia-content">${escapeHtmlFloat(msg)}</div></div>
        </div>`;
    messagesDiv.innerHTML += `
        <div class="ia-message ia-assistant" style="max-width:100%" id="floatLoading">
            <div class="ia-avatar ia-avatar-ai"><i class="fas fa-robot"></i></div>
            <div class="ia-bubble"><div class="ia-typing-indicator"><span></span><span></span><span></span></div></div>
        </div>`;
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    const baseUrl = document.getElementById('baseUrl').value;
    const token = document.getElementById('csrfToken').value;

    fetch(baseUrl + '/api/ia.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'pergunta_rapida', pergunta: msg, csrf_token: token })
    })
    .then(r => r.json())
    .then(resp => {
        const loading = document.getElementById('floatLoading');
        if (loading) loading.remove();

        const content = resp.success ? resp.data.content : ('Erro: ' + (resp.error || 'IA offline'));
        messagesDiv.innerHTML += `
            <div class="ia-message ia-assistant" style="max-width:100%">
                <div class="ia-avatar ia-avatar-ai"><i class="fas fa-robot"></i></div>
                <div class="ia-bubble" style="font-size:13px"><div class="ia-content">${floatingAiRender(content)}</div></div>
            </div>`;
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    })
    .catch(() => {
        const loading = document.getElementById('floatLoading');
        if (loading) loading.remove();
        messagesDiv.innerHTML += `
            <div class="ia-message ia-assistant" style="max-width:100%">
                <div class="ia-avatar ia-avatar-ai"><i class="fas fa-robot"></i></div>
                <div class="ia-bubble" style="font-size:13px"><div class="ia-content">❌ Erro ao conectar com a IA</div></div>
            </div>`;
    });
}

function escapeHtmlFloat(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>
<?php endif; ?>

<!-- CSRF Token -->
<input type="hidden" id="csrfToken" value="<?= gerarCSRFToken() ?>">
<input type="hidden" id="baseUrl" value="<?= BASE_URL ?>">

<?php if (isset($extraJS)): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= $extraJS ?>"></script>
<?php endif; ?>

</body>
</html>
