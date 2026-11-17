<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'staff';
$pageTitle = 'Staff Accounts';
$db = getDB();

$users = $db->query("SELECT * FROM users ORDER BY role ASC, id ASC")->fetchAll();
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
          <h5 class="fw-bold mb-0 text-dark">Staff & Employee Accounts</h5>
          <small class="text-muted">Manage employee logins, reset passwords, and assign system permissions.</small>
        </div>
      </div>

      <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 fw-semibold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="prepareAddUser()">
        <i class="bi bi-person-plus-fill me-1"></i> Add Employee
      </button>
    </header>

    <div class="app-content">
      <div class="card card-custom shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0 text-dark">Active System Users</h6>
          <span class="badge bg-primary rounded-pill"><?= count($users) ?> Accounts</span>
        </div>

        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>User Full Name</th>
                <th>Email Address (Login)</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Account Status</th>
                <th>Created Date</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="rounded-circle <?= $u['role'] === 'admin' ? 'bg-danger bg-opacity-10 text-danger' : 'bg-primary bg-opacity-10 text-primary' ?> d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                        <?= strtoupper(substr($u['fullname'], 0, 1)) ?>
                      </div>
                      <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['fullname']) ?></div>
                      </div>
                    </div>
                  </td>

                  <td>
                    <code><?= htmlspecialchars($u['email']) ?></code>
                  </td>

                  <td>
                    <small class="text-muted"><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></small>
                  </td>

                  <td>
                    <span class="badge rounded-pill <?= $u['role'] === 'admin' ? 'bg-danger text-white' : 'bg-primary text-white' ?> px-3 py-1 text-uppercase">
                      <?= $u['role'] ?>
                    </span>
                  </td>

                  <td>
                    <span class="badge rounded-pill <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> px-3 py-1">
                      <?= ucfirst($u['status']) ?>
                    </span>
                  </td>

                  <td>
                    <small class="text-muted"><?= formatDate($u['created_at']) ?></small>
                  </td>

                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-primary btn-edit-user"
                        data-id="<?= $u['id'] ?>"
                        data-name="<?= htmlspecialchars($u['fullname']) ?>"
                        data-email="<?= htmlspecialchars($u['email']) ?>"
                        data-phone="<?= htmlspecialchars($u['phone'] ?? '') ?>"
                        data-role="<?= $u['role'] ?>"
                        data-status="<?= $u['status'] ?>" title="Edit Account">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <button type="button" class="btn btn-outline-warning btn-pass-user" data-id="<?= $u['id'] ?>" data-name="<?= htmlspecialchars($u['fullname']) ?>" title="Change Password">
                        <i class="bi bi-key-fill"></i>
                      </button>

                      <?php if ($u['id'] !== $currentUser['id']): ?>
                        <button type="button" class="btn btn-outline-danger btn-delete-user" data-id="<?= $u['id'] ?>" data-name="<?= htmlspecialchars($u['fullname']) ?>" title="Delete User">
                          <i class="bi bi-trash"></i>
                        </button>
                      <?php endif; ?>
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

<div class="modal fade modal-custom" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="userModalLabel">
          <i class="bi bi-person-plus text-primary me-2"></i> Add Employee Account
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="userForm">
        <input type="hidden" name="user_id" id="usr_id" value="">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="fullname" id="usr_name" class="form-control" placeholder="e.g. Maria Santos" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Email Address (Login Username) <span class="text-danger">*</span></label>
            <input type="email" name="email" id="usr_email" class="form-control" placeholder="maria@vpbubbles.com" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Phone Number</label>
            <input type="tel" name="phone" id="usr_phone" class="form-control" placeholder="09XXXXXXXXX">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">User Role</label>
              <select name="role" id="usr_role" class="form-select">
                <option value="staff">Staff Employee</option>
                <option value="admin">Administrator</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Account Status</label>
              <select name="status" id="usr_status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>

          <div class="mb-3" id="usr_pass_group">
            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" id="usr_pass" class="form-control" placeholder="Minimum 6 characters">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">
            <i class="bi bi-check-lg me-1"></i> Save Account
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-custom" id="passwordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-key-fill text-warning me-2"></i> Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="passwordForm">
        <input type="hidden" name="user_id" id="pass_usr_id">
        <div class="modal-body">
          <p class="text-muted small">Reset password for <strong id="pass_usr_name">User</strong>.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<HTML
