<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Produto;

class ProdutoController
{
    private Produto $model;

    public function __construct()
    {
        $this->model = new Produto();
    }

    // GET /api/produtos
    public function index(Request $request): void
    {
        $produtos = $this->model->listar([
            'busca'   => $request->query['busca'] ?? null,
            'ordenar' => $request->query['ordenar'] ?? null,
            'dir'     => $request->query['dir'] ?? null,
        ]);
        Response::success($produtos);
    }

    // GET /api/produtos
    public function show(Request $request): void
    {
        $produto = $this->model->buscar((int) $request->params['id']);
        if (!$produto) {
            Response::error('Produto não encontrado', 404);
        }
        Response::success($produto);
    }

    // POST /api/produtos
    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('nome', 'nome do produto')
            ->numericoPositivo('preco_final')
            ->numericoPositivo('estoque');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $id = $this->model->criar($dados);
        Response::created(['id_produto' => $id], "/api/produtos/{$id}", 'Produto criado');
    }

    // PUT /api/produtos
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Produto não encontrado', 404);
        }
        $this->model->atualizar($id, $request->body());
        Response::success(null, 'Produto atualizado');
    }

    // DELETE /api/produtos
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Produto não encontrado', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>