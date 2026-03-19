<?php
/**
 * API: Remote Desktop
 * Gerencia sessões de acesso remoto, histórico e configurações
 */
session_start();
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

require_once __DIR__ . '/../app/models/RemoteDesktop.php';
$model = new RemoteDesktop();

$method = $_SERVER['REQUEST_METHOD'];
$user = currentUser();

// ==========================================
//  POST
// ==========================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;
    $action = $data['action'] ?? '';

    switch ($action) {

        // ===== Iniciar servidor WebSocket =====
        case 'start_server':
            requireRole(['admin', 'tecnico']);
            try {
                if ($model->isServerRunning()) {
                    jsonResponse(['success' => true, 'message' => 'Servidor já está rodando', 'running' => true]);
                }

                $phpPath = detectPhpPathForRemote();
                $serverScript = realpath(__DIR__ . '/../remote/server.php');
                $port = $model->getServerPort();
                $logFile = realpath(__DIR__ . '/../remote') . DIRECTORY_SEPARATOR . 'server.log';
                $pidFile = realpath(__DIR__ . '/../remote') . DIRECTORY_SEPARATOR . 'server.pid';
                $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

                if (!$serverScript) {
                    jsonResponse(['error' => 'Script do servidor não encontrado', 'debug' => ['dir' => __DIR__]], 404);
                }

                if (!$phpPath || (!file_exists($phpPath) && $phpPath !== 'php')) {
                    jsonResponse(['error' => 'PHP CLI não encontrado', 'debug' => ['php_path' => $phpPath, 'PHP_BINDIR' => PHP_BINDIR, 'PHP_OS' => PHP_OS]], 500);
                }

                // Verificar se funções de execução estão disponíveis
                $disabledFn = array_map('trim', explode(',', ini_get('disable_functions')));
                $needFuncs = $isWindows ? ['popen', 'pclose'] : ['shell_exec', 'exec'];
                $blocked = array_intersect($needFuncs, $disabledFn);
                if (!empty($blocked)) {
                    jsonResponse([
                        'error' => 'Funções PHP bloqueadas no servidor: ' . implode(', ', $blocked),
                        'message' => 'Peça ao administrador do servidor para desbloquear ' . implode(', ', $blocked) . ' no php.ini',
                        'debug' => ['disabled_functions' => implode(', ', $disabledFn)]
                    ], 500);
                }

                // Limpar PID antigo
                @unlink($pidFile);

                if ($isWindows) {
                    $cmd = "start /B \"\" \"$phpPath\" \"$serverScript\" $port";
                    pclose(popen($cmd, 'r'));
                } else {
                    // Linux / macOS — nohup para sobreviver ao worker do Apache
                    $cmd = 'nohup ' . escapeshellarg($phpPath) . ' ' . escapeshellarg($serverScript) . ' ' . (int)$port
                         . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
                    $pid = trim(shell_exec($cmd) ?? '');
                    if ($pid && is_numeric($pid)) {
                        @file_put_contents($pidFile, $pid);
                    }
                }

                // Aguardar até 8 segundos
                $running = false;
                for ($i = 0; $i < 16; $i++) {
                    usleep(500000);
                    if ($model->isServerRunning()) {
                        $running = true;
                        break;
                    }
                }

                // Se não iniciou, tentar capturar erro do log
                $errorHint = '';
                if (!$running && file_exists($logFile)) {
                    $logContent = @file_get_contents($logFile);
                    if ($logContent) {
                        $lines = array_filter(explode("\n", $logContent));
                        $errorHint = implode(' | ', array_slice($lines, -3));
                    }
                }

                $response = [
                    'success' => $running,
                    'message' => $running ? 'Servidor iniciado com sucesso' : 'Falha ao iniciar o servidor',
                    'running' => $running,
                    'port' => $port,
                ];
                if (!$running) {
                    $response['debug'] = [
                        'php_path' => $phpPath,
                        'php_exists' => file_exists($phpPath),
                        'script' => $serverScript,
                        'os' => PHP_OS,
                        'cmd' => $cmd ?? '',
                        'log_hint' => $errorHint,
                    ];
                }
                jsonResponse($response);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
            }
            break;

        // ===== Parar servidor =====
        case 'stop_server':
            requireRole(['admin']);
            try {
                $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
                $pidFile = realpath(__DIR__ . '/../remote') . DIRECTORY_SEPARATOR . 'server.pid';

                if ($isWindows) {
                    exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV 2>nul', $lines);
                    foreach ($lines as $line) {
                        if (stripos($line, 'php.exe') !== false) {
                            preg_match('/"php\.exe","(\d+)"/', $line, $m);
                            if (!empty($m[1])) {
                                $pid = $m[1];
                                $cmdOut = [];
                                exec("wmic process where ProcessId=$pid get CommandLine 2>nul", $cmdOut);
                                $cmdStr = implode(' ', $cmdOut);
                                if (stripos($cmdStr, 'server.php') !== false) {
                                    exec("taskkill /F /PID $pid 2>nul");
                                }
                            }
                        }
                    }
                } else {
                    // Linux: tentar PID file primeiro
                    $killed = false;
                    if (file_exists($pidFile)) {
                        $pid = trim(file_get_contents($pidFile));
                        if ($pid && is_numeric($pid)) {
                            exec("kill -9 $pid 2>/dev/null", $out, $ret);
                            $killed = ($ret === 0);
                        }
                        @unlink($pidFile);
                    }
                    // Fallback: pkill
                    if (!$killed) {
                        exec("pkill -f 'remote/server.php' 2>/dev/null");
                    }
                }

                @unlink($pidFile);
                usleep(1000000);
                $running = $model->isServerRunning();
                jsonResponse([
                    'success' => !$running,
                    'message' => !$running ? 'Servidor parado' : 'Não foi possível parar o servidor',
                    'running' => $running,
                ]);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;

        // ===== Salvar configurações =====
        case 'save_config':
            requireRole(['admin']);
            try {
                $allowed = ['server_host', 'server_port', 'max_fps', 'default_quality', 'default_scale', 'session_timeout', 'require_approval'];
                foreach ($allowed as $key) {
                    if (isset($data[$key])) {
                        $model->setConfig($key, $data[$key]);
                    }
                }
                jsonResponse(['success' => true, 'message' => 'Configurações salvas']);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;

        // ===== Limpar sessões expiradas =====
        case 'cleanup':
            requireRole(['admin', 'tecnico']);
            try {
                $db = Database::getInstance();
                $db->query("UPDATE remote_sessoes SET status = 'desconectado', desconectado_em = NOW() WHERE status IN ('online','conectado') AND atualizado_em < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
                $db->query("UPDATE remote_historico SET fim = NOW(), duracao_segundos = TIMESTAMPDIFF(SECOND, inicio, NOW()) WHERE fim IS NULL AND inicio < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
                jsonResponse(['success' => true, 'message' => 'Sessões expiradas removidas']);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;

        // ===== Configurar proxy WSS no Apache =====
        case 'configure_proxy':
            requireRole(['admin']);
            try {
                $port = $model->getServerPort();
                $result = configureApacheWsProxy($port);
                jsonResponse($result);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;

        // ===== Verificar status do proxy WSS =====
        case 'check_proxy':
            requireRole(['admin']);
            try {
                $port = $model->getServerPort();
                $result = checkApacheWsProxy($port);
                jsonResponse($result);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;

        default:
            jsonResponse(['error' => 'Ação desconhecida: ' . $action], 400);
    }
    exit;
}

// ==========================================
//  GET
// ==========================================
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'status';

    switch ($action) {

        // ===== Status geral =====
        case 'status':
            $stats = $model->estatisticas();
            $stats['server_running'] = $model->isServerRunning();
            $stats['server_port'] = $model->getServerPort();
            jsonResponse($stats);
            break;

        // ===== Listar sessões online =====
        case 'sessoes':
            $status = $_GET['status'] ?? null;
            $sessoes = $model->listarSessoes($status);
            jsonResponse(['sessoes' => $sessoes]);
            break;

        // ===== Sessão específica por código =====
        case 'sessao':
            $codigo = $_GET['codigo'] ?? '';
            $sessao = $model->buscarSessaoPorCodigo($codigo);
            if (!$sessao) {
                jsonResponse(['error' => 'Sessão não encontrada'], 404);
            }
            jsonResponse(['sessao' => $sessao]);
            break;

        // ===== Verificar se há client online para um chamado =====
        case 'chamado_client':
            $chamadoId = (int)($_GET['chamado_id'] ?? 0);
            if (!$chamadoId) jsonResponse(['error' => 'chamado_id obrigatório'], 400);
            $sessao = $model->buscarSessaoParaChamado($chamadoId);
            jsonResponse(['sessao' => $sessao, 'available' => $sessao !== null]);
            break;

        // ===== Histórico =====
        case 'historico':
            $filtros = [
                'tecnico_id' => $_GET['tecnico_id'] ?? null,
                'hostname' => $_GET['hostname'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
                'chamado_id' => $_GET['chamado_id'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0,
            ];
            $historico = $model->listarHistorico($filtros);
            $total = $model->contarHistorico($filtros);
            jsonResponse(['historico' => $historico, 'total' => $total]);
            break;

        // ===== Configurações =====
        case 'config':
            $config = $model->getAllConfig();
            jsonResponse(['config' => $config]);
            break;

        // ===== Info do servidor =====
        case 'server_info':
            jsonResponse([
                'running' => $model->isServerRunning(),
                'port' => $model->getServerPort(),
                'host' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost',
            ]);
            break;

        // ===== Download do instalador =====
        case 'download_client':
            requireRole(['admin', 'tecnico']);
            try {
                // 1. Resolver IP/host do servidor
                $serverHost = $model->getConfig('server_host');
                if (!$serverHost) {
                    $serverHost = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
                    if (in_array($serverHost, ['::1', '127.0.0.1', '0.0.0.0'])) {
                        $serverHost = gethostbyname(gethostname());
                    }
                }
                $serverPort = $model->getServerPort();

                // 2. Gerar config.json com IP pré-configurado (v3.0: novos parâmetros)
                $configJson = json_encode([
                    'server_url' => "ws://{$serverHost}:{$serverPort}",
                    'quality' => (int)$model->getConfig('default_quality', 40),
                    'fps' => (int)$model->getConfig('max_fps', 30),
                    'scale' => (float)$model->getConfig('default_scale', 0.5),
                    'auto_start' => true,
                    'require_approval' => (bool)((int)$model->getConfig('require_approval', 0)),
                    'adaptive_quality' => true,
                    'min_quality' => 15,
                    'max_quality' => 70,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                $clientDir = realpath(__DIR__ . '/../remote/client');
                if (!$clientDir) {
                    jsonResponse(['error' => 'Diretório do client não encontrado'], 500);
                }

                $ds = DIRECTORY_SEPARATOR;
                $exePath   = $clientDir . $ds . 'dist' . $ds . 'HelpDesk_Remote.exe';
                $issPath   = $clientDir . $ds . 'installer.iss';
                $outputDir = $clientDir . $ds . 'output';
                $outputExe = $outputDir . $ds . 'HelpDesk_Remote_Setup.exe';
                $configFile = $clientDir . $ds . 'config.json';

                // 3. Gravar config.json no diretório do client
                file_put_contents($configFile, $configJson);

                // 4. Tentar compilar instalador com Inno Setup (ISCC)
                $iscc = findISCCPath();
                if ($iscc && file_exists($exePath) && file_exists($issPath)) {
                    @mkdir($outputDir, 0777, true);
                    @unlink($outputExe);

                    $cmd = '"' . $iscc . '" "' . $issPath . '" 2>&1';
                    $compileOutput = [];
                    exec($cmd, $compileOutput, $exitCode);

                    if ($exitCode === 0 && file_exists($outputExe)) {
                        // Servir instalador compilado (EXE único)
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="HelpDesk_Remote_Setup.exe"');
                        header('Content-Length: ' . filesize($outputExe));
                        header('Cache-Control: no-cache, no-store, must-revalidate');
                        readfile($outputExe);
                        exit;
                    }
                    // Log do erro de compilação
                    error_log('ISCC compile failed (code ' . $exitCode . '): ' . implode("\n", $compileOutput));
                }

                // 5. Fallback: instalador pré-compilado existe (de build.bat manual)
                //    Servir direto como .exe — o config.json já está embutido no installer
                if (file_exists($outputExe)) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="HelpDesk_Remote_Setup.exe"');
                    header('Content-Length: ' . filesize($outputExe));
                    header('Cache-Control: no-cache, no-store, must-revalidate');
                    readfile($outputExe);
                    exit;
                }

                // 6. Fallback: EXE avulso (sem installer) — servir direto com config
                if (file_exists($exePath)) {
                    $zipPath = tempnam(sys_get_temp_dir(), 'hdr_');
                    $zip = new ZipArchive();
                    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                        $zip->addFile($exePath, 'HelpDesk_Remote.exe');
                        $zip->addFromString('config.json', $configJson);
                        $readme  = "HelpDesk Remote\r\n";
                        $readme .= "================\r\n\r\n";
                        $readme .= "Servidor: ws://{$serverHost}:{$serverPort}\r\n\r\n";
                        $readme .= "INSTRUCOES:\r\n";
                        $readme .= "1. Extraia os dois arquivos na mesma pasta\r\n";
                        $readme .= "2. Execute HelpDesk_Remote.exe\r\n";
                        $readme .= "3. O programa sera instalado automaticamente\r\n";
                        $zip->addFromString('LEIA-ME.txt', $readme);
                        $zip->close();

                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="HelpDesk_Remote.zip"');
                        header('Content-Length: ' . filesize($zipPath));
                        header('Cache-Control: no-cache');
                        readfile($zipPath);
                        @unlink($zipPath);
                        exit;
                    }
                }

                // Nenhum executável encontrado
                jsonResponse([
                    'error' => 'Nenhum executável compilado encontrado. Execute build.bat na pasta remote/client primeiro.',
                    'instrucoes' => [
                        '1. Instale Python 3.8+ e Inno Setup 6',
                        '2. Abra o terminal na pasta remote/client',
                        '3. Execute: build.bat',
                        '4. Tente baixar novamente',
                    ]
                ], 404);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Erro ao gerar download: ' . $e->getMessage()], 500);
            }
            break;

        default:
            jsonResponse(['error' => 'Ação desconhecida: ' . $action], 400);
    }
    exit;
}

jsonResponse(['error' => 'Método não suportado'], 405);

// ==========================================
//  Helpers
// ==========================================

function detectPhpPathForRemote(): string {
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $phpDir = PHP_BINDIR;
    $ds = DIRECTORY_SEPARATOR;

    // PHP_BINARY é o mais confiável (PHP 5.4+)
    if (defined('PHP_BINARY') && PHP_BINARY && !str_contains(strtolower(PHP_BINARY), 'httpd') && !str_contains(strtolower(PHP_BINARY), 'apache')) {
        if (file_exists(PHP_BINARY)) {
            return PHP_BINARY;
        }
    }

    if ($isWindows) {
        $candidates = [
            $phpDir . $ds . 'php.exe',
            'C:\\xampp\\php\\php.exe',
            'C:\\php\\php.exe',
            'C:\\laragon\\bin\\php\\php.exe',
        ];
    } else {
        $candidates = [
            $phpDir . $ds . 'php',
            '/usr/bin/php',
            '/usr/local/bin/php',
            '/usr/bin/php8.0',
            '/usr/bin/php8.1',
            '/usr/bin/php8.2',
            '/usr/bin/php8.3',
        ];
    }

    foreach ($candidates as $path) {
        if (file_exists($path)) return $path;
    }

    // Fallback: which / where
    $output = [];
    if ($isWindows) {
        exec('where php.exe 2>nul', $output);
    } else {
        exec('which php 2>/dev/null', $output);
    }
    if (!empty($output[0]) && file_exists(trim($output[0]))) {
        return trim($output[0]);
    }

    return 'php';
}

/**
 * Localizar o compilador Inno Setup (ISCC.exe)
 */
function findISCCPath(): ?string {
    $paths = [
        'C:\\Program Files (x86)\\Inno Setup 6\\ISCC.exe',
        'C:\\Program Files\\Inno Setup 6\\ISCC.exe',
        'C:\\Program Files (x86)\\Inno Setup 5\\ISCC.exe',
        'C:\\Program Files\\Inno Setup 5\\ISCC.exe',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) return $p;
    }
    // Verificar PATH
    $output = [];
    exec('where ISCC.exe 2>nul', $output);
    if (!empty($output[0]) && file_exists(trim($output[0]))) {
        return trim($output[0]);
    }
    return null;
}

/**
 * Verificar status do proxy WebSocket no Apache
 */
function checkApacheWsProxy(int $port): array {
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $status = [
        'configured' => false,
        'modules_loaded' => false,
        'proxy_http' => false,
        'proxy_wstunnel' => false,
        'rewrite' => false,
        'proxy_rules' => false,
        'ssl_active' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'os' => PHP_OS,
    ];

    if ($isWindows) {
        $confDir = getApacheConfDir();
        if (!$confDir) {
            $status['error'] = 'Diretório de configuração do Apache não encontrado';
            return $status;
        }
        $httpConf = $confDir . 'httpd.conf';
        $sslConf  = $confDir . 'extra' . DIRECTORY_SEPARATOR . 'httpd-ssl.conf';

        if (file_exists($httpConf)) {
            $content = file_get_contents($httpConf);
            $status['proxy_http'] = (bool)preg_match('/^\s*LoadModule\s+proxy_http_module/m', $content);
            $status['proxy_wstunnel'] = (bool)preg_match('/^\s*LoadModule\s+proxy_wstunnel_module/m', $content);
            $status['rewrite'] = (bool)preg_match('/^\s*LoadModule\s+rewrite_module/m', $content);
            $status['modules_loaded'] = $status['proxy_http'] && $status['proxy_wstunnel'];
        }
        // Verificar regras de proxy em ambos os arquivos
        $rulesInHttp = false;
        $rulesInSsl = false;
        if (file_exists($httpConf)) {
            $rulesInHttp = stripos(file_get_contents($httpConf), 'ws-remote') !== false;
        }
        if (file_exists($sslConf)) {
            $rulesInSsl = stripos(file_get_contents($sslConf), 'ws-remote') !== false;
        }
        $status['proxy_rules'] = $rulesInHttp || $rulesInSsl;
        $status['proxy_rules_http'] = $rulesInHttp;
        $status['proxy_rules_ssl'] = $rulesInSsl;
    } else {
        // Linux — verificar com apache2ctl ou apachectl
        $modules = shell_exec('apache2ctl -M 2>/dev/null') ?: shell_exec('apachectl -M 2>/dev/null') ?: '';
        $status['proxy_http'] = stripos($modules, 'proxy_http') !== false;
        $status['proxy_wstunnel'] = stripos($modules, 'proxy_wstunnel') !== false;
        $status['rewrite'] = stripos($modules, 'rewrite') !== false;
        $status['modules_loaded'] = $status['proxy_http'] && $status['proxy_wstunnel'];

        // Verificar se proxy rules existem
        $search = shell_exec("grep -rl 'ws-remote' /etc/apache2/ /etc/httpd/ 2>/dev/null") ?: '';
        $status['proxy_rules'] = !empty(trim($search));
    }

    $status['configured'] = $status['modules_loaded'] && $status['proxy_rules'];
    return $status;
}

/**
 * Configurar proxy WebSocket no Apache automaticamente
 */
function configureApacheWsProxy(int $port): array {
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $steps = [];
    $errors = [];

    $proxyBlock = "\n# === HelpDesk Remote — WebSocket Proxy (auto-configurado) ===\n"
        . "ProxyPreserveHost On\n"
        . "ProxyPass /ws-remote/ ws://127.0.0.1:{$port}/\n"
        . "ProxyPassReverse /ws-remote/ ws://127.0.0.1:{$port}/\n"
        . "RewriteEngine On\n"
        . "RewriteCond %{HTTP:Upgrade} websocket [NC]\n"
        . "RewriteCond %{HTTP:Connection} upgrade [NC]\n"
        . "RewriteRule ^/ws-remote/(.*) ws://127.0.0.1:{$port}/\$1 [P,L]\n"
        . "# === Fim HelpDesk Remote ===\n";

    if ($isWindows) {
        // ========== WINDOWS / XAMPP ==========
        $confDir = getApacheConfDir();
        if (!$confDir) {
            return ['success' => false, 'error' => 'Não foi possível encontrar o diretório de configuração do Apache'];
        }

        $httpConf = $confDir . 'httpd.conf';
        $sslConf  = $confDir . 'extra' . DIRECTORY_SEPARATOR . 'httpd-ssl.conf';

        // 1. Habilitar módulos no httpd.conf
        if (file_exists($httpConf) && is_writable($httpConf)) {
            $content = file_get_contents($httpConf);
            $modified = false;

            $modules = [
                'proxy_http_module'      => 'LoadModule proxy_http_module modules/mod_proxy_http.so',
                'proxy_wstunnel_module'  => 'LoadModule proxy_wstunnel_module modules/mod_proxy_wstunnel.so',
            ];

            foreach ($modules as $modName => $loadLine) {
                // Já ativo (sem #)
                if (preg_match('/^\s*LoadModule\s+' . preg_quote($modName) . '/m', $content)) {
                    $steps[] = "Módulo $modName já está ativo";
                    continue;
                }
                // Comentado — descomentar
                $pattern = '/^\s*#\s*(LoadModule\s+' . preg_quote($modName) . '\s+.+)$/m';
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, '$1', $content);
                    $modified = true;
                    $steps[] = "Módulo $modName descomentado (ativado)";
                } else {
                    // Adicionar após proxy_module
                    $pos = strpos($content, 'LoadModule proxy_module');
                    if ($pos !== false) {
                        $endLine = strpos($content, "\n", $pos);
                        $content = substr($content, 0, $endLine + 1) . $loadLine . "\n" . substr($content, $endLine + 1);
                        $modified = true;
                        $steps[] = "Módulo $modName adicionado";
                    }
                }
            }

            if ($modified) {
                // Backup
                @copy($httpConf, $httpConf . '.bak.' . date('YmdHis'));
                file_put_contents($httpConf, $content);
                $steps[] = "httpd.conf atualizado (backup criado)";
            }
        } else {
            $errors[] = "httpd.conf não encontrado ou sem permissão de escrita: $httpConf";
        }

        // 2. Adicionar ProxyPass no httpd.conf principal (para HTTP)
        if (file_exists($httpConf) && is_writable($httpConf)) {
            $content = file_get_contents($httpConf);
            if (stripos($content, 'ws-remote') !== false) {
                $steps[] = "Regras proxy já configuradas no httpd.conf";
            } else {
                @copy($httpConf, $httpConf . '.bak.' . date('YmdHis'));
                $content .= "\n" . $proxyBlock;
                file_put_contents($httpConf, $content);
                $steps[] = "Regras ProxyPass adicionadas ao httpd.conf (backup criado)";
            }
        }

        // 3. Adicionar ProxyPass no httpd-ssl.conf (para HTTPS)
        if (file_exists($sslConf) && is_writable($sslConf)) {
            $content = file_get_contents($sslConf);
            if (stripos($content, 'ws-remote') !== false) {
                $steps[] = "Regras proxy já configuradas no SSL VirtualHost";
            } else {
                $pos = strrpos($content, '</VirtualHost>');
                if ($pos !== false) {
                    @copy($sslConf, $sslConf . '.bak.' . date('YmdHis'));
                    $content = substr($content, 0, $pos) . $proxyBlock . "\n" . substr($content, $pos);
                    file_put_contents($sslConf, $content);
                    $steps[] = "Regras ProxyPass adicionadas ao SSL VirtualHost (backup criado)";
                }
            }
        }

        // 4. Testar configuração do Apache
        $apacheDir = dirname(dirname($confDir));
        $apacheBin = $apacheDir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'httpd.exe';
        if (file_exists($apacheBin)) {
            $testOutput = [];
            exec("\"$apacheBin\" -t 2>&1", $testOutput, $testCode);
            $testResult = implode("\n", $testOutput);
            if ($testCode === 0 || stripos($testResult, 'Syntax OK') !== false) {
                $steps[] = "Teste de configuração do Apache: OK";

                // 4. Reiniciar Apache
                $restartResult = restartApache();
                if ($restartResult) {
                    $steps[] = "Apache reiniciado com sucesso";
                } else {
                    $errors[] = "Configure OK, mas reinicie o Apache manualmente (XAMPP Control Panel)";
                }
            } else {
                $errors[] = "Erro na configuração do Apache: $testResult";
            }
        } else {
            $steps[] = "Reinicie o Apache manualmente pelo XAMPP Control Panel";
        }

    } else {
        // ========== LINUX ==========
        $configFile = '/etc/apache2/conf-available/helpdesk-ws-proxy.conf';
        $confDir2 = '/etc/httpd/conf.d/';
        $useApache2 = is_dir('/etc/apache2/');

        if ($useApache2) {
            // Debian/Ubuntu
            $target = $configFile;
        } elseif (is_dir($confDir2)) {
            // CentOS/RHEL
            $target = $confDir2 . 'helpdesk-ws-proxy.conf';
        } else {
            return ['success' => false, 'error' => 'Diretório de configuração do Apache não encontrado em /etc/apache2/ ou /etc/httpd/'];
        }

        // 1. Habilitar módulos
        if ($useApache2) {
            exec('a2enmod proxy 2>&1', $out1, $r1);
            exec('a2enmod proxy_http 2>&1', $out2, $r2);
            exec('a2enmod proxy_wstunnel 2>&1', $out3, $r3);
            exec('a2enmod rewrite 2>&1', $out4, $r4);
            $steps[] = "Módulos habilitados: proxy, proxy_http, proxy_wstunnel, rewrite";
        } else {
            $steps[] = "CentOS/RHEL: módulos carregados via conf.modules.d";
        }

        // 2. Gravar arquivo de configuração
        $written = @file_put_contents($target, $proxyBlock);
        if ($written) {
            $steps[] = "Arquivo criado: $target";
        } else {
            // Tentar com shell
            $escaped = escapeshellarg($proxyBlock);
            exec("echo $escaped | sudo tee $target 2>&1", $shellOut, $shellRet);
            if ($shellRet === 0) {
                $steps[] = "Arquivo criado com sudo: $target";
            } else {
                $errors[] = "Sem permissão para escrever em $target. Execute manualmente:\nsudo tee $target <<'EOF'\n{$proxyBlock}\nEOF";
            }
        }

        // 3. Habilitar config (Debian/Ubuntu)
        if ($useApache2) {
            exec('a2enconf helpdesk-ws-proxy 2>&1', $enOut, $enRet);
            if ($enRet === 0) {
                $steps[] = "Configuração habilitada: a2enconf helpdesk-ws-proxy";
            }
        }

        // 4. Testar e reiniciar
        $testOut = [];
        exec('apache2ctl configtest 2>&1 || apachectl configtest 2>&1', $testOut, $testRet);
        $testStr = implode(' ', $testOut);
        if ($testRet === 0 || stripos($testStr, 'Syntax OK') !== false) {
            $steps[] = "Teste de configuração: OK";
            exec('systemctl reload apache2 2>&1 || systemctl reload httpd 2>&1 || apachectl graceful 2>&1', $rlOut, $rlRet);
            if ($rlRet === 0) {
                $steps[] = "Apache recarregado com sucesso";
            } else {
                $errors[] = "Execute manualmente: sudo systemctl reload apache2";
            }
        } else {
            $errors[] = "Erro na configuração: $testStr";
        }
    }

    $success = empty($errors);
    return [
        'success' => $success,
        'message' => $success
            ? 'Proxy WSS configurado com sucesso! O WebSocket agora funciona via HTTPS.'
            : 'Configuração parcial — verifique os erros',
        'steps' => $steps,
        'errors' => $errors,
        'restart_needed' => !$success,
    ];
}

/**
 * Encontrar diretório de configuração do Apache (Windows XAMPP)
 */
function getApacheConfDir(): ?string {
    $candidates = [
        'C:\\xampp\\apache\\conf\\',
        'D:\\xampp\\apache\\conf\\',
        'C:\\wamp64\\bin\\apache\\apache2.4.51\\conf\\',
        dirname(PHP_BINDIR) . '\\apache\\conf\\',
    ];

    // Tentar descobrir via ServerRoot do Apache
    if (!empty($_SERVER['SERVER_SOFTWARE'])) {
        $output = [];
        exec('httpd.exe -V 2>nul', $output);
        foreach ($output as $line) {
            if (preg_match('/HTTPD_ROOT="([^"]+)"/', $line, $m)) {
                $candidates[] = $m[1] . '\\conf\\';
            }
        }
    }

    foreach ($candidates as $dir) {
        if (is_dir($dir) && file_exists($dir . 'httpd.conf')) {
            return $dir;
        }
    }
    return null;
}

/**
 * Reiniciar Apache (Windows)
 */
function restartApache(): bool {
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    if ($isWindows) {
        // XAMPP — usar Apache Service ou httpd.exe
        $service = 'Apache2.4';
        exec("net stop $service 2>nul && net start $service 2>nul", $out, $ret);
        if ($ret === 0) return true;

        // Fallback: XAMPP usa Apache como processo, não serviço
        $apacheBin = 'C:\\xampp\\apache\\bin\\httpd.exe';
        if (file_exists($apacheBin)) {
            exec("\"$apacheBin\" -k restart 2>nul", $out2, $ret2);
            return $ret2 === 0;
        }
        return false;
    } else {
        exec('systemctl reload apache2 2>/dev/null || systemctl reload httpd 2>/dev/null', $out, $ret);
        return $ret === 0;
    }
}
