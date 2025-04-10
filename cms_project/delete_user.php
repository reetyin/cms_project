<?php
session_start();
require 'config.php';

// 检查是否是管理员
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['userId'] ?? 0;

try {
    // 检查用户是否存在且不是管理员
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_admin = 0");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception("User not found or cannot delete admin user");
    }

    // 开始事务
    $pdo->beginTransaction();

    // 删除用户的评论
    $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
    $stmt->execute([$userId]);

    // 删除用户
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    // 提交事务
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // 如果出错，回滚事务
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 