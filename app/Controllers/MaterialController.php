<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Material;
use PDOException;

class MaterialController
{
    private Material $model;

    public function __construct()
    {
        $this->model = new Material();
    }

    public function index(Request $request): void
    {
        Response::success($this->model->listar());
    }

    public function show(Request $request): void
    {
        $material = $this->model->buscar((int) $request->params['id']);
        if (!$material) {
            Response::error('Material não encontrado', 404);
        }
        Response::success($material);
    }

    public function store(Request $request): void
    {
        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('nome', 'nome do material')
            ->numericoPositivo('custo_unitario')
            ->numericoPositivo('estoque');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $id = $this->model->criar($dados);
        Response::created(['id_material' => $id], "/api/materiais/{$id}", 'Material criado');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Material não encontrado', 404);
        }

        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('nome', 'nome do material')
            ->numericoPositivo('custo_unitario')
            ->numericoPositivo('estoque');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $this->model->atualizar($id, $dados);
        Response::success(null, 'Material atualizado');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Material não encontrado', 404);
        }

        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('Este material está em compras registradas e não pode ser excluído.', 409);
            }
            throw $e;
        }

        Response::noContent();
    }
}
