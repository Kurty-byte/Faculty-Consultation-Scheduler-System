/*
 * File: ajax/mark_notification_read.php
 * Description: AJAX endpoint to mark notifications as read
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

// Check if notification ID is provided
if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

// Get notification ID
$notificationId = (int)$_POST['notification_id'];

// Mark notification as read
$result = markNotificationAsRead($notificationId);

// Return response
echo json_encode(['success' => (bool)$result]);
?>
