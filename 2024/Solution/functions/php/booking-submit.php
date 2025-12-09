<?php
/**
 * Booking submission handler.
 *
 * Handles hotel and ticket submissions (ticket_date vs checkin/nights).
 * Validates input, computes pricing, applies loyalty discounts, and inserts
 * into the bookings table.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();

if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(dirname(__DIR__))); }
require_once ROOT_DIR . '/functions/php/helpers.php';
// Respect BASE_URL if site runs from a subpath; fallback to empty string
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';
require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/php/helpers.php';

/**
 * Sanitize a string value for safe insertion/output.
 *
 * @param mixed $v Input value
 * @return string Sanitized string
 */
function sanitize($v){ return trim(htmlspecialchars((string)$v)); }

    if (empty($_POST)) {
    add_flash('error', 'No POST data received for booking.');
    header('Location: ' . $redirectBase . '/booking.php');
    exit;
}

$type = sanitize(isset($_POST['type']) ? $_POST['type'] : '');
$name = sanitize(isset($_POST['name']) ? $_POST['name'] : '');
$email = sanitize(isset($_POST['email']) ? $_POST['email'] : '');
// If the user is logged in and the form omits name/email, use session values
$u = function_exists('current_user') ? current_user() : null;
// If the user is logged in, override any POSTed name/email with session values
if ($u) {
    $name = trim((isset($u['first_name']) ? $u['first_name'] : '') . ' ' . (isset($u['surname']) ? $u['surname'] : ''));
    $email = isset($u['email']) ? $u['email'] : '';
} else {
    // Accept POSTed values for unauthenticated users
    if (empty($name)) { $name = sanitize(isset($_POST['name']) ? $_POST['name'] : ''); }
    if (empty($email)) { $email = sanitize(isset($_POST['email']) ? $_POST['email'] : ''); }
}
$created_at = date('c');

$meta = [];
// determine user id from session (if logged in)
$user_id = isset($u['id']) ? $u['id'] : null;

