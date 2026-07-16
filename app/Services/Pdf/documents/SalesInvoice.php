<?php
declare(strict_types=1);
namespace App\Services\Pdf\Documents;

use App\Services\Pdf\PdfDocument;

class SalesInvoice extends PdfDocument
{
    public function __construct(array $data, array $company, array $config = [])
    {
        $defaults = [
            'header_on_all_pages' => true,
            'footer_on_all_pages' => true,
        ];
        parent::__construct($data, $company, array_merge($defaults, $config));
    }

    protected function documentTitle(): string
    {
        return 'Sale-Invoice';
    }

    // ─── Right side of fixed header ───────────────────────────────────
    protected function docMetaHtml(): string
    {
        $s     = $this->data;
        $no    = $this->e($s['invoice_no'] ?? '');
        $date  = $this->formatDate($s['sale_date'] ?? '');
        $state = strtoupper($this->e($s['status'] ?? ''));

        $html  = '<div class="doc-title">INVOICE</div>';
        $html .= '<div class="doc-meta">';
        $html .= '<div><b>Invoice #:</b> ' . $no . '</div>';
        $html .= '<div><b>Date:</b> ' . $date . '</div>';
        $html .= '<div><b>Status:</b> ' . $state . '</div>';
        $html .= '</div>';
        return $html;
    }

    // ─── Header (first page only — parties) ──────────────────────────
    protected function headerHtml(): string
    {
        $s    = $this->data;
        $cust = $s['customer'] ?? [];

        $html  = '<table class="party-section"><tr>';
        $html .= '<td>' . $this->partyHtml('Bill To', $s['customer_name'] ?? '', [
            'Address' => $cust['address'] ?? '',
            'Phone'   => $cust['phone'] ?? '',
            'Email'   => $cust['email'] ?? '',
        ]) . '</td>';
        $html .= '<td>' . $this->partyHtml('Ship To', $s['customer_name'] ?? '', [
            'Address' => $cust['address'] ?? '',
            'Phone'   => $cust['phone'] ?? '',
            'Email'   => $cust['email'] ?? '',
        ]) . '</td>';
        $html .= '</tr></table>';
        return $html;
    }

    // ─── Body (items table padded + tax breakup + totals) ─────────────
    protected function bodyHtml(): string
    {
        $s       = $this->data;
        $items   = $s['items'] ?? [];
        $maxRows = $this->config['item_rows'] ?? 15;

        // Items table — 9 columns
        $html = '<table class="items-table"><thead><tr>'
            . '<th class="slno">Sl<br>No</th>'
            . '<th class="itemname l">Description of goods</th>'
            . '<th class="hsnsac l">HSN/SAC</th>'
            . '<th class="qnty r">Qty</th>'
            . '<th class="qunit l">Per</th>'
            . '<th class="rate r">Rate</th>'
            . '<th class="taxrate r">Tax %</th>'
            . '<th class="taxamt r">Tax Amt</th>'
            . '<th class="linetotal r">Amount</th>'
            . '</tr></thead><tbody>';

        $slno = 1;
        foreach ($items as $it) {
            $total    = (float)($it['total'] ?? 0);
            $taxPct   = (float)($it['tax_pct'] ?? 0);
            $taxAmt   = $total * $taxPct / 100;
            $afterTax = $total + $taxAmt;

            $html .= '<tr>'
                . '<td>' . $slno . '</td>'
                . '<td>' . $this->e($it['name'] ?? '') . '</td>'
                . '<td>' . $this->e($it['hsn'] ?? '') . '</td>'
                . '<td class="r">' . $this->e((string)($it['qty'] ?? '')) . '</td>'
                . '<td>' . $this->e($it['unit'] ?? 'pcs') . '</td>'
                . '<td class="r">' . $this->money((float)($it['price'] ?? 0)) . '</td>'
                . '<td class="r">' . ($taxPct > 0 ? $taxPct . '%' : '—') . '</td>'
                . '<td class="r">' . $this->money($taxAmt) . '</td>'
                . '<td class="r">' . $this->money($afterTax) . '</td>'
                . '</tr>';
            $slno++;
        }

        // Pad remaining rows
        while ($slno <= $maxRows) {
            $html .= '<tr>'
                . '<td>' . $slno . '</td>'
                . '<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>'
                . '<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>'
                . '</tr>';
            $slno++;
        }

        $html .= '</tbody></table>';

        // Sub-footer: tax breakup (left) + totals (right) — last page only
        $taxHtml = $this->taxBreakupHtml($items);
        $totalsRows = $this->buildTotalsRows();

        $html .= '<table class="sub-footer"><tr>';
        $html .= '<td style="width:55%">';
        if ($taxHtml !== '') {
            $html .= '<div class="section-title">Tax Breakup</div>' . $taxHtml;
        }
        $html .= '</td>';
        $html .= '<td style="width:45%;vertical-align:top">';
        $html .= $this->totalsHtml($totalsRows);
        $html .= '</td></tr></table>';

        return $html;
    }

