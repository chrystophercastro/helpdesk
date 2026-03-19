<?php
/**
 * View: Proxmox - Gestão de Virtualização
 * Interface completa para gerenciar VMs, Containers, Nodes e Storage
 */
$user = currentUser();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-cloud" style="margin-right:8px;color:#E67E22"></i> Proxmox VE</h1>
        <p class="page-subtitle">Gestão de Virtualização — VMs, Containers, Nodes e Storage</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="refreshAll()" id="btnRefresh">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
        <button class="btn btn-primary" onclick="abrirModalServidor()">
            <i class="fas fa-plus"></i> Novo Servidor
        </button>
    </div>
</div>

<!-- Seletor de Servidor -->
<div class="pve-server-bar" id="serverBar">
    <div class="pve-server-select-wrap">
        <i class="fas fa-server"></i>
        <select id="serverSelect" class="form-select" onchange="onServerChange()" style="min-width:220px">
            <option value="">— Selecione um servidor —</option>
        </select>
        <span class="pve-server-status" id="serverStatus"></span>
    </div>
    <div class="pve-server-info" id="serverInfo" style="display:none">
        <span id="serverVersion" class="pve-badge pve-badge-info"></span>
        <span id="serverNodeCount" class="pve-badge pve-badge-default"></span>
    </div>
    <div class="pve-server-actions" id="serverActions" style="display:none">
        <button class="btn btn-sm btn-outline" onclick="editarServidor()"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline" onclick="testarConexao()"><i class="fas fa-plug"></i> Testar</button>
    </div>
</div>

<!-- Conteúdo Principal -->
<div id="pveContent" style="display:none">

    <!-- Cards de Estatísticas -->
    <div class="pve-stats" id="pveStats">
        <div class="pve-stat-card stat-nodes">
            <div class="pve-stat-icon"><i class="fas fa-server"></i></div>
            <div class="pve-stat-info">
                <span class="pve-stat-num" id="statNodes">0</span>
                <span class="pve-stat-label">Nodes</span>
            </div>
        </div>
        <div class="pve-stat-card stat-vms">
            <div class="pve-stat-icon"><i class="fas fa-desktop"></i></div>
            <div class="pve-stat-info">
                <span class="pve-stat-num" id="statVMs">0</span>
                <span class="pve-stat-label">VMs</span>
            </div>
        </div>
        <div class="pve-stat-card stat-cts">
            <div class="pve-stat-icon"><i class="fas fa-cube"></i></div>
            <div class="pve-stat-info">
                <span class="pve-stat-num" id="statCTs">0</span>
                <span class="pve-stat-label">Containers</span>
            </div>
        </div>
        <div class="pve-stat-card stat-running">
            <div class="pve-stat-icon"><i class="fas fa-play-circle"></i></div>
            <div class="pve-stat-info">
                <span class="pve-stat-num" id="statRunning">0</span>
                <span class="pve-stat-label">Rodando</span>
            </div>
        </div>
        <div class="pve-stat-card stat-cpu">
            <div class="pve-stat-icon"><i class="fas fa-microchip"></i></div>
            <div class="pve-stat-info">
                <span class="pve-stat-num" id="statCPU">0</span>
                <span class="pve-stat-label">CPUs</span>
            </div>
        </div>
        <div class="pve-stat-card stat-mem">
            <div class="pve-stat-icon"><i class="fas fa-memory"></i></div>
            <div class="pve-stat-info">
                <span class="pve-stat-num" id="statMem">—</span>
                <span class="pve-stat-label">Memória</span>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <div class="ad-tabs">
        <button class="ad-tab active" data-tab="overview" onclick="switchTab('overview')">
            <i class="fas fa-tachometer-alt"></i> Overview
        </button>
        <button class="ad-tab" data-tab="vms" onclick="switchTab('vms')">
            <i class="fas fa-desktop"></i> Máquinas Virtuais
        </button>
        <button class="ad-tab" data-tab="containers" onclick="switchTab('containers')">
            <i class="fas fa-cube"></i> Containers
        </button>
        <button class="ad-tab" data-tab="storage" onclick="switchTab('storage')">
            <i class="fas fa-hdd"></i> Storage
        </button>
        <button class="ad-tab" data-tab="logs" onclick="switchTab('logs')">
            <i class="fas fa-history"></i> Logs
        </button>
    </div>

    <!-- ==================== ABA: OVERVIEW ==================== -->
    <div class="ad-tab-content active" id="tab-overview">
        <h3 style="font-size:16px;font-weight:600;margin-bottom:16px"><i class="fas fa-server"></i> Nodes do Cluster</h3>
        <div class="pve-nodes-grid" id="nodesGrid">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando nodes...</p></div>
        </div>

        <!-- Uso de Recursos -->
        <div class="pve-resource-bars" id="resourceBars" style="margin-top:24px">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:16px"><i class="fas fa-chart-pie"></i> Uso de Recursos</h3>
            <div class="pve-res-grid">
                <div class="card">
                    <div class="card-body">
                        <div class="pve-res-title">CPU</div>
                        <div class="pve-progress-wrap">
                            <div class="pve-progress-bar"><div class="pve-progress-fill pve-fill-cpu" id="cpuBar" style="width:0%"></div></div>
                            <span class="pve-progress-text" id="cpuText">0%</span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="pve-res-title">Memória</div>
                        <div class="pve-progress-wrap">
                            <div class="pve-progress-bar"><div class="pve-progress-fill pve-fill-mem" id="memBar" style="width:0%"></div></div>
                            <span class="pve-progress-text" id="memText">0%</span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="pve-res-title">Disco</div>
                        <div class="pve-progress-wrap">
                            <div class="pve-progress-bar"><div class="pve-progress-fill pve-fill-disk" id="diskBar" style="width:0%"></div></div>
                            <span class="pve-progress-text" id="diskText">0%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== ABA: VMs ==================== -->
    <div class="ad-tab-content" id="tab-vms">
        <div class="card mb-4">
            <div class="card-body">
                <div class="ad-filters">
                    <div class="pve-node-select-wrap">
                        <label class="form-label" style="margin:0;font-size:13px">Node:</label>
                        <select id="vmNodeSelect" class="form-select" style="width:180px" onchange="carregarVMs()">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="ad-filter-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="vmBusca" class="form-input" placeholder="Buscar VM por nome, VMID..."
                               onkeydown="if(event.key==='Enter') filtrarVMs()">
                    </div>
                    <select id="vmStatusFilter" class="form-select" style="width:150px" onchange="filtrarVMs()">
                        <option value="">Todos status</option>
                        <option value="running">Rodando</option>
                        <option value="stopped">Parado</option>
                        <option value="paused">Pausado</option>
                    </select>
                    <button class="btn btn-outline" onclick="carregarVMs()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-primary" onclick="abrirModalCriarVM()">
                        <i class="fas fa-plus"></i> Criar VM
                    </button>
                </div>
            </div>
        </div>
        <div class="pve-vm-grid" id="vmsGrid">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando VMs...</p></div>
        </div>
    </div>

    <!-- ==================== ABA: CONTAINERS ==================== -->
    <div class="ad-tab-content" id="tab-containers">
        <div class="card mb-4">
            <div class="card-body">
                <div class="ad-filters">
                    <div class="pve-node-select-wrap">
                        <label class="form-label" style="margin:0;font-size:13px">Node:</label>
                        <select id="ctNodeSelect" class="form-select" style="width:180px" onchange="carregarContainers()">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="ad-filter-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="ctBusca" class="form-input" placeholder="Buscar container por nome, VMID..."
                               onkeydown="if(event.key==='Enter') filtrarContainers()">
                    </div>
                    <select id="ctStatusFilter" class="form-select" style="width:150px" onchange="filtrarContainers()">
                        <option value="">Todos status</option>
                        <option value="running">Rodando</option>
                        <option value="stopped">Parado</option>
                    </select>
                    <button class="btn btn-outline" onclick="carregarContainers()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-primary" onclick="abrirModalCriarCT()">
                        <i class="fas fa-plus"></i> Criar Container
                    </button>
                </div>
            </div>
        </div>
        <div class="pve-vm-grid" id="ctsGrid">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando containers...</p></div>
        </div>
    </div>

    <!-- ==================== ABA: STORAGE ==================== -->
    <div class="ad-tab-content" id="tab-storage">
        <div class="card mb-4">
            <div class="card-body">
                <div class="ad-filters">
                    <div class="pve-node-select-wrap">
                        <label class="form-label" style="margin:0;font-size:13px">Node:</label>
                        <select id="storageNodeSelect" class="form-select" style="width:180px" onchange="carregarStorage()">
                            <option value="">Selecione</option>
                        </select>
                    </div>
                    <button class="btn btn-outline" onclick="carregarStorage()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="pve-storage-grid" id="storageGrid">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando storage...</p></div>
        </div>
    </div>

    <!-- ==================== ABA: LOGS ==================== -->
    <div class="ad-tab-content" id="tab-logs">
        <div class="card">
            <div class="card-body" style="padding:0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>VM/CT</th>
                                <th>Node</th>
                                <th>Detalhes</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody id="logsTbody">
                            <tr><td colspan="7" class="text-muted" style="text-align:center;padding:40px">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estado vazio (sem servidor selecionado) -->
<div id="pveEmpty" class="pve-empty-state">
    <i class="fas fa-cloud fa-3x" style="color:#E67E22;margin-bottom:16px"></i>
    <h3>Selecione ou cadastre um servidor Proxmox</h3>
    <p class="text-muted">Conecte-se ao seu Proxmox VE para gerenciar máquinas virtuais, containers, snapshots e storage.</p>
    <button class="btn btn-primary" onclick="abrirModalServidor()" style="margin-top:12px">
        <i class="fas fa-plus"></i> Cadastrar Servidor
    </button>
</div>

<!-- ==================== MODAL: SERVIDOR ==================== -->
<div class="modal-overlay" id="modalServidor" style="display:none">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h2 id="modalServidorTitle"><i class="fas fa-server"></i> Novo Servidor Proxmox</h2>
            <button class="modal-close" onclick="fecharModal('modalServidor')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="srvId">
            <div class="form-group mb-3">
                <label class="form-label">Nome *</label>
                <input type="text" id="srvNome" class="form-input" placeholder="Ex: Proxmox Principal">
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Host/IP *</label>
                    <input type="text" id="srvHost" class="form-input" placeholder="Ex: 192.168.1.100">
                </div>
                <div class="form-group">
                    <label class="form-label">Porta</label>
                    <input type="number" id="srvPorta" class="form-input" value="8006">
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Tipo de Autenticação</label>
                <select id="srvAuthType" class="form-select" onchange="toggleAuthFields()">
                    <option value="password">Usuário e Senha</option>
                    <option value="apitoken">API Token</option>
                </select>
            </div>
            <div id="authPasswordFields">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                    <div class="form-group">
                        <label class="form-label">Usuário *</label>
                        <input type="text" id="srvUsuario" class="form-input" placeholder="root">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Realm</label>
                        <select id="srvRealm" class="form-select">
                            <option value="pam">PAM</option>
                            <option value="pve">PVE</option>
                            <option value="pmxclient">PMX Client</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Senha *</label>
                    <input type="password" id="srvSenha" class="form-input" placeholder="Senha do Proxmox">
                </div>
            </div>
            <div id="authTokenFields" style="display:none">
                <div class="form-group mb-3">
                    <label class="form-label">Token ID *</label>
                    <input type="text" id="srvTokenId" class="form-input" placeholder="user@pam!tokenname">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Token Secret *</label>
                    <input type="password" id="srvTokenSecret" class="form-input" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                </div>
            </div>
            <div class="form-group mb-3">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="srvVerifySSL">
                    <span class="form-label" style="margin:0">Verificar certificado SSL</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="fecharModal('modalServidor')">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarServidor()" id="btnSalvarSrv">
                <i class="fas fa-save"></i> Salvar
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: DETALHES VM/CT ==================== -->
<div class="modal-overlay" id="modalVMDetail" style="display:none">
    <div class="modal" style="max-width:750px">
        <div class="modal-header">
            <h2 id="vmDetailTitle"><i class="fas fa-desktop"></i> Detalhes da VM</h2>
            <button class="modal-close" onclick="fecharModal('modalVMDetail')">&times;</button>
        </div>
        <div class="modal-body" id="vmDetailBody" style="max-height:70vh;overflow-y:auto">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
        <div class="modal-footer" id="vmDetailFooter">
        </div>
    </div>
