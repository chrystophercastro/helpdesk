<?php
/**
 * Model: Chat
 * Sistema de chat interno — conversas, mensagens, presença
 */
require_once __DIR__ . '/Database.php';

class Chat {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ========================================
    // CONVERSAS
    // ========================================

    public function listarConversas($usuarioId) {
        return $this->db->fetchAll(
            "SELECT c.*,
                    cp.ultima_leitura,
                    cp.notificacao_mudo,
                    cp.papel,
                    (SELECT COUNT(*) FROM chat_mensagens cm 
                     WHERE cm.conversa_id = c.id AND cm.deletado = 0
                     AND cm.criado_em > COALESCE(cp.ultima_leitura, '1970-01-01')) as nao_lidas,
                    (SELECT cm2.conteudo FROM chat_mensagens cm2 
                     WHERE cm2.conversa_id = c.id AND cm2.deletado = 0
                     ORDER BY cm2.id DESC LIMIT 1) as ultima_msg,
                    (SELECT cm3.criado_em FROM chat_mensagens cm3
                     WHERE cm3.conversa_id = c.id AND cm3.deletado = 0
                     ORDER BY cm3.id DESC LIMIT 1) as ultima_msg_data,
                    (SELECT u.nome FROM chat_mensagens cm4
                     JOIN usuarios u ON u.id = cm4.usuario_id
                     WHERE cm4.conversa_id = c.id AND cm4.deletado = 0
                     ORDER BY cm4.id DESC LIMIT 1) as ultima_msg_autor
             FROM chat_conversas c
             INNER JOIN chat_participantes cp ON cp.conversa_id = c.id AND cp.usuario_id = ?
             WHERE c.arquivada = 0
             ORDER BY c.fixada DESC, 
                      COALESCE((SELECT cm5.criado_em FROM chat_mensagens cm5 WHERE cm5.conversa_id = c.id AND cm5.deletado = 0 ORDER BY cm5.id DESC LIMIT 1), c.criado_em) DESC",
            [$usuarioId]
        );
    }

    public function getConversa($conversaId, $usuarioId) {
        return $this->db->fetch(
            "SELECT c.*, cp.ultima_leitura, cp.notificacao_mudo, cp.papel
             FROM chat_conversas c
             INNER JOIN chat_participantes cp ON cp.conversa_id = c.id AND cp.usuario_id = ?
             WHERE c.id = ?",
            [$usuarioId, $conversaId]
        );
    }

    public function criarGrupo($dados, $criadorId) {
        $conversaId = $this->db->insert('chat_conversas', [
            'tipo' => $dados['tipo'] ?? 'grupo',
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'icone' => $dados['icone'] ?? 'fa-users',
            'cor' => $dados['cor'] ?? '#3B82F6',
            'criado_por' => $criadorId
        ]);

        // Adicionar criador como dono
        $this->db->insert('chat_participantes', [
            'conversa_id' => $conversaId,
            'usuario_id' => $criadorId,
            'papel' => 'dono'
        ]);

        // Adicionar membros
        if (!empty($dados['membros'])) {
            foreach ($dados['membros'] as $uid) {
                if ($uid != $criadorId) {
                    $this->db->insert('chat_participantes', [
                        'conversa_id' => $conversaId,
                        'usuario_id' => (int)$uid,
                        'papel' => 'membro'
                    ]);
                }
            }
        }

        // Mensagem de sistema
        $this->enviarMensagem($conversaId, $criadorId, 'criou o grupo', 'sistema');

        return $conversaId;
    }

