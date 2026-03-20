<?php
/**
 * Model: Post (Timeline / Mural de Posts)
 */
require_once __DIR__ . '/Database.php';

class Post {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca um post por ID com dados do autor.
     */
    public function findById($id) {
        return $this->db->fetch(
            "SELECT p.*, u.nome as autor_nome, u.avatar as autor_avatar, u.cargo as autor_cargo,
                    d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor
             FROM posts p
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN departamentos d ON p.departamento_id = d.id
             WHERE p.id = ? AND p.ativo = 1", [$id]
        );
    }

    /**
     * Lista posts para o feed (timeline) com paginação.
     * Todos os departamentos veem todos os posts.
     */
    public function listar($pagina = 1, $porPagina = 20) {
        $offset = ($pagina - 1) * $porPagina;

        $total = $this->db->fetchColumn("SELECT COUNT(*) FROM posts WHERE ativo = 1");

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.nome as autor_nome, u.avatar as autor_avatar, u.cargo as autor_cargo,
                    d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor
             FROM posts p
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN departamentos d ON p.departamento_id = d.id
             WHERE p.ativo = 1
             ORDER BY p.fixado DESC, p.criado_em DESC
             LIMIT ? OFFSET ?", [$porPagina, $offset]
        );

        return [
            'posts' => $posts,
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => ceil($total / $porPagina)
        ];
    }

    /**
     * Cria um novo post.
     */
    public function criar($dados) {
        return $this->db->insert('posts', [
            'usuario_id' => $dados['usuario_id'],
            'departamento_id' => $dados['departamento_id'] ?? null,
            'conteudo' => $dados['conteudo'],
            'midia' => $dados['midia'] ?? null,
            'tipo' => $dados['tipo'] ?? 'texto',
            'fixado' => $dados['fixado'] ?? 0
        ]);
    }

    /**
     * Atualiza um post existente.
     */
    public function atualizar($id, $dados) {
        $update = [];
        $campos = ['conteudo', 'midia', 'tipo', 'fixado'];
        foreach ($campos as $campo) {
            if (isset($dados[$campo])) {
                $update[$campo] = $dados[$campo];
            }
        }
        if (!empty($update)) {
            return $this->db->update('posts', $update, 'id = ?', [$id]);
        }
        return false;
    }

    /**
     * Soft delete de um post.
     */
    public function deletar($id) {
        return $this->db->update('posts', ['ativo' => 0], 'id = ?', [$id]);
    }

    /**
     * Toggle like em um post (curtir/descurtir).
     */
    public function toggleLike($postId, $usuarioId) {
        $existing = $this->db->fetch(
            "SELECT id FROM posts_likes WHERE post_id = ? AND usuario_id = ?",
            [$postId, $usuarioId]
        );

        if ($existing) {
            $this->db->delete('posts_likes', 'id = ?', [$existing['id']]);
            $this->db->query("UPDATE posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?", [$postId]);
            return ['liked' => false];
        } else {
            $this->db->insert('posts_likes', [
                'post_id' => $postId,
                'usuario_id' => $usuarioId
            ]);
            $this->db->query("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?", [$postId]);
            return ['liked' => true];
        }
    }

    /**
     * Verifica se o usuário curtiu o post.
     */
    public function isLiked($postId, $usuarioId) {
        return (bool) $this->db->fetch(
            "SELECT 1 FROM posts_likes WHERE post_id = ? AND usuario_id = ?",
            [$postId, $usuarioId]
        );
    }

    /**
     * Busca likes de múltiplos posts para o usuário atual.
     */
    public function getLikesMap($postIds, $usuarioId) {
        if (empty($postIds)) return [];
        $placeholders = str_repeat('?,', count($postIds) - 1) . '?';
        $params = array_merge($postIds, [$usuarioId]);
        $rows = $this->db->fetchAll(
            "SELECT post_id FROM posts_likes WHERE post_id IN ({$placeholders}) AND usuario_id = ?",
            $params
        );
        $map = [];
        foreach ($rows as $r) {
            $map[$r['post_id']] = true;
        }
        return $map;
    }

    /**
     * Lista comentários de um post.
     */
    public function getComentarios($postId) {
        return $this->db->fetchAll(
            "SELECT c.*, u.nome as autor_nome, u.avatar as autor_avatar
             FROM posts_comentarios c
             LEFT JOIN usuarios u ON c.usuario_id = u.id
             WHERE c.post_id = ?
             ORDER BY c.criado_em ASC", [$postId]
        );
    }

    /**
     * Adiciona um comentário.
     */
    public function comentar($postId, $usuarioId, $conteudo) {
        $id = $this->db->insert('posts_comentarios', [
            'post_id' => $postId,
            'usuario_id' => $usuarioId,
            'conteudo' => $conteudo
        ]);
        $this->db->query("UPDATE posts SET comentarios_count = comentarios_count + 1 WHERE id = ?", [$postId]);
        return $id;
    }

    /**
     * Remove um comentário.
     */
    public function removerComentario($comentarioId, $usuarioId) {
        $comment = $this->db->fetch("SELECT * FROM posts_comentarios WHERE id = ?", [$comentarioId]);
        if (!$comment) return false;

        // Só o autor do comentário ou admin pode remover
        if ((int)$comment['usuario_id'] !== (int)$usuarioId && !isAdmin()) {
            return false;
        }

        $this->db->delete('posts_comentarios', 'id = ?', [$comentarioId]);
        $this->db->query("UPDATE posts SET comentarios_count = GREATEST(comentarios_count - 1, 0) WHERE id = ?", [$comment['post_id']]);
        return true;
    }
}
