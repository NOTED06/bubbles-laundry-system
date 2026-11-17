<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    sendJsonResponse(false, 'Unauthorized. Please log in first.', [], 401);
}

$currentUser = getCurrentUser();
$userId = $currentUser['id'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

if ($action === 'record_payment') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $payAmount = (float)($_POST['amount'] ?? 0);
    $method = in_array($_POST['payment_method'] ?? '', ['Cash', 'GCash']) ? $_POST['payment_method'] : 'Cash';
    $referenceNum = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($orderId <= 0) {
        sendJsonResponse(false, 'Invalid order ID.');
    }

    if ($payAmount <= 0) {
        sendJsonResponse(false, 'Please enter a valid payment amount greater than 0.');
    }

    if ($method === 'GCash' && empty($referenceNum)) {
        sendJsonResponse(false, 'Please provide the GCash Reference Number from customer receipt.');
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        sendJsonResponse(false, 'Order not found.');
    }

    $totalAmount = (float)$order['total_amount'];
    $currentAmountPaid = (float)($order['amount_paid'] ?? 0);
    
    
    if ($order['payment_status'] === 'Paid' && $currentAmountPaid >= $totalAmount) {
        $currentAmountPaid = 0;
    }

    $newTotalPaid = min($totalAmount, $currentAmountPaid + $payAmount);

    if ($newTotalPaid >= $totalAmount) {
        $paymentStatus = 'Paid';
        $newTotalPaid = $totalAmount;
    } elseif ($newTotalPaid > 0) {
        $paymentStatus = 'Partial';
    } else {
        $paymentStatus = 'Pending';
    }

    $remainingBalance = max(0, $totalAmount - $newTotalPaid);

    try {
        $db->beginTransaction();

        
        $updateStmt = $db->prepare("
            UPDATE orders 
            SET amount_paid = ?, payment_method = ?, payment_status = ?, gcash_reference = COALESCE(NULLIF(?, ''), gcash_reference)
            WHERE id = ?
        ");
        $updateStmt->execute([
            $newTotalPaid,
            $method,
            $paymentStatus,
            ($method === 'GCash' ? $referenceNum : null),
            $orderId
        ]);

        
        $payInsert = $db->prepare("
            INSERT INTO payments (order_id, amount, payment_method, reference_number, status, verified_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $payInsert->execute([
            $orderId,
            $payAmount,
            $method,
            $referenceNum,
            $paymentStatus,
            $userId,
            $notes
        ]);

        
        $remark = "Payment of ₱" . number_format($payAmount, 2) . " ({$method}) recorded as {$paymentStatus}. Total Paid: ₱" . number_format($newTotalPaid, 2) . " / ₱" . number_format($totalAmount, 2);
        if (!empty($referenceNum)) {
            $remark .= " (GCash Ref: {$referenceNum})";
        }
        $remark .= " by " . $currentUser['name'];
        logOrderStatus($orderId, $order['order_status'], $remark, $userId);

        $db->commit();

        $statusLabel = $paymentStatus === 'Paid' ? 'Paid in Full' : "Partial Payment (Balance: ₱" . number_format($remainingBalance, 2) . ")";
        sendJsonResponse(true, "Payment of ₱" . number_format($payAmount, 2) . " ({$method}) saved! Order marked as {$statusLabel}.", [
            'order_id' => $orderId,
            'amount_paid_now' => $payAmount,
            'total_amount_paid' => $newTotalPaid,
            'remaining_balance' => $remainingBalance,
            'payment_status' => $paymentStatus,
            'payment_method' => $method
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, 'Failed to record payment: ' . $e->getMessage());
    }
} else {
    sendJsonResponse(false, 'Invalid action.');
}
