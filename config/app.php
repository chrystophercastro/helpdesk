<?php
/**
 * Configurações Gerais do Sistema
 * Oracle X
 */

// Fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Carregar Database (singleton PDO)
require_once __DIR__ . '/../app/models/Database.php';

// Caminhos
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/helpdesk');

// URL completa detectada automaticamente (protocolo + domínio + base)
if (!defined('FULL_BASE_URL')) {
    $__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $__host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    define('FULL_BASE_URL', $__scheme . '://' . $__host . BASE_URL);
}

define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Sessão
define('SESSION_LIFETIME', 3600 * 8); // 8 horas
define('SESSION_NAME', 'helpdesk_session');

// Segurança
define('CSRF_TOKEN_NAME', '_csrf_token');
define('BCRYPT_COST', 12);

// Tipos de arquivo permitidos
define('ALLOWED_EXTENSIONS', ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar','csv']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg','image/png','image/gif',
    'application/pdf',
    'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain','text/csv',
    'application/zip','application/x-rar-compressed'
]);

// Status dos chamados
define('CHAMADO_STATUS', [
    'aberto' => ['label' => 'Aberto', 'cor' => '#3B82F6', 'icone' => 'fas fa-folder-open'],
    'em_analise' => ['label' => 'Em Análise', 'cor' => '#F59E0B', 'icone' => 'fas fa-search'],
    'em_atendimento' => ['label' => 'Em Atendimento', 'cor' => '#8B5CF6', 'icone' => 'fas fa-tools'],
    'aguardando_usuario' => ['label' => 'Aguardando Usuário', 'cor' => '#F97316', 'icone' => 'fas fa-clock'],
    'resolvido' => ['label' => 'Resolvido', 'cor' => '#10B981', 'icone' => 'fas fa-check-circle'],
    'fechado' => ['label' => 'Fechado', 'cor' => '#6B7280', 'icone' => 'fas fa-lock']
]);

// Prioridades
define('PRIORIDADES', [
    'critica' => ['label' => 'Crítica', 'cor' => '#DC2626', 'icone' => 'fas fa-exclamation-triangle'],
    'alta' => ['label' => 'Alta', 'cor' => '#F59E0B', 'icone' => 'fas fa-arrow-up'],
    'media' => ['label' => 'Média', 'cor' => '#3B82F6', 'icone' => 'fas fa-minus'],
    'baixa' => ['label' => 'Baixa', 'cor' => '#10B981', 'icone' => 'fas fa-arrow-down']
]);

// Status dos projetos
define('PROJETO_STATUS', [
    'planejamento' => ['label' => 'Planejamento', 'cor' => '#6366F1'],
    'em_desenvolvimento' => ['label' => 'Em Desenvolvimento', 'cor' => '#3B82F6'],
    'em_testes' => ['label' => 'Em Testes', 'cor' => '#F59E0B'],
    'concluido' => ['label' => 'Concluído', 'cor' => '#10B981'],
    'cancelado' => ['label' => 'Cancelado', 'cor' => '#EF4444']
]);

// Colunas Kanban
define('KANBAN_COLUNAS', [
    'backlog' => ['label' => 'Backlog', 'cor' => '#6B7280', 'icone' => 'fas fa-inbox'],
    'a_fazer' => ['label' => 'A Fazer', 'cor' => '#3B82F6', 'icone' => 'fas fa-list'],
    'em_andamento' => ['label' => 'Em Andamento', 'cor' => '#F59E0B', 'icone' => 'fas fa-spinner'],
    'em_revisao' => ['label' => 'Em Revisão', 'cor' => '#8B5CF6', 'icone' => 'fas fa-eye'],
    'concluido' => ['label' => 'Concluído', 'cor' => '#10B981', 'icone' => 'fas fa-check']
]);

// Status das compras
define('COMPRA_STATUS', [
    'solicitado' => ['label' => 'Solicitado', 'cor' => '#3B82F6'],
    'em_analise' => ['label' => 'Em Análise', 'cor' => '#F59E0B'],
    'aprovado' => ['label' => 'Aprovado', 'cor' => '#10B981'],
    'reprovado' => ['label' => 'Reprovado', 'cor' => '#EF4444'],
    'comprado' => ['label' => 'Comprado', 'cor' => '#8B5CF6'],
    'entregue' => ['label' => 'Entregue', 'cor' => '#059669'],
    'cancelado' => ['label' => 'Cancelado', 'cor' => '#6B7280']
]);

// Matriz de prioridade automática (Impacto x Urgência)
define('MATRIZ_PRIORIDADE', [
    'alto' => [
        'alta' => 'critica',
        'media' => 'alta',
        'baixa' => 'media'
    ],
    'medio' => [
        'alta' => 'alta',
        'media' => 'media',
        'baixa' => 'baixa'
    ],
    'baixo' => [
        'alta' => 'media',
        'media' => 'baixa',
        'baixa' => 'baixa'
    ]
]);

/**
 * Função para calcular prioridade automática
 */
function calcularPrioridade($impacto, $urgencia) {
    $matriz = MATRIZ_PRIORIDADE;
    return $matriz[$impacto][$urgencia] ?? 'media';
}

