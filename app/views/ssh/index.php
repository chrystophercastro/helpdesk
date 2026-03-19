<?php
/**
 * View: Terminal SSH
 * Gerenciamento de servidores e terminal SSH remoto
 */
$user = currentUser();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-terminal" style="margin-right:8px;color:#22C55E"></i> Terminal SSH</h1>
        <p class="page-subtitle">Gerenciamento de servidores e execução remota de comandos</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="testarTodosSSH()">
            <i class="fas fa-satellite-dish"></i> Testar Todos
        </button>
        <button class="btn btn-primary" onclick="abrirModalServidor()">
            <i class="fas fa-plus"></i> Novo Servidor
        </button>
    </div>
</div>

<!-- Banner SSH2 -->
<div class="ssh-banner" id="ssh2Banner" style="display:none">
    <div class="card" style="border-left:4px solid #F59E0B;background:#FFFBEB">
        <div class="card-body" style="padding:14px 18px;display:flex;align-items:flex-start;gap:12px">
            <i class="fas fa-exclamation-triangle" style="color:#F59E0B;font-size:20px;margin-top:2px"></i>
            <div style="flex:1">
                <strong id="ssh2BannerTitle">Extensão SSH2</strong>
                <p id="ssh2BannerMsg" style="margin:4px 0 0;font-size:13px;color:#92400E"></p>
                <div id="ssh2BannerSteps" style="margin-top:6px;font-size:12px;color:#78350F"></div>
                <!-- Auto Install Area -->
                <div id="ssh2AutoInstall" style="display:none;margin-top:10px">
                    <div id="ssh2InstallBtns">
                        <button class="btn btn-sm btn-warning" onclick="instalarSSH2Auto()" id="btnInstalarSSH2"
                                style="font-weight:600">
                            <i class="fas fa-download"></i> Instalar Automaticamente
                        </button>
                        <span style="font-size:11px;color:#92400E;margin-left:8px">
                            PHP <span id="ssh2PhpVer"></span> — Requer permissão de administrador
                        </span>
                    </div>
                    <!-- Progress bar -->
                    <div id="ssh2InstallProgress" style="display:none;margin-top:8px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="flex:1;height:6px;background:#FDE68A;border-radius:3px;overflow:hidden">
                                <div id="ssh2ProgressBar" style="height:100%;width:0%;background:#F59E0B;border-radius:3px;transition:width .3s"></div>
                            </div>
                            <span id="ssh2ProgressText" style="font-size:11px;color:#92400E;white-space:nowrap">Iniciando...</span>
                        </div>
                        <div id="ssh2InstallLog" style="margin-top:6px;font-size:11px;color:#78350F;max-height:120px;overflow-y:auto;font-family:monospace;background:#FEF3C7;padding:6px 10px;border-radius:4px;display:none"></div>
                    </div>
                    <!-- Success / restart -->
                    <div id="ssh2InstallSuccess" style="display:none;margin-top:8px">
                        <div style="display:flex;align-items:center;gap:8px;color:#047857;font-weight:600">
                            <i class="fas fa-check-circle"></i>
                            <span id="ssh2SuccessMsg">Instalado com sucesso!</span>
                        </div>
                        <button class="btn btn-sm btn-success" onclick="reiniciarApache()" id="btnRestartApache" style="margin-top:6px">
                            <i class="fas fa-sync-alt"></i> Reiniciar Apache
                        </button>
                        <span style="font-size:11px;color:#065F46;margin-left:8px">
                            Necessário para ativar a extensão
                        </span>
                    </div>
                </div>
            </div>
            <button class="btn-icon" onclick="this.closest('.ssh-banner').style.display='none'" style="margin-left:auto">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="rede-stats" id="sshStats">
    <div class="rede-stat-card stat-total">
        <div class="rede-stat-icon"><i class="fas fa-server"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="sshStatTotal">—</span>
            <span class="rede-stat-label">Servidores</span>
        </div>
    </div>
    <div class="rede-stat-card stat-online">
        <div class="rede-stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="sshStatOnline">—</span>
            <span class="rede-stat-label">Online</span>
        </div>
    </div>
    <div class="rede-stat-card stat-offline">
        <div class="rede-stat-icon"><i class="fas fa-times-circle"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="sshStatOffline">—</span>
            <span class="rede-stat-label">Offline</span>
        </div>
    </div>
    <div class="rede-stat-card stat-unknown">
        <div class="rede-stat-icon"><i class="fas fa-layer-group"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="sshStatGrupos">—</span>
            <span class="rede-stat-label">Grupos</span>
        </div>
    </div>
</div>

<!-- Abas -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="servidores" onclick="switchTabSSH('servidores')">
        <i class="fas fa-server"></i> Servidores
    </button>
    <button class="ad-tab" data-tab="terminal" onclick="switchTabSSH('terminal')">
        <i class="fas fa-terminal"></i> Terminal
    </button>
</div>

<!-- ==================== ABA: SERVIDORES ==================== -->
<div class="ad-tab-content active" id="tab-servidores">
    <div class="card mb-4">
        <div class="card-body">
            <div class="ad-filters">
                <div class="ad-filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="buscaServidor" class="form-input" placeholder="Buscar por nome, host, usuário..."
                           onkeydown="if(event.key==='Enter') carregarServidores()">
                </div>
                <select id="filtroGrupo" class="form-select" style="width:180px" onchange="carregarServidores()">
                    <option value="">Todos os grupos</option>
                </select>
                <select id="filtroStatusSSH" class="form-select" style="width:150px" onchange="carregarServidores()">
                    <option value="">Todos</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="desconhecido">Desconhecido</option>
                </select>
                <button class="btn btn-outline" onclick="carregarServidores()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="rede-grid" id="servidoresGrid">
        <div class="loading-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Carregando servidores...</p>
        </div>
    </div>
</div>

