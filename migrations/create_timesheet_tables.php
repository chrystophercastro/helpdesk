<?php
/**
 * Migration: Timesheet
 * Tabelas: timesheet_registros
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

$db->query("CREATE TABLE IF NOT EXISTS timesheet_registros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    chamado_id INT UNSIGNED NULL,
    projeto_id INT UNSIGNED NULL,
    data DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NULL,
    duracao_minutos INT NULL,
    descricao TEXT NULL,
    tipo ENUM('chamado','projeto','interno','reuniao','treinamento','outro') DEFAULT 'chamado',
    status ENUM('em_andamento','concluido','aprovado','rejeitado') DEFAULT 'em_andamento',
    aprovado_por INT UNSIGNED NULL,
    aprovado_em DATETIME NULL,
    custo_hora DECIMAL(10,2) DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (chamado_id) REFERENCES chamados(id) ON DELETE SET NULL,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL,
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_data (usuario_id, data),
    INDEX idx_data (data),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ Tabela 'timesheet_registros' criada!\n";

echo "\n🎉 Migration Timesheet concluída com sucesso!\n";
