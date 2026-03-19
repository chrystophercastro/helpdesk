<?php
/**
 * View: MikroTik RouterOS
 * Gerenciamento de dispositivos MikroTik — NAT, Firewall, DHCP, Queues, Monitoramento
 */
?>

<!-- HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-network-wired" style="margin-right:8px;color:#D6336C"></i> MikroTik RouterOS</h1>
        <p class="page-subtitle">Gerenciamento de Rede — NAT, Firewall, DHCP, Queues e Monitoramento</p>
    </div>
    <div class="page-actions">
        <select id="mkDeviceSelect" class="form-control" style="width:auto;display:inline-block;margin-right:8px" onchange="mkTrocarDevice()">
            <option value="">Selecione um dispositivo...</option>
        </select>
        <button class="btn btn-sm" onclick="mkEditarDeviceAtivo()" id="btnMkEditDevice" style="display:none;margin-right:4px" title="Editar dispositivo">
            <i class="fas fa-cog"></i>
        </button>
        <button class="btn btn-secondary" onclick="mkRefresh()" id="btnMkRefresh">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
        <button class="btn btn-primary" onclick="mkAbrirModalDevice()">
            <i class="fas fa-plus"></i> Novo Dispositivo
        </button>
    </div>
</div>

<!-- EMPTY STATE -->
<div id="mkEmpty" class="pve-empty-state">
    <i class="fas fa-network-wired fa-3x" style="color:#D6336C;margin-bottom:16px"></i>
    <h3>Selecione ou cadastre um dispositivo MikroTik</h3>
    <p class="text-muted">Conecte-se à API RouterOS para gerenciar NAT, firewall, DHCP e mais.</p>
    <button class="btn btn-primary" onclick="mkAbrirModalDevice()" style="margin-top:12px">
        <i class="fas fa-plus"></i> Cadastrar Dispositivo
    </button>
</div>

<!-- CONTEÚDO PRINCIPAL (oculto até selecionar device) -->
<div id="mkContent" style="display:none">
    <!-- TABS -->
    <div class="ad-tabs">
        <button class="ad-tab active" data-tab="mk-overview" onclick="mkSwitchTab('mk-overview')">
            <i class="fas fa-tachometer-alt"></i> Overview
        </button>
        <button class="ad-tab" data-tab="mk-interfaces" onclick="mkSwitchTab('mk-interfaces')">
            <i class="fas fa-ethernet"></i> Interfaces
        </button>
        <button class="ad-tab" data-tab="mk-nat" onclick="mkSwitchTab('mk-nat')">
            <i class="fas fa-random"></i> NAT
        </button>
        <button class="ad-tab" data-tab="mk-firewall" onclick="mkSwitchTab('mk-firewall')">
            <i class="fas fa-shield-alt"></i> Firewall
        </button>
        <button class="ad-tab" data-tab="mk-dhcp" onclick="mkSwitchTab('mk-dhcp')">
            <i class="fas fa-address-book"></i> DHCP
        </button>
        <button class="ad-tab" data-tab="mk-queues" onclick="mkSwitchTab('mk-queues')">
            <i class="fas fa-tachometer-alt"></i> Queues
        </button>
        <button class="ad-tab" data-tab="mk-routes" onclick="mkSwitchTab('mk-routes')">
            <i class="fas fa-route"></i> Rotas
        </button>
        <button class="ad-tab" data-tab="mk-arp" onclick="mkSwitchTab('mk-arp')">
            <i class="fas fa-table"></i> ARP
        </button>
        <button class="ad-tab" data-tab="mk-logs" onclick="mkSwitchTab('mk-logs')">
            <i class="fas fa-file-alt"></i> Logs
        </button>
        <button class="ad-tab" data-tab="mk-ia" onclick="mkSwitchTab('mk-ia')">
            <i class="fas fa-robot" style="color:#8B5CF6"></i> IA Assistente
        </button>
    </div>

    <!-- =============== TAB: OVERVIEW =============== -->
    <div class="ad-tab-content active" id="tab-mk-overview">
        <div id="mkOverviewContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: INTERFACES =============== -->
    <div class="ad-tab-content" id="tab-mk-interfaces">
        <div class="mk-tab-header">
            <h3><i class="fas fa-ethernet"></i> Interfaces de Rede</h3>
        </div>
        <div id="mkInterfacesContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: NAT =============== -->
    <div class="ad-tab-content" id="tab-mk-nat">
        <div class="mk-tab-header">
            <h3><i class="fas fa-random"></i> Regras NAT</h3>
            <button class="btn btn-primary btn-sm" onclick="mkAbrirModalNat()">
                <i class="fas fa-plus"></i> Nova Regra
            </button>
        </div>
        <div id="mkNatContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: FIREWALL =============== -->
    <div class="ad-tab-content" id="tab-mk-firewall">
        <div class="mk-tab-header">
            <h3><i class="fas fa-shield-alt"></i> Firewall Filter</h3>
            <button class="btn btn-primary btn-sm" onclick="mkAbrirModalFirewall()">
                <i class="fas fa-plus"></i> Nova Regra
            </button>
        </div>
        <div id="mkFirewallContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: DHCP =============== -->
    <div class="ad-tab-content" id="tab-mk-dhcp">
        <div class="mk-tab-header">
            <h3><i class="fas fa-address-book"></i> DHCP Leases</h3>
        </div>
        <div id="mkDhcpContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: QUEUES =============== -->
    <div class="ad-tab-content" id="tab-mk-queues">
        <div class="mk-tab-header">
            <h3><i class="fas fa-tachometer-alt"></i> Simple Queues (Controle de Banda)</h3>
            <button class="btn btn-primary btn-sm" onclick="mkAbrirModalQueue()">
                <i class="fas fa-plus"></i> Nova Queue
            </button>
        </div>
        <div id="mkQueuesContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: ROUTES =============== -->
    <div class="ad-tab-content" id="tab-mk-routes">
        <div class="mk-tab-header">
            <h3><i class="fas fa-route"></i> Tabela de Rotas</h3>
            <button class="btn btn-primary btn-sm" onclick="mkAbrirModalRoute()">
                <i class="fas fa-plus"></i> Nova Rota
            </button>
        </div>
        <div id="mkRoutesContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: ARP =============== -->
    <div class="ad-tab-content" id="tab-mk-arp">
        <div class="mk-tab-header">
            <h3><i class="fas fa-table"></i> Tabela ARP</h3>
        </div>
        <div id="mkArpContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: LOGS =============== -->
    <div class="ad-tab-content" id="tab-mk-logs">
        <div class="mk-tab-header">
            <h3><i class="fas fa-file-alt"></i> Logs do Sistema</h3>
            <button class="btn btn-sm" onclick="mkCarregarLogs()">
                <i class="fas fa-sync-alt"></i> Recarregar
            </button>
        </div>
        <div id="mkLogsContent">
            <div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>
        </div>
    </div>

    <!-- =============== TAB: IA ASSISTENTE =============== -->
    <div class="ad-tab-content" id="tab-mk-ia">
        <div class="mk-ia-container">
            <!-- Chat Messages -->
            <div class="mk-ia-messages" id="mkIaMessages">
                <div class="mk-ia-welcome">
                    <div class="mk-ia-welcome-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>Assistente IA MikroTik</h3>
                    <p class="text-muted">Analiso a configuração do seu roteador e sugiro melhorias de segurança, performance e boas práticas.</p>
                    <div class="mk-ia-suggestions">
                        <button class="mk-ia-suggestion" onclick="mkIaEnviarSugestao('Analise meu firewall e me diga se há vulnerabilidades ou melhorias')">
                            <i class="fas fa-shield-alt"></i> Analisar segurança do Firewall
                        </button>
                        <button class="mk-ia-suggestion" onclick="mkIaEnviarSugestao('Analise minhas regras NAT e sugira otimizações')">
                            <i class="fas fa-random"></i> Otimizar regras NAT
                        </button>
                        <button class="mk-ia-suggestion" onclick="mkIaEnviarSugestao('Faça uma análise geral da saúde e performance do roteador')">
                            <i class="fas fa-heartbeat"></i> Saúde geral do router
                        </button>
                        <button class="mk-ia-suggestion" onclick="mkIaEnviarSugestao('Sugira configurações de QoS e filas para otimizar a banda')">
                            <i class="fas fa-tachometer-alt"></i> Sugestões de QoS
                        </button>
                        <button class="mk-ia-suggestion" onclick="mkIaEnviarSugestao('Analise as interfaces de rede e conexões. O que posso melhorar?')">
                            <i class="fas fa-ethernet"></i> Analisar interfaces
                        </button>
                        <button class="mk-ia-suggestion" onclick="mkIaEnviarSugestao('Me dê um checklist de segurança completo para MikroTik RouterOS')">
                            <i class="fas fa-list-alt"></i> Checklist de segurança
                        </button>
                    </div>
                </div>
            </div>
            <!-- Input Area -->
            <div class="mk-ia-input-area">
                <div class="mk-ia-input-wrap">
                    <textarea id="mkIaInput" placeholder="Pergunte sobre a configuração do seu MikroTik..." rows="1" onkeydown="mkIaKeydown(event)" oninput="mkIaAutoResize(this)"></textarea>
                    <button class="mk-ia-send-btn" onclick="mkIaEnviar()" id="btnMkIaSend" title="Enviar">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="mk-ia-input-footer">
                    <span class="text-muted" style="font-size:11px"><i class="fas fa-info-circle"></i> A IA tem acesso aos dados do dispositivo selecionado para análise em tempo real</span>
                    <button class="btn btn-sm" onclick="mkIaLimpar()" style="font-size:11px" title="Limpar conversa">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====================== MODAIS ====================== -->

