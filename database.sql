-- ============================================================
-- Oracle X - Sistema Completo
-- Banco de Dados MySQL/MariaDB
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE IF NOT EXISTS `helpdesk_ti` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `helpdesk_ti`;

-- ============================================================
-- TABELA: usuarios
-- ============================================================
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(200) NOT NULL UNIQUE,
  `telefone` VARCHAR(20) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `tipo` ENUM('admin','gestor','tecnico') NOT NULL DEFAULT 'tecnico',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `cargo` VARCHAR(100) DEFAULT NULL,
  `departamento` VARCHAR(100) DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_login` DATETIME DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_tipo` (`tipo`),
  INDEX `idx_usuarios_telefone` (`telefone`),
  INDEX `idx_usuarios_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: solicitantes (usuários do portal público)
-- ============================================================
CREATE TABLE `solicitantes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(200) NOT NULL,
  `telefone` VARCHAR(20) NOT NULL UNIQUE,
  `empresa` VARCHAR(150) DEFAULT NULL,
  `departamento` VARCHAR(100) DEFAULT NULL,
  `total_chamados` INT UNSIGNED NOT NULL DEFAULT 0,
  `data_cadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_solicitantes_telefone` (`telefone`),
  INDEX `idx_solicitantes_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: categorias
-- ============================================================
CREATE TABLE `categorias` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `tipo` ENUM('chamado','projeto','compra','conhecimento','inventario') NOT NULL DEFAULT 'chamado',
  `icone` VARCHAR(50) DEFAULT 'fas fa-tag',
  `cor` VARCHAR(7) DEFAULT '#6366F1',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_categorias_tipo` (`tipo`),
  INDEX `idx_categorias_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: sla
-- ============================================================
CREATE TABLE `sla` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT UNSIGNED DEFAULT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `prioridade` ENUM('critica','alta','media','baixa') NOT NULL,
  `tempo_resposta` INT UNSIGNED NOT NULL COMMENT 'em minutos',
  `tempo_resolucao` INT UNSIGNED NOT NULL COMMENT 'em minutos',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sla_prioridade` (`prioridade`),
  UNIQUE INDEX `idx_sla_cat_pri` (`categoria_id`, `prioridade`),
  CONSTRAINT `fk_sla_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: inventario
-- ============================================================
CREATE TABLE `inventario` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tipo` ENUM('computador','servidor','switch','roteador','impressora','software','monitor','telefone','outro') NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `numero_patrimonio` VARCHAR(50) UNIQUE,
  `modelo` VARCHAR(150) DEFAULT NULL,
  `fabricante` VARCHAR(100) DEFAULT NULL,
  `numero_serie` VARCHAR(100) DEFAULT NULL,
  `localizacao` VARCHAR(200) DEFAULT NULL,
  `responsavel_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('ativo','manutencao','inativo','descartado') NOT NULL DEFAULT 'ativo',
  `data_aquisicao` DATE DEFAULT NULL,
  `valor_aquisicao` DECIMAL(12,2) DEFAULT NULL,
  `garantia_ate` DATE DEFAULT NULL,
  `especificacoes` TEXT DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_inventario_tipo` (`tipo`),
  INDEX `idx_inventario_status` (`status`),
  INDEX `idx_inventario_patrimonio` (`numero_patrimonio`),
  CONSTRAINT `fk_inventario_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: chamados
-- ============================================================
CREATE TABLE `chamados` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `titulo` VARCHAR(255) NOT NULL,
  `descricao` TEXT NOT NULL,
  `categoria_id` INT UNSIGNED DEFAULT NULL,
  `prioridade` ENUM('critica','alta','media','baixa') NOT NULL DEFAULT 'media',
  `urgencia` ENUM('alta','media','baixa') NOT NULL DEFAULT 'media',
  `impacto` ENUM('alto','medio','baixo') NOT NULL DEFAULT 'medio',
  `solicitante_id` INT UNSIGNED DEFAULT NULL,
  `telefone_solicitante` VARCHAR(20) NOT NULL,
  `tecnico_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('aberto','em_analise','em_atendimento','aguardando_usuario','resolvido','fechado') NOT NULL DEFAULT 'aberto',
  `sla_id` INT UNSIGNED DEFAULT NULL,
  `ativo_id` INT UNSIGNED DEFAULT NULL,
  `canal` ENUM('portal','interno','whatsapp','email') NOT NULL DEFAULT 'portal',
  `data_abertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_primeira_resposta` DATETIME DEFAULT NULL,
  `data_resolucao` DATETIME DEFAULT NULL,
  `data_fechamento` DATETIME DEFAULT NULL,
  `sla_resposta_vencido` TINYINT(1) NOT NULL DEFAULT 0,
  `sla_resolucao_vencido` TINYINT(1) NOT NULL DEFAULT 0,
  `avaliacao` TINYINT UNSIGNED DEFAULT NULL,
  `avaliacao_comentario` TEXT DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_chamados_status` (`status`),
  INDEX `idx_chamados_prioridade` (`prioridade`),
  INDEX `idx_chamados_tecnico` (`tecnico_id`),
  INDEX `idx_chamados_solicitante` (`solicitante_id`),
  INDEX `idx_chamados_telefone` (`telefone_solicitante`),
  INDEX `idx_chamados_codigo` (`codigo`),
  INDEX `idx_chamados_data` (`data_abertura`),
  CONSTRAINT `fk_chamados_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chamados_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `solicitantes`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chamados_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chamados_sla` FOREIGN KEY (`sla_id`) REFERENCES `sla`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chamados_ativo` FOREIGN KEY (`ativo_id`) REFERENCES `inventario`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: chamados_comentarios
