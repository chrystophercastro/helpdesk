<?php
/**
 * Migration: Tabelas de Deploy / Publicação em Produção
 */
require_once __DIR__ . '/../app/models/Database.php';

try {
    $db = Database::getInstance();

    // Configurações de deploy (servidores de destino)
    $db->query("CREATE TABLE IF NOT EXISTS deploy_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descricao VARCHAR(255),
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Histórico de deploys
    $db->query("CREATE TABLE IF NOT EXISTS deploy_historico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        tipo ENUM('full','parcial','config','rollback') DEFAULT 'full',
        status ENUM('iniciado','em_progresso','concluido','erro','cancelado') DEFAULT 'iniciado',
        arquivos_total INT DEFAULT 0,
        arquivos_enviados INT DEFAULT 0,
        arquivos_erro INT DEFAULT 0,
        bytes_total BIGINT DEFAULT 0,
        duracao_segundos INT DEFAULT 0,
        versao_origem VARCHAR(20),
        versao_destino VARCHAR(20),
        log_resumo TEXT,
        log_detalhado LONGTEXT,
        iniciado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        finalizado_em DATETIME NULL,
        INDEX idx_status (status),
        INDEX idx_data (iniciado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Configs padrão
    $defaults = [
        ['deploy_method', 'ftp', 'Método de deploy: ftp, sftp, local_copy'],
        ['prod_host', '', 'Hostname/IP do servidor de produção'],
        ['prod_port', '21', 'Porta FTP/SFTP'],
        ['prod_user', '', 'Usuário FTP/SFTP'],
        ['prod_pass', '', 'Senha FTP/SFTP (criptografada)'],
        ['prod_path', '/helpdesk', 'Caminho remoto no servidor de produção'],
        ['prod_url', '', 'URL da produção (ex: https://ti.texascenter.com.br/helpdesk)'],
        ['prod_db_host', '', 'Host do banco de produção'],
        ['prod_db_name', '', 'Nome do banco de produção'],
        ['prod_db_user', '', 'Usuário do banco de produção'],
        ['prod_db_pass', '', 'Senha do banco de produção (criptografada)'],
        ['prod_base_url', '/helpdesk', 'BASE_URL na produção'],
        ['exclude_patterns', '.git,*.log,node_modules,.venv,storage/logs/*,build,dist,*.bat,*.py,*.iss,.env', 'Padrões de exclusão (separados por vírgula)'],
        ['config_overrides', '{}', 'JSON com overrides de config para produção (ex: DB_HOST, BASE_URL)'],
        ['pre_deploy_backup', '1', 'Fazer backup antes de deploy (1=sim, 0=não)'],
        ['auto_migrations', '1', 'Executar migrations após deploy (1=sim, 0=não)'],
        ['notify_deploy', '1', 'Notificar após deploy (1=sim, 0=não)'],
    ];

    foreach ($defaults as $d) {
        $db->query("INSERT IGNORE INTO deploy_config (chave, valor, descricao) VALUES (?, ?, ?)", $d);
    }

    echo "✅ Tabelas de deploy criadas com sucesso!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
