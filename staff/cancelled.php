<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStaffOrAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'cancelled';
$pageTitle = 'Cancelled Orders Archive';
$db = getDB();

$stmt = $db->query("
    SELECT 
        o.*,
        c.reason as cancellation_reason,
        c.cancelled_at,
        u.fullname as cancelled_by_name
    FROM orders o
    JOIN cancellations c ON o.id = c.order_id
    LEFT JOIN users u ON c.cancelled_by = u.id
    ORDER BY c.id DESC
");
$cancelledOrders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="app-container">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="app-main">
    <header class="app-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-light border d-lg-none" id="sidebarToggle">
          <i class="bi bi-list fs-5"></i>
        </button>
        <div>
          <h5 class="fw-bold mb-0 text-dark">Cancelled Orders Archive</h5>
          <small class="text-muted">Audit log of all cancelled laundry bookings with recorded reasons.</small>
        </div>
      </div>
    </header>

    <div class="app-content">
      <div class="card card-custom shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-x-octagon-fill me-2"></i> Cancelled Transactions</h6>
          <span class="badge bg-danger rounded-pill"><?= count($cancelledOrders) ?> Records</span>
        </div>

        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Order No.</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Original Amount</th>
                <th>Cancellation Reason</th>
                <th>Cancelled By</th>
                <th>Date & Time</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($cancelledOrders)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle fs-1 d-block mb-2 text-success opacity-50"></i>
                    No cancelled orders recorded in the system.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($cancelledOrders as $row): ?>
                  <tr>
                    <td>
                      <strong class="text-danger"><?= $row['order_number'] ?></strong>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= htmlspecialchars($row['customer_name']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($row['customer_phone']) ?></small>
                    </td>
                    <td>
                      <span class="badge badge-service"><?= htmlspecialchars($row['service_name']) ?></span>
                    </td>
                    <td>
                      <span class="text-muted text-decoration-line-through">₱ <?= number_format($row['total_amount'], 2) ?></span>
                    </td>
                    <td>
                      <div class="alert alert-danger py-1 px-2 mb-0 small" style="max-width: 300px;">
                        <i class="bi bi-quote me-1"></i><?= htmlspecialchars($row['cancellation_reason']) ?>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['cancelled_by_name'] ?? 'Staff') ?></span>
                    </td>
                    <td>
                      <small class="text-muted"><?= formatDateTime($row['cancelled_at']) ?></small>
                    </td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-secondary btn-view-order" data-order-id="<?= $row['id'] ?>">
                        <i class="bi bi-eye me-1"></i> Audit Trail
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/modals.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
