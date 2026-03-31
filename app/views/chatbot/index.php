<?php /** View: Chatbot Inteligente */ $user = currentUser(); ?>

<style>
/* ===== CHATBOT PAGE STYLES ===== */
.cb-page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:16px}
.cb-page-header-left{display:flex;align-items:center;gap:16px}
.cb-page-header-icon{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#6366F1 0%,#8B5CF6 50%,#A78BFA 100%);display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;box-shadow:0 8px 24px rgba(99,102,241,0.3);flex-shrink:0}
.cb-page-header h1{font-size:24px;font-weight:800;margin:0;letter-spacing:-0.3px}
.cb-page-header p{font-size:13px;margin:2px 0 0;opacity:0.6}

/* KPI Grid */
.cb-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:14px;margin-bottom:28px}
.cb-kpi{border-radius:14px;padding:18px 16px;display:flex;align-items:center;gap:14px;border:1px solid rgba(0,0,0,0.06);transition:all 0.2s ease;position:relative;overflow:hidden;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.04)}
.cb-kpi:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.cb-kpi-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.cb-kpi-body{display:flex;flex-direction:column;min-width:0}
.cb-kpi-value{font-size:22px;font-weight:800;line-height:1.1;letter-spacing:-0.5px}
.cb-kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;opacity:0.5;margin-top:2px}
.cb-kpi-status{font-size:13px;font-weight:700;display:flex;align-items:center;gap:5px}
.cb-kpi-status .dot{width:8px;height:8px;border-radius:50%;display:inline-block;animation:cbDotPulse 2s infinite}
@keyframes cbDotPulse{0%,100%{opacity:1}50%{opacity:0.4}}
.cb-kpi.clickable{cursor:pointer}

