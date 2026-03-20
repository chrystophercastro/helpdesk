<?php
/**
 * Controller: Dashboard
 */
require_once __DIR__ . '/../models/Chamado.php';
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Tarefa.php';
require_once __DIR__ . '/../models/Compra.php';

class DashboardController {
    private $chamado;
    private $projeto;
    private $tarefa;
    private $compra;
    private $db;

    public function __construct() {
        $this->chamado = new Chamado();
        $this->projeto = new Projeto();
        $this->tarefa = new Tarefa();
        $this->compra = new Compra();
        $this->db = Database::getInstance();
    }

    public function getStats($deptId = null) {
        // Helper para montar filtro de departamento em queries diretas
        $deptWhere = '';
        $deptWhereC = ''; // com alias c.
        $deptParams = [];
        if ($deptId) {
            $deptWhere = ' AND departamento_id = ?';
            $deptWhereC = ' AND c.departamento_id = ?';
            $deptParams = [(int)$deptId];
        }

        $stats = [
            'chamados' => [
                'abertos' => $this->chamado->contar(array_merge(['status' => 'aberto'], $deptId ? ['departamento_id' => $deptId] : [])),
                'em_andamento' => $this->chamado->contar(array_merge(['status' => 'em_atendimento'], $deptId ? ['departamento_id' => $deptId] : [])) 
                               + $this->chamado->contar(array_merge(['status' => 'em_analise'], $deptId ? ['departamento_id' => $deptId] : [])),
                'resolvidos' => $this->chamado->contar(array_merge(['status' => 'resolvido'], $deptId ? ['departamento_id' => $deptId] : [])),
                'fechados' => $this->chamado->contar(array_merge(['status' => 'fechado'], $deptId ? ['departamento_id' => $deptId] : [])),
                'total' => $this->chamado->contar($deptId ? ['departamento_id' => $deptId] : []),
                'por_status' => $this->chamado->contarPorStatus($deptId),
                'por_categoria' => $this->chamado->contarPorCategoria($deptId),
                'por_tecnico' => $this->chamado->contarPorTecnico($deptId),
                'tempo_medio' => $this->chamado->tempoMedioResolucao($deptId),
                'por_mes' => $this->chamado->chamadosPorMes(null, $deptId)
            ],
            'projetos' => [
                'por_status' => $this->projeto->contarPorStatus(),
                'ativos' => 0
            ],
            'tarefas' => [
                'por_coluna' => $this->tarefa->contarPorColuna(),
                'atrasadas' => count($this->tarefa->tarefasAtrasadas())
            ],
            'compras' => [
                'por_status' => $this->compra->contarPorStatus(),
                'valor_total' => $this->compra->valorTotal()
            ]
        ];

        // Contar projetos ativos
        foreach ($stats['projetos']['por_status'] as $ps) {
            if (in_array($ps['status'], ['planejamento', 'em_desenvolvimento', 'em_testes'])) {
                $stats['projetos']['ativos'] += $ps['total'];
            }
        }

        // ===== Métricas extras =====

        // SLA: chamados com SLA estourado
        $stats['chamados']['sla_estourados'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados c 
             INNER JOIN sla s ON c.sla_id = s.id 
             WHERE c.status NOT IN ('resolvido','fechado') 
             AND TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > s.tempo_resolucao" . $deptWhereC,
            $deptParams
        );

        // SLA: taxa de cumprimento (resolvidos dentro do prazo)
        $totalResolvidos = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE status IN ('resolvido','fechado') AND sla_id IS NOT NULL" . $deptWhere,
            $deptParams
        );
        $resolvidosDentroSLA = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             WHERE c.status IN ('resolvido','fechado')
             AND TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_resolucao, c.data_fechamento)) <= s.tempo_resolucao" . $deptWhereC,
            $deptParams
        );
        $stats['chamados']['sla_taxa'] = $totalResolvidos > 0 ? round(($resolvidosDentroSLA / $totalResolvidos) * 100) : 100;

        // Chamados abertos hoje
        $stats['chamados']['abertos_hoje'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE DATE(data_abertura) = CURDATE()" . $deptWhere,
            $deptParams
        );

        // Chamados resolvidos hoje
        $stats['chamados']['resolvidos_hoje'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE DATE(data_resolucao) = CURDATE()" . $deptWhere,
            $deptParams
        );

        // Por prioridade (abertos)
        $stats['chamados']['por_prioridade'] = $this->db->fetchAll(
            "SELECT prioridade, COUNT(*) as total FROM chamados 
             WHERE status NOT IN ('resolvido','fechado')" . $deptWhere . " GROUP BY prioridade",
            $deptParams
        );

        // Chamados recentes (últimos 5)
        $stats['chamados']['recentes'] = $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.status, c.prioridade, c.data_abertura,
                    s.nome as solicitante_nome, u.nome as tecnico_nome
             FROM chamados c
             LEFT JOIN solicitantes s ON c.solicitante_id = s.id
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             WHERE 1=1" . $deptWhereC . "
             ORDER BY c.data_abertura DESC LIMIT 5",
            $deptParams
        );

        // Compras pendentes de aprovação
        $stats['compras']['pendentes'] = 0;
        foreach ($stats['compras']['por_status'] as $cs) {
            if (in_array($cs['status'], ['solicitada', 'em_cotacao'])) {
                $stats['compras']['pendentes'] += $cs['total'];
            }
        }

        // Total tarefas por coluna (para mini-kanban)
        $tarefasCols = [];
        foreach ($stats['tarefas']['por_coluna'] as $tc) {
            $tarefasCols[$tc['coluna']] = $tc['total'];
        }
        $stats['tarefas']['colunas'] = $tarefasCols;
        $stats['tarefas']['total'] = array_sum($tarefasCols);

        // Por canal
        $stats['chamados']['por_canal'] = $this->db->fetchAll(
            "SELECT COALESCE(canal, 'interno') as canal, COUNT(*) as total 
             FROM chamados WHERE 1=1" . $deptWhere . " GROUP BY canal",
            $deptParams
        );

        // Satisfação média
        $stats['chamados']['satisfacao'] = $this->db->fetch(
            "SELECT AVG(avaliacao) as media, COUNT(avaliacao) as total 
             FROM chamados WHERE avaliacao IS NOT NULL AND avaliacao > 0" . $deptWhere,
            $deptParams
        );

        // Chamados por departamento
        if ($deptId) {
            // Gestor: só o seu departamento
            $stats['chamados']['por_departamento'] = $this->db->fetchAll(
                "SELECT d.id, d.nome, d.sigla, d.cor, d.icone,
                        COUNT(c.id) as total,
                        SUM(CASE WHEN c.status = 'aberto' THEN 1 ELSE 0 END) as abertos,
                        SUM(CASE WHEN c.status IN ('em_atendimento','em_analise') THEN 1 ELSE 0 END) as em_andamento,
                        SUM(CASE WHEN c.status IN ('resolvido','fechado') THEN 1 ELSE 0 END) as resolvidos
                 FROM departamentos d
                 LEFT JOIN chamados c ON c.departamento_id = d.id
                 WHERE d.id = ?
                 GROUP BY d.id",
                [(int)$deptId]
            );
        } else {
            // Admin: todos os departamentos
            $stats['chamados']['por_departamento'] = $this->db->fetchAll(
                "SELECT d.id, d.nome, d.sigla, d.cor, d.icone,
                        COUNT(c.id) as total,
                        SUM(CASE WHEN c.status = 'aberto' THEN 1 ELSE 0 END) as abertos,
                        SUM(CASE WHEN c.status IN ('em_atendimento','em_analise') THEN 1 ELSE 0 END) as em_andamento,
                        SUM(CASE WHEN c.status IN ('resolvido','fechado') THEN 1 ELSE 0 END) as resolvidos
                 FROM departamentos d
                 LEFT JOIN chamados c ON c.departamento_id = d.id
                 WHERE d.ativo = 1
                 GROUP BY d.id
                 ORDER BY d.ordem, d.nome"
            );
        }

        return $stats;
    }

    public function getStatsJSON() {
        return $this->getStats();
    }
}
