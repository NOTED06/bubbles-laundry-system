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

if ($action === 'create_backup') {
    $dbName = 'vpbubbles_laundry_db';
    $filename = 'laundry_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = BACKUP_DIR . DIRECTORY_SEPARATOR . $filename;

    try {
        $tables = ['users', 'customers', 'services', 'orders', 'payments', 'order_status_history', 'cancellations', 'system_settings'];
        $sqlDump = "-- ==========================================================\n";
        $sqlDump .= "-- Laundry Management System Database Backup\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Database: " . $dbName . "\n";
        $sqlDump .= "-- ==========================================================\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            
            $createStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
            if ($createStmt && isset($createStmt['Create Table'])) {
                $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sqlDump .= $createStmt['Create Table'] . ";\n\n";
            }

            
            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sqlDump .= "INSERT INTO `{$table}` (";
                $columns = array_keys($rows[0]);
                $sqlDump .= "`" . implode("`, `", $columns) . "`) VALUES\n";

                $valStrings = [];
                foreach ($rows as $row) {
                    $rowVals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $rowVals[] = "NULL";
                        } else {
                            $rowVals[] = $db->quote($val);
                        }
                    }
                    $valStrings[] = "(" . implode(", ", $rowVals) . ")";
                }
                $sqlDump .= implode(",\n", $valStrings) . ";\n\n";
            }
        }
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($filepath, $sqlDump);
        $fileSizeFormatted = round(filesize($filepath) / 1024, 2) . ' KB';

        
        $stmt = $db->prepare("INSERT INTO backups (filename, filesize, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$filename, $fileSizeFormatted, $currentUser['id']]);

        sendJsonResponse(true, "Database backup \"{$filename}\" created successfully! ({$fileSizeFormatted})", [
            'filename' => $filename,
            'filesize' => $fileSizeFormatted
        ]);
    } catch (Exception $e) {
        sendJsonResponse(false, 'Backup creation failed: ' . $e->getMessage());
    }
}

elseif ($action === 'restore') {
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, 'Please upload a valid .sql backup file.');
    }

    $uploadedFile = $_FILES['backup_file']['tmp_name'];
    $fileContent = file_get_contents($uploadedFile);

    if (empty($fileContent)) {
        sendJsonResponse(false, 'The uploaded file is empty.');
    }

    try {
        $db->exec("SET FOREIGN_KEY_CHECKS=0;");
        $db->exec($fileContent);
        $db->exec("SET FOREIGN_KEY_CHECKS=1;");

        sendJsonResponse(true, 'Database successfully restored from backup file!');
    } catch (Exception $e) {
        sendJsonResponse(false, 'Restore failed: ' . $e->getMessage());
    }
}

elseif ($action === 'download') {
    $filename = basename($_GET['file'] ?? '');
    $filepath = BACKUP_DIR . DIRECTORY_SEPARATOR . $filename;

    if (!file_exists($filepath)) {
        die('Backup file not found.');
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

elseif ($action === 'delete') {
    $id = (int)($_POST['backup_id'] ?? 0);
    $stmt = $db->prepare("SELECT filename FROM backups WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $backup = $stmt->fetch();

    if ($backup) {
        $filepath = BACKUP_DIR . DIRECTORY_SEPARATOR . $backup['filename'];
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        $db->prepare("DELETE FROM backups WHERE id = ?")->execute([$id]);
        sendJsonResponse(true, 'Backup record and file removed.');
    } else {
        sendJsonResponse(false, 'Backup not found.');
    }
}

else {
    sendJsonResponse(false, 'Invalid action.');
}
