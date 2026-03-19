<?php
/**
 * View: Notificações
 * Página completa de notificações com filtros e preferências
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-bell" style="margin-right:8px"></i> Notificações</h1>
        <p class="page-subtitle">Central de notificações do sistema</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-sm" onclick="ntfMarcarTodasLidas()">
            <i class="fas fa-check-double"></i> Marcar todas como lidas
        </button>
        <button class="btn btn-sm" onclick="ntfLimparAntigas()">
            <i class="fas fa-trash"></i> Limpar antigas
        </button>
    </div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="ntf-todas" onclick="ntfSwitchTab('ntf-todas')">
        <i class="fas fa-bell"></i> Todas
    </button>
    <button class="ad-tab" data-tab="ntf-nao-lidas" onclick="ntfSwitchTab('ntf-nao-lidas')">
        <i class="fas fa-circle" style="font-size:8px"></i> Não Lidas <span class="ntf-badge" id="ntfBadgeTab">0</span>
    </button>
    <button class="ad-tab" data-tab="ntf-preferencias" onclick="ntfSwitchTab('ntf-preferencias')">
        <i class="fas fa-cog"></i> Preferências
    </button>
</div>

<!-- Tab: Todas -->
<div class="ad-tab-content active" id="tab-ntf-todas">
    <div class="ntf-list" id="ntfListTodas">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Tab: Não Lidas -->
<div class="ad-tab-content" id="tab-ntf-nao-lidas">
    <div class="ntf-list" id="ntfListNaoLidas">
        <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<!-- Tab: Preferências -->
<div class="ad-tab-content" id="tab-ntf-preferencias">
    <div class="ntf-prefs-card">
        <h3><i class="fas fa-sliders-h"></i> Preferências de Notificação</h3>
        <p style="color:var(--gray-500);margin-bottom:20px">Escolha quais notificações deseja receber</p>
        <div class="ntf-prefs-grid" id="ntfPrefsGrid">
            <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <button class="btn btn-primary" onclick="ntfSalvarPrefs()" style="margin-top:20px">
            <i class="fas fa-save"></i> Salvar Preferências
        </button>
    </div>
</div>

<?php if (currentUser()['tipo'] === 'admin'): ?>
<!-- Admin: Enviar notificação -->
<div style="margin-top:24px">
    <div class="gh-card">
        <div class="gh-card-header">
            <h3><i class="fas fa-bullhorn"></i> Enviar Notificação (Admin)</h3>
        </div>
        <div class="gh-card-body">
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" id="ntfEnviarTitulo" class="form-control" placeholder="Título da notificação">
            </div>
            <div class="form-group">
                <label class="form-label">Mensagem</label>
                <textarea id="ntfEnviarMsg" class="form-control" rows="3" placeholder="Mensagem (opcional)"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Destino</label>
                <select id="ntfEnviarDestino" class="form-control">
                    <option value="equipe">Equipe (Admin + Técnicos)</option>
                    <option value="todos">Todos os usuários</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="ntfEnviar()">
                <i class="fas fa-paper-plane"></i> Enviar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const NTF_API = '<?= BASE_URL ?>/api/notificacoes.php';

document.addEventListener('DOMContentLoaded', () => {
    ntfCarregar();
    ntfCarregarNaoLidas();
    ntfCarregarPrefs();
});

function ntfSwitchTab(tabId) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');
}

async function ntfCarregar() {
    const el = document.getElementById('ntfListTodas');
    try {
        const r = await fetch(`${NTF_API}?action=listar&limite=50`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        document.getElementById('ntfBadgeTab').textContent = data.data.nao_lidas;
        ntfRenderList(el, data.data.notificacoes);
    } catch(e) {
        el.innerHTML = `<p style="color:var(--danger);padding:20px">${e.message}</p>`;
    }
}

async function ntfCarregarNaoLidas() {
    const el = document.getElementById('ntfListNaoLidas');
    try {
        const r = await fetch(`${NTF_API}?action=listar&limite=50&nao_lidas=1`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ntfRenderList(el, data.data.notificacoes);
    } catch(e) {
        el.innerHTML = `<p style="color:var(--danger);padding:20px">${e.message}</p>`;
    }
}

function ntfRenderList(el, list) {
    if (!list.length) {
        el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-bell-slash"></i><p>Nenhuma notificação</p></div>';
        return;
    }
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
    el.innerHTML = list.map(n => {
        const icon = icons[n.tipo] || n.icone || 'fa-bell';
        const color = colors[n.tipo] || 'var(--gray-500)';
        return `
            <div class="ntf-item ${n.lida ? '' : 'unread'}">
                <div class="ntf-item-icon" style="color:${color};background:${color}15">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="ntf-item-body">
                    <span class="ntf-item-title">${ntfEscape(n.titulo)}</span>
                    ${n.mensagem ? `<span class="ntf-item-msg">${ntfEscape(n.mensagem)}</span>` : ''}
                    <span class="ntf-item-time"><i class="fas fa-clock"></i> ${ntfTimeAgo(n.criado_em)}</span>
                </div>
                <div class="ntf-item-actions">
                    ${!n.lida ? `<button class="btn-icon" title="Marcar como lida" onclick="ntfMarcarLida(${n.id})"><i class="fas fa-check"></i></button>` : ''}
                    ${n.link ? `<a href="<?= BASE_URL ?>${n.link}" class="btn-icon" title="Abrir"><i class="fas fa-external-link-alt"></i></a>` : ''}
                    <button class="btn-icon text-danger" title="Excluir" onclick="ntfExcluir(${n.id})"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `;
    }).join('');
}

async function ntfMarcarLida(id) {
    await fetch(NTF_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({action:'marcar_lida', id}) });
    ntfCarregar(); ntfCarregarNaoLidas();
}

async function ntfMarcarTodasLidas() {
    await fetch(NTF_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({action:'marcar_todas_lidas'}) });
    ntfCarregar(); ntfCarregarNaoLidas();
}

async function ntfExcluir(id) {
    await fetch(NTF_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({action:'excluir', id}) });
    ntfCarregar(); ntfCarregarNaoLidas();
}

async function ntfLimparAntigas() {
    if (!confirm('Remover notificações lidas com mais de 30 dias?')) return;
    await fetch(NTF_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({action:'limpar_antigas', dias: 30}) });
    ntfCarregar();
}

// Preferências
async function ntfCarregarPrefs() {
    try {
        const r = await fetch(`${NTF_API}?action=preferencias`);
        const data = await r.json();
        if (!data.success) return;
        const p = data.data;
        const prefs = [
            { key: 'chamado_novo', label: 'Novo chamado criado', icon: 'fa-ticket-alt' },
            { key: 'chamado_atribuido', label: 'Chamado atribuído a mim', icon: 'fa-user-check' },
            { key: 'chamado_atualizado', label: 'Chamado atualizado', icon: 'fa-sync' },
            { key: 'chamado_fechado', label: 'Chamado fechado', icon: 'fa-check-circle' },
            { key: 'tarefa_atribuida', label: 'Tarefa atribuída a mim', icon: 'fa-tasks' },
            { key: 'tarefa_concluida', label: 'Tarefa concluída', icon: 'fa-check' },
            { key: 'projeto_atualizado', label: 'Projeto atualizado', icon: 'fa-project-diagram' },
            { key: 'compra_aprovada', label: 'Compra aprovada/rejeitada', icon: 'fa-shopping-cart' },
            { key: 'sistema', label: 'Notificações do sistema', icon: 'fa-cog' },
            { key: 'som_ativo', label: 'Som de notificação', icon: 'fa-volume-up' },
        ];
        document.getElementById('ntfPrefsGrid').innerHTML = prefs.map(pref => `
            <label class="ntf-pref-item">
                <div class="ntf-pref-info">
                    <i class="fas ${pref.icon}"></i>
                    <span>${pref.label}</span>
                </div>
                <input type="checkbox" class="ntf-pref-check" data-key="${pref.key}" ${p[pref.key] ? 'checked' : ''}>
            </label>
        `).join('');
    } catch(e) {}
}

async function ntfSalvarPrefs() {
    const dados = { action: 'salvar_preferencias' };
    document.querySelectorAll('.ntf-pref-check').forEach(cb => {
        dados[cb.dataset.key] = cb.checked ? 1 : 0;
    });
    try {
        const r = await fetch(NTF_API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(dados) });
        const data = await r.json();
        alert(data.message || 'Salvo!');
    } catch(e) { alert('Erro: ' + e.message); }
}

// Admin: Enviar
async function ntfEnviar() {
    const titulo = document.getElementById('ntfEnviarTitulo').value.trim();
    if (!titulo) return alert('Informe o título');
    try {
        const r = await fetch(NTF_API, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                action: 'enviar_sistema',
                titulo,
                mensagem: document.getElementById('ntfEnviarMsg').value,
                destino: document.getElementById('ntfEnviarDestino').value
            })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        alert('Notificação enviada!');
        document.getElementById('ntfEnviarTitulo').value = '';
        document.getElementById('ntfEnviarMsg').value = '';
    } catch(e) { alert('Erro: ' + e.message); }
}

function ntfEscape(s) { if(!s) return ''; const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function ntfTimeAgo(dt) {
    if(!dt) return '';
    const d=new Date(dt), now=new Date(), diff=Math.floor((now-d)/1000);
    if(diff<60) return 'agora';
    if(diff<3600) return Math.floor(diff/60)+'min atrás';
    if(diff<86400) return Math.floor(diff/3600)+'h atrás';
    if(diff<2592000) return Math.floor(diff/86400)+' dias atrás';
    return d.toLocaleDateString('pt-BR');
}
</script>
