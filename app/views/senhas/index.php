<?php
/**
 * View: Cofre de Senhas
 * Gestão segura de credenciais da equipe de TI
 */
$user = currentUser();
$categoriasSenha = [
    'servidor' => ['label' => 'Servidor', 'icone' => 'fas fa-server', 'cor' => '#6366F1'],
    'aplicacao' => ['label' => 'Aplicação', 'icone' => 'fas fa-window-maximize', 'cor' => '#3B82F6'],
    'rede' => ['label' => 'Rede', 'icone' => 'fas fa-network-wired', 'cor' => '#0EA5E9'],
    'email' => ['label' => 'E-mail', 'icone' => 'fas fa-envelope', 'cor' => '#F59E0B'],
    'banco_dados' => ['label' => 'Banco de Dados', 'icone' => 'fas fa-database', 'cor' => '#EF4444'],
    'cloud' => ['label' => 'Cloud', 'icone' => 'fas fa-cloud', 'cor' => '#8B5CF6'],
    'vpn' => ['label' => 'VPN', 'icone' => 'fas fa-shield-alt', 'cor' => '#10B981'],
    'certificado' => ['label' => 'Certificado', 'icone' => 'fas fa-certificate', 'cor' => '#F97316'],
    'api' => ['label' => 'API', 'icone' => 'fas fa-code', 'cor' => '#EC4899'],
    'outro' => ['label' => 'Outro', 'icone' => 'fas fa-key', 'cor' => '#6B7280']
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-vault" style="margin-right:8px;color:#6366F1"></i> Cofre de Senhas</h1>
        <p class="page-subtitle">Gerenciamento seguro de credenciais</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="abrirModalSenha()">
            <i class="fas fa-plus"></i> Nova Senha
        </button>
    </div>
</div>

<!-- Cards de Resumo por Categoria -->
<div class="senhas-summary" id="senhasSummary"></div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <div class="senhas-filters">
            <div class="senhas-filter-search">
                <i class="fas fa-search"></i>
                <input type="text" id="buscaSenha" class="form-input" placeholder="Buscar por título, IP, URL..." oninput="filtrarSenhas()">
            </div>
            <div class="senhas-filter-cat">
                <select id="filtroCatSenha" class="form-select" onchange="filtrarSenhas()">
                    <option value="">Todas Categorias</option>
                    <?php foreach ($categoriasSenha as $key => $cat): ?>
                    <option value="<?= $key ?>"><?= $cat['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Grid de Senhas -->
<div class="senhas-grid" id="senhasGrid">
    <div class="loading-center">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p>Carregando cofre...</p>
    </div>
</div>

<!-- Nenhum resultado -->
<div class="senhas-empty" id="senhasEmpty" style="display:none">
    <i class="fas fa-lock"></i>
    <h3>Nenhuma senha cadastrada</h3>
    <p>Clique em "Nova Senha" para adicionar credenciais ao cofre.</p>
</div>

<script>
const CATEGORIAS = <?= json_encode($categoriasSenha) ?>;
let senhasData = [];

// ===== CARREGAMENTO =====
document.addEventListener('DOMContentLoaded', carregarSenhas);

function carregarSenhas() {
    HelpDesk.api('GET', '/api/senhas.php', { action: 'listar' })
        .then(resp => {
            if (resp.success) {
                senhasData = resp.data;
                renderizarSenhas(senhasData);
                renderizarResumo(senhasData);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao carregar senhas', 'danger');
            }
        });
}

function renderizarResumo(data) {
    const contagem = {};
    data.forEach(s => {
        contagem[s.categoria] = (contagem[s.categoria] || 0) + 1;
    });

    let html = `
        <div class="summary-card senhas-total-card">
            <div class="summary-icon" style="background:#EFF6FF;color:#3B82F6">
                <i class="fas fa-vault"></i>
            </div>
            <div class="summary-data">
                <span class="summary-number">${data.length}</span>
                <span class="summary-label">Total</span>
            </div>
        </div>`;

    Object.entries(contagem).sort((a, b) => b[1] - a[1]).slice(0, 4).forEach(([cat, total]) => {
        const info = CATEGORIAS[cat] || CATEGORIAS['outro'];
        html += `
        <div class="summary-card" onclick="document.getElementById('filtroCatSenha').value='${cat}';filtrarSenhas()" style="cursor:pointer">
            <div class="summary-icon" style="background:${info.cor}15;color:${info.cor}">
                <i class="${info.icone}"></i>
            </div>
            <div class="summary-data">
                <span class="summary-number">${total}</span>
                <span class="summary-label">${info.label}</span>
            </div>
        </div>`;
    });

    document.getElementById('senhasSummary').innerHTML = html;
}

function renderizarSenhas(data) {
    const grid = document.getElementById('senhasGrid');
    const empty = document.getElementById('senhasEmpty');

    if (!data.length) {
        grid.style.display = 'none';
        empty.style.display = 'flex';
        return;
    }

    grid.style.display = 'grid';
    empty.style.display = 'none';

    grid.innerHTML = data.map(s => {
        const cat = CATEGORIAS[s.categoria] || CATEGORIAS['outro'];
        const userDisplay = s.usuario_dec || '-';
        const url = s.url || '';
        const host = s.ip_host ? `${s.ip_host}${s.porta ? ':' + s.porta : ''}` : '';

        return `
        <div class="senha-card" data-id="${s.id}">
            <div class="senha-card-header">
                <div class="senha-cat-badge" style="background:${cat.cor}15;color:${cat.cor}">
                    <i class="${cat.icone}"></i> ${cat.label}
                </div>
                <div class="senha-card-actions">
                    <button class="btn-icon" title="Editar" onclick="editarSenha(${s.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if ($user['tipo'] === 'admin'): ?>
                    <button class="btn-icon text-danger" title="Excluir" onclick="excluirSenha(${s.id}, '${s.titulo.replace(/'/g, "\\'")}')">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <h3 class="senha-card-title">${escapeHtml(s.titulo)}</h3>
            ${host ? `<div class="senha-card-info"><i class="fas fa-server"></i> ${escapeHtml(host)}</div>` : ''}
            ${url ? `<div class="senha-card-info"><i class="fas fa-link"></i> <a href="${escapeHtml(url)}" target="_blank" onclick="event.stopPropagation()">${truncar(url, 35)}</a></div>` : ''}
            <div class="senha-card-info"><i class="fas fa-user"></i> ${escapeHtml(userDisplay)}</div>
            <div class="senha-card-password">
                <div class="senha-field">
                    <span class="senha-dots" id="dots-${s.id}">••••••••••</span>
                    <span class="senha-revealed" id="revealed-${s.id}" style="display:none"></span>
                </div>
                <div class="senha-field-actions">
                    <button class="btn-icon-sm" title="Revelar senha" onclick="toggleSenha(${s.id})">
                        <i class="fas fa-eye" id="eye-${s.id}"></i>
                    </button>
                    <button class="btn-icon-sm" title="Copiar senha" onclick="copiarSenha(${s.id})">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="btn-icon-sm" title="Copiar usuário" onclick="copiarUsuario(${s.id}, '${(s.usuario_dec || '').replace(/'/g, "\\'")}')">
                        <i class="fas fa-user-tag"></i>
                    </button>
                </div>
            </div>
            <div class="senha-card-footer">
                <span title="Criado por ${escapeHtml(s.criado_por_nome || '')}">${tempoRelativo(s.atualizado_em || s.criado_em)}</span>
                <button class="btn-link-sm" onclick="verLogs(${s.id}, '${s.titulo.replace(/'/g, "\\'")}')">
                    <i class="fas fa-history"></i> Histórico
                </button>
            </div>
        </div>`;
    }).join('');
}

// ===== FILTROS =====
function filtrarSenhas() {
    const busca = document.getElementById('buscaSenha').value.toLowerCase();
    const cat = document.getElementById('filtroCatSenha').value;

    const filtrados = senhasData.filter(s => {
        const matchBusca = !busca ||
            s.titulo.toLowerCase().includes(busca) ||
            (s.ip_host || '').toLowerCase().includes(busca) ||
            (s.url || '').toLowerCase().includes(busca) ||
            (s.usuario_dec || '').toLowerCase().includes(busca);
        const matchCat = !cat || s.categoria === cat;
        return matchBusca && matchCat;
    });

    renderizarSenhas(filtrados);
}

// ===== REVELAR/COPIAR =====
const senhasCache = {};

function toggleSenha(id) {
    const dots = document.getElementById('dots-' + id);
    const revealed = document.getElementById('revealed-' + id);
    const eye = document.getElementById('eye-' + id);

    if (revealed.style.display !== 'none') {
        // Esconder
        dots.style.display = 'inline';
        revealed.style.display = 'none';
        eye.className = 'fas fa-eye';
        return;
    }

    // Revelar
    if (senhasCache[id]) {
        mostrarSenha(id, senhasCache[id]);
        return;
    }

    eye.className = 'fas fa-spinner fa-spin';
    HelpDesk.api('POST', '/api/senhas.php', { action: 'revelar', id: id })
        .then(resp => {
            if (resp.success) {
                senhasCache[id] = resp;
                mostrarSenha(id, resp);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao revelar', 'danger');
                eye.className = 'fas fa-eye';
            }
        });
}

function mostrarSenha(id, data) {
    const dots = document.getElementById('dots-' + id);
    const revealed = document.getElementById('revealed-' + id);
    const eye = document.getElementById('eye-' + id);

    dots.style.display = 'none';
    revealed.textContent = data.senha;
    revealed.style.display = 'inline';
    eye.className = 'fas fa-eye-slash';

    // Auto-esconder após 30 segundos
    setTimeout(() => {
        if (revealed.style.display !== 'none') {
            dots.style.display = 'inline';
            revealed.style.display = 'none';
            eye.className = 'fas fa-eye';
        }
    }, 30000);
}

function copiarSenha(id) {
    const doWork = (data) => {
        navigator.clipboard.writeText(data.senha).then(() => {
            HelpDesk.toast('Senha copiada!', 'success');
            HelpDesk.api('POST', '/api/senhas.php', { action: 'copiar_log', id: id });
        }).catch(() => {
            // Fallback
            const ta = document.createElement('textarea');
            ta.value = data.senha;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            HelpDesk.toast('Senha copiada!', 'success');
            HelpDesk.api('POST', '/api/senhas.php', { action: 'copiar_log', id: id });
        });
    };

    if (senhasCache[id]) {
        doWork(senhasCache[id]);
    } else {
        HelpDesk.api('POST', '/api/senhas.php', { action: 'revelar', id: id })
            .then(resp => {
                if (resp.success) {
                    senhasCache[id] = resp;
                    doWork(resp);
                } else {
                    HelpDesk.toast(resp.error || 'Erro', 'danger');
                }
            });
    }
}

function copiarUsuario(id, usuario) {
    navigator.clipboard.writeText(usuario).then(() => {
        HelpDesk.toast('Usuário copiado!', 'success');
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = usuario;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        HelpDesk.toast('Usuário copiado!', 'success');
    });
}

// ===== MODAL CRIAR/EDITAR =====
function abrirModalSenha(dados = null) {
    const isEdit = !!dados;
    const title = isEdit ? 'Editar Senha' : 'Nova Senha';

    const categoriasOpts = Object.entries(CATEGORIAS).map(([k, v]) =>
        `<option value="${k}" ${dados && dados.categoria === k ? 'selected' : ''}>${v.label}</option>`
    ).join('');

    const html = `
    <form id="formSenha" class="form-grid">
        ${isEdit ? `<input type="hidden" name="id" value="${dados.id}">` : ''}
        <div class="form-group col-span-2">
            <label class="form-label">Título / Nome do Serviço *</label>
            <input type="text" name="titulo" class="form-input" required value="${escapeAttr(dados?.titulo || '')}" placeholder="Ex: Servidor Principal, Gmail TI, AWS Console...">
        </div>
        <div class="form-group">
            <label class="form-label">Categoria *</label>
            <select name="categoria" class="form-select" required>${categoriasOpts}</select>
        </div>
        <div class="form-group">
            <label class="form-label">URL de Acesso</label>
            <input type="url" name="url" class="form-input" value="${escapeAttr(dados?.url || '')}" placeholder="https://...">
        </div>
        <div class="form-group">
            <label class="form-label">IP / Hostname</label>
            <input type="text" name="ip_host" class="form-input" value="${escapeAttr(dados?.ip_host || '')}" placeholder="192.168.1.1 ou servidor.local">
        </div>
        <div class="form-group">
            <label class="form-label">Porta</label>
            <input type="text" name="porta" class="form-input" value="${escapeAttr(dados?.porta || '')}" placeholder="3306, 22, 443...">
        </div>
        <div class="form-group">
            <label class="form-label">Usuário / Login</label>
            <input type="text" name="usuario" class="form-input" autocomplete="off" value="${escapeAttr(dados?.usuario_dec || '')}" placeholder="admin, root, user@email...">
        </div>
        <div class="form-group">
            <label class="form-label">${isEdit ? 'Nova Senha (deixe vazio para manter)' : 'Senha *'}</label>
            <div class="input-password-wrap">
                <input type="password" name="senha" id="inputSenhaModal" class="form-input" ${isEdit ? '' : 'required'} autocomplete="new-password" placeholder="${isEdit ? '••••••••' : 'Digite a senha'}">
                <button type="button" class="btn-icon-input" onclick="toggleInputSenha()" title="Mostrar/ocultar">
                    <i class="fas fa-eye" id="eyeInputSenha"></i>
                </button>
                <button type="button" class="btn-icon-input" onclick="gerarSenhaAleatoria()" title="Gerar senha forte">
                    <i class="fas fa-magic"></i>
                </button>
            </div>
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Observações</label>
            <textarea name="notas" class="form-textarea" rows="3" placeholder="Informações adicionais, chaves SSH, tokens...">${escapeHtml(dados?.notas_dec || '')}</textarea>
        </div>
    </form>`;

    HelpDesk.showModal(title, html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarSenha(${isEdit ? 'true' : 'false'})">
            <i class="fas fa-save"></i> ${isEdit ? 'Atualizar' : 'Salvar'}
        </button>
    `);
}

function toggleInputSenha() {
    const input = document.getElementById('inputSenhaModal');
    const eye = document.getElementById('eyeInputSenha');
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'fas fa-eye';
    }
}

function gerarSenhaAleatoria() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*()-_=+[]{}|;:,.<>?';
    let senha = '';
    const array = new Uint32Array(20);
    crypto.getRandomValues(array);
    for (let i = 0; i < 20; i++) {
        senha += chars[array[i] % chars.length];
    }
    const input = document.getElementById('inputSenhaModal');
    input.type = 'text';
    input.value = senha;
    document.getElementById('eyeInputSenha').className = 'fas fa-eye-slash';
    HelpDesk.toast('Senha forte gerada!', 'info');
}

function salvarSenha(isEdit) {
    const form = document.getElementById('formSenha');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = Object.fromEntries(new FormData(form));
    data.action = isEdit ? 'atualizar' : 'criar';

    const btn = document.querySelector('.modal-footer .btn-primary');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...'; }

    HelpDesk.api('POST', '/api/senhas.php', data)
        .then(resp => {
            if (resp && resp.success) {
                HelpDesk.toast(isEdit ? 'Senha atualizada!' : 'Senha salva no cofre!', 'success');
                HelpDesk.closeModal();
                senhasCache[data.id] = null; // Limpar cache
                carregarSenhas();
            } else {
                HelpDesk.toast(resp?.error || 'Erro ao salvar', 'danger');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> ' + (isEdit ? 'Atualizar' : 'Salvar'); }
            }
        }).catch(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> ' + (isEdit ? 'Atualizar' : 'Salvar'); }
        });
}

function editarSenha(id) {
    HelpDesk.api('POST', '/api/senhas.php', { action: 'revelar', id: id })
        .then(resp => {
            if (resp.success) {
                const s = senhasData.find(x => x.id === id || x.id == id);
                if (!s) return;
                abrirModalSenha({
                    id: s.id,
                    titulo: s.titulo,
                    categoria: s.categoria,
                    url: s.url || '',
                    ip_host: s.ip_host || '',
                    porta: s.porta || '',
                    usuario_dec: resp.usuario,
                    notas_dec: resp.notas
                });
            } else {
                HelpDesk.toast(resp.error || 'Erro ao carregar', 'danger');
            }
        });
}

function excluirSenha(id, titulo) {
    if (!confirm('⚠️ Excluir a credencial "' + titulo + '"?\n\nEsta ação é irreversível!')) return;

    HelpDesk.api('POST', '/api/senhas.php', { action: 'excluir', id: id })
        .then(resp => {
            if (resp && resp.success) {
                HelpDesk.toast('Credencial excluída!', 'success');
                delete senhasCache[id];
                carregarSenhas();
            } else {
                HelpDesk.toast(resp?.error || 'Erro ao excluir', 'danger');
            }
        });
}

// ===== LOGS =====
function verLogs(id, titulo) {
    HelpDesk.api('GET', '/api/senhas.php', { action: 'logs', id: id })
        .then(resp => {
            if (!resp.success) return HelpDesk.toast(resp.error, 'danger');

            const acaoLabels = {
                'visualizar': { icon: 'fas fa-eye', cor: '#3B82F6', label: 'Visualizou' },
                'criar': { icon: 'fas fa-plus', cor: '#10B981', label: 'Criou' },
                'editar': { icon: 'fas fa-edit', cor: '#F59E0B', label: 'Editou' },
                'excluir': { icon: 'fas fa-trash', cor: '#EF4444', label: 'Excluiu' },
                'copiar': { icon: 'fas fa-copy', cor: '#8B5CF6', label: 'Copiou' }
            };

            let html = '<div class="senhas-log-list">';
            if (!resp.data.length) {
                html += '<p class="text-center text-muted py-4">Nenhum registro de acesso.</p>';
            } else {
                resp.data.forEach(log => {
                    const a = acaoLabels[log.acao] || { icon: 'fas fa-circle', cor: '#6B7280', label: log.acao };
                    html += `
                    <div class="senhas-log-item">
                        <div class="senhas-log-icon" style="color:${a.cor}"><i class="${a.icon}"></i></div>
                        <div class="senhas-log-info">
                            <strong>${escapeHtml(log.usuario_nome || 'Sistema')}</strong> ${a.label.toLowerCase()}
                            <span class="senhas-log-date">${formatarDataHora(log.criado_em)}</span>
                        </div>
                        <div class="senhas-log-ip">${log.ip || ''}</div>
                    </div>`;
                });
            }
            html += '</div>';

            HelpDesk.showModal(`<i class="fas fa-history"></i> Histórico — ${escapeHtml(titulo)}`, html, `
                <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>
            `);
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
    return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function truncar(str, max) {
    if (!str) return '';
    return str.length > max ? str.substring(0, max) + '...' : str;
}

function tempoRelativo(data) {
    if (!data) return '';
    const agora = new Date();
    const dt = new Date(data.replace(' ', 'T'));
    const diff = Math.floor((agora - dt) / 1000);
    if (diff < 60) return 'Agora';
    if (diff < 3600) return Math.floor(diff / 60) + 'min atrás';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h atrás';
    if (diff < 2592000) return Math.floor(diff / 86400) + 'd atrás';
    return dt.toLocaleDateString('pt-BR');
}

function formatarDataHora(data) {
    if (!data) return '';
    const dt = new Date(data.replace(' ', 'T'));
    return dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}
</script>
