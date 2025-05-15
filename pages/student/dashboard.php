<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$pageTitle = 'Student Dashboard';

// Get student ID
$studentId = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']])['student_id'];

// Get pending appointments
$pendingAppointments = fetchRows(
    "SELECT a.*, u.first_name, u.last_name, d.department_name 
     FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     JOIN faculty f ON s.faculty_id = f.faculty_id 
     JOIN users u ON f.user_id = u.user_id 
     JOIN departments d ON f.department_id = d.department_id 
     WHERE a.student_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0 
     ORDER BY a.appointment_date ASC, a.start_time ASC",
    [$studentId]
);

// Get upcoming appointments
$upcomingAppointments = fetchRows(
    "SELECT a.*, u.first_name, u.last_name, d.department_name 
     FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     JOIN faculty f ON s.faculty_id = f.faculty_id 
     JOIN users u ON f.user_id = u.user_id 
     JOIN departments d ON f.department_id = d.department_id 
     WHERE a.student_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
     AND a.appointment_date >= CURDATE() 
     ORDER BY a.appointment_date ASC, a.start_time ASC",
    [$studentId]
);

// Include header
include '../../includes/header.php';
?>

<h1>Student Dashboard</h1>

<div class="dashboard-actions">
    <a href="view_faculty.php" class="btn btn-primary">Book New Appointment</a>
</div>

<div class="dashboard-section">
    <h2>Pending Appointments</h2>
    
    <?php if (empty($pendingAppointments)): ?>
        <p>No pending appointments.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingAppointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                        <td><?php echo $appointment['department_name']; ?></td>
                        <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                        <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
                        <td><span class="badge badge-warning">Pending</span></td>
                        <td>
                            <a href="view_appointments.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary">View</a>
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
                    <th>Faculty</th>
                    <th>Department</th>
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
                        <td><?php echo $appointment['department_name']; ?></td>
                        <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                        <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
                        <td><?php echo ucfirst($appointment['modality']); ?></td>
                        <td>
                            <a href="view_appointments.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary">View</a>
                            
                            <?php
                            // Check if can be canceled (24 hours before)
                            $appointmentTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
                            $hoursDifference = getHoursDifference($appointmentTime, date('Y-m-d H:i:s'));
                            
                            if ($hoursDifference >= MIN_CANCEL_HOURS):
                            ?>
                                <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                            <?php endif; ?>
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