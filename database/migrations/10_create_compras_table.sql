-- 10 - Tabela: compra
-- Depende de: fornecedor
-- Uma compra é feita a UM fornecedor (FK id_fornecedor, conforme o ER revisado).
-- Os materiais que entraram nesta compra ficam na associativa compra_material.
-- valor_total é a soma do que foi comprado (preenchido pela aplicação).

USE controle_estoque_pedidos;

CREATE TABLE compra (
  id_compra      INT AUTO_INCREMENT PRIMARY KEY,
  id_fornecedor  INT NOT NULL,
  valor_total    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  data_compra    DATE NOT NULL DEFAULT (CURRENT_DATE),
  CONSTRAINT fk_compra_fornecedor
    FOREIGN KEY (id_fornecedor) REFERENCES fornecedor(id_fornecedor)
) ENGINE=InnoDB;
