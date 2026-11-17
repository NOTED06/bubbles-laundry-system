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

if ($action === 'create') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $pickupAddress = trim($_POST['pickup_address'] ?? 'Shop Drop-off / Walk-in');
    if (empty($pickupAddress)) {
        $pickupAddress = 'Shop Drop-off / Walk-in';
    }

    $serviceIds = $_POST['service_ids'] ?? [];
    if (empty($serviceIds) && !empty($_POST['service_id'])) {
        $serviceIds = [(int)$_POST['service_id']];
    }
    if (!is_array($serviceIds)) {
        $serviceIds = [(int)$serviceIds];
    }
    $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));

    $estimatedWeight = (float)($_POST['estimated_weight'] ?? 0);
    $pickupDate = trim($_POST['pickup_date'] ?? date('Y-m-d'));
    $pickupTime = trim($_POST['pickup_time'] ?? '10:00 AM');
    $detergentQty = (int)($_POST['detergent_qty'] ?? 0);
    $paymentMethod = in_array($_POST['payment_method'] ?? '', ['Cash', 'GCash']) ? $_POST['payment_method'] : 'Cash';
    $specialInstructions = trim($_POST['special_instructions'] ?? '');

    if (empty($customerName) || empty($customerPhone) || empty($serviceIds)) {
        sendJsonResponse(false, 'Please fill in customer name, phone number, and select at least one service.');
    }

    
    $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
    $svcStmt = $db->prepare("SELECT * FROM services WHERE id IN ($placeholders) AND status = 'active'");
    $svcStmt->execute($serviceIds);
    $services = $svcStmt->fetchAll();

    if (empty($services)) {
        sendJsonResponse(false, 'Selected service(s) are invalid or no longer active.');
    }

    $serviceNames = [];
    $combinedPrice = 0.00;
    $primaryServiceId = (int)$services[0]['id'];

    foreach ($services as $svc) {
        $serviceNames[] = $svc['name'];
        $combinedPrice += (float)$svc['price_per_kg'];
    }

    $combinedServiceName = implode(' + ', $serviceNames);
    $detergentPrice = (float)getSystemSetting('detergent_price', '10.00');
    $totalAmount = $combinedPrice + ($detergentQty * $detergentPrice);

    
    $customerId = getOrCreateCustomer($customerName, $customerPhone, $pickupAddress);

    
    $orderNumber = generateOrderNumber();

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, customer_id, customer_name, customer_phone, pickup_address,
                pickup_date, pickup_time, service_id, service_name, estimated_weight,
                price_per_kg, detergent_qty, detergent_price, total_amount, payment_method,
                payment_status, order_status, special_instructions, created_by
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                'Pending', 'Pending', ?, ?
            )
        ");

        $stmt->execute([
            $orderNumber, $customerId, $customerName, $customerPhone, $pickupAddress,
            $pickupDate, $pickupTime, $primaryServiceId, $combinedServiceName, $estimatedWeight,
            $combinedPrice, $detergentQty, $detergentPrice, $totalAmount, $paymentMethod,
            $specialInstructions, $userId
        ]);

        $orderId = (int)$db->lastInsertId();

        
        logOrderStatus($orderId, 'Pending', 'Order created by ' . $currentUser['name'], $userId);

        $db->commit();

        sendJsonResponse(true, "Order #{$orderNumber} created successfully!", [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, 'Failed to create order: ' . $e->getMessage());
    }
}

elseif ($action === 'get_details') {
    $orderId = (int)($_GET['id'] ?? 0);
    if ($orderId <= 0) {
        sendJsonResponse(false, 'Invalid Order ID.');
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        sendJsonResponse(false, 'Order not found.');
    }

    $historyStmt = $db->prepare("
        SELECT h.*, u.fullname as staff_name 
        FROM order_status_history h 
        LEFT JOIN users u ON h.updated_by = u.id 
        WHERE h.order_id = ? 
        ORDER BY h.id ASC
    ");
    $historyStmt->execute([$orderId]);
    $history = $historyStmt->fetchAll();

    $paymentStmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC");
    $paymentStmt->execute([$orderId]);
    $payments = $paymentStmt->fetchAll();

    sendJsonResponse(true, 'Order details loaded', [
        'order' => $order,
        'history' => $history,
        'payments' => $payments
    ]);
}

elseif ($action === 'update_weight') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $actualWeight = (float)($_POST['actual_weight'] ?? 0);

    if ($orderId <= 0 || $actualWeight <= 0) {
        sendJsonResponse(false, 'Please provide a valid actual measured weight (greater than 0 KG).');
    }

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        sendJsonResponse(false, 'Order not found.');
    }

    $unitPrice = (float)$order['price_per_kg'];
    $detergentCost = (int)$order['detergent_qty'] * (float)$order['detergent_price'];
    $newTotal = ($actualWeight * $unitPrice) + $detergentCost + (float)$order['extra_charges'];

    try {
        $db->beginTransaction();

        $updateStmt = $db->prepare("UPDATE orders SET actual_weight = ?, total_amount = ? WHERE id = ?");
        $updateStmt->execute([$actualWeight, $newTotal, $orderId]);

        logOrderStatus(
            $orderId,
            $order['order_status'],
            "Actual weight recorded: {$actualWeight} KG. Total recalculated to ₱" . number_format($newTotal, 2) . " by " . $currentUser['name'],
            $userId
        );

        $db->commit();

        sendJsonResponse(true, "Actual weight updated to {$actualWeight} KG (Total: ₱" . number_format($newTotal, 2) . ")", [
            'actual_weight' => $actualWeight,
            'total_amount' => $newTotal
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, 'Error updating weight: ' . $e->getMessage());
    }
}

elseif ($action === 'delete') {
    if ($currentUser['role'] !== 'admin') {
        sendJsonResponse(false, 'Only Administrators can delete orders.');
    }
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) {
        sendJsonResponse(false, 'Invalid order ID.');
    }

    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);

    sendJsonResponse(true, 'Order deleted successfully.');
}

else {
    sendJsonResponse(false, 'Invalid order action requested.');
}
