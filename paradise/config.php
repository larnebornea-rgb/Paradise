<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'paradise_resort');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDB(): mysqli {
    static $schemaEnsured = false;

    mysqli_report(MYSQLI_REPORT_OFF);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (Throwable $e) {
        die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
    }

    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }

    $conn->set_charset('utf8mb4');

    if (!$schemaEnsured) {
        ensureOptionalSchema($conn);
        $schemaEnsured = true;
    }

    return $conn;
}

function ensureOptionalSchema(mysqli $conn): void {
    ensureColumn($conn, 'users', 'account_status', "VARCHAR(20) NOT NULL DEFAULT 'active'");
    ensureColumn($conn, 'users', 'display_theme', "VARCHAR(20) NOT NULL DEFAULT 'teal'");
    ensureColumn($conn, 'users', 'phone', "VARCHAR(50) NULL");
    ensureColumn($conn, 'users', 'is_guest', "TINYINT(1) NOT NULL DEFAULT 0");

    ensureColumn($conn, 'reservations', 'guest_name', "VARCHAR(150) NULL");
    ensureColumn($conn, 'reservations', 'guest_email', "VARCHAR(150) NULL");
    ensureColumn($conn, 'reservations', 'guest_phone', "VARCHAR(50) NULL");
    ensureColumn($conn, 'reservations', 'guest_count', "INT NOT NULL DEFAULT 1");
    ensureColumn($conn, 'reservations', 'check_in_time', "TIME NULL");
    ensureColumn($conn, 'reservations', 'check_out_time', "TIME NULL");
    ensureColumn($conn, 'reservations', 'down_payment', "DECIMAL(10,2) NOT NULL DEFAULT 0");
    ensureColumn($conn, 'reservations', 'payment_method', "VARCHAR(50) NULL");
    ensureColumn($conn, 'reservations', 'is_walk_in', "TINYINT(1) NOT NULL DEFAULT 0");

    $conn->query("
        CREATE TABLE IF NOT EXISTS login_activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            email VARCHAR(150) NULL,
            role VARCHAR(50) NULL,
            status VARCHAR(20) NOT NULL,
            ip_address VARCHAR(64) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
}

function ensureColumn(mysqli $conn, string $table, string $column, string $definition): void {
    $safeTable = $conn->real_escape_string($table);
    $safeColumn = $conn->real_escape_string($column);
    $safeDb = $conn->real_escape_string(DB_NAME);

    $result = $conn->query("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = '{$safeDb}'
          AND TABLE_NAME = '{$safeTable}'
          AND COLUMN_NAME = '{$safeColumn}'
        LIMIT 1
    ");

    $exists = $result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }

    if (!$exists) {
        $conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function getClientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function getPaymentQrAsset(): string {
    $candidates = [
        'assets/gcash-qr.png',
        'assets/gcash-qr.jpg',
        'assets/gcash-qr.jpeg',
        'assets/gcash-qr.webp',
        'assets/gcash-qr.svg',
    ];

    foreach ($candidates as $path) {
        if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path))) {
            return $path;
        }
    }

    return 'assets/gcash-qr.svg';
}
?>
