<?php
/**
 * View: Gestão de Rede
 * Monitoramento de dispositivos e scanner de rede
 */
$user = currentUser();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-server" style="margin-right:8px;color:#6366F1"></i> Gestão de Rede</h1>
        <p class="page-subtitle">Monitoramento de dispositivos e scanner de rede</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="pingarTodos()">
            <i class="fas fa-satellite-dish"></i> Pingar Todos
        </button>
        <button class="btn btn-primary" onclick="abrirModalDispositivo()">
            <i class="fas fa-plus"></i> Novo Dispositivo
        </button>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="rede-stats" id="redeStats">
    <div class="rede-stat-card stat-total">
        <div class="rede-stat-icon"><i class="fas fa-server"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="statTotal">—</span>
            <span class="rede-stat-label">Total</span>
        </div>
    </div>
    <div class="rede-stat-card stat-online">
        <div class="rede-stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="statOnline">—</span>
            <span class="rede-stat-label">Online</span>
        </div>
    </div>
    <div class="rede-stat-card stat-offline">
        <div class="rede-stat-icon"><i class="fas fa-times-circle"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="statOffline">—</span>
            <span class="rede-stat-label">Offline</span>
        </div>
    </div>
    <div class="rede-stat-card stat-unknown">
        <div class="rede-stat-icon"><i class="fas fa-question-circle"></i></div>
        <div class="rede-stat-info">
            <span class="rede-stat-num" id="statDesconhecido">—</span>
            <span class="rede-stat-label">Desconhecido</span>
        </div>
    </div>
</div>

<!-- Abas -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="dispositivos" onclick="switchTabRede('dispositivos')">
        <i class="fas fa-desktop"></i> Dispositivos Monitorados
    </button>
    <button class="ad-tab" data-tab="scanner" onclick="switchTabRede('scanner')">
        <i class="fas fa-radar"></i> Scanner de Rede
    </button>
</div>

<!-- ==================== ABA: DISPOSITIVOS ==================== -->
<div class="ad-tab-content active" id="tab-dispositivos">
    <div class="card mb-4">
        <div class="card-body">
            <div class="ad-filters">
                <div class="ad-filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="buscaDispositivo" class="form-input" placeholder="Buscar por nome, IP, local..."
                           onkeydown="if(event.key==='Enter') carregarDispositivos()">
                </div>
                <select id="filtroTipo" class="form-select" style="width:180px" onchange="carregarDispositivos()">
                    <option value="">Todos os tipos</option>
                    <option value="servidor">Servidor</option>
                    <option value="computador">Computador</option>
                    <option value="impressora">Impressora</option>
                    <option value="switch">Switch</option>
                    <option value="roteador">Roteador</option>
                    <option value="access_point">Access Point</option>
                    <option value="camera">Câmera</option>
                    <option value="firewall">Firewall</option>
                    <option value="nobreak">Nobreak</option>
                    <option value="outro">Outro</option>
                </select>
                <select id="filtroStatus" class="form-select" style="width:150px" onchange="carregarDispositivos()">
                    <option value="">Todos</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="desconhecido">Desconhecido</option>
                </select>
                <button class="btn btn-outline" onclick="carregarDispositivos()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="rede-grid" id="dispositivosGrid">
        <div class="loading-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Carregando dispositivos...</p>
        </div>
    </div>
</div>

