<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Set Consultation Hours';

// Include required functions
require_once '../../includes/timeslot_functions.php';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get existing consultation hours
$existingHours = getFacultyConsultationHours($facultyId);

// Organize existing hours by day
$hoursByDay = [];
foreach ($existingHours as $hours) {
    $hoursByDay[$hours['day_of_week']] = $hours;
}

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Set Consultation Hours</h1>
    <a href="<?php echo BASE_URL; ?>pages/faculty/consultation_hours.php" class="btn btn-secondary">Back to Overview</a>
</div>

<div class="consultation-hours-form">
    <div class="form-header">
        <h2>Define Your Weekly Availability</h2>
        <p>Set the days and times when you're available for student consultations. Students will be able to book 30-minute appointments within these hours.</p>
    </div>
    
    <form action="<?php echo BASE_URL; ?>pages/faculty/set_consultation_hours_process.php" method="POST" id="consultationHoursForm">
        <div class="days-grid">
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
                $existingHour = isset($hoursByDay[$dayKey]) ? $hoursByDay[$dayKey] : null;
            ?>
                <div class="day-card">
                    <div class="day-header">
                        <div class="day-toggle">
                            <input type="checkbox" 
                                   id="enable_<?php echo $dayKey; ?>" 
                                   name="days[<?php echo $dayKey; ?>][enabled]" 
                                   value="1"
                                   <?php echo $existingHour && $existingHour['is_active'] ? 'checked' : ''; ?>
                                   onchange="toggleDay('<?php echo $dayKey; ?>')">
                            <label for="enable_<?php echo $dayKey; ?>" class="day-label">
                                <span class="checkbox-custom"></span>
                                <span class="day-name"><?php echo $dayName; ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="day-times" id="times_<?php echo $dayKey; ?>" style="<?php echo (!$existingHour || !$existingHour['is_active']) ? 'display: none;' : ''; ?>">
                        <div class="time-inputs">
                            <div class="time-group">
                                <label for="start_<?php echo $dayKey; ?>">Start Time</label>
                                <input type="time" 
                                       id="start_<?php echo $dayKey; ?>" 
                                       name="days[<?php echo $dayKey; ?>][start_time]" 
                                       value="<?php echo $existingHour ? date('H:i', strtotime($existingHour['start_time'])) : '09:00'; ?>"
                                       class="form-control">
                            </div>
                            
                            <div class="time-separator">to</div>
                            
                            <div class="time-group">
                                <label for="end_<?php echo $dayKey; ?>">End Time</label>
                                <input type="time" 
                                       id="end_<?php echo $dayKey; ?>" 
                                       name="days[<?php echo $dayKey; ?>][end_time]" 
                                       value="<?php echo $existingHour ? date('H:i', strtotime($existingHour['end_time'])) : '17:00'; ?>"
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="time-preview">
                            <span class="preview-label">Available slots:</span>
                            <span class="preview-count" id="slots_<?php echo $dayKey; ?>">0</span>
                            <span class="preview-text">30-minute appointments</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="form-actions">
            <div class="bulk-actions">
                <h3>Quick Setup</h3>
                <div class="preset-buttons">
                    <button type="button" class="btn btn-outline-primary" onclick="setWeekdayHours('09:00', '17:00')">
                        Standard Hours (9 AM - 5 PM, Mon-Fri)
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setWeekdayHours('10:00', '16:00')">
                        Morning Hours (10 AM - 4 PM, Mon-Fri)
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="clearAllDays()">
                        Clear All
                    </button>
                </div>
            </div>
            
            <div class="submit-actions">
                <button type="submit" class="btn btn-primary btn-lg">Save Consultation Hours</button>
                <p class="help-text">After saving, you can add breaks (lunch, meetings) on the next step.</p>
            </div>
        </div>
    </form>
</div>

