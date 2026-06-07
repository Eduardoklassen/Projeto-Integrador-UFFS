<?php

namespace App\Models;
use App\Core\Database;
use PDO;

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuario WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuario (nome_usuario, email, senha_hash, tipo_usuario)
             VALUES (:nome, :email, :senha, :tipo)'
        );
        $stmt->execute([
            'nome'  => $dados['nome_usuario'],
            'email' => $dados['email'],
            'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
            'tipo'  => $dados['tipo_usuario'] ?? 'comum',
        ]);
        return (int) $this->db->lastInsertId();
    }
}

?>