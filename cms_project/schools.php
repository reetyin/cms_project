<?php

session_start();
require 'config.php';

check_admin();

// Get all schools with their images and school types
try {
    $stmt = $pdo->prepare("
        SELECT s.*, i.filename, c.name as school_type_name 
        FROM schools s 
        LEFT JOIN images i ON s.image_id = i.id 
        LEFT JOIN categories c ON s.school_type = c.id 
        ORDER BY s.created_at DESC
    ");
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
                    <th>Application Fee</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schools as $school): ?>
                    <tr>
                        <td style="width: 100px;">
                            <?php if ($school['filename']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($school['filename']); ?>" 
                                     class="img-thumbnail" 
                                     style="width: 80px; height: 80px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($school['title']); ?>">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($school['title']); ?></td>
                        <td><?php echo htmlspecialchars($school['location']); ?></td>
                        <td><?php echo htmlspecialchars($school['school_type_name']); ?></td>
                        <td>$<?php echo number_format($school['application_fee'], 2); ?></td>
                        <td>
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
</div>

<?php include 'footer.php'; ?>
