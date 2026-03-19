<?php
/**
 * Migration: Criar tabelas do módulo Terminal SSH
 * Tabelas: servidores_ssh, ssh_comandos_log, ssh_comandos_salvos
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

echo "=== Migration: Terminal SSH ===\n\n";

// Tabela: servidores_ssh
echo "Criando tabela servidores_ssh...\n";
$db->query("CREATE TABLE IF NOT EXISTS servidores_ssh (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    host VARCHAR(255) NOT NULL,
    porta INT DEFAULT 22,
    usuario VARCHAR(100) NOT NULL,
    metodo_auth ENUM('password', 'key') DEFAULT 'password',
    credencial TEXT COMMENT 'Senha ou chave privada (criptografado)',
    passphrase TEXT COMMENT 'Passphrase da chave (criptografado)',
    grupo VARCHAR(50) DEFAULT 'Geral',
    descricao TEXT,
    sistema_operacional VARCHAR(100),
    tags VARCHAR(255),
    ultimo_acesso DATETIME NULL,
    ultimo_status ENUM('online', 'offline', 'desconhecido') DEFAULT 'desconhecido',
    ativo TINYINT(1) DEFAULT 1,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_host_user (host, porta, usuario),
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  ✓ servidores_ssh\n";

// Tabela: ssh_comandos_log
echo "Criando tabela ssh_comandos_log...\n";
$db->query("CREATE TABLE IF NOT EXISTS ssh_comandos_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servidor_id INT NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    comando TEXT NOT NULL,
    saida LONGTEXT,
    exit_code INT,
    duracao_ms INT,
    executado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servidor_id) REFERENCES servidores_ssh(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_servidor (servidor_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (executado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  ✓ ssh_comandos_log\n";

// Tabela: ssh_comandos_salvos
echo "Criando tabela ssh_comandos_salvos...\n";
$db->query("CREATE TABLE IF NOT EXISTS ssh_comandos_salvos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    comando TEXT NOT NULL,
    descricao VARCHAR(255),
    categoria VARCHAR(50) DEFAULT 'geral',
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  ✓ ssh_comandos_salvos\n";

// Inserir comandos padrão
echo "\nInserindo comandos salvos padrão...\n";
$comandos = [
    ['Info do Sistema', 'uname -a', 'Informações completas do kernel', 'sistema'],
    ['Info do SO', 'cat /etc/os-release | head -5', 'Informações do sistema operacional', 'sistema'],
    ['Uptime', 'uptime', 'Tempo ligado e load average', 'sistema'],
    ['Usuários Logados', 'who', 'Usuários atualmente logados', 'sistema'],
    ['Últimos Logins', 'last -10', 'Últimos 10 logins no sistema', 'sistema'],
    ['Top Processos', 'ps aux --sort=-%mem | head -20', 'Processos por uso de memória', 'sistema'],
    ['Memória', 'free -h', 'Uso de memória RAM e Swap', 'recursos'],
    ['Disco', 'df -h', 'Uso de disco por partição', 'recursos'],
    ['Top CPU', 'top -bn1 | head -25', 'Snapshot do top com processos', 'recursos'],
    ['I/O Stats', 'iostat 2>/dev/null || echo "iostat não disponível"', 'Estatísticas de I/O', 'recursos'],
    ['Interfaces de Rede', 'ip addr show', 'Interfaces e endereços IP', 'rede'],
    ['Portas Abertas', 'ss -tuln', 'Portas TCP/UDP em escuta', 'rede'],
    ['Conexões Ativas', 'ss -tun | head -30', 'Conexões TCP/UDP estabelecidas', 'rede'],
    ['Tabela DNS', 'cat /etc/resolv.conf', 'Configuração DNS', 'rede'],
    ['Rotas', 'ip route show', 'Tabela de roteamento', 'rede'],
    ['Serviços Ativos', 'systemctl list-units --type=service --state=running --no-pager | head -30', 'Serviços systemd rodando', 'servicos'],
    ['Status Apache', 'systemctl status apache2 2>/dev/null || systemctl status httpd 2>/dev/null || echo "Apache não encontrado"', 'Status do Apache', 'servicos'],
    ['Status Nginx', 'systemctl status nginx 2>/dev/null || echo "Nginx não encontrado"', 'Status do Nginx', 'servicos'],
    ['Status MySQL', 'systemctl status mysql 2>/dev/null || systemctl status mariadb 2>/dev/null || echo "MySQL não encontrado"', 'Status do MySQL', 'servicos'],
    ['Docker Containers', 'docker ps 2>/dev/null || echo "Docker não instalado"', 'Containers Docker em execução', 'servicos'],
    ['Syslog', 'tail -30 /var/log/syslog 2>/dev/null || tail -30 /var/log/messages 2>/dev/null || echo "Log não encontrado"', 'Últimas linhas do syslog', 'logs'],
    ['Auth Log', 'tail -20 /var/log/auth.log 2>/dev/null || echo "Auth log não encontrado"', 'Log de autenticação', 'logs'],
    ['Dmesg', 'dmesg | tail -20', 'Mensagens do kernel', 'logs'],
];

$existing = $db->fetch("SELECT COUNT(*) as c FROM ssh_comandos_salvos")['c'] ?? 0;
if ($existing == 0) {
    foreach ($comandos as $cmd) {
        $db->insert('ssh_comandos_salvos', [
            'nome' => $cmd[0],
            'comando' => $cmd[1],
            'descricao' => $cmd[2],
            'categoria' => $cmd[3],
            'criado_por' => null,
        ]);
    }
    echo "  ✓ " . count($comandos) . " comandos inseridos\n";
} else {
    echo "  ⏭ Comandos já existem, pulando\n";
}

echo "\n✅ Migration concluída!\n";
