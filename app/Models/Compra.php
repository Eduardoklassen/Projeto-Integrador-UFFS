<?php

namespace App\Models;
use App\Core\Database;
use PDO;

/**
 * Compra = aquisição de materiais de um fornecedor.
 * Uma compra contém vários materiais (tabela associativa compra_material).
 * Ao criar, usamos TRANSAÇÃO: ou grava a compra + todos os itens, ou nada.
 * Também somamos a quantidade comprada ao estoque de cada material.
 */
class Compra
{
    private PDO $db;

    private const ORDENAVEIS = ['data_compra', 'valor_total'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(array $opts = []): array
    {
        $sql = 'SELECT c.*, f.nome AS fornecedor_nome
                FROM compra c
                JOIN fornecedor f ON f.id_fornecedor = c.id_fornecedor
                WHERE 1=1';
        $bind = [];

        if (!empty($opts['busca'])) {
            $sql .= ' AND f.nome LIKE :busca';
            $bind['busca'] = '%' . $opts['busca'] . '%';
        }

        $ordenarPor = in_array($opts['ordenar'] ?? '', self::ORDENAVEIS, true)
            ? $opts['ordenar'] : 'data_compra';
        $direcao = strtoupper($opts['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY c.{$ordenarPor} {$direcao}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    // Retorna a compra com a lista de materiais itens dela. 
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

        $itens = $this->db->prepare(
            'SELECT cm.*, m.nome AS material_nome
             FROM compra_material cm
             JOIN material m ON m.id_material = cm.id_material
             WHERE cm.id_compra = :id'
        );
        $itens->execute(['id' => $id]);
        $compra['itens'] = $itens->fetchAll();

        return $compra;
    }

    /**
     * Cria uma compra com seus itens.
     * id_fornecedor e itens = ['id_material','quantidade','custo_unitario']
     */
    public function criar(array $d): int
    {
        $itens = $d['itens'] ?? [];

        $this->db->beginTransaction();
        try {
            // valor_total é a soma dos sub_totais dos itens
            $total = 0.0;
            foreach ($itens as $it) {
                $total += (float) $it['quantidade'] * (float) $it['custo_unitario'];
            }

            $stmt = $this->db->prepare(
                'INSERT INTO compra (id_fornecedor, valor_total, data_compra)
                 VALUES (:forn, :total, :data)'
            );
            $stmt->execute([
                'forn'  => $d['id_fornecedor'],
                'total' => $total,
                'data'  => $d['data_compra'] ?? date('Y-m-d'),
            ]);
            $idCompra = (int) $this->db->lastInsertId();

            $stmtItem = $this->db->prepare(
                'INSERT INTO compra_material (id_compra, id_material, quantidade, custo_unitario)
                 VALUES (:compra, :material, :qtd, :custo)'
            );
            $stmtEstoque = $this->db->prepare(
                'UPDATE material SET estoque = estoque + :qtd WHERE id_material = :material'
            );

            foreach ($itens as $it) {
                $stmtItem->execute([
                    'compra'   => $idCompra,
                    'material' => $it['id_material'],
                    'qtd'      => $it['quantidade'],
                    'custo'    => $it['custo_unitario'],
                ]);
                // entrada de material: soma ao estoque
                $stmtEstoque->execute([
                    'qtd'      => $it['quantidade'],
                    'material' => $it['id_material'],
                ]);
            }

            $this->db->commit();
            return $idCompra;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): bool
    {
        // os itens compra_material saem em cascata pela FK ON DELETE CASCADE
        $stmt = $this->db->prepare('DELETE FROM compra WHERE id_compra = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM compra')->fetchColumn();
    }
}

?>