<?php
// Start session
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fcss_database');

// Site configuration - Updated for network access
define('SITE_NAME', 'Faculty Consultation Scheduler System');
define('SYSTEM_VERSION', '2.0.0'); // Updated for consultation hours system

// Dynamic Base URL Configuration
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = '/'. basename(__DIR__) .'/';
    
    return $protocol . $host . $path;
}

define('BASE_URL', getBaseUrl());

// Appointment and Consultation Configuration
define('MIN_CANCEL_HOURS', 24); // Minimum hours before appointment that allows cancellation
define('DEFAULT_SLOT_DURATION', 30); // Default appointment duration in minutes
define('MAX_BOOKING_DAYS_AHEAD', 30); // Maximum days ahead students can book appointments
define('MIN_BOOKING_HOURS_AHEAD', 2); // Minimum hours ahead students can book appointments

// Consultation Hours Configuration
define('MAX_CONSULTATION_HOURS_PER_DAY', 12); // Maximum consultation hours per day
define('MIN_CONSULTATION_SLOT_MINUTES', 30); // Minimum slot duration (fixed at 30 minutes)
define('MAX_BREAK_DURATION_HOURS', 4); // Maximum break duration in hours
define('DEFAULT_CONSULTATION_START', '09:00'); // Default consultation start time
define('DEFAULT_CONSULTATION_END', '17:00'); // Default consultation end time

// Notification Configuration
define('NOTIFICATION_RETENTION_DAYS', 30); // Days to keep notifications before cleanup
define('MAX_NOTIFICATIONS_PER_USER', 50); // Maximum notifications per user
define('NOTIFICATION_BATCH_SIZE', 10); // Number of notifications to display at once

// System Limits and Performance
define('MAX_CONCURRENT_BOOKINGS', 100); // Maximum concurrent booking attempts
define('CACHE_DURATION_SECONDS', 300); // Cache duration for frequently accessed data (5 minutes)
define('MAX_APPOINTMENT_HISTORY_MONTHS', 12); // Months to keep appointment history

// Time formats
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F j, Y'); // Human-readable date format
define('DISPLAY_TIME_FORMAT', 'g:i A'); // Human-readable time format

// File Upload Configuration (for future enhancements)
define('MAX_FILE_SIZE_MB', 5); // Maximum file upload size in MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt']); // Allowed file extensions
define('UPLOAD_PATH', 'uploads/'); // Upload directory path

// Email Configuration (for future enhancements)
define('SMTP_ENABLED', false); // Enable/disable email notifications
define('SMTP_HOST', ''); // SMTP server host
define('SMTP_PORT', 587); // SMTP server port
define('SMTP_USERNAME', ''); // SMTP username
define('SMTP_PASSWORD', ''); // SMTP password
define('FROM_EMAIL', 'noreply@fcss.local'); // Default from email
define('FROM_NAME', 'FCSS System'); // Default from name

// Security Configuration
define('SESSION_TIMEOUT_HOURS', 8); // Session timeout in hours
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum login attempts before lockout
define('LOCKOUT_DURATION_MINUTES', 15); // Account lockout duration in minutes
define('CSRF_TOKEN_LIFETIME', 3600); // CSRF token lifetime in seconds

// Feature Flags (for future enhancements)
define('FEATURE_EMAIL_NOTIFICATIONS', false); // Enable email notifications
define('FEATURE_SMS_NOTIFICATIONS', false); // Enable SMS notifications
define('FEATURE_RECURRING_APPOINTMENTS', false); // Enable recurring appointments
define('FEATURE_APPOINTMENT_REMINDERS', true); // Enable appointment reminders
define('FEATURE_ANALYTICS_DASHBOARD', true); // Enable analytics dashboard
define('FEATURE_EXPORT_DATA', true); // Enable data export functionality

// Development and Debug Configuration
define('DEBUG_MODE', true); // Enable/disable debug mode (set to false in production)

// Error reporting (set to 0 in production)
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Include essential files
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Function to redirect
function redirect($page) {
    header('Location: ' . BASE_URL . $page);
    exit;
}

// Function to get system configuration
function getSystemConfig() {
    return [
        'site_name' => SITE_NAME,
        'version' => SYSTEM_VERSION,
        'slot_duration' => DEFAULT_SLOT_DURATION,
        'min_cancel_hours' => MIN_CANCEL_HOURS,
        'max_booking_days' => MAX_BOOKING_DAYS_AHEAD,
        'notification_retention' => NOTIFICATION_RETENTION_DAYS,
        'features' => [
            'email_notifications' => FEATURE_EMAIL_NOTIFICATIONS,
            'sms_notifications' => FEATURE_SMS_NOTIFICATIONS,
            'recurring_appointments' => FEATURE_RECURRING_APPOINTMENTS,
            'appointment_reminders' => FEATURE_APPOINTMENT_REMINDERS,
            'analytics_dashboard' => FEATURE_ANALYTICS_DASHBOARD,
            'export_data' => FEATURE_EXPORT_DATA
        ]
    ];
}

// Function to check if a feature is enabled
function isFeatureEnabled($feature) {
    $featureMap = [
        'email_notifications' => FEATURE_EMAIL_NOTIFICATIONS,
        'sms_notifications' => FEATURE_SMS_NOTIFICATIONS,
        'recurring_appointments' => FEATURE_RECURRING_APPOINTMENTS,
        'appointment_reminders' => FEATURE_APPOINTMENT_REMINDERS,
        'analytics_dashboard' => FEATURE_ANALYTICS_DASHBOARD,
        'export_data' => FEATURE_EXPORT_DATA
    ];
    
    return isset($featureMap[$feature]) ? $featureMap[$feature] : false;
}

// Function to validate system requirements
function validateSystemRequirements() {
    $requirements = [
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'mysqli_extension' => extension_loaded('mysqli'),
        'session_support' => function_exists('session_start'),
        'upload_dir_writable' => is_writable(dirname(__FILE__) . '/' . UPLOAD_PATH),
        'log_dir_writable' => is_writable(dirname(__FILE__))
    ];
    
    $allMet = true;
    foreach ($requirements as $requirement => $met) {
        if (!$met) {
            $allMet = false;
        }
    }
    
    return [
        'all_met' => $allMet,
        'requirements' => $requirements
    ];
}

// Function to get database status
function getDatabaseStatus() {
    global $conn;
    
    if (!$conn) {
        return ['connected' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Test basic queries
        $tables = ['users', 'appointments', 'consultation_hours', 'consultation_breaks', 'notifications'];
        $tableStatus = [];
        
        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
            $tableStatus[$table] = $result ? mysqli_fetch_assoc($result)['count'] : 'ERROR';
        }
        
        return [
            'connected' => true,
            'server_info' => mysqli_get_server_info($conn),
            'tables' => $tableStatus
        ];
        
    } catch (Exception $e) {
        return [
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>