    public function getOuCriarConversaDireta($usuarioId, $outroId) {
        // Procurar conversa direta existente
        $conversa = $this->db->fetch(
            "SELECT c.id FROM chat_conversas c
             INNER JOIN chat_participantes cp1 ON cp1.conversa_id = c.id AND cp1.usuario_id = ?
             INNER JOIN chat_participantes cp2 ON cp2.conversa_id = c.id AND cp2.usuario_id = ?
             WHERE c.tipo = 'direta'",
            [$usuarioId, $outroId]
        );

        if ($conversa) return $conversa['id'];

        // Criar nova conversa direta
        $conversaId = $this->db->insert('chat_conversas', [
            'tipo' => 'direta',
            'criado_por' => $usuarioId
        ]);

        $this->db->insert('chat_participantes', [
            'conversa_id' => $conversaId,
            'usuario_id' => $usuarioId,
            'papel' => 'membro'
        ]);
        $this->db->insert('chat_participantes', [
            'conversa_id' => $conversaId,
            'usuario_id' => $outroId,
            'papel' => 'membro'
        ]);

        return $conversaId;
    }

    public function getParticipantes($conversaId) {
        return $this->db->fetchAll(
            "SELECT u.id, u.nome, u.email, u.tipo, u.departamento_id,
                    cp.papel, cp.notificacao_mudo, cp.entrou_em,
                    d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor,
                    pr.status as presenca_status, pr.ultimo_acesso, pr.digitando_em
             FROM chat_participantes cp
             INNER JOIN usuarios u ON u.id = cp.usuario_id
             LEFT JOIN departamentos d ON d.id = u.departamento_id
             LEFT JOIN chat_presenca pr ON pr.usuario_id = u.id
             WHERE cp.conversa_id = ?
             ORDER BY u.nome",
            [$conversaId]
        );
    }

