<?php
/**
 * Migration: Chat Interno
 * Tabelas para sistema de chat em tempo real
 * - Conversas (diretas + canais/grupos)
 * - Mensagens com suporte a anexos e reações
 * - Participantes de conversas
 * - Status online e digitando
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    // ========================================
    // 1. Tabela de conversas
    // ========================================
    $db->query("CREATE TABLE IF NOT EXISTS chat_conversas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('direta','grupo','canal') NOT NULL DEFAULT 'direta',
        nome VARCHAR(100) DEFAULT NULL COMMENT 'Nome do grupo/canal',
        descricao VARCHAR(500) DEFAULT NULL,
        icone VARCHAR(50) DEFAULT 'fa-users',
        cor VARCHAR(7) DEFAULT '#3B82F6',
        criado_por INT UNSIGNED DEFAULT NULL,
        fixada TINYINT(1) NOT NULL DEFAULT 0,
        arquivada TINYINT(1) NOT NULL DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo),
        INDEX idx_atualizado (atualizado_em DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'chat_conversas' criada!\n";

    // ========================================
    // 2. Participantes de conversa
    // ========================================
    $db->query("CREATE TABLE IF NOT EXISTS chat_participantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversa_id INT NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        papel ENUM('membro','admin','dono') NOT NULL DEFAULT 'membro',
        notificacao_mudo TINYINT(1) NOT NULL DEFAULT 0,
        ultima_leitura DATETIME DEFAULT NULL COMMENT 'Timestamp da última leitura',
        entrou_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_conversa_usuario (conversa_id, usuario_id),
        INDEX idx_usuario (usuario_id),
        FOREIGN KEY (conversa_id) REFERENCES chat_conversas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'chat_participantes' criada!\n";

    // ========================================
    // 3. Mensagens
    // ========================================
    $db->query("CREATE TABLE IF NOT EXISTS chat_mensagens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversa_id INT NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        tipo ENUM('texto','imagem','arquivo','sistema') NOT NULL DEFAULT 'texto',
        conteudo TEXT NOT NULL,
        resposta_a INT DEFAULT NULL COMMENT 'ID da mensagem respondida',
        editado TINYINT(1) NOT NULL DEFAULT 0,
        editado_em DATETIME DEFAULT NULL,
        deletado TINYINT(1) NOT NULL DEFAULT 0,
        deletado_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conversa_data (conversa_id, criado_em DESC),
        INDEX idx_usuario (usuario_id),
        FOREIGN KEY (conversa_id) REFERENCES chat_conversas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'chat_mensagens' criada!\n";

    // ========================================
    // 4. Reações em mensagens
    // ========================================
    $db->query("CREATE TABLE IF NOT EXISTS chat_reacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mensagem_id INT NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        emoji VARCHAR(10) NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_msg_user_emoji (mensagem_id, usuario_id, emoji),
        FOREIGN KEY (mensagem_id) REFERENCES chat_mensagens(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'chat_reacoes' criada!\n";

    // ========================================
    // 5. Anexos de mensagens
    // ========================================
    $db->query("CREATE TABLE IF NOT EXISTS chat_anexos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mensagem_id INT NOT NULL,
        nome_original VARCHAR(255) NOT NULL,
        nome_arquivo VARCHAR(255) NOT NULL,
        tamanho INT UNSIGNED NOT NULL DEFAULT 0,
        tipo_mime VARCHAR(100) DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mensagem_id) REFERENCES chat_mensagens(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'chat_anexos' criada!\n";

    // ========================================
    // 6. Status de presença
    // ========================================
    $db->query("CREATE TABLE IF NOT EXISTS chat_presenca (
        usuario_id INT UNSIGNED PRIMARY KEY,
        status ENUM('online','ausente','ocupado','offline') NOT NULL DEFAULT 'offline',
        ultimo_acesso DATETIME DEFAULT CURRENT_TIMESTAMP,
        digitando_em INT DEFAULT NULL COMMENT 'ID da conversa onde está digitando'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'chat_presenca' criada!\n";

    // ========================================
    // 7. Criar canais padrão por departamento
    // ========================================
    $departamentos = $db->fetchAll("SELECT id, nome, sigla, cor, icone FROM departamentos WHERE ativo = 1 ORDER BY ordem");
    
    foreach ($departamentos as $dept) {
        $existe = $db->fetch("SELECT id FROM chat_conversas WHERE tipo = 'canal' AND nome = ?", ['# ' . $dept['sigla']]);
        if (!$existe) {
            $canalId = $db->insert('chat_conversas', [
                'tipo' => 'canal',
                'nome' => '# ' . $dept['sigla'],
                'descricao' => 'Canal do departamento ' . $dept['nome'],
                'icone' => $dept['icone'] ?? 'fa-hashtag',
                'cor' => $dept['cor'] ?? '#3B82F6',
                'criado_por' => null
            ]);
            // Adicionar todos os usuários do departamento
            $usuarios = $db->fetchAll("SELECT id FROM usuarios WHERE ativo = 1 AND (departamento_id = ? OR tipo = 'admin')", [$dept['id']]);
            foreach ($usuarios as $u) {
                $db->insert('chat_participantes', [
                    'conversa_id' => $canalId,
                    'usuario_id' => $u['id'],
                    'papel' => 'membro'
                ]);
            }
            echo "  📢 Canal '# {$dept['sigla']}' criado com " . count($usuarios) . " membros\n";
        }
    }

    // Canal geral
    $canalGeral = $db->fetch("SELECT id FROM chat_conversas WHERE tipo = 'canal' AND nome = '# Geral'");
    if (!$canalGeral) {
        $canalId = $db->insert('chat_conversas', [
            'tipo' => 'canal',
            'nome' => '# Geral',
            'descricao' => 'Canal geral da empresa — todos os colaboradores',
            'icone' => 'fa-globe',
            'cor' => '#3B82F6',
            'criado_por' => null
        ]);
        $todos = $db->fetchAll("SELECT id FROM usuarios WHERE ativo = 1");
        foreach ($todos as $u) {
            $db->insert('chat_participantes', [
                'conversa_id' => $canalId,
                'usuario_id' => $u['id'],
                'papel' => 'membro'
            ]);
        }
        echo "  📢 Canal '# Geral' criado com " . count($todos) . " membros\n";
    }

    // Inserir presença para todos os usuários
    $usuarios = $db->fetchAll("SELECT id FROM usuarios WHERE ativo = 1");
    foreach ($usuarios as $u) {
        $existe = $db->fetch("SELECT usuario_id FROM chat_presenca WHERE usuario_id = ?", [$u['id']]);
        if (!$existe) {
            $db->insert('chat_presenca', ['usuario_id' => $u['id'], 'status' => 'offline']);
        }
    }
    echo "  👤 Presença inicializada para " . count($usuarios) . " usuários\n";

    echo "\n🎉 Migration de Chat Interno concluída!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
