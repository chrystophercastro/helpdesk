<?php
/**
 * Migration: Adicionar coluna 'tema' na tabela usuarios
 * Permite cada usuário escolher entre tema light/dark
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    $col = $db->fetch("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'tema'");
    if (!$col) {
        $db->query("ALTER TABLE `usuarios` ADD COLUMN `tema` VARCHAR(10) NOT NULL DEFAULT 'light' AFTER `ativo`");
        echo "✅ Coluna 'tema' adicionada em usuarios\n";
    } else {
        echo "⏭️  Coluna 'tema' já existe\n";
    }

    echo "\n🎉 Migration concluída!\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
