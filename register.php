<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Register Page
 * 
 * @author Maitiú Ellis
 * @description User registration page for creating new accounts
 */

// Start session
session_start();

// Define root directory for consistent path resolution
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// Include necessary files
require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/helpers.php';
require_once ROOT_DIR . '/functions/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Set page title
$pageTitle = 'Register - RZA';

// Handle form submission
if (isRequestMethod('POST')) {
    $name = getPost('name');
    $email = getPost('email');
    $password = getPost('password');
    $confirmPassword = getPost('confirm_password');
    
    // TODO: Add validation here
    // TODO: Add user creation logic here
}

// Include header template
require_once ROOT_DIR . '/templates/header.php';
?>

<!-- Register Page Content -->
<h1>Register</h1>

<form method="POST" action="register.php">
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
    </div>

    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>

    <div>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>

    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login here</a></p>

<?php 
// Include footer template
require_once ROOT_DIR . '/templates/footer.php'; 
?>
