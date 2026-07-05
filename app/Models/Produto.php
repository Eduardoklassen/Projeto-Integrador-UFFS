<?php

namespace App\Models;

use App\Core\Database;
use PDO;


  // Model Produto — correção do campo 'ativo'.
  // Como o front mandava 0 para inativo, o sistema ignorava esse valor e sempre mantinha o padrão 1. 
  // A correção foi garantir que esse campo seja enviado corretamente, convertendo o valor para inteiro 
  // para evitar problemas com strings como "0". 

class Produto
{
    private PDO $db;

    //Campos permitidos para ordenação (proteção contra SQL injection no ORDER BY) 
    private const ORDENAVEIS = ['nome', 'preco_final', 'estoque', 'criado_em'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

     // Lista produtos com filtro textual e ordenação opcionais.
     // filtro Ativos/Inativos é aplicado no front decisãopessoal de UX:
     // o administrador precisa enxergar os inativos para reativá-los).
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
            'INSERT INTO produto (nome, preco_final, estoque, observacao, ativo)
             VALUES (:nome, :preco, :estoque, :obs, :ativo)'
        );
        $stmt->execute([
            'nome'    => $d['nome'],
            'preco'   => $d['preco_final'] ?? 0,
            'estoque' => $d['estoque'] ?? 0,
            'obs'     => $d['observacao'] ?? null,
            'ativo'   => isset($d['ativo']) ? (int) $d['ativo'] : 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE produto SET nome = :nome, preco_final = :preco,
             estoque = :estoque, observacao = :obs, ativo = :ativo
             WHERE id_produto = :id'
        );
        return $stmt->execute([
            'id'      => $id,
            'nome'    => $d['nome'],
            'preco'   => $d['preco_final'] ?? 0,
            'estoque' => $d['estoque'] ?? 0,
            'obs'     => $d['observacao'] ?? null,
            'ativo'   => isset($d['ativo']) ? (int) $d['ativo'] : 1,
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
