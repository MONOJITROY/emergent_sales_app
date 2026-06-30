<?php use App\Core\View; $base = rtrim((string)\App\Core\App::config('base_url'), '/'); $assetBase = rtrim((string)(\App\Core\App::config('asset_base') ?: $base), '/'); ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in · StockFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css" rel="stylesheet">
<link href="<?= View::e($assetBase) ?>/assets/css/app.css" rel="stylesheet">
<meta name="csrf-token" content="<?= View::e(\App\Core\Csrf::token()) ?>">
<meta name="base-url" content="<?= View::e($base) ?>">
</head><body class="sf-auth">
<div class="container-fluid"><div class="row min-vh-100">
  <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-light">
    <div style="width:100%;max-width:380px">
      <div class="d-flex align-items-center gap-2 mb-4"><span class="sf-logo"></span>
        <!-- <div><div class="fw-bold">StockFlow</div><div class="text-muted small text-uppercase" style="font-size:.7rem;letter-spacing:.06em">Sales & Inventory</div></div> -->
      </div>
      <h1 class="h3 fw-bold mb-1">Sign in to your workspace</h1>
      <p class="text-muted small mb-4">Use the admin credentials below to explore the demo.</p>
      <form id="loginForm" data-testid="login-form" autocomplete="on">
        <div class="mb-3">
          <label class="form-label small text-uppercase fw-semibold text-secondary" style="font-size:.7rem;letter-spacing:.05em">Email</label>
          <input type="email" class="form-control" name="email" value="admin@stockflow.test" required data-testid="login-email-input">
        </div>
        <div class="mb-3">
          <label class="form-label small text-uppercase fw-semibold text-secondary" style="font-size:.7rem;letter-spacing:.05em">Password</label>
          <input type="password" class="form-control" name="password" value="admin123" required data-testid="login-password-input">
        </div>
        <button type="submit" class="btn btn-primary w-100 sf-btn-primary" data-testid="login-submit-button">Sign in</button>
      </form>
      <div class="mt-4 p-3 border rounded bg-white small text-muted">
        <div class="text-uppercase fw-semibold text-secondary mb-1" style="font-size:.7rem;letter-spacing:.05em">Demo credentials</div>
        <code>admin@stockflow.test / admin123</code>
      </div>
    </div>
  </div>
  <div class="col-lg-6 d-none d-lg-block p-0 position-relative">
    <div class="sf-auth-bg w-100 h-100"></div>
    <!-- <div class="position-absolute bottom-0 start-0 p-5 text-white">
      <div class="text-uppercase small mb-2" style="letter-spacing:.06em;color:#fdba74">Operations · Inventory · Sales</div>
      <h2 class="display-5 fw-bold lh-1">Move stock. Close sales. Stay in control.</h2>
      <p class="small opacity-75 mt-3">A compact, no-nonsense workspace for small teams running real-world inventory.</p>
    </div> -->
  </div>
</div></div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
<script src="<?= View::e($assetBase) ?>/assets/js/app.js"></script>
</body></html>
