<?php
/**
 * View: Active Directory Management
 * Gerenciamento de usuários, grupos e OUs do AD
 */
$user = currentUser();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-network-wired" style="margin-right:8px;color:#6366F1"></i> Active Directory</h1>
        <p class="page-subtitle">Gerenciamento de usuários, grupos e unidades organizacionais</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="abrirModalCriarUsuario()">
            <i class="fas fa-user-plus"></i> Novo Usuário
        </button>
    </div>
</div>

<!-- Status de Conexão -->
<div class="ad-connection-status" id="adConnectionStatus">
    <div class="ad-status-checking">
        <i class="fas fa-spinner fa-spin"></i> Verificando conexão com Active Directory...
    </div>
</div>

<!-- Abas -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="usuarios" onclick="switchTab('usuarios')">
        <i class="fas fa-users"></i> Usuários
    </button>
    <button class="ad-tab" data-tab="grupos" onclick="switchTab('grupos')">
        <i class="fas fa-users-cog"></i> Grupos
    </button>
    <button class="ad-tab" data-tab="ous" onclick="switchTab('ous')">
        <i class="fas fa-folder-tree"></i> Unidades Organizacionais
    </button>
    <button class="ad-tab" data-tab="config" onclick="switchTab('config')">
        <i class="fas fa-cog"></i> Configuração
    </button>
</div>

<!-- ==================== ABA: USUÁRIOS ==================== -->
<div class="ad-tab-content active" id="tab-usuarios">
    <div class="ad-usuarios-layout">
        <!-- Painel lateral: Pastas/OUs -->
        <div class="ad-ou-sidebar">
            <div class="ad-ou-sidebar-header">
                <h4><i class="fas fa-folder-tree"></i> Pastas</h4>
                <button class="btn-icon-sm" onclick="carregarOUsSidebar()" title="Atualizar"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="ad-ou-sidebar-list" id="ouSidebarList">
                <div class="ad-ou-sidebar-item active" data-ou="" onclick="selecionarOU(this, '')">
                    <i class="fas fa-globe"></i>
                    <span>Todos os Usuários</span>
                    <span class="ad-ou-badge" id="countTodos"></span>
                </div>
            </div>
        </div>

        <!-- Conteúdo principal: Tabela de Usuários -->
        <div class="ad-usuarios-main">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="ad-filters">
                        <div class="ad-filter-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="buscaUsuarioAD" class="form-input" placeholder="Buscar por nome, login, e-mail..."
                                   onkeydown="if(event.key==='Enter') carregarUsuarios()">
                        </div>
                        <button class="btn btn-secondary" onclick="carregarUsuarios()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button class="btn btn-outline" onclick="carregarUsuarios()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="ad-ou-breadcrumb" id="ouBreadcrumb">
                        <i class="fas fa-folder-open"></i> <span>Todos os Usuários</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table" id="tabelaUsuariosAD">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Nome</th>
                                <th>Login</th>
                                <th>E-mail</th>
                                <th>Departamento</th>
                                <th>Grupos</th>
                                <th width="140">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyUsuariosAD">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando usuários...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== ABA: GRUPOS ==================== -->
<div class="ad-tab-content" id="tab-grupos">
    <div class="card mb-4">
        <div class="card-body">
            <div class="ad-filters">
                <div class="ad-filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="buscaGrupoAD" class="form-input" placeholder="Buscar grupo..."
                           onkeydown="if(event.key==='Enter') carregarGrupos()">
                </div>
                <button class="btn btn-secondary" onclick="carregarGrupos()">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </div>
    </div>

    <div class="ad-grupos-grid" id="gruposGrid">
        <div class="loading-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Carregando grupos...</p>
        </div>
    </div>
</div>

<!-- ==================== ABA: OUs ==================== -->
<div class="ad-tab-content" id="tab-ous">
    <div class="card">
        <div class="card-header-custom">
            <h3><i class="fas fa-folder-tree"></i> Unidades Organizacionais</h3>
            <button class="btn btn-outline btn-sm" onclick="carregarOUs()">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
        <div class="card-body" id="ousContainer">
            <div class="loading-center">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Carregando OUs...</p>
            </div>
        </div>
    </div>
