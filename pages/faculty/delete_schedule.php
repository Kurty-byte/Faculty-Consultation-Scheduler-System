<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Check if schedule ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'Schedule ID is required.');
    redirect('pages/faculty/manage_schedules.php');
}

// Get schedule ID
$scheduleId = (int)$_GET['id'];

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Check if schedule exists and belongs to this faculty
$schedule = fetchRow(
    "SELECT * FROM availability_schedules WHERE schedule_id = ? AND faculty_id = ?",
    [$scheduleId, $facultyId]
);

if (!$schedule) {
    setFlashMessage('danger', 'Schedule not found or you do not have permission to delete it.');
    redirect('pages/faculty/manage_schedules.php');
}

// Start transaction
global $conn;
mysqli_begin_transaction($conn);

try {
    // First delete all appointment history records for the appointments
    updateOrDeleteData(
        "DELETE ah FROM appointment_history ah 
         JOIN appointments a ON ah.appointment_id = a.appointment_id 
         WHERE a.schedule_id = ?",
        [$scheduleId]
    );
    
    // Then delete all appointments
    updateOrDeleteData(
        "DELETE FROM appointments WHERE schedule_id = ?",
        [$scheduleId]
    );
    
    // Finally delete the schedule
    updateOrDeleteData(
        "DELETE FROM availability_schedules WHERE schedule_id = ?",
        [$scheduleId]
    );
    
    // Commit the transaction
    mysqli_commit($conn);
    
    setFlashMessage('success', 'Schedule and all related appointments have been permanently deleted.');
} catch (Exception $e) {
    // Rollback the transaction if something goes wrong
    mysqli_rollback($conn);
    
    setFlashMessage('danger', 'Failed to delete schedule: ' . $e->getMessage());
}

// Redirect back to the manage schedules page
redirect('pages/faculty/manage_schedules.php');
?>