try {
    $pdo = Database::pdo();
    $errors = [];

    if ($type === 'hotel') {
        $checkin = sanitize(isset($_POST['checkin']) ? $_POST['checkin'] : '');
        $nights = intval(isset($_POST['nights']) ? $_POST['nights'] : 1);
        if ($nights < 1) $errors[] = 'Nights must be at least 1';
        if (!validate_date($checkin)) $errors[] = 'Check-in date must be a valid YYYY-MM-DD date';
        $room = sanitize(isset($_POST['room']) ? $_POST['room'] : '');

        // Determine room pricing (read from settings)
        $roomPrices = [
            'single' => get_price('hotel_single', 50.0),
            'double' => get_price('hotel_double', 90.0),
            'suite' => get_price('hotel_suite', 150.0)
        ];
        $unit_price = isset($roomPrices[$room]) ? $roomPrices[$room] : $roomPrices['single'];
        if (!in_array($room, ['single','double','suite'])) $errors[] = 'Invalid room type selected';
        $total_price = $unit_price * $nights;
        if ($unit_price <= 0) $errors[] = 'Configured room price is invalid';
        $stmt = $pdo->prepare('INSERT INTO bookings (type,user_id,name,email,checkin,nights,room,unit_price,total_price,meta,created_at,status,cancelled_at,loyalty_tier,loyalty_discount_pct,loyalty_discount_amount,loyalty_perks) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $metaJson = json_encode($meta);
        // Validate guest name/email for unauthenticated users
        if (!$u) {
            if (empty($name)) $errors[] = 'Name is required';
            if (empty($email) || !is_valid_email($email)) $errors[] = 'A valid email is required';
        }
        // Loyalty checks - determine if user qualifies
        $loyalty_tier = '';
        $loyalty_discount_pct = 0.0;
        $loyalty_discount_amount = 0.0;
        $loyalty_perks = '';
        try {
            if ($user_id || $email) {
                $q = $pdo->prepare('SELECT created_at FROM bookings WHERE (user_id = ? OR (user_id IS NULL AND email = ?)) AND status = ? ORDER BY created_at DESC LIMIT 1');
                $q->execute([$user_id, $email, 'active']);
                $last = $q->fetch(PDO::FETCH_ASSOC);
                if ($last && !empty($last['created_at'])) {
                    $lastDt = new DateTime($last['created_at']);
                    $now = new DateTime($created_at);
                    $diff = $now->diff($lastDt);
                    $days = (int)$diff->format('%a');
                    // Loyalty tiers: within 6 months (<=180 days), within 12 months (<=365), within 24 months (<=730)
                    if ($days <= 180) {
                        $loyalty_tier = '6m';
                        $loyalty_discount_pct = floatval(Database::getSetting('loyalty_6m_discount_pct', '10.0'));
                    } elseif ($days <= 365) {
                        $loyalty_tier = '12m';
                        $loyalty_discount_pct = floatval(Database::getSetting('loyalty_12m_discount_pct', '5.0'));
                        $loyalty_perks = Database::getSetting('loyalty_12m_perk', 'Free breakfast for hotel bookings');
                    } elseif ($days <= 730) {
                        $loyalty_tier = '24m';
                        $loyalty_discount_pct = floatval(Database::getSetting('loyalty_24m_discount_pct', '2.0'));
                        $loyalty_perks = Database::getSetting('loyalty_24m_perk', 'Free breakfast and priority parking');
                    }
                }
            }
        } catch (Exception $e) { /* ignore loyalty errors */ }
        // apply discount if any
        if ($loyalty_discount_pct > 0.0) {
            $loyalty_discount_amount = round($total_price * ($loyalty_discount_pct/100.0), 2);
            $total_price = round($total_price - $loyalty_discount_amount, 2);
            // also reduce unit price proportionally
            if ($nights > 0) {
                $unit_price = round($unit_price - ($loyalty_discount_amount / $nights), 2);
            }
        }
        $stmt->execute([$type,$user_id,$name,$email,$checkin,$nights,$room,$unit_price,$total_price,$metaJson,$created_at,'active',null,$loyalty_tier,$loyalty_discount_pct,$loyalty_discount_amount,$loyalty_perks]);
            if (!empty($errors)) {
            add_flash('error', 'Booking errors: ' . implode(' | ', array_map('htmlspecialchars', $errors)));
            header('Location: ' . $redirectBase . '/booking-hotel.php');
            exit;
        }
        $id = $pdo->lastInsertId();

        add_flash('success', 'Hotel booking received — Booking ID: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8'));
        header('Location: ' . $redirectBase . '/booking-hotel.php');
        exit;
        echo '<ul>';
        echo '<li>Name: ' . htmlspecialchars($name) . '</li>';
        echo '<li>Email: ' . htmlspecialchars($email) . '</li>';
        echo '<li>Check-in: ' . htmlspecialchars($checkin) . '</li>';
        echo '<li>Nights: ' . htmlspecialchars($nights) . '</li>';
        echo '<li>Room: ' . htmlspecialchars($room) . '</li>';
        echo '<li>Unit price: ' . htmlspecialchars(format_money($unit_price)) . '</li>';
        echo '<li>Total price: ' . htmlspecialchars(format_money($total_price)) . '</li>';
        if (!empty($loyalty_tier)) {
            echo '<li>Loyalty tier: ' . htmlspecialchars($loyalty_tier) . '</li>';
            if (!empty($loyalty_discount_pct)) echo '<li>Discount: ' . htmlspecialchars(number_format($loyalty_discount_pct,2)) . '% (-' . htmlspecialchars(format_money($loyalty_discount_amount)) . ')</li>';
            if (!empty($loyalty_perks)) echo '<li>Perks: ' . htmlspecialchars($loyalty_perks) . '</li>';
        }
        echo '</ul>';
        echo '<p>We will contact you to confirm the reservation.</p>';
        echo '</main>';

    } elseif ($type === 'tickets') {
        $ticket_date = sanitize(isset($_POST['ticket_date']) ? $_POST['ticket_date'] : '');
        $tickets = intval(isset($_POST['tickets']) ? $_POST['tickets'] : 1);
        if ($tickets < 1) $errors[] = 'Number of tickets must be at least 1';
        if (!validate_date($ticket_date)) $errors[] = 'Ticket date must be a valid YYYY-MM-DD date';

        // Ticket pricing (read from settings)
        $unit_price = get_price('ticket_price', 10.0);
        $total_price = $unit_price * $tickets;
        if ($unit_price <= 0) $errors[] = 'Configured ticket price is invalid';
        $stmt = $pdo->prepare('INSERT INTO bookings (type,user_id,name,email,ticket_date,tickets,unit_price,total_price,meta,created_at,status,cancelled_at,loyalty_tier,loyalty_discount_pct,loyalty_discount_amount,loyalty_perks) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $metaJson = json_encode($meta);
        if (!$u) {
            if (empty($name)) $errors[] = 'Name is required';
            if (empty($email) || !is_valid_email($email)) $errors[] = 'A valid email is required';
        }
        // Loyalty checks - determine if user qualifies (tickets aggregated by ticket_date but loyalty based on last booking)
        $loyalty_tier = '';
        $loyalty_discount_pct = 0.0;
        $loyalty_discount_amount = 0.0;
        $loyalty_perks = '';
        try {
            if ($user_id || $email) {
                $q = $pdo->prepare('SELECT created_at FROM bookings WHERE (user_id = ? OR (user_id IS NULL AND email = ?)) AND status = ? ORDER BY created_at DESC LIMIT 1');
                $q->execute([$user_id, $email, 'active']);
                $last = $q->fetch(PDO::FETCH_ASSOC);
                if ($last && !empty($last['created_at'])) {
                    $lastDt = new DateTime($last['created_at']);
                    $now = new DateTime($created_at);
                    $diff = $now->diff($lastDt);
                    $days = (int)$diff->format('%a');
                    if ($days <= 180) {
                        $loyalty_tier = '6m';
                        $loyalty_discount_pct = floatval(Database::getSetting('loyalty_6m_discount_pct', '10.0'));
                    } elseif ($days <= 365) {
                        $loyalty_tier = '12m';
                        $loyalty_discount_pct = floatval(Database::getSetting('loyalty_12m_discount_pct', '5.0'));
                        $loyalty_perks = Database::getSetting('loyalty_12m_perk', '');
                    } elseif ($days <= 730) {
                        $loyalty_tier = '24m';
                        $loyalty_discount_pct = floatval(Database::getSetting('loyalty_24m_discount_pct', '2.0'));
                        $loyalty_perks = Database::getSetting('loyalty_24m_perk', '');
                    }
                }
            }
        } catch (Exception $e) {}
        if ($loyalty_discount_pct > 0.0) {
            $loyalty_discount_amount = round($total_price * ($loyalty_discount_pct/100.0), 2);
            $total_price = round($total_price - $loyalty_discount_amount, 2);
            // adjust unit price per ticket
            if ($tickets > 0) {
                $unit_price = round($unit_price - ($loyalty_discount_amount / $tickets), 2);
            }
        }
        $stmt->execute([$type,$user_id,$name,$email,$ticket_date,$tickets,$unit_price,$total_price,$metaJson,$created_at,'active',null,$loyalty_tier,$loyalty_discount_pct,$loyalty_discount_amount,$loyalty_perks]);
        if (!empty($errors)) {
            add_flash('error', 'Booking errors: ' . implode(' | ', array_map('htmlspecialchars', $errors)));
            header('Location: ' . $redirectBase . '/booking-tickets.php');
            exit;
        }
        $id = $pdo->lastInsertId();

        add_flash('success', 'Ticket purchase received — Order ID: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8'));
        header('Location: ' . $redirectBase . '/booking-tickets.php');
        exit;
        echo '<ul>';
        echo '<li>Name: ' . htmlspecialchars($name) . '</li>';
        echo '<li>Email: ' . htmlspecialchars($email) . '</li>';
        echo '<li>Date: ' . htmlspecialchars($ticket_date) . '</li>';
        echo '<li>Tickets: ' . htmlspecialchars($tickets) . '</li>';
        echo '<li>Unit price: ' . htmlspecialchars(format_money($unit_price)) . '</li>';
        echo '<li>Total price: ' . htmlspecialchars(format_money($total_price)) . '</li>';
        if (!empty($loyalty_tier)) {
            echo '<li>Loyalty tier: ' . htmlspecialchars($loyalty_tier) . '</li>';
            if (!empty($loyalty_discount_pct)) echo '<li>Discount: ' . htmlspecialchars(number_format($loyalty_discount_pct,2)) . '% (-' . htmlspecialchars(format_money($loyalty_discount_amount)) . ')</li>';
            if (!empty($loyalty_perks)) echo '<li>Perks: ' . htmlspecialchars($loyalty_perks) . '</li>';
        }
        echo '</ul>';
        echo '<p>We will email your tickets to the address provided.</p>';
        echo '</main>';

    } else {
        add_flash('error', 'Unknown booking type.');
        header('Location: ' . $redirectBase . '/booking.php');
        exit;
    }

} catch (Exception $e) {
    add_flash('error', 'Server error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    header('Location: ' . $redirectBase . '/booking.php');
    exit;
}

require_once ROOT_DIR . '/templates/footer.php';
?>
<!-- 2025-12-03 12:00 - Add booking CRUD handlers (skeleton) - author: Snat -->

