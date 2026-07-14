<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Database;
use App\Models\Transaction;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Company;

final class PaymentsController extends Controller
{
    public function index(Request $r): void
    {
        Auth::user();
        $this->view('payments/index', ['_active' => 'payments']);
    }

    public function create(Request $r): void
    {
        Auth::user();
        $this->view('payments/new', ['_active' => 'payments']);
    }

    public function apiList(Request $r): void
    {
        Auth::user();
        $q = trim((string)($_GET['q'] ?? ''));
        $this->json(Transaction::listAll('payment', $q));
    }

    public function apiGet(Request $r): void
    {
        Auth::user();
        $id = (int)$r->param('id');
        $tx = Transaction::withAllocations($id);
        if (!$tx || $tx['type'] !== 'payment') {
            $this->json(['ok' => false, 'error' => 'Not found'], 404);
            return;
        }
        $this->json($tx);
    }

    public function apiPendingInvoices(Request $r): void
    {
        Auth::user();
        $supplierId = (int)$r->param('id');
        $supplier = Supplier::find($supplierId);
        if (!$supplier) { $this->json(['ok' => false, 'error' => 'Supplier not found'], 404); return; }

        $stmt = Database::pdo()->prepare(
            "SELECT id, ref_no, supplier_name, purchase_date, total,
                    COALESCE(paid, 0) AS paid,
                    COALESCE(balance, total) AS balance
             FROM purchases WHERE supplier_id = ? AND COALESCE(balance, total) > 0 ORDER BY purchase_date ASC, id ASC"
        );
        $stmt->execute([$supplierId]);
        $invoices = $stmt->fetchAll();

        $this->json([
            'supplier' => $supplier,
            'invoices' => $invoices,
        ]);
    }

