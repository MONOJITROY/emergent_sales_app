<?php use App\Core\View; $s = $settings ?? []; ?>
<div class="d-flex justify-content-between align-items-end mb-3">
  <div>
    <h1 class="h3 fw-bold mb-1">Company Settings</h1>
    <p class="text-muted small mb-0">Manage your business profile and email configuration.</p>
  </div>
</div>

<form id="companyForm" enctype="multipart/form-data">
<input type="hidden" name="id" value="1">

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card sf-card mb-3">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">General Information</span></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small text-uppercase fw-semibold">Company Name</label>
            <input class="form-control form-control-sm" name="company_name" value="<?= View::e($s['company_name'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label small text-uppercase fw-semibold">Address</label>
            <input class="form-control form-control-sm" name="company_address" value="<?= View::e($s['company_address'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Phone</label>
            <input class="form-control form-control-sm" name="company_phone" value="<?= View::e($s['company_phone'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Email</label>
            <input class="form-control form-control-sm" name="company_email" value="<?= View::e($s['company_email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Owner Name</label>
            <input class="form-control form-control-sm" name="owner_name" value="<?= View::e($s['owner_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Website</label>
            <input class="form-control form-control-sm" name="company_website" value="<?= View::e($s['company_website'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="card sf-card mb-3">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">Tax &amp; License</span></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small text-uppercase fw-semibold">GST No</label>
            <input class="form-control form-control-sm" name="company_gst_no" value="<?= View::e($s['company_gst_no'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-uppercase fw-semibold">GST Validity</label>
            <input class="form-control form-control-sm" name="company_gst_validity" type="date" value="<?= View::e($s['company_gst_validity'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-uppercase fw-semibold">PAN</label>
            <input class="form-control form-control-sm" name="company_pan" value="<?= View::e($s['company_pan'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Trade License No</label>
            <input class="form-control form-control-sm" name="company_tradelicenseno" value="<?= View::e($s['company_tradelicenseno'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Trade License Validity</label>
            <input class="form-control form-control-sm" name="tradelicensevalidity" type="date" value="<?= View::e($s['tradelicensevalidity'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="card sf-card mb-3">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">SMTP / Email Configuration</span></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">SMTP Host</label>
            <input class="form-control form-control-sm" name="company_emailhost" value="<?= View::e($s['company_emailhost'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small text-uppercase fw-semibold">Port</label>
            <input class="form-control form-control-sm" name="company_ssl_port" type="number" value="<?= View::e($s['company_ssl_port'] ?? '587') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small text-uppercase fw-semibold">Security</label>
            <select class="form-select form-select-sm" name="company_security">
              <option value="tls" <?= ($s['company_security']??'tls')==='tls'?'selected':'' ?>>TLS</option>
              <option value="ssl" <?= ($s['company_security']??'')==='ssl'?'selected':'' ?>>SSL</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-uppercase fw-semibold">SMTP User</label>
            <input class="form-control form-control-sm" name="company_emailuser" value="<?= View::e($s['company_emailuser'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-uppercase fw-semibold">SMTP Password</label>
            <input class="form-control form-control-sm" name="company_emailpassword" type="password" value="<?= View::e($s['company_emailpassword'] ?? '') ?>">
          </div>
          <div class="col-md-4 d-flex align-items-end pb-1">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="company_smtpauth" value="1" id="smtpAuth" <?= !empty($s['company_smtpauth'])?'checked':'' ?>>
              <label class="form-check-label small text-uppercase fw-semibold" for="smtpAuth">SMTP Auth</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card sf-card mb-3">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">Email Identities</span></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">From Email ID</label>
            <input class="form-control form-control-sm" name="company_fromemailid" value="<?= View::e($s['company_fromemailid'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">From Email Name</label>
            <input class="form-control form-control-sm" name="company_fromemailname" value="<?= View::e($s['company_fromemailname'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Reply-To Email ID</label>
            <input class="form-control form-control-sm" name="company_replytoemailid" value="<?= View::e($s['company_replytoemailid'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Reply-To Name</label>
            <input class="form-control form-control-sm" name="company_replytoemailname" value="<?= View::e($s['company_replytoemailname'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">No-Reply Email ID</label>
            <input class="form-control form-control-sm" name="company_noreplyemailid" value="<?= View::e($s['company_noreplyemailid'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">No-Reply Name</label>
            <input class="form-control form-control-sm" name="company_noreplyemailname" value="<?= View::e($s['company_noreplyemailname'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card sf-card mb-3">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">Company Logo</span></div>
      <div class="card-body text-center">
        <?php if (!empty($s['company_logo'])): ?>
          <img id="logoPreview" src="<?= View::e($_assetUrl) ?>/assets/images/<?= View::e($s['company_logo']) ?>" class="img-fluid mb-3" style="max-height:120px" alt="Logo">
        <?php else: ?>
          <div id="logoPreview" class="text-muted small mb-3 py-4 border rounded bg-light">No logo uploaded</div>
        <?php endif; ?>
        <input class="form-control form-control-sm" type="file" name="company_logo" accept="image/*" id="logoInput">
        <div class="form-text small text-muted">JPEG, PNG, GIF, or WebP. Max 2 MB.</div>
      </div>
    </div>

    <div class="card sf-card mb-3">
      <div class="card-header bg-light py-2"><span class="small text-uppercase fw-semibold text-secondary">Bank Details</span></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Account Holder Name</label>
            <input class="form-control form-control-sm" name="bank_account_holder_name" value="<?= View::e($s['bank_account_holder_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Bank Name</label>
            <input class="form-control form-control-sm" name="bank_name" value="<?= View::e($s['bank_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Account No</label>
            <input class="form-control form-control-sm" name="bank_account_no" value="<?= View::e($s['bank_account_no'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">IFSC Code</label>
            <input class="form-control form-control-sm" name="bank_ifsc_code" value="<?= View::e($s['bank_ifsc_code'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">Branch Name</label>
            <input class="form-control form-control-sm" name="bank_branch_name" value="<?= View::e($s['bank_branch_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">SWIFT Code</label>
            <input class="form-control form-control-sm" name="bank_swift_code" value="<?= View::e($s['bank_swift_code'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">IBAN</label>
            <input class="form-control form-control-sm" name="bank_iban" value="<?= View::e($s['bank_iban'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small text-uppercase fw-semibold">UPI ID</label>
            <input class="form-control form-control-sm" name="bank_upi_id" value="<?= View::e($s['bank_upi_id'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label small text-uppercase fw-semibold">QR Code</label>
            <?php if (!empty($s['bank_qr_code'])): ?>
              <div class="mb-2">
                <img id="qrPreview" src="<?= View::e($_assetUrl) ?>/assets/images/<?= View::e($s['bank_qr_code']) ?>" class="img-fluid border rounded" style="max-height:120px" alt="QR Code">
              </div>
            <?php else: ?>
              <div id="qrPreview" class="text-muted small mb-2 py-3 border rounded bg-light text-center">No QR code uploaded</div>
            <?php endif; ?>
            <input class="form-control form-control-sm" type="file" name="bank_qr_code" accept="image/*" id="qrInput">
            <div class="form-text small text-muted">JPEG, PNG, GIF, or WebP. Upload payment QR code.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card sf-card">
      <div class="card-body">
        <button type="submit" class="btn sf-btn-primary w-100 mb-2" id="saveBtn"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
        <button type="reset" class="btn btn-outline-secondary w-100 btn-sm">Reset</button>
      </div>
    </div>
  </div>
</div>
</form>
<?php $GLOBALS['pageScript'] = '<script>SF.companySettings();</script>'; ?>
