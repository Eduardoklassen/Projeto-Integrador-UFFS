-- 06 - Tabela: fornecedor

USE controle_estoque_pedidos;

CREATE TABLE fornecedor (
  id_fornecedor  INT AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(120) NOT NULL,
  tipo_pessoa    ENUM('fisica','juridica') NOT NULL DEFAULT 'juridica',
  cpf_cnpj       VARCHAR(18) UNIQUE,
  email          VARCHAR(160),
  telefone       VARCHAR(20),
  endereco       VARCHAR(200),
  ativo          TINYINT(1) NOT NULL DEFAULT 1,
  criado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
