<?php
/**
 * View: Inventário de TI — Ativos + Termos de Responsabilidade
 */
$tiposAtivo = [
    'computador'=>'Computador','servidor'=>'Servidor','switch'=>'Switch',
    'roteador'=>'Roteador','impressora'=>'Impressora','software'=>'Software',
    'monitor'=>'Monitor','telefone'=>'Telefone','outro'=>'Outro'
];
$statusAtivo = ['ativo'=>'Ativo','manutencao'=>'Manutenção','inativo'=>'Inativo','descartado'=>'Descartado'];
$statusCores = ['ativo'=>'#10B981','manutencao'=>'#F59E0B','inativo'=>'#6B7280','descartado'=>'#EF4444'];
$termoCores = ['pendente'=>'#F59E0B','assinado'=>'#10B981','cancelado'=>'#EF4444'];
$termoLabels = ['pendente'=>'Pendente','assinado'=>'Assinado','cancelado'=>'Cancelado'];

$abaAtiva = $_GET['aba'] ?? 'ativos';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Inventário de TI</h1>
        <p class="page-subtitle">Gestão de ativos e termos de responsabilidade</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-sm ia-insight-btn" onclick="iaInsight('inventario_lifecycle')">
            <i class="fas fa-robot"></i> Ciclo de Vida IA
        </button>
        <?php if ($abaAtiva === 'ativos'): ?>
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoAtivo')">
            <i class="fas fa-plus"></i> Novo Ativo
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs -->
<div class="inv-tabs">
    <a href="<?= BASE_URL ?>/index.php?page=inventario&aba=ativos" 
       class="inv-tab <?= $abaAtiva === 'ativos' ? 'active' : '' ?>">
        <i class="fas fa-boxes"></i> Ativos
        <span class="inv-tab-count"><?= count($inventarioItens ?? []) ?></span>
    </a>
    <a href="<?= BASE_URL ?>/index.php?page=inventario&aba=termos" 
       class="inv-tab <?= $abaAtiva === 'termos' ? 'active' : '' ?>">
        <i class="fas fa-file-signature"></i> Termos de Responsabilidade
        <span class="inv-tab-count"><?= count($termos ?? []) ?></span>
    </a>
</div>