<!-- ==================== ABA: TERMINAL ==================== -->
<div class="ad-tab-content" id="tab-terminal">
    <!-- Barra de conexão -->
    <div class="card mb-4">
        <div class="card-body" style="padding:12px 18px">
            <div class="ssh-conn-bar">
                <div class="ssh-conn-select">
                    <label style="font-size:13px;font-weight:600;margin-right:8px;white-space:nowrap">
                        <i class="fas fa-server"></i> Servidor:
                    </label>
                    <select id="sshServidorSelect" class="form-select" style="min-width:280px" onchange="onServidorChange()">
                        <option value="">— Selecione um servidor —</option>
                    </select>
                </div>
                <div class="ssh-conn-status" id="sshConnStatus">
                    <span class="rede-status-dot rede-unknown"></span>
                    <span id="sshConnLabel">Desconectado</span>
                </div>
                <div class="ssh-conn-actions">
                    <button class="btn btn-sm btn-secondary" id="btnTestarConn" onclick="testarConexaoSSH()" disabled>
                        <i class="fas fa-plug"></i> Testar
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="abrirPainelServidor()" id="btnPainel" disabled>
                        <i class="fas fa-tachometer-alt"></i> Painel
                    </button>
                    <!-- Sudo Toggle -->
                    <div class="ssh-sudo-toggle" style="display:flex;align-items:center;gap:6px;margin-left:8px;border-left:1px solid #E2E8F0;padding-left:10px">
                        <label class="switch-sm" title="Executar comandos como root (sudo)" style="cursor:pointer">
                            <input type="checkbox" id="sshSudoToggle" onchange="toggleSudo()">
                            <span class="switch-slider-sm"></span>
                        </label>
                        <span style="font-size:12px;font-weight:600;color:#64748B;user-select:none;cursor:pointer" onclick="document.getElementById('sshSudoToggle').click()">
                            <i class="fas fa-shield-alt"></i> sudo
                        </span>
                    </div>
                </div>
            </div>
            <!-- Sudo password bar (aparece quando toggle ativado) -->
            <div id="sshSudoBar" style="display:none;padding:6px 18px 0;border-top:1px solid #F1F5F9">
                <div style="display:flex;align-items:center;gap:8px">
                    <i class="fas fa-key" style="color:#F59E0B;font-size:12px"></i>
                    <span style="font-size:11px;color:#64748B;white-space:nowrap">Senha sudo:</span>
                    <input type="password" id="sshSudoPassword" class="form-input" 
                           style="height:28px;font-size:12px;max-width:220px;padding:2px 8px" 
                           placeholder="Deixe vazio para usar a senha SSH">
                    <span style="font-size:10px;color:#94A3B8">Todos os comandos serão executados como <b style="color:#EF4444">root</b></span>
                </div>
            </div>
        </div>
    </div>

    <div class="ssh-terminal-layout">
        <!-- Sidebar: Comandos rápidos -->
        <div class="ssh-quick-cmds" id="sshQuickCmds">
            <div class="ssh-quick-header">
                <h4><i class="fas fa-bolt"></i> Comandos Rápidos</h4>
            </div>
            <div class="ssh-quick-list" id="sshQuickList">
                <div class="loading-center py-3">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
        </div>

        <!-- Terminal -->
        <div class="ssh-terminal-wrapper">
            <div class="ssh-terminal" id="sshTerminal">
                <div class="ssh-terminal-header">
                    <div class="ssh-terminal-dots">
                        <span class="ssh-terminal-dot red"></span>
                        <span class="ssh-terminal-dot yellow"></span>
                        <span class="ssh-terminal-dot green"></span>
                    </div>
                    <span class="ssh-terminal-title" id="sshTerminalTitle">Terminal SSH</span>
                    <div class="ssh-terminal-actions">
                        <button class="ssh-term-btn" onclick="limparTerminal()" title="Limpar terminal">
                            <i class="fas fa-eraser"></i>
                        </button>
                        <button class="ssh-term-btn" onclick="verHistoricoSSH()" title="Histórico de comandos" id="btnHistorico" disabled>
                            <i class="fas fa-history"></i>
                        </button>
                    </div>
                </div>
                <div class="ssh-terminal-body" id="sshTerminalBody">
                    <div class="ssh-welcome">
                        <pre style="color:#22C55E;font-size:12px;line-height:1.3">
   _____ _____ _    _ 
  / ____/ ____| |  | |
 | (___| (___ | |__| |
  \___ \\___ \|  __  |
  ____) |___) | |  | |
 |_____/_____/|_|  |_|
                        </pre>
                        <p style="color:#94A3B8;margin-top:8px">Selecione um servidor e clique em "Testar" para conectar.</p>
                        <p style="color:#64748B;font-size:12px">Use os comandos rápidos na barra lateral ou digite diretamente abaixo.</p>
                    </div>
                </div>
                <div class="ssh-terminal-input" id="sshInputBar">
                    <span class="ssh-prompt" id="sshPrompt">$</span>
                    <input type="text" class="ssh-input" id="sshInput" placeholder="Digite um comando..."
                           onkeydown="handleTerminalKey(event)" disabled autocomplete="off" spellcheck="false">
                    <button class="ssh-send-btn" id="sshSendBtn" onclick="executarComandoSSH()" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
// ===== STATE =====
let currentServerId = null;
let currentServerInfo = null;
let sshHistory = [];
let historyIndex = -1;
let servidoresCache = [];
let sudoMode = false;

// ===== TABS =====
function switchTabSSH(tab) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');

    if (tab === 'terminal') {
        carregarComandosSalvos();
        document.getElementById('sshInput').focus();
    }
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    carregarEstatisticasSSH();
    carregarServidores();
    carregarGrupos();
    checkSSH2();
});

function checkSSH2() {
    HelpDesk.api('GET', '/api/ssh.php', { action: 'check_ssh2' })
        .then(resp => {
            if (resp.success && !resp.data.available) {
                const banner = document.getElementById('ssh2Banner');
                banner.style.display = 'block';
                document.getElementById('ssh2BannerTitle').textContent = resp.data.instructions.title;
                document.getElementById('ssh2BannerMsg').textContent =
                    'A extensão SSH2 não está instalada. O sistema tentará usar o SSH nativo como fallback.';
                const steps = resp.data.instructions.steps.map((s, i) => `${i + 1}. ${s}`).join('<br>');
                document.getElementById('ssh2BannerSteps').innerHTML = steps;

                // Mostrar botão de auto instalação se disponível
                if (resp.data.instructions.auto_install) {
                    document.getElementById('ssh2AutoInstall').style.display = 'block';
                    document.getElementById('ssh2PhpVer').textContent = resp.data.php_version;
                }
            }
        });
}

