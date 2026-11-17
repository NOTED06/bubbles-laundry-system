<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$currentUser = getCurrentUser();
$activeMenu = 'settings';
$pageTitle = 'Shop & System Settings';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['reset_logo'])) {
        $oldLogo = getSystemSetting('shop_logo', '');
        if (!empty($oldLogo) && file_exists(ROOT_PATH . '/' . ltrim($oldLogo, '/\\'))) {
            @unlink(ROOT_PATH . '/' . ltrim($oldLogo, '/\\'));
        }
        setSystemSetting('shop_logo', '');
        $_SESSION['flash_success'] = 'Shop logo reset to default successfully!';
        header('Location: settings.php');
        exit;
    }

    
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['shop_logo']['tmp_name'];
        $fileName = $_FILES['shop_logo']['name'];
        $fileSize = $_FILES['shop_logo']['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
        if (!in_array($ext, $allowedExts)) {
            $_SESSION['flash_error'] = 'Invalid image format. Please upload PNG, JPG, WEBP, or SVG.';
            header('Location: settings.php');
            exit;
        }

        if ($fileSize > 5 * 1024 * 1024) {
            $_SESSION['flash_error'] = 'Image size exceeds 5MB limit. Please choose a smaller file.';
            header('Location: settings.php');
            exit;
        }

        $uploadDir = ROOT_PATH . '/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $newFileName = 'logo_' . time() . '.' . $ext;
        $destPath = $uploadDir . '/' . $newFileName;

        if (move_uploaded_file($fileTmp, $destPath)) {
            
            $oldLogo = getSystemSetting('shop_logo', '');
            if (!empty($oldLogo) && file_exists(ROOT_PATH . '/' . ltrim($oldLogo, '/\\')) && ROOT_PATH . '/' . ltrim($oldLogo, '/\\') !== $destPath) {
                @unlink(ROOT_PATH . '/' . ltrim($oldLogo, '/\\'));
            }
            setSystemSetting('shop_logo', 'uploads/' . $newFileName);
        }
    }

    $settingsToUpdate = [
        'shop_name' => trim($_POST['shop_name'] ?? ''),
        'shop_tagline' => trim($_POST['shop_tagline'] ?? ''),
        'shop_phone' => trim($_POST['shop_phone'] ?? ''),
        'shop_email' => trim($_POST['shop_email'] ?? ''),
        'shop_address' => trim($_POST['shop_address'] ?? ''),
        'operating_hours' => trim($_POST['operating_hours'] ?? ''),
        'gcash_number' => trim($_POST['gcash_number'] ?? ''),
        'gcash_name' => trim($_POST['gcash_name'] ?? ''),
        'detergent_price' => trim($_POST['detergent_price'] ?? '10.00'),
        'max_weight_per_load' => trim($_POST['max_weight_per_load'] ?? '12'),
        'downtime_mode' => isset($_POST['downtime_mode']) ? '1' : '0'
    ];

    foreach ($settingsToUpdate as $key => $val) {
        setSystemSetting($key, $val);
    }

    $_SESSION['flash_success'] = 'Shop profile and settings updated successfully!';
    header('Location: settings.php');
    exit;
}

