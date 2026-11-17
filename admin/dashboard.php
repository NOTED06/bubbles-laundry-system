<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'dashboard';
$pageTitle = 'Admin Executive Dashboard';
$db = getDB();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$weekStart = date('Y-m-d', strtotime('monday this week'));

$todayRevStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('Paid', 'Partial') AND DATE(paid_at) = ?");
$todayRevStmt->execute([$today]);
$todayRevenue = (float)$todayRevStmt->fetchColumn();

$weekRevStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('Paid', 'Partial') AND DATE(paid_at) >= ?");
$weekRevStmt->execute([$weekStart]);
$weekRevenue = (float)$weekRevStmt->fetchColumn();

$monthRevStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('Paid', 'Partial') AND DATE(paid_at) >= ?");
$monthRevStmt->execute([$monthStart]);
$monthRevenue = (float)$monthRevStmt->fetchColumn();

$allTimeRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('Paid', 'Partial')")->fetchColumn();

$gcashRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('Paid', 'Partial') AND payment_method = 'GCash'")->fetchColumn();
$cashRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('Paid', 'Partial') AND payment_method = 'Cash'")->fetchColumn();

$totalOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$completedOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Completed'")->fetchColumn();
$pendingOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'")->fetchColumn();
$cancelledOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Cancelled'")->fetchColumn();

