<?php

$pageTitle = 'Home';
$activePage = 'home';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = getDB();
$services = $db->query("SELECT * FROM services WHERE status = 'active' ORDER BY price_per_kg ASC")->fetchAll();
?>

<?php if (getSystemSetting('downtime_mode', '0') === '1'): ?>
  <div class="bg-warning bg-opacity-10 border-bottom border-warning border-opacity-50 py-3 text-dark">
    <div class="container d-flex align-items-center justify-content-center gap-3">
      <span class="badge bg-warning text-dark px-3 py-1 fs-6"><i class="bi bi-wrench-adjustable me-1"></i> Maintenance Notice</span>
      <span class="small fw-semibold">
        System Maintenance / Shop Notice is active. In-person walk-ins and offline operations remain active at our shop. Contact us at <strong><?= htmlspecialchars(getSystemSetting('shop_phone', '09540888145')) ?></strong> for urgent assistance.
      </span>
    </div>
  </div>
<?php endif; ?>

<section class="py-5 bg-white border-bottom position-relative overflow-hidden">
  <div class="container py-lg-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-light border text-primary fw-bold mb-3 small">
          <i class="bi bi-stars"></i> WASH • DRY • FOLD
        </div>
        <h1 class="display-4 fw-extrabold text-navy lh-tight mb-3">
          We clean, <span class="text-gradient">you relax.</span>
        </h1>
        <p class="lead text-muted mb-4 fs-5">
          Freshness and Cleanliness Delivered with Care! Only <strong>₱40 per wash</strong> at VP Bubbles Laundry Shop.
        </p>

        <div class="d-flex flex-wrap gap-2 mb-4">
          <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill text-primary me-1"></i> Deep Clean Wash</span>
          <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill text-primary me-1"></i> Perfectly Dried</span>
          <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill text-primary me-1"></i> Neat & Tidy Fold</span>
        </div>

        
        <div class="card card-custom p-4 border-2 border-primary border-opacity-25 shadow-lg bg-white">
          <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-geo-alt-fill text-primary fs-5"></i> Track Your Laundry Status
          </h6>
          <form action="<?= BASE_URL ?>/track.php" method="GET" class="row g-2">
            <div class="col-md-5">
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-hash text-muted"></i></span>
                <input type="text" name="order_number" class="form-control border-start-0" placeholder="e.g. ORD-2026-0001" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                <input type="tel" name="customer_phone" class="form-control border-start-0" placeholder="09XXXXXXXXX" required>
              </div>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary-gradient w-100 fw-bold">
                <i class="bi bi-search me-1"></i> Track
              </button>
            </div>
          </form>
          <small class="text-muted mt-2 d-block">
            <i class="bi bi-shield-check text-success me-1"></i> No account required. Track securely using your order number and contact.
          </small>
        </div>
      </div>

      <div class="col-lg-5 text-center">
        <div class="p-4 rounded-4 shadow-xl border bg-white d-inline-block position-relative">
          <img src="<?= getShopLogoUrl() ?>" alt="VP Bubbles Laundry Shop" class="img-fluid rounded-3 shadow-sm" style="max-height: 380px; object-fit: contain;" onerror="this.src='https://via.placeholder.com/400x400?text=VP+Bubbles+Laundry'">
        </div>
      </div>
    </div>
  </div>
</section>

