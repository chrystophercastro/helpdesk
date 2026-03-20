<?php
/**
 * View: E-mail
 * Cliente de e-mail integrado com IA
 */
?>

<div class="email-container">
    <!-- HEADER -->
    <div class="email-top-bar">
        <div class="email-top-left">
            <h2><i class="fas fa-envelope"></i> E-mail</h2>
        </div>
        <div class="email-top-right">
            <button class="btn btn-sm" onclick="emailRefresh()" id="btnEmailRefresh">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
            <button class="btn btn-primary btn-sm" onclick="abrirComposer()">
                <i class="fas fa-pen"></i> Novo E-mail
            </button>
            <button class="btn btn-sm" onclick="abrirConfigContas()">
                <i class="fas fa-cog"></i> Contas
            </button>
        </div>
    </div>

    <!-- LAYOUT: Sidebar + Content -->
    <div class="email-layout">
        <!-- SIDEBAR DE PASTAS -->
        <div class="email-sidebar" id="emailSidebar">
            <!-- Seletor de conta -->
            <div class="email-account-select">
                <select id="emailContaSelect" onchange="trocarConta()">
                    <option value="">Selecione uma conta...</option>
                </select>
            </div>

            <!-- Pastas -->
            <div class="email-folders" id="emailFolders">
                <div class="email-empty-msg">
                    <i class="fas fa-inbox"></i>
                    <p>Configure uma conta de e-mail</p>
                    <button class="btn btn-primary btn-sm" onclick="abrirConfigContas()">
                        <i class="fas fa-plus"></i> Adicionar Conta
                    </button>
                </div>
            </div>
        </div>

        <!-- LISTA DE E-MAILS -->
        <div class="email-list-panel" id="emailListPanel">
            <div class="email-list-header">
                <div class="email-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="emailBusca" placeholder="Buscar e-mails..." onkeyup="if(event.key==='Enter') buscarEmails()">
                </div>
                <div class="email-list-info" id="emailListInfo"></div>
            </div>
            <div class="email-list" id="emailList">
                <div class="email-placeholder">
                    <i class="fas fa-envelope-open-text fa-3x"></i>
                    <p>Selecione uma conta para ver seus e-mails</p>
                </div>
            </div>
            <div class="email-pagination" id="emailPagination"></div>
        </div>

        <!-- PAINEL DE LEITURA -->
        <div class="email-reader-panel" id="emailReaderPanel">
            <div class="email-reader-placeholder" id="emailReaderPlaceholder">
                <i class="fas fa-envelope fa-3x"></i>
                <p>Selecione um e-mail para ler</p>
            </div>
            <div class="email-reader-content" id="emailReaderContent" style="display:none">
                <!-- Header do email -->
                <div class="email-reader-header">
                    <div class="email-reader-actions">
                        <button class="btn btn-sm" onclick="responderEmail()" title="Responder">
                            <i class="fas fa-reply"></i> Responder
                        </button>
                        <button class="btn btn-sm" onclick="encaminharEmail()" title="Encaminhar">
                            <i class="fas fa-share"></i> Encaminhar
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="excluirEmailAtual()" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn btn-sm" onclick="toggleImportante()" id="btnFlagEmail" title="Marcar como importante">
                            <i class="fas fa-star"></i>
                        </button>
                        <button class="btn btn-sm" onclick="toggleLido()" id="btnReadEmail" title="Marcar como não lido">
                            <i class="fas fa-envelope"></i>
                        </button>
                        <div class="email-reader-actions-sep"></div>
                        <button class="btn btn-sm btn-ia-resumo" onclick="iaResumoEmail()" title="IA: Resumir e analisar">
                            <i class="fas fa-robot"></i> IA Resumir
                        </button>
                    </div>
                    <div class="email-reader-subject" id="readerSubject"></div>
                    <div class="email-reader-meta">
                        <div class="email-reader-from">
                            <div class="email-avatar" id="readerAvatar">A</div>
                            <div class="email-reader-from-info">
                                <strong id="readerFrom"></strong>
                                <span id="readerFromEmail"></span>
                            </div>
                        </div>
                        <div class="email-reader-date" id="readerDate"></div>
                    </div>
                    <div class="email-reader-to-cc">
                        <span id="readerTo"></span>
                        <span id="readerCc" style="display:none"></span>
                    </div>
                </div>

                <!-- Painel IA Resumo -->
                <div class="email-ia-panel" id="emailIaPanel" style="display:none">
                    <div class="email-ia-header">
                        <span><i class="fas fa-robot"></i> Análise da IA</span>
                        <button class="btn-close-ia" onclick="fecharIaPanel()"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="email-ia-body" id="emailIaBody">
                        <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Analisando e-mail...</p></div>
                    </div>
                </div>

                <!-- Anexos -->
                <div class="email-reader-attachments" id="readerAttachments" style="display:none"></div>

                <!-- Body -->
                <div class="email-reader-body" id="readerBody"></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Configurar Contas -->