-- ============================================================
CREATE TABLE `chamados_comentarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chamado_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `solicitante_id` INT UNSIGNED DEFAULT NULL,
  `autor_nome` VARCHAR(150) DEFAULT NULL,
  `conteudo` TEXT NOT NULL,
  `tipo` ENUM('comentario','interno','sistema') NOT NULL DEFAULT 'comentario',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_comentarios_chamado` (`chamado_id`),
  CONSTRAINT `fk_comentarios_chamado` FOREIGN KEY (`chamado_id`) REFERENCES `chamados`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comentarios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comentarios_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `solicitantes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: chamados_historico
-- ============================================================
CREATE TABLE `chamados_historico` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chamado_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `campo` VARCHAR(50) NOT NULL,
  `valor_anterior` TEXT DEFAULT NULL,
  `valor_novo` TEXT DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_historico_chamado` (`chamado_id`),
  CONSTRAINT `fk_historico_chamado` FOREIGN KEY (`chamado_id`) REFERENCES `chamados`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_historico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: chamados_tags
-- ============================================================
CREATE TABLE `chamados_tags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chamado_id` INT UNSIGNED NOT NULL,
  `tag` VARCHAR(50) NOT NULL,
  INDEX `idx_tags_chamado` (`chamado_id`),
  INDEX `idx_tags_tag` (`tag`),
  CONSTRAINT `fk_tags_chamado` FOREIGN KEY (`chamado_id`) REFERENCES `chamados`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: projetos
-- ============================================================
CREATE TABLE `projetos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(200) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `responsavel_id` INT UNSIGNED DEFAULT NULL,
  `prioridade` ENUM('critica','alta','media','baixa') NOT NULL DEFAULT 'media',
  `status` ENUM('planejamento','em_desenvolvimento','em_testes','concluido','cancelado') NOT NULL DEFAULT 'planejamento',
  `data_inicio` DATE DEFAULT NULL,
  `prazo` DATE DEFAULT NULL,
  `progresso` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `chamado_origem_id` INT UNSIGNED DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_projetos_status` (`status`),
  INDEX `idx_projetos_responsavel` (`responsavel_id`),
  CONSTRAINT `fk_projetos_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_projetos_chamado_origem` FOREIGN KEY (`chamado_origem_id`) REFERENCES `chamados`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: projetos_equipe
