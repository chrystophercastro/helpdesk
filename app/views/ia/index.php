<?php
/**
 * View: Chat IA
 * Interface de chat com Inteligência Artificial (Ollama/Llama 3)
 */
$user = currentUser();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-robot"></i> Assistente IA</h1>
        <p class="page-subtitle">Chat inteligente multi-modelo — Llama 3, Phi-3, Gemma 2, Qwen, DeepSeek, Mistral</p>
    </div>
    <div class="page-header-right">
        <div id="iaStatusBadge" class="ia-status-badge offline">
            <span class="ia-status-dot"></span>
            <span id="iaStatusText">Verificando...</span>
        </div>
        <?php if ($user['tipo'] === 'admin'): ?>
        <button class="btn btn-outline btn-sm" onclick="abrirModelos()">
            <i class="fas fa-cubes"></i> Modelos
        </button>
        <button class="btn btn-outline btn-sm" onclick="abrirConfigIA()">
            <i class="fas fa-cog"></i> Configurar
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Layout do Chat -->
<div class="ia-layout">
    <!-- Sidebar: Conversas -->
    <div class="ia-sidebar" id="iaSidebar">
        <div class="ia-sidebar-header">
            <button class="btn btn-primary btn-sm" onclick="novaConversa()" style="width:100%">
                <i class="fas fa-plus"></i> Nova Conversa
            </button>
        </div>
        <div class="ia-sidebar-search">
            <i class="fas fa-search"></i>
            <input type="text" id="iaBuscaConversa" placeholder="Buscar conversas..." oninput="filtrarConversas(this.value)">
        </div>
        <div class="ia-conversas-list" id="iaConversasList">
            <div class="loading-center py-3">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
        <div class="ia-sidebar-footer">
            <div class="ia-stats-mini" id="iaStatsMini">
                <span><i class="fas fa-comments"></i> <b id="iaTotalConversas">0</b></span>
                <span><i class="fas fa-coins"></i> <b id="iaTotalTokens">0</b></span>
            </div>
        </div>
    </div>

    <!-- Chat Principal -->
    <div class="ia-chat" id="iaChat">
        <!-- Header do chat -->
        <div class="ia-chat-header" id="iaChatHeader">
            <button class="btn-icon ia-sidebar-toggle" onclick="toggleIaSidebar()" title="Conversas">
                <i class="fas fa-bars"></i>
            </button>
            <div class="ia-chat-title">
                <h3 id="iaChatTitulo">Assistente IA</h3>
                <div class="ia-model-selector" id="iaModelSelector">
                    <button class="ia-model-btn" onclick="toggleModelDropdown()" title="Trocar modelo">
                        <span class="ia-model-dot" id="iaModelDot"></span>
                        <span id="iaModeloNome">llama3</span>
                        <i class="fas fa-chevron-down" style="font-size:10px;opacity:0.6"></i>
                    </button>
                    <div class="ia-model-dropdown" id="iaModelDropdown" style="display:none">
                        <div class="ia-model-dropdown-header">
                            <span>Selecionar Modelo</span>
                            <span class="ia-model-count" id="iaModelCount">0</span>
                        </div>
                        <div class="ia-model-list" id="iaModelList">
                            <div class="loading-center py-2"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                        <div class="ia-model-dropdown-footer" id="iaModelTarefas"></div>
                    </div>
                </div>
            </div>
            <div class="ia-chat-actions">
                <button class="btn-icon" onclick="renomearConversaAtual()" title="Renomear" id="btnRenomear" style="display:none">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn-icon" onclick="excluirConversaAtual()" title="Excluir" id="btnExcluirConversa" style="display:none">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- Mensagens -->
        <div class="ia-messages" id="iaMessages">
            <div class="ia-welcome" id="iaWelcome">
                <div class="ia-welcome-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h2>Olá, <?= htmlspecialchars(explode(' ', $user['nome'])[0]) ?>! 👋</h2>
                <p>Sou o assistente IA do HelpDesk. Como posso ajudar?</p>
                <div class="ia-welcome-suggestions">
                    <button class="ia-suggestion" onclick="enviarSugestao('Crie um projeto chamado Migração de Servidores com 3 tarefas e um sprint')">
                        <i class="fas fa-project-diagram"></i> Criar projeto com tarefas
                    </button>
                    <button class="ia-suggestion" onclick="enviarSugestao('Quais projetos estão ativos no sistema?')">
                        <i class="fas fa-tasks"></i> Listar projetos ativos
                    </button>
                    <button class="ia-suggestion" onclick="enviarSugestao('Me dê as estatísticas gerais do sistema')">
                        <i class="fas fa-chart-bar"></i> Estatísticas do sistema
                    </button>
                    <button class="ia-suggestion" onclick="enviarSugestao('Quais chamados estão abertos com prioridade alta?')">
                        <i class="fas fa-ticket-alt"></i> Chamados prioritários
                    </button>
                    <button class="ia-suggestion" onclick="enviarSugestao('Crie um chamado: Impressora do 2° andar não imprime')">
                        <i class="fas fa-plus-circle"></i> Criar chamado
                    </button>
                    <button class="ia-suggestion" onclick="enviarSugestao('Liste os servidores SSH e me diga o status de cada um')">
                        <i class="fas fa-server"></i> Status dos servidores
                    </button>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="ia-input-area">
            <div class="ia-input-wrapper">
                <textarea id="iaInput" class="ia-input" placeholder="Pergunte algo..." rows="1"
                          onkeydown="handleIaKeydown(event)" oninput="autoResizeTextarea(this)"></textarea>
                <div class="ia-input-actions">
                    <button class="ia-send-btn" id="iaSendBtn" onclick="enviarMensagem()" title="Enviar (Enter)">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <div class="ia-input-footer">
                <span class="ia-input-hint">Enter para enviar • Shift+Enter para nova linha</span>
                <span class="ia-typing" id="iaTyping" style="display:none">
                    <i class="fas fa-circle-notch fa-spin"></i> Pensando...
                </span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<script>
