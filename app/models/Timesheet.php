<?php
/**
 * Model: Timesheet
 */
require_once __DIR__ . '/../../config/app.php';

class Timesheet {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ============ REGISTROS ============

    public function listarRegistros($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'tr.usuario_id = ?';
            $params[] = $filtros['usuario_id'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'tr.data >= ?';
            $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'tr.data <= ?';
            $params[] = $filtros['data_fim'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = 'tr.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['status'])) {
            $where[] = 'tr.status = ?';
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['chamado_id'])) {
            $where[] = 'tr.chamado_id = ?';
            $params[] = $filtros['chamado_id'];
        }
        if (!empty($filtros['projeto_id'])) {
            $where[] = 'tr.projeto_id = ?';
            $params[] = $filtros['projeto_id'];
        }

        return $this->db->fetchAll(
            "SELECT tr.*,
                u.nome as usuario_nome,
                c.titulo as chamado_titulo,
                p.nome as projeto_nome,
                ap.nome as aprovador_nome
             FROM timesheet_registros tr
             JOIN usuarios u ON tr.usuario_id = u.id
             LEFT JOIN chamados c ON tr.chamado_id = c.id
             LEFT JOIN projetos p ON tr.projeto_id = p.id
             LEFT JOIN usuarios ap ON tr.aprovado_por = ap.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY tr.data DESC, tr.hora_inicio DESC",
            $params
        );
    }

    public function getRegistro($id) {
        return $this->db->fetch(
            "SELECT tr.*,
                u.nome as usuario_nome,
                c.titulo as chamado_titulo,
                p.nome as projeto_nome,
                ap.nome as aprovador_nome
             FROM timesheet_registros tr
             JOIN usuarios u ON tr.usuario_id = u.id
             LEFT JOIN chamados c ON tr.chamado_id = c.id
             LEFT JOIN projetos p ON tr.projeto_id = p.id
             LEFT JOIN usuarios ap ON tr.aprovado_por = ap.id
             WHERE tr.id = ?",
            [$id]
        );
    }

    public function criarRegistro($dados) {
        $insert = [
            'usuario_id' => $dados['usuario_id'],
            'chamado_id' => $dados['chamado_id'] ?? null,
            'projeto_id' => $dados['projeto_id'] ?? null,
            'data' => $dados['data'],
            'hora_inicio' => $dados['hora_inicio'],
            'hora_fim' => $dados['hora_fim'] ?? null,
            'duracao_minutos' => null,
            'descricao' => $dados['descricao'] ?? null,
            'tipo' => $dados['tipo'] ?? 'chamado',
            'status' => 'em_andamento',
            'custo_hora' => $dados['custo_hora'] ?? 0
        ];

        if ($insert['hora_fim']) {
            $insert['duracao_minutos'] = $this->calcularDuracao($insert['hora_inicio'], $insert['hora_fim']);
            $insert['status'] = 'concluido';
        }

        $this->db->insert('timesheet_registros', $insert);
        return $this->db->lastInsertId();
    }

    public function atualizarRegistro($id, $dados) {
        $campos = ['chamado_id','projeto_id','data','hora_inicio','hora_fim','descricao','tipo','custo_hora'];
        $update = [];
        foreach ($campos as $c) {
            if (isset($dados[$c])) $update[$c] = $dados[$c];
        }

        if (isset($update['hora_inicio']) || isset($update['hora_fim'])) {
            $atual = $this->db->fetch("SELECT hora_inicio, hora_fim FROM timesheet_registros WHERE id = ?", [$id]);
            $inicio = $update['hora_inicio'] ?? $atual['hora_inicio'];
            $fim = $update['hora_fim'] ?? $atual['hora_fim'];
            if ($inicio && $fim) {
                $update['duracao_minutos'] = $this->calcularDuracao($inicio, $fim);
                $update['status'] = 'concluido';
            }
        }

        if (!empty($update)) {
            $this->db->update('timesheet_registros', $update, 'id = ?', [$id]);
        }
        return true;
    }

    public function pararTimer($id) {
        $registro = $this->db->fetch("SELECT * FROM timesheet_registros WHERE id = ?", [$id]);
        if (!$registro || $registro['hora_fim']) return false;

        $horaFim = date('H:i:s');
        $duracao = $this->calcularDuracao($registro['hora_inicio'], $horaFim);

        $this->db->update('timesheet_registros', [
            'hora_fim' => $horaFim,
            'duracao_minutos' => $duracao,
            'status' => 'concluido'
        ], 'id = ?', [$id]);

        return true;
    }

    public function excluirRegistro($id) {
        return $this->db->delete('timesheet_registros', 'id = ?', [$id]);
    }