<div class="modal-overlay" id="modalEmailContas" style="display:none" onclick="if(event.target===this) fecharModal('modalEmailContas')">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-mail-bulk"></i> Contas de E-mail</h3>
            <button class="modal-close" onclick="fecharModal('modalEmailContas')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="email-contas-layout">
                <!-- Lista de contas -->
                <div class="email-contas-list" id="emailContasList">
                    <button class="btn btn-primary btn-sm" onclick="novaContaForm()" style="width:100%;margin-bottom:12px">
                        <i class="fas fa-plus"></i> Nova Conta
                    </button>
                    <div id="contasListItems"></div>
                </div>
                <!-- Formulário Wizard -->
                <div class="email-conta-form" id="emailContaForm" style="display:none">

                    <!-- ===== STEP 1: E-mail + Autodiscover ===== -->
                    <div id="contaStep1">
                        <h4 id="contaFormTitle">Nova Conta</h4>
                        <input type="hidden" id="contaId" value="">
                        <p style="color:#64748b;font-size:0.88rem;margin-bottom:16px">
                            Digite seu e-mail e clique em <strong>Detectar Configurações</strong>. O sistema tentará encontrar as configurações IMAP/SMTP automaticamente, como o Outlook faz.
                        </p>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome da Conta</label>
                                <input type="text" id="contaNome" placeholder="Ex: Trabalho, Pessoal..." class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Endereço de E-mail *</label>
                                <input type="email" id="contaEmail" placeholder="seu@email.com" class="form-control">
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top:16px;gap:8px">
                            <button class="btn btn-primary" onclick="autodiscoverEmail()" id="btnAutodiscover">
                                <i class="fas fa-magic"></i> Detectar Configurações
                            </button>
                            <button class="btn btn-sm" onclick="mostrarConfigManual()" style="opacity:0.7">
                                <i class="fas fa-cog"></i> Configurar Manualmente
                            </button>
                        </div>

                        <!-- Resultado Autodiscover -->
                        <div id="autodiscoverResult" style="display:none;margin-top:16px">
                        </div>
                    </div>

                    <!-- ===== STEP 2: Config Servidor (auto-preenchido ou manual) ===== -->
                    <div id="contaStep2" style="display:none">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                            <button class="btn btn-sm" onclick="voltarStep1()" title="Voltar">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <h4 style="margin:0" id="step2Title">Configurações do Servidor</h4>
                        </div>

                        <!-- Provider badge -->
                        <div id="providerBadge" style="display:none;margin-bottom:12px">
                        </div>

                        <div class="form-divider">Servidor de Entrada (IMAP)</div>
                        <div class="form-row form-row-3">
                            <div class="form-group">
                                <label>Host IMAP *</label>
                                <input type="text" id="contaImapHost" placeholder="imap.exemplo.com" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Porta</label>
                                <input type="number" id="contaImapPorta" value="993" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Segurança</label>
                                <select id="contaImapSeg" class="form-control">
                                    <option value="ssl" selected>SSL</option>
                                    <option value="tls">TLS</option>
                                    <option value="none">Nenhuma</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-divider">Servidor de Saída (SMTP)</div>
                        <div class="form-row form-row-3">
                            <div class="form-group">
                                <label>Host SMTP *</label>
                                <input type="text" id="contaSmtpHost" placeholder="smtp.exemplo.com" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Porta</label>
                                <input type="number" id="contaSmtpPorta" value="587" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Segurança</label>
                                <select id="contaSmtpSeg" class="form-control">
                                    <option value="tls" selected>TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="none">Nenhuma</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-divider">Autenticação</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Usuário (login) *</label>
                                <input type="text" id="contaUsuarioEmail" placeholder="seu@email.com" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Senha *</label>
                                <input type="password" id="contaSenhaEmail" placeholder="Senha ou App Password" class="form-control">
                            </div>
                        </div>

                        <!-- Nota do provedor -->
                        <div id="providerNote" style="display:none;padding:10px 14px;border-radius:8px;background:#FEF3C7;color:#92400E;font-size:0.85rem;margin-bottom:12px">
                            <i class="fas fa-info-circle"></i> <span id="providerNoteText"></span>
                        </div>

                        <div class="form-actions" style="margin-top:16px">
                            <button class="btn" onclick="testarConexaoForm()" id="btnTestarConexao">
                                <i class="fas fa-plug"></i> Testar Conexão
                            </button>
                            <button class="btn btn-primary" onclick="salvarContaForm()" id="btnSalvarConta">
                                <i class="fas fa-save"></i> Salvar
                            </button>
                            <button class="btn btn-danger" onclick="excluirContaForm()" id="btnExcluirConta" style="display:none">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Composer (Novo e-mail / Responder / Encaminhar) -->
<div class="modal-overlay" id="modalComposer" style="display:none" onclick="if(event.target===this) fecharModal('modalComposer')">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="composerTitle"><i class="fas fa-pen"></i> Novo E-mail</h3>
            <button class="modal-close" onclick="fecharModal('modalComposer')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="composerReplyTo" value="">
            <input type="hidden" id="composerInReplyTo" value="">

            <div class="form-group">
                <label>De:</label>
                <select id="composerFrom" class="form-control"></select>
            </div>
            <div class="form-group">
                <label>Para: *</label>
                <input type="text" id="composerTo" class="form-control" placeholder="destinatario@email.com">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Cc:</label>
                    <input type="text" id="composerCc" class="form-control" placeholder="copia@email.com">
                </div>
                <div class="form-group">
                    <label>Cco:</label>
                    <input type="text" id="composerBcc" class="form-control" placeholder="copia.oculta@email.com">
                </div>
            </div>
            <div class="form-group">
                <label>Assunto: *</label>
                <input type="text" id="composerSubject" class="form-control" placeholder="Assunto do e-mail">
            </div>
            <div class="form-group">
                <label>Mensagem:</label>
                <div class="composer-toolbar">
                    <button type="button" onclick="execComposerCmd('bold')" title="Negrito"><i class="fas fa-bold"></i></button>
                    <button type="button" onclick="execComposerCmd('italic')" title="Itálico"><i class="fas fa-italic"></i></button>
                    <button type="button" onclick="execComposerCmd('underline')" title="Sublinhado"><i class="fas fa-underline"></i></button>
                    <span class="toolbar-sep"></span>
                    <button type="button" onclick="execComposerCmd('insertUnorderedList')" title="Lista"><i class="fas fa-list-ul"></i></button>
                    <button type="button" onclick="execComposerCmd('insertOrderedList')" title="Lista numerada"><i class="fas fa-list-ol"></i></button>
                    <span class="toolbar-sep"></span>
                    <button type="button" onclick="execComposerLink()" title="Link"><i class="fas fa-link"></i></button>
                </div>
                <div id="composerBody" contenteditable="true" class="composer-body"></div>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" onclick="enviarComposer()" id="btnEnviarEmail">
                    <i class="fas fa-paper-plane"></i> Enviar
                </button>
                <button class="btn" onclick="fecharModal('modalComposer')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
// ==========================================
//  VARIÁVEIS GLOBAIS
// ==========================================
let emailContas = [];
let emailContaAtiva = null;
let emailPastas = [];
let emailPastaAtiva = 'INBOX';
let emailPage = 1;
let emailAtual = null; // email aberto no reader
let emailBuscaTimeout = null;

const EMAIL_API = '<?= BASE_URL ?>/api/email.php';

