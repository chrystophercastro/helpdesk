    </div><!-- /.page-content -->
</main><!-- /.main-content -->
</div><!-- /.app-container -->

<!-- ── Sidebar Toggle & Section Collapse ── -->
<script>
(function(){
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    // Toggle sidebar collapse (desktop)
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
        });
        // Restore state
        if (localStorage.getItem('sidebar_collapsed') === '1') {
            sidebar.classList.add('collapsed');
        }
    }

    // Section collapse/expand with persistence
    window.toggleSection = function(btn) {
        const section = btn.closest('.nav-section');
        section.classList.toggle('open');
        // Save state
        const states = JSON.parse(localStorage.getItem('sidebar_sections') || '{}');
        const key = section.getAttribute('data-section');
        states[key] = section.classList.contains('open');
        localStorage.setItem('sidebar_sections', JSON.stringify(states));
    };

    // Restore section states
    (function restoreSections() {
        const states = JSON.parse(localStorage.getItem('sidebar_sections') || '{}');
        document.querySelectorAll('.nav-section').forEach(section => {
            const key = section.getAttribute('data-section');
            if (key && states.hasOwnProperty(key)) {
                if (states[key]) section.classList.add('open');
                else section.classList.remove('open');
            }
        });
    })();

    // Mobile sidebar
    window.openMobileSidebar = function() {
        sidebar.classList.add('mobile-open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    window.closeMobileSidebar = function() {
        sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    };

    // Close mobile sidebar on nav-link click
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) closeMobileSidebar();
        });
    });

    // User dropdown
    const userBtn = document.getElementById('userMenuBtn');
    const userDrop = document.getElementById('userDropdown');
    if (userBtn && userDrop) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDrop.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
            if (!userBtn.contains(e.target) && !userDrop.contains(e.target)) {
                userDrop.classList.remove('active');
            }
        });
    }

    // Keyboard shortcut: Ctrl+K or Cmd+K to focus search
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) searchInput.focus();
        }
    });
})();
</script>

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

    // Chat unread badge polling
    function chatPollBadge() {
        fetch('<?= BASE_URL ?>/api/chat.php?action=nao_lidas')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('badge-chat');
                    if (badge) {
                        const c = data.data.total;
                        if (c > 0) { badge.textContent = c > 99 ? '99+' : c; badge.style.display = ''; }
                        else { badge.style.display = 'none'; }
                    }
                }
            }).catch(() => {});
    }
    chatPollBadge();
    setInterval(chatPollBadge, 15000);
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

<!-- IA Insights Component -->
<script src="<?= BASE_URL ?>/assets/js/ia-insights.js"></script>

<!-- CSRF Token -->
<input type="hidden" id="csrfToken" value="<?= gerarCSRFToken() ?>">
<input type="hidden" id="baseUrl" value="<?= BASE_URL ?>">

<?php if (isset($extraJS)): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= $extraJS ?>"></script>
<?php endif; ?>

</body>
</html>
