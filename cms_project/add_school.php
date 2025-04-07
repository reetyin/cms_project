<?php
session_start();
require 'config.php';
check_admin();

// Remove debug output
// error_reporting(0);

// 在文件开头获取所有学校类型
try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE name IN ('public', 'private', 'international', 'language', 'college', 'university', 'charter') ORDER BY name");
    $stmt->execute();
    $school_types = $stmt->fetchAll();
    
    // 添加调试信息
    if (empty($school_types)) {
        $error = "No school types found in database";
    }
} catch (PDOException $e) {
    $error = "Error loading school types: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $school_type = trim($_POST['school_type'] ?? '');
    $content = $_POST['content'] ?? '';
    $application_fee = floatval($_POST['application_fee'] ?? 0);
    $website = trim($_POST['website'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    $error = '';
    
    // Basic validation
    if (empty($title) || empty($location) || empty($school_type)) {
        $error = "Please fill in all required fields (Name, Location, and School Type)";
    }
    
    // Process image upload if no errors
    if (empty($error)) {
        $file = $_FILES['image'];
        $original_name = $file['name'];
        $mime_type = $file['type'];
        $filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $original_name);
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($mime_type, $allowed_types)) {
            $error = "Only JPG, JPEG & PNG files are allowed";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Create uploads directory if it doesn't exist
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $target_file = $target_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    // Insert image record
                    $stmt = $pdo->prepare("
                        INSERT INTO images (filename, original_name, mime_type, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$filename, $original_name, $mime_type]);
                    $image_id = $pdo->lastInsertId();
                    
                    // Generate slug
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
                    
                    // Insert school data
                    $stmt = $pdo->prepare("
                        INSERT INTO schools (title, location, school_type, content, image_id, 
                                          application_fee, website, user_id, slug, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    // 移除 http:// 或 https:// 前缀
                    $website = preg_replace('#^https?://#', '', $website);
                    
                    $stmt->execute([
                        $title, 
                        $location, 
                        $school_type, 
                        $content, 
                        $image_id,
                        $application_fee, 
                        $website,
                        $_SESSION['user_id'],
                        $slug
                    ]);
                    
                    $school_id = $pdo->lastInsertId();
                    
                    // Add category association if selected
                    if ($category_id > 0) {
                        $stmt = $pdo->prepare("INSERT INTO school_categories (school_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$school_id, $category_id]);
                    }
                    
                    $pdo->commit();
                    $_SESSION['success'] = "School added successfully";
                    header('Location: schools.php');
                    exit;
                } else {
                    throw new Exception("Error uploading file");
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                if (file_exists($target_file)) {
                    unlink($target_file); // Delete uploaded file if error occurs
                }
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        tinymce.init({
            selector: '#content',
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
                'template', 'paste', 'hr', 'pagebreak', 'nonbreaking', 'searchreplace',
                'visualchars', 'code', 'fullscreen', 'insertdatetime', 'media',
                'table', 'help', 'wordcount', 'emoticons', 'template', 'paste'
            ],
            toolbar: 'undo redo | formatselect | bold italic underline strikethrough | ' +
                    'alignleft aligncenter alignright alignjustify | ' +
                    'bullist numlist outdent indent | link image media table | ' +
                    'emoticons charmap | removeformat | help | fullscreen | ' +
                    'searchreplace | visualchars | code | pagebreak | nonbreaking',
            height: 500,
            menubar: 'file edit view insert format tools table help',
            branding: false,
            promotion: false,
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 14px; }',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            },
            // 添加图片上传功能
            images_upload_url: 'upload.php',
            images_upload_handler: function (blobInfo, success, failure) {
                var xhr, formData;
                xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', 'upload.php');
                xhr.onload = function() {
                    var json;
                    if (xhr.status != 200) {
                        failure('HTTP Error: ' + xhr.status);
                        return;
                    }
                    json = JSON.parse(xhr.responseText);
                    if (!json || typeof json.location != 'string') {
                        failure('Invalid JSON: ' + xhr.responseText);
                        return;
                    }
                    success(json.location);
                };
                formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                xhr.send(formData);
            }
        });
    });
</script>

<?php include('header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> School</title>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2>Add School</h2>
                        <a href="schools.php" class="btn btn-secondary">Back to Schools</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- School Name Field -->
                            <div class="mb-3">
                                <label for="title" class="form-label">School Name *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <!-- Location Field -->
                            <div class="mb-3">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>

                            <!-- School Type Field -->
                            <div class="mb-3">
                                <label for="school_type" class="form-label">School Type *</label>
                                <select class="form-select" id="school_type" name="school_type" required>
                                    <option value="">Select School Type</option>
                                    <?php foreach ($school_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Image Upload Field -->
                            <div class="mb-3">
                                <label for="image" class="form-label">Upload Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>

                            <!-- Application Fee Field -->
                            <div class="mb-3">
                                <label for="application_fee" class="form-label">Application Fee</label>
                                <input type="number" class="form-control" id="application_fee" name="application_fee" step="0.01">
                            </div>

                            <!-- Website Field -->
                            <div class="mb-3">
                                <label for="website" class="form-label">Website (Optional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">https://</span>
                                    <input type="text" class="form-control" id="website" name="website" 
                                           placeholder="example.com">
                                </div>
                                <div class="form-text">Enter domain name without http:// or https://</div>
                            </div>

                            <!-- Description Field (移到最底部) -->
                            <div class="mb-3">
                                <label for="content" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="content" name="content" rows="5"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Add School</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Include Footer -->
<?php include 'footer.php'; ?>
</body>
</html>
