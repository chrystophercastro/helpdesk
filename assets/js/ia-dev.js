/**
 * IA Dev Mode - IDE de Desenvolvimento com IA
 * 
 * Integra com o Chat IA para permitir:
 * - Desenvolvimento interno (Oracle X) com workflow de aprovação
 * - Desenvolvimento de projetos externos standalone
 */

// ===== DEV STATE =====
let iaDevMode = false;
let iaDevProjeto = null;     // projeto selecionado {id, nome, tipo, ...}
let iaDevProjetos = [];      // lista de projetos
let iaDevArquivos = [];      // arquivos do projeto
let iaDevAlteracoes = [];    // alterações pendentes
let iaDevStats = {};
let iaDevFilesFromStream = []; // arquivos parseados do último stream
let iaDevProtectedPaths = []; // cached protected path patterns

// ===== PROTECTED PATHS (client-side check) =====
function loadProtectedPaths() {
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'protected_paths' })
        .then(resp => {
            if (resp.success) iaDevProtectedPaths = resp.data;
        });
}

function isProtectedPathClient(caminho) {
    if (!caminho) return false;
    caminho = caminho.replace(/\\/g, '/').replace(/^\//, '');
    
    for (const pattern of iaDevProtectedPaths) {
        const p = pattern.replace(/\\/g, '/');
        if (p.endsWith('/*')) {
            const prefix = p.slice(0, -2);
            if (caminho.startsWith(prefix + '/') || caminho === prefix) return true;
        } else if (caminho === p) {
            return true;
        }
    }
    return false;
}

// ===== DEV MODE TOGGLE =====
function toggleDevMode(enable) {
    iaDevMode = (enable !== undefined) ? enable : !iaDevMode;
    const layout = document.getElementById('iaLayout') || document.querySelector('.ia-layout');
    const panel = document.getElementById('iaDevPanel');
    const chatTitle = document.getElementById('iaChatTitulo');

    if (iaDevMode) {
        layout.classList.add('dev-mode');
        panel.style.display = 'flex';
        loadDevProjetos();
        loadDevStats();
        loadProtectedPaths();
        // Change welcome screen
        showDevWelcome();
        // Update mode buttons
        document.querySelectorAll('.ia-mode-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.ia-mode-btn[data-mode="dev"]')?.classList.add('active');
    } else {
        layout.classList.remove('dev-mode');
        panel.style.display = 'none';
        document.querySelectorAll('.ia-mode-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.ia-mode-btn[data-mode="chat"]')?.classList.add('active');
        // Restore normal welcome if no conversation
        if (!iaConversaAtual) novaConversa();
    }
}

// ===== LOAD DATA =====
function loadDevProjetos() {
    const baseUrl = document.getElementById('baseUrl').value;
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'projetos' })
        .then(resp => {
            if (!resp.success) return;
            iaDevProjetos = resp.data;
            renderDevProjectSelector();
            // Auto-select first project
            if (iaDevProjetos.length && !iaDevProjeto) {
                selectDevProjeto(iaDevProjetos[0].id);
            }
        });
}

function loadDevStats() {
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'stats' })
        .then(resp => {
            if (!resp.success) return;
            iaDevStats = resp.data;
            renderDevStats();
        });
}

function loadDevArquivos() {
    if (!iaDevProjeto) return;
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'arquivos', projeto_id: iaDevProjeto.id })
        .then(resp => {
            if (!resp.success) return;
            iaDevArquivos = resp.data;
            renderDevFileTree();
        });
}

function loadDevAlteracoes() {
    if (!iaDevProjeto) return;
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'alteracoes', projeto_id: iaDevProjeto.id })
        .then(resp => {
            if (!resp.success) return;
            iaDevAlteracoes = resp.data;
            renderDevApprovals();
            updateApprovalBadge();
        });
}

// ===== PROJECT SELECTOR =====
function renderDevProjectSelector() {
    const container = document.getElementById('iaDevProjectSelect');
    if (!container) return;

    let html = '';
    iaDevProjetos.forEach(p => {
        const icon = p.tipo === 'interno' ? '🏢' : '📁';
        const selected = iaDevProjeto && iaDevProjeto.id === p.id ? 'selected' : '';
        html += `<option value="${p.id}" ${selected}>${icon} ${escapeHtml(p.nome)}</option>`;
    });
    container.innerHTML = html;

    // Update info
    if (iaDevProjeto) {
        const info = document.getElementById('iaDevProjectInfo');
        if (info) {
            const badgeClass = iaDevProjeto.tipo === 'interno' ? 'interno' : 'externo';
            const badgeLabel = iaDevProjeto.tipo === 'interno' ? '🔒 Interno (requer aprovação)' : '📁 Externo';
            info.innerHTML = `
                <span class="proj-badge ${badgeClass}">${badgeLabel}</span>
                ${iaDevProjeto.stack ? `<br><small>Stack: ${escapeHtml(iaDevProjeto.stack)}</small>` : ''}
                ${iaDevProjeto.descricao ? `<br><small>${escapeHtml(iaDevProjeto.descricao.substring(0, 100))}</small>` : ''}
            `;
            info.style.display = 'block';
        }
    }
}

function selectDevProjeto(id) {
    iaDevProjeto = iaDevProjetos.find(p => p.id == id);
    renderDevProjectSelector();
    loadDevArquivos();
    loadDevAlteracoes();

    // Load file tree for internal project
    if (iaDevProjeto && iaDevProjeto.tipo === 'interno') {
        loadDevEstrutura();
    } else if (iaDevProjeto) {
        loadDevArquivosDisco();
    }
}

function onDevProjectChange(el) {
    selectDevProjeto(parseInt(el.value));
}

// ===== FILE TREE =====
function loadDevEstrutura(path) {
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'estrutura', path: path || '' })
        .then(resp => {
            if (!resp.success) return;
            renderDevStructureTree(resp.data);
        });
}

function loadDevArquivosDisco() {
    if (!iaDevProjeto) return;
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'arquivos_disco', projeto_id: iaDevProjeto.id })
        .then(resp => {
            if (!resp.success) return;
            renderDevStructureTree(resp.data);
        });
}

function renderDevFileTree() {
    const container = document.getElementById('iaDevFileList');
    if (!container) return;

    if (!iaDevArquivos.length) {
        container.innerHTML = `
            <div class="ia-dev-empty">
                <i class="fas fa-file-code"></i>
                <p>Nenhum arquivo gerado ainda</p>
                <p>Peça à IA para desenvolver algo!</p>
            </div>`;
        return;
    }

    let html = '';
    iaDevArquivos.forEach(a => {
        const langIcon = getFileIcon(a.linguagem || a.caminho);
        html += `
            <div class="ia-dev-tree-item" onclick="viewDevFile(${a.id})">
                <span class="tree-icon ${langIcon.cls}">${langIcon.icon}</span>
                <span class="tree-name" title="${escapeHtml(a.caminho)}">${escapeHtml(a.caminho)}</span>
                <span class="tree-status ${a.status}">${a.status}</span>
            </div>`;
    });
    container.innerHTML = html;
}

