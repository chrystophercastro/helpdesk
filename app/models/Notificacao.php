<?php
/**
 * Model: Notificacao
 * Integração com WhatsApp via Evolution API
 */
require_once __DIR__ . '/Database.php';

class Notificacao {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Enviar mensagem WhatsApp via Evolution API
     */
    public function sendWhatsApp($telefone, $mensagem) {
        // Buscar configurações
        $config = $this->getConfig();
        
        if (!$config['whatsapp_ativo']) {
            $this->registrar('whatsapp', $telefone, $mensagem, 'pendente', 'WhatsApp desativado');
            return false;
        }

        $url = rtrim($config['evolution_api_url'], '/') . '/message/sendText/' . rawurlencode($config['evolution_instance']);
        
        $payload = json_encode([
            'number' => $telefone,
            'text' => $mensagem
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $config['evolution_api_key']
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $status = ($httpCode >= 200 && $httpCode < 300) ? 'enviado' : 'erro';
        $erroMsg = $status === 'erro' ? ($error ?: $response) : null;

        $this->registrar('whatsapp', $telefone, $mensagem, $status, $erroMsg);

        return $status === 'enviado';
    }

    /**
     * Registrar notificação no banco
     */
    public function registrar($tipo, $telefone, $mensagem, $status = 'pendente', $erro = null, $refTipo = null, $refId = null) {
        return $this->db->insert('notificacoes_whatsapp', [
            'tipo' => $tipo,
            'destinatario_telefone' => $telefone,
            'mensagem' => $mensagem,
            'referencia_tipo' => $refTipo,
            'referencia_id' => $refId,
            'status' => $status,
            'erro' => $erro,
            'enviado_em' => $status === 'enviado' ? date('Y-m-d H:i:s') : null
        ]);
    }

    /**
     * Notificar novo chamado
     * Envia mensagem diferente para solicitante e para equipe técnica
     */
    public function notificarNovoChamado($chamado, $solicitante) {
        $config = $this->getConfig();
        $baseUrl = $config['url_sistema'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);

        // Link do portal (para o solicitante consultar)
        $linkPortal = rtrim($baseUrl, '/') . '/portal/';
        // Link interno (para os técnicos acessarem o chamado)
        $linkInterno = rtrim($baseUrl, '/') . '/index.php?page=chamados&action=ver&id=' . ($chamado['id'] ?? '');

        // ── Mensagem para o SOLICITANTE ──
        $msgSolicitante  = "✅ *Chamado Aberto com Sucesso!*\n\n";
        $msgSolicitante .= "Olá, *{$solicitante['nome']}*!\n\n";
        $msgSolicitante .= "Seu chamado foi registrado e nossa equipe de TI já foi notificada.\n\n";
        $msgSolicitante .= "📋 *Código:* {$chamado['codigo']}\n";
        $msgSolicitante .= "📝 *Título:* {$chamado['titulo']}\n";
        $msgSolicitante .= "⚡ *Prioridade:* " . ucfirst($chamado['prioridade']) . "\n";
        $msgSolicitante .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
        $msgSolicitante .= "Para acompanhar o andamento, acesse o portal e informe seu telefone e o código acima:\n";
        $msgSolicitante .= "🔗 {$linkPortal}\n\n";
        $msgSolicitante .= "_Você receberá atualizações por aqui quando houver progresso._";

        // Enviar para o solicitante
        if (!empty($solicitante['telefone'])) {
            $this->sendWhatsApp($solicitante['telefone'], $msgSolicitante);
        }

        // ── Mensagem para a EQUIPE TÉCNICA ──
        $msgTecnico  = "🆕 *Novo Chamado Recebido!*\n\n";
        $msgTecnico .= "📋 *Código:* {$chamado['codigo']}\n";
        $msgTecnico .= "📝 *Título:* {$chamado['titulo']}\n";
        $msgTecnico .= "⚡ *Prioridade:* " . ucfirst($chamado['prioridade']) . "\n";
        $msgTecnico .= "👤 *Solicitante:* {$solicitante['nome']}\n";
        $msgTecnico .= "📞 *Telefone:* {$solicitante['telefone']}\n";
        $msgTecnico .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
        if (!empty($chamado['descricao'])) {
            $desc = mb_strlen($chamado['descricao']) > 200 
                ? mb_substr($chamado['descricao'], 0, 200) . '...' 
                : $chamado['descricao'];
            $msgTecnico .= "📄 *Descrição:* {$desc}\n\n";
        }
        $msgTecnico .= "🔗 *Acessar chamado:*\n{$linkInterno}";

        // Enviar para pessoas do departamento do chamado + admins
        $deptId = !empty($chamado['departamento_id']) ? (int)$chamado['departamento_id'] : 0;
        $this->notificarEquipe($msgTecnico, $deptId);
    }

    /**
     * Notificar atualização de chamado ao solicitante
     */
    public function notificarAtualizacaoChamado($chamado, $campo, $valorNovo) {
        $config = $this->getConfig();
        $baseUrl = $config['url_sistema'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);
        $linkPortal = rtrim($baseUrl, '/') . '/portal/';

        // Labels amigáveis para os campos
        $campoLabels = [
            'status' => 'Status',
            'prioridade' => 'Prioridade',
            'tecnico_id' => 'Técnico Responsável',
            'categoria_id' => 'Categoria',
            'urgencia' => 'Urgência',
            'impacto' => 'Impacto'
        ];
        $campoLabel = $campoLabels[$campo] ?? $campo;

        // Labels amigáveis para o valor
        if ($campo === 'status') {
            $statusLabels = CHAMADO_STATUS;
            $label = $statusLabels[$valorNovo]['label'] ?? $valorNovo;
        } elseif ($campo === 'prioridade') {
            $prioridadeLabels = PRIORIDADES;
            $label = $prioridadeLabels[$valorNovo]['label'] ?? ucfirst($valorNovo);
        } elseif ($campo === 'tecnico_id') {
            $db = Database::getInstance();
            $tec = $db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$valorNovo]);
            $label = $tec['nome'] ?? 'Não atribuído';
        } else {
            $label = ucfirst($valorNovo);
        }

        $msg  = "🔄 *Atualização no seu Chamado*\n\n";
        $msg .= "📋 *Código:* {$chamado['codigo']}\n";
        $msg .= "📝 *Título:* {$chamado['titulo']}\n\n";
        $msg .= "🔧 *{$campoLabel}* alterado para: *{$label}*\n";
        $msg .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
        $msg .= "Acompanhe pelo portal:\n🔗 {$linkPortal}";

        if (!empty($chamado['telefone_solicitante'])) {
            $this->sendWhatsApp($chamado['telefone_solicitante'], $msg);
        }
    }

