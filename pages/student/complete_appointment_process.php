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

// Get student ID
$student = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']]);
$studentId = $student['student_id'];

// Check if appointment exists and belongs to this student
if (!$appointment || $appointment['student_id'] != $studentId) {
    setFlashMessage('danger', 'Appointment not found or you do not have permission to complete it.');
    redirect('pages/student/view_appointments.php');
}

// Check if appointment can be completed by student
if (!canStudentCompleteAppointment($appointmentId, $studentId)) {
    setFlashMessage('danger', 'This appointment cannot be marked as completed.');
    redirect('pages/student/view_appointments.php');
}

// Process completion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get notes from form
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : null;
    
    try {
        // Mark appointment as completed
        $result = completeAppointment($appointmentId, $notes);
        
        if ($result) {
            // FIX: Create completion notification for faculty
            $notificationResult = createCompletionNotification($appointmentId);
            
            if ($notificationResult) {
                setFlashMessage('success', 'Appointment marked as completed successfully. The faculty member has been notified.');
            } else {
                setFlashMessage('success', 'Appointment marked as completed successfully.');
                // Log notification failure for debugging
                error_log("Failed to create completion notification for appointment ID: $appointmentId (by student)");
            }
            
            redirect('pages/student/view_appointments.php');
        } else {
            throw new Exception('Failed to mark appointment as completed.');
        }
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to complete appointment: ' . $e->getMessage());
        redirect('pages/student/appointment_details.php?id=' . $appointmentId);
    }
} else {
    // Display completion form
    $pageTitle = 'Complete Appointment';
    include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Mark Consultation as Completed</h1>
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
        </table>
        
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="mt-4">
            <div class="form-group">
                <label for="notes">Completion Notes (Optional):</label>
                <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Add any notes about the consultation session (what was discussed, outcomes, feedback, etc.)"></textarea>
                <small class="form-text text-muted">These notes will be recorded and visible to the faculty member.</small>
            </div>
            
            <div class="form-group text-right">
                <button type="submit" class="btn btn-success">Mark as Completed</button>
                <a href="<?php echo BASE_URL; ?>pages/student/appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
    include '../../includes/footer.php';
}
?>