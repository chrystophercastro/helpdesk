<?php
/**
 * View: Suprimentos de TI — Estoque + Requisições de Compra + Importação
 */
$user = currentUser();

$statusReqLabels = [
    'rascunho'  => 'Rascunho',
    'pendente'  => 'Pendente',
    'em_analise'=> 'Em Análise',
    'aprovada'  => 'Aprovada',
    'reprovada' => 'Reprovada',
    'comprada'  => 'Comprada',
    'entregue'  => 'Entregue',
    'cancelada' => 'Cancelada'
];
$statusReqCores = [
    'rascunho'  => '#9CA3AF',
    'pendente'  => '#F59E0B',
    'em_analise'=> '#3B82F6',
    'aprovada'  => '#10B981',
    'reprovada' => '#EF4444',
    'comprada'  => '#8B5CF6',
    'entregue'  => '#059669',
    'cancelada' => '#6B7280'
];

$prioridadeLabels = ['baixa'=>'Baixa','media'=>'Média','alta'=>'Alta','urgente'=>'Urgente'];
$prioridadeCores = ['baixa'=>'#10B981','media'=>'#F59E0B','alta'=>'#EF4444','urgente'=>'#DC2626'];

$movTipoLabels = ['entrada'=>'Entrada','saida'=>'Saída','ajuste'=>'Ajuste','devolucao'=>'Devolução'];
$movTipoCores = ['entrada'=>'#10B981','saida'=>'#EF4444','ajuste'=>'#3B82F6','devolucao'=>'#F59E0B'];

$abaAtiva = $_GET['aba'] ?? 'produtos';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-warehouse" style="color:#8B5CF6"></i> Suprimentos de TI</h1>
        <p class="page-subtitle">Gestão de estoque, movimentações e requisições de compra</p>
    </div>
    <div class="page-actions">
        <?php if ($abaAtiva === 'produtos'): ?>
        <button class="btn btn-outline" onclick="abrirModalMovimentacao()"><i class="fas fa-exchange-alt"></i> Movimentar</button>
        <button class="btn btn-primary" onclick="abrirModalProduto()"><i class="fas fa-plus"></i> Novo Produto</button>
        <?php elseif ($abaAtiva === 'requisicoes'): ?>
        <button class="btn btn-primary" onclick="abrirModalRequisicao()"><i class="fas fa-plus"></i> Nova Requisição</button>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs -->
<div class="supri-tabs">
    <a href="?page=suprimentos&aba=produtos" class="supri-tab <?= $abaAtiva === 'produtos' ? 'active' : '' ?>">
        <i class="fas fa-boxes"></i> Produtos
    </a>
    <a href="?page=suprimentos&aba=movimentacoes" class="supri-tab <?= $abaAtiva === 'movimentacoes' ? 'active' : '' ?>">
        <i class="fas fa-exchange-alt"></i> Movimentações
    </a>
    <a href="?page=suprimentos&aba=requisicoes" class="supri-tab <?= $abaAtiva === 'requisicoes' ? 'active' : '' ?>">
        <i class="fas fa-shopping-cart"></i> Requisições
    </a>
    <a href="?page=suprimentos&aba=importar" class="supri-tab <?= $abaAtiva === 'importar' ? 'active' : '' ?>">
        <i class="fas fa-file-import"></i> Importar
    </a>
</div>

<?php if ($abaAtiva === 'produtos'): ?>
<!-- ===================== ABA: PRODUTOS ===================== -->
<div class="supri-stats-grid" id="supriStats">
    <div class="supri-stat-card">
        <div class="supri-stat-icon" style="background:#8B5CF620;color:#8B5CF6"><i class="fas fa-boxes"></i></div>
        <div class="supri-stat-info"><span class="supri-stat-value" id="statTotal">--</span><span class="supri-stat-label">Total de Produtos</span></div>
    </div>
    <div class="supri-stat-card">
        <div class="supri-stat-icon" style="background:#EF444420;color:#EF4444"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="supri-stat-info"><span class="supri-stat-value" id="statBaixo">--</span><span class="supri-stat-label">Estoque Baixo</span></div>
    </div>
    <div class="supri-stat-card">
        <div class="supri-stat-icon" style="background:#DC262620;color:#DC2626"><i class="fas fa-times-circle"></i></div>
        <div class="supri-stat-info"><span class="supri-stat-value" id="statSemEstoque">--</span><span class="supri-stat-label">Sem Estoque</span></div>
    </div>
    <div class="supri-stat-card">
        <div class="supri-stat-icon" style="background:#10B98120;color:#10B981"><i class="fas fa-dollar-sign"></i></div>
        <div class="supri-stat-info"><span class="supri-stat-value" id="statValor">--</span><span class="supri-stat-label">Valor do Estoque</span></div>
    </div>
</div>

<!-- Filtros -->
<div class="card">
    <div class="supri-filters">
        <div class="supri-search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="buscaProduto" class="form-input" placeholder="Buscar por nome, código, marca..." oninput="filtrarProdutos()">
        </div>
        <select id="filtroCategoria" class="form-select" onchange="filtrarProdutos()">
            <option value="">Todas as categorias</option>
        </select>
        <select id="filtroEstoque" class="form-select" onchange="filtrarProdutos()">
            <option value="">Todo estoque</option>
            <option value="baixo">Estoque baixo</option>
            <option value="zerado">Sem estoque</option>
        </select>
    </div>

    <!-- Tabela de Produtos -->
    <div class="table-responsive">
        <table class="table" id="tabelaProdutos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Marca/Modelo</th>
                    <th>Local</th>
                    <th style="text-align:center">Estoque</th>
                    <th style="text-align:right">Preço Un.</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="listaProdutos">
                <tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($abaAtiva === 'movimentacoes'): ?>
<!-- ===================== ABA: MOVIMENTAÇÕES ===================== -->
<div class="card">
    <div class="supri-filters">
        <div class="supri-search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="buscaMov" class="form-input" placeholder="Buscar produto..." oninput="filtrarMovimentacoes()">
        </div>
        <select id="filtroTipoMov" class="form-select" onchange="filtrarMovimentacoes()">
            <option value="">Todos os tipos</option>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
            <option value="ajuste">Ajuste</option>
            <option value="devolucao">Devolução</option>
        </select>
        <input type="date" id="filtroDataInicio" class="form-input" onchange="filtrarMovimentacoes()">
        <input type="date" id="filtroDataFim" class="form-input" onchange="filtrarMovimentacoes()">
    </div>

    <div class="table-responsive">
        <table class="table" id="tabelaMovimentacoes">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th style="text-align:center">Qtd</th>
                    <th style="text-align:center">Estoque Ant.</th>
                    <th style="text-align:center">Estoque Post.</th>
                    <th>Motivo</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody id="listaMovimentacoes">
                <tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($abaAtiva === 'requisicoes'): ?>
