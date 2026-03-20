<?php
/**
 * Model: IAInsights
 * Gera prompts contextualizados para análises de IA em todos os módulos do Oracle X.
 * Cada método coleta dados do banco e retorna ['system' => ..., 'user' => ...] para streaming.
 */
require_once __DIR__ . '/Database.php';

class IAInsights {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Dispatcher: retorna prompts para o tipo de insight solicitado.
     */
    public function getPrompts($type, $params = []) {
        $method = 'insight_' . $type;
        if (!method_exists($this, $method)) {
            throw new \Exception("Tipo de insight desconhecido: $type");
        }
        return $this->$method($params);
    }

    // ========== HELPERS ==========

    private function safe($sql, $params = []) {
        try { return $this->db->fetchAll($sql, $params); } catch (\Exception $e) { return []; }
    }
    private function val($sql, $params = []) {
        try { return $this->db->fetchColumn($sql, $params); } catch (\Exception $e) { return 0; }
    }
    private function row($sql, $params = []) {
        try { return $this->db->fetch($sql, $params); } catch (\Exception $e) { return null; }
    }
    private function lines($arr, $fn) {
        return $arr ? implode("\n", array_map($fn, $arr)) : '(sem dados)';
    }

    // ==========================================================
    //  HIGH IMPACT
    // ==========================================================

