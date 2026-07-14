<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\Request; use App\Core\Database;
use App\Models\Purchase;

final class PurchasesController extends Controller {
    public function index(Request $r): void { Auth::user(); $this->view('purchases/index', ['_active'=>'purchases']); }
    public function apiList(Request $r): void { Auth::user(); $this->json(Purchase::listAll(trim((string)($_GET['q'] ?? '')))); }

    public function apiCreate(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $items = $r->input('items', []);
        if (!is_array($items) || count($items)===0) { $this->json(['ok'=>false,'error'=>'At least one line item is required'],400); return; }
        $invNo = trim((string)$r->input('supplier_inv_no',''));
        if ($invNo === '') { $this->json(['ok'=>false,'error'=>'Supplier invoice number is required'],400); return; }
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $subtotal = 0.0;
            foreach ($items as $it) { $subtotal += (float)($it['total'] ?? ((float)$it['qty']*(float)$it['price'])); }
            $tax = (float)$r->input('tax',0); $total = round($subtotal + $tax, 2);
            $refNo = Purchase::nextRefNo();
            $user = Auth::check();
            $supId = $r->input('supplier_id') ? (int)$r->input('supplier_id') : null;
            $purId = Purchase::insert([
                'ref_no'=>$refNo, 'supplier_id'=>$supId,
                'supplier_name'=>trim((string)$r->input('supplier_name','')),
                'purchase_date'=>(string)$r->input('purchase_date',date('Y-m-d')),
                'supplier_inv_no'=>$invNo,
                'supplier_inv_date'=>$r->input('supplier_inv_date') ?: null,
                'subtotal'=>round($subtotal,2),'tax'=>$tax,'total'=>$total,
                'paid'=>0,'balance'=>$total,'status'=>'unpaid',
                'notes'=>(string)$r->input('notes',''),
                'created_by'=>$user['id'] ?? null,
            ]);
            $insItem = $pdo->prepare('INSERT INTO purchase_items (purchase_id, product_id, sku, name, qty, price, total) VALUES (?,?,?,?,?,?,?)');
            $incStock = $pdo->prepare('UPDATE products SET stock = stock + ?, cost_price = ? WHERE id = ?');
            foreach ($items as $it) {
                $pid=(int)$it['product_id']; $qty=(float)$it['qty']; $price=(float)$it['price']; $tot=(float)($it['total'] ?? ($qty*$price));
                $insItem->execute([$purId, $pid, (string)$it['sku'], (string)$it['name'], $qty, $price, $tot]);
                $incStock->execute([$qty, $price, $pid]);
            }
            if ($supId && $total > 0) {
                $pdo->prepare('UPDATE suppliers SET balance = balance + ? WHERE id = ?')->execute([$total, $supId]);
            }
            $pdo->commit();
            $this->json(['ok'=>true,'id'=>$purId,'ref_no'=>$refNo]);
        } catch (\Throwable $e) {
            $pdo->rollBack(); $this->json(['ok'=>false,'error'=>$e->getMessage()], 500);
        }
    }

    public function apiDelete(Request $r): void {
        Auth::user(); $this->requireCsrf();
        $id = (int)$r->param('id'); $p = Purchase::find($id);
        if (!$p) { $this->json(['ok'=>false,'error'=>'Not found'],404); return; }
        $items = Purchase::items($id);
        $pdo = Database::pdo(); $pdo->beginTransaction();
        try {
            $dec = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($items as $it) { $dec->execute([(float)$it['qty'], (int)$it['product_id']]); }
            if (!empty($p['supplier_id']) && (float)($p['balance'] ?? 0) > 0) {
                $pdo->prepare('UPDATE suppliers SET balance = balance - ? WHERE id = ?')->execute([(float)$p['balance'], (int)$p['supplier_id']]);
            }
            Purchase::delete($id);
            $pdo->commit(); $this->json(['ok'=>true]);
        } catch (\Throwable $e) {
            $pdo->rollBack(); $this->json(['ok'=>false,'error'=>$e->getMessage()], 500);
        }
    }
}