function renderDevStructureTree(items, level) {
    level = level || 0;
    const container = document.getElementById('iaDevStructureTree');
    if (!container && level === 0) return;

    if (level === 0) {
        if (!items.length) {
            container.innerHTML = '<div class="ia-dev-empty"><i class="fas fa-folder-open"></i><p>Projeto vazio</p></div>';
            return;
        }
        container.innerHTML = buildTreeHtml(items, 0);
    }
}

function buildTreeHtml(items, level) {
    let html = '';
    items.forEach(item => {
        const indent = level * 16;
        if (item.type === 'dir') {
            html += `
                <div class="ia-dev-tree-item" style="padding-left:${12 + indent}px" onclick="toggleDevFolder(this)">
                    <span class="tree-icon folder"><i class="fas fa-folder"></i></span>
                    <span class="tree-name">${escapeHtml(item.name)}</span>
                </div>`;
            if (item.children && item.children.length) {
                html += `<div class="ia-dev-subtree">${buildTreeHtml(item.children, level + 1)}</div>`;
            }
        } else {
            const langIcon = getFileIcon(item.ext || item.name);
            html += `
                <div class="ia-dev-tree-item" style="padding-left:${12 + indent}px" 
                     onclick="readDevFile('${escapeHtml(item.path)}')">
                    <span class="tree-icon ${langIcon.cls}">${langIcon.icon}</span>
                    <span class="tree-name">${escapeHtml(item.name)}</span>
                </div>`;
        }
    });
    return html;
}

function toggleDevFolder(el) {
    const subtree = el.nextElementSibling;
    if (subtree && subtree.classList.contains('ia-dev-subtree')) {
        subtree.style.display = subtree.style.display === 'none' ? 'block' : 'none';
        const icon = el.querySelector('.folder i');
        if (icon) {
            icon.className = subtree.style.display === 'none' ? 'fas fa-folder' : 'fas fa-folder-open';
        }
    }
}

function readDevFile(path) {
    HelpDesk.api('GET', '/api/ia-dev.php', { 
        action: 'ler_arquivo', 
        caminho: path, 
        projeto_id: iaDevProjeto ? iaDevProjeto.id : 0 
    }).then(resp => {
        if (!resp.success) return HelpDesk.toast(resp.error, 'error');
        showFileViewerModal(resp.data);
    });
}

function viewDevFile(id) {
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'arquivo', id })
        .then(resp => {
            if (!resp.success) return;
            showFileViewerModal(resp.data);
        });
}