function instalarSSH2Auto() {
    const btn = document.getElementById('btnInstalarSSH2');
    const progress = document.getElementById('ssh2InstallProgress');
    const progressBar = document.getElementById('ssh2ProgressBar');
    const progressText = document.getElementById('ssh2ProgressText');
    const logDiv = document.getElementById('ssh2InstallLog');
    const btnsDiv = document.getElementById('ssh2InstallBtns');

    // Confirmar
    if (!confirm('Instalar extensão SSH2 automaticamente?\n\nO sistema irá:\n1. Detectar sua versão do PHP\n2. Baixar php_ssh2.dll do PECL\n3. Copiar DLLs para o diretório do PHP\n4. Configurar php.ini\n\nRequer Apache rodando como administrador.')) {
        return;
    }

    // UI de progresso
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Instalando...';
    progress.style.display = 'block';
    logDiv.style.display = 'block';
    logDiv.innerHTML = '';

    // Simular progresso visual
    let progStep = 0;
    const progSteps = [
        { pct: 10, msg: 'Detectando versão do PHP...' },
        { pct: 25, msg: 'Verificando permissões...' },
        { pct: 40, msg: 'Baixando DLLs do PECL...' },
        { pct: 70, msg: 'Extraindo e copiando arquivos...' },
        { pct: 85, msg: 'Configurando php.ini...' },
    ];

    const progTimer = setInterval(() => {
        if (progStep < progSteps.length) {
            const s = progSteps[progStep];
            progressBar.style.width = s.pct + '%';
            progressText.textContent = s.msg;
            logDiv.innerHTML += `<div>→ ${s.msg}</div>`;
            logDiv.scrollTop = logDiv.scrollHeight;
            progStep++;
        }
    }, 800);

    HelpDesk.api('POST', '/api/ssh.php', { action: 'install_ssh2' })
        .then(resp => {
            clearInterval(progTimer);

            if (resp.success || (resp.log && resp.log.length)) {
                // Mostrar log real
                logDiv.innerHTML = '';
                (resp.log || []).forEach(l => {
                    const icon = l.startsWith('✓') ? '✅' : l.startsWith('✗') ? '❌' : '📋';
                    logDiv.innerHTML += `<div>${icon} ${l}</div>`;
                });
                logDiv.scrollTop = logDiv.scrollHeight;
            }

            if (resp.success) {
                progressBar.style.width = '100%';
                progressBar.style.background = '#10B981';
                progressText.textContent = 'Concluído!';

                // Mostrar área de sucesso
                btnsDiv.style.display = 'none';
                document.getElementById('ssh2InstallSuccess').style.display = 'block';
                document.getElementById('ssh2SuccessMsg').textContent = resp.message;

                // Atualizar banner visual
                const card = document.querySelector('#ssh2Banner .card');
                card.style.borderLeftColor = '#10B981';
                card.style.background = '#ECFDF5';
                document.querySelector('#ssh2Banner .fa-exclamation-triangle').className = 'fas fa-check-circle';
                document.querySelector('#ssh2Banner .fa-check-circle').style.color = '#10B981';
                document.getElementById('ssh2BannerTitle').textContent = 'SSH2 Instalado!';
                document.getElementById('ssh2BannerMsg').textContent = resp.message;
                document.getElementById('ssh2BannerSteps').style.display = 'none';

                HelpDesk.toast('SSH2 instalado com sucesso! Reinicie o Apache.', 'success');
            } else {
                progressBar.style.width = '100%';
                progressBar.style.background = '#EF4444';
                progressText.textContent = 'Falha na instalação';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download"></i> Tentar Novamente';
                HelpDesk.toast(resp.message || 'Erro na instalação', 'error');
            }
        })
        .catch(err => {
            clearInterval(progTimer);
            progressBar.style.width = '100%';
            progressBar.style.background = '#EF4444';
            progressText.textContent = 'Erro na instalação';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download"></i> Tentar Novamente';
            logDiv.innerHTML += `<div>❌ Erro: ${err.message || err}</div>`;
            HelpDesk.toast('Erro ao instalar SSH2: ' + (err.message || err), 'error');
        });
}

function reiniciarApache() {
    const btn = document.getElementById('btnRestartApache');

    if (!confirm('Reiniciar o Apache?\n\nIsso irá interromper brevemente todas as conexões ativas.')) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reiniciando...';

    HelpDesk.api('POST', '/api/ssh.php', { action: 'restart_apache' })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Apache reiniciado! Recarregando página...', 'success');
                // Aguardar Apache voltar e recarregar
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Reiniciar Apache';
                HelpDesk.toast(resp.message || 'Erro ao reiniciar Apache. Reinicie manualmente pelo XAMPP.', 'warning');

                // Mostrar log se houver
                const logDiv = document.getElementById('ssh2InstallLog');
                if (resp.log && resp.log.length) {
                    logDiv.style.display = 'block';
                    resp.log.forEach(l => {
                        logDiv.innerHTML += `<div>🔄 ${l}</div>`;
                    });
                    logDiv.scrollTop = logDiv.scrollHeight;
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Reiniciar Apache';
            HelpDesk.toast('Reinicie o Apache manualmente pelo XAMPP Control Panel.', 'warning');
        });
}

// ===== ESTATÍSTICAS =====
function carregarEstatisticasSSH() {
    HelpDesk.api('GET', '/api/ssh.php', { action: 'estatisticas' })
        .then(resp => {
            if (!resp.success) return;
            document.getElementById('sshStatTotal').textContent = resp.data.total;
            document.getElementById('sshStatOnline').textContent = resp.data.online;
            document.getElementById('sshStatOffline').textContent = resp.data.offline;
            document.getElementById('sshStatGrupos').textContent = resp.data.grupos;
        });
}

// ===== GRUPOS =====
function carregarGrupos() {
    HelpDesk.api('GET', '/api/ssh.php', { action: 'grupos' })
        .then(resp => {
            if (!resp.success) return;
            const select = document.getElementById('filtroGrupo');
            const current = select.value;
            select.innerHTML = '<option value="">Todos os grupos</option>';
            resp.data.forEach(g => {
                select.innerHTML += `<option value="${escapeHtml(g)}">${escapeHtml(g)}</option>`;
            });
            select.value = current;
        });
}

