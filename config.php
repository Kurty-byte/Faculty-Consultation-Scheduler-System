<?php
// Start session
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fcss_database');

// Site configuration
define('SITE_NAME', 'Faculty Consultation Scheduler System');
define('BASE_URL', 'http://localhost/fcss/');
define('MIN_CANCEL_HOURS', 24); // Minimum hours before appointment that allows cancellation

// Time formats
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include essential files
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Function to redirect
function redirect($page) {
    header('Location: ' . BASE_URL . $page);
    exit;
}
?>