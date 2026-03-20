<?php
/**
 * Controller: Sprint
 */
require_once __DIR__ . '/../models/Sprint.php';
require_once __DIR__ . '/../models/Tarefa.php';

class SprintController {
    private $sprint;
    private $tarefa;

    public function __construct() {
        $this->sprint = new Sprint();
        $this->tarefa = new Tarefa();
    }

    public function listar($projetoId = null, $deptId = null) {
        return $this->sprint->listar($projetoId, $deptId);
    }

    public function ver($id) {
        $sprint = $this->sprint->findById($id);
        if (!$sprint) return null;
        $sprint['tarefas'] = $this->tarefa->listarPorSprint($id);
        $sprint['burndown'] = $this->sprint->getBurndownData($id);
        return $sprint;
    }

    public function criar($dados) {
        $sprintData = [
            'nome' => sanitizar($dados['nome']),
            'projeto_id' => (int)$dados['projeto_id'],
            'data_inicio' => $dados['data_inicio'],
            'data_fim' => $dados['data_fim'],
            'status' => 'planejamento',
            'meta' => sanitizar($dados['meta'] ?? '')
        ];

        $id = $this->sprint->criar($sprintData);
        return ['success' => true, 'id' => $id];
    }

    public function atualizar($id, $dados) {
        $update = [];
        $campos = ['nome', 'data_inicio', 'data_fim', 'status', 'meta'];
        foreach ($campos as $campo) {
            if (isset($dados[$campo])) {
                $update[$campo] = sanitizar($dados[$campo]);
            }
        }

        // Se concluir sprint, calcular velocidade
        if (isset($dados['status']) && $dados['status'] === 'concluida') {
            $tarefas = $this->tarefa->listarPorSprint($id);
            $pontos = 0;
            foreach ($tarefas as $t) {
                if ($t['coluna'] === 'concluido') {
                    $pontos += (int)$t['pontos'];
                }
            }
            $update['velocidade'] = $pontos;
        }

        if (!empty($update)) {
            $this->sprint->atualizar($id, $update);
        }

        return ['success' => true];
    }

    public function getBurndown($id) {
        return $this->sprint->getBurndownData($id);
    }

    public function getVelocidade($projetoId) {
        return $this->sprint->getVelocidade($projetoId);
    }
}
