<?php
/**
 * View: Departamentos — Gestão Multi-Departamentos
 */
$user = currentUser();
require_once __DIR__ . '/../../models/Departamento.php';
require_once __DIR__ . '/../../models/Usuario.php';

$deptModel = new Departamento();
$departamentos = $deptModel->listar();

$usuarioModel = new Usuario();
$tecnicos = $usuarioModel->listarTecnicos();

$db = Database::getInstance();
$todosUsuarios = $db->fetchAll("SELECT id, nome, tipo, departamento_id FROM usuarios WHERE ativo = 1 ORDER BY nome");
?>

<style>
.dept-page { padding: 0; }
.dept-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.dept-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
}
.dept-header h1 i { color: var(--primary); margin-right: 8px; }
.dept-header p { color: var(--gray-500); font-size: 14px; margin-top: 4px; }

.dept-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.dept-stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    text-align: center;
}
.dept-stat-card .stat-number {
    font-size: 28px;
    font-weight: 800;
    color: var(--primary);
}
.dept-stat-card .stat-label {
    font-size: 12px;
    color: var(--gray-500);
    margin-top: 4px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dept-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}
.dept-admin-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.2s;
    border: 2px solid transparent;
}
.dept-admin-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.dept-admin-card .card-top {
    height: 6px;
}
.dept-admin-card .card-body {
    padding: 20px;
}
.dept-admin-card .card-icon-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.dept-admin-card .dept-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}
.dept-admin-card .dept-sigla {
    font-size: 14px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    background: var(--gray-100);
    color: var(--gray-600);
}
.dept-admin-card h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 4px;
}
.dept-admin-card .desc {
    font-size: 13px;
    color: var(--gray-500);
    line-height: 1.4;
    margin-bottom: 16px;
    min-height: 36px;
}
.dept-admin-card .metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 16px;
}
.dept-admin-card .metric {
    text-align: center;
    padding: 8px 4px;
    background: var(--gray-50);
    border-radius: 8px;
}
.dept-admin-card .metric .num {
    font-size: 18px;
    font-weight: 700;
    display: block;
}
.dept-admin-card .metric .lbl {
    font-size: 10px;
    color: var(--gray-500);
    text-transform: uppercase;
    font-weight: 600;
}
.dept-admin-card .card-actions {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
}
.dept-admin-card .card-actions .btn {
    flex: 1;
    padding: 8px 12px;
    font-size: 12px;
    border-radius: 8px;
}
.dept-admin-card.inativo {
    opacity: 0.6;
}
.dept-admin-card.inativo .card-top {
    background: var(--gray-300) !important;
}

/* Modal */
.dept-modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: flex-start;
    padding-top: 60px;
}
.dept-modal-overlay.active { display: flex; }
.dept-modal {
    background: white;
    border-radius: 16px;
    width: 600px;
    max-width: 95vw;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.dept-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.dept-modal-header h3 { font-size: 18px; font-weight: 700; }
.dept-modal-body { padding: 24px; }
.dept-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--gray-100);
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.form-row-3 {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 12px;
}

.color-preview {
    width: 36px; height: 36px;
    border-radius: 8px;
    border: 2px solid var(--gray-200);
    cursor: pointer;
    display: inline-block;
    vertical-align: middle;
}

/* Icon Picker */
.icon-picker-label { display: flex; align-items: center; gap: 8px; }
.icon-picker-preview {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: var(--gray-600);
    border: 2px solid var(--gray-200);
    transition: all 0.2s;
    cursor: pointer;
}
.icon-picker-preview:hover { border-color: var(--primary); color: var(--primary); }
.icon-picker-preview.active { border-color: var(--primary); background: var(--primary); color: white; }
.icon-picker-container {
    margin-top: 8px;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    background: white;
    overflow: hidden;
}
.icon-picker-search {
    padding: 10px 12px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    gap: 8px;
}
.icon-picker-search i { color: var(--gray-400); font-size: 13px; }
.icon-picker-search input {
    border: none;
    outline: none;
    font-size: 13px;
    width: 100%;
    background: transparent;
}
.icon-picker-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
    padding: 10px;
    max-height: 200px;
    overflow-y: auto;
}
.icon-picker-item {
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    color: var(--gray-600);
    transition: all 0.15s;
    border: 2px solid transparent;
    background: var(--gray-50);
}
.icon-picker-item:hover { background: var(--primary); color: white; transform: scale(1.1); }
.icon-picker-item.selected { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 2px 8px rgba(99,102,241,0.3); }
.icon-picker-item[title]:hover::after { content: attr(title); }
</style>

