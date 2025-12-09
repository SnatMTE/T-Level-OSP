<?php
/**
 * Generic booking page which directs users to the appropriate booking
 * workflow (hotel vs tickets) and renders the booking form(s).
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
require 'templates/header.php';
?>
<style>
    .booking-options{display:flex;gap:20px;align-items:flex-start}
    .booking-card{flex:1;border:1px solid #ddd;padding:16px;background:#fff}
    .booking-card h3{margin-top:0}
    .booking-actions a{display:inline-block;margin-top:8px}
</style>

<h2>Booking</h2>
<p>You can either book a hotel room for an overnight stay, or just buy tickets to visit the zoo. Choose one of the options below to continue.</p>

<?php $u = function_exists('current_user') ? current_user() : null; $baseUrl = defined('BASE_URL') && BASE_URL ? BASE_URL : '/'; ?>
<?php $redirectTarget = isset($_GET['redirect']) ? $_GET['redirect'] : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/booking.php'); ?>
<?php if (!$u): ?>
    <div class="booking-options auth-cta">
            <div class="card" style="flex:1">
            <h3>New here? Sign up</h3>
            <p>Create an account to make bookings and view your booking history. It only takes a couple of minutes.</p>
            <div>
                <a href="<?php echo htmlspecialchars($baseUrl . '/signup.php?redirect=' . urlencode($redirectTarget), ENT_QUOTES, 'UTF-8'); ?>" class="btn" aria-label="Sign up for an account">Sign Up</a>
            </div>
        </div>
        <div class="card" style="flex:1">
            <h3>Sign in</h3>
            <p>If you're a returning guest, sign in to access booking options and saved information.</p>
            <form method="post" action="/functions/php/login.php" style="display:flex;flex-direction:column;gap:8px">
                <label>Email: <input class="input" type="email" name="email" aria-label="Email address" required></label>
                <label>Password: <input class="input" type="password" name="password" aria-label="Password" required></label>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn">Sign In</button>
            </form>
        </div>
    </div>
<?php else: ?>
<div class="booking-options">
    <div class="booking-card">
        <h3>Book a Hotel</h3>
        <p>Reserve a room for your stay near the zoo. Includes check-in date, number of nights and room type.</p>
        <div class="booking-actions">
            <a href="<?php echo htmlspecialchars($baseUrl . '/booking-hotel.php', ENT_QUOTES, 'UTF-8'); ?>">Book a Hotel</a>
        </div>
    </div>

    <div class="booking-card">
        <h3>Book Tickets</h3>
        <p>Purchase entry tickets for your visit. Choose date and number of tickets.</p>
        <div class="booking-actions">
            <a href="<?php echo htmlspecialchars($baseUrl . '/booking-tickets.php', ENT_QUOTES, 'UTF-8'); ?>">Buy Tickets</a>
        </div>
    </div>
    </div>
<?php endif; ?>
<?php require 'templates/footer.php'; ?>