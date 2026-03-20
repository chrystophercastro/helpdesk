<?php
/**
 * Controller: Projeto
 */
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Notificacao.php';
require_once __DIR__ . '/../models/NotificacaoInterna.php';

class ProjetoController {
    private $projeto;
    private $notificacao;
    private $notificacaoInterna;

    public function __construct() {
        $this->projeto = new Projeto();
        $this->notificacao = new Notificacao();
        $this->notificacaoInterna = new NotificacaoInterna();
    }

    public function listar($deptId = null) {
        $filtros = [
            'status' => $_GET['status'] ?? '',
            'busca' => $_GET['busca'] ?? ''
        ];
        return $this->projeto->listar($filtros, $deptId);
    }

    public function ver($id) {
        $projeto = $this->projeto->findById($id);
        if (!$projeto) return null;
        $projeto['equipe'] = $this->projeto->getEquipe($id);
        $projeto['comentarios'] = $this->projeto->getComentarios($id);
        return $projeto;
    }

    public function criar($dados) {
        $projetoData = [
            'nome' => sanitizar($dados['nome']),
            'descricao' => sanitizar($dados['descricao'] ?? ''),
            'responsavel_id' => $dados['responsavel_id'] ?? null,
            'departamento_id' => $dados['departamento_id'] ?? getUserDeptId(),
            'prioridade' => $dados['prioridade'] ?? 'media',
            'status' => 'planejamento',
            'data_inicio' => $dados['data_inicio'] ?? null,
            'prazo' => $dados['prazo'] ?? null
        ];

        $id = $this->projeto->criar($projetoData);

        // Adicionar membros da equipe
        if (!empty($dados['equipe'])) {
            foreach ($dados['equipe'] as $membroId) {
                $this->projeto->adicionarMembro($id, $membroId);
            }
        }

        // Notificar responsável
        if (!empty($dados['responsavel_id'])) {
            $usuario = new Usuario();
            $responsavel = $usuario->findById($dados['responsavel_id']);
            if ($responsavel) {
                $projetoData['nome'] = $dados['nome'];
                $this->notificacao->notificarNovoProjeto($projetoData, $responsavel);
            }
            // Notificação interna
            if ((int)$dados['responsavel_id'] !== ($_SESSION['usuario_id'] ?? 0)) {
                $this->notificacaoInterna->notificarSistema(
                    (int)$dados['responsavel_id'],
                    'Novo projeto: ' . $dados['nome'],
                    'Você foi designado como responsável',
                    '/index.php?page=projetos&ver=' . $id
                );
            }
        }

        // Log
        $db = Database::getInstance();
        $db->insert('logs', [
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'acao' => 'projeto_criado',
            'entidade_tipo' => 'projeto',
            'entidade_id' => $id,
            'detalhes' => "Projeto '{$dados['nome']}' criado",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        return ['success' => true, 'id' => $id];
    }

    public function atualizar($id, $dados) {
        $update = [];
        $campos = ['nome', 'descricao', 'responsavel_id', 'prioridade', 'status', 'data_inicio', 'prazo', 'progresso'];
        
        // Só admin pode trocar o departamento do projeto
        if (isAdmin() && isset($dados['departamento_id'])) {
            $update['departamento_id'] = (int)$dados['departamento_id'];
        }

        foreach ($campos as $campo) {
            if (isset($dados[$campo])) {
                $update[$campo] = sanitizar($dados[$campo]);
            }
        }

        if (!empty($update)) {
            $this->projeto->atualizar($id, $update);
        }

        return ['success' => true];
    }

    public function comentar($projetoId, $conteudo) {
        $this->projeto->adicionarComentario($projetoId, $_SESSION['usuario_id'], sanitizar($conteudo));
        return ['success' => true];
    }
}
