<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/faculty_functions.php';
require_once '../../includes/timeslot_functions.php';

// Check if required parameters are provided
if (!isset($_GET['faculty_id']) || !isset($_GET['date']) || !isset($_GET['start']) || !isset($_GET['end'])) {
    setFlashMessage('danger', 'Missing appointment information.');
    redirect('pages/student/view_faculty.php');
}

// Get and validate parameters
$facultyId = (int)$_GET['faculty_id'];
$date = sanitize($_GET['date']);
$startTime = sanitize($_GET['start']);
$endTime = sanitize($_GET['end']);

// Validate date is not in the past
if ($date < date('Y-m-d')) {
    setFlashMessage('danger', 'Cannot book appointments for past dates.');
    redirect('pages/student/view_faculty.php');
}

// Get faculty details
$faculty = getFacultyDetails($facultyId);

if (!$faculty) {
    setFlashMessage('danger', 'Faculty not found.');
    redirect('pages/student/view_faculty.php');
}

// Check if faculty has consultation hours set up
if (!hasConsultationHoursSetup($facultyId)) {
    setFlashMessage('danger', 'This faculty member has not set up their consultation hours yet.');
    redirect('pages/student/view_faculty.php');
}

// Verify the time slot is still available
$dayOfWeek = strtolower(date('l', strtotime($date)));
$availableSlots = generateTimeSlots($facultyId, $date);

$slotAvailable = false;
foreach ($availableSlots as $slot) {
    if ($slot['start_time'] === $startTime && $slot['end_time'] === $endTime && $slot['available']) {
        $slotAvailable = true;
        break;
    }
}

if (!$slotAvailable) {
    setFlashMessage('danger', 'This time slot is no longer available. Please select another slot.');
    redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
}

// Calculate appointment duration (should be 30 minutes)
$appointmentDuration = (strtotime($endTime) - strtotime($startTime)) / 60; // in minutes

// Set page title
$pageTitle = 'Book Appointment';

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Book Consultation Appointment</h1>
    <a href="<?php echo BASE_URL; ?>pages/student/faculty_schedule.php?id=<?php echo $facultyId; ?>" class="btn btn-secondary">Back to Available Slots</a>
</div>

