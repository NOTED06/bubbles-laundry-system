

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  
  const getApiUrl = (endpoint) => {
    const base = window.BASE_URL || '';
    return `${base}/api/${endpoint}`;
  };

  
  async function fetchJson(url, options = {}) {
    try {
      const response = await fetch(url, options);
      const text = await response.text();
      try {
        return JSON.parse(text);
      } catch (jsonErr) {
        console.error('Non-JSON response received:', text);
        return { success: false, message: 'Server returned an invalid response format.' };
      }
    } catch (err) {
      console.error('Fetch error:', err);
      return { success: false, message: 'Network or server communication error.' };
    }
  }

  
  
  
  const newOrderForm = document.getElementById('newOrderForm');
  const serviceCheckboxes = document.querySelectorAll('.service-check-item');
  const weightInput = document.getElementById('order_estimated_weight');
  const detergentInput = document.getElementById('order_detergent_qty');
  const pricePreviewEl = document.getElementById('order_price_preview');

  function calculateEstimatedTotal() {
    if (!pricePreviewEl) return;
    const checked = document.querySelectorAll('.service-check-item:checked');
    
    
    document.querySelectorAll('.service-check-item').forEach((cb) => {
      const card = cb.closest('.service-select-card');
      if (card) {
        if (cb.checked) {
          card.classList.add('selected');
        } else {
          card.classList.remove('selected');
        }
      }
    });

    if (checked.length === 0) {
      pricePreviewEl.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Please check at least one service above.</span>';
      return;
    }

    let serviceSum = 0;
    let names = [];
    checked.forEach((cb) => {
      const p = parseFloat(cb.getAttribute('data-price') || 0);
      serviceSum += p;
      names.push(cb.getAttribute('data-name'));
    });

    const detergentQty = parseInt(detergentInput?.value || 0, 10);
    const detergentPrice = parseFloat(detergentInput?.getAttribute('data-price') || 10.00);
    const detergentTotal = detergentQty * detergentPrice;
    const grandTotal = serviceSum + detergentTotal;

    const summaryText = names.join(' + ') + (detergentQty > 0 ? ` + ${detergentQty}x Detergent` : '');
    pricePreviewEl.innerHTML = `₱ ${grandTotal.toFixed(2)} <span class="fs-6 fw-normal text-muted d-block mt-1" style="font-size: 0.85rem;">(${summaryText})</span>`;
  }

  if (serviceCheckboxes.length > 0) {
    serviceCheckboxes.forEach(cb => cb.addEventListener('change', calculateEstimatedTotal));
  }
  if (weightInput) {
    weightInput.addEventListener('input', calculateEstimatedTotal);
  }
  if (detergentInput) {
    detergentInput.addEventListener('input', calculateEstimatedTotal);
  }

  
  if (newOrderForm) {
    newOrderForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const checked = newOrderForm.querySelectorAll('.service-check-item:checked');
      if (checked.length === 0) {
        Toast.error('Please select at least one laundry service before creating the order.', 'Service Required');
        return;
      }

      const submitBtn = newOrderForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating Order...';

      const formData = new FormData(newOrderForm);
      const res = await fetchJson(getApiUrl('orders.php?action=create'), {
        method: 'POST',
        body: formData
      });

      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;

      if (res.success) {
        Toast.success(res.message, 'Order Created!');
        const modalEl = document.getElementById('newOrderModal');
        if (modalEl) {
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();
        }
        newOrderForm.reset();
        setTimeout(() => window.location.reload(), 1200);
      } else {
        Toast.error(res.message, 'Creation Failed');
      }
    });
  }

  
  
  
  const updateWeightForm = document.getElementById('updateWeightForm');
  const actualWeightInput = document.getElementById('modal_actual_weight');
  const weightUnitPriceEl = document.getElementById('modal_weight_unit_price');
  const weightCalcPreviewEl = document.getElementById('modal_weight_calc_preview');

  
  document.querySelectorAll('.btn-update-weight').forEach((btn) => {
    btn.addEventListener('click', function () {
      const orderId = this.getAttribute('data-order-id');
      const orderNumber = this.getAttribute('data-order-number');
      const currentWeight = this.getAttribute('data-weight') || '';
      const unitPrice = parseFloat(this.getAttribute('data-unit-price') || 0);
      const detergentQty = parseInt(this.getAttribute('data-detergent-qty') || 0);

      document.getElementById('modal_weight_order_id').value = orderId;
      document.getElementById('modal_weight_order_num').textContent = orderNumber;
      actualWeightInput.value = currentWeight;
      actualWeightInput.setAttribute('data-unit-price', unitPrice);
      actualWeightInput.setAttribute('data-detergent-qty', detergentQty);

      if (weightUnitPriceEl) {
        weightUnitPriceEl.textContent = `₱ ${unitPrice.toFixed(2)} / KG`;
      }
      calcActualWeightTotal();
    });
  });

  function calcActualWeightTotal() {
    if (!actualWeightInput || !weightCalcPreviewEl) return;
    const unitPrice = parseFloat(actualWeightInput.getAttribute('data-unit-price') || 0);
    const detergentQty = parseInt(actualWeightInput.getAttribute('data-detergent-qty') || 0);
    const weight = parseFloat(actualWeightInput.value || 0);

    const total = (weight * unitPrice) + (detergentQty * 15.00);
    weightCalcPreviewEl.textContent = `₱ ${total.toFixed(2)}`;
  }

  if (actualWeightInput) {
    actualWeightInput.addEventListener('input', calcActualWeightTotal);
  }

  if (updateWeightForm) {
    updateWeightForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const submitBtn = updateWeightForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

      const formData = new FormData(updateWeightForm);
      const res = await fetchJson(getApiUrl('orders.php?action=update_weight'), {
        method: 'POST',
        body: formData
      });

      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save & Recalculate';

      if (res.success) {
        Toast.success(res.message, 'Weight Updated');
        const modalEl = document.getElementById('updateWeightModal');
        if (modalEl) {
          bootstrap.Modal.getInstance(modalEl)?.hide();
        }
        setTimeout(() => window.location.reload(), 1000);
      } else {
        Toast.error(res.message, 'Error');
      }
    });
  }

  
  
  
  const updateStatusForm = document.getElementById('updateStatusForm');
  document.querySelectorAll('.btn-update-status').forEach((btn) => {
    btn.addEventListener('click', function () {
      const orderId = this.getAttribute('data-order-id');
      const orderNumber = this.getAttribute('data-order-number');
      const currentStatus = this.getAttribute('data-status');

      document.getElementById('modal_status_order_id').value = orderId;
      document.getElementById('modal_status_order_num').textContent = orderNumber;
      const statusSelect = document.getElementById('modal_order_status');
      if (statusSelect) {
        statusSelect.value = currentStatus;
      }
    });
  });

  if (updateStatusForm) {
    updateStatusForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const submitBtn = updateStatusForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

      const formData = new FormData(updateStatusForm);
      const res = await fetchJson(getApiUrl('status.php?action=update_status'), {
        method: 'POST',
        body: formData
      });

      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Status';

      if (res.success) {
        Toast.success(res.message, 'Status Advanced');
        const modalEl = document.getElementById('updateStatusModal');
        if (modalEl) {
          bootstrap.Modal.getInstance(modalEl)?.hide();
        }
        setTimeout(() => window.location.reload(), 1000);
      } else {
        Toast.error(res.message, 'Update Failed');
      }
    });
  }

  
  
  
  const cancelOrderForm = document.getElementById('cancelOrderForm');
  document.querySelectorAll('.btn-cancel-order').forEach((btn) => {
    btn.addEventListener('click', function () {
      const orderId = this.getAttribute('data-order-id');
      const orderNumber = this.getAttribute('data-order-number');

      document.getElementById('modal_cancel_order_id').value = orderId;
      document.getElementById('modal_cancel_order_num').textContent = orderNumber;
      const reasonEl = document.getElementById('modal_cancel_reason');
      if (reasonEl) reasonEl.value = '';
    });
  });

  if (cancelOrderForm) {
    cancelOrderForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const submitBtn = cancelOrderForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelling...';

      const formData = new FormData(cancelOrderForm);
      const res = await fetchJson(getApiUrl('status.php?action=cancel_order'), {
        method: 'POST',
        body: formData
      });

      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Confirm Cancellation';

      if (res.success) {
        Toast.warning(res.message, 'Order Cancelled');
        const modalEl = document.getElementById('cancelOrderModal');
        if (modalEl) {
          bootstrap.Modal.getInstance(modalEl)?.hide();
        }
        setTimeout(() => window.location.reload(), 1000);
      } else {
        Toast.error(res.message, 'Cancellation Failed');
      }
    });
  }

  
  
  
  const paymentForm = document.getElementById('paymentModalForm');
  const payMethodSelect = document.getElementById('modal_pay_method');
  const gcashGroup = document.getElementById('gcash_reference_group');
  const payAmountInput = document.getElementById('modal_pay_amount');
  const payOptionFull = document.getElementById('pay_option_full');
  const payOptionPartial = document.getElementById('pay_option_partial');

  function calculatePaymentLive() {
    if (!payAmountInput) return;
    const total = parseFloat(document.getElementById('modal_pay_total_raw')?.value || 0);
    const alreadyPaid = parseFloat(document.getElementById('modal_pay_already_paid_raw')?.value || 0);
    const balance = Math.max(0, total - alreadyPaid);
    const entered = parseFloat(payAmountInput.value) || 0;
    const remaining = Math.max(0, balance - entered);
    const hintEl = document.getElementById('modal_pay_new_balance');

    if (hintEl) {
      if (entered <= 0) {
        hintEl.className = 'text-muted';
        hintEl.textContent = `₱ ${balance.toFixed(2)}`;
      } else if (entered >= balance && balance > 0) {
        hintEl.className = 'text-success fw-bold';
        hintEl.textContent = '₱ 0.00 (Fully Settled / Paid in Full!)';
      } else {
        hintEl.className = 'text-danger fw-bold';
        hintEl.textContent = `₱ ${remaining.toFixed(2)} (Partial Balance Remaining)`;
      }
    }
  }

  if (payAmountInput) {
    payAmountInput.addEventListener('input', calculatePaymentLive);
  }

  if (payOptionFull && payOptionPartial) {
    payOptionFull.addEventListener('change', function () {
      if (this.checked) {
        const total = parseFloat(document.getElementById('modal_pay_total_raw')?.value || 0);
        const alreadyPaid = parseFloat(document.getElementById('modal_pay_already_paid_raw')?.value || 0);
        const balance = Math.max(0, total - alreadyPaid);
        if (payAmountInput) {
          payAmountInput.readOnly = true;
          payAmountInput.classList.add('bg-light');
          payAmountInput.value = balance > 0 ? balance.toFixed(2) : total.toFixed(2);
        }
        calculatePaymentLive();
      }
    });

    payOptionPartial.addEventListener('change', function () {
      if (this.checked) {
        const total = parseFloat(document.getElementById('modal_pay_total_raw')?.value || 0);
        const alreadyPaid = parseFloat(document.getElementById('modal_pay_already_paid_raw')?.value || 0);
        const balance = Math.max(0, total - alreadyPaid);
        if (payAmountInput) {
          payAmountInput.readOnly = false;
          payAmountInput.classList.remove('bg-light');
          
          const defaultPartial = balance >= 30 ? '30.00' : (balance > 0 ? balance.toFixed(2) : '30.00');
          payAmountInput.value = defaultPartial;
          payAmountInput.focus();
          payAmountInput.select();
        }
        calculatePaymentLive();
      }
    });
  }

  if (payMethodSelect && gcashGroup) {
    payMethodSelect.addEventListener('change', function () {
      const refInput = document.getElementById('modal_pay_reference');
      if (this.value === 'GCash') {
        gcashGroup.classList.remove('d-none');
        if (refInput) refInput.setAttribute('required', 'required');
      } else {
        gcashGroup.classList.add('d-none');
        if (refInput) refInput.removeAttribute('required');
      }
    });
  }

  document.querySelectorAll('.btn-pay-modal').forEach((btn) => {
    btn.addEventListener('click', function () {
      const orderId = this.getAttribute('data-order-id');
      const orderNumber = this.getAttribute('data-order-number');
      const totalAmount = parseFloat(this.getAttribute('data-amount') || 0);
      const amountPaid = parseFloat(this.getAttribute('data-amount-paid') || 0);
      const balance = parseFloat(this.getAttribute('data-balance') || Math.max(0, totalAmount - amountPaid));
      const paymentMethod = this.getAttribute('data-payment-method') || 'Cash';
      const referenceNum = this.getAttribute('data-reference') || '';

      document.getElementById('modal_pay_order_id').value = orderId;
      document.getElementById('modal_pay_order_num').textContent = orderNumber;
      document.getElementById('modal_pay_total_raw').value = totalAmount;
      document.getElementById('modal_pay_already_paid_raw').value = amountPaid;

      document.getElementById('modal_pay_total_display').textContent = `₱ ${totalAmount.toFixed(2)}`;
      document.getElementById('modal_pay_paid_display').textContent = `₱ ${amountPaid.toFixed(2)}`;
      document.getElementById('modal_pay_balance_display').textContent = `₱ ${balance.toFixed(2)}`;

      if (payOptionFull) {
        payOptionFull.checked = true;
      }
      if (payAmountInput) {
        payAmountInput.readOnly = true;
        payAmountInput.classList.add('bg-light');
        payAmountInput.value = balance > 0 ? balance.toFixed(2) : totalAmount.toFixed(2);
      }

      if (payMethodSelect) {
        payMethodSelect.value = paymentMethod;
        payMethodSelect.dispatchEvent(new Event('change'));
      }
      const refInput = document.getElementById('modal_pay_reference');
      if (refInput) refInput.value = referenceNum;

      calculatePaymentLive();
    });
  });

  if (paymentForm) {
    paymentForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const method = payMethodSelect ? payMethodSelect.value : 'Cash';
      const refInput = document.getElementById('modal_pay_reference');

      if (method === 'GCash' && (!refInput || !refInput.value.trim())) {
        Toast.error('Please enter the GCash Reference Number to verify the payment.', 'GCash Verification');
        if (refInput) refInput.focus();
        return;
      }

      const submitBtn = paymentForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

      const formData = new FormData(paymentForm);
      const res = await fetchJson(getApiUrl('payments.php?action=record_payment'), {
        method: 'POST',
        body: formData
      });

      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="bi bi-check2-all me-1"></i> Save Payment';

      if (res.success) {
        Toast.success(res.message, 'Payment Recorded');
        const modalEl = document.getElementById('paymentModal');
        if (modalEl) {
          bootstrap.Modal.getInstance(modalEl)?.hide();
        }
        setTimeout(() => window.location.reload(), 1000);
      } else {
        Toast.error(res.message, 'Payment Error');
      }
    });
  }

  
  
  
  document.querySelectorAll('.btn-view-order').forEach((btn) => {
    btn.addEventListener('click', async function () {
      const orderId = this.getAttribute('data-order-id');
      const modalEl = document.getElementById('viewOrderModal');
      const modalBody = document.getElementById('view_order_details_content');

      if (!modalEl || !modalBody) return;
      modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading order details...</p></div>';

      const modalInstance = new bootstrap.Modal(modalEl);
      modalInstance.show();

      const res = await fetchJson(getApiUrl(`orders.php?action=get_details&id=${orderId}`));
      if (res.success && res.data) {
        renderOrderModalDetails(res.data, modalBody);
      } else {
        modalBody.innerHTML = `<div class="alert alert-danger">${res.message || 'Unable to load details.'}</div>`;
      }
    });
  });

  function renderOrderModalDetails(data, container) {
    const { order, history, payments } = data;
    const totalAmount = parseFloat(order.total_amount || 0);
    const amountPaid = parseFloat(order.amount_paid || 0);
    const balance = Math.max(0, totalAmount - amountPaid);

    let historyHtml = '';
    if (history && history.length > 0) {
      historyHtml = history.map(h => `
        <li class="mb-2">
          <span class="badge bg-light text-dark border me-2">${h.updated_at}</span>
          <strong>${h.status}</strong>
          ${h.remarks ? `<small class="text-muted d-block ms-4">${h.remarks}</small>` : ''}
        </li>
      `).join('');
    } else {
      historyHtml = '<li class="text-muted">No history logs recorded yet.</li>';
    }

    let paymentsHtml = '';
    if (payments && payments.length > 0) {
      paymentsHtml = payments.map(p => `
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
          <div>
            <span class="fw-semibold">₱ ${parseFloat(p.amount).toFixed(2)}</span>
            <span class="badge ${p.payment_method === 'GCash' ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success'} ms-1">${p.payment_method}</span>
            ${p.reference_number ? `<small class="text-muted d-block">Ref: ${p.reference_number}</small>` : ''}
          </div>
          <small class="text-muted">${p.paid_at || ''}</small>
        </div>
      `).join('');
    }

    container.innerHTML = `
      <div class="receipt-paper shadow-sm mb-3">
        <div class="text-center mb-3">
          <h5 class="fw-bold mb-0">${window.SHOP_NAME || 'VP Bubbles Laundry Shop'}</h5>
          <small class="text-muted">Official Laundry Receipt</small>
          <div class="receipt-divider"></div>
          <div class="fs-6 fw-bold text-primary font-monospace">${order.order_number}</div>
          <small class="text-muted">${order.created_at}</small>
        </div>

        <div class="mb-1"><strong>Customer:</strong> ${order.customer_name}</div>
        <div class="mb-1"><strong>Contact:</strong> ${order.customer_phone}</div>
        <div class="mb-2"><strong>Address:</strong> ${order.pickup_address}</div>
        <div class="receipt-divider"></div>

        <div class="d-flex justify-content-between mb-1">
          <span>Service:</span>
          <span><strong>${order.service_name}</strong></span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span>Rate:</span>
          <span>₱ ${parseFloat(order.price_per_kg).toFixed(2)}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span>Weight:</span>
          <span>${order.actual_weight ? order.actual_weight + ' KG (Actual)' : (order.estimated_weight ? order.estimated_weight + ' KG (Est.)' : 'N/A')}</span>
        </div>
        ${order.detergent_qty > 0 ? `
          <div class="d-flex justify-content-between mb-1">
            <span>Detergent (${order.detergent_qty}x):</span>
            <span>₱ ${(order.detergent_qty * parseFloat(order.detergent_price || 10)).toFixed(2)}</span>
          </div>
        ` : ''}
        <div class="receipt-divider"></div>
        
        <div class="d-flex justify-content-between fs-6 fw-bold text-dark mb-1">
          <span>Total Bill:</span>
          <span class="text-dark">₱ ${totalAmount.toFixed(2)}</span>
        </div>
        <div class="d-flex justify-content-between fs-6 fw-bold text-success mb-1">
          <span>Amount Paid:</span>
          <span>₱ ${amountPaid.toFixed(2)}</span>
        </div>
        <div class="d-flex justify-content-between fs-6 fw-bold ${balance > 0 ? 'text-danger' : 'text-muted'} mb-2">
          <span>Balance Due:</span>
          <span>${balance > 0 ? '₱ ' + balance.toFixed(2) : '₱ 0.00 (Fully Settled)'}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
          <span>Payment Status:</span>
          <span class="badge ${order.payment_status === 'Paid' ? 'bg-success' : (order.payment_status === 'Partial' ? 'bg-warning text-dark' : 'bg-danger-subtle text-danger')}">
            ${order.payment_status} (${order.payment_method})
          </span>
        </div>
        ${order.gcash_reference ? `<small class="text-muted d-block mt-1">Latest GCash Ref: ${order.gcash_reference}</small>` : ''}
        
        ${paymentsHtml ? `
          <div class="mt-3 pt-2 border-top">
            <small class="text-muted text-uppercase fw-bold d-block mb-1">Payment Transactions:</small>
            ${paymentsHtml}
          </div>
        ` : ''}

        <div class="receipt-divider"></div>
        <div class="text-center text-muted small">
          <div>Order Status: <strong class="text-dark">${order.order_status}</strong></div>
          <div class="mt-2">Thank you for choosing VP Bubbles Laundry!</div>
        </div>
      </div>

      <div class="card bg-light border-0 p-3 mt-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-clock-history me-1"></i> Status Audit Trail</h6>
        <ul class="list-unstyled mb-0 small">${historyHtml}</ul>
      </div>
    `;
  }

  
  
  
  document.querySelectorAll('.btn-copy').forEach((btn) => {
    btn.addEventListener('click', function () {
      const text = this.getAttribute('data-copy');
      if (text && navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
          Toast.info(`Copied "${text}" to clipboard`, 'Copied!');
        });
      }
    });
  });

  
  
  
  window.printReceiptModal = function () {
    const receiptEl = document.querySelector('#view_order_details_content .receipt-paper');
    if (!receiptEl) {
      window.print();
      return;
    }

    
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
        <title>Official Laundry Receipt</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>
          @page {
            margin: 6mm 4mm;
            size: auto;
          }
          body {
            font-family: 'Courier New', Courier, monospace;
            background: #ffffff;
            color: #000000;
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
            border: 1px dashed #000000;
            margin: 0 auto;
            box-sizing: border-box;
            font-size: 13px;
          }
          .receipt-divider {
            border-top: 1px dashed #000000;
            margin: 8px 0;
          }
          .badge {
            border: 1px solid #000000 !important;
            color: #000000 !important;
            background: transparent !important;
            font-weight: bold;
            padding: 2px 6px;
          }
          .text-primary, .text-success, .text-danger, .text-dark {
            color: #000000 !important;
          }
        </style>
      </head>
      <body>
        ${receiptEl.outerHTML}
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
  };

});
