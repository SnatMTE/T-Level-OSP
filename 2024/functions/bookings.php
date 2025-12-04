<?php
/**
 * Booking helper functions
 *
 * @author Maitiú Ellis
 * @package Functions
 * @description Functions to insert hotel bookings and ticket purchases
 */

/**
 * Insert a hotel booking record
 *
 * @param array $data Booking data: name,email,hotel_id,rooms,check_in,check_out,total_price
 * @return int Inserted booking ID
 */
function insertHotelBooking(array $data): int {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("INSERT INTO hotel_bookings (name, email, hotel_id, rooms, check_in, check_out, total_price) VALUES (:name, :email, :hotel_id, :rooms, :check_in, :check_out, :total_price)");
    $stmt->execute([
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':hotel_id' => $data['hotel_id'],
        ':rooms' => $data['rooms'],
        ':check_in' => $data['check_in'],
        ':check_out' => $data['check_out'],
        ':total_price' => $data['total_price'],
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Insert ticket purchases (multiple types allowed)
 *
 * @param array $purchases Array of purchases: each item has name,email,ticket_type_id,date,quantity,total_price
 * @return array Inserted IDs
 */
function insertTicketPurchases(array $purchases): array {
    $pdo = Database::getInstance();
    $inserted = [];
    $stmt = $pdo->prepare("INSERT INTO ticket_purchases (name, email, ticket_type_id, date, quantity, total_price) VALUES (:name, :email, :ticket_type_id, :date, :quantity, :total_price)");
    foreach ($purchases as $p) {
        $stmt->execute([
            ':name' => $p['name'],
            ':email' => $p['email'],
            ':ticket_type_id' => $p['ticket_type_id'],
            ':date' => $p['date'],
            ':quantity' => $p['quantity'],
            ':total_price' => $p['total_price'],
        ]);
        $inserted[] = (int)$pdo->lastInsertId();
    }
    return $inserted;
}
