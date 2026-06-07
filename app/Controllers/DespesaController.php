<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Despesa;

class DespesaController
{
    private Despesa $model;

    public function __construct()
    {
        $this->model = new Despesa();
    }

    public function index(Request $request): void
    {
        $despesas = $this->model->listar([
            'busca'   => $request->query['busca'] ?? null,
            'ordenar' => $request->query['ordenar'] ?? null,
            'dir'     => $request->query['dir'] ?? null,
        ]);
        Response::success($despesas);
    }

    public function show(Request $request): void
    {
        $despesa = $this->model->buscar((int) $request->params['id']);
        if (!$despesa) {
            Response::error('Despesa não encontrada', 404);
        }
        Response::success($despesa);
    }

    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('id_caixa', 'caixa')
            ->obrigatorio('valor', 'valor')
            ->numericoPositivo('valor');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $id = $this->model->criar($dados);
        Response::created(['id_despesa' => $id], "/api/despesas/{$id}", 'Despesa criada');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Despesa não encontrada', 404);
        }
        $this->model->atualizar($id, $request->body());
        Response::success(null, 'Despesa atualizada');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Despesa não encontrada', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>