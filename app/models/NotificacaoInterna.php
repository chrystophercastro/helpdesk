<?php
/**
 * Model: NotificacaoInterna
 * Sistema de notificações internas em tempo real (bell icon, dropdown)
 */
class NotificacaoInterna {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ========================================
    // CRUD
    // ========================================

    public function criar($dados) {
        return $this->db->insert('notificacoes', [
            'usuario_id'     => $dados['usuario_id'],
            'tipo'           => $dados['tipo'] ?? 'info',
            'titulo'         => $dados['titulo'],
            'mensagem'       => $dados['mensagem'] ?? null,
            'icone'          => $dados['icone'] ?? 'fa-bell',
            'link'           => $dados['link'] ?? null,
            'referencia_tipo'=> $dados['referencia_tipo'] ?? null,
            'referencia_id'  => $dados['referencia_id'] ?? null,
        ]);
    }

    public function criarParaMultiplos($usuarioIds, $dados) {
        $ids = [];
        foreach ($usuarioIds as $uid) {
            $d = $dados;
            $d['usuario_id'] = $uid;
            $ids[] = $this->criar($d);
        }
        return $ids;
    }

    public function criarParaEquipe($dados) {
        $usuarios = $this->db->fetchAll(
            "SELECT id FROM usuarios WHERE tipo IN ('admin','tecnico') AND ativo = 1"
        );
        return $this->criarParaMultiplos(array_column($usuarios, 'id'), $dados);
    }

