<?php
require 'config.php';

try {
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES ('charter')");
    $stmt->execute();
    echo "Charter school type added successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 