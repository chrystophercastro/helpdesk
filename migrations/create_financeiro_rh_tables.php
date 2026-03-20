<?php
/**
 * Migration: Sistema Financeiro + RH (Folha de Pagamento)
 * - Permissões por módulo (per-user)
 * - Colaboradores (CLT/PJ)
 * - Folha de pagamento
 * - Notas fiscais (integração SEFAZ MDF-e)
 * - Boletos/Contas a pagar
 * - Fornecedores
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance()->getConnection();

$queries = [];

// ========================================
// 1. PERMISSÕES POR MÓDULO (per-user)
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS usuario_modulos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    modulo VARCHAR(50) NOT NULL COMMENT 'folha_pagamento, financeiro, etc.',
    acesso ENUM('leitura','escrita','admin') NOT NULL DEFAULT 'leitura',
    concedido_por INT UNSIGNED DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_modulo (usuario_id, modulo),
    KEY idx_modulo (modulo),
    CONSTRAINT fk_um_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_um_concedido FOREIGN KEY (concedido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 2. COLABORADORES (CLT + PJ)
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS colaboradores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED DEFAULT NULL COMMENT 'Se vinculado a um user do sistema',
    
    -- Dados pessoais
    nome_completo VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) DEFAULT NULL,
    rg VARCHAR(20) DEFAULT NULL,
    data_nascimento DATE DEFAULT NULL,
    email VARCHAR(200) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    endereco TEXT DEFAULT NULL,
    
    -- Tipo de contrato
    tipo_contrato ENUM('clt','pj') NOT NULL,
    
    -- Dados CLT
    ctps VARCHAR(30) DEFAULT NULL,
    pis_pasep VARCHAR(20) DEFAULT NULL,
    cargo VARCHAR(100) DEFAULT NULL,
    departamento_id INT UNSIGNED DEFAULT NULL,
    data_admissao DATE DEFAULT NULL,
    data_demissao DATE DEFAULT NULL,
    salario_base DECIMAL(12,2) DEFAULT 0,
    jornada_semanal INT DEFAULT 44 COMMENT 'Horas por semana',
    
    -- Dados PJ
    cnpj VARCHAR(20) DEFAULT NULL,
    razao_social VARCHAR(200) DEFAULT NULL,
    inscricao_municipal VARCHAR(30) DEFAULT NULL,
    inscricao_estadual VARCHAR(30) DEFAULT NULL,
    banco VARCHAR(100) DEFAULT NULL,
    agencia VARCHAR(20) DEFAULT NULL,
    conta VARCHAR(30) DEFAULT NULL,
    pix VARCHAR(100) DEFAULT NULL,
    valor_hora DECIMAL(10,2) DEFAULT NULL,
    valor_mensal DECIMAL(12,2) DEFAULT NULL,
    
    -- Controle
    status ENUM('ativo','inativo','desligado','ferias','licenca') NOT NULL DEFAULT 'ativo',
    observacoes TEXT DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_colab_tipo (tipo_contrato),
    KEY idx_colab_status (status),
    KEY idx_colab_cpf (cpf),
    KEY idx_colab_cnpj (cnpj),
    KEY idx_colab_dept (departamento_id),
    CONSTRAINT fk_colab_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_colab_dept FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 3. FOLHA DE PAGAMENTO (Lançamentos)
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS folha_pagamento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colaborador_id INT UNSIGNED NOT NULL,
    
    -- Período
    competencia VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
    tipo ENUM('mensal','ferias','13_salario','rescisao','adiantamento','bonus') NOT NULL DEFAULT 'mensal',
    
    -- Valores
    salario_bruto DECIMAL(12,2) NOT NULL DEFAULT 0,
    
    -- Descontos CLT
    inss DECIMAL(10,2) DEFAULT 0,
    irrf DECIMAL(10,2) DEFAULT 0,
    vale_transporte DECIMAL(10,2) DEFAULT 0,
    vale_alimentacao DECIMAL(10,2) DEFAULT 0,
    plano_saude DECIMAL(10,2) DEFAULT 0,
    faltas_desconto DECIMAL(10,2) DEFAULT 0,
    outros_descontos DECIMAL(10,2) DEFAULT 0,
    descricao_outros_desc VARCHAR(255) DEFAULT NULL,
    
    -- Adicionais
    horas_extras DECIMAL(10,2) DEFAULT 0,
    adicional_noturno DECIMAL(10,2) DEFAULT 0,
    comissao DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    outros_adicionais DECIMAL(10,2) DEFAULT 0,
    descricao_outros_adic VARCHAR(255) DEFAULT NULL,
    
    -- PJ
    valor_nota DECIMAL(12,2) DEFAULT 0 COMMENT 'Para PJ: valor da NF emitida',
    horas_trabalhadas DECIMAL(8,2) DEFAULT 0 COMMENT 'Para PJ: horas no período',
    
    -- Totais calculados
    total_descontos DECIMAL(12,2) DEFAULT 0,
    total_adicionais DECIMAL(12,2) DEFAULT 0,
    salario_liquido DECIMAL(12,2) NOT NULL DEFAULT 0,
    
    -- FGTS (CLT)
    fgts DECIMAL(10,2) DEFAULT 0,
    
    -- Pagamento
    data_pagamento DATE DEFAULT NULL,
    status ENUM('rascunho','calculado','aprovado','pago','cancelado') NOT NULL DEFAULT 'rascunho',
    forma_pagamento ENUM('pix','transferencia','boleto','dinheiro') DEFAULT 'pix',
    comprovante_url VARCHAR(500) DEFAULT NULL,
    
    -- Observações
    observacoes TEXT DEFAULT NULL,
    
    -- Auditoria
    criado_por INT UNSIGNED DEFAULT NULL,
    aprovado_por INT UNSIGNED DEFAULT NULL,
    aprovado_em DATETIME DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_folha_competencia (competencia),
    KEY idx_folha_status (status),
    KEY idx_folha_colab (colaborador_id),
    CONSTRAINT fk_folha_colab FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    CONSTRAINT fk_folha_criado FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_folha_aprovado FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 4. FORNECEDORES
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS fornecedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(200) NOT NULL,
    nome_fantasia VARCHAR(200) DEFAULT NULL,
    cnpj VARCHAR(20) DEFAULT NULL,
    cpf VARCHAR(14) DEFAULT NULL,
    inscricao_estadual VARCHAR(30) DEFAULT NULL,
    inscricao_municipal VARCHAR(30) DEFAULT NULL,
    
    -- Contato
    email VARCHAR(200) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    contato_nome VARCHAR(100) DEFAULT NULL,
    
    -- Endereço
    cep VARCHAR(10) DEFAULT NULL,
    logradouro VARCHAR(200) DEFAULT NULL,
    numero VARCHAR(20) DEFAULT NULL,
    complemento VARCHAR(100) DEFAULT NULL,
    bairro VARCHAR(100) DEFAULT NULL,
    cidade VARCHAR(100) DEFAULT NULL,
    uf CHAR(2) DEFAULT NULL,
    
    -- Bancários
    banco VARCHAR(100) DEFAULT NULL,
    agencia VARCHAR(20) DEFAULT NULL,
    conta VARCHAR(30) DEFAULT NULL,
    pix VARCHAR(100) DEFAULT NULL,
    
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    observacoes TEXT DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_forn_cnpj (cnpj),
    KEY idx_forn_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 5. NOTAS FISCAIS (NFe/NFSe + Manifesto SEFAZ)
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS notas_fiscais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Tipo
    tipo ENUM('nfe','nfse','nfce','cte') NOT NULL DEFAULT 'nfe' COMMENT 'NFe=mercadoria, NFSe=serviço, NFCe=consumidor, CTe=transporte',
    natureza ENUM('entrada','saida') NOT NULL DEFAULT 'entrada',
    
    -- Dados da NF
    numero VARCHAR(20) DEFAULT NULL,
    serie VARCHAR(5) DEFAULT NULL,
    chave_acesso VARCHAR(50) DEFAULT NULL COMMENT 'Chave de 44 dígitos NFe',
    protocolo_autorizacao VARCHAR(20) DEFAULT NULL,
    data_emissao DATE NOT NULL,
    data_entrada_saida DATE DEFAULT NULL,
    
    -- Emitente
    emitente_cnpj VARCHAR(20) DEFAULT NULL,
    emitente_razao VARCHAR(200) DEFAULT NULL,
    emitente_nome_fantasia VARCHAR(200) DEFAULT NULL,
    emitente_uf CHAR(2) DEFAULT NULL,
    
    -- Destinatário (sua empresa)
    destinatario_cnpj VARCHAR(20) DEFAULT NULL,
    destinatario_razao VARCHAR(200) DEFAULT NULL,
    
    -- Fornecedor vinculado
    fornecedor_id INT UNSIGNED DEFAULT NULL,
    
    -- Valores
    valor_produtos DECIMAL(14,2) DEFAULT 0,
    valor_frete DECIMAL(12,2) DEFAULT 0,
    valor_seguro DECIMAL(12,2) DEFAULT 0,
    valor_desconto DECIMAL(12,2) DEFAULT 0,
    valor_outras_despesas DECIMAL(12,2) DEFAULT 0,
    valor_ipi DECIMAL(12,2) DEFAULT 0,
    valor_icms DECIMAL(12,2) DEFAULT 0,
    valor_icms_st DECIMAL(12,2) DEFAULT 0,
    valor_pis DECIMAL(12,2) DEFAULT 0,
    valor_cofins DECIMAL(12,2) DEFAULT 0,
    valor_iss DECIMAL(12,2) DEFAULT 0 COMMENT 'Para NFSe',
    valor_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    
    -- SEFAZ Manifesto
    manifesto_status ENUM('pendente','ciencia','confirmada','desconhecida','nao_realizada') DEFAULT 'pendente',
    manifesto_data DATETIME DEFAULT NULL,
    manifesto_protocolo VARCHAR(20) DEFAULT NULL,
    manifesto_justificativa TEXT DEFAULT NULL,
    
    -- XML
    xml_conteudo LONGTEXT DEFAULT NULL COMMENT 'XML completo da NFe',
    xml_url VARCHAR(500) DEFAULT NULL,
    danfe_url VARCHAR(500) DEFAULT NULL,
    
    -- Controle
    status ENUM('pendente','escriturada','cancelada','inutilizada','denegada') NOT NULL DEFAULT 'pendente',
    categoria VARCHAR(100) DEFAULT NULL COMMENT 'Classificação interna: material, serviço, combustível, etc.',
    centro_custo VARCHAR(100) DEFAULT NULL,
    departamento_id INT UNSIGNED DEFAULT NULL,
    
    observacoes TEXT DEFAULT NULL,
    criado_por INT UNSIGNED DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_nf_chave (chave_acesso),
    KEY idx_nf_tipo (tipo),
    KEY idx_nf_emitente (emitente_cnpj),
    KEY idx_nf_status (status),
    KEY idx_nf_data (data_emissao),
    KEY idx_nf_manifesto (manifesto_status),
    CONSTRAINT fk_nf_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    CONSTRAINT fk_nf_dept FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL,
    CONSTRAINT fk_nf_criado FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Itens da NF
$queries[] = "CREATE TABLE IF NOT EXISTS notas_fiscais_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_fiscal_id INT UNSIGNED NOT NULL,
    numero_item INT NOT NULL DEFAULT 1,
    
    codigo_produto VARCHAR(60) DEFAULT NULL,
    descricao VARCHAR(500) NOT NULL,
    ncm VARCHAR(10) DEFAULT NULL,
    cfop VARCHAR(10) DEFAULT NULL,
    unidade VARCHAR(10) DEFAULT 'UN',
    quantidade DECIMAL(15,4) NOT NULL DEFAULT 1,
    valor_unitario DECIMAL(14,4) NOT NULL DEFAULT 0,
    valor_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    
    -- Impostos por item
    icms_base DECIMAL(12,2) DEFAULT 0,
    icms_valor DECIMAL(12,2) DEFAULT 0,
    icms_aliquota DECIMAL(5,2) DEFAULT 0,
    ipi_valor DECIMAL(12,2) DEFAULT 0,
    pis_valor DECIMAL(12,2) DEFAULT 0,
    cofins_valor DECIMAL(12,2) DEFAULT 0,
    
    CONSTRAINT fk_nfi_nota FOREIGN KEY (nota_fiscal_id) REFERENCES notas_fiscais(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 6. CONTAS A PAGAR (Boletos + outros)
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS contas_pagar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Vínculo
    nota_fiscal_id INT UNSIGNED DEFAULT NULL,
    fornecedor_id INT UNSIGNED DEFAULT NULL,
    
    -- Identificação
    codigo VARCHAR(30) DEFAULT NULL,
    descricao VARCHAR(300) NOT NULL,
    categoria ENUM('material','servico','aluguel','energia','internet','telefone','agua','impostos','salarios','folha_pj','manutencao','software','equipamento','outros') NOT NULL DEFAULT 'outros',
    centro_custo VARCHAR(100) DEFAULT NULL,
    departamento_id INT UNSIGNED DEFAULT NULL,
    
    -- Valores
    valor_original DECIMAL(14,2) NOT NULL,
    valor_juros DECIMAL(12,2) DEFAULT 0,
    valor_multa DECIMAL(12,2) DEFAULT 0,
    valor_desconto DECIMAL(12,2) DEFAULT 0,
    valor_pago DECIMAL(14,2) DEFAULT NULL,
    
    -- Boleto
    codigo_barras VARCHAR(60) DEFAULT NULL,
    linha_digitavel VARCHAR(60) DEFAULT NULL,
    nosso_numero VARCHAR(30) DEFAULT NULL,
    banco_emissor VARCHAR(100) DEFAULT NULL,
    
    -- Datas
    data_emissao DATE DEFAULT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    
    -- Recorrência
    recorrente TINYINT(1) DEFAULT 0,
    frequencia ENUM('mensal','bimestral','trimestral','semestral','anual') DEFAULT NULL,
    parcela_atual INT DEFAULT NULL,
    total_parcelas INT DEFAULT NULL,
    conta_pai_id INT UNSIGNED DEFAULT NULL COMMENT 'Se é parcela de uma conta parcelada',
    
    -- Forma de pagamento
    forma_pagamento ENUM('boleto','pix','transferencia','debito','cartao','dinheiro') DEFAULT 'boleto',
    comprovante_url VARCHAR(500) DEFAULT NULL,
    
    -- Status
    status ENUM('pendente','parcial','pago','vencido','cancelado','agendado') NOT NULL DEFAULT 'pendente',
    
    observacoes TEXT DEFAULT NULL,
    
    -- Auditoria
    criado_por INT UNSIGNED DEFAULT NULL,
    aprovado_por INT UNSIGNED DEFAULT NULL,
    pago_por INT UNSIGNED DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_cp_vencimento (data_vencimento),
    KEY idx_cp_status (status),
    KEY idx_cp_categoria (categoria),
    KEY idx_cp_fornecedor (fornecedor_id),
    CONSTRAINT fk_cp_nf FOREIGN KEY (nota_fiscal_id) REFERENCES notas_fiscais(id) ON DELETE SET NULL,
    CONSTRAINT fk_cp_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    CONSTRAINT fk_cp_dept FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL,
    CONSTRAINT fk_cp_criado FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_cp_aprovado FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_cp_pago FOREIGN KEY (pago_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_cp_pai FOREIGN KEY (conta_pai_id) REFERENCES contas_pagar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 7. CONFIGURAÇÕES SEFAZ
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS sefaz_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cnpj_empresa VARCHAR(20) NOT NULL,
    razao_social VARCHAR(200) NOT NULL,
    inscricao_estadual VARCHAR(30) DEFAULT NULL,
    uf CHAR(2) NOT NULL DEFAULT 'BA',
    ambiente ENUM('producao','homologacao') NOT NULL DEFAULT 'homologacao',
    
    -- Certificado digital (A1)
    certificado_pfx LONGBLOB DEFAULT NULL,
    certificado_senha VARCHAR(100) DEFAULT NULL,
    certificado_validade DATE DEFAULT NULL,
    
    -- NSU (Número Sequencial Único) p/ manifestação
    ultimo_nsu VARCHAR(20) DEFAULT '0',
    max_nsu VARCHAR(20) DEFAULT '0',
    ultima_consulta DATETIME DEFAULT NULL,
    
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 8. LOG DE EVENTOS SEFAZ
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS sefaz_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('dist_dfe','manifesto','consulta','download') NOT NULL,
    nsu VARCHAR(20) DEFAULT NULL,
    chave_acesso VARCHAR(50) DEFAULT NULL,
    evento VARCHAR(50) DEFAULT NULL,
    request_xml LONGTEXT DEFAULT NULL,
    response_xml LONGTEXT DEFAULT NULL,
    http_code INT DEFAULT NULL,
    sucesso TINYINT(1) DEFAULT 0,
    mensagem TEXT DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_sefaz_log_tipo (tipo),
    KEY idx_sefaz_log_chave (chave_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// 9. HISTÓRICO / AUDITORIA FINANCEIRO
// ========================================
$queries[] = "CREATE TABLE IF NOT EXISTS financeiro_historico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entidade_tipo ENUM('nota_fiscal','conta_pagar','folha_pagamento','colaborador','fornecedor') NOT NULL,
    entidade_id INT UNSIGNED NOT NULL,
    acao VARCHAR(50) NOT NULL COMMENT 'criou, editou, aprovou, pagou, cancelou, manifestou...',
    campo_alterado VARCHAR(100) DEFAULT NULL,
    valor_anterior TEXT DEFAULT NULL,
    valor_novo TEXT DEFAULT NULL,
    usuario_id INT UNSIGNED DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_fh_entidade (entidade_tipo, entidade_id),
    CONSTRAINT fk_fh_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ========================================
// EXECUTAR
// ========================================
echo "=== Migration: Financeiro + RH ===\n\n";

$tabelas = [
    'usuario_modulos', 'colaboradores', 'folha_pagamento', 'fornecedores',
    'notas_fiscais', 'notas_fiscais_itens', 'contas_pagar', 'sefaz_config',
    'sefaz_log', 'financeiro_historico'
];

foreach ($queries as $i => $sql) {
    try {
        $db->exec($sql);
        echo "✅ Tabela '{$tabelas[$i]}' criada\n";
    } catch (PDOException $e) {
        echo "❌ Erro em '{$tabelas[$i]}': " . $e->getMessage() . "\n";
    }
}

// Inserir configurações padrão
echo "\n--- Configurações ---\n";

$configs = [
    ['chave' => 'financeiro_empresa_cnpj', 'valor' => '', 'descricao' => 'CNPJ da empresa para integração SEFAZ'],
    ['chave' => 'financeiro_empresa_razao', 'valor' => '', 'descricao' => 'Razão social da empresa'],
    ['chave' => 'financeiro_empresa_uf', 'valor' => 'BA', 'descricao' => 'UF da empresa'],
    ['chave' => 'financeiro_sefaz_ambiente', 'valor' => 'homologacao', 'descricao' => 'Ambiente SEFAZ: producao ou homologacao'],
    ['chave' => 'financeiro_sefaz_ativo', 'valor' => '0', 'descricao' => 'Integração SEFAZ ativa (0/1)'],
    ['chave' => 'folha_dia_pagamento_clt', 'valor' => '5', 'descricao' => 'Dia do pagamento CLT'],
    ['chave' => 'folha_dia_pagamento_pj', 'valor' => '10', 'descricao' => 'Dia do pagamento PJ'],
    ['chave' => 'folha_inss_teto', 'valor' => '908.86', 'descricao' => 'Teto INSS (valor máx desconto)'],
];

$dbObj = Database::getInstance();
foreach ($configs as $cfg) {
    $existe = $dbObj->fetchColumn("SELECT COUNT(*) FROM configuracoes WHERE chave = ?", [$cfg['chave']]);
    if (!$existe) {
        $dbObj->insert('configuracoes', $cfg);
        echo "✅ Config '{$cfg['chave']}' inserida\n";
    } else {
        echo "⏭️  Config '{$cfg['chave']}' já existe\n";
    }
}

// Dar acesso admin a ambos os módulos
$admins = $dbObj->fetchAll("SELECT id FROM usuarios WHERE tipo = 'admin'");
foreach ($admins as $adm) {
    foreach (['folha_pagamento', 'financeiro'] as $mod) {
        $existe = $dbObj->fetchColumn("SELECT COUNT(*) FROM usuario_modulos WHERE usuario_id = ? AND modulo = ?", [$adm['id'], $mod]);
        if (!$existe) {
            $dbObj->insert('usuario_modulos', [
                'usuario_id' => $adm['id'],
                'modulo' => $mod,
                'acesso' => 'admin',
                'concedido_por' => $adm['id']
            ]);
        }
    }
    echo "✅ Admin #{$adm['id']} com acesso a ambos os módulos\n";
}

echo "\n🎉 Migration concluída!\n";
