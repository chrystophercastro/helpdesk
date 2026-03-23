<?php
// migrations/create_chatbot_tables.php
require_once 'config/database.php';

$db = Database::getInstance();

// Tabela de Configurações do Chatbot (Modelos, System Prompts, etc.)
$sqlConfig = "CREATE TABLE IF NOT EXISTS chatbot_configuracoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    modelo_ia VARCHAR(50) DEFAULT 'llama3',
    url_ollama VARCHAR(255) DEFAULT 'http://localhost:11434',
    system_prompt TEXT,
    temperatura FLOAT DEFAULT 0.7,
    max_tokens INT DEFAULT 2000,
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$db->query($sqlConfig);

// Tabela de Sessões (Contexto por usuário/número)
$sqlSessoes = "CREATE TABLE IF NOT EXISTS chatbot_sessoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_whatsapp VARCHAR(20) NOT NULL,
    nome_contato VARCHAR(100),
    contexto_acumulado LONGTEXT, 
    ultimo_acesso DATETIME DEFAULT CURRENT_TIMESTAMP,
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (numero_whatsapp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$db->query($sqlSessoes);

// Tabela de Histórico de Mensagens
$sqlHistorico = "CREATE TABLE IF NOT EXISTS chatbot_historico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_id INT UNSIGNED NOT NULL,
    remetente ENUM('user', 'bot') NOT NULL,
    mensagem TEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sessao_id) REFERENCES chatbot_sessoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$db->query($sqlHistorico);

// Inserir configuração padrão se não existir
$db->query("INSERT IGNORE INTO chatbot_configuracoes (id, nome, system_prompt) VALUES (1, 'Padrão Oracle X', 'Você é um assistente de helpdesk chamado Oracle X. Responda de forma profissional e baseada no contexto fornecido.')");

echo "Migração do Chatbot realizada com sucesso.";