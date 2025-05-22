<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/timeslot_functions.php';

// Check if break ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'Break ID is required.');
    redirect('pages/faculty/manage_breaks.php');
}

// Get break ID
$breakId = (int)$_GET['id'];

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get break details to verify ownership
$break = fetchRow(
    "SELECT * FROM consultation_breaks WHERE break_id = ? AND faculty_id = ?",
    [$breakId, $facultyId]
);

if (!$break) {
    setFlashMessage('danger', 'Break not found or you do not have permission to delete it.');
    redirect('pages/faculty/manage_breaks.php');
}

// Check if deleting this break would affect future appointments
// (This is informational - we'll allow deletion but warn about it)
$futureAppointmentsDuringBreak = fetchRows(
    "SELECT a.appointment_id, a.appointment_date, a.start_time, a.end_time, u.first_name, u.last_name
     FROM appointments a
     JOIN availability_schedules s ON a.schedule_id = s.schedule_id
     JOIN students st ON a.student_id = st.student_id
     JOIN users u ON st.user_id = u.user_id
     WHERE s.faculty_id = ? 
     AND a.appointment_date >= CURDATE()
     AND DAYOFWEEK(a.appointment_date) = ?
     AND ((a.start_time >= ? AND a.start_time < ?) OR 
          (a.end_time > ? AND a.end_time <= ?) OR
          (a.start_time <= ? AND a.end_time >= ?))
     AND a.is_cancelled = 0",
    [
        $facultyId,
        getDayOfWeekNumber($break['day_of_week']),
        $break['start_time'], $break['end_time'],
        $break['start_time'], $break['end_time'],
        $break['start_time'], $break['end_time']
    ]
);

// Process deletion if confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] == '1') {
    try {
        $result = deleteConsultationBreak($breakId);
        
        if ($result) {
            $successMessage = 'Break deleted successfully for ' . ucfirst($break['day_of_week']) . 
                             ' (' . formatTime($break['start_time']) . ' - ' . formatTime($break['end_time']) . ').';
            
            // Calculate how many slots are now available
            $start = strtotime($break['start_time']);
            $end = strtotime($break['end_time']);
            $diffHours = ($end - $start) / 3600;
            $availableSlots = floor($diffHours * 2); // 30-minute slots
            
            if ($availableSlots > 0) {
                $successMessage .= ' ' . $availableSlots . ' appointment slot' . ($availableSlots > 1 ? 's' : '') . 
                                  ' are now available for booking.';
            }
            
            setFlashMessage('success', $successMessage);
        } else {
            setFlashMessage('danger', 'Failed to delete break. Please try again.');
        }
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to delete break: ' . $e->getMessage());
    }
    
    redirect('pages/faculty/manage_breaks.php');
}

// Helper function to convert day name to MySQL DAYOFWEEK number
function getDayOfWeekNumber($dayName) {
    $days = [
        'sunday' => 1,
        'monday' => 2,
        'tuesday' => 3,
        'wednesday' => 4,
        'thursday' => 5,
        'friday' => 6,
        'saturday' => 7
    ];
    
    return isset($days[strtolower($dayName)]) ? $days[strtolower($dayName)] : 1;
}

// If we get here, show confirmation page
$pageTitle = 'Delete Break';
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Delete Consultation Break</h1>
    <a href="<?php echo BASE_URL; ?>pages/faculty/manage_breaks.php" class="btn btn-secondary">Back to Breaks</a>
</div>

<div class="delete-confirmation">
    <div class="confirmation-card">
        <div class="warning-icon">⚠️</div>
        <h2>Confirm Break Deletion</h2>
        
        <div class="break-details">
            <h3>Break to Delete:</h3>
            <div class="break-info">
                <div class="break-day"><?php echo ucfirst($break['day_of_week']); ?></div>
                <div class="break-time"><?php echo formatTime($break['start_time']) . ' - ' . formatTime($break['end_time']); ?></div>
                <div class="break-type">
                    <span class="break-type-badge break-<?php echo $break['break_type']; ?>">
                        <?php echo ucfirst($break['break_type']); ?>
                    </span>
                    <?php if ($break['break_name']): ?>
                        <span class="break-name"><?php echo htmlspecialchars($break['break_name']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($futureAppointmentsDuringBreak)): ?>
            <div class="appointment-conflicts">
                <h3>⚠️ Warning: Existing Appointments</h3>
                <p>The following appointments are scheduled during this break time:</p>
                <div class="conflict-list">
                    <?php foreach ($futureAppointmentsDuringBreak as $appointment): ?>
                        <div class="conflict-item">
                            <strong><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></strong><br>
                            <?php echo formatDate($appointment['appointment_date']); ?> at 
                            <?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="conflict-warning">
                    <strong>Note:</strong> These appointments will remain scheduled. After deleting this break, 
                    students will be able to book additional appointments during this time period.
                </p>
            </div>
        <?php else: ?>
            <div class="no-conflicts">
                <h3>✅ No Conflicts</h3>
                <p>No existing appointments are scheduled during this break time.</p>
                <?php
                // Calculate available slots
                $start = strtotime($break['start_time']);
                $end = strtotime($break['end_time']);
                $diffHours = ($end - $start) / 3600;
                $availableSlots = floor($diffHours * 2);
                
                if ($availableSlots > 0):
                ?>
                    <p class="available-slots">
                        After deletion, <strong><?php echo $availableSlots; ?></strong> appointment slot<?php echo $availableSlots > 1 ? 's' : ''; ?> 
                        will become available for student booking.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="confirmation-actions">
            <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&confirm=1" class="btn btn-danger btn-lg">
                Yes, Delete Break
            </a>
            <a href="<?php echo BASE_URL; ?>pages/faculty/manage_breaks.php" class="btn btn-secondary btn-lg">
                Cancel
            </a>
        </div>
        
        <p class="deletion-note">
            <strong>Note:</strong> This action cannot be undone. You can always create a new break later if needed.
        </p>
    </div>
</div>

<style>
.delete-confirmation {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 500px;
    padding: 2rem;
}

.confirmation-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 2.5rem;
    max-width: 600px;
    text-align: center;
}

.warning-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.confirmation-card h2 {
    color: var(--dark);
    margin-bottom: 2rem;
}

.break-details {
    background-color: #f8f9fc;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-left: 4px solid var(--primary);
}

.break-details h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--dark);
    text-align: left;
}

.break-info {
    text-align: left;
}

.break-day {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.break-time {
    font-size: 1.1rem;
    color: var(--primary);
    font-weight: 500;
    margin-bottom: 0.75rem;
}

.break-type {
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
    font-style: italic;
    color: var(--gray);
}

.appointment-conflicts {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.appointment-conflicts h3 {
    margin-top: 0;
    color: #dc2626;
}

.conflict-list {
    margin: 1rem 0;
    padding: 1rem;
    background-color: white;
    border-radius: 6px;
    border-left: 3px solid #f87171;
}

.conflict-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #fee2e2;
}

.conflict-item:last-child {
    border-bottom: none;
}

.conflict-warning {
    background-color: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 6px;
    padding: 1rem;
    color: #9a3412;
    font-size: 0.9rem;
}

.no-conflicts {
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.no-conflicts h3 {
    margin-top: 0;
    color: #166534;
}

.available-slots {
    background-color: white;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1rem;
    border-left: 3px solid #22c55e;
    color: var(--dark);
}

.confirmation-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.deletion-note {
    font-size: 0.9rem;
    color: var(--gray);
    font-style: italic;
}

@media (max-width: 768px) {
    .confirmation-card {
        padding: 1.5rem;
        margin: 1rem;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .confirmation-actions .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>