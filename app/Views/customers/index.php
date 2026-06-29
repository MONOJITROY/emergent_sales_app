<?php $title=$title??'Customers'; $sub=$sub??'People and companies you sell to.'; $endpoint=$endpoint??'customers'; $showBalance = $showBalance ?? true; ?>
<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
  <div><h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($title) ?></h1><p class="text-muted small mb-0"><?= htmlspecialchars($sub) ?></p></div>
  <div class="d-flex gap-2">
    <div class="input-group input-group-sm" style="width:260px">
      <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
      <input class="form-control" id="searchInput" placeholder="Search…">
    </div>
    <button class="btn btn-sm sf-btn-primary" id="newBtn"><i class="bi bi-plus-lg me-1"></i>New</button>
  </div>
</div>
<div class="card sf-card"><div class="table-responsive"><table class="table table-sm align-middle sf-table mb-0">
  <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><?php if($showBalance):?><th class="text-end">Balance</th><?php endif;?><th class="text-end">Actions</th></tr></thead>
  <tbody id="rows"><tr><td colspan="<?= $showBalance?6:5 ?>" class="text-center text-muted py-3">Loading…</td></tr></tbody>
</table></div></div>
<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><?= htmlspecialchars($title) ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="form"><div class="modal-body"><div class="row g-3">
    <div class="col-12"><label class="form-label small text-uppercase fw-semibold">Name *</label><input class="form-control form-control-sm" name="name" required></div>
    <div class="col-md-6"><label class="form-label small text-uppercase fw-semibold">Email</label><input class="form-control form-control-sm" name="email"></div>
    <div class="col-md-6"><label class="form-label small text-uppercase fw-semibold">Phone</label><input class="form-control form-control-sm" name="phone"></div>
    <div class="col-12"><label class="form-label small text-uppercase fw-semibold">Address</label><input class="form-control form-control-sm" name="address"></div>
    <input type="hidden" name="id">
  </div></div>
  <div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm sf-btn-primary">Save</button></div>
  </form>
</div></div></div>
<?php $GLOBALS['pageScript'] = '<script>SF.party(\'' . $endpoint . '\', ' . ($showBalance ? 'true' : 'false') . ');</script>'; ?>
