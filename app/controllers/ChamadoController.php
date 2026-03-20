<?php
/**
 * Controller: Chamado
 */
require_once __DIR__ . '/../models/Chamado.php';
require_once __DIR__ . '/../models/Solicitante.php';
require_once __DIR__ . '/../models/Notificacao.php';
require_once __DIR__ . '/../models/NotificacaoInterna.php';
require_once __DIR__ . '/../models/Database.php';

class ChamadoController {
    private $chamado;
    private $solicitante;
    private $notificacao;
    private $notificacaoInterna;

    public function __construct() {
        $this->chamado = new Chamado();
        $this->solicitante = new Solicitante();
        $this->notificacao = new Notificacao();
        $this->notificacaoInterna = new NotificacaoInterna();
    }

    public function listar() {
        $filtros = [
            'status' => $_GET['status'] ?? '',
            'prioridade' => $_GET['prioridade'] ?? '',
            'tecnico_id' => $_GET['tecnico_id'] ?? '',
            'categoria_id' => $_GET['categoria_id'] ?? '',
            'departamento_id' => $_GET['departamento_id'] ?? '',
            'busca' => $_GET['busca'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'canal' => $_GET['canal'] ?? '',
            'impacto' => $_GET['impacto'] ?? '',
            'urgencia' => $_GET['urgencia'] ?? '',
            'sla_vencido' => $_GET['sla_vencido'] ?? '',
            'ordem' => $_GET['ordem'] ?? 'recentes',
        ];

        // Gestor: forçar filtro do seu departamento
        $deptFilter = getDeptFilter();
        if ($deptFilter) {
            $filtros['departamento_id'] = $deptFilter;
        }

        $porPagina = (int)($_GET['por_pagina'] ?? 25);
        if ($porPagina < 10) $porPagina = 10;
        if ($porPagina > 100) $porPagina = 100;

        $pagina = max(1, (int)($_GET['pg'] ?? 1));
        $offset = ($pagina - 1) * $porPagina;

        $total = $this->chamado->contar($filtros);
        $chamados = $this->chamado->listar($filtros, $porPagina, $offset);

        return [
            'chamados' => $chamados,
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => max(1, ceil($total / $porPagina)),
            'filtros' => $filtros,
        ];
    }

    public function ver($id) {
        $chamado = $this->chamado->findById($id);
        if (!$chamado) return null;

        // Gestor: só pode ver chamados do seu departamento
        $deptFilter = getDeptFilter();
        if ($deptFilter && (int)($chamado['departamento_id'] ?? 0) !== (int)$deptFilter) {
            return null;
        }

        $chamado['comentarios'] = $this->chamado->getComentarios($id);
        $chamado['historico'] = $this->chamado->getHistorico($id);
        $chamado['tags'] = $this->chamado->getTags($id);
        $chamado['anexos'] = Database::getInstance()->fetchAll(
            "SELECT * FROM anexos WHERE entidade_tipo = 'chamado' AND entidade_id = ?", [$id]
        );
        return $chamado;
    }

    public function criar($dados, $isPortal = false) {
        $telefone = formatarTelefone($dados['telefone']);

        // Verificar limite de chamados por telefone
        if ($isPortal) {
            $db = Database::getInstance();
            $limite = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'limite_chamados_telefone'");
            $abertos = $this->solicitante->contarChamadosAbertos($telefone);
            if ($abertos >= (int)$limite) {
                return ['error' => 'Limite de chamados abertos atingido para este telefone.'];
            }
        }

        // Buscar ou criar solicitante
        $solicitanteId = $this->solicitante->buscarOuCriar([
            'nome' => sanitizar($dados['nome']),
            'email' => sanitizar($dados['email']),
            'telefone' => $telefone
        ]);

        // Calcular prioridade automática
        $impacto = $dados['impacto'] ?? 'medio';
        $urgencia = $dados['urgencia'] ?? 'media';
        $prioridade = calcularPrioridade($impacto, $urgencia);

        // Buscar SLA correspondente (por categoria+prioridade, fallback para padrão)
        // Converter strings vazias para null em campos inteiros nullable
        $categoriaId = !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null;
        $tecnicoId = !empty($dados['tecnico_id']) ? (int)$dados['tecnico_id'] : null;
        $ativoId = !empty($dados['ativo_id']) ? (int)$dados['ativo_id'] : null;

        // Buscar SLA correspondente (por categoria+prioridade, fallback para padrão)
        $db = Database::getInstance();
        $sla = null;
        try {
            if ($categoriaId) {
                $sla = $db->fetch("SELECT id FROM sla WHERE categoria_id = ? AND prioridade = ? AND ativo = 1 LIMIT 1", [$categoriaId, $prioridade]);
            }
            if (!$sla) {
                $sla = $db->fetch("SELECT id FROM sla WHERE categoria_id IS NULL AND prioridade = ? AND ativo = 1 LIMIT 1", [$prioridade]);
            }
        } catch (Exception $e) {
            // Fallback: buscar SLA apenas por prioridade (compatibilidade com DB sem categoria_id)
            $sla = $db->fetch("SELECT id FROM sla WHERE prioridade = ? AND ativo = 1 LIMIT 1", [$prioridade]);
        }

        // Departamento
        $departamentoId = !empty($dados['departamento_id']) ? (int)$dados['departamento_id'] : null;

        // Se tem categoria mas não tem departamento, herdar do cadastro da categoria
        if (!$departamentoId && $categoriaId) {
            $catDept = $db->fetchColumn("SELECT departamento_id FROM categorias WHERE id = ?", [$categoriaId]);
            if ($catDept) $departamentoId = (int)$catDept;
        }

        // Buscar sigla do departamento para o código
        $sigla = 'HD';
        if ($departamentoId) {
            $deptSigla = $db->fetchColumn("SELECT sigla FROM departamentos WHERE id = ?", [$departamentoId]);
            if ($deptSigla) $sigla = $deptSigla;
        }

        $codigo = gerarCodigoChamado($sigla);
        // Garantir código único
        while ($this->chamado->findByCodigo($codigo)) {
            $codigo = gerarCodigoChamado($sigla);
        }

        $chamadoData = [
            'codigo' => $codigo,
            'titulo' => sanitizar($dados['titulo']),
            'descricao' => sanitizar($dados['descricao']),
            'categoria_id' => $categoriaId,
            'departamento_id' => $departamentoId,
            'prioridade' => $prioridade,
            'urgencia' => $urgencia,
            'impacto' => $impacto,
            'solicitante_id' => $solicitanteId,
            'telefone_solicitante' => $telefone,
            'tecnico_id' => $tecnicoId,
            'status' => 'aberto',
            'sla_id' => $sla['id'] ?? null,
            'ativo_id' => $ativoId,
            'canal' => $isPortal ? 'portal' : 'interno'
        ];

        $id = $this->chamado->criar($chamadoData);
        $this->solicitante->incrementarChamados($solicitanteId);

        // Processar anexos
        if (!empty($_FILES['anexos'])) {
            $this->processarAnexos($id, 'chamado', $_FILES['anexos']);
        }

        // Processar tags
        if (!empty($dados['tags'])) {
            $tags = explode(',', $dados['tags']);
            foreach ($tags as $tag) {
                $tag = trim(sanitizar($tag));
                if (!empty($tag)) {
                    $this->chamado->adicionarTag($id, $tag);
                }
            }
        }

        // Registrar log
        $db->insert('logs', [
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'acao' => 'chamado_criado',
            'entidade_tipo' => 'chamado',
            'entidade_id' => $id,
            'detalhes' => "Chamado {$codigo} criado",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        // Notificar via WhatsApp
        $solicitanteData = $this->solicitante->findById($solicitanteId);
        $chamadoData['id'] = $id;
        $chamadoData['codigo'] = $codigo;
        $this->notificacao->notificarNovoChamado($chamadoData, $solicitanteData);

        // Notificação interna (bell icon)
        $this->notificacaoInterna->notificarNovoChamado($chamadoData);
        if (!empty($tecnicoId)) {
            $this->notificacaoInterna->notificarChamadoAtribuido($id, $tecnicoId, $chamadoData['titulo']);
        }

        return ['success' => true, 'id' => $id, 'codigo' => $codigo];
    }

    public function atualizar($id, $dados) {
        $chamadoAtual = $this->chamado->findById($id);
        if (!$chamadoAtual) return ['error' => 'Chamado não encontrado.'];

        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $campos = ['titulo', 'descricao', 'categoria_id', 'prioridade', 'urgencia', 'impacto', 'tecnico_id', 'status', 'ativo_id'];
        $camposInteiros = ['categoria_id', 'tecnico_id', 'ativo_id'];

        // Converter strings vazias para null em campos inteiros
        foreach ($camposInteiros as $ci) {
            if (isset($dados[$ci]) && $dados[$ci] === '') {
                $dados[$ci] = null;
            }
        }

        $update = [];
        foreach ($campos as $campo) {
            if (isset($dados[$campo]) && $dados[$campo] != $chamadoAtual[$campo]) {
                $valorAnterior = $chamadoAtual[$campo];
                $valorNovo = $dados[$campo];
                $update[$campo] = in_array($campo, $camposInteiros) ? ($valorNovo !== null ? (int)$valorNovo : null) : sanitizar($valorNovo);

                $this->chamado->registrarHistorico($id, $usuarioId, $campo, $valorAnterior, $valorNovo);
                $this->notificacao->notificarAtualizacaoChamado($chamadoAtual, $campo, $valorNovo);

                // Notificação interna (bell icon)
                $this->notificacaoInterna->notificarChamadoAtualizado($chamadoAtual, $campo, $valorNovo);
            }
        }

        // Tratar mudanças de status
        if (isset($dados['status'])) {
            if ($dados['status'] === 'resolvido' && $chamadoAtual['status'] !== 'resolvido') {
                $update['data_resolucao'] = date('Y-m-d H:i:s');

                // Enviar link de avaliação via WhatsApp
                $solicitanteNome = '';
                if (!empty($chamadoAtual['solicitante_id'])) {
                    $sol = $this->solicitante->findById($chamadoAtual['solicitante_id']);
                    $solicitanteNome = $sol['nome'] ?? '';
                }
                $this->notificacao->notificarAvaliacao($chamadoAtual, $solicitanteNome);
            }
            if ($dados['status'] === 'fechado' && $chamadoAtual['status'] !== 'fechado') {
                $update['data_fechamento'] = date('Y-m-d H:i:s');
                // Notificação interna de fechamento
                $this->notificacaoInterna->notificarChamadoFechado($chamadoAtual);
            }
            if (in_array($dados['status'], ['em_analise', 'em_atendimento']) && !$chamadoAtual['data_primeira_resposta']) {
                $update['data_primeira_resposta'] = date('Y-m-d H:i:s');
            }
        }

        // Recalcular prioridade se impacto ou urgência mudou
        if (isset($dados['impacto']) || isset($dados['urgencia'])) {
            $impacto = $dados['impacto'] ?? $chamadoAtual['impacto'];
            $urgencia = $dados['urgencia'] ?? $chamadoAtual['urgencia'];
            $update['prioridade'] = calcularPrioridade($impacto, $urgencia);
        }

        // Recalcular SLA se categoria ou prioridade mudou
        if (isset($dados['categoria_id']) || isset($update['prioridade']) || isset($dados['prioridade'])) {
            $catId = $dados['categoria_id'] ?? $chamadoAtual['categoria_id'];
            $pri = $update['prioridade'] ?? $dados['prioridade'] ?? $chamadoAtual['prioridade'];
            $db = Database::getInstance();
            $novoSla = null;
            try {
                if ($catId) {
                    $novoSla = $db->fetch("SELECT id FROM sla WHERE categoria_id = ? AND prioridade = ? AND ativo = 1 LIMIT 1", [$catId, $pri]);
                }
                if (!$novoSla) {
                    $novoSla = $db->fetch("SELECT id FROM sla WHERE categoria_id IS NULL AND prioridade = ? AND ativo = 1 LIMIT 1", [$pri]);
                }
            } catch (Exception $e) {
                $novoSla = $db->fetch("SELECT id FROM sla WHERE prioridade = ? AND ativo = 1 LIMIT 1", [$pri]);
            }
            $update['sla_id'] = $novoSla['id'] ?? null;
        }

        if (!empty($update)) {
            $this->chamado->atualizar($id, $update);
        }

        return ['success' => true];
    }

    public function comentar($chamadoId, $dados) {
        $chamado = $this->chamado->findById($chamadoId);
        if (!$chamado) return ['error' => 'Chamado não encontrado.'];

        $comentarioData = [
            'chamado_id' => $chamadoId,
            'conteudo' => sanitizar($dados['conteudo']),
            'tipo' => $dados['tipo'] ?? 'comentario'
        ];

        if (!empty($dados['usuario_id'])) {
            $comentarioData['usuario_id'] = $dados['usuario_id'];
            $db = Database::getInstance();
            $user = $db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$dados['usuario_id']]);
            $comentarioData['autor_nome'] = $user['nome'] ?? 'Sistema';
        } elseif (!empty($dados['solicitante_id'])) {
            $comentarioData['solicitante_id'] = $dados['solicitante_id'];
            $comentarioData['autor_nome'] = $dados['autor_nome'] ?? 'Solicitante';
        } else {
            // Default to current session user
            $comentarioData['usuario_id'] = $_SESSION['usuario_id'] ?? null;
            if ($comentarioData['usuario_id']) {
                $db = Database::getInstance();
                $userDb = $db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$comentarioData['usuario_id']]);
                $comentarioData['autor_nome'] = $userDb['nome'] ?? 'Sistema';
            }
        }

