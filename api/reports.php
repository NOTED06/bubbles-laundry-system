<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    die('Unauthorized');
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    die('Admin access required');
}

$action = $_GET['action'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$paymentMethod = $_GET['payment_method'] ?? 'all';
$status = $_GET['status'] ?? 'all';

$db = getDB();

if ($action === 'export_csv') {
    $where = ["DATE(o.created_at) BETWEEN ? AND ?"];
    $params = [$startDate, $endDate];

    if ($paymentMethod !== 'all') {
        $where[] = "o.payment_method = ?";
        $params[] = $paymentMethod;
    }
    if ($status !== 'all') {
        $where[] = "o.order_status = ?";
        $params[] = $status;
    }

    $whereClause = implode(" AND ", $where);
    $query = "
        SELECT 
            o.order_number,
            o.customer_name,
            o.customer_phone,
            o.service_name,
            COALESCE(o.actual_weight, o.estimated_weight) as weight_kg,
            o.total_amount,
            COALESCE(o.amount_paid, 0) as amount_paid,
            (o.total_amount - COALESCE(o.amount_paid, 0)) as balance_due,
            o.payment_method,
            o.payment_status,
            o.gcash_reference,
            o.order_status,
            o.pickup_date,
            o.pickup_time,
            o.created_at
        FROM orders o
        WHERE {$whereClause}
        ORDER BY o.id DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laundry_report_' . $startDate . '_to_' . $endDate . '.csv');

    $output = fopen('php://output', 'w');
    
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    
    fputcsv($output, [
        'Order Number', 'Customer Name', 'Contact Phone', 'Service',
        'Weight (KG)', 'Total Amount (PHP)', 'Amount Paid (PHP)', 'Balance Due (PHP)',
        'Payment Method', 'Payment Status', 'GCash Reference', 'Order Status', 'Pickup Date', 'Pickup Time', 'Created At'
    ]);

    foreach ($orders as $row) {
        fputcsv($output, [
            $row['order_number'],
            $row['customer_name'],
            $row['customer_phone'],
            $row['service_name'],
            $row['weight_kg'],
            number_format($row['total_amount'], 2, '.', ''),
            number_format($row['amount_paid'], 2, '.', ''),
            number_format(max(0, $row['balance_due']), 2, '.', ''),
            $row['payment_method'],
            $row['payment_status'],
            $row['gcash_reference'] ?? 'N/A',
            $row['order_status'],
            $row['pickup_date'],
            $row['pickup_time'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}
