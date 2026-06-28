<?php
namespace App\Core;

final class Request
{
    public array $params;
    public array $body;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        $this->body = is_array($decoded) ? $decoded : $_POST;
    }

    public function param(string $key, $default = null) { return $this->params[$key] ?? $default; }
    public function input(string $key, $default = null) { return $this->body[$key] ?? $default; }
}