<script>
function prepareAddUser() {
  document.getElementById('usr_id').value = '';
  document.getElementById('userModalLabel').innerHTML = '<i class="bi bi-person-plus text-primary me-2"></i> Add Employee Account';
  document.getElementById('userForm').reset();
  document.getElementById('usr_pass_group').classList.remove('d-none');
  document.getElementById('usr_pass').setAttribute('required', 'required');
}

document.querySelectorAll('.btn-edit-user').forEach(btn => {
  btn.addEventListener('click', function () {
    document.getElementById('usr_id').value = this.getAttribute('data-id');
    document.getElementById('usr_name').value = this.getAttribute('data-name');
    document.getElementById('usr_email').value = this.getAttribute('data-email');
    document.getElementById('usr_phone').value = this.getAttribute('data-phone');
    document.getElementById('usr_role').value = this.getAttribute('data-role');
    document.getElementById('usr_status').value = this.getAttribute('data-status');

    document.getElementById('usr_pass_group').classList.add('d-none');
    document.getElementById('usr_pass').removeAttribute('required');

    document.getElementById('userModalLabel').innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i> Edit Account';
    new bootstrap.Modal(document.getElementById('userModal')).show();
  });
});

document.querySelectorAll('.btn-pass-user').forEach(btn => {
  btn.addEventListener('click', function () {
    document.getElementById('pass_usr_id').value = this.getAttribute('data-id');
    document.getElementById('pass_usr_name').textContent = this.getAttribute('data-name');
    new bootstrap.Modal(document.getElementById('passwordModal')).show();
  });
});

document.getElementById('userForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const id = document.getElementById('usr_id').value;
  const action = id ? 'update' : 'create';
  const formData = new FormData(this);

  const res = await fetch(`\${window.BASE_URL}/api/staff.php?action=\${action}`, {
    method: 'POST',
    body: formData
  });
  const data = await res.json();

  if (data.success) {
    Toast.success(data.message, 'User Saved');
    bootstrap.Modal.getInstance(document.getElementById('userModal'))?.hide();
    setTimeout(() => window.location.reload(), 900);
  } else {
    Toast.error(data.message, 'Error');
  }
});

document.getElementById('passwordForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  const res = await fetch(`\${window.BASE_URL}/api/staff.php?action=change_password`, {
    method: 'POST',
    body: formData
  });
  const data = await res.json();
  if (data.success) {
    Toast.success(data.message, 'Password Changed');
    bootstrap.Modal.getInstance(document.getElementById('passwordModal'))?.hide();
  } else {
    Toast.error(data.message, 'Error');
  }
});

document.querySelectorAll('.btn-delete-user').forEach(btn => {
  btn.addEventListener('click', async function () {
    const id = this.getAttribute('data-id');
    const name = this.getAttribute('data-name');
    if (confirm(`Are you sure you want to permanently delete account for "\${name}"?`)) {
      const formData = new FormData();
      formData.append('user_id', id);
      const res = await fetch(`\${window.BASE_URL}/api/staff.php?action=delete`, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        Toast.success(data.message, 'Deleted');
        setTimeout(() => window.location.reload(), 900);
      } else {
        Toast.error(data.message, 'Delete Failed');
      }
    }
  });
});
</script>
HTML;

require_once __DIR__ . '/../includes/modals.php';
require_once __DIR__ . '/../includes/footer.php';
?>
