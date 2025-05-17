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
    $faculty = getFacultyByDepartment($departmentFilter);
    $filteredDepartment = fetchRow("SELECT department_name FROM departments WHERE department_id = ?", [$departmentFilter]);
} else {
    $faculty = getAllActiveFaculty();
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
            <a href="view_faculty.php" class="btn btn-secondary">Clear Filter</a>
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
                    <p><strong>Department:</strong> <?php echo $member['department_name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $member['office_email']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $member['office_phone_number']; ?></p>
                </div>
                <div class="faculty-actions">
                    <a href="faculty_schedule.php?id=<?php echo $member['faculty_id']; ?>" class="btn btn-primary">View Schedule</a>
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