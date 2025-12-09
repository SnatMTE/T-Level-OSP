<?php
/**
 * Dev-only helper to inspect the bookings table.
 *
 * Renders a compact HTML table of recent bookings; useful for development
 * and debugging. Not intended for production use.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(dirname(__DIR__)) ); }
require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/php/helpers.php';

try {
    $pdo = Database::pdo();
    $stmt = $pdo->query('SELECT * FROM bookings ORDER BY id DESC LIMIT 10');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo '<pre>Error connecting to DB:' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}
?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Bookings — Debug</title>
<style>table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:6px}</style></head>
<body>
  <h2>Recent bookings (dev only)</h2>
  <table>
    <thead><tr><th>ID</th><th>Type</th><th>User ID</th><th>Name</th><th>Email</th><th>Checkin</th><th>Ticket date</th><th>Tickets</th><th>Unit</th><th>Total</th><th>Status</th><th>Loyalty Tier</th><th>Loyalty Discount %</th><th>Loyalty Discount Amt</th><th>Loyalty Perks</th><th>Cancelled at</th><th>Created at</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['id']); ?></td>
        <td><?php echo htmlspecialchars($r['type']); ?></td>
        <td><?php echo htmlspecialchars($r['user_id']); ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td><?php echo htmlspecialchars($r['email']); ?></td>
        <td><?php echo htmlspecialchars($r['checkin']); ?></td>
        <td><?php echo htmlspecialchars($r['ticket_date']); ?></td>
        <td><?php echo htmlspecialchars($r['tickets']); ?></td>
        <td><?php echo isset($r['unit_price']) ? htmlspecialchars(format_money($r['unit_price'])) : ''; ?></td>
        <td><?php echo isset($r['total_price']) ? htmlspecialchars(format_money($r['total_price'])) : ''; ?></td>
        <td><?php echo htmlspecialchars($r['status']); ?></td>
        <td><?php echo htmlspecialchars($r['loyalty_tier']); ?></td>
        <td><?php echo htmlspecialchars($r['loyalty_discount_pct']); ?></td>
        <td><?php echo htmlspecialchars($r['loyalty_discount_amount']); ?></td>
        <td><?php echo htmlspecialchars($r['loyalty_perks']); ?></td>
        <td><?php echo htmlspecialchars($r['cancelled_at']); ?></td>
        <td><?php echo htmlspecialchars($r['created_at']); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
