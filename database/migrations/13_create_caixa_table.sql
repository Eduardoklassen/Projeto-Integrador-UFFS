-- 13 - Tabela: caixa
-- Não depende de outras tabelas.
-- Representa um caixa/conta onde entram as receitas e saem as despesas.
-- O 'saldo' é um valor de referência (saldo atual). O cálculo histórico de
-- saldo por período é feito somando receitas e despesas ligadas a este caixa.

USE controle_estoque_pedidos;

CREATE TABLE caixa (
  id_caixa    INT AUTO_INCREMENT PRIMARY KEY,
  descricao   VARCHAR(120) NOT NULL,
  saldo       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