/* Color variants */
.cb-kpi-blue .cb-kpi-icon{background:rgba(59,130,246,0.1);color:#3B82F6}
.cb-kpi-green .cb-kpi-icon{background:rgba(16,185,129,0.1);color:#10B981}
.cb-kpi-purple .cb-kpi-icon{background:rgba(139,92,246,0.1);color:#8B5CF6}
.cb-kpi-cyan .cb-kpi-icon{background:rgba(6,182,212,0.1);color:#06B6D4}
.cb-kpi-red .cb-kpi-icon{background:rgba(239,68,68,0.1);color:#EF4444}
.cb-kpi-indigo .cb-kpi-icon{background:rgba(99,102,241,0.1);color:#6366F1}
.cb-kpi-emerald .cb-kpi-icon{background:rgba(16,185,129,0.1);color:#10B981}

/* Tabs */
.cb-tabs{display:flex;gap:4px;border-bottom:2px solid var(--gray-200);margin-bottom:24px;overflow-x:auto;scrollbar-width:none;padding-bottom:0}
.cb-tabs::-webkit-scrollbar{display:none}
.cb-tab{display:inline-flex;align-items:center;gap:7px;padding:10px 16px;border:none;background:none;color:var(--gray-500);font-size:13px;font-weight:600;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all 0.2s;white-space:nowrap;border-radius:8px 8px 0 0}
.cb-tab:hover{color:var(--gray-700);background:var(--gray-50)}
.cb-tab.active{color:#6366F1;border-bottom-color:#6366F1;background:rgba(99,102,241,0.04)}
.cb-tab i{font-size:13px}
.cb-tab .badge{font-size:9px;padding:2px 6px;border-radius:10px;vertical-align:middle}
.cb-tab-content{display:none}
.cb-tab-content.active{display:block;animation:cbFadeIn 0.25s ease}
@keyframes cbFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

/* Section cards */
.cb-section{background:#fff;border-radius:14px;border:1px solid rgba(0,0,0,0.06);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:20px}
.cb-section-header{padding:16px 20px;border-bottom:1px solid rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:space-between;gap:12px}
.cb-section-header h3{font-size:15px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
.cb-section-header h3 i{font-size:14px;opacity:0.5}
.cb-section-body{padding:20px}

/* Chat area */
.cb-chat-area{border-radius:12px;padding:20px;min-height:400px;max-height:500px;overflow-y:auto;background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%)}
.cb-chat-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;min-height:350px;opacity:0.4}
.cb-chat-empty i{font-size:48px;margin-bottom:12px}
.cb-chat-empty p{font-size:14px}

/* Log terminal */
.cb-log-terminal{font-family:'SFMono-Regular',Consolas,monospace;font-size:12px;background:#0d1117;color:#c9d1d9;border-radius:12px;overflow:hidden}
.cb-log-header{display:flex;align-items:center;gap:6px;padding:10px 16px;background:#161b22;border-bottom:1px solid #30363d}
.cb-log-header .dot{width:10px;height:10px;border-radius:50%}
.cb-log-body{padding:16px;max-height:500px;overflow-y:auto}

/* Ajuda steps */
.cb-step{border-radius:14px;padding:24px;border:1px solid rgba(0,0,0,0.08);margin-bottom:16px;transition:all 0.2s}
.cb-step:hover{box-shadow:0 4px 16px rgba(0,0,0,0.06)}
.cb-step-number{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:10px;font-size:14px;font-weight:800;margin-right:10px;flex-shrink:0}
.cb-step h5{display:flex;align-items:center;gap:4px;font-size:16px;font-weight:700;margin-bottom:12px}

/* Checklist */
.cb-checklist-item{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.04);font-size:13px;transition:background 0.15s}
.cb-checklist-item:hover{background:rgba(0,0,0,0.02)}
.cb-checklist-item:last-child{border-bottom:none}
.cb-checklist-icon{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0}
.cb-checklist-icon.pending{background:rgba(0,0,0,0.06);color:var(--gray-400)}
.cb-checklist-icon.ok{background:rgba(16,185,129,0.1);color:#10B981}
.cb-checklist-icon.fail{background:rgba(239,68,68,0.1);color:#EF4444}

@media(max-width:768px){
    .cb-kpi-grid{grid-template-columns:repeat(2,1fr)}
    .cb-page-header{flex-direction:column;align-items:flex-start}
}
</style>

<div class="cb-page-header">
    <div class="cb-page-header-left">
        <div class="cb-page-header-icon"><i class="fas fa-robot"></i></div>
        <div>
            <h1>Chatbot Inteligente</h1>
            <p>Gerenciamento do chatbot com IA, WhatsApp, N8N e base de dados</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-outline-primary" onclick="cbRefreshStatus()">
            <i class="fas fa-sync-alt"></i> Atualizar Status
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="cb-kpi-grid" id="cbKpis">
    <div class="cb-kpi cb-kpi-blue">
        <div class="cb-kpi-icon"><i class="fas fa-headset"></i></div>
        <div class="cb-kpi-body">
            <div class="cb-kpi-value" id="kpiSessoes">-</div>
            <div class="cb-kpi-label">Sessões Ativas</div>
        </div>
    </div>
    <div class="cb-kpi cb-kpi-green">
        <div class="cb-kpi-icon"><i class="fas fa-paper-plane"></i></div>
        <div class="cb-kpi-body">
            <div class="cb-kpi-value" id="kpiMsgsHoje">-</div>
            <div class="cb-kpi-label">Msgs Hoje</div>
        </div>
    </div>
    <div class="cb-kpi cb-kpi-purple">
        <div class="cb-kpi-icon"><i class="fas fa-user-check"></i></div>
        <div class="cb-kpi-body">
            <div class="cb-kpi-value" id="kpiNumeros">-</div>
            <div class="cb-kpi-label">Nº Autorizados</div>
        </div>
    </div>
    <div class="cb-kpi cb-kpi-cyan">
        <div class="cb-kpi-icon"><i class="fas fa-database"></i></div>
        <div class="cb-kpi-body">
            <div class="cb-kpi-value" id="kpiQueries">-</div>
            <div class="cb-kpi-label">Queries SQL</div>
        </div>
    </div>
    <div class="cb-kpi cb-kpi-red clickable" onclick="document.querySelector('[data-tab=cb-logs]').click()">
        <div class="cb-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="cb-kpi-body">
            <div class="cb-kpi-value" id="kpiErros">0</div>
            <div class="cb-kpi-label">Erros Hoje</div>
        </div>
    </div>
    <div class="cb-kpi cb-kpi-indigo">
        <div class="cb-kpi-icon"><i class="fas fa-brain"></i></div>
        <div class="cb-kpi-body">
            <div id="kpiOllama" class="cb-kpi-status">-</div>
            <div class="cb-kpi-label">Ollama IA</div>
        </div>
    </div>
    <div class="cb-kpi cb-kpi-emerald">
        <div class="cb-kpi-icon"><i class="fab fa-whatsapp"></i></div>
        <div class="cb-kpi-body">
            <div id="kpiEvolution" class="cb-kpi-status">-</div>
            <div class="cb-kpi-label">WhatsApp</div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="cb-tabs">
    <button class="cb-tab active" data-tab="cb-config" onclick="cbTab(this)">
        <i class="fas fa-cog"></i> Configurações
    </button>
    <button class="cb-tab" data-tab="cb-numeros" onclick="cbTab(this)">
        <i class="fas fa-phone"></i> Números Autorizados
    </button>
    <button class="cb-tab" data-tab="cb-sessoes" onclick="cbTab(this)">
        <i class="fas fa-comments"></i> Sessões / Chat
    </button>
    <button class="cb-tab" data-tab="cb-database" onclick="cbTab(this);cbLoadFontes()">
        <i class="fas fa-database"></i> Base de Dados
    </button>
    <button class="cb-tab" data-tab="cb-n8n" onclick="cbTab(this)">
        <i class="fas fa-project-diagram"></i> N8N
    </button>
    <button class="cb-tab" data-tab="cb-logs" onclick="cbTab(this);cbLoadLogs()">
        <i class="fas fa-file-alt"></i> Logs
        <span class="badge bg-danger ms-1" id="badgeErrosHoje" style="display:none;font-size:9px">0</span>
    </button>
    <button class="cb-tab" data-tab="cb-api-logs" onclick="cbTab(this);cbLoadDSLogs()">
        <i class="fas fa-exchange-alt"></i> API Logs
        <span class="badge bg-info ms-1" id="badgeDSLogsHoje" style="display:none;font-size:9px">0</span>
    </button>
    <button class="cb-tab" data-tab="cb-teste" onclick="cbTab(this)">
        <i class="fas fa-flask"></i> Testar IA
    </button>
    <button class="cb-tab" data-tab="cb-ajuda" onclick="cbTab(this)">
        <i class="fas fa-question-circle"></i> Ajuda
    </button>
</div>

<!-- ============================================ -->
<!--  TAB: CONFIGURAÇÕES                         -->
<!-- ============================================ -->
<div class="cb-tab-content active" id="cb-config">
    <div class="row g-4">
        <!-- Geral -->
        <div class="col-md-6">
            <div class="cb-section">
                <div class="cb-section-header"><h3><i class="fas fa-sliders-h"></i> Geral</h3></div>
                <div class="cb-section-body">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Chatbot Ativo</label>
                        <select class="form-select" id="cfg_chatbot_ativo">
                            <option value="0">Desativado</option>
                            <option value="1">Ativado</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Nome do Chatbot</label>
                        <input type="text" class="form-control" id="cfg_chatbot_nome">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Mensagem de Boas-vindas</label>
                        <textarea class="form-control" id="cfg_chatbot_boas_vindas" rows="2"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Msg Não Autorizado</label>
                        <input type="text" class="form-control" id="cfg_chatbot_msg_nao_autorizado">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Msg Erro</label>
                        <input type="text" class="form-control" id="cfg_chatbot_msg_erro">
                    </div>
                </div>
            </div>
        </div>

        <!-- Horário de Atendimento -->
        <div class="col-md-6">
            <div class="cb-section">
                <div class="cb-section-header"><h3><i class="fas fa-clock"></i> Horário de Atendimento</h3></div>
                <div class="cb-section-body">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Controle de Horário</label>
                        <select class="form-control" id="cfg_chatbot_horario_ativo">
                            <option value="0">Desativado (24h)</option>
                            <option value="1">Ativado</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Início</label>
                            <input type="time" class="form-control" id="cfg_chatbot_horario_inicio" value="08:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fim</label>
                            <input type="time" class="form-control" id="cfg_chatbot_horario_fim" value="18:00">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Dias da semana (0=Dom, 1=Seg...6=Sab)</label>
                        <input type="text" class="form-control" id="cfg_chatbot_dias_semana" placeholder="1,2,3,4,5">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Msg Fora de Horário</label>
                        <input type="text" class="form-control" id="cfg_chatbot_msg_fora_horario">
                    </div>
                </div>
            </div>
        </div>

        <!-- IA / Ollama -->
        <div class="col-md-12">
            <div class="cb-section">
                <div class="cb-section-header"><h3><i class="fas fa-brain"></i> Inteligência Artificial (Ollama)</h3></div>
                <div class="cb-section-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">Modelo (vazio = padrão do sistema)</label>
                                <select class="form-control" id="cfg_chatbot_ia_modelo">
                                    <option value="">Usar padrão do sistema</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">Temperatura: <span id="tempValue">0.5</span></label>
                                <input type="range" class="form-range" id="cfg_chatbot_ia_temperatura" min="0" max="1" step="0.1" value="0.5"
                                       oninput="document.getElementById('tempValue').textContent=this.value">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">Max Tokens</label>
                                <input type="number" class="form-control" id="cfg_chatbot_ia_max_tokens" value="1024">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">Contexto Máximo (mensagens)</label>
                                <input type="number" class="form-control" id="cfg_chatbot_ia_contexto_max" value="10">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label class="form-label">System Prompt</label>
                                <textarea class="form-control" id="cfg_chatbot_ia_system_prompt" rows="10" style="font-family:monospace;font-size:13px;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 text-end">
        <button class="btn btn-primary btn-lg" onclick="cbSaveConfig()">
            <i class="fas fa-save"></i> Salvar Configurações
        </button>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: NÚMEROS AUTORIZADOS                    -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-numeros">
    <div class="cb-section">
        <div class="cb-section-header">
            <h3><i class="fas fa-phone"></i> Números Autorizados</h3>
            <button class="btn btn-sm btn-primary" onclick="cbShowAddNumero()">
                <i class="fas fa-plus"></i> Adicionar Número
            </button>
        </div>
        <div style="padding:0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Nome</th>
                            <th>Notas</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="numerosTableBody">
                        <tr><td colspan="6" class="text-center text-muted py-4">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: SESSÕES / CHAT                         -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-sessoes">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cb-section">
                <div class="cb-section-header">
                    <h3><i class="fas fa-list"></i> Sessões</h3>
                    <button class="btn btn-sm btn-outline-primary" onclick="cbLoadSessoes()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div style="padding:0;max-height:600px;overflow-y:auto" id="sessoesListContainer">
                    <div class="text-center text-muted py-4">Carregando...</div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cb-section" id="chatViewCard">
                <div class="cb-section-header">
                    <h3 id="chatViewTitle"><i class="fas fa-comments"></i> Selecione uma sessão</h3>
                    <div>
                        <button class="btn btn-sm btn-outline-danger" id="btnLimparSessao" style="display:none" onclick="cbLimparSessao()">
                            <i class="fas fa-trash"></i> Limpar
                        </button>
                    </div>
                </div>
                <div class="cb-chat-area" id="chatViewBody">
                    <div class="cb-chat-empty">
                        <i class="fas fa-comments"></i>
                        <p>Selecione uma sessão para ver o histórico</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: BASE DE DADOS (MULTI-FONTES)          -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-database">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0" style="color:var(--gray-800);font-weight:700">
                <i class="fas fa-layer-group" style="color:var(--primary)"></i> Fontes de Dados
            </h5>
            <div class="form-check form-switch ms-2">
                <input class="form-check-input" type="checkbox" id="cfg_chatbot_db_ativo_switch"
                    onchange="document.getElementById('cfg_chatbot_db_ativo').value = this.checked ? '1' : '0'; cbSaveConfig();">
                <label class="form-check-label" for="cfg_chatbot_db_ativo_switch" style="font-size:13px;color:var(--gray-500)">Ativado</label>
            </div>
            <!-- hidden select for save compat -->
            <select class="d-none" id="cfg_chatbot_db_ativo">
                <option value="0">Desativadas</option>
                <option value="1">Ativadas</option>
            </select>
        </div>
        <button class="btn btn-primary" onclick="cbShowAddFonte()">
            <i class="fas fa-plus"></i> Nova Fonte
        </button>
    </div>

    <p class="text-muted mb-4" style="font-size:13px;margin-top:-12px">
        Configure bancos de dados e APIs que o chatbot pode consultar. Todas as fontes ativas são disponibilizadas simultaneamente para a IA.
    </p>

    <!-- Lista de Fontes -->
    <div id="fontesListContainer">
        <div class="text-center py-5" style="color:var(--gray-400)">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p>Carregando fontes de dados...</p>
        </div>
    </div>

    <!-- Área de detalhes (schema/relacionamentos) da fonte selecionada -->
    <div id="fonteDetalhes" style="display:none" class="mt-4">
        <div class="cb-section">
            <div class="cb-section-header">
                <h3 id="fonteDetalhesTitulo"><i class="fas fa-project-diagram"></i> Schema da Fonte</h3>
                <div class="d-flex gap-1 align-items-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="cbLoadFonteSchema()" title="Recarregar"><i class="fas fa-sync-alt"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('fonteDetalhes').style.display='none'" title="Fechar"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div style="padding:0">
                <ul class="nav nav-tabs nav-fill px-3 pt-2" style="font-size:13px">
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#subTabFonteSchema">
                            <i class="fas fa-list"></i> Tabelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#subTabFonteDiagram">
                            <i class="fas fa-project-diagram"></i> Diagrama
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#subTabFonteRels">
                            <i class="fas fa-link"></i> Relacionamentos
                            <span class="badge bg-primary ms-1" id="badgeFonteRelCount">0</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Lista de Tabelas -->
                    <div class="tab-pane fade p-3" id="subTabFonteSchema">
                        <div id="fonteSchemaContainer">
                            <div class="text-center py-4" style="color:var(--gray-400)">Carregando schema...</div>
                        </div>
                    </div>
                    <!-- Diagrama Interativo -->
                    <div class="tab-pane fade show active p-0" id="subTabFonteDiagram">
                        <div class="db-diagram-wrapper" id="dbDiagramWrapper">
                            <!-- Toolbar (visible in fullscreen) -->
                            <div class="db-diagram-toolbar" id="dbDiagramToolbar">
                                <span><i class="fas fa-project-diagram"></i> Diagrama</span>
                                <div class="ms-auto d-flex gap-2 align-items-center">
                                    <button class="btn btn-sm" style="border-color:#30363d;color:#c9d1d9" onclick="cbDiagramZoom(-0.1)" title="Zoom -"><i class="fas fa-search-minus"></i></button>
                                    <span id="lblZoomFS" style="font-size:12px;min-width:40px;text-align:center">100%</span>
                                    <button class="btn btn-sm" style="border-color:#30363d;color:#c9d1d9" onclick="cbDiagramZoom(0.1)" title="Zoom +"><i class="fas fa-search-plus"></i></button>
                                    <button class="btn btn-sm" style="border-color:#30363d;color:#c9d1d9" onclick="cbDiagramZoom(0)" title="Reset zoom"><i class="fas fa-undo"></i></button>
                                    <span style="border-left:1px solid #30363d;height:20px;margin:0 4px"></span>
                                    <button class="btn btn-sm" style="border-color:#30363d;color:#c9d1d9" onclick="cbAutoLayoutDiagram()" title="Auto layout"><i class="fas fa-th"></i></button>
                                    <button class="btn btn-sm" id="btnLinkModeFS" style="border-color:#30363d;color:#c9d1d9" onclick="cbToggleLinkMode()" title="Modo Link"><i class="fas fa-link"></i></button>
                                    <button class="btn btn-sm" style="border-color:#30363d;color:#c9d1d9" onclick="cbToggleFullscreen()" title="Sair fullscreen"><i class="fas fa-compress"></i></button>
                                </div>
                            </div>
                            <!-- Canvas -->
                            <div class="db-diagram-canvas" id="dbDiagramCanvas">
                                <svg class="db-diagram-svg" id="dbDiagramSvg"></svg>
                            </div>
                            <!-- Empty state -->
                            <div class="db-diagram-empty" id="dbDiagramEmpty" style="display:flex">
                                <i class="fas fa-project-diagram fa-3x mb-3" style="color:var(--gray-300)"></i>
                                <p style="color:var(--gray-400)">Clique em "Schema" em uma fonte para ver o diagrama</p>
                            </div>
                            <!-- Link mode status bar -->
                            <div class="db-link-status" id="dbLinkStatus">
                                <span class="step-badge" id="linkStep1">1</span>
                                <span id="linkStatusText">Clique na coluna <strong>FK</strong> da tabela de origem</span>
                                <button class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:#fff;border:none;font-size:12px" onclick="cbCancelLinkMode()">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        </div>
                        <!-- Diagram controls bar (outside wrapper, below diagram) -->
                        <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:var(--gray-50);border-top:1px solid var(--gray-200);font-size:12px">
                            <button class="btn btn-sm btn-outline-secondary" onclick="cbDiagramZoom(-0.1)" title="Zoom -"><i class="fas fa-search-minus"></i></button>
                            <span id="lblZoom" style="min-width:40px;text-align:center;color:var(--gray-500)">100%</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="cbDiagramZoom(0.1)" title="Zoom +"><i class="fas fa-search-plus"></i></button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="cbDiagramZoom(0)" title="Reset"><i class="fas fa-undo"></i></button>
                            <span style="border-left:1px solid var(--gray-200);height:20px;margin:0 4px"></span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="cbAutoLayoutDiagram()" title="Auto layout"><i class="fas fa-th"></i> Auto Layout</button>
                            <button class="btn btn-sm btn-outline-primary" id="btnLinkMode" onclick="cbToggleLinkMode()" title="Criar relacionamento clicando nas colunas"><i class="fas fa-link"></i> Modo Link</button>
                            <div class="ms-auto">
                                <button class="btn btn-sm btn-outline-secondary" onclick="cbToggleFullscreen()" title="Tela cheia"><i class="fas fa-expand" id="btnFullscreenIcon"></i> Tela Cheia</button>
                            </div>
                        </div>
                    </div>
                    <!-- Relacionamentos -->
                    <div class="tab-pane fade p-3" id="subTabFonteRels">
                        <div class="alert alert-info py-2" style="font-size:12px">
                            <i class="fas fa-info-circle"></i>
                            Defina os relacionamentos (JOINs) entre tabelas desta fonte. A IA usará para trazer <strong>nomes</strong> ao invés de IDs.
                        </div>
                        <div class="card card-body mb-3 p-3" style="background:var(--gray-50);border:1px solid var(--gray-200)">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label mb-1" style="font-size:11px;font-weight:600;color:var(--gray-600)">Tabela.Coluna (FK)</label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select form-select-sm" id="fRelTabelaOrigem" onchange="cbOnFRelTabelaOrigemChange()">
                                            <option value="">Tabela...</option>
                                        </select>
                                        <select class="form-select form-select-sm" id="fRelColunaOrigem"><option value="">Coluna...</option></select>
                                    </div>
                                </div>
                                <div class="col-auto text-center" style="font-size:18px;color:var(--primary)"><i class="fas fa-arrow-right"></i></div>
                                <div class="col-md-5">
                                    <label class="form-label mb-1" style="font-size:11px;font-weight:600;color:var(--gray-600)">Tabela Ref.Coluna (PK)</label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select form-select-sm" id="fRelTabelaRef" onchange="cbOnFRelTabelaRefChange()">
                                            <option value="">Tabela...</option>
                                        </select>
                                        <select class="form-select form-select-sm" id="fRelColunaRef"><option value="">Coluna (PK)...</option></select>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-success" onclick="cbAddFonteRel()"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col">
                                    <label class="form-label mb-1" style="font-size:11px;color:var(--gray-500)">Coluna nome legível (ref)</label>
                                    <select class="form-select form-select-sm" id="fRelColunaDescricao"><option value="">Ex: nome, descricao...</option></select>
                                </div>
                            </div>
                        </div>
                        <div id="fonteRelListContainer" style="max-height:350px;overflow-y:auto">
                            <div class="text-center py-3" style="color:var(--gray-400)"><small>Nenhum relacionamento</small></div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button class="btn btn-sm btn-outline-danger" onclick="cbLimparFonteRels()"><i class="fas fa-trash"></i> Limpar</button>
                            <button class="btn btn-sm btn-primary" onclick="cbSalvarFonteRels()"><i class="fas fa-save"></i> Salvar Relacionamentos</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: N8N                                    -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-n8n">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="cb-section">
                <div class="cb-section-header"><h3><i class="fas fa-project-diagram"></i> Integração N8N</h3></div>
                <div class="cb-section-body">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">N8N Ativo</label>
                        <select class="form-control" id="cfg_chatbot_n8n_ativo">
                            <option value="0">Desativado</option>
                            <option value="1">Ativado</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">URL do N8N</label>
                        <input type="text" class="form-control" id="cfg_chatbot_n8n_url" placeholder="http://localhost:5678">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Webhook URL (para onde enviar eventos)</label>
                        <input type="text" class="form-control" id="cfg_chatbot_n8n_webhook_url" placeholder="https://n8n.example.com/webhook/xxx">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">API Key (opcional)</label>
                        <input type="text" class="form-control" id="cfg_chatbot_n8n_api_key">
                    </div>

                    <div class="alert alert-info mb-3" style="font-size:13px">
                        <strong><i class="fas fa-info-circle"></i> Webhook de Recepção:</strong><br>
                        Configure no N8N para enviar eventos para:<br>
                        <code id="n8nWebhookReceiveUrl"><?= rtrim(BASE_URL, '/') ?>/api/chatbot.php</code><br>
                        <small>POST com <code>{"action":"n8n_webhook","evento":"...","dados":{...}}</code></small>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="cbSaveConfig()">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                        <button class="btn btn-outline-success" onclick="cbTestN8N()">
                            <i class="fas fa-paper-plane"></i> Testar Webhook
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="cb-section">
                <div class="cb-section-header">
                    <h3><i class="fas fa-history"></i> Logs N8N</h3>
                    <button class="btn btn-sm btn-outline-primary" onclick="cbLoadN8NLogs()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div style="padding:0;max-height:500px;overflow-y:auto">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Direção</th>
                                <th>Evento</th>
                                <th>HTTP</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="n8nLogsBody">
                            <tr><td colspan="5" class="text-center text-muted py-3">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: LOGS                                    -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-logs">
    <div class="row g-4">
        <div class="col-md-12">
            <div class="cb-section">
                <div class="cb-section-header">
                    <h3><i class="fas fa-file-alt"></i> Logs do Chatbot (Webhook + IA)</h3>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="logFiltro" onchange="cbLoadLogs()" style="width:auto;font-size:12px">
                            <option value="all">Todos</option>
                            <option value="error">🔴 Erros</option>
                            <option value="warning">🟡 Avisos</option>
                            <option value="success">🟢 Sucesso</option>
                            <option value="processing">🔵 Processando</option>
                            <option value="webhook">📨 Webhook</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="cbLoadLogs()">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="cbLimparLogs()">
                            <i class="fas fa-trash"></i> Limpar
                        </button>
                    </div>
                </div>
                <div style="padding:0">
                    <!-- Resumo de erros -->
                    <div class="px-3 py-2 d-flex gap-3 align-items-center cb-log-summary" style="border-bottom:1px solid rgba(0,0,0,0.06);font-size:12px">
                        <span id="logCountTotal" class="badge bg-secondary">0 logs</span>
                        <span id="logCountErrors" class="badge bg-danger" style="display:none">0 erros</span>
                        <span id="logCountSuccess" class="badge bg-success" style="display:none">0 OK</span>
                        <span class="ms-auto text-muted"><i class="fas fa-info-circle"></i> Logs das últimas 200 ações do webhook/IA</span>
                    </div>
                    <!-- Log entries -->
                    <div id="logContainer" class="cb-log-body" style="max-height:600px;overflow-y:auto;font-family:'SFMono-Regular',Consolas,monospace;font-size:12px;background:#0d1117;color:#c9d1d9">
                        <div class="text-center py-4" style="color:#8b949e">
                            <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
                            <p>Clique em "Atualizar" para carregar os logs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Erros do banco (mensagens tipo error) -->
    <div class="row g-4 mt-2">
        <div class="col-md-12">
            <div class="cb-section">
                <div class="cb-section-header">
                    <h3><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> Erros de SQL / IA (do banco)</h3>
                    <button class="btn btn-sm btn-outline-primary" onclick="cbLoadErros()">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                </div>
                <div style="padding:0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0" style="font-size:12px">
                            <thead>
                                <tr>
                                    <th style="width:130px">Data/Hora</th>
                                    <th style="width:120px">Contato</th>
                                    <th>SQL / Erro</th>
                                </tr>
                            </thead>
                            <tbody id="errosTableBody">
                                <tr><td colspan="3" class="text-center text-muted py-3">Clique em "Atualizar" para carregar</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: API LOGS (DataSystem)                   -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-api-logs">
    <div class="row g-4">
        <div class="col-12">
            <div class="cb-section">
                <div class="cb-section-header">
                    <h3><i class="fas fa-exchange-alt"></i> Logs de Chamadas — DataSystem API</h3>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="dsLogFiltro" onchange="cbLoadDSLogs()" style="width:auto;font-size:12px">
                            <option value="all">Todos</option>
                            <option value="errors">🔴 Erros</option>
                            <option value="success">🟢 Sucesso</option>
                        </select>
                        <select class="form-select form-select-sm" id="dsLogLimite" onchange="cbLoadDSLogs()" style="width:auto;font-size:12px">
                            <option value="50">50</option>
                            <option value="100" selected>100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="cbLoadDSLogs()">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="cbLimparDSLogs()">
                            <i class="fas fa-trash"></i> Limpar
                        </button>
                    </div>
                </div>
                <!-- Stats -->
                <div class="px-3 py-2 d-flex gap-3 align-items-center flex-wrap" style="border-bottom:1px solid rgba(0,0,0,0.06);font-size:12px" id="dsLogStats">
                    <span class="badge bg-secondary" id="dsStatTotal">0 total</span>
                    <span class="badge bg-success" id="dsStatOk">0 sucesso</span>
                    <span class="badge bg-danger" id="dsStatErro">0 erros</span>
                    <span class="badge bg-info" id="dsStatHoje">0 hoje</span>
                    <span class="badge bg-warning text-dark" id="dsStatAvg">0ms avg</span>
                </div>
                <!-- Table -->
                <div style="padding:0;overflow-x:auto">
                    <table class="table table-sm table-hover mb-0" style="font-size:12px">
                        <thead style="position:sticky;top:0;background:#fff;z-index:2">
                            <tr>
                                <th style="width:50px">#</th>
                                <th style="width:140px">Data/Hora</th>
                                <th style="width:50px">Método</th>
                                <th>Endpoint</th>
                                <th style="width:55px">HTTP</th>
                                <th style="width:70px">Duração</th>
                                <th style="width:70px">Tamanho</th>
                                <th style="width:50px">Status</th>
                                <th style="width:70px">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="dsLogTableBody">
                            <tr><td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-exchange-alt fa-2x mb-2 d-block" style="opacity:0.3"></i>
                                Clique em "Atualizar" para carregar os logs
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: TESTAR IA                               -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-teste">
    <div class="row g-4">
        <div class="col-md-8">
            <div class="cb-section">
                <div class="cb-section-header">
                    <h3><i class="fas fa-flask"></i> Testar Chatbot</h3>
                    <small class="text-muted" id="testeModelInfo"></small>
                </div>
                <div class="cb-chat-area" id="testeChat">
                    <div class="cb-chat-empty">
                        <i class="fas fa-robot"></i>
                        <p>Envie uma mensagem para testar o chatbot</p>
                    </div>
                </div>
                <div style="padding:12px 20px;border-top:1px solid rgba(0,0,0,0.06)">
                    <div class="input-group">
                        <input type="text" class="form-control" id="testeMsgInput" placeholder="Digite sua mensagem..." 
                               onkeypress="if(event.key==='Enter')cbEnviarTeste()">
                        <button class="btn btn-primary" onclick="cbEnviarTeste()" id="btnEnviarTeste">
                            <i class="fas fa-paper-plane"></i> Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cb-section">
                <div class="cb-section-header"><h3><i class="fas fa-info-circle"></i> Info do Teste</h3></div>
                <div class="cb-section-body" id="testeInfo">
                    <p class="text-muted mb-2"><small>As mensagens de teste são salvas em uma sessão especial (número 0000000000).</small></p>
                    <hr>
                    <div id="testeStats">
                        <p class="mb-1"><strong>Último teste:</strong></p>
                        <p class="text-muted">Nenhum teste realizado</p>
                    </div>
                </div>
            </div>

            <div class="cb-section mt-3">
                <div class="cb-section-header"><h3><i class="fas fa-link"></i> Webhook WhatsApp</h3></div>
                <div class="cb-section-body">
                    <p class="mb-2" style="font-size:13px">Configure este URL no Evolution API como webhook:</p>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm" id="webhookUrlDisplay" readonly
                               value="<?= rtrim(FULL_BASE_URL, '/') ?>/api/chatbot-webhook.php">
                        <button class="btn btn-sm btn-outline-secondary" onclick="cbCopyWebhook()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <small class="text-muted">Evento: <code>messages.upsert</code></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: AJUDA                                   -->
<!-- ============================================ -->
<div class="cb-tab-content" id="cb-ajuda">
    <div class="row g-4">
        <div class="col-md-8">
            <div class="cb-section">
                <div class="cb-section-header" style="background:linear-gradient(135deg,#6366F1,#8B5CF6);border-radius:14px 14px 0 0">
                    <h3 style="color:#fff"><i class="fas fa-book" style="opacity:0.8"></i> Guia Completo — Como Colocar o Chatbot para Funcionar</h3>
                </div>
                <div class="cb-section-body" style="font-size:14px;line-height:1.7">

                    <!-- PASSO 1 -->
                    <div class="cb-step" style="background:rgba(59,130,246,0.04);border-color:rgba(59,130,246,0.15)">
                        <h5><span class="cb-step-number" style="background:rgba(59,130,246,0.1);color:#3B82F6">1</span> Configurar a IA (Ollama)</h5>
                        <p>O chatbot usa o <strong>Ollama</strong> como motor de inteligência artificial local. Ele deve estar rodando em um servidor acessível.</p>
                        <ol>
                            <li>Instale o Ollama no servidor: <a href="https://ollama.ai" target="_blank">https://ollama.ai</a></li>
                            <li>Baixe pelo menos um modelo:
                                <pre class="bg-dark text-light p-2 rounded mt-1 mb-2"><code>ollama pull llama3
ollama pull glm4
ollama pull mistral</code></pre>
                            </li>
                            <li>Verifique se o Ollama está rodando: <code>curl http://SEU_IP:11434/api/tags</code></li>
                            <li>No painel <strong>Configurações > IA</strong> (menu IA do Oracle X), configure:
                                <ul>
                                    <li><strong>URL do Ollama:</strong> <code>http://IP_DO_SERVIDOR:11434</code></li>
                                    <li><strong>API Key:</strong> (vazio se não configurou autenticação)</li>
                                </ul>
                            </li>
                            <li>Volte aqui na aba <strong>Configurações</strong> e selecione o <strong>Modelo de IA</strong> desejado</li>
                            <li>Configure o <strong>System Prompt</strong> — ele define a personalidade e regras do bot</li>
                        </ol>
                        <div class="alert alert-info mb-0"><i class="fas fa-lightbulb"></i> <strong>Dica:</strong> Use o modelo <code>llama3</code> ou <code>glm4</code> para melhores respostas em português.</div>
                    </div>

                    <!-- PASSO 2 -->
                    <div class="cb-step" style="background:rgba(16,185,129,0.04);border-color:rgba(16,185,129,0.15)">
                        <h5><span class="cb-step-number" style="background:rgba(16,185,129,0.1);color:#10B981">2</span> Configurar o WhatsApp (Evolution API)</h5>
                        <p>A integração com WhatsApp usa o <strong>Evolution API</strong>, que gerencia a sessão do WhatsApp.</p>
                        <ol>
                            <li>Instale e configure o Evolution API: <a href="https://doc.evolution-api.com" target="_blank">Documentação Oficial</a></li>
                            <li>Crie uma <strong>instância</strong> no Evolution API (ex: <code>minha-empresa</code>)</li>
                            <li>No painel de <strong>IA</strong> do Oracle X, configure:
                                <ul>
                                    <li><strong>Evolution API URL:</strong> <code>http://IP:8080</code></li>
                                    <li><strong>API Key do Evolution:</strong> (chave global do Evolution)</li>
                                    <li><strong>Nome da Instância:</strong> o nome que você criou (ex: <code>minha-empresa</code>)</li>
                                </ul>
                            </li>
                            <li>Configure o <strong>Webhook</strong> no Evolution API para apontar para:
                                <pre class="bg-dark text-light p-2 rounded mt-1 mb-2"><code><?= rtrim(FULL_BASE_URL, '/') ?>/api/chatbot-webhook.php</code></pre>
                            </li>
                            <li>Selecione o evento: <code>MESSAGES_UPSERT</code></li>
                            <li>Escaneie o QR Code pelo painel do Evolution para conectar o WhatsApp</li>
                        </ol>
                        <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> O número do WhatsApp conectado será o que responde as mensagens. Use um número dedicado para o bot.</div>
                    </div>

                    <!-- PASSO 3 -->
                    <div class="cb-step" style="background:rgba(249,115,22,0.04);border-color:rgba(249,115,22,0.15)">
                        <h5><span class="cb-step-number" style="background:rgba(249,115,22,0.1);color:#F97316">3</span> Autorizar Números</h5>
                        <p>Por segurança, o chatbot <strong>só responde para números autorizados</strong>.</p>
                        <ol>
                            <li>Vá na aba <strong>Números Autorizados</strong></li>
                            <li>Adicione os números no formato: <code>5511999998888</code> (código do país + DDD + número)</li>
                            <li>Cada número pode ter um <strong>nome de contato</strong> para identificação</li>
                            <li>Mensagens de números não autorizados recebem a mensagem configurada em "Msg Não Autorizado"</li>
                        </ol>
                        <div class="alert alert-info mb-0"><i class="fas fa-lightbulb"></i> <strong>Formato:</strong> Sempre use o formato internacional sem espaços, traços ou parênteses. Ex: <code>5511999998888</code></div>
                    </div>

                    <!-- PASSO 4 -->
                    <div class="cb-step" style="background:rgba(139,92,246,0.04);border-color:rgba(139,92,246,0.15)">
                        <h5><span class="cb-step-number" style="background:rgba(139,92,246,0.1);color:#8B5CF6">4</span> Conectar uma Fonte de Dados (Opcional)</h5>
                        <p>Você pode conectar o chatbot a um <strong>banco de dados</strong> ou uma <strong>API REST</strong> para que a IA consulte dados reais.</p>
                        
                        <h6 class="mt-3"><i class="fas fa-database"></i> Opção A — Banco de Dados SQL</h6>
                        <ol>
                            <li>Na aba <strong>Base de Dados</strong>, ative a fonte e selecione o tipo: <code>MySQL</code>, <code>PostgreSQL</code>, <code>SQL Server</code> ou <code>SQLite</code></li>
                            <li>Preencha host, porta, nome do banco, usuário e senha</li>
                            <li>Clique em <strong>Testar Conexão</strong> para validar</li>
                            <li>No campo <strong>Descrição</strong>, explique o que o banco contém (isso ajuda a IA)</li>
                            <li>Em <strong>Tabelas Permitidas</strong>, liste quais tabelas a IA pode consultar (vazio = todas)</li>
                            <li>A IA gerará queries <code>SELECT</code> automaticamente quando o usuário perguntar sobre dados</li>
                        </ol>
                        <div class="alert alert-secondary mb-3">
                            <strong>Portas padrão:</strong> MySQL = <code>3306</code> | PostgreSQL = <code>5432</code> | SQL Server = <code>1433</code>
                        </div>

                        <h6 class="mt-3"><i class="fas fa-plug"></i> Opção B — API REST</h6>
                        <ol>
                            <li>Na aba <strong>Base de Dados</strong>, selecione o tipo <code>API REST</code></li>
                            <li>Informe a <strong>URL Base</strong> da API (ex: <code>https://api.empresa.com/v1</code>)</li>
                            <li>Configure a <strong>autenticação</strong>:
                                <ul>
                                    <li><strong>Bearer Token:</strong> envia <code>Authorization: Bearer SEU_TOKEN</code></li>
                                    <li><strong>API Key:</strong> envia a chave em um header customizado</li>
                                    <li><strong>Basic Auth:</strong> envia usuário e senha codificados</li>
                                </ul>
                            </li>
                            <li>Configure os <strong>Endpoints</strong> no formato JSON:
                                <pre class="bg-dark text-light p-2 rounded mt-1 mb-2" style="font-size:12px"><code>[
  {
    "method": "GET",
    "path": "/clientes",
    "description": "Lista todos os clientes",
    "params": [
      {"name": "nome", "type": "string", "description": "Filtrar por nome"},
      {"name": "limite", "type": "integer", "description": "Máx de resultados"}
    ],
    "response_example": {"id": 1, "nome": "João", "email": "joao@email.com"}
  },
  {
    "method": "GET",
    "path": "/vendas",
    "description": "Lista vendas do período",
    "params": [
      {"name": "data_inicio", "type": "string", "description": "Data início (YYYY-MM-DD)"},
      {"name": "data_fim", "type": "string", "description": "Data fim (YYYY-MM-DD)"}
    ]
  }
]</code></pre>
                            </li>
                            <li>A IA gerará chamadas API automaticamente quando o usuário perguntar sobre dados</li>
                        </ol>
                    </div>

                    <!-- PASSO 5 -->
                    <div class="cb-step" style="background:rgba(245,158,11,0.04);border-color:rgba(245,158,11,0.15)">
                        <h5><span class="cb-step-number" style="background:rgba(245,158,11,0.1);color:#F59E0B">5</span> Integração N8N (Opcional)</h5>
                        <p>O <strong>N8N</strong> permite criar automações disparadas pelo chatbot.</p>
                        <ol>
                            <li>Instale o N8N: <a href="https://n8n.io" target="_blank">https://n8n.io</a></li>
                            <li>Crie um workflow com um nó <strong>Webhook</strong> como trigger</li>
                            <li>Copie a URL do webhook e cole no campo <strong>N8N Webhook URL</strong> na aba N8N</li>
                            <li>Se configurado, o chatbot enviará eventos (nova mensagem, nova sessão) para o N8N</li>
                            <li>Use o N8N para:
                                <ul>
                                    <li>Enviar notificações por email quando alguém mandar mensagem</li>
                                    <li>Registrar conversas em planilhas/CRM</li>
                                    <li>Disparar fluxos de trabalho automatizados</li>
                                    <li>Integrar com outros sistemas (Slack, Teams, Trello, etc.)</li>
                                </ul>
                            </li>
                        </ol>
                    </div>

                    <!-- PASSO 6 -->
                    <div class="cb-step" style="background:rgba(16,185,129,0.04);border-color:rgba(16,185,129,0.15)">
                        <h5><span class="cb-step-number" style="background:rgba(16,185,129,0.1);color:#10B981">6</span> Ativar e Testar!</h5>
                        <ol>
                            <li>Na aba <strong>Configurações</strong>, coloque <strong>Chatbot Ativo = Ativado</strong></li>
                            <li>Opcionalmente configure <strong>Horário de Funcionamento</strong></li>
                            <li>Vá na aba <strong>Testar IA</strong> e envie uma mensagem para verificar se a IA responde</li>
                            <li>Envie uma mensagem pelo WhatsApp de um número autorizado</li>
                            <li>Acompanhe as conversas pela aba <strong>Sessões / Chat</strong></li>
                        </ol>
                        <div class="alert alert-success mb-0"><i class="fas fa-check-circle"></i> <strong>Pronto!</strong> Se todos os passos estiverem OK, o chatbot responderá automaticamente via WhatsApp.</div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Sidebar: Checklist Rápido + Arquitetura -->
        <div class="col-md-4">
            <div class="cb-section mb-3">
                <div class="cb-section-header" style="background:linear-gradient(135deg,#10B981,#059669);border-radius:14px 14px 0 0">
                    <h3 style="color:#fff"><i class="fas fa-clipboard-check" style="opacity:0.8"></i> Checklist Rápido</h3>
                </div>
                <div style="padding:0">
                    <div id="ajudaChecklist">
                        <div class="cb-checklist-item" id="chk_ollama">
                            <div class="cb-checklist-icon pending"><i class="fas fa-minus"></i></div>
                            <span>Ollama rodando e acessível</span>
                        </div>
                        <div class="cb-checklist-item" id="chk_modelo">
                            <div class="cb-checklist-icon pending"><i class="fas fa-minus"></i></div>
                            <span>Modelo de IA selecionado</span>
                        </div>
                        <div class="cb-checklist-item" id="chk_evolution">
                            <div class="cb-checklist-icon pending"><i class="fas fa-minus"></i></div>
                            <span>Evolution API conectado</span>
                        </div>
                        <div class="cb-checklist-item" id="chk_webhook">
                            <div class="cb-checklist-icon pending"><i class="fas fa-minus"></i></div>
                            <span>Webhook configurado</span>
                        </div>
                        <div class="cb-checklist-item" id="chk_numeros">
                            <div class="cb-checklist-icon pending"><i class="fas fa-minus"></i></div>
                            <span>Pelo menos 1 número autorizado</span>
                        </div>
                        <div class="cb-checklist-item" id="chk_ativo">
                            <div class="cb-checklist-icon pending"><i class="fas fa-minus"></i></div>
                            <span>Chatbot ativado</span>
                        </div>
                    </div>
                </div>
                <div style="padding:12px 16px;text-align:center;border-top:1px solid rgba(0,0,0,0.06)">
                    <button class="btn btn-sm btn-success" onclick="cbVerificarChecklist()">
                        <i class="fas fa-sync-alt"></i> Verificar Tudo
                    </button>
                </div>
            </div>

            <div class="cb-section mb-3">
                <div class="cb-section-header"><h3><i class="fas fa-sitemap"></i> Arquitetura do Sistema</h3></div>
                <div class="cb-section-body" style="font-size:12px">
                    <div class="text-center mb-3">
                        <pre style="text-align:left;font-size:11px;background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:8px;overflow-x:auto;line-height:1.5">
┌──────────────┐
│  WhatsApp    │
│  (Usuário)   │
└──────┬───────┘
       │ mensagem
       ▼
┌──────────────┐
│ Evolution    │
│ API          │
└──────┬───────┘
       │ webhook
       ▼
┌──────────────────────────┐
│  Oracle X - Chatbot      │
│  chatbot-webhook.php     │
│                          │
│  ┌─────────┐ ┌────────┐  │
│  │ Ollama  │ │ BD/API │  │
│  │ (IA)    │ │Externa │  │
│  └────┬────┘ └───┬────┘  │
│       │ resposta │ dados  │
│       ▼          ▼       │
│  ┌────────────────────┐  │
│  │ Resposta final     │  │
│  └─────────┬──────────┘  │
└────────────┼─────────────┘
             │
       ┌─────┴─────┐
       ▼           ▼
┌──────────┐ ┌──────────┐
│WhatsApp  │ │  N8N     │
│(resposta)│ │(webhook) │
└──────────┘ └──────────┘</pre>
                    </div>
                </div>
            </div>

            <div class="cb-section">
                <div class="cb-section-header"><h3><i class="fas fa-wrench"></i> Solução de Problemas</h3></div>
                <div class="cb-section-body" style="font-size:13px">
                    <div class="mb-3">
                        <strong class="text-danger"><i class="fas fa-times-circle"></i> IA não responde</strong>
                        <ul class="mb-0">
                            <li>Verifique se Ollama está rodando</li>
                            <li>Confira a URL e porta do Ollama</li>
                            <li>Teste na aba "Testar IA"</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <strong class="text-danger"><i class="fas fa-times-circle"></i> WhatsApp não recebe resposta</strong>
                        <ul class="mb-0">
                            <li>Verifique o status do Evolution API</li>
                            <li>Confirme que o webhook está apontando para a URL correta</li>
                            <li>Verifique se o número está autorizado</li>
                            <li>Verifique se o chatbot está ativado</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <strong class="text-danger"><i class="fas fa-times-circle"></i> Dados do banco não aparecem</strong>
                        <ul class="mb-0">
                            <li>Teste a conexão na aba "Base de Dados"</li>
                            <li>Verifique se a base está ativa</li>
                            <li>Melhore a descrição do banco no campo dedicado</li>
                            <li>Verifique as tabelas permitidas</li>
                        </ul>
                    </div>
                    <div>
                        <strong class="text-danger"><i class="fas fa-times-circle"></i> API REST não funciona</strong>
                        <ul class="mb-0">
                            <li>Teste a conexão com o botão "Testar Conexão"</li>
                            <li>Verifique a autenticação (Bearer/API Key/Basic)</li>
                            <li>Valide o JSON dos endpoints (use um validador online)</li>
                            <li>Confira se os endpoints aceitam GET</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  JAVASCRIPT                                   -->
<!-- ============================================ -->
<style>
/* ===== DIAGRAMA INTERATIVO DE BANCO ===== */
.db-diagram-wrapper {
    position: relative;
    background:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px),
        #0d1117;
    background-size: 25px 25px;
    border-radius: 0 0 8px 8px;
    overflow: auto;
    height: 560px;
    cursor: grab;
    user-select: none;
}
.db-diagram-wrapper:active { cursor: grabbing; }
.db-diagram-wrapper::-webkit-scrollbar { width: 10px; height: 10px; }
.db-diagram-wrapper::-webkit-scrollbar-track { background: #161b22; }
.db-diagram-wrapper::-webkit-scrollbar-thumb { background: #30363d; border-radius: 5px; }
.db-diagram-wrapper::-webkit-scrollbar-thumb:hover { background: #484f58; }
.db-diagram-wrapper::-webkit-scrollbar-corner { background: #161b22; }

/* Fullscreen */
.db-diagram-fullscreen {
    position: fixed !important;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 99999;
    border-radius: 0 !important;
    height: 100vh !important;
}
.db-diagram-fullscreen .db-diagram-toolbar { display: flex !important; }

/* Toolbar (fullscreen only) */
.db-diagram-toolbar {
    position: sticky;
    top: 0; left: 0; right: 0;
    z-index: 20;
    display: none;
    align-items: center;
    padding: 8px 16px;
    background: rgba(13, 17, 23, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #30363d;
    color: #c9d1d9;
    font-size: 13px;
    font-weight: 600;
    gap: 10px;
}

/* Empty state */
.db-diagram-empty {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    z-index: 5;
    display: flex;
    flex-direction: column;
    align-items: center;
    pointer-events: none;
}

/* Canvas */
.db-diagram-canvas {
    position: relative;
    min-width: 800px;
    min-height: 600px;
    transform-origin: 0 0;
}

/* SVG overlay */
.db-diagram-svg {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    pointer-events: none;
    z-index: 1;
    overflow: visible;
}

/* Table cards */
.db-table-card {
    position: absolute;
    min-width: 210px;
    max-width: 300px;
    background: #161b22;
    border: 1px solid #30363d;
    border-radius: 6px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.4);
    z-index: 2;
    user-select: none;
    transition: box-shadow 0.2s, border-color 0.2s;
}
.db-table-card:hover {
    border-color: #58a6ff;
    box-shadow: 0 4px 20px rgba(88,166,255,0.15);
}
.db-table-card.dragging {
    z-index: 100 !important;
    box-shadow: 0 8px 30px rgba(88,166,255,0.3) !important;
    border-color: #58a6ff !important;
    opacity: 0.92;
}

/* Table header */
.db-table-header {
    padding: 7px 12px;
    background: linear-gradient(135deg, #1f6feb 0%, #388bfd 100%);
    border-radius: 5px 5px 0 0;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    justify-content: space-between;
    align-items: center;
    letter-spacing: 0.3px;
    cursor: grab;
}
.db-table-header:active { cursor: grabbing; }
.db-table-count { font-size: 10px; opacity: 0.7; font-weight: 400; }

/* Table body */
.db-table-body {
    max-height: 260px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #30363d #161b22;
}
.db-table-body::-webkit-scrollbar { width: 4px; }
.db-table-body::-webkit-scrollbar-track { background: #161b22; }
.db-table-body::-webkit-scrollbar-thumb { background: #30363d; border-radius: 2px; }

/* Table columns */
.db-table-col {
    padding: 3px 10px;
    font-size: 11px;
    border-bottom: 1px solid #21262d;
    color: #c9d1d9;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.db-table-col:last-child { border-bottom: none; }
.db-table-col:hover { background: rgba(88,166,255,0.06); }
.db-table-col.pk { color: #d4a017; font-weight: 600; }
.db-table-col.fk { color: #58a6ff; }

.db-col-icon { width: 18px; text-align: center; flex-shrink: 0; font-size: 12px; }
.db-col-name { flex: 1; overflow: hidden; text-overflow: ellipsis; font-family: 'SFMono-Regular', Consolas, monospace; font-size: 11px; }
.db-col-type { color: #484f58; font-size: 10px; margin-left: auto; flex-shrink: 0; }

/* Link mode: clickable columns */
.db-diagram-wrapper.link-mode { cursor: crosshair !important; }
.db-diagram-wrapper.link-mode .db-table-header { cursor: crosshair !important; }
.db-diagram-wrapper.link-mode .db-table-col {
    cursor: pointer !important;
    transition: background 0.15s, transform 0.1s;
}
.db-diagram-wrapper.link-mode .db-table-col:hover {
    background: rgba(88,166,255,0.2) !important;
    transform: scaleX(1.01);
}
.db-table-col.link-selected {
    background: rgba(88,166,255,0.35) !important;
    border-left: 3px solid #58a6ff !important;
    animation: linkPulse 1s infinite;
}
@keyframes linkPulse {
    0%, 100% { box-shadow: inset 0 0 8px rgba(88,166,255,0.2); }
    50%      { box-shadow: inset 0 0 16px rgba(88,166,255,0.5); }
}
.db-table-card.link-source { border-color: #58a6ff !important; box-shadow: 0 0 20px rgba(88,166,255,0.25) !important; }

/* Floating link statusbar */
.db-link-status {
    position: sticky;
    bottom: 0; left: 0; right: 0;
    z-index: 20;
    display: none;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 10px 20px;
    background: rgba(31,111,235,0.95);
    backdrop-filter: blur(10px);
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    border-top: 1px solid #388bfd;
}
.db-link-status.active { display: flex; }
.db-link-status .step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    font-size: 11px;
    font-weight: 700;
}
.db-link-status .step-badge.done { background: #2ea043; }

/* Link toggle button active state */
.btn-link-active {
    background: #58a6ff !important;
    color: #fff !important;
    border-color: #58a6ff !important;
    box-shadow: 0 0 10px rgba(88,166,255,0.5);
}

/* Modal dark theme for description column */
.db-link-modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.db-link-modal {
    background: #161b22;
    border: 1px solid #30363d;
    border-radius: 12px;
    padding: 24px;
    min-width: 420px;
    max-width: 500px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.5);
    color: #c9d1d9;
}
.db-link-modal h5 { color: #58a6ff; margin-bottom: 16px; font-size: 16px; }
.db-link-modal .rel-summary { background: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px; }
.db-link-modal .rel-summary code { color: #58a6ff; }
.db-link-modal .rel-summary .arrow { color: #d29922; margin: 0 6px; }
.db-link-modal select { background: #0d1117; border: 1px solid #30363d; color: #c9d1d9; border-radius: 6px; padding: 8px 12px; width: 100%; font-size: 13px; }
.db-link-modal select:focus { border-color: #58a6ff; outline: none; box-shadow: 0 0 0 2px rgba(88,166,255,0.2); }
.db-link-modal select option { background: #0d1117; }
.db-link-modal label { display: block; margin-bottom: 6px; font-size: 12px; color: #8b949e; }
.db-link-modal .btn-row { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

/* Dark card/tab styling for fonte details */
#fonteDetalhes .nav-tabs { border-bottom-color: var(--gray-200); }
#fonteDetalhes .nav-tabs .nav-link { color: var(--gray-500); }
#fonteDetalhes .nav-tabs .nav-link:hover { color: var(--gray-700); }
#fonteDetalhes .nav-tabs .nav-link.active { color: var(--primary); font-weight: 600; }
#subTabFonteRels .alert-info { background: var(--info-bg); border-color: var(--info); }
</style>
<script>
const CB_API = '<?= BASE_URL ?>/api/chatbot.php';
let cbCurrentSessaoId = null;
let cbTesteSessaoId = null;

document.addEventListener('DOMContentLoaded', () => {
    cbRefreshStatus();
    cbLoadConfig();
    cbLoadNumeros();
    cbLoadSessoes();
});

// ---- Tab Switching ----
function cbTab(btn) {
    document.querySelectorAll('.cb-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.cb-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
}

// ---- STATUS / KPIs ----
async function cbRefreshStatus() {
    try {
        const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'status' });
        if (!resp.success) return;
        const d = resp.data;

        // KPIs
        document.getElementById('kpiSessoes').textContent = d.stats.sessoes_ativas || 0;
        document.getElementById('kpiMsgsHoje').textContent = d.stats.mensagens_hoje || 0;
        document.getElementById('kpiNumeros').textContent = d.stats.numeros_autorizados || 0;
        document.getElementById('kpiQueries').textContent = d.stats.queries_executadas || 0;
        document.getElementById('kpiErros').textContent = d.stats.erros_hoje || 0;
        const errosHoje = parseInt(d.stats.erros_hoje || 0);
        document.getElementById('kpiErros').style.color = errosHoje > 0 ? 'var(--danger)' : 'var(--success)';

        // Badge de erros na aba Logs
        const badge = document.getElementById('badgeErrosHoje');
        if (errosHoje > 0) { badge.textContent = errosHoje; badge.style.display = ''; } else { badge.style.display = 'none'; }

        // Ollama
        const ollamaEl = document.getElementById('kpiOllama');
        if (d.ollama.online) {
            ollamaEl.innerHTML = '<span class="dot" style="background:#10B981"></span> Online';
            ollamaEl.style.color = '#10B981';
            // Preencher select de modelos
            cbPopulateModels(d.ollama.models, d.ollama.modelo_ativo);
        } else {
            ollamaEl.innerHTML = '<span class="dot" style="background:#EF4444;animation:none"></span> Offline';
            ollamaEl.style.color = '#EF4444';
        }

        // Evolution
        const evoEl = document.getElementById('kpiEvolution');
        if (d.evolution.connected) {
            evoEl.innerHTML = '<span class="dot" style="background:#10B981"></span> Conectado';
            evoEl.style.color = '#10B981';
        } else {
            evoEl.innerHTML = '<span class="dot" style="background:#EF4444;animation:none"></span> ' + (d.evolution.state || 'Desconectado');
            evoEl.style.color = '#EF4444';
        }

        // Model info
        if (d.ollama.online) {
            document.getElementById('testeModelInfo').textContent = 'Modelo: ' + (d.ollama.modelo_ativo || 'N/A');
        }
    } catch (e) {
        console.error('Erro ao carregar status:', e);
    }
}

function cbPopulateModels(models, ativo) {
    const sel = document.getElementById('cfg_chatbot_ia_modelo');
    const current = sel.value;
    sel.innerHTML = '<option value="">Usar padrão do sistema</option>';
    (models || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        if (m === ativo || m === current) opt.selected = true;
        sel.appendChild(opt);
    });
}

// ---- CONFIGURAÇÕES ----
const CONFIG_FIELDS = [
    'chatbot_ativo', 'chatbot_nome', 'chatbot_boas_vindas', 'chatbot_msg_nao_autorizado', 'chatbot_msg_erro',
    'chatbot_horario_ativo', 'chatbot_horario_inicio', 'chatbot_horario_fim', 'chatbot_dias_semana', 'chatbot_msg_fora_horario',
    'chatbot_ia_modelo', 'chatbot_ia_system_prompt', 'chatbot_ia_temperatura', 'chatbot_ia_max_tokens', 'chatbot_ia_contexto_max',
    'chatbot_n8n_ativo', 'chatbot_n8n_url', 'chatbot_n8n_webhook_url', 'chatbot_n8n_api_key',
    'chatbot_db_ativo', 'chatbot_db_tipo', 'chatbot_db_host', 'chatbot_db_port', 'chatbot_db_name', 'chatbot_db_user', 'chatbot_db_pass',
    'chatbot_db_descricao', 'chatbot_db_tabelas_permitidas', 'chatbot_db_max_rows',
    'chatbot_api_url', 'chatbot_api_key', 'chatbot_api_auth_tipo', 'chatbot_api_auth_header',
    'chatbot_api_auth_user', 'chatbot_api_auth_pass', 'chatbot_api_endpoints', 'chatbot_api_descricao'
];

async function cbLoadConfig() {
    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'config' });
    if (!resp.success) return;
    const cfg = resp.data;

    CONFIG_FIELDS.forEach(key => {
        const el = document.getElementById('cfg_' + key);
        if (el && cfg[key] !== undefined) {
            el.value = cfg[key];
        }
    });

    // Atualizar display de temperatura
    const tempEl = document.getElementById('tempValue');
    if (tempEl && cfg.chatbot_ia_temperatura) {
        tempEl.textContent = cfg.chatbot_ia_temperatura;
    }

    // Atualizar visibilidade dos campos de DB/API
    cbOnTipoFonteChange();
    cbOnAuthTipoChange();

    // Sync switch de fontes de dados
    const dbSwitch = document.getElementById('cfg_chatbot_db_ativo_switch');
    if (dbSwitch) dbSwitch.checked = cfg.chatbot_db_ativo === '1';
}

async function cbSaveConfig() {
    const configs = {};
    CONFIG_FIELDS.forEach(key => {
        const el = document.getElementById('cfg_' + key);
        if (el) configs[key] = el.value;
    });

    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'save_config', configs });
    if (resp.success) {
        HelpDesk.toast('Configurações salvas com sucesso!', 'success');
        cbRefreshStatus();
    } else {
        HelpDesk.toast(resp.error || 'Erro ao salvar', 'danger');
    }
}

// ---- NÚMEROS AUTORIZADOS ----
async function cbLoadNumeros() {
    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'numeros' });
    if (!resp.success) return;
    const tbody = document.getElementById('numerosTableBody');

    if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum número cadastrado</td></tr>';
        return;
    }

    tbody.innerHTML = resp.data.map(n => `
        <tr>
            <td><code>${n.numero}</code></td>
            <td>${n.nome || '-'}</td>
            <td><small>${n.notas || '-'}</small></td>
            <td>
                <span class="badge ${n.ativo == 1 ? 'bg-success' : 'bg-secondary'}" style="cursor:pointer" onclick="cbToggleNumero(${n.id})">
                    ${n.ativo == 1 ? 'Ativo' : 'Inativo'}
                </span>
            </td>
            <td><small>${n.criado_em || '-'}</small></td>
            <td>
                <button class="btn btn-sm btn-outline-danger" onclick="cbRemoveNumero(${n.id})" title="Remover">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function cbShowAddNumero() {
    HelpDesk.showModal(
        '<i class="fas fa-phone-plus"></i> Adicionar Número',
        `<div class="form-group mb-3">
            <label class="form-label">Número WhatsApp</label>
            <input type="text" class="form-control" id="addNumeroInput" placeholder="5511999999999">
            <small class="text-muted">Com código do país (55) + DDD + número</small>
        </div>
        <div class="form-group mb-3">
            <label class="form-label">Nome (opcional)</label>
            <input type="text" class="form-control" id="addNomeInput" placeholder="João Silva">
        </div>
        <div class="form-group mb-3">
            <label class="form-label">Notas (opcional)</label>
            <input type="text" class="form-control" id="addNotasInput" placeholder="Diretor comercial">
        </div>`,
        `<button class="btn btn-primary" onclick="cbAddNumero()"><i class="fas fa-plus"></i> Adicionar</button>
         <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>`
    );
}

async function cbAddNumero() {
    const numero = document.getElementById('addNumeroInput').value.trim();
    const nome = document.getElementById('addNomeInput').value.trim();
    const notas = document.getElementById('addNotasInput').value.trim();

    if (!numero) return HelpDesk.toast('Número obrigatório', 'warning');

    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'add_numero', numero, nome, notas });
    if (resp.success) {
        HelpDesk.toast('Número adicionado!', 'success');
        HelpDesk.closeModal();
        cbLoadNumeros();
        cbRefreshStatus();
    } else {
        HelpDesk.toast(resp.error, 'danger');
    }
}

async function cbToggleNumero(id) {
    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'toggle_numero', id });
    if (resp.success) {
        cbLoadNumeros();
    }
}

async function cbRemoveNumero(id) {
    if (!confirm('Remover este número?')) return;
    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'remove_numero', id });
    if (resp.success) {
        HelpDesk.toast('Número removido', 'success');
        cbLoadNumeros();
        cbRefreshStatus();
    }
}

// ---- SESSÕES / CHAT ----
async function cbLoadSessoes() {
    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'sessoes' });
    if (!resp.success) return;

    const container = document.getElementById('sessoesListContainer');

    if (!resp.data || resp.data.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">Nenhuma sessão encontrada</div>';
        return;
    }

    container.innerHTML = resp.data.map(s => `
        <div class="p-3 border-bottom" style="cursor:pointer;${cbCurrentSessaoId == s.id ? 'background:#e8f4fd;' : ''}" 
             onclick="cbOpenChat(${s.id}, '${(s.nome_contato || '').replace(/'/g, "\\'")}')">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${s.nome_contato || 'Sem nome'}</strong><br>
                    <small class="text-muted"><i class="fas fa-phone"></i> ${s.numero_whatsapp}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary">${s.total_msgs || 0}</span><br>
                    <small class="text-muted">${s.ultimo_acesso ? s.ultimo_acesso.substring(0, 16) : '-'}</small>
                </div>
            </div>
            ${s.ultima_msg ? `<div class="mt-1"><small class="text-truncate d-block" style="max-width:300px;color:#666">${s.ultima_msg.substring(0, 80)}</small></div>` : ''}
        </div>
    `).join('');
}

async function cbOpenChat(sessaoId, nome) {
    cbCurrentSessaoId = sessaoId;
    document.getElementById('chatViewTitle').innerHTML = '<i class="fas fa-comments"></i> ' + (nome || 'Chat');
    document.getElementById('btnLimparSessao').style.display = 'inline-block';

    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'historico', sessao_id: sessaoId });
    if (!resp.success) return;

    const body = document.getElementById('chatViewBody');
    const msgs = resp.data.mensagens || [];

    if (msgs.length === 0) {
        body.innerHTML = '<div class="text-center text-muted py-5">Nenhuma mensagem nesta sessão</div>';
        return;
    }

    body.innerHTML = msgs.map(m => {
        const isUser = m.remetente === 'user';
        const bgColor = isUser ? '#dcf8c6' : '#ffffff';
        const align = isUser ? 'flex-end' : 'flex-start';
        const icon = isUser ? 'fa-user' : 'fa-robot';
        const sqlInfo = m.sql_executado ? `<div style="margin-top:4px;padding:4px 8px;background:#f0f0f0;border-radius:4px;font-size:11px;font-family:monospace">SQL: ${m.sql_executado}</div>` : '';
        const meta = m.duracao_ms ? `<small style="color:#999;font-size:10px">${m.duracao_ms}ms | ${m.tokens_usados || 0} tokens</small>` : '';
        
        return `<div style="display:flex;justify-content:${align};margin-bottom:8px">
            <div style="max-width:75%;padding:8px 12px;border-radius:12px;background:${bgColor};box-shadow:0 1px 2px rgba(0,0,0,0.1)">
                <small style="color:#888"><i class="fas ${icon}"></i> ${isUser ? 'Usuário' : 'Bot'} · ${(m.criado_em || '').substring(11, 16)}</small>
                <div style="margin-top:4px;white-space:pre-wrap">${escapeHtml(m.mensagem)}</div>
                ${sqlInfo}
                ${meta}
            </div>
        </div>`;
    }).join('');

    body.scrollTop = body.scrollHeight;
    cbLoadSessoes(); // Refresh list to highlight
}

async function cbLimparSessao() {
    if (!cbCurrentSessaoId) return;
    if (!confirm('Limpar todo o histórico desta sessão?')) return;

    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'limpar_sessao', sessao_id: cbCurrentSessaoId });
    if (resp.success) {
        HelpDesk.toast('Sessão limpa', 'success');
        cbOpenChat(cbCurrentSessaoId, '');
    }
}

// ---- BASE DE DADOS / API (LEGADO - stubs para compatibilidade) ----
function cbOnTipoFonteChange() { /* migrado para multi-fontes */ }
function cbOnAuthTipoChange() { /* migrado para multi-fontes */ }
function cbInsertEndpointTemplate() { /* migrado para multi-fontes */ }
async function cbTestDB() { /* migrado para multi-fontes */ }
async function cbLoadSchema() { /* migrado para multi-fontes */ }

// ---- N8N ----
async function cbTestN8N() {
    HelpDesk.toast('Enviando teste para N8N...', 'info');
    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'test_n8n' });
    if (resp.success) {
        HelpDesk.toast('✅ Webhook enviado com sucesso!', 'success');
        cbLoadN8NLogs();
    } else {
        HelpDesk.toast('❌ ' + (resp.error || 'Falha'), 'danger');
    }
}

async function cbLoadN8NLogs() {
    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'n8n_logs' });
    if (!resp.success) return;

    const tbody = document.getElementById('n8nLogsBody');
    if (!resp.data || resp.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Nenhum log</td></tr>';
        return;
    }

    tbody.innerHTML = resp.data.map(l => `
        <tr>
            <td><small>${(l.criado_em || '').substring(0, 16)}</small></td>
            <td><span class="badge ${l.direcao === 'saida' ? 'bg-primary' : 'bg-warning'}">${l.direcao === 'saida' ? '↑ Saída' : '↓ Entrada'}</span></td>
            <td><small>${l.evento || '-'}</small></td>
            <td><code>${l.http_code || '-'}</code></td>
            <td>${l.sucesso == 1 ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>'}</td>
        </tr>
    `).join('');
}

// ---- AJUDA / CHECKLIST ----
async function cbVerificarChecklist() {
    // Buscar status atual
    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'status' });
    if (!resp.success) {
        HelpDesk.toast('Erro ao verificar status', 'danger');
        return;
    }
    const s = resp.data;

    // Buscar config para checar modelo e ativo
    const cfgResp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'config' });
    const cfg = cfgResp.success ? cfgResp.data : {};

    const checks = [
        { id: 'chk_ollama',    ok: s.ollama?.status === 'online' },
        { id: 'chk_modelo',    ok: !!(cfg.chatbot_ia_modelo && cfg.chatbot_ia_modelo.length > 0) },
        { id: 'chk_evolution', ok: s.evolution?.connected === true },
        { id: 'chk_webhook',   ok: true }, // Webhook sempre está configurado (é uma URL fixa)
        { id: 'chk_numeros',   ok: (s.stats?.numeros_autorizados || 0) > 0 },
        { id: 'chk_ativo',     ok: cfg.chatbot_ativo === '1' },
    ];

    let totalOk = 0;
    checks.forEach(c => {
        const el = document.getElementById(c.id);
        if (!el) return;
        const iconEl = el.querySelector('.cb-checklist-icon');
        if (iconEl) {
            if (c.ok) {
                iconEl.className = 'cb-checklist-icon ok';
                iconEl.innerHTML = '<i class="fas fa-check"></i>';
                totalOk++;
            } else {
                iconEl.className = 'cb-checklist-icon fail';
                iconEl.innerHTML = '<i class="fas fa-times"></i>';
            }
        }
    });

    if (totalOk === checks.length) {
        HelpDesk.toast('✅ Tudo OK! O chatbot está pronto para funcionar.', 'success');
    } else {
        HelpDesk.toast(`⚠️ ${totalOk}/${checks.length} verificações OK. Revise os itens em vermelho.`, 'warning');
    }
}

// ---- LOGS & ERROS ----
let cbLogsAutoRefresh = null;

async function cbLoadLogs() {
    const filtro = document.getElementById('logFiltro')?.value || 'all';
    const container = document.getElementById('logContainer');
    container.innerHTML = '<div class="text-center py-3" style="color:#8b949e"><i class="fas fa-spinner fa-spin"></i> Carregando logs...</div>';

    try {
        const resp = await HelpDesk.api('GET', `/api/chatbot.php?action=logs&filtro=${filtro}&limite=300`);
        if (!resp.success) throw new Error(resp.message || 'Erro');

        const logs = resp.data || [];
        let html = '';
        let errCount = 0, okCount = 0;

        if (logs.length === 0) {
            html = '<div class="text-center py-4" style="color:#8b949e"><i class="fas fa-check-circle fa-2x mb-2 d-block"></i>Nenhum log encontrado</div>';
        } else {
            logs.forEach(log => {
                const colors = {
                    error:      { bg: '#1a0000', border: '#f85149', icon: '🔴', text: '#f85149' },
                    warning:    { bg: '#1a1500', border: '#d29922', icon: '🟡', text: '#d29922' },
                    success:    { bg: '#001a00', border: '#3fb950', icon: '🟢', text: '#3fb950' },
                    processing: { bg: '#00001a', border: '#58a6ff', icon: '🔵', text: '#58a6ff' },
                    webhook:    { bg: '#0d1117', border: '#30363d', icon: '📨', text: '#8b949e' },
                    info:       { bg: '#0d1117', border: '#30363d', icon: 'ℹ️', text: '#c9d1d9' }
                };
                const c = colors[log.level] || colors.info;
                if (log.level === 'error') errCount++;
                if (log.level === 'success') okCount++;

                const time = log.time ? `<span style="color:#7d8590;margin-right:8px">${log.time}</span>` : '';
                html += `<div style="padding:4px 12px;border-left:3px solid ${c.border};background:${c.bg};margin-bottom:1px;line-height:1.6">
                    ${time}<span style="margin-right:6px">${c.icon}</span><span style="color:${c.text}">${escapeHtml(log.message)}</span>
                </div>`;
            });
        }

        container.innerHTML = html;
        document.getElementById('logCountTotal').textContent = `${logs.length} logs`;
        const errBadge = document.getElementById('logCountErrors');
        const okBadge = document.getElementById('logCountSuccess');
        if (errCount > 0) { errBadge.textContent = `${errCount} erros`; errBadge.style.display = ''; } else { errBadge.style.display = 'none'; }
        if (okCount > 0) { okBadge.textContent = `${okCount} OK`; okBadge.style.display = ''; } else { okBadge.style.display = 'none'; }

        // badge global de erros
        const badge = document.getElementById('badgeErrosHoje');
        if (errCount > 0) { badge.textContent = errCount; badge.style.display = ''; } else { badge.style.display = 'none'; }

        // scroll to bottom (most recent)
        container.scrollTop = container.scrollHeight;
    } catch (e) {
        container.innerHTML = `<div class="text-center py-3" style="color:#f85149"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(e.message)}</div>`;
    }
}

async function cbLimparLogs() {
    if (!confirm('Limpar todos os logs do arquivo? Esta ação não pode ser desfeita.')) return;
    try {
        const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'limpar_logs' });
        if (resp.success) {
            HelpDesk.toast('✅ Logs limpos com sucesso!', 'success');
            cbLoadLogs();
        } else {
            HelpDesk.toast('❌ ' + (resp.message || 'Erro'), 'error');
        }
    } catch (e) {
        HelpDesk.toast('❌ Erro: ' + e.message, 'error');
    }
}

// =============================================
//  DATASYSTEM API LOGS
// =============================================
let _dsLogsCache = [];

async function cbLoadDSLogs() {
    const tbody = document.getElementById('dsLogTableBody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

    const filtro = document.getElementById('dsLogFiltro')?.value || 'all';
    const limite = document.getElementById('dsLogLimite')?.value || '100';

    try {
        const resp = await HelpDesk.api('GET', `/api/chatbot.php?action=datasystem_logs&filtro=${filtro}&limite=${limite}`);
        if (!resp.success) throw new Error(resp.message || 'Erro');

        const logs = resp.data || [];
        const stats = resp.stats || {};
        _dsLogsCache = logs;

        // Stats
        document.getElementById('dsStatTotal').textContent = (stats.total || 0) + ' total';
        document.getElementById('dsStatOk').textContent = (stats.sucesso || 0) + ' sucesso';
        document.getElementById('dsStatErro').textContent = (stats.erros || 0) + ' erros';
        document.getElementById('dsStatHoje').textContent = (stats.hoje || 0) + ' hoje';
        document.getElementById('dsStatAvg').textContent = (stats.avg_ms || 0) + 'ms avg';

        // Badge
        const badge = document.getElementById('badgeDSLogsHoje');
        if (stats.hoje > 0) {
            badge.textContent = stats.hoje;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }

        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Nenhum log encontrado</td></tr>';
            return;
        }

        let html = '';
        logs.forEach((log, idx) => {
            const data = log.criado_em ? new Date(log.criado_em).toLocaleString('pt-BR') : '-';
            const isErr = !log.sucesso || parseInt(log.sucesso) === 0;
            const httpBadge = log.http_code
                ? `<span class="badge ${log.http_code >= 400 ? 'bg-danger' : 'bg-success'}">${log.http_code}</span>`
                : '<span class="badge bg-secondary">—</span>';
            const duracao = log.duracao_ms ? log.duracao_ms + 'ms' : '—';
            const tamanho = log.response_size ? formatBytes(log.response_size) : '—';
            const statusIcon = isErr
                ? '<i class="fas fa-times-circle text-danger"></i>'
                : '<i class="fas fa-check-circle text-success"></i>';
            const endpoint = escapeHtml(log.endpoint || '—');
            const endpointShort = endpoint.length > 60 ? endpoint.substring(0, 60) + '…' : endpoint;

            html += `<tr style="cursor:pointer;${isErr ? 'background:rgba(239,68,68,0.04)' : ''}" onclick="cbShowDSLogDetail(${idx})">
                <td class="text-muted">${log.id}</td>
                <td><small>${data}</small></td>
                <td><span class="badge bg-primary">${escapeHtml(log.method || 'GET')}</span></td>
                <td><code style="font-size:11px;word-break:break-all" title="${endpoint}">${endpointShort}</code></td>
                <td>${httpBadge}</td>
                <td class="text-muted">${duracao}</td>
                <td class="text-muted">${tamanho}</td>
                <td class="text-center">${statusIcon}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="event.stopPropagation();cbShowDSLogDetail(${idx})" title="Ver detalhe">
                        <i class="fas fa-eye" style="font-size:11px"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="event.stopPropagation();cbDownloadDSLog(${idx},'full')" title="Baixar JSON">
                        <i class="fas fa-download" style="font-size:11px"></i>
                    </button>
                </td>
            </tr>`;
        });
        tbody.innerHTML = html;
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(e.message)}</td></tr>`;
    }
}

function cbShowDSLogDetail(idx) {
    const log = _dsLogsCache[idx];
    if (!log) return;

    const data = log.criado_em ? new Date(log.criado_em).toLocaleString('pt-BR') : '-';
    const isErr = !log.sucesso || parseInt(log.sucesso) === 0;

    let payloadHtml = '<span class="text-muted">—</span>';
    if (log.payload) {
        try {
            const p = typeof log.payload === 'string' ? JSON.parse(log.payload) : log.payload;
            payloadHtml = `<pre style="max-height:200px;overflow:auto;background:#0d1117;color:#c9d1d9;padding:12px;border-radius:8px;font-size:12px;margin:0">${escapeHtml(JSON.stringify(p, null, 2))}</pre>`;
        } catch { payloadHtml = `<pre style="max-height:200px;overflow:auto;background:#0d1117;color:#c9d1d9;padding:12px;border-radius:8px;font-size:12px;margin:0">${escapeHtml(log.payload)}</pre>`; }
    }

    let responseHtml = '<span class="text-muted">—</span>';
    if (log.response_body) {
        try {
            const r = JSON.parse(log.response_body);
            responseHtml = `<pre style="max-height:400px;overflow:auto;background:#0d1117;color:#c9d1d9;padding:12px;border-radius:8px;font-size:12px;margin:0">${escapeHtml(JSON.stringify(r, null, 2))}</pre>`;
        } catch { responseHtml = `<pre style="max-height:400px;overflow:auto;background:#0d1117;color:#c9d1d9;padding:12px;border-radius:8px;font-size:12px;margin:0">${escapeHtml(log.response_body.substring(0, 10000))}</pre>`; }
    }

    const html = `
        <div class="p-3">
            <!-- Header info -->
            <div class="d-flex gap-3 flex-wrap mb-3 pb-3" style="border-bottom:1px solid var(--gray-200)">
                <div><strong>ID:</strong> ${log.id}</div>
                <div><strong>Data:</strong> ${data}</div>
                <div><strong>Fonte:</strong> ${escapeHtml(log.fonte_nome || '—')}</div>
                <div><strong>Sessão:</strong> ${log.sessao_id || '—'}</div>
                <div><strong>Duração:</strong> <span class="badge bg-warning text-dark">${log.duracao_ms || 0}ms</span></div>
                <div><strong>Tamanho:</strong> ${formatBytes(log.response_size || 0)}</div>
                <div><strong>Status:</strong> ${isErr
                    ? '<span class="badge bg-danger"><i class="fas fa-times"></i> Erro</span>'
                    : '<span class="badge bg-success"><i class="fas fa-check"></i> Sucesso</span>'}</div>
            </div>

            <!-- Endpoint -->
            <div class="mb-3">
                <label class="form-label fw-bold mb-1" style="font-size:12px"><i class="fas fa-link"></i> Endpoint</label>
                <div style="background:var(--gray-50);padding:10px 14px;border-radius:8px;border:1px solid var(--gray-200);font-family:monospace;font-size:13px;word-break:break-all">
                    <span class="badge bg-primary me-1">${escapeHtml(log.method || 'GET')}</span>
                    <span class="badge ${log.http_code >= 400 ? 'bg-danger' : 'bg-success'} me-2">${log.http_code || '—'}</span>
                    ${escapeHtml(log.endpoint || '—')}
                </div>
            </div>

            ${log.erro ? `<div class="alert alert-danger py-2 mb-3" style="font-size:13px"><i class="fas fa-exclamation-triangle"></i> <strong>Erro:</strong> ${escapeHtml(log.erro)}</div>` : ''}

            <!-- Payload -->
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fw-bold mb-0" style="font-size:12px"><i class="fas fa-upload"></i> Payload (enviado)</label>
                    ${log.payload ? `<button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px" onclick="cbDownloadDSLog(${idx},'payload')"><i class="fas fa-download"></i> Baixar JSON</button>` : ''}
                </div>
                ${payloadHtml}
            </div>

            <!-- Response -->
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fw-bold mb-0" style="font-size:12px"><i class="fas fa-download"></i> Response (retorno)</label>
                    <div class="d-flex gap-1">
                        ${log.response_body ? `<button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px" onclick="cbDownloadDSLog(${idx},'response')"><i class="fas fa-download"></i> Baixar JSON</button>` : ''}
                        ${log.response_body && log.payload ? `<button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px" onclick="cbDownloadDSLog(${idx},'full')"><i class="fas fa-file-archive"></i> Baixar Completo</button>` : ''}
                    </div>
                </div>
                ${responseHtml}
            </div>
        </div>
    `;

    HelpDesk.showModal(
        `<i class="fas fa-exchange-alt"></i> Detalhe da Chamada #${log.id}`,
        html,
        '',
        'modal-xl'
    );
}

async function cbLimparDSLogs() {
    if (!confirm('Limpar todos os logs de chamadas DataSystem? Esta ação não pode ser desfeita.')) return;
    try {
        const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'limpar_datasystem_logs' });
        if (resp.success) {
            HelpDesk.toast('✅ Logs DataSystem limpos!', 'success');
            cbLoadDSLogs();
        } else {
            HelpDesk.toast('❌ ' + (resp.message || 'Erro'), 'error');
        }
    } catch (e) {
        HelpDesk.toast('❌ Erro: ' + e.message, 'error');
    }
}

/**
 * Download payload, response ou ambos como JSON
 * @param {number} idx - Índice no cache
 * @param {'payload'|'response'|'full'} tipo
 */
function cbDownloadDSLog(idx, tipo) {
    const log = _dsLogsCache[idx];
    if (!log) return;

    let content, filename;
    const ts = (log.criado_em || '').replace(/[\s:\/]/g, '-');
    const endpointSlug = (log.endpoint || 'unknown').replace(/[^a-zA-Z0-9_-]/g, '_').substring(0, 40);

    if (tipo === 'payload') {
        let data;
        try {
            data = typeof log.payload === 'string' ? JSON.parse(log.payload) : log.payload;
        } catch { data = log.payload; }
        content = JSON.stringify(data, null, 2);
        filename = `datasystem_payload_${log.id}_${endpointSlug}.json`;
    } else if (tipo === 'response') {
        let data;
        try {
            data = JSON.parse(log.response_body);
        } catch { data = log.response_body; }
        content = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
        filename = `datasystem_response_${log.id}_${endpointSlug}.json`;
    } else {
        // full — payload + response + metadata
        let payloadData, responseData;
        try { payloadData = typeof log.payload === 'string' ? JSON.parse(log.payload) : log.payload; } catch { payloadData = log.payload; }
        try { responseData = JSON.parse(log.response_body); } catch { responseData = log.response_body; }
        const full = {
            _meta: {
                id: log.id,
                data: log.criado_em,
                fonte: log.fonte_nome,
                method: log.method,
                endpoint: log.endpoint,
                http_code: log.http_code,
                duracao_ms: log.duracao_ms,
                response_size: log.response_size,
                sucesso: !!parseInt(log.sucesso),
                erro: log.erro || null,
                sessao_id: log.sessao_id
            },
            payload: payloadData || null,
            response: responseData || null
        };
        content = JSON.stringify(full, null, 2);
        filename = `datasystem_full_${log.id}_${endpointSlug}.json`;
    }

    const blob = new Blob([content], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    HelpDesk.toast('📥 Download iniciado: ' + filename, 'success');
}

function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

async function cbLoadErros() {
    const tbody = document.getElementById('errosTableBody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

    try {
        const resp = await HelpDesk.api('GET', '/api/chatbot.php?action=erros&limite=50');
        if (!resp.success) throw new Error(resp.message || 'Erro');

        const erros = resp.data || [];
        if (erros.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">✅ Nenhum erro registrado</td></tr>';
            return;
        }

        let html = '';
        erros.forEach(err => {
            const data = err.criado_em ? new Date(err.criado_em).toLocaleString('pt-BR') : '-';
            const contato = err.nome_contato || err.numero || '-';
            const msg = err.mensagem || '';
            // Highlight SQL in error messages
            const msgHtml = escapeHtml(msg)
                .replace(/(SQL|SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|JOIN|GROUP BY|ORDER BY|COUNT|SUM|AVG)/gi, '<strong style="color:#f85149">$1</strong>')
                .replace(/(\[ERRO[^\]]*\])/g, '<span class="badge bg-danger">$1</span>');

            html += `<tr>
                <td class="text-nowrap">${data}</td>
                <td class="text-nowrap">${escapeHtml(contato)}</td>
                <td style="font-family:monospace;font-size:11px;word-break:break-all">${msgHtml}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(e.message)}</td></tr>`;
    }
}

// ---- FONTES DE DADOS MÚLTIPLAS ----
let _fontes = [];
let _fonteAtualId = null;
let _fonteSchema = null;
let _fonteRels = [];

async function cbLoadFontes() {
    try {
        const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'fontes' });
        if (!resp.success) return;
        _fontes = resp.data || [];
        cbRenderFontes();
    } catch (e) {
        console.error('Erro ao carregar fontes:', e);
    }
}

function cbRenderFontes() {
    const container = document.getElementById('fontesListContainer');
    if (!_fontes || _fontes.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-database fa-3x mb-3" style="color:var(--gray-300)"></i>
                <p style="color:var(--gray-500)">Nenhuma fonte de dados configurada</p>
                <button class="btn btn-primary" onclick="cbShowAddFonte()">
                    <i class="fas fa-plus"></i> Adicionar Primeira Fonte
                </button>
            </div>`;
        return;
    }

    const tipoIcons = { mysql: 'fa-database', pgsql: 'fa-database', sqlserver: 'fa-database', sqlite: 'fa-file', api: 'fa-plug' };
    const tipoLabels = { mysql: 'MySQL', pgsql: 'PostgreSQL', sqlserver: 'SQL Server', sqlite: 'SQLite', api: 'API REST' };
    const tipoCores = { mysql: '#00758f', pgsql: '#336791', sqlserver: '#cc2927', sqlite: '#003b57', api: '#8B5CF6' };
    const tipoBgs = { mysql: '#f0fafb', pgsql: '#f0f4f8', sqlserver: '#fef2f2', sqlite: '#f0f5f8', api: '#f5f3ff' };

    let html = '<div class="row g-3">';
    _fontes.forEach(f => {
        const isDS = (f.api_template === 'datasystem');
        const cor = isDS ? '#E65100' : (tipoCores[f.tipo] || '#6c757d');
        const bg = isDS ? '#FFF3E0' : (tipoBgs[f.tipo] || 'var(--gray-50)');
        const icon = isDS ? 'fa-cubes' : (tipoIcons[f.tipo] || 'fa-database');
        const label = isDS ? '📦 DataSystem ERP' : (tipoLabels[f.tipo] || f.tipo);
        const testeOk = f.ultimo_teste_ok === '1' || f.ultimo_teste_ok === 1;
        const testeFalhou = f.ultimo_teste_ok === '0' || f.ultimo_teste_ok === 0;
        const ativo = f.ativo == 1;

        const statusBadge = ativo
            ? '<span class="badge bg-success" style="font-weight:500"><i class="fas fa-check"></i> Ativa</span>'
            : '<span class="badge bg-secondary" style="font-weight:500"><i class="fas fa-pause"></i> Inativa</span>';
        const testeBadge = testeOk
            ? '<span class="badge" style="background:var(--success-bg);color:var(--success);font-weight:500"><i class="fas fa-plug"></i> OK</span>'
            : (testeFalhou ? '<span class="badge" style="background:var(--danger-bg);color:var(--danger);font-weight:500"><i class="fas fa-exclamation-triangle"></i> Falha</span>'
            : '<span class="badge" style="background:var(--warning-bg);color:var(--warning);font-weight:500"><i class="fas fa-question-circle"></i> Não testada</span>');

        const info = f.tipo === 'api'
            ? (f.api_url || '-')
            : `${f.db_host || '-'}${f.db_name ? '/' + f.db_name : ''}`;

        html += `
        <div class="col-md-6 col-xl-4">
            <div class="card h-100" style="border-left:4px solid ${cor};${!ativo ? 'opacity:0.65;' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div style="width:32px;height:32px;border-radius:8px;background:${bg};display:flex;align-items:center;justify-content:center">
                                    <i class="fas ${icon}" style="color:${cor};font-size:14px"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0" style="font-weight:600;color:var(--gray-800)">${escapeHtml(f.nome)}</h6>
                                    <small style="color:var(--gray-400);font-size:11px">${label} · <code style="font-size:11px;color:${cor}">${escapeHtml(f.alias)}</code></small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-1 align-items-end">
                            ${statusBadge}
                            ${testeBadge}
                        </div>
                    </div>
                    <div class="mb-3" style="font-size:12px;color:var(--gray-500)">
                        <div class="mb-1"><i class="fas fa-server" style="width:14px;color:var(--gray-400)"></i> ${escapeHtml(info)}</div>
                        ${f.descricao ? '<div><i class="fas fa-info-circle" style="width:14px;color:var(--gray-400)"></i> ' + escapeHtml(f.descricao.substring(0, 100)) + '</div>' : ''}
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary" onclick="cbEditFonte(${f.id})" title="Editar">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="cbTestarFonte(${f.id})" title="Testar conexão">
                            <i class="fas fa-plug"></i> Testar
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="cbVerFonteSchema(${f.id})" title="Ver schema/tabelas">
                            <i class="fas fa-table"></i> Schema
                        </button>
                        <div class="ms-auto d-flex gap-1">
                            <button class="btn btn-sm btn-${ativo ? 'warning' : 'success'}" onclick="cbToggleFonte(${f.id})" title="${ativo ? 'Desativar' : 'Ativar'}" style="font-size:11px">
                                <i class="fas fa-${ativo ? 'pause' : 'play'}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="cbRemoverFonte(${f.id})" title="Remover">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function cbShowAddFonte() {
    _fonteAtualId = null;
    cbShowFonteModal('Nova Fonte de Dados', {});
}

async function cbEditFonte(id) {
    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'fonte', id });
    if (!resp.success) return HelpDesk.toast(resp.error || 'Erro', 'danger');
    _fonteAtualId = id;
    cbShowFonteModal('Editar Fonte: ' + resp.data.nome, resp.data);
}

function cbShowFonteModal(titulo, dados) {
    const isAPI = dados.tipo === 'api';
    const isDS = (dados.api_template === 'datasystem');
    const d = dados;
    HelpDesk.showModal(
        '<i class="fas fa-database"></i> ' + titulo,
        `<div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome *</label>
                <input type="text" class="form-control" id="fonteNome" value="${escapeHtml(d.nome || '')}" placeholder="Ex: ERP Vendas">
            </div>
            <div class="col-md-3">
                <label class="form-label">Alias *</label>
                <input type="text" class="form-control" id="fonteAlias" value="${escapeHtml(d.alias || '')}" placeholder="erp">
                <small class="text-muted">Minúsculas, sem espaços</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo *</label>
                <select class="form-control" id="fonteTipo" onchange="cbFonteModalTipoChange()">
                    <option value="mysql" ${d.tipo === 'mysql' ? 'selected' : ''}>MySQL</option>
                    <option value="pgsql" ${d.tipo === 'pgsql' ? 'selected' : ''}>PostgreSQL</option>
                    <option value="sqlserver" ${d.tipo === 'sqlserver' ? 'selected' : ''}>SQL Server</option>
                    <option value="sqlite" ${d.tipo === 'sqlite' ? 'selected' : ''}>SQLite</option>
                    <option value="api" ${isAPI && !isDS ? 'selected' : ''}>API REST</option>
                    <option value="datasystem" ${isDS ? 'selected' : ''}>📦 DataSystem ERP</option>
                </select>
            </div>
        </div>
        <hr>
        <!-- Campos SQL -->
        <div id="fonteFieldsSQL" style="display:${isAPI || isDS ? 'none' : ''}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Host</label>
                    <input type="text" class="form-control" id="fonteDbHost" value="${escapeHtml(d.db_host || '')}" placeholder="localhost">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Porta</label>
                    <input type="text" class="form-control" id="fonteDbPort" value="${escapeHtml(d.db_port || '')}" placeholder="3306">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banco</label>
                    <input type="text" class="form-control" id="fonteDbName" value="${escapeHtml(d.db_name || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="fonteDbUser" value="${escapeHtml(d.db_user || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Senha</label>
                    <input type="password" class="form-control" id="fonteDbPass" value="${escapeHtml(d.db_pass || '')}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Máx. Linhas</label>
                    <input type="number" class="form-control" id="fonteMaxRows" value="${d.max_rows || 50}">
                </div>
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Tabelas Permitidas <small class="text-muted">(vírgula, vazio=todas)</small></label>
                <input type="text" class="form-control" id="fonteTabelasPermitidas" value="${escapeHtml(d.tabelas_permitidas || '')}" placeholder="vendas,clientes,produtos">
            </div>
        </div>
        <!-- Campos DataSystem ERP -->
        <div id="fonteFieldsDS" style="display:${isDS ? '' : 'none'}">
            <div class="alert alert-info py-2 mb-3" style="font-size:12px">
                <i class="fas fa-info-circle"></i>
                <strong>DataSystem ERP</strong> — Integração automática com a API de gestão DataSystem.
                A autenticação JWT é feita automaticamente. Informe o CNPJ e Hash fornecidos pela DataSystem.
            </div>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">URL Base da API *</label>
                    <input type="text" class="form-control" id="fonteDsUrl" value="${escapeHtml(isDS ? (d.api_url || 'https://integracaodatasystem.useserver.com.br') : 'https://integracaodatasystem.useserver.com.br')}" placeholder="https://integracaodatasystem.useserver.com.br">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="text-muted" style="font-size:11px;padding-top:8px">
                        <i class="fas fa-lock" style="color:var(--success)"></i> Auth JWT automática
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-building"></i> CNPJ *</label>
                    <input type="text" class="form-control" id="fonteDsCnpj" value="${escapeHtml(isDS ? (d.api_auth_user || '') : '')}" placeholder="00.000.000/0000-00">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-key"></i> Hash de Acesso *</label>
                    <input type="password" class="form-control" id="fonteDsHash" value="${escapeHtml(isDS ? (d.api_key || '') : '')}" placeholder="Hash fornecido pela DataSystem">
                </div>
            </div>
            <div class="mt-3 p-3 rounded" style="background:var(--gray-50);border:1px solid var(--gray-200)">
                <h6 style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:8px">
                    <i class="fas fa-list-check"></i> Endpoints pré-configurados:
                </h6>
                <div style="font-size:11px;color:var(--gray-500);columns:2;column-gap:16px;line-height:1.8">
                    <div>📊 <strong>Vendas</strong> — por período</div>
                    <div>👥 <strong>Clientes</strong> — busca / por CPF</div>
                    <div>📦 <strong>Produtos</strong> — catálogo</div>
                    <div>🏪 <strong>Lojas</strong> — filiais</div>
                    <div>👤 <strong>Vendedores</strong> — equipe</div>
                    <div>📋 <strong>Saldos/Estoque</strong> — por loja</div>
                    <div>💰 <strong>Contas a Pagar</strong></div>
                    <div>💳 <strong>Contas a Receber</strong></div>
                    <div>📥 <strong>Entradas de Estoque</strong></div>
                    <div>🏭 <strong>Fornecedores</strong></div>
                    <div>🏷️ <strong>Departamentos, Marcas, Cores</strong></div>
                    <div>🛒 <strong>Pedidos de Compra</strong></div>
                    <div>💳 <strong>Planos de Pagamento</strong></div>
                    <div>📁 <strong>Coleções, Modelos, Classes, Tipos</strong></div>
                </div>
            </div>
        </div>
        <!-- Campos API Genérica -->
        <div id="fonteFieldsAPI" style="display:${isAPI && !isDS ? '' : 'none'}">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">URL Base</label>
                    <input type="text" class="form-control" id="fonteApiUrl" value="${escapeHtml(!isDS ? (d.api_url || '') : '')}" placeholder="https://api.exemplo.com/v1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Autenticação</label>
                    <select class="form-control" id="fonteApiAuthTipo" onchange="cbFonteModalAuthChange()">
                        <option value="none" ${d.api_auth_tipo === 'none' || !d.api_auth_tipo ? 'selected' : ''}>Nenhuma</option>
                        <option value="bearer" ${d.api_auth_tipo === 'bearer' ? 'selected' : ''}>Bearer Token</option>
                        <option value="apikey" ${d.api_auth_tipo === 'apikey' ? 'selected' : ''}>API Key</option>
                        <option value="basic" ${d.api_auth_tipo === 'basic' ? 'selected' : ''}>Basic Auth</option>
                    </select>
                </div>
                <div class="col-md-6" id="fonteApiKeyField" style="display:${['bearer','apikey'].includes(d.api_auth_tipo) ? '' : 'none'}">
                    <label class="form-label">Token / API Key</label>
                    <input type="password" class="form-control" id="fonteApiKey" value="${escapeHtml(!isDS ? (d.api_key || '') : '')}">
                </div>
                <div class="col-md-6" id="fonteApiHeaderField" style="display:${d.api_auth_tipo === 'apikey' ? '' : 'none'}">
                    <label class="form-label">Header Name</label>
                    <input type="text" class="form-control" id="fonteApiAuthHeader" value="${escapeHtml(d.api_auth_header || 'Authorization')}">
                </div>
                <div class="col-md-12" id="fonteApiBasicField" style="display:${d.api_auth_tipo === 'basic' ? '' : 'none'}">
                    <div class="row g-2">
                        <div class="col-6"><input type="text" class="form-control" id="fonteApiAuthUser" value="${escapeHtml(!isDS ? (d.api_auth_user || '') : '')}" placeholder="Usuário"></div>
                        <div class="col-6"><input type="password" class="form-control" id="fonteApiAuthPass" value="${escapeHtml(d.api_auth_pass || '')}" placeholder="Senha"></div>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Endpoints (JSON)</label>
                    <textarea class="form-control font-monospace" id="fonteApiEndpoints" rows="5" style="font-size:12px">${escapeHtml(typeof d.api_endpoints === 'string' ? d.api_endpoints : JSON.stringify(d.api_endpoints || [], null, 2))}</textarea>
                </div>
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Descrição da API</label>
                <textarea class="form-control" id="fonteApiDescricao" rows="2">${escapeHtml(d.api_descricao || '')}</textarea>
            </div>
        </div>
        <!-- Comum -->
        <div class="form-group mt-3">
            <label class="form-label">Descrição (contexto para a IA)</label>
            <textarea class="form-control" id="fonteDescricao" rows="2" placeholder="Ex: Banco do ERP com dados de vendas, clientes e estoque">${escapeHtml(d.descricao || '')}</textarea>
        </div>`,
        `<button class="btn btn-primary" onclick="cbSalvarFonte()"><i class="fas fa-save"></i> Salvar</button>
         <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>`
    );
    setTimeout(() => cbFonteModalTipoChange(), 50);
}

function cbFonteModalTipoChange() {
    const tipo = document.getElementById('fonteTipo')?.value;
    const isAPI = tipo === 'api';
    const isDS = tipo === 'datasystem';
    const sqlEl = document.getElementById('fonteFieldsSQL');
    const apiEl = document.getElementById('fonteFieldsAPI');
    const dsEl = document.getElementById('fonteFieldsDS');
    if (sqlEl) sqlEl.style.display = (isAPI || isDS) ? 'none' : '';
    if (apiEl) apiEl.style.display = (isAPI && !isDS) ? '' : 'none';
    if (dsEl) dsEl.style.display = isDS ? '' : 'none';

    // Auto-fill alias and nome for DataSystem
    if (isDS) {
        const aliasEl = document.getElementById('fonteAlias');
        const nomeEl = document.getElementById('fonteNome');
        if (aliasEl && !aliasEl.value) aliasEl.value = 'datasystem';
        if (nomeEl && !nomeEl.value) nomeEl.value = 'DataSystem ERP';
    }
}

function cbFonteModalAuthChange() {
    const auth = document.getElementById('fonteApiAuthTipo')?.value;
    const keyField = document.getElementById('fonteApiKeyField');
    const headerField = document.getElementById('fonteApiHeaderField');
    const basicField = document.getElementById('fonteApiBasicField');
    if (keyField) keyField.style.display = ['bearer','apikey'].includes(auth) ? '' : 'none';
    if (headerField) headerField.style.display = auth === 'apikey' ? '' : 'none';
    if (basicField) basicField.style.display = auth === 'basic' ? '' : 'none';
}

async function cbSalvarFonte() {
    const tipoSelect = document.getElementById('fonteTipo')?.value;
    const isDS = tipoSelect === 'datasystem';

    const dados = {
        nome: document.getElementById('fonteNome')?.value?.trim(),
        alias: document.getElementById('fonteAlias')?.value?.trim().toLowerCase().replace(/[^a-z0-9_]/g, ''),
        tipo: isDS ? 'api' : tipoSelect,
        api_template: isDS ? 'datasystem' : '',
        db_host: document.getElementById('fonteDbHost')?.value?.trim(),
        db_port: document.getElementById('fonteDbPort')?.value?.trim(),
        db_name: document.getElementById('fonteDbName')?.value?.trim(),
        db_user: document.getElementById('fonteDbUser')?.value?.trim(),
        db_pass: document.getElementById('fonteDbPass')?.value,
        descricao: document.getElementById('fonteDescricao')?.value?.trim(),
        tabelas_permitidas: document.getElementById('fonteTabelasPermitidas')?.value?.trim(),
        max_rows: parseInt(document.getElementById('fonteMaxRows')?.value) || 50,
        api_url: isDS ? (document.getElementById('fonteDsUrl')?.value?.trim() || '') : (document.getElementById('fonteApiUrl')?.value?.trim() || ''),
        api_auth_tipo: isDS ? 'bearer' : (document.getElementById('fonteApiAuthTipo')?.value || 'none'),
        api_key: isDS ? (document.getElementById('fonteDsHash')?.value || '') : (document.getElementById('fonteApiKey')?.value || ''),
        api_auth_header: isDS ? '' : (document.getElementById('fonteApiAuthHeader')?.value?.trim() || ''),
        api_auth_user: isDS ? (document.getElementById('fonteDsCnpj')?.value?.trim() || '') : (document.getElementById('fonteApiAuthUser')?.value?.trim() || ''),
        api_auth_pass: isDS ? '' : (document.getElementById('fonteApiAuthPass')?.value || ''),
        api_endpoints: isDS ? '[]' : (document.getElementById('fonteApiEndpoints')?.value?.trim() || ''),
        api_descricao: isDS ? 'API DataSystem ERP - gestão de vendas, clientes, produtos, estoque, financeiro' : (document.getElementById('fonteApiDescricao')?.value?.trim() || ''),
    };

    if (!dados.nome || !dados.alias || !dados.tipo) {
        return HelpDesk.toast('Nome, alias e tipo são obrigatórios', 'warning');
    }
    if (isDS) {
        if (!dados.api_url || !dados.api_auth_user || !dados.api_key) {
            return HelpDesk.toast('URL, CNPJ e Hash são obrigatórios para DataSystem', 'warning');
        }
    }

    let action, payload;
    if (_fonteAtualId) {
        action = 'atualizar_fonte';
        payload = { action, id: _fonteAtualId, ...dados };
    } else {
        action = 'criar_fonte';
        payload = { action, ...dados };
    }

    const resp = await HelpDesk.api('POST', '/api/chatbot.php', payload);
    if (resp.success) {
        HelpDesk.toast(resp.message || 'Fonte salva!', 'success');
        HelpDesk.closeModal();
        cbLoadFontes();
    } else {
        HelpDesk.toast(resp.error || 'Erro ao salvar', 'danger');
    }
}

async function cbTestarFonte(id) {
    HelpDesk.toast('Testando conexão...', 'info');
    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'testar_fonte', id });
    if (resp.success && resp.data) {
        if (resp.data.success) {
            HelpDesk.toast('✅ ' + resp.data.message, 'success');
        } else {
            HelpDesk.toast('❌ ' + resp.data.message, 'danger');
        }
        cbLoadFontes();
    } else {
        HelpDesk.toast(resp.error || 'Erro', 'danger');
    }
}

async function cbToggleFonte(id) {
    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'toggle_fonte', id });
    if (resp.success) {
        cbLoadFontes();
    }
}

async function cbRemoverFonte(id) {
    if (!confirm('Remover esta fonte de dados?')) return;
    const resp = await HelpDesk.api('POST', '/api/chatbot.php', { action: 'remover_fonte', id });
    if (resp.success) {
        HelpDesk.toast('Fonte removida', 'success');
        cbLoadFontes();
        if (_fonteAtualId === id) {
            document.getElementById('fonteDetalhes').style.display = 'none';
            _fonteAtualId = null;
        }
    }
}

async function cbVerFonteSchema(id) {
    _fonteAtualId = id;
    const fonte = _fontes.find(f => f.id == id);
    const tipoLabels = { mysql: 'MySQL', pgsql: 'PostgreSQL', sqlserver: 'SQL Server', sqlite: 'SQLite', api: 'API REST' };
    const isDS = fonte && fonte.api_template === 'datasystem';
    document.getElementById('fonteDetalhes').style.display = '';
    document.getElementById('fonteDetalhesTitulo').innerHTML =
        '<i class="fas fa-project-diagram" style="color:var(--primary)"></i> ' + (fonte ? escapeHtml(fonte.nome) : 'Fonte') + ' <small style="color:var(--gray-400);font-weight:400">(' + (isDS ? '📦 DataSystem ERP' : (fonte ? (tipoLabels[fonte.tipo] || fonte.tipo) : '')) + ')</small>';
    cbLoadFonteSchema();
    cbLoadFonteRels();
    document.getElementById('fonteDetalhes').scrollIntoView({ behavior: 'smooth' });
}

async function cbLoadFonteSchema() {
    if (!_fonteAtualId) return;
    const container = document.getElementById('fonteSchemaContainer');
    container.innerHTML = '<div class="text-center py-4" style="color:var(--gray-400)"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

    try {
        const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'fonte_schema', id: _fonteAtualId });
        if (!resp.success) {
            container.innerHTML = '<div class="alert alert-danger">' + (resp.error || 'Erro') + '</div>';
            cbDiagramShowEmpty('Erro ao carregar schema');
            return;
        }
        _fonteSchema = resp.data || [];
        window._dbSchema = _fonteSchema; // Sync for diagram functions
        if (_fonteSchema.length === 0) {
            container.innerHTML = '<div class="text-center py-4" style="color:var(--gray-400)">Nenhuma tabela/endpoint encontrado</div>';
            cbDiagramShowEmpty('Nenhuma tabela encontrada');
            return;
        }

        // Render table list
        let html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr style="font-size:12px;color:var(--gray-600)"><th>Tabela</th><th>Colunas</th><th class="text-end">Registros</th><th class="text-center">Permitida</th></tr></thead><tbody>';
        _fonteSchema.forEach(t => {
            const cols = (t.columns || []).map(c => `<code style="font-size:11px;color:var(--gray-700)">${escapeHtml(c.Field)}</code> <small style="color:var(--gray-400)">${escapeHtml(c.Type)}</small>`).join(', ');
            html += `<tr>
                <td><strong style="color:var(--gray-800)">${escapeHtml(t.table)}</strong></td>
                <td style="font-size:12px">${cols}</td>
                <td class="text-end" style="color:var(--gray-500)">${t.row_count}</td>
                <td class="text-center">${t.permitted ? '<i class="fas fa-check-circle" style="color:var(--success)"></i>' : '<i class="fas fa-minus-circle" style="color:var(--gray-300)"></i>'}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;

        // Build interactive diagram
        cbBuildFonteDiagram(_fonteSchema.filter(t => t.permitted));

        // Popular dropdowns de relacionamentos
        cbPopulateFonteRelDropdowns();
    } catch (e) {
        container.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
    }
}

function cbDiagramShowEmpty(msg) {
    const canvas = document.getElementById('dbDiagramCanvas');
    const emptyEl = document.getElementById('dbDiagramEmpty');
    if (canvas) canvas.querySelectorAll('.db-table-card').forEach(el => el.remove());
    const svg = document.getElementById('dbDiagramSvg');
    if (svg) svg.innerHTML = '';
    if (emptyEl) {
        emptyEl.style.display = 'flex';
        emptyEl.innerHTML = '<i class="fas fa-project-diagram fa-3x mb-3" style="color:var(--gray-300)"></i><p style="color:var(--gray-400)">' + escapeHtml(msg || 'Sem dados') + '</p>';
    }
}

function cbBuildFonteDiagram(tables) {
    const canvas = document.getElementById('dbDiagramCanvas');
    const emptyEl = document.getElementById('dbDiagramEmpty');
    const svg = document.getElementById('dbDiagramSvg');
    if (!canvas) return;

    // Clear previous cards
    canvas.querySelectorAll('.db-table-card').forEach(el => el.remove());
    if (svg) svg.innerHTML = '';

    if (!tables || tables.length === 0) {
        if (emptyEl) {
            emptyEl.style.display = 'flex';
            emptyEl.innerHTML = '<i class="fas fa-project-diagram fa-3x mb-3" style="color:var(--gray-300)"></i><p style="color:var(--gray-400)">Nenhuma tabela permitida</p>';
        }
        return;
    }

    if (emptyEl) emptyEl.style.display = 'none';

    const savedPos = cbLoadTablePositions();
    const cols = Math.max(3, Math.ceil(Math.sqrt(tables.length)));
    const spacingX = 330;
    const spacingY = 330;

    tables.forEach((t, i) => {
        const card = document.createElement('div');
        card.className = 'db-table-card';
        card.id = 'dbTable_' + t.table;
        card.dataset.table = t.table;

        // Position
        if (savedPos && savedPos[t.table]) {
            card.style.left = savedPos[t.table].left + 'px';
            card.style.top  = savedPos[t.table].top + 'px';
        } else {
            card.style.left = (50 + (i % cols) * spacingX) + 'px';
            card.style.top  = (50 + Math.floor(i / cols) * spacingY) + 'px';
        }

        // Build columns
        const colsHtml = (t.columns || []).map(c => {
            const isPK = /^(id|codigo)$/i.test(c.Field) || c.Key === 'PRI';
            const isFK = /(_id|_cod|_codigo|id_|cod_)/i.test(c.Field) || c.Key === 'MUL';
            let cls = '', icon = '';
            if (isPK)      { cls = 'pk'; icon = '🔑'; }
            else if (isFK) { cls = 'fk'; icon = '🔗'; }
            else {
                const tp = (c.Type || '').toLowerCase();
                if (/int|serial/.test(tp))                         icon = '<small style="color:#484f58">I</small>';
                else if (/char|text|string/.test(tp))              icon = '<small style="color:#484f58">A</small>';
                else if (/date|time/.test(tp))                     icon = '<small style="color:#484f58">D</small>';
                else if (/bool/.test(tp))                          icon = '<small style="color:#484f58">B</small>';
                else if (/float|double|decimal|numeric/.test(tp))  icon = '<small style="color:#484f58">N</small>';
                else                                                icon = '<small style="color:#484f58">•</small>';
            }
            return `<div class="db-table-col ${cls}" data-col="${c.Field}">
                <span class="db-col-icon">${icon}</span>
                <span class="db-col-name">${c.Field}</span>
                <span class="db-col-type">${c.Type}</span>
            </div>`;
        }).join('');

        card.innerHTML = `
            <div class="db-table-header">
                <span><i class="fas fa-table" style="opacity:0.7"></i> ${t.table}</span>
                <span class="db-table-count">${t.row_count ?? '?'} reg</span>
            </div>
            <div class="db-table-body">${colsHtml}</div>
        `;

        canvas.appendChild(card);
        cbInitTableDrag(card);
    });

    cbUpdateCanvasSize();

    // Draw relationships (data loaded by cbLoadFonteRels called in parallel)
    // Give a small delay to let cbLoadFonteRels finish if still loading
    setTimeout(() => cbDrawRelLines(), 300);
}

function cbPopulateFonteRelDropdowns() {
    if (!_fonteSchema) return;
    const tables = _fonteSchema.filter(t => t.permitted).map(t => t.table);
    ['fRelTabelaOrigem', 'fRelTabelaRef'].forEach(selId => {
        const sel = document.getElementById(selId);
        if (!sel) return;
        sel.innerHTML = '<option value="">Tabela...</option>' + tables.map(t => `<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');
    });
}

function cbOnFRelTabelaOrigemChange() {
    const table = document.getElementById('fRelTabelaOrigem')?.value;
    cbPopulateFonteRelCols('fRelColunaOrigem', table);
}
function cbOnFRelTabelaRefChange() {
    const table = document.getElementById('fRelTabelaRef')?.value;
    cbPopulateFonteRelCols('fRelColunaRef', table);
    cbPopulateFonteRelCols('fRelColunaDescricao', table);
}
function cbPopulateFonteRelCols(selId, table) {
    const sel = document.getElementById(selId);
    if (!sel || !table) { if(sel) sel.innerHTML = '<option value="">Coluna...</option>'; return; }
    const t = (_fonteSchema || []).find(t => t.table === table);
    if (!t) return;
    sel.innerHTML = '<option value="">Coluna...</option>' + (t.columns || []).map(c => `<option value="${escapeHtml(c.Field)}">${escapeHtml(c.Field)}</option>`).join('');
}

async function cbLoadFonteRels() {
    if (!_fonteAtualId) return;
    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'fonte_relacionamentos', id: _fonteAtualId });
    _fonteRels = resp.success ? (resp.data || []) : [];
    window._dbRelacionamentos = [..._fonteRels];
    document.getElementById('badgeFonteRelCount').textContent = _fonteRels.length;
    cbRenderFonteRels();
    cbDrawRelLines();
}

function cbRenderFonteRels() {
    const container = document.getElementById('fonteRelListContainer');
    if (!container) return;
    // Keep arrays in sync
    _fonteRels = [...window._dbRelacionamentos];
    const badge = document.getElementById('badgeFonteRelCount');
    if (badge) badge.textContent = _fonteRels.length;

    if (!_fonteRels.length) {
        container.innerHTML = '<div class="text-center py-3" style="color:var(--gray-400)"><small>Nenhum relacionamento</small></div>';
        return;
    }
    let html = '';
    _fonteRels.forEach((r, i) => {
        html += `<div class="d-flex align-items-center justify-content-between py-2 px-3" style="font-size:12px;border-bottom:1px solid var(--gray-100);background:${i % 2 === 0 ? '#fff' : 'var(--gray-50)'}">
            <span>
                <code style="color:var(--primary);font-weight:600">${escapeHtml(r.tabela)}.${escapeHtml(r.coluna)}</code>
                <i class="fas fa-arrow-right mx-2" style="color:var(--gray-400);font-size:10px"></i>
                <code style="color:var(--success);font-weight:600">${escapeHtml(r.ref_tabela)}.${escapeHtml(r.ref_coluna)}</code>
                ${r.coluna_descricao ? ' <small style="color:var(--gray-400)">(→ '+escapeHtml(r.coluna_descricao)+')</small>' : ''}
            </span>
            <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="cbRemoverRelacionamento(${i})" title="Remover"><i class="fas fa-times"></i></button>
        </div>`;
    });
    container.innerHTML = html;
}

function cbAddFonteRel() {
    const tabela = document.getElementById('fRelTabelaOrigem')?.value;
    const coluna = document.getElementById('fRelColunaOrigem')?.value;
    const ref_tabela = document.getElementById('fRelTabelaRef')?.value;
    const ref_coluna = document.getElementById('fRelColunaRef')?.value;
    const coluna_descricao = document.getElementById('fRelColunaDescricao')?.value;
    if (!tabela || !coluna || !ref_tabela || !ref_coluna) return HelpDesk.toast('Preencha todos os campos', 'warning');

    const existe = window._dbRelacionamentos.find(r =>
        r.tabela === tabela && r.coluna === coluna && r.ref_tabela === ref_tabela && r.ref_coluna === ref_coluna
    );
    if (existe) return HelpDesk.toast('Esse relacionamento já existe', 'warning');

    const rel = { tabela, coluna, ref_tabela, ref_coluna, coluna_descricao: coluna_descricao || '' };
    window._dbRelacionamentos.push(rel);
    _fonteRels = [...window._dbRelacionamentos];
    cbRenderFonteRels();
    cbDrawRelLines();
    HelpDesk.toast('Relacionamento adicionado! Clique em "Salvar" para persistir.', 'info');
}

function cbLimparFonteRels() {
    if (!confirm('Limpar todos os relacionamentos?')) return;
    window._dbRelacionamentos = [];
    _fonteRels = [];
    cbRenderFonteRels();
    cbDrawRelLines();
    cbSalvarFonteRels();
}

async function cbSalvarFonteRels() {
    if (!_fonteAtualId) return;
    _fonteRels = [...window._dbRelacionamentos];
    const resp = await HelpDesk.api('POST', '/api/chatbot.php', {
        action: 'salvar_fonte_relacionamentos',
        id: _fonteAtualId,
        relacionamentos: _fonteRels,
    });
    if (resp.success) {
        HelpDesk.toast('Relacionamentos salvos!', 'success');
    } else {
        HelpDesk.toast(resp.error || 'Erro', 'danger');
    }
}

// ---- TESTAR IA ----
async function cbEnviarTeste() {
    const input = document.getElementById('testeMsgInput');
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    const btn = document.getElementById('btnEnviarTeste');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Adicionar mensagem do usuário ao chat
    const chatEl = document.getElementById('testeChat');
    const emptyMsg = chatEl.querySelector('.text-center.text-muted');
    if (emptyMsg) emptyMsg.remove();

    chatEl.innerHTML += `<div style="display:flex;justify-content:flex-end;margin-bottom:8px">
        <div style="max-width:75%;padding:8px 12px;border-radius:12px;background:#dcf8c6;box-shadow:0 1px 2px rgba(0,0,0,0.1)">
            <small style="color:#888"><i class="fas fa-user"></i> Você</small>
            <div style="margin-top:4px">${escapeHtml(msg)}</div>
        </div>
    </div>`;
    chatEl.scrollTop = chatEl.scrollHeight;

    // Indicador de digitação
    chatEl.innerHTML += `<div id="typingIndicator" style="display:flex;justify-content:flex-start;margin-bottom:8px">
        <div style="padding:8px 12px;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,0.1)">
            <i class="fas fa-circle-notch fa-spin"></i> Pensando...
        </div>
    </div>`;
    chatEl.scrollTop = chatEl.scrollHeight;

    try {
        const resp = await HelpDesk.api('POST', '/api/chatbot.php', {
            action: 'test_ia',
            mensagem: msg,
            sessao_id: cbTesteSessaoId
        });

        // Remover indicador de digitação
        const typing = document.getElementById('typingIndicator');
        if (typing) typing.remove();

        if (resp.success && resp.data) {
            cbTesteSessaoId = resp.data.sessao_id;

            const sqlInfo = resp.data.sql ? `<div style="margin-top:4px;padding:4px 8px;background:#f0f0f0;border-radius:4px;font-size:11px;font-family:monospace">SQL: ${escapeHtml(resp.data.sql)}</div>` : '';

            chatEl.innerHTML += `<div style="display:flex;justify-content:flex-start;margin-bottom:8px">
                <div style="max-width:75%;padding:8px 12px;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,0.1)">
                    <small style="color:#888"><i class="fas fa-robot"></i> Bot</small>
                    <div style="margin-top:4px;white-space:pre-wrap">${escapeHtml(resp.data.resposta)}</div>
                    ${sqlInfo}
                    <small style="color:#999;font-size:10px">${resp.data.duracao_ms}ms | ${resp.data.tokens} tokens | ${resp.data.modelo}</small>
                </div>
            </div>`;

            // Atualizar stats
            document.getElementById('testeStats').innerHTML = `
                <p class="mb-1"><strong>Último teste:</strong></p>
                <p class="mb-1">Modelo: <code>${resp.data.modelo}</code></p>
                <p class="mb-1">Tokens: ${resp.data.tokens}</p>
                <p class="mb-1">Tempo: ${resp.data.duracao_ms}ms</p>
                ${resp.data.sql ? `<p class="mb-1">SQL: <code style="font-size:11px">${escapeHtml(resp.data.sql)}</code></p>` : ''}
            `;
        } else {
            chatEl.innerHTML += `<div style="display:flex;justify-content:flex-start;margin-bottom:8px">
                <div style="padding:8px 12px;border-radius:12px;background:#ffe0e0">
                    <i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> ${escapeHtml(resp.error || 'Erro desconhecido')}
                </div>
            </div>`;
        }
    } catch (e) {
        const typing = document.getElementById('typingIndicator');
        if (typing) typing.remove();
        chatEl.innerHTML += `<div style="display:flex;justify-content:flex-start;margin-bottom:8px">
            <div style="padding:8px 12px;border-radius:12px;background:#ffe0e0">
                <i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> Erro: ${e.message}
            </div>
        </div>`;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
    chatEl.scrollTop = chatEl.scrollHeight;
}

// ---- UTILITÁRIOS ----
function cbCopyWebhook() {
    const url = document.getElementById('webhookUrlDisplay').value;
    navigator.clipboard.writeText(url).then(() => {
        HelpDesk.toast('URL copiada!', 'success');
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================
//  DIAGRAMA INTERATIVO
// ============================================================

window._dbSchema = [];
window._dbRelacionamentos = [];
window._diagramZoom = 1;
let _dragState = null;
let _panState = null;
let _linkMode = false;
let _linkSource = null; // { table, column, element }

// ---- TABLE DRAG SYSTEM ----
function cbInitTableDrag(el) {
    const header = el.querySelector('.db-table-header');
    header.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        e.preventDefault();
        e.stopPropagation();
        _dragState = {
            el: el,
            startX: e.clientX,
            startY: e.clientY,
            origLeft: parseInt(el.style.left) || 0,
            origTop: parseInt(el.style.top) || 0,
            zoom: window._diagramZoom || 1
        };
        el.classList.add('dragging');
        document.addEventListener('mousemove', cbOnDragMove);
        document.addEventListener('mouseup', cbOnDragEnd);
    });
    // Touch support
    header.addEventListener('touchstart', (e) => {
        const touch = e.touches[0];
        _dragState = {
            el: el,
            startX: touch.clientX,
            startY: touch.clientY,
            origLeft: parseInt(el.style.left) || 0,
            origTop: parseInt(el.style.top) || 0,
            zoom: window._diagramZoom || 1
        };
        el.classList.add('dragging');
        document.addEventListener('touchmove', cbOnTouchDragMove, { passive: false });
        document.addEventListener('touchend', cbOnTouchDragEnd);
    }, { passive: true });
}

function cbOnDragMove(e) {
    if (!_dragState) return;
    const dx = (e.clientX - _dragState.startX) / _dragState.zoom;
    const dy = (e.clientY - _dragState.startY) / _dragState.zoom;
    _dragState.el.style.left = Math.max(0, _dragState.origLeft + dx) + 'px';
    _dragState.el.style.top  = Math.max(0, _dragState.origTop + dy) + 'px';
    cbDrawRelLines();
}

function cbOnDragEnd() {
    if (!_dragState) return;
    _dragState.el.classList.remove('dragging');
    _dragState = null;
    document.removeEventListener('mousemove', cbOnDragMove);
    document.removeEventListener('mouseup', cbOnDragEnd);
    cbUpdateCanvasSize();
    cbSaveTablePositions();
}

function cbOnTouchDragMove(e) {
    if (!_dragState) return;
    e.preventDefault();
    const touch = e.touches[0];
    const dx = (touch.clientX - _dragState.startX) / _dragState.zoom;
    const dy = (touch.clientY - _dragState.startY) / _dragState.zoom;
    _dragState.el.style.left = Math.max(0, _dragState.origLeft + dx) + 'px';
    _dragState.el.style.top  = Math.max(0, _dragState.origTop + dy) + 'px';
    cbDrawRelLines();
}

function cbOnTouchDragEnd() {
    if (!_dragState) return;
    _dragState.el.classList.remove('dragging');
    _dragState = null;
    document.removeEventListener('touchmove', cbOnTouchDragMove);
    document.removeEventListener('touchend', cbOnTouchDragEnd);
    cbUpdateCanvasSize();
    cbSaveTablePositions();
}

// ---- CANVAS PAN (drag background to scroll) ----
function cbInitCanvasPan() {
    const wrapper = document.getElementById('dbDiagramWrapper');
    if (!wrapper) return;

    wrapper.addEventListener('mousedown', (e) => {
        if (e.target.closest('.db-table-card') || e.target.closest('.db-diagram-toolbar') || e.button !== 0) return;
        _panState = { startX: e.clientX, startY: e.clientY, scrollLeft: wrapper.scrollLeft, scrollTop: wrapper.scrollTop };
        wrapper.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', (e) => {
        if (!_panState) return;
        wrapper.scrollLeft = _panState.scrollLeft - (e.clientX - _panState.startX);
        wrapper.scrollTop  = _panState.scrollTop  - (e.clientY - _panState.startY);
    });

    document.addEventListener('mouseup', () => {
        if (_panState) {
            _panState = null;
            const w = document.getElementById('dbDiagramWrapper');
            if (w) w.style.cursor = 'grab';
        }
    });

    // Ctrl+Scroll to zoom
    wrapper.addEventListener('wheel', (e) => {
        if (e.ctrlKey) {
            e.preventDefault();
            cbDiagramZoom(e.deltaY > 0 ? -0.1 : 0.1);
        }
    }, { passive: false });
}

// ---- ZOOM ----
function cbDiagramZoom(delta) {
    const canvas = document.getElementById('dbDiagramCanvas');
    if (!canvas) return;
    if (delta === 0) {
        window._diagramZoom = 1;
    } else {
        window._diagramZoom = Math.max(0.3, Math.min(2.5, window._diagramZoom + delta));
    }
    canvas.style.transform = 'scale(' + window._diagramZoom + ')';
    canvas.style.transformOrigin = '0 0';
    const pct = Math.round(window._diagramZoom * 100) + '%';
    const lbl = document.getElementById('lblZoom');
    const lblFS = document.getElementById('lblZoomFS');
    if (lbl) lbl.textContent = pct;
    if (lblFS) lblFS.textContent = pct;
}

// ---- FULLSCREEN ----
function cbToggleFullscreen() {
    const wrapper = document.getElementById('dbDiagramWrapper');
    const icon = document.getElementById('btnFullscreenIcon');
    if (!wrapper) return;
    wrapper.classList.toggle('db-diagram-fullscreen');
    const isFS = wrapper.classList.contains('db-diagram-fullscreen');
    if (icon) icon.className = isFS ? 'fas fa-compress' : 'fas fa-expand';
    if (isFS) {
        document.addEventListener('keydown', cbFullscreenEsc);
        document.body.style.overflow = 'hidden';
    } else {
        document.removeEventListener('keydown', cbFullscreenEsc);
        document.body.style.overflow = '';
    }
    setTimeout(cbDrawRelLines, 100);
}

function cbFullscreenEsc(e) {
    if (e.key === 'Escape') cbToggleFullscreen();
}

// ---- AUTO LAYOUT ----
function cbAutoLayoutDiagram() {
    const canvas = document.getElementById('dbDiagramCanvas');
    if (!canvas) return;
    const cards = canvas.querySelectorAll('.db-table-card');
    if (cards.length === 0) return;

    const cols = Math.max(3, Math.ceil(Math.sqrt(cards.length)));
    const spacingX = 330;
    const spacingY = 330;

    cards.forEach((el, i) => {
        const col = i % cols;
        const row = Math.floor(i / cols);
        el.style.transition = 'left 0.4s ease, top 0.4s ease';
        el.style.left = (50 + col * spacingX) + 'px';
        el.style.top  = (50 + row * spacingY) + 'px';
        setTimeout(() => { el.style.transition = ''; }, 500);
    });

    setTimeout(() => {
        cbUpdateCanvasSize();
        cbSaveTablePositions();
        cbDrawRelLines();
    }, 450);
}

// ---- CANVAS SIZE ----
function cbUpdateCanvasSize() {
    const canvas = document.getElementById('dbDiagramCanvas');
    if (!canvas) return;
    let maxX = 800, maxY = 600;
    canvas.querySelectorAll('.db-table-card').forEach(el => {
        const r = (parseInt(el.style.left) || 0) + el.offsetWidth + 150;
        const b = (parseInt(el.style.top) || 0) + el.offsetHeight + 150;
        if (r > maxX) maxX = r;
        if (b > maxY) maxY = b;
    });
    canvas.style.width  = maxX + 'px';
    canvas.style.height = maxY + 'px';
    const svg = document.getElementById('dbDiagramSvg');
    if (svg) {
        svg.setAttribute('width', maxX);
        svg.setAttribute('height', maxY);
    }
}

// ---- SVG RELATIONSHIP LINES ----
function cbDrawRelLines() {
    const svg = document.getElementById('dbDiagramSvg');
    if (!svg) return;

    // Rebuild SVG
    const ns = 'http://www.w3.org/2000/svg';
    svg.innerHTML = '';

    // Defs: arrowhead + glow
    const defs = document.createElementNS(ns, 'defs');
    defs.innerHTML = `
        <marker id="arrowFK" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto">
            <polygon points="0 0, 8 3, 0 6" fill="#58a6ff" opacity="0.8"/>
        </marker>
        <filter id="glowLine" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="2.5" result="g"/>
            <feMerge><feMergeNode in="g"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
    `;
    svg.appendChild(defs);

    const rels = window._dbRelacionamentos || [];
    if (rels.length === 0) return;

    rels.forEach(rel => {
        const srcEl = document.getElementById('dbTable_' + rel.tabela);
        const tgtEl = document.getElementById('dbTable_' + rel.ref_tabela);
        if (!srcEl || !tgtEl) return;

        const src = { x: parseInt(srcEl.style.left)||0, y: parseInt(srcEl.style.top)||0, w: srcEl.offsetWidth, h: srcEl.offsetHeight };
        const tgt = { x: parseInt(tgtEl.style.left)||0, y: parseInt(tgtEl.style.top)||0, w: tgtEl.offsetWidth, h: tgtEl.offsetHeight };

        const srcCx = src.x + src.w/2, srcCy = src.y + src.h/2;
        const tgtCx = tgt.x + tgt.w/2, tgtCy = tgt.y + tgt.h/2;
        const dx = tgtCx - srcCx, dy = tgtCy - srcCy;
        let x1, y1, x2, y2;

        // Connect from nearest edges
        if (Math.abs(dx) >= Math.abs(dy)) {
            if (dx >= 0) { x1 = src.x + src.w; y1 = srcCy; x2 = tgt.x; y2 = tgtCy; }
            else          { x1 = src.x; y1 = srcCy; x2 = tgt.x + tgt.w; y2 = tgtCy; }
        } else {
            if (dy >= 0) { x1 = srcCx; y1 = src.y + src.h; x2 = tgtCx; y2 = tgt.y; }
            else          { x1 = srcCx; y1 = src.y; x2 = tgtCx; y2 = tgt.y + tgt.h; }
        }

        // Bezier control points
        const cpOff = Math.min(Math.max(Math.abs(dx), Math.abs(dy)) * 0.4, 120);
        let cx1 = x1, cy1 = y1, cx2 = x2, cy2 = y2;
        if (Math.abs(dx) >= Math.abs(dy)) {
            cx1 = dx >= 0 ? x1 + cpOff : x1 - cpOff;
            cx2 = dx >= 0 ? x2 - cpOff : x2 + cpOff;
        } else {
            cy1 = dy >= 0 ? y1 + cpOff : y1 - cpOff;
            cy2 = dy >= 0 ? y2 - cpOff : y2 + cpOff;
        }

        const d = `M ${x1} ${y1} C ${cx1} ${cy1}, ${cx2} ${cy2}, ${x2} ${y2}`;

        // Glow (wide, behind)
        const glow = document.createElementNS(ns, 'path');
        glow.setAttribute('d', d);
        glow.setAttribute('fill', 'none');
        glow.setAttribute('stroke', '#58a6ff');
        glow.setAttribute('stroke-width', '5');
        glow.setAttribute('opacity', '0.1');
        glow.setAttribute('filter', 'url(#glowLine)');
        svg.appendChild(glow);

        // Main line
        const path = document.createElementNS(ns, 'path');
        path.setAttribute('d', d);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', '#58a6ff');
        path.setAttribute('stroke-width', '1.5');
        path.setAttribute('opacity', '0.7');
        path.setAttribute('marker-end', 'url(#arrowFK)');
        svg.appendChild(path);

        // Label at midpoint
        const midX = (x1 + x2) / 2;
        const midY = (y1 + y2) / 2;
        const labelText = rel.coluna + ' → ' + rel.ref_coluna + (rel.coluna_descricao ? ' (' + rel.coluna_descricao + ')' : '');
        const textW = labelText.length * 5.5 + 16;

        const bg = document.createElementNS(ns, 'rect');
        bg.setAttribute('x', midX - textW/2);
        bg.setAttribute('y', midY - 18);
        bg.setAttribute('width', textW);
        bg.setAttribute('height', 16);
        bg.setAttribute('rx', 4);
        bg.setAttribute('fill', '#161b22');
        bg.setAttribute('stroke', '#30363d');
        bg.setAttribute('stroke-width', '0.5');
        bg.setAttribute('opacity', '0.9');
        svg.appendChild(bg);

        const text = document.createElementNS(ns, 'text');
        text.setAttribute('x', midX);
        text.setAttribute('y', midY - 7);
        text.setAttribute('fill', '#8b949e');
        text.setAttribute('font-size', '9');
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-family', 'SFMono-Regular, Consolas, monospace');
        text.textContent = labelText;
        svg.appendChild(text);
    });
}

// ---- SAVE/LOAD TABLE POSITIONS (localStorage) ----
function cbGetPositionKey() {
    if (_fonteAtualId) {
        const fonte = _fontes.find(f => f.id == _fonteAtualId);
        return 'cbDiagram_fonte_' + (fonte ? fonte.alias : _fonteAtualId);
    }
    return 'cbDiagram_default';
}

function cbSaveTablePositions() {
    const canvas = document.getElementById('dbDiagramCanvas');
    if (!canvas) return;
    const pos = {};
    canvas.querySelectorAll('.db-table-card').forEach(el => {
        pos[el.dataset.table] = { left: parseInt(el.style.left)||0, top: parseInt(el.style.top)||0 };
    });
    try { localStorage.setItem(cbGetPositionKey(), JSON.stringify(pos)); } catch(e) {}
}

function cbLoadTablePositions() {
    try {
        const d = localStorage.getItem(cbGetPositionKey());
        return d ? JSON.parse(d) : null;
    } catch(e) { return null; }
}

// Init canvas pan on load
document.addEventListener('DOMContentLoaded', cbInitCanvasPan);

// ============================================================
//  LINK MODE (click-to-relate)
// ============================================================

function cbToggleLinkMode() {
    _linkMode = !_linkMode;
    const wrapper = document.getElementById('dbDiagramWrapper');
    const status  = document.getElementById('dbLinkStatus');
    const btn     = document.getElementById('btnLinkMode');
    const btnFS   = document.getElementById('btnLinkModeFS');

    if (_linkMode) {
        wrapper.classList.add('link-mode');
        status.classList.add('active');
        if (btn) btn.classList.add('btn-link-active');
        if (btnFS) btnFS.classList.add('btn-link-active');
        _linkSource = null;
        cbUpdateLinkStatus();
        // Attach click listeners to all columns
        document.querySelectorAll('#dbDiagramCanvas .db-table-col').forEach(el => {
            el.addEventListener('click', cbOnColumnClick);
        });
    } else {
        cbCancelLinkMode();
    }
}

function cbCancelLinkMode() {
    _linkMode = false;
    _linkSource = null;
    const wrapper = document.getElementById('dbDiagramWrapper');
    const status  = document.getElementById('dbLinkStatus');
    const btn     = document.getElementById('btnLinkMode');
    const btnFS   = document.getElementById('btnLinkModeFS');

    if (wrapper) wrapper.classList.remove('link-mode');
    if (status) status.classList.remove('active');
    if (btn) btn.classList.remove('btn-link-active');
    if (btnFS) btnFS.classList.remove('btn-link-active');

    // Remove highlights
    document.querySelectorAll('.link-selected').forEach(el => el.classList.remove('link-selected'));
    document.querySelectorAll('.link-source').forEach(el => el.classList.remove('link-source'));

    // Remove click listeners
    document.querySelectorAll('#dbDiagramCanvas .db-table-col').forEach(el => {
        el.removeEventListener('click', cbOnColumnClick);
    });
}

function cbUpdateLinkStatus() {
    const textEl = document.getElementById('linkStatusText');
    const badge1 = document.getElementById('linkStep1');
    if (!_linkSource) {
        badge1.className = 'step-badge';
        badge1.textContent = '1';
        textEl.innerHTML = 'Clique na coluna <strong>FK</strong> da tabela de origem';
    } else {
        badge1.className = 'step-badge done';
        badge1.textContent = '✓';
        textEl.innerHTML = '<strong>' + _linkSource.table + '.' + _linkSource.column + '</strong> → Agora clique na coluna <strong>PK</strong> da tabela destino';
    }
}

function cbOnColumnClick(e) {
    e.stopPropagation();
    e.preventDefault();
    if (!_linkMode) return;

    const colEl = e.currentTarget;
    const card  = colEl.closest('.db-table-card');
    if (!card) return;

    const tableName = card.dataset.table;
    const colName   = colEl.dataset.col;

    if (!_linkSource) {
        // Step 1: select source (FK)
        _linkSource = { table: tableName, column: colName, element: colEl };
        colEl.classList.add('link-selected');
        card.classList.add('link-source');
        cbUpdateLinkStatus();
    } else {
        // Step 2: select target (PK) - must be different table
        if (tableName === _linkSource.table) {
            HelpDesk.toast('Selecione uma coluna de outra tabela!', 'warning');
            return;
        }

        const target = { table: tableName, column: colName };

        // Check duplicate
        const exists = window._dbRelacionamentos.find(r =>
            r.tabela === _linkSource.table && r.coluna === _linkSource.column &&
            r.ref_tabela === target.table && r.ref_coluna === target.column
        );
        if (exists) {
            HelpDesk.toast('Esse relacionamento já existe!', 'warning');
            cbCancelLinkMode();
            return;
        }

        // Show modal for description column
        cbShowLinkModal(_linkSource, target);
    }
}

function cbShowLinkModal(source, target) {
    // Find columns of target table for description select
    const tSchema = window._dbSchema.find(t => t.table === target.table);
    const nomeCols = ['nome', 'name', 'descricao', 'description', 'titulo', 'title', 'razao_social', 'nome_fantasia', 'label', 'denominacao'];
    let optionsHtml = '<option value="">(nenhuma - usar só o ID)</option>';
    let autoVal = '';
    if (tSchema) {
        tSchema.columns.forEach(c => {
            const isNome = nomeCols.some(n => c.Field.toLowerCase().includes(n));
            if (isNome && !autoVal) autoVal = c.Field;
            optionsHtml += `<option value="${c.Field}" ${isNome ? 'style="color:#7ee787;font-weight:bold"' : ''}>${c.Field}${isNome ? ' ✓' : ''}</option>`;
        });
    }

    const overlay = document.createElement('div');
    overlay.className = 'db-link-modal-overlay';
    overlay.id = 'dbLinkModalOverlay';
    overlay.innerHTML = `
        <div class="db-link-modal">
            <h5><i class="fas fa-link"></i> Novo Relacionamento</h5>
            <div class="rel-summary">
                <code>${source.table}</code>.<strong>${source.column}</strong>
                <span class="arrow">→</span>
                <code>${target.table}</code>.<strong>${target.column}</strong>
            </div>
            <label>Coluna com nome legível (da tabela <strong style="color:#58a6ff">${target.table}</strong>):</label>
            <select id="linkModalDescCol">${optionsHtml}</select>
            <small style="color:#484f58;display:block;margin-top:6px">A IA usará esta coluna para mostrar nomes em vez de IDs.</small>
            <div class="btn-row">
                <button class="btn btn-sm" style="border-color:#30363d;color:#8b949e" onclick="cbCloseLinkModal(false)">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-sm" style="background:#2ea043;color:#fff;border:none" onclick="cbCloseLinkModal(true)">
                    <i class="fas fa-check"></i> Criar Relacionamento
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    // Auto-select name column
    const sel = document.getElementById('linkModalDescCol');
    if (autoVal && sel) sel.value = autoVal;

    // Store pending data
    window._pendingLink = { source, target };

    // Close on overlay click
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) cbCloseLinkModal(false);
    });

    // Close on Escape
    const escHandler = (e) => { if (e.key === 'Escape') { cbCloseLinkModal(false); document.removeEventListener('keydown', escHandler); } };
    document.addEventListener('keydown', escHandler);
}

function cbCloseLinkModal(confirm) {
    const overlay = document.getElementById('dbLinkModalOverlay');

    if (confirm && window._pendingLink) {
        const descCol = document.getElementById('linkModalDescCol')?.value || '';
        const { source, target } = window._pendingLink;

        const rel = {
            tabela: source.table,
            coluna: source.column,
            ref_tabela: target.table,
            ref_coluna: target.column,
            coluna_descricao: descCol
        };

        // Add to both systems
        window._dbRelacionamentos.push(rel);
        _fonteRels.push(rel);

        cbRenderRelacionamentos();
        cbDrawRelLines();

        // Auto-save via fonte endpoint
        cbSalvarRelacionamentos();
        HelpDesk.toast('✅ Relacionamento criado: ' + source.table + '.' + source.column + ' → ' + target.table + '.' + target.column, 'success');
    }

    // Cleanup
    if (overlay) overlay.remove();
    window._pendingLink = null;
    cbCancelLinkMode();
}

// ============================================================
//  RELACIONAMENTOS (rewired for multi-fonte)
// ============================================================

function cbPreencherSelectsRelacionamento(tables) {
    const selOrigem = document.getElementById('relTabelaOrigem');
    const selRef = document.getElementById('relTabelaRef');
    if (!selOrigem || !selRef) return;
    const opts = '<option value="">Tabela...</option>' + tables.map(t => `<option value="${t.table}">${t.table}</option>`).join('');
    selOrigem.innerHTML = opts;
    selRef.innerHTML = opts;
    const colOrigem = document.getElementById('relColunaOrigem');
    const colRef = document.getElementById('relColunaRef');
    const colDesc = document.getElementById('relColunaDescricao');
    if (colOrigem) colOrigem.innerHTML = '<option value="">Coluna...</option>';
    if (colRef)    colRef.innerHTML = '<option value="">Coluna (PK)...</option>';
    if (colDesc)   colDesc.innerHTML = '<option value="">Selecione tabela ref primeiro...</option>';
}

function cbOnRelTabelaOrigemChange() {
    const tabela = document.getElementById('relTabelaOrigem')?.value;
    const sel = document.getElementById('relColunaOrigem');
    if (!sel || !tabela || !window._dbSchema) { if(sel) sel.innerHTML = '<option value="">Coluna...</option>'; return; }
    const t = window._dbSchema.find(x => x.table === tabela);
    if (!t) return;
    sel.innerHTML = '<option value="">Coluna...</option>' + t.columns.map(c => {
        const isFK = /(_id|_cod|_codigo|id_|cod_)$/i.test(c.Field);
        return `<option value="${c.Field}" ${isFK ? 'style="color:var(--primary);font-weight:bold"' : ''}>${c.Field} (${c.Type})${isFK ? ' 🔗' : ''}</option>`;
    }).join('');
}

function cbOnRelTabelaRefChange() {
    const tabela = document.getElementById('relTabelaRef')?.value;
    const selPK = document.getElementById('relColunaRef');
    const selDesc = document.getElementById('relColunaDescricao');
    if (!selPK || !tabela || !window._dbSchema) {
        if(selPK)  selPK.innerHTML = '<option value="">Coluna (PK)...</option>';
        if(selDesc) selDesc.innerHTML = '<option value="">Selecione tabela ref primeiro...</option>';
        return;
    }
    const t = window._dbSchema.find(x => x.table === tabela);
    if (!t) return;

    selPK.innerHTML = '<option value="">Coluna (PK)...</option>' + t.columns.map(c => {
        const isPK = c.Field.toLowerCase() === 'id' || c.Key === 'PRI';
        return `<option value="${c.Field}" ${isPK ? 'selected' : ''}>${c.Field}${isPK ? ' 🔑' : ''}</option>`;
    }).join('');

    const nomeCols = ['nome', 'name', 'descricao', 'description', 'titulo', 'title', 'razao_social', 'nome_fantasia', 'label', 'denominacao'];
    selDesc.innerHTML = '<option value="">(opcional)</option>' + t.columns.map(c => {
        const isNome = nomeCols.some(n => c.Field.toLowerCase().includes(n));
        return `<option value="${c.Field}" ${isNome ? 'style="color:green;font-weight:bold"' : ''}>${c.Field}${isNome ? ' ✓' : ''}</option>`;
    }).join('');
    const autoNome = t.columns.find(c => nomeCols.some(n => c.Field.toLowerCase() === n));
    if (autoNome) selDesc.value = autoNome.Field;
}

function cbAddRelacionamento() {
    const tOrigem = document.getElementById('relTabelaOrigem')?.value;
    const cOrigem = document.getElementById('relColunaOrigem')?.value;
    const tRef = document.getElementById('relTabelaRef')?.value;
    const cRef = document.getElementById('relColunaRef')?.value;
    const cDesc = document.getElementById('relColunaDescricao')?.value;

    if (!tOrigem || !cOrigem || !tRef || !cRef) {
        HelpDesk.toast('Preencha tabela e coluna de origem + tabela e coluna de referência', 'warning');
        return;
    }

    const existe = window._dbRelacionamentos.find(r =>
        r.tabela === tOrigem && r.coluna === cOrigem && r.ref_tabela === tRef && r.ref_coluna === cRef
    );
    if (existe) {
        HelpDesk.toast('Esse relacionamento já existe', 'warning');
        return;
    }

    const rel = {
        tabela: tOrigem,
        coluna: cOrigem,
        ref_tabela: tRef,
        ref_coluna: cRef,
        coluna_descricao: cDesc || ''
    };

    window._dbRelacionamentos.push(rel);
    _fonteRels.push(rel);

    cbRenderRelacionamentos();
    HelpDesk.toast('Relacionamento adicionado! Clique em "Salvar" para persistir.', 'info');
}

function cbRemoverRelacionamento(idx) {
    window._dbRelacionamentos.splice(idx, 1);
    _fonteRels = [...window._dbRelacionamentos];
    cbRenderRelacionamentos();
}

function cbRenderRelacionamentos() {
    // Sync arrays
    _fonteRels = [...window._dbRelacionamentos];

    // Update badges
    const badge = document.getElementById('badgeRelCount');
    if (badge) badge.textContent = window._dbRelacionamentos.length;

    // Render via the unified fonte renderer
    cbRenderFonteRels();

    // Redesenhar linhas do diagrama
    cbDrawRelLines();
}

async function cbSalvarRelacionamentos() {
    if (!_fonteAtualId) {
        HelpDesk.toast('Selecione uma fonte primeiro', 'warning');
        return;
    }
    try {
        const resp = await HelpDesk.api('POST', '/api/chatbot.php', {
            action: 'salvar_fonte_relacionamentos',
            id: _fonteAtualId,
            relacionamentos: window._dbRelacionamentos
        });
        if (resp.success) {
            HelpDesk.toast('Relacionamentos salvos! O chatbot usará estes JOINs nas consultas. 🎉', 'success');
        } else {
            HelpDesk.toast(resp.error || 'Erro ao salvar', 'danger');
        }
    } catch (e) {
        HelpDesk.toast('Erro: ' + e.message, 'danger');
    }
}

async function cbCarregarRelacionamentosFonte() {
    if (!_fonteAtualId) return;
    try {
        const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'fonte_relacionamentos', id: _fonteAtualId });
        if (resp.success && resp.data) {
            window._dbRelacionamentos = Array.isArray(resp.data) ? resp.data : [];
            _fonteRels = [...window._dbRelacionamentos];
            cbRenderRelacionamentos();
        }
    } catch (e) { /* silenciar */ }
}

// Legacy wrapper
async function cbCarregarRelacionamentos() {
    return cbCarregarRelacionamentosFonte();
}

function cbLimparRelacionamentos() {
    if (!confirm('Tem certeza que deseja remover TODOS os relacionamentos?')) return;
    window._dbRelacionamentos = [];
    _fonteRels = [];
    cbRenderRelacionamentos();
    cbSalvarRelacionamentos();
}
</script>