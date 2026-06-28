<h1 class="h3 fw-bold mb-1">Reports</h1>
<p class="text-muted small mb-3">Operational insights across sales and receivables.</p>
<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-c">By Customer</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-p">By Product</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-a">Invoice Aging</button></li>
</ul>
<div class="tab-content">
  <div id="tab-c" class="tab-pane fade show active"><div class="card sf-card"><div class="table-responsive"><table class="table table-sm sf-table mb-0">
    <thead><tr><th>Customer</th><th class="text-end">Invoices</th><th class="text-end">Total</th><th class="text-end">Balance</th></tr></thead>
    <tbody id="rByCust"><tr><td colspan="4" class="text-center text-muted py-3">Loading…</td></tr></tbody>
  </table></div></div></div>
  <div id="tab-p" class="tab-pane fade"><div class="card sf-card"><div class="table-responsive"><table class="table table-sm sf-table mb-0">
    <thead><tr><th>SKU</th><th>Product</th><th class="text-end">Qty Sold</th><th class="text-end">Revenue</th></tr></thead>
    <tbody id="rByProd"><tr><td colspan="4" class="text-center text-muted py-3">Loading…</td></tr></tbody>
  </table></div></div></div>
  <div id="tab-a" class="tab-pane fade">
    <div class="row g-3 mb-3" id="agingBuckets"></div>
    <div class="card sf-card"><div class="table-responsive"><table class="table table-sm sf-table mb-0">
      <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Days</th><th>Bucket</th><th class="text-end">Total</th><th class="text-end">Balance</th></tr></thead>
      <tbody id="rAging"><tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr></tbody>
    </table></div></div>
  </div>
</div>
<script>SF.reports();</script>