-- ============================================================
CREATE TABLE `projetos_equipe` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `projeto_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `papel` VARCHAR(50) DEFAULT 'membro',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_projeto_usuario` (`projeto_id`, `usuario_id`),
  CONSTRAINT `fk_equipe_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_equipe_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: projetos_comentarios
-- ============================================================
CREATE TABLE `projetos_comentarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `projeto_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `conteudo` TEXT NOT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_proj_coment_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_proj_coment_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: tarefas
-- ============================================================
CREATE TABLE `tarefas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(255) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `projeto_id` INT UNSIGNED DEFAULT NULL,
  `sprint_id` INT UNSIGNED DEFAULT NULL,
  `responsavel_id` INT UNSIGNED DEFAULT NULL,
  `coluna` ENUM('backlog','a_fazer','em_andamento','em_revisao','concluido') NOT NULL DEFAULT 'backlog',
  `prioridade` ENUM('critica','alta','media','baixa') NOT NULL DEFAULT 'media',
  `prazo` DATE DEFAULT NULL,
  `pontos` TINYINT UNSIGNED DEFAULT NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `horas_estimadas` DECIMAL(5,1) DEFAULT NULL,
  `horas_trabalhadas` DECIMAL(5,1) DEFAULT 0,
  `chamado_origem_id` INT UNSIGNED DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tarefas_projeto` (`projeto_id`),
  INDEX `idx_tarefas_sprint` (`sprint_id`),
  INDEX `idx_tarefas_responsavel` (`responsavel_id`),
  INDEX `idx_tarefas_coluna` (`coluna`),
  CONSTRAINT `fk_tarefas_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tarefas_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: tarefas_comentarios
-- ============================================================
CREATE TABLE `tarefas_comentarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tarefa_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `conteudo` TEXT NOT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tarefa_coment` FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tarefa_coment_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: tarefas_tags
-- ============================================================
CREATE TABLE `tarefas_tags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tarefa_id` INT UNSIGNED NOT NULL,
  `tag` VARCHAR(50) NOT NULL,
  INDEX `idx_tarefa_tags` (`tarefa_id`),
  CONSTRAINT `fk_tarefa_tags` FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: sprints
-- ============================================================
CREATE TABLE `sprints` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `projeto_id` INT UNSIGNED NOT NULL,
  `data_inicio` DATE NOT NULL,
  `data_fim` DATE NOT NULL,
  `status` ENUM('planejamento','ativa','concluida','cancelada') NOT NULL DEFAULT 'planejamento',
  `meta` TEXT DEFAULT NULL,
  `velocidade` INT UNSIGNED DEFAULT 0,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sprints_projeto` (`projeto_id`),
  INDEX `idx_sprints_status` (`status`),
  CONSTRAINT `fk_sprints_projeto` FOREIGN KEY (`projeto_id`) REFERENCES `projetos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar FK de tarefas -> sprints após criação da tabela sprints
ALTER TABLE `tarefas` ADD CONSTRAINT `fk_tarefas_sprint` FOREIGN KEY (`sprint_id`) REFERENCES `sprints`(`id`) ON DELETE SET NULL;
ALTER TABLE `tarefas` ADD CONSTRAINT `fk_tarefas_chamado_origem` FOREIGN KEY (`chamado_origem_id`) REFERENCES `chamados`(`id`) ON DELETE SET NULL;