<!-- Modal: Dispositivo -->
<div class="modal-overlay" id="modalMkDevice" style="display:none">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h2 id="mkDeviceModalTitle"><i class="fas fa-network-wired"></i> Novo Dispositivo MikroTik</h2>
            <button class="modal-close" onclick="mkFecharModal('modalMkDevice')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mkDeviceId" value="">
            <div class="form-group">
                <label class="form-label">Nome *</label>
                <input type="text" id="mkDeviceNome" class="form-control" placeholder="Ex: Router Principal">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <input type="text" id="mkDeviceDesc" class="form-control" placeholder="Descrição do dispositivo">
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label class="form-label">Host/IP *</label>
                    <input type="text" id="mkDeviceHost" class="form-control" placeholder="192.168.1.1">
                </div>
                <div class="form-group" style="flex:1">
                    <label class="form-label">Porta API</label>
                    <input type="number" id="mkDevicePorta" class="form-control" value="8728">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Usuário *</label>
                    <input type="text" id="mkDeviceUser" class="form-control" placeholder="admin">
                </div>
                <div class="form-group">
                    <label class="form-label">Senha *</label>
                    <input type="password" id="mkDevicePass" class="form-control" placeholder="Senha">
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="mkDeviceSSL"> Usar SSL (porta 8729)
                </label>
            </div>
            <div class="form-actions" style="margin-top:16px;display:flex;gap:8px">
                <button class="btn" onclick="mkTestarConexao()" id="btnMkTestar">
                    <i class="fas fa-plug"></i> Testar Conexão
                </button>
                <button class="btn btn-primary" onclick="mkSalvarDevice()" id="btnMkSalvar">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button class="btn btn-danger" onclick="mkExcluirDevice()" id="btnMkExcluir" style="display:none">
                    <i class="fas fa-trash"></i> Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: NAT Rule -->
<div class="modal-overlay" id="modalMkNat" style="display:none">
    <div class="modal" style="max-width:650px">
        <div class="modal-header">
            <h2 id="mkNatModalTitle"><i class="fas fa-random"></i> Nova Regra NAT</h2>
            <button class="modal-close" onclick="mkFecharModal('modalMkNat')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mkNatRuleId" value="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Chain *</label>
                    <select id="mkNatChain" class="form-control">
                        <option value="dstnat">dstnat</option>
                        <option value="srcnat">srcnat</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Action *</label>
                    <select id="mkNatAction" class="form-control">
                        <option value="dst-nat">dst-nat</option>
                        <option value="src-nat">src-nat</option>
                        <option value="masquerade">masquerade</option>
                        <option value="redirect">redirect</option>
                        <option value="netmap">netmap</option>
                        <option value="accept">accept</option>
                        <option value="passthrough">passthrough</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Protocolo</label>
                    <select id="mkNatProtocol" class="form-control">
                        <option value="">Qualquer</option>
                        <option value="tcp">TCP</option>
                        <option value="udp">UDP</option>
                        <option value="icmp">ICMP</option>
                        <option value="6">TCP (6)</option>
                        <option value="17">UDP (17)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">In-Interface</label>
                    <input type="text" id="mkNatInIf" class="form-control" placeholder="ether1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Src Address</label>
                    <input type="text" id="mkNatSrcAddr" class="form-control" placeholder="0.0.0.0/0">
                </div>
                <div class="form-group">
                    <label class="form-label">Dst Address</label>
                    <input type="text" id="mkNatDstAddr" class="form-control" placeholder="0.0.0.0/0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Src Port</label>
                    <input type="text" id="mkNatSrcPort" class="form-control" placeholder="Ex: 80">
                </div>
                <div class="form-group">
                    <label class="form-label">Dst Port</label>
                    <input type="text" id="mkNatDstPort" class="form-control" placeholder="Ex: 8080">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">To Addresses</label>
                    <input type="text" id="mkNatToAddr" class="form-control" placeholder="192.168.1.100">
                </div>
                <div class="form-group">
                    <label class="form-label">To Ports</label>
                    <input type="text" id="mkNatToPort" class="form-control" placeholder="80">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Comentário</label>
                <input type="text" id="mkNatComment" class="form-control" placeholder="Descrição da regra">
            </div>
            <div class="form-actions" style="margin-top:16px;display:flex;gap:8px">
                <button class="btn btn-primary" onclick="mkSalvarNat()" id="btnMkSalvarNat">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button class="btn" onclick="mkFecharModal('modalMkNat')">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Firewall Rule -->
<div class="modal-overlay" id="modalMkFirewall" style="display:none">
    <div class="modal" style="max-width:650px">
        <div class="modal-header">
            <h2 id="mkFwModalTitle"><i class="fas fa-shield-alt"></i> Nova Regra Firewall</h2>
            <button class="modal-close" onclick="mkFecharModal('modalMkFirewall')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mkFwRuleId" value="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Chain *</label>
                    <select id="mkFwChain" class="form-control">
                        <option value="input">input</option>
                        <option value="forward">forward</option>
                        <option value="output">output</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Action *</label>
                    <select id="mkFwAction" class="form-control">
                        <option value="accept">accept</option>
                        <option value="drop">drop</option>
                        <option value="reject">reject</option>
                        <option value="log">log</option>
                        <option value="passthrough">passthrough</option>
                        <option value="jump">jump</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Protocolo</label>
                    <select id="mkFwProtocol" class="form-control">
                        <option value="">Qualquer</option>
                        <option value="tcp">TCP</option>
                        <option value="udp">UDP</option>
                        <option value="icmp">ICMP</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Connection State</label>
                    <input type="text" id="mkFwConnState" class="form-control" placeholder="established,related">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Src Address</label>
                    <input type="text" id="mkFwSrcAddr" class="form-control" placeholder="0.0.0.0/0">
                </div>
                <div class="form-group">
                    <label class="form-label">Dst Address</label>
                    <input type="text" id="mkFwDstAddr" class="form-control" placeholder="0.0.0.0/0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Src Port</label>
                    <input type="text" id="mkFwSrcPort" class="form-control" placeholder="Ex: 22">
                </div>
                <div class="form-group">
                    <label class="form-label">Dst Port</label>
                    <input type="text" id="mkFwDstPort" class="form-control" placeholder="Ex: 80,443">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">In-Interface</label>
                    <input type="text" id="mkFwInIf" class="form-control" placeholder="ether1">
                </div>
                <div class="form-group">
                    <label class="form-label">Out-Interface</label>
                    <input type="text" id="mkFwOutIf" class="form-control" placeholder="ether2">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Comentário</label>
                <input type="text" id="mkFwComment" class="form-control" placeholder="Descrição da regra">
            </div>
            <div class="form-actions" style="margin-top:16px;display:flex;gap:8px">
                <button class="btn btn-primary" onclick="mkSalvarFirewall()" id="btnMkSalvarFw">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button class="btn" onclick="mkFecharModal('modalMkFirewall')">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Queue -->
