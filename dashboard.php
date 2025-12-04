<?php
/**
 * Dashboard Page
 * 
 * @author Maitiú Ellis
 * @description User dashboard - requires authentication
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

// Set page title
// Include header template
require_once ROOT_DIR . '/templates/header.php';
?>

<!-- Dashboard Page Content -->
// Include header template
require_once __DIR__ . '/templates/header.php';
?>

<!-- Dashboard Page Content -->
<h1>Dashboard</h1>

<p>Welcome, <?php echo esc($_SESSION['user_data']['name'] ?? 'User'); ?>!</p>

<section>
    <h2>Your Bookings</h2>
    <!-- TODO: Display user bookings here -->
    <p>No bookings yet.</p>
</section>

<?php 
// Include footer template
require_once ROOT_DIR . '/templates/footer.php'; 
?>
