<?php
/**
 * New Notification System
 * Simple and clean notification management
 */

// Create a new notification
function createNotification($userId, $appointmentId, $type, $message) {
    global $conn;
    
    // Validate inputs
    if (!$userId || !$appointmentId || !$type || !$message) {
        return false;
    }
    
    // Check if notification already exists to avoid duplicates
    $existing = fetchRow(
        "SELECT notification_id FROM notifications 
         WHERE user_id = ? AND appointment_id = ? AND notification_type = ?",
        [$userId, $appointmentId, $type]
    );
    
    if ($existing) {
        return $existing['notification_id']; // Return existing notification ID
    }
    
    // Insert new notification
    $result = insertData(
        "INSERT INTO notifications (user_id, appointment_id, notification_type, message, is_read, created_at) 
         VALUES (?, ?, ?, ?, 0, NOW())",
        [$userId, $appointmentId, $type, $message]
    );
    
    return $result;
}

// Get unread notifications for a user
function getUnreadNotifications($userId, $limit = 50) {
    if (!$userId) {
        return [];
    }
    
    $notifications = fetchRows(
        "SELECT n.*, a.appointment_date, a.start_time, a.end_time, a.modality, a.platform, a.location
         FROM notifications n
         JOIN appointments a ON n.appointment_id = a.appointment_id
         WHERE n.user_id = ? AND n.is_read = 0
         ORDER BY n.created_at DESC
         LIMIT ?",
        [$userId, $limit]
    );
    
    // Add formatted time ago and links
    foreach ($notifications as &$notification) {
        $notification['time_ago'] = getTimeAgo($notification['created_at']);
        $notification['formatted_time'] = date('M j, Y g:i A', strtotime($notification['created_at']));
        $notification['link'] = getNotificationLink($notification);
    }
    
    return $notifications;
}

// Count unread notifications for a user
function countUnreadNotifications($userId) {
    if (!$userId) {
        return 0;
    }
    
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
    
    return $result ? (int)$result['count'] : 0;
}

// Mark a single notification as read
function markNotificationAsRead($notificationId) {
    if (!$notificationId) {
        return false;
    }
    
    $result = updateOrDeleteData(
        "UPDATE notifications SET is_read = 1 WHERE notification_id = ?",
        [$notificationId]
    );
    
    return $result;
}

