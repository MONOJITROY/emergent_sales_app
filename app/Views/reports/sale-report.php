<h1 class="h3 fw-bold mb-1">Sale Report</h1>
<p class="text-muted small mb-3">Detailed sales with date range, customer and status filters.</p>

<div class="card sf-card mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-2"><label class="form-label small fw-semibold">From</label><input type="date" class="form-control form-control-sm" id="srFrom"></div>
      <div class="col-md-2"><label class="form-label small fw-semibold">To</label><input type="date" class="form-control form-control-sm" id="srTo"></div>
      <div class="col-md-3"><label class="form-label small fw-semibold">Customer</label><select class="form-select form-select-sm" id="srCustomer"><option value="">All Customers</option></select></div>
      <div class="col-md-2"><label class="form-label small fw-semibold">Status</label><select class="form-select form-select-sm" id="srStatus"><option value="all">All</option><option value="paid">Paid</option><option value="partial">Partial</option><option value="unpaid">Unpaid</option></select></div>
      <div class="col-md-1"><button class="btn btn-sm sf-btn-primary w-100" id="srLoad"><i class="bi bi-search"></i></button></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3" id="srSummary"></div>

<div class="card sf-card">
  <div class="table-responsive">
    <table class="table table-sm sf-table mb-0">
      <thead><tr>
        <th>Invoice No</th><th>Date</th><th>Customer</th>
        <th class="text-end">Subtotal</th><th class="text-end">Discount</th><th class="text-end">Tax</th>
        <th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th>
      </tr></thead>
      <tbody id="srRows"><tr><td colspan="10" class="text-center text-muted py-3">Click search to load data.</td></tr></tbody>
      <tfoot id="srFoot"></tfoot>
    </table>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.saleReport();</script>'; ?>