<div class="booking-container">
    <div class="appointment-summary">
        <div class="summary-card">
            <div class="summary-header">
                <h2>📅 Appointment Details</h2>
            </div>
            <div class="summary-content">
                <div class="detail-row">
                    <span class="detail-label">Faculty:</span>
                    <span class="detail-value"><?php echo $faculty['first_name'] . ' ' . $faculty['last_name']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Department:</span>
                    <span class="detail-value"><?php echo $faculty['department_name']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo date('l, F j, Y', strtotime($date)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value"><?php echo formatTime($startTime) . ' - ' . formatTime($endTime); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value"><?php echo $appointmentDuration; ?> minutes</span>
                </div>
            </div>
        </div>
        
        <div class="booking-info">
            <h3>🔔 Important Notes</h3>
            <ul>
                <li>Your appointment request needs <strong>faculty approval</strong></li>
                <li>You'll receive a notification once approved or rejected</li>
                <li>Cancel at least <strong>24 hours in advance</strong> if needed</li>
                <li>Be prepared with specific questions or topics to discuss</li>
            </ul>
        </div>
    </div>
    
    <div class="booking-form-container">
        <div class="booking-form">
            <h2>Complete Your Booking</h2>
            
            <form action="<?php echo BASE_URL; ?>pages/student/booking_process.php" method="POST" id="bookingForm">
                <!-- Hidden fields for appointment details -->
                <input type="hidden" name="faculty_id" value="<?php echo $facultyId; ?>">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                <input type="hidden" name="start_time" value="<?php echo $startTime; ?>">
                <input type="hidden" name="end_time" value="<?php echo $endTime; ?>">
                <input type="hidden" name="duration" value="<?php echo $appointmentDuration; ?>">
                
                <div class="form-section">
                    <h3>Consultation Details</h3>
                    
                    <div class="form-group">
                        <label for="modality" class="required">Consultation Type</label>
                        <div class="modality-options">
                            <div class="modality-option">
                                <input type="radio" id="physical" name="modality" value="physical" required>
                                <label for="physical" class="modality-label">
                                    <div class="modality-icon">🏢</div>
                                    <div class="modality-info">
                                        <strong>In-Person</strong>
                                        <p>Meet at the faculty office</p>
                                    </div>
                                </label>
                            </div>
                            <div class="modality-option">
                                <input type="radio" id="virtual" name="modality" value="virtual" required>
                                <label for="virtual" class="modality-label">
                                    <div class="modality-icon">💻</div>
                                    <div class="modality-info">
                                        <strong>Virtual</strong>
                                        <p>Online meeting via video call</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group modality-details" id="physicalDetails" style="display: none;">
                        <label for="location">Meeting Location</label>
                        <input type="text" name="location" id="location" class="form-control" 
                               placeholder="e.g., Faculty Office Room 201, Library Study Room">
                        <small class="form-text">Default location will be the faculty's office if not specified.</small>
                    </div>
                    
                    <div class="form-group modality-details" id="virtualDetails" style="display: none;">
                        <label for="platform">Video Platform</label>
                        <select name="platform" id="platform" class="form-control">
                            <option value="Zoom">Zoom</option>
                            <option value="Google Meet">Google Meet</option>
                            <option value="Microsoft Teams">Microsoft Teams</option>
                            <option value="Other">Other (specify in remarks)</option>
                        </select>
                        <small class="form-text">The faculty will provide the meeting link after approval.</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Purpose of Consultation</h3>
                    
                    <div class="form-group">
                        <label for="consultation_topic">Topic Category</label>
                        <select name="consultation_topic" id="consultation_topic" class="form-control">
                            <option value="Academic Guidance">Academic Guidance</option>
                            <option value="Course Related">Course Related Questions</option>
                            <option value="Research Discussion">Research Discussion</option>
                            <option value="Career Advice">Career Advice</option>
                            <option value="Thesis/Project">Thesis/Project Consultation</option>
                            <option value="Personal Development">Personal Development</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks" class="required">Detailed Description</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="5" required 
                                  placeholder="Please provide specific details about what you'd like to discuss during the consultation. Be as specific as possible to help the faculty prepare for your meeting."></textarea>
                        <small class="form-text">
                            <span class="char-count">0</span>/500 characters
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="preparation">How are you preparing for this consultation?</label>
                        <textarea name="preparation" id="preparation" class="form-control" rows="3" 
                                  placeholder="e.g., I have reviewed the course materials, prepared specific questions, gathered relevant documents..."></textarea>
                        <small class="form-text">Optional: Let the faculty know how you're preparing.</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Contact Information</h3>
                    
                    <div class="form-group">
                        <label for="student_email">Your Email</label>
                        <input type="email" name="student_email" id="student_email" class="form-control" 
                               value="<?php echo $_SESSION['email']; ?>" readonly>
                        <small class="form-text">This is your registered email address.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_phone">Phone Number (Optional)</label>
                        <input type="tel" name="student_phone" id="student_phone" class="form-control" 
                               placeholder="Your phone number for urgent contact">
                    </div>
                </div>
                
                <div class="form-actions">
                    <div class="booking-terms">
                        <label class="terms-checkbox">
                            <input type="checkbox" name="agree_terms" required>
                            <span class="checkmark"></span>
                            I understand that this appointment requires faculty approval and I agree to attend punctually if approved.
                        </label>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary btn-lg">
                            📝 Submit Appointment Request
                        </button>
                        <a href="<?php echo BASE_URL; ?>pages/student/faculty_schedule.php?id=<?php echo $facultyId; ?>" 
                           class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.booking-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.appointment-summary {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.summary-header {
    padding: 1.5rem;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.summary-header h2 {
    margin: 0;
    color: white;
    font-size: 1.3rem;
}

.summary-content {
    padding: 1.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    opacity: 0.9;
}

.detail-value {
    font-weight: 600;
    text-align: right;
}

.booking-info {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.booking-info h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--primary);
}

.booking-info ul {
    margin: 0;
    padding-left: 1.5rem;
}

.booking-info li {
    margin-bottom: 0.5rem;
    color: var(--gray);
}

.booking-form-container {
    position: sticky;
    top: 2rem;
    align-self: start;
}

.booking-form {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 2rem;
}

.booking-form h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--dark);
    text-align: center;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--primary);
    font-size: 1.1rem;
}

