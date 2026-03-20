<?php
/**
 * Portal Público - Avaliação de Atendimento
 * Acesso via link enviado por WhatsApp (sem login)
 */
session_start();
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// Buscar configs
$configs = [];
$confs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($confs as $c) $configs[$c['chave']] = $c['valor'];

$empresaNome = $configs['empresa_nome'] ?? 'Oracle X';

// Parâmetros: ?codigo=HD-2026-00001&token=abc123
$codigo = $_GET['codigo'] ?? '';
$token  = $_GET['token'] ?? '';

$chamado = null;
$erro = null;
$sucesso = false;

if (empty($codigo) || empty($token)) {
    $erro = 'Link de avaliação inválido.';
} else {
    // Buscar chamado pelo código
    $chamado = $db->fetch(
        "SELECT c.*, s.nome AS solicitante_nome, s.telefone AS solicitante_telefone,
                cat.nome AS categoria_nome,
                u.nome AS tecnico_nome
         FROM chamados c
         LEFT JOIN solicitantes s ON c.solicitante_id = s.id
         LEFT JOIN categorias cat ON c.categoria_id = cat.id
         LEFT JOIN usuarios u ON c.tecnico_id = u.id
         WHERE c.codigo = ?",
        [$codigo]
    );

    if (!$chamado) {
        $erro = 'Chamado não encontrado.';
    } else {
        // Validar token (hash do código + telefone + id)
        $tokenEsperado = hash('sha256', $chamado['codigo'] . $chamado['telefone_solicitante'] . $chamado['id']);
        if (!hash_equals($tokenEsperado, $token)) {
            $erro = 'Link de avaliação inválido ou expirado.';
            $chamado = null;
        } elseif (!in_array($chamado['status'], ['resolvido', 'fechado'])) {
            $erro = 'Este chamado ainda não foi finalizado.';
            $chamado = null;
        } elseif (!empty($chamado['avaliacao']) && $chamado['avaliacao'] > 0) {
            $sucesso = true; // já avaliado
        }
    }
}

