<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/appointment_functions.php';
require_once '../../includes/notification_system.php';

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'Appointment ID is required.');
    redirect('pages/student/view_appointments.php');
}

// Get appointment ID
$appointmentId = (int)$_GET['id'];

// Get appointment details
$appointment = getAppointmentDetails($appointmentId);

// Check if appointment exists and belongs to this student
$student = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']]);
$studentId = $student['student_id'];

if (!$appointment || $appointment['student_id'] != $studentId) {
    setFlashMessage('danger', 'Appointment not found or you do not have permission to cancel it.');
    redirect('pages/student/view_appointments.php');
}

// Check if appointment can be cancelled
if ($appointment['is_cancelled']) {
    setFlashMessage('danger', 'This appointment is already cancelled.');
    redirect('pages/student/view_appointments.php');
}

// Check if appointment is in the past
if (isPast($appointment['appointment_date'] . ' ' . $appointment['start_time'])) {
    setFlashMessage('danger', 'Cannot cancel appointments that have already passed.');
    redirect('pages/student/view_appointments.php');
}

// Check if within cancellation window (24 hours before)
$appointmentTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
$hoursDifference = getHoursDifference($appointmentTime, date('Y-m-d H:i:s'));

if ($hoursDifference < MIN_CANCEL_HOURS) {
    setFlashMessage('danger', 'Cannot cancel appointments less than ' . MIN_CANCEL_HOURS . ' hours before the scheduled time.');
    redirect('pages/student/view_appointments.php');
}

// Process cancellation
$notes = "Cancelled by student: " . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

try {
    $result = cancelAppointment($appointmentId, $notes);
    
    // Create notification for faculty using new system
    $notificationResult = createCancellationNotification($appointmentId);
    
    if ($notificationResult) {
        setFlashMessage('success', 'Appointment cancelled successfully. The faculty member has been notified of the cancellation and the time slot is now available for other students.');
    } else {
        setFlashMessage('success', 'Appointment cancelled successfully. The time slot is now available for other students.');
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Failed to cancel appointment: ' . $e->getMessage());
}

// Redirect back to appointments page
redirect('pages/student/view_appointments.php');
?>