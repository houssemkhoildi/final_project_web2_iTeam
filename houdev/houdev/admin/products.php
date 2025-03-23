<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        handleAddProduct();
    } elseif (isset($_POST['update_product'])) {
        handleUpdateProduct();
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    handleDeleteProduct();
}

// Fetch products and categories
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Check if editing product
$editProduct = null;
if (isset($_GET['edit'])) {
    $editProduct = getProductById($_GET['edit']);
}

function handleAddProduct() {
    global $conn;
    
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = sanitizeInput($_POST['price']);
    $category = sanitizeInput($_POST['category']);
    $stock = sanitizeInput($_POST['stock']);
    $image = uploadProductImage();

    if ($image) {
        try {
            $stmt = $conn->prepare("INSERT INTO products 
                (name, description, price, category_id, image, stock)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category, $image, $stock]);
            $_SESSION['success'] = "Product added successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error adding product: " . $e->getMessage();
        }
    }
}

function handleUpdateProduct() {
    global $conn;
    
    $id = sanitizeInput($_POST['id']);
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = sanitizeInput($_POST['price']);
    $category = sanitizeInput($_POST['category']);
    $stock = sanitizeInput($_POST['stock']);
    
    // Handle image update
    $image = $_POST['existing_image'];
    if (!empty($_FILES['image']['name'])) {
        deleteProductImage($image);
        $image = uploadProductImage();
    }

    try {
        $stmt = $conn->prepare("UPDATE products SET
            name = ?,
            description = ?,
            price = ?,
            category_id = ?,
            image = ?,
            stock = ?
            WHERE id = ?");
        $stmt->execute([$name, $description, $price, $category, $image, $stock, $id]);
        $_SESSION['success'] = "Product updated successfully!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error updating product: " . $e->getMessage();
    }
}

function handleDeleteProduct() {
    global $conn;
    
    $id = sanitizeInput($_GET['delete']);
    $product = getProductById($id);
    
    if ($product) {
        try {
            // Delete product image
            deleteProductImage($product['image']);
            
            // Delete product from database
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Product deleted successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
        }
    }
}

function getProductById($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function uploadProductImage() {
    $targetDir = "../assets/images/products/";
    $fileName = basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . uniqid() . "_" . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if image file is actual image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if (!$check) {
        $_SESSION['error'] = "File is not an image.";
        return false;
    }

    // Check file size (max 2MB)
    if ($_FILES["image"]["size"] > 2000000) {
        $_SESSION['error'] = "Image is too large (max 2MB).";
        return false;
    }

    // Allow certain file formats
    $allowedFormats = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($imageFileType, $allowedFormats)) {
        $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        return false;
    }

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        return $targetFile;
    } else {
        $_SESSION['error'] = "Error uploading image.";
        return false;
    }
}

function deleteProductImage($imagePath) {
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management - Houdev</title>
    <?php include '../includes/header.php'; ?>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h1>
                </div>

                <!-- Product Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if ($editProduct): ?>
                                <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>Product Name</label>
                                        <input type="text" name="name" class="form-control" 
                                            value="<?= $editProduct['name'] ?? '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="3" 
                                            required><?= $editProduct['description'] ?? '' ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>Price</label>
                                        <input type="number" step="0.01" name="price" class="form-control" 
                                            value="<?= $editProduct['price'] ?? '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Category</label>
                                        <select name="category" class="form-select" required>
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" 
                                                    <?= ($editProduct['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                    <?= $cat['name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Stock Quantity</label>
                                        <input type="number" name="stock" class="form-control" 
                                            value="<?= $editProduct['stock'] ?? '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Product Image</label>
                                        <input type="file" name="image" class="form-control" 
                                            accept="image/*" <?= !$editProduct ? 'required' : '' ?>>
                                        <?php if ($editProduct): ?>
                                            <small class="text-muted">Current image: 
                                                <a href="<?= $editProduct['image'] ?>" target="_blank">
                                                    <?= basename($editProduct['image']) ?>
                                                </a>
                                            </small>
                                            <input type="hidden" name="existing_image" 
                                                value="<?= $editProduct['image'] ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="<?= $editProduct ? 'update_product' : 'add_product' ?>" 
                                class="btn btn-success">
                                <?= $editProduct ? 'Update Product' : 'Add Product' ?>
                            </button>
                            <?php if ($editProduct): ?>
                                <a href="products.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products as $product): ?>
                                    <tr>
                                        <td><?= $product['id'] ?></td>
                                        <td>
                                            <img src="<?= $product['image'] ?>" 
                                                alt="<?= $product['name'] ?>" 
                                                style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td><?= $product['name'] ?></td>
                                        <td>$<?= number_format($product['price'], 2) ?></td>
                                        <td><?= $product['stock'] ?></td>
                                        <td>
                                            <?= $categories[array_search($product['category_id'], 
                                                array_column($categories, 'id'))]['name'] ?? 'N/A' ?>
                                        </td>
                                        <td>
                                            <a href="products.php?edit=<?= $product['id'] ?>" 
                                               class="btn btn-sm btn-warning">Edit</a>
                                            <a href="products.php?delete=<?= $product['id'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure?')">Delete</a>
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