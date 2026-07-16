<?php
declare(strict_types=1);
namespace App\Services\Pdf\Documents;

use App\Services\Pdf\PdfDocument;

class DebitNote extends PdfDocument
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
        return 'Debit-Note';
    }

    // ─── Header (first page only) ────────────────────────────────────
    protected function headerHtml(): string
    {
        $dn = $this->data;

        $html = '<table class="doc-title-bar"><tr>'
            . '<td class="doc-title">DEBIT NOTE</td>'
            . '<td class="doc-meta">'
            . '<div><b>DN #:</b> ' . $this->e($dn['dn_no'] ?? '') . '</div>'
            . '<div><b>Date:</b> ' . $this->formatDate($dn['dn_date'] ?? '') . '</div>';

        if (!empty($dn['purchase_ref_no'])) {
            $html .= '<div><b>Ref PO #:</b> ' . $this->e($dn['purchase_ref_no']) . '</div>';
        }
        if (!empty($dn['purchase_date'])) {
            $html .= '<div><b>PO Date:</b> ' . $this->formatDate($dn['purchase_date']) . '</div>';
        }

        $html .= '</td></tr></table>';

        $html .= '<table class="party-section"><tr>';
        $html .= '<td>' . $this->partyHtml('Supplier', $dn['supplier_name'] ?? '', [
            'Address' => $dn['supplier_address'] ?? '',
            'Phone'   => $dn['supplier_phone'] ?? '',
            'Email'   => $dn['supplier_email'] ?? '',
        ]) . '</td>';
        $html .= '<td>' . $this->partyHtml('Original Purchase', $dn['purchase_ref_no'] ?? '—', [
            'Date'   => isset($dn['purchase_date']) ? $this->formatDate($dn['purchase_date']) : '',
            'Reason' => $dn['reason'] ?? '',
        ]) . '</td>';
        $html .= '</tr></table>';

        return $html;
    }

    // ─── Body ─────────────────────────────────────────────────────────
    protected function bodyHtml(): string
    {
        $dn    = $this->data;
        $items = $dn['items'] ?? [];

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

        $reason = $dn['reason'] ?? '';
        if ($reason !== '') {
            $html .= '<div style="margin-top:10px;font-size:9.5px"><b>Reason:</b> ' . $this->e($reason) . '</div>';
        }

        return $html;
    }

    // ─── Footer (end of doc) ─────────────────────────────────────────
    protected function footerHtml(): string
    {
        $dn    = $this->data;
        $total = (float)($dn['total'] ?? 0);

        $html  = $this->amountInWordsHtml($total);

        if (!empty($dn['purchase_ref_no'])) {
            $html .= '<div class="amt-words"><b>Note:</b> This is a debit note issued against Purchase Order #'
                . $this->e($dn['purchase_ref_no'])
                . (isset($dn['purchase_date']) ? ' dated ' . $this->formatDate($dn['purchase_date']) : '')
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
            'This debit note is issued under GST for the reason stated above.',
            'Input tax credit adjustment, if any, to be done as per GST rules.',
            'Original purchase invoice must be surrendered for debit note processing.',
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
