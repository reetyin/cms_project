<?php
session_start();
require 'config.php';
check_admin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = "Category name is required";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $success = "Category added successfully";
            } else {
                $error = "Error adding category";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<?php include('header.php'); ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Add Category</h2>
                    <a href="categories.php" class="btn btn-secondary">Back to Categories</a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?> 