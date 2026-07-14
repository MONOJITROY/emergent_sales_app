<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Database;
use App\Models\Transaction;

final class ReconciliationController extends Controller
{
    public function receiptIndex(Request $r): void
    {
        Auth::user();
        $this->view('reconciliation/receipts', ['_active' => 'reconciliation-receipts']);
    }

    public function paymentIndex(Request $r): void
    {
        Auth::user();
        $this->view('reconciliation/payments', ['_active' => 'reconciliation-payments']);
    }

    public function apiReceiptList(Request $r): void
    {
        Auth::user();
        $this->json(Transaction::pendingList('receipt'));
    }

    public function apiPaymentList(Request $r): void
    {
        Auth::user();
        $this->json(Transaction::pendingList('payment'));
    }

    public function apiSettle(Request $r): void
    {
        Auth::user();
        $this->requireCsrf();
        $id = (int)$r->param('id');
        $user = Auth::check();

        $tx = Transaction::withAllocations($id);
        if (!$tx) { $this->json(['ok' => false, 'error' => 'Not found'], 404); return; }
        if ($tx['reconciliation_status'] !== 'pending') {
            $this->json(['ok' => false, 'error' => 'Transaction is not pending reconciliation'], 400);
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $partyType = $tx['party_type'];
            $partyId = (int)$tx['party_id'];
            $amount = (float)$tx['amount'];

            foreach ($tx['allocations'] as $alloc) {
                $allocAmt = (float)$alloc['allocated_amount'];
                if ($allocAmt <= 0) continue;

                if ($alloc['invoice_type'] === 'sale') {
                    $saleId = (int)$alloc['invoice_id'];
                    $pdo->prepare(
                        'UPDATE sales SET paid = paid + ?, balance = balance - ?, status = IF(balance - ? <= 0, "paid", IF(paid + ? > 0, "partial", "unpaid")) WHERE id = ?'
                    )->execute([$allocAmt, $allocAmt, $allocAmt, $allocAmt, $saleId]);
                } elseif ($alloc['invoice_type'] === 'purchase') {
                    $purchaseId = (int)$alloc['invoice_id'];
                    $pdo->prepare(
                        'UPDATE purchases SET paid = paid + ?, balance = balance - ?, status = IF(balance - ? <= 0, "paid", IF(paid + ? > 0, "partial", "unpaid")) WHERE id = ?'
                    )->execute([$allocAmt, $allocAmt, $allocAmt, $allocAmt, $purchaseId]);
                }
            }

            if ($partyType === 'customer' && $partyId > 0) {
                $pdo->prepare('UPDATE customers SET balance = balance - ? WHERE id = ?')
                    ->execute([$amount, $partyId]);
            } elseif ($partyType === 'supplier' && $partyId > 0) {
                $pdo->prepare('UPDATE suppliers SET balance = balance - ? WHERE id = ?')
                    ->execute([$amount, $partyId]);
            }

            Transaction::settle($id, $user['id'] ?? 0);

            $pdo->commit();
            $this->json(Transaction::withAllocations($id));
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
