<?php
/**
 * Migration: Criar tabelas de Contratos e Fornecedores
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// 1) Fornecedores
$db->query("CREATE TABLE IF NOT EXISTS fornecedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20),
    contato_nome VARCHAR(255),
    contato_email VARCHAR(255),
    contato_telefone VARCHAR(20),
    endereco TEXT,
    website VARCHAR(255),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    criado_por INT UNSIGNED,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_nome (nome),
    INDEX idx_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✅ Tabela 'fornecedores' criada!\n";

// 2) Contratos
$db->query("CREATE TABLE IF NOT EXISTS contratos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fornecedor_id INT UNSIGNED NOT NULL,
    numero VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('manutencao','garantia','licenca','suporte','servico','aluguel','outro') DEFAULT 'servico',
    valor DECIMAL(12,2),
    recorrencia ENUM('mensal','trimestral','semestral','anual','unico') DEFAULT 'mensal',
    data_inicio DATE NOT NULL,
    data_fim DATE,
    data_renovacao DATE,
    auto_renovar TINYINT(1) DEFAULT 0,
    alerta_dias INT DEFAULT 30 COMMENT 'Dias antes do vencimento para alertar',
    status ENUM('ativo','vencido','cancelado','renovado') DEFAULT 'ativo',
    arquivo VARCHAR(255) COMMENT 'Caminho do PDF/documento',
    observacoes TEXT,
    criado_por INT UNSIGNED,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_vencimento (data_fim),
    INDEX idx_fornecedor (fornecedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✅ Tabela 'contratos' criada!\n";

// 3) Vínculo contrato-inventário
$db->query("CREATE TABLE IF NOT EXISTS contrato_ativos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT UNSIGNED NOT NULL,
    ativo_id INT UNSIGNED NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (ativo_id) REFERENCES inventario(id) ON DELETE CASCADE,
    UNIQUE KEY uk_contrato_ativo (contrato_id, ativo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✅ Tabela 'contrato_ativos' criada!\n";

echo "\n🎉 Migration de Contratos e Fornecedores concluída!\n";
