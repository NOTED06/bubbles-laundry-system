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
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';

    if (empty($fullname) || empty($email) || empty($password)) {
        sendJsonResponse(false, 'Please fill in all required fields (Name, Email, Password).');
    }

    
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        sendJsonResponse(false, 'An account with this email address already exists.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (fullname, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$fullname, $email, $phone, $hash, $role]);

    sendJsonResponse(true, "Staff account for \"{$fullname}\" created successfully!");
}

elseif ($action === 'update') {
    $id = (int)($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if ($id <= 0 || empty($fullname) || empty($email)) {
        sendJsonResponse(false, 'Please provide valid account details.');
    }

    
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $checkStmt->execute([$email, $id]);
    if ($checkStmt->fetch()) {
        sendJsonResponse(false, 'Email already in use by another account.');
    }

    $stmt = $db->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
    $stmt->execute([$fullname, $email, $phone, $role, $status, $id]);

    sendJsonResponse(true, "User account updated successfully!");
}

elseif ($action === 'change_password') {
    $id = (int)($_POST['user_id'] ?? 0);
    $newPassword = trim($_POST['new_password'] ?? '');

    if ($id <= 0 || strlen($newPassword) < 6) {
        sendJsonResponse(false, 'Password must be at least 6 characters.');
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $id]);

    sendJsonResponse(true, 'Password updated successfully!');
}

elseif ($action === 'delete') {
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(false, 'Invalid user ID.');
    }

    if ($id === (int)$currentUser['id']) {
        sendJsonResponse(false, 'You cannot delete your own active account.');
    }

    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    sendJsonResponse(true, 'User account deleted successfully.');
}

elseif ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT id, fullname, email, phone, role, status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $u = $stmt->fetch();

    if ($u) {
        sendJsonResponse(true, 'User loaded', $u);
    } else {
        sendJsonResponse(false, 'User not found.');
    }
}

else {
    sendJsonResponse(false, 'Invalid action.');
}
