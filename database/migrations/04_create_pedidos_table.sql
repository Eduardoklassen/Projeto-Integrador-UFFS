-- 04 - Tabela: pedido
-- Depende de: cliente, usuario

-- ALTERAÇÃO (#3 do ER revisado após primeiros testes): adicionada a coluna id_usuario,
-- registrando QUAL usuário do sistema lançou o pedido (rastreabilidade).

USE controle_estoque_pedidos;

CREATE TABLE pedido (
  id_pedido     INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente    INT NOT NULL,
  id_usuario    INT NOT NULL,
  status        ENUM('aberto','pago','enviado','entregue','cancelado')
                  NOT NULL DEFAULT 'aberto',
  data_pedido   DATE NOT NULL DEFAULT (CURRENT_DATE),
  data_entrega  DATE NULL,
  valor_total   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_pedido_cliente
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
  CONSTRAINT fk_pedido_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
