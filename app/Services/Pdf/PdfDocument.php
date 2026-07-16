<?php
declare(strict_types=1);
namespace App\Services\Pdf;

use App\Models\Company;

abstract class PdfDocument
{
    protected array $data;
    protected array $company;
    protected array $config;

    private static array $defaults = [
        'header_on_all_pages' => true,
        'footer_on_all_pages' => true,
        'paper'               => 'A4',
        'orientation'         => 'portrait',
        'item_rows'           => 11,
    ];

    public function __construct(array $data, array $company, array $config = [])
    {
        $this->data    = $data;
        $this->company = $company;
        $this->config  = array_merge(self::$defaults, $config);
    }

    // ─── Abstract methods ────────────────────────────────────────────
    abstract protected function headerHtml(): string;
    abstract protected function bodyHtml(): string;
    abstract protected function footerHtml(): string;
    abstract protected function documentTitle(): string;

    // ─── Optional override: right side of fixed header ───────────────
    protected function docMetaHtml(): string { return ''; }

    // ─── Main render ──────────────────────────────────────────────────
    public function render(): string
    {
        $css = $this->baseCss();

        $headerOnAll = $this->config['header_on_all_pages'];
        $footerOnAll = $this->config['footer_on_all_pages'];

        // Single @page rule
        $margins = '';
        if ($headerOnAll) $margins .= ' margin-top:75px;';
        if ($footerOnAll) $margins .= ' margin-bottom:80px;';
        if ($margins !== '') {
            $css .= '@page {' . $margins . ' }';
        }

        $headerHtml = $this->headerHtml();
        $bodyHtml   = $this->bodyHtml();
        $footerHtml = $this->footerHtml();

        // Fixed header — two-column: company branding (left) + doc meta (right)
        if ($headerOnAll) {
            $metaHtml = $this->docMetaHtml();
            if ($metaHtml !== '') {
                $headerBlock = '<div class="fixed-header"><table class="fixed-hdr-table"><tr>'
                    . '<td class="fixed-hdr-left">' . $this->companyHeaderHtml() . '</td>'
                    . '<td class="fixed-hdr-right">' . $metaHtml . '</td>'
                    . '</tr></table></div>';
            } else {
                $headerBlock = '<div class="fixed-header">' . $this->companyHeaderHtml() . '</div>';
            }
            $headerFlow = $headerHtml;
        } else {
            $headerBlock = $this->companyHeaderHtml();
            $headerFlow  = $headerHtml;
        }

        // Fixed footer — running total + page number
        if ($footerOnAll) {
            $footerBlock = '<div class="fixed-footer">' . $this->repeatFooterHtml() . '</div>';
            $footerFlow  = $footerHtml;
        } else {
            $footerBlock = '';
            $footerFlow  = $footerHtml;
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>'
            . $headerBlock
            . '<div class="body-content">' . $headerFlow . $bodyHtml . $footerFlow . '</div>'
            . $footerBlock
            . '</body></html>';
    }

    // ─── Repeating footer (running total for fixed footer on all pages) ─
    protected function repeatFooterHtml(): string
    {
        $d     = $this->data;
        $total = (float)($d['total'] ?? 0);

        /* $html = '<div class="running-total">';
        $html .= '<div class="section-title">Running Total</div>';
        $html .= '<div class="running-total-amount">' . $this->money($total) . '</div>';
        $html .= '</div>';
        return $html; */
        return "";
    }

    // ─── Output PDF ───────────────────────────────────────────────────
    public function output(string $filename, bool $download = true): void
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            http_response_code(500);
            echo 'Dompdf not installed. Run `composer install`.';
            return;
        }

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml($this->render(), 'UTF-8');
        $dompdf->setPaper($this->config['paper'], $this->config['orientation']);
        $dompdf->render();

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $safeName . '.pdf"');
        echo $dompdf->output();
        exit;
    }

    // ─── Tax Breakup ──────────────────────────────────────────────────
    protected function taxBreakup(array $items): array
    {
        $groups = [];
        foreach ($items as $it) {
            $rate = (float)($it['tax_pct'] ?? 0);
            if ($rate <= 0) continue;
            $hsn   = $it['hsn'] ?? '—';
            $tduty = $it['typeofduty'] ?? 'GST';
            $key   = $hsn . '|' . $rate . '|' . $tduty;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'hsn'          => $hsn,
                    'rate'         => $rate,
                    'typeofduty'   => $tduty,
                    'taxableValue' => 0.0,
                    'taxAmount'    => 0.0,
                ];
            }
            $groups[$key]['taxableValue'] += (float)($it['total'] ?? 0);
            $groups[$key]['taxAmount']    += (float)($it['total'] ?? 0) * $rate / 100;
        }
        return $groups;
    }

    protected function taxBreakupHtml(array $items): string
    {
        $groups = $this->taxBreakup($items);
        if (empty($groups)) return '';

        $tTax = $tCg = $tSg = $tTx = 0.0;
        $rows = '';

        foreach ($groups as $g) {
            $isGst = $g['typeofduty'] === 'GST';
            $half  = $isGst ? $g['rate'] / 2 : $g['rate'];
            $cg    = $g['taxableValue'] * $half / 100;
            $sg    = $isGst ? $cg : 0.0;
            $tTax += $g['taxableValue'];
            $tCg  += $cg;
            $tSg  += $sg;
            $tTx  += $g['taxAmount'];

            $rows .= '<tr>'
                . '<td>' . $this->e($g['hsn']) . '</td>'
                . '<td class="r">' . $this->money($g['taxableValue']) . '</td>'
                . '<td class="r">' . $half . '%</td>'
                . '<td class="r">' . $this->money($cg) . '</td>'
                . '<td class="r">' . ($isGst ? $half . '%' : '—') . '</td>'
                . '<td class="r">' . $this->money($sg) . '</td>'
                . '<td class="r fw">' . $this->money($g['taxAmount']) . '</td>'
                . '</tr>';
        }

        $rows .= '<tr class="bold">'
            . '<td>Total</td>'
            . '<td class="r">' . $this->money($tTax) . '</td>'
            . '<td></td>'
            . '<td class="r">' . $this->money($tCg) . '</td>'
            . '<td></td>'
            . '<td class="r">' . $this->money($tSg) . '</td>'
            . '<td class="r">' . $this->money($tTx) . '</td>'
            . '</tr>';

        return '<table class="tax-table">'
            . '<thead><tr>'
            . '<th>HSN/SAC</th><th class="r">Taxable Value</th>'
            . '<th class="c" colspan="2">CGST</th>'
            . '<th class="c" colspan="2">SGST / UTGST</th>'
            . '<th class="r">Total</th>'
            . '</tr><tr>'
            . '<th></th><th></th>'
            . '<th class="r n">Rate</th><th class="r n">Amount</th>'
            . '<th class="r n">Rate</th><th class="r n">Amount</th>'
            . '<th></th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';
    }

    // ─── Number to Words (Indian format) ──────────────────────────────
    protected function numToWords(float $number): string
    {
        $number   = round($number, 2);
        $whole    = (int)$number;
        $fraction = (int)(($number - $whole) * 100);
        $words    = $this->convertGroup($whole);
        if ($fraction > 0) {
            $words .= ' and ' . $this->convertGroup($fraction) . ' Paise';
        }
        return $words . ' Only';
    }

    private function convertGroup(int $num): string
    {
        if ($num === 0) return 'Zero';
        $ones     = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                     'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                     'Seventeen', 'Eighteen', 'Nineteen'];
        $tens     = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $suffixes = ['', 'Thousand', 'Lakh', 'Crore'];
        $result   = '';
        $sIdx     = 0;
        while ($num > 0) {
            $chunk = $num % 1000;
            if ($chunk > 0) {
                $part = '';
                $h    = (int)($chunk / 100);
                if ($h > 0) $part .= $ones[$h] . ' Hundred ';
                $r = $chunk % 100;
                if ($r > 0) {
                    $part .= ($r < 20 ? $ones[$r] : $tens[(int)($r / 10)] . ($r % 10 ? ' ' . $ones[$r % 10] : '')) . ' ';
                }
                $result = $part . $suffixes[$sIdx] . ' ' . $result;
            }
            $num  = (int)($num / 1000);
            $sIdx++;
        }
        return trim($result);
    }

    // ─── Company Header HTML (left side of fixed header) ──────────────
    protected function companyHeaderHtml(): string
    {
        $logoHtml = '';
        if (!empty($this->company['company_logo'])) {
            $path = __DIR__ . '/../../../public/assets/images/' . $this->company['company_logo'];
            if (file_exists($path)) {
                $data  = base64_encode(file_get_contents($path));
                $ext   = strtolower(pathinfo($this->company['company_logo'], PATHINFO_EXTENSION));
                $mime  = match ($ext) { 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/jpeg' };
                $logoHtml = '<img src="data:' . $mime . ';base64,' . $data . '" style="max-height:50px;margin-bottom:2px"><br>';
            }
        }

        $name    = $this->e($this->company['company_name'] ?? '');
        $address = $this->e($this->company['company_address'] ?? '');
        $phone   = $this->e($this->company['company_phone'] ?? '');
        $email   = $this->e($this->company['company_email'] ?? '');
        $gst     = $this->e($this->company['company_gst_no'] ?? '');
        $pan     = $this->e($this->company['company_pan'] ?? '');

        $html = $logoHtml;
        $html .= '<div class="company-name">' . $name . '</div>';
        if ($address) $html .= '<div class="small">' . $address . '</div>';
        if ($phone || $email) {
            $html .= '<div class="small">';
            if ($phone) $html .= 'Phone: ' . $phone;
            if ($phone && $email) $html .= ' | ';
            if ($email) $html .= 'Email: ' . $email;
            $html .= '</div>';
        }
        if ($gst) $html .= '<div class="small">GST No: ' . $gst . '</div>';
        elseif ($pan) $html .= '<div class="small">PAN: ' . $pan . '</div>';
        return $html;
    }

    // ─── Bill To / Ship To HTML ───────────────────────────────────────
    protected function partyHtml(string $title, string $name, array $fields = []): string
    {
        $html = '<div class="party-box"><div class="party-title">' . $this->e($title) . '</div>';
        $html .= '<div class="party-name">' . $this->e($name) . '</div>';
        foreach ($fields as $label => $value) {
            if ($value !== '') {
                $html .= '<div class="small">' . $this->e($label) . ': ' . $this->e($value) . '</div>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    // ─── Bank Details HTML ────────────────────────────────────────────
    protected function bankDetailsHtml(): string
    {
        $c = $this->company;
        $parts = [];
        if (!empty($c['bank_name']))               $parts[] = '<b>Bank:</b> ' . $this->e($c['bank_name']);
        if (!empty($c['bank_account_holder_name'])) $parts[] = '<b>A/C Holder:</b> ' . $this->e($c['bank_account_holder_name']);
        if (!empty($c['bank_account_no']))          $parts[] = '<b>A/C No:</b> ' . $this->e($c['bank_account_no']);
        if (!empty($c['bank_ifsc_code']))           $parts[] = '<b>IFSC:</b> ' . $this->e($c['bank_ifsc_code']);
        if (!empty($c['bank_branch_name']))         $parts[] = '<b>Branch:</b> ' . $this->e($c['bank_branch_name']);

        if (empty($parts)) return '';

        return '<div class="bank-box"><div class="section-title">Bank Details</div>'
            . implode('<br>', $parts) . '</div>';
    }

    // ─── Signature Block HTML ─────────────────────────────────────────
    protected function signatureBlockHtml(): string
    {
        $owner = $this->e($this->company['owner_name'] ?? '');
        $comp  = $this->e($this->company['company_name'] ?? '');
        if ($owner === '' && $comp === '') return '';

        return '<div class="sig-box">'
            . '<div class="section-title">Authorised Signatory</div>'
            . '<div class="sig-line"></div>'
            . '<div class="sig-name">' . $owner . '</div>'
            . '<div class="sig-company">' . $comp . '</div>'
            . '</div>';
    }

    // ─── Amount in Words HTML ─────────────────────────────────────────
    protected function amountInWordsHtml(float $total): string
    {
        return '<div class="amt-words"><b>Amount in words:</b> Rupees ' . $this->numToWords($total) . '</div>';
    }

    // ─── Totals HTML (right-aligned summary) ─────────────────────────
    protected function totalsHtml(array $rows): string
    {
        $html = '<table class="totals-table">';
        foreach ($rows as $label => $value) {
            $isBold   = $value['bold'] ?? false;
            $isOrange = $value['orange'] ?? false;
            $cls      = $isBold ? ' class="bold"' : ($isOrange ? ' class="orange"' : '');
            $sign     = $value['sign'] ?? '';
            $html .= '<tr' . $cls . '><td>' . $this->e($label) . '</td>'
                . '<td class="r">' . $sign . $this->money((float)$value['amount']) . '</td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

    // ─── Helpers ──────────────────────────────────────────────────────
    protected function e(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    protected function money(float $val): string
    {
        return number_format($val, 2);
    }

    protected function formatDate(string $date): string
    {
        if ($date === '' || $date === '0000-00-00') return '';
        return date('d-m-Y', strtotime($date));
    }

    // ─── Base CSS ─────────────────────────────────────────────────────
    private function baseCss(): string
    {
        return '
* { margin:0; padding:0; }
body { font-family:DejaVu Sans,sans-serif; color:#1a1a1a; font-size:10px; line-height:1.4; margin-top:0.5in; margin-bottom:0.5in; margin-left:0.3in; margin-right:0.3in; }
.fixed-header { position:fixed; top:0; width:100%; }
.fixed-footer { position:fixed; bottom:0; width:100%; }
.body-content { padding:0; }

/* Fixed header — two-column table */
.fixed-hdr-table { width:92%; border-bottom:2px solid #1a1a1a; padding-bottom:4px; margin-top:1.3rem;}
.fixed-hdr-table td { vertical-align:top; }
.fixed-hdr-left { width:45%; }
.fixed-hdr-right { width:50%; text-align:right; }

/* Company header — compact */
.company-name { font-size:13px; font-weight:700; margin-bottom:2px; }
.small { font-size:8px; color:#555; }

/* Document meta (right side of fixed header) */
.doc-title { font-size:16px; font-weight:700; margin-bottom:3px; margin-right:1rem; }
.doc-meta { font-size:9px; text-align:right; }
.doc-meta div { margin-bottom:1px; }

/* Party boxes */
.party-section { width:100%; margin-bottom:10px; margin-top:5.7rem; }
.party-section td { width:50%; vertical-align:top; padding-right:10px; }
.party-section td:last-child { padding-right:0; padding-left:10px; }
.party-box { margin-bottom:6px; }
.party-title { font-size:8px; text-transform:uppercase; letter-spacing:.06em; color:#666; font-weight:600; margin-bottom:3px; }
.party-name { font-weight:600; font-size:11px; margin-bottom:2px; }

/* Items table */
.items-table { width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px; }
.items-table th { background:#f0f0f0; font-size:8px; text-transform:uppercase; letter-spacing:.04em; padding:5px 6px; border:1px solid #ccc; color:#555; }
.items-table td { padding:5px 6px; border-bottom:1px solid #e5e5e5; }
.items-table tbody tr:last-child td { border-bottom:2px solid #1a1a1a; }
.items-table th.l, .items-table td.l { text-align:left; }
.items-table th.r, .items-table td.r { text-align:right; }
.items-table th.c, .items-table td.c { text-align:center; }
.items-table th.slno { width:4%; }
.items-table th.itemname { width:30%; }
.items-table th.hsnsac { width:12%; }
.items-table th.qnty { width:7%; }
.items-table th.qunit { width:7%; }
.items-table th.rate { width:10%; }
.items-table th.taxrate { width:8%; }
.items-table th.taxamt { width:10%; }
.items-table th.linetotal { width:12%; }

/* Sub-footer (tax breakup + totals, body flow, last page only) */
.sub-footer { width:100%; margin-top:8px; margin-bottom:8px; }
.sub-footer td { vertical-align:top; }

/* Tax breakup table */
.tax-table { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:0; }
.tax-table th { font-size:8px; text-transform:uppercase; letter-spacing:.03em; background:#f8f8f8; padding:4px 5px; border:1px solid #ccc; }
.tax-table td { padding:4px 5px; border:1px solid #e5e5e5; }

/* Totals table */
.totals-table { width:220px; margin-left:auto; margin-bottom:8px; }
.totals-table td { padding:3px 8px; font-size:10px; }
.totals-table .bold td { border-top:1px solid #1a1a1a; padding-top:5px; font-weight:700; }
.totals-table .orange td { color:#ea580c; font-weight:700; }

/* Running total (fixed footer) */
.running-total { text-align:right; padding:4px 0; }
.running-total .section-title { display:inline; margin-right:8px; }
.running-total-amount { display:inline; font-size:12px; font-weight:700; }

/* Bank box */
.bank-box { margin-bottom:5px; font-size:9px; line-height:1.6; }

/* Signature */
.sig-box { text-align:right; margin-top:20px; }
.sig-line { width:200px; border-top:1px solid #999; margin-left:auto; margin-bottom:4px; margin-top:40px; }
.sig-name { font-weight:600; font-size:10px; }
.sig-company { font-size:9px; color:#555; }

/* Amount in words */
.amt-words { font-size:9.5px; margin-bottom:8px; padding:6px 8px; background:#f8f8f8; border:1px solid #e5e5e5; }

/* Section title */
.section-title { font-size:8px; text-transform:uppercase; letter-spacing:.06em; color:#666; font-weight:600; margin-bottom:4px; }

/* Terms */
.terms { font-size:8.5px; color:#555; margin-top:8px; line-height:1.6; }
.terms .section-title { margin-bottom:2px; }

/* Utility */
.r { text-align:right; }
.c { text-align:center; }
.fw { font-weight:600; }
.bold { font-weight:700; }
.orange { color:#ea580c; font-weight:700; }
.n { font-weight:400; }

/* Page break */
.page-break { page-break-before:always; }
';
    }
}
