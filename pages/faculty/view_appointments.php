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

// Get appointments
$query = "SELECT a.*, s.day_of_week, st.student_id, u.first_name, u.last_name, u.email 
         FROM appointments a 
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
         JOIN students st ON a.student_id = st.student_id 
         JOIN users u ON st.user_id = u.user_id 
         WHERE s.faculty_id = ?";

$params = [$facultyId];

if ($statusFilter === 'pending') {
    $query .= " AND a.is_approved = 0 AND a.is_cancelled = 0";
} else if ($statusFilter === 'approved') {
    $query .= " AND a.is_approved = 1 AND a.is_cancelled = 0";
} else if ($statusFilter === 'cancelled') {
    $query .= " AND a.is_cancelled = 1";
}

$query .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

$appointments = fetchRows($query, $params);

// Count appointments by status
$pendingCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0",
    [$facultyId]
));

$approvedCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0",
    [$facultyId]
));

$cancelledCount = count(fetchRows(
    "SELECT a.appointment_id FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE s.faculty_id = ? AND a.is_cancelled = 1",
    [$facultyId]
));

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
    <a href="view_appointments.php?status=cancelled" class="stat-box <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
        <h3>Cancelled/Rejected</h3>
        <p class="stat-number"><?php echo $cancelledCount; ?></p>
    </a>
    <a href="view_appointments.php" class="stat-box <?php echo $statusFilter === null ? 'active' : ''; ?>">
        <h3>All</h3>
        <p class="stat-number"><?php echo $pendingCount + $approvedCount + $cancelledCount; ?></p>
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
                        <?php if ($appointment['is_cancelled']): ?>
                            <span class="badge badge-danger">Cancelled</span>
                        <?php elseif ($appointment['is_approved']): ?>
                            <span class="badge badge-success">Approved</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">View</a>
                        
                        <?php if (!$appointment['is_approved'] && !$appointment['is_cancelled']): ?>
                            <a href="approve_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                            <a href="reject_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<style>
.appointment-stats {
    display: flex;
    margin-bottom: 20px;
}

.stat-box {
    flex: 1;
    padding: 15px;
    margin-right: 10px;
    background-color: #f4f4f4;
    border-radius: 4px;
    text-align: center;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.stat-box:hover, .stat-box.active {
    background-color: #e0e0e0;
}

.stat-box h3 {
    margin-top: 0;
    font-size: 16px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 0;
}

.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
}

.badge-success {
    background-color: #2ecc71;
    color: white;
}

.badge-warning {
    background-color: #f39c12;
    color: white;
}

.badge-danger {
    background-color: #e74c3c;
    color: white;
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>