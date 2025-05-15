<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Set page title
$pageTitle = 'Manage Schedules';

// Get faculty ID
$facultyId = getFacultyIdFromUserId($_SESSION['user_id']);

// Get all schedules for this faculty
$schedules = fetchRows(
    "SELECT * FROM availability_schedules 
     WHERE faculty_id = ? 
     ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), 
     start_time",
    [$facultyId]
);

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Consultation Schedules</h1>
    <a href="add_schedule.php" class="btn btn-primary">Add New Schedule</a>
</div>

<?php if (empty($schedules)): ?>
    <div class="alert alert-info">
        You haven't set up any consultation schedules yet. Click "Add New Schedule" to create your first consultation slot.
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Day</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Recurring</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedules as $schedule): ?>
                <tr class="<?php echo $schedule['is_active'] ? '' : 'inactive-row'; ?>">
                    <td><?php echo ucfirst($schedule['day_of_week']); ?></td>
                    <td><?php echo formatTime($schedule['start_time']); ?></td>
                    <td><?php echo formatTime($schedule['end_time']); ?></td>
                    <td>
                        <?php if ($schedule['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($schedule['is_recurring']): ?>
                            <span class="badge badge-info">Weekly</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">One-time</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this schedule?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<style>
.inactive-row {
    background-color: #f9f9f9;
    color: #999;
}

.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
}

.badge-success {
    background-color: #2ecc71;
    color: white;
}

.badge-danger {
    background-color: #e74c3c;
    color: white;
}

.badge-info {
    background-color: #3498db;
    color: white;
}

.badge-secondary {
    background-color: #95a5a6;
    color: white;
}
</style>

<?php
// Include footer
include '../../includes/footer.php';
?>