<!-- ===================== ABA: REQUISIÇÕES ===================== -->
<div class="supri-stats-grid supri-stats-mini" id="reqStats"></div>

<div class="card">
    <div class="supri-filters">
        <div class="supri-search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="buscaReq" class="form-input" placeholder="Buscar requisição..." oninput="filtrarRequisicoes()">
        </div>
        <select id="filtroStatusReq" class="form-select" onchange="filtrarRequisicoes()">
            <option value="">Todos os status</option>
            <?php foreach ($statusReqLabels as $k => $v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="table-responsive">
        <table class="table" id="tabelaRequisicoes">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título</th>
                    <th>Solicitante</th>
                    <th>Prioridade</th>
                    <th style="text-align:right">Valor</th>
                    <th>Status</th>
                    <th>E-mail</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="listaRequisicoes">
                <tr><td colspan="9" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($abaAtiva === 'importar'): ?>
<!-- ===================== ABA: IMPORTAR ===================== -->
<div class="card">
    <div class="supri-import-area">
        <div class="supri-import-icon"><i class="fas fa-file-csv"></i></div>
        <h3>Importar Produtos via Planilha</h3>
        <p class="text-muted">Importe seu estoque a partir de um arquivo CSV. O sistema detectará automaticamente as colunas.</p>

        <div class="supri-import-upload">
            <form id="formImportar" enctype="multipart/form-data">
                <input type="hidden" name="action" value="importar">
                <div class="supri-import-dropzone" id="dropZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Arraste o arquivo aqui ou <strong>clique para selecionar</strong></p>
                    <span class="text-muted">Formatos aceitos: .csv, .txt (separado por vírgula ou ponto-e-vírgula)</span>
                    <input type="file" id="arquivoImport" name="arquivo" accept=".csv,.txt" style="display:none">
                </div>
                <div id="importFileInfo" style="display:none" class="supri-import-file-info">
                    <i class="fas fa-file-alt"></i>
                    <span id="importFileName"></span>
                    <button type="button" class="btn btn-sm btn-ghost" onclick="limparImport()"><i class="fas fa-times"></i></button>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" id="btnImportar" disabled>
                    <i class="fas fa-upload"></i> Importar Produtos
                </button>
            </form>
        </div>

        <div id="importResultado" style="display:none" class="supri-import-resultado"></div>

        <div class="supri-import-help">
            <h4><i class="fas fa-info-circle"></i> Colunas reconhecidas automaticamente</h4>
            <div class="supri-import-cols">
                <span class="supri-col-tag"><strong>nome</strong> (obrigatório)</span>
                <span class="supri-col-tag">codigo / sku</span>
                <span class="supri-col-tag">descricao</span>
                <span class="supri-col-tag">categoria</span>
                <span class="supri-col-tag">unidade</span>
                <span class="supri-col-tag">marca</span>
                <span class="supri-col-tag">modelo</span>
                <span class="supri-col-tag">localizacao</span>
                <span class="supri-col-tag">estoque / quantidade / qtd</span>
                <span class="supri-col-tag">estoque_minimo</span>
                <span class="supri-col-tag">preco / valor / custo</span>
                <span class="supri-col-tag">fornecedor</span>
                <span class="supri-col-tag">ncm</span>
            </div>
            <div style="margin-top:15px">
                <a href="#" onclick="baixarModeloCSV();return false" class="btn btn-outline btn-sm">
                    <i class="fas fa-download"></i> Baixar Modelo CSV
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===================== MODAIS ===================== -->

<!-- Modal: Produto -->
<div class="modal-overlay" id="modalProduto" style="display:none">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="modalProdutoTitulo"><i class="fas fa-box"></i> Novo Produto</h3>
            <button class="modal-close" onclick="fecharModal('modalProduto')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formProduto" class="form-grid">
                <input type="hidden" name="id" id="produtoId">
                <input type="hidden" name="action" id="produtoAction" value="criar_produto">

                <div class="form-group col-span-2">
                    <label class="form-label">Nome do Produto *</label>
                    <input type="text" name="nome" id="produtoNome" class="form-input" required placeholder="Ex: Toner HP 80A">
                </div>
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" id="produtoCodigo" class="form-input" placeholder="Auto">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" id="produtoCategoria" class="form-select">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" id="produtoMarca" class="form-input" placeholder="Ex: HP">
                </div>
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" id="produtoModelo" class="form-input" placeholder="Ex: CF280A">
                </div>
                <div class="form-group">
                    <label class="form-label">Unidade</label>
                    <select name="unidade" id="produtoUnidade" class="form-select">
                        <option value="un">Unidade (un)</option>
                        <option value="cx">Caixa (cx)</option>
                        <option value="pct">Pacote (pct)</option>
                        <option value="m">Metro (m)</option>
                        <option value="kg">Quilo (kg)</option>
                        <option value="rolo">Rolo</option>
                        <option value="par">Par</option>
                        <option value="kit">Kit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Localização</label>
                    <input type="text" name="localizacao" id="produtoLocal" class="form-input" placeholder="Ex: Almoxarifado A, Prateleira 3">
                </div>
                <div class="form-group">
                    <label class="form-label">Estoque Atual</label>
                    <input type="number" name="estoque_atual" id="produtoEstoque" class="form-input" min="0" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Estoque Mínimo</label>
                    <input type="number" name="estoque_minimo" id="produtoEstoqueMin" class="form-input" min="0" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Preço Unitário (R$)</label>
                    <input type="number" name="preco_unitario" id="produtoPreco" class="form-input" step="0.01" min="0" placeholder="0,00">
                </div>
                <div class="form-group">
                    <label class="form-label">Fornecedor Padrão</label>
                    <input type="text" name="fornecedor_padrao" id="produtoFornecedor" class="form-input" placeholder="Nome do fornecedor">
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" id="produtoObs" class="form-textarea" rows="2" placeholder="Notas adicionais..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="fecharModal('modalProduto')">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarProduto()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- Modal: Movimentação -->
<div class="modal-overlay" id="modalMovimentacao" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-exchange-alt"></i> Movimentação de Estoque</h3>
            <button class="modal-close" onclick="fecharModal('modalMovimentacao')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formMovimentacao" class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label">Produto *</label>
                    <select name="suprimento_id" id="movProduto" class="form-select" required>
                        <option value="">Selecione o produto...</option>
                    </select>
                    <small class="form-help" id="movEstoqueInfo"></small>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" id="movTipo" class="form-select" required>
                        <option value="entrada">📥 Entrada</option>
                        <option value="saida">📤 Saída</option>
                        <option value="ajuste">🔧 Ajuste (valor absoluto)</option>
                        <option value="devolucao">↩️ Devolução</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantidade *</label>
                    <input type="number" name="quantidade" id="movQuantidade" class="form-input" min="1" value="1" required>
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Motivo</label>
                    <input type="text" name="motivo" id="movMotivo" class="form-input" placeholder="Ex: Compra NF 1234, Retirada para setor X">
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Documento / NF</label>
                    <input type="text" name="documento" id="movDocumento" class="form-input" placeholder="Número da NF ou documento">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="fecharModal('modalMovimentacao')">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarMovimentacao()"><i class="fas fa-check"></i> Registrar</button>
        </div>
    </div>
</div>

<!-- Modal: Requisição de Compra -->
<div class="modal-overlay" id="modalRequisicao" style="display:none">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-shopping-cart"></i> Nova Requisição de Compra</h3>
            <button class="modal-close" onclick="fecharModal('modalRequisicao')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formRequisicao" class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label">Título da Requisição *</label>
                    <input type="text" name="titulo" id="reqTitulo" class="form-input" required placeholder="Ex: Reposição de toners Q1/2025">
                </div>
                <div class="form-group">
                    <label class="form-label">Prioridade</label>
                    <select name="prioridade" id="reqPrioridade" class="form-select">
                        <option value="baixa">🟢 Baixa</option>
                        <option value="media" selected>🟡 Média</option>
                        <option value="alta">🔴 Alta</option>
                        <option value="urgente">🚨 Urgente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail do Setor de Compras</label>
                    <input type="email" name="email_compras" id="reqEmailCompras" class="form-input" placeholder="compras@empresa.com">
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Justificativa</label>
                    <textarea name="justificativa" id="reqJustificativa" class="form-textarea" rows="2" placeholder="Justifique a necessidade da compra..."></textarea>
                </div>

                <!-- Itens da requisição -->
                <div class="form-group col-span-2">
                    <label class="form-label">Itens da Requisição</label>
                    <div class="supri-req-itens">
                        <div class="supri-req-itens-header">
                            <span>Produto</span>
                            <span>Qtd</span>
                            <span>Preço Est.</span>
                            <span>Subtotal</span>
                            <span></span>
                        </div>
                        <div id="reqItensLista"></div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="adicionarItemRequisicao()">
                            <i class="fas fa-plus"></i> Adicionar Item
                        </button>
                    </div>
                    <div class="supri-req-total">
                        Total Estimado: <strong id="reqTotalEstimado">R$ 0,00</strong>
                    </div>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" id="reqObs" class="form-textarea" rows="2"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="fecharModal('modalRequisicao')">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarRequisicao()"><i class="fas fa-paper-plane"></i> Criar Requisição</button>
        </div>
    </div>
</div>

<!-- Modal: Ver Produto -->
<div class="modal-overlay" id="modalVerProduto" style="display:none">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-box-open"></i> <span id="verProdutoTitulo">Detalhes do Produto</span></h3>
            <button class="modal-close" onclick="fecharModal('modalVerProduto')">&times;</button>
        </div>
        <div class="modal-body" id="verProdutoBody">
        </div>
    </div>
</div>

<!-- Modal: Ver Requisição -->
<div class="modal-overlay" id="modalVerRequisicao" style="display:none">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-file-invoice"></i> <span id="verReqTitulo">Detalhes da Requisição</span></h3>
            <button class="modal-close" onclick="fecharModal('modalVerRequisicao')">&times;</button>
        </div>
        <div class="modal-body" id="verReqBody">
        </div>
        <div class="modal-footer" id="verReqFooter">
        </div>
    </div>
</div>

<!-- Modal: Enviar E-mail -->
<div class="modal-overlay" id="modalEnviarEmail" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-envelope"></i> Enviar Requisição por E-mail</h3>
            <button class="modal-close" onclick="fecharModal('modalEnviarEmail')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEnviarEmail" class="form-grid">
                <input type="hidden" name="requisicao_id" id="emailReqId">
                <div class="form-group col-span-2">
                    <label class="form-label">E-mail do Setor de Compras *</label>
                    <input type="email" name="email_destino" id="emailDestino" class="form-input" required placeholder="compras@empresa.com">
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Enviar de (Conta de E-mail) *</label>
                    <select name="conta_email_id" id="emailContaId" class="form-select" required>
                        <option value="">Carregando contas...</option>
                    </select>
                </div>
                <div class="form-group col-span-2">
                    <div class="supri-email-preview">
                        <i class="fas fa-info-circle"></i>
                        A requisição será enviada como um e-mail formatado com todos os itens, valores e justificativa.
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="fecharModal('modalEnviarEmail')">Cancelar</button>
            <button class="btn btn-primary" onclick="enviarEmailRequisicao()"><i class="fas fa-paper-plane"></i> Enviar E-mail</button>
        </div>
    </div>
</div>

<!-- Modal: Alterar Status Requisição -->
<div class="modal-overlay" id="modalStatusReq" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-edit"></i> Alterar Status da Requisição</h3>
            <button class="modal-close" onclick="fecharModal('modalStatusReq')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formStatusReq" class="form-grid">
                <input type="hidden" name="id" id="statusReqId">
                <div class="form-group col-span-2">
                    <label class="form-label">Novo Status *</label>
                    <select name="status" id="statusReqStatus" class="form-select" required>
                        <?php foreach ($statusReqLabels as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-span-2">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" id="statusReqObs" class="form-textarea" rows="3" placeholder="Motivo da alteração..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="fecharModal('modalStatusReq')">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarStatusRequisicao()"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </div>
</div>

<script>
// ==========================================
//  CONSTANTES
// ==========================================
const API_URL = '<?= BASE_URL ?>/api/suprimentos.php';
const statusReqLabels = <?= json_encode($statusReqLabels) ?>;
const statusReqCores = <?= json_encode($statusReqCores) ?>;
const prioridadeLabels = <?= json_encode($prioridadeLabels) ?>;
const prioridadeCores = <?= json_encode($prioridadeCores) ?>;
const movTipoLabels = <?= json_encode($movTipoLabels) ?>;
const movTipoCores = <?= json_encode($movTipoCores) ?>;
const abaAtiva = '<?= $abaAtiva ?>';
const isAdmin = <?= in_array($user['tipo'], ['admin', 'gestor']) ? 'true' : 'false' ?>;

let categorias = [];
let produtosCache = [];

// ==========================================
//  INIT
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    carregarCategorias();
    if (abaAtiva === 'produtos') {
        carregarEstatisticas();
        carregarProdutos();
    } else if (abaAtiva === 'movimentacoes') {
        carregarMovimentacoes();
    } else if (abaAtiva === 'requisicoes') {
        carregarRequisicoes();
        carregarEstatisticasRequisicoes();
    }
});

