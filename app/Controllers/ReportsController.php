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

    public function partyOutstanding(Request $r): void { Auth::user(); $this->view('reports/party-outstanding', ['_active'=>'reports']); }
    public function partyLedger(Request $r): void { Auth::user(); $this->view('reports/party-ledger', ['_active'=>'reports']); }

    public function apiPartyOutstanding(Request $r): void {
        Auth::user();
        $type = $_GET['type'] ?? 'all';
        $pdo = Database::pdo();
        $rows = [];
        if ($type === 'all' || $type === 'customer') {
            $rows = array_merge($rows, $pdo->query(
                "SELECT 'Customer' AS party_type, c.name AS party_name, c.id AS party_id, c.phone AS phone,
                 COUNT(s.id) AS unpaid_invoices, c.balance AS outstanding
                 FROM customers c LEFT JOIN sales s ON s.customer_id = c.id AND s.balance > 0
                 WHERE c.balance > 0 GROUP BY c.id, c.name, c.phone, c.balance"
            )->fetchAll());
        }
        if ($type === 'all' || $type === 'supplier') {
            $rows = array_merge($rows, $pdo->query(
                "SELECT 'Supplier' AS party_type, sp.name AS party_name, sp.id AS party_id, sp.phone AS phone,
                 COUNT(p.id) AS unpaid_invoices, sp.balance AS outstanding
                 FROM suppliers sp LEFT JOIN purchases p ON p.supplier_id = sp.id AND p.balance > 0
                 WHERE sp.balance > 0 GROUP BY sp.id, sp.name, sp.phone, sp.balance"
            )->fetchAll());
        }
        usort($rows, fn($a, $b) => (float)$b['outstanding'] <=> (float)$a['outstanding']);
        $this->json($rows);
    }

    public function apiPartyLedger(Request $r): void {
        Auth::user();
        $partyType = $_GET['party_type'] ?? 'customer';
        $partyId = (int)($_GET['party_id'] ?? 0);
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        if (!$partyId) { $this->json(['opening'=>0,'entries'=>[]]); return; }
        $pdo = Database::pdo();
        $entries = [];
        $opening = 0.0;

        if ($partyType === 'customer') {
            if ($from) {
                $s = $pdo->prepare("SELECT COALESCE(SUM(total - COALESCE(balance,0)),0) AS net FROM sales WHERE customer_id = ? AND sale_date < ?");
                $s->execute([$partyId, $from]);
                $opening += (float)$s->fetch()['net'];
                $s = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS net FROM transactions WHERE party_type='customer' AND party_id = ? AND transaction_date < ?");
                $s->execute([$partyId, $from]);
                $opening -= (float)$s->fetch()['net'];
            }
            $sql = "SELECT sale_date AS date, 'Invoice' AS type, invoice_no AS ref_no, total AS debit, 0 AS credit FROM sales WHERE customer_id = ?";
            $params = [$partyId];
            if ($from) { $sql .= " AND sale_date >= ?"; $params[] = $from; }
            if ($to)   { $sql .= " AND sale_date <= ?"; $params[] = $to; }
            $entries = $pdo->prepare($sql);
            $entries->execute($params);
            $saleRows = $entries->fetchAll();
            $sql = "SELECT transaction_date AS date, 'Receipt' AS type, transaction_no AS ref_no, 0 AS debit, amount AS credit FROM transactions WHERE party_type='customer' AND party_id = ?";
            $params = [$partyId];
            if ($from) { $sql .= " AND transaction_date >= ?"; $params[] = $from; }
            if ($to)   { $sql .= " AND transaction_date <= ?"; $params[] = $to; }
            $entries = $pdo->prepare($sql);
            $entries->execute($params);
            $txnRows = $entries->fetchAll();
            $all = array_merge($saleRows, $txnRows);
        } else {
            if ($from) {
                $s = $pdo->prepare("SELECT COALESCE(SUM(total - COALESCE(balance,0)),0) AS net FROM purchases WHERE supplier_id = ? AND purchase_date < ?");
                $s->execute([$partyId, $from]);
                $opening += (float)$s->fetch()['net'];
                $s = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS net FROM transactions WHERE party_type='supplier' AND party_id = ? AND transaction_date < ?");
                $s->execute([$partyId, $from]);
                $opening -= (float)$s->fetch()['net'];
            }
            $sql = "SELECT purchase_date AS date, 'Purchase' AS type, ref_no AS ref_no, 0 AS debit, total AS credit FROM purchases WHERE supplier_id = ?";
            $params = [$partyId];
            if ($from) { $sql .= " AND purchase_date >= ?"; $params[] = $from; }
            if ($to)   { $sql .= " AND purchase_date <= ?"; $params[] = $to; }
            $entries = $pdo->prepare($sql);
            $entries->execute($params);
            $purRows = $entries->fetchAll();
            $sql = "SELECT transaction_date AS date, 'Payment' AS type, transaction_no AS ref_no, amount AS debit, 0 AS credit FROM transactions WHERE party_type='supplier' AND party_id = ?";
            $params = [$partyId];
            if ($from) { $sql .= " AND transaction_date >= ?"; $params[] = $from; }
            if ($to)   { $sql .= " AND transaction_date <= ?"; $params[] = $to; }
            $entries = $pdo->prepare($sql);
            $entries->execute($params);
            $txnRows = $entries->fetchAll();
            $all = array_merge($purRows, $txnRows);
        }
        usort($all, fn($a, $b) => strcmp($a['date'], $b['date']) ?: strcmp($a['type'], $b['type']));
        $running = $opening;
        foreach ($all as &$e) {
            $running += (float)$e['debit'] - (float)$e['credit'];
            $e['balance'] = round($running, 2);
        }
        $this->json(['opening' => round($opening, 2), 'entries' => $all]);
    }

    /* ── Sale Report ── */
    public function saleReport(Request $r): void { Auth::user(); $this->view('reports/sale-report', ['_active'=>'reports']); }
    public function apiSaleReport(Request $r): void {
        Auth::user();
        $pdo = Database::pdo();
        $from = $_GET['from'] ?? null; $to = $_GET['to'] ?? null;
        $customer = $_GET['customer'] ?? null; $status = $_GET['status'] ?? null;
        $where = []; $params = [];
        if ($from) { $where[] = 's.sale_date >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 's.sale_date <= ?'; $params[] = $to; }
        if ($customer) { $where[] = 's.customer_id = ?'; $params[] = (int)$customer; }
        if ($status && $status !== 'all') { $where[] = 's.status = ?'; $params[] = $status; }
        $w = $where ? 'WHERE '.implode(' AND ', $where) : '';
        $sql = "SELECT s.invoice_no, s.customer_name AS customer, s.sale_date AS date, s.subtotal, s.discount, s.tax, s.total, s.paid, s.balance, s.status FROM sales s $w ORDER BY s.sale_date DESC, s.invoice_no DESC";
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
        $sql2 = "SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS total, COALESCE(SUM(paid),0) AS paid, COALESCE(SUM(balance),0) AS balance FROM sales s $w";
        $stmt2 = $pdo->prepare($sql2); $stmt2->execute($params); $summary = $stmt2->fetch();
        $this->json(['rows'=>$rows, 'summary'=>$summary]);
    }

    /* ── Product Report ── */
    public function productReport(Request $r): void { Auth::user(); $this->view('reports/product-report', ['_active'=>'reports']); }
    public function apiProductReport(Request $r): void {
        Auth::user();
        $pdo = Database::pdo();
        $rows = $pdo->query(
            "SELECT p.sku, p.name AS product, p.category, p.unit, p.cost_price, p.sale_price, p.stock, p.reorder_level,
             COALESCE(si.qty_sold, 0) AS qty_sold, COALESCE(si.sales_total, 0) AS sales_total,
             COALESCE(pi.qty_purchased, 0) AS qty_purchased, COALESCE(pi.purchase_total, 0) AS purchase_total
             FROM products p
             LEFT JOIN (SELECT product_id, SUM(qty) AS qty_sold, SUM(total) AS sales_total FROM sale_items GROUP BY product_id) si ON si.product_id = p.id
             LEFT JOIN (SELECT product_id, SUM(qty) AS qty_purchased, SUM(total) AS purchase_total FROM purchase_items GROUP BY product_id) pi ON pi.product_id = p.id
             ORDER BY p.name"
        )->fetchAll();
        $this->json($rows);
    }

    /* ── Purchase Report ── */
    public function purchaseReport(Request $r): void { Auth::user(); $this->view('reports/purchase-report', ['_active'=>'reports']); }
    public function apiPurchaseReport(Request $r): void {
        Auth::user();
        $pdo = Database::pdo();
        $from = $_GET['from'] ?? null; $to = $_GET['to'] ?? null;
        $supplier = $_GET['supplier'] ?? null; $status = $_GET['status'] ?? null;
        $where = []; $params = [];
        if ($from) { $where[] = 'pu.purchase_date >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 'pu.purchase_date <= ?'; $params[] = $to; }
        if ($supplier) { $where[] = 'pu.supplier_id = ?'; $params[] = (int)$supplier; }
        if ($status && $status !== 'all') { $where[] = 'pu.status = ?'; $params[] = $status; }
        $w = $where ? 'WHERE '.implode(' AND ', $where) : '';
        $sql = "SELECT pu.ref_no, pu.supplier_name AS supplier, pu.purchase_date AS date, pu.subtotal, pu.tax, pu.total, pu.paid, pu.balance, pu.status FROM purchases pu $w ORDER BY pu.purchase_date DESC, pu.ref_no DESC";
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
        $sql2 = "SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS total, COALESCE(SUM(paid),0) AS paid, COALESCE(SUM(balance),0) AS balance FROM purchases pu $w";
        $stmt2 = $pdo->prepare($sql2); $stmt2->execute($params); $summary = $stmt2->fetch();
        $this->json(['rows'=>$rows, 'summary'=>$summary]);
    }

    /* ── Daybook Report ── */
    public function daybook(Request $r): void { Auth::user(); $this->view('reports/daybook', ['_active'=>'reports']); }
    public function apiDaybook(Request $r): void {
        Auth::user();
        $date = $_GET['date'] ?? date('Y-m-d');
        $pdo = Database::pdo();
        $entries = [];
        $s = $pdo->prepare("SELECT sale_date AS date, 'Sale' AS type, invoice_no AS ref_no, customer_name AS party, total AS debit, 0 AS credit FROM sales WHERE sale_date = ? ORDER BY created_at");
        $s->execute([$date]); $entries = array_merge($entries, $s->fetchAll());
        $s = $pdo->prepare("SELECT purchase_date AS date, 'Purchase' AS type, ref_no AS ref_no, supplier_name AS party, 0 AS debit, total AS credit FROM purchases WHERE purchase_date = ? ORDER BY created_at");
        $s->execute([$date]); $entries = array_merge($entries, $s->fetchAll());
        $s = $pdo->prepare("SELECT transaction_date AS date, IF(type='receipt','Receipt','Payment') AS type, transaction_no AS ref_no, party_name AS party, 0 AS debit, amount AS credit FROM transactions WHERE transaction_date = ? AND type='receipt' ORDER BY created_at");
        $s->execute([$date]); $entries = array_merge($entries, $s->fetchAll());
        $s = $pdo->prepare("SELECT transaction_date AS date, 'Payment' AS type, transaction_no AS ref_no, party_name AS party, amount AS debit, 0 AS credit FROM transactions WHERE transaction_date = ? AND type='payment' ORDER BY created_at");
        $s->execute([$date]); $entries = array_merge($entries, $s->fetchAll());
        usort($entries, fn($a,$b) => strcmp($a['type'],$b['type']));
        $totalSales = 0; $totalPurchases = 0; $totalReceipts = 0; $totalPayments = 0;
        foreach ($entries as $e) {
            if ($e['type']==='Sale') $totalSales += (float)$e['debit'];
            elseif ($e['type']==='Purchase') $totalPurchases += (float)$e['credit'];
            elseif ($e['type']==='Receipt') $totalReceipts += (float)$e['credit'];
            elseif ($e['type']==='Payment') $totalPayments += (float)$e['debit'];
        }
        $this->json(['date'=>$date, 'entries'=>$entries, 'summary'=>['sales'=>$totalSales,'purchases'=>$totalPurchases,'receipts'=>$totalReceipts,'payments'=>$totalPayments]]);
    }

    /* ── Invoice Ageing Report (Sales + Purchase) ── */
    public function invoiceAgeing(Request $r): void { Auth::user(); $this->view('reports/invoice-ageing', ['_active'=>'reports']); }
    public function apiInvoiceAgeing(Request $r): void {
        Auth::user();
        $type = $_GET['type'] ?? 'sales';
        $pdo = Database::pdo();
        $buckets = ['0-30'=>0.0,'31-60'=>0.0,'61-90'=>0.0,'90+'=>0.0];
        if ($type === 'sales') {
            $rows = $pdo->query("SELECT invoice_no, customer_name AS party, sale_date AS date, DATEDIFF(CURDATE(), sale_date) AS days_overdue, total, COALESCE(balance, total) AS balance FROM sales WHERE COALESCE(balance, total) > 0 ORDER BY days_overdue DESC")->fetchAll();
        } else {
            $rows = $pdo->query("SELECT ref_no AS invoice_no, supplier_name AS party, purchase_date AS date, DATEDIFF(CURDATE(), purchase_date) AS days_overdue, total, COALESCE(balance, total) AS balance FROM purchases WHERE COALESCE(balance, total) > 0 ORDER BY days_overdue DESC")->fetchAll();
        }
        foreach ($rows as &$r2) {
            $d = (int)$r2['days_overdue'];
            $b = $d <= 30 ? '0-30' : ($d <= 60 ? '31-60' : ($d <= 90 ? '61-90' : '90+'));
            $r2['bucket'] = $b; $buckets[$b] += (float)$r2['balance'];
        }
        $this->json(['buckets'=>$buckets,'rows'=>$rows]);
    }
}
