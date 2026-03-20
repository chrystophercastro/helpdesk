<?php
/**
 * View: Folha de Pagamento
 * Módulo RH — CLT + PJ
 */
require_once __DIR__ . '/../../models/ModuloPermissao.php';
$nivelAcesso = temAcessoModulo('folha_pagamento') ? 'ok' : '';
$podeEscrever = temAcessoModulo('folha_pagamento', 'escrita');
$podeAdmin = temAcessoModulo('folha_pagamento', 'admin');
$db = Database::getInstance();
$departamentos = $db->fetchAll("SELECT id, nome, sigla, cor FROM departamentos WHERE ativo = 1 ORDER BY nome");
?>

<style>
/* ── Folha de Pagamento Styles ── */
.rh-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.rh-kpi{background:#fff;border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f0f0f0;transition:transform .15s}
.rh-kpi:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.rh-kpi-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
.rh-kpi-body{display:flex;flex-direction:column}
.rh-kpi-value{font-size:24px;font-weight:700;color:var(--gray-900)}
.rh-kpi-label{font-size:13px;color:var(--gray-500)}

.rh-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.rh-search{flex:1;min-width:200px;padding:10px 14px 10px 38px;border:1px solid #e2e8f0;border-radius:var(--radius);font-size:14px;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 10-1.397 1.398h-.001l3.85 3.85a1 1 0 001.415-1.414l-3.85-3.85zm-5.44 1.157a5.5 5.5 0 110-11 5.5 5.5 0 010 11z'/%3E%3C/svg%3E") 12px center/16px no-repeat}
.rh-search:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.rh-select{padding:10px 14px;border:1px solid #e2e8f0;border-radius:var(--radius);font-size:14px;background:#fff;cursor:pointer}

.rh-table-wrap{background:#fff;border-radius:var(--radius-lg);box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #f0f0f0;overflow:hidden}
.rh-table{width:100%;border-collapse:collapse}
.rh-table thead{background:#f8fafc}
.rh-table th{padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0}
.rh-table td{padding:12px 16px;font-size:14px;color:var(--gray-800);border-bottom:1px solid #f1f5f9}
.rh-table tbody tr:hover{background:#f8fafc}
.rh-table tbody tr:last-child td{border-bottom:none}

.rh-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600}
.rh-badge-clt{background:#DBEAFE;color:#1D4ED8}
.rh-badge-pj{background:#FEF3C7;color:#92400E}
.rh-badge-ativo{background:#D1FAE5;color:#065F46}
.rh-badge-inativo{background:#FEE2E2;color:#991B1B}
.rh-badge-rascunho{background:#F1F5F9;color:#475569}
.rh-badge-aprovado{background:#DBEAFE;color:#1D4ED8}
.rh-badge-pago{background:#D1FAE5;color:#065F46}
.rh-badge-cancelado{background:#FEE2E2;color:#991B1B}

.rh-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.rh-colab-cell{display:flex;align-items:center;gap:10px}
.rh-colab-info{display:flex;flex-direction:column}
.rh-colab-name{font-weight:600;color:var(--gray-900)}
.rh-colab-sub{font-size:12px;color:var(--gray-500)}

.rh-valor{font-weight:600;font-variant-numeric:tabular-nums}
.rh-valor-bruto{color:var(--gray-900)}
.rh-valor-liquido{color:#059669}
.rh-valor-desc{color:#DC2626}

.rh-action-btn{width:32px;height:32px;border:none;border-radius:var(--radius);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--gray-500);transition:all .15s}
.rh-action-btn:hover{background:#f1f5f9;color:var(--primary)}
.rh-action-btn.danger:hover{background:#FEE2E2;color:#DC2626}

/* Card de resumo da competência */
.rh-comp-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px}
.rh-comp-card{background:#fff;border-radius:var(--radius-lg);padding:20px;border:1px solid #f0f0f0;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.rh-comp-card h4{font-size:14px;color:var(--gray-500);margin-bottom:12px;font-weight:600}
.rh-comp-row{display:flex;justify-content:space-between;margin-bottom:6px;font-size:14px}
.rh-comp-row span:first-child{color:var(--gray-600)}
.rh-comp-row span:last-child{font-weight:600;color:var(--gray-900)}
.rh-comp-total{border-top:2px solid #e2e8f0;padding-top:8px;margin-top:8px}

/* Modal overlay (Pattern B — inline) */
.rh-modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(2px)}
.rh-modal-overlay.active{display:flex}
.rh-modal{background:#fff;border-radius:var(--radius-lg);width:95%;max-width:720px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.rh-modal-header{padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between}
.rh-modal-header h2{font-size:18px;font-weight:700;color:var(--gray-900)}
.rh-modal-close{width:36px;height:36px;border:none;background:#f1f5f9;border-radius:50%;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;color:var(--gray-500);transition:all .15s}
.rh-modal-close:hover{background:#fee2e2;color:#dc2626}
.rh-modal-body{padding:24px;overflow-y:auto;flex:1}
.rh-modal-footer{padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:8px}

.rh-form-row{display:flex;gap:16px;margin-bottom:16px}
.rh-form-row .form-group{flex:1}
.form-group{margin-bottom:0}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:6px}
.form-group .form-control{width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:var(--radius);font-size:14px;transition:border .15s,box-shadow .15s}
.form-group .form-control:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(59,130,246,.1)}

.rh-empty{text-align:center;padding:48px 20px;color:var(--gray-400)}
.rh-empty i{font-size:48px;margin-bottom:12px;display:block}
.rh-empty p{font-size:15px;margin-bottom:16px}

@media(max-width:768px){
    .rh-kpi-grid{grid-template-columns:1fr 1fr}
    .rh-form-row{flex-direction:column;gap:12px}
    .rh-toolbar{flex-direction:column}
    .rh-search{min-width:auto;width:100%}
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-money-check-alt" style="color:#10B981"></i> Folha de Pagamento</h1>
        <p>Gestão de folha de pagamento — CLT e PJ</p>
    </div>
    <div class="page-header-actions">
        <?php if ($podeEscrever): ?>
        <button class="btn btn-outline btn-sm" onclick="rhGerarFolha()">
            <i class="fas fa-cogs"></i> Gerar Folha
        </button>
        <button class="btn btn-primary" onclick="rhAbrirModalColaborador()">
            <i class="fas fa-plus"></i> Novo Colaborador
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPIs -->
<div class="rh-kpi-grid" id="rhKpis">
    <div class="rh-kpi"><div class="rh-kpi-icon" style="background:#DBEAFE;color:#3B82F6"><i class="fas fa-users"></i></div><div class="rh-kpi-body"><span class="rh-kpi-value" id="kpiTotalColab">-</span><span class="rh-kpi-label">Colaboradores Ativos</span></div></div>
    <div class="rh-kpi"><div class="rh-kpi-icon" style="background:#D1FAE5;color:#059669"><i class="fas fa-id-card"></i></div><div class="rh-kpi-body"><span class="rh-kpi-value" id="kpiClt">-</span><span class="rh-kpi-label">CLT</span></div></div>
    <div class="rh-kpi"><div class="rh-kpi-icon" style="background:#FEF3C7;color:#D97706"><i class="fas fa-file-invoice"></i></div><div class="rh-kpi-body"><span class="rh-kpi-value" id="kpiPj">-</span><span class="rh-kpi-label">PJ</span></div></div>
    <div class="rh-kpi"><div class="rh-kpi-icon" style="background:#EDE9FE;color:#7C3AED"><i class="fas fa-wallet"></i></div><div class="rh-kpi-body"><span class="rh-kpi-value" id="kpiFolhaMes">-</span><span class="rh-kpi-label">Folha do Mês</span></div></div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="rh-colaboradores" onclick="rhTab(this)">
        <i class="fas fa-users"></i> Colaboradores
    </button>
    <button class="ad-tab" data-tab="rh-folha" onclick="rhTab(this)">
        <i class="fas fa-file-invoice-dollar"></i> Lançamentos
    </button>
    <button class="ad-tab" data-tab="rh-resumo" onclick="rhTab(this)">
        <i class="fas fa-chart-pie"></i> Resumo
    </button>
</div>

<!-- Tab: Colaboradores -->
<div class="ad-tab-content active" id="rh-colaboradores">
    <div class="rh-toolbar">
        <input type="text" id="rhBuscaColab" placeholder="Buscar colaboradores..." class="rh-search" oninput="rhCarregarColaboradores()">
        <select id="rhFiltroTipo" class="rh-select" onchange="rhCarregarColaboradores()">
            <option value="">Todos os Tipos</option>
            <option value="clt">CLT</option>
            <option value="pj">PJ</option>
        </select>
        <select id="rhFiltroStatus" class="rh-select" onchange="rhCarregarColaboradores()">
            <option value="ativo">Ativos</option>
            <option value="inativo">Inativos</option>
            <option value="">Todos</option>
        </select>
    </div>
    <div class="rh-table-wrap">
        <table class="rh-table" id="rhTabelaColab">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Tipo</th>
                    <th>Cargo / Serviço</th>
                    <th>Salário / Valor</th>
                    <th>Admissão</th>
                    <th>Status</th>
                    <th width="80">Ações</th>
                </tr>
            </thead>
            <tbody id="rhColabBody"></tbody>
        </table>
    </div>
</div>

<!-- Tab: Lançamentos -->
<div class="ad-tab-content" id="rh-folha">
    <div class="rh-toolbar">
        <input type="month" id="rhCompetencia" class="rh-select" value="<?= date('Y-m') ?>" onchange="rhCarregarLancamentos()">
        <select id="rhFiltroTipoFolha" class="rh-select" onchange="rhCarregarLancamentos()">
            <option value="">Todos os Tipos</option>
            <option value="clt">CLT</option>
            <option value="pj">PJ</option>
        </select>
        <select id="rhFiltroStatusFolha" class="rh-select" onchange="rhCarregarLancamentos()">
            <option value="">Todos os Status</option>
            <option value="rascunho">Rascunho</option>
            <option value="aprovado">Aprovado</option>
            <option value="pago">Pago</option>
            <option value="cancelado">Cancelado</option>
        </select>
    </div>
    <div class="rh-table-wrap">
        <table class="rh-table" id="rhTabelaFolha">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Tipo</th>
                    <th>Competência</th>
                    <th>Bruto</th>
                    <th>Descontos</th>
                    <th>Líquido</th>
                    <th>Status</th>
                    <th width="100">Ações</th>
                </tr>
            </thead>
            <tbody id="rhFolhaBody"></tbody>
        </table>
    </div>
</div>

<!-- Tab: Resumo -->
<div class="ad-tab-content" id="rh-resumo">
    <div class="rh-toolbar">
        <input type="month" id="rhCompResumo" class="rh-select" value="<?= date('Y-m') ?>" onchange="rhCarregarResumo()">
    </div>
    <div class="rh-comp-cards" id="rhResumoCards"></div>
</div>

<!-- Modal: Colaborador -->
<div class="rh-modal-overlay" id="rhModalColaborador">
    <div class="rh-modal" style="max-width:800px">
        <div class="rh-modal-header">
            <h2 id="rhModalColabTitulo">Novo Colaborador</h2>
            <button class="rh-modal-close" onclick="rhFecharModal('rhModalColaborador')">&times;</button>
        </div>
        <div class="rh-modal-body">
            <input type="hidden" id="rhColabId">
            <div class="rh-form-row">
                <div class="form-group" style="flex:2">
                    <label>Nome Completo *</label>
                    <input type="text" id="rhColabNome" class="form-control">
                </div>
                <div class="form-group">
                    <label>Tipo *</label>
                    <select id="rhColabTipo" class="form-control" onchange="rhToggleTipoCampos()">
                        <option value="clt">CLT</option>
                        <option value="pj">PJ</option>
                    </select>
                </div>
            </div>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>CPF</label>
                    <input type="text" id="rhColabCPF" class="form-control" placeholder="000.000.000-00">
                </div>
                <div class="form-group">
                    <label>RG</label>
                    <input type="text" id="rhColabRG" class="form-control">
                </div>
                <div class="form-group">
                    <label>Data Nascimento</label>
                    <input type="date" id="rhColabNascimento" class="form-control">
                </div>
            </div>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" id="rhColabEmail" class="form-control">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" id="rhColabTelefone" class="form-control">
                </div>
            </div>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>Endereço</label>
                    <input type="text" id="rhColabEndereco" class="form-control">
                </div>
                <div class="form-group">
                    <label>Departamento</label>
                    <select id="rhColabDepartamento" class="form-control">
                        <option value="">Selecionar...</option>
                        <?php foreach ($departamentos as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Campos CLT -->
            <div id="rhCamposCLT">
                <hr style="margin:16px 0;border:none;border-top:1px solid #e2e8f0">
                <h4 style="font-size:14px;color:var(--gray-700);margin-bottom:12px"><i class="fas fa-id-card" style="color:#3B82F6"></i> Dados CLT</h4>
                <div class="rh-form-row">
                    <div class="form-group">
                        <label>CTPS</label>
                        <input type="text" id="rhColabCTPS" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>PIS/PASEP</label>
                        <input type="text" id="rhColabPIS" class="form-control">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="form-group">
                        <label>Cargo</label>
                        <input type="text" id="rhColabCargo" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Data Admissão</label>
                        <input type="date" id="rhColabAdmissao" class="form-control">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="form-group">
                        <label>Salário Base (R$)</label>
                        <input type="number" step="0.01" id="rhColabSalario" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Jornada Semanal (h)</label>
                        <input type="number" id="rhColabJornada" class="form-control" value="44">
                    </div>
                </div>
            </div>

            <!-- Campos PJ -->
            <div id="rhCamposPJ" style="display:none">
                <hr style="margin:16px 0;border:none;border-top:1px solid #e2e8f0">
                <h4 style="font-size:14px;color:var(--gray-700);margin-bottom:12px"><i class="fas fa-file-invoice" style="color:#D97706"></i> Dados PJ</h4>
                <div class="rh-form-row">
                    <div class="form-group">
                        <label>CNPJ</label>
                        <input type="text" id="rhColabCNPJ" class="form-control" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="form-group">
                        <label>Razão Social</label>
                        <input type="text" id="rhColabRazao" class="form-control">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="form-group">
                        <label>Valor Mensal (R$)</label>
                        <input type="number" step="0.01" id="rhColabValorMensal" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Valor Hora (R$)</label>
                        <input type="number" step="0.01" id="rhColabValorHora" class="form-control">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="form-group">
                        <label>Banco</label>
                        <input type="text" id="rhColabBanco" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Agência</label>
                        <input type="text" id="rhColabAgencia" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Conta</label>
                        <input type="text" id="rhColabConta" class="form-control">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="form-group">
                        <label>PIX</label>
                        <input type="text" id="rhColabPix" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Inscrição Municipal</label>
                        <input type="text" id="rhColabIM" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="rh-modal-footer">
            <button class="btn btn-secondary" onclick="rhFecharModal('rhModalColaborador')">Cancelar</button>
            <button class="btn btn-primary" onclick="rhSalvarColaborador()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal: Lançamento -->
<div class="rh-modal-overlay" id="rhModalLancamento">
    <div class="rh-modal" style="max-width:800px">
        <div class="rh-modal-header">
            <h2 id="rhModalLancTitulo">Novo Lançamento</h2>
            <button class="rh-modal-close" onclick="rhFecharModal('rhModalLancamento')">&times;</button>
        </div>
        <div class="rh-modal-body">
            <input type="hidden" id="rhLancId">
            <div class="rh-form-row">
                <div class="form-group" style="flex:2">
                    <label>Colaborador *</label>
                    <select id="rhLancColaborador" class="form-control" onchange="rhPreencherDados()">
                        <option value="">Selecionar...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Competência *</label>
                    <input type="month" id="rhLancCompetencia" class="form-control" value="<?= date('Y-m') ?>">
                </div>
            </div>
            <hr style="margin:12px 0;border:none;border-top:1px solid #e2e8f0">
            <h4 style="font-size:14px;color:var(--gray-700);margin-bottom:12px"><i class="fas fa-plus-circle" style="color:#059669"></i> Proventos</h4>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>Salário Bruto / Valor NF (R$)</label>
                    <input type="number" step="0.01" id="rhLancBruto" class="form-control" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>Horas Extras (R$)</label>
                    <input type="number" step="0.01" id="rhLancHE" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>Bônus (R$)</label>
                    <input type="number" step="0.01" id="rhLancBonus" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
            </div>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>Adicional Noturno (R$)</label>
                    <input type="number" step="0.01" id="rhLancAdicNoturno" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>Comissão (R$)</label>
                    <input type="number" step="0.01" id="rhLancComissao" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>Outros Adicionais (R$)</label>
                    <input type="number" step="0.01" id="rhLancOutrosAd" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
            </div>
            <hr style="margin:12px 0;border:none;border-top:1px solid #e2e8f0">
            <h4 style="font-size:14px;color:var(--gray-700);margin-bottom:12px"><i class="fas fa-minus-circle" style="color:#DC2626"></i> Descontos</h4>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>INSS (R$)</label>
                    <input type="number" step="0.01" id="rhLancINSS" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>IRRF (R$)</label>
                    <input type="number" step="0.01" id="rhLancIRRF" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>FGTS (R$)</label>
                    <input type="number" step="0.01" id="rhLancFGTS" class="form-control" value="0">
                </div>
            </div>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>Vale Transporte (R$)</label>
                    <input type="number" step="0.01" id="rhLancVT" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>Vale Alimentação (R$)</label>
                    <input type="number" step="0.01" id="rhLancVA" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>Plano de Saúde (R$)</label>
                    <input type="number" step="0.01" id="rhLancPS" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
            </div>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>Faltas/Desconto (R$)</label>
                    <input type="number" step="0.01" id="rhLancFaltas" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
                <div class="form-group">
                    <label>Outros Descontos (R$)</label>
                    <input type="number" step="0.01" id="rhLancOutrosDesc" class="form-control" value="0" oninput="rhRecalcular()">
                </div>
            </div>
            <hr style="margin:12px 0;border:none;border-top:1px solid #e2e8f0">
            <div class="rh-form-row" style="align-items:center">
                <div class="form-group">
                    <label>Total Descontos</label>
                    <div style="font-size:20px;font-weight:700;color:#DC2626" id="rhLancTotalDesc">R$ 0,00</div>
                </div>
                <div class="form-group">
                    <label>Total Proventos</label>
                    <div style="font-size:20px;font-weight:700;color:#059669" id="rhLancTotalProv">R$ 0,00</div>
                </div>
                <div class="form-group">
                    <label><strong>Líquido a Receber</strong></label>
                    <div style="font-size:24px;font-weight:700;color:var(--primary)" id="rhLancLiquido">R$ 0,00</div>
                </div>
            </div>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>Observações</label>
                    <textarea id="rhLancObs" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="rh-modal-footer">
            <button class="btn btn-secondary" onclick="rhFecharModal('rhModalLancamento')">Cancelar</button>
            <button class="btn btn-primary" onclick="rhSalvarLancamento()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal: Gerar Folha -->
<div class="rh-modal-overlay" id="rhModalGerar">
    <div class="rh-modal" style="max-width:480px">
        <div class="rh-modal-header">
            <h2>Gerar Folha em Lote</h2>
            <button class="rh-modal-close" onclick="rhFecharModal('rhModalGerar')">&times;</button>
        </div>
        <div class="rh-modal-body">
            <p style="color:var(--gray-600);margin-bottom:16px">Gera lançamentos automáticos para todos os colaboradores ativos do tipo selecionado.</p>
            <div class="rh-form-row">
                <div class="form-group">
                    <label>Competência *</label>
                    <input type="month" id="rhGerarComp" class="form-control" value="<?= date('Y-m') ?>">
                </div>
                <div class="form-group">
                    <label>Tipo *</label>
                    <select id="rhGerarTipo" class="form-control">
                        <option value="clt">CLT</option>
                        <option value="pj">PJ</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="rh-modal-footer">
            <button class="btn btn-secondary" onclick="rhFecharModal('rhModalGerar')">Cancelar</button>
            <button class="btn btn-primary" onclick="rhConfirmarGerar()"><i class="fas fa-cogs"></i> Gerar</button>
        </div>
    </div>
</div>

<script>
const RH_API = '<?= BASE_URL ?>/api/folha.php';
const podeEscrever = <?= $podeEscrever ? 'true' : 'false' ?>;
const podeAdmin = <?= $podeAdmin ? 'true' : 'false' ?>;

function showToast(msg, type = 'info') {
    const typeMap = { error: 'danger', success: 'success', warning: 'warning', info: 'info' };
    if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) {
        HelpDesk.toast(msg, typeMap[type] || type);
    }
}

function rhFormatMoney(v) {
    return 'R$ ' + (parseFloat(v) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function rhFormatDate(d) {
    if (!d) return '-';
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('pt-BR');
}

// ── Tabs ──
function rhTab(btn) {
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    const tabId = btn.dataset.tab;
    document.getElementById(tabId).classList.add('active');
    if (tabId === 'rh-folha') rhCarregarLancamentos();
    if (tabId === 'rh-resumo') rhCarregarResumo();
}

// ── Modals ──
function rhAbrirModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function rhFecharModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
document.querySelectorAll('.rh-modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) rhFecharModal(this.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.rh-modal-overlay.active').forEach(m => rhFecharModal(m.id));
});

// ── Toggle campos CLT/PJ ──
function rhToggleTipoCampos() {
    const tipo = document.getElementById('rhColabTipo').value;
    document.getElementById('rhCamposCLT').style.display = tipo === 'clt' ? '' : 'none';
    document.getElementById('rhCamposPJ').style.display = tipo === 'pj' ? '' : 'none';
}

// ── KPIs ──
async function rhCarregarStats() {
    try {
        const r = await fetch(RH_API + '?action=stats');
        const j = await r.json();
        if (j.success) {
            const d = j.data;
            document.getElementById('kpiTotalColab').textContent = (d.total_clt || 0) + (d.total_pj || 0);
            document.getElementById('kpiClt').textContent = d.total_clt || 0;
            document.getElementById('kpiPj').textContent = d.total_pj || 0;
            document.getElementById('kpiFolhaMes').textContent = rhFormatMoney(d.folha_mes || 0);
        }
    } catch(e) { console.error(e); }
}

// ── Colaboradores ──
async function rhCarregarColaboradores() {
    const busca = document.getElementById('rhBuscaColab').value;
    const tipo = document.getElementById('rhFiltroTipo').value;
    const status = document.getElementById('rhFiltroStatus').value;
    const params = new URLSearchParams({ action: 'colaboradores', busca, tipo_contrato: tipo, status });
    try {
        const r = await fetch(RH_API + '?' + params);
        const j = await r.json();
        const tbody = document.getElementById('rhColabBody');
        if (!j.success || !j.data.length) {
            tbody.innerHTML = `<tr><td colspan="7"><div class="rh-empty"><i class="fas fa-users"></i><p>Nenhum colaborador encontrado</p></div></td></tr>`;
            return;
        }
        tbody.innerHTML = j.data.map(c => {
            const initials = c.nome_completo.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
            const bgColor = c.tipo_contrato === 'clt' ? '#3B82F6' : '#D97706';
            const valor = c.tipo_contrato === 'clt' ? rhFormatMoney(c.salario_base) : rhFormatMoney(c.valor_mensal || c.valor_hora || 0);
            const sub = c.tipo_contrato === 'clt' ? (c.cpf || '') : (c.cnpj || c.razao_social || '');
            const cargo = c.cargo || c.razao_social || '-';
            return `<tr>
                <td><div class="rh-colab-cell"><div class="rh-avatar" style="background:${bgColor}">${initials}</div><div class="rh-colab-info"><span class="rh-colab-name">${c.nome_completo}</span><span class="rh-colab-sub">${sub}</span></div></div></td>
                <td><span class="rh-badge rh-badge-${c.tipo_contrato}">${c.tipo_contrato.toUpperCase()}</span></td>
                <td>${cargo}</td>
                <td class="rh-valor rh-valor-bruto">${valor}</td>
                <td>${rhFormatDate(c.data_admissao)}</td>
                <td><span class="rh-badge rh-badge-${c.status}">${c.status.charAt(0).toUpperCase() + c.status.slice(1)}</span></td>
                <td>
                    ${podeEscrever ? `<button class="rh-action-btn" title="Editar" onclick="rhEditarColaborador(${c.id})"><i class="fas fa-edit"></i></button>` : ''}
                    ${podeEscrever ? `<button class="rh-action-btn" title="Novo Lançamento" onclick="rhNovoLancamento(${c.id})"><i class="fas fa-plus-circle"></i></button>` : ''}
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

function rhAbrirModalColaborador(dados = null) {
    document.getElementById('rhColabId').value = dados ? dados.id : '';
    document.getElementById('rhModalColabTitulo').textContent = dados ? 'Editar Colaborador' : 'Novo Colaborador';
    const campos = ['Nome','CPF','RG','Nascimento','Email','Telefone','Endereco','Departamento',
                     'CTPS','PIS','Cargo','Admissao','Salario','Jornada',
                     'CNPJ','Razao','ValorMensal','ValorHora','Banco','Agencia','Conta','Pix','IM'];
    campos.forEach(c => {
        const el = document.getElementById('rhColab' + c);
        if (el) el.value = '';
    });
    if (dados) {
        document.getElementById('rhColabNome').value = dados.nome_completo || '';
        document.getElementById('rhColabTipo').value = dados.tipo_contrato || 'clt';
        document.getElementById('rhColabCPF').value = dados.cpf || '';
        document.getElementById('rhColabRG').value = dados.rg || '';
        document.getElementById('rhColabNascimento').value = dados.data_nascimento || '';
        document.getElementById('rhColabEmail').value = dados.email || '';
        document.getElementById('rhColabTelefone').value = dados.telefone || '';
        document.getElementById('rhColabEndereco').value = dados.endereco || '';
        document.getElementById('rhColabDepartamento').value = dados.departamento_id || '';
        document.getElementById('rhColabCTPS').value = dados.ctps || '';
        document.getElementById('rhColabPIS').value = dados.pis_pasep || '';
        document.getElementById('rhColabCargo').value = dados.cargo || '';
        document.getElementById('rhColabAdmissao').value = dados.data_admissao || '';
        document.getElementById('rhColabSalario').value = dados.salario_base || '';
        document.getElementById('rhColabJornada').value = dados.jornada_semanal || 44;
        document.getElementById('rhColabCNPJ').value = dados.cnpj || '';
        document.getElementById('rhColabRazao').value = dados.razao_social || '';
        document.getElementById('rhColabValorMensal').value = dados.valor_mensal || '';
        document.getElementById('rhColabValorHora').value = dados.valor_hora || '';
        document.getElementById('rhColabBanco').value = dados.banco || '';
        document.getElementById('rhColabAgencia').value = dados.agencia || '';
        document.getElementById('rhColabConta').value = dados.conta || '';
        document.getElementById('rhColabPix').value = dados.pix || '';
        document.getElementById('rhColabIM').value = dados.inscricao_municipal || '';
    } else {
        document.getElementById('rhColabTipo').value = 'clt';
        document.getElementById('rhColabJornada').value = 44;
    }
    rhToggleTipoCampos();
    rhAbrirModal('rhModalColaborador');
}

async function rhEditarColaborador(id) {
    try {
        const r = await fetch(RH_API + '?action=colaborador&id=' + id);
        const j = await r.json();
        if (j.success) rhAbrirModalColaborador(j.data);
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao carregar', 'error'); }
}

async function rhSalvarColaborador() {
    const id = document.getElementById('rhColabId').value;
    const tipo = document.getElementById('rhColabTipo').value;
    const payload = {
        action: id ? 'atualizar_colaborador' : 'criar_colaborador',
        nome_completo: document.getElementById('rhColabNome').value,
        tipo_contrato: tipo,
        cpf: document.getElementById('rhColabCPF').value,
        rg: document.getElementById('rhColabRG').value,
        data_nascimento: document.getElementById('rhColabNascimento').value,
        email: document.getElementById('rhColabEmail').value,
        telefone: document.getElementById('rhColabTelefone').value,
        endereco: document.getElementById('rhColabEndereco').value,
        departamento_id: document.getElementById('rhColabDepartamento').value
    };
    if (id) payload.id = id;
    if (tipo === 'clt') {
        payload.ctps = document.getElementById('rhColabCTPS').value;
        payload.pis_pasep = document.getElementById('rhColabPIS').value;
        payload.cargo = document.getElementById('rhColabCargo').value;
        payload.data_admissao = document.getElementById('rhColabAdmissao').value;
        payload.salario_base = document.getElementById('rhColabSalario').value;
        payload.jornada_semanal = document.getElementById('rhColabJornada').value;
    } else {
        payload.cnpj = document.getElementById('rhColabCNPJ').value;
        payload.razao_social = document.getElementById('rhColabRazao').value;
        payload.valor_mensal = document.getElementById('rhColabValorMensal').value;
        payload.valor_hora = document.getElementById('rhColabValorHora').value;
        payload.banco = document.getElementById('rhColabBanco').value;
        payload.agencia = document.getElementById('rhColabAgencia').value;
        payload.conta = document.getElementById('rhColabConta').value;
        payload.pix = document.getElementById('rhColabPix').value;
        payload.inscricao_municipal = document.getElementById('rhColabIM').value;
    }
    if (!payload.nome_completo) { showToast('Nome é obrigatório', 'warning'); return; }
    try {
        const r = await fetch(RH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const j = await r.json();
        if (j.success) {
            rhFecharModal('rhModalColaborador');
            rhCarregarColaboradores();
            rhCarregarStats();
            showToast(id ? 'Colaborador atualizado!' : 'Colaborador cadastrado!', 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao salvar', 'error'); }
}

// ── Lançamentos ──
async function rhCarregarLancamentos() {
    const comp = document.getElementById('rhCompetencia').value;
    const tipo = document.getElementById('rhFiltroTipoFolha').value;
    const status = document.getElementById('rhFiltroStatusFolha').value;
    const params = new URLSearchParams({ action: 'lancamentos', competencia: comp, tipo_contrato: tipo, status });
    try {
        const r = await fetch(RH_API + '?' + params);
        const j = await r.json();
        const tbody = document.getElementById('rhFolhaBody');
        if (!j.success || !j.data.length) {
            tbody.innerHTML = `<tr><td colspan="8"><div class="rh-empty"><i class="fas fa-file-invoice-dollar"></i><p>Nenhum lançamento encontrado</p></div></td></tr>`;
            return;
        }
        tbody.innerHTML = j.data.map(l => {
            const descontos = parseFloat(l.inss || 0) + parseFloat(l.irrf || 0) + parseFloat(l.vale_transporte || 0) +
                              parseFloat(l.vale_alimentacao || 0) + parseFloat(l.plano_saude || 0) +
                              parseFloat(l.faltas_desconto || 0) + parseFloat(l.outros_descontos || 0);
            const proventos = parseFloat(l.salario_bruto || 0) + parseFloat(l.horas_extras || 0) +
                              parseFloat(l.adicional_noturno || 0) + parseFloat(l.comissao || 0) +
                              parseFloat(l.bonus || 0) + parseFloat(l.outros_adicionais || 0);
            const liquido = proventos - descontos;
            const statusBadge = {
                'rascunho': 'rh-badge-rascunho',
                'aprovado': 'rh-badge-aprovado',
                'pago': 'rh-badge-pago',
                'cancelado': 'rh-badge-cancelado'
            }[l.status] || 'rh-badge-rascunho';

            return `<tr>
                <td><strong>${l.colaborador_nome || '-'}</strong></td>
                <td><span class="rh-badge rh-badge-${l.tipo_contrato || 'clt'}">${(l.tipo_contrato || 'clt').toUpperCase()}</span></td>
                <td>${l.competencia}</td>
                <td class="rh-valor rh-valor-bruto">${rhFormatMoney(proventos)}</td>
                <td class="rh-valor rh-valor-desc">${rhFormatMoney(descontos)}</td>
                <td class="rh-valor rh-valor-liquido">${rhFormatMoney(liquido)}</td>
                <td><span class="rh-badge ${statusBadge}">${l.status.charAt(0).toUpperCase() + l.status.slice(1)}</span></td>
                <td>
                    ${podeEscrever && l.status === 'rascunho' ? `<button class="rh-action-btn" title="Editar" onclick="rhEditarLancamento(${l.id})"><i class="fas fa-edit"></i></button>` : ''}
                    ${podeAdmin && l.status === 'rascunho' ? `<button class="rh-action-btn" title="Aprovar" onclick="rhAprovar(${l.id})"><i class="fas fa-check"></i></button>` : ''}
                    ${podeAdmin && l.status === 'aprovado' ? `<button class="rh-action-btn" title="Marcar Pago" onclick="rhMarcarPago(${l.id})"><i class="fas fa-dollar-sign"></i></button>` : ''}
                    ${podeEscrever && l.status === 'rascunho' ? `<button class="rh-action-btn danger" title="Excluir" onclick="rhDeletarLancamento(${l.id})"><i class="fas fa-trash"></i></button>` : ''}
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

let colabCache = [];
async function rhCarregarSelectColaboradores() {
    try {
        const r = await fetch(RH_API + '?action=colaboradores&status=ativo');
        const j = await r.json();
        colabCache = j.data || [];
        const sel = document.getElementById('rhLancColaborador');
        sel.innerHTML = '<option value="">Selecionar...</option>' +
            colabCache.map(c => `<option value="${c.id}" data-tipo="${c.tipo_contrato}" data-salario="${c.salario_base || c.valor_mensal || 0}">${c.nome_completo} (${c.tipo_contrato.toUpperCase()})</option>`).join('');
    } catch(e) {}
}

function rhPreencherDados() {
    const sel = document.getElementById('rhLancColaborador');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.salario) {
        document.getElementById('rhLancBruto').value = opt.dataset.salario;
        rhRecalcular();
    }
}

function rhNovoLancamento(colabId = null) {
    document.getElementById('rhLancId').value = '';
    document.getElementById('rhModalLancTitulo').textContent = 'Novo Lançamento';
    ['HE','Bonus','AdicNoturno','Comissao','OutrosAd','INSS','IRRF','FGTS','VT','VA','PS','Faltas','OutrosDesc'].forEach(f => {
        document.getElementById('rhLanc' + f).value = 0;
    });
    document.getElementById('rhLancBruto').value = '';
    document.getElementById('rhLancObs').value = '';
    document.getElementById('rhLancCompetencia').value = document.getElementById('rhCompetencia')?.value || new Date().toISOString().slice(0, 7);
    rhCarregarSelectColaboradores().then(() => {
        if (colabId) {
            document.getElementById('rhLancColaborador').value = colabId;
            rhPreencherDados();
        }
    });
    rhRecalcular();
    rhAbrirModal('rhModalLancamento');
}

async function rhEditarLancamento(id) {
    try {
        const r = await fetch(RH_API + '?action=lancamento&id=' + id);
        const j = await r.json();
        if (!j.success) { showToast(j.error, 'error'); return; }
        const d = j.data;
        document.getElementById('rhLancId').value = d.id;
        document.getElementById('rhModalLancTitulo').textContent = 'Editar Lançamento';
        await rhCarregarSelectColaboradores();
        document.getElementById('rhLancColaborador').value = d.colaborador_id;
        document.getElementById('rhLancCompetencia').value = d.competencia;
        document.getElementById('rhLancBruto').value = d.salario_bruto;
        document.getElementById('rhLancHE').value = d.horas_extras || 0;
        document.getElementById('rhLancBonus').value = d.bonus || 0;
        document.getElementById('rhLancAdicNoturno').value = d.adicional_noturno || 0;
        document.getElementById('rhLancComissao').value = d.comissao || 0;
        document.getElementById('rhLancOutrosAd').value = d.outros_adicionais || 0;
        document.getElementById('rhLancINSS').value = d.inss || 0;
        document.getElementById('rhLancIRRF').value = d.irrf || 0;
        document.getElementById('rhLancFGTS').value = d.fgts || 0;
        document.getElementById('rhLancVT').value = d.vale_transporte || 0;
        document.getElementById('rhLancVA').value = d.vale_alimentacao || 0;
        document.getElementById('rhLancPS').value = d.plano_saude || 0;
        document.getElementById('rhLancFaltas').value = d.faltas_desconto || 0;
        document.getElementById('rhLancOutrosDesc').value = d.outros_descontos || 0;
        document.getElementById('rhLancObs').value = d.observacoes || '';
        rhRecalcular();
        rhAbrirModal('rhModalLancamento');
    } catch(e) { showToast('Erro ao carregar', 'error'); }
}

function rhRecalcular() {
    const bruto = parseFloat(document.getElementById('rhLancBruto').value) || 0;
    const he = parseFloat(document.getElementById('rhLancHE').value) || 0;
    const bonus = parseFloat(document.getElementById('rhLancBonus').value) || 0;
    const adicNoturno = parseFloat(document.getElementById('rhLancAdicNoturno').value) || 0;
    const comissao = parseFloat(document.getElementById('rhLancComissao').value) || 0;
    const outrosAd = parseFloat(document.getElementById('rhLancOutrosAd').value) || 0;
    const totalProv = bruto + he + bonus + adicNoturno + comissao + outrosAd;

    const inss = parseFloat(document.getElementById('rhLancINSS').value) || 0;
    const irrf = parseFloat(document.getElementById('rhLancIRRF').value) || 0;
    const vt = parseFloat(document.getElementById('rhLancVT').value) || 0;
    const va = parseFloat(document.getElementById('rhLancVA').value) || 0;
    const ps = parseFloat(document.getElementById('rhLancPS').value) || 0;
    const faltas = parseFloat(document.getElementById('rhLancFaltas').value) || 0;
    const outrosDesc = parseFloat(document.getElementById('rhLancOutrosDesc').value) || 0;
    const totalDesc = inss + irrf + vt + va + ps + faltas + outrosDesc;

    document.getElementById('rhLancTotalProv').textContent = rhFormatMoney(totalProv);
    document.getElementById('rhLancTotalDesc').textContent = rhFormatMoney(totalDesc);
    document.getElementById('rhLancLiquido').textContent = rhFormatMoney(totalProv - totalDesc);
}

async function rhSalvarLancamento() {
    const id = document.getElementById('rhLancId').value;
    const payload = {
        action: id ? 'atualizar_lancamento' : 'criar_lancamento',
        colaborador_id: document.getElementById('rhLancColaborador').value,
        competencia: document.getElementById('rhLancCompetencia').value,
        salario_bruto: document.getElementById('rhLancBruto').value,
        horas_extras: document.getElementById('rhLancHE').value,
        bonus: document.getElementById('rhLancBonus').value,
        adicional_noturno: document.getElementById('rhLancAdicNoturno').value,
        comissao: document.getElementById('rhLancComissao').value,
        outros_adicionais: document.getElementById('rhLancOutrosAd').value,
        inss: document.getElementById('rhLancINSS').value,
        irrf: document.getElementById('rhLancIRRF').value,
        fgts: document.getElementById('rhLancFGTS').value,
        vale_transporte: document.getElementById('rhLancVT').value,
        vale_alimentacao: document.getElementById('rhLancVA').value,
        plano_saude: document.getElementById('rhLancPS').value,
        faltas_desconto: document.getElementById('rhLancFaltas').value,
        outros_descontos: document.getElementById('rhLancOutrosDesc').value,
        observacoes: document.getElementById('rhLancObs').value
    };
    if (id) payload.id = id;
    try {
        const r = await fetch(RH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const j = await r.json();
        if (j.success) {
            rhFecharModal('rhModalLancamento');
            rhCarregarLancamentos();
            rhCarregarStats();
            showToast(id ? 'Lançamento atualizado!' : 'Lançamento criado!', 'success');
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao salvar', 'error'); }
}

async function rhAprovar(id) {
    if (!confirm('Aprovar este lançamento?')) return;
    try {
        const r = await fetch(RH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'aprovar', id })
        });
        const j = await r.json();
        if (j.success) { showToast('Lançamento aprovado!', 'success'); rhCarregarLancamentos(); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

async function rhMarcarPago(id) {
    if (!confirm('Marcar como pago?')) return;
    try {
        const r = await fetch(RH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'marcar_pago', id, data_pagamento: new Date().toISOString().slice(0, 10), forma_pagamento: 'pix' })
        });
        const j = await r.json();
        if (j.success) { showToast('Marcado como pago!', 'success'); rhCarregarLancamentos(); rhCarregarStats(); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

async function rhDeletarLancamento(id) {
    if (!confirm('Excluir este lançamento?')) return;
    try {
        const r = await fetch(RH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'deletar_lancamento', id })
        });
        const j = await r.json();
        if (j.success) { showToast('Lançamento excluído!', 'success'); rhCarregarLancamentos(); rhCarregarStats(); }
        else showToast(j.error, 'error');
    } catch(e) { showToast('Erro', 'error'); }
}

// ── Gerar Folha ──
function rhGerarFolha() {
    rhAbrirModal('rhModalGerar');
}

async function rhConfirmarGerar() {
    const comp = document.getElementById('rhGerarComp').value;
    const tipo = document.getElementById('rhGerarTipo').value;
    if (!comp) { showToast('Selecione a competência', 'warning'); return; }
    try {
        const r = await fetch(RH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'gerar_folha', competencia: comp, tipo_contrato: tipo })
        });
        const j = await r.json();
        if (j.success) {
            rhFecharModal('rhModalGerar');
            showToast(`${j.data.gerados} lançamentos gerados!`, 'success');
            rhCarregarLancamentos();
            rhCarregarStats();
        } else showToast(j.error, 'error');
    } catch(e) { showToast('Erro ao gerar', 'error'); }
}

// ── Resumo ──
async function rhCarregarResumo() {
    const comp = document.getElementById('rhCompResumo').value;
    try {
        const r = await fetch(RH_API + '?action=resumo&competencia=' + comp);
        const j = await r.json();
        if (!j.success) return;
        const d = j.data;
        const container = document.getElementById('rhResumoCards');
        container.innerHTML = `
            <div class="rh-comp-card">
                <h4><i class="fas fa-id-card" style="color:#3B82F6"></i> CLT</h4>
                <div class="rh-comp-row"><span>Colaboradores</span><span>${d.total_clt || 0}</span></div>
                <div class="rh-comp-row"><span>Salário Bruto</span><span>${rhFormatMoney(d.bruto_clt)}</span></div>
                <div class="rh-comp-row"><span>Líquido</span><span style="color:#059669">${rhFormatMoney(d.liquido_clt)}</span></div>
                <div class="rh-comp-row"><span>FGTS</span><span>${rhFormatMoney(d.fgts_total)}</span></div>
                <div class="rh-comp-row rh-comp-total"><span><strong>Custo Total CLT</strong></span><span><strong>${rhFormatMoney(parseFloat(d.bruto_clt || 0) + parseFloat(d.fgts_total || 0))}</strong></span></div>
            </div>
            <div class="rh-comp-card">
                <h4><i class="fas fa-file-invoice" style="color:#D97706"></i> PJ</h4>
                <div class="rh-comp-row"><span>Prestadores</span><span>${d.total_pj || 0}</span></div>
                <div class="rh-comp-row"><span>Total NFs</span><span>${rhFormatMoney(d.bruto_pj)}</span></div>
                <div class="rh-comp-row rh-comp-total"><span><strong>Custo Total PJ</strong></span><span><strong>${rhFormatMoney(d.bruto_pj)}</strong></span></div>
            </div>
            <div class="rh-comp-card">
                <h4><i class="fas fa-chart-pie" style="color:#7C3AED"></i> Consolidado</h4>
                <div class="rh-comp-row"><span>Custo Total</span><span><strong>${rhFormatMoney(parseFloat(d.bruto_clt || 0) + parseFloat(d.fgts_total || 0) + parseFloat(d.bruto_pj || 0))}</strong></span></div>
                <div class="rh-comp-row"><span>Rascunho</span><span>${d.status_rascunho || 0}</span></div>
                <div class="rh-comp-row"><span>Aprovados</span><span style="color:#3B82F6">${d.status_aprovado || 0}</span></div>
                <div class="rh-comp-row"><span>Pagos</span><span style="color:#059669">${d.status_pago || 0}</span></div>
                <div class="rh-comp-row"><span>Cancelados</span><span style="color:#DC2626">${d.status_cancelado || 0}</span></div>
            </div>
        `;
    } catch(e) { console.error(e); }
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
    rhCarregarStats();
    rhCarregarColaboradores();
});
</script>