<div class="modal-overlay" id="modalMkQueue" style="display:none">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h2 id="mkQueueModalTitle"><i class="fas fa-tachometer-alt"></i> Nova Queue</h2>
            <button class="modal-close" onclick="mkFecharModal('modalMkQueue')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mkQueueId" value="">
            <div class="form-group">
                <label class="form-label">Nome *</label>
                <input type="text" id="mkQueueName" class="form-control" placeholder="Ex: limite-usuario-1">
            </div>
            <div class="form-group">
                <label class="form-label">Target * (IP/Rede)</label>
                <input type="text" id="mkQueueTarget" class="form-control" placeholder="192.168.1.100/32">
            </div>
            <div class="form-group">
                <label class="form-label">Max Limit (upload/download)</label>
                <input type="text" id="mkQueueMaxLimit" class="form-control" placeholder="10M/50M">
            </div>
            <div class="form-group">
                <label class="form-label">Burst Limit</label>
                <input type="text" id="mkQueueBurstLimit" class="form-control" placeholder="15M/60M">
            </div>
            <div class="form-group">
                <label class="form-label">Burst Threshold</label>
                <input type="text" id="mkQueueBurstThreshold" class="form-control" placeholder="8M/40M">
            </div>
            <div class="form-group">
                <label class="form-label">Comentário</label>
                <input type="text" id="mkQueueComment" class="form-control" placeholder="Descrição">
            </div>
            <div class="form-actions" style="margin-top:16px;display:flex;gap:8px">
                <button class="btn btn-primary" onclick="mkSalvarQueue()" id="btnMkSalvarQueue">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button class="btn" onclick="mkFecharModal('modalMkQueue')">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Rota -->
<div class="modal-overlay" id="modalMkRoute" style="display:none">
    <div class="modal" style="max-width:450px">
        <div class="modal-header">
            <h2><i class="fas fa-route"></i> Nova Rota</h2>
            <button class="modal-close" onclick="mkFecharModal('modalMkRoute')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Dst Address *</label>
                <input type="text" id="mkRouteDst" class="form-control" placeholder="0.0.0.0/0">
            </div>
            <div class="form-group">
                <label class="form-label">Gateway *</label>
                <input type="text" id="mkRouteGw" class="form-control" placeholder="192.168.1.1">
            </div>
            <div class="form-group">
                <label class="form-label">Distance</label>
                <input type="number" id="mkRouteDist" class="form-control" value="1">
            </div>
            <div class="form-group">
                <label class="form-label">Comentário</label>
                <input type="text" id="mkRouteComment" class="form-control">
            </div>
            <div class="form-actions" style="margin-top:16px;display:flex;gap:8px">
                <button class="btn btn-primary" onclick="mkSalvarRoute()">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button class="btn" onclick="mkFecharModal('modalMkRoute')">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- ====================== JAVASCRIPT ====================== -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
const MK_API = '<?= BASE_URL ?>/api/mikrotik.php';
let mkDeviceAtivo = null;
let mkDevices = [];
let mkCurrentTab = 'mk-overview';

// ==========================================
//  HELPERS
// ==========================================
async function mkGet(action, params = {}) {
    params.action = action;
    if (mkDeviceAtivo) params.device_id = mkDeviceAtivo;
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(`${MK_API}?${qs}`);
    if (!res.ok) {
        let msg = `HTTP ${res.status}`;
        try { const j = await res.json(); msg = j.error || msg; } catch(e) {}
        throw new Error(msg);
    }
    const text = await res.text();
    if (!text) throw new Error('Resposta vazia do servidor');
    let json;
    try { json = JSON.parse(text); } catch(e) { throw new Error('Resposta inválida do servidor'); }
    if (json.error) throw new Error(json.error);
    return json;
}

async function mkPost(action, data = {}) {
    data.action = action;
    if (mkDeviceAtivo && !data.device_id) data.device_id = mkDeviceAtivo;
    const res = await fetch(MK_API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if (!res.ok) {
        let msg = `HTTP ${res.status}`;
        try { const j = await res.json(); msg = j.error || msg; } catch(e) {}
        throw new Error(msg);
    }
    const text = await res.text();
    if (!text) throw new Error('Resposta vazia do servidor');
    let json;
    try { json = JSON.parse(text); } catch(e) { throw new Error('Resposta inválida do servidor'); }
    if (json.error) throw new Error(json.error);
    return json;
}

function mkToast(msg, type = 'success') {
    if (typeof showToast === 'function') showToast(msg, type);
    else alert(msg);
}

function mkFecharModal(id) { document.getElementById(id).style.display = 'none'; }
function mkAbrirModal(id) { document.getElementById(id).style.display = 'flex'; }

function mkEsc(text) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(text || ''));
    return d.innerHTML;
}

function mkFormatBytes(bytes) {
    bytes = parseFloat(bytes) || 0;
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' B';
}

function mkFormatBitrate(bps) {
    bps = parseFloat(bps) || 0;
    if (bps >= 1000000000) return (bps / 1000000000).toFixed(2) + ' Gbps';
    if (bps >= 1000000) return (bps / 1000000).toFixed(2) + ' Mbps';
    if (bps >= 1000) return (bps / 1000).toFixed(2) + ' Kbps';
    return bps + ' bps';
}

function mkBadge(text, type = 'info') {
    const colors = {
        success: '#10B981', danger: '#EF4444', warning: '#F59E0B',
        info: '#3B82F6', muted: '#94a3b8', purple: '#8B5CF6'
    };
    const c = colors[type] || colors.info;
    return `<span style="background:${c}15;color:${c};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600">${text}</span>`;
}

function mkDisabledBadge(disabled) {
    return disabled === 'true' ? mkBadge('Desabilitado', 'danger') : mkBadge('Ativo', 'success');
}

// ==========================================
//  TABS
// ==========================================
function mkSwitchTab(tabName) {
    mkCurrentTab = tabName;
    document.querySelectorAll('#mkContent .ad-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.tab === tabName);
    });
    document.querySelectorAll('#mkContent .ad-tab-content').forEach(c => {
        c.classList.toggle('active', c.id === 'tab-' + tabName);
    });
    // Carregar dados da tab se necessário
    if (mkDeviceAtivo) mkLoadTabData(tabName);
}

function mkLoadTabData(tab) {
    switch (tab) {
        case 'mk-overview': mkCarregarOverview(); break;
        case 'mk-interfaces': mkCarregarInterfaces(); break;
        case 'mk-nat': mkCarregarNat(); break;
        case 'mk-firewall': mkCarregarFirewall(); break;
        case 'mk-dhcp': mkCarregarDhcp(); break;
        case 'mk-queues': mkCarregarQueues(); break;
        case 'mk-routes': mkCarregarRoutes(); break;
        case 'mk-arp': mkCarregarArp(); break;
        case 'mk-logs': mkCarregarLogs(); break;
    }
}

// ==========================================
//  INIT & DEVICES
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    mkCarregarDevices();
});

async function mkCarregarDevices() {
    try {
        const res = await mkGet('listar_dispositivos');
        mkDevices = res.data || [];
        const sel = document.getElementById('mkDeviceSelect');
        sel.innerHTML = '<option value="">Selecione um dispositivo...</option>' +
            mkDevices.map(d => `<option value="${d.id}">${mkEsc(d.nome)} (${mkEsc(d.host)})</option>`).join('');
        if (mkDeviceAtivo) {
            sel.value = mkDeviceAtivo;
        }
    } catch(e) {
        console.error('Erro ao carregar devices:', e);
    }
}

function mkTrocarDevice() {
    const val = document.getElementById('mkDeviceSelect').value;
    const editBtn = document.getElementById('btnMkEditDevice');
    if (!val) {
        mkDeviceAtivo = null;
        document.getElementById('mkEmpty').style.display = 'block';
        document.getElementById('mkContent').style.display = 'none';
        editBtn.style.display = 'none';
        return;
    }
    mkDeviceAtivo = val;
    editBtn.style.display = 'inline-flex';
    document.getElementById('mkEmpty').style.display = 'none';
    document.getElementById('mkContent').style.display = 'block';
    mkLoadTabData(mkCurrentTab);
}

function mkEditarDeviceAtivo() {
    if (mkDeviceAtivo) mkAbrirModalDevice(mkDeviceAtivo);
}

