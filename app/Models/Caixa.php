<?php

namespace App\Models;

use App\Core\Database;
use PDO;


 // Caixa é a conta de saldo, onde receitas aumentam e despesas diminuem; 
 // o saldo é calculado pelos lançamentos, não editado manualmente.
class Caixa
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        return $this->db->query('SELECT * FROM caixa ORDER BY id_caixa')->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM caixa WHERE id_caixa = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO caixa (descricao, saldo) VALUES (:descricao, :saldo)'
        );
        $stmt->execute([
            'descricao' => $d['descricao'],
            'saldo'     => $d['saldo'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Atualiza apenas a descrição (saldo é movimentado por receita/despesa). */
    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE caixa SET descricao = :descricao WHERE id_caixa = :id'
        );
        return $stmt->execute(['id' => $id, 'descricao' => $d['descricao']]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM caixa WHERE id_caixa = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** Soma/subtrai do saldo (usado por Receita e Despesa). */
    public function movimentar(int $id, float $delta): void
    {
        $stmt = $this->db->prepare(
            'UPDATE caixa SET saldo = saldo + :delta WHERE id_caixa = :id'
        );
        $stmt->execute(['delta' => $delta, 'id' => $id]);
    }
}
