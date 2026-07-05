<?php

namespace App\Models;

use App\Core\Database;
use PDO;

  // Receita — MOVIMENTA O SALDO DO CAIXA.
  // um lançamento ele precisa refletir no saldo do caixa. Criar receita SOMA no caixa
  // excluir/alterar estorna e reaplica. Tudo em transação, para o
  // lançamento e o saldo nunca ficarem dessincronizados.

class Receita
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        return $this->db->query(
            'SELECT r.*, r.data_receita AS criado_em, c.descricao AS caixa_descricao
             FROM receita r
             JOIN caixa c ON c.id_caixa = r.id_caixa
             ORDER BY r.id_receita DESC'
        )->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM receita WHERE id_receita = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO receita (id_caixa, id_pedido, valor, forma_pagamento, observacao)
                 VALUES (:caixa, :pedido, :valor, :forma, :obs)'
            );
            $stmt->execute([
                'caixa'  => $d['id_caixa'],
                'pedido' => !empty($d['id_pedido']) ? $d['id_pedido'] : null,
                'valor'  => $d['valor'],
                'forma'  => $d['forma_pagamento'] ?? null,
                'obs'    => $d['observacao'] ?? null,
            ]);
            $id = (int) $this->db->lastInsertId();

            (new Caixa())->movimentar((int) $d['id_caixa'], +(float) $d['valor']);

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Atualiza estornando o valor antigo e aplicando o novo. 
    public function atualizar(int $id, array $d): void
    {
        $this->db->beginTransaction();
        try {
            $atual = $this->buscar($id);

            $stmt = $this->db->prepare(
                'UPDATE receita SET id_caixa = :caixa, id_pedido = :pedido,
                 valor = :valor, forma_pagamento = :forma, observacao = :obs
                 WHERE id_receita = :id'
            );
            $stmt->execute([
                'id'     => $id,
                'caixa'  => $d['id_caixa'],
                'pedido' => !empty($d['id_pedido']) ? $d['id_pedido'] : null,
                'valor'  => $d['valor'],
                'forma'  => $d['forma_pagamento'] ?? null,
                'obs'    => $d['observacao'] ?? null,
            ]);

            $caixa = new Caixa();
            $caixa->movimentar((int) $atual['id_caixa'], -(float) $atual['valor']); // estorna
            $caixa->movimentar((int) $d['id_caixa'], +(float) $d['valor']);          // reaplica

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
            $stmt = $this->db->prepare('DELETE FROM receita WHERE id_receita = :id');
            $stmt->execute(['id' => $id]);

            (new Caixa())->movimentar((int) $atual['id_caixa'], -(float) $atual['valor']);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
