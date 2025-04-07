<?php
session_start();
require 'config.php';
check_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// 获取分类信息
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        header('Location: categories.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Error loading category";
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = "Category name is required";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $id])) {
                $success = "Category updated successfully";
                $category['name'] = $name;
                $category['description'] = $description;
            } else {
                $error = "Error updating category";
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
                    <h2>Edit Category</h2>
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
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($category['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                echo htmlspecialchars($category['description']); 
                            ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?> 