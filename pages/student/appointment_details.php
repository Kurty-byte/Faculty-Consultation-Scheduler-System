<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/appointment_functions.php';

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
    setFlashMessage('danger', 'Appointment not found or you do not have permission to view it.');
    redirect('pages/student/view_appointments.php');
}

// Get appointment history
$history = getAppointmentHistory($appointmentId);

// Set page title
$pageTitle = 'Appointment Details';

// Check if appointment can be cancelled
$canCancel = false;
if (!$appointment['is_cancelled'] && !isPast($appointment['appointment_date'] . ' ' . $appointment['start_time'])) {
    $appointmentTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
    $canCancel = getHoursDifference($appointmentTime, date('Y-m-d H:i:s')) >= MIN_CANCEL_HOURS;
}

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Appointment Details</h1>
    <a href="view_appointments.php" class="btn btn-secondary">Back to Appointments</a>
</div>

<div class="appointment-detail-card">
    <div class="card-section">
        <h2>Appointment Information</h2>
        <table class="detail-table">
            <tr>
                <th>Faculty:</th>
                <td><?php echo $appointment['faculty_first_name'] . ' ' . $appointment['faculty_last_name']; ?></td>
            </tr>
            <tr>
                <th>Department:</th>
                <td><?php echo $appointment['department_name']; ?></td>
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
                <th>Status:</th>
                <td>
                    <?php if ($appointment['is_cancelled']): ?>
                        <span class="badge badge-danger">Cancelled</span>
                    <?php elseif ($appointment['is_approved']): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Reason for Consultation:</th>
                <td><?php echo nl2br(htmlspecialchars($appointment['remarks'])); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="card-section">
        <h2>Appointment Timeline</h2>
        <div class="timeline">
            <?php foreach ($history as $event): ?>
                <div class="timeline-item">
                    <div class="timeline-badge 
                        <?php 
                        switch($event['status_change']) {
                            case 'created': echo 'badge-info'; break;
                            case 'approved': echo 'badge-success'; break;
                            case 'rejected': echo 'badge-danger'; break;
                            case 'cancelled': echo 'badge-warning'; break;
                            default: echo 'badge-secondary';
                        }
                        ?>">
                        <i class="icon-<?php echo $event['status_change']; ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>
                            <?php 
                            switch($event['status_change']) {
                                case 'created': echo 'Appointment Created'; break;
                                case 'approved': echo 'Appointment Approved'; break;
                                case 'rejected': echo 'Appointment Rejected'; break;
                                case 'cancelled': echo 'Appointment Cancelled'; break;
                                default: echo ucfirst($event['status_change']);
                            }
                            ?>
                        </h4>
                        <p>
                            <small><?php echo date('F j, Y g:i A', strtotime($event['changed_at'])); ?></small><br>
                            <?php if ($event['notes']): ?>
                                <em><?php echo htmlspecialchars($event['notes']); ?></em><br>
                            <?php endif; ?>
                            By: <?php echo $event['first_name'] . ' ' . $event['last_name']; ?> (<?php echo ucfirst($event['role']); ?>)
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if ($canCancel): ?>
    <div class="card-actions">
        <a href="cancel_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel Appointment</a>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>