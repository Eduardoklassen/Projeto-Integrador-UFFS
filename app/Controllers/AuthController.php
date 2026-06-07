<?php

namespace App\Controllers;
use App\Core\Request;
use App\Helpers\Response;
use App\Models\Usuario;
use App\Services\AuthService;
class AuthController
{
    public function login(Request $request): void
    {
        $email = $request->input('email');
        $senha = $request->input('senha');

        if (!$email || !$senha) {
            Response::error('E-mail e senha são obrigatórios', 422);
        }

        $usuario = (new Usuario())->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
            Response::error('Credenciais inválidas', 401);
        }

        $token = (new AuthService())->gerarToken($usuario);

        Response::success([
            'token'   => $token,
            'usuario' => [
                'id'   => $usuario['id_usuario'],
                'nome' => $usuario['nome_usuario'],
                'tipo' => $usuario['tipo_usuario'],
            ],
        ], 'Login realizado com sucesso');
    }

    public function register(Request $request): void
    {
        $dados = $request->body();

        if (empty($dados['email']) || empty($dados['senha']) || empty($dados['nome_usuario'])) {
            Response::error('Nome, e-mail e senha são obrigatórios', 422);
        }

        $model = new Usuario();
        if ($model->buscarPorEmail($dados['email'])) {
            Response::error('E-mail já cadastrado', 409);
        }

        $id = $model->criar($dados);
        Response::success(['id_usuario' => $id], 'Usuário criado com sucesso', 201);
    }
}

?>