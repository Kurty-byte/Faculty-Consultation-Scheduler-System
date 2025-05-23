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

// IMPROVED VALIDATION: Use the enhanced validation system
$validationErrors = validateAppointmentTimeSlot($facultyId, $date, $startTime, $endTime);

if (!empty($validationErrors)) {
    setFlashMessage('danger', 'Invalid time slot: ' . implode(' ', $validationErrors));
    redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
}

// Double-check: verify the time slot is still available using improved function
if (!isSlotAvailableImproved($facultyId, $date, $startTime, $endTime)) {
    setFlashMessage('danger', 'This time slot is no longer available. Please select another slot.');
    redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
}

// Calculate appointment duration (should be 30 minutes)
$appointmentDuration = (strtotime($endTime) - strtotime($startTime)) / 60; // in minutes

// Check if this is today and if there's enough advance notice
$isToday = ($date === date('Y-m-d'));
if ($isToday) {
    $currentDateTime = new DateTime();
    $slotDateTime = new DateTime($date . ' ' . $startTime);
    $bufferTime = clone $currentDateTime;
    $bufferTime->add(new DateInterval('PT30M')); // 30 minutes buffer
    
    if ($slotDateTime <= $bufferTime) {
        setFlashMessage('danger', 'Appointments must be booked at least 30 minutes in advance.');
        redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
    }
}

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
    <div class="appointment-summary-enhanced">
        <div class="summary-card-enhanced">
            <div class="summary-header-enhanced">
                <h2>📅 Appointment Details</h2>
                <?php if ($isToday): ?>
                    <span class="urgency-badge today">Today's Appointment</span>
                <?php elseif ($date === date('Y-m-d', strtotime('+1 day'))): ?>
                    <span class="urgency-badge tomorrow">Tomorrow's Appointment</span>
                <?php endif; ?>
            </div>
            <div class="summary-content-enhanced">
                <div class="faculty-info-brief">
                    <div class="faculty-avatar">
                        <?php echo strtoupper(substr($faculty['first_name'], 0, 1) . substr($faculty['last_name'], 0, 1)); ?>
                    </div>
                    <div class="faculty-details-brief">
                        <h3><?php echo $faculty['first_name'] . ' ' . $faculty['last_name']; ?></h3>
                        <p><?php echo $faculty['department_name']; ?></p>
                    </div>
                </div>
                
                <div class="appointment-details-grid">
                    <div class="detail-row-enhanced">
                        <span class="detail-icon">📅</span>
                        <div class="detail-content">
                            <span class="detail-label">Date</span>
                            <span class="detail-value"><?php echo date('l, F j, Y', strtotime($date)); ?></span>
                        </div>
                    </div>
                    <div class="detail-row-enhanced">
                        <span class="detail-icon">⏰</span>
                        <div class="detail-content">
                            <span class="detail-label">Time</span>
                            <span class="detail-value"><?php echo formatTime($startTime) . ' - ' . formatTime($endTime); ?></span>
                        </div>
                    </div>
                    <div class="detail-row-enhanced">
                        <span class="detail-icon">⏱️</span>
                        <div class="detail-content">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value"><?php echo $appointmentDuration; ?> minutes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="booking-info-enhanced">
            <h3>🔔 Important Notes</h3>
            <div class="info-list">
                <div class="info-item">
                    <span class="info-icon">✅</span>
                    <span>Your appointment request needs <strong>faculty approval</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-icon">🔔</span>
                    <span>You'll receive a notification once approved or rejected</span>
                </div>
                <div class="info-item">
                    <span class="info-icon">⏰</span>
                    <span>Cancel at least <strong>24 hours in advance</strong> if needed</span>
                </div>
                <div class="info-item">
                    <span class="info-icon">📝</span>
                    <span>Be prepared with specific questions or topics to discuss</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="booking-form-container-enhanced">
        <div class="booking-form-enhanced">
            <h2>Complete Your Booking</h2>
            
            <form action="<?php echo BASE_URL; ?>pages/student/booking_process.php" method="POST" id="bookingForm">
                <!-- Hidden fields for appointment details -->
                <input type="hidden" name="faculty_id" value="<?php echo $facultyId; ?>">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                <input type="hidden" name="start_time" value="<?php echo $startTime; ?>">
                <input type="hidden" name="end_time" value="<?php echo $endTime; ?>">
                <input type="hidden" name="duration" value="<?php echo $appointmentDuration; ?>">
                
                <div class="form-section-enhanced">
                    <h3>Consultation Details</h3>
                    
                    <div class="form-group">
                        <label for="modality" class="required">Consultation Type</label>
                        <div class="modality-options-enhanced">
                            <div class="modality-option-enhanced">
                                <input type="radio" id="physical" name="modality" value="physical" required>
                                <label for="physical" class="modality-label-enhanced">
                                    <div class="modality-icon-enhanced">🏢</div>
                                    <div class="modality-info-enhanced">
                                        <strong>In-Person</strong>
                                        <p>Meet at the faculty office</p>
                                    </div>
                                    <div class="radio-indicator"></div>
                                </label>
                            </div>
                            <div class="modality-option-enhanced">
                                <input type="radio" id="virtual" name="modality" value="virtual" required>
                                <label for="virtual" class="modality-label-enhanced">
                                    <div class="modality-icon-enhanced">💻</div>
                                    <div class="modality-info-enhanced">
                                        <strong>Virtual</strong>
                                        <p>Online meeting via video call</p>
                                    </div>
                                    <div class="radio-indicator"></div>
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
                
                <div class="form-section-enhanced">
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
                        <div class="char-counter">
                            <span class="char-count">0</span>/500 characters
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="preparation">How are you preparing for this consultation?</label>
                        <textarea name="preparation" id="preparation" class="form-control" rows="3" 
                                  placeholder="e.g., I have reviewed the course materials, prepared specific questions, gathered relevant documents..."></textarea>
                        <small class="form-text">Optional: Let the faculty know how you're preparing.</small>
                    </div>
                </div>
                
                <div class="form-section-enhanced">
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
                
                <div class="form-actions-enhanced">
                    <div class="booking-terms-enhanced">
                        <label class="terms-checkbox-enhanced">
                            <input type="checkbox" name="agree_terms" required>
                            <span class="checkmark-enhanced"></span>
                            <span class="terms-text">I understand that this appointment requires faculty approval and I agree to attend punctually if approved.</span>
                        </label>
                    </div>
                    
                    <div class="action-buttons-enhanced">
                        <button type="submit" class="btn btn-primary btn-lg btn-submit-enhanced" id="submitBtn">
                            <span class="btn-icon">📝</span>
                            Submit Appointment Request
                        </button>
                        <a href="<?php echo BASE_URL; ?>pages/student/faculty_schedule.php?id=<?php echo $facultyId; ?>" 
                           class="btn btn-secondary btn-cancel-enhanced">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Enhanced Booking Container */
