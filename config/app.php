<?php

  // Configurações gerais da aplicação.
  // jwt_expires: 900s = 15 MINUTOS.
  // POR QUÊ: requisito de segurança do projeto — sessão parada por
  // mais de 15 min deve exigir novo login. Quando o token expira,
  // qualquer requisição devolve 401 e o front redireciona para o
  // login com o aviso "Sessão expirada".
  // O valor efetivo vem do .env (JWT_EXPIRES=900)
 
return [
    'nome'         => 'Controle de Estoque e Pedidos',
    'jwt_secret'   => $_ENV['JWT_SECRET'] ?? 'segredo_padrao_inseguro',
    'jwt_expires'  => (int) ($_ENV['JWT_EXPIRES'] ?? 900),
];
