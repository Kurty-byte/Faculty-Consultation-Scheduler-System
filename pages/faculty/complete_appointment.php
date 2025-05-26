<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/appointment_functions.php';

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'Appointment ID is required.');
    redirect('pages/faculty/view_appointments.php');
}

// Get appointment ID
$appointmentId = (int)$_GET['id'];

// Get appointment details
$appointment = getAppointmentDetails($appointmentId);

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Check if appointment exists and belongs to this faculty
if (!$appointment || $appointment['faculty_id'] != $facultyId) {
    setFlashMessage('danger', 'Appointment not found or you do not have permission to complete it.');
    redirect('pages/faculty/view_appointments.php');
}

// Check if appointment can be completed
if (!canCompleteAppointment($appointmentId)) {
    setFlashMessage('danger', 'This appointment cannot be marked as completed.');
    redirect('pages/faculty/view_appointments.php');
}

// Process completion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get notes from form
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : null;
    
    try {
        $result = completeAppointment($appointmentId, $notes);
        
        setFlashMessage('success', 'Appointment marked as completed successfully.');
        redirect('pages/faculty/view_appointments.php');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to complete appointment: ' . $e->getMessage());
        redirect('pages/faculty/appointment_details.php?id=' . $appointmentId);
    }
} else {
    // Display completion form
    $pageTitle = 'Complete Appointment';
    include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Mark Appointment as Completed</h1>
    <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Back to Appointment</a>
</div>

<div class="card">
    <div class="card-body">
        <h2>Appointment Details</h2>
        <table class="detail-table">
            <tr>
                <th>Student:</th>
                <td><?php echo $appointment['student_first_name'] . ' ' . $appointment['student_last_name']; ?></td>
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
                <th>Student's Reason:</th>
                <td><?php echo nl2br(htmlspecialchars($appointment['remarks'])); ?></td>
            </tr>
        </table>
        
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="mt-4">
            <div class="form-group">
                <label for="notes">Completion Notes (Optional):</label>
                <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Add any notes about the consultation session (topics discussed, outcomes, etc.)"></textarea>
                <small class="form-text text-muted">These notes will be recorded in the appointment history.</small>
            </div>
            
            <div class="form-group text-right">
                <button type="submit" class="btn btn-success">Mark as Completed</button>
                <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
    include '../../includes/footer.php';
}
?>