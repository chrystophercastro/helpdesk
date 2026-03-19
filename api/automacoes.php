<?php
/**
 * API: Automações & Rotinas
 */
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/Automacao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

requireRole(['admin', 'gestor']);

$automacao = new Automacao();
$usuarioId = $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'overview':
            jsonResponse(['success' => true, 'data' => $automacao->getOverview()]);
            break;

        case 'cron_status':
            requireRole(['admin']);
            $cronStatus = getCronStatus();
            jsonResponse(['success' => true, 'data' => $cronStatus]);
            break;

        case 'cron_logs':
            requireRole(['admin']);
            $logFile = __DIR__ . '/../storage/logs/cron_' . date('Y-m') . '.log';
            $logs = [];
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $logs = array_slice(array_reverse($lines), 0, 50);
            }
            jsonResponse(['success' => true, 'data' => $logs]);
            break;

        case 'listar':
            $filtros = [];
            if (isset($_GET['ativo'])) $filtros['ativo'] = $_GET['ativo'];
            if (!empty($_GET['trigger_tipo'])) $filtros['trigger_tipo'] = $_GET['trigger_tipo'];
            if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
            jsonResponse(['success' => true, 'data' => $automacao->listar($filtros)]);
            break;

        case 'detalhe':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $auto = $automacao->getById($id);
            if (!$auto) jsonResponse(['success' => false, 'error' => 'Automação não encontrada'], 404);
            jsonResponse(['success' => true, 'data' => $auto]);
            break;

        case 'labels':
            jsonResponse([
                'success' => true,
                'data' => [
                    'triggers' => $automacao->getTriggerLabels(),
                    'acoes' => $automacao->getAcaoLabels()
                ]
            ]);
            break;

        case 'tecnicos':
            $db = Database::getInstance();
            $tecnicos = $db->fetchAll(
                "SELECT id, nome FROM usuarios WHERE tipo IN ('admin','tecnico') AND ativo = 1 ORDER BY nome"
            );
            jsonResponse(['success' => true, 'data' => $tecnicos]);
            break;

        case 'categorias':
            $db = Database::getInstance();
            $cats = $db->fetchAll("SELECT id, nome FROM categorias ORDER BY nome");
            jsonResponse(['success' => true, 'data' => $cats]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'criar':
            if (empty($input['nome']) || empty($input['trigger_tipo']) || empty($input['acao_tipo'])) {
                jsonResponse(['success' => false, 'error' => 'Nome, trigger e ação são obrigatórios'], 400);
            }
            $id = $automacao->criar($input, $usuarioId);
            jsonResponse(['success' => true, 'message' => 'Automação criada!', 'id' => $id]);
            break;

        case 'atualizar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $automacao->atualizar($id, $input);
            jsonResponse(['success' => true, 'message' => 'Automação atualizada!']);
            break;

        case 'excluir':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $automacao->excluir($id);
            jsonResponse(['success' => true, 'message' => 'Automação excluída!']);
            break;

        case 'toggle':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $novoStatus = $automacao->toggleAtivo($id);
            $label = $novoStatus ? 'ativada' : 'desativada';
            jsonResponse(['success' => true, 'message' => "Automação $label!", 'ativo' => $novoStatus]);
            break;

        case 'executar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'error' => 'ID necessário'], 400);
            $resultado = $automacao->executarManual($id);
            jsonResponse(['success' => true, 'message' => 'Execução concluída!', 'resultado' => $resultado]);
            break;

        case 'executar_todas':
            requireRole(['admin']);
            $resultados = $automacao->executarTodas();
            jsonResponse(['success' => true, 'message' => count($resultados) . ' automação(ões) processada(s)', 'resultados' => $resultados]);
            break;

        case 'cron_instalar':
            requireRole(['admin']);
            $intervalo = (int)($input['intervalo'] ?? 5);
            if ($intervalo < 1) $intervalo = 1;
            if ($intervalo > 1440) $intervalo = 1440;
            $resultado = cronInstalar($intervalo);
            jsonResponse($resultado);
            break;

        case 'cron_desinstalar':
            requireRole(['admin']);
            $resultado = cronDesinstalar();
            jsonResponse($resultado);
            break;

        case 'cron_testar':
            requireRole(['admin']);
            $resultado = cronTestar();
            jsonResponse($resultado);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
    }
}

// ============ FUNÇÕES AUXILIARES CRON ============

/**
 * Retorna status da tarefa agendada do Windows
 */
