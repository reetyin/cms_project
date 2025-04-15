<?php
session_start();
require 'config.php';
require 'image_helper.php'; // 添加图片处理辅助文件

// 开启错误报告，方便调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // 确保网址以 https:// 开头
    if (!empty($website)) {
        $website = 'https://' . $website;
    }
    
    // 修改验证
    if (empty($title) || empty($location) || empty($school_type)) {
        $error = "Please fill in all required fields (Name, Location, and School Type)";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 处理图片删除
            if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                // 获取旧图片信息
                $stmt = $pdo->prepare("SELECT filename FROM images WHERE id = ?");
                $stmt->execute([$school['image_id']]);
                $old_image = $stmt->fetch();
                
                if ($old_image) {
                    // 删除物理文件
                    $old_file = 'uploads/' . $old_image['filename'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                    
                    // 删除图片记录
                    $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
                    $stmt->execute([$school['image_id']]);
                    
                    // 设置 image_id 为 null
                    $image_id = null;
                }
            } else {
                // 如果没有删除图片，保留原来的 image_id
                $image_id = $school['image_id'];
            }
            
            // 处理新图片上传
            if (!empty($_FILES['image']['name'][0])) {
                // 输出调试信息
                echo "<pre>";
                echo "开始处理图片上传...\n";
                echo "文件信息: ";
                print_r($_FILES['image']);
                echo "</pre>";
                
                // 检查文件是否有效
                if ($_FILES['image']['error'][0] !== UPLOAD_ERR_OK) {
                    $error = "文件上传失败，错误代码: " . $_FILES['image']['error'][0];
                    echo "<pre>$error</pre>";
                } else {
                    // 使用多文件处理函数处理上传的图片
                    $target_dir = "uploads/";
                    
                    // 确保目标目录存在
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                        echo "<pre>创建目标目录: $target_dir</pre>";
                    }
                    
                    // 直接使用resize_image函数处理图片
                    $temp_path = $_FILES['image']['tmp_name'][0];
                    $original_name = $_FILES['image']['name'][0];
                    $mime_type = $_FILES['image']['type'][0];
                    
                    // 生成唯一文件名
                    $filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $original_name);
                    $target_path = $target_dir . $filename;
                    
                    echo "<pre>临时文件路径: $temp_path</pre>";
                    echo "<pre>目标文件路径: $target_path</pre>";
                    
                    // 直接调用resize_image函数
                    if (resize_image($temp_path, $target_path, 800, 600, 80)) {
                        echo "<pre>图片调整大小成功</pre>";
                        
                        // 插入新图片记录
                        $stmt = $pdo->prepare("
                            INSERT INTO images (filename, original_name, mime_type, created_at) 
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$filename, $original_name, $mime_type]);
                        $image_id = $pdo->lastInsertId();
                        
                        echo "<pre>图片记录已插入，ID: $image_id</pre>";
                    } else {
                        $error = "图片处理失败";
                        echo "<pre>$error</pre>";
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
                                           value="<?php echo str_replace(['http://', 'https://'], '', htmlspecialchars($school['website'])); ?>" 
                                           placeholder="example.com">
                                </div>
                            </div>

                            <!-- Image Upload Field -->
                            <div class="mb-3">
                                <label for="image" class="form-label">School Image (Optional)</label>
                                <?php if (!empty($school['filename'])): ?>
                                    <div class="mb-2">
                                        <img src="uploads/<?php echo htmlspecialchars($school['filename']); ?>" 
                                             class="img-thumbnail" style="max-width: 200px" 
                                             alt="<?php echo htmlspecialchars($school['title']); ?>">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="markImageForRemoval()">
                                                Remove Image
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="remove_image" id="remove_image" value="0">
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image[]" multiple>
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

<!-- 在 </body> 标签前添加 JavaScript -->
<script>
function markImageForRemoval() {
    if (confirm('Are you sure you want to remove this image? Click Update School to confirm.')) {
        document.getElementById('remove_image').value = '1';
        const currentImage = document.querySelector('.mb-2');
        if (currentImage) {
            currentImage.style.display = 'none';
        }
    }
}

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
