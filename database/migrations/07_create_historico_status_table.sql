-- 07 - Tabela: historico_status  (#6 do ER revisado)
-- Depende de: pedido

-- Registra cada mudança de status de um pedido (aberto -> pago -> enviado)
-- Cada vez que o status do pedido muda, uma linha é inserida aqui, guardando
-- o status anterior.
-- linha do tempo completa de um pedido (como o rastreamento de uma encomenda).

USE controle_estoque_pedidos;

CREATE TABLE historico_status (
  id_historico    INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT NOT NULL,
  status_anterior ENUM('aberto','pago','enviado','entregue','cancelado') NULL,
  status_novo     ENUM('aberto','pago','enviado','entregue','cancelado') NOT NULL,
  data_mudanca    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_historico_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE CASCADE
) ENGINE=InnoDB;
