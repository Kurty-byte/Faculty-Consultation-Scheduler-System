<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/appointment_functions.php';
require_once '../../includes/notification_system.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    $missedBy = isset($_POST['missed_by']) ? sanitize($_POST['missed_by']) : '';
    $missedReason = isset($_POST['missed_reason']) ? sanitize($_POST['missed_reason']) : '';
    $confirmMissed = isset($_POST['confirm_missed']) ? true : false;
    
    // Validate required fields
    if (!$appointmentId || !$missedBy || !$missedReason || !$confirmMissed) {
        setFlashMessage('danger', 'All required fields must be filled out and confirmed.');
        redirect('pages/student/view_appointments.php');
    }
    
    // Validate missed_by value
    if ($missedBy !== 'student') {
        setFlashMessage('danger', 'Invalid request source.');
        redirect('pages/student/view_appointments.php');
    }
    
    // Validate reason length
    if (strlen($missedReason) > 500) {
        setFlashMessage('danger', 'Reason must be 500 characters or less.');
        redirect('pages/student/mark_missed.php?id=' . $appointmentId);
    }
    
    // Get student ID
    $student = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']]);
    $studentId = $student['student_id'];
    
    // Get appointment details to verify ownership
    $appointment = getAppointmentDetails($appointmentId);
    
    // Check if appointment exists and belongs to this student
    if (!$appointment || $appointment['student_id'] != $studentId) {
        setFlashMessage('danger', 'Appointment not found or you do not have permission to mark it as missed.');
        redirect('pages/student/view_appointments.php');
    }
    
    // Check if appointment can be marked as missed
    if (!canMarkAppointmentAsMissed($appointmentId, 'student')) {
        setFlashMessage('danger', 'This appointment cannot be marked as missed at this time.');
        redirect('pages/student/appointment_details.php?id=' . $appointmentId);
    }
    
    try {
        // Mark the appointment as missed
        $result = markAppointmentAsMissed($appointmentId, 'student', $missedReason);
        
        if ($result) {
            // Create notification for faculty
            $notificationResult = createMissedNotification($appointmentId, 'student');
            
            $successMessage = 'Appointment has been marked as missed successfully.';
            
            if ($notificationResult) {
                $successMessage .= ' The faculty member has been notified about being marked as missed.';
            }
            
            setFlashMessage('success', $successMessage);
            redirect('pages/student/view_appointments.php');
        } else {
            throw new Exception('Failed to mark appointment as missed.');
        }
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to mark appointment as missed: ' . $e->getMessage());
        redirect('pages/student/mark_missed.php?id=' . $appointmentId);
    }
    
} else {
    // Not a POST request, redirect to view appointments page
    setFlashMessage('danger', 'Invalid request method.');
    redirect('pages/student/view_appointments.php');
}
?>