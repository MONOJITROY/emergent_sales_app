<?php
// ---- App configuration ----
return [
    'app_name'  => 'StockFlow',
    'base_url'  => rtrim(getenv('APP_BASE_URL') ?: 'http://localhost/emergent_sales_App/emergent_sales_app', '/'), // e.g. http://localhost:8000
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('DB_PORT') ?: '3306',
        'name'    => getenv('DB_NAME') ?: 'stockflow',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') ?: '131118swA@',
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        'email'    => 'admin@stockflow.test',
        'password' => 'admin123',
        'name'     => 'Administrator',
    ],
];
