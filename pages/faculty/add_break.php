<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Add Break';

// Include required functions
require_once '../../includes/timeslot_functions.php';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Check if faculty has consultation hours set up
if (!hasConsultationHoursSetup($facultyId)) {
    setFlashMessage('warning', 'Please set up your consultation hours first before adding breaks.');
    redirect('pages/faculty/set_consultation_hours.php');
}

// Get faculty's consultation hours
$consultationHours = getFacultyConsultationHours($facultyId);

// Get pre-selected day if provided
$selectedDay = isset($_GET['day']) ? sanitize($_GET['day']) : '';

// Organize consultation hours by day
$hoursByDay = [];
foreach ($consultationHours as $hours) {
    if ($hours['is_active']) {
        $hoursByDay[$hours['day_of_week']] = $hours;
    }
}

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Add Consultation Break</h1>
    <a href="<?php echo BASE_URL; ?>pages/faculty/manage_breaks.php" class="btn btn-secondary">Back to Breaks</a>
</div>

<div class="add-break-form">
    <div class="form-header">
        <h2>Schedule a Break Period</h2>
        <p>Add breaks for lunch, meetings, or personal time. Students won't be able to book appointments during these periods.</p>
    </div>
    
    <form action="<?php echo BASE_URL; ?>pages/faculty/add_break_process.php" method="POST" id="addBreakForm">
        <div class="form-grid">
            <div class="break-details-section">
                <h3>Break Details</h3>
                
                <div class="form-group">
                    <label for="day_of_week" class="required">Day of Week</label>
                    <select name="day_of_week" id="day_of_week" class="form-control" required onchange="updateAvailableHours()">
                        <option value="">Select a day</option>
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
                            if (isset($hoursByDay[$dayKey])):
                        ?>
                            <option value="<?php echo $dayKey; ?>" <?php echo $selectedDay === $dayKey ? 'selected' : ''; ?>>
                                <?php echo $dayName; ?>
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                    <small class="form-text text-muted">Only days with consultation hours are available.</small>
                </div>
                
                <div class="time-inputs-group">
                    <div class="form-group">
                        <label for="start_time" class="required">Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required onchange="validateBreakTime()">
                    </div>
                    
                    <div class="time-separator">to</div>
                    
                    <div class="form-group">
                        <label for="end_time" class="required">End Time</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required onchange="validateBreakTime()">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="break_type">Break Type</label>
                    <select name="break_type" id="break_type" class="form-control" onchange="toggleBreakName()">
                        <option value="lunch">Lunch Break</option>
                        <option value="meeting">Meeting</option>
                        <option value="personal">Personal Time</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group" id="break_name_group" style="display: none;">
                    <label for="break_name">Break Name/Description</label>
                    <input type="text" name="break_name" id="break_name" class="form-control" placeholder="e.g., Faculty Meeting, Research Time">
                    <small class="form-text text-muted">Optional: Add a description for this break.</small>
                </div>
            </div>
            
            <div class="break-preview-section">
                <h3>Break Preview</h3>
                
                <div class="consultation-hours-display" id="consultationHoursDisplay">
                    <p class="help-text">Select a day to see your consultation hours.</p>
                </div>
                
                <div class="break-preview" id="breakPreview" style="display: none;">
                    <div class="preview-card">
                        <h4>Your Break</h4>
                        <div class="preview-time" id="previewTime">--:-- to --:--</div>
                        <div class="preview-type" id="previewType">Break Type</div>
                        <div class="preview-impact">
                            <span class="impact-label">Blocked slots:</span>
                            <span class="impact-count" id="blockedSlots">0</span>
                            <span class="impact-text">30-minute appointments</span>
                        </div>
                    </div>
                </div>
                
                <div class="validation-messages" id="validationMessages"></div>
            </div>
        </div>
        
        <div class="form-actions">
            <div class="quick-presets">
                <h3>Common Break Times</h3>
                <div class="preset-buttons">
                    <button type="button" class="btn btn-outline-secondary" onclick="setBreakTime('12:00', '13:00', 'lunch')">
                        Lunch (12:00 PM - 1:00 PM)
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setBreakTime('15:00', '15:30', 'personal')">
                        Afternoon Break (3:00 PM - 3:30 PM)
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setBreakTime('14:00', '16:00', 'meeting')">
                        Meeting (2:00 PM - 4:00 PM)
                    </button>
                </div>
            </div>
            
            <div class="submit-actions">
                <button type="submit" class="btn btn-primary btn-lg" id="submitButton" disabled>Add Break</button>
                <p class="help-text">Make sure the break time is within your consultation hours.</p>
            </div>
        </div>
    </form>
