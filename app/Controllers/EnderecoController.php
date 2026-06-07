<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Endereco;

class EnderecoController
{
    private Endereco $model;

    public function __construct()
    {
        $this->model = new Endereco();
    }

    // GET /api/enderecos id_cliente= 1  ou id_fornecedor= 2
    public function index(Request $request): void
    {
        $enderecos = $this->model->listar([
            'id_cliente'    => $request->query['id_cliente'] ?? null,
            'id_fornecedor' => $request->query['id_fornecedor'] ?? null,
        ]);
        Response::success($enderecos);
    }

    public function show(Request $request): void
    {
        $endereco = $this->model->buscar((int) $request->params['id']);
        if (!$endereco) {
            Response::error('Endereço não encontrado', 404);
        }
        Response::success($endereco);
    }

    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('rua', 'rua');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        // regra de negócio: precisa pertencer a um cliente OU a um fornecedor
        $temCliente    = !empty($dados['id_cliente']);
        $temFornecedor = !empty($dados['id_fornecedor']);
        if ($temCliente === $temFornecedor) {
            Response::error(
                'Informe id_cliente OU id_fornecedor (exatamente um dos dois)',
                422
            );
        }

        $id = $this->model->criar($dados);
        Response::created(['id_endereco' => $id], "/api/enderecos/{$id}", 'Endereço criado');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Endereço não encontrado', 404);
        }
        $this->model->atualizar($id, $request->body());
        Response::success(null, 'Endereço atualizado');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Endereço não encontrado', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>