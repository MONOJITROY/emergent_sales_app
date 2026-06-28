<?php
namespace App\Core;

final class View
{
    public function render(string $template, array $data = [], ?string $layout = 'main'): string
    {
        $content = $this->renderFile($template, $data);
        if (!$layout) return $content;
        $data['content']   = $content;
        $data['_csrf']     = Csrf::token();
        $data['_user']     = Auth::check();
        $data['_baseUrl']  = App::config('base_url') ?: '';
        $data['_assetUrl'] = App::config('asset_base') ?: ($data['_baseUrl']);
        $data['_appName']  = App::config('app_name') ?: 'StockFlow';
        $data['_active']   = $data['_active'] ?? '';
        return $this->renderFile("layouts/$layout", $data);
    }

    private function renderFile(string $template, array $data): string
    {
        $path = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_file($path)) throw new \RuntimeException("View not found: $template");
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    public static function e($v): string
    {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
