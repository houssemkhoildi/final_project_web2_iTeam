<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect logged-in users
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['redirect_url'] ?? 'index.php'));
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        // Validate inputs
        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            try {
                // Find user by username or email
                $stmt = $conn->prepare("
                    SELECT id, username, password, is_admin 
                    FROM users 
                    WHERE username = ? OR email = ?
                ");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Regenerate session ID
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    $_SESSION['last_activity'] = time();

                    // Redirect to original URL or dashboard
                    $redirect = $_SESSION['redirect_url'] ?? 'user/dashboard.php';
                    unset($_SESSION['redirect_url']);
                    header("Location: $redirect");
                    exit();
                } else {
                    $error = 'Invalid username or password';
                }
            } catch(PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Login failed. Please try again';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Houdev</title>
    <?php include 'includes/header.php'; ?>
</head>
<body> 

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Login to Your Account</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= 
                                htmlspecialchars($_SESSION['success']); 
                                unset($_SESSION['success']);
                            ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Username or Email</label>
                                <input type="text" name="username" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($username) ?>" 
                                       required autofocus>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" 
                                       class="form-control" 
                                       required>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Login
                                </button>
                            </div>

                            <div class="text-center">
                                <a href="forgot_password.php" class="text-decoration-none">
                                    Forgot Password?
                                </a>
                            </div>

                            <hr class="my-4">

                            <div class="text-center">
                                Don't have an account? 
                                <a href="register.php" class="text-decoration-none">
                                    Register here
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>