// ==========================================
//  HELPERS API
// ==========================================
async function emailApiGet(action, params = {}) {
    params.action = action;
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(`${EMAIL_API}?${qs}`);
    const json = await res.json();
    if (json.error) throw new Error(json.error);
    return json;
}

async function emailApiPost(action, data = {}) {
    data.action = action;
    const res = await fetch(EMAIL_API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.error) throw new Error(json.error);
    return json;
}

function emailShowToast(msg, type = 'success') {
    if (typeof showToast === 'function') {
        showToast(msg, type);
    } else {
        alert(msg);
    }
}

function fecharModal(id) {
    document.getElementById(id).style.display = 'none';
}
function abrirModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function formatEmailDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const today = now.toDateString();
    const emailDay = d.toDateString();

    if (today === emailDay) {
        return d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
    }

    const diffDays = Math.floor((now - d) / 86400000);
    if (diffDays < 7) {
        return d.toLocaleDateString('pt-BR', {weekday: 'short', hour: '2-digit', minute: '2-digit'});
    }

    return d.toLocaleDateString('pt-BR', {day: '2-digit', month: '2-digit', year: '2-digit'});
}

function formatEmailSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function getInitials(name) {
    return (name || '?').charAt(0).toUpperCase();
}

// ==========================================
//  INICIALIZAÇÃO
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    carregarContas();
});

// ==========================================
//  CONTAS
// ==========================================
async function carregarContas() {
    try {
        const res = await emailApiGet('listar_contas');
        emailContas = res.data || [];

        const sel = document.getElementById('emailContaSelect');
        sel.innerHTML = emailContas.length
            ? emailContas.map(c => `<option value="${c.id}">${c.nome_conta} (${c.email})</option>`).join('')
            : '<option value="">Nenhuma conta configurada</option>';

        // Popular composer select também
        const composerFrom = document.getElementById('composerFrom');
        composerFrom.innerHTML = emailContas.map(c => `<option value="${c.id}">${c.nome_conta} &lt;${c.email}&gt;</option>`).join('');

        if (emailContas.length) {
            emailContaAtiva = emailContas[0].id;
            sel.value = emailContaAtiva;
            carregarPastas();
        }
    } catch(e) {
        console.error('Erro ao carregar contas:', e);
    }
}

function trocarConta() {
    const sel = document.getElementById('emailContaSelect');
    emailContaAtiva = sel.value;
    if (emailContaAtiva) {
        carregarPastas();
    }
}

// ==========================================
//  PASTAS
// ==========================================
async function carregarPastas() {
    if (!emailContaAtiva) return;
    const container = document.getElementById('emailFolders');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin"></i></div>';

    try {
        const res = await emailApiGet('listar_pastas', {conta_id: emailContaAtiva});
        emailPastas = res.data || [];

        if (!emailPastas.length) {
            container.innerHTML = '<div class="email-empty-msg"><p>Nenhuma pasta encontrada</p></div>';
            return;
        }

        // Ordenar: INBOX primeiro, depois o resto
        emailPastas.sort((a, b) => {
            if (a.name === 'INBOX') return -1;
            if (b.name === 'INBOX') return 1;
            return a.label.localeCompare(b.label);
        });

        container.innerHTML = emailPastas.map(p => `
            <div class="email-folder-item ${p.name === emailPastaAtiva ? 'active' : ''}"
                 onclick="selecionarPasta('${encodeURIComponent(p.name)}')" data-folder="${p.name}">
                <i class="fas ${p.icon}"></i>
                <span>${p.label}</span>
                <span class="email-folder-badge" id="badge-${encodeURIComponent(p.name)}"></span>
            </div>
        `).join('');

        // Carregar e-mails da pasta ativa
        carregarEmails();

        // Contar não lidos na inbox
        contarNaoLidos();

    } catch(e) {
        const isAuth = e.message && (e.message.toLowerCase().includes('autentica') || e.message.toLowerCase().includes('senha'));
        container.innerHTML = `
            <div class="email-empty-msg">
                <i class="fas fa-exclamation-triangle" style="color:#EF4444;font-size:1.5rem"></i>
                <p style="font-size:0.88rem;color:#991B1B;margin:8px 0 4px">${escapeHtml(e.message)}</p>
                <button class="btn btn-sm btn-primary" onclick="abrirConfigContas()" style="margin-top:8px">
                    <i class="fas fa-cog"></i> ${isAuth ? 'Corrigir Senha' : 'Configurações'}
                </button>
            </div>`;
    }
}

function selecionarPasta(folderEncoded) {
    emailPastaAtiva = decodeURIComponent(folderEncoded);
    emailPage = 1;

    // Atualizar active state
    document.querySelectorAll('.email-folder-item').forEach(el => {
        el.classList.toggle('active', el.dataset.folder === emailPastaAtiva);
    });

    carregarEmails();
}

