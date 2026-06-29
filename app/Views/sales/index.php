<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
  <div><h1 class="h3 fw-bold mb-1">Sales / Invoices</h1><p class="text-muted small mb-0">All issued invoices with payment status.</p></div>
  <div class="d-flex gap-2">
    <div class="input-group input-group-sm" style="width:280px">
      <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
      <input class="form-control" id="searchInput" placeholder="Search invoice or customer…">
    </div>
    <a class="btn btn-sm sf-btn-primary" href="<?= htmlspecialchars($_baseUrl) ?>/sales/new"><i class="bi bi-plus-lg me-1"></i>New Sale</a>
    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($_baseUrl) ?>/api/exports/sales.xlsx" target="_blank"><i class="bi bi-file-earmark-excel me-1"></i>Export XLSX</a>
  </div>
</div>
<div class="card sf-card"><div class="table-responsive"><table class="table table-sm align-middle sf-table mb-0">
  <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Items</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
  <tbody id="rows"><tr><td colspan="8" class="text-center text-muted py-3">Loading…</td></tr></tbody>
</table></div></div>
<!-- <script>SF.sales();</script> -->
<?php $GLOBALS['pageScript'] = '<script>SF.sales();</script>'; ?>
