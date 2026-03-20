<?php
/**
 * View: Financeiro
 * Notas Fiscais, Contas a Pagar e Integração SEFAZ
 */
require_once __DIR__ . '/../../models/ModuloPermissao.php';
$podeEscrever = temAcessoModulo('financeiro', 'escrita');
$podeAdmin = temAcessoModulo('financeiro', 'admin');
$db = Database::getInstance();
?>

<style>
/* ── Financeiro Styles ── */
.fin-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.fin-kpi{background:#fff;border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f0f0f0;transition:transform .15s}
.fin-kpi:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.fin-kpi-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
.fin-kpi-body{display:flex;flex-direction:column}
.fin-kpi-value{font-size:22px;font-weight:700;color:var(--gray-900)}
.fin-kpi-label{font-size:12px;color:var(--gray-500)}

.fin-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.fin-search{flex:1;min-width:200px;padding:10px 14px 10px 38px;border:1px solid #e2e8f0;border-radius:var(--radius);font-size:14px;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 10-1.397 1.398h-.001l3.85 3.85a1 1 0 001.415-1.414l-3.85-3.85zm-5.44 1.157a5.5 5.5 0 110-11 5.5 5.5 0 010 11z'/%3E%3C/svg%3E") 12px center/16px no-repeat}
.fin-search:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.fin-select{padding:10px 14px;border:1px solid #e2e8f0;border-radius:var(--radius);font-size:14px;background:#fff;cursor:pointer}

.fin-table-wrap{background:#fff;border-radius:var(--radius-lg);box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f0f0f0;overflow-x:auto}
.fin-table{width:100%;border-collapse:collapse;min-width:700px}
.fin-table thead{background:#f8fafc}
.fin-table th{padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.fin-table td{padding:12px 16px;font-size:14px;color:var(--gray-800);border-bottom:1px solid #f1f5f9}
.fin-table tbody tr:hover{background:#f8fafc}
.fin-table tbody tr:last-child td{border-bottom:none}

.fin-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap}
.fin-badge-pendente{background:#FEF3C7;color:#92400E}
.fin-badge-ciencia{background:#DBEAFE;color:#1D4ED8}
.fin-badge-confirmada{background:#D1FAE5;color:#065F46}
.fin-badge-desconhecida{background:#FEE2E2;color:#991B1B}
.fin-badge-nao_realizada{background:#F3F4F6;color:#4B5563}
.fin-badge-aberto{background:#FEF3C7;color:#92400E}
.fin-badge-aprovado{background:#DBEAFE;color:#1D4ED8}
.fin-badge-pago{background:#D1FAE5;color:#065F46}
.fin-badge-cancelado{background:#F3F4F6;color:#6B7280}
.fin-badge-vencido{background:#FEE2E2;color:#991B1B}
.fin-badge-pj{background:#EDE9FE;color:#7C3AED}
.fin-badge-pf{background:#FEF3C7;color:#92400E}

.fin-action-btn{width:32px;height:32px;border:none;border-radius:var(--radius);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--gray-500);transition:all .15s}
.fin-action-btn:hover{background:#f1f5f9;color:var(--primary)}
.fin-action-btn.danger:hover{background:#FEE2E2;color:#DC2626}
.fin-action-btn.success:hover{background:#D1FAE5;color:#059669}

.fin-chave{font-family:'SF Mono',Consolas,monospace;font-size:11px;color:var(--gray-500);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fin-valor{font-weight:600;font-variant-numeric:tabular-nums}

/* Cards grid (fornecedores) */
.fin-cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.fin-forn-card{background:#fff;border-radius:var(--radius-lg);border:1px solid #f0f0f0;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden;transition:transform .15s}
.fin-forn-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.fin-forn-header{padding:16px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #f1f5f9}
.fin-forn-avatar{width:42px;height:42px;border-radius:10px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.fin-forn-header h4{font-size:15px;font-weight:700;color:var(--gray-900);margin:0}
.fin-forn-header small{font-size:12px;color:var(--gray-500)}
.fin-forn-body{padding:12px 20px}
.fin-forn-row{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-600);padding:4px 0}
.fin-forn-row i{width:16px;text-align:center;color:var(--gray-400)}
.fin-forn-footer{padding:12px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
.fin-forn-actions{display:flex;gap:4px}

/* SEFAZ Config Section */
.fin-config-section{background:#fff;border-radius:var(--radius-lg);padding:24px;border:1px solid #f0f0f0;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:16px}
.fin-config-section h3{font-size:16px;font-weight:700;color:var(--gray-900);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.fin-config-row{display:flex;gap:16px;margin-bottom:16px}
.fin-config-row .form-group{flex:1}

/* SEFAZ Status */
.fin-sefaz-status{display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:var(--radius);margin-bottom:16px;font-size:14px;font-weight:600}
.fin-sefaz-status.ready{background:#D1FAE5;color:#065F46}
.fin-sefaz-status.not-ready{background:#FEF3C7;color:#92400E}

/* SEFAZ Log */
.fin-log-list{max-height:400px;overflow-y:auto}
.fin-log-item{padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.fin-log-item:last-child{border-bottom:none}
.fin-log-time{color:var(--gray-400);font-size:11px;font-family:'SF Mono',Consolas,monospace}
.fin-log-acao{font-weight:600;margin:0 6px}
.fin-log-success{color:#059669}
.fin-log-error{color:#DC2626}

/* Modal overlay */
.fin-modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(2px)}
.fin-modal-overlay.active{display:flex}
.fin-modal{background:#fff;border-radius:var(--radius-lg);width:95%;max-width:720px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.fin-modal-header{padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between}
.fin-modal-header h2{font-size:18px;font-weight:700;color:var(--gray-900)}
.fin-modal-close{width:36px;height:36px;border:none;background:#f1f5f9;border-radius:50%;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;color:var(--gray-500);transition:all .15s}
.fin-modal-close:hover{background:#fee2e2;color:#dc2626}
.fin-modal-body{padding:24px;overflow-y:auto;flex:1}
.fin-modal-footer{padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:8px}

.fin-form-row{display:flex;gap:16px;margin-bottom:16px}
.fin-form-row .form-group{flex:1}

.fin-empty{text-align:center;padding:48px 20px;color:var(--gray-400)}
.fin-empty i{font-size:48px;margin-bottom:12px;display:block}
.fin-empty p{font-size:15px;margin-bottom:16px}

/* Manifesto actions dropdown */
.fin-manifesto-actions{position:relative;display:inline-block}
.fin-manifesto-menu{position:absolute;top:100%;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:var(--radius);box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:200px;z-index:100;display:none;padding:4px 0}
.fin-manifesto-menu.show{display:block}
.fin-manifesto-menu a{display:flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;color:var(--gray-700);text-decoration:none;transition:background .1s}
.fin-manifesto-menu a:hover{background:#f8fafc}
.fin-manifesto-menu a i{width:16px;text-align:center}

@media(max-width:768px){
    .fin-kpi-grid{grid-template-columns:1fr 1fr}
    .fin-form-row,.fin-config-row{flex-direction:column;gap:12px}
    .fin-toolbar{flex-direction:column}
    .fin-search{min-width:auto;width:100%}
    .fin-cards-grid{grid-template-columns:1fr}
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-file-invoice-dollar" style="color:#F59E0B"></i> Financeiro</h1>
        <p>Notas Fiscais, Contas a Pagar e Manifesto SEFAZ</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('financeiro_anomalias')">
            <i class="fas fa-robot"></i> Anomalias IA
        </button>
        <?php if ($podeEscrever): ?>
        <button class="btn btn-outline btn-sm" onclick="finImportarXML()">
            <i class="fas fa-file-code"></i> Importar XML
        </button>
        <button class="btn btn-outline btn-sm" onclick="finConsultarSefaz()">
            <i class="fas fa-satellite-dish"></i> Consultar SEFAZ
        </button>
        <button class="btn btn-primary" onclick="finAbrirModalConta()">
            <i class="fas fa-plus"></i> Nova Conta
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPIs -->
<div class="fin-kpi-grid" id="finKpis">
    <div class="fin-kpi"><div class="fin-kpi-icon" style="background:#FEF3C7;color:#D97706"><i class="fas fa-satellite-dish"></i></div><div class="fin-kpi-body"><span class="fin-kpi-value" id="kpiManifesto">-</span><span class="fin-kpi-label">Pendentes Manifesto</span></div></div>
    <div class="fin-kpi"><div class="fin-kpi-icon" style="background:#FEE2E2;color:#DC2626"><i class="fas fa-exclamation-triangle"></i></div><div class="fin-kpi-body"><span class="fin-kpi-value" id="kpiVencidos">-</span><span class="fin-kpi-label">Contas Vencidas</span></div></div>
    <div class="fin-kpi"><div class="fin-kpi-icon" style="background:#DBEAFE;color:#3B82F6"><i class="fas fa-calendar-alt"></i></div><div class="fin-kpi-body"><span class="fin-kpi-value" id="kpiProximos">-</span><span class="fin-kpi-label">Vence em 7 dias</span></div></div>
    <div class="fin-kpi"><div class="fin-kpi-icon" style="background:#FEF3C7;color:#92400E"><i class="fas fa-money-bill-wave"></i></div><div class="fin-kpi-body"><span class="fin-kpi-value" id="kpiPendente">-</span><span class="fin-kpi-label">Total Pendente</span></div></div>
    <div class="fin-kpi"><div class="fin-kpi-icon" style="background:#D1FAE5;color:#059669"><i class="fas fa-check-circle"></i></div><div class="fin-kpi-body"><span class="fin-kpi-value" id="kpiPagoMes">-</span><span class="fin-kpi-label">Pago no Mês</span></div></div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="fin-notas" onclick="finTab(this)">
        <i class="fas fa-file-invoice"></i> Notas Fiscais
    </button>
    <button class="ad-tab" data-tab="fin-contas" onclick="finTab(this)">
        <i class="fas fa-barcode"></i> Contas a Pagar
    </button>
    <button class="ad-tab" data-tab="fin-fornecedores" onclick="finTab(this)">
        <i class="fas fa-building"></i> Fornecedores
    </button>
    <?php if ($podeAdmin): ?>
    <button class="ad-tab" data-tab="fin-sefaz" onclick="finTab(this)">
        <i class="fas fa-cog"></i> SEFAZ Config
    </button>
    <?php endif; ?>
</div>

<!-- Tab: Notas Fiscais -->
<div class="ad-tab-content active" id="fin-notas">
    <div class="fin-toolbar">
        <input type="text" id="finBuscaNF" placeholder="Buscar por número, chave, fornecedor..." class="fin-search" oninput="finCarregarNotas()">
        <select id="finFiltroManifesto" class="fin-select" onchange="finCarregarNotas()">
            <option value="">Todos os Status</option>
            <option value="pendente">Pendente</option>
            <option value="ciencia">Ciência</option>
            <option value="confirmada">Confirmada</option>
            <option value="desconhecida">Desconhecida</option>
            <option value="nao_realizada">Não Realizada</option>
        </select>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table">
            <thead>
                <tr>
                    <th>Nº / Série</th>
                    <th>Fornecedor</th>
                    <th>Chave de Acesso</th>
                    <th>Emissão</th>
                    <th>Valor</th>
                    <th>Manifesto</th>
                    <th width="120">Ações</th>
                </tr>
            </thead>
            <tbody id="finNotasBody"></tbody>
        </table>
    </div>
</div>

<!-- Tab: Contas a Pagar -->
<div class="ad-tab-content" id="fin-contas">
    <div class="fin-toolbar">
        <input type="text" id="finBuscaConta" placeholder="Buscar contas..." class="fin-search" oninput="finCarregarContas()">
        <select id="finFiltroStatusConta" class="fin-select" onchange="finCarregarContas()">
            <option value="">Todos</option>
            <option value="aberto">Aberto</option>
            <option value="aprovado">Aprovado</option>
            <option value="vencido">Vencido</option>
            <option value="pago">Pago</option>
            <option value="cancelado">Cancelado</option>
        </select>
        <select id="finFiltroCategoria" class="fin-select" onchange="finCarregarContas()">
            <option value="">Todas Categorias</option>
            <option value="material">Material</option>
            <option value="servico">Serviço</option>
            <option value="aluguel">Aluguel</option>
            <option value="utilidade">Utilidade</option>
            <option value="imposto">Imposto</option>
            <option value="folha">Folha</option>
            <option value="outros">Outros</option>
        </select>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Fornecedor</th>
                    <th>Vencimento</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th width="120">Ações</th>
                </tr>
            </thead>
            <tbody id="finContasBody"></tbody>
        </table>
    </div>
</div>

<!-- Tab: Fornecedores -->
<div class="ad-tab-content" id="fin-fornecedores">
    <div class="fin-toolbar">
        <input type="text" id="finBuscaForn" placeholder="Buscar fornecedores..." class="fin-search" oninput="finCarregarFornecedores()">
        <?php if ($podeEscrever): ?>
        <button class="btn btn-primary btn-sm" onclick="finAbrirModalFornecedor()">
            <i class="fas fa-plus"></i> Novo Fornecedor
        </button>
        <?php endif; ?>
    </div>
    <div class="fin-cards-grid" id="finFornGrid"></div>
</div>

<!-- Tab: SEFAZ Config -->
<?php if ($podeAdmin): ?>
<div class="ad-tab-content" id="fin-sefaz">
    <div class="fin-sefaz-status" id="finSefazStatus">
        <i class="fas fa-spinner fa-spin"></i> Verificando...
    </div>

    <div class="fin-config-section">
        <h3><i class="fas fa-building" style="color:var(--primary)"></i> Dados da Empresa</h3>
        <div class="fin-config-row">
            <div class="form-group">
                <label>CNPJ</label>
                <input type="text" id="sefazCNPJ" class="form-control" placeholder="00.000.000/0000-00">
            </div>
            <div class="form-group" style="flex:2">
                <label>Razão Social</label>
                <input type="text" id="sefazRazao" class="form-control">
            </div>
        </div>
        <div class="fin-config-row">
            <div class="form-group" style="flex:0 0 120px">
                <label>UF</label>
                <select id="sefazUF" class="form-control">
                    <option value="">...</option>
                    <?php
                    $ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
                    foreach ($ufs as $uf) echo "<option value=\"$uf\">$uf</option>";
                    ?>
                </select>
            </div>
            <div class="form-group" style="flex:0 0 200px">
                <label>Ambiente</label>
                <select id="sefazAmbiente" class="form-control">
                    <option value="2">Homologação</option>
                    <option value="1">Produção</option>
                </select>
            </div>
            <div class="form-group" style="flex:0 0 160px">
                <label>Integração Ativa</label>
                <select id="sefazAtivo" class="form-control">
                    <option value="0">Desativada</option>
                    <option value="1">Ativada</option>
                </select>
            </div>
            <div class="form-group" style="flex:0 0 auto;display:flex;align-items:flex-end;gap:8px">
                <button class="btn btn-primary" onclick="finSalvarConfigSefaz()"><i class="fas fa-save"></i> Salvar</button>
                <button class="btn btn-outline" onclick="finTestarConexao()" title="Testa DNS, SSL e conectividade aos servidores SEFAZ"><i class="fas fa-stethoscope"></i> Testar Conexão</button>
            </div>
        </div>
    </div>

    <!-- Diagnóstico de conexão -->
    <div class="fin-config-section" id="finDiagnosticoSection" style="display:none">
        <h3><i class="fas fa-stethoscope" style="color:#3B82F6"></i> Diagnóstico de Conexão</h3>
        <div id="finDiagnosticoResult" style="font-size:13px"></div>
    </div>

    <div class="fin-config-section">
        <h3><i class="fas fa-key" style="color:#F59E0B"></i> Certificado Digital A1</h3>
        <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px">Faça upload do arquivo .pfx do certificado digital para assinar documentos SEFAZ.</p>
        <div class="fin-config-row">
            <div class="form-group">
                <label>Arquivo .pfx</label>
                <input type="file" id="sefazCertFile" class="form-control" accept=".pfx,.p12">
            </div>
            <div class="form-group">
                <label>Senha do Certificado</label>
                <input type="password" id="sefazCertSenha" class="form-control">
            </div>
            <div class="form-group" style="flex:0 0 auto;display:flex;align-items:flex-end">
                <button class="btn btn-primary" onclick="finUploadCertificado()"><i class="fas fa-upload"></i> Enviar</button>
            </div>
        </div>
        <div id="sefazCertInfo" style="font-size:13px;color:var(--gray-500)"></div>
    </div>

    <div class="fin-config-section">
        <h3><i class="fas fa-list-alt" style="color:#8B5CF6"></i> Log de Comunicações SEFAZ</h3>
        <div class="fin-log-list" id="finSefazLogs">
            <div style="text-align:center;padding:20px;color:var(--gray-400)"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Fornecedor -->
<div class="fin-modal-overlay" id="finModalFornecedor">
    <div class="fin-modal" style="max-width:680px">
        <div class="fin-modal-header">
            <h2 id="finModalFornTitulo">Novo Fornecedor</h2>
            <button class="fin-modal-close" onclick="finFecharModal('finModalFornecedor')">&times;</button>
        </div>
        <div class="fin-modal-body">
            <input type="hidden" id="finFornId">
            <div class="fin-form-row">
                <div class="form-group" style="flex:2">
                    <label>Razão Social *</label>
                    <input type="text" id="finFornRazao" class="form-control">
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select id="finFornTipo" class="form-control">
                        <option value="pj">PJ</option>
                        <option value="pf">PF</option>
                    </select>
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>CNPJ / CPF</label>
                    <input type="text" id="finFornCNPJ" class="form-control">
                </div>
                <div class="form-group">
                    <label>Nome Fantasia</label>
                    <input type="text" id="finFornFantasia" class="form-control">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Inscrição Estadual</label>
                    <input type="text" id="finFornIE" class="form-control">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" id="finFornEmail" class="form-control">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" id="finFornTelefone" class="form-control">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group" style="flex:2">
                    <label>Endereço</label>
                    <input type="text" id="finFornEndereco" class="form-control">
                </div>
                <div class="form-group">
                    <label>Cidade</label>
                    <input type="text" id="finFornCidade" class="form-control">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group" style="flex:0 0 80px">
                    <label>UF</label>
                    <input type="text" id="finFornUF" class="form-control" maxlength="2">
                </div>
                <div class="form-group">
                    <label>CEP</label>
                    <input type="text" id="finFornCEP" class="form-control">
                </div>
                <div class="form-group">
                    <label>Contato</label>
                    <input type="text" id="finFornContato" class="form-control">
                </div>
            </div>
        </div>
        <div class="fin-modal-footer">
            <button class="btn btn-secondary" onclick="finFecharModal('finModalFornecedor')">Cancelar</button>
            <button class="btn btn-primary" onclick="finSalvarFornecedor()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal: Conta a Pagar -->
<div class="fin-modal-overlay" id="finModalConta">
    <div class="fin-modal" style="max-width:720px">
        <div class="fin-modal-header">
            <h2 id="finModalContaTitulo">Nova Conta a Pagar</h2>
            <button class="fin-modal-close" onclick="finFecharModal('finModalConta')">&times;</button>
        </div>
        <div class="fin-modal-body">
            <input type="hidden" id="finContaId">
            <div class="fin-form-row">
                <div class="form-group" style="flex:2">
                    <label>Descrição *</label>
                    <input type="text" id="finContaDescricao" class="form-control">
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select id="finContaCategoria" class="form-control">
                        <option value="">Selecionar...</option>
                        <option value="material">Material</option>
                        <option value="servico">Serviço</option>
                        <option value="aluguel">Aluguel</option>
                        <option value="utilidade">Utilidade</option>
                        <option value="imposto">Imposto</option>
                        <option value="folha">Folha</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Fornecedor</label>
                    <select id="finContaFornecedor" class="form-control">
                        <option value="">Nenhum</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>NF Vinculada</label>
                    <select id="finContaNF" class="form-control">
                        <option value="">Nenhuma</option>
                    </select>
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Valor (R$) *</label>
                    <input type="number" step="0.01" id="finContaValor" class="form-control">
                </div>
                <div class="form-group">
                    <label>Vencimento *</label>
                    <input type="date" id="finContaVencimento" class="form-control">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Código de Barras</label>
                    <input type="text" id="finContaCodBarras" class="form-control">
                </div>
                <div class="form-group">
                    <label>Linha Digitável</label>
                    <input type="text" id="finContaLinhaDigitavel" class="form-control">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Nosso Número</label>
                    <input type="text" id="finContaNossoNum" class="form-control">
                </div>
                <div class="form-group">
                    <label>Banco</label>
                    <input type="text" id="finContaBanco" class="form-control">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Juros (R$)</label>
                    <input type="number" step="0.01" id="finContaJuros" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label>Multa (R$)</label>
                    <input type="number" step="0.01" id="finContaMulta" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label>Desconto (R$)</label>
                    <input type="number" step="0.01" id="finContaDesconto" class="form-control" value="0">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Observações</label>
                    <textarea id="finContaObs" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="fin-modal-footer">
            <button class="btn btn-secondary" onclick="finFecharModal('finModalConta')">Cancelar</button>
            <button class="btn btn-primary" onclick="finSalvarConta()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal: Pagar Conta -->
<div class="fin-modal-overlay" id="finModalPagar">
    <div class="fin-modal" style="max-width:480px">
        <div class="fin-modal-header">
            <h2>Registrar Pagamento</h2>
            <button class="fin-modal-close" onclick="finFecharModal('finModalPagar')">&times;</button>
        </div>
        <div class="fin-modal-body">
            <input type="hidden" id="finPagarId">
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Data Pagamento *</label>
                    <input type="date" id="finPagarData" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Forma de Pagamento</label>
                    <select id="finPagarForma" class="form-control">
                        <option value="boleto">Boleto</option>
                        <option value="pix">PIX</option>
                        <option value="transferencia">Transferência</option>
                        <option value="debito_auto">Débito Automático</option>
                        <option value="cartao">Cartão</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Valor Pago (R$)</label>
                    <input type="number" step="0.01" id="finPagarValor" class="form-control">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="form-group">
                    <label>Comprovante (referência)</label>
                    <input type="text" id="finPagarComprovante" class="form-control" placeholder="Nº transação, protocolo...">
                </div>
            </div>
        </div>
        <div class="fin-modal-footer">
            <button class="btn btn-secondary" onclick="finFecharModal('finModalPagar')">Cancelar</button>
            <button class="btn btn-primary" onclick="finConfirmarPagamento()"><i class="fas fa-check"></i> Confirmar Pagamento</button>
        </div>
    </div>
</div>

<!-- Modal: Importar XML -->
<div class="fin-modal-overlay" id="finModalXML">
    <div class="fin-modal" style="max-width:480px">
        <div class="fin-modal-header">
            <h2>Importar XML de NF-e</h2>
            <button class="fin-modal-close" onclick="finFecharModal('finModalXML')">&times;</button>
        </div>
        <div class="fin-modal-body">
            <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px">Selecione o arquivo XML da NF-e para importar automaticamente.</p>
            <div class="form-group">
                <label>Arquivo XML</label>
                <input type="file" id="finXMLFile" class="form-control" accept=".xml">
            </div>
        </div>
        <div class="fin-modal-footer">
            <button class="btn btn-secondary" onclick="finFecharModal('finModalXML')">Cancelar</button>
            <button class="btn btn-primary" onclick="finEnviarXML()"><i class="fas fa-upload"></i> Importar</button>
        </div>
    </div>
</div>

<script>
const FIN_API = '<?= BASE_URL ?>/api/financeiro.php';
const finPodeEscrever = <?= $podeEscrever ? 'true' : 'false' ?>;
const finPodeAdmin = <?= $podeAdmin ? 'true' : 'false' ?>;

function showToast(msg, type = 'info') {
    const typeMap = { error: 'danger', success: 'success', warning: 'warning', info: 'info' };
    if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) {
        HelpDesk.toast(msg, typeMap[type] || type);
    }
}

function finFormatMoney(v) {
    return 'R$ ' + (parseFloat(v) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function finFormatDate(d) {
    if (!d) return '-';
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('pt-BR');
}

// ── Tabs ──
function finTab(btn) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    const tabId = btn.dataset.tab;
    document.getElementById(tabId).classList.add('active');
    if (tabId === 'fin-contas') finCarregarContas();
    if (tabId === 'fin-fornecedores') finCarregarFornecedores();
    if (tabId === 'fin-sefaz') finCarregarConfigSefaz();
}

// ── Modals ──
function finAbrirModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function finFecharModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
document.querySelectorAll('.fin-modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) finFecharModal(this.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.fin-modal-overlay.active').forEach(m => finFecharModal(m.id));
});

// ── KPIs ──
async function finCarregarStats() {
    try {
        const r = await fetch(FIN_API + '?action=stats');
        const j = await r.json();
        if (j.success) {
            const d = j.data;
            document.getElementById('kpiManifesto').textContent = d.pendentes_manifesto || 0;
            document.getElementById('kpiVencidos').textContent = d.contas_vencidas || 0;
            document.getElementById('kpiProximos').textContent = d.contas_proximas || 0;
            document.getElementById('kpiPendente').textContent = finFormatMoney(d.total_pendente || 0);
            document.getElementById('kpiPagoMes').textContent = finFormatMoney(d.pago_mes || 0);
        }
    } catch(e) { console.error(e); }
}

// ── Notas Fiscais ──
async function finCarregarNotas() {
    const busca = document.getElementById('finBuscaNF').value;
    const status = document.getElementById('finFiltroManifesto').value;
    const params = new URLSearchParams({ action: 'notas', busca, status_manifesto: status });
    try {
        const r = await fetch(FIN_API + '?' + params);
        const j = await r.json();
        const tbody = document.getElementById('finNotasBody');
        if (!j.success || !j.data.length) {
            tbody.innerHTML = `<tr><td colspan="7"><div class="fin-empty"><i class="fas fa-file-invoice"></i><p>Nenhuma nota fiscal encontrada</p></div></td></tr>`;
            return;
        }
        tbody.innerHTML = j.data.map(n => {
            const manifestoLabels = {
                'pendente': 'Pendente', 'ciencia': 'Ciência', 'confirmada': 'Confirmada',
                'desconhecida': 'Desconhecida', 'nao_realizada': 'Não Realizada'
            };
            const label = manifestoLabels[n.status_manifesto] || n.status_manifesto;
            return `<tr>
                <td><strong>${n.numero || '-'}</strong> / ${n.serie || '1'}</td>
                <td>${n.fornecedor_razao || n.fornecedor_nome || '-'}</td>
                <td><span class="fin-chave" title="${n.chave_acesso || ''}">${n.chave_acesso || '-'}</span></td>
                <td>${finFormatDate(n.data_emissao)}</td>
                <td class="fin-valor">${finFormatMoney(n.valor_total)}</td>
                <td><span class="fin-badge fin-badge-${n.status_manifesto}">${label}</span></td>
                <td>
                    ${finPodeEscrever && n.status_manifesto === 'pendente' ? `
                        <button class="fin-action-btn" title="Ciência da Operação" onclick="finManifestar('${n.chave_acesso}','ciencia')"><i class="fas fa-eye"></i></button>
                        <button class="fin-action-btn success" title="Confirmar" onclick="finManifestar('${n.chave_acesso}','confirmada')"><i class="fas fa-check"></i></button>
                    ` : ''}
                    ${finPodeEscrever && n.status_manifesto === 'ciencia' ? `
                        <button class="fin-action-btn success" title="Confirmar Operação" onclick="finManifestar('${n.chave_acesso}','confirmada')"><i class="fas fa-check-double"></i></button>
                        <button class="fin-action-btn danger" title="Desconhecer" onclick="finManifestar('${n.chave_acesso}','desconhecida')"><i class="fas fa-times"></i></button>
                    ` : ''}
                    ${finPodeEscrever && n.id ? `<button class="fin-action-btn" title="Gerar Conta" onclick="finGerarContaDeNF(${n.id})" ><i class="fas fa-barcode"></i></button>` : ''}
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

// ── Manifesto SEFAZ ──
async function finManifestar(chave, tipo) {
    const labels = { ciencia: 'Ciência da Operação', confirmada: 'Confirmação', desconhecida: 'Desconhecimento', nao_realizada: 'Operação Não Realizada' };
    let justificativa = '';
    if (tipo === 'desconhecida' || tipo === 'nao_realizada') {
        justificativa = prompt(`Justificativa para "${labels[tipo]}" (mín. 15 caracteres):`);
        if (!justificativa || justificativa.length < 15) { showToast('Justificativa deve ter no mínimo 15 caracteres', 'warning'); return; }
    }
    if (!confirm(`Confirma ${labels[tipo]} para esta NF-e?`)) return;
    try {
        const r = await fetch(FIN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'sefaz_manifestar', chave_acesso: chave, tipo_manifesto: tipo, justificativa })
        });
        const j = await r.json();
        if (j.success && j.data.sucesso) {
            showToast(`Manifesto "${labels[tipo]}" enviado com sucesso!`, 'success');
            finCarregarNotas();
            finCarregarStats();
        } else {
            showToast(j.data?.mensagem || j.error || 'Erro ao manifestar', 'error');
        }
    } catch(e) { showToast('Erro de comunicação', 'error'); }
}

// ── Consultar DF-e ──
async function finConsultarSefaz() {
    showToast('Consultando SEFAZ...', 'info');
    try {
        const r = await fetch(FIN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'sefaz_consultar' })
        });
        const j = await r.json();
        if (j.success) {
            const d = j.data;
            if (d.cStat) {
                showToast(`SEFAZ [${d.cStat}]: ${d.xMotivo || ''} — ${(d.notas||[]).length} doc(s) recebido(s)`, 'success');
            } else {
                showToast(`SEFAZ: ${(d.notas||[]).length} documento(s) processado(s)`, 'success');
            }
            finCarregarNotas();
            finCarregarStats();
        } else {
            showToast(j.error || 'Erro na consulta SEFAZ', 'error');
        }
    } catch(e) {
        console.error('SEFAZ error:', e);
        showToast('Falha na comunicação com o servidor. Verifique o console para detalhes.', 'error');
    }
}

// ── Testar Conexão SEFAZ ──
async function finTestarConexao() {
    const section = document.getElementById('finDiagnosticoSection');
    const result = document.getElementById('finDiagnosticoResult');
    section.style.display = 'block';
    result.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando conexão com servidores SEFAZ...';
    showToast('Testando conexão SEFAZ...', 'info');
    try {
        const r = await fetch(FIN_API + '?action=sefaz_testar');
        const j = await r.json();
        if (j.success) {
            const d = j.data;
            let html = `<div style="margin-bottom:12px"><strong>Ambiente:</strong> ${d.ambiente} | <strong>UF:</strong> ${d.uf} | <strong>Status Geral:</strong> <span style="color:${d.ok?'#059669':'#DC2626'}">${d.ok?'✅ OK':'❌ Falhou'}</span></div>`;
            html += '<table style="width:100%;font-size:13px;border-collapse:collapse">';
            html += '<tr style="background:#f1f5f9"><th style="text-align:left;padding:6px">Serviço</th><th>URL</th><th>Status</th><th>Tempo</th><th>Erro</th></tr>';
            for (const [nome, t] of Object.entries(d.testes)) {
                const icon = t.ok ? '✅' : '❌';
                const urlShort = t.url ? (new URL(t.url).hostname) : '-';
                html += `<tr style="border-bottom:1px solid #e2e8f0">`
                    + `<td style="padding:6px;font-weight:500">${nome}</td>`
                    + `<td style="padding:6px;font-size:11px;color:#6b7280">${urlShort}</td>`
                    + `<td style="padding:6px;text-align:center">${icon}</td>`
                    + `<td style="padding:6px;text-align:center">${t.tempo||'-'}</td>`
                    + `<td style="padding:6px;color:#DC2626;font-size:11px">${t.erro||''}</td>`
                    + '</tr>';
            }
            html += '</table>';
            result.innerHTML = html;
            showToast(d.ok ? 'Conexão com SEFAZ OK!' : 'Problemas detectados — veja o diagnóstico', d.ok ? 'success' : 'warning');
        } else {
            result.innerHTML = `<span style="color:#DC2626">❌ ${j.error}</span>`;
            showToast(j.error, 'error');
        }
    } catch(e) {
        result.innerHTML = '<span style="color:#DC2626">❌ Erro ao executar diagnóstico: ' + e.message + '</span>';
        showToast('Erro ao testar conexão', 'error');
    }
}

// ── Importar XML ──
function finImportarXML() {
    finAbrirModal('finModalXML');
}

async function finEnviarXML() {
    const file = document.getElementById('finXMLFile').files[0];
    if (!file) { showToast('Selecione um arquivo XML', 'warning'); return; }
    const formData = new FormData();
    formData.append('action', 'sefaz_importar_xml');
    formData.append('xml', file);
    try {
        const r = await fetch(FIN_API, { method: 'POST', body: formData });
        const j = await r.json();
        if (j.success) {
            finFecharModal('finModalXML');
            showToast('NF-e importada com sucesso!', 'success');
            finCarregarNotas();
            finCarregarStats();
        } else showToast(j.error || 'Erro ao importar', 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

// ── Contas a Pagar ──
async function finCarregarContas() {
    const busca = document.getElementById('finBuscaConta').value;
    const status = document.getElementById('finFiltroStatusConta').value;
    const categoria = document.getElementById('finFiltroCategoria').value;
    const params = new URLSearchParams({ action: 'contas', busca, status, categoria });
    try {
        const r = await fetch(FIN_API + '?' + params);
        const j = await r.json();
        const tbody = document.getElementById('finContasBody');
        if (!j.success || !j.data.length) {
            tbody.innerHTML = `<tr><td colspan="7"><div class="fin-empty"><i class="fas fa-barcode"></i><p>Nenhuma conta encontrada</p></div></td></tr>`;
            return;
        }
        tbody.innerHTML = j.data.map(c => {
            const statusLabels = { aberto:'Aberto', aprovado:'Aprovado', pago:'Pago', cancelado:'Cancelado', vencido:'Vencido' };
            const isVencido = c.status === 'vencido' || (c.status === 'aberto' && c.data_vencimento < new Date().toISOString().slice(0,10));
            const badge = isVencido ? 'vencido' : c.status;
            return `<tr>
                <td><strong>${c.codigo || '-'}</strong></td>
                <td>${c.descricao || '-'}</td>
                <td>${c.fornecedor_razao || '-'}</td>
                <td>${finFormatDate(c.data_vencimento)}</td>
                <td class="fin-valor">${finFormatMoney(c.valor)}</td>
                <td><span class="fin-badge fin-badge-${badge}">${statusLabels[badge] || badge}</span></td>
                <td>
                    ${finPodeEscrever && (c.status === 'aberto' || c.status === 'vencido') ? `<button class="fin-action-btn" title="Editar" onclick="finEditarConta(${c.id})"><i class="fas fa-edit"></i></button>` : ''}
                    ${finPodeAdmin && (c.status === 'aberto' || c.status === 'vencido') ? `<button class="fin-action-btn success" title="Aprovar" onclick="finAprovarConta(${c.id})"><i class="fas fa-check"></i></button>` : ''}
                    ${finPodeAdmin && c.status === 'aprovado' ? `<button class="fin-action-btn success" title="Pagar" onclick="finAbrirPagar(${c.id}, ${c.valor})"><i class="fas fa-dollar-sign"></i></button>` : ''}
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

function finAbrirModalConta(dados = null) {
    document.getElementById('finContaId').value = dados ? dados.id : '';
    document.getElementById('finModalContaTitulo').textContent = dados ? 'Editar Conta' : 'Nova Conta a Pagar';
    ['Descricao','Categoria','Valor','Vencimento','CodBarras','LinhaDigitavel','NossoNum','Banco','Obs'].forEach(f => {
        const el = document.getElementById('finConta' + f);
        if (el) el.value = dados ? (dados[f.toLowerCase()] || '') : '';
    });
    if (dados) {
        document.getElementById('finContaDescricao').value = dados.descricao || '';
        document.getElementById('finContaCategoria').value = dados.categoria || '';
        document.getElementById('finContaValor').value = dados.valor || '';
        document.getElementById('finContaVencimento').value = dados.data_vencimento || '';
        document.getElementById('finContaCodBarras').value = dados.codigo_barras || '';
        document.getElementById('finContaLinhaDigitavel').value = dados.linha_digitavel || '';
        document.getElementById('finContaNossoNum').value = dados.nosso_numero || '';
        document.getElementById('finContaBanco').value = dados.banco || '';
        document.getElementById('finContaJuros').value = dados.juros || 0;
        document.getElementById('finContaMulta').value = dados.multa || 0;
        document.getElementById('finContaDesconto').value = dados.desconto || 0;
        document.getElementById('finContaObs').value = dados.observacoes || '';
        document.getElementById('finContaFornecedor').value = dados.fornecedor_id || '';
        document.getElementById('finContaNF').value = dados.nota_fiscal_id || '';
    } else {
        document.getElementById('finContaJuros').value = 0;
        document.getElementById('finContaMulta').value = 0;
        document.getElementById('finContaDesconto').value = 0;
    }
    finCarregarSelectFornecedores();
    finAbrirModal('finModalConta');
}

async function finEditarConta(id) {
    try {
        const r = await fetch(FIN_API + '?action=conta&id=' + id);
        const j = await r.json();
        if (j.success) finAbrirModalConta(j.data);
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao carregar', 'error'); }
}

async function finSalvarConta() {
    const id = document.getElementById('finContaId').value;
    const payload = {
        action: id ? 'atualizar_conta' : 'criar_conta',
        descricao: document.getElementById('finContaDescricao').value,
        categoria: document.getElementById('finContaCategoria').value,
        fornecedor_id: document.getElementById('finContaFornecedor').value,
        nota_fiscal_id: document.getElementById('finContaNF').value,
        valor: document.getElementById('finContaValor').value,
        data_vencimento: document.getElementById('finContaVencimento').value,
        codigo_barras: document.getElementById('finContaCodBarras').value,
        linha_digitavel: document.getElementById('finContaLinhaDigitavel').value,
        nosso_numero: document.getElementById('finContaNossoNum').value,
        banco: document.getElementById('finContaBanco').value,
        juros: document.getElementById('finContaJuros').value,
        multa: document.getElementById('finContaMulta').value,
        desconto: document.getElementById('finContaDesconto').value,
        observacoes: document.getElementById('finContaObs').value
    };
    if (id) payload.id = id;
    try {
        const r = await fetch(FIN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const j = await r.json();
        if (j.success) {
            finFecharModal('finModalConta');
            finCarregarContas();
            finCarregarStats();
            showToast(id ? 'Conta atualizada!' : 'Conta cadastrada!', 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao salvar', 'error'); }
}

async function finAprovarConta(id) {
    if (!confirm('Aprovar esta conta para pagamento?')) return;
    try {
        const r = await fetch(FIN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'aprovar_conta', id })
        });
        const j = await r.json();
        if (j.success) { showToast('Conta aprovada!', 'success'); finCarregarContas(); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

function finAbrirPagar(id, valor) {
    document.getElementById('finPagarId').value = id;
    document.getElementById('finPagarValor').value = valor;
    document.getElementById('finPagarData').value = new Date().toISOString().slice(0, 10);
    finAbrirModal('finModalPagar');
}

async function finConfirmarPagamento() {
    const id = document.getElementById('finPagarId').value;
    const payload = {
        action: 'pagar_conta',
        id: id,
        data_pagamento: document.getElementById('finPagarData').value,
        forma_pagamento: document.getElementById('finPagarForma').value,
        valor_pago: document.getElementById('finPagarValor').value,
        comprovante: document.getElementById('finPagarComprovante').value
    };
    try {
        const r = await fetch(FIN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const j = await r.json();
        if (j.success) {
            finFecharModal('finModalPagar');
            finCarregarContas();
            finCarregarStats();
            showToast('Pagamento registrado!', 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

function finGerarContaDeNF(nfId) {
    // Preenche a modal de conta com a NF vinculada
    fetch(FIN_API + '?action=nota&id=' + nfId).then(r => r.json()).then(j => {
        if (j.success) {
            const n = j.data;
            finAbrirModalConta({
                nota_fiscal_id: n.id,
                fornecedor_id: n.fornecedor_id,
                descricao: 'NF ' + (n.numero || '') + ' - ' + (n.fornecedor_razao || n.natureza_operacao || ''),
                valor: n.valor_total,
                categoria: 'material'
            });
        }
    });
}

// ── Fornecedores ──
async function finCarregarFornecedores() {
    const busca = document.getElementById('finBuscaForn').value;
    const params = new URLSearchParams({ action: 'fornecedores', busca });
    try {
        const r = await fetch(FIN_API + '?' + params);
        const j = await r.json();
        const grid = document.getElementById('finFornGrid');
        if (!j.success || !j.data.length) {
            grid.innerHTML = `<div class="fin-empty"><i class="fas fa-building"></i><p>Nenhum fornecedor cadastrado</p></div>`;
            return;
        }
        grid.innerHTML = j.data.map(f => {
            const initials = (f.razao_social || 'XX').substring(0, 2).toUpperCase();
            return `<div class="fin-forn-card">
                <div class="fin-forn-header">
                    <div class="fin-forn-avatar" style="background:${f.tipo === 'pj' ? '#7C3AED' : '#D97706'}">${initials}</div>
                    <div>
                        <h4>${f.razao_social || f.nome_fantasia || '-'}</h4>
                        <small>${f.cnpj_cpf || ''} <span class="fin-badge fin-badge-${f.tipo}">${(f.tipo || 'pj').toUpperCase()}</span></small>
                    </div>
                </div>
                <div class="fin-forn-body">
                    ${f.email ? `<div class="fin-forn-row"><i class="fas fa-envelope"></i> ${f.email}</div>` : ''}
                    ${f.telefone ? `<div class="fin-forn-row"><i class="fas fa-phone"></i> ${f.telefone}</div>` : ''}
                    ${f.cidade ? `<div class="fin-forn-row"><i class="fas fa-map-marker-alt"></i> ${f.cidade}${f.uf ? ' - ' + f.uf : ''}</div>` : ''}
                    ${f.contato ? `<div class="fin-forn-row"><i class="fas fa-user"></i> ${f.contato}</div>` : ''}
                </div>
                <div class="fin-forn-footer">
                    <small style="color:var(--gray-400)">${f.nome_fantasia || ''}</small>
                    <div class="fin-forn-actions">
                        ${finPodeEscrever ? `<button class="fin-action-btn" title="Editar" onclick="finEditarFornecedor(${f.id})"><i class="fas fa-edit"></i></button>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    } catch(e) { console.error(e); }
}

let fornCache = [];
async function finCarregarSelectFornecedores() {
    try {
        const r = await fetch(FIN_API + '?action=fornecedores');
        const j = await r.json();
        fornCache = j.data || [];
        const sel = document.getElementById('finContaFornecedor');
        const currentVal = sel.value;
        sel.innerHTML = '<option value="">Nenhum</option>' +
            fornCache.map(f => `<option value="${f.id}">${f.razao_social || f.nome_fantasia} (${f.cnpj_cpf || ''})</option>`).join('');
        if (currentVal) sel.value = currentVal;
    } catch(e) {}
}

function finAbrirModalFornecedor(dados = null) {
    document.getElementById('finFornId').value = dados ? dados.id : '';
    document.getElementById('finModalFornTitulo').textContent = dados ? 'Editar Fornecedor' : 'Novo Fornecedor';
    const campos = { Razao: 'razao_social', CNPJ: 'cnpj_cpf', Fantasia: 'nome_fantasia', Tipo: 'tipo',
                     IE: 'inscricao_estadual', Email: 'email', Telefone: 'telefone', Endereco: 'endereco',
                     Cidade: 'cidade', UF: 'uf', CEP: 'cep', Contato: 'contato' };
    Object.entries(campos).forEach(([elSuffix, key]) => {
        const el = document.getElementById('finForn' + elSuffix);
        if (el) el.value = dados ? (dados[key] || '') : '';
    });
    if (!dados) document.getElementById('finFornTipo').value = 'pj';
    finAbrirModal('finModalFornecedor');
}

async function finEditarFornecedor(id) {
    try {
        const r = await fetch(FIN_API + '?action=fornecedor&id=' + id);
        const j = await r.json();
        if (j.success) finAbrirModalFornecedor(j.data);
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

async function finSalvarFornecedor() {
    const id = document.getElementById('finFornId').value;
    const payload = {
        action: id ? 'atualizar_fornecedor' : 'criar_fornecedor',
        razao_social: document.getElementById('finFornRazao').value,
        cnpj_cpf: document.getElementById('finFornCNPJ').value,
        nome_fantasia: document.getElementById('finFornFantasia').value,
        tipo: document.getElementById('finFornTipo').value,
        inscricao_estadual: document.getElementById('finFornIE').value,
        email: document.getElementById('finFornEmail').value,
        telefone: document.getElementById('finFornTelefone').value,
        endereco: document.getElementById('finFornEndereco').value,
        cidade: document.getElementById('finFornCidade').value,
        uf: document.getElementById('finFornUF').value,
        cep: document.getElementById('finFornCEP').value,
        contato: document.getElementById('finFornContato').value
    };
    if (id) payload.id = id;
    try {
        const r = await fetch(FIN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const j = await r.json();
        if (j.success) {
            finFecharModal('finModalFornecedor');
            finCarregarFornecedores();
            finCarregarStats();
            showToast(id ? 'Fornecedor atualizado!' : 'Fornecedor cadastrado!', 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao salvar', 'error'); }
}

// ── SEFAZ Config ──
async function finCarregarConfigSefaz() {
    try {
        // Status
        const rs = await fetch(FIN_API + '?action=sefaz_status');
        const js = await rs.json();
        const statusEl = document.getElementById('finSefazStatus');
        if (js.data?.ready) {
            statusEl.className = 'fin-sefaz-status ready';
            statusEl.innerHTML = '<i class="fas fa-check-circle"></i> SEFAZ configurado e pronto para uso';
        } else {
            statusEl.className = 'fin-sefaz-status not-ready';
            statusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> SEFAZ ainda não configurado — preencha os dados abaixo';
        }
    } catch(e) {}

    try {
        // Config
        const rc = await fetch(FIN_API + '?action=sefaz_config');
        const jc = await rc.json();
        if (jc.success) {
            const c = jc.data;
            document.getElementById('sefazCNPJ').value = c.cnpj_empresa || '';
            document.getElementById('sefazRazao').value = c.razao_social || '';
            document.getElementById('sefazUF').value = c.uf || '';
            document.getElementById('sefazAmbiente').value = c.ambiente === 'producao' ? '1' : '2';
            document.getElementById('sefazAtivo').value = c.ativo || '0';
            if (c.certificado_validade) {
                document.getElementById('sefazCertInfo').innerHTML = `<i class="fas fa-certificate" style="color:#059669"></i> Certificado válido até: <strong>${c.certificado_validade}</strong>`;
            }
        }
    } catch(e) {}

    try {
        // Logs
        const rl = await fetch(FIN_API + '?action=sefaz_logs&limite=30');
        const jl = await rl.json();
        const logsEl = document.getElementById('finSefazLogs');
        if (jl.success && jl.data.length) {
            logsEl.innerHTML = jl.data.map(l => {
                const cls = l.status === 'sucesso' ? 'fin-log-success' : 'fin-log-error';
                return `<div class="fin-log-item">
                    <span class="fin-log-time">${l.criado_em}</span>
                    <span class="fin-log-acao ${cls}">${l.acao}</span>
                    <span>${l.mensagem || ''}</span>
                </div>`;
            }).join('');
        } else {
            logsEl.innerHTML = '<div style="text-align:center;padding:20px;color:var(--gray-400)">Nenhum log registrado</div>';
        }
    } catch(e) {}
}

async function finSalvarConfigSefaz() {
    const ambienteSelect = document.getElementById('sefazAmbiente').value;
    const payload = {
        action: 'sefaz_salvar_config',
        cnpj: document.getElementById('sefazCNPJ').value,
        razao_social: document.getElementById('sefazRazao').value,
        uf: document.getElementById('sefazUF').value,
        ambiente: ambienteSelect === '1' ? 'producao' : 'homologacao',
        ativo: document.getElementById('sefazAtivo').value
    };
    try {
        const r = await fetch(FIN_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const j = await r.json();
        if (j.success) {
            showToast('Configurações SEFAZ salvas!', 'success');
            finCarregarConfigSefaz();
        } else showToast(j.error || 'Erro ao salvar configuração', 'error');
    } catch(e) { showToast('Erro ao salvar: ' + e.message, 'error'); }
}

async function finUploadCertificado() {
    const file = document.getElementById('sefazCertFile').files[0];
    const senha = document.getElementById('sefazCertSenha').value;
    if (!file) { showToast('Selecione o arquivo .pfx', 'warning'); return; }
    if (!senha) { showToast('Digite a senha do certificado', 'warning'); return; }
    const formData = new FormData();
    formData.append('action', 'sefaz_upload_certificado');
    formData.append('certificado', file);
    formData.append('senha_certificado', senha);
    try {
        const r = await fetch(FIN_API, { method: 'POST', body: formData });
        const j = await r.json();
        if (j.success) {
            showToast('Certificado enviado com sucesso!', 'success');
            finCarregarConfigSefaz();
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao enviar certificado', 'error'); }
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
    finCarregarStats();
    finCarregarNotas();
});
</script>