.booking-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-bottom: 2rem;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

/* Enhanced Appointment Summary */
.appointment-summary-enhanced {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    position: sticky;
    top: 2rem;
    align-self: start;
}

.summary-card-enhanced {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.summary-header-enhanced {
    padding: 2rem;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.2);
    position: relative;
}

.summary-header-enhanced h2 {
    margin: 0;
    color: white;
    font-size: 1.4rem;
    font-weight: 600;
}

.urgency-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.urgency-badge.today {
    background-color: rgba(255,193,7,0.9);
    color: #212529;
}

.urgency-badge.tomorrow {
    background-color: rgba(40,167,69,0.9);
    color: white;
}

.summary-content-enhanced {
    padding: 2rem;
}

.faculty-info-brief {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.faculty-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    margin-right: 1rem;
    border: 2px solid rgba(255,255,255,0.3);
}

.faculty-details-brief h3 {
    margin: 0 0 0.25rem;
    color: white;
    font-size: 1.2rem;
}

.faculty-details-brief p {
    margin: 0;
    opacity: 0.8;
    font-size: 0.9rem;
}

.appointment-details-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row-enhanced {
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.1);
    padding: 1rem;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.detail-icon {
    font-size: 1.2rem;
    margin-right: 1rem;
    width: 24px;
}

.detail-content {
    flex: 1;
}

.detail-label {
    display: block;
    font-size: 0.85rem;
    opacity: 0.8;
    margin-bottom: 0.25rem;
}

.detail-value {
    display: block;
    font-size: 1.1rem;
    font-weight: 600;
    color: white;
}

/* Enhanced Booking Info */
.booking-info-enhanced {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.booking-info-enhanced h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--primary);
    font-size: 1.1rem;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.info-icon {
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 0.1rem;
}

.info-item span:last-child {
    color: var(--gray);
    line-height: 1.4;
    font-size: 0.9rem;
}

