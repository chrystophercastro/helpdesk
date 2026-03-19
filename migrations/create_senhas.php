<?php
/**
 * Migration: Criar tabela de senhas (cofre de senhas)
 * 
 * Executar: php migrations/create_senhas.php
 */
require_once __DIR__ . '/../config/app.php';
$db = Database::getInstance();

// 1) Criar tabela senhas
try {
    $db->query("CREATE TABLE IF NOT EXISTS `senhas` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(255) NOT NULL COMMENT 'Nome do serviço/sistema',
        `categoria` ENUM('servidor','aplicacao','rede','email','banco_dados','cloud','vpn','certificado','api','outro') NOT NULL DEFAULT 'outro',
        `url` TEXT NULL COMMENT 'URL de acesso',
        `usuario` VARCHAR(255) NULL COMMENT 'Login/usuário (criptografado)',
        `senha` TEXT NOT NULL COMMENT 'Senha (criptografada AES-256)',
        `notas` TEXT NULL COMMENT 'Observações adicionais (criptografado)',
        `ip_host` VARCHAR(255) NULL COMMENT 'IP ou hostname',
        `porta` VARCHAR(10) NULL COMMENT 'Porta de acesso',
        `criado_por` INT UNSIGNED NOT NULL,
        `atualizado_por` INT UNSIGNED NULL,
        `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`criado_por`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "OK: Tabela 'senhas' criada.\n";
} catch (Exception $e) {
    echo "SKIP: senhas - " . $e->getMessage() . "\n";
}

// 2) Criar tabela de log de acessos a senhas (auditoria)
try {
    $db->query("CREATE TABLE IF NOT EXISTS `senhas_log` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `senha_id` INT UNSIGNED NOT NULL,
        `usuario_id` INT UNSIGNED NOT NULL,
        `acao` ENUM('visualizar','criar','editar','excluir','copiar') NOT NULL,
        `ip` VARCHAR(45) NULL,
        `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`senha_id`) REFERENCES `senhas`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "OK: Tabela 'senhas_log' criada.\n";
} catch (Exception $e) {
    echo "SKIP: senhas_log - " . $e->getMessage() . "\n";
}

echo "\nMigration concluída!\n";
