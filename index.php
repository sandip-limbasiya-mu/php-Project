<?php
/**
 * index.php - Landing / Home Page
 * 
 * This is the first page visitors see. It shows welcome message, 
 * features, and links to register/login.
 */

// Include configuration file (DB connection + session)
require_once 'config.php';

// Include header (navigation bar + HTML head)
include 'header.php';
?>

<!-- ===== Hero / Banner Section ===== -->
<div class="hero-section text-center">
    <h1 class="mb-4">Welcome to MedCare Hospital</h1>
    <p class="mb-5">Your Health, Our Priority. Book appointments with top doctors online in just a few clicks.</p>
    <div>
        <?php 
        // Show different buttons based on login status
        if(isset($_SESSION['user_id'])): 
        ?>
            <a href="appointment.php" class="btn btn-light btn-lg me-3">
                <i class="bi bi-calendar-plus me-2"></i>Book Appointment
            </a>
            <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
            </a>
        <?php else: ?>
            <a href="register.php" class="btn btn-light btn-lg me-3">
                <i class="bi bi-person-plus me-2"></i>Register Now
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>User Login
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ===== Features Section ===== -->
<h2 class="text-center mb-4">Our Services</h2>
<div class="row g-4 mb-5">
    <!-- Feature 1 -->
    <div class="col-md-4">
        <div class="card feature-card">
            <i class="bi bi-calendar-check-fill"></i>
            <h5>Easy Appointment Booking</h5>
            <p class="text-muted">Book appointments with your preferred doctors online at your convenience.</p>
        </div>
    </div>
    
    <!-- Feature 2 -->
    <div class="col-md-4">
        <div class="card feature-card">
            <i class="bi bi-people-fill"></i>
            <h5>Expert Doctors</h5>
            <p class="text-muted">Access a panel of highly qualified and experienced medical specialists.</p>
        </div>
    </div>
    
    <!-- Feature 3 -->
    <div class="col-md-4">
        <div class="card feature-card">
            <i class="bi bi-shield-check"></i>
            <h5>Secure Platform</h5>
            <p class="text-muted">Your health data and personal information are safe and confidential with us.</p>
        </div>
    </div>
</div>

<!-- ===== Our Doctors Preview Section ===== -->
<h2 class="text-center mb-4">Our Doctors</h2>
<div class="row g-4 mb-5">
    <?php
    // Fetch all doctors from database
    try {
        $stmt = $conn->prepare("SELECT * FROM doctors LIMIT 4");
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($doctors as $doctor):
            // Get first letter of doctor name for avatar
            $firstLetter = strtoupper($doctor['name'][0]);
    ?>
        <div class="col-md-3">
            <div class="card doctor-card">
                <div class="card-body text-center">
                    <div class="doctor-avatar mx-auto"><?php echo $firstLetter; ?></div>
                    <h5 class="card-title"><?php echo $doctor['name']; ?></h5>
                    <p class="text-primary mb-2"><?php echo $doctor['specialization']; ?></p>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-clock me-1"></i><?php echo $doctor['availability']; ?>
                    </p>
                    <p class="fw-bold mb-0">Fee: ₹<?php echo number_format($doctor['fee'], 2); ?></p>
                </div>
            </div>
        </div>
    <?php
        endforeach;
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    ?>
</div>

<!-- ===== How It Works Section ===== -->
<h2 class="text-center mb-4">How It Works</h2>
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card text-center p-4">
            <h1 class="text-primary mb-2">1</h1>
            <h5>Register</h5>
            <p class="text-muted mb-0">Create a free account in just 2 minutes.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-4">
            <h1 class="text-primary mb-2">2</h1>
            <h5>Choose Doctor</h5>
            <p class="text-muted mb-0">Select a doctor based on specialization.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-4">
            <h1 class="text-primary mb-2">3</h1>
            <h5>Book Slot</h5>
            <p class="text-muted mb-0">Pick a convenient date and time.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-4">
            <h1 class="text-primary mb-2">4</h1>
            <h5>Get Treatment</h5>
            <p class="text-muted mb-0">Visit the hospital at your scheduled time.</p>
        </div>
    </div>
</div>

<?php
// Include footer (copyright + Bootstrap JS)
include 'footer.php';
?>
