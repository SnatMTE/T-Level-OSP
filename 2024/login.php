<?php
/**
 * Login Page
 * 
 * @author Maitiú Ellis
 * @description User authentication page for logging in
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
$pageTitle = 'Login - RZA';

// Handle form submission
if (isRequestMethod('POST')) {
    $email = getPost('email');
    $password = getPost('password');
    
    // TODO: Add validation here
    // TODO: Add authentication logic here
}

// Include header template
require_once ROOT_DIR . '/templates/header.php';
?>

<!-- Login Page Content -->
<h1>Login</h1>

<form method="POST" action="login.php">
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>

    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="/register.php">Register here</a></p>

<?php 
// Include footer template
require_once ROOT_DIR . '/templates/footer.php'; 
?>
