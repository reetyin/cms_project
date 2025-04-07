<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';

$error = '';
$success = '';

// Fetch categories for the dropdown
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    
    // Create a slug from the title
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Process uploaded image if any
    $image_id = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Image upload processing code here
        // Set $image_id to the ID of the uploaded image
    }
    
    // Insert the page
    $stmt = $pdo->prepare("INSERT INTO pages (title, content, slug, user_id, image_id) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$title, $content, $slug, $_SESSION['user_id'], $image_id]);
    
    if ($result) {
        $page_id = $pdo->lastInsertId();
        
        // Add page to category if selected
        if ($category_id) {
            $stmt = $pdo->prepare("INSERT INTO page_categories (page_id, category_id) VALUES (?, ?)");
            $stmt->execute([$page_id, $category_id]);
        }
        
        $success = "Page created successfully!";
    } else {
        $error = "Failed to create page. Please try again.";
    }
}
?>

<?php include('header.php'); ?>

<!-- Include TinyMCE -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#content',
    plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak',
    toolbar_mode: 'floating',
    height: 400
  });
</script>

<div class="container">
    <h2>Add New Page</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="content">Content:</label>
            <textarea class="form-control" id="content" name="content"></textarea>
        </div>
        
        <div class="form-group">
            <label for="category_id">Category:</label>
            <select class="form-control" id="category_id" name="category_id">
                <option value="">Select a category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="image">Featured Image:</label>
            <input type="file" class="form-control-file" id="image" name="image">
        </div>
        
        <button type="submit" class="btn btn-primary">Create Page</button>
    </form>
</div>

<?php include('footer.php'); ?> 