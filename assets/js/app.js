/**
 * Oracle X - Main JavaScript Application
 */
const HelpDesk = {
    baseUrl: '',
    csrfToken: '',

    /**
     * Initialize the application
     */
    init() {
        this.baseUrl = document.getElementById('baseUrl')?.value || '';
        this.csrfToken = document.getElementById('csrfToken')?.value || '';

        this.initSidebar();
        this.initUserMenu();
        this.initMobileMenu();
        this.initGlobalSearch();
        this.initAutoCloseAlerts();
    },

    /**
     * AJAX API helper
     * @param {string} method - HTTP method
     * @param {string} url - API endpoint URL
     * @param {Object|FormData} data - Data to send
     * @param {boolean} isFormData - Whether data is FormData
     * @returns {Promise}
     */
    async api(method, url, data = null, isFormData = false) {
        try {
            const fullUrl = url.startsWith('http') ? url : this.baseUrl + url;
            const options = {
                method: method.toUpperCase(),
                headers: {},
                credentials: 'same-origin'
            };

            if (method.toUpperCase() === 'GET') {
                // Append data as query params for GET
                if (data) {
                    const params = new URLSearchParams(data);
                    const separator = fullUrl.includes('?') ? '&' : '?';
                    options.url = fullUrl + separator + params.toString();
                } else {
                    options.url = fullUrl;
                }
            } else {
                options.url = fullUrl;
                if (isFormData) {
                    // Don't set Content-Type for FormData, browser handles it
                    if (data instanceof FormData) {
                        data.append('_csrf', this.csrfToken);
                    }
                    options.body = data;
                } else {
                    options.headers['Content-Type'] = 'application/json';
                    if (data) {
                        data._csrf = this.csrfToken;
                        options.body = JSON.stringify(data);
                    }
                }
            }

            const response = await fetch(options.url || fullUrl, options);
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            console.error('API Error:', error);
            this.toast('Erro de conexão com o servidor', 'danger');
            return { error: 'Erro de conexão' };
        }
    },

    /**
     * Open a named modal (calls the modal function defined in pages)
     * @param {string} name - Modal name
     */
    openModal(name) {
        if (this.modals && typeof this.modals[name] === 'function') {
            this.modals[name]();
        } else {
            console.warn('Modal not found:', name);
        }
    },

    /**
     * Show modal with custom content
     * @param {string} title - Modal title
     * @param {string} body - Modal body HTML
     * @param {string} footer - Modal footer HTML
     * @param {string} sizeClass - Optional size class (modal-lg, modal-xl)
     */
    showModal(title, body, footer = '', sizeClass = '') {
        const overlay = document.getElementById('modalOverlay');
        const container = document.getElementById('modalContainer');
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');

        if (!overlay || !container) return;

        titleEl.innerHTML = title;
        bodyEl.innerHTML = body;
        footerEl.innerHTML = footer;

        // Reset and apply size class
        container.className = 'modal';
        if (sizeClass) container.classList.add(sizeClass);

        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Focus first input
        setTimeout(() => {
            const firstInput = bodyEl.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) firstInput.focus();
        }, 100);
    },

    /**
     * Close the modal
     */
    closeModal() {
        const overlay = document.getElementById('modalOverlay');
        if (overlay) {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    /**
     * Show a toast notification
     * @param {string} message - Message text
     * @param {string} type - Type: success, danger, warning, info
     * @param {number} duration - Auto-dismiss duration in ms
     */
    toast(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas ${icons[type] || icons.info}"></i>
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(toast);

        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    },

    /**
     * Initialize sidebar toggle
     */
    initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        if (!sidebar || !toggle) return;

        // Load saved state
        const collapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        if (collapsed) sidebar.classList.add('collapsed');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
        });
    },

    /**
     * Initialize mobile menu
     */
    initMobileMenu() {
        const btn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        if (!btn || !sidebar) return;

        btn.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('mobile-open') && 
                !sidebar.contains(e.target) && 
                !btn.contains(e.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });
    },

    /**
     * Initialize user dropdown menu
     */
    initUserMenu() {
        const btn = document.getElementById('userMenuBtn');
        const dropdown = document.getElementById('userDropdown');
        if (!btn || !dropdown) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    },

    /**
     * Initialize global search
     */
    initGlobalSearch() {
        const input = document.getElementById('globalSearch');
        const results = document.getElementById('searchResults');
        if (!input || !results) return;

        let timer = null;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const query = input.value.trim();
            if (query.length < 2) {
                results.classList.remove('active');
                results.innerHTML = '';
                return;
            }

            timer = setTimeout(async () => {
                // Simple search across pages
                const items = this.searchLocalPages(query);
                if (items.length > 0) {
                    results.innerHTML = items.map(item => `
                        <a href="${item.url}" class="search-result-item">
                            <i class="fas ${item.icon}" style="color:${item.color}"></i>
                            <div>
                                <div style="font-weight:600;font-size:0.88rem">${item.title}</div>
                                <div style="font-size:0.78rem;color:#64748b">${item.subtitle}</div>
                            </div>
                        </a>
                    `).join('');
                    results.classList.add('active');
                } else {
                    results.innerHTML = '<div class="search-result-item"><span style="color:#94a3b8">Nenhum resultado encontrado</span></div>';
                    results.classList.add('active');
                }
            }, 300);
        });

        input.addEventListener('blur', () => {
            setTimeout(() => results.classList.remove('active'), 200);
        });

        input.addEventListener('focus', () => {
            if (results.innerHTML && input.value.trim().length >= 2) {
                results.classList.add('active');
            }
        });
    },

    /**
     * Search local navigation pages
     */
    searchLocalPages(query) {
        const q = query.toLowerCase();
        const pages = [
            { title: 'Dashboard', subtitle: 'Painel principal', icon: 'fa-tachometer-alt', color: '#3B82F6', url: this.baseUrl + '/index.php?page=dashboard' },
            { title: 'Chamados', subtitle: 'Tickets de suporte', icon: 'fa-ticket-alt', color: '#10B981', url: this.baseUrl + '/index.php?page=chamados' },
            { title: 'Projetos', subtitle: 'Gestão de projetos', icon: 'fa-project-diagram', color: '#8B5CF6', url: this.baseUrl + '/index.php?page=projetos' },
            { title: 'Kanban', subtitle: 'Quadro de tarefas', icon: 'fa-columns', color: '#F59E0B', url: this.baseUrl + '/index.php?page=kanban' },
            { title: 'Sprints', subtitle: 'Sprints ágeis', icon: 'fa-running', color: '#EC4899', url: this.baseUrl + '/index.php?page=sprints' },
            { title: 'Compras', subtitle: 'Requisições de compra', icon: 'fa-shopping-cart', color: '#06B6D4', url: this.baseUrl + '/index.php?page=compras' },
            { title: 'Inventário', subtitle: 'Ativos de TI', icon: 'fa-boxes', color: '#F97316', url: this.baseUrl + '/index.php?page=inventario' },
            { title: 'Usuários', subtitle: 'Gestão de usuários', icon: 'fa-users-cog', color: '#EF4444', url: this.baseUrl + '/index.php?page=usuarios' },
            { title: 'Base de Conhecimento', subtitle: 'Artigos e soluções', icon: 'fa-book', color: '#14B8A6', url: this.baseUrl + '/index.php?page=base-conhecimento' },
            { title: 'Relatórios', subtitle: 'Análises e métricas', icon: 'fa-chart-bar', color: '#6366F1', url: this.baseUrl + '/index.php?page=relatorios' },
            { title: 'Configurações', subtitle: 'Ajustes do sistema', icon: 'fa-cog', color: '#64748B', url: this.baseUrl + '/index.php?page=configuracoes' },
        ];

        return pages.filter(p => 
            p.title.toLowerCase().includes(q) || 
            p.subtitle.toLowerCase().includes(q)
        ).slice(0, 6);
    },

    /**
     * Auto close flash alerts
     */
    initAutoCloseAlerts() {
        const alert = document.getElementById('flashAlert');
        if (alert) {
            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.3s ease forwards';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    },

    /**
     * Modal registry - pages register their modal functions here
     */
    modals: {},

    /**
     * Confirm dialog wrapper
     */
    confirm(message) {
        return window.confirm(message);
    },

    /**
     * Format currency BRL
     */
    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    },

    /**
     * Format date
     */
    formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('pt-BR');
    }
};

// ---- Initialize when DOM is ready ----
document.addEventListener('DOMContentLoaded', () => {
    HelpDesk.init();
});

// ---- Close modal on overlay click ----
document.addEventListener('click', (e) => {
    if (e.target.id === 'modalOverlay') {
        HelpDesk.closeModal();
    }
});

// ---- Close modal on Escape key ----
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        HelpDesk.closeModal();
    }
});

// ---- Modal close button ----
document.addEventListener('DOMContentLoaded', () => {
    const closeBtn = document.getElementById('modalClose');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => HelpDesk.closeModal());
    }
});