// Processar envio da avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $chamado && !$sucesso) {
    $nota = (int)($_POST['nota'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');

    if ($nota < 1 || $nota > 5) {
        $erro = 'Por favor, selecione uma nota de 1 a 5 estrelas.';
    } else {
        $db->update('chamados', [
            'avaliacao' => $nota,
            'avaliacao_comentario' => !empty($comentario) ? $comentario : null
        ], 'id = ?', [$chamado['id']]);

        $sucesso = true;

        // Notificar equipe sobre avaliação recebida
        require_once __DIR__ . '/../app/models/Notificacao.php';
        $notificacao = new Notificacao();

        $estrelas = str_repeat('⭐', $nota);
        $msgEquipe  = "ðŸ“Š *Avaliação de Atendimento Recebida*\n\n";
        $msgEquipe .= "ðŸ“‹ *Chamado:* {$chamado['codigo']}\n";
        $msgEquipe .= "ðŸ“ *Título:* {$chamado['titulo']}\n";
        $msgEquipe .= "ðŸ‘¤ *Solicitante:* {$chamado['solicitante_nome']}\n";
        if (!empty($chamado['tecnico_nome'])) {
            $msgEquipe .= "ðŸ”§ *Técnico:* {$chamado['tecnico_nome']}\n";
        }
        $msgEquipe .= "\n{$estrelas} *Nota: {$nota}/5*\n";
        if (!empty($comentario)) {
            $preview = mb_strlen($comentario) > 300 ? mb_substr($comentario, 0, 300) . '...' : $comentario;
            $msgEquipe .= "\nðŸ’­ *Comentário:*\n_{$preview}_\n";
        }
        $msgEquipe .= "\nðŸ“… " . date('d/m/Y H:i');

        // Enviar para equipe técnica
        $tecnicos = $db->fetchAll("SELECT telefone FROM usuarios WHERE tipo IN ('tecnico','gestor','admin') AND ativo = 1");
        foreach ($tecnicos as $tec) {
            $notificacao->sendWhatsApp($tec['telefone'], $msgEquipe);
        }
    }
}

$statusList = CHAMADO_STATUS;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliar Atendimento - <?= htmlspecialchars($empresaNome) ?></title>
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
        .av-header {
            background: linear-gradient(135deg, #1E1B4B 0%, #4338CA 100%);
            color: white;
            padding: 48px 24px 80px;
            text-align: center;
        }
        .av-header-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
        .av-header h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .av-header p {
            opacity: 0.85;
            font-size: 15px;
        }
        .av-container {
            max-width: 560px;
            margin: -48px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }
        .av-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
        }

        /* Chamado Info */
        .av-chamado-info {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
        }
        .av-chamado-info h3 {
            font-size: 14px;
            color: #64748B;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .av-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
        }
        .av-info-row:not(:last-child) {
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .av-info-label {
            font-size: 13px;
            color: #64748B;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .av-info-label i { width: 14px; text-align: center; }
        .av-info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1E293B;
        }

        /* Stars Rating */
        .av-rating-section {
            text-align: center;
            margin-bottom: 28px;
        }
        .av-rating-section h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 4px;
        }
        .av-rating-section p {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 20px;
        }
        .av-stars {
            display: flex;
            justify-content: center;
            gap: 8px;
            direction: rtl;
        }
        .av-stars input { display: none; }
        .av-stars label {
            font-size: 40px;
            color: #D1D5DB;
            cursor: pointer;
            transition: all 0.15s;
            padding: 4px;
        }
        .av-stars label:hover,
        .av-stars label:hover ~ label,
        .av-stars input:checked ~ label {
            color: #FBBF24;
            transform: scale(1.15);
        }
        .av-stars label:active { transform: scale(0.95); }

        .av-rating-text {
            font-size: 14px;
            font-weight: 600;
            color: #6366F1;
            margin-top: 12px;
            min-height: 22px;
            transition: all .2s;
        }

        /* Comment */
        .av-comment-group {
            margin-bottom: 24px;
        }
        .av-comment-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .av-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #D1D5DB;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            transition: border-color 0.2s;
            color: #1E293B;
        }
        .av-textarea:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .av-textarea::placeholder { color: #9CA3AF; }

        /* Button */
        .av-btn {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .av-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
        }
        .av-btn:active { transform: translateY(0); }
        .av-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Success */
        .av-success {
            text-align: center;
            padding: 20px 0;
        }
        .av-success-icon {
            width: 80px; height: 80px;
            background: #ECFDF5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: #10B981;
        }
        .av-success h2 {
            font-size: 22px;
            font-weight: 800;
            color: #1E293B;
            margin-bottom: 8px;
        }
        .av-success p {
            font-size: 14px;
            color: #64748B;
            line-height: 1.6;
        }
        .av-success-stars {
            font-size: 32px;
            margin: 16px 0;
            color: #FBBF24;
        }

        /* Error */
        .av-error {
            text-align: center;
            padding: 20px 0;
        }
        .av-error-icon {
            width: 80px; height: 80px;
            background: #FEF2F2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: #EF4444;
        }
        .av-error h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 8px;
        }
        .av-error p {
            font-size: 14px;
            color: #64748B;
        }
        .av-error a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4F46E5;
            text-decoration: none;
            font-weight: 600;
            margin-top: 16px;
        }
        .av-error a:hover { text-decoration: underline; }

        .av-alert {
            background: #FEF2F2;
            color: #B91C1C;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .av-footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #94A3B8;
        }
        .av-footer a {
            color: #6366F1;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .av-header { padding: 36px 20px 64px; }
            .av-header h1 { font-size: 22px; }
            .av-card { padding: 24px 20px; }
            .av-stars label { font-size: 32px; }
        }
    </style>
</head>
<body>

<div class="av-header">
    <span class="av-header-icon"><i class="fas fa-star-half-stroke"></i></span>
    <h1>Avaliação de Atendimento</h1>
    <p><?= htmlspecialchars($empresaNome) ?> — Sua opinião é muito importante</p>
</div>

