<?php
/**
 * Layout: Sidebar
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$user = currentUser();

function menuActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-bolt"></i>
            <span>Oracle X</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Principal</span>
            <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="nav-link <?= menuActive('dashboard', $currentPage) ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Service Desk</span>
            <a href="<?= BASE_URL ?>/index.php?page=chamados" class="nav-link <?= menuActive('chamados', $currentPage) ?>">
                <i class="fas fa-ticket-alt"></i>
                <span>Chamados</span>
                <span class="nav-badge" id="badge-chamados"></span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=base-conhecimento" class="nav-link <?= menuActive('base-conhecimento', $currentPage) ?>">
                <i class="fas fa-book"></i>
                <span>Base de Conhecimento</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Gestão</span>
            <a href="<?= BASE_URL ?>/index.php?page=projetos" class="nav-link <?= menuActive('projetos', $currentPage) ?>">
                <i class="fas fa-project-diagram"></i>
                <span>Projetos</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=kanban" class="nav-link <?= menuActive('kanban', $currentPage) ?>">
                <i class="fas fa-columns"></i>
                <span>Kanban</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=sprints" class="nav-link <?= menuActive('sprints', $currentPage) ?>">
                <i class="fas fa-running"></i>
                <span>Sprints</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=calendario" class="nav-link <?= menuActive('calendario', $currentPage) ?>">
                <i class="fas fa-calendar-alt" style="color:#3B82F6"></i>
                <span>Calendário</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=timesheet" class="nav-link <?= menuActive('timesheet', $currentPage) ?>">
                <i class="fas fa-stopwatch" style="color:#10B981"></i>
                <span>Timesheet</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Comunicação</span>            <a href="<?= BASE_URL ?>/index.php?page=posts" class="nav-link <?= menuActive('posts', $currentPage) ?>">
                <i class="fas fa-stream" style="color:#8B5CF6"></i>
                <span>Mural</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=chat" class="nav-link <?= menuActive('chat', $currentPage) ?>">
                <i class="fas fa-comments" style="color:#10B981"></i>
                <span>Chat</span>
                <span class="nav-badge" id="badge-chat"></span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=email" class="nav-link <?= menuActive('email', $currentPage) ?>">
                <i class="fas fa-envelope"></i>
                <span>E-mail</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Inteligência Artificial</span>
            <a href="<?= BASE_URL ?>/index.php?page=ia" class="nav-link <?= menuActive('ia', $currentPage) ?>">
                <i class="fas fa-robot" style="color:#8B5CF6"></i>
                <span>Assistente IA</span>
            </a>
        </div>

        <?php if (isTIDept()): ?>
        <div class="nav-section">
            <span class="nav-section-title">Operações TI</span>
            <a href="<?= BASE_URL ?>/index.php?page=compras" class="nav-link <?= menuActive('compras', $currentPage) ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Compras</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=inventario" class="nav-link <?= menuActive('inventario', $currentPage) ?>">
                <i class="fas fa-boxes"></i>
                <span>Inventário</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=suprimentos" class="nav-link <?= menuActive('suprimentos', $currentPage) ?>">
                <i class="fas fa-warehouse" style="color:#8B5CF6"></i>
                <span>Suprimentos</span>
            </a>
        </div>
        <?php endif; ?>

        <?php
        // Módulos com permissão individual
        require_once __DIR__ . '/../../models/ModuloPermissao.php';
        $temFolha = temAcessoModulo('folha_pagamento');
        $temFinanceiro = temAcessoModulo('financeiro');
        if ($temFolha || $temFinanceiro):
        ?>
        <div class="nav-section">
            <span class="nav-section-title">Financeiro / RH</span>
            <?php if ($temFinanceiro): ?>
            <a href="<?= BASE_URL ?>/index.php?page=financeiro" class="nav-link <?= menuActive('financeiro', $currentPage) ?>">
                <i class="fas fa-file-invoice-dollar" style="color:#F59E0B"></i>
                <span>Financeiro</span>
            </a>
            <?php endif; ?>
            <?php if ($temFolha): ?>
            <a href="<?= BASE_URL ?>/index.php?page=folha-pagamento" class="nav-link <?= menuActive('folha-pagamento', $currentPage) ?>">
                <i class="fas fa-money-check-alt" style="color:#10B981"></i>
                <span>Folha de Pagamento</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="nav-section">
            <span class="nav-section-title">Ferramentas</span>
            <?php if (in_array($user['tipo'], ['admin', 'tecnico'])): ?>
            <a href="<?= BASE_URL ?>/index.php?page=remoto" class="nav-link <?= menuActive('remoto', $currentPage) ?>">
                <i class="fas fa-desktop" style="color:#3B82F6"></i>
                <span>Acesso Remoto</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=senhas" class="nav-link <?= menuActive('senhas', $currentPage) ?>">
                <i class="fas fa-key"></i>
                <span>Cofre de Senhas</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=rede" class="nav-link <?= menuActive('rede', $currentPage) ?>">
                <i class="fas fa-server"></i>
                <span>Gestão de Rede</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=ssh" class="nav-link <?= menuActive('ssh', $currentPage) ?>">
                <i class="fas fa-terminal"></i>
                <span>Terminal SSH</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=proxmox" class="nav-link <?= menuActive('proxmox', $currentPage) ?>">
                <i class="fas fa-cloud"></i>
                <span>Proxmox VE</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=mikrotik" class="nav-link <?= menuActive('mikrotik', $currentPage) ?>">
                <i class="fas fa-network-wired" style="color:#D6336C"></i>
                <span>MikroTik</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=github" class="nav-link <?= menuActive('github', $currentPage) ?>">
                <i class="fab fa-github"></i>
                <span>GitHub</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=cmdb" class="nav-link <?= menuActive('cmdb', $currentPage) ?>">
                <i class="fas fa-sitemap" style="color:#06B6D4"></i>
                <span>CMDB</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=monitor" class="nav-link <?= menuActive('monitor', $currentPage) ?>">
                <i class="fas fa-heartbeat" style="color:#EF4444"></i>
                <span>Monitor NOC</span>
            </a>
            <?php endif; ?>
        </div>

        <?php if (in_array($user['tipo'], ['admin', 'gestor'])): ?>
        <div class="nav-section">
            <span class="nav-section-title">Administração</span>
            <?php if ($user['tipo'] === 'admin'): ?>
            <a href="<?= BASE_URL ?>/index.php?page=departamentos" class="nav-link <?= menuActive('departamentos', $currentPage) ?>">
                <i class="fas fa-building" style="color:#6366F1"></i>
                <span>Departamentos</span>
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/index.php?page=usuarios" class="nav-link <?= menuActive('usuarios', $currentPage) ?>">
                <i class="fas fa-users-cog"></i>
                <span>Usuários</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=relatorios" class="nav-link <?= menuActive('relatorios', $currentPage) ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Relatórios</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=sla" class="nav-link <?= menuActive('sla', $currentPage) ?>">
                <i class="fas fa-tachometer-alt" style="color:#F59E0B"></i>
                <span>SLA Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=contratos" class="nav-link <?= menuActive('contratos', $currentPage) ?>">
                <i class="fas fa-file-contract" style="color:#8B5CF6"></i>
                <span>Contratos</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=automacoes" class="nav-link <?= menuActive('automacoes', $currentPage) ?>">
                <i class="fas fa-robot" style="color:#10B981"></i>
                <span>Automações</span>
            </a>
            <?php if ($user['tipo'] === 'admin'): ?>
            <a href="<?= BASE_URL ?>/index.php?page=ad" class="nav-link <?= menuActive('ad', $currentPage) ?>">
                <i class="fas fa-network-wired"></i>
                <span>Active Directory</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=configuracoes" class="nav-link <?= menuActive('configuracoes', $currentPage) ?>">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=atualizacao" class="nav-link <?= menuActive('atualizacao', $currentPage) ?>">
                <i class="fas fa-cloud-upload-alt" style="color:#F59E0B"></i>
                <span>Atualização</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=deploy" class="nav-link <?= menuActive('deploy', $currentPage) ?>">
                <i class="fas fa-rocket" style="color:#6366F1"></i>
                <span>Deploy Produção</span>
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=permissoes-modulos" class="nav-link <?= menuActive('permissoes-modulos', $currentPage) ?>">
                <i class="fas fa-shield-alt" style="color:#10B981"></i>
                <span>Permissões Módulos</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/avatars/<?= $user['avatar'] ?>" alt="">
                <?php else: ?>
                    <span><?= strtoupper(substr($user['nome'], 0, 2)) ?></span>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($user['nome']) ?></span>
                <span class="user-role"><?= ucfirst($user['tipo']) ?></span>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content">
    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-search">
                <i class="fas fa-search"></i>
                <input type="text" id="globalSearch" placeholder="Buscar chamados, projetos, tarefas..." autocomplete="off">
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="notif-dropdown-wrapper" id="notifWrapper">
                <button class="topbar-btn" id="notifBtn" title="Notificações" onclick="notifToggle()">
                    <i class="fas fa-bell"></i>
                    <span class="topbar-badge" id="notifBadge" style="display:none">0</span>
                </button>
                <div class="notif-dropdown" id="notifDropdown" style="display:none">
                    <div class="notif-dropdown-header">
                        <h3>Notificações</h3>
                        <button class="notif-mark-all" onclick="notifMarcarTodasLidas()" title="Marcar todas como lidas">
                            <i class="fas fa-check-double"></i>
                        </button>
                    </div>
                    <div class="notif-dropdown-body" id="notifDropdownBody">
                        <div class="notif-loading"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                    <div class="notif-dropdown-footer">
                        <a href="<?= BASE_URL ?>/index.php?page=notificacoes">Ver todas as notificações</a>
                    </div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/portal/" class="topbar-btn" title="Portal Público" target="_blank">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <div class="topbar-user-menu">
                <button class="topbar-user-btn" id="userMenuBtn">
                    <div class="user-avatar-sm">
                        <span><?= strtoupper(substr($user['nome'], 0, 2)) ?></span>
                    </div>
                    <span><?= htmlspecialchars($user['nome']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu" id="userDropdown">
                    <a href="<?= BASE_URL ?>/index.php?page=perfil"><i class="fas fa-user"></i> Meu Perfil</a>
                    <a href="<?= BASE_URL ?>/index.php?page=configuracoes"><i class="fas fa-cog"></i> Configurações</a>
                    <hr>
                    <a href="<?= BASE_URL ?>/login.php?action=logout" class="text-danger"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['tipo'] ?>" id="flashAlert">
        <i class="fas fa-<?= $flash['tipo'] === 'success' ? 'check-circle' : ($flash['tipo'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
        <span><?= $flash['mensagem'] ?></span>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>

    <!-- Page Content -->
    <div class="page-content">