</div>

<!-- ==================== MODAL: SNAPSHOT ==================== -->
<div class="modal-overlay" id="modalSnapshot" style="display:none">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h2><i class="fas fa-camera"></i> Criar Snapshot</h2>
            <button class="modal-close" onclick="fecharModal('modalSnapshot')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="snapServerId">
            <input type="hidden" id="snapNode">
            <input type="hidden" id="snapVmid">
            <input type="hidden" id="snapType">
            <div class="form-group mb-3">
                <label class="form-label">Nome do Snapshot *</label>
                <input type="text" id="snapName" class="form-input" placeholder="Ex: antes-update">
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Descrição</label>
                <textarea id="snapDesc" class="form-input" rows="3" placeholder="Descrição opcional..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="fecharModal('modalSnapshot')">Cancelar</button>
            <button class="btn btn-primary" onclick="criarSnapshot()">
                <i class="fas fa-camera"></i> Criar Snapshot
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: EDITAR HARDWARE ==================== -->
<div class="modal-overlay" id="modalEditHW" style="display:none">
    <div class="modal" style="max-width:650px">
        <div class="modal-header">
            <h2><i class="fas fa-cogs"></i> Editar Hardware</h2>
            <button class="modal-close" onclick="fecharModal('modalEditHW')">&times;</button>
        </div>
        <div class="modal-body" style="max-height:70vh;overflow-y:auto">
            <input type="hidden" id="hwServerId">
            <input type="hidden" id="hwNode">
            <input type="hidden" id="hwVmid">
            <input type="hidden" id="hwType">
            <div class="pve-hw-warning" id="hwRunningWarn" style="display:none">
                <i class="fas fa-exclamation-triangle"></i>
                <span>VM está rodando. Algumas alterações serão aplicadas após reiniciar (hot-plug limitado).</span>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin-bottom:12px"><i class="fas fa-microchip"></i> Processador</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Cores (vCPU)</label>
                    <input type="number" id="hwCores" class="form-input" min="1" max="128" value="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Sockets</label>
                    <input type="number" id="hwSockets" class="form-input" min="1" max="4" value="1">
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Tipo de CPU</label>
                <select id="hwCpuType" class="form-select">
                    <option value="host">host (melhor performance)</option>
                    <option value="kvm64">kvm64 (compatibilidade)</option>
                    <option value="x86-64-v2-AES">x86-64-v2-AES</option>
                    <option value="qemu64">qemu64</option>
                </select>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-memory"></i> Memória RAM</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Memória (MB)</label>
                    <input type="number" id="hwMemory" class="form-input" min="128" step="128" value="2048">
                </div>
                <div class="form-group">
                    <label class="form-label">Balloon (MB, 0=desabilitado)</label>
                    <input type="number" id="hwBalloon" class="form-input" min="0" step="128" value="0">
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-network-wired"></i> Rede (net0)</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <select id="hwNetModel" class="form-select">
                        <option value="virtio">VirtIO (recomendado)</option>
                        <option value="e1000">Intel E1000</option>
                        <option value="rtl8139">Realtek RTL8139</option>
                        <option value="vmxnet3">VMXNet3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Bridge</label>
                    <input type="text" id="hwNetBridge" class="form-input" value="vmbr0">
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">VLAN Tag (vazio = sem VLAN)</label>
                <input type="number" id="hwNetVlan" class="form-input" min="1" max="4094" placeholder="Ex: 100">
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-hdd"></i> Disco Principal</h4>
            <div class="pve-detail-grid" id="hwDiskInfo" style="margin-bottom:12px">
                <div class="pve-detail-item"><span class="pve-detail-label">Disco atual</span><span class="pve-detail-val" id="hwDiskCurrent">—</span></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Expandir disco (incremento)</label>
                    <input type="text" id="hwDiskResize" class="form-input" placeholder="Ex: +10G">
                </div>
                <div class="form-group">
                    <label class="form-label">Disco a redimensionar</label>
                    <select id="hwDiskName" class="form-select">
                        <option value="scsi0">scsi0</option>
                        <option value="virtio0">virtio0</option>
                        <option value="sata0">sata0</option>
                        <option value="ide0">ide0</option>
                    </select>
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-sliders-h"></i> Opções</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">OS Type</label>
                    <select id="hwOsType" class="form-select">
                        <option value="l26">Linux 2.6+ / 5.x+</option>
                        <option value="win10">Windows 10/11/2019/2022</option>
                        <option value="win7">Windows 7/2008r2</option>
                        <option value="win8">Windows 8/2012</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Boot Order</label>
                    <input type="text" id="hwBootOrder" class="form-input" placeholder="order=scsi0;ide2;net0">
                </div>
            </div>
            <div class="form-group mb-3">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="hwOnboot">
                    <span class="form-label" style="margin:0">Iniciar automaticamente com o host</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="fecharModal('modalEditHW')">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarHardware()" id="btnSalvarHW">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: CRIAR VM ==================== -->
<div class="modal-overlay" id="modalCriarVM" style="display:none">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h2><i class="fas fa-desktop"></i> Criar Máquina Virtual</h2>
            <button class="modal-close" onclick="fecharModal('modalCriarVM')">&times;</button>
        </div>
        <div class="modal-body" style="max-height:70vh;overflow-y:auto">
            <h4 style="font-size:14px;font-weight:600;margin-bottom:12px"><i class="fas fa-info-circle"></i> Identificação</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Node *</label>
                    <select id="createVmNode" class="form-select" onchange="carregarISOsParaCriar()"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">VMID *</label>
                    <input type="number" id="createVmId" class="form-input" min="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" id="createVmName" class="form-input" placeholder="minha-vm">
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-compact-disc"></i> Sistema</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">OS Type</label>
                    <select id="createVmOs" class="form-select">
                        <option value="l26">Linux 2.6+ / 5.x+</option>
                        <option value="win10">Windows 10/11/2019/2022</option>
                        <option value="win7">Windows 7/2008r2</option>
                        <option value="win8">Windows 8/2012</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">ISO / CD-ROM</label>
                    <select id="createVmIso" class="form-select">
                        <option value="none">Nenhum (PXE/Cloud-init)</option>
                    </select>
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-microchip"></i> Hardware</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Cores</label>
                    <input type="number" id="createVmCores" class="form-input" min="1" max="128" value="2">
                </div>
                <div class="form-group">
                    <label class="form-label">Sockets</label>
                    <input type="number" id="createVmSockets" class="form-input" min="1" max="4" value="1">
                </div>
                <div class="form-group">
                    <label class="form-label">RAM (MB)</label>
                    <input type="number" id="createVmMemory" class="form-input" min="128" step="128" value="2048">
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-hdd"></i> Disco</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Storage</label>
                    <input type="text" id="createVmStorage" class="form-input" value="local-lvm" placeholder="local-lvm">
                </div>
                <div class="form-group">
                    <label class="form-label">Tamanho (GB)</label>
                    <input type="number" id="createVmDiskSize" class="form-input" min="1" value="32">
                </div>
                <div class="form-group">
                    <label class="form-label">Interface</label>
                    <select id="createVmDiskIface" class="form-select">
                        <option value="scsi0">SCSI (scsi0)</option>
                        <option value="virtio0">VirtIO Block</option>
                        <option value="sata0">SATA</option>
                        <option value="ide0">IDE</option>
                    </select>
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-network-wired"></i> Rede</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <select id="createVmNetModel" class="form-select">
                        <option value="virtio">VirtIO</option>
                        <option value="e1000">E1000</option>
                        <option value="rtl8139">RTL8139</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Bridge</label>
                    <input type="text" id="createVmBridge" class="form-input" value="vmbr0">
                </div>
                <div class="form-group">
                    <label class="form-label">VLAN (opcional)</label>
                    <input type="number" id="createVmVlan" class="form-input" min="1" max="4094" placeholder="">
                </div>
            </div>
            <div class="form-group mb-3">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="createVmStart">
                    <span class="form-label" style="margin:0">Iniciar após criar</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="fecharModal('modalCriarVM')">Cancelar</button>
            <button class="btn btn-primary" onclick="criarVM()" id="btnCriarVM">
                <i class="fas fa-plus"></i> Criar VM
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: CRIAR CONTAINER ==================== -->
<div class="modal-overlay" id="modalCriarCT" style="display:none">
    <div class="modal" style="max-width:650px">
        <div class="modal-header">
            <h2><i class="fas fa-cube"></i> Criar Container (LXC)</h2>
            <button class="modal-close" onclick="fecharModal('modalCriarCT')">&times;</button>
        </div>
        <div class="modal-body" style="max-height:70vh;overflow-y:auto">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Node *</label>
                    <select id="createCtNode" class="form-select" onchange="carregarTemplatesParaCriar()"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">VMID *</label>
                    <input type="number" id="createCtId" class="form-input" min="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Hostname *</label>
                    <input type="text" id="createCtHostname" class="form-input" placeholder="meu-container">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Template *</label>
                    <select id="createCtTemplate" class="form-select">
                        <option value="">Carregando...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Senha Root *</label>
                    <input type="password" id="createCtPassword" class="form-input" placeholder="Senha do root">
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-microchip"></i> Recursos</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Cores</label>
                    <input type="number" id="createCtCores" class="form-input" min="1" max="128" value="2">
                </div>
                <div class="form-group">
                    <label class="form-label">RAM (MB)</label>
                    <input type="number" id="createCtMemory" class="form-input" min="64" step="64" value="1024">
                </div>
                <div class="form-group">
                    <label class="form-label">Swap (MB)</label>
                    <input type="number" id="createCtSwap" class="form-input" min="0" step="64" value="512">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Storage Root</label>
                    <input type="text" id="createCtStorage" class="form-input" value="local-lvm">
                </div>
                <div class="form-group">
                    <label class="form-label">Tamanho Rootfs (GB)</label>
                    <input type="number" id="createCtDisk" class="form-input" min="1" value="8">
                </div>
            </div>
            <h4 style="font-size:14px;font-weight:600;margin:16px 0 12px"><i class="fas fa-network-wired"></i> Rede</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">IP (ou dhcp)</label>
                    <input type="text" id="createCtIp" class="form-input" value="dhcp" placeholder="dhcp ou 192.168.1.50/24">
                </div>
                <div class="form-group">
                    <label class="form-label">Bridge</label>
                    <input type="text" id="createCtBridge" class="form-input" value="vmbr0">
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Gateway (se IP estático)</label>
                <input type="text" id="createCtGw" class="form-input" placeholder="192.168.1.1">
            </div>
            <div class="form-group mb-3">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="createCtStart">
                    <span class="form-label" style="margin:0">Iniciar após criar</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="fecharModal('modalCriarCT')">Cancelar</button>
            <button class="btn btn-primary" onclick="criarCT()" id="btnCriarCT">
                <i class="fas fa-plus"></i> Criar Container
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: CLONAR ==================== -->
<div class="modal-overlay" id="modalClone" style="display:none">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h2><i class="fas fa-clone"></i> Clonar VM/CT</h2>
            <button class="modal-close" onclick="fecharModal('modalClone')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cloneServerId">
            <input type="hidden" id="cloneNode">
            <input type="hidden" id="cloneVmid">
            <input type="hidden" id="cloneType">
            <p class="text-muted mb-3" id="cloneSourceLabel" style="font-size:13px"></p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="mb-3">
                <div class="form-group">
                    <label class="form-label">Novo VMID *</label>
                    <input type="number" id="cloneNewId" class="form-input" min="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Novo Nome</label>
                    <input type="text" id="cloneNewName" class="form-input" placeholder="clone-nome">
                </div>
            </div>
            <div class="form-group mb-3">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="cloneFull" checked>
                    <span class="form-label" style="margin:0">Clone completo (full clone)</span>
                </label>
                <small class="text-muted">Linked clone é mais rápido, mas depende do original</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="fecharModal('modalClone')">Cancelar</button>
            <button class="btn btn-primary" onclick="executarClone()" id="btnClone">
                <i class="fas fa-clone"></i> Clonar
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: MIGRAR ==================== -->
<div class="modal-overlay" id="modalMigrate" style="display:none">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h2><i class="fas fa-exchange-alt"></i> Migrar VM/CT</h2>
            <button class="modal-close" onclick="fecharModal('modalMigrate')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="migrateServerId">
            <input type="hidden" id="migrateNode">
            <input type="hidden" id="migrateVmid">
            <input type="hidden" id="migrateType">
            <p class="text-muted mb-3" id="migrateSourceLabel" style="font-size:13px"></p>
            <div class="form-group mb-3">
                <label class="form-label">Node Destino *</label>
                <select id="migrateTarget" class="form-select"></select>
            </div>
            <div class="form-group mb-3">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="migrateOnline" checked>
                    <span class="form-label" style="margin:0">Migração online (live migration)</span>
                </label>
                <small class="text-muted">Se a VM estiver rodando, migra sem desligá-la</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="fecharModal('modalMigrate')">Cancelar</button>
            <button class="btn btn-primary" onclick="executarMigrate()" id="btnMigrate">
                <i class="fas fa-exchange-alt"></i> Migrar
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL: CONSOLE ==================== -->
<div class="modal-overlay" id="modalConsole" style="display:none">
    <div class="modal" style="max-width:95vw;width:1200px;height:85vh">
        <div class="modal-header" style="padding:8px 16px">
            <h2 id="consoleTitle" style="font-size:15px"><i class="fas fa-terminal"></i> Console</h2>
            <div style="display:flex;align-items:center;gap:8px">
                <button class="btn btn-sm btn-outline" onclick="abrirConsoleFullscreen()" title="Abrir em nova aba">
                    <i class="fas fa-external-link-alt"></i>
                </button>
                <button class="modal-close" onclick="fecharConsole()">&times;</button>
            </div>
        </div>
        <div class="modal-body" style="padding:0;flex:1;overflow:hidden">
            <iframe id="consoleFrame" style="width:100%;height:100%;border:none;background:#1a1a1a"></iframe>
        </div>
    </div>
