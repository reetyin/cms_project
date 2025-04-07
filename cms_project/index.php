<?php
session_start();
require 'config.php';

// Search and filter logic
$where_conditions = [];
$params = [];

// Search by name
if (!empty($_GET['search'])) {
    $where_conditions[] = "(s.title LIKE ? OR s.location LIKE ?)";
    $params[] = "%{$_GET['search']}%";
    $params[] = "%{$_GET['search']}%";
}

// Filter by school type
if (!empty($_GET['type'])) {
    $where_conditions[] = "c.name = ?";
    $params[] = $_GET['type'];
}

// Filter by location
if (!empty($_GET['location'])) {
    $where_conditions[] = "location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
}

// Build the query
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$sql = "SELECT s.*, i.filename, c.name as school_type_name 
        FROM schools s 
        LEFT JOIN images i ON s.image_id = i.id 
        LEFT JOIN categories c ON s.school_type = c.id 
        $where_clause 
        ORDER BY s.created_at DESC";

// Execute the query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schools = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading schools: " . $e->getMessage();
}

// 获取所有学校类型
$types_query = "SELECT DISTINCT school_type FROM schools ORDER BY school_type";
$school_types = $pdo->query($types_query)->fetchAll(PDO::FETCH_COLUMN);

// 获取所有分类
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories = $pdo->query($categories_query)->fetchAll();

// 生成相反的排序顺序
$reverse_order = $order === 'asc' ? 'desc' : 'asc';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School CMS - Homepage</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>
<!-- Include Header -->
<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Schools List</h2>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <a href="add_school.php" class="btn btn-primary">Add New School</a>
        <?php endif; ?>
    </div>

    <!-- Search and Filter Form -->
    <form action="" method="GET" class="mb-4">
        <div class="row g-3">
            <!-- Search by name -->
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by name" 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <!-- School Type Filter -->
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All School Types</option>
                    <option value="Public" <?php echo (isset($_GET['type']) && $_GET['type'] === 'Public') ? 'selected' : ''; ?>>Public</option>
                    <option value="Private" <?php echo (isset($_GET['type']) && $_GET['type'] === 'Private') ? 'selected' : ''; ?>>Private</option>
                    <option value="Charter" <?php echo (isset($_GET['type']) && $_GET['type'] === 'Charter') ? 'selected' : ''; ?>>Charter</option>
                </select>
            </div>

            <!-- Location Filter -->
            <div class="col-md-3">
                <input type="text" name="location" class="form-control" 
                       placeholder="Filter by location" 
                       value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
            </div>

            <!-- Submit Button -->
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </div>
    </form>

    <?php if (!empty($_GET)): ?>
        <div class="mb-3">
            <a href="index.php" class="btn btn-outline-secondary">Clear Filters</a>
        </div>
    <?php endif; ?>

    <!-- Schools List -->
    <?php if (!empty($schools)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Application Fee</th>
                        <th>Action</th>
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
                                <a href="school_detail.php?id=<?php echo $school['id']; ?>" 
                                   class="btn btn-primary btn-sm">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            No schools found matching your criteria.
        </div>
    <?php endif; ?>
</div>

<!-- Include Footer -->
<?php include 'footer.php'; ?>

</body>
</html>