</div>

<!-- ==================== ABA: CONFIGURAÇÃO ==================== -->
<div class="ad-tab-content" id="tab-config">
    <div class="card">
        <div class="card-header-custom">
            <h3><i class="fas fa-cog"></i> Conexão com Active Directory</h3>
        </div>
        <div class="card-body">
            <div class="ad-config-help">
                <div class="ad-config-help-icon"><i class="fas fa-info-circle"></i></div>
                <div>
                    <strong>Como preencher?</strong>
                    <p>O <b>Base DN</b> é o ponto raiz de busca no Active Directory. Ele é derivado do nome do seu domínio, onde cada parte vira um componente <code>DC=</code>.</p>
                    <div class="ad-config-examples">
                        <span><code>texasco.local</code> → <code>DC=texasco,DC=local</code></span>
                        <span><code>empresa.com.br</code> → <code>DC=empresa,DC=com,DC=br</code></span>
                    </div>
                </div>
            </div>
            <form id="formAdConfig" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Servidor (IP ou hostname) *</label>
                    <input type="text" name="ad_server" id="ad_server" class="form-input" placeholder="Ex: 192.168.1.10 ou dc01.texasco.local" required>
                    <small class="form-hint">IP ou nome do Domain Controller</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Porta</label>
                    <input type="number" name="ad_porta" id="ad_porta" class="form-input" value="389" placeholder="389">
                    <small class="form-hint">389 para LDAP, 636 para LDAPS (SSL)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Domínio *</label>
                    <input type="text" name="ad_dominio" id="ad_dominio" class="form-input" placeholder="Ex: texasco.local" oninput="sugerirBaseDN(this.value)">
                    <small class="form-hint">Nome completo do domínio AD</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Base DN * <span id="baseDnSugestao" class="ad-sugestao"></span></label>
                    <input type="text" name="ad_base_dn" id="ad_base_dn" class="form-input" placeholder="Ex: DC=texasco,DC=local" required>
                    <small class="form-hint">Ponto raiz de busca (gerado automaticamente ao digitar o domínio)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">SSL / LDAPS</label>
                    <select name="ad_ssl" id="ad_ssl" class="form-select" onchange="ajustarPortaSSL(this.value)">
                        <option value="0">Não (LDAP — porta 389)</option>
                        <option value="1">Sim (LDAPS — porta 636)</option>
                    </select>
                    <small class="form-hint">LDAPS melhora segurança (reset de senha usa fallback automático)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Usuário Admin *</label>
                    <input type="text" name="ad_admin_user" id="ad_admin_user" class="form-input" placeholder="Ex: administrador@texasco.local" required>
                    <small class="form-hint">UPN do administrador (usuario@dominio)</small>
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Senha Admin *</label>
                    <div class="input-password-wrap">
                        <input type="password" name="ad_admin_pass" id="ad_admin_pass" class="form-input" placeholder="Deixe vazio para manter a senha atual">
                        <button type="button" class="btn-icon-input" onclick="toggleSenhaInput('ad_admin_pass','eyeAdminPass')" title="Mostrar">
                            <i class="fas fa-eye" id="eyeAdminPass"></i>
                        </button>
                    </div>
                    <small class="form-hint">Senha do administrador do domínio</small>
                </div>
                <div class="form-group col-span-2">
                    <div class="ad-config-actions">
                        <button type="button" class="btn btn-secondary" onclick="testarConexaoAD()">
                            <i class="fas fa-plug"></i> Testar Conexão
                        </button>
                        <button type="button" class="btn btn-primary" onclick="salvarConfigAD()">
                            <i class="fas fa-save"></i> Salvar Configuração
                        </button>
                    </div>
                    <div id="adTestResult" class="ad-test-result" style="display:none"></div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ===== TABS =====
