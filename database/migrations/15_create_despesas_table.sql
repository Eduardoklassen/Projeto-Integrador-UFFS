-- 15 - Tabela: despesa
-- Depende de: compra, caixa
-- Uma despesa é gerada por uma compra (saída de dinheiro) e sai de um caixa.
-- id_compra é opcional (NULL) para permitir despesas avulsas (ex.: conta de
-- luz), mas no fluxo normal toda despesa vem de uma compra.
-- tipo_movimentacao classifica a saída (compra de material, operacional, etc.).

USE controle_estoque_pedidos;

CREATE TABLE despesa (
  id_despesa        INT AUTO_INCREMENT PRIMARY KEY,
  id_compra         INT NULL,
  id_caixa          INT NOT NULL,
  valor             DECIMAL(12,2) NOT NULL,
  tipo_movimentacao ENUM('compra_material','operacional','outro')
                      NOT NULL DEFAULT 'outro',
  data_despesa      DATE NOT NULL DEFAULT (CURRENT_DATE),
  observacao        VARCHAR(255),
  CONSTRAINT fk_despesa_compra
    FOREIGN KEY (id_compra) REFERENCES compra(id_compra) ON DELETE SET NULL,
  CONSTRAINT fk_despesa_caixa
    FOREIGN KEY (id_caixa) REFERENCES caixa(id_caixa)
) ENGINE=InnoDB;
