<?php
/**
 * Migration: Sistema Multi-Departamentos
 * 
 * Cria tabela departamentos e adiciona departamento_id em:
 *   - categorias
 *   - chamados
 *   - usuarios
 */
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

try {
    // ============================================================
    // 1. Criar tabela departamentos
    // ============================================================
    $db->query("
        CREATE TABLE IF NOT EXISTS `departamentos` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(100) NOT NULL,
            `sigla` VARCHAR(10) NOT NULL UNIQUE,
            `descricao` TEXT DEFAULT NULL,
            `icone` VARCHAR(50) NOT NULL DEFAULT 'fas fa-building',
            `cor` VARCHAR(7) NOT NULL DEFAULT '#6366F1',
            `email` VARCHAR(200) DEFAULT NULL,
            `responsavel_id` INT UNSIGNED DEFAULT NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `ordem` INT NOT NULL DEFAULT 0,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_departamentos_ativo` (`ativo`),
            INDEX `idx_departamentos_ordem` (`ordem`),
            CONSTRAINT `fk_departamentos_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela departamentos criada\n";

    // ============================================================
    // 2. Adicionar departamento_id em categorias
    // ============================================================
    $col = $db->fetch("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categorias' AND COLUMN_NAME = 'departamento_id'");
    if (!$col) {
        $db->query("ALTER TABLE `categorias` ADD COLUMN `departamento_id` INT UNSIGNED DEFAULT NULL AFTER `tipo`");
        $db->query("ALTER TABLE `categorias` ADD INDEX `idx_categorias_departamento` (`departamento_id`)");
        $db->query("ALTER TABLE `categorias` ADD CONSTRAINT `fk_categorias_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE SET NULL");
        echo "✅ Coluna departamento_id adicionada em categorias\n";
    } else {
        echo "⏭️  Coluna categorias.departamento_id já existe\n";
    }

    // ============================================================
    // 3. Adicionar departamento_id em chamados
    // ============================================================
    $col = $db->fetch("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chamados' AND COLUMN_NAME = 'departamento_id'");
    if (!$col) {
        $db->query("ALTER TABLE `chamados` ADD COLUMN `departamento_id` INT UNSIGNED DEFAULT NULL AFTER `canal`");
        $db->query("ALTER TABLE `chamados` ADD INDEX `idx_chamados_departamento` (`departamento_id`)");
        $db->query("ALTER TABLE `chamados` ADD CONSTRAINT `fk_chamados_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE SET NULL");
        echo "✅ Coluna departamento_id adicionada em chamados\n";
    } else {
        echo "⏭️  Coluna chamados.departamento_id já existe\n";
    }

    // ============================================================
    // 4. Adicionar departamento_id em usuarios
    // ============================================================
    $col = $db->fetch("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'departamento_id'");
    if (!$col) {
        $db->query("ALTER TABLE `usuarios` ADD COLUMN `departamento_id` INT UNSIGNED DEFAULT NULL AFTER `departamento`");
        $db->query("ALTER TABLE `usuarios` ADD INDEX `idx_usuarios_departamento` (`departamento_id`)");
        $db->query("ALTER TABLE `usuarios` ADD CONSTRAINT `fk_usuarios_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE SET NULL");
        echo "✅ Coluna departamento_id adicionada em usuarios\n";
    } else {
        echo "⏭️  Coluna usuarios.departamento_id já existe\n";
    }

    // ============================================================
    // 5. Seed: Departamento TI padrão
    // ============================================================
    $existeTI = $db->fetch("SELECT id FROM departamentos WHERE sigla = 'TI'");
    if (!$existeTI) {
        $db->query("
            INSERT INTO `departamentos` (`nome`, `sigla`, `descricao`, `icone`, `cor`, `ativo`, `ordem`) VALUES
            ('Tecnologia da Informação', 'TI', 'Suporte técnico, infraestrutura, sistemas e redes', 'fas fa-laptop-code', '#3B82F6', 1, 1),
            ('Recursos Humanos', 'RH', 'Gestão de pessoas, folha de pagamento, benefícios e recrutamento', 'fas fa-users', '#8B5CF6', 1, 2),
            ('Financeiro', 'FIN', 'Contas a pagar e receber, fiscal, tesouraria', 'fas fa-dollar-sign', '#10B981', 1, 3),
            ('Manutenção', 'MAN', 'Manutenção predial, elétrica, hidráulica e equipamentos', 'fas fa-wrench', '#F59E0B', 1, 4),
            ('Administrativo', 'ADM', 'Gestão administrativa, compras, contratos', 'fas fa-building', '#EF4444', 1, 5)
        ");
        echo "✅ Departamentos padrão inseridos (TI, RH, FIN, MAN, ADM)\n";

        // Vincular categorias existentes (tipo=chamado) ao departamento TI
        $tiId = $db->fetchColumn("SELECT id FROM departamentos WHERE sigla = 'TI'");
        if ($tiId) {
            $db->query("UPDATE categorias SET departamento_id = ? WHERE tipo = 'chamado' AND departamento_id IS NULL", [$tiId]);
            echo "✅ Categorias de chamado vinculadas ao departamento TI\n";

            // Vincular chamados existentes ao departamento TI
            $db->query("UPDATE chamados SET departamento_id = ? WHERE departamento_id IS NULL", [$tiId]);
            echo "✅ Chamados existentes vinculados ao departamento TI\n";

            // Vincular usuarios existentes ao departamento TI
            $db->query("UPDATE usuarios SET departamento_id = ? WHERE departamento_id IS NULL", [$tiId]);
            echo "✅ Usuários existentes vinculados ao departamento TI\n";
        }
    } else {
        echo "⏭️  Departamentos já existem\n";
    }

    echo "\n🎉 Migration multi-departamentos concluída com sucesso!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