function mkRefresh() {
    if (mkDeviceAtivo) {
        mkLoadTabData(mkCurrentTab);
    }
}

// ==========================================
//  DEVICE CRUD
// ==========================================
function mkAbrirModalDevice(id = null) {
    document.getElementById('mkDeviceId').value = '';
    document.getElementById('mkDeviceNome').value = '';
    document.getElementById('mkDeviceDesc').value = '';
    document.getElementById('mkDeviceHost').value = '';
    document.getElementById('mkDevicePorta').value = '8728';
    document.getElementById('mkDeviceUser').value = '';
    document.getElementById('mkDevicePass').value = '';
    document.getElementById('mkDeviceSSL').checked = false;
    document.getElementById('btnMkExcluir').style.display = 'none';
    document.getElementById('mkDeviceModalTitle').innerHTML = '<i class="fas fa-network-wired"></i> Novo Dispositivo MikroTik';

    if (id) {
        mkEditarDevice(id);
    }

    mkAbrirModal('modalMkDevice');
}

async function mkEditarDevice(id) {
    try {
        const res = await mkGet('get_dispositivo', {id});
        const d = res.data;
        document.getElementById('mkDeviceModalTitle').innerHTML = '<i class="fas fa-network-wired"></i> Editar Dispositivo';
        document.getElementById('mkDeviceId').value = d.id;
        document.getElementById('mkDeviceNome').value = d.nome;
        document.getElementById('mkDeviceDesc').value = d.descricao || '';
        document.getElementById('mkDeviceHost').value = d.host;
        document.getElementById('mkDevicePorta').value = d.porta;
        document.getElementById('mkDeviceUser').value = d.usuario;
        document.getElementById('mkDeviceSSL').checked = d.use_ssl == 1;
        document.getElementById('btnMkExcluir').style.display = 'inline-flex';
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    }
}

async function mkSalvarDevice() {
    const btn = document.getElementById('btnMkSalvar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        const id = document.getElementById('mkDeviceId').value;
        const data = {
            nome: document.getElementById('mkDeviceNome').value,
            descricao: document.getElementById('mkDeviceDesc').value,
            host: document.getElementById('mkDeviceHost').value,
            porta: document.getElementById('mkDevicePorta').value,
            usuario: document.getElementById('mkDeviceUser').value,
            senha: document.getElementById('mkDevicePass').value,
            use_ssl: document.getElementById('mkDeviceSSL').checked ? 1 : 0,
        };

        if (id) {
            data.id = id;
            await mkPost('atualizar_dispositivo', data);
        } else {
            await mkPost('criar_dispositivo', data);
        }

        mkToast('Dispositivo salvo com sucesso!');
        mkFecharModal('modalMkDevice');
        mkCarregarDevices();

    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
    }
}

async function mkExcluirDevice() {
    const id = document.getElementById('mkDeviceId').value;
    if (!id || !confirm('Excluir este dispositivo?')) return;
    try {
        await mkPost('excluir_dispositivo', {id});
        mkToast('Dispositivo excluído!');
        mkFecharModal('modalMkDevice');
        if (mkDeviceAtivo == id) {
            mkDeviceAtivo = null;
            document.getElementById('mkEmpty').style.display = 'block';
            document.getElementById('mkContent').style.display = 'none';
        }
        mkCarregarDevices();
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    }
}

