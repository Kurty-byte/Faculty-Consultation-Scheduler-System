<?php
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

// Get current unread notification count
$currentCount = countUnreadNotifications($_SESSION['user_id']);

// Get last known count from request (for comparison)
$lastKnownCount = isset($_GET['last_count']) ? (int)$_GET['last_count'] : 0;

// FIX: Better logic to determine if there are truly new notifications
$hasNewNotifications = false;
$latestNotification = null;

// Get the most recent notification to check if it's actually new
if ($currentCount > 0) {
    $latestNotificationData = fetchRow(
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
    
    if ($latestNotificationData) {
        // Only consider it "new" if:
        // 1. The count increased from last known count, AND
        // 2. The latest notification was created within the last 5 minutes
        $notificationAge = time() - $latestNotificationData['timestamp'];
        $isRecentNotification = $notificationAge < 300; // 5 minutes
        
        $hasNewNotifications = ($currentCount > $lastKnownCount) && $isRecentNotification;
        
        if ($hasNewNotifications) {
            $latestNotification = [
                'id' => $latestNotificationData['notification_id'],
                'type' => $latestNotificationData['notification_type'],
                'message' => $latestNotificationData['message'],
                'time_ago' => getTimeAgo($latestNotificationData['raw_timestamp']),
                'appointment_date' => formatDate($latestNotificationData['appointment_date']),
                'appointment_time' => formatTime($latestNotificationData['start_time']),
                'created_at' => $latestNotificationData['raw_timestamp'],
                'timestamp' => $latestNotificationData['timestamp']
            ];
        }
    }
}

// Get user activity summary (optional enhancement)
$activitySummary = [
    'pending_appointments' => 0,
    'todays_appointments' => 0
];

if ($_SESSION['role'] === 'faculty') {
    $facultyId = getFacultyIdFromUserId($_SESSION['user_id']);
    
    $activitySummary['pending_appointments'] = fetchRow(
        "SELECT COUNT(*) as count FROM appointments a 
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
         WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0",
        [$facultyId]
    )['count'];
    
    $activitySummary['todays_appointments'] = fetchRow(
        "SELECT COUNT(*) as count FROM appointments a 
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
         WHERE s.faculty_id = ? AND a.appointment_date = CURDATE() AND a.is_approved = 1 AND a.is_cancelled = 0",
        [$facultyId]
    )['count'];
    
} elseif ($_SESSION['role'] === 'student') {
    $studentId = getStudentIdFromUserId($_SESSION['user_id']);
    
    $activitySummary['pending_appointments'] = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE student_id = ? AND is_approved = 0 AND is_cancelled = 0",
        [$studentId]
    )['count'];
    
    $activitySummary['todays_appointments'] = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE student_id = ? AND appointment_date = CURDATE() AND is_approved = 1 AND is_cancelled = 0",
        [$studentId]
    )['count'];
}

// Return comprehensive response
echo json_encode([
    'success' => true,
    'count' => $currentCount,
    'last_known_count' => $lastKnownCount,
    'has_new_notifications' => $hasNewNotifications,
    'latest_notification' => $latestNotification,
    'activity_summary' => $activitySummary,
    'timestamp' => date('Y-m-d H:i:s'),
    'server_time' => time(),
    'user_id' => $_SESSION['user_id'],
    'user_role' => $_SESSION['role'],
    'debug_info' => [
        'current_count' => $currentCount,
        'last_known_count' => $lastKnownCount,
        'count_increased' => $currentCount > $lastKnownCount,
        'latest_notification_age' => isset($notificationAge) ? $notificationAge : null,
        'is_recent' => isset($isRecentNotification) ? $isRecentNotification : false
    ]
]);
?>