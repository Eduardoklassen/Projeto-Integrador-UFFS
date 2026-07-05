<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

 // A compra representa a entrada de materiais no estoque. 
 // Ela aumenta o estoque, é processada em uma transação completa e não pode ser atualizada; 
 // se precisar corrigir, é removida com estorno do estoque e criada novamente.
class Compra
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        return $this->db->query(
            'SELECT c.*, f.nome AS fornecedor_nome
             FROM compra c
             JOIN fornecedor f ON f.id_fornecedor = c.id_fornecedor
             ORDER BY c.id_compra DESC'
        )->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, f.nome AS fornecedor_nome
             FROM compra c
             JOIN fornecedor f ON f.id_fornecedor = c.id_fornecedor
             WHERE c.id_compra = :id'
        );
        $stmt->execute(['id' => $id]);
        $compra = $stmt->fetch();
        if (!$compra) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT i.*, m.nome AS material_nome
             FROM compra_material i
             JOIN material m ON m.id_material = i.id_material
             WHERE i.id_compra = :id'
        );
        $stmt->execute(['id' => $id]);
        $compra['itens'] = $stmt->fetchAll();

        return $compra;
    }

    /**
     * Cria compra + itens e dá ENTRADA no estoque dos materiais.
     * @param array $itens [{ id_material, quantidade, custo_unitario }]
     */
    public function criar(int $idFornecedor, array $itens): int
    {
        $this->db->beginTransaction();
        try {
            $total = 0.0;
            $existe = $this->db->prepare('SELECT nome FROM material WHERE id_material = :id');
            foreach ($itens as $item) {
                $existe->execute(['id' => $item['id_material']]);
                if (!$existe->fetch()) {
                    throw new RuntimeException("Material #{$item['id_material']} não encontrado.");
                }
                $total += $item['quantidade'] * $item['custo_unitario'];
            }

            $stmt = $this->db->prepare(
                'INSERT INTO compra (id_fornecedor, valor_total) VALUES (:fornecedor, :total)'
            );
            $stmt->execute(['fornecedor' => $idFornecedor, 'total' => $total]);
            $idCompra = (int) $this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO compra_material (id_compra, id_material, quantidade, custo_unitario)
                 VALUES (:compra, :material, :qtd, :custo)'
            );
            $entrada = $this->db->prepare(
                'UPDATE material SET estoque = estoque + :qtd WHERE id_material = :id'
            );
            foreach ($itens as $item) {
                $insItem->execute([
                    'compra'   => $idCompra,
                    'material' => $item['id_material'],
                    'qtd'      => $item['quantidade'],
                    'custo'    => $item['custo_unitario'],
                ]);
                $entrada->execute(['qtd' => $item['quantidade'], 'id' => $item['id_material']]);
            }

            $this->db->commit();
            return $idCompra;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Exclui estornando a entrada de estoque (simetria com criar). */
    public function excluir(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT id_material, quantidade FROM compra_material WHERE id_compra = :id'
            );
            $stmt->execute(['id' => $id]);
            $estorno = $this->db->prepare(
                'UPDATE material SET estoque = estoque - :qtd WHERE id_material = :id'
            );
            foreach ($stmt->fetchAll() as $item) {
                $estorno->execute(['qtd' => $item['quantidade'], 'id' => $item['id_material']]);
            }

            $del = $this->db->prepare('DELETE FROM compra WHERE id_compra = :id');
            $del->execute(['id' => $id]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM compra')->fetchColumn();
    }
}
