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
    setFlashMessage('danger', 'Appointment not found or you do not have permission to view it.');
    redirect('pages/faculty/view_appointments.php');
}

// Get appointment history
$history = getAppointmentHistory($appointmentId);

// Set page title
$pageTitle = 'Appointment Details';

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Appointment Details</h1>
    <a href="view_appointments.php" class="btn btn-secondary">Back to Appointments</a>
</div>

<div class="appointment-detail-card">
    <div class="card-section">
        <h2>Appointment Information</h2>
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
                <th>Time:</th>
                <td><?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?></td>
            </tr>
            <tr>
                <th>Day:</th>
                <td><?php echo ucfirst($appointment['day_of_week']); ?></td>
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
                <th>Status:</th>
                <td>
                    <?php if ($appointment['is_cancelled']): ?>
                        <span class="badge badge-danger">Cancelled</span>
                    <?php elseif ($appointment['is_approved']): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Reason for Consultation:</th>
                <td><?php echo nl2br(htmlspecialchars($appointment['remarks'])); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="card-section">
        <h2>Appointment Timeline</h2>
        <div class="timeline">
            <?php foreach ($history as $event): ?>
                <div class="timeline-item">
                    <div class="timeline-badge 
                        <?php 
                        switch($event['status_change']) {
                            case 'created': echo 'badge-info'; break;
                            case 'approved': echo 'badge-success'; break;
                            case 'rejected': echo 'badge-danger'; break;
                            case 'cancelled': echo 'badge-warning'; break;
                            default: echo 'badge-secondary';
                        }
                        ?>">
                        <i class="icon-<?php echo $event['status_change']; ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>
                            <?php 
                            switch($event['status_change']) {
                                case 'created': echo 'Appointment Created'; break;
                                case 'approved': echo 'Appointment Approved'; break;
                                case 'rejected': echo 'Appointment Rejected'; break;
                                case 'cancelled': echo 'Appointment Cancelled'; break;
                                default: echo ucfirst($event['status_change']);
                            }
                            ?>
                        </h4>
                        <p>
                            <small><?php echo date('F j, Y g:i A', strtotime($event['changed_at'])); ?></small><br>
                            <?php if ($event['notes']): ?>
                                <em><?php echo htmlspecialchars($event['notes']); ?></em><br>
                            <?php endif; ?>
                            By: <?php echo $event['first_name'] . ' ' . $event['last_name']; ?> (<?php echo ucfirst($event['role']); ?>)
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if (!$appointment['is_approved'] && !$appointment['is_cancelled']): ?>
    <div class="card-actions">
        <a href="approve_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-success">Approve Appointment</a>
        <a href="reject_appointment.php?id=<?php echo $appointmentId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this appointment?')">Reject Appointment</a>
    </div>
    <?php endif; ?>
</div>

<style>
.appointment-detail-card {
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    padding: 20px;
}

.card-section {
    margin-bottom: 25px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.card-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 0;
}

.detail-table {
    width: 100%;
}

.detail-table th {
    width: 30%;
    text-align: right;
    padding: 8px 15px 8px 0;
    vertical-align: top;
    color: #666;
}

.detail-table td {
    padding: 8px 0;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-badge {
    position: absolute;
    left: -30px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    text-align: center;
    color: white;
    margin-top: 3px;
}

.timeline-content {
    padding-bottom: 10px;
}

.timeline-content h4 {
    margin-top: 0;
    font-size: 18px;
}

.badge-info {
    background-color: #3498db;
}

.badge-success {
    background-color: #2ecc71;
}

.badge-danger {
    background-color: #e74c3c;
}

.badge-warning {
    background-color: #f39c12;
}

.badge-secondary {
    background-color: #95a5a6;
}

.card-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.card-actions .btn {
    margin-left: 10px;
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>