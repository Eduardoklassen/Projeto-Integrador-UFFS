<?php

namespace App\Models;

use App\Core\Database;
use PDO;

 // O model ganhou CRUD completo: agora cria, busca, atualiza e exclui clientes, 
 // em vez de só listar e contar. A implementação foi feita com segurança 
 // e no mesmo padrão do model de produtos.
class Cliente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        return $this->db->query('SELECT * FROM cliente ORDER BY nome')->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cliente WHERE id_cliente = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cliente (nome, email, telefone, cpf_cnpj)
             VALUES (:nome, :email, :telefone, :cpf_cnpj)'
        );
        $stmt->execute([
            'nome'     => $d['nome'],
            // Campos opcionais viram NULL (e não string vazia) para
            // manter o banco limpo e o UNIQUE de cpf_cnpj coerente.
            'email'    => ($d['email'] ?? '') !== '' ? $d['email'] : null,
            'telefone' => ($d['telefone'] ?? '') !== '' ? $d['telefone'] : null,
            'cpf_cnpj' => $d['cpf_cnpj'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE cliente SET nome = :nome, email = :email,
             telefone = :telefone, cpf_cnpj = :cpf_cnpj
             WHERE id_cliente = :id'
        );
        return $stmt->execute([
            'id'       => $id,
            'nome'     => $d['nome'],
            'email'    => ($d['email'] ?? '') !== '' ? $d['email'] : null,
            'telefone' => ($d['telefone'] ?? '') !== '' ? $d['telefone'] : null,
            'cpf_cnpj' => $d['cpf_cnpj'],
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cliente WHERE id_cliente = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM cliente')->fetchColumn();
    }
}
