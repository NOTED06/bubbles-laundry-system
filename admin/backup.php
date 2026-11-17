<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'backup';
$pageTitle = 'Database Backup & Recovery';
$db = getDB();

$backups = $db->query("
    SELECT b.*, u.fullname as creator_name
    FROM backups b
    LEFT JOIN users u ON b.created_by = u.id
    ORDER BY b.id DESC
")->fetchAll();

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
          <h5 class="fw-bold mb-0 text-dark">Database Backup & Disaster Recovery</h5>
          <small class="text-muted">Safeguard laundry transactions and restore data during system loss.</small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#restoreModal">
          <i class="bi bi-upload me-1"></i> Restore SQL Backup
        </button>
        <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" id="btnCreateBackup">
          <i class="bi bi-download me-1"></i> Create Backup Now
        </button>
      </div>
    </header>

    <div class="app-content">
      
      <div class="alert alert-info border-info d-flex align-items-center gap-3 p-3 rounded-3 mb-4">
        <div class="stat-icon bg-info bg-opacity-25 text-info rounded-circle" style="width: 44px; height: 44px; font-size: 1.4rem;">
          <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div>
          <h6 class="fw-bold mb-0">System Disaster Recovery Protection</h6>
          <small>
            Backups contain complete table schemas and all records (Users, Customers, Services, Orders, Payments, and Status Logs). Store backups in safe cloud storage or external drives regularly.
          </small>
        </div>
      </div>

      
      <div class="card card-custom shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-database-check text-primary me-2"></i> Generated Database Backups</h6>
          <span class="badge bg-primary rounded-pill"><?= count($backups) ?> Backup Files</span>
        </div>

        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Backup File Name</th>
                <th>File Size</th>
                <th>Created By</th>
                <th>Date & Time</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="backupTableBody">
              <?php if (empty($backups)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-database-slash fs-1 d-block mb-2 text-muted opacity-50"></i>
                    No database backups generated yet. Click <strong>"Create Backup Now"</strong> above!
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($backups as $b): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-code text-primary fs-4"></i>
                        <strong class="text-dark"><?= htmlspecialchars($b['filename']) ?></strong>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= htmlspecialchars($b['filesize'] ?? 'N/A') ?></span>
                    </td>
                    <td>
                      <small class="text-muted"><?= htmlspecialchars($b['creator_name'] ?? 'System') ?></small>
                    </td>
                    <td>
                      <small class="text-muted"><?= formatDateTime($b['created_at']) ?></small>
                    </td>
                    <td class="text-end">
                      <a href="<?= BASE_URL ?>/api/backup.php?action=download&file=<?= urlencode($b['filename']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                        <i class="bi bi-download me-1"></i> Download
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger btn-delete-backup rounded-pill px-2" data-id="<?= $b['id'] ?>" data-name="<?= htmlspecialchars($b['filename']) ?>">
                        <i class="bi bi-trash"></i>
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

<div class="modal fade modal-custom" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold" id="restoreModalLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> Restore Database from Backup
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="restoreForm" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="alert alert-warning py-2 small mb-3">
            <i class="bi bi-exclamation-circle-fill me-1"></i> <strong>Warning:</strong> Restoring a backup will overwrite current database tables with the contents of the uploaded SQL file. Proceed with caution.
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Select SQL Backup File (.sql) <span class="text-danger">*</span></label>
            <input type="file" name="backup_file" id="restore_file" class="form-control" accept=".sql" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4" id="btnConfirmRestore">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Start Restore
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<HTML
<script>
document.getElementById('btnCreateBackup')?.addEventListener('click', async function () {
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Backing up...';

  const res = await fetch(`\${window.BASE_URL}/api/backup.php?action=create_backup`, { method: 'POST' });
  const data = await res.json();

  this.disabled = false;
  this.innerHTML = '<i class="bi bi-download me-1"></i> Create Backup Now';

  if (data.success) {
    Toast.success(data.message, 'Backup Complete');
    setTimeout(() => window.location.reload(), 1000);
  } else {
    Toast.error(data.message, 'Backup Failed');
  }
});

document.getElementById('restoreForm')?.addEventListener('submit', async function (e) {
  e.preventDefault();
  if (!confirm('Are you absolutely sure you want to restore this database? Current records will be replaced.')) {
    return;
  }

  const submitBtn = document.getElementById('btnConfirmRestore');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Restoring Database...';

  const formData = new FormData(this);
  const res = await fetch(`\${window.BASE_URL}/api/backup.php?action=restore`, {
    method: 'POST',
    body: formData
  });
  const data = await res.json();

  submitBtn.disabled = false;
  submitBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Start Restore';

  if (data.success) {
    Toast.success(data.message, 'Restored Successfully');
    bootstrap.Modal.getInstance(document.getElementById('restoreModal'))?.hide();
    setTimeout(() => window.location.reload(), 1200);
  } else {
    Toast.error(data.message, 'Restore Failed');
  }
});

document.querySelectorAll('.btn-delete-backup').forEach(btn => {
  btn.addEventListener('click', async function () {
    const id = this.getAttribute('data-id');
    const name = this.getAttribute('data-name');
    if (confirm(`Delete backup file "\${name}"?`)) {
      const formData = new FormData();
      formData.append('backup_id', id);
      const res = await fetch(`\${window.BASE_URL}/api/backup.php?action=delete`, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        Toast.success(data.message, 'Deleted');
        setTimeout(() => window.location.reload(), 800);
      } else {
        Toast.error(data.message, 'Error');
      }
    }
  });
});
</script>
HTML;

require_once __DIR__ . '/../includes/footer.php';
?>
