<?php

namespace App\Models;
use App\Core\Database;
use PDO;

//Esqueleto inicial.
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

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM cliente')->fetchColumn();
    }
}

?>