<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/appointment_functions.php';
require_once '../../includes/notification_system.php';

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
    setFlashMessage('danger', 'Appointment not found or you do not have permission to approve it.');
    redirect('pages/faculty/view_appointments.php');
}

// Check if appointment is already approved or cancelled
if ($appointment['is_approved'] || $appointment['is_cancelled']) {
    setFlashMessage('danger', 'This appointment is already ' . ($appointment['is_approved'] ? 'approved' : 'cancelled') . '.');
    redirect('pages/faculty/view_appointments.php');
}

// Process approval
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get notes from form
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : null;
    
    try {
        $result = approveAppointment($appointmentId, $notes);
        
        // Create notification for student using new system
        $notificationResult = createApprovalNotification($appointmentId);
        
        if ($notificationResult) {
            setFlashMessage('success', 'Appointment approved successfully. The student has been notified of the approval.');
        } else {
            setFlashMessage('success', 'Appointment approved successfully.');
        }
        
        redirect('pages/faculty/view_appointments.php');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to approve appointment: ' . $e->getMessage());
        redirect('pages/faculty/appointment_details.php?id=' . $appointmentId);
    }
} else {
    // Display approval form
    $pageTitle = 'Approve Appointment';
    include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Approve Appointment</h1>
    <a href="appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Back to Appointment</a>
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
            <?php if ($appointment['modality'] === 'virtual' && $appointment['platform']): ?>
                <tr>
                    <th>Platform:</th>
                    <td><?php echo $appointment['platform']; ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($appointment['modality'] === 'physical' && $appointment['location']): ?>
                <tr>
                    <th>Location:</th>
                    <td><?php echo $appointment['location']; ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Student's Reason:</th>
                <td><?php echo nl2br(htmlspecialchars($appointment['remarks'])); ?></td>
            </tr>
        </table>
        
        <form action="" method="POST" class="mt-4">
            <div class="form-group">
                <label for="notes">Approval Notes (Optional):</label>
                <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Add any notes or instructions for the student (meeting link, room details, etc.)"></textarea>
                <small class="form-text text-muted">These notes will be visible to the student and included in their notification.</small>
            </div>
            
            <div class="form-group text-right">
                <button type="submit" class="btn btn-success">Approve Appointment</button>
                <a href="appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
    include '../../includes/footer.php';
}
?>