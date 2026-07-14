<h1 class="h3 fw-bold mb-1">Product Report</h1>
<p class="text-muted small mb-3">Product-wise sales, purchases and stock summary.</p>

<div class="row g-3 mb-3" id="prSummary"></div>

<div class="card sf-card">
  <div class="table-responsive">
    <table class="table table-sm sf-table mb-0">
      <thead><tr>
        <th>SKU</th><th>Product</th><th>Category</th><th>Unit</th>
        <th class="text-end">Cost Price</th><th class="text-end">Sale Price</th>
        <th class="text-end">Stock</th><th class="text-end">Reorder</th>
        <th class="text-end">Qty Sold</th><th class="text-end">Sales Revenue</th>
        <th class="text-end">Qty Purchased</th><th class="text-end">Purchase Cost</th>
      </tr></thead>
      <tbody id="prRows"><tr><td colspan="12" class="text-center text-muted py-3">Loading…</td></tr></tbody>
      <tfoot id="prFoot"></tfoot>
    </table>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.productReport();</script>'; ?>