async function mkTestarConexao() {
    const btn = document.getElementById('btnMkTestar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
    try {
        await mkPost('testar_conexao', {
            id: document.getElementById('mkDeviceId').value || undefined,
            host: document.getElementById('mkDeviceHost').value,
            porta: document.getElementById('mkDevicePorta').value,
            usuario: document.getElementById('mkDeviceUser').value,
            senha: document.getElementById('mkDevicePass').value,
            use_ssl: document.getElementById('mkDeviceSSL').checked ? 1 : 0,
        });
        mkToast('✅ Conexão bem-sucedida!');
    } catch(e) {
        mkToast('❌ Falha: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Testar Conexão';
    }
}

// ==========================================
//  OVERVIEW
// ==========================================
async function mkCarregarOverview() {
    const container = document.getElementById('mkOverviewContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando overview...</p></div>';

    try {
        const res = await mkGet('overview');
        const d = res.data;

        const cpuColor = d.cpu_load > 80 ? '#EF4444' : d.cpu_load > 50 ? '#F59E0B' : '#10B981';
        const memColor = d.memory_pct > 80 ? '#EF4444' : d.memory_pct > 50 ? '#F59E0B' : '#10B981';
        const hddColor = d.hdd_pct > 80 ? '#EF4444' : d.hdd_pct > 50 ? '#F59E0B' : '#10B981';

        container.innerHTML = `
            <!-- Info Card -->
            <div class="mk-info-card">
                <div class="mk-info-card-header">
                    <div class="mk-identity">
                        <i class="fas fa-network-wired" style="font-size:24px;color:#D6336C"></i>
                        <div>
                            <h3>${mkEsc(d.identity)}</h3>
                            <span class="text-muted">${mkEsc(d.board_name)} — RouterOS ${mkEsc(d.version)}</span>
                        </div>
                    </div>
                    <div class="mk-uptime">
                        <i class="fas fa-clock"></i> Uptime: <strong>${mkEsc(d.uptime)}</strong>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="mk-stats-grid">
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:${cpuColor}15;color:${cpuColor}">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.cpu_load}%</div>
                        <div class="mk-stat-label">CPU (${d.cpu_count} core${d.cpu_count > 1 ? 's' : ''})</div>
                        <div class="mk-progress"><div class="mk-progress-bar" style="width:${d.cpu_load}%;background:${cpuColor}"></div></div>
                    </div>
                </div>
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:${memColor}15;color:${memColor}">
                        <i class="fas fa-memory"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.memory_pct}%</div>
                        <div class="mk-stat-label">RAM — ${mkFormatBytes(d.used_memory)} / ${mkFormatBytes(d.total_memory)}</div>
                        <div class="mk-progress"><div class="mk-progress-bar" style="width:${d.memory_pct}%;background:${memColor}"></div></div>
                    </div>
                </div>
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:${hddColor}15;color:${hddColor}">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.hdd_pct}%</div>
                        <div class="mk-stat-label">Disco — ${mkFormatBytes(d.used_hdd)} / ${mkFormatBytes(d.total_hdd)}</div>
                        <div class="mk-progress"><div class="mk-progress-bar" style="width:${d.hdd_pct}%;background:${hddColor}"></div></div>
                    </div>
                </div>
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:#3B82F615;color:#3B82F6">
                        <i class="fas fa-ethernet"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.interfaces_up} / ${d.interfaces_total}</div>
                        <div class="mk-stat-label">Interfaces (UP/Total)</div>
                    </div>
                </div>
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:#8B5CF615;color:#8B5CF6">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.dhcp_leases}</div>
                        <div class="mk-stat-label">DHCP Leases</div>
                    </div>
                </div>
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:#EC489915;color:#EC4899">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.firewall_rules}</div>
                        <div class="mk-stat-label">Regras Firewall</div>
                    </div>
                </div>
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:#F59E0B15;color:#F59E0B">
                        <i class="fas fa-random"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.nat_rules}</div>
                        <div class="mk-stat-label">Regras NAT</div>
                    </div>
                </div>
                <div class="mk-stat-card">
                    <div class="mk-stat-icon" style="background:#06B6D415;color:#06B6D4">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div class="mk-stat-info">
                        <div class="mk-stat-num">${d.queues}</div>
                        <div class="mk-stat-label">Simple Queues</div>
                    </div>
                </div>
            </div>

            <!-- IPs -->
            ${d.ip_addresses && d.ip_addresses.length ? `
            <div class="mk-section-title"><i class="fas fa-map-marker-alt"></i> Endereços IP</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Endereço</th><th>Rede</th><th>Interface</th><th>Status</th></tr></thead>
                    <tbody>
                        ${d.ip_addresses.map(ip => `<tr>
                            <td><strong>${mkEsc(ip.address)}</strong></td>
                            <td>${mkEsc(ip.network || '-')}</td>
                            <td>${mkEsc(ip.interface || '-')}</td>
                            <td>${ip.disabled === 'true' ? mkBadge('Desabilitado','danger') : mkBadge('Ativo','success')}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
            ` : ''}

            <div style="text-align:center;margin-top:16px">
                <button class="btn btn-sm" onclick="mkAbrirModalDevice(${mkDeviceAtivo})">
                    <i class="fas fa-cog"></i> Configurações do Dispositivo
                </button>
            </div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle fa-2x" style="color:#EF4444;margin-bottom:12px"></i><h3>Erro de Conexão</h3><p>${mkEsc(e.message)}</p><button class="btn btn-primary btn-sm" onclick="mkCarregarOverview()" style="margin-top:12px"><i class="fas fa-sync-alt"></i> Tentar novamente</button></div>`;
    }
}

// ==========================================
//  INTERFACES
// ==========================================
async function mkCarregarInterfaces() {
    const container = document.getElementById('mkInterfacesContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('interfaces');
        const ifs = res.data || [];

        if (!ifs.length) {
            container.innerHTML = '<div class="empty-state"><p>Nenhuma interface encontrada</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Nome</th><th>Tipo</th><th>MAC</th><th>TX</th><th>RX</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        ${ifs.map((i, idx) => {
                            const running = i.running === 'true';
                            const disabled = i.disabled === 'true';
                            return `<tr style="${disabled ? 'opacity:0.5' : ''}">
                                <td>${idx + 1}</td>
                                <td><strong>${mkEsc(i.name)}</strong>${i.comment ? '<br><small class="text-muted">' + mkEsc(i.comment) + '</small>' : ''}</td>
                                <td>${mkEsc(i.type || '-')}</td>
                                <td><code style="font-size:11px">${mkEsc(i['mac-address'] || '-')}</code></td>
                                <td>${mkFormatBytes(i['tx-byte'] || 0)}</td>
                                <td>${mkFormatBytes(i['rx-byte'] || 0)}</td>
                                <td>${disabled ? mkBadge('Desabilitado','danger') : running ? mkBadge('Running','success') : mkBadge('Down','warning')}</td>
                                <td>
                                    ${disabled
                                        ? `<button class="btn btn-sm" onclick="mkToggleInterface('${i['.id']}', true)" title="Habilitar"><i class="fas fa-play" style="color:#10B981"></i></button>`
                                        : `<button class="btn btn-sm" onclick="mkToggleInterface('${i['.id']}', false)" title="Desabilitar"><i class="fas fa-stop" style="color:#EF4444"></i></button>`
                                    }
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

async function mkToggleInterface(ifId, enable) {
    try {
        await mkPost(enable ? 'enable_interface' : 'disable_interface', {interface_id: ifId});
        mkToast(enable ? 'Interface habilitada!' : 'Interface desabilitada!');
        mkCarregarInterfaces();
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    }
}

// ==========================================
//  NAT
// ==========================================
async function mkCarregarNat() {
    const container = document.getElementById('mkNatContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('nat_rules');
        const rules = res.data || [];

        if (!rules.length) {
            container.innerHTML = '<div class="empty-state"><p>Nenhuma regra NAT configurada</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Chain</th><th>Action</th><th>Protocol</th><th>Src Addr</th><th>Dst Addr</th><th>Dst Port</th><th>To Addr</th><th>To Port</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        ${rules.map((r, idx) => {
                            const disabled = r.disabled === 'true';
                            return `<tr style="${disabled ? 'opacity:0.5' : ''}">
                                <td>${idx}</td>
                                <td>${mkBadge(r.chain || '-', r.chain === 'srcnat' ? 'info' : 'purple')}</td>
                                <td><strong>${mkEsc(r.action || '-')}</strong></td>
                                <td>${mkEsc(r.protocol || 'any')}</td>
                                <td>${mkEsc(r['src-address'] || '*')}</td>
                                <td>${mkEsc(r['dst-address'] || '*')}</td>
                                <td>${mkEsc(r['dst-port'] || '*')}</td>
                                <td>${mkEsc(r['to-addresses'] || '-')}</td>
                                <td>${mkEsc(r['to-ports'] || '-')}</td>
                                <td>${mkDisabledBadge(r.disabled)}</td>
                                <td class="mk-actions-cell">
                                    <button class="btn btn-sm" onclick="mkEditarNat('${r['.id']}')" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm" onclick="mkToggleNat('${r['.id']}', ${disabled})" title="${disabled ? 'Habilitar' : 'Desabilitar'}">
                                        <i class="fas ${disabled ? 'fa-play' : 'fa-pause'}" style="color:${disabled ? '#10B981' : '#F59E0B'}"></i>
                                    </button>
                                    <button class="btn btn-sm" onclick="mkRemoverNat('${r['.id']}')" title="Remover"><i class="fas fa-trash" style="color:#EF4444"></i></button>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            <div class="text-muted" style="font-size:12px;margin-top:8px"><i class="fas fa-info-circle"></i> ${rules.length} regra(s) NAT. Comentários aparecem como tooltip ao editar.</div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

function mkAbrirModalNat(ruleId = null) {
    document.getElementById('mkNatRuleId').value = '';
    document.getElementById('mkNatChain').value = 'dstnat';
    document.getElementById('mkNatAction').value = 'dst-nat';
    document.getElementById('mkNatProtocol').value = '';
    document.getElementById('mkNatInIf').value = '';
    document.getElementById('mkNatSrcAddr').value = '';
    document.getElementById('mkNatDstAddr').value = '';
    document.getElementById('mkNatSrcPort').value = '';
    document.getElementById('mkNatDstPort').value = '';
    document.getElementById('mkNatToAddr').value = '';
    document.getElementById('mkNatToPort').value = '';
    document.getElementById('mkNatComment').value = '';
    document.getElementById('mkNatModalTitle').innerHTML = '<i class="fas fa-random"></i> Nova Regra NAT';
    mkAbrirModal('modalMkNat');
}

async function mkEditarNat(ruleId) {
    // Recarregar as regras para pegar os dados
    try {
        const res = await mkGet('nat_rules');
        const rule = (res.data || []).find(r => r['.id'] === ruleId);
        if (!rule) { mkToast('Regra não encontrada', 'error'); return; }

        document.getElementById('mkNatModalTitle').innerHTML = '<i class="fas fa-random"></i> Editar Regra NAT';
        document.getElementById('mkNatRuleId').value = ruleId;
        document.getElementById('mkNatChain').value = rule.chain || 'dstnat';
        document.getElementById('mkNatAction').value = rule.action || 'dst-nat';
        document.getElementById('mkNatProtocol').value = rule.protocol || '';
        document.getElementById('mkNatInIf').value = rule['in-interface'] || '';
        document.getElementById('mkNatSrcAddr').value = rule['src-address'] || '';
        document.getElementById('mkNatDstAddr').value = rule['dst-address'] || '';
        document.getElementById('mkNatSrcPort').value = rule['src-port'] || '';
        document.getElementById('mkNatDstPort').value = rule['dst-port'] || '';
        document.getElementById('mkNatToAddr').value = rule['to-addresses'] || '';
        document.getElementById('mkNatToPort').value = rule['to-ports'] || '';
        document.getElementById('mkNatComment').value = rule.comment || '';
        mkAbrirModal('modalMkNat');
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    }
}

async function mkSalvarNat() {
    const btn = document.getElementById('btnMkSalvarNat');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        const ruleId = document.getElementById('mkNatRuleId').value;
        const data = {
            chain: document.getElementById('mkNatChain').value,
            action_type: document.getElementById('mkNatAction').value,
            protocol: document.getElementById('mkNatProtocol').value,
            'in-interface': document.getElementById('mkNatInIf').value,
            'src-address': document.getElementById('mkNatSrcAddr').value,
            'dst-address': document.getElementById('mkNatDstAddr').value,
            'src-port': document.getElementById('mkNatSrcPort').value,
            'dst-port': document.getElementById('mkNatDstPort').value,
            'to-addresses': document.getElementById('mkNatToAddr').value,
            'to-ports': document.getElementById('mkNatToPort').value,
            comment: document.getElementById('mkNatComment').value,
        };

        if (ruleId) {
            data.rule_id = ruleId;
            await mkPost('update_nat', data);
        } else {
            await mkPost('add_nat', data);
        }

        mkToast('Regra NAT salva!');
        mkFecharModal('modalMkNat');
        mkCarregarNat();
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
    }
}

async function mkToggleNat(ruleId, currentlyDisabled) {
    try {
        await mkPost(currentlyDisabled ? 'enable_nat' : 'disable_nat', {rule_id: ruleId});
        mkToast(currentlyDisabled ? 'Regra habilitada!' : 'Regra desabilitada!');
        mkCarregarNat();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

async function mkRemoverNat(ruleId) {
    if (!confirm('Remover esta regra NAT?')) return;
    try {
        await mkPost('remove_nat', {rule_id: ruleId});
        mkToast('Regra removida!');
        mkCarregarNat();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

// ==========================================
//  FIREWALL
// ==========================================
async function mkCarregarFirewall() {
    const container = document.getElementById('mkFirewallContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('firewall_rules');
        const rules = res.data || [];

        if (!rules.length) {
            container.innerHTML = '<div class="empty-state"><p>Nenhuma regra de firewall</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Chain</th><th>Action</th><th>Protocol</th><th>Src Addr</th><th>Dst Addr</th><th>Dst Port</th><th>Conn State</th><th>Comentário</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        ${rules.map((r, idx) => {
                            const disabled = r.disabled === 'true';
                            const actionColor = r.action === 'drop' ? 'danger' : r.action === 'reject' ? 'warning' : r.action === 'accept' ? 'success' : 'info';
                            return `<tr style="${disabled ? 'opacity:0.5' : ''}">
                                <td>${idx}</td>
                                <td>${mkBadge(r.chain || '-', 'info')}</td>
                                <td>${mkBadge(r.action || '-', actionColor)}</td>
                                <td>${mkEsc(r.protocol || 'any')}</td>
                                <td>${mkEsc(r['src-address'] || '*')}</td>
                                <td>${mkEsc(r['dst-address'] || '*')}</td>
                                <td>${mkEsc(r['dst-port'] || '*')}</td>
                                <td>${mkEsc(r['connection-state'] || '-')}</td>
                                <td><small>${mkEsc(r.comment || '')}</small></td>
                                <td>${mkDisabledBadge(r.disabled)}</td>
                                <td class="mk-actions-cell">
                                    <button class="btn btn-sm" onclick="mkEditarFirewall('${r['.id']}')" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm" onclick="mkToggleFirewall('${r['.id']}', ${disabled})" title="${disabled ? 'Habilitar' : 'Desabilitar'}">
                                        <i class="fas ${disabled ? 'fa-play' : 'fa-pause'}" style="color:${disabled ? '#10B981' : '#F59E0B'}"></i>
                                    </button>
                                    <button class="btn btn-sm" onclick="mkRemoverFirewall('${r['.id']}')" title="Remover"><i class="fas fa-trash" style="color:#EF4444"></i></button>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

function mkAbrirModalFirewall() {
    document.getElementById('mkFwRuleId').value = '';
    document.getElementById('mkFwChain').value = 'forward';
    document.getElementById('mkFwAction').value = 'accept';
    document.getElementById('mkFwProtocol').value = '';
    document.getElementById('mkFwConnState').value = '';
    document.getElementById('mkFwSrcAddr').value = '';
    document.getElementById('mkFwDstAddr').value = '';
    document.getElementById('mkFwSrcPort').value = '';
    document.getElementById('mkFwDstPort').value = '';
    document.getElementById('mkFwInIf').value = '';
    document.getElementById('mkFwOutIf').value = '';
    document.getElementById('mkFwComment').value = '';
    document.getElementById('mkFwModalTitle').innerHTML = '<i class="fas fa-shield-alt"></i> Nova Regra Firewall';
    mkAbrirModal('modalMkFirewall');
}

async function mkEditarFirewall(ruleId) {
    try {
        const res = await mkGet('firewall_rules');
        const rule = (res.data || []).find(r => r['.id'] === ruleId);
        if (!rule) { mkToast('Regra não encontrada', 'error'); return; }

        document.getElementById('mkFwModalTitle').innerHTML = '<i class="fas fa-shield-alt"></i> Editar Regra Firewall';
        document.getElementById('mkFwRuleId').value = ruleId;
        document.getElementById('mkFwChain').value = rule.chain || 'forward';
        document.getElementById('mkFwAction').value = rule.action || 'accept';
        document.getElementById('mkFwProtocol').value = rule.protocol || '';
        document.getElementById('mkFwConnState').value = rule['connection-state'] || '';
        document.getElementById('mkFwSrcAddr').value = rule['src-address'] || '';
        document.getElementById('mkFwDstAddr').value = rule['dst-address'] || '';
        document.getElementById('mkFwSrcPort').value = rule['src-port'] || '';
        document.getElementById('mkFwDstPort').value = rule['dst-port'] || '';
        document.getElementById('mkFwInIf').value = rule['in-interface'] || '';
        document.getElementById('mkFwOutIf').value = rule['out-interface'] || '';
        document.getElementById('mkFwComment').value = rule.comment || '';
        mkAbrirModal('modalMkFirewall');
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    }
}

async function mkSalvarFirewall() {
    const btn = document.getElementById('btnMkSalvarFw');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        const ruleId = document.getElementById('mkFwRuleId').value;
        const data = {
            chain: document.getElementById('mkFwChain').value,
            action_type: document.getElementById('mkFwAction').value,
            protocol: document.getElementById('mkFwProtocol').value,
            'connection-state': document.getElementById('mkFwConnState').value,
            'src-address': document.getElementById('mkFwSrcAddr').value,
            'dst-address': document.getElementById('mkFwDstAddr').value,
            'src-port': document.getElementById('mkFwSrcPort').value,
            'dst-port': document.getElementById('mkFwDstPort').value,
            'in-interface': document.getElementById('mkFwInIf').value,
            'out-interface': document.getElementById('mkFwOutIf').value,
            comment: document.getElementById('mkFwComment').value,
        };

        if (ruleId) {
            data.rule_id = ruleId;
            await mkPost('update_firewall', data);
        } else {
            await mkPost('add_firewall', data);
        }

        mkToast('Regra salva!');
        mkFecharModal('modalMkFirewall');
        mkCarregarFirewall();
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
    }
}

async function mkToggleFirewall(ruleId, currentlyDisabled) {
    try {
        await mkPost(currentlyDisabled ? 'enable_firewall' : 'disable_firewall', {rule_id: ruleId});
        mkToast(currentlyDisabled ? 'Regra habilitada!' : 'Regra desabilitada!');
        mkCarregarFirewall();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

async function mkRemoverFirewall(ruleId) {
    if (!confirm('Remover esta regra de firewall?')) return;
    try {
        await mkPost('remove_firewall', {rule_id: ruleId});
        mkToast('Regra removida!');
        mkCarregarFirewall();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

// ==========================================
//  DHCP
// ==========================================
async function mkCarregarDhcp() {
    const container = document.getElementById('mkDhcpContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('dhcp_leases');
        const leases = res.data || [];

        if (!leases.length) {
            container.innerHTML = '<div class="empty-state"><p>Nenhuma lease DHCP</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Endereço IP</th><th>MAC Address</th><th>Hostname</th><th>Server</th><th>Status</th><th>Tipo</th><th>Ações</th></tr></thead>
                    <tbody>
                        ${leases.map(l => {
                            const isDynamic = l.dynamic === 'true';
                            const isActive = l.status === 'bound';
                            return `<tr>
                                <td><strong>${mkEsc(l.address || '-')}</strong></td>
                                <td><code style="font-size:11px">${mkEsc(l['mac-address'] || '-')}</code></td>
                                <td>${mkEsc(l['host-name'] || '-')}</td>
                                <td>${mkEsc(l.server || '-')}</td>
                                <td>${isActive ? mkBadge('Bound','success') : mkBadge(l.status || '-','muted')}</td>
                                <td>${isDynamic ? mkBadge('Dinâmico','info') : mkBadge('Estático','purple')}</td>
                                <td class="mk-actions-cell">
                                    ${isDynamic ? `<button class="btn btn-sm" onclick="mkDhcpMakeStatic('${l['.id']}')" title="Tornar estático"><i class="fas fa-thumbtack" style="color:#8B5CF6"></i></button>` : ''}
                                    <button class="btn btn-sm" onclick="mkDhcpRemove('${l['.id']}')" title="Remover"><i class="fas fa-trash" style="color:#EF4444"></i></button>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            <div class="text-muted" style="font-size:12px;margin-top:8px"><i class="fas fa-info-circle"></i> ${leases.length} lease(s) DHCP</div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

async function mkDhcpMakeStatic(leaseId) {
    if (!confirm('Converter lease para estática?')) return;
    try {
        await mkPost('make_dhcp_static', {lease_id: leaseId});
        mkToast('Lease convertida para estática!');
        mkCarregarDhcp();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

async function mkDhcpRemove(leaseId) {
    if (!confirm('Remover esta lease?')) return;
    try {
        await mkPost('remove_dhcp_lease', {lease_id: leaseId});
        mkToast('Lease removida!');
        mkCarregarDhcp();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

// ==========================================
//  QUEUES
// ==========================================
async function mkCarregarQueues() {
    const container = document.getElementById('mkQueuesContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('queues');
        const queues = res.data || [];

        if (!queues.length) {
            container.innerHTML = '<div class="empty-state"><p>Nenhuma queue configurada</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Nome</th><th>Target</th><th>Max Limit</th><th>Burst</th><th>Taxa Atual</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        ${queues.map(q => {
                            const disabled = q.disabled === 'true';
                            return `<tr style="${disabled ? 'opacity:0.5' : ''}">
                                <td><strong>${mkEsc(q.name)}</strong>${q.comment ? '<br><small class="text-muted">' + mkEsc(q.comment) + '</small>' : ''}</td>
                                <td>${mkEsc(q.target || '-')}</td>
                                <td>${mkEsc(q['max-limit'] || '-')}</td>
                                <td>${mkEsc(q['burst-limit'] || '-')}</td>
                                <td>${mkEsc(q.rate || '-')}</td>
                                <td>${mkDisabledBadge(q.disabled)}</td>
                                <td class="mk-actions-cell">
                                    <button class="btn btn-sm" onclick="mkEditarQueue('${q['.id']}')" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm" onclick="mkToggleQueue('${q['.id']}', ${disabled})" title="${disabled ? 'Habilitar' : 'Desabilitar'}">
                                        <i class="fas ${disabled ? 'fa-play' : 'fa-pause'}" style="color:${disabled ? '#10B981' : '#F59E0B'}"></i>
                                    </button>
                                    <button class="btn btn-sm" onclick="mkRemoverQueue('${q['.id']}')" title="Remover"><i class="fas fa-trash" style="color:#EF4444"></i></button>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

function mkAbrirModalQueue(queueId = null) {
    document.getElementById('mkQueueId').value = '';
    document.getElementById('mkQueueName').value = '';
    document.getElementById('mkQueueTarget').value = '';
    document.getElementById('mkQueueMaxLimit').value = '';
    document.getElementById('mkQueueBurstLimit').value = '';
    document.getElementById('mkQueueBurstThreshold').value = '';
    document.getElementById('mkQueueComment').value = '';
    document.getElementById('mkQueueModalTitle').innerHTML = '<i class="fas fa-tachometer-alt"></i> Nova Queue';
    mkAbrirModal('modalMkQueue');
}

async function mkEditarQueue(queueId) {
    try {
        const res = await mkGet('queues');
        const q = (res.data || []).find(r => r['.id'] === queueId);
        if (!q) { mkToast('Queue não encontrada', 'error'); return; }

        document.getElementById('mkQueueModalTitle').innerHTML = '<i class="fas fa-tachometer-alt"></i> Editar Queue';
        document.getElementById('mkQueueId').value = queueId;
        document.getElementById('mkQueueName').value = q.name || '';
        document.getElementById('mkQueueTarget').value = q.target || '';
        document.getElementById('mkQueueMaxLimit').value = q['max-limit'] || '';
        document.getElementById('mkQueueBurstLimit').value = q['burst-limit'] || '';
        document.getElementById('mkQueueBurstThreshold').value = q['burst-threshold'] || '';
        document.getElementById('mkQueueComment').value = q.comment || '';
        mkAbrirModal('modalMkQueue');
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    }
}

async function mkSalvarQueue() {
    const btn = document.getElementById('btnMkSalvarQueue');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        const queueId = document.getElementById('mkQueueId').value;
        const data = {
            name: document.getElementById('mkQueueName').value,
            target: document.getElementById('mkQueueTarget').value,
            'max-limit': document.getElementById('mkQueueMaxLimit').value,
            'burst-limit': document.getElementById('mkQueueBurstLimit').value,
            'burst-threshold': document.getElementById('mkQueueBurstThreshold').value,
            comment: document.getElementById('mkQueueComment').value,
        };

        if (queueId) {
            data.queue_id = queueId;
            await mkPost('update_queue', data);
        } else {
            await mkPost('add_queue', data);
        }

        mkToast('Queue salva!');
        mkFecharModal('modalMkQueue');
        mkCarregarQueues();
    } catch(e) {
        mkToast('Erro: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
    }
}

async function mkToggleQueue(queueId, currentlyDisabled) {
    try {
        await mkPost(currentlyDisabled ? 'enable_queue' : 'disable_queue', {queue_id: queueId});
        mkToast(currentlyDisabled ? 'Queue habilitada!' : 'Queue desabilitada!');
        mkCarregarQueues();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

async function mkRemoverQueue(queueId) {
    if (!confirm('Remover esta queue?')) return;
    try {
        await mkPost('remove_queue', {queue_id: queueId});
        mkToast('Queue removida!');
        mkCarregarQueues();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

// ==========================================
//  ROUTES
// ==========================================
async function mkCarregarRoutes() {
    const container = document.getElementById('mkRoutesContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('routes');
        const routes = res.data || [];

        if (!routes.length) {
            container.innerHTML = '<div class="empty-state"><p>Nenhuma rota encontrada</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Dst Address</th><th>Gateway</th><th>Distance</th><th>Tipo</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        ${routes.map(r => {
                            const isDynamic = r.dynamic === 'true';
                            const active = r.active === 'true';
                            return `<tr>
                                <td><strong>${mkEsc(r['dst-address'] || '-')}</strong></td>
                                <td>${mkEsc(r.gateway || '-')}</td>
                                <td>${mkEsc(r.distance || '-')}</td>
                                <td>${isDynamic ? mkBadge('Dinâmica','info') : mkBadge('Estática','purple')}</td>
                                <td>${active ? mkBadge('Ativa','success') : mkBadge('Inativa','muted')}</td>
                                <td>
                                    ${!isDynamic ? `<button class="btn btn-sm" onclick="mkRemoverRoute('${r['.id']}')" title="Remover"><i class="fas fa-trash" style="color:#EF4444"></i></button>` : ''}
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

function mkAbrirModalRoute() {
    document.getElementById('mkRouteDst').value = '';
    document.getElementById('mkRouteGw').value = '';
    document.getElementById('mkRouteDist').value = '1';
    document.getElementById('mkRouteComment').value = '';
    mkAbrirModal('modalMkRoute');
}

async function mkSalvarRoute() {
    try {
        await mkPost('add_route', {
            'dst-address': document.getElementById('mkRouteDst').value,
            gateway: document.getElementById('mkRouteGw').value,
            distance: document.getElementById('mkRouteDist').value,
            comment: document.getElementById('mkRouteComment').value,
        });
        mkToast('Rota adicionada!');
        mkFecharModal('modalMkRoute');
        mkCarregarRoutes();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

async function mkRemoverRoute(routeId) {
    if (!confirm('Remover esta rota?')) return;
    try {
        await mkPost('remove_route', {route_id: routeId});
        mkToast('Rota removida!');
        mkCarregarRoutes();
    } catch(e) { mkToast('Erro: ' + e.message, 'error'); }
}

// ==========================================
//  ARP
// ==========================================
async function mkCarregarArp() {
    const container = document.getElementById('mkArpContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('arp');
        const arps = res.data || [];

        if (!arps.length) {
            container.innerHTML = '<div class="empty-state"><p>Tabela ARP vazia</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Endereço IP</th><th>MAC Address</th><th>Interface</th><th>Tipo</th><th>Status</th></tr></thead>
                    <tbody>
                        ${arps.map(a => `<tr>
                            <td><strong>${mkEsc(a.address || '-')}</strong></td>
                            <td><code style="font-size:11px">${mkEsc(a['mac-address'] || '-')}</code></td>
                            <td>${mkEsc(a.interface || '-')}</td>
                            <td>${a.dynamic === 'true' ? mkBadge('Dinâmico','info') : mkBadge('Estático','purple')}</td>
                            <td>${a.complete === 'true' ? mkBadge('Complete','success') : mkBadge('Incomplete','warning')}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
            <div class="text-muted" style="font-size:12px;margin-top:8px"><i class="fas fa-info-circle"></i> ${arps.length} entrada(s) ARP</div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

// ==========================================
//  LOGS
// ==========================================
async function mkCarregarLogs() {
    const container = document.getElementById('mkLogsContent');
    container.innerHTML = '<div class="loading-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    try {
        const res = await mkGet('logs', {limit: 150});
        const logs = res.data || [];

        if (!logs.length) {
            container.innerHTML = '<div class="empty-state"><p>Nenhum log encontrado</p></div>';
            return;
        }

        container.innerHTML = `
            <div class="mk-logs-container">
                ${logs.reverse().map(l => {
                    const topic = l.topics || '';
                    let cls = 'mk-log-info';
                    if (topic.includes('error') || topic.includes('critical')) cls = 'mk-log-error';
                    else if (topic.includes('warning')) cls = 'mk-log-warn';
                    else if (topic.includes('system') || topic.includes('script')) cls = 'mk-log-system';

                    return `<div class="mk-log-entry ${cls}">
                        <span class="mk-log-time">${mkEsc(l.time || '')}</span>
                        <span class="mk-log-topic">${mkEsc(topic)}</span>
                        <span class="mk-log-msg">${mkEsc(l.message || '')}</span>
                    </div>`;
                }).join('')}
            </div>
        `;
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><p style="color:#EF4444">${mkEsc(e.message)}</p></div>`;
    }
}

// ==========================================
//  IA ASSISTENTE
// ==========================================
let mkIaHistorico = [];
let mkIaStreaming = false;

function mkIaKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        mkIaEnviar();
    }
}

function mkIaAutoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 150) + 'px';
}

function mkIaEnviarSugestao(texto) {
    document.getElementById('mkIaInput').value = texto;
    mkIaEnviar();
}

function mkIaLimpar() {
    mkIaHistorico = [];
    const container = document.getElementById('mkIaMessages');
    container.innerHTML = `
        <div class="mk-ia-welcome">
            <div class="mk-ia-welcome-icon"><i class="fas fa-robot"></i></div>
            <h3>Assistente IA MikroTik</h3>
            <p class="text-muted">Conversa limpa. Faça uma nova pergunta!</p>
        </div>
    `;
}

function mkIaAddMessage(role, content) {
    const container = document.getElementById('mkIaMessages');
    // Remove welcome screen
    const welcome = container.querySelector('.mk-ia-welcome');
    if (welcome) welcome.remove();

    const isUser = role === 'user';
    const div = document.createElement('div');
    div.className = `mk-ia-msg ${isUser ? 'mk-ia-msg-user' : 'mk-ia-msg-ai'}`;

    const avatar = isUser
        ? '<div class="mk-ia-avatar mk-ia-avatar-user"><i class="fas fa-user"></i></div>'
        : '<div class="mk-ia-avatar mk-ia-avatar-ai"><i class="fas fa-robot"></i></div>';

    div.innerHTML = `
        ${avatar}
        <div class="mk-ia-bubble">
            <div class="mk-ia-bubble-content">${isUser ? mkEsc(content) : content}</div>
        </div>
    `;

    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}

function mkIaRenderMarkdown(text) {
    // Use marked.js if available for high-quality rendering
    if (typeof marked !== 'undefined') {
        try { return marked.parse(text); } catch(e) {}
    }
    // Fallback: Simple markdown
    let html = mkEsc(text);
    html = html.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code class="language-$1">$2</code></pre>');
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
    html = html.replace(/^### (.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^## (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^# (.+)$/gm, '<h2>$1</h2>');
    html = html.replace(/^[-*] (.+)$/gm, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');
    html = html.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');
    html = html.replace(/\n/g, '<br>');
    html = html.replace(/(<\/h[234]>|<\/pre>|<\/ul>|<\/ol>)<br>/g, '$1');
    html = html.replace(/<br>(<h[234]>|<pre>|<ul>|<ol>)/g, '$1');
    return html;
}

async function mkIaEnviar() {
    if (mkIaStreaming) return;

    const input = document.getElementById('mkIaInput');
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    input.style.height = 'auto';

    // Add user message
    mkIaAddMessage('user', msg);
    mkIaHistorico.push({role: 'user', content: msg});

    // Add AI placeholder with loading
    const aiDiv = mkIaAddMessage('assistant', '<div class="mk-ia-loading"><i class="fas fa-spinner fa-spin"></i> Carregando modelo IA...</div>');
    const bubbleContent = aiDiv.querySelector('.mk-ia-bubble-content');

    const btn = document.getElementById('btnMkIaSend');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    mkIaStreaming = true;

    let fullContent = '';
    let receivedContent = false;

    try {
        const response = await fetch(MK_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'ia_stream',
                mensagem: msg,
                device_id: mkDeviceAtivo,
                historico: mkIaHistorico.slice(-10),
                modelo: ''
            })
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            throw new Error(errData.error || 'Erro na resposta do servidor');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let lastRender = 0;
        let loadingDots = 0;
        let loadingInterval = null;

        function processLine(line) {
            if (!line.startsWith('data: ')) return;
            try {
                const data = JSON.parse(line.slice(6));

                // Status de conexão
                if (data.status === 'connected') {
                    return;
                }

                // Heartbeat: modelo carregando
                if (data.loading) {
                    if (!loadingInterval) {
                        loadingInterval = setInterval(() => {
                            loadingDots = (loadingDots + 1) % 4;
                            const dots = '.'.repeat(loadingDots);
                            bubbleContent.innerHTML = `<div class="mk-ia-loading"><i class="fas fa-cog fa-spin"></i> Preparando modelo IA${dots}</div>`;
                        }, 500);
                    }
                    return;
                }

                if (data.error) {
                    if (loadingInterval) { clearInterval(loadingInterval); loadingInterval = null; }
                    fullContent += '\n\n❌ Erro: ' + data.error;
                    bubbleContent.innerHTML = mkIaRenderMarkdown(fullContent);
                    return 'stop';
                }
                if (data.content) {
                    if (!receivedContent) {
                        receivedContent = true;
                        if (loadingInterval) { clearInterval(loadingInterval); loadingInterval = null; }
                    }
                    fullContent += data.content;
                    const now = Date.now();
                    if (now - lastRender > 80) {
                        bubbleContent.innerHTML = mkIaRenderMarkdown(fullContent) + '<span class="mk-ia-cursor">▊</span>';
                        const container = document.getElementById('mkIaMessages');
                        container.scrollTop = container.scrollHeight;
                        lastRender = now;
                    }
                }
                if (data.done) {
                    if (loadingInterval) { clearInterval(loadingInterval); loadingInterval = null; }
                    bubbleContent.innerHTML = mkIaRenderMarkdown(fullContent);
                    mkIaHistorico.push({role: 'assistant', content: fullContent});
                    const container = document.getElementById('mkIaMessages');
                    container.scrollTop = container.scrollHeight;
                    return 'stop';
                }
            } catch(e) { /* ignore parse errors */ }
        }

        async function processStream({done, value}) {
            if (value) buffer += decoder.decode(value, {stream: true});
            const lines = buffer.split('\n');
            buffer = lines.pop();
            for (const line of lines) {
                if (processLine(line.trim()) === 'stop' && line.includes('"done":true')) {
                    if (loadingInterval) clearInterval(loadingInterval);
                    return;
                }
            }
            if (done) {
                if (loadingInterval) clearInterval(loadingInterval);
                if (buffer.trim()) processLine(buffer.trim());
                if (fullContent) {
                    bubbleContent.innerHTML = mkIaRenderMarkdown(fullContent);
                    if (!mkIaHistorico.some(h => h.role === 'assistant' && h.content === fullContent)) {
                        mkIaHistorico.push({role: 'assistant', content: fullContent});
                    }
                }
                return;
            }
            return reader.read().then(processStream);
        }

        await reader.read().then(processStream);

    } catch(e) {
        bubbleContent.innerHTML = `<span style="color:#EF4444"><i class="fas fa-exclamation-triangle"></i> ${mkEsc(e.message)}</span>`;
    } finally {
        mkIaStreaming = false;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    }
}
</script>
