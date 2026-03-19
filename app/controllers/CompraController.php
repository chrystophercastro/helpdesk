<?php
/**
 * Controller: Compra
 */
require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Notificacao.php';

class CompraController {
    private $compra;
    private $notificacao;

    public function __construct() {
        $this->compra = new Compra();
        $this->notificacao = new Notificacao();
    }

    public function listar() {
        $filtros = [
            'status' => $_GET['status'] ?? '',
            'busca' => $_GET['busca'] ?? ''
        ];
        return $this->compra->listar($filtros);
    }

    public function ver($id) {
        $compra = $this->compra->findById($id);
        if (!$compra) return null;
        $compra['historico'] = $this->compra->getHistorico($id);
        return $compra;
    }

    public function criar($dados) {
        $codigo = gerarCodigoCompra();
        $compraData = [
            'codigo' => $codigo,
            'solicitante_usuario_id' => $_SESSION['usuario_id'] ?? null,
            'item' => sanitizar($dados['item']),
            'descricao' => sanitizar($dados['descricao'] ?? ''),
            'quantidade' => (int)($dados['quantidade'] ?? 1),
            'justificativa' => sanitizar($dados['justificativa']),
            'prioridade' => $dados['prioridade'] ?? 'media',
            'valor_estimado' => (float)($dados['valor_estimado'] ?? 0),
            'status' => 'solicitado'
        ];

        $id = $this->compra->criar($compraData);

        // Notificar gestores
        $usuario = new Usuario();
        $gestores = Database::getInstance()->fetchAll(
            "SELECT telefone FROM usuarios WHERE tipo IN ('gestor','admin') AND ativo = 1"
        );
        foreach ($gestores as $g) {
            $compraData['codigo'] = $codigo;
            $this->notificacao->notificarCompra($compraData, 'solicitado', $g['telefone']);
        }

        return ['success' => true, 'id' => $id, 'codigo' => $codigo];
    }

    public function alterarStatus($id, $status, $observacao = '') {
        $compra = $this->compra->findById($id);
        if (!$compra) return ['error' => 'Requisição não encontrada.'];

        $this->compra->alterarStatus($id, $status, $_SESSION['usuario_id'], sanitizar($observacao));

        // Notificar solicitante
        if ($compra['solicitante_telefone']) {
            $this->notificacao->notificarCompra($compra, $status, $compra['solicitante_telefone']);
        }

        return ['success' => true];
    }
}
