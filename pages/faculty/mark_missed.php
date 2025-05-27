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

<div class="missed-appointment-form">
    <div class="appointment-summary-card">
        <h2>📅 Appointment Details</h2>
        <table class="detail-table">
            <tr>
                <th>Student:</th>
                <td><?php echo $appointment['student_first_name'] . ' ' . $appointment['student_last_name']; ?></td>
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
    </div>
    
    <div class="missed-form-card">
        <div class="alert alert-warning">
            <h4>⚠️ Mark Student as Missed</h4>
            <p>You are about to mark this appointment as missed because the student did not show up or was significantly late (20+ minutes).</p>
            <p><strong>This action will:</strong></p>
            <ul>
                <li>Record the appointment as missed in the system</li>
                <li>Notify the student about being marked as missed</li>
                <li>Free up this time slot for future bookings</li>
                <li>Create a permanent record in the appointment history</li>
            </ul>
        </div>
        
        <form action="<?php echo BASE_URL; ?>pages/faculty/mark_missed_process.php" method="POST" class="missed-form" id="missedForm">
            <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
            <input type="hidden" name="missed_by" value="faculty">
            
            <div class="form-group">
                <label for="missed_reason" class="required">Reason for Marking as Missed</label>
                <textarea name="missed_reason" id="missed_reason" class="form-control" rows="4" required 
                          placeholder="Please specify why you're marking this appointment as missed (e.g., student did not show up, student was 30 minutes late, no communication from student, etc.)"></textarea>
                <small class="form-text text-muted">This information will be included in the notification to the student.</small>
                <div class="char-counter">
                    <span class="char-count">0</span>/500 characters
                </div>
            </div>
            
            <div class="form-group">
                <label class="confirmation-checkbox">
                    <input type="checkbox" name="confirm_missed" required>
                    <span class="checkmark"></span>
                    <span class="checkbox-text">I confirm that the student did not attend this appointment or was significantly late (20+ minutes), and I want to mark this appointment as missed.</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-danger btn-lg" id="submitBtn">
                    <span class="btn-icon">❌</span>
                    Mark as Missed
                </button>
                <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $appointmentId; ?>" 
                   class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.missed-appointment-form {
    max-width: 800px;
    margin: 0 auto;
    display: grid;
    gap: 2rem;
}

.appointment-summary-card, .missed-form-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 2rem;
}

.appointment-summary-card h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--primary);
    text-align: center;
}

.missed-form-card .alert {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.missed-form-card .alert h4 {
    color: #856404;
    margin-top: 0;
    margin-bottom: 1rem;
}

.missed-form-card .alert p {
    color: #856404;
    margin-bottom: 0.5rem;
}

.missed-form-card .alert ul {
    color: #856404;
    margin-bottom: 0;
    padding-left: 1.5rem;
}

.missed-form-card .alert li {
    margin-bottom: 0.25rem;
}

.char-counter {
    text-align: right;
    margin-top: 0.5rem;
    font-size: 0.85rem;
}

.char-count {
    font-weight: 600;
    color: var(--primary);
}

.confirmation-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    cursor: pointer;
    line-height: 1.5;
    padding: 1rem;
    background-color: #f8f9fc;
    border-radius: 8px;
    border-left: 4px solid var(--warning);
}

.confirmation-checkbox input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 24px;
    height: 24px;
    border: 2px solid #ddd;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    margin-top: 2px;
    background-color: white;
}

.confirmation-checkbox input[type="checkbox"]:checked + .checkmark {
    background-color: var(--danger);
    border-color: var(--danger);
}

.confirmation-checkbox input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    color: white;
    font-weight: bold;
    font-size: 1rem;
}

.checkbox-text {
    color: var(--gray);
    font-size: 0.95rem;
    font-weight: 500;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn-icon {
    margin-right: 0.5rem;
}

#currentTime {
    font-weight: 600;
    color: var(--primary);
}

@media (max-width: 768px) {
    .missed-appointment-form {
        margin: 1rem;
    }
    
    .appointment-summary-card, .missed-form-card {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const missedForm = document.getElementById('missedForm');
    const submitBtn = document.getElementById('submitBtn');
    const reasonTextarea = document.getElementById('missed_reason');
    const charCount = document.querySelector('.char-count');
    const confirmCheckbox = document.querySelector('input[name="confirm_missed"]');
    
    // Update current time every second
    function updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
        document.getElementById('currentTime').textContent = timeString;
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
        
        if (!confirmCheckbox.checked) {
            e.preventDefault();
            alert('Please confirm that you want to mark this appointment as missed.');
            return;
        }
        
        // Final confirmation
        const confirmAction = confirm(
            'Are you sure you want to mark this appointment as missed?\n\n' +
            'This action will:\n' +
            '• Notify the student\n' +
            '• Record the appointment as missed\n' +
            '• Free up this time slot\n\n' +
            'This action cannot be undone.'
        );
        
        if (!confirmAction) {
            e.preventDefault();
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Marking as Missed...';
        submitBtn.style.opacity = '0.7';
        
        // Re-enable after timeout (fallback)
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="btn-icon">❌</span> Mark as Missed';
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