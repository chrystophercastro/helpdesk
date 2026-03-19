<?php
/**
 * Cron Runner: Verificações de infraestrutura + Automações
 * 
 * Executa em ordem:
 *   1. Verificação de serviços (Monitor NOC) — ping, HTTP, TCP, DNS
 *   2. Ping de dispositivos de rede — switches, roteadores, APs, etc.
 *   3. Motor de automações — todas as regras ativas
 * 
 * Configurar no Agendador de Tarefas do Windows ou crontab:
 * - A cada 5 minutos: php C:\xampp\htdocs\helpdesk\cron.php
 * - Ou via URL: http://localhost/helpdesk/cron.php?key=SUA_CHAVE_SECRETA
 * 
 * Para agendar no Windows (Agendador de Tarefas):
 *   Ação: C:\xampp\php\php.exe
 *   Argumentos: C:\xampp\htdocs\helpdesk\cron.php
 *   Disparar: A cada 5 minutos
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/models/Automacao.php';
require_once __DIR__ . '/app/models/Monitor.php';
require_once __DIR__ . '/app/models/Rede.php';

// Segurança: aceitar apenas CLI ou request com chave
$isCli = php_sapi_name() === 'cli';
$chaveValida = ($_GET['key'] ?? '') === 'helpdesk_cron_2026_secret';

if (!$isCli && !$chaveValida) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado. Use via CLI ou forneça a chave.']);
    exit;
}

// Prevenir execução simultânea
$lockFile = __DIR__ . '/storage/cron.lock';
if (!is_dir(__DIR__ . '/storage')) mkdir(__DIR__ . '/storage', 0755, true);

if (file_exists($lockFile)) {
    $lockTime = (int)file_get_contents($lockFile);
    if (time() - $lockTime < 300) { // 5 minutos
        $msg = "Cron já em execução (lock há " . (time() - $lockTime) . "s)";
        if ($isCli) echo "⚠️ $msg\n";
        else echo json_encode(['warning' => $msg]);
        exit;
    }
}

file_put_contents($lockFile, time());

// Log helper
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = "$logDir/cron_" . date('Y-m') . ".log";

function cronLog($msg, $logFile) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

try {
    $infraResultados = [];

    // ═══════════════════════════════════════════
    // FASE 1: Verificação de Serviços (Monitor NOC)
    // ═══════════════════════════════════════════
    try {
        $monitorInicio = microtime(true);
        $monitor = new Monitor();
        $monitorResults = $monitor->verificarTodos();
        $monitorMs = (int)((microtime(true) - $monitorInicio) * 1000);

        $monitorTotal = count($monitorResults);
        $monitorOnline = count(array_filter($monitorResults, fn($r) => ($r['status'] ?? '') === 'online'));
        $monitorOffline = $monitorTotal - $monitorOnline;

        $infraResultados[] = [
            'modulo' => 'Monitor NOC',
            'icone' => '🖥️',
            'success' => true,
            'msg' => "$monitorTotal serviço(s) verificado(s) ($monitorOnline online, $monitorOffline com problemas)",
            'duracao_ms' => $monitorMs
        ];

        cronLog("[MONITOR] $monitorTotal serviços verificados ($monitorOnline online, $monitorOffline problemas) [{$monitorMs}ms]", $logFile);
    } catch (Exception $e) {
        $infraResultados[] = [
            'modulo' => 'Monitor NOC',
            'icone' => '🖥️',
            'success' => false,
            'msg' => 'Erro: ' . $e->getMessage(),
            'duracao_ms' => 0
        ];
        cronLog("[MONITOR] ERRO: " . $e->getMessage(), $logFile);
    }

    // ═══════════════════════════════════════════
    // FASE 2: Ping de Dispositivos de Rede
    // ═══════════════════════════════════════════
    try {
        $redeInicio = microtime(true);
        $rede = new Rede();
        $redeResults = $rede->pingarTodos();
        $redeMs = (int)((microtime(true) - $redeInicio) * 1000);

        $redeTotal = count($redeResults);
        $redeOnline = count(array_filter($redeResults, fn($r) => ($r['status'] ?? '') === 'online'));
        $redeOffline = $redeTotal - $redeOnline;

        $infraResultados[] = [
            'modulo' => 'Rede (Ping)',
            'icone' => '🌐',
            'success' => true,
            'msg' => "$redeTotal dispositivo(s) pingado(s) ($redeOnline online, $redeOffline offline)",
            'duracao_ms' => $redeMs
        ];

        cronLog("[REDE] $redeTotal dispositivos pingados ($redeOnline online, $redeOffline offline) [{$redeMs}ms]", $logFile);
    } catch (Exception $e) {
        $infraResultados[] = [
            'modulo' => 'Rede (Ping)',
            'icone' => '🌐',
            'success' => false,
            'msg' => 'Erro: ' . $e->getMessage(),
            'duracao_ms' => 0
        ];
        cronLog("[REDE] ERRO: " . $e->getMessage(), $logFile);
    }

    // ═══════════════════════════════════════════
    // FASE 3: Motor de Automações
    // ═══════════════════════════════════════════
    $automacao = new Automacao();
    $resultados = $automacao->executarTodas();

    $total = count($resultados);
    $sucesso = count(array_filter($resultados, fn($r) => $r['success'] ?? false));
    $erros = $total - $sucesso;

    cronLog("[AUTO] $total automações processadas ($sucesso ok, $erros erros)", $logFile);

    // ═══════════════════════════════════════════
    // OUTPUT
    // ═══════════════════════════════════════════
    if ($isCli) {
        echo "🤖 HelpDesk Cron Runner\n";
        echo "═══════════════════════════════════════\n";
        echo "📅 " . date('d/m/Y H:i:s') . "\n\n";

        // Infraestrutura
        echo "📡 INFRAESTRUTURA\n";
        echo "───────────────────────────────────────\n";
        foreach ($infraResultados as $ir) {
            $icon = $ir['success'] ? $ir['icone'] : '❌';
            echo "$icon {$ir['modulo']}: {$ir['msg']} [{$ir['duracao_ms']}ms]\n";
        }
        echo "\n";

        // Automações
        echo "⚙️  AUTOMAÇÕES\n";
        echo "───────────────────────────────────────\n";
        if ($total === 0) {
            echo "ℹ️  Nenhuma automação elegível para execução.\n";
        } else {
            foreach ($resultados as $r) {
                $icon = ($r['success'] ?? false) ? '✅' : '❌';
                echo "$icon {$r['automacao']}";
                if (isset($r['itens_afetados'])) echo " ({$r['itens_afetados']} itens)";
                if (isset($r['detalhes'])) echo " — {$r['detalhes']}";
                if (isset($r['erro'])) echo " — ERRO: {$r['erro']}";
                echo " [{$r['duracao_ms']}ms]\n";
            }
        }
        echo "\n✨ Finalizado.\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'infraestrutura' => $infraResultados,
            'automacoes' => [
                'total' => $total,
                'sucesso' => $sucesso,
                'erros' => $erros,
                'resultados' => $resultados
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    $msg = "ERRO GERAL: " . $e->getMessage();
    cronLog($msg, $logFile);
    if ($isCli) echo "❌ $msg\n";
    else {
        header('Content-Type: application/json');
        echo json_encode(['error' => $msg]);
    }
} finally {
    @unlink($lockFile);
}
