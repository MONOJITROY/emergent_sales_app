<h1 class="h3 fw-bold mb-1">Invoice Ageing</h1>
<p class="text-muted small mb-3">Outstanding invoices bucketed by age — sales and purchases.</p>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-sa">Sales Ageing</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pa">Purchase Ageing</button></li>
</ul>

<div class="tab-content">
  <div id="tab-sa" class="tab-pane fade show active">
    <div class="row g-3 mb-3" id="saBuckets"></div>
    <div class="card sf-card"><div class="table-responsive"><table class="table table-sm sf-table mb-0">
      <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Days</th><th>Bucket</th><th class="text-end">Total</th><th class="text-end">Balance</th></tr></thead>
      <tbody id="saRows"><tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr></tbody>
    </table></div></div>
  </div>
  <div id="tab-pa" class="tab-pane fade">
    <div class="row g-3 mb-3" id="paBuckets"></div>
    <div class="card sf-card"><div class="table-responsive"><table class="table table-sm sf-table mb-0">
      <thead><tr><th>Invoice</th><th>Supplier</th><th>Date</th><th class="text-end">Days</th><th>Bucket</th><th class="text-end">Total</th><th class="text-end">Balance</th></tr></thead>
      <tbody id="paRows"><tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr></tbody>
    </table></div></div>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.invoiceAgeing();</script>'; ?>
