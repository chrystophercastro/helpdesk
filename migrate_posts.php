<?php
/**
 * Migration: Criar tabelas do módulo Posts/Timeline
 */
require_once __DIR__ . '/config/app.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // 1. Tabela posts
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        departamento_id INT UNSIGNED DEFAULT NULL,
        conteudo LONGTEXT NOT NULL,
        midia JSON DEFAULT NULL COMMENT 'Array de arquivos: [{tipo, url, thumb}]',
        tipo ENUM('texto','foto','video','galeria','comunicado') DEFAULT 'texto',
        fixado TINYINT(1) DEFAULT 0,
        ativo TINYINT(1) DEFAULT 1,
        likes_count INT UNSIGNED DEFAULT 0,
        comentarios_count INT UNSIGNED DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_posts_user (usuario_id),
        INDEX idx_posts_dept (departamento_id),
        INDEX idx_posts_fixado (fixado, criado_em),
        INDEX idx_posts_criado (criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Tabela posts criada.\n";

    // 2. Tabela posts_likes
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts_likes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_post_user (post_id, usuario_id),
        INDEX idx_likes_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Tabela posts_likes criada.\n";

    // 3. Tabela posts_comentarios
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts_comentarios (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        conteudo TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_comments_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Tabela posts_comentarios criada.\n";

    echo "\nMigração Posts/Timeline concluída!\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
