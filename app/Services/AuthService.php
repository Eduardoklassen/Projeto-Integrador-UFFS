<?php

namespace App\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

//Geração e validação de tokens JWT.
 
class AuthService
{
    private string $secret;
    private int $expires;

    public function __construct()
    {
        $cfg = require __DIR__ . '/../../config/app.php';
        $this->secret  = $cfg['jwt_secret'];
        $this->expires = $cfg['jwt_expires'];
    }

    public function gerarToken(array $usuario): string
    {
        $agora = time();
        $payload = [
            'iat' => $agora,
            'exp' => $agora + $this->expires,
            'sub' => $usuario['id_usuario'],
            'nome' => $usuario['nome_usuario'],
            'tipo' => $usuario['tipo_usuario'],
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    //Valida o token e retorna o payload, ou null se inválido.
    public function validarToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable $e) {
            return null;
        }
    }
}

?>
