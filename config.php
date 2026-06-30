<?php
// ---- App configuration ----
return [
    'app_name'  => 'MediMart',
    'base_url'  => rtrim(getenv('APP_BASE_URL') ?: '', '/'), // e.g. http://localhost:8000
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
