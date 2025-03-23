<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

// Get statistics
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRevenue = $conn->query("SELECT SUM(total) FROM orders WHERE status = 'delivered'")->fetchColumn();

// Recent orders
$recentOrders = $conn->query("
    SELECT o.id, u.username, o.total, o.status, o.created_at 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Popular products
$popularProducts = $conn->query("
    SELECT p.id, p.name, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Houdev</title>
    <?php include '../includes/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Overview</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Products</h5>
                                <h2 class="card-text"><?= $totalProducts ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Orders</h5>
                                <h2 class="card-text"><?= $totalOrders ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <h2 class="card-text"><?= $totalUsers ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Revenue</h5>
                                <h2 class="card-text">$<?= number_format($totalRevenue, 2) ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body">
                                <h5 class="card-title">Sales Overview</h5>
                                <canvas id="salesChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body">
                                <h5 class="card-title">Order Status Distribution</h5>
                                <canvas id="orderStatusChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Recent Orders</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= $order['username'] ?></td>
                                        <td>$<?= number_format($order['total'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusBadge($order['status']) ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <a href="orders.php?action=view&id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Popular Products -->
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Most Popular Products</h5>
                        <div class="row">
                            <?php foreach($popularProducts as $product): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= $product['name'] ?></h6>
                                        <p class="card-text">
                                            <span class="text-muted">Total Sold:</span>
                                            <?= $product['total_sold'] ?>
                                        </p>
                                        <a href="../products/product.php?id=<?= $product['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">View Product</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Dashboard Charts Script -->
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Monthly Sales',
                    data: [12000, 19000, 3000, 5000, 2000, 3000],
                    borderColor: '#4e73df',
                    tension: 0.4
                }]
            }
        });

        // Order Status Chart
        const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered'],
                datasets: [{
                    data: [12, 19, 3, 5],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                }]
            }
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
// Helper function for status badges
function getStatusBadge($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        default: return 'secondary';
    }
}
?>