<?php
/**
 * Portal Público - Abertura e Consulta de Chamados
 */
session_start();
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();
$categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
$configs = [];
$confs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($confs as $c) $configs[$c['chave']] = $c['valor'];

$statusList = CHAMADO_STATUS;
$prioridadeList = PRIORIDADES;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Suporte - <?= $configs['empresa_nome'] ?? 'Oracle X' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F1F5F9;
            color: #1E293B;
            min-height: 100vh;
        }
        .portal-header {
            background: linear-gradient(135deg, #1E1B4B 0%, #4338CA 100%);
            color: white;
            padding: 48px 24px;
            text-align: center;
        }
        .portal-header h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .portal-header p {
            opacity: 0.85;
            font-size: 16px;
        }
        .portal-header .logo-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
        .portal-container {
            max-width: 800px;
            margin: -40px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }
        .portal-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 0;
        }
        .portal-tab {
            flex: 1;
            padding: 14px 20px;
            background: white;
            border: none;
            border-radius: 12px 12px 0 0;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: #6B7280;
            cursor: pointer;
            transition: all 0.2s;
        }
        .portal-tab.active {
            background: white;
            color: #4F46E5;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        }
        .portal-tab i { margin-right: 8px; }
        .portal-card {
            background: white;
            border-radius: 0 0 16px 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .portal-panel { display: none; }
        .portal-panel.active { display: block; }
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        }
        .form-textarea { resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.3);
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
        .alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
        .chamado-result {
            background: #F8FAFC;
            border-radius: 12px;
            padding: 24px;
        }
        .chamado-result h3 {
            font-size: 18px;
            margin-bottom: 16px;
            color: #1E1B4B;
        }
        .result-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .result-row:last-child { border-bottom: none; }
        .result-label { font-weight: 600; color: #6B7280; font-size: 13px; }
        .result-value { font-weight: 500; }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .comment-list { margin-top: 20px; }
        .comment-item {
            background: white;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 10px;
            border: 1px solid #E5E7EB;
        }
        .comment-meta {
            font-size: 12px;
            color: #6B7280;
            margin-bottom: 6px;
        }
        .comment-meta strong { color: #1E293B; }
        .portal-footer {
            text-align: center;
            padding: 24px;
            color: #6B7280;
            font-size: 13px;
        }
        .portal-footer a { color: #4F46E5; text-decoration: none; }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .portal-card { padding: 20px; }
        }

        /* Loading Modal */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.active { display: flex; }
        .loading-card {
            background: white;
            border-radius: 20px;
            padding: 48px 56px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: loadingSlideIn 0.3s ease;
        }
        @keyframes loadingSlideIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .loading-spinner {
            width: 56px; height: 56px;
            border: 4px solid #E5E7EB;
            border-top-color: #4F46E5;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-title {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 6px;
        }
        .loading-subtitle {
            font-size: 14px;
            color: #6B7280;
        }

        /* Success Modal */
        .success-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .success-overlay.active { display: flex; }
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 48px 56px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: loadingSlideIn 0.3s ease;
            max-width: 440px;
        }
        .success-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: #ECFDF5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .success-icon i { font-size: 28px; color: #059669; }
        .success-title {
            font-size: 20px;
            font-weight: 800;
            color: #1E293B;
            margin-bottom: 8px;
        }
        .success-subtitle {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .success-code {
            display: inline-block;
            background: #F1F5F9;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 20px;
            font-weight: 800;
            color: #4F46E5;
            letter-spacing: 1px;
            margin-bottom: 20px;
            font-family: 'SF Mono', 'Consolas', monospace;
        }
        .success-btn {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }
        .success-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.3);
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <i class="fas fa-headset logo-icon"></i>
        <h1><?= $configs['empresa_nome'] ?? 'Oracle X' ?></h1>
        <p>Portal de Suporte — Abra e acompanhe seus chamados</p>
    </div>

    <div class="portal-container">
        <div class="portal-tabs">
            <button class="portal-tab active" onclick="switchTab('abrir')">
                <i class="fas fa-plus-circle"></i> Abrir Chamado
            </button>
            <button class="portal-tab" onclick="switchTab('consultar')">
                <i class="fas fa-search"></i> Consultar Chamado
            </button>
        </div>

        <div class="portal-card">
            <!-- Alert dinâmico -->
            <div class="alert" id="portalAlert" style="display:none">
                <i class="fas fa-info-circle" id="portalAlertIcon"></i>
                <span id="portalAlertText"></span>
            </div>

            <!-- Painel: Abrir Chamado -->
            <div class="portal-panel active" id="panelAbrir">
                <form id="formAbrirChamado">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telefone (WhatsApp) *</label>
                            <input type="text" name="telefone" class="form-input" required 
                                   placeholder="5562999999999" value="55">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoria *</label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Impacto</label>
                            <select name="impacto" class="form-select">
                                <option value="baixo">Baixo - Apenas eu sou afetado</option>
                                <option value="medio" selected>Médio - Minha equipe é afetada</option>
                                <option value="alto">Alto - Toda a empresa é afetada</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Urgência</label>
                            <select name="urgencia" class="form-select">
                                <option value="baixa">Baixa - Pode aguardar</option>
                                <option value="media" selected>Média - Preciso em breve</option>
                                <option value="alta">Alta - Preciso imediatamente</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Título do Chamado *</label>
                        <input type="text" name="titulo" class="form-input" required 
                               placeholder="Descreva brevemente o problema">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descrição Detalhada *</label>
                        <textarea name="descricao" class="form-textarea" rows="5" required 
                                  placeholder="Descreva o problema com o máximo de detalhes possível"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Anexos (opcional)</label>
                        <input type="file" name="anexos[]" class="form-input" multiple>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Enviar Chamado
                    </button>
                </form>
            </div>

            <!-- Painel: Consultar -->
            <div class="portal-panel" id="panelConsultar">
                <form id="formConsultarChamado" style="margin-bottom: 24px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telefone (WhatsApp)</label>
                            <input type="text" name="telefone" class="form-input" required placeholder="5562999999999" value="55" id="consultaTelefone">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Código do Chamado</label>
                            <input type="text" name="codigo" class="form-input" required placeholder="HD-2026-00001" id="consultaCodigo">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-search"></i> Consultar
                    </button>
                </form>

                <div id="resultadoConsulta" style="display:none"></div>
            </div>
        </div>

        <div class="portal-footer">
            <p>Powered by <a href="<?= BASE_URL ?>">Oracle X</a> | <a href="<?= BASE_URL ?>/login.php">Área Administrativa</a></p>
        </div>
    </div>

    <script>
    /* =================== Tab switching =================== */
    function switchTab(tab) {
        document.querySelectorAll('.portal-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.portal-panel').forEach(p => p.classList.remove('active'));
        hideAlert();

        if (tab === 'abrir') {
            document.querySelectorAll('.portal-tab')[0].classList.add('active');
            document.getElementById('panelAbrir').classList.add('active');
        } else {
            document.querySelectorAll('.portal-tab')[1].classList.add('active');
            document.getElementById('panelConsultar').classList.add('active');
        }
    }

    /* =================== Alert helpers =================== */
    function showAlert(text, type) {
        const el = document.getElementById('portalAlert');
        const icon = document.getElementById('portalAlertIcon');
        const txt = document.getElementById('portalAlertText');
        el.className = 'alert alert-' + type;
        icon.className = 'fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle');
        txt.innerHTML = text;
        el.style.display = 'flex';
    }

    function hideAlert() {
        document.getElementById('portalAlert').style.display = 'none';
    }

    /* =================== Loading Modal =================== */
    function showLoading(title, subtitle) {
        document.getElementById('loadingTitle').textContent = title || 'Processando...';
        document.getElementById('loadingSubtitle').textContent = subtitle || 'Aguarde um momento';
        document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }

    /* =================== Success Modal =================== */
    function showSuccess(codigo, telefone) {
        document.getElementById('successCodigo').textContent = codigo;
        document.getElementById('successOverlay').classList.add('active');

        document.getElementById('btnAcompanhar').onclick = function() {
            document.getElementById('successOverlay').classList.remove('active');
            // Preencher consulta e trocar tab
            document.getElementById('consultaTelefone').value = telefone;
            document.getElementById('consultaCodigo').value = codigo;
            switchTab('consultar');
            // Auto-consultar
            consultarChamado(telefone, codigo);
        };
    }

    /* =================== API helper (JSON) =================== */
    async function portalApi(data) {
        const resp = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await resp.json();
    }

    /* =================== API helper (FormData — for file uploads) =================== */
    async function portalApiForm(formData) {
        const resp = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        return await resp.json();
    }

    /* =================== Abrir chamado =================== */
    document.getElementById('formAbrirChamado').addEventListener('submit', async function(e) {
        e.preventDefault();
        hideAlert();

        const form = this;
        const formData = new FormData(form);
        formData.append('acao', 'abrir');

        // Validação básica client-side
        const nome = formData.get('nome'), email = formData.get('email'),
              telefone = formData.get('telefone'), titulo = formData.get('titulo'),
              descricao = formData.get('descricao');
        if (!nome || !email || !telefone || !titulo || !descricao) {
            showAlert('Preencha todos os campos obrigatórios.', 'error');
            return;
        }

        showLoading('Abrindo seu chamado...', 'Estamos registrando sua solicitação');

        try {
            const resp = await portalApiForm(formData);
            hideLoading();

            if (resp.success) {
                // Limpar formulário
                form.reset();
                form.querySelector('[name="telefone"]').value = '55';

                // Mostrar modal de sucesso
                showSuccess(resp.codigo, resp.telefone);
            } else {
                showAlert(resp.error || 'Erro ao abrir chamado.', 'error');
            }
        } catch (err) {
            hideLoading();
            showAlert('Erro de conexão com o servidor. Tente novamente.', 'error');
        }
    });

    /* =================== Consultar chamado =================== */
    async function consultarChamado(telefone, codigo) {
        hideAlert();
        showLoading('Consultando chamado...', 'Buscando informações');

        try {
            const resp = await portalApi({ acao: 'consultar', telefone, codigo });
            hideLoading();

            if (resp.success) {
                renderResultado(resp.chamado);
            } else {
                document.getElementById('resultadoConsulta').style.display = 'none';
                showAlert(resp.error || 'Chamado não encontrado.', 'error');
            }
        } catch (err) {
            hideLoading();
            showAlert('Erro de conexão com o servidor. Tente novamente.', 'error');
        }
    }

    document.getElementById('formConsultarChamado').addEventListener('submit', function(e) {
        e.preventDefault();
        const telefone = document.getElementById('consultaTelefone').value.trim();
        const codigo = document.getElementById('consultaCodigo').value.trim();
        if (!telefone || !codigo) {
            showAlert('Informe o telefone e o código do chamado.', 'error');
            return;
        }
        consultarChamado(telefone, codigo);
    });

    /* =================== Render resultado =================== */
    function renderResultado(ch) {
        let html = `
        <div class="chamado-result">
            <h3><i class="fas fa-ticket-alt"></i> ${ch.codigo} — ${ch.titulo}</h3>
            <div class="result-row">
                <span class="result-label">Status</span>
                <span class="status-badge" style="background:${ch.status_cor}20;color:${ch.status_cor}">
                    <i class="${ch.status_icone}"></i> ${ch.status_label}
                </span>
            </div>
            <div class="result-row">
                <span class="result-label">Prioridade</span>
                <span class="result-value">${ch.prioridade}</span>
            </div>
            <div class="result-row">
                <span class="result-label">Categoria</span>
                <span class="result-value">${ch.categoria}</span>
            </div>
            <div class="result-row">
                <span class="result-label">Técnico</span>
                <span class="result-value">${ch.tecnico}</span>
            </div>
            <div class="result-row">
                <span class="result-label">Aberto em</span>
                <span class="result-value">${ch.data_abertura}</span>
            </div>
            <div class="result-row">
                <span class="result-label">Descrição</span>
                <span class="result-value">${ch.descricao}</span>
            </div>`;

        if (ch.comentarios && ch.comentarios.length > 0) {
            html += `<div class="comment-list"><h4 style="margin-bottom:12px"><i class="fas fa-comments"></i> Comentários</h4>`;
            ch.comentarios.forEach(com => {
                html += `
                <div class="comment-item">
                    <div class="comment-meta"><strong>${com.autor}</strong> — ${com.data}</div>
                    <div>${com.conteudo}</div>
                </div>`;
            });
            html += `</div>`;
        }

        html += `</div>`;

        const container = document.getElementById('resultadoConsulta');
        container.innerHTML = html;
        container.style.display = 'block';
    }
    </script>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-card">
            <div class="loading-spinner"></div>
            <div class="loading-title" id="loadingTitle">Processando...</div>
            <div class="loading-subtitle" id="loadingSubtitle">Aguarde um momento</div>
        </div>
    </div>

    <!-- Success Overlay -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-card">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <div class="success-title">Chamado Aberto com Sucesso!</div>
            <div class="success-subtitle">Seu chamado foi registrado e nossa equipe será notificada. Guarde o código abaixo para acompanhamento:</div>
            <div class="success-code" id="successCodigo"></div>
            <br>
            <button class="success-btn" id="btnAcompanhar">
                <i class="fas fa-search"></i> Acompanhar Chamado
            </button>
        </div>
    </div>
</body>
</html>
