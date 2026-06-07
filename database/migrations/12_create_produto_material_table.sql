-- 12 - Tabela: produto_material  (associativa N:N "Utiliza")
-- Depende de: produto, material
-- Resolve o muitos-para-muitos entre produto e material: um produto utiliza
-- vários materiais (a "receita de fabricação"), e um material é usado por
-- vários produtos. Cada linha diz QUANTO de um material o produto consome.
-- Chave primária composta (id_produto + id_material): um material não se
-- repete duas vezes na composição do mesmo produto.

USE controle_estoque_pedidos;

CREATE TABLE produto_material (
  id_produto      INT NOT NULL,
  id_material     INT NOT NULL,
  quantidade      DECIMAL(10,2) NOT NULL CHECK (quantidade > 0),
  PRIMARY KEY (id_produto, id_material),
  CONSTRAINT fk_pm_produto
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto) ON DELETE CASCADE,
  CONSTRAINT fk_pm_material
    FOREIGN KEY (id_material) REFERENCES material(id_material)
) ENGINE=InnoDB;
