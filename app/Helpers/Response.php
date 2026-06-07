<?php

namespace App\Helpers;

//Respostas JSON padronizadas.
 
class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data = null, string $msg = 'OK', int $status = 200): void
    {
        self::json([
            'sucesso'  => true,
            'mensagem' => $msg,
            'dados'    => $data,
        ], $status);
    }

    public static function error(string $msg = 'Erro', int $status = 400, $detalhes = null): void
    {
        self::json([
            'sucesso'  => false,
            'mensagem' => $msg,
            'detalhes' => $detalhes,
        ], $status);
    }

    //Resposta 201 Created, com header Location apontando para o recurso novo.
    public static function created($data = null, string $location = null, string $msg = 'Criado'): void
    {
        if ($location) {
            header('Location: ' . $location);
        }
        self::success($data, $msg, 201);
    }

    // Resposta 204 No Content — usada em exclusões bem-sucedidas.
    // Não envia corpo, conforme a semântica REST.
  
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }
}

?>