// ===== APPROVALS =====
function renderDevApprovals() {
    const container = document.getElementById('iaDevApprovalList');
    if (!container) return;

    if (!iaDevAlteracoes.length) {
        container.innerHTML = `
            <div class="ia-dev-empty">
                <i class="fas fa-check-double"></i>
                <p>Nenhuma alteração pendente</p>
            </div>`;
        return;
    }

    let html = '';
    iaDevAlteracoes.forEach(alt => {
        const statusIcon = {
            'pendente': 'fa-clock', 'em_teste': 'fa-flask',
            'aprovado': 'fa-check', 'aplicado': 'fa-rocket',
            'rejeitado': 'fa-times', 'revertido': 'fa-undo'
        }[alt.status] || 'fa-question';

        const statusLabel = {
            'pendente': 'Pendente', 'em_teste': 'Em Teste',
            'aprovado': 'Aprovado', 'aplicado': 'Aplicado',
            'rejeitado': 'Rejeitado', 'revertido': 'Revertido'
        }[alt.status] || alt.status;

        html += `
            <div class="ia-dev-approval-card ${alt.status === 'em_teste' ? 'testing' : ''}">
                <div class="ia-dev-approval-header" onclick="viewAlteracao(${alt.id})">
                    <div class="ia-dev-approval-icon ${alt.status}">
                        <i class="fas ${statusIcon}"></i>
                    </div>
                    <div class="ia-dev-approval-info">
                        <div class="title">${escapeHtml(alt.titulo)}</div>
                        <div class="meta">
                            <span><i class="fas fa-file"></i> ${alt.total_arquivos} arquivo(s)</span>
                            <span><i class="fas fa-clock"></i> ${timeAgo(alt.criado_em)}</span>
                            ${alt.status === 'em_teste' ? '<span class="test-badge"><i class="fas fa-flask"></i> Em Teste</span>' : ''}
                        </div>
                    </div>
                </div>
                ${alt.status === 'pendente' ? `
                <div class="ia-dev-approval-actions">
                    <button class="btn btn-sm btn-test" onclick="testarAlteracao(${alt.id})">
                        <i class="fas fa-flask"></i> Testar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="rejeitarAlteracao(${alt.id})">
                        <i class="fas fa-times"></i> Rejeitar
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="viewAlteracao(${alt.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </div>` : ''}
                ${alt.status === 'em_teste' ? `
                <div class="ia-dev-approval-actions">
                    <button class="btn btn-sm btn-success" onclick="aprovarAlteracao(${alt.id})">
                        <i class="fas fa-check"></i> Aprovar
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="cancelarTeste(${alt.id})">
                        <i class="fas fa-undo"></i> Cancelar Teste
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="viewAlteracao(${alt.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </div>` : ''}
                ${alt.status === 'aprovado' ? `
                <div class="ia-dev-approval-actions">
                    <button class="btn btn-sm btn-primary" onclick="aplicarAlteracao(${alt.id})">
                        <i class="fas fa-rocket"></i> Aplicar no Código
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="viewAlteracao(${alt.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </div>` : ''}
                ${alt.status === 'aplicado' ? `
                <div class="ia-dev-approval-actions">
                    <button class="btn btn-sm btn-warning" onclick="reverterAlteracao(${alt.id})">
                        <i class="fas fa-undo"></i> Reverter
                    </button>
                </div>` : ''}
            </div>`;
    });
    container.innerHTML = html;
}

function updateApprovalBadge() {
    const badge = document.getElementById('iaDevApprovalBadge');
    if (badge) {
        const actionable = iaDevAlteracoes.filter(a => a.status === 'pendente' || a.status === 'em_teste').length;
        badge.textContent = actionable;
        badge.style.display = actionable > 0 ? 'inline-flex' : 'none';
    }
    // Also update mode toggle badge
    const modeBadge = document.getElementById('iaDevModeBadge');
    if (modeBadge) {
        const actionable = iaDevAlteracoes.filter(a => a.status === 'pendente' || a.status === 'em_teste').length;
        modeBadge.textContent = actionable;
        modeBadge.style.display = actionable > 0 ? 'inline' : 'none';
    }
}

// ===== APPROVAL ACTIONS =====
function viewAlteracao(id) {
    HelpDesk.api('GET', '/api/ia-dev.php', { action: 'alteracao', id })
        .then(resp => {
            if (!resp.success) return HelpDesk.toast(resp.error, 'error');
            showAlteracaoModal(resp.data);
        });
}

function testarAlteracao(id) {
    if (!confirm('🧪 Deployar esta alteração para teste?\n\nOs arquivos serão aplicados ao código para que você possa testar. Um backup será criado automaticamente.\n\nDepois do teste, você poderá Aprovar (manter) ou Cancelar Teste (reverter).')) return;
    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'testar', id })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('🧪 Alteração deployada para teste! Verifique no sistema e depois aprove ou cancele.', 'success');
                loadDevAlteracoes();
                loadDevArquivos();
                HelpDesk.closeModal();
            } else if (resp.error && resp.error.includes('PROTEGIDO') && window.iaDevUserTipo === 'admin') {
                if (confirm('⚠️ OVERRIDE DE PROTEÇÃO (Admin)\n\n' + resp.error + '\n\nDeseja forçar o override?')) {
                    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'testar', id, force_override: true })
                        .then(r => {
                            if (r.success) {
                                HelpDesk.toast('🔓🧪 Teste deployado com override!', 'success');
                                loadDevAlteracoes(); loadDevArquivos(); HelpDesk.closeModal();
                            } else { HelpDesk.toast(r.error, 'error'); }
                        });
                }
            } else {
                HelpDesk.toast(resp.error, 'error');
            }
        });
}

function cancelarTeste(id) {
    if (!confirm('↩️ Cancelar teste e reverter os arquivos?\n\nTodos os arquivos desta alteração serão revertidos ao estado anterior.')) return;
    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'cancelar_teste', id })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('↩️ Teste cancelado, arquivos revertidos!', 'success');
                loadDevAlteracoes();
                loadDevArquivos();
                HelpDesk.closeModal();
            } else {
                HelpDesk.toast(resp.error, 'error');
            }
        });
}

function aprovarAlteracao(id) {
    if (!confirm('✅ Aprovar esta alteração?')) return;
    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'aprovar', id })
        .then(resp => {
            if (resp.success) {
                const msg = (resp.data && resp.data.from_test) 
                    ? '✅ Aprovado e aplicado! Alteração confirmada no código.' 
                    : '✅ Alteração aprovada!';
                HelpDesk.toast(msg, 'success');
                loadDevAlteracoes();
                loadDevArquivos();
                HelpDesk.closeModal();
            } else {
                HelpDesk.toast(resp.error, 'error');
            }
        });
}

function aplicarAlteracao(id) {
    if (!confirm('⚠️ Aplicar alterações ao código-fonte?\n\nIsso vai modificar arquivos no servidor. Um backup será criado automaticamente.')) return;
    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'aplicar', id })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('🚀 Alterações aplicadas ao código!', 'success');
                loadDevAlteracoes();
                loadDevArquivos();
                HelpDesk.closeModal();
            } else if (resp.error && resp.error.includes('PROTEGIDO') && window.iaDevUserTipo === 'admin') {
                if (confirm('⚠️ OVERRIDE DE PROTEÇÃO (Admin)\n\n' + resp.error + '\n\nDeseja forçar o override?')) {
                    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'aplicar', id, force_override: true })
                        .then(r => {
                            if (r.success) {
                                HelpDesk.toast('🔓🚀 Alterações aplicadas com override!', 'success');
                                loadDevAlteracoes(); loadDevArquivos(); HelpDesk.closeModal();
                            } else { HelpDesk.toast(r.error, 'error'); }
                        });
                }
            } else {
                HelpDesk.toast(resp.error, 'error');
            }
        });
}

function rejeitarAlteracao(id) {
    const notas = prompt('Motivo da rejeição (opcional):');
    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'rejeitar', id, notas })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Alteração rejeitada', 'success');
                loadDevAlteracoes();
                loadDevArquivos();
            }
        });
}

function reverterAlteracao(id) {
    if (!confirm('⚠️ Reverter esta alteração?\n\nOs arquivos serão restaurados ao estado anterior.')) return;
    HelpDesk.api('POST', '/api/ia-dev.php', { action: 'reverter', id })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('↩️ Alteração revertida', 'success');
                loadDevAlteracoes();
                loadDevArquivos();
            }
        });
}

// ===== NEW PROJECT =====
function novoDevProjeto() {
    const html = `
        <form id="formNovoProjeto" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label">Nome do Projeto *</label>
                <input type="text" name="nome" class="form-input" placeholder="Ex: API de Pagamentos" required>
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-input" rows="2" placeholder="O que este projeto faz?"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Stack / Tecnologias</label>
                <input type="text" name="stack" class="form-input" placeholder="Ex: php, react, node">
            </div>
            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-input">
                    <option value="externo">📁 Projeto Externo (standalone)</option>
                </select>
            </div>
        </form>
    `;

    HelpDesk.showModal(
        '<i class="fas fa-plus-circle"></i> Novo Projeto',
        html,
        `<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
         <button class="btn btn-primary" onclick="criarDevProjeto()"><i class="fas fa-rocket"></i> Criar Projeto</button>`
    );
}

function criarDevProjeto() {
    const form = document.getElementById('formNovoProjeto');
    const fd = new FormData(form);
    const data = {
        action: 'novo_projeto',
        nome: fd.get('nome'),
        descricao: fd.get('descricao'),
        stack: fd.get('stack'),
        tipo: fd.get('tipo'),
    };
    if (!data.nome) return HelpDesk.toast('Nome é obrigatório', 'error');

    HelpDesk.api('POST', '/api/ia-dev.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.closeModal();
                HelpDesk.toast('🎉 Projeto criado!', 'success');
                loadDevProjetos();
                setTimeout(() => selectDevProjeto(resp.data.id), 500);
            }
        });
}

// ===== DEV STATS =====
function renderDevStats() {
    const container = document.getElementById('iaDevStatsBar');
    if (!container) return;
    container.innerHTML = `
        <div class="ia-dev-stat">
            <i class="fas fa-folder" style="color:#8B5CF6"></i>
            <strong>${iaDevStats.total_projetos || 0}</strong>
            <span>Projetos</span>
        </div>
        <div class="ia-dev-stat">
            <i class="fas fa-file-code" style="color:#3B82F6"></i>
            <strong>${iaDevStats.total_arquivos || 0}</strong>
            <span>Arquivos</span>
        </div>
        <div class="ia-dev-stat">
            <i class="fas fa-clock" style="color:#FBBF24"></i>
            <strong>${iaDevStats.pendentes || 0}</strong>
            <span>Pendentes</span>
        </div>
        <div class="ia-dev-stat">
            <i class="fas fa-flask" style="color:#A855F7"></i>
            <strong>${iaDevStats.em_teste || 0}</strong>
            <span>Em Teste</span>
        </div>
        <div class="ia-dev-stat">
            <i class="fas fa-rocket" style="color:#22C55E"></i>
            <strong>${iaDevStats.aplicados || 0}</strong>
            <span>Aplicados</span>
        </div>
    `;
}

// ===== DEV WELCOME SCREEN =====
function showDevWelcome() {
    if (iaConversaAtual) return; // Don't override active conversation
    const msgDiv = document.getElementById('iaMessages');
    const projetoNome = iaDevProjeto ? iaDevProjeto.nome : 'um projeto';
    
    msgDiv.innerHTML = `
        <div class="ia-welcome" id="iaWelcome">
            <div class="ia-welcome-icon">
                <i class="fas fa-code" style="color:#8B5CF6"></i>
            </div>
            <h2>Modo Desenvolvedor 🚀</h2>
            <p>Peça para eu criar módulos, APIs, páginas, correções ou projetos inteiros.</p>
            <div class="ia-dev-suggestions">
                <button class="ia-dev-suggestion" onclick="enviarSugestao('Crie um módulo completo de FAQ para o Oracle X, com model, view, controller, migration e API REST')">
                    <i class="fas fa-puzzle-piece"></i>
                    <div class="suggestion-text">
                        <strong>Novo Módulo Oracle X</strong>
                        Criar módulo completo com MVC + API + migration
                    </div>
                </button>
                <button class="ia-dev-suggestion" onclick="enviarSugestao('Crie uma landing page moderna em HTML/CSS/JS com seção hero, features, pricing e contato')">
                    <i class="fas fa-globe"></i>
                    <div class="suggestion-text">
                        <strong>Landing Page</strong>
                        HTML/CSS/JS com design moderno e responsivo
                    </div>
                </button>
                <button class="ia-dev-suggestion" onclick="enviarSugestao('Crie uma API REST em PHP para gerenciamento de tarefas com CRUD completo, autenticação JWT e documentação')">
                    <i class="fas fa-server"></i>
                    <div class="suggestion-text">
                        <strong>API REST</strong>
                        Backend completo com auth e documentação
                    </div>
                </button>
                <button class="ia-dev-suggestion" onclick="enviarSugestao('Analise o código do módulo de chamados do Oracle X e sugira melhorias de performance e segurança')">
                    <i class="fas fa-search-plus"></i>
                    <div class="suggestion-text">
                        <strong>Code Review</strong>
                        Analisar código existente e sugerir melhorias
                    </div>
                </button>
                <button class="ia-dev-suggestion" onclick="enviarSugestao('Crie um app completo de controle financeiro pessoal em React com dashboard, categorias e gráficos')">
                    <i class="fas fa-react"></i>
                    <div class="suggestion-text">
                        <strong>App React</strong>
                        Aplicação frontend com componentes e estado
                    </div>
                </button>
                <button class="ia-dev-suggestion" onclick="enviarSugestao('Corrija e melhore o sistema de notificações do Oracle X, adicionando notificações em tempo real com WebSocket')">
                    <i class="fas fa-wrench"></i>
                    <div class="suggestion-text">
                        <strong>Melhoria Oracle X</strong>
                        Corrigir ou aprimorar funcionalidade existente
                    </div>
                </button>
            </div>
        </div>`;
}

// ===== FILE VIEWER MODAL =====
function showFileViewerModal(arquivo) {
    const lang = arquivo.linguagem || 'text';
    const lines = (arquivo.conteudo || '').split('\n');
    const lineNums = lines.map((_, i) => `<span>${i + 1}</span>`).join('\n');
    
    const html = `
        <div style="display:flex;gap:8px;margin-bottom:12px;font-size:12px;color:#94A3B8">
            <span><i class="fas fa-file-code"></i> ${escapeHtml(arquivo.caminho)}</span>
            <span>•</span>
            <span>${lang}</span>
            <span>•</span>
            <span>${lines.length} linhas</span>
            ${arquivo.tamanho ? `<span>• ${formatBytes(arquivo.tamanho)}</span>` : ''}
        </div>
        <div class="ia-dev-file-card-code" style="max-height:65vh;border:1px solid var(--border-color,#334155);border-radius:8px;background:#0F172A">
            <pre style="display:flex"><code class="line-numbers" style="color:#475569;border-right:1px solid #334155;padding-right:12px;text-align:right;user-select:none">${lineNums}</code><code style="flex:1;padding-left:12px">${escapeHtml(arquivo.conteudo || '')}</code></pre>
        </div>`;

    HelpDesk.showModal(
        `<i class="fas fa-file-code"></i> ${escapeHtml(arquivo.caminho)}`,
        html,
        `<button class="btn btn-secondary" onclick="copyToClipboard(\`${btoa(unescape(encodeURIComponent(arquivo.conteudo || '')))}\`)"><i class="fas fa-copy"></i> Copiar</button>
         <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>`,
        'modal-xl'
    );
}

function showAlteracaoModal(alt) {
    // ===== FULL CODE REVIEW PANEL =====
    const arquivos = alt.arquivos || [];
    const statusInfo = {
        pendente:  { icon: 'fa-clock',  color: '#FBBF24', label: '⏳ Pendente',  bg: 'rgba(251,191,36,0.1)' },
        em_teste:  { icon: 'fa-flask',  color: '#A855F7', label: '🧪 Em Teste',  bg: 'rgba(168,85,247,0.1)' },
        aprovado:  { icon: 'fa-check',  color: '#22C55E', label: '✅ Aprovado',  bg: 'rgba(34,197,94,0.1)' },
        aplicado:  { icon: 'fa-rocket', color: '#3B82F6', label: '🚀 Aplicado',  bg: 'rgba(59,130,246,0.1)' },
        rejeitado: { icon: 'fa-times',  color: '#EF4444', label: '❌ Rejeitado', bg: 'rgba(239,68,68,0.1)' },
        revertido: { icon: 'fa-undo',   color: '#F59E0B', label: '↩️ Revertido', bg: 'rgba(245,158,11,0.1)' },
    };
    const si = statusInfo[alt.status] || statusInfo.pendente;

    // Build file list sidebar
    let fileListHtml = '';
    arquivos.forEach((a, i) => {
        const langIcon = getFileIcon(a.caminho);
        const fileName = a.caminho.split('/').pop();
        const folderPath = a.caminho.split('/').slice(0, -1).join('/');
        const actionLabel = { criar: 'NOVO', modificar: 'MOD', deletar: 'DEL' }[a.acao] || '';
        const actionClass = a.acao || 'criar';
        const lines = (a.conteudo || '').split('\n').length;
        fileListHtml += `
            <div class="cr-file-item ${i === 0 ? 'active' : ''}" onclick="crSelectFile(${i})" data-idx="${i}">
                <div class="cr-file-item-icon">${langIcon.icon}</div>
                <div class="cr-file-item-info">
                    <div class="cr-file-item-name">${escapeHtml(fileName)}</div>
                    <div class="cr-file-item-path">${escapeHtml(folderPath || '/')}</div>
                </div>
                <div class="cr-file-item-badges">
                    <span class="cr-action-badge ${actionClass}">${actionLabel}</span>
                    <span class="cr-lines-badge">${lines}L</span>
                </div>
            </div>`;
    });

    // Build action buttons based on status
    let actionBtns = '';
    if (alt.status === 'pendente') {
        actionBtns = `
            <button class="btn btn-test btn-sm" onclick="testarAlteracao(${alt.id})"><i class="fas fa-flask"></i> Testar</button>
            <button class="btn btn-sm btn-danger" onclick="rejeitarAlteracao(${alt.id})"><i class="fas fa-times"></i> Rejeitar</button>`;
    } else if (alt.status === 'em_teste') {
        actionBtns = `
            <button class="btn btn-sm btn-success" onclick="aprovarAlteracao(${alt.id})"><i class="fas fa-check"></i> Aprovar</button>
            <button class="btn btn-sm btn-warning" onclick="cancelarTeste(${alt.id})"><i class="fas fa-undo"></i> Cancelar Teste</button>`;
    } else if (alt.status === 'aprovado') {
        actionBtns = `
            <button class="btn btn-sm btn-primary" onclick="aplicarAlteracao(${alt.id})"><i class="fas fa-rocket"></i> Aplicar</button>`;
    } else if (alt.status === 'aplicado') {
        actionBtns = `
            <button class="btn btn-sm btn-warning" onclick="reverterAlteracao(${alt.id})"><i class="fas fa-undo"></i> Reverter</button>`;
    }

    // Detect testable URLs from file paths
    let testLinksHtml = '';
    if (alt.status === 'em_teste' && alt.projeto_tipo === 'interno') {
        const baseUrl = document.getElementById('baseUrl')?.value || '';
        const viewFiles = arquivos.filter(a => a.caminho.match(/app\/views\/(\w[\w-]*)\/index\.php/));
        const apiFiles = arquivos.filter(a => a.caminho.match(/api\/[\w-]+\.php/));
        
        if (viewFiles.length || apiFiles.length) {
            testLinksHtml = '<div class="cr-test-links"><div class="cr-test-links-title"><i class="fas fa-external-link-alt"></i> Testar no Navegador</div>';
            viewFiles.forEach(f => {
                const match = f.caminho.match(/app\/views\/([\w-]+)\/index\.php/);
                if (match) {
                    const page = match[1];
                    testLinksHtml += `<a href="${baseUrl}/index.php?page=${page}" target="_blank" class="cr-test-link">
                        <i class="fas fa-globe"></i> Abrir página: ${page}
                    </a>`;
                }
            });
            apiFiles.forEach(f => {
                const match = f.caminho.match(/api\/([\w-]+)\.php/);
                if (match) {
                    testLinksHtml += `<a href="${baseUrl}/api/${match[1]}.php" target="_blank" class="cr-test-link">
                        <i class="fas fa-server"></i> API: ${match[1]}
                    </a>`;
                }
            });
            testLinksHtml += '</div>';
        }
    }

    const html = `
    <div class="cr-panel" id="crPanel">
        <!-- Header -->
        <div class="cr-header">
            <div class="cr-header-left">
                <div class="cr-title">
                    <i class="fas fa-code-branch" style="color:#8B5CF6"></i>
                    <span>${escapeHtml(alt.titulo)}</span>
                </div>
                <div class="cr-meta">
                    <span class="cr-status-badge" style="background:${si.bg};color:${si.color}">
                        <i class="fas ${si.icon}"></i> ${si.label}
                    </span>
                    <span><i class="fas fa-user"></i> ${escapeHtml(alt.solicitante_nome || 'IA')}</span>
                    <span><i class="fas fa-calendar"></i> ${new Date(alt.criado_em).toLocaleString('pt-BR')}</span>
                    <span><i class="fas fa-file-code"></i> ${arquivos.length} arquivo(s)</span>
                    ${alt.notas_revisao ? `<span><i class="fas fa-comment"></i> ${escapeHtml(alt.notas_revisao)}</span>` : ''}
                </div>
            </div>
            <div class="cr-header-actions">
                ${actionBtns}
                <button class="btn btn-sm btn-secondary" onclick="HelpDesk.closeModal()"><i class="fas fa-times"></i></button>
            </div>
        </div>

        ${testLinksHtml}

        <!-- Body: Sidebar + Code Viewer -->
        <div class="cr-body">
            <!-- File list sidebar -->
            <div class="cr-sidebar">
                <div class="cr-sidebar-title">
                    <i class="fas fa-folder-open"></i> Arquivos
                    <span class="cr-file-count">${arquivos.length}</span>
                </div>
                <div class="cr-file-list" id="crFileList">
                    ${fileListHtml}
                </div>
            </div>

            <!-- Code viewer -->
            <div class="cr-viewer">
                <div class="cr-viewer-toolbar" id="crViewerToolbar">
                    <div class="cr-viewer-path" id="crViewerPath">Selecione um arquivo</div>
                    <div class="cr-viewer-actions">
                        <button class="cr-toolbar-btn" id="crBtnViewCode" onclick="crSwitchView('code')" title="Código">
                            <i class="fas fa-code"></i> Código
                        </button>
                        <button class="cr-toolbar-btn" id="crBtnViewDiff" onclick="crSwitchView('diff')" title="Diff">
                            <i class="fas fa-columns"></i> Diff
                        </button>
                        <button class="cr-toolbar-btn" onclick="crCopyCurrentFile()" title="Copiar">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="cr-code-area" id="crCodeArea">
                    <div class="cr-empty-viewer">
                        <i class="fas fa-hand-pointer"></i>
                        <p>Selecione um arquivo à esquerda para visualizar</p>
                    </div>
                </div>
            </div>
        </div>
    </div>`;

    // Store files globally for the review panel
    window._crFiles = arquivos;
    window._crCurrentIdx = 0;
    window._crCurrentView = 'code';
    window._crAltId = alt.id;
    window._crAltStatus = alt.status;

    HelpDesk.showModal('', html, '', 'cr-modal');

    // Auto-select first file
    if (arquivos.length > 0) {
        setTimeout(() => crSelectFile(0), 100);
    }
}

// ===== CODE REVIEW FUNCTIONS =====
function crSelectFile(idx) {
    const files = window._crFiles || [];
    if (!files[idx]) return;
    
    window._crCurrentIdx = idx;
    const file = files[idx];

    // Update sidebar active state
    document.querySelectorAll('.cr-file-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`.cr-file-item[data-idx="${idx}"]`)?.classList.add('active');

    // Update toolbar path
    const langIcon = getFileIcon(file.caminho);
    const actionBadge = { criar: 'NOVO', modificar: 'MODIFICADO', deletar: 'DELETAR' }[file.acao] || '';
    const actionClass = file.acao || 'criar';
    document.getElementById('crViewerPath').innerHTML = `
        ${langIcon.icon} <span>${escapeHtml(file.caminho)}</span>
        <span class="cr-action-badge ${actionClass}" style="margin-left:8px">${actionBadge}</span>`;

    // Show/hide diff button based on whether original exists
    const diffBtn = document.getElementById('crBtnViewDiff');
    if (diffBtn) {
        diffBtn.style.display = (file.conteudo_original || file.acao === 'modificar') ? 'inline-flex' : 'none';
    }

    // Render current view
    crSwitchView(window._crCurrentView);
}

function crSwitchView(view) {
    window._crCurrentView = view;
    const file = (window._crFiles || [])[window._crCurrentIdx];
    if (!file) return;

    // Update toolbar active buttons
    document.getElementById('crBtnViewCode')?.classList.toggle('active', view === 'code');
    document.getElementById('crBtnViewDiff')?.classList.toggle('active', view === 'diff');

    const area = document.getElementById('crCodeArea');
    if (!area) return;

    if (view === 'diff' && file.conteudo_original) {
        area.innerHTML = crRenderDiff(file.conteudo_original, file.conteudo || '');
    } else {
        area.innerHTML = crRenderCode(file.conteudo || '', file.linguagem || file.caminho);
    }
}

function crRenderCode(content, langOrPath) {
    const lines = content.split('\n');
    const totalDigits = String(lines.length).length;
    
    let html = '<div class="cr-code-block"><table class="cr-code-table"><tbody>';
    lines.forEach((line, i) => {
        const num = String(i + 1).padStart(totalDigits, ' ');
        html += `<tr class="cr-code-line">
            <td class="cr-line-num">${num}</td>
            <td class="cr-line-code"><pre>${escapeHtml(line) || ' '}</pre></td>
        </tr>`;
    });
    html += '</tbody></table></div>';

    // Summary bar
    const lang = typeof langOrPath === 'string' ? (getFileIcon(langOrPath).cls || langOrPath.split('.').pop()) : '';
    const size = new Blob([content]).size;
    html += `<div class="cr-code-summary">
        <span>${lang.replace('file-', '').toUpperCase() || 'TEXT'}</span>
        <span>${lines.length} linhas</span>
        <span>${formatBytes(size)}</span>
    </div>`;

    return html;
}

function crRenderDiff(original, modified) {
    const oldLines = original.split('\n');
    const newLines = modified.split('\n');
    
    // Simple diff: compare line by line
    const maxLen = Math.max(oldLines.length, newLines.length);
    let html = '<div class="cr-diff-block"><table class="cr-diff-table"><tbody>';
    
    let oldIdx = 0, newIdx = 0;
    
    // Build a basic LCS-style diff
    const diffLines = crComputeDiff(oldLines, newLines);
    
    diffLines.forEach(d => {
        if (d.type === 'same') {
            html += `<tr class="cr-diff-line cr-diff-same">
                <td class="cr-diff-num">${d.oldNum}</td>
                <td class="cr-diff-num">${d.newNum}</td>
                <td class="cr-diff-marker"> </td>
                <td class="cr-diff-code"><pre>${escapeHtml(d.text)}</pre></td>
            </tr>`;
        } else if (d.type === 'remove') {
            html += `<tr class="cr-diff-line cr-diff-remove">
                <td class="cr-diff-num">${d.oldNum}</td>
                <td class="cr-diff-num"></td>
                <td class="cr-diff-marker">−</td>
                <td class="cr-diff-code"><pre>${escapeHtml(d.text)}</pre></td>
            </tr>`;
        } else if (d.type === 'add') {
            html += `<tr class="cr-diff-line cr-diff-add">
                <td class="cr-diff-num"></td>
                <td class="cr-diff-num">${d.newNum}</td>
                <td class="cr-diff-marker">+</td>
                <td class="cr-diff-code"><pre>${escapeHtml(d.text)}</pre></td>
            </tr>`;
        }
    });

    html += '</tbody></table></div>';
    
    // Diff summary
    const added = diffLines.filter(d => d.type === 'add').length;
    const removed = diffLines.filter(d => d.type === 'remove').length;
    html += `<div class="cr-code-summary">
        <span style="color:#4ADE80">+${added} adicionadas</span>
        <span style="color:#F87171">−${removed} removidas</span>
        <span>${diffLines.filter(d => d.type === 'same').length} inalteradas</span>
    </div>`;

    return html;
}

function crComputeDiff(oldLines, newLines) {
    // Simple patience-like diff using LCS
    const result = [];
    const m = oldLines.length, n = newLines.length;
    
    // For small files, use full LCS. For large, use a simpler approach
    if (m + n > 2000) {
        // Fast line-by-line comparison for large files
        let i = 0, j = 0;
        while (i < m || j < n) {
            if (i < m && j < n && oldLines[i] === newLines[j]) {
                result.push({ type: 'same', text: oldLines[i], oldNum: i+1, newNum: j+1 });
                i++; j++;
            } else if (j < n && (i >= m || !newLines.slice(j).includes(oldLines[i]))) {
                result.push({ type: 'add', text: newLines[j], newNum: j+1 });
                j++;
            } else if (i < m) {
                result.push({ type: 'remove', text: oldLines[i], oldNum: i+1 });
                i++;
            }
        }
        return result;
    }
    
    // Standard LCS DP
    const dp = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));
    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] = oldLines[i-1] === newLines[j-1] 
                ? dp[i-1][j-1] + 1 
                : Math.max(dp[i-1][j], dp[i][j-1]);
        }
    }
    
    // Backtrack
    const lcs = [];
    let i = m, j = n;
    while (i > 0 && j > 0) {
        if (oldLines[i-1] === newLines[j-1]) {
            lcs.unshift({ oldIdx: i-1, newIdx: j-1 });
            i--; j--;
        } else if (dp[i-1][j] > dp[i][j-1]) {
            i--;
        } else {
            j--;
        }
    }
    
    // Build diff from LCS
    let oi = 0, ni = 0;
    lcs.forEach(match => {
        while (oi < match.oldIdx) {
            result.push({ type: 'remove', text: oldLines[oi], oldNum: oi+1 });
            oi++;
        }
        while (ni < match.newIdx) {
            result.push({ type: 'add', text: newLines[ni], newNum: ni+1 });
            ni++;
        }
        result.push({ type: 'same', text: oldLines[oi], oldNum: oi+1, newNum: ni+1 });
        oi++; ni++;
    });
    while (oi < m) {
        result.push({ type: 'remove', text: oldLines[oi], oldNum: oi+1 });
        oi++;
    }
    while (ni < n) {
        result.push({ type: 'add', text: newLines[ni], newNum: ni+1 });
        ni++;
    }
    
    return result;
}

function crCopyCurrentFile() {
    const file = (window._crFiles || [])[window._crCurrentIdx];
    if (!file) return;
    navigator.clipboard.writeText(file.conteudo || '');
    HelpDesk.toast('📋 Copiado!', 'success');
}

// ===== PARSE DEV FILES FROM STREAM =====
function parseDevFilesFromContent(content) {
    const files = [];
    // Match: ```lang[DEV_FILE:path]\ncontent\n```
    const regex = /```(\w*)\[DEV_FILE:([^\]]+)\]\s*\n([\s\S]*?)```/g;
    let match;
    while ((match = regex.exec(content)) !== null) {
        files.push({
            linguagem: match[1] || detectLangFromExt(match[2]),
            caminho: match[2].trim(),
            conteudo: match[3].trimEnd(),
            acao: 'criar',
        });
    }
    return files;
}

function detectLangFromExt(path) {
    const ext = path.split('.').pop().toLowerCase();
    const map = {
        php: 'php', js: 'javascript', ts: 'typescript', css: 'css',
        html: 'html', sql: 'sql', json: 'json', py: 'python',
        jsx: 'jsx', tsx: 'tsx', vue: 'vue', md: 'markdown'
    };
    return map[ext] || ext;
}

/**
 * Render DEV_FILE blocks as styled file cards in the chat
 * Called after stream finishes to replace raw code blocks
 */
function renderDevFileCards(contentEl, fullContent) {
    if (!iaDevMode) return;
    
    iaDevFilesFromStream = parseDevFilesFromContent(fullContent);
    if (!iaDevFilesFromStream.length) return;

    const isAdmin = (window.iaDevUserTipo === 'admin');

    // Replace each file block with a styled card
    let html = contentEl.innerHTML;
    
    iaDevFilesFromStream.forEach((file, idx) => {
        const langIcon = getFileIcon(file.caminho);
        const lines = file.conteudo.split('\n').length;
        const size = new Blob([file.conteudo]).size;
        const isProtected = isProtectedPathClient(file.caminho);
        const protectedBadge = isProtected 
            ? '<span class="file-action-badge deletar" title="Módulo protegido — admin pode forçar override"><i class="fas fa-shield-alt"></i> PROTEGIDO</span>' 
            : '';
        // Admin pode salvar protegidos (com confirmação), outros não
        const canSave = !isProtected || isAdmin;
        const saveTitle = isProtected 
            ? (isAdmin ? 'Salvar com Override (admin)' : 'Módulo protegido — somente admin') 
            : 'Salvar no projeto';
        const saveDisabled = canSave ? '' : ' disabled style="opacity:0.4;cursor:not-allowed"';
        const saveFn = isProtected && isAdmin 
            ? `saveDevFileForce(${idx})` 
            : `saveDevFile(${idx})`;
        const saveIcon = isProtected && isAdmin ? 'fa-unlock' : 'fa-save';
        const saveLabel = isProtected && isAdmin ? 'Override' : 'Salvar';
        
        const cardHtml = `
            <div class="ia-dev-file-card ${isProtected ? 'ia-dev-file-protected' : ''}" id="devFileCard${idx}">
                <div class="ia-dev-file-card-header">
                    <span class="file-lang-icon">${langIcon.icon}</span>
                    <span class="file-path">${escapeHtml(file.caminho)}</span>
                    ${protectedBadge}
                    <span class="file-action-badge criar">NOVO</span>
                </div>
                <div class="ia-dev-file-card-code">
                    <pre><code>${escapeHtml(file.conteudo)}</code></pre>
                </div>
                <div class="ia-dev-file-card-footer">
                    <div class="file-meta">
                        <span>${file.linguagem}</span>
                        <span>${lines} linhas</span>
                        <span>${formatBytes(size)}</span>
                    </div>
                    <div class="file-actions">
                        <button onclick="copyFileContent(${idx})" title="Copiar"><i class="fas fa-copy"></i> Copiar</button>
                        <button class="btn-apply" onclick="${saveFn}" title="${saveTitle}"${saveDisabled}><i class="fas ${saveIcon}"></i> ${saveLabel}</button>
                    </div>
                </div>
            </div>`;
        
        // Try to find and replace the original code block in the rendered HTML
        // Look for <code> blocks that contain the file content
        const escapedPath = escapeHtml(file.caminho);
        // The marked.js would have rendered ```lang[DEV_FILE:path] as a code block
        // We need a reliable way to find it
        const codeBlockRegex = new RegExp(
            '<pre><code[^>]*>[\\s\\S]*?' + escapeRegex(escapeHtml(file.conteudo.substring(0, 50))) + '[\\s\\S]*?</code></pre>',
            'i'
        );
        
        if (codeBlockRegex.test(html)) {
            html = html.replace(codeBlockRegex, cardHtml);
        }
    });

    contentEl.innerHTML = html;

    // Add "Salvar Todos" button if there are multiple files
    if (iaDevFilesFromStream.length > 1) {
        const saveAllBtn = document.createElement('div');
        saveAllBtn.style.cssText = 'text-align:center;margin:12px 0';
        saveAllBtn.innerHTML = `
            <button class="btn btn-primary btn-sm" onclick="saveAllDevFiles()" style="background:linear-gradient(135deg,#8B5CF6,#6D28D9)">
                <i class="fas fa-save"></i> Salvar Todos (${iaDevFilesFromStream.length} arquivos)
            </button>`;
        contentEl.appendChild(saveAllBtn);
    } else if (iaDevFilesFromStream.length === 1) {
        // Already has save button per card
    }
}

