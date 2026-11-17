<?php

require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
$role = $currentUser['role'] ?? 'staff';
$activeMenu = $activeMenu ?? 'dashboard';

$db = getDB();
$today = date('Y-m-d');

$overdueCount = 0;
$pendingStmt = $db->query("SELECT pickup_date, pickup_time, order_status FROM orders WHERE order_status NOT IN ('Ready', 'Completed', 'Cancelled')");
while ($row = $pendingStmt->fetch()) {
    $urgency = calculateOrderUrgency($row['pickup_date'], $row['pickup_time'], $row['order_status']);
    if ($urgency['status'] === 'overdue') {
        $overdueCount++;
    }
}

$pickupsStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status NOT IN ('Completed', 'Cancelled')");
$pickupsStmt->execute([$today]);
$todayPickupsCount = (int)$pickupsStmt->fetchColumn();

$readyStmt = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Ready'");
$readyCount = (int)$readyStmt->fetchColumn();

$shopLogoUrl = getShopLogoUrl();
?>
<aside class="app-sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon flex-shrink-0" style="overflow: hidden; border-radius: 12px; background: white; padding: 2px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
      <?php if ($shopLogoUrl): ?>
        <img src="<?= $shopLogoUrl ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 10px;">
      <?php else: ?>
        <i class="bi bi-droplet-half fs-4 text-primary"></i>
      <?php endif; ?>
    </div>
    <div>
      <div class="fw-bold text-white lh-sm" style="font-size: 0.95rem;"><?= htmlspecialchars(getSystemSetting('shop_name', 'VP Bubbles Laundry Shop')) ?></div>
      <span class="badge bg-primary text-uppercase mt-1" style="font-size: 0.65rem; letter-spacing: 0.05em;"><?= strtoupper($role) ?> PORTAL</span>
    </div>
  </div>

  <div class="sidebar-menu">
    <div class="sidebar-heading">Operations</div>

    <a href="<?= BASE_URL ?>/<?= $role ?>/dashboard.php" class="sidebar-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-grid-1x2-fill"></i>
      <span>Dashboard</span>
    </a>

    <a href="<?= BASE_URL ?>/<?= $role === 'admin' ? 'admin' : 'staff' ?>/orders.php" class="sidebar-link <?= $activeMenu === 'orders' ? 'active' : '' ?>">
      <i class="bi bi-basket3-fill"></i>
      <span>All Orders</span>
      <?php if ($overdueCount > 0): ?>
        <span class="badge bg-danger text-white ms-auto priority-pulse" title="Overdue Orders"><?= $overdueCount ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= BASE_URL ?>/staff/pickups.php" class="sidebar-link <?= $activeMenu === 'pickups' ? 'active' : '' ?>">
      <i class="bi bi-calendar-check-fill"></i>
      <span>Today's Pickups</span>
      <?php if ($todayPickupsCount > 0): ?>
        <span class="badge bg-info text-dark ms-auto"><?= $todayPickupsCount ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= BASE_URL ?>/staff/customers.php" class="sidebar-link <?= $activeMenu === 'customers' ? 'active' : '' ?>">
      <i class="bi bi-people-fill"></i>
      <span>Customer Records</span>
    </a>

    <a href="<?= BASE_URL ?>/staff/cancelled.php" class="sidebar-link <?= $activeMenu === 'cancelled' ? 'active' : '' ?>">
      <i class="bi bi-x-octagon-fill"></i>
      <span>Cancelled Archive</span>
    </a>

    <?php if ($role === 'admin'): ?>
      <div class="sidebar-heading mt-3">Administration</div>

      <a href="<?= BASE_URL ?>/admin/services.php" class="sidebar-link <?= $activeMenu === 'services' ? 'active' : '' ?>">
        <i class="bi bi-tags-fill"></i>
        <span>Services & Rates</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/staff.php" class="sidebar-link <?= $activeMenu === 'staff' ? 'active' : '' ?>">
        <i class="bi bi-person-badge-fill"></i>
        <span>Staff Accounts</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/reports.php" class="sidebar-link <?= $activeMenu === 'reports' ? 'active' : '' ?>">
        <i class="bi bi-graph-up-arrow"></i>
        <span>Reports & Analytics</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/backup.php" class="sidebar-link <?= $activeMenu === 'backup' ? 'active' : '' ?>">
        <i class="bi bi-database-fill-gear"></i>
        <span>Backup & Restore</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-link <?= $activeMenu === 'settings' ? 'active' : '' ?>">
        <i class="bi bi-gear-fill"></i>
        <span>Shop Settings</span>
      </a>
    <?php endif; ?>

    <div class="sidebar-heading mt-3">Public Tools</div>
    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="sidebar-link">
      <i class="bi bi-box-arrow-up-right"></i>
      <span>Customer Landing</span>
    </a>
    <a href="<?= BASE_URL ?>/track.php" target="_blank" class="sidebar-link">
      <i class="bi bi-search"></i>
      <span>Public Tracking</span>
    </a>
  </div>

  <div class="sidebar-user">
    <div class="d-flex align-items-center gap-2 overflow-hidden">
      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem;">
        <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
      </div>
      <div class="overflow-hidden">
        <div class="text-white text-truncate fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></div>
        <small class="text-white-50 d-block" style="font-size: 0.72rem;"><?= htmlspecialchars($currentUser['email'] ?? '') ?></small>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-light border-0 text-white-50 hover-text-white" title="Logout">
      <i class="bi bi-box-arrow-right fs-5"></i>
    </a>
  </div>
</aside>
