<?php

namespace App\Models;
use App\Core\Database;
use PDO;

/**
 * Receita = entrada de dinheiro. Pode vir de um pedido (id_pedido) e sempre
 * entra em um caixa (id_caixa). O listar traz dados do caixa por JOIN.
 */
class Receita
{
    private PDO $db;

    private const ORDENAVEIS = ['valor', 'data_receita', 'forma_pagamento'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    public function listar(array $opts = []): array
    {
        $sql = 'SELECT r.*, c.descricao AS caixa_descricao
                FROM receita r
                JOIN caixa c ON c.id_caixa = r.id_caixa
                WHERE 1=1';
        $bind = [];

        if (!empty($opts['busca'])) {
            $sql .= ' AND r.observacao LIKE :busca';
            $bind['busca'] = '%' . $opts['busca'] . '%';
        }

        $ordenarPor = in_array($opts['ordenar'] ?? '', self::ORDENAVEIS, true)
            ? $opts['ordenar'] : 'data_receita';
        $direcao = strtoupper($opts['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY r.{$ordenarPor} {$direcao}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM receita WHERE id_receita = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO receita (id_pedido, id_caixa, valor, forma_pagamento, data_receita, observacao)
             VALUES (:pedido, :caixa, :valor, :forma, :data, :obs)'
        );
        $stmt->execute([
            'pedido' => $d['id_pedido'] ?? null,
            'caixa'  => $d['id_caixa'],
            'valor'  => $d['valor'],
            'forma'  => $d['forma_pagamento'] ?? 'outro',
            'data'   => $d['data_receita'] ?? date('Y-m-d'),
            'obs'    => $d['observacao'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE receita SET id_pedido = :pedido, id_caixa = :caixa, valor = :valor,
             forma_pagamento = :forma, data_receita = :data, observacao = :obs
             WHERE id_receita = :id'
        );
        return $stmt->execute([
            'id'     => $id,
            'pedido' => $d['id_pedido'] ?? null,
            'caixa'  => $d['id_caixa'],
            'valor'  => $d['valor'],
            'forma'  => $d['forma_pagamento'] ?? 'outro',
            'data'   => $d['data_receita'] ?? date('Y-m-d'),
            'obs'    => $d['observacao'] ?? null,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM receita WHERE id_receita = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM receita')->fetchColumn();
    }

    // Soma total de receitas
    public function somaTotal(): float
    {
        return (float) $this->db->query('SELECT COALESCE(SUM(valor),0) FROM receita')->fetchColumn();
    }
}

?>