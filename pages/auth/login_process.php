<?php
// Include config file
require_once '../../config.php';

// Check if already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate form data
    if (empty($_POST['email']) || empty($_POST['password'])) {
        setFlashMessage('danger', 'Please enter both email and password.');
        redirect('home.php');
    }
    
    // Sanitize inputs
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password
    
    // Authenticate user
    if (authenticateUser($email, $password)) {
        // Redirect to appropriate dashboard
        redirectToDashboard();
    } else {
        // Authentication failed
        setFlashMessage('danger', 'Invalid email or password. Please try again.');
        redirect('login.php');
    }
} else {
    // Not a POST request, redirect to login page
    redirect('home.php');
}
?>