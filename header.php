<?php
/**
 * header.php - Common Header File
 * 
 * This file is included at the top of every page.
 * It contains the HTML head, Bootstrap CSS, navigation bar, and starts the page body.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Appointment Booking System</title>
    
    <!-- Bootstrap 5 CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <!-- Logo / Brand Name -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-heart-pulse me-2"></i>MedCare Hospital
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php 
                    // Show these links only if user is logged in
                    if(isset($_SESSION['user_id'])): 
                    ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="appointment.php">
                                <i class="bi bi-calendar-plus me-1"></i>Book Appointment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="appointments.php">
                                <i class="bi bi-calendar3 me-1"></i>My Appointments
                            </a>
                        </li>
                        <?php 
                        // Show Doctors link only for Admin
                        if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): 
                        ?>
                            <li class="nav-item">
                                <a class="nav-link" href="doctors.php">
                                    <i class="bi bi-person-badge me-1"></i>Doctors
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person-circle me-1"></i>Profile
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Right side buttons -->
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link text-white-50">
                                <i class="bi bi-person-fill me-1"></i><?php echo $_SESSION['user_name']; ?>
                                <span class="badge bg-light text-primary ms-1"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item me-2">
                            <a class="nav-link btn btn-outline-light btn-sm" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-light text-primary btn-sm" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="container my-4">
