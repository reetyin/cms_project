<?php
require_once 'config.php';
session_start();

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $school_id = $_GET['id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First, get the image filename
        $stmt = $pdo->prepare("
            SELECT i.filename 
            FROM schools s 
            LEFT JOIN images i ON s.image_id = i.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
        
        // Delete the school record
        $stmt = $pdo->prepare("DELETE FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        
        // If there was an image, delete it from the images table and file system
        if ($school && $school['filename']) {
            // Delete from images table
            $stmt = $pdo->prepare("DELETE FROM images WHERE filename = ?");
            $stmt->execute([$school['filename']]);
            
            // Delete file from uploads directory
            $file_path = 'uploads/' . $school['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        header("Location: schools.php");
        exit();
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        die("Error deleting school: " . $e->getMessage());
    }
} else {
    header("Location: schools.php");
    exit();
}