function copyFileContent(idx) {
    const file = iaDevFilesFromStream[idx];
    if (!file) return;
    navigator.clipboard.writeText(file.conteudo);
    HelpDesk.toast('📋 Copiado!', 'success');
}

function saveDevFile(idx) {
    const file = iaDevFilesFromStream[idx];
    if (!file || !iaDevProjeto) {
        HelpDesk.toast('Selecione um projeto primeiro', 'error');
        return;
    }

    HelpDesk.api('POST', '/api/ia-dev.php', {
        action: 'salvar_arquivos',
        projeto_id: iaDevProjeto.id,
        conversa_id: iaConversaAtual,
        titulo: 'Arquivo: ' + file.caminho,
        arquivos: [file],
    }).then(resp => {
        if (resp.success) {
            markDevFileCardSaved(idx);
            loadDevArquivos();
            loadDevAlteracoes();
        } else {
            HelpDesk.toast(resp.error, 'error');
        }
    });
}

/**
 * Salvar arquivo protegido com force override (admin only)
 * Pede confirmação explícita antes de prosseguir
 */
function saveDevFileForce(idx) {
    const file = iaDevFilesFromStream[idx];
    if (!file || !iaDevProjeto) {
        HelpDesk.toast('Selecione um projeto primeiro', 'error');
        return;
    }
    if (window.iaDevUserTipo !== 'admin') {
        HelpDesk.toast('Somente admin pode forçar override', 'error');
        return;
    }

    // Confirmação explícita com modal
    const body = `
        <div style="text-align:center;padding:10px">
            <i class="fas fa-exclamation-triangle" style="font-size:48px;color:#F59E0B;margin-bottom:16px"></i>
            <h4 style="margin-bottom:8px">Arquivo Protegido</h4>
            <p>Você está prestes a salvar alterações em um <strong>módulo protegido</strong> do sistema:</p>
            <code style="display:block;background:var(--bg-tertiary);padding:8px;border-radius:6px;margin:12px 0;color:#EF4444">${escapeHtml(file.caminho)}</code>
            <p style="color:#F59E0B;font-size:0.9em"><i class="fas fa-shield-alt"></i> Isso pode impactar funcionalidades existentes. Um backup será criado automaticamente.</p>
        </div>`;
    const footer = `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-danger" onclick="confirmForceOverride(${idx})" style="background:#EF4444">
            <i class="fas fa-unlock"></i> Confirmar Override
        </button>`;
    HelpDesk.showModal('⚠️ Override de Proteção', body, footer, 'sm');
}

