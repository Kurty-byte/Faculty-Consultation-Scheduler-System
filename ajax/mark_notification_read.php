<?php
/*
 * File: ajax/mark_notification_read.php
 * Description: AJAX endpoint to mark single notification as read (New System)
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

// Check if notification ID is provided
if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

// Get notification ID
$notificationId = (int)$_POST['notification_id'];

// Verify the notification belongs to the current user
$notification = fetchRow(
    "SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ?",
    [$notificationId, $_SESSION['user_id']]
);

if (!$notification) {
    echo json_encode(['success' => false, 'message' => 'Notification not found or access denied']);
    exit;
}

// Mark notification as read
$result = markNotificationAsRead($notificationId);

if ($result) {
    // Get updated unread count
    $unreadCount = countUnreadNotifications($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Notification marked as read',
        'unread_count' => $unreadCount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}
?>