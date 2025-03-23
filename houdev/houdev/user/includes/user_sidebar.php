<?php
// user_sidebar.php - User account navigation
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
?>

<div class="list-group">
    <a href="dashboard.php" 
       class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-home fa-fw me-2"></i>Dashboard
    </a>
    
    <a href="orders.php" 
       class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-bag fa-fw me-2"></i>My Orders
        <span class="badge bg-primary float-end"><?= getOrderCount() ?></span>
    </a>

    <a href="addresses.php" 
       class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'addresses.php' ? 'active' : '' ?>">
        <i class="fas fa-address-book fa-fw me-2"></i>Addresses
    </a>

    <a href="profile.php" 
       class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
        <i class="fas fa-user fa-fw me-2"></i>Profile
    </a>

    <form method="post" action="../includes/logout.php" class="list-group-item">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit" class="btn btn-link text-decoration-none p-0">
            <i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout
        </button>
    </form>
</div>

<?php
// Helper function to get order count
function getOrderCount() {
    global $conn, $userId;
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("Order count error: " . $e->getMessage());
        return 0;
    }
}
?>