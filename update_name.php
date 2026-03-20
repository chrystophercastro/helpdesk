<?php
require_once __DIR__ . '/config/app.php';
$db = Database::getInstance();
$db->update('configuracoes', ['valor' => 'Oracle X'], "chave = ?", ['empresa_nome']);
echo "empresa_nome atualizado para Oracle X\n";
