<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/faculty_functions.php';
require_once '../../includes/timeslot_functions.php';

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

// Check if faculty has consultation hours set up
if (!hasConsultationHoursSetup($facultyId)) {
    setFlashMessage('info', 'This faculty member has not set up their consultation hours yet. Please try again later.');
    redirect('pages/student/view_faculty.php');
}

// Set page title
$pageTitle = 'Available Appointments - ' . $faculty['first_name'] . ' ' . $faculty['last_name'];

// Get date range for schedule (default to next 14 days)
$fromDate = isset($_GET['from']) ? sanitize($_GET['from']) : date('Y-m-d');
$toDate = isset($_GET['to']) ? sanitize($_GET['to']) : date('Y-m-d', strtotime('+14 days'));

// Ensure from date is not in the past
if ($fromDate < date('Y-m-d')) {
    $fromDate = date('Y-m-d');
}

// Get available consultation slots (30-minute appointments)
$availableSlots = getAvailableConsultationSlots($facultyId, $fromDate, $toDate);

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Book Consultation Appointment</h1>
    <a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" class="btn btn-secondary">Back to Faculty Directory</a>
</div>

<div class="faculty-profile">
    <div class="faculty-info">
        <h2><?php echo $faculty['first_name'] . ' ' . $faculty['last_name']; ?></h2>
        <p><strong>Department:</strong> <?php echo $faculty['department_name']; ?></p>
        <p><strong>Email:</strong> <?php echo $faculty['office_email']; ?></p>
        <p><strong>Phone:</strong> <?php echo $faculty['office_phone_number']; ?></p>
    </div>
</div>

<div class="appointment-booking">
    <div class="booking-header">
        <h3>Available 30-Minute Consultation Slots</h3>
        <p>Select a date and time that works for you. Each appointment is 30 minutes long.</p>
    </div>
    
    <div class="date-filter">
        <form action="" method="GET" class="date-range-form">
            <input type="hidden" name="id" value="<?php echo $facultyId; ?>">
            <div class="date-inputs">
                <div class="date-group">
                    <label for="from">From Date:</label>
                    <input type="date" name="from" id="from" class="form-control" 
                           value="<?php echo $fromDate; ?>" 
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>
                <div class="date-group">
                    <label for="to">To Date:</label>
                    <input type="date" name="to" id="to" class="form-control" 
                           value="<?php echo $toDate; ?>" 
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update View</button>
            </div>
        </form>
    </div>
    
    <?php if (empty($availableSlots)): ?>
        <div class="no-slots-available">
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>No Available Slots</h3>
                <p>No consultation slots are available in the selected date range.</p>
                <div class="suggestions">
                    <h4>Try:</h4>
                    <ul>
                        <li>Extending the date range</li>
                        <li>Checking back later - faculty may add more hours</li>
                        <li>Contacting the faculty member directly</li>
                    </ul>
                </div>
                <a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" class="btn btn-secondary">
                    Browse Other Faculty
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="available-slots-container">
            <?php foreach ($availableSlots as $dateSlots): ?>
                <div class="date-section">
                    <div class="date-header">
                        <h3><?php echo $dateSlots['day_name'] . ', ' . $dateSlots['formatted_date']; ?></h3>
                        <div class="slots-count">
                            <?php echo count($dateSlots['slots']); ?> slot<?php echo count($dateSlots['slots']) > 1 ? 's' : ''; ?> available
                        </div>
                    </div>
                    
                    <div class="time-slots-grid">
                        <?php foreach ($dateSlots['slots'] as $slot): ?>
                            <div class="time-slot-card">
                                <div class="slot-time">
                                    <?php echo $slot['formatted_time']; ?>
                                </div>
                                <div class="slot-duration">
                                    30 minutes
                                </div>
                                <div class="slot-actions">
                                    <a href="<?php echo BASE_URL; ?>pages/student/book_appointment.php?faculty_id=<?php echo $facultyId; ?>&date=<?php echo $dateSlots['date']; ?>&start=<?php echo $slot['start_time']; ?>&end=<?php echo $slot['end_time']; ?>" 
                                       class="btn btn-primary btn-book">
                                        Book This Slot
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="booking-info">
    <div class="info-card">
        <h4>📋 Booking Information</h4>
        <ul>
            <li><strong>Duration:</strong> Each appointment is exactly 30 minutes</li>
            <li><strong>Confirmation:</strong> Your appointment needs faculty approval</li>
            <li><strong>Cancellation:</strong> Cancel at least 24 hours in advance</li>
            <li><strong>Modality:</strong> Choose between in-person or virtual consultation</li>
        </ul>
    </div>
