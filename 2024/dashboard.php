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

// Initialize database
Database::initialize();

// Set page title
$pageTitle = 'Dashboard - RZA';

// Require user to be logged in
requireLogin();

// Include header template
require_once ROOT_DIR . '/templates/header.php';
?>

<!-- Dashboard Page Content -->
<h1>Dashboard</h1>

<p>Welcome, <?php echo esc($_SESSION['user_data']['name'] ?? 'User'); ?>!</p>

<?php
// Determine user's email (prefer session user_data, fallback to users table via user_id)
$pdo = Database::getInstance();
$email = $_SESSION['user_data']['email'] ?? null;
if (!$email && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT email, name FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $email = $u['email'];
        if (empty($_SESSION['user_data']['name']) && !empty($u['name'])) {
            $_SESSION['user_data']['name'] = $u['name'];
        }
    }
}

if (!$email) {
    // Nothing to display without an email identifier
    echo '<section><h2>Your Bookings</h2><p>No bookings available for your account.</p></section>';
} else {
    // Fetch hotel bookings
    $stmt = $pdo->prepare('SELECT hb.*, h.name AS hotel_name FROM hotel_bookings hb LEFT JOIN hotels h ON hb.hotel_id = h.id WHERE hb.email = :email ORDER BY hb.created_at DESC');
    $stmt->execute([':email' => $email]);
    $hotelBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch ticket purchases
    $stmt = $pdo->prepare('SELECT tp.*, tt.name AS ticket_name FROM ticket_purchases tp LEFT JOIN ticket_types tt ON tp.ticket_type_id = tt.id WHERE tp.email = :email ORDER BY tp.created_at DESC');
    $stmt->execute([':email' => $email]);
    $ticketPurchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>

    <section>
        <h2>Your Hotel Bookings</h2>
        <?php if (empty($hotelBookings)): ?>
            <p>No hotel bookings found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hotel</th>
                        <th>Rooms</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total</th>
                        <th>Booked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hotelBookings as $hb): ?>
                        <tr>
                            <td><?php echo esc($hb['id']); ?></td>
                            <td><?php echo esc($hb['hotel_name'] ?? '—'); ?></td>
                            <td><?php echo esc($hb['rooms']); ?></td>
                            <td><?php echo esc($hb['check_in']); ?></td>
                            <td><?php echo esc($hb['check_out']); ?></td>
                            <td>£<?php echo esc(number_format($hb['total_price'],2)); ?></td>
                            <td><?php echo esc($hb['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section>
        <h2>Your Ticket Purchases</h2>
        <?php if (empty($ticketPurchases)): ?>
            <p>No ticket purchases found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ticket</th>
                        <th>Date</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Purchased</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ticketPurchases as $tp): ?>
                        <tr>
                            <td><?php echo esc($tp['id']); ?></td>
                            <td><?php echo esc($tp['ticket_name'] ?? '—'); ?></td>
                            <td><?php echo esc($tp['date']); ?></td>
                            <td><?php echo esc($tp['quantity']); ?></td>
                            <td>£<?php echo esc(number_format($tp['total_price'],2)); ?></td>
                            <td><?php echo esc($tp['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <?php
}

?>

<?php 
// Include footer template
require_once ROOT_DIR . '/templates/footer.php'; 
?>