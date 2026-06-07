<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Compra;

class CompraController
{
    private Compra $model;

    public function __construct()
    {
        $this->model = new Compra();
    }

    public function index(Request $request): void
    {
        $compras = $this->model->listar([
            'busca'   => $request->query['busca'] ?? null,
            'ordenar' => $request->query['ordenar'] ?? null,
            'dir'     => $request->query['dir'] ?? null,
        ]);
        Response::success($compras);
    }

    public function show(Request $request): void
    {
        $compra = $this->model->buscar((int) $request->params['id']);
        if (!$compra) {
            Response::error('Compra não encontrada', 404);
        }
        Response::success($compra);
    }

    // POST /api/compras
    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('id_fornecedor', 'fornecedor');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        if (empty($dados['itens']) || !is_array($dados['itens'])) {
            Response::error('A compra precisa de ao menos um item (itens[])', 422);
        }

        try {
            $id = $this->model->criar($dados);
        } catch (\Throwable $e) {
            Response::error('Erro ao registrar compra: ' . $e->getMessage(), 500);
        }

        Response::created(['id_compra' => $id], "/api/compras/{$id}", 'Compra registrada');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Compra não encontrada', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>