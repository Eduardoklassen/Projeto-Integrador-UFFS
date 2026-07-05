<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Cliente;
use App\Models\Pedido;
use RuntimeException;
use PDOException;


 // store: pedido com itens + baixa de estoque + histórico.
 // update: mudança de status (com registro no histórico).
 // destroy: exclusão com devolução do estoque.

class PedidoController
{
    private const STATUS_VALIDOS = ['aberto', 'pago', 'enviado', 'entregue', 'cancelado'];

    private Pedido $model;

    public function __construct()
    {
        $this->model = new Pedido();
    }

    // GET /api/pedidos
    public function index(Request $request): void
    {
        Response::success($this->model->listar());
    }

    // GET /api/pedidos/{id}
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

        $v = (new Validator($dados))->obrigatorio('id_cliente', 'cliente');
        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }
        if (!(new Cliente())->buscar((int) $dados['id_cliente'])) {
            Response::error('Cliente informado não existe', 422);
        }

        $itens = $this->validarItens($dados['itens'] ?? null);

        // Responsável = usuário autenticado (injetado pelo AuthMiddleware)
        $idUsuario = isset($request->params['_auth']['id'])
            ? (int) $request->params['_auth']['id'] : null;

        try {
            $id = $this->model->criar((int) $dados['id_cliente'], $idUsuario, $itens);
        } catch (RuntimeException $e) {
            // Regra de negócio violada (ex.: estoque insuficiente):
            // 422 com a mensagem exata — o front exibe no formulário.
            Response::error($e->getMessage(), 422);
        }

        Response::created(['id_pedido' => $id], "/api/pedidos/{$id}", 'Pedido criado');
    }

    // PUT /api/pedidos/{id}  { status }
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Pedido não encontrado', 404);
        }

        $dados = $request->body();
        $status = $dados['status'] ?? null;

        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            Response::error(
                'Status inválido. Use: ' . implode(', ', self::STATUS_VALIDOS),
                422
            );
        }

        $this->model->atualizarStatus($id, $status);
        Response::success(null, 'Status do pedido atualizado');
    }

    // DELETE /api/pedidos/{id}
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Pedido não encontrado', 404);
        }

        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            // FK: registro em uso por outra tabela. Mensagem clara (409)
            // em vez de "erro interno" (500).
            if ($e->getCode() === '23000') {
                Response::error('Este pedido possui registros vinculados e não pode ser excluído.', 409);
            }
            throw $e;
        }
        Response::noContent();
    }

    // Valida a lista de itens; interrompe com 422 se algo falhar
    private function validarItens($itens): array
    {
        if (!is_array($itens) || !count($itens)) {
            Response::error('Informe pelo menos um item no pedido', 422);
        }

        $limpos = [];
        foreach ($itens as $i => $item) {
            $n = $i + 1;
            if (empty($item['id_produto'])) {
                Response::error("Item {$n}: produto não informado", 422);
            }
            if (!isset($item['quantidade']) || !is_numeric($item['quantidade']) || $item['quantidade'] <= 0) {
                Response::error("Item {$n}: quantidade deve ser maior que zero", 422);
            }
            if (!isset($item['valor_unitario']) || !is_numeric($item['valor_unitario']) || $item['valor_unitario'] < 0) {
                Response::error("Item {$n}: valor unitário inválido", 422);
            }
            $limpos[] = [
                'id_produto'     => (int) $item['id_produto'],
                'quantidade'     => (int) $item['quantidade'],
                'valor_unitario' => (float) $item['valor_unitario'],
            ];
        }
        return $limpos;
    }
}
