<?php
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Logout the user
$result = logoutUser();

// Remove remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to home page with success message
redirect('login.php?message=' . urlencode('You have been successfully logged out.'));
?>
