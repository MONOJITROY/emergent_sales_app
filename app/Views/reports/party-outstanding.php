<h1 class="h3 fw-bold mb-1">Party Outstanding</h1>
<p class="text-muted small mb-3">Outstanding balances across customers and suppliers.</p>

<div class="row g-3 mb-3" id="poSummary"></div>

<div class="card sf-card">
  <div class="card-header d-flex align-items-center gap-2 flex-wrap">
    <select class="form-select form-select-sm w-auto" id="poType">
      <option value="all">All Parties</option>
      <option value="customer">Customers</option>
      <option value="supplier">Suppliers</option>
    </select>
  </div>
  <div class="table-responsive">
    <table class="table table-sm sf-table mb-0">
      <thead><tr>
        <th>Party Name</th>
        <th>Type</th>
        <th>Phone</th>
        <th class="text-end">Unpaid Invoices</th>
        <th class="text-end">Outstanding</th>
      </tr></thead>
      <tbody id="poRows"><tr><td colspan="5" class="text-center text-muted py-3">Loading…</td></tr></tbody>
      <tfoot id="poFoot"></tfoot>
    </table>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.partyOutstanding();</script>'; ?>