</div>

<style>
.faculty-profile {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.faculty-profile h2 {
    color: white;
    margin-bottom: 1rem;
}

.faculty-profile p {
    margin-bottom: 0.5rem;
    opacity: 0.9;
}

.appointment-booking {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
}

.booking-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.booking-header h3 {
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.date-filter {
    background-color: #f8f9fc;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.date-range-form {
    display: flex;
    justify-content: center;
}

.date-inputs {
    display: flex;
    align-items: end;
    gap: 1rem;
    flex-wrap: wrap;
}

.date-group {
    display: flex;
    flex-direction: column;
}

.date-group label {
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--dark);
}

.no-slots-available {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-state {
    max-width: 500px;
    margin: 0 auto;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: var(--dark);
    margin-bottom: 1rem;
}

.suggestions {
    background-color: #f8f9fc;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 2rem 0;
    text-align: left;
}

.suggestions h4 {
    margin-bottom: 1rem;
    color: var(--primary);
}

.suggestions ul {
    margin-bottom: 0;
}

.suggestions li {
    margin-bottom: 0.5rem;
}

.available-slots-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.date-section {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.date-header {
    background-color: var(--primary);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.date-header h3 {
    margin: 0;
    color: white;
    font-size: 1.1rem;
}

.slots-count {
    font-size: 0.9rem;
    opacity: 0.9;
    background-color: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
}

.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
}

.time-slot-card {
    background-color: #f8f9fc;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.time-slot-card:hover {
    border-color: var(--primary);
    background-color: rgba(78, 115, 223, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.slot-time {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.slot-duration {
    font-size: 0.85rem;
    color: var(--gray);
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.btn-book {
    width: 100%;
    font-weight: 500;
}

.booking-info {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.info-card h4 {
    color: var(--dark);
    margin-bottom: 1rem;
}

.info-card ul {
    margin: 0;
    padding-left: 1.5rem;
}

.info-card li {
    margin-bottom: 0.5rem;
    color: var(--gray);
}

.info-card strong {
    color: var(--dark);
}

@media (max-width: 768px) {
    .faculty-profile {
        padding: 1.5rem;
    }
    
    .appointment-booking {
        padding: 1rem;
    }
    
    .date-inputs {
        flex-direction: column;
        align-items: stretch;
    }
    
    .date-inputs .btn {
        margin-top: 1rem;
    }
    
    .date-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .time-slots-grid {
        grid-template-columns: 1fr;
        padding: 1rem;
    }
    
    .suggestions {
        text-align: center;
    }
}

@media (max-width: 576px) {
    .time-slot-card {
        padding: 1rem;
    }
    
    .slot-time {
        font-size: 1rem;
    }
    
    .booking-info {
        padding: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validate date range
    const fromDate = document.getElementById('from');
    const toDate = document.getElementById('to');
    
    fromDate.addEventListener('change', function() {
        if (toDate.value && this.value > toDate.value) {
            toDate.value = this.value;
        }
        
        // Limit to 30 days from selected start date
        const maxDate = new Date(this.value);
        maxDate.setDate(maxDate.getDate() + 30);
        toDate.max = maxDate.toISOString().split('T')[0];
    });
    
    toDate.addEventListener('change', function() {
        if (fromDate.value && this.value < fromDate.value) {
            fromDate.value = this.value;
        }
    });
    
    // Set minimum date to today for both inputs
    const today = new Date().toISOString().split('T')[0];
    fromDate.min = today;
    toDate.min = today;
    
    // Add hover effects to time slot cards
    const timeSlots = document.querySelectorAll('.time-slot-card');
    timeSlots.forEach(slot => {
        slot.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        slot.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>