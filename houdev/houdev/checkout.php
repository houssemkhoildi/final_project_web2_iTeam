<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/links.php';
redirectIfNotLoggedIn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: checkout.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        // Get user details
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Validate cart contents
        if (empty($_SESSION['cart'])) {
            throw new Exception("Your cart is empty");
        }

        // Validate stock and calculate total
        $total = 0;
        $productIds = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $products = $conn->prepare("
            SELECT id, price, stock FROM products 
            WHERE id IN ($placeholders) FOR UPDATE
        ");
        $products->execute($productIds);
        $productData = $products->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productData as $product) {
            $cartItem = $_SESSION['cart'][$product['id']];
            if ($product['stock'] < $cartItem['quantity']) {
                throw new Exception("Insufficient stock for {$cartItem['name']}");
            }
            $total += $product['price'] * $cartItem['quantity'];
        }

        // Create order
        $stmt = $conn->prepare("
            INSERT INTO orders 
            (user_id, total, status, shipping_address)
            VALUES (?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $userId,
            $total,
            sanitizeInput($_POST['shipping_address'])
        ]);
        $orderId = $conn->lastInsertId();

        // Create order items
        $orderItems = $conn->prepare("
            INSERT INTO order_items 
            (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($productData as $product) {
            $cartItem = $_SESSION['cart'][$product['id']];
            $orderItems->execute([
                $orderId,
                $product['id'],
                $cartItem['quantity'],
                $product['price']
            ]);

            // Update product stock
            $conn->prepare("
                UPDATE products 
                SET stock = stock - ? 
                WHERE id = ?
            ")->execute([$cartItem['quantity'], $product['id']]);
        }

        // Process payment (simulated)
        $paymentSuccess = true; // Replace with actual payment processing
        if (!$paymentSuccess) {
            throw new Exception("Payment processing failed");
        }

        // Clear cart and commit transaction
        unset($_SESSION['cart']);
        $conn->commit();

        // Send confirmation email (simulated)
        // sendOrderConfirmationEmail($user['email'], $orderId);

        $_SESSION['order_id'] = $orderId;
        header("Location: order_confirmation.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: checkout.php");
        exit();
    }
}

// Get cart items
$cartItems = [];
if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $conn->prepare("
        SELECT id, name, price, image 
        FROM products 
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($productIds);
    
    while ($product = $stmt->fetch()) {
        $cartItems[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $_SESSION['cart'][$product['id']]['quantity']
        ];
    }
}

// Calculate totals
$subtotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);
$shipping = $subtotal > 100 ? 0 : 15;
$tax = $subtotal * 0.10;
$grandTotal = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Houdev</title>
    <?php include 'includes/header.php'; ?>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <main class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Checkout</h2>
                
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= 
                        htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                    ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <!-- Shipping Information -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Shipping Information</h4>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" 
                                    value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" 
                                    disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Shipping Address</label>
                                <textarea name="shipping_address" class="form-control" rows="3" required><?= 
                                    htmlspecialchars($_SESSION['address'] ?? '')
                                ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Payment Details</h4>
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" 
                                    placeholder="4242 4242 4242 4242" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Expiration Date</label>
                                    <input type="text" class="form-control" 
                                        placeholder="MM/YY" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CVC</label>
                                    <input type="text" class="form-control" 
                                        placeholder="123" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Order Summary</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartItems as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="assets/images/products/<?= $item['image'] ?>" 
                                                    alt="<?= $item['name'] ?>" 
                                                    class="img-thumbnail" 
                                                    style="width: 50px; height: 50px; object-fit: cover;">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </td>
                                            <td>$<?= number_format($item['price'], 2) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <dl class="row">
                                <dt class="col-6">Subtotal:</dt>
                                <dd class="col-6 text-end">$<?= number_format($subtotal, 2) ?></dd>
                                
                                <dt class="col-6">Shipping:</dt>
                                <dd class="col-6 text-end">$<?= number_format($shipping, 2) ?></dd>
                                
                                <dt class="col-6">Tax (10%):</dt>
                                <dd class="col-6 text-end">$<?= number_format($tax, 2) ?></dd>
                                
                                <hr>
                                
                                <dt class="col-6 fw-bold">Grand Total:</dt>
                                <dd class="col-6 text-end fw-bold">$<?= number_format($grandTotal, 2) ?></dd>
                            </dl>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>