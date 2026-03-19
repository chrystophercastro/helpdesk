<?php
/**
 * Model: Calendario
 * Gestão de eventos, reuniões, lembretes e integrações
 */

require_once __DIR__ . '/../../config/app.php';

class Calendario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ===================== CRUD =====================

    public function listarEventos($filtros = []) {
        $where = ['e.ativo = 1'];
        $params = [];

        if (!empty($filtros['inicio'])) {
            $where[] = 'e.data_inicio >= ?';
            $params[] = $filtros['inicio'];
        }
        if (!empty($filtros['fim'])) {
            $where[] = '(e.data_fim <= ? OR (e.data_fim IS NULL AND e.data_inicio <= ?))';
            $params[] = $filtros['fim'];
            $params[] = $filtros['fim'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = 'e.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['usuario_id'])) {
            $where[] = '(e.criado_por = ? OR JSON_CONTAINS(e.participantes, CAST(? AS JSON), \'$\'))';
            $params[] = $filtros['usuario_id'];
            $params[] = $filtros['usuario_id'];
        }

        $sql = "SELECT e.*, u.nome as criador_nome
                FROM calendar_eventos e
                LEFT JOIN usuarios u ON e.criado_por = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.data_inicio ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getEvento($id) {
        return $this->db->fetch(
            "SELECT e.*, u.nome as criador_nome
             FROM calendar_eventos e
             LEFT JOIN usuarios u ON e.criado_por = u.id
             WHERE e.id = ?", [$id]
        );
    }

    public function criarEvento($dados) {
        if (!empty($dados['participantes']) && is_array($dados['participantes'])) {
            $dados['participantes'] = json_encode($dados['participantes']);
        }
        return $this->db->insert('calendar_eventos', $dados);
    }

    public function atualizarEvento($id, $dados) {
        if (isset($dados['participantes']) && is_array($dados['participantes'])) {
            $dados['participantes'] = json_encode($dados['participantes']);
        }
        return $this->db->update('calendar_eventos', $dados, 'id = ?', [$id]);
    }

    public function excluirEvento($id) {
        return $this->db->update('calendar_eventos', ['ativo' => 0], 'id = ?', [$id]);
    }

    // ===================== Mês/Semana/Dia =====================

    public function getEventosMes($ano, $mes, $usuarioId = null) {
        $inicio = "$ano-$mes-01 00:00:00";
        $fim = date('Y-m-t 23:59:59', strtotime($inicio));

        $filtros = ['inicio' => $inicio, 'fim' => $fim];
        if ($usuarioId) $filtros['usuario_id'] = $usuarioId;

        return $this->listarEventos($filtros);
    }

    public function getEventosSemana($data, $usuarioId = null) {
        $inicio = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($data)));
        $fim = date('Y-m-d 23:59:59', strtotime('sunday this week', strtotime($data)));

        $filtros = ['inicio' => $inicio, 'fim' => $fim];
        if ($usuarioId) $filtros['usuario_id'] = $usuarioId;

        return $this->listarEventos($filtros);
    }

    public function getEventosDia($data, $usuarioId = null) {
        $inicio = "$data 00:00:00";
        $fim = "$data 23:59:59";

        $filtros = ['inicio' => $inicio, 'fim' => $fim];
        if ($usuarioId) $filtros['usuario_id'] = $usuarioId;

        return $this->listarEventos($filtros);
    }

    // ===================== Integração com Chamados/Projetos =====================

    public function getEventosVinculados($tipo, $id) {
        $campo = $tipo . '_id';
        return $this->db->fetchAll(
            "SELECT e.*, u.nome as criador_nome
             FROM calendar_eventos e
             LEFT JOIN usuarios u ON e.criado_por = u.id
             WHERE e.$campo = ? AND e.ativo = 1
             ORDER BY e.data_inicio ASC", [$id]
        );
    }

    public function importarPrazos($usuarioId) {
        $importados = 0;

        // Sprints com data fim
        $sprints = $this->db->fetchAll(
            "SELECT s.id, s.nome, s.data_fim, p.nome as projeto_nome
             FROM sprints s
             LEFT JOIN projetos p ON s.projeto_id = p.id
             WHERE s.data_fim IS NOT NULL AND s.status IN ('planejada','ativa')
             AND s.id NOT IN (SELECT sprint_id FROM calendar_eventos WHERE sprint_id IS NOT NULL AND ativo = 1)"
        );
        foreach ($sprints as $sprint) {
            $this->criarEvento([
                'titulo' => 'Sprint: ' . $sprint['nome'],
                'descricao' => 'Fim da sprint do projeto ' . ($sprint['projeto_nome'] ?? ''),
                'tipo' => 'lembrete',
                'data_inicio' => $sprint['data_fim'] . ' 09:00:00',
                'data_fim' => $sprint['data_fim'] . ' 18:00:00',
                'cor' => '#8B5CF6',
                'sprint_id' => $sprint['id'],
                'criado_por' => $usuarioId
            ]);
            $importados++;
        }

        // Chamados com SLA/deadline
        $chamados = $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.sla_prazo
             FROM chamados c
             WHERE c.sla_prazo IS NOT NULL AND c.status NOT IN ('resolvido','fechado','cancelado')
             AND c.id NOT IN (SELECT chamado_id FROM calendar_eventos WHERE chamado_id IS NOT NULL AND ativo = 1)"
        );
        foreach ($chamados as $chamado) {
            $this->criarEvento([
                'titulo' => "SLA: #{$chamado['codigo']} - {$chamado['titulo']}",
                'descricao' => 'Prazo SLA do chamado',
                'tipo' => 'lembrete',
                'data_inicio' => $chamado['sla_prazo'],
                'cor' => '#EF4444',
                'chamado_id' => $chamado['id'],
                'criado_por' => $usuarioId
            ]);
            $importados++;
        }

        return $importados;
    }

    // ===================== Estatísticas =====================

    public function getEstatisticas($usuarioId = null) {
        $where = 'ativo = 1';
        $params = [];
        if ($usuarioId) {
            $where .= ' AND criado_por = ?';
            $params[] = $usuarioId;
        }

        $total = $this->db->count('calendar_eventos', $where, $params);

        $hoje = date('Y-m-d');
        $eventosHoje = $this->db->count('calendar_eventos', 
            $where . " AND DATE(data_inicio) = ?", 
            array_merge($params, [$hoje])
        );
        $proximosEventos = $this->db->count('calendar_eventos', 
            $where . " AND data_inicio > NOW() AND data_inicio <= DATE_ADD(NOW(), INTERVAL 7 DAY)", 
            $params
        );

        $porTipo = $this->db->fetchAll(
            "SELECT tipo, COUNT(*) as total FROM calendar_eventos WHERE $where GROUP BY tipo ORDER BY total DESC",
            $params
        );

        return [
            'total' => $total,
            'hoje' => $eventosHoje,
            'proximos_7_dias' => $proximosEventos,
            'por_tipo' => $porTipo
        ];
    }

    // Próximos eventos
    public function getProximos($limite = 5, $usuarioId = null) {
        $where = 'e.ativo = 1 AND e.data_inicio >= NOW()';
        $params = [];
        if ($usuarioId) {
            $where .= ' AND (e.criado_por = ? OR JSON_CONTAINS(e.participantes, CAST(? AS JSON), \'$\'))';
            $params[] = $usuarioId;
            $params[] = $usuarioId;
        }
        return $this->db->fetchAll(
            "SELECT e.*, u.nome as criador_nome
             FROM calendar_eventos e
             LEFT JOIN usuarios u ON e.criado_por = u.id
             WHERE $where ORDER BY e.data_inicio ASC LIMIT $limite", $params
        );
    }

    // Busca
    public function buscar($termo, $usuarioId = null) {
        $where = 'e.ativo = 1 AND (e.titulo LIKE ? OR e.descricao LIKE ? OR e.local LIKE ?)';
        $params = ["%$termo%", "%$termo%", "%$termo%"];
        if ($usuarioId) {
            $where .= ' AND (e.criado_por = ? OR JSON_CONTAINS(e.participantes, CAST(? AS JSON), \'$\'))';
            $params[] = $usuarioId;
            $params[] = $usuarioId;
        }
        return $this->db->fetchAll(
            "SELECT e.*, u.nome as criador_nome
             FROM calendar_eventos e
             LEFT JOIN usuarios u ON e.criado_por = u.id
             WHERE $where ORDER BY e.data_inicio DESC LIMIT 50", $params
        );
    }
}
