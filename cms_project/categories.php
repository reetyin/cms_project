<?php
session_start();
require 'config.php';
check_admin();

$error = '';
$success = '';

// 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = "Category name is required";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $success = "Category added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error adding category: " . $e->getMessage();
        }
    }
}

// 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = (int)$_POST['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        $error = "Error deleting category";
    }
}

// 
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<?php include('header.php'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Categories</h2>
        <a href="add_category.php" class="btn btn-primary">Add New Category</a>
    </div>

    <?php if ($categories): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td>
                                <a href="edit_category.php?id=<?php echo $category['id']; ?>" 
                                   class="btn btn-primary btn-sm">Edit</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="delete" value="<?php echo $category['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to delete this category?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No categories found.</p>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?> 