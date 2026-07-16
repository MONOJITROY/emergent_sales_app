<?php
/**
 * Quick test — renders SalesInvoice HTML + PDF without DB.
 * Run: php public/temp/test_pdf.php
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\Pdf\Documents\SalesInvoice;

$data = [
    'invoice_no'    => 'INV-2026-001',
    'sale_date'     => '2026-07-16',
    'status'        => 'paid',
    'customer_name' => 'Acme Industries Pvt Ltd',
    'customer'      => [
        'name'    => 'Acme Industries Pvt Ltd',
        'address' => '12/34 MG Road, Bangalore 560001',
        'phone'   => '+91 80 2345 6789',
        'email'   => 'purchase@acme.in',
    ],
    'subtotal'  => 10000,
    'discount'  => 500,
    'tax'       => 1710,
    'total'     => 11210,
    'paid'      => 11210,
    'balance'   => 0,
    'roundoff'  => 0,
    'items'     => [
        ['name' => 'Widget A',      'hsn' => '8471', 'qty' => 10, 'unit' => 'pcs',  'price' => 500,  'total' => 5000,  'tax_pct' => 18, 'typeofduty' => 'GST'],
        ['name' => 'Widget B',      'hsn' => '8472', 'qty' => 5,  'unit' => 'pcs',  'price' => 600,  'total' => 3000,  'tax_pct' => 18, 'typeofduty' => 'GST'],
        ['name' => 'Service Fees',  'hsn' => '9983', 'qty' => 1,  'unit' => 'lot',  'price' => 2000, 'total' => 2000,  'tax_pct' => 18, 'typeofduty' => 'GST'],
    ],
];

$company = [
    'owner_name'               => 'Ravi Kumar',
    'company_name'             => 'Emergent Sales Pvt Ltd',
    'company_address'          => '45 Industrial Area Phase 2, Bangalore',
    'company_phone'            => '+91 80 1234 5678',
    'company_email'            => 'info@emergent.co.in',
    'company_gst_no'           => '29AABCE1234F1ZP',
    'company_pan'              => 'AABCE1234F',
    'bank_name'                => 'HDFC Bank',
    'bank_account_holder_name' => 'Emergent Sales Pvt Ltd',
    'bank_account_no'          => '50100012345678',
    'bank_ifsc_code'           => 'HDFC0001234',
    'bank_branch_name'         => 'Indiranagar',
];

$doc = new SalesInvoice($data, $company);

// Write HTML
$html = $doc->render();
file_put_contents(__DIR__ . '/debug_invoice.html', $html);
echo "HTML written to public/temp/debug_invoice.html (" . strlen($html) . " bytes)\n";

// Write PDF
$dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdf = $dompdf->output();
file_put_contents(__DIR__ . '/debug_invoice.pdf', $pdf);
echo "PDF  written to public/temp/debug_invoice.pdf  (" . strlen($pdf) . " bytes)\n";
echo "Done.\n";
