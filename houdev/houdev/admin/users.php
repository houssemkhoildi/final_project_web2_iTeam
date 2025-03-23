<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_admin'])) {
        handleToggleAdmin();
    }
}

if (isset($_GET['delete'])) {
    handleDeleteUser();
}

// Fetch all users
$users = $conn->query("
    SELECT id, username, email, created_at, is_admin 
    FROM users
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function handleToggleAdmin() {
    global $conn;
    
    $userId = sanitizeInput($_POST['user_id']);
    $currentUser = $_SESSION['user_id'];
    
    // Prevent self-modification
    if ($userId == $currentUser) {
        $_SESSION['error'] = "You cannot modify your own admin status!";
        return;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = "User privileges updated successfully!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error updating user: " . $e->getMessage();
    }
}

function handleDeleteUser() {
    global $conn;
    
    $userId = sanitizeInput($_GET['delete']);
    $currentUser = $_SESSION['user_id'];
    
    // Prevent self-deletion
    if ($userId == $currentUser) {
        $_SESSION['error'] = "You cannot delete your own account!";
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Delete user orders
        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        $conn->commit();
        $_SESSION['success'] = "User deleted successfully!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Houdev</title>
    <?php include '../includes/header.php'; ?>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User Management</h1>
                </div>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['is_admin'] ? 'success' : 'primary' ?>">
                                                <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="submit" name="toggle_admin" class="btn btn-sm btn-warning">
                                                        <?= $user['is_admin'] ? 'Demote' : 'Promote' ?>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?= $user['id'] ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                    Delete
                                                </a>
                                            <?php endif; ?>
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