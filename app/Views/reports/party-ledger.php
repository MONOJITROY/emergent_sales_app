<h1 class="h3 fw-bold mb-1">Party Ledger</h1>
<p class="text-muted small mb-3">Detailed transaction history for a selected party.</p>

<div class="card sf-card mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Party Type</label>
        <select class="form-select form-select-sm" id="plPartyType">
          <option value="customer">Customer</option>
          <option value="supplier">Supplier</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Party</label>
        <select class="form-select form-select-sm" id="plPartyId"><option value="">Select party…</option></select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold">From</label>
        <input type="date" class="form-control form-control-sm" id="plFrom">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold">To</label>
        <input type="date" class="form-control form-control-sm" id="plTo">
      </div>
      <div class="col-md-1">
        <button class="btn btn-sm sf-btn-primary w-100" id="plLoad"><i class="bi bi-search"></i></button>
      </div>
    </div>
  </div>
</div>

<div id="plInfo" class="mb-3"></div>

<div class="card sf-card">
  <div class="table-responsive">
    <table class="table table-sm sf-table mb-0">
      <thead><tr>
        <th>Date</th>
        <th>Type</th>
        <th>Reference</th>
        <th class="text-end">Debit</th>
        <th class="text-end">Credit</th>
        <th class="text-end">Balance</th>
      </tr></thead>
      <tbody id="plRows"><tr><td colspan="6" class="text-center text-muted py-3">Select a party to view ledger.</td></tr></tbody>
      <tfoot id="plFoot"></tfoot>
    </table>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.partyLedger();</script>'; ?>
