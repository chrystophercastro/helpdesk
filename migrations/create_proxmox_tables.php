<?php
/**
 * Migration: Criar tabelas para módulo Proxmox
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// Tabela de servidores Proxmox
$db->query("CREATE TABLE IF NOT EXISTS proxmox_servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    host VARCHAR(255) NOT NULL,
    porta INT DEFAULT 8006,
    usuario VARCHAR(100) NOT NULL,
    senha TEXT NOT NULL,
    realm VARCHAR(50) DEFAULT 'pam',
    token_id VARCHAR(100) DEFAULT NULL,
    token_secret TEXT DEFAULT NULL,
    auth_type ENUM('password','apitoken') DEFAULT 'password',
    verificar_ssl TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    criado_por INT UNSIGNED DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Tabela de log de ações no Proxmox
$db->query("CREATE TABLE IF NOT EXISTS proxmox_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    acao VARCHAR(100) NOT NULL,
    vmid INT DEFAULT NULL,
    vm_nome VARCHAR(255) DEFAULT NULL,
    node VARCHAR(100) DEFAULT NULL,
    detalhes TEXT DEFAULT NULL,
    resultado ENUM('sucesso','erro') DEFAULT 'sucesso',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES proxmox_servers(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Tabelas Proxmox criadas com sucesso!\n";
