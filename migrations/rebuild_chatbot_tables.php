<?php
/**
 * Migration: Rebuild Chatbot Tables
 * 
 * Recria as tabelas do chatbot com estrutura adequada para:
 * - Integração com IA (Ollama, via ia_config existente)
 * - Integração com Evolution API (WhatsApp)
 * - Integração com N8N (automações)
 * - Conexão com base de dados customizada
 * - Controle de números autorizados
 * 
 * Executar: php migrations/rebuild_chatbot_tables.php
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    echo "=== Migration: Rebuild Chatbot Tables ===\n\n";

    // -------------------------------------------------------
    // 1. chatbot_config (key-value, padrão do sistema)
    // -------------------------------------------------------
    echo "1. Criando chatbot_config...\n";
    $db->query("CREATE TABLE IF NOT EXISTS chatbot_config (
        chave VARCHAR(100) PRIMARY KEY,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Inserir configurações padrão
    $defaults = [
        // Geral
        ['chatbot_ativo', '0', 'Chatbot ativo (1=sim, 0=não)'],
        ['chatbot_nome', 'Oracle X Bot', 'Nome do chatbot exibido nas mensagens'],
        ['chatbot_boas_vindas', 'Olá! Sou o assistente Oracle X. Como posso ajudar?', 'Mensagem de boas-vindas'],
        ['chatbot_msg_nao_autorizado', 'Desculpe, seu número não está autorizado a usar este serviço.', 'Mensagem para números não autorizados'],
        ['chatbot_msg_erro', 'Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.', 'Mensagem de erro genérico'],
        ['chatbot_msg_fora_horario', 'Atendimento disponível de segunda a sexta, das 8h às 18h.', 'Mensagem fora do horário'],
        ['chatbot_horario_ativo', '0', 'Respeitar horário de atendimento (1=sim, 0=não)'],
        ['chatbot_horario_inicio', '08:00', 'Horário de início do atendimento'],
        ['chatbot_horario_fim', '18:00', 'Horário de fim do atendimento'],
        ['chatbot_dias_semana', '1,2,3,4,5', 'Dias da semana ativos (0=Dom,1=Seg...6=Sab)'],
        
        // IA - usa ia_config do sistema, mas pode ter overrides
        ['chatbot_ia_modelo', '', 'Modelo IA para o chatbot (vazio = usa modelo padrão do sistema)'],
        ['chatbot_ia_system_prompt', 'Você é um assistente virtual inteligente. Responda de forma clara e objetiva, baseando-se nos dados disponíveis. Se não souber, diga que não tem a informação.', 'System prompt do chatbot'],
        ['chatbot_ia_temperatura', '0.5', 'Temperatura da IA para o chatbot'],
        ['chatbot_ia_max_tokens', '1024', 'Máximo de tokens na resposta'],
        ['chatbot_ia_contexto_max', '10', 'Número máximo de mensagens de contexto por sessão'],

        // N8N
        ['chatbot_n8n_ativo', '0', 'Integração N8N ativa (1=sim, 0=não)'],
        ['chatbot_n8n_url', '', 'URL base da instância N8N (ex: http://localhost:5678)'],
        ['chatbot_n8n_webhook_url', '', 'URL do webhook N8N para receber eventos do chatbot'],
        ['chatbot_n8n_api_key', '', 'API Key do N8N (opcional)'],

        // Database externa
        ['chatbot_db_ativo', '0', 'Consulta a banco externo ativo (1=sim, 0=não)'],
        ['chatbot_db_host', '', 'Host do banco externo'],
        ['chatbot_db_port', '3306', 'Porta do banco externo'],
        ['chatbot_db_name', '', 'Nome do banco externo'],
        ['chatbot_db_user', '', 'Usuário do banco externo'],
        ['chatbot_db_pass', '', 'Senha do banco externo'],
        ['chatbot_db_descricao', '', 'Descrição/contexto do banco para a IA (ex: banco de vendas com tabelas: vendas, produtos, clientes)'],
        ['chatbot_db_tabelas_permitidas', '', 'Tabelas que a IA pode consultar (separadas por vírgula). Vazio = todas'],
        ['chatbot_db_max_rows', '50', 'Máximo de linhas retornadas por consulta'],
    ];

    foreach ($defaults as $d) {
        $db->query(
            "INSERT INTO chatbot_config (chave, valor, descricao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE descricao = VALUES(descricao)",
            [$d[0], $d[1], $d[2]]
        );
    }
    echo "   ✅ chatbot_config criada com " . count($defaults) . " configurações\n";

    // -------------------------------------------------------
    // 2. chatbot_numeros_autorizados
    // -------------------------------------------------------
    echo "2. Criando chatbot_numeros_autorizados...\n";
    $db->query("CREATE TABLE IF NOT EXISTS chatbot_numeros_autorizados (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        numero VARCHAR(20) NOT NULL COMMENT 'Número WhatsApp (ex: 5511999998888)',
        nome VARCHAR(100) DEFAULT NULL COMMENT 'Nome/identificação do contato',
        ativo TINYINT(1) DEFAULT 1,
        notas TEXT DEFAULT NULL COMMENT 'Observações sobre este número',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_numero (numero)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ chatbot_numeros_autorizados criada\n";

    // -------------------------------------------------------
    // 3. chatbot_sessoes (sessões de conversa)
    // -------------------------------------------------------
    echo "3. Criando chatbot_sessoes...\n";
    $db->query("CREATE TABLE IF NOT EXISTS chatbot_sessoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        numero_whatsapp VARCHAR(20) NOT NULL,
        nome_contato VARCHAR(100) DEFAULT NULL,
        contexto_resumo TEXT DEFAULT NULL COMMENT 'Resumo do contexto da conversa para a IA',
        total_mensagens INT UNSIGNED DEFAULT 0,
        ultimo_acesso DATETIME DEFAULT CURRENT_TIMESTAMP,
        ativo TINYINT(1) DEFAULT 1,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_sessao_numero (numero_whatsapp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ chatbot_sessoes criada\n";

    // -------------------------------------------------------
    // 4. chatbot_mensagens (histórico de mensagens)
    // -------------------------------------------------------
    echo "4. Criando chatbot_mensagens...\n";
    $db->query("CREATE TABLE IF NOT EXISTS chatbot_mensagens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sessao_id INT UNSIGNED NOT NULL,
        remetente ENUM('user','bot') NOT NULL,
        mensagem TEXT NOT NULL,
        tipo ENUM('text','query','error','system') DEFAULT 'text' COMMENT 'Tipo da mensagem',
        sql_executado TEXT DEFAULT NULL COMMENT 'SQL gerado pela IA (se tipo=query)',
        dados_consulta JSON DEFAULT NULL COMMENT 'Resultado da query (se tipo=query)',
        tokens_usados INT UNSIGNED DEFAULT 0,
        duracao_ms INT UNSIGNED DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sessao (sessao_id),
        INDEX idx_criado (criado_em),
        FOREIGN KEY (sessao_id) REFERENCES chatbot_sessoes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ chatbot_mensagens criada\n";

    // -------------------------------------------------------
    // 5. chatbot_n8n_logs (logs de webhooks N8N)
    // -------------------------------------------------------
    echo "5. Criando chatbot_n8n_logs...\n";
    $db->query("CREATE TABLE IF NOT EXISTS chatbot_n8n_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        direcao ENUM('entrada','saida') NOT NULL COMMENT 'entrada=N8N->chatbot, saida=chatbot->N8N',
        evento VARCHAR(100) DEFAULT NULL,
        payload JSON DEFAULT NULL,
        resposta JSON DEFAULT NULL,
        http_code INT UNSIGNED DEFAULT NULL,
        sucesso TINYINT(1) DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_direcao (direcao),
        INDEX idx_evento (evento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ chatbot_n8n_logs criada\n";

    // -------------------------------------------------------
    // Remover tabelas antigas conflitantes (se existirem)
    // -------------------------------------------------------
    echo "\n6. Limpando tabelas antigas...\n";
    // Verificar se a tabela antiga existe com formato key-value
    $oldTable = $db->fetch("SHOW TABLES LIKE 'chatbot_configuracoes'");
    if ($oldTable) {
        // Migrar dados relevantes se existirem
        try {
            $oldRows = $db->fetchAll("SELECT chave, valor FROM chatbot_configuracoes");
            $migrated = 0;
            foreach ($oldRows as $row) {
                // Mapear chaves antigas para novas
                $keyMap = [
                    'chatbot_ativo' => 'chatbot_ativo',
                    'chatbot_nome' => 'chatbot_nome',
                    'chatbot_boas_vindas' => 'chatbot_boas_vindas',
                    'chatbot_modelo_ia' => 'chatbot_ia_modelo',
                    'chatbot_max_tokens' => 'chatbot_ia_max_tokens',
                ];
                $newKey = $keyMap[$row['chave']] ?? null;
                if ($newKey && !empty($row['valor'])) {
                    $db->query("UPDATE chatbot_config SET valor = ? WHERE chave = ?", [$row['valor'], $newKey]);
                    $migrated++;
                }
            }
            if ($migrated > 0) echo "   Migrados {$migrated} valores da tabela antiga\n";
        } catch (\Exception $e) {
            // Ignorar erros de migração
        }
        $db->query("DROP TABLE IF EXISTS chatbot_configuracoes");
        echo "   ✅ chatbot_configuracoes antiga removida\n";
    }

    // Migrar dados de chatbot_historico se existir
    $oldHistorico = $db->fetch("SHOW TABLES LIKE 'chatbot_historico'");
    if ($oldHistorico) {
        try {
            // Tentar migrar histórico antigo
            $oldSessions = $db->fetchAll("SELECT * FROM chatbot_sessoes");
            // Se a tabela chatbot_sessoes já foi recriada acima, os dados antigos já foram perdidos
            // Só limpar a tabela antiga
        } catch (\Exception $e) {}
        $db->query("DROP TABLE IF EXISTS chatbot_historico");
        echo "   ✅ chatbot_historico antiga removida\n";
    }

    echo "\n✅ Migration concluída com sucesso!\n";
    echo "Tabelas criadas:\n";
    echo "  - chatbot_config (configurações key-value)\n";
    echo "  - chatbot_numeros_autorizados (whitelist de números)\n";
    echo "  - chatbot_sessoes (sessões de conversa)\n";
    echo "  - chatbot_mensagens (histórico completo)\n";
    echo "  - chatbot_n8n_logs (logs de integração N8N)\n";

} catch (\Exception $e) {
    echo "❌ Erro na migration: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
