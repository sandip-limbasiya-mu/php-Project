<?php
/**
 * logout.php - Logout Page
 * 
 * This page destroys the user session and redirects to login page.
 * Very simple but very important for security.
 */

require_once 'config.php';

// Destroy ALL session variables
$_SESSION = array();

// Delete the session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session completely
session_destroy();

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit;
?>
