<?php
/**
 * Migration: Tabelas de Inteligência Artificial
 * - ia_conversas: Histórico de conversas com a IA
 * - ia_mensagens: Mensagens individuais de cada conversa
 * - ia_config: Configurações do módulo de IA
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    echo "=== Migration: Tabelas de IA ===\n\n";

    // 1. Tabela de conversas
    $db->query("CREATE TABLE IF NOT EXISTS ia_conversas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        titulo VARCHAR(255) DEFAULT 'Nova Conversa',
        contexto ENUM('geral', 'chamado', 'ssh', 'rede', 'inventario', 'codigo') DEFAULT 'geral',
        contexto_id INT UNSIGNED DEFAULT NULL COMMENT 'ID do chamado/servidor/etc quando contextual',
        modelo VARCHAR(100) DEFAULT 'llama3',
        tokens_total INT UNSIGNED DEFAULT 0,
        ativa TINYINT(1) DEFAULT 1,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_usuario (usuario_id),
        INDEX idx_contexto (contexto, contexto_id),
        INDEX idx_ativa (ativa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabela ia_conversas criada\n";

    // 2. Tabela de mensagens
    $db->query("CREATE TABLE IF NOT EXISTS ia_mensagens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversa_id INT UNSIGNED NOT NULL,
        role ENUM('user', 'assistant', 'system') NOT NULL,
        conteudo TEXT NOT NULL,
        tokens INT UNSIGNED DEFAULT 0,
        duracao_ms INT UNSIGNED DEFAULT 0 COMMENT 'Tempo de resposta da IA',
        modelo VARCHAR(100) DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversa_id) REFERENCES ia_conversas(id) ON DELETE CASCADE,
        INDEX idx_conversa (conversa_id),
        INDEX idx_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabela ia_mensagens criada\n";

    // 3. Tabela de configuração da IA
    $db->query("CREATE TABLE IF NOT EXISTS ia_config (
        chave VARCHAR(100) PRIMARY KEY,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Tabela ia_config criada\n";

    // 4. Inserir configurações padrão
    $configs = [
        ['ollama_url', 'http://172.16.0.183:11434', 'URL do servidor Ollama'],
        ['modelo_padrao', 'llama3', 'Modelo padrão para chat'],
        ['modelo_rapido', 'llama3', 'Modelo para análises rápidas (classificação, etc)'],
        ['temperatura', '0.7', 'Temperatura de geração (0.0-1.0)'],
        ['max_tokens', '2048', 'Máximo de tokens por resposta'],
        ['contexto_max', '8192', 'Janela de contexto máxima'],
        ['system_prompt', 'Você é um assistente de TI especializado chamado HelpDesk IA. Você faz parte de um sistema HelpDesk de suporte técnico. Responda sempre em português do Brasil. Seja direto, técnico e útil. Use formatação markdown quando apropriado.', 'Prompt do sistema padrão'],
        ['system_prompt_ssh', 'Você é um assistente especializado em Linux, servidores e administração de sistemas. Ajude a explicar comandos, interpretar saídas/erros e sugerir soluções. Seja conciso e técnico. Responda em português do Brasil.', 'Prompt para contexto SSH'],
        ['system_prompt_chamados', 'Você é um assistente de suporte técnico de TI. Ajude a classificar chamados, sugerir soluções e redigir respostas profissionais para os usuários. Responda em português do Brasil.', 'Prompt para contexto de chamados'],
        ['habilitado', '1', 'IA habilitada (1) ou desabilitada (0)'],
    ];

    $stmt = null;
    foreach ($configs as $c) {
        $db->query("INSERT IGNORE INTO ia_config (chave, valor, descricao) VALUES (?, ?, ?)", $c);
    }
    echo "✓ Configurações padrão inseridas\n";

    echo "\n=== Migration concluída com sucesso! ===\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
