<?php
/**
 * Admin: settings page - pricing and options.
 *
 * Provides a form to edit settings such as ticket and hotel prices and other
 * configurable values stored in the settings table.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
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

$pageTitle = 'Admin - Settings';
require_once ROOT_DIR . '/templates/header.php';

$pdo = Database::pdo();

function saveSetting($key, $value) {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?,?)');
    $stmt->execute([$key, (string)$value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_price = floatval(isset($_POST['ticket_price']) ? $_POST['ticket_price'] : 10.0);
    $hotel_single = floatval(isset($_POST['hotel_single']) ? $_POST['hotel_single'] : 50.0);
    $hotel_double = floatval(isset($_POST['hotel_double']) ? $_POST['hotel_double'] : 90.0);
    $hotel_suite = floatval(isset($_POST['hotel_suite']) ? $_POST['hotel_suite'] : 150.0);
    $errors = [];
    if ($ticket_price < 0) $errors[] = 'Ticket price must be 0 or more';
    if ($hotel_single < 0) $errors[] = 'Hotel single price must be 0 or more';
    if ($hotel_double < 0) $errors[] = 'Hotel double price must be 0 or more';
    if ($hotel_suite < 0) $errors[] = 'Hotel suite price must be 0 or more';
    if (empty($errors)) {
        saveSetting('hotel_single', $hotel_single);
    }
    
    if (isset($_POST['loyalty_6m_discount_pct']) && (floatval($_POST['loyalty_6m_discount_pct']) < 0 || floatval($_POST['loyalty_6m_discount_pct']) > 100)) $errors[] = '6-month discount must be between 0 and 100';
    if (isset($_POST['loyalty_12m_discount_pct']) && (floatval($_POST['loyalty_12m_discount_pct']) < 0 || floatval($_POST['loyalty_12m_discount_pct']) > 100)) $errors[] = '12-month discount must be between 0 and 100';
    if (isset($_POST['loyalty_24m_discount_pct']) && (floatval($_POST['loyalty_24m_discount_pct']) < 0 || floatval($_POST['loyalty_24m_discount_pct']) > 100)) $errors[] = '24-month discount must be between 0 and 100';
    if (!empty($errors)) {
        http_response_code(400);
        echo '<div class="err"><p>Please fix the following:</p><ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $errors)) . '</li></ul></div>';
    }
    
    // Only save other settings if no errors
    if (empty($errors)) {
        saveSetting('hotel_double', $hotel_double);
        saveSetting('hotel_suite', $hotel_suite);
        saveSetting('ticket_price', $ticket_price);
        // Save loyalty settings if provided
        if (isset($_POST['loyalty_6m_discount_pct'])) saveSetting('loyalty_6m_discount_pct', floatval($_POST['loyalty_6m_discount_pct']));
        if (isset($_POST['loyalty_12m_discount_pct'])) saveSetting('loyalty_12m_discount_pct', floatval($_POST['loyalty_12m_discount_pct']));
        if (isset($_POST['loyalty_24m_discount_pct'])) saveSetting('loyalty_24m_discount_pct', floatval($_POST['loyalty_24m_discount_pct']));
        if (isset($_POST['loyalty_12m_perk'])) saveSetting('loyalty_12m_perk', $_POST['loyalty_12m_perk']);
        if (isset($_POST['loyalty_24m_perk'])) saveSetting('loyalty_24m_perk', $_POST['loyalty_24m_perk']);
        // Map and location settings
        if (isset($_POST['zoo_lat'])) saveSetting('zoo_lat', trim($_POST['zoo_lat']));
        if (isset($_POST['zoo_lon'])) saveSetting('zoo_lon', trim($_POST['zoo_lon']));
        if (isset($_POST['map_use_server_geocode'])) saveSetting('map_use_server_geocode', '1'); else saveSetting('map_use_server_geocode', '0');
        if (isset($_POST['map_use_server_routing'])) saveSetting('map_use_server_routing', '1'); else saveSetting('map_use_server_routing', '0');
        // reCAPTCHA keys
        if (isset($_POST['recaptcha_site_key'])) saveSetting('recaptcha_site_key', trim($_POST['recaptcha_site_key']));
        if (isset($_POST['recaptcha_secret_key'])) saveSetting('recaptcha_secret_key', trim($_POST['recaptcha_secret_key']));
        echo '<div class="notice">Settings saved.</div>';
    }
    
}

$ticket_price = floatval(Database::getSetting('ticket_price', '10.0'));
$hotel_single = floatval(Database::getSetting('hotel_single', '50.0'));
$hotel_double = floatval(Database::getSetting('hotel_double', '90.0'));
$hotel_suite = floatval(Database::getSetting('hotel_suite', '150.0'));
$loyalty_6m_discount_pct = floatval(Database::getSetting('loyalty_6m_discount_pct', '10.0'));
$loyalty_12m_discount_pct = floatval(Database::getSetting('loyalty_12m_discount_pct', '5.0'));
$loyalty_24m_discount_pct = floatval(Database::getSetting('loyalty_24m_discount_pct', '2.0'));
$loyalty_12m_perk = Database::getSetting('loyalty_12m_perk', 'Free breakfast for hotel bookings');
$loyalty_24m_perk = Database::getSetting('loyalty_24m_perk', 'Free breakfast and priority parking');
// reCAPTCHA keys (optional)
$recaptcha_site_key = Database::getSetting('recaptcha_site_key', '');
$recaptcha_secret_key = Database::getSetting('recaptcha_secret_key', '');
// Zoo coordinates & map options
$zoo_lat = Database::getSetting('zoo_lat', '52.6548');
$zoo_lon = Database::getSetting('zoo_lon', '-0.4827');
$map_use_server_geocode = Database::getSetting('map_use_server_geocode', '0');
$map_use_server_routing = Database::getSetting('map_use_server_routing', '0');

?>
<h2>Site Pricing</h2>
<form method="post">
    <label>Ticket price: £<input type="number" step="0.01" min="0" name="ticket_price" value="<?php echo htmlspecialchars(number_format($ticket_price,2,'.','')); ?>"></label><br>
    <label>Hotel - Single: £<input type="number" step="0.01" min="0" name="hotel_single" value="<?php echo htmlspecialchars(number_format($hotel_single,2,'.','')); ?>"></label><br>
    <label>Hotel - Double: £<input type="number" step="0.01" min="0" name="hotel_double" value="<?php echo htmlspecialchars(number_format($hotel_double,2,'.','')); ?>"></label><br>
    <label>Hotel - Suite: £<input type="number" step="0.01" min="0" name="hotel_suite" value="<?php echo htmlspecialchars(number_format($hotel_suite,2,'.','')); ?>"></label><br>
    <h3>Loyalty settings</h3>
    <label>6-month discount (%): <input type="number" step="0.1" min="0" max="100" name="loyalty_6m_discount_pct" value="<?php echo htmlspecialchars(number_format($loyalty_6m_discount_pct,2,'.','')); ?>"></label><br>
    <label>12-month discount (%): <input type="number" step="0.1" min="0" max="100" name="loyalty_12m_discount_pct" value="<?php echo htmlspecialchars(number_format($loyalty_12m_discount_pct,2,'.','')); ?>"></label><br>
    <label>12-month perk (text): <input type="text" name="loyalty_12m_perk" value="<?php echo htmlspecialchars($loyalty_12m_perk); ?>"></label><br>
    <label>24-month discount (%): <input type="number" step="0.1" min="0" max="100" name="loyalty_24m_discount_pct" value="<?php echo htmlspecialchars(number_format($loyalty_24m_discount_pct,2,'.','')); ?>"></label><br>
    <label>24-month perk (text): <input type="text" name="loyalty_24m_perk" value="<?php echo htmlspecialchars($loyalty_24m_perk); ?>"></label><br>
    <button type="submit">Save</button>
</form>
<h3>reCAPTCHA</h3>
<p class="small-muted">Optionally protect the contact form with Google reCAPTCHA v2 (checkbox). Add the site key and secret obtained from the Google reCAPTCHA admin console.</p>
<form method="post">
    <label>Site key: <input type="text" name="recaptcha_site_key" value="<?php echo htmlspecialchars($recaptcha_site_key); ?>"></label><br>
    <label>Secret key: <input type="text" name="recaptcha_secret_key" value="<?php echo htmlspecialchars($recaptcha_secret_key); ?>"></label><br>
    <button type="submit">Save</button>
</form>
<h3>Map</h3>
<p class="small-muted">Configure the location of the zoo and whether the site uses server-side geocoding and routing (recommended for higher reliability).</p>
<form method="post">
    <label>Zoo latitude: <input type="text" name="zoo_lat" value="<?php echo htmlspecialchars($zoo_lat); ?>"></label><br>
    <label>Zoo longitude: <input type="text" name="zoo_lon" value="<?php echo htmlspecialchars($zoo_lon); ?>"></label><br>
    <label>Use server-side geocoding: <input type="checkbox" name="map_use_server_geocode" value="1" <?php echo ($map_use_server_geocode === '1') ? 'checked' : ''; ?>></label><br>
    <label>Use server-side routing: <input type="checkbox" name="map_use_server_routing" value="1" <?php echo ($map_use_server_routing === '1') ? 'checked' : ''; ?>></label><br>
    <button type="submit">Save</button>
</form>

<?php require_once ROOT_DIR . '/templates/footer.php';

?>
