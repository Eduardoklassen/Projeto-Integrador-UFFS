-- 01 - Tabela: usuario (autenticação e autorização)

USE controle_estoque_pedidos;

CREATE TABLE usuario (
  id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
  nome_usuario   VARCHAR(120)  NOT NULL,
  email          VARCHAR(160)  NOT NULL UNIQUE,
  senha_hash     VARCHAR(255)  NOT NULL,
  tipo_usuario   ENUM('admin','comum') NOT NULL DEFAULT 'comum',
  criado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
