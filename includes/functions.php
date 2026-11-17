<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

function sendJsonResponse(bool $success, string $message = '', array $data = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function formatCurrency(float $amount): string {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

function formatDateTime(?string $datetime, string $format = 'M d, Y h:i A'): string {
    if (!$datetime) return 'N/A';
    return date($format, strtotime($datetime));
}

function generateOrderNumber(): string {
    $db = getDB();
    $year = date('Y');
    $stmt = $db->query("SELECT MAX(id) as max_id FROM orders");
    $row = $stmt->fetch();
    $nextId = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
    return ORDER_PREFIX . $year . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
}

function calculateOrderUrgency(string $pickupDate, string $pickupTime, string $orderStatus): array {
    if (in_array($orderStatus, ['Ready', 'Completed', 'Cancelled'])) {
        return [
            'status' => 'resolved',
            'label' => $orderStatus,
            'badge_class' => 'bg-secondary',
            'diff_text' => 'Finished / ' . $orderStatus
        ];
    }

    $dateTimeStr = $pickupDate . ' ' . $pickupTime;
    $pickupTimestamp = strtotime($dateTimeStr);
    if (!$pickupTimestamp) {
        $pickupTimestamp = strtotime($pickupDate . ' 17:00:00');
    }

    $now = time();
    $diffMinutes = round(($pickupTimestamp - $now) / 60);

    if ($diffMinutes < 0) {
        $hoursAgo = abs(round($diffMinutes / 60, 1));
        return [
            'status' => 'overdue',
            'label' => 'OVERDUE',
            'badge_class' => 'bg-danger text-white priority-pulse',
            'diff_text' => $hoursAgo . 'h overdue',
            'minutes' => $diffMinutes
        ];
    } elseif ($diffMinutes <= 120) { 
        return [
            'status' => 'due_soon',
            'label' => 'DUE SOON',
            'badge_class' => 'bg-warning text-dark priority-pulse',
            'diff_text' => 'In ' . $diffMinutes . ' mins',
            'minutes' => $diffMinutes
        ];
    } else {
        $hoursRemaining = round($diffMinutes / 60, 1);
        return [
            'status' => 'upcoming',
            'label' => 'UPCOMING',
            'badge_class' => 'bg-info text-dark',
            'diff_text' => 'In ' . $hoursRemaining . 'h',
            'minutes' => $diffMinutes
        ];
    }
}

function getStatusBadge(string $status): string {
    $map = [
        'Pending'          => ['bg-warning text-dark', 'bi-hourglass-split', 'Pending'],
        'Pickup Scheduled' => ['bg-primary text-white', 'bi-calendar-check', 'Scheduled'],
        'Picked Up'        => ['bg-info text-dark', 'bi-box-seam', 'Dropped Off'],
        'Washing'          => ['bg-primary text-white', 'bi-water', 'Washing'],
        'Drying'           => ['bg-info text-white', 'bi-wind', 'Drying'],
        'Folding'          => ['bg-secondary text-white', 'bi-layers', 'Folding'],
        'Ready'            => ['bg-success text-white', 'bi-check-circle', 'Ready for Pick-up'],
        'Completed'        => ['bg-dark text-white', 'bi-bag-check-fill', 'Completed'],
        'Cancelled'        => ['bg-danger text-white', 'bi-x-circle-fill', 'Cancelled']
    ];

    $badge = $map[$status] ?? ['bg-secondary text-white', 'bi-circle', $status];
    $display = $badge[2] ?? $status;
    return '<span class="badge rounded-pill ' . $badge[0] . ' px-2 py-1"><i class="bi ' . $badge[1] . ' me-1"></i> ' . htmlspecialchars($display) . '</span>';
}

function getPaymentBadge(string $status, string $method = 'Cash', float $amountPaid = 0.0, float $totalAmount = 0.0): string {
    $methodBadge = htmlspecialchars($method);
    if ($status === 'Paid') {
        return '<span class="badge rounded-pill bg-success px-2 py-1"><i class="bi bi-check2-all me-1"></i> Paid (' . $methodBadge . ')</span>';
    } elseif ($status === 'Partial') {
        $detail = ($totalAmount > 0 && $amountPaid > 0) ? ' ₱' . number_format($amountPaid, 0) . '/' . number_format($totalAmount, 0) : '';
        return '<span class="badge rounded-pill bg-warning text-dark px-2 py-1" title="Partial Payment: ₱' . number_format($amountPaid, 2) . ' paid of ₱' . number_format($totalAmount, 2) . '"><i class="bi bi-pie-chart-fill me-1"></i> Partial' . $detail . ' (' . $methodBadge . ')</span>';
    }
    return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i> Unpaid (' . $methodBadge . ')</span>';
}

function getOrCreateCustomer(string $name, string $phone, string $address = 'Shop Drop-off / Walk-in', ?string $email = null): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, address FROM customers WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch();

    if ($customer) {
        
        $updateStmt = $db->prepare("UPDATE customers SET name = ?, address = ?, email = COALESCE(?, email) WHERE id = ?");
        $updateStmt->execute([$name, $address, $email, $customer['id']]);
        return (int)$customer['id'];
    }

    $insertStmt = $db->prepare("INSERT INTO customers (name, phone, address, email) VALUES (?, ?, ?, ?)");
    $insertStmt->execute([$name, $phone, $address, $email]);
    return (int)$db->lastInsertId();
}

function logOrderStatus(int $orderId, string $status, ?string $remarks = null, ?int $userId = null): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, remarks, updated_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderId, $status, $remarks, $userId]);
}

function getSystemSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['setting_value'] : $default;
}

function setSystemSetting(string $key, string $value): bool {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

function getShopLogoUrl(): string {
    $customLogo = getSystemSetting('shop_logo', '');
    if (!empty($customLogo)) {
        $cleanPath = ltrim($customLogo, '/\\');
        $fullPath = ROOT_PATH . '/' . $cleanPath;
        if (file_exists($fullPath)) {
            return BASE_URL . '/' . $cleanPath . '?v=' . filemtime($fullPath);
        }
    }
    $defaultLogo = ROOT_PATH . '/assets/images/logo.png';
    if (file_exists($defaultLogo)) {
        return BASE_URL . '/assets/images/logo.png?v=' . filemtime($defaultLogo);
    }
    return '';
}

function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
