<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/appointment_functions.php';

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'Appointment ID is required.');
    redirect('pages/faculty/view_appointments.php');
}

// Get appointment ID
$appointmentId = (int)$_GET['id'];

// Get appointment details
$appointment = getAppointmentDetails($appointmentId);

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Check if appointment exists and belongs to this faculty
if (!$appointment || $appointment['faculty_id'] != $facultyId) {
    setFlashMessage('danger', 'Appointment not found or you do not have permission to mark it as missed.');
    redirect('pages/faculty/view_appointments.php');
}
    
// Check if appointment can be marked as missed
if (!canMarkAppointmentAsMissed($appointmentId, 'faculty')) {
    $message = 'This appointment cannot be marked as missed at this time.';
    
    if ($appointment['is_missed']) {
        $message = 'This appointment is already marked as missed.';
    } elseif ($appointment['is_cancelled']) {
        $message = 'Cannot mark a cancelled appointment as missed.';
    } elseif (!empty($appointment['completed_at'])) {
        $message = 'Cannot mark a completed appointment as missed.';
    } elseif (!$appointment['is_approved']) {
        $message = 'Only approved appointments can be marked as missed.';
    } else {
        $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
        $currentDateTime = new DateTime();
        $missedThreshold = clone $appointmentDateTime;
        $missedThreshold->add(new DateInterval('PT20M'));
        
        if ($currentDateTime < $missedThreshold) {
            $message = 'You can only mark an appointment as missed 20 minutes after the scheduled start time.';
        } else {
            $maxMissedTime = clone $appointmentDateTime;
            $maxMissedTime->add(new DateInterval('PT24H'));
            if ($currentDateTime > $maxMissedTime) {
                $message = 'You can only mark an appointment as missed within 24 hours of the scheduled time.';
            }
        }
    }
    
    setFlashMessage('danger', $message);
    redirect('pages/faculty/appointment_details.php?id=' . $appointmentId);
}

// Set page title
$pageTitle = 'Mark Appointment as Missed';

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Mark Appointment as Missed</h1>
    <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Back to Appointment</a>
</div>

<div class="card">
    <div class="card-body">
        <h2>Appointment Details</h2>
        <table class="detail-table">
            <tr>
                <th>Faculty:</th>
                <td><?php echo $appointment['faculty_first_name'] . ' ' . $appointment['faculty_last_name']; ?></td>
            </tr>
            <tr>
                <th>Department:</th>
                <td><?php echo $appointment['department_name']; ?></td>
            </tr>
            <tr>
                <th>Date:</th>
                <td><?php echo formatDate($appointment['appointment_date']); ?></td>
            </tr>
            <tr>
                <th>Scheduled Time:</th>
                <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
            </tr>
            <tr>
                <th>Modality:</th>
                <td><?php echo ucfirst($appointment['modality']); ?></td>
            </tr>
            <?php if ($appointment['modality'] === 'virtual' && $appointment['platform']): ?>
                <tr>
                    <th>Platform:</th>
                    <td><?php echo $appointment['platform']; ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($appointment['modality'] === 'physical' && $appointment['location']): ?>
                <tr>
                    <th>Location:</th>
                    <td><?php echo $appointment['location']; ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Current Time:</th>
                <td><span id="currentTime"><?php echo date('g:i A'); ?></span></td>
            </tr>
        </table>
        
        <div class="alert alert-warning mt-3">
            <strong>Are you sure you want to mark this appointment as missed?</strong> This action will notify the faculty member and create a permanent record in the appointment history.
        </div>
        
        <form action="<?php echo BASE_URL; ?>pages/faculty/mark_missed_process.php" method="POST" class="mt-4" id="missedForm">
            <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
            <input type="hidden" name="missed_by" value="student">
            
            <div class="form-group">
                <label for="missed_reason" class="required">Reason for Marking as Missed:</label>
                <textarea name="missed_reason" id="missed_reason" class="form-control" rows="4" required 
                          placeholder="Please specify why you're marking this appointment as missed (e.g., faculty did not show up, faculty was 30 minutes late, no communication from faculty, etc.)"></textarea>
                <small class="form-text text-muted">This information will be included in the notification to the faculty member.</small>
                <div class="char-counter">
                    <span class="char-count">0</span>/500 characters
                </div>
            </div>
            
            <div class="form-group text-right">
                <button type="submit" class="btn btn-danger" id="submitBtn">Mark as Missed</button>
                <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
    /* Updated CSS to match cancellation interface */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--gray-light);
}

.card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
    overflow: hidden;
}

