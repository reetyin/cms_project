<?php
session_start();
require 'config.php';
check_admin();

// 处理评论管理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['hide'])) {
            $stmt = $pdo->prepare("UPDATE comments SET is_approved = 0 WHERE id = ?");
            $stmt->execute([(int)$_POST['hide']]);
            $_SESSION['success'] = "Comment hidden successfully";
        } elseif (isset($_POST['show'])) {
            $stmt = $pdo->prepare("UPDATE comments SET is_approved = 1 WHERE id = ?");
            $stmt->execute([(int)$_POST['show']]);
            $_SESSION['success'] = "Comment shown successfully";
        } elseif (isset($_POST['reject'])) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([(int)$_POST['reject']]);
            $_SESSION['success'] = "Comment deleted successfully";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing comment";
        error_log("Comment moderation error: " . $e->getMessage());
    }
}

// 获取所有评论
$stmt = $pdo->prepare("
    SELECT c.*, s.title as school_title, u.username 
    FROM comments c
    JOIN schools s ON c.school_id = s.id
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$comments = $stmt->fetchAll();

// 获取错误和成功消息
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>

<?php include('header.php'); ?>

<div class="container mt-4">
    <h2>Comment Management</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (empty($comments)): ?>
        <div class="alert alert-info">No comments found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Author</th>
                        <th>School</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comment['username'] ?? 'Anonymous'); ?></td>
                            <td><?php echo htmlspecialchars($comment['school_title']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($comment['content'])); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $comment['is_approved'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $comment['is_approved'] ? 'Visible' : 'Hidden'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?php if ($comment['is_approved']): ?>
                                        <button type="submit" name="hide" 
                                                value="<?php echo $comment['id']; ?>" 
                                                class="btn btn-sm btn-warning">
                                            Hide
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="show" 
                                                value="<?php echo $comment['id']; ?>" 
                                                class="btn btn-sm btn-success">
                                            Show
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" name="reject" 
                                            value="<?php echo $comment['id']; ?>" 
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this comment?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?> 