// Mark all notifications as read for a user
function markAllNotificationsAsRead($userId) {
    if (!$userId) {
        return false;
    }
    
    $result = updateOrDeleteData(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
    
    return $result;
}

// Generate appropriate link for notification
function getNotificationLink($notification) {
    $userRole = getCurrentUserRole();
    $basePath = BASE_URL . 'pages/' . $userRole . '/';
    
    // All notifications link to appointment details
    return $basePath . 'appointment_details.php?id=' . $notification['appointment_id'];
}

// Create notification when student books appointment
function createBookingNotification($appointmentId) {
    // Get appointment details
    $appointment = fetchRow(
        "SELECT a.*, s.faculty_id, u.first_name, u.last_name
         FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         JOIN students st ON a.student_id = st.student_id
         JOIN users u ON st.user_id = u.user_id
         WHERE a.appointment_id = ?",
        [$appointmentId]
    );
    
    if (!$appointment) {
        return false;
    }
    
    // Get faculty user ID
    $faculty = fetchRow(
        "SELECT user_id FROM faculty WHERE faculty_id = ?",
        [$appointment['faculty_id']]
    );
    
    if (!$faculty) {
        return false;
    }
    
    $message = $appointment['first_name'] . ' ' . $appointment['last_name'] . ' requested a consultation';
    
    return createNotification(
        $faculty['user_id'],
        $appointmentId,
        'appointment_request',
        $message
    );
}

// Create notification when faculty approves appointment
function createApprovalNotification($appointmentId) {
    // Get appointment details
    $appointment = fetchRow(
        "SELECT a.*, st.user_id as student_user_id, u.first_name, u.last_name
         FROM appointments a
         JOIN students st ON a.student_id = st.student_id
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         JOIN faculty f ON s.faculty_id = f.faculty_id
         JOIN users u ON f.user_id = u.user_id
         WHERE a.appointment_id = ?",
        [$appointmentId]
    );
    
    if (!$appointment) {
        return false;
    }
    
    $message = $appointment['first_name'] . ' ' . $appointment['last_name'] . ' approved your appointment';
    
    return createNotification(
        $appointment['student_user_id'],
        $appointmentId,
        'appointment_approved',
        $message
    );
}

// Create notification when faculty rejects appointment
function createRejectionNotification($appointmentId) {
    // Get appointment details
    $appointment = fetchRow(
        "SELECT a.*, st.user_id as student_user_id, u.first_name, u.last_name
         FROM appointments a
         JOIN students st ON a.student_id = st.student_id
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         JOIN faculty f ON s.faculty_id = f.faculty_id
         JOIN users u ON f.user_id = u.user_id
         WHERE a.appointment_id = ?",
        [$appointmentId]
    );
    
    if (!$appointment) {
        return false;
    }
    
    $message = $appointment['first_name'] . ' ' . $appointment['last_name'] . ' rejected your appointment';
    
    return createNotification(
        $appointment['student_user_id'],
        $appointmentId,
        'appointment_rejected',
        $message
    );
}

// Create notification when student cancels appointment
function createCancellationNotification($appointmentId) {
    // Get appointment details
    $appointment = fetchRow(
        "SELECT a.*, s.faculty_id, u.first_name, u.last_name
         FROM appointments a
         JOIN students st ON a.student_id = st.student_id
         JOIN users u ON st.user_id = u.user_id
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         WHERE a.appointment_id = ?",
        [$appointmentId]
    );
    
    if (!$appointment) {
        return false;
    }
    
    // Get faculty user ID
    $faculty = fetchRow(
        "SELECT user_id FROM faculty WHERE faculty_id = ?",
        [$appointment['faculty_id']]
    );
    
    if (!$faculty) {
        return false;
    }
    
    $message = $appointment['first_name'] . ' ' . $appointment['last_name'] . ' cancelled their appointment';
    
    return createNotification(
        $faculty['user_id'],
        $appointmentId,
        'appointment_cancelled',
        $message
    );
}

// Enhanced time ago function - FIXED VERSION
function getTimeAgo($timestamp) {
    // Ensure we're working with a proper timestamp
    $time = strtotime($timestamp);
    $now = time();
    
    // If timestamp is invalid, return error message
    if ($time === false) {
        return 'Invalid time';
    }
    
    $diff = $now - $time;
    
    // Handle future dates
    if ($diff < 0) {
        return 'Just now';
    }
    
    // Less than a minute
    if ($diff < 60) {
        return 'Just now';
    }
    
    // Minutes
    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    }
    
    // Hours
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    
    // Days
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    
    // Weeks
    if ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }
    
    // Months
    if ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    }
    
    // Years
    $years = floor($diff / 31536000);
    return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
}

// Get exact formatted time
function getExactTime($timestamp) {
    return date('F j, Y \a\t g:i A', strtotime($timestamp));
}

// Clean old notifications (older than 30 days)
function cleanOldNotifications() {
    $result = updateOrDeleteData(
        "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    return $result;
}

// Debug function to check notification timestamps
function debugNotificationTime($notificationId) {
    $notification = fetchRow(
        "SELECT *, UNIX_TIMESTAMP(created_at) as unix_timestamp FROM notifications WHERE notification_id = ?",
        [$notificationId]
    );
    
    if ($notification) {
        return [
            'created_at' => $notification['created_at'],
            'unix_timestamp' => $notification['unix_timestamp'],
            'current_time' => date('Y-m-d H:i:s'),
            'current_unix' => time(),
            'time_ago' => getTimeAgo($notification['created_at']),
            'formatted_time' => getExactTime($notification['created_at'])
        ];
    }
    
    return null;
}
?>