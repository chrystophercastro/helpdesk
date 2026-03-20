<?php
/**
 * View: Gestão de Usuários (Admin/Gestor)
 */
$tiposUsuario = ['admin'=>'Administrador','gestor'=>'Gestor','tecnico'=>'Técnico'];
$deptMap = [];
foreach ($departamentosLista ?? [] as $dep) {
    $deptMap[$dep['id']] = $dep;
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Gestão de Usuários</h1>
        <p class="page-subtitle">
            <?php if (!isAdmin() && getDeptFilter()): ?>
                Usuários do seu departamento
            <?php else: ?>
                Administrar usuários do sistema
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoUsuario')">
            <i class="fas fa-user-plus"></i> Novo Usuário
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1rem; padding: 1rem;">
    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <div class="form-group" style="margin:0; min-width: 200px;">
            <?php if (!isAdmin() && getDeptFilter()): ?>
            <select id="filtroDepartamento" class="form-select" disabled style="padding: 0.5rem 0.75rem; opacity:0.7;">
                <?php foreach ($departamentosLista ?? [] as $dep): ?>
                <option value="<?= $dep['id'] ?>" selected><?= htmlspecialchars($dep['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <select id="filtroDepartamento" class="form-select" onchange="filtrarUsuarios()" style="padding: 0.5rem 0.75rem;">
                <option value="">Todos os Departamentos</option>
                <?php foreach ($departamentosLista ?? [] as $dep): ?>
                <option value="<?= $dep['id'] ?>" style="color:<?= $dep['cor'] ?>"><?= htmlspecialchars($dep['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
        <div class="form-group" style="margin:0; min-width: 160px;">
            <select id="filtroTipo" class="form-select" onchange="filtrarUsuarios()" style="padding: 0.5rem 0.75rem;">
                <option value="">Todos os Tipos</option>
                <option value="admin">Administrador</option>
                <option value="gestor">Gestor</option>
                <option value="tecnico">Técnico</option>
            </select>
        </div>
        <div class="form-group" style="margin:0; min-width: 160px;">
            <select id="filtroStatus" class="form-select" onchange="filtrarUsuarios()" style="padding: 0.5rem 0.75rem;">
                <option value="">Todos os Status</option>
                <option value="1">Ativos</option>
                <option value="0">Inativos</option>
            </select>
        </div>
        <span id="filtroContador" style="color:#6B7280; font-size:0.85rem; margin-left:auto;"></span>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Departamento</th>
                    <th>Status</th>
                    <th>Último Acesso</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                <tr><td colspan="7" class="text-center py-4"><div class="empty-state-sm"><i class="fas fa-users"></i><p>Nenhum usuário encontrado</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                <tr data-dept="<?= $u['departamento_id'] ?? '' ?>" data-tipo="<?= $u['tipo'] ?>" data-ativo="<?= $u['ativo'] ?>">
                    <td>
                        <div class="user-cell">
                            <div class="avatar avatar-sm"><?= strtoupper(mb_substr($u['nome'], 0, 2)) ?></div>
                            <strong><?= htmlspecialchars($u['nome']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php
                        $tipoCores = ['admin'=>'#EF4444','gestor'=>'#8B5CF6','tecnico'=>'#3B82F6'];
                        $corT = $tipoCores[$u['tipo']] ?? '#6B7280';
                        ?>
                        <span class="status-badge" style="background:<?= $corT ?>20;color:<?= $corT ?>"><?= $tiposUsuario[$u['tipo']] ?? $u['tipo'] ?></span>
                    </td>
                    <td>
                        <?php if (!empty($u['departamento_sigla'])): 
                            $corD = $u['departamento_cor'] ?? '#6B7280';
                        ?>
                        <span class="status-badge" style="background:<?= $corD ?>20;color:<?= $corD ?>">
                            <i class="fas fa-<?= htmlspecialchars($u['departamento_icone'] ?? 'building') ?>" style="margin-right:4px"></i>
                            <?= htmlspecialchars($u['departamento_nome']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#9CA3AF">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['ativo']): ?>
                        <span class="status-badge" style="background:#10B98120;color:#10B981">Ativo</span>
                        <?php else: ?>
                        <span class="status-badge" style="background:#EF444420;color:#EF4444">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['ultimo_login'] ? tempoRelativo($u['ultimo_login']) : 'Nunca' ?></td>
                    <td>
                        <button class="btn btn-sm btn-ghost" onclick="editarUsuario(<?= $u['id'] ?>)" title="Editar"><i class="fas fa-edit"></i></button>
                        <?php if ($u['id'] !== currentUser()['id']): ?>
                        <button class="btn btn-sm btn-ghost text-danger" onclick="toggleUsuario(<?= $u['id'] ?>, <?= $u['ativo'] ?>)" title="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>">
                            <i class="fas fa-<?= $u['ativo'] ? 'ban' : 'check' ?>"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Departments list for JS modals
const departamentosLista = <?= json_encode(array_map(function($d) {
    return ['id' => $d['id'], 'nome' => $d['nome'], 'sigla' => $d['sigla'], 'cor' => $d['cor']];
}, $departamentosLista ?? [])) ?>;

const isUserAdmin = <?= isAdmin() ? 'true' : 'false' ?>;
const userDeptId = <?= getDeptFilter() ? (int)getDeptFilter() : 'null' ?>;

function buildDeptOptions(selectedId) {
    let opts = '<option value="">Selecione o departamento</option>';
    departamentosLista.forEach(d => {
        opts += `<option value="${d.id}" ${d.id == selectedId ? 'selected' : ''}>${d.nome} (${d.sigla})</option>`;
    });
    return opts;
}

HelpDesk.modals = HelpDesk.modals || {};
HelpDesk.modals.novoUsuario = function() {
    const html = `
    <form id="formNovoUsuario" class="form-grid">
        <div class="form-group">
            <label class="form-label">Nome Completo *</label>
            <input type="text" name="nome" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Senha *</label>
            <input type="password" name="senha" class="form-input" required minlength="6">
        </div>
        <div class="form-group">
            <label class="form-label">Tipo *</label>
            <select name="tipo" class="form-select" required>
                <option value="tecnico">Técnico</option>
                <option value="gestor">Gestor</option>
                <?php if (isAdmin()): ?>
                <option value="admin">Administrador</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Departamento *</label>
            <?php if (!isAdmin() && getDeptFilter()): ?>
            <select name="departamento_id" class="form-select" required disabled style="opacity:0.7">
                <?php foreach ($departamentosLista ?? [] as $dep): ?>
                <option value="<?= $dep['id'] ?>" selected><?= htmlspecialchars($dep['nome']) ?> (<?= htmlspecialchars($dep['sigla']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="departamento_id" value="<?= getDeptFilter() ?>">
            <?php else: ?>
            <select name="departamento_id" class="form-select" required>
                <option value="">Selecione o departamento</option>
                <?php foreach ($departamentosLista ?? [] as $dep): ?>
                <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nome']) ?> (<?= htmlspecialchars($dep['sigla']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label class="form-label">Telefone</label>
            <input type="text" name="telefone" class="form-input" placeholder="(00) 00000-0000">
        </div>
    </form>`;

    HelpDesk.showModal('Novo Usuário', html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovoUsuario()"><i class="fas fa-save"></i> Cadastrar</button>
    `);
};

function submitNovoUsuario() {
    const form = document.getElementById('formNovoUsuario');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar';
    HelpDesk.api('POST', '/api/usuarios.php', data).then(resp => {
        if (resp.success) { HelpDesk.toast('Usuário criado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro ao criar usuário', 'danger');
    });
}

function editarUsuario(id) {
    HelpDesk.api('GET', '/api/usuarios.php?action=ver&id=' + id).then(u => {
        if (!u) return;
        const html = `
        <form id="formEditUsuario" class="form-grid">
            <input type="hidden" name="id" value="${u.id}">
            <div class="form-group">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" class="form-input" value="${u.nome}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="${u.email}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nova Senha</label>
                <input type="password" name="senha" class="form-input" placeholder="Deixe vazio para manter">
            </div>
            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="tecnico" ${u.tipo==='tecnico'?'selected':''}>Técnico</option>
                    <option value="gestor" ${u.tipo==='gestor'?'selected':''}>Gestor</option>
                    ${isUserAdmin ? `<option value="admin" ${u.tipo==='admin'?'selected':''}>Administrador</option>` : ''}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Departamento *</label>
                <select name="departamento_id" class="form-select" required ${!isUserAdmin && userDeptId ? 'disabled' : ''}>
                    ${buildDeptOptions(userDeptId && !isUserAdmin ? userDeptId : u.departamento_id)}
                </select>
                ${!isUserAdmin && userDeptId ? `<input type="hidden" name="departamento_id" value="${userDeptId}">` : ''}
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" class="form-input" value="${u.telefone||''}">
            </div>
        </form>`;
        HelpDesk.showModal('Editar: ' + u.nome, html, `
            <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="submitEditUsuario()"><i class="fas fa-save"></i> Salvar</button>
        `);
    });
}

function submitEditUsuario() {
    const form = document.getElementById('formEditUsuario');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'atualizar';
    HelpDesk.api('POST', '/api/usuarios.php', data).then(resp => {
        if (resp.success) { HelpDesk.toast('Usuário atualizado!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}

function toggleUsuario(id, ativo) {
    if (!confirm(ativo ? 'Desativar este usuário?' : 'Ativar este usuário?')) return;
    HelpDesk.api('POST', '/api/usuarios.php', { action: 'toggle', id: id }).then(resp => {
        if (resp.success) { HelpDesk.toast('Status alterado!', 'success'); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}

function filtrarUsuarios() {
    const dept = document.getElementById('filtroDepartamento').value;
    const tipo = document.getElementById('filtroTipo').value;
    const status = document.getElementById('filtroStatus').value;
    const rows = document.querySelectorAll('table tbody tr[data-dept]');
    let visivel = 0;
    rows.forEach(row => {
        let show = true;
        if (dept && row.dataset.dept !== dept) show = false;
        if (tipo && row.dataset.tipo !== tipo) show = false;
        if (status !== '' && row.dataset.ativo !== status) show = false;
        row.style.display = show ? '' : 'none';
        if (show) visivel++;
    });
    document.getElementById('filtroContador').textContent = visivel + ' de ' + rows.length + ' usuários';
}

// Show counter on load
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('table tbody tr[data-dept]');
    if (rows.length > 0) {
        document.getElementById('filtroContador').textContent = rows.length + ' usuários';
    }
});
</script>
