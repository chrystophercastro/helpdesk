<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Database.php';
$db = Database::getInstance();
try {
    $db->query("CREATE UNIQUE INDEX idx_sla_cat_pri ON sla (categoria_id, prioridade)");
    echo "Index created!\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
