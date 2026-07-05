<?php

namespace App\Models;

use App\Core\Database;
use PDO;


  // Model Material — CRUD completo, com 'ativo' persistido
  // desde o início (lição aprendida do bug em Produto).
 
class Material
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        return $this->db->query('SELECT * FROM material ORDER BY nome')->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM material WHERE id_material = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO material (nome, unidade_medida, custo_unitario, estoque, ativo)
             VALUES (:nome, :unidade, :custo, :estoque, :ativo)'
        );
        $stmt->execute([
            'nome'    => $d['nome'],
            'unidade' => $d['unidade_medida'] ?? 'un',
            'custo'   => $d['custo_unitario'] ?? 0,
            'estoque' => $d['estoque'] ?? 0,
            // isset aceita 0 e "0".
            'ativo'   => isset($d['ativo']) ? (int) $d['ativo'] : 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE material SET nome = :nome, unidade_medida = :unidade,
             custo_unitario = :custo, estoque = :estoque, ativo = :ativo
             WHERE id_material = :id'
        );
        return $stmt->execute([
            'id'      => $id,
            'nome'    => $d['nome'],
            'unidade' => $d['unidade_medida'] ?? 'un',
            'custo'   => $d['custo_unitario'] ?? 0,
            'estoque' => $d['estoque'] ?? 0,
            'ativo'   => isset($d['ativo']) ? (int) $d['ativo'] : 1,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM material WHERE id_material = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM material')->fetchColumn();
    }
}
