<?php
/**
 * Migration: Tabelas do módulo Conexão Remota
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    // ==========================================
    //  Sessões remotas (clientes conectados)
    // ==========================================
    $db->query("CREATE TABLE IF NOT EXISTS remote_sessoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(10) NOT NULL,
        client_id VARCHAR(64) NOT NULL,
        hostname VARCHAR(255) DEFAULT '',
        username VARCHAR(255) DEFAULT '',
        ip_address VARCHAR(45) DEFAULT '',
        ip_local VARCHAR(45) DEFAULT '',
        os_info VARCHAR(255) DEFAULT '',
        resolucao VARCHAR(20) DEFAULT '',
        status ENUM('online','conectado','desconectado') DEFAULT 'online',
        tecnico_id INT UNSIGNED NULL,
        chamado_id INT UNSIGNED NULL,
        inventario_id INT UNSIGNED NULL,
        conectado_em DATETIME NULL,
        desconectado_em DATETIME NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_remote_codigo (codigo),
        INDEX idx_remote_status (status),
        INDEX idx_remote_client (client_id),
        INDEX idx_remote_hostname (hostname),
        CONSTRAINT fk_remote_tecnico FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE SET NULL,
        CONSTRAINT fk_remote_chamado FOREIGN KEY (chamado_id) REFERENCES chamados(id) ON DELETE SET NULL,
        CONSTRAINT fk_remote_inventario FOREIGN KEY (inventario_id) REFERENCES inventario(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'remote_sessoes' criada!\n";

    // ==========================================
    //  Histórico de conexões
    // ==========================================
    $db->query("CREATE TABLE IF NOT EXISTS remote_historico (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sessao_codigo VARCHAR(10) DEFAULT '',
        tecnico_id INT UNSIGNED NOT NULL,
        tecnico_nome VARCHAR(255) DEFAULT '',
        hostname VARCHAR(255) DEFAULT '',
        username VARCHAR(255) DEFAULT '',
        ip_address VARCHAR(45) DEFAULT '',
        chamado_id INT UNSIGNED NULL,
        inventario_id INT UNSIGNED NULL,
        inicio DATETIME NOT NULL,
        fim DATETIME NULL,
        duracao_segundos INT UNSIGNED DEFAULT 0,
        arquivos_enviados INT UNSIGNED DEFAULT 0,
        arquivos_recebidos INT UNSIGNED DEFAULT 0,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_rhist_tecnico (tecnico_id),
        INDEX idx_rhist_inicio (inicio),
        CONSTRAINT fk_rhist_tecnico FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        CONSTRAINT fk_rhist_chamado FOREIGN KEY (chamado_id) REFERENCES chamados(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'remote_historico' criada!\n";

    // ==========================================
    //  Configurações do servidor remoto
    // ==========================================
    $db->query("CREATE TABLE IF NOT EXISTS remote_config (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor VARCHAR(500) DEFAULT '',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Inserir configurações padrão
    $defaults = [
        ['server_port', '8089'],
        ['max_fps', '15'],
        ['default_quality', '50'],
        ['default_scale', '0.6'],
        ['session_timeout', '300'],
        ['require_approval', '1'],
    ];
    foreach ($defaults as [$chave, $valor]) {
        $db->query("INSERT IGNORE INTO remote_config (chave, valor) VALUES (?, ?)", [$chave, $valor]);
    }
    echo "✅ Tabela 'remote_config' criada com configurações padrão!\n";

    echo "\n🎉 Migration Conexão Remota concluída com sucesso!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
