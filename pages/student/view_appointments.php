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

// Get appointments
$appointments = getStudentAppointments($studentId, $statusFilter);

// Count appointments by status
$pendingCount = count(getStudentAppointments($studentId, 'pending'));
$approvedCount = count(getStudentAppointments($studentId, 'approved'));
$cancelledCount = count(getStudentAppointments($studentId, 'cancelled'));

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
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=cancelled" class="stat-box <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
        <h3>Cancelled/Rejected</h3>
        <p class="stat-number"><?php echo $cancelledCount; ?></p>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php" class="stat-box <?php echo $statusFilter === null ? 'active' : ''; ?>">
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
                        <?php if ($appointment['is_cancelled']): ?>
                            <span class="badge badge-danger">Cancelled</span>
                        <?php elseif ($appointment['is_approved']): ?>
                            <span class="badge badge-success">Approved</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>pages/student/appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">View</a>
                        
                        <?php
                        // Show cancel button only for approved or pending appointments that haven't passed
                        if (!$appointment['is_cancelled'] && !isPast($appointment['appointment_date'] . ' ' . $appointment['start_time'])):
                            // Check if can be cancelled (24 hours before)
                            $appointmentTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
                            $canCancel = getHoursDifference($appointmentTime, date('Y-m-d H:i:s')) >= MIN_CANCEL_HOURS;
                            
                            if ($canCancel):
                            ?>
                                <a href="<?php echo BASE_URL; ?>pages/student/cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled title="Cannot cancel appointments less than <?php echo MIN_CANCEL_HOURS; ?> hours before the scheduled time">Cancel</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
// Include footer
include '../../includes/footer.php';
?>