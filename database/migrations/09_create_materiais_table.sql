-- 09 - Tabela: material
-- Não depende de outras tabelas.
-- Matéria-prima usada na fabricação dos produtos.
-- O fornecedor NÃO fica aqui: descobrimos quem forneceu um material através
-- das compras (fornecedor -> compra -> compra_material -> material).
-- O campo 'estoque' guarda a quantidade disponível em estoque do material.

USE controle_estoque_pedidos;

CREATE TABLE material (
  id_material     INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(120) NOT NULL,
  unidade_medida  VARCHAR(20)  NOT NULL DEFAULT 'un',
  custo_unitario  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estoque         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ativo           TINYINT(1) NOT NULL DEFAULT 1,
  criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
