<?php
/**
 * Admin dashboard index page.
 *
 * Provides navigation to admin pages such as users, bookings, revenue and
 * settings. Requires an admin-level user to access.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(__DIR__)); }
require_once ROOT_DIR . '/functions/php/auth.php';
require_once ROOT_DIR . '/functions/php/helpers.php';

if (!is_logged_in() || empty(current_user()['is_admin'])) {
    http_response_code(403);
    require_once ROOT_DIR . '/templates/header.php';
    echo '<h2>Forbidden</h2><p>You must be an admin to view this page.</p>';
    require_once ROOT_DIR . '/templates/footer.php';
    exit;
}

$pageTitle = 'Admin - Dashboard';
require_once ROOT_DIR . '/templates/header.php';
?>

<style>
  .admin-wrap{display:flex;gap:20px;max-width:900px;margin:0 auto}
  .admin-box{flex:1;border:1px solid #ddd;padding:16px;background:#fff;text-align:center}
  /* Removed button-like styling for the links; keep default link appearance */
  .admin-box a{display:inline-block;margin-top:12px;text-decoration:underline;color:inherit}
  .admin-box a:hover{text-decoration:underline}
  .admin-box h3{margin:0 0 8px}
</style>

<h2>Admin Dashboard</h2>
<div class="admin-wrap">
  <div class="admin-box">
  <h3>View Users</h3>
  <p>Open the users list to view and manage user accounts.</p>
  <a href="<?php echo BASE_URL ?: '/'; ?>/admin/users.php">Open Users</a>
  </div>
  <div class="admin-box">
  <h3>View Bookings</h3>
  <p>Open the bookings list to review and manage bookings.</p>
  <a href="<?php echo BASE_URL ?: '/'; ?>/admin/bookings.php">Open Bookings</a>
  </div>
  <div class="admin-box">
  <h3>Revenue</h3>
  <p>View weekly revenue from bookings.</p>
  <a href="<?php echo BASE_URL ?: '/'; ?>/admin/revenue.php">View Revenue</a>
  </div>
  <div class="admin-box">
  <h3>Pricing</h3>
  <p>Update ticket and hotel prices for the site.</p>
  <a href="<?php echo BASE_URL ?: '/'; ?>/admin/settings.php">Edit Pricing</a>
  </div>
  <div class="admin-box">
  <h3>Contacts</h3>
  <p>View messages submitted through the contact form.</p>
  <a href="<?php echo BASE_URL ?: '/'; ?>/admin/contacts.php">View Contacts</a>
  </div>
  <div class="admin-box">
    <h3>Education Requests</h3>
    <p>Review and manage incoming requests for education tours.</p>
    <a href="<?php echo BASE_URL ?: '/'; ?>/admin/education_requests.php">View Requests</a>
  </div>
</div>

<?php require_once ROOT_DIR . '/templates/footer.php'; ?>

<!-- 2025-12-03 11:30 - Add admin area placeholder - author: Snat -->

