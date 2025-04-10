<?php
session_start();
require 'config.php';

// 检查是否是管理员
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? 0;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $is_admin = $_POST['is_admin'] ?? 0;
    
    try {
        $pdo->beginTransaction();
        
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            throw new Exception("Username already exists");
        }
        
        // 检查邮箱是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists");
        }
        
        // 更新用户信息
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, password = ?, is_admin = ? 
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $hashed_password, $is_admin, $userId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, is_admin = ? 
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $is_admin, $userId]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 