<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
  <div><h1 class="h3 fw-bold mb-1">Products</h1><p class="text-muted small mb-0">Master list of items, with current stock.</p></div>
  <div class="d-flex gap-2">
    <div class="input-group input-group-sm" style="width:260px">
      <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
      <input class="form-control" id="searchInput" placeholder="Search SKU or name…" data-testid="products-search">
    </div>
    <button class="btn btn-sm sf-btn-primary" id="newBtn" data-testid="add-product-btn"><i class="bi bi-plus-lg me-1"></i>New Product</button>
  </div>
</div>
<div class="card sf-card"><div class="table-responsive"><table class="table table-sm align-middle sf-table mb-0">
  <thead><tr><th>HSN</th><th>SKU</th><th>Name</th><th>Category</th><th class="text-end">Cost</th><th class="text-end">Price</th><th class="text-end">Stock</th><th class="text-end">Reorder</th><th class="text-end">Actions</th></tr></thead>
  <tbody id="rows"><tr><td colspan="8" class="text-center text-muted py-3">Loading…</td></tr></tbody>
</table></div></div>

<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Product</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="form"><div class="modal-body"><div class="row g-3">
    <div class="col-md-3"><label class="form-label small text-uppercase fw-semibold">HSN *</label><input class="form-control form-control-sm" name="hsn" required></div>
    <div class="col-md-3"><label class="form-label small text-uppercase fw-semibold">SKU *</label><input class="form-control form-control-sm" name="sku" required></div>
    <div class="col-md-6"><label class="form-label small text-uppercase fw-semibold">Name *</label><input class="form-control form-control-sm" name="name" required></div>
    <div class="col-md-6"><label class="form-label small text-uppercase fw-semibold">Category</label><input class="form-control form-control-sm" name="category"></div>
    <div class="col-md-6"><label class="form-label small text-uppercase fw-semibold">Unit</label><input class="form-control form-control-sm" name="unit" value="pcs"></div>
    <div class="col-md-3"><label class="form-label small text-uppercase fw-semibold">Cost</label><input class="form-control form-control-sm" type="number" step="any" name="cost_price" value="0"></div>
    <div class="col-md-3"><label class="form-label small text-uppercase fw-semibold">Price</label><input class="form-control form-control-sm" type="number" step="any" name="sale_price" value="0"></div>
    <div class="col-md-3"><label class="form-label small text-uppercase fw-semibold">Stock</label><input class="form-control form-control-sm" type="number" step="any" name="stock" value="0"></div>
    <div class="col-md-3"><label class="form-label small text-uppercase fw-semibold">Reorder</label><input class="form-control form-control-sm" type="number" step="any" name="reorder_level" value="0"></div>
    <input type="hidden" name="id">
  </div></div>
  <div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm sf-btn-primary" data-testid="save-product-btn">Save</button></div>
  </form>
</div></div></div>

<?php $GLOBALS['pageScript'] = '<script>SF.products();</script>'; ?>
