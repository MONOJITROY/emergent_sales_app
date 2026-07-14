<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Request; use App\Core\Database;

final class ReportsController extends Controller {
    public function index(Request $r): void { Auth::user(); $this->view('reports/index', ['_active'=>'reports']); }

    public function salesByCustomer(Request $r): void {
        Auth::user();
        $rows = Database::pdo()->query("SELECT customer_name AS customer, COUNT(*) AS invoices, SUM(total) AS total, SUM(balance) AS balance FROM sales GROUP BY customer_name ORDER BY total DESC")->fetchAll();
        $this->json($rows);
    }
    public function salesByProduct(Request $r): void {
        Auth::user();
        $rows = Database::pdo()->query("SELECT sku, name AS product, SUM(qty) AS qty, SUM(total) AS total FROM sale_items GROUP BY product_id, sku, name ORDER BY total DESC")->fetchAll();
        $this->json($rows);
    }
    public function invoiceAging(Request $r): void {
        Auth::user();
        $rows = Database::pdo()->query("SELECT invoice_no, customer_name AS customer, sale_date AS date, DATEDIFF(CURDATE(), sale_date) AS days_overdue, total, COALESCE(balance, total) AS balance FROM sales WHERE COALESCE(balance, total) > 0 ORDER BY days_overdue DESC")->fetchAll();
        $buckets = ['0-30'=>0.0,'31-60'=>0.0,'61-90'=>0.0,'90+'=>0.0];
        foreach ($rows as &$r2) {
            $d = (int)$r2['days_overdue'];
            $b = $d <= 30 ? '0-30' : ($d <= 60 ? '31-60' : ($d <= 90 ? '61-90' : '90+'));
            $r2['bucket'] = $b; $buckets[$b] += (float)$r2['balance'];
        }
        $this->json(['buckets'=>$buckets,'rows'=>$rows]);
    }
}