async function contarNaoLidos() {
    try {
        const res = await emailApiGet('contar_nao_lidos', {conta_id: emailContaAtiva, folder: 'INBOX'});
        const count = res.data?.count || 0;
        const badge = document.getElementById('badge-INBOX');
        if (badge) {
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    } catch(e) {}
}

// ==========================================
//  LISTA DE E-MAILS
// ==========================================
async function carregarEmails() {
    if (!emailContaAtiva) return;

    const list = document.getElementById('emailList');
    list.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando e-mails...</p></div>';

    const busca = document.getElementById('emailBusca').value;

    try {
        const res = await emailApiGet('listar_emails', {
            conta_id: emailContaAtiva,
            folder: emailPastaAtiva,
            page: emailPage,
            per_page: 30,
            busca
        });

        const data = res.data;
        const emails = data.emails || [];

        // Info
        document.getElementById('emailListInfo').textContent =
            `${data.total} e-mail(s) — Página ${data.page} de ${data.pages || 1}`;

        if (!emails.length) {
            list.innerHTML = '<div class="email-placeholder"><i class="fas fa-inbox fa-3x"></i><p>Nenhum e-mail nesta pasta</p></div>';
            document.getElementById('emailPagination').innerHTML = '';
            return;
        }

        list.innerHTML = emails.map(em => `
            <div class="email-item ${em.seen ? '' : 'email-unread'} ${emailAtual && emailAtual.uid === em.uid ? 'email-selected' : ''}"
                 onclick="abrirEmail(${em.uid})" data-uid="${em.uid}">
                <div class="email-item-flag">
                    ${em.flagged ? '<i class="fas fa-star email-star-on"></i>' : '<i class="far fa-star"></i>'}
                </div>
                <div class="email-item-avatar">${getInitials(em.from)}</div>
                <div class="email-item-content">
                    <div class="email-item-top">
                        <span class="email-item-from">${escapeHtml(em.from)}</span>
                        <span class="email-item-date">${formatEmailDate(em.date)}</span>
                    </div>
                    <div class="email-item-subject">${escapeHtml(em.subject)}</div>
                </div>
                ${em.has_attachment ? '<div class="email-item-attach"><i class="fas fa-paperclip"></i></div>' : ''}
            </div>
        `).join('');

        // Paginação
        renderEmailPagination(data.page, data.pages);

    } catch(e) {
        list.innerHTML = `<div class="email-placeholder"><i class="fas fa-exclamation-triangle fa-3x" style="color:#EF4444"></i><p>${e.message}</p></div>`;
    }
}

function renderEmailPagination(page, pages) {
    const container = document.getElementById('emailPagination');
    if (pages <= 1) { container.innerHTML = ''; return; }

    let html = '';
    if (page > 1) html += `<button class="btn btn-sm" onclick="emailPage=${page-1};carregarEmails()"><i class="fas fa-chevron-left"></i></button>`;

    const start = Math.max(1, page - 2);
    const end = Math.min(pages, page + 2);
    for (let i = start; i <= end; i++) {
        html += `<button class="btn btn-sm ${i===page?'btn-primary':''}" onclick="emailPage=${i};carregarEmails()">${i}</button>`;
    }

    if (page < pages) html += `<button class="btn btn-sm" onclick="emailPage=${page+1};carregarEmails()"><i class="fas fa-chevron-right"></i></button>`;

    container.innerHTML = html;
}

function buscarEmails() {
    emailPage = 1;
    carregarEmails();
}

function emailRefresh() {
    const btn = document.getElementById('btnEmailRefresh');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
    btn.disabled = true;

    carregarEmails().then(() => {
        contarNaoLidos();
    }).finally(() => {
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Atualizar';
        btn.disabled = false;
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text || ''));
    return div.innerHTML;
}

// ==========================================
//  LER E-MAIL
// ==========================================
async function abrirEmail(uid) {
    const placeholder = document.getElementById('emailReaderPlaceholder');
    const content = document.getElementById('emailReaderContent');

    placeholder.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>';
    placeholder.style.display = 'flex';
    content.style.display = 'none';

    // Highlight na lista
    document.querySelectorAll('.email-item').forEach(el => {
        el.classList.toggle('email-selected', el.dataset.uid == uid);
    });

    try {
        const res = await emailApiGet('ler_email', {
            conta_id: emailContaAtiva,
            uid: uid,
            folder: emailPastaAtiva
        });

        emailAtual = res.data;
        emailAtual.uid = uid;

        // Marcar como lido na lista
        const listItem = document.querySelector(`.email-item[data-uid="${uid}"]`);
        if (listItem) listItem.classList.remove('email-unread');

        renderEmailReader();

        placeholder.style.display = 'none';
        content.style.display = 'flex';

        // Fechar painel IA se aberto
        fecharIaPanel();

    } catch(e) {
        placeholder.innerHTML = `<div class="email-placeholder"><i class="fas fa-exclamation-triangle fa-3x" style="color:#EF4444"></i><p>${e.message}</p></div>`;
    }
}

function renderEmailReader() {
    const em = emailAtual;
    if (!em) return;

    document.getElementById('readerSubject').textContent = em.subject;
    document.getElementById('readerFrom').textContent = em.from;
    document.getElementById('readerFromEmail').textContent = `<${em.from_email}>`;
    document.getElementById('readerDate').textContent = em.date;
    document.getElementById('readerAvatar').textContent = getInitials(em.from);
    document.getElementById('readerTo').innerHTML = '<strong>Para:</strong> ' + escapeHtml(em.to);

    if (em.cc) {
        document.getElementById('readerCc').innerHTML = '<strong>Cc:</strong> ' + escapeHtml(em.cc);
        document.getElementById('readerCc').style.display = 'inline';
    } else {
        document.getElementById('readerCc').style.display = 'none';
    }

    // Flag/Read buttons
    document.getElementById('btnFlagEmail').classList.toggle('btn-active', em.flagged);
    document.getElementById('btnReadEmail').title = em.seen ? 'Marcar como não lido' : 'Marcar como lido';

    // Anexos
    const attachDiv = document.getElementById('readerAttachments');
    if (em.attachments && em.attachments.length) {
        attachDiv.style.display = 'block';
        attachDiv.innerHTML = '<div class="email-attachments-label"><i class="fas fa-paperclip"></i> Anexos (' + em.attachments.length + '):</div>' +
            em.attachments.map(a =>
                `<a class="email-attachment-item" href="${EMAIL_API}?action=baixar_anexo&conta_id=${emailContaAtiva}&uid=${em.uid}&part=${a.partNum}&folder=${encodeURIComponent(emailPastaAtiva)}" target="_blank">
                    <i class="fas fa-file"></i>
                    <span>${escapeHtml(a.filename)}</span>
                    <small>${formatEmailSize(a.size)}</small>
                </a>`
            ).join('');
    } else {
        attachDiv.style.display = 'none';
    }

    // Body — usar iframe para isolar CSS
    const bodyContainer = document.getElementById('readerBody');
    const bodyHtml = em.body_html || '<pre>' + escapeHtml(em.body_text) + '</pre>';

    const iframeStyles = `
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; color: #1a1a2e; margin: 0; padding: 8px; word-wrap: break-word; line-height: 1.6; background: transparent; }
        img { max-width: 100%; height: auto; }
        a { color: #3B82F6; }
        pre { white-space: pre-wrap; font-family: inherit; }
        table { max-width: 100%; }
        blockquote { margin: 8px 0; padding-left: 12px; border-left: 3px solid #ddd; color: #666; }
    `;

    const fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' + iframeStyles + '</style></head><body>' + bodyHtml + '</body></html>';

    // Usar Blob URL para evitar problemas com caracteres especiais no conteúdo HTML
    const blob = new Blob([fullHtml], {type: 'text/html; charset=utf-8'});
    const blobUrl = URL.createObjectURL(blob);

    bodyContainer.innerHTML = '';
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'width:100%;border:none;min-height:300px';
    iframe.sandbox = 'allow-same-origin';
    iframe.onload = function() {
        // Auto-resize iframe
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            iframe.style.height = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight, 300) + 20 + 'px';
        } catch(e) {}
        URL.revokeObjectURL(blobUrl);
    };
    iframe.src = blobUrl;
    bodyContainer.appendChild(iframe);
}