.modality-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.modality-option {
    position: relative;
}

.modality-option input[type="radio"] {
    display: none;
}

.modality-label {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    background-color: white;
}

.modality-label:hover {
    border-color: var(--primary);
    background-color: rgba(78, 115, 223, 0.05);
}

.modality-option input[type="radio"]:checked + .modality-label {
    border-color: var(--primary);
    background-color: rgba(78, 115, 223, 0.1);
}

.modality-icon {
    font-size: 1.5rem;
    margin-right: 0.75rem;
}

.modality-info strong {
    display: block;
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.modality-info p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--gray);
}

.modality-details {
    margin-top: 1rem;
    padding: 1rem;
    background-color: #f8f9fc;
    border-radius: 6px;
    border-left: 3px solid var(--primary);
}

.char-count {
    font-weight: 600;
    color: var(--primary);
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.booking-terms {
    margin-bottom: 1.5rem;
}

.terms-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--gray);
    line-height: 1.4;
}

.terms-checkbox input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    margin-top: 2px;
}

.terms-checkbox input[type="checkbox"]:checked + .checkmark {
    background-color: var(--primary);
    border-color: var(--primary);
}

.terms-checkbox input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.action-buttons .btn {
    min-width: 200px;
}

@media (max-width: 768px) {
    .booking-container {
        grid-template-columns: 1fr;
    }
    
    .booking-form-container {
        position: static;
    }
    
    .modality-options {
        grid-template-columns: 1fr;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .detail-value {
        text-align: left;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle modality selection
    const modalityRadios = document.querySelectorAll('input[name="modality"]');
    const physicalDetails = document.getElementById('physicalDetails');
    const virtualDetails = document.getElementById('virtualDetails');
    
    modalityRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'physical') {
                physicalDetails.style.display = 'block';
                virtualDetails.style.display = 'none';
            } else if (this.value === 'virtual') {
                physicalDetails.style.display = 'none';
                virtualDetails.style.display = 'block';
            }
        });
    });
    
    // Character counter for remarks
    const remarksTextarea = document.getElementById('remarks');
    const charCount = document.querySelector('.char-count');
    
    remarksTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count > 500) {
            charCount.style.color = 'var(--danger)';
        } else if (count > 400) {
            charCount.style.color = 'var(--warning)';
        } else {
            charCount.style.color = 'var(--primary)';
        }
    });
    
    // Form validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const modality = document.querySelector('input[name="modality"]:checked');
        const remarks = document.getElementById('remarks').value.trim();
        const agreeTerms = document.querySelector('input[name="agree_terms"]').checked;
        
        if (!modality) {
            e.preventDefault();
            alert('Please select a consultation type (In-Person or Virtual).');
            return;
        }
        
        if (!remarks) {
            e.preventDefault();
            alert('Please provide a detailed description of what you\'d like to discuss.');
            return;
        }
        
        if (remarks.length > 500) {
            e.preventDefault();
            alert('Please limit your description to 500 characters or less.');
            return;
        }
        
        if (!agreeTerms) {
            e.preventDefault();
            alert('Please agree to the booking terms.');
            return;
        }
        
        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '⏳ Submitting Request...';
    });
    
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>