function getCronStatus() {
    $taskName = 'HelpDesk_Cron';
    $status = [
        'instalado' => false,
        'task_name' => $taskName,
        'intervalo' => null,
        'proximo_execucao' => null,
        'ultimo_execucao' => null,
        'status_tarefa' => null,
        'ultimo_resultado' => null,
        'php_path' => detectPhpPath(),
        'cron_path' => realpath(__DIR__ . '/../cron.php') ?: __DIR__ . '/../cron.php',
        'lock_exists' => file_exists(__DIR__ . '/../storage/cron.lock'),
        'last_log_lines' => []
    ];

    // Verificar se a tarefa existe no Windows Task Scheduler
    $output = [];
    $code = 0;
    exec("schtasks /Query /TN \"$taskName\" /FO LIST /V 2>&1", $output, $code);

    if ($code === 0 && !empty($output)) {
        $status['instalado'] = true;
        $info = implode("\n", $output);

        // Parse das informações
        foreach ($output as $line) {
            $line = trim($line);
            if (preg_match('/^Pr[óo]xima Execu[çc][ãa]o:\s*(.+)$/iu', $line, $m)) {
                $status['proximo_execucao'] = trim($m[1]);
            } elseif (preg_match('/^Next Run Time:\s*(.+)$/i', $line, $m)) {
                $status['proximo_execucao'] = trim($m[1]);
            } elseif (preg_match('/^[ÚU]ltima Execu[çc][ãa]o:\s*(.+)$/iu', $line, $m)) {
                $status['ultimo_execucao'] = trim($m[1]);
            } elseif (preg_match('/^Last Run Time:\s*(.+)$/i', $line, $m)) {
                $status['ultimo_execucao'] = trim($m[1]);
            } elseif (preg_match('/^Status:\s*(.+)$/i', $line, $m)) {
                $status['status_tarefa'] = trim($m[1]);
            } elseif (preg_match('/^[ÚU]ltimo Resultado:\s*(.+)$/iu', $line, $m)) {
                $status['ultimo_resultado'] = trim($m[1]);
            } elseif (preg_match('/^Last Result:\s*(.+)$/i', $line, $m)) {
                $status['ultimo_resultado'] = trim($m[1]);
            } elseif (preg_match('/^Repetir a cada:\s*(.+)$/iu', $line, $m)) {
                $val = trim($m[1]);
                if (preg_match('/(\d+)/', $val, $n)) $status['intervalo'] = (int)$n[1];
            } elseif (preg_match('/^Repeat: Every:\s*(.+)$/i', $line, $m)) {
                $val = trim($m[1]);
                if (preg_match('/(\d+)/', $val, $n)) $status['intervalo'] = (int)$n[1];
            }
        }

        // Tentar pegar intervalo do trigger via XML
        if (!$status['intervalo']) {
            $xmlOut = [];
            exec("schtasks /Query /TN \"$taskName\" /XML 2>&1", $xmlOut, $xmlCode);
            if ($xmlCode === 0) {
                $xml = implode("\n", $xmlOut);
                if (preg_match('/PT(\d+)M/i', $xml, $im)) {
                    $status['intervalo'] = (int)$im[1];
                }
            }
        }
    }

    // Últimas linhas do log
    $logFile = __DIR__ . '/../storage/logs/cron_' . date('Y-m') . '.log';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $status['last_log_lines'] = array_slice(array_reverse($lines), 0, 10);
    }

    return $status;
}

/**
 * Detecta o caminho do PHP automaticamente
 */
function detectPhpPath() {
    // 1. Tentar deduzir a partir do diretório do PHP atual (funciona com Apache e CLI)
    $phpDir = defined('PHP_BINDIR') ? PHP_BINDIR : '';
    if ($phpDir) {
        $candidate = $phpDir . DIRECTORY_SEPARATOR . 'php.exe';
        if (file_exists($candidate)) return $candidate;
    }

    // 2. Caminhos conhecidos do XAMPP/WAMP
    $knownPaths = [
        'C:\\xampp\\php\\php.exe',
        'D:\\xampp\\php\\php.exe',
        'C:\\wamp64\\bin\\php\\php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION . '\\php.exe',
    ];
    foreach ($knownPaths as $p) {
        if (file_exists($p)) return $p;
    }

    // 3. PHP_BINARY somente se for realmente php.exe (não httpd.exe/apache)
    if (defined('PHP_BINARY') && PHP_BINARY && file_exists(PHP_BINARY)) {
        $basename = strtolower(basename(PHP_BINARY));
        if (strpos($basename, 'php') !== false && strpos($basename, 'httpd') === false) {
            return PHP_BINARY;
        }
    }

    // 4. Tentar via where
    $whereOutput = [];
    exec('where php.exe 2>NUL', $whereOutput);
    if (!empty($whereOutput[0]) && file_exists(trim($whereOutput[0]))) {
        return trim($whereOutput[0]);
    }

    // 5. Fallback
    return 'php';
}

