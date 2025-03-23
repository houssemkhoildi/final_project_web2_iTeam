<?php
// Security headers - can be set before session starts
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

// Session security validations
function validateSession() {
    // Create session fingerprint
    $fingerprint = $_SERVER['HTTP_USER_AGENT'] . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) 
        ? $_SERVER['HTTP_X_FORWARDED_FOR'] 
        : $_SERVER['REMOTE_ADDR']);

    // Validate fingerprint
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = hash('sha256', $fingerprint);
    } elseif ($_SESSION['fingerprint'] !== hash('sha256', $fingerprint)) {
        session_destroy();
        header("Location: /login.php?error=session_invalid");
        exit();
    }

    // Regenerate ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }

    // Update last activity
    $_SESSION['last_activity'] = time();
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function redirectIfNotLoggedIn($redirectUrl = '/login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirectUrl");
        exit();
    }
    validateSession(); // Validate on each protected page access
}

function redirectIfNotAdmin($redirectUrl = '/index.php') {
    redirectIfNotLoggedIn();
    
    if (!isAdmin()) {
        $_SESSION['error'] = 'Unauthorized access';
        header("Location: $redirectUrl");
        exit();
    }
}

// Initialize session validation
if (session_status() === PHP_SESSION_ACTIVE) {
    validateSession();
    
    // Handle session timeout (60 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        header("Location: /login.php?error=session_expired");
        exit();
    }
}
?>