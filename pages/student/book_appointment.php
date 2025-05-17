<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/faculty_functions.php';
require_once '../../includes/schedule_functions.php';

// Check if schedule information is provided
if (!isset($_GET['schedule_id']) || !isset($_GET['date']) || !isset($_GET['start']) || !isset($_GET['end'])) {
    setFlashMessage('danger', 'Missing schedule information.');
    redirect('pages/student/view_faculty.php');
}

// Get schedule information
$scheduleId = (int)$_GET['schedule_id'];
$date = sanitize($_GET['date']);
$startTime = sanitize($_GET['start']);
$endTime = sanitize($_GET['end']);

// Get schedule details
$schedule = getScheduleById($scheduleId);

if (!$schedule) {
    setFlashMessage('danger', 'Schedule not found.');
    redirect('pages/student/view_faculty.php');
}

// Get faculty details
$facultyId = $schedule['faculty_id'];
$faculty = getFacultyDetails($facultyId);

// Check if slot is still available
if (checkSlotBooked($scheduleId, $date, $startTime, $endTime)) {
    setFlashMessage('danger', 'This time slot is no longer available. Please select another slot.');
    redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
}

// Format date and time for display
$formattedDate = formatDate($date);
$formattedTime = formatTime($startTime) . ' - ' . formatTime($endTime);

// Set page title
$pageTitle = 'Book Appointment';

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Book Consultation Appointment</h1>
    <a href="faculty_schedule.php?id=<?php echo $facultyId; ?>" class="btn btn-secondary">Back to Schedule</a>
</div>

<div class="appointment-details">
    <h2>Appointment Details</h2>
    <p><strong>Faculty:</strong> <?php echo $faculty['first_name'] . ' ' . $faculty['last_name']; ?></p>
    <p><strong>Department:</strong> <?php echo $faculty['department_name']; ?></p>
    <p><strong>Date:</strong> <?php echo $formattedDate; ?></p>
    <p><strong>Time:</strong> <?php echo $formattedTime; ?></p>
</div>

<div class="booking-form">
    <form action="booking_process.php" method="POST">
        <input type="hidden" name="schedule_id" value="<?php echo $scheduleId; ?>">
        <input type="hidden" name="date" value="<?php echo $date; ?>">
        <input type="hidden" name="start_time" value="<?php echo $startTime; ?>">
        <input type="hidden" name="end_time" value="<?php echo $endTime; ?>">
        
        <div class="form-group">
            <label for="modality">Consultation Type:</label>
            <select name="modality" id="modality" class="form-control" required>
                <option value="physical">In-Person</option>
                <option value="virtual">Virtual</option>
            </select>
        </div>
        
        <div class="form-group physical-field">
            <label for="location">Location:</label>
            <input type="text" name="location" id="location" class="form-control" placeholder="Faculty Office Room #">
        </div>
        
        <div class="form-group virtual-field" style="display: none;">
            <label for="platform">Platform:</label>
            <select name="platform" id="platform" class="form-control">
                <option value="Zoom">Zoom</option>
                <option value="Google Meet">Google Meet</option>
                <option value="Microsoft Teams">Microsoft Teams</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="remarks">Reason for Consultation:</label>
            <textarea name="remarks" id="remarks" class="form-control" rows="4" required></textarea>
            <small class="form-text text-muted">Please provide details about what you would like to discuss during the consultation.</small>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Request Appointment</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle modality fields
    const modalitySelect = document.getElementById('modality');
    const physicalFields = document.querySelectorAll('.physical-field');
    const virtualFields = document.querySelectorAll('.virtual-field');
    
    modalitySelect.addEventListener('change', function() {
        if (this.value === 'physical') {
            physicalFields.forEach(field => field.style.display = 'block');
            virtualFields.forEach(field => field.style.display = 'none');
        } else {
            physicalFields.forEach(field => field.style.display = 'none');
            virtualFields.forEach(field => field.style.display = 'block');
        }
    });
    
    // Trigger the change event to set initial visibility
    modalitySelect.dispatchEvent(new Event('change'));
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>