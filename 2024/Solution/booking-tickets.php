<?php
/**
 * Ticket booking page for day tickets.
 *
 * Presents a form to select a date and number of tickets, then posts to
 * `functions/php/booking-submit.php`.
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
    exit;
}

$pageTitle = 'Tickets - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>Buy Tickets</h2>
        <?php
    $u = function_exists('current_user') ? current_user() : null;
    $prefill_name = $u ? ($u['first_name'] . ' ' . $u['surname']) : '';
    $prefill_email = $u ? $u['email'] : '';
?>
<form class="card" method="post" action="<?php echo BASE_URL ?: '/'; ?>/functions/php/booking-submit.php">
    <input type="hidden" name="type" value="tickets">
        <?php if (!$u): ?>
            <label>Name: <input class="input" type="text" name="name"></label><br>
            <label>Email: <input class="input" type="email" name="email"></label><br>
        <?php else: ?>
            <div><strong>Booking for</strong>: <?php echo htmlspecialchars($prefill_name, ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Email</strong>: <?php echo htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8'); ?></div>
            <!-- No name/email inputs present for logged-in users, handler will use session values -->
        <?php endif; ?>
    <div class="form-row">
        <label style="flex:1">Ticket date: <input class="input" type="date" name="ticket_date"></label>
        <label style="width:220px">Number of tickets: <input class="input" type="number" name="tickets" id="tickets-count" min="1" value="1"></label>
    </div>
        <?php $ticket_unit = get_price('ticket_price', 10.0); ?>
        <p>Ticket price: £<span id="ticket-unit"><?php echo number_format($ticket_unit,2); ?></span> each, Total: £<span id="ticket-total"><?php echo number_format($ticket_unit,2); ?></span></p>
        <script>
            /**
             * Ticket form helper.
             *
             * Updates the ticket total when the number of tickets changes.
             * Inline helper attached to `#tickets-count` input.
             *
             * @module ticketForm
             * @author Snat
             * @link https://snat.co.uk
             */
            (function(){
                /** @type {HTMLInputElement} */
                var tickets = document.getElementById('tickets-count');
                /** @type {number} Unit price in GBP as float */
                var unit = <?php echo number_format($ticket_unit,2,'.',''); ?>;
                /** @type {HTMLElement} */
                var unitEl = document.getElementById('ticket-unit');
                /** @type {HTMLElement} */
                var totalEl = document.getElementById('ticket-total');
                unitEl.textContent = unit.toFixed(2);
                /**
                 * Update total price based on selected tickets.
                 * @returns {void}
                 */
                function update(){
                    var n = Math.max(1, parseInt(tickets.value || 1));
                    totalEl.textContent = (n * unit).toFixed(2);
                }
                tickets.addEventListener('input', update);
                update();
            })();
        </script>
    <?php
        // Show loyalty message if logged in
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
        if (!empty($loyaltyMsg)) echo '<p><strong>Loyalty</strong>: ' . htmlspecialchars($loyaltyMsg) . '</p>';
    ?>
    <button type="submit" class="btn">Buy Tickets</button>
</form>
<?php require_once ROOT_DIR . '/templates/footer.php';
