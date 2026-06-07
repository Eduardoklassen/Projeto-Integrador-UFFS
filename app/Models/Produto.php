<?php

namespace App\Models;
use App\Core\Database;
use PDO;

class Produto
{
    private PDO $db;

    // Campos permitidos para ordenação
    private const ORDENAVEIS = ['nome', 'preco_final', 'estoque', 'criado_em'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    
    // Lista produtos com filtro textual e ordenação opcionais.
    
    public function listar(array $opts = []): array
    {
        $sql = 'SELECT * FROM produto WHERE 1=1';
        $bind = [];

        if (!empty($opts['busca'])) {
            $sql .= ' AND nome LIKE :busca';
            $bind['busca'] = '%' . $opts['busca'] . '%';
        }

        $ordenarPor = in_array($opts['ordenar'] ?? '', self::ORDENAVEIS, true)
            ? $opts['ordenar'] : 'nome';
        $direcao = strtoupper($opts['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$ordenarPor} {$direcao}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM produto WHERE id_produto = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO produto (nome, preco_final, estoque, observacao)
             VALUES (:nome, :preco, :estoque, :obs)'
        );
        $stmt->execute([
            'nome'    => $d['nome'],
            'preco'   => $d['preco_final'] ?? 0,
            'estoque' => $d['estoque'] ?? 0,
            'obs'     => $d['observacao'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE produto SET nome = :nome, preco_final = :preco,
             estoque = :estoque, observacao = :obs WHERE id_produto = :id'
        );
        return $stmt->execute([
            'id'      => $id,
            'nome'    => $d['nome'],
            'preco'   => $d['preco_final'] ?? 0,
            'estoque' => $d['estoque'] ?? 0,
            'obs'     => $d['observacao'] ?? null,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM produto WHERE id_produto = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM produto')->fetchColumn();
    }
}

?>