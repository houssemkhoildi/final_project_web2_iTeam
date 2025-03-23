<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Get featured products
$featuredProducts = [];
try {
    $stmt = $conn->query("
        SELECT p.*, c.name AS category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.featured = 1
        ORDER BY p.created_at DESC
        LIMIT 8
    ");
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $featuredProducts = []; // Ensure empty array on error
}

// Get popular categories
$categories = [];
try {
    $stmt = $conn->query("
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY product_count DESC
        LIMIT 6
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $categories = []; // Ensure empty array on error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Houdev - Your Tech Marketplace</title>
    <?php include __DIR__ . '/includes/header.php'; ?>
</head>
<body>
 

    <main class="container-fluid p-0">
        <!-- Hero Section -->
        <section class="hero-section bg-dark text-white py-5">
            <div class="container text-center">
                <h1 class="display-4 mb-4">Welcome to Houdev</h1>
                <p class="lead mb-4">Your one-stop shop for the latest tech gadgets</p>
                <a href="#featured-products" class="btn btn-primary btn-lg">Shop Now</a>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="mb-4 text-center">Popular Categories</h2>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-6 g-4">
                    <?php foreach ($categories as $category): ?>
                    <div class="col">
                        <a href="category.php?id=<?= htmlspecialchars($category['id']) ?>" 
                           class="card h-100 text-decoration-none text-dark">
                            <div class="card-body text-center">
                                <i class="bi bi-gear fs-1 mb-3"></i>
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                                <small class="text-muted"><?= htmlspecialchars($category['product_count']) ?> items</small>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section id="featured-products" class="py-5">
            <div class="container">
                <h2 class="mb-4 text-center">Featured Products</h2>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php foreach ($featuredProducts as $product): ?>
                    <div class="col">
                        <div class="card h-100 shadow">
                            <?php
                            $imagePath = 'assets/images/products/' . htmlspecialchars($product['image']);
                            $defaultImage = 'assets/images/placeholder.jpg';
                            ?>
                            <img src="<?= file_exists($imagePath) ? $imagePath : $defaultImage ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="text-danger">$<?= number_format($product['price'], 2) ?></h5>
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="d-grid gap-2">
                                    <a href="products/product.php?id=<?= htmlspecialchars($product['id']) ?>" 
                                       class="btn btn-outline-primary">
                                        View Details
                                    </a>
                                    <?php if ($product['stock'] > 0): ?>
                                    <form action="cart/add_to_cart.php" method="POST">
                                        <input type="hidden" name="csrf_token" 
                                               value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="product_id" 
                                               value="<?= htmlspecialchars($product['id']) ?>">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($featuredProducts)): ?>
                    <div class="alert alert-info text-center mt-4">No featured products found</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Promo Banner -->
        <section class="bg-primary text-white py-5">
            <div class="container text-center">
                <h3 class="mb-4">Summer Tech Sale!</h3>
                <p class="lead mb-4">Up to 50% off selected items</p>
                <a href="sale.php" class="btn btn-light btn-lg">View Sale</a>
            </div>
        </section>
    </main>

    <?php 
    // Check if footer file exists before including
    $footerFile = __DIR__ . '/includes/footer.php';
    if (file_exists($footerFile)) {
        include $footerFile;
    } else {
        error_log("Footer file not found: $footerFile");
    }
    ?>
</body>
</html>