<?php
/**
 * Admin export bookings CSV endpoint.
 *
 * Exports bookings to CSV for reporting and offline analysis.
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
    echo 'Forbidden: admin only';
    exit;
}

$pdo = Database::pdo();

// Optional filters via GET
$filters = [];
$params = [];
// Validate inputs
$errors = [];
if (!empty($_GET['type'])) { $filters[] = 'type = ?'; $params[] = $_GET['type']; }
if (!empty($_GET['status'])) { $filters[] = 'status = ?'; $params[] = $_GET['status']; }
if (!empty($_GET['start'])) { $filters[] = "created_at >= ?"; $params[] = $_GET['start']; }
if (!empty($_GET['end'])) { $filters[] = "created_at <= ?"; $params[] = $_GET['end']; }
// stricter checks
if (!empty($_GET['type']) && !in_array($_GET['type'], ['hotel','tickets'])) $errors[] = 'Invalid type filter';
if (!empty($_GET['status']) && !in_array($_GET['status'], ['active','cancelled'])) $errors[] = 'Invalid status filter';
if (!empty($_GET['start']) && !validate_date($_GET['start'])) $errors[] = 'Invalid start date';
if (!empty($_GET['end']) && !validate_date($_GET['end'])) $errors[] = 'Invalid end date';
if (!empty($errors)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo implode("\n", $errors);
    exit;
}

$where = '';
if (!empty($filters)) { $where = 'WHERE ' . implode(' AND ', $filters); }

$sql = 'SELECT id,type,user_id,name,email,checkin,nights,room,ticket_date,tickets,unit_price,total_price,loyalty_tier,loyalty_discount_pct,loyalty_discount_amount,loyalty_perks,meta,status,cancelled_at,created_at FROM bookings ' . $where . ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build CSV filename and headers
$filename = 'bookings_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// CSV header
fputcsv($out, ['id','type','user_id','name','email','checkin','nights','room','ticket_date','tickets','unit_price','total_price','loyalty_tier','loyalty_discount_pct','loyalty_discount_amount','loyalty_perks','status','cancelled_at','created_at']);

foreach ($rows as $r) {
    // format numeric prices as 2 decimal values, but keep numeric for CSV
    $unit = isset($r['unit_price']) ? number_format(floatval($r['unit_price']), 2, '.', '') : '';
    $total = isset($r['total_price']) ? number_format(floatval($r['total_price']), 2, '.', '') : '';
    fputcsv($out, [
        $r['id'], $r['type'], $r['user_id'], $r['name'], $r['email'], $r['checkin'], $r['nights'], $r['room'], $r['ticket_date'], $r['tickets'], $unit, $total, $r['loyalty_tier'], $r['loyalty_discount_pct'], $r['loyalty_discount_amount'], $r['loyalty_perks'], $r['status'], $r['cancelled_at'], $r['created_at']
    ]);
}

fclose($out);
exit;

?>