// ===== STATE =====
let iaConversaAtual = null;
let iaConversasCache = [];
let iaEnviando = false;

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    verificarStatusIA();
    carregarConversas();
    document.getElementById('iaInput').focus();
});

// ===== STATUS =====
let iaModeloSelecionado = null;
let iaModelosDisponiveis = [];
let iaModelosTarefa = {};

function verificarStatusIA() {
    HelpDesk.api('GET', '/api/ia.php', { action: 'status' })
        .then(resp => {
            if (!resp.success) return;
            const d = resp.data;
            const badge = document.getElementById('iaStatusBadge');
            const text = document.getElementById('iaStatusText');

            if (d.online) {
                badge.className = 'ia-status-badge online';
                text.textContent = `Online · ${(d.models||[]).length} modelo(s)`;
                iaModeloSelecionado = iaModeloSelecionado || d.modelo_padrao;
                document.getElementById('iaModeloNome').textContent = iaModeloSelecionado;
                iaModelosDisponiveis = d.models || [];
                iaModelosTarefa = d.modelos_tarefa || {};
                renderModelList();
                renderModelTarefas();
                // Dot color
                const meta = iaModelosDisponiveis.find(m => m.name === iaModeloSelecionado);
                const dot = document.getElementById('iaModelDot');
                if (dot && meta) dot.style.background = meta.cor || '#22C55E';
            } else {
                badge.className = 'ia-status-badge offline';
                text.textContent = 'Offline';
            }

            if (d.stats) {
                document.getElementById('iaTotalConversas').textContent = d.stats.total_conversas || 0;
                document.getElementById('iaTotalTokens').textContent = formatTokens(d.stats.total_tokens || 0);
            }
        });
}

function toggleModelDropdown() {
    const dd = document.getElementById('iaModelDropdown');
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

// Fechar dropdown ao clicar fora
document.addEventListener('click', (e) => {
    const sel = document.getElementById('iaModelSelector');
    if (sel && !sel.contains(e.target)) {
        document.getElementById('iaModelDropdown').style.display = 'none';
    }
});

function renderModelList() {
    const list = document.getElementById('iaModelList');
    const countEl = document.getElementById('iaModelCount');
    if (countEl) countEl.textContent = iaModelosDisponiveis.length;

    if (!iaModelosDisponiveis.length) {
        list.innerHTML = '<div class="ia-model-item" style="color:#94A3B8;justify-content:center">Nenhum modelo encontrado</div>';
        return;
    }

    const tierMap = {
        'ultra-leve': { icon: 'fa-feather', badge: '⚡ Ultra-Leve', cls: 'ia-tier-ultralight' },
        'leve': { icon: 'fa-leaf', badge: '🌿 Leve', cls: 'ia-tier-light' },
        'medio': { icon: 'fa-bolt', badge: '⭐ Médio', cls: 'ia-tier-medium' },
        'avancado': { icon: 'fa-brain', badge: '🧠 Avançado', cls: 'ia-tier-advanced' },
        'desconhecido': { icon: 'fa-cube', badge: 'Outro', cls: 'ia-tier-unknown' },
    };

    list.innerHTML = iaModelosDisponiveis.map(m => {
        const isActive = m.name === iaModeloSelecionado;
        const t = tierMap[m.tier] || tierMap['desconhecido'];
        // Check if this model is assigned to any task
        const tasks = [];
        Object.entries(iaModelosTarefa).forEach(([k, v]) => {
            if (v.modelo === m.name) tasks.push(v.label);
        });
        const taskBadges = tasks.map(t => `<span class="ia-model-task-badge">${t}</span>`).join('');
        return `
        <div class="ia-model-item ${isActive ? 'active' : ''}" onclick="selecionarModelo('${m.name}')">
            <div class="ia-model-item-icon" style="background:${m.cor}20;color:${m.cor}">
                <i class="fas ${m.icone || t.icon}"></i>
            </div>
            <div class="ia-model-item-info">
                <span class="ia-model-item-name">${m.label || m.name}</span>
                <span class="ia-model-item-meta">
                    <span class="ia-model-tier-badge ${t.cls}">${t.badge}</span>
                    <span>${m.size}</span>
                    ${m.parametros && m.parametros !== '?' ? `<span>· ${m.parametros}</span>` : ''}
                </span>
                ${taskBadges ? `<div class="ia-model-tasks-row">${taskBadges}</div>` : ''}
            </div>
            ${isActive ? '<i class="fas fa-check-circle" style="color:#22C55E;font-size:16px"></i>' : ''}
        </div>`;
    }).join('');
}

function renderModelTarefas() {
    const footer = document.getElementById('iaModelTarefas');
    if (!footer) return;
    const tarefas = Object.entries(iaModelosTarefa);
    if (!tarefas.length) { footer.innerHTML = ''; return; }
    footer.innerHTML = `<div class="ia-model-tarefas-header">Modelos por Tarefa</div>` +
        tarefas.map(([k, v]) => `
            <div class="ia-model-tarefa-row">
                <i class="fas ${v.icone}" style="width:16px;text-align:center"></i>
                <span>${v.label}</span>
                <strong>${v.modelo}</strong>
            </div>
        `).join('');
}

function selecionarModelo(nome) {
    iaModeloSelecionado = nome;
    document.getElementById('iaModeloNome').textContent = nome;
    document.getElementById('iaModelDropdown').style.display = 'none';
    // Update dot color
    const meta = iaModelosDisponiveis.find(m => m.name === nome);
    const dot = document.getElementById('iaModelDot');
    if (dot && meta) dot.style.background = meta.cor || '#22C55E';
    renderModelList();
    showToastIA(`Modelo: ${meta?.label || nome}`, 'success');
}

function showToastIA(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `pve-toast pve-toast-${type}`;
    el.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'info-circle'}"></i> ${msg}`;
    document.body.appendChild(el);
    setTimeout(() => el.classList.add('show'), 10);
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 3000);
}

