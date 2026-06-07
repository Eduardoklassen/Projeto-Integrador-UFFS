<?php

namespace App\Core;
use App\Helpers\Response;

// Roteador simples: mapeia método HTTP + caminho para um controller e ação,
// com suporte a middlewares (auth, perfis) e parâmetros de rota (id).
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array $handler, array $middlewares = []): void
    {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, array $handler, array $middlewares): void
    {
        // Converte (id) em grupo de captura nomeado
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = compact('method', 'pattern', 'handler', 'middlewares');
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            if (preg_match($route['pattern'], $request->path, $matches)) {
                // Extrai apenas parâmetros nomeados
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $request->params[$key] = $value;
                    }
                }

                // Executa middlewares
                foreach ($route['middlewares'] as $mw) {
                    $this->runMiddleware($mw, $request);
                }

                // Executa controller
                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->$action($request);
                return;
            }
        }

        Response::error('Rota não encontrada', 404);
    }

    private function runMiddleware(string $name, Request $request): void
    {
        switch ($name) {
            case 'auth':
                (new \App\Middleware\AuthMiddleware())->handle($request);
                break;
            case 'admin':
                (new \App\Middleware\RoleMiddleware())->handle($request, 'admin');
                break;
        }
    }
}

?>