<?php
/**
 * Migration: Add categoria_id to SLA table for category+priority based SLA
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Database.php';

$db = Database::getInstance();

// 1) Add categoria_id column to sla table
try {
    $db->query("ALTER TABLE sla ADD COLUMN categoria_id INT UNSIGNED DEFAULT NULL AFTER id");
    echo "OK: Added categoria_id column to sla\n";
} catch (Exception $e) {
    echo "SKIP: " . $e->getMessage() . "\n";
}

// 2) Add foreign key
try {
    $db->query("ALTER TABLE sla ADD CONSTRAINT fk_sla_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL");
    echo "OK: Added FK constraint\n";
} catch (Exception $e) {
    echo "SKIP FK: " . $e->getMessage() . "\n";
}

// 3) Update nome where empty
try {
    $db->query("UPDATE sla SET nome = CONCAT('SLA ', UPPER(LEFT(prioridade,1)), SUBSTRING(prioridade,2)) WHERE nome IS NULL OR nome = ''");
    echo "OK: Updated SLA names\n";
} catch (Exception $e) {
    echo "SKIP: " . $e->getMessage() . "\n";
}

// 4) Add unique index on (categoria_id, prioridade)
try {
    $db->query("ALTER TABLE sla DROP INDEX IF EXISTS idx_sla_cat_pri");
    $db->query("CREATE UNIQUE INDEX idx_sla_cat_pri ON sla (categoria_id, prioridade)");
    echo "OK: Added unique index (categoria_id, prioridade)\n";
} catch (Exception $e) {
    echo "SKIP Index: " . $e->getMessage() . "\n";
}

// 5) Show current data
echo "\n=== Current SLA Data ===\n";
$rows = $db->fetchAll("SELECT * FROM sla ORDER BY categoria_id, prioridade");
foreach ($rows as $r) {
    echo "ID={$r['id']} cat_id=" . ($r['categoria_id'] ?? 'NULL') . " pri={$r['prioridade']} resp={$r['tempo_resposta']}min resol={$r['tempo_resolucao']}min nome={$r['nome']}\n";
}

echo "\n=== Categories (chamado) ===\n";
$cats = $db->fetchAll("SELECT id, nome FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
foreach ($cats as $c) {
    echo "ID={$c['id']} {$c['nome']}\n";
}

echo "\nMigration complete!\n";