<div class="dept-page">
    <div class="dept-header">
        <div>
            <h1><i class="fas fa-building"></i> Departamentos</h1>
            <p>Gerencie os departamentos da empresa para o portal multi-departamentos</p>
        </div>
        <button class="btn btn-primary" onclick="abrirModalDept()">
            <i class="fas fa-plus"></i> Novo Departamento
        </button>
    </div>

    <!-- Stats resumo -->
    <div class="dept-stats">
        <div class="dept-stat-card">
            <div class="stat-number"><?= count($departamentos) ?></div>
            <div class="stat-label">Departamentos</div>
        </div>
        <div class="dept-stat-card">
            <div class="stat-number"><?= array_sum(array_column($departamentos, 'total_categorias')) ?></div>
            <div class="stat-label">Categorias</div>
        </div>
        <div class="dept-stat-card">
            <div class="stat-number"><?= array_sum(array_column($departamentos, 'chamados_abertos')) ?></div>
            <div class="stat-label">Chamados Abertos</div>
        </div>
        <div class="dept-stat-card">
            <div class="stat-number"><?= array_sum(array_column($departamentos, 'total_usuarios')) ?></div>
            <div class="stat-label">Usuários</div>
        </div>
    </div>

    <!-- Grid de departamentos -->
    <div class="dept-grid" id="deptGrid">
        <?php foreach ($departamentos as $dept): ?>
        <div class="dept-admin-card <?= $dept['ativo'] ? '' : 'inativo' ?>" id="deptCard<?= $dept['id'] ?>">
            <div class="card-top" style="background:<?= htmlspecialchars($dept['cor']) ?>"></div>
            <div class="card-body">
                <div class="card-icon-row">
                    <div class="dept-icon" style="background:<?= htmlspecialchars($dept['cor']) ?>">
                        <i class="<?= htmlspecialchars($dept['icone']) ?>"></i>
                    </div>
                    <div>
                        <span class="dept-sigla"><?= htmlspecialchars($dept['sigla']) ?></span>
                        <?php if (!$dept['ativo']): ?>
                        <span style="font-size:11px;color:var(--danger);font-weight:600;margin-left:4px;">INATIVO</span>
                        <?php endif; ?>
                    </div>
                </div>
                <h3><?= htmlspecialchars($dept['nome']) ?></h3>
                <div class="desc"><?= htmlspecialchars($dept['descricao'] ?: '—') ?></div>
                <div class="metrics">
                    <div class="metric">
                        <span class="num" style="color:<?= htmlspecialchars($dept['cor']) ?>"><?= (int)$dept['total_categorias'] ?></span>
                        <span class="lbl">Categorias</span>
                    </div>
                    <div class="metric">
                        <span class="num" style="color:#F59E0B"><?= (int)$dept['chamados_abertos'] ?></span>
                        <span class="lbl">Abertos</span>
                    </div>
                    <div class="metric">
                        <span class="num" style="color:#10B981"><?= (int)$dept['total_chamados'] ?></span>
                        <span class="lbl">Total</span>
                    </div>
                </div>
                <?php if ($dept['responsavel_nome']): ?>
                <div style="font-size:12px;color:var(--gray-500);margin-bottom:12px;">
                    <i class="fas fa-user"></i> Resp: <strong><?= htmlspecialchars($dept['responsavel_nome']) ?></strong>
                </div>
                <?php endif; ?>
                <div class="card-actions">
                    <button class="btn btn-outline btn-sm" onclick="editarDept(<?= $dept['id'] ?>)" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-sm <?= $dept['ativo'] ? 'btn-outline' : 'btn-success' ?>"
                            onclick="toggleDept(<?= $dept['id'] ?>)"
                            title="<?= $dept['ativo'] ? 'Desativar' : 'Ativar' ?>">
                        <i class="fas fa-<?= $dept['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
                        <?= $dept['ativo'] ? 'Desativar' : 'Ativar' ?>
                    </button>
                    <?php if ($dept['total_chamados'] == 0): ?>
                    <button class="btn btn-outline btn-sm" onclick="excluirDept(<?= $dept['id'] ?>, '<?= htmlspecialchars($dept['nome']) ?>')" style="color:var(--danger);" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($departamentos)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
            <i class="fas fa-building" style="font-size:48px;color:var(--gray-300);margin-bottom:16px;display:block;"></i>
            <h3 style="color:var(--gray-500);">Nenhum departamento cadastrado</h3>
            <p style="color:var(--gray-400);">Clique em "Novo Departamento" para criar o primeiro.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Criar/Editar -->
