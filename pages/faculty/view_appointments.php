<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'View Appointments';

// Include required functions
require_once '../../includes/appointment_functions.php';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get appointment status filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : null;

// Get appointments - FIXED to properly distinguish between cancelled and rejected
$query = "SELECT a.*, s.day_of_week, st.student_id, u.first_name, u.last_name, u.email,
                 ah.status_change as last_status_change
         FROM appointments a 
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
         JOIN students st ON a.student_id = st.student_id 
         JOIN users u ON st.user_id = u.user_id 
         LEFT JOIN (
             SELECT appointment_id, status_change,
                    ROW_NUMBER() OVER (PARTITION BY appointment_id ORDER BY changed_at DESC) as rn
             FROM appointment_history 
             WHERE status_change IN ('cancelled', 'rejected')
         ) ah ON a.appointment_id = ah.appointment_id AND ah.rn = 1
         WHERE s.faculty_id = ?";

$params = [$facultyId];

if ($statusFilter === 'pending') {
    $query .= " AND a.is_approved = 0 AND a.is_cancelled = 0 AND a.is_missed = 0 AND a.completed_at IS NULL";
} else if ($statusFilter === 'approved') {
    $query .= " AND a.is_approved = 1 AND a.is_cancelled = 0 AND a.is_missed = 0 AND a.completed_at IS NULL";
} else if ($statusFilter === 'completed') {
    $query .= " AND a.completed_at IS NOT NULL";
} else if ($statusFilter === 'cancelled') {
    $query .= " AND a.is_cancelled = 1";
} else if ($statusFilter === 'missed') {
    $query .= " AND a.is_missed = 1";
}

$query .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

$appointments = fetchRows($query, $params);

// Count appointments by status - FIXED to properly count rejected vs cancelled
$pendingCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0 AND a.is_missed = 0 AND a.completed_at IS NULL",
    [$facultyId]
));

$approvedCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 AND a.is_missed = 0 AND a.completed_at IS NULL",
    [$facultyId]
));

$completedCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.completed_at IS NOT NULL",
    [$facultyId]
));

// Count all cancelled appointments (both cancelled and rejected)
$cancelledCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.is_cancelled = 1",
    [$facultyId]
));

$missedCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.is_missed = 1",
    [$facultyId]
));

// Fix the total count calculation
$totalCount = $pendingCount + $approvedCount + $completedCount + $cancelledCount + $missedCount;

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Appointments</h1>
</div>

<div class="appointment-stats">
    <a href="view_appointments.php?status=pending" class="stat-box <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
        <h3>Pending</h3>
        <p class="stat-number"><?php echo $pendingCount; ?></p>
    </a>
    <a href="view_appointments.php?status=approved" class="stat-box <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
        <h3>Approved</h3>
        <p class="stat-number"><?php echo $approvedCount; ?></p>
    </a>
    <a href="view_appointments.php?status=completed" class="stat-box <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
        <h3>Completed</h3>
        <p class="stat-number"><?php echo $completedCount; ?></p>
    </a>
    <a href="view_appointments.php?status=cancelled" class="stat-box <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
        <h3>Withdrawn</h3>
        <p class="stat-number"><?php echo $cancelledCount; ?></p>
    </a>
    <a href="view_appointments.php?status=missed" class="stat-box <?php echo $statusFilter === 'missed' ? 'active' : ''; ?>">
        <h3>Missed</h3>
        <p class="stat-number"><?php echo $missedCount; ?></p>
    </a>
    <a href="view_appointments.php" class="stat-box <?php echo $statusFilter === null ? 'active' : ''; ?>">
        <h3>All</h3>
        <p class="stat-number"><?php echo $totalCount; ?></p>
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
                <th>Student</th>
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
                        <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">View</a>
                        
                        <?php if (!$appointment['is_approved'] && !$appointment['is_cancelled'] && !$appointment['is_missed']): ?>
                            <a href="<?php echo BASE_URL; ?>pages/faculty/approve_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                            <a href="<?php echo BASE_URL; ?>pages/faculty/reject_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                        <?php elseif (canCompleteAppointment($appointment['appointment_id'])): ?>
                            <a href="<?php echo BASE_URL; ?>pages/faculty/complete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-info">Mark Complete</a>
                        <?php endif; ?>
                        
                        <?php if (canMarkAppointmentAsMissed($appointment['appointment_id'], 'faculty')): ?>
                            <a href="<?php echo BASE_URL; ?>pages/faculty/mark_missed.php?id=<?php echo $appointment['appointment_id']; ?>" 
                            class="btn btn-sm btn-warning" title="Mark student as missed">Mark Missed</a>
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

</style>

<?php
// Include footer
include '../../includes/footer.php';
?>