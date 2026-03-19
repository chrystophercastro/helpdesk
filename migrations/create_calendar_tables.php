<?php
/**
 * Migration: Criar tabelas do Calendário
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// Tabela: calendar_eventos
$db->query("CREATE TABLE IF NOT EXISTS calendar_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('evento','reuniao','lembrete','manutencao','deploy','plantao') DEFAULT 'evento',
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME,
    dia_inteiro TINYINT(1) DEFAULT 0,
    cor VARCHAR(7) DEFAULT '#3B82F6',
    local VARCHAR(255),
    recorrencia ENUM('nenhuma','diaria','semanal','mensal','anual') DEFAULT 'nenhuma',
    recorrencia_fim DATE,
    chamado_id INT UNSIGNED,
    projeto_id INT UNSIGNED,
    sprint_id INT UNSIGNED,
    criado_por INT UNSIGNED NOT NULL,
    participantes JSON,
    notificar_antes INT DEFAULT 15 COMMENT 'minutos antes do evento',
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_data (data_inicio, data_fim),
    INDEX idx_tipo (tipo),
    INDEX idx_criado_por (criado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✅ Tabela 'calendar_eventos' criada!\n";

echo "\n🎉 Migration de Calendário concluída!\n";
