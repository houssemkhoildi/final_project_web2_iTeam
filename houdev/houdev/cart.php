<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if not logged in
redirectIfNotLoggedIn();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quantities
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $productId => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }
        $_SESSION['success'] = 'Cart updated successfully';
    }
    
    // Remove item
    if (isset($_POST['remove_item'])) {
        $productId = sanitizeInput($_POST['product_id']);
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['success'] = 'Item removed from cart';
        }
    }
}

// Calculate totals
$subtotal = 0;
$taxRate = 0.10; // 10% tax
$shipping = 0;
$cartItems = [];

if (!empty($_SESSION['cart'])) {
    // Get product details from database
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = rtrim(str_repeat('?,', count($productIds)), ',');
    
    $stmt = $conn->prepare("
        SELECT id, name, price, image, stock 
        FROM products 
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($productIds);
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $quantity = $_SESSION['cart'][$product['id']]['quantity'];
        $total = $product['price'] * $quantity;
        
        $cartItems[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity,
            'total' => $total,
            'stock' => $product['stock']
        ];
        
        $subtotal += $total;
    }
    
    // Calculate shipping
    $shipping = $subtotal > 100 ? 0 : 15;
}

$tax = $subtotal * $taxRate;
$grandTotal = $subtotal + $tax + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Houdev</title>
    <?php include '../includes/header.php'; ?>
</head>
<body>
    <?php include '../includes/navigation.php'; ?>

    <main class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Shopping Cart</h2>
                
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (empty($cartItems)): ?>
                    <div class="alert alert-info">
                        Your cart is empty. <a href="../index.php">Continue shopping</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/images/products/<?= htmlspecialchars($item['image']) ?>" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                                     class="img-thumbnail" 
                                                     style="width: 80px; height: 80px; object-fit: cover;">
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                    <small>Stock: <?= $item['stock'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td>
                                            <input type="number" 
                                                   name="quantities[<?= $item['id'] ?>]" 
                                                   value="<?= $item['quantity'] ?>" 
                                                   min="1" 
                                                   max="<?= $item['stock'] ?>" 
                                                   class="form-control" 
                                                   style="width: 80px">
                                        </td>
                                        <td>$<?= number_format($item['total'], 2) ?></td>
                                        <td>
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <button type="submit" name="remove_item" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="../index.php" class="btn btn-outline-secondary">
                                Continue Shopping
                            </a>
                            <button type="submit" name="update_cart" class="btn btn-primary">
                                Update Cart
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Order Summary</h5>
                        
                        <dl class="row">
                            <dt class="col-6">Subtotal:</dt>
                            <dd class="col-6 text-end">$<?= number_format($subtotal, 2) ?></dd>
                            
                            <dt class="col-6">Tax (<?= ($taxRate * 100) ?>%):</dt>
                            <dd class="col-6 text-end">$<?= number_format($tax, 2) ?></dd>
                            
                            <dt class="col-6">Shipping:</dt>
                            <dd class="col-6 text-end">$<?= number_format($shipping, 2) ?></dd>
                            
                            <hr>
                            
                            <dt class="col-6 fw-bold">Grand Total:</dt>
                            <dd class="col-6 text-end fw-bold">$<?= number_format($grandTotal, 2) ?></dd>
                        </dl>
                        
                        <a href="../checkout.php" 
                           class="btn btn-success w-100 <?= empty($cartItems) ? 'disabled' : '' ?>">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>