// ==========================================
//  UTILITÁRIOS
// ==========================================
function fecharModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}
function abrirModal(id) {
    document.getElementById(id).style.display = 'flex';
    document.body.style.overflow = 'hidden';
    // Focus first input
    setTimeout(() => {
        const modal = document.getElementById(id);
        const input = modal.querySelector('input:not([type="hidden"]), select, textarea');
        if (input) input.focus();
    }, 100);
}
function formatarMoeda(val) {
    return 'R$ ' + parseFloat(val || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function formatarData(dt) {
    if (!dt) return '-';
    const d = new Date(dt);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
}

async function apiGet(action, params = {}) {
    const url = new URL(API_URL, window.location.origin);
    url.searchParams.set('action', action);
    for (const [k, v] of Object.entries(params)) {
        if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v);
    }
    const r = await fetch(url);
    return r.json();
}

async function apiPost(data) {
    const r = await fetch(API_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    return r.json();
}

// ==========================================
//  CATEGORIAS
// ==========================================
async function carregarCategorias() {
    const res = await apiGet('categorias');
    categorias = res.categorias || [];
    const selects = ['filtroCategoria', 'produtoCategoria'];
    selects.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const val = el.value;
        const opts = id === 'filtroCategoria' ? '<option value="">Todas as categorias</option>' : '<option value="">Selecione...</option>';
        el.innerHTML = opts + categorias.map(c =>
            `<option value="${c.id}" ${c.id == val ? 'selected' : ''}>${c.nome}</option>`
        ).join('');
    });
}

