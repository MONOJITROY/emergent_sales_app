<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
  <div><h1 class="h3 fw-bold mb-1">Purchases</h1><p class="text-muted small mb-0">Stock receipts from suppliers.</p></div>
  <div class="d-flex gap-2">
    <div class="input-group input-group-sm" style="width:260px">
      <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
      <input class="form-control" id="searchInput" placeholder="Search ref or supplier…">
    </div>
    <button class="btn btn-sm sf-btn-primary" id="newBtn"><i class="bi bi-plus-lg me-1"></i>New Purchase</button>
  </div>
</div>
<div class="card sf-card"><div class="table-responsive"><table class="table table-sm align-middle sf-table mb-0">
  <thead><tr><th>Ref</th><th>Supplier</th><th>Date</th><th>Items</th><th class="text-end">Subtotal</th><th class="text-end">Tax</th><th class="text-end">Total</th><th class="text-end">Actions</th></tr></thead>
  <tbody id="rows"><tr><td colspan="8" class="text-center text-muted py-3">Loading…</td></tr></tbody>
</table></div></div>

<div class="modal fade" id="purchaseModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">New purchase</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label small text-uppercase fw-semibold">Supplier</label>
        <select id="supplierSel" class="form-select form-select-sm"><option value="">— pick a supplier —</option></select>
      </div>
      <div class="col-md-4 d-none">
        <label class="form-label small text-uppercase fw-semibold">Supplier name *</label>
        <input id="supplierName" class="form-control form-control-sm" readonly>
      </div>
      <div class="col-md-2 datefield">
        <label class="form-label small text-uppercase fw-semibold">Purchase Date *</label>
        <input id="purchasedate" type="date" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-uppercase fw-semibold">Inv No *</label>
        <input id="supplierinvno" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 datefield">
        <label class="form-label small text-uppercase fw-semibold">Inv Date *</label>
        <input type="date" id="supplierinvdate" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 c-width-120">
        <label class="form-label small text-uppercase fw-semibold" style="letter-spacing: -0.05rem;">Inv Amt (Before Tax) *</label>
        <input id="supplierinvamt" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 c-width-100">
        <label class="form-label small text-uppercase fw-semibold">Tax Amt *</label>
        <input id="supplierinvtaxamt" class="form-control form-control-sm">
      </div>
      <div class="col-md-2 c-width-120">
        <label class="form-label small text-uppercase fw-semibold">Total Inv Amt *</label>
        <input id="supplierinvtotamt" class="form-control form-control-sm" readonly>
      </div>
    </div>
    <div class="border rounded">
      <div class="d-flex justify-content-between align-items-center bg-light px-2 py-2 border-bottom">
        <span class="small text-uppercase fw-semibold text-secondary">Items</span>
        <button class="btn btn-sm btn-outline-secondary" id="addLine"><i class="bi bi-plus-lg me-1"></i>Add</button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm sf-table mb-0">
          <thead>
            <tr>
              <th style="width:40%">Product</th>
              <th class="text-ends">Qty</th>
              <th class="text-ends">Cost</th>
              <th class="text-ends">Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="lines">
            <tr>
              <td colspan="5" class="text-center text-muted small py-2">No items.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="row g-3 mt-3">
      <div class="col-md-6"><label class="form-label small text-uppercase fw-semibold">Notes</label><textarea id="notes" rows="3" class="form-control form-control-sm"></textarea></div>
      <div class="col-md-6">
        <div class="d-flex justify-content-between"><span>Subtotal</span><span class="text-num" id="pSub">0.00</span></div>
        <div class="d-flex justify-content-between align-items-center mt-2"><span>Tax</span><input id="pTax" type="text" step="any" class="form-control form-control-sm text-end" style="width:120px" value="0"></div>
        <div class="d-flex justify-content-between fw-bold text-orange border-top pt-1 mt-1"><span>Total</span><span class="text-num" id="pTotal">0.00</span></div>
      </div>
    </div>
  </div>
  <div class="modal-footer py-2"><button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm sf-btn-primary" id="savePurchase">Save</button></div>
</div></div></div>
<?php $GLOBALS['pageScript'] = '<script>SF.purchases();</script>'; ?>
