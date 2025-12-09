<?php
/**
 * Hotel booking page template and form.
 *
 * Render the hotel booking form where guests can select check-in date,
 * nights and room type; posts to `functions/php/booking-submit.php`.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();

// Load init if present so BASE_URL and ROOT_DIR are available before any redirects
if (file_exists(__DIR__ . '/init.php')) { require_once __DIR__ . '/init.php'; }
if (!defined('ROOT_DIR')) { define('ROOT_DIR', __DIR__); }
require_once ROOT_DIR . '/functions/php/helpers.php';
require_once ROOT_DIR . '/functions/php/auth.php';

// Enforce login for booking actions, if user isn't logged in, redirect to booking page where
// the login form is displayed to keep the same UX / CTA behaviour.
$u = function_exists('current_user') ? current_user() : null;
if (!$u) {
    add_flash('notice', 'Please sign in to continue booking.');
    $redirectBase = defined('BASE_URL') && BASE_URL ? BASE_URL : '';
    header('Location: ' . $redirectBase . '/booking.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pageTitle = 'Hotel Booking - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>Book a Hotel Room</h2>
        <?php
            $u = function_exists('current_user') ? current_user() : null;
            $prefill_name = $u ? ($u['first_name'] . ' ' . $u['surname']) : '';
            $prefill_email = $u ? $u['email'] : '';
            $loyaltyMsg = '';
            if ($u) {
                try {
                    $pdo = Database::pdo();
                    $q = $pdo->prepare('SELECT created_at FROM bookings WHERE user_id = ? AND status = ? ORDER BY created_at DESC LIMIT 1');
                    $q->execute([$u['id'], 'active']);
                    $last = $q->fetch(PDO::FETCH_ASSOC);
                    if ($last && !empty($last['created_at'])) {
                        $lastDt = new DateTime($last['created_at']);
                        $now = new DateTime();
                        $diff = $now->diff($lastDt);
                        $days = (int)$diff->format('%a');
                        if ($days <= 180) { $loyaltyMsg = 'You are eligible for a ' . Database::getSetting('loyalty_6m_discount_pct', '10') . '% return discount.'; }
                        elseif ($days <= 365) { $loyaltyMsg = 'You qualify for a ' . Database::getSetting('loyalty_12m_discount_pct', '5') . '% return discount and ' . Database::getSetting('loyalty_12m_perk','Free breakfast for hotel bookings') . '.'; }
                        elseif ($days <= 730) { $loyaltyMsg = 'Thanks for returning! You qualify for ' . Database::getSetting('loyalty_24m_perk','Free breakfast and priority parking') . ' and ' . Database::getSetting('loyalty_24m_discount_pct','2') . '% discount.'; }
                    }
                } catch (Exception $e) { }
            }
        ?>
        <form class="card" method="post" action="/functions/php/booking-submit.php">
    <input type="hidden" name="type" value="hotel">
        <?php if (!$u): ?>
            <label>Name: <input class="input" type="text" name="name"></label><br>
            <label>Email: <input class="input" type="email" name="email"></label><br>
        <?php else: ?>
            <div><strong>Booking for</strong>: <?php echo htmlspecialchars($prefill_name, ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Email</strong>: <?php echo htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    <div class="form-row">
        <label style="flex:1">Check-in date: <input class="input" type="date" name="checkin"></label>
        <label style="width:120px">Nights: <input class="input" type="number" name="nights" min="1" value="1"></label>
    </div>
        <?php
            $single = get_price('hotel_single',50.0);
            $double = get_price('hotel_double',90.0);
            $suite = get_price('hotel_suite',150.0);
        ?>
        <label>Room type:
            <select name="room" id="room-select">
                <option value="single" data-price="<?php echo number_format($single,2,'.',''); ?>">Single - £<?php echo number_format($single,2); ?>/night</option>
                <option value="double" data-price="<?php echo number_format($double,2,'.',''); ?>">Double - £<?php echo number_format($double,2); ?>/night</option>
                <option value="suite" data-price="<?php echo number_format($suite,2,'.',''); ?>">Suite - £<?php echo number_format($suite,2); ?>/night</option>
            </select>
        </label>
        <p>Total price: £<span id="hotel-total"><?php echo number_format($single,2); ?></span></p>
        <script>
            /**
             * Hotel booking price helper.
             *
             * Calculates and updates the total price when the room type or number
             * of nights change. Uses `data-price` on room options and `input[name="nights"]`.
             *
             * @module hotelForm
             * @author Snat
             * @link https://snat.co.uk
             */
            (function(){
                /** @type {HTMLSelectElement} */
                var room = document.getElementById('room-select');
                /** @type {HTMLInputElement} */
                var nights = document.querySelector('input[name="nights"]');
                /** @type {HTMLElement} */
                var totalEl = document.getElementById('hotel-total');
                /**
                 * Recalculate and update the displayed hotel total.
                 * @returns {void}
                 */
                function update(){
                    var price = parseFloat(room.options[room.selectedIndex].getAttribute('data-price') || 0);
                    var n = Math.max(1, parseInt(nights.value || 1));
                    totalEl.textContent = (price * n).toFixed(2);
                }
                room.addEventListener('change', update);
                nights.addEventListener('input', update);
                update();
            })();
        </script>
    <button type="submit" class="btn">Reserve Room</button>
</form>
<?php if (!empty($loyaltyMsg)): ?><p><strong>Loyalty</strong>: <?php echo htmlspecialchars($loyaltyMsg); ?></p><?php endif; ?>
<?php require_once ROOT_DIR . '/templates/footer.php';
