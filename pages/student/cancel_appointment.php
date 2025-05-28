<?php
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

// Process cancellation form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get cancellation reason from form
    $cancellationReason = isset($_POST['cancellation_reason']) ? sanitize($_POST['cancellation_reason']) : null;

    if (!empty($cancellationReason)) {
        $notes = $cancellationReason;
    }

    try {
        $result = cancelAppointment($appointmentId, $notes, $cancellationReason);
        
        // Create notification for faculty using new system
        $notificationResult = createCancellationNotification($appointmentId, $cancellationReason);
        
        if ($notificationResult) {
            setFlashMessage('success', 'Appointment cancelled successfully. The faculty member has been notified of the cancellation and the time slot is now available for other students.');
        } else {
            setFlashMessage('success', 'Appointment cancelled successfully. The time slot is now available for other students.');
        }
        
        redirect('pages/student/view_appointments.php');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to cancel appointment: ' . $e->getMessage());
        redirect('pages/student/cancel_appointment.php?id=' . $appointmentId);
    }
} else {
    // Display cancellation form
    $pageTitle = 'Cancel Appointment';
    include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Cancel Appointment</h1>
    <a href="<?php echo BASE_URL; ?>pages/student/appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Back to Appointment</a>
</div>

<div class="card">
    <div class="card-body">
        <h2>Appointment Details</h2>
        <table class="detail-table">
            <tr>
                <th>Faculty:</th>
                <td><?php echo $appointment['faculty_first_name'] . ' ' . $appointment['faculty_last_name']; ?></td>
            </tr>
            <tr>
                <th>Date:</th>
                <td><?php echo formatDate($appointment['appointment_date']); ?></td>
            </tr>
            <tr>
                <th>Time:</th>
                <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
            </tr>
            <tr>
                <th>Modality:</th>
                <td><?php echo ucfirst($appointment['modality']); ?></td>
            </tr>
            <tr>
                <th>Reason for Consultation:</th>
                <td><?php echo displayTextContent($appointment['remarks']); ?></td>
            </tr>
        </table>
        
        <div class="alert alert-warning mt-3">
            <strong>Are you sure you want to cancel this appointment?</strong> This action cannot be undone and the faculty member will be notified.
        </div>
        
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="mt-4">
            <div class="form-group">
                <label for="cancellation_reason">Reason for Cancellation (Optional):</label>
                <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="4" 
                          placeholder="Please provide a reason for cancelling this appointment (e.g., schedule conflict, emergency, etc.)"></textarea>
                <small class="form-text text-muted">This will help the faculty member understand why you cancelled.</small>
            </div>
            
            <div class="form-group text-right">
                <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                <a href="<?php echo BASE_URL; ?>pages/student/appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Keep Appointment</a>
            </div>
        </form>
    </div>
</div>

<?php
    include '../../includes/footer.php';
}
?>