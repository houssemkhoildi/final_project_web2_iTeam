<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    updateOrderStatus();
}

if (isset($_GET['delete'])) {
    deleteOrder();
}

// Fetch orders
$orders = $conn->query("
    SELECT o.*, u.username, u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function updateOrderStatus() {
    global $conn;
    
    $orderId = sanitizeInput($_POST['order_id']);
    $newStatus = sanitizeInput($_POST['status']);
    
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $_SESSION['success'] = "Order status updated successfully!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error updating order: " . $e->getMessage();
    }
}

function deleteOrder() {
    global $conn;
    
    $orderId = sanitizeInput($_GET['delete']);
    
    try {
        $conn->beginTransaction();
        
        // Delete order items
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Delete order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        
        $conn->commit();
        $_SESSION['success'] = "Order deleted successfully!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error deleting order: " . $e->getMessage();
    }
}

function getOrderItems($orderId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management - Houdev</title>
    <?php include '../includes/header.php'; ?>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Order Management</h1>
                </div>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <?php $items = getOrderItems($order['id']); ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['id']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($order['username']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($items as $item): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                                         style="width: 40px; height: 40px; object-fit: cover;">
                                                    <div>
                                                        <div><?= htmlspecialchars($item['name']) ?></div>
                                                        <small class="text-muted">
                                                            <?= $item['quantity'] ?> x $<?= number_format($item['price'], 2) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td>$<?= number_format($order['total'], 2) ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" 
                                                    onchange="this.form.submit()">
                                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <a href="order_details.php?id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-primary">View</a>
                                            <a href="?delete=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>