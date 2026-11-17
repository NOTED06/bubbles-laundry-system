<?php

$shopName = getSystemSetting('shop_name', 'VP Bubbles Laundry Shop');
$shopPhone = getSystemSetting('shop_phone', '09540888145');
$shopLogoUrl = getShopLogoUrl();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3 shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" href="<?= BASE_URL ?>/index.php">
      <div class="sidebar-brand-icon shadow-sm flex-shrink-0" style="width: 42px; height: 42px; border-radius: 10px; overflow: hidden; background: white; display: flex; align-items: center; justify-content: center; padding: 2px; border: 1px solid #e2e8f0;">
        <?php if ($shopLogoUrl): ?>
          <img src="<?= $shopLogoUrl ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">
        <?php else: ?>
          <i class="bi bi-droplet-half fs-4 text-primary"></i>
        <?php endif; ?>
      </div>
      <div>
        <span class="fs-5 d-block lh-1 text-primary fw-extrabold"><?= htmlspecialchars($shopName) ?></span>
        <small class="text-muted fw-normal" style="font-size: 0.72rem; letter-spacing: 0.05em;">TORIL, DAVAO CITY</small>
      </div>
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-semibold">
        <li class="nav-item">
          <a class="nav-link px-3 <?= $activePage === 'home' ? 'active text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= $activePage === 'services' ? 'active text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php#services">Services & Rates</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= $activePage === 'estimator' ? 'active text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php#estimator">Rate Estimator</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= $activePage === 'track' ? 'active text-primary' : '' ?>" href="<?= BASE_URL ?>/track.php">
            <i class="bi bi-geo-alt-fill text-primary me-1"></i> Track Order
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3" href="<?= BASE_URL ?>/index.php#contact">Contact & Hours</a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/track.php" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold btn-sm">
          <i class="bi bi-search me-1"></i> Track Laundry
        </a>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm">
          <i class="bi bi-lock-fill me-1"></i> Staff Portal
        </a>
      </div>
    </div>
  </div>
</nav>
