<?php
/**
 * Model: Deploy
 * Gerencia publicação do sistema em servidores de produção
 * Suporta: FTP, SFTP, cópia local
 */

require_once __DIR__ . '/Database.php';

class Deploy
{
    private $db;
    private $basePath;
    private $config = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->basePath = realpath(__DIR__ . '/../../');
    }

    // =========================================================
    //  Configuração
    // =========================================================

    public function getConfig(): array
    {
        if (empty($this->config)) {
            $rows = $this->db->fetchAll("SELECT chave, valor, descricao FROM deploy_config ORDER BY chave");
            foreach ($rows as $r) {
                $this->config[$r['chave']] = [
                    'valor' => $r['valor'],
                    'descricao' => $r['descricao'],
                ];
            }
        }
        return $this->config;
    }

    public function getConfigValue(string $key, string $default = ''): string
    {
        $cfg = $this->getConfig();
        return $cfg[$key]['valor'] ?? $default;
    }

    public function saveConfig(array $data): bool
    {
        foreach ($data as $key => $value) {
            // Criptografar senhas
            if (str_contains($key, 'pass') && $value !== '' && $value !== '********') {
                $value = $this->encrypt($value);
            }
            $this->db->query(
                "INSERT INTO deploy_config (chave, valor) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor)",
                [$key, $value]
            );
        }
        $this->config = []; // Reset cache
        return true;
    }

    // =========================================================
    //  Histórico
    // =========================================================

    public function getHistorico(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT h.*, u.nome AS usuario_nome 
             FROM deploy_historico h 
             LEFT JOIN usuarios u ON u.id = h.usuario_id 
             ORDER BY h.iniciado_em DESC LIMIT ?",
            [$limit]
        );
    }

    public function getDeployById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM deploy_historico WHERE id = ?", [$id]);
        return $row ?: null;
    }

    // =========================================================
    //  Preview (listar arquivos que seriam enviados)
    // =========================================================

    public function preview(): array
    {
        $excludes = $this->getExcludePatterns();
        $files = [];
        $totalSize = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;

            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($this->basePath) + 1));

            if ($this->isExcluded($relativePath, $excludes)) continue;

            $size = $file->getSize();
            $totalSize += $size;
            $files[] = [
                'path' => $relativePath,
                'size' => $size,
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'type' => $this->getFileCategory($relativePath),
            ];
        }

        // Ordenar por caminho
        usort($files, fn($a, $b) => strcmp($a['path'], $b['path']));

        return [
            'files' => $files,
            'total_files' => count($files),
            'total_size' => $totalSize,
            'excludes' => $excludes,
        ];
    }

    // =========================================================
    //  Deploy completo
    // =========================================================

    public function executeDeploy(int $userId, string $tipo = 'full', array $selectedFiles = []): array
    {
        $method = $this->getConfigValue('deploy_method', 'ftp');
        $version = trim(@file_get_contents($this->basePath . '/VERSION') ?: '0.0.0');

        // Criar registro no histórico
        $deployId = $this->db->insert('deploy_historico', [
            'usuario_id' => $userId,
            'tipo' => $tipo,
            'status' => 'iniciado',
            'versao_origem' => $version,
            'versao_destino' => $version,
            'iniciado_em' => date('Y-m-d H:i:s'),
        ]);

        $log = [];
        $log[] = "[" . date('H:i:s') . "] Deploy iniciado (método: $method, tipo: $tipo)";
        $log[] = "[" . date('H:i:s') . "] Versão: $version";

        try {
            // Obter lista de arquivos
            if ($tipo === 'full' || empty($selectedFiles)) {
                $preview = $this->preview();
                $files = $preview['files'];
            } else {
                // Parcial — só os arquivos selecionados
                $allPreview = $this->preview();
                $files = array_filter($allPreview['files'], fn($f) => in_array($f['path'], $selectedFiles));
                $files = array_values($files);
            }

            $totalFiles = count($files);
            $totalSize = array_sum(array_column($files, 'size'));

            $this->db->update('deploy_historico', [
                'status' => 'em_progresso',
                'arquivos_total' => $totalFiles,
                'bytes_total' => $totalSize,
            ], 'id = ?', [$deployId]);

            $log[] = "[" . date('H:i:s') . "] Arquivos a enviar: $totalFiles (" . $this->formatBytes($totalSize) . ")";

            // Executar deploy pelo método configurado
            $result = match ($method) {
                'ftp' => $this->deployViaFTP($files, $log, $deployId),
                'sftp' => $this->deployViaSFTP($files, $log, $deployId),
                'local_copy' => $this->deployViaLocalCopy($files, $log, $deployId),
                default => throw new Exception("Método de deploy não suportado: $method"),
            };

            // Aplicar overrides de config na produção
            $configOverrides = $this->getConfigOverrides();
            if (!empty($configOverrides)) {
                $log[] = "[" . date('H:i:s') . "] Aplicando configurações de produção...";
                $configResult = $this->applyConfigOverrides($method, $configOverrides, $log);
                if ($configResult) {
                    $log[] = "[" . date('H:i:s') . "] ✅ Configurações de produção aplicadas";
                }
            }

            // Finalizar
            $duracao = time() - strtotime($this->db->fetch("SELECT iniciado_em FROM deploy_historico WHERE id = ?", [$deployId])['iniciado_em']);

            $this->db->update('deploy_historico', [
                'status' => 'concluido',
                'arquivos_enviados' => $result['sent'],
                'arquivos_erro' => $result['errors'],
                'duracao_segundos' => $duracao,
                'log_resumo' => "✅ Deploy concluído: {$result['sent']}/{$totalFiles} arquivos enviados",
                'log_detalhado' => implode("\n", $log),
                'finalizado_em' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$deployId]);

            $log[] = "[" . date('H:i:s') . "] ✅ Deploy concluído! {$result['sent']} enviados, {$result['errors']} erros ({$duracao}s)";

            return [
                'success' => true,
                'deploy_id' => $deployId,
                'files_sent' => $result['sent'],
                'files_error' => $result['errors'],
                'files_total' => $totalFiles,
                'duration' => $duracao,
                'log' => $log,
            ];

        } catch (Exception $e) {
            $log[] = "[" . date('H:i:s') . "] ❌ ERRO: " . $e->getMessage();

            $this->db->update('deploy_historico', [
                'status' => 'erro',
                'log_resumo' => "❌ Erro: " . $e->getMessage(),
                'log_detalhado' => implode("\n", $log),
                'finalizado_em' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$deployId]);

            return [
                'success' => false,
                'deploy_id' => $deployId,
                'error' => $e->getMessage(),
                'log' => $log,
            ];
        }
    }

    // =========================================================
    //  Deploy via FTP
    // =========================================================

    private function deployViaFTP(array $files, array &$log, int $deployId): array
    {
        $host = $this->getConfigValue('prod_host');
        $port = (int)$this->getConfigValue('prod_port', '21');
        $user = $this->getConfigValue('prod_user');
        $pass = $this->decrypt($this->getConfigValue('prod_pass'));
        $remotePath = rtrim($this->getConfigValue('prod_path', '/helpdesk'), '/');

        if (!$host || !$user) {
            throw new Exception("Configuração FTP incompleta (host/user)");
        }

        $log[] = "[" . date('H:i:s') . "] Conectando FTP: $user@$host:$port...";

        // Conectar
        $ftp = @ftp_connect($host, $port, 30);
        if (!$ftp) {
            throw new Exception("Não foi possível conectar ao FTP: $host:$port");
        }

        if (!@ftp_login($ftp, $user, $pass)) {
            ftp_close($ftp);
            throw new Exception("Falha no login FTP (usuário: $user)");
        }

        ftp_pasv($ftp, true);
        $log[] = "[" . date('H:i:s') . "] ✅ Conectado ao FTP (modo passivo)";

        $sent = 0;
        $errors = 0;
        $createdDirs = [];

        foreach ($files as $i => $file) {
            $localFile = $this->basePath . '/' . $file['path'];
            $remoteFile = $remotePath . '/' . $file['path'];
            $remoteDir = dirname($remoteFile);

            try {
                // Criar diretórios remotos se necessário
                if (!isset($createdDirs[$remoteDir])) {
                    $this->ftpMkdirRecursive($ftp, $remoteDir);
                    $createdDirs[$remoteDir] = true;
                }

                // Upload do arquivo
                if (@ftp_put($ftp, $remoteFile, $localFile, FTP_BINARY)) {
                    $sent++;
                } else {
                    $errors++;
                    $log[] = "[" . date('H:i:s') . "] ⚠️ Falha: {$file['path']}";
                }

                // Atualizar progresso a cada 50 arquivos
                if (($i + 1) % 50 === 0) {
                    $pct = round(($i + 1) / count($files) * 100);
                    $log[] = "[" . date('H:i:s') . "] Progresso: {$pct}% ({$sent} enviados)";
                    $this->db->update('deploy_historico', [
                        'arquivos_enviados' => $sent,
                        'arquivos_erro' => $errors,
                    ], 'id = ?', [$deployId]);
                }

            } catch (Exception $e) {
                $errors++;
                $log[] = "[" . date('H:i:s') . "] ⚠️ Erro em {$file['path']}: " . $e->getMessage();
            }
        }

        ftp_close($ftp);
        $log[] = "[" . date('H:i:s') . "] FTP encerrado. $sent enviados, $errors erros.";

        return ['sent' => $sent, 'errors' => $errors];
    }

    private function ftpMkdirRecursive($ftp, string $dir): void
    {
        $parts = explode('/', trim($dir, '/'));
        $current = '';
        foreach ($parts as $part) {
            $current .= '/' . $part;
            @ftp_mkdir($ftp, $current);
        }
    }

    // =========================================================
    //  Deploy via SFTP (ssh2)
    // =========================================================

    private function deployViaSFTP(array $files, array &$log, int $deployId): array
    {
        $host = $this->getConfigValue('prod_host');
        $port = (int)$this->getConfigValue('prod_port', '22');
        $user = $this->getConfigValue('prod_user');
        $pass = $this->decrypt($this->getConfigValue('prod_pass'));
        $remotePath = rtrim($this->getConfigValue('prod_path', '/helpdesk'), '/');

        if (!function_exists('ssh2_connect')) {
            throw new Exception("Extensão ssh2 não está instalada. Use: pecl install ssh2 ou mude para FTP.");
        }

        $log[] = "[" . date('H:i:s') . "] Conectando SFTP: $user@$host:$port...";

        $conn = @ssh2_connect($host, $port);
        if (!$conn) {
            throw new Exception("Não foi possível conectar via SSH: $host:$port");
        }

        if (!@ssh2_auth_password($conn, $user, $pass)) {
            throw new Exception("Falha na autenticação SSH (usuário: $user)");
        }

        $sftp = ssh2_sftp($conn);
        if (!$sftp) {
            throw new Exception("Não foi possível iniciar SFTP");
        }

        $log[] = "[" . date('H:i:s') . "] ✅ Conectado via SFTP";

        $sent = 0;
        $errors = 0;
        $sftpBase = "ssh2.sftp://" . intval($sftp);
        $createdDirs = [];

        foreach ($files as $i => $file) {
            $localFile = $this->basePath . '/' . $file['path'];
            $remoteFile = $remotePath . '/' . $file['path'];
            $remoteDir = dirname($remoteFile);

            try {
                // Criar diretórios remotos
                if (!isset($createdDirs[$remoteDir])) {
                    $this->sftpMkdirRecursive($sftpBase, $remoteDir);
                    $createdDirs[$remoteDir] = true;
                }

                // Upload
                $content = file_get_contents($localFile);
                if ($content !== false && file_put_contents("$sftpBase$remoteFile", $content) !== false) {
                    $sent++;
                } else {
                    $errors++;
                    $log[] = "[" . date('H:i:s') . "] ⚠️ Falha: {$file['path']}";
                }

                if (($i + 1) % 50 === 0) {
                    $pct = round(($i + 1) / count($files) * 100);
                    $log[] = "[" . date('H:i:s') . "] Progresso: {$pct}% ({$sent} enviados)";
                    $this->db->update('deploy_historico', [
                        'arquivos_enviados' => $sent,
                        'arquivos_erro' => $errors,
                    ], 'id = ?', [$deployId]);
                }

            } catch (Exception $e) {
                $errors++;
                $log[] = "[" . date('H:i:s') . "] ⚠️ Erro em {$file['path']}: " . $e->getMessage();
            }
        }

        $log[] = "[" . date('H:i:s') . "] SFTP encerrado. $sent enviados, $errors erros.";
        return ['sent' => $sent, 'errors' => $errors];
    }

    private function sftpMkdirRecursive(string $sftpBase, string $dir): void
    {
        $parts = explode('/', trim($dir, '/'));
        $current = '';
        foreach ($parts as $part) {
            $current .= '/' . $part;
            if (!is_dir("$sftpBase$current")) {
                @mkdir("$sftpBase$current", 0755);
            }
        }
    }

    // =========================================================
    //  Deploy via cópia local (mesmo servidor ou compartilhamento de rede)
    // =========================================================

    private function deployViaLocalCopy(array $files, array &$log, int $deployId): array
    {
        $destPath = $this->getConfigValue('prod_path');

        if (!$destPath || !is_dir(dirname($destPath))) {
            throw new Exception("Caminho de destino inválido: $destPath");
        }

        $log[] = "[" . date('H:i:s') . "] Copiando para: $destPath";

        $sent = 0;
        $errors = 0;

        foreach ($files as $i => $file) {
            $src = $this->basePath . '/' . $file['path'];
            $dst = $destPath . '/' . $file['path'];
            $dstDir = dirname($dst);

            try {
                if (!is_dir($dstDir)) {
                    mkdir($dstDir, 0755, true);
                }
                if (copy($src, $dst)) {
                    $sent++;
                } else {
                    $errors++;
                    $log[] = "[" . date('H:i:s') . "] ⚠️ Falha: {$file['path']}";
                }

                if (($i + 1) % 50 === 0) {
                    $pct = round(($i + 1) / count($files) * 100);
                    $log[] = "[" . date('H:i:s') . "] Progresso: {$pct}% ({$sent} enviados)";
                    $this->db->update('deploy_historico', [
                        'arquivos_enviados' => $sent,
                        'arquivos_erro' => $errors,
                    ], 'id = ?', [$deployId]);
                }

            } catch (Exception $e) {
                $errors++;
            }
        }

        $log[] = "[" . date('H:i:s') . "] Cópia local encerrada. $sent copiados, $errors erros.";
        return ['sent' => $sent, 'errors' => $errors];
    }

    // =========================================================
    //  Config Overrides (aplicar config de produção)
    // =========================================================

    public function getConfigOverrides(): array
    {
        $json = $this->getConfigValue('config_overrides', '{}');
        return json_decode($json, true) ?: [];
    }

    public function saveConfigOverrides(array $overrides): bool
    {
        return $this->saveConfig([
            'config_overrides' => json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);
    }

    /**
     * Aplica overrides de config na produção após o upload dos arquivos.
     * Gera os arquivos config/database.php e config/app.php remotamente.
     */
    private function applyConfigOverrides(string $method, array $overrides, array &$log): bool
    {
        if (empty($overrides)) return false;

        $remotePath = rtrim($this->getConfigValue('prod_path', '/helpdesk'), '/');
        $filesToUpload = [];

        // ===== database.php override =====
        $dbOverrides = array_filter($overrides, fn($k) => str_starts_with($k, 'DB_'), ARRAY_FILTER_USE_KEY);
        if (!empty($dbOverrides)) {
            $dbHost = $dbOverrides['DB_HOST'] ?? DB_HOST;
            $dbName = $dbOverrides['DB_NAME'] ?? DB_NAME;
            $dbUser = $dbOverrides['DB_USER'] ?? DB_USER;
            $dbPass = $dbOverrides['DB_PASS'] ?? DB_PASS;
            $dbPort = $dbOverrides['DB_PORT'] ?? (defined('DB_PORT') ? DB_PORT : 3306);

            $dbContent = "<?php\n";
            $dbContent .= "/**\n * Configuração do Banco de Dados\n * Oracle X — Produção (gerado por Deploy)\n */\n\n";
            $dbContent .= "define('DB_HOST', " . var_export($dbHost, true) . ");\n";
            $dbContent .= "define('DB_NAME', " . var_export($dbName, true) . ");\n";
            $dbContent .= "define('DB_USER', " . var_export($dbUser, true) . ");\n";
            $dbContent .= "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
            $dbContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            $dbContent .= "define('DB_PORT', $dbPort);\n";

            $filesToUpload['config/database.php'] = $dbContent;
            $log[] = "[" . date('H:i:s') . "]   → database.php (host=$dbHost, db=$dbName)";
        }

        // ===== app.php overrides (BASE_URL) =====
        $baseUrl = $overrides['BASE_URL'] ?? null;
        if ($baseUrl !== null) {
            // Ler app.php local e substituir BASE_URL
            $appContent = file_get_contents($this->basePath . '/config/app.php');
            $appContent = preg_replace(
                "/define\('BASE_URL',\s*'[^']*'\)/",
                "define('BASE_URL', " . var_export($baseUrl, true) . ")",
                $appContent
            );
            $filesToUpload['config/app.php'] = $appContent;
            $log[] = "[" . date('H:i:s') . "]   → app.php (BASE_URL=$baseUrl)";
        }

        // Upload dos arquivos de config
        if (empty($filesToUpload)) return false;

        foreach ($filesToUpload as $relPath => $content) {
            $this->uploadContent($method, $remotePath . '/' . $relPath, $content);
        }

        return true;
    }

    /**
     * Upload de conteúdo gerado (não é arquivo local)
     */
    private function uploadContent(string $method, string $remotePath, string $content): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'deploy_');
        file_put_contents($tmpFile, $content);

        try {
            switch ($method) {
                case 'ftp':
                    $host = $this->getConfigValue('prod_host');
                    $port = (int)$this->getConfigValue('prod_port', '21');
                    $user = $this->getConfigValue('prod_user');
                    $pass = $this->decrypt($this->getConfigValue('prod_pass'));
                    $ftp = @ftp_connect($host, $port, 15);
                    if ($ftp && @ftp_login($ftp, $user, $pass)) {
                        ftp_pasv($ftp, true);
                        $dir = dirname($remotePath);
                        $this->ftpMkdirRecursive($ftp, $dir);
                        @ftp_put($ftp, $remotePath, $tmpFile, FTP_BINARY);
                        ftp_close($ftp);
                    }
                    break;

                case 'local_copy':
                    $dir = dirname($remotePath);
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    copy($tmpFile, $remotePath);
                    break;

                case 'sftp':
                    // Similar ao deploy principal
                    break;
            }
        } finally {
            @unlink($tmpFile);
        }
    }

    // =========================================================
    //  Testar conexão
    // =========================================================

    public function testConnection(): array
    {
        $method = $this->getConfigValue('deploy_method', 'ftp');
        $host = $this->getConfigValue('prod_host');
        $port = (int)$this->getConfigValue('prod_port', '21');
        $user = $this->getConfigValue('prod_user');
        $pass = $this->decrypt($this->getConfigValue('prod_pass'));
        $remotePath = $this->getConfigValue('prod_path', '/helpdesk');

        $startTime = microtime(true);

        try {
            switch ($method) {
                case 'ftp':
                    $ftp = @ftp_connect($host, $port, 10);
                    if (!$ftp) throw new Exception("Não conectou em $host:$port");
                    if (!@ftp_login($ftp, $user, $pass)) {
                        ftp_close($ftp);
                        throw new Exception("Login falhou (user: $user)");
                    }
                    ftp_pasv($ftp, true);

                    // Tentar listar diretório remoto
                    $list = @ftp_nlist($ftp, $remotePath);
                    $dirExists = $list !== false;
                    ftp_close($ftp);

                    $ms = round((microtime(true) - $startTime) * 1000);
                    return [
                        'success' => true,
                        'method' => 'FTP',
                        'host' => "$host:$port",
                        'path' => $remotePath,
                        'dir_exists' => $dirExists,
                        'latency_ms' => $ms,
                        'message' => "✅ Conexão FTP OK ({$ms}ms)" . ($dirExists ? " — diretório encontrado" : " — diretório NÃO encontrado"),
                    ];

                case 'sftp':
                    if (!function_exists('ssh2_connect')) {
                        throw new Exception("Extensão ssh2 não instalada");
                    }
                    $conn = @ssh2_connect($host, $port);
                    if (!$conn) throw new Exception("Não conectou em $host:$port");
                    if (!@ssh2_auth_password($conn, $user, $pass)) {
                        throw new Exception("Login SSH falhou (user: $user)");
                    }
                    $sftp = ssh2_sftp($conn);
                    $stat = @ssh2_sftp_stat($sftp, $remotePath);
                    $ms = round((microtime(true) - $startTime) * 1000);

                    return [
                        'success' => true,
                        'method' => 'SFTP',
                        'host' => "$host:$port",
                        'path' => $remotePath,
                        'dir_exists' => $stat !== false,
                        'latency_ms' => $ms,
                        'message' => "✅ Conexão SFTP OK ({$ms}ms)",
                    ];

                case 'local_copy':
                    $exists = is_dir($remotePath) || is_dir(dirname($remotePath));
                    $ms = round((microtime(true) - $startTime) * 1000);
                    return [
                        'success' => $exists,
                        'method' => 'Local Copy',
                        'path' => $remotePath,
                        'dir_exists' => is_dir($remotePath),
                        'latency_ms' => $ms,
                        'message' => $exists ? "✅ Caminho acessível" : "❌ Caminho não encontrado: $remotePath",
                    ];

                default:
                    throw new Exception("Método desconhecido: $method");
            }
        } catch (Exception $e) {
            $ms = round((microtime(true) - $startTime) * 1000);
            return [
                'success' => false,
                'method' => strtoupper($method),
                'error' => $e->getMessage(),
                'latency_ms' => $ms,
                'message' => "❌ " . $e->getMessage(),
            ];
        }
    }

    // =========================================================
    //  Utilitários
    // =========================================================

    private function getExcludePatterns(): array
    {
        $raw = $this->getConfigValue('exclude_patterns', '');
        $patterns = array_map('trim', explode(',', $raw));
        // Sempre excluir
        $always = ['.git', '.venv', 'node_modules', 'storage/logs', '*.log', 'remote/client/build', 'remote/client/dist', 'remote/client/output', 'remote/client/__pycache__'];
        return array_unique(array_filter(array_merge($patterns, $always)));
    }

    private function isExcluded(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);
            if (empty($pattern)) continue;

            // Padrão exato de diretório (ex: .git, node_modules)
            if (!str_contains($pattern, '*') && !str_contains($pattern, '/')) {
                // Match no início ou como componente de caminho
                if (str_starts_with($path, $pattern . '/') || $path === $pattern) return true;
                if (str_contains($path, '/' . $pattern . '/')) return true;
            }
            // Padrão com wildcard (ex: *.log)
            elseif (str_starts_with($pattern, '*')) {
                $ext = substr($pattern, 1);
                if (str_ends_with($path, $ext)) return true;
            }
            // Padrão com path (ex: storage/logs/*)
            elseif (str_ends_with($pattern, '/*')) {
                $dir = substr($pattern, 0, -2);
                if (str_starts_with($path, $dir . '/') || str_starts_with($path, $dir)) return true;
            }
            // Padrão exato
            else {
                if (fnmatch($pattern, $path)) return true;
                if (str_starts_with($path, $pattern)) return true;
            }
        }
        return false;
    }

    private function getFileCategory(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match (true) {
            in_array($ext, ['php']) => 'php',
            in_array($ext, ['js', 'ts']) => 'js',
            in_array($ext, ['css', 'scss', 'less']) => 'css',
            in_array($ext, ['html', 'htm', 'twig']) => 'html',
            in_array($ext, ['json', 'xml', 'yml', 'yaml', 'ini', 'env']) => 'config',
            in_array($ext, ['sql']) => 'sql',
            in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'webp']) => 'image',
            in_array($ext, ['ttf', 'woff', 'woff2', 'eot']) => 'font',
            in_array($ext, ['md', 'txt', 'log']) => 'doc',
            default => 'other',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    // ===== Criptografia simples para senhas =====

    private function getEncryptionKey(): string
    {
        return hash('sha256', 'helpdesk_deploy_' . (defined('DB_PASS') ? DB_PASS : 'key'), true);
    }

    private function encrypt(string $value): string
    {
        if (empty($value)) return '';
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    private function decrypt(string $value): string
    {
        if (empty($value)) return '';
        $key = $this->getEncryptionKey();
        $data = base64_decode($value);
        if ($data === false || !str_contains($data, '::')) return $value; // Não está criptografado
        [$iv, $encrypted] = explode('::', $data, 2);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        return $decrypted !== false ? $decrypted : $value;
    }

    // =========================================================
    //  Estatísticas
    // =========================================================

    public function getStats(): array
    {
        $total = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM deploy_historico");
        $ultimo = $this->db->fetch("SELECT * FROM deploy_historico ORDER BY iniciado_em DESC LIMIT 1");
        $sucesso = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM deploy_historico WHERE status = 'concluido'");
        $erro = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM deploy_historico WHERE status = 'erro'");

        return [
            'total_deploys' => $total,
            'deploys_sucesso' => $sucesso,
            'deploys_erro' => $erro,
            'ultimo_deploy' => $ultimo,
        ];
    }
}
