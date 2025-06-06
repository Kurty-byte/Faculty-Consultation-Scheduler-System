<?php
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

// Mark all notifications as read for current user
$result = markAllNotificationsAsRead($_SESSION['user_id']);

if ($result !== false) {
    // Get updated unread count (should be 0 now)
    $unreadCount = countUnreadNotifications($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'All notifications marked as read',
        'unread_count' => $unreadCount,
        'affected_rows' => $result
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
}
?>