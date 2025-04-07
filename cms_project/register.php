<?php
session_start();
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists";
            } else {
                // 检查邮箱是否已存在
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email already exists";
                } else {
                    // 创建新用户
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password]);
                    
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['is_admin'] = 0;
                    
                    header("Location: index.php");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<?php include('header.php'); ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Register</h2>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <a href="login.php">Click here to login</a>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Register</button>
                        <a href="login.php" class="btn btn-link">Already have an account? Login</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?> 