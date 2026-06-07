<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Models\Cliente;

//Esqueleto da Entrega I.

class ClienteController
{
    public function index(Request $request): void
    {
        $clientes = (new Cliente())->listar();
        Response::success($clientes);
    }

    public function store(Request $request): void
    {
        Response::success(null, 'Rota de criação de cliente recebida com sucesso', 201);
    }

    public function show(Request $request): void
    {
        Response::success(null, 'Rota de detalhe de cliente recebida com sucesso');
    }

    public function update(Request $request): void
    {
        Response::success(null, 'Rota de atualização de cliente recebida com sucesso');
    }

    public function destroy(Request $request): void
    {
        Response::success(null, 'Rota de exclusão de cliente recebida com sucesso');
    }
}

?>