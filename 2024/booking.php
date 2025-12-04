<?php
/**
 * Booking Page
 * 
 * @author Maitiú Ellis
 * @description Booking system page for users to make reservations
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
$pageTitle = 'Booking - RZA';

// Handle form submission
if (isRequestMethod('POST')) {
    // Process booking submission
    $bookingData = [
        'name' => getPost('name'),
        'email' => getPost('email'),
        'date' => getPost('date'),
        'time' => getPost('time'),
    ];
    
    // TODO: Add validation here
    // TODO: Add database insertion here
}

// Include header template
require_once ROOT_DIR . '/templates/header.php';
?>

<!-- Booking Page Content -->
<h1>Make a Booking</h1>

<form method="POST" action="booking.php">
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
    </div>

    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div>
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required>
    </div>

    <div>
        <label for="time">Time:</label>
        <input type="time" id="time" name="time" required>
    </div>

    <button type="submit">Submit Booking</button>
</form>

<?php 
// Include footer template
require_once ROOT_DIR . '/templates/footer.php'; 
?>
