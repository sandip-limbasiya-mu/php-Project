<?php
/**
 * login.php - User Login Page
  
 * This page allows users (Admin / Patient) to log in.
 * It validates email and password against the database and creates a session.
 */

require_once 'config.php';

// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize error and success message variables
$error = '';
$success = '';

// Handle form submission (when login button is clicked)
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data and trim whitespace
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // ===== FORM VALIDATION =====
    if(empty($email)) {
        $error = "Email is required.";
    } elseif(empty($password)) {
        $error = "Password is required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        
        try {
            // Prepare SQL query to find user by email
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                // User found - now verify password
                // password_verify() checks plain password against the hashed one in DB
                if(password_verify($password, $user['password'])) {
                    
                    // Password is correct - create session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    $success = "Login successful! Redirecting to dashboard...";
                    
                    // Redirect to dashboard after 2 seconds
                    header("refresh:1; url=dashboard.php");
                    
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No account found with this email.";
            }
            
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">
                    <i class="bi bi-box-arrow-in-right me-2"></i>User Login
                </h4>
            </div>
            <div class="card-body p-4">
                
                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>Email Address <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i>Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center mb-0">
                    Don't have an account? 
                    <a href="register.php" class="text-decoration-none fw-bold">Register Here</a>
                </p>
                
            </div>
        </div>
        
        <!-- Demo credentials card -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Demo Credentials</h6>
            </div>
            <div class="card-body small">
                <p class="mb-2"><strong>Admin Login:</strong><br>
                Email: admin@hospital.com<br>
                Password: admin123</p>
                <p class="mb-0"><strong>Patient Login:</strong><br>
                Email: rajesh@email.com<br>
                Password: patient123</p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
