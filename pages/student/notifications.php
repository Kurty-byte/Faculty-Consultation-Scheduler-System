<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$pageTitle = 'Notifications';

// Include new notification system
require_once '../../includes/notification_system.php';
require_once '../../includes/appointment_functions.php';

// Get only unread notifications for this student
$notifications = getUnreadNotifications($_SESSION['user_id']);

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Notifications</h1>
    <div class="page-actions">
        <?php if (!empty($notifications)): ?>
            <button id="markAllReadBtn" class="btn btn-sm btn-secondary">Mark All as Read</button>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>pages/student/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<div class="notifications-page">
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔔</div>
            <div class="empty-state-text">No unread notifications</div>
            <p>You'll see notifications here when faculty members respond to your appointment requests.</p>
        </div>
    <?php else: ?>
        <div class="notifications-list-page" id="notificationsList">
            <?php foreach ($notifications as $notification): ?>
            <div class="notification-card unread" data-notification-id="<?php echo $notification['notification_id']; ?>" data-type="<?php echo $notification['notification_type']; ?>">
                    <div class="notification-card-header">
                        <div class="notification-icon-large">
                            <?php if ($notification['notification_type'] == 'appointment_approved'): ?>
                                ✅
                            <?php elseif ($notification['notification_type'] == 'appointment_rejected'): ?>
                                ❌
                            <?php elseif ($notification['notification_type'] == 'appointment_missed'): ?>
                                ⚠️
                            <?php elseif ($notification['notification_type'] == 'appointment_completed'): ?>
                                🟢
                            <?php endif; ?>
                        </div>
                        <div class="notification-meta">
                            <div class="notification-type">
                                <?php if ($notification['notification_type'] == 'appointment_approved'): ?>
                                    <span class="badge badge-success">Appointment Approved</span>
                                <?php elseif ($notification['notification_type'] == 'appointment_rejected'): ?>
                                    <span class="badge badge-danger">Appointment Rejected</span>
                                <?php elseif ($notification['notification_type'] == 'appointment_missed'): ?>
                                    <span class="badge badge-warning">Faculty Marked as Missed</span>
                                <?php elseif ($notification['notification_type'] == 'appointment_completed'): ?>
                                    <span class="badge badge-success">Appointment Completed</span>
                                <?php endif; ?>
                                <span class="badge badge-info">New</span>
                            </div>
                            <div class="notification-time">
                                <span class="time-ago" data-timestamp="<?php echo $notification['timestamp']; ?>">
                                    <?php echo $notification['time_ago']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="notification-card-body">
                        <h3 class="notification-title"><?php echo displayTextContent($notification['message']); ?></h3>
                        
                        <!-- Get additional appointment details -->
                        <?php 
                        $appointment = getAppointmentDetails($notification['appointment_id']);
                        if ($appointment): 
                        ?>
                            <div class="appointment-preview">
                                <div class="appointment-info">
                                    <strong>Faculty:</strong> <?php echo $appointment['faculty_first_name'] . ' ' . $appointment['faculty_last_name']; ?><br>
                                    <strong>Date:</strong> <?php echo formatDate($appointment['appointment_date']); ?><br>
                                    <strong>Time:</strong> <?php echo formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']); ?><br>
                                    <strong>Modality:</strong> <?php echo ucfirst($appointment['modality']); ?>
                                    <?php if ($appointment['modality'] === 'virtual' && $appointment['platform']): ?>
                                        (<?php echo $appointment['platform']; ?>)
                                    <?php elseif ($appointment['modality'] === 'physical' && $appointment['location']): ?>
                                        (<?php echo $appointment['location']; ?>)
                                    <?php endif; ?>
                                    
                                    <?php if ($notification['notification_type'] == 'appointment_rejected'): ?>
                                        <?php
                                        // Get rejection reason from appointment history
                                        $rejectionReason = fetchRow(
                                            "SELECT notes FROM appointment_history 
                                             WHERE appointment_id = ? AND status_change = 'rejected' 
                                             ORDER BY changed_at DESC LIMIT 1",
                                            [$notification['appointment_id']]
                                        );
                                        if ($rejectionReason && !empty($rejectionReason['notes'])):
                                        ?>
                                            <br><strong>Reason:</strong> <?php echo htmlspecialchars($rejectionReason['notes']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-card-footer">
                        <div class="notification-actions">
                            <a href="<?php echo BASE_URL; ?>pages/student/appointment_details.php?id=<?php echo $notification['appointment_id']; ?>"
                            class="btn btn-sm btn-primary">View Details</a>
                            <?php if ($notification['notification_type'] == 'appointment_approved'): ?>
                                <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php"
                                class="btn btn-sm btn-success">View All Appointments</a>
                            <?php elseif ($notification['notification_type'] == 'appointment_rejected'): ?>
                                <a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php"
                                class="btn btn-sm btn-warning">Book New Appointment</a>
                            <?php elseif ($notification['notification_type'] == 'appointment_missed'): ?>
                                <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=missed"
                                class="btn btn-sm btn-warning">View Missed Appointments</a>
                            <?php elseif ($notification['notification_type'] == 'appointment_completed'): ?>
                                <a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php?status=completed"
                                class="btn btn-sm btn-success">View Completed Appointments</a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-secondary mark-read-btn" 
                                    data-notification-id="<?php echo $notification['notification_id']; ?>">
                                Mark as Read
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark individual notification as read
    document.querySelectorAll('.mark-read-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            markNotificationAsRead(notificationId, this);
        });
    });

    document.querySelectorAll('.notification-card[data-type="appointment_missed"]').forEach(function(card) {
        card.style.borderLeftWidth = '5px';
        card.style.borderLeftColor = '#ffc107';
    });
    
    // Mark all notifications as read
    const markAllBtn = document.getElementById('markAllReadBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
    }
    
    function markNotificationAsRead(notificationId, buttonElement) {
        fetch('../../ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the notification card from display
                const card = buttonElement.closest('.notification-card');
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    checkIfEmpty();
                    updateNotificationBadge(data.unread_count);
                }, 300);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking the notification as read.');
        });
    }
    
    function markAllNotificationsAsRead() {
        fetch('../../ajax/mark_all_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove all notification cards
                const cards = document.querySelectorAll('.notification-card');
                cards.forEach(card => {
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                });
                
                // Hide mark all button
                const markAllBtn = document.getElementById('markAllReadBtn');
                if (markAllBtn) {
                    markAllBtn.style.display = 'none';
                }
                
                setTimeout(() => {
                    checkIfEmpty();
                    updateNotificationBadge(0);
                }, 500);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking notifications as read.');
        });
    }
    
    function checkIfEmpty() {
        const notificationsList = document.getElementById('notificationsList');
        if (notificationsList && notificationsList.children.length === 0) {
            // Show empty state
            const emptyState = `
                <div class="empty-state">
                    <div class="empty-state-icon">🔔</div>
                    <div class="empty-state-text">No unread notifications</div>
                    <p>You'll see notifications here when faculty members respond to your appointment requests or when you mark faculty as missed.</p>
                </div>
            `;
            notificationsList.outerHTML = emptyState;
        }
    }
    
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (count > 0) {
            if (badge) {
                badge.textContent = count;
            }
        } else {
            if (badge) {
                badge.remove();
            }
        }
    }

    function updateNotificationDisplay() {
        const missedNotifications = document.querySelectorAll('.notification-card[data-type="appointment_missed"]').length;
        const approvedNotifications = document.querySelectorAll('.notification-card[data-type="appointment_approved"]').length;
        const rejectedNotifications = document.querySelectorAll('.notification-card[data-type="appointment_rejected"]').length;
        const completedNotifications = document.querySelectorAll('.notification-card[data-type="appointment_completed"]').length;
        
        // You can add custom logic here to handle different notification types
        console.log('Missed:', missedNotifications, 'Approved:', approvedNotifications, 'Rejected:', rejectedNotifications, 'Completed:', completedNotifications);
    }
    
    updateNotificationDisplay();
});
</script>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.notifications-page {
    max-width: 800px;
    margin: 0 auto;
}