function formatTokens(n) {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return n;
}

// ===== CONVERSAS =====
function carregarConversas() {
    HelpDesk.api('GET', '/api/ia.php', { action: 'conversas' })
        .then(resp => {
            if (!resp.success) return;
            iaConversasCache = resp.data;
            renderConversas(resp.data);
        });
}

function renderConversas(conversas) {
    const list = document.getElementById('iaConversasList');

    if (!conversas.length) {
        list.innerHTML = `
            <div class="ia-empty-conversas">
                <i class="fas fa-comments" style="font-size:24px;color:#CBD5E1"></i>
                <p style="color:#94A3B8;font-size:13px;margin-top:8px">Nenhuma conversa ainda</p>
            </div>`;
        return;
    }

    // Agrupar por data
    const hoje = new Date().toDateString();
    const ontem = new Date(Date.now() - 86400000).toDateString();
    const grupos = { hoje: [], ontem: [], semana: [], antes: [] };

    conversas.forEach(c => {
        const d = new Date(c.atualizado_em).toDateString();
        if (d === hoje) grupos.hoje.push(c);
        else if (d === ontem) grupos.ontem.push(c);
        else if (Date.now() - new Date(c.atualizado_em) < 7 * 86400000) grupos.semana.push(c);
        else grupos.antes.push(c);
    });

    let html = '';
    const renderGroup = (label, items) => {
        if (!items.length) return '';
        let h = `<div class="ia-conversa-group-label">${label}</div>`;
        items.forEach(c => {
            const active = iaConversaAtual == c.id ? 'active' : '';
            const icon = c.contexto === 'ssh' ? 'fa-terminal' : c.contexto === 'chamado' ? 'fa-ticket-alt' : 'fa-comment';
            h += `
                <div class="ia-conversa-item ${active}" data-id="${c.id}" onclick="abrirConversa(${c.id})">
                    <i class="fas ${icon}"></i>
                    <span class="ia-conversa-titulo">${escapeHtml(c.titulo)}</span>
                    <span class="ia-conversa-count">${c.total_mensagens || ''}</span>
                </div>`;
        });
        return h;
    };

    html += renderGroup('Hoje', grupos.hoje);
    html += renderGroup('Ontem', grupos.ontem);
    html += renderGroup('Esta Semana', grupos.semana);
    html += renderGroup('Anteriores', grupos.antes);

    list.innerHTML = html;
}

function filtrarConversas(busca) {
    busca = busca.toLowerCase();
    const filtrados = iaConversasCache.filter(c =>
        c.titulo.toLowerCase().includes(busca) ||
        (c.primeira_mensagem || '').toLowerCase().includes(busca)
    );
    renderConversas(filtrados);
}

function novaConversa() {
    iaConversaAtual = null;
    document.getElementById('iaMessages').innerHTML = document.getElementById('iaWelcome') ?
        document.getElementById('iaMessages').innerHTML : '';

    // Rebuild welcome
    const messagesDiv = document.getElementById('iaMessages');
    messagesDiv.innerHTML = `
        <div class="ia-welcome" id="iaWelcome">
            <div class="ia-welcome-icon"><i class="fas fa-robot"></i></div>
            <h2>Nova Conversa</h2>
            <p>Faça uma pergunta para começar</p>
        </div>`;

    document.getElementById('iaChatTitulo').textContent = 'Nova Conversa';
    document.getElementById('btnRenomear').style.display = 'none';
    document.getElementById('btnExcluirConversa').style.display = 'none';
    document.getElementById('iaInput').focus();

    // Remover active das conversas
    document.querySelectorAll('.ia-conversa-item').forEach(el => el.classList.remove('active'));
}

function abrirConversa(id) {
    iaConversaAtual = id;

    // Marcar ativa
    document.querySelectorAll('.ia-conversa-item').forEach(el => {
        el.classList.toggle('active', el.dataset.id == id);
    });

    document.getElementById('btnRenomear').style.display = '';
    document.getElementById('btnExcluirConversa').style.display = '';

    // Carregar mensagens
    const msgDiv = document.getElementById('iaMessages');
    msgDiv.innerHTML = '<div class="loading-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    HelpDesk.api('GET', '/api/ia.php', { action: 'mensagens', conversa_id: id })
        .then(resp => {
            if (!resp.success) return;

            document.getElementById('iaChatTitulo').textContent = resp.data.conversa.titulo;
            document.getElementById('iaModeloNome').textContent = resp.data.conversa.modelo;
            iaModeloSelecionado = resp.data.conversa.modelo;
            renderModelList();

            msgDiv.innerHTML = '';
            resp.data.mensagens.forEach(m => {
                if (m.role !== 'system') {
                    adicionarMensagem(m.role, m.conteudo, m.duracao_ms, m.tokens, false);
                }
            });

            msgDiv.scrollTop = msgDiv.scrollHeight;
            document.getElementById('iaInput').focus();
        });
}

function renomearConversaAtual() {
    if (!iaConversaAtual) return;
    const titulo = prompt('Novo nome da conversa:');
    if (!titulo) return;

    HelpDesk.api('POST', '/api/ia.php', { action: 'renomear', id: iaConversaAtual, titulo })
        .then(resp => {
            if (resp.success) {
                document.getElementById('iaChatTitulo').textContent = titulo;
                carregarConversas();
            }
        });
}

