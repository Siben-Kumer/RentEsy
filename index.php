<?php
//

require_once 'includes/session.php';

if (isset($_SESSION['user'])) {
    // User is logged in, redirect to their specific dashboard
    $role = strtolower($_SESSION['user']['role']);
    $dashboard_url = 'dashboard/' . $role . '/';
    
    // Before redirecting, check if the directory for the role exists
    if (is_dir($dashboard_url)) {
        header("Location: " . $dashboard_url);
    } else {
        // Fallback for safety, though this case is unlikely if roles are managed well
        header("Location: login.php");
    }
    exit();
} else {
    // User is not logged in, redirect to the home page
    header("Location: home.php");
    exit();
}