    // ─── Footer (last page only — words + bank + terms + sig) ────────
    protected function footerHtml(): string
    {
        $s     = $this->data;
        $total = (float)($s['total'] ?? 0);

        $owner = $this->e($this->company['owner_name'] ?? '');
        $comp  = $this->e($this->company['company_name'] ?? '');

        // Two-column: left = words + bank + terms, right = signature
        $html  = '<table style="width:100%"><tr>';
        $html .= '<td style="width:55%;vertical-align:top">';
        $html .= $this->amountInWordsHtml($total);
        $html .= $this->bankDetailsHtml();
        $html .= $this->termsHtml();
        $html .= '</td>';
        $html .= '<td style="width:45%;vertical-align:bottom;text-align:right">';
        if ($owner !== '' || $comp !== '') {
            $html .= '<div class="section-title" style="margin-bottom:4px">Authorised Signatory</div>';
            $html .= '<div class="sig-line"></div>';
            $html .= '<div class="sig-name">' . $owner . '</div>';
            $html .= '<div class="sig-company">' . $comp . '</div>';
        }
        $html .= '</td>';
        $html .= '</tr></table>';

        return $html;
    }

    // ─── Totals rows builder ─────────────────────────────────────────
    private function buildTotalsRows(): array
    {
        $s        = $this->data;
        $subtotal = (float)($s['subtotal'] ?? 0);
        $discount = (float)($s['discount'] ?? 0);
        $roundoff = (float)($s['roundoff'] ?? 0);
        $tax      = (float)($s['tax'] ?? 0);
        $total    = (float)($s['total'] ?? 0);
        $paid     = (float)($s['paid'] ?? 0);
        $balance  = (float)($s['balance'] ?? 0);

        $rows = ['Subtotal' => ['amount' => $subtotal]];
        if ($discount > 0) {
            $rows['Discount'] = ['amount' => -$discount, 'sign' => '-'];
        }
        $rows['Tax'] = ['amount' => $tax, 'sign' => '+'];
        if ($roundoff != 0) {
            $rows['Round Off'] = ['amount' => $roundoff, 'sign' => $roundoff >= 0 ? '+' : ''];
        }
        $rows['Total']   = ['amount' => $total, 'bold' => true];
        $rows['Paid']    = ['amount' => $paid];
        $rows['Balance'] = ['amount' => $balance, 'orange' => true];
        return $rows;
    }

    // ─── Terms & Conditions ───────────────────────────────────────────
    private function termsHtml(): string
    {
        $terms = [
            'Goods once sold will not be taken back or exchanged.',
            'Payment by cheque/draft subject to realisation.',
            'Interest at 18% p.a. will be charged on overdue invoices.',
            'Subject to jurisdiction of local courts.',
        ];

        $html = '<div class="terms"><div class="section-title">Terms & Conditions</div>';
        $i = 1;
        foreach ($terms as $t) {
            $html .= $i . ') ' . $this->e($t) . '<br>';
            $i++;
        }
        $html .= '</div>';
        return $html;
    }
}
