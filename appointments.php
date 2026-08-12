<?php
/**
 * appointments.php - View / Manage Appointments Page
 * 
 * PATIENT: Views only their own appointments.
 * ADMIN: Views ALL appointments, can Approve, Reject, or Delete.
 * Has SEARCH functionality by patient name, doctor name, date, or status.
 */

require_once 'config.php';

// ---- SECURITY CHECK: Must be logged in ----
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$error = '';
$success = '';

// ===== HANDLE ADMIN ACTIONS: Approve, Reject, Delete =====
if($user_role == 'admin' && isset($_GET['action']) && isset($_GET['id'])) {
    
    $action = $_GET['action'];
    $apt_id = (int)$_GET['id'];
    
    try {
        if($action == 'approve') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'approved' WHERE id = ?");
            if($stmt->execute([$apt_id])) $success = "Appointment APPROVED successfully!";
        }
        elseif($action == 'reject') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ?");
            if($stmt->execute([$apt_id])) $success = "Appointment REJECTED successfully!";
        }
        elseif($action == 'delete') {
            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
            if($stmt->execute([$apt_id])) $success = "Appointment DELETED successfully!";
        }
    } catch(PDOException $e) {
        $error = "Action failed: " . $e->getMessage();
    }
}

// ===== SEARCH AND FILTER LOGIC =====
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';

// Start building the SQL query dynamically
$sql = "SELECT a.*, d.name as doctor_name, d.specialization, d.fee, u.name as patient_name, u.phone, u.email ";
$sql .= "FROM appointments a ";
$sql .= "JOIN doctors d ON a.doctor_id = d.id ";
$sql .= "JOIN users u ON a.patient_id = u.id ";
$sql .= "WHERE 1=1 ";  // Base condition, always true

$params = array();  // Array to hold parameters for prepared statement

// --- Patient sees only their own appointments ---
if($user_role != 'admin') {
    $sql .= "AND a.patient_id = ? ";
    $params[] = $user_id;
}

// --- Search by keyword (patient name, doctor name, reason) ---
if(!empty($search)) {
    $sql .= "AND (u.name LIKE ? OR d.name LIKE ? OR a.reason LIKE ?) ";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// --- Filter by status ---
if(!empty($status_filter)) {
    $sql .= "AND a.status = ? ";
    $params[] = $status_filter;
}

// --- Filter by date ---
if(!empty($date_filter)) {
    $sql .= "AND a.appointment_date = ? ";
    $params[] = $date_filter;
}

// --- Order by date descending (newest first) ---
$sql .= "ORDER BY a.appointment_date DESC, a.created_at DESC";

// Execute query and get appointments
$appointments = array();
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching appointments: " . $e->getMessage();
}

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
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0">
        <i class="bi bi-calendar3 text-primary me-2"></i>
        <?php echo ($user_role == 'admin') ? 'All Appointments' : 'My Appointments'; ?>
        <span class="badge bg-secondary ms-2"><?php echo count($appointments); ?></span>
    </h2>
    <a href="appointment.php" class="btn btn-primary">
        <i class="bi bi-calendar-plus me-2"></i>Book New Appointment
    </a>
</div>

