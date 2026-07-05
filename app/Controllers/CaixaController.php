<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Caixa;
use PDOException;

/** CRUD de caixas (contas do financeiro). */
class CaixaController
{
    private Caixa $model;

    public function __construct()
    {
        $this->model = new Caixa();
    }

    public function index(Request $request): void
    {
        Response::success($this->model->listar());
    }

    public function show(Request $request): void
    {
        $caixa = $this->model->buscar((int) $request->params['id']);
        if (!$caixa) {
            Response::error('Caixa não encontrado', 404);
        }
        Response::success($caixa);
    }

    public function store(Request $request): void
    {
        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('descricao', 'descrição do caixa')
            ->numericoPositivo('saldo');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $id = $this->model->criar($dados);
        Response::created(['id_caixa' => $id], "/api/caixas/{$id}", 'Caixa criado');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Caixa não encontrado', 404);
        }

        $dados = $request->body();
        $v = (new Validator($dados))->obrigatorio('descricao', 'descrição do caixa');
        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        // Só a descrição é editável — o saldo é consequência dos
        // lançamentos (integridade contábil).
        $this->model->atualizar($id, $dados);
        Response::success(null, 'Caixa atualizado');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Caixa não encontrado', 404);
        }

        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('Este caixa possui lançamentos e não pode ser excluído.', 409);
            }
            throw $e;
        }

        Response::noContent();
    }
}
