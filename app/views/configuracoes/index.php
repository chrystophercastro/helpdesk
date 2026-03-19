<?php
/**
 * View: Configurações do Sistema (Admin)
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Configurações</h1>
        <p class="page-subtitle">Configurações gerais do sistema</p>
    </div>
</div>

<div class="config-tabs">
    <button class="config-tab" data-tab="geral" onclick="showConfigTab('geral')"><i class="fas fa-cog"></i> Geral</button>
    <button class="config-tab" data-tab="email" onclick="showConfigTab('email')"><i class="fas fa-envelope"></i> Email</button>
    <button class="config-tab" data-tab="whatsapp" onclick="showConfigTab('whatsapp')"><i class="fab fa-whatsapp"></i> WhatsApp</button>
    <button class="config-tab" data-tab="sla" onclick="showConfigTab('sla')"><i class="fas fa-clock"></i> SLA</button>
    <button class="config-tab" data-tab="categorias" onclick="showConfigTab('categorias')"><i class="fas fa-tags"></i> Categorias</button>
</div>

<!-- Tab: Geral -->
<div id="tab-geral" class="config-panel">
    <div class="card">
        <div class="card-header"><h3>Configurações Gerais</h3></div>
        <div class="card-body">
            <form id="formConfigGeral" class="form-grid">
                <?php foreach ($configuracoes as $c): ?>
                <?php if (in_array($c['chave'], ['empresa_nome','empresa_email','empresa_telefone','empresa_logo','timezone','idioma'])): ?>
                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars($c['descricao'] ?? $c['chave']) ?></label>
                    <input type="text" name="config[<?= $c['chave'] ?>]" class="form-input" value="<?= htmlspecialchars($c['valor']) ?>">
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </form>
            <div style="margin-top:16px;text-align:right">
                <button class="btn btn-primary" onclick="salvarConfig('geral')"><i class="fas fa-save"></i> Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Email -->
<div id="tab-email" class="config-panel">
    <div class="card">
        <div class="card-header"><h3>Configurações de Email</h3></div>
        <div class="card-body">
            <form id="formConfigEmail" class="form-grid">
                <?php foreach ($configuracoes as $c): ?>
                <?php if (in_array($c['chave'], ['smtp_host','smtp_porta','smtp_usuario','smtp_senha','smtp_seguranca','email_remetente'])): ?>
                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars($c['descricao'] ?? $c['chave']) ?></label>
                    <input type="<?= $c['chave'] === 'smtp_senha' ? 'password' : 'text' ?>" name="config[<?= $c['chave'] ?>]" class="form-input" value="<?= htmlspecialchars($c['valor']) ?>">
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </form>
            <div style="margin-top:16px;text-align:right">
                <button class="btn btn-secondary" onclick="testarEmail()"><i class="fas fa-paper-plane"></i> Testar</button>
                <button class="btn btn-primary" onclick="salvarConfig('email')"><i class="fas fa-save"></i> Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Tab: WhatsApp -->
<div id="tab-whatsapp" class="config-panel">
    <div class="card">
        <div class="card-header"><h3>Integração WhatsApp (Evolution API)</h3></div>
        <div class="card-body">
            <form id="formConfigWhatsapp" class="form-grid">
                <?php foreach ($configuracoes as $c): ?>
                <?php if (in_array($c['chave'], ['evolution_api_url','evolution_api_key','evolution_instance','whatsapp_ativo'])): ?>
                <div class="form-group <?= $c['chave'] === 'evolution_api_url' ? 'col-span-2' : '' ?>">
                    <label class="form-label"><?= htmlspecialchars($c['descricao'] ?? $c['chave']) ?></label>
                    <?php if ($c['chave'] === 'whatsapp_ativo'): ?>
                    <select name="config[<?= $c['chave'] ?>]" class="form-select">
                        <option value="1" <?= $c['valor'] == '1' ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= $c['valor'] == '0' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                    <?php else: ?>
                    <input type="<?= $c['chave'] === 'evolution_api_key' ? 'password' : 'text' ?>" name="config[<?= $c['chave'] ?>]" class="form-input" value="<?= htmlspecialchars($c['valor']) ?>">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </form>
            <div style="margin-top:16px;text-align:right">
                <button class="btn btn-success" onclick="testarWhatsApp()"><i class="fab fa-whatsapp"></i> Testar Conexão</button>
                <button class="btn btn-primary" onclick="salvarConfig('whatsapp')"><i class="fas fa-save"></i> Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Tab: SLA -->
<div id="tab-sla" class="config-panel">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Regras de SLA por Categoria &amp; Prioridade</h3>
            <button class="btn btn-sm btn-primary" onclick="novoSLA()"><i class="fas fa-plus"></i> Nova Regra</button>
        </div>
        <div class="card-body">
            <p style="color:var(--gray-500);font-size:0.85rem;margin-bottom:16px">
                <i class="fas fa-info-circle"></i>
                Defina tempos de SLA específicos por <strong>categoria</strong> e <strong>prioridade</strong>.
                Regras sem categoria servem como padrão quando não houver regra específica.
            </p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Prioridade</th>
                        <th>Tempo Resposta</th>
                        <th>Tempo Resolução</th>
                        <th style="width:100px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $priCores = ['critica'=>'#EF4444','alta'=>'#F97316','media'=>'#F59E0B','baixa'=>'#10B981'];
                    $priLabels = ['critica'=>'Crítica','alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'];
                    foreach ($slaRegras ?? [] as $sla):
                    ?>
                    <tr>
                        <td>
                            <?php if (empty($sla['categoria_id'])): ?>
                            <span class="status-badge" style="background:#6366F120;color:#6366F1">
                                <i class="fas fa-globe"></i> Padrão (todas)
                            </span>
                            <?php else: ?>
                            <span class="status-badge" style="background:#3B82F620;color:#3B82F6">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($sla['categoria_nome'] ?? 'Categoria #'.$sla['categoria_id']) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge" style="background:<?= ($priCores[$sla['prioridade']] ?? '#6B7280') ?>20;color:<?= $priCores[$sla['prioridade']] ?? '#6B7280' ?>">
                                <i class="fas fa-flag"></i> <?= $priLabels[$sla['prioridade']] ?? ucfirst($sla['prioridade']) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:600">
                                <?php
                                $minResp = $sla['tempo_resposta'];
                                echo $minResp >= 60 ? floor($minResp/60) . 'h' . ($minResp%60 ? ' ' . ($minResp%60) . 'min' : '') : $minResp . ' min';
                                ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:600">
                                <?php
                                $minResol = $sla['tempo_resolucao'];
                                echo $minResol >= 60 ? floor($minResol/60) . 'h' . ($minResol%60 ? ' ' . ($minResol%60) . 'min' : '') : $minResol . ' min';
                                ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-ghost" onclick='editarSLA(<?= json_encode($sla) ?>)' title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-ghost" style="color:#EF4444" onclick="excluirSLA(<?= $sla['id'] ?>)" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($slaRegras)): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:32px">
                        <i class="fas fa-clock" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                        Nenhuma regra de SLA cadastrada
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Categorias -->
<div id="tab-categorias" class="config-panel">
    <div class="card">
        <div class="card-header">
            <h3>Categorias de Chamados</h3>
            <button class="btn btn-sm btn-primary" onclick="novaCategoria()"><i class="fas fa-plus"></i> Nova Categoria</button>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ícone</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias ?? [] as $cat): ?>
                    <tr>
                        <td><i class="<?= $cat['icone'] ?? 'fas fa-tag' ?>" style="color:#3B82F6;font-size:1.2rem"></i></td>
                        <td><strong><?= htmlspecialchars($cat['nome']) ?></strong></td>
                        <td class="text-muted"><?= htmlspecialchars($cat['tipo'] ?? '-') ?></td>
                        <td>
                            <?php if ($cat['ativo'] ?? 1): ?>
                            <span class="status-badge" style="background:#10B98120;color:#10B981">Ativa</span>
                            <?php else: ?>
                            <span class="status-badge" style="background:#EF444420;color:#EF4444">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-ghost"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Restaurar aba ativa pelo hash da URL ao carregar
(function() {
    const hash = location.hash.replace('#', '') || 'geral';
    showConfigTab(hash);
})();

function showConfigTab(tab) {
    document.querySelectorAll('.config-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.config-tab').forEach(t => t.classList.remove('active'));
    const panel = document.getElementById('tab-' + tab);
    const btn = document.querySelector('.config-tab[data-tab="' + tab + '"]');
    if (panel) panel.classList.add('active');
    if (btn) btn.classList.add('active');
    location.hash = tab;
}

function salvarConfig(secao) {
    const formId = 'formConfig' + secao.charAt(0).toUpperCase() + secao.slice(1);
    const form = document.getElementById(formId);
    if (!form) return;

    // Reconstruir objeto config aninhado a partir do FormData
    const formData = new FormData(form);
    const config = {};
    for (const [key, value] of formData.entries()) {
        const match = key.match(/^config\[(.+)\]$/);
        if (match) {
            config[match[1]] = value;
        }
    }

    if (Object.keys(config).length === 0) {
        HelpDesk.toast('Nenhuma configuração encontrada nesta aba', 'warning');
        return;
    }

    HelpDesk.api('POST', '/api/configuracoes.php', { action: 'salvar', config: config }).then(resp => {
        if (resp.success) HelpDesk.toast('Configurações salvas!', 'success');
        else HelpDesk.toast(resp.error || 'Erro ao salvar', 'danger');
    });
}

function testarWhatsApp() {
    // Primeiro salvar as configs atuais, depois testar
    const form = document.getElementById('formConfigWhatsapp');
    if (form) {
        const formData = new FormData(form);
        const config = {};
        for (const [key, value] of formData.entries()) {
            const match = key.match(/^config\[(.+)\]$/);
            if (match) config[match[1]] = value;
        }
        // Salvar primeiro
        HelpDesk.api('POST', '/api/configuracoes.php', { action: 'salvar', config: config }).then(() => {
            // Depois testar conexão
            HelpDesk.api('POST', '/api/configuracoes.php', { action: 'testar_whatsapp' }).then(resp => {
                if (resp.success) HelpDesk.toast('Conexão WhatsApp OK! ' + (resp.message || ''), 'success');
                else HelpDesk.toast('Falha na conexão: ' + (resp.error || ''), 'danger');
            });
        });
    }
}

function testarEmail() {
    salvarConfig('email');
    HelpDesk.toast('Configurações de email salvas. Teste de envio requer configuração SMTP válida.', 'info');
}

function novaCategoria() {
    HelpDesk.showModal('Nova Categoria', `
        <form id="formNovaCategoria">
            <div class="form-group">
                <label class="form-label">Nome *</label>
                <input type="text" name="nome" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <input type="text" name="descricao" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Ícone (Font Awesome)</label>
                <input type="text" name="icone" class="form-input" placeholder="Ex: desktop, network-wired, shield">
            </div>
        </form>
    `, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitNovaCategoria()"><i class="fas fa-save"></i> Criar</button>
    `);
}

function submitNovaCategoria() {
    const form = document.getElementById('formNovaCategoria');
    const data = Object.fromEntries(new FormData(form));
    data.action = 'criar_categoria';
    HelpDesk.api('POST', '/api/configuracoes.php', data).then(resp => {
        if (resp.success) { HelpDesk.toast('Categoria criada!', 'success'); HelpDesk.closeModal(); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}

function novoSLA() {
    const categoriasOptions = `<?php
        echo '<option value="">Padrão (todas as categorias)</option>';
        foreach ($categoriasAtivas ?? [] as $cat) {
            echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['nome']) . '</option>';
        }
    ?>`;
    HelpDesk.showModal('Nova Regra SLA', `
        <form id="formNovoSLA" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label">Categoria</label>
                <select name="categoria_id" class="form-select">${categoriasOptions}</select>
                <small style="color:var(--gray-400);font-size:0.78rem">Deixe como "Padrão" para aplicar a todas as categorias sem regra específica</small>
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Prioridade *</label>
                <select name="prioridade" class="form-select" required>
                    <option value="baixa">Baixa</option>
                    <option value="media">Média</option>
                    <option value="alta">Alta</option>
                    <option value="critica">Crítica</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tempo Resposta (minutos) *</label>
                <input type="number" name="tempo_resposta" class="form-input" required min="1" placeholder="Ex: 30">
            </div>
            <div class="form-group">
                <label class="form-label">Tempo Resolução (minutos) *</label>
                <input type="number" name="tempo_resolucao" class="form-input" required min="1" placeholder="Ex: 480">
            </div>
        </form>
    `, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitSLA('criar_sla')"><i class="fas fa-save"></i> Criar</button>
    `);
}

function editarSLA(sla) {
    const categoriasOptions = `<?php
        echo '<option value="">Padrão (todas as categorias)</option>';
        foreach ($categoriasAtivas ?? [] as $cat) {
            echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['nome']) . '</option>';
        }
    ?>`;
    HelpDesk.showModal('Editar Regra SLA', `
        <form id="formNovoSLA" class="form-grid">
            <input type="hidden" name="id" value="${sla.id}">
            <div class="form-group col-span-2">
                <label class="form-label">Categoria</label>
                <select name="categoria_id" class="form-select" id="slaEditCat">${categoriasOptions}</select>
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Prioridade *</label>
                <select name="prioridade" class="form-select" id="slaEditPri" required>
                    <option value="baixa">Baixa</option>
                    <option value="media">Média</option>
                    <option value="alta">Alta</option>
                    <option value="critica">Crítica</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tempo Resposta (minutos) *</label>
                <input type="number" name="tempo_resposta" class="form-input" required min="1" value="${sla.tempo_resposta}">
            </div>
            <div class="form-group">
                <label class="form-label">Tempo Resolução (minutos) *</label>
                <input type="number" name="tempo_resolucao" class="form-input" required min="1" value="${sla.tempo_resolucao}">
            </div>
        </form>
    `, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="submitSLA('atualizar_sla')"><i class="fas fa-save"></i> Salvar</button>
    `);
    // Set selected values after modal renders
    setTimeout(() => {
        document.getElementById('slaEditCat').value = sla.categoria_id || '';
        document.getElementById('slaEditPri').value = sla.prioridade;
    }, 100);
}

function submitSLA(action) {
    const form = document.getElementById('formNovoSLA');
    const data = Object.fromEntries(new FormData(form));
    data.action = action;
    HelpDesk.api('POST', '/api/configuracoes.php', data).then(resp => {
        if (resp.success) {
            HelpDesk.toast(action === 'criar_sla' ? 'Regra SLA criada!' : 'Regra SLA atualizada!', 'success');
            HelpDesk.closeModal();
            setTimeout(() => location.reload(), 500);
        } else {
            HelpDesk.toast(resp.error || 'Erro', 'danger');
        }
    });
}

function excluirSLA(id) {
    if (!confirm('Excluir esta regra de SLA?')) return;
    HelpDesk.api('POST', '/api/configuracoes.php', { action: 'excluir_sla', id: id }).then(resp => {
        if (resp.success) { HelpDesk.toast('Regra excluída!', 'success'); setTimeout(() => location.reload(), 500); }
        else HelpDesk.toast(resp.error || 'Erro', 'danger');
    });
}
</script>