<?php if ($abaAtiva === 'ativos'): ?>
<!-- ===================== ABA: ATIVOS ===================== -->
<div class="card">
    <!-- Filtros -->
    <div class="inv-filters">
        <div class="inv-filter-group">
            <div class="inv-search">
                <i class="fas fa-search"></i>
                <input type="text" id="buscaAtivo" placeholder="Buscar por nome, patrimônio ou modelo..." 
                       value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>"
                       onkeydown="if(event.key==='Enter') filtrarAtivos()">
            </div>
            <select class="form-select" id="filtroTipo" onchange="filtrarAtivos()">
                <option value="">Todos os Tipos</option>
                <?php foreach ($tiposAtivo as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($_GET['tipo'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" id="filtroStatus" onchange="filtrarAtivos()">
                <option value="">Todos os Status</option>
                <?php foreach ($statusAtivo as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Patrimônio</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Modelo</th>
                    <th>Localização</th>
                    <th>Responsável</th>
                    <th>Status</th>
                    <th style="width:140px">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventarioItens)): ?>
                <tr><td colspan="8" class="text-center py-4">
                    <div class="empty-state-sm"><i class="fas fa-boxes"></i><p>Nenhum ativo encontrado</p></div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($inventarioItens as $item): 
                    $iconMap = ['computador'=>'laptop','servidor'=>'server','switch'=>'network-wired','roteador'=>'wifi','impressora'=>'print','software'=>'compact-disc','monitor'=>'desktop','telefone'=>'phone','outro'=>'cube'];
                ?>
                <tr>
                    <td><span class="code-badge"><?= $item['numero_patrimonio'] ?? '-' ?></span></td>
                    <td><strong><?= htmlspecialchars($item['nome']) ?></strong></td>
                    <td><span class="inv-tipo-badge"><i class="fas fa-<?= $iconMap[$item['tipo']] ?? 'cube' ?>"></i> <?= $tiposAtivo[$item['tipo']] ?? $item['tipo'] ?></span></td>
                    <td><?= htmlspecialchars($item['modelo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['localizacao'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['responsavel_nome'] ?? '-') ?></td>
                    <td>
                        <?php $cor = $statusCores[$item['status']] ?? '#6B7280'; ?>
                        <span class="status-badge" style="background:<?= $cor ?>20;color:<?= $cor ?>">
                            <?= $statusAtivo[$item['status']] ?? $item['status'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="inv-actions">
                            <button class="btn btn-sm btn-ghost" title="Editar" onclick="editarAtivo(<?= $item['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-ghost" title="Gerar Termo" onclick="gerarTermo(<?= $item['id'] ?>)">
                                <i class="fas fa-file-signature"></i>
                            </button>
                            <button class="btn btn-sm btn-ghost text-danger" title="Excluir" onclick="excluirAtivo(<?= $item['id'] ?>, '<?= addslashes($item['nome']) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- ===================== ABA: TERMOS DE RESPONSABILIDADE ===================== -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Ativo</th>
                    <th>Patrimônio</th>
                    <th>Usuário</th>
                    <th>Técnico</th>
                    <th>Data Entrega</th>
                    <th>Status</th>
                    <th style="width:160px">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($termos)): ?>
                <tr><td colspan="8" class="text-center py-4">
                    <div class="empty-state-sm"><i class="fas fa-file-signature"></i><p>Nenhum termo registrado</p></div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($termos as $termo): ?>
                <tr>
                    <td><span class="code-badge"><?= $termo['codigo'] ?></span></td>
                    <td><strong><?= htmlspecialchars($termo['ativo_nome'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($termo['numero_patrimonio'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($termo['usuario_nome']) ?></td>
                    <td><?= htmlspecialchars($termo['tecnico_nome'] ?? '-') ?></td>
                    <td><?= date('d/m/Y', strtotime($termo['data_entrega'])) ?></td>
                    <td>
                        <?php $cor = $termoCores[$termo['status']] ?? '#6B7280'; ?>
                        <span class="status-badge" style="background:<?= $cor ?>20;color:<?= $cor ?>">
                            <i class="fas fa-<?= $termo['status'] === 'assinado' ? 'check-circle' : ($termo['status'] === 'pendente' ? 'clock' : 'times-circle') ?>"></i>
                            <?= $termoLabels[$termo['status']] ?? $termo['status'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="inv-actions">
                            <button class="btn btn-sm btn-ghost" title="Visualizar" onclick="verTermo(<?= $termo['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($termo['status'] === 'pendente'): ?>
                            <button class="btn btn-sm btn-ghost" title="Copiar Link" onclick="copiarLinkTermo('<?= $termo['token'] ?>')">
                                <i class="fas fa-link"></i>
                            </button>
                            <button class="btn btn-sm btn-ghost" title="Enviar WhatsApp" onclick="enviarTermoWhatsApp(<?= $termo['id'] ?>)">
                                <i class="fab fa-whatsapp" style="color:#25D366"></i>
                            </button>
                            <button class="btn btn-sm btn-ghost text-danger" title="Cancelar" onclick="cancelarTermo(<?= $termo['id'] ?>)">
                                <i class="fas fa-ban"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($termo['status'] === 'assinado'): ?>
                            <button class="btn btn-sm btn-ghost" title="Imprimir" onclick="imprimirTermo(<?= $termo['id'] ?>)">
                                <i class="fas fa-print"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
const tiposAtivo = <?= json_encode($tiposAtivo) ?>;
const statusAtivo = <?= json_encode($statusAtivo) ?>;
const baseUrl = '<?= BASE_URL ?>';

let tecOpts = '<option value="">Nenhum</option>';
<?php foreach ($tecnicos as $t): ?>
tecOpts += '<option value="<?= $t['id'] ?>"><?= addslashes($t['nome']) ?></option>';
<?php endforeach; ?>
let tipoOpts = '';
<?php foreach ($tiposAtivo as $k => $v): ?>
tipoOpts += '<option value="<?= $k ?>"><?= $v ?></option>';
<?php endforeach; ?>

// ===================== FILTROS =====================
function filtrarAtivos() {
    const tipo = document.getElementById('filtroTipo').value;
    const status = document.getElementById('filtroStatus').value;
    const busca = document.getElementById('buscaAtivo').value;
    let url = baseUrl + '/index.php?page=inventario&aba=ativos';
    if (tipo) url += '&tipo=' + tipo;
    if (status) url += '&status=' + status;
    if (busca) url += '&busca=' + encodeURIComponent(busca);
    location.href = url;
}

// ===================== NOVO ATIVO =====================
HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novoAtivo = function() {
    const html = `
    <form id="formNovoAtivo" class="form-grid">
        <div class="form-group"><label class="form-label">Tipo *</label><select name="tipo" class="form-select" required>${tipoOpts}</select></div>
        <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Nº Patrimônio</label><input type="text" name="numero_patrimonio" class="form-input"></div>
        <div class="form-group"><label class="form-label">Modelo</label><input type="text" name="modelo" class="form-input"></div>
        <div class="form-group"><label class="form-label">Fabricante</label><input type="text" name="fabricante" class="form-input"></div>
        <div class="form-group"><label class="form-label">Nº Série</label><input type="text" name="numero_serie" class="form-input"></div>
        <div class="form-group"><label class="form-label">Localização</label><input type="text" name="localizacao" class="form-input"></div>
        <div class="form-group"><label class="form-label">Responsável</label><select name="responsavel_id" class="form-select">${tecOpts}</select></div>
        <div class="form-group"><label class="form-label">Data Aquisição</label><input type="date" name="data_aquisicao" class="form-input"></div>
        <div class="form-group"><label class="form-label">Valor (R$)</label><input type="number" name="valor_aquisicao" class="form-input" step="0.01" min="0"></div>
        <div class="form-group"><label class="form-label">Garantia Até</label><input type="date" name="garantia_ate" class="form-input"></div>
        <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select"><option value="ativo">Ativo</option><option value="manutencao">Manutenção</option><option value="inativo">Inativo</option></select></div>
        <div class="form-group col-span-2"><label class="form-label">Especificações</label><textarea name="especificacoes" class="form-textarea" rows="2"></textarea></div>
        <div class="form-group col-span-2"><label class="form-label">Observações</label><textarea name="observacoes" class="form-textarea" rows="2"></textarea></div>
    </form>`;
    HelpDesk.showModal('Novo Ativo', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovoAtivo()"><i class="fas fa-save"></i> Cadastrar</button>
    `);
};

function submitNovoAtivo() {
    const form = document.getElementById('formNovoAtivo');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';
    HelpDesk.api('POST', '/api/inventario.php', data).then(resp => {
        if (resp && resp.success) { HelpDesk.toast('Ativo cadastrado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else if (resp && resp.error && resp.error !== 'Erro de conexão') HelpDesk.toast(resp.error, 'danger');
    });
}

// ===================== EDITAR ATIVO =====================
function editarAtivo(id) {
    HelpDesk.api('GET', '/api/inventario.php?action=ver&id=' + id).then(item => {
        if (!item || item.error) { HelpDesk.toast('Ativo não encontrado', 'danger'); return; }

        let editTipoOpts = '';
        for (const [k, v] of Object.entries(tiposAtivo)) editTipoOpts += `<option value="${k}" ${item.tipo===k?'selected':''}>${v}</option>`;
        let editTecOpts = '<option value="">Nenhum</option>';
        <?php foreach ($tecnicos as $t): ?>
        editTecOpts += `<option value="<?= $t['id'] ?>" ${item.responsavel_id==<?= $t['id'] ?>?'selected':''}><?= addslashes($t['nome']) ?></option>`;
        <?php endforeach; ?>
        let editStatusOpts = '';
        for (const [k, v] of Object.entries(statusAtivo)) editStatusOpts += `<option value="${k}" ${item.status===k?'selected':''}>${v}</option>`;

        const html = `
        <form id="formEditarAtivo" class="form-grid">
            <input type="hidden" name="id" value="${item.id}">
            <div class="form-group"><label class="form-label">Tipo *</label><select name="tipo" class="form-select" required>${editTipoOpts}</select></div>
            <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-input" required value="${item.nome||''}"></div>
            <div class="form-group"><label class="form-label">Nº Patrimônio</label><input type="text" name="numero_patrimonio" class="form-input" value="${item.numero_patrimonio||''}"></div>
            <div class="form-group"><label class="form-label">Modelo</label><input type="text" name="modelo" class="form-input" value="${item.modelo||''}"></div>
            <div class="form-group"><label class="form-label">Fabricante</label><input type="text" name="fabricante" class="form-input" value="${item.fabricante||''}"></div>
            <div class="form-group"><label class="form-label">Nº Série</label><input type="text" name="numero_serie" class="form-input" value="${item.numero_serie||''}"></div>
            <div class="form-group"><label class="form-label">Localização</label><input type="text" name="localizacao" class="form-input" value="${item.localizacao||''}"></div>
            <div class="form-group"><label class="form-label">Responsável</label><select name="responsavel_id" class="form-select">${editTecOpts}</select></div>
            <div class="form-group"><label class="form-label">Data Aquisição</label><input type="date" name="data_aquisicao" class="form-input" value="${item.data_aquisicao||''}"></div>
            <div class="form-group"><label class="form-label">Valor (R$)</label><input type="number" name="valor_aquisicao" class="form-input" step="0.01" value="${item.valor_aquisicao||''}"></div>
            <div class="form-group"><label class="form-label">Garantia Até</label><input type="date" name="garantia_ate" class="form-input" value="${item.garantia_ate||''}"></div>
            <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select">${editStatusOpts}</select></div>
            <div class="form-group col-span-2"><label class="form-label">Especificações</label><textarea name="especificacoes" class="form-textarea" rows="2">${item.especificacoes||''}</textarea></div>
            <div class="form-group col-span-2"><label class="form-label">Observações</label><textarea name="observacoes" class="form-textarea" rows="2">${item.observacoes||''}</textarea></div>
        </form>`;
        HelpDesk.showModal('Editar: ' + item.nome, html, `
            <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="submitEditarAtivo()"><i class="fas fa-save"></i> Salvar</button>
        `);
    });
}

function submitEditarAtivo() {
    const form = document.getElementById('formEditarAtivo');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const data = Object.fromEntries(new FormData(form));
    data.action = 'atualizar';
    HelpDesk.api('POST', '/api/inventario.php', data).then(resp => {
        if (resp && resp.success) { HelpDesk.toast('Ativo atualizado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else if (resp && resp.error && resp.error !== 'Erro de conexão') HelpDesk.toast(resp.error, 'danger');
    });
}

// ===================== EXCLUIR ATIVO =====================
function excluirAtivo(id, nome) {
    if (!confirm('Excluir o ativo "' + nome + '"? Ação irreversível.')) return;
    HelpDesk.api('POST', '/api/inventario.php', { action: 'deletar', id: id }).then(resp => {
        if (resp.success) { HelpDesk.toast('Ativo excluído!', 'success'); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro ao excluir', 'danger');
    });
}

// ===================== GERAR TERMO =====================
function gerarTermo(ativoId) {
    HelpDesk.api('GET', '/api/inventario.php?action=ver&id=' + ativoId).then(item => {
        if (!item || item.error) { HelpDesk.toast('Ativo não encontrado', 'danger'); return; }

        let tecOpts2 = '<option value="">Selecione...</option>';
        <?php foreach ($tecnicos as $t): ?>
        tecOpts2 += '<option value="<?= $t['id'] ?>" data-nome="<?= addslashes($t['nome']) ?>"><?= addslashes($t['nome']) ?></option>';
        <?php endforeach; ?>

        const html = `
        <div class="inv-termo-form">
            <div class="inv-termo-ativo-info">
                <h4><i class="fas fa-laptop"></i> ${item.nome}</h4>
                <p>Patrimônio: <strong>${item.numero_patrimonio||'N/A'}</strong> | Modelo: ${item.modelo||'N/A'} | Série: ${item.numero_serie||'N/A'}</p>
            </div>
            <form id="formGerarTermo" class="form-grid">
                <input type="hidden" name="ativo_id" value="${ativoId}">
                <div class="form-group col-span-2"><label class="form-label">Nome do Usuário *</label><input type="text" name="usuario_nome" class="form-input" required placeholder="Nome completo do responsável"></div>
                <div class="form-group"><label class="form-label">Cargo</label><input type="text" name="usuario_cargo" class="form-input" placeholder="Ex: Analista"></div>
                <div class="form-group"><label class="form-label">Departamento</label><input type="text" name="usuario_departamento" class="form-input" placeholder="Ex: Marketing"></div>
                <div class="form-group"><label class="form-label">Telefone (WhatsApp) *</label><input type="text" name="usuario_telefone" class="form-input" required placeholder="5562999999999" value="55"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="usuario_email" class="form-input"></div>
                <div class="form-group col-span-2"><label class="form-label">Técnico Responsável *</label><select name="tecnico_id" class="form-select" required onchange="onTecnicoChange(this)">${tecOpts2}</select></div>
                <div class="form-group col-span-2"><label class="form-label">Condições / Observações</label><textarea name="condicoes" class="form-textarea" rows="3" placeholder="Estado de conservação, acessórios inclusos..."></textarea></div>
                <div class="form-group col-span-2">
                    <label class="form-label"><i class="fas fa-camera"></i> Fotos do Equipamento</label>
                    <p style="font-size:.78rem;color:var(--gray-500);margin-bottom:8px">Anexe fotos do estado atual do equipamento (máx. 10 fotos, JPG/PNG/WebP)</p>
                    <div class="inv-fotos-actions">
                        <div class="inv-fotos-dropzone" id="fotosDropzone" onclick="document.getElementById('inputFotos').click()" ondragover="event.preventDefault();this.classList.add('dragover')" ondragleave="this.classList.remove('dragover')" ondrop="handleFotosDrop(event)">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Clique ou arraste fotos aqui</span>
                            <input type="file" id="inputFotos" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="handleFotosSelect(this.files)">
                        </div>
                        <button type="button" class="inv-fotos-camera-btn" onclick="abrirCameraParaTermo()">
                            <i class="fas fa-camera"></i>
                            <span>Tirar Foto</span>
                        </button>
                    </div>
                    <div class="inv-fotos-preview" id="fotosPreview"></div>
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label"><i class="fas fa-pen-fancy"></i> Assinatura do Técnico *</label>
                    <div class="inv-sig-tabs">
                        <button type="button" class="inv-sig-tab active" onclick="switchTermoSigTab('desenhar')"><i class="fas fa-pen-fancy"></i> Desenhar</button>
                        <button type="button" class="inv-sig-tab" onclick="switchTermoSigTab('rubrica')"><i class="fas fa-font"></i> Rubrica</button>
                    </div>
                    <div id="termoSigDesenhar" class="inv-sig-panel active">
                        <p style="font-size:.78rem;color:var(--gray-500);margin-bottom:8px">Assine no quadro abaixo:</p>
                        <div class="inv-sig-wrapper">
                            <canvas id="canvasTecnico" width="500" height="150"></canvas>
                            <button type="button" class="inv-sig-clear" onclick="limparAssinatura('canvasTecnico')"><i class="fas fa-eraser"></i> Limpar</button>
                        </div>
                    </div>
                    <div id="termoSigRubrica" class="inv-sig-panel">
                        <p style="font-size:.78rem;color:var(--gray-500);margin-bottom:8px">Rubrica gerada com o nome do técnico selecionado:</p>
                        <div style="text-align:center;padding:8px 0">
                            <div style="display:inline-block;border:1px solid var(--gray-200);border-radius:10px;padding:8px;background:#FAFAFA">
                                <canvas id="canvasTecRubrica" width="400" height="100"></canvas>
                            </div>
                            <div style="display:flex;gap:6px;justify-content:center;margin-top:10px">
                                <button type="button" class="inv-rub-btn active" onclick="changeTecRubStyle(0)">Cursiva</button>
                                <button type="button" class="inv-rub-btn" onclick="changeTecRubStyle(1)">Elegante</button>
                                <button type="button" class="inv-rub-btn" onclick="changeTecRubStyle(2)">Iniciais</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>`;

        HelpDesk.showModal('<i class="fas fa-file-signature"></i> Gerar Termo de Responsabilidade', html, `
            <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="submitGerarTermo()"><i class="fas fa-paper-plane"></i> Gerar e Enviar via WhatsApp</button>
        `, 'modal-lg');
        setTimeout(() => initSignaturePad('canvasTecnico'), 200);
    });
}

let termoSigModo = 'desenhar';
let tecRubStyleIdx = 0;
let tecnicoNomeAtual = '';
let termoFotosFiles = []; // Array of File objects for upload

function switchTermoSigTab(modo) {
    termoSigModo = modo;
    document.querySelectorAll('.inv-sig-tab').forEach((t, i) => t.classList.toggle('active', (i===0&&modo==='desenhar')||(i===1&&modo==='rubrica')));
    document.getElementById('termoSigDesenhar').classList.toggle('active', modo==='desenhar');
    document.getElementById('termoSigRubrica').classList.toggle('active', modo==='rubrica');
    if (modo === 'rubrica') renderTecRubrica();
}

function onTecnicoChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    tecnicoNomeAtual = opt?.dataset?.nome || '';
    if (termoSigModo === 'rubrica') renderTecRubrica();
}

function changeTecRubStyle(idx) {
    tecRubStyleIdx = idx;
    document.querySelectorAll('.inv-rub-btn').forEach((b, i) => b.classList.toggle('active', i===idx));
    renderTecRubrica();
}

function renderTecRubrica() {
    const rc = document.getElementById('canvasTecRubrica');
    if (!rc) return;
    const rctx = rc.getContext('2d');
    rc.width = 800; rc.height = 200;
    rc.style.width = '400px'; rc.style.height = '100px';
    rctx.scale(2, 2);
    rctx.clearRect(0, 0, 400, 100);
    if (!tecnicoNomeAtual) { rctx.fillStyle = '#94A3B8'; rctx.font = '14px Inter,sans-serif'; rctx.textAlign = 'center'; rctx.fillText('Selecione um técnico acima', 200, 55); return; }
    const fonts = ['48px "Dancing Script"', '44px "Great Vibes"', 'italic bold 50px "Playfair Display"'];
    const partes = tecnicoNomeAtual.trim().split(/\s+/);
    let texto;
    if (tecRubStyleIdx === 2) texto = (partes[0].charAt(0) + (partes.length > 1 ? partes[partes.length-1].charAt(0) : '')).toUpperCase();
    else texto = partes.length > 1 ? partes[0] + ' ' + partes[partes.length-1] : partes[0];
    rctx.font = fonts[tecRubStyleIdx]; rctx.fillStyle = '#1E293B'; rctx.textAlign = 'center'; rctx.textBaseline = 'middle';
    rctx.fillText(texto, 200, 45);
    const m = rctx.measureText(texto);
    rctx.strokeStyle = '#1E293B'; rctx.lineWidth = 1; rctx.globalAlpha = 0.3;
    rctx.beginPath(); rctx.moveTo(200-m.width/2-10, 72); rctx.quadraticCurveTo(200, 66, 200+m.width/2+10, 72); rctx.stroke();
    rctx.globalAlpha = 1;
}

// ===================== FOTOS DO EQUIPAMENTO =====================
function handleFotosDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    if (e.dataTransfer.files) handleFotosSelect(e.dataTransfer.files);
}

function handleFotosSelect(files) {
    const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    for (const f of files) {
        if (!allowed.includes(f.type)) { HelpDesk.toast('Arquivo "'+f.name+'" não é uma imagem válida.', 'warning'); continue; }
        if (f.size > 10*1024*1024) { HelpDesk.toast('Arquivo "'+f.name+'" excede 10MB.', 'warning'); continue; }
        if (termoFotosFiles.length >= 10) { HelpDesk.toast('Máximo de 10 fotos atingido.', 'warning'); break; }
        termoFotosFiles.push(f);
    }
    renderFotosPreview();
}

function renderFotosPreview() {
    const container = document.getElementById('fotosPreview');
    if (!container) return;
    if (termoFotosFiles.length === 0) { container.innerHTML = ''; return; }
    let html = '';
    termoFotosFiles.forEach((f, i) => {
        const url = URL.createObjectURL(f);
        html += `<div class="inv-foto-item">
            <img src="${url}" alt="${f.name}">
            <button type="button" class="inv-foto-remove" onclick="removeFotoPreview(${i})" title="Remover"><i class="fas fa-times"></i></button>
            <span class="inv-foto-name">${f.name.length>20?f.name.substring(0,17)+'...':f.name}</span>
        </div>`;
    });
    container.innerHTML = html;
}

function removeFotoPreview(idx) {
    termoFotosFiles.splice(idx, 1);
    renderFotosPreview();
}

function submitGerarTermo() {
    const form = document.getElementById('formGerarTermo');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    let assinaturaData;
    if (termoSigModo === 'desenhar') {
        const canvas = document.getElementById('canvasTecnico');
        if (isCanvasBlank(canvas)) { HelpDesk.toast('Assine o termo como técnico responsável.', 'warning'); return; }
        assinaturaData = canvas.toDataURL('image/png');
    } else {
        if (!tecnicoNomeAtual) { HelpDesk.toast('Selecione um técnico antes de usar rubrica.', 'warning'); return; }
        assinaturaData = document.getElementById('canvasTecRubrica').toDataURL('image/png');
    }

    // Usar FormData para suportar upload de fotos
    const fd = new FormData(form);
    fd.append('action', 'gerar_termo');
    fd.append('tecnico_assinatura', assinaturaData);
    termoFotosFiles.forEach(f => fd.append('fotos[]', f));

    // Mostrar modal de carregamento
    const hasFotos = termoFotosFiles.length > 0;
    mostrarLoadingTermo(hasFotos);

    HelpDesk.api('POST', '/api/inventario.php', fd, true).then(resp => {
        if (resp && resp.success) {
            atualizarLoadingStep(hasFotos ? 3 : 2, true, resp.codigo);
        } else {
            fecharLoadingTermo();
            if (resp && resp.error) HelpDesk.toast(resp.error, 'danger');
            else HelpDesk.toast('Erro ao gerar termo.', 'danger');
        }
    }).catch(() => {
        fecharLoadingTermo();
        HelpDesk.toast('Erro de conexão ao gerar termo.', 'danger');
    });
}

// ===================== LOADING MODAL =====================
let _loadingOverlay = null;

function mostrarLoadingTermo(hasFotos) {
    // Fechar modal do formulário
    HelpDesk.closeModal();

    const steps = [
        { icon: 'fa-file-signature', text: 'Gerando termo de responsabilidade...', id: 'step-termo' },
    ];
    if (hasFotos) {
        steps.push({ icon: 'fa-cloud-upload-alt', text: 'Enviando fotos do equipamento...', id: 'step-fotos' });
    }
    steps.push({ icon: 'fa-paper-plane', text: 'Enviando link via WhatsApp...', id: 'step-whatsapp' });

    let stepsHtml = steps.map((s, i) => `
        <div class="loading-step ${i === 0 ? 'active' : ''}" id="${s.id}">
            <div class="loading-step-icon">
                <i class="fas ${s.icon}"></i>
                <div class="loading-step-check"><i class="fas fa-check"></i></div>
            </div>
            <span class="loading-step-text">${s.text}</span>
        </div>
    `).join('');

    const overlay = document.createElement('div');
    overlay.className = 'loading-termo-overlay';
    overlay.innerHTML = `
        <div class="loading-termo-box">
            <div class="loading-termo-spinner">
                <div class="loading-spinner-ring"></div>
                <i class="fas fa-file-signature loading-spinner-icon"></i>
            </div>
            <h3 class="loading-termo-title">Gerando Termo...</h3>
            <p class="loading-termo-subtitle">Aguarde enquanto processamos</p>
            <div class="loading-steps-list">${stepsHtml}</div>
        </div>
        <div class="loading-termo-success" id="loadingSuccess" style="display:none">
            <div class="loading-success-icon"><i class="fas fa-check-circle"></i></div>
            <h3>Termo Gerado com Sucesso!</h3>
            <p class="loading-success-code" id="loadingCodigoTermo"></p>
            <p class="loading-success-msg">O link para assinatura foi enviado via WhatsApp.</p>
        </div>
    `;
    document.body.appendChild(overlay);
    _loadingOverlay = overlay;

    // Animação dos steps
    setTimeout(() => overlay.classList.add('visible'), 10);
    _loadingStepIndex = 0;
    _loadingTotalSteps = steps.length;
    _loadingHasFotos = hasFotos;
    animarLoadingSteps();
}

let _loadingStepIndex = 0;
let _loadingTotalSteps = 2;
let _loadingHasFotos = false;
let _loadingStepTimer = null;

function animarLoadingSteps() {
    // Avançar steps automaticamente a cada ~1.5s (visual apenas)
    const stepIds = ['step-termo'];
    if (_loadingHasFotos) stepIds.push('step-fotos');
    stepIds.push('step-whatsapp');

    let idx = 0;
    _loadingStepTimer = setInterval(() => {
        if (idx < stepIds.length - 1) {
            const el = document.getElementById(stepIds[idx]);
            if (el) { el.classList.remove('active'); el.classList.add('done'); }
            idx++;
            const next = document.getElementById(stepIds[idx]);
            if (next) next.classList.add('active');
        } else {
            clearInterval(_loadingStepTimer);
        }
    }, 1500);
}

function atualizarLoadingStep(finalStep, sucesso, codigo) {
    if (_loadingStepTimer) clearInterval(_loadingStepTimer);

    // Marcar todos os steps como concluídos
    const stepIds = ['step-termo'];
    if (_loadingHasFotos) stepIds.push('step-fotos');
    stepIds.push('step-whatsapp');
    stepIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.classList.remove('active'); el.classList.add('done'); }
    });

    if (sucesso) {
        setTimeout(() => {
            if (!_loadingOverlay) return;
            // Trocar para tela de sucesso
            const box = _loadingOverlay.querySelector('.loading-termo-box');
            const suc = document.getElementById('loadingSuccess');
            if (box) box.style.display = 'none';
            if (suc) {
                suc.style.display = 'block';
                const codEl = document.getElementById('loadingCodigoTermo');
                if (codEl && codigo) codEl.textContent = codigo;
            }
            termoFotosFiles = [];
            // Redirecionar após 2.5s
            setTimeout(() => {
                fecharLoadingTermo();
                location.href = baseUrl + '/index.php?page=inventario&aba=termos';
            }, 2500);
        }, 600);
    }
}

function fecharLoadingTermo() {
    if (_loadingStepTimer) clearInterval(_loadingStepTimer);
    if (_loadingOverlay) {
        _loadingOverlay.classList.remove('visible');
        setTimeout(() => {
            if (_loadingOverlay && _loadingOverlay.parentNode) {
                _loadingOverlay.parentNode.removeChild(_loadingOverlay);
            }
            _loadingOverlay = null;
        }, 300);
    }
}

// ===================== AÇÕES TERMOS =====================
function verTermo(id) {
    HelpDesk.api('GET', '/api/inventario.php?action=ver_termo&id=' + id).then(t => {
        if (!t || t.error) { HelpDesk.toast('Termo não encontrado', 'danger'); return; }
        const si = t.status==='assinado'?'✅':(t.status==='pendente'?'⏳':'❌');
        const sl = t.status==='assinado'?'Assinado':(t.status==='pendente'?'Pendente':'Cancelado');

        // Construir galeria de fotos
        const qtdFotos = (t.fotos && t.fotos.length) || 0;
        let fotosHtml = `<div style="margin-top:20px">
            <h4 style="color:var(--gray-700);margin-bottom:10px"><i class="fas fa-camera"></i> Fotos do Equipamento (${qtdFotos})</h4>`;
        if (qtdFotos > 0) {
            fotosHtml += `<div class="inv-fotos-gallery">`;
            t.fotos.forEach(f => {
                const url = baseUrl + '/uploads/termos/' + f.nome_arquivo;
                fotosHtml += `<div class="inv-foto-gallery-item">
                    <img src="${url}" alt="${f.nome_original}" onclick="ampliarFoto('${url}','${f.nome_original.replace(/'/g,"\\'")}')"> 
                    <button type="button" class="inv-foto-remove" onclick="excluirFotoTermo(${f.id},${t.id})" title="Excluir foto"><i class="fas fa-trash"></i></button>
                </div>`;
            });
            fotosHtml += `</div>`;
        } else {
            fotosHtml += `<p style="color:var(--gray-400);font-size:.85rem;padding:12px 0"><i class="fas fa-image"></i> Nenhuma foto anexada</p>`;
        }
        // Botões para adicionar fotos (galeria + câmera)
        fotosHtml += `<div class="inv-fotos-add-btns">
            <button type="button" class="btn btn-sm btn-secondary" onclick="abrirUploadFotos(${t.id})">
                <i class="fas fa-images"></i> Selecionar Fotos
            </button>
            <button type="button" class="btn btn-sm btn-primary" onclick="abrirCameraParaTermoExistente(${t.id})">
                <i class="fas fa-camera"></i> Tirar Foto
            </button>
        </div></div>`;
        let addFotosHtml = '';
        let html = `
        <div class="inv-termo-view">
            <div class="inv-termo-header-info"><h3>${t.codigo} ${si} ${sl}</h3><p>Gerado em ${fmtDate(t.criado_em)}</p></div>
            <div class="form-grid" style="gap:12px">
                <div class="inv-info-block"><span class="inv-info-lbl">Ativo</span><span class="inv-info-val">${t.ativo_nome} (${t.numero_patrimonio||'S/N'})</span></div>
                <div class="inv-info-block"><span class="inv-info-lbl">Tipo</span><span class="inv-info-val">${tiposAtivo[t.ativo_tipo]||t.ativo_tipo}</span></div>
                <div class="inv-info-block"><span class="inv-info-lbl">Usuário</span><span class="inv-info-val">${t.usuario_nome}</span></div>
                <div class="inv-info-block"><span class="inv-info-lbl">Cargo / Depto</span><span class="inv-info-val">${t.usuario_cargo||'-'} / ${t.usuario_departamento||'-'}</span></div>
                <div class="inv-info-block"><span class="inv-info-lbl">Técnico</span><span class="inv-info-val">${t.tecnico_nome||'-'}</span></div>
                <div class="inv-info-block"><span class="inv-info-lbl">Data Entrega</span><span class="inv-info-val">${fmtDate(t.data_entrega)}</span></div>
            </div>
            ${t.condicoes?'<div style="margin-top:16px"><strong>Condições:</strong><p style="color:var(--gray-600);margin-top:4px">'+t.condicoes+'</p></div>':''}
            ${fotosHtml}
            ${addFotosHtml}
            <div class="inv-termo-sigs">
                <div class="inv-termo-sig-box">
                    <h4>Assinatura do Técnico</h4>
                    ${t.tecnico_assinatura?'<img src="'+t.tecnico_assinatura+'" alt="Assinatura Técnico">':'<p class="inv-sig-pending">Não assinou</p>'}
                    ${t.data_assinatura_tecnico?'<small>'+fmtDate(t.data_assinatura_tecnico)+'</small>':''}
                </div>
                <div class="inv-termo-sig-box">
                    <h4>Assinatura do Usuário</h4>
                    ${t.usuario_assinatura?'<img src="'+t.usuario_assinatura+'" alt="Assinatura Usuário">':'<p class="inv-sig-pending">Aguardando assinatura</p>'}
                    ${t.data_assinatura_usuario?'<small>'+fmtDate(t.data_assinatura_usuario)+'</small>':''}
                </div>
            </div>
        </div>`;
        let footer = '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>';
        if (t.status==='assinado') footer += ` <button class="btn btn-primary" onclick="imprimirTermo(${t.id})"><i class="fas fa-print"></i> Imprimir</button>`;
        HelpDesk.showModal('Termo de Responsabilidade', html, footer, 'modal-lg');
    });
}

function ampliarFoto(url, nome) {
    const html = `<div style="text-align:center"><img src="${url}" alt="${nome}" style="max-width:100%;max-height:70vh;border-radius:8px"></div>`;
    HelpDesk.showModal('<i class="fas fa-image"></i> ' + nome, html, '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>', 'modal-lg');
}

function abrirUploadFotos(termoId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    input.accept = 'image/jpeg,image/png,image/gif,image/webp';
    input.onchange = function() {
        if (!input.files.length) return;
        const fd = new FormData();
        fd.append('action', 'upload_fotos_termo');
        fd.append('termo_id', termoId);
        for (const f of input.files) fd.append('fotos[]', f);
        HelpDesk.api('POST', '/api/inventario.php', fd, true).then(resp => {
            if (resp && resp.success) {
                HelpDesk.toast(resp.uploaded + ' foto(s) enviada(s)!', 'success');
                HelpDesk.closeModal();
                setTimeout(() => verTermo(termoId), 300);
            } else HelpDesk.toast(resp?.error || 'Erro ao enviar fotos', 'danger');
        });
    };
    input.click();
}

// ===================== CÂMERA =====================
function abrirCameraParaTermo() {
    // Ao criar termo: adiciona foto ao array local
    abrirCameraModal(function(file) {
        if (termoFotosFiles.length >= 10) { HelpDesk.toast('Máximo de 10 fotos atingido.', 'warning'); return; }
        termoFotosFiles.push(file);
        renderFotosPreview();
    });
}

function abrirCameraParaTermoExistente(termoId) {
    // Ao visualizar termo: envia foto direto via API
    abrirCameraModal(function(file) {
        const fd = new FormData();
        fd.append('action', 'upload_fotos_termo');
        fd.append('termo_id', termoId);
        fd.append('fotos[]', file);
        HelpDesk.api('POST', '/api/inventario.php', fd, true).then(resp => {
            if (resp && resp.success) {
                HelpDesk.toast('Foto capturada e enviada!', 'success');
                HelpDesk.closeModal();
                setTimeout(() => verTermo(termoId), 300);
            } else HelpDesk.toast(resp?.error || 'Erro ao enviar foto', 'danger');
        });
    });
}

function abrirCameraModal(onCapture) {
    // Tentar detectar se é mobile
    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    if (isMobile) {
        // Mobile: usar input nativo com capture (abre câmera direto)
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.capture = 'environment';
        input.onchange = function() {
            if (input.files.length > 0) onCapture(input.files[0]);
        };
        input.click();
        return;
    }
    // Desktop: abrir modal com getUserMedia
    const modalHtml = `
    <div class="inv-camera-container">
        <video id="cameraStream" autoplay playsinline style="width:100%;max-height:60vh;border-radius:10px;background:#000"></video>
        <canvas id="cameraCanvas" style="display:none"></canvas>
        <div id="cameraPreviewWrap" style="display:none;text-align:center">
            <img id="cameraPreviewImg" style="max-width:100%;max-height:55vh;border-radius:10px;border:2px solid #10B981">
        </div>
        <div class="inv-camera-controls">
            <select id="cameraSelect" class="form-select" style="max-width:250px;font-size:.8rem" onchange="trocarCamera(this.value)"></select>
            <div class="inv-camera-btns" id="cameraBtnsCapture">
                <button type="button" class="btn btn-lg btn-danger inv-camera-shutter" onclick="capturarFoto()" title="Capturar">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            <div class="inv-camera-btns" id="cameraBtnsConfirm" style="display:none">
                <button type="button" class="btn btn-secondary" onclick="descartarFoto()"><i class="fas fa-redo"></i> Nova Foto</button>
                <button type="button" class="btn btn-primary" onclick="confirmarFoto()"><i class="fas fa-check"></i> Usar esta Foto</button>
            </div>
        </div>
    </div>`;
    HelpDesk.showModal('<i class="fas fa-camera"></i> Câmera', modalHtml, 
        '<button class="btn btn-secondary" onclick="fecharCamera()">Cancelar</button>', 'modal-lg');
    
    window._cameraOnCapture = onCapture;
    window._cameraStream = null;
    window._cameraBlob = null;
    setTimeout(iniciarCamera, 300);
}

async function iniciarCamera(deviceId) {
    try {
        if (window._cameraStream) {
            window._cameraStream.getTracks().forEach(t => t.stop());
        }
        const constraints = { video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' }, audio: false };
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        window._cameraStream = stream;
        const video = document.getElementById('cameraStream');
        if (video) { video.srcObject = stream; }
        // Popular lista de câmeras
        const devices = await navigator.mediaDevices.enumerateDevices();
        const sel = document.getElementById('cameraSelect');
        if (sel && sel.options.length === 0) {
            const cameras = devices.filter(d => d.kind === 'videoinput');
            cameras.forEach((cam, i) => {
                const opt = document.createElement('option');
                opt.value = cam.deviceId;
                opt.textContent = cam.label || ('Câmera ' + (i + 1));
                if (deviceId && cam.deviceId === deviceId) opt.selected = true;
                sel.appendChild(opt);
            });
            if (cameras.length <= 1) sel.style.display = 'none';
        }
    } catch (e) {
        HelpDesk.toast('Não foi possível acessar a câmera. Verifique as permissões.', 'danger');
        fecharCamera();
    }
}

function trocarCamera(deviceId) {
    iniciarCamera(deviceId);
}

function capturarFoto() {
    const video = document.getElementById('cameraStream');
    const canvas = document.getElementById('cameraCanvas');
    if (!video || !canvas) return;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    canvas.toBlob(function(blob) {
        window._cameraBlob = blob;
        const url = URL.createObjectURL(blob);
        document.getElementById('cameraPreviewImg').src = url;
        video.style.display = 'none';
        document.getElementById('cameraPreviewWrap').style.display = 'block';
        document.getElementById('cameraBtnsCapture').style.display = 'none';
        document.getElementById('cameraBtnsConfirm').style.display = 'flex';
    }, 'image/jpeg', 0.85);
}

function descartarFoto() {
    window._cameraBlob = null;
    document.getElementById('cameraStream').style.display = 'block';
    document.getElementById('cameraPreviewWrap').style.display = 'none';
    document.getElementById('cameraBtnsCapture').style.display = 'flex';
    document.getElementById('cameraBtnsConfirm').style.display = 'none';
}

function confirmarFoto() {
    if (!window._cameraBlob || !window._cameraOnCapture) return;
    const file = new File([window._cameraBlob], 'foto_' + Date.now() + '.jpg', { type: 'image/jpeg' });
    fecharCamera();
    window._cameraOnCapture(file);
}

function fecharCamera() {
    if (window._cameraStream) {
        window._cameraStream.getTracks().forEach(t => t.stop());
        window._cameraStream = null;
    }
    window._cameraBlob = null;
    window._cameraOnCapture = null;
    HelpDesk.closeModal();
}

function excluirFotoTermo(fotoId, termoId) {
    if (!confirm('Excluir esta foto?')) return;
    HelpDesk.api('POST', '/api/inventario.php', { action: 'excluir_foto_termo', foto_id: fotoId }).then(resp => {
        if (resp && resp.success) {
            HelpDesk.toast('Foto excluída!', 'success');
            HelpDesk.closeModal();
            setTimeout(() => verTermo(termoId), 300);
        } else HelpDesk.toast(resp?.error || 'Erro ao excluir', 'danger');
    });
}

function copiarLinkTermo(token) {
    const url = location.origin + baseUrl + '/portal/assinar.php?token=' + token;
    navigator.clipboard.writeText(url).then(() => HelpDesk.toast('Link copiado!', 'success'));
}

function enviarTermoWhatsApp(id) {
    HelpDesk.api('POST', '/api/inventario.php', { action: 'enviar_termo_whatsapp', id: id }).then(resp => {
        if (resp.success) HelpDesk.toast('Link enviado via WhatsApp!', 'success');
        else HelpDesk.toast(resp.error || 'Erro ao enviar', 'danger');
    });
}

function cancelarTermo(id) {
    if (!confirm('Cancelar este termo?')) return;
    HelpDesk.api('POST', '/api/inventario.php', { action: 'cancelar_termo', id: id }).then(resp => {
        if (resp.success) { HelpDesk.toast('Termo cancelado.', 'success'); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}

function imprimirTermo(id) {
    window.open(baseUrl + '/portal/assinar.php?termo_id=' + id + '&print=1', '_blank');
}

// ===================== CANVAS ASSINATURA =====================
const sigPads = {};
function initSignaturePad(cid) {
    const c = document.getElementById(cid);
    if (!c) return;
    const ctx = c.getContext('2d');
    const r = c.getBoundingClientRect();
    c.width = r.width * 2; c.height = r.height * 2;
    ctx.scale(2, 2);
    c.style.width = r.width + 'px'; c.style.height = r.height + 'px';
    ctx.strokeStyle = '#1E293B'; ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.lineJoin = 'round';

    let d = false, lx = 0, ly = 0;
    function gp(e) { const rr = c.getBoundingClientRect(); const t = e.touches ? e.touches[0] : e; return { x: t.clientX - rr.left, y: t.clientY - rr.top }; }
    function sd(e) { e.preventDefault(); d = true; const p = gp(e); lx = p.x; ly = p.y; }
    function dr(e) { if (!d) return; e.preventDefault(); const p = gp(e); ctx.beginPath(); ctx.moveTo(lx, ly); ctx.lineTo(p.x, p.y); ctx.stroke(); lx = p.x; ly = p.y; }
    function ed() { d = false; }

    c.addEventListener('mousedown', sd); c.addEventListener('mousemove', dr); c.addEventListener('mouseup', ed); c.addEventListener('mouseleave', ed);
    c.addEventListener('touchstart', sd, {passive:false}); c.addEventListener('touchmove', dr, {passive:false}); c.addEventListener('touchend', ed);
    sigPads[cid] = { canvas: c, ctx };
}

function limparAssinatura(cid) { const p = sigPads[cid]; if (p) p.ctx.clearRect(0, 0, p.canvas.width, p.canvas.height); }
function isCanvasBlank(c) { const d = c.getContext('2d').getImageData(0, 0, c.width, c.height).data; for (let i = 3; i < d.length; i += 4) if (d[i] !== 0) return false; return true; }
function fmtDate(s) { if (!s) return '-'; const d = new Date(s); return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}); }
</script>