function confirmForceOverride(idx) {
    HelpDesk.closeModal();
    const file = iaDevFilesFromStream[idx];

    HelpDesk.api('POST', '/api/ia-dev.php', {
        action: 'salvar_arquivos',
        projeto_id: iaDevProjeto.id,
        conversa_id: iaConversaAtual,
        titulo: '⚠️ Override: ' + file.caminho,
        arquivos: [file],
        force_override: true,
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('🔓 Arquivo protegido salvo com override!', 'success');
            markDevFileCardSaved(idx);
            loadDevArquivos();
            loadDevAlteracoes();
        } else {
            HelpDesk.toast(resp.error, 'error');
        }
    });
}

/**
 * Helper: marca visualmente um card de arquivo como salvo
 */
function markDevFileCardSaved(idx) {
    const isInterno = iaDevProjeto?.tipo === 'interno';
    const msg = isInterno 
        ? '📝 Arquivo salvo para aprovação!' 
        : '✅ Arquivo salvo no projeto!';
    HelpDesk.toast(msg, 'success');
    
    const card = document.getElementById('devFileCard' + idx);
    if (card) {
        card.classList.remove('ia-dev-file-protected');
        const badge = card.querySelector('.file-action-badge');
        if (badge) {
            badge.className = 'file-action-badge ' + (isInterno ? 'modificar' : 'criar');
            badge.textContent = isInterno ? 'PENDENTE' : 'SALVO';
        }
        const saveBtn = card.querySelector('.btn-apply');
        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fas fa-check"></i> Salvo';
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.6';
        }
    }
}

