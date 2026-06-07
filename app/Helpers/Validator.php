<?php

namespace App\Helpers;

// Validação simples de dados de entrada.
//Acumula erros e permite checagem ao final.
class Validator
{
    private array $erros = [];
    private array $dados;

    public function __construct(array $dados)
    {
        $this->dados = $dados;
    }

    //Campo obrigatório e não vazio. 
    public function obrigatorio(string $campo, string $rotulo = null): self
    {
        $rotulo = $rotulo ?? $campo;
        if (!isset($this->dados[$campo]) || $this->dados[$campo] === '') {
            $this->erros[$campo] = "O campo {$rotulo} é obrigatório.";
        }
        return $this;
    }

    // Valida formato de email. 
    public function email(string $campo): self
    {
        if (!empty($this->dados[$campo]) &&
            !filter_var($this->dados[$campo], FILTER_VALIDATE_EMAIL)) {
            $this->erros[$campo] = "O campo {$campo} deve conter um e-mail válido.";
        }
        return $this;
    }

    //Valor numérico maior ou igual a zero. 
    public function numericoPositivo(string $campo): self
    {
        if (isset($this->dados[$campo]) &&
            (!is_numeric($this->dados[$campo]) || $this->dados[$campo] < 0)) {
            $this->erros[$campo] = "O campo {$campo} deve ser um número positivo.";
        }
        return $this;
    }

    // Valida o campo contra uma expressão regular.
    //Só valida se o campo estiver preenchido (use junto com obrigatorio se for necessário).
    public function formato(string $campo, string $regex, string $mensagem = null): self
    {
        if (!empty($this->dados[$campo]) && !preg_match($regex, (string) $this->dados[$campo])) {
            $this->erros[$campo] = $mensagem ?? "O campo {$campo} está em formato inválido.";
        }
        return $this;
    }

    // Valida o tamanho (nº de caracteres) do campo entre min e max.
    // Passe null em max para não limitar o máximo.
    public function tamanho(string $campo, int $min, ?int $max = null): self
    {
        if (!empty($this->dados[$campo])) {
            $len = mb_strlen((string) $this->dados[$campo]);
            if ($len < $min) {
                $this->erros[$campo] = "O campo {$campo} deve ter ao menos {$min} caracteres.";
            } elseif ($max !== null && $len > $max) {
                $this->erros[$campo] = "O campo {$campo} deve ter no máximo {$max} caracteres.";
            }
        }
        return $this;
    }

    public function passou(): bool
    {
        return empty($this->erros);
    }

    public function erros(): array
    {
        return $this->erros;
    }
}

?>