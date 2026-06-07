<?php

namespace App\Models;
use App\Core\Database;
use PDO;

//Caixa = conta onde entram receitas e saem despesas.
class Caixa
{
    private PDO $db;

    private const ORDENAVEIS = ['descricao', 'saldo', 'criado_em'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(array $opts = []): array
    {
        $sql = 'SELECT * FROM caixa WHERE 1=1';
        $bind = [];

        if (!empty($opts['busca'])) {
            $sql .= ' AND descricao LIKE :busca';
            $bind['busca'] = '%' . $opts['busca'] . '%';
        }

        $ordenarPor = in_array($opts['ordenar'] ?? '', self::ORDENAVEIS, true)
            ? $opts['ordenar'] : 'descricao';
        $direcao = strtoupper($opts['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$ordenarPor} {$direcao}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
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
            'INSERT INTO caixa (descricao, saldo) VALUES (:desc, :saldo)'
        );
        $stmt->execute([
            'desc'  => $d['descricao'],
            'saldo' => $d['saldo'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE caixa SET descricao = :desc, saldo = :saldo WHERE id_caixa = :id'
        );
        return $stmt->execute([
            'id'    => $id,
            'desc'  => $d['descricao'],
            'saldo' => $d['saldo'] ?? 0,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM caixa WHERE id_caixa = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM caixa')->fetchColumn();
    }
}

?>