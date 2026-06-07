-- 11 - Tabela: compra_material  (associativa N:N "Contém")
-- Depende de: compra, material

-- Resolve o relacionamento muitos-para-muitos entre compra e material:
-- uma compra contém vários materiais, e um material aparece em várias compras.
-- Cada linha guarda QUANTO de um material entrou numa compra e por qual preço.
-- o mesmo material não se repete duas vezes na mesma compra.

USE controle_estoque_pedidos;

CREATE TABLE compra_material (
  id_compra       INT NOT NULL,
  id_material     INT NOT NULL,
  quantidade      DECIMAL(10,2) NOT NULL CHECK (quantidade > 0),
  custo_unitario  DECIMAL(10,2) NOT NULL,
  sub_total       DECIMAL(10,2)
    AS (quantidade * custo_unitario) STORED,
  PRIMARY KEY (id_compra, id_material),
  CONSTRAINT fk_cm_compra
    FOREIGN KEY (id_compra) REFERENCES compra(id_compra) ON DELETE CASCADE,
  CONSTRAINT fk_cm_material
    FOREIGN KEY (id_material) REFERENCES material(id_material)
) ENGINE=InnoDB;
