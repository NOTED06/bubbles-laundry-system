<?php

class Database {
    private static ?PDO $instance = null;
    
    
    private const LOCAL_HOST = 'localhost';
    private const LOCAL_USER = 'root';
    private const LOCAL_PASS = '';
    private const LOCAL_NAME = 'vpbubbles_laundry_db';

    
    private const PROD_HOST = 'sql102.infinityfree.com'; 
    private const PROD_USER = 'if0_42756879';            
    private const PROD_PASS = 'fFF0XvR2wILqJF';            
    private const PROD_NAME = 'if0_42756879_laundry_system'; 

    private const DB_CHARSET = 'utf8mb4';

    
    public static function isLocalEnvironment(): bool {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return in_array($host, ['localhost', '127.0.0.1', '::1']) || str_starts_with($host, 'localhost:');
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $isLocal = self::isLocalEnvironment();

            $dbHost = $isLocal ? self::LOCAL_HOST : (getenv('DB_HOST') ?: self::PROD_HOST);
            $dbUser = $isLocal ? self::LOCAL_USER : (getenv('DB_USER') ?: self::PROD_USER);
            $dbPass = $isLocal ? self::LOCAL_PASS : (getenv('DB_PASS') ?: self::PROD_PASS);
            $dbName = $isLocal ? self::LOCAL_NAME : (getenv('DB_NAME') ?: self::PROD_NAME);

            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=" . self::DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            try {
                self::$instance = new PDO($dsn, $dbUser, $dbPass, $options);
            } catch (PDOException $e) {
                if ($isLocal) {
                    
                    try {
                        $rootDsn = "mysql:host=" . self::LOCAL_HOST . ";charset=" . self::DB_CHARSET;
                        $tempPdo = new PDO($rootDsn, self::LOCAL_USER, self::LOCAL_PASS, $options);
                        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . self::LOCAL_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                        self::$instance = new PDO($dsn, self::LOCAL_USER, self::LOCAL_PASS, $options);
                        return self::$instance;
                    } catch (PDOException $ex) {
                        
                    }
                }

                
                if (php_sapi_name() === 'cli') {
                    die("Database Connection Error: " . $e->getMessage() . "\n");
                }

                header('HTTP/1.1 500 Internal Server Error');
                if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Database connection failed: ' . $e->getMessage()
                    ]);
                } else {
                    echo "<!DOCTYPE html><html lang='en'><head><title>Database Setup - VP Bubbles Laundry</title><meta name='viewport' content='width=device-width, initial-scale=1'><link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'></head><body class='bg-light py-5'><div class='container' style='max-width: 620px;'><div class='card shadow-sm border-0 rounded-4 p-4'><div class='text-center mb-3'><span class='display-5 text-warning'>⚠️</span><h4 class='fw-bold mt-2'>Database Connection Setup</h4><p class='text-muted small'>VP Bubbles Laundry Management System</p></div><div class='alert alert-danger py-2 small'><strong>Connection Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div><div class='bg-light p-3 rounded-3 small mb-3 border'><strong>Connection Details:</strong><div class='mt-1'>Host: <code>" . htmlspecialchars($dbHost) . "</code></div><div>Database: <code>" . htmlspecialchars($dbName) . "</code></div><div>Username: <code>" . htmlspecialchars($dbUser) . "</code></div></div><div class='small text-muted mb-3'>To fix this on InfinityFree / Live Hosting: <ol class='ps-3 mb-0 mt-1'><li>Go to your Hosting Control Panel &gt; <strong>MySQL Databases</strong>.</li><li>Check your <strong>MySQL Hostname</strong> and <strong>Database Name</strong>.</li><li>Update <code>config/database.php</code> with the exact Hostname &amp; Database Name.</li><li>Import <code>database/laundry_system.sql</code> via phpMyAdmin.</li></ol></div><a href='javascript:location.reload()' class='btn btn-primary w-100 rounded-pill fw-semibold'>🔄 Retry Connection</a></div></div></body></html>";
                }
                exit;
            }
        }
        return self::$instance;
    }
}

function getDB(): PDO {
    return Database::getInstance();
}
