<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Manage Breaks';

// Include required functions
require_once '../../includes/timeslot_functions.php';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Check if faculty has consultation hours set up
if (!hasConsultationHoursSetup($facultyId)) {
    setFlashMessage('warning', 'Please set up your consultation hours first before adding breaks.');
    redirect('pages/faculty/set_consultation_hours.php');
}

// Get faculty's consultation hours and breaks
$consultationHours = getFacultyConsultationHours($facultyId);
$consultationBreaks = getFacultyConsultationBreaks($facultyId);

// Check if this is first time setup
$isFirstTime = isset($_GET['first_time']) && $_GET['first_time'] == '1';

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Consultation Breaks</h1>
    <div class="page-actions">
        <a href="<?php echo BASE_URL; ?>pages/faculty/add_break.php" class="btn btn-primary">Add Break</a>
        <a href="<?php echo BASE_URL; ?>pages/faculty/consultation_hours.php" class="btn btn-secondary">Back to Overview</a>
    </div>
</div>

<?php if ($isFirstTime): ?>
    <div class="first-time-notice">
        <div class="notice-card">
            <div class="notice-icon">☕</div>
            <h2>Add Breaks to Your Schedule</h2>
            <p>Great! Your consultation hours are now set up. You can optionally add breaks for:</p>
            <ul>
                <li><strong>Lunch breaks</strong> - Block time for meals</li>
                <li><strong>Meetings</strong> - Faculty meetings, classes, etc.</li>
                <li><strong>Personal time</strong> - Administrative work, research time</li>
            </ul>
            <p>Students won't be able to book appointments during these break periods.</p>
            <div class="notice-actions">
                <a href="<?php echo BASE_URL; ?>pages/faculty/add_break.php" class="btn btn-primary">Add Your First Break</a>
                <a href="<?php echo BASE_URL; ?>pages/faculty/consultation_hours.php" class="btn btn-secondary">Skip for Now</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="breaks-management">
    <?php if (empty($consultationBreaks)): ?>
        <div class="no-breaks-state">
            <div class="empty-state-card">
                <div class="empty-icon">🕐</div>
                <h2>No Breaks Scheduled</h2>
                <p>You haven't added any breaks to your consultation schedule yet.</p>
                <p>Add breaks for lunch, meetings, or personal time to prevent students from booking during those periods.</p>
                <a href="<?php echo BASE_URL; ?>pages/faculty/add_break.php" class="btn btn-primary btn-lg">Add Your First Break</a>
            </div>
        </div>
    <?php else: ?>
        <div class="breaks-overview">
            <div class="overview-header">
                <h2>Your Scheduled Breaks</h2>
                <p>Breaks prevent students from booking appointments during these times.</p>
            </div>
            
            <div class="breaks-grid">
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
                    // Get consultation hours for this day
                    $dayHours = array_filter($consultationHours, function($hour) use ($dayKey) {
                        return $hour['day_of_week'] === $dayKey && $hour['is_active'];
                    });
                    
                    // Get breaks for this day
                    $dayBreaks = array_filter($consultationBreaks, function($break) use ($dayKey) {
                        return $break['day_of_week'] === $dayKey && $break['is_active'];
                    });
                    
                    // Only show days that have consultation hours
                    if (!empty($dayHours)):
                ?>
                    <div class="day-breaks-card">
                        <div class="day-breaks-header">
                            <h3><?php echo $dayName; ?></h3>
                            <div class="consultation-time">
                                <?php foreach ($dayHours as $hours): ?>
                                    <span class="hours-range">
                                        <?php echo formatTime($hours['start_time']) . ' - ' . formatTime($hours['end_time']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="day-breaks-content">
                            <?php if (empty($dayBreaks)): ?>
                                <div class="no-breaks-day">
                                    <span class="no-breaks-text">No breaks scheduled</span>
                                    <a href="<?php echo BASE_URL; ?>pages/faculty/add_break.php?day=<?php echo $dayKey; ?>" class="btn btn-sm btn-outline-primary">Add Break</a>
                                </div>
                            <?php else: ?>
                                <div class="breaks-list">
                                    <?php foreach ($dayBreaks as $break): ?>
                                        <div class="break-item">
                                            <div class="break-info">
                                                <div class="break-time">
                                                    <span class="time-range">
                                                        <?php echo formatTime($break['start_time']) . ' - ' . formatTime($break['end_time']); ?>
                                                    </span>
                                                </div>
                                                <div class="break-details">
                                                    <span class="break-type-badge break-<?php echo $break['break_type']; ?>">
                                                        <?php echo ucfirst($break['break_type']); ?>
                                                    </span>
                                                    <?php if ($break['break_name']): ?>
                                                        <span class="break-name"><?php echo htmlspecialchars($break['break_name']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="break-actions">
                                                <a href="<?php echo BASE_URL; ?>pages/faculty/delete_break.php?id=<?php echo $break['break_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this break?')">
                                                    Remove
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="day-add-break">
                                    <a href="<?php echo BASE_URL; ?>pages/faculty/add_break.php?day=<?php echo $dayKey; ?>" class="btn btn-sm btn-outline-primary">Add Another Break</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        
        <div class="breaks-summary">
            <div class="summary-card">
                <h3>Break Summary</h3>
                <?php
                // Calculate break statistics
                $breakTypes = [];
                $totalBreakTime = 0;
                
                foreach ($consultationBreaks as $break) {
                    $breakType = $break['break_type'];
                    if (!isset($breakTypes[$breakType])) {
                        $breakTypes[$breakType] = 0;
                    }
                    $breakTypes[$breakType]++;
                    
                    // Calculate break duration
                    $start = strtotime($break['start_time']);
                    $end = strtotime($break['end_time']);
                    $totalBreakTime += ($end - $start) / 3600; // Convert to hours
                }
                ?>
                <div class="summary-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($consultationBreaks); ?></div>
                        <div class="stat-label">Total Breaks</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($totalBreakTime, 1); ?>h</div>
                        <div class="stat-label">Break Time/Week</div>
                    </div>
                </div>
                
                <div class="break-types">
                    <h4>Break Types</h4>
                    <?php foreach ($breakTypes as $type => $count): ?>
                        <div class="break-type-stat">
                            <span class="break-type-badge break-<?php echo $type; ?>"><?php echo ucfirst($type); ?></span>
                            <span class="break-count"><?php echo $count; ?> break<?php echo $count > 1 ? 's' : ''; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.first-time-notice {
    margin-bottom: 2rem;
}

.notice-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.notice-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.notice-card h2 {
    color: white;
    margin-bottom: 1rem;
}

.notice-card ul {
    text-align: left;
    max-width: 400px;
    margin: 1.5rem auto;
    padding-left: 1.5rem;
}

.notice-card li {
    margin-bottom: 0.5rem;
}

.notice-actions {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.no-breaks-state {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
    padding: 2rem;
}

.empty-state-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 3rem;
    text-align: center;
    max-width: 500px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state-card h2 {
    color: var(--dark);
    margin-bottom: 1rem;
}

.breaks-management {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    align-items: start;
}

.overview-header {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.overview-header h2 {
    margin-bottom: 0.5rem;
    color: var(--dark);
}

.breaks-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.day-breaks-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.day-breaks-header {
    background-color: var(--primary);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.day-breaks-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.consultation-time {
    font-size: 0.9rem;
    opacity: 0.9;
}

.hours-range {
    background-color: rgba(255,255,255,0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    margin-left: 0.5rem;
}

.day-breaks-content {
    padding: 1rem;
}

.no-breaks-day {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: #f8f9fc;
    border-radius: 6px;
}

.no-breaks-text {
    color: var(--gray);
    font-style: italic;
}

.breaks-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.break-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background-color: #f8f9fc;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
}

.break-info {
    flex: 1;
}

.break-time {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.time-range {
    font-size: 0.95rem;
}

.break-details {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.break-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.break-type-badge.break-lunch {
    background-color: #fef3c7;
    color: #92400e;
}

.break-type-badge.break-meeting {
    background-color: #dbeafe;
    color: #1e40af;
}

.break-type-badge.break-personal {
    background-color: #f3e8ff;
    color: #7c3aed;
}

.break-type-badge.break-other {
    background-color: #e5e7eb;
    color: #374151;
}

.break-name {
    font-size: 0.85rem;
    color: var(--gray);
    font-style: italic;
}

.day-add-break {
    text-align: center;
    padding-top: 0.5rem;
    border-top: 1px solid #e9ecef;
}

.breaks-summary {
    position: sticky;
    top: 2rem;
}

.summary-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.summary-card h3 {
    margin-bottom: 1rem;
    color: var(--dark);
}

.summary-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
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
    font-size: 1.25rem;
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

.break-types h4 {
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.break-type-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.break-count {
    font-size: 0.85rem;
    color: var(--gray);
}

@media (max-width: 768px) {
    .breaks-management {
        grid-template-columns: 1fr;
    }
    
    .day-breaks-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .break-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .break-actions {
        align-self: flex-end;
    }
    
    .notice-actions {
        flex-direction: column;
    }
    
    .summary-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>