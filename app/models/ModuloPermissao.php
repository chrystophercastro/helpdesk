<?php
/**
 * Model: Permissão de Módulos
 * Controla acesso per-user a módulos restritos (folha_pagamento, financeiro)
 */
require_once __DIR__ . '/Database.php';

class ModuloPermissao {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Verifica se o usuário tem acesso a um módulo
     */
    public function temAcesso($usuarioId, $modulo, $nivelMinimo = 'leitura') {
        $niveis = ['leitura' => 1, 'escrita' => 2, 'admin' => 3];
        
        $row = $this->db->fetch(
            "SELECT acesso FROM usuario_modulos WHERE usuario_id = ? AND modulo = ?",
            [$usuarioId, $modulo]
        );
        
        if (!$row) return false;
        
        $nivelUser = $niveis[$row['acesso']] ?? 0;
        $nivelReq = $niveis[$nivelMinimo] ?? 1;
        
        return $nivelUser >= $nivelReq;
    }

    /**
     * Retorna o nível de acesso do usuário
     */
    public function getNivelAcesso($usuarioId, $modulo) {
        return $this->db->fetchColumn(
            "SELECT acesso FROM usuario_modulos WHERE usuario_id = ? AND modulo = ?",
            [$usuarioId, $modulo]
        ) ?: null;
    }

    /**
     * Listar todos os módulos de um usuário
     */
    public function getModulosUsuario($usuarioId) {
        return $this->db->fetchAll(
            "SELECT um.*, u2.nome as concedido_por_nome 
             FROM usuario_modulos um
             LEFT JOIN usuarios u2 ON u2.id = um.concedido_por
             WHERE um.usuario_id = ?",
            [$usuarioId]
        );
    }

    /**
     * Listar todos os usuários com acesso a um módulo
     */
    public function getUsuariosModulo($modulo) {
        return $this->db->fetchAll(
            "SELECT um.*, u.nome, u.email, u.tipo, u.departamento_id,
                    d.sigla as departamento_sigla, d.cor as departamento_cor,
                    u2.nome as concedido_por_nome
             FROM usuario_modulos um
             INNER JOIN usuarios u ON u.id = um.usuario_id
             LEFT JOIN departamentos d ON d.id = u.departamento_id
             LEFT JOIN usuarios u2 ON u2.id = um.concedido_por
             WHERE um.modulo = ?
             ORDER BY u.nome",
            [$modulo]
        );
    }

    /**
     * Conceder acesso
     */
    public function concederAcesso($usuarioId, $modulo, $acesso, $concedidoPor) {
        $existe = $this->db->fetch(
            "SELECT id FROM usuario_modulos WHERE usuario_id = ? AND modulo = ?",
            [$usuarioId, $modulo]
        );

        if ($existe) {
            $this->db->update('usuario_modulos', 
                ['acesso' => $acesso, 'concedido_por' => $concedidoPor],
                'id = ?', [$existe['id']]
            );
        } else {
            $this->db->insert('usuario_modulos', [
                'usuario_id' => $usuarioId,
                'modulo' => $modulo,
                'acesso' => $acesso,
                'concedido_por' => $concedidoPor
            ]);
        }
        return true;
    }

    /**
     * Revogar acesso
     */
    public function revogarAcesso($usuarioId, $modulo) {
        return $this->db->delete('usuario_modulos', 'usuario_id = ? AND modulo = ?', [$usuarioId, $modulo]);
    }

    /**
     * Listar todos os usuários com flag de acesso ao módulo
     */
    public function listarUsuariosComFlag($modulo) {
        return $this->db->fetchAll(
            "SELECT u.id, u.nome, u.email, u.tipo, u.departamento_id, u.ativo,
                    d.sigla as departamento_sigla, d.cor as departamento_cor,
                    um.acesso, um.concedido_por, um.criado_em as acesso_desde,
                    u2.nome as concedido_por_nome
             FROM usuarios u
             LEFT JOIN departamentos d ON d.id = u.departamento_id
             LEFT JOIN usuario_modulos um ON um.usuario_id = u.id AND um.modulo = ?
             LEFT JOIN usuarios u2 ON u2.id = um.concedido_por
             WHERE u.ativo = 1
             ORDER BY u.nome",
            [$modulo]
        );
    }

    /**
     * Módulos disponíveis
     */
    public static function getModulosDisponiveis() {
        return [
            'folha_pagamento' => [
                'nome' => 'Folha de Pagamento',
                'descricao' => 'Gestão de colaboradores e folha (CLT/PJ)',
                'icone' => 'fa-money-check-alt',
                'cor' => '#10B981',
                'departamento' => 'RH'
            ],
            'financeiro' => [
                'nome' => 'Financeiro',
                'descricao' => 'Notas fiscais, boletos, contas a pagar, SEFAZ',
                'icone' => 'fa-file-invoice-dollar',
                'cor' => '#F59E0B',
                'departamento' => 'FIN'
            ]
        ];
    }
}

/**
 * Helpers globais para verificação rápida
 */
function temAcessoModulo($modulo, $nivelMinimo = 'leitura') {
    if (!isLoggedIn()) return false;
    if (isAdmin()) return true; // Admin sempre tem acesso
    static $cache = [];
    $key = $_SESSION['usuario_id'] . ':' . $modulo . ':' . $nivelMinimo;
    if (!isset($cache[$key])) {
        $perm = new ModuloPermissao();
        $cache[$key] = $perm->temAcesso($_SESSION['usuario_id'], $modulo, $nivelMinimo);
    }
    return $cache[$key];
}

function requireModulo($modulo, $nivelMinimo = 'leitura') {
    if (!temAcessoModulo($modulo, $nivelMinimo)) {
        if (isAjax()) {
            jsonResponse(['error' => 'Sem permissão para acessar este módulo'], 403);
        }
        setFlash('danger', 'Você não tem permissão para acessar este módulo. Solicite acesso ao administrador.');
        redirect('index.php?page=dashboard');
    }
}
