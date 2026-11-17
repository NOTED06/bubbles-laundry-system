<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'staff';
    header('Location: ' . BASE_URL . '/' . ($role === 'admin' ? 'admin' : 'staff') . '/dashboard.php');
    exit;
}

$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = attemptLogin($email, $password);
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
        header('Location: ' . $result['redirect']);
        exit;
    } else {
        $errorMsg = $result['message'];
    }
}

$pageTitle = 'Employee Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-vh-100 d-flex flex-column justify-content-center py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-5">
        
        
        <div class="text-center mb-4">
          <?php $shopLogoUrl = getShopLogoUrl(); ?>
          <a href="<?= BASE_URL ?>/index.php" class="d-inline-flex align-items-center gap-2 text-decoration-none text-white mb-2">
            <div class="sidebar-brand-icon shadow-sm flex-shrink-0" style="width: 48px; height: 48px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 2px;">
              <?php if ($shopLogoUrl): ?>
                <img src="<?= $shopLogoUrl ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 10px;">
              <?php else: ?>
                <i class="bi bi-droplet-half fs-3 text-primary"></i>
              <?php endif; ?>
            </div>
            <span class="fs-4 fw-extrabold text-white"><?= htmlspecialchars(getSystemSetting('shop_name', 'VP Bubbles Laundry')) ?></span>
          </a>
          <p class="text-white-50 small mb-0">Authorized Staff & Administrator Portal</p>
        </div>

        
        <div class="card card-custom p-4 p-md-5 shadow-2xl bg-white border-0">
          <h4 class="fw-bold text-dark mb-1">Sign In</h4>
          <p class="text-muted small mb-4">Enter your credentials to access the management dashboard.</p>

          <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger py-2 small d-flex align-items-center gap-2 mb-3">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
              <div><?= htmlspecialchars($errorMsg) ?></div>
            </div>
          <?php endif; ?>

          <form action="<?= BASE_URL ?>/login.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold text-dark">Email Address</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" name="email" id="login_email" class="form-control border-start-0" placeholder="staff@vpbubbles.com" required>
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <label class="form-label fw-semibold text-dark">Password</label>
              </div>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" name="password" id="login_password" class="form-control border-start-0 border-end-0" placeholder="••••••••" required>
                <button type="button" class="input-group-text bg-light border-start-0" id="togglePasswordBtn">
                  <i class="bi bi-eye text-muted" id="togglePasswordIcon"></i>
                </button>
              </div>
            </div>

            <div class="mt-4 mb-3">
              <button type="submit" class="btn btn-primary-gradient w-100 py-2 fw-bold shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Dashboard
              </button>
            </div>
          </form>

          
          <div class="mt-4 pt-3 border-top text-center">
            <small class="text-muted fw-bold d-block mb-2 text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Demo Quick Login</small>
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="fillDemo('admin@vpbubbles.com', 'admin123')">
                <i class="bi bi-shield-lock-fill me-1"></i> Admin
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1" onclick="fillDemo('staff@vpbubbles.com', 'staff123')">
                <i class="bi bi-person-badge-fill me-1"></i> Staff
              </button>
            </div>
          </div>
        </div>

        <div class="text-center mt-4">
          <a href="<?= BASE_URL ?>/index.php" class="text-white-50 text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Return to Customer Website
          </a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<HTML
<script>
  function fillDemo(email, pass) {
    document.getElementById('login_email').value = email;
    document.getElementById('login_password').value = pass;
    Toast.info('Demo credentials filled: ' + email, 'Auto-filled');
  }

  document.getElementById('togglePasswordBtn')?.addEventListener('click', function () {
    const input = document.getElementById('login_password');
    const icon = document.getElementById('togglePasswordIcon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
  });
</script>
HTML;
require_once __DIR__ . '/includes/footer.php';
?>