function saveAllDevFiles() {
    if (!iaDevProjeto || !iaDevFilesFromStream.length) return;

    const isAdmin = (window.iaDevUserTipo === 'admin');
    const hasProtected = iaDevFilesFromStream.some(f => isProtectedPathClient(f.caminho));

    // Se tem arquivos protegidos e não é admin, filtrar
    if (hasProtected && !isAdmin) {
        const allowed = iaDevFilesFromStream.filter(f => !isProtectedPathClient(f.caminho));
        if (!allowed.length) {
            HelpDesk.toast('Todos os arquivos são protegidos. Somente admin pode forçar.', 'error');
            return;
        }
        HelpDesk.toast(`⚠️ ${iaDevFilesFromStream.length - allowed.length} arquivo(s) protegido(s) ignorado(s)`, 'warning');
        iaDevFilesFromStream = allowed;
    }

    const titulo = prompt('Título da alteração:', 'Gerado pela IA - ' + new Date().toLocaleString('pt-BR'));
    if (!titulo) return;

    // Se admin e tem protegidos, pedir confirmação
    let forceOverride = false;
    if (hasProtected && isAdmin) {
        const protectedNames = iaDevFilesFromStream.filter(f => isProtectedPathClient(f.caminho)).map(f => f.caminho);
        if (!confirm(`⚠️ OVERRIDE DE PROTEÇÃO\n\nOs seguintes arquivos são módulos protegidos:\n• ${protectedNames.join('\n• ')}\n\nDeseja forçar o override como admin?`)) {
            return;
        }
        forceOverride = true;
    }

    HelpDesk.api('POST', '/api/ia-dev.php', {
        action: 'salvar_arquivos',
        projeto_id: iaDevProjeto.id,
        conversa_id: iaConversaAtual,
        titulo: (forceOverride ? '⚠️ Override: ' : '') + titulo,
        arquivos: iaDevFilesFromStream,
        force_override: forceOverride,
    }).then(resp => {
        if (resp.success) {
            const isInterno = iaDevProjeto.tipo === 'interno';
            const msg = isInterno 
                ? `📝 ${iaDevFilesFromStream.length} arquivos enviados para aprovação!` 
                : `✅ ${iaDevFilesFromStream.length} arquivos salvos!`;
            HelpDesk.toast(msg, 'success');
            
            // Disable all save buttons
            iaDevFilesFromStream.forEach((_, i) => {
                const card = document.getElementById('devFileCard' + i);
                if (card) {
                    const saveBtn = card.querySelector('.btn-apply');
                    if (saveBtn) {
                        saveBtn.innerHTML = '<i class="fas fa-check"></i> Salvo';
                        saveBtn.disabled = true;
                        saveBtn.style.opacity = '0.6';
                    }
                }
            });
            
            loadDevArquivos();
            loadDevAlteracoes();
        } else {
            HelpDesk.toast(resp.error, 'error');
        }
    });
}

