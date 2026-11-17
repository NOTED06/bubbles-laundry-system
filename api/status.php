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

if ($action === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['order_status'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    $allowedStatuses = [
        'Pending', 'Pickup Scheduled', 'Picked Up',
        'Washing', 'Drying', 'Folding', 'Ready', 'Completed', 'Cancelled'
    ];

    if ($orderId <= 0 || !in_array($newStatus, $allowedStatuses)) {
        sendJsonResponse(false, 'Invalid order or status transition requested.');
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        sendJsonResponse(false, 'Order not found.');
    }

    try {
        $db->beginTransaction();

        $updateStmt = $db->prepare("UPDATE orders SET order_status = ?, assigned_staff_id = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $userId, $orderId]);

        $logRemark = !empty($remarks) ? $remarks : "Status progressed to {$newStatus} by " . $currentUser['name'];
        $logRemark = !empty($remarks) ? $remarks : ($newStatus === 'Cancelled' ? 'Order cancelled by staff' : "Status progressed to {$newStatus} by " . $currentUser['name']);
        logOrderStatus($orderId, $newStatus, $logRemark, $userId);

        if ($newStatus === 'Cancelled') {
            $cancelReason = !empty($remarks) ? $remarks : 'Order cancelled via status update.';
            $cancelStmt = $db->prepare("INSERT INTO cancellations (order_id, cancelled_by, reason) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reason = VALUES(reason)");
            $cancelStmt->execute([$orderId, $userId, $cancelReason]);
        }

        $db->commit();

        sendJsonResponse(true, "Order #{$order['order_number']} status updated to \"{$newStatus}\".", [
            'order_id' => $orderId,
            'order_status' => $newStatus
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, 'Failed to update order status: ' . $e->getMessage());
    }
}

elseif ($action === 'cancel_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $reason = trim($_POST['cancellation_reason'] ?? '');

    if ($orderId <= 0 || empty($reason)) {
        sendJsonResponse(false, 'Please provide a valid cancellation reason.');
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        sendJsonResponse(false, 'Order not found.');
    }

    try {
        $db->beginTransaction();

        
        $updateStmt = $db->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE id = ?");
        $updateStmt->execute([$orderId]);

        
        $cancelStmt = $db->prepare("INSERT INTO cancellations (order_id, cancelled_by, reason) VALUES (?, ?, ?)");
        $cancelStmt->execute([$orderId, $userId, $reason]);

        
        logOrderStatus($orderId, 'Cancelled', "Order cancelled. Reason: {$reason} (Cancelled by " . $currentUser['name'] . ")", $userId);

        $db->commit();

        sendJsonResponse(true, "Order #{$order['order_number']} has been cancelled and archived.", [
            'order_id' => $orderId,
            'order_status' => 'Cancelled'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, 'Failed to cancel order: ' . $e->getMessage());
    }
}

else {
    sendJsonResponse(false, 'Invalid action.');
}
