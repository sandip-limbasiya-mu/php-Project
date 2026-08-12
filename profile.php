<?php
/**
 * profile.php - User Profile Page
 * 
 * Users (Admin or Patient) can view and update their personal profile here.
 * Also includes optional password change.
 */

require_once 'config.php';

// ---- SECURITY: Must be logged in ----
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// ===== FETCH CURRENT USER DATA =====
$user = null;
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading profile: " . $e->getMessage();
}

// ===== HANDLE PROFILE UPDATE =====
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $age = (int)$_POST['age'];
    $address = trim($_POST['address']);
    
    // Validate
    if(empty($name)) {
        $error = "Name is required.";
    } elseif(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Valid email is required.";
    } elseif(empty($phone)) {
        $error = "Phone number is required.";
    } elseif(empty($gender)) {
        $error = "Gender is required.";
    } elseif(empty($age) || $age < 1 || $age > 150) {
        $error = "Valid age is required.";
    } elseif(empty($address)) {
        $error = "Address is required.";
    } else {
        
        // Check if email already used by someone ELSE
        try {
            $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $checkEmail->execute([$email, $user_id]);
            if($checkEmail->rowCount() > 0) {
                $error = "This email is already used by another account.";
            } else {
                // Update profile
                $stmt = $conn->prepare("UPDATE users 
                                       SET name = ?, email = ?, phone = ?, gender = ?, age = ?, address = ? 
                                       WHERE id = ?");
                $result = $stmt->execute([$name, $email, $phone, $gender, $age, $address, $user_id]);
                
                if($result) {
                    // Update session variables too
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    $success = "Profile updated successfully!";
                    
                    // Refresh user data from DB
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Update failed. Please try again.";
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ===== HANDLE PASSWORD CHANGE =====
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password)) {
        $error = "Please enter your current password.";
    } elseif(empty($new_password)) {
        $error = "Please enter a new password.";
    } elseif(strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif($new_password != $confirm_password) {
        $error = "New password and confirmation do not match.";
    } else {
        
        try {
            // Verify current password first
            if(password_verify($current_password, $user['password'])) {
                
                // Hash and update new password
                $hashedNew = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $result = $stmt->execute([$hashedNew, $user_id]);
                
                if($result) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Password change failed.";
                }
                
            } else {
                $error = "Current password is incorrect.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get user's first letter for avatar
$avatarLetter = $user ? strtoupper($user['name'][0]) : 'U';

include 'header.php';
?>

<!-- Messages -->
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

<!-- Page Header -->
<h2 class="mb-4">
    <i class="bi bi-person-circle text-primary me-2"></i>My Profile
</h2>

<div class="row g-4">
    
    <!-- ===== PROFILE INFO CARD ===== -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-person-lines-fill me-2"></i>Personal Information
                </h5>
                <span class="badge bg-light text-primary">
                    <i class="bi bi-shield-check me-1"></i><?php echo ucfirst($user['role']); ?>
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   pattern="[0-9]{10}"
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <!-- Gender -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="Male" 
                                           <?php echo ($user['gender'] == 'Male') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="Female" 
                                           <?php echo ($user['gender'] == 'Female') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="other" value="Other" 
                                           <?php echo ($user['gender'] == 'Other') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="other">Other</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Age -->
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="age" name="age" min="1" max="150" 
                                   value="<?php echo $user['age']; ?>" required>
                        </div>
                        
                        <!-- Member since -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Member Since</label>
                            <input type="text" class="form-control bg-light" disabled
                                   value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>">
                        </div>
                        
                        <!-- Address -->
                        <div class="col-md-12 mb-4">
                            <label for="address" class="form-label">Full Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <!-- ===== CHANGE PASSWORD CARD ===== -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-key-fill me-2 text-warning"></i>Change Password
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" 
                                   placeholder="Enter current password" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Min 6 characters" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Retype new password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-warning text-dark">
                        <i class="bi bi-arrow-repeat me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ===== AVATAR / QUICK STATS SIDEBAR ===== -->
    <div class="col-lg-4">
        <div class="card text-center mb-4">
            <div class="card-body p-4">
                <div class="avatar-circle">
                    <?php echo $avatarLetter; ?>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge bg-primary mb-3">
                    <?php echo ucfirst($user['role']); ?>
                </span>
                
                <hr>
                
                <div class="row text-center">
                    <?php 
                    // Count user's appointments
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
                    $stmt->execute([$user_id]);
                    $total_apt = $stmt->fetchColumn();
                    ?>
                    <div class="col-6">
                        <h3 class="text-primary mb-0"><?php echo $total_apt; ?></h3>
                        <small class="text-muted">Appointments</small>
                    </div>
                    <div class="col-6">
                        <h3 class="text-success mb-0"><?php echo $user['age']; ?></h3>
                        <small class="text-muted">Age</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Info Card -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Quick Info</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-telephone me-2"></i>Phone:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($user['phone']); ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-gender-ambiguous me-2"></i>Gender:</span>
                        <span class="fw-bold"><?php echo $user['gender']; ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-calendar me-2"></i>Age:</span>
                        <span class="fw-bold"><?php echo $user['age']; ?> yrs</span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1">
                            <i class="bi bi-house me-2"></i>Address:
                        </span>
                        <span class="fw-bold"><?php echo htmlspecialchars($user['address']); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