// ==========================================
//  ESTATÍSTICAS
// ==========================================
async function carregarEstatisticas() {
    const res = await apiGet('estatisticas');
    document.getElementById('statTotal').textContent = res.total_produtos ?? 0;
    document.getElementById('statBaixo').textContent = res.estoque_baixo ?? 0;
    document.getElementById('statSemEstoque').textContent = res.sem_estoque ?? 0;
    document.getElementById('statValor').textContent = formatarMoeda(res.valor_estoque ?? 0);
}

async function carregarEstatisticasRequisicoes() {
    const res = await apiGet('estatisticas');
    const stats = res.requisicoes_por_status || [];
    const container = document.getElementById('reqStats');
    if (!container) return;
    let html = '';
    const icons = {pendente:'fa-clock',em_analise:'fa-search',aprovada:'fa-check-circle',comprada:'fa-shopping-bag',entregue:'fa-truck'};
    stats.forEach(s => {
        const cor = statusReqCores[s.status] || '#6B7280';
        const label = statusReqLabels[s.status] || s.status;
        const icon = icons[s.status] || 'fa-circle';
        html += `<div class="supri-stat-card supri-stat-mini">
            <div class="supri-stat-icon" style="background:${cor}20;color:${cor}"><i class="fas ${icon}"></i></div>
            <div class="supri-stat-info"><span class="supri-stat-value">${s.total}</span><span class="supri-stat-label">${label}</span></div>
        </div>`;
    });
    container.innerHTML = html;
}

