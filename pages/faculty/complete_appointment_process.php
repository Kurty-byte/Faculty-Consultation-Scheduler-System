<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/appointment_functions.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : null;
    
    if (!$appointmentId) {
        setFlashMessage('danger', 'Invalid appointment ID.');
        redirect('pages/faculty/view_appointments.php');
    }
    
    // Get faculty ID
    $facultyId = getFacultyIdFromUserId($_SESSION['user_id']);
    
    // Get appointment details to verify ownership
    $appointment = getAppointmentDetails($appointmentId);
    
    // Check if appointment exists and belongs to this faculty
    if (!$appointment || $appointment['faculty_id'] != $facultyId) {
        setFlashMessage('danger', 'Appointment not found or you do not have permission to complete it.');
        redirect('pages/faculty/view_appointments.php');
    }
    
    // Check if appointment can be completed
    if (!canCompleteAppointment($appointmentId)) {
        setFlashMessage('danger', 'This appointment cannot be marked as completed.');
        redirect('pages/faculty/appointment_details.php?id=' . $appointmentId);
    }
    
    try {
        $result = completeAppointment($appointmentId, $notes);
        
        setFlashMessage('success', 'Appointment marked as completed successfully.');
        redirect('pages/faculty/view_appointments.php');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to complete appointment: ' . $e->getMessage());
        redirect('pages/faculty/complete_appointment.php?id=' . $appointmentId);
    }
} else {
    // Not a POST request, redirect to view appointments page
    redirect('pages/faculty/view_appointments.php');
}
?>