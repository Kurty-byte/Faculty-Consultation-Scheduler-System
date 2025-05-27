<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$pageTitle = 'Student Dashboard';

// Include notification system for time formatting
require_once '../../includes/notification_system.php';
require_once '../../includes/appointment_functions.php';

// Get student ID
$studentId = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']])['student_id'];

$pendingAppointments = getAppointmentsWithTimeDisplay(
    "SELECT a.*, u.first_name, u.last_name, d.department_name, a.cancellation_reason,
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
     JOIN faculty f ON s.faculty_id = f.faculty_id 
     JOIN users u ON f.user_id = u.user_id 
     JOIN departments d ON f.department_id = d.department_id 
     WHERE a.student_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0 
     ORDER BY a.appointment_date ASC, a.start_time ASC 
     LIMIT 5",
    [$studentId]
);

$upcomingAppointments = getAppointmentsWithTimeDisplay(
    "SELECT a.*, u.first_name, u.last_name, d.department_name, a.cancellation_reason,
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
     JOIN faculty f ON s.faculty_id = f.faculty_id 
     JOIN users u ON f.user_id = u.user_id 
     JOIN departments d ON f.department_id = d.department_id 
     WHERE a.student_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
     AND (a.appointment_date > CURDATE() OR 
          (a.appointment_date = CURDATE() AND a.start_time > CURTIME()))
     ORDER BY a.appointment_date ASC, a.start_time ASC 
     LIMIT 5",
    [$studentId]
);

$todayAppointments = fetchRows(
    "SELECT a.* FROM appointments a 
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
     WHERE a.student_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
     AND a.appointment_date = CURDATE()
     AND a.end_time > CURTIME()
     AND a.completed_at IS NULL",
    [$studentId]
);

// Include header
include '../../includes/header.php';
?>

<h1>Student Dashboard</h1>

<div class="dashboard-stats">
    <div class="stat-box warning">
        <div class="stat-content">
            <h3>Pending Approval</h3>
            <p class="stat-number"><?php echo count($pendingAppointments); ?></p>
            <p class="stat-text">Awaiting faculty response</p>
        </div>
        <div class="stat-icon">📋</div>
        <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=pending" class="btn btn-primary btn-sm">View All</a>
    </div>
    
    <div class="stat-box success">
        <div class="stat-content">
            <h3>Upcoming Today</h3>
            <p class="stat-number"><?php echo count($todayAppointments); ?></p>
            <p class="stat-text">Scheduled for today</p>
        </div>
        <div class="stat-icon">📅</div>
        <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=approved" class="btn btn-success btn-sm">View Schedule</a>
    </div>
    
    <div class="stat-box info">
        <div class="stat-content">
            <h3>Completed</h3>
            <p class="stat-number"><?php 
            $completedCount = count(fetchRows(
                "SELECT appointment_id FROM appointments WHERE student_id = ? AND completed_at IS NOT NULL",
                [$studentId]
            ));
            echo $completedCount;
            ?></p>
            <p class="stat-text">Finished consultations</p>
        </div>
        <div class="stat-icon">✅</div>
        <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=completed" class="btn btn-info btn-sm">View Completed</a>
    </div>
    
    <div class="stat-box warning">
        <div class="stat-content">
            <h3>Missed</h3>
            <p class="stat-number"><?php 
            $missedCount = count(fetchRows(
                "SELECT appointment_id FROM appointments WHERE student_id = ? AND is_missed = 1",
                [$studentId]
            ));
            echo $missedCount;
            ?></p>
            <p class="stat-text">Faculty no-shows</p>
        </div>
        <div class="stat-icon">⚠️</div>
        <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=missed" class="btn btn-warning btn-sm">View Missed</a>
    </div>
</div>

