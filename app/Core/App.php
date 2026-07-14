<?php
namespace App\Core;

final class App
{
    private static array $config = [];

    public static function setConfig(array $cfg): void { self::$config = $cfg; }
    public static function config(?string $key = null) { return $key ? (self::$config[$key] ?? null) : self::$config; }

    public static function ensureAdminSeed(): void
    {
        try {
            $admin = self::$config['admin'];
            $pdo = Database::pdo();
            $row = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $row->execute([$admin['email']]);
            $u = $row->fetch();
            if (!$u) {
                $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, "admin")');
                $stmt->execute([$admin['email'], password_hash($admin['password'], PASSWORD_BCRYPT), $admin['name']]);
            } elseif (!password_verify($admin['password'], $u['password_hash'])) {
                // Ensure default password works (fixes broken seed hash from schema.sql)
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([password_hash($admin['password'], PASSWORD_BCRYPT), $u['id']]);
            }
        } catch (\Throwable $e) {
            // ignore — likely DB not set up yet
        }
    }

    public function run(): void
    {
        $router = new Router();
        $this->registerRoutes($router);

        $method     = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $reqUri     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');   // /<sub>/public/index.php
        $scriptDir  = rtrim(dirname($scriptName), '/');                          // /<sub>/public
        $pathInfo   = $_SERVER['PATH_INFO'] ?? '';

        // Detect whether Apache mod_rewrite is active (so /public/login works without index.php).
        $rewriteOn = function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules(), true);

        // Compute URI to route on
        if ($pathInfo !== '') {
            $uri = $pathInfo;                                                    // /public/index.php/login  -> /login
        } elseif ($reqUri === $scriptName || $reqUri === $scriptName . '/') {
            $uri = '/';                                                          // direct /public/index.php
        } elseif ($scriptName !== '' && str_starts_with($reqUri, $scriptName . '/')) {
            $uri = substr($reqUri, strlen($scriptName)) ?: '/';                  // /public/index.php/login (no rewrite)
        } elseif ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($reqUri, $scriptDir)) {
            $uri = substr($reqUri, strlen($scriptDir)) ?: '/';                   // /public/login -> /login (rewrite case)
        } else {
            $uri = $reqUri;
        }
        if ($uri === '') $uri = '/';
        // Strip trailing slash (except root)
        if ($uri !== '/' && str_ends_with($uri, '/')) $uri = rtrim($uri, '/');

        // Base URL for navigation/assets — if no rewrite, route everything through index.php so PATH_INFO is used.
        if (empty(self::$config['base_url'])) {
            self::$config['base_url']     = $rewriteOn ? $scriptDir : $scriptName;  // .../public  OR  .../public/index.php
            self::$config['asset_base']   = $scriptDir;                              // assets always served directly
            self::$config['rewrite_on']   = $rewriteOn;
        }

        try {
            $router->dispatch($method, $uri);
        } catch (\Throwable $e) {
            http_response_code(500);
            if (str_starts_with($uri, '/api/')) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            } else {
                echo '<h1>Server error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            }
        }
    }

    private function registerRoutes(Router $r): void
    {
        // Public
        $r->get('/login',  ['App\\Controllers\\AuthController', 'showLogin']);
        $r->post('/api/auth/login',  ['App\\Controllers\\AuthController', 'login']);
        $r->post('/api/auth/logout', ['App\\Controllers\\AuthController', 'logout']);

        // Pages (auth required)
        $r->get('/',           ['App\\Controllers\\DashboardController', 'index']);
        $r->get('/products',   ['App\\Controllers\\ProductsController',  'index']);
        $r->get('/customers',  ['App\\Controllers\\CustomersController', 'index']);
        $r->get('/suppliers',  ['App\\Controllers\\SuppliersController', 'index']);
        $r->get('/sales',      ['App\\Controllers\\SalesController',     'index']);
        $r->get('/sales/new',  ['App\\Controllers\\SalesController',     'create']);
        $r->get('/sales/{id}', ['App\\Controllers\\SalesController',     'show']);
        $r->get('/purchases',  ['App\\Controllers\\PurchasesController', 'index']);
        $r->get('/reports',    ['App\\Controllers\\ReportsController',   'index']);
        $r->get('/users',          ['App\\Controllers\\UsersController',     'index']);
        $r->get('/company/settings',    ['App\\Controllers\\CompanyController',        'index']);
        $r->get('/invoice-templates',              ['App\\Controllers\\InvoiceTemplatesController','index']);
        $r->get('/invoice-templates/',             ['App\\Controllers\\InvoiceTemplatesController','index']);
        $r->get('/invoice-templates/{template}/preview',  ['App\\Controllers\\InvoiceTemplatesController','preview']);
        $r->get('/taxtypes',            ['App\\Controllers\\TaxtypeController',        'index']);

        // API
        $r->get('/api/dashboard/stats', ['App\\Controllers\\DashboardController', 'stats']);

        foreach (['products','customers','suppliers'] as $res) {
            $ctrl = 'App\\Controllers\\' . ucfirst($res) . 'Controller';
            $r->get("/api/$res",        [$ctrl, 'list']);
            $r->post("/api/$res",       [$ctrl, 'create']);
            $r->put("/api/$res/{id}",   [$ctrl, 'update']);
            $r->delete("/api/$res/{id}",[$ctrl, 'delete']);
        }

        $r->get('/api/sales',              ['App\\Controllers\\SalesController', 'apiList']);
        $r->get('/api/sales/{id}',         ['App\\Controllers\\SalesController', 'apiGet']);
        $r->post('/api/sales',             ['App\\Controllers\\SalesController', 'apiCreate']);
        $r->delete('/api/sales/{id}',      ['App\\Controllers\\SalesController', 'apiDelete']);

        $r->get('/api/purchases',     ['App\\Controllers\\PurchasesController', 'apiList']);
        $r->post('/api/purchases',    ['App\\Controllers\\PurchasesController', 'apiCreate']);
        $r->delete('/api/purchases/{id}', ['App\\Controllers\\PurchasesController', 'apiDelete']);

        $r->get('/api/users',            ['App\\Controllers\\UsersController', 'apiList']);
        $r->post('/api/users',           ['App\\Controllers\\UsersController', 'apiCreate']);
        $r->delete('/api/users/{id}',    ['App\\Controllers\\UsersController', 'apiDelete']);

        $r->get('/api/taxtypes',        ['App\\Controllers\\TaxtypeController', 'list']);
        $r->post('/api/taxtypes',       ['App\\Controllers\\TaxtypeController', 'create']);
        $r->put('/api/taxtypes/{id}',   ['App\\Controllers\\TaxtypeController', 'update']);
        $r->delete('/api/taxtypes/{id}',['App\\Controllers\\TaxtypeController', 'delete']);

        $r->get('/api/company/settings',     ['App\\Controllers\\CompanyController',           'get']);
        $r->post('/api/company/settings',    ['App\\Controllers\\CompanyController',           'save']);
        $r->get('/api/invoice-templates',    ['App\\Controllers\\InvoiceTemplatesController',  'list']);
        $r->post('/api/invoice-templates/default', ['App\\Controllers\\InvoiceTemplatesController', 'setDefault']);

        $r->get('/api/reports/sales-by-customer', ['App\\Controllers\\ReportsController', 'salesByCustomer']);
        $r->get('/api/reports/sales-by-product',  ['App\\Controllers\\ReportsController', 'salesByProduct']);
        $r->get('/api/reports/invoice-aging',     ['App\\Controllers\\ReportsController', 'invoiceAging']);

        // Exports & email
        $r->get('/api/exports/sales.xlsx',           ['App\\Controllers\\ExportController', 'salesXlsx']);
        $r->get('/api/exports/invoice/{id}.pdf',     ['App\\Controllers\\ExportController', 'invoicePdf']);
        $r->post('/api/sales/{id}/email',            ['App\\Controllers\\ExportController', 'emailInvoice']);
        $r->get('/api/invoice/{id}/render',          ['App\\Controllers\\ExportController', 'renderInvoice']);

        // Receipts
        $r->get('/receipts',                              ['App\\Controllers\\ReceiptsController', 'index']);
        $r->get('/receipts/new',                          ['App\\Controllers\\ReceiptsController', 'create']);
        $r->get('/api/receipts',                          ['App\\Controllers\\ReceiptsController', 'apiList']);
        $r->get('/api/receipts/{id}',                     ['App\\Controllers\\ReceiptsController', 'apiGet']);
        $r->post('/api/receipts',                         ['App\\Controllers\\ReceiptsController', 'apiCreate']);
        $r->get('/api/receipts/{id}/pdf',                 ['App\\Controllers\\ReceiptsController', 'apiPdf']);
        $r->get('/api/customers/{id}/pending-invoices',   ['App\\Controllers\\ReceiptsController', 'apiPendingInvoices']);

        // Payments
        $r->get('/payments',                              ['App\\Controllers\\PaymentsController', 'index']);
        $r->get('/payments/new',                          ['App\\Controllers\\PaymentsController', 'create']);
        $r->get('/api/payments',                          ['App\\Controllers\\PaymentsController', 'apiList']);
        $r->get('/api/payments/{id}',                     ['App\\Controllers\\PaymentsController', 'apiGet']);
        $r->post('/api/payments',                         ['App\\Controllers\\PaymentsController', 'apiCreate']);
        $r->get('/api/payments/{id}/pdf',                 ['App\\Controllers\\PaymentsController', 'apiPdf']);
        $r->get('/api/suppliers/{id}/pending-invoices',   ['App\\Controllers\\PaymentsController', 'apiPendingInvoices']);

        // Reconciliation
        $r->get('/reconciliation/receipts',               ['App\\Controllers\\ReconciliationController', 'receiptIndex']);
        $r->get('/reconciliation/payments',               ['App\\Controllers\\ReconciliationController', 'paymentIndex']);
        $r->get('/api/reconciliation/receipts',            ['App\\Controllers\\ReconciliationController', 'apiReceiptList']);
        $r->get('/api/reconciliation/payments',            ['App\\Controllers\\ReconciliationController', 'apiPaymentList']);
        $r->post('/api/reconciliation/{id}/settle',        ['App\\Controllers\\ReconciliationController', 'apiSettle']);

        // Edit sale
        $r->put('/api/sales/{id}',                   ['App\\Controllers\\SalesController', 'apiUpdate']);
        $r->get('/sales/{id}/edit',                  ['App\\Controllers\\SalesController', 'edit']);
    }
}
