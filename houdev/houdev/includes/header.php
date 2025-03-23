<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Houdev - Your Tech Marketplace' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <header class="sticky-top">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <!-- Brand/Logo -->
                <a class="navbar-brand" href="index.php">
                    <i class="bi bi-cpu"></i> Houdev
                </a>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation Links -->
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" 
                               href="index.php">
                                Home
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                Categories
                            </a>
                            <ul class="dropdown-menu">
                                <?php
                                $categories = $conn->query("SELECT * FROM categories LIMIT 8");
                                while($cat = $categories->fetch()):
                                ?>
                                    <li>
                                        <a class="dropdown-item" href="category.php?id=<?= $cat['id'] ?>">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" 
                               href="about.php">
                                About
                            </a>
                        </li>
                    </ul>

                    <!-- Search Form -->
                    <form class="d-flex mx-3" role="search" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" 
                               placeholder="Search products..." 
                               aria-label="Search"
                               name="q"
                               autocomplete="off">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>

                    <!-- User Account & Cart -->
                    <ul class="navbar-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle"></i>
                                    <?= htmlspecialchars($_SESSION['username']) ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="user/dashboard.php">
                                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                                        </a>
                                    </li>
                                    <?php if ($_SESSION['is_admin']): ?>
                                        <li>
                                            <a class="dropdown-item" href="admin/dashboard.php">
                                                <i class="bi bi-shield-lock me-2"></i> Admin Panel
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>" 
                                   href="login.php">
                                    Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'register.php' ? 'active' : '' ?>" 
                                   href="register.php">
                                    Register
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'cart.php' ? 'active' : '' ?>" 
                               href="cart.php">
                                <i class="bi bi-cart"></i>
                                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                    <span class="badge bg-danger">
                                        <?= count($_SESSION['cart']) ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-shrink-0">