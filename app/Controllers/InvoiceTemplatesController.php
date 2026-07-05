<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Models\Company;

final class InvoiceTemplatesController extends Controller
{
    public function index(Request $r): void
    {
        Auth::requireAdmin();
        $settings = Company::settings();
        $this->view('invoicetemplates/index', ['_active' => 'invoicetemplates', 'settings' => $settings]);
    }

    public function list(Request $r): void
    {
        Auth::requireAdmin();
        $dir = __DIR__ . '/../../public/invoice-templates';
        $templates = [];
        if (is_dir($dir)) {
            $items = scandir($dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $dir . '/' . $item;
                if (!is_dir($path)) continue;
                $htmlFile = $path . '/' . $item . '.html';
                if (!file_exists($htmlFile)) continue;
                $templates[] = [
                    'id'   => $item,
                    'name' => ucwords(str_replace(['-', '_'], ' ', $item)),
                ];
            }
        }
        usort($templates, fn($a, $b) => strcmp($a['id'], $b['id']));
        $this->json($templates);
    }

    public function setDefault(Request $r): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $template = trim((string)$r->input('template', ''));
        if ($template === '') {
            $this->json(['ok' => false, 'error' => 'Template required'], 400);
            return;
        }
        Company::update(1, ['invoice_template' => $template]);
        $this->json(['ok' => true, 'template' => $template]);
    }

    public function preview(Request $r): void
    {
        $template = trim($r->param('template', ''));
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $template)) {
            http_response_code(400);
            echo 'Invalid template';
            return;
        }
        $file = __DIR__ . '/../../public/invoice-templates/' . $template . '/' . $template . '.html';
        if (!file_exists($file)) {
            http_response_code(404);
            echo 'Template not found';
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        $html = file_get_contents($file);
        $baseUrl = rtrim((string)App::config('base_url'), '/');
        $html = str_replace('<head>', '<head><base href="' . $baseUrl . '/invoice-templates/' . $template . '/">', $html);
        echo $html;
    }
}
