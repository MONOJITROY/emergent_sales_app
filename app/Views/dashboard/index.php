<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
  <div><h1 class="h3 fw-bold mb-1">Operations Overview</h1><p class="text-muted small mb-0">A snapshot of today’s activity.</p></div>
</div>
<div class="row g-3 mb-3" id="kpis"></div>
<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="card sf-card"><div class="card-body">
    <div class="text-uppercase text-secondary small fw-semibold mb-2" style="font-size:.7rem;letter-spacing:.06em">Sales · last 7 days</div>
    <canvas id="salesChart" height="90"></canvas>
  </div></div></div>
  <div class="col-lg-4"><div class="card sf-card h-100"><div class="card-body">
    <div class="text-uppercase text-secondary small fw-semibold mb-2" style="font-size:.7rem;letter-spacing:.06em">Low Stock</div>
    <ul class="list-unstyled mb-0 small" id="lowStock"></ul>
  </div></div></div>
</div>
<div class="card sf-card"><div class="card-body">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="text-uppercase text-secondary small fw-semibold" style="font-size:.7rem;letter-spacing:.06em">Recent invoices</div>
    <a href="<?= htmlspecialchars($_baseUrl) ?>/sales" class="small text-decoration-none text-orange">View all →</a>
  </div>
  <div class="table-responsive"><table class="table table-sm align-middle sf-table mb-0">
    <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
    <tbody id="recentSales"><tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr></tbody>
  </table></div>
</div></div>
<script>SF.dashboard();</script>
