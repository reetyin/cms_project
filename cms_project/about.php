<?php
// Include the database connection (if needed for any dynamic data)
require 'config.php';

// Fetch the list of schools from the database (if needed for dynamic data)
$query = "SELECT * FROM schools";
$stmt = $pdo->query($query);
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include the header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School CMS - About</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<!-- Main Content Area -->
<div class="container">
    <h2>About School CMS</h2>
    <p>Welcome to School CMS, a platform designed to manage schools effectively. Our mission is to provide an easy-to-use system for administrators and educators to handle school data efficiently.</p>

    <h3>Our Mission</h3>
    <p>Our mission is to empower schools with a simple content management system to streamline their operations and improve communication among staff and students.</p>

    <h3>Our Team</h3>
    <p>We are a group of passionate developers and educators who are dedicated to making school management easier and more efficient.</p>

    <h3>Contact Us</h3>
    <p>If you have any questions or suggestions, feel free to <a href="mailto:support@schoolcms.com">contact us</a>.</p>
</div>

<!-- Include the footer -->
<?php include 'footer.php'; ?>

</body>
</html>
