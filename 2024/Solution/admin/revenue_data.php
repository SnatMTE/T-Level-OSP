<?php
/**
 * Admin JSON endpoint for revenue data.
 *
 * Returns revenue summary used by the admin charts in JSON format.
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
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$pdo = Database::pdo();

// Last N days (default 30); optional GET `days` parameter
$days = 30;
if (isset($_GET['days']) && intval($_GET['days']) > 0) { $days = intval($_GET['days']); }
// Cap days to 365 to avoid very large queries
if ($days > 365) { http_response_code(400); header('Content-Type: application/json'); echo json_encode(['error' => 'Days parameter too large']); exit; }

$end = new DateTime();
$start = clone $end;
$start->sub(new DateInterval('P' . $days . 'D'));

$startStr = $start->format('Y-m-d') . ' 00:00:00';
$endStr = $end->format('Y-m-d') . ' 23:59:59';

// Prepare bins for each day
$period = new DatePeriod(new DateTime($startStr), new DateInterval('P1D'), (new DateTime($endStr))->modify('+1 day'));
$labels = [];
foreach ($period as $d) { $labels[] = $d->format('Y-m-d'); }

// Query groups by created_at date
// Get revenue & bookings aggregated by created_at date
$sql = "SELECT date(created_at) AS day, SUM(COALESCE(total_price,0)) AS revenue, COUNT(*) AS bookings_count FROM bookings WHERE created_at >= ? AND created_at <= ? GROUP BY day ORDER BY day ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$startStr, $endStr]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$map = [];
foreach ($rows as $r) {
    $map[$r['day']] = [
        'revenue' => floatval($r['revenue']),
        'tickets' => 0,
        'bookings' => intval($r['bookings_count'])
    ];
}

// Get tickets by ticket_date rather than created_at (shows per-day visits)
$sql2 = "SELECT date(ticket_date) AS day, SUM(COALESCE(tickets,0)) as tickets_sold FROM bookings WHERE type = 'tickets' AND ticket_date IS NOT NULL AND ticket_date >= ? AND ticket_date <= ? GROUP BY day ORDER BY day ASC";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
$ticketRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($ticketRows as $tr) {
    if (!isset($map[$tr['day']])) { $map[$tr['day']] = [ 'revenue' => 0.0, 'tickets' => 0, 'bookings' => 0 ]; }
    $map[$tr['day']]['tickets'] = intval($tr['tickets_sold']);
}

// fill response arrays in order of labels
$revenue = [];
$tickets = [];
$bookings = [];
foreach ($labels as $l) {
    if (isset($map[$l])) {
        $revenue[] = $map[$l]['revenue'];
        $tickets[] = $map[$l]['tickets'];
        $bookings[] = $map[$l]['bookings'];
    } else {
        $revenue[] = 0.0;
        $tickets[] = 0;
        $bookings[] = 0;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([ 'labels' => $labels, 'revenue' => $revenue, 'tickets' => $tickets, 'bookings' => $bookings ]);
exit;
?>
