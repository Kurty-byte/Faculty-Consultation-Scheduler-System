<?php
// Include config file
require_once 'config.php';

// Check if already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Set page title
$pageTitle = 'Login';

// Include header
include 'includes/header.php';
?>

<div class="login-container">
    <h1>Welcome to <?php echo SITE_NAME; ?></h1>
    
    <div class="login-box">
        <h2>Login</h2>
        
        <form action="<?php echo BASE_URL; ?>pages/auth/login_process.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>

            <div class="form-group">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>