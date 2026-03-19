<?php
/**
 * Migration: Suprimentos de TI (Estoque + Requisições de Compra)
 * 
 * Tabelas:
 *   - suprimento_categorias    (categorias de produtos)
 *   - suprimentos              (produtos/itens de estoque)
 *   - suprimento_movimentacoes (entradas/saídas de estoque)
 *   - suprimento_requisicoes   (requisições de compra)
 *   - suprimento_requisicao_itens (itens de cada requisição)
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    // ===========================
    // 1. Categorias de Suprimentos
    // ===========================
    $db->query("CREATE TABLE IF NOT EXISTS suprimento_categorias (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        cor VARCHAR(7) DEFAULT '#6B7280',
        icone VARCHAR(50) DEFAULT 'fa-box',
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_supri_cat_nome (nome)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'suprimento_categorias' criada!\n";

    // Categorias padrão
    $db->query("INSERT IGNORE INTO suprimento_categorias (nome, descricao, cor, icone) VALUES
        ('Cabos e Conectores', 'Cabos HDMI, USB, rede, energia, etc.', '#3B82F6', 'fa-plug'),
        ('Periféricos', 'Mouse, teclado, headset, webcam, etc.', '#8B5CF6', 'fa-keyboard'),
        ('Toners e Cartuchos', 'Toners, cartuchos, tambores de impressora', '#F59E0B', 'fa-print'),
        ('Armazenamento', 'HD, SSD, pendrives, cartões SD', '#10B981', 'fa-hdd'),
        ('Memória RAM', 'Módulos DDR3, DDR4, DDR5', '#06B6D4', 'fa-memory'),
        ('Fontes e Baterias', 'Fontes de alimentação, baterias, nobreaks', '#EF4444', 'fa-car-battery'),
        ('Rede', 'Patch cords, conectores RJ45, switches, APs', '#6366F1', 'fa-network-wired'),
        ('Ferramentas', 'Chaves, testadores, alicates, multímetro', '#78716C', 'fa-tools'),
        ('Limpeza', 'Álcool isopropílico, flanela, spray, ar comprimido', '#84CC16', 'fa-broom'),
        ('Papelaria', 'Etiquetas, papel A4, fitas adesivas', '#D97706', 'fa-sticky-note'),
        ('Software e Licenças', 'Licenças de software, antivírus, Office', '#EC4899', 'fa-key'),
        ('Outros', 'Itens diversos não categorizados', '#6B7280', 'fa-ellipsis-h')
    ");
    echo "✅ Categorias padrão inseridas!\n";

    // ===========================
    // 2. Suprimentos (Produtos)
    // ===========================
    $db->query("CREATE TABLE IF NOT EXISTS suprimentos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(30) DEFAULT NULL,
        nome VARCHAR(200) NOT NULL,
        descricao TEXT DEFAULT NULL,
        categoria_id INT UNSIGNED DEFAULT NULL,
        unidade VARCHAR(20) NOT NULL DEFAULT 'un',
        marca VARCHAR(100) DEFAULT NULL,
        modelo VARCHAR(100) DEFAULT NULL,
        localizacao VARCHAR(150) DEFAULT NULL,
        estoque_atual INT NOT NULL DEFAULT 0,
        estoque_minimo INT NOT NULL DEFAULT 0,
        estoque_maximo INT DEFAULT NULL,
        preco_unitario DECIMAL(12,2) DEFAULT NULL,
        fornecedor_padrao VARCHAR(200) DEFAULT NULL,
        codigo_fornecedor VARCHAR(100) DEFAULT NULL,
        ncm VARCHAR(15) DEFAULT NULL,
        observacoes TEXT DEFAULT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_por INT UNSIGNED DEFAULT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_supri_codigo (codigo),
        INDEX idx_supri_nome (nome),
        INDEX idx_supri_categoria (categoria_id),
        INDEX idx_supri_estoque (estoque_atual, estoque_minimo),
        CONSTRAINT fk_supri_categoria FOREIGN KEY (categoria_id) REFERENCES suprimento_categorias(id) ON DELETE SET NULL,
        CONSTRAINT fk_supri_criado FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'suprimentos' criada!\n";

    // ===========================
    // 3. Movimentações de Estoque
    // ===========================
    $db->query("CREATE TABLE IF NOT EXISTS suprimento_movimentacoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        suprimento_id INT UNSIGNED NOT NULL,
        tipo ENUM('entrada','saida','ajuste','devolucao') NOT NULL,
        quantidade INT NOT NULL,
        estoque_anterior INT NOT NULL DEFAULT 0,
        estoque_posterior INT NOT NULL DEFAULT 0,
        motivo VARCHAR(255) DEFAULT NULL,
        documento VARCHAR(100) DEFAULT NULL,
        requisicao_id INT UNSIGNED DEFAULT NULL,
        usuario_id INT UNSIGNED DEFAULT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_supri_mov_produto (suprimento_id),
        INDEX idx_supri_mov_tipo (tipo),
        INDEX idx_supri_mov_data (criado_em),
        CONSTRAINT fk_supri_mov_prod FOREIGN KEY (suprimento_id) REFERENCES suprimentos(id) ON DELETE CASCADE,
        CONSTRAINT fk_supri_mov_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'suprimento_movimentacoes' criada!\n";

    // ===========================
    // 4. Requisições de Compra
    // ===========================
    $db->query("CREATE TABLE IF NOT EXISTS suprimento_requisicoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) NOT NULL,
        titulo VARCHAR(200) NOT NULL,
        justificativa TEXT DEFAULT NULL,
        prioridade ENUM('baixa','media','alta','urgente') NOT NULL DEFAULT 'media',
        status ENUM('rascunho','pendente','em_analise','aprovada','reprovada','comprada','entregue','cancelada') NOT NULL DEFAULT 'rascunho',
        valor_total DECIMAL(14,2) NOT NULL DEFAULT 0,
        solicitante_id INT UNSIGNED NOT NULL,
        aprovador_id INT UNSIGNED DEFAULT NULL,
        email_compras VARCHAR(200) DEFAULT NULL,
        email_enviado TINYINT(1) NOT NULL DEFAULT 0,
        data_aprovacao DATETIME DEFAULT NULL,
        data_compra DATETIME DEFAULT NULL,
        data_entrega DATETIME DEFAULT NULL,
        observacoes TEXT DEFAULT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_supri_req_cod (codigo),
        INDEX idx_supri_req_status (status),
        INDEX idx_supri_req_solic (solicitante_id),
        CONSTRAINT fk_supri_req_solic FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
        CONSTRAINT fk_supri_req_aprov FOREIGN KEY (aprovador_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'suprimento_requisicoes' criada!\n";

    // ===========================
    // 5. Itens da Requisição
    // ===========================
    $db->query("CREATE TABLE IF NOT EXISTS suprimento_requisicao_itens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        requisicao_id INT UNSIGNED NOT NULL,
        suprimento_id INT UNSIGNED DEFAULT NULL,
        nome_item VARCHAR(200) NOT NULL,
        descricao VARCHAR(500) DEFAULT NULL,
        quantidade INT NOT NULL DEFAULT 1,
        unidade VARCHAR(20) NOT NULL DEFAULT 'un',
        preco_estimado DECIMAL(12,2) DEFAULT NULL,
        subtotal DECIMAL(14,2) GENERATED ALWAYS AS (quantidade * COALESCE(preco_estimado, 0)) STORED,
        INDEX idx_supri_ri_req (requisicao_id),
        INDEX idx_supri_ri_prod (suprimento_id),
        CONSTRAINT fk_supri_ri_req FOREIGN KEY (requisicao_id) REFERENCES suprimento_requisicoes(id) ON DELETE CASCADE,
        CONSTRAINT fk_supri_ri_prod FOREIGN KEY (suprimento_id) REFERENCES suprimentos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'suprimento_requisicao_itens' criada!\n";

    // ===========================
    // 6. Histórico de Requisições
    // ===========================
    $db->query("CREATE TABLE IF NOT EXISTS suprimento_requisicao_historico (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        requisicao_id INT UNSIGNED NOT NULL,
        status VARCHAR(30) NOT NULL,
        usuario_id INT UNSIGNED DEFAULT NULL,
        observacao TEXT DEFAULT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_supri_rh_req (requisicao_id),
        CONSTRAINT fk_supri_rh_req FOREIGN KEY (requisicao_id) REFERENCES suprimento_requisicoes(id) ON DELETE CASCADE,
        CONSTRAINT fk_supri_rh_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'suprimento_requisicao_historico' criada!\n";

    echo "\n🎉 Migration de Suprimentos concluída com sucesso!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
