<?php
/**
 * Model: Automacao
 * Motor de automações e rotinas do HelpDesk
 */
require_once __DIR__ . '/../../config/app.php';

class Automacao {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ============ CRUD ============

    public function listar($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (isset($filtros['ativo'])) {
            $where[] = 'a.ativo = ?';
            $params[] = (int)$filtros['ativo'];
        }
        if (!empty($filtros['trigger_tipo'])) {
            $where[] = 'a.trigger_tipo = ?';
            $params[] = $filtros['trigger_tipo'];
        }
        if (!empty($filtros['busca'])) {
            $where[] = '(a.nome LIKE ? OR a.descricao LIKE ?)';
            $busca = '%' . $filtros['busca'] . '%';
            $params[] = $busca;
            $params[] = $busca;
        }

        return $this->db->fetchAll(
            "SELECT a.*, u.nome as criado_por_nome
             FROM automacoes a
             LEFT JOIN usuarios u ON a.criado_por = u.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.ativo DESC, a.nome ASC",
            $params
        );
    }

    public function getById($id) {
        $auto = $this->db->fetch(
            "SELECT a.*, u.nome as criado_por_nome
             FROM automacoes a
             LEFT JOIN usuarios u ON a.criado_por = u.id
             WHERE a.id = ?",
            [$id]
        );
        if ($auto) {
            $auto['trigger_config'] = json_decode($auto['trigger_config'], true) ?: [];
            $auto['acao_config'] = json_decode($auto['acao_config'], true) ?: [];
            $auto['logs'] = $this->getLogs($id, 10);
        }
        return $auto;
    }

    public function criar($dados, $usuarioId) {
        $insert = [
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'trigger_tipo' => $dados['trigger_tipo'],
            'trigger_config' => json_encode($dados['trigger_config'] ?? []),
            'acao_tipo' => $dados['acao_tipo'],
            'acao_config' => json_encode($dados['acao_config'] ?? []),
            'ativo' => (int)($dados['ativo'] ?? 0),
            'criado_por' => $usuarioId
        ];

        $this->db->insert('automacoes', $insert);
        return $this->db->lastInsertId();
    }

    public function atualizar($id, $dados) {
        $update = [];
        $campos = ['nome', 'descricao', 'ativo'];
        foreach ($campos as $c) {
            if (isset($dados[$c])) $update[$c] = $dados[$c];
        }
        if (isset($dados['trigger_tipo'])) $update['trigger_tipo'] = $dados['trigger_tipo'];
        if (isset($dados['trigger_config'])) $update['trigger_config'] = json_encode($dados['trigger_config']);
        if (isset($dados['acao_tipo'])) $update['acao_tipo'] = $dados['acao_tipo'];
        if (isset($dados['acao_config'])) $update['acao_config'] = json_encode($dados['acao_config']);

        if (!empty($update)) {
            $this->db->update('automacoes', $update, 'id = ?', [$id]);
        }
        return true;
    }

    public function excluir($id) {
        return $this->db->delete('automacoes', 'id = ?', [$id]);
    }

    public function toggleAtivo($id) {
        $auto = $this->db->fetch("SELECT ativo FROM automacoes WHERE id = ?", [$id]);
        if (!$auto) return false;
        $novoStatus = $auto['ativo'] ? 0 : 1;
        $this->db->update('automacoes', ['ativo' => $novoStatus], 'id = ?', [$id]);
        return $novoStatus;
    }

    // ============ LOGS ============

