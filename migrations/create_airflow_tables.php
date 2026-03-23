<?php
/**
 * Migration: Airflow Tables
 * Tabelas locais para integração com Apache Airflow
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// Log de ações executadas pelo painel (trigger, pause, clear)
$db->query("
    CREATE TABLE IF NOT EXISTS airflow_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED DEFAULT NULL,
        acao VARCHAR(50) NOT NULL,
        dag_id VARCHAR(255) DEFAULT NULL,
        detalhes JSON DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_airflow_log_dag (dag_id),
        INDEX idx_airflow_log_user (usuario_id),
        INDEX idx_airflow_log_date (criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Snapshots de métricas (para gráfico de tendência)
$db->query("
    CREATE TABLE IF NOT EXISTS airflow_snapshots (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        dags_total INT DEFAULT 0,
        dags_active INT DEFAULT 0,
        runs_running INT DEFAULT 0,
        runs_failed_24h INT DEFAULT 0,
        import_errors INT DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_airflow_snap_date (criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// DAGs favoritas por usuário
$db->query("
    CREATE TABLE IF NOT EXISTS airflow_favoritos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        dag_id VARCHAR(255) NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_airflow_fav (usuario_id, dag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Garantir que existe a tabela de configurações
$db->query("
    CREATE TABLE IF NOT EXISTS configuracoes_sistema (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor LONGTEXT DEFAULT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✅ Tabelas do Airflow criadas com sucesso!\n";
