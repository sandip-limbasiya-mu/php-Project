<?php

?>
    </div> <!-- Close the main container div from header.php -->
    
    <!-- Footer Section -->
    <footer class="bg-light py-4 mt-5 border-top">
        <div class="container text-center text-muted">
            <p class="mb-0">
                <i class="bi bi-heart-pulse text-primary me-1"></i>
                &copy; 2026 MedCare Hospital Appointment System
                <span class="mx-2">|</span>
                 
            </p>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JavaScript Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Simple JavaScript for Bootstrap alerts auto-hide -->
    <script>
        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
