<?php
/**
 * Migration: Criar tabelas para integração GitHub
 * Configuração de contas, repositórios vinculados a projetos, webhooks
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// Tabela: Configuração da conta GitHub (token por usuário ou global)
$sql1 = "CREATE TABLE IF NOT EXISTS github_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    token TEXT NOT NULL,
    username VARCHAR(100) DEFAULT NULL,
    avatar_url VARCHAR(500) DEFAULT NULL,
    escopo VARCHAR(255) DEFAULT 'repo',
    ativo TINYINT(1) DEFAULT 1,
    ultimo_uso DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Tabela: Repositórios vinculados a projetos
$sql2 = "CREATE TABLE IF NOT EXISTS github_repositorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NULL,
    owner VARCHAR(100) NOT NULL,
    repo VARCHAR(200) NOT NULL,
    full_name VARCHAR(300) NOT NULL,
    description TEXT DEFAULT NULL,
    default_branch VARCHAR(100) DEFAULT 'main',
    private TINYINT(1) DEFAULT 0,
    language VARCHAR(50) DEFAULT NULL,
    stars INT DEFAULT 0,
    forks INT DEFAULT 0,
    open_issues INT DEFAULT 0,
    html_url VARCHAR(500) DEFAULT NULL,
    ultimo_sync DATETIME DEFAULT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_repo (owner, repo),
    INDEX idx_projeto (projeto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Tabela: Cache de commits (últimos por repo)
$sql3 = "CREATE TABLE IF NOT EXISTS github_commits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repositorio_id INT NOT NULL,
    sha VARCHAR(40) NOT NULL,
    message TEXT NOT NULL,
    author_name VARCHAR(200) DEFAULT NULL,
    author_email VARCHAR(200) DEFAULT NULL,
    author_avatar VARCHAR(500) DEFAULT NULL,
    author_login VARCHAR(100) DEFAULT NULL,
    commit_date DATETIME NOT NULL,
    additions INT DEFAULT 0,
    deletions INT DEFAULT 0,
    url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sha (repositorio_id, sha),
    INDEX idx_repo_date (repositorio_id, commit_date DESC),
    FOREIGN KEY (repositorio_id) REFERENCES github_repositorios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Tabela: Webhooks recebidos
$sql4 = "CREATE TABLE IF NOT EXISTS github_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repositorio_id INT DEFAULT NULL,
    event_type VARCHAR(50) NOT NULL,
    action VARCHAR(50) DEFAULT NULL,
    payload JSON DEFAULT NULL,
    processed TINYINT(1) DEFAULT 0,
    resultado TEXT DEFAULT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event (event_type, processed),
    INDEX idx_repo (repositorio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$tables = [
    ['github_config', $sql1],
    ['github_repositorios', $sql2],
    ['github_commits', $sql3],
    ['github_webhooks', $sql4],
];

foreach ($tables as [$name, $sql]) {
    try {
        $db->query($sql);
        echo "✅ Tabela '{$name}' criada com sucesso!\n";
    } catch (Exception $e) {
        echo "❌ Erro ao criar '{$name}': " . $e->getMessage() . "\n";
    }
}
