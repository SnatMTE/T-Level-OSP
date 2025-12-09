<?php
/**
 * Admin: users list page
 *
 * Manage user accounts and view user details.
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

if (!is_logged_in() || !current_user()['is_admin']) {
    http_response_code(403);
    require_once ROOT_DIR . '/templates/header.php';
    echo '<h2>Forbidden</h2><p>You must be an admin to view this page.</p>';
    require_once ROOT_DIR . '/templates/footer.php';
    exit;
}

$pdo = Database::pdo();
$stmt = $pdo->query('SELECT id,email,first_name,surname,is_admin,created_at FROM users ORDER BY id DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin - Users';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>Users</h2>
<table>
  <thead><tr><th>ID</th><th>Email</th><th>Name</th><th>Admin</th><th>Created</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?php echo htmlspecialchars($r['id']); ?></td>
      <td><?php echo htmlspecialchars($r['email']); ?></td>
      <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['surname']); ?></td>
      <td><?php echo $r['is_admin'] ? 'Yes' : 'No'; ?></td>
      <td><?php echo htmlspecialchars($r['created_at']); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php require_once ROOT_DIR . '/templates/footer.php'; ?>