<div class="dept-modal-overlay" id="deptModal">
    <div class="dept-modal">
        <div class="dept-modal-header">
            <h3 id="deptModalTitle">Novo Departamento</h3>
            <button class="btn btn-outline btn-sm" onclick="fecharModalDept()" style="border:none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="dept-modal-body">
            <form id="formDept">
                <input type="hidden" name="id" id="deptId">

                <div class="form-row-3" style="margin-bottom:16px;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="deptNome" class="form-control" required placeholder="Ex: Tecnologia da Informação">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Sigla *</label>
                        <input type="text" name="sigla" id="deptSigla" class="form-control" required placeholder="TI" maxlength="10" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Cor</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" name="cor" id="deptCor" value="#6366F1" style="width:42px;height:38px;border:none;cursor:pointer;border-radius:8px;">
                            <span id="deptCorHex" style="font-size:12px;color:var(--gray-500);">#6366F1</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" id="deptDescricao" class="form-control" rows="2" placeholder="Breve descrição do departamento"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Ícone</label>
                    <input type="hidden" name="icone" id="deptIcone" value="fas fa-building">
                    <div class="icon-picker-container">
                        <div class="icon-picker-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="iconSearch" placeholder="Buscar ícone..." oninput="filtrarIcones(this.value)">
                        </div>
                        <div class="icon-picker-grid" id="iconGrid"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" id="deptEmail" class="form-control" placeholder="depto@empresa.com">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Responsável</label>
                        <select name="responsavel_id" id="deptResponsavel" class="form-control">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($todosUsuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?> (<?= $u['tipo'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Ordem</label>
                        <input type="number" name="ordem" id="deptOrdem" class="form-control" value="0" min="0">
                    </div>
                </div>
            </form>
        </div>
        <div class="dept-modal-footer">
            <button class="btn btn-outline" onclick="fecharModalDept()">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarDept()" id="btnSalvarDept">
                <i class="fas fa-save"></i> Salvar
            </button>
        </div>
    </div>
</div>

<script>
const DEPT_API = '<?= BASE_URL ?>/api/departamentos.php';
let deptEditando = null;

// ==========================================
// Icon Picker — Ícones visuais categorizados
// ==========================================
const DEPT_ICONS = [
    // Departamentos / Prédio
    { icon: 'fas fa-building', label: 'Prédio' },
    { icon: 'fas fa-city', label: 'Cidade' },
    { icon: 'fas fa-landmark', label: 'Governo' },
    { icon: 'fas fa-store', label: 'Loja' },
    { icon: 'fas fa-warehouse', label: 'Depósito' },
    { icon: 'fas fa-home', label: 'Casa' },
    { icon: 'fas fa-hotel', label: 'Hotel' },
    { icon: 'fas fa-industry', label: 'Indústria' },
    // TI / Tecnologia
    { icon: 'fas fa-laptop-code', label: 'Laptop Code' },
    { icon: 'fas fa-desktop', label: 'Desktop' },
    { icon: 'fas fa-server', label: 'Servidor' },
    { icon: 'fas fa-network-wired', label: 'Rede' },
    { icon: 'fas fa-wifi', label: 'WiFi' },
    { icon: 'fas fa-database', label: 'Banco de Dados' },
    { icon: 'fas fa-code', label: 'Código' },
    { icon: 'fas fa-microchip', label: 'Chip' },
    { icon: 'fas fa-hdd', label: 'HD' },
    { icon: 'fas fa-shield-alt', label: 'Segurança' },
    { icon: 'fas fa-bug', label: 'Bug' },
    { icon: 'fas fa-robot', label: 'Robô' },
    { icon: 'fas fa-terminal', label: 'Terminal' },
    { icon: 'fas fa-cloud', label: 'Nuvem' },
    { icon: 'fas fa-satellite-dish', label: 'Satélite' },
    // Pessoas / RH
    { icon: 'fas fa-users', label: 'Pessoas' },
    { icon: 'fas fa-user-tie', label: 'Executivo' },
    { icon: 'fas fa-user-graduate', label: 'Graduação' },
    { icon: 'fas fa-user-shield', label: 'Segurança' },
    { icon: 'fas fa-people-carry', label: 'Equipe' },
    { icon: 'fas fa-user-cog', label: 'Config Usuário' },
    { icon: 'fas fa-id-card', label: 'Crachá' },
    { icon: 'fas fa-hands-helping', label: 'Ajuda' },
    { icon: 'fas fa-handshake', label: 'Acordo' },
    { icon: 'fas fa-chalkboard-teacher', label: 'Professor' },
    // Financeiro
    { icon: 'fas fa-dollar-sign', label: 'Dólar' },
    { icon: 'fas fa-coins', label: 'Moedas' },
    { icon: 'fas fa-money-bill-wave', label: 'Dinheiro' },
    { icon: 'fas fa-chart-line', label: 'Gráfico Linha' },
    { icon: 'fas fa-chart-bar', label: 'Gráfico Barra' },
    { icon: 'fas fa-chart-pie', label: 'Gráfico Pizza' },
    { icon: 'fas fa-calculator', label: 'Calculadora' },
    { icon: 'fas fa-receipt', label: 'Recibo' },
    { icon: 'fas fa-file-invoice-dollar', label: 'Nota Fiscal' },
    { icon: 'fas fa-piggy-bank', label: 'Cofre' },
    { icon: 'fas fa-wallet', label: 'Carteira' },
    { icon: 'fas fa-credit-card', label: 'Cartão' },
    // Operações / Manutenção
    { icon: 'fas fa-wrench', label: 'Chave' },
    { icon: 'fas fa-tools', label: 'Ferramentas' },
    { icon: 'fas fa-cog', label: 'Engrenagem' },
    { icon: 'fas fa-cogs', label: 'Engrenagens' },
    { icon: 'fas fa-hard-hat', label: 'Capacete' },
    { icon: 'fas fa-hammer', label: 'Martelo' },
    { icon: 'fas fa-screwdriver', label: 'Chave de Fenda' },
    { icon: 'fas fa-toolbox', label: 'Caixa Ferramentas' },
    { icon: 'fas fa-plug', label: 'Tomada' },
    { icon: 'fas fa-bolt', label: 'Raio' },
    { icon: 'fas fa-fire-extinguisher', label: 'Extintor' },
    // Marketing / Comunicação
    { icon: 'fas fa-bullhorn', label: 'Megafone' },
    { icon: 'fas fa-ad', label: 'Anúncio' },
    { icon: 'fas fa-palette', label: 'Paleta' },
    { icon: 'fas fa-paint-brush', label: 'Pincel' },
    { icon: 'fas fa-pen-fancy', label: 'Caneta' },
    { icon: 'fas fa-comments', label: 'Chat' },
    { icon: 'fas fa-envelope', label: 'E-mail' },
    { icon: 'fas fa-share-alt', label: 'Compartilhar' },
    { icon: 'fas fa-globe', label: 'Globo' },
    { icon: 'fas fa-camera', label: 'Câmera' },
    { icon: 'fas fa-video', label: 'Vídeo' },
    { icon: 'fas fa-image', label: 'Imagem' },
    // Jurídico
    { icon: 'fas fa-gavel', label: 'Martelo Juiz' },
    { icon: 'fas fa-balance-scale', label: 'Balança' },
    { icon: 'fas fa-file-contract', label: 'Contrato' },
    { icon: 'fas fa-file-signature', label: 'Assinatura' },
    // Logística / Transporte
    { icon: 'fas fa-truck', label: 'Caminhão' },
    { icon: 'fas fa-shipping-fast', label: 'Entrega' },
    { icon: 'fas fa-box', label: 'Caixa' },
    { icon: 'fas fa-boxes', label: 'Caixas' },
    { icon: 'fas fa-dolly', label: 'Carrinho' },
    { icon: 'fas fa-pallet', label: 'Palete' },
    { icon: 'fas fa-map-marked-alt', label: 'Mapa' },
    { icon: 'fas fa-route', label: 'Rota' },
    // Saúde / Segurança
    { icon: 'fas fa-heartbeat', label: 'Batimento' },
    { icon: 'fas fa-medkit', label: 'Kit Médico' },
    { icon: 'fas fa-hospital', label: 'Hospital' },
    { icon: 'fas fa-stethoscope', label: 'Estetoscópio' },
    { icon: 'fas fa-first-aid', label: 'Primeiro Socorro' },
    { icon: 'fas fa-shield-virus', label: 'Proteção' },
    // Educação / Treinamento
    { icon: 'fas fa-graduation-cap', label: 'Formatura' },
    { icon: 'fas fa-book', label: 'Livro' },
    { icon: 'fas fa-book-reader', label: 'Leitor' },
    { icon: 'fas fa-school', label: 'Escola' },
    { icon: 'fas fa-award', label: 'Prêmio' },
    { icon: 'fas fa-certificate', label: 'Certificado' },
    // Vendas / Compras
    { icon: 'fas fa-shopping-cart', label: 'Carrinho' },
    { icon: 'fas fa-cash-register', label: 'Caixa' },
    { icon: 'fas fa-shopping-bag', label: 'Sacola' },
    { icon: 'fas fa-tags', label: 'Tags' },
    { icon: 'fas fa-percent', label: 'Porcentagem' },
    // Outros
    { icon: 'fas fa-headset', label: 'Headset' },
    { icon: 'fas fa-phone', label: 'Telefone' },
    { icon: 'fas fa-print', label: 'Impressora' },
    { icon: 'fas fa-clipboard-list', label: 'Checklist' },
    { icon: 'fas fa-tasks', label: 'Tarefas' },
    { icon: 'fas fa-project-diagram', label: 'Projeto' },
    { icon: 'fas fa-flag', label: 'Bandeira' },
    { icon: 'fas fa-star', label: 'Estrela' },
    { icon: 'fas fa-rocket', label: 'Foguete' },
    { icon: 'fas fa-lightbulb', label: 'Lâmpada' },
    { icon: 'fas fa-key', label: 'Chave' },
    { icon: 'fas fa-lock', label: 'Cadeado' },
    { icon: 'fas fa-clock', label: 'Relógio' },
    { icon: 'fas fa-calendar-alt', label: 'Calendário' },
    { icon: 'fas fa-bell', label: 'Sino' },
    { icon: 'fas fa-leaf', label: 'Folha' },
    { icon: 'fas fa-recycle', label: 'Reciclar' },
    { icon: 'fas fa-seedling', label: 'Planta' },
    { icon: 'fas fa-solar-panel', label: 'Solar' },
    { icon: 'fas fa-car', label: 'Carro' },
    { icon: 'fas fa-bus', label: 'Ônibus' },
    { icon: 'fas fa-parking', label: 'Estacionamento' },
    { icon: 'fas fa-utensils', label: 'Restaurante' },
    { icon: 'fas fa-coffee', label: 'Café' },
    { icon: 'fas fa-broom', label: 'Vassoura' },
    { icon: 'fas fa-tshirt', label: 'Roupa' },
];

let selectedIcon = 'fas fa-building';

function renderIconGrid(filter = '') {
    const grid = document.getElementById('iconGrid');
    const f = filter.toLowerCase();
    const filtered = f ? DEPT_ICONS.filter(i => i.label.toLowerCase().includes(f) || i.icon.includes(f)) : DEPT_ICONS;

    grid.innerHTML = filtered.map(i =>
        `<div class="icon-picker-item ${i.icon === selectedIcon ? 'selected' : ''}" 
              title="${i.label}" 
              onclick="selecionarIcone('${i.icon}', this)">
            <i class="${i.icon}"></i>
        </div>`
    ).join('');

    if (filtered.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--gray-400);font-size:13px;">Nenhum ícone encontrado</div>';
    }
}

function selecionarIcone(icon, el) {
    selectedIcon = icon;
    document.getElementById('deptIcone').value = icon;
    document.querySelectorAll('.icon-picker-item').forEach(e => e.classList.remove('selected'));
    if (el) el.classList.add('selected');
}

function filtrarIcones(val) {
    renderIconGrid(val);
}

// Init icon grid on load
document.addEventListener('DOMContentLoaded', () => renderIconGrid());

// Color picker sync
document.getElementById('deptCor').addEventListener('input', function() {
    document.getElementById('deptCorHex').textContent = this.value;
});

function abrirModalDept() {
    deptEditando = null;
    document.getElementById('deptModalTitle').textContent = 'Novo Departamento';
    document.getElementById('formDept').reset();
    document.getElementById('deptId').value = '';
    document.getElementById('deptCor').value = '#6366F1';
    document.getElementById('deptCorHex').textContent = '#6366F1';
    document.getElementById('deptIcone').value = 'fas fa-building';
    selectedIcon = 'fas fa-building';
    document.getElementById('iconSearch').value = '';
    renderIconGrid();
    document.getElementById('deptModal').classList.add('active');
}

function fecharModalDept() {
    document.getElementById('deptModal').classList.remove('active');
}

async function editarDept(id) {
    try {
        const r = await fetch(`${DEPT_API}?action=detalhe&id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await r.json();
        if (data.departamento) {
            const d = data.departamento;
            deptEditando = id;
            document.getElementById('deptModalTitle').textContent = 'Editar: ' + d.nome;
            document.getElementById('deptId').value = d.id;
            document.getElementById('deptNome').value = d.nome;
            document.getElementById('deptSigla').value = d.sigla;
            document.getElementById('deptCor').value = d.cor;
            document.getElementById('deptCorHex').textContent = d.cor;
            document.getElementById('deptDescricao').value = d.descricao || '';
            document.getElementById('deptIcone').value = d.icone || 'fas fa-building';
            selectedIcon = d.icone || 'fas fa-building';
            document.getElementById('iconSearch').value = '';
            renderIconGrid();
            document.getElementById('deptEmail').value = d.email || '';
            document.getElementById('deptResponsavel').value = d.responsavel_id || '';
            document.getElementById('deptOrdem').value = d.ordem || 0;
            document.getElementById('deptModal').classList.add('active');
        }
    } catch(e) {
        HelpDesk.toast('Erro ao carregar departamento', 'danger');
    }
}

async function salvarDept() {
    const nome = document.getElementById('deptNome').value.trim();
    const sigla = document.getElementById('deptSigla').value.trim().toUpperCase();

    if (!nome || !sigla) {
        HelpDesk.toast('Nome e sigla são obrigatórios', 'danger');
        return;
    }

    const btn = document.getElementById('btnSalvarDept');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    const payload = {
        action: deptEditando ? 'atualizar' : 'criar',
        id: deptEditando,
        nome,
        sigla,
        cor: document.getElementById('deptCor').value,
        descricao: document.getElementById('deptDescricao').value.trim(),
        icone: document.getElementById('deptIcone').value || 'fas fa-building',
        email: document.getElementById('deptEmail').value.trim(),
        responsavel_id: document.getElementById('deptResponsavel').value || null,
        ordem: parseInt(document.getElementById('deptOrdem').value) || 0,
    };

    try {
        const r = await fetch(DEPT_API, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        const data = await r.json();
        if (data.success) {
            HelpDesk.toast(deptEditando ? 'Departamento atualizado!' : 'Departamento criado!', 'success');
            fecharModalDept();
            setTimeout(() => location.reload(), 500);
        } else {
            HelpDesk.toast(data.error || 'Erro ao salvar', 'danger');
        }
    } catch(e) {
        HelpDesk.toast('Erro de conexão: ' + e.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
    }
}

async function toggleDept(id) {
    if (!confirm('Deseja alterar o status deste departamento?')) return;
    try {
        const r = await fetch(DEPT_API, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'toggle', id })
        });
        const data = await r.json();
        if (data.success) {
            HelpDesk.toast(data.ativo ? 'Departamento ativado' : 'Departamento desativado', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            HelpDesk.toast(data.error || 'Erro', 'danger');
        }
    } catch(e) {
        HelpDesk.toast('Erro de conexão', 'danger');
    }
}

async function excluirDept(id, nome) {
    if (!confirm(`Excluir o departamento "${nome}"? Esta ação não pode ser desfeita.`)) return;
    try {
        const r = await fetch(DEPT_API, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'excluir', id })
        });
        const data = await r.json();
        if (data.success) {
            HelpDesk.toast('Departamento excluído', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            HelpDesk.toast(data.error || 'Erro ao excluir', 'danger');
        }
    } catch(e) {
        HelpDesk.toast('Erro de conexão', 'danger');
    }
}

// Close modal on overlay click
document.getElementById('deptModal').addEventListener('click', function(e) {
    if (e.target === this) fecharModalDept();
});
</script>
