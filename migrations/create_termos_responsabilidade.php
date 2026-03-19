<?php
/**
 * Migration: Create termos_responsabilidade table for digital signature terms
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// 1) Create table
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS `termos_responsabilidade` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `codigo` VARCHAR(30) NOT NULL UNIQUE,
            `ativo_id` INT UNSIGNED NOT NULL,
            `usuario_nome` VARCHAR(200) NOT NULL COMMENT 'Nome do usuário que recebe o ativo',
            `usuario_cargo` VARCHAR(150) DEFAULT NULL,
            `usuario_departamento` VARCHAR(150) DEFAULT NULL,
            `usuario_telefone` VARCHAR(20) DEFAULT NULL,
            `usuario_email` VARCHAR(200) DEFAULT NULL,
            `tecnico_id` INT UNSIGNED DEFAULT NULL COMMENT 'Técnico/gestor que entrega',
            `tecnico_assinatura` LONGTEXT DEFAULT NULL COMMENT 'Base64 da assinatura do técnico',
            `usuario_assinatura` LONGTEXT DEFAULT NULL COMMENT 'Base64 da assinatura do usuário',
            `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token para link público de assinatura',
            `condicoes` TEXT DEFAULT NULL COMMENT 'Condições/observações do termo',
            `status` ENUM('pendente','assinado','cancelado') NOT NULL DEFAULT 'pendente',
            `data_entrega` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `data_assinatura_tecnico` DATETIME DEFAULT NULL,
            `data_assinatura_usuario` DATETIME DEFAULT NULL,
            `ip_assinatura_usuario` VARCHAR(45) DEFAULT NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_termo_ativo` (`ativo_id`),
            INDEX `idx_termo_status` (`status`),
            INDEX `idx_termo_token` (`token`),
            CONSTRAINT `fk_termo_ativo` FOREIGN KEY (`ativo_id`) REFERENCES `inventario`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_termo_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "OK: Created termos_responsabilidade table\n";
} catch (Exception $e) {
    echo "SKIP: " . $e->getMessage() . "\n";
}

// 2) Also add missing fields to inventario if not present
$fieldsToAdd = [
    'data_aquisicao' => "ADD COLUMN `data_aquisicao` DATE DEFAULT NULL AFTER `especificacoes`",
    'valor_aquisicao' => "ADD COLUMN `valor_aquisicao` DECIMAL(12,2) DEFAULT NULL AFTER `data_aquisicao`",
    'garantia_ate' => "ADD COLUMN `garantia_ate` DATE DEFAULT NULL AFTER `valor_aquisicao`",
    'observacoes' => "ADD COLUMN `observacoes` TEXT DEFAULT NULL AFTER `garantia_ate`"
];

foreach ($fieldsToAdd as $col => $alterDef) {
    try {
        $cols = $db->fetchAll("SHOW COLUMNS FROM inventario");
        $exists = false;
        foreach ($cols as $c) {
            if ($c['Field'] === $col) { $exists = true; break; }
        }
        if (!$exists) {
            $db->query("ALTER TABLE inventario $alterDef");
            echo "OK: Added column $col to inventario\n";
        } else {
            echo "SKIP: Column $col already exists\n";
        }
    } catch (Exception $e) {
        echo "SKIP $col: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration complete!\n";
