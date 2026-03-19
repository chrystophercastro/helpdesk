<?php
/**
 * Migration: Notificações Internas
 * Tabela para sistema de notificações em tempo real
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    // Tabela de notificações
    $db->query("CREATE TABLE IF NOT EXISTS notificacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        tipo VARCHAR(50) NOT NULL DEFAULT 'info' COMMENT 'info, success, warning, danger, chamado, tarefa, projeto, sistema',
        titulo VARCHAR(255) NOT NULL,
        mensagem TEXT,
        icone VARCHAR(50) DEFAULT 'fa-bell',
        link VARCHAR(500) DEFAULT NULL COMMENT 'URL para redirecionar ao clicar',
        referencia_tipo VARCHAR(50) DEFAULT NULL COMMENT 'chamado, tarefa, projeto, compra, etc',
        referencia_id INT DEFAULT NULL,
        lida TINYINT(1) NOT NULL DEFAULT 0,
        lida_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_usuario_lida (usuario_id, lida),
        INDEX idx_criado (criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'notificacoes' criada!\n";

    // Tabela de preferências de notificação
    $db->query("CREATE TABLE IF NOT EXISTS notificacao_preferencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL UNIQUE,
        chamado_novo TINYINT(1) DEFAULT 1,
        chamado_atribuido TINYINT(1) DEFAULT 1,
        chamado_atualizado TINYINT(1) DEFAULT 1,
        chamado_fechado TINYINT(1) DEFAULT 1,
        tarefa_atribuida TINYINT(1) DEFAULT 1,
        tarefa_concluida TINYINT(1) DEFAULT 1,
        projeto_atualizado TINYINT(1) DEFAULT 1,
        compra_aprovada TINYINT(1) DEFAULT 1,
        sistema TINYINT(1) DEFAULT 1,
        som_ativo TINYINT(1) DEFAULT 1,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'notificacao_preferencias' criada!\n";

    echo "\n🎉 Migration de notificações concluída!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
