/*
 * File: ajax/get_notifications.php
 * Description: AJAX endpoint to get user notifications
 */

<?php
// Include config file
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Include notification functions
require_once '../includes/notification_functions.php';

// Get notifications
$notifications = getUserNotifications();

// Format time for each notification
foreach ($notifications as &$notification) {
    $notification['time_ago'] = getTimeAgo($notification['timestamp']);
}

// Return response
echo json_encode(['success' => true, 'notifications' => $notifications]);
?>
