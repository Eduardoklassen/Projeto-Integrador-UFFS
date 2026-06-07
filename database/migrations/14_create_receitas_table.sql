-- 14 - Tabela: receita
-- Depende de: pedido, caixa
-- Uma receita é gerada por um pedido (entrada de dinheiro) e entra em um caixa.
-- id_pedido é opcional (NULL) para permitir receitas avulsas, mas no fluxo
-- normal toda receita vem de um pedido pago.

USE controle_estoque_pedidos;

CREATE TABLE receita (
  id_receita      INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT NULL,
  id_caixa        INT NOT NULL,
  valor           DECIMAL(12,2) NOT NULL,
  forma_pagamento ENUM('dinheiro','pix','cartao','boleto','outro')
                    NOT NULL DEFAULT 'outro',
  data_receita    DATE NOT NULL DEFAULT (CURRENT_DATE),
  observacao      VARCHAR(255),
  CONSTRAINT fk_receita_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE SET NULL,
  CONSTRAINT fk_receita_caixa
    FOREIGN KEY (id_caixa) REFERENCES caixa(id_caixa)
) ENGINE=InnoDB;
