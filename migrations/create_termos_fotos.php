<?php
/**
 * Migration: Create termos_fotos table for equipment photo attachments
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    $db->query("
        CREATE TABLE IF NOT EXISTS `termos_fotos` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `termo_id` INT UNSIGNED NOT NULL,
            `nome_original` VARCHAR(255) NOT NULL,
            `nome_arquivo` VARCHAR(255) NOT NULL,
            `tamanho` INT UNSIGNED NOT NULL DEFAULT 0,
            `tipo_mime` VARCHAR(100) DEFAULT NULL,
            `descricao` VARCHAR(255) DEFAULT NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_termos_fotos_termo` (`termo_id`),
            CONSTRAINT `fk_termos_fotos_termo` FOREIGN KEY (`termo_id`) REFERENCES `termos_responsabilidade`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "OK: Created termos_fotos table\n";
} catch (Exception $e) {
    echo "SKIP: " . $e->getMessage() . "\n";
}

// Create uploads directory for termos
$uploadDir = __DIR__ . '/../uploads/termos';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "OK: Created uploads/termos directory\n";
} else {
    echo "SKIP: uploads/termos directory already exists\n";
}

echo "\nMigration complete!\n";
