<?php
/**
 * doctors.php - Doctor Management Page (ADMIN ONLY)
 * 
 * Admin can Add, Edit, Delete doctors from this page.
 * Regular patients should NOT have access to this page.
 */

require_once 'config.php';

// ---- SECURITY CHECK: Must be logged in AND be an admin ----
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if($_SESSION['user_role'] != 'admin') {
    die("Access denied! Only administrators can view this page.");
}

$error = '';
$success = '';

// ===== HANDLE FORM ACTIONS (Add / Edit / Delete) =====

// --- ACTION: DELETE DOCTOR ---
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
        if($stmt->execute([$id])) {
            $success = "Doctor deleted successfully!";
        } else {
            $error = "Failed to delete doctor.";
        }
    } catch(PDOException $e) {
        $error = "Cannot delete: This doctor has existing appointments.";
    }
}

// --- ACTION: ADD OR UPDATE DOCTOR ---
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form fields
    $name = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $availability = trim($_POST['availability']);
    $fee = (float)$_POST['fee'];
    $doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
    
    // Validation
    if(empty($name)) {
        $error = "Doctor name is required.";
    } elseif(empty($specialization)) {
        $error = "Specialization is required.";
    } elseif(empty($contact)) {
        $error = "Contact number is required.";
    } elseif(empty($email)) {
        $error = "Email is required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif(empty($availability)) {
        $error = "Availability is required.";
    } elseif($fee < 0) {
        $error = "Fee cannot be negative.";
    } else {
        
        try {
            if($doctor_id > 0) {
                // ===== UPDATE EXISTING DOCTOR =====
                $stmt = $conn->prepare("UPDATE doctors 
                                       SET name = ?, specialization = ?, contact = ?, email = ?, availability = ?, fee = ? 
                                       WHERE id = ?");
                $result = $stmt->execute([$name, $specialization, $contact, $email, $availability, $fee, $doctor_id]);
                if($result) $success = "Doctor updated successfully!";
            } else {
                // ===== ADD NEW DOCTOR =====
                $stmt = $conn->prepare("INSERT INTO doctors (name, specialization, contact, email, availability, fee) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([$name, $specialization, $contact, $email, $availability, $fee]);
                if($result) $success = "Doctor added successfully!";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ===== FETCH ALL DOCTORS FOR DISPLAY =====
$doctors = array();
try {
    $stmt = $conn->query("SELECT * FROM doctors ORDER BY created_at DESC");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching doctors: " . $e->getMessage();
}

// ===== GET DOCTOR DATA FOR EDIT MODE =====
$edit_doctor = null;
if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ? LIMIT 1");
        $stmt->execute([$edit_id]);
        $edit_doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching doctor details.";
    }
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
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-person-badge text-primary me-2"></i>Doctor Management
    </h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#doctorModal" onclick="resetForm()">
        <i class="bi bi-plus-lg me-2"></i>Add New Doctor
    </button>
</div>

<!-- Doctors Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Availability</th>
                        <th>Fee</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($doctors) > 0): ?>
                        <?php foreach($doctors as $index => $doc): ?>
                            <tr>
                                <td><?php echo $doc['id']; ?></td>
                                <td>
                                    <strong><?php echo $doc['name']; ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $doc['specialization']; ?></span>
                                </td>
                                <td><?php echo $doc['contact']; ?></td>
                                <td><?php echo $doc['email']; ?></td>
                                <td class="small"><?php echo $doc['availability']; ?></td>
                                <td>₹<?php echo number_format($doc['fee'], 2); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doc)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="doctors.php?action=delete&id=<?php echo $doc['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete Dr. <?php echo $doc['name']; ?>?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox me-2"></i>No doctors found. Click "Add New Doctor" to get started.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== ADD / EDIT DOCTOR MODAL ===== -->
<div class="modal fade" id="doctorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="bi bi-person-plus me-2"></i>Add New Doctor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Hidden field for doctor ID (0 = new record) -->
                    <input type="hidden" name="doctor_id" id="doctor_id" value="0">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Doctor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="doctor_name" 
                                   placeholder="Dr. John Doe" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialization <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="specialization" id="doctor_specialization" 
                                   placeholder="Cardiologist" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact No. <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="contact" id="doctor_contact" 
                                   placeholder="9876543210" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="doctor_email" 
                                   placeholder="doctor@hospital.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Availability <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="availability" id="doctor_availability" 
                                   placeholder="Mon-Fri: 9AM-5PM" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Consultation Fee (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="fee" id="doctor_fee" 
                                   placeholder="500.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSaveBtn">
                        <i class="bi bi-save me-2"></i>Save Doctor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== JAVASCRIPT FOR EDIT/RESET FUNCTIONS ===== -->
<script>
    // Function to fill form with existing doctor data for editing
    function editDoctor(doctor) {
        document.getElementById('doctor_id').value = doctor.id;
        document.getElementById('doctor_name').value = doctor.name;
        document.getElementById('doctor_specialization').value = doctor.specialization;
        document.getElementById('doctor_contact').value = doctor.contact;
        document.getElementById('doctor_email').value = doctor.email;
        document.getElementById('doctor_availability').value = doctor.availability;
        document.getElementById('doctor_fee').value = doctor.fee;
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Doctor';
        document.getElementById('modalSaveBtn').innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Update Doctor';
        
        // Manually show the Bootstrap modal
        var modal = new bootstrap.Modal(document.getElementById('doctorModal'));
        modal.show();
    }
    
    // Function to reset form to add new doctor mode
    function resetForm() {
        document.getElementById('doctor_id').value = 0;
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i>Add New Doctor';
        document.getElementById('modalSaveBtn').innerHTML = '<i class="bi bi-save me-2"></i>Save Doctor';
        // Reset all form fields inside the modal
        document.querySelector('#doctorModal form').reset();
    }
</script>

<?php include 'footer.php'; ?>