// ===== SERVIDORES =====
function carregarServidores() {
    const grid = document.getElementById('servidoresGrid');
    grid.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>';

    const params = {
        action: 'listar',
        busca: document.getElementById('buscaServidor')?.value || '',
        grupo: document.getElementById('filtroGrupo')?.value || '',
        status: document.getElementById('filtroStatusSSH')?.value || '',
    };

    HelpDesk.api('GET', '/api/ssh.php', params)
        .then(resp => {
            if (!resp.success) {
                grid.innerHTML = `<div class="ad-error-msg">${escapeHtml(resp.error)}</div>`;
                return;
            }

            servidoresCache = resp.data;

            // Atualizar select do terminal
            atualizarSelectServidores(resp.data);

            if (!resp.data.length) {
                grid.innerHTML = `<div class="loading-center">
                    <i class="fas fa-server" style="font-size:40px;color:#CBD5E1"></i>
                    <p>Nenhum servidor cadastrado</p>
                    <button class="btn btn-primary btn-sm mt-3" onclick="abrirModalServidor()">
                        <i class="fas fa-plus"></i> Cadastrar Servidor
                    </button>
                </div>`;
                return;
            }

            // Agrupar por grupo
            const grupos = {};
            resp.data.forEach(s => {
                const g = s.grupo || 'Sem Grupo';
                if (!grupos[g]) grupos[g] = [];
                grupos[g].push(s);
            });

            let html = '';
            Object.entries(grupos).forEach(([grupo, servers]) => {
                html += `<div class="ssh-grupo-header"><i class="fas fa-folder"></i> ${escapeHtml(grupo)} (${servers.length})</div>`;
                html += '<div class="rede-grid">';
                servers.forEach(s => {
                    const statusClass = s.ultimo_status === 'online' ? 'rede-online' : s.ultimo_status === 'offline' ? 'rede-offline' : 'rede-unknown';
                    const statusLabel = s.ultimo_status === 'online' ? 'Online' : s.ultimo_status === 'offline' ? 'Offline' : 'Desconhecido';
                    const ultimoAcesso = s.ultimo_acesso ? timeAgo(s.ultimo_acesso) : 'Nunca';
                    const osIcon = getOSIcon(s.sistema_operacional);

                    html += `
                    <div class="rede-card ${statusClass}" data-id="${s.id}">
                        <div class="rede-card-header">
                            <div class="rede-card-icon"><i class="fas ${osIcon}"></i></div>
                            <div class="rede-card-status">
                                <span class="rede-status-dot ${statusClass}"></span>
                                <span>${statusLabel}</span>
                            </div>
                        </div>
                        <div class="rede-card-body">
                            <h4>${escapeHtml(s.nome)}</h4>
                            <div class="rede-card-ip"><code>${escapeHtml(s.usuario)}@${escapeHtml(s.host)}:${s.porta}</code></div>
                            <div class="rede-card-meta">
                                ${s.sistema_operacional ? `<span><i class="fas fa-laptop-code"></i> ${escapeHtml(s.sistema_operacional)}</span>` : ''}
                                ${s.descricao ? `<span title="${escapeAttr(s.descricao)}"><i class="fas fa-info-circle"></i> ${escapeHtml(s.descricao.substring(0, 30))}${s.descricao.length > 30 ? '...' : ''}</span>` : ''}
                            </div>
                            <div class="rede-card-ping-info">
                                <span><i class="fas fa-${s.metodo_auth === 'key' ? 'key' : 'lock'}"></i> ${s.metodo_auth === 'key' ? 'Chave' : 'Senha'}</span>
                                <span><i class="fas fa-clock"></i> ${ultimoAcesso}</span>
                            </div>
                        </div>
                        <div class="rede-card-actions">
                            <button class="btn btn-sm btn-secondary" onclick="abrirTerminalServidor(${s.id})" title="Abrir Terminal">
                                <i class="fas fa-terminal"></i> Terminal
                            </button>
                            <button class="btn-icon" onclick="abrirPainelServidorId(${s.id})" title="Painel do Sistema">
                                <i class="fas fa-tachometer-alt"></i>
                            </button>
                            <button class="btn-icon" onclick="abrirModalServidor(${s.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['tipo'] === 'admin'): ?>
                            <button class="btn-icon text-danger" onclick="excluirServidor(${s.id}, '${escapeAttr(s.nome)}')" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>`;
                });
                html += '</div>';
            });

            grid.innerHTML = html;
        });
}

function atualizarSelectServidores(servidores) {
    const select = document.getElementById('sshServidorSelect');
    const current = select.value;
    select.innerHTML = '<option value="">— Selecione um servidor —</option>';

    const grupos = {};
    servidores.forEach(s => {
        const g = s.grupo || 'Sem Grupo';
        if (!grupos[g]) grupos[g] = [];
        grupos[g].push(s);
    });

    Object.entries(grupos).forEach(([grupo, servers]) => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = grupo;
        servers.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `${s.nome} (${s.usuario}@${s.host})`;
            opt.dataset.nome = s.nome;
            opt.dataset.user = s.usuario;
            opt.dataset.host = s.host;
            optgroup.appendChild(opt);
        });
        select.appendChild(optgroup);
    });

    if (current) select.value = current;
}

function getOSIcon(os) {
    if (!os) return 'fa-server';
    const lower = os.toLowerCase();
    if (lower.includes('ubuntu') || lower.includes('debian') || lower.includes('linux') || lower.includes('centos') || lower.includes('redhat') || lower.includes('fedora') || lower.includes('suse')) return 'fa-linux';
    if (lower.includes('windows')) return 'fa-windows';
    if (lower.includes('mac') || lower.includes('darwin')) return 'fa-apple';
    return 'fa-server';
}

