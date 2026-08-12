<?php
/**
 * appointment.php - Book Appointment Page
 * 
 * Patients use this page to book new appointments with doctors.
 * Patients select a doctor, date, time, and write a reason for visit.
 */

require_once 'config.php';

// ---- SECURITY: Must be logged in ----
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$error = '';
$success = '';

// ===== HANDLE FORM SUBMISSION (Booking an Appointment) =====
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $doctor_id = (int)$_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);
    
    // ===== FORM VALIDATION =====
    if(empty($doctor_id) || $doctor_id <= 0) {
        $error = "Please select a doctor.";
    } elseif(empty($appointment_date)) {
        $error = "Please select an appointment date.";
    } else {
        // Check that date is NOT in the past
        $today = date('Y-m-d');
        if($appointment_date < $today) {
            $error = "Appointment date cannot be in the past.";
        } elseif(empty($appointment_time)) {
            $error = "Please select an appointment time.";
        } elseif(empty($reason)) {
            $error = "Please describe the reason for your visit.";
        } elseif(strlen($reason) < 5) {
            $error = "Reason should be at least 5 characters.";
        } else {
            
            try {
                // ===== CHECK FOR DUPLICATE: Same doctor, same date, same time =====
                $check_stmt = $conn->prepare("SELECT id FROM appointments 
                                              WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                                              LIMIT 1");
                $check_stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
                if($check_stmt->rowCount() > 0) {
                    $error = "Sorry, this time slot is already booked. Please choose another time.";
                } else {
                    
                    // ===== INSERT APPOINTMENT INTO DATABASE =====
                    $stmt = $conn->prepare("INSERT INTO appointments 
                                           (patient_id, doctor_id, appointment_date, appointment_time, reason, status) 
                                           VALUES (?, ?, ?, ?, ?, 'pending')");
                    
                    $result = $stmt->execute([$user_id, $doctor_id, $appointment_date, $appointment_time, $reason]);
                    
                    if($result) {
                        $success = "Appointment booked successfully! Waiting for admin approval. Redirecting...";
                        header("refresh:2; url=appointments.php");
                    } else {
                        $error = "Failed to book appointment. Please try again.";
                    }
                }
            } catch(PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// ===== FETCH ALL DOCTORS FOR THE DROPDOWN =====
$doctors = array();
try {
    $stmt = $conn->query("SELECT * FROM doctors ORDER BY name ASC");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading doctors: " . $e->getMessage();
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
        <i class="bi bi-calendar-plus text-primary me-2"></i>Book Appointment
    </h2>
    <a href="appointments.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>View My Appointments
    </a>
</div>

<div class="row">
    
    <!-- ===== APPOINTMENT BOOKING FORM ===== -->
    <div class="col-lg-7 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Fill Booking Details
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="" id="appointmentForm">
                    
                    <!-- Doctor Selection -->
                    <div class="mb-3">
                        <label for="doctor_id" class="form-label">
                            <i class="bi bi-person-badge me-1"></i>Select Doctor <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg" id="doctor_id" name="doctor_id" required>
                            <option value="">-- Please Select a Doctor --</option>
                            <?php foreach($doctors as $doc): ?>
                                <option value="<?php echo $doc['id']; ?>" 
                                    <?php echo (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doc['id']) ? 'selected' : ''; ?>>
                                    <?php echo $doc['name']; ?> - <?php echo $doc['specialization']; ?>
                                    (₹<?php echo number_format($doc['fee'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Choose a doctor based on their specialization.</div>
                    </div>
                    
                    <!-- Doctor Info Preview (populated via JS when doctor is selected) -->
                    <div class="card bg-light mb-4" id="doctorInfoCard" style="display:none;">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="doctor-avatar me-3 mb-0" id="docAvatar">?</div>
                                <div>
                                    <h6 class="mb-1" id="docName">-</h6>
                                    <p class="text-primary mb-1" id="docSpecial">-</p>
                                    <p class="small text-muted mb-1">
                                        <i class="bi bi-clock me-1"></i><span id="docAvailability">-</span>
                                    </p>
                                    <p class="small fw-bold mb-0">
                                        <i class="bi bi-currency-rupee me-1"></i>Fee: <span id="docFee">-</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Appointment Date -->
                        <div class="col-md-6 mb-3">
                            <label for="appointment_date" class="form-label">
                                <i class="bi bi-calendar-event me-1"></i>Appointment Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-lg" 
                                   id="appointment_date" name="appointment_date" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo isset($_POST['appointment_date']) ? $_POST['appointment_date'] : ''; ?>"
                                   required>
                            <div class="form-text">Select a date from today onwards.</div>
                        </div>
                        
                        <!-- Appointment Time -->
                        <div class="col-md-6 mb-3">
                            <label for="appointment_time" class="form-label">
                                <i class="bi bi-clock me-1"></i>Appointment Time <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg" id="appointment_time" name="appointment_time" required>
                                <option value="">-- Select Time --</option>
                                <option value="09:00 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '09:00 AM') ? 'selected' : ''; ?>>09:00 AM</option>
                                <option value="09:30 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '09:30 AM') ? 'selected' : ''; ?>>09:30 AM</option>
                                <option value="10:00 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '10:00 AM') ? 'selected' : ''; ?>>10:00 AM</option>
                                <option value="10:30 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '10:30 AM') ? 'selected' : ''; ?>>10:30 AM</option>
                                <option value="11:00 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '11:00 AM') ? 'selected' : ''; ?>>11:00 AM</option>
                                <option value="11:30 AM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '11:30 AM') ? 'selected' : ''; ?>>11:30 AM</option>
                                <option value="02:00 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '02:00 PM') ? 'selected' : ''; ?>>02:00 PM</option>
                                <option value="02:30 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '02:30 PM') ? 'selected' : ''; ?>>02:30 PM</option>
                                <option value="03:00 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '03:00 PM') ? 'selected' : ''; ?>>03:00 PM</option>
                                <option value="03:30 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '03:30 PM') ? 'selected' : ''; ?>>03:30 PM</option>
                                <option value="04:00 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '04:00 PM') ? 'selected' : ''; ?>>04:00 PM</option>
                                <option value="04:30 PM" <?php echo (isset($_POST['appointment_time']) && $_POST['appointment_time'] == '04:30 PM') ? 'selected' : ''; ?>>04:30 PM</option>
                            </select>
                            <div class="form-text">Available slots in 30-minute intervals.</div>
                        </div>
                    </div>
                    
                    <!-- Reason for Visit -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">
                            <i class="bi bi-file-medical me-1"></i>Reason for Visit <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" 
                                  placeholder="Please describe your symptoms or reason for consultation..." 
                                  required><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                        <div class="form-text">Briefly describe your health issue so the doctor can prepare.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 btn-lg">
                        <i class="bi bi-calendar-check me-2"></i>Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ===== DOCTORS LIST SIDEBAR ===== -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2 text-primary"></i>Available Doctors
                </h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($doctors as $doc): ?>
                        <li class="list-group-item d-flex align-items-center" style="cursor:pointer;" 
                            onclick="selectDoctor(<?php echo $doc['id']; ?>)">
                            <div class="doctor-avatar me-3 mb-0" style="width:50px; height:50px; font-size:1.2rem;">
                                <?php echo strtoupper($doc['name'][0]); ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo $doc['name']; ?></h6>
                                <p class="small text-primary mb-1"><?php echo $doc['specialization']; ?></p>
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-telephone me-1"></i><?php echo $doc['contact']; ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">₹<?php echo number_format($doc['fee'], 0); ?></div>
                                <div class="small text-muted">Fee</div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
    // Store doctor data in JS array from PHP
    const doctorsList = <?php echo json_encode($doctors); ?>;
    
    // When user clicks a doctor from the sidebar
    function selectDoctor(id) {
        document.getElementById('doctor_id').value = id;
        updateDoctorInfo(id);
    }
    
    // When doctor changes in the dropdown
    document.getElementById('doctor_id').addEventListener('change', function() {
        updateDoctorInfo(this.value);
    });
    
    // Show doctor info card
    function updateDoctorInfo(doctorId) {
        const card = document.getElementById('doctorInfoCard');
        if(!doctorId) {
            card.style.display = 'none';
            return;
        }
        
        const doctor = doctorsList.find(d => d.id == doctorId);
        if(doctor) {
            card.style.display = 'block';
            document.getElementById('docAvatar').textContent = doctor.name.charAt(0).toUpperCase();
            document.getElementById('docName').textContent = doctor.name;
            document.getElementById('docSpecial').textContent = doctor.specialization;
            document.getElementById('docAvailability').textContent = doctor.availability;
            document.getElementById('docFee').textContent = '₹' + parseFloat(doctor.fee).toLocaleString('en-IN', {minimumFractionDigits: 2});
        }
    }
    
    // If form is repopulated (on error), trigger doctor info display
    window.addEventListener('DOMContentLoaded', function() {
        const currentDoc = document.getElementById('doctor_id').value;
        if(currentDoc) updateDoctorInfo(currentDoc);
    });
</script>

<?php include 'footer.php'; ?>
