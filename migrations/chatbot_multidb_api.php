<?php
/**
 * Migration: Adicionar suporte multi-driver (PostgreSQL, SQL Server, SQLite) + API REST
 * na fonte de dados do chatbot
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

echo "=== Migration: Chatbot Multi-DB + API REST ===\n\n";

$novasConfigs = [
    // Tipo de fonte de dados
    ['chatbot_db_tipo', 'mysql'],           // mysql, pgsql, sqlserver, sqlite, api

    // API REST como fonte de dados
    ['chatbot_api_url', ''],                // URL base da API
    ['chatbot_api_key', ''],                // Token/Key da API
    ['chatbot_api_auth_tipo', 'none'],      // none, bearer, apikey, basic
    ['chatbot_api_auth_header', 'Authorization'],  // Header customizado para apikey
    ['chatbot_api_auth_user', ''],          // Usuário (basic auth)
    ['chatbot_api_auth_pass', ''],          // Senha (basic auth)
    ['chatbot_api_endpoints', ''],          // JSON descrevendo endpoints disponíveis
    ['chatbot_api_descricao', ''],          // Descrição da API para contexto da IA
];

$inseridos = 0;
foreach ($novasConfigs as [$chave, $valor]) {
    $existe = $db->fetch("SELECT chave FROM chatbot_config WHERE chave = ?", [$chave]);
    if (!$existe) {
        $db->insert('chatbot_config', ['chave' => $chave, 'valor' => $valor]);
        echo "  + {$chave} = '{$valor}'\n";
        $inseridos++;
    } else {
        echo "  - {$chave} (já existe)\n";
    }
}

echo "\n✅ {$inseridos} novas configurações inseridas.\n";
echo "=== Migration concluída! ===\n";