/**
 * Instala a tarefa agendada no Windows
 */
function cronInstalar($intervaloMinutos = 5) {
    $taskName = 'HelpDesk_Cron';
    $phpPath = detectPhpPath();
    $cronPath = realpath(__DIR__ . '/../cron.php') ?: (__DIR__ . '/../cron.php');

    // Verificar se PHP existe
    if ($phpPath !== 'php' && !file_exists($phpPath)) {
        return ['success' => false, 'error' => "PHP não encontrado em: $phpPath"];
    }

    // Verificar se cron.php existe
    if (!file_exists($cronPath)) {
        return ['success' => false, 'error' => "Arquivo cron.php não encontrado em: $cronPath"];
    }

    // Remover tarefa anterior se existir
    exec("schtasks /Delete /TN \"$taskName\" /F 2>&1", $delOutput, $delCode);

    // Criar a tarefa agendada
    // /SC MINUTE /MO X = a cada X minutos
    // /F = forçar criação (sobrescrever se existir)
    // Tenta primeiro sem /RL HIGHEST (não requer admin), depois com se falhar
    $cmd = sprintf(
        'schtasks /Create /TN "%s" /TR "\"%s\" \"%s\"" /SC MINUTE /MO %d /F',
        $taskName,
        $phpPath,
        $cronPath,
        $intervaloMinutos
    );

    $output = [];
    $code = 0;
    exec($cmd . ' 2>&1', $output, $code);

    // Se falhou, tentar com /RL HIGHEST (precisa de admin)
    if ($code !== 0) {
        $output = [];
        $cmd .= ' /RL HIGHEST';
        exec($cmd . ' 2>&1', $output, $code);
    }

    if ($code === 0) {
        // Registrar na tabela configuracoes se existir
        try {
            $db = Database::getInstance();
            $exists = $db->fetch("SELECT id FROM configuracoes WHERE chave = 'cron_intervalo'");
            if ($exists) {
                $db->update('configuracoes', ['valor' => $intervaloMinutos], "chave = 'cron_intervalo'");
            } else {
                $db->insert('configuracoes', [
                    'chave' => 'cron_intervalo',
                    'valor' => $intervaloMinutos,
                    'descricao' => 'Intervalo do cron em minutos'
                ]);
            }
        } catch (\Exception $e) { /* tabela pode não ter essa coluna */ }

        return [
            'success' => true,
            'message' => "Cron instalado com sucesso! Executará a cada {$intervaloMinutos} minuto(s).",
            'detalhes' => [
                'task' => $taskName,
                'php' => $phpPath,
                'script' => $cronPath,
                'intervalo' => $intervaloMinutos,
                'output' => implode("\n", $output)
            ]
        ];
    }

    return [
        'success' => false,
        'error' => 'Falha ao criar tarefa agendada. ' . implode(' ', $output),
        'dica' => 'O servidor web (Apache) pode não ter permissão para criar tarefas. Tente executar o XAMPP como Administrador.',
        'comando_manual' => $cmd
    ];
}

/**
 * Remove a tarefa agendada do Windows
 */
function cronDesinstalar() {
    $taskName = 'HelpDesk_Cron';

    $output = [];
    $code = 0;
    exec("schtasks /Delete /TN \"$taskName\" /F 2>&1", $output, $code);

    if ($code === 0) {
        // Remover config
        try {
            $db = Database::getInstance();
            $db->delete('configuracoes', "chave = 'cron_intervalo'");
        } catch (\Exception $e) { /* ignore */ }

        return [
            'success' => true,
            'message' => 'Cron desinstalado com sucesso!'
        ];
    }

    // Código 1 pode significar que a tarefa não existe
    $outputStr = implode(' ', $output);
    if (stripos($outputStr, 'não foi localiz') !== false || 
        stripos($outputStr, 'does not exist') !== false ||
        stripos($outputStr, 'não exist') !== false) {
        return [
            'success' => true,
            'message' => 'A tarefa já não estava instalada.'
        ];
    }

    return [
        'success' => false,
        'error' => 'Falha ao remover tarefa. ' . $outputStr
    ];
}

/**
 * Testa a execução do cron manualmente via CLI
 */
function cronTestar() {
    $phpPath = detectPhpPath();
    $cronPath = realpath(__DIR__ . '/../cron.php') ?: (__DIR__ . '/../cron.php');

    $output = [];
    $code = 0;
    $cmd = sprintf('"%s" "%s" 2>&1', $phpPath, $cronPath);
    exec($cmd, $output, $code);

    return [
        'success' => $code === 0,
        'message' => $code === 0 ? 'Cron executado com sucesso!' : 'Erro na execução do cron.',
        'output' => implode("\n", $output),
        'exit_code' => $code
    ];
}