// ===== MODAL SERVIDOR =====
function abrirModalServidor(id = null) {
    const isEdit = !!id;
    const titulo = isEdit ? 'Editar Servidor' : 'Novo Servidor SSH';

    const html = `
    <form id="formServidor" class="form-grid">
        ${isEdit ? `<input type="hidden" name="id" value="${id}">` : ''}
        <div class="form-group">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" id="sNome" class="form-input" required placeholder="Ex: Servidor Web Produção">
        </div>
        <div class="form-group">
            <label class="form-label">Host (IP ou Hostname) *</label>
            <input type="text" name="host" id="sHost" class="form-input" required placeholder="192.168.1.100 ou server.example.com">
        </div>
        <div class="form-group">
            <label class="form-label">Porta</label>
            <input type="number" name="porta" id="sPorta" class="form-input" value="22" min="1" max="65535">
        </div>
        <div class="form-group">
            <label class="form-label">Usuário *</label>
            <input type="text" name="usuario" id="sUsuario" class="form-input" required placeholder="root">
        </div>
        <div class="form-group">
            <label class="form-label">Método de Autenticação</label>
            <select name="metodo_auth" id="sMetodo" class="form-select" onchange="toggleAuthFields()">
                <option value="password">Senha</option>
                <option value="key">Chave Privada</option>
            </select>
        </div>
        <div class="form-group" id="grupoSenha">
            <label class="form-label">${isEdit ? 'Senha (deixe vazio para manter)' : 'Senha *'}</label>
            <input type="password" name="credencial" id="sCredencial" class="form-input" placeholder="••••••••" ${!isEdit ? 'required' : ''}>
        </div>
        <div class="form-group col-span-2" id="grupoChave" style="display:none">
            <label class="form-label">${isEdit ? 'Chave Privada (deixe vazio para manter)' : 'Chave Privada *'}</label>
            <textarea name="credencial" id="sChavePrivada" class="form-input" rows="4" placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;..." style="font-family:monospace;font-size:12px" disabled></textarea>
        </div>
        <div class="form-group" id="grupoPassphrase" style="display:none">
            <label class="form-label">Passphrase (opcional)</label>
            <input type="password" name="passphrase" id="sPassphrase" class="form-input" placeholder="Se a chave tiver passphrase">
        </div>
        <div class="form-group">
            <label class="form-label">Grupo</label>
            <input type="text" name="grupo" id="sGrupo" class="form-input" placeholder="Ex: Produção, Desenvolvimento" value="Geral" list="gruposList">
            <datalist id="gruposList"></datalist>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" id="sDescricao" class="form-input" rows="2" placeholder="Descrição ou observações sobre o servidor..."></textarea>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Tags</label>
            <input type="text" name="tags" id="sTags" class="form-input" placeholder="web, nginx, produção (separar por vírgula)">
        </div>
    </form>`;

    HelpDesk.showModal(`<i class="fas fa-${isEdit ? 'edit' : 'plus'}"></i> ${titulo}`, html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarServidor(${isEdit})"><i class="fas fa-save"></i> Salvar</button>
    `, 'modal-lg');

    // Preencher datalist de grupos
    const dl = document.getElementById('gruposList');
    HelpDesk.api('GET', '/api/ssh.php', { action: 'grupos' }).then(resp => {
        if (resp.success) resp.data.forEach(g => {
            const opt = document.createElement('option');
            opt.value = g;
            dl.appendChild(opt);
        });
    });

    if (isEdit) {
        HelpDesk.api('GET', '/api/ssh.php', { action: 'servidor', id: id })
            .then(resp => {
                if (resp.success) {
                    const s = resp.data;
                    document.getElementById('sNome').value = s.nome || '';
                    document.getElementById('sHost').value = s.host || '';
                    document.getElementById('sPorta').value = s.porta || 22;
                    document.getElementById('sUsuario').value = s.usuario || '';
                    document.getElementById('sMetodo').value = s.metodo_auth || 'password';
                    document.getElementById('sGrupo').value = s.grupo || 'Geral';
                    document.getElementById('sDescricao').value = s.descricao || '';
                    document.getElementById('sTags').value = s.tags || '';
                    toggleAuthFields();
                }
            });
    }
}

function toggleAuthFields() {
    const metodo = document.getElementById('sMetodo').value;
    const grupoSenha = document.getElementById('grupoSenha');
    const grupoChave = document.getElementById('grupoChave');
    const grupoPassphrase = document.getElementById('grupoPassphrase');
    const inputSenha = document.getElementById('sCredencial');
    const inputChave = document.getElementById('sChavePrivada');

    if (metodo === 'key') {
        grupoSenha.style.display = 'none';
        grupoChave.style.display = 'block';
        grupoPassphrase.style.display = 'block';
        inputSenha.disabled = true;
        inputSenha.name = '';
        inputChave.disabled = false;
        inputChave.name = 'credencial';
    } else {
        grupoSenha.style.display = 'block';
        grupoChave.style.display = 'none';
        grupoPassphrase.style.display = 'none';
        inputSenha.disabled = false;
        inputSenha.name = 'credencial';
        inputChave.disabled = true;
        inputChave.name = '';
    }
}

function salvarServidor(isEdit) {
    const form = document.getElementById('formServidor');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = isEdit ? 'atualizar' : 'criar';

    const btn = document.querySelector('.modal-footer .btn-primary');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...'; }

    HelpDesk.api('POST', '/api/ssh.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                HelpDesk.closeModal();
                carregarServidores();
                carregarEstatisticasSSH();
                carregarGrupos();
            } else {
                HelpDesk.toast(resp.error, 'danger');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Salvar'; }
            }
        });
}

function excluirServidor(id, nome) {
    if (!confirm(`Excluir o servidor "${nome}"?\nO histórico de comandos será perdido.`)) return;

    HelpDesk.api('POST', '/api/ssh.php', { action: 'excluir', id: id })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                carregarServidores();
                carregarEstatisticasSSH();
                carregarGrupos();
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

// ===== TESTAR CONEXÃO =====
function testarTodosSSH() {
    HelpDesk.toast('Testando todos os servidores...', 'info');
    HelpDesk.api('POST', '/api/ssh.php', { action: 'testar_todos' })
        .then(resp => {
            if (resp.success) {
                const on = resp.data.filter(s => s.status === 'online').length;
                const off = resp.data.filter(s => s.status === 'offline').length;
                HelpDesk.toast(`Concluído! 🟢 ${on} online, 🔴 ${off} offline`, on > 0 ? 'success' : 'warning');
                carregarServidores();
                carregarEstatisticasSSH();
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

function testarConexaoSSH() {
    const id = document.getElementById('sshServidorSelect').value;
    if (!id) return;

    const statusEl = document.getElementById('sshConnStatus');
    const dot = statusEl.querySelector('.rede-status-dot');
    const label = document.getElementById('sshConnLabel');

    dot.className = 'rede-status-dot rede-unknown';
    label.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';

    HelpDesk.api('POST', '/api/ssh.php', { action: 'testar', id: id })
        .then(resp => {
            if (resp.success) {
                dot.className = 'rede-status-dot rede-online';
                label.textContent = `Online (${resp.data.latencia}ms)`;
                currentServerId = parseInt(id);
                currentServerInfo = resp.data;

                // Habilitar terminal
                document.getElementById('sshInput').disabled = false;
                document.getElementById('sshSendBtn').disabled = false;
                document.getElementById('btnPainel').disabled = false;
                document.getElementById('btnHistorico').disabled = false;

                // Atualizar prompt
                const opt = document.getElementById('sshServidorSelect').selectedOptions[0];
                const user = opt?.dataset?.user || 'user';
                const host = opt?.dataset?.host || 'server';
                const prompt = document.getElementById('sshPrompt');
                if (sudoMode) {
                    prompt.textContent = '#';
                    prompt.style.color = '#EF4444';
                } else {
                    prompt.textContent = `${user}@${host}:~$`;
                    prompt.style.color = '';
                }
                document.getElementById('sshTerminalTitle').textContent = `Terminal — ${resp.data.nome}`;

                terminalAddLine(`Conectado a ${resp.data.nome} (${resp.data.host}:${resp.data.porta}) — latência: ${resp.data.latencia}ms`, 'info');

                document.getElementById('sshInput').focus();
            } else {
                dot.className = 'rede-status-dot rede-offline';
                label.textContent = 'Offline';
                HelpDesk.toast(resp.error, 'danger');
                terminalAddLine(`Erro: ${resp.error}`, 'error');
            }
        })
        .catch(err => {
            dot.className = 'rede-status-dot rede-offline';
            label.textContent = 'Erro';
            HelpDesk.toast('Falha ao testar conexão', 'danger');
        });
}

function onServidorChange() {
    const id = document.getElementById('sshServidorSelect').value;
    const btns = ['btnTestarConn', 'btnPainel', 'btnHistorico'];

    if (id) {
        document.getElementById('btnTestarConn').disabled = false;
        // Desabilitar terminal até testar
        document.getElementById('sshInput').disabled = true;
        document.getElementById('sshSendBtn').disabled = true;
        document.getElementById('btnPainel').disabled = true;
        document.getElementById('btnHistorico').disabled = true;

        currentServerId = null;
        currentServerInfo = null;

        const dot = document.getElementById('sshConnStatus').querySelector('.rede-status-dot');
        dot.className = 'rede-status-dot rede-unknown';
        document.getElementById('sshConnLabel').textContent = 'Clique em Testar';
        document.getElementById('sshPrompt').textContent = '$';
        document.getElementById('sshTerminalTitle').textContent = 'Terminal SSH';
    } else {
        btns.forEach(b => document.getElementById(b).disabled = true);
        document.getElementById('sshInput').disabled = true;
        document.getElementById('sshSendBtn').disabled = true;
    }
}

// ===== ABRIR TERMINAL DO CARD =====
function abrirTerminalServidor(id) {
    switchTabSSH('terminal');
    document.getElementById('sshServidorSelect').value = id;
    onServidorChange();
    // Auto testar
    setTimeout(() => testarConexaoSSH(), 200);
}

// ===== TERMINAL =====
const DANGEROUS_PATTERNS = [
    { pattern: /rm\s+(-[rf]+\s+)?\/($|\s)/, msg: 'rm no diretório raiz' },
    { pattern: /\b(shutdown|poweroff|init\s+0|halt)\b/, msg: 'Desligamento do servidor' },
    { pattern: /\b(reboot|init\s+6)\b/, msg: 'Reinicialização do servidor' },
    { pattern: /\b(mkfs|fdisk|parted)\b/, msg: 'Formatação de disco' },
    { pattern: /\bdd\s+if=/, msg: 'Escrita direta em disco' },
    { pattern: /:\(\)\{/, msg: 'Fork bomb' },
];

function executarComandoSSH() {
    const input = document.getElementById('sshInput');
    const cmd = input.value.trim();
    if (!cmd || !currentServerId) return;

    // Comandos locais
    if (cmd === 'clear' || cmd === 'cls') {
        limparTerminal();
        input.value = '';
        return;
    }

    if (cmd === 'help') {
        terminalAddLine('$ help', 'command');
        terminalAddLine('Comandos locais disponíveis:', 'info');
        terminalAddLine('  clear/cls  — Limpar terminal', 'output');
        terminalAddLine('  help       — Mostrar esta ajuda', 'output');
        terminalAddLine('  exit       — Desconectar', 'output');
        terminalAddLine('Use os comandos rápidos na barra lateral para ações comuns.', 'info');
        input.value = '';
        return;
    }

    if (cmd === 'exit') {
        terminalAddLine('$ exit', 'command');
        terminalAddLine('Sessão encerrada.', 'info');
        currentServerId = null;
        input.disabled = true;
        document.getElementById('sshSendBtn').disabled = true;
        const dot = document.getElementById('sshConnStatus').querySelector('.rede-status-dot');
        dot.className = 'rede-status-dot rede-unknown';
        document.getElementById('sshConnLabel').textContent = 'Desconectado';
        input.value = '';
        return;
    }

    // Verificar comandos perigosos
    for (const d of DANGEROUS_PATTERNS) {
        if (d.pattern.test(cmd)) {
            if (!confirm(`⚠️ Comando potencialmente perigoso!\n\n${d.msg}\n\nComando: ${cmd}\n\nDeseja continuar?`)) {
                input.value = '';
                return;
            }
            break;
        }
    }

    input.value = '';
    sshHistory.unshift(cmd);
    historyIndex = -1;

    // Mostrar comando
    const prompt = document.getElementById('sshPrompt').textContent;
    terminalAddLine(`${prompt} ${cmd}`, 'command');

    // Loading
    const loadingId = 'loading-' + Date.now();
    terminalAddLine('<i class="fas fa-spinner fa-spin"></i> Executando...', 'loading', loadingId);

    // Desabilitar input temporariamente
    input.disabled = true;
    document.getElementById('sshSendBtn').disabled = true;

    HelpDesk.api('POST', '/api/ssh.php', {
        action: 'executar',
        servidor_id: currentServerId,
        comando: cmd,
        use_sudo: sudoMode ? '1' : '',
        sudo_password: sudoMode ? (document.getElementById('sshSudoPassword').value || '') : ''
    }).then(resp => {
        // Remover loading
        const loadEl = document.getElementById(loadingId);
        if (loadEl) loadEl.remove();

        if (resp.success) {
            const d = resp.data;
            if (d.output) terminalAddLine(d.output, 'output');
            if (d.error) terminalAddLine(d.error, 'error');

            const duracao = d.duracao_ms ? `(${d.duracao_ms}ms)` : '';
            if (d.exit_code !== 0) {
                terminalAddLine(`[exit code: ${d.exit_code}] ${duracao}`, 'exit-code');
            } else if (d.duracao_ms) {
                terminalAddLine(duracao, 'info');
            }

            // AI Action Buttons
            const aiDiv = document.createElement('div');
            aiDiv.className = 'ssh-ai-actions';
            const saida = (d.output || '') + (d.error || '');
            aiDiv.innerHTML = `
                <button class="ssh-ai-btn" onclick="sshAiExplicar('${escapeJsStr(cmd)}')">
                    <i class="fas fa-robot"></i> Explicar
                </button>
                ${saida ? `<button class="ssh-ai-btn" onclick="sshAiInterpretar('${escapeJsStr(cmd)}', this)">
                    <i class="fas fa-lightbulb"></i> Interpretar Saída
                </button>` : ''}
                ${d.exit_code !== 0 ? `<button class="ssh-ai-btn" onclick="sshAiSugerir('${escapeJsStr(d.error || d.output || '')}')">
                    <i class="fas fa-magic"></i> Sugerir Correção
                </button>` : ''}
            `;
            aiDiv._saida = saida;
            document.getElementById('sshTerminalBody').appendChild(aiDiv);
        } else {
            terminalAddLine('Erro: ' + resp.error, 'error');
        }

        input.disabled = false;
        document.getElementById('sshSendBtn').disabled = false;
        input.focus();
    }).catch(err => {
        const loadEl = document.getElementById(loadingId);
        if (loadEl) loadEl.remove();

        terminalAddLine('Erro de conexão com o servidor', 'error');
        input.disabled = false;
        document.getElementById('sshSendBtn').disabled = false;
        input.focus();
    });
}

function terminalAddLine(text, type = 'output', id = null) {
    const body = document.getElementById('sshTerminalBody');
    const line = document.createElement('div');
    line.className = `ssh-line ssh-${type}`;
    if (id) line.id = id;

    if (type === 'loading') {
        line.innerHTML = text;
    } else {
        line.textContent = text;
    }

    body.appendChild(line);
    body.scrollTop = body.scrollHeight;
}

function limparTerminal() {
    const body = document.getElementById('sshTerminalBody');
    body.innerHTML = '';
    if (currentServerInfo) {
        terminalAddLine(`Terminal limpo — ${currentServerInfo.nome}`, 'info');
    }
}

function toggleSudo() {
    sudoMode = document.getElementById('sshSudoToggle').checked;
    document.getElementById('sshSudoBar').style.display = sudoMode ? 'block' : 'none';

    const prompt = document.getElementById('sshPrompt');
    if (sudoMode) {
        prompt.textContent = '#';
        prompt.style.color = '#EF4444';
        terminalAddLine('🔒 Modo sudo ativado — comandos serão executados como root', 'info');
    } else {
        prompt.textContent = currentServerInfo ? `${currentServerInfo.usuario}@${currentServerInfo.nome}:~$` : '$';
        prompt.style.color = '';
        terminalAddLine('🔓 Modo sudo desativado', 'info');
    }
    document.getElementById('sshInput').focus();
}

function handleTerminalKey(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        executarComandoSSH();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (historyIndex < sshHistory.length - 1) {
            historyIndex++;
            e.target.value = sshHistory[historyIndex];
        }
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (historyIndex > 0) {
            historyIndex--;
            e.target.value = sshHistory[historyIndex];
        } else {
            historyIndex = -1;
            e.target.value = '';
        }
    } else if (e.key === 'l' && e.ctrlKey) {
        e.preventDefault();
        limparTerminal();
    }
}

// ===== COMANDOS RÁPIDOS =====
function carregarComandosSalvos() {
    HelpDesk.api('GET', '/api/ssh.php', { action: 'comandos_salvos' })
        .then(resp => {
            if (!resp.success) return;

            const list = document.getElementById('sshQuickList');

            if (!resp.data.length) {
                list.innerHTML = '<p class="text-center text-muted py-3" style="font-size:12px">Nenhum comando salvo</p>';
                return;
            }

            // Agrupar por categoria
            const cats = {};
            resp.data.forEach(c => {
                const cat = c.categoria || 'geral';
                if (!cats[cat]) cats[cat] = [];
                cats[cat].push(c);
            });

            const catIcons = {
                sistema: 'fa-info-circle',
                recursos: 'fa-microchip',
                rede: 'fa-network-wired',
                servicos: 'fa-cogs',
                logs: 'fa-file-alt',
                geral: 'fa-terminal',
            };

            const catLabels = {
                sistema: 'Sistema',
                recursos: 'Recursos',
                rede: 'Rede',
                servicos: 'Serviços',
                logs: 'Logs',
                geral: 'Geral',
            };

            let html = '';
            Object.entries(cats).forEach(([cat, cmds]) => {
                const icon = catIcons[cat] || 'fa-terminal';
                const label = catLabels[cat] || cat;
                html += `<div class="ssh-quick-cat">
                    <div class="ssh-quick-cat-header" onclick="this.parentElement.classList.toggle('open')">
                        <i class="fas ${icon}"></i> ${escapeHtml(label)}
                        <i class="fas fa-chevron-right ssh-chevron"></i>
                    </div>
                    <div class="ssh-quick-cat-items">`;
                cmds.forEach(c => {
                    html += `<button class="ssh-quick-item" onclick="inserirComando('${escapeAttr(c.comando)}')" title="${escapeAttr(c.descricao || c.comando)}">
                        ${escapeHtml(c.nome)}
                    </button>`;
                });
                html += `</div></div>`;
            });

            list.innerHTML = html;

            // Abrir primeira categoria
            const first = list.querySelector('.ssh-quick-cat');
            if (first) first.classList.add('open');
        });
}

function inserirComando(cmd) {
    if (!currentServerId) {
        HelpDesk.toast('Conecte-se a um servidor primeiro', 'warning');
        return;
    }
    document.getElementById('sshInput').value = cmd;
    document.getElementById('sshInput').focus();
}

// ===== PAINEL DO SERVIDOR =====
function abrirPainelServidor() {
    if (!currentServerId) return;
    abrirPainelServidorId(currentServerId);
}

function abrirPainelServidorId(id) {
    HelpDesk.showModal(
        '<i class="fas fa-tachometer-alt"></i> Painel do Sistema',
        '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color:#6366F1"></i><p class="mt-3">Coletando informações do servidor via SSH...</p></div>',
        '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>',
        'modal-lg'
    );

    HelpDesk.api('POST', '/api/ssh.php', { action: 'info_sistema', id: id })
        .then(resp => {
            if (!resp.success) {
                document.querySelector('.modal-body').innerHTML = `
                    <div class="ad-error-msg"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(resp.error)}</div>`;
                return;
            }

            const info = resp.data;
            const memPctClass = info.mem_pct > 90 ? 'danger' : info.mem_pct > 70 ? 'warning' : 'success';
            const cpuPctClass = info.cpu_usage > 90 ? 'danger' : info.cpu_usage > 70 ? 'warning' : 'success';

            let discosHtml = '';
            if (info.discos && info.discos.length) {
                discosHtml = info.discos.map(d => {
                    const pctClass = d.use_pct > 90 ? 'danger' : d.use_pct > 70 ? 'warning' : 'success';
                    return `<div class="ssh-disk-item">
                        <div class="ssh-disk-info">
                            <span class="ssh-disk-mount">${escapeHtml(d.mount)}</span>
                            <span class="ssh-disk-size">${d.used} / ${d.size}</span>
                        </div>
                        <div class="ssh-progress-bar">
                            <div class="ssh-progress-fill ${pctClass}" style="width:${d.use_pct}%"></div>
                        </div>
                        <span class="ssh-pct">${d.use_pct}%</span>
                    </div>`;
                }).join('');
            }

            document.querySelector('.modal-body').innerHTML = `
            <div class="ssh-painel">
                <!-- Info Cards -->
                <div class="ssh-painel-grid">
                    <div class="ssh-info-card">
                        <div class="ssh-info-icon"><i class="fas fa-server"></i></div>
                        <div>
                            <strong>${escapeHtml(info.hostname)}</strong>
                            <small>${escapeHtml(info.os || 'SO não detectado')}</small>
                        </div>
                    </div>
                    <div class="ssh-info-card">
                        <div class="ssh-info-icon"><i class="fas fa-microchip"></i></div>
                        <div>
                            <strong>${info.cpu_count} vCPU${info.cpu_count > 1 ? 's' : ''}</strong>
                            <small>${escapeHtml(info.cpu_model || info.arch || '')}</small>
                        </div>
                    </div>
                    <div class="ssh-info-card">
                        <div class="ssh-info-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <strong>Uptime</strong>
                            <small>${escapeHtml(info.uptime || '—')}</small>
                        </div>
                    </div>
                    <div class="ssh-info-card">
                        <div class="ssh-info-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <strong>${info.logged_users} usuário(s)</strong>
                            <small>${info.processes} processos</small>
                        </div>
                    </div>
                </div>

                <!-- CPU -->
                <div class="ssh-resource-card">
                    <h4><i class="fas fa-microchip"></i> CPU</h4>
                    <div class="ssh-resource-bar">
                        <div class="ssh-progress-bar large">
                            <div class="ssh-progress-fill ${cpuPctClass}" style="width:${info.cpu_usage}%"></div>
                        </div>
                        <span class="ssh-pct">${info.cpu_usage}%</span>
                    </div>
                    ${info.load ? `<small class="text-muted">Load: ${escapeHtml(info.load)}</small>` : ''}
                </div>

                <!-- Memória -->
                <div class="ssh-resource-card">
                    <h4><i class="fas fa-memory"></i> Memória RAM</h4>
                    <div class="ssh-resource-bar">
                        <div class="ssh-progress-bar large">
                            <div class="ssh-progress-fill ${memPctClass}" style="width:${info.mem_pct}%"></div>
                        </div>
                        <span class="ssh-pct">${info.mem_pct}%</span>
                    </div>
                    <small class="text-muted">${info.mem_used}MB / ${info.mem_total}MB (${info.mem_free}MB livre)</small>
                    ${info.swap_total > 0 ? `<br><small class="text-muted">Swap: ${info.swap_used}MB / ${info.swap_total}MB (${info.swap_pct}%)</small>` : ''}
                </div>

                <!-- Disco -->
                <div class="ssh-resource-card">
                    <h4><i class="fas fa-hdd"></i> Disco</h4>
                    ${discosHtml || '<small class="text-muted">Sem dados</small>'}
                </div>

                <!-- IPs -->
                ${info.ips ? `<div class="ssh-resource-card">
                    <h4><i class="fas fa-network-wired"></i> IPs</h4>
                    <code style="font-size:12px">${escapeHtml(info.ips)}</code>
                </div>` : ''}
            </div>`;
        })
        .catch(() => {
            document.querySelector('.modal-body').innerHTML =
                '<div class="ad-error-msg"><i class="fas fa-exclamation-triangle"></i> Erro ao conectar ao servidor</div>';
        });
}

// ===== HISTÓRICO =====
function verHistoricoSSH() {
    if (!currentServerId) return;

    HelpDesk.api('GET', '/api/ssh.php', { action: 'historico', servidor_id: currentServerId })
        .then(resp => {
            if (!resp.success) return HelpDesk.toast(resp.error, 'danger');

            let html = '';
            if (!resp.data.length) {
                html = '<p class="text-center text-muted py-4">Nenhum comando executado ainda.</p>';
            } else {
                html = '<div class="ssh-historico-list">';
                resp.data.forEach(h => {
                    const exitClass = h.exit_code === 0 ? 'success' : 'danger';
                    html += `
                    <div class="ssh-historico-item" onclick="inserirComando('${escapeAttr(h.comando)}')">
                        <div class="ssh-hist-cmd"><code>$ ${escapeHtml(h.comando)}</code></div>
                        <div class="ssh-hist-meta">
                            <span class="badge badge-${exitClass}" style="font-size:10px">exit: ${h.exit_code}</span>
                            <span>${h.duracao_ms || 0}ms</span>
                            <span>${escapeHtml(h.usuario_nome || '')}</span>
                            <span>${escapeHtml(h.executado_em)}</span>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }

            HelpDesk.showModal(
                '<i class="fas fa-history"></i> Histórico de Comandos',
                html,
                '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>',
                'modal-lg'
            );
        });
}

