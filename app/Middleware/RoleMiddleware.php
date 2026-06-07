<?php

namespace App\Middleware;
use App\Core\Request;
use App\Helpers\Response;


//Verifica se o usuário autenticado possui o perfil exigido.
// Deve ser usado SEMPRE depois do AuthMiddleware.

class RoleMiddleware
{
    public function handle(Request $request, string $perfilExigido): void
    {
        $auth = $request->params['_auth'] ?? null;

        if (!$auth) {
            Response::error('Usuário não autenticado', 401);
        }

        if ($auth['tipo'] !== $perfilExigido) {
            Response::error('Acesso negado: permissão insuficiente', 403);
        }
    }
}

?>