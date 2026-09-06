<?php
/**
 * PetNest - Database Connection
 * Purpose: PDO database connection configuration and handler.
 * Scope: System / Core
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'petnest_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Project Root URL (adjust if running inside subdirectory in htdocs)
define('BASE_URL', '/PetNest');

// Upload Paths
define('UPLOAD_PATH_PROFILES', __DIR__ . '/../uploads/profiles/');
define('UPLOAD_PATH_PETS', __DIR__ . '/../uploads/pets/');

// Establish PDO Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // In production, log the error instead of outputting sensitive DB details
    die("<div style='font-family:sans-serif; padding:20px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:6px;'>
            <h3>⚠️ Database Connection Error</h3>
            <p>Could not connect to the database <strong>" . DB_NAME . "</strong>.</p>
            <p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><em>Please ensure XAMPP MySQL is running and you have imported <code>database.sql</code> in phpMyAdmin.</em></p>
         </div>");
}

/**
 * Clean and sanitize user input string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

/**
 * Set flash message to display on the next page
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Display and clear flash message
 */
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        echo "<div class='alert alert-{$type} alert-dismissible' role='alert'>
                <span>{$message}</span>
                <button type='button' class='btn-close' onclick='this.parentElement.remove();'>&times;</button>
              </div>";
    }
}
