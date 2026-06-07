<?php

namespace App\Models;
use App\Core\Database;
use PDO;

class Fornecedor
{
    private PDO $db;

    // Campos permitidos para ordenação.Pensado na proteção contra SQL injection no ORDER BY 
    private const ORDENAVEIS = ['nome', 'tipo_pessoa', 'criado_em'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(array $opts = []): array
    {
        $sql = 'SELECT * FROM fornecedor WHERE 1=1';
        $bind = [];

        if (!empty($opts['busca'])) {
            $sql .= ' AND (nome LIKE :busca OR cpf_cnpj LIKE :busca)';
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
        $stmt = $this->db->prepare('SELECT * FROM fornecedor WHERE id_fornecedor = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO fornecedor (nome, tipo_pessoa, cpf_cnpj, email, telefone, endereco)
             VALUES (:nome, :tipo, :doc, :email, :tel, :end)'
        );
        $stmt->execute([
            'nome'  => $d['nome'],
            'tipo'  => $d['tipo_pessoa'] ?? 'juridica',
            'doc'   => $d['cpf_cnpj'] ?? null,
            'email' => $d['email'] ?? null,
            'tel'   => $d['telefone'] ?? null,
            'end'   => $d['endereco'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE fornecedor SET nome = :nome, tipo_pessoa = :tipo, cpf_cnpj = :doc,
             email = :email, telefone = :tel, endereco = :end WHERE id_fornecedor = :id'
        );
        return $stmt->execute([
            'id'    => $id,
            'nome'  => $d['nome'],
            'tipo'  => $d['tipo_pessoa'] ?? 'juridica',
            'doc'   => $d['cpf_cnpj'] ?? null,
            'email' => $d['email'] ?? null,
            'tel'   => $d['telefone'] ?? null,
            'end'   => $d['endereco'] ?? null,
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

?>