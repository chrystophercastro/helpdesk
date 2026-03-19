<?php
/**
 * View: GitHub
 * Integração GitHub — Repositórios, Commits, Pull Requests, Issues, CI/CD
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fab fa-github" style="margin-right:8px"></i> GitHub</h1>
        <p class="page-subtitle">Integração com repositórios, commits, PRs, issues e CI/CD</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-sm" onclick="ghSync()" id="ghBtnSync" style="display:none" title="Sincronizar todos">
            <i class="fas fa-sync-alt"></i> Sincronizar
        </button>
        <button class="btn btn-primary btn-sm" onclick="ghAbrirModalVincular()" id="ghBtnVincular" style="display:none">
            <i class="fas fa-plus"></i> Vincular Repositório
        </button>
    </div>
</div>

<!-- Estado: Não configurado -->
<div id="ghSetup" style="display:none">
    <div class="gh-setup-card">
        <div class="gh-setup-icon">
            <i class="fab fa-github"></i>
        </div>
        <h2>Conecte sua conta GitHub</h2>
        <p>Para acessar seus repositórios, commits, pull requests e issues, configure seu Personal Access Token (PAT).</p>
        <div class="gh-setup-steps">
            <div class="gh-step">
                <span class="gh-step-num">1</span>
                <div>
                    <strong>Gerar Token</strong>
                    <p>Acesse <a href="https://github.com/settings/tokens" target="_blank">github.com/settings/tokens</a> e crie um token com permissões de <code>repo</code>, <code>read:org</code></p>
                </div>
            </div>
            <div class="gh-step">
                <span class="gh-step-num">2</span>
                <div>
                    <strong>Colar Token</strong>
                    <p>Cole seu token abaixo. Ele será criptografado (AES-256) e armazenado com segurança.</p>
                </div>
            </div>
            <div class="gh-step">
                <span class="gh-step-num">3</span>
                <div>
                    <strong>Pronto!</strong>
                    <p>Vincule repositórios aos seus projetos e acompanhe tudo em um só lugar.</p>
                </div>
            </div>
        </div>
        <div class="gh-setup-form">
            <div class="form-group">
                <label class="form-label">Personal Access Token</label>
                <input type="password" id="ghSetupToken" class="form-control" placeholder="ghp_xxxxxxxxxxxxxxxxxxxx">
            </div>
            <button class="btn btn-primary" onclick="ghSalvarConfig()" id="ghBtnSalvarSetup">
                <i class="fas fa-link"></i> Conectar ao GitHub
            </button>
        </div>
    </div>
</div>

<!-- Estado: Conectado -->
<div id="ghContent" style="display:none">
    <!-- Tabs -->
    <div class="ad-tabs">
        <button class="ad-tab active" data-tab="gh-overview" onclick="ghSwitchTab('gh-overview')">
            <i class="fas fa-chart-pie"></i> Overview
        </button>
        <button class="ad-tab" data-tab="gh-repos" onclick="ghSwitchTab('gh-repos')">
            <i class="fas fa-book"></i> Repositórios
        </button>
        <button class="ad-tab" data-tab="gh-commits" onclick="ghSwitchTab('gh-commits')">
            <i class="fas fa-code-branch"></i> Commits
        </button>
        <button class="ad-tab" data-tab="gh-prs" onclick="ghSwitchTab('gh-prs')">
            <i class="fas fa-code-merge"></i> Pull Requests
        </button>
        <button class="ad-tab" data-tab="gh-issues" onclick="ghSwitchTab('gh-issues')">
            <i class="fas fa-exclamation-circle"></i> Issues
        </button>
        <button class="ad-tab" data-tab="gh-cicd" onclick="ghSwitchTab('gh-cicd')">
            <i class="fas fa-cogs"></i> CI/CD
        </button>
        <button class="ad-tab" data-tab="gh-config" onclick="ghSwitchTab('gh-config')">
            <i class="fas fa-cog"></i> Configuração
        </button>
    </div>

    <!-- ============================= TAB: OVERVIEW ============================= -->
    <div class="ad-tab-content active" id="tab-gh-overview">
        <div class="gh-user-banner" id="ghUserBanner"></div>
        <div class="gh-stats-grid" id="ghStatsGrid"></div>
        <div class="gh-grid-2">
            <div class="gh-card">
                <div class="gh-card-header">
                    <h3><i class="fas fa-clock"></i> Commits Recentes</h3>
                </div>
                <div class="gh-card-body" id="ghRecentCommits">
                    <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>
            <div class="gh-card">
                <div class="gh-card-header">
                    <h3><i class="fas fa-book"></i> Repositórios Vinculados</h3>
                </div>
                <div class="gh-card-body" id="ghRecentRepos">
                    <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================= TAB: REPOSITÓRIOS ============================= -->
    <div class="ad-tab-content" id="tab-gh-repos">
        <div class="gh-toolbar">
            <div class="gh-toolbar-left">
                <input type="text" class="form-control" id="ghRepoSearch" placeholder="Filtrar repositórios..." oninput="ghFiltrarRepos()">
            </div>
            <div class="gh-toolbar-right">
                <select class="form-control" id="ghRepoFilter" onchange="ghCarregarReposVinculados()">
                    <option value="">Todos os projetos</option>
                </select>
            </div>
        </div>
        <div class="gh-repos-grid" id="ghReposGrid">
            <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <!-- ============================= TAB: COMMITS ============================= -->
    <div class="ad-tab-content" id="tab-gh-commits">
        <div class="gh-toolbar">
            <div class="gh-toolbar-left">
                <select class="form-control" id="ghCommitRepo" onchange="ghCarregarCommits()">
                    <option value="">Selecione um repositório</option>
                </select>
                <select class="form-control" id="ghCommitBranch" onchange="ghCarregarCommits()" style="display:none">
                    <option value="">Todas as branches</option>
                </select>
            </div>
        </div>
        <div class="gh-commits-timeline" id="ghCommitsTimeline">
            <div class="gh-empty-state">
                <i class="fas fa-code-branch"></i>
                <p>Selecione um repositório para ver os commits</p>
            </div>
        </div>
    </div>

    <!-- ============================= TAB: PULL REQUESTS ============================= -->
    <div class="ad-tab-content" id="tab-gh-prs">
        <div class="gh-toolbar">
            <div class="gh-toolbar-left">
                <select class="form-control" id="ghPrRepo" onchange="ghCarregarPRs()">
                    <option value="">Selecione um repositório</option>
                </select>
            </div>
            <div class="gh-toolbar-right">
                <div class="gh-toggle-group">
                    <button class="gh-toggle active" data-state="open" onclick="ghTogglePRState('open')">
                        <i class="fas fa-code-merge"></i> Abertos
                    </button>
                    <button class="gh-toggle" data-state="closed" onclick="ghTogglePRState('closed')">
                        <i class="fas fa-check"></i> Fechados
                    </button>
                    <button class="gh-toggle" data-state="all" onclick="ghTogglePRState('all')">
                        <i class="fas fa-list"></i> Todos
                    </button>
                </div>
            </div>
        </div>
        <div class="gh-list" id="ghPrList">
            <div class="gh-empty-state">
                <i class="fas fa-code-merge"></i>
                <p>Selecione um repositório para ver os Pull Requests</p>
            </div>
        </div>
    </div>

    <!-- ============================= TAB: ISSUES ============================= -->
    <div class="ad-tab-content" id="tab-gh-issues">
        <div class="gh-toolbar">
            <div class="gh-toolbar-left">
                <select class="form-control" id="ghIssueRepo" onchange="ghCarregarIssues()">
                    <option value="">Selecione um repositório</option>
                </select>
            </div>
            <div class="gh-toolbar-right">
                <div class="gh-toggle-group">
                    <button class="gh-toggle active" data-state="open" onclick="ghToggleIssueState('open')">
                        <i class="fas fa-exclamation-circle"></i> Abertas
                    </button>
                    <button class="gh-toggle" data-state="closed" onclick="ghToggleIssueState('closed')">
                        <i class="fas fa-check-circle"></i> Fechadas
                    </button>
                    <button class="gh-toggle" data-state="all" onclick="ghToggleIssueState('all')">
                        <i class="fas fa-list"></i> Todas
                    </button>
                </div>
                <button class="btn btn-sm btn-primary" onclick="ghAbrirModalNovaIssue()">
                    <i class="fas fa-plus"></i> Nova Issue
                </button>
            </div>
        </div>
        <div class="gh-list" id="ghIssueList">
            <div class="gh-empty-state">
                <i class="fas fa-exclamation-circle"></i>
                <p>Selecione um repositório para ver as Issues</p>
            </div>
        </div>
    </div>

    <!-- ============================= TAB: CI/CD ============================= -->
    <div class="ad-tab-content" id="tab-gh-cicd">
        <div class="gh-toolbar">
            <div class="gh-toolbar-left">
                <select class="form-control" id="ghCiRepo" onchange="ghCarregarWorkflows()">
                    <option value="">Selecione um repositório</option>
                </select>
            </div>
        </div>
        <div class="gh-list" id="ghCiList">
            <div class="gh-empty-state">
                <i class="fas fa-cogs"></i>
                <p>Selecione um repositório para ver os Workflows</p>
            </div>
        </div>
    </div>

    <!-- ============================= TAB: CONFIGURAÇÃO ============================= -->
    <div class="ad-tab-content" id="tab-gh-config">
        <div class="gh-config-grid">
            <div class="gh-card">
                <div class="gh-card-header">
                    <h3><i class="fas fa-user-circle"></i> Conta Conectada</h3>
                </div>
                <div class="gh-card-body" id="ghConfigUser">
                    <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>
            <div class="gh-card">
                <div class="gh-card-header">
                    <h3><i class="fas fa-key"></i> Token de Acesso</h3>
                </div>
                <div class="gh-card-body">
                    <p style="color:var(--gray-500);margin-bottom:12px">O token é armazenado criptografado (AES-256-CBC). Você pode atualizar ou remover a qualquer momento.</p>
                    <div class="form-group">
                        <label class="form-label">Novo Token (deixe vazio para manter o atual)</label>
                        <input type="password" id="ghConfigToken" class="form-control" placeholder="ghp_xxxxxxxxxxxxxxxxxxxx">
                    </div>
                    <div style="display:flex;gap:8px;margin-top:12px">
                        <button class="btn btn-primary btn-sm" onclick="ghAtualizarToken()">
                            <i class="fas fa-save"></i> Atualizar Token
                        </button>
                        <button class="btn btn-sm" style="background:var(--danger);color:#fff" onclick="ghRemoverConfig()">
                            <i class="fas fa-trash"></i> Desconectar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================= MODAL: VINCULAR REPO ============================= -->
<div class="modal-overlay" id="ghModalVincular" style="display:none">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h2><i class="fab fa-github"></i> Vincular Repositório</h2>
            <button class="modal-close" onclick="ghFecharModal('ghModalVincular')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Buscar repositório</label>
                <input type="text" id="ghSearchRepo" class="form-control" placeholder="Digite para buscar nos seus repositórios..." oninput="ghBuscarRepoDebounce()">
            </div>
            <div class="gh-search-results" id="ghSearchResults">
                <div class="gh-empty-state" style="padding:40px 0">
                    <i class="fab fa-github"></i>
                    <p>Digite para buscar seus repositórios</p>
                </div>
            </div>
            <div id="ghVincularForm" style="display:none">
                <hr style="margin:16px 0;border-color:var(--gray-200)">
                <div class="gh-selected-repo" id="ghSelectedRepo"></div>
                <div class="form-group" style="margin-top:12px">
                    <label class="form-label">Vincular ao Projeto (opcional)</label>
                    <select class="form-control" id="ghVincularProjeto">
                        <option value="">Nenhum projeto</option>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="ghVincularRepo()" style="margin-top:12px;width:100%">
                    <i class="fas fa-link"></i> Vincular Repositório
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================= MODAL: NOVA ISSUE ============================= -->
<div class="modal-overlay" id="ghModalIssue" style="display:none">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-circle"></i> Nova Issue</h2>
            <button class="modal-close" onclick="ghFecharModal('ghModalIssue')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" id="ghIssueTitle" class="form-control" placeholder="Título da issue">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea id="ghIssueBody" class="form-control" rows="6" placeholder="Descreva a issue (suporta Markdown)..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Labels (separadas por vírgula)</label>
                <input type="text" id="ghIssueLabels" class="form-control" placeholder="bug, enhancement, help wanted">
            </div>
            <button class="btn btn-primary" onclick="ghCriarIssue()" style="width:100%;margin-top:8px">
                <i class="fas fa-paper-plane"></i> Criar Issue
            </button>
        </div>
    </div>
</div>

<!-- ============================= MODAL: DETALHES PR ============================= -->
<div class="modal-overlay" id="ghModalPR" style="display:none">
    <div class="modal" style="max-width:800px;max-height:85vh;overflow-y:auto">
        <div class="modal-header">
            <h2 id="ghPRTitle"><i class="fas fa-code-merge"></i> Pull Request</h2>
            <button class="modal-close" onclick="ghFecharModal('ghModalPR')">&times;</button>
        </div>
        <div class="modal-body" id="ghPRBody">
            <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>
</div>

<!-- ============================= MODAL: DETALHES REPO ============================= -->
<div class="modal-overlay" id="ghModalRepoDetail" style="display:none">
    <div class="modal" style="max-width:900px;max-height:85vh;overflow-y:auto">
        <div class="modal-header">
            <h2 id="ghRepoDetailTitle"><i class="fab fa-github"></i> Repositório</h2>
            <button class="modal-close" onclick="ghFecharModal('ghModalRepoDetail')">&times;</button>
        </div>
        <div class="modal-body" id="ghRepoDetailBody">
            <div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>
</div>

<!-- ======================== JAVASCRIPT ======================== -->
<script>
const GH_API = '<?= BASE_URL ?>/api/github.php';
let ghUser = null;
let ghReposVinculados = [];
let ghProjetos = [];
let ghCurrentPRState = 'open';
let ghCurrentIssueState = 'open';
let ghSearchTimeout = null;
let ghSelectedRepoData = null;

// ========================================
// INICIALIZAÇÃO
// ========================================
document.addEventListener('DOMContentLoaded', () => ghInit());

async function ghInit() {
    try {
        const r = await fetch(`${GH_API}?action=status`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (data.data.connected) {
            ghUser = data.data.user;
            document.getElementById('ghSetup').style.display = 'none';
            document.getElementById('ghContent').style.display = 'block';
            document.getElementById('ghBtnSync').style.display = '';
            document.getElementById('ghBtnVincular').style.display = '';
            ghRenderOverview(data.data);
            ghCarregarProjetos();
            ghCarregarReposVinculados();
        } else {
            document.getElementById('ghSetup').style.display = 'block';
            document.getElementById('ghContent').style.display = 'none';
        }
    } catch (e) {
        ghToast('Erro ao verificar status: ' + e.message, 'danger');
        document.getElementById('ghSetup').style.display = 'block';
    }
}

// ========================================
// TABS
// ========================================
function ghSwitchTab(tabId) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');

    // Carregar dados da tab ao trocar
    if (tabId === 'gh-repos') ghCarregarReposVinculados();
    if (tabId === 'gh-config') ghRenderConfig();
}

// ========================================
// CONFIG / SETUP
// ========================================
async function ghSalvarConfig() {
    const token = document.getElementById('ghSetupToken').value.trim();
    if (!token) return ghToast('Informe o token', 'warning');

    const btn = document.getElementById('ghBtnSalvarSetup');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validando...';

    try {
        const r = await fetch(GH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'salvar_config', token })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ghToast(data.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        ghToast('Erro: ' + e.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-link"></i> Conectar ao GitHub';
    }
}

async function ghAtualizarToken() {
    const token = document.getElementById('ghConfigToken').value.trim();
    if (!token) return ghToast('Informe o novo token', 'warning');
    try {
        const r = await fetch(GH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'salvar_config', token })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ghToast('Token atualizado!', 'success');
        document.getElementById('ghConfigToken').value = '';
        ghInit();
    } catch (e) {
        ghToast('Erro: ' + e.message, 'danger');
    }
}

async function ghRemoverConfig() {
    if (!confirm('Desconectar do GitHub? Os repositórios vinculados serão mantidos mas não poderão ser sincronizados.')) return;
    try {
        const r = await fetch(GH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remover_config' })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ghToast('Desconectado do GitHub', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        ghToast('Erro: ' + e.message, 'danger');
    }
}

// ========================================
// OVERVIEW
// ========================================
function ghRenderOverview(data) {
    // User banner
    const u = data.user;
    document.getElementById('ghUserBanner').innerHTML = `
        <div class="gh-user-info">
            <img src="${u.avatar_url}" alt="${u.login}" class="gh-user-avatar">
            <div>
                <h2>${u.name || u.login}</h2>
                <p>@${u.login} ${u.bio ? '· ' + u.bio : ''}</p>
                <div class="gh-user-meta">
                    ${u.company ? `<span><i class="fas fa-building"></i> ${u.company}</span>` : ''}
                    ${u.location ? `<span><i class="fas fa-map-marker-alt"></i> ${u.location}</span>` : ''}
                    <span><i class="fas fa-book"></i> ${u.public_repos} repos</span>
                    <span><i class="fas fa-users"></i> ${u.followers} seguidores</span>
                </div>
            </div>
        </div>
    `;

    // Stats
    const s = data.stats;
    document.getElementById('ghStatsGrid').innerHTML = `
        <div class="gh-stat-card">
            <div class="gh-stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-book"></i></div>
            <div class="gh-stat-info">
                <span class="gh-stat-value">${s.repos_vinculados}</span>
                <span class="gh-stat-label">Repos Vinculados</span>
            </div>
        </div>
        <div class="gh-stat-card">
            <div class="gh-stat-icon" style="background:var(--success-bg);color:var(--success)"><i class="fas fa-code-branch"></i></div>
            <div class="gh-stat-info">
                <span class="gh-stat-value">${s.total_commits}</span>
                <span class="gh-stat-label">Commits Sync</span>
            </div>
        </div>
        <div class="gh-stat-card">
            <div class="gh-stat-icon" style="background:var(--warning-bg);color:var(--warning)"><i class="fas fa-star"></i></div>
            <div class="gh-stat-info">
                <span class="gh-stat-value">${s.total_stars}</span>
                <span class="gh-stat-label">Total Stars</span>
            </div>
        </div>
        <div class="gh-stat-card">
            <div class="gh-stat-icon" style="background:var(--purple-bg);color:var(--purple)"><i class="fas fa-code-merge"></i></div>
            <div class="gh-stat-info">
                <span class="gh-stat-value">${s.total_forks}</span>
                <span class="gh-stat-label">Total Forks</span>
            </div>
        </div>
    `;

    // Recent commits
    ghCarregarCommitsRecentes();
    ghCarregarReposOverview();
}

async function ghCarregarCommitsRecentes() {
    const el = document.getElementById('ghRecentCommits');
    try {
        const r = await fetch(`${GH_API}?action=repos_vinculados`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        let allCommits = [];
        for (const repo of data.data.slice(0, 5)) {
            try {
                const cr = await fetch(`${GH_API}?action=commits_local&repo_id=${repo.id}&limit=5`);
                const cd = await cr.json();
                if (cd.success && cd.data) {
                    cd.data.forEach(c => { c._repo_name = repo.repo; c._repo_owner = repo.owner; });
                    allCommits = allCommits.concat(cd.data);
                }
            } catch(e) {}
        }

        allCommits.sort((a, b) => new Date(b.data_commit) - new Date(a.data_commit));
        allCommits = allCommits.slice(0, 10);

        if (!allCommits.length) {
            el.innerHTML = '<div class="gh-empty-state" style="padding:20px"><i class="fas fa-code-branch"></i><p>Nenhum commit sincronizado</p></div>';
            return;
        }

        el.innerHTML = allCommits.map(c => `
            <div class="gh-commit-item-mini">
                <div class="gh-commit-avatar">
                    <img src="${c.autor_avatar || 'https://github.com/ghost.png'}" alt="">
                </div>
                <div class="gh-commit-info">
                    <span class="gh-commit-msg">${ghEscape(c.mensagem?.split('\n')[0] || '')}</span>
                    <span class="gh-commit-meta">${c.autor_nome || 'Unknown'} em <strong>${c._repo_name || ''}</strong> · ${ghTimeAgo(c.data_commit)}</span>
                </div>
                <a href="https://github.com/${c._repo_owner}/${c._repo_name}/commit/${c.sha}" target="_blank" class="gh-commit-sha">${(c.sha||'').substring(0,7)}</a>
            </div>
        `).join('');
    } catch (e) {
        el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`;
    }
}

async function ghCarregarReposOverview() {
    const el = document.getElementById('ghRecentRepos');
    try {
        const r = await fetch(`${GH_API}?action=repos_vinculados`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state" style="padding:20px"><i class="fas fa-book"></i><p>Nenhum repositório vinculado</p><button class="btn btn-sm btn-primary" onclick="ghAbrirModalVincular()" style="margin-top:8px"><i class="fas fa-plus"></i> Vincular</button></div>';
            return;
        }

        el.innerHTML = data.data.slice(0, 6).map(r => `
            <div class="gh-repo-mini" onclick="ghVerRepoDetail(${r.id})">
                <div class="gh-repo-mini-header">
                    <i class="fas fa-book" style="color:var(--primary)"></i>
                    <strong>${ghEscape(r.owner)}/${ghEscape(r.repo)}</strong>
                </div>
                <div class="gh-repo-mini-stats">
                    ${r.linguagem ? `<span class="gh-lang-dot" style="background:${ghLangColor(r.linguagem)}"></span><span>${r.linguagem}</span>` : ''}
                    <span><i class="fas fa-star"></i> ${r.stars || 0}</span>
                    <span><i class="fas fa-code-branch"></i> ${r.forks || 0}</span>
                </div>
            </div>
        `).join('');
    } catch (e) {
        el.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`;
    }
}

// ========================================
// REPOSITÓRIOS
// ========================================
async function ghCarregarProjetos() {
    try {
        const r = await fetch(`${GH_API}?action=projetos`);
        const data = await r.json();
        if (data.success) {
            ghProjetos = data.data;
            // Popular selects de projeto
            const opts = '<option value="">Nenhum projeto</option>' + ghProjetos.map(p => `<option value="${p.id}">${ghEscape(p.nome)}</option>`).join('');
            const filterOpts = '<option value="">Todos os projetos</option>' + ghProjetos.map(p => `<option value="${p.id}">${ghEscape(p.nome)}</option>`).join('');
            document.getElementById('ghVincularProjeto').innerHTML = opts;
            document.getElementById('ghRepoFilter').innerHTML = filterOpts;
        }
    } catch(e) {}
}

async function ghCarregarReposVinculados() {
    const projetoId = document.getElementById('ghRepoFilter')?.value || '';
    const grid = document.getElementById('ghReposGrid');
    grid.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>';

    try {
        let url = `${GH_API}?action=repos_vinculados`;
        if (projetoId) url += `&projeto_id=${projetoId}`;
        const r = await fetch(url);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        ghReposVinculados = data.data;
        ghPopularRepoSelects();

        if (!data.data.length) {
            grid.innerHTML = `
                <div class="gh-empty-state" style="grid-column:1/-1;padding:60px 0">
                    <i class="fab fa-github" style="font-size:48px;color:var(--gray-400)"></i>
                    <h3>Nenhum repositório vinculado</h3>
                    <p>Vincule repositórios do GitHub para acompanhar commits, PRs e issues</p>
                    <button class="btn btn-primary" onclick="ghAbrirModalVincular()" style="margin-top:12px">
                        <i class="fas fa-plus"></i> Vincular Repositório
                    </button>
                </div>
            `;
            return;
        }

        grid.innerHTML = data.data.map(r => `
            <div class="gh-repo-card" data-name="${ghEscape(r.owner+'/'+r.repo).toLowerCase()}">
                <div class="gh-repo-card-header">
                    <div class="gh-repo-name">
                        <i class="${r.privado ? 'fas fa-lock' : 'fas fa-book'}" style="color:${r.privado ? 'var(--warning)' : 'var(--primary)'}"></i>
                        <a href="https://github.com/${r.owner}/${r.repo}" target="_blank">${ghEscape(r.owner)}/<strong>${ghEscape(r.repo)}</strong></a>
                    </div>
                    <div class="gh-repo-actions">
                        <button class="btn-icon" title="Detalhes" onclick="ghVerRepoDetail(${r.id})"><i class="fas fa-eye"></i></button>
                        <button class="btn-icon" title="Sincronizar" onclick="ghSyncRepo(${r.id})"><i class="fas fa-sync-alt"></i></button>
                        <button class="btn-icon text-danger" title="Desvincular" onclick="ghDesvincular(${r.id})"><i class="fas fa-unlink"></i></button>
                    </div>
                </div>
                ${r.descricao ? `<p class="gh-repo-desc">${ghEscape(r.descricao)}</p>` : ''}
                <div class="gh-repo-meta">
                    ${r.linguagem ? `<span><span class="gh-lang-dot" style="background:${ghLangColor(r.linguagem)}"></span>${r.linguagem}</span>` : ''}
                    <span><i class="fas fa-star"></i> ${r.stars || 0}</span>
                    <span><i class="fas fa-code-branch"></i> ${r.forks || 0}</span>
                    <span><i class="fas fa-exclamation-circle"></i> ${r.issues_abertas || 0}</span>
                </div>
                ${r.projeto_nome ? `<div class="gh-repo-project"><i class="fas fa-project-diagram"></i> ${ghEscape(r.projeto_nome)}</div>` : ''}
                <div class="gh-repo-updated">Atualizado ${ghTimeAgo(r.ultimo_sync || r.criado_em)}</div>
            </div>
        `).join('');
    } catch (e) {
        grid.innerHTML = `<div class="gh-empty-state" style="grid-column:1/-1"><p style="color:var(--danger)">${e.message}</p></div>`;
    }
}

function ghFiltrarRepos() {
    const q = document.getElementById('ghRepoSearch').value.toLowerCase();
    document.querySelectorAll('.gh-repo-card').forEach(card => {
        card.style.display = card.dataset.name.includes(q) ? '' : 'none';
    });
}

function ghPopularRepoSelects() {
    const opts = '<option value="">Selecione um repositório</option>' +
        ghReposVinculados.map(r => `<option value="${r.id}" data-owner="${r.owner}" data-repo="${r.repo}">${r.owner}/${r.repo}</option>`).join('');
    ['ghCommitRepo','ghPrRepo','ghIssueRepo','ghCiRepo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = opts;
    });
}

// ========================================
// VINCULAR / DESVINCULAR
// ========================================
async function ghAbrirModalVincular() {
    document.getElementById('ghModalVincular').style.display = 'flex';
    document.getElementById('ghSearchRepo').value = '';
    document.getElementById('ghVincularForm').style.display = 'none';
    document.getElementById('ghSearchResults').innerHTML = '<div class="gh-empty-state" style="padding:40px 0"><i class="fab fa-github"></i><p>Digite para buscar seus repositórios</p></div>';
    ghSelectedRepoData = null;
}

function ghBuscarRepoDebounce() {
    clearTimeout(ghSearchTimeout);
    ghSearchTimeout = setTimeout(ghBuscarRepo, 400);
}

async function ghBuscarRepo() {
    const q = document.getElementById('ghSearchRepo').value.trim();
    if (q.length < 2) return;

    const el = document.getElementById('ghSearchResults');
    el.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';

    try {
        const r = await fetch(`${GH_API}?action=repos_remoto&tipo=all&sort=updated`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        const filtered = data.data.filter(repo =>
            repo.full_name.toLowerCase().includes(q.toLowerCase()) ||
            (repo.description || '').toLowerCase().includes(q.toLowerCase())
        );

        if (!filtered.length) {
            el.innerHTML = '<div class="gh-empty-state" style="padding:20px"><p>Nenhum repositório encontrado</p></div>';
            return;
        }

        el.innerHTML = filtered.slice(0, 15).map(repo => `
            <div class="gh-search-item" onclick='ghSelecionarRepo(${JSON.stringify(repo).replace(/'/g, "&#39;")})'>
                <div class="gh-search-item-info">
                    <i class="${repo.private ? 'fas fa-lock' : 'fas fa-book'}" style="color:${repo.private ? 'var(--warning)' : 'var(--primary)'}"></i>
                    <div>
                        <strong>${ghEscape(repo.full_name)}</strong>
                        ${repo.description ? `<p>${ghEscape(repo.description).substring(0, 80)}</p>` : ''}
                    </div>
                </div>
                <div class="gh-search-item-meta">
                    ${repo.language ? `<span>${repo.language}</span>` : ''}
                    <span><i class="fas fa-star"></i> ${repo.stargazers_count}</span>
                </div>
            </div>
        `).join('');
    } catch (e) {
        el.innerHTML = `<p style="color:var(--danger);padding:12px">${e.message}</p>`;
    }
}

function ghSelecionarRepo(repo) {
    ghSelectedRepoData = repo;
    const [owner, repoName] = repo.full_name.split('/');
    document.getElementById('ghSelectedRepo').innerHTML = `
        <div class="gh-selected-info">
            <i class="fab fa-github" style="font-size:24px"></i>
            <div>
                <strong>${ghEscape(repo.full_name)}</strong>
                ${repo.description ? `<p>${ghEscape(repo.description)}</p>` : ''}
            </div>
        </div>
    `;
    document.getElementById('ghVincularForm').style.display = 'block';
}

async function ghVincularRepo() {
    if (!ghSelectedRepoData) return;
    const [owner, repo] = ghSelectedRepoData.full_name.split('/');
    const projetoId = document.getElementById('ghVincularProjeto').value || null;

    try {
        const r = await fetch(GH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'vincular_repo', owner, repo, projeto_id: projetoId })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ghToast(data.message, 'success');
        ghFecharModal('ghModalVincular');
        ghCarregarReposVinculados();
        ghCarregarCommitsRecentes();
    } catch (e) {
        ghToast('Erro: ' + e.message, 'danger');
    }
}

async function ghDesvincular(id) {
    if (!confirm('Desvincular este repositório? Os commits sincronizados serão removidos.')) return;
    try {
        const r = await fetch(GH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'desvincular_repo', id })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ghToast('Repositório desvinculado', 'success');
        ghCarregarReposVinculados();
    } catch (e) {
        ghToast('Erro: ' + e.message, 'danger');
    }
}

async function ghSyncRepo(repoId) {
    try {
        ghToast('Sincronizando...', 'info');
        const r = await fetch(GH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'sync_repo', repo_id: repoId })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ghToast('Repositório sincronizado!', 'success');
        ghCarregarReposVinculados();
        ghCarregarCommitsRecentes();
    } catch (e) {
        ghToast('Erro: ' + e.message, 'danger');
    }
}

async function ghSync() {
    if (!ghReposVinculados.length) return ghToast('Nenhum repositório para sincronizar', 'warning');
    ghToast('Sincronizando todos os repositórios...', 'info');
    for (const repo of ghReposVinculados) {
        try {
            await fetch(GH_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'sync_repo', repo_id: repo.id })
            });
        } catch(e) {}
    }
    ghToast('Sincronização concluída!', 'success');
    ghInit();
}

// ========================================
// REPO DETAIL
// ========================================
async function ghVerRepoDetail(repoId) {
    document.getElementById('ghModalRepoDetail').style.display = 'flex';
    const body = document.getElementById('ghRepoDetailBody');
    body.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i> Carregando detalhes...</div>';

    try {
        const r = await fetch(`${GH_API}?action=repo_overview&repo_id=${repoId}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        const o = data.data;
        const repo = o.repo;
        document.getElementById('ghRepoDetailTitle').innerHTML = `<i class="fab fa-github"></i> ${ghEscape(repo.owner)}/${ghEscape(repo.repo)}`;

        body.innerHTML = `
            ${repo.descricao ? `<p style="color:var(--gray-600);margin-bottom:16px">${ghEscape(repo.descricao)}</p>` : ''}

            <div class="gh-detail-stats">
                <div class="gh-detail-stat"><i class="fas fa-star" style="color:var(--warning)"></i><span>${repo.stars || 0} Stars</span></div>
                <div class="gh-detail-stat"><i class="fas fa-code-branch" style="color:var(--primary)"></i><span>${repo.forks || 0} Forks</span></div>
                <div class="gh-detail-stat"><i class="fas fa-exclamation-circle" style="color:var(--danger)"></i><span>${repo.issues_abertas || 0} Issues</span></div>
                <div class="gh-detail-stat"><i class="fas fa-eye" style="color:var(--info)"></i><span>${o.branches?.length || 0} Branches</span></div>
            </div>

            ${o.languages ? `
            <div class="gh-detail-section">
                <h4><i class="fas fa-code"></i> Linguagens</h4>
                <div class="gh-languages-bar">
                    ${ghRenderLanguages(o.languages)}
                </div>
            </div>` : ''}

            <div class="gh-detail-section">
                <h4><i class="fas fa-code-branch"></i> Branches (${o.branches?.length || 0})</h4>
                <div class="gh-detail-list">
                    ${(o.branches || []).slice(0, 10).map(b => `
                        <span class="gh-branch-tag">${ghEscape(b.name)}</span>
                    `).join('')}
                </div>
            </div>

            <div class="gh-detail-section">
                <h4><i class="fas fa-code-merge"></i> Pull Requests Abertos (${o.pull_requests?.length || 0})</h4>
                <div class="gh-detail-list">
                    ${(o.pull_requests || []).slice(0, 5).map(pr => `
                        <div class="gh-detail-item">
                            <span class="gh-pr-state open"><i class="fas fa-code-merge"></i></span>
                            <a href="${pr.html_url}" target="_blank">#${pr.number} ${ghEscape(pr.title)}</a>
                            <span class="gh-detail-meta">por ${pr.user?.login || 'unknown'}</span>
                        </div>
                    `).join('') || '<p style="color:var(--gray-400)">Nenhum PR aberto</p>'}
                </div>
            </div>

            <div class="gh-detail-section">
                <h4><i class="fas fa-users"></i> Contribuidores (${o.contributors?.length || 0})</h4>
                <div class="gh-contributors">
                    ${(o.contributors || []).slice(0, 10).map(c => `
                        <a href="https://github.com/${c.login}" target="_blank" class="gh-contributor" title="${c.login} (${c.contributions} commits)">
                            <img src="${c.avatar_url}" alt="${c.login}">
                        </a>
                    `).join('')}
                </div>
            </div>

            <div class="gh-detail-section">
                <h4><i class="fas fa-clock"></i> Commits Recentes</h4>
                <div class="gh-detail-list">
                    ${(o.commits || []).slice(0, 5).map(c => `
                        <div class="gh-detail-item">
                            <code>${(c.sha || '').substring(0,7)}</code>
                            <span>${ghEscape((c.commit?.message || c.mensagem || '').split('\n')[0])}</span>
                            <span class="gh-detail-meta">${c.commit?.author?.name || c.autor_nome || ''} · ${ghTimeAgo(c.commit?.author?.date || c.data_commit)}</span>
                        </div>
                    `).join('') || '<p style="color:var(--gray-400)">Nenhum commit</p>'}
                </div>
            </div>

            ${repo.projeto_nome ? `
            <div class="gh-detail-section">
                <h4><i class="fas fa-project-diagram"></i> Projeto Vinculado</h4>
                <p><strong>${ghEscape(repo.projeto_nome)}</strong></p>
            </div>` : ''}

            <div style="display:flex;gap:8px;margin-top:20px">
                <a href="https://github.com/${repo.owner}/${repo.repo}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fab fa-github"></i> Abrir no GitHub
                </a>
                <button class="btn btn-sm" onclick="ghSyncRepo(${repo.id});ghFecharModal('ghModalRepoDetail')">
                    <i class="fas fa-sync-alt"></i> Sincronizar
                </button>
            </div>
        `;
    } catch (e) {
        body.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`;
    }
}

// ========================================
// COMMITS
// ========================================
async function ghCarregarCommits() {
    const sel = document.getElementById('ghCommitRepo');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const owner = opt.dataset.owner;
    const repo = opt.dataset.repo;
    const branch = document.getElementById('ghCommitBranch').value || '';
    const el = document.getElementById('ghCommitsTimeline');
    el.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>';

    // Carregar branches
    if (document.getElementById('ghCommitBranch').style.display === 'none') {
        try {
            const br = await fetch(`${GH_API}?action=branches&owner=${owner}&repo=${repo}`);
            const bd = await br.json();
            if (bd.success) {
                const branchSel = document.getElementById('ghCommitBranch');
                branchSel.innerHTML = '<option value="">Todas as branches</option>' +
                    bd.data.map(b => `<option value="${b.name}">${b.name}</option>`).join('');
                branchSel.style.display = '';
            }
        } catch(e) {}
    }

    try {
        let url = `${GH_API}?action=commits&owner=${owner}&repo=${repo}&limit=40`;
        if (branch) url += `&branch=${branch}`;
        const r = await fetch(url);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-code-branch"></i><p>Nenhum commit encontrado</p></div>';
            return;
        }

        el.innerHTML = data.data.map(c => {
            const author = c.commit?.author || {};
            const user = c.author || {};
            return `
            <div class="gh-commit-item">
                <div class="gh-commit-dot"></div>
                <div class="gh-commit-content">
                    <div class="gh-commit-header">
                        <img src="${user.avatar_url || 'https://github.com/ghost.png'}" alt="" class="gh-commit-author-img">
                        <strong>${ghEscape(author.name || 'Unknown')}</strong>
                        <span class="gh-commit-date">${ghTimeAgo(author.date)}</span>
                    </div>
                    <p class="gh-commit-message">${ghEscape(c.commit?.message?.split('\n')[0] || '')}</p>
                    ${c.commit?.message?.split('\n').length > 1 ? `<p class="gh-commit-body">${ghEscape(c.commit.message.split('\n').slice(1).join('\n').trim())}</p>` : ''}
                    <div class="gh-commit-footer">
                        <a href="${c.html_url}" target="_blank" class="gh-commit-sha"><i class="fas fa-code"></i> ${c.sha.substring(0,7)}</a>
                        ${c.stats ? `<span class="gh-additions">+${c.stats.additions || 0}</span><span class="gh-deletions">-${c.stats.deletions || 0}</span>` : ''}
                    </div>
                </div>
            </div>
            `;
        }).join('');
    } catch (e) {
        el.innerHTML = `<div class="gh-empty-state"><p style="color:var(--danger)">${e.message}</p></div>`;
    }
}

// ========================================
// PULL REQUESTS
// ========================================
function ghTogglePRState(state) {
    ghCurrentPRState = state;
    document.querySelectorAll('#tab-gh-prs .gh-toggle').forEach(b => b.classList.remove('active'));
    document.querySelector(`#tab-gh-prs .gh-toggle[data-state="${state}"]`).classList.add('active');
    ghCarregarPRs();
}

async function ghCarregarPRs() {
    const sel = document.getElementById('ghPrRepo');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const owner = opt.dataset.owner;
    const repo = opt.dataset.repo;
    const el = document.getElementById('ghPrList');
    el.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>';

    try {
        const r = await fetch(`${GH_API}?action=pull_requests&owner=${owner}&repo=${repo}&state=${ghCurrentPRState}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-code-merge"></i><p>Nenhum Pull Request encontrado</p></div>';
            return;
        }

        el.innerHTML = data.data.map(pr => `
            <div class="gh-list-item" onclick="ghVerPR('${owner}','${repo}',${pr.number})">
                <div class="gh-list-icon ${pr.state === 'open' ? 'open' : (pr.merged_at ? 'merged' : 'closed')}">
                    <i class="fas ${pr.merged_at ? 'fa-code-merge' : (pr.state === 'open' ? 'fa-code-branch' : 'fa-times')}"></i>
                </div>
                <div class="gh-list-content">
                    <div class="gh-list-title">
                        <span>#${pr.number}</span>
                        <strong>${ghEscape(pr.title)}</strong>
                    </div>
                    <div class="gh-list-meta">
                        <img src="${pr.user?.avatar_url || ''}" alt="" class="gh-meta-avatar">
                        <span>${pr.user?.login || 'unknown'}</span>
                        <span>·</span>
                        <span>${ghTimeAgo(pr.created_at)}</span>
                        ${pr.labels?.length ? pr.labels.map(l => `<span class="gh-label" style="background:#${l.color}20;color:#${l.color};border:1px solid #${l.color}40">${l.name}</span>`).join('') : ''}
                    </div>
                </div>
                <div class="gh-list-stats">
                    <span class="gh-additions">+${pr.additions || 0}</span>
                    <span class="gh-deletions">-${pr.deletions || 0}</span>
                    <span><i class="fas fa-comment"></i> ${pr.comments || 0}</span>
                </div>
            </div>
        `).join('');
    } catch (e) {
        el.innerHTML = `<div class="gh-empty-state"><p style="color:var(--danger)">${e.message}</p></div>`;
    }
}

async function ghVerPR(owner, repo, number) {
    document.getElementById('ghModalPR').style.display = 'flex';
    const body = document.getElementById('ghPRBody');
    body.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>';

    try {
        const r = await fetch(`${GH_API}?action=pull_request&owner=${owner}&repo=${repo}&number=${number}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        const pr = data.data.pr;
        const files = data.data.files;

        document.getElementById('ghPRTitle').innerHTML = `<i class="fas fa-code-merge"></i> #${pr.number} ${ghEscape(pr.title)}`;

        body.innerHTML = `
            <div class="gh-pr-status ${pr.state === 'open' ? 'open' : (pr.merged_at ? 'merged' : 'closed')}">
                <i class="fas ${pr.merged_at ? 'fa-code-merge' : (pr.state === 'open' ? 'fa-code-branch' : 'fa-times')}"></i>
                <span>${pr.merged_at ? 'Merged' : (pr.state === 'open' ? 'Open' : 'Closed')}</span>
            </div>

            <div class="gh-pr-info">
                <img src="${pr.user?.avatar_url || ''}" alt="" class="gh-pr-avatar">
                <span><strong>${pr.user?.login}</strong> quer fazer merge de <code>${ghEscape(pr.head?.label || '')}</code> em <code>${ghEscape(pr.base?.label || '')}</code></span>
            </div>

            ${pr.body ? `<div class="gh-pr-body">${ghEscape(pr.body)}</div>` : ''}

            <div class="gh-pr-stats-bar">
                <span class="gh-additions">+${pr.additions || 0} adições</span>
                <span class="gh-deletions">-${pr.deletions || 0} remoções</span>
                <span><i class="fas fa-file"></i> ${pr.changed_files || 0} arquivos</span>
                <span><i class="fas fa-comment"></i> ${pr.comments || 0} comentários</span>
            </div>

            ${files?.length ? `
            <div class="gh-pr-files">
                <h4><i class="fas fa-file-code"></i> Arquivos Alterados (${files.length})</h4>
                ${files.map(f => `
                    <div class="gh-pr-file">
                        <span class="gh-pr-file-status ${f.status}">${f.status === 'added' ? '+' : (f.status === 'removed' ? '-' : '~')}</span>
                        <span class="gh-pr-file-name">${ghEscape(f.filename)}</span>
                        <span class="gh-pr-file-changes">
                            <span class="gh-additions">+${f.additions}</span>
                            <span class="gh-deletions">-${f.deletions}</span>
                        </span>
                    </div>
                `).join('')}
            </div>` : ''}

            <a href="${pr.html_url}" target="_blank" class="btn btn-primary btn-sm" style="margin-top:16px">
                <i class="fab fa-github"></i> Abrir no GitHub
            </a>
        `;
    } catch (e) {
        body.innerHTML = `<p style="color:var(--danger)">${e.message}</p>`;
    }
}

// ========================================
// ISSUES
// ========================================
function ghToggleIssueState(state) {
    ghCurrentIssueState = state;
    document.querySelectorAll('#tab-gh-issues .gh-toggle').forEach(b => b.classList.remove('active'));
    document.querySelector(`#tab-gh-issues .gh-toggle[data-state="${state}"]`).classList.add('active');
    ghCarregarIssues();
}

async function ghCarregarIssues() {
    const sel = document.getElementById('ghIssueRepo');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const owner = opt.dataset.owner;
    const repo = opt.dataset.repo;
    const el = document.getElementById('ghIssueList');
    el.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>';

    try {
        const r = await fetch(`${GH_API}?action=issues&owner=${owner}&repo=${repo}&state=${ghCurrentIssueState}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        if (!data.data.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-check-circle" style="color:var(--success)"></i><p>Nenhuma issue encontrada</p></div>';
            return;
        }

        el.innerHTML = data.data.map(issue => `
            <div class="gh-list-item">
                <div class="gh-list-icon ${issue.state}">
                    <i class="fas ${issue.state === 'open' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
                </div>
                <div class="gh-list-content">
                    <div class="gh-list-title">
                        <span>#${issue.number}</span>
                        <a href="${issue.html_url}" target="_blank"><strong>${ghEscape(issue.title)}</strong></a>
                    </div>
                    <div class="gh-list-meta">
                        <img src="${issue.user?.avatar_url || ''}" alt="" class="gh-meta-avatar">
                        <span>${issue.user?.login || 'unknown'}</span>
                        <span>·</span>
                        <span>${ghTimeAgo(issue.created_at)}</span>
                        ${issue.labels?.length ? issue.labels.map(l => `<span class="gh-label" style="background:#${l.color}20;color:#${l.color};border:1px solid #${l.color}40">${l.name}</span>`).join('') : ''}
                    </div>
                </div>
                <div class="gh-list-stats">
                    <span><i class="fas fa-comment"></i> ${issue.comments || 0}</span>
                </div>
            </div>
        `).join('');
    } catch (e) {
        el.innerHTML = `<div class="gh-empty-state"><p style="color:var(--danger)">${e.message}</p></div>`;
    }
}

function ghAbrirModalNovaIssue() {
    const sel = document.getElementById('ghIssueRepo');
    if (!sel.value) return ghToast('Selecione um repositório primeiro', 'warning');
    document.getElementById('ghModalIssue').style.display = 'flex';
    document.getElementById('ghIssueTitle').value = '';
    document.getElementById('ghIssueBody').value = '';
    document.getElementById('ghIssueLabels').value = '';
}

async function ghCriarIssue() {
    const sel = document.getElementById('ghIssueRepo');
    const opt = sel.options[sel.selectedIndex];
    const title = document.getElementById('ghIssueTitle').value.trim();
    if (!title) return ghToast('Título obrigatório', 'warning');

    const labelsStr = document.getElementById('ghIssueLabels').value.trim();
    const labels = labelsStr ? labelsStr.split(',').map(l => l.trim()).filter(Boolean) : [];

    try {
        const r = await fetch(GH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'criar_issue',
                owner: opt.dataset.owner,
                repo: opt.dataset.repo,
                title,
                body: document.getElementById('ghIssueBody').value,
                labels
            })
        });
        const data = await r.json();
        if (!data.success) throw new Error(data.error);
        ghToast('Issue criada!', 'success');
        ghFecharModal('ghModalIssue');
        ghCarregarIssues();
    } catch (e) {
        ghToast('Erro: ' + e.message, 'danger');
    }
}

// ========================================
// CI/CD (WORKFLOWS)
// ========================================
async function ghCarregarWorkflows() {
    const sel = document.getElementById('ghCiRepo');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const owner = opt.dataset.owner;
    const repo = opt.dataset.repo;
    const el = document.getElementById('ghCiList');
    el.innerHTML = '<div class="gh-loading"><i class="fas fa-spinner fa-spin"></i></div>';

    try {
        const r = await fetch(`${GH_API}?action=workflows&owner=${owner}&repo=${repo}`);
        const data = await r.json();
        if (!data.success) throw new Error(data.error);

        const runs = data.data.workflow_runs || data.data || [];
        if (!runs.length) {
            el.innerHTML = '<div class="gh-empty-state"><i class="fas fa-cogs"></i><p>Nenhum workflow encontrado</p></div>';
            return;
        }

        el.innerHTML = runs.map(run => {
            const statusIcon = {
                'completed': run.conclusion === 'success' ? 'fa-check-circle' : (run.conclusion === 'failure' ? 'fa-times-circle' : 'fa-minus-circle'),
                'in_progress': 'fa-spinner fa-spin',
                'queued': 'fa-clock'
            }[run.status] || 'fa-circle';

            const statusColor = {
                'success': 'var(--success)',
                'failure': 'var(--danger)',
                'cancelled': 'var(--gray-400)',
                'in_progress': 'var(--warning)'
            }[run.conclusion || run.status] || 'var(--gray-500)';

            return `
            <div class="gh-list-item">
                <div class="gh-list-icon" style="background:${statusColor}15;color:${statusColor}">
                    <i class="fas ${statusIcon}"></i>
                </div>
                <div class="gh-list-content">
                    <div class="gh-list-title">
                        <strong>${ghEscape(run.name || run.workflow?.name || 'Workflow')}</strong>
                    </div>
                    <div class="gh-list-meta">
                        <span>${ghEscape(run.head_branch || '')}</span>
                        <span>·</span>
                        <span>${run.head_commit?.message?.split('\n')[0] || ''}</span>
                        <span>·</span>
                        <span>${ghTimeAgo(run.created_at)}</span>
                    </div>
                </div>
                <div class="gh-list-stats">
                    <span class="gh-ci-status" style="color:${statusColor}">${run.conclusion || run.status}</span>
                    <a href="${run.html_url}" target="_blank" class="btn-icon"><i class="fas fa-external-link-alt"></i></a>
                </div>
            </div>
            `;
        }).join('');
    } catch (e) {
        el.innerHTML = `<div class="gh-empty-state"><p style="color:var(--danger)">${e.message}</p></div>`;
    }
}

// ========================================
// CONFIG TAB
// ========================================
function ghRenderConfig() {
    if (!ghUser) return;
    document.getElementById('ghConfigUser').innerHTML = `
        <div class="gh-config-user-card">
            <img src="${ghUser.avatar_url}" alt="${ghUser.login}" class="gh-config-avatar">
            <div>
                <h3>${ghUser.name || ghUser.login}</h3>
                <p>@${ghUser.login}</p>
                ${ghUser.bio ? `<p style="color:var(--gray-500);margin-top:4px">${ghEscape(ghUser.bio)}</p>` : ''}
                <div class="gh-config-meta">
                    <span><i class="fas fa-book"></i> ${ghUser.public_repos} repos</span>
                    <span><i class="fas fa-users"></i> ${ghUser.followers} seguidores</span>
                    <span><i class="fas fa-user-friends"></i> ${ghUser.following} seguindo</span>
                </div>
            </div>
        </div>
    `;
}

// ========================================
// UTILITÁRIOS
// ========================================
function ghFecharModal(id) {
    document.getElementById(id).style.display = 'none';
}

function ghEscape(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function ghTimeAgo(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'agora';
    if (diff < 3600) return Math.floor(diff / 60) + 'min atrás';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h atrás';
    if (diff < 2592000) return Math.floor(diff / 86400) + 'd atrás';
    return d.toLocaleDateString('pt-BR');
}

function ghLangColor(lang) {
    const colors = {
        'JavaScript': '#f1e05a', 'TypeScript': '#3178c6', 'Python': '#3572A5', 'PHP': '#4F5D95',
        'Java': '#b07219', 'C#': '#178600', 'C++': '#f34b7d', 'Go': '#00ADD8', 'Rust': '#dea584',
        'Ruby': '#701516', 'Swift': '#F05138', 'Kotlin': '#A97BFF', 'Dart': '#00B4AB',
        'HTML': '#e34c26', 'CSS': '#563d7c', 'Shell': '#89e051', 'Vue': '#41b883', 'Svelte': '#ff3e00'
    };
    return colors[lang] || '#8b949e';
}

function ghRenderLanguages(langs) {
    if (!langs || typeof langs !== 'object') return '';
    const total = Object.values(langs).reduce((a, b) => a + b, 0);
    if (!total) return '';
    const entries = Object.entries(langs).sort((a, b) => b[1] - a[1]);
    const bar = entries.map(([l, v]) => `<div style="width:${(v/total*100).toFixed(1)}%;background:${ghLangColor(l)}" title="${l}: ${(v/total*100).toFixed(1)}%"></div>`).join('');
    const legend = entries.slice(0, 6).map(([l, v]) => `<span><span class="gh-lang-dot" style="background:${ghLangColor(l)}"></span>${l} ${(v/total*100).toFixed(1)}%</span>`).join('');
    return `<div class="gh-lang-bar">${bar}</div><div class="gh-lang-legend">${legend}</div>`;
}

function ghToast(msg, type = 'info') {
    const container = document.getElementById('ghToastContainer') || (() => {
        const c = document.createElement('div');
        c.id = 'ghToastContainer';
        c.className = 'gh-toast-container';
        document.body.appendChild(c);
        return c;
    })();

    const icons = { success: 'check-circle', danger: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    const toast = document.createElement('div');
    toast.className = `gh-toast ${type}`;
    toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i><span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.classList.add('fade-out'); setTimeout(() => toast.remove(), 300); }, 3500);
}

// Fechar modais com ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
    }
});

// Fechar modais clicando fora
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.style.display = 'none';
    });
});
</script>
