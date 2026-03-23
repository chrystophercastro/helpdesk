<?php
/**
 * Model: IADev - Motor de desenvolvimento assistido por IA
 * 
 * Gerencia projetos de desenvolvimento (internos e externos),
 * arquivos gerados pela IA, workflow de aprovação para Oracle X,
 * e leitura de contexto do codebase.
 */

class IADev {
    private $db;
    private $userId;
    private $basePath;
    private $storagePath;

    /**
     * Caminhos protegidos - módulos originais do sistema que NÃO podem ser
     * sobrescritos/deletados pelo Dev Mode. Isso impede que a IA corrompa
     * funcionalidades existentes do Oracle X.
     * 
     * Usa glob patterns. Tanto 'criar' sobre um existente quanto 'modificar'/'deletar'
     * são bloqueados se o caminho bater com algum padrão protegido.
     */
    private static $protectedPaths = [
        // === Core do sistema ===
        'index.php',
        'config/*',
        'config/app.php',
        'config/database.php',

        // === Layout / Template base ===
        'app/views/layouts/*',

        // === Módulos originais - Views ===
        'app/views/dashboard/*',
        'app/views/chamados/*',
        'app/views/projetos/*',
        'app/views/inventario/*',
        'app/views/rede/*',
        'app/views/compras/*',
        'app/views/conhecimento/*',
        'app/views/relatorios/*',
        'app/views/admin/*',
        'app/views/perfil/*',
        'app/views/login/*',
        'app/views/usuarios/*',
        'app/views/ia/index.php',         // Chat IA principal
        'app/views/chatbot/*',
        'app/views/portal/*',
        'app/views/posts/*',
        'app/views/ssh/*',

        // === Models originais ===
        'app/models/Chamado.php',
        'app/models/Projeto.php',
        'app/models/Inventario.php',
        'app/models/Rede.php',
        'app/models/Compra.php',
        'app/models/Conhecimento.php',
        'app/models/Usuario.php',
        'app/models/IA.php',
        'app/models/IAActions.php',
        'app/models/IADev.php',
        'app/models/Database.php',
        'app/models/Notificacao.php',
        'app/models/Chat.php',
        'app/models/Chatbot.php',
        'app/models/SSH.php',
        'app/models/Financeiro.php',
        'app/models/RH.php',
        'app/models/Post.php',
        'app/models/Auth.php',

        // === APIs originais ===
        'api/ia.php',
        'api/ia-dev.php',
        'api/chamados.php',
        'api/projetos.php',
        'api/inventario.php',
        'api/rede.php',
        'api/compras.php',
        'api/conhecimento.php',
        'api/admin.php',
        'api/ssh.php',
        'api/chat.php',
        'api/chatbot.php',
        'api/notificacoes.php',
        'api/usuarios.php',
        'api/relatorios.php',
        'api/portal.php',
        'api/posts.php',
        'api/financeiro.php',
        'api/rh.php',
        'api/upload.php',

        // === Assets originais ===
        'assets/css/style.css',
        'assets/css/dark-theme.css',
        'assets/css/ia.css',
        'assets/css/ia-dev.css',
        'assets/js/app.js',
        'assets/js/ia-dev.js',

        // === Storage / Segurança ===
        'storage/*',
        '.htaccess',
        'composer.json',
        'composer.lock',
    ];

    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId;
        $this->basePath = realpath(__DIR__ . '/../../');
        $this->storagePath = $this->basePath . '/storage/ia_dev';
    }

    /**
     * Verifica se um caminho está protegido contra modificação
     * Retorna true se o caminho NÃO pode ser alterado
     */
    public static function isProtectedPath($caminho) {
        $caminho = ltrim(str_replace('\\', '/', $caminho), '/');
        
        foreach (self::$protectedPaths as $pattern) {
            $pattern = str_replace('\\', '/', $pattern);
            
            // Wildcard no final: path/* bloqueia tudo dentro da pasta
            if (str_ends_with($pattern, '/*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($caminho, $prefix . '/') || $caminho === $prefix) {
                    return true;
                }
            }
            // Match exato
            elseif ($caminho === $pattern) {
                return true;
            }
        }
        return false;
    }

    /**
     * Valida um array de arquivos e retorna os bloqueados
     * @return array ['allowed' => [...], 'blocked' => [...]]
     */
    public static function validatePaths($arquivos) {
        $allowed = [];
        $blocked = [];
        
        foreach ($arquivos as $arq) {
            $caminho = $arq['caminho'] ?? '';
            if (self::isProtectedPath($caminho)) {
                $blocked[] = $caminho;
            } else {
                $allowed[] = $arq;
            }
        }
        
        return ['allowed' => $allowed, 'blocked' => $blocked];
    }

    /**
     * Retorna a lista de padrões protegidos (para exibir no frontend)
     */
    public static function getProtectedPatterns() {
        return self::$protectedPaths;
    }

    // ==========================================
    //  PROJETOS
    // ==========================================

    public function listarProjetos($userId = null) {
        $where = "p.status = 'ativo'";
        $params = [];
        if ($userId) {
            // Internos são visíveis para todos, externos apenas do próprio usuário
            $where .= " AND (p.tipo = 'interno' OR p.usuario_id = ?)";
            $params[] = $userId;
        }
        return $this->db->fetchAll(
            "SELECT p.*, u.nome as criador_nome,
                    (SELECT COUNT(*) FROM ia_dev_arquivos a WHERE a.projeto_id = p.id) as total_arquivos,
                    (SELECT COUNT(*) FROM ia_dev_alteracoes alt WHERE alt.projeto_id = p.id AND alt.status = 'pendente') as pendentes
             FROM ia_dev_projetos p 
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             WHERE {$where} ORDER BY p.tipo ASC, p.atualizado_em DESC", $params
        );
    }

    public function findProjeto($id) {
        return $this->db->fetch(
            "SELECT p.*, u.nome as criador_nome FROM ia_dev_projetos p 
             LEFT JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?", [$id]
        );
    }

    public function criarProjeto($dados) {
        $id = $this->db->insert('ia_dev_projetos', [
            'usuario_id' => $this->userId,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'tipo' => $dados['tipo'] ?? 'externo',
            'stack' => $dados['stack'] ?? null,
            'caminho' => $dados['caminho'] ?? null,
        ]);

        // Criar pasta para projetos externos
        if (($dados['tipo'] ?? 'externo') === 'externo') {
            $dir = $this->storagePath . '/projects/' . $id;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        return $id;
    }

    public function getProjetoInterno() {
        return $this->db->fetch("SELECT * FROM ia_dev_projetos WHERE tipo = 'interno' LIMIT 1");
    }

    // ==========================================
    //  ARQUIVOS
    // ==========================================

    public function listarArquivos($projetoId, $status = null) {
        $where = "a.projeto_id = ?";
        $params = [$projetoId];
        if ($status) {
            $where .= " AND a.status = ?";
            $params[] = $status;
        }
        return $this->db->fetchAll(
            "SELECT a.*, c.titulo as conversa_titulo 
             FROM ia_dev_arquivos a 
             LEFT JOIN ia_conversas c ON a.conversa_id = c.id
             WHERE {$where} ORDER BY a.criado_em DESC", $params
        );
    }

    public function findArquivo($id) {
        return $this->db->fetch("SELECT * FROM ia_dev_arquivos WHERE id = ?", [$id]);
    }

    /**
     * Salvar arquivo gerado pela IA
     */
    public function salvarArquivo($projetoId, $dados, $forceOverride = false) {
        $caminho = $dados['caminho'];
        $conteudo = $dados['conteudo'];
        $linguagem = $dados['linguagem'] ?? $this->detectarLinguagem($caminho);
        $acao = $dados['acao'] ?? 'criar';
        $conversaId = $dados['conversa_id'] ?? null;

        $projeto = $this->findProjeto($projetoId);
        if (!$projeto) throw new \Exception('Projeto não encontrado');

        // === PROTEÇÃO: alertar sobre escrita em módulos protegidos (projetos internos) ===
        // Admin pode forçar override com confirmação explícita
        if ($projeto['tipo'] === 'interno' && self::isProtectedPath($caminho) && !$forceOverride) {
            throw new \Exception(
                "⛔ Caminho protegido: '{$caminho}' é um módulo original do Oracle X. " .
                "Use 'Forçar Override' (admin) para permitir a alteração."
            );
        }

        // Para modificações em projeto interno, guardar conteúdo original
        // Também guardar original quando admin faz override em arquivo protegido existente
        $conteudoOriginal = null;
        if ($projeto['tipo'] === 'interno' && ($acao === 'modificar' || $forceOverride)) {
            $filePath = $this->basePath . '/' . ltrim($caminho, '/');
            if (file_exists($filePath)) {
                $conteudoOriginal = file_get_contents($filePath);
                // Se o arquivo já existe e ação é 'criar', mudar para 'modificar' 
                if ($acao === 'criar') $acao = 'modificar';
            }
        }

        // Status: interno = pendente (precisa aprovação), externo = aplicado (imediato)
        $status = $projeto['tipo'] === 'interno' ? 'pendente' : 'aplicado';

        $id = $this->db->insert('ia_dev_arquivos', [
            'projeto_id' => $projetoId,
            'conversa_id' => $conversaId,
            'caminho' => $caminho,
            'conteudo' => $conteudo,
            'conteudo_original' => $conteudoOriginal,
            'linguagem' => $linguagem,
            'acao' => $acao,
            'status' => $status,
        ]);

        // Para projetos externos, salvar fisicamente no disco
        if ($projeto['tipo'] === 'externo') {
            $this->salvarArquivoNoDisco($projetoId, $caminho, $conteudo);
        }

        return $id;
    }

    /**
     * Salvar múltiplos arquivos de uma vez (de uma resposta da IA)
     */
    public function salvarArquivosLote($projetoId, $arquivos, $conversaId = null, $titulo = null, $forceOverride = false) {
        $projeto = $this->findProjeto($projetoId);
        if (!$projeto) throw new \Exception('Projeto não encontrado');

        $ids = [];
        $alteracaoId = null;

        // Para projetos internos, criar uma alteração agrupada
        if ($projeto['tipo'] === 'interno' && $titulo) {
            $alteracaoId = $this->db->insert('ia_dev_alteracoes', [
                'projeto_id' => $projetoId,
                'usuario_id' => $this->userId,
                'conversa_id' => $conversaId,
                'titulo' => $titulo,
                'descricao' => 'Gerado pela IA Dev',
                'status' => 'pendente',
            ]);
        }

        foreach ($arquivos as $arq) {
            $arq['conversa_id'] = $conversaId;
            $arqId = $this->salvarArquivo($projetoId, $arq, $forceOverride);
            $ids[] = $arqId;

            // Vincular à alteração
            if ($alteracaoId) {
                $this->db->insert('ia_dev_alteracao_arquivos', [
                    'alteracao_id' => $alteracaoId,
                    'arquivo_id' => $arqId,
                ]);
            }
        }

        return [
            'arquivo_ids' => $ids,
            'alteracao_id' => $alteracaoId,
        ];
    }

    private function salvarArquivoNoDisco($projetoId, $caminho, $conteudo) {
        $dir = $this->storagePath . '/projects/' . $projetoId;
        $fullPath = $dir . '/' . ltrim($caminho, '/');
        $parentDir = dirname($fullPath);
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }
        file_put_contents($fullPath, $conteudo);
    }

    // ==========================================
    //  ALTERAÇÕES (Change Requests) - Oracle X
    // ==========================================

    public function listarAlteracoes($projetoId = null, $status = null) {
        $where = "1=1";
        $params = [];
        if ($projetoId) {
            $where .= " AND alt.projeto_id = ?";
            $params[] = $projetoId;
        }
        if ($status) {
            $where .= " AND alt.status = ?";
            $params[] = $status;
        }
        return $this->db->fetchAll(
            "SELECT alt.*, u.nome as solicitante_nome, ua.nome as aprovador_nome,
                    p.nome as projeto_nome, p.tipo as projeto_tipo,
                    (SELECT COUNT(*) FROM ia_dev_alteracao_arquivos aa WHERE aa.alteracao_id = alt.id) as total_arquivos
             FROM ia_dev_alteracoes alt
             LEFT JOIN usuarios u ON alt.usuario_id = u.id
             LEFT JOIN usuarios ua ON alt.aprovado_por = ua.id
             LEFT JOIN ia_dev_projetos p ON alt.projeto_id = p.id
             WHERE {$where} ORDER BY alt.criado_em DESC", $params
        );
    }

    public function findAlteracao($id) {
        $alt = $this->db->fetch(
            "SELECT alt.*, u.nome as solicitante_nome, p.nome as projeto_nome, p.tipo as projeto_tipo
             FROM ia_dev_alteracoes alt
             LEFT JOIN usuarios u ON alt.usuario_id = u.id
             LEFT JOIN ia_dev_projetos p ON alt.projeto_id = p.id
             WHERE alt.id = ?", [$id]
        );
        if ($alt) {
            $alt['arquivos'] = $this->db->fetchAll(
                "SELECT a.* FROM ia_dev_arquivos a
                 INNER JOIN ia_dev_alteracao_arquivos aa ON aa.arquivo_id = a.id
                 WHERE aa.alteracao_id = ?", [$id]
            );
        }
        return $alt;
    }

    /**
     * Testar uma alteração - deploya os arquivos no codebase para teste
     * Cria backups automaticamente para permitir reverter
     */
    public function testarAlteracao($id, $forceOverride = false) {
        $alt = $this->findAlteracao($id);
        if (!$alt || $alt['status'] !== 'pendente') {
            throw new \Exception('Alteração não encontrada ou não está pendente');
        }

        // === PROTEÇÃO: verificar se algum arquivo toca módulo protegido ===
        $validation = self::validatePaths($alt['arquivos']);
        if (!empty($validation['blocked']) && !$forceOverride) {
            $blockedList = implode(', ', $validation['blocked']);
            throw new \Exception(
                "⛔ PROTEGIDO: Os seguintes arquivos são módulos protegidos: {$blockedList}. " .
                "Use 'Forçar Override' (admin) para prosseguir mesmo assim."
            );
        }

        $resultados = [];
        $backupDir = $this->storagePath . '/backups/test_' . $id;
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

        foreach ($alt['arquivos'] as $arq) {
            $filePath = $this->basePath . '/' . ltrim($arq['caminho'], '/');
            $parentDir = dirname($filePath);

            try {
                switch ($arq['acao']) {
                    case 'criar':
                        if (!is_dir($parentDir)) mkdir($parentDir, 0755, true);
                        file_put_contents($filePath, $arq['conteudo']);
                        $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'ok', 'acao' => 'criado'];
                        break;

                    case 'modificar':
                        if (!is_dir($parentDir)) mkdir($parentDir, 0755, true);
                        // Backup antes de modificar
                        if (file_exists($filePath)) {
                            copy($filePath, $backupDir . '/' . md5($arq['caminho']) . '.bak');
                            // Salvar mapeamento para restauração
                            $mapFile = $backupDir . '/map.json';
                            $map = file_exists($mapFile) ? json_decode(file_get_contents($mapFile), true) : [];
                            $map[md5($arq['caminho'])] = $arq['caminho'];
                            file_put_contents($mapFile, json_encode($map));
                        }
                        file_put_contents($filePath, $arq['conteudo']);
                        $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'ok', 'acao' => 'modificado'];
                        break;

                    case 'deletar':
                        if (file_exists($filePath)) {
                            copy($filePath, $backupDir . '/' . md5($arq['caminho']) . '.bak');
                            $mapFile = $backupDir . '/map.json';
                            $map = file_exists($mapFile) ? json_decode(file_get_contents($mapFile), true) : [];
                            $map[md5($arq['caminho'])] = $arq['caminho'];
                            file_put_contents($mapFile, json_encode($map));
                            unlink($filePath);
                        }
                        $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'ok', 'acao' => 'deletado'];
                        break;
                }

                $this->db->update('ia_dev_arquivos', ['status' => 'em_teste'], 'id = ?', [$arq['id']]);

            } catch (\Exception $e) {
                $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'erro', 'mensagem' => $e->getMessage()];
            }
        }

        $this->db->update('ia_dev_alteracoes', [
            'status' => 'em_teste',
            'testado_em' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        return $resultados;
    }

    /**
     * Cancelar teste - reverte os arquivos ao estado anterior
     */
    public function cancelarTeste($id) {
        $alt = $this->findAlteracao($id);
        if (!$alt || $alt['status'] !== 'em_teste') {
            throw new \Exception('Alteração não está em teste');
        }

        $backupDir = $this->storagePath . '/backups/test_' . $id;

        foreach ($alt['arquivos'] as $arq) {
            $filePath = $this->basePath . '/' . ltrim($arq['caminho'], '/');
            $backupFile = $backupDir . '/' . md5($arq['caminho']) . '.bak';

            if ($arq['acao'] === 'criar' && file_exists($filePath)) {
                // Arquivo foi criado no teste, remover
                unlink($filePath);
            } elseif ($arq['acao'] === 'modificar' && file_exists($backupFile)) {
                // Restaurar backup
                copy($backupFile, $filePath);
            } elseif ($arq['acao'] === 'deletar' && file_exists($backupFile)) {
                // Restaurar arquivo deletado
                copy($backupFile, $filePath);
            } elseif ($arq['conteudo_original']) {
                // Fallback: restaurar do conteúdo original no banco
                file_put_contents($filePath, $arq['conteudo_original']);
            }

            $this->db->update('ia_dev_arquivos', ['status' => 'pendente'], 'id = ?', [$arq['id']]);
        }

        // Limpar backups do teste
        if (is_dir($backupDir)) {
            $this->removeDir($backupDir);
        }

        $this->db->update('ia_dev_alteracoes', [
            'status' => 'pendente',
            'testado_em' => null,
        ], 'id = ?', [$id]);

        return true;
    }

    /**
     * Remover diretório recursivamente
     */
    private function removeDir($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Aprovar uma alteração (admin/tecnico)
     * Se em_teste: confirma e marca como aplicado (arquivos já estão no disco)
     * Se pendente: marca como aprovado (ainda precisa aplicar)
     */
    public function aprovarAlteracao($id, $aprovadorId, $notas = null) {
        $alt = $this->findAlteracao($id);
        if (!$alt || !in_array($alt['status'], ['pendente', 'em_teste'])) {
            throw new \Exception('Alteração não encontrada ou já processada');
        }

        $isFromTest = ($alt['status'] === 'em_teste');

        if ($isFromTest) {
            // Arquivos já estão no disco do teste - confirmar como aplicado
            $this->db->update('ia_dev_alteracoes', [
                'status' => 'aplicado',
                'aprovado_por' => $aprovadorId,
                'aprovado_em' => date('Y-m-d H:i:s'),
                'aplicado_em' => date('Y-m-d H:i:s'),
                'notas_revisao' => $notas,
            ], 'id = ?', [$id]);

            foreach ($alt['arquivos'] as $arq) {
                $this->db->update('ia_dev_arquivos', [
                    'status' => 'aplicado',
                    'aplicado_em' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$arq['id']]);
            }

            // Mover backups de test_ para backup permanente
            $testBackupDir = $this->storagePath . '/backups/test_' . $id;
            $permBackupDir = $this->storagePath . '/backups/' . $id;
            if (is_dir($testBackupDir)) {
                if (!is_dir($permBackupDir)) mkdir($permBackupDir, 0755, true);
                $files = array_diff(scandir($testBackupDir), ['.', '..']);
                foreach ($files as $file) {
                    rename($testBackupDir . '/' . $file, $permBackupDir . '/' . $file);
                }
                $this->removeDir($testBackupDir);
            }
        } else {
            // Aprovação sem teste - fluxo normal
            $this->db->update('ia_dev_alteracoes', [
                'status' => 'aprovado',
                'aprovado_por' => $aprovadorId,
                'aprovado_em' => date('Y-m-d H:i:s'),
                'notas_revisao' => $notas,
            ], 'id = ?', [$id]);

            foreach ($alt['arquivos'] as $arq) {
                $this->db->update('ia_dev_arquivos', ['status' => 'aprovado'], 'id = ?', [$arq['id']]);
            }
        }

        return ['from_test' => $isFromTest];
    }

    /**
     * Aplicar alteração aprovada ao codebase Oracle X
     */
    public function aplicarAlteracao($id, $forceOverride = false) {
        $alt = $this->findAlteracao($id);
        if (!$alt || $alt['status'] !== 'aprovado') {
            throw new \Exception('Alteração não aprovada');
        }

        // === PROTEÇÃO: verificar se algum arquivo toca módulo protegido ===
        $validation = self::validatePaths($alt['arquivos']);
        if (!empty($validation['blocked']) && !$forceOverride) {
            $blockedList = implode(', ', $validation['blocked']);
            throw new \Exception(
                "⛔ PROTEGIDO: Os seguintes arquivos são módulos protegidos: {$blockedList}. " .
                "Use 'Forçar Override' (admin) para prosseguir mesmo assim."
            );
        }

        $resultados = [];
        foreach ($alt['arquivos'] as $arq) {
            $filePath = $this->basePath . '/' . ltrim($arq['caminho'], '/');
            $parentDir = dirname($filePath);

            try {
                switch ($arq['acao']) {
                    case 'criar':
                        if (!is_dir($parentDir)) mkdir($parentDir, 0755, true);
                        file_put_contents($filePath, $arq['conteudo']);
                        $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'ok', 'acao' => 'criado'];
                        break;

                    case 'modificar':
                        if (!is_dir($parentDir)) mkdir($parentDir, 0755, true);
                        // Backup antes de modificar
                        if (file_exists($filePath)) {
                            $backupDir = $this->storagePath . '/backups/' . $id;
                            if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
                            copy($filePath, $backupDir . '/' . basename($filePath) . '.bak');
                        }
                        file_put_contents($filePath, $arq['conteudo']);
                        $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'ok', 'acao' => 'modificado'];
                        break;

                    case 'deletar':
                        if (file_exists($filePath)) {
                            // Backup antes de deletar
                            $backupDir = $this->storagePath . '/backups/' . $id;
                            if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
                            copy($filePath, $backupDir . '/' . basename($filePath) . '.bak');
                            unlink($filePath);
                        }
                        $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'ok', 'acao' => 'deletado'];
                        break;
                }

                $this->db->update('ia_dev_arquivos', [
                    'status' => 'aplicado',
                    'aplicado_em' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$arq['id']]);

            } catch (\Exception $e) {
                $resultados[] = ['arquivo' => $arq['caminho'], 'status' => 'erro', 'mensagem' => $e->getMessage()];
            }
        }

        $this->db->update('ia_dev_alteracoes', [
            'status' => 'aplicado',
            'aplicado_em' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        return $resultados;
    }

    /**
     * Rejeitar uma alteração
     */
    public function rejeitarAlteracao($id, $notas = null) {
        $alt = $this->findAlteracao($id);
        if (!$alt || !in_array($alt['status'], ['pendente', 'aprovado'])) {
            throw new \Exception('Alteração não encontrada ou já processada');
        }

        $this->db->update('ia_dev_alteracoes', [
            'status' => 'rejeitado',
            'notas_revisao' => $notas,
        ], 'id = ?', [$id]);

        foreach ($alt['arquivos'] as $arq) {
            $this->db->update('ia_dev_arquivos', ['status' => 'rejeitado'], 'id = ?', [$arq['id']]);
        }
        return true;
    }

    /**
     * Reverter alteração já aplicada
     */
    public function reverterAlteracao($id) {
        $alt = $this->findAlteracao($id);
        if (!$alt || $alt['status'] !== 'aplicado') {
            throw new \Exception('Alteração não foi aplicada');
        }

        foreach ($alt['arquivos'] as $arq) {
            $filePath = $this->basePath . '/' . ltrim($arq['caminho'], '/');
            
            if ($arq['acao'] === 'criar' && file_exists($filePath)) {
                unlink($filePath);
            } elseif ($arq['acao'] === 'modificar' && $arq['conteudo_original']) {
                file_put_contents($filePath, $arq['conteudo_original']);
            } elseif ($arq['acao'] === 'deletar' && $arq['conteudo_original']) {
                file_put_contents($filePath, $arq['conteudo_original']);
            }

            $this->db->update('ia_dev_arquivos', ['status' => 'pendente'], 'id = ?', [$arq['id']]);
        }

        $this->db->update('ia_dev_alteracoes', ['status' => 'revertido'], 'id = ?', [$id]);
        return true;
    }

    // ==========================================
    //  LEITURA DE CONTEXTO DO CODEBASE
    // ==========================================

    /**
     * Ler a estrutura do projeto Oracle X (árvore de diretórios)
     */
    public function getEstruturaOracleX($path = '', $maxDepth = 3) {
        $fullPath = $this->basePath . ($path ? '/' . ltrim($path, '/') : '');
        if (!is_dir($fullPath)) return [];
        return $this->scanDir($fullPath, $this->basePath, $maxDepth, 0);
    }

    private function scanDir($dir, $basePath, $maxDepth, $currentDepth) {
        if ($currentDepth >= $maxDepth) return [];
        
        $ignoreDirs = ['.git', 'node_modules', '.venv', 'vendor', 'storage', '.vscode', '__pycache__'];
        $items = [];

        $entries = scandir($dir);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (in_array($entry, $ignoreDirs)) continue;

            $fullPath = $dir . '/' . $entry;
            $relativePath = str_replace($basePath . '/', '', $fullPath);
            $relativePath = str_replace('\\', '/', $relativePath);

            if (is_dir($fullPath)) {
                $children = $this->scanDir($fullPath, $basePath, $maxDepth, $currentDepth + 1);
                $items[] = [
                    'name' => $entry,
                    'path' => $relativePath,
                    'type' => 'dir',
                    'children' => $children,
                ];
            } else {
                $items[] = [
                    'name' => $entry,
                    'path' => $relativePath,
                    'type' => 'file',
                    'size' => filesize($fullPath),
                    'ext' => pathinfo($entry, PATHINFO_EXTENSION),
                ];
            }
        }

        // Ordenar: diretórios primeiro
        usort($items, function($a, $b) {
            if ($a['type'] !== $b['type']) return $a['type'] === 'dir' ? -1 : 1;
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }

    /**
     * Ler conteúdo de um arquivo do Oracle X
     */
    public function lerArquivoOracleX($caminho) {
        $safePath = realpath($this->basePath . '/' . ltrim($caminho, '/'));
        if (!$safePath || strpos($safePath, $this->basePath) !== 0) {
            throw new \Exception('Caminho inválido');
        }
        if (!file_exists($safePath) || !is_file($safePath)) {
            throw new \Exception('Arquivo não encontrado');
        }
        // Limitar tamanho (500KB)
        if (filesize($safePath) > 512000) {
            throw new \Exception('Arquivo muito grande (>500KB)');
        }
        return [
            'caminho' => $caminho,
            'conteudo' => file_get_contents($safePath),
            'linguagem' => $this->detectarLinguagem($caminho),
            'tamanho' => filesize($safePath),
        ];
    }

    /**
     * Ler múltiplos arquivos para dar contexto à IA
     */
    public function lerContextoProjeto($caminhos) {
        $contexto = [];
        foreach ($caminhos as $cam) {
            try {
                $arq = $this->lerArquivoOracleX($cam);
                $contexto[] = "=== ARQUIVO: {$arq['caminho']} ({$arq['linguagem']}) ===\n{$arq['conteudo']}\n";
            } catch (\Exception $e) {
                $contexto[] = "=== ARQUIVO: {$cam} === ERRO: {$e->getMessage()}\n";
            }
        }
        return implode("\n", $contexto);
    }

    /**
     * Gerar contexto resumido do Oracle X para o system prompt
     */
    public function getContextoOracleX() {
        $estrutura = $this->getEstruturaOracleX('', 2);
        $resumo = $this->formatarEstrutura($estrutura);

        // Pegar padrões do sistema
        $padroes = <<<'EOT'
## Padrões do Projeto Oracle X

### Arquitetura
- **Framework**: PHP puro (MVC simplificado), MySQL, JavaScript vanilla
- **Router**: `index.php` com switch/case de módulos
- **Models**: `app/models/` - classes com Database singleton (`Database::getInstance()`)
- **Views**: `app/views/{modulo}/index.php` - PHP com HTML inline
- **Controllers**: `app/controllers/` - lógica de negócio
- **APIs**: `api/{modulo}.php` - endpoints REST (JSON)
- **Migrations**: `migrations/` - scripts PHP de criação de tabelas

### Padrões de Código
- **Database**: `$db = Database::getInstance()` → `$db->fetch()`, `$db->fetchAll()`, `$db->insert()`, `$db->update()`, `$db->delete()`
- **Auth**: `currentUser()` retorna array do usuário, `isLoggedIn()` verifica sessão
- **Toast**: `HelpDesk.toast('mensagem', 'success|error')`
- **Modal**: `HelpDesk.showModal(titulo, corpo, footer, classe)`
- **API calls**: `HelpDesk.api('GET|POST', '/api/xxx.php', data).then(resp => ...)`
- **CSS**: Variáveis CSS custom (--bg-primary, --text-primary, --accent, etc.)
- **Permissões**: `usuario_modulos` table, `ModuloPermissao::temAcesso($userId, $modulo)`

### Convenções
- Tabelas em snake_case português (chamados, usuarios, projetos)
- Colunas: `criado_em`, `atualizado_em`, `ativo` (soft delete)
- IDs: `INT UNSIGNED AUTO_INCREMENT`
- Charset: `utf8mb4_unicode_ci`
- Código: UTF-8, indentação 4 espaços
- Nomes de variáveis/funções em camelCase (JS) e snake_case (PHP)
EOT;

        return "## Estrutura de Diretórios\n```\n{$resumo}```\n\n{$padroes}";
    }

    private function formatarEstrutura($items, $prefix = '') {
        $result = '';
        foreach ($items as $i => $item) {
            $isLast = ($i === count($items) - 1);
            $connector = $isLast ? '└── ' : '├── ';
            $result .= $prefix . $connector . $item['name'];
            if ($item['type'] === 'dir') {
                $result .= "/\n";
                if (!empty($item['children'])) {
                    $childPrefix = $prefix . ($isLast ? '    ' : '│   ');
                    $result .= $this->formatarEstrutura($item['children'], $childPrefix);
                }
            } else {
                $result .= "\n";
            }
        }
        return $result;
    }

    // ==========================================
    //  DOWNLOAD (ZIP)
    // ==========================================

    public function exportarProjetoZip($projetoId) {
        $projeto = $this->findProjeto($projetoId);
        if (!$projeto) throw new \Exception('Projeto não encontrado');

        $zipPath = $this->storagePath . '/exports/' . $projetoId . '_' . time() . '.zip';
        $exportDir = dirname($zipPath);
        if (!is_dir($exportDir)) mkdir($exportDir, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Não foi possível criar o ZIP');
        }

        if ($projeto['tipo'] === 'externo') {
            // Zippar pasta física do projeto
            $projDir = $this->storagePath . '/projects/' . $projetoId;
            if (is_dir($projDir)) {
                $this->addDirToZip($zip, $projDir, $projeto['nome']);
            }
        } else {
            // Para interno, pegar arquivos do banco (apenas aplicados)
            $arquivos = $this->listarArquivos($projetoId, 'aplicado');
            foreach ($arquivos as $arq) {
                $zip->addFromString($arq['caminho'], $arq['conteudo']);
            }
        }

        $zip->close();
        return $zipPath;
    }

    private function addDirToZip($zip, $dir, $base) {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            $relativePath = $base . '/' . substr($file->getPathname(), strlen($dir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    // ==========================================
    //  SYSTEM PROMPT para Dev Mode
    // ==========================================

    /**
     * Gerar system prompt para modo desenvolvimento
     */
    public function getDevSystemPrompt($projeto) {
        $isInterno = ($projeto['tipo'] ?? 'externo') === 'interno';
        $stack = $projeto['stack'] ?? 'geral';
        $nome = $projeto['nome'] ?? 'Projeto';

        $prompt = "Você é um engenheiro de software sênior e arquiteto full-stack. ";
        $prompt .= "Você está trabalhando no projeto **{$nome}**.\n\n";

        if ($isInterno) {
            $prompt .= "Este é o projeto INTERNO Oracle X (sistema HelpDesk). ";
            $prompt .= "Você deve seguir RIGOROSAMENTE os padrões e convenções do projeto.\n\n";
            $prompt .= $this->getContextoOracleX();
            $prompt .= "\n\n";
        } else {
            $prompt .= "Este é um projeto externo com stack: {$stack}.\n\n";
        }

        $prompt .= <<<'EOT'

## REGRAS DE OUTPUT

Quando precisar gerar código, SEMPRE use o formato de bloco de arquivo:

```[DEV_FILE:caminho/relativo/arquivo.ext]
conteúdo do arquivo aqui
```

### Regras:
1. SEMPRE use o marcador `[DEV_FILE:caminho]` dentro do code fence (```)
2. O caminho deve ser RELATIVO à raiz do projeto
3. Gere arquivos COMPLETOS, nunca parciais (nada de "// ... resto do código")
4. Para modificações, gere o arquivo INTEIRO com as alterações
5. Indique claramente se é criação ou modificação de arquivo
6. Use a linguagem correta do code fence (php, js, css, sql, html, etc.)

### Exemplo:
```php[DEV_FILE:app/models/NovoModelo.php]
<?php
class NovoModelo {
    // código completo aqui
}
```

```sql[DEV_FILE:migrations/create_novo_modulo.sql]
CREATE TABLE IF NOT EXISTS nova_tabela (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
);
```

## COMPORTAMENTO

### REGRA PRINCIPAL: ANALISE ANTES DE AGIR
- **SEMPRE** analise o código/projeto existente ANTES de propor alterações
- Quando o usuário pedir uma correção ou melhoria, PRIMEIRO leia e entenda o que já existe
- NÃO adicione funções, métodos ou variáveis que já existem no código
- NÃO mude a estrutura de arquivos que estão funcionando — corrija apenas o problema específico
- NÃO recrie do zero um arquivo que só precisa de ajuste pontual
- Se o pedido é "corrigir X", gere APENAS a correção de X, não refatore o arquivo inteiro
- Pergunte ao usuário se tiver dúvida sobre o escopo da alteração

### Regras Gerais
- Explique BREVEMENTE o que cada arquivo faz antes de gerá-lo
- Se for uma alteração grande, divida em etapas lógicas
- Para migrações SQL, use CREATE TABLE IF NOT EXISTS
- Para projetos internos Oracle X: siga os padrões do projeto fielmente
- Nunca deixe placeholders como "// TODO" ou "// implementar" - gere código funcional completo
- Ao criar módulos novos para o Oracle X, inclua: model, view, controller/API, migration, e rota no index.php
- Quando for modificar um arquivo existente, mantenha TODO o código original que não precisa mudar
- NÃO remova comentários, funções ou blocos de código existentes que não estejam relacionados à alteração pedida
EOT;

        return $prompt;
    }

    /**
     * Parsear arquivos da resposta da IA
     * Procura blocos ```[DEV_FILE:caminho]...```
     */
    public static function parseDevFiles($content) {
        $files = [];
        // Match: ```lang[DEV_FILE:path]\ncontent\n```
        $pattern = '/```(\w*)\[DEV_FILE:([^\]]+)\]\s*\n([\s\S]*?)```/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $linguagem = $match[1] ?: null;
                $caminho = trim($match[2]);
                $conteudo = rtrim($match[3]);

                // Detectar linguagem pelo caminho se não especificada
                if (!$linguagem) {
                    $linguagem = self::detectLangFromPath($caminho);
                }

                $files[] = [
                    'caminho' => $caminho,
                    'conteudo' => $conteudo,
                    'linguagem' => $linguagem,
                    'acao' => 'criar', // default
                ];
            }
        }

        return $files;
    }

    // ==========================================
    //  UTILITÁRIOS
    // ==========================================

    public function detectarLinguagem($caminho) {
        return self::detectLangFromPath($caminho);
    }

    public static function detectLangFromPath($path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'php' => 'php', 'js' => 'javascript', 'ts' => 'typescript',
            'css' => 'css', 'scss' => 'scss', 'html' => 'html', 'htm' => 'html',
            'sql' => 'sql', 'json' => 'json', 'xml' => 'xml', 'yaml' => 'yaml',
            'yml' => 'yaml', 'md' => 'markdown', 'py' => 'python', 'rb' => 'ruby',
            'java' => 'java', 'c' => 'c', 'cpp' => 'cpp', 'h' => 'c',
            'sh' => 'bash', 'bat' => 'batch', 'ps1' => 'powershell',
            'env' => 'env', 'ini' => 'ini', 'conf' => 'conf',
            'vue' => 'vue', 'jsx' => 'jsx', 'tsx' => 'tsx',
        ];
        return $map[$ext] ?? $ext;
    }

    /**
     * Estatísticas do dev mode
     */
    public function getStats($userId = null) {
        $stats = [];
        $stats['total_projetos'] = $this->db->fetchColumn("SELECT COUNT(*) FROM ia_dev_projetos WHERE status = 'ativo'");
        $stats['total_arquivos'] = $this->db->fetchColumn("SELECT COUNT(*) FROM ia_dev_arquivos");
        $stats['pendentes'] = $this->db->fetchColumn("SELECT COUNT(*) FROM ia_dev_alteracoes WHERE status = 'pendente'");
        $stats['em_teste'] = $this->db->fetchColumn("SELECT COUNT(*) FROM ia_dev_alteracoes WHERE status = 'em_teste'");
        $stats['aplicados'] = $this->db->fetchColumn("SELECT COUNT(*) FROM ia_dev_alteracoes WHERE status = 'aplicado'");
        if ($userId) {
            $stats['meus_projetos'] = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM ia_dev_projetos WHERE usuario_id = ? AND status = 'ativo'", [$userId]
            );
        }
        return $stats;
    }

    /**
     * Listar arquivos de um projeto externo no disco
     */
    public function listarArquivosDisco($projetoId) {
        $dir = $this->storagePath . '/projects/' . $projetoId;
        if (!is_dir($dir)) return [];
        return $this->scanDir($dir, $dir, 5, 0);
    }

    /**
     * Ler arquivo de projeto externo do disco
     */
    public function lerArquivoExterno($projetoId, $caminho) {
        $dir = $this->storagePath . '/projects/' . $projetoId;
        $safePath = realpath($dir . '/' . ltrim($caminho, '/'));
        if (!$safePath || strpos($safePath, realpath($dir)) !== 0) {
            throw new \Exception('Caminho inválido');
        }
        if (!file_exists($safePath) || !is_file($safePath)) {
            throw new \Exception('Arquivo não encontrado');
        }
        return [
            'caminho' => $caminho,
            'conteudo' => file_get_contents($safePath),
            'linguagem' => $this->detectarLinguagem($caminho),
            'tamanho' => filesize($safePath),
        ];
    }
}
