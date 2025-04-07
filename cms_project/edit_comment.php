<?php
session_start();
require 'config.php';

// 检查管理员权限
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $content = trim($_POST['content']);
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Content cannot be empty']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
        $success = $stmt->execute([$content, $id]);
        echo json_encode(['success' => $success]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 