.card-body {
    padding: 1.25rem;
}

.card h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--dark);
    font-size: 1.5rem;
    font-weight: 600;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
}

.detail-table th {
    width: 30%;
    text-align: right;
    padding: 8px 15px 8px 0;
    vertical-align: top;
    color: var(--gray);
    font-weight: 500;
}

.detail-table td {
    padding: 8px 0;
    color: var(--dark);
}

.alert {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: var(--border-radius);
}

.alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.alert strong {
    font-weight: 600;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--dark);
    font-size: 0.9rem;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: var(--dark);
    background-color: var(--white);
    background-clip: padding-box;
    border: 1px solid #d1d3e2;
    border-radius: var(--border-radius);
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    color: var(--dark);
    background-color: var(--white);
    border-color: var(--primary-light);
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

textarea.form-control {
    height: auto;
    min-height: 100px;
    resize: vertical;
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: var(--secondary);
}

.text-right {
    text-align: right !important;
}

.mt-3 {
    margin-top: 1rem !important;
}

.mt-4 {
    margin-top: 1.5rem !important;
}

/* Button styling to match other pages */
.btn {
    display: inline-block;
    font-weight: var(--font-weight-normal);
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: var(--font-size-base);
    line-height: var(--line-height-base);
    border-radius: var(--border-radius);
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, 
                border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    cursor: pointer;
    text-decoration: none;
    margin-right: 0.5rem;
}

.btn-danger {
    color: var(--white);
    background-color: var(--danger);
    border-color: var(--danger);
}

.btn-danger:hover {
    color: var(--white);
    background-color: #d52a1a;
    border-color: #d52a1a;
    text-decoration: none;
}

.btn-secondary {
    color: var(--white);
    background-color: var(--secondary);
    border-color: var(--secondary);
}

.btn-secondary:hover {
    color: var(--white);
    background-color: #6b6d7d;
    border-color: #6b6d7d;
    text-decoration: none;
}

/* Character counter styling */
.char-counter {
    text-align: right;
    margin-top: 0.5rem;
    font-size: 0.85rem;
}

.char-count {
    font-weight: 600;
    color: var(--primary);
}

/* Current time styling */
#currentTime {
    font-weight: 600;
    color: var(--primary);
}

/* Responsive design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header h1 {
        margin-bottom: 15px;
    }
    
    .text-right {
        text-align: left !important;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 10px;
        margin-right: 0;
    }
    
    .detail-table th {
        width: 40%;
    }
}

@media (max-width: 576px) {
    .detail-table {
        display: block;
    }
    
    .detail-table th,
    .detail-table td {
        display: block;
        width: 100%;
        text-align: left;
    }
    
    .detail-table th {
        padding-bottom: 0;
    }
    
    .detail-table td {
        padding-top: 0;
        padding-bottom: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const missedForm = document.getElementById('missedForm');
    const submitBtn = document.getElementById('submitBtn');
    const reasonTextarea = document.getElementById('missed_reason');
    const charCount = document.querySelector('.char-count');
    
    // Update current time every second
    function updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
        const currentTimeElement = document.getElementById('currentTime');
        if (currentTimeElement) {
            currentTimeElement.textContent = timeString;
        }
    }
    
    setInterval(updateCurrentTime, 1000);
    
    // Character counter for reason textarea
    reasonTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count > 500) {
            charCount.style.color = 'var(--danger)';
            this.style.borderColor = 'var(--danger)';
        } else if (count > 400) {
            charCount.style.color = 'var(--warning)';
            this.style.borderColor = 'var(--warning)';
        } else {
            charCount.style.color = 'var(--primary)';
            this.style.borderColor = '';
        }
    });
    
    // Form validation and submission
    missedForm.addEventListener('submit', function(e) {
        const reason = reasonTextarea.value.trim();
        
        if (!reason) {
            e.preventDefault();
            alert('Please provide a reason for marking this appointment as missed.');
            reasonTextarea.focus();
            return;
        }
        
        if (reason.length > 500) {
            e.preventDefault();
            alert('Please limit your reason to 500 characters or less.');
            reasonTextarea.focus();
            return;
        }
        
        // Final confirmation
        const confirmAction = confirm('Are you sure you want to mark this appointment as missed? This action cannot be undone.');
        
        if (!confirmAction) {
            e.preventDefault();
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Marking as Missed...';
        submitBtn.style.opacity = '0.7';
        
        // Re-enable after timeout (fallback)
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Mark as Missed';
            submitBtn.style.opacity = '1';
        }, 10000);
    });
    
    // Auto-resize textarea
    reasonTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>