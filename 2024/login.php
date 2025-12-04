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
    
    // Basic validation
    if (empty($email) || empty($password)) {
        setFlash('error', 'Please provide email and password.');
        redirect('login.php');
    }

    // Ensure database/tables are ready
    Database::initialize();

    $pdo = Database::getInstance();
    $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
        setFlash('error', 'Invalid email or password.');
        redirect('login.php');
    }

    // Success: set session and redirect
    setUserSession((int)$user['id'], ['name' => $user['name'], 'email' => $user['email']]);
    setFlash('success', 'Logged in successfully.');
    // Redirect to destination if provided
    $redirectTo = getGet('redirect', 'dashboard.php');
    redirect($redirectTo);
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

<p>Don't have an account? <a href="register.php">Register here</a></p>

<?php 
// Include footer template
require_once ROOT_DIR . '/templates/footer.php'; 
?>