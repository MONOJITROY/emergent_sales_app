<?php use App\Core\View; ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= View::e($_appName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css" rel="stylesheet">
<link href="<?= View::e($_assetUrl) ?>/assets/css/app.css" rel="stylesheet">
<meta name="csrf-token" content="<?= View::e($_csrf) ?>">
<meta name="base-url" content="<?= View::e($_baseUrl) ?>">
</head><body>
<div class="d-flex min-vh-100">
  <aside class="sf-sidebar text-light flex-shrink-0 d-flex flex-column" id="sidebar">
    <div class="px-3 py-3 border-bottom border-secondary-subtle d-flex align-items-center gap-2">
      <span class="sf-logo"></span><!-- <strong>Medimart-Medical Supplies</strong> -->
    </div>
    <nav class="nav flex-column p-2 small flex-grow-1">
      <?php
        $groups = [
          ['dashboard', '', 'bi-speedometer2', 'Dashboard'],
          ['_group', 'Masters', 'bi-collection', [
            ['products', 'products', 'bi-box-seam', 'Products'],
            ['customers', 'customers', 'bi-people', 'Customers'],
            ['suppliers', 'suppliers', 'bi-truck', 'Suppliers'],
            ['taxtypes', 'taxtypes', 'bi-percent', 'Tax Types'],
          ]],
          ['_group', 'Operations', 'bi-arrow-left-right', [
            ['sales', 'sales', 'bi-receipt', 'Sales'],
            ['purchases', 'purchases', 'bi-cart-plus', 'Purchases'],
            ['receipts', 'receipts', 'bi-cash-stack', 'Receipts'],
            ['payments', 'payments', 'bi-credit-card', 'Payments'],
            ['reconciliation-receipts', 'reconciliation/receipts', 'bi-check2-circle', 'Reconcile Receipts'],
            ['reconciliation-payments', 'reconciliation/payments', 'bi-check2-circle', 'Reconcile Payments'],
          ]],
          ['_group', 'Reports', 'bi-bar-chart-line', [
            ['reports', 'reports', 'bi-bar-chart-line', 'Sales Reports'],
          ]],
        ];
        if (($_user['role'] ?? '')==='admin') {
          $groups[] = ['_group', 'Settings', 'bi-gear', [
            ['company', 'company/settings', 'bi-gear', 'Company'],
            ['invoicetemplates', 'invoice-templates', 'bi-file-earmark-text', 'Invoice Templates'],
            ['users', 'users', 'bi-person-gear', 'Users'],
          ]];
        }

        foreach ($groups as $item):
          if ($item[0] === '_group'):
            [$_, $label, $groupIcon, $children] = $item;
            $groupId = preg_replace('/[^a-z0-9]/i', '', $label);
            $hasActive = false;
            foreach ($children as $ch) { if ($ch[0] === $_active) { $hasActive = true; break; } }
      ?>
        <a class="sf-nav-header" data-bs-toggle="collapse" href="#navGroup<?= $groupId ?>" role="button" aria-expanded="<?= $hasActive ? 'true' : 'false' ?>">
          <span><i class="bi <?= $groupIcon ?> me-2"></i><?= $label ?></span>
          <i class="bi bi-chevron-down"></i>
        </a>
        <div class="collapse sf-nav-group <?= $hasActive ? 'show' : '' ?>" id="navGroup<?= $groupId ?>">
          <?php foreach ($children as $ch): [$key,$path,$chIcon,$chLabel] = $ch;
            $active = $_active === $key ? 'active' : ''; ?>
          <a class="nav-link sf-nav <?= $active ?>" href="<?= View::e($_baseUrl) ?>/<?= $path ?>" data-testid="nav-<?= $key ?>">
            <i class="bi <?= $chIcon ?> me-2"></i><?= $chLabel ?>
          </a>
          <?php endforeach; ?>
        </div>
      <?php else: [$key,$path,$icon,$label] = $item;
        $active = $_active === $key ? 'active' : ''; ?>
        <a class="nav-link sf-nav <?= $active ?>" href="<?= View::e($_baseUrl) ?>/<?= $path ?>" data-testid="nav-<?= $key ?>">
          <i class="bi <?= $icon ?> me-2"></i><?= $label ?>
        </a>
      <?php endif; endforeach; ?>
    </nav>
    <div class="p-3 border-top border-secondary-subtle small">
      <div class="text-uppercase opacity-75" style="font-size:.7rem;letter-spacing:.05em">Signed in</div>
      <div class="fw-semibold text-truncate"><?= View::e($_user['name'] ?? '') ?></div>
      <div class="opacity-75 text-truncate"><?= View::e($_user['email'] ?? '') ?> · <?= View::e($_user['role'] ?? '') ?></div>
      <button class="btn btn-sm btn-outline-light w-100 mt-2" id="logoutBtn" data-testid="logout-btn"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button>
    </div>
  </aside>

  <div class="flex-grow-1 min-w-0 d-flex flex-column">
    <header class="sf-topbar d-flex align-items-center px-3 px-md-4 gap-3">
      <button class="btn btn-sm btn-light d-lg-none" id="sbToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
      <div class="small text-uppercase text-muted" style="letter-spacing:.06em"><?= View::e($_appName) ?></div>
      <span class="text-muted">/</span>
      <div class="fw-semibold text-capitalize"><?= View::e($_active ?: 'page') ?></div>
      <div class="ms-auto small text-muted d-none d-sm-block"><?= date('D, M j Y') ?></div>
    </header>
    <main class="p-3 p-md-4 flex-grow-1"><?= $content ?></main>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= View::e($_assetUrl) ?>/assets/js/app.js"></script>
<?= $GLOBALS['pageScript'] ?? '' ?><?php unset($GLOBALS['pageScript']); ?>
</body></html>
