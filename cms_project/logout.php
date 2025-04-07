<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Set flash message for successful logout
session_start();
$_SESSION['flash_message'] = "You have been successfully logged out.";
$_SESSION['flash_type'] = "success";

// Redirect to login page
header("Location: index.php");
exit; 