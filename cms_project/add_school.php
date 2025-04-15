<?php
session_start();
require 'config.php';
require 'image_helper.php'; // 添加图片处理辅助文件
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
        try {
            $pdo->beginTransaction();
            
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            
            // Insert school data first
            $stmt = $pdo->prepare("
                INSERT INTO schools (title, location, school_type, content, 
                                  application_fee, website, user_id, slug, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            // 移除 http:// 或 https:// 前缀
            $website = preg_replace('#^https?://#', '', $website);
            
            $stmt->execute([
                $title, 
                $location, 
                $school_type, 
                $content, 
                $application_fee, 
                $website,
                $_SESSION['user_id'],
                $slug
            ]);
            
            $school_id = $pdo->lastInsertId();
            
            // Process image if uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                // 使用多文件处理函数处理上传的图片
                $target_dir = "uploads/";
                $result = process_multiple_images($_FILES['image'], $target_dir, 800, 600, 80);
                
                if ($result['success']) {
                    $filename = $result['filename'];
                    $original_name = $_FILES['image']['name'][0];
                    $mime_type = $_FILES['image']['type'][0];
                    
                    // Insert image record
                    $stmt = $pdo->prepare("
                        INSERT INTO images (filename, original_name, mime_type, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$filename, $original_name, $mime_type]);
                    $image_id = $pdo->lastInsertId();
                    
                    // Update school with image_id
                    $stmt = $pdo->prepare("UPDATE schools SET image_id = ? WHERE id = ?");
                    $stmt->execute([$image_id, $school_id]);
                } else {
                    // 记录图片处理错误，但不中断整个流程
                    $error = "Image processing error: " . $result['error'];
                }
            }
            
            // Add category association if selected
            if ($category_id > 0) {
                $stmt = $pdo->prepare("INSERT INTO school_categories (school_id, category_id) VALUES (?, ?)");
                $stmt->execute([$school_id, $category_id]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "School added successfully";
            header('Location: schools.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            if (isset($target_file) && file_exists($target_file)) {
                unlink($target_file); // Delete uploaded file if error occurs
            }
            $error = "Error: " . $e->getMessage();
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
                                <label for="image" class="form-label">School Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image[]" multiple>
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

<!-- 在 </body> 标签前添加 JavaScript -->
<script>
// 添加文件验证
document.getElementById('image').addEventListener('change', function(e) {
    const files = e.target.files;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    for (let file of files) {
        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG, PNG, GIF and WebP images are allowed');
            this.value = '';
            return;
        }
    }
});

// 添加拖放功能
const dropZone = document.getElementById('image');
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.stopPropagation();
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const files = e.dataTransfer.files;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    for (let file of files) {
        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG, PNG, GIF and WebP images are allowed');
            return;
        }
    }
    
    const dataTransfer = new DataTransfer();
    for (let file of files) {
        dataTransfer.items.add(file);
    }
    dropZone.files = dataTransfer.files;
});
</script>
</body>
</html>
