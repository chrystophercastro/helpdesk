<?php
/**
 * Controller: Tarefa
 */
require_once __DIR__ . '/../models/Tarefa.php';
require_once __DIR__ . '/../models/Projeto.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Notificacao.php';
require_once __DIR__ . '/../models/NotificacaoInterna.php';

class TarefaController {
    private $tarefa;
    private $projeto;
    private $notificacao;
    private $notificacaoInterna;

    public function __construct() {
        $this->tarefa = new Tarefa();
        $this->projeto = new Projeto();
        $this->notificacao = new Notificacao();
        $this->notificacaoInterna = new NotificacaoInterna();
    }

    public function listarKanban($projetoId = null, $deptId = null) {
        $tarefas = $this->tarefa->listarPorColuna($projetoId, $deptId);
        $kanban = [];
        foreach (KANBAN_COLUNAS as $key => $col) {
            $kanban[$key] = [
                'info' => $col,
                'tarefas' => []
            ];
        }
        foreach ($tarefas as $t) {
            $kanban[$t['coluna']]['tarefas'][] = $t;
        }
        return $kanban;
    }

    public function criar($dados) {
        $tarefaData = [
            'titulo' => sanitizar($dados['titulo']),
            'descricao' => sanitizar($dados['descricao'] ?? ''),
            'projeto_id' => !empty($dados['projeto_id']) ? (int)$dados['projeto_id'] : null,
            'sprint_id' => !empty($dados['sprint_id']) ? (int)$dados['sprint_id'] : null,
            'responsavel_id' => !empty($dados['responsavel_id']) ? (int)$dados['responsavel_id'] : null,
            'coluna' => $dados['coluna'] ?? 'backlog',
            'prioridade' => $dados['prioridade'] ?? 'media',
            'prazo' => !empty($dados['prazo']) ? $dados['prazo'] : null,
            'pontos' => !empty($dados['pontos']) ? (int)$dados['pontos'] : null,
            'horas_estimadas' => !empty($dados['horas_estimadas']) ? (float)$dados['horas_estimadas'] : null
        ];

        $id = $this->tarefa->criar($tarefaData);

        // Notificação interna para responsável
        if (!empty($dados['responsavel_id'])) {
            $projetoNome = '';
            if (!empty($dados['projeto_id'])) {
                $p = $this->projeto->findById($dados['projeto_id']);
                $projetoNome = $p['nome'] ?? '';
            }
            $this->notificacaoInterna->notificarTarefaAtribuida($id, (int)$dados['responsavel_id'], $tarefaData['titulo'], $projetoNome);
        }

        // Atualizar progresso do projeto
        if (!empty($dados['projeto_id'])) {
            $this->projeto->atualizarProgresso($dados['projeto_id']);
        }

        // Tags
        if (!empty($dados['tags'])) {
            $db = Database::getInstance();
            $tags = explode(',', $dados['tags']);
            foreach ($tags as $tag) {
                $tag = trim(sanitizar($tag));
                if (!empty($tag)) {
                    $db->insert('tarefas_tags', ['tarefa_id' => $id, 'tag' => $tag]);
                }
            }
        }

        return ['success' => true, 'id' => $id];
    }

    public function atualizar($id, $dados) {
        $update = [];
        $campos = ['titulo', 'descricao', 'projeto_id', 'sprint_id', 'responsavel_id', 'coluna', 'prioridade', 'prazo', 'pontos', 'horas_estimadas', 'horas_trabalhadas'];
        $nullableInt = ['projeto_id', 'sprint_id', 'responsavel_id', 'pontos'];
        $nullableFloat = ['horas_estimadas', 'horas_trabalhadas'];
        $nullableDate = ['prazo'];

        foreach ($campos as $campo) {
            if (isset($dados[$campo])) {
                if (in_array($campo, $nullableInt)) {
                    $update[$campo] = !empty($dados[$campo]) ? (int)$dados[$campo] : null;
                } elseif (in_array($campo, $nullableFloat)) {
                    $update[$campo] = !empty($dados[$campo]) ? (float)$dados[$campo] : null;
                } elseif (in_array($campo, $nullableDate)) {
                    $update[$campo] = !empty($dados[$campo]) ? $dados[$campo] : null;
                } else {
                    $update[$campo] = $dados[$campo];
                }
            }
        }

        if (!empty($update)) {
            $this->tarefa->atualizar($id, $update);

            // Atualizar progresso do projeto
            $tarefa = $this->tarefa->findById($id);
            if ($tarefa && $tarefa['projeto_id']) {
                $this->projeto->atualizarProgresso($tarefa['projeto_id']);
            }

            // Notificar se coluna mudou
            if (isset($dados['coluna']) && $tarefa) {
                $usuario = new Usuario();
                $responsavel = $tarefa['responsavel_id'] ? $usuario->findById($tarefa['responsavel_id']) : null;
                $this->notificacao->notificarAtualizacaoTarefa($tarefa, $responsavel);

                // Notificação interna
                if (!empty($tarefa['responsavel_id']) && $tarefa['responsavel_id'] != ($_SESSION['usuario_id'] ?? 0)) {
                    $colunaLabel = KANBAN_COLUNAS[$dados['coluna']]['label'] ?? $dados['coluna'];
                    $this->notificacaoInterna->notificarSistema(
                        $tarefa['responsavel_id'],
                        'Tarefa movida: ' . $tarefa['titulo'],
                        'Nova coluna: ' . $colunaLabel
                    );
                }
            }
        }

        return ['success' => true];
    }

    public function mover($id, $coluna, $ordem) {
        $this->tarefa->moverColuna($id, $coluna, $ordem);
        
        $tarefa = $this->tarefa->findById($id);
        if ($tarefa && $tarefa['projeto_id']) {
            $this->projeto->atualizarProgresso($tarefa['projeto_id']);
        }

        // Notificar
        if ($tarefa) {
            $usuario = new Usuario();
            $responsavel = $tarefa['responsavel_id'] ? $usuario->findById($tarefa['responsavel_id']) : null;
            $this->notificacao->notificarAtualizacaoTarefa($tarefa, $responsavel);
        }

        return ['success' => true];
    }

    public function deletar($id) {
        $tarefa = $this->tarefa->findById($id);
        $this->tarefa->deletar($id);
        
        if ($tarefa && $tarefa['projeto_id']) {
            $this->projeto->atualizarProgresso($tarefa['projeto_id']);
        }

        return ['success' => true];
    }

    public function comentar($tarefaId, $conteudo) {
        $this->tarefa->adicionarComentario($tarefaId, $_SESSION['usuario_id'], sanitizar($conteudo));
        return ['success' => true];
    }

    public function registrarHoras($tarefaId, $horas) {
        $tarefa = $this->tarefa->findById($tarefaId);
        if ($tarefa) {
            $novasHoras = (float)$tarefa['horas_trabalhadas'] + (float)$horas;
            $this->tarefa->atualizar($tarefaId, ['horas_trabalhadas' => $novasHoras]);
        }
        return ['success' => true];
    }
}
