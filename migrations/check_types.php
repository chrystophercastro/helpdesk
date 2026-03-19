<?php
require_once __DIR__ . '/../config/app.php';
$db = Database::getInstance();
$cols = $db->fetchAll("SHOW COLUMNS FROM usuarios");
foreach ($cols as $c) {
    if ($c['Field'] === 'id') {
        echo "usuarios.id type: " . $c['Type'] . "\n";
    }
}
// Also check what notificacoes looks like
$cols2 = $db->fetchAll("SHOW COLUMNS FROM notificacoes WHERE Field='usuario_id'");
foreach ($cols2 as $c) {
    echo "notificacoes.usuario_id type: " . $c['Type'] . "\n";
}
