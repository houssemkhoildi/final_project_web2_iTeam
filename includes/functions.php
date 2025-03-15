<?php
// includes/functions.php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateOrderNumber() {
    return 'ORD-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending':
            return 'badge bg-warning text-dark';
        case 'Processing':
            return 'badge bg-info text-dark';
        case 'Shipped':
            return 'badge bg-primary';
        case 'Delivered':
            return 'badge bg-success';
        case 'Cancelled':
            return 'badge bg-danger';
        default:
            return 'badge bg-secondary';
    }
}

function displayAlert($message, $type = 'success') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}
?>