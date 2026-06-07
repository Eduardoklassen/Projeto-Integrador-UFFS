<?php

use App\Controllers\AuthController;
use App\Controllers\ProdutoController;
use App\Controllers\PedidoController;
use App\Controllers\ClienteController;
use App\Controllers\FornecedorController;
use App\Controllers\DashboardController;
use App\Controllers\MaterialController;
use App\Controllers\CaixaController;
use App\Controllers\ReceitaController;
use App\Controllers\DespesaController;
use App\Controllers\EnderecoController;
use App\Controllers\CompraController;

/** @var \App\Core\Router $router */

//  Rotas públicas 
$router->post('/api/login',    [AuthController::class, 'login']);
$router->post('/api/register', [AuthController::class, 'register']);

//  Produtos (CRUD) 
$router->get('/api/produtos',        [ProdutoController::class, 'index'],   ['auth']);
$router->get('/api/produtos/{id}',   [ProdutoController::class, 'show'],    ['auth']);
$router->post('/api/produtos',       [ProdutoController::class, 'store'],   ['auth', 'admin']);
$router->put('/api/produtos/{id}',   [ProdutoController::class, 'update'],  ['auth', 'admin']);
$router->delete('/api/produtos/{id}',[ProdutoController::class, 'destroy'], ['auth', 'admin']);

// Pedidos (CRUD) 
$router->get('/api/pedidos',         [PedidoController::class, 'index'],    ['auth']);
$router->get('/api/pedidos/{id}',    [PedidoController::class, 'show'],     ['auth']);
$router->post('/api/pedidos',        [PedidoController::class, 'store'],    ['auth']);
$router->put('/api/pedidos/{id}',    [PedidoController::class, 'update'],   ['auth']);
$router->delete('/api/pedidos/{id}', [PedidoController::class, 'destroy'],  ['auth', 'admin']);

// Clientes (CRUD) 
$router->get('/api/clientes',        [ClienteController::class, 'index'],   ['auth']);
$router->get('/api/clientes/{id}',   [ClienteController::class, 'show'],    ['auth']);
$router->post('/api/clientes',       [ClienteController::class, 'store'],   ['auth']);
$router->put('/api/clientes/{id}',   [ClienteController::class, 'update'],  ['auth']);
$router->delete('/api/clientes/{id}',[ClienteController::class, 'destroy'], ['auth', 'admin']);

//  Fornecedores (CRUD) 
$router->get('/api/fornecedores',        [FornecedorController::class, 'index'],   ['auth']);
$router->get('/api/fornecedores/{id}',   [FornecedorController::class, 'show'],    ['auth']);
$router->post('/api/fornecedores',       [FornecedorController::class, 'store'],   ['auth', 'admin']);
$router->put('/api/fornecedores/{id}',   [FornecedorController::class, 'update'],  ['auth', 'admin']);
$router->delete('/api/fornecedores/{id}',[FornecedorController::class, 'destroy'], ['auth', 'admin']);

//  Materiais (CRUD) 
$router->get('/api/materiais',        [MaterialController::class, 'index'],   ['auth']);
$router->get('/api/materiais/{id}',   [MaterialController::class, 'show'],    ['auth']);
$router->post('/api/materiais',       [MaterialController::class, 'store'],   ['auth', 'admin']);
$router->put('/api/materiais/{id}',   [MaterialController::class, 'update'],  ['auth', 'admin']);
$router->delete('/api/materiais/{id}',[MaterialController::class, 'destroy'], ['auth', 'admin']);

// Caixas (CRUD) 
$router->get('/api/caixas',        [CaixaController::class, 'index'],   ['auth']);
$router->get('/api/caixas/{id}',   [CaixaController::class, 'show'],    ['auth']);
$router->post('/api/caixas',       [CaixaController::class, 'store'],   ['auth', 'admin']);
$router->put('/api/caixas/{id}',   [CaixaController::class, 'update'],  ['auth', 'admin']);
$router->delete('/api/caixas/{id}',[CaixaController::class, 'destroy'], ['auth', 'admin']);

// Receitas (CRUD) 
$router->get('/api/receitas',        [ReceitaController::class, 'index'],   ['auth']);
$router->get('/api/receitas/{id}',   [ReceitaController::class, 'show'],    ['auth']);
$router->post('/api/receitas',       [ReceitaController::class, 'store'],   ['auth']);
$router->put('/api/receitas/{id}',   [ReceitaController::class, 'update'],  ['auth']);
$router->delete('/api/receitas/{id}',[ReceitaController::class, 'destroy'], ['auth', 'admin']);

// Despesas (CRUD) 
$router->get('/api/despesas',        [DespesaController::class, 'index'],   ['auth']);
$router->get('/api/despesas/{id}',   [DespesaController::class, 'show'],    ['auth']);
$router->post('/api/despesas',       [DespesaController::class, 'store'],   ['auth']);
$router->put('/api/despesas/{id}',   [DespesaController::class, 'update'],  ['auth']);
$router->delete('/api/despesas/{id}',[DespesaController::class, 'destroy'], ['auth', 'admin']);

// Endereços (CRUD) 
$router->get('/api/enderecos',        [EnderecoController::class, 'index'],   ['auth']);
$router->get('/api/enderecos/{id}',   [EnderecoController::class, 'show'],    ['auth']);
$router->post('/api/enderecos',       [EnderecoController::class, 'store'],   ['auth']);
$router->put('/api/enderecos/{id}',   [EnderecoController::class, 'update'],  ['auth']);
$router->delete('/api/enderecos/{id}',[EnderecoController::class, 'destroy'], ['auth']);

// Compras (criar/listar/ver/excluir) 
$router->get('/api/compras',         [CompraController::class, 'index'],    ['auth']);
$router->get('/api/compras/{id}',    [CompraController::class, 'show'],     ['auth']);
$router->post('/api/compras',        [CompraController::class, 'store'],    ['auth', 'admin']);
$router->delete('/api/compras/{id}', [CompraController::class, 'destroy'],  ['auth', 'admin']);

// Dashboard 
$router->get('/api/dashboard',       [DashboardController::class, 'resumo'],['auth']);

?>
