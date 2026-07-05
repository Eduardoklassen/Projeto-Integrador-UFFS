<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Model Fornecedor — correção do campo 'ativo'.
 *
 * O QUE mudou: mesmo bug do Model Produto — a coluna `ativo`
 * existe na tabela (TINYINT(1) DEFAULT 1) mas não era incluída
 * no INSERT/UPDATE, então a Situação escolhida na tela era
 * silenciosamente descartada.
 */
class Fornecedor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        return $this->db->query('SELECT * FROM fornecedor ORDER BY nome')->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM fornecedor WHERE id_fornecedor = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO fornecedor (nome, tipo_pessoa, cpf_cnpj, email, telefone, endereco, ativo)
             VALUES (:nome, :tipo, :doc, :email, :tel, :end, :ativo)'
        );
        $stmt->execute([
            'nome'  => $d['nome'],
            'tipo'  => $d['tipo_pessoa'] ?? 'juridica',
            'doc'   => $d['cpf_cnpj'] ?? null,
            'email' => $d['email'] ?? null,
            'tel'   => $d['telefone'] ?? null,
            'end'   => $d['endereco'] ?? null,
            // isset (e não ??/empty) proposital: aceita 0 e "0".
            'ativo' => isset($d['ativo']) ? (int) $d['ativo'] : 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE fornecedor SET nome = :nome, tipo_pessoa = :tipo, cpf_cnpj = :doc,
             email = :email, telefone = :tel, endereco = :end, ativo = :ativo
             WHERE id_fornecedor = :id'
        );
        return $stmt->execute([
            'id'    => $id,
            'nome'  => $d['nome'],
            'tipo'  => $d['tipo_pessoa'] ?? 'juridica',
            'doc'   => $d['cpf_cnpj'] ?? null,
            'email' => $d['email'] ?? null,
            'tel'   => $d['telefone'] ?? null,
            'end'   => $d['endereco'] ?? null,
            'ativo' => isset($d['ativo']) ? (int) $d['ativo'] : 1,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM fornecedor WHERE id_fornecedor = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM fornecedor')->fetchColumn();
    }
}
