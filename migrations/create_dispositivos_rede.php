<?php
/**
 * Migration: Criar tabelas de gestão de rede
 * 
 * Executar: php migrations/create_dispositivos_rede.php
 */
require_once __DIR__ . '/../config/app.php';
$db = Database::getInstance();

// 1) Tabela de dispositivos monitorados
try {
    $db->query("CREATE TABLE IF NOT EXISTS `dispositivos_rede` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `nome` VARCHAR(255) NOT NULL COMMENT 'Nome do dispositivo',
        `ip` VARCHAR(45) NOT NULL COMMENT 'Endereço IP',
        `tipo` ENUM('servidor','computador','impressora','switch','roteador','access_point','camera','firewall','nobreak','outro') NOT NULL DEFAULT 'outro',
        `localizacao` VARCHAR(255) NULL COMMENT 'Local físico',
        `mac_address` VARCHAR(17) NULL COMMENT 'Endereço MAC',
        `observacoes` TEXT NULL,
        `intervalo_ping` INT UNSIGNED NOT NULL DEFAULT 60 COMMENT 'Intervalo de ping em segundos',
        `ultimo_status` ENUM('online','offline','desconhecido') NOT NULL DEFAULT 'desconhecido',
        `ultimo_ping` DATETIME NULL,
        `latencia_ms` DECIMAL(8,2) NULL COMMENT 'Latência em ms',
        `ativo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Monitoramento ativo',
        `notificar_offline` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Enviar alerta quando offline',
        `criado_por` INT UNSIGNED NOT NULL,
        `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_ip` (`ip`),
        FOREIGN KEY (`criado_por`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "OK: Tabela 'dispositivos_rede' criada.\n";
} catch (Exception $e) {
    echo "SKIP: dispositivos_rede - " . $e->getMessage() . "\n";
}

// 2) Histórico de status (para gráficos e relatórios)
try {
    $db->query("CREATE TABLE IF NOT EXISTS `dispositivos_rede_log` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `dispositivo_id` INT UNSIGNED NOT NULL,
        `status` ENUM('online','offline') NOT NULL,
        `latencia_ms` DECIMAL(8,2) NULL,
        `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_dispositivo_data` (`dispositivo_id`, `criado_em`),
        FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_rede`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "OK: Tabela 'dispositivos_rede_log' criada.\n";
} catch (Exception $e) {
    echo "SKIP: dispositivos_rede_log - " . $e->getMessage() . "\n";
}

echo "\nMigration concluída!\n";
