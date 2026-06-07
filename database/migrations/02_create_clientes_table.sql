-- 02 - Tabela: cliente

USE controle_estoque_pedidos;

CREATE TABLE cliente (
  id_cliente   INT AUTO_INCREMENT PRIMARY KEY,
  nome         VARCHAR(120) NOT NULL,
  email        VARCHAR(160),
  telefone     VARCHAR(20),
  cpf_cnpj     VARCHAR(18) UNIQUE,
  criado_em    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
