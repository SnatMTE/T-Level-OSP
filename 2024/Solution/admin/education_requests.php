<?php
/**
 * Admin: list education tour requests.
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(__DIR__)); }
require_once ROOT_DIR . '/functions/php/auth.php';
require_once ROOT_DIR . '/functions/php/helpers.php';
require_once ROOT_DIR . '/db/Database.php';

if (!is_logged_in() || empty(current_user()['is_admin'])) {
    http_response_code(403);
    require_once ROOT_DIR . '/templates/header.php';
    echo '<h2>Forbidden</h2><p>You must be an admin to view this page.</p>';
    require_once ROOT_DIR . '/templates/footer.php';
    exit;
}

// Handle optional actions via POST (mark as resolved/needs followup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $pdo = Database::pdo();
    if ($action === 'mark_resolved') {
        $upd = $pdo->prepare('UPDATE education_requests SET status = ?, meta = json_insert(COALESCE(meta,\'{}\'), \"$.admin_resolved_by\", ?) WHERE id = ?');
        $upd->execute(['resolved', current_user()['id'], $id]);
        add_flash('success','Request marked resolved');
    } elseif ($action === 'mark_followup') {
        $upd = $pdo->prepare('UPDATE education_requests SET status = ?, meta = json_insert(COALESCE(meta,\'{}\'), \"$.admin_followup_by\", ?) WHERE id = ?');
        $upd->execute(['followup', current_user()['id'], $id]);
        add_flash('success','Request marked for follow-up');
    }
    header('Location: ' . (defined('BASE_URL') && BASE_URL ? BASE_URL : '') . '/admin/education_requests.php');
    exit;
}

$pdo = Database::pdo();
$rows = $pdo->query('SELECT * FROM education_requests ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Admin - Education Requests';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>Education Requests</h2>
<table class="card" style="width:100%">
    <thead>
        <tr><th>ID</th><th>School</th><th>Contact</th><th>Email</th><th>Date</th><th>Group</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['id']); ?></td>
            <td><?php echo htmlspecialchars($r['school']); ?></td>
            <td><?php echo htmlspecialchars($r['contact']); ?></td>
            <td><?php echo htmlspecialchars($r['email']); ?></td>
            <td><?php echo htmlspecialchars($r['date']); ?></td>
            <td><?php echo htmlspecialchars($r['group_size']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
            <td>
                <form method="post" style="display:inline">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id']); ?>">
                    <button class="btn" name="action" value="mark_resolved">Resolve</button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($r['id']); ?>">
                    <button class="btn secondary" name="action" value="mark_followup">Mark Follow-up</button>
                </form>
            </td>
        </tr>
        <tr><td colspan="9"><small><?php echo nl2br(htmlspecialchars($r['notes'])); ?></small></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
require_once ROOT_DIR . '/templates/footer.php';
