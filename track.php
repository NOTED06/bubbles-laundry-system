<?php

$pageTitle = 'Track Your Laundry';
$activePage = 'track';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$initialOrderNum = htmlspecialchars(trim($_GET['order_number'] ?? ''));
$initialPhone = htmlspecialchars(trim($_GET['customer_phone'] ?? ''));
$initialOrderDataJson = 'null';

if (!empty($initialOrderNum) && !empty($initialPhone)) {
    try {
        $cleanPhone = preg_replace('/[^0-9]/', '', $initialPhone);
        $db = getDB();
        $stmt = $db->prepare("
            SELECT o.*, s.name as service_title, s.unit_label, s.badge as service_badge
            FROM orders o
            JOIN services s ON o.service_id = s.id
            WHERE UPPER(o.order_number) = UPPER(?) 
              AND REPLACE(REPLACE(REPLACE(o.customer_phone, ' ', ''), '-', ''), '+63', '0') LIKE ?
            LIMIT 1
        ");
        $searchParam = '%' . substr($cleanPhone, -9);
        $stmt->execute([$initialOrderNum, $searchParam]);
        $serverOrder = $stmt->fetch();

        if ($serverOrder) {
            $histStmt = $db->prepare("SELECT status, remarks, updated_at FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
            $histStmt->execute([$serverOrder['id']]);
            $serverHistory = $histStmt->fetchAll();

            $serverCancel = null;
            if ($serverOrder['order_status'] === 'Cancelled') {
                $cStmt = $db->prepare("SELECT reason, cancelled_at FROM cancellations WHERE order_id = ? LIMIT 1");
                $cStmt->execute([$serverOrder['id']]);
                $serverCancel = $cStmt->fetch();
            }

            $steps = [
                'Pending'          => 1,
                'Pickup Scheduled' => 1,
                'Picked Up'        => 1,
                'Washing'          => 2,
                'Drying'           => 3,
                'Folding'          => 4,
                'Ready'            => 5,
                'Completed'        => 6
            ];

            $initialOrderDataJson = json_encode([
                'order' => [
                    'id' => $serverOrder['id'],
                    'order_number' => $serverOrder['order_number'],
                    'customer_name' => $serverOrder['customer_name'],
                    'customer_phone' => $serverOrder['customer_phone'],
                    'pickup_address' => $serverOrder['pickup_address'],
                    'pickup_date' => formatDate($serverOrder['pickup_date']),
                    'pickup_time' => $serverOrder['pickup_time'],
                    'service_name' => $serverOrder['service_name'],
                    'service_badge' => $serverOrder['service_badge'],
                    'unit_label' => $serverOrder['unit_label'],
                    'price_per_kg' => (float)$serverOrder['price_per_kg'],
                    'estimated_weight' => $serverOrder['estimated_weight'] ? (float)$serverOrder['estimated_weight'] : null,
                    'actual_weight' => $serverOrder['actual_weight'] ? (float)$serverOrder['actual_weight'] : null,
                    'detergent_qty' => (int)$serverOrder['detergent_qty'],
                    'detergent_price' => (float)$serverOrder['detergent_price'],
                    'total_amount' => (float)$serverOrder['total_amount'],
                    'amount_paid' => (float)($serverOrder['amount_paid'] ?? 0),
                    'balance' => max(0, (float)$serverOrder['total_amount'] - (float)($serverOrder['amount_paid'] ?? 0)),
                    'payment_method' => $serverOrder['payment_method'],
                    'payment_status' => $serverOrder['payment_status'],
                    'gcash_reference' => $serverOrder['gcash_reference'],
                    'order_status' => $serverOrder['order_status'],
                    'special_instructions' => $serverOrder['special_instructions'],
                    'created_at' => formatDateTime($serverOrder['created_at']),
                    'updated_at' => formatDateTime($serverOrder['updated_at'])
                ],
                'current_step' => $steps[$serverOrder['order_status']] ?? 1,
                'total_steps' => 6,
                'history' => array_map(function($h) {
                    return [
                        'status' => $h['status'],
                        'remarks' => $h['remarks'],
                        'updated_at' => formatDateTime($h['updated_at'])
                    ];
                }, $serverHistory),
                'cancellation' => $serverCancel
            ]);
        }
    } catch (Exception $e) {
        
    }
}
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

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      
      <div class="card card-custom p-4 p-md-5 mb-4 shadow-lg">
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
            <i class="bi bi-geo-alt-fill fs-2"></i>
          </div>
          <h2 class="fw-bold">Track Your Laundry</h2>
          <p class="text-muted">Enter your Order Number and Contact Number to check live progress.</p>
        </div>

        <form id="trackOrderForm" class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Order Number <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-hash text-muted"></i></span>
              <input type="text" id="track_order_number" name="order_number" class="form-control form-control-lg text-uppercase" placeholder="ORD-2026-0001" value="<?= $initialOrderNum ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Contact / Mobile Number <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
              <input type="tel" id="track_customer_phone" name="customer_phone" class="form-control form-control-lg" placeholder="09XXXXXXXXX" value="<?= $initialPhone ?>" required>
            </div>
          </div>
          <div class="col-12 mt-4">
            <button type="submit" id="trackSubmitBtn" class="btn btn-primary-gradient w-100 py-3 fw-bold fs-5 shadow-sm">
              <i class="bi bi-search me-2"></i> Track Laundry Progress
            </button>
          </div>
        </form>

        <div class="text-center mt-3">
          <small class="text-muted">
            <i class="bi bi-lock-fill text-primary me-1"></i> Customer privacy protected: Contact number verification required.
          </small>
        </div>
      </div>

      
      <div id="trackingResultCard" class="d-none">
        
        <div class="card card-custom p-4 mb-4 shadow-md bg-white border-top border-4 border-primary">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 border-bottom pb-3">
            <div>
              <span class="badge bg-light text-primary border px-3 py-1 mb-1 fw-bold">ORDER STATUS</span>
              <h3 class="fw-bold mb-0 text-dark" id="res_order_number">ORD-2026-0001</h3>
              <small class="text-muted" id="res_created_at">Placed on Aug 26, 2026</small>
            </div>
            <div class="text-md-end" id="res_status_badge_container">
              
            </div>
          </div>

          
          <div id="res_cancellation_banner" class="alert alert-danger d-none mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> This order has been cancelled</h6>
            <div id="res_cancellation_reason" class="small">Reason stated here</div>
          </div>

          
          <div class="tracking-timeline mb-4" id="res_timeline_container">
            <div class="timeline-steps">
              <div class="timeline-progress-bar" id="res_timeline_bar" style="width: 0%;"></div>

              <div class="timeline-step" data-step="1">
                <div class="step-circle"><i class="bi bi-receipt"></i></div>
                <div class="step-title">Order Intake</div>
              </div>
              <div class="timeline-step" data-step="2">
                <div class="step-circle"><i class="bi bi-water"></i></div>
                <div class="step-title">Washing</div>
              </div>
              <div class="timeline-step" data-step="3">
                <div class="step-circle"><i class="bi bi-wind"></i></div>
                <div class="step-title">Drying</div>
              </div>
              <div class="timeline-step" data-step="4">
                <div class="step-circle"><i class="bi bi-layers"></i></div>
                <div class="step-title">Folding</div>
              </div>
              <div class="timeline-step" data-step="5">
                <div class="step-circle"><i class="bi bi-check-circle"></i></div>
                <div class="step-title">Ready for Pick-up</div>
              </div>
              <div class="timeline-step" data-step="6">
                <div class="step-circle"><i class="bi bi-bag-check-fill"></i></div>
                <div class="step-title">Completed</div>
              </div>
            </div>
          </div>

          
          <div class="row g-3 p-3 bg-light rounded-3 mb-4">
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-bold">Customer Name:</small>
              <div class="fw-bold text-dark" id="res_customer_name">-</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-bold">Service Selected:</small>
              <div class="fw-bold text-primary" id="res_service_name">-</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-bold">Weighed / Estimated Weight:</small>
              <div class="fw-bold text-dark" id="res_weight">-</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-bold">Total Bill:</small>
              <div class="fs-5 fw-extrabold text-success" id="res_total_amount">₱ 0.00</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-bold">Payment Status:</small>
              <div id="res_payment_badge">-</div>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-bold">Scheduled Pickup / Ready:</small>
              <div class="fw-bold text-dark" id="res_pickup_schedule">-</div>
            </div>
          </div>

          
          <div class="d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btnTrackPrintReceipt">
              <i class="bi bi-printer me-1"></i> Print Receipt
            </button>
            <a href="tel:09540888145" class="btn btn-outline-primary rounded-pill px-4">
              <i class="bi bi-telephone-fill me-1"></i> Call Laundry Shop
            </a>
          </div>
        </div>

        
        <div class="card card-custom p-4 shadow-sm bg-white">
          <h5 class="fw-bold mb-3"><i class="bi bi-clock-history text-primary me-2"></i> Real-Time Processing Log</h5>
          <ul class="list-unstyled mb-0" id="res_history_list">
            
          </ul>
        </div>
      </div>

    </div>
  </div>
</div>

<?php
$extraScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('trackOrderForm');
  const resultCard = document.getElementById('trackingResultCard');
  const submitBtn = document.getElementById('trackSubmitBtn');
  let lastTrackedData = null;

  async function performTrack() {
    const orderNum = document.getElementById('track_order_number').value.trim();
    const phone = document.getElementById('track_customer_phone').value.trim();

    if (!orderNum || !phone) return;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Searching Order...';

    let url = (window.BASE_URL || '') + '/api/track-order.php?order_number=' + encodeURIComponent(orderNum) + '&customer_phone=' + encodeURIComponent(phone);
    
    try {
      let res = await fetch(url);
      if (!res.ok) {
        url = 'api/track-order.php?order_number=' + encodeURIComponent(orderNum) + '&customer_phone=' + encodeURIComponent(phone);
        res = await fetch(url);
      }
      const data = await res.json();

      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-search me-2"></i> Track Laundry Progress';

      if (data.success && data.data) {
        Toast.success('Order found! Showing latest status.', 'Found Order');
        renderTrackingResult(data.data);
      } else {
        Toast.error(data.message || 'No order found with matching details.', 'Tracking Error');
        resultCard.classList.add('d-none');
      }
    } catch (e) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-search me-2"></i> Track Laundry Progress';
      Toast.error('Network error while connecting to tracking service.', 'Network Error');
    }
  }

  function renderTrackingResult(data) {
    lastTrackedData = data;
    const { order, current_step, total_steps, history, cancellation } = data;

    document.getElementById('res_order_number').textContent = order.order_number;
    document.getElementById('res_created_at').textContent = 'Placed on ' + order.created_at;
    document.getElementById('res_customer_name').textContent = order.customer_name;
    document.getElementById('res_service_name').textContent = order.service_name;
    document.getElementById('res_weight').textContent = order.actual_weight 
      ? `\${order.actual_weight} KG (Actual Weight)` 
      : (order.estimated_weight ? `\${order.estimated_weight} KG (Estimated)` : 'Weighing on receipt');
    document.getElementById('res_total_amount').textContent = `₱ \${order.total_amount.toFixed(2)}`;
    document.getElementById('res_pickup_schedule').textContent = `\${order.pickup_date} at \${order.pickup_time}`;

    
    let paymentBadgeHtml = '';
    if (order.payment_status === 'Paid') {
      paymentBadgeHtml = `<span class="badge bg-success px-3 py-1"><i class="bi bi-check2-all me-1"></i> Paid (\${order.payment_method})</span>`;
    } else if (order.payment_status === 'Partial') {
      paymentBadgeHtml = `<span class="badge bg-warning text-dark px-3 py-1"><i class="bi bi-pie-chart-fill me-1"></i> Partial (₱\${order.amount_paid.toFixed(2)} / ₱\${order.total_amount.toFixed(2)}) - \${order.payment_method}</span>`;
    } else {
      paymentBadgeHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1"><i class="bi bi-clock-history me-1"></i> Unpaid (\${order.payment_method})</span>`;
    }
    document.getElementById('res_payment_badge').innerHTML = paymentBadgeHtml;

    
    document.getElementById('res_status_badge_container').innerHTML = `
      <span class="fs-6 badge rounded-pill \${order.order_status === 'Completed' ? 'bg-dark' : (order.order_status === 'Cancelled' ? 'bg-danger' : 'bg-primary')} px-4 py-2">
        \${order.order_status}
      </span>
    `;

    
    const cancelBanner = document.getElementById('res_cancellation_banner');
    const timelineContainer = document.getElementById('res_timeline_container');
    if (order.order_status === 'Cancelled') {
      cancelBanner.classList.remove('d-none');
      document.getElementById('res_cancellation_reason').textContent = cancellation ? `Reason: \${cancellation.reason}` : 'Order cancelled by customer or staff.';
      timelineContainer.classList.add('opacity-50');
    } else {
      cancelBanner.classList.add('d-none');
      timelineContainer.classList.remove('opacity-50');
    }

    
    const steps = document.querySelectorAll('.timeline-step');
    steps.forEach(stepEl => {
      const stepNum = parseInt(stepEl.getAttribute('data-step'), 10);
      stepEl.classList.remove('completed', 'active');
      if (stepNum < current_step) {
        stepEl.classList.add('completed');
      } else if (stepNum === current_step && order.order_status !== 'Cancelled') {
        stepEl.classList.add('active');
      }
    });

    const progressPercent = Math.max(0, Math.min(100, ((current_step - 1) / (total_steps - 1)) * 100));
    document.getElementById('res_timeline_bar').style.width = `\${progressPercent}%`;

    
    const historyList = document.getElementById('res_history_list');
    if (history && history.length > 0) {
      historyList.innerHTML = history.map(h => `
        <li class="d-flex align-items-start gap-3 mb-3 border-bottom pb-2">
          <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px; font-size: 1rem;">
            <i class="bi bi-arrow-right-short"></i>
          </div>
          <div>
            <div class="fw-bold text-dark">\${h.status}</div>
            \${h.remarks ? `<div class="text-muted small">\${h.remarks}</div>` : ''}
            <small class="text-muted" style="font-size: 0.75rem;">\${h.updated_at}</small>
          </div>
        </li>
      `).join('');
    } else {
      historyList.innerHTML = '<li class="text-muted small">No logs recorded yet.</li>';
    }

    resultCard.classList.remove('d-none');
    resultCard.scrollIntoView({ behavior: 'smooth' });
  }

  
  function printTrackingReceipt() {
    if (!lastTrackedData || !lastTrackedData.order) {
      Toast.error('Please search an order first before printing receipt.', 'No Order Found');
      return;
    }
    const order = lastTrackedData.order;
    const totalAmount = parseFloat(order.total_amount) || 0;
    const amountPaid = parseFloat(order.amount_paid) || 0;
    const balance = Math.max(0, totalAmount - amountPaid);

    const printIframe = document.createElement('iframe');
    printIframe.style.position = 'fixed';
    printIframe.style.right = '0';
    printIframe.style.bottom = '0';
    printIframe.style.width = '0';
    printIframe.style.height = '0';
    printIframe.style.border = '0';
    document.body.appendChild(printIframe);

    const doc = printIframe.contentWindow.document;
    doc.open();
    doc.write(`
      <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="utf-8">
        <title>Official Laundry Receipt - \${order.order_number}</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>
          @page {
            margin: 6mm 4mm;
            size: auto;
          }
          body {
            font-family: 'Courier New', Courier, monospace;
            background: 
            color: 
            padding: 8px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          .receipt-paper {
            width: 100%;
            max-width: 360px;
            padding: 14px;
            border: 1px dashed 
            margin: 0 auto;
            box-sizing: border-box;
            font-size: 13px;
          }
          .receipt-divider {
            border-top: 1px dashed 
            margin: 8px 0;
          }
          .badge {
            border: 1px solid 
            color: 
            background: transparent !important;
            font-weight: bold;
            padding: 2px 6px;
          }
        </style>
      </head>
      <body>
        <div class="receipt-paper">
          <div class="text-center mb-2">
            <h5 class="fw-bold mb-0">\${window.SHOP_NAME || 'VP Bubbles Laundry Shop'}</h5>
            <small class="text-muted">Official Laundry Receipt</small>
            <div class="receipt-divider"></div>
            <div class="fs-6 fw-bold text-dark font-monospace">\${order.order_number}</div>
            <small class="text-muted">\${order.created_at}</small>
          </div>

          <div class="mb-1"><strong>Customer:</strong> \${order.customer_name}</div>
          <div class="mb-1"><strong>Contact:</strong> \${order.customer_phone}</div>
          <div class="mb-2"><strong>Address:</strong> \${order.pickup_address || 'Shop Drop-off / Walk-in'}</div>
          <div class="receipt-divider"></div>

          <div class="d-flex justify-content-between mb-1">
            <span>Service:</span>
            <span><strong>\${order.service_name}</strong></span>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span>Rate:</span>
            <span>₱ \${parseFloat(order.price_per_kg).toFixed(2)}</span>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span>Weight:</span>
            <span>\${order.actual_weight ? order.actual_weight + ' KG (Actual)' : (order.estimated_weight ? order.estimated_weight + ' KG (Est.)' : 'Weighing upon intake')}</span>
          </div>
          \${order.detergent_qty > 0 ? `
            <div class="d-flex justify-content-between mb-1">
              <span>Detergent (\${order.detergent_qty}x):</span>
              <span>₱ \${(order.detergent_qty * parseFloat(order.detergent_price || 10)).toFixed(2)}</span>
            </div>
          ` : ''}
          <div class="receipt-divider"></div>
          
          <div class="d-flex justify-content-between fs-6 fw-bold text-dark mb-1">
            <span>Total Bill:</span>
            <span>₱ \${totalAmount.toFixed(2)}</span>
          </div>
          <div class="d-flex justify-content-between fs-6 fw-bold mb-1">
            <span>Amount Paid:</span>
            <span>₱ \${amountPaid.toFixed(2)}</span>
          </div>
          <div class="d-flex justify-content-between fs-6 fw-bold mb-2">
            <span>Balance Due:</span>
            <span>\${balance > 0 ? '₱ ' + balance.toFixed(2) : '₱ 0.00 (Fully Settled)'}</span>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
            <span>Payment Status:</span>
            <span class="badge">
              \${order.payment_status} (\${order.payment_method})
            </span>
          </div>
          \${order.gcash_reference ? `<small class="d-block mt-1">GCash Ref: \${order.gcash_reference}</small>` : ''}
          
          <div class="receipt-divider"></div>
          <div class="text-center small">
            <div>Order Status: <strong>\${order.order_status}</strong></div>
            <div>Scheduled Pickup: <strong>\${order.pickup_date} at \${order.pickup_time}</strong></div>
            <div class="mt-2">Thank you for choosing VP Bubbles Laundry!</div>
          </div>
        </div>
      </body>
      </html>
    `);
    doc.close();

    setTimeout(() => {
      printIframe.contentWindow.focus();
      printIframe.contentWindow.print();
      setTimeout(() => {
        if (document.body.contains(printIframe)) {
          document.body.removeChild(printIframe);
        }
      }, 1500);
    }, 350);
  }

  document.getElementById('btnTrackPrintReceipt')?.addEventListener('click', printTrackingReceipt);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    performTrack();
  });

  
  const serverPreloadedData = {$initialOrderDataJson};
  if (serverPreloadedData) {
    renderTrackingResult(serverPreloadedData);
  } else if (document.getElementById('track_order_number').value && document.getElementById('track_customer_phone').value) {
    performTrack();
  }
});
</script>
HTML;
require_once __DIR__ . '/includes/footer.php';
?>
