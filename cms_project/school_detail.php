<?php
session_start();
require 'config.php';

// Get school ID from URL
$school_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($school_id == 0) {
    header('Location: index.php');
    exit;
}

// Get school details
$stmt = $pdo->prepare("
    SELECT s.*, i.filename, i.original_name, c.name as school_type_name 
    FROM schools s 
    LEFT JOIN images i ON s.image_id = i.id 
    LEFT JOIN categories c ON s.school_type = c.id 
    WHERE s.id = ?
");
$stmt->execute([$school_id]);
$school = $stmt->fetch();

if (!$school) {
    header('Location: index.php');
    exit;
}

// Handle comment submission
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $captcha = $_POST['captcha'] ?? '';
    $content = trim($_POST['content'] ?? '');
    
    if (empty($content)) {
        $comment_error = "Comment cannot be empty";
    } elseif (empty($captcha) || !isset($_SESSION['captcha']) || 
              strtoupper($captcha) !== $_SESSION['captcha']) {
        $comment_error = "Invalid CAPTCHA";
        $_SESSION['temp_comment'] = $content;
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (school_id, user_id, content, is_approved) 
                                  VALUES (?, ?, ?, 1)");
            
            if ($stmt->execute([$school_id, $_SESSION['user_id'], $content])) {
                $comment_success = "Comment submitted successfully!";
                unset($_SESSION['temp_comment']);
            } else {
                $comment_error = "Error submitting comment";
            }
        } catch (PDOException $e) {
            $comment_error = "Error submitting comment";
            error_log("PDO Error: " . $e->getMessage());
        }
    }
    unset($_SESSION['captcha']);
}

// Get approved comments
$stmt = $pdo->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.school_id = ? AND c.is_approved = 1
    ORDER BY c.created_at DESC
");
$stmt->execute([$school_id]);
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School CMS - School Details</title>
    <link rel="stylesheet" href="index.css">

</head>
<body>

<?php include('header.php'); ?>

<!-- Main Content Area -->
<div class="container">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h1 class="card-title"><?php echo htmlspecialchars($school['title']); ?></h1>
            <h2 class="mb-0"><?php echo htmlspecialchars($school['name']); ?></h2>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <?php if ($school['filename']): ?>
                        <div class="school-image mb-4">
                            <img src="uploads/<?php echo htmlspecialchars($school['filename']); ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo htmlspecialchars($school['name']); ?>">
                        </div>
                    <?php endif; ?>
                    <table class="table">
                        <tr>
                            <th width="150">Location:</th>
                            <td><?php echo htmlspecialchars($school['location']); ?></td>
                        </tr>
                        <tr>
                            <th>School Type:</th>
                            <td><?php echo htmlspecialchars($school['school_type_name']); ?></td>
                        </tr>
                        <?php if ($school['category_name']): ?>
                        <tr>
                            <th>Category:</th>
                            <td><?php echo htmlspecialchars($school['category_name']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Application Fee:</th>
                            <td>$<?php echo number_format($school['application_fee'], 2); ?></td>
                        </tr>
                        <?php if ($school['website']): ?>
                        <tr>
                            <th>Website:</th>
                            <td>
                                <a href="<?php echo htmlspecialchars($school['website']); ?>" 
                                   target="_blank"><?php echo htmlspecialchars($school['website']); ?></a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <!-- Description -->
                    <div class="mb-4">
                        <h3>Description</h3>
                        <div class="school-description">
                            <?php echo $school['content']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="card">
        <div class="card-header">
            <h3>Comments</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Comment Form for logged-in users -->
                <form method="POST" class="mb-4">
                    <?php if ($comment_error): ?>
                        <div class="alert alert-danger"><?php echo $comment_error; ?></div>
                    <?php endif; ?>
                    <?php if ($comment_success): ?>
                        <div class="alert alert-success"><?php echo $comment_success; ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="content" class="form-label">Your Comment *</label>
                        <textarea class="form-control" id="content" name="content" rows="3" required><?php 
                            echo htmlspecialchars($_SESSION['temp_comment'] ?? ''); 
                        ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="captcha" class="form-label">CAPTCHA *</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" class="form-control" id="captcha" name="captcha" 
                                   style="width: 150px;" required>
                            <img src="generate_captcha.php" alt="CAPTCHA" 
                                 style="height: 40px; cursor: pointer;" 
                                 onclick="this.src='generate_captcha.php?'+Math.random()">
                            <small class="text-muted">(Click image to refresh)</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Comment</button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    Please <a href="login.php">login</a> to leave a comment. 
                    Don't have an account? <a href="register.php">Register here</a>.
                </div>
            <?php endif; ?>

            <!-- Comments List -->
            <?php if ($comments): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                                </small>
                            </div>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No comments yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

</body>
</html>
