<?php
/**
 * Migration: Criar tabelas de Automações & Rotinas
 */
require_once __DIR__ . '/../config/app.php';

try {
    $db = Database::getInstance();

    // Tabela principal de regras de automação
    $db->query("CREATE TABLE IF NOT EXISTS automacoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(150) NOT NULL,
        descricao TEXT NULL,
        ativo TINYINT(1) DEFAULT 1,

        -- Trigger
        trigger_tipo ENUM(
            'novo_chamado','chamado_sem_resposta','chamado_resolvido_x_dias',
            'sla_prestes_vencer','sla_violado',
            'contrato_vencendo','contrato_vencido',
            'monitor_offline','monitor_lento',
            'agendado_diario','agendado_semanal','agendado_mensal',
            'novo_usuario','inventario_sem_responsavel'
        ) NOT NULL,
        trigger_config JSON NULL COMMENT 'Condições extras: {dias, prioridade, categoria_id, hora_execucao, dia_semana...}',

        -- Ação
        acao_tipo ENUM(
            'atribuir_chamado','mudar_status_chamado','mudar_prioridade_chamado',
            'escalar_chamado','enviar_notificacao','criar_tarefa',
            'fechar_chamado','enviar_email_resumo',
            'atualizar_contrato_status','gerar_relatorio',
            'executar_webhook','limpar_notificacoes_antigas'
        ) NOT NULL,
        acao_config JSON NULL COMMENT 'Parâmetros da ação: {usuario_id, status, prioridade, mensagem, webhook_url...}',

        -- Execução
        ultima_execucao DATETIME NULL,
        proxima_execucao DATETIME NULL,
        total_execucoes INT UNSIGNED DEFAULT 0,
        total_itens_afetados INT UNSIGNED DEFAULT 0,

        -- Meta
        criado_por INT UNSIGNED NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (criado_por) REFERENCES usuarios(id),
        INDEX idx_ativo (ativo),
        INDEX idx_trigger (trigger_tipo),
        INDEX idx_proxima (proxima_execucao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "✅ Tabela 'automacoes' criada\n";

    // Log de execuções
    $db->query("CREATE TABLE IF NOT EXISTS automacao_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        automacao_id INT UNSIGNED NOT NULL,
        executado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('sucesso','erro','parcial') DEFAULT 'sucesso',
        itens_afetados INT UNSIGNED DEFAULT 0,
        detalhes JSON NULL COMMENT 'Lista de IDs afetados e resultados',
        erro_mensagem TEXT NULL,
        duracao_ms INT UNSIGNED DEFAULT 0,

        FOREIGN KEY (automacao_id) REFERENCES automacoes(id) ON DELETE CASCADE,
        INDEX idx_automacao_data (automacao_id, executado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "✅ Tabela 'automacao_logs' criada\n";

    // Inserir automações padrão pré-configuradas
    $admin = $db->fetch("SELECT id FROM usuarios WHERE tipo = 'admin' ORDER BY id LIMIT 1");
    $userId = $admin ? $admin['id'] : $db->fetch("SELECT id FROM usuarios ORDER BY id LIMIT 1")['id'];

    $automacoes = [
        [
            'nome' => 'Fechar chamados resolvidos há 7 dias',
            'descricao' => 'Fecha automaticamente chamados com status "resolvido" há mais de 7 dias sem interação do solicitante.',
            'trigger_tipo' => 'chamado_resolvido_x_dias',
            'trigger_config' => json_encode(['dias' => 7]),
            'acao_tipo' => 'fechar_chamado',
            'acao_config' => json_encode(['mensagem' => 'Chamado fechado automaticamente após 7 dias sem resposta.']),
            'ativo' => 0
        ],
        [
            'nome' => 'Escalar chamados com SLA crítico',
            'descricao' => 'Escala automaticamente chamados quando faltam menos de 30 minutos para violar o SLA.',
            'trigger_tipo' => 'sla_prestes_vencer',
            'trigger_config' => json_encode(['minutos_restantes' => 30]),
            'acao_tipo' => 'escalar_chamado',
            'acao_config' => json_encode(['notificar_gestor' => true, 'mudar_prioridade' => 'urgente']),
            'ativo' => 0
        ],
        [
            'nome' => 'Notificar contratos vencendo em 30 dias',
            'descricao' => 'Envia notificação diária sobre contratos que vencem nos próximos 30 dias.',
            'trigger_tipo' => 'contrato_vencendo',
            'trigger_config' => json_encode(['dias' => 30]),
            'acao_tipo' => 'enviar_notificacao',
            'acao_config' => json_encode(['titulo' => 'Contrato próximo do vencimento', 'destinatarios' => 'gestores']),
            'ativo' => 0
        ],
        [
            'nome' => 'Resumo diário de chamados pendentes',
            'descricao' => 'Gera um resumo diário às 08:00 com todos os chamados pendentes e SLA em risco.',
            'trigger_tipo' => 'agendado_diario',
            'trigger_config' => json_encode(['hora_execucao' => '08:00']),
            'acao_tipo' => 'gerar_relatorio',
            'acao_config' => json_encode(['tipo' => 'chamados_pendentes', 'notificar' => true]),
            'ativo' => 0
        ],
        [
            'nome' => 'Alertar monitores offline',
            'descricao' => 'Notifica a equipe quando um host monitorado fica offline por mais de 5 minutos.',
            'trigger_tipo' => 'monitor_offline',
            'trigger_config' => json_encode(['minutos_offline' => 5]),
            'acao_tipo' => 'enviar_notificacao',
            'acao_config' => json_encode(['titulo' => 'Host offline detectado', 'destinatarios' => 'tecnicos']),
            'ativo' => 0
        ],
        [
            'nome' => 'Atribuir chamados de rede automaticamente',
            'descricao' => 'Atribui automaticamente chamados da categoria "Rede" ao técnico responsável.',
            'trigger_tipo' => 'novo_chamado',
            'trigger_config' => json_encode(['categoria_nome' => 'Rede']),
            'acao_tipo' => 'atribuir_chamado',
            'acao_config' => json_encode(['usuario_id' => null, 'mensagem' => 'Atribuído automaticamente pela regra de automação.']),
            'ativo' => 0
        ],
        [
            'nome' => 'Limpar notificações antigas (90 dias)',
            'descricao' => 'Remove notificações lidas com mais de 90 dias para manter o banco limpo.',
            'trigger_tipo' => 'agendado_semanal',
            'trigger_config' => json_encode(['dia_semana' => 0, 'hora_execucao' => '03:00']),
            'acao_tipo' => 'limpar_notificacoes_antigas',
            'acao_config' => json_encode(['dias' => 90]),
            'ativo' => 0
        ],
        [
            'nome' => 'Chamados sem resposta há 24h',
            'descricao' => 'Notifica o gestor quando um chamado está sem resposta por mais de 24 horas.',
            'trigger_tipo' => 'chamado_sem_resposta',
            'trigger_config' => json_encode(['horas' => 24]),
            'acao_tipo' => 'enviar_notificacao',
            'acao_config' => json_encode(['titulo' => 'Chamado sem resposta há 24h', 'destinatarios' => 'gestores']),
            'ativo' => 0
        ]
    ];

    foreach ($automacoes as $auto) {
        $db->query(
            "INSERT INTO automacoes (nome, descricao, trigger_tipo, trigger_config, acao_tipo, acao_config, ativo, criado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$auto['nome'], $auto['descricao'], $auto['trigger_tipo'], $auto['trigger_config'],
             $auto['acao_tipo'], $auto['acao_config'], $auto['ativo'], $userId]
        );
    }

    echo "✅ " . count($automacoes) . " automações padrão inseridas (desativadas)\n";
    echo "\n🎉 Migration concluída!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
