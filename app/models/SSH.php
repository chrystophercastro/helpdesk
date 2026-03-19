<?php
/**
 * Model: SSH
 * Gerenciamento de servidores SSH, execução remota de comandos
 */

class SSH {
    private $db;
    private const CIPHER_METHOD = 'aes-256-cbc';
    private const ENC_SALT = 'helpdesk_ssh_enc_2024!';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ==========================================
    //  CRIPTOGRAFIA DE CREDENCIAIS
    // ==========================================

    private function getEncryptionKey() {
        return hash('sha256', self::ENC_SALT . __DIR__, true);
    }

    public function encrypt($plaintext) {
        if (empty($plaintext)) return '';
        $key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt($ciphertext) {
        if (empty($ciphertext)) return '';
        $raw = base64_decode($ciphertext);
        if ($raw === false || strlen($raw) < 17) return '';
        $key = $this->getEncryptionKey();
        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        return openssl_decrypt($encrypted, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv) ?: '';
    }

    // ==========================================
    //  CHECK EXTENSÃO SSH2
    // ==========================================

    public static function isSSH2Available() {
        return function_exists('ssh2_connect');
    }

    public static function getSSH2Instructions() {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'title' => 'Instalar extensão SSH2 (Windows/XAMPP)',
                'steps' => [
                    'Baixe php_ssh2.dll compatível com sua versão do PHP em pecl.php.net/package/ssh2',
                    'Copie php_ssh2.dll para a pasta php/ext/ do XAMPP',
                    'Baixe também libssh2.dll e copie para a pasta php/ do XAMPP',
                    'Adicione "extension=ssh2" no php.ini',
                    'Reinicie o Apache no XAMPP',
                ],
                'auto_install' => true,
            ];
        }
        return [
            'title' => 'Instalar extensão SSH2 (Linux)',
            'steps' => [
                'Execute: sudo apt install php-ssh2  (Debian/Ubuntu)',
                'Ou: sudo yum install php-pecl-ssh2  (CentOS/RHEL)',
                'Reinicie o Apache: sudo systemctl restart apache2',
            ],
            'auto_install' => false,
        ];
    }

    /**
     * Garante permissão de escrita em um diretório (Windows)
     * Usa icacls com SID universal para conceder controle total
     */
    private static function ensureWritePermissions($dir, &$log = []) {
        if (!$dir || !is_dir($dir)) return;
        if (is_writable($dir)) {
            $log[] = "✓ Permissão OK: {$dir}";
            return;
        }

        if (PHP_OS_FAMILY !== 'Windows') return;

        $log[] = "⚠ Sem permissão de escrita em: {$dir} — tentando corrigir...";

        // Tentar com SID universal S-1-1-0 (Everyone em qualquer idioma)
        $cmd = "icacls \"" . rtrim($dir, '\\') . "\" /grant *S-1-1-0:(OI)(CI)F /T /Q 2>&1";
        $output = [];
        @exec($cmd, $output, $code);

        if ($code === 0) {
            clearstatcache(true, $dir);
            $log[] = "✓ Permissão concedida via icacls: {$dir}";
            return;
        }

        $log[] = "✗ icacls falhou (código {$code}): " . implode(' ', $output);

        // Tentar com o usuário atual
        $user = getenv('USERNAME');
        if ($user) {
            $cmd2 = "icacls \"" . rtrim($dir, '\\') . "\" /grant \"{$user}:(OI)(CI)F\" /T /Q 2>&1";
            $out2 = [];
            @exec($cmd2, $out2, $code2);

            if ($code2 === 0) {
                clearstatcache(true, $dir);
                $log[] = "✓ Permissão concedida para {$user}: {$dir}";
                return;
            }
            $log[] = "✗ icacls usuário falhou: " . implode(' ', $out2);
        }

        // Tentar via takeown
        $cmd3 = "takeown /F \"" . rtrim($dir, '\\') . "\" /R /D S 2>&1";
        @exec($cmd3, $out3, $code3);
        if ($code3 === 0) {
            // Após takeown, tentar icacls novamente
            @exec($cmd, $out4, $code4);
            clearstatcache(true, $dir);
            $log[] = $code4 === 0
                ? "✓ Permissão concedida via takeown + icacls: {$dir}"
                : "✗ takeown OK mas icacls falhou";
        } else {
            $log[] = "✗ Sem permissão para corrigir. Execute fix_permissions.bat como Administrador.";
        }
    }

    /**
     * Instalação automática da extensão SSH2 (Windows/XAMPP)
     * Detecta versão do PHP, baixa DLLs do PECL, configura php.ini
     */
    public static function autoInstallSSH2() {
        $log = [];

        // Já instalado?
        if (self::isSSH2Available()) {
            return ['success' => true, 'message' => 'A extensão SSH2 já está instalada e ativa!', 'log' => $log];
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return [
                'success' => false,
                'message' => 'Instalação automática disponível apenas para Windows/XAMPP. Use: sudo apt install php-ssh2',
                'log' => $log
            ];
        }

        // ---- Detectar configuração do PHP ----
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $isTS = ZEND_THREAD_SAFE;
        $is64 = PHP_INT_SIZE === 8;
        $arch = $is64 ? 'x64' : 'x86';
        $ts = $isTS ? 'ts' : 'nts';

        // Mapeamento de VS version por versão do PHP
        $vsVersions = [
            '8.0' => 'vs16',
            '8.1' => 'vs16',
            '8.2' => 'vs16',
            '8.3' => 'vs16',
            '8.4' => 'vs17',
        ];

        if (!isset($vsVersions[$phpVersion])) {
            return [
                'success' => false,
                'message' => "PHP {$phpVersion} não suportado para instalação automática (suportados: 8.0-8.4)",
                'log' => $log
            ];
        }

        $vsVer = $vsVersions[$phpVersion];
        $phpIni = php_ini_loaded_file();

        // ---- Detectar diretórios corretos (XAMPP fix) ----
        // No XAMPP, PHP_BINARY = apache/bin/httpd.exe e PHP_EXTENSION_DIR pode apontar para C:\php\ext
        // Precisamos derivar os caminhos reais a partir do php.ini
        $extDir = PHP_EXTENSION_DIR;
        $phpDir = dirname(PHP_BINARY);

        // Corrigir via php.ini (sempre confiável no XAMPP)
        if ($phpIni) {
            $iniDir = dirname($phpIni); // C:\xampp\php
            $realExtDir = $iniDir . DIRECTORY_SEPARATOR . 'ext';
            $realPhpDir = $iniDir;

            // Se o ext/ real existe, usar ele
            if (is_dir($realExtDir)) {
                $extDir = $realExtDir;
            }
            // Se o phpDir aponta para apache/bin, corrigir
            if (stripos($phpDir, 'apache') !== false && is_dir($realPhpDir)) {
                $phpDir = $realPhpDir;
            }
        }

        // Fallback: procurar php.exe no XAMPP
        if (!is_dir($extDir) || stripos($phpDir, 'apache') !== false) {
            $possiblePaths = ['C:\\xampp\\php', 'D:\\xampp\\php', 'E:\\xampp\\php'];
            foreach ($possiblePaths as $p) {
                if (is_dir($p . '\\ext')) {
                    $extDir = $p . '\\ext';
                    $phpDir = $p;
                    break;
                }
            }
        }

        // Criar ext/ se não existir
        if (!is_dir($extDir)) {
            @mkdir($extDir, 0777, true);
        }

        $log[] = "PHP {$phpVersion} | {$ts} | {$vsVer} | {$arch}";
        $log[] = "Diretório ext: {$extDir}";
        $log[] = "Diretório PHP: {$phpDir}";
        $log[] = "php.ini: {$phpIni}";

        // ---- Garantir permissões de escrita via icacls ----
        self::ensureWritePermissions($extDir, $log);
        self::ensureWritePermissions($phpDir, $log);
        if ($phpIni) {
            self::ensureWritePermissions(dirname($phpIni), $log);
        }

        if (!is_writable($extDir)) {
            return [
                'success' => false,
                'message' => "Sem permissão de escrita em: {$extDir}. Execute o XAMPP Control Panel como Administrador.",
                'log' => $log
            ];
        }

        // ---- Tentar baixar o pacote ----
        $ssh2Versions = ['1.4.1', '1.4', '1.3.1'];
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_ssh2_' . uniqid() . '.zip';
        $downloaded = false;
        $downloadUrl = '';

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 60,
                'user_agent' => 'Mozilla/5.0 (PHP HelpDesk Auto-Installer)',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        foreach ($ssh2Versions as $ver) {
            $url = "https://windows.php.net/downloads/pecl/releases/ssh2/{$ver}/php_ssh2-{$ver}-{$phpVersion}-{$ts}-{$vsVer}-{$arch}.zip";
            $log[] = "Tentando: {$url}";

            $data = @file_get_contents($url, false, $ctx);
            if ($data !== false && strlen($data) > 1000) {
                file_put_contents($tempFile, $data);
                $downloaded = true;
                $downloadUrl = $url;
                $log[] = "✓ Download OK (" . round(strlen($data) / 1024) . " KB)";
                break;
            } else {
                $log[] = "✗ Falhou";
            }
        }

        // Tentar snaps (versões mais recentes no diretório snaps)
        if (!$downloaded) {
            foreach (['1.4.1', '1.4'] as $ver) {
                $url = "https://windows.php.net/downloads/pecl/snaps/ssh2/{$ver}/php_ssh2-{$ver}-{$phpVersion}-{$ts}-{$vsVer}-{$arch}.zip";
                $log[] = "Tentando snaps: {$url}";

                $data = @file_get_contents($url, false, $ctx);
                if ($data !== false && strlen($data) > 1000) {
                    file_put_contents($tempFile, $data);
                    $downloaded = true;
                    $downloadUrl = $url;
                    $log[] = "✓ Download OK (" . round(strlen($data) / 1024) . " KB)";
                    break;
                } else {
                    $log[] = "✗ Falhou";
                }
            }
        }

        if (!$downloaded) {
            return [
                'success' => false,
                'message' => "Não foi possível baixar php_ssh2 para PHP {$phpVersion} ({$ts}-{$vsVer}-{$arch}). Baixe manualmente em pecl.php.net/package/ssh2",
                'log' => $log,
                'details' => [
                    'php_version' => $phpVersion,
                    'thread_safe' => $isTS,
                    'arch' => $arch,
                    'vs' => $vsVer,
                ]
            ];
        }

        // ---- Extrair ZIP ----
        if (!class_exists('ZipArchive')) {
            @unlink($tempFile);
            return ['success' => false, 'message' => 'Extensão ZIP do PHP não está habilitada', 'log' => $log];
        }

        $zip = new \ZipArchive();
        if ($zip->open($tempFile) !== true) {
            @unlink($tempFile);
            return ['success' => false, 'message' => 'Erro ao abrir arquivo ZIP baixado', 'log' => $log];
        }

        $extractDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_ssh2_ext_' . uniqid();
        @mkdir($extractDir, 0777, true);
        $zip->extractTo($extractDir);
        $zip->close();
        @unlink($tempFile);

        $log[] = "Extraído em: {$extractDir}";

        // ---- Copiar DLLs ----
        $dllsCopied = [];
        $errors = [];

        // Busca recursiva por DLLs no diretório extraído
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractDir));
        foreach ($iterator as $file) {
            $filename = strtolower($file->getFilename());

            if ($filename === 'php_ssh2.dll') {
                $dest = $extDir . DIRECTORY_SEPARATOR . 'php_ssh2.dll';
                if (@copy($file->getPathname(), $dest)) {
                    $dllsCopied[] = "php_ssh2.dll → {$extDir}";
                    $log[] = "✓ Copiado php_ssh2.dll para {$extDir}";
                } else {
                    $errors[] = "Falha ao copiar php_ssh2.dll para {$extDir}";
                    $log[] = "✗ Erro ao copiar php_ssh2.dll";
                }
            }

            if ($filename === 'libssh2.dll') {
                $dest = $phpDir . DIRECTORY_SEPARATOR . 'libssh2.dll';
                if (@copy($file->getPathname(), $dest)) {
                    $dllsCopied[] = "libssh2.dll → {$phpDir}";
                    $log[] = "✓ Copiado libssh2.dll para {$phpDir}";
                } else {
                    $errors[] = "Falha ao copiar libssh2.dll para {$phpDir}";
                    $log[] = "✗ Erro ao copiar libssh2.dll";
                }
            }
        }

        // Limpar diretório temporário
        $cleanIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($cleanIterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($extractDir);

        if (empty($dllsCopied)) {
            return [
                'success' => false,
                'message' => 'DLLs não encontradas no pacote baixado. Tente baixar manualmente.',
                'log' => $log,
                'errors' => $errors
            ];
        }

        // ---- Configurar php.ini ----
        $iniUpdated = false;
        if ($phpIni && is_writable($phpIni)) {
            $iniContent = file_get_contents($phpIni);

            if (strpos($iniContent, 'extension=ssh2') === false) {
                // Encontrar a última linha extension= e adicionar após ela
                if (preg_match_all('/^extension=\S+/m', $iniContent, $matches, PREG_OFFSET_CAPTURE)) {
                    $lastMatch = end($matches[0]);
                    $pos = $lastMatch[1] + strlen($lastMatch[0]);
                    $iniContent = substr($iniContent, 0, $pos) . "\nextension=ssh2" . substr($iniContent, $pos);
                } else {
                    $iniContent .= "\nextension=ssh2\n";
                }

                if (@file_put_contents($phpIni, $iniContent)) {
                    $iniUpdated = true;
                    $log[] = "✓ Adicionado 'extension=ssh2' no php.ini";
                } else {
                    $errors[] = "Não foi possível editar php.ini. Adicione manualmente: extension=ssh2";
                    $log[] = "✗ Erro ao editar php.ini";
                }
            } else {
                $iniUpdated = true;
                $log[] = "✓ extension=ssh2 já existe no php.ini";
            }
        } else {
            $errors[] = "php.ini não encontrado ou sem permissão de escrita ({$phpIni})";
            $log[] = "✗ Sem acesso ao php.ini";
        }

        return [
            'success' => true,
            'message' => 'SSH2 instalado com sucesso! Reinicie o Apache no XAMPP para ativar a extensão.',
            'requires_restart' => true,
            'log' => $log,
            'details' => [
                'dlls' => $dllsCopied,
                'ini_updated' => $iniUpdated,
                'ini_path' => $phpIni,
                'php_version' => $phpVersion,
                'download_url' => $downloadUrl,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Tentar reiniciar o Apache (Windows/XAMPP)
     */
    public static function restartApache() {
        if (PHP_OS_FAMILY !== 'Windows') {
            return ['success' => false, 'message' => 'Reinício automático apenas no Windows'];
        }

        $log = [];

        // Tentar via XAMPP
        $xamppPath = dirname(dirname(PHP_BINARY)); // php/ -> xampp/
        $apacheControl = $xamppPath . '\\apache\\bin\\httpd.exe';

        if (file_exists($apacheControl)) {
            $log[] = "Apache encontrado: {$apacheControl}";

            // Tentar reiniciar via net stop/start
            @exec('net stop Apache2.4 2>&1', $out1, $code1);
            $log[] = "net stop Apache2.4: " . implode(' ', $out1);
            sleep(1);
            @exec('net start Apache2.4 2>&1', $out2, $code2);
            $log[] = "net start Apache2.4: " . implode(' ', $out2);

            if ($code2 === 0) {
                return ['success' => true, 'message' => 'Apache reiniciado com sucesso!', 'log' => $log];
            }
        }

        // Tentar httpd -k restart
        @exec('httpd -k restart 2>&1', $out3, $code3);
        $log[] = "httpd -k restart: " . implode(' ', $out3);

        if ($code3 === 0) {
            return ['success' => true, 'message' => 'Apache reiniciado!', 'log' => $log];
        }

        // Tentar via taskkill + start
        @exec('tasklist /FI "IMAGENAME eq httpd.exe" 2>&1', $outList);
        if (count($outList) > 2) {
            $log[] = "Apache está rodando, tentando restart via XAMPP...";
            $xamppApacheStart = $xamppPath . '\\apache_start.bat';
            if (file_exists($xamppApacheStart)) {
                @exec("taskkill /F /IM httpd.exe 2>&1", $outKill);
                sleep(2);
                @pclose(@popen("start /B \"\" \"{$xamppApacheStart}\"", 'r'));
                $log[] = "Apache reiniciado via XAMPP batch";
                return ['success' => true, 'message' => 'Apache reiniciado via XAMPP!', 'log' => $log];
            }
        }

        return [
            'success' => false,
            'message' => 'Não foi possível reiniciar automaticamente. Reinicie o Apache manualmente pelo XAMPP Control Panel.',
            'log' => $log
        ];
    }

    // ==========================================
    //  CRUD
    // ==========================================

    public function listar($filtros = []) {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['busca'])) {
            $where[] = "(s.nome LIKE :busca OR s.host LIKE :busca OR s.usuario LIKE :busca OR s.descricao LIKE :busca)";
            $params[':busca'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['grupo'])) {
            $where[] = "s.grupo = :grupo";
            $params[':grupo'] = $filtros['grupo'];
        }

        if (!empty($filtros['status'])) {
            $where[] = "s.ultimo_status = :status";
            $params[':status'] = $filtros['status'];
        }

        $sql = "SELECT s.*, u.nome as criado_por_nome
                FROM servidores_ssh s
                LEFT JOIN usuarios u ON s.criado_por = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY s.grupo, s.nome";

        return $this->db->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->db->fetch(
            "SELECT s.*, u.nome as criado_por_nome
             FROM servidores_ssh s
             LEFT JOIN usuarios u ON s.criado_por = u.id
             WHERE s.id = :id",
            [':id' => $id]
        );
    }

    public function criar($dados) {
        $insert = [
            'nome' => trim($dados['nome']),
            'host' => trim($dados['host']),
            'porta' => (int)($dados['porta'] ?? 22),
            'usuario' => trim($dados['usuario']),
            'metodo_auth' => $dados['metodo_auth'] ?? 'password',
            'credencial' => $this->encrypt($dados['credencial'] ?? ''),
            'grupo' => trim($dados['grupo'] ?? 'Geral'),
            'descricao' => trim($dados['descricao'] ?? ''),
            'tags' => trim($dados['tags'] ?? ''),
            'criado_por' => $dados['criado_por'] ?? null,
        ];

        if (!empty($dados['passphrase'])) {
            $insert['passphrase'] = $this->encrypt($dados['passphrase']);
        }

        return $this->db->insert('servidores_ssh', $insert);
    }

    public function atualizar($id, $dados) {
        $update = [];

        $campos = ['nome', 'host', 'usuario', 'grupo', 'descricao', 'tags', 'sistema_operacional'];
        foreach ($campos as $campo) {
            if (isset($dados[$campo])) {
                $update[$campo] = trim($dados[$campo]);
            }
        }

        if (isset($dados['porta'])) {
            $update['porta'] = (int)$dados['porta'];
        }

        if (isset($dados['metodo_auth'])) {
            $update['metodo_auth'] = $dados['metodo_auth'];
        }

        if (isset($dados['ativo'])) {
            $update['ativo'] = (int)$dados['ativo'];
        }

        // Só atualizar credencial se enviada (não vazia)
        if (!empty($dados['credencial'])) {
            $update['credencial'] = $this->encrypt($dados['credencial']);
        }

        if (!empty($dados['passphrase'])) {
            $update['passphrase'] = $this->encrypt($dados['passphrase']);
        }

        if (!empty($update)) {
            $this->db->update('servidores_ssh', $update, 'id = ?', [$id]);
        }
    }

    public function excluir($id) {
        $this->db->delete('servidores_ssh', 'id = ?', [$id]);
    }

    // ==========================================
    //  CONEXÃO E EXECUÇÃO SSH
    // ==========================================

    /**
     * Testar conectividade com o servidor (ping + porta 22)
     */
    public function testarConexao($id) {
        $servidor = $this->findById($id);
        if (!$servidor) throw new \Exception('Servidor não encontrado');

        $host = $servidor['host'];
        $porta = $servidor['porta'];

        // Testar conectividade de rede (TCP connect)
        $start = microtime(true);
        $fp = @fsockopen($host, $porta, $errno, $errstr, 5);
        $latencia = round((microtime(true) - $start) * 1000);

        if ($fp) {
            fclose($fp);
            $this->db->update('servidores_ssh', [
                'ultimo_status' => 'online',
                'ultimo_acesso' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            return [
                'online' => true,
                'latencia' => $latencia,
                'host' => $host,
                'porta' => $porta,
                'nome' => $servidor['nome'],
            ];
        }

        $this->db->update('servidores_ssh', [
            'ultimo_status' => 'offline',
        ], 'id = ?', [$id]);

        throw new \Exception("Não foi possível conectar em {$host}:{$porta} — {$errstr}");
    }

    // ==========================================
    //  EXECUÇÃO DE COMANDOS SSH
    // ==========================================

    /**
     * Envolver comando com sudo -S (lê senha via stdin, sem TTY interativo)
     */
    private function wrapWithSudo($comando, $useSudo, $password) {
        if (!$useSudo || empty($password)) {
            return $comando;
        }

        // Escapar a senha para uso no echo (evitar caracteres especiais quebrarem)
        $escapedPass = str_replace(["'"], ["'\\''"], $password);

        // sudo -S lê senha de stdin, não precisa de terminal interativo
        // O SUDO_PROMPT vazio evita output indesejado
        return "echo '{$escapedPass}' | SUDO_PROMPT='' sudo -S -p '' {$comando}";
    }

    /**
     * Limpar caracteres de controle de PTY da saída
     */
    private function cleanPtyOutput($output) {
        // Remover sequências ANSI de escape (cores, cursor, etc)
        $output = preg_replace('/\x1B\[[0-9;]*[a-zA-Z]/', '', $output);
        // Remover outros caracteres de controle (exceto \n \r \t)
        $output = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $output);
        // Limpar \r\n para \n
        $output = str_replace("\r\n", "\n", $output);
        $output = str_replace("\r", "\n", $output);
        // Remover prompt de sudo que pode vazar
        $output = preg_replace('/^\[sudo\] (senha|password) (para|for) \S+:\s*/m', '', $output);
        return trim($output);
    }

    /**
     * Executar comando SSH via extensão ssh2
     */
    private function executarViaSsh2($servidor, $comando, $timeout = 30, $useSudo = false, $sudoPassword = null) {
        $host = $servidor['host'];
        $porta = (int)$servidor['porta'];
        $usuario = $servidor['usuario'];
        $senha = $this->decrypt($servidor['credencial']);

        $connection = @ssh2_connect($host, $porta);
        if (!$connection) {
            throw new \Exception("Falha ao conectar em {$host}:{$porta}");
        }

        if ($servidor['metodo_auth'] === 'key') {
            // Auth por chave privada
            $tmpKey = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($tmpKey, $senha);
            chmod($tmpKey, 0600);

            $passphrase = !empty($servidor['passphrase']) ? $this->decrypt($servidor['passphrase']) : null;

            // Gerar chave pública a partir da privada
            $tmpPub = $tmpKey . '.pub';
            $pubKeyCmd = "ssh-keygen -y -f " . escapeshellarg($tmpKey) . " 2>/dev/null";
            $pubKeyContent = shell_exec($pubKeyCmd);
            if ($pubKeyContent) {
                file_put_contents($tmpPub, $pubKeyContent);
            }

            $auth = @ssh2_auth_pubkey_file($connection, $usuario, $tmpPub, $tmpKey, $passphrase);

            @unlink($tmpKey);
            @unlink($tmpPub);

            if (!$auth) {
                throw new \Exception('Falha na autenticação por chave SSH');
            }
        } else {
            // Auth por senha
            $auth = @ssh2_auth_password($connection, $usuario, $senha);
            if (!$auth) {
                throw new \Exception('Falha na autenticação — senha incorreta');
            }
        }

        // Montar comando final com sudo se necessário
        $actualCmd = $this->wrapWithSudo($comando, $useSudo, $sudoPassword ?: $senha);
        $fullCmd = $actualCmd . '; echo "___EXIT_CODE___:$?"';

        // Executar com PTY para suportar sudo/docker e comandos interativos
        $stream = ssh2_exec($connection, $fullCmd, 'xterm', null, 80, 24);
        if (!$stream) {
            throw new \Exception('Falha ao executar comando');
        }

        stream_set_blocking($stream, true);
        stream_set_timeout($stream, $timeout);

        $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stderr, true);

        $output = stream_get_contents($stream);
        $error = stream_get_contents($stderr);

        fclose($stream);
        fclose($stderr);

        // Limpar caracteres de controle do PTY
        $output = $this->cleanPtyOutput($output);

        // Extrair exit code da saída
        $exitCode = 0;
        if (preg_match('/___EXIT_CODE___:(\d+)/', $output, $m)) {
            $exitCode = (int)$m[1];
            $output = preg_replace('/\n?___EXIT_CODE___:\d+\s*$/', '', $output);
        }

        // Atualizar último acesso
        $this->db->update('servidores_ssh', [
            'ultimo_acesso' => date('Y-m-d H:i:s'),
            'ultimo_status' => 'online',
        ], 'id = ?', [$servidor['id']]);

        return [
            'output' => $output,
            'error' => $error,
            'exit_code' => $exitCode,
        ];
    }

    /**
     * Executar comando via ssh nativo (fallback)
     */
    private function executarViaNativo($servidor, $comando, $timeout = 30, $useSudo = false, $sudoPassword = null) {
        $host = $servidor['host'];
        $porta = (int)$servidor['porta'];
        $usuario = $servidor['usuario'];
        $senha = $this->decrypt($servidor['credencial']);

        // Montar comando com sudo se necessário
        $actualCmd = $this->wrapWithSudo($comando, $useSudo, $sudoPassword ?: $senha);

        if ($servidor['metodo_auth'] === 'key') {
            // Salvar chave em arquivo temporário
            $tmpKey = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($tmpKey, $senha);

            if (PHP_OS_FAMILY !== 'Windows') {
                chmod($tmpKey, 0600);
            }

            $sshCmd = sprintf(
                'ssh -tt -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -i %s -p %d %s@%s %s 2>&1',
                escapeshellarg($tmpKey),
                $porta,
                escapeshellarg($usuario),
                escapeshellarg($host),
                escapeshellarg($actualCmd . '; echo "___EXIT_CODE___:$?"')
            );

            $output = '';
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open($sshCmd, $descriptors, $pipes);

            if (is_resource($proc)) {
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
            }

            @unlink($tmpKey);
        } else {
            // Password auth via sshpass (Linux) ou plink (Windows)
            if (PHP_OS_FAMILY === 'Windows') {
                // Tentar plink.exe (PuTTY)
                $plinkPath = 'plink.exe';
                $sshCmd = sprintf(
                    '%s -ssh -P %d -l %s -pw %s -batch %s %s 2>&1',
                    $plinkPath,
                    $porta,
                    escapeshellarg($usuario),
                    escapeshellarg($senha),
                    escapeshellarg($host),
                    escapeshellarg($actualCmd . '; echo "___EXIT_CODE___:$?"')
                );
            } else {
                // Linux: usar sshpass + -tt para forçar PTY
                $sshCmd = sprintf(
                    'sshpass -p %s ssh -tt -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d %s@%s %s 2>&1',
                    escapeshellarg($senha),
                    $porta,
                    escapeshellarg($usuario),
                    escapeshellarg($host),
                    escapeshellarg($actualCmd . '; echo "___EXIT_CODE___:$?"')
                );
            }

            $output = '';
            $error = '';
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open($sshCmd, $descriptors, $pipes);

            if (is_resource($proc)) {
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
            }
        }

        // Limpar caracteres de controle do PTY
        $output = $this->cleanPtyOutput($output ?? '');

        // Extrair exit code
        $exitCode = 0;
        if (preg_match('/___EXIT_CODE___:(\d+)/', $output, $m)) {
            $exitCode = (int)$m[1];
            $output = preg_replace('/\n?___EXIT_CODE___:\d+\s*$/', '', $output);
        }

        // Atualizar último acesso
        $this->db->update('servidores_ssh', [
            'ultimo_acesso' => date('Y-m-d H:i:s'),
            'ultimo_status' => 'online',
        ], 'id = ?', [$servidor['id']]);

        return [
            'output' => $output,
            'error' => $error ?? '',
            'exit_code' => $exitCode,
        ];
    }

    /**
     * Executar comando num servidor
     */
    public function executarComando($id, $comando, $timeout = 30, $useSudo = false, $sudoPassword = null) {
        $servidor = $this->findById($id);
        if (!$servidor) throw new \Exception('Servidor não encontrado');

        // Tentar ssh2 primeiro, fallback para nativo
        if (self::isSSH2Available()) {
            return $this->executarViaSsh2($servidor, $comando, $timeout, $useSudo, $sudoPassword);
        }

        return $this->executarViaNativo($servidor, $comando, $timeout, $useSudo, $sudoPassword);
    }

    /**
     * Obter informações completas do sistema via SSH
     */
    public function getInfoSistema($id) {
        $cmd = implode('; ', [
            'echo "===HOSTNAME==="; hostname 2>/dev/null',
            'echo "===UPTIME==="; uptime 2>/dev/null',
            'echo "===OS==="; cat /etc/os-release 2>/dev/null | grep -E "^(NAME|VERSION|PRETTY_NAME)="',
            'echo "===KERNEL==="; uname -r 2>/dev/null',
            'echo "===ARCH==="; uname -m 2>/dev/null',
            'echo "===CPU_COUNT==="; nproc 2>/dev/null',
            'echo "===CPU_MODEL==="; cat /proc/cpuinfo 2>/dev/null | grep "model name" | head -1 | cut -d: -f2',
            'echo "===CPU_USAGE==="; top -bn1 2>/dev/null | grep "Cpu(s)" | awk \'{print $2}\'',
            'echo "===MEMORY==="; free -m 2>/dev/null | grep Mem',
            'echo "===SWAP==="; free -m 2>/dev/null | grep Swap',
            'echo "===DISK==="; df -h 2>/dev/null | grep -vE "^(tmpfs|devtmpfs|udev|Filesystem|overlay)" | head -10',
            'echo "===LOAD==="; cat /proc/loadavg 2>/dev/null',
            'echo "===IPS==="; hostname -I 2>/dev/null',
            'echo "===PROCESSES==="; ps aux 2>/dev/null | wc -l',
            'echo "===LOGGED==="; who 2>/dev/null | wc -l',
            'echo "===END==="',
        ]);

        $result = $this->executarComando($id, $cmd, 15);
        $output = $result['output'] ?? '';

        $info = [];

        // Parser de seções
        $sections = ['HOSTNAME', 'UPTIME', 'OS', 'KERNEL', 'ARCH', 'CPU_COUNT', 'CPU_MODEL',
                      'CPU_USAGE', 'MEMORY', 'SWAP', 'DISK', 'LOAD', 'IPS', 'PROCESSES', 'LOGGED'];

        foreach ($sections as $section) {
            $pattern = "/==={$section}===\n(.*?)(?====|\z)/s";
            if (preg_match($pattern, $output, $m)) {
                $info[$section] = trim($m[1]);
            } else {
                $info[$section] = '';
            }
        }

        // Processar memória
        $memTotal = $memUsed = $memFree = 0;
        if (preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $info['MEMORY'], $m)) {
            $memTotal = (int)$m[1];
            $memUsed = (int)$m[2];
            $memFree = (int)$m[3];
        }

        // Processar swap
        $swapTotal = $swapUsed = 0;
        if (preg_match('/Swap:\s+(\d+)\s+(\d+)/', $info['SWAP'], $m)) {
            $swapTotal = (int)$m[1];
            $swapUsed = (int)$m[2];
        }

        // Processar disco
        $discos = [];
        if ($info['DISK']) {
            foreach (explode("\n", $info['DISK']) as $line) {
                if (preg_match('/^(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+)%\s+(.+)$/', trim($line), $m)) {
                    $discos[] = [
                        'device' => $m[1],
                        'size' => $m[2],
                        'used' => $m[3],
                        'avail' => $m[4],
                        'use_pct' => (int)$m[5],
                        'mount' => $m[6],
                    ];
                }
            }
        }

        // Processar OS
        $osName = '';
        if (preg_match('/PRETTY_NAME="?([^"\n]+)"?/', $info['OS'], $m)) {
            $osName = $m[1];
        } elseif (preg_match('/NAME="?([^"\n]+)"?/', $info['OS'], $m)) {
            $osName = $m[1];
        }

        // Atualizar SO no banco
        if ($osName) {
            $this->db->update('servidores_ssh', [
                'sistema_operacional' => $osName,
            ], 'id = ?', [$id]);
        }

        return [
            'hostname' => $info['HOSTNAME'],
            'uptime' => $info['UPTIME'],
            'os' => $osName,
            'kernel' => $info['KERNEL'],
            'arch' => $info['ARCH'],
            'cpu_count' => (int)($info['CPU_COUNT'] ?: 0),
            'cpu_model' => trim($info['CPU_MODEL']),
            'cpu_usage' => floatval($info['CPU_USAGE']),
            'mem_total' => $memTotal,
            'mem_used' => $memUsed,
            'mem_free' => $memFree,
            'mem_pct' => $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0,
            'swap_total' => $swapTotal,
            'swap_used' => $swapUsed,
            'swap_pct' => $swapTotal > 0 ? round(($swapUsed / $swapTotal) * 100, 1) : 0,
            'discos' => $discos,
            'load' => $info['LOAD'],
            'ips' => $info['IPS'],
            'processes' => (int)trim($info['PROCESSES']),
            'logged_users' => (int)trim($info['LOGGED']),
        ];
    }

    // ==========================================
    //  ESTATÍSTICAS
    // ==========================================

    public function getEstatisticas() {
        $stats = $this->db->fetch("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN ultimo_status = 'online' THEN 1 ELSE 0 END) as online,
            SUM(CASE WHEN ultimo_status = 'offline' THEN 1 ELSE 0 END) as offline,
            SUM(CASE WHEN ultimo_status = 'desconhecido' THEN 1 ELSE 0 END) as desconhecido,
            COUNT(DISTINCT grupo) as grupos
            FROM servidores_ssh WHERE ativo = 1
        ");

        return [
            'total' => (int)($stats['total'] ?? 0),
            'online' => (int)($stats['online'] ?? 0),
            'offline' => (int)($stats['offline'] ?? 0),
            'desconhecido' => (int)($stats['desconhecido'] ?? 0),
            'grupos' => (int)($stats['grupos'] ?? 0),
        ];
    }

    // ==========================================
    //  LOG DE COMANDOS
    // ==========================================

    public function logComando($servidorId, $usuarioId, $comando, $saida, $exitCode, $duracaoMs) {
        // Limitar tamanho da saída no log
        $saidaLimitada = mb_substr($saida, 0, 50000);

        $this->db->insert('ssh_comandos_log', [
            'servidor_id' => $servidorId,
            'usuario_id' => $usuarioId,
            'comando' => $comando,
            'saida' => $saidaLimitada,
            'exit_code' => $exitCode,
            'duracao_ms' => $duracaoMs,
        ]);
    }

    public function getHistoricoComandos($servidorId, $limite = 50) {
        return $this->db->fetchAll(
            "SELECT l.*, u.nome as usuario_nome
             FROM ssh_comandos_log l
             LEFT JOIN usuarios u ON l.usuario_id = u.id
             WHERE l.servidor_id = ?
             ORDER BY l.executado_em DESC
             LIMIT " . (int)$limite,
            [$servidorId]
        );
    }

    // ==========================================
    //  COMANDOS SALVOS
    // ==========================================

    public function getComandosSalvos() {
        return $this->db->fetchAll(
            "SELECT * FROM ssh_comandos_salvos ORDER BY categoria, nome"
        );
    }

    public function salvarComando($dados) {
        return $this->db->insert('ssh_comandos_salvos', [
            'nome' => trim($dados['nome']),
            'comando' => trim($dados['comando']),
            'descricao' => trim($dados['descricao'] ?? ''),
            'categoria' => trim($dados['categoria'] ?? 'geral'),
            'criado_por' => $dados['criado_por'] ?? null,
        ]);
    }

    public function excluirComando($id) {
        $this->db->delete('ssh_comandos_salvos', 'id = ?', [$id]);
    }

    // ==========================================
    //  GRUPOS
    // ==========================================

    public function getGrupos() {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT grupo FROM servidores_ssh WHERE grupo IS NOT NULL AND grupo != '' ORDER BY grupo"
        );
        return array_column($rows, 'grupo');
    }

    // ==========================================
    //  TESTAR TODOS
    // ==========================================

    public function testarTodos() {
        $servidores = $this->db->fetchAll("SELECT id, host, porta, nome FROM servidores_ssh WHERE ativo = 1");
        $resultados = [];

        foreach ($servidores as $s) {
            $start = microtime(true);
            $fp = @fsockopen($s['host'], $s['porta'], $errno, $errstr, 3);
            $latencia = round((microtime(true) - $start) * 1000);

            $online = $fp !== false;
            if ($fp) fclose($fp);

            $status = $online ? 'online' : 'offline';
            $this->db->update('servidores_ssh', [
                'ultimo_status' => $status,
                'ultimo_acesso' => $online ? date('Y-m-d H:i:s') : null,
            ], 'id = ?', [$s['id']]);

            $resultados[] = [
                'id' => $s['id'],
                'nome' => $s['nome'],
                'host' => $s['host'],
                'status' => $status,
                'latencia' => $online ? $latencia : null,
            ];
        }

        return $resultados;
    }
}
