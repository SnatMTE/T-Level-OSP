<?php
/**
 * Booking (merged) Page
 * Shows either hotel booking or tickets view based on GET `type` param
 *
 * @author Maitiú Ellis
 */

// Start session
session_start();

// Define root directory for consistent path resolution
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// Include necessary files
require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/helpers.php';
require_once ROOT_DIR . '/functions/auth.php';
require_once ROOT_DIR . '/functions/bookings.php';

// Initialize DB
Database::initialize();

// Set page title
$pageTitle = 'Booking - RZA';

$pdo = Database::getInstance();

// Handle form submissions (hotel or tickets)
if (isRequestMethod('POST')) {
    $type = getPost('booking_type', getGet('type', 'hotel'));

    if ($type === 'hotel') {
        $name = getPost('name');
        $email = getPost('email');
        $hotelId = (int)getPost('hotel_id');
        $rooms = (int)getPost('rooms');
        $checkIn = getPost('check_in');
        $checkOut = getPost('check_out');

        $nights = max(1, (int)round((strtotime($checkOut) - strtotime($checkIn)) / 86400));

        $stmt = $pdo->prepare("SELECT price_per_night FROM hotels WHERE id = :id");
        $stmt->execute([':id' => $hotelId]);
        $pricePerNight = (float)$stmt->fetchColumn();

        $total = $pricePerNight * $rooms * $nights;

        $bookingId = insertHotelBooking([
            'name' => $name,
            'email' => $email,
            'hotel_id' => $hotelId,
            'rooms' => $rooms,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => $total,
        ]);

        setFlash('success', "Hotel booking created (ID: {$bookingId}). Total: £" . number_format($total,2));
        redirect('booking.php?type=hotel');
    }

    if ($type === 'tickets') {
        $name = getPost('name');
        $email = getPost('email');
        $date = getPost('date');

        $ticketTypes = $pdo->query("SELECT * FROM ticket_types")->fetchAll(PDO::FETCH_ASSOC);

        $purchases = [];
        foreach ($ticketTypes as $tt) {
            $qtyKey = 'qty_' . $tt['id'];
            $qty = (int)getPost($qtyKey, 0);
            if ($qty > 0) {
                $total = $qty * (float)$tt['price'];
                $purchases[] = [
                    'name' => $name,
                    'email' => $email,
                    'ticket_type_id' => $tt['id'],
                    'date' => $date,
                    'quantity' => $qty,
                    'total_price' => $total,
                ];
            }
        }

        if (!empty($purchases)) {
            $ids = insertTicketPurchases($purchases);
            setFlash('success', 'Tickets purchased. IDs: ' . implode(',', $ids));
        } else {
            setFlash('error', 'No tickets selected.');
        }
        redirect('booking.php?type=tickets');
    }
}

// Determine view via GET parameter: ?type=hotel or ?type=tickets
$view = strtolower(getGet('type', 'hotel'));
if (!in_array($view, ['hotel', 'tickets'])) {
    $view = 'hotel';
}

// Fetch data for views
$hotels = $pdo->query("SELECT * FROM hotels")->fetchAll(PDO::FETCH_ASSOC);
$ticketTypes = $pdo->query("SELECT * FROM ticket_types")->fetchAll(PDO::FETCH_ASSOC);

// Include header
require_once ROOT_DIR . '/templates/header.php';
?>

<h1>Bookings</h1>

<nav>
    <a href="booking.php?type=hotel">Hotel Booking</a> | <a href="booking.php?type=tickets">Tickets</a>
</nav>

<?php if ($view === 'hotel'): ?>

    <section>
        <h2>Hotel Booking</h2>
        <form method="POST" action="booking.php?type=hotel">
            <input type="hidden" name="booking_type" value="hotel">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div>
                <label for="hotel_id">Hotel:</label>
                <select id="hotel_id" name="hotel_id">
                    <?php foreach ($hotels as $h): ?>
                        <option value="<?php echo esc($h['id']); ?>"><?php echo esc($h['name']); ?> - £<?php echo esc(number_format($h['price_per_night'],2)); ?> / night (<?php echo esc($h['total_rooms']); ?> rooms)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="rooms">Rooms:</label>
                <input type="number" id="rooms" name="rooms" value="1" min="1">
            </div>

            <div>
                <label for="check_in">Check-in:</label>
                <input type="date" id="check_in" name="check_in" required>
            </div>

            <div>
                <label for="check_out">Check-out:</label>
                <input type="date" id="check_out" name="check_out" required>
            </div>

            <button type="submit">Book Hotel</button>
        </form>
    </section>

<?php else: ?>

    <section>
        <h2>Tickets</h2>
        <form method="POST" action="booking.php?type=tickets">
            <input type="hidden" name="booking_type" value="tickets">

            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div>
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>

            <?php foreach ($ticketTypes as $tt): ?>
                <div>
                    <label><?php echo esc($tt['name']); ?> (£<?php echo esc(number_format($tt['price'],2)); ?>):</label>
                    <input type="number" name="qty_<?php echo esc($tt['id']); ?>" min="0" value="0">
                </div>
            <?php endforeach; ?>

            <button type="submit">Buy Tickets</button>
        </form>
    </section>

<?php endif; ?>

<?php
// Include footer template
require_once ROOT_DIR . '/templates/footer.php';
?>
