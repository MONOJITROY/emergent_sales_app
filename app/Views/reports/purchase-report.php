<h1 class="h3 fw-bold mb-1">Purchase Report</h1>
<p class="text-muted small mb-3">Detailed purchases with date range, supplier and status filters.</p>

<div class="card sf-card mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-2"><label class="form-label small fw-semibold">From</label><input type="date" class="form-control form-control-sm" id="purFrom"></div>
      <div class="col-md-2"><label class="form-label small fw-semibold">To</label><input type="date" class="form-control form-control-sm" id="purTo"></div>
      <div class="col-md-3"><label class="form-label small fw-semibold">Supplier</label><select class="form-select form-select-sm" id="purSupplier"><option value="">All Suppliers</option></select></div>
      <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select class="form-select form-select-sm" id="purStatus"><option value="all">All</option><option value="paid">Paid</option><option value="partial">Partial</option><option value="unpaid">Unpaid</option></select></div>
      <div class="col-md-1"><button class="btn btn-sm sf-btn-primary w-100" id="purLoad"><i class="bi bi-search"></i></button></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3" id="purSummary"></div>

<div class="card sf-card">
  <div class="table-responsive">
    <table class="table table-sm sf-table mb-0">
      <thead><tr>
        <th>Ref No</th><th>Date</th><th>Supplier</th>
        <th class="text-end">Subtotal</th><th class="text-end">Tax</th>
        <th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th>
      </tr></thead>
      <tbody id="purRows"><tr><td colspan="9" class="text-center text-muted py-3">Click search to load data.</td></tr></tbody>
      <tfoot id="purFoot"></tfoot>
    </table>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.purchaseReport();</script>'; ?>
