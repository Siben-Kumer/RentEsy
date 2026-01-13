<?php
// 1. Ensure all session-related functionalities are loaded.
require_once 'includes/session.php'; // This starts the session with secure settings.
require_once 'database.php';
require_once 'includes/auth.php'; // This has the cookie clearing functions.

// 2. If a "Remember Me" cookie exists, invalidate it on the server and client.
if (!empty($_COOKIE['remember_me'])) {
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);
    if ($selector && $validator) {
        // Delete the token from the database.
        clear_auth_token($selector, $pdo);
    }
    // Expire the cookie on the client's browser.
    clear_remember_me_cookie();
}

// 3. Unset all session variables.
$_SESSION = [];

// 4. Destroy the current session.
session_destroy();

// 5. Redirect to the login page with a success message.
// A query parameter is used since the session has been destroyed.
header("Location: login.php?status=loggedout");
exit();
