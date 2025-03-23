<?php
// user/dashboard.php
require_once __DIR__ . '/../includes/config.php';  // Add config inclusion
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

// Get user details
try {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent orders
    $orders = $conn->prepare("
        SELECT o.id, o.total, o.status, o.created_at, 
               COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $orders->execute([$userId]);
    $recentOrders = $orders->fetchAll(PDO::FETCH_ASSOC);

    // Get order status count
    $statusCount = $conn->prepare("
        SELECT status, COUNT(*) as count 
        FROM orders 
        WHERE user_id = ?
        GROUP BY status
    ");
    $statusCount->execute([$userId]);
    $orderStatuses = $statusCount->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading dashboard data";
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Houdev</title>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <div class="container">
        <?php include __DIR__ . '/../includes/navigation.php'; ?>

        <div class="row mt-4">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-person-circle fs-1"></i>
                            </div>
                            <h5><?= htmlspecialchars($user['username']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        
                        <div class="list-group">
                            <a href="dashboard.php" class="list-group-item list-group-item-action active">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                            <a href="orders.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-cart me-2"></i>My Orders
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person me-2"></i>Profile Settings
                            </a>
                            <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="mb-4">Welcome Back, <?= htmlspecialchars($user['username']) ?></h3>
                        
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <?php foreach ($orderStatuses as $status): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-<?= getStatusBadge($status['status']) ?>">
                                    <div class="card-body">
                                        <h6 class="card-title text-uppercase small"><?= ucfirst($status['status']) ?></h6>
                                        <h2 class="card-text"><?= $status['count'] ?></h2>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Recent Orders -->
                        <div class="mb-5">
                            <h5 class="mb-3">Recent Orders</h5>
                            <?php if (!empty($recentOrders)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($order['id']) ?></td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($order['items_count']) ?></td>
                                                <td>$<?= number_format($order['total'], 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusBadge($order['status']) ?>">
                                                        <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order_details.php?id=<?= htmlspecialchars($order['id']) ?>" 
                                                       class="btn btn-sm btn-primary">View</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No orders found. Start shopping!</div>
                            <?php endif; ?>
                        </div>

                        <!-- Account Summary -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Account Details</h5>
                                        <ul class="list-unstyled">
                                            <li><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></li>
                                            <li><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
                                            <li><strong>Registered:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></li>
                                        </ul>
                                        <a href="profile.php" class="btn btn-outline-primary">Edit Profile</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Shipping Address</h5>
                                        <p><?= nl2br(htmlspecialchars($user['address'] ?? 'No address provided')) ?></p>
                                        <a href="profile.php" class="btn btn-outline-primary">Update Address</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

<?php
function getStatusBadge($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>