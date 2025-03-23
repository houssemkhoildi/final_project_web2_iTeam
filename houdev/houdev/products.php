<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Get product ID from URL
$productId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : 0;

try {
    // Get product details
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header("HTTP/1.0 404 Not Found");
        include '../404.php';
        exit();
    }

    // Get product images (assuming multiple images stored in JSON array)
    $images = json_decode($product['images']) ?: [$product['image']];

    // Get related products
    $relatedStmt = $conn->prepare("
        SELECT * FROM products 
        WHERE category_id = ? AND id != ?
        ORDER BY RAND()
        LIMIT 4
    ");
    $relatedStmt->execute([$product['category_id'], $productId]);
    $relatedProducts = $relatedStmt->fetchAll();

    // Get product reviews
    $reviewStmt = $conn->prepare("
        SELECT r.*, u.username 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE product_id = ?
        ORDER BY created_at DESC
    ");
    $reviewStmt->execute([$productId]);
    $reviews = $reviewStmt->fetchAll();

    // Calculate average rating
    $ratingStmt = $conn->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
        FROM reviews 
        WHERE product_id = ?
    ");
    $ratingStmt->execute([$productId]);
    $ratings = $ratingStmt->fetch();

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading product details";
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Houdev</title>
    <?php include '../includes/header.php'; ?>
</head>
<body>
    <?php include '../includes/navigation.php'; ?>

    <main class="container mt-5">
        <!-- Product Main Section -->
        <div class="row">
            <!-- Product Images -->
            <div class="col-md-6">
                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($image) ?>" 
                                 class="d-block w-100" 
                                 alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
                
                <!-- Thumbnails -->
                <div class="row mt-3 g-2">
                    <?php foreach ($images as $index => $image): ?>
                    <div class="col-3">
                        <img src="<?= htmlspecialchars($image) ?>" 
                             class="img-thumbnail cursor-pointer"
                             style="height: 100px; object-fit: cover;"
                             onclick="$('#productCarousel').carousel(<?= $index ?>)" 
                             alt="Thumbnail <?= $index + 1 ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-md-6">
                <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                
                <!-- Rating -->
                <div class="d-flex align-items-center mb-3">
                    <div class="rating-stars">
                        <?php $avgRating = $ratings['avg_rating'] ?: 0; ?>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?= $i <= $avgRating ? '-fill' : '' ?> text-warning"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="ms-2 text-muted">
                        (<?= $ratings['review_count'] ?> reviews)
                    </span>
                </div>

                <!-- Price -->
                <h3 class="text-danger mb-4">$<?= number_format($product['price'], 2) ?></h3>

                <!-- Stock Status -->
                <p class="<?= $product['stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $product['stock'] > 0 ? "In Stock ({$product['stock']} available)" : 'Out of Stock' ?>
                </p>

                <!-- Add to Cart -->
                <form action="../cart/add_to_cart.php" method="POST" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="quantity" class="col-form-label">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" name="quantity" id="quantity" 
                                   class="form-control" 
                                   value="1" min="1" max="<?= $product['stock'] ?>"
                                   <?= $product['stock'] < 1 ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-lg"
                                <?= $product['stock'] < 1 ? 'disabled' : '' ?>>
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Product Details Accordion -->
                <div class="accordion" id="productAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#descriptionCollapse">
                                Description
                            </button>
                        </h2>
                        <div id="descriptionCollapse" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <?= nl2br(htmlspecialchars($product['description'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#specsCollapse">
                                Specifications
                            </button>
                        </h2>
                        <div id="specsCollapse" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <!-- Add specifications data if available -->
                                <dl class="row">
                                    <dt class="col-sm-4">Category</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($product['category_name']) ?></dd>
                                    
                                    <dt class="col-sm-4">SKU</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($product['sku'] ?? 'N/A') ?></dd>
                                    
                                    <dt class="col-sm-4">Weight</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($product['weight'] ?? 'N/A') ?> lbs</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <section class="mt-5">
            <h3>Customer Reviews</h3>
            
            <!-- Review Form -->
            <?php if (isLoggedIn()): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5>Write a Review</h5>
                    <form action="../reviews/submit_review.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="product_id" value="<?= $productId ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-stars">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" 
                                           name="rating" id="rating<?= $i ?>" 
                                           value="<?= $i ?>" required>
                                    <label class="form-check-label" for="rating<?= $i ?>">
                                        <?= $i ?> Stars
                                    </label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Review Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Review</label>
                            <textarea name="content" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                Please <a href="../login.php">login</a> to write a review
            </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <div class="mt-4">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><?= htmlspecialchars($review['title']) ?></h5>
                                <small class="text-muted">
                                    <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?> text-warning"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="card-text"><?= nl2br(htmlspecialchars($review['content'])) ?></p>
                            <small class="text-muted">By <?= htmlspecialchars($review['username']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">No reviews yet. Be the first to review this product!</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Related Products -->
        <?php if (count($relatedProducts) > 0): ?>
        <section class="mt-5">
            <h3>Related Products</h3>
            <div class="row row-cols-1 row-cols-md-4 g-4">
                <?php foreach ($relatedProducts as $related): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($related['image']) ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($related['name']) ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="product.php?id=<?= $related['id'] ?>" 
                                   class="text-decoration-none">
                                    <?= htmlspecialchars($related['name']) ?>
                                </a>
                            </h5>
                            <h6 class="text-danger">$<?= number_format($related['price'], 2) ?></h6>
                            <a href="product.php?id=<?= $related['id'] ?>" 
                               class="btn btn-outline-primary">View Product</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>