function switchTab(tab) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');

    // Carregar dados da aba
    if (tab === 'usuarios' && !usuariosLoaded) carregarUsuarios();
    if (tab === 'grupos' && !gruposLoaded) carregarGrupos();
    if (tab === 'ous' && !ousLoaded) carregarOUs();
    if (tab === 'config' && !configLoaded) carregarConfig();
}

let usuariosLoaded = false, gruposLoaded = false, ousLoaded = false, configLoaded = false;
let gruposCache = [], ousCache = [];
let ouSelecionadaDn = '', ouSelecionadaNome = 'Todos os Usuários';

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    verificarConexao();
});

function verificarConexao() {
    HelpDesk.api('POST', '/api/ad.php', { action: 'testar_conexao' })
        .then(resp => {
            const el = document.getElementById('adConnectionStatus');
            if (resp.success) {
                el.innerHTML = `<div class="ad-status-ok"><i class="fas fa-check-circle"></i> Conectado ao Active Directory</div>`;
                carregarOUsSidebar();
                carregarUsuarios();
            } else {
                el.innerHTML = `<div class="ad-status-err"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(resp.message || resp.error || 'Falha na conexão')}
                    <button class="btn btn-sm btn-secondary ml-3" onclick="switchTab('config')">Configurar</button></div>`;
                switchTab('config');
                carregarConfig();
            }
        });
}

// ===== OUs SIDEBAR =====
function carregarOUsSidebar() {
    HelpDesk.api('GET', '/api/ad.php', { action: 'ous' })
        .then(resp => {
            if (!resp.success) return;
            ousCache = resp.data;
            renderOUsSidebar(resp.data);
        });
}

function renderOUsSidebar(ous) {
    const list = document.getElementById('ouSidebarList');
    // Manter o item "Todos"
    let html = `
        <div class="ad-ou-sidebar-item ${ouSelecionadaDn === '' ? 'active' : ''}" data-ou="" onclick="selecionarOU(this, '')">
            <i class="fas fa-globe"></i>
            <span>Todos os Usuários</span>
        </div>`;

    // Agrupar OUs por nível/pai para criar árvore
    // Filtrar OUs que contêm "Usuarios" ou são relevantes
    const ousOrdenadas = ous.sort((a, b) => {
        // Ordenar por DN (menos vírgulas = mais superficial)
        const depthA = (a.dn.match(/,/g) || []).length;
        const depthB = (b.dn.match(/,/g) || []).length;
        if (depthA !== depthB) return depthA - depthB;
        return a.nome.localeCompare(b.nome);
    });

    ousOrdenadas.forEach(ou => {
        const indent = Math.max(0, ou.nivel) * 16;
        const isActive = ouSelecionadaDn === ou.dn;
        const icon = ou.nivel <= 1 ? 'fa-folder-open' : 'fa-folder';
        html += `
        <div class="ad-ou-sidebar-item ${isActive ? 'active' : ''}" data-ou="${escapeAttr(ou.dn)}" onclick="selecionarOU(this, '${escapeAttr(ou.dn)}', '${escapeAttr(ou.nome)}')" style="padding-left:${12 + indent}px" title="${escapeAttr(ou.dn)}">
            <i class="fas ${icon}"></i>
            <span>${escapeHtml(ou.nome)}</span>
            ${ou.objetos > 0 ? `<span class="ad-ou-badge">${ou.objetos}</span>` : ''}
        </div>`;
    });

    list.innerHTML = html;
}

function selecionarOU(el, ouDn, ouNome) {
    ouSelecionadaDn = ouDn;
    ouSelecionadaNome = ouNome || 'Todos os Usuários';

    // Atualizar visual
    document.querySelectorAll('.ad-ou-sidebar-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');

    // Breadcrumb
    const bc = document.getElementById('ouBreadcrumb');
    if (ouDn) {
        bc.innerHTML = `<i class="fas fa-folder-open"></i> <span>${escapeHtml(ouNome)}</span> <code class="ad-bc-dn">${escapeHtml(ouDn)}</code>`;
    } else {
        bc.innerHTML = `<i class="fas fa-folder-open"></i> <span>Todos os Usuários</span>`;
    }

    carregarUsuarios();
}

