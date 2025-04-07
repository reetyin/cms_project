<?php
session_start();
require 'config.php';
check_admin();

if (!isset($_FILES['file'])) {
    die(json_encode(['error' => 'No file uploaded']));
}

$file = $_FILES['file'];
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed_types)) {
    die(json_encode(['error' => 'Only JPG, JPEG & PNG files are allowed']));
}

if ($file['size'] > $max_size) {
    die(json_encode(['error' => 'File size must be less than 5MB']));
}

$filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $file['name']);

try {
    $pdo->beginTransaction();
    
    // 插入图片记录
    $stmt = $pdo->prepare("INSERT INTO images (filename, original_name) VALUES (?, ?)");
    $stmt->execute([$filename, $file['name']]);
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], 'uploads/' . $filename)) {
        throw new Exception("Failed to upload file");
    }
    
    $pdo->commit();
    
    echo json_encode([
        'location' => 'uploads/' . $filename
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
} 