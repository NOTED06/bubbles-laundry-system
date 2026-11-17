<?php

?>
  
  <div id="toast-container" aria-live="polite"></div>

  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  
  <script src="<?= BASE_URL ?>/assets/js/toast-notifications.js?v=<?= time() ?>"></script>

  
  <script src="<?= BASE_URL ?>/assets/js/order-crud.js?v=<?= time() ?>"></script>

  
  <script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>

  
  <?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
  <?php endif; ?>
</body>
</html>
