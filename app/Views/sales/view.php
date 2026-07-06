<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <a href="<?= View::e($_baseUrl) ?>/sales" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back</a>
  <div class="d-flex gap-2">
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a class="btn btn-sm btn-outline-secondary" href="<?= View::e($_baseUrl) ?>/api/exports/invoice/<?= (int)$sale['id'] ?>.pdf" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
    <button class="btn btn-sm btn-outline-secondary" id="emailInvoice" data-id="<?= (int)$sale['id'] ?>"><i class="bi bi-envelope me-1"></i>Email</button>
    <a class="btn btn-sm btn-outline-secondary" href="<?= View::e($_baseUrl) ?>/sales/<?= (int)$sale['id'] ?>/edit"><i class="bi bi-pencil me-1"></i>Edit</a>
    <button class="btn btn-sm btn-outline-danger" id="deleteSale" data-id="<?= (int)$sale['id'] ?>"><i class="bi bi-trash me-1"></i>Delete</button>
  </div>
</div>

<?php if (!empty($company['invoice_template'])): ?>
<div class="card sf-card no-print"><div class="card-body text-center py-5">
  <i class="bi bi-file-earmark-text" style="font-size:3rem;color:#adb5bd"></i>
  <p class="text-muted mt-2 mb-3">This invoice uses a custom template.</p>
  <button class="btn sf-btn-primary" data-bs-toggle="modal" data-bs-target="#invoiceTemplateModal">
    <i class="bi bi-eye me-1"></i>View Invoice
  </button>
</div></div>

<div class="modal fade" id="invoiceTemplateModal" tabindex="-1" style="--bs-modal-width:900px">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">Invoice #<?= View::e($sale['invoice_no']) ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <iframe src="<?= View::e($_baseUrl) ?>/api/invoice/<?= (int)$sale['id'] ?>/render" style="width:100%;height:700px;border:0;border-radius:0 0 var(--sf-radius,6px) var(--sf-radius,6px)"></iframe>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card sf-card print-area"><div class="card-body">
  <div class="d-flex justify-content-between flex-wrap gap-3 pb-1 border-bottom mb-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="sf-invlogo"><?php if (!empty($company['company_logo'])): ?><img src="<?= View::e($_baseUrl) ?>/assets/images/<?= View::e($company['company_logo']) ?>" alt="Company Logo"><?php endif; ?></span>
      </div>
    </div>
    <div class="text-end">
      <div class="text-uppercase small text-secondary fw-semibold" style="font-size:.7rem;letter-spacing:.06em">Invoice</div>
      <div class="text-num h5 mb-0"># <?= View::e($sale['invoice_no']) ?></div>
      <div class="small text-muted">Date: <?= date('d-m-Y', strtotime(View::e($sale['sale_date']))) ?></div>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="text-uppercase small text-secondary fw-semibold mb-1" style="font-size:.7rem;letter-spacing:.06em">Bill to</div>
      <div class="fw-semibold"><?= View::e($sale['customer_name']) ?></div>
      <div class="small text-muted">Address: <?= View::e($sale['customer']['address'] ?? '') ?></div>
      <div class="small text-muted">Phone: <?= View::e($sale['customer']['phone'] ?? '') ?></div>
      <div class="small text-muted">Email: <?= View::e($sale['customer']['email'] ?? '') ?></div>
    </div>
    <div class="col-md-6 text-md-end">
      <div class="text-uppercase small text-secondary fw-semibold mb-1" style="font-size:.7rem;letter-spacing:.06em">Status</div>
      <span class="badge sf-badge sf-status-<?= View::e($sale['status']) ?>"><?= strtoupper($sale['status']) ?></span>
    </div>
  </div>
  <div class="table-responsive"><table class="table table-sm sf-table">
    <thead><tr><th>SKU</th><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
    <tbody><?php foreach ($sale['items'] as $it): ?>
      <tr><td class="text-num small"><?= View::e($it['sku']) ?></td><td><?= View::e($it['name']) ?></td>
        <td class="text-end text-num"><?= View::e($it['qty']) ?></td>
        <td class="text-end text-num"><?= number_format((float)$it['price'],2) ?></td>
        <td class="text-end text-num"><?= number_format((float)$it['total'],2) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <div class="d-flex justify-content-end"><div style="min-width:260px">
    <div class="d-flex justify-content-between small"><span class="text-muted">Subtotal</span><span class="text-num"><?= number_format((float)$sale['subtotal'],2) ?></span></div>
    <div class="d-flex justify-content-between small"><span class="text-muted">Discount</span><span class="text-num">-<?= number_format((float)$sale['discount'],2) ?></span></div>
    <div class="d-flex justify-content-between small"><span class="text-muted">Tax</span><span class="text-num">+<?= number_format((float)$sale['tax'],2) ?></span></div>
    <div class="d-flex justify-content-between fw-bold border-top mt-1 pt-1"><span>Total</span><span class="text-num"><?= number_format((float)$sale['total'],2) ?></span></div>
    <div class="d-flex justify-content-between small text-muted"><span>Paid</span><span class="text-num"><?= number_format((float)$sale['paid'],2) ?></span></div>
    <div class="d-flex justify-content-between fw-bold text-orange"><span>Balance</span><span class="text-num"><?= number_format((float)$sale['balance'],2) ?></span></div>
  </div></div>
  <?php if (!empty($sale['notes'])): ?><div class="border-top pt-3 mt-3 small"><strong class="text-uppercase text-secondary" style="font-size:.7rem;letter-spacing:.06em">Notes</strong><div class="text-muted mt-1" style="white-space:pre-wrap"><?= View::e($sale['notes']) ?></div></div><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ((float)$sale['balance'] > 0): ?>
<div class="card sf-card mt-3 no-print"><div class="card-body">
  <div class="text-uppercase small fw-semibold text-secondary mb-2" style="font-size:.7rem;letter-spacing:.06em">Record payment</div>
  <div class="d-flex gap-2"><input id="payAmt" type="number" step="any" class="form-control form-control-sm" style="max-width:180px" placeholder="Amount">
    <button class="btn btn-sm sf-btn-primary" id="payBtn" data-id="<?= (int)$sale['id'] ?>">Record</button></div>
</div></div>
<?php endif; ?>
<?php $GLOBALS['pageScript'] = '<script>SF.saleView(' . (int)$sale['id'] . ');</script>'; ?>
