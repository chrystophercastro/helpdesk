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

    public function getStats() {
        $stats = [
            'chamados' => [
                'abertos' => $this->chamado->contar(['status' => 'aberto']),
                'em_andamento' => $this->chamado->contar(['status' => 'em_atendimento']) + $this->chamado->contar(['status' => 'em_analise']),
                'resolvidos' => $this->chamado->contar(['status' => 'resolvido']),
                'fechados' => $this->chamado->contar(['status' => 'fechado']),
                'total' => $this->chamado->contar(),
                'por_status' => $this->chamado->contarPorStatus(),
                'por_categoria' => $this->chamado->contarPorCategoria(),
                'por_tecnico' => $this->chamado->contarPorTecnico(),
                'tempo_medio' => $this->chamado->tempoMedioResolucao(),
                'por_mes' => $this->chamado->chamadosPorMes()
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
             AND TIMESTAMPDIFF(MINUTE, c.data_abertura, NOW()) > s.tempo_resolucao"
        );

        // SLA: taxa de cumprimento (resolvidos dentro do prazo)
        $totalResolvidos = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE status IN ('resolvido','fechado') AND sla_id IS NOT NULL"
        );
        $resolvidosDentroSLA = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados c
             INNER JOIN sla s ON c.sla_id = s.id
             WHERE c.status IN ('resolvido','fechado')
             AND TIMESTAMPDIFF(MINUTE, c.data_abertura, COALESCE(c.data_resolucao, c.data_fechamento)) <= s.tempo_resolucao"
        );
        $stats['chamados']['sla_taxa'] = $totalResolvidos > 0 ? round(($resolvidosDentroSLA / $totalResolvidos) * 100) : 100;

        // Chamados abertos hoje
        $stats['chamados']['abertos_hoje'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE DATE(data_abertura) = CURDATE()"
        );

        // Chamados resolvidos hoje
        $stats['chamados']['resolvidos_hoje'] = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM chamados WHERE DATE(data_resolucao) = CURDATE()"
        );

        // Por prioridade (abertos)
        $stats['chamados']['por_prioridade'] = $this->db->fetchAll(
            "SELECT prioridade, COUNT(*) as total FROM chamados 
             WHERE status NOT IN ('resolvido','fechado') GROUP BY prioridade"
        );

        // Chamados recentes (últimos 5)
        $stats['chamados']['recentes'] = $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.status, c.prioridade, c.data_abertura,
                    s.nome as solicitante_nome, u.nome as tecnico_nome
             FROM chamados c
             LEFT JOIN solicitantes s ON c.solicitante_id = s.id
             LEFT JOIN usuarios u ON c.tecnico_id = u.id
             ORDER BY c.data_abertura DESC LIMIT 5"
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
             FROM chamados GROUP BY canal"
        );

        // Satisfação média
        $stats['chamados']['satisfacao'] = $this->db->fetch(
            "SELECT AVG(avaliacao) as media, COUNT(avaliacao) as total 
             FROM chamados WHERE avaliacao IS NOT NULL AND avaliacao > 0"
        );

        return $stats;
    }

    public function getStatsJSON() {
        return $this->getStats();
    }
}