function excluirConversaAtual() {
    if (!iaConversaAtual) return;
    if (!confirm('Excluir esta conversa?')) return;

    HelpDesk.api('POST', '/api/ia.php', { action: 'excluir_conversa', id: iaConversaAtual })
        .then(resp => {
            if (resp.success) {
                novaConversa();
                carregarConversas();
                HelpDesk.toast('Conversa excluída', 'success');
            }
        });
}

// ===== MENSAGENS =====
function adicionarMensagem(role, content, duracaoMs = 0, tokens = 0, animate = true) {
    const msgDiv = document.getElementById('iaMessages');
    const welcome = document.getElementById('iaWelcome');
    if (welcome) welcome.remove();

    const div = document.createElement('div');
    div.className = `ia-message ia-${role}` + (animate ? ' ia-animate-in' : '');

    const avatar = role === 'user'
        ? `<div class="ia-avatar ia-avatar-user"><i class="fas fa-user"></i></div>`
        : `<div class="ia-avatar ia-avatar-ai"><i class="fas fa-robot"></i></div>`;

    const meta = duracaoMs
        ? `<div class="ia-meta"><span>${duracaoMs}ms</span>${tokens ? ` • ${tokens} tokens` : ''}</div>`
        : '';

    // Renderizar markdown para respostas da IA
    let htmlContent;
    if (role === 'assistant') {
        try {
            htmlContent = marked.parse(renderStreamContent(content));
        } catch(e) {
            htmlContent = content.replace(/\n/g, '<br>');
        }
    } else {
        htmlContent = escapeHtml(content).replace(/\n/g, '<br>');
    }

    div.innerHTML = `
        ${avatar}
        <div class="ia-bubble">
            <div class="ia-content">${htmlContent}</div>
            ${meta}
        </div>`;

    // Botão copiar para respostas da IA
    if (role === 'assistant') {
        const copyBtn = document.createElement('button');
        copyBtn.className = 'ia-copy-btn';
        copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
        copyBtn.title = 'Copiar';
        copyBtn.onclick = () => {
            navigator.clipboard.writeText(content);
            copyBtn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => copyBtn.innerHTML = '<i class="fas fa-copy"></i>', 2000);
        };
        div.querySelector('.ia-bubble').appendChild(copyBtn);
    }

    msgDiv.appendChild(div);
    msgDiv.scrollTop = msgDiv.scrollHeight;
}

function adicionarLoading() {
    const msgDiv = document.getElementById('iaMessages');
    const welcome = document.getElementById('iaWelcome');
    if (welcome) welcome.remove();

    const div = document.createElement('div');
    div.className = 'ia-message ia-assistant ia-animate-in';
    div.id = 'iaLoadingMsg';
    div.innerHTML = `
        <div class="ia-avatar ia-avatar-ai"><i class="fas fa-robot"></i></div>
        <div class="ia-bubble">
            <div class="ia-typing-indicator">
                <span></span><span></span><span></span>
            </div>
        </div>`;
    msgDiv.appendChild(div);
    msgDiv.scrollTop = msgDiv.scrollHeight;
}

function removerLoading() {
    const el = document.getElementById('iaLoadingMsg');
    if (el) el.remove();
}

// ===== STREAM HELPERS =====
let _renderTimer = null;
let _lastRender = 0;

function renderStreamContent(text) {
    // Substituir blocos [ACTION:xxx]{...}[/ACTION] completos por indicadores visuais limpos
    let display = text.replace(/\[ACTION:(\w+)\][\s\S]*?\[\/ACTION\]/g, function(m, name) {
        return '\n> ⚡ **' + name.replace(/_/g, ' ') + '**\n';
    });
    // Esconder bloco ACTION incompleto (IA ainda digitando)
    const idx = display.lastIndexOf('[ACTION:');
    if (idx !== -1 && display.indexOf('[/ACTION]', idx) === -1) {
        display = display.substring(0, idx) + '\n> ⏳ *Preparando ação...*\n';
    }
    return display;
}

function atualizarStreamUI(fullContent, msgDiv, forceNow) {
    const now = Date.now();
    const el = document.getElementById('iaStreamContent');
    if (!el) return;

    // Throttle: renderizar no máximo a cada 80ms para suavidade
    if (!forceNow && now - _lastRender < 80) {
        clearTimeout(_renderTimer);
        _renderTimer = setTimeout(() => atualizarStreamUI(fullContent, msgDiv, true), 80);
        return;
    }
    _lastRender = now;

    const display = renderStreamContent(fullContent);
    try {
        el.innerHTML = marked.parse(display) + '<span class="ia-cursor">▊</span>';
    } catch(e) {
        el.innerHTML = escapeHtml(display).replace(/\n/g, '<br>') + '<span class="ia-cursor">▊</span>';
    }
    msgDiv.scrollTop = msgDiv.scrollHeight;
}

function criarExecPanel(actionsList) {
    let html = '<div class="ia-exec-panel" id="iaExecPanel">';
    html += '<div class="ia-exec-header" id="iaExecHeader"><i class="fas fa-bolt ia-pulse"></i> Executando <b>' + actionsList.length + '</b> ação(ões)...</div>';
    html += '<div class="ia-exec-progress-wrap"><div class="ia-exec-progress-fill" id="iaExecProgressFill"></div></div>';
    html += '<div class="ia-exec-list" id="iaExecList">';
    actionsList.forEach((a, i) => {
        html += `<div class="ia-exec-item ia-exec-pending" id="iaExecItem${i}">
            <span class="ia-exec-icon">⬜</span>
            <span class="ia-exec-label">${a.label}</span>
            <span class="ia-exec-result" id="iaExecResult${i}"></span>
        </div>`;
    });
    html += '</div></div>';
    return html;
}

