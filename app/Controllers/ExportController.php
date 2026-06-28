<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Mailer;
use App\Core\Database;
use App\Models\Sale;
use App\Models\Customer;

final class ExportController extends Controller
{
    // GET /api/exports/sales.xlsx
    public function salesXlsx(Request $r): void
    {
        Auth::user();
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $this->json(['ok' => false, 'error' => 'PhpSpreadsheet not installed. Run `composer install`.'], 500); return;
        }
        $rows = Database::pdo()->query("SELECT invoice_no, customer_name, sale_date, subtotal, discount, tax, total, paid, balance, status FROM sales ORDER BY id DESC")->fetchAll();
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $s = $ss->getActiveSheet();
        $s->setTitle('Sales');
        $headers = ['Invoice','Customer','Date','Subtotal','Discount','Tax','Total','Paid','Balance','Status'];
        $s->fromArray($headers, null, 'A1');
        $s->getStyle('A1:J1')->getFont()->setBold(true);
        $i = 2;
        foreach ($rows as $row) { $s->fromArray(array_values($row), null, "A$i"); $i++; }
        foreach (range('A','J') as $col) { $s->getColumnDimension($col)->setAutoSize(true); }
        $file = sys_get_temp_dir() . '/sales_' . date('Ymd_His') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($file);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="sales.xlsx"');
        header('Content-Length: ' . filesize($file));
        readfile($file); @unlink($file); exit;
    }

    // GET /api/exports/invoice/{id}.pdf
    public function invoicePdf(Request $r): void
    {
        Auth::user();
        if (!class_exists('Dompdf\\Dompdf')) {
            $this->json(['ok' => false, 'error' => 'Dompdf not installed. Run `composer install`.'], 500); return;
        }
        $sale = Sale::withItems((int)$r->param('id'));
        if (!$sale) { $this->json(['ok'=>false,'error'=>'Not found'],404); return; }
        $html = $this->invoiceHtml($sale);
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        $download = isset($_GET['download']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $sale['invoice_no'] . '.pdf"');
        echo $dompdf->output(); exit;
    }

    // POST /api/sales/{id}/email
    public function emailInvoice(Request $r): void
    {
        Auth::user(); $this->requireCsrf();
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') || !class_exists('Dompdf\\Dompdf')) {
            $this->json(['ok'=>false,'error'=>'PHPMailer or Dompdf not installed. Run `composer install`.'],500); return;
        }
        $sale = Sale::withItems((int)$r->param('id'));
        if (!$sale) { $this->json(['ok'=>false,'error'=>'Not found'],404); return; }
        $to = trim((string)$r->input('email', ''));
        if ($to === '' && !empty($sale['customer_id'])) {
            $c = Customer::find((int)$sale['customer_id']); $to = $c['email'] ?? '';
        }
        if ($to === '') { $this->json(['ok'=>false,'error'=>'Recipient email required'],400); return; }

        $dompdf = new \Dompdf\Dompdf(['defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($this->invoiceHtml($sale), 'UTF-8');
        $dompdf->setPaper('A4'); $dompdf->render();
        $pdfPath = sys_get_temp_dir() . '/' . $sale['invoice_no'] . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());

        $cfg = Mailer::smtpConfig();
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $cfg['host']; $mail->Port = $cfg['port'];
            if ($cfg['user']) { $mail->SMTPAuth = true; $mail->Username = $cfg['user']; $mail->Password = $cfg['pass']; }
            if ($cfg['secure']) $mail->SMTPSecure = $cfg['secure'];
            $mail->setFrom($cfg['from'], $cfg['name']);
            $mail->addAddress($to, $sale['customer_name']);
            $mail->Subject = 'Invoice ' . $sale['invoice_no'];
            $mail->Body = "Hello {$sale['customer_name']},\n\nPlease find attached invoice {$sale['invoice_no']} for a total of " . number_format((float)$sale['total'],2) . ".\n\nThank you.";
            $mail->addAttachment($pdfPath);
            $mail->send();
            @unlink($pdfPath);
            $this->json(['ok'=>true,'sent_to'=>$to]);
        } catch (\Throwable $e) {
            @unlink($pdfPath);
            $this->json(['ok'=>false,'error'=>'Mailer: ' . $e->getMessage()],500);
        }
    }

    private function invoiceHtml(array $sale): string
    {
        $rows = '';
        foreach ($sale['items'] as $it) {
            $rows .= '<tr><td>' . htmlspecialchars((string)$it['sku']) . '</td><td>' . htmlspecialchars((string)$it['name']) . '</td>'
                . '<td style="text-align:right">' . htmlspecialchars((string)$it['qty']) . '</td>'
                . '<td style="text-align:right">' . number_format((float)$it['price'],2) . '</td>'
                . '<td style="text-align:right">' . number_format((float)$it['total'],2) . '</td></tr>';
        }
        $css = 'body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:11px}'
             . 'h1{font-size:22px;margin:0}h3{font-size:13px;margin:0 0 4px}'
             . 'table{width:100%;border-collapse:collapse;margin-top:10px}'
             . 'th,td{padding:6px 8px;border-bottom:1px solid #e2e8f0}'
             . 'th{background:#f8fafc;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:.05em;color:#475569}'
             . '.right{text-align:right}.muted{color:#64748b}.totals td{border:none;padding:2px 8px}';
        $h = '<html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>'
            . '<table style="border:none;margin:0"><tr><td style="border:none"><h1>StockFlow</h1><div class="muted">Sales &amp; Inventory</div></td>'
            . '<td style="border:none;text-align:right"><h3>INVOICE</h3><div><b>' . htmlspecialchars((string)$sale['invoice_no']) . '</b></div>'
            . '<div class="muted">Date: ' . htmlspecialchars((string)$sale['sale_date']) . '</div>'
            . '<div class="muted">Status: ' . strtoupper((string)$sale['status']) . '</div></td></tr></table>'
            . '<p><b>Bill to:</b><br>' . htmlspecialchars((string)$sale['customer_name']) . '</p>'
            . '<table><thead><tr><th>SKU</th><th>Item</th><th class="right">Qty</th><th class="right">Price</th><th class="right">Total</th></tr></thead><tbody>'
            . $rows . '</tbody></table>'
            . '<table class="totals" style="width:280px;margin-left:auto;margin-top:10px">'
            . '<tr><td class="muted">Subtotal</td><td class="right">' . number_format((float)$sale['subtotal'],2) . '</td></tr>'
            . '<tr><td class="muted">Discount</td><td class="right">-' . number_format((float)$sale['discount'],2) . '</td></tr>'
            . '<tr><td class="muted">Tax</td><td class="right">+' . number_format((float)$sale['tax'],2) . '</td></tr>'
            . '<tr><td><b>Total</b></td><td class="right"><b>' . number_format((float)$sale['total'],2) . '</b></td></tr>'
            . '<tr><td class="muted">Paid</td><td class="right">' . number_format((float)$sale['paid'],2) . '</td></tr>'
            . '<tr><td><b>Balance</b></td><td class="right" style="color:#ea580c"><b>' . number_format((float)$sale['balance'],2) . '</b></td></tr>'
            . '</table>'
            . (empty($sale['notes']) ? '' : '<p><b>Notes:</b><br>' . nl2br(htmlspecialchars((string)$sale['notes'])) . '</p>')
            . '</body></html>';
        return $h;
    }
}
