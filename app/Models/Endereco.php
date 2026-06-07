<?php

namespace App\Models;
use App\Core\Database;
use PDO;

/**
 * Endereco = entidade própria, pertence a UM cliente OU a UM fornecedor.
 * O banco garante (via CHECK) que exatamente uma das FKs esteja preenchida.
 */
class Endereco
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Lista endereços. Aceita filtro por dono:
     * id_cliente - endereços de um cliente
     * id_fornecedor - endereços de um fornecedor
     */
    public function listar(array $opts = []): array
    {
        $sql = 'SELECT * FROM endereco WHERE 1=1';
        $bind = [];

        if (!empty($opts['id_cliente'])) {
            $sql .= ' AND id_cliente = :cli';
            $bind['cli'] = (int) $opts['id_cliente'];
        }
        if (!empty($opts['id_fornecedor'])) {
            $sql .= ' AND id_fornecedor = :forn';
            $bind['forn'] = (int) $opts['id_fornecedor'];
        }

        $sql .= ' ORDER BY id_endereco ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM endereco WHERE id_endereco = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO endereco (id_cliente, id_fornecedor, descricao, rua, numero, bairro, cep)
             VALUES (:cli, :forn, :desc, :rua, :num, :bairro, :cep)'
        );
        $stmt->execute([
            'cli'    => $d['id_cliente'] ?? null,
            'forn'   => $d['id_fornecedor'] ?? null,
            'desc'   => $d['descricao'] ?? null,
            'rua'    => $d['rua'],
            'num'    => $d['numero'] ?? null,
            'bairro' => $d['bairro'] ?? null,
            'cep'    => $d['cep'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE endereco SET descricao = :desc, rua = :rua, numero = :num,
             bairro = :bairro, cep = :cep WHERE id_endereco = :id'
        );
        return $stmt->execute([
            'id'     => $id,
            'desc'   => $d['descricao'] ?? null,
            'rua'    => $d['rua'],
            'num'    => $d['numero'] ?? null,
            'bairro' => $d['bairro'] ?? null,
            'cep'    => $d['cep'] ?? null,
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM endereco WHERE id_endereco = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM endereco')->fetchColumn();
    }
}

?>