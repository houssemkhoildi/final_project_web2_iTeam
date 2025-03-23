<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Get sale products
$saleProducts = [];
try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS category_name,
               ROUND(p.price * (1 - p.discount_percent / 100), 2) AS sale_price
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.discount_percent > 0
        ORDER BY p.discount_percent DESC
        LIMIT 12
    ");
    $stmt->execute();
    $saleProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $saleProducts = [];
}

// Get sale categories
$saleCategories = [];
try {
    $stmt = $conn->query("
        SELECT c.*, COUNT(p.id) AS sale_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.discount_percent > 0
        GROUP BY c.id
        HAVING sale_count > 0
        ORDER BY sale_count DESC
        LIMIT 4
    ");
    $saleCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $saleCategories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summer Tech Sale - Houdev</title>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <style>
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        .original-price {
            text-decoration: line-through;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <main class="container-fluid p-0">
        <!-- Sale Header -->
        <section class="bg-danger text-white py-5">
            <div class="container text-center">
                <h1 class="display-4 mb-3">Summer Tech Sale</h1>
                <div class="countdown-timer mb-4" id="countdown">
                    <div class="d-inline-block mx-2">
                        <span id="days">00</span>d
                    </div>
                    <div class="d-inline-block mx-2">
                        <span id="hours">00</span>h
                    </div>
                    <div class="d-inline-block mx-2">
                        <span id="minutes">00</span>m
                    </div>
                    <div class="d-inline-block mx-2">
                        <span id="seconds">00</span>s
                    </div>
                </div>
                <p class="lead">Huge discounts on selected items - Limited time only!</p>
            </div>
        </section>

        <!-- Sale Categories -->
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="mb-4 text-center">Hot Deals by Category</h2>
                <div class="row g-4">
                    <?php foreach ($saleCategories as $category): ?>
                    <div class="col-md-3">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                                <p class="text-muted"><?= $category['sale_count'] ?> items on sale</p>
                                <a href="category.php?id=<?= $category['id'] ?>&sale=1" 
                                   class="btn btn-outline-danger">
                                    View Deals
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Sale Products Grid -->
        <section class="py-5">
            <div class="container">
                <h2 class="mb-4 text-center">Featured Sale Items</h2>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php foreach ($saleProducts as $product): ?>
                    <div class="col">
                        <div class="card h-100 shadow position-relative">
                            <span class="discount-badge badge bg-danger fs-5">
                                -<?= $product['discount_percent'] ?>%
                            </span>
                            <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </p>
                                <div class="price-container">
                                    <span class="original-price">
                                        $<?= number_format($product['price'], 2) ?>
                                    </span>
                                    <h4 class="text-danger d-inline ms-2">
                                        $<?= number_format($product['sale_price'], 2) ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="d-grid gap-2">
                                    <a href="products/product.php?id=<?= $product['id'] ?>" 
                                       class="btn btn-outline-primary">
                                        View Details
                                    </a>
                                    <?php if ($product['stock'] > 0): ?>
                                    <form action="cart/add_to_cart.php" method="POST">
                                        <input type="hidden" name="csrf_token" 
                                               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="product_id" 
                                               value="<?= $product['id'] ?>">
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($saleProducts)): ?>
                    <div class="alert alert-info text-center mt-4">No sale items currently available</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Limited Time Banner -->
        <section class="bg-warning py-4">
            <div class="container text-center">
                <h3 class="mb-3">Hurry! Sale Ends Soon</h3>
                <div class="countdown-timer" id="countdown2"></div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // Countdown Timer
        function updateCountdown() {
            const saleEnd = new Date('2023-08-31T23:59:59').getTime();
            const now = new Date().getTime();
            const distance = saleEnd - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').innerHTML = days.toString().padStart(2, '0');
            document.getElementById('hours').innerHTML = hours.toString().padStart(2, '0');
            document.getElementById('minutes').innerHTML = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').innerHTML = seconds.toString().padStart(2, '0');

            if (distance < 0) {
                clearInterval(countdownTimer);
                document.getElementById('countdown').innerHTML = "Sale Ended!";
            }
        }

        // Update every second
        const countdownTimer = setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call
    </script>
</body>
</html>