    public function aprovarRegistro($id, $aprovadorId) {
        return $this->db->update('timesheet_registros', [
            'status' => 'aprovado',
            'aprovado_por' => $aprovadorId,
            'aprovado_em' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function rejeitarRegistro($id, $aprovadorId) {
        return $this->db->update('timesheet_registros', [
            'status' => 'rejeitado',
            'aprovado_por' => $aprovadorId,
            'aprovado_em' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    }

    public function getTimerAtivo($usuarioId) {
        return $this->db->fetch(
            "SELECT tr.*, c.titulo as chamado_titulo, p.nome as projeto_nome
             FROM timesheet_registros tr
             LEFT JOIN chamados c ON tr.chamado_id = c.id
             LEFT JOIN projetos p ON tr.projeto_id = p.id
             WHERE tr.usuario_id = ? AND tr.status = 'em_andamento' AND tr.hora_fim IS NULL
             ORDER BY tr.criado_em DESC LIMIT 1",
            [$usuarioId]
        );
    }

    // ============ RELATÓRIOS ============

    public function getResumoSemanal($usuarioId, $dataRef = null) {
        $dataRef = $dataRef ?: date('Y-m-d');
        $diaSemana = date('N', strtotime($dataRef));
        $inicio = date('Y-m-d', strtotime("-" . ($diaSemana - 1) . " days", strtotime($dataRef)));
        $fim = date('Y-m-d', strtotime("+" . (7 - $diaSemana) . " days", strtotime($dataRef)));

        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime("+$i days", strtotime($inicio)));
            $registro = $this->db->fetch(
                "SELECT SUM(duracao_minutos) as total_min, COUNT(*) as registros
                 FROM timesheet_registros WHERE usuario_id = ? AND data = ? AND status != 'rejeitado'",
                [$usuarioId, $d]
            );
            $dias[] = [
                'data' => $d,
                'dia_semana' => date('D', strtotime($d)),
                'total_minutos' => (int)($registro['total_min'] ?? 0),
                'registros' => (int)($registro['registros'] ?? 0)
            ];
        }

        $totalSemana = $this->db->fetch(
            "SELECT SUM(duracao_minutos) as total FROM timesheet_registros
             WHERE usuario_id = ? AND data BETWEEN ? AND ? AND status != 'rejeitado'",
            [$usuarioId, $inicio, $fim]
        );

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'dias' => $dias,
            'total_minutos' => (int)($totalSemana['total'] ?? 0)
        ];
    }

    public function getRelatorioPorTipo($filtros = []) {
        $where = ['1=1'];
        $params = [];
        if (!empty($filtros['usuario_id'])) { $where[] = 'tr.usuario_id = ?'; $params[] = $filtros['usuario_id']; }
        if (!empty($filtros['data_inicio'])) { $where[] = 'tr.data >= ?'; $params[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim'])) { $where[] = 'tr.data <= ?'; $params[] = $filtros['data_fim']; }

        return $this->db->fetchAll(
            "SELECT tr.tipo, SUM(tr.duracao_minutos) as total_minutos, COUNT(*) as total_registros
             FROM timesheet_registros tr
             WHERE " . implode(' AND ', $where) . " AND tr.status != 'rejeitado'
             GROUP BY tr.tipo ORDER BY total_minutos DESC",
            $params
        );
    }

    public function getRelatorioPorUsuario($filtros = []) {
        $where = ['1=1'];
        $params = [];
        if (!empty($filtros['data_inicio'])) { $where[] = 'tr.data >= ?'; $params[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim'])) { $where[] = 'tr.data <= ?'; $params[] = $filtros['data_fim']; }

        return $this->db->fetchAll(
            "SELECT u.nome as usuario, tr.usuario_id,
                    SUM(tr.duracao_minutos) as total_minutos,
                    COUNT(*) as total_registros,
                    SUM(tr.duracao_minutos * tr.custo_hora / 60) as custo_total
             FROM timesheet_registros tr
             JOIN usuarios u ON tr.usuario_id = u.id
             WHERE " . implode(' AND ', $where) . " AND tr.status != 'rejeitado'
             GROUP BY tr.usuario_id ORDER BY total_minutos DESC",
            $params
        );
    }

    // ============ OVERVIEW ============

    public function getOverview($usuarioId = null, $isGestor = false) {
        $hoje = date('Y-m-d');
        $mesInicio = date('Y-m-01');
        $mesFim = date('Y-m-t');

        // Horas hoje
        $whereUser = $usuarioId && !$isGestor ? 'AND usuario_id = ?' : '';
        $paramsUser = $usuarioId && !$isGestor ? [$hoje, $usuarioId] : [$hoje];
        $horasHoje = $this->db->fetch(
            "SELECT SUM(duracao_minutos) as total FROM timesheet_registros WHERE data = ? $whereUser AND status != 'rejeitado'",
            $paramsUser
        );

        // Horas mês
        $paramsMes = $usuarioId && !$isGestor ? [$mesInicio, $mesFim, $usuarioId] : [$mesInicio, $mesFim];
        $horasMes = $this->db->fetch(
            "SELECT SUM(duracao_minutos) as total FROM timesheet_registros WHERE data BETWEEN ? AND ? $whereUser AND status != 'rejeitado'",
            $paramsMes
        );

        // Pendentes de aprovação
        $pendentes = $this->db->fetch(
            "SELECT COUNT(*) as total FROM timesheet_registros WHERE status = 'concluido'"
        );

        // Timer ativo
        $timer = $usuarioId ? $this->getTimerAtivo($usuarioId) : null;

        // Custo mensal
        $custoMes = $this->db->fetch(
            "SELECT SUM(duracao_minutos * custo_hora / 60) as total FROM timesheet_registros
             WHERE data BETWEEN ? AND ? AND status != 'rejeitado'",
            [$mesInicio, $mesFim]
        );

        return [
            'horas_hoje' => (int)($horasHoje['total'] ?? 0),
            'horas_mes' => (int)($horasMes['total'] ?? 0),
            'pendentes_aprovacao' => (int)($pendentes['total'] ?? 0),
            'custo_mes' => floatval($custoMes['total'] ?? 0),
            'timer_ativo' => $timer
        ];
    }

    // ============ HELPERS ============

    private function calcularDuracao($inicio, $fim) {
        $t1 = strtotime($inicio);
        $t2 = strtotime($fim);
        if ($t2 < $t1) $t2 += 86400; // next day
        return max(0, round(($t2 - $t1) / 60));
    }
}
