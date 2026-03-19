<?php
/**
 * View: Gestão de Usuários (Admin/Gestor)
 */
$tiposUsuario = ['admin'=>'Administrador','gestor'=>'Gestor','tecnico'=>'Técnico'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Gestão de Usuários</h1>
        <p class="page-subtitle">Administrar usuários do sistema</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="HelpDesk.openModal('novoUsuario')">
            <i class="fas fa-user-plus"></i> Novo Usuário
        </button>
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
                <tr>
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
                    <td><?= htmlspecialchars($u['departamento'] ?? '-') ?></td>
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
                <option value="admin">Administrador</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Departamento</label>
            <input type="text" name="departamento" class="form-input">
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
                    <option value="admin" ${u.tipo==='admin'?'selected':''}>Administrador</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Departamento</label>
                <input type="text" name="departamento" class="form-input" value="${u.departamento||''}">
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
</script>
