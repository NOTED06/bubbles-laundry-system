<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'reports';
$pageTitle = 'Reports & Financial Analytics';
$db = getDB();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$paymentMethod = $_GET['payment_method'] ?? 'all';
$status = $_GET['status'] ?? 'all';

$where = ["DATE(o.created_at) BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if ($paymentMethod !== 'all') {
    $where[] = "o.payment_method = ?";
    $params[] = $paymentMethod;
}
if ($status !== 'all') {
    $where[] = "o.order_status = ?";
    $params[] = $status;
}

$whereSql = "WHERE " . implode(" AND ", $where);

$reportQuery = "
    SELECT 
        o.*,
        s.name as service_title
    FROM orders o
    JOIN services s ON o.service_id = s.id
    {$whereSql}
    ORDER BY o.id DESC
";
$stmt = $db->prepare($reportQuery);
$stmt->execute($params);
$reportOrders = $stmt->fetchAll();

$totalRevenue = 0;
$totalWeight = 0;
$paidCount = 0;
$pendingCount = 0;

foreach ($reportOrders as $r) {
    $paid = (float)($r['amount_paid'] ?? 0);
    $totalRevenue += $paid;
    if ($r['payment_status'] === 'Paid') {
        $paidCount++;
    } else {
        $pendingCount++;
    }
    $w = $r['actual_weight'] !== null ? (float)$r['actual_weight'] : (float)$r['estimated_weight'];
    $totalWeight += $w;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="app-container">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="app-main">
    <header class="app-topbar no-print">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-light border d-lg-none" id="sidebarToggle">
          <i class="bi bi-list fs-5"></i>
        </button>
        <div>
          <h5 class="fw-bold mb-0 text-dark">Financial & Operational Reports</h5>
          <small class="text-muted">Generate detailed transaction audit reports and export data.</small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/api/reports.php?action=export_csv&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&payment_method=<?= urlencode($paymentMethod) ?>&status=<?= urlencode($status) ?>" class="btn btn-success rounded-pill px-3 py-2 fw-semibold btn-sm">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
        </a>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold btn-sm" onclick="window.print()">
          <i class="bi bi-printer me-1"></i> Print Report
        </button>
      </div>
    </header>

    <div class="app-content">
      
      <div class="card card-custom p-4 mb-4 shadow-sm bg-white no-print">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-funnel text-primary me-1"></i> Report Filters</h6>
        <form action="reports.php" method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="all" <?= $paymentMethod === 'all' ? 'selected' : '' ?>>All Methods</option>
              <option value="Cash" <?= $paymentMethod === 'Cash' ? 'selected' : '' ?>>Cash Only</option>
              <option value="GCash" <?= $paymentMethod === 'GCash' ? 'selected' : '' ?>>GCash Only</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Order Status</label>
            <select name="status" class="form-select">
              <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
              <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
              <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
              <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold">
              <i class="bi bi-filter me-1"></i> Apply Filters
            </button>
          </div>
        </form>
      </div>

      
      <div class="card card-custom p-4 mb-4 shadow-sm bg-white border-top border-4 border-primary">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
          <div>
            <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars(getSystemSetting('shop_name', 'VP Bubbles Laundry Shop')) ?></h4>
            <div class="text-muted small">Generated Report: <?= formatDate($startDate) ?> to <?= formatDate($endDate) ?></div>
          </div>
          <div class="text-end">
            <span class="badge bg-primary px-3 py-2 fs-6"><?= count($reportOrders) ?> Total Orders</span>
          </div>
        </div>

        <div class="row g-3 text-center border-top pt-3">
          <div class="col-6 col-md-3">
            <small class="text-muted text-uppercase fw-bold">Total Paid Revenue</small>
            <div class="fs-4 fw-extrabold text-success mt-1">₱ <?= number_format($totalRevenue, 2) ?></div>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted text-uppercase fw-bold">Total Processed KG</small>
            <div class="fs-4 fw-extrabold text-primary mt-1"><?= number_format($totalWeight, 1) ?> KG</div>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted text-uppercase fw-bold">Paid Transactions</small>
            <div class="fs-4 fw-extrabold text-dark mt-1"><?= $paidCount ?> Orders</div>
          </div>
          <div class="col-6 col-md-3">
            <small class="text-muted text-uppercase fw-bold">Pending Payments</small>
            <div class="fs-4 fw-extrabold text-warning mt-1"><?= $pendingCount ?> Orders</div>
          </div>
        </div>
      </div>

      
      <div class="card card-custom shadow-sm mb-4">
        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Order No.</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Weight</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($reportOrders)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    No records found for the selected period.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($reportOrders as $r): ?>
                  <tr>
                    <td><strong><?= $r['order_number'] ?></strong></td>
                    <td>
                      <div><?= htmlspecialchars($r['customer_name']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($r['customer_phone']) ?></small>
                    </td>
                    <td><span class="badge badge-service"><?= htmlspecialchars($r['service_title']) ?></span></td>
                    <td><?= $r['actual_weight'] !== null ? $r['actual_weight'] . ' KG' : ($r['estimated_weight'] ? $r['estimated_weight'] . ' KG (Est.)' : '-') ?></td>
                    <td><strong>₱ <?= number_format($r['total_amount'], 2) ?></strong></td>
                    <td><?= getPaymentBadge($r['payment_status'], $r['payment_method'], (float)($r['amount_paid'] ?? 0), (float)$r['total_amount']) ?></td>
                    <td><?= getStatusBadge($r['order_status']) ?></td>
                    <td><small class="text-muted"><?= formatDate($r['created_at']) ?></small></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
