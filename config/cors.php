<?php

  // Configuração de CORS.
  // Inclui as portas padrão do Live Server (5500 e 5501), com
  // localhost E 127.0.0.1, porque o navegador trata os dois como
  // origens DIFERENTES — o front em 127.0.0.1:5500 não é aceito
  // por uma regra que só cita localhost:5500.
  // Se quiser controlar pelo .env, defina CORS_ORIGIN (separando
  // por vírgula); se não, esta lista abaixo é usada.
 

$origensEnv = array_filter(array_map('trim', explode(',', $_ENV['CORS_ORIGIN'] ?? '')));

return [
    'allow_origin_list' => $origensEnv ?: [
        'http://127.0.0.1:5500',
        'http://localhost:5500',
        'http://127.0.0.1:5501',
        'http://localhost:5501',
        'http://127.0.0.1:5502',
        'http://localhost:5502',
    ],
    'allow_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
    'allow_headers' => 'Content-Type, Authorization',
];
