<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/appointment_functions.php';
require_once '../../includes/notification_system.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    $missedBy = isset($_POST['missed_by']) ? sanitize($_POST['missed_by']) : '';
    $missedReason = isset($_POST['missed_reason']) ? sanitize($_POST['missed_reason']) : '';
    
    // Debug logging (remove in production)
    if (DEBUG_MODE) {
        error_log("Faculty Mark Missed Debug - appointmentId: $appointmentId, missedBy: '$missedBy', reasonLength: " . strlen($missedReason));
    }
    
    // Validate required fields
    if (!$appointmentId) {
        setFlashMessage('danger', 'Appointment ID is required.');
        redirect('pages/faculty/view_appointments.php');
    }
    
    if (empty($missedReason)) {
        setFlashMessage('danger', 'Please provide a reason for marking this appointment as missed.');
        redirect('pages/faculty/mark_missed.php?id=' . $appointmentId);
    }
    
    // Set missed_by to 'faculty' if not provided or incorrect (faculty should always mark as faculty)
    $missedBy = 'faculty';
    
    // Validate reason length
    if (strlen($missedReason) > 500) {
        setFlashMessage('danger', 'Reason must be 500 characters or less.');
        redirect('pages/faculty/mark_missed.php?id=' . $appointmentId);
    }
    
    // Get faculty ID
    $facultyId = getFacultyIdFromUserId($_SESSION['user_id']);
    
    // Get appointment details to verify ownership
    $appointment = getAppointmentDetails($appointmentId);
    
    // Check if appointment exists and belongs to this faculty
    if (!$appointment || $appointment['faculty_id'] != $facultyId) {
        setFlashMessage('danger', 'Appointment not found or you do not have permission to mark it as missed.');
        redirect('pages/faculty/view_appointments.php');
    }
    
    // Check if appointment can be marked as missed
    if (!canMarkAppointmentAsMissed($appointmentId, 'faculty')) {
        setFlashMessage('danger', 'This appointment cannot be marked as missed at this time.');
        redirect('pages/faculty/appointment_details.php?id=' . $appointmentId);
    }
    
    try {
        // Mark the appointment as missed
        $result = markAppointmentAsMissed($appointmentId, 'faculty', $missedReason);
        
        if ($result) {
            // Create notification for student
            $notificationResult = createMissedNotification($appointmentId, 'faculty');
            
            $successMessage = 'Appointment has been marked as missed successfully.';
            
            if ($notificationResult) {
                $successMessage .= ' The student has been notified about being marked as missed.';
            }
            
            setFlashMessage('success', $successMessage);
            redirect('pages/faculty/view_appointments.php');
        } else {
            throw new Exception('Failed to mark appointment as missed.');
        }
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to mark appointment as missed: ' . $e->getMessage());
        redirect('pages/faculty/mark_missed.php?id=' . $appointmentId);
    }
    
} else {
    // Not a POST request, redirect to view appointments page
    setFlashMessage('danger', 'Invalid request method.');
    redirect('pages/faculty/view_appointments.php');
}
?>