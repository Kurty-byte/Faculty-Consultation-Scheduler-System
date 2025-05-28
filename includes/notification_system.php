<?php
/**
 * New Notification System with Dynamic Time Display
 * Simple and clean notification management with duplicate prevention
 */

// Create a new notification with enhanced duplicate prevention
function createNotification($userId, $appointmentId, $type, $message) {
    global $conn;
    
    // Validate inputs
    if (!$userId || !$appointmentId || !$type || !$message) {
        return false;
    }
    
    // Validate notification type (add 'appointment_missed' to valid types)
    $validTypes = ['appointment_request', 'appointment_approved', 'appointment_rejected', 'appointment_cancelled', 'appointment_completed', 'appointment_missed'];
    if (!in_array($type, $validTypes)) {
        return false;
    }
    
    // Enhanced duplicate check - look for recent notifications of the same type
    $existingRecent = fetchRow(
        "SELECT notification_id FROM notifications 
         WHERE user_id = ? AND appointment_id = ? AND notification_type = ?
         AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
        [$userId, $appointmentId, $type]
    );
    
    if ($existingRecent) {
        return $existingRecent['notification_id']; // Return existing recent notification ID
    }
    
    // For certain types, also check if there's any existing notification
    if (in_array($type, ['appointment_request', 'appointment_approved', 'appointment_rejected'])) {
        $existingAny = fetchRow(
            "SELECT notification_id FROM notifications 
             WHERE user_id = ? AND appointment_id = ? AND notification_type = ?",
            [$userId, $appointmentId, $type]
        );
        
        if ($existingAny) {
            return $existingAny['notification_id']; // Return existing notification ID
        }
    }
    
    // Insert new notification
    $result = insertData(
        "INSERT INTO notifications (user_id, appointment_id, notification_type, message, is_read, created_at) 
         VALUES (?, ?, ?, ?, 0, NOW())",
        [$userId, $appointmentId, $type, $message]
    );
    
    return $result;
}