    public function adicionarParticipante($conversaId, $usuarioId, $adicionadoPorId) {
        $existe = $this->db->fetch(
            "SELECT id FROM chat_participantes WHERE conversa_id = ? AND usuario_id = ?",
            [$conversaId, $usuarioId]
        );
        if ($existe) return false;

        $this->db->insert('chat_participantes', [
            'conversa_id' => $conversaId,
            'usuario_id' => $usuarioId,
            'papel' => 'membro'
        ]);

        $user = $this->db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$usuarioId]);
        $this->enviarMensagem($conversaId, $adicionadoPorId, 'adicionou ' . ($user['nome'] ?? ''), 'sistema');
        return true;
    }

    public function removerParticipante($conversaId, $usuarioId, $removidoPorId) {
        $this->db->delete('chat_participantes', 'conversa_id = ? AND usuario_id = ?', [$conversaId, $usuarioId]);
        $user = $this->db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$usuarioId]);
        $this->enviarMensagem($conversaId, $removidoPorId, 'removeu ' . ($user['nome'] ?? ''), 'sistema');
        return true;
    }

    public function sairDoGrupo($conversaId, $usuarioId) {
        $this->db->delete('chat_participantes', 'conversa_id = ? AND usuario_id = ?', [$conversaId, $usuarioId]);
        $this->enviarMensagem($conversaId, $usuarioId, 'saiu do grupo', 'sistema');
        return true;
    }

    // ========================================
    // MENSAGENS
    // ========================================

    public function getMensagens($conversaId, $limite = 50, $antesDeId = null) {
        $params = [$conversaId];
        $sql = "SELECT m.*, u.nome as autor_nome, u.tipo as autor_tipo,
                       d.sigla as autor_dept_sigla, d.cor as autor_dept_cor,
                       mr.conteudo as resposta_conteudo, mr.usuario_id as resposta_usuario_id,
                       ur.nome as resposta_autor_nome
                FROM chat_mensagens m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                LEFT JOIN departamentos d ON d.id = u.departamento_id
                LEFT JOIN chat_mensagens mr ON mr.id = m.resposta_a
                LEFT JOIN usuarios ur ON ur.id = mr.usuario_id
                WHERE m.conversa_id = ?";

        if ($antesDeId) {
            $sql .= " AND m.id < ?";
            $params[] = $antesDeId;
        }

        $sql .= " ORDER BY m.id DESC LIMIT " . (int)$limite;

        $msgs = $this->db->fetchAll($sql, $params);

        // Buscar reações para essas mensagens
        if (!empty($msgs)) {
            $ids = array_column($msgs, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $reacoes = $this->db->fetchAll(
                "SELECT r.mensagem_id, r.emoji, r.usuario_id, u.nome as usuario_nome
                 FROM chat_reacoes r
                 INNER JOIN usuarios u ON u.id = r.usuario_id
                 WHERE r.mensagem_id IN ($placeholders)",
                $ids
            );

            $reacoesMap = [];
            foreach ($reacoes as $r) {
                $reacoesMap[$r['mensagem_id']][] = $r;
            }

            foreach ($msgs as &$m) {
                $m['reacoes'] = $reacoesMap[$m['id']] ?? [];
            }
        }

        return array_reverse($msgs);
    }

    public function enviarMensagem($conversaId, $usuarioId, $conteudo, $tipo = 'texto', $respostaA = null) {
        $id = $this->db->insert('chat_mensagens', [
            'conversa_id' => $conversaId,
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'conteudo' => $conteudo,
            'resposta_a' => $respostaA
        ]);

        // Atualizar timestamp da conversa
        $this->db->query("UPDATE chat_conversas SET atualizado_em = NOW() WHERE id = ?", [$conversaId]);

        return $id;
    }

    public function editarMensagem($mensagemId, $usuarioId, $novoConteudo) {
        $msg = $this->db->fetch("SELECT * FROM chat_mensagens WHERE id = ? AND usuario_id = ?", [$mensagemId, $usuarioId]);
        if (!$msg || $msg['tipo'] === 'sistema') return false;

        $this->db->query(
            "UPDATE chat_mensagens SET conteudo = ?, editado = 1, editado_em = NOW() WHERE id = ?",
            [$novoConteudo, $mensagemId]
        );
        return true;
    }

    public function deletarMensagem($mensagemId, $usuarioId, $isAdmin = false) {
        $where = $isAdmin ? "id = ?" : "id = ? AND usuario_id = ?";
        $params = $isAdmin ? [$mensagemId] : [$mensagemId, $usuarioId];
        
        $msg = $this->db->fetch("SELECT * FROM chat_mensagens WHERE $where", $params);
        if (!$msg) return false;

        $this->db->query(
            "UPDATE chat_mensagens SET deletado = 1, deletado_em = NOW(), conteudo = 'Mensagem apagada' WHERE id = ?",
            [$mensagemId]
        );
        return true;
    }

    public function toggleReacao($mensagemId, $usuarioId, $emoji) {
        $existe = $this->db->fetch(
            "SELECT id FROM chat_reacoes WHERE mensagem_id = ? AND usuario_id = ? AND emoji = ?",
            [$mensagemId, $usuarioId, $emoji]
        );

        if ($existe) {
            $this->db->delete('chat_reacoes', 'id = ?', [$existe['id']]);
            return 'removed';
        } else {
            $this->db->insert('chat_reacoes', [
                'mensagem_id' => $mensagemId,
                'usuario_id' => $usuarioId,
                'emoji' => $emoji
            ]);
            return 'added';
        }
    }

    // ========================================
    // PRESENÇA
    // ========================================

    public function atualizarPresenca($usuarioId, $status = 'online') {
        $this->db->query(
            "INSERT INTO chat_presenca (usuario_id, status, ultimo_acesso) 
             VALUES (?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE status = VALUES(status), ultimo_acesso = NOW()",
            [$usuarioId, $status]
        );
    }

    public function setDigitando($usuarioId, $conversaId = null) {
        $this->db->query(
            "UPDATE chat_presenca SET digitando_em = ?, ultimo_acesso = NOW() WHERE usuario_id = ?",
            [$conversaId, $usuarioId]
        );
    }

    public function getPresencaUsuarios($usuarioIds) {
        if (empty($usuarioIds)) return [];
        $placeholders = implode(',', array_fill(0, count($usuarioIds), '?'));
        return $this->db->fetchAll(
            "SELECT * FROM chat_presenca WHERE usuario_id IN ($placeholders)",
            $usuarioIds
        );
    }

    public function marcarOfflineInativos($minutos = 2) {
        $this->db->query(
            "UPDATE chat_presenca SET status = 'offline', digitando_em = NULL
             WHERE status != 'offline' AND ultimo_acesso < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$minutos]
        );
    }

    // ========================================
    // LEITURA
    // ========================================

    public function marcarLida($conversaId, $usuarioId) {
        $this->db->query(
            "UPDATE chat_participantes SET ultima_leitura = NOW() WHERE conversa_id = ? AND usuario_id = ?",
            [$conversaId, $usuarioId]
        );
    }

    // ========================================
    // CONTADORES
    // ========================================

    public function contarNaoLidasTotal($usuarioId) {
        $result = $this->db->fetch(
            "SELECT COALESCE(SUM(sub.nao_lidas), 0) as total FROM (
                SELECT COUNT(cm.id) as nao_lidas
                FROM chat_participantes cp
                INNER JOIN chat_conversas cc ON cc.id = cp.conversa_id AND cc.arquivada = 0
                INNER JOIN chat_mensagens cm ON cm.conversa_id = cp.conversa_id 
                    AND cm.deletado = 0
                    AND cm.criado_em > COALESCE(cp.ultima_leitura, '1970-01-01')
                    AND cm.usuario_id != ?
                WHERE cp.usuario_id = ? AND cp.notificacao_mudo = 0
            ) sub",
            [$usuarioId, $usuarioId]
        );
        return (int)($result['total'] ?? 0);
    }

    public function getMensagensNovas($conversaId, $depoisDeId) {
        return $this->db->fetchAll(
            "SELECT m.*, u.nome as autor_nome, u.tipo as autor_tipo,
                    d.sigla as autor_dept_sigla, d.cor as autor_dept_cor
             FROM chat_mensagens m
             INNER JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN departamentos d ON d.id = u.departamento_id
             WHERE m.conversa_id = ? AND m.id > ?
             ORDER BY m.id ASC",
            [$conversaId, $depoisDeId]
        );
    }

    // ========================================
    // BUSCA
    // ========================================

    public function buscarMensagens($usuarioId, $termo, $limite = 30) {
        return $this->db->fetchAll(
            "SELECT m.*, u.nome as autor_nome, cc.nome as conversa_nome, cc.tipo as conversa_tipo
             FROM chat_mensagens m
             INNER JOIN usuarios u ON u.id = m.usuario_id
             INNER JOIN chat_conversas cc ON cc.id = m.conversa_id
             INNER JOIN chat_participantes cp ON cp.conversa_id = m.conversa_id AND cp.usuario_id = ?
             WHERE m.deletado = 0 AND m.conteudo LIKE ?
             ORDER BY m.criado_em DESC
             LIMIT " . (int)$limite,
            [$usuarioId, '%' . $termo . '%']
        );
    }

    // ========================================
    // HELPERS
    // ========================================

    public function getInfoConversaDireta($conversaId, $meuId) {
        return $this->db->fetch(
            "SELECT u.id, u.nome, u.email, u.tipo, u.departamento_id,
                    d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor,
                    pr.status as presenca_status, pr.ultimo_acesso
             FROM chat_participantes cp
             INNER JOIN usuarios u ON u.id = cp.usuario_id
             LEFT JOIN departamentos d ON d.id = u.departamento_id
             LEFT JOIN chat_presenca pr ON pr.usuario_id = u.id
             WHERE cp.conversa_id = ? AND cp.usuario_id != ?",
            [$conversaId, $meuId]
        );
    }

    public function listarUsuariosDisponiveis($meuId) {
        return $this->db->fetchAll(
            "SELECT u.id, u.nome, u.email, u.tipo, u.departamento_id,
                    d.nome as departamento_nome, d.sigla as departamento_sigla, d.cor as departamento_cor,
                    pr.status as presenca_status
             FROM usuarios u
             LEFT JOIN departamentos d ON d.id = u.departamento_id
             LEFT JOIN chat_presenca pr ON pr.usuario_id = u.id
             WHERE u.ativo = 1 AND u.id != ?
             ORDER BY FIELD(pr.status, 'online','ausente','ocupado','offline'), u.nome",
            [$meuId]
        );
    }
}