<section id="services" class="py-5 bg-light">
  <div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
      <h6 class="text-primary text-uppercase fw-bold letter-spacing-1">Affordable & Professional</h6>
      <h2 class="display-6 fw-bold">Services & Official Price List</h2>
      <p class="text-muted">High quality machines, premium detergents, and fast turn-around times.</p>
    </div>

    <div class="row g-4">
      <?php foreach ($services as $s): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card card-custom h-100 p-4 position-relative">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi <?= htmlspecialchars($s['icon'] ?? 'bi-droplet-half') ?> fs-4"></i>
              </div>
              <span class="badge bg-primary text-white rounded-pill px-3 py-1"><?= htmlspecialchars($s['badge'] ?? 'Standard') ?></span>
            </div>

            <h5 class="fw-bold mb-1"><?= htmlspecialchars($s['name']) ?></h5>
            <p class="text-muted small mb-3"><?= htmlspecialchars($s['description'] ?? 'Premium laundry treatment.') ?></p>

            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-baseline">
              <div>
                <span class="fs-3 fw-extrabold text-primary">₱ <?= number_format($s['price_per_kg'], 2) ?></span>
                <span class="text-muted small">/ <?= htmlspecialchars($s['unit_label']) ?></span>
              </div>
              <small class="text-muted">Max 12kg/load</small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    
    <div class="row mt-4">
      <div class="col-12">
        <div class="card bg-white border p-3 rounded-4 d-flex flex-md-row justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle fs-4">
              <i class="bi bi-tag-fill"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-0">Detergent / Fabric Conditioner Pack</h6>
              <small class="text-muted">Only <strong>₱10.00 per pack</strong> (Ariel, Tide, Downy floral scents available)</small>
            </div>
          </div>
          <a href="#estimator" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
            <i class="bi bi-calculator me-1"></i> Calculate Cost
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="estimator" class="py-5 bg-white border-top border-bottom">
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card card-custom p-4 p-md-5 border-2 border-primary border-opacity-25 shadow-lg">
          <div class="text-center mb-4">
            <div class="d-inline-block p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-2">
              <i class="bi bi-calculator-fill fs-2"></i>
            </div>
            <h3 class="fw-bold">Interactive Laundry Cost Estimator</h3>
            <p class="text-muted">Estimate your laundry bill in seconds before walking in.</p>
          </div>

          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Select Service:</label>
              <select id="est_service" class="form-select form-select-lg">
                <?php foreach ($services as $s): ?>
                  <option value="<?= $s['price_per_kg'] ?>"><?= htmlspecialchars($s['name']) ?> (₱<?= number_format($s['price_per_kg'], 2) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Estimated Weight (KG):</label>
              <div class="input-group input-group-lg">
                <input type="number" id="est_weight" class="form-control fw-bold" value="5.0" min="1" max="50" step="0.5">
                <span class="input-group-text">KG</span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Detergent / Downy Packs:</label>
              <div class="input-group input-group-lg">
                <input type="number" id="est_detergent" class="form-control fw-bold" value="1" min="0" max="10">
                <span class="input-group-text">Packs (₱10 each)</span>
              </div>
            </div>

            <div class="col-md-6 d-flex align-items-center">
              <div class="card bg-light border-0 w-100 p-3 text-center rounded-3">
                <small class="text-muted text-uppercase fw-bold">Estimated Total:</small>
                <div class="display-6 fw-extrabold text-primary" id="est_total_display">₱ 0.00</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="contact" class="py-5 bg-light">
  <div class="container py-4">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card card-custom p-4 text-center h-100">
          <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
            <i class="bi bi-clock-history fs-3"></i>
          </div>
          <h5 class="fw-bold">Operating Hours</h5>
          <p class="text-muted mb-0"><strong>Monday – Sunday</strong><br>7:00 AM – 8:00 PM</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-custom p-4 text-center h-100">
          <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-3">
            <i class="bi bi-telephone-fill fs-3"></i>
          </div>
          <h5 class="fw-bold">Contact Number</h5>
          <p class="text-muted mb-0"><strong>09540888145</strong><br><a href="mailto:lamatanmariapaz@gmail.com" class="text-muted text-decoration-none">lamatanmariapaz@gmail.com</a></p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-custom p-4 text-center h-100">
          <div class="stat-icon bg-danger bg-opacity-10 text-danger mx-auto mb-3">
            <i class="bi bi-geo-alt-fill fs-3"></i>
          </div>
          <h5 class="fw-bold">Shop Location</h5>
          <p class="text-muted mb-0"><strong>St. Jude Village, Daliao Toril</strong><br>Davao City, Philippines</p>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="bg-white border-top py-4 text-center text-muted small">
  <div class="container">
    <div>&copy; <?= date('Y') ?> <strong><?= htmlspecialchars(getSystemSetting('shop_name', 'VP Bubbles Laundry Shop')) ?></strong>. All rights reserved.</div>
    <div class="mt-1">
      <a href="<?= BASE_URL ?>/track.php" class="text-muted me-3">Track Laundry</a>
      <a href="<?= BASE_URL ?>/login.php" class="text-muted">Staff / Admin Login</a>
    </div>
  </div>
</footer>

<?php
$extraScripts = <<<HTML
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const sSelect = document.getElementById('est_service');
    const wInput = document.getElementById('est_weight');
    const dInput = document.getElementById('est_detergent');
    const totalDisplay = document.getElementById('est_total_display');

    function calc() {
      const rate = parseFloat(sSelect.value || 40);
      const weight = parseFloat(wInput.value || 1);
      const detergent = parseInt(dInput.value || 0, 10);
      const total = (rate * (weight > 0 ? weight : 1)) + (detergent * 10.00);
      totalDisplay.textContent = '₱ ' + total.toFixed(2);
    }

    if (sSelect && wInput && dInput) {
      sSelect.addEventListener('change', calc);
      wInput.addEventListener('input', calc);
      dInput.addEventListener('input', calc);
      calc();
    }
  });
</script>
HTML;
require_once __DIR__ . '/includes/footer.php';
?>
