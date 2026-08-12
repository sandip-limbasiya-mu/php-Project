<?php
/**
 * register.php - Patient Registration Page
 * 
 * This page allows new patients to create an account.
 * It validates the form, checks for duplicates, hashes the password, and saves to database.
 */

require_once 'config.php';

// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// When form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get and trim all form inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $age = (int)$_POST['age'];
    $address = trim($_POST['address']);
    
    // ===== FORM VALIDATION =====
    // 1. Check all required fields
    if(empty($name)) {
        $error = "Full name is required.";
    } elseif(empty($email)) {
        $error = "Email address is required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif(empty($password)) {
        $error = "Password is required.";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif($password != $confirm_password) {
        $error = "Password and Confirm Password do not match.";
    } elseif(empty($phone)) {
        $error = "Phone number is required.";
    } elseif(!ctype_digit($phone) || strlen($phone) != 10) {
        $error = "Phone number must be exactly 10 digits.";
    } elseif(empty($gender)) {
        $error = "Please select your gender.";
    } elseif(empty($age) || $age < 1 || $age > 150) {
        $error = "Please enter a valid age between 1 and 150.";
    } elseif(empty($address)) {
        $error = "Address is required.";
    } else {
        
        // ===== VALIDATION PASSED =====
        try {
            // 1. First check if email already exists in database
            $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkEmail->execute([$email]);
            
            if($checkEmail->rowCount() > 0) {
                $error = "This email is already registered. Please try another or login.";
            } else {
                
                // 2. Hash the password securely - NEVER store plain passwords
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // 3. Insert new patient into users table
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, gender, age, address, role) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, 'patient')");
                
                // Execute with array of values in same order as placeholders
                $result = $stmt->execute([$name, $email, $hashedPassword, $phone, $gender, $age, $address]);
                
                if($result) {
                    $success = "Registration successful! You can now login. Redirecting to login page...";
                    // Redirect to login page after 2 seconds
                    header("refresh:2; url=login.php");
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
            
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">
                    <i class="bi bi-person-plus me-2"></i>Patient Registration
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
                    <div class="row">
                        
                        <!-- Full Name -->
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">
                                <i class="bi bi-person me-1"></i>Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   placeholder="Enter your full name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="example@email.com" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-1"></i>Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Minimum 6 characters" required>
                            <div class="form-text">Use at least 6 characters.</div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Retype your password" required>
                        </div>
                        
                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">
                                <i class="bi bi-telephone me-1"></i>Phone Number <span class="text-danger">*</span>
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="10-digit mobile number" 
                                   pattern="[0-9]{10}"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <!-- Gender -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-gender-ambiguous me-1"></i>Gender <span class="text-danger">*</span>
                            </label>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="Male" 
                                           <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="Female" 
                                           <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="other" value="Other" 
                                           <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="other">Other</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Age -->
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">
                                <i class="bi bi-calendar me-1"></i>Age <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="age" name="age" min="1" max="150" 
                                   placeholder="Enter your age" 
                                   value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <!-- Address -->
                        <div class="col-md-12 mb-4">
                            <label for="address" class="form-label">
                                <i class="bi bi-house me-1"></i>Full Address <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="House no., Street, City, State" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center mb-0">
                    Already have an account? 
                    <a href="login.php" class="text-decoration-none fw-bold">Login Here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
