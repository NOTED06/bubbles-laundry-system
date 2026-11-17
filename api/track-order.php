<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$orderNumber = trim($_GET['order_number'] ?? $_POST['order_number'] ?? '');
$customerPhone = trim($_GET['customer_phone'] ?? $_POST['customer_phone'] ?? '');

if (empty($orderNumber) || empty($customerPhone)) {
    sendJsonResponse(false, 'Please enter both Order Number and Contact Number.');
}

$cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);

$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, s.name as service_title, s.unit_label, s.badge as service_badge, c.email as customer_email
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN customers c ON o.customer_id = c.id
    WHERE UPPER(o.order_number) = UPPER(?) 
      AND REPLACE(REPLACE(REPLACE(o.customer_phone, ' ', ''), '-', ''), '+63', '0') LIKE ?
    LIMIT 1
");

$searchParam = '%' . substr($cleanPhone, -9); 
$stmt->execute([$orderNumber, $searchParam]);
$order = $stmt->fetch();

if (!$order) {
    sendJsonResponse(false, 'No order found matching the provided Order Number and Contact Number. Please verify your details.');
}

$historyStmt = $db->prepare("
    SELECT status, remarks, updated_at 
    FROM order_status_history 
    WHERE order_id = ? 
    ORDER BY id ASC
");
$historyStmt->execute([$order['id']]);
$history = $historyStmt->fetchAll();

$cancellation = null;
if ($order['order_status'] === 'Cancelled') {
    $cancelStmt = $db->prepare("SELECT reason, cancelled_at FROM cancellations WHERE order_id = ? LIMIT 1");
    $cancelStmt->execute([$order['id']]);
    $cancellation = $cancelStmt->fetch();
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

$currentStepIndex = $steps[$order['order_status']] ?? 1;

sendJsonResponse(true, 'Order found successfully', [
    'order' => [
        'id' => $order['id'],
        'order_number' => $order['order_number'],
        'customer_name' => $order['customer_name'],
        'customer_phone' => $order['customer_phone'],
        'pickup_address' => $order['pickup_address'],
        'pickup_date' => formatDate($order['pickup_date']),
        'pickup_time' => $order['pickup_time'],
        'service_name' => $order['service_name'],
        'service_badge' => $order['service_badge'],
        'unit_label' => $order['unit_label'],
        'price_per_kg' => (float)$order['price_per_kg'],
        'estimated_weight' => $order['estimated_weight'] ? (float)$order['estimated_weight'] : null,
        'actual_weight' => $order['actual_weight'] ? (float)$order['actual_weight'] : null,
        'detergent_qty' => (int)$order['detergent_qty'],
        'detergent_price' => (float)$order['detergent_price'],
        'total_amount' => (float)$order['total_amount'],
        'amount_paid' => (float)($order['amount_paid'] ?? 0),
        'balance' => max(0, (float)$order['total_amount'] - (float)($order['amount_paid'] ?? 0)),
        'payment_method' => $order['payment_method'],
        'payment_status' => $order['payment_status'],
        'gcash_reference' => $order['gcash_reference'],
        'order_status' => $order['order_status'],
        'special_instructions' => $order['special_instructions'],
        'created_at' => formatDateTime($order['created_at']),
        'updated_at' => formatDateTime($order['updated_at'])
    ],
    'current_step' => $currentStepIndex,
    'total_steps' => count($steps),
    'history' => $history,
    'cancellation' => $cancellation
]);
