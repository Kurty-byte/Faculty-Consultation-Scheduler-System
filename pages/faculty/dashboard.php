<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Faculty Dashboard';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get pending appointments
$pendingAppointments = fetchRows(
    "SELECT a.*, s.day_of_week, u.first_name, u.last_name 
     FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     JOIN students st ON a.student_id = st.student_id 
     JOIN users u ON st.user_id = u.user_id 
     WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0 
     ORDER BY a.appointment_date ASC, a.start_time ASC 
     LIMIT 5",
    [$facultyId]
);

// Get upcoming appointments
$upcomingAppointments = fetchRows(
    "SELECT a.*, s.day_of_week, u.first_name, u.last_name 
     FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     JOIN students st ON a.student_id = st.student_id 
     JOIN users u ON st.user_id = u.user_id 
     WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
     AND a.appointment_date >= CURDATE() 
     ORDER BY a.appointment_date ASC, a.start_time ASC 
     LIMIT 5",
    [$facultyId]
);

// Include header
include '../../includes/header.php';
?>

<h1>Faculty Dashboard</h1>

<div class="dashboard-stats">
    <div class="stat-box">
        <h3>Pending Appointments</h3>
        <p class="stat-number"><?php echo count($pendingAppointments); ?></p>
        <a href="view_appointments.php?status=pending" class="btn btn-primary">View All</a>
    </div>
</div>

<div class="dashboard-section">
    <h2>Pending Appointments</h2>
    
    <?php if (empty($pendingAppointments)): ?>
        <p>No pending appointments.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingAppointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                        <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                        <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
                        <td>
                            <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="dashboard-section">
    <h2>Upcoming Appointments</h2>
    
    <?php if (empty($upcomingAppointments)): ?>
        <p>No upcoming appointments.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcomingAppointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                        <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                        <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
                        <td><?php echo ucfirst($appointment['modality']); ?></td>
                        <td>
                            <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>