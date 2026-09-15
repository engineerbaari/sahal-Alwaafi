<?php
return [
    'db' => [
        'host' => '127.0.0.1',   // server‑ka MySQL
        'port' => 3306,          // port‑ka MySQL
        'name' => 'cargosom',    // magaca database‑ka aad abuuray
        'user' => 'root',        // magaca isticmaalaha MySQL
        'pass' => '',        // erayga sirta MySQL (haddii aad dejisay)
    ],
    'app' => [
        'name' => 'CargoSom',
        'base_url' => 'http://localhost/cargosom', // URL‑ka mashruuca
        'currency' => 'USD',
        'tax_rate' => 0.05,
    ],
];
