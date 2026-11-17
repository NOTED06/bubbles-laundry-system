<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStaffOrAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'pickups';
$pageTitle = "Today's Pickups";
$today = date('Y-m-d');
$db = getDB();

$stmt = $db->prepare("
    SELECT o.*, s.name as service_title
    FROM orders o
    JOIN services s ON o.service_id = s.id
    WHERE o.pickup_date = ? AND o.order_status NOT IN ('Completed', 'Cancelled')
    ORDER BY o.pickup_time ASC
");
$stmt->execute([$today]);
$pickups = $stmt->fetchAll();

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
          <h5 class="fw-bold mb-0 text-dark">Scheduled Pickups Queue</h5>
          <small class="text-muted"><i class="bi bi-calendar-event text-primary"></i> Pickups scheduled for today: <strong><?= date('F d, Y') ?></strong></small>
        </div>
      </div>

      <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newOrderModal">
        <i class="bi bi-plus-lg me-1"></i> New Order
      </button>
    </header>

    <div class="app-content">
      <div class="card card-custom shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0 text-dark">Today's Pickup Schedule</h6>
          <span class="badge bg-primary rounded-pill"><?= count($pickups) ?> Scheduled</span>
        </div>

        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Time Slot</th>
                <th>Order No.</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Customer Address</th>
                <th>Service</th>
                <th>Current Status</th>
                <th class="text-end">Quick Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($pickups)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-calendar2-check fs-1 d-block mb-2 text-muted opacity-50"></i>
                    No pickups scheduled for today!
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($pickups as $row): ?>
                  <tr>
                    <td>
                      <span class="badge bg-light text-dark border px-3 py-2 fw-bold">
                        <i class="bi bi-clock me-1 text-primary"></i> <?= $row['pickup_time'] ?>
                      </span>
                    </td>
                    <td>
                      <strong class="text-primary"><?= $row['order_number'] ?></strong>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= htmlspecialchars($row['customer_name']) ?></div>
                    </td>
                    <td>
                      <a href="tel:<?= htmlspecialchars($row['customer_phone']) ?>" class="btn btn-sm btn-light border">
                        <i class="bi bi-telephone text-primary me-1"></i> <?= htmlspecialchars($row['customer_phone']) ?>
                      </a>
                    </td>
                    <td>
                      <small class="text-muted d-block text-truncate" style="max-width: 250px;">
                        <i class="bi bi-geo-alt me-1 text-danger"></i> <?= htmlspecialchars($row['pickup_address']) ?>
                      </small>
                    </td>
                    <td>
                      <span class="badge badge-service"><?= htmlspecialchars($row['service_title']) ?></span>
                    </td>
                    <td>
                      <?= getStatusBadge($row['order_status']) ?>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        
                        <button type="button" class="btn btn-outline-info btn-update-weight" title="Weigh Laundry (KG)"
                          data-bs-toggle="modal" data-bs-target="#updateWeightModal"
                          data-order-id="<?= $row['id'] ?>"
                          data-order-number="<?= $row['order_number'] ?>"
                          data-weight="<?= $row['actual_weight'] ?? $row['estimated_weight'] ?>"
                          data-unit-price="<?= $row['price_per_kg'] ?>"
                          data-detergent-qty="<?= $row['detergent_qty'] ?>">
                          <i class="bi bi-speedometer2 me-1"></i> Weigh
                        </button>

                        
                        <button type="button" class="btn btn-outline-primary btn-update-status" title="Advance Status"
                          data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                          data-order-id="<?= $row['id'] ?>"
                          data-order-number="<?= $row['order_number'] ?>"
                          data-status="<?= $row['order_status'] ?>">
                          <i class="bi bi-arrow-repeat"></i>
                        </button>
                      </div>
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
