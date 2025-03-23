<?php
// includes/links.php

// Base Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$base_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', realpath(__DIR__ . '/..')));
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $base_path . '/');

// Core Pages
define('HOME_URL', BASE_URL . 'index.php');
define('LOGIN_URL', BASE_URL . 'login.php');
define('REGISTER_URL', BASE_URL . 'register.php');
define('CHECKOUT_URL', BASE_URL . 'checkout.php');

// Admin Section
define('ADMIN_DASHBOARD', BASE_URL . 'admin/dashboard.php');
define('ADMIN_PRODUCTS', BASE_URL . 'admin/products.php');
define('ADMIN_ORDERS', BASE_URL . 'admin/orders.php');
define('ADMIN_USERS', BASE_URL . 'admin/users.php');

// User Section
define('USER_DASHBOARD', BASE_URL . 'user/dashboard.php');
define('USER_ORDERS', BASE_URL . 'user/orders.php');
define('USER_PROFILE', BASE_URL . 'user/profile.php');

// Products
define('PRODUCTS_MAIN', BASE_URL . 'products/product.php');

// Cart
define('CART_MAIN', BASE_URL . 'cart/cart.php');

// Assets
define('CSS_PATH', BASE_URL . 'assets/css/');
define('JS_PATH', BASE_URL . 'assets/js/');
define('IMG_PATH', BASE_URL . 'assets/images/');

// Functions
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function asset($type, $file) {
    switch($type) {
        case 'css':
            return CSS_PATH . $file;
        case 'js':
            return JS_PATH . $file;
        case 'img':
            return IMG_PATH . $file;
        default:
            return BASE_URL . $file;
    }
}

function redirect($path, $status_code = 303) {
    header('Location: ' . url($path), true, $status_code);
    exit();
}

function current_url() {
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
 
if (!defined('IN_APP')) {
    die('Direct access not permitted');
}
?>