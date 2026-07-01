<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
  <div><h1 class="h3 fw-bold mb-1">Tax Types</h1><p class="text-muted small mb-0">Manage GST rates and duty types.</p></div>
  <div class="d-flex gap-2">
    <button class="btn btn-sm sf-btn-primary" id="newBtn"><i class="bi bi-plus-lg me-1"></i>New Tax Type</button>
  </div>
</div>
<div class="card sf-card"><div class="table-responsive"><table class="table table-sm align-middle sf-table mb-0">
  <thead><tr><th>Tax Name</th><th>Under Group</th><th>Type of Duty</th><th class="text-end">Percentage</th><th class="text-end">Actions</th></tr></thead>
  <tbody id="rows"><tr><td colspan="5" class="text-center text-muted py-3">Loading…</td></tr></tbody>
</table></div></div>

<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Tax Type</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="form"><div class="modal-body"><div class="row g-3">
    <div class="col-12">
      <label class="form-label small text-uppercase fw-semibold">Tax Name *</label>
      <input class="form-control form-control-sm" name="taxname" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small text-uppercase fw-semibold">Under Group</label>
      <select class="form-select form-select-sm" name="undergroup">
        <option value="Duties & Taxes">Duties & Taxes</option>
        <option value="Others">Others</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label small text-uppercase fw-semibold">Type of Duty</label>
      <select class="form-select form-select-sm" name="typeofduty">
        <option value="GST">GST</option>
        <option value="Others">Others</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label small text-uppercase fw-semibold">Percentage</label>
      <input class="form-control form-control-sm" type="number" step="0.01" name="percentage" value="0">
    </div>
    <input type="hidden" name="id">
  </div></div>
  <div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm sf-btn-primary">Save</button></div>
  </form>
</div></div></div>

<?php $GLOBALS['pageScript'] = '<script>SF.taxtypes();</script>'; ?>