// ===== USUÁRIOS =====
function carregarUsuarios() {
    const tbody = document.getElementById('tbodyUsuariosAD');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

    const busca = document.getElementById('buscaUsuarioAD')?.value || '';
    const params = { action: 'usuarios', busca: busca };
    if (ouSelecionadaDn) params.ou_dn = ouSelecionadaDn;

    HelpDesk.api('GET', '/api/ad.php', params)
        .then(resp => {
            if (!resp.success) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${escapeHtml(resp.error)}</td></tr>`;
                return;
            }
            usuariosLoaded = true;
            const data = resp.data;

            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-users"></i> Nenhum usuário encontrado</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(u => {
                const statusClass = !u.ativo ? 'ad-status-disabled' : u.bloqueado ? 'ad-status-locked' : 'ad-status-active';
                const statusLabel = !u.ativo ? 'Desabilitado' : u.bloqueado ? 'Bloqueado' : 'Ativo';
                const statusIcon = !u.ativo ? 'fa-ban' : u.bloqueado ? 'fa-lock' : 'fa-check-circle';
                const gruposStr = u.grupos.slice(0, 3).join(', ') + (u.grupos.length > 3 ? ` +${u.grupos.length - 3}` : '');
                const dnEsc = escapeAttr(u.dn);

                return `<tr>
                    <td><span class="ad-status-pill ${statusClass}"><i class="fas ${statusIcon}"></i> ${statusLabel}</span></td>
                    <td><strong>${escapeHtml(u.nome)}</strong></td>
                    <td><code>${escapeHtml(u.login)}</code></td>
                    <td>${escapeHtml(u.email) || '<span class="text-muted">—</span>'}</td>
                    <td>${escapeHtml(u.departamento) || '<span class="text-muted">—</span>'}</td>
                    <td><span class="ad-grupos-cell" title="${escapeAttr(u.grupos.join(', '))}">${escapeHtml(gruposStr) || '<span class="text-muted">—</span>'}</span></td>
                    <td>
                        <div class="ad-row-actions">
                            <button class="btn-icon" title="Resetar Senha" onclick="abrirResetSenha('${dnEsc}', '${escapeAttr(u.nome)}')"><i class="fas fa-key"></i></button>
                            <button class="btn-icon" title="Gerenciar Grupos" onclick="abrirGerenciarGrupos('${dnEsc}', '${escapeAttr(u.nome)}')"><i class="fas fa-users-cog"></i></button>
                            ${u.ativo
                                ? `<button class="btn-icon text-danger" title="Desabilitar" onclick="toggleConta('${dnEsc}', false, '${escapeAttr(u.nome)}')"><i class="fas fa-user-slash"></i></button>`
                                : `<button class="btn-icon text-success" title="Habilitar" onclick="toggleConta('${dnEsc}', true, '${escapeAttr(u.nome)}')"><i class="fas fa-user-check"></i></button>`
                            }
                            ${u.bloqueado ? `<button class="btn-icon text-warning" title="Desbloquear" onclick="desbloquearConta('${dnEsc}')"><i class="fas fa-unlock"></i></button>` : ''}
                        </div>
                    </td>
                </tr>`;
            }).join('');
        });
}

// ===== CRIAR USUÁRIO =====
function abrirModalCriarUsuario() {
    // Primeiro carregar OUs e grupos
    Promise.all([
        gruposCache.length ? Promise.resolve({ data: gruposCache }) : HelpDesk.api('GET', '/api/ad.php', { action: 'grupos' }).then(r => { if(r.success) gruposCache = r.data; return r; }),
        ousCache.length ? Promise.resolve({ data: ousCache }) : HelpDesk.api('GET', '/api/ad.php', { action: 'ous' }).then(r => { if(r.success) ousCache = r.data; return r; })
    ]).then(([gruposResp, ousResp]) => {
        const grupos = gruposResp.data || [];
        const ous = ousResp.data || [];

        const ousOpts = ous.map(o => `<option value="${escapeAttr(o.dn)}">${escapeHtml(o.nome)} (${escapeHtml(o.dn)})</option>`).join('');
        const gruposCheckboxes = grupos.map(g =>
            `<label class="ad-checkbox-item"><input type="checkbox" name="grupos[]" value="${escapeAttr(g.dn)}"> ${escapeHtml(g.nome)}</label>`
        ).join('');

        const html = `
        <form id="formCriarUsuarioAD" class="form-grid">
            <div class="form-group">
                <label class="form-label">Primeiro Nome *</label>
                <input type="text" name="primeiro_nome" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Sobrenome *</label>
                <input type="text" name="sobrenome" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Login (sAMAccountName) *</label>
                <input type="text" name="login" class="form-input" required placeholder="joao.silva">
            </div>
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-input" placeholder="joao@empresa.com">
            </div>
            <div class="form-group">
                <label class="form-label">Senha *</label>
                <div class="input-password-wrap">
                    <input type="password" name="senha" id="senhaNovoUsuarioAD" class="form-input" required>
                    <button type="button" class="btn-icon-input" onclick="toggleSenhaInput('senhaNovoUsuarioAD','eyeNovoAD')" title="Mostrar">
                        <i class="fas fa-eye" id="eyeNovoAD"></i>
                    </button>
                    <button type="button" class="btn-icon-input" onclick="gerarSenhaAD('senhaNovoUsuarioAD')" title="Gerar senha">
                        <i class="fas fa-magic"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Unidade Organizacional (OU)</label>
                <select name="ou_dn" class="form-select">
                    <option value="">Padrão (Base DN)</option>
                    ${ousOpts}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Cargo</label>
                <input type="text" name="cargo" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Departamento</label>
                <input type="text" name="departamento" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Empresa</label>
                <input type="text" name="empresa" class="form-input">
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Descrição</label>
                <input type="text" name="descricao" class="form-input">
            </div>
            <div class="form-group col-span-2">
                <label class="ad-checkbox-item" style="margin-bottom:12px">
                    <input type="checkbox" name="trocar_senha" value="1" checked>
                    Forçar troca de senha no próximo login
                </label>
            </div>
            ${grupos.length ? `
            <div class="form-group col-span-2">
                <label class="form-label">Adicionar aos Grupos</label>
                <div class="ad-checkbox-grid">${gruposCheckboxes}</div>
            </div>` : ''}
        </form>`;

        HelpDesk.showModal('Novo Usuário AD', html, `
            <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="submitCriarUsuarioAD()"><i class="fas fa-user-plus"></i> Criar Usuário</button>
        `, 'modal-lg');
    });
}

function submitCriarUsuarioAD() {
    const form = document.getElementById('formCriarUsuarioAD');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const formData = new FormData(form);
    const data = {};
    const grupos = [];
    for (let [k, v] of formData.entries()) {
        if (k === 'grupos[]') grupos.push(v);
        else data[k] = v;
    }
    data.action = 'criar_usuario';
    data.grupos = grupos;

    const btn = document.querySelector('.modal-footer .btn-primary');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...'; }

    HelpDesk.api('POST', '/api/ad.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                HelpDesk.closeModal();
                carregarUsuarios();
            } else {
                HelpDesk.toast(resp.error, 'danger');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-user-plus"></i> Criar Usuário'; }
            }
        });
}

// ===== RESET SENHA =====
function abrirResetSenha(dn, nome) {
    const html = `
    <form id="formResetSenha" class="form-grid">
        <input type="hidden" name="dn" value="${escapeAttr(dn)}">
        <div class="form-group col-span-2">
            <p class="mb-3">Resetar senha de <strong>${escapeHtml(nome)}</strong></p>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Nova Senha *</label>
            <div class="input-password-wrap">
                <input type="password" name="nova_senha" id="resetSenhaInput" class="form-input" required>
                <button type="button" class="btn-icon-input" onclick="toggleSenhaInput('resetSenhaInput','eyeResetAD')" title="Mostrar">
                    <i class="fas fa-eye" id="eyeResetAD"></i>
                </button>
                <button type="button" class="btn-icon-input" onclick="gerarSenhaAD('resetSenhaInput')" title="Gerar senha">
                    <i class="fas fa-magic"></i>
                </button>
            </div>
        </div>
        <div class="form-group col-span-2">
            <label class="ad-checkbox-item">
                <input type="checkbox" name="forcar_troca" value="1" checked>
                Forçar troca no próximo login
            </label>
        </div>
    </form>`;

    HelpDesk.showModal('<i class="fas fa-key"></i> Resetar Senha', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitResetSenha()"><i class="fas fa-key"></i> Resetar</button>
    `);
}

function submitResetSenha() {
    const form = document.getElementById('formResetSenha');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = 'resetar_senha';

    const btn = document.querySelector('.modal-footer .btn-primary');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetando...'; }

    HelpDesk.api('POST', '/api/ad.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                HelpDesk.closeModal();
            } else {
                HelpDesk.toast(resp.error, 'danger');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-key"></i> Resetar'; }
            }
        });
}

