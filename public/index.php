<?php
declare(strict_types=1);

session_start();

// PHP built-in server: serve static assets directly
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../app/Core/Autoload.php';
\App\Core\Autoload::register();
// Composer autoload (PhpSpreadsheet, Dompdf, PHPMailer) — optional
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

$config = require __DIR__ . '/../config.php';
\App\Core\Database::configure($config['db']);
\App\Core\App::setConfig($config);

// Re-seed admin password hash on every boot so the default works even if hash in seed isn't valid bcrypt
\App\Core\App::ensureAdminSeed();

$app = new \App\Core\App();
$app->run();
