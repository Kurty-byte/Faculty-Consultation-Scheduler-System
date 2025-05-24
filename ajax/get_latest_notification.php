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

// Get the most recent notification for the current user
$latestNotification = fetchRow(
    "SELECT n.*, a.appointment_date, a.start_time, a.end_time,
            UNIX_TIMESTAMP(n.created_at) as timestamp,
            n.created_at as raw_timestamp
     FROM notifications n
     JOIN appointments a ON n.appointment_id = a.appointment_id
     WHERE n.user_id = ? AND n.is_read = 0
     ORDER BY n.created_at DESC
     LIMIT 1",
    [$_SESSION['user_id']]
);

if ($latestNotification) {
    // Add dynamic time display
    $latestNotification['time_ago'] = getTimeAgo($latestNotification['raw_timestamp']);
    $latestNotification['formatted_date'] = formatDate($latestNotification['appointment_date']);
    $latestNotification['formatted_time'] = formatTime($latestNotification['start_time']);
    
    echo json_encode([
        'success' => true,
        'has_notification' => true,
        'notification' => $latestNotification
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_notification' => false,
        'notification' => null
    ]);
}
?>