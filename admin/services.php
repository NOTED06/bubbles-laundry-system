<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'services';
$pageTitle = 'Services & Rates Management';
$db = getDB();

$services = $db->query("SELECT * FROM services ORDER BY id ASC")->fetchAll();
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
          <h5 class="fw-bold mb-0 text-dark">Services & Pricing Management</h5>
          <small class="text-muted">Control laundry services, rates per KG/Load, badges, and active statuses.</small>
        </div>
      </div>

      <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="prepareAddService()">
        <i class="bi bi-plus-lg me-1"></i> Add Service
      </button>
    </header>

    <div class="app-content">
      <div class="card card-custom shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0 text-dark">Configured Laundry Services</h6>
          <span class="badge bg-primary rounded-pill"><?= count($services) ?> Total Services</span>
        </div>

        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Service Name</th>
                <th>Rate / Price</th>
                <th>Unit Type</th>
                <th>Badge</th>
                <th>Icon</th>
                <th>Status</th>
                <th class="text-end">Actions (Modals)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($services as $s): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="stat-icon bg-primary bg-opacity-10 text-primary" style="width: 36px; height: 36px; font-size: 1.1rem;">
                        <i class="bi <?= htmlspecialchars($s['icon'] ?? 'bi-droplet-half') ?>"></i>
                      </div>
                      <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($s['description'] ?? '') ?></small>
                      </div>
                    </div>
                  </td>

                  <td>
                    <span class="fs-5 fw-extrabold text-primary">₱ <?= number_format($s['price_per_kg'], 2) ?></span>
                  </td>

                  <td>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['unit_label']) ?></span>
                  </td>

                  <td>
                    <span class="badge bg-info text-dark"><?= htmlspecialchars($s['badge'] ?? 'Standard') ?></span>
                  </td>

                  <td>
                    <code><?= htmlspecialchars($s['icon'] ?? 'bi-droplet-half') ?></code>
                  </td>

                  <td>
                    <span class="badge rounded-pill <?= $s['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> px-3 py-1">
                      <?= ucfirst($s['status']) ?>
                    </span>
                  </td>

                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-primary btn-edit-service" 
                        data-id="<?= $s['id'] ?>"
                        data-name="<?= htmlspecialchars($s['name']) ?>"
                        data-price="<?= $s['price_per_kg'] ?>"
                        data-type="<?= $s['unit_type'] ?>"
                        data-label="<?= htmlspecialchars($s['unit_label']) ?>"
                        data-badge="<?= htmlspecialchars($s['badge']) ?>"
                        data-icon="<?= htmlspecialchars($s['icon']) ?>"
                        data-desc="<?= htmlspecialchars($s['description']) ?>"
                        data-status="<?= $s['status'] ?>">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                      </button>

                      <button type="button" class="btn btn-outline-danger btn-delete-service" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
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

<div class="modal fade modal-custom" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="serviceModalLabel">
          <i class="bi bi-tag-fill text-primary me-2"></i> Add Laundry Service
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="serviceForm">
        <input type="hidden" name="service_id" id="svc_id" value="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="svc_name" class="form-control" placeholder="e.g. Wash & Fold" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Price Rate (₱) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.5" min="1" name="price_per_kg" id="svc_price" class="form-control fw-bold" placeholder="40.00" required>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Unit Type</label>
              <select name="unit_type" id="svc_unit_type" class="form-select">
                <option value="per_load">Per Load</option>
                <option value="per_kg">Per KG</option>
                <option value="per_item">Per Item / Piece</option>
                <option value="fixed">Fixed Price</option>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Unit Display Label</label>
              <input type="text" name="unit_label" id="svc_unit_label" class="form-control" placeholder="e.g. per Load (up to 12kg)">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Badge Tag</label>
              <input type="text" name="badge" id="svc_badge" class="form-control" placeholder="e.g. Most Popular">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Bootstrap Icon Class</label>
              <input type="text" name="icon" id="svc_icon" class="form-control" placeholder="bi-droplet-half" value="bi-droplet-half">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" id="svc_status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" id="svc_desc" class="form-control" rows="2" placeholder="Short summary of what this service covers..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-gradient rounded-pill px-4" id="svcSubmitBtn">
            <i class="bi bi-check-lg me-1"></i> Save Service
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<HTML
<script>
function prepareAddService() {
  document.getElementById('svc_id').value = '';
  document.getElementById('serviceModalLabel').innerHTML = '<i class="bi bi-plus-circle text-primary me-2"></i> Add Laundry Service';
  document.getElementById('serviceForm').reset();
  document.getElementById('svc_icon').value = 'bi-droplet-half';
  document.getElementById('svc_status').value = 'active';
}

document.querySelectorAll('.btn-edit-service').forEach(btn => {
  btn.addEventListener('click', function () {
    document.getElementById('svc_id').value = this.getAttribute('data-id');
    document.getElementById('svc_name').value = this.getAttribute('data-name');
    document.getElementById('svc_price').value = this.getAttribute('data-price');
    document.getElementById('svc_unit_type').value = this.getAttribute('data-type');
    document.getElementById('svc_unit_label').value = this.getAttribute('data-label');
    document.getElementById('svc_badge').value = this.getAttribute('data-badge');
    document.getElementById('svc_icon').value = this.getAttribute('data-icon');
    document.getElementById('svc_desc').value = this.getAttribute('data-desc');
    document.getElementById('svc_status').value = this.getAttribute('data-status');

    document.getElementById('serviceModalLabel').innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i> Edit Laundry Service';
    const modal = new bootstrap.Modal(document.getElementById('serviceModal'));
    modal.show();
  });
});

document.getElementById('serviceForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const id = document.getElementById('svc_id').value;
  const action = id ? 'update' : 'create';
  const formData = new FormData(this);

  const res = await fetch(`\${window.BASE_URL}/api/services.php?action=\${action}`, {
    method: 'POST',
    body: formData
  });
  const data = await res.json();

  if (data.success) {
    Toast.success(data.message, 'Service Saved');
    bootstrap.Modal.getInstance(document.getElementById('serviceModal'))?.hide();
    setTimeout(() => window.location.reload(), 900);
  } else {
    Toast.error(data.message, 'Error');
  }
});

document.querySelectorAll('.btn-delete-service').forEach(btn => {
  btn.addEventListener('click', async function () {
    const id = this.getAttribute('data-id');
    const name = this.getAttribute('data-name');
    if (confirm(`Are you sure you want to remove or deactivate service "\${name}"?`)) {
      const formData = new FormData();
      formData.append('service_id', id);

      const res = await fetch(`\${window.BASE_URL}/api/services.php?action=delete`, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        Toast.success(data.message, 'Updated');
        setTimeout(() => window.location.reload(), 900);
      } else {
        Toast.error(data.message, 'Action Failed');
      }
    }
  });
});
</script>
HTML;

require_once __DIR__ . '/../includes/modals.php';
require_once __DIR__ . '/../includes/footer.php';
?>