<style>
.consultation-hours-form {
    max-width: 1000px;
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

.days-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.day-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.day-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.day-header {
    padding: 1rem;
    background-color: #f8f9fc;
    border-bottom: 1px solid #e9ecef;
}

.day-toggle {
    display: flex;
    align-items: center;
}

.day-toggle input[type="checkbox"] {
    display: none;
}

.day-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: 500;
    color: var(--dark);
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 4px;
    margin-right: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.day-toggle input[type="checkbox"]:checked + .day-label .checkbox-custom {
    background-color: var(--primary);
    border-color: var(--primary);
}

.day-toggle input[type="checkbox"]:checked + .day-label .checkbox-custom::after {
    content: '✓';
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}

.day-name {
    font-size: 1.1rem;
}

.day-times {
    padding: 1.5rem;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.time-inputs {
    display: flex;
    align-items: end;
    gap: 1rem;
    margin-bottom: 1rem;
}

.time-group {
    flex: 1;
}

.time-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: var(--gray);
    font-weight: 500;
}

.time-separator {
    color: var(--gray);
    font-weight: 500;
    padding-bottom: 0.25rem;
}

.time-preview {
    background-color: #f8f9fc;
    padding: 0.75rem;
    border-radius: 6px;
    text-align: center;
    font-size: 0.9rem;
}

.preview-label {
    color: var(--gray);
}

.preview-count {
    font-weight: 700;
    color: var(--primary);
    margin: 0 0.25rem;
}

.preview-text {
    color: var(--gray);
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

.bulk-actions h3 {
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
    .days-grid {
        grid-template-columns: 1fr;
    }
    
    .time-inputs {
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize slot counts for existing enabled days
    document.querySelectorAll('input[type="checkbox"]:checked').forEach(function(checkbox) {
        const dayKey = checkbox.id.replace('enable_', '');
        updateSlotCount(dayKey);
    });
    
    // Add event listeners for time changes
    document.querySelectorAll('input[type="time"]').forEach(function(input) {
        input.addEventListener('change', function() {
            const dayKey = this.id.split('_')[1];
            updateSlotCount(dayKey);
        });
    });
});

function toggleDay(dayKey) {
    const checkbox = document.getElementById('enable_' + dayKey);
    const timesDiv = document.getElementById('times_' + dayKey);
    
    if (checkbox.checked) {
        timesDiv.style.display = 'block';
        updateSlotCount(dayKey);
    } else {
        timesDiv.style.display = 'none';
    }
}

function updateSlotCount(dayKey) {
    const startTime = document.getElementById('start_' + dayKey).value;
    const endTime = document.getElementById('end_' + dayKey).value;
    const slotCountElement = document.getElementById('slots_' + dayKey);
    
    if (startTime && endTime && startTime < endTime) {
        const start = new Date('2000-01-01 ' + startTime);
        const end = new Date('2000-01-01 ' + endTime);
        const diffHours = (end - start) / (1000 * 60 * 60);
        const slots = Math.floor(diffHours * 2); // 2 slots per hour (30-minute slots)
        
        slotCountElement.textContent = slots;
    } else {
        slotCountElement.textContent = '0';
    }
}

function setWeekdayHours(startTime, endTime) {
    const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    
    weekdays.forEach(function(day) {
        const checkbox = document.getElementById('enable_' + day);
        const startInput = document.getElementById('start_' + day);
        const endInput = document.getElementById('end_' + day);
        
        checkbox.checked = true;
        startInput.value = startTime;
        endInput.value = endTime;
        
        toggleDay(day);
    });
}

function clearAllDays() {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    days.forEach(function(day) {
        const checkbox = document.getElementById('enable_' + day);
        checkbox.checked = false;
        toggleDay(day);
    });
}

// Form validation
document.getElementById('consultationHoursForm').addEventListener('submit', function(e) {
    const enabledDays = document.querySelectorAll('input[type="checkbox"]:checked');
    
    if (enabledDays.length === 0) {
        e.preventDefault();
        alert('Please select at least one day for consultation hours.');
        return;
    }
    
    // Validate time ranges for enabled days
    let hasError = false;
    enabledDays.forEach(function(checkbox) {
        const dayKey = checkbox.id.replace('enable_', '');
        const startTime = document.getElementById('start_' + dayKey).value;
        const endTime = document.getElementById('end_' + dayKey).value;
        
        if (!startTime || !endTime) {
            hasError = true;
            alert('Please set both start and end times for ' + dayKey);
            return;
        }
        
        if (startTime >= endTime) {
            hasError = true;
            alert('End time must be after start time for ' + dayKey);
            return;
        }
    });
    
    if (hasError) {
        e.preventDefault();
    }
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>