// ==========================================
//  AÇÕES DE E-MAIL
// ==========================================
async function excluirEmailAtual() {
    if (!emailAtual) return;
    if (!confirm('Excluir este e-mail?')) return;

    try {
        await emailApiPost('excluir_email', {
            conta_id: emailContaAtiva,
            uid: emailAtual.uid,
            folder: emailPastaAtiva
        });
        emailShowToast('E-mail excluído');
        emailAtual = null;
        document.getElementById('emailReaderContent').style.display = 'none';
        document.getElementById('emailReaderPlaceholder').style.display = 'flex';
        document.getElementById('emailReaderPlaceholder').innerHTML = '<i class="fas fa-envelope fa-3x"></i><p>Selecione um e-mail para ler</p>';
        carregarEmails();
    } catch(e) {
        emailShowToast('Erro: ' + e.message, 'error');
    }
}

async function toggleImportante() {
    if (!emailAtual) return;
    const newState = !emailAtual.flagged;

    try {
        await emailApiPost('marcar_importante', {
            conta_id: emailContaAtiva,
            uid: emailAtual.uid,
            flagged: newState,
            folder: emailPastaAtiva
        });
        emailAtual.flagged = newState;
        document.getElementById('btnFlagEmail').classList.toggle('btn-active', newState);
    } catch(e) {
        emailShowToast('Erro: ' + e.message, 'error');
    }
}

