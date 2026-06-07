<?php

//Configuração de CORS — libera o front-end a consumir a API.
//Em produção, troque '*' pela origem específica do front.
 
return [
    'allow_origin'  => $_ENV['CORS_ORIGIN'] ?? '*',
    'allow_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
    'allow_headers' => 'Content-Type, Authorization',
];

?>