<?php
/**
 * View: Detalhe do Chamado — Layout Profissional
 */
if (!$chamado) {
    echo '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Chamado não encontrado</h3></div>';
    return;
}
$statusList = CHAMADO_STATUS;
$prioridadeList = PRIORIDADES;
$st = $statusList[$chamado['status']];
$pri = $prioridadeList[$chamado['prioridade']];
$user = currentUser();

// Calcular SLA progress
$slaPercent = null;
$slaClass = '';
if (!empty($chamado['tempo_resolucao']) && empty($chamado['data_resolucao'])) {
    $abertura = strtotime($chamado['data_abertura']);
    $agora = time();
    $decorrido = ($agora - $abertura) / 60;
    $slaPercent = min(100, round(($decorrido / $chamado['tempo_resolucao']) * 100));
    $slaClass = $slaPercent >= 90 ? 'sla-critical' : ($slaPercent >= 70 ? 'sla-warning' : 'sla-ok');
}
?>

<!-- Header do Chamado -->
<div class="chamado-view-header">
    <div class="chamado-view-header-top">
        <a href="<?= BASE_URL ?>/index.php?page=chamados" class="btn-back">
            <i class="fas fa-arrow-left"></i> Voltar aos chamados
        </a>
        <div class="chamado-view-header-actions">
            <span id="btnRemoteContainer"></span>
            <?php if (!in_array($chamado['status'], ['resolvido', 'fechado'])): ?>
            <button class="btn btn-sm btn-success" onclick="atualizarChamado(<?= $chamado['id'] ?>, 'status', 'resolvido')">
                <i class="fas fa-check-circle"></i> Resolver
            </button>
            <?php endif; ?>
            <?php if ($user['tipo'] === 'admin'): ?>
            <button class="btn btn-sm btn-danger" onclick="excluirChamado(<?= $chamado['id'] ?>, '<?= addslashes($chamado['codigo']) ?>')">
                <i class="fas fa-trash"></i> Excluir
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="chamado-view-title-block">
        <div class="chamado-view-badges">
            <span class="cv-badge-code"><?= $chamado['codigo'] ?></span>
            <span class="cv-badge-status" style="background:<?= $st['cor'] ?>12;color:<?= $st['cor'] ?>;border:1px solid <?= $st['cor'] ?>35">
                <i class="<?= $st['icone'] ?>"></i> <?= $st['label'] ?>
            </span>
            <span class="cv-badge-priority cv-pri-<?= $chamado['prioridade'] ?>">
                <i class="fas fa-flag"></i> <?= $pri['label'] ?>
            </span>
        </div>
        <h1 class="chamado-view-title"><?= htmlspecialchars($chamado['titulo']) ?></h1>
        <div class="chamado-view-meta">
            <span><i class="far fa-calendar-alt"></i> <?= formatarData($chamado['data_abertura']) ?></span>
            <span><i class="far fa-user"></i> <?= htmlspecialchars($chamado['solicitante_nome'] ?? 'N/A') ?></span>
            <?php if (!empty($chamado['categoria_nome'])): ?>
            <span><i class="far fa-folder"></i> <?= htmlspecialchars($chamado['categoria_nome']) ?></span>
            <?php endif; ?>
            <span><i class="fas fa-<?= ($chamado['canal'] ?? 'interno') === 'portal' ? 'globe' : 'desktop' ?>"></i> <?= ucfirst($chamado['canal'] ?? 'Interno') ?></span>
        </div>
    </div>

    <?php if ($slaPercent !== null): ?>
    <div class="cv-sla-bar <?= $slaClass ?>">
        <div class="cv-sla-info">
            <span><i class="fas fa-stopwatch"></i> SLA <?= $chamado['sla_nome'] ?? '' ?></span>
            <span class="cv-sla-pct"><?= $slaPercent ?>%</span>
        </div>
        <div class="cv-sla-track"><div class="cv-sla-fill" style="width:<?= $slaPercent ?>%"></div></div>
    </div>
    <?php endif; ?>
</div>

