<div class="d-flex justify-content-between align-items-end mb-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Invoice Templates</h1>
    <p class="text-muted small mb-0">Select and set a default invoice template.</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card sf-card">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">Available Templates</span></div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label small text-uppercase fw-semibold">Choose Template</label>
          <select class="form-select form-select-sm" id="templateSelect">
            <option value="">-- Select a template --</option>
          </select>
        </div>
        <div class="small text-muted mb-3" id="currentDefault">No default template set</div>
        <button type="button" class="btn sf-btn-primary w-100" id="setDefaultBtn" disabled>
          <i class="bi bi-check-lg me-1"></i>Set as Default
        </button>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card sf-card">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">Preview</span></div>
      <div class="card-body p-0">
        <div id="previewArea" class="text-center text-muted small py-5">Select a template to preview</div>
        <iframe id="previewFrame" src="" style="display:none;width:100%;height:600px;border:0;border-radius:0 0 var(--sf-radius,6px) var(--sf-radius,6px)"></iframe>
      </div>
    </div>
  </div>
</div>

<?php $GLOBALS['pageScript'] = '<script>SF.invoiceTemplates();</script>'; ?>
