<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'VP Bubbles Laundry');
define('APP_TAGLINE', 'Your Laundry, Our Responsibility');
define('CURRENCY_SYMBOL', '₱');
define('ORDER_PREFIX', 'ORD-');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = rtrim(dirname($scriptName), '/\\');

$cleanBase = preg_replace('/(\/admin|\/staff|\/api|\/includes|\/config).*$/', '', $baseDir);
define('BASE_URL', $protocol . $host . ($cleanBase ? $cleanBase : ''));

define('ROOT_PATH', dirname(__DIR__));
define('BACKUP_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'backups');
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');

if (!is_dir(BACKUP_DIR)) {
    @mkdir(BACKUP_DIR, 0777, true);
}
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0777, true);
}

date_default_timezone_set('Asia/Manila');
