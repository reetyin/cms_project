<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    try {
        // 更新用户的最后登录时间
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// 清除所有会话变量
$_SESSION = array();

// 销毁会话 cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// 销毁会话
session_destroy();

// Set flash message for successful logout
session_start();
$_SESSION['flash_message'] = "You have been successfully logged out.";
$_SESSION['flash_type'] = "success";

// Redirect to login page
header("Location: index.php");
exit; 