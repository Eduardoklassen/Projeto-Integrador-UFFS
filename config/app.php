<?php

  //Configurações gerais da aplicação.

return [
    'nome'         => 'Controle de Estoque e Pedidos',
    'jwt_secret'   => $_ENV['JWT_SECRET'] ?? 'segredo_padrao_inseguro',
    'jwt_expires'  => (int) ($_ENV['JWT_EXPIRES'] ?? 3600),
];

?>
