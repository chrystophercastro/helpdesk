<?php
/**
 * Migration: Criar tabela notificacoes_whatsapp
 * Separa as notificações WhatsApp da tabela notificacoes (que agora é usada pelo sistema de bell icon)
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    $db->query("CREATE TABLE IF NOT EXISTS notificacoes_whatsapp (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(50) NOT NULL DEFAULT 'whatsapp',
        destinatario_telefone VARCHAR(20) NOT NULL,
        destinatario_nome VARCHAR(150) DEFAULT NULL,
        mensagem TEXT NOT NULL,
        referencia_tipo VARCHAR(30) DEFAULT NULL,
        referencia_id INT UNSIGNED DEFAULT NULL,
        status ENUM('pendente','enviado','erro') NOT NULL DEFAULT 'pendente',
        tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
        erro TEXT DEFAULT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        enviado_em DATETIME DEFAULT NULL,
        INDEX idx_nw_status (status),
        INDEX idx_nw_telefone (destinatario_telefone),
        INDEX idx_nw_referencia (referencia_tipo, referencia_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'notificacoes_whatsapp' criada!\n";

    // Migrar dados existentes se houver (caso a tabela notificacoes antiga ainda tenha o schema WhatsApp)
    try {
        $cols = $db->fetchAll("DESCRIBE notificacoes");
        $colNames = array_column($cols, 'Field');
        if (in_array('destinatario_telefone', $colNames)) {
            $count = $db->fetchColumn("SELECT COUNT(*) FROM notificacoes WHERE destinatario_telefone IS NOT NULL");
            if ($count > 0) {
                $db->query("INSERT INTO notificacoes_whatsapp 
                    (tipo, destinatario_telefone, destinatario_nome, mensagem, referencia_tipo, referencia_id, status, tentativas, erro, criado_em, enviado_em)
                    SELECT tipo, destinatario_telefone, destinatario_nome, mensagem, referencia_tipo, referencia_id, status, tentativas, erro, criado_em, enviado_em 
                    FROM notificacoes WHERE destinatario_telefone IS NOT NULL");
                echo "✅ {$count} registros migrados da tabela antiga!\n";
            }
        }
    } catch (Exception $e) {
        // Tabela antiga já tem schema interno, nada a migrar
    }

    echo "\n🎉 Migration concluída!\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
