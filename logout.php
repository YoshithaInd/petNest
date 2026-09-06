<?php
/**
 * PetNest - Logout Handler
 * Purpose: Destroys user session and securely logs out of the platform.
 * Scope: Member 1
 */
require_once __DIR__ . '/config/db.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start new session to carry flash message
session_start();
setFlash('info', 'You have been successfully logged out.');

redirect('/login.php');