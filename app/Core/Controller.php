<?php
namespace App\Core;

abstract class Controller
{
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function view(string $template, array $data = [], ?string $layout = 'main'): void
    {
        $view = new View();
        echo $view->render($template, $data, $layout);
    }

    protected function requireCsrf(): void
    {
        Csrf::check();
    }
}
