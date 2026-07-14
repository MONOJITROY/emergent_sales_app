<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Database; use App\Core\Request;

final class DashboardController extends Controller {
    public function index(Request $r): void { Auth::user(); $this->view('dashboard/index', ['_active'=>'dashboard']); }
    public function stats(Request $r): void {
        Auth::user();
        $pdo = Database::pdo();
        $today = date('Y-m-d');
        $todaySales = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE sale_date = '$today'")->fetchColumn();
        $totalSales = (float)$pdo->query('SELECT COALESCE(SUM(total),0) FROM sales')->fetchColumn();
        $outstanding = (float)$pdo->query('SELECT COALESCE(SUM(COALESCE(balance,total)),0) FROM sales')->fetchColumn();
        $productsCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $customersCount = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
        $lowStock = $pdo->query('SELECT * FROM products WHERE reorder_level > 0 AND stock <= reorder_level ORDER BY stock ASC LIMIT 10')->fetchAll();
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $v = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE sale_date = '$d'")->fetchColumn();
            $chart[] = ['date'=>$d,'total'=>$v];
        }
        $recent = $pdo->query('SELECT id, invoice_no, customer_name, DATE_FORMAT(sale_date,"%d-%m-%Y") AS sale_date, total, balance, status FROM sales ORDER BY id DESC LIMIT 5')->fetchAll();
        $pendingRecon = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE reconciliation_status = 'pending'")->fetchColumn();
        $this->json([
            'today_sales'=>$todaySales,'total_sales'=>$totalSales,'outstanding'=>$outstanding,
            'products_count'=>$productsCount,'customers_count'=>$customersCount,
            'low_stock_count'=>count($lowStock),'low_stock_items'=>$lowStock,
            'chart'=>$chart,'recent_sales'=>$recent,'pending_reconciliation'=>$pendingRecon,
        ]);
    }
}
