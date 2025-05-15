/*
 * File: ajax/get_new_notifications.php
 * Description: AJAX endpoint to get new notifications for browser push notifications
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

// Get the timestamp since when to check for new notifications
$since = isset($_GET['since']) ? (int)$_GET['since'] : 0;

// Get user ID and role
$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

$notifications = [];

if ($userRole == 'faculty') {
    // For faculty: get new appointment requests
    $facultyId = getFacultyIdFromUserId($userId);
    
    $query = "SELECT a.appointment_id, a.remarks, a.appointed_on, 
             u.first_name, u.last_name
             FROM appointments a
             JOIN availability_schedules s ON a.schedule_id = s.schedule_id
             JOIN students st ON a.student_id = st.student_id
             JOIN users u ON st.user_id = u.user_id
             WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0
             AND UNIX_TIMESTAMP(a.appointed_on) > ?
             ORDER BY a.appointed_on DESC";
             
    $result = executeQuery($query, [$facultyId, $since]);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = [
                'id' => $row['appointment_id'],
                'type' => 'appointment_request',
                'message' => $row['first_name'] . ' ' . $row['last_name'] . ' requested a consultation',
                'timestamp' => $row['appointed_on'],
                'link' => BASE_URL . 'pages/faculty/appointment_details.php?id=' . $row['appointment_id']
            ];
        }
    }
} else if ($userRole == 'student') {
    // For student: get new status changes (approved/rejected appointments)
    $studentId = getStudentIdFromUserId($userId);
    
    $query = "SELECT a.appointment_id, ah.status_change, ah.notes, ah.changed_at, 
             u.first_name, u.last_name
             FROM appointment_history ah
             JOIN appointments a ON ah.appointment_id = a.appointment_id
             JOIN availability_schedules s ON a.schedule_id = s.schedule_id
             JOIN faculty f ON s.faculty_id = f.faculty_id
             JOIN users u ON f.user_id = u.user_id
             WHERE a.student_id = ? AND (ah.status_change = 'approved' OR ah.status_change = 'rejected')
             AND UNIX_TIMESTAMP(ah.changed_at) > ?
             ORDER BY ah.changed_at DESC";
             
    $result = executeQuery($query, [$studentId, $since]);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $statusText = ($row['status_change'] == 'approved') ? 'approved' : 'rejected';
            
            $notifications[] = [
                'id' => $row['appointment_id'],
                'type' => 'appointment_' . $row['status_change'],
                'message' => $row['first_name'] . ' ' . $row['last_name'] . ' ' . $statusText . ' your appointment',
                'timestamp' => $row['changed_at'],
                'link' => BASE_URL . 'pages/student/appointment_details.php?id=' . $row['appointment_id']
            ];
        }
    }
}

// Return response with current timestamp
echo json_encode([
    'success' => true, 
    'notifications' => $notifications,
    'current_time' => time()
]);
?>