// ===== ENVIAR (STREAMING SSE) =====
function enviarMensagem() {
    const input = document.getElementById('iaInput');
    const msg = input.value.trim();
    if (!msg || iaEnviando) return;

    iaEnviando = true;
    input.value = '';
    autoResizeTextarea(input);

    // Mostrar mensagem do usuário
    adicionarMensagem('user', msg);

    const typingEl = document.getElementById('iaTyping');
    typingEl.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Pensando...';
    typingEl.style.display = 'inline-flex';
    document.getElementById('iaSendBtn').disabled = true;

    // Criar conversa se necessário
    const criarEEnviar = (conversaId) => {
        // Criar bolha da IA vazia para streaming
        const msgDiv = document.getElementById('iaMessages');
        const welcome = document.getElementById('iaWelcome');
        if (welcome) welcome.remove();

        const aiDiv = document.createElement('div');
        aiDiv.className = 'ia-message ia-assistant ia-animate-in';
        aiDiv.innerHTML = `
            <div class="ia-avatar ia-avatar-ai"><i class="fas fa-robot"></i></div>
            <div class="ia-bubble" id="iaStreamBubble">
                <div class="ia-content" id="iaStreamContent"><span class="ia-cursor">▊</span></div>
            </div>`;
        msgDiv.appendChild(aiDiv);

        let fullContent = '';
        let totalActions = 0;
        let completedActions = 0;
        const startTime = Date.now();

        // SSE via fetch + ReadableStream
        const baseUrl = document.getElementById('baseUrl').value;
        const token = document.getElementById('csrfToken').value;

        fetch(baseUrl + '/api/ia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'enviar_stream',
                mensagem: msg,
                conversa_id: conversaId || '',
                contexto: 'geral',
                modelo: iaModeloSelecionado || '',
                csrf_token: token
            })
        }).then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            let streamDoneContent = null; // conteúdo final do servidor

            function processSSELine(line) {
                if (!line.startsWith('data: ')) return false;
                try {
                    const data = JSON.parse(line.slice(6));

                    if (data.error) {
                        finalizarStreamComErro(data.error);
                        return 'stop';
                    }

                    // ===== CONEXÃO ESTABELECIDA =====
                    if (data.status === 'connected') {
                        const modelLabel = data.model_label || data.model || '';
                        let statusMsg = '<i class="fas fa-circle-notch fa-spin"></i> ';
                        if (data.is_complex) {
                            statusMsg += '🧠 Usando <b>' + modelLabel + '</b> para tarefa complexa...';
                        } else if (data.needs_tools) {
                            statusMsg += '⚡ Processando ações com <b>' + modelLabel + '</b>...';
                        } else {
                            statusMsg += 'Conectado a <b>' + modelLabel + '</b>...';
                        }
                        typingEl.innerHTML = statusMsg;
                        return false;
                    }

                    // ===== MODELO CARREGANDO (heartbeat) =====
                    if (data.loading) {
                        const elapsed = Math.round((Date.now() - startTime) / 1000);
                        const modelName = data.model || '';
                        if (data.status === 'planning') {
                            typingEl.innerHTML = '<i class="fas fa-brain ia-pulse"></i> 🧠 Planejando projeto... ' + elapsed + 's';
                        } else {
                            typingEl.innerHTML = '<i class="fas fa-cog fa-spin"></i> Carregando <b>' + modelName + '</b> na memória... ' + elapsed + 's';
                        }
                        return false;
                    }

                    // ===== TOKENS DE CONTEÚDO =====
                    if (data.content) {
                        // Trocar indicador para "Gerando..."
                        typingEl.innerHTML = '<i class="fas fa-pen-fancy ia-pulse"></i> Gerando resposta...';
                        fullContent += data.content;
                        atualizarStreamUI(fullContent, msgDiv, false);
                    }

                    // ===== FASE DE EXECUÇÃO: INÍCIO =====
                    if (data.actions_phase) {
                        totalActions = data.total;
                        completedActions = 0;
                        typingEl.innerHTML = '<i class="fas fa-bolt ia-pulse"></i> Executando ações...';
                        // Renderizar texto limpo (sem cursor)
                        const el = document.getElementById('iaStreamContent');
                        if (el) {
                            const display = renderStreamContent(fullContent);
                            try { el.innerHTML = marked.parse(display); }
                            catch(e) { el.innerHTML = escapeHtml(display).replace(/\n/g, '<br>'); }
                        }
                        // Criar painel de execução
                        const bubble = document.getElementById('iaStreamBubble');
                        if (bubble) {
                            const wrapper = document.createElement('div');
                            wrapper.innerHTML = criarExecPanel(data.actions_list);
                            bubble.appendChild(wrapper.firstChild);
                        }
                        msgDiv.scrollTop = msgDiv.scrollHeight;
                    }

                    // ===== AÇÃO EXECUTANDO =====
                    if (data.action_executing) {
                        const item = document.getElementById('iaExecItem' + data.index);
                        if (item) {
                            item.className = 'ia-exec-item ia-exec-running';
                            item.querySelector('.ia-exec-icon').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        }
                        typingEl.innerHTML = '<i class="fas fa-cog fa-spin"></i> ' + data.label + '... (' + (data.index + 1) + '/' + totalActions + ')';
                        msgDiv.scrollTop = msgDiv.scrollHeight;
                    }

                    // ===== AÇÃO CONCLUÍDA =====
                    if (data.action_done) {
                        completedActions++;
                        const item = document.getElementById('iaExecItem' + data.index);
                        if (item) {
                            if (data.success) {
                                item.className = 'ia-exec-item ia-exec-success';
                                item.querySelector('.ia-exec-icon').textContent = '✅';
                            } else {
                                item.className = 'ia-exec-item ia-exec-error';
                                item.querySelector('.ia-exec-icon').textContent = '❌';
                            }
                            const resultEl = document.getElementById('iaExecResult' + data.index);
                            if (resultEl) resultEl.textContent = data.message || '';
                        }
                        const fill = document.getElementById('iaExecProgressFill');
                        if (fill) fill.style.width = Math.round((completedActions / totalActions) * 100) + '%';
                        if (completedActions >= totalActions) {
                            const header = document.getElementById('iaExecHeader');
                            if (header) header.innerHTML = '<i class="fas fa-check-circle"></i> <b>' + totalActions + '</b> ação(ões) concluída(s)';
                            typingEl.innerHTML = '<i class="fas fa-check"></i> Finalizando...';
                        }
                        msgDiv.scrollTop = msgDiv.scrollHeight;
                    }

                    // ===== STREAM FINALIZADA =====
                    if (data.done) {
                        if (data.conversa_id && !iaConversaAtual) {
                            iaConversaAtual = data.conversa_id;
                            document.getElementById('btnRenomear').style.display = '';
                            document.getElementById('btnExcluirConversa').style.display = '';
                            carregarConversas();
                        }
                        streamDoneContent = data.final_content || fullContent;
                        finalizarStream(streamDoneContent, startTime, data.tokens, data.duracao_ms);
                        return 'stop';
                    }
                } catch(e) { /* skip malformed lines */ }
                return false;
            }

            function processStream({ done, value }) {
                if (value) {
                    buffer += decoder.decode(value, { stream: true });
                }

                // Processar todas as linhas completas no buffer
                const lines = buffer.split('\n');
                buffer = lines.pop(); // guardar linha incompleta

                for (const line of lines) {
                    const result = processSSELine(line);
                    if (result === 'stop') return;
                }

                // Se o ReadableStream acabou, processar buffer restante
                if (done) {
                    if (buffer.trim()) {
                        const result = processSSELine(buffer.trim());
                        if (result === 'stop') return;
                    }
                    // Fallback: stream acabou sem evento done do servidor
                    if (!streamDoneContent) {
                        finalizarStream(fullContent, startTime);
                    }
                    return;
                }

                return reader.read().then(processStream);
            }

            return reader.read().then(processStream);

        }).catch(err => {
            let errorMsg = err.message || 'Falha na conexão';
            if (err.name === 'AbortError') {
                errorMsg = 'Timeout: a IA demorou muito para responder. O modelo pode estar carregando — tente novamente.';
            }
            finalizarStreamComErro(errorMsg);
        });
    };

    // Se não tem conversa, criar uma antes
    if (!iaConversaAtual) {
        HelpDesk.api('POST', '/api/ia.php', {
            action: 'nova_conversa',
            contexto: 'geral',
            titulo: msg.substring(0, 60) + (msg.length > 60 ? '...' : '')
        }).then(resp => {
            if (resp.success) {
                iaConversaAtual = resp.data.id;
                document.getElementById('btnRenomear').style.display = '';
                document.getElementById('btnExcluirConversa').style.display = '';
                document.getElementById('iaChatTitulo').textContent = msg.substring(0, 60) + (msg.length > 60 ? '...' : '');
                carregarConversas();
            }
            criarEEnviar(iaConversaAtual);
        }).catch(() => criarEEnviar(null));
    } else {
        criarEEnviar(iaConversaAtual);
    }
}

