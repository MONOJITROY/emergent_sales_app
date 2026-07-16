<?php
declare(strict_types=1);
namespace App\Services\Pdf\Documents;

use App\Services\Pdf\PdfDocument;

class DeliveryChallan extends PdfDocument
{
    public function __construct(array $data, array $company, array $config = [])
    {
        $defaults = [
            'header_on_all_pages' => false,
            'footer_on_all_pages' => false,
        ];
        parent::__construct($data, $company, array_merge($defaults, $config));
    }

    protected function documentTitle(): string
    {
        return 'Delivery-Challan';
    }

    // ─── Header (first page only) ────────────────────────────────────
    protected function headerHtml(): string
    {
        $dc = $this->data;

        $html = '<table class="doc-title-bar"><tr>'
            . '<td class="doc-title">DELIVERY CHALLAN</td>'
            . '<td class="doc-meta">'
            . '<div><b>DC #:</b> ' . $this->e($dc['dc_no'] ?? '') . '</div>'
            . '<div><b>Date:</b> ' . $this->formatDate($dc['dc_date'] ?? '') . '</div>';

        if (!empty($dc['vehicle_no'])) {
            $html .= '<div><b>Vehicle #:</b> ' . $this->e($dc['vehicle_no']) . '</div>';
        }
        if (!empty($dc['driver_name'])) {
            $html .= '<div><b>Driver:</b> ' . $this->e($dc['driver_name']) . '</div>';
        }
        if (!empty($dc['sale_invoice_no'])) {
            $html .= '<div><b>Ref Invoice:</b> ' . $this->e($dc['sale_invoice_no']) . '</div>';
        }

        $html .= '</td></tr></table>';

        $html .= '<table class="party-section"><tr>';
        $html .= '<td>' . $this->partyHtml('Deliver To', $dc['customer_name'] ?? '', [
            'Address' => $dc['customer_address'] ?? '',
            'Phone'   => $dc['customer_phone'] ?? '',
        ]) . '</td>';
        $html .= '<td>' . $this->partyHtml('Dispatch From', $this->company['company_name'] ?? '', [
            'Address' => $this->company['company_address'] ?? '',
            'Phone'   => $this->company['company_phone'] ?? '',
        ]) . '</td>';
        $html .= '</tr></table>';

        return $html;
    }

    // ─── Body (no prices) ─────────────────────────────────────────────
    protected function bodyHtml(): string
    {
        $dc    = $this->data;
        $items = $dc['items'] ?? [];

        $html = '<table class="items-table"><thead><tr>'
            . '<th>S.No</th>'
            . '<th>Item</th>'
            . '<th>HSN/SAC</th>'
            . '<th class="r">Qty</th>'
            . '<th>Unit</th>'
            . '</tr></thead><tbody>';

        $slno = 1;
        foreach ($items as $it) {
            $html .= '<tr>'
                . '<td>' . $slno . '</td>'
                . '<td>' . $this->e($it['name'] ?? '') . '</td>'
                . '<td>' . $this->e($it['hsn'] ?? '') . '</td>'
                . '<td class="r">' . $this->e((string)($it['qty'] ?? '')) . '</td>'
                . '<td>' . $this->e($it['unit'] ?? 'pcs') . '</td>'
                . '</tr>';
            $slno++;
        }

        $html .= '</tbody></table>';

        $notes = $dc['notes'] ?? '';
        if ($notes !== '') {
            $html .= '<div style="margin-top:10px;font-size:9.5px"><b>Notes:</b><br>'
                . nl2br($this->e($notes)) . '</div>';
        }

        return $html;
    }

    // ─── Footer (signatures + terms) ──────────────────────────────────
    protected function footerHtml(): string
    {
        $html  = '<div style="margin-top:30px">';
        $html .= '<table style="width:100%"><tr>';

        $html .= '<td style="width:50%;vertical-align:bottom;text-align:center">';
        $html .= '<div style="margin-top:50px;border-top:1px solid #999;padding-top:6px;font-size:9px">';
        $html .= '<b>Received By</b><br>';
        $html .= 'Name: _________________<br>';
        $html .= 'Signature: _________________<br>';
        $html .= 'Date: _________________';
        $html .= '</div></td>';

        $html .= '<td style="width:50%;vertical-align:bottom;text-align:center">';
        $html .= '<div style="margin-top:50px;border-top:1px solid #999;padding-top:6px;font-size:9px">';
        $html .= '<b>For ' . $this->e($this->company['company_name'] ?? '') . '</b><br><br><br>';
        $html .= 'Authorised Signatory';
        $html .= '</div></td>';

        $html .= '</tr></table>';
        $html .= '</div>';

        $html .= $this->termsHtml();

        return $html;
    }

    // ─── Terms ────────────────────────────────────────────────────────
    private function termsHtml(): string
    {
        $terms = [
            'Goods delivered as per above details. Please check and acknowledge.',
            'Any discrepancy to be reported within 24 hours of delivery.',
            'This challan is not a tax invoice. Original invoice will be issued separately.',
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
