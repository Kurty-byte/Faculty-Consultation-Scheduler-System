<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title for SEO/accessibility
$pageTitle = 'Schedule Management - Redirecting';

// Improved redirect message
setFlashMessage('info', 'Schedule management has been moved! You can now manage your consultation hours and breaks from the new system.');

// Redirect to the new consultation hours page
redirect('pages/faculty/consultation_hours.php');
?>