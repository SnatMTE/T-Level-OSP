<?php
/**
 * Admin contacts review page.
 *
 * Shows messages submitted via the contact form for admin review.
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

$dir = ROOT_DIR . '/test';
$handledDir = $dir . '/handled';
if (!is_dir($dir)) { mkdir($dir, 0755, true); }
if (!is_dir($handledDir)) { mkdir($handledDir, 0755, true); }

$message = null;
// Handle POST actions: mark as handled or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $file = isset($_POST['file']) ? basename($_POST['file']) : '';
    // sanitize: only allow contact_*.txt filenames
    if ($file && preg_match('/^contact_[A-Za-z0-9_\-\.]+\.txt$/', $file)) {
        $full = $dir . '/' . $file;
        if ($action === 'mark_handled' && file_exists($full)) {
            rename($full, $handledDir . '/' . $file);
            $message = 'Marked as handled: ' . htmlspecialchars($file);
        } elseif ($action === 'delete' && file_exists($full)) {
            unlink($full);
            $message = 'Deleted: ' . htmlspecialchars($file);
        }
    }
}

$files = glob($dir . '/contact_*.txt');
usort($files, function($a, $b){ return filemtime($b) - filemtime($a); });

require_once ROOT_DIR . '/templates/header.php';
?>
<h2>Contact messages</h2>
<?php if ($message): ?><div class="notice"><?php echo $message; ?></div><?php endif; ?>
<?php if (empty($files)): ?>
  <p>No contact messages found.</p>
<?php else: ?>
  <table>
    <thead>
      <tr><th>File</th><th>From</th><th>Subject</th><th>Received</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($files as $f): ?>
      <?php
        $name = basename($f);
        $contents = file_get_contents($f);
        // parse our payload format
        // From: NAME <email>
        // Subject: ...
        // Message:\n\n...
        $from = '';
        $subject = '';
        $received = filemtime($f);
        if (preg_match('/From:\s*(.*?)\s*<([^>]+)>/s', $contents, $m)) { $from = trim($m[1] . ' <' . $m[2] . '>'); }
        if (preg_match('/Subject:\s*(.*)/', $contents, $m2)) { $subject = trim($m2[1]); }
        if (preg_match('/Received:\s*(.*)/', $contents, $m3)) { $received = trim($m3[1]); }
      ?>
      <tr>
        <td><?php echo htmlspecialchars($name); ?></td>
        <td><?php echo htmlspecialchars($from); ?></td>
        <td><?php echo htmlspecialchars($subject); ?></td>
        <td><?php echo htmlspecialchars($received); ?></td>
        <td>
          <form method="post" style="display:inline">
            <input type="hidden" name="file" value="<?php echo htmlspecialchars($name); ?>">
            <button type="submit" name="action" value="view">View</button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Mark handled?');">
            <input type="hidden" name="file" value="<?php echo htmlspecialchars($name); ?>">
            <button type="submit" name="action" value="mark_handled">Mark handled</button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete message?');">
            <input type="hidden" name="file" value="<?php echo htmlspecialchars($name); ?>">
            <button type="submit" name="action" value="delete">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <hr>
  <h3>Preview</h3>
  <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view' && !empty($_POST['file'])) {
        $file = basename($_POST['file']);
        $full = $dir . '/' . $file;
        if (file_exists($full)) {
            echo '<pre>' . htmlspecialchars(file_get_contents($full)) . '</pre>';
        } else {
            echo '<p>File not found.</p>';
        }
    }
  ?>
<?php endif; ?>

<?php require_once ROOT_DIR . '/templates/footer.php'; ?>