function finalizarStream(content, startTime, tokens, duracaoMs) {
    clearTimeout(_renderTimer);
    const el = document.getElementById('iaStreamContent');
    if (el) {
        // Limpar ACTION blocks remanescentes e renderizar markdown final
        const cleanContent = renderStreamContent(content);
        try {
            el.innerHTML = marked.parse(cleanContent);
        } catch(e) {
            el.innerHTML = escapeHtml(cleanContent).replace(/\n/g, '<br>');
        }
        el.removeAttribute('id');

        // Marcar painel de execução como completo (se existir)
        const execPanel = document.getElementById('iaExecPanel');
        if (execPanel) execPanel.classList.add('ia-exec-completed');

        // Adicionar meta info
        const bubble = document.getElementById('iaStreamBubble');
        if (bubble) {
            const duracao = duracaoMs || (Date.now() - startTime);
            const metaDiv = document.createElement('div');
            metaDiv.className = 'ia-meta';
            metaDiv.innerHTML = `<span>${duracao}ms</span>${tokens ? ` • ${tokens} tokens` : ''}`;
            bubble.appendChild(metaDiv);

            // Botão copiar
            const copyBtn = document.createElement('button');
            copyBtn.className = 'ia-copy-btn';
            copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
            copyBtn.title = 'Copiar';
            copyBtn.onclick = () => {
                navigator.clipboard.writeText(content);
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => copyBtn.innerHTML = '<i class="fas fa-copy"></i>', 2000);
            };
            bubble.appendChild(copyBtn);
            bubble.removeAttribute('id');
        }
    }

    document.getElementById('iaTyping').style.display = 'none';
    document.getElementById('iaSendBtn').disabled = false;
    iaEnviando = false;
    document.getElementById('iaInput').focus();
}

function finalizarStreamComErro(errorMsg) {
    const el = document.getElementById('iaStreamContent');
    if (el) {
        el.innerHTML = '❌ Erro: ' + escapeHtml(errorMsg);
        el.removeAttribute('id');
    }
    document.getElementById('iaTyping').style.display = 'none';
    document.getElementById('iaSendBtn').disabled = false;
    iaEnviando = false;
    document.getElementById('iaInput').focus();
}

function enviarSugestao(texto) {
    document.getElementById('iaInput').value = texto;
    enviarMensagem();
}

// ===== INPUT HANDLING =====
function handleIaKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        enviarMensagem();
    }
}

function autoResizeTextarea(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 200) + 'px';
}

// ===== SIDEBAR =====
function toggleIaSidebar() {
    document.getElementById('iaSidebar').classList.toggle('ia-sidebar-open');
}

