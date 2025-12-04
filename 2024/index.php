<?php
/**
 * Index - Home Page
 * 
 * @author Maitiú Ellis
 * @description Main landing page of the application
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

// Initialize database on first run
Database::initialize();

// Set page title
$pageTitle = 'Home - RZA';

// Include header template
require_once ROOT_DIR . '/templates/header.php';
?>

<!-- Home Page Content -->
<h1>Welcome to RZA</h1>
<p>This is the home page of the application.</p>

<?php 
// Include footer template
require_once ROOT_DIR . '/templates/footer.php'; 
?>