<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Fornecedor;

class DashboardController
{
    // GET /api/dashboard
    public function resumo(Request $request): void
    {
        Response::success([
            'total_produtos'    => (new Produto())->contar(),
            'total_pedidos'     => (new Pedido())->contar(),
            'total_clientes'    => (new Cliente())->contar(),
            'total_fornecedores'=> (new Fornecedor())->contar(),
        ]);
    }
}

?>
