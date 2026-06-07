<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Material;

class MaterialController
{
    private Material $model;

    public function __construct()
    {
        $this->model = new Material();
    }

    // GET /api/materiais
    public function index(Request $request): void
    {
        $materiais = $this->model->listar([
            'busca'   => $request->query['busca'] ?? null,
            'ordenar' => $request->query['ordenar'] ?? null,
            'dir'     => $request->query['dir'] ?? null,
        ]);
        Response::success($materiais);
    }

    // GET /api/materiais (id)
    public function show(Request $request): void
    {
        $material = $this->model->buscar((int) $request->params['id']);
        if (!$material) {
            Response::error('Material não encontrado', 404);
        }
        Response::success($material);
    }

    // POST /api/materiais
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

    // PUT /api/materiais (id)
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Material não encontrado', 404);
        }
        $this->model->atualizar($id, $request->body());
        Response::success(null, 'Material atualizado');
    }

    // DELETE /api/materiais (id)
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Material não encontrado', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>