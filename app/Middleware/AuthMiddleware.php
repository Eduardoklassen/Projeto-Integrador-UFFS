<?php

namespace App\Middleware;
use App\Core\Request;
use App\Helpers\Response;
use App\Services\AuthService;

/**
 * Verifica se a requisição traz um token JWT válido.
 * Em caso afirmativo, anexa os dados do usuário autenticado à requisição.
 */
class AuthMiddleware
{
    public function handle(Request $request): void
    {
        $token = $request->bearerToken();

        if (!$token) {
            Response::error('Token de autenticação não fornecido', 401);
        }

        $payload = (new AuthService())->validarToken($token);

        if (!$payload) {
            Response::error('Token inválido ou expirado', 401);
        }

        // Disponibiliza o usuário autenticado para os controllers/middlewares
        $request->params['_auth'] = [
            'id'   => $payload->sub,
            'nome' => $payload->nome,
            'tipo' => $payload->tipo,
        ];
    }
}

?>