<!-- Grid Principal -->
<div class="chamado-view-grid">
    <!-- ===================== MAIN COLUMN ===================== -->
    <div class="chamado-view-main">

        <!-- Descrição -->
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-align-left"></i> Descrição</h3></div>
            <div class="cv-card-body">
                <div class="cv-description"><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></div>
            </div>
        </div>

        <!-- Tags -->
        <?php if (!empty($chamado['tags'])): ?>
        <div class="cv-tags-row">
            <?php foreach ($chamado['tags'] as $tag): ?>
            <span class="cv-tag">
                <i class="fas fa-tag"></i> <?= htmlspecialchars($tag['tag']) ?>
                <button onclick="removerTag(<?= $chamado['id'] ?>, '<?= htmlspecialchars($tag['tag']) ?>')" class="cv-tag-x">&times;</button>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Anexos -->
        <div class="cv-card">
            <div class="cv-card-header">
                <h3><i class="fas fa-paperclip"></i> Anexos (<?= count($chamado['anexos'] ?? []) ?>)</h3>
                <label class="btn btn-outline btn-sm cv-upload-btn">
                    <input type="file" id="inputAnexoChamado" name="anexos[]" multiple 
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" style="display:none"
                           onchange="uploadAnexos(this.files)">
                    <i class="fas fa-upload"></i> Adicionar Arquivo
                </label>
            </div>
            <div class="cv-card-body" id="anexosContainer">
                <?php if (!empty($chamado['anexos'])): ?>
                <div class="cv-anexos">
                    <?php foreach ($chamado['anexos'] as $anexo): 
                        $ext = strtolower(pathinfo($anexo['nome_original'], PATHINFO_EXTENSION));
                        $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                        $icon = $isImg ? 'fa-image' : ($ext === 'pdf' ? 'fa-file-pdf' : (in_array($ext, ['doc','docx']) ? 'fa-file-word' : 'fa-file'));
                        $color = $isImg ? '#10B981' : ($ext === 'pdf' ? '#EF4444' : '#3B82F6');
                    ?>
                    <a href="<?= UPLOAD_URL ?>/chamado/<?= $chamado['id'] ?>/<?= $anexo['nome_arquivo'] ?>" class="cv-anexo" target="_blank">
                        <i class="fas <?= $icon ?>" style="color:<?= $color ?>"></i>
                        <div>
                            <span class="cv-anexo-name"><?= htmlspecialchars($anexo['nome_original']) ?></span>
                            <small><?= round($anexo['tamanho'] / 1024) ?> KB</small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="cv-anexos-empty">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Nenhum anexo ainda</p>
                    <small>Clique em "Adicionar Arquivo" para enviar</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comentários -->
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-comments"></i> Comentários (<?= count($chamado['comentarios']) ?>)</h3></div>
            <div class="cv-card-body cv-card-body-flush">
                <div class="cv-chat" id="chatContainer">
                    <?php if (empty($chamado['comentarios'])): ?>
                    <div class="cv-chat-empty">
                        <i class="far fa-comment-dots"></i>
                        <p>Nenhum comentário ainda</p>
                        <small>Adicione o primeiro comentário abaixo</small>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($chamado['comentarios'] as $com): 
                        $isOwn = ($com['usuario_id'] ?? null) == $user['id'];
                        $isInternal = $com['tipo'] === 'interno';
                        $initials = strtoupper(mb_substr($com['autor_nome'] ?? $com['usuario_nome'] ?? 'S', 0, 2));
                        $avatarColors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4'];
                        $colorIdx = crc32($com['autor_nome'] ?? '') % count($avatarColors);
                        $avatarBg = $avatarColors[abs($colorIdx)];
                    ?>
                    <div class="cv-chat-msg <?= $isOwn ? 'cv-chat-own' : '' ?> <?= $isInternal ? 'cv-chat-internal' : '' ?>">
                        <?php if (!$isOwn): ?>
                        <div class="cv-chat-avatar" style="background:<?= $avatarBg ?>"><?= $initials ?></div>
                        <?php endif; ?>
                        <div class="cv-chat-bubble">
                            <div class="cv-chat-bubble-header">
                                <strong><?= htmlspecialchars($com['autor_nome'] ?? $com['usuario_nome'] ?? 'Sistema') ?></strong>
                                <time><?= tempoRelativo($com['criado_em']) ?></time>
                                <?php if ($isInternal): ?>
                                <span class="cv-chat-badge-int"><i class="fas fa-lock"></i> Interno</span>
                                <?php endif; ?>
                            </div>
                            <div class="cv-chat-text"><?= nl2br(htmlspecialchars($com['conteudo'])) ?></div>
                            <?php
                            // Mostrar anexos do comentário
                            $comAnexos = Database::getInstance()->fetchAll(
                                "SELECT * FROM anexos WHERE entidade_tipo = 'comentario' AND entidade_id = ?", [$com['id']]
                            );
                            if (!empty($comAnexos)): ?>
                            <div class="cv-chat-attachments">
                                <?php foreach ($comAnexos as $ca):
                                    $caExt = strtolower(pathinfo($ca['nome_original'], PATHINFO_EXTENSION));
                                    $caIsImg = in_array($caExt, ['jpg','jpeg','png','gif','webp']);
                                    $caUrl = UPLOAD_URL . '/comentarios/' . $com['id'] . '/' . $ca['nome_arquivo'];
                                ?>
                                <?php if ($caIsImg): ?>
                                <a href="<?= $caUrl ?>" target="_blank" class="cv-chat-img-preview">
                                    <img src="<?= $caUrl ?>" alt="<?= htmlspecialchars($ca['nome_original']) ?>">
                                </a>
                                <?php else: ?>
                                <a href="<?= $caUrl ?>" target="_blank" class="cv-chat-file">
                                    <i class="fas fa-file"></i> <?= htmlspecialchars($ca['nome_original']) ?>
                                    <small>(<?= round($ca['tamanho'] / 1024) ?> KB)</small>
                                </a>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($isOwn): ?>
                        <div class="cv-chat-avatar cv-chat-avatar-own" style="background:<?= $avatarBg ?>"><?= $initials ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Composer -->
                <form id="formComentario" class="cv-composer" enctype="multipart/form-data">
                    <textarea id="comentarioTexto" placeholder="Escreva um comentário..." rows="3"></textarea>
                    <div class="cv-composer-attachments" id="filePreview" style="display:none"></div>
                    <div class="cv-composer-bar">
                        <div class="cv-composer-left">
                            <label class="cv-composer-check">
                                <input type="checkbox" id="comentarioInterno" value="interno">
                                <i class="fas fa-lock"></i> Nota interna
                            </label>
                            <label class="cv-composer-file-btn" title="Anexar arquivo">
                                <input type="file" id="comentarioArquivos" name="arquivos[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" style="display:none">
                                <i class="fas fa-paperclip"></i> Anexar
                            </label>
                            <button type="button" class="cv-composer-ia-btn" onclick="iaSugerirResposta()" title="IA: Sugerir resposta para este chamado">
                                <i class="fas fa-robot"></i> Sugerir Resposta IA
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i> Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Histórico -->
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-history"></i> Histórico</h3></div>
            <div class="cv-card-body">
                <div class="cv-timeline">
                    <?php foreach ($chamado['historico'] as $h): ?>
                    <div class="cv-tl-item">
                        <div class="cv-tl-dot"></div>
                        <div class="cv-tl-body">
                            <span>
                                <strong><?= htmlspecialchars($h['usuario_nome'] ?? 'Sistema') ?></strong>
                                alterou <em><?= $h['campo'] ?></em>
                                <?php if ($h['valor_anterior']): ?>
                                    de <span class="cv-tl-old"><?= htmlspecialchars($h['valor_anterior']) ?></span>
                                <?php endif; ?>
                                para <span class="cv-tl-new"><?= htmlspecialchars($h['valor_novo'] ?? '-') ?></span>
                            </span>
                            <time><?= formatarData($h['criado_em']) ?></time>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="cv-tl-item">
                        <div class="cv-tl-dot cv-tl-dot-green"></div>
                        <div class="cv-tl-body">
                            <span><strong>Chamado aberto</strong></span>
                            <time><?= formatarData($chamado['data_abertura']) ?></time>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== SIDEBAR ===================== -->
    <div class="chamado-view-sidebar">

        <!-- Ações -->
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-sliders-h"></i> Ações</h3></div>
            <div class="cv-card-body">
                <div class="cv-sidebar-field">
                    <label>Status</label>
                    <select class="form-select" onchange="atualizarChamado(<?= $chamado['id'] ?>, 'status', this.value)">
                        <?php foreach ($statusList as $key => $s): ?>
                        <option value="<?= $key ?>" <?= $chamado['status'] === $key ? 'selected' : '' ?>><?= $s['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cv-sidebar-field">
                    <label>Prioridade</label>
                    <select class="form-select" onchange="atualizarChamado(<?= $chamado['id'] ?>, 'prioridade', this.value)">
                        <?php foreach ($prioridadeList as $key => $p): ?>
                        <option value="<?= $key ?>" <?= $chamado['prioridade'] === $key ? 'selected' : '' ?>><?= $p['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cv-sidebar-field">
                    <label>Técnico Responsável</label>
                    <select class="form-select" onchange="atualizarChamado(<?= $chamado['id'] ?>, 'tecnico_id', this.value)">
                        <option value="">Não atribuído</option>
                        <?php foreach ($tecnicos as $tec): ?>
                        <option value="<?= $tec['id'] ?>" <?= $chamado['tecnico_id'] == $tec['id'] ? 'selected' : '' ?>><?= $tec['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cv-sidebar-field">
                    <label>Categoria</label>
                    <select class="form-select" onchange="mudarCategoria(<?= $chamado['id'] ?>, this.value)">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($chamado['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cv-sidebar-field" style="padding-top:8px">
                    <button class="btn btn-sm btn-outline" onclick="iaClassificarChamado()" style="width:100%;gap:6px;justify-content:center" title="Classificar com IA">
                        <i class="fas fa-robot"></i> Classificar com IA
                    </button>
                </div>
            </div>
        </div>

        <!-- Transformar Chamado -->
        <div class="cv-card cv-card-transform">
            <div class="cv-card-header"><h3><i class="fas fa-exchange-alt"></i> Transformar em</h3></div>
            <div class="cv-card-body">
                <div class="cv-transform-grid">
                    <button class="cv-transform-btn cv-transform-projeto" onclick="abrirTransformacao('projeto')">
                        <i class="fas fa-project-diagram"></i>
                        <span>Projeto</span>
                    </button>
                    <button class="cv-transform-btn cv-transform-sprint" onclick="abrirTransformacao('sprint')">
                        <i class="fas fa-running"></i>
                        <span>Sprint</span>
                    </button>
                    <button class="cv-transform-btn cv-transform-compra" onclick="abrirTransformacao('compra')">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Compra</span>
                    </button>
                </div>
                <?php
                // Mostrar vínculos existentes
                $db2 = Database::getInstance();
                $vinculoProjeto = $db2->fetch("SELECT id, nome FROM projetos WHERE chamado_origem_id = ?", [$chamado['id']]);
                $vinculoCompra = $db2->fetch("SELECT id, codigo FROM compras WHERE chamado_origem_id = ?", [$chamado['id']]);
                $vinculoTarefa = $db2->fetch("SELECT t.id, t.titulo, s.nome as sprint_nome, s.id as sprint_id FROM tarefas t LEFT JOIN sprints s ON t.sprint_id = s.id WHERE t.chamado_origem_id = ?", [$chamado['id']]);
                ?>
                <?php if ($vinculoProjeto || $vinculoCompra || $vinculoTarefa): ?>
                <div class="cv-transform-links">
                    <?php if ($vinculoProjeto): ?>
                    <a href="<?= BASE_URL ?>/index.php?page=projetos&action=ver&id=<?= $vinculoProjeto['id'] ?>" class="cv-transform-link cv-tl-projeto">
                        <i class="fas fa-project-diagram"></i>
                        <span><?= htmlspecialchars($vinculoProjeto['nome']) ?></span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($vinculoTarefa): ?>
                    <a href="<?= BASE_URL ?>/index.php?page=sprints&acao=ver&id=<?= $vinculoTarefa['sprint_id'] ?>" class="cv-transform-link cv-tl-sprint">
                        <i class="fas fa-running"></i>
                        <span><?= htmlspecialchars($vinculoTarefa['sprint_nome'] ?? 'Sprint') ?></span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($vinculoCompra): ?>
                    <a href="<?= BASE_URL ?>/index.php?page=compras&action=ver&id=<?= $vinculoCompra['id'] ?>" class="cv-transform-link cv-tl-compra">
                        <i class="fas fa-shopping-cart"></i>
                        <span><?= htmlspecialchars($vinculoCompra['codigo']) ?></span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Solicitante -->
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-user"></i> Solicitante</h3></div>
            <div class="cv-card-body">
                <div class="cv-solicitante">
                    <div class="cv-solicitante-avatar">
                        <?= strtoupper(mb_substr($chamado['solicitante_nome'] ?? 'N', 0, 2)) ?>
                    </div>
                    <div class="cv-solicitante-data">
                        <strong><?= htmlspecialchars($chamado['solicitante_nome'] ?? 'N/A') ?></strong>
                        <?php if (!empty($chamado['telefone_solicitante'])): ?>
                        <a href="https://wa.me/<?= $chamado['telefone_solicitante'] ?>" target="_blank" class="cv-contact-link cv-whatsapp">
                            <i class="fab fa-whatsapp"></i> <?= $chamado['telefone_solicitante'] ?>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($chamado['solicitante_email'])): ?>
                        <a href="mailto:<?= $chamado['solicitante_email'] ?>" class="cv-contact-link">
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($chamado['solicitante_email']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalhes -->
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-info-circle"></i> Detalhes</h3></div>
            <div class="cv-card-body">
                <div class="cv-info-list">
                    <div class="cv-info-row">
                        <span class="cv-info-label">Categoria</span>
                        <span class="cv-info-value"><?= htmlspecialchars($chamado['categoria_nome'] ?? 'N/A') ?></span>
                    </div>
                    <div class="cv-info-row">
                        <span class="cv-info-label">Impacto</span>
                        <span class="cv-info-value"><?= ucfirst($chamado['impacto']) ?></span>
                    </div>
                    <div class="cv-info-row">
                        <span class="cv-info-label">Urgência</span>
                        <span class="cv-info-value"><?= ucfirst($chamado['urgencia']) ?></span>
                    </div>
                    <?php if (!empty($chamado['sla_nome'])): ?>
                    <div class="cv-info-row">
                        <span class="cv-info-label">SLA</span>
                        <span class="cv-info-value"><?= $chamado['sla_nome'] ?></span>
                    </div>
                    <div class="cv-info-row">
                        <span class="cv-info-label">Tempo Resposta</span>
                        <span class="cv-info-value"><?= $chamado['tempo_resposta'] ?> min</span>
                    </div>
                    <div class="cv-info-row">
                        <span class="cv-info-label">Tempo Resolução</span>
                        <span class="cv-info-value"><?= $chamado['tempo_resolucao'] ?> min</span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($chamado['ativo_nome'])): ?>
                    <div class="cv-info-row">
                        <span class="cv-info-label">Ativo</span>
                        <span class="cv-info-value"><?= htmlspecialchars($chamado['ativo_nome']) ?> (<?= $chamado['numero_patrimonio'] ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Datas -->
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-calendar-alt"></i> Datas</h3></div>
            <div class="cv-card-body">
                <div class="cv-info-list">
                    <div class="cv-info-row">
                        <span class="cv-info-label">Abertura</span>
                        <span class="cv-info-value"><?= formatarData($chamado['data_abertura']) ?></span>
                    </div>
                    <?php if (!empty($chamado['data_primeira_resposta'])): ?>
                    <div class="cv-info-row">
                        <span class="cv-info-label">1ª Resposta</span>
                        <span class="cv-info-value"><?= formatarData($chamado['data_primeira_resposta']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($chamado['data_resolucao'])): ?>
                    <div class="cv-info-row">
                        <span class="cv-info-label">Resolução</span>
                        <span class="cv-info-value"><?= formatarData($chamado['data_resolucao']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($chamado['data_fechamento'])): ?>
                    <div class="cv-info-row">
                        <span class="cv-info-label">Fechamento</span>
                        <span class="cv-info-value"><?= formatarData($chamado['data_fechamento']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Avaliação do Atendimento -->
        <?php if (in_array($chamado['status'], ['resolvido', 'fechado'])): ?>
        <div class="cv-card">
            <div class="cv-card-header"><h3><i class="fas fa-star-half-stroke"></i> Avaliação</h3></div>
            <div class="cv-card-body">
                <?php if (!empty($chamado['avaliacao']) && $chamado['avaliacao'] > 0): ?>
                <div style="text-align:center; padding: 8px 0;">
                    <div style="font-size: 1.6rem; margin-bottom: 4px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa<?= $i <= $chamado['avaliacao'] ? 's' : 'r' ?> fa-star" style="color: <?= $i <= $chamado['avaliacao'] ? '#FBBF24' : '#D1D5DB' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--gray-900);"><?= $chamado['avaliacao'] ?>/5</div>
                    <?php if (!empty($chamado['avaliacao_comentario'])): ?>
                    <div style="margin-top: 12px; padding: 12px; background: var(--gray-50); border-radius: 8px; font-size: 0.85rem; color: var(--gray-700); text-align: left; font-style: italic;">
                        <i class="fas fa-quote-left" style="color: var(--gray-300); margin-right: 4px;"></i>
                        <?= htmlspecialchars($chamado['avaliacao_comentario']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding: 12px 0;">
                    <div style="font-size: 2rem; color: var(--gray-300); margin-bottom: 8px;">
                        <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                    </div>
                    <p style="font-size: 0.82rem; color: var(--gray-500); margin: 0;">Aguardando avaliação do solicitante</p>
                    <button onclick="reenviarAvaliacao(<?= $chamado['id'] ?>)" class="btn btn-sm btn-secondary" style="margin-top: 10px; font-size: 0.78rem;">
                        <i class="fab fa-whatsapp"></i> Reenviar link de avaliação
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function atualizarChamado(id, campo, valor) {
    HelpDesk.api('POST', '/api/chamados.php', {
        action: 'atualizar', id: id, [campo]: valor
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Chamado atualizado! Solicitante notificado via WhatsApp.', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            HelpDesk.toast(resp.error || 'Erro ao atualizar', 'danger');
        }
    });
}

function mudarCategoria(id, categoriaId) {
    HelpDesk.api('POST', '/api/chamados.php', {
        action: 'atualizar', id: id, categoria_id: categoriaId || null
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Categoria atualizada! SLA recalculado.', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            HelpDesk.toast(resp.error || 'Erro ao atualizar', 'danger');
        }
    });
}

function excluirChamado(id, codigo) {
    if (!confirm('⚠️ ATENÇÃO: Excluir o chamado ' + codigo + '?\n\nEsta ação é irreversível e removerá:\n- Todos os comentários\n- Todos os anexos\n- Todo o histórico\n\nDeseja continuar?')) return;
    if (!confirm('Tem certeza absoluta?\n\nChamado: ' + codigo)) return;
    HelpDesk.api('POST', '/api/chamados.php', { action: 'excluir', id: id }).then(resp => {
        if (resp && resp.success) {
            HelpDesk.toast('Chamado excluído com sucesso!', 'success');
            setTimeout(() => location.href = '<?= BASE_URL ?>/index.php?page=chamados', 800);
        } else {
            HelpDesk.toast(resp?.error || 'Erro ao excluir chamado', 'danger');
        }
    });
}

function reenviarAvaliacao(chamadoId) {
    if (!confirm('Deseja reenviar o link de avaliação via WhatsApp para o solicitante?')) return;
    HelpDesk.api('POST', '/api/chamados.php', {
        action: 'reenviar_avaliacao', chamado_id: chamadoId
    }).then(resp => {
        if (resp.success) {
            HelpDesk.toast('Link de avaliação reenviado via WhatsApp!', 'success');
        } else {
            HelpDesk.toast(resp.error || 'Erro ao reenviar', 'danger');
        }
    });
}

function removerTag(chamadoId, tag) {
    HelpDesk.api('POST', '/api/chamados.php', { action: 'remover_tag', chamado_id: chamadoId, tag: tag }).then(resp => {
        if (resp.success) location.reload();
    });
}

// File preview
const fileInput = document.getElementById('comentarioArquivos');
const filePreview = document.getElementById('filePreview');
fileInput.addEventListener('change', function() {
    filePreview.innerHTML = '';
    if (this.files.length > 0) {
        filePreview.style.display = 'flex';
        Array.from(this.files).forEach((file, idx) => {
            const isImg = file.type.startsWith('image/');
            const el = document.createElement('div');
            el.className = 'cv-file-preview-item';
            if (isImg) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                el.appendChild(img);
            } else {
                el.innerHTML = '<i class="fas fa-file"></i>';
            }
            const name = document.createElement('span');
            name.textContent = file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name;
            el.appendChild(name);
            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '&times;';
            removeBtn.className = 'cv-file-remove';
            removeBtn.type = 'button';
            removeBtn.onclick = () => { el.remove(); if (filePreview.children.length === 0) filePreview.style.display = 'none'; };
            el.appendChild(removeBtn);
            filePreview.appendChild(el);
        });
    } else {
        filePreview.style.display = 'none';
    }
});

document.getElementById('formComentario').addEventListener('submit', function(e) {
    e.preventDefault();
    const texto = document.getElementById('comentarioTexto').value.trim();
    const arquivos = document.getElementById('comentarioArquivos').files;
    if (!texto && arquivos.length === 0) { HelpDesk.toast('Digite um comentário ou anexe um arquivo', 'warning'); return; }

    const tipo = document.getElementById('comentarioInterno').checked ? 'interno' : 'comentario';
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    const formData = new FormData();
    formData.append('action', 'comentar');
    formData.append('chamado_id', <?= $chamado['id'] ?>);
    formData.append('conteudo', texto || '📎 Arquivo(s) anexado(s)');
    formData.append('tipo', tipo);
    for (let i = 0; i < arquivos.length; i++) {
        formData.append('arquivos[]', arquivos[i]);
    }

    HelpDesk.api('POST', '/api/chamados.php', formData, true).then(resp => {
        if (resp.success) {
            const msg = tipo === 'interno' ? 'Nota interna salva!' : 'Comentário enviado! Solicitante notificado.';
            HelpDesk.toast(msg, 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
            HelpDesk.toast(resp.error || 'Erro', 'danger');
        }
    });
});

// Auto-scroll chat
const chat = document.getElementById('chatContainer');
if (chat) chat.scrollTop = chat.scrollHeight;

// ==================== TRANSFORMAÇÕES ====================

function abrirTransformacao(tipo) {
    let html = '', title = '', icon = '';
    const chamadoId = <?= $chamado['id'] ?>;
    const chamadoTitulo = <?= json_encode($chamado['titulo']) ?>;
    const chamadoDesc = <?= json_encode($chamado['descricao']) ?>;

    if (tipo === 'projeto') {
        icon = 'fa-project-diagram';
        title = 'Transformar em Projeto';
        html = `
        <form id="formTransProjeto" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label">Nome do Projeto *</label>
                <input type="text" name="nome" class="form-input" required value="${chamadoTitulo}">
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-textarea" rows="3">${chamadoDesc}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Responsável</label>
                <select name="responsavel_id" class="form-select">
                    <option value="">Selecione</option>
                    <?php foreach ($tecnicos as $tec): ?>
                    <option value="<?= $tec['id'] ?>" <?= ($chamado['tecnico_id'] == $tec['id']) ? 'selected' : '' ?>><?= $tec['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Data Início</label>
                <input type="date" name="data_inicio" class="form-input" value="${new Date().toISOString().split('T')[0]}">
            </div>
            <div class="form-group">
                <label class="form-label">Prazo</label>
                <input type="date" name="prazo" class="form-input">
            </div>
            <p class="cv-transform-info col-span-2">
                <i class="fas fa-info-circle"></i>
                O chamado será vinculado ao projeto e o solicitante será notificado via WhatsApp.
            </p>
        </form>`;
    } else if (tipo === 'sprint') {
        icon = 'fa-running';
        title = 'Transformar em Sprint';
        html = `
        <form id="formTransSprint" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label">Projeto *</label>
                <select name="projeto_id" class="form-select" required>
                    <option value="">Selecione o projeto</option>
                    <?php foreach ($projetos ?? [] as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Nome da Sprint</label>
                <input type="text" name="nome" class="form-input" value="Sprint - ${chamadoTitulo}">
            </div>
            <div class="form-group">
                <label class="form-label">Data Início</label>
                <input type="date" name="data_inicio" class="form-input" value="${new Date().toISOString().split('T')[0]}">
            </div>
            <div class="form-group">
                <label class="form-label">Data Fim</label>
                <input type="date" name="data_fim" class="form-input" value="${new Date(Date.now() + 14*86400000).toISOString().split('T')[0]}">
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Meta da Sprint</label>
                <textarea name="meta" class="form-textarea" rows="2" placeholder="Descreva o objetivo da sprint">Resolver: ${chamadoTitulo}</textarea>
            </div>
            <p class="cv-transform-info col-span-2">
                <i class="fas fa-info-circle"></i>
                Será criada uma sprint com uma tarefa vinculada ao chamado. O solicitante será notificado.
            </p>
        </form>`;
    } else if (tipo === 'compra') {
        icon = 'fa-shopping-cart';
        title = 'Transformar em Requisição de Compra';
        html = `
        <form id="formTransCompra" class="form-grid">
            <div class="form-group col-span-2">
                <label class="form-label">Item / Produto *</label>
                <input type="text" name="item" class="form-input" required value="${chamadoTitulo}">
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-textarea" rows="3">${chamadoDesc}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Quantidade</label>
                <input type="number" name="quantidade" class="form-input" value="1" min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Valor Estimado (R$)</label>
                <input type="number" name="valor_estimado" class="form-input" step="0.01" min="0" placeholder="0,00">
            </div>
            <div class="form-group col-span-2">
                <label class="form-label">Justificativa *</label>
                <textarea name="justificativa" class="form-textarea" rows="2" required>Originado do chamado <?= $chamado['codigo'] ?>: ${chamadoTitulo}</textarea>
            </div>
            <p class="cv-transform-info col-span-2">
                <i class="fas fa-info-circle"></i>
                Uma requisição de compra será criada e o solicitante será notificado via WhatsApp.
            </p>
        </form>`;
    }

    HelpDesk.showModal(`<i class="fas ${icon}"></i> ${title}`, html, `
        <button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Cancelar</button>
        <button class="btn btn-primary" onclick="executarTransformacao('${tipo}', ${chamadoId})">
            <i class="fas fa-exchange-alt"></i> Transformar
        </button>
    `);
}

function executarTransformacao(tipo, chamadoId) {
    const formId = tipo === 'projeto' ? 'formTransProjeto' : (tipo === 'sprint' ? 'formTransSprint' : 'formTransCompra');
    const form = document.getElementById(formId);
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const formData = new FormData(form);
    const payload = { action: 'transformar_' + tipo, chamado_id: chamadoId };
    for (const [key, value] of formData.entries()) {
        payload[key] = value;
    }

    // Disable button
    const btn = document.querySelector('.modal-footer .btn-primary');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Transformando...'; }

    HelpDesk.api('POST', '/api/chamados.php', payload).then(resp => {
        if (resp.success) {
            HelpDesk.closeModal();
            HelpDesk.toast(resp.message || 'Transformação realizada com sucesso! Solicitante notificado.', 'success');

            // Redirecionar após 1.5s
            setTimeout(() => {
                if (tipo === 'projeto' && resp.projeto_id) {
                    window.location.href = '<?= BASE_URL ?>/index.php?page=projetos&action=ver&id=' + resp.projeto_id;
                } else if (tipo === 'sprint' && resp.sprint_id) {
                    window.location.href = '<?= BASE_URL ?>/index.php?page=sprints&acao=ver&id=' + resp.sprint_id;
                } else if (tipo === 'compra' && resp.compra_id) {
                    window.location.href = '<?= BASE_URL ?>/index.php?page=compras&action=ver&id=' + resp.compra_id;
                } else {
                    location.reload();
                }
            }, 1500);
        } else {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Transformar'; }
            HelpDesk.toast(resp.error || 'Erro na transformação', 'danger');
        }
    }).catch(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Transformar'; }
        HelpDesk.toast('Erro de conexão', 'danger');
    });
}

// Upload de anexos ao chamado
function uploadAnexos(files) {
    if (!files || files.length === 0) return;

    const formData = new FormData();
    formData.append('action', 'upload_anexo');
    formData.append('chamado_id', <?= $chamado['id'] ?>);
    for (let i = 0; i < files.length; i++) {
        formData.append('anexos[]', files[i]);
    }

    HelpDesk.toast('Enviando arquivo(s)...', 'info');

    HelpDesk.api('POST', '/api/chamados.php', formData, true)
        .then(resp => {
            if (resp.success) {
                HelpDesk.toast(`${resp.uploaded} arquivo(s) enviado(s)!`, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                HelpDesk.toast(resp.error || 'Erro ao enviar arquivos', 'danger');
            }
        }).catch(() => {
            HelpDesk.toast('Erro de conexão ao enviar arquivos', 'danger');
        });

    // Limpar input
    document.getElementById('inputAnexoChamado').value = '';
}

// ==================== IA ASSISTENTE ====================

function iaSugerirResposta() {
    const btn = document.querySelector('.cv-composer-ia-btn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';

    HelpDesk.api('POST', '/api/ia.php', {
        action: 'sugerir_resposta',
        chamado_id: <?= $chamado['id'] ?>
    }).then(resp => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (resp.success && resp.data.sugestao) {
            const textarea = document.getElementById('comentarioTexto');
            textarea.value = resp.data.sugestao;
            textarea.focus();
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 300) + 'px';
            HelpDesk.toast('Sugestão gerada pela IA! Revise antes de enviar.', 'success');
        } else {
            HelpDesk.toast(resp.error || 'Erro ao gerar sugestão', 'danger');
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        HelpDesk.toast('Erro de conexão com a IA', 'danger');
    });
}

// ==================== ACESSO REMOTO ====================
(function checkRemoteClient() {
    const container = document.getElementById('btnRemoteContainer');
    if (!container) return;

    const BASE = <?= json_encode(BASE_URL) ?>;
    fetch(BASE + '/api/remoto.php?action=chamado_client&chamado_id=<?= $chamado['id'] ?>')
        .then(r => r.json())
        .then(data => {
            if (data.available && data.sessao) {
                const s = data.sessao;
                const statusColor = s.status === 'online' ? '#10B981' : '#3B82F6';
                const statusLabel = s.status === 'online' ? 'Online' : 'Conectado';
                container.innerHTML = `
                    <button class="btn btn-sm" onclick="conectarRemoto('${s.codigo}')" 
                            style="background:linear-gradient(135deg,#6366F1,#8B5CF6);color:white;border:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(99,102,241,0.3);animation:pulseRemote 2s infinite;">
                        <i class="fas fa-desktop"></i>
                        <span>Acesso Remoto</span>
                        <span style="background:${statusColor};color:white;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600;">${statusLabel}</span>
                    </button>
                `;
                // Adicionar animação de pulse
                if (!document.getElementById('remoteAnimStyle')) {
                    const style = document.createElement('style');
                    style.id = 'remoteAnimStyle';
                    style.textContent = `
                        @keyframes pulseRemote {
                            0%, 100% { box-shadow: 0 2px 8px rgba(99,102,241,0.3); }
                            50% { box-shadow: 0 2px 16px rgba(99,102,241,0.5); }
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
        })
        .catch(() => {});
})();

function conectarRemoto(codigo) {
    const BASE = <?= json_encode(BASE_URL) ?>;
    window.open(
        BASE + '/index.php?page=remoto&code=' + encodeURIComponent(codigo) + '&chamado_id=<?= $chamado['id'] ?>',
        '_blank'
    );
}

function iaClassificarChamado() {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Classificando...';

    HelpDesk.api('POST', '/api/ia.php', {
        action: 'classificar_chamado',
        titulo: <?= json_encode($chamado['titulo']) ?>,
        descricao: <?= json_encode($chamado['descricao']) ?>
    }).then(resp => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (resp.success && resp.data) {
            const cat = resp.data.categoria || 'N/A';
            const pri = resp.data.prioridade || 'N/A';
            const resumo = resp.data.resumo || '';

            let html = '<div style="padding:8px 0">';
            html += '<div style="margin-bottom:12px"><strong>📂 Categoria sugerida:</strong> <span style="color:var(--primary);font-weight:600">' + cat.charAt(0).toUpperCase() + cat.slice(1) + '</span></div>';
            html += '<div style="margin-bottom:12px"><strong>🚦 Prioridade sugerida:</strong> <span style="font-weight:600">' + pri.charAt(0).toUpperCase() + pri.slice(1) + '</span></div>';
            if (resumo) {
                html += '<div style="margin-bottom:12px"><strong>📝 Resumo:</strong> ' + resumo + '</div>';
            }
            html += '<p style="font-size:12px;color:var(--gray-500);margin-top:12px"><i class="fas fa-info-circle"></i> Aplique as sugestões manualmente nos campos ao lado se concordar.</p>';
            html += '</div>';

            HelpDesk.showModal('<i class="fas fa-robot"></i> Classificação IA', html, '<button class="btn btn-secondary" onclick="HelpDesk.closeModal()">Fechar</button>');
        } else {
            HelpDesk.toast(resp.error || 'Erro ao classificar', 'danger');
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        HelpDesk.toast('Erro de conexão com a IA', 'danger');
    });
}
</script>
