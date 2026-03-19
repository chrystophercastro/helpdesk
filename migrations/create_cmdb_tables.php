<?php
/**
 * Migration: CMDB (Configuration Management Database)
 * Tabelas: cmdb_categorias, cmdb_itens, cmdb_relacionamentos, cmdb_historico
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// 1. Categorias de CI (Configuration Item)
$db->query("CREATE TABLE IF NOT EXISTS cmdb_categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    icone VARCHAR(50) DEFAULT 'fas fa-cube',
    cor VARCHAR(20) DEFAULT '#3B82F6',
    descricao TEXT NULL,
    pai_id INT UNSIGNED NULL,
    ordem INT DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pai_id) REFERENCES cmdb_categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ Tabela 'cmdb_categorias' criada!\n";

// 2. Itens de Configuração (CI)
$db->query("CREATE TABLE IF NOT EXISTS cmdb_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    identificador VARCHAR(50) NULL UNIQUE,
    categoria_id INT UNSIGNED NULL,
    inventario_id INT UNSIGNED NULL,
    descricao TEXT NULL,
    status ENUM('ativo','inativo','planejado','em_manutencao','aposentado') DEFAULT 'ativo',
    criticidade ENUM('critica','alta','media','baixa') DEFAULT 'media',
    ambiente ENUM('producao','homologacao','desenvolvimento','teste') DEFAULT 'producao',
    versao VARCHAR(50) NULL,
    ip_endereco VARCHAR(45) NULL,
    localizacao VARCHAR(200) NULL,
    responsavel_id INT UNSIGNED NULL,
    fornecedor_id INT UNSIGNED NULL,
    dados_extras JSON NULL,
    criado_por INT UNSIGNED NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES cmdb_categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (inventario_id) REFERENCES inventario(id) ON DELETE SET NULL,
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_criticidade (criticidade),
    INDEX idx_ambiente (ambiente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ Tabela 'cmdb_itens' criada!\n";

// 3. Relacionamentos entre CIs
$db->query("CREATE TABLE IF NOT EXISTS cmdb_relacionamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ci_origem_id INT UNSIGNED NOT NULL,
    ci_destino_id INT UNSIGNED NOT NULL,
    tipo ENUM('depende_de','componente_de','conecta_com','backup_de','executa_em','hospedado_em','monitora') NOT NULL DEFAULT 'depende_de',
    descricao VARCHAR(255) NULL,
    criado_por INT UNSIGNED NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ci_origem_id) REFERENCES cmdb_itens(id) ON DELETE CASCADE,
    FOREIGN KEY (ci_destino_id) REFERENCES cmdb_itens(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rel (ci_origem_id, ci_destino_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ Tabela 'cmdb_relacionamentos' criada!\n";

// 4. Histórico de mudanças nos CIs
$db->query("CREATE TABLE IF NOT EXISTS cmdb_historico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ci_id INT UNSIGNED NOT NULL,
    tipo_mudanca ENUM('criacao','atualizacao','status','relacionamento','exclusao') NOT NULL,
    campo VARCHAR(100) NULL,
    valor_anterior TEXT NULL,
    valor_novo TEXT NULL,
    descricao TEXT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ci_id) REFERENCES cmdb_itens(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ci_hist (ci_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ Tabela 'cmdb_historico' criada!\n";

// Inserir categorias padrão
$categorias = [
    ['Servidores', 'fas fa-server', '#3B82F6', 'Servidores físicos e virtuais', null, 1],
    ['Rede', 'fas fa-network-wired', '#10B981', 'Equipamentos de rede', null, 2],
    ['Aplicações', 'fas fa-window-maximize', '#8B5CF6', 'Sistemas e aplicações', null, 3],
    ['Banco de Dados', 'fas fa-database', '#F59E0B', 'Instâncias de banco de dados', null, 4],
    ['Storage', 'fas fa-hdd', '#EC4899', 'Armazenamento e backup', null, 5],
    ['Serviços Cloud', 'fas fa-cloud', '#06B6D4', 'Serviços em nuvem (AWS, Azure, GCP)', null, 6],
    ['Segurança', 'fas fa-shield-alt', '#EF4444', 'Firewalls, IDS/IPS, certificados', null, 7],
    ['Estações de Trabalho', 'fas fa-desktop', '#6366F1', 'Desktops e notebooks', null, 8],
];

$existentes = $db->fetch("SELECT COUNT(*) as total FROM cmdb_categorias");
if ($existentes['total'] == 0) {
    foreach ($categorias as $cat) {
        $db->insert('cmdb_categorias', [
            'nome' => $cat[0],
            'icone' => $cat[1],
            'cor' => $cat[2],
            'descricao' => $cat[3],
            'pai_id' => $cat[4],
            'ordem' => $cat[5]
        ]);
    }
    echo "✅ Categorias padrão inseridas!\n";
}

echo "\n🎉 Migration CMDB concluída com sucesso!\n";
