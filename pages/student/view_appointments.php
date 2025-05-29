<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/appointment_functions.php';

// Set page title
$pageTitle = 'My Appointments';

// Get student ID
$student = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']]);
$studentId = $student['student_id'];

// Get appointment status filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : null;

// Build the base query - to include appointment history for proper status detection
$query = "SELECT a.*, s.day_of_week, u.first_name, u.last_name, d.department_name,
                 ah.status_change as last_status_change,
                 CASE 
                     WHEN a.completed_at IS NOT NULL THEN 'completed'
                     WHEN a.is_cancelled = 1 THEN 'cancelled'
                     WHEN a.is_approved = 1 THEN 'approved' 
                     ELSE 'pending'
                 END as status_text
          FROM appointments a 
          JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
          JOIN faculty f ON s.faculty_id = f.faculty_id 
          JOIN users u ON f.user_id = u.user_id 
          JOIN departments d ON f.department_id = d.department_id 
          LEFT JOIN (
              SELECT appointment_id, status_change,
                     ROW_NUMBER() OVER (PARTITION BY appointment_id ORDER BY changed_at DESC) as rn
              FROM appointment_history 
              WHERE status_change IN ('cancelled', 'rejected')
          ) ah ON a.appointment_id = ah.appointment_id AND ah.rn = 1
          WHERE a.student_id = ?";

$params = [$studentId];

// Add status filter conditions - FIXED
if ($statusFilter === 'pending') {
    $query .= " AND a.is_approved = 0 AND a.is_cancelled = 0 AND a.is_missed = 0 AND a.completed_at IS NULL";
} elseif ($statusFilter === 'approved') {
    $query .= " AND a.is_approved = 1 AND a.is_cancelled = 0 AND a.is_missed = 0 AND a.completed_at IS NULL";
} elseif ($statusFilter === 'completed') {
    $query .= " AND a.completed_at IS NOT NULL";
} elseif ($statusFilter === 'cancelled') {
    $query .= " AND a.is_cancelled = 1";
} elseif ($statusFilter === 'missed') {
    $query .= " AND a.is_missed = 1";
}

$query .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

// Get appointments
$appointments = fetchRows($query, $params);

// Count appointments by status
$pendingCount = count(fetchRows(
    "SELECT appointment_id FROM appointments WHERE student_id = ? AND is_approved = 0 AND is_cancelled = 0 AND is_missed = 0 AND completed_at IS NULL",
    [$studentId]
));

$approvedCount = count(fetchRows(
    "SELECT appointment_id FROM appointments WHERE student_id = ? AND is_approved = 1 AND is_cancelled = 0 AND is_missed = 0 AND completed_at IS NULL",
    [$studentId]
));

$completedCount = count(fetchRows(
    "SELECT appointment_id FROM appointments WHERE student_id = ? AND completed_at IS NOT NULL",
    [$studentId]
));

$cancelledCount = count(fetchRows(
    "SELECT appointment_id FROM appointments WHERE student_id = ? AND is_cancelled = 1",
    [$studentId]
));

$missedCount = count(fetchRows(
    "SELECT appointment_id FROM appointments WHERE student_id = ? AND is_missed = 1",
    [$studentId]
));

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>My Appointments</h1>
    <a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" class="btn btn-primary">Book New Appointment</a>
</div>

<div class="appointment-stats">
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=pending" class="stat-box <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
        <h3>Pending</h3>
        <p class="stat-number"><?php echo $pendingCount; ?></p>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=approved" class="stat-box <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
        <h3>Approved</h3>
        <p class="stat-number"><?php echo $approvedCount; ?></p>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=completed" class="stat-box <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
        <h3>Completed</h3>
        <p class="stat-number"><?php echo $completedCount; ?></p>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=cancelled" class="stat-box <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
        <h3>Withdrawn</h3>
        <p class="stat-number"><?php echo $cancelledCount; ?></p>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=missed" class="stat-box <?php echo $statusFilter === 'missed' ? 'active' : ''; ?>">
        <h3>Missed</h3>
        <p class="stat-number"><?php echo $missedCount; ?></p>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php" class="stat-box <?php echo $statusFilter === null ? 'active' : ''; ?>">
        <h3>All</h3>
        <p class="stat-number"><?php echo $pendingCount + $approvedCount + $completedCount + $cancelledCount + $missedCount; ?></p>
    </a>
</div>

<?php if (empty($appointments)): ?>
    <div class="alert alert-info">
        No appointments found<?php echo $statusFilter ? ' with this status' : ''; ?>.
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Faculty</th>
                <th>Department</th>
                <th>Date</th>
                <th>Time</th>
                <th>Modality</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                    <td><?php echo $appointment['department_name']; ?></td>
                    <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                    <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
                    <td><?php echo ucfirst($appointment['modality']); ?></td>
                    <td>
                        <?php if (!empty($appointment['completed_at'])): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php elseif ($appointment['is_missed']): ?>
                            <span class="badge badge-warning">Missed</span>
                        <?php elseif ($appointment['is_cancelled']): ?>
                            <?php 
                            // Check if it was rejected or cancelled based on appointment history
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
                    <td>
                        <a href="<?php echo BASE_URL; ?>pages/student/appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">View</a>
                        
                        <?php
                        // Show cancel button only for approved or pending appointments that haven't passed and aren't missed
                        if (!$appointment['is_cancelled'] && !$appointment['is_missed'] && !isPast($appointment['appointment_date'] . ' ' . $appointment['start_time']) && empty($appointment['completed_at'])):
                            // Check if can be cancelled (24 hours before)
                            $appointmentTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
                            $canCancel = getHoursDifference($appointmentTime, date('Y-m-d H:i:s')) >= MIN_CANCEL_HOURS;
                            
                            if ($canCancel):
                            ?>
                                <a href="<?php echo BASE_URL; ?>pages/student/cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled title="Cannot cancel appointments less than <?php echo MIN_CANCEL_HOURS; ?> hours before the scheduled time">Cancel</button>
                            <?php endif; ?>
                        <?php elseif (canStudentCompleteAppointment($appointment['appointment_id'], $studentId) && empty($appointment['completed_at'])): ?>
                            <a href="<?php echo BASE_URL; ?>pages/student/complete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-success">Mark Complete</a>
                        <?php endif; ?>
                        
                        <?php if (canMarkAppointmentAsMissed($appointment['appointment_id'], 'student')): ?>
                            <a href="<?php echo BASE_URL; ?>pages/student/mark_missed.php?id=<?php echo $appointment['appointment_id']; ?>" 
                            class="btn btn-sm btn-warning" title="Mark faculty as missed">Mark Missed</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<style>

.badge-warning {
    color: #212529;
    background-color: #ffc107;
}

.stat-box:nth-child(6) {
    border-left-color: var(--warning);
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

</style>

<?php
// Include footer
include '../../includes/footer.php';
?>