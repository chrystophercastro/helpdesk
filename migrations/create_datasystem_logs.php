<?php
/**
 * Migration: Criar tabela chatbot_datasystem_logs
 * 
 * Armazena payload e retorno de cada chamada à API DataSystem
 * feita pelo chatbot (para debug e auditoria).
 * 
 * Executar: php migrations/create_datasystem_logs.php
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    echo "=== Migration: Chatbot DataSystem Logs ===\n\n";

    $db->query("CREATE TABLE IF NOT EXISTS chatbot_datasystem_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        fonte_id INT UNSIGNED DEFAULT NULL COMMENT 'ID da fonte (chatbot_fontes_dados)',
        fonte_nome VARCHAR(100) DEFAULT NULL,
        method VARCHAR(10) NOT NULL DEFAULT 'GET',
        endpoint VARCHAR(500) NOT NULL COMMENT 'Path + query string chamado',
        payload JSON DEFAULT NULL COMMENT 'Body enviado (POST) ou query params',
        http_code INT UNSIGNED DEFAULT NULL,
        response_body LONGTEXT DEFAULT NULL COMMENT 'Resposta crua da API',
        response_size INT UNSIGNED DEFAULT NULL COMMENT 'Tamanho da resposta em bytes',
        duracao_ms INT UNSIGNED DEFAULT NULL COMMENT 'Duração da chamada em ms',
        sucesso TINYINT(1) DEFAULT 1,
        erro VARCHAR(500) DEFAULT NULL COMMENT 'Mensagem de erro, se houver',
        sessao_id INT UNSIGNED DEFAULT NULL COMMENT 'Sessão do chatbot que originou',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fonte (fonte_id),
        INDEX idx_criado (criado_em),
        INDEX idx_sucesso (sucesso),
        INDEX idx_endpoint (endpoint(100))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "   ✅ chatbot_datasystem_logs criada\n";
    echo "\n✅ Migration concluída!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
