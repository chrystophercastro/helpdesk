<?php
/**
 * Migration: Adicionar suporte a templates de API (DataSystem, etc.)
 * 
 * Adiciona coluna api_template em chatbot_fontes_dados para identificar
 * integrações com APIs que precisam de lógica especial (autenticação JWT,
 * paginação customizada, etc.)
 * 
 * Executar: php migrations/add_datasystem_template.php
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    echo "=== Migration: DataSystem API Template ===\n\n";

    // 1. Adicionar coluna api_template
    echo "1. Adicionando coluna api_template...\n";
    try {
        $db->query("ALTER TABLE chatbot_fontes_dados ADD COLUMN api_template VARCHAR(50) DEFAULT NULL COMMENT 'Template de API (datasystem, etc.)' AFTER api_descricao");
        echo "   ✅ Coluna api_template adicionada\n";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ⏭ Coluna api_template já existe\n";
        } else {
            throw $e;
        }
    }

    // 2. Adicionar coluna para cache de token JWT
    echo "2. Adicionando coluna api_token_cache...\n";
    try {
        $db->query("ALTER TABLE chatbot_fontes_dados ADD COLUMN api_token_cache TEXT DEFAULT NULL COMMENT 'Cache de token JWT para APIs com autenticação dinâmica' AFTER api_template");
        echo "   ✅ Coluna api_token_cache adicionada\n";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ⏭ Coluna api_token_cache já existe\n";
        } else {
            throw $e;
        }
    }

    // 3. Adicionar coluna para data de expiração do token
    echo "3. Adicionando coluna api_token_expires...\n";
    try {
        $db->query("ALTER TABLE chatbot_fontes_dados ADD COLUMN api_token_expires DATETIME DEFAULT NULL COMMENT 'Data/hora de expiração do token cache' AFTER api_token_cache");
        echo "   ✅ Coluna api_token_expires adicionada\n";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ⏭ Coluna api_token_expires já existe\n";
        } else {
            throw $e;
        }
    }

    echo "\n✅ Migration concluída com sucesso!\n";
    echo "   Agora você pode criar fontes com api_template='datasystem'\n";
    echo "   Use CNPJ em api_auth_user e HASH em api_key\n";

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
