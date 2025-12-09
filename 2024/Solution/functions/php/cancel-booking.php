<?php
/**
 * Cancel a booking.
 *
 * Expects a POST with id parameter and will mark the booking cancelled if
 * the current user is allowed to cancel it (owner or admin).
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();

if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(dirname(__DIR__))); }
require_once ROOT_DIR . '/functions/php/auth.php';
require_once ROOT_DIR . '/functions/php/helpers.php';
require_once ROOT_DIR . '/db/Database.php';
// Respect BASE_URL for redirects
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    add_flash('error', 'Invalid request method.');
    header('Location: ' . $redirectBase . '/admin/bookings.php');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    add_flash('error', 'Bad request: missing booking id.');
    header('Location: ' . $redirectBase . '/admin/bookings.php');
    exit;
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$booking) {
    add_flash('error', 'Booking not found.');
    header('Location: ' . $redirectBase . '/admin/bookings.php');
    exit;
}

$u = function_exists('current_user') ? current_user() : null;
if (!$u) {
    // Not logged in — disallow cancel
    add_flash('error', 'You must be logged in to cancel bookings.');
    header('Location: ' . $redirectBase . '/admin/bookings.php');
    exit;
}

// Admins may cancel any booking
$allowed = false;
if (!empty($u['is_admin'])) {
    $allowed = true;
} else {
    // If booking has user_id, compare to session id. Otherwise compare email
    if (!empty($booking['user_id'])) {
        if ($booking['user_id'] == $u['id']) { $allowed = true; }
    } else {
        if (!empty($booking['email']) && $booking['email'] === $u['email']) { $allowed = true; }
    }
}

if (!$allowed) {
    add_flash('error', 'Not allowed to cancel this booking.');
    header('Location: ' . $redirectBase . '/admin/bookings.php');
    exit;
}

$cancelled_at = date('c');
$update = $pdo->prepare('UPDATE bookings SET status = ?, cancelled_at = ? WHERE id = ?');
$update->execute(['cancelled', $cancelled_at, $id]);
add_flash('success', 'Booking cancelled.');
    header('Location: ' . $redirectBase . '/my-bookings.php');
exit;

?>