<div class="dashboard-section">
    <div class="dashboard-section-header">
        <h2>Pending Appointments</h2>
        <?php if (count($pendingAppointments) > 0): ?>
            <a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php?status=pending" class="btn btn-primary btn-sm">View All Pending</a>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-section-body">
        <?php if (empty($pendingAppointments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <div class="empty-state-text">No pending requests</div>
                <p>You don't have any appointment requests waiting for approval.</p>
                <a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" class="btn btn-primary">Book New Appointment</a>
            </div>
        <?php else: ?>
            <div class="appointments-list">
                <?php foreach ($pendingAppointments as $appointment): ?>
                <?php
                $itemClass = 'appointment-item';
                if ($appointment['is_missed']) {
                    $itemClass .= ' missed';
                } elseif ($appointment['is_cancelled']) {
                    $itemClass .= ' cancelled';
                } elseif ($appointment['is_approved']) {
                    $itemClass .= ' approved';  
                } else {
                    $itemClass .= ' pending';
                }
                ?>
                <div class="<?php echo $itemClass; ?>">
                    <?php if ($appointment['is_missed']): ?>
                        <div class="appointment-status-indicator missed"></div>
                    <?php elseif ($appointment['is_cancelled']): ?>
                        <div class="appointment-status-indicator cancelled"></div>
                    <?php elseif ($appointment['is_approved']): ?>
                        <div class="appointment-status-indicator approved"></div>
                    <?php else: ?>
                        <div class="appointment-status-indicator pending"></div>
                    <?php endif; ?>
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
                            <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            <?php
                            // Check if can be canceled (24 hours before) and not missed
                            $appointmentTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
                            $hoursDifference = getHoursDifference($appointmentTime, date('Y-m-d H:i:s'));
                            
                            if (!$appointment['is_missed'] && $hoursDifference >= MIN_CANCEL_HOURS && empty($appointment['completed_at'])):
                            ?>
                                <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                class="btn btn-danger btn-sm" 
                                onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                            <?php elseif (canStudentCompleteAppointment($appointment['appointment_id'], $studentId) && empty($appointment['completed_at']) && !$appointment['is_missed']): ?>
                                <a href="complete_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                class="btn btn-success btn-sm">Mark Complete</a>
                            <?php elseif (!$appointment['is_missed']): ?>
                                <button class="btn btn-secondary btn-sm" disabled 
                                        title="Cannot cancel appointments less than <?php echo MIN_CANCEL_HOURS; ?> hours before the scheduled time">
                                    Cannot Cancel
                                </button>
                            <?php endif; ?>
                            
                            <?php if (canMarkAppointmentAsMissed($appointment['appointment_id'], 'student')): ?>
                                <a href="mark_missed.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                class="btn btn-warning btn-sm" title="Mark faculty as missed">Mark Missed</a>
                            <?php endif; ?>
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
                <p>Your approved appointments will appear here.</p>
                <a href="view_faculty.php" class="btn btn-primary">Book an Appointment</a>
            </div>
        <?php else: ?>
            <div class="appointments-list">
                <?php foreach ($upcomingAppointments as $appointment): ?>
                <?php
                $itemClass = 'appointment-item';
                if ($appointment['is_missed']) {
                    $itemClass .= ' missed';
                } elseif ($appointment['is_approved']) {
                    $itemClass .= ' approved';
                } else {
                    $itemClass .= ' pending';
                }
                ?>
                <div class="<?php echo $itemClass; ?>">
                    <?php if ($appointment['is_missed']): ?>
                        <div class="appointment-status-indicator missed"></div>
                    <?php else: ?>
                        <div class="appointment-status-indicator approved"></div>
                    <?php endif; ?>
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
                            <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            <?php
                            // Check if can be canceled (24 hours before) and not missed
                            $appointmentTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
                            $hoursDifference = getHoursDifference($appointmentTime, date('Y-m-d H:i:s'));
                            
                            if (!$appointment['is_missed'] && $hoursDifference >= MIN_CANCEL_HOURS):
                            ?>
                                <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                class="btn btn-danger btn-sm" 
                                onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                            <?php elseif (!$appointment['is_missed']): ?>
                                <button class="btn btn-secondary btn-sm" disabled 
                                        title="Cannot cancel appointments less than <?php echo MIN_CANCEL_HOURS; ?> hours before the scheduled time">
                                    Cannot Cancel
                                </button>
                            <?php endif; ?>
                            
                            <?php if (canMarkAppointmentAsMissed($appointment['appointment_id'], 'student')): ?>
                                <a href="mark_missed.php?id=<?php echo $appointment['appointment_id']; ?>" 
                                class="btn btn-warning btn-sm" title="Mark faculty as missed">Mark Missed</a>
                            <?php endif; ?>
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

.stat-box.warning {
    border-left-color: var(--warning);
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

.stat-box.warning {
    border-left-color: var(--warning);
}

.stat-box.info {
    border-left-color: var(--info);
}

.appointment-status-indicator.missed {
    background-color: var(--warning);
}

.appointment-item.missed {
    border-left-color: var(--warning);
    background-color: rgba(255, 193, 7, 0.02);
}

.appointment-status-indicator.cancelled {
    background-color: var(--danger);
}

.btn-warning {
    background-color: var(--warning);
    border-color: var(--warning);
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #212529;
}

.btn-info {
    background-color: var(--info);
    border-color: var(--info);
    color: white;
}

.btn-info:hover {
    background-color: #258391;
    border-color: #1e6b73;
    color: white;
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>