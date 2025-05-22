<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Faculty Dashboard';

// Include notification system for time formatting
require_once '../../includes/notification_system.php';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get pending appointments with dynamic time display
$pendingAppointments = getAppointmentsWithTimeDisplay(
    "SELECT a.*, s.day_of_week, u.first_name, u.last_name,
            CASE 
                WHEN a.appointed_on IS NOT NULL THEN a.appointed_on
                ELSE a.updated_on
            END as activity_time,
            UNIX_TIMESTAMP(CASE 
                WHEN a.appointed_on IS NOT NULL THEN a.appointed_on
                ELSE a.updated_on
            END) as activity_timestamp
     FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     JOIN students st ON a.student_id = st.student_id 
     JOIN users u ON st.user_id = u.user_id 
     WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0 
     ORDER BY a.appointment_date ASC, a.start_time ASC 
     LIMIT 5",
    [$facultyId]
);

// Get upcoming appointments with dynamic time display
$upcomingAppointments = getAppointmentsWithTimeDisplay(
    "SELECT a.*, s.day_of_week, u.first_name, u.last_name,
            CASE 
                WHEN a.updated_on IS NOT NULL THEN a.updated_on
                ELSE a.appointed_on
            END as activity_time,
            UNIX_TIMESTAMP(CASE 
                WHEN a.updated_on IS NOT NULL THEN a.updated_on
                ELSE a.appointed_on
            END) as activity_timestamp
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
    <div class="stat-box primary">
        <div class="stat-content">
            <h3>Pending Requests</h3>
            <p class="stat-number"><?php echo count($pendingAppointments); ?></p>
            <p class="stat-text">Need your approval</p>
        </div>
        <div class="stat-icon">📋</div>
        <a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php?status=pending" class="btn btn-primary btn-sm">View All</a>
    </div>
    
    <div class="stat-box success">
        <div class="stat-content">
            <h3>Upcoming Today</h3>
            <?php
            $todayAppointments = array_filter($upcomingAppointments, function($apt) {
                return $apt['appointment_date'] === date('Y-m-d');
            });
            ?>
            <p class="stat-number"><?php echo count($todayAppointments); ?></p>
            <p class="stat-text">Scheduled for today</p>
        </div>
        <div class="stat-icon">📅</div>
        <a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php?status=approved" class="btn btn-success btn-sm">View Schedule</a>
    </div>
</div>

<div class="dashboard-section">
    <div class="dashboard-section-header">
        <h2>Pending Appointment Requests</h2>
        <?php if (count($pendingAppointments) > 0): ?>
            <a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php?status=pending" class="btn btn-primary btn-sm">View All Pending</a>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-section-body">
        <?php if (empty($pendingAppointments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <div class="empty-state-text">No pending requests</div>
                <p>All appointment requests have been reviewed.</p>
            </div>
        <?php else: ?>
            <div class="appointments-list">
                <?php foreach ($pendingAppointments as $appointment): ?>
                    <div class="appointment-item pending">
                        <div class="appointment-status-indicator pending"></div>
                        <div class="appointment-info">
                            <div class="appointment-header">
                                <h4 class="appointment-title"><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></h4>
                                <span class="appointment-time-ago" data-timestamp="<?php echo isset($appointment['activity_timestamp']) ? $appointment['activity_timestamp'] : $appointment['timestamp']; ?>">
                                    Requested <?php echo $appointment['time_ago']; ?>
                                </span>
                            </div>
                            <div class="appointment-details">
                                <span class="appointment-date"><?php echo formatDate($appointment['appointment_date']); ?></span>
                                <span class="appointment-time"><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></span>
                                <span class="appointment-type"><?php echo ucfirst($appointment['modality']); ?></span>
                            </div>
                            <?php if (!empty($appointment['remarks'])): ?>
                                <div class="appointment-reason">
                                    <strong>Reason:</strong> <?php echo htmlspecialchars(substr($appointment['remarks'], 0, 100)) . (strlen($appointment['remarks']) > 100 ? '...' : ''); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="appointment-actions">
                            <a href="<?php echo BASE_URL; ?>pages/faculty/approve_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-success btn-sm">Approve</a>
                            <a href="<?php echo BASE_URL; ?>pages/faculty/reject_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-danger btn-sm">Reject</a>
                            <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-section">
    <div class="dashboard-section-header">
        <h2>Upcoming Appointments</h2>
        <?php if (count($upcomingAppointments) > 0): ?>
            <a href="view_appointments.php?status=approved" class="btn btn-primary btn-sm">View All Upcoming</a>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-section-body">
        <?php if (empty($upcomingAppointments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📅</div>
                <div class="empty-state-text">No upcoming appointments</div>
                <p>Your schedule is currently clear.</p>
            </div>
        <?php else: ?>
            <div class="appointments-list">
                <?php foreach ($upcomingAppointments as $appointment): ?>
                    <div class="appointment-item approved">
                        <div class="appointment-status-indicator approved"></div>
                        <div class="appointment-info">
                            <div class="appointment-header">
                                <h4 class="appointment-title"><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></h4>
                                <span class="appointment-time-ago" data-timestamp="<?php echo isset($appointment['activity_timestamp']) ? $appointment['activity_timestamp'] : $appointment['timestamp']; ?>">
                                    Approved <?php echo $appointment['time_ago']; ?>
                                </span>
                            </div>
                            <div class="appointment-details">
                                <span class="appointment-date 
                                    <?php echo ($appointment['appointment_date'] === date('Y-m-d')) ? 'today' : ''; ?>">
                                    <?php 
                                    if ($appointment['appointment_date'] === date('Y-m-d')) {
                                        echo 'Today';
                                    } else {
                                        echo formatDate($appointment['appointment_date']);
                                    }
                                    ?>
                                </span>
                                <span class="appointment-time"><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></span>
                                <span class="appointment-type"><?php echo ucfirst($appointment['modality']); ?>
                                    <?php if ($appointment['modality'] === 'virtual' && $appointment['platform']): ?>
                                        (<?php echo $appointment['platform']; ?>)
                                    <?php elseif ($appointment['modality'] === 'physical' && $appointment['location']): ?>
                                        (<?php echo $appointment['location']; ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="appointment-actions">
                            <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-box {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
    position: relative;
    transition: transform 0.3s ease;
    border-left: 4px solid var(--primary);
}

.stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.stat-box.primary {
    border-left-color: var(--primary);
}

.stat-box.success {
    border-left-color: var(--success);
}

.stat-content h3 {
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--gray);
    font-weight: 600;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0 0 0.25rem;
}

.stat-text {
    font-size: 0.9rem;
    color: var(--gray);
    margin: 0 0 1rem;
}

.stat-icon {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    font-size: 2rem;
    opacity: 0.3;
}

.appointments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.appointment-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.appointment-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.appointment-item.pending {
    border-left-color: var(--warning);
    background-color: rgba(246, 194, 62, 0.02);
}

.appointment-item.approved {
    border-left-color: var(--success);
    background-color: rgba(28, 200, 138, 0.02);
}

.appointment-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.appointment-status-indicator.pending {
    background-color: var(--warning);
}

.appointment-status-indicator.approved {
    background-color: var(--success);
}

.appointment-info {
    flex: 1;
}

.appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.appointment-title {
    margin: 0;
    font-size: 1.1rem;
    color: var(--dark);
    font-weight: 600;
}

.appointment-time-ago {
    font-size: 0.85rem;
    color: var(--gray);
    font-weight: 500;
}

.appointment-details {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.appointment-details span {
    font-size: 0.9rem;
    color: var(--gray);
}

.appointment-date.today {
    color: var(--primary);
    font-weight: 600;
}

.appointment-reason {
    font-size: 0.85rem;
    color: var(--gray);
    font-style: italic;
    margin-top: 0.5rem;
}

.appointment-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--gray);
}

.empty-state-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.6;
}

.empty-state-text {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: var(--dark);
    font-weight: 500;
}

@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .appointment-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .appointment-header {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }
    
    .appointment-time-ago {
        margin-top: 0.25rem;
    }
    
    .appointment-details {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .appointment-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .stat-box {
        padding: 1rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>