    /**
     * Notificar comentário em chamado ao solicitante
     * Não notifica se for nota interna
     */
    public function notificarComentarioChamado($chamado, $autorNome, $tipo = 'comentario', $conteudo = '') {
        // Nota interna NÃO notifica o solicitante
        if ($tipo === 'interno') return;

        $config = $this->getConfig();
        $baseUrl = $config['url_sistema'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);
        $linkPortal = rtrim($baseUrl, '/') . '/portal/';

        $msg  = "💬 *Novo Comentário no seu Chamado*\n\n";
        $msg .= "📋 *Código:* {$chamado['codigo']}\n";
        $msg .= "📝 *Título:* {$chamado['titulo']}\n\n";
        $msg .= "👤 *Respondido por:* {$autorNome}\n";
        $msg .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
        if (!empty($conteudo)) {
            $preview = mb_strlen($conteudo) > 300 ? mb_substr($conteudo, 0, 300) . '...' : $conteudo;
            $msg .= "💭 *Mensagem:*\n_{$preview}_\n\n";
        }
        $msg .= "Acesse o portal para ver a resposta completa:\n🔗 {$linkPortal}";

        if (!empty($chamado['telefone_solicitante'])) {
            $this->sendWhatsApp($chamado['telefone_solicitante'], $msg);
        }
    }

