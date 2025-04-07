<?php
session_start();
require 'config.php';
check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    try {
        // 删除相关的评论
        $stmt = $pdo->prepare("DELETE FROM comments WHERE school_id = ?");
        $stmt->execute([$id]);
        
        // 删除分类关联
        $stmt = $pdo->prepare("DELETE FROM school_categories WHERE school_id = ?");
        $stmt->execute([$id]);
        
        // 删除学校
        $stmt = $pdo->prepare("DELETE FROM schools WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "School deleted successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting school";
    }
}

header('Location: schools.php');
exit;
?>
