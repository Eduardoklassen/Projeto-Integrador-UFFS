-- 05 - Tabela: item_pedido
-- Depende de: pedido, produto

USE controle_estoque_pedidos;

CREATE TABLE item_pedido (
  id_item_pedido  INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT NOT NULL,
  id_produto      INT NOT NULL,
  quantidade      INT NOT NULL CHECK (quantidade > 0),
  valor_unitario  DECIMAL(10,2) NOT NULL,
  sub_total       DECIMAL(10,2)
    AS (quantidade * valor_unitario) STORED,
  CONSTRAINT fk_item_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE CASCADE,
  CONSTRAINT fk_item_produto
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
) ENGINE=InnoDB;