<!-- ==================== ABA: SCANNER ==================== -->
<div class="ad-tab-content" id="tab-scanner">
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="mb-3" style="font-size:16px;font-weight:600"><i class="fas fa-radar"></i> Scanner de Rede</h3>
            <p class="text-muted mb-4" style="font-size:13px">Escaneia uma faixa de IPs para encontrar dispositivos ativos. Os resultados aparecem em tempo real.</p>
            <div class="rede-scan-form">
                <div class="form-group">
                    <label class="form-label">Rede Base *</label>
                    <input type="text" id="scanRede" class="form-input" placeholder="Detectando..." style="width:180px">
                </div>
                <div class="form-group">
                    <label class="form-label">IP Início</label>
                    <input type="number" id="scanInicio" class="form-input" value="1" min="1" max="254" style="width:100px">
                </div>
                <div class="form-group">
                    <label class="form-label">IP Fim</label>
                    <input type="number" id="scanFim" class="form-input" value="254" min="1" max="254" style="width:100px">
                </div>
                <div class="form-group" style="align-self:flex-end">
                    <button class="btn btn-primary" id="btnScan" onclick="iniciarScan()">
                        <i class="fas fa-search-location"></i> Escanear
                    </button>
                    <button class="btn btn-danger" id="btnScanParar" onclick="pararScan()" style="display:none">
                        <i class="fas fa-stop"></i> Parar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progresso do Scan -->
    <div class="rede-scan-progress" id="scanProgress" style="display:none">
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                    <i class="fas fa-spinner fa-spin" style="color:#6366F1;font-size:18px"></i>
                    <span id="scanStatusText" style="font-size:14px;font-weight:500">Iniciando scan...</span>
                    <span id="scanPctText" style="margin-left:auto;font-size:13px;color:#64748B"></span>
                </div>
                <div class="rede-scan-bar">
                    <div class="rede-scan-bar-fill" id="scanBarFill" style="width:0%;transition:width 0.3s ease"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados do Scan -->
    <div id="scanResultados" style="display:none">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-list"></i> Dispositivos Encontrados (<span id="scanCount">0</span>)</h3>
                <span class="text-muted" id="scanFaixa" style="font-size:13px"></span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Hostname</th>
                            <th>MAC Address</th>
                            <th>Latência</th>
                            <th>Cadastrado</th>
                            <th width="100">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="scanTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const TIPOS_ICON = {
    servidor: 'fa-server',
    computador: 'fa-desktop',
    impressora: 'fa-print',
    switch: 'fa-network-wired',
    roteador: 'fa-wifi',
    access_point: 'fa-broadcast-tower',
    camera: 'fa-video',
    firewall: 'fa-shield-alt',
    nobreak: 'fa-battery-full',
    outro: 'fa-cube'
};

const TIPOS_LABEL = {
    servidor: 'Servidor',
    computador: 'Computador',
    impressora: 'Impressora',
    switch: 'Switch',
    roteador: 'Roteador',
    access_point: 'Access Point',
    camera: 'Câmera',
    firewall: 'Firewall',
    nobreak: 'Nobreak',
    outro: 'Outro'
};

// ===== TABS =====
function switchTabRede(tab) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    carregarEstatisticas();
    carregarDispositivos();

    // Auto-detectar rede pelo servidor (não pelo navegador)
    detectarRedeServidor();
});

function detectarRedeServidor() {
    HelpDesk.api('GET', '/api/rede.php', { action: 'rede_info' })
        .then(resp => {
            if (resp.success && resp.data.rede) {
                document.getElementById('scanRede').value = resp.data.rede;
            }
        })
        .catch(() => {
            // Fallback: tenta pelo hostname do navegador
            const host = window.location.hostname;
            if (/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(host)) {
                const partes = host.split('.');
                document.getElementById('scanRede').value = partes.slice(0, 3).join('.');
            }
        });
}

// ===== ESTATÍSTICAS =====
function carregarEstatisticas() {
    HelpDesk.api('GET', '/api/rede.php', { action: 'estatisticas' })
        .then(resp => {
            if (!resp.success) return;
            document.getElementById('statTotal').textContent = resp.data.total;
            document.getElementById('statOnline').textContent = resp.data.online;
            document.getElementById('statOffline').textContent = resp.data.offline;
            document.getElementById('statDesconhecido').textContent = resp.data.desconhecido;
        });
}

