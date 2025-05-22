<?php
/*
 * File: ajax/get_notification_count.php
 * Description: AJAX endpoint to get unread notification count (New System)
 */

// Include config file
$configPath = dirname(__FILE__) . '/../config.php';
if (!file_exists($configPath)) {
    $configPath = '../config.php';
}
require_once $configPath;

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Include new notification system
require_once '../includes/notification_system.php';

// Get unread notification count for current user
$count = countUnreadNotifications($_SESSION['user_id']);

// Return response
echo json_encode([
    'success' => true, 
    'count' => $count,
    'timestamp' => date('Y-m-d H:i:s'),
    'user_id' => $_SESSION['user_id']
]);
?>