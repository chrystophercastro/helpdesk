<?php
/**
 * Model: Senha (Cofre de Senhas)
 * Gerencia senhas criptografadas com AES-256-CBC
 */
require_once __DIR__ . '/Database.php';

class Senha {
    private $db;
    
    // Chave de criptografia - em produção, mover para variável de ambiente
    private $encryptionKey;
    private $cipher = 'AES-256-CBC';

    public function __construct() {
        $this->db = Database::getInstance();
        // Derivar chave a partir de um segredo fixo (pode ser movido para .env)
        $secret = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'helpdesk-ti-cofre-2024-!@#$%';
        $this->encryptionKey = hash('sha256', $secret, true);
    }

    /**
     * Criptografar valor
     */
    private function encrypt($value) {
        if (empty($value)) return null;
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($value, $this->cipher, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Descriptografar valor
     */
    private function decrypt($value) {
        if (empty($value)) return null;
        $data = base64_decode($value);
        $parts = explode('::', $data, 2);
        if (count($parts) !== 2) return '[erro ao descriptografar]';
        list($iv, $encrypted) = $parts;
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->encryptionKey, 0, $iv);
        return $decrypted !== false ? $decrypted : '[erro ao descriptografar]';
    }

    /**
     * Buscar por ID (com descriptografia)
     */
    public function findById($id) {
        $senha = $this->db->fetch(
            "SELECT s.*, u1.nome AS criado_por_nome, u2.nome AS atualizado_por_nome
             FROM senhas s
             LEFT JOIN usuarios u1 ON s.criado_por = u1.id
             LEFT JOIN usuarios u2 ON s.atualizado_por = u2.id
             WHERE s.id = ?",
            [$id]
        );
        if ($senha) {
            $senha['usuario_dec'] = $this->decrypt($senha['usuario']);
            $senha['senha_dec'] = $this->decrypt($senha['senha']);
            $senha['notas_dec'] = $this->decrypt($senha['notas']);
        }
        return $senha;
    }

    /**
     * Listar senhas (sem descriptografar a senha em si)
     */
    public function listar($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['categoria'])) {
            $where[] = "s.categoria = ?";
            $params[] = $filtros['categoria'];
        }

        if (!empty($filtros['busca'])) {
            $where[] = "(s.titulo LIKE ? OR s.ip_host LIKE ? OR s.url LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->fetchAll(
            "SELECT s.id, s.titulo, s.categoria, s.url, s.usuario, s.ip_host, s.porta,
                    s.criado_por, s.atualizado_por, s.criado_em, s.atualizado_em,
                    u1.nome AS criado_por_nome
             FROM senhas s
             LEFT JOIN usuarios u1 ON s.criado_por = u1.id
             {$whereClause}
             ORDER BY s.titulo ASC",
            $params
        );
    }

    /**
     * Criar senha
     */
    public function criar($dados) {
        return $this->db->insert('senhas', [
            'titulo' => $dados['titulo'],
            'categoria' => $dados['categoria'],
            'url' => $dados['url'] ?? null,
            'usuario' => $this->encrypt($dados['usuario'] ?? ''),
            'senha' => $this->encrypt($dados['senha']),
            'notas' => $this->encrypt($dados['notas'] ?? ''),
            'ip_host' => $dados['ip_host'] ?? null,
            'porta' => $dados['porta'] ?? null,
            'criado_por' => $dados['criado_por']
        ]);
    }

    /**
     * Atualizar senha
     */
    public function atualizar($id, $dados) {
        $update = [
            'titulo' => $dados['titulo'],
            'categoria' => $dados['categoria'],
            'url' => $dados['url'] ?? null,
            'usuario' => $this->encrypt($dados['usuario'] ?? ''),
            'notas' => $this->encrypt($dados['notas'] ?? ''),
            'ip_host' => $dados['ip_host'] ?? null,
            'porta' => $dados['porta'] ?? null,
            'atualizado_por' => $dados['atualizado_por']
        ];

        // Só atualizar senha se foi informada
        if (!empty($dados['senha'])) {
            $update['senha'] = $this->encrypt($dados['senha']);
        }

        return $this->db->update('senhas', $update, 'id = ?', [$id]);
    }

    /**
     * Excluir senha
     */
    public function deletar($id) {
        return $this->db->delete('senhas', 'id = ?', [$id]);
    }

    /**
     * Registrar log de acesso
     */
    public function registrarLog($senhaId, $usuarioId, $acao) {
        return $this->db->insert('senhas_log', [
            'senha_id' => $senhaId,
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }

    /**
     * Buscar logs de uma senha
     */
    public function getLogs($senhaId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT sl.*, u.nome AS usuario_nome
             FROM senhas_log sl
             LEFT JOIN usuarios u ON sl.usuario_id = u.id
             WHERE sl.senha_id = ?
             ORDER BY sl.criado_em DESC
             LIMIT ?",
            [$senhaId, $limit]
        );
    }

    /**
     * Contar por categoria
     */
    public function contarPorCategoria() {
        return $this->db->fetchAll(
            "SELECT categoria, COUNT(*) as total FROM senhas GROUP BY categoria ORDER BY total DESC"
        );
    }

    /**
     * Descriptografar campo de usuário para listagem
     */
    public function decryptUsuario($encrypted) {
        return $this->decrypt($encrypted);
    }
}
