<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Mailer;
use App\Core\Database;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Company;

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

    // GET /api/invoice/{id}/render
    public function renderInvoice(Request $r): void
    {
        Auth::user();
        $sale = Sale::withItems((int)$r->param('id'));
        if (!$sale) { http_response_code(404); echo 'Not found'; return; }
        $company = Company::settings() ?: [];
        if (empty($company['invoice_template'])) {
            echo '<p class="text-muted">No invoice template configured.</p>';
            return;
        }
        $html = $this->renderInvoiceTemplate($sale, $company);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    // Shared rendering entry — used by PDF, email, and the iframe
    private function invoiceHtml(array $sale): string
    {
        $company = Company::settings() ?: [];
        if (!empty($company['invoice_template'])) {
            $rendered = $this->renderInvoiceTemplate($sale, $company);
            if ($rendered !== '') return $rendered;
        }

        $logoHtml = '';
        $nameHtml = htmlspecialchars($company['company_name'] ?: 'StockFlow');
        if (!empty($company['company_logo'])) {
            $logoPath = __DIR__ . '/../../public/assets/images/' . $company['company_logo'];
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoExt = strtolower(pathinfo($company['company_logo'], PATHINFO_EXTENSION));
                $logoMime = $logoExt === 'png' ? 'image/png' : ($logoExt === 'gif' ? 'image/gif' : ($logoExt === 'webp' ? 'image/webp' : 'image/jpeg'));
                $logoHtml = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" alt="Logo" style="max-height:40px;margin-bottom:4px"><br>';
            }
        }

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
            . '<table style="border:none;margin-left:-7px;"><tr><td style="border:none">' . $logoHtml . '<h1 style="display:inline">' . $nameHtml . '</h1></td>'
            . '<td style="border:none;text-align:right"><h3>INVOICE</h3><div><b>' . htmlspecialchars((string)$sale['invoice_no']) . '</b></div>'
            . '<div class="muted">Date: ' . htmlspecialchars((string)$sale['sale_date']) . '</div>'
            . '<div class="muted">Status: ' . strtoupper((string)$sale['status']) . '</div></td></tr></table>'
            . '<p><b>Bill to:</b><br>' . htmlspecialchars((string)$sale['customer_name']) . '<br>'
            . htmlspecialchars((string)$sale['customer']['address'] ?? '') . '<br>' . htmlspecialchars((string)$sale['customer']['phone'] ?? '') . '<br>' . htmlspecialchars((string)$sale['customer']['email'] ?? '') . '</p>'
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

    // ─── Template rendering ────────────────────────────────────────────

    private function renderInvoiceTemplate(array $sale, array $company): string
    {
        $templateName = $company['invoice_template'] ?? '';
        if ($templateName === '') return '';
        $templateFile = __DIR__ . '/../../public/invoice-templates/' . $templateName . '/' . $templateName . '.html';
        if (!file_exists($templateFile)) return '';

        $html = file_get_contents($templateFile);

        // Inject <base> tag so relative paths (./jquery.min.js etc.) resolve to the template directory
        $baseUrl = App::config('base_url') ?: '';
        $baseHref = $baseUrl . '/invoice-templates/' . rawurlencode($templateName) . '/';
        $html = preg_replace('/(<head\b[^>]*>)/i', '$1<base href="' . $baseHref . '">', $html, 1);

        // Replacements that apply before item rows
        $replace = [];

        // Logo
        $replace['{businesslogo}'] = $this->logoDataUri($company);

        // Company
        $replace['{companyname}'] = htmlspecialchars($company['company_name'] ?? '');
        $replace['{companyaddress}'] = htmlspecialchars($company['company_address'] ?? '');
        $replace['{compnygstno}'] = htmlspecialchars($company['company_gst_no'] ?? '');
        $replace['{businessname}'] = htmlspecialchars($company['company_name'] ?? '');
        $replace['{businessaddress}'] = htmlspecialchars($company['company_address'] ?? '');
        $replace['{businesscity}'] = '';
        $replace['{businesscountry}'] = '';
        $replace['{businessemail}'] = htmlspecialchars($company['company_email'] ?? '');
        $replace['{companybankdetails}'] = $this->formatBankDetails($company);

        // Sale
        $replace['{invoiceno}'] = htmlspecialchars($sale['invoice_no'] ?? '');
        $replace['{invoicedateinddmmyyyy}'] = date('d-m-Y', strtotime($sale['sale_date'] ?? ''));
        $replace['{totalinvoiceamountbeforetax}'] = number_format((float)($sale['subtotal'] ?? 0), 2);
        $replace['{taxamount}'] = number_format((float)($sale['tax'] ?? 0), 2);
        $replace['{totalinvoiceamountaftertax}'] = number_format((float)($sale['total'] ?? 0), 2);
        $replace['{amountinwords}'] = $this->numToWords((float)($sale['total'] ?? 0));

        // Customer
        $customer = $sale['customer'] ?? [];
        $customerName  = htmlspecialchars($sale['customer_name'] ?? '');
        $customerAddr  = htmlspecialchars($customer['address'] ?? '');
        $customerCity  = '';
        $customerCountry = '';
        $customerEmail = htmlspecialchars($customer['email'] ?? '');
        // Handle both single and double brace variants used across templates
        $replace['{{customername}}'] = $customerName;
        $replace['{{customeraddress}}'] = $customerAddr;
        $replace['{{customercity}}'] = $customerCity;
        $replace['{{customercountry}}'] = $customerCountry;
        $replace['{{customeremail}}'] = $customerEmail;
        $replace['{customername}'] = $customerName;
        $replace['{customeraddress}'] = $customerAddr;
        $replace['{customercity}'] = $customerCity;
        $replace['{customercountry}'] = $customerCountry;
        $replace['{customeremail}'] = $customerEmail;

        // Apply global replacements first
        $html = str_replace(array_keys($replace), array_values($replace), $html);

        // Process item rows
        $html = $this->processItemRows($html, $sale['items'] ?? []);

        return $html;
    }

    private function processItemRows(string $html, array $items): string
    {
        $itemCells = ['{slno}','{itemname}','{Quantity}','{Price}','{lineamount}'];
        $tbodyStart = 0; $found = false;
        while (($tbodyStart = strpos($html, '<tbody>', $tbodyStart)) !== false) {
            $end = strpos($html, '</tbody>', $tbodyStart);
            if ($end === false) break;
            $content = substr($html, $tbodyStart, $end - $tbodyStart + 9);
            foreach ($itemCells as $ph) {
                if (str_contains($content, $ph)) { $found = true; break 2; }
            }
            $tbodyStart = $end + 9;
        }
        if (!$found) return $html;
        $tbodyLen = $end - $tbodyStart + 9;

        $tbodyContent = substr($html, $tbodyStart, $tbodyLen);

        // Find first <tr> inside tbody — this is the item row template
        $trStart = strpos($tbodyContent, '<tr>', 7);
        $trEnd = strpos($tbodyContent, '</tr>', $trStart);
        if ($trStart === false || $trEnd === false) return $html;

        $itemRowTemplate = substr($tbodyContent, $trStart, $trEnd - $trStart + 6);
        $afterItemRow = substr($tbodyContent, $trEnd + 6);

        // Generate item rows
        $itemRows = '';
        $slno = 1;
        foreach ($items as $it) {
            $row = $itemRowTemplate;
            $rowReplace = [
                '{slno}'          => $slno,
                '{itemname}'      => htmlspecialchars($it['name'] ?? ''),
                '{hsn/sac}'       => htmlspecialchars($it['hsn'] ?? ''),
                '{Quantity}'      => (float)($it['qty'] ?? 0),
                '{Price}'         => number_format((float)($it['price'] ?? 0), 2),
                '{per}'           => htmlspecialchars($it['unit'] ?? ''),
                '{lineamount}'    => number_format((float)($it['total'] ?? 0), 2),
            ];
            $row = str_replace(array_keys($rowReplace), array_values($rowReplace), $row);
            $itemRows .= $row;
            $slno++;
        }

        $newTbody = '<tbody>' . $itemRows . $afterItemRow;
        $html = substr_replace($html, $newTbody, $tbodyStart, $tbodyLen);
        return $html;
    }

    private function logoDataUri(array $company): string
    {
        if (empty($company['company_logo'])) return '';
        $path = __DIR__ . '/../../public/assets/images/' . $company['company_logo'];
        if (!file_exists($path)) return '';
        $data = base64_encode(file_get_contents($path));
        $ext = strtolower(pathinfo($company['company_logo'], PATHINFO_EXTENSION));
        $mime = match ($ext) { 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/jpeg' };
        return 'data:' . $mime . ';base64,' . $data;
    }

    private function formatBankDetails(array $company): string
    {
        $parts = [];
        if (!empty($company['bank_name'])) $parts[] = 'Bank: <b>' . $company['bank_name']."</b><br>";
        if (!empty($company['bank_account_holder_name'])) $parts[] = 'A/c Holder: <b>' . $company['bank_account_holder_name']."</b><br>";
        if (!empty($company['bank_account_no'])) $parts[] = 'A/c No: <b>' . $company['bank_account_no']."</b><br>";
        if (!empty($company['bank_ifsc_code'])) $parts[] = 'IFSC: <b>' . $company['bank_ifsc_code']."</b><br>";
        if (!empty($company['bank_branch_name'])) $parts[] = 'Branch: <b>' . $company['bank_branch_name']."</b><br>";
        if (!empty($company['bank_swift_code'])) $parts[] = 'SWIFT: <b>' . $company['bank_swift_code']."</b><br>";
        if (!empty($company['bank_iban'])) $parts[] = 'IBAN: <b>' . $company['bank_iban']."</b><br>";
        if (!empty($company['bank_upi_id'])) $parts[] = 'UPI: <b>' . $company['bank_upi_id']."</b><br>";
        return implode("\n", $parts);
    }

    private function numToWords(float $number): string
    {
        $number = round($number, 2);
        $whole = (int)$number;
        $fraction = (int)(($number - $whole) * 100);
        $words = $this->convertNumberToWords($whole);
        if ($fraction > 0) {
            $words .= ' and ' . $this->convertNumberToWords($fraction) . ' Paise';
        }
        return $words . ' Only';
    }

    private function convertNumberToWords(int $num): string
    {
        if ($num === 0) return 'Zero';
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $suffixes = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];
        if ($num < 20) return $ones[$num];
        if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
        $words = '';
        $suffixIdx = 0;
        while ($num > 0) {
            $chunk = $num % 1000;
            if ($chunk > 0) {
                $chunkWords = '';
                $h = (int)($chunk / 100);
                if ($h > 0) $chunkWords .= $ones[$h] . ' Hundred ';
                $r = $chunk % 100;
                if ($r > 0) {
                    if ($r < 20) $chunkWords .= $ones[$r] . ' ';
                    else $chunkWords .= $tens[(int)($r / 10)] . ($r % 10 ? ' ' . $ones[$r % 10] : '') . ' ';
                }
                $words = $chunkWords . $suffixes[$suffixIdx] . ' ' . $words;
            }
            $num = (int)($num / 1000);
            $suffixIdx++;
        }
        return trim($words);
    }
}
