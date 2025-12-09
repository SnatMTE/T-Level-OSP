<?php
/**
 * My Bookings - shows logged-in user's bookings.
 *
 * Requires a logged-in user, then queries their bookings and renders a
 * list of booking entries with their status and options to cancel.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();

if (!defined('ROOT_DIR')) { define('ROOT_DIR', __DIR__); }
require_once ROOT_DIR . '/functions/php/auth.php';
require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/php/helpers.php';

if (!is_logged_in()) {
    // Simple message; you might want to redirect to login instead
    $pageTitle = 'My Bookings - Riget Zoo Adventures (Sign in to view)';
    require_once ROOT_DIR . '/templates/header.php';
    echo '<h2>Please sign in to view your bookings</h2>';
    echo '<p><a href="' . (defined('BASE_URL') && BASE_URL ? BASE_URL : '') . '/signup.php">Sign up</a> or sign in in the header to view your bookings.</p>';
    require_once ROOT_DIR . '/templates/footer.php';
    exit;
}

$u = current_user();
$email = $u['email'];
$uid = isset($u['id']) ? $u['id'] : null;

$pdo = Database::pdo();
if ($uid) {
  // include either bookings tied to this user_id OR older bookings tied by email
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = ? OR (user_id IS NULL AND email = ?) ORDER BY created_at DESC');
  $stmt->execute([$uid, $email]);
} else {
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE email = ? ORDER BY created_at DESC');
  $stmt->execute([$email]);
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Bookings - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>My Bookings</h2>
<p>Bookings for: <strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['surname'], ENT_QUOTES, 'UTF-8'); ?></strong> &lt;<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>&gt;</p>

<?php if (empty($rows)): ?>
  <p>You have no bookings yet.</p>
<?php else: ?>
  <table class="card" style="width:100%;border-collapse:collapse">
    <thead>
      <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Status</th>
        <th>Details</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['id']); ?></td>
        <td><?php echo htmlspecialchars($r['type']); ?></td>
        <td><?php echo htmlspecialchars(isset($r['status']) ? $r['status'] : 'active'); ?></td>
        <td>
          <?php if ($r['type'] === 'hotel'): ?>
            <strong>Check-in:</strong> <?php echo htmlspecialchars($r['checkin']); ?> &nbsp; <strong>Nights:</strong> <?php echo htmlspecialchars($r['nights']); ?> &nbsp; <strong>Room:</strong> <?php echo htmlspecialchars($r['room']); ?>
            <?php if (!empty($r['total_price']) || !empty($r['unit_price'])): ?>
              <div><strong>Unit:</strong> <?php echo htmlspecialchars(format_money($r['unit_price'])); ?> • <strong>Total:</strong> <?php echo htmlspecialchars(format_money($r['total_price'])); ?></div>
            <?php endif; ?>
            <?php if (!empty($r['loyalty_tier'])): ?>
              <div><strong>Loyalty:</strong> <?php echo htmlspecialchars($r['loyalty_tier']); ?>; <?php if (!empty($r['loyalty_discount_pct'])) echo 'Discount: ' . htmlspecialchars(number_format($r['loyalty_discount_pct'],2)) . '%;'; ?> <?php if (!empty($r['loyalty_perks'])) echo 'Perks: ' . htmlspecialchars($r['loyalty_perks']); ?></div>
            <?php endif; ?>
          <?php else: ?>
            <strong>Date:</strong> <?php echo htmlspecialchars($r['ticket_date']); ?> &nbsp; <strong>Tickets:</strong> <?php echo htmlspecialchars($r['tickets']); ?>
            <?php if (!empty($r['total_price']) || !empty($r['unit_price'])): ?>
              <div><strong>Unit:</strong> <?php echo htmlspecialchars(format_money($r['unit_price'])); ?> • <strong>Total:</strong> <?php echo htmlspecialchars(format_money($r['total_price'])); ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($r['created_at']); ?></td>
        <td>
          <?php if ((isset($r['status']) ? $r['status'] : 'active') !== 'cancelled'): ?>
            <form method="post" action="<?php echo BASE_URL ?: '/'; ?>/functions/php/cancel-booking.php" onsubmit="return confirm('Cancel this booking?');">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id']); ?>">
              <button type="submit" class="btn secondary">Cancel booking</button>
            </form>
          <?php else: ?>
            Cancelled at: <?php echo htmlspecialchars($r['cancelled_at']); ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php
require_once ROOT_DIR . '/templates/footer.php';

?>
