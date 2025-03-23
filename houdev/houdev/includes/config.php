<?php
// Start session management
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'houdev';
$username = 'root';
$password = '';

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create PDO instance
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    // Log error and exit
    error_log("Database connection failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Security functions
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCsrfToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Timezone configuration
date_default_timezone_set('UTC');

// File upload configuration
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
?>