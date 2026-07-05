<?php

namespace App\Core;

  // Encapsula os dados da requisição HTTP recebida.
 
class Request
{
    public string $method;
    public string $path;
    public array $params = [];
    public array $query;
    private ?array $body = null;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query  = $_GET;
    }

    public function body(): array
    {
        if ($this->body === null) {
            $raw = file_get_contents('php://input');
            $decodificado = json_decode($raw, true);
            // Se o JSON vier malformado, tratamos como corpo vazio em
            // vez de deixar um TypeError explodir (robustez + não vaza erro).
            $this->body = is_array($decodificado) ? $decodificado : [];
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

    /**
     * IP de origem da requisição (para rate limiting).
     * SEGURANÇA: usamos REMOTE_ADDR, que é o IP real da conexão TCP e
     * NÃO pode ser forjado pelo cliente.
     */
    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
