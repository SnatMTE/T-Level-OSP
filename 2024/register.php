<?php
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
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        setFlash('error', 'Please fill in all required fields.');
        redirect('register.php');
    }

    if ($password !== $confirmPassword) {
        setFlash('error', 'Passwords do not match.');
        redirect('register.php');
    }

    // Ensure database tables exist
    Database::initialize();

    // Create user (password hashing)
    $pdo = Database::getInstance();
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed,
        ]);
        $userId = (int)$pdo->lastInsertId();

        // Set session and redirect to dashboard
        setUserSession($userId, ['name' => $name, 'email' => $email]);
        setFlash('success', 'Registration successful. Welcome!');
        redirect('dashboard.php');
    } catch (PDOException $e) {
        // Unique constraint on email
        setFlash('error', 'Could not create account. Email may already be registered.');
        redirect('register.php');
    }
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