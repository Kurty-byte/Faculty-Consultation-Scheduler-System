<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Consultation Hours';

// Include required functions
require_once '../../includes/timeslot_functions.php';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get faculty's consultation hours
$consultationHours = getFacultyConsultationHours($facultyId);

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Consultation Hours Management</h1>
    <div class="page-actions">
        <a href="<?php echo BASE_URL; ?>pages/faculty/set_consultation_hours.php" class="btn btn-primary">
            <?php echo empty($consultationHours) ? 'Set Up Hours' : 'Modify Hours'; ?>
        </a>
    </div>
</div>

<?php if (empty($consultationHours)): ?>
    <div class="consultation-setup-prompt">
        <div class="setup-card">
            <div class="setup-icon">⏰</div>
            <h2>Set Up Your Consultation Hours</h2>
            <p>You haven't set up your consultation hours yet. Define when you're available to meet with students.</p>
            <p><strong>How it works:</strong></p>
            <ul>
                <li>Set your daily consultation hours (e.g., Monday-Friday, 9:00 AM - 5:00 PM)</li>
                <li>Students will see 30-minute appointment slots within your available hours</li>
                <li>Each slot can be booked by one student at a time</li>
                <li>Students need your approval before appointments are confirmed</li>
            </ul>
            <a href="<?php echo BASE_URL; ?>pages/faculty/set_consultation_hours.php" class="btn btn-primary btn-lg">Get Started</a>
        </div>
    </div>
<?php else: ?>
    <div class="consultation-overview">
        <div class="overview-card">
            <h2>Your Weekly Consultation Schedule</h2>
            <div class="schedule-grid">
                <?php
                $daysOfWeek = [
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday', 
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday'
                ];
                
                foreach ($daysOfWeek as $dayKey => $dayName):
                    $dayHours = array_filter($consultationHours, function($hour) use ($dayKey) {
                        return $hour['day_of_week'] === $dayKey;
                    });
                ?>
                    <div class="day-schedule <?php echo empty($dayHours) ? 'no-hours' : ''; ?>">
                        <div class="day-header">
                            <h3><?php echo $dayName; ?></h3>
                        </div>
                        <div class="day-content">
                            <?php if (empty($dayHours)): ?>
                                <div class="no-consultation">
                                    <span class="status-badge unavailable">Not Available</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($dayHours as $hours): ?>
                                    <div class="consultation-block">
                                        <div class="time-range">
                                            <span class="time-start"><?php echo formatTime($hours['start_time']); ?></span>
                                            <span class="time-separator">-</span>
                                            <span class="time-end"><?php echo formatTime($hours['end_time']); ?></span>
                                        </div>
                                        <span class="status-badge available">Available</span>
                                        <?php if ($hours['notes']): ?>
                                            <div class="consultation-notes">
                                                <small><?php echo htmlspecialchars($hours['notes']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- <div class="schedule-actions">
            <div class="action-card">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="<?php echo BASE_URL; ?>pages/faculty/set_consultation_hours.php" class="btn btn-primary">
                        <span class="btn-icon">⚙️</span>
                        Modify Hours
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php" class="btn btn-info">
                        <span class="btn-icon">📅</span>
                        View Appointments
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/faculty/dashboard.php" class="btn btn-secondary">
                        <span class="btn-icon">🏠</span>
                        Dashboard
                    </a>
                </div>
            </div> -->
            
            <div class="stats-card">
                <h3>This Week's Overview</h3>
                <?php
                // Calculate total consultation hours per week
                $totalHours = 0;
                
                foreach ($consultationHours as $hours) {
                    $start = strtotime($hours['start_time']);
                    $end = strtotime($hours['end_time']);
                    $totalHours += ($end - $start) / 3600; // Convert to hours
                }
                
                $totalSlots = $totalHours * 2; // 30-minute slots = 2 per hour
                $activeDays = count(array_unique(array_column($consultationHours, 'day_of_week')));
                ?>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($totalHours, 1); ?></div>
                        <div class="stat-label">Hours/Week</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $totalSlots; ?></div>
                        <div class="stat-label">Available Slots</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $activeDays; ?></div>
                        <div class="stat-label">Days Active</div>
                    </div>
                </div>
                
                <div class="schedule-info">
                    <h4>📋 Important Notes</h4>
                    <ul>
                        <li>Each appointment slot is <strong>30 minutes</strong></li>
                        <li>Students need your <strong>approval</strong> for bookings</li>
                        <li>You'll receive notifications for new requests</li>
                        <li>Students can cancel up to 24 hours in advance</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.consultation-setup-prompt {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
    padding: 2rem;
}

.setup-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 3rem;
    text-align: center;
    max-width: 600px;
}

.setup-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.setup-card h2 {
    color: var(--dark);
    margin-bottom: 1rem;
}

.setup-card ul {
    text-align: left;
    margin: 1.5rem 0;
    padding-left: 1.5rem;
}

.setup-card li {
    margin-bottom: 0.5rem;
}

.consultation-overview {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.overview-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.schedule-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.day-schedule {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.day-schedule.no-hours {
    opacity: 0.6;
}

.day-header {
    background-color: var(--primary);
    color: #ffffff;
    padding: 0.75rem;
    text-align: center;
}

.day-header h3 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: white;
}

.day-content {
    padding: 1rem;
}

.no-consultation {
    text-align: center;
}

.consultation-block {
    margin-bottom: 0.5rem;
    padding: 0.75rem;
    background-color: #f8f9fc;
    border-radius: 4px;
    border-left: 3px solid var(--success);
}

.time-range {
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.time-separator {
    margin: 0 0.25rem;
    color: var(--gray);
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
}

.status-badge.available {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.unavailable {
    background-color: #f8d7da;
    color: #721c24;
}

.consultation-notes {
    margin-top: 0.5rem;
    font-style: italic;
    color: var(--gray);
}

.schedule-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.action-card, .stats-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.action-buttons .btn {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    text-align: left;
}

.btn-icon {
    margin-right: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background-color: #f8f9fc;
    border-radius: 6px;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.schedule-info {
    background-color: #f8f9fc;
    border-radius: 6px;
    padding: 1rem;
    border-left: 3px solid var(--info);
}

.schedule-info h4 {
    margin-top: 0;
    margin-bottom: 0.75rem;
    color: var(--dark);
}

.schedule-info ul {
    margin: 0;
    padding-left: 1.2rem;
}

.schedule-info li {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
    color: var(--gray);
}

@media (max-width: 768px) {
    .consultation-overview {
        grid-template-columns: 1fr;
    }
    
    .schedule-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons .btn {
        justify-content: center;
    }
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>