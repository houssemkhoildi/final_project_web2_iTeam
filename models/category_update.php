<?php
// Include database and required models
include_once 'config/database.php';
include_once 'models/Category.php';

// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Instantiate database and objects
$database = new Database();
$db = $database->getConnection();
$category = new Category($db);

// Page title
$page_title = "Update Category";

// Get ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// Set ID property of category to be read
$category->id = $id;

// Read the category details
if(!$category->readOne()) {
    header("Location: categories.php");
    exit;
}

// Process form submission
if($_POST) {
    // Set category property values
    $category->name = $_POST['name'];
    $category->description = $_POST['description'];
    
    // Update the category
    if($category->update()) {
        $message = "Category was updated successfully.";
        $message_class = "success";
    } else {
        $message = "Unable to update category.";
        $message_class = "danger";
    }
}

include_once 'includes/header.php';
?>

<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Update Category</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="categories.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>
    </div>

    <?php if(isset($message)) { ?>
    <div class="alert alert-<?php echo $message_class; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php } ?>

    <!-- Update Category Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Category Information</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id={$id}"); ?>" method="post">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" id="name" value="<?php echo htmlspecialchars($category->name); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="description" rows="3"><?php echo htmlspecialchars($category->description); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>