<?php

namespace App\Core;

// Encapsula os dados da requisição HTTP recebida.
class Request
{
    public string $method;
    public string $path;
    public array $params = [];   // parâmetros de rota (id)
    public array $query;         // query string
    private ?array $body = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query  = $_GET;
    }

    //Retorna o corpo JSON da requisição como array associativo.
    public function body(): array
    {
        if ($this->body === null) {
            $raw = file_get_contents('php://input');
            $this->body = json_decode($raw, true) ?? [];
        }
        return $this->body;
    }

    public function input(string $key, $default = null)
    {
        return $this->body()[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }
}

?>