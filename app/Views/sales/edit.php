<?php use App\Core\View; ?>
<a href="<?= View::e($_baseUrl) ?>/sales/<?= (int)$sale['id'] ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back to invoice</a>
<h1 class="h3 fw-bold mb-1 mt-2">Edit Invoice <span class="text-num"><?= View::e($sale['invoice_no']) ?></span></h1>
<p class="text-muted small mb-3">Updating an invoice will restore prior stock then apply the new lines.</p>

<div class="card sf-card mb-3"><div class="card-body">
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small text-uppercase fw-semibold">Customer</label>
      <select id="customerSelect" class="form-select form-select-sm"><option value="">Walk-in customer</option></select>
    </div>
    <div class="col-md-6">
      <label class="form-label small text-uppercase fw-semibold">Customer name</label>
      <input id="customerName" class="form-control form-control-sm" value="<?= View::e($sale['customer_name']) ?>">
    </div>
  </div>
</div></div>

<div class="card sf-card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
    <span class="small text-uppercase fw-semibold text-secondary">Line items</span>
    <button class="btn btn-sm btn-outline-secondary" id="addLine"><i class="bi bi-plus-lg me-1"></i>Add item</button>
  </div>
  <div class="table-responsive"><table class="table table-sm sf-table mb-0">
    <thead><tr>
      <th style="width:30%">Product</th>
      <th class="text-end" style="max-width:80px !important;width:80px !important;">Qty</th>
      <th class="text-end" style="max-width:100px !important;width:100px !important;">Price</th>
      <th class="text-end" style="max-width:70px !important;width:70px !important;">Tax %</th>
      <th class="text-end">Total <small class="d-block text-muted fw-normal" style="font-size:.6rem">(Before Tax)</small></th>
      <th class="text-end">Tax Amt</th>
      <th class="text-end">Total <small class="d-block text-muted fw-normal" style="font-size:.6rem">(After Tax)</small></th>
      <th></th>
    </tr></thead>
    <tbody id="lines"></tbody>
  </table></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card sf-card h-100"><div class="card-body">
    <label class="form-label small text-uppercase fw-semibold">Notes</label>
    <textarea id="notes" class="form-control form-control-sm" rows="4"><?= View::e($sale['notes'] ?? '') ?></textarea>
  </div></div></div>
  <div class="col-md-6"><div class="card sf-card h-100"><div class="card-body">
    <div class="d-flex justify-content-between small"><span>Subtotal</span><span class="text-num" id="subtotal">0.00</span></div>
    <div class="d-flex justify-content-between align-items-center mt-2"><span>Discount</span><input id="discount" type="number" step="any" class="form-control form-control-sm text-end" style="width:120px" value="<?= (float)$sale['discount'] ?>"></div>
    <div class="d-flex justify-content-between align-items-center mt-2"><span>Tax</span><input id="tax" type="text" class="form-control form-control-sm text-end bg-light" style="width:120px" readonly value="<?= (float)$sale['tax'] ?>"></div>
    <div id="taxBreakupWrap" class="mt-2" style="display:none">
      <a class="text-primary small text-decoration-none" data-bs-toggle="collapse" href="#taxBreakup" role="button"><i class="bi bi-chevron-down me-1"></i><small>Tax Breakup</small></a>
      <div class="collapse mt-1" id="taxBreakup"><div id="taxBreakupBody" class="border rounded p-2"></div></div>
    </div>
    <hr class="my-2">
    <div class="d-flex justify-content-between align-items-center mt-2"><span>Round Off</span><span class="text-num" id="roundoff"><?= (float)($sale['roundoff'] ?? 0) ?></span></div>
    <div class="d-flex justify-content-between"><strong>Grand Total</strong><strong class="text-num h5 mb-0" id="total">0.00</strong></div>
    <div class="d-flex justify-content-between align-items-center mt-2"><span>Paid</span><input id="paid" type="number" step="any" class="form-control form-control-sm text-end" style="width:120px" value="<?= (float)$sale['paid'] ?>"></div>
    <div class="d-flex justify-content-between mt-2"><strong>Balance</strong><strong class="text-orange text-num" id="balance">0.00</strong></div>
    <button id="updateSale" class="btn sf-btn-primary w-100 mt-3" data-id="<?= (int)$sale['id'] ?>">Update Invoice</button>
  </div></div></div>
</div>
<?php $GLOBALS['pageScript'] = '<script>SF.saleEdit(' . json_encode($sale) . ');</script>'; ?>
