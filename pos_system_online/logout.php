<?php

// Load authentication functions
require_once __DIR__ . "/includes/auth.php";


// Clear all session information
$_SESSION = [];


// Remove the session cookie
if (ini_get("session.use_cookies")) {

    // Get current session cookie settings
    $p = session_get_cookie_params();


    // Expire the session cookie
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $p["path"],
        $p["domain"],
        $p["secure"],
        $p["httponly"]
    );

}


// Destroy the session
session_destroy();


// Return to the login page
header("Location: login.php");
exit;

?>