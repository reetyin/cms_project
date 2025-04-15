<?php

session_start();
require 'config.php';

check_admin();

// Add after session_start()
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get all schools with their images and school types
try {
    // Get total count
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM schools");
    $total_schools = $count_stmt->fetchColumn();
    $total_pages = ceil($total_schools / $items_per_page);

    // Modified main query with pagination
    $stmt = $pdo->prepare("
        SELECT s.*, i.filename, c.name as school_type_name 
        FROM schools s 
        LEFT JOIN images i ON s.image_id = i.id 
        LEFT JOIN categories c ON s.school_type = c.id 
        ORDER BY s.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $schools = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading schools";
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Schools Management</h1>
        <a href="add_school.php" class="btn btn-primary">Add New School</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Application&nbsp;Fee</th>
                    <th>Website</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schools as $school): ?>
                    <tr>
                        <td style="width: 102.4px; height: 32px;">
                            <?php if (!empty($school['filename'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($school['filename']); ?>" 
                                     class="img-thumbnail" 
                                     style="width: 102.4px; height: 32px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($school['title']); ?>">
                            <?php else: ?>
                                <img src="images/default.jpg" 
                                     class="img-thumbnail"
                                     style="width: 160px; height: 80px; object-fit: cover;"
                                     alt="Default Image">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($school['title']); ?></td>
                        <td><?php echo htmlspecialchars($school['location']); ?></td>
                        <td><?php echo htmlspecialchars($school['school_type_name']); ?></td>
                        <td>$<?php echo number_format($school['application_fee'], 2); ?></td>
                        <td>
                            <?php if (!empty($school['website'])): ?>
                                <a href="<?php echo htmlspecialchars($school['website']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($school['website']); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($school['updated_at'])); ?></td>
                        <td style="display: flex; gap: 5px;">
                                <a href="edit_school.php?id=<?php echo $school['id']; ?>" 
                                    class="btn btn-sm btn-primary">Edit</a>
                                <a href="delete_school.php?id=<?php echo $school['id']; ?>" 
                                class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this school?')">Delete</a>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($current_page - 1); ?>">Previous</a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($current_page + 1); ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php include 'footer.php'; ?>
