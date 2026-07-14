<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h5 class="mb-0 fw-bold">Payments</h5>
  <a class="btn btn-sm sf-btn-primary" href="<?= View::e($_baseUrl) ?>/payments/new"><i class="bi bi-plus-lg me-1"></i>New Payment</a>
</div>
<div class="card sf-card"><div class="card-body">
  <div class="d-flex align-items-center gap-2 mb-3">
    <input id="searchInput" type="text" class="form-control form-control-sm" style="max-width:260px" placeholder="Search payments..." data-testid="search-payments">
  </div>
  <div class="table-responsive"><table class="table table-sm sf-table">
    <thead><tr>
      <th>Payment No</th><th>Supplier</th><th>Date</th><th class="text-end">Amount</th>
      <th>Mode</th><th>Reference</th><th>Status</th><th class="text-end">PDF</th>
    </tr></thead>
    <tbody id="rows"><tr><td colspan="8" class="text-center text-muted py-3">Loading...</td></tr></tbody>
  </table></div>
</div></div>
<?php $GLOBALS['pageScript'] = '<script>SF.payments();</script>'; ?>
