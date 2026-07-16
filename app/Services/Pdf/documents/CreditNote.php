<?php
declare(strict_types=1);
namespace App\Services\Pdf\Documents;

use App\Services\Pdf\PdfDocument;

class CreditNote extends PdfDocument
{
    public function __construct(array $data, array $company, array $config = [])
    {
        $defaults = [
            'header_on_all_pages' => false,
            'footer_on_all_pages' => true,
        ];
        parent::__construct($data, $company, array_merge($defaults, $config));
    }

    protected function documentTitle(): string
    {
        return 'Credit-Note';
    }

    // ─── Header (first page only) ────────────────────────────────────
    protected function headerHtml(): string
    {
        $cn = $this->data;

        $html = '<table class="doc-title-bar"><tr>'
            . '<td class="doc-title">CREDIT NOTE</td>'
            . '<td class="doc-meta">'
            . '<div><b>CN #:</b> ' . $this->e($cn['cn_no'] ?? '') . '</div>'
            . '<div><b>Date:</b> ' . $this->formatDate($cn['cn_date'] ?? '') . '</div>';

        if (!empty($cn['sale_invoice_no'])) {
            $html .= '<div><b>Ref Invoice:</b> ' . $this->e($cn['sale_invoice_no']) . '</div>';
        }
        if (!empty($cn['sale_date'])) {
            $html .= '<div><b>Invoice Date:</b> ' . $this->formatDate($cn['sale_date']) . '</div>';
        }

        $html .= '</td></tr></table>';

        $html .= '<table class="party-section"><tr>';
        $html .= '<td>' . $this->partyHtml('Bill To', $cn['customer_name'] ?? '', [
            'Address' => $cn['customer_address'] ?? '',
            'Phone'   => $cn['customer_phone'] ?? '',
            'Email'   => $cn['customer_email'] ?? '',
        ]) . '</td>';
        $html .= '<td>' . $this->partyHtml('Original Invoice', $cn['sale_invoice_no'] ?? '—', [
            'Date'   => isset($cn['sale_date']) ? $this->formatDate($cn['sale_date']) : '',
            'Reason' => $cn['reason'] ?? '',
        ]) . '</td>';
        $html .= '</tr></table>';

        return $html;
    }

    // ─── Body ─────────────────────────────────────────────────────────
    protected function bodyHtml(): string
    {
        $cn    = $this->data;
        $items = $cn['items'] ?? [];

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

        $reason = $cn['reason'] ?? '';
        if ($reason !== '') {
            $html .= '<div style="margin-top:10px;font-size:9.5px"><b>Reason:</b> ' . $this->e($reason) . '</div>';
        }

        return $html;
    }

    // ─── Footer (end of doc — words + ref note + terms + sig) ────────
    protected function footerHtml(): string
    {
        $cn    = $this->data;
        $total = (float)($cn['total'] ?? 0);

        $html  = $this->amountInWordsHtml($total);

        if (!empty($cn['sale_invoice_no'])) {
            $html .= '<div class="amt-words"><b>Note:</b> This is a credit note issued against Invoice #'
                . $this->e($cn['sale_invoice_no'])
                . (isset($cn['sale_date']) ? ' dated ' . $this->formatDate($cn['sale_date']) : '')
                . '</div>';
        }

        $html .= $this->termsHtml();
        $html .= $this->signatureBlockHtml();

        return $html;
    }

    // ─── Terms ────────────────────────────────────────────────────────
    private function termsHtml(): string
    {
        $terms = [
            'This credit note is issued under GST for the reason stated above.',
            'Input tax credit adjustment, if any, to be done as per GST rules.',
            'Original invoice must be surrendered for credit note processing.',
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
