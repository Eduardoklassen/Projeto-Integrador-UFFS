-- 03 - Tabela: produto (com estoque próprio)

USE controle_estoque_pedidos;

CREATE TABLE produto (
  id_produto    INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(120) NOT NULL,
  preco_final   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estoque       INT NOT NULL DEFAULT 0,
  observacao    VARCHAR(255),
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
