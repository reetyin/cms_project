// 在保存图片时
if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
    // 使用相对路径
    $relative_path = 'uploads/schools/' . $new_filename;
    
    // 更新数据库
    $stmt = $pdo->prepare("UPDATE schools SET image_path = ? WHERE id = ?");
    $stmt->execute([$relative_path, $id]);
}