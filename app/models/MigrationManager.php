<?php
/**
 * MigrationManager - Gerenciador de Migrations do Sistema
 * Controla execução, histórico e status de todas as migrations
 */
require_once __DIR__ . '/Database.php';

class MigrationManager
{
    private $db;
    private $migrationsPath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = BASE_PATH . '/migrations';
        $this->ensureTable();
    }

    /* ─── Bootstrap ─── */
    private function ensureTable()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT UNSIGNED NOT NULL DEFAULT 1,
            executado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            duracao_ms INT UNSIGNED DEFAULT 0,
            status ENUM('sucesso','erro','skip') DEFAULT 'sucesso',
            output TEXT NULL,
            erro TEXT NULL,
            UNIQUE KEY idx_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* ─── Scan ─── */
    public function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) return [];

        $files = glob($this->migrationsPath . '/*.php');
        $migrations = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, ['index', 'run_all', '.gitkeep'])) continue;
            $migrations[] = $name;
        }
        sort($migrations);
        return $migrations;
    }

    public function getExecutedNames(): array
    {
        $rows = $this->db->fetchAll("SELECT migration FROM migrations WHERE status = 'sucesso'");
        return array_column($rows, 'migration');
    }

    public function getPendingMigrations(): array
    {
        return array_values(array_diff($this->getMigrationFiles(), $this->getExecutedNames()));
    }

    /* ─── Execute ─── */
    public function runMigration(string $name): array
    {
        $file = $this->migrationsPath . '/' . $name . '.php';
        if (!file_exists($file)) {
            return ['status' => 'erro', 'erro' => 'Arquivo não encontrado: ' . $name];
        }

        // Já executada?
        $existing = $this->db->fetch(
            "SELECT id FROM migrations WHERE migration = ? AND status = 'sucesso'",
            [$name]
        );
        if ($existing) {
            return ['status' => 'skip', 'mensagem' => 'Migration já foi executada com sucesso'];
        }

        // Remover registro de erro anterior se existir
        $this->db->query("DELETE FROM migrations WHERE migration = ? AND status = 'erro'", [$name]);

        $batch = (int)($this->db->fetch("SELECT COALESCE(MAX(batch),0)+1 as b FROM migrations")['b'] ?? 1);
        $start = microtime(true);
        ob_start();

        try {
            require $file;
            $output   = ob_get_clean();
            $duration = (int)round((microtime(true) - $start) * 1000);

            $this->db->insert('migrations', [
                'migration'  => $name,
                'batch'      => $batch,
                'duracao_ms' => $duration,
                'status'     => 'sucesso',
                'output'     => $output ?: null
            ]);

            return ['status' => 'sucesso', 'output' => $output, 'duracao_ms' => $duration];
        } catch (\Throwable $e) {
            $output   = ob_get_clean();
            $duration = (int)round((microtime(true) - $start) * 1000);

            $this->db->insert('migrations', [
                'migration'  => $name,
                'batch'      => $batch,
                'duracao_ms' => $duration,
                'status'     => 'erro',
                'output'     => $output ?: null,
                'erro'       => $e->getMessage()
            ]);

            return ['status' => 'erro', 'output' => $output, 'erro' => $e->getMessage(), 'duracao_ms' => $duration];
        }
    }

    public function runAllPending(): array
    {
        $pending = $this->getPendingMigrations();
        $results = [];

        foreach ($pending as $name) {
            $r = $this->runMigration($name);
            $r['migration'] = $name;
            $results[] = $r;
            if ($r['status'] === 'erro') break; // para na primeira falha
        }
        return $results;
    }

    /* ─── Marcar como executada (setup inicial) ─── */
    public function markAllAsExecuted(): int
    {
        $all      = $this->getMigrationFiles();
        $executed = $this->getExecutedNames();
        $batch    = (int)($this->db->fetch("SELECT COALESCE(MAX(batch),0)+1 as b FROM migrations")['b'] ?? 1);
        $count    = 0;

        foreach ($all as $name) {
            if (!in_array($name, $executed)) {
                $this->db->insert('migrations', [
                    'migration'  => $name,
                    'batch'      => $batch,
                    'status'     => 'sucesso',
                    'output'     => 'Marcada manualmente como executada',
                    'duracao_ms' => 0
                ]);
                $count++;
            }
        }
        return $count;
    }

    /* ─── Resetar ─── */
    public function resetMigration(string $name): bool
    {
        $this->db->query("DELETE FROM migrations WHERE migration = ?", [$name]);
        return true;
    }

    /* ─── Overview ─── */
    public function getOverview(): array
    {
        $allFiles    = $this->getMigrationFiles();
        $executedArr = $this->getExecutedNames();
        $pending     = array_values(array_diff($allFiles, $executedArr));

        $executedData = $this->db->fetchAll("SELECT * FROM migrations ORDER BY executado_em DESC");
        $map = [];
        foreach ($executedData as $row) {
            $map[$row['migration']] = $row;
        }

        $migrations = [];
        foreach ($allFiles as $name) {
            $m = [
                'nome'      => $name,
                'arquivo'   => $name . '.php',
                'status'    => in_array($name, $executedArr) ? 'executada' : 'pendente',
                'descricao' => $this->getMigrationDescription($name),
                'tabelas'   => $this->getMigrationTables($name)
            ];
            if (isset($map[$name])) {
                $m['executado_em'] = $map[$name]['executado_em'];
                $m['duracao_ms']   = $map[$name]['duracao_ms'];
                $m['batch']        = $map[$name]['batch'];
                $m['output']       = $map[$name]['output'];
            }
            $migrations[] = $m;
        }

        return [
            'total'           => count($allFiles),
            'executadas'      => count($executedArr),
            'pendentes'       => count($pending),
            'lista_pendentes' => $pending,
            'migrations'      => $migrations,
            'ultima_execucao' => !empty($executedData) ? $executedData[0]['executado_em'] : null
        ];
    }

    /* ─── Histórico ─── */
    public function getHistory(int $limit = 100): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM migrations ORDER BY executado_em DESC LIMIT ?",
            [$limit]
        );
    }

    /* ─── System Info ─── */
    public function getSystemInfo(): array
    {
        $mysqlVer = $this->db->fetch("SELECT VERSION() as v");
        $dbSize   = $this->db->fetch("
            SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) as size_mb,
                   COUNT(*) as total_tables
            FROM information_schema.tables WHERE table_schema = DATABASE()
        ");
        $tables = $this->db->fetchAll("
            SELECT table_name as tabela,
                   ROUND((data_length+index_length)/1024,2) as tamanho_kb,
                   table_rows as registros,
                   engine,
                   create_time as criado_em
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            ORDER BY (data_length+index_length) DESC
        ");

        $diskFree  = @disk_free_space(BASE_PATH) ?: 0;
        $diskTotal = @disk_total_space(BASE_PATH) ?: 1;

        return [
            'php' => [
                'versao'             => PHP_VERSION,
                'memory_limit'       => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max'         => ini_get('upload_max_filesize'),
                'post_max'           => ini_get('post_max_size'),
                'timezone'           => date_default_timezone_get(),
                'extensoes'          => get_loaded_extensions()
            ],
            'mysql' => [
                'versao'        => $mysqlVer['v'] ?? 'N/A',
                'database'      => $this->db->fetch("SELECT DATABASE() as d")['d'] ?? 'N/A',
                'tamanho_mb'    => $dbSize['size_mb'] ?? 0,
                'total_tabelas' => $dbSize['total_tables'] ?? 0,
                'tabelas'       => $tables
            ],
            'servidor' => [
                'software'       => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'os'             => PHP_OS,
                'hostname'       => gethostname(),
                'disco_livre_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
                'disco_total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
                'disco_uso_pct'  => round(($diskTotal - $diskFree) / $diskTotal * 100, 1)
            ],
            'helpdesk' => [
                'versao'    => $this->getSystemVersion(),
                'base_path' => BASE_PATH,
                'base_url'  => BASE_URL,
                'ambiente'  => $this->detectEnvironment()
            ]
        ];
    }

    /* ─── Health Checks ─── */
    public function getHealthChecks(): array
    {
        $checks = [];

        // PHP Version
        $checks[] = [
            'nome' => 'PHP Version', 'icone' => 'fab fa-php',
            'status' => version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'warning',
            'valor' => PHP_VERSION, 'requerido' => '>= 8.0'
        ];

        // Extensões obrigatórias
        foreach (['pdo','pdo_mysql','json','mbstring','curl','openssl'] as $ext) {
            $checks[] = [
                'nome' => "Ext: $ext", 'icone' => 'fas fa-puzzle-piece',
                'status' => extension_loaded($ext) ? 'ok' : 'erro',
                'valor' => extension_loaded($ext) ? 'Instalada' : 'Ausente',
                'requerido' => 'Obrigatória'
            ];
        }

        // MySQL
        try {
            $this->db->fetch("SELECT 1");
            $checks[] = ['nome'=>'MySQL','icone'=>'fas fa-database','status'=>'ok','valor'=>'Conectado','requerido'=>'Obrigatório'];
        } catch (\Exception $e) {
            $checks[] = ['nome'=>'MySQL','icone'=>'fas fa-database','status'=>'erro','valor'=>'Falha','requerido'=>'Obrigatório'];
        }

        // Uploads
        $up = BASE_PATH . '/uploads';
        $checks[] = [
            'nome'=>'Dir Uploads','icone'=>'fas fa-folder',
            'status'=> is_writable($up) ? 'ok' : 'warning',
            'valor'=> is_writable($up) ? 'Gravável' : 'Sem permissão',
            'requerido'=>'Gravável'
        ];

        // Storage
        $st = BASE_PATH . '/storage';
        $checks[] = [
            'nome'=>'Dir Storage','icone'=>'fas fa-hdd',
            'status'=> (is_dir($st) && is_writable($st)) ? 'ok' : 'warning',
            'valor'=> is_dir($st) ? (is_writable($st)?'Gravável':'Sem permissão') : 'Não existe',
            'requerido'=>'Gravável'
        ];

        // Disco
        $freeGB = @disk_free_space(BASE_PATH) / 1024/1024/1024;
        $checks[] = [
            'nome'=>'Disco','icone'=>'fas fa-hdd',
            'status'=> $freeGB > 1 ? 'ok' : ($freeGB > .5 ? 'warning' : 'erro'),
            'valor'=> round($freeGB,2).' GB livre',
            'requerido'=>'> 1 GB'
        ];

        // Migrations pendentes
        $pend = count($this->getPendingMigrations());
        $checks[] = [
            'nome'=>'Migrations','icone'=>'fas fa-code-branch',
            'status'=> $pend === 0 ? 'ok' : 'warning',
            'valor'=> $pend === 0 ? 'Atualizado' : "$pend pendente(s)",
            'requerido'=>'0 pendentes'
        ];

        // Memory
        $mem = (int) ini_get('memory_limit');
        $checks[] = [
            'nome'=>'Memória','icone'=>'fas fa-memory',
            'status'=> $mem >= 128 ? 'ok' : 'warning',
            'valor'=> ini_get('memory_limit'),
            'requerido'=>'>= 128M'
        ];

        return $checks;
    }

    /* ─── Helpers ─── */
    public function getSystemVersion(): string
    {
        $vf = BASE_PATH . '/VERSION';
        if (file_exists($vf)) return trim(file_get_contents($vf));
        return '2.0.' . count($this->getExecutedNames());
    }

    private function detectEnvironment(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
            return 'Desenvolvimento';
        }
        return 'Produção';
    }

    private function getMigrationDescription(string $name): string
    {
        $file = $this->migrationsPath . '/' . $name . '.php';
        if (!file_exists($file)) return $this->humanize($name);

        $content = file_get_contents($file);
        if (preg_match('/\*\s*Migration:\s*(.+)/i', $content, $m)) return trim($m[1]);
        if (preg_match('/\/\*\*?\s*\n\s*\*\s*(.+)/m', $content, $m)) return trim($m[1]);
        return $this->humanize($name);
    }

    private function getMigrationTables(string $name): array
    {
        $file = $this->migrationsPath . '/' . $name . '.php';
        if (!file_exists($file)) return [];

        $content = file_get_contents($file);
        $tables = [];

        // CREATE TABLE
        preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`]?(\w+)[`]?/i', $content, $m);
        foreach ($m[1] as $t) $tables[] = $t;

        // ALTER TABLE
        preg_match_all('/ALTER\s+TABLE\s+[`]?(\w+)[`]?/i', $content, $m);
        foreach ($m[1] as $t) {
            if (!in_array($t, $tables)) $tables[] = $t;
        }

        return $tables;
    }

    private function humanize(string $name): string
    {
        $n = str_replace(['create_', '_tables', '_table'], ['Criar ', '', ''], $name);
        return ucfirst(str_replace('_', ' ', $n));
    }
}
