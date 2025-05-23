<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Set page title
$pageTitle = 'View Faculty';

// Include required functions
require_once '../../includes/faculty_functions.php';

// Get all departments with faculty
$departments = getDepartmentsWithFaculty();

// Get all faculty or filter by department
$departmentFilter = isset($_GET['department']) ? (int)$_GET['department'] : null;

if ($departmentFilter) {
    // Fixed query to ensure department_name is included
    $faculty = fetchRows(
        "SELECT f.faculty_id, u.first_name, u.last_name, d.department_name, f.office_email, f.office_phone_number 
         FROM faculty f 
         JOIN users u ON f.user_id = u.user_id 
         JOIN departments d ON f.department_id = d.department_id 
         WHERE f.department_id = ? AND u.is_active = 1 AND f.status = 'active' 
         ORDER BY u.last_name, u.first_name",
        [$departmentFilter]
    );
    $filteredDepartment = fetchRow("SELECT department_name FROM departments WHERE department_id = ?", [$departmentFilter]);
} else {
    // Fixed query to ensure department_name is included
    $faculty = fetchRows(
        "SELECT f.faculty_id, u.first_name, u.last_name, d.department_name, f.office_email, f.office_phone_number 
         FROM faculty f 
         JOIN users u ON f.user_id = u.user_id 
         JOIN departments d ON f.department_id = d.department_id 
         WHERE u.is_active = 1 AND f.status = 'active' 
         ORDER BY u.last_name, u.first_name"
    );
}

// Include header
include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Faculty Directory</h1>
</div>

<div class="filter-section">
    <form action="" method="GET" class="form-inline">
        <div class="form-group">
            <label for="department">Filter by Department:</label>
            <select name="department" id="department" class="form-control">
                <option value="">All Departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo $department['department_id']; ?>" <?php echo ($departmentFilter == $department['department_id']) ? 'selected' : ''; ?>>
                        <?php echo $department['department_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($departmentFilter): ?>
            <a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" class="btn btn-secondary">Clear Filter</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($departmentFilter && $filteredDepartment): ?>
    <h2>Faculty in <?php echo $filteredDepartment['department_name']; ?> Department</h2>
<?php endif; ?>

<?php if (empty($faculty)): ?>
    <div class="alert alert-info">
        No faculty members found<?php echo $departmentFilter ? ' in this department' : ''; ?>.
    </div>
<?php else: ?>
    <div class="faculty-list">
        <?php foreach ($faculty as $member): ?>
            <div class="faculty-card">
                <div class="faculty-info">
                    <h3><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></h3>
                    <p><strong>Department:</strong> <?php echo isset($member['department_name']) ? $member['department_name'] : 'N/A'; ?></p>
                    <p><strong>Email:</strong> <?php echo isset($member['office_email']) ? $member['office_email'] : 'N/A'; ?></p>
                    <p><strong>Phone:</strong> <?php echo isset($member['office_phone_number']) ? $member['office_phone_number'] : 'N/A'; ?></p>
                </div>
                <div class="faculty-actions">
                    <a href="<?php echo BASE_URL; ?>pages/student/faculty_schedule.php?id=<?php echo $member['faculty_id']; ?>" class="btn btn-primary">View Schedule</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when department changes
    document.getElementById('department').addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>