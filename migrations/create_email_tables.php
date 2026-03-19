<?php
/**
 * Migration: Criar tabelas do módulo de E-mail
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// Tabela de contas de e-mail por usuário
$db->query("CREATE TABLE IF NOT EXISTS email_contas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nome_conta VARCHAR(100) NOT NULL DEFAULT 'Meu E-mail',
    email VARCHAR(255) NOT NULL,
    imap_host VARCHAR(255) NOT NULL,
    imap_porta INT NOT NULL DEFAULT 993,
    imap_seguranca ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
    smtp_host VARCHAR(255) NOT NULL,
    smtp_porta INT NOT NULL DEFAULT 587,
    smtp_seguranca ENUM('ssl','tls','none') NOT NULL DEFAULT 'tls',
    usuario_email VARCHAR(255) NOT NULL,
    senha_email VARCHAR(500) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_sync DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_email (usuario_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "✅ Tabela email_contas criada com sucesso!\n";
