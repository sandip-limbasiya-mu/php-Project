<?php
/**
 * dashboard.php - Dashboard Page
 * 
 * This is the main landing page after login.
 * Admin sees total doctors/patients/appointments stats.
 * Patient sees their own appointment summary.
 */

require_once 'config.php';

// ---- SECURITY CHECK: If user is not logged in, redirect to login ----
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

$success = '';
// If user just logged in, show welcome message
if(isset($_GET['welcome']) && $_GET['welcome'] == 1) {
    $success = "Welcome back, " . $user_name . "!";
}

// ===== Get Dashboard Statistics =====
$stats = array();

if($user_role == 'admin') {
    // ADMIN: Get total counts from all tables
    try {
        // Total Doctors
        $stmt = $conn->query("SELECT COUNT(*) FROM doctors");
        $stats['total_doctors'] = $stmt->fetchColumn();
        
        // Total Patients (role = 'patient')
        $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'patient'");
        $stats['total_patients'] = $stmt->fetchColumn();
        
        // Total Appointments
        $stmt = $conn->query("SELECT COUNT(*) FROM appointments");
        $stats['total_appointments'] = $stmt->fetchColumn();
        
        // Pending Appointments (status = 'pending')
        $stmt = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
        $stats['pending_appointments'] = $stmt->fetchColumn();
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    // PATIENT: Get their personal stats
    try {
        // My Total Appointments
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
        $stmt->execute([$user_id]);
        $stats['my_appointments'] = $stmt->fetchColumn();
        
        // My Approved Appointments
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'approved'");
        $stmt->execute([$user_id]);
        $stats['approved_appointments'] = $stmt->fetchColumn();
        
        // My Pending Appointments
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        $stats['pending_appointments'] = $stmt->fetchColumn();
        
        // My Rejected Appointments
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'rejected'");
        $stmt->execute([$user_id]);
        $stats['rejected_appointments'] = $stmt->fetchColumn();
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// ===== Recent Appointments List =====
$recent_appointments = array();
try {
    if($user_role == 'admin') {
        // Admin sees latest 5 appointments with all details
        $stmt = $conn->prepare("SELECT a.*, d.name as doctor_name, d.specialization, u.name as patient_name 
                                FROM appointments a 
                                JOIN doctors d ON a.doctor_id = d.id 
                                JOIN users u ON a.patient_id = u.id 
                                ORDER BY a.created_at DESC 
                                LIMIT 5");
        $stmt->execute();
    } else {
        // Patient sees only their 5 latest appointments
        $stmt = $conn->prepare("SELECT a.*, d.name as doctor_name, d.specialization 
                                FROM appointments a 
                                JOIN doctors d ON a.doctor_id = d.id 
                                WHERE a.patient_id = ? 
                                ORDER BY a.created_at DESC 
                                LIMIT 5");
        $stmt->execute([$user_id]);
    }
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

include 'header.php';
?>

<!-- Welcome Message Alert -->
<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard
    </h2>
    <small class="text-muted">
        Last Login: <?php echo date('F j, Y, g:i A'); ?>
    </small>
</div>

<?php if($user_role == 'admin'): ?>
    <!-- ===== ADMIN STATS CARDS ===== -->
    <div class="row g-4 mb-5">
        <!-- Total Doctors -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-doctors p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-info"><?php echo $stats['total_doctors']; ?></div>
                        <div class="stat-label">Total Doctors</div>
                    </div>
                    <i class="bi bi-person-badge text-info" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Total Patients -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-patients p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-success"><?php echo $stats['total_patients']; ?></div>
                        <div class="stat-label">Total Patients</div>
                    </div>
                    <i class="bi bi-people text-success" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Total Appointments -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-appointments p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-warning"><?php echo $stats['total_appointments']; ?></div>
                        <div class="stat-label">Total Appointments</div>
                    </div>
                    <i class="bi bi-calendar3 text-warning" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Pending Appointments -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-pending p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-danger"><?php echo $stats['pending_appointments']; ?></div>
                        <div class="stat-label">Pending Appointments</div>
                    </div>
                    <i class="bi bi-hourglass-split text-danger" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Quick Actions -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="doctors.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-person-plus-fill text-primary" style="font-size: 2.5rem; margin-bottom:10px;"></i>
                    <h5 class="text-dark">Manage Doctors</h5>
                    <p class="text-muted mb-0">Add, Edit or Remove Doctors</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="appointments.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-calendar-check-fill text-success" style="font-size: 2.5rem; margin-bottom:10px;"></i>
                    <h5 class="text-dark">Manage Appointments</h5>
                    <p class="text-muted mb-0">Approve or Reject Bookings</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="profile.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-person-circle text-info" style="font-size: 2.5rem; margin-bottom:10px;"></i>
                    <h5 class="text-dark">My Profile</h5>
                    <p class="text-muted mb-0">View and Update Profile</p>
                </div>
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- ===== PATIENT STATS CARDS ===== -->
    <div class="row g-4 mb-5">
        <!-- My Appointments -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-appointments p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-warning"><?php echo $stats['my_appointments']; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <i class="bi bi-calendar3 text-warning" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Approved -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-patients p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-success"><?php echo $stats['approved_appointments']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Pending -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-doctors p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-info"><?php echo $stats['pending_appointments']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <i class="bi bi-hourglass-split text-info" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Rejected -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-pending p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-danger"><?php echo $stats['rejected_appointments']; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 2.5rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Quick Actions -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="appointment.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-calendar-plus-fill text-primary" style="font-size: 2.5rem; margin-bottom:10px;"></i>
                    <h5 class="text-dark">Book Appointment</h5>
                    <p class="text-muted mb-0">Schedule a new consultation</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="appointments.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-calendar3 text-success" style="font-size: 2.5rem; margin-bottom:10px;"></i>
                    <h5 class="text-dark">My Appointments</h5>
                    <p class="text-muted mb-0">View all bookings</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="profile.php" class="text-decoration-none">
                <div class="card text-center p-4 h-100">
                    <i class="bi bi-person-circle text-info" style="font-size: 2.5rem; margin-bottom:10px;"></i>
                    <h5 class="text-dark">My Profile</h5>
                    <p class="text-muted mb-0">View and update details</p>
                </div>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- ===== Recent Appointments Table ===== -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-clock-history me-2 text-primary"></i>
            <?php echo ($user_role == 'admin') ? 'Recent Appointments' : 'My Recent Appointments'; ?>
        </h5>
        <a href="appointments.php" class="btn btn-sm btn-outline-primary">
            View All <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <?php if($user_role == 'admin'): ?>
                            <th>Patient</th>
                        <?php endif; ?>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($recent_appointments) > 0): ?>
                        <?php foreach($recent_appointments as $apt): ?>
                            <tr>
                                <?php if($user_role == 'admin'): ?>
                                    <td><?php echo $apt['patient_name']; ?></td>
                                <?php endif; ?>
                                <td><?php echo $apt['doctor_name']; ?></td>
                                <td><?php echo $apt['specialization']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                <td><?php echo $apt['appointment_time']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo ($user_role == 'admin') ? 6 : 5; ?>" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox me-2"></i>No appointments found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
