<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Pedido;

class PedidoController
{
    private Pedido $model;

    public function __construct()
    {
        $this->model = new Pedido();
    }

    public function index(Request $request): void
    {
        $pedidos = $this->model->listar();
        Response::success($pedidos);
    }

    public function show(Request $request): void
    {
        $pedido = $this->model->buscar((int) $request->params['id']);
        if (!$pedido) {
            Response::error('Pedido não encontrado', 404);
        }
        Response::success($pedido);
    }

    // POST /api/pedidos
    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('id_cliente', 'cliente');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        if (empty($dados['itens']) || !is_array($dados['itens'])) {
            Response::error('O pedido precisa de ao menos um item (itens[])', 422);
        }

        // id do usuário autenticado, injetado pelo AuthMiddleware
        $idUsuario = (int) ($request->params['_auth']['id'] ?? 0);
        if ($idUsuario <= 0) {
            Response::error('Usuário autenticado não identificado', 401);
        }

        try {
            $id = $this->model->criar($dados, $idUsuario);
        } catch (\Throwable $e) {
            Response::error('Erro ao criar pedido: ' . $e->getMessage(), 500);
        }

        Response::created(['id_pedido' => $id], "/api/pedidos/{$id}", 'Pedido criado');
    }

    // PUT /api/pedidos -> Usada aqui para mudar o status registra histórico.
    // Resultado: { "status": "pago" }
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        $dados = $request->body();

        if (empty($dados['status'])) {
            Response::error('Informe o novo status', 422);
        }

        try {
            $ok = $this->model->mudarStatus($id, $dados['status']);
        } catch (\Throwable $e) {
            Response::error('Erro ao atualizar pedido: ' . $e->getMessage(), 500);
        }

        if (!$ok) {
            Response::error('Pedido não encontrado ou status inválido', 422);
        }

        Response::success(null, 'Status do pedido atualizado');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Pedido não encontrado', 404);
        }
        $this->model->excluir($id);
        Response::noContent();
    }
}

?>