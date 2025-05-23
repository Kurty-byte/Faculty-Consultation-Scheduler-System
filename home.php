<?php
// Include config file
require_once 'config.php';

// If user is logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Set page title
$pageTitle = 'Home';

// Include header (we'll modify this to handle landing page styling)
$isLandingPage = true;
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="container">
            <div class="hero-text">
                <h1 class="hero-title">Faculty Consultation Scheduler System</h1>
                <p class="hero-subtitle">Streamline academic consultations with an efficient scheduling platform</p>
                <p class="hero-description">Connect students with faculty members through organized, time-managed consultation sessions. Book appointments, manage schedules, and enhance academic collaboration.</p>
                <div class="hero-actions">
                    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary btn-lg hero-btn">
                        <span class="btn-icon">🔑</span>
                        Login to Continue
                    </a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-outline-light btn-lg hero-btn">
                        <span class="btn-icon">📝</span>
                        Create Account
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-illustration">
                    <div class="illustration-element calendar">📅</div>
                    <div class="illustration-element clock">⏰</div>
                    <div class="illustration-element users">👥</div>
                    <div class="illustration-element check">✅</div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-scroll-indicator">
        <div class="scroll-arrow">↓</div>
        <span>Discover More</span>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose Our Consultation System?</h2>
            <p>Designed specifically for academic institutions to enhance student-faculty interactions</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Smart Scheduling</h3>
                <p>Faculty set their availability, students book 30-minute slots that work for everyone. Real-time availability prevents double-bookings.</p>
                <ul class="feature-list">
                    <li>✓ Flexible consultation hours</li>
                    <li>✓ Automatic conflict detection</li>
                    <li>✓ 24-hour cancellation policy</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3>Instant Notifications</h3>
                <p>Stay informed with real-time notifications for appointment requests, approvals, and updates. Never miss an important consultation.</p>
                <ul class="feature-list">
                    <li>✓ Real-time status updates</li>
                    <li>✓ Email & in-app notifications</li>
                    <li>✓ Appointment reminders</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💻</div>
                <h3>Flexible Consultation</h3>
                <p>Support for both in-person and virtual consultations. Choose the format that works best for your academic needs.</p>
                <ul class="feature-list">
                    <li>✓ In-person meetings</li>
                    <li>✓ Virtual video calls</li>
                    <li>✓ Platform integration</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Comprehensive Dashboard</h3>
                <p>Track your appointments, view upcoming schedules, and manage your consultation preferences from one central dashboard.</p>
                <ul class="feature-list">
                    <li>✓ Appointment history</li>
                    <li>✓ Schedule management</li>
                    <li>✓ Statistics & insights</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section">
    <div class="container">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Simple steps to get started with academic consultations</p>
        </div>
        
        <div class="steps-container">
            <div class="user-type-tabs">
                <button class="tab-btn active" onclick="showSteps('faculty')">For Faculty</button>
                <button class="tab-btn" onclick="showSteps('student')">For Students</button>
            </div>
            
            <div class="steps-content">
                <div id="faculty-steps" class="steps-grid active">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Create Account</h4>
                            <p>Register as a faculty member with your department information</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Set Consultation Hours</h4>
                            <p>Define your weekly availability for student consultations</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Receive Requests</h4>
                            <p>Students book appointments during your available times</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Approve & Consult</h4>
                            <p>Review requests and conduct meaningful consultations</p>
                        </div>
                    </div>
                </div>
                
                <div id="student-steps" class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Create Account</h4>
                            <p>Register as a student with your academic information</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Browse Faculty</h4>
                            <p>Find faculty members by department and view their availability</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Book Appointment</h4>
                            <p>Select a time slot and provide consultation details</p>
                        </div>
                    </div>
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Attend Consultation</h4>
                            <p>Join your approved consultation session and get academic guidance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Transform Your Academic Consultations?</h2>
            <p>Join hundreds of faculty and students who are already using our platform to enhance their academic collaboration.</p>
            <div class="cta-actions">
                <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-primary btn-lg">
                    <span class="btn-icon">🚀</span>
                    Get Started Today
                </a>
                <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline-primary btn-lg">
                    <span class="btn-icon">🔑</span>
                    Already have an account?
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Tab switching functionality
function showSteps(userType) {
    // Remove active class from all tabs and steps
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.steps-grid').forEach(grid => grid.classList.remove('active'));
    
    // Add active class to selected tab and corresponding steps
    event.target.classList.add('active');
    document.getElementById(userType + '-steps').classList.add('active');
}

// Animated counter for statistics
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    const speed = 50; // Lower is faster
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const count = +counter.innerText;
        const inc = target / speed;
        
        if (count < target) {
            counter.innerText = Math.ceil(count + inc);
            setTimeout(() => animateCounters(), 50);
        } else {
            counter.innerText = target;
        }
    });
}

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
            
            // Start counter animation when stats section is visible
            if (entry.target.classList.contains('stats-section')) {
                animateCounters();
            }
        }
    });
}, observerOptions);

// Observe all sections for animations
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('section').forEach(section => {
        observer.observe(section);
    });
    
    // Smooth scrolling for hero scroll indicator
    document.querySelector('.hero-scroll-indicator').addEventListener('click', function() {
        document.querySelector('.how-it-works-section').scrollIntoView({ 
            behavior: 'smooth' 
        });
    });
    
    // Add parallax effect to hero section
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            heroSection.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>