<!-- ===== SEARCH & FILTER BAR ===== -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <!-- Keyword Search -->
            <div class="col-md-4">
                <label class="form-label">
                    <i class="bi bi-search me-1"></i>Search
                </label>
                <input type="text" class="form-control" name="search" 
                       placeholder="Search patient, doctor, reason..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <!-- Status Filter -->
            <div class="col-md-2">
                <label class="form-label">
                    <i class="bi bi-filter me-1"></i>Status
                </label>
                <select class="form-select" name="status">
                    <option value="">All</option>
                    <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo ($status_filter == 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo ($status_filter == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <!-- Date Filter -->
            <div class="col-md-3">
                <label class="form-label">
                    <i class="bi bi-calendar-event me-1"></i>Date
                </label>
                <input type="date" class="form-control" name="date_filter" 
                       value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            
            <!-- Buttons -->
            <div class="col-md-3">
                <div class="d-grid gap-2 d-flex">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Apply
                    </button>
                    <a href="appointments.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===== APPOINTMENTS LIST ===== -->
<?php if(count($appointments) > 0): ?>
    <div class="row g-4">
        <?php foreach($appointments as $apt): ?>
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
                        <div>
                            <strong>#<?php echo $apt['id']; ?></strong>
                            <span class="status-badge status-<?php echo $apt['status']; ?> ms-2">
                                <?php echo ucfirst($apt['status']); ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?php echo date('M j, Y', strtotime($apt['created_at'])); ?>
                        </small>
                    </div>
                    <div class="card-body">
                        
                        <h5 class="card-title mb-3">
                            <i class="bi bi-person-badge text-primary me-2"></i>
                            <?php echo $apt['doctor_name']; ?>
                        </h5>
                        <p class="text-primary mb-3">
                            <i class="bi bi-stethoscope me-1"></i><?php echo $apt['specialization']; ?>
                        </p>
                        
                        <?php if($user_role == 'admin'): ?>
                            <p class="mb-2">
                                <strong><i class="bi bi-person me-1"></i>Patient:</strong>
                                <?php echo $apt['patient_name']; ?>
                            </p>
                            <p class="mb-2">
                                <strong><i class="bi bi-telephone me-1"></i>Phone:</strong>
                                <?php echo $apt['phone']; ?>
                            </p>
                        <?php endif; ?>
                        
                        <p class="mb-2">
                            <strong><i class="bi bi-calendar-event me-1"></i>Date:</strong>
                            <?php echo date('l, F j, Y', strtotime($apt['appointment_date'])); ?>
                        </p>
                        
                        <p class="mb-2">
                            <strong><i class="bi bi-clock me-1"></i>Time:</strong>
                            <?php echo $apt['appointment_time']; ?>
                        </p>
                        
                        <p class="mb-3">
                            <strong><i class="bi bi-currency-rupee me-1"></i>Fee:</strong>
                            ₹<?php echo number_format($apt['fee'], 2); ?>
                        </p>
                        
                        <hr class="my-3">
                        
                        <p class="mb-0">
                            <strong><i class="bi bi-file-medical me-1"></i>Reason:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($apt['reason']); ?></span>
                        </p>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="card-footer bg-white border-top">
                        <?php if($user_role == 'admin'): ?>
                            <!-- ADMIN ACTIONS -->
                            <div class="d-grid gap-2">
                                <?php if($apt['status'] == 'pending'): ?>
                                    <div class="d-grid gap-2 d-flex">
                                        <a href="appointments.php?action=approve&id=<?php echo $apt['id']; ?>" 
                                           class="btn btn-success flex-grow-1">
                                            <i class="bi bi-check-lg me-1"></i>Approve
                                        </a>
                                        <a href="appointments.php?action=reject&id=<?php echo $apt['id']; ?>" 
                                           class="btn btn-warning flex-grow-1"
                                           onclick="return confirm('Reject this appointment?')">
                                            <i class="bi bi-x-lg me-1"></i>Reject
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <a href="appointments.php?action=delete&id=<?php echo $apt['id']; ?>" 
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('Delete this appointment permanently?')">
                                    <i class="bi bi-trash me-1"></i>Delete Appointment
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- PATIENT VIEW: No actions, just status info -->
                            <?php if($apt['status'] == 'approved'): ?>
                                <div class="alert alert-success mb-0 py-2 small">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Your appointment has been approved. Please arrive 10 minutes early.
                                </div>
                            <?php elseif($apt['status'] == 'pending'): ?>
                                <div class="alert alert-warning mb-0 py-2 small">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    Your appointment is pending approval. Please wait for admin confirmation.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger mb-0 py-2 small">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Your appointment was rejected. Please book a new appointment with another time/doctor.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <!-- No appointments found message -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3 text-muted">No Appointments Found</h4>
            <p class="text-muted mb-4">
                <?php 
                if(!empty($search) || !empty($status_filter) || !empty($date_filter)) {
                    echo "Try changing your search or filter criteria.";
                } else {
                    echo ($user_role == 'admin') ? "No appointments have been booked yet." : "You haven't booked any appointments yet.";
                }
                ?>
            </p>
            <div class="d-flex justify-content-center gap-2">
                <?php if(!empty($search) || !empty($status_filter) || !empty($date_filter)): ?>
                    <a href="appointments.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>Clear Filters
                    </a>
                <?php endif; ?>
                <a href="appointment.php" class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-1"></i>Book Appointment
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
