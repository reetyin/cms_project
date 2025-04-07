<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';

// gathering data for the dashboard
$stmt = $pdo->prepare("SELECT p.*, u.username 
                      FROM pages p 
                      LEFT JOIN users u ON p.user_id = u.id 
                      ORDER BY p.created_at DESC");
$stmt->execute();
$pages = $stmt->fetchAll();

// getting categories and their page counts
$stmt = $pdo->prepare("SELECT c.*, COUNT(pc.page_id) as page_count 
                      FROM categories c 
                      LEFT JOIN page_categories pc ON c.id = pc.category_id 
                      GROUP BY c.id");
$stmt->execute();
$categories = $stmt->fetchAll();

// if the user is an admin, fetch users
$users = [];
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
}
?>

<?php include('header.php'); ?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2>Dashboard</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Pages</h5>
                <p class="card-text display-4"><?php echo count($pages); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Categories</h5>
                <p class="card-text display-4"><?php echo count($categories); ?></p>
            </div>
        </div>
    </div>
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Users</h5>
                <p class="card-text display-4"><?php echo count($users); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Manage Pages</h3>
                <a href="add_page.php" class="btn btn-primary">Add New Page</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                <td><?php echo htmlspecialchars($page['username']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($page['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $page['status'] == 'published' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($page['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_page.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="view_page.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="delete_page.php?id=<?php echo $page['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this page?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Categories</h3>
                <a href="add_category.php" class="btn btn-primary">Add Category</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Pages</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo $category['page_count']; ?></td>
                                <td>
                                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="delete_category.php?id=<?php echo $category['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Users</h3>
                <a href="add_user.php" class="btn btn-primary">Add User</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['is_admin'] ? 'danger' : 'primary'; ?>">
                                        <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include('footer.php'); ?>