    public function apiCreate(Request $r): void
    {
        Auth::user();
        $this->requireCsrf();
        $user = Auth::check();

        $supplierId = (int)$r->input('supplier_id', 0);
        $amount = (float)$r->input('amount', 0);
        $mode = (string)$r->input('mode', 'cash');
        $transactionDate = (string)$r->input('transaction_date', date('Y-m-d'));
        $referenceNo = trim((string)$r->input('reference_no', ''));
        $bankName = trim((string)$r->input('bank_name', ''));
        $notes = trim((string)$r->input('notes', ''));
        $allocations = $r->input('allocations', []);

        if ($supplierId <= 0) { $this->json(['ok' => false, 'error' => 'Supplier is required'], 400); return; }
        if ($amount <= 0) { $this->json(['ok' => false, 'error' => 'Amount must be positive'], 400); return; }
        if (!in_array($mode, ['cash', 'cheque', 'upi', 'transfer'], true)) {
            $this->json(['ok' => false, 'error' => 'Invalid payment mode'], 400); return;
        }
        if (!is_array($allocations) || count($allocations) === 0) {
            $this->json(['ok' => false, 'error' => 'At least one invoice allocation is required'], 400); return;
        }

        $supplier = Supplier::find($supplierId);
        if (!$supplier) { $this->json(['ok' => false, 'error' => 'Supplier not found'], 404); return; }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $txNo = Transaction::nextPaymentNo();
            $totalAllocated = 0.0;
            foreach ($allocations as $alloc) {
                $totalAllocated += (float)($alloc['amount'] ?? 0);
            }

            $txId = Transaction::insert([
                'transaction_no' => $txNo,
                'type' => 'payment',
                'party_type' => 'supplier',
                'party_id' => $supplierId,
                'party_name' => $supplier['name'],
                'amount' => round($amount, 2),
                'mode' => $mode,
                'reference_no' => $referenceNo ?: null,
                'bank_name' => $bankName ?: null,
                'transaction_date' => $transactionDate,
                'reconciliation_status' => in_array($mode, ['cheque', 'transfer'], true) ? 'pending' : 'reconciled',
                'notes' => $notes ?: null,
                'created_by' => $user['id'] ?? null,
            ]);

            $insAlloc = $pdo->prepare(
                'INSERT INTO transaction_allocations (transaction_id, invoice_type, invoice_id, invoice_no, allocated_amount) VALUES (?, ?, ?, ?, ?)'
            );
            $updPurchase = $pdo->prepare(
                'UPDATE purchases SET paid = paid + ?, balance = balance - ?, status = IF(balance - ? <= 0, "paid", IF(paid + ? > 0, "partial", "unpaid")) WHERE id = ?'
            );

            $isImmediate = !in_array($mode, ['cheque', 'transfer'], true);

            foreach ($allocations as $alloc) {
                $purchaseId = (int)$alloc['invoice_id'];
                $allocAmt = (float)$alloc['amount'];
                $invoiceNo = (string)$alloc['invoice_no'];
                if ($allocAmt <= 0) continue;

                $insAlloc->execute([$txId, 'purchase', $purchaseId, $invoiceNo, round($allocAmt, 2)]);
                if ($isImmediate) {
                    $updPurchase->execute([round($allocAmt, 2), round($allocAmt, 2), round($allocAmt, 2), round($allocAmt, 2), $purchaseId]);
                }
            }

            if ($isImmediate) {
                Database::pdo()->prepare('UPDATE suppliers SET balance = balance - ? WHERE id = ?')
                    ->execute([round($amount, 2), $supplierId]);
            }

            $pdo->commit();

            $tx = Transaction::withAllocations($txId);

            $this->generatePaymentPdf($tx);

            $this->json($tx);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiPdf(Request $r): void
    {
        Auth::user();
        $id = (int)$r->param('id');
        $tx = Transaction::withAllocations($id);
        if (!$tx || $tx['type'] !== 'payment') {
            $this->json(['ok' => false, 'error' => 'Not found'], 404);
            return;
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            $this->json(['ok' => false, 'error' => 'Dompdf not installed. Run `composer install`.'], 500);
            return;
        }

        $html = $this->paymentHtml($tx);
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $download = isset($_GET['download']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $tx['transaction_no'] . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    private function generatePaymentPdf(array $tx): void
    {
        if (!class_exists('Dompdf\\Dompdf')) return;

        $dir = __DIR__ . '/../../public/payments';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $html = $this->paymentHtml($tx);
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $path = $dir . '/' . $tx['transaction_no'] . '.pdf';
        file_put_contents($path, $dompdf->output());
    }

    private function paymentHtml(array $tx): string
    {
        $company = Company::settings() ?: [];

        $logoHtml = '';
        if (!empty($company['company_logo'])) {
            $logoPath = __DIR__ . '/../../public/assets/images/' . $company['company_logo'];
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoExt = strtolower(pathinfo($company['company_logo'], PATHINFO_EXTENSION));
                $logoMime = $logoExt === 'png' ? 'image/png' : 'image/jpeg';
                $logoHtml = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" alt="Logo" style="max-height:40px;margin-bottom:4px"><br>';
            }
        }

        $allocRows = '';
        foreach ($tx['allocations'] as $alloc) {
            $supplierInv = !empty($alloc['supplier_inv_no']) ? htmlspecialchars($alloc['supplier_inv_no']) : '—';
            $allocRows .= '<tr><td>' . htmlspecialchars($alloc['invoice_no']) . '</td>'
                . '<td>' . $supplierInv . '</td>'
                . '<td style="text-align:right">' . number_format((float)$alloc['allocated_amount'], 2) . '</td></tr>';
        }

        $css = 'body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:11px}'
             . 'h1{font-size:22px;margin:0}h3{font-size:13px;margin:0 0 4px}'
             . 'table{width:100%;border-collapse:collapse;margin-top:10px}'
             . 'th,td{padding:6px 8px;border-bottom:1px solid #e2e8f0}'
             . 'th{background:#f8fafc;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:.05em;color:#475569}'
             . '.right{text-align:right}.muted{color:#64748b}';

        $statusBadge = $tx['reconciliation_status'] === 'reconciled'
            ? '<span style="color:#047857;font-weight:600">RECONCILED</span>'
            : '<span style="color:#b45309;font-weight:600">PENDING RECONCILIATION</span>';

        $surplus = (float)$tx['amount'] - array_reduce($tx['allocations'], fn($s, $a) => $s + (float)$a['allocated_amount'], 0);
        $surplusRow = $surplus > 0.01
            ? '<tr><td>Surplus / Advance</td><td class="right">' . number_format($surplus, 2) . '</td></tr>'
            : '';

        $modeLabel = ucfirst($tx['mode']);
        $refLine = !empty($tx['reference_no']) ? '<div class="muted">' . htmlspecialchars(ucfirst($tx['mode'])) . ' Ref: ' . htmlspecialchars($tx['reference_no']) . '</div>' : '';
        $bankLine = !empty($tx['bank_name']) ? '<div class="muted">Bank: ' . htmlspecialchars($tx['bank_name']) . '</div>' : '';

        $h = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>'
            . '<table style="border:none"><tr><td style="border:none">' . $logoHtml . '<h1>' . htmlspecialchars($company['company_name'] ?: 'StockFlow') . '</h1></td>'
            . '<td style="border:none;text-align:right"><h3>PAYMENT</h3>'
            . '<div><b>' . htmlspecialchars($tx['transaction_no']) . '</b></div>'
            . '<div class="muted">Date: ' . htmlspecialchars($tx['transaction_date']) . '</div>'
            . '<div>' . $statusBadge . '</div></td></tr></table>'
            . '<p><b>Paid to:</b><br>' . htmlspecialchars($tx['party_name']) . '</p>'
            . '<p><b>Mode:</b> ' . $modeLabel . '</p>'
            . $refLine . $bankLine
            . '<table><thead><tr><th>Record No</th><th>Supplier Inv No</th><th class="right">Amount Allocated</th></tr></thead><tbody>'
            . $allocRows . '</tbody></table>'
            . '<table style="width:280px;margin-left:auto;margin-top:10px">'
            . '<tr><td><b>Total Paid</b></td><td class="right"><b>' . number_format((float)$tx['amount'], 2) . '</b></td></tr>'
            . $surplusRow
            . '</table>'
            . (empty($tx['notes']) ? '' : '<p><b>Notes:</b><br>' . nl2br(htmlspecialchars($tx['notes'])) . '</p>')
            . '</body></html>';

        return $h;
    }
}
