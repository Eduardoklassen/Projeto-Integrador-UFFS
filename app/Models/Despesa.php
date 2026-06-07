<?php

namespace App\Models;
use App\Core\Database;
use PDO;

/**
 * Despesa = saída de dinheiro. Pode vir de uma compra id_compra e sempre
 * sai de um caixa.
 */
class Despesa
{
    private PDO $db;

    private const ORDENAVEIS = ['valor', 'data_despesa', 'tipo_movimentacao'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(array $opts = []): array
    {
        $sql = 'SELECT d.*, c.descricao AS caixa_descricao
                FROM despesa d
                JOIN caixa c ON c.id_caixa = d.id_caixa
                WHERE 1=1';
        $bind = [];

        if (!empty($opts['busca'])) {
            $sql .= ' AND d.observacao LIKE :busca';
            $bind['busca'] = '%' . $opts['busca'] . '%';
        }

        $ordenarPor = in_array($opts['ordenar'] ?? '', self::ORDENAVEIS, true)
            ? $opts['ordenar'] : 'data_despesa';
        $direcao = strtoupper($opts['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY d.{$ordenarPor} {$direcao}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM despesa WHERE id_despesa = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO despesa (id_compra, id_caixa, valor, tipo_movimentacao, data_despesa, observacao)
             VALUES (:compra, :caixa, :valor, :tipo, :data, :obs)'
        );
        $stmt->execute([
            'compra' => $d['id_compra'] ?? null,
            'caixa'  => $d['id_caixa'],
            'valor'  => $d['valor'],
            'tipo'   => $d['tipo_movimentacao'] ?? 'outro',
            'data'   => $d['data_despesa'] ?? date('Y-m-d'),
            'obs'    => $d['observacao'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE despesa SET id_compra = :compra, id_caixa = :caixa, valor = :valor,
             tipo_movimentacao = :tipo, data_despesa = :data, observacao = :obs
             WHERE id_despesa = :id'
        );
        return $stmt->execute([
            'id'     => $id,
            'compra' => $d['id_compra'] ?? null,
            'caixa'  => $d['id_caixa'],
            'valor'  => $d['valor'],
            'tipo'   => $d['tipo_movimentacao'] ?? 'outro',
            'data'   => $d['data_despesa'] ?? date('Y-m-d'),
            'obs'    => $d['observacao'] ?? null,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM despesa WHERE id_despesa = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM despesa')->fetchColumn();
    }

    //Soma total de despesas
    public function somaTotal(): float
    {
        return (float) $this->db->query('SELECT COALESCE(SUM(valor),0) FROM despesa')->fetchColumn();
    }
}

?>