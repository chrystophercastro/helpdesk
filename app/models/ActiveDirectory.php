<?php
/**
 * Model: ActiveDirectory
 * Gerenciamento de usuários, grupos e OUs do Active Directory via LDAP
 */
require_once __DIR__ . '/Database.php';

class ActiveDirectory {
    private $conn;
    private $config;
    private $bound = false;

    public function __construct() {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'ad_%'");
        $this->config = [];
        foreach ($rows as $r) {
            $this->config[$r['chave']] = $r['valor'];
        }
    }

    /**
     * Verificar se AD está configurado
     */
    public function isConfigured() {
        return !empty($this->config['ad_server']) && !empty($this->config['ad_base_dn']);
    }

    /**
     * Obter configuração
     */
    public function getConfig() {
        return [
            'server' => $this->config['ad_server'] ?? '',
            'porta' => $this->config['ad_porta'] ?? '389',
            'base_dn' => $this->config['ad_base_dn'] ?? '',
            'admin_user' => $this->config['ad_admin_user'] ?? '',
            'admin_pass' => !empty($this->config['ad_admin_pass']) ? '••••••••' : '',
            'dominio' => $this->config['ad_dominio'] ?? '',
            'ssl' => $this->config['ad_ssl'] ?? '0',
        ];
    }

    /**
     * Conectar ao AD
     */
    public function connect() {
        if (!extension_loaded('ldap')) {
            throw new Exception('Extensão LDAP não está instalada no PHP.');
        }

        if (!$this->isConfigured()) {
            throw new Exception('Active Directory não configurado. Vá em Configurações para definir os dados de conexão.');
        }

        $server = $this->config['ad_server'];
        $porta = (int)($this->config['ad_porta'] ?? 389);
        $ssl = ($this->config['ad_ssl'] ?? '0') === '1';

        $prefix = $ssl ? 'ldaps://' : 'ldap://';
        $uri = $prefix . $server;

        $this->conn = @ldap_connect($uri, $porta);
        if (!$this->conn) {
            throw new Exception('Não foi possível conectar ao servidor AD: ' . $server);
        }

        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->conn, LDAP_OPT_NETWORK_TIMEOUT, 10);

        // Bind com credenciais admin
        $adminUser = $this->config['ad_admin_user'] ?? '';
        $adminPass = $this->config['ad_admin_pass'] ?? '';

        if (empty($adminUser) || empty($adminPass)) {
            throw new Exception('Credenciais de administrador do AD não configuradas.');
        }

        $bind = @ldap_bind($this->conn, $adminUser, $adminPass);
        if (!$bind) {
            $err = ldap_error($this->conn);
            throw new Exception('Falha na autenticação com AD: ' . $err);
        }

