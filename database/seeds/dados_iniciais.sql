-- Dados iniciais 
-- Execute após todas as migrations.

USE controle_estoque_pedidos;

-- Usuário administrador.
-- Senha: "admin123" (hash bcrypt).
-- Gere o seu próprio com: password_hash('suasenha', PASSWORD_DEFAULT)
INSERT INTO usuario (nome_usuario, email, senha_hash, tipo_usuario) VALUES
('Administrador', 'admin@local', '$2y$10$K26UtsBAARJaBxy32o3JSOKWvNI2M8O8S9uof7RangNI9x2Cicee.', 'admin');

INSERT INTO cliente (nome, email, telefone, cpf_cnpj) VALUES
('Cliente Exemplo', 'cliente@exemplo.com', '5599999999999', '12345678900');

INSERT INTO produto (nome, preco_final, estoque, observacao) VALUES
('Produto A', 19.90, 100, 'Item de exemplo'),
('Produto B', 49.50, 30,  'Item de exemplo');

INSERT INTO fornecedor (nome, tipo_pessoa, cpf_cnpj, email, telefone) VALUES
('Fornecedor Exemplo Ltda', 'juridica', '12345678000199', 'contato@fornecedor.com', '5599888887777');

-- Endereços (um para o cliente, um para o fornecedor)
INSERT INTO endereco (id_cliente, id_fornecedor, descricao, rua, numero, bairro, cep) VALUES
(1, NULL, 'Residencial', 'Rua das Flores', '123', 'Centro', '98765000'),
(NULL, 1, 'Sede',        'Av. Industrial',  '500', 'Distrito', '98765111');

-- Materiais (matéria-prima)
INSERT INTO material (nome, unidade_medida, custo_unitario, estoque) VALUES
('Areia',   'm3', 80.00, 0),
('Cimento', 'sc', 32.50, 0);

-- Caixa principal
INSERT INTO caixa (descricao, saldo) VALUES
('Caixa Principal', 0.00);