    /**
     * 1. Dashboard — Briefing Diário
     */
    private function insight_dashboard_briefing($p) {
        $ab   = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE status IN ('aberto','novo')");
        $and  = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE status = 'em_andamento'");
        $rh   = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE status = 'resolvido' AND DATE(resolvido_em) = CURDATE()");
        $ah   = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE DATE(criado_em) = CURDATE()");
        $sla  = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE status NOT IN ('resolvido','fechado') AND sla_resolucao IS NOT NULL AND sla_resolucao < NOW()");
        $tm   = $this->val("SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR,criado_em,resolvido_em)),1) FROM chamados WHERE status='resolvido' AND resolvido_em >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)") ?: 0;

        $tecs = $this->safe("SELECT u.nome, COUNT(c.id) as t FROM chamados c JOIN usuarios u ON c.tecnico_id=u.id WHERE c.status IN ('aberto','novo','em_andamento') GROUP BY c.tecnico_id,u.nome ORDER BY t DESC LIMIT 8");
        $cats = $this->safe("SELECT COALESCE(cat.nome,'Sem cat.') as nome, COUNT(c.id) as t FROM chamados c LEFT JOIN categorias cat ON c.categoria_id=cat.id WHERE c.status NOT IN ('resolvido','fechado') GROUP BY cat.nome ORDER BY t DESC LIMIT 5");

        $s1 = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)");
        $s0 = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE())+7 DAY) AND criado_em < DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)");
        $trend = $s0 > 0 ? round(($s1 - $s0) / $s0 * 100) : 0;

        $pja = (int)$this->val("SELECT COUNT(*) FROM projetos WHERE status='em_andamento' AND data_fim IS NOT NULL AND data_fim < CURDATE()");
        $tfa = (int)$this->val("SELECT COUNT(*) FROM tarefas WHERE data_vencimento IS NOT NULL AND data_vencimento < CURDATE() AND coluna NOT IN ('concluido','done')");

        $ctx  = "DATA: " . date('d/m/Y H:i') . "\n\n";
        $ctx .= "CHAMADOS: {$ab} abertos, {$and} em andamento | SLA estourados: {$sla}\n";
        $ctx .= "HOJE: {$ah} abertos, {$rh} resolvidos | Tempo médio (30d): {$tm}h\n";
        $ctx .= "TENDÊNCIA SEMANAL: {$trend}% ({$s1} esta semana vs {$s0} anterior)\n\n";
        $ctx .= "CARGA POR TÉCNICO:\n" . $this->lines($tecs, fn($r) => "- {$r['nome']}: {$r['t']} tickets") . "\n\n";
        $ctx .= "TOP CATEGORIAS ABERTAS:\n" . $this->lines($cats, fn($r) => "- {$r['nome']}: {$r['t']}") . "\n\n";
        $ctx .= "ALERTAS: {$pja} projetos atrasados, {$tfa} tarefas atrasadas";

        return [
            'system' => 'Você é um analista sênior de TI. Gere um BRIEFING DIÁRIO conciso e acionável em português BR. Use emojis para destaque. Estruture em: 1) 📋 Resumo Executivo (3 frases), 2) 🚨 Pontos Críticos, 3) 📊 Tendências, 4) 👥 Distribuição de Carga, 5) ✅ Recomendações (ações concretas). Seja direto — interprete os dados, não repita.',
            'user' => $ctx
        ];
    }

    /**
     * 2a. Base de Conhecimento — Busca Inteligente
     */
    private function insight_kb_search($p) {
        $query = trim($p['query'] ?? '');
        if (!$query) throw new \Exception('Informe o termo de busca.');

        $artigos = $this->safe("SELECT id, titulo, SUBSTRING(problema,1,200) as problema, SUBSTRING(solucao,1,200) as solucao, visualizacoes FROM artigos WHERE ativo = 1 ORDER BY visualizacoes DESC LIMIT 50");
        if (!$artigos) {
            $artigos = $this->safe("SELECT id, titulo, SUBSTRING(conteudo,1,300) as problema FROM base_conhecimento WHERE ativo = 1 ORDER BY criado_em DESC LIMIT 50");
        }

        $list = $this->lines($artigos, fn($a) => "#{$a['id']} | {$a['titulo']} | " . ($a['problema'] ?? '') . " | " . ($a['solucao'] ?? ''));

        return [
            'system' => 'Você é um especialista em suporte de TI. O usuário descreveu um problema. Analise a lista de artigos da base de conhecimento e retorne os TOP 5 mais relevantes, ranqueados por relevância. Para cada artigo, mostre: posição, ID (#), título, e uma breve explicação de por que é relevante. Se nenhum artigo for relevante, sugira o que o usuário deveria fazer. Responda em português BR.',
            'user' => "PROBLEMA DO USUÁRIO:\n{$query}\n\nARTIGOS DISPONÍVEIS:\nID | Título | Problema | Solução\n{$list}"
        ];
    }

    /**
     * 2b. Base de Conhecimento — Gerar Artigo de Chamado
     */
    private function insight_kb_generate($p) {
        $id = trim($p['chamado_id'] ?? '');
        if (!$id) throw new \Exception('Informe o ID ou código do chamado.');

        // Tentar buscar por ID numérico ou código
        $chamado = $this->row("SELECT * FROM chamados WHERE id = ? OR codigo = ? LIMIT 1", [$id, $id]);
        if (!$chamado) throw new \Exception("Chamado '{$id}' não encontrado.");

        $respostas = $this->safe("SELECT r.mensagem, r.tipo, u.nome as autor FROM chamado_respostas r LEFT JOIN usuarios u ON r.usuario_id = u.id WHERE r.chamado_id = ? ORDER BY r.criado_em", [$chamado['id']]);
        $respTxt = $this->lines($respostas, fn($r) => "[{$r['tipo']}] {$r['autor']}: {$r['mensagem']}");

        $ctx  = "CHAMADO #{$chamado['id']} ({$chamado['codigo']})\n";
        $ctx .= "Título: {$chamado['titulo']}\n";
        $ctx .= "Descrição: {$chamado['descricao']}\n";
        $ctx .= "Status: {$chamado['status']} | Prioridade: {$chamado['prioridade']}\n\n";
        $ctx .= "HISTÓRICO DE RESPOSTAS:\n{$respTxt}";

        return [
            'system' => 'Você é um escritor técnico sênior. Transforme este chamado resolvido em um ARTIGO de Base de Conhecimento. Estruture em: **Título** (claro e pesquisável), **Problema** (descrição do sintoma), **Causa** (diagnóstico), **Solução** (passo a passo numerado), **Prevenção** (dicas). Use linguagem objetiva e técnica em português BR. Formate em Markdown.',
            'user' => $ctx
        ];
    }

    /**
     * 3. Relatórios — Narrador Executivo
     */
    private function insight_relatorios_narrator($p) {
        $de  = $p['de'] ?? date('Y-m-01');
        $ate = $p['ate'] ?? date('Y-m-d');

        $total     = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE criado_em BETWEEN ? AND ?", [$de, "$ate 23:59:59"]);
        $resolvidos = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE status='resolvido' AND resolvido_em BETWEEN ? AND ?", [$de, "$ate 23:59:59"]);
        $tm         = $this->val("SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR,criado_em,resolvido_em)),1) FROM chamados WHERE status='resolvido' AND resolvido_em BETWEEN ? AND ?", [$de, "$ate 23:59:59"]) ?: 0;
        $sla_e      = (int)$this->val("SELECT COUNT(*) FROM chamados WHERE criado_em BETWEEN ? AND ? AND sla_resolucao IS NOT NULL AND (resolvido_em > sla_resolucao OR (status NOT IN ('resolvido','fechado') AND sla_resolucao < NOW()))", [$de, "$ate 23:59:59"]);

        $porCat = $this->safe("SELECT COALESCE(cat.nome,'Sem cat.') as nome, COUNT(*) as t FROM chamados c LEFT JOIN categorias cat ON c.categoria_id=cat.id WHERE c.criado_em BETWEEN ? AND ? GROUP BY cat.nome ORDER BY t DESC LIMIT 8", [$de, "$ate 23:59:59"]);
        $porTec = $this->safe("SELECT u.nome, COUNT(*) as t, SUM(CASE WHEN c.status='resolvido' THEN 1 ELSE 0 END) as resolvidos FROM chamados c JOIN usuarios u ON c.tecnico_id=u.id WHERE c.criado_em BETWEEN ? AND ? GROUP BY c.tecnico_id,u.nome ORDER BY t DESC LIMIT 8", [$de, "$ate 23:59:59"]);
        $porPri = $this->safe("SELECT prioridade, COUNT(*) as t FROM chamados WHERE criado_em BETWEEN ? AND ? GROUP BY prioridade", [$de, "$ate 23:59:59"]);

        $txRes = $total > 0 ? round($resolvidos / $total * 100, 1) : 0;
        $txSLA = $total > 0 ? round(($total - $sla_e) / $total * 100, 1) : 100;

        $ctx  = "PERÍODO: " . date('d/m/Y', strtotime($de)) . " a " . date('d/m/Y', strtotime($ate)) . "\n\n";
        $ctx .= "KPIs:\n- Total chamados: {$total}\n- Resolvidos: {$resolvidos} ({$txRes}%)\n- Tempo médio: {$tm}h\n- SLA cumprido: {$txSLA}% ({$sla_e} estourados)\n\n";
        $ctx .= "POR CATEGORIA:\n" . $this->lines($porCat, fn($r) => "- {$r['nome']}: {$r['t']}") . "\n\n";
        $ctx .= "POR TÉCNICO:\n" . $this->lines($porTec, fn($r) => "- {$r['nome']}: {$r['t']} total, {$r['resolvidos']} resolvidos") . "\n\n";
        $ctx .= "POR PRIORIDADE:\n" . $this->lines($porPri, fn($r) => "- {$r['prioridade']}: {$r['t']}");

        return [
            'system' => 'Você é um analista de gestão de TI. Gere uma NARRAÇÃO EXECUTIVA deste relatório para apresentação à diretoria. Estruture em: 1) 📊 Visão Geral do Período, 2) 🏆 Destaques Positivos, 3) ⚠️ Pontos de Atenção, 4) 👥 Performance da Equipe, 5) 📋 Recomendações Estratégicas. Use linguagem profissional, dados percentuais e comparativos. Português BR. Formato Markdown.',
            'user' => $ctx
        ];
    }

    /**
     * 4. SLA Dashboard — Análise Preditiva + Causa Raiz
     */
    private function insight_sla_predictive($p) {
        // Chamados em risco (SLA próximo de estourar)
        $risco = $this->safe("SELECT c.id, c.codigo, c.titulo, c.prioridade, c.status,
            TIMESTAMPDIFF(MINUTE, NOW(), c.sla_resolucao) as min_restantes,
            u.nome as tecnico, COALESCE(cat.nome,'') as categoria
            FROM chamados c
            LEFT JOIN usuarios u ON c.tecnico_id = u.id
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE c.status NOT IN ('resolvido','fechado')
            AND c.sla_resolucao IS NOT NULL
            ORDER BY c.sla_resolucao ASC LIMIT 15");

        $estourados = $this->safe("SELECT COALESCE(cat.nome,'Sem cat.') as cat, COUNT(*) as t,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, c.sla_resolucao, COALESCE(c.resolvido_em, NOW()))),1) as avg_atraso_h
            FROM chamados c LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE (c.resolvido_em > c.sla_resolucao OR (c.status NOT IN ('resolvido','fechado') AND c.sla_resolucao < NOW()))
            AND c.criado_em >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY cat.nome ORDER BY t DESC LIMIT 5");

        $carga = $this->safe("SELECT u.nome, COUNT(*) as abertos,
            SUM(CASE WHEN c.sla_resolucao IS NOT NULL AND c.sla_resolucao < NOW() THEN 1 ELSE 0 END) as estourados
            FROM chamados c JOIN usuarios u ON c.tecnico_id = u.id
            WHERE c.status NOT IN ('resolvido','fechado')
            GROUP BY c.tecnico_id, u.nome ORDER BY abertos DESC LIMIT 8");

        $riscoTxt = $this->lines($risco, function($r) {
            $min = (int)$r['min_restantes'];
            $status = $min < 0 ? '🔴 ESTOURADO' : ($min < 60 ? '🟡 CRÍTICO' : ($min < 240 ? '🟠 ATENÇÃO' : '🟢 OK'));
            $tempo = $min < 0 ? abs($min).'min atrás' : ($min < 60 ? $min.'min' : round($min/60,1).'h');
            return "- {$status} #{$r['codigo']} \"{$r['titulo']}\" ({$r['prioridade']}) → {$r['tecnico']} | {$tempo}";
        });

        $ctx  = "DATA: " . date('d/m/Y H:i') . "\n\n";
        $ctx .= "CHAMADOS POR SLA (próximos a estourar):\n{$riscoTxt}\n\n";
        $ctx .= "CATEGORIAS COM MAIS SLA ESTOURADO (30d):\n" . $this->lines($estourados, fn($r) => "- {$r['cat']}: {$r['t']} estourados, atraso médio {$r['avg_atraso_h']}h") . "\n\n";
        $ctx .= "CARGA POR TÉCNICO:\n" . $this->lines($carga, fn($r) => "- {$r['nome']}: {$r['abertos']} abertos, {$r['estourados']} estourados");

        return [
            'system' => 'Você é um especialista em SLA e gestão de serviços de TI. Faça uma análise PREDITIVA e de CAUSA RAIZ. Estruture em: 1) 🚨 Alertas Imediatos (chamados que vão estourar SLA), 2) 📊 Análise de Causa Raiz (por que estão estourando?), 3) 👥 Sobrecarga da Equipe, 4) ✅ Ações Preventivas (redistribuição, priorização). Seja direto e acionável. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 5. Monitor/NOC — Correlação de Incidentes
     */
    private function insight_monitor_correlation($p) {
        $servicos = $this->safe("SELECT id, nome, url, tipo, status, ultimo_check, ultimo_tempo_ms, uptime_percent FROM monitor_servicos ORDER BY status ASC, nome LIMIT 30");
        $incidentes = $this->safe("SELECT i.id, s.nome as servico, i.tipo, i.inicio, i.fim, i.duracao_min, i.descricao
            FROM monitor_incidentes i JOIN monitor_servicos s ON i.servico_id = s.id
            WHERE i.inicio >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY i.inicio DESC LIMIT 20");
        $cmdb = $this->safe("SELECT ci.nome, ci.tipo, ci.criticidade FROM cmdb_itens ci WHERE ci.status = 'ativo' LIMIT 20");

        $svcTxt = $this->lines($servicos, fn($r) => "- [{$r['status']}] {$r['nome']} ({$r['tipo']}) uptime:{$r['uptime_percent']}% ping:{$r['ultimo_tempo_ms']}ms");
        $incTxt = $this->lines($incidentes, fn($r) => "- {$r['servico']}: {$r['tipo']} em {$r['inicio']}" . ($r['fim'] ? " (resolvido {$r['duracao_min']}min)" : " ⚠️ ATIVO"));

        $ctx  = "DATA: " . date('d/m/Y H:i') . "\n\n";
        $ctx .= "SERVIÇOS MONITORADOS:\n{$svcTxt}\n\n";
        $ctx .= "INCIDENTES RECENTES (7 dias):\n{$incTxt}\n\n";
        $ctx .= "ITENS CMDB ATIVOS:\n" . $this->lines($cmdb, fn($r) => "- {$r['nome']} ({$r['tipo']}, criticidade: {$r['criticidade']})");

        return [
            'system' => 'Você é um engenheiro de confiabilidade (SRE). Analise os serviços e incidentes para identificar CORRELAÇÕES e CAUSA RAIZ. Estruture em: 1) 🔴 Status Atual (serviços com problema), 2) 🔗 Correlações Detectadas (incidentes simultâneos, dependências), 3) 🔍 Hipótese de Causa Raiz, 4) ⚡ Ações Recomendadas. Se não houver incidentes, faça uma análise de saúde geral. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 6. Contratos — Inteligência de Renovação
     */
    private function insight_contratos_intelligence($p) {
        $ativos = $this->safe("SELECT c.id, c.titulo, c.fornecedor, c.valor_mensal, c.valor_total, c.data_inicio, c.data_fim, c.status,
            DATEDIFF(c.data_fim, CURDATE()) as dias_restantes
            FROM contratos c WHERE c.status IN ('ativo','renovando') ORDER BY c.data_fim ASC LIMIT 20");
        $vencendo = $this->safe("SELECT c.titulo, c.fornecedor, c.valor_mensal, c.data_fim,
            DATEDIFF(c.data_fim, CURDATE()) as dias
            FROM contratos c WHERE c.status = 'ativo' AND c.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY c.data_fim");

        $totalMensal = 0;
        foreach ($ativos as $c) $totalMensal += (float)($c['valor_mensal'] ?? 0);

        $ctx  = "DATA: " . date('d/m/Y') . "\n\n";
        $ctx .= "CONTRATOS ATIVOS: " . count($ativos) . " | Custo mensal total: R$" . number_format($totalMensal, 2, ',', '.') . "\n\n";
        $ctx .= "PORTFÓLIO:\n" . $this->lines($ativos, fn($r) => "- {$r['titulo']} ({$r['fornecedor']}) R$" . number_format($r['valor_mensal'] ?? 0, 2, ',', '.') . "/mês | vence em {$r['dias_restantes']}d ({$r['data_fim']})") . "\n\n";
        $ctx .= "⚠️ VENCENDO EM 90 DIAS:\n" . $this->lines($vencendo, fn($r) => "- {$r['titulo']} ({$r['fornecedor']}) em {$r['dias']} dias ({$r['data_fim']})");

        return [
            'system' => 'Você é um analista de contratos e procurement. Analise o portfólio de contratos e gere inteligência acionável. Estruture em: 1) 📋 Visão Geral do Portfólio, 2) ⚠️ Contratos em Risco (vencendo), 3) 💰 Oportunidades de Economia (consolidação, renegociação), 4) ✅ Plano de Ação (com datas). Português BR.',
            'user' => $ctx
        ];
    }

    // ==========================================================
    //  MEDIUM IMPACT
    // ==========================================================

    /**
     * 7. Kanban — Análise de Distribuição + Sugestão de Atribuição
     */
    private function insight_kanban_assignment($p) {
        $projId = $p['projeto_id'] ?? null;
        $where = $projId ? "AND t.projeto_id = " . (int)$projId : "";

        $distrib = $this->safe("SELECT COALESCE(u.nome,'Não atribuída') as nome, t.coluna, COUNT(*) as total
            FROM tarefas t LEFT JOIN usuarios u ON t.responsavel_id = u.id
            WHERE 1=1 {$where}
            GROUP BY t.responsavel_id, u.nome, t.coluna ORDER BY total DESC");
        $atrasadas = $this->safe("SELECT t.titulo, COALESCE(u.nome,'Não atribuída') as responsavel, t.prioridade, t.data_vencimento, t.coluna
            FROM tarefas t LEFT JOIN usuarios u ON t.responsavel_id = u.id
            WHERE t.data_vencimento < CURDATE() AND t.coluna NOT IN ('concluido','done') {$where}
            ORDER BY t.data_vencimento LIMIT 10");
        $carga = $this->safe("SELECT u.nome, COUNT(*) as t,
            SUM(CASE WHEN t.coluna IN ('concluido','done') THEN 1 ELSE 0 END) as feitas,
            SUM(CASE WHEN t.data_vencimento < CURDATE() AND t.coluna NOT IN ('concluido','done') THEN 1 ELSE 0 END) as atrasadas
            FROM tarefas t JOIN usuarios u ON t.responsavel_id = u.id WHERE 1=1 {$where} GROUP BY t.responsavel_id, u.nome ORDER BY t DESC LIMIT 10");

        $ctx  = "DISTRIBUIÇÃO KANBAN:\n" . $this->lines($distrib, fn($r) => "- {$r['nome']} | {$r['coluna']}: {$r['total']}") . "\n\n";
        $ctx .= "TAREFAS ATRASADAS:\n" . $this->lines($atrasadas, fn($r) => "- \"{$r['titulo']}\" → {$r['responsavel']} ({$r['prioridade']}) venceu {$r['data_vencimento']}") . "\n\n";
        $ctx .= "CARGA POR MEMBRO:\n" . $this->lines($carga, fn($r) => "- {$r['nome']}: {$r['t']} total, {$r['feitas']} feitas, {$r['atrasadas']} atrasadas");

        return [
            'system' => 'Você é um gerente de projetos ágil. Analise a distribuição de tarefas no Kanban e sugira otimizações. Estruture em: 1) 📊 Análise de Distribuição (equilíbrio de carga), 2) 🚨 Gargalos Identificados, 3) 🔄 Sugestões de Redistribuição (quem está sobrecarregado, quem tem capacidade), 4) ⏰ Tarefas Urgentes. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 8. Projetos — Análise de Risco
     */
    private function insight_projeto_risk($p) {
        $projId = $p['projeto_id'] ?? null;
        $where = $projId ? "WHERE p.id = " . (int)$projId : "WHERE p.status IN ('em_andamento','planejamento')";

        $projetos = $this->safe("SELECT p.id, p.nome, p.status, p.prioridade, p.progresso, p.data_inicio, p.data_fim,
            DATEDIFF(p.data_fim, CURDATE()) as dias_restantes,
            DATEDIFF(CURDATE(), p.data_inicio) as dias_decorridos,
            DATEDIFF(p.data_fim, p.data_inicio) as dias_total,
            (SELECT COUNT(*) FROM tarefas WHERE projeto_id=p.id) as total_tarefas,
            (SELECT COUNT(*) FROM tarefas WHERE projeto_id=p.id AND coluna IN ('concluido','done')) as tarefas_feitas,
            (SELECT COUNT(*) FROM tarefas WHERE projeto_id=p.id AND data_vencimento < CURDATE() AND coluna NOT IN ('concluido','done')) as tarefas_atrasadas
            FROM projetos p {$where} ORDER BY p.data_fim ASC LIMIT 10");

        $ctx = "DATA: " . date('d/m/Y') . "\n\nPROJETOS:\n";
        foreach ($projetos as $pr) {
            $pctTempo = $pr['dias_total'] > 0 ? round($pr['dias_decorridos'] / $pr['dias_total'] * 100) : 0;
            $pctTarefas = $pr['total_tarefas'] > 0 ? round($pr['tarefas_feitas'] / $pr['total_tarefas'] * 100) : 0;
            $ctx .= "\n📁 {$pr['nome']} ({$pr['status']}, {$pr['prioridade']})\n";
            $ctx .= "  Prazo: {$pr['data_inicio']} → {$pr['data_fim']} ({$pr['dias_restantes']}d restantes)\n";
            $ctx .= "  Progresso declarado: {$pr['progresso']}% | Tarefas: {$pctTarefas}% ({$pr['tarefas_feitas']}/{$pr['total_tarefas']})\n";
            $ctx .= "  Tempo consumido: {$pctTempo}% | Atrasadas: {$pr['tarefas_atrasadas']}\n";
        }

        return [
            'system' => 'Você é um gerente de riscos de projetos. Analise cada projeto e classifique o risco (🟢 BAIXO, 🟡 MÉDIO, 🔴 ALTO, ⚫ CRÍTICO) baseado na relação progresso vs tempo. Estruture em: 1) 📊 Matriz de Risco (cada projeto), 2) ⚠️ Projetos Críticos (detalhado), 3) 📈 Velocidade vs Necessária, 4) ✅ Recomendações (ações para mitigar). Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 9. Inventário — Ciclo de Vida dos Ativos
     */
    private function insight_inventario_lifecycle($p) {
        $porTipo = $this->safe("SELECT tipo, COUNT(*) as t,
            ROUND(AVG(DATEDIFF(CURDATE(), data_aquisicao)/365),1) as idade_media,
            SUM(CASE WHEN DATEDIFF(CURDATE(), data_aquisicao) > 1825 THEN 1 ELSE 0 END) as velhos_5anos
            FROM inventario WHERE status != 'descartado' GROUP BY tipo ORDER BY t DESC");
        $antigos = $this->safe("SELECT nome, tipo, marca, modelo, data_aquisicao,
            ROUND(DATEDIFF(CURDATE(), data_aquisicao)/365,1) as idade_anos, status
            FROM inventario WHERE DATEDIFF(CURDATE(), data_aquisicao) > 1460 AND status != 'descartado'
            ORDER BY data_aquisicao LIMIT 15");
        $total = (int)$this->val("SELECT COUNT(*) FROM inventario WHERE status != 'descartado'");

        // Chamados por ativo (se houver vinculação)
        $chamPorAtivo = $this->safe("SELECT i.nome, i.tipo, COUNT(c.id) as chamados
            FROM inventario i JOIN chamados c ON c.ativo_id = i.id
            WHERE c.criado_em >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY i.id, i.nome, i.tipo ORDER BY chamados DESC LIMIT 10");

        $ctx  = "INVENTÁRIO GERAL: {$total} ativos\n\n";
        $ctx .= "POR TIPO:\n" . $this->lines($porTipo, fn($r) => "- {$r['tipo']}: {$r['t']} ativos, idade média {$r['idade_media']} anos, {$r['velhos_5anos']} com 5+ anos") . "\n\n";
        $ctx .= "ATIVOS MAIS ANTIGOS (4+ anos):\n" . $this->lines($antigos, fn($r) => "- {$r['nome']} ({$r['marca']} {$r['modelo']}) — {$r['idade_anos']} anos, status: {$r['status']}") . "\n\n";
        if ($chamPorAtivo) {
            $ctx .= "ATIVOS COM MAIS CHAMADOS (12 meses):\n" . $this->lines($chamPorAtivo, fn($r) => "- {$r['nome']} ({$r['tipo']}): {$r['chamados']} chamados");
        }

        return [
            'system' => 'Você é um gerente de ativos de TI. Analise o ciclo de vida do inventário e dê recomendações. Estruture em: 1) 📊 Visão Geral do Parque, 2) ⚠️ Ativos em Risco (velhos, alto uso, muitos chamados), 3) 💰 Análise de TCO (custo de manutenção vs substituição), 4) 📋 Plano de Renovação (priorizado). Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 10. Timesheet — Análise de Produtividade
     */
    private function insight_timesheet_productivity($p) {
        $de  = $p['de'] ?? date('Y-m-d', strtotime('-30 days'));
        $ate = $p['ate'] ?? date('Y-m-d');

        $porUser = $this->safe("SELECT u.nome, SUM(t.duracao_minutos) as min_total, COUNT(*) as entradas
            FROM timesheet_entradas t JOIN usuarios u ON t.usuario_id = u.id
            WHERE t.data BETWEEN ? AND ? GROUP BY t.usuario_id, u.nome ORDER BY min_total DESC LIMIT 10", [$de, $ate]);
        $porTipo = $this->safe("SELECT tipo, SUM(duracao_minutos) as min_total, COUNT(*) as entradas
            FROM timesheet_entradas WHERE data BETWEEN ? AND ? GROUP BY tipo ORDER BY min_total DESC", [$de, $ate]);
        $totalHoras = $this->val("SELECT ROUND(SUM(duracao_minutos)/60, 1) FROM timesheet_entradas WHERE data BETWEEN ? AND ?", [$de, $ate]) ?: 0;

        $ctx  = "PERÍODO: " . date('d/m/Y', strtotime($de)) . " a " . date('d/m/Y', strtotime($ate)) . "\n";
        $ctx .= "TOTAL: {$totalHoras}h registradas\n\n";
        $ctx .= "POR MEMBRO:\n" . $this->lines($porUser, fn($r) => "- {$r['nome']}: " . round($r['min_total']/60, 1) . "h ({$r['entradas']} registros)") . "\n\n";
        $ctx .= "POR TIPO DE ATIVIDADE:\n" . $this->lines($porTipo, fn($r) => "- {$r['tipo']}: " . round($r['min_total']/60, 1) . "h ({$r['entradas']} registros)");

        return [
            'system' => 'Você é um analista de produtividade. Analise os dados de timesheet e identifique padrões, gargalos e oportunidades. Estruture em: 1) ⏱️ Resumo de Horas, 2) 👥 Análise por Membro (quem está mais/menos produtivo), 3) 📊 Distribuição por Atividade (muito tempo em reuniões? chamados?), 4) ✅ Recomendações de Otimização. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 11. Financeiro — Detecção de Anomalias
     */
    private function insight_financeiro_anomalias($p) {
        $nfs = $this->safe("SELECT nf.numero, nf.valor_total, nf.data_emissao, f.razao_social as fornecedor
            FROM notas_fiscais nf LEFT JOIN fornecedores f ON nf.fornecedor_id = f.id
            ORDER BY nf.data_emissao DESC LIMIT 30");
        $contas = $this->safe("SELECT cp.descricao, cp.valor, cp.vencimento, cp.status, f.razao_social as fornecedor
            FROM contas_pagar cp LEFT JOIN fornecedores f ON cp.fornecedor_id = f.id
            WHERE cp.vencimento >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY cp.vencimento LIMIT 20");
        $vencidas = $this->safe("SELECT cp.descricao, cp.valor, cp.vencimento, DATEDIFF(CURDATE(), cp.vencimento) as dias_atraso
            FROM contas_pagar cp WHERE cp.status = 'pendente' AND cp.vencimento < CURDATE() ORDER BY cp.vencimento LIMIT 10");

        // Média por fornecedor
        $medias = $this->safe("SELECT f.razao_social, ROUND(AVG(nf.valor_total),2) as media, COUNT(*) as qtd,
            MAX(nf.valor_total) as maximo, MIN(nf.valor_total) as minimo
            FROM notas_fiscais nf JOIN fornecedores f ON nf.fornecedor_id = f.id
            GROUP BY nf.fornecedor_id, f.razao_social HAVING qtd >= 2 ORDER BY media DESC LIMIT 10");

        $ctx  = "DATA: " . date('d/m/Y') . "\n\n";
        $ctx .= "ÚLTIMAS NFs:\n" . $this->lines($nfs, fn($r) => "- NF {$r['numero']}: R$" . number_format($r['valor_total'] ?? 0, 2, ',', '.') . " ({$r['fornecedor']}) em {$r['data_emissao']}") . "\n\n";
        $ctx .= "CONTAS A PAGAR:\n" . $this->lines($contas, fn($r) => "- {$r['descricao']}: R$" . number_format($r['valor'] ?? 0, 2, ',', '.') . " vence {$r['vencimento']} [{$r['status']}]") . "\n\n";
        $ctx .= "⚠️ VENCIDAS:\n" . $this->lines($vencidas, fn($r) => "- {$r['descricao']}: R$" . number_format($r['valor'] ?? 0, 2, ',', '.') . " ({$r['dias_atraso']}d atraso)") . "\n\n";
        $ctx .= "MÉDIA POR FORNECEDOR:\n" . $this->lines($medias, fn($r) => "- {$r['razao_social']}: média R$" . number_format($r['media'] ?? 0, 2, ',', '.') . " (min R$" . number_format($r['minimo'] ?? 0, 2, ',', '.') . " / max R$" . number_format($r['maximo'] ?? 0, 2, ',', '.') . ") {$r['qtd']} NFs");

        return [
            'system' => 'Você é um auditor financeiro. Analise os dados financeiros e detecte ANOMALIAS, RISCOS e OPORTUNIDADES. Estruture em: 1) 🔍 Anomalias Detectadas (valores fora do padrão, NFs suspeitas), 2) ⚠️ Contas em Risco (vencidas, próximas do vencimento), 3) 📊 Análise de Fornecedores (padrões de preço), 4) ✅ Ações Recomendadas. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 12. CMDB — Análise de Impacto
     */
    private function insight_cmdb_impact($p) {
        $ciId = $p['ci_id'] ?? null;

        if ($ciId) {
            // Impacto de um CI específico
            $ci = $this->row("SELECT * FROM cmdb_itens WHERE id = ?", [(int)$ciId]);
            $rels = $this->safe("SELECT r.tipo_relacionamento, ci2.nome, ci2.tipo, ci2.criticidade, ci2.status
                FROM cmdb_relacionamentos r
                JOIN cmdb_itens ci2 ON (CASE WHEN r.item_origem_id = ? THEN r.item_destino_id ELSE r.item_origem_id END) = ci2.id
                WHERE r.item_origem_id = ? OR r.item_destino_id = ?
                LIMIT 20", [(int)$ciId, (int)$ciId, (int)$ciId]);

            $ctx  = "CI ANALISADO: {$ci['nome']} ({$ci['tipo']}, criticidade: {$ci['criticidade']}, status: {$ci['status']})\n\n";
            $ctx .= "RELACIONAMENTOS:\n" . $this->lines($rels, fn($r) => "- [{$r['tipo_relacionamento']}] {$r['nome']} ({$r['tipo']}, {$r['criticidade']})");
        } else {
            // Visão geral
            $cis = $this->safe("SELECT tipo, criticidade, COUNT(*) as t FROM cmdb_itens WHERE status = 'ativo' GROUP BY tipo, criticidade ORDER BY tipo, criticidade");
            $semRel = $this->safe("SELECT ci.nome, ci.tipo FROM cmdb_itens ci LEFT JOIN cmdb_relacionamentos r ON ci.id = r.item_origem_id OR ci.id = r.item_destino_id WHERE r.id IS NULL AND ci.status = 'ativo' LIMIT 10");
            $criticos = $this->safe("SELECT ci.nome, ci.tipo,
                (SELECT COUNT(*) FROM cmdb_relacionamentos WHERE item_origem_id = ci.id OR item_destino_id = ci.id) as deps
                FROM cmdb_itens ci WHERE ci.criticidade IN ('alta','critica') AND ci.status = 'ativo' ORDER BY deps DESC LIMIT 10");

            $ctx  = "VISÃO GERAL CMDB:\n" . $this->lines($cis, fn($r) => "- {$r['tipo']} ({$r['criticidade']}): {$r['t']}") . "\n\n";
            $ctx .= "CIs CRÍTICOS (mais dependências):\n" . $this->lines($criticos, fn($r) => "- {$r['nome']} ({$r['tipo']}): {$r['deps']} dependências") . "\n\n";
            $ctx .= "CIs SEM RELACIONAMENTO (possível risco):\n" . $this->lines($semRel, fn($r) => "- {$r['nome']} ({$r['tipo']})");
        }

        return [
            'system' => 'Você é um engenheiro de CMDB e gestão de mudanças. Analise os itens de configuração e seus relacionamentos para avaliar IMPACTO de falhas. Estruture em: 1) 🔗 Mapa de Dependências (o que depende de quê), 2) 💥 Simulação de Impacto (se X cair, o que é afetado?), 3) ⚠️ Pontos Únicos de Falha (SPOFs), 4) ✅ Recomendações de Resiliência. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 13. Chat — Assistente IA no contexto da conversa
     */
    private function insight_chat_ia($p) {
        $pergunta = trim($p['pergunta'] ?? $p['query'] ?? '');
        if (!$pergunta) throw new \Exception('Informe sua pergunta.');

        $canalId = $p['canal_id'] ?? $p['conversa_id'] ?? null;
        $msgs = [];
        if ($canalId) {
            $msgs = $this->safe("SELECT m.conteudo, u.nome as autor, m.criado_em
                FROM chat_mensagens m LEFT JOIN usuarios u ON m.usuario_id = u.id
                WHERE m.canal_id = ? ORDER BY m.criado_em DESC LIMIT 15", [(int)$canalId]);
            $msgs = array_reverse($msgs);
        }

        $kb = $this->safe("SELECT titulo, SUBSTRING(COALESCE(problema, conteudo, ''),1,150) as resumo FROM artigos WHERE ativo = 1 ORDER BY visualizacoes DESC LIMIT 20");
        if (!$kb) $kb = $this->safe("SELECT titulo, SUBSTRING(conteudo,1,150) as resumo FROM base_conhecimento WHERE ativo = 1 ORDER BY criado_em DESC LIMIT 20");

        $ctx = "";
        if ($msgs) {
            $ctx .= "CONTEXTO DA CONVERSA (últimas mensagens):\n" . $this->lines($msgs, fn($m) => "[{$m['autor']}]: {$m['conteudo']}") . "\n\n";
        }
        $ctx .= "BASE DE CONHECIMENTO DISPONÍVEL:\n" . $this->lines($kb, fn($a) => "- {$a['titulo']}: {$a['resumo']}") . "\n\n";
        $ctx .= "PERGUNTA DO USUÁRIO:\n{$pergunta}";

        return [
            'system' => 'Você é um assistente técnico de TI no chat interno da empresa. Responda à pergunta do usuário usando o contexto da conversa e a base de conhecimento quando aplicável. Seja conciso, técnico e útil. Se encontrar um artigo relevante na KB, cite-o. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 14. Deploy — Análise de Risco
     */
    private function insight_deploy_risk($p) {
        $recentes = $this->safe("SELECT d.id, d.descricao, d.status, d.criado_em, u.nome as usuario, d.total_arquivos
            FROM deploys d LEFT JOIN usuarios u ON d.usuario_id = u.id
            ORDER BY d.criado_em DESC LIMIT 10");
        $diaSemana = date('N'); // 1=seg, 7=dom
        $hora = date('H');
        $incidentes = $this->safe("SELECT COUNT(*) as t, DATE(d.criado_em) as data
            FROM deploys d WHERE d.status = 'erro' AND d.criado_em >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(d.criado_em) ORDER BY data DESC");

        $ctx  = "DATA/HORA: " . date('d/m/Y H:i') . " (dia da semana: {$diaSemana})\n\n";
        $ctx .= "DEPLOYS RECENTES:\n" . $this->lines($recentes, fn($r) => "- [{$r['status']}] {$r['descricao']} por {$r['usuario']} em {$r['criado_em']} ({$r['total_arquivos']} arquivos)") . "\n\n";
        $ctx .= "DEPLOYS COM ERRO (30d):\n" . $this->lines($incidentes, fn($r) => "- {$r['data']}: {$r['t']} erro(s)");

        return [
            'system' => 'Você é um engenheiro DevOps. Analise o histórico de deploys e avalie o RISCO de realizar um deploy agora. Considere: dia da semana (sexta/fim de semana = risco alto), horário, taxa de erros recentes, quantidade de arquivos. Estruture em: 1) 🚦 Nível de Risco Atual (Verde/Amarelo/Vermelho), 2) 📊 Histórico de Falhas, 3) ⏰ Janela Recomendada, 4) ✅ Checklist Pré-Deploy. Português BR.',
            'user' => $ctx
        ];
    }

    /**
     * 15. Automações — Sugestão de Automações
     */
    private function insight_automacoes_suggest($p) {
        // Padrões repetitivos em chamados
        $repetitivos = $this->safe("SELECT COALESCE(cat.nome,'Sem cat.') as categoria, COUNT(*) as total,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, c.criado_em, c.resolvido_em)),0) as tempo_medio_min
            FROM chamados c LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE c.status = 'resolvido' AND c.criado_em >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
            GROUP BY cat.nome HAVING total >= 3 ORDER BY total DESC LIMIT 10");

        // Automações existentes
        $existentes = $this->safe("SELECT nome, ativo, trigger_tipo, ultima_execucao FROM automacoes ORDER BY ultima_execucao DESC LIMIT 10");

        // Resoluções comuns (se houver padrão)
        $resolucoes = $this->safe("SELECT c.titulo, COUNT(*) as ocorrencias
            FROM chamados c WHERE c.status = 'resolvido' AND c.criado_em >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
            GROUP BY c.titulo HAVING ocorrencias >= 2 ORDER BY ocorrencias DESC LIMIT 10");

        $ctx  = "CATEGORIAS MAIS RESOLVIDAS (60 dias):\n" . $this->lines($repetitivos, fn($r) => "- {$r['categoria']}: {$r['total']} chamados, tempo médio {$r['tempo_medio_min']}min") . "\n\n";
        $ctx .= "TÍTULOS REPETIDOS:\n" . $this->lines($resolucoes, fn($r) => "- \"{$r['titulo']}\": {$r['ocorrencias']}x") . "\n\n";
        $ctx .= "AUTOMAÇÕES EXISTENTES:\n" . $this->lines($existentes, fn($r) => "- {$r['nome']} ({$r['trigger_tipo']}) " . ($r['ativo'] ? '✅' : '❌'));

        return [
            'system' => 'Você é um analista de processos e automação de TI. Analise os padrões repetitivos nos chamados e sugira AUTOMAÇÕES que podem ser criadas. Estruture em: 1) 🔍 Padrões Identificados (chamados repetitivos que podem ser automatizados), 2) ⚡ Automações Sugeridas (nome, trigger, ação, impacto estimado), 3) 🤖 Automações Existentes (status), 4) 📊 Impacto Estimado (horas/mês economizadas). Português BR.',
            'user' => $ctx
        ];
    }
}