// ==========================================
//  PRODUTOS
// ==========================================
async function carregarProdutos() {
    const tbody = document.getElementById('listaProdutos');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

    const params = {
        busca: document.getElementById('buscaProduto')?.value || '',
        categoria_id: document.getElementById('filtroCategoria')?.value || ''
    };

    const filtroEstoque = document.getElementById('filtroEstoque')?.value;
    if (filtroEstoque === 'baixo') params.estoque_baixo = 1;

    const res = await apiGet('listar_produtos', params);
    let produtos = res.produtos || [];
    produtosCache = produtos;

    if (filtroEstoque === 'zerado') {
        produtos = produtos.filter(p => p.estoque_atual == 0);
    }

    if (!produtos.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="empty-state-sm"><i class="fas fa-box-open"></i><p>Nenhum produto encontrado</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = produtos.map(p => {
        const catCor = p.categoria_cor || '#6B7280';
        const estoqueBaixo = p.estoque_minimo > 0 && p.estoque_atual <= p.estoque_minimo;
        const semEstoque = p.estoque_atual == 0;
        let estoqueClass = 'supri-estoque-ok';
        if (semEstoque) estoqueClass = 'supri-estoque-zero';
        else if (estoqueBaixo) estoqueClass = 'supri-estoque-baixo';

        return `<tr>
            <td><span class="code-badge">${p.codigo || '-'}</span></td>
            <td><strong>${esc(p.nome)}</strong>${p.descricao ? '<br><small class="text-muted">' + esc(p.descricao).substring(0, 60) + '</small>' : ''}</td>
            <td>${p.categoria_nome ? `<span class="supri-cat-badge" style="background:${catCor}15;color:${catCor};border:1px solid ${catCor}30"><i class="fas ${p.categoria_icone || 'fa-box'}"></i> ${esc(p.categoria_nome)}</span>` : '-'}</td>
            <td>${p.marca || p.modelo ? esc((p.marca || '') + (p.modelo ? ' ' + p.modelo : '')) : '-'}</td>
            <td>${p.localizacao ? '<small>' + esc(p.localizacao) + '</small>' : '-'}</td>
            <td style="text-align:center"><span class="${estoqueClass}">${p.estoque_atual}${p.estoque_minimo > 0 ? ' <small>/ mín ' + p.estoque_minimo + '</small>' : ''}</span></td>
            <td style="text-align:right">${p.preco_unitario ? formatarMoeda(p.preco_unitario) : '-'}</td>
            <td>
                <div class="table-actions">
                    <button class="btn btn-sm btn-ghost" onclick="verProduto(${p.id})" title="Ver detalhes"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-ghost" onclick="editarProduto(${p.id})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-ghost" onclick="abrirMovRapida(${p.id})" title="Movimentar estoque"><i class="fas fa-exchange-alt"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

let debounceTimer;
function filtrarProdutos() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(carregarProdutos, 300);
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ==========================================
//  CRUD PRODUTO
// ==========================================
function abrirModalProduto(id = null) {
    document.getElementById('formProduto').reset();
    document.getElementById('produtoId').value = '';
    document.getElementById('produtoAction').value = 'criar_produto';
    document.getElementById('modalProdutoTitulo').innerHTML = '<i class="fas fa-box"></i> Novo Produto';
    abrirModal('modalProduto');
}

async function editarProduto(id) {
    const res = await apiGet('ver_produto', {id});
    if (res.error) return showToast(res.error, 'error');
    const p = res.produto;
    document.getElementById('produtoAction').value = 'editar_produto';
    document.getElementById('produtoId').value = p.id;
    document.getElementById('produtoNome').value = p.nome || '';
    document.getElementById('produtoCodigo').value = p.codigo || '';
    document.getElementById('produtoCategoria').value = p.categoria_id || '';
    document.getElementById('produtoMarca').value = p.marca || '';
    document.getElementById('produtoModelo').value = p.modelo || '';
    document.getElementById('produtoUnidade').value = p.unidade || 'un';
    document.getElementById('produtoLocal').value = p.localizacao || '';
    document.getElementById('produtoEstoque').value = p.estoque_atual || 0;
    document.getElementById('produtoEstoqueMin').value = p.estoque_minimo || 0;
    document.getElementById('produtoPreco').value = p.preco_unitario || '';
    document.getElementById('produtoFornecedor').value = p.fornecedor_padrao || '';
    document.getElementById('produtoObs').value = p.observacoes || '';
    document.getElementById('modalProdutoTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Produto';
    // Desabilitar estoque atual na edição (usar movimentação)
    document.getElementById('produtoEstoque').disabled = true;
    abrirModal('modalProduto');
}

async function salvarProduto() {
    const form = document.getElementById('formProduto');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = document.getElementById('produtoAction').value;
    if (document.getElementById('produtoId').value) {
        data.id = document.getElementById('produtoId').value;
    }

    const res = await apiPost(data);
    if (res.error) return showToast(res.error, 'error');
    showToast(res.message, 'success');
    fecharModal('modalProduto');
    document.getElementById('produtoEstoque').disabled = false;
    carregarProdutos();
    carregarEstatisticas();
}

async function verProduto(id) {
    const res = await apiGet('ver_produto', {id});
    if (res.error) return showToast(res.error, 'error');
    const p = res.produto;
    const movs = res.movimentacoes || [];
    const catCor = p.categoria_cor || '#6B7280';

    let html = `
    <div class="supri-detail-grid">
        <div class="supri-detail-main">
            <div class="supri-detail-header">
                <span class="code-badge">${p.codigo || '-'}</span>
                ${p.categoria_nome ? `<span class="supri-cat-badge" style="background:${catCor}15;color:${catCor};border:1px solid ${catCor}30"><i class="fas ${p.categoria_icone || 'fa-box'}"></i> ${esc(p.categoria_nome)}</span>` : ''}
            </div>
            <h2 style="margin:10px 0 5px">${esc(p.nome)}</h2>
            ${p.descricao ? '<p class="text-muted">' + esc(p.descricao) + '</p>' : ''}
            <div class="supri-detail-info">
                <div><strong>Marca:</strong> ${esc(p.marca) || '-'}</div>
                <div><strong>Modelo:</strong> ${esc(p.modelo) || '-'}</div>
                <div><strong>Unidade:</strong> ${p.unidade || '-'}</div>
                <div><strong>Localização:</strong> ${esc(p.localizacao) || '-'}</div>
                <div><strong>Fornecedor:</strong> ${esc(p.fornecedor_padrao) || '-'}</div>
                <div><strong>Preço Un.:</strong> ${p.preco_unitario ? formatarMoeda(p.preco_unitario) : '-'}</div>
            </div>
        </div>
        <div class="supri-detail-sidebar">
            <div class="supri-estoque-card ${p.estoque_atual == 0 ? 'zero' : (p.estoque_minimo > 0 && p.estoque_atual <= p.estoque_minimo ? 'baixo' : 'ok')}">
                <span class="supri-estoque-valor">${p.estoque_atual}</span>
                <span class="supri-estoque-label">Em estoque</span>
                ${p.estoque_minimo > 0 ? '<small>Mínimo: ' + p.estoque_minimo + '</small>' : ''}
            </div>
            <button class="btn btn-primary btn-block" onclick="fecharModal('modalVerProduto');abrirMovRapida(${p.id})">
                <i class="fas fa-exchange-alt"></i> Movimentar
            </button>
        </div>
    </div>`;

    if (movs.length) {
        html += `<h4 style="margin-top:20px;padding-top:15px;border-top:1px solid var(--border-color)"><i class="fas fa-history"></i> Últimas Movimentações</h4>
        <table class="table table-sm" style="margin-top:10px"><thead><tr><th>Data</th><th>Tipo</th><th>Qtd</th><th>Estoque</th><th>Motivo</th><th>Usuário</th></tr></thead><tbody>`;
        movs.forEach(m => {
            const cor = movTipoCores[m.tipo] || '#6B7280';
            html += `<tr>
                <td><small>${formatarData(m.criado_em)}</small></td>
                <td><span class="status-badge" style="background:${cor}20;color:${cor}">${movTipoLabels[m.tipo] || m.tipo}</span></td>
                <td style="text-align:center"><strong>${m.tipo === 'saida' ? '-' : (m.tipo === 'ajuste' ? '=' : '+')}${Math.abs(m.quantidade)}</strong></td>
                <td style="text-align:center">${m.estoque_anterior} → ${m.estoque_posterior}</td>
                <td><small>${esc(m.motivo) || '-'}</small></td>
                <td><small>${esc(m.usuario_nome) || '-'}</small></td>
            </tr>`;
        });
        html += '</tbody></table>';
    }

    document.getElementById('verProdutoTitulo').textContent = p.nome;
    document.getElementById('verProdutoBody').innerHTML = html;
    abrirModal('modalVerProduto');
}

// ==========================================
//  MOVIMENTAÇÕES
// ==========================================
async function carregarProdutosSelect() {
    const res = await apiGet('listar_produtos', {ativo: 1});
    const produtos = res.produtos || [];
    const sel = document.getElementById('movProduto');
    sel.innerHTML = '<option value="">Selecione o produto...</option>' +
        produtos.map(p => `<option value="${p.id}" data-estoque="${p.estoque_atual}">${p.codigo ? p.codigo + ' - ' : ''}${p.nome} (estoque: ${p.estoque_atual})</option>`).join('');
}

function abrirModalMovimentacao() {
    document.getElementById('formMovimentacao').reset();
    carregarProdutosSelect();
    abrirModal('modalMovimentacao');
}

function abrirMovRapida(produtoId) {
    document.getElementById('formMovimentacao').reset();
    carregarProdutosSelect().then(() => {
        document.getElementById('movProduto').value = produtoId;
    });
    abrirModal('modalMovimentacao');
}

document.getElementById('movProduto')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const est = opt?.dataset?.estoque;
    document.getElementById('movEstoqueInfo').textContent = est !== undefined ? `Estoque atual: ${est} unidades` : '';
});

async function salvarMovimentacao() {
    const form = document.getElementById('formMovimentacao');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = 'movimentar';
    data.quantidade = parseInt(data.quantidade);

    const res = await apiPost(data);
    if (res.error) return showToast(res.error, 'error');
    showToast(res.message + ` — Estoque atual: ${res.estoque_atual}`, 'success');
    fecharModal('modalMovimentacao');
    if (abaAtiva === 'produtos') { carregarProdutos(); carregarEstatisticas(); }
    if (abaAtiva === 'movimentacoes') carregarMovimentacoes();
}

async function carregarMovimentacoes() {
    const tbody = document.getElementById('listaMovimentacoes');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    const params = {
        tipo: document.getElementById('filtroTipoMov')?.value || '',
        data_inicio: document.getElementById('filtroDataInicio')?.value || '',
        data_fim: document.getElementById('filtroDataFim')?.value || ''
    };

    const res = await apiGet('listar_movimentacoes', params);
    const movs = res.movimentacoes || [];

    if (!movs.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="empty-state-sm"><i class="fas fa-exchange-alt"></i><p>Nenhuma movimentação</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = movs.map(m => {
        const cor = movTipoCores[m.tipo] || '#6B7280';
        const sinal = m.tipo === 'saida' ? '-' : (m.tipo === 'ajuste' ? '=' : '+');
        return `<tr>
            <td><small>${formatarData(m.criado_em)}</small></td>
            <td><span class="code-badge">${m.produto_codigo || '-'}</span> ${esc(m.produto_nome)}</td>
            <td><span class="status-badge" style="background:${cor}20;color:${cor}">${movTipoLabels[m.tipo] || m.tipo}</span></td>
            <td style="text-align:center;font-weight:600;color:${cor}">${sinal}${Math.abs(m.quantidade)}</td>
            <td style="text-align:center">${m.estoque_anterior}</td>
            <td style="text-align:center"><strong>${m.estoque_posterior}</strong></td>
            <td><small>${esc(m.motivo) || '-'}</small></td>
            <td><small>${esc(m.usuario_nome) || '-'}</small></td>
        </tr>`;
    }).join('');
}

function filtrarMovimentacoes() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(carregarMovimentacoes, 300);
}

// ==========================================
//  REQUISIÇÕES DE COMPRA
// ==========================================
let reqItensCount = 0;

function adicionarItemRequisicao(item = null) {
    reqItensCount++;
    const idx = reqItensCount;
    const div = document.createElement('div');
    div.className = 'supri-req-item-row';
    div.id = `reqItem${idx}`;
    div.innerHTML = `
        <div class="supri-req-item-produto">
            <input type="text" class="form-input form-input-sm" placeholder="Nome do item" id="reqItemNome${idx}" value="${item?.nome || ''}">
            <input type="hidden" id="reqItemProdId${idx}" value="${item?.suprimento_id || ''}">
        </div>
        <div><input type="number" class="form-input form-input-sm" min="1" value="${item?.quantidade || 1}" id="reqItemQtd${idx}" onchange="calcularTotalReq()"></div>
        <div><input type="number" class="form-input form-input-sm" step="0.01" min="0" value="${item?.preco || ''}" id="reqItemPreco${idx}" placeholder="0,00" onchange="calcularTotalReq()"></div>
        <div class="supri-req-item-subtotal" id="reqItemSub${idx}">R$ 0,00</div>
        <div><button type="button" class="btn btn-sm btn-ghost text-danger" onclick="removerItemReq(${idx})"><i class="fas fa-trash"></i></button></div>
    `;
    document.getElementById('reqItensLista').appendChild(div);
    calcularTotalReq();
}

function removerItemReq(idx) {
    document.getElementById(`reqItem${idx}`)?.remove();
    calcularTotalReq();
}

function calcularTotalReq() {
    let total = 0;
    document.querySelectorAll('[id^="reqItem"][id$="Qtd"]').forEach(el => {
        const match = el.id.match(/reqItemQtd(\d+)/);
        if (!match) return;
        const idx = match[1];
        const qtd = parseFloat(el.value) || 0;
        const preco = parseFloat(document.getElementById(`reqItemPreco${idx}`)?.value) || 0;
        const sub = qtd * preco;
        const subEl = document.getElementById(`reqItemSub${idx}`);
        if (subEl) subEl.textContent = formatarMoeda(sub);
        total += sub;
    });
    document.getElementById('reqTotalEstimado').textContent = formatarMoeda(total);
}

function abrirModalRequisicao() {
    document.getElementById('formRequisicao').reset();
    document.getElementById('reqItensLista').innerHTML = '';
    reqItensCount = 0;
    adicionarItemRequisicao();
    abrirModal('modalRequisicao');
}

async function salvarRequisicao() {
    const form = document.getElementById('formRequisicao');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar_requisicao';

    // Coletar itens
    const itens = [];
    document.querySelectorAll('[id^="reqItem"][id$="Qtd"]').forEach(el => {
        const match = el.id.match(/reqItemQtd(\d+)/);
        if (!match) return;
        const idx = match[1];
        const nome = document.getElementById(`reqItemNome${idx}`)?.value;
        if (!nome) return;
        itens.push({
            nome_item: nome,
            suprimento_id: document.getElementById(`reqItemProdId${idx}`)?.value || null,
            quantidade: parseInt(el.value) || 1,
            preco_estimado: parseFloat(document.getElementById(`reqItemPreco${idx}`)?.value) || 0,
            unidade: 'un'
        });
    });

    if (!itens.length) return showToast('Adicione pelo menos um item', 'error');
    data.itens = JSON.stringify(itens);

    const res = await apiPost(data);
    if (res.error) return showToast(res.error, 'error');
    showToast(res.message, 'success');
    fecharModal('modalRequisicao');
    carregarRequisicoes();
    carregarEstatisticasRequisicoes();
}

async function carregarRequisicoes() {
    const tbody = document.getElementById('listaRequisicoes');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    const params = {
        status: document.getElementById('filtroStatusReq')?.value || '',
        busca: document.getElementById('buscaReq')?.value || ''
    };

    const res = await apiGet('listar_requisicoes', params);
    const reqs = res.requisicoes || [];

    if (!reqs.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="empty-state-sm"><i class="fas fa-shopping-cart"></i><p>Nenhuma requisição</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = reqs.map(r => {
        const stCor = statusReqCores[r.status] || '#6B7280';
        const stLabel = statusReqLabels[r.status] || r.status;
        const priCor = prioridadeCores[r.prioridade] || '#6B7280';
        const priLabel = prioridadeLabels[r.prioridade] || r.prioridade;
        return `<tr>
            <td><span class="code-badge">${esc(r.codigo)}</span></td>
            <td><strong>${esc(r.titulo)}</strong></td>
            <td>${esc(r.solicitante_nome) || '-'}</td>
            <td><span class="priority-badge" style="background:${priCor}20;color:${priCor}">${priLabel}</span></td>
            <td style="text-align:right">${formatarMoeda(r.valor_total)}</td>
            <td><span class="status-badge" style="background:${stCor}20;color:${stCor}">${stLabel}</span></td>
            <td>${r.email_enviado == 1 ? '<i class="fas fa-check-circle text-success" title="E-mail enviado"></i>' : '<i class="fas fa-times-circle text-muted" title="Não enviado"></i>'}</td>
            <td><small>${formatarData(r.criado_em)}</small></td>
            <td>
                <div class="table-actions">
                    <button class="btn btn-sm btn-ghost" onclick="verRequisicao(${r.id})" title="Ver"><i class="fas fa-eye"></i></button>
                    ${isAdmin ? `<button class="btn btn-sm btn-ghost" onclick="abrirAlterarStatusReq(${r.id}, '${r.status}')" title="Status"><i class="fas fa-edit"></i></button>` : ''}
                    <button class="btn btn-sm btn-ghost" onclick="abrirEnviarEmail(${r.id}, '${esc(r.email_compras) || ''}')" title="Enviar e-mail"><i class="fas fa-envelope"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function filtrarRequisicoes() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(carregarRequisicoes, 300);
}

async function verRequisicao(id) {
    const res = await apiGet('ver_requisicao', {id});
    if (res.error) return showToast(res.error, 'error');
    const r = res.requisicao;
    const itens = res.itens || [];
    const hist = res.historico || [];

    const stCor = statusReqCores[r.status] || '#6B7280';
    const stLabel = statusReqLabels[r.status] || r.status;
    const priCor = prioridadeCores[r.prioridade] || '#6B7280';

    let html = `
    <div class="supri-req-detail">
        <div class="supri-req-detail-header">
            <div>
                <span class="code-badge">${esc(r.codigo)}</span>
                <span class="status-badge" style="background:${stCor}20;color:${stCor}">${stLabel}</span>
                <span class="priority-badge" style="background:${priCor}20;color:${priCor}">${prioridadeLabels[r.prioridade] || r.prioridade}</span>
            </div>
            <div><small class="text-muted">${formatarData(r.criado_em)}</small></div>
        </div>
        <h3 style="margin:15px 0 5px">${esc(r.titulo)}</h3>
        <p><strong>Solicitante:</strong> ${esc(r.solicitante_nome)}</p>
        ${r.justificativa ? '<p><strong>Justificativa:</strong> ' + esc(r.justificativa) + '</p>' : ''}
        ${r.email_enviado ? '<p class="text-success"><i class="fas fa-check-circle"></i> E-mail enviado para ' + esc(r.email_compras) + '</p>' : ''}

        <h4 style="margin-top:20px;border-top:1px solid var(--border-color);padding-top:15px">Itens</h4>
        <table class="table table-sm"><thead><tr><th>#</th><th>Item</th><th>Qtd</th><th style="text-align:right">Preço Est.</th><th style="text-align:right">Subtotal</th></tr></thead><tbody>`;

    itens.forEach((it, i) => {
        const sub = it.subtotal ?? (it.quantidade * (it.preco_estimado || 0));
        html += `<tr>
            <td>${i+1}</td>
            <td><strong>${esc(it.nome_item)}</strong>${it.descricao ? '<br><small class="text-muted">' + esc(it.descricao) + '</small>' : ''}</td>
            <td>${it.quantidade} ${it.unidade || ''}</td>
            <td style="text-align:right">${formatarMoeda(it.preco_estimado)}</td>
            <td style="text-align:right"><strong>${formatarMoeda(sub)}</strong></td>
        </tr>`;
    });

    html += `</tbody><tfoot><tr><td colspan="4" style="text-align:right"><strong>TOTAL:</strong></td>
        <td style="text-align:right;font-size:16px"><strong>${formatarMoeda(r.valor_total)}</strong></td></tr></tfoot></table>`;

    if (hist.length) {
        html += '<h4 style="margin-top:20px;border-top:1px solid var(--border-color);padding-top:15px"><i class="fas fa-history"></i> Histórico</h4>';
        html += '<div class="supri-timeline">';
        hist.forEach(h => {
            const hCor = statusReqCores[h.status] || '#6B7280';
            html += `<div class="supri-timeline-item">
                <div class="supri-timeline-dot" style="background:${hCor}"></div>
                <div class="supri-timeline-content">
                    <strong style="color:${hCor}">${statusReqLabels[h.status] || h.status}</strong>
                    <span class="text-muted"> — ${esc(h.usuario_nome) || 'Sistema'} em ${formatarData(h.criado_em)}</span>
                    ${h.observacao ? '<p style="margin:3px 0 0">' + esc(h.observacao) + '</p>' : ''}
                </div>
            </div>`;
        });
        html += '</div>';
    }
    html += '</div>';

    document.getElementById('verReqTitulo').textContent = r.codigo + ' — ' + r.titulo;
    document.getElementById('verReqBody').innerHTML = html;
    document.getElementById('verReqFooter').innerHTML = `
        <button class="btn btn-outline" onclick="fecharModal('modalVerRequisicao')">Fechar</button>
        <button class="btn btn-primary" onclick="fecharModal('modalVerRequisicao');abrirEnviarEmail(${r.id}, '${esc(r.email_compras) || ''}')"><i class="fas fa-envelope"></i> Enviar por E-mail</button>
    `;
    abrirModal('modalVerRequisicao');
}

function abrirAlterarStatusReq(id, statusAtual) {
    document.getElementById('statusReqId').value = id;
    document.getElementById('statusReqStatus').value = statusAtual;
    document.getElementById('statusReqObs').value = '';
    abrirModal('modalStatusReq');
}

async function salvarStatusRequisicao() {
    const data = Object.fromEntries(new FormData(document.getElementById('formStatusReq')));
    data.action = 'alterar_status_requisicao';

    const res = await apiPost(data);
    if (res.error) return showToast(res.error, 'error');
    showToast(res.message, 'success');
    fecharModal('modalStatusReq');
    carregarRequisicoes();
    carregarEstatisticasRequisicoes();
}

// ==========================================
//  E-MAIL
// ==========================================
async function abrirEnviarEmail(reqId, emailPadrao) {
    document.getElementById('emailReqId').value = reqId;
    document.getElementById('emailDestino').value = emailPadrao || '';

    // Carregar contas de email
    const res = await apiGet('contas_email');
    const contas = res.contas || [];
    const sel = document.getElementById('emailContaId');
    if (!contas.length) {
        sel.innerHTML = '<option value="">Nenhuma conta de e-mail configurada</option>';
    } else {
        sel.innerHTML = contas.map(c => `<option value="${c.id}">${c.nome_conta} (${c.email})</option>`).join('');
    }
    abrirModal('modalEnviarEmail');
}

async function enviarEmailRequisicao() {
    const form = document.getElementById('formEnviarEmail');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const data = Object.fromEntries(new FormData(form));
    data.action = 'enviar_email_requisicao';

    const btn = form.querySelector('.btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    const res = await apiPost(data);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar E-mail';

    if (res.error) return showToast(res.error, 'error');
    showToast(res.message, 'success');
    fecharModal('modalEnviarEmail');
    carregarRequisicoes();
}

// ==========================================
//  IMPORTAÇÃO
// ==========================================
const dropZone = document.getElementById('dropZone');
const arquivoInput = document.getElementById('arquivoImport');

if (dropZone) {
    dropZone.addEventListener('click', () => arquivoInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            arquivoInput.files = e.dataTransfer.files;
            mostrarArquivoSelecionado(e.dataTransfer.files[0]);
        }
    });
}

if (arquivoInput) {
    arquivoInput.addEventListener('change', function() {
        if (this.files.length) mostrarArquivoSelecionado(this.files[0]);
    });
}

function mostrarArquivoSelecionado(file) {
    document.getElementById('importFileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('importFileInfo').style.display = 'flex';
    document.getElementById('dropZone').style.display = 'none';
    document.getElementById('btnImportar').disabled = false;
}

function limparImport() {
    document.getElementById('arquivoImport').value = '';
    document.getElementById('importFileInfo').style.display = 'none';
    document.getElementById('dropZone').style.display = 'flex';
    document.getElementById('btnImportar').disabled = true;
    document.getElementById('importResultado').style.display = 'none';
}

document.getElementById('formImportar')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnImportar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';

    const formData = new FormData(this);
    formData.set('action', 'importar');

    try {
        const r = await fetch(API_URL, { method: 'POST', body: formData });
        const res = await r.json();

        const divRes = document.getElementById('importResultado');
        divRes.style.display = 'block';

        if (res.error) {
            divRes.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ${esc(res.error)}</div>`;
        } else {
            const resultado = res.resultado || {};
            let errHtml = '';
            if (resultado.erros?.length) {
                errHtml = '<div class="supri-import-erros"><strong>Detalhes:</strong><ul>' +
                    resultado.erros.map(e => `<li>${esc(e)}</li>`).join('') + '</ul></div>';
            }
            divRes.innerHTML = `<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <strong>${res.message}</strong>
                ${errHtml}
            </div>`;
        }
    } catch (err) {
        showToast('Erro na importação: ' + err.message, 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload"></i> Importar Produtos';
});

function baixarModeloCSV() {
    const csv = 'nome;codigo;categoria;marca;modelo;unidade;localizacao;estoque;estoque_minimo;preco;fornecedor\n' +
        'Toner HP 80A;SUP-00001;Toners e Cartuchos;HP;CF280A;un;Almoxarifado A;10;3;189.90;Kalunga\n' +
        'Cabo HDMI 2m;SUP-00002;Cabos e Conectores;Generic;;un;Prateleira 2;25;5;29.90;Amazon\n' +
        'Mouse USB;SUP-00003;Periféricos;Logitech;M170;un;TI Sala 1;15;5;49.90;Pichau';
    const blob = new Blob(['\uFEFF' + csv], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'modelo_suprimentos.csv'; a.click();
    URL.revokeObjectURL(url);
}

// Toast fallback
function showToast(msg, type = 'info') {
    if (typeof HelpDesk !== 'undefined' && HelpDesk.toast) {
        HelpDesk.toast(msg, type);
    } else {
        alert(msg);
    }
}
</script>
