<?php

namespace App\Models;

use App\Core\Database;
use PDO;


  // Usuario — agora com CRUD completo (listar/buscar/atualizar/excluir).
  // SEGURANÇA: senha_hash NUNCA é devolvida nas listagens/detalhe —
  // as consultas selecionam só colunas públicas. O hash só é lido
 
class Usuario
{
    private PDO $db;

    // Colunas seguras para expor (sem senha_hash)
    private const COLUNAS_PUBLICAS =
        'id_usuario, nome_usuario, email, tipo_usuario, criado_em';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function buscarPorEmail(string $email): ?array
    {
        // Aqui SIM traz o hash — é o único ponto que precisa (login).
        $stmt = $this->db->prepare('SELECT * FROM usuario WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function listar(): array
    {
        return $this->db->query(
            'SELECT ' . self::COLUNAS_PUBLICAS . ' FROM usuario ORDER BY nome_usuario'
        )->fetchAll();
    }

    // Detalhe público (sem hash)
    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUNAS_PUBLICAS . ' FROM usuario WHERE id_usuario = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
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

      // Atualiza nome, e-mail e tipo. A senha só é alterada se vier
      // preenchida (campo em branco = mantém a atual) — evita zerar
     
    public function atualizar(int $id, array $dados): bool
    {
        $campos = 'nome_usuario = :nome, email = :email, tipo_usuario = :tipo';
        $params = [
            'id'    => $id,
            'nome'  => $dados['nome_usuario'],
            'email' => $dados['email'],
            'tipo'  => $dados['tipo_usuario'] ?? 'comum',
        ];

        if (!empty($dados['senha'])) {
            $campos .= ', senha_hash = :senha';
            $params['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }

        $stmt = $this->db->prepare("UPDATE usuario SET {$campos} WHERE id_usuario = :id");
        return $stmt->execute($params);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuario WHERE id_usuario = :id');
        return $stmt->execute(['id' => $id]);
    }

    // Quantos admins existem (para impedir remoção aleatória)
    public function contarAdmins(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM usuario WHERE tipo_usuario = 'admin'"
        )->fetchColumn();
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
    }
}
