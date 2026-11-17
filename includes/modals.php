<?php

$db = getDB();
$servicesList = $db->query("SELECT * FROM services WHERE status = 'active' ORDER BY price_per_kg ASC")->fetchAll();
$detergentPrice = (float)getSystemSetting('detergent_price', '10.00');
?>

<div class="modal fade modal-custom" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="newOrderModalLabel">
          <i class="bi bi-plus-circle-fill text-primary me-2"></i> Create New Laundry Order
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="newOrderForm">
        <div class="modal-body">
          <div class="row g-3">
            
            <div class="col-12">
              <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                <i class="bi bi-person-lines-fill me-1"></i> Customer Information (No registration needed)
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
              <input type="text" name="customer_name" class="form-control" placeholder="e.g. Juan Dela Cruz" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Contact / Mobile Number <span class="text-danger">*</span></label>
              <input type="tel" name="customer_phone" class="form-control" placeholder="e.g. 09171234567" required>
              <small class="text-muted">Used by customer to track laundry status.</small>
            </div>
            
            <div class="col-12 mt-3">
              <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h6 class="fw-bold text-primary mb-0">
                  <i class="bi bi-basket-fill me-1"></i> Select Laundry Services (Combine Multiple) <span class="text-danger">*</span>
                </h6>
                <small class="text-muted">Select all services needed</small>
              </div>
              <div class="row g-2" id="serviceSelectionGrid">
                <?php foreach ($servicesList as $svc): ?>
                  <div class="col-md-6">
                    <label class="service-select-card p-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer w-100 mb-0" for="svc_<?= $svc['id'] ?>">
                      <div class="d-flex align-items-center gap-2">
                        <input class="form-check-input service-check-item mt-0" type="checkbox" name="service_ids[]" value="<?= $svc['id'] ?>" id="svc_<?= $svc['id'] ?>" data-price="<?= $svc['price_per_kg'] ?>" data-name="<?= htmlspecialchars($svc['name']) ?>" data-type="<?= $svc['unit_type'] ?>" data-label="<?= htmlspecialchars($svc['unit_label']) ?>">
                        <div>
                          <div class="fw-bold text-dark service-name-title"><?= htmlspecialchars($svc['name']) ?></div>
                          <small class="text-muted"><?= htmlspecialchars($svc['unit_label']) ?></small>
                        </div>
                      </div>
                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 fs-6 fw-bold">
                        ₱<?= number_format($svc['price_per_kg'], 2) ?>
                      </span>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Estimated Weight (KG) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" step="0.1" min="0.5" max="50" name="estimated_weight" id="order_estimated_weight" class="form-control" value="5.0" required>
                <span class="input-group-text">KG (Max 12kg/load)</span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Pickup Date <span class="text-danger">*</span></label>
              <input type="date" name="pickup_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Pickup / Target Time <span class="text-danger">*</span></label>
              <select name="pickup_time" class="form-select" required>
                <option value="08:00 AM">08:00 AM</option>
                <option value="09:00 AM">09:00 AM</option>
                <option value="10:00 AM">10:00 AM</option>
                <option value="11:00 AM">11:00 AM</option>
                <option value="01:00 PM">01:00 PM</option>
                <option value="02:00 PM">02:00 PM</option>
                <option value="03:00 PM">03:00 PM</option>
                <option value="04:00 PM">04:00 PM</option>
                <option value="05:00 PM">05:00 PM</option>
                <option value="06:00 PM">06:00 PM</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Detergent / Fabric Conditioner</label>
              <div class="input-group">
                <input type="number" min="0" max="20" name="detergent_qty" id="order_detergent_qty" class="form-control" value="1" data-price="<?= $detergentPrice ?>">
                <span class="input-group-text">Packs (₱<?= number_format($detergentPrice, 2) ?> each)</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Payment Method</label>
              <select name="payment_method" class="form-select">
                <option value="Cash">Cash on Dropoff/Pickup</option>
                <option value="GCash">GCash Payment</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Special Instructions / Notes</label>
              <textarea name="special_instructions" class="form-control" rows="2" placeholder="e.g., Separate colored clothes, extra fabric conditioner, urgent completion"></textarea>
            </div>

            
            <div class="col-12">
              <div class="card bg-light border-primary border-opacity-25 p-3 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-bold text-dark">Estimated Total Amount:</div>
                    <small class="text-muted">Calculated using base service rate + detergent.</small>
                  </div>
                  <div class="fs-4 fw-extrabold text-primary" id="order_price_preview">₱ 0.00</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">
            <i class="bi bi-check2-circle me-1"></i> Create Order
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-custom" id="updateWeightModal" tabindex="-1" aria-labelledby="updateWeightModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="updateWeightModalLabel">
          <i class="bi bi-speedometer2 text-info me-2"></i> Record Actual Laundry Weight
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="updateWeightForm">
        <input type="hidden" name="order_id" id="modal_weight_order_id">
        <div class="modal-body">
          <div class="alert alert-info py-2 d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <div>
              Updating weight for Order <strong id="modal_weight_order_num">ORD-2026-XXXX</strong>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Service Rate:</label>
            <div class="fw-bold text-primary fs-5" id="modal_weight_unit_price">₱ 40.00 / KG</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Actual Weighed Weight (KG) <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" step="0.05" min="0.1" max="100" name="actual_weight" id="modal_actual_weight" class="form-control form-control-lg fw-bold" placeholder="0.00" required>
              <span class="input-group-text">KG</span>
            </div>
            <small class="text-muted">Enter exact weight measured on the digital scale upon receipt.</small>
          </div>

          <div class="card bg-light p-3 border-0 rounded-3">
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-semibold">Recalculated Total:</span>
              <span class="fs-4 fw-bold text-success" id="modal_weight_calc_preview">₱ 0.00</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">
            <i class="bi bi-check-lg me-1"></i> Save & Recalculate
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-custom" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="updateStatusModalLabel">
          <i class="bi bi-arrow-repeat text-warning me-2"></i> Update Laundry Status
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="updateStatusForm">
        <input type="hidden" name="order_id" id="modal_status_order_id">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Order Number:</label>
            <div class="fs-6 fw-bold text-dark" id="modal_status_order_num">ORD-2026-XXXX</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Next Stage / Status <span class="text-danger">*</span></label>
            <select name="order_status" id="modal_order_status" class="form-select form-select-lg fw-semibold" required>
              <option value="Pending">⏳ Pending (Order Intake / Dropped Off)</option>
              <option value="Washing">🌊 Washing in Progress</option>
              <option value="Drying">💨 Drying in Progress</option>
              <option value="Folding">🧺 Folding & Packaging</option>
              <option value="Ready">✅ Ready for Customer Pick-up</option>
              <option value="Completed">🎉 Completed / Picked Up by Customer</option>
              <option value="Cancelled">❌ Cancelled</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Status Remarks / Machine # / Staff Note</label>
            <input type="text" name="remarks" class="form-control" placeholder="e.g. Washer #2 running 45min cycle, folded neatly in plastic bag #4">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">
            <i class="bi bi-check-circle me-1"></i> Update Status
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-custom" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold" id="cancelOrderModalLabel">
          <i class="bi bi-exclamation-diamond-fill me-2"></i> Cancel Laundry Order
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="cancelOrderForm">
        <input type="hidden" name="order_id" id="modal_cancel_order_id">
        <div class="modal-body">
          <div class="alert alert-warning py-2 small">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Are you sure you want to cancel order <strong id="modal_cancel_order_num">ORD-2026-XXXX</strong>? This will be recorded in the Cancelled Archive.
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
            <textarea name="cancellation_reason" id="modal_cancel_reason" class="form-control" rows="3" placeholder="State reason (e.g., Customer requested cancellation, unreachable phone number, duplicate booking)" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Keep Order</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4">
            <i class="bi bi-x-circle me-1"></i> Confirm Cancellation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-custom" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="paymentModalLabel">
          <i class="bi bi-cash-coin text-success me-2"></i> Process Order Payment
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="paymentModalForm">
        <input type="hidden" name="order_id" id="modal_pay_order_id">
        <input type="hidden" id="modal_pay_total_raw" value="0">
        <input type="hidden" id="modal_pay_already_paid_raw" value="0">

        <div class="modal-body">
          
          <div class="card bg-light border-0 p-3 mb-3 rounded-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted small text-uppercase fw-bold">Order Number</span>
              <strong class="text-primary font-monospace" id="modal_pay_order_num">ORD-2026-XXXX</strong>
            </div>
            <div class="row g-2 text-center pt-2 border-top">
              <div class="col-4">
                <small class="text-muted d-block" style="font-size: 0.72rem;">Total Bill</small>
                <strong class="text-dark fs-6" id="modal_pay_total_display">₱ 0.00</strong>
              </div>
              <div class="col-4 border-start border-end">
                <small class="text-muted d-block" style="font-size: 0.72rem;">Already Paid</small>
                <strong class="text-success fs-6" id="modal_pay_paid_display">₱ 0.00</strong>
              </div>
              <div class="col-4">
                <small class="text-muted d-block" style="font-size: 0.72rem;">Balance Due</small>
                <strong class="text-danger fs-6" id="modal_pay_balance_display">₱ 0.00</strong>
              </div>
            </div>
          </div>

          
          <div class="mb-3">
            <label class="form-label fw-semibold">Payment Option <span class="text-danger">*</span></label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="pay_option" id="pay_option_full" value="full" checked>
              <label class="btn btn-outline-success" for="pay_option_full">
                <i class="bi bi-check2-circle me-1"></i> Full Payment
              </label>

              <input type="radio" class="btn-check" name="pay_option" id="pay_option_partial" value="partial">
              <label class="btn btn-outline-warning" for="pay_option_partial">
                <i class="bi bi-pie-chart me-1"></i> Partial Payment
              </label>
            </div>
          </div>

          
          <div class="mb-3">
            <label class="form-label fw-semibold">Amount to Pay (₱) <span class="text-danger">*</span></label>
            <div class="input-group input-group-lg">
              <span class="input-group-text bg-white fw-bold text-primary">₱</span>
              <input type="number" step="0.01" min="0.01" name="amount" id="modal_pay_amount" class="form-control fw-bold text-primary" placeholder="0.00" required>
            </div>
            <div class="d-flex justify-content-between mt-1 px-1">
              <small class="text-muted" id="modal_pay_remaining_hint">Remaining Balance After Payment: <strong id="modal_pay_new_balance" class="text-success">₱0.00</strong></small>
            </div>
          </div>

          
          <div class="mb-3">
            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
            <select name="payment_method" id="modal_pay_method" class="form-select fw-semibold" required>
              <option value="Cash">💵 Cash on Hand</option>
              <option value="GCash">📱 GCash E-Wallet</option>
            </select>
          </div>

          
          <div id="gcash_reference_group" class="d-none">
            <div class="alert alert-info py-2 small mb-3">
              <div class="fw-bold mb-1"><i class="bi bi-qr-code me-1"></i> VP Bubbles GCash Details:</div>
              <div><strong>Account Name:</strong> <?= htmlspecialchars(getSystemSetting('gcash_name', 'MARIA PAZ LAMATAN')) ?></div>
              <div><strong>GCash Number:</strong> <?= htmlspecialchars(getSystemSetting('gcash_number', '09540888145')) ?></div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">GCash Reference Number <span class="text-danger">*</span></label>
              <input type="text" name="reference_number" id="modal_pay_reference" class="form-control" placeholder="e.g. 902837461928">
              <small class="text-muted">Enter the 10-13 digit GCash reference number from customer receipt.</small>
            </div>
          </div>

          
          <div class="mb-2">
            <label class="form-label fw-semibold">Payment Notes (Optional)</label>
            <input type="text" name="notes" id="modal_pay_notes" class="form-control" placeholder="e.g. 50% downpayment / Paid via counter">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success rounded-pill px-4">
            <i class="bi bi-check2-all me-1"></i> Save Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-custom" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="viewOrderModalLabel">
          <i class="bi bi-receipt-cutoff text-primary me-2"></i> Order Summary & Receipt
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="view_order_details_content">
        
      </div>
      <div class="modal-footer justify-content-between no-print">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary-gradient rounded-pill px-4" onclick="printReceiptModal()">
          <i class="bi bi-printer me-1"></i> Print Receipt
        </button>
      </div>
    </div>
  </div>
</div>