</div>

<style>
.add-break-form {
    max-width: 900px;
    margin: 0 auto;
}

.form-header {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.form-header h2 {
    color: var(--dark);
    margin-bottom: 1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.break-details-section, .break-preview-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.break-details-section h3, .break-preview-section h3 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--dark);
    font-size: 1.2rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.time-inputs-group {
    display: flex;
    align-items: end;
    gap: 1rem;
    margin-bottom: 1rem;
}

.time-inputs-group .form-group {
    flex: 1;
    margin-bottom: 0;
}

.time-separator {
    color: var(--gray);
    font-weight: 500;
    padding-bottom: 0.25rem;
}

.consultation-hours-display {
    background-color: #f8f9fc;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 3px solid var(--primary);
}

.hours-info {
    margin-bottom: 0.5rem;
}

.hours-time {
    font-weight: 600;
    color: var(--dark);
}

.hours-duration {
    font-size: 0.9rem;
    color: var(--gray);
}

.break-preview {
    margin-bottom: 1rem;
}

.preview-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}

.preview-card h4 {
    margin: 0 0 1rem;
    color: white;
}

.preview-time {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.preview-type {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.preview-impact {
    background-color: rgba(255,255,255,0.2);
    border-radius: 6px;
    padding: 0.75rem;
    font-size: 0.9rem;
}

.impact-label {
    opacity: 0.9;
}

.impact-count {
    font-weight: 700;
    margin: 0 0.25rem;
}

.impact-text {
    opacity: 0.9;
}

.validation-messages {
    min-height: 40px;
}

.validation-message {
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

.validation-message.error {
    background-color: #fee;
    color: #c53030;
    border-left: 3px solid #f56565;
}

.validation-message.warning {
    background-color: #fffbf0;
    color: #d69e2e;
    border-left: 3px solid #f6ad55;
}

.validation-message.success {
    background-color: #f0fff4;
    color: #38a169;
    border-left: 3px solid #68d391;
}

.form-actions {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: center;
}

.quick-presets h3 {
    margin-bottom: 1rem;
    color: var(--dark);
}

.preset-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.submit-actions {
    text-align: center;
}

.help-text {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: var(--gray);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .time-inputs-group {
        flex-direction: column;
        gap: 1rem;
    }
    
    .time-separator {
        text-align: center;
        padding: 0;
    }
    
    .form-actions {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .preset-buttons {
        margin-bottom: 1rem;
    }
}
</style>

<script>
// Store consultation hours data
const consultationHoursData = <?php echo json_encode($hoursByDay); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize if day is pre-selected
    if (document.getElementById('day_of_week').value) {
        updateAvailableHours();
    }
    
    // Set up event listeners
    document.getElementById('break_type').addEventListener('change', toggleBreakName);
    
    // Initialize break name visibility
    toggleBreakName();
});

function updateAvailableHours() {
    const daySelect = document.getElementById('day_of_week');
    const selectedDay = daySelect.value;
    const hoursDisplay = document.getElementById('consultationHoursDisplay');
    
    if (selectedDay && consultationHoursData[selectedDay]) {
        const hours = consultationHoursData[selectedDay];
        const startTime = formatTime(hours.start_time);
        const endTime = formatTime(hours.end_time);
        
        hoursDisplay.innerHTML = `
            <div class="hours-info">
                <div class="hours-time">${startTime} - ${endTime}</div>
                <div class="hours-duration">Consultation hours for ${daySelect.options[daySelect.selectedIndex].text}</div>
            </div>
        `;
        
        // Enable time inputs
        document.getElementById('start_time').disabled = false;
        document.getElementById('end_time').disabled = false;
        
        // Validate current break time if set
        validateBreakTime();
    } else {
        hoursDisplay.innerHTML = '<p class="help-text">Select a day to see your consultation hours.</p>';
        
        // Disable time inputs
        document.getElementById('start_time').disabled = true;
        document.getElementById('end_time').disabled = true;
        document.getElementById('submitButton').disabled = true;
        
        // Hide preview
        document.getElementById('breakPreview').style.display = 'none';
    }
}

function validateBreakTime() {
    const daySelect = document.getElementById('day_of_week');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const messagesDiv = document.getElementById('validationMessages');
    const submitButton = document.getElementById('submitButton');
    const breakPreview = document.getElementById('breakPreview');
    
    const selectedDay = daySelect.value;
    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;
    
    // Clear previous messages
    messagesDiv.innerHTML = '';
    
    if (!selectedDay || !startTime || !endTime) {
        submitButton.disabled = true;
        breakPreview.style.display = 'none';
        return;
    }
    
    // Basic time validation
    if (startTime >= endTime) {
        showValidationMessage('End time must be after start time.', 'error');
        submitButton.disabled = true;
        breakPreview.style.display = 'none';
        return;
    }
    
    // Check if break is within consultation hours
    const consultationHours = consultationHoursData[selectedDay];
    if (consultationHours) {
        const consultStart = consultationHours.start_time;
        const consultEnd = consultationHours.end_time;
        
        if (startTime < consultStart || endTime > consultEnd) {
            showValidationMessage('Break must be within your consultation hours (' + formatTime(consultStart) + ' - ' + formatTime(consultEnd) + ').', 'error');
            submitButton.disabled = true;
            breakPreview.style.display = 'none';
            return;
        }
    }
    
    // If we get here, validation passed
    showValidationMessage('Break time is valid and within consultation hours.', 'success');
    submitButton.disabled = false;
    
    // Update preview
    updateBreakPreview();
}

function updateBreakPreview() {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const breakType = document.getElementById('break_type').value;
    const breakName = document.getElementById('break_name').value;
    
    // Update preview elements
    document.getElementById('previewTime').textContent = formatTime(startTime) + ' to ' + formatTime(endTime);
    
    let typeText = breakType.charAt(0).toUpperCase() + breakType.slice(1);
    if (breakName && (breakType === 'other' || breakType === 'meeting')) {
        typeText += ': ' + breakName;
    }
    document.getElementById('previewType').textContent = typeText;
    
    // Calculate blocked slots
    const start = new Date('2000-01-01 ' + startTime);
    const end = new Date('2000-01-01 ' + endTime);
    const diffHours = (end - start) / (1000 * 60 * 60);
    const blockedSlots = Math.ceil(diffHours * 2); // 30-minute slots
    
    document.getElementById('blockedSlots').textContent = blockedSlots;
    
    // Show preview
    document.getElementById('breakPreview').style.display = 'block';
}

function showValidationMessage(message, type) {
    const messagesDiv = document.getElementById('validationMessages');
    messagesDiv.innerHTML = `<div class="validation-message ${type}">${message}</div>`;
}

function toggleBreakName() {
    const breakType = document.getElementById('break_type').value;
    const breakNameGroup = document.getElementById('break_name_group');
    const breakNameInput = document.getElementById('break_name');
    
    if (breakType === 'other' || breakType === 'meeting') {
        breakNameGroup.style.display = 'block';
        if (breakType === 'other') {
            breakNameInput.required = true;
        }
    } else {
        breakNameGroup.style.display = 'none';
        breakNameInput.required = false;
        breakNameInput.value = '';
    }
    
    // Update preview if times are set
    if (document.getElementById('start_time').value && document.getElementById('end_time').value) {
        updateBreakPreview();
    }
}

function setBreakTime(startTime, endTime, breakType) {
    const daySelect = document.getElementById('day_of_week');
    
    if (!daySelect.value) {
        alert('Please select a day first.');
        return;
    }
    
    document.getElementById('start_time').value = startTime;
    document.getElementById('end_time').value = endTime;
    document.getElementById('break_type').value = breakType;
    
    toggleBreakName();
    validateBreakTime();
}

function formatTime(timeString) {
    // Convert 24-hour format to 12-hour format for display
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Form submission validation
document.getElementById('addBreakForm').addEventListener('submit', function(e) {
    const daySelect = document.getElementById('day_of_week');
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const breakType = document.getElementById('break_type').value;
    const breakName = document.getElementById('break_name').value;
    
    if (!daySelect.value || !startTime || !endTime) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return;
    }
    
    if (startTime >= endTime) {
        e.preventDefault();
        alert('End time must be after start time.');
        return;
    }
    
    if (breakType === 'other' && !breakName.trim()) {
        e.preventDefault();
        alert('Please provide a name/description for this break.');
        return;
    }
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>