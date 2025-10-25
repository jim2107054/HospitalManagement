<?php
// Session check middleware
// Include this file at the top of dashboard pages that require authentication

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // User is not logged in, redirect to login page
    header('Location: login.html');
    exit();
}

// Optional: Check if session has expired (e.g., after 2 hours of inactivity)
$sessionTimeout = 2 * 60 * 60; // 2 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $sessionTimeout)) {
    // Session expired
    session_unset();
    session_destroy();
    header('Location: login.html?expired=true');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