// ===== CONFIG (Admin) =====
function abrirConfigIA() {
    HelpDesk.api('GET', '/api/ia.php', { action: 'config' })
        .then(resp => {
            if (!resp.success) return;

            const configs = resp.data;
            let html = '<form id="formConfigIA" class="form-grid">';

            configs.forEach(c => {
                const isTextarea = c.valor.length > 80 || c.chave.includes('prompt');
                html += `<div class="form-group ${isTextarea ? 'col-span-2' : ''}">
                    <label class="form-label">${escapeHtml(c.chave)}
                        ${c.descricao ? `<small style="display:block;color:#94A3B8;font-weight:400">${escapeHtml(c.descricao)}</small>` : ''}
                    </label>
                    ${isTextarea
                        ? `<textarea name="${c.chave}" class="form-input" rows="3" style="font-family:monospace;font-size:12px">${escapeHtml(c.valor)}</textarea>`
                        : `<input type="text" name="${c.chave}" value="${escapeHtml(c.valor)}" class="form-input">`
                    }
                </div>`;
            });

            html += '</form>';

            HelpDesk.showModal('<i class="fas fa-cog"></i> Configuração da IA', html, `
                <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="salvarConfigIA()"><i class="fas fa-save"></i> Salvar</button>
            `, 'modal-lg');
        });
}

function salvarConfigIA() {
    const form = document.getElementById('formConfigIA');
    const configs = {};
    new FormData(form).forEach((v, k) => configs[k] = v);

    HelpDesk.api('POST', '/api/ia.php', { action: 'salvar_config', configs })
        .then(resp => {
            if (resp.success) {
                HelpDesk.closeModal();
                HelpDesk.toast('Configurações salvas!', 'success');
                verificarStatusIA();
            }
        });
}