-- ============================================================
-- TABELA: compras
-- ============================================================
CREATE TABLE `compras` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `solicitante_id` INT UNSIGNED DEFAULT NULL,
  `solicitante_usuario_id` INT UNSIGNED DEFAULT NULL,
  `item` VARCHAR(200) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `quantidade` INT UNSIGNED NOT NULL DEFAULT 1,
  `justificativa` TEXT NOT NULL,
  `prioridade` ENUM('critica','alta','media','baixa') NOT NULL DEFAULT 'media',
  `valor_estimado` DECIMAL(12,2) DEFAULT NULL,
  `valor_aprovado` DECIMAL(12,2) DEFAULT NULL,
  `status` ENUM('solicitado','em_analise','aprovado','reprovado','comprado','entregue','cancelado') NOT NULL DEFAULT 'solicitado',
  `aprovador_id` INT UNSIGNED DEFAULT NULL,
  `fornecedor` VARCHAR(200) DEFAULT NULL,
  `nota_fiscal` VARCHAR(100) DEFAULT NULL,
  `data_aprovacao` DATETIME DEFAULT NULL,
  `data_compra` DATETIME DEFAULT NULL,
  `data_entrega` DATETIME DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `chamado_origem_id` INT UNSIGNED DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_compras_status` (`status`),
  INDEX `idx_compras_solicitante` (`solicitante_usuario_id`),
  CONSTRAINT `fk_compras_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `solicitantes`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_compras_solicitante_usuario` FOREIGN KEY (`solicitante_usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_compras_aprovador` FOREIGN KEY (`aprovador_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_compras_chamado_origem` FOREIGN KEY (`chamado_origem_id`) REFERENCES `chamados`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: compras_historico
-- ============================================================
CREATE TABLE `compras_historico` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `observacao` TEXT DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_compras_hist` FOREIGN KEY (`compra_id`) REFERENCES `compras`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_compras_hist_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: base_conhecimento
-- ============================================================
CREATE TABLE `base_conhecimento` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(255) NOT NULL,
  `problema` TEXT NOT NULL,
  `solucao` TEXT NOT NULL,
  `categoria_id` INT UNSIGNED DEFAULT NULL,
  `autor_id` INT UNSIGNED DEFAULT NULL,
  `visualizacoes` INT UNSIGNED NOT NULL DEFAULT 0,
  `util` INT UNSIGNED NOT NULL DEFAULT 0,
  `publicado` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY `ft_conhecimento` (`titulo`, `problema`, `solucao`),
  CONSTRAINT `fk_conhecimento_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conhecimento_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: anexos
-- ============================================================
CREATE TABLE `anexos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `entidade_tipo` ENUM('chamado','chamado_comentario','projeto','tarefa','compra','conhecimento','inventario') NOT NULL,
  `entidade_id` INT UNSIGNED NOT NULL,
  `nome_original` VARCHAR(255) NOT NULL,
  `nome_arquivo` VARCHAR(255) NOT NULL,
  `tamanho` INT UNSIGNED NOT NULL DEFAULT 0,
  `tipo_mime` VARCHAR(100) DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_anexos_entidade` (`entidade_tipo`, `entidade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: notificacoes
-- ============================================================
CREATE TABLE `notificacoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tipo` VARCHAR(50) NOT NULL,
  `destinatario_telefone` VARCHAR(20) NOT NULL,
  `destinatario_nome` VARCHAR(150) DEFAULT NULL,
  `mensagem` TEXT NOT NULL,
  `referencia_tipo` VARCHAR(30) DEFAULT NULL,
  `referencia_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('pendente','enviado','erro') NOT NULL DEFAULT 'pendente',
  `tentativas` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `erro` TEXT DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `enviado_em` DATETIME DEFAULT NULL,
  INDEX `idx_notificacoes_status` (`status`),
  INDEX `idx_notificacoes_telefone` (`destinatario_telefone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: logs
