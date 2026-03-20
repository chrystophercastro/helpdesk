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
    <button class="config-tab" data-tab="portal" onclick="showConfigTab('portal')"><i class="fas fa-globe"></i> Portal</button>
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
                        <th>Departamento</th>
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
                        <td>
                            <?php if (!empty($cat['departamento_sigla'])): ?>
                            <span class="tag" style="background:<?= $cat['departamento_cor'] ?? '#6366F1' ?>15;color:<?= $cat['departamento_cor'] ?? '#6366F1' ?>;border:1px solid <?= $cat['departamento_cor'] ?? '#6366F1' ?>30;font-weight:600;font-size:11px">
                                <?= htmlspecialchars($cat['departamento_sigla']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
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

<!-- Tab: Portal -->
<div id="tab-portal" class="config-panel">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-globe"></i> Personalização do Portal Público</h3>
            <a href="<?= BASE_URL ?>/portal/" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-external-link-alt"></i> Ver Portal</a>
        </div>
        <div class="card-body">
            <form id="formConfigPortal">
                <!-- Textos -->
                <h4 style="margin-bottom:12px;color:var(--gray-700);font-size:0.9rem;"><i class="fas fa-font"></i> Textos & Identidade</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Título do Portal</label>
                        <input type="text" name="config[portal_titulo]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_titulo'] ?? '') ?>" placeholder="Deixe vazio para usar nome da empresa">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ícone (Font Awesome)</label>
                        <input type="text" name="config[portal_icone]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_icone'] ?? 'fas fa-headset') ?>" placeholder="fas fa-headset">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Subtítulo do Portal</label>
                        <input type="text" name="config[portal_subtitulo]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_subtitulo'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Título da Seção Mural</label>
                        <input type="text" name="config[portal_mural_titulo]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_mural_titulo'] ?? 'Mural da Empresa') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Título da Seção Consultar</label>
                        <input type="text" name="config[portal_consulta_titulo]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_consulta_titulo'] ?? 'Consultar Chamado') ?>">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Texto do Rodapé</label>
                        <input type="text" name="config[portal_footer_texto]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_footer_texto'] ?? '') ?>" placeholder="Texto adicional no rodapé (opcional)">
                    </div>
                </div>

                <hr style="margin:24px 0;border:none;border-top:1px solid var(--gray-200)">

                <!-- Cores -->
                <h4 style="margin-bottom:12px;color:var(--gray-700);font-size:0.9rem;"><i class="fas fa-palette"></i> Cores</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Cor Primária</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" id="portalCorPrimaria" value="<?= htmlspecialchars($portalConfigs['portal_cor_primaria'] ?? '#4F46E5') ?>" style="width:40px;height:36px;border:none;cursor:pointer;" onchange="document.querySelector('[name=\\'config[portal_cor_primaria]\\']').value=this.value">
                            <input type="text" name="config[portal_cor_primaria]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_cor_primaria'] ?? '#4F46E5') ?>" style="flex:1" onchange="document.getElementById('portalCorPrimaria').value=this.value">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cor Primária Escura</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" id="portalCorPrimariaDark" value="<?= htmlspecialchars($portalConfigs['portal_cor_primaria_dark'] ?? '#3730A3') ?>" style="width:40px;height:36px;border:none;cursor:pointer;" onchange="document.querySelector('[name=\\'config[portal_cor_primaria_dark]\\']').value=this.value">
                            <input type="text" name="config[portal_cor_primaria_dark]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_cor_primaria_dark'] ?? '#3730A3') ?>" style="flex:1" onchange="document.getElementById('portalCorPrimariaDark').value=this.value">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gradiente Header — Cor 1 (Início)</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" id="portalH1" value="<?= htmlspecialchars($portalConfigs['portal_cor_header_1'] ?? '#1E1B4B') ?>" style="width:40px;height:36px;border:none;cursor:pointer;" onchange="document.querySelector('[name=\\'config[portal_cor_header_1]\\']').value=this.value;previewGradient()">
                            <input type="text" name="config[portal_cor_header_1]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_cor_header_1'] ?? '#1E1B4B') ?>" style="flex:1" onchange="document.getElementById('portalH1').value=this.value;previewGradient()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gradiente Header — Cor 2 (Meio)</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" id="portalH2" value="<?= htmlspecialchars($portalConfigs['portal_cor_header_2'] ?? '#4338CA') ?>" style="width:40px;height:36px;border:none;cursor:pointer;" onchange="document.querySelector('[name=\\'config[portal_cor_header_2]\\']').value=this.value;previewGradient()">
                            <input type="text" name="config[portal_cor_header_2]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_cor_header_2'] ?? '#4338CA') ?>" style="flex:1" onchange="document.getElementById('portalH2').value=this.value;previewGradient()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gradiente Header — Cor 3 (Fim)</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="color" id="portalH3" value="<?= htmlspecialchars($portalConfigs['portal_cor_header_3'] ?? '#6366F1') ?>" style="width:40px;height:36px;border:none;cursor:pointer;" onchange="document.querySelector('[name=\\'config[portal_cor_header_3]\\']').value=this.value;previewGradient()">
                            <input type="text" name="config[portal_cor_header_3]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_cor_header_3'] ?? '#6366F1') ?>" style="flex:1" onchange="document.getElementById('portalH3').value=this.value;previewGradient()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Preview do Gradiente</label>
                        <div id="gradientPreview" style="height:48px;border-radius:10px;background:linear-gradient(135deg, <?= htmlspecialchars($portalConfigs['portal_cor_header_1'] ?? '#1E1B4B') ?> 0%, <?= htmlspecialchars($portalConfigs['portal_cor_header_2'] ?? '#4338CA') ?> 50%, <?= htmlspecialchars($portalConfigs['portal_cor_header_3'] ?? '#6366F1') ?> 100%)"></div>
                    </div>
                </div>

                <hr style="margin:24px 0;border:none;border-top:1px solid var(--gray-200)">

                <!-- Imagens -->
                <h4 style="margin-bottom:12px;color:var(--gray-700);font-size:0.9rem;"><i class="fas fa-image"></i> Imagens</h4>
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label">URL da Logo (deixe vazio para usar ícone)</label>
                        <input type="text" name="config[portal_logo]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_logo'] ?? '') ?>" placeholder="https://... ou /helpdesk/uploads/logo.png">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Upload de Logo</label>
                        <input type="file" id="portalLogoFile" class="form-input" accept="image/*" onchange="uploadPortalImage(this, 'config[portal_logo]')">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">URL do Banner do Mural (imagem decorativa)</label>
                        <input type="text" name="config[portal_banner_url]" class="form-input" value="<?= htmlspecialchars($portalConfigs['portal_banner_url'] ?? '') ?>" placeholder="https://... ou /helpdesk/uploads/banner.png">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Upload de Banner</label>
                        <input type="file" id="portalBannerFile" class="form-input" accept="image/*" onchange="uploadPortalImage(this, 'config[portal_banner_url]')">
                    </div>
                </div>

                <hr style="margin:24px 0;border:none;border-top:1px solid var(--gray-200)">

                <!-- Seções -->
                <h4 style="margin-bottom:12px;color:var(--gray-700);font-size:0.9rem;"><i class="fas fa-eye"></i> Visibilidade de Seções</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Exibir Departamentos</label>
                        <select name="config[portal_mostrar_depts]" class="form-select">
                            <option value="1" <?= ($portalConfigs['portal_mostrar_depts'] ?? '1') == '1' ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= ($portalConfigs['portal_mostrar_depts'] ?? '1') == '0' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exibir Mural</label>
                        <select name="config[portal_mostrar_mural]" class="form-select">
                            <option value="1" <?= ($portalConfigs['portal_mostrar_mural'] ?? '1') == '1' ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= ($portalConfigs['portal_mostrar_mural'] ?? '1') == '0' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exibir Consultar Chamado</label>
                        <select name="config[portal_mostrar_consulta]" class="form-select">
                            <option value="1" <?= ($portalConfigs['portal_mostrar_consulta'] ?? '1') == '1' ? 'selected' : '' ?>>Sim</option>
                            <option value="0" <?= ($portalConfigs['portal_mostrar_consulta'] ?? '1') == '0' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qtd. de Posts no Mural</label>
                        <input type="number" name="config[portal_posts_quantidade]" class="form-input" min="1" max="50" value="<?= htmlspecialchars($portalConfigs['portal_posts_quantidade'] ?? '5') ?>">
                    </div>
                </div>

                <hr style="margin:24px 0;border:none;border-top:1px solid var(--gray-200)">

                <!-- CSS Personalizado -->
                <h4 style="margin-bottom:12px;color:var(--gray-700);font-size:0.9rem;"><i class="fas fa-code"></i> CSS Personalizado</h4>
                <div class="form-group">
                    <label class="form-label">CSS customizado (avançado)</label>
                    <textarea name="config[portal_css_custom]" class="form-input" rows="5" style="font-family:'SF Mono','Consolas',monospace;font-size:12px;min-height:100px;resize:vertical;" placeholder=".portal-header { /* suas regras */ }"><?= htmlspecialchars($portalConfigs['portal_css_custom'] ?? '') ?></textarea>
                    <small style="color:var(--gray-400);font-size:0.78rem">CSS injetado diretamente na página do portal. Use com cuidado.</small>
                </div>
            </form>
            <div style="margin-top:16px;text-align:right">
                <button class="btn btn-primary" onclick="salvarConfig('portal')"><i class="fas fa-save"></i> Salvar Configurações do Portal</button>
            </div>
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
                <label class="form-label">Departamento</label>
                <select name="departamento_id" class="form-select">
                    <option value="">— Nenhum (Global) —</option>
                    <?php
                    $db2 = Database::getInstance();
                    $deptos = $db2->fetchAll("SELECT id, nome, sigla FROM departamentos WHERE ativo = 1 ORDER BY ordem, nome");
                    foreach ($deptos as $dep):
                    ?>
                    <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['sigla'] . ' - ' . $dep['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
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

/* === Portal functions === */
function previewGradient() {
    const c1 = document.getElementById('portalH1')?.value || '#1E1B4B';
    const c2 = document.getElementById('portalH2')?.value || '#4338CA';
    const c3 = document.getElementById('portalH3')?.value || '#6366F1';
    const el = document.getElementById('gradientPreview');
    if (el) el.style.background = `linear-gradient(135deg, ${c1} 0%, ${c2} 50%, ${c3} 100%)`;
}

function uploadPortalImage(input, targetName) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        HelpDesk.toast('Imagem muito grande (max 5MB)', 'warning');
        return;
    }
    const fd = new FormData();
    fd.append('action', 'upload_portal_image');
    fd.append('imagem', file);

    fetch('<?= BASE_URL ?>/api/configuracoes.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.success && resp.url) {
                const targetInput = document.querySelector('[name="' + targetName + '"]');
                if (targetInput) targetInput.value = resp.url;
                HelpDesk.toast('Imagem enviada!', 'success');
            } else {
                HelpDesk.toast(resp.error || 'Erro no upload', 'danger');
            }
        })
        .catch(() => HelpDesk.toast('Erro de conexão', 'danger'));
}
</script>
