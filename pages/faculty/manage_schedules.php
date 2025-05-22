<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Redirect to new consultation hours system
setFlashMessage('info', 'Schedule management has been updated! You can now set consultation hours and manage breaks more efficiently.');
redirect('pages/faculty/consultation_hours.php');

// This file now serves as a redirect to the new consultation hours system
// The old schedule management has been replaced with a more comprehensive system
?>