/**
 * Gerar código do chamado
 * @param string $sigla Sigla do departamento (ex: TI, RH, FIN). Default: HD
 */
function gerarCodigoChamado($sigla = 'HD') {
    $sigla = strtoupper(trim($sigla)) ?: 'HD';
    return $sigla . '-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * Gerar código da compra
 */
function gerarCodigoCompra() {
    return 'RC-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * Formatar telefone
 */
function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 11) {
        return '55' . $telefone;
    }
    return $telefone;
}

/**
 * Sanitizar input
 */
function sanitizar($input) {
    if (is_array($input)) {
        return array_map('sanitizar', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Formatar data
 */
function formatarData($data, $formato = 'd/m/Y H:i') {
    if (empty($data)) return '-';
    $dt = new DateTime($data);
    return $dt->format($formato);
}

/**
 * Tempo relativo
 */
function tempoRelativo($data) {
    $agora = new DateTime();
    $dt = new DateTime($data);
    $diff = $agora->diff($dt);
    
    if ($diff->y > 0) return $diff->y . ' ano(s) atrás';
    if ($diff->m > 0) return $diff->m . ' mês(es) atrás';
    if ($diff->d > 0) return $diff->d . ' dia(s) atrás';
    if ($diff->h > 0) return $diff->h . ' hora(s) atrás';
    if ($diff->i > 0) return $diff->i . ' minuto(s) atrás';
    return 'Agora mesmo';
}

/**
 * CSRF Token
 */
function gerarCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validarCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField() {
    $token = gerarCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

/**
 * Verificar se é requisição AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Resposta JSON
 */
function jsonResponse($data, $statusCode = 200) {
    // Limpar qualquer saída PHP prévia (warnings/notices) que corromperia o JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        $json = json_encode(['error' => 'Erro interno: falha ao codificar resposta JSON (' . json_last_error_msg() . ')']);
    }
    echo $json;
    exit;
}

/**
 * Redirecionar
 */
function redirect($url) {
    header('Location: ' . BASE_URL . '/' . ltrim($url, '/'));
    exit;
}

/**
 * Mensagens flash
 */
function setFlash($tipo, $mensagem) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Verificar autenticação
 */
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (isAjax()) {
            jsonResponse(['error' => 'Não autenticado'], 401);
        }
        redirect('login.php');
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['usuario_tipo'], $roles)) {
        if (isAjax()) {
            jsonResponse(['error' => 'Sem permissão'], 403);
        }
        setFlash('danger', 'Você não tem permissão para acessar esta área.');
        redirect('index.php');
    }
}

function currentUser() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nome' => $_SESSION['usuario_nome'] ?? '',
        'email' => $_SESSION['usuario_email'] ?? '',
        'telefone' => $_SESSION['usuario_telefone'] ?? '',
        'tipo' => $_SESSION['usuario_tipo'] ?? '',
        'avatar' => $_SESSION['usuario_avatar'] ?? '',
        'departamento_id' => $_SESSION['usuario_departamento_id'] ?? null,
        'tema' => $_SESSION['usuario_tema'] ?? 'light'
    ];
}

function isAdmin() {
    return ($_SESSION['usuario_tipo'] ?? '') === 'admin';
}

function isGestor() {
    return ($_SESSION['usuario_tipo'] ?? '') === 'gestor';
}

function getUserDeptId() {
    // Auto-fill departamento_id na sessão se ausente (sessões anteriores à atualização)
    if (!isset($_SESSION['usuario_departamento_id']) && isset($_SESSION['usuario_id'])) {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT departamento_id FROM usuarios WHERE id = ?", [$_SESSION['usuario_id']]);
        $_SESSION['usuario_departamento_id'] = $row['departamento_id'] ?? null;
    }
    return $_SESSION['usuario_departamento_id'] ?? null;
}

/**
 * Retorna a sigla do departamento do usuário logado.
 */
function getUserDeptSigla() {
    if (!isset($_SESSION['usuario_departamento_sigla']) && isset($_SESSION['usuario_id'])) {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT d.sigla FROM departamentos d INNER JOIN usuarios u ON u.departamento_id = d.id WHERE u.id = ?",
            [$_SESSION['usuario_id']]
        );
        $_SESSION['usuario_departamento_sigla'] = $row['sigla'] ?? null;
    }
    return $_SESSION['usuario_departamento_sigla'] ?? null;
}

/**
 * Verifica se o usuário pertence ao departamento de TI.
 */
function isTIDept() {
    if (isAdmin()) return true;
    return getUserDeptSigla() === 'TI';
}

/**
 * Retorna o departamento_id para filtros.
 * Admin: null (vê tudo). Gestor: seu departamento.
 */
function getDeptFilter() {
    if (isAdmin()) return null;
    return getUserDeptId();
}

/**
 * Retorna tempo relativo em português (ex: "5min atrás", "2h atrás").
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->getTimestamp() - $past->getTimestamp();
    if ($diff < 60) return 'Agora';
    if ($diff < 3600) return floor($diff / 60) . 'min atrás';
    if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
    return $past->format('d/m/Y');
}
