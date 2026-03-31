<?php
/**
 * Layout: Sidebar — ClickUp-style
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$user = currentUser();

require_once __DIR__ . '/../../models/ModuloPermissao.php';

function menuActive($page, $current) {
    return $page === $current ? 'active' : '';
}

// Helper: check module access (admin always passes)
function canSee($mod) {
    return temAcessoModulo($mod);
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Workspace Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon"><i class="fas fa-bolt"></i></div>
            <span class="sidebar-logo-text">Oracle X</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" title="Recolher menu">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <nav class="sidebar-nav" id="sidebarNav">
        <!-- ── Principal ── -->
        <div class="nav-section open" data-section="principal">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Principal</span>
            </button>
            <div class="nav-section-items">
                <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="nav-link <?= menuActive('dashboard', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#3B82F6"></span>
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <!-- ── Service Desk ── -->
        <div class="nav-section open" data-section="servicedesk">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Service Desk</span>
            </button>
            <div class="nav-section-items">
                <a href="<?= BASE_URL ?>/index.php?page=chamados" class="nav-link <?= menuActive('chamados', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#EF4444"></span>
                    <i class="fas fa-ticket-alt"></i>
                    <span>Chamados</span>
                    <span class="nav-badge" id="badge-chamados"></span>
                </a>
                <a href="<?= BASE_URL ?>/index.php?page=base-conhecimento" class="nav-link <?= menuActive('base-conhecimento', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#8B5CF6"></span>
                    <i class="fas fa-book"></i>
                    <span>Base de Conhecimento</span>
                </a>
            </div>
        </div>

        <!-- ── Gestão ── -->
        <div class="nav-section open" data-section="gestao">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Gestão</span>
            </button>
            <div class="nav-section-items">
                <a href="<?= BASE_URL ?>/index.php?page=projetos" class="nav-link <?= menuActive('projetos', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#10B981"></span>
                    <i class="fas fa-project-diagram"></i>
                    <span>Projetos</span>
                </a>
                <a href="<?= BASE_URL ?>/index.php?page=kanban" class="nav-link <?= menuActive('kanban', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#F59E0B"></span>
                    <i class="fas fa-columns"></i>
                    <span>Kanban</span>
                </a>
                <a href="<?= BASE_URL ?>/index.php?page=sprints" class="nav-link <?= menuActive('sprints', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#EC4899"></span>
                    <i class="fas fa-running"></i>
                    <span>Sprints</span>
                </a>
                <a href="<?= BASE_URL ?>/index.php?page=calendario" class="nav-link <?= menuActive('calendario', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#3B82F6"></span>
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendário</span>
                </a>
                <a href="<?= BASE_URL ?>/index.php?page=timesheet" class="nav-link <?= menuActive('timesheet', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#10B981"></span>
                    <i class="fas fa-stopwatch"></i>
                    <span>Timesheet</span>
                </a>
                <?php if (canSee('sla')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=sla" class="nav-link <?= menuActive('sla', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#F59E0B"></span>
                    <i class="fas fa-tachometer-alt"></i>
                    <span>SLA Dashboard</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('contratos')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=contratos" class="nav-link <?= menuActive('contratos', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#8B5CF6"></span>
                    <i class="fas fa-file-contract"></i>
                    <span>Contratos</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('relatorios')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=relatorios" class="nav-link <?= menuActive('relatorios', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#8B5CF6"></span>
                    <i class="fas fa-chart-bar"></i>
                    <span>Relatórios</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Comunicação ── -->
        <div class="nav-section open" data-section="comunicacao">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Comunicação</span>
            </button>
            <div class="nav-section-items">
                <a href="<?= BASE_URL ?>/index.php?page=posts" class="nav-link <?= menuActive('posts', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#8B5CF6"></span>
                    <i class="fas fa-stream"></i>
                    <span>Mural</span>
                </a>
                <a href="<?= BASE_URL ?>/index.php?page=chat" class="nav-link <?= menuActive('chat', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#10B981"></span>
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                    <span class="nav-badge" id="badge-chat"></span>
                </a>
                <a href="<?= BASE_URL ?>/index.php?page=email" class="nav-link <?= menuActive('email', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#3B82F6"></span>
                    <i class="fas fa-envelope"></i>
                    <span>E-mail</span>
                </a>
            </div>
        </div>

        <!-- ── Inteligência Artificial ── -->
        <div class="nav-section open" data-section="ia">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Inteligência Artificial</span>
            </button>
            <div class="nav-section-items">
                <a href="<?= BASE_URL ?>/index.php?page=ia" class="nav-link <?= menuActive('ia', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#8B5CF6"></span>
                    <i class="fas fa-robot"></i>
                    <span>Assistente IA</span>
                </a>
                <?php if (canSee('chatbot')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=chatbot" class="nav-link <?= menuActive('chatbot', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#06B6D4"></span>
                    <i class="fas fa-headset"></i>
                    <span>Chatbot</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Operações TI ── -->
        <?php
        $opTI = ['compras','inventario','suprimentos'];
        $temOpTI = false;
        foreach ($opTI as $m) { if (canSee($m)) { $temOpTI = true; break; } }
        if ($temOpTI):
        ?>
        <div class="nav-section open" data-section="operacoes">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Operações TI</span>
            </button>
            <div class="nav-section-items">
                <?php if (canSee('compras')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=compras" class="nav-link <?= menuActive('compras', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#F59E0B"></span>
                    <i class="fas fa-shopping-cart"></i>
                    <span>Compras</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('inventario')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=inventario" class="nav-link <?= menuActive('inventario', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#8B5CF6"></span>
                    <i class="fas fa-boxes"></i>
                    <span>Inventário</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('suprimentos')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=suprimentos" class="nav-link <?= menuActive('suprimentos', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#8B5CF6"></span>
                    <i class="fas fa-warehouse"></i>
                    <span>Suprimentos</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Financeiro / RH ── -->
        <?php
        $temFinanceiro = canSee('financeiro');
        $temFolha = canSee('folha_pagamento');
        if ($temFinanceiro || $temFolha):
        ?>
        <div class="nav-section open" data-section="finrh">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Financeiro / RH</span>
            </button>
            <div class="nav-section-items">
                <?php if ($temFinanceiro): ?>
                <a href="<?= BASE_URL ?>/index.php?page=financeiro" class="nav-link <?= menuActive('financeiro', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#F59E0B"></span>
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Financeiro</span>
                </a>
                <?php endif; ?>
                <?php if ($temFolha): ?>
                <a href="<?= BASE_URL ?>/index.php?page=folha-pagamento" class="nav-link <?= menuActive('folha-pagamento', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#10B981"></span>
                    <i class="fas fa-money-check-alt"></i>
                    <span>Folha de Pagamento</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Ferramentas ── -->
        <?php
        $ferramentas = ['remoto','senhas','rede','ssh','proxmox','mikrotik','github','cmdb','monitor','airflow'];
        $temFerr = false;
        foreach ($ferramentas as $m) { if (canSee($m)) { $temFerr = true; break; } }
        if ($temFerr):
        ?>
        <div class="nav-section open" data-section="ferramentas">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Ferramentas</span>
            </button>
            <div class="nav-section-items">
                <?php if (canSee('remoto')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=remoto" class="nav-link <?= menuActive('remoto', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#3B82F6"></span>
                    <i class="fas fa-desktop"></i>
                    <span>Acesso Remoto</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('senhas')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=senhas" class="nav-link <?= menuActive('senhas', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#EF4444"></span>
                    <i class="fas fa-key"></i>
                    <span>Cofre de Senhas</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('rede')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=rede" class="nav-link <?= menuActive('rede', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#3B82F6"></span>
                    <i class="fas fa-server"></i>
                    <span>Gestão de Rede</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('ssh')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=ssh" class="nav-link <?= menuActive('ssh', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#10B981"></span>
                    <i class="fas fa-terminal"></i>
                    <span>Terminal SSH</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('proxmox')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=proxmox" class="nav-link <?= menuActive('proxmox', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#E97521"></span>
                    <i class="fas fa-cloud"></i>
                    <span>Proxmox VE</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('mikrotik')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=mikrotik" class="nav-link <?= menuActive('mikrotik', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#D6336C"></span>
                    <i class="fas fa-network-wired"></i>
                    <span>MikroTik</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('github')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=github" class="nav-link <?= menuActive('github', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#6E40C9"></span>
                    <i class="fab fa-github"></i>
                    <span>GitHub</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('cmdb')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=cmdb" class="nav-link <?= menuActive('cmdb', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#06B6D4"></span>
                    <i class="fas fa-sitemap"></i>
                    <span>CMDB</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('monitor')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=monitor" class="nav-link <?= menuActive('monitor', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#EF4444"></span>
                    <i class="fas fa-heartbeat"></i>
                    <span>Monitor NOC</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('airflow')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=airflow" class="nav-link <?= menuActive('airflow', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#017CEE"></span>
                    <i class="fas fa-wind"></i>
                    <span>Airflow</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Administração ── -->
        <?php
        $adminMods = ['departamentos','usuarios','automacoes','ad','configuracoes','atualizacao','deploy'];
        $temAdmin = false;
        foreach ($adminMods as $m) { if (canSee($m)) { $temAdmin = true; break; } }
        if ($temAdmin || isAdmin()):
        ?>
        <div class="nav-section open" data-section="admin">
            <button class="nav-section-toggle" onclick="toggleSection(this)">
                <i class="fas fa-chevron-right nav-section-arrow"></i>
                <span class="nav-section-title">Administração</span>
            </button>
            <div class="nav-section-items">
                <?php if (canSee('departamentos')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=departamentos" class="nav-link <?= menuActive('departamentos', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#6366F1"></span>
                    <i class="fas fa-building"></i>
                    <span>Departamentos</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('usuarios')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=usuarios" class="nav-link <?= menuActive('usuarios', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#3B82F6"></span>
                    <i class="fas fa-users-cog"></i>
                    <span>Usuários</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('automacoes')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=automacoes" class="nav-link <?= menuActive('automacoes', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#10B981"></span>
                    <i class="fas fa-robot"></i>
                    <span>Automações</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('ad')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=ad" class="nav-link <?= menuActive('ad', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#3B82F6"></span>
                    <i class="fas fa-network-wired"></i>
                    <span>Active Directory</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('configuracoes')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=configuracoes" class="nav-link <?= menuActive('configuracoes', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#64748B"></span>
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('atualizacao')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=atualizacao" class="nav-link <?= menuActive('atualizacao', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#F59E0B"></span>
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Atualização</span>
                </a>
                <?php endif; ?>
                <?php if (canSee('deploy')): ?>
                <a href="<?= BASE_URL ?>/index.php?page=deploy" class="nav-link <?= menuActive('deploy', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#6366F1"></span>
                    <i class="fas fa-rocket"></i>
                    <span>Deploy Produção</span>
                </a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/index.php?page=permissoes-modulos" class="nav-link <?= menuActive('permissoes-modulos', $currentPage) ?>">
                    <span class="nav-dot" style="--dot-color:#10B981"></span>
                    <i class="fas fa-shield-alt"></i>
                    <span>Permissões</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer / User -->
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/index.php?page=perfil" class="sidebar-user">
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
        </a>
    </div>
</aside>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

<!-- Main Content -->
<main class="main-content">
    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-menu-btn" id="mobileMenuBtn" onclick="openMobileSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-search">
                <i class="fas fa-search"></i>
                <input type="text" id="globalSearch" placeholder="Buscar chamados, projetos, tarefas..." autocomplete="off">
                <kbd class="search-shortcut">⌘K</kbd>
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="theme-toggle" id="themeToggleBtn" title="Alternar tema" onclick="HelpDesk.toggleTheme()">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
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
                    <span class="topbar-user-name"><?= htmlspecialchars($user['nome']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu" id="userDropdown">
                    <a href="<?= BASE_URL ?>/index.php?page=perfil"><i class="fas fa-user"></i> Meu Perfil</a>
                    <?php if (canSee('configuracoes')): ?>
                    <a href="<?= BASE_URL ?>/index.php?page=configuracoes"><i class="fas fa-cog"></i> Configurações</a>
                    <?php endif; ?>
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
