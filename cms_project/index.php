<?php
session_start();
require 'config.php';

// Search and filter logic
$where_conditions = [];
$params = [];

// Search by name
if (!empty($_GET['search'])) {
    $where_conditions[] = "s.title LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

// Filter by school type
if (!empty($_GET['type'])) {
    $where_conditions[] = "s.school_type = ?";
    $params[] = $_GET['type'];
}

// Filter by location
if (!empty($_GET['location'])) {
    $where_conditions[] = "s.location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
}

// Build the query
$sql = "SELECT s.*, i.filename, c.name as category_name 
        FROM schools s 
        LEFT JOIN images i ON s.image_id = i.id 
        LEFT JOIN categories c ON s.school_type = c.id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}
$sql .= " ORDER BY s.created_at DESC";

// Execute the query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schools = $stmt->fetchAll();

// Get all school types from categories table
$types_query = "SELECT id, name FROM categories ORDER BY name";
$school_types = $pdo->query($types_query)->fetchAll();

// Get all locations
$locations_query = "SELECT DISTINCT location FROM schools WHERE location IS NOT NULL ORDER BY location";
$locations = $pdo->query($locations_query)->fetchAll(PDO::FETCH_COLUMN);

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
                    <?php foreach ($school_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" 
                                <?php echo (isset($_GET['type']) && $_GET['type'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Location Filter -->
            <div class="col-md-3">
                <select name="location" class="form-select">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo htmlspecialchars($location); ?>"
                                <?php echo (isset($_GET['location']) && $_GET['location'] == $location) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                        <th>Application&nbsp;Fee</th>
                        <th>Website</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schools as $school): ?>
                        <tr>
                            <td style="width: 160px; height: 50px;">
                                <?php if ($school['filename']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($school['filename']); ?>" 
                                         class="img-thumbnail" 
                                         style="width: 160px; height: 50px; object-fit: cover;"
                                         alt="<?php echo htmlspecialchars($school['title']); ?>">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($school['title']); ?></td>
                            <td><?php echo htmlspecialchars($school['location']); ?></td>
                            <td><?php echo htmlspecialchars($school['category_name']); ?></td>
                            <td>$<?php echo number_format($school['application_fee'], 2); ?></td>
                            <td>
                                <?php if (!empty($school['website'])): ?>
                                    <a href="<?php echo htmlspecialchars($school['website']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($school['website']); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($school['updated_at'])); ?></td>
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
