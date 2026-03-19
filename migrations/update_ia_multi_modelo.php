<?php
/**
 * Migration: Atualização IA Multi-Modelo (Smart Routing)
 * 
 * Adiciona configurações para roteamento inteligente de modelos:
 * - modelo_codigo: Modelo dedicado para análise de código e SSH
 * - modelo_analise: Modelo dedicado para análise/relatórios e planejamento de projetos
 * - num_ctx: Janela de contexto do Ollama (substitui contexto_max)
 * 
 * Estas configurações permitem atribuir modelos diferentes para cada tipo de tarefa,
 * otimizando performance e qualidade das respostas da IA.
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    echo "=== Migration: IA Multi-Modelo (Smart Routing) ===\n\n";

    // 1. Novas configurações para roteamento por tarefa
    $newConfigs = [
        ['modelo_codigo', 'llama3', 'Modelo para análise de código, comandos SSH e troubleshooting'],
        ['modelo_analise', 'llama3', 'Modelo para análise, relatórios e planejamento de projetos (planner)'],
        ['num_ctx', '2048', 'Janela de contexto do Ollama em tokens (num_ctx)'],
    ];

    $inserted = 0;
    foreach ($newConfigs as $c) {
        // INSERT IGNORE evita conflito se já existir
        $result = $db->query(
            "INSERT IGNORE INTO ia_config (chave, valor, descricao) VALUES (?, ?, ?)",
            $c
        );
        $check = $db->fetch("SELECT chave FROM ia_config WHERE chave = ?", [$c[0]]);
        if ($check) {
            echo "✓ Config '{$c[0]}' disponível\n";
            $inserted++;
        }
    }

    // 2. Atualizar max_tokens para -1 (sem limite artificial - Ollama gerencia via num_ctx)
    $db->query(
        "UPDATE ia_config SET valor = '-1', descricao = 'Máximo de tokens por resposta (-1 = sem limite, gerenciado pelo num_ctx)' WHERE chave = 'max_tokens' AND valor NOT IN ('-1')",
    );
    echo "✓ max_tokens configurado para -1 (sem limite artificial)\n";

    // 3. Atualizar contexto_max → sincronizar com num_ctx
    $db->query(
        "UPDATE ia_config SET valor = '2048', descricao = 'Janela de contexto máxima (legado - use num_ctx)' WHERE chave = 'contexto_max'",
    );
    echo "✓ contexto_max sincronizado com num_ctx\n";

    echo "\n=== Migration concluída com sucesso! ===\n";
    echo "→ {$inserted} configurações de multi-modelo adicionadas\n";
    echo "→ Sistema agora suporta modelos diferentes por tarefa (chat, rápido, código, análise)\n";
    echo "→ Use a interface de IA > Gerenciar Modelos para atribuir modelos a cada tarefa\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