        $id = $this->chamado->adicionarComentario($comentarioData);

        // Notificar via WhatsApp (passa o tipo para não notificar notas internas)
        $autorNome = $comentarioData['autor_nome'] ?? 'Sistema';
        $tipo = $comentarioData['tipo'] ?? 'comentario';
        $conteudo = $comentarioData['conteudo'] ?? '';
        $this->notificacao->notificarComentarioChamado($chamado, $autorNome, $tipo, $conteudo);

        // Notificação interna (bell icon)
        $this->notificacaoInterna->notificarComentarioChamado($chamado, $autorNome, $tipo);

        return ['success' => true, 'id' => $id];
    }

    private function processarAnexos($entidadeId, $entidadeTipo, $files) {
        $db = Database::getInstance();
        $uploadDir = UPLOAD_PATH . '/' . $entidadeTipo . '/' . $entidadeId . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK || empty($name)) continue;
            if ($size > UPLOAD_MAX_SIZE) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTENSIONS)) continue;

            $nomeArquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $destino = $uploadDir . $nomeArquivo;

            if (move_uploaded_file($tmp, $destino)) {
                $db->insert('anexos', [
                    'entidade_tipo' => $entidadeTipo,
                    'entidade_id' => $entidadeId,
                    'nome_original' => $name,
                    'nome_arquivo' => $nomeArquivo,
                    'tamanho' => $size,
                    'tipo_mime' => mime_content_type($destino)
                ]);
            }
        }
    }
}
