<?php
/**
 * HelpDesk TI - Entry Point Principal
 */
session_start();

// Redirecionar para setup se não estiver instalado
if (!file_exists(__DIR__ . '/config/.installed')) {
    header('Location: /helpdesk/setup.php');
    exit;
}

require_once __DIR__ . '/config/app.php';

requireLogin();

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// Páginas válidas
$validPages = [
    'dashboard', 'chamados', 'projetos', 'kanban', 'sprints',
    'compras', 'inventario', 'suprimentos', 'senhas', 'rede', 'ssh', 'ia', 'proxmox', 'email', 'ad', 'usuarios', 'base-conhecimento',
    'relatorios', 'configuracoes', 'perfil', 'mikrotik', 'github', 'notificacoes', 'monitor', 'calendario', 'sla', 'contratos', 'cmdb', 'timesheet', 'automacoes', 'atualizacao', 'remoto', 'deploy'
];

if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}

$pageTitle = 'HelpDesk TI';

// Carregar dados por página
switch ($page) {
    case 'dashboard':
        require_once __DIR__ . '/app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $stats = $controller->getStats();
        $db = Database::getInstance();
        $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        $ativos = $db->fetchAll("SELECT id, nome, numero_patrimonio FROM inventario WHERE status = 'ativo' ORDER BY nome");
        $pageTitle = 'Dashboard - HelpDesk TI';
        break;

    case 'chamados':
        require_once __DIR__ . '/app/controllers/ChamadoController.php';
        $controller = new ChamadoController();
        if ($action === 'ver' && isset($_GET['id'])) {
            $chamado = $controller->ver((int)$_GET['id']);
            $pageTitle = 'Chamado #' . ($chamado['codigo'] ?? '') . ' - HelpDesk TI';
        } else {
            $resultado = $controller->listar();
            $chamados = $resultado['chamados'];
            $paginacao = [
                'total' => $resultado['total'],
                'pagina' => $resultado['pagina'],
                'por_pagina' => $resultado['por_pagina'],
                'total_paginas' => $resultado['total_paginas'],
            ];
            $filtrosAtivos = $resultado['filtros'];
            $pageTitle = 'Chamados - HelpDesk TI';
        }
        // Carregar categorias e técnicos para formulários
        $db = Database::getInstance();
        $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        $ativos = $db->fetchAll("SELECT id, nome, numero_patrimonio FROM inventario WHERE status = 'ativo' ORDER BY nome");
        // Projetos para transformação em sprint
        $projetos = $db->fetchAll("SELECT id, nome FROM projetos WHERE status NOT IN ('concluido','cancelado') ORDER BY nome");
        break;

    case 'projetos':
        require_once __DIR__ . '/app/controllers/ProjetoController.php';
        $controller = new ProjetoController();
        if ($action === 'ver' && isset($_GET['id'])) {
            $projeto = $controller->ver((int)$_GET['id']);
            $pageTitle = ($projeto['nome'] ?? 'Projeto') . ' - HelpDesk TI';
        } else {
            $projetos = $controller->listar();
            $pageTitle = 'Projetos - HelpDesk TI';
        }
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        break;

    case 'kanban':
        require_once __DIR__ . '/app/controllers/TarefaController.php';
        $controller = new TarefaController();
        $projetoId = $_GET['projeto_id'] ?? null;
        $kanban = $controller->listarKanban($projetoId);
        $pageTitle = 'Kanban - HelpDesk TI';
        $db = Database::getInstance();
        $projetosLista = $db->fetchAll("SELECT id, nome FROM projetos WHERE status != 'cancelado' ORDER BY nome");
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        $extraJS = 'kanban.js';
        break;

    case 'sprints':
        require_once __DIR__ . '/app/controllers/SprintController.php';
        $controller = new SprintController();
        $projetoId = $_GET['projeto'] ?? null;
        if (isset($_GET['acao']) && $_GET['acao'] === 'ver' && isset($_GET['id'])) {
            $sprint = $controller->ver((int)$_GET['id']);
            $tarefasSprint = $sprint['tarefas'] ?? [];
            $burndownData = $sprint['burndown'] ?? [];
            $action = 'ver';
            $pageTitle = ($sprint['nome'] ?? 'Sprint') . ' - HelpDesk TI';

            // Handle POST actions for sprint
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
                if ($_POST['action'] === 'iniciar') {
                    $controller->atualizar((int)$_POST['sprint_id'], ['status' => 'ativa']);
                    header('Location: ' . BASE_URL . '/index.php?page=sprints&acao=ver&id=' . $_POST['sprint_id']); exit;
                } elseif ($_POST['action'] === 'concluir') {
                    $controller->atualizar((int)$_POST['sprint_id'], ['status' => 'concluida']);
                    header('Location: ' . BASE_URL . '/index.php?page=sprints&acao=ver&id=' . $_POST['sprint_id']); exit;
                }
            }
        } else {
            $sprints = $controller->listar($projetoId);
            // Check for active sprint
            $sprintAtivo = null;
            foreach ($sprints as $s) {
                if ($s['status'] === 'ativa') { $sprintAtivo = $s; break; }
            }
            $pageTitle = 'Sprints - HelpDesk TI';
        }
        $db = Database::getInstance();
        $projetos = $db->fetchAll("SELECT id, nome FROM projetos WHERE status != 'cancelado' ORDER BY nome");
        break;

    case 'compras':
        require_once __DIR__ . '/app/controllers/CompraController.php';
        $controller = new CompraController();
        if ($action === 'ver' && isset($_GET['id'])) {
            $compra = $controller->ver((int)$_GET['id']);
            $pageTitle = 'Compra ' . ($compra['codigo'] ?? '') . ' - HelpDesk TI';
        } else {
            $compras = $controller->listar();
            $pageTitle = 'Requisições de Compra - HelpDesk TI';
        }
        break;

    case 'inventario':
        require_once __DIR__ . '/app/models/Inventario.php';
        $inventarioModel = new Inventario();
        if ($action === 'ver' && isset($_GET['id'])) {
            $ativo = $inventarioModel->findById((int)$_GET['id']);
            $chamadosVinculados = $inventarioModel->getChamadosVinculados((int)$_GET['id']);
            $pageTitle = ($ativo['nome'] ?? 'Ativo') . ' - HelpDesk TI';
        } else {
            $filtros = [
                'tipo' => $_GET['tipo'] ?? '',
                'status' => $_GET['status'] ?? '',
                'busca' => $_GET['busca'] ?? ''
            ];
            $inventarioItens = $inventarioModel->listar($filtros);
            // Carregar termos de responsabilidade
            $db = Database::getInstance();
            $termos = $db->fetchAll(
                "SELECT t.*, i.nome AS ativo_nome, i.numero_patrimonio, i.tipo AS ativo_tipo,
                        u.nome AS tecnico_nome
                 FROM termos_responsabilidade t
                 LEFT JOIN inventario i ON t.ativo_id = i.id
                 LEFT JOIN usuarios u ON t.tecnico_id = u.id
                 ORDER BY t.criado_em DESC"
            );
            $pageTitle = 'Inventário - HelpDesk TI';
        }
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        break;

    case 'suprimentos':
        require_once __DIR__ . '/app/models/Suprimento.php';
        $pageTitle = 'Suprimentos de TI - HelpDesk TI';
        break;

    case 'senhas':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Cofre de Senhas - HelpDesk TI';
        break;

    case 'rede':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Gestão de Rede - HelpDesk TI';
        break;

    case 'ssh':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Terminal SSH - HelpDesk TI';
        break;

    case 'ia':
        $pageTitle = 'Assistente IA - HelpDesk TI';
        break;

    case 'email':
        $pageTitle = 'E-mail - HelpDesk TI';
        break;

    case 'github':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'GitHub - HelpDesk TI';
        break;

    case 'notificacoes':
        $pageTitle = 'Notificações - HelpDesk TI';
        break;

    case 'monitor':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Monitor NOC - HelpDesk TI';
        break;

    case 'calendario':
        $pageTitle = 'Calendário - HelpDesk TI';
        break;

    case 'sla':
        requireRole(['admin', 'gestor']);
        $pageTitle = 'SLA Dashboard - HelpDesk TI';
        break;

    case 'contratos':
        requireRole(['admin', 'gestor']);
        $pageTitle = 'Contratos e Fornecedores - HelpDesk TI';
        break;

    case 'cmdb':
        requireRole(['admin', 'gestor', 'tecnico']);
        $pageTitle = 'CMDB - HelpDesk TI';
        break;

    case 'timesheet':
        $pageTitle = 'Timesheet - HelpDesk TI';
        break;

    case 'automacoes':
        requireRole(['admin', 'gestor']);
        $pageTitle = 'Automações - HelpDesk TI';
        break;

    case 'atualizacao':
        requireRole(['admin']);
        $pageTitle = 'Atualização do Sistema - HelpDesk TI';
        break;

    case 'deploy':
        requireRole(['admin']);
        $pageTitle = 'Deploy — Produção - HelpDesk TI';
        break;

    case 'remoto':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Acesso Remoto - HelpDesk TI';
        break;

    case 'proxmox':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Proxmox VE - HelpDesk TI';
        break;

    case 'ad':
        requireRole(['admin']);
        $pageTitle = 'Active Directory - HelpDesk TI';
        break;

    case 'usuarios':
        requireRole(['admin', 'gestor']);
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $usuarios = $usuarioModel->listar(['busca' => $_GET['busca'] ?? '']);
        $pageTitle = 'Usuários - HelpDesk TI';
        break;

    case 'base-conhecimento':
        require_once __DIR__ . '/app/models/BaseConhecimento.php';
        $bcModel = new BaseConhecimento();
        if ($action === 'ver' && isset($_GET['id'])) {
            $artigo = $bcModel->findById((int)$_GET['id']);
            $bcModel->incrementarVisualizacao((int)$_GET['id']);
            $pageTitle = ($artigo['titulo'] ?? 'Artigo') . ' - HelpDesk TI';
        } else {
            $filtros = [
                'busca' => $_GET['busca'] ?? '',
                'categoria_id' => $_GET['categoria_id'] ?? '',
                'publicado' => 1
            ];
            $artigos = $bcModel->listar($filtros);
            $pageTitle = 'Base de Conhecimento - HelpDesk TI';
        }
        $db = Database::getInstance();
        $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'conhecimento' AND ativo = 1");
        break;

    case 'relatorios':
        requireRole(['admin', 'gestor']);
        require_once __DIR__ . '/app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $stats = $controller->getStats();
        // Obter dados extras para relatórios
        require_once __DIR__ . '/app/models/Chamado.php';
        $chamadoModel = new Chamado();
        $tempoMedio = $stats['chamados']['tempo_medio']['horas'] ?? 0;
        // SLA estourados
        $db = Database::getInstance();
        $slaEstourados = $db->fetchColumn(
            "SELECT COUNT(*) FROM chamados c 
             INNER JOIN sla s ON c.sla_id = s.id 
             WHERE c.status NOT IN ('resolvido','fechado') 
             AND TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > s.tempo_resolucao"
        ) ?: 0;
        // Por prioridade
        $porPrioridade = $db->fetchAll(
            "SELECT prioridade, COUNT(*) as total FROM chamados GROUP BY prioridade"
        );
        // Por mês com abertos/resolvidos
        $porMesDetalhado = $db->fetchAll(
            "SELECT DATE_FORMAT(data_abertura, '%Y-%m') as mes,
                    COUNT(*) as abertos,
                    SUM(CASE WHEN status IN ('resolvido','fechado') THEN 1 ELSE 0 END) as resolvidos
             FROM chamados 
             WHERE data_abertura >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(data_abertura, '%Y-%m') ORDER BY mes"
        );
        $relatorio = [
            'total_chamados' => $stats['chamados']['total'] ?? 0,
            'chamados_resolvidos' => $stats['chamados']['resolvidos'] ?? 0,
            'tempo_medio' => round($tempoMedio, 1) . 'h',
            'sla_estourados' => $slaEstourados,
            'por_categoria' => $stats['chamados']['por_categoria'] ?? [],
            'por_mes' => $porMesDetalhado,
            'por_prioridade' => $porPrioridade,
            'por_tecnico' => $stats['chamados']['por_tecnico'] ?? []
        ];
        $pageTitle = 'Relatórios - HelpDesk TI';
        break;

    case 'configuracoes':
        requireRole(['admin']);
        $db = Database::getInstance();
        $configuracoes = $db->fetchAll("SELECT * FROM configuracoes ORDER BY chave");
        $categorias = $db->fetchAll("SELECT * FROM categorias ORDER BY tipo, nome");
        $categoriasAtivas = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
        $slaRegras = $db->fetchAll(
            "SELECT s.*, c.nome as categoria_nome 
             FROM sla s LEFT JOIN categorias c ON s.categoria_id = c.id 
             ORDER BY s.categoria_id IS NULL DESC, c.nome, FIELD(s.prioridade,'critica','alta','media','baixa')"
        );
        $pageTitle = 'Configurações - HelpDesk TI';
        break;

    case 'perfil':
        $db = Database::getInstance();
        $perfilUsuario = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['usuario_id']]);
        $pageTitle = 'Meu Perfil - HelpDesk TI';
        break;
}

// Renderizar
include __DIR__ . '/app/views/layouts/header.php';
include __DIR__ . '/app/views/layouts/sidebar.php';

$viewFile = __DIR__ . '/app/views/' . $page . '/';
$acao = $_GET['acao'] ?? $action;
if (($acao === 'ver' || $action === 'ver') && file_exists($viewFile . 'ver.php')) {
    include $viewFile . 'ver.php';
} elseif (($acao === 'criar' || $action === 'criar') && file_exists($viewFile . 'criar.php')) {
    include $viewFile . 'criar.php';
} elseif (file_exists($viewFile . 'index.php')) {
    include $viewFile . 'index.php';
} else {
    echo '<div class="empty-state"><i class="fas fa-hard-hat"></i><h3>Página em construção</h3><p>Esta funcionalidade será disponibilizada em breve.</p></div>';
}

include __DIR__ . '/app/views/layouts/footer.php';
