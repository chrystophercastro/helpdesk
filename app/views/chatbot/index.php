<?php /** View: Chatbot Inteligente */ $user = currentUser(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-headset"></i> Chatbot Inteligente</h1>
        <p class="page-subtitle">Gerenciamento do chatbot com IA, WhatsApp, N8N e base de dados</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-outline-primary" onclick="cbRefreshStatus()">
            <i class="fas fa-sync-alt"></i> Atualizar Status
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4" id="cbKpis">
    <div class="col">
        <div class="card text-center p-3">
            <div style="font-size:22px;font-weight:700;color:var(--primary)" id="kpiSessoes">-</div>
            <small class="text-muted">Sessões Ativas</small>
        </div>
    </div>
    <div class="col">
        <div class="card text-center p-3">
            <div style="font-size:22px;font-weight:700;color:var(--success)" id="kpiMsgsHoje">-</div>
            <small class="text-muted">Msgs Hoje</small>
        </div>
    </div>
    <div class="col">
        <div class="card text-center p-3">
            <div style="font-size:22px;font-weight:700;color:var(--purple)" id="kpiNumeros">-</div>
            <small class="text-muted">Nº Autorizados</small>
        </div>
    </div>
    <div class="col">
        <div class="card text-center p-3">
            <div style="font-size:22px;font-weight:700;color:var(--cyan)" id="kpiQueries">-</div>
            <small class="text-muted">Queries SQL</small>
        </div>
    </div>
    <div class="col">
        <div class="card text-center p-3" style="cursor:pointer" onclick="document.querySelector('[data-tab=cb-logs]').click()">
            <div style="font-size:22px;font-weight:700;color:var(--danger)" id="kpiErros">0</div>
            <small class="text-muted">Erros Hoje</small>
        </div>
    </div>
    <div class="col">
        <div class="card text-center p-3">
            <div id="kpiOllama" style="font-size:14px;font-weight:600;">-</div>
            <small class="text-muted">Ollama IA</small>
        </div>
    </div>
    <div class="col">
        <div class="card text-center p-3">
            <div id="kpiEvolution" style="font-size:14px;font-weight:600;">-</div>
            <small class="text-muted">WhatsApp</small>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="ad-tabs">
    <button class="ad-tab active" data-tab="cb-config" onclick="cbTab(this)">
        <i class="fas fa-cog"></i> Configurações
    </button>
    <button class="ad-tab" data-tab="cb-numeros" onclick="cbTab(this)">
        <i class="fas fa-phone"></i> Números Autorizados
    </button>
    <button class="ad-tab" data-tab="cb-sessoes" onclick="cbTab(this)">
        <i class="fas fa-comments"></i> Sessões / Chat
    </button>
    <button class="ad-tab" data-tab="cb-database" onclick="cbTab(this)">
        <i class="fas fa-database"></i> Base de Dados
    </button>
    <button class="ad-tab" data-tab="cb-n8n" onclick="cbTab(this)">
        <i class="fas fa-project-diagram"></i> N8N
    </button>
    <button class="ad-tab" data-tab="cb-logs" onclick="cbTab(this);cbLoadLogs()">
        <i class="fas fa-file-alt"></i> Logs
        <span class="badge bg-danger ms-1" id="badgeErrosHoje" style="display:none;font-size:9px">0</span>
    </button>
    <button class="ad-tab" data-tab="cb-teste" onclick="cbTab(this)">
        <i class="fas fa-flask"></i> Testar IA
    </button>
    <button class="ad-tab" data-tab="cb-ajuda" onclick="cbTab(this)">
        <i class="fas fa-question-circle"></i> Ajuda
    </button>
</div>

<!-- ============================================ -->
<!--  TAB: CONFIGURAÇÕES                         -->
<!-- ============================================ -->
<div class="ad-tab-content active" id="cb-config">
    <div class="row g-4">
        <!-- Geral -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-sliders-h"></i> Geral</h6></div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Chatbot Ativo</label>
                        <select class="form-control" id="cfg_chatbot_ativo">
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
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-clock"></i> Horário de Atendimento</h6></div>
                <div class="card-body">
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
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-brain"></i> Inteligência Artificial (Ollama)</h6></div>
                <div class="card-body">
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
<div class="ad-tab-content" id="cb-numeros">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-phone"></i> Números Autorizados</h6>
            <button class="btn btn-sm btn-primary" onclick="cbShowAddNumero()">
                <i class="fas fa-plus"></i> Adicionar Número
            </button>
        </div>
        <div class="card-body p-0">
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
<div class="ad-tab-content" id="cb-sessoes">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-list"></i> Sessões</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="cbLoadSessoes()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body p-0" style="max-height:600px;overflow-y:auto" id="sessoesListContainer">
                    <div class="text-center text-muted py-4">Carregando...</div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card" id="chatViewCard">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0" id="chatViewTitle"><i class="fas fa-comments"></i> Selecione uma sessão</h6>
                    <div>
                        <button class="btn btn-sm btn-outline-danger" id="btnLimparSessao" style="display:none" onclick="cbLimparSessao()">
                            <i class="fas fa-trash"></i> Limpar
                        </button>
                    </div>
                </div>
                <div class="card-body" id="chatViewBody" style="height:500px;overflow-y:auto;background:#f8f9fa;">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-comments fa-3x mb-3" style="opacity:0.3"></i>
                        <p>Selecione uma sessão para ver o histórico</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!--  TAB: BASE DE DADOS                          -->
<!-- ============================================ -->
<div class="ad-tab-content" id="cb-database">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-database"></i> Conexão com Base de Dados Externa</h6></div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Fonte de Dados Ativa</label>
                        <select class="form-control" id="cfg_chatbot_db_ativo">
                            <option value="0">Desativada</option>
                            <option value="1">Ativada</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Tipo de Fonte</label>
                        <select class="form-control" id="cfg_chatbot_db_tipo" onchange="cbOnTipoFonteChange()">
                            <option value="mysql">MySQL / MariaDB</option>
                            <option value="pgsql">PostgreSQL</option>
                            <option value="sqlserver">SQL Server</option>
                            <option value="sqlite">SQLite</option>
                            <option value="api">API REST</option>
                        </select>
                    </div>

                    <!-- ====== Campos para Banco SQL ====== -->
                    <div id="dbFieldsSQL">
                        <div class="row mb-3" id="dbFieldHost">
                            <div class="col-8">
                                <label class="form-label">Host</label>
                                <input type="text" class="form-control" id="cfg_chatbot_db_host" placeholder="localhost">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Porta</label>
                                <input type="text" class="form-control" id="cfg_chatbot_db_port" placeholder="3306">
                            </div>
                        </div>
                        <div class="form-group mb-3" id="dbFieldSQLite" style="display:none">
                            <label class="form-label">Caminho do Arquivo SQLite</label>
                            <input type="text" class="form-control" id="cfg_chatbot_db_host_sqlite" placeholder="C:\dados\meu_banco.db" disabled>
                            <small class="text-muted">O campo Host será usado como caminho do arquivo</small>
                        </div>
                        <div class="form-group mb-3" id="dbFieldName">
                            <label class="form-label">Nome do Banco</label>
                            <input type="text" class="form-control" id="cfg_chatbot_db_name">
                        </div>
                        <div class="form-group mb-3" id="dbFieldUser">
                            <label class="form-label">Usuário</label>
                            <input type="text" class="form-control" id="cfg_chatbot_db_user">
                        </div>
                        <div class="form-group mb-3" id="dbFieldPass">
                            <label class="form-label">Senha</label>
                            <input type="password" class="form-control" id="cfg_chatbot_db_pass">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Descrição do Banco (contexto para a IA)</label>
                            <textarea class="form-control" id="cfg_chatbot_db_descricao" rows="3" placeholder="Ex: Banco de dados do ERP com vendas, clientes e produtos"></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Tabelas Permitidas (separadas por vírgula, vazio = todas)</label>
                            <input type="text" class="form-control" id="cfg_chatbot_db_tabelas_permitidas" placeholder="vendas,clientes,produtos">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Máx. Linhas por Consulta</label>
                            <input type="number" class="form-control" id="cfg_chatbot_db_max_rows" value="50">
                        </div>
                    </div>

                    <!-- ====== Campos para API REST ====== -->
                    <div id="dbFieldsAPI" style="display:none">
                        <div class="form-group mb-3">
                            <label class="form-label">URL Base da API</label>
                            <input type="text" class="form-control" id="cfg_chatbot_api_url" placeholder="https://api.exemplo.com/v1">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Tipo de Autenticação</label>
                            <select class="form-control" id="cfg_chatbot_api_auth_tipo" onchange="cbOnAuthTipoChange()">
                                <option value="none">Nenhuma</option>
                                <option value="bearer">Bearer Token</option>
                                <option value="apikey">API Key (Header Custom)</option>
                                <option value="basic">Basic Auth (Usuário/Senha)</option>
                            </select>
                        </div>
                        <div class="form-group mb-3" id="apiFieldKey" style="display:none">
                            <label class="form-label">Token / API Key</label>
                            <input type="password" class="form-control" id="cfg_chatbot_api_key" placeholder="sk-...">
                        </div>
                        <div class="form-group mb-3" id="apiFieldHeader" style="display:none">
                            <label class="form-label">Nome do Header</label>
                            <input type="text" class="form-control" id="cfg_chatbot_api_auth_header" placeholder="X-API-Key">
                        </div>
                        <div id="apiFieldBasic" style="display:none">
                            <div class="form-group mb-3">
                                <label class="form-label">Usuário</label>
                                <input type="text" class="form-control" id="cfg_chatbot_api_auth_user">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" class="form-control" id="cfg_chatbot_api_auth_pass">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Descrição da API (contexto para a IA)</label>
                            <textarea class="form-control" id="cfg_chatbot_api_descricao" rows="2" placeholder="Ex: API do ERP que retorna dados de vendas e clientes"></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Endpoints Disponíveis (JSON)</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="cbInsertEndpointTemplate()">
                                    <i class="fas fa-plus"></i> Modelo
                                </button>
                            </label>
                            <textarea class="form-control font-monospace" id="cfg_chatbot_api_endpoints" rows="10" placeholder='[{"method":"GET","path":"/clientes","description":"Lista clientes","params":[{"name":"nome","type":"string","description":"Filtrar por nome"}]}]' style="font-size:12px"></textarea>
                            <small class="text-muted">Array JSON com os endpoints que a IA pode usar. Cada item: method, path, description, params[], response_example</small>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="cbSaveConfig()">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                        <button class="btn btn-outline-success" onclick="cbTestDB()">
                            <i class="fas fa-plug"></i> Testar Conexão
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background:#161b22;border-bottom:1px solid #30363d">
                    <h6 class="mb-0" id="dbSchemaTitle" style="color:#c9d1d9"><i class="fas fa-project-diagram" style="color:#58a6ff"></i> Diagrama do Banco</h6>
                    <div class="d-flex gap-1 align-items-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-sm" onclick="cbDiagramZoom(-0.1)" title="Zoom Out" style="border-color:#30363d;color:#8b949e"><i class="fas fa-search-minus"></i></button>
                            <button class="btn btn-sm" onclick="cbDiagramZoom(0)" title="Resetar Zoom" style="border-color:#30363d;color:#c9d1d9;min-width:48px"><small id="lblZoom">100%</small></button>
                            <button class="btn btn-sm" onclick="cbDiagramZoom(0.1)" title="Zoom In" style="border-color:#30363d;color:#8b949e"><i class="fas fa-search-plus"></i></button>
                        </div>
                        <button class="btn btn-sm" onclick="cbLoadSchema()" title="Carregar Schema" style="border-color:#1f6feb;color:#58a6ff"><i class="fas fa-sync-alt"></i></button>
                        <button class="btn btn-sm" onclick="cbToggleLinkMode()" title="Criar Relacionamento (clique nas colunas)" id="btnLinkMode" style="border-color:#2ea043;color:#2ea043"><i class="fas fa-link"></i></button>
                        <button class="btn btn-sm" onclick="cbAutoLayoutDiagram()" title="Reorganizar Layout" style="border-color:#d29922;color:#d29922"><i class="fas fa-th"></i></button>
                        <button class="btn btn-sm" onclick="cbToggleFullscreen()" title="Tela Cheia" id="btnFullscreen" style="border-color:#30363d;color:#8b949e"><i class="fas fa-expand" id="btnFullscreenIcon"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Sub-abas: Diagrama | Relacionamentos -->
                    <ul class="nav nav-tabs nav-fill px-3 pt-2" style="font-size:13px">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#subTabDiagrama">
                                <i class="fas fa-project-diagram"></i> Diagrama
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#subTabRelacionamentos">
                                <i class="fas fa-link"></i> Relacionamentos
                                <span class="badge bg-primary ms-1" id="badgeRelCount">0</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Aba Tabelas/Diagrama -->
                        <div class="tab-pane fade show active p-0" id="subTabDiagrama">
                            <div id="dbDiagramWrapper" class="db-diagram-wrapper">
                                <!-- Toolbar: visível apenas em fullscreen -->
                                <div id="dbDiagramToolbar" class="db-diagram-toolbar">
                                    <span><i class="fas fa-project-diagram"></i> Diagrama do Banco</span>
                                    <div class="d-flex gap-1 align-items-center ms-auto">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-dark btn-sm" onclick="cbDiagramZoom(-0.1)"><i class="fas fa-search-minus"></i></button>
                                            <button class="btn btn-dark btn-sm" id="lblZoomFS" style="min-width:48px">100%</button>
                                            <button class="btn btn-dark btn-sm" onclick="cbDiagramZoom(0.1)"><i class="fas fa-search-plus"></i></button>
                                        </div>
                                        <button class="btn btn-dark btn-sm" onclick="cbDiagramZoom(0)" title="Resetar Zoom"><i class="fas fa-undo"></i></button>
                                        <button class="btn btn-dark btn-sm" onclick="cbToggleLinkMode()" id="btnLinkModeFS"><i class="fas fa-link"></i> Relacionar</button>
                                        <button class="btn btn-dark btn-sm" onclick="cbAutoLayoutDiagram()"><i class="fas fa-th"></i> Reorganizar</button>
                                        <button class="btn btn-warning btn-sm" onclick="cbToggleFullscreen()"><i class="fas fa-compress"></i> Sair</button>
                                    </div>
                                </div>
                                <!-- Empty state -->
                                <div id="dbDiagramEmpty" class="db-diagram-empty">
                                    <i class="fas fa-database fa-3x mb-3" style="color:#30363d"></i>
                                    <p style="color:#8b949e">Clique em <strong style="color:#58a6ff"><i class="fas fa-sync-alt"></i> Carregar</strong> para visualizar o diagrama</p>
                                </div>
                                <!-- Canvas com tabelas + SVG -->
                                <div id="dbDiagramCanvas" class="db-diagram-canvas">
                                    <svg id="dbDiagramSvg" class="db-diagram-svg"></svg>
                                </div>
                                <!-- Floating status bar for link mode -->
                                <div id="dbLinkStatus" class="db-link-status">
                                    <span class="step-badge" id="linkStep1">1</span>
                                    <span id="linkStatusText">Clique na coluna <strong>FK</strong> da tabela de origem</span>
                                    <button class="btn btn-sm btn-outline-light" onclick="cbCancelLinkMode()" style="font-size:11px">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Aba Relacionamentos -->
                        <div class="tab-pane fade p-3" id="subTabRelacionamentos">
                            <div class="alert alert-info py-2" style="font-size:12px">
                                <i class="fas fa-info-circle"></i>
                                Defina os relacionamentos (JOINs) entre tabelas. A IA usará estas relações para trazer <strong>nomes</strong> ao invés de IDs/códigos.
                            </div>
                            <!-- Formulário para adicionar relacionamento -->
                            <div class="card card-body bg-light mb-3 p-2" id="formAddRel">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label mb-0" style="font-size:11px"><strong>Tabela.Coluna (FK)</strong></label>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm" id="relTabelaOrigem" onchange="cbOnRelTabelaOrigemChange()">
                                                <option value="">Tabela...</option>
                                            </select>
                                            <select class="form-select form-select-sm" id="relColunaOrigem">
                                                <option value="">Coluna...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto text-center" style="font-size:18px;color:var(--primary)">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label mb-0" style="font-size:11px"><strong>Tabela Ref.Coluna (PK)</strong></label>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm" id="relTabelaRef" onchange="cbOnRelTabelaRefChange()">
                                                <option value="">Tabela...</option>
                                            </select>
                                            <select class="form-select form-select-sm" id="relColunaRef">
                                                <option value="">Coluna (PK)...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-sm btn-success" onclick="cbAddRelacionamento()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Coluna de exibição (nome legível) -->
                                <div class="row g-2 mt-1">
                                    <div class="col">
                                        <label class="form-label mb-0" style="font-size:11px">Coluna com nome legível (da tabela referenciada)</label>
                                        <select class="form-select form-select-sm" id="relColunaDescricao">
                                            <option value="">Ex: nome, descricao, titulo...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- Lista de relacionamentos -->
                            <div id="relListContainer" style="max-height:350px;overflow-y:auto">
                                <div class="text-center text-muted py-3">
                                    <small>Nenhum relacionamento definido</small>
                                </div>
                            </div>
                            <!-- Botão salvar -->
                            <div class="d-flex justify-content-between mt-2">
                                <button class="btn btn-sm btn-outline-danger" onclick="cbLimparRelacionamentos()">
                                    <i class="fas fa-trash"></i> Limpar Todos
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="cbSalvarRelacionamentos()">
                                    <i class="fas fa-save"></i> Salvar Relacionamentos
                                </button>
                            </div>
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
<div class="ad-tab-content" id="cb-n8n">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-project-diagram"></i> Integração N8N</h6></div>
                <div class="card-body">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history"></i> Logs N8N</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="cbLoadN8NLogs()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body p-0" style="max-height:500px;overflow-y:auto">
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
<div class="ad-tab-content" id="cb-logs">
    <div class="row g-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-file-alt"></i> Logs do Chatbot (Webhook + IA)</h6>
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
                <div class="card-body p-0">
                    <!-- Resumo de erros -->
                    <div class="px-3 py-2 d-flex gap-3 align-items-center" style="background:#f8f9fa;border-bottom:1px solid #dee2e6;font-size:12px">
                        <span id="logCountTotal" class="badge bg-secondary">0 logs</span>
                        <span id="logCountErrors" class="badge bg-danger" style="display:none">0 erros</span>
                        <span id="logCountSuccess" class="badge bg-success" style="display:none">0 OK</span>
                        <span class="ms-auto text-muted"><i class="fas fa-info-circle"></i> Logs das últimas 200 ações do webhook/IA</span>
                    </div>
                    <!-- Log entries -->
                    <div id="logContainer" style="max-height:600px;overflow-y:auto;font-family:'SFMono-Regular',Consolas,monospace;font-size:12px;background:#0d1117;color:#c9d1d9">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle text-danger"></i> Erros de SQL / IA (do banco)</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="cbLoadErros()">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                </div>
                <div class="card-body p-0">
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
<!--  TAB: TESTAR IA                               -->
<!-- ============================================ -->
<div class="ad-tab-content" id="cb-teste">
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-flask"></i> Testar Chatbot</h6>
                    <small class="text-muted" id="testeModelInfo"></small>
                </div>
                <div class="card-body" id="testeChat" style="height:450px;overflow-y:auto;background:#f8f9fa;">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-robot fa-3x mb-3" style="opacity:0.3"></i>
                        <p>Envie uma mensagem para testar o chatbot</p>
                    </div>
                </div>
                <div class="card-footer">
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
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle"></i> Info do Teste</h6></div>
                <div class="card-body" id="testeInfo">
                    <p class="text-muted mb-2"><small>As mensagens de teste são salvas em uma sessão especial (número 0000000000).</small></p>
                    <hr>
                    <div id="testeStats">
                        <p class="mb-1"><strong>Último teste:</strong></p>
                        <p class="text-muted">Nenhum teste realizado</p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-link"></i> Webhook WhatsApp</h6></div>
                <div class="card-body">
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
<div class="ad-tab-content" id="cb-ajuda">
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-book"></i> Guia Completo — Como Colocar o Chatbot para Funcionar</h5>
                </div>
                <div class="card-body" style="font-size:14px;line-height:1.7">

                    <!-- PASSO 1 -->
                    <div class="mb-4 p-3 border rounded" style="background:#f0f9ff">
                        <h5 class="text-primary"><i class="fas fa-1"></i> Passo 1 — Configurar a IA (Ollama)</h5>
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
                    <div class="mb-4 p-3 border rounded" style="background:#f0fff4">
                        <h5 class="text-success"><i class="fas fa-2"></i> Passo 2 — Configurar o WhatsApp (Evolution API)</h5>
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
                    <div class="mb-4 p-3 border rounded" style="background:#fff5f0">
                        <h5 style="color:var(--orange)"><i class="fas fa-3"></i> Passo 3 — Autorizar Números</h5>
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
                    <div class="mb-4 p-3 border rounded" style="background:#f5f0ff">
                        <h5 style="color:var(--purple)"><i class="fas fa-4"></i> Passo 4 — Conectar uma Fonte de Dados (Opcional)</h5>
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
                    <div class="mb-4 p-3 border rounded" style="background:#fffbe6">
                        <h5 style="color:#b8860b"><i class="fas fa-5"></i> Passo 5 — Integração N8N (Opcional)</h5>
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
                    <div class="mb-4 p-3 border rounded" style="background:#e8f5e9">
                        <h5 class="text-success"><i class="fas fa-6"></i> Passo 6 — Ativar e Testar!</h5>
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
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-clipboard-check"></i> Checklist Rápido</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" id="ajudaChecklist" style="font-size:13px">
                        <li class="list-group-item d-flex align-items-center gap-2" id="chk_ollama">
                            <i class="fas fa-circle text-muted"></i> Ollama rodando e acessível
                        </li>
                        <li class="list-group-item d-flex align-items-center gap-2" id="chk_modelo">
                            <i class="fas fa-circle text-muted"></i> Modelo de IA selecionado
                        </li>
                        <li class="list-group-item d-flex align-items-center gap-2" id="chk_evolution">
                            <i class="fas fa-circle text-muted"></i> Evolution API conectado
                        </li>
                        <li class="list-group-item d-flex align-items-center gap-2" id="chk_webhook">
                            <i class="fas fa-circle text-muted"></i> Webhook configurado
                        </li>
                        <li class="list-group-item d-flex align-items-center gap-2" id="chk_numeros">
                            <i class="fas fa-circle text-muted"></i> Pelo menos 1 número autorizado
                        </li>
                        <li class="list-group-item d-flex align-items-center gap-2" id="chk_ativo">
                            <i class="fas fa-circle text-muted"></i> Chatbot ativado
                        </li>
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <button class="btn btn-sm btn-success" onclick="cbVerificarChecklist()">
                        <i class="fas fa-sync-alt"></i> Verificar Tudo
                    </button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-sitemap"></i> Arquitetura do Sistema</h6></div>
                <div class="card-body" style="font-size:12px">
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

            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-wrench"></i> Solução de Problemas</h6></div>
                <div class="card-body" style="font-size:13px">
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

/* Dark card/tab styling for diagram card */
#cb-database .col-md-7 > .card { background: #0d1117; border-color: #30363d; }
#cb-database .col-md-7 .nav-tabs { border-bottom-color: #30363d; background: #161b22; }
#cb-database .col-md-7 .nav-tabs .nav-link { color: #8b949e; border-color: transparent; }
#cb-database .col-md-7 .nav-tabs .nav-link:hover { color: #c9d1d9; border-color: #30363d #30363d transparent; }
#cb-database .col-md-7 .nav-tabs .nav-link.active { color: #58a6ff; background: #0d1117; border-color: #30363d #30363d #0d1117; }
#subTabRelacionamentos { background: #0d1117; }
#subTabRelacionamentos .alert-info { background: rgba(56,139,253,0.1); border-color: #1f6feb; color: #c9d1d9; }
#subTabRelacionamentos .card-body { background: #161b22 !important; }
#subTabRelacionamentos .bg-light { background: #161b22 !important; }
#subTabRelacionamentos .form-select,
#subTabRelacionamentos .form-control { background: #0d1117; border-color: #30363d; color: #c9d1d9; }
#subTabRelacionamentos .form-label { color: #8b949e; }
#subTabRelacionamentos .border { border-color: #30363d !important; }
#subTabRelacionamentos .text-muted { color: #8b949e !important; }
#subTabRelacionamentos #relListContainer .d-flex { background: #161b22; }
#subTabRelacionamentos #relListContainer code { font-size: 12px; }
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
    document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ad-tab-content').forEach(c => c.classList.remove('active'));
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
            ollamaEl.innerHTML = '<span style="color:var(--success)">● Online</span>';
            // Preencher select de modelos
            cbPopulateModels(d.ollama.models, d.ollama.modelo_ativo);
        } else {
            ollamaEl.innerHTML = '<span style="color:var(--danger)">● Offline</span>';
        }

        // Evolution
        const evoEl = document.getElementById('kpiEvolution');
        if (d.evolution.connected) {
            evoEl.innerHTML = '<span style="color:var(--success)">● Conectado</span>';
        } else {
            evoEl.innerHTML = '<span style="color:var(--danger)">● ' + (d.evolution.state || 'Desconectado') + '</span>';
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

// ---- BASE DE DADOS / API ----

// Toggle campos conforme tipo de fonte selecionado
function cbOnTipoFonteChange() {
    const tipo = document.getElementById('cfg_chatbot_db_tipo').value;
    const isAPI = tipo === 'api';
    const isSQLite = tipo === 'sqlite';

    document.getElementById('dbFieldsSQL').style.display = isAPI ? 'none' : '';
    document.getElementById('dbFieldsAPI').style.display = isAPI ? '' : 'none';

    // SQLite: só precisa do caminho do arquivo, sem user/pass/port/name
    document.getElementById('dbFieldHost').style.display = isSQLite ? 'none' : '';
    document.getElementById('dbFieldSQLite').style.display = isSQLite ? '' : 'none';
    document.getElementById('dbFieldName').style.display = isSQLite ? 'none' : '';
    document.getElementById('dbFieldUser').style.display = isSQLite ? 'none' : '';
    document.getElementById('dbFieldPass').style.display = isSQLite ? 'none' : '';

    // Para SQLite, usar campo host como path
    if (isSQLite) {
        document.getElementById('cfg_chatbot_db_host').placeholder = 'C:\\dados\\banco.db';
    } else {
        document.getElementById('cfg_chatbot_db_host').placeholder = 'localhost';
    }

    // Atualizar placeholder da porta conforme driver
    const portPlaceholders = { mysql: '3306', pgsql: '5432', sqlserver: '1433' };
    document.getElementById('cfg_chatbot_db_port').placeholder = portPlaceholders[tipo] || '3306';

    // Atualizar título do schema
    const titleEl = document.getElementById('dbSchemaTitle');
    if (titleEl) {
        titleEl.innerHTML = isAPI 
            ? '<i class="fas fa-plug"></i> Endpoints da API' 
            : '<i class="fas fa-table"></i> Schema do Banco (' + tipo.toUpperCase() + ')';
    }
}

// Toggle campos de auth conforme tipo
function cbOnAuthTipoChange() {
    const auth = document.getElementById('cfg_chatbot_api_auth_tipo').value;
    document.getElementById('apiFieldKey').style.display = (auth === 'bearer' || auth === 'apikey') ? '' : 'none';
    document.getElementById('apiFieldHeader').style.display = auth === 'apikey' ? '' : 'none';
    document.getElementById('apiFieldBasic').style.display = auth === 'basic' ? '' : 'none';
}

// Inserir template de endpoint no campo JSON
function cbInsertEndpointTemplate() {
    const el = document.getElementById('cfg_chatbot_api_endpoints');
    let current = [];
    try { current = JSON.parse(el.value) || []; } catch(e) { current = []; }
    current.push({
        method: "GET",
        path: "/exemplo",
        description: "Descreva o que este endpoint faz",
        params: [
            { name: "filtro", type: "string", description: "Parâmetro de filtro" }
        ],
        response_example: { id: 1, nome: "Exemplo" }
    });
    el.value = JSON.stringify(current, null, 2);
}

async function cbTestDB() {
    HelpDesk.toast('Testando conexão...', 'info');
    // Salvar config primeiro
    await cbSaveConfig();

    const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'db_test' });
    if (resp.data && resp.data.success) {
        const tipoLabel = resp.data.tipo === 'api' ? 'API' : resp.data.tipo?.toUpperCase();
        HelpDesk.toast('✅ Conectado! ' + resp.data.message + (tipoLabel ? ' [' + tipoLabel + ']' : ''), 'success');
        cbLoadSchema();
    } else {
        HelpDesk.toast('❌ Falha: ' + (resp.data?.message || resp.error), 'danger');
    }
}