    public function getLogs($automacaoId, $limite = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM automacao_logs
             WHERE automacao_id = ?
             ORDER BY executado_em DESC
             LIMIT " . (int)$limite,
            [$automacaoId]
        );
    }

    private function registrarLog($automacaoId, $status, $itensAfetados, $detalhes = null, $erro = null, $duracaoMs = 0) {
        $this->db->insert('automacao_logs', [
            'automacao_id' => $automacaoId,
            'status' => $status,
            'itens_afetados' => $itensAfetados,
            'detalhes' => $detalhes ? json_encode($detalhes) : null,
            'erro_mensagem' => $erro,
            'duracao_ms' => $duracaoMs
        ]);

        $this->db->query(
            "UPDATE automacoes SET ultima_execucao = NOW(), total_execucoes = total_execucoes + 1,
             total_itens_afetados = total_itens_afetados + ? WHERE id = ?",
            [$itensAfetados, $automacaoId]
        );
    }

    // ============ OVERVIEW / DASHBOARD ============

    public function getOverview() {
        $total = $this->db->fetch("SELECT COUNT(*) as t FROM automacoes")['t'];
        $ativas = $this->db->fetch("SELECT COUNT(*) as t FROM automacoes WHERE ativo = 1")['t'];
        $execHoje = $this->db->fetch(
            "SELECT COUNT(*) as t FROM automacao_logs WHERE DATE(executado_em) = CURDATE()"
        )['t'];
        $itensHoje = $this->db->fetch(
            "SELECT COALESCE(SUM(itens_afetados), 0) as t FROM automacao_logs WHERE DATE(executado_em) = CURDATE()"
        )['t'];
        $errosHoje = $this->db->fetch(
            "SELECT COUNT(*) as t FROM automacao_logs WHERE DATE(executado_em) = CURDATE() AND status = 'erro'"
        )['t'];

        $ultimasExecucoes = $this->db->fetchAll(
            "SELECT l.*, a.nome as automacao_nome, a.trigger_tipo, a.acao_tipo
             FROM automacao_logs l
             JOIN automacoes a ON l.automacao_id = a.id
             ORDER BY l.executado_em DESC LIMIT 15"
        );

        $porTrigger = $this->db->fetchAll(
            "SELECT trigger_tipo, COUNT(*) as total, SUM(ativo) as ativas FROM automacoes GROUP BY trigger_tipo ORDER BY total DESC"
        );

        $topAutomacoes = $this->db->fetchAll(
            "SELECT a.id, a.nome, a.trigger_tipo, a.acao_tipo, a.total_execucoes, a.total_itens_afetados, a.ultima_execucao
             FROM automacoes a WHERE a.ativo = 1 ORDER BY a.total_execucoes DESC LIMIT 5"
        );

        return [
            'total' => (int)$total,
            'ativas' => (int)$ativas,
            'inativas' => (int)$total - (int)$ativas,
            'execucoes_hoje' => (int)$execHoje,
            'itens_afetados_hoje' => (int)$itensHoje,
            'erros_hoje' => (int)$errosHoje,
            'ultimas_execucoes' => $ultimasExecucoes,
            'por_trigger' => $porTrigger,
            'top_automacoes' => $topAutomacoes
        ];
    }

    // ============ MOTOR DE EXECUÇÃO ============

    /**
     * Executa todas as automações ativas e elegíveis
     * Deve ser chamado via cron ou endpoint periódico
     */
    public function executarTodas() {
        $automacoes = $this->db->fetchAll(
            "SELECT * FROM automacoes WHERE ativo = 1"
        );

        $resultados = [];
        foreach ($automacoes as $auto) {
            $auto['trigger_config'] = json_decode($auto['trigger_config'], true) ?: [];
            $auto['acao_config'] = json_decode($auto['acao_config'], true) ?: [];

            if ($this->deveExecutar($auto)) {
                $resultados[] = $this->executarAutomacao($auto);
            }
        }
        return $resultados;
    }

    /**
     * Executa uma automação específica manualmente
     */
    public function executarManual($id) {
        $auto = $this->db->fetch("SELECT * FROM automacoes WHERE id = ?", [$id]);
        if (!$auto) return ['success' => false, 'error' => 'Automação não encontrada'];

        $auto['trigger_config'] = json_decode($auto['trigger_config'], true) ?: [];
        $auto['acao_config'] = json_decode($auto['acao_config'], true) ?: [];

        return $this->executarAutomacao($auto);
    }

    /**
     * Verifica se uma automação deve ser executada agora
     */
    private function deveExecutar($auto) {
        $tipo = $auto['trigger_tipo'];
        $config = $auto['trigger_config'];
        $ultimaExec = $auto['ultima_execucao'];

        // Agendados - verificar intervalo
        if (str_starts_with($tipo, 'agendado_')) {
            $horaConfig = $config['hora_execucao'] ?? '08:00';
            $horaAtual = date('H:i');

            // Só executa na hora configurada (janela de 5 min)
            $horaConfigTs = strtotime($horaConfig);
            $horaAtualTs = strtotime($horaAtual);
            if (abs($horaAtualTs - $horaConfigTs) > 300) return false;

            // Verifica se já executou hoje/esta semana/este mês
            if ($ultimaExec) {
                $lastTs = strtotime($ultimaExec);
                switch ($tipo) {
                    case 'agendado_diario':
                        if (date('Y-m-d', $lastTs) === date('Y-m-d')) return false;
                        break;
                    case 'agendado_semanal':
                        $diaConfig = $config['dia_semana'] ?? 1;
                        if (date('w') != $diaConfig) return false;
                        if (date('Y-W', $lastTs) === date('Y-W')) return false;
                        break;
                    case 'agendado_mensal':
                        $diaConfig = $config['dia_mes'] ?? 1;
                        if (date('j') != $diaConfig) return false;
                        if (date('Y-m', $lastTs) === date('Y-m')) return false;
                        break;
                }
            }
            return true;
        }

        // Event-based triggers - verificar se já executou nos últimos 5 minutos
        if ($ultimaExec && (time() - strtotime($ultimaExec)) < 300) {
            return false;
        }

        return true;
    }

    /**
     * Executa uma automação e registra o resultado
     */
    private function executarAutomacao($auto) {
        $inicio = microtime(true);

        try {
            $resultado = match ($auto['trigger_tipo']) {
                'chamado_resolvido_x_dias'  => $this->triggerChamadoResolvido($auto),
                'chamado_sem_resposta'      => $this->triggerChamadoSemResposta($auto),
                'sla_prestes_vencer'        => $this->triggerSlaPresteVencer($auto),
                'sla_violado'               => $this->triggerSlaViolado($auto),
                'contrato_vencendo'         => $this->triggerContratoVencendo($auto),
                'contrato_vencido'          => $this->triggerContratoVencido($auto),
                'monitor_offline'           => $this->triggerMonitorOffline($auto),
                'monitor_lento'             => $this->triggerMonitorLento($auto),
                'dispositivo_offline'       => $this->triggerDispositivoOffline($auto),
                'novo_chamado'              => $this->triggerNovoChamado($auto),
                'agendado_diario',
                'agendado_semanal',
                'agendado_mensal'           => $this->triggerAgendado($auto),
                'inventario_sem_responsavel'=> $this->triggerInventarioSemResponsavel($auto),
                default                     => ['itens' => [], 'msg' => 'Trigger não implementado']
            };

            $duracaoMs = (int)((microtime(true) - $inicio) * 1000);
            $itensAfetados = count($resultado['itens'] ?? []);

            $this->registrarLog(
                $auto['id'],
                $itensAfetados > 0 ? 'sucesso' : 'sucesso',
                $itensAfetados,
                $resultado,
                null,
                $duracaoMs
            );

            return [
                'success' => true,
                'automacao' => $auto['nome'],
                'itens_afetados' => $itensAfetados,
                'detalhes' => $resultado['msg'] ?? '',
                'duracao_ms' => $duracaoMs
            ];

        } catch (\Exception $e) {
            $duracaoMs = (int)((microtime(true) - $inicio) * 1000);
            $this->registrarLog($auto['id'], 'erro', 0, null, $e->getMessage(), $duracaoMs);

            return [
                'success' => false,
                'automacao' => $auto['nome'],
                'erro' => $e->getMessage(),
                'duracao_ms' => $duracaoMs
            ];
        }
    }

    // ============ TRIGGERS ============

    private function triggerChamadoResolvido($auto) {
        $dias = $auto['trigger_config']['dias'] ?? 7;
        $chamados = $this->db->fetchAll(
            "SELECT id, codigo, titulo FROM chamados
             WHERE status = 'resolvido'
             AND atualizado_em < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$dias]
        );

        if (empty($chamados)) return ['itens' => [], 'msg' => 'Nenhum chamado resolvido para fechar'];

        return $this->executarAcao($auto, $chamados, 'chamado');
    }

    private function triggerChamadoSemResposta($auto) {
        $horas = $auto['trigger_config']['horas'] ?? 24;
        $chamados = $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.tecnico_id
             FROM chamados c
             WHERE c.status IN ('aberto','em_atendimento')
             AND c.atualizado_em < DATE_SUB(NOW(), INTERVAL ? HOUR)
             AND NOT EXISTS (
                 SELECT 1 FROM chamado_comentarios cc
                 WHERE cc.chamado_id = c.id
                 AND cc.criado_em > DATE_SUB(NOW(), INTERVAL ? HOUR)
             )",
            [$horas, $horas]
        );

        if (empty($chamados)) return ['itens' => [], 'msg' => 'Todos os chamados foram respondidos'];

        return $this->executarAcao($auto, $chamados, 'chamado');
    }

    private function triggerSlaPresteVencer($auto) {
        $minutos = $auto['trigger_config']['minutos_restantes'] ?? 30;
        $chamados = $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.tecnico_id, c.prioridade,
                    s.tempo_resolucao, c.criado_em,
                    TIMESTAMPDIFF(MINUTE, c.criado_em, NOW()) as minutos_passados
             FROM chamados c
             LEFT JOIN sla s ON c.sla_id = s.id
             WHERE c.status IN ('aberto','em_atendimento')
             AND s.tempo_resolucao IS NOT NULL
             AND TIMESTAMPDIFF(MINUTE, c.criado_em, NOW()) >= (s.tempo_resolucao - ?)
             AND TIMESTAMPDIFF(MINUTE, c.criado_em, NOW()) < s.tempo_resolucao",
            [$minutos]
        );

        if (empty($chamados)) return ['itens' => [], 'msg' => 'Nenhum SLA em risco'];

        return $this->executarAcao($auto, $chamados, 'chamado');
    }

    private function triggerSlaViolado($auto) {
        $chamados = $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.tecnico_id, c.prioridade,
                    s.tempo_resolucao
             FROM chamados c
             LEFT JOIN sla s ON c.sla_id = s.id
             WHERE c.status IN ('aberto','em_atendimento')
             AND s.tempo_resolucao IS NOT NULL
             AND TIMESTAMPDIFF(MINUTE, c.criado_em, NOW()) > s.tempo_resolucao"
        );

        if (empty($chamados)) return ['itens' => [], 'msg' => 'Nenhum SLA violado'];

        return $this->executarAcao($auto, $chamados, 'chamado');
    }

    private function triggerContratoVencendo($auto) {
        $dias = $auto['trigger_config']['dias'] ?? 30;
        $contratos = $this->db->fetchAll(
            "SELECT c.id, c.titulo, c.numero, c.data_fim,
                    DATEDIFF(c.data_fim, CURDATE()) as dias_restantes,
                    f.nome as fornecedor_nome
             FROM contratos c
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
             WHERE c.status = 'ativo'
             AND c.data_fim IS NOT NULL
             AND c.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)",
            [$dias]
        );

        if (empty($contratos)) return ['itens' => [], 'msg' => 'Nenhum contrato vencendo'];

        return $this->executarAcao($auto, $contratos, 'contrato');
    }

    private function triggerContratoVencido($auto) {
        $contratos = $this->db->fetchAll(
            "SELECT c.id, c.titulo, c.numero, c.data_fim, f.nome as fornecedor_nome
             FROM contratos c
             LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
             WHERE c.status = 'ativo' AND c.data_fim < CURDATE()"
        );

        if (empty($contratos)) return ['itens' => [], 'msg' => 'Nenhum contrato vencido'];

        // Atualizar status para vencido
        foreach ($contratos as $ct) {
            $this->db->update('contratos', ['status' => 'vencido'], 'id = ?', [$ct['id']]);
        }

        return $this->executarAcao($auto, $contratos, 'contrato');
    }

    private function triggerMonitorOffline($auto) {
        $minutos = $auto['trigger_config']['minutos_offline'] ?? 5;

        // Verificar serviços monitorados (NOC) que estão offline
        $servicos = $this->db->fetchAll(
            "SELECT s.id, s.nome, s.host as endereco, s.tipo, s.status,
                    s.ultimo_check, s.grupo,
                    TIMESTAMPDIFF(MINUTE, s.ultimo_check, NOW()) as min_offline
             FROM monitor_servicos s
             WHERE s.status = 'offline'
             AND s.ativo = 1
             AND s.ultimo_check < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$minutos]
        );

        if (empty($servicos)) return ['itens' => [], 'msg' => 'Todos os serviços monitorados estão online'];

        return $this->executarAcao($auto, $servicos, 'monitor');
    }

    private function triggerMonitorLento($auto) {
        $limiteMs = $auto['trigger_config']['limite_ms'] ?? 3000;

        // Serviços online mas com resposta lenta (degradados)
        $servicos = $this->db->fetchAll(
            "SELECT s.id, s.nome, s.host as endereco, s.tipo, s.status,
                    s.resposta_ms, s.grupo, s.ultimo_check
             FROM monitor_servicos s
             WHERE s.ativo = 1
             AND s.status IN ('online', 'degradado')
             AND s.resposta_ms > ?
             AND s.ultimo_check > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
            [$limiteMs]
        );

        if (empty($servicos)) return ['itens' => [], 'msg' => 'Nenhum serviço com resposta lenta'];

        return $this->executarAcao($auto, $servicos, 'monitor');
    }

    private function triggerDispositivoOffline($auto) {
        $minutos = $auto['trigger_config']['minutos_offline'] ?? 5;

        // Dispositivos de rede offline (switches, roteadores, APs, etc.)
        $dispositivos = $this->db->fetchAll(
            "SELECT d.id, d.nome, d.ip as endereco, d.tipo, d.ultimo_status as status,
                    d.ultimo_ping, d.localizacao,
                    TIMESTAMPDIFF(MINUTE, d.ultimo_ping, NOW()) as min_offline
             FROM dispositivos_rede d
             WHERE d.ultimo_status = 'offline'
             AND d.ativo = 1
             AND d.ultimo_ping < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$minutos]
        );

        if (empty($dispositivos)) return ['itens' => [], 'msg' => 'Todos os dispositivos de rede estão online'];

        return $this->executarAcao($auto, $dispositivos, 'dispositivo_rede');
    }

    private function triggerNovoChamado($auto) {
        $config = $auto['trigger_config'];
        $where = ["c.status = 'aberto'", "c.tecnico_id IS NULL"];
        $params = [];

        if (!empty($config['categoria_nome'])) {
            $where[] = "cat.nome = ?";
            $params[] = $config['categoria_nome'];
        }
        if (!empty($config['prioridade'])) {
            $where[] = "c.prioridade = ?";
            $params[] = $config['prioridade'];
        }

        // Pegar apenas chamados dos últimos 10 minutos não processados
        $where[] = "c.criado_em > DATE_SUB(NOW(), INTERVAL 10 MINUTE)";

        $chamados = $this->db->fetchAll(
            "SELECT c.id, c.codigo, c.titulo, c.prioridade, cat.nome as categoria_nome
             FROM chamados c
             LEFT JOIN categorias cat ON c.categoria_id = cat.id
             WHERE " . implode(' AND ', $where),
            $params
        );

        if (empty($chamados)) return ['itens' => [], 'msg' => 'Nenhum chamado novo correspondente'];

        return $this->executarAcao($auto, $chamados, 'chamado');
    }

    private function triggerAgendado($auto) {
        $acaoTipo = $auto['acao_tipo'];

        switch ($acaoTipo) {
            case 'gerar_relatorio':
                return $this->acaoGerarRelatorio($auto);

            case 'limpar_notificacoes_antigas':
                return $this->acaoLimparNotificacoes($auto);

            case 'enviar_email_resumo':
                return $this->acaoEnviarResumo($auto);

            default:
                return ['itens' => [], 'msg' => 'Ação agendada executada (sem itens específicos)'];
        }
    }

    private function triggerInventarioSemResponsavel($auto) {
        $itens = $this->db->fetchAll(
            "SELECT id, nome, numero_patrimonio FROM inventario
             WHERE responsavel IS NULL OR responsavel = ''
             AND status = 'ativo'"
        );

        if (empty($itens)) return ['itens' => [], 'msg' => 'Todos os ativos têm responsável'];

        return $this->executarAcao($auto, $itens, 'inventario');
    }

    // ============ AÇÕES ============

    private function executarAcao($auto, $itens, $tipoItem) {
        $acao = $auto['acao_tipo'];
        $config = $auto['acao_config'];
        $ids = array_column($itens, 'id');
        $msg = '';

        switch ($acao) {
            case 'fechar_chamado':
                $mensagem = $config['mensagem'] ?? 'Fechado automaticamente por automação.';
                foreach ($ids as $id) {
                    $this->db->update('chamados', ['status' => 'fechado'], 'id = ?', [$id]);
                    // Registrar no histórico
                    $this->db->insert('chamado_historico', [
                        'chamado_id' => $id,
                        'usuario_id' => $auto['criado_por'],
                        'tipo' => 'status',
                        'descricao' => $mensagem,
                        'valor_anterior' => 'resolvido',
                        'valor_novo' => 'fechado'
                    ]);
                }
                $msg = count($ids) . " chamado(s) fechado(s) automaticamente";
                break;

            case 'atribuir_chamado':
                $tecnicoId = $config['usuario_id'] ?? null;
                if (!$tecnicoId) {
                    // Round-robin: pegar técnico com menos chamados
                    $tecnico = $this->db->fetch(
                        "SELECT u.id FROM usuarios u
                         WHERE u.tipo IN ('admin','tecnico') AND u.ativo = 1
                         ORDER BY (SELECT COUNT(*) FROM chamados c WHERE c.tecnico_id = u.id AND c.status IN ('aberto','em_atendimento')) ASC
                         LIMIT 1"
                    );
                    $tecnicoId = $tecnico ? $tecnico['id'] : null;
                }
                if ($tecnicoId) {
                    foreach ($ids as $id) {
                        $this->db->update('chamados', [
                            'tecnico_id' => $tecnicoId,
                            'status' => 'em_atendimento'
                        ], 'id = ?', [$id]);
                    }
                    $msg = count($ids) . " chamado(s) atribuído(s) ao técnico #$tecnicoId";
                }
                break;

            case 'mudar_status_chamado':
                $novoStatus = $config['status'] ?? 'em_atendimento';
                foreach ($ids as $id) {
                    $this->db->update('chamados', ['status' => $novoStatus], 'id = ?', [$id]);
                }
                $msg = count($ids) . " chamado(s) alterado(s) para '$novoStatus'";
                break;

            case 'mudar_prioridade_chamado':
                $novaPrioridade = $config['prioridade'] ?? 'alta';
                foreach ($ids as $id) {
                    $this->db->update('chamados', ['prioridade' => $novaPrioridade], 'id = ?', [$id]);
                }
                $msg = count($ids) . " chamado(s) com prioridade alterada para '$novaPrioridade'";
                break;

            case 'escalar_chamado':
                $notificarGestor = $config['notificar_gestor'] ?? true;
                if (!empty($config['mudar_prioridade'])) {
                    foreach ($ids as $id) {
                        $this->db->update('chamados', ['prioridade' => $config['mudar_prioridade']], 'id = ?', [$id]);
                    }
                }
                if ($notificarGestor) {
                    require_once __DIR__ . '/NotificacaoInterna.php';
                    $notif = new NotificacaoInterna();
                    $gestores = $this->db->fetchAll("SELECT id FROM usuarios WHERE tipo IN ('admin','gestor') AND ativo = 1");
                    foreach ($itens as $item) {
                        $notif->criarParaMultiplos(array_column($gestores, 'id'), [
                            'tipo' => 'warning',
                            'titulo' => '⚡ Chamado escalado: ' . ($item['codigo'] ?? '#' . $item['id']),
                            'mensagem' => ($item['titulo'] ?? '') . ' - SLA em risco',
                            'icone' => 'fa-exclamation-triangle',
                            'link' => '/helpdesk/index.php?page=chamados&ver=' . $item['id'],
                            'referencia_tipo' => 'chamado',
                            'referencia_id' => $item['id']
                        ]);
                    }
                }
                $msg = count($ids) . " chamado(s) escalado(s)";
                break;

            case 'enviar_notificacao':
                require_once __DIR__ . '/NotificacaoInterna.php';
                $notif = new NotificacaoInterna();
                $titulo = $config['titulo'] ?? 'Notificação de Automação';
                $destinatarios = $config['destinatarios'] ?? 'todos';

                $destIds = [];
                switch ($destinatarios) {
                    case 'gestores':
                        $destIds = array_column($this->db->fetchAll("SELECT id FROM usuarios WHERE tipo IN ('admin','gestor') AND ativo = 1"), 'id');
                        break;
                    case 'tecnicos':
                        $destIds = array_column($this->db->fetchAll("SELECT id FROM usuarios WHERE tipo IN ('admin','tecnico') AND ativo = 1"), 'id');
                        break;
                    default:
                        $destIds = array_column($this->db->fetchAll("SELECT id FROM usuarios WHERE ativo = 1"), 'id');
                }

                foreach ($itens as $item) {
                    $nomeItem = $item['titulo'] ?? $item['nome'] ?? $item['codigo'] ?? '#' . $item['id'];
                    $notif->criarParaMultiplos($destIds, [
                        'tipo' => 'warning',
                        'titulo' => $titulo,
                        'mensagem' => $nomeItem,
                        'icone' => 'fa-robot',
                        'link' => $tipoItem === 'chamado' ? '/helpdesk/index.php?page=chamados&ver=' . $item['id'] : null,
                        'referencia_tipo' => $tipoItem,
                        'referencia_id' => $item['id']
                    ]);
                }
                $msg = count($itens) . " notificação(ões) enviada(s) para " . count($destIds) . " usuário(s)";
                break;

            case 'atualizar_contrato_status':
                foreach ($ids as $id) {
                    $this->db->update('contratos', ['status' => 'vencido'], 'id = ?', [$id]);
                }
                $msg = count($ids) . " contrato(s) atualizado(s) para 'vencido'";
                break;

            case 'criar_tarefa':
                // Criar tarefas no módulo de projetos para cada item encontrado
                $projetoId = $config['projeto_id'] ?? null;
                $responsavelId = $config['responsavel_id'] ?? $auto['criado_por'];
                $criadas = 0;
                if ($projetoId) {
                    foreach ($itens as $item) {
                        $nomeItem = $item['titulo'] ?? $item['nome'] ?? $item['codigo'] ?? '#' . $item['id'];
                        try {
                            $this->db->insert('tarefas', [
                                'projeto_id' => $projetoId,
                                'titulo' => '[Auto] ' . $nomeItem,
                                'descricao' => 'Tarefa criada automaticamente pela automação: ' . $auto['nome'],
                                'status' => 'pendente',
                                'prioridade' => 'media',
                                'responsavel_id' => $responsavelId,
                                'criado_por' => $auto['criado_por']
                            ]);
                            $criadas++;
                        } catch (\Exception $e) { /* tabela pode não existir */ }
                    }
                }
                $msg = $criadas . " tarefa(s) criada(s)";
                break;

            case 'executar_webhook':
                $url = $config['url'] ?? '';
                $metodo = strtoupper($config['metodo'] ?? 'POST');
                $headersConfig = $config['headers'] ?? [];
                $webhookOk = 0;
                $webhookErr = 0;
                if ($url) {
                    foreach ($itens as $item) {
                        try {
                            $payload = json_encode([
                                'automacao' => $auto['nome'],
                                'trigger' => $auto['trigger_tipo'],
                                'item' => $item,
                                'timestamp' => date('Y-m-d H:i:s')
                            ]);
                            $ch = curl_init($url);
                            curl_setopt_array($ch, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 10,
                                CURLOPT_CUSTOMREQUEST => $metodo,
                                CURLOPT_POSTFIELDS => $payload,
                                CURLOPT_HTTPHEADER => array_merge(
                                    ['Content-Type: application/json'],
                                    $headersConfig
                                ),
                                CURLOPT_SSL_VERIFYPEER => false
                            ]);
                            $response = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            if ($httpCode >= 200 && $httpCode < 400) $webhookOk++;
                            else $webhookErr++;
                        } catch (\Exception $e) { $webhookErr++; }
                    }
                }
                $msg = "Webhook: $webhookOk ok, $webhookErr erro(s) de " . count($itens) . " item(ns)";
                break;

            default:
                $msg = 'Ação "' . $acao . '" processada com ' . count($itens) . ' item(ns)';
        }

        return ['itens' => $ids, 'msg' => $msg];
    }

    // ============ AÇÕES AGENDADAS ESPECIAIS ============

    private function acaoGerarRelatorio($auto) {
        $tipo = $auto['acao_config']['tipo'] ?? 'chamados_pendentes';
        $dados = [];

        switch ($tipo) {
            case 'chamados_pendentes':
                $dados = $this->db->fetchAll(
                    "SELECT c.id, c.codigo, c.titulo, c.prioridade, c.status, c.criado_em,
                            u.nome as tecnico_nome, cat.nome as categoria_nome
                     FROM chamados c
                     LEFT JOIN usuarios u ON c.tecnico_id = u.id
                     LEFT JOIN categorias cat ON c.categoria_id = cat.id
                     WHERE c.status IN ('aberto','em_atendimento')
                     ORDER BY FIELD(c.prioridade,'urgente','alta','media','baixa'), c.criado_em ASC"
                );
                break;
        }

        if (!empty($auto['acao_config']['notificar'])) {
            require_once __DIR__ . '/NotificacaoInterna.php';
            $notif = new NotificacaoInterna();
            $gestores = $this->db->fetchAll("SELECT id FROM usuarios WHERE tipo IN ('admin','gestor') AND ativo = 1");
            $notif->criarParaMultiplos(array_column($gestores, 'id'), [
                'tipo' => 'info',
                'titulo' => '📊 Resumo: ' . count($dados) . ' chamados pendentes',
                'mensagem' => 'Relatório automático gerado em ' . date('d/m/Y H:i'),
                'icone' => 'fa-chart-bar',
                'link' => '/helpdesk/index.php?page=chamados'
            ]);
        }

        return ['itens' => array_column($dados, 'id'), 'msg' => 'Relatório gerado com ' . count($dados) . ' itens'];
    }

    private function acaoLimparNotificacoes($auto) {
        $dias = $auto['acao_config']['dias'] ?? 90;
        $result = $this->db->query(
            "DELETE FROM notificacoes WHERE lida = 1 AND criado_em < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$dias]
        );
        $removidas = $result->rowCount();
        return ['itens' => [], 'msg' => "$removidas notificação(ões) antigas removidas"];
    }

    private function acaoEnviarResumo($auto) {
        // Gerar um resumo e notificar
        $pendentes = $this->db->fetch("SELECT COUNT(*) as t FROM chamados WHERE status IN ('aberto','em_atendimento')")['t'];
        $resolvidos = $this->db->fetch("SELECT COUNT(*) as t FROM chamados WHERE status = 'resolvido' AND DATE(atualizado_em) = CURDATE()")['t'];

        require_once __DIR__ . '/NotificacaoInterna.php';
        $notif = new NotificacaoInterna();
        $gestores = $this->db->fetchAll("SELECT id FROM usuarios WHERE tipo IN ('admin','gestor') AND ativo = 1");
        $notif->criarParaMultiplos(array_column($gestores, 'id'), [
            'tipo' => 'info',
            'titulo' => "📋 Resumo: $pendentes pendentes, $resolvidos resolvidos hoje",
            'mensagem' => 'Resumo automático de ' . date('d/m/Y'),
            'icone' => 'fa-clipboard-list',
            'link' => '/helpdesk/index.php?page=dashboard'
        ]);

        return ['itens' => [], 'msg' => "Resumo enviado: $pendentes pendentes, $resolvidos resolvidos"];
    }

    // ============ HELPERS ============

    public function getTriggerLabels() {
        return [
            'novo_chamado' => ['label' => 'Novo chamado criado', 'icon' => 'fa-ticket-alt', 'cor' => '#3B82F6'],
            'chamado_sem_resposta' => ['label' => 'Chamado sem resposta', 'icon' => 'fa-clock', 'cor' => '#F59E0B'],
            'chamado_resolvido_x_dias' => ['label' => 'Chamado resolvido há X dias', 'icon' => 'fa-check-double', 'cor' => '#10B981'],
            'sla_prestes_vencer' => ['label' => 'SLA prestes a vencer', 'icon' => 'fa-hourglass-half', 'cor' => '#F59E0B'],
            'sla_violado' => ['label' => 'SLA violado', 'icon' => 'fa-exclamation-circle', 'cor' => '#EF4444'],
            'contrato_vencendo' => ['label' => 'Contrato vencendo', 'icon' => 'fa-file-contract', 'cor' => '#8B5CF6'],
            'contrato_vencido' => ['label' => 'Contrato vencido', 'icon' => 'fa-file-excel', 'cor' => '#EF4444'],
            'monitor_offline' => ['label' => 'Serviço offline (NOC)', 'icon' => 'fa-heartbeat', 'cor' => '#EF4444'],
            'monitor_lento' => ['label' => 'Serviço lento (NOC)', 'icon' => 'fa-tachometer-alt', 'cor' => '#F59E0B'],
            'dispositivo_offline' => ['label' => 'Dispositivo de rede offline', 'icon' => 'fa-network-wired', 'cor' => '#EF4444'],
            'agendado_diario' => ['label' => 'Agendado diário', 'icon' => 'fa-calendar-day', 'cor' => '#06B6D4'],
            'agendado_semanal' => ['label' => 'Agendado semanal', 'icon' => 'fa-calendar-week', 'cor' => '#06B6D4'],
            'agendado_mensal' => ['label' => 'Agendado mensal', 'icon' => 'fa-calendar-alt', 'cor' => '#06B6D4'],
            'novo_usuario' => ['label' => 'Novo usuário', 'icon' => 'fa-user-plus', 'cor' => '#10B981'],
            'inventario_sem_responsavel' => ['label' => 'Ativo sem responsável', 'icon' => 'fa-box-open', 'cor' => '#F59E0B']
        ];
    }

    public function getAcaoLabels() {
        return [
            'atribuir_chamado' => ['label' => 'Atribuir chamado', 'icon' => 'fa-user-check'],
            'mudar_status_chamado' => ['label' => 'Mudar status', 'icon' => 'fa-exchange-alt'],
            'mudar_prioridade_chamado' => ['label' => 'Mudar prioridade', 'icon' => 'fa-sort-amount-up'],
            'escalar_chamado' => ['label' => 'Escalar chamado', 'icon' => 'fa-level-up-alt'],
            'enviar_notificacao' => ['label' => 'Enviar notificação', 'icon' => 'fa-bell'],
            'criar_tarefa' => ['label' => 'Criar tarefa', 'icon' => 'fa-tasks'],
            'fechar_chamado' => ['label' => 'Fechar chamado', 'icon' => 'fa-times-circle'],
            'enviar_email_resumo' => ['label' => 'Enviar resumo', 'icon' => 'fa-envelope'],
            'atualizar_contrato_status' => ['label' => 'Atualizar contrato', 'icon' => 'fa-file-signature'],
            'gerar_relatorio' => ['label' => 'Gerar relatório', 'icon' => 'fa-chart-bar'],
            'executar_webhook' => ['label' => 'Executar webhook', 'icon' => 'fa-globe'],
            'limpar_notificacoes_antigas' => ['label' => 'Limpar notificações', 'icon' => 'fa-broom']
        ];
    }
}
