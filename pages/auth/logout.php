<?php
// Include config file
require_once '../../config.php';

// Check if logged in
if (isLoggedIn()) {
    // Logout user
    logoutUser();
    
    // Set flash message
    setFlashMessage('success', 'You have been successfully logged out.');
}

// Redirect to login page
redirect('index.php');
?>