// ===== DISPOSITIVOS =====
function carregarDispositivos() {
    const grid = document.getElementById('dispositivosGrid');
    grid.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>';

    const params = {
        action: 'listar',
        busca: document.getElementById('buscaDispositivo')?.value || '',
        tipo: document.getElementById('filtroTipo')?.value || '',
        status: document.getElementById('filtroStatus')?.value || '',
    };

    HelpDesk.api('GET', '/api/rede.php', params)
        .then(resp => {
            if (!resp.success) {
                grid.innerHTML = `<div class="ad-error-msg">${escapeHtml(resp.error)}</div>`;
                return;
            }

            if (!resp.data.length) {
                grid.innerHTML = '<div class="loading-center"><i class="fas fa-server" style="font-size:40px;color:#CBD5E1"></i><p>Nenhum dispositivo cadastrado</p><button class="btn btn-primary btn-sm mt-3" onclick="abrirModalDispositivo()"><i class="fas fa-plus"></i> Cadastrar</button></div>';
                return;
            }

            grid.innerHTML = resp.data.map(d => {
                const icon = TIPOS_ICON[d.tipo] || 'fa-cube';
                const tipoLabel = TIPOS_LABEL[d.tipo] || d.tipo;
                const statusClass = d.ultimo_status === 'online' ? 'rede-online' : d.ultimo_status === 'offline' ? 'rede-offline' : 'rede-unknown';
                const statusLabel = d.ultimo_status === 'online' ? 'Online' : d.ultimo_status === 'offline' ? 'Offline' : 'Desconhecido';
                const statusIcon = d.ultimo_status === 'online' ? 'fa-check-circle' : d.ultimo_status === 'offline' ? 'fa-times-circle' : 'fa-question-circle';
                const latencia = d.latencia_ms ? d.latencia_ms + ' ms' : '—';
                const ultimoPing = d.ultimo_ping ? timeAgo(d.ultimo_ping) : 'Nunca';

                return `
                <div class="rede-card ${statusClass}" data-id="${d.id}">
                    <div class="rede-card-header">
                        <div class="rede-card-icon"><i class="fas ${icon}"></i></div>
                        <div class="rede-card-status">
                            <span class="rede-status-dot ${statusClass}"></span>
                            <span>${statusLabel}</span>
                        </div>
                    </div>
                    <div class="rede-card-body">
                        <h4>${escapeHtml(d.nome)}</h4>
                        <div class="rede-card-ip"><code>${escapeHtml(d.ip)}</code></div>
                        <div class="rede-card-meta">
                            <span><i class="fas fa-tag"></i> ${escapeHtml(tipoLabel)}</span>
                            ${d.localizacao ? `<span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(d.localizacao)}</span>` : ''}
                        </div>
                        <div class="rede-card-ping-info">
                            <span><i class="fas fa-tachometer-alt"></i> ${latencia}</span>
                            <span><i class="fas fa-clock"></i> ${ultimoPing}</span>
                        </div>
                    </div>
                    <div class="rede-card-actions">
                        <button class="btn btn-sm btn-secondary" onclick="pingarDispositivo(${d.id})" title="Pingar agora">
                            <i class="fas fa-satellite-dish"></i> Ping
                        </button>
                        <button class="btn-icon" onclick="verHistorico(${d.id}, '${escapeAttr(d.nome)}')" title="Histórico">
                            <i class="fas fa-chart-line"></i>
                        </button>
                        <button class="btn-icon" onclick="abrirModalDispositivo(${d.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($user['tipo'] === 'admin'): ?>
                        <button class="btn-icon text-danger" onclick="excluirDispositivo(${d.id}, '${escapeAttr(d.nome)}')" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>`;
            }).join('');
        });
}