async function toggleLido() {
    if (!emailAtual) return;
    const newState = !emailAtual.seen;

    try {
        await emailApiPost('marcar_lido', {
            conta_id: emailContaAtiva,
            uid: emailAtual.uid,
            lido: newState,
            folder: emailPastaAtiva
        });
        emailAtual.seen = newState;
        carregarEmails();
    } catch(e) {
        emailShowToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  COMPOSER (Novo / Responder / Encaminhar)
// ==========================================
function abrirComposer() {
    document.getElementById('composerTitle').innerHTML = '<i class="fas fa-pen"></i> Novo E-mail';
    document.getElementById('composerTo').value = '';
    document.getElementById('composerCc').value = '';
    document.getElementById('composerBcc').value = '';
    document.getElementById('composerSubject').value = '';
    document.getElementById('composerBody').innerHTML = '';
    document.getElementById('composerReplyTo').value = '';
    document.getElementById('composerInReplyTo').value = '';

    if (emailContaAtiva) {
        document.getElementById('composerFrom').value = emailContaAtiva;
    }

    abrirModal('modalComposer');
}

function responderEmail() {
    if (!emailAtual) return;
    document.getElementById('composerTitle').innerHTML = '<i class="fas fa-reply"></i> Responder';
    document.getElementById('composerTo').value = emailAtual.from_email;
    document.getElementById('composerCc').value = '';
    document.getElementById('composerBcc').value = '';
    document.getElementById('composerSubject').value = 'Re: ' + emailAtual.subject.replace(/^Re:\s*/i, '');
    document.getElementById('composerReplyTo').value = emailAtual.from_email;
    document.getElementById('composerInReplyTo').value = emailAtual.uid;

    const quoteDate = emailAtual.date;
    const quoteFrom = emailAtual.from + ' <' + emailAtual.from_email + '>';
    document.getElementById('composerBody').innerHTML =
        '<br><br><div style="border-left:3px solid #ccc;padding-left:12px;color:#666">' +
        '<p>Em ' + quoteDate + ', ' + escapeHtml(quoteFrom) + ' escreveu:</p>' +
        (emailAtual.body_html || '<pre>' + escapeHtml(emailAtual.body_text) + '</pre>') +
        '</div>';

    if (emailContaAtiva) document.getElementById('composerFrom').value = emailContaAtiva;
    abrirModal('modalComposer');
}

function encaminharEmail() {
    if (!emailAtual) return;
    document.getElementById('composerTitle').innerHTML = '<i class="fas fa-share"></i> Encaminhar';
    document.getElementById('composerTo').value = '';
    document.getElementById('composerCc').value = '';
    document.getElementById('composerBcc').value = '';
    document.getElementById('composerSubject').value = 'Fwd: ' + emailAtual.subject.replace(/^Fwd:\s*/i, '');
    document.getElementById('composerReplyTo').value = '';
    document.getElementById('composerInReplyTo').value = '';

    document.getElementById('composerBody').innerHTML =
        '<br><br><div style="border-top:1px solid #ccc;padding-top:12px">' +
        '<p><strong>---------- Mensagem encaminhada ----------</strong></p>' +
        '<p>De: ' + escapeHtml(emailAtual.from + ' <' + emailAtual.from_email + '>') + '</p>' +
        '<p>Data: ' + emailAtual.date + '</p>' +
        '<p>Assunto: ' + escapeHtml(emailAtual.subject) + '</p>' +
        '<p>Para: ' + escapeHtml(emailAtual.to) + '</p>' +
        '<br>' +
        (emailAtual.body_html || '<pre>' + escapeHtml(emailAtual.body_text) + '</pre>') +
        '</div>';

    if (emailContaAtiva) document.getElementById('composerFrom').value = emailContaAtiva;
    abrirModal('modalComposer');
}

async function enviarComposer() {
    const contaId = document.getElementById('composerFrom').value;
    const to = document.getElementById('composerTo').value.trim();
    const subject = document.getElementById('composerSubject').value.trim();
    const body = document.getElementById('composerBody').innerHTML;

    if (!contaId || !to || !subject) {
        emailShowToast('Preencha destinatário e assunto', 'error');
        return;
    }

    const btn = document.getElementById('btnEnviarEmail');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
        await emailApiPost('enviar_email', {
            conta_id: contaId,
            to,
            subject,
            body,
            cc: document.getElementById('composerCc').value.trim(),
            bcc: document.getElementById('composerBcc').value.trim(),
            reply_to: document.getElementById('composerReplyTo').value,
            in_reply_to: document.getElementById('composerInReplyTo').value,
        });
        emailShowToast('E-mail enviado com sucesso!');
        fecharModal('modalComposer');
    } catch(e) {
        emailShowToast('Erro ao enviar: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
    }
}

function execComposerCmd(cmd) {
    document.execCommand(cmd, false, null);
    document.getElementById('composerBody').focus();
}

function execComposerLink() {
    const url = prompt('URL do link:', 'https://');
    if (url) document.execCommand('createLink', false, url);
}

// ==========================================
//  CONFIG CONTAS
// ==========================================
async function abrirConfigContas() {
    abrirModal('modalEmailContas');
    document.getElementById('emailContaForm').style.display = 'none';
    await renderContasList();
}

async function renderContasList() {
    const container = document.getElementById('contasListItems');
    try {
        const res = await emailApiGet('listar_contas');
        emailContas = res.data || [];

        if (!emailContas.length) {
            container.innerHTML = '<div class="email-empty-msg"><p>Nenhuma conta configurada</p></div>';
            return;
        }

        container.innerHTML = emailContas.map(c => `
            <div class="email-conta-item" onclick="editarConta(${c.id})">
                <div class="email-conta-item-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="email-conta-item-info">
                    <strong>${escapeHtml(c.nome_conta)}</strong>
                    <span>${escapeHtml(c.email)}</span>
                    <small>${c.imap_host}:${c.imap_porta}</small>
                </div>
                <div class="email-conta-item-status">
                    <span class="pve-badge ${c.ativo ? 'pve-badge-success' : 'pve-badge-danger'}">${c.ativo ? 'Ativo' : 'Inativo'}</span>
                </div>
            </div>
        `).join('');
    } catch(e) {
        container.innerHTML = `<div class="email-empty-msg"><p>Erro: ${e.message}</p></div>`;
    }
}

function novaContaForm() {
    document.getElementById('contaFormTitle').textContent = 'Nova Conta';
    document.getElementById('contaId').value = '';
    document.getElementById('contaNome').value = '';
    document.getElementById('contaEmail').value = '';
    document.getElementById('contaImapHost').value = '';
    document.getElementById('contaImapPorta').value = '993';
    document.getElementById('contaImapSeg').value = 'ssl';
    document.getElementById('contaSmtpHost').value = '';
    document.getElementById('contaSmtpPorta').value = '587';
    document.getElementById('contaSmtpSeg').value = 'tls';
    document.getElementById('contaUsuarioEmail').value = '';
    document.getElementById('contaSenhaEmail').value = '';
    document.getElementById('btnExcluirConta').style.display = 'none';
    document.getElementById('autodiscoverResult').style.display = 'none';
    document.getElementById('autodiscoverResult').innerHTML = '';
    document.getElementById('providerBadge').style.display = 'none';
    document.getElementById('providerNote').style.display = 'none';
    // Mostrar step 1, esconder step 2
    document.getElementById('contaStep1').style.display = 'block';
    document.getElementById('contaStep2').style.display = 'none';
    document.getElementById('emailContaForm').style.display = 'block';
}

async function editarConta(contaId) {
    try {
        const res = await emailApiGet('get_conta', {conta_id: contaId});
        const c = res.data;

        document.getElementById('contaFormTitle').textContent = 'Editar Conta';
        document.getElementById('contaId').value = c.id;
        document.getElementById('contaNome').value = c.nome_conta;
        document.getElementById('contaEmail').value = c.email;
        document.getElementById('contaImapHost').value = c.imap_host;
        document.getElementById('contaImapPorta').value = c.imap_porta;
        document.getElementById('contaImapSeg').value = c.imap_seguranca;
        document.getElementById('contaSmtpHost').value = c.smtp_host;
        document.getElementById('contaSmtpPorta').value = c.smtp_porta;
        document.getElementById('contaSmtpSeg').value = c.smtp_seguranca;
        document.getElementById('contaUsuarioEmail').value = c.usuario_email;
        document.getElementById('contaSenhaEmail').value = '';
        document.getElementById('btnExcluirConta').style.display = 'inline-flex';
        document.getElementById('providerBadge').style.display = 'none';
        document.getElementById('providerNote').style.display = 'none';
        // Edição: ir direto para step 2
        document.getElementById('contaStep1').style.display = 'none';
        document.getElementById('contaStep2').style.display = 'block';
        document.getElementById('step2Title').textContent = 'Editar Conta - ' + c.nome_conta;
        document.getElementById('emailContaForm').style.display = 'block';
    } catch(e) {
        emailShowToast('Erro: ' + e.message, 'error');
    }
}

// ===== AUTODISCOVER =====
async function autodiscoverEmail() {
    const emailVal = document.getElementById('contaEmail').value.trim();
    if (!emailVal || !emailVal.includes('@')) {
        emailShowToast('Digite um endereço de e-mail válido', 'error');
        return;
    }

    const btn = document.getElementById('btnAutodiscover');
    const resultDiv = document.getElementById('autodiscoverResult');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Detectando...';
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = `
        <div style="padding:20px;text-align:center;color:#64748b">
            <i class="fas fa-spinner fa-spin fa-2x" style="margin-bottom:8px"></i>
            <p style="margin:0">Procurando configurações para <strong>${escapeHtml(emailVal)}</strong>...</p>
            <small>Consultando provedores conhecidos, MX, autoconfig, autodiscover...</small>
        </div>`;

    try {
        const res = await emailApiPost('autodiscover', { email: emailVal });
        const data = res.data;

        if (data.found) {
            const methodLabels = {
                'known_provider': 'Provedor Conhecido',
                'mx_lookup': 'Registro MX',
                'mozilla_autoconfig': 'Mozilla Autoconfig',
                'microsoft_autodiscover': 'Microsoft Autodiscover',
                'port_probing': 'Detecção de Portas'
            };
            const methodLabel = methodLabels[data.method] || data.method;
            const providerName = data.provider || 'Servidor de E-mail';

            resultDiv.innerHTML = `
                <div style="padding:16px;border-radius:10px;background:#F0FDF4;border:1px solid #BBF7D0">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                        <div style="width:36px;height:36px;border-radius:50%;background:#10B981;color:#fff;display:flex;align-items:center;justify-content:center">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <strong style="color:#166534;font-size:1.05rem">Configuração encontrada!</strong>
                            <div style="color:#15803D;font-size:0.8rem">${escapeHtml(providerName)} — via ${escapeHtml(methodLabel)}</div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.85rem;color:#374151">
                        <div><i class="fas fa-download" style="color:#3B82F6;width:16px"></i> IMAP: <strong>${escapeHtml(data.config.imap_host)}:${data.config.imap_porta}</strong> (${data.config.imap_seguranca.toUpperCase()})</div>
                        <div><i class="fas fa-upload" style="color:#F59E0B;width:16px"></i> SMTP: <strong>${escapeHtml(data.config.smtp_host)}:${data.config.smtp_porta}</strong> (${data.config.smtp_seguranca.toUpperCase()})</div>
                    </div>
                    ${data.note ? `<div style="margin-top:8px;padding:8px 12px;border-radius:6px;background:#FEF3C7;color:#92400E;font-size:0.82rem"><i class="fas fa-lightbulb"></i> ${escapeHtml(data.note)}</div>` : ''}
                    <div style="margin-top:12px;display:flex;gap:8px">
                        <button class="btn btn-primary btn-sm" onclick="aplicarAutodiscover()">
                            <i class="fas fa-check"></i> Usar estas configurações
                        </button>
                        <button class="btn btn-sm" onclick="mostrarConfigManual()">
                            <i class="fas fa-edit"></i> Editar manualmente
                        </button>
                    </div>
                </div>`;

            // Guardar resultado para aplicar depois
            window._autodiscoverResult = data;
        } else {
            resultDiv.innerHTML = `
                <div style="padding:16px;border-radius:10px;background:#FEF2F2;border:1px solid #FECACA">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                        <div style="width:36px;height:36px;border-radius:50%;background:#EF4444;color:#fff;display:flex;align-items:center;justify-content:center">
                            <i class="fas fa-times"></i>
                        </div>
                        <div>
                            <strong style="color:#991B1B">Configuração não encontrada</strong>
                            <div style="color:#DC2626;font-size:0.8rem">Não foi possível detectar automaticamente</div>
                        </div>
                    </div>
                    <p style="font-size:0.85rem;color:#374151;margin:0 0 12px">
                        O domínio <strong>${escapeHtml(emailVal.split('@')[1])}</strong> não foi reconhecido. Você precisará configurar os servidores IMAP/SMTP manualmente.
                    </p>
                    <button class="btn btn-primary btn-sm" onclick="mostrarConfigManual()">
                        <i class="fas fa-cog"></i> Configurar Manualmente
                    </button>
                </div>`;
            window._autodiscoverResult = null;
        }
    } catch(e) {
        resultDiv.innerHTML = `
            <div style="padding:16px;border-radius:10px;background:#FEF2F2;border:1px solid #FECACA">
                <div style="display:flex;align-items:center;gap:10px">
                    <i class="fas fa-exclamation-triangle" style="color:#EF4444"></i>
                    <span style="color:#991B1B">Erro: ${escapeHtml(e.message)}</span>
                </div>
                <button class="btn btn-sm" onclick="mostrarConfigManual()" style="margin-top:8px">
                    <i class="fas fa-cog"></i> Configurar Manualmente
                </button>
            </div>`;
        window._autodiscoverResult = null;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic"></i> Detectar Configurações';
    }
}

function aplicarAutodiscover() {
    const data = window._autodiscoverResult;
    if (!data || !data.config) return;

    const cfg = data.config;
    document.getElementById('contaImapHost').value = cfg.imap_host;
    document.getElementById('contaImapPorta').value = cfg.imap_porta;
    document.getElementById('contaImapSeg').value = cfg.imap_seguranca;
    document.getElementById('contaSmtpHost').value = cfg.smtp_host;
    document.getElementById('contaSmtpPorta').value = cfg.smtp_porta;
    document.getElementById('contaSmtpSeg').value = cfg.smtp_seguranca;

    // Preencher usuário com o e-mail
    const emailVal = document.getElementById('contaEmail').value.trim();
    document.getElementById('contaUsuarioEmail').value = emailVal;

    // Auto-preencher nome da conta se vazio
    if (!document.getElementById('contaNome').value.trim()) {
        document.getElementById('contaNome').value = data.provider || emailVal.split('@')[1];
    }

    // Provider badge
    if (data.provider) {
        const badge = document.getElementById('providerBadge');
        badge.style.display = 'block';
        badge.innerHTML = `
            <div style="padding:8px 14px;border-radius:8px;background:#EFF6FF;border:1px solid #BFDBFE;display:flex;align-items:center;gap:8px">
                <i class="fas fa-check-circle" style="color:#3B82F6"></i>
                <span style="color:#1E40AF;font-size:0.85rem">
                    <strong>${escapeHtml(data.provider)}</strong> — configuração automática aplicada
                </span>
            </div>`;
    }

    // Provider note
    if (data.note) {
        document.getElementById('providerNote').style.display = 'block';
        document.getElementById('providerNoteText').textContent = data.note;
    } else {
        document.getElementById('providerNote').style.display = 'none';
    }

    // Ir para step 2
    document.getElementById('contaStep1').style.display = 'none';
    document.getElementById('contaStep2').style.display = 'block';
    document.getElementById('step2Title').textContent = 'Confirme e digite sua senha';

    // Focar no campo de senha
    setTimeout(() => document.getElementById('contaSenhaEmail').focus(), 200);
}

function mostrarConfigManual() {
    // Preencher usuário com o e-mail se vazio
    const emailVal = document.getElementById('contaEmail').value.trim();
    if (emailVal && !document.getElementById('contaUsuarioEmail').value) {
        document.getElementById('contaUsuarioEmail').value = emailVal;
    }
    if (emailVal && !document.getElementById('contaNome').value.trim()) {
        document.getElementById('contaNome').value = emailVal.split('@')[1] || 'Meu E-mail';
    }

    document.getElementById('providerBadge').style.display = 'none';
    document.getElementById('providerNote').style.display = 'none';
    document.getElementById('contaStep1').style.display = 'none';
    document.getElementById('contaStep2').style.display = 'block';
    document.getElementById('step2Title').textContent = 'Configuração Manual';
}

function voltarStep1() {
    document.getElementById('contaStep1').style.display = 'block';
    document.getElementById('contaStep2').style.display = 'none';
}

async function salvarContaForm() {
    const btn = document.getElementById('btnSalvarConta');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        await emailApiPost('salvar_conta', {
            id: document.getElementById('contaId').value || undefined,
            nome_conta: document.getElementById('contaNome').value,
            email: document.getElementById('contaEmail').value,
            imap_host: document.getElementById('contaImapHost').value,
            imap_porta: document.getElementById('contaImapPorta').value,
            imap_seguranca: document.getElementById('contaImapSeg').value,
            smtp_host: document.getElementById('contaSmtpHost').value,
            smtp_porta: document.getElementById('contaSmtpPorta').value,
            smtp_seguranca: document.getElementById('contaSmtpSeg').value,
            usuario_email: document.getElementById('contaUsuarioEmail').value,
            senha_email: document.getElementById('contaSenhaEmail').value,
        });

        emailShowToast('Conta salva com sucesso!');
        document.getElementById('emailContaForm').style.display = 'none';
        renderContasList();
        carregarContas(); // Refresh selects
    } catch(e) {
        emailShowToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
    }
}

async function excluirContaForm() {
    const id = document.getElementById('contaId').value;
    if (!id || !confirm('Excluir esta conta de e-mail?')) return;

    try {
        await emailApiPost('excluir_conta', {conta_id: id});
        emailShowToast('Conta excluída');
        document.getElementById('emailContaForm').style.display = 'none';
        renderContasList();
        carregarContas();
    } catch(e) {
        emailShowToast('Erro: ' + e.message, 'error');
    }
}

async function testarConexaoForm() {
    const btn = document.getElementById('btnTestarConexao');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';

    try {
        await emailApiPost('testar_conexao', {
            id: document.getElementById('contaId').value || undefined,
            imap_host: document.getElementById('contaImapHost').value,
            imap_porta: document.getElementById('contaImapPorta').value,
            imap_seguranca: document.getElementById('contaImapSeg').value,
            usuario_email: document.getElementById('contaUsuarioEmail').value,
            senha_email: document.getElementById('contaSenhaEmail').value,
        });

        emailShowToast('✅ Conexão IMAP bem-sucedida!');
    } catch(e) {
        emailShowToast('❌ Falha: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Testar Conexão';
    }
}

// ==========================================
//  IA RESUMO (Streaming SSE)
// ==========================================
async function iaResumoEmail() {
    if (!emailAtual) return;

    const panel = document.getElementById('emailIaPanel');
    const body = document.getElementById('emailIaBody');

    panel.style.display = 'block';
    body.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando modelo IA...</p></div>';

    try {
        const response = await fetch(EMAIL_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'ia_resumo',
                conta_id: emailContaAtiva,
                uid: emailAtual.uid,
                folder: emailPastaAtiva,
            })
        });

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let fullContent = '';
        let receivedContent = false;
        let loadingDots = 0;
        let loadingInterval = null;

        while (true) {
            const {done, value} = await reader.read();
            if (done) break;

            const text = decoder.decode(value, {stream: true});
            const lines = text.split('\n');

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                try {
                    const data = JSON.parse(line.substring(6));

                    // Status de conexão
                    if (data.status === 'connected') continue;

                    // Heartbeat: modelo carregando
                    if (data.loading) {
                        if (!loadingInterval) {
                            loadingInterval = setInterval(() => {
                                loadingDots = (loadingDots + 1) % 4;
                                const dots = '.'.repeat(loadingDots);
                                body.innerHTML = `<div class="loading-center"><i class="fas fa-cog fa-spin fa-2x"></i><p>Preparando modelo IA${dots}</p></div>`;
                            }, 500);
                        }
                        continue;
                    }

                    if (data.error) {
                        if (loadingInterval) clearInterval(loadingInterval);
                        body.innerHTML = `<div class="email-ia-error"><i class="fas fa-exclamation-triangle"></i> ${data.error}</div>`;
                        break;
                    }
                    if (data.content) {
                        if (!receivedContent) {
                            receivedContent = true;
                            if (loadingInterval) { clearInterval(loadingInterval); loadingInterval = null; }
                            body.innerHTML = '<div class="email-ia-content" id="iaResumoContent"></div>';
                        }
                        fullContent += data.content;
                        const contentDiv = document.getElementById('iaResumoContent');
                        if (contentDiv) {
                            if (typeof marked !== 'undefined') {
                                contentDiv.innerHTML = marked.parse(fullContent);
                            } else {
                                contentDiv.innerHTML = '<pre style="white-space:pre-wrap;font-family:inherit">' + escapeHtml(fullContent) + '</pre>';
                            }
                            panel.scrollTop = panel.scrollHeight;
                        }
                    }
                    if (data.done) {
                        if (loadingInterval) clearInterval(loadingInterval);
                        // Final render
                        const contentDiv = document.getElementById('iaResumoContent');
                        if (contentDiv && fullContent) {
                            if (typeof marked !== 'undefined') {
                                contentDiv.innerHTML = marked.parse(fullContent);
                            }
                        }
                    }
                } catch(e) {}
            }
        }

        if (loadingInterval) clearInterval(loadingInterval);

    } catch(e) {
        body.innerHTML = `<div class="email-ia-error"><i class="fas fa-exclamation-triangle"></i> Erro: ${e.message}</div>`;
    }
}

function fecharIaPanel() {
    document.getElementById('emailIaPanel').style.display = 'none';
}
</script>
