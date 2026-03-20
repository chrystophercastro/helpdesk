<?php
/**
 * Oracle X - Entry Point Principal
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
    'dashboard', 'chamados', 'projetos', 'kanban', 'sprints', 'posts', 'chat',
    'compras', 'inventario', 'suprimentos', 'senhas', 'rede', 'ssh', 'ia', 'proxmox', 'email', 'ad', 'usuarios', 'base-conhecimento',
    'relatorios', 'configuracoes', 'perfil', 'mikrotik', 'github', 'notificacoes', 'monitor', 'calendario', 'sla', 'contratos', 'cmdb', 'timesheet', 'automacoes', 'atualizacao', 'remoto', 'deploy', 'departamentos',
    'folha-pagamento', 'financeiro', 'permissoes-modulos'
];

if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}

$pageTitle = 'Oracle X';

// Carregar dados por página
switch ($page) {
    case 'dashboard':
        require_once __DIR__ . '/app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $deptFilter = getDeptFilter();
        $stats = $controller->getStats($deptFilter);
        $db = Database::getInstance();
        // Gestor: só categorias do seu departamento
        if ($deptFilter) {
            $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 AND departamento_id = ? ORDER BY nome", [$deptFilter]);
        } else {
            $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
        }
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        // Gestor: filtrar técnicos do seu departamento
        if ($deptFilter) {
            $tecnicos = array_filter($tecnicos, fn($t) => ($t['departamento_id'] ?? null) == $deptFilter);
            $tecnicos = array_values($tecnicos);
        }
        $ativos = $db->fetchAll("SELECT id, nome, numero_patrimonio FROM inventario WHERE status = 'ativo' ORDER BY nome");
        $pageTitle = 'Dashboard - Oracle X';
        break;

    case 'chamados':
        require_once __DIR__ . '/app/controllers/ChamadoController.php';
        $controller = new ChamadoController();
        $deptFilter = getDeptFilter();
        if ($action === 'ver' && isset($_GET['id'])) {
            $chamado = $controller->ver((int)$_GET['id']);
            $pageTitle = 'Chamado #' . ($chamado['codigo'] ?? '') . ' - Oracle X';
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
            $pageTitle = 'Chamados - Oracle X';
        }
        // Carregar categorias e técnicos para formulários
        $db = Database::getInstance();
        if ($deptFilter) {
            $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 AND departamento_id = ? ORDER BY nome", [$deptFilter]);
        } else {
            $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
        }
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        if ($deptFilter) {
            $tecnicos = array_filter($tecnicos, fn($t) => ($t['departamento_id'] ?? null) == $deptFilter);
            $tecnicos = array_values($tecnicos);
        }
        $ativos = $db->fetchAll("SELECT id, nome, numero_patrimonio FROM inventario WHERE status = 'ativo' ORDER BY nome");
        // Projetos para transformação em sprint
        $projetos = $db->fetchAll("SELECT id, nome FROM projetos WHERE status NOT IN ('concluido','cancelado') ORDER BY nome");
        // Departamentos para filtro
        if ($deptFilter) {
            $departamentosLista = $db->fetchAll("SELECT id, nome, sigla, cor FROM departamentos WHERE ativo = 1 AND id = ? ORDER BY ordem, nome", [$deptFilter]);
        } else {
            $departamentosLista = $db->fetchAll("SELECT id, nome, sigla, cor FROM departamentos WHERE ativo = 1 ORDER BY ordem, nome");
        }
        break;

    case 'projetos':
        require_once __DIR__ . '/app/controllers/ProjetoController.php';
        $controller = new ProjetoController();
        $deptFilter = getDeptFilter();
        if ($action === 'ver' && isset($_GET['id'])) {
            $projeto = $controller->ver((int)$_GET['id']);
            // Gestor só vê projetos do seu departamento
            if ($deptFilter && $projeto && (int)($projeto['departamento_id'] ?? 0) !== (int)$deptFilter) {
                setFlash('danger', 'Acesso negado a este projeto.');
                header('Location: ' . BASE_URL . '/index.php?page=projetos'); exit;
            }
            $pageTitle = ($projeto['nome'] ?? 'Projeto') . ' - Oracle X';
        } else {
            $projetos = $controller->listar($deptFilter);
            $pageTitle = 'Projetos - Oracle X';
        }
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        // Para equipe: todos os técnicos (cross-departamento)
        $tecnicos = $usuarioModel->listarTecnicos();
        break;

    case 'kanban':
        require_once __DIR__ . '/app/controllers/TarefaController.php';
        $controller = new TarefaController();
        $projetoId = $_GET['projeto_id'] ?? null;
        $deptFilter = getDeptFilter();
        $kanban = $controller->listarKanban($projetoId, $deptFilter);
        $pageTitle = 'Kanban - Oracle X';
        $db = Database::getInstance();
        if ($deptFilter) {
            $projetosLista = $db->fetchAll("SELECT id, nome FROM projetos WHERE status != 'cancelado' AND departamento_id = ? ORDER BY nome", [$deptFilter]);
        } else {
            $projetosLista = $db->fetchAll("SELECT id, nome FROM projetos WHERE status != 'cancelado' ORDER BY nome");
        }
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        $extraJS = 'kanban.js';
        break;

    case 'sprints':
        require_once __DIR__ . '/app/controllers/SprintController.php';
        $controller = new SprintController();
        $projetoId = $_GET['projeto'] ?? null;
        $deptFilter = getDeptFilter();
        if (isset($_GET['acao']) && $_GET['acao'] === 'ver' && isset($_GET['id'])) {
            $sprint = $controller->ver((int)$_GET['id']);
            // Verificar acesso: gestor só vê sprints de projetos do seu dept
            if ($deptFilter && $sprint) {
                $db2 = Database::getInstance();
                $projDept = $db2->fetchColumn("SELECT departamento_id FROM projetos WHERE id = ?", [$sprint['projeto_id'] ?? 0]);
                if ((int)$projDept !== (int)$deptFilter) {
                    setFlash('danger', 'Acesso negado a esta sprint.');
                    header('Location: ' . BASE_URL . '/index.php?page=sprints'); exit;
                }
            }
            $tarefasSprint = $sprint['tarefas'] ?? [];
            $burndownData = $sprint['burndown'] ?? [];
            $action = 'ver';
            $pageTitle = ($sprint['nome'] ?? 'Sprint') . ' - Oracle X';

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
            $sprints = $controller->listar($projetoId, $deptFilter);
            // Check for active sprint
            $sprintAtivo = null;
            foreach ($sprints as $s) {
                if ($s['status'] === 'ativa') { $sprintAtivo = $s; break; }
            }
            $pageTitle = 'Sprints - Oracle X';
        }
        $db = Database::getInstance();
        if ($deptFilter) {
            $projetos = $db->fetchAll("SELECT id, nome FROM projetos WHERE status != 'cancelado' AND departamento_id = ? ORDER BY nome", [$deptFilter]);
        } else {
            $projetos = $db->fetchAll("SELECT id, nome FROM projetos WHERE status != 'cancelado' ORDER BY nome");
        }
        break;

    case 'posts':
        require_once __DIR__ . '/app/models/Post.php';
        $pageTitle = 'Mural - Oracle X';
        break;

    case 'chat':
        $pageTitle = 'Chat - Oracle X';
        break;

    case 'compras':
        if (!isTIDept()) { setFlash('danger', 'Acesso restrito ao departamento de TI.'); header('Location: ' . BASE_URL . '/index.php?page=dashboard'); exit; }
        require_once __DIR__ . '/app/controllers/CompraController.php';
        $controller = new CompraController();
        if ($action === 'ver' && isset($_GET['id'])) {
            $compra = $controller->ver((int)$_GET['id']);
            $pageTitle = 'Compra ' . ($compra['codigo'] ?? '') . ' - Oracle X';
        } else {
            $compras = $controller->listar();
            $pageTitle = 'Requisições de Compra - Oracle X';
        }
        break;

    case 'inventario':
        if (!isTIDept()) { setFlash('danger', 'Acesso restrito ao departamento de TI.'); header('Location: ' . BASE_URL . '/index.php?page=dashboard'); exit; }
        require_once __DIR__ . '/app/models/Inventario.php';
        $inventarioModel = new Inventario();
        if ($action === 'ver' && isset($_GET['id'])) {
            $ativo = $inventarioModel->findById((int)$_GET['id']);
            $chamadosVinculados = $inventarioModel->getChamadosVinculados((int)$_GET['id']);
            $pageTitle = ($ativo['nome'] ?? 'Ativo') . ' - Oracle X';
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
            $pageTitle = 'Inventário - Oracle X';
        }
        require_once __DIR__ . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $tecnicos = $usuarioModel->listarTecnicos();
        break;

    case 'suprimentos':
        if (!isTIDept()) { setFlash('danger', 'Acesso restrito ao departamento de TI.'); header('Location: ' . BASE_URL . '/index.php?page=dashboard'); exit; }
        require_once __DIR__ . '/app/models/Suprimento.php';
        $pageTitle = 'Suprimentos de TI - Oracle X';
        break;

    case 'senhas':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Cofre de Senhas - Oracle X';
        break;

    case 'rede':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Gestão de Rede - Oracle X';
        break;

    case 'ssh':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Terminal SSH - Oracle X';
        break;

    case 'ia':
        $pageTitle = 'Assistente IA - Oracle X';
        break;

    case 'email':
        $pageTitle = 'E-mail - Oracle X';
        break;

    case 'github':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'GitHub - Oracle X';
        break;

    case 'notificacoes':
        $pageTitle = 'Notificações - Oracle X';
        break;

    case 'monitor':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Monitor NOC - Oracle X';
        break;

    case 'calendario':
        $pageTitle = 'Calendário - Oracle X';
        break;

    case 'sla':
        requireRole(['admin', 'gestor']);
        $pageTitle = 'SLA Dashboard - Oracle X';
        break;

    case 'contratos':
        requireRole(['admin', 'gestor']);
        $pageTitle = 'Contratos e Fornecedores - Oracle X';
        break;

    case 'cmdb':
        requireRole(['admin', 'gestor', 'tecnico']);
        $pageTitle = 'CMDB - Oracle X';
        break;

    case 'timesheet':
        $pageTitle = 'Timesheet - Oracle X';
        break;

    case 'automacoes':
        requireRole(['admin', 'gestor']);
        $pageTitle = 'Automações - Oracle X';
        break;

    case 'atualizacao':
        requireRole(['admin']);
        $pageTitle = 'Atualização do Sistema - Oracle X';
        break;

    case 'deploy':
        requireRole(['admin']);
        $pageTitle = 'Deploy — Produção - Oracle X';
        break;

    case 'departamentos':
        requireRole(['admin']);
        $pageTitle = 'Departamentos - Oracle X';
        break;

    case 'folha-pagamento':
        require_once __DIR__ . '/app/models/ModuloPermissao.php';
        requireModulo('folha_pagamento');
        $pageTitle = 'Folha de Pagamento - Oracle X';
        break;

    case 'financeiro':
        require_once __DIR__ . '/app/models/ModuloPermissao.php';
        requireModulo('financeiro');
        $pageTitle = 'Financeiro - Oracle X';
        break;

    case 'permissoes-modulos':
        requireRole(['admin']);
        $pageTitle = 'Permissões de Módulos - Oracle X';
        break;

    case 'remoto':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Acesso Remoto - Oracle X';
        break;

    case 'proxmox':
        requireRole(['admin', 'tecnico']);
        $pageTitle = 'Proxmox VE - Oracle X';
        break;

    case 'ad':
        requireRole(['admin']);
        $pageTitle = 'Active Directory - Oracle X';
        break;

    case 'usuarios':
        requireRole(['admin', 'gestor']);
        require_once __DIR__ . '/app/models/Usuario.php';
        require_once __DIR__ . '/app/models/Departamento.php';
        $usuarioModel = new Usuario();
        $departamentoModel = new Departamento();
        $deptFilter = getDeptFilter();
        $filtrosUsuarios = ['busca' => $_GET['busca'] ?? ''];
        if ($deptFilter) {
            $filtrosUsuarios['departamento_id'] = $deptFilter;
        }
        $usuarios = $usuarioModel->listar($filtrosUsuarios);
        if ($deptFilter) {
            // Gestor: só o seu departamento na lista
            $dbU = Database::getInstance();
            $departamentosLista = $dbU->fetchAll("SELECT * FROM departamentos WHERE ativo = 1 AND id = ? ORDER BY ordem, nome", [$deptFilter]);
        } else {
            $departamentosLista = $departamentoModel->listarAtivos();
        }
        $pageTitle = 'Usuários - Oracle X';
        break;

    case 'base-conhecimento':
        require_once __DIR__ . '/app/models/BaseConhecimento.php';
        $bcModel = new BaseConhecimento();
        if ($action === 'ver' && isset($_GET['id'])) {
            $artigo = $bcModel->findById((int)$_GET['id']);
            $bcModel->incrementarVisualizacao((int)$_GET['id']);
            $pageTitle = ($artigo['titulo'] ?? 'Artigo') . ' - Oracle X';
        } else {
            $filtros = [
                'busca' => $_GET['busca'] ?? '',
                'categoria_id' => $_GET['categoria_id'] ?? '',
                'publicado' => 1
            ];
            $artigos = $bcModel->listar($filtros);
            $pageTitle = 'Base de Conhecimento - Oracle X';
        }
        $db = Database::getInstance();
        $categorias = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'conhecimento' AND ativo = 1");
        break;

    case 'relatorios':
        requireRole(['admin', 'gestor']);
        require_once __DIR__ . '/app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $deptFilter = getDeptFilter();
        $stats = $controller->getStats($deptFilter);
        // Obter dados extras para relatórios
        require_once __DIR__ . '/app/models/Chamado.php';
        $chamadoModel = new Chamado();
        $tempoMedio = $stats['chamados']['tempo_medio']['horas'] ?? 0;
        // SLA estourados (filtrado por dept)
        $db = Database::getInstance();
        $deptWR = $deptFilter ? " AND c.departamento_id = ?" : "";
        $deptPR = $deptFilter ? [(int)$deptFilter] : [];
        $slaEstourados = $db->fetchColumn(
            "SELECT COUNT(*) FROM chamados c 
             INNER JOIN sla s ON c.sla_id = s.id 
             WHERE c.status NOT IN ('resolvido','fechado') 
             AND TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > s.tempo_resolucao" . $deptWR,
            $deptPR
        ) ?: 0;
        // Por prioridade (filtrado por dept)
        $deptWS = $deptFilter ? " WHERE departamento_id = ?" : "";
        $deptPS = $deptFilter ? [(int)$deptFilter] : [];
        $porPrioridade = $db->fetchAll(
            "SELECT prioridade, COUNT(*) as total FROM chamados" . $deptWS . " GROUP BY prioridade",
            $deptPS
        );
        // Por mês com abertos/resolvidos (filtrado por dept)
        $deptWM = $deptFilter ? " AND departamento_id = ?" : "";
        $deptPM = $deptFilter ? [(int)$deptFilter] : [];
        $porMesDetalhado = $db->fetchAll(
            "SELECT DATE_FORMAT(data_abertura, '%Y-%m') as mes,
                    COUNT(*) as abertos,
                    SUM(CASE WHEN status IN ('resolvido','fechado') THEN 1 ELSE 0 END) as resolvidos
             FROM chamados 
             WHERE data_abertura >= DATE_SUB(NOW(), INTERVAL 12 MONTH)" . $deptWM . "
             GROUP BY DATE_FORMAT(data_abertura, '%Y-%m') ORDER BY mes",
            $deptPM
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
        $pageTitle = 'Relatórios - Oracle X';
        break;

    case 'configuracoes':
        requireRole(['admin']);
        $db = Database::getInstance();
        $configuracoes = $db->fetchAll("SELECT * FROM configuracoes ORDER BY chave");
        $categorias = $db->fetchAll("SELECT c.*, d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor FROM categorias c LEFT JOIN departamentos d ON c.departamento_id = d.id ORDER BY c.tipo, c.nome");
        $categoriasAtivas = $db->fetchAll("SELECT * FROM categorias WHERE tipo = 'chamado' AND ativo = 1 ORDER BY nome");
        $slaRegras = $db->fetchAll(
            "SELECT s.*, c.nome as categoria_nome 
             FROM sla s LEFT JOIN categorias c ON s.categoria_id = c.id 
             ORDER BY s.categoria_id IS NULL DESC, c.nome, FIELD(s.prioridade,'critica','alta','media','baixa')"
        );
        // Portal configs (chave => valor) for the Portal tab
        $portalConfigs = [];
        foreach ($configuracoes as $c) {
            if (strpos($c['chave'], 'portal_') === 0) {
                $portalConfigs[$c['chave']] = $c['valor'];
            }
        }
        $pageTitle = 'Configurações - Oracle X';
        break;

    case 'perfil':
        $db = Database::getInstance();
        $perfilUsuario = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['usuario_id']]);
        $pageTitle = 'Meu Perfil - Oracle X';
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
