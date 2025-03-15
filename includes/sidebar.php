<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_dir == 'crud_app' && $current_page == 'index.php') ? 'active' : ''; ?>" href="../index.php">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_dir == 'crud_app' && $current_page == 'dashboard.php') ? 'active' : ''; ?>" href="../dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_dir == 'products') ? 'active' : ''; ?>" href="../products/index.php">
                    <i class="fas fa-box"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_dir == 'categories') ? 'active' : ''; ?>" href="../categories/index.php">
                    <i class="fas fa-tag"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_dir == 'orders') ? 'active' : ''; ?>" href="../orders/index.php">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
            </li>
        </ul>
    </div>
</nav>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">