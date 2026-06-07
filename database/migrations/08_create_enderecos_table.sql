-- 08 - Tabela: endereco
-- Depende de: cliente, fornecedor
-- ============================================
-- No ER revisado, Endereço é uma ENTIDADE PRÓPRIA (não um campo de texto (foi feita alteração),
-- ligada tanto a Cliente quanto a Fornecedor. Cada endereço pertence a UM
-- cliente OU a UM fornecedor. As duas FKs são opcionais (NULL): um endereço
-- de cliente terá id_fornecedor nulo, e vice-versa.
-- A regra "pertence a um OU outro" é garantida pelo CHECK abaixo, que exige
-- que exatamente uma das duas FKs esteja preenchida.

USE controle_estoque_pedidos;

CREATE TABLE endereco (
  id_endereco    INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente     INT NULL,
  id_fornecedor  INT NULL,
  descricao      VARCHAR(120),
  rua            VARCHAR(160) NOT NULL,
  numero         VARCHAR(20),
  bairro         VARCHAR(80),
  cep            VARCHAR(10),
  CONSTRAINT fk_endereco_cliente
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE,
  CONSTRAINT fk_endereco_fornecedor
    FOREIGN KEY (id_fornecedor) REFERENCES fornecedor(id_fornecedor) ON DELETE CASCADE,
  -- exatamente uma das duas FKs deve estar preenchida (XOR):
  CONSTRAINT chk_endereco_dono
    CHECK (
      (id_cliente IS NOT NULL AND id_fornecedor IS NULL)
      OR
      (id_cliente IS NULL AND id_fornecedor IS NOT NULL)
    )
) ENGINE=InnoDB;
