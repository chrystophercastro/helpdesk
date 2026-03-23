<?php
/**
 * Migration: Criar tabelas do módulo IA Dev (IDE de desenvolvimento por IA)
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // 1. Projetos de desenvolvimento gerenciados pela IA
    $pdo->exec("CREATE TABLE IF NOT EXISTS ia_dev_projetos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        nome VARCHAR(200) NOT NULL,
        descricao TEXT,
        tipo ENUM('interno','externo') DEFAULT 'externo' COMMENT 'interno=Oracle X, externo=projeto standalone',
        stack VARCHAR(200) DEFAULT NULL COMMENT 'php, node, python, react, etc',
        caminho VARCHAR(500) DEFAULT NULL COMMENT 'Caminho relativo para projetos externos',
        status ENUM('ativo','arquivado') DEFAULT 'ativo',
        config JSON DEFAULT NULL COMMENT 'Configurações extras do projeto',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_dev_proj_user (usuario_id),
        INDEX idx_dev_proj_tipo (tipo),
        INDEX idx_dev_proj_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela ia_dev_projetos criada.\n";

    // 2. Arquivos gerados/modificados pela IA
    $pdo->exec("CREATE TABLE IF NOT EXISTS ia_dev_arquivos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        projeto_id INT UNSIGNED NOT NULL,
        conversa_id INT UNSIGNED DEFAULT NULL,
        caminho VARCHAR(500) NOT NULL COMMENT 'Caminho relativo ao projeto',
        conteudo LONGTEXT,
        conteudo_original LONGTEXT DEFAULT NULL COMMENT 'Para diffs em modificações',
        linguagem VARCHAR(50) DEFAULT NULL,
        acao ENUM('criar','modificar','deletar') DEFAULT 'criar',
        versao INT UNSIGNED DEFAULT 1,
        status ENUM('pendente','aprovado','rejeitado','aplicado') DEFAULT 'pendente',
        aplicado_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dev_arq_proj (projeto_id),
        INDEX idx_dev_arq_conv (conversa_id),
        INDEX idx_dev_arq_status (status),
        FOREIGN KEY (projeto_id) REFERENCES ia_dev_projetos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela ia_dev_arquivos criada.\n";

    // 3. Alterações agrupadas (change requests) - usado para Oracle X
    $pdo->exec("CREATE TABLE IF NOT EXISTS ia_dev_alteracoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        projeto_id INT UNSIGNED NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        conversa_id INT UNSIGNED DEFAULT NULL,
        titulo VARCHAR(300) NOT NULL,
        descricao TEXT,
        status ENUM('pendente','aprovado','rejeitado','aplicado','revertido') DEFAULT 'pendente',
        aprovado_por INT UNSIGNED DEFAULT NULL,
        aprovado_em DATETIME DEFAULT NULL,
        aplicado_em DATETIME DEFAULT NULL,
        notas_revisao TEXT DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dev_alt_proj (projeto_id),
        INDEX idx_dev_alt_user (usuario_id),
        INDEX idx_dev_alt_status (status),
        FOREIGN KEY (projeto_id) REFERENCES ia_dev_projetos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela ia_dev_alteracoes criada.\n";

    // 4. Vincular arquivos a alterações
    $pdo->exec("CREATE TABLE IF NOT EXISTS ia_dev_alteracao_arquivos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        alteracao_id INT UNSIGNED NOT NULL,
        arquivo_id INT UNSIGNED NOT NULL,
        FOREIGN KEY (alteracao_id) REFERENCES ia_dev_alteracoes(id) ON DELETE CASCADE,
        FOREIGN KEY (arquivo_id) REFERENCES ia_dev_arquivos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela ia_dev_alteracao_arquivos criada.\n";

    // 5. Criar o projeto Oracle X interno automaticamente
    $exists = $db->fetchColumn("SELECT COUNT(*) FROM ia_dev_projetos WHERE tipo = 'interno' AND nome = 'Oracle X'");
    if (!$exists) {
        $db->insert('ia_dev_projetos', [
            'usuario_id' => 1,
            'nome' => 'Oracle X',
            'descricao' => 'Sistema HelpDesk Oracle X - Desenvolvimento interno. Alterações requerem aprovação antes de ir para produção.',
            'tipo' => 'interno',
            'stack' => 'php,mysql,javascript,css',
            'status' => 'ativo'
        ]);
        echo "✅ Projeto interno 'Oracle X' criado automaticamente.\n";
    }

    echo "\n🎉 Migração IA Dev concluída!\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
