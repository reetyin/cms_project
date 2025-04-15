<?php
session_start();
require 'config.php';

// Add these lines after session_start() and before the search logic
$items_per_page = 10; // Number of schools per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Search and filter logic
$where_conditions = [];
$params = [];

// Search by name
if (!empty($_GET['search'])) {
    $where_conditions[] = "(s.title LIKE ? OR s.short_name LIKE ?)";
    $params[] = "%" . $_GET['search'] . "%";
    $params[] = "%" . $_GET['search'] . "%";
}


// Filter by school type
if (!empty($_GET['type'])) {
    $where_conditions[] = "s.school_type = ?";
    $params[] = $_GET['type'];
}

// Filter by location
if (!empty($_GET['location']) && empty($_GET['province_code'])) {
    $where_conditions[] = "s.location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
} 
// 如果同时提供了省份代码，则使用OR查询两者
elseif (!empty($_GET['location']) && !empty($_GET['province_code'])) {
    $where_conditions[] = "(s.location LIKE ? OR s.location LIKE ?)";
    $params[] = "%" . $_GET['location'] . "%";
    $params[] = "%" . $_GET['province_code'] . "%";
}

if (!empty($_GET['fee_range'])) {
    $range = $_GET['fee_range'];

    if (strpos($range, '-') !== false) {
        $fee_range = explode('-', $range);
        $where_conditions[] = "s.application_fee BETWEEN ? AND ?";
        $params[] = floatval($fee_range[0]);
        $params[] = floatval($fee_range[1]);
    } elseif (strpos($range, 'min') === 0) {
        $min_fee = floatval(substr($range, 3)); // e.g. 'min150' -> 150
        $where_conditions[] = "s.application_fee >= ?";
        $params[] = $min_fee;
    }
}

// Build the query
$sql = "SELECT s.*, i.filename, c.name as category_name 
        FROM schools s 
        LEFT JOIN images i ON s.image_id = i.id 
        LEFT JOIN categories c ON s.school_type = c.id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// 添加排序逻辑
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'name_asc':
            $sql .= " ORDER BY s.title ASC";
            break;
        case 'name_desc':
            $sql .= " ORDER BY s.title DESC";
            break;
        case 'fee_asc':
            $sql .= " ORDER BY s.application_fee ASC";
            break;
        case 'fee_desc':
            $sql .= " ORDER BY s.application_fee DESC";
            break;
        default:
            $sql .= " ORDER BY s.created_at DESC";
    }
} else {
    $sql .= " ORDER BY s.created_at DESC";
}

// 添加分页
$sql .= " LIMIT $items_per_page OFFSET $offset";

// Modify the main SQL query to include COUNT
$count_sql = "SELECT COUNT(*) FROM schools s";
if (!empty($where_conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_schools = $count_stmt->fetchColumn();
$total_pages = ceil($total_schools / $items_per_page);

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

// Get Canadian provinces for filter
$provinces = [
    'Alberta' => 'AB',
    'British Columbia' => 'BC',
    'Manitoba' => 'MB',
    'New Brunswick' => 'NB',
    'Newfoundland and Labrador' => 'NL',
    'Northwest Territories' => 'NT',
    'Nova Scotia' => 'NS',
    'Nunavut' => 'NU',
    'Ontario' => 'ON',
    'Prince Edward Island' => 'PE',
    'Quebec' => 'QC',
    'Saskatchewan' => 'SK',
    'Yukon' => 'YT'
];

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
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <!-- School Categories -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">School Categories</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($school_types as $type): ?>
                            <a href="?type=<?php echo $type['id']; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <?php echo htmlspecialchars($type['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Filters -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Filters</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="?fee_range=0-100" class="btn btn-outline-info btn-sm mb-1">Fee: $0-$100</a>
                        <a href="?fee_range=100-150" class="btn btn-outline-info btn-sm mb-1">Fee: $100-$150</a>
                        <a href="?fee_range=min150" class="btn btn-outline-info btn-sm mb-1">Fee: $150+</a>
                        
                        <a href="?sort=name_asc" class="btn <?php echo ($_GET['sort'] ?? '') === 'name_asc' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm mb-1">
                            Name (A-Z)
                        </a>
                        <a href="?sort=name_desc" class="btn <?php echo ($_GET['sort'] ?? '') === 'name_desc' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm mb-1">
                            Name (Z-A)
                        </a>
                        <a href="?sort=fee_asc" class="btn <?php echo ($_GET['sort'] ?? '') === 'fee_asc' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm mb-1">
                            Fee (Low-High)
                        </a>
                        <a href="?sort=fee_desc" class="btn <?php echo ($_GET['sort'] ?? '') === 'fee_desc' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm mb-1">
                            Fee (High-Low)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Locations Filter -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Canadian Provinces</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($provinces as $province => $code): ?>
                            <a href="?location=<?php echo urlencode($province); ?>&province_code=<?php echo urlencode($code); ?>"
                               class="btn btn-outline-secondary btn-sm">
                                <?php echo htmlspecialchars($province); ?> (<?php echo htmlspecialchars($code); ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Search and Filter Form -->
            <form action="" method="GET" class="mb-4">
                <div class="row g-3">
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
                    <!-- Search by name -->
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name" 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
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
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>App.&nbsp;Fee</th>
                                        <th>Website</th>
                                        <th>Updated</th>
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
                                                         style="width: 80px; height: 50px; object-fit: cover;"
                                                         alt="<?php echo htmlspecialchars($school['title']); ?>">
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($school['title']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($school['location']); ?></td>
                                            <td><?php echo htmlspecialchars($school['category_name']); ?></td>
                                            <td>$<?php echo number_format($school['application_fee'], 2); ?></td>
                                            <td>
                                                <?php if (!empty($school['website'])): ?>
                                                    <a href="<?php echo htmlspecialchars($school['website']); ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 150px;">
                                                        <?php echo htmlspecialchars($school['website']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($school['updated_at'])); ?></td>
                                            <td>
                                                <a href="school_detail.php?id=<?php echo $school['id']; ?>" 
                                                   class="btn btn-primary btn-sm">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No schools found matching your criteria.
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    // 构建保留查询参数的基础 URL
                    $base_url = '?';
                    $query_params = $_GET;
                    unset($query_params['page']); // 移除当前页，稍后再加
                    $base_url .= http_build_query($query_params);
                    // Fix the URL when there are no query params
                    $connector = !empty($query_params) ? '&' : '';

                    if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $base_url . $connector . 'page=' . ($current_page - 1); ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $base_url . $connector . 'page=' . $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $base_url . $connector . 'page=' . ($current_page + 1); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Include Footer -->
<?php include 'footer.php'; ?>

</body>
</html>


