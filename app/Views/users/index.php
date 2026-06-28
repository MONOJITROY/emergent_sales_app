<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
  <div><h1 class="h3 fw-bold mb-1">Users & Roles</h1><p class="text-muted small mb-0">Manage staff accounts (admin only).</p></div>
  <button class="btn btn-sm sf-btn-primary" id="newBtn"><i class="bi bi-plus-lg me-1"></i>New User</button>
</div>
<div class="card sf-card"><div class="table-responsive"><table class="table table-sm align-middle sf-table mb-0">
  <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
  <tbody id="rows"><tr><td colspan="5" class="text-center text-muted py-3">Loading…</td></tr></tbody>
</table></div></div>

<div class="modal fade" id="userModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">New user</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="userForm"><div class="modal-body row g-3">
    <div class="col-12"><label class="form-label small text-uppercase fw-semibold">Name</label><input class="form-control form-control-sm" name="name" required></div>
    <div class="col-12"><label class="form-label small text-uppercase fw-semibold">Email</label><input class="form-control form-control-sm" type="email" name="email" required></div>
    <div class="col-12"><label class="form-label small text-uppercase fw-semibold">Password</label><input class="form-control form-control-sm" type="password" name="password" required></div>
    <div class="col-12"><label class="form-label small text-uppercase fw-semibold">Role</label>
      <select class="form-select form-select-sm" name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select>
    </div>
  </div><div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm sf-btn-primary">Save</button></div></form>
</div></div></div>
<script>SF.users();</script>
