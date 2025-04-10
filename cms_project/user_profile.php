<?php
session_start();
require 'config.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// 获取用户信息
try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error_message = "Error loading user data";
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            // 验证当前密码
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_user = $stmt->fetch();

            if (!password_verify($current_password, $current_user['password'])) {
                $error_message = "Current password is incorrect";
            } else {
                // 更新用户信息
                $updates = [];
                $params = [];

                if ($username !== $user['username']) {
                    $updates[] = "username = ?";
                    $params[] = $username;
                }
                if ($email !== $user['email']) {
                    $updates[] = "email = ?";
                    $params[] = $email;
                }
                if (!empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match";
                    } else {
                        $updates[] = "password = ?";
                        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                    }
                }

                if (empty($error_message) && !empty($updates)) {
                    $params[] = $user_id;
                    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success_message = "Profile updated successfully";
                    
                    // 更新session中的用户名
                    $_SESSION['username'] = $username;
                }
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile";
        }
    } elseif (isset($_POST['logout'])) {
        // 处理注销
        session_destroy();
        header('Location: index.php');
        exit();
    } elseif (isset($_POST['delete_account'])) {
        $password = $_POST['delete_password'];
        
        try {
            // 验证密码
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_user = $stmt->fetch();
            
            if (password_verify($password, $current_user['password'])) {
                // 开始事务
                $pdo->beginTransaction();
                
                // 删除用户的评论
                $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // 删除用户
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // 提交事务
                $pdo->commit();
                
                // 清除会话
                session_destroy();
                
                // 重定向到首页
                header('Location: index.php');
                exit();
            } else {
                $error_message = "Incorrect password";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error deleting account";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - School Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">User Profile</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    Update Profile
                                </button>
                                <div>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="confirmDeleteAccount()">
                                        Delete Account
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 修改删除账号确认模态框 -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                    <form id="deleteAccountForm" method="POST" action="user_profile.php">
                        <div class="mb-3">
                            <label for="delete_password" class="form-label">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="delete_password" name="delete_password" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_account" class="btn btn-danger">
                                Delete Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmDeleteAccount() {
        new bootstrap.Modal(document.getElementById('deleteAccountModal')).show();
    }
    </script>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 