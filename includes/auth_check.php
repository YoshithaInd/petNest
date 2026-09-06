<?php
/**
 * PetNest - Auth Check Helper
 * Purpose: Session security check helper and role-based access control.
 * Scope: Shared Security
 */
require_once __DIR__ . '/../config/db.php';

/**
 * Check if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details from session
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'name'      => $_SESSION['user_name'] ?? 'User',
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? '',
        'photo'     => $_SESSION['user_photo'] ?? 'default_avatar.png',
    ];
}

/**
 * Check if the current user has a specific role
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require user to be logged in, otherwise redirect to login page
 */
function requireAuth() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access that page.');
        redirect('/login.php');
    }
}

/**
 * Require user to have one of the specified roles
 * @param array|string $allowedRoles Single role string or array of role strings
 */
function requireRole($allowedRoles) {
    requireAuth();
    
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    $userRole = $_SESSION['user_role'] ?? '';
    if (!in_array($userRole, $allowedRoles, true)) {
        setFlash('danger', 'Access denied. You do not have permission to view that page.');
        
        // Redirect to their appropriate dashboard
        switch ($userRole) {
            case 'admin':
                redirect('/admin/dashboard.php');
                break;
            case 'operator':
                redirect('/operator/dashboard.php');
                break;
            case 'keeper':
                redirect('/keeper/dashboard.php');
                break;
            case 'owner':
                redirect('/owner/dashboard.php');
                break;
            default:
                redirect('/login.php');
        }
    }
}
