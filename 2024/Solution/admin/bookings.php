<?php
/**
 * Admin bookings dashboard page.
 *
 * Displays bookings and administrative actions such as cancellation and
 * an export link.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(__DIR__)); }
require_once ROOT_DIR . '/functions/php/auth.php';
require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/php/helpers.php';

if (!is_logged_in() || empty(current_user()['is_admin'])) {
    http_response_code(403);
    require_once ROOT_DIR . '/templates/header.php';
    echo '<h2>Forbidden</h2><p>You must be an admin to view this page.</p>';
    require_once ROOT_DIR . '/templates/footer.php';
    exit;
}

$pdo = Database::pdo();
$sql = 'SELECT b.*, u.email AS user_email, u.first_name AS user_first, u.surname AS user_surname FROM bookings b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC';
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin - Bookings';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>All Bookings</h2>
<div style="margin-bottom:12px">
  <form method="get" action="/admin/export_bookings.php" style="display:inline-block">
    <label>Type:
      <select name="type">
        <option value="">All</option>
        <option value="tickets">Tickets</option>
        <option value="hotel">Hotel</option>
      </select>
    </label>
    <label>Status:
      <select name="status">
        <option value="">All</option>
        <option value="active">Active</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </label>
    <label>Start: <input type="date" name="start"></label>
    <label>End: <input type="date" name="end"></label>
    <button type="submit">Export CSV</button>
  </form>
</div>
<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>User</th>
      <th>Type</th>
      <th>Details</th>
      <th>Status</th>
      <th>Created</th>
      <th>Actions</th>
      <th>Loyalty</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['id']); ?></td>
        <td>
          <?php if (!empty($r['user_id'])): ?>
            <?php echo htmlspecialchars($r['user_first'] . ' ' . $r['user_surname'] . ' (' . $r['user_email'] . ')'); ?>
          <?php else: ?>
            <?php echo htmlspecialchars($r['name'] . ' (' . $r['email'] . ')'); ?>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($r['type']); ?></td>
        <td>
          <?php if ($r['type'] === 'hotel'): ?>
            <strong>Check-in:</strong> <?php echo htmlspecialchars($r['checkin']); ?> &nbsp; <strong>Nights:</strong> <?php echo htmlspecialchars($r['nights']); ?> &nbsp; <strong>Room:</strong> <?php echo htmlspecialchars($r['room']); ?>
            <?php if (!empty($r['total_price']) || !empty($r['unit_price'])): ?>
              <div><strong>Unit:</strong> <?php echo htmlspecialchars(format_money($r['unit_price'])); ?> • <strong>Total:</strong> <?php echo htmlspecialchars(format_money($r['total_price'])); ?></div>
            <?php endif; ?>
          <?php else: ?>
            <strong>Date:</strong> <?php echo htmlspecialchars($r['ticket_date']); ?> &nbsp; <strong>Tickets:</strong> <?php echo htmlspecialchars($r['tickets']); ?>
            <?php if (!empty($r['total_price']) || !empty($r['unit_price'])): ?>
              <div><strong>Unit:</strong> <?php echo htmlspecialchars(format_money($r['unit_price'])); ?> • <strong>Total:</strong> <?php echo htmlspecialchars(format_money($r['total_price'])); ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars(isset($r['status']) ? $r['status'] : 'active'); ?><?php if (!empty($r['cancelled_at'])) echo ' ('.htmlspecialchars($r['cancelled_at']).')'; ?></td>
        <td><?php echo htmlspecialchars($r['created_at']); ?></td>
        <td>
          <?php if ((isset($r['status']) ? $r['status'] : 'active') !== 'cancelled'): ?>
            <form method="post" action="/functions/php/cancel-booking.php" onsubmit="return confirm('Cancel this booking?');">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id']); ?>">
              <button type="submit">Cancel</button>
            </form>
          <?php else: ?>
            Cancelled
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once ROOT_DIR . '/templates/footer.php'; ?>
