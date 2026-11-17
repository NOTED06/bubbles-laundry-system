<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireStaffOrAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'customers';
$pageTitle = 'Customer Directory';
$db = getDB();

$search = trim($_GET['q'] ?? '');
$where = "";
$params = [];

if (!empty($search)) {
    $where = "WHERE c.name LIKE ? OR c.phone LIKE ? OR c.address LIKE ?";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$stmt = $db->prepare("
    SELECT 
        c.*,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.payment_status = 'Paid' THEN o.total_amount ELSE 0 END), 0) as lifetime_spent,
        MAX(o.created_at) as last_order_date
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
    {$where}
    GROUP BY c.id
    ORDER BY total_orders DESC, c.id DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

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
          <h5 class="fw-bold mb-0 text-dark">Customer Profiles & History</h5>
          <small class="text-muted">Recorded customer database (No customer accounts required).</small>
        </div>
      </div>

      <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#newOrderModal">
        <i class="bi bi-plus-lg me-1"></i> New Order
      </button>
    </header>

    <div class="app-content">
      
      <div class="card card-custom p-3 mb-4 shadow-sm bg-white">
        <form action="customers.php" method="GET" class="d-flex gap-2">
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="q" class="form-control" placeholder="Search customer name, contact phone, or address..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <button type="submit" class="btn btn-primary px-4 fw-semibold">Search</button>
          <?php if (!empty($search)): ?>
            <a href="customers.php" class="btn btn-outline-secondary">Clear</a>
          <?php endif; ?>
        </form>
      </div>

      
      <div class="card card-custom shadow-sm mb-4">
        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Customer Name</th>
                <th>Contact Phone</th>
                <th>Address</th>
                <th>Total Orders</th>
                <th>Lifetime Spent</th>
                <th>Last Transaction</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($customers)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-people fs-1 d-block mb-2 text-muted opacity-50"></i>
                    No customer records found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($customers as $c): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                          <?= strtoupper(substr($c['name'], 0, 1)) ?>
                        </div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                      </div>
                    </td>

                    <td>
                      <a href="tel:<?= htmlspecialchars($c['phone']) ?>" class="text-muted text-decoration-none">
                        <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($c['phone']) ?>
                      </a>
                    </td>

                    <td>
                      <small class="text-muted d-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($c['address']) ?>">
                        <?= htmlspecialchars($c['address']) ?>
                      </small>
                    </td>

                    <td>
                      <span class="badge bg-light text-dark border px-3 py-1"><?= $c['total_orders'] ?> Orders</span>
                    </td>

                    <td>
                      <strong class="text-success">₱ <?= number_format($c['lifetime_spent'], 2) ?></strong>
                    </td>

                    <td>
                      <small class="text-muted"><?= formatDate($c['last_order_date']) ?></small>
                    </td>

                    <td class="text-end">
                      <a href="orders.php?q=<?= urlencode($c['phone']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-receipt me-1"></i> View Orders
                      </a>
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
