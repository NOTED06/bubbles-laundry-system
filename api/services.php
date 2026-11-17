<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    sendJsonResponse(false, 'Unauthorized.', [], 401);
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    sendJsonResponse(false, 'Admin privileges required.', [], 403);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price_per_kg'] ?? 0);
    $unitType = in_array($_POST['unit_type'] ?? '', ['per_kg', 'per_load', 'per_item', 'fixed']) ? $_POST['unit_type'] : 'per_load';
    $unitLabel = trim($_POST['unit_label'] ?? 'per Load');
    $badge = trim($_POST['badge'] ?? 'Standard');
    $icon = trim($_POST['icon'] ?? 'bi-droplet-half');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || $price <= 0) {
        sendJsonResponse(false, 'Please provide service name and a valid rate.');
    }

    $stmt = $db->prepare("
        INSERT INTO services (name, price_per_kg, unit_type, unit_label, badge, icon, description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$name, $price, $unitType, $unitLabel, $badge, $icon, $description]);

    sendJsonResponse(true, "Service \"{$name}\" created successfully!");
}

elseif ($action === 'update') {
    $id = (int)($_POST['service_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price_per_kg'] ?? 0);
    $unitType = in_array($_POST['unit_type'] ?? '', ['per_kg', 'per_load', 'per_item', 'fixed']) ? $_POST['unit_type'] : 'per_load';
    $unitLabel = trim($_POST['unit_label'] ?? 'per Load');
    $badge = trim($_POST['badge'] ?? 'Standard');
    $icon = trim($_POST['icon'] ?? 'bi-droplet-half');
    $description = trim($_POST['description'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if ($id <= 0 || empty($name) || $price <= 0) {
        sendJsonResponse(false, 'Please provide valid service details and rate.');
    }

    $stmt = $db->prepare("
        UPDATE services 
        SET name = ?, price_per_kg = ?, unit_type = ?, unit_label = ?, badge = ?, icon = ?, description = ?, status = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $price, $unitType, $unitLabel, $badge, $icon, $description, $status, $id]);

    sendJsonResponse(true, "Service \"{$name}\" updated successfully!");
}

elseif ($action === 'delete') {
    $id = (int)($_POST['service_id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(false, 'Invalid service ID.');
    }

    
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE service_id = ?");
    $checkStmt->execute([$id]);
    $orderCount = (int)$checkStmt->fetchColumn();

    if ($orderCount > 0) {
        
        $db->prepare("UPDATE services SET status = 'inactive' WHERE id = ?")->execute([$id]);
        sendJsonResponse(true, "Service has existing order records. It has been deactivated instead of deleted.");
    } else {
        $db->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
        sendJsonResponse(true, "Service deleted successfully.");
    }
}

elseif ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $svc = $stmt->fetch();

    if ($svc) {
        sendJsonResponse(true, 'Service loaded', $svc);
    } else {
        sendJsonResponse(false, 'Service not found.');
    }
}

else {
    sendJsonResponse(false, 'Invalid service action.');
}
