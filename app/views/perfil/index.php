<?php
/**
 * View: Meu Perfil
 */
$user = currentUser();
if (!$perfilUsuario) {
    echo '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Usuário não encontrado</h3></div>';
    return;
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Meu Perfil</h1>
        <p class="page-subtitle">Gerencie suas informações pessoais</p>
    </div>
</div>

<div class="grid-2">
    <!-- Informações do Perfil -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user"></i> Informações Pessoais</h3>
        </div>
        <div class="card-body">
            <div style="text-align:center;margin-bottom:24px">
                <div class="user-avatar" style="width:80px;height:80px;font-size:28px;margin:0 auto 12px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff">
                    <?php if (!empty($perfilUsuario['avatar'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/avatars/<?= $perfilUsuario['avatar'] ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                    <?php else: ?>
                        <span><?= strtoupper(substr($perfilUsuario['nome'], 0, 2)) ?></span>
                    <?php endif; ?>
                </div>
                <h3 style="margin:0;font-size:1.2rem"><?= htmlspecialchars($perfilUsuario['nome']) ?></h3>
                <p style="color:#64748b;margin:4px 0;font-size:0.9rem"><?= ucfirst($perfilUsuario['tipo']) ?></p>
            </div>

            <form id="formPerfil" class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($perfilUsuario['nome']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($perfilUsuario['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-input" value="<?= htmlspecialchars($perfilUsuario['telefone'] ?? '') ?>" placeholder="5562999999999">
                </div>
                <div class="form-group col-span-2">
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alterar Senha -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock"></i> Alterar Senha</h3>
        </div>
        <div class="card-body">
            <form id="formSenha">
                <div class="form-group">
                    <label class="form-label">Senha Atual</label>
                    <input type="password" name="senha_atual" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" name="nova_senha" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fas fa-key"></i> Alterar Senha
                    </button>
                </div>
            </form>
        </div>

        <!-- Informações da Conta -->
        <div class="card-header" style="border-top:1px solid #e5e7eb">
            <h3 class="card-title"><i class="fas fa-info-circle"></i> Informações da Conta</h3>
        </div>
        <div class="card-body">
            <div class="detail-info">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-id-badge"></i> ID</span>
                    <span class="info-value">#<?= $perfilUsuario['id'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-shield-alt"></i> Tipo</span>
                    <span class="info-value"><?= ucfirst($perfilUsuario['tipo']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar-plus"></i> Cadastro</span>
                    <span class="info-value"><?= formatarData($perfilUsuario['criado_em'] ?? $perfilUsuario['created_at'] ?? '', 'd/m/Y') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-clock"></i> Último Login</span>
                    <span class="info-value"><?= formatarData($perfilUsuario['ultimo_login'] ?? '', 'd/m/Y H:i') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Atualizar perfil
document.getElementById('formPerfil').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    data.action = 'atualizar';
    data.id = <?= $perfilUsuario['id'] ?>;

    HelpDesk.api('POST', '/api/usuarios.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Perfil atualizado com sucesso!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao atualizar perfil', 'danger');
            }
        });
});

// Alterar senha
document.getElementById('formSenha').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));

    if (data.nova_senha !== data.confirmar_senha) {
        HelpDesk.toast('As senhas não coincidem!', 'danger');
        return;
    }

    if (data.nova_senha.length < 6) {
        HelpDesk.toast('A nova senha deve ter no mínimo 6 caracteres!', 'danger');
        return;
    }

    data.action = 'alterar_senha';
    data.id = <?= $perfilUsuario['id'] ?>;

    HelpDesk.api('POST', '/api/usuarios.php', data)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast('Senha alterada com sucesso!', 'success');
                this.reset();
            } else {
                HelpDesk.toast(resp.error || 'Erro ao alterar senha', 'danger');
            }
        });
});
</script>
