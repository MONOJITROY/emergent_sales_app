<?php
declare(strict_types=1);
namespace App\Services\Pdf\Documents;

use App\Services\Pdf\PdfDocument;

class PurchaseOrder extends PdfDocument
{
    public function __construct(array $data, array $company, array $config = [])
    {
        $defaults = [
            'header_on_all_pages' => true,
            'footer_on_all_pages' => false,
        ];
        parent::__construct($data, $company, array_merge($defaults, $config));
    }

    protected function documentTitle(): string
    {
        return 'Purchase-Order';
    }

    // ─── Header (first page only) ────────────────────────────────────
    protected function headerHtml(): string
    {
        $p = $this->data;

        $html = '<table class="doc-title-bar"><tr>'
            . '<td class="doc-title">PURCHASE ORDER</td>'
            . '<td class="doc-meta">'
            . '<div><b>PO #:</b> ' . $this->e($p['ref_no'] ?? '') . '</div>'
            . '<div><b>Date:</b> ' . $this->formatDate($p['purchase_date'] ?? '') . '</div>'
            . '<div><b>Ref:</b> ' . $this->e($p['supplier_inv_no'] ?? '—') . '</div>'
            . '</td></tr></table>';

        $html .= '<table class="party-section"><tr>';
        $html .= '<td>' . $this->partyHtml('Supplier', $p['supplier_name'] ?? '', [
            'Address' => $p['supplier_address'] ?? '',
            'Phone'   => $p['supplier_phone'] ?? '',
            'Email'   => $p['supplier_email'] ?? '',
        ]) . '</td>';
        $html .= '<td>' . $this->partyHtml('Ship To', $this->company['company_name'] ?? '', [
            'Address' => $this->company['company_address'] ?? '',
            'Phone'   => $this->company['company_phone'] ?? '',
        ]) . '</td>';
        $html .= '</tr></table>';

        return $html;
    }

    // ─── Body ─────────────────────────────────────────────────────────
    protected function bodyHtml(): string
    {
        $p     = $this->data;
        $items = $p['items'] ?? [];

        $html = '<table class="items-table"><thead><tr>'
            . '<th>S.No</th>'
            . '<th>Item</th>'
            . '<th>HSN/SAC</th>'
            . '<th class="r">Qty</th>'
            . '<th>Unit</th>'
            . '<th class="r">Rate</th>'
            . '<th class="r">Tax %</th>'
            . '<th class="r">Amount</th>'
            . '</tr></thead><tbody>';

        $slno = 1;
        foreach ($items as $it) {
            $total  = (float)($it['total'] ?? 0);
            $taxPct = (float)($it['tax_pct'] ?? 0);

            $html .= '<tr>'
                . '<td>' . $slno . '</td>'
                . '<td>' . $this->e($it['name'] ?? '') . '</td>'
                . '<td>' . $this->e($it['hsn'] ?? '') . '</td>'
                . '<td class="r">' . $this->e((string)($it['qty'] ?? '')) . '</td>'
                . '<td>' . $this->e($it['unit'] ?? 'pcs') . '</td>'
                . '<td class="r">' . $this->money((float)($it['price'] ?? 0)) . '</td>'
                . '<td class="r">' . ($taxPct > 0 ? $taxPct . '%' : '—') . '</td>'
                . '<td class="r">' . $this->money($total) . '</td>'
                . '</tr>';
            $slno++;
        }

        $html .= '</tbody></table>';

        $taxHtml = $this->taxBreakupHtml($items);
        if ($taxHtml !== '') {
            $html .= '<div class="section-title">Tax Breakup</div>' . $taxHtml;
        }

        return $html;
    }

    // ─── Footer (end of doc — words + terms + signature) ─────────────
    protected function footerHtml(): string
    {
        $p     = $this->data;
        $total = (float)($p['total'] ?? 0);

        $html  = $this->amountInWordsHtml($total);
        $html .= $this->termsHtml();
        $html .= $this->signatureBlockHtml();

        return $html;
    }

    // ─── Repeating footer (bank + summary, first page only config) ───
    protected function repeatFooterHtml(): string
    {
        $p       = $this->data;
        $subtotal = (float)($p['subtotal'] ?? 0);
        $tax      = (float)($p['tax'] ?? 0);
        $total    = (float)($p['total'] ?? 0);
        $paid     = (float)($p['paid'] ?? 0);
        $balance  = (float)($p['balance'] ?? 0);

        $totalsRows = [
            'Subtotal' => ['amount' => $subtotal],
            'Tax'      => ['amount' => $tax, 'sign' => '+'],
            'Total'    => ['amount' => $total, 'bold' => true],
            'Paid'     => ['amount' => $paid],
            'Balance'  => ['amount' => $balance, 'orange' => true],
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
        $terms = [
            'Delivery within 7-15 working days from date of confirmed PO.',
            '100% advance payment / 50% advance, balance on delivery.',
            'Material to be dispatched to our godown at the address mentioned above.',
            'Any damage or shortage to be reported within 48 hours of delivery.',
            'GST as applicable will be charged extra.',
            'This PO is valid for 30 days from date of issue.',
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
