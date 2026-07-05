<?php

namespace App\Helpers;

/**
 * Rate limiter simples baseado em arquivos.
 * Um contador em arquivo por chave (ex.: "login:187.1.2.3") entrega
 * proteção real contra brute-force
 */
class RateLimiter
{
    private string $dir;

    public function __construct()
    {
        // storage/ fica FORA de public/ — nunca acessível via URL.
        $this->dir = __DIR__ . '/../../storage/ratelimit';
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0770, true);
        }
    }

    private function arquivo(string $chave): string
    {
        // hash no nome evita path traversal a partir da chave.
        return $this->dir . '/' . hash('sha256', $chave) . '.json';
    }

    private function ler(string $chave): array
    {
        $arq = $this->arquivo($chave);
        if (!is_file($arq)) {
            return [];
        }
        $dados = json_decode((string) file_get_contents($arq), true);
        return is_array($dados) ? $dados : [];
    }

    /** Está bloqueado? (nº de falhas na janela >= limite) */
    public function bloqueado(string $chave, int $limite, int $janelaSegundos): bool
    {
        $agora = time();
        $falhas = array_filter(
            $this->ler($chave),
            fn ($ts) => ($agora - $ts) < $janelaSegundos
        );
        return count($falhas) >= $limite;
    }

    /** Registra uma falha agora e persiste (descartando as expiradas). */
    public function registrarFalha(string $chave, int $janelaSegundos): void
    {
        $agora = time();
        $falhas = array_filter(
            $this->ler($chave),
            fn ($ts) => ($agora - $ts) < $janelaSegundos
        );
        $falhas[] = $agora;
        file_put_contents($this->arquivo($chave), json_encode(array_values($falhas)), LOCK_EX);
    }

    /** Zera o contador (ex.: após login bem-sucedido). */
    public function limpar(string $chave): void
    {
        $arq = $this->arquivo($chave);
        if (is_file($arq)) {
            @unlink($arq);
        }
    }
}
