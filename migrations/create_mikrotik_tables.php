<?php
/**
 * Migration: Criar tabela mikrotik_devices
 * Armazena conexões de dispositivos MikroTik RouterOS
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

$sql = "CREATE TABLE IF NOT EXISTS mikrotik_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    host VARCHAR(255) NOT NULL,
    porta INT DEFAULT 8728,
    usuario VARCHAR(100) NOT NULL,
    senha VARCHAR(500) NOT NULL,
    use_ssl TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_host_porta (host, porta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->query($sql);
    echo "✅ Tabela 'mikrotik_devices' criada com sucesso!\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