// ===== GERENCIAR GRUPOS DO USUÁRIO =====
function abrirGerenciarGrupos(userDn, userName) {
    // Carregar dados do usuário e todos os grupos
    Promise.all([
        HelpDesk.api('GET', '/api/ad.php', { action: 'usuario', dn: userDn }),
        gruposCache.length ? Promise.resolve({ data: gruposCache }) : HelpDesk.api('GET', '/api/ad.php', { action: 'grupos' }).then(r => { if(r.success) gruposCache = r.data; return r; })
    ]).then(([userResp, gruposResp]) => {
        if (!userResp.success) return HelpDesk.toast(userResp.error, 'danger');

        const usuario = userResp.data;
        const todosGrupos = gruposResp.data || [];
        const gruposDoUsuario = usuario.grupos || [];
        const gruposDoUsuarioSet = new Set(gruposDoUsuario.map(g => g.toLowerCase()));

        let html = `<p class="mb-3">Grupos de <strong>${escapeHtml(userName)}</strong></p>`;
        html += '<div class="ad-grupos-manage-list">';

        todosGrupos.forEach(g => {
            const isMember = gruposDoUsuarioSet.has(g.dn.toLowerCase());
            html += `
            <div class="ad-grupo-manage-item ${isMember ? 'is-member' : ''}">
                <div class="ad-grupo-manage-info">
                    <i class="fas fa-users" style="color:${isMember ? '#10B981' : '#94A3B8'}"></i>
                    <span>${escapeHtml(g.nome)}</span>
                    ${g.descricao ? `<small class="text-muted">${escapeHtml(g.descricao)}</small>` : ''}
                </div>
                <button class="btn btn-sm ${isMember ? 'btn-danger-outline' : 'btn-success-outline'}"
                        onclick="toggleGrupo('${escapeAttr(userDn)}', '${escapeAttr(g.dn)}', ${isMember}, '${escapeAttr(userName)}')">
                    ${isMember ? '<i class="fas fa-minus"></i> Remover' : '<i class="fas fa-plus"></i> Adicionar'}
                </button>
            </div>`;
        });

        html += '</div>';

        HelpDesk.showModal('<i class="fas fa-users-cog"></i> Gerenciar Grupos', html, `
            <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>
        `, 'modal-lg');
    });
}

