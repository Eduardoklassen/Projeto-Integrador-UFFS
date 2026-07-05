<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Cliente;
use PDOException;

/**
 * CRUD de clientes — IMPLEMENTADO.
 *
 * O QUE mudou: este controller era um esqueleto da Entrega I —
 * store/update/destroy devolviam "sucesso" SEM tocar no banco.
 * POR QUÊ era grave: o front confiava no sucesso, fechava o modal
 * e recarregava a lista — e o usuário via o cadastro "sumir".
 * Falhar em silêncio é pior que falhar com erro.
 */
class ClienteController
{
    private Cliente $model;

    public function __construct()
    {
        $this->model = new Cliente();
    }

    // GET /api/clientes
    public function index(Request $request): void
    {
        Response::success($this->model->listar());
    }

    // GET /api/clientes/{id}
    public function show(Request $request): void
    {
        $cliente = $this->model->buscar((int) $request->params['id']);
        if (!$cliente) {
            Response::error('Cliente não encontrado', 404);
        }
        Response::success($cliente);
    }

    // POST /api/clientes
    public function store(Request $request): void
    {
        $dados = $request->body();

        $v = (new Validator($dados))
            ->obrigatorio('nome', 'nome do cliente')
            ->obrigatorio('cpf_cnpj', 'CPF/CNPJ')
            ->email('email');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        try {
            $id = $this->model->criar($dados);
        } catch (PDOException $e) {
            // 23000 = violação de restrição (cpf_cnpj é UNIQUE na tabela).
            // Devolvemos mensagem clara em vez de um 500 genérico.
            if ($e->getCode() === '23000') {
                Response::error('CPF/CNPJ já cadastrado para outro cliente.', 422);
            }
            throw $e;
        }

        Response::created(['id_cliente' => $id], "/api/clientes/{$id}", 'Cliente criado');
    }

    // PUT /api/clientes/{id}
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Cliente não encontrado', 404);
        }

        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('nome', 'nome do cliente')
            ->obrigatorio('cpf_cnpj', 'CPF/CNPJ')
            ->email('email');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }

        try {
            $this->model->atualizar($id, $dados);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('CPF/CNPJ já cadastrado para outro cliente.', 422);
            }
            throw $e;
        }

        Response::success(null, 'Cliente atualizado');
    }

    // DELETE /api/clientes/{id}
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        if (!$this->model->buscar($id)) {
            Response::error('Cliente não encontrado', 404);
        }

        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            // Cliente com pedidos vinculados: a FK impede a exclusão.
            // Melhor explicar do que estourar 500 — o registro histórico
            // (pedidos) tem valor contábil e não deve sumir em cascata.
            if ($e->getCode() === '23000') {
                Response::error('Este cliente possui pedidos vinculados e não pode ser excluído.', 409);
            }
            throw $e;
        }

        Response::noContent();
    }
}
