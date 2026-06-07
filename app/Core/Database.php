<?php

namespace App\Core;
use PDO;
use PDOException;

//Conexão única (singleton) com o banco de dados via PDO.
//Lê as credenciais de config/database.php.

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $cfg = require __DIR__ . '/../../config/database.php';

            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};"
                 . "dbname={$cfg['name']};charset={$cfg['charset']}";

            try {
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['erro' => 'Falha na conexão com o banco de dados']);
                exit;
            }
        }

        return self::$instance;
    }
}

?>