// ===== HELPERS =====
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeAttr(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\\/g,'\\\\');
}

function timeAgo(dateStr) {
    if (!dateStr) return 'Nunca';
    const now = new Date();
    const date = new Date(dateStr.replace(' ', 'T'));
    const diffSec = Math.floor((now - date) / 1000);
    if (diffSec < 60) return 'Agora';
    if (diffSec < 3600) return Math.floor(diffSec / 60) + 'min atrás';
    if (diffSec < 86400) return Math.floor(diffSec / 3600) + 'h atrás';
    return Math.floor(diffSec / 86400) + 'd atrás';
}

function escapeJsStr(str) {
    if (!str) return '';
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, '\\n').replace(/\r/g, '');
}

// ===== SSH AI INTEGRATION =====
function sshAiRenderContent(text) {
    if (typeof marked !== 'undefined') {
        try { return marked.parse(text); } catch(e) {}
    }
    return escapeHtml(text).replace(/\n/g, '<br>');
}

function sshAiExplicar(comando) {
    terminalAddLine('🤖 Consultando IA...', 'info');

    HelpDesk.api('POST', '/api/ia.php', {
        action: 'explicar_comando',
        comando: comando
    }).then(resp => {
        if (resp.success) {
            const div = document.createElement('div');
            div.className = 'ssh-ai-response';
            div.innerHTML = '<strong>🤖 Explicação:</strong>' + sshAiRenderContent(resp.data.content);
            document.getElementById('sshTerminalBody').appendChild(div);
            document.getElementById('sshTerminalBody').scrollTop = document.getElementById('sshTerminalBody').scrollHeight;
        } else {
            terminalAddLine('❌ IA indisponível: ' + (resp.error || 'Verifique o Ollama'), 'error');
        }
    }).catch(() => terminalAddLine('❌ Erro de conexão com a IA', 'error'));
}

