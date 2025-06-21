<?php
require_once 'includes/config.php';

// Check if user is logged in
if (is_logged_in()) {
    $user = new User($db);
    $user->logout();
    $_SESSION['success'] = "You have been logged out successfully";
}

// Redirect to home page
header('Location: index.php');
exit;
?>