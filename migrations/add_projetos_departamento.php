<?php
/**
 * Migration: Adicionar coluna departamento_id na tabela projetos
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // Verificar se a coluna já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM projetos LIKE 'departamento_id'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE projetos ADD COLUMN departamento_id INT UNSIGNED DEFAULT NULL AFTER responsavel_id");
        $pdo->exec("ALTER TABLE projetos ADD INDEX idx_projetos_dept (departamento_id)");
        echo "✅ Coluna departamento_id adicionada à tabela projetos.\n";
    } else {
        echo "ℹ️ Coluna departamento_id já existe.\n";
    }
    echo "🎉 Migração concluída!\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
