<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Caixa;

class CaixaController
{
    private Caixa $model;

    public function __construct()
    {
        $this->model = new Caixa();
    }

    // GET /api/caixas
    public function index(Request $request): void
    {
        $caixas = $this->model->listar([
            'busca'   => $request->query['busca'] ?? null,
            'ordenar' => $request->query['ordenar'] ?? null,
            'dir'     => $request->query['dir'] ?? null,
        ]);
        Response::success($caixas);
    }

    // GET /api/caixas/(id)
    public function show(Request $request): void
    {
        $caixa = $this->model->buscar((int) $request->params['id']);
        if (!$caixa) {
            Response::error('Caixa não encontrado', 404);
        }
        Response::success($caixa);
    }

    // POST /api/caixas
    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('descricao', 'descrição do caixa');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $id = $this->model->criar($dados);
        Response::created(['id_caixa' => $id], "/api/caixas/{$id}", 'Caixa criado');
    }

    // PUT /api/caixas/(id)
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Caixa não encontrado', 404);
        }
        $this->model->atualizar($id, $request->body());
        Response::success(null, 'Caixa atualizado');
    }

    // DELETE /api/caixas/(id)
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Caixa não encontrado', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>