.notification-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    padding: 20px;
    transition: all 0.3s ease;
    border-left: 4px solid #4e73df;
    background-color: rgba(78, 115, 223, 0.02);
}

.notification-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.notification-card-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.notification-icon-large {
    font-size: 24px;
    margin-right: 15px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fc;
    border-radius: 50%;
}

.notification-meta {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-type {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.notification-time {
    text-align: right;
    font-size: 1em;
    color: #495057;
    font-weight: 600;
}

.notification-title {
    margin: 0 0 15px;
    font-size: 1.2em;
    color: #2c3e50;
}

.appointment-preview {
    background-color: #f8f9fc;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.appointment-info {
    font-size: 0.95em;
    line-height: 1.6;
}

.notification-card-footer {
    border-top: 1px solid #e9ecef;
    padding-top: 15px;
    margin-top: 15px;
}

.notification-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #6c757d;
}

.empty-state-icon {
    font-size: 4em;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state-text {
    font-size: 1.3em;
    margin-bottom: 10px;
    color: #495057;
}

.badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
}

.badge-success {
    color: #fff;
    background-color: #1cc88a;
}

.badge-danger {
    color: #fff;
    background-color: #e74a3b;
}

.badge-info {
    color: #fff;
    background-color: #36b9cc;
}

.badge-warning {
    color: #212529;
    background-color: #f6c23e;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-actions {
        margin-top: 10px;
        width: 100%;
    }
    
    .notification-meta {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notification-time {
        text-align: left;
        margin-top: 8px;
    }
    
    .notification-actions {
        flex-direction: column;
    }
    
    .notification-actions .btn {
        width: 100%;
        margin-bottom: 5px;
    }
}

.badge-warning {
    color: #212529;
    background-color: #ffc107;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #212529;
}

/* Special styling for missed appointment notifications */
.notification-card[data-type="appointment_missed"] {
    border-left-color: var(--warning);
    background-color: rgba(255, 193, 7, 0.02);
}

.notification-card[data-type="appointment_missed"] .notification-icon-large {
    background-color: rgba(255, 193, 7, 0.1);
    color: var(--warning);
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>