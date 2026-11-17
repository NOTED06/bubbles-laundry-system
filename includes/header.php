<?php

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? getSystemSetting('shop_name', 'VP Bubbles Laundry Shop');
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
$flashWarning = $_SESSION['flash_warning'] ?? null;
$flashInfo = $_SESSION['flash_info'] ?? null;

unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_warning'], $_SESSION['flash_info']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(getSystemSetting('shop_tagline', 'We clean, you relax.')) ?></title>
  
  
  <?php if ($flashSuccess): ?><meta name="flash-success" content="<?= htmlspecialchars($flashSuccess) ?>"><?php endif; ?>
  <?php if ($flashError): ?><meta name="flash-error" content="<?= htmlspecialchars($flashError) ?>"><?php endif; ?>
  <?php if ($flashWarning): ?><meta name="flash-warning" content="<?= htmlspecialchars($flashWarning) ?>"><?php endif; ?>
  <?php if ($flashInfo): ?><meta name="flash-info" content="<?= htmlspecialchars($flashInfo) ?>"><?php endif; ?>

  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">

  
  <script>
    window.BASE_URL = "<?= BASE_URL ?>";
    window.SHOP_NAME = "<?= addslashes(getSystemSetting('shop_name', 'VP Bubbles Laundry Shop')) ?>";
    window.CURRENCY = "<?= CURRENCY_SYMBOL ?>";
  </script>
</head>
<body>