</div>

<script>
// ==========================================
//  ESTADO GLOBAL
// ==========================================
const API = '<?= BASE_URL ?>/api/proxmox.php';
let currentServerId = null;
let nodesCache = [];
let allVMs = [];
let allCTs = [];
let refreshTimer = null;

// ==========================================
//  INICIALIZAÇÃO
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    carregarServidores();
});

async function apiGet(action, params = {}) {
    params.action = action;
    const qs = new URLSearchParams(params).toString();
    const r = await fetch(`${API}?${qs}`);
    const data = await r.json();
    if (!r.ok || data.error) throw new Error(data.error || 'Erro desconhecido');
    return data;
}

async function apiPost(action, body = {}) {
    body.action = action;
    const r = await fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(body)
    });
    const data = await r.json();
    if (!r.ok || data.error) throw new Error(data.error || 'Erro desconhecido');
    return data;
}

function showToast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `pve-toast pve-toast-${type}`;
    el.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':type==='error'?'times-circle':'info-circle'}"></i> ${msg}`;
    document.body.appendChild(el);
    setTimeout(() => el.classList.add('show'), 10);
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 4000);
}

function fecharModal(id) {
    document.getElementById(id).style.display = 'none';
}

function formatBytes(b, p = 2) {
    if (b <= 0) return '0 B';
    const u = ['B','KB','MB','GB','TB','PB'];
    const i = Math.floor(Math.log(b) / Math.log(1024));
    return (b / Math.pow(1024, i)).toFixed(p) + ' ' + u[i];
}

function formatUptime(sec) {
    if (!sec || sec <= 0) return '—';
    const d = Math.floor(sec/86400), h = Math.floor((sec%86400)/3600), m = Math.floor((sec%3600)/60);
    let parts = [];
    if (d > 0) parts.push(d+'d');
    if (h > 0) parts.push(h+'h');
    if (m > 0) parts.push(m+'m');
    return parts.join(' ') || '< 1m';
}

// ==========================================
//  SERVIDORES
// ==========================================
async function carregarServidores() {
    try {
        const res = await apiGet('listar_servidores');
        const sel = document.getElementById('serverSelect');
        sel.innerHTML = '<option value="">— Selecione um servidor —</option>';
        (res.data || []).forEach(s => {
            sel.innerHTML += `<option value="${s.id}" ${s.id == currentServerId ? 'selected':''}>${s.nome} (${s.host}:${s.porta})</option>`;
        });
        if (currentServerId) onServerChange();
    } catch(e) {
        console.error('Erro ao carregar servidores:', e);
    }
}

async function onServerChange() {
    const sid = document.getElementById('serverSelect').value;
    if (!sid) {
        currentServerId = null;
        document.getElementById('pveContent').style.display = 'none';
        document.getElementById('pveEmpty').style.display = 'flex';
        document.getElementById('serverInfo').style.display = 'none';
        document.getElementById('serverActions').style.display = 'none';
        document.getElementById('serverStatus').innerHTML = '';
        if (refreshTimer) clearInterval(refreshTimer);
        return;
    }
    currentServerId = parseInt(sid);
    document.getElementById('pveEmpty').style.display = 'none';
    document.getElementById('pveContent').style.display = 'block';
    document.getElementById('serverActions').style.display = 'flex';
    document.getElementById('serverStatus').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando...';

    try {
        // Testar conexão primeiro
        const testRes = await apiGet('testar_conexao', {id: currentServerId});
        if (testRes.data.conectado) {
            document.getElementById('serverStatus').innerHTML = '<span class="pve-status-dot pve-dot-online"></span> Conectado';
            document.getElementById('serverVersion').textContent = 'PVE ' + (testRes.data.versao || '?');
            document.getElementById('serverInfo').style.display = 'flex';
        } else {
            document.getElementById('serverStatus').innerHTML = '<span class="pve-status-dot pve-dot-offline"></span> Falha';
            showToast('Falha ao conectar: ' + (testRes.data.erro || ''), 'error');
            return;
        }

        // Carregar overview
        await carregarOverview();
        await carregarNodes();

        // Auto-refresh a cada 10s — dados em tempo real
        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            if (currentServerId) refreshAtivo();
        }, 10000);

    } catch(e) {
        document.getElementById('serverStatus').innerHTML = '<span class="pve-status-dot pve-dot-offline"></span> Erro';
        showToast('Erro: ' + e.message, 'error');
    }
}

function toggleAuthFields() {
    const type = document.getElementById('srvAuthType').value;
    document.getElementById('authPasswordFields').style.display = type === 'password' ? 'block' : 'none';
    document.getElementById('authTokenFields').style.display = type === 'apitoken' ? 'block' : 'none';
}

function abrirModalServidor(editId = null) {
    document.getElementById('srvId').value = editId || '';
    document.getElementById('modalServidorTitle').innerHTML = editId
        ? '<i class="fas fa-edit"></i> Editar Servidor'
        : '<i class="fas fa-server"></i> Novo Servidor Proxmox';

    if (!editId) {
        document.getElementById('srvNome').value = '';
        document.getElementById('srvHost').value = '';
        document.getElementById('srvPorta').value = '8006';
        document.getElementById('srvUsuario').value = 'root';
        document.getElementById('srvRealm').value = 'pam';
        document.getElementById('srvSenha').value = '';
        document.getElementById('srvAuthType').value = 'password';
        document.getElementById('srvTokenId').value = '';
        document.getElementById('srvTokenSecret').value = '';
        document.getElementById('srvVerifySSL').checked = false;
        toggleAuthFields();
    }
    document.getElementById('modalServidor').style.display = 'flex';
}

async function editarServidor() {
    if (!currentServerId) return;
    try {
        const res = await apiGet('get_servidor', {id: currentServerId});
        const s = res.data;
        document.getElementById('srvId').value = s.id;
        document.getElementById('srvNome').value = s.nome;
        document.getElementById('srvHost').value = s.host;
        document.getElementById('srvPorta').value = s.porta;
        document.getElementById('srvUsuario').value = s.usuario;
        document.getElementById('srvRealm').value = s.realm || 'pam';
        document.getElementById('srvAuthType').value = s.auth_type || 'password';
        document.getElementById('srvTokenId').value = s.token_id || '';
        document.getElementById('srvVerifySSL').checked = !!parseInt(s.verificar_ssl);
        document.getElementById('srvSenha').value = '';
        document.getElementById('srvTokenSecret').value = '';
        toggleAuthFields();
        document.getElementById('modalServidorTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Servidor';
        document.getElementById('modalServidor').style.display = 'flex';
    } catch(e) {
        showToast('Erro ao carregar servidor: ' + e.message, 'error');
    }
}

async function salvarServidor() {
    const id = document.getElementById('srvId').value;
    const authType = document.getElementById('srvAuthType').value;

    const dados = {
        nome: document.getElementById('srvNome').value.trim(),
        host: document.getElementById('srvHost').value.trim(),
        porta: parseInt(document.getElementById('srvPorta').value) || 8006,
        auth_type: authType,
        verificar_ssl: document.getElementById('srvVerifySSL').checked ? 1 : 0
    };

    if (authType === 'password') {
        dados.usuario = document.getElementById('srvUsuario').value.trim();
        dados.realm = document.getElementById('srvRealm').value;
        dados.senha = document.getElementById('srvSenha').value;
    } else {
        dados.usuario = document.getElementById('srvUsuario').value.trim() || 'api';
        dados.token_id = document.getElementById('srvTokenId').value.trim();
        dados.token_secret = document.getElementById('srvTokenSecret').value.trim();
    }

    if (!dados.nome || !dados.host) {
        showToast('Nome e Host são obrigatórios', 'error');
        return;
    }

    const btn = document.getElementById('btnSalvarSrv');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        if (id) {
            dados.id = parseInt(id);
            await apiPost('atualizar_servidor', dados);
            showToast('Servidor atualizado!');
        } else {
            const res = await apiPost('criar_servidor', dados);
            currentServerId = res.id;
            showToast('Servidor cadastrado!');
        }
        fecharModal('modalServidor');
        await carregarServidores();
        if (currentServerId) {
            document.getElementById('serverSelect').value = currentServerId;
            onServerChange();
        }
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
    }
}

