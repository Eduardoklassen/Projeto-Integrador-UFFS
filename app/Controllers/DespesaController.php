<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Caixa;
use App\Models\Despesa;
use PDOException;


class DespesaController
{
    private Despesa $model;

    public function __construct()
    {
        $this->model = new Despesa();
    }

    public function index(Request $request): void
    {
        Response::success($this->model->listar());
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
        $dados = $this->validar($request);
        $id = $this->model->criar($dados);
        Response::created(['id_despesa' => $id], "/api/despesas/{$id}", 'Despesa registrada');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Despesa não encontrada', 404);
        }
        $dados = $this->validar($request);
        $this->model->atualizar($id, $dados);
        Response::success(null, 'Despesa atualizada');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Despesa não encontrada', 404);
        }
        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            // FK: registro em uso por outra tabela. Mensagem clara (409)
            // em vez de "erro interno" (500).
            if ($e->getCode() === '23000') {
                Response::error('Esta despesa está vinculada a outros registros e não pode ser excluída.', 409);
            }
            throw $e;
        }
        Response::noContent();
    }

    private function validar(Request $request): array
    {
        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('id_caixa', 'caixa')
            ->obrigatorio('valor', 'valor')
            ->numericoPositivo('valor');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        if (!(new Caixa())->buscar((int) $dados['id_caixa'])) {
            Response::error('Caixa informado não existe', 422);
        }

        $tiposValidos = ['compra_material', 'operacional', 'outro'];
        if (!in_array($dados['tipo_movimentacao'] ?? '', $tiposValidos, true)) {
            $dados['tipo_movimentacao'] = 'outro';
        }

        return $dados;
    }
}
