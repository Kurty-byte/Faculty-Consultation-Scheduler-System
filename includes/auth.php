<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Get current user role
function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Authenticate user
function authenticateUser($email, $password) {
    $user = fetchRow("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        return true;
    }
    
    return false;
}

// Logout user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

// Require login for a page
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('danger', 'You must be logged in to access this page.');
        redirect('home.php');
    }
}

// Require specific role for a page
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        setFlashMessage('danger', 'You do not have permission to access this page.');
        
        if (hasRole('faculty')) {
            redirect('pages/faculty/dashboard.php');
        } else if (hasRole('student')) {
            redirect('pages/student/dashboard.php');
        } else {
            redirect('home.php');
        }
    }
}

// Create account hash from password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Redirect to appropriate dashboard based on role
function redirectToDashboard() {
    if (hasRole('faculty')) {
        redirect('pages/faculty/dashboard.php');
    } else if (hasRole('student')) {
        redirect('pages/student/dashboard.php');
    } else {
        redirect('home.php');
    }
}
?>