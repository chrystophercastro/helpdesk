<?php
/**
 * Migration: Adicionar status 'em_teste' às tabelas do IA Dev
 * 
 * Permite que alterações sejam testadas no codebase antes de serem aprovadas.
 * Fluxo: pendente → em_teste → aplicado (aprovado) OU pendente (cancelar teste)
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // 1. Adicionar 'em_teste' ao ENUM de status da tabela ia_dev_alteracoes
    $pdo->exec("ALTER TABLE ia_dev_alteracoes 
        MODIFY COLUMN status ENUM('pendente','em_teste','aprovado','rejeitado','aplicado','revertido') DEFAULT 'pendente'");
    echo "✅ Status 'em_teste' adicionado à tabela ia_dev_alteracoes.\n";

    // 2. Adicionar 'em_teste' ao ENUM de status da tabela ia_dev_arquivos
    $pdo->exec("ALTER TABLE ia_dev_arquivos 
        MODIFY COLUMN status ENUM('pendente','em_teste','aprovado','rejeitado','aplicado') DEFAULT 'pendente'");
    echo "✅ Status 'em_teste' adicionado à tabela ia_dev_arquivos.\n";

    // 3. Adicionar coluna testado_em na tabela ia_dev_alteracoes
    $cols = $pdo->query("SHOW COLUMNS FROM ia_dev_alteracoes LIKE 'testado_em'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE ia_dev_alteracoes ADD COLUMN testado_em DATETIME DEFAULT NULL AFTER aprovado_em");
        echo "✅ Coluna testado_em adicionada à tabela ia_dev_alteracoes.\n";
    } else {
        echo "⏭️ Coluna testado_em já existe.\n";
    }

    echo "\n🎉 Migração em_teste concluída!\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
