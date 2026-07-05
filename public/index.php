<?php


  //Front Controller — único ponto de entrada da API (hardened).
 
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;
use App\Helpers\Response;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

  // CORS (config/cors.php) 
  // protege a API contra um problema de CORS mal configurado
  // um site malicioso pode fazer requisições à API pelo navegador
  // Para evitar isso, a API deve aceitar apenas as origens cadastradas na lista branca
  // através de allow_origin_list, e nunca usar * quando há credenciais envolvidas
  // Isso reduz o risco de acesso não autorizado de outros domínios

$cors = require __DIR__ . '/../config/cors.php';
$origem = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origem, $cors['allow_origin_list'], true)) {
    header('Access-Control-Allow-Origin: ' . $origem);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: ' . $cors['allow_methods']);
header('Access-Control-Allow-Headers: ' . $cors['allow_headers']);

// Cabeçalhos de segurança (defesa em profundidade) 
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Erros não tratados: genérico ao cliente, detalhado no log
// Quando ocorre um erro interno no servidor, a API não deve mostrar detalhes técnicos
// Em vez disso, ela responde com uma mensagem genérica
// enquanto o detalhe real é registrado no log do servidor para análise técnica
// evita vazamento de informação

set_exception_handler(function (\Throwable $e) {
    error_log('[ERRO 500] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Erro interno do servidor', 500);
});

$router = new Router();
require __DIR__ . '/../routes/api.php';

$router->dispatch(new Request());
