<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-n8">
  <div>
    <h1 class="h3 fw-bold mb-0 mt-0">New Sale</h1>
    <p class="text-muted small mb-1 mt-n4">Create an invoice and decrement stock.</p>
  </div>
  <div>
    <a href="<?= htmlspecialchars($_baseUrl) ?>/sales" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>

<div class="card sf-card mb-3"><div class="card-body">
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small text-uppercase fw-semibold">Customer</label>
      <select id="customerSelect" class="form-select form-select-sm"><option value="">Walk-in customer</option></select>
    </div>
    <div class="col-md-6">
      <label class="form-label small text-uppercase fw-semibold">Customer name</label>
      <input id="customerName" class="form-control form-control-sm" value="Walk-in customer">
    </div>
  </div>
</div></div>

<div class="card sf-card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
    <span class="small text-uppercase fw-semibold text-secondary">Line items</span>
    <button class="btn btn-sm btn-outline-secondary" id="addLine"><i class="bi bi-plus-lg me-1"></i>Add item</button>
  </div>
  <div class="table-responsive">
    <table class="table table-sm sf-table mb-0">
      <thead>
        <tr>
          <th style="width:40%">Product</th>
          <th class="text-end" style="max-width: 100px !important;width: 100px !important;">Qty</th>
          <th class="text-end" style="max-width: 100px !important;width: 100px !important;">Price</th>
          <th class="text-end" style="max-width: 100px !important;width: 100px !important;">Tax %</th>
          <th class="text-end">Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="lines">
        <tr>
          <td colspan="5" class="text-center text-muted py-3 small">No items yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card sf-card h-100"><div class="card-body">
    <label class="form-label small text-uppercase fw-semibold">Notes</label>
    <textarea id="notes" class="form-control form-control-sm" rows="4"></textarea>
  </div></div></div>
  <div class="col-md-6"><div class="card sf-card h-100"><div class="card-body">
    <div class="d-flex justify-content-between small">
      <span>Subtotal</span>
      <span class="text-num" id="subtotal">0.00</span>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <span>Discount</span>
      <input id="discount" type="number" step="any" class="form-control form-control-sm text-end sale-disc numberinput" style="width:120px" value="0">
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <span>Tax</span>
      <input id="tax" type="text" class="form-control form-control-sm text-end bg-light sale-tax numberinput" style="width:120px" readonly value="0.00">
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <span>Round Off</span>
      <span class="text-num" id="roundoff">0.00</span>
    </div>
    <hr class="my-2">
    <div class="d-flex justify-content-between">
      <strong>Grand Total</strong>
      <strong class="text-num h5 mb-0" id="total">0.00</strong>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <span>Paid now</span>
      <input id="paid" type="number" step="any" class="form-control form-control-sm text-end numberinput" style="width:120px" value="0">
    </div>
    <div class="d-flex justify-content-between mt-2">
      <strong>Balance</strong>
      <strong class="text-orange text-num" id="balance">0.00</strong>
    </div>
    <button id="saveSale" class="btn sf-btn-primary w-100 mt-3" data-testid="save-sale-btn">Save Invoice</button>
  </div></div></div>
</div>
<?php $GLOBALS['pageScript'] = '<script>SF.saleNew();</script>'; ?>