async function testarConexao() {
    if (!currentServerId) return;
    document.getElementById('serverStatus').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
    try {
        const res = await apiGet('testar_conexao', {id: currentServerId});
        if (res.data.conectado) {
            document.getElementById('serverStatus').innerHTML = '<span class="pve-status-dot pve-dot-online"></span> Conectado';
            showToast('Conexão OK — Proxmox v' + (res.data.versao || '?'));
        } else {
            document.getElementById('serverStatus').innerHTML = '<span class="pve-status-dot pve-dot-offline"></span> Falha';
            showToast('Falha: ' + (res.data.erro || 'Erro desconhecido'), 'error');
        }
    } catch(e) {
        document.getElementById('serverStatus').innerHTML = '<span class="pve-status-dot pve-dot-offline"></span> Erro';
        showToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  OVERVIEW
// ==========================================
async function carregarOverview(silent = false) {
    if (!currentServerId) return;
    try {
        const res = await apiGet('overview', {server_id: currentServerId});
        const ov = res.data;

        // Stats
        document.getElementById('statNodes').textContent = (ov.nodes || []).length;
        document.getElementById('statVMs').textContent = ov.vms.total;
        document.getElementById('statCTs').textContent = ov.containers.total;
        document.getElementById('statRunning').textContent = ov.vms.running + ov.containers.running;
        document.getElementById('statCPU').textContent = ov.total_cpu;
        document.getElementById('serverNodeCount').textContent = (ov.nodes || []).length + ' node(s)';

        // Memory
        const memPct = ov.total_mem > 0 ? ((ov.total_mem_used / ov.total_mem) * 100).toFixed(1) : 0;
        document.getElementById('statMem').textContent = formatBytes(ov.total_mem_used) + ' / ' + formatBytes(ov.total_mem);

        // Resource bars
        document.getElementById('memBar').style.width = memPct + '%';
        document.getElementById('memText').textContent = memPct + '% (' + formatBytes(ov.total_mem_used) + ' / ' + formatBytes(ov.total_mem) + ')';

        const diskPct = ov.total_disk > 0 ? ((ov.total_disk_used / ov.total_disk) * 100).toFixed(1) : 0;
        document.getElementById('diskBar').style.width = diskPct + '%';
        document.getElementById('diskText').textContent = diskPct + '% (' + formatBytes(ov.total_disk_used) + ' / ' + formatBytes(ov.total_disk) + ')';

        // CPU avg (from nodes)
        let cpuAvg = 0;
        if (ov.nodes.length) {
            cpuAvg = ov.nodes.reduce((sum, n) => sum + (n.cpu || 0), 0) / ov.nodes.length * 100;
        }
        document.getElementById('cpuBar').style.width = cpuAvg.toFixed(1) + '%';
        document.getElementById('cpuText').textContent = cpuAvg.toFixed(1) + '%';

        // Color bars based on usage
        colorBar('cpuBar', cpuAvg);
        colorBar('memBar', parseFloat(memPct));
        colorBar('diskBar', parseFloat(diskPct));

    } catch(e) {
        if (!silent) showToast('Erro ao carregar overview: ' + e.message, 'error');
    }
}

function colorBar(id, pct) {
    const bar = document.getElementById(id);
    if (pct > 85) bar.style.background = '#EF4444';
    else if (pct > 65) bar.style.background = '#F59E0B';
    else bar.style.background = '#22C55E';
}

// ==========================================
//  NODES
// ==========================================
async function carregarNodes() {
    if (!currentServerId) return;
    try {
        const res = await apiGet('nodes', {server_id: currentServerId});
        nodesCache = res.data || [];

        // Populate node selects
        ['vmNodeSelect', 'ctNodeSelect', 'storageNodeSelect'].forEach(selId => {
            const sel = document.getElementById(selId);
            const prev = sel.value;
            sel.innerHTML = selId === 'storageNodeSelect' ? '<option value="">Selecione</option>' : '<option value="">Todos</option>';
            nodesCache.forEach(n => {
                sel.innerHTML += `<option value="${n.node}" ${n.node === prev ? 'selected':''}>${n.node}</option>`;
            });
            // Auto-select first if storageNodeSelect
            if (selId === 'storageNodeSelect' && !prev && nodesCache.length) {
                sel.value = nodesCache[0].node;
            }
        });

        // Render nodes grid
        renderNodes();
    } catch(e) {
        showToast('Erro ao carregar nodes: ' + e.message, 'error');
    }
}

function renderNodes() {
    const grid = document.getElementById('nodesGrid');
    if (!nodesCache.length) {
        grid.innerHTML = '<div class="pve-empty-msg"><i class="fas fa-info-circle"></i> Nenhum node encontrado</div>';
        return;
    }

    const existingCards = grid.querySelectorAll('.pve-node-card[data-node]');

    if (existingCards.length) {
        // Smart update in-place
        const existingMap = {};
        existingCards.forEach(c => { existingMap[c.dataset.node] = c; });

        nodesCache.forEach(n => {
            const card = existingMap[n.node];
            if (card) {
                const online = n.status === 'online';
                const cpuPct = ((n.cpu || 0) * 100).toFixed(1);
                const memPct = n.maxmem > 0 ? ((n.mem / n.maxmem) * 100).toFixed(1) : 0;
                const diskPct = n.maxdisk > 0 ? ((n.disk / n.maxdisk) * 100).toFixed(1) : 0;

                const metrics = card.querySelectorAll('.pve-metric');
                if (metrics[0]) {
                    const fill = metrics[0].querySelector('.pve-mini-fill');
                    fill.style.width = cpuPct + '%';
                    fill.style.background = cpuPct > 85 ? '#EF4444' : cpuPct > 65 ? '#F59E0B' : '';
                    metrics[0].querySelector('.pve-metric-val').textContent = cpuPct + '%';
                }
                if (metrics[1]) {
                    const fill = metrics[1].querySelector('.pve-mini-fill');
                    fill.style.width = memPct + '%';
                    fill.style.background = memPct > 85 ? '#EF4444' : memPct > 65 ? '#F59E0B' : '';
                    metrics[1].querySelector('.pve-metric-val').textContent = formatBytes(n.mem) + ' / ' + formatBytes(n.maxmem);
                }
                if (metrics[2]) {
                    const fill = metrics[2].querySelector('.pve-mini-fill');
                    fill.style.width = diskPct + '%';
                    fill.style.background = diskPct > 85 ? '#EF4444' : diskPct > 65 ? '#F59E0B' : '';
                    metrics[2].querySelector('.pve-metric-val').textContent = formatBytes(n.disk) + ' / ' + formatBytes(n.maxdisk);
                }

                const footer = card.querySelector('.pve-node-footer span');
                if (footer) footer.innerHTML = '<i class="fas fa-clock"></i> Uptime: ' + formatUptime(n.uptime);
            }
        });
        return;
    }

    // Primeiro carregamento — render completo
    grid.innerHTML = nodesCache.map(n => {
        const online = n.status === 'online';
        const cpuPct = ((n.cpu || 0) * 100).toFixed(1);
        const memPct = n.maxmem > 0 ? ((n.mem / n.maxmem) * 100).toFixed(1) : 0;
        const diskPct = n.maxdisk > 0 ? ((n.disk / n.maxdisk) * 100).toFixed(1) : 0;

        return `
        <div class="pve-node-card ${online ? 'node-online' : 'node-offline'}" data-node="${n.node}">
            <div class="pve-node-header">
                <div class="pve-node-name">
                    <span class="pve-status-dot ${online ? 'pve-dot-online' : 'pve-dot-offline'}"></span>
                    <strong>${n.node}</strong>
                </div>
                <span class="pve-badge ${online ? 'pve-badge-success' : 'pve-badge-danger'}">${n.status || 'unknown'}</span>
            </div>
            <div class="pve-node-metrics">
                <div class="pve-metric">
                    <span class="pve-metric-label"><i class="fas fa-microchip"></i> CPU</span>
                    <div class="pve-mini-bar"><div class="pve-mini-fill" style="width:${cpuPct}%;${cpuPct > 85 ? 'background:#EF4444' : cpuPct > 65 ? 'background:#F59E0B' : ''}"></div></div>
                    <span class="pve-metric-val">${cpuPct}%</span>
                </div>
                <div class="pve-metric">
                    <span class="pve-metric-label"><i class="fas fa-memory"></i> RAM</span>
                    <div class="pve-mini-bar"><div class="pve-mini-fill" style="width:${memPct}%;${memPct > 85 ? 'background:#EF4444' : memPct > 65 ? 'background:#F59E0B' : ''}"></div></div>
                    <span class="pve-metric-val">${formatBytes(n.mem)} / ${formatBytes(n.maxmem)}</span>
                </div>
                <div class="pve-metric">
                    <span class="pve-metric-label"><i class="fas fa-hdd"></i> Disco</span>
                    <div class="pve-mini-bar"><div class="pve-mini-fill" style="width:${diskPct}%;${diskPct > 85 ? 'background:#EF4444' : diskPct > 65 ? 'background:#F59E0B' : ''}"></div></div>
                    <span class="pve-metric-val">${formatBytes(n.disk)} / ${formatBytes(n.maxdisk)}</span>
                </div>
            </div>
            <div class="pve-node-footer">
                <span class="text-muted" style="font-size:12px"><i class="fas fa-clock"></i> Uptime: ${formatUptime(n.uptime)}</span>
            </div>
        </div>`;
    }).join('');
}

// ==========================================
//  VMs
// ==========================================
async function carregarVMs(silent = false) {
    if (!currentServerId) return;
    const grid = document.getElementById('vmsGrid');
    if (!silent) {
        grid.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando VMs...</p></div>';
    }

    const selectedNode = document.getElementById('vmNodeSelect').value;
    allVMs = [];

    try {
        const nodesToQuery = selectedNode ? [{node: selectedNode}] : nodesCache;
        for (const n of nodesToQuery) {
            const res = await apiGet('vms', {server_id: currentServerId, node: n.node});
            (res.data || []).forEach(vm => { vm._node = n.node; allVMs.push(vm); });
        }
        filtrarVMs();
    } catch(e) {
        if (!silent) grid.innerHTML = `<div class="pve-empty-msg"><i class="fas fa-exclamation-triangle" style="color:#EF4444"></i> Erro: ${e.message}</div>`;
    }
}

function filtrarVMs() {
    const busca = document.getElementById('vmBusca').value.toLowerCase();
    const statusFilter = document.getElementById('vmStatusFilter').value;
    let filtered = allVMs;

    if (busca) {
        filtered = filtered.filter(v => (v.name||'').toLowerCase().includes(busca) || String(v.vmid).includes(busca));
    }
    if (statusFilter) {
        filtered = filtered.filter(v => v.status === statusFilter);
    }

    renderVMGrid(filtered, 'vmsGrid', 'qemu');
}

function buildVMCardHTML(vm, type) {
    const running = vm.status === 'running';
    const paused = vm.status === 'paused';
    const cpuPct = running ? ((vm.cpu || 0) * 100).toFixed(1) : '0.0';
    const memPct = vm.maxmem > 0 ? ((vm.mem / vm.maxmem) * 100).toFixed(1) : 0;

    const statusClass = running ? 'pve-vm-running' : paused ? 'pve-vm-paused' : 'pve-vm-stopped';
    const statusLabel = running ? 'Rodando' : paused ? 'Pausado' : 'Parado';
    const statusDot = running ? 'pve-dot-online' : paused ? 'pve-dot-warning' : 'pve-dot-offline';
    const icon = type === 'qemu' ? 'fa-desktop' : 'fa-cube';
    const prefix = type === 'qemu' ? 'vm' : 'ct';

    return `
    <div class="pve-vm-card ${statusClass}" data-vmid="${vm.vmid}" data-status="${vm.status}" onclick="abrirDetalhesVM(${currentServerId},'${vm._node}',${vm.vmid},'${type}')">
        <div class="pve-vm-header">
            <div class="pve-vm-id-name">
                <span class="pve-vm-id">${vm.vmid}</span>
                <span class="pve-vm-name"><i class="fas ${icon}"></i> ${vm.name || 'sem nome'}</span>
            </div>
            <span class="pve-status-dot ${statusDot}" title="${statusLabel}"></span>
        </div>
        <div class="pve-vm-metrics">
            ${running ? `
            <div class="pve-metric">
                <span class="pve-metric-label">CPU</span>
                <div class="pve-mini-bar"><div class="pve-mini-fill" style="width:${cpuPct}%"></div></div>
                <span class="pve-metric-val">${cpuPct}%</span>
            </div>
            <div class="pve-metric">
                <span class="pve-metric-label">RAM</span>
                <div class="pve-mini-bar"><div class="pve-mini-fill" style="width:${memPct}%"></div></div>
                <span class="pve-metric-val">${formatBytes(vm.mem || 0)}</span>
            </div>
            <div class="pve-vm-extra">
                <span><i class="fas fa-clock"></i> ${formatUptime(vm.uptime)}</span>
                <span><i class="fas fa-hdd"></i> ${formatBytes(vm.maxdisk || 0)}</span>
            </div>
            ` : `
            <div class="pve-vm-stopped-info">
                <span><i class="fas fa-hdd"></i> ${formatBytes(vm.maxdisk || 0)}</span>
                <span><i class="fas fa-memory"></i> ${formatBytes(vm.maxmem || 0)}</span>
            </div>
            `}
        </div>
        <div class="pve-vm-footer">
            <span class="pve-badge pve-badge-default">${vm._node}</span>
            <div class="pve-vm-actions" onclick="event.stopPropagation()">
                ${!running ? `<button class="pve-act-btn pve-act-start" title="Iniciar" onclick="acaoVM('${prefix}_start',${currentServerId},'${vm._node}',${vm.vmid},'${vm.name||''}')"><i class="fas fa-play"></i></button>` : ''}
                ${running ? `<button class="pve-act-btn pve-act-reboot" title="Reiniciar" onclick="acaoVM('${prefix}_reboot',${currentServerId},'${vm._node}',${vm.vmid},'${vm.name||''}')"><i class="fas fa-redo"></i></button>` : ''}
                ${running ? `<button class="pve-act-btn pve-act-shutdown" title="Desligar" onclick="acaoVM('${prefix}_shutdown',${currentServerId},'${vm._node}',${vm.vmid},'${vm.name||''}')"><i class="fas fa-power-off"></i></button>` : ''}
                ${running ? `<button class="pve-act-btn pve-act-stop" title="Forçar Parada" onclick="acaoVM('${prefix}_stop',${currentServerId},'${vm._node}',${vm.vmid},'${vm.name||''}')"><i class="fas fa-stop"></i></button>` : ''}
                ${!running ? `<button class="pve-act-btn pve-act-stop" title="Excluir" onclick="excluirVM(${currentServerId},'${vm._node}',${vm.vmid},'${vm.name||''}','${type}')"><i class="fas fa-trash"></i></button>` : ''}
            </div>
        </div>
    </div>`;
}

function updateVMCardInPlace(card, vm, type) {
    const running = vm.status === 'running';
    const paused = vm.status === 'paused';
    const oldStatus = card.dataset.status;

    // Se o status mudou (running<->stopped), rebuild o card inteiro com transição
    if (oldStatus !== vm.status) {
        const temp = document.createElement('div');
        temp.innerHTML = buildVMCardHTML(vm, type).trim();
        const newCard = temp.firstElementChild;
        card.className = newCard.className;
        card.innerHTML = newCard.innerHTML;
        card.dataset.status = vm.status;
        card.dataset.vmid = vm.vmid;
        card.setAttribute('onclick', newCard.getAttribute('onclick'));
        return;
    }

    // Mesmo status — atualizar apenas métricas in-place (sem flicker)
    if (running) {
        const cpuPct = ((vm.cpu || 0) * 100).toFixed(1);
        const memPct = vm.maxmem > 0 ? ((vm.mem / vm.maxmem) * 100).toFixed(1) : 0;

        const metrics = card.querySelectorAll('.pve-metric');
        if (metrics[0]) {
            metrics[0].querySelector('.pve-mini-fill').style.width = cpuPct + '%';
            metrics[0].querySelector('.pve-metric-val').textContent = cpuPct + '%';
        }
        if (metrics[1]) {
            metrics[1].querySelector('.pve-mini-fill').style.width = memPct + '%';
            metrics[1].querySelector('.pve-metric-val').textContent = formatBytes(vm.mem || 0);
        }

        const extra = card.querySelector('.pve-vm-extra');
        if (extra) {
            const spans = extra.querySelectorAll('span');
            if (spans[0]) spans[0].innerHTML = '<i class="fas fa-clock"></i> ' + formatUptime(vm.uptime);
            if (spans[1]) spans[1].innerHTML = '<i class="fas fa-hdd"></i> ' + formatBytes(vm.maxdisk || 0);
        }
    }
}

function renderVMGrid(items, gridId, type) {
    const grid = document.getElementById(gridId);

    if (!items.length) {
        grid.innerHTML = '<div class="pve-empty-msg"><i class="fas fa-info-circle"></i> Nenhum item encontrado</div>';
        return;
    }

    // Verificar se já existem cards no DOM para fazer diff
    const existingCards = grid.querySelectorAll('.pve-vm-card[data-vmid]');

    if (!existingCards.length) {
        // Primeiro carregamento — render completo
        grid.innerHTML = items.map(vm => buildVMCardHTML(vm, type)).join('');
        return;
    }

    // Smart diff: atualizar cards existentes in-place
    const existingMap = {};
    existingCards.forEach(card => { existingMap[card.dataset.vmid] = card; });
    const newVMIDs = new Set(items.map(vm => String(vm.vmid)));

    // Atualizar existentes ou adicionar novos
    items.forEach(vm => {
        const vmid = String(vm.vmid);
        const existingCard = existingMap[vmid];

        if (existingCard) {
            updateVMCardInPlace(existingCard, vm, type);
            delete existingMap[vmid];
        } else {
            // Nova VM — adicionar com fade-in
            const temp = document.createElement('div');
            temp.innerHTML = buildVMCardHTML(vm, type).trim();
            const newCard = temp.firstElementChild;
            newCard.classList.add('pve-card-entering');
            grid.appendChild(newCard);
            requestAnimationFrame(() => {
                requestAnimationFrame(() => { newCard.classList.remove('pve-card-entering'); });
            });
        }
    });

    // Remover cards que não existem mais com fade-out
    Object.values(existingMap).forEach(card => {
        card.classList.add('pve-card-leaving');
        setTimeout(() => card.remove(), 300);
    });
}

// ==========================================
//  CONTAINERS
// ==========================================
async function carregarContainers(silent = false) {
    if (!currentServerId) return;
    const grid = document.getElementById('ctsGrid');
    if (!silent) {
        grid.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando containers...</p></div>';
    }

    const selectedNode = document.getElementById('ctNodeSelect').value;
    allCTs = [];

    try {
        const nodesToQuery = selectedNode ? [{node: selectedNode}] : nodesCache;
        for (const n of nodesToQuery) {
            const res = await apiGet('containers', {server_id: currentServerId, node: n.node});
            (res.data || []).forEach(ct => { ct._node = n.node; allCTs.push(ct); });
        }
        filtrarContainers();
    } catch(e) {
        if (!silent) grid.innerHTML = `<div class="pve-empty-msg"><i class="fas fa-exclamation-triangle" style="color:#EF4444"></i> Erro: ${e.message}</div>`;
    }
}

function filtrarContainers() {
    const busca = document.getElementById('ctBusca').value.toLowerCase();
    const statusFilter = document.getElementById('ctStatusFilter').value;
    let filtered = allCTs;

    if (busca) {
        filtered = filtered.filter(c => (c.name||'').toLowerCase().includes(busca) || String(c.vmid).includes(busca));
    }
    if (statusFilter) {
        filtered = filtered.filter(c => c.status === statusFilter);
    }

    renderVMGrid(filtered, 'ctsGrid', 'lxc');
}

// ==========================================
//  STORAGE
// ==========================================
async function carregarStorage(silent = false) {
    if (!currentServerId) return;
    const node = document.getElementById('storageNodeSelect').value;
    if (!node) { document.getElementById('storageGrid').innerHTML = '<div class="pve-empty-msg">Selecione um node</div>'; return; }

    const grid = document.getElementById('storageGrid');
    if (!silent) {
        grid.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando storage...</p></div>';
    }

    try {
        const res = await apiGet('storage', {server_id: currentServerId, node});
        const stores = res.data || [];

        if (!stores.length) {
            grid.innerHTML = '<div class="pve-empty-msg"><i class="fas fa-info-circle"></i> Nenhum storage encontrado</div>';
            return;
        }

        // Verificar se já existem cards de storage no DOM para smart update
        const existingCards = grid.querySelectorAll('.pve-storage-card[data-storage]');

        if (existingCards.length) {
            const existingMap = {};
            existingCards.forEach(c => { existingMap[c.dataset.storage] = c; });

            stores.forEach(s => {
                const card = existingMap[s.storage];
                if (card) {
                    const pct = s.total > 0 ? ((s.used / s.total) * 100).toFixed(1) : 0;
                    const fill = card.querySelector('.pve-progress-fill');
                    if (fill) {
                        fill.style.width = pct + '%';
                        fill.style.background = pct > 85 ? '#EF4444' : pct > 65 ? '#F59E0B' : '#22C55E';
                    }
                    const pctText = card.querySelector('.pve-progress-text');
                    if (pctText) pctText.textContent = pct + '%';

                    const sizes = card.querySelectorAll('.pve-storage-sizes span');
                    if (sizes[0]) sizes[0].textContent = 'Usado: ' + formatBytes(s.used || 0);
                    if (sizes[1]) sizes[1].textContent = 'Total: ' + formatBytes(s.total || 0);
                    if (sizes[2]) sizes[2].textContent = 'Livre: ' + formatBytes((s.total || 0) - (s.used || 0));
                }
            });
            return;
        }

        // Primeiro carregamento — render completo
        grid.innerHTML = stores.map(s => {
            const pct = s.total > 0 ? ((s.used / s.total) * 100).toFixed(1) : 0;
            const active = s.active == 1;
            const iconMap = {dir: 'fa-folder', lvm: 'fa-database', zfspool: 'fa-water', nfs: 'fa-network-wired', cifs: 'fa-share-alt', cephfs: 'fa-project-diagram'};
            const icon = iconMap[s.type] || 'fa-hdd';

            return `
            <div class="pve-storage-card" data-storage="${s.storage}">
                <div class="pve-storage-header">
                    <div><i class="fas ${icon}" style="color:#E67E22;margin-right:8px"></i><strong>${s.storage}</strong></div>
                    <span class="pve-badge ${active ? 'pve-badge-success' : 'pve-badge-danger'}">${active ? 'Ativo' : 'Inativo'}</span>
                </div>
                <div class="pve-storage-type">${s.type} ${s.content ? '— ' + s.content : ''}</div>
                <div class="pve-progress-wrap" style="margin-top:12px">
                    <div class="pve-progress-bar">
                        <div class="pve-progress-fill" style="width:${pct}%;${pct > 85 ? 'background:#EF4444' : pct > 65 ? 'background:#F59E0B' : 'background:#22C55E'}"></div>
                    </div>
                    <span class="pve-progress-text">${pct}%</span>
                </div>
                <div class="pve-storage-sizes">
                    <span>Usado: ${formatBytes(s.used || 0)}</span>
                    <span>Total: ${formatBytes(s.total || 0)}</span>
                    <span>Livre: ${formatBytes((s.total || 0) - (s.used || 0))}</span>
                </div>
            </div>`;
        }).join('');
    } catch(e) {
        grid.innerHTML = `<div class="pve-empty-msg"><i class="fas fa-exclamation-triangle" style="color:#EF4444"></i> Erro: ${e.message}</div>`;
    }
}

// ==========================================
//  AÇÕES VM/CT
// ==========================================
async function acaoVM(action, serverId, node, vmid, vmNome) {
    const labels = {
        vm_start: 'Iniciar', vm_stop: 'Forçar Parada', vm_shutdown: 'Desligar', vm_reset: 'Resetar',
        vm_reboot: 'Reiniciar', vm_suspend: 'Suspender', vm_resume: 'Resumir',
        ct_start: 'Iniciar', ct_stop: 'Forçar Parada', ct_shutdown: 'Desligar', ct_reboot: 'Reiniciar'
    };

    const dangerous = ['vm_stop', 'vm_reset', 'ct_stop'];
    if (dangerous.includes(action)) {
        if (!confirm(`⚠️ ${labels[action]} VM/CT ${vmid} (${vmNome})?\nEsta ação pode causar perda de dados.`)) return;
    }

    try {
        showToast(`Executando ${labels[action]} na VM ${vmid}...`, 'info');
        await apiPost(action, {server_id: serverId, node, vmid, vm_nome: vmNome});
        showToast(`${labels[action]} executado com sucesso! VM ${vmid}`, 'success');

        // Refresh depois de 2 segundos para dar tempo da ação propagar
        setTimeout(() => {
            carregarOverview(true);
            const activeTab = document.querySelector('.ad-tab.active')?.dataset.tab;
            if (activeTab === 'vms') carregarVMs(true);
            else if (activeTab === 'containers') carregarContainers(true);
        }, 2500);
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  DETALHES VM/CT
// ==========================================
let currentDetailVM = null;
let hwOriginalMac = '';
let hwOriginalNet0 = '';

async function abrirDetalhesVM(serverId, node, vmid, type) {
    document.getElementById('modalVMDetail').style.display = 'flex';
    const body = document.getElementById('vmDetailBody');
    body.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando detalhes...</p></div>';

    const icon = type === 'qemu' ? 'fa-desktop' : 'fa-cube';
    const typeLabel = type === 'qemu' ? 'VM' : 'Container';

    try {
        const statusAction = type === 'qemu' ? 'vm_status' : 'container_status';
        const res = await apiGet(statusAction, {server_id: serverId, node, vmid});
        const status = res.data.status || {};
        const config = res.data.config || {};

        currentDetailVM = {serverId, node, vmid, type, status, config};

        // Carregar snapshots
        let snaps = [];
        try {
            const snapRes = await apiGet('snapshots', {server_id: serverId, node, vmid, type});
            snaps = (snapRes.data || []).filter(s => s.name !== 'current');
        } catch(e) {}

        document.getElementById('vmDetailTitle').innerHTML = `<i class="fas ${icon}"></i> ${typeLabel} ${vmid} — ${status.name || config.name || 'sem nome'}`;

        const running = status.status === 'running';
        const cpuPct = running ? ((status.cpu || 0) * 100).toFixed(1) : 0;
        const memPct = status.maxmem > 0 ? (((status.mem || 0) / status.maxmem) * 100).toFixed(1) : 0;

        body.innerHTML = `
        <div class="pve-detail-status">
            <span class="pve-status-dot ${running ? 'pve-dot-online' : 'pve-dot-offline'}"></span>
            <span class="pve-badge ${running ? 'pve-badge-success' : 'pve-badge-danger'}" style="font-size:14px">${running ? 'Rodando' : 'Parado'}</span>
            <span class="pve-badge pve-badge-default">${node}</span>
            ${status.qmpstatus ? `<span class="pve-badge pve-badge-info">${status.qmpstatus}</span>` : ''}
        </div>

        <!-- Config Info -->
        <div class="pve-detail-grid">
            <div class="pve-detail-item">
                <span class="pve-detail-label"><i class="fas fa-microchip"></i> CPUs</span>
                <span class="pve-detail-val">${config.cores || config.cpulimit || status.cpus || '—'} core(s) ${config.sockets ? '/ ' + config.sockets + ' socket(s)' : ''}</span>
            </div>
            <div class="pve-detail-item">
                <span class="pve-detail-label"><i class="fas fa-memory"></i> Memória</span>
                <span class="pve-detail-val">${config.memory ? config.memory + ' MB' : formatBytes(status.maxmem || 0)}</span>
            </div>
            <div class="pve-detail-item">
                <span class="pve-detail-label"><i class="fas fa-hdd"></i> Disco</span>
                <span class="pve-detail-val">${formatBytes(status.maxdisk || 0)}</span>
            </div>
            <div class="pve-detail-item">
                <span class="pve-detail-label"><i class="fas fa-clock"></i> Uptime</span>
                <span class="pve-detail-val">${formatUptime(status.uptime || 0)}</span>
            </div>
            ${config.ostype ? `<div class="pve-detail-item">
                <span class="pve-detail-label"><i class="fas fa-laptop-code"></i> OS Type</span>
                <span class="pve-detail-val">${config.ostype}</span>
            </div>` : ''}
            ${config.boot ? `<div class="pve-detail-item">
                <span class="pve-detail-label"><i class="fas fa-play"></i> Boot Order</span>
                <span class="pve-detail-val">${config.boot}</span>
            </div>` : ''}
            ${config.net0 ? `<div class="pve-detail-item" style="grid-column:1/-1">
                <span class="pve-detail-label"><i class="fas fa-network-wired"></i> Rede</span>
                <span class="pve-detail-val" style="font-size:12px;word-break:break-all">${config.net0}</span>
            </div>` : ''}
        </div>

        ${running ? `
        <!-- Uso Atual -->
        <h4 style="font-size:14px;font-weight:600;margin:20px 0 12px"><i class="fas fa-chart-bar"></i> Uso Atual</h4>
        <div class="pve-detail-metrics">
            <div class="pve-metric-big">
                <span>CPU</span>
                <div class="pve-progress-wrap">
                    <div class="pve-progress-bar"><div class="pve-progress-fill" style="width:${cpuPct}%;${cpuPct > 85 ? 'background:#EF4444' : cpuPct > 65 ? 'background:#F59E0B' : 'background:#22C55E'}"></div></div>
                    <span class="pve-progress-text">${cpuPct}%</span>
                </div>
            </div>
            <div class="pve-metric-big">
                <span>Memória</span>
                <div class="pve-progress-wrap">
                    <div class="pve-progress-bar"><div class="pve-progress-fill" style="width:${memPct}%;${memPct > 85 ? 'background:#EF4444' : memPct > 65 ? 'background:#F59E0B' : 'background:#22C55E'}"></div></div>
                    <span class="pve-progress-text">${memPct}% (${formatBytes(status.mem || 0)} / ${formatBytes(status.maxmem || 0)})</span>
                </div>
            </div>
            ${status.netin !== undefined ? `
            <div style="display:flex;gap:24px;margin-top:8px">
                <span style="font-size:13px"><i class="fas fa-arrow-down" style="color:#22C55E"></i> Net In: ${formatBytes(status.netin || 0)}</span>
                <span style="font-size:13px"><i class="fas fa-arrow-up" style="color:#3B82F6"></i> Net Out: ${formatBytes(status.netout || 0)}</span>
                <span style="font-size:13px"><i class="fas fa-exchange-alt" style="color:#F59E0B"></i> Disk R: ${formatBytes(status.diskread || 0)} / W: ${formatBytes(status.diskwrite || 0)}</span>
            </div>
            ` : ''}
        </div>
        ` : ''}

        <!-- Snapshots -->
        <h4 style="font-size:14px;font-weight:600;margin:20px 0 12px">
            <i class="fas fa-camera"></i> Snapshots (${snaps.length})
            <button class="btn btn-sm btn-outline" style="margin-left:8px" onclick="abrirModalSnapshot(${serverId},'${node}',${vmid},'${type}')">
                <i class="fas fa-plus"></i> Novo
            </button>
        </h4>
        ${snaps.length ? `
        <table class="table" style="font-size:13px">
            <thead><tr><th>Nome</th><th>Descrição</th><th>Ações</th></tr></thead>
            <tbody>
                ${snaps.map(s => `
                <tr>
                    <td><i class="fas fa-camera" style="color:#6366F1;margin-right:6px"></i>${s.name}</td>
                    <td>${s.description || '—'}</td>
                    <td>
                        <button class="pve-act-btn pve-act-reboot" title="Rollback" onclick="rollbackSnap(${serverId},'${node}',${vmid},'${s.name}','${type}')"><i class="fas fa-undo"></i></button>
                        <button class="pve-act-btn pve-act-stop" title="Excluir" onclick="excluirSnap(${serverId},'${node}',${vmid},'${s.name}','${type}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`).join('')}
            </tbody>
        </table>
        ` : '<p class="text-muted" style="font-size:13px">Nenhum snapshot</p>'}
        `;

        // Footer actions — enhanced with Edit, Delete, Console, Clone, Migrate
        const prefix = type === 'qemu' ? 'vm' : 'ct';

        document.getElementById('vmDetailFooter').innerHTML = `
            <div style="display:flex;gap:6px;flex-wrap:wrap;width:100%">
                <!-- Power Actions -->
                ${!running ? `<button class="btn btn-success btn-sm" onclick="acaoVM('${prefix}_start',${serverId},'${node}',${vmid},'');fecharModal('modalVMDetail')"><i class="fas fa-play"></i> Iniciar</button>` : ''}
                ${running ? `<button class="btn btn-primary btn-sm" onclick="acaoVM('${prefix}_reboot',${serverId},'${node}',${vmid},'');fecharModal('modalVMDetail')"><i class="fas fa-redo"></i> Reiniciar</button>` : ''}
                ${running ? `<button class="btn btn-secondary btn-sm" onclick="acaoVM('${prefix}_shutdown',${serverId},'${node}',${vmid},'');fecharModal('modalVMDetail')"><i class="fas fa-power-off"></i> Desligar</button>` : ''}
                ${running ? `<button class="btn btn-danger btn-sm" onclick="acaoVM('${prefix}_stop',${serverId},'${node}',${vmid},'');fecharModal('modalVMDetail')"><i class="fas fa-stop"></i> Forçar Parada</button>` : ''}

                <span style="border-left:1px solid var(--border);height:28px;margin:0 4px"></span>

                <!-- Console -->
                <button class="btn btn-outline btn-sm" onclick="abrirConsole(${serverId},'${node}',${vmid},'${type}')" ${!running ? 'disabled title="VM precisa estar rodando"' : ''}>
                    <i class="fas fa-terminal"></i> Console
                </button>

                <!-- Edit Hardware -->
                ${type === 'qemu' ? `<button class="btn btn-outline btn-sm" onclick="abrirEditarHardware(${serverId},'${node}',${vmid},'${type}')"><i class="fas fa-cogs"></i> Hardware</button>` : ''}

                <!-- Clone -->
                <button class="btn btn-outline btn-sm" onclick="abrirClonar(${serverId},'${node}',${vmid},'${type}','${status.name || config.name || ''}')"><i class="fas fa-clone"></i> Clonar</button>

                <!-- Migrate -->
                ${nodesCache.length > 1 ? `<button class="btn btn-outline btn-sm" onclick="abrirMigrar(${serverId},'${node}',${vmid},'${type}','${status.name || config.name || ''}')"><i class="fas fa-exchange-alt"></i> Migrar</button>` : ''}

                <!-- Snapshot & Backup -->
                <button class="btn btn-outline btn-sm" onclick="abrirModalSnapshot(${serverId},'${node}',${vmid},'${type}')"><i class="fas fa-camera"></i> Snap</button>
                <button class="btn btn-outline btn-sm" onclick="backupVM(${serverId},'${node}',${vmid},'${type}')"><i class="fas fa-download"></i> Backup</button>

                <span style="border-left:1px solid var(--border);height:28px;margin:0 4px"></span>

                <!-- Delete -->
                ${!running ? `<button class="btn btn-danger btn-sm" onclick="excluirVM(${serverId},'${node}',${vmid},'${status.name || config.name || ''}','${type}')"><i class="fas fa-trash"></i> Excluir</button>` : ''}
            </div>
        `;

    } catch(e) {
        body.innerHTML = `<div class="pve-empty-msg"><i class="fas fa-exclamation-triangle" style="color:#EF4444"></i> Erro ao carregar detalhes: ${e.message}</div>`;
        document.getElementById('vmDetailFooter').innerHTML = '';
    }
}

// ==========================================
//  EDITAR HARDWARE
// ==========================================
async function abrirEditarHardware(serverId, node, vmid, type) {
    document.getElementById('hwServerId').value = serverId;
    document.getElementById('hwNode').value = node;
    document.getElementById('hwVmid').value = vmid;
    document.getElementById('hwType').value = type;

    try {
        const statusAction = type === 'qemu' ? 'vm_status' : 'container_status';
        const res = await apiGet(statusAction, {server_id: serverId, node, vmid});
        const config = res.data.config || {};
        const status = res.data.status || {};
        const running = status.status === 'running';

        document.getElementById('hwRunningWarn').style.display = running ? 'flex' : 'none';

        // Populate fields
        document.getElementById('hwCores').value = config.cores || 1;
        document.getElementById('hwSockets').value = config.sockets || 1;
        document.getElementById('hwMemory').value = config.memory || 2048;
        document.getElementById('hwBalloon').value = config.balloon !== undefined ? config.balloon : 0;
        document.getElementById('hwOnboot').checked = !!parseInt(config.onboot);
        document.getElementById('hwOsType').value = config.ostype || 'l26';
        document.getElementById('hwBootOrder').value = config.boot || '';

        // CPU type
        const cpuType = config.cpu || 'host';
        document.getElementById('hwCpuType').value = cpuType.split(',')[0];

        // Network
        hwOriginalNet0 = config.net0 || '';
        hwOriginalMac = '';
        if (config.net0) {
            const netParts = config.net0.split(',');
            const modelPart = netParts[0] || '';
            const modelMatch = modelPart.match(/^(\w+)=/);
            if (modelMatch) document.getElementById('hwNetModel').value = modelMatch[1];

            // Extract existing MAC address
            const macMatch = modelPart.match(/=([0-9A-Fa-f:]{17})/);
            if (macMatch) hwOriginalMac = macMatch[1];

            const bridgeMatch = config.net0.match(/bridge=(\w+)/);
            if (bridgeMatch) document.getElementById('hwNetBridge').value = bridgeMatch[1];

            const vlanMatch = config.net0.match(/tag=(\d+)/);
            document.getElementById('hwNetVlan').value = vlanMatch ? vlanMatch[1] : '';
        }

        // Disk info
        let diskLabel = '—';
        let diskKey = 'scsi0';
        ['scsi0','virtio0','sata0','ide0','scsi1','virtio1'].forEach(d => {
            if (config[d]) {
                diskLabel = config[d];
                diskKey = d;
            }
        });
        document.getElementById('hwDiskCurrent').textContent = diskLabel;
        document.getElementById('hwDiskName').value = diskKey;
        document.getElementById('hwDiskResize').value = '';

        document.getElementById('modalEditHW').style.display = 'flex';
    } catch(e) {
        showToast('Erro ao carregar config: ' + e.message, 'error');
    }
}

async function salvarHardware() {
    const serverId = parseInt(document.getElementById('hwServerId').value);
    const node = document.getElementById('hwNode').value;
    const vmid = parseInt(document.getElementById('hwVmid').value);
    const type = document.getElementById('hwType').value;

    const btn = document.getElementById('btnSalvarHW');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        // Build config update
        const configUpdate = {
            cores: parseInt(document.getElementById('hwCores').value),
            sockets: parseInt(document.getElementById('hwSockets').value),
            memory: parseInt(document.getElementById('hwMemory').value),
            balloon: parseInt(document.getElementById('hwBalloon').value),
            cpu: document.getElementById('hwCpuType').value,
            ostype: document.getElementById('hwOsType').value,
            onboot: document.getElementById('hwOnboot').checked ? 1 : 0
        };

        const bootOrder = document.getElementById('hwBootOrder').value.trim();
        if (bootOrder) configUpdate.boot = bootOrder;

        // Network — preserve existing MAC address
        const netModel = document.getElementById('hwNetModel').value;
        const netBridge = document.getElementById('hwNetBridge').value;
        const netVlan = document.getElementById('hwNetVlan').value;
        const macPart = hwOriginalMac ? hwOriginalMac : '';
        let netStr = macPart ? `${netModel}=${macPart},bridge=${netBridge}` : `${netModel},bridge=${netBridge}`;
        if (netVlan) netStr += `,tag=${netVlan}`;
        // Preserve firewall and other extra params from original net0
        if (hwOriginalNet0) {
            const extraParams = hwOriginalNet0.split(',').filter(p => {
                const k = p.split('=')[0].trim().toLowerCase();
                return !['bridge','tag'].includes(k) && !p.match(/^\w+=[0-9A-Fa-f:]{17}$/) && !p.match(/^\w+$/) && k !== 'model';
            });
            // Add params like firewall=1, queues=, rate=, etc.
            extraParams.forEach(p => {
                const k = p.split('=')[0].trim().toLowerCase();
                if (k && !netStr.includes(k + '=')) netStr += ',' + p;
            });
        }
        configUpdate.net0 = netStr;

        // Apply config
        await apiPost('update_vm_config', {
            server_id: serverId, node, vmid, type, config: configUpdate
        });

        // Disk resize if specified
        const diskResize = document.getElementById('hwDiskResize').value.trim();
        if (diskResize) {
            const diskName = document.getElementById('hwDiskName').value;
            await apiPost('resize_disk', {
                server_id: serverId, node, vmid, type,
                disk: diskName,
                size: diskResize
            });
        }

        showToast('Hardware atualizado com sucesso!');
        fecharModal('modalEditHW');
        abrirDetalhesVM(serverId, node, vmid, type);

    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
    }
}

// ==========================================
//  CRIAR VM
// ==========================================
async function abrirModalCriarVM() {
    if (!currentServerId) { showToast('Selecione um servidor primeiro', 'error'); return; }

    // Populate node select
    const sel = document.getElementById('createVmNode');
    sel.innerHTML = '';
    nodesCache.forEach(n => { sel.innerHTML += `<option value="${n.node}">${n.node}</option>`; });

    // Get next VMID
    try {
        const res = await apiGet('next_vmid', {server_id: currentServerId});
        document.getElementById('createVmId').value = res.data?.vmid || 100;
    } catch(e) {
        document.getElementById('createVmId').value = 100;
    }

    document.getElementById('createVmName').value = '';
    document.getElementById('createVmCores').value = 2;
    document.getElementById('createVmSockets').value = 1;
    document.getElementById('createVmMemory').value = 2048;
    document.getElementById('createVmDiskSize').value = 32;
    document.getElementById('createVmStorage').value = 'local-lvm';
    document.getElementById('createVmBridge').value = 'vmbr0';
    document.getElementById('createVmVlan').value = '';
    document.getElementById('createVmStart').checked = false;

    document.getElementById('modalCriarVM').style.display = 'flex';
    carregarISOsParaCriar();
}

async function carregarISOsParaCriar() {
    const node = document.getElementById('createVmNode').value;
    const sel = document.getElementById('createVmIso');
    sel.innerHTML = '<option value="none">Nenhum (PXE/Cloud-init)</option>';
    if (!node) return;

    try {
        const res = await apiPost('list_isos', {server_id: currentServerId, node, storage: 'local'});
        (res.data || []).forEach(iso => {
            sel.innerHTML += `<option value="${iso.volid}">${iso.volid.split('/').pop()}</option>`;
        });
    } catch(e) {
        // Try iso storage name variants
        try {
            const res2 = await apiGet('storage', {server_id: currentServerId, node});
            const isoStores = (res2.data || []).filter(s => (s.content || '').includes('iso'));
            for (const store of isoStores) {
                const res3 = await apiPost('list_isos', {server_id: currentServerId, node, storage: store.storage});
                (res3.data || []).forEach(iso => {
                    sel.innerHTML += `<option value="${iso.volid}">${iso.volid.split('/').pop()}</option>`;
                });
            }
        } catch(e2) {}
    }
}

async function criarVM() {
    const node = document.getElementById('createVmNode').value;
    const vmid = parseInt(document.getElementById('createVmId').value);
    const name = document.getElementById('createVmName').value.trim();

    if (!node || !vmid || !name) {
        showToast('Node, VMID e Nome são obrigatórios', 'error');
        return;
    }

    const btn = document.getElementById('btnCriarVM');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';

    const cores = parseInt(document.getElementById('createVmCores').value);
    const sockets = parseInt(document.getElementById('createVmSockets').value);
    const memory = parseInt(document.getElementById('createVmMemory').value);
    const storage = document.getElementById('createVmStorage').value;
    const diskSize = parseInt(document.getElementById('createVmDiskSize').value);
    const diskIface = document.getElementById('createVmDiskIface').value;
    const iso = document.getElementById('createVmIso').value;
    const ostype = document.getElementById('createVmOs').value;
    const netModel = document.getElementById('createVmNetModel').value;
    const bridge = document.getElementById('createVmBridge').value;
    const vlan = document.getElementById('createVmVlan').value;
    const startAfter = document.getElementById('createVmStart').checked;

    let netStr = `${netModel},bridge=${bridge}`;
    if (vlan) netStr += `,tag=${vlan}`;

    const config = {
        name, cores, sockets, memory, ostype,
        net0: netStr,
        scsihw: 'virtio-scsi-pci'
    };
    config[diskIface] = `${storage}:${diskSize}`;

    if (iso && iso !== 'none') {
        config.ide2 = `${iso},media=cdrom`;
        config.boot = `order=${diskIface};ide2;net0`;
    } else {
        config.boot = `order=${diskIface};net0`;
    }

    if (startAfter) config.start = 1;

    try {
        await apiPost('create_vm', {
            server_id: currentServerId, node, vmid, config
        });
        showToast(`VM ${vmid} (${name}) criada com sucesso!`);
        fecharModal('modalCriarVM');
        carregarOverview(true);
        carregarVMs();
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Criar VM';
    }
}

// ==========================================
//  CRIAR CONTAINER
// ==========================================
async function abrirModalCriarCT() {
    if (!currentServerId) { showToast('Selecione um servidor primeiro', 'error'); return; }

    const sel = document.getElementById('createCtNode');
    sel.innerHTML = '';
    nodesCache.forEach(n => { sel.innerHTML += `<option value="${n.node}">${n.node}</option>`; });

    try {
        const res = await apiGet('next_vmid', {server_id: currentServerId});
        document.getElementById('createCtId').value = res.data?.vmid || 100;
    } catch(e) {
        document.getElementById('createCtId').value = 100;
    }

    document.getElementById('createCtHostname').value = '';
    document.getElementById('createCtPassword').value = '';
    document.getElementById('createCtCores').value = 2;
    document.getElementById('createCtMemory').value = 1024;
    document.getElementById('createCtSwap').value = 512;
    document.getElementById('createCtStorage').value = 'local-lvm';
    document.getElementById('createCtDisk').value = 8;
    document.getElementById('createCtIp').value = 'dhcp';
    document.getElementById('createCtBridge').value = 'vmbr0';
    document.getElementById('createCtGw').value = '';
    document.getElementById('createCtStart').checked = false;

    document.getElementById('modalCriarCT').style.display = 'flex';
    carregarTemplatesParaCriar();
}

async function carregarTemplatesParaCriar() {
    const node = document.getElementById('createCtNode').value;
    const sel = document.getElementById('createCtTemplate');
    sel.innerHTML = '<option value="">Carregando...</option>';
    if (!node) return;

    try {
        const res = await apiGet('templates', {server_id: currentServerId, node});
        const templates = res.data || [];
        sel.innerHTML = '<option value="">— Selecione —</option>';
        templates.forEach(t => {
            const label = (t.volid || t.template || '').split('/').pop();
            sel.innerHTML += `<option value="${t.volid || t.template}">${label}</option>`;
        });
        if (!templates.length) {
            sel.innerHTML = '<option value="">Nenhum template encontrado</option>';
        }
    } catch(e) {
        sel.innerHTML = '<option value="">Erro ao carregar</option>';
    }
}

async function criarCT() {
    const node = document.getElementById('createCtNode').value;
    const vmid = parseInt(document.getElementById('createCtId').value);
    const hostname = document.getElementById('createCtHostname').value.trim();
    const password = document.getElementById('createCtPassword').value;
    const template = document.getElementById('createCtTemplate').value;

    if (!node || !vmid || !hostname || !password || !template) {
        showToast('Preencha todos os campos obrigatórios', 'error');
        return;
    }

    const btn = document.getElementById('btnCriarCT');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';

    const storage = document.getElementById('createCtStorage').value;
    const diskGB = parseInt(document.getElementById('createCtDisk').value);
    const ip = document.getElementById('createCtIp').value.trim();
    const bridge = document.getElementById('createCtBridge').value.trim();
    const gw = document.getElementById('createCtGw').value.trim();

    let netStr = `name=eth0,bridge=${bridge}`;
    if (ip === 'dhcp') {
        netStr += ',ip=dhcp';
    } else {
        netStr += `,ip=${ip}`;
        if (gw) netStr += `,gw=${gw}`;
    }

    const config = {
        hostname, password,
        ostemplate: template,
        cores: parseInt(document.getElementById('createCtCores').value),
        memory: parseInt(document.getElementById('createCtMemory').value),
        swap: parseInt(document.getElementById('createCtSwap').value),
        rootfs: `${storage}:${diskGB}`,
        net0: netStr,
        start: document.getElementById('createCtStart').checked ? 1 : 0,
        unprivileged: 1
    };

    try {
        await apiPost('create_ct', {
            server_id: currentServerId, node, vmid, config
        });
        showToast(`Container ${vmid} (${hostname}) criado com sucesso!`);
        fecharModal('modalCriarCT');
        carregarOverview(true);
        carregarContainers();
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Criar Container';
    }
}

// ==========================================
//  EXCLUIR VM/CT
// ==========================================
async function excluirVM(serverId, node, vmid, vmNome, type) {
    const typeLabel = type === 'qemu' ? 'VM' : 'Container';
    if (!confirm(`⚠️ EXCLUIR ${typeLabel} ${vmid} (${vmNome})?\n\nEsta ação é IRREVERSÍVEL!\nTodos os discos e dados serão perdidos.`)) return;
    if (!confirm(`Tem CERTEZA que deseja excluir permanentemente a ${typeLabel} ${vmid}?`)) return;

    try {
        showToast(`Excluindo ${typeLabel} ${vmid}...`, 'info');
        await apiPost('delete_vm', {server_id: serverId, node, vmid, type});
        showToast(`${typeLabel} ${vmid} excluída com sucesso!`);
        fecharModal('modalVMDetail');
        setTimeout(() => {
            carregarOverview(true);
            carregarVMs(true);
            carregarContainers(true);
        }, 2000);
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  CLONAR VM/CT
// ==========================================
async function abrirClonar(serverId, node, vmid, type, vmName) {
    document.getElementById('cloneServerId').value = serverId;
    document.getElementById('cloneNode').value = node;
    document.getElementById('cloneVmid').value = vmid;
    document.getElementById('cloneType').value = type;
    document.getElementById('cloneSourceLabel').textContent =
        `Clonar ${type === 'qemu' ? 'VM' : 'Container'} ${vmid} (${vmName}) do node ${node}`;
    document.getElementById('cloneNewName').value = vmName ? vmName + '-clone' : '';
    document.getElementById('cloneFull').checked = true;

    try {
        const res = await apiGet('next_vmid', {server_id: serverId});
        document.getElementById('cloneNewId').value = res.data?.vmid || vmid + 1;
    } catch(e) {
        document.getElementById('cloneNewId').value = vmid + 1;
    }

    document.getElementById('modalClone').style.display = 'flex';
}

async function executarClone() {
    const serverId = parseInt(document.getElementById('cloneServerId').value);
    const node = document.getElementById('cloneNode').value;
    const vmid = parseInt(document.getElementById('cloneVmid').value);
    const type = document.getElementById('cloneType').value;
    const newid = parseInt(document.getElementById('cloneNewId').value);
    const newName = document.getElementById('cloneNewName').value.trim();
    const full = document.getElementById('cloneFull').checked;

    if (!newid) { showToast('Informe o novo VMID', 'error'); return; }

    const btn = document.getElementById('btnClone');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clonando...';

    try {
        await apiPost('clone_vm', {
            server_id: serverId, node, vmid, type,
            newid, name: newName, full
        });
        showToast(`Clone iniciado! Novo VMID: ${newid}`);
        fecharModal('modalClone');
        setTimeout(() => { carregarOverview(true); carregarVMs(true); carregarContainers(true); }, 3000);
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-clone"></i> Clonar';
    }
}

// ==========================================
//  MIGRAR VM/CT
// ==========================================
async function abrirMigrar(serverId, node, vmid, type, vmName) {
    document.getElementById('migrateServerId').value = serverId;
    document.getElementById('migrateNode').value = node;
    document.getElementById('migrateVmid').value = vmid;
    document.getElementById('migrateType').value = type;
    document.getElementById('migrateSourceLabel').textContent =
        `Migrar ${type === 'qemu' ? 'VM' : 'Container'} ${vmid} (${vmName}) de ${node}`;
    document.getElementById('migrateOnline').checked = true;

    const sel = document.getElementById('migrateTarget');
    sel.innerHTML = '';
    nodesCache.filter(n => n.node !== node).forEach(n => {
        sel.innerHTML += `<option value="${n.node}">${n.node} (${n.status})</option>`;
    });

    document.getElementById('modalMigrate').style.display = 'flex';
}

async function executarMigrate() {
    const serverId = parseInt(document.getElementById('migrateServerId').value);
    const node = document.getElementById('migrateNode').value;
    const vmid = parseInt(document.getElementById('migrateVmid').value);
    const type = document.getElementById('migrateType').value;
    const targetNode = document.getElementById('migrateTarget').value;
    const online = document.getElementById('migrateOnline').checked;

    if (!targetNode) { showToast('Selecione o node destino', 'error'); return; }

    const btn = document.getElementById('btnMigrate');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Migrando...';

    try {
        await apiPost('migrate_vm', {
            server_id: serverId, node, vmid, type,
            target_node: targetNode, online
        });
        showToast(`Migração iniciada para ${targetNode}!`);
        fecharModal('modalMigrate');
        fecharModal('modalVMDetail');
        setTimeout(() => { carregarOverview(true); carregarNodes(); carregarVMs(true); }, 5000);
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Migrar';
    }
}

// ==========================================
//  CONSOLE (noVNC / Proxmox built-in)
// ==========================================
async function abrirConsole(serverId, node, vmid, type) {
    document.getElementById('consoleTitle').innerHTML =
        `<i class="fas fa-terminal"></i> Console — ${type === 'qemu' ? 'VM' : 'CT'} ${vmid} (${node})`;
    document.getElementById('consoleFrame').src = 'about:blank';
    document.getElementById('modalConsole').style.display = 'flex';

    try {
        const res = await apiPost('console', {server_id: serverId, node, vmid, type});
        const url = res.data?.url;
        if (!url) throw new Error('URL do console não retornada');
        document.getElementById('consoleFrame').src = url;
    } catch(e) {
        showToast('Erro ao abrir console: ' + e.message, 'error');
        document.getElementById('consoleFrame').srcdoc =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;background:#1a1a1a;color:#ff6b6b;font-family:sans-serif;padding:20px;text-align:center">' +
            '<div><i class="fas fa-exclamation-triangle" style="font-size:48px;margin-bottom:16px;display:block"></i>' +
            '<h3>Erro ao conectar ao console</h3>' +
            '<p style="color:#999;margin-top:8px">' + e.message + '</p>' +
            '<p style="color:#666;margin-top:12px;font-size:13px">Dica: Abra o console diretamente pelo Proxmox Web UI se o problema persistir.</p>' +
            '</div></div>';
    }
}

function abrirConsoleFullscreen() {
    const iframe = document.getElementById('consoleFrame');
    if (iframe.src) {
        window.open(iframe.src, '_blank');
    }
}

function fecharConsole() {
    document.getElementById('consoleFrame').src = 'about:blank';
    fecharModal('modalConsole');
}

// ==========================================
//  SNAPSHOTS
// ==========================================
function abrirModalSnapshot(serverId, node, vmid, type) {
    document.getElementById('snapServerId').value = serverId;
    document.getElementById('snapNode').value = node;
    document.getElementById('snapVmid').value = vmid;
    document.getElementById('snapType').value = type;
    document.getElementById('snapName').value = 'snap-' + new Date().toISOString().slice(0,10);
    document.getElementById('snapDesc').value = '';
    document.getElementById('modalSnapshot').style.display = 'flex';
}

async function criarSnapshot() {
    const data = {
        server_id: parseInt(document.getElementById('snapServerId').value),
        node: document.getElementById('snapNode').value,
        vmid: parseInt(document.getElementById('snapVmid').value),
        type: document.getElementById('snapType').value,
        snapname: document.getElementById('snapName').value.trim(),
        description: document.getElementById('snapDesc').value.trim()
    };

    if (!data.snapname) { showToast('Nome do snapshot obrigatório', 'error'); return; }

    try {
        await apiPost('criar_snapshot', data);
        showToast('Snapshot criado com sucesso!');
        fecharModal('modalSnapshot');
        // Recarregar detalhes
        abrirDetalhesVM(data.server_id, data.node, data.vmid, data.type);
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    }
}

async function rollbackSnap(serverId, node, vmid, snapname, type) {
    if (!confirm(`⚠️ Rollback para snapshot "${snapname}"?\nA VM voltará ao estado do snapshot.`)) return;
    try {
        await apiPost('rollback_snapshot', {server_id: serverId, node, vmid, snapname, type});
        showToast('Rollback executado!');
        abrirDetalhesVM(serverId, node, vmid, type);
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    }
}

async function excluirSnap(serverId, node, vmid, snapname, type) {
    if (!confirm(`Excluir snapshot "${snapname}"?`)) return;
    try {
        await apiPost('excluir_snapshot', {server_id: serverId, node, vmid, snapname, type});
        showToast('Snapshot excluído!');
        abrirDetalhesVM(serverId, node, vmid, type);
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  BACKUP
// ==========================================
async function backupVM(serverId, node, vmid, type) {
    if (!confirm(`Iniciar backup da ${type === 'qemu' ? 'VM' : 'Container'} ${vmid}?`)) return;
    try {
        await apiPost('backup_vm', {server_id: serverId, node, vmid, type});
        showToast('Backup iniciado! Acompanhe no Proxmox.', 'success');
    } catch(e) {
        showToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  LOGS
// ==========================================
async function carregarLogs() {
    if (!currentServerId) return;
    const tbody = document.getElementById('logsTbody');
    try {
        const res = await apiGet('logs', {server_id: currentServerId});
        const logs = res.data || [];

        if (!logs.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-muted" style="text-align:center;padding:40px">Nenhum log encontrado</td></tr>';
            return;
        }

        tbody.innerHTML = logs.map(l => {
            const date = new Date(l.criado_em);
            const dateStr = date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
            const resultClass = l.resultado === 'sucesso' ? 'pve-badge-success' : 'pve-badge-danger';

            return `<tr>
                <td style="font-size:12px;white-space:nowrap">${dateStr}</td>
                <td>${l.usuario_nome || '—'}</td>
                <td><span class="pve-badge pve-badge-info">${l.acao}</span></td>
                <td>${l.vmid ? `<strong>${l.vmid}</strong> ${l.vm_nome || ''}` : '—'}</td>
                <td>${l.node || '—'}</td>
                <td style="font-size:12px;max-width:250px;overflow:hidden;text-overflow:ellipsis">${l.detalhes || '—'}</td>
                <td><span class="pve-badge ${resultClass}">${l.resultado}</span></td>
            </tr>`;
        }).join('');
    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-muted" style="text-align:center;color:#EF4444">${e.message}</td></tr>`;
    }
}

// ==========================================
//  TABS
// ==========================================
function switchTab(tabName) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`.ad-tab[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');

    // Carregar dados da aba (sempre atualiza para tempo real)
    switch(tabName) {
        case 'vms': carregarVMs(); break;
        case 'containers': carregarContainers(); break;
        case 'storage': carregarStorage(); break;
        case 'logs': carregarLogs(); break;
        case 'overview': carregarNodes(); break;
    }
}

// ==========================================
//  REFRESH — TEMPO REAL
// ==========================================
async function refreshAtivo() {
    if (!currentServerId) return;
    try {
        await carregarOverview(true);
        const activeTab = document.querySelector('.ad-tab.active')?.dataset.tab;
        switch(activeTab) {
            case 'vms': await carregarVMs(true); break;
            case 'containers': await carregarContainers(true); break;
            case 'storage': await carregarStorage(true); break;
            case 'overview': await carregarNodes(); break;
        }
    } catch(e) {
        console.warn('Auto-refresh erro:', e.message);
    }
}

async function refreshAll() {
    if (!currentServerId) return;
    const btn = document.getElementById('btnRefresh');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';

    try {
        await carregarOverview();
        await carregarNodes();
        const activeTab = document.querySelector('.ad-tab.active')?.dataset.tab;
        if (activeTab === 'vms') await carregarVMs();
        else if (activeTab === 'containers') await carregarContainers();
        else if (activeTab === 'storage') await carregarStorage();
        else if (activeTab === 'logs') await carregarLogs();
        showToast('Dados atualizados!');
    } catch(e) {
        showToast('Erro ao atualizar: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Atualizar';
    }
}
</script>
