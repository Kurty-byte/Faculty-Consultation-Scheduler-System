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

// Get appointment details with proper status detection
$appointment = fetchRow(
    "SELECT a.*, s.day_of_week, s.faculty_id, f.user_id as faculty_user_id, 
            uf.first_name as faculty_first_name, uf.last_name as faculty_last_name, uf.email as faculty_email,
            us.first_name as student_first_name, us.last_name as student_last_name, us.email as student_email,
            d.department_name, a.cancellation_reason,
            ah.status_change as last_status_change
     FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     JOIN faculty f ON s.faculty_id = f.faculty_id 
     JOIN users uf ON f.user_id = uf.user_id 
     JOIN students st ON a.student_id = st.student_id 
     JOIN users us ON st.user_id = us.user_id 
     JOIN departments d ON f.department_id = d.department_id 
     LEFT JOIN (
         SELECT appointment_id, status_change,
                ROW_NUMBER() OVER (PARTITION BY appointment_id ORDER BY changed_at DESC) as rn
         FROM appointment_history 
         WHERE status_change IN ('cancelled', 'rejected')
     ) ah ON a.appointment_id = ah.appointment_id AND ah.rn = 1
     WHERE a.appointment_id = ?",
    [$appointmentId]
);

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Check if appointment exists and belongs to this faculty
if (!$appointment || $appointment['faculty_id'] != $facultyId) {
    setFlashMessage('danger', 'Appointment not found or you do not have permission to view it.');
    redirect('pages/faculty/view_appointments.php');
}

// Get appointment history
$history = getAppointmentHistory($appointmentId);

// Set page title
$pageTitle = 'Appointment Details';

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Appointment Details</h1>
    <a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php" class="btn btn-secondary">Back to Appointments</a>
</div>

<div class="appointment-detail-card">
    <div class="card-section">
        <h2>Appointment Information</h2>
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
                <th>Day:</th>
                <td><?php echo ucfirst($appointment['day_of_week']); ?></td>
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
                        <?php 
                        if ($appointment['last_status_change'] === 'rejected'): ?>
                            <span class="badge badge-danger">Rejected</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Cancelled</span>
                        <?php endif; ?>
                    <?php elseif ($appointment['is_approved']): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!empty($appointment['completed_at'])): ?>
                <tr>
                    <th>Completed At:</th>
                    <td><?php echo date('F j, Y g:i A', strtotime($appointment['completed_at'])); ?></td>
                </tr>
            <?php endif; ?>
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
            <tr>
                <th>Reason for Consultation:</th>
                <td><?php echo displayTextContent($appointment['remarks']); ?></td>
            </tr>
            <?php if ($appointment['is_cancelled'] && !empty($appointment['cancellation_reason'])): ?>
            <tr>
                <th><?php echo ($appointment['last_status_change'] === 'rejected') ? 'Rejection Reason:' : 'Cancellation Reason:'; ?></th>
                <td style="color: var(--danger); font-weight: 500;"><?php echo displayTextContent($appointment['cancellation_reason']); ?></td>
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

    <?php if (!$appointment['is_approved'] && !$appointment['is_cancelled'] && !$appointment['is_missed'] && empty($appointment['completed_at'])): ?>
        <div class="card-actions">
            <a href="<?php echo BASE_URL; ?>pages/faculty/approve_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-success">Approve Appointment</a>
            <a href="<?php echo BASE_URL; ?>pages/faculty/reject_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this appointment?')">Reject Appointment</a>
        </div>
        <?php elseif (canCompleteAppointment($appointmentId)): ?>
        <div class="card-actions">
            <a href="<?php echo BASE_URL; ?>pages/faculty/complete_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-info">Mark as Completed</a>
        </div>
        <?php endif; ?>

        <?php if (canMarkAppointmentAsMissed($appointmentId, 'faculty')): ?>
        <div class="card-actions">
            <a href="<?php echo BASE_URL; ?>pages/faculty/mark_missed.php?id=<?php echo $appointmentId; ?>" 
            class="btn btn-warning" 
            onclick="return confirm('Are you sure you want to mark this appointment as missed? This action will notify the student.')">
                <span class="btn-icon">⚠️</span>
                Mark Student as Missed
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