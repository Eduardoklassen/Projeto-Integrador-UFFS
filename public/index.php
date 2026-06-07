<?php

// Front Controller — único ponto de entrada da API.
 
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;
use App\Helpers\Response;
use Dotenv\Dotenv;

// Carrega variáveis de ambiente 
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// (config/cors.php)
$cors = require __DIR__ . '/../config/cors.php';
header('Access-Control-Allow-Origin: ' . $cors['allow_origin']);
header('Access-Control-Allow-Methods: ' . $cors['allow_methods']);
header('Access-Control-Allow-Headers: ' . $cors['allow_headers']);

// Responde requisições preflight do navegador
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Captura erros não tratados como JSON 
set_exception_handler(function (\Throwable $e) {
    Response::error('Erro interno do servidor', 500, $e->getMessage());
});

// Inicializa o roteador e carrega as rotas 
$router = new Router();
require __DIR__ . '/../routes/api.php';

// Despacha a requisição 
$router->dispatch(new Request());
