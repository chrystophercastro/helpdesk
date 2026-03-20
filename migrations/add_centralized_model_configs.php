<?php
/**
 * Migration: Adicionar modelos centralizados para chamados, e-mail e rede
 * 
 * Adiciona configurações:
 * - modelo_chamados: Modelo para classificação e sugestão de resposta em chamados
 * - modelo_email: Modelo para análise e resumo de e-mails
 * - modelo_rede: Modelo para assistente de rede MikroTik
 * 
 * Todos os módulos agora leem o modelo da tabela ia_config centralizada,
 * configurável via Assistente IA > Gerenciar Modelos (admin only).
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    echo "=== Migration: Modelos Centralizados (Chamados/Email/Rede) ===\n\n";

    // Buscar modelo_padrao atual para usar como default
    $padrao = $db->fetch("SELECT valor FROM ia_config WHERE chave = 'modelo_padrao'");
    $modeloPadrao = $padrao['valor'] ?? 'llama3';

    $newConfigs = [
        ['modelo_chamados', $modeloPadrao, 'Modelo para classificação e sugestão de resposta em chamados'],
        ['modelo_email', $modeloPadrao, 'Modelo para análise e resumo de e-mails (IA Resumir)'],
        ['modelo_rede', $modeloPadrao, 'Modelo para assistente de rede e análise MikroTik'],
    ];

    $inserted = 0;
    foreach ($newConfigs as $c) {
        $db->query(
            "INSERT IGNORE INTO ia_config (chave, valor, descricao) VALUES (?, ?, ?)",
            $c
        );
        $check = $db->fetch("SELECT chave FROM ia_config WHERE chave = ?", [$c[0]]);
        if ($check) {
            echo "✓ Config '{$c[0]}' = '{$c[1]}'\n";
            $inserted++;
        }
    }

    echo "\n=== Migration concluída! ===\n";
    echo "→ {$inserted} novos modelos centralizados adicionados\n";
    echo "→ Configuráveis via Assistente IA > Gerenciar Modelos > Atribuir\n";
    echo "→ Chamados, E-mail e Rede agora usam modelo da config centralizada\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
