<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h5 class="mb-0 fw-bold">Reconcile Receipts</h5>
</div>
<div class="card sf-card"><div class="card-body">
  <p class="small text-muted mb-3">Receipts received via cheque or transfer that are pending bank reconciliation. Click "Settle" once the transaction is confirmed by the bank.</p>

  <div class="sf-filter-bar mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="form-label small mb-0">Customer</label>
        <select id="fCustomer" class="form-select form-select-sm"><option value="">All Customers</option></select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-0">Receipt No</label>
        <select id="fReceiptNo" class="form-select form-select-sm"><option value="">All Receipts</option></select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-0">Invoice No</label>
        <input type="text" id="fInvNo" class="form-control form-control-sm" placeholder="Search...">
      </div>
      <div class="col-auto">
        <label class="form-label small mb-0">Reference No</label>
        <input type="text" id="fRefNo" class="form-control form-control-sm" placeholder="Search...">
      </div>
      <div class="col-auto">
        <button id="fClear" class="btn btn-sm btn-outline-secondary mt-4" title="Clear filters"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
  </div>

  <div class="table-responsive"><table class="table table-sm sf-table">
    <thead><tr>
      <th>Receipt No</th><th>Customer</th><th>Date</th><th class="text-end">Amount</th>
      <th>Mode</th><th>Reference</th><th class="text-end">Action</th>
    </tr></thead>
    <tbody id="rows"><tr><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr></tbody>
  </table></div>
</div></div>
<?php $GLOBALS['pageScript'] = '<script>SF.reconciliationReceipts();</script>'; ?>
