<?php
/**
 * Access Control Helper
 * 
 * This file provides role-based access control functions for the application.
 * Use this to protect pages by checking user roles before allowing access.
 * 
 * Usage:
 *   require_once('../includes/access_control.php');
 *   require_admin(); // Redirects if not admin
 *   require_customer(); // Redirects if not customer
 *   require_staff(); // Redirects if staff, manager, or delivery
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * Returns true if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user has a specific role
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is any staff-related role (staff, manager, delivery)
 */
function is_staff_role() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['staff', 'manager', 'delivery']);
}

/**
 * Check if user is admin and redirect if not
 * MUST be called BEFORE header includes
 */
function require_admin() {
    if (!has_role('admin')) {
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '') . 'index.php');
        exit;
    }
}

/**
 * Check if user is customer and redirect if not
 * MUST be called BEFORE header includes
 */
function require_customer() {
    if (!has_role('customer')) {
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '../' : '') . 'index.php');
        exit;
    }
}

/**
 * Check if user is staff (including manager/delivery) and redirect if not
 * MUST be called BEFORE header includes
 */
function require_staff() {
    if (!is_staff_role()) {
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/staff/') !== false ? '../' : '') . 'index.php');
        exit;
    }
}

/**
 * Check if user is manager and redirect if not
 * MUST be called BEFORE header includes
 */
function require_manager() {
    if (!has_role('manager')) {
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/staff/') !== false ? 'dashboard.php' : '../index.php'));
        exit;
    }
}

/**
 * Check if user is delivery and redirect if not
 * MUST be called BEFORE header includes
 */
function require_delivery() {
    if (!has_role('delivery')) {
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/staff/') !== false ? 'dashboard.php' : '../index.php'));
        exit;
    }
}

/**
 * Get current user role
 */
function get_user_role() {
    return $_SESSION['role'] ?? 'guest';
}

/**
 * Get current user ID
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Redirect to login page
 * MUST be called BEFORE header includes
 */
function redirect_to_login() {
    header('Location: ../login.php');
    exit;
}

/**
 * Get role-specific dashboard URL
 */
function get_dashboard_url($role = null) {
    $role = $role ?? get_user_role();
    
    switch($role) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'manager':
        case 'staff':
        case 'delivery':
            return 'staff/dashboard.php';
        case 'customer':
            return 'customer/dashboard.php';
        default:
            return 'index.php';
    }
}
?>
