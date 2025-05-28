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
$appointment = fetchRow(
    "SELECT a.*, s.day_of_week, s.faculty_id, f.user_id as faculty_user_id, 
            uf.first_name as faculty_first_name, uf.last_name as faculty_last_name, uf.email as faculty_email,
            us.first_name as student_first_name, us.last_name as student_last_name, us.email as student_email,
            d.department_name, a.cancellation_reason,
            CASE 
                WHEN a.is_cancelled = 1 THEN 'cancelled'
                WHEN a.is_approved = 1 THEN 'approved' 
                ELSE 'pending'
            END as status_text,
            CASE 
                WHEN a.appointment_date < CURDATE() THEN 'past'
                WHEN a.appointment_date = CURDATE() THEN 'today'
                ELSE 'future'
            END as time_status
     FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     JOIN faculty f ON s.faculty_id = f.faculty_id 
     JOIN users uf ON f.user_id = uf.user_id 
     JOIN students st ON a.student_id = st.student_id 
     JOIN users us ON st.user_id = us.user_id 
     JOIN departments d ON f.department_id = d.department_id 
     WHERE a.appointment_id = ?",
    [$appointmentId]
);

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
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php" class="btn btn-secondary">Back to Appointments</a>
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
                    <?php if (!empty($appointment['completed_at'])): ?>
                        <span class="badge badge-success">Completed</span>
                    <?php elseif ($appointment['is_missed']): ?>
                        <span class="badge badge-warning">Missed</span>
                    <?php elseif ($appointment['is_cancelled']): ?>
                        <span class="badge badge-danger">Cancelled</span>
                    <?php elseif ($appointment['is_approved']): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($appointment['is_missed']): ?>
                <tr>
                    <th>Missed At:</th>
                    <td><?php echo date('F j, Y g:i A', strtotime($appointment['missed_at'])); ?></td>
                </tr>
                <tr>
                    <th>Marked as Missed By:</th>
                    <td><?php echo ucfirst($appointment['missed_by']); ?></td>
                </tr>
                <?php if (!empty($appointment['missed_reason'])): ?>
                    <tr>
                        <th>Missed Reason:</th>
                        <td style="color: var(--warning); font-weight: 500;"><?php echo displayTextContent($appointment['missed_reason']); ?></td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($appointment['completed_at'])): ?>
                <tr>
                    <th>Completed At:</th>
                    <td><?php echo date('F j, Y g:i A', strtotime($appointment['completed_at'])); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Reason for Consultation:</th>
                <td><?php echo displayTextContent($appointment['remarks']); ?></td>
            </tr>
            <?php if ($appointment['is_cancelled'] && !empty($appointment['cancellation_reason'])): ?>
            <tr>
                <th>Cancellation Reason:</th>
                <td style="color: var(--danger); font-weight: 500;"><?php displayTextContent($appointment['cancellation_reason']); ?></td>
            </tr>
            <?php endif; ?>
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
                            case 'missed': echo 'badge-warning'; break;
                            case 'completed': echo 'badge-success'; break;
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
                                case 'missed': echo 'Appointment Marked as Missed'; break;
                                case 'completed': echo 'Appointment Completed'; break;
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
    
    <?php if ($canCancel && empty($appointment['completed_at']) && !$appointment['is_missed']): ?>
        <div class="card-actions">
            <a href="<?php echo BASE_URL; ?>pages/student/cancel_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel Appointment</a>
        </div>
        <?php elseif (canStudentCompleteAppointment($appointmentId, $studentId) && empty($appointment['completed_at']) && !$appointment['is_missed']): ?>
        <div class="card-actions">
            <a href="<?php echo BASE_URL; ?>pages/student/complete_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-success">Mark as Completed</a>
        </div>
        <?php endif; ?>

        <?php if (canMarkAppointmentAsMissed($appointmentId, 'student')): ?>
        <div class="card-actions">
            <a href="<?php echo BASE_URL; ?>pages/student/mark_missed.php?id=<?php echo $appointmentId; ?>" 
            class="btn btn-warning" 
            onclick="return confirm('Are you sure you want to mark this appointment as missed? This action will notify the faculty member.')">
                <span class="btn-icon">⚠️</span>
                Mark Faculty as Missed
            </a>
        </div>
    <?php endif; ?>
</div>

<style>

.badge-warning {
    color: #212529;
    background-color: #ffc107;
}

.timeline-badge.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #212529;
}

.btn-icon {
    margin-right: 0.5rem;
}   

</style>

<?php
// Include footer
include '../../includes/footer.php';
?>