    /**
     * Enviar mídia (imagem/documento) via WhatsApp Evolution API
     */
    public function sendWhatsAppMedia($telefone, $filePath, $fileName, $mimeType, $caption = '') {
        $config = $this->getConfig();

        if (!$config['whatsapp_ativo']) {
            return false;
        }

        $isImage = str_starts_with($mimeType, 'image/');
        $endpoint = $isImage ? '/message/sendMedia/' : '/message/sendMedia/';
        $url = rtrim($config['evolution_api_url'], '/') . $endpoint . rawurlencode($config['evolution_instance']);

        // Converter arquivo local para base64
        if (!file_exists($filePath)) return false;
        $base64 = base64_encode(file_get_contents($filePath));

        $payload = json_encode([
            'number' => $telefone,
            'mediatype' => $isImage ? 'image' : 'document',
            'mimetype' => $mimeType,
            'caption' => $caption ?: $fileName,
            'media' => 'data:' . $mimeType . ';base64,' . $base64,
            'fileName' => $fileName
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $config['evolution_api_key']
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $status = ($httpCode >= 200 && $httpCode < 300) ? 'enviado' : 'erro';
        $erroMsg = $status === 'erro' ? ($error ?: $response) : null;

        $this->registrar('whatsapp_media', $telefone, "Arquivo: {$fileName}", $status, $erroMsg);

        return $status === 'enviado';
    }

    /**
     * Notificar criação de projeto
     */
    public function notificarNovoProjeto($projeto, $responsavel) {
        $msg = "🚀 *Novo Projeto Criado*\n\n";
        $msg .= "📋 *Projeto:* {$projeto['nome']}\n";
        $msg .= "👤 *Responsável:* {$responsavel['nome']}\n";
        $msg .= "📅 *Início:* {$projeto['data_inicio']}\n";
        $msg .= "📅 *Prazo:* {$projeto['prazo']}";

        $this->sendWhatsApp($responsavel['telefone'], $msg);
    }

    /**
     * Notificar atualização de tarefa
     */
    public function notificarAtualizacaoTarefa($tarefa, $responsavel) {
        $colunas = KANBAN_COLUNAS;
        $colunaLabel = $colunas[$tarefa['coluna']]['label'] ?? $tarefa['coluna'];

        $msg = "📌 *Tarefa Atualizada*\n\n";
        $msg .= "📝 *Tarefa:* {$tarefa['titulo']}\n";
        $msg .= "📊 *Status:* {$colunaLabel}\n";
        $msg .= "📅 *Data:* " . date('d/m/Y H:i');

        if ($responsavel && !empty($responsavel['telefone'])) {
            $this->sendWhatsApp($responsavel['telefone'], $msg);
        }
    }

    /**
     * Notificar alteração de compra
     */
    public function notificarCompra($compra, $status, $telefone) {
        $statusLabels = COMPRA_STATUS;
        $label = $statusLabels[$status]['label'] ?? $status;

        $msg = "🛒 *Requisição de Compra*\n\n";
        $msg .= "📋 *Código:* {$compra['codigo']}\n";
        $msg .= "📦 *Item:* {$compra['item']}\n";
        $msg .= "📊 *Status:* {$label}\n";
        $msg .= "📅 *Data:* " . date('d/m/Y H:i');

        $this->sendWhatsApp($telefone, $msg);
    }

    /**
     * Notificar transformação de chamado em projeto/sprint/compra
     */
    public function notificarTransformacao($chamado, $tipo, $entidadeId, $nomeEntidade) {
        $config = $this->getConfig();
        $baseUrl = $config['url_sistema'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);
        $linkPortal = rtrim($baseUrl, '/') . '/portal/';

        $icones = [
            'projeto' => '🚀',
            'sprint' => '🏃',
            'compra' => '🛒'
        ];
        $labels = [
            'projeto' => 'Projeto',
            'sprint' => 'Sprint',
            'compra' => 'Requisição de Compra'
        ];

        $icone = $icones[$tipo] ?? '🔄';
        $label = $labels[$tipo] ?? ucfirst($tipo);

        // Mensagem para o solicitante
        $msg  = "{$icone} *Seu Chamado foi promovido!*\n\n";
        $msg .= "Olá! Informamos que seu chamado foi transformado em um(a) *{$label}* para melhor atendimento.\n\n";
        $msg .= "📋 *Chamado:* {$chamado['codigo']}\n";
        $msg .= "📝 *Título:* {$chamado['titulo']}\n\n";
        $msg .= "{$icone} *{$label}:* {$nomeEntidade}\n";
        $msg .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
        $msg .= "Isso significa que sua solicitação está sendo tratada com ainda mais atenção. ";
        $msg .= "Você continuará recebendo atualizações por aqui.\n\n";
        $msg .= "Acompanhe pelo portal:\n🔗 {$linkPortal}";

        if (!empty($chamado['telefone_solicitante'])) {
            $this->sendWhatsApp($chamado['telefone_solicitante'], $msg);
        }

        // Mensagem para a equipe técnica
        $linkInterno = rtrim($baseUrl, '/') . '/index.php?page=';
        if ($tipo === 'projeto') {
            $linkInterno .= 'projetos&action=ver&id=' . $entidadeId;
        } elseif ($tipo === 'sprint') {
            $linkInterno .= 'sprints&acao=ver&id=' . $entidadeId;
        } elseif ($tipo === 'compra') {
            $linkInterno .= 'compras&action=ver&id=' . $entidadeId;
        }

        $msgEquipe  = "{$icone} *Chamado Transformado em {$label}*\n\n";
        $msgEquipe .= "📋 *Chamado:* {$chamado['codigo']} — {$chamado['titulo']}\n";
        $msgEquipe .= "👤 *Solicitante:* {$chamado['solicitante_nome']}\n\n";
        $msgEquipe .= "{$icone} *{$label}:* {$nomeEntidade}\n";
        $msgEquipe .= "📅 *Data:* " . date('d/m/Y H:i') . "\n\n";
        $msgEquipe .= "🔗 *Acessar:*\n{$linkInterno}";

        $this->notificarEquipe($msgEquipe);
    }

    /**
     * Notificar equipe técnica
     */
    private function notificarEquipe($mensagem, $departamentoId = 0) {
        if ($departamentoId) {
            // Usuários do departamento + admins (sempre recebem)
            $tecnicos = $this->db->fetchAll(
                "SELECT telefone FROM usuarios WHERE ativo = 1 AND telefone IS NOT NULL AND telefone != '' AND (tipo = 'admin' OR departamento_id = ?)",
                [$departamentoId]
            );
        } else {
            // Sem departamento: somente admins
            $tecnicos = $this->db->fetchAll(
                "SELECT telefone FROM usuarios WHERE tipo = 'admin' AND ativo = 1 AND telefone IS NOT NULL AND telefone != ''"
            );
        }
        foreach ($tecnicos as $tec) {
            if (!empty($tec['telefone'])) {
                $this->sendWhatsApp($tec['telefone'], $mensagem);
            }
        }
    }

    /**
     * Notificar solicitante para avaliar o atendimento
     * Chamado quando status muda para "resolvido"
     */
    public function notificarAvaliacao($chamado, $solicitanteNome = '') {
        $config = $this->getConfig();
        $baseUrl = $config['url_sistema'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);

        // Gerar token seguro para o link
        $token = hash('sha256', $chamado['codigo'] . $chamado['telefone_solicitante'] . $chamado['id']);

        // Link público de avaliação
        $linkAvaliacao = rtrim($baseUrl, '/') . '/portal/avaliar.php?codigo=' . urlencode($chamado['codigo']) . '&token=' . $token;

        $nome = $solicitanteNome ?: 'Solicitante';

        $msg  = "⭐ *Avalie nosso Atendimento!*\n\n";
        $msg .= "Olá, *{$nome}*!\n\n";
        $msg .= "Seu chamado foi *resolvido* e gostaríamos de saber como foi sua experiência.\n\n";
        $msg .= "📋 *Código:* {$chamado['codigo']}\n";
        $msg .= "📝 *Título:* {$chamado['titulo']}\n";
        $msg .= "📅 *Resolvido em:* " . date('d/m/Y H:i') . "\n\n";
        $msg .= "Por favor, avalie o atendimento clicando no link abaixo:\n\n";
        $msg .= "🔗 {$linkAvaliacao}\n\n";
        $msg .= "Sua opinião é muito importante para melhorarmos nosso serviço! 🙏";

        if (!empty($chamado['telefone_solicitante'])) {
            $this->sendWhatsApp($chamado['telefone_solicitante'], $msg);
        }
    }

    /**
     * Obter configurações
     */
    private function getConfig() {
        $configs = $this->db->fetchAll("SELECT chave, valor FROM configuracoes");
        $result = [];
        foreach ($configs as $c) {
            $result[$c['chave']] = $c['valor'];
        }
        return $result;
    }

    /**
     * Listar notificações
     */
    public function listar($limite = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM notificacoes_whatsapp ORDER BY criado_em DESC LIMIT ?", [$limite]
        );
    }

    /**
     * Reenviar notificação
     */
    public function reenviar($id) {
        $notif = $this->db->fetch("SELECT * FROM notificacoes_whatsapp WHERE id = ?", [$id]);
        if ($notif) {
            return $this->sendWhatsApp($notif['destinatario_telefone'], $notif['mensagem']);
        }
        return false;
    }
}