// ===== PING =====
function pingarDispositivo(id) {
    const card = document.querySelector(`.rede-card[data-id="${id}"]`);
    if (card) {
        const btn = card.querySelector('.btn-secondary');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
    }

    HelpDesk.api('POST', '/api/rede.php', { action: 'ping', id: id })
        .then(resp => {
            if (resp.success) {
                const d = resp.data;
                const emoji = d.status === 'online' ? '🟢' : '🔴';
                const lat = d.latencia ? ` (${d.latencia}ms)` : '';
                HelpDesk.toast(`${emoji} ${d.nome}: ${d.status}${lat}`, d.status === 'online' ? 'success' : 'danger');
                carregarDispositivos();
                carregarEstatisticas();
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

function pingarTodos() {
    HelpDesk.toast('Pingando todos os dispositivos...', 'info');
    HelpDesk.api('POST', '/api/rede.php', { action: 'ping_todos' })
        .then(resp => {
            if (resp.success) {
                const online = resp.data.filter(d => d && d.status === 'online').length;
                const offline = resp.data.filter(d => d && d.status === 'offline').length;
                HelpDesk.toast(`Concluído! 🟢 ${online} online, 🔴 ${offline} offline`, online > 0 ? 'success' : 'warning');
                carregarDispositivos();
                carregarEstatisticas();
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

// ===== MODAL DISPOSITIVO =====
function abrirModalDispositivo(id = null) {
    const isEdit = !!id;
    const titulo = isEdit ? 'Editar Dispositivo' : 'Novo Dispositivo';

    const tiposOptions = Object.entries(TIPOS_LABEL).map(([k, v]) =>
        `<option value="${k}">${v}</option>`
    ).join('');

    const html = `
    <form id="formDispositivo" class="form-grid">
        ${isEdit ? `<input type="hidden" name="id" value="${id}">` : ''}
        <div class="form-group">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" id="dNome" class="form-input" required placeholder="Ex: Servidor Principal">
        </div>
        <div class="form-group">
            <label class="form-label">Endereço IP *</label>
            <input type="text" name="ip" id="dIp" class="form-input" required placeholder="192.168.1.100"
                   pattern="^\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}$">
        </div>
        <div class="form-group">
            <label class="form-label">Tipo</label>
            <select name="tipo" id="dTipo" class="form-select">${tiposOptions}</select>
        </div>
        <div class="form-group">
            <label class="form-label">Localização</label>
            <input type="text" name="localizacao" id="dLocal" class="form-input" placeholder="Ex: Sala de servidores">
        </div>
        <div class="form-group">
            <label class="form-label">MAC Address</label>
            <input type="text" name="mac_address" id="dMac" class="form-input" placeholder="AA:BB:CC:DD:EE:FF">
        </div>
        <div class="form-group">
            <label class="form-label">Intervalo de Ping (seg)</label>
            <input type="number" name="intervalo_ping" id="dIntervalo" class="form-input" value="60" min="10" max="3600">
        </div>
        <div class="form-group col-span-2">
            <label class="form-label">Observações</label>
            <textarea name="observacoes" id="dObs" class="form-input" rows="2" placeholder="Anotações sobre o dispositivo..."></textarea>
        </div>
        <div class="form-group col-span-2">
            <label class="ad-checkbox-item">
                <input type="checkbox" name="notificar_offline" id="dNotificar" value="1" checked>
                Notificar quando ficar offline
            </label>
        </div>
    </form>`;

    HelpDesk.showModal(`<i class="fas fa-${isEdit ? 'edit' : 'plus'}"></i> ${titulo}`, html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="salvarDispositivo(${isEdit})"><i class="fas fa-save"></i> Salvar</button>
    `, 'modal-lg');

    // Se editando, carregar dados
    if (isEdit) {
        HelpDesk.api('GET', '/api/rede.php', { action: 'dispositivo', id: id })
            .then(resp => {
                if (resp.success) {
                    const d = resp.data;
                    document.getElementById('dNome').value = d.nome || '';
                    document.getElementById('dIp').value = d.ip || '';
                    document.getElementById('dTipo').value = d.tipo || 'outro';
                    document.getElementById('dLocal').value = d.localizacao || '';
                    document.getElementById('dMac').value = d.mac_address || '';
                    document.getElementById('dIntervalo').value = d.intervalo_ping || 60;
                    document.getElementById('dObs').value = d.observacoes || '';
                    document.getElementById('dNotificar').checked = d.notificar_offline == 1;
                }
            });
    }
}

function salvarDispositivo(isEdit) {
    const form = document.getElementById('formDispositivo');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = isEdit ? 'atualizar' : 'criar';

    const btn = document.querySelector('.modal-footer .btn-primary');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...'; }

    HelpDesk.api('POST', '/api/rede.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                HelpDesk.closeModal();
                carregarDispositivos();
                carregarEstatisticas();
            } else {
                HelpDesk.toast(resp.error, 'danger');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Salvar'; }
            }
        });
}

function excluirDispositivo(id, nome) {
    if (!confirm(`Excluir o dispositivo "${nome}"? O histórico de pings será perdido.`)) return;

    HelpDesk.api('POST', '/api/rede.php', { action: 'excluir', id: id })
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(resp.message, 'success');
                carregarDispositivos();
                carregarEstatisticas();
            } else {
                HelpDesk.toast(resp.error, 'danger');
            }
        });
}

// ===== HISTÓRICO =====
function verHistorico(id, nome) {
    HelpDesk.api('GET', '/api/rede.php', { action: 'historico', id: id })
        .then(resp => {
            if (!resp.success) return HelpDesk.toast(resp.error, 'danger');

            const uptime = resp.uptime_24h !== null ? resp.uptime_24h + '%' : 'Sem dados';
            let html = `<div class="rede-historico-uptime">
                <span class="rede-uptime-label">Uptime 24h</span>
                <span class="rede-uptime-value">${uptime}</span>
            </div>`;

            if (!resp.data.length) {
                html += '<p class="text-center text-muted py-4">Nenhum registro de ping ainda.</p>';
            } else {
                html += '<div class="rede-historico-list">';
                resp.data.forEach(h => {
                    const isOnline = h.status === 'online';
                    const cls = isOnline ? 'online' : 'offline';
                    const icon = isOnline ? 'fa-check-circle' : 'fa-times-circle';
                    const lat = h.latencia_ms ? h.latencia_ms + ' ms' : '—';
                    html += `
                    <div class="rede-historico-item ${cls}">
                        <i class="fas ${icon}"></i>
                        <span class="rede-hist-status">${h.status}</span>
                        <span class="rede-hist-latencia">${lat}</span>
                        <span class="rede-hist-data">${escapeHtml(h.criado_em)}</span>
                    </div>`;
                });
                html += '</div>';
            }

            HelpDesk.showModal(`<i class="fas fa-chart-line"></i> Histórico — ${escapeHtml(nome)}`, html, `
                <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>
            `, 'modal-lg');
        });
}

// ===== SCANNER (SSE - TEMPO REAL) =====
let scanEventSource = null;

function iniciarScan() {
    const rede = document.getElementById('scanRede').value.trim();
    const inicio = parseInt(document.getElementById('scanInicio').value) || 1;
    const fim = parseInt(document.getElementById('scanFim').value) || 254;

    if (!rede) {
        HelpDesk.toast('Informe a rede base (ex: 192.168.1)', 'warning');
        return;
    }
    if (!/^\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(rede)) {
        HelpDesk.toast('Formato inválido. Use: 192.168.1', 'warning');
        return;
    }

    // UI - iniciar
    const btnScan = document.getElementById('btnScan');
    const btnParar = document.getElementById('btnScanParar');
    btnScan.style.display = 'none';
    btnParar.style.display = 'inline-flex';

    document.getElementById('scanProgress').style.display = 'block';
    document.getElementById('scanBarFill').style.width = '0%';
    document.getElementById('scanStatusText').textContent = `Escaneando ${rede}.${inicio} - ${rede}.${fim}...`;
    document.getElementById('scanPctText').textContent = '';

    // Limpar resultados anteriores
    document.getElementById('scanResultados').style.display = 'block';
    document.getElementById('scanTbody').innerHTML = '';
    document.getElementById('scanCount').textContent = '0';
    document.getElementById('scanFaixa').textContent = `${rede}.${inicio} — ${rede}.${fim}`;

    let encontrados = 0;
    const baseUrl = '<?= BASE_URL ?>';
    const url = `${baseUrl}/api/rede.php?action=scan_stream&rede=${encodeURIComponent(rede)}&inicio=${inicio}&fim=${fim}`;

    // Fechar conexão anterior se existir
    if (scanEventSource) {
        scanEventSource.close();
        scanEventSource = null;
    }

    scanEventSource = new EventSource(url);

    scanEventSource.onmessage = function(e) {
        let data;
        try { data = JSON.parse(e.data); } catch(err) { return; }

        // Erro
        if (data.type === 'error') {
            HelpDesk.toast(data.message, 'danger');
            finalizarScan();
            return;
        }

        // Progresso
        if (data.type === 'progress') {
            const pct = Math.round((data.scanned / data.total) * 100);
            document.getElementById('scanBarFill').style.width = pct + '%';
            document.getElementById('scanStatusText').textContent = `Escaneando ${data.current_ip}...`;
            document.getElementById('scanPctText').textContent = `${data.scanned}/${data.total} (${pct}%)`;
            return;
        }

        // Dispositivo encontrado
        if (data.type === 'result') {
            encontrados++;
            document.getElementById('scanCount').textContent = encontrados;
            adicionarResultadoScan(data);
            return;
        }

        // Concluído
        if (data.type === 'done') {
            document.getElementById('scanBarFill').style.width = '100%';
            document.getElementById('scanStatusText').innerHTML = `<i class="fas fa-check-circle" style="color:#22C55E"></i> Concluído! ${data.encontrados} dispositivo(s) encontrado(s).`;
            document.getElementById('scanPctText').textContent = '';
            finalizarScan(false);

            if (encontrados === 0) {
                document.getElementById('scanTbody').innerHTML = '<tr><td colspan="6" class="text-center py-4">Nenhum dispositivo encontrado nesta faixa.</td></tr>';
            }
            return;
        }
    };

    scanEventSource.onerror = function() {
        finalizarScan();
    };
}

function pararScan() {
    if (scanEventSource) {
        scanEventSource.close();
        scanEventSource = null;
    }
    document.getElementById('scanStatusText').innerHTML = '<i class="fas fa-stop-circle" style="color:#EF4444"></i> Scan interrompido.';
    document.getElementById('scanPctText').textContent = '';
    finalizarScan(false);
}

function finalizarScan(hideProgress = true) {
    if (scanEventSource) {
        scanEventSource.close();
        scanEventSource = null;
    }

    const btnScan = document.getElementById('btnScan');
    const btnParar = document.getElementById('btnScanParar');
    btnScan.style.display = 'inline-flex';
    btnParar.style.display = 'none';

    if (hideProgress) {
        setTimeout(() => {
            document.getElementById('scanProgress').style.display = 'none';
        }, 500);
    }
}

function adicionarResultadoScan(d) {
    const tbody = document.getElementById('scanTbody');
    const tr = document.createElement('tr');
    tr.style.animation = 'fadeIn 0.3s ease';

    const cadastradoHtml = d.cadastrado
        ? `<span class="badge badge-success"><i class="fas fa-check"></i> ${escapeHtml(d.dispositivo_nome)}</span>`
        : '<span class="badge badge-secondary">Não cadastrado</span>';

    const acaoHtml = !d.cadastrado
        ? `<button class="btn btn-sm btn-primary" onclick="cadastrarDoScan('${escapeAttr(d.ip)}', '${escapeAttr(d.hostname)}', '${escapeAttr(d.mac)}')"><i class="fas fa-plus"></i></button>`
        : `<button class="btn btn-sm btn-outline" onclick="pingarDispositivo(${d.dispositivo_id})"><i class="fas fa-satellite-dish"></i></button>`;

    tr.innerHTML = `
        <td><code><strong>${escapeHtml(d.ip)}</strong></code></td>
        <td>${escapeHtml(d.hostname) || '<span class="text-muted">—</span>'}</td>
        <td><code>${escapeHtml(d.mac) || '—'}</code></td>
        <td>${d.latencia ? d.latencia + ' ms' : '—'}</td>
        <td>${cadastradoHtml}</td>
        <td>${acaoHtml}</td>
    `;

    tbody.appendChild(tr);

    // Scroll para mostrar o novo resultado
    tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function cadastrarDoScan(ip, hostname, mac) {
    abrirModalDispositivo();
    // Preencher campos após modal abrir
    setTimeout(() => {
        document.getElementById('dIp').value = ip;
        document.getElementById('dNome').value = hostname || ip;
        if (mac) document.getElementById('dMac').value = mac;
    }, 200);
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
</script>