async function cbLoadSchema() {
    const wrapper = document.getElementById('dbDiagramWrapper');
    const canvas  = document.getElementById('dbDiagramCanvas');
    const svg     = document.getElementById('dbDiagramSvg');
    const emptyEl = document.getElementById('dbDiagramEmpty');
    const tipo    = document.getElementById('cfg_chatbot_db_tipo')?.value || 'mysql';
    const isAPI   = tipo === 'api';

    // Reset
    canvas.querySelectorAll('.db-table-card').forEach(el => el.remove());
    svg.innerHTML = '';
    emptyEl.style.display = 'flex';
    emptyEl.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x mb-3" style="color:#58a6ff"></i><p style="color:#8b949e">Carregando schema...</p>';

    try {
        const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'db_schema' });
        if (!resp.success) {
            emptyEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-3" style="color:#f85149"></i><p style="color:#f85149">' + (resp.error || 'Erro ao carregar') + '</p>';
            return;
        }

        if (!resp.data || resp.data.length === 0) {
            emptyEl.innerHTML = '<i class="fas fa-database fa-3x mb-3" style="color:#30363d"></i><p style="color:#8b949e">' + (isAPI ? 'Nenhum endpoint configurado' : 'Nenhuma tabela encontrada') + '</p>';
            return;
        }

        window._dbSchema = resp.data;
        emptyEl.style.display = 'none';

        if (isAPI) {
            // API mode - simple list
            canvas.style.minWidth = 'auto';
            const listDiv = document.createElement('div');
            listDiv.className = 'p-3';
            listDiv.innerHTML = resp.data.map(t => `
                <div class="mb-3 border rounded p-2" style="background:#161b22;border-color:#30363d !important">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong style="color:#58a6ff"><i class="fas fa-plug"></i> ${t.table}</strong>
                    </div>
                    <div style="font-size:12px;color:#8b949e">${typeof t.row_count === 'string' ? t.row_count : ''}</div>
                    ${t.columns && t.columns.length > 0 ? `<div style="font-size:12px;color:#c9d1d9;margin-top:4px">
                        <strong style="color:#8b949e">Params:</strong> ${t.columns.map(c => '<code style="margin-right:8px;color:#7ee787">' + c.Field + ' <small style="color:#8b949e">(' + c.Type + ')</small></code>').join('')}
                    </div>` : ''}
                </div>
            `).join('');
            canvas.appendChild(listDiv);
        } else {
            // SQL mode - interactive diagram with draggable cards
            const tables = resp.data.filter(t => t.permitted);
            const savedPos = cbLoadTablePositions();
            const cols = Math.max(3, Math.ceil(Math.sqrt(tables.length)));
            const spacingX = 330;
            const spacingY = 330;

            tables.forEach((t, i) => {
                const card = document.createElement('div');
                card.className = 'db-table-card';
                card.id = 'dbTable_' + t.table;
                card.dataset.table = t.table;

                // Position: saved or auto-grid
                if (savedPos && savedPos[t.table]) {
                    card.style.left = savedPos[t.table].left + 'px';
                    card.style.top  = savedPos[t.table].top + 'px';
                } else {
                    card.style.left = (50 + (i % cols) * spacingX) + 'px';
                    card.style.top  = (50 + Math.floor(i / cols) * spacingY) + 'px';
                }

                // Build columns
                const colsHtml = t.columns.map(c => {
                    const isPK = /^(id|codigo)$/i.test(c.Field) || c.Key === 'PRI';
                    const isFK = /(_id|_cod|_codigo|id_|cod_)$/i.test(c.Field) || c.Key === 'MUL';
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
            cbPreencherSelectsRelacionamento(tables);
        }

        // Load and draw relationships
        await cbCarregarRelacionamentos();
        cbDrawRelLines();

    } catch (e) {
        emptyEl.style.display = 'flex';
        emptyEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-3" style="color:#f85149"></i><p style="color:#f85149">Erro: ' + e.message + '</p>';
    }
}

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
        const icon = el.querySelector('i');
        if (c.ok) {
            icon.className = 'fas fa-check-circle text-success';
            totalOk++;
        } else {
            icon.className = 'fas fa-times-circle text-danger';
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
    const host = document.getElementById('cfg_chatbot_db_host')?.value || 'local';
    const name = document.getElementById('cfg_chatbot_db_name')?.value || 'db';
    return 'cbDiagram_' + host + '_' + name;
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

        // Add relationship
        window._dbRelacionamentos.push({
            tabela: source.table,
            coluna: source.column,
            ref_tabela: target.table,
            ref_coluna: target.column,
            coluna_descricao: descCol
        });

        cbRenderRelacionamentos();
        cbDrawRelLines();

        // Auto-save
        cbSalvarRelacionamentos();
        HelpDesk.toast('✅ Relacionamento criado: ' + source.table + '.' + source.column + ' → ' + target.table + '.' + target.column, 'success');
    }

    // Cleanup
    if (overlay) overlay.remove();
    window._pendingLink = null;
    cbCancelLinkMode();
}

// ============================================================
//  RELACIONAMENTOS
// ============================================================

function cbPreencherSelectsRelacionamento(tables) {
    const selOrigem = document.getElementById('relTabelaOrigem');
    const selRef = document.getElementById('relTabelaRef');
    const opts = '<option value="">Tabela...</option>' + tables.map(t => `<option value="${t.table}">${t.table}</option>`).join('');
    selOrigem.innerHTML = opts;
    selRef.innerHTML = opts;
    // Limpar colunas
    document.getElementById('relColunaOrigem').innerHTML = '<option value="">Coluna...</option>';
    document.getElementById('relColunaRef').innerHTML = '<option value="">Coluna (PK)...</option>';
    document.getElementById('relColunaDescricao').innerHTML = '<option value="">Selecione tabela ref primeiro...</option>';
}

function cbOnRelTabelaOrigemChange() {
    const tabela = document.getElementById('relTabelaOrigem').value;
    const sel = document.getElementById('relColunaOrigem');
    if (!tabela || !window._dbSchema) { sel.innerHTML = '<option value="">Coluna...</option>'; return; }
    const t = window._dbSchema.find(x => x.table === tabela);
    if (!t) return;
    sel.innerHTML = '<option value="">Coluna...</option>' + t.columns.map(c => {
        const isFK = /(_id|_cod|_codigo|id_|cod_)$/i.test(c.Field);
        return `<option value="${c.Field}" ${isFK ? 'style="color:var(--primary);font-weight:bold"' : ''}>${c.Field} (${c.Type})${isFK ? ' 🔗' : ''}</option>`;
    }).join('');
}

function cbOnRelTabelaRefChange() {
    const tabela = document.getElementById('relTabelaRef').value;
    const selPK = document.getElementById('relColunaRef');
    const selDesc = document.getElementById('relColunaDescricao');
    if (!tabela || !window._dbSchema) {
        selPK.innerHTML = '<option value="">Coluna (PK)...</option>';
        selDesc.innerHTML = '<option value="">Selecione tabela ref primeiro...</option>';
        return;
    }
    const t = window._dbSchema.find(x => x.table === tabela);
    if (!t) return;

    selPK.innerHTML = '<option value="">Coluna (PK)...</option>' + t.columns.map(c => {
        const isPK = c.Field.toLowerCase() === 'id' || c.Key === 'PRI';
        return `<option value="${c.Field}" ${isPK ? 'selected' : ''}>${c.Field}${isPK ? ' 🔑' : ''}</option>`;
    }).join('');

    // Detectar colunas de nome/descrição
    const nomeCols = ['nome', 'name', 'descricao', 'description', 'titulo', 'title', 'razao_social', 'nome_fantasia', 'label', 'denominacao'];
    selDesc.innerHTML = '<option value="">(opcional)</option>' + t.columns.map(c => {
        const isNome = nomeCols.some(n => c.Field.toLowerCase().includes(n));
        return `<option value="${c.Field}" ${isNome ? 'style="color:green;font-weight:bold"' : ''}>${c.Field}${isNome ? ' ✓' : ''}</option>`;
    }).join('');
    // Auto-selecionar primeira coluna que parece nome
    const autoNome = t.columns.find(c => nomeCols.some(n => c.Field.toLowerCase() === n));
    if (autoNome) selDesc.value = autoNome.Field;
}

function cbAddRelacionamento() {
    const tOrigem = document.getElementById('relTabelaOrigem').value;
    const cOrigem = document.getElementById('relColunaOrigem').value;
    const tRef = document.getElementById('relTabelaRef').value;
    const cRef = document.getElementById('relColunaRef').value;
    const cDesc = document.getElementById('relColunaDescricao').value;

    if (!tOrigem || !cOrigem || !tRef || !cRef) {
        HelpDesk.toast('Preencha tabela e coluna de origem + tabela e coluna de referência', 'warning');
        return;
    }

    // Verificar duplicata
    const existe = window._dbRelacionamentos.find(r =>
        r.tabela === tOrigem && r.coluna === cOrigem && r.ref_tabela === tRef && r.ref_coluna === cRef
    );
    if (existe) {
        HelpDesk.toast('Esse relacionamento já existe', 'warning');
        return;
    }

    window._dbRelacionamentos.push({
        tabela: tOrigem,
        coluna: cOrigem,
        ref_tabela: tRef,
        ref_coluna: cRef,
        coluna_descricao: cDesc || ''
    });

    cbRenderRelacionamentos();
    HelpDesk.toast('Relacionamento adicionado! Clique em "Salvar" para persistir.', 'info');
}

function cbRemoverRelacionamento(idx) {
    window._dbRelacionamentos.splice(idx, 1);
    cbRenderRelacionamentos();
}

function cbRenderRelacionamentos() {
    const container = document.getElementById('relListContainer');
    const badge = document.getElementById('badgeRelCount');
    const rels = window._dbRelacionamentos;
    badge.textContent = rels.length;

    if (rels.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3"><small>Nenhum relacionamento definido</small></div>';
        return;
    }

    container.innerHTML = rels.map((r, i) => `
        <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-1" style="font-size:12px">
            <div>
                <code class="text-primary">${r.tabela}</code>.<strong>${r.coluna}</strong>
                <i class="fas fa-long-arrow-alt-right mx-1" style="color:var(--primary)"></i>
                <code class="text-success">${r.ref_tabela}</code>.<strong>${r.ref_coluna}</strong>
                ${r.coluna_descricao ? `<span class="badge bg-info ms-1" style="font-size:10px">→ ${r.coluna_descricao}</span>` : ''}
            </div>
            <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="cbRemoverRelacionamento(${i})" title="Remover">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');

    // Redesenhar linhas do diagrama
    cbDrawRelLines();
}

async function cbSalvarRelacionamentos() {
    try {
        const resp = await HelpDesk.api('POST', '/api/chatbot.php', {
            action: 'save_relationships',
            relationships: window._dbRelacionamentos
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

async function cbCarregarRelacionamentos() {
    try {
        const resp = await HelpDesk.api('GET', '/api/chatbot.php', { action: 'get_relationships' });
        if (resp.success && resp.data) {
            window._dbRelacionamentos = Array.isArray(resp.data) ? resp.data : [];
            cbRenderRelacionamentos();
        }
    } catch (e) { /* silenciar */ }
}

function cbLimparRelacionamentos() {
    if (!confirm('Tem certeza que deseja remover TODOS os relacionamentos?')) return;
    window._dbRelacionamentos = [];
    cbRenderRelacionamentos();
    cbSalvarRelacionamentos();
}
</script>