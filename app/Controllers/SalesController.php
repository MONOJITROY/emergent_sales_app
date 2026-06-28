<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Request; use App\Core\Database;
use App\Models\Sale; use App\Models\Product;

final class SalesController extends Controller {
    public function index(Request $r): void { Auth::user(); $this->view('sales/index', ['_active'=>'sales']); }
    public function create(Request $r): void { Auth::user(); $this->view('sales/new', ['_active'=>'sales']); }
    public function edit(Request $r): void {
        Auth::user();
        $sale = Sale::withItems((int)$r->param('id'));
        if (!$sale) { http_response_code(404); echo '<h1>Not found</h1>'; return; }
        $this->view('sales/edit', ['_active'=>'sales','sale'=>$sale]);
    }

    public function apiUpdate(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $id = (int)$r->param('id');
        $sale = Sale::withItems($id);
        if (!$sale) { $this->json(['ok'=>false,'error'=>'Not found'],404); return; }
        $items = $r->input('items', []);
        if (!is_array($items) || count($items)===0) { $this->json(['ok'=>false,'error'=>'At least one line item is required'],400); return; }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            // Restore stock from old items
            $inc = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            foreach ($sale['items'] as $it) { $inc->execute([(float)$it['qty'], (int)$it['product_id']]); }
            // Reverse old customer balance
            if (!empty($sale['customer_id']) && (float)$sale['balance'] > 0) {
                $pdo->prepare('UPDATE customers SET balance = balance - ? WHERE id = ?')->execute([(float)$sale['balance'], (int)$sale['customer_id']]);
            }
            // Delete old items
            $pdo->prepare('DELETE FROM sale_items WHERE sale_id = ?')->execute([$id]);

            // Recompute
            $subtotal = 0.0; foreach ($items as $it) { $subtotal += (float)($it['total'] ?? ((float)$it['qty']*(float)$it['price'])); }
            $discount = (float)$r->input('discount',0); $tax = (float)$r->input('tax',0);
            $total = max(0, $subtotal - $discount + $tax);
            $paid = min((float)$r->input('paid', (float)$sale['paid']), $total);
            $balance = round($total - $paid, 2);
            $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
            $custId = $r->input('customer_id') ? (int)$r->input('customer_id') : null;

            Sale::update($id, [
                'customer_id'   => $custId,
                'customer_name' => trim((string)$r->input('customer_name', $sale['customer_name'])),
                'subtotal'      => round($subtotal,2),
                'discount'      => $discount, 'tax' => $tax,
                'total'         => round($total,2),
                'paid'          => round($paid,2),
                'balance'       => $balance,
                'status'        => $status,
                'notes'         => (string)$r->input('notes', $sale['notes'] ?? ''),
            ]);
            $insItem = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, sku, name, qty, price, total) VALUES (?,?,?,?,?,?,?)');
            $dec = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($items as $it) {
                $pid=(int)$it['product_id']; $qty=(float)$it['qty']; $price=(float)$it['price']; $tot=(float)($it['total'] ?? ($qty*$price));
                $insItem->execute([$id, $pid, (string)$it['sku'], (string)$it['name'], $qty, $price, $tot]);
                $dec->execute([$qty, $pid]);
            }
            if ($custId && $balance > 0) {
                $pdo->prepare('UPDATE customers SET balance = balance + ? WHERE id = ?')->execute([$balance, $custId]);
            }
            $pdo->commit();
            $this->json(Sale::withItems($id));
        } catch (\Throwable $e) {
            $pdo->rollBack(); $this->json(['ok'=>false,'error'=>$e->getMessage()],500);
        }
    }
    public function view(Request $r): void {
        Auth::user();
        $sale = Sale::withItems((int)$r->param('id'));
        if (!$sale) { http_response_code(404); echo '<h1>Not found</h1>'; return; }
        $this->view('sales/view', ['_active'=>'sales','sale'=>$sale]);
    }
    public function apiList(Request $r): void { Auth::user(); $this->json(Sale::listAll(trim((string)($_GET['q'] ?? '')))); }
    public function apiGet(Request $r): void { Auth::user(); $s = Sale::withItems((int)$r->param('id'));
        if (!$s) { $this->json(['ok'=>false,'error'=>'Not found'],404); return; } $this->json($s); }

    public function apiCreate(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $items = $r->input('items', []);
        if (!is_array($items) || count($items) === 0) { $this->json(['ok'=>false,'error'=>'At least one line item is required'],400); return; }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $subtotal = 0.0;
            foreach ($items as $it) { $subtotal += (float)($it['total'] ?? ((float)$it['qty']*(float)$it['price'])); }
            $discount = (float)$r->input('discount', 0);
            $tax = (float)$r->input('tax', 0);
            $total = max(0, $subtotal - $discount + $tax);
            $paid = min((float)$r->input('paid', 0), $total);
            $balance = round($total - $paid, 2);
            $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
            $invoiceNo = Sale::nextInvoiceNo();
            $user = Auth::check();
            $custId = $r->input('customer_id') ? (int)$r->input('customer_id') : null;

            $saleId = Sale::insert([
                'invoice_no'=>$invoiceNo, 'customer_id'=>$custId,
                'customer_name'=>trim((string)$r->input('customer_name','Walk-in customer')),
                'sale_date'=>date('Y-m-d'),
                'subtotal'=>round($subtotal,2),'discount'=>$discount,'tax'=>$tax,
                'total'=>round($total,2),'paid'=>round($paid,2),'balance'=>$balance,
                'status'=>$status,'notes'=>(string)$r->input('notes',''),
                'created_by'=>$user['id'] ?? null,
            ]);
            $insItem = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, sku, name, qty, price, total) VALUES (?,?,?,?,?,?,?)');
            $decStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($items as $it) {
                $pid = (int)$it['product_id']; $qty = (float)$it['qty']; $price=(float)$it['price']; $tot=(float)($it['total'] ?? ($qty*$price));
                $insItem->execute([$saleId, $pid, (string)$it['sku'], (string)$it['name'], $qty, $price, $tot]);
                $decStock->execute([$qty, $pid]);
            }
            if ($custId && $balance > 0) {
                $pdo->prepare('UPDATE customers SET balance = balance + ? WHERE id = ?')->execute([$balance, $custId]);
            }
            $pdo->commit();
            $this->json(Sale::withItems($saleId));
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok'=>false,'error'=>$e->getMessage()], 500);
        }
    }

    public function apiPayment(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $id = (int)$r->param('id'); $amount = (float)$r->input('amount', 0);
        if ($amount <= 0) { $this->json(['ok'=>false,'error'=>'Amount must be positive'], 400); return; }
        $sale = Sale::find($id); if (!$sale) { $this->json(['ok'=>false,'error'=>'Not found'],404); return; }
        $newPaid = min((float)$sale['total'], (float)$sale['paid'] + $amount);
        $newBalance = round((float)$sale['total'] - $newPaid, 2);
        $newStatus = $newBalance <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');
        $delta = $newPaid - (float)$sale['paid'];
        Sale::update($id, ['paid'=>$newPaid, 'balance'=>$newBalance, 'status'=>$newStatus]);
        if (!empty($sale['customer_id']) && $delta > 0) {
            Database::pdo()->prepare('UPDATE customers SET balance = balance - ? WHERE id = ?')->execute([$delta, (int)$sale['customer_id']]);
        }
        $this->json(Sale::withItems($id));
    }

    public function apiDelete(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $id = (int)$r->param('id'); $sale = Sale::withItems($id);
        if (!$sale) { $this->json(['ok'=>false,'error'=>'Not found'],404); return; }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $inc = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            foreach ($sale['items'] as $it) { $inc->execute([(float)$it['qty'], (int)$it['product_id']]); }
            if (!empty($sale['customer_id']) && (float)$sale['balance'] > 0) {
                $pdo->prepare('UPDATE customers SET balance = balance - ? WHERE id = ?')->execute([(float)$sale['balance'], (int)$sale['customer_id']]);
            }
            Sale::delete($id);
            $pdo->commit();
            $this->json(['ok'=>true]);
        } catch (\Throwable $e) {
            $pdo->rollBack(); $this->json(['ok'=>false,'error'=>$e->getMessage()], 500);
        }
    }
}