-- ============================================================
CREATE TABLE `logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `entidade_tipo` VARCHAR(30) DEFAULT NULL,
  `entidade_id` INT UNSIGNED DEFAULT NULL,
  `detalhes` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_logs_usuario` (`usuario_id`),
  INDEX `idx_logs_acao` (`acao`),
  INDEX `idx_logs_data` (`criado_em`),
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: configuracoes
-- ============================================================
CREATE TABLE `configuracoes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT DEFAULT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Usuário admin padrão (senha: admin123)
INSERT INTO `usuarios` (`nome`, `email`, `telefone`, `senha`, `tipo`) VALUES
('Administrador', 'admin@helpdesk.com', '5562999999999', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Técnicos de exemplo
INSERT INTO `usuarios` (`nome`, `email`, `telefone`, `senha`, `tipo`, `cargo`, `departamento`) VALUES
('Carlos Técnico', 'carlos@helpdesk.com', '5562988888888', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico', 'Analista de Suporte', 'TI'),
('Maria Gestora', 'maria@helpdesk.com', '5562977777777', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestor', 'Gestora de TI', 'TI');

-- Categorias padrão
INSERT INTO `categorias` (`nome`, `tipo`, `icone`, `cor`) VALUES
('Hardware', 'chamado', 'fas fa-desktop', '#EF4444'),
('Software', 'chamado', 'fas fa-code', '#3B82F6'),
('Rede', 'chamado', 'fas fa-network-wired', '#8B5CF6'),
('E-mail', 'chamado', 'fas fa-envelope', '#F59E0B'),
('Impressora', 'chamado', 'fas fa-print', '#10B981'),
('Acesso / Permissão', 'chamado', 'fas fa-key', '#EC4899'),
('Telefonia', 'chamado', 'fas fa-phone', '#6366F1'),
('Sistema ERP', 'chamado', 'fas fa-server', '#14B8A6'),
('Infraestrutura', 'chamado', 'fas fa-building', '#F97316'),
('Outros', 'chamado', 'fas fa-ellipsis-h', '#6B7280'),
('Desenvolvimento', 'projeto', 'fas fa-laptop-code', '#3B82F6'),
('Infraestrutura', 'projeto', 'fas fa-server', '#10B981'),
('Segurança', 'projeto', 'fas fa-shield-alt', '#EF4444'),
('Equipamentos', 'compra', 'fas fa-box', '#F59E0B'),
('Software/Licenças', 'compra', 'fas fa-file-contract', '#8B5CF6'),
('Suprimentos', 'compra', 'fas fa-boxes', '#6366F1'),
('Tutorial', 'conhecimento', 'fas fa-book', '#3B82F6'),
('FAQ', 'conhecimento', 'fas fa-question-circle', '#10B981'),
('Procedimento', 'conhecimento', 'fas fa-clipboard-list', '#F59E0B');

-- SLA padrão
INSERT INTO `sla` (`nome`, `prioridade`, `tempo_resposta`, `tempo_resolucao`) VALUES
('SLA Crítica', 'critica', 15, 120),
('SLA Alta', 'alta', 30, 240),
('SLA Média', 'media', 60, 480),
('SLA Baixa', 'baixa', 120, 1440);

-- Configurações padrão
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`) VALUES
('empresa_nome', 'Oracle X', 'Nome da empresa'),
('empresa_logo', '', 'Logo da empresa'),
('evolution_api_url', 'http://localhost:8080', 'URL da Evolution API'),
('evolution_api_key', '', 'API Key da Evolution API'),
('evolution_instance', 'helpdesk', 'Nome da instância Evolution API'),
('whatsapp_ativo', '0', 'Ativar notificações WhatsApp'),
('recaptcha_site_key', '', 'Google reCAPTCHA Site Key'),
('recaptcha_secret_key', '', 'Google reCAPTCHA Secret Key'),
('limite_chamados_telefone', '5', 'Limite de chamados abertos por telefone'),
('horario_inicio', '08:00', 'Início do horário comercial'),
('horario_fim', '18:00', 'Fim do horário comercial'),
('dias_uteis', '1,2,3,4,5', 'Dias úteis (1=seg, 7=dom)');

SET FOREIGN_KEY_CHECKS = 1;
