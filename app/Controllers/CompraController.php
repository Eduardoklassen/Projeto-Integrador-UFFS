<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Compra;
use App\Models\Fornecedor;
use RuntimeException;
use PDOException;

/**
 * Compras — entrada de materiais (criar/listar/ver/excluir).
 * Sem update: compra é lançamento — corrige-se excluindo (com
 * estorno de estoque) e lançando novamente.
 */
class CompraController
{
    private Compra $model;

    public function __construct()
    {
        $this->model = new Compra();
    }

    // GET /api/compras
    public function index(Request $request): void
    {
        Response::success($this->model->listar());
    }

    // GET /api/compras/{id}
    public function show(Request $request): void
    {
        $compra = $this->model->buscar((int) $request->params['id']);
        if (!$compra) {
            Response::error('Compra não encontrada', 404);
        }
        Response::success($compra);
    }

    // POST /api/compras  { id_fornecedor, itens: [{id_material, quantidade, custo_unitario}] }
    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))->obrigatorio('id_fornecedor', 'fornecedor');
        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }
        if (!(new Fornecedor())->buscar((int) $dados['id_fornecedor'])) {
            Response::error('Fornecedor informado não existe', 422);
        }

        $itens = $this->validarItens($dados['itens'] ?? null);

        try {
            $id = $this->model->criar((int) $dados['id_fornecedor'], $itens);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 422);
        }

        Response::created(['id_compra' => $id], "/api/compras/{$id}", 'Compra registrada');
    }

    // DELETE /api/compras/{id}
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Compra não encontrada', 404);
        }

        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            // FK: registro em uso por outra tabela. Mensagem clara (409)
            // em vez de "erro interno" (500).
            if ($e->getCode() === '23000') {
                Response::error('Esta compra possui registros vinculados e não pode ser excluída.', 409);
            }
            throw $e;
        }
        Response::noContent();
    }

    private function validarItens($itens): array
    {
        if (!is_array($itens) || !count($itens)) {
            Response::error('Informe pelo menos um item na compra', 422);
        }

        $limpos = [];
        foreach ($itens as $i => $item) {
            $n = $i + 1;
            if (empty($item['id_material'])) {
                Response::error("Item {$n}: material não informado", 422);
            }
            if (!isset($item['quantidade']) || !is_numeric($item['quantidade']) || $item['quantidade'] <= 0) {
                Response::error("Item {$n}: quantidade deve ser maior que zero", 422);
            }
            if (!isset($item['custo_unitario']) || !is_numeric($item['custo_unitario']) || $item['custo_unitario'] < 0) {
                Response::error("Item {$n}: custo unitário inválido", 422);
            }
            $limpos[] = [
                'id_material'    => (int) $item['id_material'],
                'quantidade'     => (float) $item['quantidade'],
                'custo_unitario' => (float) $item['custo_unitario'],
            ];
        }
        return $limpos;
    }
}
