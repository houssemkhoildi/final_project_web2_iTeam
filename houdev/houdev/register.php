<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect logged-in users
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$formData = [
    'username' => '',
    'email' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $address = sanitizeInput($_POST['address']);

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        try {
            // Check if username/email exists
            $stmt = $conn->prepare("
                SELECT id FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Username or email already exists';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("
                    INSERT INTO users 
                    (username, email, password, address)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$username, $email, $hashedPassword, $address]);
                
                $_SESSION['success'] = 'Registration successful! Please login';
                header('Location: login.php');
                exit();
            }
        } catch(PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed. Please try again';
        }
    }

    // Preserve form data
    $formData = [
        'username' => $username,
        'email' => $email,
        'address' => $address
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Houdev</title>
    <?php include 'includes/header.php'; ?>
</head>
<body> 

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Create Account</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($formData['username']) ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($formData['email']) ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" 
                                       class="form-control" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" 
                                       class="form-control" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= 
                                    htmlspecialchars($formData['address']) 
                                ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Register
                                </button>
                            </div>

                            <div class="mt-3 text-center">
                                Already have an account? 
                                <a href="login.php">Login here</a>
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