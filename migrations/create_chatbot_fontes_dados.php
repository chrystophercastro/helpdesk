<?php
/**
 * Migration: Criar tabela chatbot_fontes_dados
 * 
 * Permite múltiplas fontes de dados configuráveis para o chatbot.
 * Cada fonte pode ser: MySQL, PostgreSQL, SQL Server, SQLite ou API REST.
 * Todas as fontes ativas são incluídas no contexto da IA simultaneamente.
 * 
 * Executar: php migrations/create_chatbot_fontes_dados.php
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    echo "=== Migration: Chatbot Fontes de Dados Múltiplas ===\n\n";

    // -------------------------------------------------------
    // 1. chatbot_fontes_dados (cada registro = uma fonte)
    // -------------------------------------------------------
    echo "1. Criando chatbot_fontes_dados...\n";
    $db->query("CREATE TABLE IF NOT EXISTS chatbot_fontes_dados (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL COMMENT 'Nome amigável da fonte (ex: ERP Vendas, CRM)',
        alias VARCHAR(50) NOT NULL COMMENT 'Alias curto para a IA referenciar (ex: erp, crm)',
        tipo ENUM('mysql','pgsql','sqlserver','sqlite','api') NOT NULL DEFAULT 'mysql',
        ativo TINYINT(1) DEFAULT 1,
        
        -- Conexão SQL
        db_host VARCHAR(255) DEFAULT NULL,
        db_port VARCHAR(10) DEFAULT NULL,
        db_name VARCHAR(100) DEFAULT NULL,
        db_user VARCHAR(100) DEFAULT NULL,
        db_pass VARCHAR(255) DEFAULT NULL,
        
        -- Configurações SQL
        descricao TEXT DEFAULT NULL COMMENT 'Descrição/contexto para a IA',
        tabelas_permitidas TEXT DEFAULT NULL COMMENT 'Tabelas permitidas (separadas por vírgula, vazio=todas)',
        max_rows INT UNSIGNED DEFAULT 50,
        relacionamentos JSON DEFAULT NULL COMMENT 'Relacionamentos manuais entre tabelas',
        
        -- Configurações API REST
        api_url VARCHAR(500) DEFAULT NULL,
        api_auth_tipo ENUM('none','bearer','apikey','basic') DEFAULT 'none',
        api_key TEXT DEFAULT NULL,
        api_auth_header VARCHAR(100) DEFAULT 'Authorization',
        api_auth_user VARCHAR(100) DEFAULT NULL,
        api_auth_pass VARCHAR(255) DEFAULT NULL,
        api_endpoints JSON DEFAULT NULL COMMENT 'JSON com endpoints disponíveis',
        api_descricao TEXT DEFAULT NULL,
        
        -- Metadados
        ordem INT UNSIGNED DEFAULT 0 COMMENT 'Ordem de prioridade',
        ultimo_teste DATETIME DEFAULT NULL,
        ultimo_teste_ok TINYINT(1) DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        UNIQUE KEY uk_alias (alias)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✅ chatbot_fontes_dados criada\n";

    // -------------------------------------------------------
    // 2. Migrar configuração existente (single source) para a nova tabela
    // -------------------------------------------------------
    echo "\n2. Migrando fonte de dados existente...\n";
    
    // Verificar se já existem fontes na nova tabela
    $existentes = $db->fetchColumn("SELECT COUNT(*) FROM chatbot_fontes_dados");
    
    if ($existentes == 0) {
        // Verificar se há configuração de fonte única
        $dbAtivo = null;
        try {
            $row = $db->fetch("SELECT valor FROM chatbot_config WHERE chave = 'chatbot_db_ativo'");
            $dbAtivo = $row ? $row['valor'] : '0';
        } catch (\Exception $e) { 
            $dbAtivo = '0';
        }
        
        $getConf = function($chave, $default = '') use ($db) {
            try {
                $r = $db->fetch("SELECT valor FROM chatbot_config WHERE chave = ?", [$chave]);
                return $r ? $r['valor'] : $default;
            } catch (\Exception $e) {
                return $default;
            }
        };
        
        $tipo = $getConf('chatbot_db_tipo', 'mysql');
        $host = $getConf('chatbot_db_host');
        $apiUrl = $getConf('chatbot_api_url');
        
        // Se tinha algo configurado, migrar
        if (!empty($host) || !empty($apiUrl)) {
            $dados = [
                'nome' => 'Fonte Principal',
                'alias' => 'principal',
                'tipo' => $tipo,
                'ativo' => (int)$dbAtivo,
                'db_host' => $getConf('chatbot_db_host'),
                'db_port' => $getConf('chatbot_db_port'),
                'db_name' => $getConf('chatbot_db_name'),
                'db_user' => $getConf('chatbot_db_user'),
                'db_pass' => $getConf('chatbot_db_pass'),
                'descricao' => $getConf('chatbot_db_descricao'),
                'tabelas_permitidas' => $getConf('chatbot_db_tabelas_permitidas'),
                'max_rows' => (int)$getConf('chatbot_db_max_rows', '50'),
                'relacionamentos' => $getConf('chatbot_db_relacionamentos', '[]') ?: '[]',
                'api_url' => $getConf('chatbot_api_url'),
                'api_auth_tipo' => $getConf('chatbot_api_auth_tipo', 'none'),
                'api_key' => $getConf('chatbot_api_key'),
                'api_auth_header' => $getConf('chatbot_api_auth_header', 'Authorization'),
                'api_auth_user' => $getConf('chatbot_api_auth_user'),
                'api_auth_pass' => $getConf('chatbot_api_auth_pass'),
                'api_endpoints' => $getConf('chatbot_api_endpoints') ?: null,
                'api_descricao' => $getConf('chatbot_api_descricao'),
                'ordem' => 1,
            ];
            
            $db->insert('chatbot_fontes_dados', $dados);
            echo "   ✅ Fonte principal migrada (tipo: {$tipo})\n";
        } else {
            echo "   - Nenhuma fonte existente para migrar\n";
        }
    } else {
        echo "   - Já existem {$existentes} fontes na tabela (pulando migração)\n";
    }

    // -------------------------------------------------------
    // 3. Garantir config chatbot_db_ativo continua existindo (controle geral)
    // -------------------------------------------------------
    echo "\n3. Verificando configuração geral...\n";
    $db->query(
        "INSERT INTO chatbot_config (chave, valor, descricao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE descricao = VALUES(descricao)",
        ['chatbot_db_multi_fontes', '1', 'Usar sistema de múltiplas fontes de dados (1=sim, 0=não)']
    );
    echo "   ✅ Flag chatbot_db_multi_fontes configurada\n";

    echo "\n✅ Migration concluída com sucesso!\n";
    echo "Tabela criada: chatbot_fontes_dados\n";
    echo "A configuração antiga (single source) foi migrada automaticamente.\n";

} catch (\Exception $e) {
    echo "❌ Erro na migration: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
