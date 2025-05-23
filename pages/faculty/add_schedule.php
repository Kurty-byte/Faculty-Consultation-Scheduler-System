<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Add Schedule';

// Include required functions
require_once '../../includes/schedule_functions.php';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get days of week
$daysOfWeek = getDaysOfWeek();

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Add New Consultation Schedule</h1>
    <a href="<?php echo BASE_URL; ?>pages/faculty/manage_schedules.php" class="btn btn-secondary">Back to Schedules</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>pages/faculty/add_schedule_process.php" method="POST">
            <div class="form-group">
                <label for="day_of_week">Day of Week</label>
                <select name="day_of_week" id="day_of_week" class="form-control" required>
                    <?php foreach ($daysOfWeek as $value => $label): ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" name="start_time" id="start_time" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" name="end_time" id="end_time" class="form-control" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Create Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation for time inputs
    const form = document.querySelector('form');
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    
    form.addEventListener('submit', function(event) {
        if (startTime.value >= endTime.value) {
            event.preventDefault();
            alert('End time must be after start time.');
        }
    });
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>