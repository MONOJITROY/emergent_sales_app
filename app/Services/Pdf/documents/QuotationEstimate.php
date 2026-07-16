<?php
declare(strict_types=1);
namespace App\Services\Pdf\Documents;

use App\Services\Pdf\PdfDocument;

class QuotationEstimate extends PdfDocument
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
        return 'Quotation';
    }

    // ─── Header (first page only) ────────────────────────────────────
    protected function headerHtml(): string
    {
        $q = $this->data;

        $html = '<table class="doc-title-bar"><tr>'
            . '<td class="doc-title">QUOTATION</td>'
            . '<td class="doc-meta">'
            . '<div><b>Quote #:</b> ' . $this->e($q['quote_no'] ?? '') . '</div>'
            . '<div><b>Date:</b> ' . $this->formatDate($q['quote_date'] ?? '') . '</div>'
            . '<div><b>Valid Until:</b> ' . $this->formatDate($q['valid_until'] ?? '') . '</div>'
            . '<div><b>Status:</b> ' . strtoupper($this->e($q['status'] ?? 'draft')) . '</div>'
            . '</td></tr></table>';

        $html .= '<table class="party-section"><tr>';
        $html .= '<td>' . $this->partyHtml('To', $q['customer_name'] ?? '', [
            'Address' => $q['customer_address'] ?? '',
            'Phone'   => $q['customer_phone'] ?? '',
            'Email'   => $q['customer_email'] ?? '',
        ]) . '</td>';
        $html .= '<td>' . $this->partyHtml('From', $this->company['company_name'] ?? '', [
            'Address' => $this->company['company_address'] ?? '',
            'Phone'   => $this->company['company_phone'] ?? '',
            'Email'   => $this->company['company_email'] ?? '',
        ]) . '</td>';
        $html .= '</tr></table>';

        return $html;
    }

    // ─── Body ─────────────────────────────────────────────────────────
    protected function bodyHtml(): string
    {
        $q     = $this->data;
        $items = $q['items'] ?? [];

        $html = '<table class="items-table"><thead><tr>'
            . '<th>S.No</th>'
            . '<th>Item</th>'
            . '<th>HSN/SAC</th>'
            . '<th class="r">Qty</th>'
            . '<th>Unit</th>'
            . '<th class="r">Rate</th>'
            . '<th class="r">Tax %</th>'
            . '<th class="r">Tax Amt</th>'
            . '<th class="r">Amount</th>'
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

        $html .= '</tbody></table>';

        $taxHtml = $this->taxBreakupHtml($items);
        if ($taxHtml !== '') {
            $html .= '<div class="section-title">Tax Breakup</div>' . $taxHtml;
        }

        $notes = $q['notes'] ?? '';
        if ($notes !== '') {
            $html .= '<div style="margin-top:10px;font-size:9.5px"><b>Notes:</b><br>'
                . nl2br($this->e($notes)) . '</div>';
        }

        return $html;
    }

    // ─── Footer (end of doc — words + terms + sig) ───────────────────
    protected function footerHtml(): string
    {
        $q     = $this->data;
        $total = (float)($q['total'] ?? 0);

        $html  = $this->amountInWordsHtml($total);
        $html .= $this->termsHtml();
        $html .= $this->signatureBlockHtml();

        return $html;
    }

    // ─── Repeating footer (bank + summary) ───────────────────────────
    protected function repeatFooterHtml(): string
    {
        $q       = $this->data;
        $subtotal = (float)($q['subtotal'] ?? 0);
        $tax      = (float)($q['tax'] ?? 0);
        $total    = (float)($q['total'] ?? 0);

        $totalsRows = [
            'Subtotal' => ['amount' => $subtotal],
            'Tax'      => ['amount' => $tax, 'sign' => '+'],
            'Total'    => ['amount' => $total, 'bold' => true],
        ];

        $html  = '<table style="width:100%"><tr><td style="width:55%;vertical-align:top">';
        $html .= $this->bankDetailsHtml();
        $html .= '</td><td style="width:45%;vertical-align:top">';
        $html .= $this->totalsHtml($totalsRows);
        $html .= '</td></tr></table>';
        return $html;
    }

    // ─── Terms ────────────────────────────────────────────────────────
    private function termsHtml(): string
    {
        $validity = isset($this->data['valid_until'])
            ? $this->formatDate($this->data['valid_until'])
            : '15 days from date of quotation';

        $terms = [
            'This quotation is valid for ' . $validity . '.',
            'Prices are inclusive/exclusive of GST as applicable.',
            'Delivery: 7-15 working days from date of confirmation.',
            'Payment: 100% advance / 50% advance, balance before delivery.',
            'Material warranty: 12 months from date of delivery.',
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