/* Enhanced Form */
.booking-form-container-enhanced {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.booking-form-enhanced {
    padding: 2rem;
}

.booking-form-enhanced h2 {
    margin-top: 0;
    margin-bottom: 2rem;
    color: var(--dark);
    text-align: center;
    font-size: 1.5rem;
}

.form-section-enhanced {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.form-section-enhanced:last-of-type {
    border-bottom: none;
}

.form-section-enhanced h3 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--primary);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Enhanced Modality Options */
.modality-options-enhanced {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.modality-option-enhanced {
    position: relative;
}

.modality-option-enhanced input[type="radio"] {
    display: none;
}

.modality-label-enhanced {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: white;
    position: relative;
}

.modality-label-enhanced:hover {
    border-color: var(--primary);
    background-color: rgba(78, 115, 223, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.modality-option-enhanced input[type="radio"]:checked + .modality-label-enhanced {
    border-color: var(--primary);
    background-color: rgba(78, 115, 223, 0.1);
    box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
}

.modality-icon-enhanced {
    font-size: 2rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.modality-info-enhanced {
    flex: 1;
}

.modality-info-enhanced strong {
    display: block;
    color: var(--dark);
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.modality-info-enhanced p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--gray);
}

.radio-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 50%;
    transition: all 0.2s;
}

.modality-option-enhanced input[type="radio"]:checked + .modality-label-enhanced .radio-indicator {
    border-color: var(--primary);
    background-color: var(--primary);
}

.modality-option-enhanced input[type="radio"]:checked + .modality-label-enhanced .radio-indicator::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background-color: white;
    border-radius: 50%;
}

/* Character Counter */
.char-counter {
    text-align: right;
    margin-top: 0.5rem;
    font-size: 0.85rem;
}

.char-count {
    font-weight: 600;
    color: var(--primary);
}

/* Enhanced Terms */
.booking-terms-enhanced {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fc;
    border-radius: 8px;
    border-left: 4px solid var(--primary);
}

.terms-checkbox-enhanced {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    cursor: pointer;
    line-height: 1.5;
}

.terms-checkbox-enhanced input[type="checkbox"] {
    display: none;
}

.checkmark-enhanced {
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
}

.terms-checkbox-enhanced input[type="checkbox"]:checked + .checkmark-enhanced {
    background-color: var(--primary);
    border-color: var(--primary);
}

.terms-checkbox-enhanced input[type="checkbox"]:checked + .checkmark-enhanced::after {
    content: '✓';
    color: white;
    font-weight: bold;
    font-size: 1rem;
}

.terms-text {
    color: var(--gray);
    font-size: 0.95rem;
}

/* Enhanced Action Buttons */
.action-buttons-enhanced {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn-submit-enhanced {
    min-width: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-weight: 600;
    position: relative;
    overflow: hidden;
}

.btn-submit-enhanced::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-submit-enhanced:hover::before {
    left: 100%;
}

.btn-cancel-enhanced {
    min-width: 120px;
}

.btn-icon {
    font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .booking-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .appointment-summary-enhanced {
        position: static;
    }
    
    .modality-options-enhanced {
        grid-template-columns: 1fr;
    }
    
    .action-buttons-enhanced {
        flex-direction: column;
    }
    
    .action-buttons-enhanced .btn {
        width: 100%;
    }
    
    .faculty-info-brief {
        flex-direction: column;
        text-align: center;
    }
    
    .faculty-avatar {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .booking-form-enhanced {
        padding: 1.5rem;
    }
    
    .summary-card-enhanced,
    .summary-content-enhanced {
        padding: 1.5rem;
    }
    
    .detail-row-enhanced {
        padding: 0.75rem;
    }
    
    .modality-label-enhanced {
        padding: 1rem;
    }
    
    .modality-icon-enhanced {
        font-size: 1.5rem;
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
    
    // Enhanced character counter for remarks
    const remarksTextarea = document.getElementById('remarks');
    const charCount = document.querySelector('.char-count');
    
    remarksTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count > 500) {
            charCount.style.color = 'var(--danger)';
            charCount.parentNode.style.color = 'var(--danger)';
        } else if (count > 400) {
            charCount.style.color = 'var(--warning)';
            charCount.parentNode.style.color = 'var(--warning)';
        } else {
            charCount.style.color = 'var(--primary)';
            charCount.parentNode.style.color = 'var(--gray)';
        }
    });
    
    // Enhanced form validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const modality = document.querySelector('input[name="modality"]:checked');
        const remarks = document.getElementById('remarks').value.trim();
        const agreeTerms = document.querySelector('input[name="agree_terms"]').checked;
        const submitBtn = document.getElementById('submitBtn');
        
        if (!modality) {
            e.preventDefault();
            alert('Please select a consultation type (In-Person or Virtual).');
            return;
        }
        
        if (!remarks) {
            e.preventDefault();
            alert('Please provide a detailed description of what you\'d like to discuss.');
            document.getElementById('remarks').focus();
            return;
        }
        
        if (remarks.length > 500) {
            e.preventDefault();
            alert('Please limit your description to 500 characters or less.');
            document.getElementById('remarks').focus();
            return;
        }
        
        if (!agreeTerms) {
            e.preventDefault();
            alert('Please agree to the booking terms.');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Submitting Request...';
        submitBtn.style.opacity = '0.7';
        
        // Re-enable after 10 seconds to prevent permanent disable if there's an error
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="btn-icon">📝</span> Submit Appointment Request';
            submitBtn.style.opacity = '1';
        }, 10000);
    });
    
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
    
    // Add smooth animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    document.querySelectorAll('.form-section-enhanced').forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(section);
    });
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>