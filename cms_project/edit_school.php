<?php
session_start();
require 'config.php';

// 移除调试信息
check_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

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

// 获取学校信息，包括图片
try {
    $stmt = $pdo->prepare("
        SELECT s.*, i.filename, i.original_name, c.name as school_type_name 
        FROM schools s 
        LEFT JOIN images i ON s.image_id = i.id 
        LEFT JOIN categories c ON s.school_type = c.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $school = $stmt->fetch();
    
    if (!$school) {
        header('Location: schools.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Error loading school";
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $school_type = trim($_POST['school_type'] ?? '');
    $content = $_POST['content'] ?? '';
    $application_fee = floatval($_POST['application_fee'] ?? 0);
    $website = trim($_POST['website'] ?? '');
    $website = preg_replace('#^https?://#', '', $website);
    
    // 修改验证
    if (empty($title) || empty($location) || empty($school_type)) {
        $error = "Please fill in all required fields (Name, Location, and School Type)";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 处理新图片上传
            $image_id = $school['image_id'];
            if (!empty($_FILES['image']['name'])) {
                $file = $_FILES['image'];
                $original_name = $file['name'];
                $mime_type = $file['type'];
                $filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $original_name);
                
                // 检查文件类型
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!in_array($mime_type, $allowed_types)) {
                    throw new Exception("Only JPG, JPEG & PNG files are allowed");
                }
                
                // 移动文件到上传目录
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $target_file = $target_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    // 插入新图片记录
                    $stmt = $pdo->prepare("
                        INSERT INTO images (filename, original_name, mime_type, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$filename, $original_name, $mime_type]);
                    $image_id = $pdo->lastInsertId();
                    
                    // 如果有旧图片，删除旧图片文件
                    if ($school['filename']) {
                        $old_file = $target_dir . $school['filename'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                }
            }
            
            // 更新 slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            
            // 更新学校信息
            $stmt = $pdo->prepare("
                UPDATE schools 
                SET title = ?, location = ?, school_type = ?, content = ?, 
                    image_id = ?, application_fee = ?, website = ?, 
                    slug = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $title,
                $location,
                $school_type,
                $content,
                $image_id,
                $application_fee,
                $website,
                $slug,
                $id
            ]);
            
            $pdo->commit();
            $success = "School updated successfully";
            
            // 重新获取学校信息
            $stmt = $pdo->prepare("
                SELECT s.*, i.filename, i.original_name, c.name as school_type_name 
                FROM schools s 
                LEFT JOIN images i ON s.image_id = i.id 
                LEFT JOIN categories c ON s.school_type = c.id 
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $school = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
            
            // 如果上传了新图片但出错，删除新上传的文件
            if (isset($target_file) && file_exists($target_file)) {
                unlink($target_file);
            }
        }
    }
}

// 获取所有分类
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 400,
            menubar: true,
            branding: false,
            promotion: false,
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 14px; }'
        });
    });
</script>

<?php include('header.php'); ?>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2>Edit School</h2>
                        <a href="schools.php" class="btn btn-secondary">Back to Schools</a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- School Name Field -->
                            <div class="mb-3">
                                <label for="title" class="form-label">School Name *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($school['title']); ?>" required>
                            </div>

                            <!-- Location Field -->
                            <div class="mb-3">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($school['location']); ?>" required>
                            </div>

                            <!-- School Type Field -->
                            <div class="mb-3">
                                <label for="school_type" class="form-label">School Type *</label>
                                <select class="form-select" id="school_type" name="school_type" required>
                                    <option value="">Select School Type</option>
                                    <?php foreach ($school_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" 
                                                <?php echo $school['school_type'] == $type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Application Fee Field -->
                            <div class="mb-3">
                                <label for="application_fee" class="form-label">Application Fee</label>
                                <input type="number" class="form-control" id="application_fee" name="application_fee" 
                                       value="<?php echo $school['application_fee']; ?>" step="0.01">
                            </div>

                            <!-- Website Field -->
                            <div class="mb-3">
                                <label for="website" class="form-label">Website (Optional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">https://</span>
                                    <input type="text" class="form-control" id="website" name="website" 
                                           value="<?php echo htmlspecialchars($school['website']); ?>" 
                                           placeholder="example.com">
                                </div>
                                <div class="form-text">Enter domain name without http:// or https://</div>
                            </div>

                            <!-- Image Upload Field (移到 Description 上面) -->
                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                <?php if ($school['filename']): ?>
                                    <div class="mb-2">
                                        <img src="uploads/<?php echo htmlspecialchars($school['filename']); ?>" 
                                             class="img-thumbnail" style="max-width: 200px" 
                                             alt="<?php echo htmlspecialchars($school['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                <label for="image" class="form-label">Upload New Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>

                            <!-- Description Field (移到底部) -->
                            <div class="mb-3">
                                <label for="content" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="content" name="content" rows="5"><?php 
                                    echo htmlspecialchars($school['content']); 
                                ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update School</button>
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
