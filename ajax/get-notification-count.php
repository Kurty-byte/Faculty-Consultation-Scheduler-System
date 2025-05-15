/*
 * File: ajax/get_notification_count.php
 * Description: AJAX endpoint to get unread notification count
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

// Get unread notification count
$count = countUnreadNotifications();

// Return response
echo json_encode(['success' => true, 'count' => $count]);
?>