$chartDays = [];
$chartVolume = [];
$chartRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartDays[] = date('M d', strtotime($d));
    
    $volStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
    $volStmt->execute([$d]);
    $chartVolume[] = (int)$volStmt->fetchColumn();

    $revStmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'Paid' AND DATE(created_at) = ?");
    $revStmt->execute([$d]);
    $chartRevenue[] = (float)$revStmt->fetchColumn();
}

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
          <h5 class="fw-bold mb-0 text-dark">Executive Analytics & Revenue</h5>
          <small class="text-muted"><i class="bi bi-shield-check text-primary"></i> Administrator Management Hub &bull; <?= date('F d, Y') ?></small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold btn-sm">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i> Reports
        </a>
        <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newOrderModal">
          <i class="bi bi-plus-lg me-1"></i> New Order
        </button>
      </div>
    </header>

    <div class="app-content">

      
      <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
          <div class="stat-card border-top border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Today's Revenue</span>
                <h3 class="fw-extrabold text-success mt-1 mb-0">₱ <?= number_format($todayRevenue, 2) ?></h3>
                <small class="text-muted">Paid transactions today</small>
              </div>
              <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-cash-stack"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="stat-card border-top border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">This Week's Revenue</span>
                <h3 class="fw-extrabold text-primary mt-1 mb-0">₱ <?= number_format($weekRevenue, 2) ?></h3>
                <small class="text-muted">Since Monday</small>
              </div>
              <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-calendar-week"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="stat-card border-top border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Monthly Revenue</span>
                <h3 class="fw-extrabold text-info mt-1 mb-0">₱ <?= number_format($monthRevenue, 2) ?></h3>
                <small class="text-muted"><?= date('F Y') ?></small>
              </div>
              <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-graph-up-arrow"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="stat-card border-top border-4 border-dark">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-bold text-uppercase">Total Lifetime Sales</span>
                <h3 class="fw-extrabold text-dark mt-1 mb-0">₱ <?= number_format($allTimeRevenue, 2) ?></h3>
                <small class="text-muted"><?= $totalOrdersCount ?> Total Orders</small>
              </div>
              <div class="stat-icon bg-dark bg-opacity-10 text-dark">
                <i class="bi bi-wallet2"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="row g-4 mb-4">
        
        <div class="col-lg-8">
          <div class="card card-custom p-4 shadow-sm h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i> 7-Day Performance Trend</h6>
              <span class="badge bg-light text-dark border">Daily Orders & Income</span>
            </div>
            <div style="height: 280px; position: relative;">
              <canvas id="performanceChart"></canvas>
            </div>
          </div>
        </div>

        
        <div class="col-lg-4">
          <div class="card card-custom p-4 shadow-sm h-100 bg-white">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-pie-chart-fill text-info me-2"></i> Payment Breakdown</h6>
            <div style="height: 220px; position: relative;" class="d-flex align-items-center justify-content-center">
              <canvas id="paymentPieChart"></canvas>
            </div>
            <div class="row text-center mt-3 pt-2 border-top g-2">
              <div class="col-6">
                <small class="text-muted d-block">GCash (<?= $allTimeRevenue > 0 ? round(($gcashRevenue / $allTimeRevenue) * 100) : 0 ?>%)</small>
                <strong class="text-primary">₱ <?= number_format($gcashRevenue, 2) ?></strong>
              </div>
              <div class="col-6">
                <small class="text-muted d-block">Cash (<?= $allTimeRevenue > 0 ? round(($cashRevenue / $allTimeRevenue) * 100) : 0 ?>%)</small>
                <strong class="text-success">₱ <?= number_format($cashRevenue, 2) ?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="card card-custom shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i> Recent Order Transactions</h6>
          <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Orders</a>
        </div>
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
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $recentOrders = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
              foreach ($recentOrders as $row):
              ?>
                <tr>
                  <td><strong class="text-primary"><?= $row['order_number'] ?></strong></td>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($row['customer_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($row['customer_phone']) ?></small>
                  </td>
                  <td><span class="badge badge-service"><?= htmlspecialchars($row['service_name']) ?></span></td>
                  <td><?= $row['actual_weight'] ? $row['actual_weight'] . ' KG' : ($row['estimated_weight'] ? $row['estimated_weight'] . ' KG (Est.)' : '-') ?></td>
                  <td><strong>₱ <?= number_format($row['total_amount'], 2) ?></strong></td>
                  <td><?= getPaymentBadge($row['payment_status'], $row['payment_method']) ?></td>
                  <td><?= getStatusBadge($row['order_status']) ?></td>
                  <td><small class="text-muted"><?= formatDate($row['created_at']) ?></small></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-view-order" data-order-id="<?= $row['id'] ?>">
                      <i class="bi bi-eye"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
$chartDaysJson = json_encode($chartDays);
$chartVolumeJson = json_encode($chartVolume);
$chartRevenueJson = json_encode($chartRevenue);
$gcashRevVal = $gcashRevenue;
$cashRevVal = $cashRevenue;

$extraScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
  
  const ctxPerf = document.getElementById('performanceChart')?.getContext('2d');
  if (ctxPerf) {
    new Chart(ctxPerf, {
      type: 'bar',
      data: {
        labels: {$chartDaysJson},
        datasets: [
          {
            label: 'Daily Revenue (₱)',
            data: {$chartRevenueJson},
            backgroundColor: 'rgba(2, 132, 199, 0.25)',
            borderColor: '#0284c7',
            borderWidth: 2,
            borderRadius: 6,
            yAxisID: 'y'
          },
          {
            type: 'line',
            label: 'Order Volume',
            data: {$chartVolumeJson},
            borderColor: '#10b981',
            backgroundColor: '#10b981',
            tension: 0.3,
            yAxisID: 'y1'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            ticks: {
              callback: val => '₱' + val
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            grid: { drawOnChartArea: false }
          }
        }
      }
    });
  }

  
  const ctxPie = document.getElementById('paymentPieChart')?.getContext('2d');
  if (ctxPie) {
    new Chart(ctxPie, {
      type: 'doughnut',
      data: {
        labels: ['GCash', 'Cash'],
        datasets: [{
          data: [{$gcashRevVal}, {$cashRevVal}],
          backgroundColor: ['#0284c7', '#10b981'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        },
        cutout: '65%'
      }
    });
  }
});
</script>
HTML;

require_once __DIR__ . '/../includes/modals.php';
require_once __DIR__ . '/../includes/footer.php';
?>
