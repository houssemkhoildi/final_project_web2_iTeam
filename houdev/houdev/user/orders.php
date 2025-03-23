<?php
// user/dashboard.php
require_once __DIR__ . '/../includes/config.php';  // Add config inclusion
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and Filter
$search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_NUMBER_INT) : '';
$statusFilter = isset($_GET['status']) ? filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) : '';

// Base query
$query = "SELECT o.id, o.total, o.status, o.created_at, 
          COUNT(oi.id) as items_count
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE o.user_id = :user_id";

$params = [':user_id' => $userId];

if (!empty($search)) {
    $query .= " AND o.id = :search";
    $params[':search'] = $search;
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $query .= " AND o.status = :status";
    $params[':status'] = $statusFilter;
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC
            LIMIT :per_page OFFSET :offset";

// Get orders
try {
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error retrieving orders";
    $orders = [];
}

// Total orders count
$countQuery = "SELECT COUNT(DISTINCT o.id) as total 
               FROM orders o
               WHERE o.user_id = :user_id";
$countParams = [':user_id' => $userId];

if (!empty($search)) {
    $countQuery .= " AND o.id = :search";
    $countParams[':search'] = $search;
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $countQuery .= " AND o.status = :status";
    $countParams[':status'] = $statusFilter;
}

try {
    $countStmt = $conn->prepare($countQuery);
    foreach ($countParams as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalOrders = $countStmt->fetchColumn();
    $totalPages = ceil($totalOrders / $perPage);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error counting orders";
    $totalPages = 1;
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid request";
        header("Location: orders.php");
        exit();
    }

    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    
    if (!$orderId) {
        $_SESSION['error'] = "Invalid order ID";
        header("Location: orders.php");
        exit();
    }
    
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' 
                              WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Order #$orderId has been cancelled.";
        } else {
            $_SESSION['error'] = "Unable to cancel order or order not found";
        }
    } catch(PDOException $e) {
        error_log("Cancellation error: " . $e->getMessage());
        $_SESSION['error'] = "Error cancelling order";
    }
    header("Location: orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - Houdev</title>
    <?php include '../includes/header.php'; ?>
</head>
<body class="bg-light">
    <div class="container">
        <?php include '../includes/navigation.php'; ?>

        <div class="row mt-4">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php include 'includes/user_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="mb-4">My Orders</h3>
                        
                        <!-- Search and Filter -->
                        <form class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" name="search" class="form-control"
                                           placeholder="Search by Order ID" value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-4">
                                    <select name="status" class="form-select">
                                        <option value="all">All Statuses</option>
                                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </div>
                        </form>

                        <?php if (!empty($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                        <?php endif; ?>

                        <!-- Orders Table -->
                        <?php if (count($orders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($order['id']) ?></td>
                                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($order['items_count']) ?></td>
                                            <td>$<?= number_format($order['total'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadge($order['status']) ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_details.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-primary">View</a>
                                                <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="cancel_order" 
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to cancel this order?')">
                                                        Cancel
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" 
                                           href="?page=<?= $page - 1 ?>&search=<?= $search ?>&status=<?= $statusFilter ?>">
                                            Previous
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" 
                                           href="?page=<?= $i ?>&search=<?= $search ?>&status=<?= $statusFilter ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" 
                                           href="?page=<?= $page + 1 ?>&search=<?= $search ?>&status=<?= $statusFilter ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php else: ?>
                            <div class="alert alert-info">No orders found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
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