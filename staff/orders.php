<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStaffOrAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'orders';
$pageTitle = 'All Orders';
$db = getDB();

$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($filterStatus !== 'all') {
    $where[] = "o.order_status = ?";
    $params[] = $filterStatus;
}

if (!empty($searchQuery)) {
    $where[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $db->prepare("
    SELECT o.*, COALESCE(NULLIF(o.service_name, ''), s.name) as service_title, s.unit_label
    FROM orders o
    JOIN services s ON o.service_id = s.id
    {$whereSql}
    ORDER BY o.id DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

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
          <h5 class="fw-bold mb-0 text-dark">Laundry Orders Master Table</h5>
          <small class="text-muted">Manage, track, record weights, and update order statuses.</small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newOrderModal">
          <i class="bi bi-plus-lg me-1"></i> New Order
        </button>
      </div>
    </header>

    <div class="app-content">

      
      <div class="card card-custom mb-4 p-3 shadow-sm bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex flex-wrap gap-1">
            <a href="orders.php?status=all" class="btn btn-sm <?= $filterStatus === 'all' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">All</a>
            <a href="orders.php?status=Pending" class="btn btn-sm <?= $filterStatus === 'Pending' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">Pending</a>
            <a href="orders.php?status=Washing" class="btn btn-sm <?= $filterStatus === 'Washing' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">Washing</a>
            <a href="orders.php?status=Drying" class="btn btn-sm <?= $filterStatus === 'Drying' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">Drying</a>
            <a href="orders.php?status=Folding" class="btn btn-sm <?= $filterStatus === 'Folding' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">Folding</a>
            <a href="orders.php?status=Ready" class="btn btn-sm <?= $filterStatus === 'Ready' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">Ready</a>
            <a href="orders.php?status=Completed" class="btn btn-sm <?= $filterStatus === 'Completed' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">Completed</a>
            <a href="orders.php?status=Cancelled" class="btn btn-sm <?= $filterStatus === 'Cancelled' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">Cancelled</a>
          </div>

          <form action="orders.php" method="GET" class="d-flex gap-2">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
            <div class="input-group input-group-sm" style="min-width: 250px;">
              <input type="text" name="q" class="form-control" placeholder="Search order#, name, phone..." value="<?= htmlspecialchars($searchQuery) ?>">
              <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
            <?php if (!empty($searchQuery)): ?>
              <a href="orders.php?status=<?= htmlspecialchars($filterStatus) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      
      <div class="card card-custom shadow-sm mb-4">
        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th class="col-order-no">Order No.</th>
                <th class="col-customer">Customer</th>
                <th class="col-service">Service</th>
                <th class="col-weight">Weight</th>
                <th class="col-amount">Total</th>
                <th class="col-payment">Payment</th>
                <th class="col-status">Status</th>
                <th class="col-pickup">Date Placed</th>
                <th class="col-actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($orders)): ?>
                <tr>
                  <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-2 text-muted opacity-50"></i>
                    No orders match your filter criteria.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($orders as $row): ?>
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
                    </td>

                    <td class="col-weight">
                      <?php if ($row['actual_weight'] !== null): ?>
                        <strong class="text-success"><?= $row['actual_weight'] ?> KG</strong>
                      <?php else: ?>
                        <span class="text-muted"><?= $row['estimated_weight'] ?? '0.00' ?> KG <small>(Est.)</small></span>
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
                      <div class="fw-semibold text-dark"><?= formatDate($row['created_at']) ?></div>
                      <small class="text-muted"><?= formatDateTime($row['created_at'], 'h:i A') ?></small>
                    </td>

                    <td class="col-actions">
                      <div class="table-actions-group">
                        
                        <button type="button" class="btn btn-outline-info btn-update-weight" title="Record Actual Weight"
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

                        
                        <button type="button" class="btn btn-outline-secondary btn-view-order" title="Receipt & Audit"
                          data-order-id="<?= $row['id'] ?>">
                          <i class="bi bi-eye"></i>
                        </button>

                        
                        <?php if ($row['order_status'] !== 'Cancelled' && $row['order_status'] !== 'Completed'): ?>
                          <button type="button" class="btn btn-outline-danger btn-cancel-order" title="Cancel Order"
                            data-bs-toggle="modal" data-bs-target="#cancelOrderModal"
                            data-order-id="<?= $row['id'] ?>"
                            data-order-number="<?= $row['order_number'] ?>">
                            <i class="bi bi-x-circle"></i>
                          </button>
                        <?php endif; ?>
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
