<?php
namespace App\Core;

final class Autoload
{
    public static function register(): void
    {
        spl_autoload_register(function (string $class): void {
            if (strpos($class, 'App\\') !== 0) return;
            $rel = str_replace('\\', '/', substr($class, 4)) . '.php';
            $path = dirname(__DIR__) . '/' . $rel;
            if (is_file($path)) require $path;
        });
    }
}
