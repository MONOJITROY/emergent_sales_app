<h1 class="h3 fw-bold mb-1">Daybook</h1>
<p class="text-muted small mb-3">Daily summary of all transactions — sales, purchases, receipts and payments.</p>

<div class="card sf-card mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label small fw-semibold">Date</label><input type="date" class="form-control form-control-sm" id="dbDate" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-md-1"><button class="btn btn-sm sf-btn-primary w-100" id="dbLoad"><i class="bi bi-search"></i></button></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3" id="dbSummary"></div>

<div class="card sf-card">
  <div class="table-responsive">
    <table class="table table-sm sf-table mb-0">
      <thead><tr>
        <th>Date</th><th>Type</th><th>Reference</th><th>Party</th>
        <th class="text-end">Debit</th><th class="text-end">Credit</th>
      </tr></thead>
      <tbody id="dbRows"><tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr></tbody>
      <tfoot id="dbFoot"></tfoot>
    </table>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.daybook();</script>'; ?>
