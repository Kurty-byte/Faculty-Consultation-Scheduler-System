/* 
 * File: includes/notification_functions.php
 * Description: Functions for handling notifications
 */

<?php
// Get unread notifications for the current user
function getUserNotifications($limit = 10) {
    // Get current user ID and role
    $userId = getCurrentUserId();
    $userRole = getCurrentUserRole();
    
    if (!$userId) {
        return [];
    }
    
    global $conn;
    $notifications = [];
    
    if ($userRole == 'faculty') {
        // For faculty: get pending appointment requests
        $facultyId = getFacultyIdFromUserId($userId);
        
        $query = "SELECT a.appointment_id, a.remarks, a.appointed_on, 
                 u.first_name, u.last_name, 
                 ah.appointment_id as is_read
                 FROM appointments a
                 JOIN availability_schedules s ON a.schedule_id = s.schedule_id
                 JOIN students st ON a.student_id = st.student_id
                 JOIN users u ON st.user_id = u.user_id
                 LEFT JOIN appointment_history ah ON a.appointment_id = ah.appointment_id AND ah.status_change = 'read_by_faculty'
                 WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0
                 ORDER BY a.appointed_on DESC
                 LIMIT ?";
                 
        $result = executeQuery($query, [$facultyId, $limit]);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = [
                    'id' => $row['appointment_id'],
                    'type' => 'appointment_request',
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . ' requested a consultation',
                    'details' => $row['remarks'],
                    'timestamp' => $row['appointed_on'],
                    'is_read' => !empty($row['is_read']),
                    'link' => BASE_URL . 'pages/faculty/appointment_details.php?id=' . $row['appointment_id']
                ];
            }
        }
    } else if ($userRole == 'student') {
        // For student: get status changes (approved/rejected appointments)
        $studentId = getStudentIdFromUserId($userId);
        
        // First, find the latest status changes
        $query = "SELECT a.appointment_id, ah.status_change, ah.notes, ah.changed_at, 
                 u.first_name, u.last_name,
                 ah2.appointment_id as is_read
                 FROM appointment_history ah
                 JOIN appointments a ON ah.appointment_id = a.appointment_id
                 JOIN availability_schedules s ON a.schedule_id = s.schedule_id
                 JOIN faculty f ON s.faculty_id = f.faculty_id
                 JOIN users u ON f.user_id = u.user_id
                 LEFT JOIN appointment_history ah2 ON a.appointment_id = ah2.appointment_id AND ah2.status_change = 'read_by_student'
                 WHERE a.student_id = ? AND (ah.status_change = 'approved' OR ah.status_change = 'rejected')
                 ORDER BY ah.changed_at DESC
                 LIMIT ?";
                 
        $result = executeQuery($query, [$studentId, $limit]);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $statusText = ($row['status_change'] == 'approved') ? 'approved' : 'rejected';
                
                $notifications[] = [
                    'id' => $row['appointment_id'],
                    'type' => 'appointment_' . $row['status_change'],
                    'message' => $row['first_name'] . ' ' . $row['last_name'] . ' ' . $statusText . ' your appointment',
                    'details' => $row['notes'],
                    'timestamp' => $row['changed_at'],
                    'is_read' => !empty($row['is_read']),
                    'link' => BASE_URL . 'pages/student/appointment_details.php?id=' . $row['appointment_id']
                ];
            }
        }
    }
    
    return $notifications;
}

// Count unread notifications for current user
function countUnreadNotifications() {
    $notifications = getUserNotifications(99); // Get a larger set to count all unread
    $count = 0;
    
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) {
            $count++;
        }
    }
    
    return $count;
}

// Mark a notification as read
function markNotificationAsRead($notificationId) {
    $userRole = getCurrentUserRole();
    $userId = getCurrentUserId();
    
    if (!$userId || !$notificationId) {
        return false;
    }
    
    $status = ($userRole == 'faculty') ? 'read_by_faculty' : 'read_by_student';
    
    // Add an entry to appointment_history to mark as read
    $result = insertData(
        "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
         VALUES (?, ?, ?, 'Notification read')",
        [$notificationId, $status, $userId]
    );
    
    return $result;
}

// Format time for notifications
function getTimeAgo($timestamp) {
    $time_difference = time() - strtotime($timestamp);

    if ($time_difference < 60) {
        return 'Just now';
    } elseif ($time_difference < 3600) {
        return floor($time_difference / 60) . ' min ago';
    } elseif ($time_difference < 86400) {
        return floor($time_difference / 3600) . ' hours ago';
    } elseif ($time_difference < 604800) {
        return floor($time_difference / 86400) . ' days ago';
    } else {
        return date('M j', strtotime($timestamp));
    }
}
?>