        $this->bound = true;
        return true;
    }

    /**
     * Reconectar com StartTLS (necessário para alteração de senha sem LDAPS)
     */
    private function connectWithStartTLS() {
        // Fechar conexão atual
        if ($this->conn) {
            @ldap_unbind($this->conn);
        }

        $server = $this->config['ad_server'];
        $porta = (int)($this->config['ad_porta'] ?? 389);

        $this->conn = @ldap_connect('ldap://' . $server, $porta);
        if (!$this->conn) return false;

        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->conn, LDAP_OPT_NETWORK_TIMEOUT, 10);

        // Iniciar TLS
        if (!@ldap_start_tls($this->conn)) {
            return false;
        }

        $adminUser = $this->config['ad_admin_user'] ?? '';
        $adminPass = $this->config['ad_admin_pass'] ?? '';

        if (!@ldap_bind($this->conn, $adminUser, $adminPass)) {
            return false;
        }

        $this->bound = true;
        return true;
    }

    /**
     * Desconectar
     */
    public function disconnect() {
        if ($this->conn) {
            @ldap_unbind($this->conn);
            $this->conn = null;
            $this->bound = false;
        }
    }

    /**
     * Garantir conexão ativa
     */
    private function ensureConnected() {
        if (!$this->bound) {
            $this->connect();
        }
    }

    // ==========================================
    //  USUÁRIOS
    // ==========================================

    /**
     * Listar usuários do AD
     */
    public function listarUsuarios($ouDn = null, $busca = '') {
        $this->ensureConnected();

        $baseDn = $ouDn ?: $this->config['ad_base_dn'];
        $filter = '(&(objectClass=user)(objectCategory=person)';

        if (!empty($busca)) {
            $busca = ldap_escape($busca, '', LDAP_ESCAPE_FILTER);
            $filter .= "(|(cn=*{$busca}*)(sAMAccountName=*{$busca}*)(mail=*{$busca}*)(displayName=*{$busca}*))";
        }

        $filter .= ')';

        $attrs = [
            'cn', 'sAMAccountName', 'displayName', 'mail', 'title',
            'department', 'telephoneNumber', 'memberOf', 'distinguishedName',
            'userAccountControl', 'whenCreated', 'whenChanged', 'lastLogon',
            'description', 'physicalDeliveryOfficeName', 'company'
        ];

        $result = @ldap_search($this->conn, $baseDn, $filter, $attrs, 0, 500);
        if (!$result) {
            throw new Exception('Erro na busca LDAP: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $result);
        $usuarios = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            $uac = (int)($entry['useraccountcontrol'][0] ?? 0);

            $grupos = [];
            if (isset($entry['memberof'])) {
                for ($j = 0; $j < $entry['memberof']['count']; $j++) {
                    // Extrair CN do grupo
                    if (preg_match('/^CN=([^,]+)/i', $entry['memberof'][$j], $m)) {
                        $grupos[] = $m[1];
                    }
                }
            }

            $usuarios[] = [
                'dn' => $entry['distinguishedname'][0] ?? '',
                'cn' => $entry['cn'][0] ?? '',
                'login' => $entry['samaccountname'][0] ?? '',
                'nome' => $entry['displayname'][0] ?? ($entry['cn'][0] ?? ''),
                'email' => $entry['mail'][0] ?? '',
                'cargo' => $entry['title'][0] ?? '',
                'departamento' => $entry['department'][0] ?? '',
                'telefone' => $entry['telephonenumber'][0] ?? '',
                'escritorio' => $entry['physicaldeliveryofficename'][0] ?? '',
                'empresa' => $entry['company'][0] ?? '',
                'descricao' => $entry['description'][0] ?? '',
                'grupos' => $grupos,
                'ativo' => !($uac & 2), // bit 2 = ACCOUNTDISABLE
                'bloqueado' => (bool)($uac & 16), // bit 4 = LOCKOUT
                'criado_em' => $this->ldapDateToPhp($entry['whencreated'][0] ?? ''),
                'atualizado_em' => $this->ldapDateToPhp($entry['whenchanged'][0] ?? ''),
            ];
        }

        // Ordenar por nome
        usort($usuarios, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));

        return $usuarios;
    }

    /**
     * Buscar usuário por DN
     */
    public function getUsuario($dn) {
        $this->ensureConnected();

        $attrs = [
            'cn', 'sAMAccountName', 'displayName', 'mail', 'title',
            'department', 'telephoneNumber', 'memberOf', 'distinguishedName',
            'userAccountControl', 'whenCreated', 'whenChanged', 'description',
            'physicalDeliveryOfficeName', 'company', 'givenName', 'sn'
        ];

        $result = @ldap_read($this->conn, $dn, '(objectClass=*)', $attrs);
        if (!$result) {
            throw new Exception('Usuário não encontrado: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $result);
        if ($entries['count'] === 0) return null;

        $entry = $entries[0];
        $uac = (int)($entry['useraccountcontrol'][0] ?? 0);

        $grupos = [];
        if (isset($entry['memberof'])) {
            for ($j = 0; $j < $entry['memberof']['count']; $j++) {
                $grupos[] = $entry['memberof'][$j];
            }
        }

        return [
            'dn' => $entry['distinguishedname'][0] ?? '',
            'cn' => $entry['cn'][0] ?? '',
            'login' => $entry['samaccountname'][0] ?? '',
            'nome' => $entry['displayname'][0] ?? '',
            'primeiro_nome' => $entry['givenname'][0] ?? '',
            'sobrenome' => $entry['sn'][0] ?? '',
            'email' => $entry['mail'][0] ?? '',
            'cargo' => $entry['title'][0] ?? '',
            'departamento' => $entry['department'][0] ?? '',
            'telefone' => $entry['telephonenumber'][0] ?? '',
            'escritorio' => $entry['physicaldeliveryofficename'][0] ?? '',
            'empresa' => $entry['company'][0] ?? '',
            'descricao' => $entry['description'][0] ?? '',
            'grupos' => $grupos,
            'ativo' => !($uac & 2),
        ];
    }

    /**
     * Criar usuário no AD
     */
    public function criarUsuario($dados) {
        $this->ensureConnected();

        $dominio = $this->config['ad_dominio'] ?? '';
        $ou = $dados['ou_dn'] ?? $this->config['ad_base_dn'];
        $login = $dados['login'];
        $nome = $dados['primeiro_nome'] . ' ' . $dados['sobrenome'];
        $dn = "CN={$nome},{$ou}";

        // UPN (User Principal Name)
        $upn = $login . '@' . $dominio;

        $entry = [
            'cn' => $nome,
            'sAMAccountName' => $login,
            'userPrincipalName' => $upn,
            'givenName' => $dados['primeiro_nome'],
            'sn' => $dados['sobrenome'],
            'displayName' => $nome,
            'objectClass' => ['top', 'person', 'organizationalPerson', 'user'],
            'userAccountControl' => '544', // NORMAL_ACCOUNT + PASSWD_NOTREQD temporariamente
        ];

        if (!empty($dados['email'])) {
            $entry['mail'] = $dados['email'];
        }
        if (!empty($dados['cargo'])) {
            $entry['title'] = $dados['cargo'];
        }
        if (!empty($dados['departamento'])) {
            $entry['department'] = $dados['departamento'];
        }
        if (!empty($dados['telefone'])) {
            $entry['telephoneNumber'] = $dados['telefone'];
        }
        if (!empty($dados['descricao'])) {
            $entry['description'] = $dados['descricao'];
        }
        if (!empty($dados['empresa'])) {
            $entry['company'] = $dados['empresa'];
        }

        $result = @ldap_add($this->conn, $dn, $entry);
        if (!$result) {
            throw new Exception('Erro ao criar usuário: ' . ldap_error($this->conn));
        }

        // Definir senha
        if (!empty($dados['senha'])) {
            $this->definirSenha($dn, $dados['senha']);
        }

        // Habilitar conta (trocar UAC para NORMAL_ACCOUNT = 512)
        $habilitarMods = ['userAccountControl' => ['512']];
        if (!empty($dados['trocar_senha_proximo_login'])) {
            $habilitarMods['pwdLastSet'] = ['0'];
        }
        @ldap_mod_replace($this->conn, $dn, $habilitarMods);

        return $dn;
    }

    /**
     * Definir/Resetar senha do usuário
     * Tenta múltiplos métodos: LDAP direto → StartTLS → PowerShell
     */
    public function definirSenha($dn, $novaSenha) {
        $this->ensureConnected();

        // AD exige senha em formato UTF-16LE entre aspas
        $newPassword = iconv('UTF-8', 'UTF-16LE', '"' . $novaSenha . '"');
        $entry = ['unicodePwd' => $newPassword];

        // Método 1: Tentativa direta (funciona se LDAPS ou StartTLS já ativo)
        $result = @ldap_mod_replace($this->conn, $dn, $entry);
        if ($result) return true;

        $ldapErr = ldap_error($this->conn);

        // Método 2: Tentar com StartTLS (TLS sobre porta 389)
        $ssl = ($this->config['ad_ssl'] ?? '0') === '1';
        if (!$ssl) {
            if ($this->connectWithStartTLS()) {
                $result = @ldap_mod_replace($this->conn, $dn, $entry);
                if ($result) return true;
            }
            // Reconectar normalmente para não quebrar outras operações
            $this->bound = false;
            $this->connect();
        }

        // Método 3: PowerShell (funciona em servidores Windows com RSAT-AD)
        $psResult = $this->definirSenhaPowerShell($dn, $novaSenha);
        if ($psResult) return true;

        // Se nenhum método funcionou
        throw new Exception('Erro ao definir senha. Métodos tentados: LDAP, StartTLS e PowerShell. Erro LDAP: ' . $ldapErr . '. Verifique se o servidor possui certificado SSL/TLS configurado ou módulo AD do PowerShell instalado (RSAT).');
    }

    /**
     * Definir senha via PowerShell (fallback para ambientes sem LDAPS)
     * Tenta ADSI COM (não precisa de RSAT) e Set-ADAccountPassword
     */
    private function definirSenhaPowerShell($dn, $novaSenha) {
        // Verificar se estamos no Windows
        if (PHP_OS_FAMILY !== 'Windows') return false;

        // Escapar caracteres especiais para PowerShell
        $senhaEsc = str_replace(["'"], ["''"], $novaSenha);
        $dnEsc = str_replace(["'"], ["''"], $dn);
        $server = $this->config['ad_server'] ?? '';
        $adminUser = $this->config['ad_admin_user'] ?? '';
        $adminPassEsc = str_replace(["'"], ["''"], $this->config['ad_admin_pass'] ?? '');

        // Método via ADSI (funciona sem RSAT em máquinas Windows no domínio)
        // SetPassword tenta Kerberos, depois SSL, depois outros métodos seguros
        $ps = <<<PS
\$ErrorActionPreference = 'Stop'
try {
    # Método 1: ADSI COM com SetPassword (tenta Kerberos > SSL automaticamente)
    \$user = New-Object DirectoryServices.DirectoryEntry("LDAP://{$server}/{$dnEsc}", '{$adminUser}', '{$adminPassEsc}')
    \$user.Invoke("SetPassword", @('{$senhaEsc}'))
    \$user.CommitChanges()
    Write-Output 'OK'
} catch {
    try {
        # Método 2: RSAT Set-ADAccountPassword
        Import-Module ActiveDirectory -ErrorAction Stop
        \$secPass = ConvertTo-SecureString '{$senhaEsc}' -AsPlainText -Force
        \$cred = New-Object System.Management.Automation.PSCredential('{$adminUser}', (ConvertTo-SecureString '{$adminPassEsc}' -AsPlainText -Force))
        Set-ADAccountPassword -Identity '{$dnEsc}' -NewPassword \$secPass -Reset -Server '{$server}' -Credential \$cred
        Write-Output 'OK'
    } catch {
        Write-Output "ERRO:\$_"
    }
}
PS;

        $tempFile = sys_get_temp_dir() . '\\ad_reset_' . uniqid() . '.ps1';
        file_put_contents($tempFile, $ps);

        $output = shell_exec('powershell -ExecutionPolicy Bypass -NoProfile -File "' . $tempFile . '" 2>&1');
        @unlink($tempFile);

        if ($output !== null && trim($output) === 'OK') {
            return true;
        }

        return false;
    }

    /**
     * Resetar senha e forçar troca no próximo login
     */
    public function resetarSenha($dn, $novaSenha, $forcarTroca = true) {
        $this->definirSenha($dn, $novaSenha);

        if ($forcarTroca) {
            @ldap_mod_replace($this->conn, $dn, ['pwdLastSet' => ['0']]);
        }

        return true;
    }

    /**
     * Habilitar/Desabilitar conta
     */
    public function toggleConta($dn, $habilitar = true) {
        $this->ensureConnected();

        $uac = $habilitar ? '512' : '514'; // 512=normal, 514=disabled

        $result = @ldap_mod_replace($this->conn, $dn, ['userAccountControl' => [$uac]]);
        if (!$result) {
            throw new Exception('Erro ao alterar conta: ' . ldap_error($this->conn));
        }

        return true;
    }

    /**
     * Desbloquear conta
     */
    public function desbloquearConta($dn) {
        $this->ensureConnected();

        $result = @ldap_mod_replace($this->conn, $dn, ['lockoutTime' => ['0']]);
        if (!$result) {
            throw new Exception('Erro ao desbloquear conta: ' . ldap_error($this->conn));
        }

        return true;
    }

    // ==========================================
    //  GRUPOS
    // ==========================================

    /**
     * Listar grupos do AD
     */
    public function listarGrupos($busca = '') {
        $this->ensureConnected();

        $baseDn = $this->config['ad_base_dn'];
        $filter = '(objectClass=group)';

        if (!empty($busca)) {
            $busca = ldap_escape($busca, '', LDAP_ESCAPE_FILTER);
            $filter = "(&(objectClass=group)(|(cn=*{$busca}*)(description=*{$busca}*)))";
        }

        $attrs = ['cn', 'distinguishedName', 'description', 'member', 'groupType'];

        $result = @ldap_search($this->conn, $baseDn, $filter, $attrs, 0, 500);
        if (!$result) {
            throw new Exception('Erro na busca de grupos: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $result);
        $grupos = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            $membros = (int)($entry['member']['count'] ?? 0);

            $grupos[] = [
                'dn' => $entry['distinguishedname'][0] ?? '',
                'nome' => $entry['cn'][0] ?? '',
                'descricao' => $entry['description'][0] ?? '',
                'membros_count' => $membros,
                'tipo' => $this->getGroupType((int)($entry['grouptype'][0] ?? 0)),
            ];
        }

        usort($grupos, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));

        return $grupos;
    }

    /**
     * Listar membros de um grupo
     */
    public function listarMembrosGrupo($grupoDn) {
        $this->ensureConnected();

        $result = @ldap_read($this->conn, $grupoDn, '(objectClass=*)', ['member']);
        if (!$result) {
            throw new Exception('Grupo não encontrado: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $result);
        $membros = [];

        if (isset($entries[0]['member'])) {
            for ($i = 0; $i < $entries[0]['member']['count']; $i++) {
                $memberDn = $entries[0]['member'][$i];
                if (preg_match('/^CN=([^,]+)/i', $memberDn, $m)) {
                    $membros[] = [
                        'dn' => $memberDn,
                        'nome' => $m[1],
                    ];
                }
            }
        }

        usort($membros, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));
        return $membros;
    }

    /**
     * Adicionar usuário a um grupo
     */
    public function adicionarAoGrupo($userDn, $grupoDn) {
        $this->ensureConnected();

        $result = @ldap_mod_add($this->conn, $grupoDn, ['member' => [$userDn]]);
        if (!$result) {
            $err = ldap_error($this->conn);
            if (stripos($err, 'Already exists') !== false || ldap_errno($this->conn) === 68) {
                throw new Exception('Usuário já é membro deste grupo.');
            }
            throw new Exception('Erro ao adicionar ao grupo: ' . $err);
        }

        return true;
    }

    /**
     * Remover usuário de um grupo
     */
    public function removerDoGrupo($userDn, $grupoDn) {
        $this->ensureConnected();

        $result = @ldap_mod_del($this->conn, $grupoDn, ['member' => [$userDn]]);
        if (!$result) {
            throw new Exception('Erro ao remover do grupo: ' . ldap_error($this->conn));
        }

        return true;
    }

    // ==========================================
    //  OUs (Unidades Organizacionais)
    // ==========================================

    /**
     * Listar OUs (árvore)
     */
    public function listarOUs($parentDn = null) {
        $this->ensureConnected();

        $baseDn = $parentDn ?: $this->config['ad_base_dn'];
        $filter = '(objectClass=organizationalUnit)';
        $attrs = ['ou', 'distinguishedName', 'description', 'whenCreated'];

        $result = @ldap_search($this->conn, $baseDn, $filter, $attrs, 0, 500);
        if (!$result) {
            throw new Exception('Erro na busca de OUs: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $result);
        $ous = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            $dn = $entry['distinguishedname'][0] ?? '';

            // Contar quantos objetos tem na OU
            $count = 0;
            $countResult = @ldap_list($this->conn, $dn, '(objectClass=*)', ['dn']);
            if ($countResult) {
                $count = ldap_count_entries($this->conn, $countResult);
            }

            $ous[] = [
                'dn' => $dn,
                'nome' => $entry['ou'][0] ?? '',
                'descricao' => $entry['description'][0] ?? '',
                'objetos' => $count,
                'nivel' => substr_count($dn, ',') - substr_count($this->config['ad_base_dn'], ','),
            ];
        }

        usort($ous, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));

        return $ous;
    }

    // ==========================================
    //  HELPERS
    // ==========================================

    /**
     * Converter data LDAP para formato PHP
     */
    private function ldapDateToPhp($ldapDate) {
        if (empty($ldapDate)) return null;
        // Format: 20240115123045.0Z
        try {
            $dt = DateTime::createFromFormat('YmdHis.0\Z', $ldapDate, new DateTimeZone('UTC'));
            if ($dt) {
                $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                return $dt->format('Y-m-d H:i:s');
            }
        } catch (Exception $e) {}
        return null;
    }

    /**
     * Determinar tipo do grupo
     */
    private function getGroupType($type) {
        if ($type & 0x80000000) {
            return 'Segurança';
        }
        return 'Distribuição';
    }

    /**
     * Testar conexão
     */
    public function testarConexao() {
        try {
            $this->connect();
            $this->disconnect();
            return ['success' => true, 'message' => 'Conexão com Active Directory estabelecida com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Gerar senha aleatória
     */
    public static function gerarSenha($tamanho = 14) {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $special = '!@#$%&*()-_=+';

        // Garantir ao menos 1 de cada tipo
        $senha = $upper[random_int(0, strlen($upper) - 1)]
               . $lower[random_int(0, strlen($lower) - 1)]
               . $digits[random_int(0, strlen($digits) - 1)]
               . $special[random_int(0, strlen($special) - 1)];

        $all = $upper . $lower . $digits . $special;
        for ($i = 4; $i < $tamanho; $i++) {
            $senha .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($senha);
    }

    public function __destruct() {
        $this->disconnect();
    }
}