<div class="av-container">
    <div class="av-card">
        <?php if ($erro && !$chamado): ?>
            <!-- Erro: link inválido -->
            <div class="av-error">
                <div class="av-error-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <h2>Ops!</h2>
                <p><?= htmlspecialchars($erro) ?></p>
                <a href="<?= BASE_URL ?>/portal/"><i class="fas fa-arrow-left"></i> Ir para o Portal</a>
            </div>

        <?php elseif ($sucesso): ?>
            <!-- Sucesso: avaliação salva ou já avaliada -->
            <div class="av-success">
                <div class="av-success-icon"><i class="fas fa-check"></i></div>
                <h2>Obrigado pela sua avaliação!</h2>
                <div class="av-success-stars">
                    <?php
                    $notaFinal = $chamado['avaliacao'] ?? ($nota ?? 0);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $notaFinal ? '★' : '☆';
                    }
                    ?>
                </div>
                <p>
                    Sua avaliação do chamado <strong><?= htmlspecialchars($chamado['codigo']) ?></strong> foi registrada com sucesso.<br>
                    Agradecemos por nos ajudar a melhorar nosso atendimento!
                </p>
            </div>

        <?php else: ?>
            <!-- Formulário de avaliação -->
            <?php if ($erro): ?>
                <div class="av-alert"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <!-- Info do Chamado -->
            <div class="av-chamado-info">
                <h3><i class="fas fa-ticket-alt"></i> Dados do Chamado</h3>
                <div class="av-info-row">
                    <span class="av-info-label"><i class="fas fa-hashtag"></i> Código</span>
                    <span class="av-info-value"><?= htmlspecialchars($chamado['codigo']) ?></span>
                </div>
                <div class="av-info-row">
                    <span class="av-info-label"><i class="fas fa-heading"></i> Título</span>
                    <span class="av-info-value"><?= htmlspecialchars(mb_strimwidth($chamado['titulo'], 0, 40, '...')) ?></span>
                </div>
                <?php if (!empty($chamado['categoria_nome'])): ?>
                <div class="av-info-row">
                    <span class="av-info-label"><i class="fas fa-folder"></i> Categoria</span>
                    <span class="av-info-value"><?= htmlspecialchars($chamado['categoria_nome']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($chamado['tecnico_nome'])): ?>
                <div class="av-info-row">
                    <span class="av-info-label"><i class="fas fa-user-cog"></i> Técnico</span>
                    <span class="av-info-value"><?= htmlspecialchars($chamado['tecnico_nome']) ?></span>
                </div>
                <?php endif; ?>
                <div class="av-info-row">
                    <span class="av-info-label"><i class="fas fa-calendar"></i> Aberto em</span>
                    <span class="av-info-value"><?= date('d/m/Y', strtotime($chamado['data_abertura'])) ?></span>
                </div>
                <?php if (!empty($chamado['data_resolucao'])): ?>
                <div class="av-info-row">
                    <span class="av-info-label"><i class="fas fa-check-circle"></i> Resolvido em</span>
                    <span class="av-info-value"><?= date('d/m/Y', strtotime($chamado['data_resolucao'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Estrelas -->
            <form method="POST" id="formAvaliacao">
                <div class="av-rating-section">
                    <h2>Como foi seu atendimento?</h2>
                    <p>Clique nas estrelas para avaliar</p>
                    <div class="av-stars">
                        <input type="radio" name="nota" id="star5" value="5">
                        <label for="star5" title="Excelente"><i class="fas fa-star"></i></label>
                        <input type="radio" name="nota" id="star4" value="4">
                        <label for="star4" title="Muito Bom"><i class="fas fa-star"></i></label>
                        <input type="radio" name="nota" id="star3" value="3">
                        <label for="star3" title="Bom"><i class="fas fa-star"></i></label>
                        <input type="radio" name="nota" id="star2" value="2">
                        <label for="star2" title="Regular"><i class="fas fa-star"></i></label>
                        <input type="radio" name="nota" id="star1" value="1">
                        <label for="star1" title="Ruim"><i class="fas fa-star"></i></label>
                    </div>
                    <div class="av-rating-text" id="ratingText"></div>
                </div>

                <!-- Comentário -->
                <div class="av-comment-group">
                    <label for="comentario"><i class="fas fa-comment-dots"></i> Deixe um comentário (opcional)</label>
                    <textarea class="av-textarea" name="comentario" id="comentario"
                        placeholder="Conte-nos como foi sua experiência com nosso atendimento..."></textarea>
                </div>

                <button type="submit" class="av-btn" id="btnEnviar" disabled>
                    <i class="fas fa-paper-plane"></i> Enviar Avaliação
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="av-footer">
        <p>Powered by <a href="<?= BASE_URL ?>/portal/"><?= htmlspecialchars($empresaNome) ?></a></p>
    </div>
</div>

<script>
const ratingTexts = {
    1: 'ðŸ˜ž Ruim — Precisa melhorar muito',
    2: 'ðŸ˜ Regular — Abaixo das expectativas',
    3: 'ðŸ™‚ Bom — Atendeu parcialmente',
    4: 'ðŸ˜Š Muito Bom — Quase perfeito',
    5: 'ðŸ¤© Excelente — Superou as expectativas!'
};

document.querySelectorAll('.av-stars input').forEach(input => {
    input.addEventListener('change', function() {
        const val = parseInt(this.value);
        document.getElementById('ratingText').textContent = ratingTexts[val] || '';
        document.getElementById('btnEnviar').disabled = false;
    });
});

document.getElementById('formAvaliacao')?.addEventListener('submit', function(e) {
    const checked = document.querySelector('.av-stars input:checked');
    if (!checked) {
        e.preventDefault();
        alert('Por favor, selecione uma nota de 1 a 5 estrelas.');
        return;
    }
    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
});
</script>

</body>
</html>