$currentLogoUrl = getShopLogoUrl();
$isCustomLogo = !empty(getSystemSetting('shop_logo', ''));

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
          <h5 class="fw-bold mb-0 text-dark">Shop Profile & System Settings</h5>
          <small class="text-muted">Configure store logo, contact details, payment accounts, and operating details.</small>
        </div>
      </div>
    </header>

    <div class="app-content">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          
          
          <div class="card card-custom p-4 mb-4 shadow-sm bg-white border-top border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <h6 class="fw-bold text-primary mb-0">
                <i class="bi bi-image me-2"></i> Official Shop Logo & Branding
              </h6>
              <?php if ($isCustomLogo): ?>
                <form action="settings.php" method="POST" onsubmit="return confirm('Reset shop logo back to default logo?');" class="d-inline">
                  <input type="hidden" name="reset_logo" value="1">
                  <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default Logo
                  </button>
                </form>
              <?php endif; ?>
            </div>

            <form action="settings.php" method="POST" enctype="multipart/form-data">
              <div class="row align-items-center g-4">
                
                <div class="col-sm-4 text-center">
                  <div class="p-3 bg-light rounded-4 border d-inline-block text-center shadow-sm">
                    <div style="width: 130px; height: 130px; border-radius: 16px; overflow: hidden; background: white; border: 2px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                      <?php if ($currentLogoUrl): ?>
                        <img src="<?= $currentLogoUrl ?>" id="logo_preview_img" alt="Shop Logo" style="width: 100%; height: 100%; object-fit: contain; padding: 4px;">
                      <?php else: ?>
                        <i class="bi bi-droplet-half fs-1 text-primary" id="logo_preview_placeholder"></i>
                        <img src="" id="logo_preview_img" alt="Shop Logo" class="d-none" style="width: 100%; height: 100%; object-fit: contain; padding: 4px;">
                      <?php endif; ?>
                    </div>
                    <small class="text-muted d-block mt-2 fw-semibold">Current Active Logo</small>
                  </div>
                </div>

                
                <div class="col-sm-8">
                  <label class="form-label fw-bold text-dark mb-1">Upload New Shop Logo</label>
                  <input type="file" name="shop_logo" id="input_shop_logo" class="form-control mb-2" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                  <div class="form-text small text-muted mb-2">
                    <i class="bi bi-info-circle me-1"></i> <strong>Recommended:</strong> High-resolution PNG or WebP with transparent background (Square format: 512x512px). Max 5MB.
                  </div>
                  <div class="small text-success d-none" id="logo_file_chosen_alert">
                    <i class="bi bi-check-circle-fill me-1"></i> New logo selected! Click <strong>"Save All Settings"</strong> below to apply.
                  </div>
                </div>
              </div>

              <hr class="my-4">

              
              <h6 class="fw-bold text-primary mb-3">
                <i class="bi bi-shop me-2"></i> Store Identity & Contact Information
              </h6>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Shop Business Name</label>
                  <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars(getSystemSetting('shop_name', 'VP Bubbles Laundry Shop')) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Slogan / Tagline</label>
                  <input type="text" name="shop_tagline" class="form-control" value="<?= htmlspecialchars(getSystemSetting('shop_tagline', 'We clean, you relax.')) ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Official Contact Number</label>
                  <input type="text" name="shop_phone" class="form-control" value="<?= htmlspecialchars(getSystemSetting('shop_phone', '09540888145')) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Official Email Address</label>
                  <input type="email" name="shop_email" class="form-control" value="<?= htmlspecialchars(getSystemSetting('shop_email', 'lamatanmariapaz@gmail.com')) ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Physical Shop Address</label>
                  <input type="text" name="shop_address" class="form-control" value="<?= htmlspecialchars(getSystemSetting('shop_address', 'St. Jude Village, Daliao Toril, Davao City')) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Operating Schedule / Hours</label>
                  <input type="text" name="operating_hours" class="form-control" value="<?= htmlspecialchars(getSystemSetting('operating_hours', 'Monday – Sunday: 7:00 AM – 8:00 PM')) ?>" required>
                </div>
              </div>

            
            <div class="card card-custom p-4 mb-4 shadow-sm bg-white">
              <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                <i class="bi bi-wallet2 me-2"></i> Payment Configuration & Add-ons
              </h6>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">GCash Account Name</label>
                  <input type="text" name="gcash_name" class="form-control" value="<?= htmlspecialchars(getSystemSetting('gcash_name', 'MARIA PAZ LAMATAN (VP BUBBLES)')) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">GCash Account Number</label>
                  <input type="text" name="gcash_number" class="form-control" value="<?= htmlspecialchars(getSystemSetting('gcash_number', '09540888145')) ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Detergent / Fabric Conditioner Price (₱)</label>
                  <input type="number" step="0.5" name="detergent_price" class="form-control" value="<?= htmlspecialchars(getSystemSetting('detergent_price', '10.00')) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Max Weight per Load (KG)</label>
                  <input type="number" name="max_weight_per_load" class="form-control" value="<?= htmlspecialchars(getSystemSetting('max_weight_per_load', '12')) ?>">
                </div>
              </div>
            </div>

            
            <div class="card card-custom p-4 mb-4 shadow-sm bg-white">
              <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                <i class="bi bi-wrench-adjustable me-2"></i> System Downtime & Maintenance Mode
              </h6>

              <div class="form-check form-switch fs-5">
                <input class="form-check-input" type="checkbox" name="downtime_mode" id="downtimeSwitch" <?= getSystemSetting('downtime_mode', '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fs-6 fw-semibold text-dark ms-2" for="downtimeSwitch">
                  Enable Maintenance Notice Mode
                </label>
              </div>
              <small class="text-muted d-block mt-2">
                When enabled, displays an informational maintenance notice on public customer pages while staff can still access offline transaction entry procedures.
              </small>
            </div>

            <div class="text-end mb-4">
              <button type="submit" class="btn btn-primary-gradient rounded-pill px-5 py-2 fw-bold shadow-md">
                <i class="bi bi-check2-circle me-1"></i> Save All Settings & Logo
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const logoInput = document.getElementById('input_shop_logo');
  const previewImg = document.getElementById('logo_preview_img');
  const placeholder = document.getElementById('logo_preview_placeholder');
  const alertBox = document.getElementById('logo_file_chosen_alert');

  if (logoInput) {
    logoInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        const objectUrl = URL.createObjectURL(file);
        if (previewImg) {
          previewImg.src = objectUrl;
          previewImg.classList.remove('d-none');
        }
        if (placeholder) {
          placeholder.classList.add('d-none');
        }
        if (alertBox) {
          alertBox.classList.remove('d-none');
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
