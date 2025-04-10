<?php
require_once 'config.php';
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['school_id'])) {
    $school_id = $_POST['school_id'];
    
    try {
        // Get image filename from images table
        $stmt = $pdo->prepare("
            SELECT i.filename 
            FROM schools s 
            JOIN images i ON s.image_id = i.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$school_id]);
        $image = $stmt->fetch();
        
        if ($image && $image['filename']) {
            // Delete file from uploads directory
            $file_path = 'uploads/' . $image['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Update schools table to remove image_id
            $stmt = $pdo->prepare("UPDATE schools SET image_id = NULL WHERE id = ?");
            $stmt->execute([$school_id]);
            
            // Delete from images table
            $stmt = $pdo->prepare("DELETE FROM images WHERE filename = ?");
            $stmt->execute([$image['filename']]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Image not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 