<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Fornecedor;

class FornecedorController
{
    private Fornecedor $model;

    public function __construct()
    {
        $this->model = new Fornecedor();
    }

    // GET /api/fornecedores
    public function index(Request $request): void
    {
        $fornecedores = $this->model->listar([
            'busca'   => $request->query['busca'] ?? null,
            'ordenar' => $request->query['ordenar'] ?? null,
            'dir'     => $request->query['dir'] ?? null,
        ]);
        Response::success($fornecedores);
    }

    // GET /api/fornecedores (id)
    public function show(Request $request): void
    {
        $fornecedor = $this->model->buscar((int) $request->params['id']);
        if (!$fornecedor) {
            Response::error('Fornecedor não encontrado', 404);
        }
        Response::success($fornecedor);
    }

    // POST /api/fornecedores
    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('nome', 'nome do fornecedor')
            ->tamanho('nome', 2, 120)
            ->email('email')
            ->formato('cpf_cnpj', '/^\d{11}$|^\d{14}$/', 'CPF/CNPJ deve ter 11 ou 14 dígitos (somente números).');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $id = $this->model->criar($dados);
        Response::created(['id_fornecedor' => $id], "/api/fornecedores/{$id}", 'Fornecedor criado');
    }

    // PUT /api/fornecedores (id)
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Fornecedor não encontrado', 404);
        }

        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('nome', 'nome do fornecedor')
            ->tamanho('nome', 2, 120)
            ->email('email')
            ->formato('cpf_cnpj', '/^\d{11}$|^\d{14}$/', 'CPF/CNPJ deve ter 11 ou 14 dígitos (somente números).');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        $this->model->atualizar($id, $dados);
        Response::success(null, 'Fornecedor atualizado');
    }

    // DELETE /api/fornecedores/(id)
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Fornecedor não encontrado', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>