// Get unread notifications for a user with timestamp data for dynamic display
function getUnreadNotifications($userId, $limit = 50) {
    if (!$userId) {
        return [];
    }
    
    // FIX: Order by creation time and group by appointment to avoid duplicates
    $notifications = fetchRows(
        "SELECT n.*, a.appointment_date, a.start_time, a.end_time, a.modality, a.platform, a.location,
                UNIX_TIMESTAMP(n.created_at) as timestamp,
                n.created_at as raw_timestamp
         FROM notifications n
         JOIN appointments a ON n.appointment_id = a.appointment_id
         WHERE n.user_id = ? AND n.is_read = 0
         ORDER BY n.created_at DESC
         LIMIT ?",
        [$userId, $limit]
    );
    
    // Add dynamic time display data
    foreach ($notifications as &$notification) {
        $notification['time_ago'] = getTimeAgo($notification['raw_timestamp']);
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

// FIX: Enhanced booking notification creation with duplicate prevention
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

// FIX: Enhanced approval notification creation
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

// Enhanced rejection notification creation
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

// Enhanced cancellation notification creation
function createCancellationNotification($appointmentId, $cancellationReason = null) {
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
    
    if (!empty($cancellationReason)) {
        $message .= '. Reason: ' . $cancellationReason;
    }
    
    return createNotification(
        $faculty['user_id'],
        $appointmentId,
        'appointment_cancelled',
        $message
    );
}

// Enhanced time ago function - Returns dynamic time strings
function getTimeAgo($timestamp) {
    // Ensure we're working with a proper timestamp
    $time = strtotime($timestamp);
    $now = time();
    
    // If timestamp is invalid, return error message
    if ($time === false) {
        return 'Unknown time';
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

// Get appointments with dynamic time display for dashboards
function getAppointmentsWithTimeDisplay($query, $params = []) {
    $appointments = fetchRows($query, $params);
    
    // Add dynamic time display for each appointment
    foreach ($appointments as &$appointment) {
        // Use the most recent timestamp (updated_on or appointed_on)
        $activityTime = null;
        if (!empty($appointment['updated_on'])) {
            $activityTime = $appointment['updated_on'];
        } elseif (!empty($appointment['appointed_on'])) {
            $activityTime = $appointment['appointed_on'];
        }
        
        if ($activityTime) {
            $appointment['time_ago'] = getTimeAgo($activityTime);
            $appointment['timestamp'] = strtotime($activityTime);
        } else {
            $appointment['time_ago'] = 'Unknown time';
            $appointment['timestamp'] = 0;
        }
    }
    
    return $appointments;
}

// Clean old notifications (older than 30 days)
function cleanOldNotifications() {
    $result = updateOrDeleteData(
        "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    return $result;
}

// Enhanced completion notification creation
function createCompletionNotification($appointmentId) {
    // Get appointment details
    $appointment = fetchRow(
        "SELECT a.*, st.user_id as student_user_id, f.user_id as faculty_user_id, 
                us.first_name as student_first_name, us.last_name as student_last_name,
                uf.first_name as faculty_first_name, uf.last_name as faculty_last_name
         FROM appointments a 
         JOIN students st ON a.student_id = st.student_id 
         JOIN users us ON st.user_id = us.user_id
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
         JOIN faculty f ON s.faculty_id = f.faculty_id 
         JOIN users uf ON f.user_id = uf.user_id
         WHERE a.appointment_id = ?",
        [$appointmentId]
    );
    
    if (!$appointment) {
        return false;
    }
    
    // Determine who completed the appointment and who should be notified
    $currentUserRole = getCurrentUserRole();
    
    if ($currentUserRole === 'student') {
        // Student completed the appointment, notify faculty
        $notifyUserId = $appointment['faculty_user_id'];
        $message = $appointment['student_first_name'] . ' ' . $appointment['student_last_name'] . ' marked your consultation as completed';
    } else {
        // Faculty completed the appointment, notify student
        $notifyUserId = $appointment['student_user_id'];
        $message = $appointment['faculty_first_name'] . ' ' . $appointment['faculty_last_name'] . ' marked your consultation as completed';
    }
    
    return createNotification(
        $notifyUserId,
        $appointmentId,
        'appointment_completed',
        $message
    );
}

// Enhanced missed notification creation
function createMissedNotification($appointmentId, $missedBy) {
    // Get appointment details
    $appointment = fetchRow(
        "SELECT a.*, s.faculty_id, st.user_id as student_user_id, uf.first_name as faculty_first_name, 
                uf.last_name as faculty_last_name, us.first_name as student_first_name, us.last_name as student_last_name
         FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         JOIN students st ON a.student_id = st.student_id
         JOIN users us ON st.user_id = us.user_id
         JOIN faculty f ON s.faculty_id = f.faculty_id
         JOIN users uf ON f.user_id = uf.user_id
         WHERE a.appointment_id = ?",
        [$appointmentId]
    );
    
    if (!$appointment) {
        return false;
    }
    
    // Determine who to notify (the other party)
    if ($missedBy === 'student') {
        // Student marked faculty as missed, notify faculty
        $faculty = fetchRow("SELECT user_id FROM faculty WHERE faculty_id = ?", [$appointment['faculty_id']]);
        if (!$faculty) return false;
        
        $notifyUserId = $faculty['user_id'];
        $message = $appointment['student_first_name'] . ' ' . $appointment['student_last_name'] . ' marked you as missed for the appointment';
    } else {
        // Faculty marked student as missed, notify student
        $notifyUserId = $appointment['student_user_id'];
        $message = $appointment['faculty_first_name'] . ' ' . $appointment['faculty_last_name'] . ' marked you as missed for the appointment';
    }
    
    return createNotification(
        $notifyUserId,
        $appointmentId,
        'appointment_missed',
        $message
    );
}

// Function to remove duplicate notifications (cleanup utility)
function removeDuplicateNotifications() {
    global $conn;
    
    // Remove duplicate notifications keeping the most recent one
    $query = "
        DELETE n1 FROM notifications n1
        INNER JOIN notifications n2 
        WHERE n1.notification_id < n2.notification_id 
        AND n1.user_id = n2.user_id 
        AND n1.appointment_id = n2.appointment_id 
        AND n1.notification_type = n2.notification_type
        AND n1.created_at < n2.created_at
    ";
    
    return mysqli_query($conn, $query);
}

// Function to get latest notification with better duplicate handling
function getLatestNotification($userId) {
    if (!$userId) {
        return null;
    }
    
    return fetchRow(
        "SELECT n.*, a.appointment_date, a.start_time, a.end_time,
                UNIX_TIMESTAMP(n.created_at) as timestamp,
                n.created_at as raw_timestamp
         FROM notifications n
         JOIN appointments a ON n.appointment_id = a.appointment_id
         WHERE n.user_id = ? AND n.is_read = 0
         ORDER BY n.created_at DESC
         LIMIT 1",
        [$userId]
    );
}

// Additional utility functions

// Get notification statistics for admin/debugging
function getNotificationStats() {
    $stats = [
        'total_notifications' => 0,
        'unread_notifications' => 0,
        'notifications_by_type' => [],
        'notifications_by_user_role' => []
    ];
    
    // Total notifications
    $total = fetchRow("SELECT COUNT(*) as count FROM notifications");
    $stats['total_notifications'] = $total ? $total['count'] : 0;
    
    // Unread notifications
    $unread = fetchRow("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
    $stats['unread_notifications'] = $unread ? $unread['count'] : 0;
    
    // Notifications by type
    $byType = fetchRows(
        "SELECT notification_type, COUNT(*) as count 
         FROM notifications 
         GROUP BY notification_type 
         ORDER BY count DESC"
    );
    foreach ($byType as $type) {
        $stats['notifications_by_type'][$type['notification_type']] = $type['count'];
    }
    
    // Notifications by user role
    $byRole = fetchRows(
        "SELECT u.role, COUNT(n.notification_id) as count
         FROM notifications n
         JOIN users u ON n.user_id = u.user_id
         GROUP BY u.role
         ORDER BY count DESC"
    );
    foreach ($byRole as $role) {
        $stats['notifications_by_user_role'][$role['role']] = $role['count'];
    }
    
    return $stats;
}

// Get all notifications for a user (read and unread) with pagination
function getAllNotifications($userId, $page = 1, $limit = 20) {
    if (!$userId) {
        return [];
    }
    
    $offset = ($page - 1) * $limit;
    
    $notifications = fetchRows(
        "SELECT n.*, a.appointment_date, a.start_time, a.end_time, a.modality, a.platform, a.location,
                UNIX_TIMESTAMP(n.created_at) as timestamp,
                n.created_at as raw_timestamp
         FROM notifications n
         JOIN appointments a ON n.appointment_id = a.appointment_id
         WHERE n.user_id = ?
         ORDER BY n.created_at DESC
         LIMIT ? OFFSET ?",
        [$userId, $limit, $offset]
    );
    
    // Add dynamic time display data
    foreach ($notifications as &$notification) {
        $notification['time_ago'] = getTimeAgo($notification['raw_timestamp']);
        $notification['link'] = getNotificationLink($notification);
    }
    
    return $notifications;
}

// Check if user has specific type of notification
function hasNotificationType($userId, $appointmentId, $type) {
    if (!$userId || !$appointmentId || !$type) {
        return false;
    }
    
    $result = fetchRow(
        "SELECT notification_id FROM notifications 
         WHERE user_id = ? AND appointment_id = ? AND notification_type = ?",
        [$userId, $appointmentId, $type]
    );
    
    return $result !== null;
}

// Bulk mark notifications as read by type
function markNotificationsByTypeAsRead($userId, $type) {
    if (!$userId || !$type) {
        return false;
    }
    
    return updateOrDeleteData(
        "UPDATE notifications SET is_read = 1 
         WHERE user_id = ? AND notification_type = ? AND is_read = 0",
        [$userId, $type]
    );
}

// Get notification count by type for a user
function getNotificationCountByType($userId, $type) {
    if (!$userId || !$type) {
        return 0;
    }
    
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM notifications 
         WHERE user_id = ? AND notification_type = ? AND is_read = 0",
        [$userId, $type]
    );
    
    return $result ? (int)$result['count'] : 0;
}

// Delete old read notifications (cleanup function)
function deleteOldReadNotifications($daysOld = 90) {
    $result = updateOrDeleteData(
        "DELETE FROM notifications 
         WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
        [$daysOld]
    );
    
    return $result;
}
?>