<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Notifications';

// Include new notification system
require_once '../../includes/notification_system.php';
require_once '../../includes/appointment_functions.php';

// Get only unread notifications for this faculty member
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
        <a href="<?php echo BASE_URL; ?>pages/faculty/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<div class="notifications-page">
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔔</div>
            <div class="empty-state-text">No unread notifications</div>
            <p>You'll see notifications here when students book appointments or make changes to existing bookings.</p>
        </div>
    <?php else: ?>
        <div class="notifications-list-page" id="notificationsList">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card unread" data-notification-id="<?php echo $notification['notification_id']; ?>" data-type="<?php echo $notification['notification_type']; ?>">
                    <div class="notification-card-header">
                        <div class="notification-icon-large">
                            <?php if ($notification['notification_type'] == 'appointment_request'): ?>
                                📅
                            <?php elseif ($notification['notification_type'] == 'appointment_cancelled'): ?>
                                ⚠️
                            <?php elseif ($notification['notification_type'] == 'appointment_missed'): ?>
                                ❌
                            <?php elseif ($notification['notification_type'] == 'appointment_completed'): ?>
                                🟢
                            <?php endif; ?>
                        </div>
                        <div class="notification-meta">
                            <div class="notification-type">
                                <?php if ($notification['notification_type'] == 'appointment_request'): ?>
                                    <span class="badge badge-primary">New Appointment Request</span>
                                <?php elseif ($notification['notification_type'] == 'appointment_cancelled'): ?>
                                    <span class="badge badge-warning">Appointment Cancelled</span>
                                <?php elseif ($notification['notification_type'] == 'appointment_missed'): ?>
                                    <span class="badge badge-danger">Student Marked as Missed</span>
                                <?php endif; ?>

                                <?php
                                $createdAt = new DateTime($notification['created_at']);
                                $now = new DateTime();
                                $interval = $now->diff($createdAt);

                                if ($interval->days < 2 && $now > $createdAt): ?>
                                    <span class="badge badge-info">New</span>
                                <?php endif; ?>
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
                                    <strong>Student:</strong> <?php echo $appointment['student_first_name'] . ' ' . $appointment['student_last_name']; ?><br>
                                    <strong>Appointment Date:</strong> <?php echo formatDate($appointment['appointment_date']); ?><br>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-card-footer">
                        <div class="notification-actions">
                            <?php if ($notification['notification_type'] == 'appointment_request' && $appointment && !$appointment['is_approved'] && !$appointment['is_cancelled'] && !$appointment['is_missed']): ?>
                                <a href="<?php echo BASE_URL; ?>pages/faculty/approve_appointment.php?id=<?php echo $notification['appointment_id']; ?>"
                                class="btn btn-sm btn-success">Approve</a>
                                <a href="<?php echo BASE_URL; ?>pages/faculty/reject_appointment.php?id=<?php echo $notification['appointment_id']; ?>"
                                class="btn btn-sm btn-danger">Reject</a>
                            <?php elseif ($notification['notification_type'] == 'appointment_missed'): ?>
                                <a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php?status=missed"
                                class="btn btn-sm btn-warning">View Missed Appointments</a>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>pages/faculty/appointment_details.php?id=<?php echo $notification['appointment_id']; ?>"
                            class="btn btn-sm btn-primary">View Details</a>
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
                    <p>You'll see notifications here when students book appointments, cancel bookings, or when you mark students as missed.</p>
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
        const requestNotifications = document.querySelectorAll('.notification-card[data-type="appointment_request"]').length;
        const cancelledNotifications = document.querySelectorAll('.notification-card[data-type="appointment_cancelled"]').length;
        
        // You can add custom logic here to handle different notification types
        console.log('Missed:', missedNotifications, 'Requests:', requestNotifications, 'Cancelled:', cancelledNotifications);
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

.badge-primary {
    color: #fff;
    background-color: #4e73df;
}

.badge-warning {
    color: #212529;
    background-color: #f6c23e;
}

.badge-info {
    color: #fff;
    background-color: #36b9cc;
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

.badge-danger {
    color: #fff;
    background-color: #dc3545;
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