// ===== DEV TAB SWITCHING =====
function switchDevTab(tabName, btnEl) {
    // Update tab buttons
    document.querySelectorAll('.ia-dev-tab').forEach(t => t.classList.remove('active'));
    if (btnEl) {
        btnEl.classList.add('active');
    } else {
        document.querySelector(`.ia-dev-tab[data-tab="${tabName}"]`)?.classList.add('active');
    }
    // Update tab panes
    document.querySelectorAll('.ia-dev-tab-pane').forEach(c => c.classList.remove('active'));
    const pane = document.getElementById('iaDevTab_' + tabName);
    if (pane) pane.classList.add('active');
}

// ===== DOWNLOAD PROJECT =====
function downloadDevProjeto() {
    if (!iaDevProjeto) return;
    // Create a form submission for download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = document.getElementById('baseUrl').value + '/api/ia-dev.php';
    form.innerHTML = `
        <input type="hidden" name="action" value="download">
        <input type="hidden" name="projeto_id" value="${iaDevProjeto.id}">
    `;
    document.body.appendChild(form);
    form.submit();
    form.remove();
}

// ===== UTILITY =====
function getFileIcon(pathOrLang) {
    const ext = (pathOrLang || '').split('.').pop().toLowerCase();
    const map = {
        php: { icon: '<i class="fab fa-php"></i>', cls: 'file-php' },
        js: { icon: '<i class="fab fa-js"></i>', cls: 'file-js' },
        javascript: { icon: '<i class="fab fa-js"></i>', cls: 'file-js' },
        ts: { icon: '<i class="fab fa-js"></i>', cls: 'file-js' },
        css: { icon: '<i class="fab fa-css3-alt"></i>', cls: 'file-css' },
        scss: { icon: '<i class="fab fa-sass"></i>', cls: 'file-css' },
        html: { icon: '<i class="fab fa-html5"></i>', cls: 'file-html' },
        htm: { icon: '<i class="fab fa-html5"></i>', cls: 'file-html' },
        sql: { icon: '<i class="fas fa-database"></i>', cls: 'file-sql' },
        json: { icon: '<i class="fas fa-brackets-curly"></i>', cls: 'file-json' },
        md: { icon: '<i class="fab fa-markdown"></i>', cls: '' },
        py: { icon: '<i class="fab fa-python"></i>', cls: '' },
        python: { icon: '<i class="fab fa-python"></i>', cls: '' },
        jsx: { icon: '<i class="fab fa-react"></i>', cls: 'file-js' },
        tsx: { icon: '<i class="fab fa-react"></i>', cls: 'file-js' },
        vue: { icon: '<i class="fab fa-vuejs"></i>', cls: '' },
        yaml: { icon: '<i class="fas fa-file-alt"></i>', cls: '' },
        yml: { icon: '<i class="fas fa-file-alt"></i>', cls: '' },
        sh: { icon: '<i class="fas fa-terminal"></i>', cls: '' },
        env: { icon: '<i class="fas fa-key"></i>', cls: '' },
    };
    return map[ext] || map[pathOrLang] || { icon: '<i class="fas fa-file"></i>', cls: '' };
}

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1024 / 1024).toFixed(1) + ' MB';
}

function timeAgo(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'agora';
    if (diff < 3600) return Math.floor(diff / 60) + 'min';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    return Math.floor(diff / 86400) + 'd';
}

function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function copyToClipboard(b64) {
    try {
        const text = decodeURIComponent(escape(atob(b64)));
        navigator.clipboard.writeText(text);
        HelpDesk.toast('📋 Copiado!', 'success');
    } catch(e) {
        HelpDesk.toast('Erro ao copiar', 'error');
    }
}
