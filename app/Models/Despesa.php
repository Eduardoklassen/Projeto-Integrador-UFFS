<?php

namespace App\Models;

use App\Core\Database;
use PDO;


 // Model Despesa — simétrico da Receita: SUBTRAI do saldo do
 // caixa ao criar; estorna ao excluir/alterar. Mesma decisão de
 // transação (lançamento e saldo nunca dessincronizam).
 
class Despesa
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        return $this->db->query(
            'SELECT d.*, d.data_despesa AS criado_em, c.descricao AS caixa_descricao
             FROM despesa d
             JOIN caixa c ON c.id_caixa = d.id_caixa
             ORDER BY d.id_despesa DESC'
        )->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM despesa WHERE id_despesa = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO despesa (id_caixa, valor, tipo_movimentacao, observacao)
                 VALUES (:caixa, :valor, :tipo, :obs)'
            );
            $stmt->execute([
                'caixa' => $d['id_caixa'],
                'valor' => $d['valor'],
                'tipo'  => $d['tipo_movimentacao'] ?? null,
                'obs'   => $d['observacao'] ?? null,
            ]);
            $id = (int) $this->db->lastInsertId();

            (new Caixa())->movimentar((int) $d['id_caixa'], -(float) $d['valor']);

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function atualizar(int $id, array $d): void
    {
        $this->db->beginTransaction();
        try {
            $atual = $this->buscar($id);

            $stmt = $this->db->prepare(
                'UPDATE despesa SET id_caixa = :caixa, valor = :valor,
                 tipo_movimentacao = :tipo, observacao = :obs
                 WHERE id_despesa = :id'
            );
            $stmt->execute([
                'id'    => $id,
                'caixa' => $d['id_caixa'],
                'valor' => $d['valor'],
                'tipo'  => $d['tipo_movimentacao'] ?? null,
                'obs'   => $d['observacao'] ?? null,
            ]);

            $caixa = new Caixa();
            $caixa->movimentar((int) $atual['id_caixa'], +(float) $atual['valor']); // estorna
            $caixa->movimentar((int) $d['id_caixa'], -(float) $d['valor']);          // reaplica

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $atual = $this->buscar($id);
            $stmt = $this->db->prepare('DELETE FROM despesa WHERE id_despesa = :id');
            $stmt->execute(['id' => $id]);

            (new Caixa())->movimentar((int) $atual['id_caixa'], +(float) $atual['valor']);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