// ===== GERENCIAMENTO DE MODELOS (Admin) =====
function abrirModelos() {
    HelpDesk.api('GET', '/api/ia.php', { action: 'status' })
        .then(resp => {
            if (!resp.success) return;
            const models = resp.data.models || [];
            const tarefas = resp.data.modelos_tarefa || {};
            iaModelosDisponiveis = models;
            iaModelosTarefa = tarefas;

            const tierMap = {
                'ultra-leve': { badge: '⚡ Ultra-Leve', cls: 'ia-tier-ultralight' },
                'leve': { badge: '🌿 Leve', cls: 'ia-tier-light' },
                'medio': { badge: '⭐ Médio', cls: 'ia-tier-medium' },
                'avancado': { badge: '🧠 Avançado', cls: 'ia-tier-advanced' },
                'desconhecido': { badge: 'Outro', cls: 'ia-tier-unknown' },
            };

            let totalSize = 0;
            models.forEach(m => totalSize += (m.size_bytes || 0));
            const totalGB = (totalSize / 1024 / 1024 / 1024).toFixed(1);

            let html = `
            <div class="ia-models-panel">
                <!-- KPIs -->
                <div class="ia-models-kpis">
                    <div class="ia-models-kpi">
                        <i class="fas fa-cubes" style="color:#3B82F6"></i>
                        <div><strong>${models.length}</strong><span>Modelos Instalados</span></div>
                    </div>
                    <div class="ia-models-kpi">
                        <i class="fas fa-hdd" style="color:#F59E0B"></i>
                        <div><strong>${totalGB} GB</strong><span>Espaço Total</span></div>
                    </div>
                    <div class="ia-models-kpi">
                        <i class="fas fa-check-circle" style="color:#10B981"></i>
                        <div><strong>${resp.data.online ? 'Online' : 'Offline'}</strong><span>Ollama Status</span></div>
                    </div>
                </div>

                <!-- Modelos Instalados -->
                <h4 style="margin:20px 0 12px"><i class="fas fa-layer-group"></i> Modelos Instalados</h4>
                <div class="ia-models-grid">
                    ${models.map(m => {
                        const t = tierMap[m.tier] || tierMap['desconhecido'];
                        const tasks = [];
                        Object.entries(tarefas).forEach(([k, v]) => {
                            if (v.modelo === m.name) tasks.push(v.label);
                        });
                        return `
                        <div class="ia-model-card">
                            <div class="ia-model-card-header">
                                <div class="ia-model-card-icon" style="background:${m.cor}20;color:${m.cor}">
                                    <i class="fas ${m.icone || 'fa-cube'}"></i>
                                </div>
                                <div>
                                    <strong>${m.label || m.name}</strong>
                                    <span class="ia-model-tier-badge ${t.cls}">${t.badge}</span>
                                </div>
                            </div>
                            <p class="ia-model-card-desc">${m.descricao || ''}</p>
                            <div class="ia-model-card-details">
                                <span><i class="fas fa-microchip"></i> ${m.parametros || m.parameter_size || '?'}</span>
                                <span><i class="fas fa-hdd"></i> ${m.size}</span>
                                <span><i class="fas fa-memory"></i> ${m.ram_estimada || 'N/A'}</span>
                                ${m.quantization !== 'N/A' ? `<span><i class="fas fa-compress"></i> ${m.quantization}</span>` : ''}
                            </div>
                            ${tasks.length ? `<div class="ia-model-card-tasks">${tasks.map(t => `<span class="ia-model-task-badge">${t}</span>`).join('')}</div>` : ''}
                            <div class="ia-model-card-id"><code>${m.name}</code></div>
                            <div class="ia-model-card-actions">
                                <button class="btn btn-sm btn-secondary" onclick="definirModeloTarefa('${m.name}')" title="Definir para tarefa">
                                    <i class="fas fa-tasks"></i> Atribuir
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deletarModelo('${m.name}')" title="Remover">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>`;
                    }).join('')}
                </div>

                <!-- Atribuição de Modelos por Tarefa -->
                <h4 style="margin:24px 0 12px"><i class="fas fa-tasks"></i> Modelo por Tarefa</h4>
                <div class="ia-tarefa-grid">
                    ${Object.entries(tarefas).map(([k, v]) => `
                        <div class="ia-tarefa-card">
                            <div class="ia-tarefa-icon"><i class="fas ${v.icone}"></i></div>
                            <div class="ia-tarefa-info">
                                <strong>${v.label}</strong>
                                <small>${v.descricao}</small>
                            </div>
                            <select class="form-control form-control-sm" onchange="setModeloTarefa('${k}', this.value)" style="max-width:200px">
                                ${models.map(m => `<option value="${m.name}" ${m.name === v.modelo ? 'selected' : ''}>${m.label || m.name}</option>`).join('')}
                            </select>
                        </div>
                    `).join('')}
                </div>

                <!-- Pull Novo Modelo -->
                <h4 style="margin:24px 0 12px"><i class="fas fa-download"></i> Baixar Novo Modelo</h4>
                <div class="ia-pull-section">
                    <div class="ia-pull-input-row">
                        <input type="text" class="form-control" id="iaPullModelName" placeholder="Ex: phi3:mini, gemma2:2b, mistral..." style="flex:1">
                        <button class="btn btn-primary" onclick="pullModelo()">
                            <i class="fas fa-download"></i> Baixar
                        </button>
                    </div>
                    <div id="iaPullStatus" style="margin-top:8px"></div>
                    <div class="ia-pull-suggestions">
                        <span>Sugestões:</span>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='phi3:mini'">phi3:mini</button>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='gemma2:2b'">gemma2:2b</button>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='qwen2.5:3b'">qwen2.5:3b</button>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='mistral'">mistral</button>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='tinyllama'">tinyllama</button>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='deepseek-r1:8b'">deepseek-r1:8b</button>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='llama3.1'">llama3.1</button>
                        <button class="ia-pull-tag" onclick="document.getElementById('iaPullModelName').value='codellama'">codellama</button>
                    </div>
                </div>
            </div>`;

            HelpDesk.showModal(
                '<i class="fas fa-cubes"></i> Gerenciar Modelos de IA',
                html,
                '',
                'modal-xl'
            );
        });
}

async function pullModelo() {
    const name = document.getElementById('iaPullModelName')?.value?.trim();
    if (!name) return;
    const status = document.getElementById('iaPullStatus');
    status.innerHTML = '<div style="padding:12px;background:var(--bg-secondary);border-radius:8px"><i class="fas fa-spinner fa-spin"></i> Baixando <strong>' + name + '</strong>... Isso pode levar vários minutos.</div>';

    try {
        const r = await fetch('<?= BASE_URL ?>/api/ia.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action: 'pull_modelo', nome: name })
        });
        const data = await r.json();
        if (data.success) {
            status.innerHTML = '<div style="padding:12px;background:rgba(16,185,129,.1);border-radius:8px;color:#10B981"><i class="fas fa-check-circle"></i> Modelo <strong>' + name + '</strong> baixado com sucesso!</div>';
            verificarStatusIA();
            setTimeout(() => abrirModelos(), 1500);
        } else {
            status.innerHTML = '<div style="padding:12px;background:rgba(239,68,68,.1);border-radius:8px;color:#EF4444"><i class="fas fa-times-circle"></i> Erro: ' + (data.error || 'Falha ao baixar') + '</div>';
        }
    } catch (e) {
        status.innerHTML = '<div style="padding:12px;background:rgba(239,68,68,.1);border-radius:8px;color:#EF4444"><i class="fas fa-times-circle"></i> Erro: ' + e.message + '</div>';
    }
}

async function deletarModelo(name) {
    if (!confirm(`Remover o modelo "${name}" do Ollama?\n\nO modelo será excluído do disco e precisará ser baixado novamente.`)) return;

    try {
        const r = await HelpDesk.api('POST', '/api/ia.php', { action: 'deletar_modelo', nome: name });
        if (r.success) {
            showToastIA('Modelo removido: ' + name, 'success');
            verificarStatusIA();
            setTimeout(() => abrirModelos(), 500);
        } else {
            showToastIA(r.error || 'Erro ao remover', 'error');
        }
    } catch (e) {
        showToastIA('Erro: ' + e.message, 'error');
    }
}

function definirModeloTarefa(modelo) {
    const tarefas = [
        { key: 'chat', label: '💬 Chat / Conversa' },
        { key: 'rapido', label: '⚡ Tarefas Rápidas' },
        { key: 'codigo', label: '💻 Código / SSH' },
        { key: 'analise', label: '📊 Análise / Relatórios' },
    ];

    HelpDesk.showModal(
        'Atribuir Modelo',
        `<p>Definir <strong>${modelo}</strong> para qual tarefa?</p>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:16px">
                ${tarefas.map(t => `<button class="btn btn-secondary" style="width:100%;text-align:left" onclick="setModeloTarefa('${t.key}','${modelo}');HelpDesk.closeModal()">${t.label}</button>`).join('')}
            </div>`,
        ''
    );
}

async function setModeloTarefa(tarefa, modelo) {
    try {
        const r = await HelpDesk.api('POST', '/api/ia.php', { action: 'definir_modelo_tarefa', tarefa, modelo });
        if (r.success) {
            showToastIA(r.message, 'success');
            verificarStatusIA();
            // Atualizar modal se estiver aberta
            const modalOpen = document.getElementById('modalOverlay')?.classList?.contains('active');
            if (modalOpen) setTimeout(() => abrirModelos(), 500);
        }
    } catch (e) {
        showToastIA('Erro: ' + e.message, 'error');
    }
}

// ===== UTILS =====
function escapeHtml(text) {
    if (!text) return '';
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}
</script>