function toggleGrupo(userDn, grupoDn, isRemove, userName) {
    const action = isRemove ? 'remover_grupo' : 'adicionar_grupo';

    HelpDesk.api('POST', '/api/ad.php', { action: action, user_dn: userDn, grupo_dn: grupoDn })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                HelpDesk.closeModal();
                carregarUsuarios();
                setTimeout(() => abrirGerenciarGrupos(userDn, userName), 500);
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

// ===== TOGGLE / DESBLOQUEAR CONTA =====
function toggleConta(dn, habilitar, nome) {
    const acao = habilitar ? 'habilitar' : 'desabilitar';
    if (!confirm(`${habilitar ? 'Habilitar' : '⚠️ Desabilitar'} a conta de "${nome}"?`)) return;

    HelpDesk.api('POST', '/api/ad.php', { action: 'toggle_conta', dn: dn, habilitar: habilitar ? 1 : 0 })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                carregarUsuarios();
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

function desbloquearConta(dn) {
    HelpDesk.api('POST', '/api/ad.php', { action: 'desbloquear_conta', dn: dn })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                carregarUsuarios();
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

// ===== GRUPOS (ABA) =====
function carregarGrupos() {
    const grid = document.getElementById('gruposGrid');
    grid.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>';

    const busca = document.getElementById('buscaGrupoAD')?.value || '';

    HelpDesk.api('GET', '/api/ad.php', { action: 'grupos', busca: busca })
        .then(resp => {
            if (!resp.success) {
                grid.innerHTML = `<div class="ad-error-msg">${escapeHtml(resp.error)}</div>`;
                return;
            }
            gruposLoaded = true;
            gruposCache = resp.data;

            if (!resp.data.length) {
                grid.innerHTML = '<div class="loading-center"><i class="fas fa-users-cog"></i><p>Nenhum grupo encontrado</p></div>';
                return;
            }

            grid.innerHTML = resp.data.map(g => `
                <div class="ad-grupo-card">
                    <div class="ad-grupo-card-header">
                        <div class="ad-grupo-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <h4>${escapeHtml(g.nome)}</h4>
                            <span class="ad-grupo-tipo">${escapeHtml(g.tipo)}</span>
                        </div>
                    </div>
                    ${g.descricao ? `<p class="ad-grupo-desc">${escapeHtml(g.descricao)}</p>` : ''}
                    <div class="ad-grupo-card-footer">
                        <span><i class="fas fa-user"></i> ${g.membros_count} membro(s)</span>
                        <button class="btn-link-sm" onclick="verMembrosGrupo('${escapeAttr(g.dn)}', '${escapeAttr(g.nome)}')">
                            <i class="fas fa-eye"></i> Ver membros
                        </button>
                    </div>
                </div>
            `).join('');
        });
}

function verMembrosGrupo(grupoDn, grupoNome) {
    HelpDesk.api('POST', '/api/ad.php', { action: 'membros_grupo', grupo_dn: grupoDn })
        .then(resp => {
            if (!resp.success) return HelpDesk.toast(resp.error, 'danger');

            let html = '<div class="ad-membros-list">';
            if (!resp.data.length) {
                html += '<p class="text-center text-muted py-3">Nenhum membro neste grupo.</p>';
            } else {
                resp.data.forEach(m => {
                    html += `
                    <div class="ad-membro-item">
                        <div class="ad-membro-avatar"><i class="fas fa-user"></i></div>
                        <span>${escapeHtml(m.nome)}</span>
                    </div>`;
                });
            }
            html += '</div>';

            HelpDesk.showModal(`<i class="fas fa-users"></i> Membros — ${escapeHtml(grupoNome)}`, html, `
                <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>
            `);
        });
}

// ===== OUs =====
function carregarOUs() {
    const container = document.getElementById('ousContainer');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    HelpDesk.api('GET', '/api/ad.php', { action: 'ous' })
        .then(resp => {
            if (!resp.success) {
                container.innerHTML = `<div class="ad-error-msg">${escapeHtml(resp.error)}</div>`;
                return;
            }
            ousLoaded = true;
            ousCache = resp.data;

            if (!resp.data.length) {
                container.innerHTML = '<p class="text-center text-muted py-4">Nenhuma OU encontrada.</p>';
                return;
            }

            container.innerHTML = '<div class="ad-ous-tree">' + resp.data.map(ou => `
                <div class="ad-ou-item" style="padding-left: ${Math.min(ou.nivel * 24, 96)}px">
                    <div class="ad-ou-icon"><i class="fas fa-folder${ou.nivel === 0 ? '-open' : ''}"></i></div>
                    <div class="ad-ou-info">
                        <strong>${escapeHtml(ou.nome)}</strong>
                        ${ou.descricao ? `<small>${escapeHtml(ou.descricao)}</small>` : ''}
                    </div>
                    <div class="ad-ou-meta">
                        <span class="ad-ou-count"><i class="fas fa-cube"></i> ${ou.objetos} objeto(s)</span>
                    </div>
                    <div class="ad-ou-dn" title="${escapeAttr(ou.dn)}"><code>${escapeHtml(ou.dn)}</code></div>
                </div>
            `).join('') + '</div>';
        });
}

// ===== CONFIG =====
function carregarConfig() {
    HelpDesk.api('GET', '/api/ad.php', { action: 'config' })
        .then(resp => {
            if (resp.success) {
                configLoaded = true;
                const d = resp.data;
                document.getElementById('ad_server').value = d.server || '';
                document.getElementById('ad_porta').value = d.porta || '389';
                document.getElementById('ad_base_dn').value = d.base_dn || '';
                document.getElementById('ad_admin_user').value = d.admin_user || '';
                document.getElementById('ad_dominio').value = d.dominio || '';
                document.getElementById('ad_ssl').value = d.ssl || '0';
            }
        });
}

function salvarConfigAD() {
    const form = document.getElementById('formAdConfig');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = 'salvar_config';

    HelpDesk.api('POST', '/api/ad.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

function testarConexaoAD() {
    const result = document.getElementById('adTestResult');
    result.style.display = 'block';
    result.className = 'ad-test-result testing';
    result.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando conexão...';

    // Salvar config primeiro
    const form = document.getElementById('formAdConfig');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'salvar_config';

    HelpDesk.api('POST', '/api/ad.php', data).then(() => {
        HelpDesk.api('POST', '/api/ad.php', { action: 'testar_conexao' })
            .then(resp => {
                if (resp.success) {
                    result.className = 'ad-test-result success';
                    result.innerHTML = '<i class="fas fa-check-circle"></i> ' + escapeHtml(resp.message);
                } else {
                    result.className = 'ad-test-result error';
                    result.innerHTML = '<i class="fas fa-times-circle"></i> ' + escapeHtml(resp.message || resp.error);
                }
            });
    });
}

// ===== HELPERS =====

function sugerirBaseDN(dominio) {
    const sugestao = document.getElementById('baseDnSugestao');
    const input = document.getElementById('ad_base_dn');
    if (!dominio || !dominio.includes('.')) {
        sugestao.textContent = '';
        return;
    }
    const dn = dominio.trim().split('.').map(p => 'DC=' + p).join(',');
    sugestao.innerHTML = `— sugestão: <a href="#" onclick="event.preventDefault(); document.getElementById('ad_base_dn').value='${dn}'; document.getElementById('baseDnSugestao').textContent='✓ aplicado'; return false;">${dn} <i class="fas fa-check" style="font-size:11px"></i></a>`;
    // Auto-preencher se vazio
    if (!input.value) input.value = dn;
}

function ajustarPortaSSL(ssl) {
    const porta = document.getElementById('ad_porta');
    if (ssl === '1' && porta.value === '389') porta.value = '636';
    if (ssl === '0' && porta.value === '636') porta.value = '389';
}

function gerarSenhaAD(inputId) {
    HelpDesk.api('POST', '/api/ad.php', { action: 'gerar_senha' })
        .then(resp => {
            if (resp.success) {
                const input = document.getElementById(inputId);
                input.value = resp.senha;
                input.type = 'text';
                HelpDesk.toast('Senha forte gerada!', 'info');
            }
        });
}

function toggleSenhaInput(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'fas fa-eye';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeAttr(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
