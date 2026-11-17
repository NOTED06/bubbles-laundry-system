<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStaffOrAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'dashboard';
$pageTitle = 'Staff Dashboard';
$today = date('Y-m-d');
$db = getDB();

$todayOrdersStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
$todayOrdersStmt->execute([$today]);
$todayOrdersCount = (int)$todayOrdersStmt->fetchColumn();

$pendingCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'")->fetchColumn();

$pickupsTodayStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE pickup_date = ? AND order_status NOT IN ('Completed', 'Cancelled')");
$pickupsTodayStmt->execute([$today]);
$pickupsTodayCount = (int)$pickupsTodayStmt->fetchColumn();

$processingCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('Picked Up', 'Washing', 'Drying', 'Folding')")->fetchColumn();

$readyCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Ready'")->fetchColumn();

$completedTodayStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE order_status = 'Completed' AND DATE(updated_at) = ?");
$completedTodayStmt->execute([$today]);
$completedTodayCount = (int)$completedTodayStmt->fetchColumn();

$cancelledCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Cancelled'")->fetchColumn();

$activeOrdersStmt = $db->query("
    SELECT o.*, COALESCE(NULLIF(o.service_name, ''), s.name) as service_title, s.unit_label
    FROM orders o
    JOIN services s ON o.service_id = s.id
    WHERE o.order_status NOT IN ('Completed', 'Cancelled')
    ORDER BY o.pickup_date ASC, o.pickup_time ASC
");
$activeOrders = $activeOrdersStmt->fetchAll();

$overdueOrders = [];
$dueSoonOrders = [];
$upcomingOrders = [];

foreach ($activeOrders as &$ord) {
    $urgency = calculateOrderUrgency($ord['pickup_date'], $ord['pickup_time'], $ord['order_status']);
    $ord['urgency'] = $urgency;

    if ($urgency['status'] === 'overdue') {
        $overdueOrders[] = $ord;
    } elseif ($urgency['status'] === 'due_soon') {
        $dueSoonOrders[] = $ord;
    } else {
        $upcomingOrders[] = $ord;
    }
}
unset($ord);

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
          <h5 class="fw-bold mb-0 text-dark">Staff Operations Dashboard</h5>
          <small class="text-muted"><i class="bi bi-geo-alt-fill text-primary"></i> St. Jude Village, Daliao Toril &bull; <?= date('l, F d, Y') ?></small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3">
        <div class="d-none d-md-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill border">
          <i class="bi bi-clock-history text-primary"></i>
          <span id="liveClock" class="fw-bold text-dark small">--:--:--</span>
        </div>

        <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newOrderModal">
          <i class="bi bi-plus-lg me-1"></i> New Order
        </button>
      </div>
    </header>

    <div class="app-content">

      
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Today's Orders</span>
                <h3 class="fw-extrabold text-primary mt-1 mb-0"><?= $todayOrdersCount ?></h3>
              </div>
              <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-receipt"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Pickups Today</span>
                <h3 class="fw-extrabold text-info mt-1 mb-0"><?= $pickupsTodayCount ?></h3>
              </div>
              <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-truck"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">In Processing</span>
                <h3 class="fw-extrabold text-warning mt-1 mb-0"><?= $processingCount ?></h3>
              </div>
              <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-water"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Ready for Claim</span>
                <h3 class="fw-extrabold text-success mt-1 mb-0"><?= $readyCount ?></h3>
              </div>
              <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check2-circle"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Completed</span>
                <h3 class="fw-extrabold text-dark mt-1 mb-0"><?= $completedTodayCount ?></h3>
              </div>
              <div class="stat-icon bg-dark bg-opacity-10 text-dark">
                <i class="bi bi-bag-check-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Cancelled</span>
                <h3 class="fw-extrabold text-danger mt-1 mb-0"><?= $cancelledCount ?></h3>
              </div>
              <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-x-octagon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="card card-custom mb-4 border-top border-4 border-warning shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-alarm-fill text-warning fs-5"></i>
            <h6 class="fw-bold mb-0 text-dark">Time Management & Urgency Queues</h6>
          </div>
          <small class="text-muted">Prioritized by scheduled pickup vs current time</small>
        </div>
        <div class="card-body p-3">
          <div class="row g-3">
            
            <div class="col-md-4">
              <div class="border rounded-3 p-3 bg-danger bg-opacity-10 h-100 border-danger">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> OVERDUE</span>
                  <span class="badge bg-danger rounded-pill"><?= count($overdueOrders) ?> Orders</span>
                </div>
                <?php if (empty($overdueOrders)): ?>
                  <div class="text-muted small py-3 text-center">No overdue orders! Great work!</div>
                <?php else: ?>
                  <div class="d-flex flex-column gap-2">
                    <?php foreach ($overdueOrders as $ord): ?>
                      <div class="card p-2 bg-white border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                          <strong class="text-dark"><?= $ord['order_number'] ?></strong>
                          <span class="badge bg-danger"><?= $ord['urgency']['diff_text'] ?></span>
                        </div>
                        <div class="small text-muted"><?= htmlspecialchars($ord['customer_name']) ?> &bull; <?= $ord['pickup_time'] ?></div>
                        <div class="mt-2 d-flex gap-1">
                          <button class="btn btn-xs btn-outline-primary py-0 px-2 btn-update-status" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?= $ord['id'] ?>" data-order-number="<?= $ord['order_number'] ?>" data-status="<?= $ord['order_status'] ?>">Advance</button>
                          <button class="btn btn-xs btn-outline-secondary py-0 px-2 btn-view-order" style="font-size: 0.75rem;" data-order-id="<?= $ord['id'] ?>">View</button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            
            <div class="col-md-4">
              <div class="border rounded-3 p-3 bg-warning bg-opacity-10 h-100 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold text-warning-emphasis"><i class="bi bi-hourglass-top me-1"></i> DUE SOON (Within 2h)</span>
                  <span class="badge bg-warning text-dark rounded-pill"><?= count($dueSoonOrders) ?> Orders</span>
                </div>
                <?php if (empty($dueSoonOrders)): ?>
                  <div class="text-muted small py-3 text-center">No orders due in next 2 hours.</div>
                <?php else: ?>
                  <div class="d-flex flex-column gap-2">
                    <?php foreach ($dueSoonOrders as $ord): ?>
                      <div class="card p-2 bg-white border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                          <strong class="text-dark"><?= $ord['order_number'] ?></strong>
                          <span class="badge bg-warning text-dark"><?= $ord['urgency']['diff_text'] ?></span>
                        </div>
                        <div class="small text-muted"><?= htmlspecialchars($ord['customer_name']) ?> &bull; <?= $ord['pickup_time'] ?></div>
                        <div class="mt-2 d-flex gap-1">
                          <button class="btn btn-xs btn-outline-primary py-0 px-2 btn-update-status" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?= $ord['id'] ?>" data-order-number="<?= $ord['order_number'] ?>" data-status="<?= $ord['order_status'] ?>">Advance</button>
                          <button class="btn btn-xs btn-outline-secondary py-0 px-2 btn-view-order" style="font-size: 0.75rem;" data-order-id="<?= $ord['id'] ?>">View</button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            
            <div class="col-md-4">
              <div class="border rounded-3 p-3 bg-light h-100 border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold text-primary"><i class="bi bi-calendar-event me-1"></i> UPCOMING SCHEDULE</span>
                  <span class="badge bg-info text-dark rounded-pill"><?= count($upcomingOrders) ?> Orders</span>
                </div>
                <?php if (empty($upcomingOrders)): ?>
                  <div class="text-muted small py-3 text-center">No upcoming orders.</div>
                <?php else: ?>
                  <div class="d-flex flex-column gap-2" style="max-height: 240px; overflow-y: auto;">
                    <?php foreach ($upcomingOrders as $ord): ?>
                      <div class="card p-2 bg-white border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                          <strong class="text-dark"><?= $ord['order_number'] ?></strong>
                          <span class="badge bg-light text-dark border"><?= $ord['urgency']['diff_text'] ?></span>
                        </div>
                        <div class="small text-muted"><?= htmlspecialchars($ord['customer_name']) ?> &bull; <?= formatDate($ord['pickup_date']) ?> <?= $ord['pickup_time'] ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="card card-custom shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-task text-primary me-2"></i> Active Laundry Orders</h6>
            <small class="text-muted">Orders currently in progress, waiting for weighing, or ready for customer claim.</small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="max-width: 250px;">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="tableSearchInput" class="form-control border-start-0" placeholder="Filter orders...">
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-custom data-table align-middle mb-0">
            <thead>
              <tr>
                <th class="col-order-no">Order No.</th>
                <th class="col-customer">Customer</th>
                <th class="col-service">Service & Rate</th>
                <th class="col-weight">Weight</th>
                <th class="col-amount">Total</th>
                <th class="col-payment">Payment</th>
                <th class="col-status">Status</th>
                <th class="col-pickup">Pickup Schedule</th>
                <th class="col-actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($activeOrders)): ?>
                <tr>
                  <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-muted opacity-50"></i>
                    No active orders at the moment. Click <strong>"New Order"</strong> to create one!
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($activeOrders as $row): ?>
                  <tr>
                    <td class="col-order-no">
                      <button class="order-num-badge btn-copy border-0" data-copy="<?= $row['order_number'] ?>" title="Click to copy">
                        <?= $row['order_number'] ?>
                      </button>
                      <small class="d-block text-muted mt-1" style="font-size: 0.72rem;"><i class="bi bi-calendar3 me-1"></i><?= formatDate($row['created_at']) ?></small>
                    </td>

                    <td class="col-customer">
                      <div class="fw-bold text-dark text-truncate" style="max-width: 170px;"><?= htmlspecialchars($row['customer_name']) ?></div>
                      <a href="tel:<?= htmlspecialchars($row['customer_phone']) ?>" class="small text-muted text-decoration-none d-block">
                        <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($row['customer_phone']) ?>
                      </a>
                    </td>

                    <td class="col-service">
                      <span class="badge badge-service"><?= htmlspecialchars($row['service_title']) ?></span>
                      <small class="d-block text-muted">₱<?= number_format($row['price_per_kg'], 2) ?>/<?= htmlspecialchars($row['unit_label']) ?></small>
                    </td>

                    <td class="col-weight">
                      <?php if ($row['actual_weight'] !== null): ?>
                        <strong class="text-success"><?= $row['actual_weight'] ?> KG</strong>
                        <small class="d-block text-muted">(Actual)</small>
                      <?php else: ?>
                        <span class="text-warning fw-semibold"><?= $row['estimated_weight'] ?? '0.00' ?> KG</span>
                        <small class="d-block text-muted">(Est. Weighed on dropoff)</small>
                      <?php endif; ?>
                    </td>

                    <td class="col-amount">
                      <strong class="text-dark fs-6">₱ <?= number_format($row['total_amount'], 2) ?></strong>
                    </td>

                    <td class="col-payment">
                      <?= getPaymentBadge($row['payment_status'], $row['payment_method'], (float)($row['amount_paid'] ?? 0), (float)$row['total_amount']) ?>
                    </td>

                    <td class="col-status">
                      <?= getStatusBadge($row['order_status']) ?>
                    </td>

                    <td class="col-pickup">
                      <div class="fw-semibold text-dark"><?= formatDate($row['pickup_date']) ?></div>
                      <small class="text-muted fw-semibold"><?= $row['pickup_time'] ?></small>
                      <?php 
                        $urgencyInfo = $row['urgency'] ?? calculateOrderUrgency($row['pickup_date'], $row['pickup_time'], $row['order_status']);
                      ?>
                      <div class="mt-1">
                        <span class="urgency-badge <?= $urgencyInfo['badge_class'] ?? 'bg-secondary' ?>">
                          <?= $urgencyInfo['label'] ?? '' ?>
                        </span>
                      </div>
                    </td>

                    <td class="col-actions">
                      <div class="table-actions-group">
                        
                        <button type="button" class="btn btn-outline-info btn-update-weight" title="Record Actual KG"
                          data-bs-toggle="modal" data-bs-target="#updateWeightModal"
                          data-order-id="<?= $row['id'] ?>"
                          data-order-number="<?= $row['order_number'] ?>"
                          data-weight="<?= $row['actual_weight'] ?? $row['estimated_weight'] ?>"
                          data-unit-price="<?= $row['price_per_kg'] ?>"
                          data-detergent-qty="<?= $row['detergent_qty'] ?>">
                          <i class="bi bi-speedometer2"></i>
                        </button>

                        
                        <button type="button" class="btn btn-outline-primary btn-update-status" title="Update Status"
                          data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                          data-order-id="<?= $row['id'] ?>"
                          data-order-number="<?= $row['order_number'] ?>"
                          data-status="<?= $row['order_status'] ?>">
                          <i class="bi bi-arrow-repeat"></i>
                        </button>

                        
                        <button type="button" class="btn btn-outline-success btn-pay-modal" title="Record Payment"
                          data-bs-toggle="modal" data-bs-target="#paymentModal"
                          data-order-id="<?= $row['id'] ?>"
                          data-order-number="<?= $row['order_number'] ?>"
                          data-amount="<?= $row['total_amount'] ?>"
                          data-amount-paid="<?= $row['amount_paid'] ?? 0 ?>"
                          data-balance="<?= max(0, $row['total_amount'] - ($row['amount_paid'] ?? 0)) ?>"
                          data-payment-status="<?= $row['payment_status'] ?>"
                          data-payment-method="<?= $row['payment_method'] ?>"
                          data-reference="<?= htmlspecialchars($row['gcash_reference'] ?? '') ?>">
                          <i class="bi bi-cash-coin"></i>
                        </button>

                        
                        <button type="button" class="btn btn-outline-secondary btn-view-order" title="View Summary & Receipt"
                          data-order-id="<?= $row['id'] ?>">
                          <i class="bi bi-eye"></i>
                        </button>

                        
                        <button type="button" class="btn btn-outline-danger btn-cancel-order" title="Cancel Order"
                          data-bs-toggle="modal" data-bs-target="#cancelOrderModal"
                          data-order-id="<?= $row['id'] ?>"
                          data-order-number="<?= $row['order_number'] ?>">
                          <i class="bi bi-x-circle"></i>
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
