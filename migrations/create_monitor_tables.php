<?php
/**
 * Migration: Monitoring / NOC
 * Tabelas para monitoramento de serviços, uptime e alertas
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    // Serviços monitorados
    $db->query("CREATE TABLE IF NOT EXISTS monitor_servicos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        tipo ENUM('http','https','ping','tcp','dns') NOT NULL DEFAULT 'http',
        host VARCHAR(255) NOT NULL,
        porta INT DEFAULT NULL,
        caminho VARCHAR(255) DEFAULT '/' COMMENT 'Para HTTP/HTTPS',
        esperado VARCHAR(255) DEFAULT NULL COMMENT 'Código HTTP ou string esperada',
        intervalo_seg INT NOT NULL DEFAULT 60 COMMENT 'Intervalo de verificação',
        timeout_seg INT NOT NULL DEFAULT 10,
        status ENUM('online','offline','degradado','desconhecido') NOT NULL DEFAULT 'desconhecido',
        ultimo_check DATETIME DEFAULT NULL,
        ultimo_online DATETIME DEFAULT NULL,
        uptime_percent DECIMAL(5,2) DEFAULT 100.00,
        resposta_ms INT DEFAULT NULL COMMENT 'Último tempo de resposta em ms',
        total_checks INT DEFAULT 0,
        total_falhas INT DEFAULT 0,
        grupo VARCHAR(50) DEFAULT 'Geral',
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        notificar TINYINT(1) NOT NULL DEFAULT 1,
        criado_por INT UNSIGNED DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_grupo (grupo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'monitor_servicos' criada!\n";

    // Histórico de checks
    $db->query("CREATE TABLE IF NOT EXISTS monitor_historico (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        servico_id INT NOT NULL,
        status ENUM('online','offline','degradado') NOT NULL,
        resposta_ms INT DEFAULT NULL,
        codigo_http INT DEFAULT NULL,
        mensagem VARCHAR(500) DEFAULT NULL,
        checado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (servico_id) REFERENCES monitor_servicos(id) ON DELETE CASCADE,
        INDEX idx_servico_data (servico_id, checado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'monitor_historico' criada!\n";

    // Incidentes
    $db->query("CREATE TABLE IF NOT EXISTS monitor_incidentes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        servico_id INT NOT NULL,
        tipo ENUM('outage','degraded','recovery') NOT NULL,
        inicio DATETIME NOT NULL,
        fim DATETIME DEFAULT NULL,
        duracao_seg INT DEFAULT NULL,
        mensagem TEXT,
        resolvido TINYINT(1) DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (servico_id) REFERENCES monitor_servicos(id) ON DELETE CASCADE,
        INDEX idx_servico (servico_id),
        INDEX idx_inicio (inicio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'monitor_incidentes' criada!\n";

    echo "\n🎉 Migration de Monitoring/NOC concluída!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