function sshAiInterpretar(comando, btn) {
    // Pega a saída do comando (linhas de output anteriores ao botão)
    const actionsDiv = btn.closest('.ssh-ai-actions');
    const saida = actionsDiv._saida || '';

    terminalAddLine('🤖 Analisando saída...', 'info');

    HelpDesk.api('POST', '/api/ia.php', {
        action: 'interpretar_saida',
        comando: comando,
        saida: saida
    }).then(resp => {
        if (resp.success) {
            const div = document.createElement('div');
            div.className = 'ssh-ai-response';
            div.innerHTML = '<strong>🤖 Análise:</strong>' + sshAiRenderContent(resp.data.content);
            document.getElementById('sshTerminalBody').appendChild(div);
            document.getElementById('sshTerminalBody').scrollTop = document.getElementById('sshTerminalBody').scrollHeight;
        } else {
            terminalAddLine('❌ IA indisponível: ' + (resp.error || ''), 'error');
        }
    }).catch(() => terminalAddLine('❌ Erro de conexão com a IA', 'error'));
}

function sshAiSugerir(erro) {
    terminalAddLine('🤖 Buscando sugestão...', 'info');

    HelpDesk.api('POST', '/api/ia.php', {
        action: 'sugerir_comando',
        objetivo: 'Corrigir o seguinte erro: ' + erro,
        contexto_servidor: currentServerInfo ? `${currentServerInfo.nome} (${currentServerInfo.host})` : ''
    }).then(resp => {
        if (resp.success) {
            const div = document.createElement('div');
            div.className = 'ssh-ai-response';
            div.innerHTML = '<strong>🤖 Sugestão:</strong>' + sshAiRenderContent(resp.data.content);
            document.getElementById('sshTerminalBody').appendChild(div);
            document.getElementById('sshTerminalBody').scrollTop = document.getElementById('sshTerminalBody').scrollHeight;
        } else {
            terminalAddLine('❌ IA indisponível: ' + (resp.error || ''), 'error');
        }
    }).catch(() => terminalAddLine('❌ Erro de conexão com a IA', 'error'));
}
</script>
