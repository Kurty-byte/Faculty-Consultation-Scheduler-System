<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/faculty_functions.php';
require_once '../../includes/schedule_functions.php'; // Include schedule functions

// Check if faculty ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'Faculty ID is required.');
    redirect('pages/student/view_faculty.php');
}

// Get faculty ID
$facultyId = (int)$_GET['id'];

// Get faculty details
$faculty = getFacultyDetails($facultyId);

if (!$faculty) {
    setFlashMessage('danger', 'Faculty not found.');
    redirect('pages/student/view_faculty.php');
}

// Set page title
$pageTitle = 'Schedule for ' . $faculty['first_name'] . ' ' . $faculty['last_name'];

// Get date range for schedule (default to next 30 days)
$fromDate = isset($_GET['from']) ? sanitize($_GET['from']) : date('Y-m-d');
$toDate = isset($_GET['to']) ? sanitize($_GET['to']) : date('Y-m-d', strtotime('+30 days'));

// Get tab selection - default to 'recurring'
$activeTab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'recurring';

// Get available schedules
$availableSchedules = getAvailableSchedulesForFaculty($facultyId, $fromDate, $toDate);

// Separate recurring and non-recurring schedules
$recurringSchedules = [];
$nonRecurringSchedules = [];
$processedNonRecurringIds = []; // Track unique non-recurring schedules

foreach ($availableSchedules as $schedule) {
    $scheduleDetails = getScheduleById($schedule['schedule_id']);
    
    if ($scheduleDetails) {
        if ($scheduleDetails['is_recurring'] == 1) {
            // For recurring schedules, add all instances
            $recurringSchedules[] = $schedule;
        } else {
            // For non-recurring schedules, only add unique entries based on schedule_id
            if (!in_array($schedule['schedule_id'], $processedNonRecurringIds)) {
                $nonRecurringSchedules[] = $schedule;
                $processedNonRecurringIds[] = $schedule['schedule_id'];
            }
        }
    }
}

// Determine which schedules to display based on active tab
$displaySchedules = ($activeTab == 'recurring') ? $recurringSchedules : $nonRecurringSchedules;

// Group schedules by date
$schedulesByDate = [];
foreach ($displaySchedules as $schedule) {
    $date = $schedule['date'];
    if (!isset($schedulesByDate[$date])) {
        $schedulesByDate[$date] = [];
    }
    $schedulesByDate[$date][] = $schedule;
}

// Sort dates chronologically
ksort($schedulesByDate);

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Available Consultation Times</h1>
    <a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" class="btn btn-secondary">Back to Faculty Directory</a>
</div>

<div class="faculty-profile">
    <h2><?php echo $faculty['first_name'] . ' ' . $faculty['last_name']; ?></h2>
    <p><strong>Department:</strong> <?php echo $faculty['department_name']; ?></p>
    <p><strong>Email:</strong> <?php echo $faculty['office_email']; ?></p>
    <p><strong>Phone:</strong> <?php echo $faculty['office_phone_number']; ?></p>
</div>

<div class="date-filter">
    <form action="" method="GET" class="form-inline">
        <input type="hidden" name="id" value="<?php echo $facultyId; ?>">
        <input type="hidden" name="tab" value="<?php echo $activeTab; ?>">
        <div class="form-group">
            <label for="from">From:</label>
            <input type="date" name="from" id="from" class="form-control" value="<?php echo $fromDate; ?>" min="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <label for="to">To:</label>
            <input type="date" name="to" id="to" class="form-control" value="<?php echo $toDate; ?>" min="<?php echo date('Y-m-d'); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update Date Range</button>
    </form>
</div>

<!-- Schedule Type Tabs -->
<div class="schedule-tabs">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab == 'recurring' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>pages/student/faculty_schedule.php?id=<?php echo $facultyId; ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>&tab=recurring">
                Weekly Schedules 
                <span class="badge"><?php echo count($recurringSchedules); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab == 'non-recurring' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>pages/student/faculty_schedule.php?id=<?php echo $facultyId; ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>&tab=non-recurring">
                One-time Schedules 
                <span class="badge"><?php echo count($nonRecurringSchedules); ?></span>
            </a>
        </li>
    </ul>
</div>

<?php if (empty($displaySchedules)): ?>
    <div class="alert alert-info">
        <?php if ($activeTab == 'recurring'): ?>
            No weekly recurring consultation slots found for the selected date range.
        <?php else: ?>
            No one-time consultation slots found for the selected date range.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="schedule-list">
        <?php foreach ($schedulesByDate as $date => $schedules): ?>
            <div class="date-section">
                <h3>
                    <?php 
                    $dateObj = new DateTime($date);
                    echo $dateObj->format('l, F j, Y'); 
                    ?>
                </h3>
                <div class="time-slots">
                    <?php foreach ($schedules as $schedule): ?>
                        <div class="time-slot">
                            <span class="time"><?php echo formatTime($schedule['start_time']) . ' - ' . formatTime($schedule['end_time']); ?></span>
                            <a href="<?php echo BASE_URL; ?>pages/student/book_appointment.php?schedule_id=<?php echo $schedule['schedule_id']; ?>&date=<?php echo $date; ?>&start=<?php echo $schedule['start_time']; ?>&end=<?php echo $schedule['end_time']; ?>" class="btn btn-primary">Book</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validate date range
    const fromDate = document.getElementById('from');
    const toDate = document.getElementById('to');
    
    fromDate.addEventListener('change', function() {
        if (toDate.value && this.value > toDate.value) {
            toDate.value = this.value;
        }
    });
    
    toDate.addEventListener('change', function() {
        if (fromDate.value && this.value < fromDate.value) {
            fromDate.value = this.value;
        }
    });
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    if (fromDate.value < today) {
        fromDate.value = today;
    }
    if (toDate.value < today) {
        toDate.value = today;
    }
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>