    public function listar($usuarioId, $limite = 50, $offset = 0, $apenasNaoLidas = false) {
        $sql = "SELECT * FROM notificacoes WHERE usuario_id = ?";
        $params = [$usuarioId];
        if ($apenasNaoLidas) {
            $sql .= " AND lida = 0";
        }
        $sql .= " ORDER BY criado_em DESC LIMIT " . (int)$limite . " OFFSET " . (int)$offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function contarNaoLidas($usuarioId) {
        return (int)$this->db->count('notificacoes', 'usuario_id = ? AND lida = 0', [$usuarioId]);
    }

    public function marcarLida($id, $usuarioId) {
        return $this->db->query(
            "UPDATE notificacoes SET lida = 1, lida_em = NOW() WHERE id = ? AND usuario_id = ?",
            [$id, $usuarioId]
        );
    }

    public function marcarTodasLidas($usuarioId) {
        return $this->db->query(
            "UPDATE notificacoes SET lida = 1, lida_em = NOW() WHERE usuario_id = ? AND lida = 0",
            [$usuarioId]
        );
    }

    public function excluir($id, $usuarioId) {
        return $this->db->delete('notificacoes', 'id = ? AND usuario_id = ?', [$id, $usuarioId]);
    }

    public function limparAntigas($dias = 30) {
        return $this->db->query(
            "DELETE FROM notificacoes WHERE criado_em < DATE_SUB(NOW(), INTERVAL ? DAY) AND lida = 1",
            [$dias]
        );
    }

    // ========================================
    // PREFERÊNCIAS
    // ========================================

    public function getPreferencias($usuarioId) {
        $prefs = $this->db->fetch(
            "SELECT * FROM notificacao_preferencias WHERE usuario_id = ?",
            [$usuarioId]
        );
        if (!$prefs) {
            $this->db->insert('notificacao_preferencias', ['usuario_id' => $usuarioId]);
            $prefs = $this->db->fetch(
                "SELECT * FROM notificacao_preferencias WHERE usuario_id = ?",
                [$usuarioId]
            );
        }
        return $prefs;
    }

    public function salvarPreferencias($usuarioId, $dados) {
        $campos = [
            'chamado_novo', 'chamado_atribuido', 'chamado_atualizado', 'chamado_fechado',
            'tarefa_atribuida', 'tarefa_concluida', 'projeto_atualizado',
            'compra_aprovada', 'sistema', 'som_ativo'
        ];
        $update = [];
        foreach ($campos as $c) {
            if (isset($dados[$c])) {
                $update[$c] = (int)$dados[$c];
            }
        }
        $exists = $this->db->fetch("SELECT id FROM notificacao_preferencias WHERE usuario_id = ?", [$usuarioId]);
        if ($exists) {
            return $this->db->update('notificacao_preferencias', $update, 'usuario_id = ?', [$usuarioId]);
        } else {
            $update['usuario_id'] = $usuarioId;
            return $this->db->insert('notificacao_preferencias', $update);
        }
    }

    public function deveNotificar($usuarioId, $tipoNotificacao) {
        $prefs = $this->getPreferencias($usuarioId);
        return (bool)($prefs[$tipoNotificacao] ?? 1);
    }

    // ========================================
    // HELPERS DE NOTIFICAÇÃO
    // ========================================

    public function notificarNovoChamado($chamado) {
        $tecnicos = $this->db->fetchAll("SELECT id FROM usuarios WHERE tipo IN ('admin','tecnico') AND ativo = 1");
        foreach ($tecnicos as $t) {
            if (!$this->deveNotificar($t['id'], 'chamado_novo')) continue;
            $this->criar([
                'usuario_id' => $t['id'],
                'tipo' => 'chamado',
                'titulo' => 'Novo chamado: ' . ($chamado['titulo'] ?? $chamado['codigo'] ?? '#' . $chamado['id']),
                'mensagem' => 'Prioridade: ' . ucfirst($chamado['prioridade'] ?? 'normal'),
                'icone' => 'fa-ticket-alt',
                'link' => '/index.php?page=chamados&action=ver&id=' . $chamado['id'],
                'referencia_tipo' => 'chamado',
                'referencia_id' => $chamado['id'],
            ]);
        }
    }

    public function notificarChamadoAtribuido($chamadoId, $tecnicoId, $chamadoTitulo) {
        if (!$this->deveNotificar($tecnicoId, 'chamado_atribuido')) return;
        $this->criar([
            'usuario_id' => $tecnicoId,
            'tipo' => 'chamado',
            'titulo' => 'Chamado atribuído a você',
            'mensagem' => $chamadoTitulo,
            'icone' => 'fa-user-check',
            'link' => '/index.php?page=chamados&action=ver&id=' . $chamadoId,
            'referencia_tipo' => 'chamado',
            'referencia_id' => $chamadoId,
        ]);
    }

    public function notificarTarefaAtribuida($tarefaId, $responsavelId, $tarefaTitulo, $projetoNome = '') {
        if (!$this->deveNotificar($responsavelId, 'tarefa_atribuida')) return;
        $this->criar([
            'usuario_id' => $responsavelId,
            'tipo' => 'tarefa',
            'titulo' => 'Nova tarefa atribuída',
            'mensagem' => $tarefaTitulo . ($projetoNome ? " ($projetoNome)" : ''),
            'icone' => 'fa-tasks',
            'link' => '/index.php?page=kanban',
            'referencia_tipo' => 'tarefa',
            'referencia_id' => $tarefaId,
        ]);
    }

    public function notificarChamadoAtualizado($chamado, $campo, $valorNovo) {
        // Notificar técnico atribuído (se houver) e admins
        $destinatarios = [];
        if (!empty($chamado['tecnico_id'])) {
            $destinatarios[] = $chamado['tecnico_id'];
        }
        $admins = $this->db->fetchAll("SELECT id FROM usuarios WHERE tipo = 'admin' AND ativo = 1");
        foreach ($admins as $a) {
            if (!in_array($a['id'], $destinatarios)) {
                $destinatarios[] = $a['id'];
            }
        }

        $labels = [
            'status' => 'Status', 'prioridade' => 'Prioridade', 'tecnico_id' => 'Técnico',
            'categoria_id' => 'Categoria', 'titulo' => 'Título', 'descricao' => 'Descrição',
            'urgencia' => 'Urgência', 'impacto' => 'Impacto'
        ];
        $campoLabel = $labels[$campo] ?? $campo;
        $codigo = $chamado['codigo'] ?? '#' . $chamado['id'];

        // Se é atribuição de técnico, usar método específico
        if ($campo === 'tecnico_id' && !empty($valorNovo)) {
            $this->notificarChamadoAtribuido($chamado['id'], (int)$valorNovo, $chamado['titulo'] ?? $codigo);
            return;
        }

        $usuarioAtual = $_SESSION['usuario_id'] ?? 0;
        foreach ($destinatarios as $uid) {
            if ($uid == $usuarioAtual) continue; // Não notificar quem fez a ação
            if (!$this->deveNotificar($uid, 'chamado_atualizado')) continue;
            $this->criar([
                'usuario_id' => $uid,
                'tipo' => 'chamado',
                'titulo' => "$campoLabel atualizado: $codigo",
                'mensagem' => ucfirst($campoLabel) . ' alterado para: ' . ucfirst($valorNovo),
                'icone' => 'fa-sync',
                'link' => '/index.php?page=chamados&action=ver&id=' . $chamado['id'],
                'referencia_tipo' => 'chamado',
                'referencia_id' => $chamado['id'],
            ]);
        }
    }

    public function notificarChamadoFechado($chamado) {
        $destinatarios = [];
        if (!empty($chamado['tecnico_id'])) {
            $destinatarios[] = $chamado['tecnico_id'];
        }
        $admins = $this->db->fetchAll("SELECT id FROM usuarios WHERE tipo = 'admin' AND ativo = 1");
        foreach ($admins as $a) {
            if (!in_array($a['id'], $destinatarios)) {
                $destinatarios[] = $a['id'];
            }
        }

        $codigo = $chamado['codigo'] ?? '#' . $chamado['id'];
        $usuarioAtual = $_SESSION['usuario_id'] ?? 0;
        foreach ($destinatarios as $uid) {
            if ($uid == $usuarioAtual) continue;
            if (!$this->deveNotificar($uid, 'chamado_fechado')) continue;
            $this->criar([
                'usuario_id' => $uid,
                'tipo' => 'success',
                'titulo' => "Chamado fechado: $codigo",
                'mensagem' => $chamado['titulo'] ?? '',
                'icone' => 'fa-check-circle',
                'link' => '/index.php?page=chamados&action=ver&id=' . $chamado['id'],
                'referencia_tipo' => 'chamado',
                'referencia_id' => $chamado['id'],
            ]);
        }
    }

    public function notificarComentarioChamado($chamado, $autorNome, $tipo = 'comentario') {
        if ($tipo === 'interno') return; // Não notificar notas internas

        $destinatarios = [];
        if (!empty($chamado['tecnico_id'])) {
            $destinatarios[] = $chamado['tecnico_id'];
        }
        $admins = $this->db->fetchAll("SELECT id FROM usuarios WHERE tipo = 'admin' AND ativo = 1");
        foreach ($admins as $a) {
            if (!in_array($a['id'], $destinatarios)) {
                $destinatarios[] = $a['id'];
            }
        }

        $codigo = $chamado['codigo'] ?? '#' . $chamado['id'];
        $usuarioAtual = $_SESSION['usuario_id'] ?? 0;
        foreach ($destinatarios as $uid) {
            if ($uid == $usuarioAtual) continue;
            if (!$this->deveNotificar($uid, 'chamado_atualizado')) continue;
            $this->criar([
                'usuario_id' => $uid,
                'tipo' => 'chamado',
                'titulo' => "Novo comentário: $codigo",
                'mensagem' => "Por $autorNome",
                'icone' => 'fa-comment',
                'link' => '/index.php?page=chamados&action=ver&id=' . $chamado['id'],
                'referencia_tipo' => 'chamado',
                'referencia_id' => $chamado['id'],
            ]);
        }
    }

    public function notificarSistema($usuarioId, $titulo, $mensagem = '', $link = null) {
        if (!$this->deveNotificar($usuarioId, 'sistema')) return;
        $this->criar([
            'usuario_id' => $usuarioId,
            'tipo' => 'sistema',
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'icone' => 'fa-cog',
            'link' => $link,
        ]);
    }

    // ========================================
    // ESTATÍSTICAS
    // ========================================

    public function getEstatisticas($usuarioId) {
        $total = (int)$this->db->count('notificacoes', 'usuario_id = ?', [$usuarioId]);
        $naoLidas = $this->contarNaoLidas($usuarioId);
        $hoje = (int)$this->db->count('notificacoes', 'usuario_id = ? AND DATE(criado_em) = CURDATE()', [$usuarioId]);
        $porTipo = $this->db->fetchAll(
            "SELECT tipo, COUNT(*) as total FROM notificacoes WHERE usuario_id = ? GROUP BY tipo ORDER BY total DESC",
            [$usuarioId]
        );
        return [
            'total' => $total,
            'nao_lidas' => $naoLidas,
            'hoje' => $hoje,
            'por_tipo' => $porTipo,
        ];
    }
}
